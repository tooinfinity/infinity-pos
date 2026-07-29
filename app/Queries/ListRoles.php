<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListRoles
{
    /**
     * @return Collection<int, Role>
     */
    public function execute(): Collection
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->with('permissions:id,name,guard_name')
            ->withCount('users')
            ->orderBy('name')
            ->get();
    }
}
