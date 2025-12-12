<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Modules\MarkingManagement\Entities\Marking;
use Modules\PeminjamanManagement\Entities\Peminjaman;
use Modules\SaranaManagement\Entities\Sarana;

class DashboardService
{
    public function getCoreStats(): array
    {
        $userModel = \App\Models\User::class;
        $roleModel = \App\Models\Role::class;
        $permissionModel = \Spatie\Permission\Models\Permission::class;

        return [
            'total_users' => $userModel::count(),
            'total_roles' => $roleModel::count(),
            'total_permissions' => $permissionModel::count(),
            'active_users' => $userModel::where('status', 'active')->count(),
        ];
    }

    /**
     * Get top borrowed sarana for dashboard.
     *
     * For now, only users with peminjaman.view permission will see data.
     */
    public function getTopSaranaUsageForUser(User $user, int $days = 30, int $limit = 5): array
    {
        if (!$user->can('peminjaman.view')) {
            return [];
        }

        $end = Carbon::now()->endOfDay();

        // Jika days == 30, gunakan bulan berjalan agar sinkron dengan kebutuhan laporan
        if ($days === 30) {
            $start = $end->copy()->startOfMonth();
        } else {
            $start = $end->copy()->subDays(max($days, 1) - 1)->startOfDay();
        }

        $query = Peminjaman::query()
            ->with(['items.sarana'])
            ->whereIn('status', [
                Peminjaman::STATUS_APPROVED,
                Peminjaman::STATUS_PICKED_UP,
                Peminjaman::STATUS_RETURNED,
            ])
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString());

        $collection = $query->get();

        if ($collection->isEmpty()) {
            return [];
        }

        $usageMap = [];

        foreach ($collection as $peminjaman) {
            $durationHours = max(1, $this->calculateDurationHours($peminjaman));

            foreach ($peminjaman->items as $item) {
                $sarana = $item->sarana;

                if (!$sarana) {
                    continue;
                }

                $approvedQty = (int) ($item->approved_quantity ?? $item->qty_approved ?? 0);

                if ($approvedQty <= 0) {
                    continue;
                }

                $saranaId = (int) $sarana->id;

                if (!isset($usageMap[$saranaId])) {
                    $usageMap[$saranaId] = [
                        'sarana_id' => $saranaId,
                        'name' => $sarana->name,
                        'type' => $sarana->type,
                        'total_qty' => 0,
                        'used_hours' => 0,
                    ];
                }

                $usageMap[$saranaId]['total_qty'] += $approvedQty;
                $usageMap[$saranaId]['used_hours'] += $approvedQty * $durationHours;
            }
        }

        if (empty($usageMap)) {
            return [];
        }

        $results = collect($usageMap)
            ->sortByDesc('used_hours')
            ->take($limit)
            ->values()
            ->map(function (array $row) use ($start, $end) {
                $row['used_hours'] = (int) round($row['used_hours']);
                $row['period_start'] = $start->toDateString();
                $row['period_end'] = $end->toDateString();

                return $row;
            })
            ->all();

        return $results;
    }

    /**
     * Get yearly loan totals (per month) for a given user (admin will see all loans).
     */
    public function getYearlyLoanTotalsForUser(User $user, ?int $year = null): array
    {
        $year = $year ?? (int) now()->year;

        $baseQuery = Peminjaman::query();
        if (!$user->can('peminjaman.view')) {
            $baseQuery->where('user_id', $user->id);
        }

        $availableYears = (clone $baseQuery)
            ->selectRaw('DISTINCT YEAR(created_at) as year')
            ->whereNotNull('created_at')
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->map(static fn ($value) => (int) $value)
            ->values()
            ->all();

        if (!in_array($year, $availableYears, true)) {
            $availableYears[] = $year;
        }

        $availableYears = array_values(array_unique($availableYears));
        rsort($availableYears);

        $monthlyCounts = (clone $baseQuery)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->pluck('total', 'month');

        $monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $series = [];

        for ($month = 1; $month <= 12; $month++) {
            $series[] = (int) ($monthlyCounts[$month] ?? 0);
        }

        $total = array_sum($series);
        $max = $total > 0 ? max($series) : 0;
        $peakIndex = $max > 0 ? array_search($max, $series, true) : null;
        $peakLabel = $peakIndex !== null ? $monthLabels[$peakIndex] : null;

        return [
            'year' => $year,
            'labels' => $monthLabels,
            'data' => $series,
            'total' => $total,
            'max' => $max,
            'peak_month' => $peakLabel,
            'available_years' => $availableYears,
        ];
    }

    /**
     * Build calendar events for an arbitrary date range (peminjaman only).
     */
    public function getCalendarEventsForRange(User $user, Carbon $start, Carbon $end): array
    {
        // Peminjaman events
        $loanQuery = Peminjaman::query();

        if (!$user->can('peminjaman.view')) {
            $loanQuery->where('user_id', $user->id);
        }

        $peminjaman = $loanQuery
            ->with(['prasarana:id,name'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhere(function ($nested) use ($start, $end) {
                        $nested->where('start_date', '<', $start->toDateString())
                            ->where('end_date', '>', $end->toDateString());
                    });
            })
            ->limit(100)
            ->orderBy('start_date')
            ->get();

        $loanEvents = $peminjaman->map(function (Peminjaman $item) {
            $startTime = $item->start_time;
            $endTime = $item->end_time;

            $startDateTime = null;
            if ($item->start_date) {
                $startDateTime = $item->start_date->copy();
                if ($startTime) {
                    $startDateTime->setTimeFromTimeString($startTime);
                }
            }

            $endDateTime = null;
            if ($item->end_date) {
                $endDateTime = $item->end_date->copy();
                if ($endTime) {
                    $endDateTime->setTimeFromTimeString($endTime);
                }
            }

            return [
                'id' => $item->id,
                'title' => $item->event_name ?? 'Peminjaman #' . $item->id,
                'type' => 'peminjaman',
                // Tanggal utama event: tanggal acara dimulai
                'date' => $item->start_date ? $item->start_date->toDateString() : null,
                'start' => $startDateTime ? $startDateTime->toIso8601String() : ($item->start_date ? $item->start_date->toDateString() : null),
                'end' => $endDateTime ? $endDateTime->toIso8601String() : ($item->end_date ? $item->end_date->toDateString() : null),
                'location' => $item->prasarana?->name ?? $item->lokasi_custom,
                'status' => $item->status,
                'url' => Route::has('peminjaman.show') ? route('peminjaman.show', $item->id) : null,
            ];
        })->all();

        // Marking events (jadwal acara yang direncanakan)
        $markingQuery = Marking::query();

        if (!$user->can('peminjaman.view')) {
            $markingQuery->where('user_id', $user->id);
        }

        $markings = $markingQuery
            ->with(['prasarana:id,name'])
            ->whereBetween('start_datetime', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->whereIn('status', [
                Marking::STATUS_ACTIVE,
                Marking::STATUS_CONVERTED,
            ])
            ->limit(100)
            ->orderBy('start_datetime')
            ->get();

        $markingEvents = $markings->map(function (Marking $item) {
            $start = $item->start_datetime;
            $end = $item->end_datetime;

            return [
                'id' => $item->id,
                'title' => $item->event_name ?? 'Marking #' . $item->id,
                'type' => 'marking',
                'date' => $start ? $start->toDateString() : null,
                'start' => $start ? $start->toIso8601String() : null,
                'end' => $end ? $end->toIso8601String() : null,
                'location' => $item->getLocation(),
                'status' => $item->status,
                'url' => Route::has('marking.show') ? route('marking.show', $item->id) : null,
            ];
        })->all();

        return array_values(array_merge($loanEvents, $markingEvents));
    }

    /**
     * Convenience helper: events for current month (used by server-rendered calendar list).
     */
    public function getMonthlyCalendarEventsForUser(User $user): array
    {
        $start = now()->copy()->startOfMonth();
        $end = now()->copy()->endOfMonth();

        return $this->getCalendarEventsForRange($user, $start, $end);
    }

    /**
     * Build quick actions tailored to user permissions.
     */
    public function getQuickActionsForUser(User $user): array
    {
        $actions = [];

        // Admin / approver actions
        if ($user->can('peminjaman.create')) {
            $this->appendAction($actions, [
                'title' => 'Ajukan Peminjaman',
                'icon' => 'heroicon-o-clipboard-document-list',
                'route' => 'peminjaman.create',
            ]);
        }

        if ($user->can('peminjaman.view')) {
            $this->appendAction($actions, [
                'title' => 'Lihat Semua Peminjaman',
                'icon' => 'heroicon-o-inbox-stack',
                'route' => 'peminjaman.index',
            ]);
        }

        if ($user->can('report.view') && Route::has('peminjaman.reports.index')) {
            $this->appendAction($actions, [
                'title' => 'Lihat Laporan',
                'icon' => 'heroicon-o-chart-bar',
                'route' => 'peminjaman.reports.index',
            ]);
        }

        // Actions for all users
        $this->appendAction($actions, [
            'title' => 'Peminjaman Saya',
            'icon' => 'heroicon-o-clock',
            'route' => 'peminjaman.index',
        ]);

        return $actions;
    }

    protected function appendAction(array &$actions, array $meta): void
    {
        if (!empty($meta['route']) && Route::has($meta['route'])) {
            $actions[] = [
                'title' => $meta['title'],
                'icon' => $meta['icon'] ?? 'heroicon-o-arrow-right-circle',
                'url' => route($meta['route']),
            ];
        }
    }

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
