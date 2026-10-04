<?php

declare(strict_types=1);

namespace MiGears\Validator;

/**
 * Builds rule instances from declarative config, and resolves an alias to a
 * rule — a pre-registered instance first, then a custom class-string, then a
 * built-in derived by name.
 *
 * Kept separate from {@see Validator} so the orchestration stays thin. Every
 * config style is first normalised into a constructor argument list
 * ({@see argumentsFor()}), then handed to a single `new $class(...$args)` — the
 * declared parameter types are what actually accept or reject a value, PHP
 * does the type check, and this factory only rewrites the failure around the
 * rule and the config instead of a raw argument error.
 */
final class RuleFactory
{
    private const BUILTIN_NAMESPACE = 'MiGears\Validator\Rules\\';

    private const RULE_SUFFIX = 'Rule';

    /** @var array<string, class-string<RuleInterface>> */
    private array $registry = [];

    /** @var array<string, RuleInterface> */
    private array $instances = [];

    /**
     * @param list<class-string<RuleInterface>> $ruleClasses
     */
    public function __construct(array $ruleClasses = [])
    {
        foreach ($ruleClasses as $class) {
            $this->registerInternal($class);
        }
    }

    /**
     * Register a custom rule for this factory only.
     *
     * Two forms:
     *
     *  - class-string — built lazily by createRule() from the config given for
     *    the alias in the rules array, so the rule's parameters live there. The
     *    alias is derived from the class short name (StrongPasswordRule =>
     *    strongPassword).
     *  - RuleInterface instance — an already-built rule (e.g. one carrying a
     *    dependency), keyed by its own getErrorCode() so it need not be a named
     *    class. Its config in the rules array is ignored.
     *
     * Returns true if the derived alias already resolved to another rule
     * (i.e. this registration overrode one) — the caller may then log a warning.
     *
     * @param class-string<RuleInterface>|RuleInterface $rule
     */
    public function register(string|RuleInterface $rule): bool
    {
        return $rule instanceof RuleInterface
            ? $this->registerInstance($rule)
            : $this->registerInternal($rule);
    }

    /**
     * Create a rule instance from a rule key and its config.
     *
     * Rule formats:
     *   'required' => true                    // enable with defaults
     *   'minLength' => 5                      // enable with main param
     *   'minLength' => ['min' => 5]           // full configuration array
     *   'custom' => new CustomRule()          // rule instance
     */
    public function createRule(string|int $key, mixed $config): RuleInterface
    {
        // Direct rule instance
        if ($config instanceof RuleInterface) {
            return $config;
        }

        // Numeric key means the value is a rule alias with no config
        if (is_int($key)) {
            if (!is_string($config)) {
                $type = gettype($config);
                throw new \InvalidArgumentException(
                    "List-form rule at index {$key} must be a string alias, got {$type}"
                );
            }
            return $this->instances[$config] ?? new ($this->resolve($config))();
        }

        // A registered instance wins over a class-string or a built-in of the
        // same alias — the instance is already built, so its config is moot.
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        $class = $this->resolve($key);
        $args = $this->argumentsFor($class, $config);

        try {
            return new $class(...$args);
        } catch (\TypeError $error) {
            throw $this->rewordTypeError($class, $args, $error);
        }
    }

    private function registerInternal(string $class): bool
    {
        if (!is_subclass_of($class, RuleInterface::class)) {
            throw new \InvalidArgumentException(
                "Rule {$class} must implement " . RuleInterface::class
            );
        }

        $alias = self::aliasFromClass($this->shortName($class));
        $overwritten = $this->aliasTaken($alias);

        $this->registry[$alias] = $class;

        return $overwritten;
    }

    /**
     * Register a ready-made rule under its own error code.
     *
     * The alias is getErrorCode(), not the class name: an instance need not be a
     * named class (anonymous classes and mocks are fine), and the module's
     * convention already makes the rule name and the error code one and the same.
     */
    private function registerInstance(RuleInterface $rule): bool
    {
        $alias = $rule->getErrorCode();
        $overwritten = $this->aliasTaken($alias);

        $this->instances[$alias] = $rule;

        return $overwritten;
    }

    /**
     * Whether an alias already resolves to a custom registration or a built-in,
     * so a registration under it would override something.
     */
    private function aliasTaken(string $alias): bool
    {
        return isset($this->instances[$alias])
            || isset($this->registry[$alias])
            || class_exists(self::BUILTIN_NAMESPACE . ucfirst($alias) . self::RULE_SUFFIX);
    }

    /**
     * Derive a rule alias from a rule class short name.
     */
    private static function aliasFromClass(string $shortName): string
    {
        $suffix = self::RULE_SUFFIX;
        if (str_ends_with($shortName, $suffix)) {
            $shortName = substr($shortName, 0, -strlen($suffix));
        }
        return lcfirst($shortName);
    }

    /**
     * Resolve a rule alias to a rule class. Custom registrations take priority,
     * then built-in rules are derived by name.
     *
     * @return class-string<RuleInterface>
     */
    private function resolve(string $alias): string
    {
        if (isset($this->registry[$alias])) {
            return $this->registry[$alias];
        }

        $class = self::BUILTIN_NAMESPACE . ucfirst($alias) . self::RULE_SUFFIX;
        if (!class_exists($class)) {
            throw new \InvalidArgumentException("Unknown rule: {$alias}");
        }
        return $class;
    }

    /**
     * Normalise every config style into the argument list for the rule
     * constructor:
     *
     *   true | null | []  -> []              (defaults)
     *   map array         -> named args      (keys are constructor parameter names)
     *   list array        -> positional args (array-typed first param, or one scalar)
     *   scalar            -> [coerced scalar] for the first parameter
     *
     * @param class-string<RuleInterface> $class
     * @return array<int|string, mixed>
     */
    private function argumentsFor(string $class, mixed $config): array
    {
        if ($config === true || $config === null || $config === []) {
            return [];
        }

        if (is_array($config)) {
            return $this->arrayArguments($class, $config);
        }

        return $this->scalarArguments($class, $config);
    }

    /**
     * Array config in two shapes:
     *
     *  - map form (string keys): `['minLength' => ['min' => 5]]` — keys are
     *    constructor parameter names; an unknown key is rejected.
     *  - list form (0-based keys): `['enum' => ['A', 'B']]` — positional.
     *    The list is passed as the single argument when the first parameter
     *    accepts an array (e.g. enum); otherwise a one-element list is
     *    treated as the scalar config.
     *
     * @param class-string<RuleInterface> $class
     * @param array<mixed> $config
     * @return array<int|string, mixed>
     */
    private function arrayArguments(string $class, array $config): array
    {
        if (!array_is_list($config)) {
            return $this->filterConfig($class, $config);
        }

        if ($this->acceptsArrayArgument($class)) {
            return [$config];
        }

        if (count($config) === 1) {
            return $this->scalarArguments($class, $config[0]);
        }

        throw new \InvalidArgumentException(sprintf(
            '%s takes a single argument, but list config has %d; '
            . 'use the scalar form or the named form instead.',
            $this->shortName($class),
            count($config)
        ));
    }

    /**
     * Scalar config mapped to the rule's first constructor parameter.
     *
     * @param class-string<RuleInterface> $class
     * @return array<int, mixed>
     */
    private function scalarArguments(string $class, mixed $value): array
    {
        $parameter = $this->firstParameter($class);

        if ($parameter === null) {
            // A falsy scalar is this module's established way of writing "enabled, no config": `['required' => 0]`
            // and `['required' => '']` both mean the rule is on, and testLooseFalsyValuesDoNotDisableRule pins
            // that. A truthy one is a different intent — the caller means to configure something, and there is
            // nowhere for it to go, while the map form throws on the same intent (see filterConfig). Refusing it
            // is the loud half of the same asymmetry rather than a new rule.
            // 假值标量是本模块既有的「启用、不带配置」写法：`['required' => 0]` 与 `['required' => '']` 都表示
            // 规则开着，testLooseFalsyValuesDoNotDisableRule 钉住了这一点。真值则是另一种意图——调用方是要配置
            // 点什么，而这里没有地方可去，映射形式对同样的意图会抛异常（见 filterConfig）。拒绝它，是把这处
            // 不对称补成响亮的一半，而不是另立规矩。
            if (!$value) {
                return [];
            }

            throw new \InvalidArgumentException(sprintf(
                '%s takes no constructor argument, so the config %s cannot be applied to it; '
                . 'drop the config or use a rule that accepts one.',
                $this->shortName($class),
                is_scalar($value) ? var_export($value, true) : get_debug_type($value)
            ));
        }

        return [$this->coerceScalar($parameter, $value)];
    }

    /**
     * Reword a TypeError raised by the constructor so it names the rule and the
     * config value rather than a raw PHP argument. PHP remains the authority on
     * which types fit; this only changes the wording.
     *
     * @param class-string<RuleInterface> $class
     * @param array<int|string, mixed> $args
     */
    private function rewordTypeError(string $class, array $args, \TypeError $error): \InvalidArgumentException
    {
        $parameter = $this->firstParameter($class);

        // A single positional argument can only target the first parameter, and it
        // is the one shape this factory coerces, so phrase the failure around it.
        // 单个位置参数只会命中构造器首参，且这是本工厂唯一做类型适配的形态，故围绕它表述。
        if ($parameter !== null && $args !== [] && array_is_list($args)) {
            $type = $parameter->getType();

            return new \InvalidArgumentException(sprintf(
                '%s expects %s for its first constructor argument, got %s.',
                $this->shortName($class),
                $type === null ? '(untyped)' : (string) $type,
                get_debug_type($args[0])
            ), 0, $error);
        }

        // Drop the "Class::__construct(): " prefix so the message names the rule,
        // not a fully-qualified class the caller never wrote.
        $detail = preg_replace('/^.*?::__construct\(\): /', '', $error->getMessage()) ?? $error->getMessage();

        return new \InvalidArgumentException(sprintf(
            'Invalid config for %s: %s',
            $this->shortName($class),
            $detail
        ), 0, $error);
    }

    /**
     * Whether the rule's first constructor parameter accepts an array.
     *
     * @param class-string<RuleInterface> $class
     */
    private function acceptsArrayArgument(string $class): bool
    {
        $parameter = $this->firstParameter($class);

        return $parameter !== null
            && in_array('array', $this->typeNames($parameter->getType()), true);
    }

    /**
     * Coerce a scalar config to the first constructor parameter type, so
     * numeric/bool params tolerate string config (e.g. 'minLength' => '5').
     * Anything that does not narrow is left for the constructor to accept or
     * reject.
     */
    private function coerceScalar(\ReflectionParameter $parameter, mixed $value): mixed
    {
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
     * Flatten a reflection type into its named type names, looking through union,
     * intersection and DNF types.
     *
     * @return string[]
     */
    private function typeNames(?\ReflectionType $type): array
    {
        if ($type instanceof \ReflectionNamedType) {
            return [$type->getName()];
        }
        if (!$type instanceof \ReflectionUnionType && !$type instanceof \ReflectionIntersectionType) {
            return [];
        }

        $names = [];
        foreach ($type->getTypes() as $inner) {
            $names = [...$names, ...$this->typeNames($inner)];
        }
        return $names;
    }

    /**
     * Validate a map-form config against the rule's constructor parameter names.
     * An unknown key is rejected rather than dropped, so a typo fails loudly
     * instead of silently weakening the rule into a no-op.
     *
     * @param class-string<RuleInterface> $class
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function filterConfig(string $class, array $config): array
    {
        $valid = array_map(
            static fn (\ReflectionParameter $p): string => $p->getName(),
            $this->constructorParameters($class)
        );

        $unknown = array_diff(array_keys($config), $valid);
        if ($unknown !== []) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown config key(s) for %s: %s. Valid key(s): %s.',
                $this->shortName($class),
                implode(', ', $unknown),
                $valid === [] ? '(none)' : implode(', ', $valid)
            ));
        }

        return $config;
    }

    /**
     * @param class-string<RuleInterface> $class
     * @return array<int, \ReflectionParameter>
     */
    private function constructorParameters(string $class): array
    {
        return (new \ReflectionClass($class))->getConstructor()?->getParameters() ?? [];
    }

    /**
     * @param class-string<RuleInterface> $class
     */
    private function firstParameter(string $class): ?\ReflectionParameter
    {
        return $this->constructorParameters($class)[0] ?? null;
    }

    /**
     * @param class-string<RuleInterface> $class
     */
    private function shortName(string $class): string
    {
        return (new \ReflectionClass($class))->getShortName();
    }
}
