<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Role;
use App\Models\User;
use Spatie\LaravelData\Data;

final class ManagedUserData extends Data
{
    /**
     * @param  array<int, string>  $roles
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public bool $is_active,
        public ?string $deleted_at,
        public array $roles,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            is_active: $user->is_active,
            deleted_at: $user->deleted_at?->toIso8601String(),
            roles: $user->roles->map(fn (Role $role): string => $role->name)->all(),
        );
    }
}
