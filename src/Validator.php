<?php

declare(strict_types=1);

namespace MiGears\Validator;

use MiGears\Validator\Validators\EmailValidator;
use MiGears\Validator\Validators\IntegerValidator;
use MiGears\Validator\Validators\MaxLengthValidator;
use MiGears\Validator\Validators\MaxValidator;
use MiGears\Validator\Validators\MinLengthValidator;
use MiGears\Validator\Validators\MinValidator;
use MiGears\Validator\Validators\PatternValidator;
use MiGears\Validator\Validators\RequiredValidator;
use MiGears\Validator\Validators\UrlValidator;

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

    /** @var array<string, class-string<ValidatorInterface>> */
    private static array $validatorMap = [
        'required'  => RequiredValidator::class,
        'email'     => EmailValidator::class,
        'minLength' => MinLengthValidator::class,
        'maxLength' => MaxLengthValidator::class,
        'pattern'   => PatternValidator::class,
        'integer'   => IntegerValidator::class,
        'min'       => MinValidator::class,
        'max'       => MaxValidator::class,
        'url'       => UrlValidator::class,
    ];

    /**
     * Register a custom validator with an alias.
     *
     * @param string $alias Short name for the validator
     * @param class-string<ValidatorInterface> $class
     */
    public static function register(string $alias, string $class): void
    {
        self::$validatorMap[$alias] = $class;
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
            if (!isset(self::$validatorMap[$alias])) {
                throw new \InvalidArgumentException("Unknown validator: {$alias}");
            }
            return new (self::$validatorMap[$alias])();
        }

        // Named rule with configuration
        if (!isset(self::$validatorMap[$key])) {
            throw new \InvalidArgumentException("Unknown validator: {$key}");
        }

        $class = self::$validatorMap[$key];

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

        return new $class($value);
    }
}
