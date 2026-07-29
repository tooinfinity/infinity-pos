<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Permission;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Facades\Activity;
use Throwable;

final readonly class SyncUserRoles
{
    /**
     * @param  array<int, string>  $roles
     *
     * @throws Throwable
     */
    public function handle(User $actor, User $managedUser, array $roles): void
    {
        DB::transaction(function () use ($actor, $managedUser, $roles): void {
            $lockedUser = User::query()
                ->whereKey($managedUser->getKey())
                ->lockForUpdate()
                ->first();

            if ($lockedUser === null) {
                return;
            }

            $currentRoles = $lockedUser->roles()->pluck('name')->all();
            $wasAdministrator = in_array(RoleName::Administrator->value, $currentRoles, true);
            $willBeAdministrator = in_array(RoleName::Administrator->value, $roles, true);

            abort_if($wasAdministrator && ! $willBeAdministrator && $this->isLastActiveAdministrator($lockedUser), 409, 'Cannot remove the only active administrator from their administrator role.');

            $lockedUser->syncRoles($roles);

            $this->retainAdministratorPermissions($lockedUser);

            Activity::causedBy($actor)
                ->performedOn($lockedUser)
                ->withProperties([
                    'event' => 'roles.synced',
                    'roles_before' => array_values($currentRoles),
                    'roles_after' => array_values($roles),
                ])
                ->log('User roles updated.');
        });
    }

    private function isLastActiveAdministrator(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        $activeAdministratorCount = Role::findByName(RoleName::Administrator->value, 'web')->users()
            ->where('users.is_active', true)
            ->count();

        return $activeAdministratorCount <= 1;
    }

    private function retainAdministratorPermissions(User $user): void
    {
        $administratorRole = Role::query()
            ->where('name', RoleName::Administrator->value)
            ->where('guard_name', 'web')
            ->first();

        if ($administratorRole === null) {
            return;
        }

        if (! $user->hasRole($administratorRole)) {
            return;
        }

        $administratorRole->syncPermissions(
            collect(Permission::cases())->map(fn (Permission $permission): string => $permission->value),
        );
    }
}
