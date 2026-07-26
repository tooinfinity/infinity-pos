<?php

declare(strict_types=1);

use App\Actions\RestoreUser;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('restores an archived user and records the activity', function (): void {
    $actor = User::factory()->create();
    $managedUser = User::factory()->create();
    $managedUser->delete();

    resolve(RestoreUser::class)->handle($actor, $managedUser);

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

it('rejects restoring a user who is not archived', function (): void {
    $actor = User::factory()->create();
    $managedUser = User::factory()->create();

    resolve(RestoreUser::class)->handle($actor, $managedUser);
})->throws(HttpException::class, 'The account is not archived.');
