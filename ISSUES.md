# migears-validator — Known Issues

> Summary of this module's issues. The items themselves are in [`issues/`](issues/README.md), one file
> per item: a front-matter header and a thread. This file is generated from them and can be rewritten at
> any time; edit an item, never this file.
>
> From the miGears Full-Module Code Review Report (5th round, 2026-09-28).

| | |
|---|---|
| Status | **P2 open** |
| Size | src 196 lines (net) · 107 tests · 2 src files |

Legend — **P0** functional or security · **P1** documentation that fails when copied · **P2** robustness · **P3** metadata and docs

## At a glance

| | |
|---|---|
| Unsettled | P0 0 · P1 0 · P2 2 · P3 1 · other 0 |
| Settled | 0 of 3 |
| Waiting on the owner | `P2-1`, `P2-2`, `P3-1` |
| Waiting on the reviewer | _nothing_ |
| Waiting on the coordinator | _nothing_ |
| Deferred, owing nobody | _nothing_ |

| id | level | status | title |
|---|---|---|---|
| [`P2-1`](issues/P2-1.md) | P2 | **open** | The list-shaped config path passes the array straight to the … |
| [`P2-2`](issues/P2-2.md) | P2 | **open** | Map-form config silently discards unknown keys via … |
| [`P3-1`](issues/P3-1.md) | P3 | **open** | The module's ISSUES.md asserts that PHP warnings fail the suite here, … |

## Unclosed

What is left to do here: every item whose `status` is not `verified` or `closed`,
highest severity first. `waiting on` is the party who acts next, read from that status.

| | |
|---|---|
| Unclosed | **3** of 3 |
| By status | `open` 3 |
| Waiting on | owner 3 |

| level | item | status | waiting on | title |
|---|---|---|---|---|
| **P2** | [`P2-1`](issues/P2-1.md) | `open` | owner | The list-shaped config path passes the array straight to the … |
| **P2** | [`P2-2`](issues/P2-2.md) | `open` | owner | Map-form config silently discards unknown keys via … |
| **P3** | [`P3-1`](issues/P3-1.md) | `open` | owner | The module's ISSUES.md asserts that PHP warnings fail the suite here, … |

## Verdict

A well-designed declarative validator with 23 built-in rules and flexible config styles; null config values are treated the same as true (rule enabled), which is not documented and could surprise users.

## Fixed since the last round

All three prior items confirmed fixed in code: P2-1 list-form config now properly handled (scalar / array-throws dispatch); P2-2 unknown config keys now throw instead of being silently dropped; P3-1 G2 strict flags complete.

## Test gaps

No test for required validator with empty array value ([]); no test for pattern validator with a pattern missing delimiters; no test for custom validator that overrides a built-in via constructor pre-registration.

## Verification protocol

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- Warning/notice/deprecation/risky flags in `phpunit.xml.dist`: all four on
- A PHP warning counts as a test failure only where those flags are on; otherwise run `./vendor/bin/phpunit --fail-on-warning` explicitly.


---

# migears-validator — 已知问题

> 本模块问题的概览。条目本体在 [`issues/`](issues/README.md)，一条目一文件：前置字段加讨论串。
> 本文件由条目生成，随时可以整段重写；请改条目，不要改本文件。
>
> 出自 miGears 全模块代码评审报告（5th round，2026-09-28）。

| | |
|---|---|
| 状态 | **P2 待修** |
| 体量 | src 196 行（净）· 107 个用例 · 2 个源文件 |

级别说明 — **P0** 功能性或安全级 · **P1** 文档照抄即错 · **P2** 健壮性 · **P3** 元数据与文档

## 状态一览

| | |
|---|---|
| 未了结 | P0 0 · P1 0 · P2 2 · P3 1 · 其他 0 |
| 已了结 | 0 / 3 |
| 等负责人 | `P2-1`, `P2-2`, `P3-1` |
| 等评审方 | _无_ |
| 等协调人 | _无_ |
| 已暂缓，不欠谁 | _无_ |

| id | 级别 | 状态 | 标题 |
|---|---|---|---|
| [`P2-1`](issues/P2-1.md) | P2 | **open** | 列表形态配置会把数组直接传给构造器：["pattern" => ["/^[a-z]+$/"]] 抛 TypeError: … |
| [`P2-2`](issues/P2-2.md) | P2 | **open** | 映射形态配置用 array_intersect_key 静默丢弃未知键，因此拼错会让规则变弱而不是报错：["minLength" => … |
| [`P3-1`](issues/P3-1.md) | P3 | **open** | 本模块 ISSUES.md 声称 PHP 警告会导致套件失败，而 phpunit.xml.dist … |

## 未关闭

本模块还剩什么要做：所有 `status` 不是 `verified` 或 `closed` 的条目，按严重度从高到低。
`waiting on` 是下一步该动手的一方，由其状态读出。

| | |
|---|---|
| 未关闭 | **3** / 3 |
| 按状态 | `open` 3 |
| 等在谁 | 负责人 3 |

| 级别 | 条目 | 状态 | 等在谁 | 标题 |
|---|---|---|---|---|
| **P2** | [`P2-1`](issues/P2-1.md) | `open` | 负责人 | 列表形态配置会把数组直接传给构造器：["pattern" => ["/^[a-z]+$/"]] 抛 TypeError: … |
| **P2** | [`P2-2`](issues/P2-2.md) | `open` | 负责人 | 映射形态配置用 array_intersect_key 静默丢弃未知键，因此拼错会让规则变弱而不是报错：["minLength" => … |
| **P3** | [`P3-1`](issues/P3-1.md) | `open` | 负责人 | 本模块 ISSUES.md 声称 PHP 警告会导致套件失败，而 phpunit.xml.dist … |

## 结论

一个设计精良的声明式验证器，含 23 个内置规则、灵活的配置风格；null 配置值与 true 同等对待（启用规则），这一点未文档化，可能让用户意外。

## 本轮已修复确认

All three prior items confirmed fixed in code: P2-1 list-form config now properly handled (scalar / array-throws dispatch); P2-2 unknown config keys now throw instead of being silently dropped; P3-1 G2 strict flags complete.

## 测试盲区

无 required 验证器对空数组值（[]）的行为测试；无 pattern 验证器缺少分隔符的测试；无通过构造器预注册覆盖内置规则的自定义验证器测试。

## 验证方式

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- `phpunit.xml.dist` 中的 warning/notice/deprecation/risky 开关：四个全开
- 只有在上述开关打开时 PHP 警告才会导致套件失败；否则请显式加 `--fail-on-warning`。
