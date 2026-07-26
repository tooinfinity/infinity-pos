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
    public function handle(User $actor, User $managedUser): void
    {
        DB::transaction(function () use ($actor, $managedUser): void {
            $lockedUser = User::query()
                ->withTrashed()
                ->whereKey($managedUser->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($lockedUser->trashed(), 409, 'The account is not archived.');

            $lockedUser->restore();

            Activity::causedBy($actor)
                ->performedOn($lockedUser)
                ->withProperties([
                    'event' => 'restored',
                    'email' => $lockedUser->email,
                ])
                ->log('User account restored.');
        });
    }
}
