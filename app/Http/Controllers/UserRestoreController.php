<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RestoreUser;
use App\Http\Requests\RestoreUserRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Throwable;

final readonly class UserRestoreController
{
    /**
     * @throws Throwable
     */
    public function __invoke(RestoreUserRequest $request, #[CurrentUser] User $actor, User $user, RestoreUser $action): RedirectResponse
    {
        if (! $action->handle($actor, $user)) {
            return to_route('users.index')
                ->with('toast', ['type' => 'error', 'message' => 'The account is not archived.']);
        }

        return to_route('users.index')
            ->with('toast', ['type' => 'success', 'message' => sprintf('Account for %s restored.', $user->name)]);
    }
}
