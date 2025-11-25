<?php

namespace App\Http\Controllers\Examples;

use App\Http\Controllers\Controller;
use App\Http\Requests\FileUploadRequest;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Example controller untuk implementasi file upload
 * 
 * Contoh penggunaan FileUploadService di berbagai scenario
 */
class FileUploadExampleController extends Controller
{
    public function __construct(
        private readonly FileUploadService $fileUploadService
    ) {
    }

    /**
     * Example 1: Upload gambar sarpras (public file)
     */
    public function uploadSarprasImage(FileUploadRequest $request): JsonResponse
    {
        try {
            // Simple upload tanpa tracking ke database
            $result = $this->fileUploadService->upload(
                file: $request->file('file'),
                type: 'image',
                category: 'sarpras',
                disk: 'public',
                options: [
                    'optimize' => true,
                    'thumbnail' => true,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Gambar berhasil diupload',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Example 2: Upload dokumen dengan tracking ke database
     */
    public function uploadDocument(FileUploadRequest $request): JsonResponse
    {
        try {
            // Upload dengan tracking metadata ke database
            $result = $this->fileUploadService->uploadWithTracking(
                file: $request->file('file'),
                type: 'document',
                category: 'documents',
                disk: 'local', // private storage
                options: [
                    'uploadable_type' => $request->input('model_type'), // e.g., 'App\Models\Peminjaman'
                    'uploadable_id' => $request->input('model_id'), // e.g., peminjaman_id
                    'is_public' => false,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Dokumen berhasil diupload',
                'data' => [
                    'file_id' => $result['model']->id,
                    'path' => $result['path'],
                    'download_url' => $result['model']->getTemporaryUrl(60),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Example 3: Upload avatar user
     */
    public function uploadAvatar(FileUploadRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();

            // Upload avatar dengan delete avatar lama
            $result = $this->fileUploadService->upload(
                file: $request->file('file'),
                type: 'avatar',
                category: 'avatars',
                disk: 'public',
                options: [
                    'optimize' => true,
                    'thumbnail' => true,
                    'old_file' => $user->avatar, // Hapus avatar lama
                ]
            );

            // Update user avatar path
            $user->update([
                'avatar' => $result['path'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Avatar berhasil diupload',
                'data' => [
                    'url' => $result['url'],
                    'thumbnail' => $result['thumbnail'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Example 4: Upload multiple files sekaligus
     */
    public function uploadMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'files' => 'required|array|max:10',
            'files.*' => 'required|file|max:5120|mimes:jpg,jpeg,png,pdf',
        ]);

        try {
            $results = $this->fileUploadService->uploadMultiple(
                files: $request->file('files'),
                type: 'document',
                category: 'documents',
                disk: 'local',
                options: [
                    'uploadable_type' => $request->input('model_type'),
                    'uploadable_id' => $request->input('model_id'),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => count($results).' file berhasil diupload',
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Example 5: Upload file temporary (untuk preview sebelum submit form)
     */
    public function uploadTemporary(FileUploadRequest $request): JsonResponse
    {
        try {
            $result = $this->fileUploadService->upload(
                file: $request->file('file'),
                type: $request->input('type', 'image'),
                category: 'temp',
                disk: 'local',
                options: []
            );

            return response()->json([
                'success' => true,
                'message' => 'File temporary berhasil diupload',
                'data' => [
                    'path' => $result['path'],
                    'filename' => $result['filename'],
                    // Kirim encrypted path untuk digunakan saat submit form
                    'temp_token' => encrypt($result['path']),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Example 6: Replace temporary file dengan permanent file
     * 
     * Gunakan saat submit form setelah upload temporary
     */
    public function moveTempToPermanent(Request $request): JsonResponse
    {
        $request->validate([
            'temp_token' => 'required|string',
            'category' => 'required|string',
        ]);

        try {
            $tempPath = decrypt($request->input('temp_token'));
            $category = $request->input('category');

            // Move file dari temp ke permanent location
            $disk = config('upload.disk', 'local');
            $filename = basename($tempPath);
            $newPath = config("upload.paths.{$category}").'/'.$filename;

            \Storage::disk($disk)->move($tempPath, $newPath);

            return response()->json([
                'success' => true,
                'message' => 'File berhasil dipindahkan',
                'data' => [
                    'path' => $newPath,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
