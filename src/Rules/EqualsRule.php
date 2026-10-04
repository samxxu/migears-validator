<?php

declare(strict_types=1);

namespace MiGears\Validator\Rules;

use MiGears\Validator\RuleInterface;

final class EqualsRule implements RuleInterface
{
    public function __construct(
        private readonly mixed $expected = null,
    ) {
    }

    public function validate(mixed $value): bool
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return true;
        }

        return $value === $this->expected;
    }

    public function getErrorCode(): string
    {
        return 'equals';
    }

    public function getErrorParams(): array
    {
        return ['expected' => $this->expected];
    }
}
