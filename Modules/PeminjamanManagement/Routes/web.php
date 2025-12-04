<?php

use Illuminate\Support\Facades\Route;
use Modules\PeminjamanManagement\Http\Controllers\PeminjamanController;
use Modules\PeminjamanManagement\Http\Controllers\PeminjamanApprovalController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth', 'profile.completed'])->group(function () {
    // Peminjaman Resource Routes
    Route::resource('peminjaman', PeminjamanController::class);

    // Cancel peminjaman
    Route::post('peminjaman/{peminjaman}/cancel', [PeminjamanController::class, 'cancel'])
        ->name('peminjaman.cancel');

    // Approval Routes
    Route::prefix('peminjaman/{peminjaman}')->name('peminjaman.')->group(function () {
        // Process approval (approve/reject)
        Route::post('approval', [PeminjamanApprovalController::class, 'processApproval'])
            ->name('approval.process');

        // Override approval
        Route::post('override', [PeminjamanApprovalController::class, 'override'])
            ->name('approval.override');

        // Validate pickup
        Route::post('validate-pickup', [PeminjamanApprovalController::class, 'validatePickup'])
            ->name('validate-pickup');

        // Validate return
        Route::post('validate-return', [PeminjamanApprovalController::class, 'validateReturn'])
            ->name('validate-return');

        // Assign units
        Route::post('assign-units', [PeminjamanApprovalController::class, 'assignUnits'])
            ->name('assign-units');
    });

    // Pending approvals for current user
    Route::get('peminjaman-approvals/pending', [PeminjamanApprovalController::class, 'pendingApprovals'])
        ->name('peminjaman.approvals.pending');
});
