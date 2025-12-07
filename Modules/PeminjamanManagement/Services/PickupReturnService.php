<?php

namespace Modules\PeminjamanManagement\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\PeminjamanManagement\Entities\Peminjaman;
use Modules\PeminjamanManagement\Entities\PeminjamanItem;
use Modules\PeminjamanManagement\Entities\PeminjamanItemUnit;
use Modules\PeminjamanManagement\Events\PeminjamanStatusChanged;
use Modules\SaranaManagement\Entities\SaranaUnit;

class PickupReturnService
{
    /**
     * Validate pickup.
     */
    public function validatePickup(
        Peminjaman $peminjaman,
        int $validatedBy,
        ?UploadedFile $fotoFile = null,
        array $unitAssignments = []
    ): Peminjaman {
        return DB::transaction(function () use ($peminjaman, $validatedBy, $fotoFile, $unitAssignments) {
            $oldStatus = $peminjaman->status;
            // Upload foto if provided
            $fotoPath = null;
            if ($fotoFile) {
                $fotoPath = $this->storeFile($fotoFile, 'peminjaman/pickup');
            }

            // Assign serialized units if provided
            if (!empty($unitAssignments)) {
                $this->assignSerializedUnits($peminjaman, $unitAssignments, $validatedBy);
            }

            // Update peminjaman status
            $peminjaman->update([
                'status' => Peminjaman::STATUS_PICKED_UP,
                'pickup_validated_by' => $validatedBy,
                'pickup_validated_at' => now(),
                'foto_pickup_path' => $fotoPath,
            ]);

            Log::info('Pickup validated', [
                'peminjaman_id' => $peminjaman->id,
                'validated_by' => $validatedBy,
            ]);

            $peminjaman = $peminjaman->fresh();

            if ($oldStatus !== $peminjaman->status) {
                PeminjamanStatusChanged::dispatch($peminjaman, $oldStatus, $peminjaman->status);
            }

            return $peminjaman;
        });
    }

    /**
     * Validate return.
     */
    public function validateReturn(
        Peminjaman $peminjaman,
        int $validatedBy,
        ?UploadedFile $fotoFile = null
    ): Peminjaman {
        return DB::transaction(function () use ($peminjaman, $validatedBy, $fotoFile) {
            $oldStatus = $peminjaman->status;
            // Upload foto if provided
            $fotoPath = null;
            if ($fotoFile) {
                $fotoPath = $this->storeFile($fotoFile, 'peminjaman/return');
            }

            // Release all serialized units
            $this->releaseSerializedUnits($peminjaman, $validatedBy);

            // Update peminjaman status
            $peminjaman->update([
                'status' => Peminjaman::STATUS_RETURNED,
                'return_validated_by' => $validatedBy,
                'return_validated_at' => now(),
                'foto_return_path' => $fotoPath,
            ]);

            Log::info('Return validated', [
                'peminjaman_id' => $peminjaman->id,
                'validated_by' => $validatedBy,
            ]);

            $peminjaman = $peminjaman->fresh();

            if ($oldStatus !== $peminjaman->status) {
                PeminjamanStatusChanged::dispatch($peminjaman, $oldStatus, $peminjaman->status);
            }

            return $peminjaman;
        });
    }

    /**
     * Assign serialized units to peminjaman.
     */
    public function assignSerializedUnits(Peminjaman $peminjaman, array $unitAssignments, int $assignedBy): void
    {
        foreach ($unitAssignments as $itemId => $unitIds) {
            $item = PeminjamanItem::find($itemId);

            if (!$item || $item->peminjaman_id !== $peminjaman->id) {
                continue;
            }

            // Check if sarana is serialized
            if (optional($item->sarana)->type !== 'serialized') {
                continue;
            }

            foreach ($unitIds as $unitId) {
                // Check if unit exists and is available
                $unit = SaranaUnit::where('id', $unitId)
                    ->where('sarana_id', $item->sarana_id)
                    ->where('unit_status', 'tersedia')
                    ->first();

                if (!$unit) {
                    continue;
                }

                // Create assignment
                PeminjamanItemUnit::create([
                    'peminjaman_id' => $peminjaman->id,
                    'peminjaman_item_id' => $item->id,
                    'unit_id' => $unitId,
                    'assigned_by' => $assignedBy,
                    'assigned_at' => now(),
                    'status' => PeminjamanItemUnit::STATUS_ACTIVE,
                ]);

                // Update unit status
                $unit->update(['unit_status' => 'dipinjam']);
            }

            // Update qty_approved
            $assignedCount = PeminjamanItemUnit::forPeminjamanItem($item->id)
                ->active()
                ->count();

            $item->update(['qty_approved' => $assignedCount]);
        }
    }

    /**
     * Release serialized units from peminjaman.
     */
    public function releaseSerializedUnits(Peminjaman $peminjaman, ?int $releasedBy = null): void
    {
        $releasedBy = $releasedBy ?? Auth::id();

        $activeUnits = PeminjamanItemUnit::forPeminjaman($peminjaman->id)
            ->active()
            ->get();

        foreach ($activeUnits as $assignment) {
            // Update assignment status
            $assignment->update([
                'status' => PeminjamanItemUnit::STATUS_RELEASED,
                'released_by' => $releasedBy,
                'released_at' => now(),
            ]);

            // Update unit status back to tersedia
            if ($assignment->unit) {
                $assignment->unit->update(['unit_status' => 'tersedia']);
            }
        }

        // Update sarana stats
        $saranaIds = $peminjaman->items()->pluck('sarana_id')->unique();
        foreach ($saranaIds as $saranaId) {
            $sarana = \Modules\SaranaManagement\Entities\Sarana::find($saranaId);
            if ($sarana) {
                $sarana->updateStats();
            }
        }
    }

    /**
     * Update unit assignments for a peminjaman item.
     */
    public function updateUnitAssignments(
        PeminjamanItem $item,
        array $selectedUnitIds,
        int $updatedBy
    ): void {
        DB::transaction(function () use ($item, $selectedUnitIds, $updatedBy) {
            $peminjaman = $item->peminjaman;
            $selectedUnitIds = collect($selectedUnitIds)->map(fn ($id) => (int) $id)->filter()->values();

            // Get existing assignments
            $existingAssignments = PeminjamanItemUnit::forPeminjamanItem($item->id)
                ->forPeminjaman($peminjaman->id)
                ->get()
                ->keyBy('unit_id');

            $activeAssignments = $existingAssignments->filter(fn ($a) => $a->status === PeminjamanItemUnit::STATUS_ACTIVE);
            $activeUnitIds = $activeAssignments->keys();

            // Units to detach
            $unitsToDetach = $activeUnitIds->diff($selectedUnitIds);
            if ($unitsToDetach->isNotEmpty()) {
                foreach ($unitsToDetach as $unitId) {
                    $assignment = $existingAssignments->get($unitId);
                    if ($assignment) {
                        $assignment->update([
                            'status' => PeminjamanItemUnit::STATUS_RELEASED,
                            'released_by' => $updatedBy,
                            'released_at' => now(),
                        ]);
                    }
                }

                SaranaUnit::whereIn('id', $unitsToDetach->all())->update(['unit_status' => 'tersedia']);
            }

            // Units to attach
            $unitsToAttach = $selectedUnitIds->diff($activeUnitIds);
            foreach ($unitsToAttach as $unitId) {
                $existingAssignment = $existingAssignments->get($unitId);

                if ($existingAssignment) {
                    // Reactivate existing assignment
                    $existingAssignment->update([
                        'status' => PeminjamanItemUnit::STATUS_ACTIVE,
                        'released_by' => null,
                        'released_at' => null,
                        'assigned_by' => $updatedBy,
                        'assigned_at' => now(),
                    ]);
                } else {
                    // Create new assignment
                    $unit = SaranaUnit::where('id', $unitId)
                        ->where('sarana_id', $item->sarana_id)
                        ->where('unit_status', 'tersedia')
                        ->first();

                    if ($unit) {
                        PeminjamanItemUnit::create([
                            'peminjaman_id' => $peminjaman->id,
                            'peminjaman_item_id' => $item->id,
                            'unit_id' => $unitId,
                            'assigned_by' => $updatedBy,
                            'assigned_at' => now(),
                            'status' => PeminjamanItemUnit::STATUS_ACTIVE,
                        ]);

                        $unit->update(['unit_status' => 'dipinjam']);
                    }
                }
            }

            // Update qty_approved
            $item->update([
                'qty_approved' => PeminjamanItemUnit::forPeminjamanItem($item->id)
                    ->forPeminjaman($peminjaman->id)
                    ->active()
                    ->count(),
            ]);
        });
    }

    /**
     * Store uploaded file.
     */
    protected function storeFile(UploadedFile $file, string $baseDir): string
    {
        $dir = trim($baseDir, '/') . '/' . date('Y/m');
        return $file->store($dir, 'public');
    }
}
