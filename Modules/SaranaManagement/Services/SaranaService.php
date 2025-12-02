<?php

namespace Modules\SaranaManagement\Services;

use Modules\SaranaManagement\Entities\Sarana;
use Modules\SaranaManagement\Entities\SaranaUnit;
use Modules\SaranaManagement\Repositories\Interfaces\SaranaRepositoryInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SaranaService
{
    public function __construct(
        private readonly SaranaRepositoryInterface $saranaRepository,
        private readonly DatabaseManager $database
    ) {}

    /**
     * Get all saranas with filters and pagination
     */
    public function getSaranas(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->saranaRepository->getAll($filters, $perPage);
    }

    /**
     * Find sarana by ID
     */
    public function getSaranaById(int $id): ?Sarana
    {
        return $this->saranaRepository->findById($id);
    }

    /**
     * Create new sarana with transaction
     */
    public function createSarana(array $data): Sarana
    {
        return $this->database->transaction(function () use ($data) {
            // Generate kode sarana jika tidak ada
            if (empty($data['kode_sarana'])) {
                $data['kode_sarana'] = $this->generateKodeSarana();
            }

            // Handle file upload
            if (isset($data['foto']) && $data['foto'] instanceof UploadedFile) {
                $data['foto'] = $this->uploadFoto($data['foto']);
            } else {
                unset($data['foto']); // Remove if not uploaded
            }
            // Normalisasi data jumlah berdasarkan type
            $data = $this->normalizeQuantityData($data);

            return $this->saranaRepository->create($data);
        });
    }

    /**
     * Update existing sarana with transaction
     */
    public function updateSarana(Sarana $sarana, array $data): Sarana
    {
        return $this->database->transaction(function () use ($sarana, $data) {
            $hapusFoto = !empty($data['hapus_foto']);

            // Handle file upload
            if (isset($data['foto']) && $data['foto'] instanceof UploadedFile) {
                // Delete old foto if exists
                if ($sarana->foto) {
                    $this->deleteFoto($sarana->foto);
                }
                
                // Upload new foto
                $data['foto'] = $this->uploadFoto($data['foto']);
            } else {
                if ($hapusFoto && $sarana->foto) {
                    // Explicitly delete existing foto without uploading a new one
                    $this->deleteFoto($sarana->foto);
                    $data['foto'] = null;
                } else {
                    // Keep old foto
                    unset($data['foto']);
                }
            }

            // Field kontrol tidak perlu dikirim ke repository
            unset($data['hapus_foto']);
            // Normalisasi data jumlah berdasarkan type
            $data = $this->normalizeQuantityData($data, $sarana);

            return $this->saranaRepository->update($sarana, $data);
        });
    }

    /**
     * Delete sarana
     */
    public function deleteSarana(Sarana $sarana): void
    {
        $this->database->transaction(function () use ($sarana) {
            // TODO: Check if sarana is being borrowed
            // if ($sarana->peminjaman()->where('status', 'dipinjam')->exists()) {
            //     throw new \RuntimeException('Sarana sedang dipinjam, tidak dapat dihapus.');
            // }

            // Delete foto file if exists
            if ($sarana->foto) {
                $this->deleteFoto($sarana->foto);
            }

            $this->saranaRepository->delete($sarana);
        });
    }

    /**
     * Get saranas by kategori
     */
    public function getSaranasByKategori(int $kategoriId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->saranaRepository->getByKategori($kategoriId, $perPage);
    }

    /**
     * Find sarana by kode
     */
    public function findSaranaByKode(string $kode): ?Sarana
    {
        return $this->saranaRepository->findByKode($kode);
    }

    /**
     * Tambah satu unit untuk sarana serialized
     */
    public function addUnit(Sarana $sarana, string $unitCode, string $status = 'tersedia'): SaranaUnit
    {
        return $this->database->transaction(function () use ($sarana, $unitCode, $status) {
            $unit = new SaranaUnit([
                'unit_code' => $unitCode,
                'unit_status' => $status,
            ]);

            $sarana->units()->save($unit);

            $sarana->refresh();
            $sarana->updateStats();

            return $unit;
        });
    }

    /**
     * Update unit sarana
     */
    public function updateUnit(SaranaUnit $unit, array $data): SaranaUnit
    {
        return $this->database->transaction(function () use ($unit, $data) {
            $unit->fill($data);
            $unit->save();

            $sarana = $unit->sarana;
            if ($sarana) {
                $sarana->refresh();
                $sarana->updateStats();
            }

            return $unit;
        });
    }

    /**
     * Hapus unit sarana
     */
    public function deleteUnit(SaranaUnit $unit): void
    {
        $this->database->transaction(function () use ($unit) {
            $sarana = $unit->sarana;

            $unit->delete();

            if ($sarana) {
                $sarana->refresh();
                $sarana->updateStats();
            }
        });
    }

    /**
     * Tambah beberapa unit sekaligus
     */
    public function addBulkUnits(Sarana $sarana, array $unitCodes, string $status = 'tersedia'): array
    {
        return $this->database->transaction(function () use ($sarana, $unitCodes, $status) {
            $units = [];

            foreach ($unitCodes as $code) {
                if (! $code) {
                    continue;
                }

                $units[] = $sarana->units()->create([
                    'unit_code' => $code,
                    'unit_status' => $status,
                ]);
            }

            $sarana->refresh();
            $sarana->updateStats();

            return $units;
        });
    }

    /**
     * Update status beberapa unit sekaligus
     */
    public function updateBulkUnitStatus(Sarana $sarana, array $unitIds, string $status): int
    {
        return $this->database->transaction(function () use ($sarana, $unitIds, $status) {
            if (empty($unitIds)) {
                return 0;
            }

            $updated = $sarana->units()
                ->whereIn('id', $unitIds)
                ->update(['unit_status' => $status]);

            $sarana->refresh();
            $sarana->updateStats();

            return $updated;
        });
    }

    /**
     * Generate unique kode sarana
     */
    private function generateKodeSarana(): string
    {
        $lastSarana = Sarana::orderBy('id', 'desc')->first();
        $nextNumber = $lastSarana ? (int) substr($lastSarana->kode_sarana, 4) + 1 : 1;

        return 'SRN-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Upload foto sarana
     */
    private function uploadFoto(UploadedFile $file): string
    {
        // Generate unique filename
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        // Store in public/storage/saranas directory
        $path = $file->storeAs('saranas', $filename, 'public');

        return $path;
    }

    /**
     * Delete foto sarana from storage
     */
    private function deleteFoto(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Normalize quantity-related fields based on sarana type
     */
    private function normalizeQuantityData(array $data, ?Sarana $existing = null): array
    {
        $type = $data['type'] ?? $existing?->type ?? 'pooled';

        // Pastikan jumlah_total ada
        if (!isset($data['jumlah_total'])) {
            $data['jumlah_total'] = $existing?->jumlah_total ?? 1;
        }

        if ($type === 'pooled') {
            $total = (int) ($data['jumlah_total'] ?? 0);

            $rusak = (int) ($data['jumlah_rusak'] ?? ($existing?->jumlah_rusak ?? 0));
            $maintenance = (int) ($data['jumlah_maintenance'] ?? ($existing?->jumlah_maintenance ?? 0));
            $hilang = (int) ($data['jumlah_hilang'] ?? ($existing?->jumlah_hilang ?? 0));

            if (isset($data['jumlah_tersedia'])) {
                $tersedia = (int) $data['jumlah_tersedia'];
            } else {
                $tersedia = max(0, $total - $rusak - $maintenance - $hilang);
            }

            $data['jumlah_tersedia'] = $tersedia;
            $data['jumlah_rusak'] = $rusak;
            $data['jumlah_maintenance'] = $maintenance;
            $data['jumlah_hilang'] = $hilang;
        } else {
            // Serialized: breakdown akan dihitung dari sarana_units
            $data['jumlah_tersedia'] = 0;
            $data['jumlah_rusak'] = 0;
            $data['jumlah_maintenance'] = 0;
            $data['jumlah_hilang'] = 0;
        }

        $data['type'] = $type;

        return $data;
    }
}
