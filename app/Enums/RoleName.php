<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleName: string
{
    case Administrator = 'Administrator';
    case StoreManager = 'StoreManager';
    case Cashier = 'Cashier';
    case InventoryClerk = 'InventoryClerk';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $role): string => $role->value, self::cases());
    }

    public static function contains(string $name): bool
    {
        return self::tryFrom($name) !== null;
    }
}
