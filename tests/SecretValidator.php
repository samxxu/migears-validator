<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests;

use MiGears\Validator\ValidatorInterface;

final class SecretValidator implements ValidatorInterface
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