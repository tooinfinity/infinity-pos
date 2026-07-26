<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class ListManagedUsers
{
    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function execute(): LengthAwarePaginator
    {
        return User::query()
            ->with('roles:id,name,guard_name')
            ->withTrashed()
            ->select(['id', 'name', 'email', 'is_active', 'created_at', 'deleted_at'])
            ->latest('created_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }
}
