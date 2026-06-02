<?php

namespace App\Support\Enums;

use InvalidArgumentException;

final class DocumentTypes
{
    public const PO = 'po';
    public const PR = 'pr';

    public static function all(): array
    {
        return [
            self::PO,
            self::PR,
        ];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::all(), true);
    }

    public static function assertValid(string $value): void
    {
        if (! self::isValid($value)) {
            throw new InvalidArgumentException("Invalid document type: {$value}");
        }
    }
}

