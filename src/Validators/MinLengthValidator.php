<?php

declare(strict_types=1);

namespace MiGears\Validator\Validators;

use MiGears\Validator\ValidatorInterface;

final class MinLengthValidator implements ValidatorInterface
{
    public function __construct(
        private readonly int $min = 0,
    ) {
    }

    public function validate(mixed $value): bool
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        return mb_strlen($value) >= $this->min;
    }

    public function getErrorCode(): string
    {
        return 'minLength';
    }

    public function getErrorParams(): array
    {
        return ['min' => $this->min];
    }
}
