<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Auth\AuthorizesByPermission;
use App\Enums\Permission;
use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class StoreManagedUserRequest extends FormRequest
{
    use AuthorizesByPermission;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        if ($actor === null || $actor->cannot($this->permission()->value)) {
            return false;
        }

        $roles = $this->array('roles');

        if ($roles !== [] && $actor->cannot(Permission::UsersAssignRoles->value)) {
            return false;
        }

        return ! (in_array(RoleName::Administrator->value, $roles, true) && ! $actor->hasRole(RoleName::Administrator->value));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'confirmed', Password::defaults()],
            'roles' => ['present', 'array'],
            'roles.*' => ['string', 'distinct', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ];
    }

    protected function permission(): Permission
    {
        return Permission::UsersCreate;
    }
}
