<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Auth\AuthorizesByPermission;
use App\Enums\Permission;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteUserRequest extends FormRequest
{
    use AuthorizesByPermission;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $managedUser = $this->route('user');
        $actor = $this->user();

        return $managedUser instanceof User
            && $actor instanceof User
            && $actor->can($this->permission()->value)
            && ! $actor->is($managedUser);
    }

    /**
     * @return array<string, array<int, string>>
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
