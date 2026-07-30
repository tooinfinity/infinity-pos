<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\AdministratorProtectionMode;
use App\Enums\RoleName;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Translation\PotentiallyTranslatedString;

final class RemainingAdministrator implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    private array $data = [];

    public function __construct(
        private readonly AdministratorProtectionMode $mode,
        private readonly User $target,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->target->hasRole(RoleName::Administrator->value)) {
            return;
        }

        if ($this->shouldSkip($value)) {
            return;
        }

        if ($this->remainingAdministratorCount() < 1) {
            $fail($this->message());
        }
    }

    private function shouldSkip(mixed $value): bool
    {
        if ($this->mode === AdministratorProtectionMode::Status && $value === true) {
            return true;
        }

        if ($this->mode === AdministratorProtectionMode::Role) {
            $roles = $this->payloadRoles();

            return in_array(RoleName::Administrator->value, $roles, true);
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function payloadRoles(): array
    {
        /** @var list<string> $roles */
        $roles = $this->data['roles'] ?? [];

        return array_values(array_filter(
            $roles,
            static fn (string $role): bool => $role !== '',
        ));
    }

    private function remainingAdministratorCount(): int
    {
        $query = User::query()
            ->whereHas(
                'roles',
                fn (Builder $builder) => $builder
                    ->where('name', RoleName::Administrator->value)
                    ->where('guard_name', 'web'),
            )
            ->whereKeyNot($this->target->getKey());

        if ($this->mode === AdministratorProtectionMode::Status
            || $this->mode === AdministratorProtectionMode::Role) {
            $query->where('is_active', true);
        }

        return $query->count();
    }

    private function message(): string
    {
        return match ($this->mode) {
            AdministratorProtectionMode::Role => 'Cannot remove the only active administrator from their administrator role.',
            AdministratorProtectionMode::Status => 'Cannot deactivate the only active administrator.',
            AdministratorProtectionMode::Archive => 'Cannot archive the only remaining administrator.',
        };
    }
}
