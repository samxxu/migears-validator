<?php

declare(strict_types=1);

namespace MiGears\Validator\Rules;

use MiGears\Validator\RuleInterface;

final class LessOrEqualThanRule implements RuleInterface
{
    public function __construct(
        private readonly int|float $threshold = 0,
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

        return (float) $value <= $this->threshold;
    }

    public function getErrorCode(): string
    {
        return 'lessOrEqualThan';
    }

    public function getErrorParams(): array
    {
        return ['threshold' => $this->threshold];
    }
}
