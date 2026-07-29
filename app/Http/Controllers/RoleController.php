<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateRole;
use App\Actions\DeleteRole;
use App\Actions\UpdateRole;
use App\Http\Requests\CreateRolePageRequest;
use App\Http\Requests\DeleteRoleRequest;
use App\Http\Requests\EditRolePageRequest;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Requests\ViewRolesRequest;
use App\Models\Role;
use App\Models\User;
use App\Queries\GetRoleEditPageData;
use App\Queries\GetRolesIndexPageData;
use App\Queries\ListPermissionOptions;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class RoleController
{
    public function index(ViewRolesRequest $request, #[CurrentUser] User $actor, GetRolesIndexPageData $pageData): Response
    {
        return Inertia::render('roles/index', $pageData->execute($actor));
    }

    public function create(CreateRolePageRequest $request, ListPermissionOptions $permissions): Response
    {
        return Inertia::render('roles/create', [
            'permissions' => $permissions->execute(),
        ]);
    }

    public function store(StoreRoleRequest $request, CreateRole $action): RedirectResponse
    {
        /** @var string $name */
        $name = $request->validated('name');
        $role = $action->handle($name);

        return to_route('roles.index')
            ->with('toast', ['type' => 'success', 'message' => sprintf('Role %s created.', $role->name)]);
    }

    public function edit(EditRolePageRequest $request, Role $role, #[CurrentUser] User $actor, GetRoleEditPageData $pageData): Response
    {
        return Inertia::render('roles/edit', $pageData->execute($actor, $role));
    }

    public function update(UpdateRoleRequest $request, Role $role, UpdateRole $action): RedirectResponse
    {
        /** @var string $name */
        $name = $request->validated('name');
        $action->handle($role, $name);

        return to_route('roles.index')
            ->with('toast', ['type' => 'success', 'message' => 'Role updated.']);
    }

    public function destroy(DeleteRoleRequest $request, Role $role, DeleteRole $action): RedirectResponse
    {
        if (! $action->handle($role)) {
            return to_route('roles.index')
                ->with('toast', ['type' => 'error', 'message' => 'Role is assigned to one or more users.']);
        }

        return to_route('roles.index')
            ->with('toast', ['type' => 'success', 'message' => 'Role removed.']);
    }
}
