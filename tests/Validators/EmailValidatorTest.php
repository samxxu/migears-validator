<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests\Validators;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use MiGears\Validator\Validators\EmailValidator;

#[CoversClass(EmailValidator::class)]
final class EmailValidatorTest extends TestCase
{
    public function testValidEmail(): void
    {
        $validator = new EmailValidator();
        self::assertTrue($validator->validate('user@example.com'));
    }

    public function testInvalidEmail(): void
    {
        $validator = new EmailValidator();
        self::assertFalse($validator->validate('not-an-email'));
    }

    public function testValidEmptyString(): void
    {
        $validator = new EmailValidator();
        self::assertTrue($validator->validate(''));
    }

    public function testErrorCode(): void
    {
        $validator = new EmailValidator();
        self::assertSame('email', $validator->getErrorCode());
    }

    public function testErrorParams(): void
    {
        $validator = new EmailValidator();
        self::assertSame([], $validator->getErrorParams());
    }
}
