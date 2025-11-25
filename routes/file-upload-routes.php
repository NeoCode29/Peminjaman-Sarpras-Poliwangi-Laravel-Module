<?php

use App\Http\Controllers\Examples\FileUploadExampleController;
use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| File Upload Routes
|--------------------------------------------------------------------------
|
| Routes untuk file upload dan download
| Include file ini di routes/web.php atau routes/api.php
|
*/

// File download & stream routes (untuk private files)
Route::middleware(['auth'])->group(function () {
    Route::get('/files/{file}/download', [FileController::class, 'download'])->name('file.download');
    Route::get('/files/{file}/stream', [FileController::class, 'stream'])->name('file.stream');
    Route::delete('/files/{file}', [FileController::class, 'destroy'])->name('file.destroy');
});

// Example upload routes sebelumnya dihapus sesuai kebutuhan aplikasi.
