<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\CreateUserData;
use App\Enums\Permission;
use App\Enums\RoleName;
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
        abort_if($actor->cannot(Permission::UsersCreate->value), 403);
        abort_if($data->roles !== [] && $actor->cannot(Permission::UsersAssignRoles->value), 403);
        abort_if(in_array(RoleName::Administrator->value, $data->roles, true) && ! $actor->hasRole(RoleName::Administrator->value), 403);

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
