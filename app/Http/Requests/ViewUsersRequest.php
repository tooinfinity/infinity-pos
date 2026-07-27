<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Http\FormRequest;

final class ViewUsersRequest extends FormRequest
{
    public function authorize(#[CurrentUser] User $user): bool
    {
        return $user->can($this->permission()->value) === true;
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
        return Permission::UsersView;
    }
}
