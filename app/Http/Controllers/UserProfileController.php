<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateUser;
use App\Data\UpdateUserData;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class UserProfileController
{
    public function edit(): Response
    {
        return Inertia::render('user-profile/edit', [
            'status' => session('status'),
        ]);
    }

    public function update(UpdateUserRequest $request, #[CurrentUser] User $user, UpdateUser $action): RedirectResponse
    {
        $data = UpdateUserData::from($request->validated());

        $action->handle($user, $data);

        return to_route('user-profile.edit');
    }
}
