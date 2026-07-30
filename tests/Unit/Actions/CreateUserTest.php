<?php

declare(strict_types=1);

use App\Actions\CreateUser;
use App\Data\CreateUserData;
use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission as PermissionModel;

it('may create a user', function (): void {
    Event::fake([Registered::class]);

    $action = resolve(CreateUser::class);
    $actor = User::factory()->create();
    $role = Role::findOrCreate('User creator', 'web');
    $role->givePermissionTo(PermissionModel::findOrCreate(Permission::UsersCreate->value, 'web'));

    $actor->assignRole($role);

    $user = $action->handle($actor, CreateUserData::from([
        'name' => 'Test User',
        'email' => 'example@email.com',
        'password' => 'password',
    ]));

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('Test User')
        ->and($user->email)->toBe('example@email.com')
        ->and($user->password)->not->toBe('password');

    $activity = Activity::query()->where('description', 'User account created.')->firstOrFail();

    expect($activity->causer?->is($actor))->toBeTrue()
        ->and($activity->subject?->is($user))->toBeTrue();

    Event::assertDispatched(Registered::class);
});
