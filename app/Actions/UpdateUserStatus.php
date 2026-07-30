<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Facades\Activity;
use Throwable;

final readonly class UpdateUserStatus
{
    /**
     * @throws Throwable
     */
    public function handle(User $actor, User $managedUser, bool $isActive): void
    {
        DB::transaction(function () use ($actor, $managedUser, $isActive): void {
            $lockedUser = User::query()
                ->whereKey($managedUser->getKey())
                ->lockForUpdate()
                ->first();

            if ($lockedUser === null) {
                return;
            }

            $wasActive = (bool) $lockedUser->is_active;

            $lockedUser->forceFill(['is_active' => $isActive])->save();

            Activity::causedBy($actor)
                ->performedOn($lockedUser)
                ->withProperties([
                    'event' => 'status.updated',
                    'from' => $wasActive,
                    'to' => $isActive,
                ])
                ->log('User status updated.');
        });
    }
}
