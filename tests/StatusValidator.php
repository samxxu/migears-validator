<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests;

use MiGears\Validator\ValidatorInterface;

/**
 * Test-only validator whose alias (`status`) is registered by exactly one
 * test, so the "register returns false for a new alias" assertion stays
 * deterministic regardless of test ordering.
 */
final class StatusValidator implements ValidatorInterface
{
    public function validate(mixed $value): bool
    {
        return $value === 'open';
    }

    public function getErrorCode(): string
    {
        return 'status';
    }

    public function getErrorParams(): array
    {
        return [];
    }
}