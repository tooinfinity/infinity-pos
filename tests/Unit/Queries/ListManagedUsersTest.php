<?php

declare(strict_types=1);

use App\Models\User;
use App\Queries\ListManagedUsers;
use Spatie\Permission\Models\Role;

it('paginates managed users newest first with archived users and roles', function (): void {
    $role = Role::findOrCreate('Manager', 'web');
    $oldestUser = User::factory()->create(['created_at' => now()->subDays(2)]);
    $archivedUser = User::factory()->create(['created_at' => now()->subDay()]);
    $newestUser = User::factory()->create(['created_at' => now()]);

    $newestUser->assignRole($role);

    $archivedUser->delete();

    $users = resolve(ListManagedUsers::class)->execute();

    expect($users->perPage())->toBe(15)
        ->and($users->total())->toBe(3)
        ->and($users->getCollection()->modelKeys())->toBe([
            $newestUser->getKey(),
            $archivedUser->getKey(),
            $oldestUser->getKey(),
        ])
        ->and($users->getCollection()->first()->relationLoaded('roles'))->toBeTrue()
        ->and($users->getCollection()->first()->roles->pluck('name')->all())->toBe(['Manager'])
        ->and($users->getCollection()->contains(fn (User $user): bool => $user->is($archivedUser) && $user->trashed()))->toBeTrue();
});

it('limits each page to fifteen users', function (): void {
    User::factory()->count(16)->create();

    $users = resolve(ListManagedUsers::class)->execute();

    expect($users->count())->toBe(15)
        ->and($users->total())->toBe(16)
        ->and($users->lastPage())->toBe(2);
});
