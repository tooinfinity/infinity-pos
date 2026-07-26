<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Auth\AuthorizesByPermission;
use App\Enums\Permission;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateManagedUserRequest extends FormRequest
{
    use AuthorizesByPermission;

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

    protected function permission(): Permission
    {
        return Permission::UsersUpdate;
    }
}
