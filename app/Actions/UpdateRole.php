<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class UpdateRole
{
    /**
     * @throws Throwable
     */
    public function handle(Role $role, string $name): Role
    {
        return DB::transaction(function () use ($role, $name): Role {
            $lockedRole = Role::query()
                ->whereKey($role->getKey())
                ->where('guard_name', 'web')
                ->lockForUpdate()
                ->firstOrFail();

            $lockedRole->update(['name' => $name]);

            return $lockedRole->refresh();
        });
    }
}
