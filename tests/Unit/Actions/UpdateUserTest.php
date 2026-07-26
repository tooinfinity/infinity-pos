<?php

declare(strict_types=1);

use App\Actions\UpdateUser;
use App\Data\UpdateUserData;
use App\Models\User;

it('may update a user', function (): void {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@email.com',
    ]);

    $action = resolve(UpdateUser::class);

    $action->handle($user, UpdateUserData::from([
        'name' => 'New Name',
        'email' => 'old@email.com',
    ]));

    expect($user->refresh()->name)->toBe('New Name')
        ->and($user->email)->toBe('old@email.com');
});

it('updates email when it changes', function (): void {
    $user = User::factory()->create([
        'email' => 'old@email.com',
    ]);

    $action = resolve(UpdateUser::class);

    $action->handle($user, UpdateUserData::from([
        'name' => $user->name,
        'email' => 'new@email.com',
    ]));

    expect($user->refresh()->email)->toBe('new@email.com');
});

it('keeps the email and updates other fields when email stays the same', function (): void {
    $user = User::factory()->create([
        'email' => 'same@email.com',
    ]);

    $action = resolve(UpdateUser::class);

    $action->handle($user, UpdateUserData::from([
        'email' => 'same@email.com',
        'name' => 'Updated Name',
    ]));

    expect($user->refresh()->email)->toBe('same@email.com')
        ->and($user->name)->toBe('Updated Name');
});
