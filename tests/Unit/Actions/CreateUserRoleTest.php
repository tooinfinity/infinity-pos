<?php

declare(strict_types=1);

use App\Actions\CreateUserRole;
use App\Models\User;
use Spatie\Permission\Models\Role;

it('assigns the given roles to the user', function (): void {
    Role::create(['name' => 'editor']);
    Role::create(['name' => 'viewer']);

    $user = User::factory()->create();

    $action = resolve(CreateUserRole::class);

    $action->handle($user, ['editor', 'viewer']);

    expect($user->refresh()->roles->pluck('name')->sort()->values()->all())
        ->toBe(['editor', 'viewer']);
});

it('replaces any existing roles on the user', function (): void {
    Role::create(['name' => 'editor']);
    Role::create(['name' => 'viewer']);

    $user = User::factory()->create();
    $user->assignRole('editor');

    $action = resolve(CreateUserRole::class);

    $action->handle($user, ['viewer']);

    expect($user->refresh()->roles->pluck('name')->all())->toBe(['viewer']);
});
