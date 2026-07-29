<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\Permission;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission as PermissionModel;

final readonly class GetRoleEditPageData
{
    public function __construct(private ListPermissionOptions $permissions) {}

    /**
     * @return array{
     *     role: array{id: string, name: string, is_protected: bool, permissions_locked: bool, permissions: array<int, string>},
     *     permissions: array<int, array{value: string, label: string, group: string}>,
     *     can: array{update: bool, delete: bool, assign_permissions: bool}
     * }
     */
    public function execute(User $actor, Role $role): array
    {
        $role->loadMissing('permissions:id,name,guard_name');

        $isProtected = RoleName::contains($role->name);
        $permissionsLocked = $role->name === RoleName::Administrator->value;

        return [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'is_protected' => $isProtected,
                'permissions_locked' => $permissionsLocked,
                'permissions' => $role->permissions->map(fn (PermissionModel $permission): string => $permission->name)->all(),
            ],
            'permissions' => $this->permissions->execute(),
            'can' => [
                'update' => ! $isProtected && $actor->can(Permission::RolesUpdate->value),
                'delete' => ! $isProtected && $actor->can(Permission::RolesDelete->value),
                'assign_permissions' => ! $permissionsLocked
                    && $actor->can(Permission::RolesAssignPermissions->value),
            ],
        ];
    }
}
