<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use MiGears\Validator\Validator;
use MiGears\Validator\Validators\RequiredValidator;

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
        $validator = new Validator();
        $validator->register(SecretValidator::class);

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

    public function testRegisterNewValidatorReturnsFalse(): void
    {
        self::assertFalse((new Validator())->register(StatusValidator::class));
    }

    public function testRegisterOverridingBuiltinReturnsTrue(): void
    {
        $validator = new Validator();
        self::assertTrue($validator->register(EmailValidator::class));

        self::assertTrue($validator->passes(
            ['email' => 'user@example.com'],
            ['email' => ['email' => true]]
        ));
        self::assertFalse($validator->passes(
            ['email' => 'not-an-email'],
            ['email' => ['email' => true]]
        ));
    }

    public function testRegisterNonValidatorThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Validator())->register(self::class);
    }

    public function testCustomRuleDoesNotLeakToOtherInstances(): void
    {
        $a = new Validator();
        $a->register(SecretValidator::class);

        $this->expectException(\InvalidArgumentException::class);
        (new Validator())->validate(
            ['code' => 'wrong'],
            ['code' => ['secret' => true]]
        );
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

    public function testStringScalarConfigForNumericParam(): void
    {
        $validator = new Validator();
        $errors = $validator->validate(
            ['name' => 'ab'],
            ['name' => ['minLength' => '5']]
        );
        self::assertSame('minLength', $errors['name']['rule']);
        self::assertSame(['min' => 5], $errors['name']['params']);
    }

    public function testStringScalarConfigForUnionNumericParam(): void
    {
        $validator = new Validator();
        self::assertFalse($validator->passes(['n' => 5], ['n' => ['min' => '10']]));
        self::assertTrue($validator->passes(['n' => 15], ['n' => ['min' => '10']]));
    }

    public function testScalarBoolConfigForInvertParam(): void
    {
        $validator = new Validator();
        self::assertFalse($validator->passes(
            ['text' => 'visit https://example.com now'],
            ['text' => ['containUrl' => 1]]
        ));
        self::assertTrue($validator->passes(
            ['text' => 'no url here'],
            ['text' => ['containUrl' => 1]]
        ));
    }

    public function testConstructorPreRegistersCustomValidators(): void
    {
        $validator = new Validator([SecretValidator::class]);
        self::assertTrue($validator->passes(['token' => 'secret'], ['token' => ['secret' => true]]));
        self::assertFalse($validator->passes(['token' => 'a-different-value'], ['token' => ['secret' => true]]));
    }

    public function testConstructorPreRegisteredRulesAreInstanceScoped(): void
    {
        $with = new Validator([SecretValidator::class]);
        $without = new Validator();
        self::assertTrue($with->passes(['token' => 'secret'], ['token' => ['secret' => true]]));
        $this->expectException(\InvalidArgumentException::class);
        $without->validate(['token' => 'secret'], ['token' => ['secret' => true]]);
    }

    public function testConstructorAcceptsCollidingAliasClassWithoutError(): void
    {
        // EmailValidator collides with the builtin `email` rule; passing it via
        // the constructor must be accepted (override path), not throw.
        $validator = new Validator([EmailValidator::class]);
        self::assertTrue($validator->passes(
            ['value' => 'user@example.com'],
            ['value' => ['email' => true]]
        ));
        self::assertFalse($validator->passes(
            ['value' => 'not-an-email'],
            ['value' => ['email' => true]]
        ));
    }

    public function testVersionConstant(): void
    {
        self::assertSame('2.0.0', Validator::VERSION);
    }
}
