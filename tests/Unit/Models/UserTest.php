<?php

declare(strict_types=1);

use App\Models\User;

test('to array', function (): void {
    $user = User::factory()->create()->refresh();

    expect(array_keys($user->toArray()))
        ->toBe([
            'id',
            'name',
            'email',
            'is_active',
            'created_at',
            'updated_at',
            'last_activity_at',
            'deleted_at',
        ]);
});

it('touches and persists user activity', function (): void {
    $user = User::factory()->create(['last_activity_at' => null]);

    expect($user->touchActivity())->toBeTrue()
        ->and($user->refresh()->last_activity_at)->not->toBeNull();
});
