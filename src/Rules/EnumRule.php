<?php

declare(strict_types=1);

namespace MiGears\Validator\Rules;

use MiGears\Validator\RuleInterface;

final class EnumRule implements RuleInterface
{
    /** @var string[] */
    private array $allowed;

    /**
     * @param string|list<int|string> $allowed Pipe-separated values or a list of allowed values
     */
    public function __construct(string|array $allowed = [])
    {
        $this->allowed = array_map('strval', is_array($allowed)
            ? $allowed
            : array_filter(explode('|', $allowed), static fn (string $value): bool => $value !== ''));
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
