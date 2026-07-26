<?php

declare(strict_types=1);

use App\Actions\SyncUserRoles;
use App\Enums\Permission;
use App\Enums\RoleName;
use App\Models\User;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $administrator = Role::findOrCreate(RoleName::Administrator->value, 'web');
    Role::findOrCreate('editor', 'web');
    Role::findOrCreate('viewer', 'web');

    foreach (Permission::cases() as $permission) {
        PermissionModel::findOrCreate($permission->value, 'web');
    }

    $administrator->syncPermissions(
        collect(Permission::cases())->map(fn (Permission $permission): string => $permission->value),
    );
});

it('assigns the given roles to the managed user', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole(RoleName::Administrator->value);

    $managedUser = User::factory()->create();

    $action = resolve(SyncUserRoles::class);

    $action->handle($actor, $managedUser, ['editor']);

    expect($managedUser->refresh()->roles->pluck('name')->all())->toBe(['editor']);
});

it('forbids demoting the only active administrator by another administrator', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole('editor');

    $onlyAdmin = User::factory()->create(['is_active' => true]);
    $onlyAdmin->assignRole(RoleName::Administrator->value);

    $action = resolve(SyncUserRoles::class);

    $action->handle($actor, $onlyAdmin, ['editor']);
})->throws(HttpException::class, 'Cannot remove the only active administrator from their administrator role.');

it('forbids the last active administrator from removing themselves from the role', function (): void {
    $onlyAdmin = User::factory()->create(['is_active' => true]);
    $onlyAdmin->assignRole(RoleName::Administrator->value);

    $action = resolve(SyncUserRoles::class);

    $action->handle($onlyAdmin, $onlyAdmin, ['editor']);
})->throws(HttpException::class, 'Cannot remove the only active administrator from their administrator role.');

it('forbids non-administrator actors from removing an administrator role', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole('editor');

    $managedUser = User::factory()->create();
    $managedUser->assignRole(RoleName::Administrator->value);

    $action = resolve(SyncUserRoles::class);

    $action->handle($actor, $managedUser, ['editor']);
})->throws(HttpException::class);

it('forbids non-administrator actors from granting the administrator role', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole('editor');

    $managedUser = User::factory()->create();

    $action = resolve(SyncUserRoles::class);

    $action->handle($actor, $managedUser, [RoleName::Administrator->value]);
})->throws(HttpException::class);

it('allows demoting an administrator when another active administrator exists', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole(RoleName::Administrator->value);

    $otherAdmin = User::factory()->create(['is_active' => true]);
    $otherAdmin->assignRole(RoleName::Administrator->value);

    $managedUser = User::factory()->create(['is_active' => true]);
    $managedUser->assignRole(RoleName::Administrator->value);

    $action = resolve(SyncUserRoles::class);

    $action->handle($actor, $managedUser, ['editor']);

    expect($managedUser->refresh()->hasRole(RoleName::Administrator->value))->toBeFalse();
});

it('restores the full administrator permission set when an administrator keeps the role', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole(RoleName::Administrator->value);

    $managedUser = User::factory()->create();
    $managedUser->assignRole(RoleName::Administrator->value);

    $administratorRole = Role::findByName(RoleName::Administrator->value);
    $administratorRole->syncPermissions([Permission::UsersView->value]);

    expect($administratorRole->permissions->pluck('name')->all())->toBe([Permission::UsersView->value]);

    $action = resolve(SyncUserRoles::class);

    $action->handle($actor, $managedUser, [RoleName::Administrator->value]);

    $administratorRole->refresh();

    expect($administratorRole->permissions->pluck('name')->sort()->values()->all())
        ->toBe(collect(Permission::cases())->map(fn (Permission $permission): string => $permission->value)->sort()->values()->all());
});

it('does nothing when the managed user no longer exists', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole(RoleName::Administrator->value);

    $ghost = User::factory()->create();
    $ghostId = $ghost->getKey();
    $ghost->delete();
    $ghost->forceDelete();

    $action = resolve(SyncUserRoles::class);

    $action->handle($actor, User::query()->find($ghostId) ?? $ghost, ['editor']);

    expect(User::query()->withTrashed()->whereKey($ghostId)->exists())->toBeFalse();
});

it('allows demoting an inactive administrator', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole(RoleName::Administrator->value);

    $managedUser = User::factory()->create(['is_active' => false]);
    $managedUser->assignRole(RoleName::Administrator->value);

    resolve(SyncUserRoles::class)->handle($actor, $managedUser, ['editor']);

    expect($managedUser->refresh()->roles->pluck('name')->all())->toBe(['editor']);
});

it('synchronizes ordinary roles before the administrator catalog exists', function (): void {
    Role::findByName(RoleName::Administrator->value, 'web')->delete();
    $actor = User::factory()->create();
    $managedUser = User::factory()->create();
    $managedUser->assignRole('editor');

    resolve(SyncUserRoles::class)->handle($actor, $managedUser, ['viewer']);

    expect($managedUser->refresh()->roles->pluck('name')->all())->toBe(['viewer']);
});
