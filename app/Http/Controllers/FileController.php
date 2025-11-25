<?php

namespace App\Http\Controllers;

use App\Models\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    /**
     * Download private file dengan authorization check
     */
    public function download(Request $request, UploadedFile $file): StreamedResponse
    {
        // Verify signature untuk security
        if (! $file->verifySignature(
            $request->get('signature', ''),
            $request->get('expires', 0)
        )) {
            abort(403, 'Link download tidak valid atau sudah kadaluarsa.');
        }

        // Authorization: user harus owner atau punya permission
        if (! $this->canAccessFile($file)) {
            abort(403, 'Anda tidak memiliki akses ke file ini.');
        }

        // Check if file exists
        if (! $file->exists()) {
            abort(404, 'File tidak ditemukan.');
        }

        // Increment download count
        $file->incrementDownloadCount();

        // Return file as download
        return Storage::disk($file->disk)->download(
            $file->file_path,
            $file->original_name
        );
    }

    /**
     * Stream private file (untuk view di browser seperti PDF/image)
     */
    public function stream(Request $request, UploadedFile $file): StreamedResponse
    {
        // Verify signature
        if (! $file->verifySignature(
            $request->get('signature', ''),
            $request->get('expires', 0)
        )) {
            abort(403, 'Link tidak valid atau sudah kadaluarsa.');
        }

        // Authorization
        if (! $this->canAccessFile($file)) {
            abort(403, 'Anda tidak memiliki akses ke file ini.');
        }

        // Check if file exists
        if (! $file->exists()) {
            abort(404, 'File tidak ditemukan.');
        }

        // Increment access count
        $file->incrementDownloadCount();

        // Stream file
        return Storage::disk($file->disk)->response(
            $file->file_path,
            $file->original_name
        );
    }

    /**
     * Delete uploaded file
     */
    public function destroy(UploadedFile $file)
    {
        // Authorization: only owner or admin can delete
        if (! $this->canDeleteFile($file)) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus file ini.');
        }

        // Delete file from storage
        $file->deleteFile();

        // Soft delete record
        $file->delete();

        return response()->json([
            'success' => true,
            'message' => 'File berhasil dihapus.',
        ]);
    }

    /**
     * Check if user can access file
     */
    protected function canAccessFile(UploadedFile $file): bool
    {
        $user = auth()->user();

        // Public files dapat diakses semua orang
        if ($file->is_public) {
            return true;
        }

        // Owner dapat akses file sendiri
        if ($file->user_id === $user->id) {
            return true;
        }

        // Admin dapat akses semua file
        if ($user->hasRole('Admin Sarpras')) {
            return true;
        }

        // Check polymorphic ownership
        // Contoh: jika file attached ke sarpras, cek apakah user berhak akses sarpras tsb
        if ($file->uploadable) {
            // Custom logic based on uploadable model
            // Implement based on your business logic
        }

        return false;
    }

    /**
     * Check if user can delete file
     */
    protected function canDeleteFile(UploadedFile $file): bool
    {
        $user = auth()->user();

        // Owner dapat delete file sendiri
        if ($file->user_id === $user->id) {
            return true;
        }

        // Admin dapat delete semua file
        if ($user->hasRole('Admin Sarpras')) {
            return true;
        }

        return false;
    }
}
