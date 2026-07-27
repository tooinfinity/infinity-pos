<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateUser;
use App\Actions\UpdateUser;
use App\Data\CreateUserData;
use App\Data\UpdateUserData;
use App\Enums\Permission;
use App\Http\Requests\CreateManagedUserPageRequest;
use App\Http\Requests\EditManagedUserPageRequest;
use App\Http\Requests\StoreManagedUserRequest;
use App\Http\Requests\UpdateManagedUserRequest;
use App\Http\Requests\ViewUsersRequest;
use App\Models\User;
use App\Queries\GetManagedUserEditPageData;
use App\Queries\GetManagedUsersIndexPageData;
use App\Queries\ListSelectableRoles;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

final readonly class UserManagementController
{
    public function index(ViewUsersRequest $request, #[CurrentUser] User $actor, GetManagedUsersIndexPageData $pageData): Response
    {
        return Inertia::render('users/index', $pageData->execute($actor));
    }

    public function create(CreateManagedUserPageRequest $request, #[CurrentUser] User $actor, ListSelectableRoles $roles): Response
    {
        return Inertia::render('users/create', [
            'roles' => $roles->execute($actor),
            'canAssignRoles' => $actor->can(Permission::UsersAssignRoles->value) === true,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(StoreManagedUserRequest $request, #[CurrentUser] User $actor, CreateUser $action): RedirectResponse
    {
        $user = $action->handle($actor, CreateUserData::from($request->validated()));

        return to_route('users.index')
            ->with('toast', ['type' => 'success', 'message' => sprintf('Account for %s created.', $user->name)]);
    }

    public function edit(EditManagedUserPageRequest $request, #[CurrentUser] User $actor, User $user, GetManagedUserEditPageData $pageData): Response
    {
        return Inertia::render('users/edit', $pageData->execute($actor, $user));
    }

    public function update(UpdateManagedUserRequest $request, User $user, UpdateUser $action): RedirectResponse
    {
        $action->handle($user, UpdateUserData::from($request->validated()));

        return to_route('users.index')
            ->with('toast', ['type' => 'success', 'message' => 'Account details updated.']);
    }
}
