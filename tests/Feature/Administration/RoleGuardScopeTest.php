<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use App\Queries\ListRoles;
use App\Queries\ListSelectableRoles;
use Spatie\Permission\Models\Permission as PermissionModel;

it('only lists web roles', function (): void {
    $actor = User::factory()->create();
    Role::create(['name' => 'Web role', 'guard_name' => 'web']);
    Role::create(['name' => 'API role', 'guard_name' => 'api']);

    expect(resolve(ListRoles::class)->execute()->pluck('name')->all())->toBe(['Web role'])
        ->and(resolve(ListSelectableRoles::class)->execute($actor))->toBe([
            ['value' => 'Web role', 'label' => 'Web role'],
        ]);
});

it('does not bind non-web roles to administration routes', function (): void {
    $actor = User::factory()->create();
    $managerRole = Role::create(['name' => 'Role manager', 'guard_name' => 'web']);
    $managerRole->syncPermissions(collect([
        Permission::RolesUpdate,
        Permission::RolesDelete,
        Permission::RolesAssignPermissions,
    ])->map(fn (Permission $permission): PermissionModel => PermissionModel::findOrCreate($permission->value, 'web')));
    $actor->assignRole($managerRole);

    $apiRole = Role::create(['name' => 'API role', 'guard_name' => 'api']);

    $this->actingAs($actor)->get(route('roles.edit', $apiRole))->assertNotFound();
    $this->actingAs($actor)->put(route('roles.update', $apiRole), ['name' => 'Changed'])->assertNotFound();
    $this->actingAs($actor)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('roles.destroy', $apiRole))
        ->assertNotFound();
    $this->actingAs($actor)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->put(route('roles.permissions.sync', $apiRole), ['permissions' => []])
        ->assertNotFound();

    expect($apiRole->refresh()->name)->toBe('API role');
});
