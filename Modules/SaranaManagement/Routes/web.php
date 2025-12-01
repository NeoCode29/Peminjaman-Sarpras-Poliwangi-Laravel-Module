<?php

use Illuminate\Support\Facades\Route;
use Modules\SaranaManagement\Http\Controllers\SaranaController;
use Modules\SaranaManagement\Http\Controllers\KategoriSaranaController;
use Modules\SaranaManagement\Http\Controllers\SaranaApproverController;

/*
|--------------------------------------------------------------------------
| Web Routes - Sarana Management Module
|--------------------------------------------------------------------------
|
| Routes untuk manajemen sarana dan kategori sarana
|
*/

Route::middleware(['auth', 'profile.completed'])->group(function () {
    
    // Kategori Sarana Routes
    Route::resource('kategori-sarana', KategoriSaranaController::class)
        ->parameters(['kategori-sarana' => 'kategori_sarana']);
    
    // Sarana Routes
    Route::resource('sarana', SaranaController::class);

    // Sarana Unit & Approver Routes
    Route::prefix('sarana/{sarana}')->name('sarana.')->group(function () {
        // Units
        Route::get('units', [SaranaController::class, 'units'])->name('units.index');
        Route::post('units', [SaranaController::class, 'storeUnit'])->name('units.store');
        Route::put('units/{unit}', [SaranaController::class, 'updateUnit'])->name('units.update');
        Route::delete('units/{unit}', [SaranaController::class, 'destroyUnit'])->name('units.destroy');

        // Approvers
        Route::post('approvers', [SaranaApproverController::class, 'store'])->name('approvers.store');
        Route::put('approvers/{approver}', [SaranaApproverController::class, 'update'])->name('approvers.update');
        Route::delete('approvers/{approver}', [SaranaApproverController::class, 'destroy'])->name('approvers.destroy');
    });
    
});
