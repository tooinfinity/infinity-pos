<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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
        abort_if($actor->is($managedUser), 403, 'You cannot archive your own account.');

        DB::transaction(function () use ($actor, $managedUser): void {
            $lockedUser = User::query()
                ->whereKey($managedUser->getKey())
                ->lockForUpdate()
                ->first();

            if ($lockedUser === null) {
                return;
            }

            abort_if($actor->is($lockedUser), 403, 'You cannot archive your own account.');

            abort_if(
                $lockedUser->hasRole(RoleName::Administrator->value) && ! $actor->hasRole(RoleName::Administrator->value),
                403,
                'Only administrators can archive administrators.',
            );

            abort_if($this->isOnlyRemainingAdministrator($lockedUser), 409, 'Cannot archive the only remaining administrator.');

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

    private function isOnlyRemainingAdministrator(User $user): bool
    {
        if (! $user->hasRole(RoleName::Administrator->value)) {
            return false;
        }

        return User::query()
            ->whereKeyNot($user->getKey())
            ->whereHas('roles', fn (Builder $query) => $query->where('name', RoleName::Administrator->value))
            ->doesntExist();
    }
}
