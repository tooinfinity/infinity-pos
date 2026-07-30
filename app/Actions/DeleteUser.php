<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Facades\Activity;
use Throwable;

final readonly class DeleteUser
{
    /**
     * @throws Throwable
     */
    public function handle(User $actor, User $managedUser): void
    {
        DB::transaction(function () use ($actor, $managedUser): void {
            $lockedUser = User::query()
                ->whereKey($managedUser->getKey())
                ->lockForUpdate()
                ->first();

            if ($lockedUser === null) {
                return;
            }

            $lockedUser->delete();

            DB::table('sessions')
                ->where('user_id', $lockedUser->getKey())
                ->delete();

            Activity::causedBy($actor)
                ->performedOn($lockedUser)
                ->withProperties([
                    'event' => 'archived',
                    'email' => $lockedUser->email,
                ])
                ->log('User account archived.');
        });
    }
}
