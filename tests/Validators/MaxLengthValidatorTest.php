<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests\Validators;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use MiGears\Validator\Validators\MaxLengthValidator;

#[CoversClass(MaxLengthValidator::class)]
final class MaxLengthValidatorTest extends TestCase
{
    public function testValidBelowMax(): void
    {
        $validator = new MaxLengthValidator(max: 10);
        self::assertTrue($validator->validate('hello'));
    }

    public function testValidEqualToMax(): void
    {
        $validator = new MaxLengthValidator(max: 5);
        self::assertTrue($validator->validate('hello'));
    }

    public function testInvalidAboveMax(): void
    {
        $validator = new MaxLengthValidator(max: 3);
        self::assertFalse($validator->validate('hello'));
    }

    public function testMultibyte(): void
    {
        $validator = new MaxLengthValidator(max: 3);
        self::assertTrue($validator->validate('你好世'));
        self::assertFalse($validator->validate('你好世界'));
    }

    public function testNullValue(): void
    {
        $validator = new MaxLengthValidator(max: 10);
        self::assertTrue($validator->validate(null));
    }

    public function testErrorCode(): void
    {
        $validator = new MaxLengthValidator(max: 10);
        self::assertSame('maxLength', $validator->getErrorCode());
    }

    public function testErrorParams(): void
    {
        $validator = new MaxLengthValidator(max: 10);
        self::assertSame(['max' => 10], $validator->getErrorParams());
    }
}
