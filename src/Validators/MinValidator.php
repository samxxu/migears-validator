<?php

declare(strict_types=1);

namespace MiGears\Validator\Validators;

use MiGears\Validator\ValidatorInterface;

final class MinValidator implements ValidatorInterface
{
    public function __construct(
        private readonly int|float $min = 0,
    ) {
    }

    public function validate(mixed $value): bool
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return true;
        }

        if (!is_numeric($value)) {
            return false;
        }

        return (float) $value >= $this->min;
    }

    public function getErrorCode(): string
    {
        return 'min';
    }

    public function getErrorParams(): array
    {
        return ['min' => $this->min];
    }
}
