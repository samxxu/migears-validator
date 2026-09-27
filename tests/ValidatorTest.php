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

    public function testEnumListArrayConfigThroughValidator(): void
    {
        $validator = new Validator();
        // list-array form: ['enum' => ['A','B']] — previously silently dropped
        self::assertTrue($validator->passes(
            ['status' => 'A'],
            ['status' => ['enum' => ['A', 'B']]]
        ));
        self::assertFalse($validator->passes(
            ['status' => 'C'],
            ['status' => ['enum' => ['A', 'B']]]
        ));
    }

    public function testEnumTrueHasEmptyAllowedSet(): void
    {
        $validator = new Validator();
        // ['enum' => true] enables enum with its default (empty) allowed set,
        // so every non-empty value is rejected while blank values are skipped.
        self::assertFalse($validator->passes(
            ['status' => 'A'],
            ['status' => ['enum' => true]]
        ));
        self::assertTrue($validator->passes(
            ['status' => ''],
            ['status' => ['enum' => true]]
        ));
    }

    public function testPatternNamedArrayConfigThroughValidator(): void
    {
        $validator = new Validator();
        // named-array form: ['pattern' => ['pattern' => '/.../']]
        self::assertTrue($validator->passes(
            ['code' => 'abc123'],
            ['code' => ['pattern' => ['pattern' => '/^[a-z0-9]+$/']]]
        ));
        self::assertFalse($validator->passes(
            ['code' => 'ABC'],
            ['code' => ['pattern' => ['pattern' => '/^[a-z0-9]+$/']]]
        ));
    }

    public function testPatternListArrayConfigWorks(): void
    {
        $validator = new Validator();
        // list form on a scalar-first-param validator: single element is the
        // scalar config (previously threw a raw TypeError)
        self::assertTrue($validator->passes(
            ['code' => 'abc'],
            ['code' => ['pattern' => ['/^[a-z]+$/']]]
        ));
        self::assertFalse($validator->passes(
            ['code' => 'ABC'],
            ['code' => ['pattern' => ['/^[a-z]+$/']]]
        ));
    }

    public function testMinLengthListArrayConfigWorks(): void
    {
        $validator = new Validator();
        self::assertTrue($validator->passes(
            ['name' => 'abc'],
            ['name' => ['minLength' => [3]]]
        ));
        self::assertFalse($validator->passes(
            ['name' => 'ab'],
            ['name' => ['minLength' => [3]]]
        ));
        // string numerics are coerced on the list path too
        self::assertFalse($validator->passes(
            ['name' => 'ab'],
            ['name' => ['minLength' => ['3']]]
        ));
    }

    public function testMultiElementListConfigForScalarParamThrows(): void
    {
        $validator = new Validator();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/takes a single argument/');
        $validator->passes(
            ['name' => 'abc'],
            ['name' => ['minLength' => [3, 10]]]
        );
    }

    public function testEqualsTrueFailsForNonEmptyValue(): void
    {
        $validator = new Validator();
        // ['equals' => true] uses default expected=null, so any non-empty value fails
        self::assertFalse($validator->passes(
            ['val' => 'hello'],
            ['val' => ['equals' => true]]
        ));
        self::assertTrue($validator->passes(
            ['val' => ''],
            ['val' => ['equals' => true]]
        ));
    }

    public function testLooseFalsyValuesDoNotDisableRule(): void
    {
        $validator = new Validator();
        // 0 does NOT disable required — empty value still rejected
        self::assertFalse($validator->passes(
            ['name' => ''],
            ['name' => ['required' => 0]]
        ));
        // empty string does NOT disable required
        self::assertFalse($validator->passes(
            ['name' => ''],
            ['name' => ['required' => '']]
        ));
        // null does NOT disable required (falls into the "use defaults" path)
        self::assertFalse($validator->passes(
            ['name' => ''],
            ['name' => ['required' => null]]
        ));
    }

    public function testListFormWithFalseGivesClearException(): void
    {
        $validator = new Validator();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/List-form rule.*must be a string alias/');
        $validator->validate(
            ['email' => 'x@y.com'],
            ['email' => ['email', false]]
        );
    }

    public function testConstructorDoesNotCallOverriddenRegister(): void
    {
        // Subclass that overrides register() and tracks calls.
        // The parent constructor must NOT invoke the overridden register().
        $mock = new class([SecretValidator::class]) extends Validator {
            public int $registerCalls = 0;

            public function register(string $class): bool
            {
                $this->registerCalls++;
                return parent::register($class);
            }
        };

        self::assertSame(0, $mock->registerCalls,
            'Constructor should not call the overridden register()');
        // SecretValidator was still registered via the internal path
        self::assertTrue($mock->passes(['token' => 'secret'], ['token' => ['secret' => true]]));

        // Explicit register() call does invoke the override
        $mock->register(StatusValidator::class);
        self::assertSame(1, $mock->registerCalls);
    }

    public function testEmptyArrayConfigUsesDefaults(): void
    {
        $validator = new Validator();
        // [] means "use default config" — same as true
        self::assertTrue($validator->passes(
            ['email' => 'user@example.com'],
            ['email' => ['email' => []]]
        ));
        self::assertFalse($validator->passes(
            ['name' => ''],
            ['name' => ['required' => []]]
        ));
    }

    public function testFalseConfigDisablesRule(): void
    {
        $validator = new Validator();
        // required=false should skip the rule, so empty value passes
        self::assertTrue($validator->passes(
            ['name' => ''],
            ['name' => ['required' => false]]
        ));
        // minLength=false should skip the rule too
        self::assertTrue($validator->passes(
            ['name' => 'ab'],
            ['name' => ['minLength' => false]]
        ));
        // mixed: false rule is skipped, but other rules still apply
        self::assertFalse($validator->passes(
            ['name' => 'ab'],
            ['name' => ['required' => false, 'minLength' => 5]]
        ));
    }

    public function testArrayConfigRejectsMisspelledKey(): void
    {
        $validator = new Validator();
        // a typo must fail loudly instead of silently weakening the rule
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unknown config key\(s\) for MinLengthValidator: mn/');
        $validator->passes(
            ['name' => 'ab'],
            ['name' => ['minLength' => ['mn' => 5]]]
        );
    }

    public function testArrayConfigRejectsUnknownPatternKey(): void
    {
        $validator = new Validator();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unknown config key\(s\) for PatternValidator: patern/');
        $validator->passes(
            ['code' => '123'],
            ['code' => ['pattern' => ['patern' => '/^[a-z]+$/']]]
        );
    }

    public function testArrayConfigWithValidKeysStillWorks(): void
    {
        $validator = new Validator();
        self::assertTrue($validator->passes(
            ['name' => 'hello'],
            ['name' => ['minLength' => ['min' => 3]]]
        ));
        self::assertFalse($validator->passes(
            ['name' => 'ab'],
            ['name' => ['minLength' => ['min' => 3]]]
        ));
    }
}
