<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests\Rules;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use MiGears\Validator\Rules\MinLengthRule;

#[CoversClass(MinLengthRule::class)]
final class MinLengthRuleTest extends TestCase
{
    public function testValidAboveMin(): void
    {
        $rule = new MinLengthRule(min: 3);
        self::assertTrue($rule->validate('hello'));
    }

    public function testValidEqualToMin(): void
    {
        $rule = new MinLengthRule(min: 3);
        self::assertTrue($rule->validate('abc'));
    }

    public function testInvalidBelowMin(): void
    {
        $rule = new MinLengthRule(min: 3);
        self::assertFalse($rule->validate('ab'));
    }

    public function testMultibyte(): void
    {
        $rule = new MinLengthRule(min: 3);
        self::assertTrue($rule->validate('你好世界'));
        self::assertFalse($rule->validate('你好'));
    }

    public function testNullValue(): void
    {
        $rule = new MinLengthRule(min: 3);
        self::assertTrue($rule->validate(null));
    }

    public function testErrorCode(): void
    {
        $rule = new MinLengthRule(min: 3);
        self::assertSame('minLength', $rule->getErrorCode());
    }

    public function testErrorParams(): void
    {
        $rule = new MinLengthRule(min: 3);
        self::assertSame(['min' => 3], $rule->getErrorParams());
    }
}
