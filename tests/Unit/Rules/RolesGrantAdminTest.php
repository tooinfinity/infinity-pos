<?php

declare(strict_types=1);

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use App\Rules\RolesGrantAdmin;

beforeEach(function (): void {
    Role::findOrCreate(RoleName::Administrator->value, 'web');
    Role::findOrCreate('editor', 'web');
});

function runGrantRule(RolesGrantAdmin $rule): bool
{
    $failed = false;

    $rule->validate('roles', null, function () use (&$failed): void {
        $failed = true;
    });

    return $failed;
}

it('passes when the payload does not contain the administrator role', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole('editor');

    expect(runGrantRule(new RolesGrantAdmin($actor, ['editor'])))->toBeFalse();
});

it('passes when an administrator grants the administrator role', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole(RoleName::Administrator->value);

    expect(runGrantRule(new RolesGrantAdmin($actor, [RoleName::Administrator->value])))->toBeFalse();
});

it('fails when a non-administrator grants the administrator role', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole('editor');

    expect(runGrantRule(new RolesGrantAdmin($actor, [RoleName::Administrator->value])))->toBeTrue();
});
