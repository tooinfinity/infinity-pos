<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\User;
use App\Queries\ListRoles;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role;

it('lists roles alphabetically with permissions and user counts', function (): void {
    $permission = PermissionModel::findOrCreate(Permission::UsersView->value, 'web');
    $zulu = Role::create(['name' => 'Zulu', 'guard_name' => 'web']);
    $alpha = Role::create(['name' => 'Alpha', 'guard_name' => 'web']);
    $alpha->givePermissionTo($permission);

    User::factory()->count(2)->create()->each(
        fn (User $user): User => $user->assignRole($alpha),
    );

    $roles = resolve(ListRoles::class)->execute();

    expect($roles->pluck('name')->all())->toBe(['Alpha', 'Zulu'])
        ->and($roles[0]->relationLoaded('permissions'))->toBeTrue()
        ->and($roles[0]->permissions->pluck('name')->all())->toBe([Permission::UsersView->value])
        ->and($roles[0]->users_count)->toBe(2)
        ->and($roles[1]->relationLoaded('permissions'))->toBeTrue()
        ->and($roles[1]->permissions)->toBeEmpty()
        ->and($roles[1]->users_count)->toBe(0);
});
