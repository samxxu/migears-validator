<?php

declare(strict_types=1);

namespace MiGears\Validator\Rules;

use MiGears\Validator\RuleInterface;

final class ContainUrlRule implements RuleInterface
{
    public function __construct(
        private readonly bool $invert = false,
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

        $contains = preg_match('#http(s)?://([\w\-]+\.)+[\w\-]+(:[0-9]{1,5})?(/[\w\- ./?%&=]*)?#i', $value) === 1;
        return $this->invert ? !$contains : $contains;
    }

    public function getErrorCode(): string
    {
        return 'containUrl';
    }

    public function getErrorParams(): array
    {
        return ['invert' => $this->invert];
    }
}
