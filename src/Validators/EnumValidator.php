<?php

declare(strict_types=1);

namespace MiGears\Validator\Validators;

use MiGears\Validator\ValidatorInterface;

final class EnumValidator implements ValidatorInterface
{
    /** @var string[] */
    private array $allowed;

    public function __construct(string|array $allowed = [])
    {
        $this->allowed = array_map('strval', is_array($allowed)
            ? $allowed
            : array_filter(explode('|', $allowed)));
    }

    public function validate(mixed $value): bool
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return true;
        }

        if (!is_scalar($value)) {
            return false;
        }

        return in_array((string) $value, $this->allowed, true);
    }

    public function getErrorCode(): string
    {
        return 'enum';
    }

    public function getErrorParams(): array
    {
        return ['allowed' => implode('|', $this->allowed)];
    }
}
