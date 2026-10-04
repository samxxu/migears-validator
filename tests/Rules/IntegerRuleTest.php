<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests\Rules;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use MiGears\Validator\Rules\IntegerRule;

#[CoversClass(IntegerRule::class)]
final class IntegerRuleTest extends TestCase
{
    public function testValidInt(): void
    {
        $rule = new IntegerRule();
        self::assertTrue($rule->validate(123));
    }

    public function testValidIntString(): void
    {
        $rule = new IntegerRule();
        self::assertTrue($rule->validate('123'));
    }

    public function testInvalidString(): void
    {
        $rule = new IntegerRule();
        self::assertFalse($rule->validate('abc'));
    }

    public function testInvalidFloat(): void
    {
        $rule = new IntegerRule();
        self::assertFalse($rule->validate(3.14));
    }

    public function testValidNegativeString(): void
    {
        $rule = new IntegerRule();
        self::assertTrue($rule->validate('-5'));
    }

    public function testTrailingNewlineIsInvalid(): void
    {
        $rule = new IntegerRule();
        self::assertFalse($rule->validate("123\n"));
        self::assertFalse($rule->validate("-5\n"));
    }

    public function testErrorCode(): void
    {
        $rule = new IntegerRule();
        self::assertSame('integer', $rule->getErrorCode());
    }

    public function testErrorParams(): void
    {
        $rule = new IntegerRule();
        self::assertSame([], $rule->getErrorParams());
    }
}
