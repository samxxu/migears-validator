<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests;

use PHPUnit\Framework\TestCase;
use MiGears\Validator\Validator;
use MiGears\Validator\Rules\AlphaNumericRule;
use MiGears\Validator\Rules\AlphaRule;
use MiGears\Validator\Rules\ContainUrlRule;
use MiGears\Validator\Rules\DateRangeRule;
use MiGears\Validator\Rules\DateRule;
use MiGears\Validator\Rules\DateTimeRangeRule;
use MiGears\Validator\Rules\EnumRule;
use MiGears\Validator\Rules\EqualsRule;
use MiGears\Validator\Rules\GreaterOrEqualThanRule;
use MiGears\Validator\Rules\GreaterThanRule;
use MiGears\Validator\Rules\IpAddressRule;
use MiGears\Validator\Rules\LessOrEqualThanRule;
use MiGears\Validator\Rules\LessThanRule;
use MiGears\Validator\Rules\MoneyRule;
use MiGears\Validator\Rules\NumberRule;
use MiGears\Validator\Rules\TimeRangeRule;
use MiGears\Validator\Rules\TimeRule;

final class GenericRulesTest extends TestCase
{
    public function testDateRule(): void
    {
        $rule = new DateRule();
        self::assertTrue($rule->validate('2026-09-19'));
        self::assertTrue($rule->validate('2026-9-9'));
        self::assertFalse($rule->validate('2026-13-40'));
        self::assertFalse($rule->validate('2026-02-30'));
        self::assertFalse($rule->validate('2026/09/19'));
        self::assertFalse($rule->validate('not-a-date'));
        self::assertFalse($rule->validate("2026-09-19\n"));
        self::assertTrue($rule->validate(''));
        self::assertSame('date', $rule->getErrorCode());
    }

    public function testDateRangeRule(): void
    {
        $both = new DateRangeRule('2026-09-01', '2026-09-30');
        self::assertTrue($both->validate('2026-09-01'));
        self::assertTrue($both->validate('2026-09-15'));
        self::assertTrue($both->validate('2026-09-30'));
        self::assertFalse($both->validate('2026-08-31'));
        self::assertFalse($both->validate('2026-10-01'));
        self::assertSame('dateRange', $both->getErrorCode());
        self::assertSame(['min' => '2026-09-01', 'max' => '2026-09-30'], $both->getErrorParams());

        // Only one bound is required.
        $minOnly = new DateRangeRule(min: '2026-09-01');
        self::assertTrue($minOnly->validate('2026-09-01'));
        self::assertTrue($minOnly->validate('2030-01-01'));
        self::assertFalse($minOnly->validate('2026-08-31'));

        $maxOnly = new DateRangeRule(max: '2026-09-30');
        self::assertTrue($maxOnly->validate('1999-01-01'));
        self::assertTrue($maxOnly->validate('2026-09-30'));
        self::assertFalse($maxOnly->validate('2026-10-01'));
    }

    public function testDateRangeRuleComparesActualDateNotString(): void
    {
        // The accepted format is not zero-padded, so '2026-9-9' must compare as 2026-09-09.
        $rule = new DateRangeRule('2026-9-5', '2026-10-01');
        self::assertTrue($rule->validate('2026-9-5'));
        self::assertTrue($rule->validate('2026-09-30'));
        self::assertFalse($rule->validate('2026-9-4'));
        self::assertFalse($rule->validate('2026-10-02'));
    }

    public function testDateRangeRuleSkipsEmptyAndRejectsMalformedValues(): void
    {
        $rule = new DateRangeRule('2026-09-01', '2026-09-30');
        self::assertTrue($rule->validate(null));
        self::assertTrue($rule->validate(''));
        self::assertTrue($rule->validate('  '));
        self::assertFalse($rule->validate('2026-02-30'));
        self::assertFalse($rule->validate('2026-13-01'));
        self::assertFalse($rule->validate('2026/09/19'));
        self::assertFalse($rule->validate("2026-09-19\n"));
        self::assertFalse($rule->validate(20260919));
    }

    public function testDateRangeRuleRejectsInvalidConfiguration(): void
    {
        $cases = [
            'no bound at all' => static fn () => new DateRangeRule(),
            'malformed min' => static fn () => new DateRangeRule('2026-02-30'),
            'malformed max' => static fn () => new DateRangeRule(null, 'abc'),
            'reversed bounds' => static fn () => new DateRangeRule('2026-09-30', '2026-09-01'),
        ];

        $accepted = [];

        foreach ($cases as $label => $build) {
            try {
                $build();
                $accepted[] = $label;
            } catch (\InvalidArgumentException) {
                // expected
            }
        }

        self::assertSame([], $accepted, 'These configurations should have been rejected');
    }

    public function testDateRangeRuleThroughValidatorClass(): void
    {
        $validator = new Validator();
        $rules = ['issued' => ['dateRange' => ['min' => '2026-09-01', 'max' => '2026-09-30']]];

        self::assertTrue($validator->passes(['issued' => '2026-09-15'], $rules));

        $errors = $validator->validate(['issued' => '2026-08-31'], $rules);
        self::assertSame('dateRange', $errors['issued']['rule']);
        self::assertSame(
            ['min' => '2026-09-01', 'max' => '2026-09-30'],
            $errors['issued']['params']
        );

        // Scalar config lands on the first parameter, so it configures `min`.
        self::assertTrue($validator->passes(
            ['issued' => '2026-09-15'],
            ['issued' => ['dateRange' => '2026-09-01']]
        ));
    }

    public function testDateTimeRangeRule(): void
    {
        $both = new DateTimeRangeRule('2026-09-01 09:00', '2026-09-30 18:00');
        self::assertTrue($both->validate('2026-09-01 09:00'));
        self::assertTrue($both->validate('2026-09-15 12:30:45'));
        self::assertTrue($both->validate('2026-09-30 18:00'));
        self::assertFalse($both->validate('2026-09-01 08:59'));
        self::assertFalse($both->validate('2026-09-30 18:01'));
        self::assertSame('dateTimeRange', $both->getErrorCode());
        self::assertSame(
            ['min' => '2026-09-01 09:00', 'max' => '2026-09-30 18:00'],
            $both->getErrorParams()
        );

        // Only one bound is required.
        $minOnly = new DateTimeRangeRule(min: '2026-09-01 09:00');
        self::assertTrue($minOnly->validate('2026-09-01 09:00'));
        self::assertTrue($minOnly->validate('2030-01-01 00:00'));
        self::assertFalse($minOnly->validate('2026-09-01 08:59'));

        $maxOnly = new DateTimeRangeRule(max: '2026-09-30 18:00');
        self::assertTrue($maxOnly->validate('1999-01-01 00:00'));
        self::assertTrue($maxOnly->validate('2026-09-30 18:00'));
        self::assertFalse($maxOnly->validate('2026-09-30 18:01'));
    }

    public function testDateTimeRangeRuleComparesDateAndTimeTogether(): void
    {
        // A window that spans a day boundary must be ordered by the full
        // date-time, not by the date and the time separately.
        $rule = new DateTimeRangeRule('2026-09-30 18:00', '2026-10-01 09:00');
        self::assertTrue($rule->validate('2026-09-30 18:00'));
        self::assertTrue($rule->validate('2026-09-30 20:00'));
        self::assertTrue($rule->validate('2026-10-01 09:00'));
        self::assertFalse($rule->validate('2026-09-30 17:59'));
        self::assertFalse($rule->validate('2026-10-01 09:01'));

        // Neither part is zero-padded, so '2026-9-9 9:5' must compare as 2026-09-09 09:05.
        $loose = new DateTimeRangeRule('2026-9-9 9:5', '2026-9-9 10:00');
        self::assertTrue($loose->validate('2026-9-9 9:5'));
        self::assertTrue($loose->validate('2026-9-9 9:05:30'));
        self::assertFalse($loose->validate('2026-09-09 09:04'));
    }

    public function testDateTimeRangeRuleSkipsEmptyAndRejectsMalformedValues(): void
    {
        $rule = new DateTimeRangeRule('2026-09-01 09:00', '2026-09-30 18:00');
        self::assertTrue($rule->validate(null));
        self::assertTrue($rule->validate(''));
        self::assertTrue($rule->validate('  '));
        self::assertFalse($rule->validate('2026-02-30 10:00'));
        self::assertFalse($rule->validate('2026-09-19 24:00'));
        self::assertFalse($rule->validate('2026-09-19'));
        self::assertFalse($rule->validate('10:30'));
        self::assertFalse($rule->validate('2026-09-19T10:30'));
        self::assertFalse($rule->validate('2026-09-19  10:30'));
        self::assertFalse($rule->validate("2026-09-19 10:30\n"));
        self::assertFalse($rule->validate(20260919));
    }

    public function testDateTimeRangeRuleRejectsInvalidConfiguration(): void
    {
        $cases = [
            'no bound at all' => static fn () => new DateTimeRangeRule(),
            'malformed min' => static fn () => new DateTimeRangeRule('2026-02-30 10:00'),
            'malformed max' => static fn () => new DateTimeRangeRule(null, 'abc'),
            'reversed bounds' => static fn () => new DateTimeRangeRule(
                '2026-09-30 18:00',
                '2026-09-30 09:00'
            ),
        ];

        $accepted = [];

        foreach ($cases as $label => $build) {
            try {
                $build();
                $accepted[] = $label;
            } catch (\InvalidArgumentException) {
                // expected
            }
        }

        self::assertSame([], $accepted, 'These configurations should have been rejected');
    }

    public function testDateTimeRangeRuleThroughValidatorClass(): void
    {
        $validator = new Validator();
        $rules = ['published' => [
            'dateTimeRange' => ['min' => '2026-09-01 09:00', 'max' => '2026-09-30 18:00'],
        ]];

        self::assertTrue($validator->passes(['published' => '2026-09-15 12:00'], $rules));

        $errors = $validator->validate(['published' => '2026-09-30 18:01'], $rules);
        self::assertSame('dateTimeRange', $errors['published']['rule']);
        self::assertSame(
            ['min' => '2026-09-01 09:00', 'max' => '2026-09-30 18:00'],
            $errors['published']['params']
        );

        // Scalar config lands on the first parameter, so it configures `min`.
        self::assertTrue($validator->passes(
            ['published' => '2026-09-15 12:00'],
            ['published' => ['dateTimeRange' => '2026-09-01 09:00']]
        ));
    }

    public function testTimeRule(): void
    {
        $rule = new TimeRule();
        self::assertTrue($rule->validate('10:30'));
        self::assertTrue($rule->validate('10:30:45'));
        self::assertFalse($rule->validate('24:00'));
        self::assertFalse($rule->validate('10:60'));
        self::assertFalse($rule->validate('10:30:99'));
        self::assertFalse($rule->validate('10-30'));
        self::assertFalse($rule->validate("10:30\n"));
        self::assertSame('time', $rule->getErrorCode());
    }

    public function testTimeRangeRule(): void
    {
        $both = new TimeRangeRule('09:00', '18:00');
        self::assertTrue($both->validate('09:00'));
        self::assertTrue($both->validate('12:30'));
        self::assertTrue($both->validate('18:00'));
        self::assertFalse($both->validate('08:59'));
        self::assertFalse($both->validate('18:01'));
        self::assertSame('timeRange', $both->getErrorCode());
        self::assertSame(['min' => '09:00', 'max' => '18:00'], $both->getErrorParams());

        // Only one bound is required.
        $minOnly = new TimeRangeRule(min: '09:00');
        self::assertTrue($minOnly->validate('09:00'));
        self::assertTrue($minOnly->validate('23:59'));
        self::assertFalse($minOnly->validate('08:59'));

        $maxOnly = new TimeRangeRule(max: '18:00');
        self::assertTrue($maxOnly->validate('00:00'));
        self::assertTrue($maxOnly->validate('18:00'));
        self::assertFalse($maxOnly->validate('18:01'));
    }

    public function testTimeRangeRuleComparesActualTimeNotString(): void
    {
        // The accepted format is not zero-padded, so '9:5' must compare as 09:05.
        $rule = new TimeRangeRule('9:05', '10:00');
        self::assertTrue($rule->validate('9:5'));
        self::assertTrue($rule->validate('09:30'));
        self::assertFalse($rule->validate('9:04'));

        $withSeconds = new TimeRangeRule('09:00:00', '09:00:30');
        self::assertTrue($withSeconds->validate('09:00:30'));
        self::assertFalse($withSeconds->validate('09:00:31'));
    }

    public function testTimeRangeRuleSkipsEmptyAndRejectsMalformedValues(): void
    {
        $rule = new TimeRangeRule('09:00', '18:00');
        self::assertTrue($rule->validate(null));
        self::assertTrue($rule->validate(''));
        self::assertTrue($rule->validate('  '));
        self::assertFalse($rule->validate('24:00'));
        self::assertFalse($rule->validate('10:60'));
        self::assertFalse($rule->validate('10-30'));
        self::assertFalse($rule->validate("10:30\n"));
        self::assertFalse($rule->validate(1030));
    }

    public function testTimeRangeRuleRejectsInvalidConfiguration(): void
    {
        $cases = [
            'no bound at all' => static fn () => new TimeRangeRule(),
            'malformed min' => static fn () => new TimeRangeRule('25:00'),
            'malformed max' => static fn () => new TimeRangeRule(null, 'abc'),
            'crossing midnight' => static fn () => new TimeRangeRule('22:00', '06:00'),
        ];

        $accepted = [];

        foreach ($cases as $label => $build) {
            try {
                $build();
                $accepted[] = $label;
            } catch (\InvalidArgumentException) {
                // expected
            }
        }

        self::assertSame([], $accepted, 'These configurations should have been rejected');
    }

    public function testTimeRangeRuleThroughValidatorClass(): void
    {
        $validator = new Validator();
        $rules = ['slot' => ['timeRange' => ['min' => '09:00', 'max' => '18:00']]];

        self::assertTrue($validator->passes(['slot' => '12:00'], $rules));

        $errors = $validator->validate(['slot' => '08:00'], $rules);
        self::assertSame('timeRange', $errors['slot']['rule']);
        self::assertSame(['min' => '09:00', 'max' => '18:00'], $errors['slot']['params']);

        // Scalar config lands on the first parameter, so it configures `min`.
        self::assertTrue($validator->passes(['slot' => '12:00'], ['slot' => ['timeRange' => '09:00']]));

        // No bound at all stays a loud failure rather than a silent no-op.
        $this->expectException(\InvalidArgumentException::class);
        $validator->validate(['slot' => '12:00'], ['slot' => ['timeRange' => true]]);
    }

    public function testNumberRule(): void
    {
        $rule = new NumberRule();
        self::assertTrue($rule->validate('123'));
        self::assertTrue($rule->validate('12.5'));
        self::assertTrue($rule->validate('1e3'));
        self::assertTrue($rule->validate('+3'));
        self::assertTrue($rule->validate(-3));
        self::assertFalse($rule->validate('abc'));
        self::assertSame('number', $rule->getErrorCode());
    }

    public function testMoneyRule(): void
    {
        $rule = new MoneyRule();
        self::assertTrue($rule->validate('0'));
        self::assertTrue($rule->validate('10'));
        self::assertTrue($rule->validate('10.5'));
        self::assertTrue($rule->validate('0.5'));
        self::assertFalse($rule->validate('10.555'));
        self::assertFalse($rule->validate('abc'));
        self::assertFalse($rule->validate("10.50\n"));
        self::assertSame('money', $rule->getErrorCode());
    }

    public function testEnumRule(): void
    {
        $rule = new EnumRule('ACCEPTED|REJECTED|FINAL');
        self::assertTrue($rule->validate('ACCEPTED'));
        self::assertTrue($rule->validate('FINAL'));
        self::assertFalse($rule->validate('PENDING'));
        self::assertSame('enum', $rule->getErrorCode());
        self::assertSame(['allowed' => 'ACCEPTED|REJECTED|FINAL'], $rule->getErrorParams());

        $arrayRule = new EnumRule(['A', 'B']);
        self::assertTrue($arrayRule->validate('A'));
        self::assertFalse($arrayRule->validate('C'));

        $intRule = new EnumRule([1, 2]);
        self::assertTrue($intRule->validate(1));
        self::assertFalse($intRule->validate(3));
    }

    public function testEnumPipeStringPreservesZeroValue(): void
    {
        // A bare array_filter() drops the falsy string "0", so '0|1' silently
        // allowed only '1'. The pipe form must agree with the list form.
        $pipe = new EnumRule('0|1');
        $list = new EnumRule(['0', '1']);

        self::assertSame($list->getErrorParams(), $pipe->getErrorParams());
        self::assertTrue($pipe->validate('0'));
        self::assertTrue($pipe->validate('1'));
        self::assertFalse($pipe->validate('2'));

        // Empty pipe segments are still ignored.
        $gaps = new EnumRule('A||B');
        self::assertSame(['allowed' => 'A|B'], $gaps->getErrorParams());
    }

    public function testEqualsRule(): void
    {
        $rule = new EqualsRule('wx-app-id');
        self::assertTrue($rule->validate('wx-app-id'));
        self::assertFalse($rule->validate('other'));
        self::assertTrue($rule->validate(''));
        self::assertSame('equals', $rule->getErrorCode());
    }

    public function testEqualsRuleIsStrict(): void
    {
        $rule = new EqualsRule('1');
        self::assertTrue($rule->validate('1'));
        self::assertFalse($rule->validate(1));
    }

    public function testAlphaRule(): void
    {
        $rule = new AlphaRule();
        self::assertTrue($rule->validate('abcXYZ'));
        self::assertFalse($rule->validate('abc123'));
        self::assertFalse($rule->validate('abc 123'));
        self::assertFalse($rule->validate("abc\n"));
        self::assertTrue($rule->validate(''));
        self::assertSame('alpha', $rule->getErrorCode());
    }

    public function testAlphaNumericRule(): void
    {
        $rule = new AlphaNumericRule();
        self::assertTrue($rule->validate('abc123'));
        self::assertFalse($rule->validate('abc 123'));
        self::assertFalse($rule->validate("abc123\n"));
        self::assertTrue($rule->validate(''));
        self::assertSame('alphaNumeric', $rule->getErrorCode());
    }

    public function testIpAddressRule(): void
    {
        $rule = new IpAddressRule();
        self::assertTrue($rule->validate('192.168.1.1'));
        self::assertTrue($rule->validate('2001:db8::1'));
        self::assertFalse($rule->validate('999.999.1.1'));
        self::assertTrue($rule->validate(''));
        self::assertSame('ipAddress', $rule->getErrorCode());
    }

    public function testComparisonRules(): void
    {
        $greater = new GreaterThanRule(100);
        self::assertTrue($greater->validate(101));
        self::assertFalse($greater->validate(100));
        self::assertSame('greaterThan', $greater->getErrorCode());

        $greaterOrEqual = new GreaterOrEqualThanRule(100);
        self::assertTrue($greaterOrEqual->validate(100));
        self::assertFalse($greaterOrEqual->validate(99));
        self::assertSame('greaterOrEqualThan', $greaterOrEqual->getErrorCode());

        $less = new LessThanRule(100);
        self::assertTrue($less->validate(99));
        self::assertFalse($less->validate(100));
        self::assertSame('lessThan', $less->getErrorCode());

        $lessOrEqual = new LessOrEqualThanRule(100);
        self::assertTrue($lessOrEqual->validate(100));
        self::assertFalse($lessOrEqual->validate(101));
        self::assertSame('lessOrEqualThan', $lessOrEqual->getErrorCode());
    }

    public function testContainUrlRule(): void
    {
        $rule = new ContainUrlRule();
        self::assertTrue($rule->validate('visit https://example.com now'));
        self::assertFalse($rule->validate('no url here'));
        self::assertSame('containUrl', $rule->getErrorCode());

        $inverted = new ContainUrlRule(true);
        self::assertTrue($inverted->validate('no url here'));
        self::assertFalse($inverted->validate('https://example.com'));
    }

    public function testNewRulesWorkThroughValidatorClass(): void
    {
        $validator = new Validator();
        $errors = $validator->validate(
            ['start' => '2026/13/40', 'amount' => '12.5', 'status' => 'PENDING', 'score' => '101'],
            [
                'start' => ['date' => true],
                'amount' => ['money' => true],
                'status' => ['enum' => 'ACCEPTED|REJECTED'],
                'score' => ['lessOrEqualThan' => 100],
            ]
        );
        self::assertArrayHasKey('start', $errors);
        self::assertSame('date', $errors['start']['rule']);
        self::assertArrayHasKey('status', $errors);
        self::assertSame('enum', $errors['status']['rule']);
        self::assertArrayHasKey('score', $errors);
        self::assertSame('lessOrEqualThan', $errors['score']['rule']);
        self::assertArrayNotHasKey('amount', $errors);
    }
}
