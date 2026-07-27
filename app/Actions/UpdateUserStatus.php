<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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
        abort_if($actor->is($managedUser), 403, 'You cannot change your own status.');

        DB::transaction(function () use ($actor, $managedUser, $isActive): void {
            $lockedUser = User::query()
                ->whereKey($managedUser->getKey())
                ->lockForUpdate()
                ->first();

            if ($lockedUser === null) {
                return;
            }

            abort_if($actor->is($lockedUser), 403, 'You cannot change your own status.');

            $wasActive = $lockedUser->is_active;

            abort_if($wasActive && ! $isActive && $this->isLastActiveAdministrator($lockedUser), 409, 'Cannot deactivate the only active administrator.');

            abort_if(! $actor->hasRole(RoleName::Administrator->value) && $this->isOnlyRemainingAdministrator($lockedUser), 403);

            $lockedUser->forceFill(['is_active' => $isActive])->save();

            Activity::causedBy($actor)
                ->performedOn($lockedUser)
                ->withProperties([
                    'event' => 'status.updated',
                    'from' => (bool) $wasActive,
                    'to' => $isActive,
                ])
                ->log('User status updated.');
        });
    }

    private function isLastActiveAdministrator(User $user): bool
    {
        if (! $user->hasRole(RoleName::Administrator->value)) {
            return false;
        }

        return $this->isOnlyRemainingAdministrator($user);
    }

    private function isOnlyRemainingAdministrator(User $user): bool
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn (Builder $query) => $query
                ->where('name', RoleName::Administrator->value)
                ->where('guard_name', 'web'))
            ->whereKeyNot($user->getKey())
            ->doesntExist();
    }
}
