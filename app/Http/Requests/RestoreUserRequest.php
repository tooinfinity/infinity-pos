<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Auth\AuthorizesByPermission;
use App\Enums\Permission;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class RestoreUserRequest extends FormRequest
{
    use AuthorizesByPermission;

    public function authorize(): bool
    {
        $managedUser = $this->route('user');
        $actor = $this->user();

        return $managedUser instanceof User
            && $managedUser->trashed()
            && $actor instanceof User
            && $actor->can($this->permission()->value);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    protected function permission(): Permission
    {
        return Permission::UsersDelete;
    }
}
