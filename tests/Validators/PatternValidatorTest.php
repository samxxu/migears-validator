<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests\Validators;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use MiGears\Validator\Validators\PatternValidator;

#[CoversClass(PatternValidator::class)]
final class PatternValidatorTest extends TestCase
{
    public function testValidMatch(): void
    {
        $validator = new PatternValidator(pattern: '/^[a-z]+$/');
        self::assertTrue($validator->validate('abc'));
    }

    public function testInvalidNoMatch(): void
    {
        $validator = new PatternValidator(pattern: '/^[a-z]+$/');
        self::assertFalse($validator->validate('ABC123'));
    }

    public function testErrorCode(): void
    {
        $validator = new PatternValidator(pattern: '/^[a-z]+$/');
        self::assertSame('pattern', $validator->getErrorCode());
    }

    public function testErrorParams(): void
    {
        $validator = new PatternValidator(pattern: '/^[a-z]+$/');
        self::assertSame(['pattern' => '/^[a-z]+$/'], $validator->getErrorParams());
    }
}
