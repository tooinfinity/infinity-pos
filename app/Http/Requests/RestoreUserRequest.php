<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;

final class RestoreUserRequest extends FormRequest
{
    public function authorize(
        #[CurrentUser] User $actor,
        #[RouteParameter('user')] User $managedUser,
    ): bool {
        return $managedUser->trashed()
            && $actor->can($this->permission()->value);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    private function permission(): Permission
    {
        return Permission::UsersDelete;
    }
}
