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
    public function __construct(
        private CreateUserRole $createUserRole,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(CreateUserData $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::query()->create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => $data->password,
                'is_active' => true,
            ]);

            $this->createUserRole->handle($user, $data->roles);

            event(new Registered($user));

            Activity::causedBy($user)
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
