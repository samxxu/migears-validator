<?php

declare(strict_types=1);

namespace MiGears\Validator;

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
    public const VERSION = '2.3.0';

    private RuleFactory $factory;

    /**
     * Optional pre-registration of custom rules by class-string. Each entry is
     * registered through the internal path (alias derived from the class name),
     * so a ready-to-use instance can be built in one shot:
     *
     *   $validator = new Validator([StrongPasswordRule::class]);
     *
     * For a rule that must be pre-registered as an instance, call
     * {@see register()} instead.
     *
     * @param list<class-string<RuleInterface>> $ruleClasses
     */
    public function __construct(array $ruleClasses = [])
    {
        $this->factory = new RuleFactory($ruleClasses);
    }

    /**
     * Register a custom rule for this instance only.
     *
     * Two forms:
     *
     *  - class-string — the rule is built lazily from the config given for it in
     *    the rules array (see {@see RuleFactory::createRule()}), so its
     *    parameters live there: `'minLength' => 5`, `'strength' => 12`. The alias
     *    is derived from the class short name (StrongPasswordRule =>
     *    strongPassword).
     *  - RuleInterface instance — an already-built rule, e.g. one carrying a
     *    dependency. Its alias is its own getErrorCode(), so it need not be a
     *    named class (anonymous classes and mocks work). Because it is already
     *    built, any config given for it in the rules array is ignored — do not
     *    use this form to pass parameters.
     *
     * Returns true if the derived alias already resolved to another rule
     * (i.e. this registration overrode one) — the caller may then log a warning.
     * Custom rules are scoped to each Validator instance, so they never leak
     * into other validation contexts.
     *
     * @param class-string<RuleInterface>|RuleInterface $rule
     */
    public function register(string|RuleInterface $rule): bool
    {
        return $this->factory->register($rule);
    }

    /**
     * Validate data against a rule set.
     *
     * Returns an array of errors keyed by field name.
     * Each error has 'rule' (error code) and 'params' (for i18n interpolation).
     * Empty array means validation passed.
     *
     * @param array<string, mixed> $data   Input data
     * @param array<string, mixed> $rules  Field name => rules array (the array is enforced at runtime, not by this type)
     * @return array<string, array{rule: string, params: array<string, mixed>}>
     */
    public function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            if (!is_array($fieldRules)) {
                // A non-array here used to reach the inner foreach as the raw PHP warning
                // "foreach() argument must be of type array|object" and then leave the field
                // with no errors at all — validating nothing while reporting success. Refusing
                // it is the same loud failure an unknown config key already gets.
                // 非数组此前会被内层 foreach 当成裸 PHP 警告「foreach() argument must be of type
                // array|object」，随后让该字段不带任何错误——什么都不校验却报告通过。拒绝它，
                // 与未知配置键得到的响亮失败一致。
                throw new \InvalidArgumentException(sprintf(
                    'Rules for field %s must be an array, got %s.',
                    $field,
                    get_debug_type($fieldRules)
                ));
            }

            $value = $data[$field] ?? null;

            foreach ($fieldRules as $key => $config) {
                // `false` means the rule is disabled — skip it
                if ($config === false && !is_int($key)) {
                    continue;
                }

                $rule = $this->factory->createRule($key, $config);

                if (!$rule->validate($value)) {
                    $errors[$field] = [
                        'rule' => $rule->getErrorCode(),
                        'params' => $rule->getErrorParams(),
                    ];
                    break; // short-circuit on first failure per field
                }
            }
        }

        return $errors;
    }

    /**
     * Check if data passes validation (convenience method).
     *
     * @param array<string, mixed> $data  Input data
     * @param array<string, mixed> $rules  Field name => rules array (the array is enforced at runtime, not by this type)
     */
    public function passes(array $data, array $rules): bool
    {
        return $this->validate($data, $rules) === [];
    }
}
