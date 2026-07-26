<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateUser;
use App\Actions\UpdateUser;
use App\Data\CreateUserData;
use App\Data\UpdateUserData;
use App\Enums\Permission;
use App\Http\Requests\StoreManagedUserRequest;
use App\Http\Requests\UpdateManagedUserRequest;
use App\Http\Requests\ViewUsersRequest;
use App\Models\User;
use App\Queries\ListManagedUsers;
use App\Queries\ListSelectableRoles;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

final readonly class UserManagementController
{
    public function index(ViewUsersRequest $request, ListManagedUsers $query): Response
    {
        $users = $query->execute();
        $actor = $request->user();
        assert($actor instanceof User);

        return Inertia::render('users/index', [
            'users' => $users->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => (bool) $user->is_active,
                'deleted_at' => $user->deleted_at?->toIso8601String(),
                'created_at' => $user->created_at->toIso8601String(),
                'roles' => $user->roles->pluck('name')->all(),
                'can_manage_status' => $actor->can(Permission::UsersManageStatus->value)
                    && ! $actor->is($user)
                    && $user->deleted_at === null,
                'can_manage' => $user->deleted_at !== null
                    ? $actor->can(Permission::UsersDelete->value)
                    : $actor->canAny([
                        Permission::UsersUpdate->value,
                        Permission::UsersAssignRoles->value,
                        Permission::UsersResetPassword->value,
                        Permission::UsersDelete->value,
                    ]),
            ])->toArray(),
            'can' => ['create' => $actor->can(Permission::UsersCreate->value)],
        ]);
    }

    public function create(#[CurrentUser] User $user, ListSelectableRoles $roles): Response
    {
        $this->abortUnless($user, Permission::UsersCreate->value);

        return Inertia::render('users/create', [
            'roles' => $roles->execute($user),
            'canAssignRoles' => $user->can(Permission::UsersAssignRoles->value) === true,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(StoreManagedUserRequest $request, CreateUser $action): RedirectResponse
    {
        $user = $action->handle(CreateUserData::from($request->validated()));

        return to_route('users.index')
            ->with('toast', ['type' => 'success', 'message' => sprintf('Account for %s created.', $user->name)]);
    }

    public function edit(#[CurrentUser] User $actor, User $user, ListSelectableRoles $roles): Response
    {
        abort_unless($actor->canAny([
            Permission::UsersUpdate->value,
            Permission::UsersAssignRoles->value,
            Permission::UsersResetPassword->value,
            Permission::UsersDelete->value,
        ]), 403);

        $user->load('roles:id,name,guard_name');

        $isDeleted = $user->deleted_at !== null;

        return Inertia::render('users/edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => (bool) $user->is_active,
                'deleted_at' => $user->deleted_at?->toIso8601String(),
                'roles' => $user->roles->pluck('name')->all(),
            ],
            'isDeleted' => $isDeleted,
            'roles' => $roles->execute($actor),
            'can' => [
                'update' => ! $isDeleted && $actor->can(Permission::UsersUpdate->value),
                'assign_roles' => ! $isDeleted && $actor->can(Permission::UsersAssignRoles->value),
                'reset_password' => ! $isDeleted && $actor->can(Permission::UsersResetPassword->value),
                'archive' => ! $isDeleted && ! $actor->is($user) && $actor->can(Permission::UsersDelete->value),
                'restore' => $isDeleted && $actor->can(Permission::UsersDelete->value),
            ],
        ]);
    }

    public function update(UpdateManagedUserRequest $request, User $user, UpdateUser $action): RedirectResponse
    {
        $action->handle($user, UpdateUserData::from($request->validated()));

        return to_route('users.index')
            ->with('toast', ['type' => 'success', 'message' => 'Account details updated.']);
    }

    private function abortUnless(mixed $actor, string $permission): void
    {
        abort_unless($actor instanceof User && $actor->can($permission), 403);
    }
}
