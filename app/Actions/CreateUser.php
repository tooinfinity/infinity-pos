<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\CreateUserData;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Facades\Activity;
use Throwable;

final readonly class CreateUser
{
    /**
     * @throws Throwable
     */
    public function handle(User $actor, CreateUserData $data): User
    {
        return DB::transaction(function () use ($actor, $data): User {
            $user = User::query()->create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => $data->password,
                'is_active' => true,
            ]);

            $user->syncRoles($data->roles);

            event(new Registered($user));

            Activity::causedBy($actor)
                ->performedOn($user)
                ->withProperties([
                    'event' => 'created',
                    'roles' => array_values($data->roles),
                ])
                ->log('User account created.');

            return $user;
        });
    }
}
