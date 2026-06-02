<?php

namespace App\Support\Enums;

use InvalidArgumentException;

final class DecisionTypes
{
    public const WAREHOUSE_SUBMIT = 'warehouse_submit';
    public const WAREHOUSE_RESUBMIT = 'warehouse_resubmit';
    public const SPV_APPROVE = 'spv_approve';
    public const SPV_REJECT = 'spv_reject';
    public const FINANCE_CLOSE = 'finance_close';
    public const FINANCE_REJECT = 'finance_reject';
    public const ADMIN_OVERRIDE = 'admin_override';
    public const ACCURATE_REFRESH = 'accurate_refresh';
    public const SYSTEM_STATUS_CHANGE = 'system_status_change';

    public static function all(): array
    {
        return [
            self::WAREHOUSE_SUBMIT,
            self::WAREHOUSE_RESUBMIT,
            self::SPV_APPROVE,
            self::SPV_REJECT,
            self::FINANCE_CLOSE,
            self::FINANCE_REJECT,
            self::ADMIN_OVERRIDE,
            self::ACCURATE_REFRESH,
            self::SYSTEM_STATUS_CHANGE,
        ];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::all(), true);
    }

    public static function assertValid(string $value): void
    {
        if (! self::isValid($value)) {
            throw new InvalidArgumentException("Invalid decision type: {$value}");
        }
    }
}

