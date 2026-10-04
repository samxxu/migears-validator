<?php

declare(strict_types=1);

namespace MiGears\Validator\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use MiGears\Validator\Validator;
use MiGears\Validator\RuleInterface;
use MiGears\Validator\Rules\RequiredRule;

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

    public function testRequiredRule(): void
    {
        $validator = new Validator();
        $errors = $validator->validate(
            ['field' => ''],
            ['field' => ['required' => true]]
        );
        self::assertSame('required', $errors['field']['rule']);
    }

    public function testMinLengthRule(): void
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

    public function testUnknownRuleThrows(): void
    {
        $validator = new Validator();
        $this->expectException(\InvalidArgumentException::class);
        $validator->validate(
            ['field' => 'value'],
            ['field' => ['nonexistent' => true]]
        );
    }

    public function testRegisterCustomRule(): void
    {
        $validator = new Validator();
        $validator->register(SecretRule::class);

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

    public function testRegisterNewRuleReturnsFalse(): void
    {
        self::assertFalse((new Validator())->register(StatusRule::class));
    }

    public function testRegisterOverridingBuiltinReturnsTrue(): void
    {
        $validator = new Validator();
        self::assertTrue($validator->register(EmailRule::class));

        self::assertTrue($validator->passes(
            ['email' => 'user@example.com'],
            ['email' => ['email' => true]]
        ));
        self::assertFalse($validator->passes(
            ['email' => 'not-an-email'],
            ['email' => ['email' => true]]
        ));
    }

    public function testRegisterNonRuleThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Validator())->register(self::class);
    }

    public function testCustomRuleDoesNotLeakToOtherInstances(): void
    {
        $a = new Validator();
        $a->register(SecretRule::class);

        $this->expectException(\InvalidArgumentException::class);
        (new Validator())->validate(
            ['code' => 'wrong'],
            ['code' => ['secret' => true]]
        );
    }

    public function testRuleInstanceInRules(): void
    {
        $customRule = new RequiredRule();

        $validator = new Validator();
        $errors = $validator->validate(
            ['field' => ''],
            ['field' => ['custom' => $customRule]]
        );
        self::assertSame('required', $errors['field']['rule']);
    }

    public function testRegisterInstanceUsesErrorCodeAsAlias(): void
    {
        $validator = new Validator();
        $validator->register(new SecretRule());

        self::assertTrue($validator->passes(['code' => 'secret'], ['code' => ['secret' => true]]));
        self::assertFalse($validator->passes(['code' => 'wrong'], ['code' => ['secret' => true]]));
    }

    public function testRegisterInstanceAcceptsAnAnonymousClass(): void
    {
        // The alias comes from getErrorCode(), not the class name, so a rule
        // need not be a named class.
        $rule = new class implements RuleInterface {
            public function validate(mixed $value): bool
            {
                return $value === 'ok';
            }

            public function getErrorCode(): string
            {
                return 'anon';
            }

            public function getErrorParams(): array
            {
                return [];
            }
        };

        $validator = new Validator();
        self::assertFalse($validator->register($rule));
        self::assertTrue($validator->passes(['v' => 'ok'], ['v' => ['anon' => true]]));
        self::assertFalse($validator->passes(['v' => 'no'], ['v' => ['anon' => true]]));
    }

    public function testRegisteredInstanceOverridesBuiltinAndReportsIt(): void
    {
        // An instance is keyed by its error code, so a rule whose code is
        // `email` displaces the built-in of the same name.
        $rule = new class implements RuleInterface {
            public function validate(mixed $value): bool
            {
                return $value === 'anything';
            }

            public function getErrorCode(): string
            {
                return 'email';
            }

            public function getErrorParams(): array
            {
                return [];
            }
        };

        $validator = new Validator();
        self::assertTrue($validator->register($rule));
        self::assertTrue($validator->passes(['email' => 'anything'], ['email' => ['email' => true]]));
        self::assertFalse($validator->passes(['email' => 'user@example.com'], ['email' => ['email' => true]]));
    }

    public function testRegisteredInstanceResolvesInListForm(): void
    {
        $validator = new Validator();
        $validator->register(new SecretRule());

        self::assertTrue($validator->passes(['code' => 'secret'], ['code' => ['secret']]));
        self::assertFalse($validator->passes(['code' => 'wrong'], ['code' => ['secret']]));
    }

    public function testRegisteredInstanceRespectsFalseDisable(): void
    {
        $validator = new Validator();
        $validator->register(new SecretRule());

        // `false` disables the rule before its instance is ever looked up
        self::assertTrue($validator->passes(['code' => 'wrong'], ['code' => ['secret' => false]]));
    }

    public function testRegisteredInstanceIgnoresItsConfig(): void
    {
        $validator = new Validator();
        $validator->register(new SecretRule());

        // A scalar config would otherwise be routed to the constructor; for an
        // already-built instance it is simply ignored. (SecretRule takes no
        // constructor argument, so routing it would have thrown instead.)
        self::assertTrue($validator->passes(['code' => 'secret'], ['code' => ['secret' => 'ignored']]));
    }

    public function testRegisteredInstanceWinsOverARegisteredClassOfTheSameAlias(): void
    {
        $validator = new Validator();
        $validator->register(SecretRule::class);
        $validator->register(new class implements RuleInterface {
            public function validate(mixed $value): bool
            {
                return $value === 'other';
            }

            public function getErrorCode(): string
            {
                return 'secret';
            }

            public function getErrorParams(): array
            {
                return [];
            }
        });

        self::assertTrue($validator->passes(['code' => 'other'], ['code' => ['secret' => true]]));
        self::assertFalse($validator->passes(['code' => 'secret'], ['code' => ['secret' => true]]));
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

    public function testScalarConfigOnARuleWithNoConstructorArgumentIsRefused(): void
    {
        // The config used to vanish: EmailRule takes no constructor argument, so the scalar was dropped
        // and the rule quietly became a bare email check.
        // 该配置曾会凭空消失：EmailRule 没有构造参数，于是这个标量被丢弃，规则悄然退化成普通邮箱校验。
        $validator = new Validator();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('EmailRule takes no constructor argument');

        $validator->validate(['name' => 'x'], ['name' => ['email' => 'nonsense']]);
    }

    public function testUncoercibleScalarConfigRaisesTheModulesOwnException(): void
    {
        // A raw TypeError used to escape the constructor call, naming a PHP argument rather than the config
        // value the caller actually wrote.
        // 此前会从构造器调用处漏出原始的 TypeError，点名的是 PHP 参数，而不是调用方真正写下的配置值。
        $validator = new Validator();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'MinLengthRule expects int for its first constructor argument, got string'
        );

        $validator->validate(['name' => 'x'], ['name' => ['minLength' => 'abc']]);
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

    public function testConstructorPreRegistersCustomRules(): void
    {
        $validator = new Validator([SecretRule::class]);
        self::assertTrue($validator->passes(['token' => 'secret'], ['token' => ['secret' => true]]));
        self::assertFalse($validator->passes(['token' => 'a-different-value'], ['token' => ['secret' => true]]));
    }

    public function testConstructorPreRegisteredRulesAreInstanceScoped(): void
    {
        $with = new Validator([SecretRule::class]);
        $without = new Validator();
        self::assertTrue($with->passes(['token' => 'secret'], ['token' => ['secret' => true]]));
        $this->expectException(\InvalidArgumentException::class);
        $without->validate(['token' => 'secret'], ['token' => ['secret' => true]]);
    }

    public function testConstructorAcceptsCollidingAliasClassWithoutError(): void
    {
        // EmailRule collides with the builtin `email` rule; passing it via
        // the constructor must be accepted (override path), not throw.
        $validator = new Validator([EmailRule::class]);
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
        self::assertSame('2.3.0', Validator::VERSION);
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
        // list form on a scalar-first-param rule: single element is the
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
        $mock = new class([SecretRule::class]) extends Validator {
            public int $registerCalls = 0;

            public function register(string|RuleInterface $rule): bool
            {
                $this->registerCalls++;
                return parent::register($rule);
            }
        };

        self::assertSame(0, $mock->registerCalls,
            'Constructor should not call the overridden register()');
        // SecretRule was still registered via the internal path
        self::assertTrue($mock->passes(['token' => 'secret'], ['token' => ['secret' => true]]));

        // Explicit register() call does invoke the override
        $mock->register(StatusRule::class);
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
        $this->expectExceptionMessageMatches('/Unknown config key\(s\) for MinLengthRule: mn/');
        $validator->passes(
            ['name' => 'ab'],
            ['name' => ['minLength' => ['mn' => 5]]]
        );
    }

    public function testArrayConfigRejectsUnknownPatternKey(): void
    {
        $validator = new Validator();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unknown config key\(s\) for PatternRule: patern/');
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

    public function testNonArrayFieldRulesAreRefused(): void
    {
        // A string rules value used to emit the raw PHP warning "foreach() argument must be of type
        // array|object" and then pass the field silently, validating nothing while reporting success.
        // 字符串形态的规则值曾抛出裸 PHP 警告「foreach() argument must be of type array|object」，
        // 随后静默放行该字段——什么都不校验却报告通过。
        $validator = new Validator();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rules for field name must be an array, got string');

        $validator->validate(['name' => 'John'], ['name' => 'required']);
    }
}
