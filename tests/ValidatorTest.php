<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use MiGears\Validator\Validator;
use MiGears\Validator\ValidatorInterface;
use MiGears\Validator\Validators\RequiredValidator;
use MiGears\Validator\Validators\MinLengthValidator;

#[CoversClass(Validator::class)]
final class ValidatorTest extends TestCase
{
    public function testValidatePasses(): void
    {
        $validator = new Validator();
        $errors = $validator->validate(
            ['name' => 'John'],
            ['name' => ['required' => true]]
        );
        self::assertSame([], $errors);
    }

    public function testValidateReturnsErrorCode(): void
    {
        $validator = new Validator();
        $errors = $validator->validate(
            ['name' => ''],
            ['name' => ['required' => true]]
        );
        self::assertArrayHasKey('name', $errors);
        self::assertSame('required', $errors['name']['rule']);
        self::assertSame([], $errors['name']['params']);
    }

    public function testValidateShortCircuit(): void
    {
        $validator = new Validator();
        $errors = $validator->validate(
            ['name' => ''],
            ['name' => ['required' => true, 'minLength' => 3]]
        );
        self::assertArrayHasKey('name', $errors);
        self::assertSame('required', $errors['name']['rule']);
    }

    public function testPassesMethod(): void
    {
        $validator = new Validator();
        self::assertTrue($validator->passes(
            ['name' => 'John'],
            ['name' => ['required' => true]]
        ));
        self::assertFalse($validator->passes(
            ['name' => ''],
            ['name' => ['required' => true]]
        ));
    }

    public function testRequiredValidator(): void
    {
        $validator = new Validator();
        $errors = $validator->validate(
            ['field' => ''],
            ['field' => ['required' => true]]
        );
        self::assertSame('required', $errors['field']['rule']);
    }

    public function testMinLengthValidator(): void
    {
        $validator = new Validator();
        $errors = $validator->validate(
            ['name' => 'ab'],
            ['name' => ['minLength' => 3]]
        );
        self::assertSame('minLength', $errors['name']['rule']);
        self::assertSame(['min' => 3], $errors['name']['params']);
    }

    public function testMultipleFields(): void
    {
        $validator = new Validator();
        $errors = $validator->validate(
            ['name' => '', 'email' => ''],
            [
                'name' => ['required' => true],
                'email' => ['required' => true, 'email' => true],
            ]
        );
        self::assertCount(2, $errors);
        self::assertArrayHasKey('name', $errors);
        self::assertArrayHasKey('email', $errors);
        self::assertSame('required', $errors['name']['rule']);
        self::assertSame('required', $errors['email']['rule']);
    }

    public function testUnknownValidatorThrows(): void
    {
        $validator = new Validator();
        $this->expectException(\InvalidArgumentException::class);
        $validator->validate(
            ['field' => 'value'],
            ['field' => ['nonexistent' => true]]
        );
    }

    public function testRegisterCustomValidator(): void
    {
        $customClass = new class implements ValidatorInterface {
            public function validate(mixed $value): bool
            {
                return $value === 'secret';
            }
            public function getErrorCode(): string
            {
                return 'secret';
            }
            public function getErrorParams(): array
            {
                return [];
            }
        };

        Validator::register('secret', $customClass::class);

        $validator = new Validator();
        $errors = $validator->validate(
            ['code' => 'wrong'],
            ['code' => ['secret' => true]]
        );
        self::assertSame('secret', $errors['code']['rule']);

        $errors = $validator->validate(
            ['code' => 'secret'],
            ['code' => ['secret' => true]]
        );
        self::assertSame([], $errors);
    }

    public function testValidatorInstanceInRules(): void
    {
        $customValidator = new RequiredValidator();

        $validator = new Validator();
        $errors = $validator->validate(
            ['field' => ''],
            ['field' => ['custom' => $customValidator]]
        );
        self::assertSame('required', $errors['field']['rule']);
    }

    public function testScalarConfig(): void
    {
        $validator = new Validator();
        $errors = $validator->validate(
            ['name' => 'ab'],
            ['name' => ['minLength' => 5]]
        );
        self::assertSame('minLength', $errors['name']['rule']);
        self::assertSame(['min' => 5], $errors['name']['params']);
    }

    public function testArrayConfig(): void
    {
        $validator = new Validator();
        $errors = $validator->validate(
            ['name' => 'ab'],
            ['name' => ['minLength' => ['min' => 5]]]
        );
        self::assertSame('minLength', $errors['name']['rule']);
        self::assertSame(['min' => 5], $errors['name']['params']);
    }

    public function testVersionConstant(): void
    {
        self::assertSame('2.0.0', Validator::VERSION);
    }
}
