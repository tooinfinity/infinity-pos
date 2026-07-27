<?php

declare(strict_types=1);

use App\Data\ManagedUserData;
use App\Enums\Permission as AppPermission;
use App\Models\User;
use App\Queries\GetManagedUserEditPageData;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('builds edit page data for an active managed user', function (): void {
    $actor = User::factory()->create();
    $actor->givePermissionTo(collect([
        AppPermission::UsersUpdate,
        AppPermission::UsersAssignRoles,
        AppPermission::UsersResetPassword,
        AppPermission::UsersDelete,
    ])->map(fn (AppPermission $permission): Permission => Permission::findOrCreate($permission->value, 'web')));

    $role = Role::findOrCreate('Cashier', 'web');
    $user = User::factory()->create(['is_active' => false]);
    $user->assignRole($role);

    $data = resolve(GetManagedUserEditPageData::class)->execute($actor, $user);

    expect($data['user'])->toBeInstanceOf(ManagedUserData::class)
        ->and($data['user']->toArray())->toMatchArray([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => false,
            'deleted_at' => null,
            'roles' => ['Cashier'],
        ])
        ->and($data['isDeleted'])->toBeFalse()
        ->and($data['can'])->toBe([
            'update' => true,
            'assign_roles' => true,
            'reset_password' => true,
            'archive' => true,
            'restore' => false,
        ]);
});

it('only allows restoring an archived managed user', function (): void {
    $actor = User::factory()->create();
    $actor->givePermissionTo(Permission::findOrCreate(AppPermission::UsersDelete->value, 'web'));

    $user = User::factory()->create();
    $user->delete();

    $data = resolve(GetManagedUserEditPageData::class)->execute($actor, $user);

    expect($data['isDeleted'])->toBeTrue()
        ->and($data['user']->deleted_at)->not->toBeNull()
        ->and($data['can'])->toBe([
            'update' => false,
            'assign_roles' => false,
            'reset_password' => false,
            'archive' => false,
            'restore' => true,
        ]);
});

it('does not allow an actor to archive their own account', function (): void {
    $actor = User::factory()->create();
    $actor->givePermissionTo(Permission::findOrCreate(AppPermission::UsersDelete->value, 'web'));

    $data = resolve(GetManagedUserEditPageData::class)->execute($actor, $actor);

    expect($data['can']['archive'])->toBeFalse();
});
