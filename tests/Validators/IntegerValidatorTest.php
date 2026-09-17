<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests\Validators;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use MiGears\Validator\Validators\IntegerValidator;

#[CoversClass(IntegerValidator::class)]
final class IntegerValidatorTest extends TestCase
{
    public function testValidInt(): void
    {
        $validator = new IntegerValidator();
        self::assertTrue($validator->validate(123));
    }

    public function testValidIntString(): void
    {
        $validator = new IntegerValidator();
        self::assertTrue($validator->validate('123'));
    }

    public function testInvalidString(): void
    {
        $validator = new IntegerValidator();
        self::assertFalse($validator->validate('abc'));
    }

    public function testInvalidFloat(): void
    {
        $validator = new IntegerValidator();
        self::assertFalse($validator->validate(3.14));
    }

    public function testValidNegativeString(): void
    {
        $validator = new IntegerValidator();
        self::assertTrue($validator->validate('-5'));
    }

    public function testErrorCode(): void
    {
        $validator = new IntegerValidator();
        self::assertSame('integer', $validator->getErrorCode());
    }

    public function testErrorParams(): void
    {
        $validator = new IntegerValidator();
        self::assertSame([], $validator->getErrorParams());
    }
}
