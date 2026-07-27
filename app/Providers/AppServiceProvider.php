<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Route::bind('role', fn (string $value): Role => Role::query()
            ->whereKey($value)
            ->where('guard_name', 'web')
            ->firstOrFail());
    }
}
