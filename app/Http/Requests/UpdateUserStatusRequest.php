<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateUserStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(
        #[CurrentUser] User $actor,
        #[RouteParameter('user')] User $managedUser,
    ): bool {
        return $actor->can($this->permission()->value)
            && ! $actor->is($managedUser);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['is_active' => ['required', 'boolean']];
    }

    private function permission(): Permission
    {
        return Permission::UsersManageStatus;
    }
}
