<?php

declare(strict_types=1);

namespace App\Enums;

enum Permission: string
{
    case UsersView = 'users.view';
    case UsersCreate = 'users.create';
    case UsersUpdate = 'users.update';
    case UsersManageStatus = 'users.manage_status';
    case UsersAssignRoles = 'users.assign_roles';
    case UsersResetPassword = 'users.reset_password';
    case UsersDelete = 'users.delete';
    case RolesView = 'roles.view';
    case RolesCreate = 'roles.create';
    case RolesUpdate = 'roles.update';
    case RolesDelete = 'roles.delete';
    case RolesAssignPermissions = 'roles.assign_permissions';

    public function label(): string
    {
        return match ($this) {
            self::UsersView => 'View users',
            self::UsersCreate => 'Create users',
            self::UsersUpdate => 'Update users',
            self::UsersManageStatus => 'Activate and deactivate users',
            self::UsersAssignRoles => 'Assign roles to users',
            self::UsersResetPassword => 'Reset user passwords',
            self::UsersDelete => 'Archive and restore users',
            self::RolesView => 'View roles',
            self::RolesCreate => 'Create roles',
            self::RolesUpdate => 'Update roles',
            self::RolesDelete => 'Delete roles',
            self::RolesAssignPermissions => 'Assign permissions to roles',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::UsersView,
            self::UsersCreate,
            self::UsersUpdate,
            self::UsersManageStatus,
            self::UsersAssignRoles,
            self::UsersResetPassword,
            self::UsersDelete => 'Users',
            self::RolesView,
            self::RolesCreate,
            self::RolesUpdate,
            self::RolesDelete,
            self::RolesAssignPermissions => 'Roles',
        };
    }
}
