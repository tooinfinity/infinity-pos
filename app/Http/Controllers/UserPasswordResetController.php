<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ResetUserPassword;
use App\Data\ResetUserPasswordData;
use App\Http\Requests\ResetManagedUserPasswordRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class UserPasswordResetController
{
    public function __invoke(ResetManagedUserPasswordRequest $request, #[CurrentUser] User $actor, User $user, ResetUserPassword $action): RedirectResponse
    {
        $action->handle(
            $actor,
            $user,
            ResetUserPasswordData::from(['password' => $request->validated('password')]),
        );

        return to_route('users.index')
            ->with('toast', ['type' => 'success', 'message' => 'Password reset.']);
    }
}
