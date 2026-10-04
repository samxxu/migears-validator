<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests;

use MiGears\Validator\RuleInterface;

final class SecretRule implements RuleInterface
{
    public function validate(mixed $value): bool
    {
        return $value === 'secret';
    }

    public function getErrorCode(): string
    {
        return 'secret';
    }

    public function getErrorParams(): array
    {
        return [];
    }
}