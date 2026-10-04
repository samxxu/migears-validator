<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests\Rules;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use MiGears\Validator\Rules\PatternRule;

#[CoversClass(PatternRule::class)]
final class PatternRuleTest extends TestCase
{
    public function testValidMatch(): void
    {
        $rule = new PatternRule(pattern: '/^[a-z]+$/');
        self::assertTrue($rule->validate('abc'));
    }

    public function testInvalidNoMatch(): void
    {
        $rule = new PatternRule(pattern: '/^[a-z]+$/');
        self::assertFalse($rule->validate('ABC123'));
    }

    public function testErrorCode(): void
    {
        $rule = new PatternRule(pattern: '/^[a-z]+$/');
        self::assertSame('pattern', $rule->getErrorCode());
    }

    public function testErrorParams(): void
    {
        $rule = new PatternRule(pattern: '/^[a-z]+$/');
        self::assertSame(['pattern' => '/^[a-z]+$/'], $rule->getErrorParams());
    }

    public function testInvalidPatternThrowsAtConstruction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PatternRule(pattern: '/invalid(/');
    }

    public function testEmptyPatternIsValid(): void
    {
        $rule = new PatternRule(pattern: '//');
        self::assertTrue($rule->validate('anything'));
    }
}
