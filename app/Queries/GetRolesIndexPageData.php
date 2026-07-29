<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\Permission;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission as PermissionModel;

final readonly class GetRolesIndexPageData
{
    public function __construct(private ListRoles $roles) {}

    /**
     * @return array{
     *     roles: array<int, array{id: string, name: string, is_protected: bool, permissions: array<int, string>, users_count: int}>,
     *     can: array{create: bool, update: bool, delete: bool, assign_permissions: bool}
     * }
     */
    public function execute(User $actor): array
    {
        return [
            'roles' => $this->roles->execute()->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'is_protected' => RoleName::contains($role->name),
                'permissions' => $role->permissions->map(fn (PermissionModel $permission): string => $permission->name)->all(),
                'users_count' => $role->users_count,
            ])->all(),
            'can' => [
                'create' => $actor->can(Permission::RolesCreate->value),
                'update' => $actor->can(Permission::RolesUpdate->value),
                'delete' => $actor->can(Permission::RolesDelete->value),
                'assign_permissions' => $actor->can(Permission::RolesAssignPermissions->value),
            ],
        ];
    }
}
