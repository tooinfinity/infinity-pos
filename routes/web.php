<?php

declare(strict_types=1);

use App\Http\Controllers\RoleController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\UserDestroyController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\UserPasswordController;
use App\Http\Controllers\UserPasswordResetController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\UserRestoreController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\UserStatusController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('welcome'))->name('home');

Route::middleware(['auth', EnsureUserIsActive::class])->group(function (): void {
    Route::get('dashboard', fn () => Inertia::render('dashboard'))->name('dashboard');

    Route::get('settings/profile', [UserProfileController::class, 'edit'])->name('user-profile.edit');
    Route::put('settings/profile', [UserProfileController::class, 'update'])->name('user-profile.update');
    Route::get('settings/password', [UserPasswordController::class, 'edit'])->name('user-password.edit');
    Route::put('settings/password', [UserPasswordController::class, 'update'])->name('user-password.update');
    Route::get('settings/appearance', fn () => Inertia::render('appearance/update'))->name('appearance.edit');

    Route::prefix('administration/users')->name('users.')->group(function (): void {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::get('/create', [UserManagementController::class, 'create'])->name('create');
        Route::post('/', [UserManagementController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserManagementController::class, 'edit'])
            ->withTrashed()
            ->middleware('password.confirm')
            ->name('edit');
        Route::put('/{user}', [UserManagementController::class, 'update'])->name('update');
        Route::put('/{user}/status', UserStatusController::class)->name('status.update');
        Route::put('/{user}/roles', UserRoleController::class)->middleware('password.confirm')->name('roles.sync');
        Route::put('/{user}/password', UserPasswordResetController::class)->middleware('password.confirm')->name('password.reset');
        Route::delete('/{user}', UserDestroyController::class)->middleware('password.confirm')->name('destroy');
        Route::put('/{user}/restore', UserRestoreController::class)
            ->withTrashed()
            ->middleware('password.confirm')
            ->name('restore');
    });

    Route::prefix('administration/roles')->name('roles.')->group(function (): void {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/create', [RoleController::class, 'create'])->name('create');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->middleware('password.confirm')->name('destroy');
        Route::put('/{role}/permissions', RolePermissionController::class)->middleware('password.confirm')->name('permissions.sync');
    });
});
