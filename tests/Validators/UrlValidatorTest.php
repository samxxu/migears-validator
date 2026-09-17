<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests\Validators;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use MiGears\Validator\Validators\UrlValidator;

#[CoversClass(UrlValidator::class)]
final class UrlValidatorTest extends TestCase
{
    public function testValidUrl(): void
    {
        $validator = new UrlValidator();
        self::assertTrue($validator->validate('https://example.com'));
    }

    public function testInvalidUrl(): void
    {
        $validator = new UrlValidator();
        self::assertFalse($validator->validate('not-a-url'));
    }

    public function testValidEmptyString(): void
    {
        $validator = new UrlValidator();
        self::assertTrue($validator->validate(''));
    }

    public function testErrorCode(): void
    {
        $validator = new UrlValidator();
        self::assertSame('url', $validator->getErrorCode());
    }

    public function testErrorParams(): void
    {
        $validator = new UrlValidator();
        self::assertSame([], $validator->getErrorParams());
    }
}
