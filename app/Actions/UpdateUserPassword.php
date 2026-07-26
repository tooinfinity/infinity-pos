<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\UpdatePasswordData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final readonly class UpdateUserPassword
{
    /**
     * @throws ValidationException
     */
    public function handle(User $user, UpdatePasswordData $data): void
    {
        if (! Hash::check($data->currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('The provided password does not match your current password.')],
            ]);
        }

        $user->update([
            'password' => $data->newPassword,
        ]);
    }
}
