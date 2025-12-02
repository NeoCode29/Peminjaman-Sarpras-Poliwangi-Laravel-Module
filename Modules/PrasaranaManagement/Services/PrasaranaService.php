<?php

namespace Modules\PrasaranaManagement\Services;

use App\Models\Peminjaman;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\PrasaranaManagement\Entities\Prasarana;
use Modules\PrasaranaManagement\Entities\PrasaranaImage;
use Modules\PrasaranaManagement\Repositories\Interfaces\PrasaranaRepositoryInterface;

class PrasaranaService
{
    public function __construct(
        private readonly PrasaranaRepositoryInterface $prasaranaRepository,
        private readonly DatabaseManager $database,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->prasaranaRepository->getAll($filters, $perPage);
    }

    public function getById(int $id): ?Prasarana
    {
        return $this->prasaranaRepository->findById($id);
    }

    public function create(array $data, int $creatorId, array $images = []): Prasarana
    {
        return $this->database->transaction(function () use ($data, $creatorId, $images) {
            $data['created_by'] = $creatorId;

            $prasarana = $this->prasaranaRepository->create($data);

            $this->storeImages($prasarana, $images);

            return $prasarana->load(['kategori', 'images']);
        });
    }

    public function update(Prasarana $prasarana, array $data, array $newImages = [], array $removeImageIds = []): Prasarana
    {
        return $this->database->transaction(function () use ($prasarana, $data, $newImages, $removeImageIds) {
            $updated = $this->prasaranaRepository->update($prasarana, $data);

            if (!empty($removeImageIds)) {
                $images = $updated->images()
                    ->whereIn('id', $removeImageIds)
                    ->get();

                foreach ($images as $image) {
                    $this->deleteImageFile($image->image_url);
                    $image->delete();
                }
            }

            if (!empty($newImages)) {
                $this->storeImages($updated, $newImages);
            }

            return $updated->load(['kategori', 'images']);
        });
    }

    public function delete(Prasarana $prasarana): void
    {
        $this->database->transaction(function () use ($prasarana) {
            if ($this->hasActivePeminjaman($prasarana)) {
                throw new \RuntimeException('Tidak dapat menghapus prasarana yang memiliki peminjaman aktif.');
            }

            foreach ($prasarana->images as $image) {
                $this->deleteImageFile($image->image_url);
                $image->delete();
            }

            $this->prasaranaRepository->delete($prasarana);
        });
    }

    public function deleteImage(PrasaranaImage $image): void
    {
        $this->database->transaction(function () use ($image) {
            $this->deleteImageFile($image->image_url);
            $image->delete();
        });
    }

    private function storeImages(Prasarana $prasarana, array $images): void
    {
        if (empty($images)) {
            return;
        }

        $currentMax = (int) $prasarana->images()->max('sort_order');

        foreach ($images as $index => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('prasarana/images', 'public');

            $prasarana->images()->create([
                'image_url' => $path,
                'sort_order' => $currentMax + $index + 1,
            ]);
        }
    }

    private function deleteImageFile(?string $path): void
    {
        if (empty($path)) {
            return;
        }

        if (str_starts_with($path, 'http')) {
            $parsedPath = parse_url($path, PHP_URL_PATH);
            $path = $parsedPath ?: $path;
        }

        $prefix = '/storage/';
        if (str_starts_with($path, $prefix)) {
            $path = substr($path, strlen($prefix));
        }

        $path = ltrim($path, '/');

        if ($path !== '') {
            Storage::disk('public')->delete($path);
        }
    }

    private function hasActivePeminjaman(Prasarana $prasarana): bool
    {
        if (! class_exists(Peminjaman::class)) {
            return false;
        }

        return $prasarana->peminjaman()
            ->whereIn('status', ['pending', 'approved', 'picked_up'])
            ->exists();
    }
}
