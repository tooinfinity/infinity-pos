<?php

declare(strict_types=1);

use App\Actions\DeleteUser;
use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    Role::findOrCreate(RoleName::Administrator->value, 'web');
    Role::findOrCreate('editor', 'web');
});

it('soft-deletes a user', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole(RoleName::Administrator->value);

    $managedUser = User::factory()->create();

    $action = resolve(DeleteUser::class);

    $action->handle($actor, $managedUser);

    expect($managedUser->refresh()->deleted_at)->not->toBeNull()
        ->and(User::query()->whereKey($managedUser->getKey())->exists())->toBeFalse()
        ->and(User::query()->withTrashed()->whereKey($managedUser->getKey())->exists())->toBeTrue();
});

it('purges the sessions of a soft-deleted user', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole(RoleName::Administrator->value);

    $managedUser = User::factory()->create();
    $managedUserId = $managedUser->getKey();

    DB::table('sessions')->insert([
        'id' => 'session-1',
        'user_id' => $managedUserId,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'testing',
        'payload' => 'payload',
        'last_activity' => now()->getTimestamp(),
    ]);

    $action = resolve(DeleteUser::class);

    $action->handle($actor, $managedUser);

    expect(DB::table('sessions')->where('user_id', $managedUserId)->count())->toBe(0);
});

it('forbids the actor from archiving themselves', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole(RoleName::Administrator->value);

    $action = resolve(DeleteUser::class);

    $action->handle($actor, $actor);
})->throws(HttpException::class, 'You cannot archive your own account.');

it('forbids a non-administrator from archiving the only remaining administrator', function (): void {
    $actManager = User::factory()->create();
    $actManager->assignRole('editor');

    $onlyAdmin = User::factory()->create();
    $onlyAdmin->assignRole(RoleName::Administrator->value);

    $action = resolve(DeleteUser::class);

    $action->handle($actManager, $onlyAdmin);
})->throws(HttpException::class, 'Only administrators can archive administrators.');

it('allows deletion of a non-administrator user when other administrators exist', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole(RoleName::Administrator->value);

    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole(RoleName::Administrator->value);

    $managedUser = User::factory()->create();
    $managedUser->assignRole('editor');

    $managedUserId = $managedUser->getKey();

    $action = resolve(DeleteUser::class);

    $action->handle($actor, $managedUser);

    expect(User::query()->whereKey($managedUserId)->exists())->toBeFalse();
});

it('forbids a non-administrator actor from archiving an administrator when other admins exist', function (): void {
    $actManager = User::factory()->create();
    $actManager->assignRole('editor');

    $secondAdmin = User::factory()->create();
    $secondAdmin->assignRole(RoleName::Administrator->value);

    $action = resolve(DeleteUser::class);

    $action->handle($actManager, $secondAdmin);
})->throws(HttpException::class, 'Only administrators can archive administrators.');

it('allows deletion of an administrator when at least one other administrator exists', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole(RoleName::Administrator->value);

    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole(RoleName::Administrator->value);

    $managedUser = User::factory()->create();
    $managedUser->assignRole(RoleName::Administrator->value);

    $managedUserId = $managedUser->getKey();

    $action = resolve(DeleteUser::class);

    $action->handle($actor, $managedUser);

    expect(User::query()->whereKey($managedUserId)->exists())->toBeFalse();
});

it('does nothing when the managed user no longer exists', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole(RoleName::Administrator->value);

    $ghost = User::factory()->create();
    $ghostId = $ghost->getKey();
    $ghost->delete();
    $ghost->forceDelete();

    $action = resolve(DeleteUser::class);

    $action->handle($actor, User::query()->find($ghostId) ?? $ghost);

    expect(User::query()->withTrashed()->whereKey($ghostId)->exists())->toBeFalse();
});
