<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\Route;
use Modules\PrasaranaManagement\Http\Controllers\PrasaranaController;
use Modules\PrasaranaManagement\Http\Controllers\KategoriPrasaranaController;
use Modules\PrasaranaManagement\Http\Controllers\PrasaranaApproverController;

Route::middleware(['auth', 'profile.completed'])->group(function () {
    Route::resource('kategori-prasarana', KategoriPrasaranaController::class)
        ->parameters(['kategori-prasarana' => 'kategori_prasarana']);

    Route::resource('prasarana', PrasaranaController::class);

    // Nested routes: Prasarana Approvers
    Route::prefix('prasarana/{prasarana}')->name('prasarana.')->group(function () {
        Route::post('approvers', [PrasaranaApproverController::class, 'store'])->name('approvers.store');
        Route::put('approvers/{approver}', [PrasaranaApproverController::class, 'update'])->name('approvers.update');
        Route::delete('approvers/{approver}', [PrasaranaApproverController::class, 'destroy'])->name('approvers.destroy');

        // Prasarana images
        Route::delete('images/{image}', [PrasaranaController::class, 'destroyImage'])
            ->name('image.destroy');
    });
});
