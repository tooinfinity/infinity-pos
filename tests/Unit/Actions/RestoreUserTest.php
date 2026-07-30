<?php

declare(strict_types=1);

use App\Actions\RestoreUser;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

it('restores an archived user and records the activity', function (): void {
    $actor = User::factory()->create();
    $managedUser = User::factory()->create();
    $managedUser->delete();

    expect(resolve(RestoreUser::class)->handle($actor, $managedUser))->toBeTrue();

    $activity = Activity::query()
        ->where('description', 'User account restored.')
        ->latest('id')
        ->firstOrFail();

    expect($managedUser->fresh())->not->toBeNull()
        ->and($activity->causer->is($actor))->toBeTrue()
        ->and($activity->subject->is($managedUser))->toBeTrue()
        ->and($activity->properties->get('event'))->toBe('restored')
        ->and($activity->properties->get('email'))->toBe($managedUser->email);
});

it('returns false when restoring a user who is not archived', function (): void {
    $actor = User::factory()->create();
    $managedUser = User::factory()->create();

    expect(resolve(RestoreUser::class)->handle($actor, $managedUser))->toBeFalse();
});
