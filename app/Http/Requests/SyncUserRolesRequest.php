<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SyncUserRolesRequest extends FormRequest
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
        return [
            'roles' => ['present', 'array'],
            'roles.*' => ['string', 'distinct', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ];
    }

    private function permission(): Permission
    {
        return Permission::UsersAssignRoles;
    }
}
