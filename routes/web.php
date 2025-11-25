<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PermissionManagementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleManagementController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::view('/playground-ui', 'playground.ui')->name('playground.ui');
Route::view('/components-demo', 'components-demo')->name('components.demo');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

    Route::get('/oauth/login', [OAuthController::class, 'redirect'])->name('oauth.login');
    Route::get('/oauth/callback', [OAuthController::class, 'callback'])->name('oauth.callback');
});

Route::middleware(['auth', 'user.not.blocked'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::post('/oauth/logout', [OAuthController::class, 'logout'])->name('oauth.logout');

    // Profile Setup Routes (tanpa middleware profile.completed)
    Route::get('/setup', [ProfileController::class, 'setup'])->name('profile.setup');
    Route::post('/setup', [ProfileController::class, 'completeSetup'])->name('profile.complete-setup');
    Route::get('/profile/get-prodis', [ProfileController::class, 'getProdisByJurusan'])->name('profile.get-prodis');

    Route::middleware('profile.completed')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Profile Management Routes
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::get('/profile/password/edit', [ProfileController::class, 'changePassword'])->name('profile.password.edit');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

        // User Management
        Route::resource('user-management', UserManagementController::class)
            ->parameters(['user-management' => 'user'])
            ->except(['destroy']);
        Route::delete('user-management/{user}', [UserManagementController::class, 'destroy'])
            ->name('user-management.destroy');
        Route::post('user-management/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])
            ->name('user-management.toggle-status');
        Route::post('user-management/{user}/block', [UserManagementController::class, 'block'])
            ->name('user-management.block');
        Route::post('user-management/{user}/unblock', [UserManagementController::class, 'unblock'])
            ->name('user-management.unblock');
        Route::post('user-management/{user}/change-password', [UserManagementController::class, 'changePassword'])
            ->name('user-management.change-password');

        // Role Management
        Route::resource('role-management', RoleManagementController::class)
            ->parameters(['role-management' => 'role'])
            ->except(['destroy']);
        Route::delete('role-management/{role}', [RoleManagementController::class, 'destroy'])
            ->name('role-management.destroy');
        Route::post('role-management/{role}/toggle-status', [RoleManagementController::class, 'toggleStatus'])
            ->name('role-management.toggle-status');

        // Permission Management
        Route::resource('permission-management', PermissionManagementController::class)
            ->parameters(['permission-management' => 'permission'])
            ->except(['destroy']);
        Route::delete('permission-management/{permission}', [PermissionManagementController::class, 'destroy'])
            ->name('permission-management.destroy');
        Route::post('permission-management/{permission}/toggle-status', [PermissionManagementController::class, 'toggleStatus'])
            ->name('permission-management.toggle-status');

        // System Settings
        Route::get('/settings', [SystemSettingController::class, 'index'])
            ->name('settings.index');
        Route::post('/settings', [SystemSettingController::class, 'update'])
            ->name('settings.update');

        // Notifications
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])
                ->name('index');
            Route::get('/recent', [NotificationController::class, 'recent'])
                ->name('recent');
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])
                ->name('mark-as-read');
            Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])
                ->name('mark-all-read');
            Route::get('/count', [NotificationController::class, 'count'])
                ->name('count');
            Route::delete('/{id}', [NotificationController::class, 'destroy'])
                ->name('destroy');
        });
    });
});

// File download & stream routes (untuk private files)
Route::middleware(['auth', 'user.not.blocked'])->group(function () {
    Route::get('/files/{file}/download', [FileController::class, 'download'])->name('file.download');
    Route::get('/files/{file}/stream', [FileController::class, 'stream'])->name('file.stream');
    Route::delete('/files/{file}', [FileController::class, 'destroy'])->name('file.destroy');
});
