<?php

namespace App\Services\Accurate;

use RuntimeException;

final class AccurateException extends RuntimeException
{
    public static function integration(string $message): self
    {
        return new self($message);
    }

    public static function invalidResponse(string $message): self
    {
        return new self($message);
    }

    public static function mapping(string $message): self
    {
        return new self($message);
    }
}

