<?php

declare(strict_types=1);

namespace MiGears\Validator\Validators;

use MiGears\Validator\ValidatorInterface;

final class AlphaNumericValidator implements ValidatorInterface
{
    public function validate(mixed $value): bool
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        return preg_match('/^[A-Za-z0-9]+$/D', $value) === 1;
    }

    public function getErrorCode(): string
    {
        return 'alphaNumeric';
    }

    public function getErrorParams(): array
    {
        return [];
    }
}
