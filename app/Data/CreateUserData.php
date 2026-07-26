<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

final class CreateUserData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        /** @var array<int, string> */
        public array $roles = [],
    ) {}
}
