<?php

namespace App\Support\Enums;

use InvalidArgumentException;

final class ItemMatchStatuses
{
    public const SESUAI = 'sesuai';
    public const TIDAK_SESUAI = 'tidak_sesuai';

    public static function all(): array
    {
        return [
            self::SESUAI,
            self::TIDAK_SESUAI,
        ];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::all(), true);
    }

    public static function assertValid(string $value): void
    {
        if (! self::isValid($value)) {
            throw new InvalidArgumentException("Invalid item match status: {$value}");
        }
    }
}

