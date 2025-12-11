<?php

namespace Modules\PeminjamanManagement\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\PeminjamanManagement\Entities\Peminjaman;
use Modules\PeminjamanManagement\Entities\PeminjamanItem;
use Modules\PeminjamanManagement\Entities\PeminjamanItemUnit;
use Modules\SaranaManagement\Entities\Sarana;

class SlotConflictService
{
    protected ?array $lastConflict = null;

    /**
     * Check for conflicts in peminjaman request.
     */
    public function checkConflicts(Request $request, ?int $excludePeminjamanId = null): ?string
    {
        $this->lastConflict = null;

        // Check prasarana conflict
        if ($request->filled('prasarana_id')) {
            $conflict = $this->checkPrasaranaConflict(
                $request->prasarana_id,
                $request->start_date,
                $request->end_date,
                $request->start_time,
                $request->end_time,
                $excludePeminjamanId
            );

            if ($conflict) {
                return $conflict;
            }
        }

        // Check sarana conflicts
        if ($request->filled('sarana_items')) {
            foreach ($request->sarana_items as $item) {
                $conflict = $this->checkSaranaConflict(
                    $item['sarana_id'],
                    $item['qty_requested'],
                    $request->start_date,
                    $request->end_date,
                    $excludePeminjamanId,
                    $request->start_time,
                    $request->end_time
                );

                if ($conflict) {
                    return $conflict;
                }
            }
        }

        return null;
    }

    /**
     * Check prasarana conflict.
     */
    public function checkPrasaranaConflict(
        int $prasaranaId,
        string $startDate,
        string $endDate,
        ?string $startTime,
        ?string $endTime,
        ?int $excludePeminjamanId = null
    ): ?string {
        $startDateTime = $this->getStartDateTime($startDate, $startTime);
        $endDateTime = $this->getEndDateTime($endDate, $endTime);

        $query = Peminjaman::where('prasarana_id', $prasaranaId)
            ->active()
            ->where(function ($q) use ($startDateTime, $endDateTime) {
                $q->whereRaw('CONCAT(start_date, " ", COALESCE(start_time, "00:00:00")) <= ?', [$endDateTime])
                    ->whereRaw('CONCAT(end_date, " ", COALESCE(end_time, "23:59:59")) >= ?', [$startDateTime]);
            });

        if ($excludePeminjamanId) {
            $query->where('id', '!=', $excludePeminjamanId);
        }

        $conflictingPeminjaman = $query->first();

        if ($conflictingPeminjaman) {
            $this->lastConflict = [
                'type' => 'prasarana',
                'peminjaman' => $conflictingPeminjaman,
            ];

            return "Prasarana sudah dipinjam pada tanggal tersebut oleh {$conflictingPeminjaman->user->name} untuk acara '{$conflictingPeminjaman->event_name}'.";
        }

        return null;
    }

    /**
     * Check sarana conflict.
     */
    public function checkSaranaConflict(
        int $saranaId,
        int $qtyRequested,
        string $startDate,
        string $endDate,
        ?int $excludePeminjamanId = null,
        ?string $startTime = null,
        ?string $endTime = null
    ): ?string {
        $sarana = Sarana::find($saranaId);

        if (!$sarana) {
            return "Sarana tidak ditemukan.";
        }

        // For serialized sarana, check unit availability
        if ($sarana->type === 'serialized') {
            return $this->checkSerializedSaranaConflict(
                $sarana,
                $qtyRequested,
                $startDate,
                $endDate,
                $startTime,
                $endTime,
                $excludePeminjamanId
            );
        }

        // For pooled sarana, check quantity availability
        return $this->checkPooledSaranaConflict(
            $sarana,
            $qtyRequested,
            $startDate,
            $endDate,
            $startTime,
            $endTime,
            $excludePeminjamanId
        );
    }

    /**
     * Check serialized sarana conflict.
     */
    protected function checkSerializedSaranaConflict(
        Sarana $sarana,
        int $qtyRequested,
        string $startDate,
        string $endDate,
        ?string $startTime,
        ?string $endTime,
        ?int $excludePeminjamanId = null
    ): ?string {
        $startDateTime = $this->getStartDateTime($startDate, $startTime);
        $endDateTime = $this->getEndDateTime($endDate, $endTime);

        // Get units that are already assigned in the date range
        $assignedUnitIds = PeminjamanItemUnit::active()
            ->whereHas('peminjamanItem', function ($q) use ($sarana) {
                $q->where('sarana_id', $sarana->id);
            })
            ->whereHas('peminjaman', function ($q) use ($startDateTime, $endDateTime, $excludePeminjamanId) {
                $q->active()
                    ->where(function ($q2) use ($startDateTime, $endDateTime) {
                        $q2->whereRaw('CONCAT(start_date, " ", COALESCE(start_time, "00:00:00")) <= ?', [$endDateTime])
                            ->whereRaw('CONCAT(end_date, " ", COALESCE(end_time, "23:59:59")) >= ?', [$startDateTime]);
                    });

                if ($excludePeminjamanId) {
                    $q->where('peminjaman_id', '!=', $excludePeminjamanId);
                }
            })
            ->pluck('unit_id');

        $availableUnits = $sarana->units()
            ->where('unit_status', 'tersedia')
            ->whereNotIn('id', $assignedUnitIds)
            ->count();

        if ($availableUnits < $qtyRequested) {
            return "Sarana '{$sarana->nama}' tidak memiliki cukup unit tersedia. Tersedia: {$availableUnits}, Diminta: {$qtyRequested}.";
        }

        return null;
    }

    /**
     * Check pooled sarana conflict.
     */
    protected function checkPooledSaranaConflict(
        Sarana $sarana,
        int $qtyRequested,
        string $startDate,
        string $endDate,
        ?string $startTime,
        ?string $endTime,
        ?int $excludePeminjamanId = null
    ): ?string {
        $startDateTime = $this->getStartDateTime($startDate, $startTime);
        $endDateTime = $this->getEndDateTime($endDate, $endTime);

        // Get total requested quantity in the date range
        $query = PeminjamanItem::where('sarana_id', $sarana->id)
            ->whereHas('peminjaman', function ($q) use ($startDateTime, $endDateTime, $excludePeminjamanId) {
                $q->active()
                    ->where(function ($q2) use ($startDateTime, $endDateTime) {
                        $q2->whereRaw('CONCAT(start_date, " ", COALESCE(start_time, "00:00:00")) <= ?', [$endDateTime])
                            ->whereRaw('CONCAT(end_date, " ", COALESCE(end_time, "23:59:59")) >= ?', [$startDateTime]);
                    });

                if ($excludePeminjamanId) {
                    $q->where('peminjaman_id', '!=', $excludePeminjamanId);
                }
            });

        $reservedQty = $query->sum('qty_requested');
        $availableQty = $sarana->jumlah_tersedia - $reservedQty;

        if ($availableQty < $qtyRequested) {
            return "Sarana '{$sarana->nama}' tidak memiliki cukup stok tersedia. Tersedia: {$availableQty}, Diminta: {$qtyRequested}.";
        }

        return null;
    }

    /**
     * Find pending conflicts for a peminjaman.
     */
    public function findPendingConflicts(Peminjaman $peminjaman): Collection
    {
        $conflicts = collect();

        // Normalize start/end datetime from casted attributes (Carbon or string)
        $startDate = $peminjaman->start_date instanceof \Carbon\Carbon
            ? $peminjaman->start_date->toDateString()
            : (string) $peminjaman->start_date;

        $endDate = $peminjaman->end_date instanceof \Carbon\Carbon
            ? $peminjaman->end_date->toDateString()
            : (string) $peminjaman->end_date;

        $startTime = $peminjaman->start_time instanceof \Carbon\Carbon
            ? $peminjaman->start_time->format('H:i:s')
            : ($peminjaman->start_time ? (string) $peminjaman->start_time : null);

        $endTime = $peminjaman->end_time instanceof \Carbon\Carbon
            ? $peminjaman->end_time->format('H:i:s')
            : ($peminjaman->end_time ? (string) $peminjaman->end_time : null);

        $startDateTime = $this->getStartDateTime($startDate, $startTime);
        $endDateTime = $this->getEndDateTime($endDate, $endTime);

        // Check prasarana conflicts
        if ($peminjaman->prasarana_id) {
            $prasaranaConflicts = Peminjaman::where('prasarana_id', $peminjaman->prasarana_id)
                ->where('id', '!=', $peminjaman->id)
                ->active()
                ->where(function ($q) use ($startDateTime, $endDateTime) {
                    $q->whereRaw('CONCAT(start_date, " ", COALESCE(start_time, "00:00:00")) <= ?', [$endDateTime])
                        ->whereRaw('CONCAT(end_date, " ", COALESCE(end_time, "23:59:59")) >= ?', [$startDateTime]);
                })
                ->get();

            $conflicts = $conflicts->merge($prasaranaConflicts);
        }

        // Check sarana conflicts
        $saranaIds = $peminjaman->items()->pluck('sarana_id');
        if ($saranaIds->isNotEmpty()) {
            $saranaConflicts = Peminjaman::where('id', '!=', $peminjaman->id)
                ->active()
                ->where(function ($q) use ($startDateTime, $endDateTime) {
                    $q->whereRaw('CONCAT(start_date, " ", COALESCE(start_time, "00:00:00")) <= ?', [$endDateTime])
                        ->whereRaw('CONCAT(end_date, " ", COALESCE(end_time, "23:59:59")) >= ?', [$startDateTime]);
                })
                ->whereHas('items', function ($q) use ($saranaIds) {
                    $q->whereIn('sarana_id', $saranaIds);
                })
                ->get();

            $conflicts = $conflicts->merge($saranaConflicts);
        }

        return $conflicts->unique('id');
    }

    protected function getStartDateTime(string $date, ?string $time): string
    {
        return $date . ' ' . ($time ?? '00:00:00');
    }

    protected function getEndDateTime(string $date, ?string $time): string
    {
        return $date . ' ' . ($time ?? '23:59:59');
    }

    /**
     * Get last conflict info.
     */
    public function getLastConflict(): ?array
    {
        return $this->lastConflict;
    }

    /**
     * Get conflicting unit IDs for a peminjaman item.
     */
    public function getConflictingUnitIds(PeminjamanItem $item, Collection $unitIds): Collection
    {
        if ($unitIds->isEmpty()) {
            return collect();
        }

        return PeminjamanItemUnit::whereIn('unit_id', $unitIds)
            ->active()
            ->where('peminjaman_id', '!=', $item->peminjaman_id)
            ->whereHas('peminjaman', function ($query) {
                $query->whereNotIn('status', [
                    Peminjaman::STATUS_RETURNED,
                    Peminjaman::STATUS_CANCELLED,
                    Peminjaman::STATUS_REJECTED,
                ]);
            })
            ->whereHas('peminjaman', function ($query) use ($item) {
                $startDateTime = $this->getStartDateTime($item->peminjaman->start_date, $item->peminjaman->start_time);
                $endDateTime = $this->getEndDateTime($item->peminjaman->end_date, $item->peminjaman->end_time);

                $query->where(function ($q) use ($startDateTime, $endDateTime) {
                    $q->whereRaw('CONCAT(start_date, " ", COALESCE(start_time, "00:00:00")) <= ?', [$endDateTime])
                        ->whereRaw('CONCAT(end_date, " ", COALESCE(end_time, "23:59:59")) >= ?', [$startDateTime]);
                });
            })
            ->pluck('unit_id');
    }
}
