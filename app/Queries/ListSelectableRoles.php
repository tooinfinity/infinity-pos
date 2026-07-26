<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\RoleName;
use App\Models\User;
use Spatie\Permission\Models\Role;

final readonly class ListSelectableRoles
{
    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function execute(User $actor): array
    {
        $query = Role::query()->orderBy('name');

        if (! $actor->hasRole(RoleName::Administrator->value)) {
            $query->where('name', '!=', RoleName::Administrator->value);
        }

        return $query->get(['id', 'name'])->map(fn (Role $role): array => [
            'value' => $role->name,
            'label' => $role->name,
        ])->all();
    }
}
