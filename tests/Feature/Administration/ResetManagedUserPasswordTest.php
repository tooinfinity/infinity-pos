<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission as PermissionModel;

function bootstrapAdministratorForPasswordReset(): User
{
    $permissions = collect(Permission::cases())
        ->map(fn (Permission $permission): PermissionModel => PermissionModel::findOrCreate($permission->value, 'web'));

    $role = Role::findOrCreate(RoleName::Administrator->value, 'web');
    $role->syncPermissions($permissions);

    $user = User::factory()->create([
        'name' => 'Root Admin',
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);
    $user->assignRole($role);

    return $user;
}

it('resets a managed password after recent password confirmation', function (): void {
    $admin = bootstrapAdministratorForPasswordReset();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->put(route('users.password.reset', ['user' => $target->getKey()]), [
            'password' => 'new-secret',
            'password_confirmation' => 'new-secret',
        ])
        ->assertRedirectToRoute('users.index');

    expect(Hash::check('new-secret', $target->refresh()->password))->toBeTrue();
});

it('redirects to password confirmation before resetting a managed password', function (): void {
    $admin = bootstrapAdministratorForPasswordReset();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->put(route('users.password.reset', ['user' => $target->getKey()]), [
            'password' => 'new-secret',
            'password_confirmation' => 'new-secret',
        ])
        ->assertRedirectToRoute('password.confirm');

    expect(Hash::check('new-secret', $target->refresh()->password))->toBeFalse();
});

it('rejects a managed password reset without its role permission', function (): void {
    bootstrapAdministratorForPasswordReset();
    $target = User::factory()->create();

    $actor = User::factory()->create();

    $this->actingAs($actor)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->put(route('users.password.reset', ['user' => $target->getKey()]), [
            'password' => 'new-secret',
            'password_confirmation' => 'new-secret',
        ])
        ->assertForbidden();

    expect(Hash::check('new-secret', $target->refresh()->password))->toBeFalse();
});
