<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests\Rules;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use MiGears\Validator\Rules\MaxLengthRule;

#[CoversClass(MaxLengthRule::class)]
final class MaxLengthRuleTest extends TestCase
{
    public function testValidBelowMax(): void
    {
        $rule = new MaxLengthRule(max: 10);
        self::assertTrue($rule->validate('hello'));
    }

    public function testValidEqualToMax(): void
    {
        $rule = new MaxLengthRule(max: 5);
        self::assertTrue($rule->validate('hello'));
    }

    public function testInvalidAboveMax(): void
    {
        $rule = new MaxLengthRule(max: 3);
        self::assertFalse($rule->validate('hello'));
    }

    public function testMultibyte(): void
    {
        $rule = new MaxLengthRule(max: 3);
        self::assertTrue($rule->validate('你好世'));
        self::assertFalse($rule->validate('你好世界'));
    }

    public function testNullValue(): void
    {
        $rule = new MaxLengthRule(max: 10);
        self::assertTrue($rule->validate(null));
    }

    public function testErrorCode(): void
    {
        $rule = new MaxLengthRule(max: 10);
        self::assertSame('maxLength', $rule->getErrorCode());
    }

    public function testErrorParams(): void
    {
        $rule = new MaxLengthRule(max: 10);
        self::assertSame(['max' => 10], $rule->getErrorParams());
    }
}
