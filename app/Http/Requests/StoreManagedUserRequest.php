<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Models\User;
use App\Rules\RolesGrantAdmin;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class StoreManagedUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(#[CurrentUser] User $actor): bool
    {
        if ($actor->cannot(Permission::UsersCreate->value)) {
            return false;
        }

        if ($this->array('roles') === []) {
            return true;
        }

        return $actor->can(Permission::UsersAssignRoles->value);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $actor = $this->user();
        /** @var array<int, string> $roles */
        $roles = $this->array('roles');

        assert($actor instanceof User);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'confirmed', Password::defaults()],
            'roles' => [
                'bail',
                'present',
                'array',
                new RolesGrantAdmin($actor, $roles),
            ],
            'roles.*' => ['string', 'distinct', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ];
    }
}
