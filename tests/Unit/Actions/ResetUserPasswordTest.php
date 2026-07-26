<?php

declare(strict_types=1);

use App\Actions\ResetUserPassword;
use App\Data\ResetUserPasswordData;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

it('resets the user password to the provided value', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);

    $action = resolve(ResetUserPassword::class);

    $action->handle($user, $user, ResetUserPasswordData::from(['password' => 'new-password']));

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue()
        ->and(Hash::check('old-password', $user->password))->toBeFalse();
});

it('stores the password as a hashed value, not plaintext', function (): void {
    $user = User::factory()->create();

    $action = resolve(ResetUserPassword::class);

    $action->handle($user, $user, ResetUserPasswordData::from(['password' => 'plain-secret']));

    $stored = $user->refresh()->password;

    expect($stored)->not->toBe('plain-secret')
        ->and(Hash::check('plain-secret', $stored))->toBeTrue();
});

it('purges existing sessions for the target user when the password is reset', function (): void {
    $user = User::factory()->create();
    $userId = $user->getKey();

    DB::table('sessions')->insert([
        'id' => 'session-1',
        'user_id' => $userId,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'testing',
        'payload' => 'payload',
        'last_activity' => now()->getTimestamp(),
    ]);
    DB::table('sessions')->insert([
        'id' => 'session-2',
        'user_id' => $userId,
        'ip_address' => '127.0.0.2',
        'user_agent' => 'testing',
        'payload' => 'payload',
        'last_activity' => now()->getTimestamp(),
    ]);

    $action = resolve(ResetUserPassword::class);

    $action->handle($user, $user, ResetUserPasswordData::from(['password' => 'new-secret']));

    expect(DB::table('sessions')->where('user_id', $userId)->count())->toBe(0);
});
