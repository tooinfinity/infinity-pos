<?php

declare(strict_types=1);

namespace App\Auth;

use App\Enums\Permission;

trait AuthorizesByPermission
{
    abstract protected function permission(): Permission;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can($this->permission()->value) === true;
    }
}
