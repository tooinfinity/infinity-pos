<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\RoleName;
use App\Http\Requests\DeleteRoleRequest;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Requests\ViewRolesRequest;
use App\Models\User;
use App\Queries\ListRoles;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

final readonly class RoleController
{
    public function index(ViewRolesRequest $request, #[CurrentUser] User $actor, ListRoles $query): Response
    {
        $roles = $query->execute();

        return Inertia::render('roles/index', [
            'roles' => $roles->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'is_protected' => RoleName::contains($role->name),
                'permissions' => $role->permissions->pluck('name')->all(),
                'users_count' => $role->users_count,
            ])->all(),
            'can' => [
                'create' => $actor->can(Permission::RolesCreate->value),
                'update' => $actor->can(Permission::RolesUpdate->value),
                'delete' => $actor->can(Permission::RolesDelete->value),
                'assign_permissions' => $actor->can(Permission::RolesAssignPermissions->value),
            ],
        ]);
    }

    public function create(ViewRolesRequest $request, #[CurrentUser] User $actor): Response
    {
        abort_unless($actor->can(Permission::RolesCreate->value), 403);

        return Inertia::render('roles/create', [
            'permissions' => collect(Permission::cases())->map(fn (Permission $permission): array => [
                'value' => $permission->value,
                'label' => $permission->label(),
                'group' => $permission->group(),
            ])->all(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        /** @var string $name */
        $name = $request->validated('name');
        $role = Role::findOrCreate($name, 'web');
        $role->syncPermissions([]);

        return to_route('roles.index')
            ->with('toast', ['type' => 'success', 'message' => sprintf('Role %s created.', $role->name)]);
    }

    public function edit(ViewRolesRequest $request, Role $role, #[CurrentUser] User $actor): Response
    {
        abort_unless($actor->canAny([
            Permission::RolesUpdate->value,
            Permission::RolesAssignPermissions->value,
        ]) === true, 403);

        $role->load('permissions:id,name,guard_name');

        return Inertia::render('roles/edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'is_protected' => RoleName::contains($role->name),
                'permissions_locked' => $role->name === RoleName::Administrator->value,
                'permissions' => $role->permissions->pluck('name')->all(),
            ],
            'permissions' => collect(Permission::cases())->map(fn (Permission $permission): array => [
                'value' => $permission->value,
                'label' => $permission->label(),
                'group' => $permission->group(),
            ])->all(),
            'can' => [
                'update' => ! RoleName::contains($role->name) && $actor->can(Permission::RolesUpdate->value),
                'delete' => ! RoleName::contains($role->name) && $actor->can(Permission::RolesDelete->value),
                'assign_permissions' => $role->name !== RoleName::Administrator->value
                    && $actor->can(Permission::RolesAssignPermissions->value),
            ],
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $role->update(['name' => $request->validated('name')]);

        return to_route('roles.index')
            ->with('toast', ['type' => 'success', 'message' => 'Role updated.']);
    }

    public function destroy(DeleteRoleRequest $request, Role $role): RedirectResponse
    {
        if ($role->users()->exists()) {
            return to_route('roles.index')
                ->with('toast', ['type' => 'error', 'message' => 'Role is assigned to one or more users.']);
        }

        $role->delete();

        return to_route('roles.index')
            ->with('toast', ['type' => 'success', 'message' => 'Role removed.']);
    }
}
