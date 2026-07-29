<?php

declare(strict_types=1);

use App\Actions\UpdateRole;
use App\Models\Role;

it('renames an existing role', function (): void {
    $role = Role::create(['name' => 'Shift Lead', 'guard_name' => 'web']);

    $updated = resolve(UpdateRole::class)->handle($role, 'Team Lead');

    expect($updated->name)->toBe('Team Lead')
        ->and($updated->guard_name)->toBe('web')
        ->and(Role::query()->whereKey($role->id)->value('name'))->toBe('Team Lead');
});

it('scopes the update to web roles', function (): void {
    $webRole = Role::create(['name' => 'Cashier', 'guard_name' => 'web']);
    $apiRole = Role::create(['name' => 'Cashier', 'guard_name' => 'api']);

    resolve(UpdateRole::class)->handle($webRole, 'Cashier Updated');

    expect($webRole->refresh()->name)->toBe('Cashier Updated')
        ->and($apiRole->refresh()->name)->toBe('Cashier');
});
