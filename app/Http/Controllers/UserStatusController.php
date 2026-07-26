<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateUserStatus;
use App\Http\Requests\UpdateUserStatusRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Throwable;

final readonly class UserStatusController
{
    /**
     * @throws Throwable
     */
    public function __invoke(UpdateUserStatusRequest $request, User $user, UpdateUserStatus $action): RedirectResponse
    {
        $actor = $request->user();
        assert($actor instanceof User);

        $action->handle(
            $actor,
            $user,
            (bool) $request->boolean('is_active'),
        );

        return to_route('users.index')
            ->with('toast', ['type' => 'success', 'message' => 'Account status updated.']);
    }
}
