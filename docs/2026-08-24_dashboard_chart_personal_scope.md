# 2026-08-24 — Dashboard chart (User Activity & Coin Trend): fix team/personal scope mismatch

## The report

On the member dashboard (`user/main`), the "User Activity & Coin Trend" chart's
**Earning Coin** tile showed `8,000 BMAN` while the **Earning Wallet** card
directly below it — same page, same user, same moment — showed
`12,000.00 BMAN`. User expectation: these are "the same money," just shown two
different ways, so they should match (or at least be visibly the same
person's figures).

## Root cause

`Dashboardchart_model` computes all five chart series (`active_users`,
`bonus_used`, `staking_done`, `earning_coin`, `coin_withdrawal`) with
`TEAM_SCOPE = true` — every series was summed across the member's **binary
downline**, explicitly **excluding the member themselves**
(`_teamIds()`: "Never includes the member"). Meanwhile every wallet balance
card on the same page (`Walletledger_model::balances($userid)`) is the
logged-in user's own balance.

So `Earning Coin: 8,000` was never the user's money at all — it was however
much their 6 team members earned in bonus credits over the selected period
(last 12 months). `Earning Wallet: 12,000.00` was the user's own current
balance, all sources, all time. Two different people's money, shown stacked
on top of each other with labels that invited exactly the "why don't these
match" question. This was true for `bonus_used`, `staking_done`, and
`coin_withdrawal` too — every tile except `active_users` sits directly beside
a matching personal wallet card, and all of them were quietly team totals.

`active_users` and the chart's two trend lines (`left_investment`/
`right_investment`) are the only series where "team" is the only sensible
scope — a personal "active users" count is always 0 or 1, and a single
member has no "left leg"/"right leg" of their own to speak of. Those stay
team-scoped by design; everything else changed to personal.

## Fix

`Dashboardchart_model::trend()` (the member-facing chart) now splits into two
passes:

- **Team-wide pass** (`_applyLedger()` for `active_users`,
  `_applyLegInvestments()` for left/right investment) — unchanged logic,
  still skipped entirely when the downline is empty.
- **Personal pass** (`_applyPersonal()`, new) — `bonus_used`, `staking_done`,
  `earning_coin`, `coin_withdrawal`, always computed for the session user
  regardless of downline size.

`_applyPersonal()` does **not** hand-write new SQL. It reuses
`_applyLedger()`/`_applyStaking()`/`_applyWithdrawals()` — the exact same
query builders the team/platform paths use — called a second time with `$ids`
forced to the single-element array `[$userId]` (a one-element `IN (...)` is
equivalent to `= ?`). That keeps the personal figures permanently in sync
with the team/platform business rules (the `bonus_reduction` clawback
exclusion, the `swap_completed`/`completed` status pair, the `net_amount`
column) instead of risking two copies of the same logic drifting apart later.
It explicitly zeroes those four fields on the real bucket array before
copying results over — otherwise a bucket where the team had activity but
this member personally didn't would keep the team pass's leftover value
instead of correctly reading 0.

**Side fix (previously latent):** a member with an empty downline used to
get an all-zero chart — the old code's empty-team early-return skipped every
series, including what should have been their own personal earning figures.
Now the personal pass always runs, independent of team size.

**Side benefit:** the team-wide pass no longer calls `_applyStaking()`/
`_applyWithdrawals()` at all — their team-scoped output was only ever going
to be discarded once those fields became personal, so removing those calls
is 2 fewer queries per dashboard load, not 2 more.

`platformTrend()` (the **admin** dashboard's version of this same chart) is
completely untouched — confirmed via grep it's the only other caller of this
model, and platform-wide is the only scope that makes sense there (there's no
"personal" figure to show on an aggregate, all-users view).

## Verification

- `php -l` clean.
- Hand-traced both the empty-downline and populated-downline paths through
  `trend()`.
- Confirmed the existing `idx_user_created(user_id, created_at)` index
  (from `db/2026-07-17_dashboard_chart_indexes.sql`) already covers the new
  single-id query pattern — no new migration needed.
- Smoke-tested the exact SQL text `_applyLedger()`/`_applyStaking()`/
  `_applyWithdrawals()` generate with `user_id IN (2)` against the real local
  schema — all three execute cleanly.
- Verified the on-disk file cache (`CI_Cache_file::_get()`) genuinely honors
  its TTL and discards expired entries on read — ruled out as an explanation
  for stale-looking numbers after deploy.
- **Not verified end-to-end against real transaction history**: the local
  dev database (`e-commerce-mlm-v2`) currently has no bonus/staking/
  withdrawal/earning ledger rows to compute non-zero figures from, and does
  not match the account shown in the reported screenshots (same test user id
  has a different `active_users` count locally than the screenshot shows) —
  it is evidently a different dataset than whatever backs the live site.

## Deployment note

Per this repo's usual workflow, the change was made directly in the main
checkout (`D:\SATZ\SATZ\php\FInal`) and left **uncommitted** for review — no
`git add`/`commit` was run. Screenshots taken after this fix was written
still showed numbers identical to the pre-fix screenshot; since the local
dev database doesn't match that live account either, the fix has very likely
not reached that server yet rather than being wrong — pending confirmation
once deployed.

## Files changed

- `application/models/user/Dashboardchart_model.php`
  - Class docblock — documents the per-series scope split (which tiles are
    team vs. personal, and why).
  - `trend()` — team-wide block now only calls `_applyLedger()` +
    `_applyLegInvestments()`; personal fields always computed afterward via
    the new `_applyPersonal()`, independent of downline size.
  - `_applyLedger()` docblock — documents its three different callers
    (`platformTrend()`, `trend()`'s team block, `_applyPersonal()`) and which
    of its three output columns each one actually keeps.
  - `_applyPersonal()` — new. Personal-scoped `bonus_used`/`staking_done`/
    `earning_coin`/`coin_withdrawal` via single-user reuse of the existing
    aggregation helpers.
  - `_applyStaking()` / `_applyWithdrawals()` docblocks — note their two
    remaining callers (`platformTrend()` platform-wide, `_applyPersonal()`
    single-user) now that `trend()`'s team block no longer calls them.
  - `_shape()` — `scope` field now reports `'mixed'` (was `'team'`) when
    `TEAM_SCOPE` is on, since the payload is no longer uniformly team-scoped.
    `platformTrend()` still overwrites this to `'platform'` itself, so admin
    output is unaffected.
