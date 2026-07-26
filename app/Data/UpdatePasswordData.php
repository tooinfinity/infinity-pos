<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

final class UpdatePasswordData extends Data
{
    public function __construct(
        public string $currentPassword,
        public string $newPassword,
    ) {}
}
