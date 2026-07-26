<?php

declare(strict_types=1);

use App\Actions\CreateUser;
use App\Actions\SyncUserRoles;
use App\Actions\UpdateUserStatus;
use App\Data\CreateUserData;
use App\Enums\Permission;
use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role;

function bootstrapAdministrator(): User
{
    $permissions = collect(Permission::cases())
        ->map(fn (Permission $permission): PermissionModel => PermissionModel::findOrCreate($permission->value, 'web'));

    $role = Role::findOrCreate(RoleName::Administrator->value, 'web');
    $role->syncPermissions($permissions);

    $user = resolve(CreateUser::class)->handle(CreateUserData::from([
        'name' => 'Root Admin',
        'email' => 'admin@example.com',
        'password' => 'password',
        'roles' => [],
    ]));
    $user->assignRole($role);

    return $user;
}

function assignPermissionsThroughRole(User $user, array $permissions): void
{
    $role = Role::findOrCreate('Delegated '.str_replace('@example.com', '', $user->email), 'web');
    $role->syncPermissions($permissions);

    $user->assignRole($role);
}

function confirmAdministrativePassword(): array
{
    return ['auth.password_confirmed_at' => time()];
}

it('allows only users with users.view to view the users page', function (): void {
    $admin = bootstrapAdministrator();
    $viewer = User::factory()->create([
        'email' => 'viewer@example.com',
        'password' => Hash::make('password'),
    ]);
    assignPermissionsThroughRole($viewer, [Permission::UsersView->value]);

    $this->actingAs($viewer)->get(route('users.index'))->assertOk();
    $this->actingAs($admin)->get(route('users.index'))->assertOk();

    $unauthorized = User::factory()->create();
    $this->actingAs($unauthorized)->get(route('users.index'))->assertForbidden();
});

it('refuses public registration routes', function (): void {
    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();
});

it('rejects login for inactive users', function (): void {
    User::factory()->create([
        'email' => 'inactive@example.com',
        'password' => Hash::make('password'),
        'is_active' => false,
    ]);

    $this->fromRoute('login')
        ->post(route('login.store'), [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ])
        ->assertRedirectToRoute('login')
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('only lets users with the right capability create other users', function (): void {
    bootstrapAdministrator();
    $actor = User::factory()->create();
    assignPermissionsThroughRole($actor, [Permission::UsersCreate->value]);

    $this->actingAs($actor)->post(route('users.store'), [
        'name' => 'New Hire',
        'email' => 'hire@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => [],
    ])->assertRedirectToRoute('users.index');

    expect(User::query()->where('email', 'hire@example.com')->exists())->toBeTrue();

    $noPermission = User::factory()->create();
    $this->actingAs($noPermission)->post(route('users.store'), [
        'name' => 'Hacker',
        'email' => 'hacker@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertForbidden();
});

it('rejects assigning the administrator role without holding it', function (): void {
    bootstrapAdministrator();
    $manager = User::factory()->create();
    assignPermissionsThroughRole($manager, [
        Permission::UsersCreate->value,
        Permission::UsersAssignRoles->value,
    ]);

    $this->actingAs($manager)->post(route('users.store'), [
        'name' => 'New Admin',
        'email' => 'newadmin@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => [RoleName::Administrator->value],
    ])->assertForbidden();
});

it('prevents a user from deactivating themselves', function (): void {
    $admin = bootstrapAdministrator();

    $this->actingAs($admin)
        ->put(route('users.status.update', ['user' => $admin->id]), ['is_active' => false])
        ->assertStatus(403);
});

it('refuses to deactivate the only active administrator', function (): void {
    $admin = bootstrapAdministrator();

    $this->actingAs($admin)
        ->put(route('users.status.update', ['user' => $admin->id]), ['is_active' => false])
        ->assertStatus(403);

    $employee = User::factory()->create();
    assignPermissionsThroughRole($employee, [Permission::UsersManageStatus->value]);

    $this->actingAs($employee)
        ->put(route('users.status.update', ['user' => $admin->id]), ['is_active' => false])
        ->assertStatus(409);
});

it('blocks removal of the last active administrator role', function (): void {
    $admin = bootstrapAdministrator();

    $this->actingAs($admin)
        ->withSession(confirmAdministrativePassword())
        ->put(route('users.roles.sync', ['user' => $admin->id]), ['roles' => []])
        ->assertStatus(409);

    expect($admin->fresh()->hasRole(RoleName::Administrator->value))->toBeTrue();
});

it('allows clearing non-administrator roles', function (): void {
    $admin = bootstrapAdministrator();
    $employee = User::factory()->create();
    Role::findOrCreate('Cashier', 'web');
    resolve(SyncUserRoles::class)->handle($admin, $employee, ['Cashier']);

    $this->actingAs($admin)
        ->withSession(confirmAdministrativePassword())
        ->put(route('users.roles.sync', ['user' => $employee->id]), ['roles' => []])
        ->assertRedirectToRoute('users.index');

    expect($employee->fresh()->roles)->toBeEmpty();
});

it('keeps the administrator role synchronized with the permission catalog', function (): void {
    $admin = bootstrapAdministrator();

    expect($admin->getAllPermissions()->pluck('name')->sort()->values()->all())
        ->toBe(collect(Permission::cases())->map(fn (Permission $p): string => $p->value)->sort()->values()->all());
});

it('logs out deactivated users on the next request', function (): void {
    $admin = bootstrapAdministrator();
    $employee = User::factory()->create(['email' => 'employee@example.com', 'password' => Hash::make('password')]);
    Role::findOrCreate('Cashier', 'web');
    $employee->assignRole('Cashier');

    $this->actingAs($employee)->get(route('dashboard'))->assertOk();

    resolve(UpdateUserStatus::class)->handle($admin, $employee, false);

    $this->actingAs($employee)
        ->get(route('dashboard'))
        ->assertRedirectToRoute('login');

    $this->assertGuest();
});

it('archives a user through the destroy route', function (): void {
    $admin = bootstrapAdministrator();
    $target = User::factory()->create();
    $targetId = $target->getKey();

    $this->actingAs($admin)
        ->withSession(confirmAdministrativePassword())
        ->delete(route('users.destroy', ['user' => $targetId]))
        ->assertRedirectToRoute('users.index');

    expect(User::query()->whereKey($targetId)->exists())->toBeFalse()
        ->and(User::query()->withTrashed()->whereKey($targetId)->first()?->deleted_at)->not->toBeNull();
});

it('refuses user archival without users.delete permission', function (): void {
    bootstrapAdministrator();
    $actor = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($actor)
        ->withSession(confirmAdministrativePassword())
        ->delete(route('users.destroy', ['user' => $target->getKey()]))
        ->assertForbidden();

    expect(User::query()->whereKey($target->getKey())->exists())->toBeTrue();
});

it('requires recent password confirmation before user archival', function (): void {
    $admin = bootstrapAdministrator();
    $target = User::factory()->create();
    $targetId = $target->getKey();

    $this->actingAs($admin)
        ->delete(route('users.destroy', ['user' => $targetId]))
        ->assertRedirectToRoute('password.confirm');

    expect(User::query()->whereKey($targetId)->exists())->toBeTrue();
});

it('forbids the actor from archiving themselves via the destroy route', function (): void {
    $admin = bootstrapAdministrator();

    $this->actingAs($admin)
        ->withSession(confirmAdministrativePassword())
        ->delete(route('users.destroy', ['user' => $admin->getKey()]))
        ->assertStatus(403);

    expect(User::query()->whereKey($admin->getKey())->exists())->toBeTrue();
});

it('restores an archived user while preserving their status and roles', function (): void {
    $admin = bootstrapAdministrator();
    $role = Role::findOrCreate(RoleName::Cashier->value, 'web');
    $target = User::factory()->create(['is_active' => false]);
    $target->assignRole($role);
    $target->delete();

    $this->actingAs($admin)
        ->withSession(confirmAdministrativePassword())
        ->put(route('users.restore', ['user' => $target->getKey()]))
        ->assertRedirectToRoute('users.index');

    $restored = $target->fresh();

    expect($restored)->not->toBeNull()
        ->and($restored->is_active)->toBeFalse()
        ->and($restored->hasRole(RoleName::Cashier->value))->toBeTrue();
});

it('opens the management page for an archived user after password confirmation', function (): void {
    $admin = bootstrapAdministrator();
    $target = User::factory()->create();
    $target->delete();

    $this->actingAs($admin)
        ->withSession(confirmAdministrativePassword())
        ->get(route('users.edit', ['user' => $target->getKey()]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('users/edit')
            ->where('isDeleted', true)
            ->where('can.restore', true));
});

it('rejects assigning roles while creating a user without users assign roles permission', function (): void {
    bootstrapAdministrator();
    $actor = User::factory()->create();
    assignPermissionsThroughRole($actor, [Permission::UsersCreate->value]);

    $this->actingAs($actor)
        ->post(route('users.store'), [
            'name' => 'New Cashier',
            'email' => 'new-cashier@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'roles' => [RoleName::Cashier->value],
        ])
        ->assertForbidden();

    expect(User::query()->where('email', 'new-cashier@example.com')->exists())->toBeFalse();
});

it('renders user creation according to role assignment capability', function (): void {
    $admin = bootstrapAdministrator();

    $this->actingAs($admin)
        ->get(route('users.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('users/create')
            ->where('canAssignRoles', true)
            ->where('roles', fn ($roles): bool => $roles->contains('value', RoleName::Administrator->value)));

    $creator = User::factory()->create();
    assignPermissionsThroughRole($creator, [Permission::UsersCreate->value]);

    $this->actingAs($creator)
        ->get(route('users.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('canAssignRoles', false)
            ->where('roles', fn ($roles): bool => ! $roles->contains('value', RoleName::Administrator->value)));
});

it('forbids opening user creation without users create permission', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('users.create'))
        ->assertForbidden();
});

it('updates managed user account details through its controller', function (): void {
    $admin = bootstrapAdministrator();
    $managedUser = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'managed@example.com',
    ]);

    $this->actingAs($admin)
        ->put(route('users.update', $managedUser), [
            'name' => 'New Name',
            'email' => 'managed@example.com',
        ])
        ->assertRedirectToRoute('users.index')
        ->assertSessionDoesntHaveErrors();

    expect($managedUser->refresh()->name)->toBe('New Name')
        ->and($managedUser->email)->toBe('managed@example.com');
});

it('updates a managed user status through its controller', function (): void {
    $admin = bootstrapAdministrator();
    $managedUser = User::factory()->create(['is_active' => true]);

    $this->actingAs($admin)
        ->put(route('users.status.update', $managedUser), ['is_active' => false])
        ->assertRedirectToRoute('users.index')
        ->assertSessionHas('toast.message', 'Account status updated.');

    expect($managedUser->refresh()->is_active)->toBeFalse();
});

it('marks archived users as manageable but not status changeable on the index', function (): void {
    $admin = bootstrapAdministrator();
    $managedUser = User::factory()->create();
    $managedUser->delete();

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('users.data', function ($users) use ($managedUser): bool {
                $archived = $users->firstWhere('id', $managedUser->id);

                return $archived['deleted_at'] !== null
                    && $archived['can_manage_status'] === false
                    && $archived['can_manage'] === true;
            }));
});
