<?php

namespace Modules\PeminjamanManagement\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\PeminjamanManagement\Entities\Peminjaman;

class PeminjamanReportService
{
    /**
     * Retrieve paginated peminjaman report data with summary metrics.
     */
    public function getPeminjamanReportData(array $filters, int $perPage = 15): array
    {
        $baseQuery = $this->buildBaseQuery($filters);

        /** @var LengthAwarePaginator $paginator */
        $paginator = (clone $baseQuery)
            ->orderByDesc('start_date')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->appends($filters);

        $collection = (clone $baseQuery)->get();

        return [
            'paginator' => $paginator,
            'summary' => $this->summarizeCollection($collection),
        ];
    }

    /**
     * Retrieve all peminjaman rows for export (without pagination).
     */
    public function getPeminjamanRowsForExport(array $filters): Collection
    {
        return (clone $this->buildBaseQuery($filters))
            ->orderByDesc('start_date')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Compute summary metrics for a peminjaman collection.
     */
    public function summarizeCollection(Collection $collection): array
    {
        $statusCounts = $collection
            ->groupBy('status')
            ->map(static fn (Collection $group) => $group->count())
            ->toArray();

        $totalParticipants = $collection->sum(static fn ($peminjaman) => (int) ($peminjaman->jumlah_peserta ?? 0));

        $totalDurationHours = $collection->sum(function ($peminjaman) {
            return $this->calculateDurationHours($peminjaman);
        });

        $totalItemsApproved = $collection->sum(function ($peminjaman) {
            if (!method_exists($peminjaman, 'items') || !$peminjaman->relationLoaded('items')) {
                $peminjaman->loadMissing('items');
            }

            return $peminjaman->items->sum(static fn ($item) => (int) ($item->approved_quantity ?? 0));
        });

        return [
            'total_records' => $collection->count(),
            'status_counts' => $statusCounts,
            'total_participants' => $totalParticipants,
            'total_duration_hours' => $totalDurationHours,
            'total_items_approved' => $totalItemsApproved,
        ];
    }

    /**
     * Build the base query for peminjaman reports.
     */
    protected function buildBaseQuery(array $filters): Builder
    {
        $query = Peminjaman::query()->with([
            'user:id,name',
            'prasarana:id,name',
            'items.sarana:id,nama,type',
            'ukm:id,nama',
        ]);

        return $this->applyFilters($query, $filters);
    }

    /**
     * Apply report filters to the query.
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $start = Carbon::parse($filters['start_date'])->startOfDay();
        $end = Carbon::parse($filters['end_date'])->endOfDay();

        $query->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString());

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'conflicted') {
                $query->whereNotNull('konflik');
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['search'])) {
            $keyword = '%' . $filters['search'] . '%';

            $query->where(function (Builder $builder) use ($keyword) {
                $builder->where('event_name', 'like', $keyword)
                    ->orWhereHas('user', static function (Builder $userQuery) use ($keyword) {
                        $userQuery->where('name', 'like', $keyword);
                    })
                    ->orWhereHas('prasarana', static function (Builder $prasaranaQuery) use ($keyword) {
                        $prasaranaQuery->where('name', 'like', $keyword);
                    })
                    ->orWhereHas('ukm', static function (Builder $ukmQuery) use ($keyword) {
                        $ukmQuery->where('nama', 'like', $keyword);
                    });
            });
        }

        if (!empty($filters['pickup_status'])) {
            if ($filters['pickup_status'] === 'not_picked') {
                $query->whereNull('pickup_validated_at');
            } elseif ($filters['pickup_status'] === 'picked') {
                $query->whereNotNull('pickup_validated_at');
            }
        }

        return $query;
    }

    /**
     * Calculate duration hours for a peminjaman entry.
     */
    protected function calculateDurationHours(Peminjaman $peminjaman): int
    {
        $startDate = $peminjaman->start_date ? $peminjaman->start_date->copy() : null;
        $endDate = $peminjaman->end_date ? $peminjaman->end_date->copy() : null;

        if (!$startDate || !$endDate) {
            return 0;
        }

        if ($peminjaman->start_time && $peminjaman->end_time) {
            $startDateTime = $startDate->copy()->setTimeFromTimeString($peminjaman->start_time);
            $endDateTime = $endDate->copy()->setTimeFromTimeString($peminjaman->end_time);

            if ($endDateTime->lessThanOrEqualTo($startDateTime)) {
                $endDateTime = $endDateTime->addDay();
            }

            return max(1, $startDateTime->diffInHours($endDateTime));
        }

        return max(1, ($startDate->diffInDays($endDate) + 1) * 24);
    }
}
