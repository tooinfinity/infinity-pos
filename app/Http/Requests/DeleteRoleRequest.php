<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Auth\AuthorizesByPermission;
use App\Enums\Permission;
use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\Permission\Models\Role;

final class DeleteRoleRequest extends FormRequest
{
    use AuthorizesByPermission;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $role = $this->route('role');

        if (! $role instanceof Role || RoleName::contains($role->name)) {
            return false;
        }

        $actor = $this->user();

        return $actor !== null && $actor->can($this->permission()->value);
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
        return Permission::RolesDelete;
    }
}
