<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Permission;
use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role;
use Throwable;

#[Signature('app:bootstrap-admin')]
#[Description('Create or update the initial administrator account and authorization catalog')]
final class BootstrapAdministratorCommand extends Command
{
    /**
     * @throws Throwable
     */
    public function handle(): int
    {
        /** @var string $name */
        $name = $this->ask('Administrator name');
        /** @var string $emailInput */
        $emailInput = $this->ask('Administrator email');
        $email = mb_strtolower($emailInput);
        /** @var string $password */
        $password = $this->secret('Administrator password');
        /** @var string $passwordConfirmation */
        $passwordConfirmation = $this->secret('Confirm administrator password');
        /** @var int|string|null $existingUserId */
        $existingUserId = User::query()->withTrashed()->where('email', $email)->value('id');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($existingUserId)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($name, $email, $password): void {
            $this->bootstrapAuthorizationCatalog();

            $administrator = User::query()->withTrashed()->firstOrNew(['email' => $email]);
            $administrator->fill([
                'name' => $name,
                'password' => $password,
                'is_active' => true,
            ]);
            $administrator->save();
            $administrator->restore();

            $administrator->syncRoles([RoleName::Administrator->value]);
        });

        $this->info('Administrator access is ready.');

        return self::SUCCESS;
    }

    private function bootstrapAuthorizationCatalog(): void
    {
        $permissions = collect(Permission::cases())
            ->map(fn (Permission $permission): \Spatie\Permission\Contracts\Permission => PermissionModel::findOrCreate($permission->value, 'web'));

        foreach (RoleName::cases() as $roleName) {
            Role::findOrCreate($roleName->value, 'web');
        }

        Role::findByName(RoleName::Administrator->value, 'web')->syncPermissions($permissions);
    }
}
