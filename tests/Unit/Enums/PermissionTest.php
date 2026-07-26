<?php

declare(strict_types=1);

use App\Enums\Permission;

it('provides presentation metadata', function (Permission $permission, string $label, string $group): void {
    expect($permission->label())->toBe($label)
        ->and($permission->group())->toBe($group);
})->with([
    [Permission::UsersView, 'View users', 'Users'],
    [Permission::UsersCreate, 'Create users', 'Users'],
    [Permission::UsersUpdate, 'Update users', 'Users'],
    [Permission::UsersManageStatus, 'Activate and deactivate users', 'Users'],
    [Permission::UsersAssignRoles, 'Assign roles to users', 'Users'],
    [Permission::UsersResetPassword, 'Reset user passwords', 'Users'],
    [Permission::UsersDelete, 'Archive and restore users', 'Users'],
    [Permission::RolesView, 'View roles', 'Roles'],
    [Permission::RolesCreate, 'Create roles', 'Roles'],
    [Permission::RolesUpdate, 'Update roles', 'Roles'],
    [Permission::RolesDelete, 'Delete roles', 'Roles'],
    [Permission::RolesAssignPermissions, 'Assign permissions to roles', 'Roles'],
]);
