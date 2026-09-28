# migears-validator — Known Issues / 已知问题

> Summary of this module's issues. The items themselves are in [`issues/`](issues/README.md), one file
> per item: a front-matter header and a thread. This file is generated from them and can be rewritten at
> any time; edit an item, never this file.
>
> 本模块问题的概览。条目本体在 [`issues/`](issues/README.md)，一条目一文件：前置字段加讨论串。
> 本文件由条目生成，随时可以整段重写；请改条目，不要改本文件。
>
> From the miGears Full-Module Code Review Report (4th round, 2026-09-27).

| | |
|---|---|
| Status / 状态 | **P2 open / P2 待修** |
| Size / 体量 | src 1,179 lines (809 net) · 102 tests · 25 src files |

Legend / 图例 — **P0** functional or security · **P1** documentation that fails when copied · **P2** robustness · **P3** metadata and docs
级别说明 — **P0** 功能性或安全级 · **P1** 文档照抄即错 · **P2** 健壮性 · **P3** 元数据与文档

## At a glance / 状态一览

| | |
|---|---|
| Items / 条目 | P0 0 · P1 0 · P2 2 · P3 1 · other 0 |
| Answered / 已回复 | 0 of 3 |
| Waiting / 等待回复 | `P2-1`, `P2-2`, `P3-1` |

| id | level | status | title |
|---|---|---|---|
| [`P2-1`](issues/P2-1.md) | P2 | **open** | The list-shaped config path passes the array straight to the … |
| [`P2-2`](issues/P2-2.md) | P2 | **open** | Map-form config silently discards unknown keys via … |
| [`P3-1`](issues/P3-1.md) | P3 | **open** | The module's ISSUES.md asserts that PHP warnings fail the suite here, … |

## Verdict / 结论

The root cause of last round's P0 is only half fixed. The list form now works for enum but throws a raw TypeError for validators whose first parameter is scalar, and the map form still drops unknown keys silently — so a single typo turns a rule into a no-op.

上一轮 P0 的根因只修了一半。列表形态对 enum 可用了，但对首参为标量的验证器会抛原始 TypeError；映射形态仍静默丢弃未知键——一个拼写错误就能让规则彻底失效。

## Fixed since the last round / 本轮已修复确认

上一轮大部分已收口：enum 的列表形态已正确（'A' 通过、'C' 报错）；README 的 isset(...) === 'minLength' 与 equals=true 两处相反结论已改正并有行为断言；构造器改调私有的 registerInternal()（docblock 注明不可覆写）；PatternValidator 构造期校验正则；列表形态遇 false 改为具名 InvalidArgumentException；validate() 返回错误数组这一点已在 README 显著提示；「无反射 / ~350 行」改为实际口径。 

## Test gaps / 测试盲区

No negative case for a misspelled key in map-form config (the existing test only shows extra keys are ignored, not that a typo kills the rule); no documented boundary for the list form on non-enum validators; no assertion for `["enum" => true]` (empty allowed set) semantics.

无「映射形态拼错键」的负向用例（现有测试只证明多键被忽略，未证明拼错会使规则失效）；无「列表形态用于非 enum 验证器」的文档化边界；无 ["enum" => true]（空允许集）的语义断言。

## Verification protocol / 验证方式

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- Warning/notice/deprecation/risky flags in `phpunit.xml.dist`: all four on
- A PHP warning counts as a test failure only where those flags are on; otherwise run `./vendor/bin/phpunit --fail-on-warning` explicitly.
- 只有在上述开关打开时 PHP 警告才会导致套件失败；否则请显式加 `--fail-on-warning`。
