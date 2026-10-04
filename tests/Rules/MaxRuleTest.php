<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests\Rules;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use MiGears\Validator\Rules\MaxRule;

#[CoversClass(MaxRule::class)]
final class MaxRuleTest extends TestCase
{
    public function testValidBelowMax(): void
    {
        $rule = new MaxRule(max: 10);
        self::assertTrue($rule->validate(5));
    }

    public function testValidEqualToMax(): void
    {
        $rule = new MaxRule(max: 10);
        self::assertTrue($rule->validate(10));
    }

    public function testInvalidAboveMax(): void
    {
        $rule = new MaxRule(max: 10);
        self::assertFalse($rule->validate(15));
    }

    public function testErrorCode(): void
    {
        $rule = new MaxRule(max: 10);
        self::assertSame('max', $rule->getErrorCode());
    }

    public function testErrorParams(): void
    {
        $rule = new MaxRule(max: 10);
        self::assertSame(['max' => 10], $rule->getErrorParams());
    }
}
