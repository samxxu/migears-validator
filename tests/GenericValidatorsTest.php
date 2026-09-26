<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests;

use PHPUnit\Framework\TestCase;
use MiGears\Validator\Validator;
use MiGears\Validator\Validators\AlphaNumericValidator;
use MiGears\Validator\Validators\AlphaValidator;
use MiGears\Validator\Validators\ContainUrlValidator;
use MiGears\Validator\Validators\DateValidator;
use MiGears\Validator\Validators\EnumValidator;
use MiGears\Validator\Validators\EqualsValidator;
use MiGears\Validator\Validators\GreaterOrEqualThanValidator;
use MiGears\Validator\Validators\GreaterThanValidator;
use MiGears\Validator\Validators\IpAddressValidator;
use MiGears\Validator\Validators\LessOrEqualThanValidator;
use MiGears\Validator\Validators\LessThanValidator;
use MiGears\Validator\Validators\MoneyValidator;
use MiGears\Validator\Validators\NumberValidator;
use MiGears\Validator\Validators\TimeValidator;

final class GenericValidatorsTest extends TestCase
{
    public function testDateValidator(): void
    {
        $validator = new DateValidator();
        self::assertTrue($validator->validate('2026-09-19'));
        self::assertTrue($validator->validate('2026-9-9'));
        self::assertFalse($validator->validate('2026-13-40'));
        self::assertFalse($validator->validate('2026-02-30'));
        self::assertFalse($validator->validate('2026/09/19'));
        self::assertFalse($validator->validate('not-a-date'));
        self::assertFalse($validator->validate("2026-09-19\n"));
        self::assertTrue($validator->validate(''));
        self::assertSame('date', $validator->getErrorCode());
    }

    public function testTimeValidator(): void
    {
        $validator = new TimeValidator();
        self::assertTrue($validator->validate('10:30'));
        self::assertTrue($validator->validate('10:30:45'));
        self::assertFalse($validator->validate('24:00'));
        self::assertFalse($validator->validate('10:60'));
        self::assertFalse($validator->validate('10:30:99'));
        self::assertFalse($validator->validate('10-30'));
        self::assertFalse($validator->validate("10:30\n"));
        self::assertSame('time', $validator->getErrorCode());
    }

    public function testNumberValidator(): void
    {
        $validator = new NumberValidator();
        self::assertTrue($validator->validate('123'));
        self::assertTrue($validator->validate('12.5'));
        self::assertTrue($validator->validate('1e3'));
        self::assertTrue($validator->validate('+3'));
        self::assertTrue($validator->validate(-3));
        self::assertFalse($validator->validate('abc'));
        self::assertSame('number', $validator->getErrorCode());
    }

    public function testMoneyValidator(): void
    {
        $validator = new MoneyValidator();
        self::assertTrue($validator->validate('0'));
        self::assertTrue($validator->validate('10'));
        self::assertTrue($validator->validate('10.5'));
        self::assertTrue($validator->validate('0.5'));
        self::assertFalse($validator->validate('10.555'));
        self::assertFalse($validator->validate('abc'));
        self::assertFalse($validator->validate("10.50\n"));
        self::assertSame('money', $validator->getErrorCode());
    }

    public function testEnumValidator(): void
    {
        $validator = new EnumValidator('ACCEPTED|REJECTED|FINAL');
        self::assertTrue($validator->validate('ACCEPTED'));
        self::assertTrue($validator->validate('FINAL'));
        self::assertFalse($validator->validate('PENDING'));
        self::assertSame('enum', $validator->getErrorCode());
        self::assertSame(['allowed' => 'ACCEPTED|REJECTED|FINAL'], $validator->getErrorParams());

        $arrayValidator = new EnumValidator(['A', 'B']);
        self::assertTrue($arrayValidator->validate('A'));
        self::assertFalse($arrayValidator->validate('C'));

        $intValidator = new EnumValidator([1, 2]);
        self::assertTrue($intValidator->validate(1));
        self::assertFalse($intValidator->validate(3));
    }

    public function testEqualsValidator(): void
    {
        $validator = new EqualsValidator('wx-app-id');
        self::assertTrue($validator->validate('wx-app-id'));
        self::assertFalse($validator->validate('other'));
        self::assertTrue($validator->validate(''));
        self::assertSame('equals', $validator->getErrorCode());
    }

    public function testEqualsValidatorIsStrict(): void
    {
        $validator = new EqualsValidator('1');
        self::assertTrue($validator->validate('1'));
        self::assertFalse($validator->validate(1));
    }

    public function testAlphaValidator(): void
    {
        $validator = new AlphaValidator();
        self::assertTrue($validator->validate('abcXYZ'));
        self::assertFalse($validator->validate('abc123'));
        self::assertFalse($validator->validate('abc 123'));
        self::assertFalse($validator->validate("abc\n"));
        self::assertTrue($validator->validate(''));
        self::assertSame('alpha', $validator->getErrorCode());
    }

    public function testAlphaNumericValidator(): void
    {
        $validator = new AlphaNumericValidator();
        self::assertTrue($validator->validate('abc123'));
        self::assertFalse($validator->validate('abc 123'));
        self::assertFalse($validator->validate("abc123\n"));
        self::assertTrue($validator->validate(''));
        self::assertSame('alphaNumeric', $validator->getErrorCode());
    }

    public function testIpAddressValidator(): void
    {
        $validator = new IpAddressValidator();
        self::assertTrue($validator->validate('192.168.1.1'));
        self::assertTrue($validator->validate('2001:db8::1'));
        self::assertFalse($validator->validate('999.999.1.1'));
        self::assertTrue($validator->validate(''));
        self::assertSame('ipAddress', $validator->getErrorCode());
    }

    public function testComparisonValidators(): void
    {
        $greater = new GreaterThanValidator(100);
        self::assertTrue($greater->validate(101));
        self::assertFalse($greater->validate(100));
        self::assertSame('greaterThan', $greater->getErrorCode());

        $greaterOrEqual = new GreaterOrEqualThanValidator(100);
        self::assertTrue($greaterOrEqual->validate(100));
        self::assertFalse($greaterOrEqual->validate(99));
        self::assertSame('greaterOrEqualThan', $greaterOrEqual->getErrorCode());

        $less = new LessThanValidator(100);
        self::assertTrue($less->validate(99));
        self::assertFalse($less->validate(100));
        self::assertSame('lessThan', $less->getErrorCode());

        $lessOrEqual = new LessOrEqualThanValidator(100);
        self::assertTrue($lessOrEqual->validate(100));
        self::assertFalse($lessOrEqual->validate(101));
        self::assertSame('lessOrEqualThan', $lessOrEqual->getErrorCode());
    }

    public function testContainUrlValidator(): void
    {
        $validator = new ContainUrlValidator();
        self::assertTrue($validator->validate('visit https://example.com now'));
        self::assertFalse($validator->validate('no url here'));
        self::assertSame('containUrl', $validator->getErrorCode());

        $inverted = new ContainUrlValidator(true);
        self::assertTrue($inverted->validate('no url here'));
        self::assertFalse($inverted->validate('https://example.com'));
    }

    public function testNewValidatorsWorkThroughValidatorClass(): void
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
