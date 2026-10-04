<?php

declare(strict_types=1);

namespace MiGears\Validator\Rules;

use MiGears\Validator\RuleInterface;

final class AlphaRule implements RuleInterface
{
    public function validate(mixed $value): bool
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        return preg_match('/^[a-zA-Z]+$/D', $value) === 1;
    }

    public function getErrorCode(): string
    {
        return 'alpha';
    }

    public function getErrorParams(): array
    {
        return [];
    }
}
