# migears/validator

![Version](https://img.shields.io/badge/version-2.0.0-blue)

Lightweight, declarative validation library for PHP. Error-code based, i18n-ready — no hardcoded messages.

Validator provides a clean API for validating arrays (form data, API parameters, domain objects) with a simple rule syntax. Validation errors are returned as structured error codes + parameters, ready for translation via any i18n library.

## Features

- **Error-code based** — no hardcoded messages, fully i18n-ready
- **9 built-in validators** — required, email, integer, url, min, max, minLength, maxLength, pattern
- **Declarative rules** — three config styles: boolean, scalar, or array
- **Custom validators** — register with an alias or pass instances directly
- **Short-circuit validation** — stops at the first error per field
- **Extensible interface** — implement `ValidatorInterface` for custom rules
- **Zero dependencies** — single package, ~350 lines total

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

### Scalar — set the main parameter
```php
['minLength' => 5, 'max' => 100]
```

### Array — full configuration
```php
['minLength' => ['min' => 5], 'pattern' => ['pattern' => '/^[a-z]+$/']]
```

### Instance — pass a validator directly
```php
['custom' => new MyCustomValidator()]
```

## Built-in Validators

| Rule | Error Code | Params | Description |
|------|-----------|--------|-------------|
| `required` | `required` | `[]` | Value cannot be null, empty string, or empty array |
| `email` | `email` | `[]` | Valid email format |
| `integer` | `integer` | `[]` | Integer or integer string |
| `url` | `url` | `[]` | Valid URL format |
| `min` | `min` | `{min}` | Numeric minimum value |
| `max` | `max` | `{max}` | Numeric maximum value |
| `minLength` | `minLength` | `{min}` | Minimum string length (multibyte-safe) |
| `maxLength` | `maxLength` | `{max}` | Maximum string length (multibyte-safe) |
| `pattern` | `pattern` | `{pattern}` | Regex pattern match |

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

Register and use:

```php
Validator::register('strongPassword', StrongPasswordValidator::class);

$errors = $validator->validate($data, [
    'password' => ['required' => true, 'strongPassword' => true],
]);
```

## API Reference

| Method | Description |
|--------|-------------|
| `new Validator()` | Create a new validator instance |
| `validate(array $data, array $rules): array` | Validate data, return errors |
| `passes(array $data, array $rules): bool` | Check if validation passes |
| `Validator::register(string $alias, string $class)` | Register custom validator |

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
- **9 个内置验证器** — required、email、integer、url、min、max、minLength、maxLength、pattern
- **声明式规则** — 三种配置方式：布尔值、标量、数组
- **自定义验证器** — 注册别名或直接传入实例
- **短路验证** — 每个字段遇到第一个错误即停止
- **可扩展接口** — 实现 `ValidatorInterface` 自定义规则
- **零依赖** — 单个包，总共约 350 行

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

### 标量值 — 设置主要参数
```php
['minLength' => 5, 'max' => 100]
```

### 数组 — 完整配置
```php
['minLength' => ['min' => 5], 'pattern' => ['pattern' => '/^[a-z]+$/']]
```

### 实例 — 直接传入验证器
```php
['custom' => new MyCustomValidator()]
```

## 内置验证器

| 规则 | 错误码 | 参数 | 说明 |
|------|--------|------|------|
| `required` | `required` | `[]` | 值不能为 null、空字符串或空数组 |
| `email` | `email` | `[]` | 有效的邮箱格式 |
| `integer` | `integer` | `[]` | 整数或整数字符串 |
| `url` | `url` | `[]` | 有效的 URL 格式 |
| `min` | `min` | `{min}` | 数值最小值 |
| `max` | `max` | `{max}` | 数值最大值 |
| `minLength` | `minLength` | `{min}` | 最小字符串长度（多字节安全） |
| `maxLength` | `maxLength` | `{max}` | 最大字符串长度（多字节安全） |
| `pattern` | `pattern` | `{pattern}` | 正则表达式匹配 |

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

注册并使用：

```php
Validator::register('strongPassword', StrongPasswordValidator::class);

$errors = $validator->validate($data, [
    'password' => ['required' => true, 'strongPassword' => true],
]);
```

## API 参考

| 方法 | 说明 |
|------|------|
| `new Validator()` | 创建新的验证器实例 |
| `validate(array $data, array $rules): array` | 验证数据，返回错误 |
| `passes(array $data, array $rules): bool` | 检查验证是否通过 |
| `Validator::register(string $alias, string $class)` | 注册自定义验证器 |

## 设计哲学

miGears Validator 遵循 miGears 设计哲学：**极简、可读、实用**。

- **错误码，不是消息** — i18n 不是事后考虑，而是内置设计
- **简单接口** — 一个接口，三个方法
- **默认短路** — 每个字段一个错误，快速失败
- **没有魔法** — 没有注解，正常使用不需要反射
- **小到可以读完** — 总共约 350 行代码

## 许可证

MIT
