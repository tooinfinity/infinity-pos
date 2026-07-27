<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\Permission;

final readonly class ListPermissionOptions
{
    /**
     * @return array<int, array{value: string, label: string, group: string}>
     */
    public function execute(): array
    {
        return array_map(static fn (Permission $permission): array => [
            'value' => $permission->value,
            'label' => $permission->label(),
            'group' => $permission->group(),
        ], Permission::cases());
    }
}
