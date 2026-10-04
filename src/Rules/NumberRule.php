<?php

declare(strict_types=1);

namespace MiGears\Validator\Rules;

use MiGears\Validator\RuleInterface;

final class NumberRule implements RuleInterface
{
    public function validate(mixed $value): bool
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return true;
        }

        return is_numeric($value);
    }

    public function getErrorCode(): string
    {
        return 'number';
    }

    public function getErrorParams(): array
    {
        return [];
    }
}
