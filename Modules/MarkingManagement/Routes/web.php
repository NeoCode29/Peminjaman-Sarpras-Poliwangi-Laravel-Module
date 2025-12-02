<?php

use Illuminate\Support\Facades\Route;
use Modules\MarkingManagement\Http\Controllers\MarkingController;

/*
|--------------------------------------------------------------------------
| Web Routes - Marking Management Module
|--------------------------------------------------------------------------
|
| Routes untuk manajemen marking (reservasi sementara)
|
*/

Route::middleware(['auth', 'profile.completed'])->group(function () {
    
    // Marking Resource Routes
    Route::resource('marking', MarkingController::class);
    
    // Additional Marking Routes
    Route::prefix('marking/{marking}')->name('marking.')->group(function () {
        Route::post('convert', [MarkingController::class, 'convert'])->name('convert');
        Route::post('extend', [MarkingController::class, 'extend'])->name('extend');
    });
    
});
