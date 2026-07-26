<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('renders the password settings page', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('user-password.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('user-password/edit'));
});

it('updates the authenticated users password', function (): void {
    $user = User::factory()->create(['password' => Hash::make('old-password')]);

    $this->actingAs($user)
        ->put(route('user-password.update'), [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertRedirectToRoute('user-password.edit')
        ->assertSessionDoesntHaveErrors();

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

it('rejects invalid password update input through the controller route', function (): void {
    $user = User::factory()->create(['password' => Hash::make('old-password')]);

    $this->actingAs($user)
        ->fromRoute('user-password.edit')
        ->put(route('user-password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'different-password',
        ])
        ->assertRedirectToRoute('user-password.edit')
        ->assertSessionHasErrors(['current_password', 'password']);

    expect(Hash::check('old-password', $user->refresh()->password))->toBeTrue();
});
