<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Permission;
use App\Enums\RoleName;
use Spatie\Permission\Models\Role;
use Throwable;

final readonly class SyncRolePermissions
{
    /**
     * @param  array<int, string>  $permissions
     *
     * @throws Throwable
     */
    public function handle(Role $role, array $permissions): void
    {
        abort_if($role->name === RoleName::Administrator->value, 403, 'The administrator role permissions are managed automatically.');

        $resolved = collect($permissions)
            ->map(fn (string $permission): string => Permission::from($permission)->value)
            ->unique()
            ->values()
            ->all();

        $role->syncPermissions($resolved);
    }
}
