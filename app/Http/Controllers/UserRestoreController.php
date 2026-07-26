<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RestoreUser;
use App\Http\Requests\RestoreUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Throwable;

final readonly class UserRestoreController
{
    /**
     * @throws Throwable
     */
    public function __invoke(RestoreUserRequest $request, User $user, RestoreUser $action): RedirectResponse
    {
        $actor = $request->user();
        assert($actor instanceof User);

        $action->handle($actor, $user);

        return to_route('users.index')
            ->with('toast', ['type' => 'success', 'message' => sprintf('Account for %s restored.', $user->name)]);
    }
}
