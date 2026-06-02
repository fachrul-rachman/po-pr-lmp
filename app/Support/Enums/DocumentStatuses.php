<?php

namespace App\Support\Enums;

use InvalidArgumentException;

final class DocumentStatuses
{
    public const WAREHOUSE_SUBMITTED = 'warehouse_submitted';
    public const SPV_APPROVED = 'spv_approved';
    public const SPV_REJECTED = 'spv_rejected';
    public const FINANCE_REJECTED = 'finance_rejected';
    public const FINANCE_CLOSED = 'finance_closed';

    public static function all(): array
    {
        return [
            self::WAREHOUSE_SUBMITTED,
            self::SPV_APPROVED,
            self::SPV_REJECTED,
            self::FINANCE_REJECTED,
            self::FINANCE_CLOSED,
        ];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::all(), true);
    }

    public static function assertValid(string $value): void
    {
        if (! self::isValid($value)) {
            throw new InvalidArgumentException("Invalid document status: {$value}");
        }
    }
}

