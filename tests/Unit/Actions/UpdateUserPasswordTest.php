<?php

declare(strict_types=1);

use App\Actions\UpdateUserPassword;
use App\Data\UpdatePasswordData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

it('may update a user password', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);

    $action = resolve(UpdateUserPassword::class);

    $action->handle($user, UpdatePasswordData::from([
        'currentPassword' => 'old-password',
        'newPassword' => 'new-password',
    ]));

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue()
        ->and(Hash::check('old-password', $user->password))->toBeFalse();
});

it('rejects an incorrect current password without changing it', function (): void {
    $user = User::factory()->create(['password' => Hash::make('old-password')]);

    resolve(UpdateUserPassword::class)->handle($user, UpdatePasswordData::from([
        'currentPassword' => 'incorrect-password',
        'newPassword' => 'new-password',
    ]));
})->throws(ValidationException::class, 'The provided password does not match your current password.');
