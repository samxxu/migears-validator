<?php

declare(strict_types=1);

namespace MiGears\Validator\Validators;

use MiGears\Validator\ValidatorInterface;

final class DateValidator implements ValidatorInterface
{
    public function validate(mixed $value): bool
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        if (preg_match('/^(\d{1,4})-(\d{1,2})-(\d{1,2})$/D', $value, $m) !== 1) {
            return false;
        }

        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }

    public function getErrorCode(): string
    {
        return 'date';
    }

    public function getErrorParams(): array
    {
        return [];
    }
}
