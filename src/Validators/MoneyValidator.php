<?php

declare(strict_types=1);

namespace MiGears\Validator\Validators;

use MiGears\Validator\ValidatorInterface;

final class MoneyValidator implements ValidatorInterface
{
    public function validate(mixed $value): bool
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return true;
        }

        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            return false;
        }

        return preg_match('/(^[1-9](\d+)?(\.\d{1,2})?$)|(^0$)|(^\d\.\d{1,2}$)/D', (string) $value) === 1;
    }

    public function getErrorCode(): string
    {
        return 'money';
    }

    public function getErrorParams(): array
    {
        return [];
    }
}
