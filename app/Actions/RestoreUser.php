<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Facades\Activity;
use Throwable;

final readonly class RestoreUser
{
    /**
     * @throws Throwable
     */
    public function handle(User $actor, User $managedUser): bool
    {
        return DB::transaction(function () use ($actor, $managedUser): bool {
            $lockedUser = User::query()
                ->withTrashed()
                ->whereKey($managedUser->getKey())
                ->lockForUpdate()
                ->first();

            if ($lockedUser === null || ! $lockedUser->trashed()) {
                return false;
            }

            $lockedUser->restore();

            Activity::causedBy($actor)
                ->performedOn($lockedUser)
                ->withProperties([
                    'event' => 'restored',
                    'email' => $lockedUser->email,
                ])
                ->log('User account restored.');

            return true;
        });
    }
}
