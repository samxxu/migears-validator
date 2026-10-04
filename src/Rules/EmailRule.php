<?php

declare(strict_types=1);

namespace MiGears\Validator\Rules;

use MiGears\Validator\RuleInterface;

final class EmailRule implements RuleInterface
{
    public function validate(mixed $value): bool
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function getErrorCode(): string
    {
        return 'email';
    }

    public function getErrorParams(): array
    {
        return [];
    }
}
