<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property string $id
 * @property string $name
 * @property string $guard_name
 */
final class Role extends SpatieRole
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    use HasUuids;
}
