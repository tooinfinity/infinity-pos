<?php

declare(strict_types=1);

use App\Actions\UpdateUserLastActivity;
use App\Models\User;

it('sets the timestamp on the user and saves it', function (): void {
    $user = User::factory()->create(['last_activity_at' => null]);

    $action = resolve(UpdateUserLastActivity::class);
    $result = $action->handle($user);

    expect($result)->toBeTrue()
        ->and($user->refresh()->last_activity_at)->not->toBeNull();
});
