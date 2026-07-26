<?php

declare(strict_types=1);

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    config()->set('pos.session.idle_minutes', 15);
});

function recentActivityUser(): User
{
    return User::factory()->create([
        'email' => 'manager@example.com',
        'password' => Hash::make('password'),
        'is_active' => true,
        'last_activity_at' => CarbonImmutable::now(),
    ]);
}

it('allows an active user through and stamps their activity', function (): void {
    $user = recentActivityUser();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();

    expect($user->refresh()->last_activity_at)->not->toBeNull();
});

it('signs out a deactivated user on the next request', function (): void {
    $user = recentActivityUser();
    $user->forceFill(['is_active' => false])->save();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirectToRoute('login');

    $this->assertGuest();
});

it('signs out a soft-deleted user on the next request', function (): void {
    $user = recentActivityUser();
    $user->delete();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirectToRoute('login');

    $this->assertGuest();
});

it('signs out an idle user when the threshold is exceeded', function (): void {
    $user = recentActivityUser();
    $user->forceFill([
        'last_activity_at' => CarbonImmutable::now()->subMinutes(30),
    ])->save();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirectToRoute('login');

    $this->assertGuest();
});

it('keeps an active user who is within the idle threshold', function (): void {
    $user = recentActivityUser();
    $user->forceFill([
        'last_activity_at' => CarbonImmutable::now()->subMinutes(5),
    ])->save();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

it('skips idle checks when the idle window is set to zero', function (): void {
    config()->set('pos.session.idle_minutes', 0);

    $user = recentActivityUser();
    $user->forceFill([
        'last_activity_at' => CarbonImmutable::now()->subYears(2),
    ])->save();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

it('updates the last activity timestamp on the next request', function (): void {
    $user = recentActivityUser();
    $user->forceFill([
        'last_activity_at' => CarbonImmutable::now()->subMinutes(2),
    ])->save();

    $previousActivity = $user->last_activity_at;

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();

    $user->refresh();

    expect($user->last_activity_at)->not->toBeNull()
        ->and($user->last_activity_at->greaterThan($previousActivity))->toBeTrue();
});
