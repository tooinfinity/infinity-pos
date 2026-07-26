<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role;

it('bootstraps the authorization catalog and administrator without a seeder', function (): void {
    $this->artisan('app:bootstrap-admin')
        ->expectsQuestion('Administrator name', 'Root Admin')
        ->expectsQuestion('Administrator email', 'ADMIN@example.com')
        ->expectsQuestion('Administrator password', 'password')
        ->expectsQuestion('Confirm administrator password', 'password')
        ->expectsOutput('Administrator access is ready.')
        ->assertSuccessful();

    $administrator = User::query()->where('email', 'admin@example.com')->firstOrFail();

    expect($administrator->hasRole(RoleName::Administrator->value))->toBeTrue()
        ->and(Hash::check('password', $administrator->password))->toBeTrue()
        ->and(Role::query()->whereIn('name', RoleName::values())->count())->toBe(count(RoleName::cases()))
        ->and(PermissionModel::query()->count())->toBe(count(Permission::cases()))
        ->and($administrator->getAllPermissions()->count())->toBe(count(Permission::cases()));
});

it('restores and updates an archived administrator idempotently', function (): void {
    $administrator = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'admin@example.com',
        'is_active' => false,
    ]);
    $administrator->delete();

    $this->artisan('app:bootstrap-admin')
        ->expectsQuestion('Administrator name', 'New Name')
        ->expectsQuestion('Administrator email', 'admin@example.com')
        ->expectsQuestion('Administrator password', 'new-password')
        ->expectsQuestion('Confirm administrator password', 'new-password')
        ->assertSuccessful();

    $restored = $administrator->fresh();

    expect($restored)->not->toBeNull()
        ->and($restored->name)->toBe('New Name')
        ->and($restored->is_active)->toBeTrue()
        ->and($restored->hasRole(RoleName::Administrator->value))->toBeTrue()
        ->and(User::query()->withTrashed()->where('email', 'admin@example.com')->count())->toBe(1);
});

it('fails validation for weak passwords', function (): void {
    $this->artisan('app:bootstrap-admin')
        ->expectsQuestion('Administrator name', 'Root Admin')
        ->expectsQuestion('Administrator email', 'admin@example.com')
        ->expectsQuestion('Administrator password', 'short')
        ->expectsQuestion('Confirm administrator password', 'mismatch')
        ->assertFailed();

    expect(User::query()->where('email', 'admin@example.com')->exists())->toBeFalse();
});

it('can be run repeatedly without duplicating the administrator', function (): void {
    foreach (range(1, 2) as $attempt) {
        $this->artisan('app:bootstrap-admin')
            ->expectsQuestion('Administrator name', 'Root Admin')
            ->expectsQuestion('Administrator email', 'admin@example.com')
            ->expectsQuestion('Administrator password', 'password')
            ->expectsQuestion('Confirm administrator password', 'password')
            ->assertSuccessful();
    }

    expect(User::query()->where('email', 'admin@example.com')->count())->toBe(1);
});
