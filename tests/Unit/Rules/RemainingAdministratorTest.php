<?php

declare(strict_types=1);

use App\Enums\AdministratorProtectionMode;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use App\Rules\RemainingAdministrator;

beforeEach(function (): void {
    Role::findOrCreate(RoleName::Administrator->value, 'web');
    Role::findOrCreate('editor', 'web');
});

function runRule(RemainingAdministrator $rule, mixed $value, array $data = []): bool
{
    $failed = false;
    $reflected = new ReflectionClass($rule);
    $dataProperty = $reflected->getProperty('data');
    $dataProperty->setValue($rule, $data);

    $rule->validate('is_active', $value, function () use (&$failed): void {
        $failed = true;
    });

    return $failed;
}

it('is a no-op when the target user is not an administrator', function (): void {
    $target = User::factory()->create(['is_active' => true]);
    $target->assignRole('editor');

    $rule = new RemainingAdministrator(AdministratorProtectionMode::Status, $target);

    expect(runRule($rule, false))->toBeFalse();
});

it('passes when an administrator deactivates and other active administrators remain', function (): void {
    $target = User::factory()->create(['is_active' => true]);
    $target->assignRole(RoleName::Administrator->value);

    $other = User::factory()->create(['is_active' => true]);
    $other->assignRole(RoleName::Administrator->value);

    $rule = new RemainingAdministrator(AdministratorProtectionMode::Status, $target);

    expect(runRule($rule, false))->toBeFalse();
});

it('fails when deactivating the only active administrator', function (): void {
    $target = User::factory()->create(['is_active' => true]);
    $target->assignRole(RoleName::Administrator->value);

    $rule = new RemainingAdministrator(AdministratorProtectionMode::Status, $target);

    expect(runRule($rule, false))->toBeTrue();
});

it('ignores status changes that activate the user', function (): void {
    $target = User::factory()->create(['is_active' => false]);
    $target->assignRole(RoleName::Administrator->value);

    $rule = new RemainingAdministrator(AdministratorProtectionMode::Status, $target);

    expect(runRule($rule, true))->toBeFalse();
});

it('passes when archiving an administrator and another administrator exists', function (): void {
    $target = User::factory()->create(['is_active' => true]);
    $target->assignRole(RoleName::Administrator->value);

    $other = User::factory()->create(['is_active' => true]);
    $other->assignRole(RoleName::Administrator->value);

    $rule = new RemainingAdministrator(AdministratorProtectionMode::Archive, $target);

    expect(runRule($rule, null))->toBeFalse();
});

it('fails when archiving the only remaining administrator', function (): void {
    $target = User::factory()->create(['is_active' => true]);
    $target->assignRole(RoleName::Administrator->value);

    $rule = new RemainingAdministrator(AdministratorProtectionMode::Archive, $target);

    expect(runRule($rule, null))->toBeTrue();
});

it('passes when the administrator role remains in the synced roles', function (): void {
    $target = User::factory()->create(['is_active' => true]);
    $target->assignRole(RoleName::Administrator->value);

    $rule = new RemainingAdministrator(AdministratorProtectionMode::Role, $target);

    expect(runRule($rule, [RoleName::Administrator->value, 'editor'], [
        'roles' => [RoleName::Administrator->value, 'editor'],
    ]))->toBeFalse();
});

it('fails when removing administrator role and no other active administrators remain', function (): void {
    $target = User::factory()->create(['is_active' => true]);
    $target->assignRole(RoleName::Administrator->value);

    $rule = new RemainingAdministrator(AdministratorProtectionMode::Role, $target);

    expect(runRule($rule, ['editor'], [
        'roles' => ['editor'],
    ]))->toBeTrue();
});

it('fails when the payload is empty and no other active administrators remain', function (): void {
    $target = User::factory()->create(['is_active' => true]);
    $target->assignRole(RoleName::Administrator->value);

    $rule = new RemainingAdministrator(AdministratorProtectionMode::Role, $target);

    expect(runRule($rule, [], [
        'roles' => [],
    ]))->toBeTrue();
});
