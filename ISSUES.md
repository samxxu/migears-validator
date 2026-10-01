# migears-validator — Known Issues

> Summary of this module's issues. The items themselves are in [`issues/`](issues/README.md), one file
> per item: a front-matter header and a thread. This file is generated from them and can be rewritten at
> any time; edit an item, never this file.
>
> From the miGears Full-Module Code Review Report (6th round, 2026-10-01).

| | |
|---|---|
| Status | **P2 open** |
| Size | src 885 lines (net) · 110 tests · 25 src files |

Legend — **P0** functional or security · **P1** documentation that fails when copied · **P2** robustness · **P3** metadata and docs

## At a glance

| | |
|---|---|
| Unsettled | P0 0 · P1 0 · P2 2 · P3 0 · other 0 |
| Settled | 8 of 10 |
| Waiting on the owner | _nothing_ |
| Waiting on the coordinator | _nothing_ |
| Waiting on the reviewer | `P2-3`, `P2-6` |
| Deferred, owing nobody | _nothing_ |

| id | level | status | title |
|---|---|---|---|
| [`P2-1`](issues/P2-1.md) | P2 | **verified** | The list-shaped config path passes the array straight to the … |
| [`P2-2`](issues/P2-2.md) | P2 | **verified** | Map-form config silently discards unknown keys via … |
| [`P2-3`](issues/P2-3.md) | P2 | **rejected** | Validator::createValidator() treats null config the same as true (both … |
| [`P2-4`](issues/P2-4.md) | P2 | **verified** | The `enum` pipe form silently drops the value `0`: a bare … |
| [`P2-5`](issues/P2-5.md) | P2 | **verified** | A scalar config that cannot be coerced leaks a raw `TypeError` out of … |
| [`P2-6`](issues/P2-6.md) | P2 | **fixed** | validate() assumes each field’s rules value is an array. Given a string … |
| [`P3-1`](issues/P3-1.md) | P3 | **verified** | The module's ISSUES.md asserts that PHP warnings fail the suite here, … |
| [`P3-2`](issues/P3-2.md) | P3 | **verified** | README claims '~1,200 lines total' but src/Validator.php alone is 358 … |
| [`P3-3`](issues/P3-3.md) | P3 | **verified** | A scalar or list config on a validator with no constructor parameters … |
| [`P3-4`](issues/P3-4.md) | P3 | **verified** | `README.md` calls `register(EqualPasswordStrengthValidator::class)` in … |

## Unclosed

What is left to do here: every item whose `status` is not `verified` or `closed`,
highest severity first. `waiting on` is the party who acts next, read from that status.

| | |
|---|---|
| Unclosed | **2** of 10 |
| By status | `rejected` 1 · `fixed` 1 |
| Waiting on | reviewer 2 |

| level | item | status | waiting on | title |
|---|---|---|---|---|
| **P2** | [`P2-3`](issues/P2-3.md) | `rejected` | reviewer | Validator::createValidator() treats null config the same as true (both … |
| **P2** | [`P2-6`](issues/P2-6.md) | `fixed` | reviewer | validate() assumes each field’s rules value is an array. Given a string … |

## Verdict

The fail-loudly policy is now carried out on every config path the round tested — except one, the field’s rules value itself, which is where the remaining hole is.

## Fixed since the last round

P2-5, P3-2, P3-3 and P3-4 all verified: an uncoercible scalar config now raises the module’s own exception instead of a raw TypeError, the size claim is ~1,300 against a measured 1,307, a validator with no constructor parameter refuses a truthy scalar, and the README names a class that exists.

## Test gaps

No test passes a non-array rules value to validate(), which is why the finding below escaped; the README’s size claim has no behavioural path to pin it.

## Verification protocol

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- Warning/notice/deprecation/risky flags in `phpunit.xml.dist`: all four on
- A PHP warning counts as a test failure only where those flags are on; otherwise run `./vendor/bin/phpunit --fail-on-warning` explicitly.


---

# migears-validator — 已知问题

> 本模块问题的概览。条目本体在 [`issues/`](issues/README.md)，一条目一文件：前置字段加讨论串。
> 本文件由条目生成，随时可以整段重写；请改条目，不要改本文件。
>
> 出自 miGears 全模块代码评审报告（6th round，2026-10-01）。

| | |
|---|---|
| 状态 | **P2 待修** |
| 体量 | src 885 行（净）· 110 个用例 · 25 个源文件 |

级别说明 — **P0** 功能性或安全级 · **P1** 文档照抄即错 · **P2** 健壮性 · **P3** 元数据与文档

## 状态一览

| | |
|---|---|
| 未了结 | P0 0 · P1 0 · P2 2 · P3 0 · 其他 0 |
| 已了结 | 8 / 10 |
| 等模块主 | _无_ |
| 等协调人 | _无_ |
| 等评审方 | `P2-3`, `P2-6` |
| 已暂缓，不欠谁 | _无_ |

| id | 级别 | 状态 | 标题 |
|---|---|---|---|
| [`P2-1`](issues/P2-1.md) | P2 | **verified** | 列表形态配置会把数组直接传给构造器：["pattern" => ["/^[a-z]+$/"]] 抛 TypeError: … |
| [`P2-2`](issues/P2-2.md) | P2 | **verified** | 映射形态配置用 array_intersect_key 静默丢弃未知键，因此拼错会让规则变弱而不是报错：["minLength" => … |
| [`P2-3`](issues/P2-3.md) | P2 | **rejected** | Validator::createValidator() 将 null 配置与 true 同等对待（都调用无参构造器）。README … |
| [`P2-4`](issues/P2-4.md) | P2 | **verified** | `enum` 的竖线形式会静默丢掉取值 `0`：裸 `array_filter()` 丢弃 `"0"`，因此 `'0|1'` 只允许 … |
| [`P2-5`](issues/P2-5.md) | P2 | **verified** | 无法强制转换的标量配置会把原始 `TypeError` … |
| [`P2-6`](issues/P2-6.md) | P2 | **fixed** | validate() 假定每个字段的规则取值是数组。传入字符串时它抛出裸 PHP 警告（"foreach() argument must be … |
| [`P3-1`](issues/P3-1.md) | P3 | **verified** | 本模块 ISSUES.md 声称 PHP 警告会导致套件失败，而 phpunit.xml.dist … |
| [`P3-2`](issues/P3-2.md) | P3 | **verified** | README 声称「总共约 1,200 行」，但仅 src/Validator.php 就有 358 行，加上 23 个验证器类（平均每个约 … |
| [`P3-3`](issues/P3-3.md) | P3 | **verified** | 在没有构造参数的验证器上传入标量或列表配置会被静默丢弃，而映射形式会抛异常。分岔是拒绝它，或把这种不对称写进文档。 |
| [`P3-4`](issues/P3-4.md) | P3 | **verified** | `README.md` 两处调用 … |

## 未关闭

本模块还剩什么要做：所有 `status` 不是 `verified` 或 `closed` 的条目，按严重度从高到低。
`waiting on` 是下一步该动手的一方，由其状态读出。

| | |
|---|---|
| 未关闭 | **2** / 10 |
| 按状态 | `rejected` 1 · `fixed` 1 |
| 等在谁 | 评审方 2 |

| 级别 | 条目 | 状态 | 等在谁 | 标题 |
|---|---|---|---|---|
| **P2** | [`P2-3`](issues/P2-3.md) | `rejected` | 评审方 | Validator::createValidator() 将 null 配置与 true 同等对待（都调用无参构造器）。README … |
| **P2** | [`P2-6`](issues/P2-6.md) | `fixed` | 评审方 | validate() 假定每个字段的规则取值是数组。传入字符串时它抛出裸 PHP 警告（"foreach() argument must be … |

## 结论

本轮测过的每一条配置路径都已贯彻「响亮失败」的取向——只差一条，即字段自身的规则取值，剩下的漏洞正在那里。

## 本轮已修复确认

P2-5, P3-2, P3-3 and P3-4 all verified: an uncoercible scalar config now raises the module’s own exception instead of a raw TypeError, the size claim is ~1,300 against a measured 1,307, a validator with no constructor parameter refuses a truthy scalar, and the README names a class that exists.

## 测试盲区

无用例把非数组的规则取值交给 validate()，下面那条 finding 正是因此逃逸；README 的体量主张无行为路径可钉。

## 验证方式

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- `phpunit.xml.dist` 中的 warning/notice/deprecation/risky 开关：四个全开
- 只有在上述开关打开时 PHP 警告才会导致套件失败；否则请显式加 `--fail-on-warning`。
