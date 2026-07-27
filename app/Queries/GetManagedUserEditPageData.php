<?php

declare(strict_types=1);

namespace App\Queries;

use App\Data\ManagedUserData;
use App\Enums\Permission;
use App\Models\User;

final readonly class GetManagedUserEditPageData
{
    public function __construct(private ListSelectableRoles $roles) {}

    /**
     * @return array{
     *     user: ManagedUserData,
     *     isDeleted: bool,
     *     roles: array<int, array{value: string, label: string}>,
     *     can: array{
     *         update: bool,
     *         assign_roles: bool,
     *         reset_password: bool,
     *         archive: bool,
     *         restore: bool
     *     }
     * }
     */
    public function execute(User $actor, User $user): array
    {
        $user->loadMissing('roles:id,name,guard_name');

        $isDeleted = $user->trashed();

        return [
            'user' => ManagedUserData::fromModel($user),
            'isDeleted' => $isDeleted,
            'roles' => $this->roles->execute($actor),
            'can' => [
                'update' => ! $isDeleted && $actor->can(Permission::UsersUpdate->value),
                'assign_roles' => ! $isDeleted && $actor->can(Permission::UsersAssignRoles->value),
                'reset_password' => ! $isDeleted && $actor->can(Permission::UsersResetPassword->value),
                'archive' => ! $isDeleted
                    && ! $actor->is($user)
                    && $actor->can(Permission::UsersDelete->value),
                'restore' => $isDeleted && $actor->can(Permission::UsersDelete->value),
            ],
        ];
    }
}
