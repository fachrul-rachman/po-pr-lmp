<?php

namespace App\Support\Enums;

use InvalidArgumentException;

final class ActorRoles
{
    public const SYSTEM = 'system';

    public static function all(): array
    {
        return array_merge(UserRoles::all(), [self::SYSTEM]);
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::all(), true);
    }

    public static function assertValid(string $value): void
    {
        if (! self::isValid($value)) {
            throw new InvalidArgumentException("Invalid actor role: {$value}");
        }
    }
}

