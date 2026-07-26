<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteUser;
use App\Http\Requests\DeleteUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Throwable;

final readonly class UserDestroyController
{
    /**
     * @throws Throwable
     */
    public function __invoke(DeleteUserRequest $request, User $user, DeleteUser $action): RedirectResponse
    {
        $actor = $request->user();
        assert($actor instanceof User);

        $action->handle($actor, $user);

        return to_route('users.index')
            ->with('toast', ['type' => 'success', 'message' => sprintf('Account for %s archived.', $user->name)]);
    }
}
