<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Auth\AuthorizesByPermission;
use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

final class ViewUsersRequest extends FormRequest
{
    use AuthorizesByPermission;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    protected function permission(): Permission
    {
        return Permission::UsersView;
    }
}
