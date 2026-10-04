<?php

declare(strict_types=1);

namespace MiGears\Validator\Rules;

use MiGears\Validator\RuleInterface;

final class IpAddressRule implements RuleInterface
{
    public function validate(mixed $value): bool
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    public function getErrorCode(): string
    {
        return 'ipAddress';
    }

    public function getErrorParams(): array
    {
        return [];
    }
}
