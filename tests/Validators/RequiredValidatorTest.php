<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests\Validators;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use MiGears\Validator\Validators\RequiredValidator;

#[CoversClass(RequiredValidator::class)]
final class RequiredValidatorTest extends TestCase
{
    public function testValidWithString(): void
    {
        $validator = new RequiredValidator();
        self::assertTrue($validator->validate('hello'));
    }

    public function testInvalidWithEmptyString(): void
    {
        $validator = new RequiredValidator();
        self::assertFalse($validator->validate(''));
    }

    public function testInvalidWithNull(): void
    {
        $validator = new RequiredValidator();
        self::assertFalse($validator->validate(null));
    }

    public function testInvalidWithEmptyArray(): void
    {
        $validator = new RequiredValidator();
        self::assertFalse($validator->validate([]));
    }

    public function testValidWithZero(): void
    {
        $validator = new RequiredValidator();
        self::assertTrue($validator->validate('0'));
    }

    public function testErrorCode(): void
    {
        $validator = new RequiredValidator();
        self::assertSame('required', $validator->getErrorCode());
    }

    public function testErrorParams(): void
    {
        $validator = new RequiredValidator();
        self::assertSame([], $validator->getErrorParams());
    }
}
