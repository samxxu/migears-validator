<?php

declare(strict_types=1);

namespace MiGears\Validator;

use MiGears\Validator\ValidatorInterface;

/**
 * Lightweight validator for arrays (form data, API parameters, etc.).
 *
 * Declarative rule configuration with error codes and params,
 * ready for i18n message translation.
 *
 * Usage:
 *   $validator = new Validator();
 *   $errors = $validator->validate($_POST, [
 *       'username' => ['required' => true, 'minLength' => 3],
 *       'email'    => ['required' => true, 'email' => true],
 *   ]);
 *
 * Error format:
 *   [
 *     'username' => ['rule' => 'required', 'params' => []],
 *   ]
 */
class Validator
{
    public const VERSION = '2.0.0';

    private const BUILTIN_NAMESPACE = 'MiGears\Validator\Validators\\';

    /** @var array<string, class-string<ValidatorInterface>> */
    private array $registry = [];

    /**
     * Optional pre-registration of custom validators. Each class-string is
     * registered via {@see register()} (alias derived from class name), so a
     * ready-to-use instance can be built in one shot:
     *
     *   $validator = new Validator([StrongPasswordValidator::class]);
     *
     * @param list<class-string<ValidatorInterface>> $validators
     */
    public function __construct(array $validators = [])
    {
        foreach ($validators as $class) {
            $this->register($class);
        }
    }

    /**
     * Register a custom validator for this instance only. The rule alias is
     * derived from the class short name (e.g. StrongPasswordValidator =>
     * strongPassword).
     *
     * Returns true if the derived alias already resolved to another validator
     * (i.e. this registration overrode one) — the caller may then log a warning.
     * Custom rules are scoped to each Validator instance, so they never leak
     * into other validation contexts.
     *
     * @param class-string<ValidatorInterface> $class
     */
    public function register(string $class): bool
    {
        if (!is_subclass_of($class, ValidatorInterface::class)) {
            throw new \InvalidArgumentException(
                "Validator {$class} must implement " . ValidatorInterface::class
            );
        }

        $alias = self::aliasFromClass((new \ReflectionClass($class))->getShortName());
        $overwritten = isset($this->registry[$alias])
            || class_exists(self::BUILTIN_NAMESPACE . ucfirst($alias) . 'Validator');

        $this->registry[$alias] = $class;

        return $overwritten;
    }

    /**
     * Derive a rule alias from a validator class short name.
     */
    private static function aliasFromClass(string $shortName): string
    {
        $suffix = 'Validator';
        if (str_ends_with($shortName, $suffix)) {
            $shortName = substr($shortName, 0, -strlen($suffix));
        }
        return lcfirst($shortName);
    }

    /**
     * Resolve a rule alias to a validator class. Custom registrations take
     * priority, then built-in validators are derived by name.
     *
     * @return class-string<ValidatorInterface>
     */
    private function resolve(string $alias): string
    {
        if (isset($this->registry[$alias])) {
            return $this->registry[$alias];
        }

        $class = self::BUILTIN_NAMESPACE . ucfirst($alias) . 'Validator';
        if (!class_exists($class)) {
            throw new \InvalidArgumentException("Unknown validator: {$alias}");
        }
        return $class;
    }

    /**
     * Validate data against a rule set.
     *
     * Returns an array of errors keyed by field name.
     * Each error has 'rule' (error code) and 'params' (for i18n interpolation).
     * Empty array means validation passed.
     *
     * Rule formats:
     *   'required' => true                    // enable with defaults
     *   'minLength' => 5                      // enable with main param
     *   'minLength' => ['min' => 5]           // full configuration array
     *   'custom' => new CustomValidator()     // validator instance
     *
     * @param array<string, mixed> $data   Input data
     * @param array<string, array> $rules  Field name => rules array
     * @return array<string, array{rule: string, params: array<string, mixed>}>
     */
    public function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $key => $config) {
                $validator = $this->createValidator($key, $config);

                if (!$validator->validate($value)) {
                    $errors[$field] = [
                        'rule' => $validator->getErrorCode(),
                        'params' => $validator->getErrorParams(),
                    ];
                    break; // short-circuit on first failure per field
                }
            }
        }

        return $errors;
    }

    /**
     * Check if data passes validation (convenience method).
     */
    public function passes(array $data, array $rules): bool
    {
        return $this->validate($data, $rules) === [];
    }

    /**
     * Create a validator instance from rule key and config.
     */
    private function createValidator(string|int $key, mixed $config): ValidatorInterface
    {
        // Direct validator instance
        if ($config instanceof ValidatorInterface) {
            return $config;
        }

        // Numeric key means the value is a validator alias with no config
        if (is_int($key)) {
            $alias = (string) $config;
            return new (self::resolve($alias))();
        }

        // Named rule with configuration
        $class = self::resolve($key);

        return match (true) {
            $config === true || $config === null => new $class(),
            is_array($config) => new $class(...$config),
            is_scalar($config) => $this->createFromScalar($class, $config),
            default => throw new \InvalidArgumentException(
                "Unsupported validator config type: " . gettype($config)
            ),
        };
    }

    /**
     * Create a validator with a scalar parameter mapped to first constructor arg.
     *
     * @param class-string<ValidatorInterface> $class
     */
    private function createFromScalar(string $class, mixed $value): ValidatorInterface
    {
        $reflection = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null || $constructor->getParameters() === []) {
            return new $class();
        }

        return new $class($this->coerceScalar($class, $value));
    }

    /**
     * Coerce a scalar config to the validator's first constructor parameter type,
     * so numeric/bool params tolerate string config (e.g. 'minLength' => '5').
     */
    private function coerceScalar(string $class, mixed $value): mixed
    {
        $parameter = (new \ReflectionClass($class))->getConstructor()?->getParameters()[0] ?? null;
        if ($parameter === null) {
            return $value;
        }

        $names = $this->typeNames($parameter->getType());
        $acceptsInt = in_array('int', $names, true);
        $acceptsFloat = in_array('float', $names, true);
        $acceptsBool = in_array('bool', $names, true);

        if (($acceptsInt || $acceptsFloat) && is_numeric($value)) {
            $intLike = $acceptsInt && !is_float($value) && strpbrk((string) $value, '.eE') === false;
            return $intLike ? (int) $value : (float) $value;
        }

        if ($acceptsBool && !is_bool($value)) {
            if ($value === 1 || $value === '1' || $value === 'true') {
                return true;
            }
            if ($value === 0 || $value === '0' || $value === 'false') {
                return false;
            }
        }

        return $value;
    }

    /**
     * Extract the type name(s) of a reflection type (named or union).
     *
     * @return string[]
     */
    private function typeNames(?\ReflectionType $type): array
    {
        if ($type instanceof \ReflectionNamedType) {
            return [$type->getName()];
        }
        if ($type instanceof \ReflectionUnionType) {
            return array_map(static fn (\ReflectionNamedType $t): string => $t->getName(), $type->getTypes());
        }
        return [];
    }
}
