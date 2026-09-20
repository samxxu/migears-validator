<?php

declare(strict_types=1);

namespace MiGears\Validator\Validators;

use MiGears\Validator\ValidatorInterface;

final class NumberValidator implements ValidatorInterface
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
