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
                    $excludePeminjamanId
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
        $query = Peminjaman::where('prasarana_id', $prasaranaId)
            ->active()
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
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
        ?int $excludePeminjamanId = null
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
                $excludePeminjamanId
            );
        }

        // For pooled sarana, check quantity availability
        return $this->checkPooledSaranaConflict(
            $sarana,
            $qtyRequested,
            $startDate,
            $endDate,
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
        ?int $excludePeminjamanId = null
    ): ?string {
        // Get units that are already assigned in the date range
        $assignedUnitIds = PeminjamanItemUnit::active()
            ->whereHas('peminjamanItem', function ($q) use ($sarana) {
                $q->where('sarana_id', $sarana->id);
            })
            ->whereHas('peminjaman', function ($q) use ($startDate, $endDate, $excludePeminjamanId) {
                $q->active()
                    ->where(function ($q2) use ($startDate, $endDate) {
                        $q2->whereBetween('start_date', [$startDate, $endDate])
                            ->orWhereBetween('end_date', [$startDate, $endDate])
                            ->orWhere(function ($q3) use ($startDate, $endDate) {
                                $q3->where('start_date', '<=', $startDate)
                                    ->where('end_date', '>=', $endDate);
                            });
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
        ?int $excludePeminjamanId = null
    ): ?string {
        // Get total requested quantity in the date range
        $query = PeminjamanItem::where('sarana_id', $sarana->id)
            ->whereHas('peminjaman', function ($q) use ($startDate, $endDate, $excludePeminjamanId) {
                $q->active()
                    ->where(function ($q2) use ($startDate, $endDate) {
                        $q2->whereBetween('start_date', [$startDate, $endDate])
                            ->orWhereBetween('end_date', [$startDate, $endDate])
                            ->orWhere(function ($q3) use ($startDate, $endDate) {
                                $q3->where('start_date', '<=', $startDate)
                                    ->where('end_date', '>=', $endDate);
                            });
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

        // Check prasarana conflicts
        if ($peminjaman->prasarana_id) {
            $prasaranaConflicts = Peminjaman::where('prasarana_id', $peminjaman->prasarana_id)
                ->where('id', '!=', $peminjaman->id)
                ->pending()
                ->dateRange($peminjaman->start_date, $peminjaman->end_date)
                ->get();

            $conflicts = $conflicts->merge($prasaranaConflicts);
        }

        // Check sarana conflicts
        $saranaIds = $peminjaman->items()->pluck('sarana_id');
        if ($saranaIds->isNotEmpty()) {
            $saranaConflicts = Peminjaman::where('id', '!=', $peminjaman->id)
                ->pending()
                ->dateRange($peminjaman->start_date, $peminjaman->end_date)
                ->whereHas('items', function ($q) use ($saranaIds) {
                    $q->whereIn('sarana_id', $saranaIds);
                })
                ->get();

            $conflicts = $conflicts->merge($saranaConflicts);
        }

        return $conflicts->unique('id');
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
                $query->where(function ($q) use ($item) {
                    $q->whereBetween('start_date', [$item->peminjaman->start_date, $item->peminjaman->end_date])
                        ->orWhereBetween('end_date', [$item->peminjaman->start_date, $item->peminjaman->end_date])
                        ->orWhere(function ($q2) use ($item) {
                            $q2->where('start_date', '<=', $item->peminjaman->start_date)
                                ->where('end_date', '>=', $item->peminjaman->end_date);
                        });
                });
            })
            ->pluck('unit_id');
    }
}
