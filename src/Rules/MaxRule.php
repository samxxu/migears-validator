<?php

declare(strict_types=1);

namespace MiGears\Validator\Rules;

use MiGears\Validator\RuleInterface;

final class MaxRule implements RuleInterface
{
    public function __construct(
        private readonly int|float $max = 0,
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

        return (float) $value <= $this->max;
    }

    public function getErrorCode(): string
    {
        return 'max';
    }

    public function getErrorParams(): array
    {
        return ['max' => $this->max];
    }
}
