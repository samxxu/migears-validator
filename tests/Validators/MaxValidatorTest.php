<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests\Validators;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use MiGears\Validator\Validators\MaxValidator;

#[CoversClass(MaxValidator::class)]
final class MaxValidatorTest extends TestCase
{
    public function testValidBelowMax(): void
    {
        $validator = new MaxValidator(max: 10);
        self::assertTrue($validator->validate(5));
    }

    public function testValidEqualToMax(): void
    {
        $validator = new MaxValidator(max: 10);
        self::assertTrue($validator->validate(10));
    }

    public function testInvalidAboveMax(): void
    {
        $validator = new MaxValidator(max: 10);
        self::assertFalse($validator->validate(15));
    }

    public function testErrorCode(): void
    {
        $validator = new MaxValidator(max: 10);
        self::assertSame('max', $validator->getErrorCode());
    }

    public function testErrorParams(): void
    {
        $validator = new MaxValidator(max: 10);
        self::assertSame(['max' => 10], $validator->getErrorParams());
    }
}
