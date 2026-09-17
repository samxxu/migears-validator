<?php

declare(strict_types=1);

namespace MiGears\Validator\Validators;

use MiGears\Validator\ValidatorInterface;

final class IntegerValidator implements ValidatorInterface
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
            return preg_match('/^-?\d+$/', $value) === 1;
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
