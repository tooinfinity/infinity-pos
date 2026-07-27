<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateManagedUserRequest extends FormRequest
{
    public function authorize(#[CurrentUser] User $user): bool
    {
        return $user->can($this->permission()->value) === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $managedUser = $this->route('user');
        assert($managedUser instanceof User);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($managedUser->id)],
        ];
    }

    private function permission(): Permission
    {
        return Permission::UsersUpdate;
    }
}
