<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\UpdateUserData;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class UpdateUser
{
    /**
     * @throws Throwable
     */
    public function handle(User $user, UpdateUserData $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $user->update([
                'name' => $data->name,
                'email' => $data->email,
            ]);

            return $user->refresh();
        });
    }
}
