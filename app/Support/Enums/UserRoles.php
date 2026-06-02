<?php

namespace App\Support\Enums;

use InvalidArgumentException;

final class UserRoles
{
    public const ADMIN = 'admin';
    public const WAREHOUSE = 'warehouse';
    public const SPV = 'spv';
    public const FINANCE = 'finance';
    public const PURCHASING = 'purchasing';

    public static function all(): array
    {
        return [
            self::ADMIN,
            self::WAREHOUSE,
            self::SPV,
            self::FINANCE,
            self::PURCHASING,
        ];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::all(), true);
    }

    public static function assertValid(string $value): void
    {
        if (! self::isValid($value)) {
            throw new InvalidArgumentException("Invalid user role: {$value}");
        }
    }
}

