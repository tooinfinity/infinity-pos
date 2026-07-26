<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('renders the Fortify password confirmation page', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('password.confirm'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('user-password-confirmation/create'));
});

it('confirms the current password and returns to the intended location', function (): void {
    $user = User::factory()->create(['password' => Hash::make('password')]);

    $this->actingAs($user)
        ->withSession(['url.intended' => route('dashboard')])
        ->post(route('password.confirm.store'), ['password' => 'password'])
        ->assertRedirect(route('dashboard'));

    expect(session('auth.password_confirmed_at'))->toBeInt();
});

it('rejects an incorrect password confirmation', function (): void {
    $user = User::factory()->create(['password' => Hash::make('password')]);

    $this->actingAs($user)
        ->fromRoute('password.confirm')
        ->post(route('password.confirm.store'), ['password' => 'incorrect'])
        ->assertRedirectToRoute('password.confirm')
        ->assertSessionHasErrors('password');
});
