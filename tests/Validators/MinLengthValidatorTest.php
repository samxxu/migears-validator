<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests\Validators;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use MiGears\Validator\Validators\MinLengthValidator;

#[CoversClass(MinLengthValidator::class)]
final class MinLengthValidatorTest extends TestCase
{
    public function testValidAboveMin(): void
    {
        $validator = new MinLengthValidator(min: 3);
        self::assertTrue($validator->validate('hello'));
    }

    public function testValidEqualToMin(): void
    {
        $validator = new MinLengthValidator(min: 3);
        self::assertTrue($validator->validate('abc'));
    }

    public function testInvalidBelowMin(): void
    {
        $validator = new MinLengthValidator(min: 3);
        self::assertFalse($validator->validate('ab'));
    }

    public function testMultibyte(): void
    {
        $validator = new MinLengthValidator(min: 3);
        self::assertTrue($validator->validate('你好世界'));
        self::assertFalse($validator->validate('你好'));
    }

    public function testNullValue(): void
    {
        $validator = new MinLengthValidator(min: 3);
        self::assertTrue($validator->validate(null));
    }

    public function testErrorCode(): void
    {
        $validator = new MinLengthValidator(min: 3);
        self::assertSame('minLength', $validator->getErrorCode());
    }

    public function testErrorParams(): void
    {
        $validator = new MinLengthValidator(min: 3);
        self::assertSame(['min' => 3], $validator->getErrorParams());
    }
}
