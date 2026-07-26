<?php

declare(strict_types=1);

namespace App\Queries;

use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

final readonly class ListRoles
{
    /**
     * @return Collection<int, Role>
     */
    public function execute(): Collection
    {
        return Role::query()
            ->with('permissions:id,name,guard_name')
            ->withCount('users')
            ->orderBy('name')
            ->get();
    }
}
