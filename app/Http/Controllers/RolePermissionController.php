<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SyncRolePermissions;
use App\Http\Requests\SyncRolePermissionsRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Throwable;

final readonly class RolePermissionController
{
    /**
     * @throws Throwable
     */
    public function __invoke(SyncRolePermissionsRequest $request, Role $role, SyncRolePermissions $action): RedirectResponse
    {
        /** @var array<int, string> $validated */
        $validated = $request->validated('permissions');
        $action->handle($role, $validated);

        return to_route('roles.index')
            ->with('toast', ['type' => 'success', 'message' => 'Permissions updated.']);
    }
}
