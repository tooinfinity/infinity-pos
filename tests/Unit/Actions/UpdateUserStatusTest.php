<?php

declare(strict_types=1);

use App\Actions\UpdateUserStatus;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    Role::findOrCreate(RoleName::Administrator->value, 'web');
    Role::findOrCreate('editor', 'web');
});

it('deactivates an active user', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole(RoleName::Administrator->value);

    $managedUser = User::factory()->create(['is_active' => true]);

    $action = resolve(UpdateUserStatus::class);

    $action->handle($actor, $managedUser, isActive: false);

    expect($managedUser->refresh()->is_active)->toBeFalse();
});

it('activates an inactive user', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole(RoleName::Administrator->value);

    $managedUser = User::factory()->create(['is_active' => false]);

    $action = resolve(UpdateUserStatus::class);

    $action->handle($actor, $managedUser, isActive: true);

    expect($managedUser->refresh()->is_active)->toBeTrue();
});

it('forbids deactivating the only active administrator', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole('editor');

    $onlyAdmin = User::factory()->create(['is_active' => true]);
    $onlyAdmin->assignRole(RoleName::Administrator->value);

    $action = resolve(UpdateUserStatus::class);

    $action->handle($actor, $onlyAdmin, isActive: false);
})->throws(HttpException::class, 'Cannot deactivate the only active administrator.');

it('allows deactivating an administrator when another active administrator remains', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole(RoleName::Administrator->value);

    $otherAdmin = User::factory()->create(['is_active' => true]);
    $otherAdmin->assignRole(RoleName::Administrator->value);

    $managedUser = User::factory()->create(['is_active' => true]);
    $managedUser->assignRole(RoleName::Administrator->value);

    $action = resolve(UpdateUserStatus::class);

    $action->handle($actor, $managedUser, isActive: false);

    expect($managedUser->refresh()->is_active)->toBeFalse();
});

it('does not block status changes for non-administrator users', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole('editor');

    $otherAdmin = User::factory()->create(['is_active' => true]);
    $otherAdmin->assignRole(RoleName::Administrator->value);

    $soleUser = User::factory()->create(['is_active' => false]);

    $action = resolve(UpdateUserStatus::class);

    $action->handle($actor, $soleUser, isActive: true);

    expect($soleUser->refresh()->is_active)->toBeTrue();
});

it('does nothing when the managed user no longer exists', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole(RoleName::Administrator->value);

    $ghost = User::factory()->create();
    $ghostId = $ghost->getKey();
    $ghost->delete();
    $ghost->forceDelete();

    $action = resolve(UpdateUserStatus::class);

    $action->handle($actor, User::query()->find($ghostId) ?? $ghost, isActive: false);

    expect(User::query()->withTrashed()->whereKey($ghostId)->exists())->toBeFalse();
});

it('forbids a non-administrator actor from targeting the only remaining administrator role', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole('editor');

    $onlyAdmin = User::factory()->create(['is_active' => false]);
    $onlyAdmin->assignRole(RoleName::Administrator->value);

    $target = User::factory()->create(['is_active' => false]);
    $target->assignRole(RoleName::Administrator->value);

    $action = resolve(UpdateUserStatus::class);

    $action->handle($actor, $target, isActive: true);
})->throws(HttpException::class);
