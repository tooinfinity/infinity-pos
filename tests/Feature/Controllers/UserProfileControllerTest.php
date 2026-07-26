<?php

declare(strict_types=1);

use App\Models\User;

it('renders profile edit page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->fromRoute('dashboard')
        ->get(route('user-profile.edit'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('user-profile/edit')
            ->has('status'));
});

it('may update profile information', function (): void {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);

    $response = $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->put(route('user-profile.update'), [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

    $response->assertRedirectToRoute('user-profile.edit');

    expect($user->refresh()->name)->toBe('New Name')
        ->and($user->email)->toBe('new@example.com');
});

it('requires name', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->put(route('user-profile.update'), [
            'email' => 'test@example.com',
        ]);

    $response->assertRedirectToRoute('user-profile.edit')
        ->assertSessionHasErrors('name');
});

it('requires email', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->put(route('user-profile.update'), [
            'name' => 'Test User',
        ]);

    $response->assertRedirectToRoute('user-profile.edit')
        ->assertSessionHasErrors('email');
});

it('requires valid email', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->put(route('user-profile.update'), [
            'name' => 'Test User',
            'email' => 'not-an-email',
        ]);

    $response->assertRedirectToRoute('user-profile.edit')
        ->assertSessionHasErrors('email');
});

it('requires unique email except own', function (): void {
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);
    $user = User::factory()->create(['email' => 'test@example.com']);

    $response = $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->put(route('user-profile.update'), [
            'name' => 'Test User',
            'email' => 'existing@example.com',
        ]);

    $response->assertRedirectToRoute('user-profile.edit')
        ->assertSessionHasErrors('email');
});

it('allows keeping same email', function (): void {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $response = $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->put(route('user-profile.update'), [
            'name' => 'Updated Name',
            'email' => 'test@example.com',
        ]);

    $response->assertRedirectToRoute('user-profile.edit')
        ->assertSessionDoesntHaveErrors();
});
