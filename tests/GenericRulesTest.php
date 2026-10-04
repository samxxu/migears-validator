<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests;

use PHPUnit\Framework\TestCase;
use MiGears\Validator\Validator;
use MiGears\Validator\Rules\AlphaNumericRule;
use MiGears\Validator\Rules\AlphaRule;
use MiGears\Validator\Rules\ContainUrlRule;
use MiGears\Validator\Rules\DateRule;
use MiGears\Validator\Rules\EnumRule;
use MiGears\Validator\Rules\EqualsRule;
use MiGears\Validator\Rules\GreaterOrEqualThanRule;
use MiGears\Validator\Rules\GreaterThanRule;
use MiGears\Validator\Rules\IpAddressRule;
use MiGears\Validator\Rules\LessOrEqualThanRule;
use MiGears\Validator\Rules\LessThanRule;
use MiGears\Validator\Rules\MoneyRule;
use MiGears\Validator\Rules\NumberRule;
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
