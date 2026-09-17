<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests\Validators;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use MiGears\Validator\Validators\MinValidator;

#[CoversClass(MinValidator::class)]
final class MinValidatorTest extends TestCase
{
    public function testValidAboveMin(): void
    {
        $validator = new MinValidator(min: 5);
        self::assertTrue($validator->validate(10));
    }

    public function testValidEqualToMin(): void
    {
        $validator = new MinValidator(min: 5);
        self::assertTrue($validator->validate(5));
    }

    public function testInvalidBelowMin(): void
    {
        $validator = new MinValidator(min: 5);
        self::assertFalse($validator->validate(3));
    }

    public function testErrorCode(): void
    {
        $validator = new MinValidator(min: 5);
        self::assertSame('min', $validator->getErrorCode());
    }

    public function testErrorParams(): void
    {
        $validator = new MinValidator(min: 5);
        self::assertSame(['min' => 5], $validator->getErrorParams());
    }

    public function testWithStringNumber(): void
    {
        $validator = new MinValidator(min: 5);
        self::assertTrue($validator->validate('10'));
        self::assertFalse($validator->validate('3'));
    }
}
