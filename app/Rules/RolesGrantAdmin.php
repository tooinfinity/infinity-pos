<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\RoleName;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final readonly class RolesGrantAdmin implements ValidationRule
{
    /**
     * @param  array<int, string>  $roles
     */
    public function __construct(
        private User $actor,
        private array $roles,
    ) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! in_array(RoleName::Administrator->value, $this->roles, true)) {
            return;
        }

        if ($this->actor->hasRole(RoleName::Administrator->value)) {
            return;
        }

        $fail('Only administrators can grant the administrator role.');
    }
}
