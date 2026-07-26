<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

final readonly class CreateUserRole
{
    /** @param array<int, string> $roles */
    public function handle(
        User $user,
        array $roles,
    ): void {
        $user->syncRoles($roles);
    }
}
