<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class DeleteRole
{
    /**
     * @throws Throwable
     */
    public function handle(Role $role): bool
    {
        return DB::transaction(function () use ($role): bool {
            $lockedRole = Role::query()
                ->whereKey($role->getKey())
                ->where('guard_name', 'web')
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRole->users()->exists()) {
                return false;
            }

            return (bool) $lockedRole->delete();
        });
    }
}
