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

if (isset($errors['username']['rule']) === 'minLength') {
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

> ⚠️ `true` always means "enable with default config" — never "set the parameter to `true`". Two consequences:
> - To pass `true` to a boolean parameter use the array form: `['containUrl' => ['invert' => true]]`.
> - Some rules silently pass with their default when keyed `true`. `['pattern' => true]` uses the empty default pattern, and `['equals' => true]` compares against `null` — both will almost always pass. If you intend to enforce a pattern/equality, pass an explicit scalar (`['pattern' => '/.../']`, `['equals' => 'x']`).

### Scalar — set the main parameter
```php
['minLength' => 5, 'max' => 100]
```
Scalar values are coerced to the validator's parameter type, so string numerics also work: `['minLength' => '5']`, `['containUrl' => 1]`.

### Array — full configuration
```php
['minLength' => ['min' => 5], 'pattern' => ['pattern' => '/^[a-z]+$/']]
```

### Instance — pass a validator directly
```php
['custom' => new MyCustomValidator()]
```

### Zero-index alias — enable a rule by name only
```php
[0 => 'required', 1 => 'email']
```

Rules in this array form take a string alias as the value and apply the rule with its default configuration.

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
if ($validator->register(EqualPasswordStrengthValidator::class)) {
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
- **No magic** — no annotations, no reflection for normal usage
- **Small enough to read** — ~350 lines total

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

> ⚠️ `true` 始终表示"以默认配置启用"，绝不表示"把参数设为 true"。两个后果：
> - 要传 `true` 给布尔参数请用数组形式：`['containUrl' => ['invert' => true]]`。
> - 某些规则配 `true` 时用默认值静默通过。`['pattern' => true]` 使用空的默认正则，`['equals' => true]` 会与 `null` 比较——二者几乎必然通过。若要真正校验格式/相等，请传明确的标量（`['pattern' => '/.../']`、`['equals' => 'x']`）。

### 标量值 — 设置主要参数
```php
['minLength' => 5, 'max' => 100]
```
标量值会按验证器参数类型自动适配，因此数字字符串也可用：`['minLength' => '5']`、`['containUrl' => 1]`。

### 数组 — 完整配置
```php
['minLength' => ['min' => 5], 'pattern' => ['pattern' => '/^[a-z]+$/']]
```

### 实例 — 直接传入验证器
```php
['custom' => new MyCustomValidator()]
```

### 零索引别名 — 仅按名称启用规则
```php
[0 => 'required', 1 => 'email']
```

此形态的值为字符串规则名，使用默认配置启用该规则。

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
if ($validator->register(EqualPasswordStrengthValidator::class)) {
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
- **没有魔法** — 没有注解，正常使用不需要反射
- **小到可以读完** — 总共约 350 行代码

## 许可证

MIT
