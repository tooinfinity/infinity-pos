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
