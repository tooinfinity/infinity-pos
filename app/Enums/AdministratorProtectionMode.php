<?php

declare(strict_types=1);

namespace App\Enums;

enum AdministratorProtectionMode: string
{
    case Role = 'role';
    case Status = 'status';
    case Archive = 'archive';
}
