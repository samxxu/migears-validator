<?php

declare(strict_types=1);

namespace MiGears\Validator\Validators;

use MiGears\Validator\ValidatorInterface;

final class PatternValidator implements ValidatorInterface
{
    public function __construct(
        private readonly string $pattern = '//',
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

        return preg_match($this->pattern, $value) === 1;
    }

    public function getErrorCode(): string
    {
        return 'pattern';
    }

    public function getErrorParams(): array
    {
        return ['pattern' => $this->pattern];
    }
}
