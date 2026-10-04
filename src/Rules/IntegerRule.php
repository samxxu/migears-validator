<?php

declare(strict_types=1);

namespace MiGears\Validator\Rules;

use MiGears\Validator\RuleInterface;

final class IntegerRule implements RuleInterface
{
    public function validate(mixed $value): bool
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return true;
        }

        if (is_int($value)) {
            return true;
        }

        if (is_string($value)) {
            return preg_match('/^-?\d+$/D', $value) === 1;
        }

        return false;
    }

    public function getErrorCode(): string
    {
        return 'integer';
    }

    public function getErrorParams(): array
    {
        return [];
    }
}
