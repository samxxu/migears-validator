<?php

declare(strict_types=1);

namespace MiGears\Validator\Rules;

use MiGears\Validator\RuleInterface;

final class DateRangeRule implements RuleInterface
{
    private readonly ?int $minOrdinal;

    private readonly ?int $maxOrdinal;

    /**
     * At least one bound is required. A bound that is not a real date, or a
     * `min` later than `max`, fails loudly at construction — a silently dropped
     * or inverted bound would widen the range instead of narrowing it.
     */
    public function __construct(
        private readonly ?string $min = null,
        private readonly ?string $max = null,
    ) {
        if ($this->min === null && $this->max === null) {
            throw new \InvalidArgumentException(
                'DateRangeRule requires at least one of "min" or "max".'
            );
        }

        $this->minOrdinal = $this->boundOrdinal($this->min, 'min');
        $this->maxOrdinal = $this->boundOrdinal($this->max, 'max');

        if ($this->minOrdinal !== null
            && $this->maxOrdinal !== null
            && $this->minOrdinal > $this->maxOrdinal) {
            throw new \InvalidArgumentException(sprintf(
                'DateRangeRule requires "min" (%s) to not be later than "max" (%s).',
                $this->min,
                $this->max
            ));
        }
    }

    public function validate(mixed $value): bool
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return true;
        }

        $ordinal = self::ordinal($value);
        if ($ordinal === null) {
            return false;
        }

        return ($this->minOrdinal === null || $ordinal >= $this->minOrdinal)
            && ($this->maxOrdinal === null || $ordinal <= $this->maxOrdinal);
    }

    public function getErrorCode(): string
    {
        return 'dateRange';
    }

    public function getErrorParams(): array
    {
        return ['min' => $this->min, 'max' => $this->max];
    }

    private function boundOrdinal(?string $bound, string $name): ?int
    {
        if ($bound === null) {
            return null;
        }

        $ordinal = self::ordinal($bound);
        if ($ordinal === null) {
            throw new \InvalidArgumentException(sprintf(
                'DateRangeRule "%s" must be a real date in YYYY-M-D format, got %s.',
                $name,
                var_export($bound, true)
            ));
        }

        return $ordinal;
    }

    /**
     * Resolve a date to a sortable YYYYMMDD ordinal, or null when it is not a
     * real date. Comparison happens on these integers rather than on the
     * strings: the accepted format is not zero-padded (`2026-9-9`), so a
     * lexicographic comparison would order dates wrongly.
     */
    private static function ordinal(mixed $value): ?int
    {
        if (!is_string($value)) {
            return null;
        }

        if (preg_match('/^(\d{1,4})-(\d{1,2})-(\d{1,2})$/D', $value, $m) !== 1) {
            return null;
        }

        $year = (int) $m[1];
        $month = (int) $m[2];
        $day = (int) $m[3];

        if (!checkdate($month, $day, $year)) {
            return null;
        }

        return $year * 10000 + $month * 100 + $day;
    }
}
