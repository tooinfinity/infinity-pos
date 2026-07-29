<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission as PermissionModel;

function bootstrapAdministratorRole(): User
{
    $permissions = collect(Permission::cases())
        ->map(fn (Permission $permission): PermissionModel => PermissionModel::findOrCreate($permission->value, 'web'));

    $role = Role::findOrCreate(RoleName::Administrator->value, 'web');
    $role->syncPermissions($permissions);

    $admin = User::factory()->create();
    $admin->assignRole($role);

    return $admin;
}

it('blocks renaming and permission changes on the administrator role', function (): void {
    $admin = bootstrapAdministratorRole();
    $roleId = Role::query()->where('name', RoleName::Administrator->value)->value('id');

    $this->actingAs($admin)
        ->put(route('roles.update', ['role' => $roleId]), ['name' => 'Renamed'])
        ->assertForbidden();

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->put(route('roles.permissions.sync', ['role' => $roleId]), [
            'permissions' => [Permission::UsersView->value],
        ])
        ->assertForbidden();
});

it('creates custom roles and assigns permissions to them', function (): void {
    $admin = bootstrapAdministratorRole();

    $this->actingAs($admin)
        ->post(route('roles.store'), ['name' => 'Shift Lead'])
        ->assertRedirectToRoute('roles.index');

    $role = Role::query()->where('name', 'Shift Lead')->first();

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->put(route('roles.permissions.sync', ['role' => $role->id]), [
            'permissions' => [Permission::UsersView->value, Permission::RolesView->value],
        ])
        ->assertRedirectToRoute('roles.index');

    expect($role->fresh()->permissions->pluck('name')->sort()->values()->all())
        ->toBe([Permission::RolesView->value, Permission::UsersView->value]);
});

it('refuses to delete a role assigned to a user', function (): void {
    $admin = bootstrapAdministratorRole();
    $employee = User::factory()->create();
    $role = Role::create(['name' => 'Stock', 'guard_name' => 'web']);
    $employee->assignRole($role);

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('roles.destroy', ['role' => $role->id]))
        ->assertRedirectToRoute('roles.index')
        ->assertSessionHas('toast.type', 'error');

    expect(Role::query()->where('name', 'Stock')->exists())->toBeTrue();
});

it('lets delegated users update role metadata for non-administrator roles', function (): void {
    $admin = bootstrapAdministratorRole();
    $manager = User::factory()->create();
    $managerRole = Role::create(['name' => 'Role editor', 'guard_name' => 'web']);
    $managerRole->givePermissionTo(Permission::RolesUpdate->value);

    $manager->assignRole($managerRole);

    $role = Role::create(['name' => 'Manager', 'guard_name' => 'web']);

    $this->actingAs($manager)
        ->put(route('roles.update', ['role' => $role->id]), ['name' => 'Boss'])
        ->assertRedirectToRoute('roles.index');

    expect(Role::query()->where('name', 'Boss')->exists())->toBeTrue();
});

it('protects every default POS role name from update and deletion', function (RoleName $roleName): void {
    $admin = bootstrapAdministratorRole();
    $role = Role::findOrCreate($roleName->value, 'web');

    $this->actingAs($admin)
        ->put(route('roles.update', ['role' => $role->id]), ['name' => 'Renamed'])
        ->assertForbidden();

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('roles.destroy', ['role' => $role->id]))
        ->assertForbidden();
})->with(RoleName::cases());

it('renders roles with permissions user counts and capabilities', function (): void {
    $admin = bootstrapAdministratorRole();
    $role = Role::create(['name' => 'Shift Lead', 'guard_name' => 'web']);
    $role->givePermissionTo(Permission::UsersView->value);
    User::factory()->create()->assignRole($role);

    $this->actingAs($admin)
        ->get(route('roles.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('roles/index')
            ->where('can.create', true)
            ->where('can.update', true)
            ->where('can.delete', true)
            ->where('can.assign_permissions', true)
            ->where('roles', function ($roles): bool {
                $shiftLead = $roles->firstWhere('name', 'Shift Lead');

                return $shiftLead['is_protected'] === false
                    && $shiftLead['permissions'] === [Permission::UsersView->value]
                    && $shiftLead['users_count'] === 1;
            }));
});

it('renders the role creation page with the permission catalog', function (): void {
    $admin = bootstrapAdministratorRole();

    $this->actingAs($admin)
        ->get(route('roles.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('roles/create')
            ->has('permissions', count(Permission::cases()))
            ->where('permissions.0.value', Permission::UsersView->value)
            ->where('permissions.0.label', Permission::UsersView->label())
            ->where('permissions.0.group', Permission::UsersView->group()));
});

it('requires roles create permission to open the role creation page', function (): void {
    bootstrapAdministratorRole();
    $viewer = User::factory()->create();
    $viewerRole = Role::create(['name' => 'Role viewer', 'guard_name' => 'web']);
    $viewerRole->givePermissionTo(Permission::RolesView->value);

    $viewer->assignRole($viewerRole);

    $this->actingAs($viewer)
        ->get(route('roles.create'))
        ->assertForbidden();
});

it('allows a delegated creator to open role creation without roles view permission', function (): void {
    bootstrapAdministratorRole();
    $creator = User::factory()->create();
    $creatorRole = Role::create(['name' => 'Role creator', 'guard_name' => 'web']);
    $creatorRole->givePermissionTo(Permission::RolesCreate->value);

    $creator->assignRole($creatorRole);

    $this->actingAs($creator)
        ->get(route('roles.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('roles/create'));
});

it('renders custom and protected role management capabilities', function (): void {
    $admin = bootstrapAdministratorRole();
    $custom = Role::create(['name' => 'Shift Lead', 'guard_name' => 'web']);
    $custom->givePermissionTo(Permission::UsersView->value);

    $cashier = Role::findOrCreate(RoleName::Cashier->value, 'web');
    $administrator = Role::findByName(RoleName::Administrator->value, 'web');

    $this->actingAs($admin)
        ->get(route('roles.edit', $custom))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('role.is_protected', false)
            ->where('role.permissions_locked', false)
            ->where('can.update', true)
            ->where('can.delete', true)
            ->where('can.assign_permissions', true));

    $this->actingAs($admin)
        ->get(route('roles.edit', $cashier))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('role.is_protected', true)
            ->where('role.permissions_locked', false)
            ->where('can.update', false)
            ->where('can.delete', false)
            ->where('can.assign_permissions', true));

    $this->actingAs($admin)
        ->get(route('roles.edit', $administrator))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('role.is_protected', true)
            ->where('role.permissions_locked', true)
            ->where('can.update', false)
            ->where('can.delete', false)
            ->where('can.assign_permissions', false));
});

it('requires update or assign permissions capability to edit a role', function (): void {
    bootstrapAdministratorRole();
    $viewer = User::factory()->create();
    $viewerRole = Role::create(['name' => 'Role viewer', 'guard_name' => 'web']);
    $viewerRole->givePermissionTo(Permission::RolesView->value);

    $viewer->assignRole($viewerRole);
    $role = Role::create(['name' => 'Shift Lead', 'guard_name' => 'web']);

    $this->actingAs($viewer)
        ->get(route('roles.edit', $role))
        ->assertForbidden();
});

it('allows a delegated editor to edit a role without roles view permission', function (): void {
    bootstrapAdministratorRole();
    $editor = User::factory()->create();
    $editorRole = Role::create(['name' => 'Role editor', 'guard_name' => 'web']);
    $editorRole->givePermissionTo(Permission::RolesUpdate->value);

    $editor->assignRole($editorRole);
    $role = Role::create(['name' => 'Shift Lead', 'guard_name' => 'web']);

    $this->actingAs($editor)
        ->get(route('roles.edit', $role))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('roles/edit')
            ->where('can.update', true));
});

it('deletes an unassigned custom role', function (): void {
    $admin = bootstrapAdministratorRole();
    $role = Role::create(['name' => 'Temporary', 'guard_name' => 'web']);

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('roles.destroy', $role))
        ->assertRedirectToRoute('roles.index')
        ->assertSessionHas('toast.message', 'Role removed.');

    expect(Role::query()->whereKey($role->id)->exists())->toBeFalse();
});
