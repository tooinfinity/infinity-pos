<?php

declare(strict_types=1);

use App\Enums\RoleName;
use App\Models\User;
use App\Queries\ListSelectableRoles;
use Spatie\Permission\Models\Role;

it('hides the administrator role from non-administrators', function (): void {
    Role::findOrCreate(RoleName::Administrator->value, 'web');
    Role::findOrCreate('Viewer', 'web');
    Role::findOrCreate('Editor', 'web');

    expect(resolve(ListSelectableRoles::class)->execute(User::factory()->create()))->toBe([
        ['value' => 'Editor', 'label' => 'Editor'],
        ['value' => 'Viewer', 'label' => 'Viewer'],
    ]);
});
