# migears/validator

![Version](https://img.shields.io/badge/version-2.0.0-blue)

Lightweight, declarative validation library for PHP. Error-code based, i18n-ready — no hardcoded messages.

Validator provides a clean API for validating arrays (form data, API parameters, domain objects) with a simple rule syntax. Validation errors are returned as structured error codes + parameters, ready for translation via any i18n library.

> **Background**: miGears is the open-source successor of **TinyGears**, a
> self-developed PHP framework. It was renamed and open-sourced recently because
> the name *TinyGears* is already taken in the open-source community.

## Features

- **Error-code based** — no hardcoded messages, fully i18n-ready
- **23 built-in validators** — required, email, integer, number, url, date, time, money, enum, ipAddress, alpha, alphaNumeric, min, max, minLength, maxLength, pattern, equals, greaterThan, greaterOrEqualThan, lessThan, lessOrEqualThan, containUrl
- **Declarative rules** — multiple config styles: boolean, scalar, array, instance, or zero-index alias
- **Custom validators** — register by class name (alias derived) on each instance, or pass instances directly
- **Short-circuit validation** — stops at the first error per field
- **Extensible interface** — implement `ValidatorInterface` for custom rules
- **Zero dependencies** — single package, no runtime dependencies

## Boundaries

**In scope**

- The rule engine: executing declarative rule sets against arrays via `validate()` / `passes()`, returning structured error codes + params (PSR-4 under `MiGears\Validator`).
- The 23 built-in rule classes and the `ValidatorInterface` (`validate()` / `getErrorCode()` / `getErrorParams()`) for writing custom rules.
- The five rule-config styles (boolean, scalar, array map/list, validator instance, zero-index alias), including scalar coercion and a loud `InvalidArgumentException` on an unknown config key.
- Per-instance custom-validator registration, with the alias derived from the class short name and the ability to override a built-in rule.

**Not in scope (by design)**

- Translating error codes into human-readable messages — this module only produces codes + params; message interpolation and localization belong to `migears/i18n`.
- Declaring and owning the rules themselves — Domain objects declare their rules (e.g. `migears/domain`'s `Validatable` trait with `validationRules()`); the Validator only executes them.
- Presenting or delivering the result — no HTTP status, no exception, no JSON or view rendering; the caller or framework decides how to surface the errors.
- Transforming or sanitizing input — `validate()` returns only an error map, never a cleaned, filtered or type-cast value.

## Installation

```bash
composer require migears/validator
```

Requires: PHP 8.1+, ext-mbstring.

## Quick Start

```php
use MiGears\Validator\Validator;

$validator = new Validator();

$errors = $validator->validate($_POST, [
    'username' => ['required' => true, 'minLength' => 3, 'maxLength' => 20],
    'email'    => ['required' => true, 'email' => true],
    'age'      => ['integer' => true, 'min' => 0, 'max' => 150],
]);

if ($errors === []) {
    // validation passed
} else {
    // $errors = [
    //   'username' => ['rule' => 'minLength', 'params' => ['min' => 3]],
    //   'email'    => ['rule' => 'email', 'params' => []],
    // ]
}
```

### Error format

Errors are returned as structured data — error code + params, not hardcoded messages. This makes translation and programmatic handling easy.

> 💡 `validate()` returns an **error array**, not a boolean. An empty array means all rules passed. Use `$validator->passes($data, $rules)` when you just need a boolean result — `if ($v->validate(...))` is always truthy when there is at least one error, which is the opposite of what the name suggests.

```php
[
    'username' => ['rule' => 'minLength', 'params' => ['min' => 3]],
    'email'    => ['rule' => 'email',     'params' => []],
]
```

## Handling Errors

Validator produces data — you decide how to present it. Below are three common patterns.

### 1. Server-side rendering (with migears/i18n)

Translate errors to user-friendly messages in your controller/handler:

```php
use MiGears\I18n\ArrayTranslator;

$translator = new ArrayTranslator('en', [
    'validation.required'  => '{field} is required',
    'validation.minLength' => '{field} must be at least {min} characters',
    'validation.maxLength' => '{field} must not exceed {max} characters',
    'validation.email'     => '{field} must be a valid email address',
    'validation.min'       => '{field} must be at least {min}',
    'validation.max'       => '{field} must not exceed {max}',
    'validation.integer'   => '{field} must be an integer',
    'validation.url'       => '{field} must be a valid URL',
    'validation.pattern'   => '{field} format is invalid',
]);

$fieldLabels = [
    'username' => 'Username',
    'email'    => 'Email address',
];

$errors = $validator->validate($_POST, $rules);

if ($errors !== []) {
    $messages = [];
    foreach ($errors as $field => $error) {
        $messages[$field] = $translator->get(
            "validation.{$error['rule']}",
            ['field' => $fieldLabels[$field] ?? $field, ...$error['params']]
        );
    }

    // Pass to view: messages + old input for repopulation
    return $view->render('form', [
        'errors' => $messages,
        'old'    => $_POST,
    ]);
}
```

### 2. API / Frontend-backend separation

Return errors as-is — let the frontend handle translation:

```php
// Backend controller
$errors = $validator->validate($requestBody, $rules);

if ($errors !== []) {
    return $response->json([
        'error'  => 'validation_failed',
        'errors' => $errors,
    ], 422);
}
```

Frontend receives structured errors and translates them with its own i18n library:

```javascript
// Frontend (React/Vue/etc.)
const fieldLabels = { username: '用户名', email: '邮箱' };

const messages = {};
for (const [field, err] of Object.entries(data.errors)) {
  messages[field] = t(`validation.${err.rule}`, {
    field: fieldLabels[field] || field,
    ...err.params,
  });
}
```

### 3. Programmatic handling (CLI / services)

Use error codes for logic decisions:

```php
$errors = $validator->validate($input, $rules);

if (isset($errors['email'])) {
    match ($errors['email']['rule']) {
        'required' => $logger->warning('Email missing for user registration'),
        'email'    => $logger->warning('Invalid email format'),
        default    => $logger->warning('Email validation failed'),
    };
}

if (isset($errors['username']) && $errors['username']['rule'] === 'minLength') {
    $min = $errors['username']['params']['min'];
    $logger->info("Username too short, minimum is {$min}");
}
```

### With Domain Objects

Use `migears/domain`'s `Validatable` trait for self-validating domain objects:

```php
class UserDomain
{
    use \MiGears\Domain\Validatable;

    public function __construct(
        public readonly string $username,
        public readonly string $email,
    ) {}

    protected static function validationRules(): array
    {
        return [
            'username' => ['required' => true, 'minLength' => 3],
            'email'    => ['required' => true, 'email' => true],
        ];
    }
}

$user = UserDomain::fromArray($_POST);
$errors = $user->validate();
```

## Rule Configuration

### Boolean — enable with defaults
```php
['required' => true, 'email' => true]
```

> ⚠️ `true` always means "enable with default config", never "set the parameter to `true`". Three consequences:
> - To pass `true` to a boolean parameter use the array form: `['containUrl' => ['invert' => true]]`.
> - Some rules run with a useless default when keyed `true`. `['pattern' => true]` uses the empty pattern and matches everything. `['equals' => true]` compares against `null`, so it **almost always fails** for any non-empty value. `['enum' => true]` enables enum with an empty allowed set, so it **rejects every non-empty value**. To actually enforce a pattern/equality/set, pass an explicit scalar or list.
> - Only strict `false` disables a rule: `['required' => false]` skips the rule. Loose falsy values like `0`, `''`, or `null` do **not** disable; they are treated as scalar config and may enable the rule.

### Scalar — set the main parameter
```php
['minLength' => 5, 'max' => 100]
```
Scalar values are coerced to the validator's parameter type, so string numerics also work: `['minLength' => '5']`, `['containUrl' => 1]`.

### Array — named form (constructor parameter names)
```php
['minLength' => ['min' => 5], 'pattern' => ['pattern' => '/^[a-z]+$/']]
```
Keys are the validator's constructor parameter names. An unknown key raises `InvalidArgumentException` listing the valid keys, so a typo fails loudly instead of silently turning the rule into a no-op.

### Array — list form (positional values)
```php
['enum' => ['A', 'B']]          // allowed set, same as ['enum' => 'A|B']
['pattern' => ['/^[a-z]+$/']]   // same as ['pattern' => '/^[a-z]+$/']
['minLength' => [5]]            // same as ['minLength' => 5]
```
When the validator's first parameter accepts an array (e.g. `enum`), the whole list becomes that argument. Otherwise a one-element list is treated as the scalar config (coercion applies), and a longer list raises `InvalidArgumentException`.

A config with nowhere to go is refused rather than dropped: a truthy scalar or one-element list handed to a validator whose constructor takes no argument raises `InvalidArgumentException`, the same loud failure an unknown key gets. Loose falsy values (`0`, `''`) remain the way to write "enabled, no config".

### Instance — pass a validator directly
```php
['custom' => new MyCustomValidator()]
```

### Zero-index alias — enable a rule by name only
```php
[0 => 'required', 1 => 'email']
```

Rules in this array form take a string alias as the value and apply the rule with its default configuration. A field's rules value must be an array of rules; a non-array value such as `['name' => 'required']` raises `InvalidArgumentException` instead of validating nothing.

### Empty-value semantics

Every built-in validator (except `required`) treats a `null` or blank-string value as valid — i.e. it is skipped. Only `required` can force a field to be present. For example `['email' => true]` passes when `email` is absent; add `'required' => true` to make it mandatory.

## Built-in Validators

| Rule | Params | Description |
|------|--------|-------------|
| `required` | `[]` | Value cannot be null, empty string, or empty array |
| `email` | `[]` | Valid email address |
| `integer` | `[]` | Integer or integer string |
| `number` | `[]` | Numeric value (int, float, or numeric string) |
| `min` | `{min}` | Numeric value ≥ min |
| `max` | `{max}` | Numeric value ≤ max |
| `minLength` | `{min}` | Minimum string length (multibyte-safe) |
| `maxLength` | `{max}` | Maximum string length (multibyte-safe) |
| `pattern` | `{pattern}` | Regex pattern match |
| `url` | `[]` | Valid URL |
| `date` | `[]` | Real, valid calendar date in `YYYY-M-D` format |
| `time` | `[]` | Real time in `H:M(:S)` format (0–23, 0–59)|
| `money` | `[]` | Money amount: zero or positive decimal with ≤ 2 places |
| `enum` | `{allowed}` | Value is one of the allowed values (array or `a\|b` string) |
| `ipAddress` | `[]` | Valid IPv4 or IPv6 address |
| `alpha` | `[]` | Alphabetic characters only (`a-zA-Z`) |
| `alphaNumeric` | `[]` | Alphanumeric characters only (`a-zA-Z0-9`) |
| `equals` | `{expected}` | Strictly equals the expected value |
| `greaterThan` | `{threshold}` | Numeric value > threshold |
| `greaterOrEqualThan` | `{threshold}` | Numeric value ≥ threshold |
| `lessThan` | `{threshold}` | Numeric value < threshold |
| `lessOrEqualThan` | `{threshold}` | Numeric value ≤ threshold |
| `containUrl` | `{invert}` | Contains a URL (inverted when `invert` is true) |

> The rule name and the error code are the same; the `Params` column shows the entries returned in the error, i.e. the interpolated variables for i18n messages. `alpha` and `alphaNumeric` are ASCII-only — they do not accept accented or CJK (e.g. Chinese) characters.

## Custom Validators

Implement `ValidatorInterface`:

```php
use MiGears\Validator\ValidatorInterface;

final class StrongPasswordValidator implements ValidatorInterface
{
    public function validate(mixed $value): bool
    {
        return is_string($value)
            && strlen($value) >= 8
            && preg_match('/[A-Z]/', $value)
            && preg_match('/[0-9]/', $value);
    }

    public function getErrorCode(): string
    {
        return 'strongPassword';
    }

    public function getErrorParams(): array
    {
        return [];
    }
}
```

Register and use — custom rules are scoped to each `Validator` instance:

```php
use MiGears\Validator\Validator;

$validator = new Validator();
$validator->register(StrongPasswordValidator::class);

// true if it overrode an existing rule (e.g. replacing a built-in) — log a
// warning in that case if you care
if ($validator->register(\MiGears\Validator\Validators\EmailValidator::class)) {
    // overwritten an existing rule
}

$errors = $validator->validate($data, [
    'password' => ['required' => true, 'strongPassword' => true],
]);
```

The rule alias is derived from the class short name: `StrongPasswordValidator` → `strongPassword`. To replace a built-in rule, name your class to collide with it (e.g. `EmailValidator` in your own namespace overrides `email`). Because registration is per instance, custom rules never leak into other validation contexts.

You can also pre-register validators in the constructor for a ready-to-use instance:
```php
$validator = new Validator([
    StrongPasswordValidator::class,
    CustomDomainValidators\EmailValidator::class, // override built-in `email`
]);
```

## API Reference

| Method | Description |
|--------|-------------|
| `new Validator(array $validators = [])` | Create a validator instance, optionally pre-registering custom validator classes in one shot |
| `validate(array $data, array $rules): array` | Validate data, return errors |
| `passes(array $data, array $rules): bool` | Check if validation passes |
| `$validator->register(class-string $class): bool` | Register a custom validator on this instance; alias derived from class name, returns `true` if it overrode an existing rule |

## Design Philosophy

miGears Validator follows the miGears philosophy: **minimal, readable, and useful**.

- **Error codes, not messages** — i18n is not an afterthought, it's built-in
- **Simple interface** — one interface with three methods
- **Short-circuit by default** — one error per field, fail fast
- **No magic** — no annotations, reflection used internally only for config coercion
- **Small enough to read** — ~1,300 lines total

## License

MIT

---

# migears/validator

![Version](https://img.shields.io/badge/version-2.0.0-blue)

轻量级声明式 PHP 验证库。基于错误码，i18n 友好 —— 没有硬编码的消息。

Validator 提供简洁的 API 来验证数组（表单数据、API 参数、领域对象），使用简单的规则语法。验证错误以结构化的错误码 + 参数形式返回，可直接通过任何 i18n 库进行翻译。

## 特性

- **基于错误码** — 没有硬编码消息，完全 i18n 就绪
- **23 个内置验证器** — required、email、integer、number、url、date、time、money、enum、ipAddress、alpha、alphaNumeric、min、max、minLength、maxLength、pattern、equals、greaterThan、greaterOrEqualThan、lessThan、lessOrEqualThan、containUrl
- **声明式规则** — 多种配置方式：布尔值、标量、数组、实例、零索引别名
- **自定义验证器** — 在每个实例上按类名注册（别名自动推导）或直接传入实例
- **短路验证** — 每个字段遇到第一个错误即停止
- **可扩展接口** — 实现 `ValidatorInterface` 自定义规则
- **零依赖** — 单个包，无任何运行时依赖

## 边界

**范围内**

- 规则引擎：通过 `validate()` / `passes()` 对数组执行声明式规则集，返回结构化的错误码 + 参数（PSR-4 根为 `MiGears\Validator`）。
- 23 个内置规则类，以及用于编写自定义规则的 `ValidatorInterface`（`validate()` / `getErrorCode()` / `getErrorParams()`）。
- 五种规则配置形态（布尔值、标量、数组命名/列表、验证器实例、零索引别名），含标量类型适配，以及未知配置键时抛出的 `InvalidArgumentException`。
- 基于实例的自定义验证器注册，别名由类短名推导，并可覆盖内置规则。

**范围外（刻意不做）**

- 把错误码翻译成人类可读的消息 —— 本模块只产出错误码 + 参数；消息插值与本地化属于 `migears/i18n`。
- 声明与持有规则本身 —— 规则由领域对象声明（例如 `migears/domain` 的 `Validatable` trait 配合 `validationRules()`），Validator 只负责执行。
- 呈现或投递结果 —— 不做 HTTP 状态码、不抛异常、不做 JSON 或视图渲染；如何暴露错误由调用方或框架决定。
- 转换或清洗输入 —— `validate()` 只返回错误映射，绝不返回被清洗、过滤或类型转换后的值。

## 安装

```bash
composer require migears/validator
```

要求：PHP 8.1+，ext-mbstring。

## 快速开始

```php
use MiGears\Validator\Validator;

$validator = new Validator();

$errors = $validator->validate($_POST, [
    'username' => ['required' => true, 'minLength' => 3, 'maxLength' => 20],
    'email'    => ['required' => true, 'email' => true],
    'age'      => ['integer' => true, 'min' => 0, 'max' => 150],
]);

if ($errors === []) {
    // 验证通过
} else {
    // $errors = [
    //   'username' => ['rule' => 'minLength', 'params' => ['min' => 3]],
    //   'email'    => ['rule' => 'email', 'params' => []],
    // ]
}
```

### 错误格式

错误以结构化数据返回 —— 错误码 + 参数，而非硬编码消息。这样翻译和程序化处理都很方便。

> 💡 `validate()` 返回的是**错误数组**，不是布尔值。空数组表示全部通过。只需布尔结果时请用 `$validator->passes($data, $rules)` —— 注意 `if ($v->validate(...))` 在有错误时恒为真，语义与字面直觉相反。

```php
[
    'username' => ['rule' => 'minLength', 'params' => ['min' => 3]],
    'email'    => ['rule' => 'email',     'params' => []],
]
```

## 错误处理

Validator 只产出数据 —— 如何展示由你决定。以下是三种常见模式。

### 1. 服务端渲染（配合 migears/i18n）

在控制器/处理器中将错误翻译为用户友好的消息：

```php
use MiGears\I18n\ArrayTranslator;

$translator = new ArrayTranslator('zh', [
    'validation.required'  => '{field} 不能为空',
    'validation.minLength' => '{field} 长度不能少于 {min} 个字符',
    'validation.maxLength' => '{field} 长度不能超过 {max} 个字符',
    'validation.email'     => '{field} 格式不正确',
    'validation.min'       => '{field} 不能小于 {min}',
    'validation.max'       => '{field} 不能大于 {max}',
    'validation.integer'   => '{field} 必须是整数',
    'validation.url'       => '{field} 格式不正确',
    'validation.pattern'   => '{field} 格式不正确',
]);

$fieldLabels = [
    'username' => '用户名',
    'email'    => '邮箱',
];

$errors = $validator->validate($_POST, $rules);

if ($errors !== []) {
    $messages = [];
    foreach ($errors as $field => $error) {
        $messages[$field] = $translator->get(
            "validation.{$error['rule']}",
            ['field' => $fieldLabels[$field] ?? $field, ...$error['params']]
        );
    }

    // 传给视图：错误消息 + 旧数据回填
    return $view->render('form', [
        'errors' => $messages,
        'old'    => $_POST,
    ]);
}
```

### 2. API / 前后端分离

原样返回错误 —— 让前端处理翻译：

```php
// 后端控制器
$errors = $validator->validate($requestBody, $rules);

if ($errors !== []) {
    return $response->json([
        'error'  => 'validation_failed',
        'errors' => $errors,
    ], 422);
}
```

前端收到结构化错误后，用自己的 i18n 库翻译：

```javascript
// 前端（React/Vue 等）
const fieldLabels = { username: '用户名', email: '邮箱' };

const messages = {};
for (const [field, err] of Object.entries(data.errors)) {
  messages[field] = t(`validation.${err.rule}`, {
    field: fieldLabels[field] || field,
    ...err.params,
  });
}
```

### 3. 程序化处理（CLI / 服务层）

用错误码做逻辑判断：

```php
$errors = $validator->validate($input, $rules);

if (isset($errors['email'])) {
    match ($errors['email']['rule']) {
        'required' => $logger->warning('用户注册缺少邮箱'),
        'email'    => $logger->warning('邮箱格式不正确'),
        default    => $logger->warning('邮箱验证失败'),
    };
}

if ($errors['username']['rule'] === 'minLength') {
    $min = $errors['username']['params']['min'];
    $logger->info("用户名太短，最少需要 {$min} 个字符");
}
```

### 与领域对象配合

使用 `migears/domain` 的 `Validatable` trait 实现自验证领域对象：

```php
class UserDomain
{
    use \MiGears\Domain\Validatable;

    public function __construct(
        public readonly string $username,
        public readonly string $email,
    ) {}

    protected static function validationRules(): array
    {
        return [
            'username' => ['required' => true, 'minLength' => 3],
            'email'    => ['required' => true, 'email' => true],
        ];
    }
}

$user = UserDomain::fromArray($_POST);
$errors = $user->validate();
```

## 规则配置

### 布尔值 — 启用默认配置
```php
['required' => true, 'email' => true]
```

> ⚠️ `true` 始终表示"以默认配置启用"，绝不表示"把参数设为 true"。三个后果：
> - 要传 `true` 给布尔参数请用数组形式：`['containUrl' => ['invert' => true]]`。
> - 某些规则配 `true` 时会使用无意义的默认值：`['pattern' => true]` 使用空正则，什么都匹配；`['equals' => true]` 与 `null` 比较，对任意非空值**几乎必然失败**；`['enum' => true]` 以空允许集启用，**拒绝一切非空值**。要真正校验格式/相等/枚举集合，请传明确的标量或列表。
> - 只有严格 `false` 才禁用规则：`['required' => false]` 会跳过该规则。`0`、`''`、`null` 等松散假值**不会**禁用，它们被当作标量配置处理，可能反而启用规则。

### 标量值 — 设置主要参数
```php
['minLength' => 5, 'max' => 100]
```
标量值会按验证器参数类型自动适配，因此数字字符串也可用：`['minLength' => '5']`、`['containUrl' => 1]`。

### 数组 — 命名形态（构造参数名作键）
```php
['minLength' => ['min' => 5], 'pattern' => ['pattern' => '/^[a-z]+$/']]
```
键为验证器的构造参数名。未知键会抛 `InvalidArgumentException` 并列出合法键，因此拼写错误会立刻报错，而不是把规则静默变成空操作。

### 数组 — 列表形态（位置取值）
```php
['enum' => ['A', 'B']]          // 允许集，等价于 ['enum' => 'A|B']
['pattern' => ['/^[a-z]+$/']]   // 等价于 ['pattern' => '/^[a-z]+$/']
['minLength' => [5]]            // 等价于 ['minLength' => 5]
```
当验证器的首参接受数组（如 `enum`）时，整个列表作为该参数传入；否则单元素列表按标量配置处理（含类型适配），多元素列表则抛 `InvalidArgumentException`。

无处可去的配置会被拒绝，而不是被丢弃：把真值标量或单元素列表交给一个构造器不接受任何参数的验证器，会抛 `InvalidArgumentException`，与未知键得到的是同一种响亮失败。假值（`0`、`''`）仍照旧写作「启用、不带配置」。

### 实例 — 直接传入验证器
```php
['custom' => new MyCustomValidator()]
```

### 零索引别名 — 仅按名称启用规则
```php
[0 => 'required', 1 => 'email']
```

此形态的值为字符串规则名，使用默认配置启用该规则。字段的规则取值必须是规则数组；像 `['name' => 'required']` 这样的非数组取值会抛 `InvalidArgumentException`，而不是什么都不校验。

### 空值语义

内置所有验证器（`required` 除外）都把 `null` 或空白字符串视为合法——即自动跳过。只有 `required` 能强制字段必填。例如 `['email' => true]` 在缺少 `email` 时通过；要强制必填需加上 `'required' => true`。

## 内置验证器

| 规则 | 参数 | 说明 |
|------|------|------|
| `required` | `[]` | 值不能为 null、空字符串或空数组 |
| `email` | `[]` | 有效的邮箱地址 |
| `integer` | `[]` | 整数或整数字符串 |
| `number` | `[]` | 数值（int、float 或数字字符串） |
| `min` | `{min}` | 数值 ≥ min |
| `max` | `{max}` | 数值 ≤ max |
| `minLength` | `{min}` | 最小字符串长度（多字节安全） |
| `maxLength` | `{max}` | 最大字符串长度（多字节安全） |
| `pattern` | `{pattern}` | 正则表达式匹配 |
| `url` | `[]` | 有效的 URL |
| `date` | `[]` | 真实合法的日历日期，`YYYY-M-D` 格式 |
| `time` | `[]` | 真实合法的时间，`H:M(:S)` 格式（0–23、0–59）|
| `money` | `[]` | 金额：0 或最多两位小数的正数 |
| `enum` | `{allowed}` | 值在允许集合内（数组或 `a\|b` 字符串） |
| `ipAddress` | `[]` | 有效的 IPv4 或 IPv6 地址 |
| `alpha` | `[]` | 仅英文字母（`a-zA-Z`） |
| `alphaNumeric` | `[]` | 仅字母数字（`a-zA-Z0-9`） |
| `equals` | `{expected}` | 与期望值严格相等 |
| `greaterThan` | `{threshold}` | 数值 > threshold |
| `greaterOrEqualThan` | `{threshold}` | 数值 ≥ threshold |
| `lessThan` | `{threshold}` | 数值 < threshold |
| `lessOrEqualThan` | `{threshold}` | 数值 ≤ threshold |
| `containUrl` | `{invert}` | 包含 URL（`invert` 为 true 时取反） |

> 规则名即错误码；「参数」列是出错时返回的字段，即 i18n 消息用于插值的变量。`alpha` 与 `alphaNumeric` 仅支持 ASCII，不接受带重音或 CJK（如中文）字符。

## 自定义验证器

实现 `ValidatorInterface`：

```php
use MiGears\Validator\ValidatorInterface;

final class StrongPasswordValidator implements ValidatorInterface
{
    public function validate(mixed $value): bool
    {
        return is_string($value)
            && strlen($value) >= 8
            && preg_match('/[A-Z]/', $value)
            && preg_match('/[0-9]/', $value);
    }

    public function getErrorCode(): string
    {
        return 'strongPassword';
    }

    public function getErrorParams(): array
    {
        return [];
    }
}
```

注册并使用——自定义规则只会作用在当前 `Validator` 实例上：

```php
use MiGears\Validator\Validator;

$validator = new Validator();
$validator->register(StrongPasswordValidator::class);

// 若返回 true，表示覆盖了已有的规则（例如替换内置规则），此时可酌情记录 warn
if ($validator->register(\MiGears\Validator\Validators\EmailValidator::class)) {
    // 覆盖了已有规则
}

$errors = $validator->validate($data, [
    'password' => ['required' => true, 'strongPassword' => true],
]);
```

规则别名由类短名推导：`StrongPasswordValidator` → `strongPassword`。若要覆盖内置规则，把外部类命名成与之重名即可（例如自己命名一个 `EmailValidator` 就能覆盖内置的 `email`）。因为注册是基于实例的，自定义规则不会泄漏到其它验证场景。

也可以在构造器里一次性预注册，得到一个开箱即用的实例：
```php
$validator = new Validator([
    StrongPasswordValidator::class,
    CustomDomainValidators\EmailValidator::class, // 覆盖内置 `email`
]);
```

## API 参考

| 方法 | 说明 |
|------|------|
| `new Validator(array $validators = [])` | 创建验证器实例，可选地在构造时一次性预注册自定义验证器类 |
| `validate(array $data, array $rules): array` | 验证数据，返回错误 |
| `passes(array $data, array $rules): bool` | 检查验证是否通过 |
| `$validator->register(class-string $class): bool` | 在当前实例注册自定义验证器；别名由类名推导，返回 `true` 表示覆盖了已有规则 |

## 设计哲学

miGears Validator 遵循 miGears 设计哲学：**极简、可读、实用**。

- **错误码，不是消息** — i18n 不是事后考虑，而是内置设计
- **简单接口** — 一个接口，三个方法
- **默认短路** — 每个字段一个错误，快速失败
- **没有魔法** — 没有注解，反射仅内部用于配置适配
- **小到可以读完** — 总共约 1,300 行代码

## 许可证

MIT
