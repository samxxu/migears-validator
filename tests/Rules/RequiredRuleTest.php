<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests\Rules;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use MiGears\Validator\Rules\RequiredRule;

#[CoversClass(RequiredRule::class)]
final class RequiredRuleTest extends TestCase
{
    public function testValidWithString(): void
    {
        $rule = new RequiredRule();
        self::assertTrue($rule->validate('hello'));
    }

    public function testInvalidWithEmptyString(): void
    {
        $rule = new RequiredRule();
        self::assertFalse($rule->validate(''));
    }

    public function testInvalidWithNull(): void
    {
        $rule = new RequiredRule();
        self::assertFalse($rule->validate(null));
    }

    public function testInvalidWithEmptyArray(): void
    {
        $rule = new RequiredRule();
        self::assertFalse($rule->validate([]));
    }

    public function testValidWithZero(): void
    {
        $rule = new RequiredRule();
        self::assertTrue($rule->validate('0'));
    }

    public function testErrorCode(): void
    {
        $rule = new RequiredRule();
        self::assertSame('required', $rule->getErrorCode());
    }

    public function testErrorParams(): void
    {
        $rule = new RequiredRule();
        self::assertSame([], $rule->getErrorParams());
    }
}
