<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests\Rules;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use MiGears\Validator\Rules\MinRule;

#[CoversClass(MinRule::class)]
final class MinRuleTest extends TestCase
{
    public function testValidAboveMin(): void
    {
        $rule = new MinRule(min: 5);
        self::assertTrue($rule->validate(10));
    }

    public function testValidEqualToMin(): void
    {
        $rule = new MinRule(min: 5);
        self::assertTrue($rule->validate(5));
    }

    public function testInvalidBelowMin(): void
    {
        $rule = new MinRule(min: 5);
        self::assertFalse($rule->validate(3));
    }

    public function testErrorCode(): void
    {
        $rule = new MinRule(min: 5);
        self::assertSame('min', $rule->getErrorCode());
    }

    public function testErrorParams(): void
    {
        $rule = new MinRule(min: 5);
        self::assertSame(['min' => 5], $rule->getErrorParams());
    }

    public function testWithStringNumber(): void
    {
        $rule = new MinRule(min: 5);
        self::assertTrue($rule->validate('10'));
        self::assertFalse($rule->validate('3'));
    }
}
