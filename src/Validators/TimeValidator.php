<?php

declare(strict_types=1);

namespace MiGears\Validator\Validators;

use MiGears\Validator\ValidatorInterface;

final class TimeValidator implements ValidatorInterface
{
    public function validate(mixed $value): bool
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        if (preg_match('/^(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?$/D', $value, $m) !== 1) {
            return false;
        }

        $second = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : null;

        return (int) $m[1] <= 23
            && (int) $m[2] <= 59
            && ($second === null || $second <= 59);
    }

    public function getErrorCode(): string
    {
        return 'time';
    }

    public function getErrorParams(): array
    {
        return [];
    }
}
