<?php

declare(strict_types=1);

namespace App\Actions;

use Spatie\Permission\Models\Role;

final readonly class CreateRole
{
    public function handle(string $name): Role
    {
        return Role::query()->create([
            'name' => $name,
            'guard_name' => 'web',
        ]);
    }
}
