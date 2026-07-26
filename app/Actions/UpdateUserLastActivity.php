<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Carbon\CarbonImmutable;

final readonly class UpdateUserLastActivity
{
    public function handle(User $user): bool
    {
        $user->forceFill(['last_activity_at' => CarbonImmutable::now()])->save();

        return true;
    }
}
