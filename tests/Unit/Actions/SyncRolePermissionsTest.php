<?php

declare(strict_types=1);

use App\Actions\SyncRolePermissions;
use App\Enums\Permission;
use App\Models\Role;
use Spatie\Permission\Models\Permission as PermissionModel;

beforeEach(function (): void {
    foreach (Permission::cases() as $permission) {
        PermissionModel::findOrCreate($permission->value, 'web');
    }
});

it('syncs the given permissions onto the role', function (): void {
    $role = Role::create(['name' => 'editor']);

    $action = resolve(SyncRolePermissions::class);

    $action->handle($role, [Permission::UsersView->value, Permission::UsersCreate->value]);

    expect($role->refresh()->permissions->pluck('name')->all())
        ->toBe([Permission::UsersView->value, Permission::UsersCreate->value]);
});

it('removes permissions that are no longer present', function (): void {
    $role = Role::create(['name' => 'editor']);
    $role->syncPermissions([Permission::UsersView->value, Permission::UsersCreate->value]);

    $action = resolve(SyncRolePermissions::class);

    $action->handle($role, [Permission::UsersView->value]);

    expect($role->refresh()->permissions->pluck('name')->all())
        ->toBe([Permission::UsersView->value]);
});

it('clears all permissions when given an empty list', function (): void {
    $role = Role::create(['name' => 'editor']);
    $role->syncPermissions([Permission::UsersView->value]);

    $action = resolve(SyncRolePermissions::class);

    $action->handle($role, []);

    expect($role->refresh()->permissions)->toBeEmpty();
});

it('throws on an unknown permission value', function (): void {
    $role = Role::create(['name' => 'editor']);

    $action = resolve(SyncRolePermissions::class);

    $action->handle($role, ['not.a.real.permission']);
})->throws(ValueError::class);
