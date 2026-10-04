<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests\Rules;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use MiGears\Validator\Rules\EmailRule;

#[CoversClass(EmailRule::class)]
final class EmailRuleTest extends TestCase
{
    public function testValidEmail(): void
    {
        $rule = new EmailRule();
        self::assertTrue($rule->validate('user@example.com'));
    }

    public function testInvalidEmail(): void
    {
        $rule = new EmailRule();
        self::assertFalse($rule->validate('not-an-email'));
    }

    public function testValidEmptyString(): void
    {
        $rule = new EmailRule();
        self::assertTrue($rule->validate(''));
    }

    public function testErrorCode(): void
    {
        $rule = new EmailRule();
        self::assertSame('email', $rule->getErrorCode());
    }

    public function testErrorParams(): void
    {
        $rule = new EmailRule();
        self::assertSame([], $rule->getErrorParams());
    }
}
