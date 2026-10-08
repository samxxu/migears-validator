<?php

declare(strict_types=1);

namespace MiGears\Validator\Rules;

use MiGears\Validator\RuleInterface;

final class TimeRangeRule implements RuleInterface
{
    private readonly ?int $minSeconds;

    private readonly ?int $maxSeconds;

    /**
     * At least one bound is required. A bound that is not a valid time, or a
     * `min` later than `max`, fails loudly at construction — a silently dropped
     * or inverted bound would widen the range instead of narrowing it.
     */
    public function __construct(
        private readonly ?string $min = null,
        private readonly ?string $max = null,
    ) {
        if ($this->min === null && $this->max === null) {
            throw new \InvalidArgumentException(
                'TimeRangeRule requires at least one of "min" or "max".'
            );
        }

        $this->minSeconds = $this->boundSeconds($this->min, 'min');
        $this->maxSeconds = $this->boundSeconds($this->max, 'max');

        if ($this->minSeconds !== null
            && $this->maxSeconds !== null
            && $this->minSeconds > $this->maxSeconds) {
            throw new \InvalidArgumentException(sprintf(
                'TimeRangeRule requires "min" (%s) to not be later than "max" (%s); '
                . 'a range crossing midnight is not supported.',
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

        $seconds = self::secondsOfDay($value);
        if ($seconds === null) {
            return false;
        }

        return ($this->minSeconds === null || $seconds >= $this->minSeconds)
            && ($this->maxSeconds === null || $seconds <= $this->maxSeconds);
    }

    public function getErrorCode(): string
    {
        return 'timeRange';
    }

    public function getErrorParams(): array
    {
        return ['min' => $this->min, 'max' => $this->max];
    }

    private function boundSeconds(?string $bound, string $name): ?int
    {
        if ($bound === null) {
            return null;
        }

        $seconds = self::secondsOfDay($bound);
        if ($seconds === null) {
            throw new \InvalidArgumentException(sprintf(
                'TimeRangeRule "%s" must be a time in H:M(:S) format, got %s.',
                $name,
                var_export($bound, true)
            ));
        }

        return $seconds;
    }

    /**
     * Resolve a time to its second of the day, or null when it is not a valid
     * time. Comparison happens on these integers rather than on the strings:
     * the accepted format is not zero-padded (`9:5`), so a lexicographic
     * comparison would order values wrongly.
     */
    private static function secondsOfDay(mixed $value): ?int
    {
        if (!is_string($value)) {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?$/D', $value, $m) !== 1) {
            return null;
        }

        $hour = (int) $m[1];
        $minute = (int) $m[2];
        $second = isset($m[3]) ? (int) $m[3] : 0;

        if ($hour > 23 || $minute > 59 || $second > 59) {
            return null;
        }

        return $hour * 3600 + $minute * 60 + $second;
    }
}
