<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;

final readonly class GetManagedUsersIndexPageData
{
    public function __construct(private ListManagedUsers $users) {}

    /**
     * @return array{users: array<mixed>, can: array{create: bool}}
     */
    public function execute(User $actor): array
    {
        return [
            'users' => $this->users->execute()->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'deleted_at' => $user->deleted_at?->toIso8601String(),
                'created_at' => $user->created_at->toIso8601String(),
                'roles' => $user->roles->map(fn (Role $role): string => $role->name)->all(),
                'can_manage_status' => $actor->can(Permission::UsersManageStatus->value)
                    && ! $actor->is($user)
                    && ! $user->trashed(),
                'can_manage' => $user->trashed()
                    ? $actor->can(Permission::UsersDelete->value)
                    : $actor->canAny([
                        Permission::UsersUpdate->value,
                        Permission::UsersAssignRoles->value,
                        Permission::UsersResetPassword->value,
                        Permission::UsersDelete->value,
                    ]),
            ])->toArray(),
            'can' => ['create' => $actor->can(Permission::UsersCreate->value)],
        ];
    }
}
