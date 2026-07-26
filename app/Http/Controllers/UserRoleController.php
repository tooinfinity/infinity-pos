<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SyncUserRoles;
use App\Http\Requests\SyncUserRolesRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Throwable;

final readonly class UserRoleController
{
    /**
     * @throws Throwable
     */
    public function __invoke(SyncUserRolesRequest $request, User $user, SyncUserRoles $action): RedirectResponse
    {
        $actor = $request->user();
        assert($actor instanceof User);

        /** @var array<int, string> $validated */
        $validated = $request->validated('roles');

        $action->handle(
            $actor,
            $user,
            array_values(array_unique($validated)),
        );

        return to_route('users.index')
            ->with('toast', ['type' => 'success', 'message' => 'Roles updated.']);
    }
}
