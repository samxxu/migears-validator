<?php

declare(strict_types=1);

namespace MiGears\Validator\Rules;

use MiGears\Validator\RuleInterface;

final class RequiredRule implements RuleInterface
{
    public function validate(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return $value !== [];
        }

        return true;
    }

    public function getErrorCode(): string
    {
        return 'required';
    }

    public function getErrorParams(): array
    {
        return [];
    }
}
