<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests\Rules;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use MiGears\Validator\Rules\UrlRule;

#[CoversClass(UrlRule::class)]
final class UrlRuleTest extends TestCase
{
    public function testValidUrl(): void
    {
        $rule = new UrlRule();
        self::assertTrue($rule->validate('https://example.com'));
    }

    public function testInvalidUrl(): void
    {
        $rule = new UrlRule();
        self::assertFalse($rule->validate('not-a-url'));
    }

    public function testValidEmptyString(): void
    {
        $rule = new UrlRule();
        self::assertTrue($rule->validate(''));
    }

    public function testErrorCode(): void
    {
        $rule = new UrlRule();
        self::assertSame('url', $rule->getErrorCode());
    }

    public function testErrorParams(): void
    {
        $rule = new UrlRule();
        self::assertSame([], $rule->getErrorParams());
    }
}
