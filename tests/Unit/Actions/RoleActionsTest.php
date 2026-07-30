<?php

declare(strict_types=1);

use App\Actions\CreateRole;
use App\Actions\DeleteRole;
use App\Models\Role;
use App\Models\User;

it('creates a new web role without changing an existing role', function (): void {
    $existing = Role::create(['name' => 'Existing', 'guard_name' => 'web']);

    $created = resolve(CreateRole::class)->handle('Shift Lead');

    expect($created->name)->toBe('Shift Lead')
        ->and($created->guard_name)->toBe('web')
        ->and($existing->refresh()->name)->toBe('Existing');
});

it('deletes an unassigned custom role', function (): void {
    $role = Role::create(['name' => 'Temporary', 'guard_name' => 'web']);

    expect(resolve(DeleteRole::class)->handle($role))->toBeTrue()
        ->and(Role::query()->whereKey($role->id)->exists())->toBeFalse();
});

it('does not delete an assigned role', function (): void {
    $role = Role::create(['name' => 'Assigned', 'guard_name' => 'web']);
    User::factory()->create()->assignRole($role);

    expect(resolve(DeleteRole::class)->handle($role))->toBeFalse()
        ->and(Role::query()->whereKey($role->id)->exists())->toBeTrue();
});
