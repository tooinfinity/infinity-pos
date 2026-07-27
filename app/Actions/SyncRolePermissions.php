<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Permission;
use App\Enums\RoleName;
use Illuminate\Support\Facades\DB;
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
        $resolved = collect($permissions)
            ->map(fn (string $permission): string => Permission::from($permission)->value)
            ->unique()
            ->values()
            ->all();

        DB::transaction(function () use ($role, $resolved): void {
            $lockedRole = Role::query()
                ->whereKey($role->getKey())
                ->where('guard_name', 'web')
                ->lockForUpdate()
                ->firstOrFail();

            abort_if($lockedRole->name === RoleName::Administrator->value, 403, 'The administrator role permissions are managed automatically.');

            $lockedRole->syncPermissions($resolved);
        });
    }
}
