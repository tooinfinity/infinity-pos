<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateUserPassword;
use App\Data\UpdatePasswordData;
use App\Http\Requests\UpdateUserPasswordRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class UserPasswordController
{
    public function edit(): Response
    {
        return Inertia::render('user-password/edit');
    }

    public function update(UpdateUserPasswordRequest $request, #[CurrentUser] User $user, UpdateUserPassword $action): RedirectResponse
    {
        $data = UpdatePasswordData::from([
            'currentPassword' => $request->validated('current_password'),
            'newPassword' => $request->validated('password'),
        ]);

        $action->handle($user, $data);

        return to_route('user-password.edit');
    }
}
