# 2026-08-24 — Dashboard chart: Bonus Used / Staking Done → wallet balance

Follow-up to the same day's
[2026-08-24_dashboard_chart_personal_scope.md](2026-08-24_dashboard_chart_personal_scope.md).
That build fixed team-vs-personal scope: all four non-team tiles became the
session user's own numbers instead of their downline's. It surfaced a second,
deeper mismatch once scope was no longer the issue.

## What personal scope alone didn't fix

Verified directly against the real database backing `192.168.29.18:9001`
(`e-commerce-mlm-v2`, `user_id=2`, whose wallet balances — 12,000 earning /
3,000 staking / 25,000 bonus — match the reported screenshot exactly):

- **Bonus wallet_ledger, all time: one row.** `credit=25000, debit=0`. Correctly
  personal-scoped, `bonus_used` (SUM of `debit`) correctly computed 0 — the
  user has never had a debit, only the credit that built the balance.
- **`staking_swap_orders`, all time: zero rows for this user.** The 3,000 in
  the Staking Wallet came entirely from two `wallet_ledger` credits with
  `reference_type='binary_matching'` (1,000 + 2,000) — a matching-bonus
  payout apparently splits automatically across the earning and staking
  wallet types. `staking_done` (COUNT of completed swap-order purchases)
  correctly computed 0 — this user has never bought a staking package.

Both zeros were the CORRECT answer to their original questions ("how much
has this member spent from their bonus wallet" / "how many staking purchases
has this member completed"). The problem was the questions: sitting directly
under a `Bonus Wallet: 25,000` and `Staking Wallet: 3,000` card, a member
reads "Bonus Used: 0" / "Staking Done: 0" as "you have nothing," not as the
activity-only metric it actually was.

## Fix

Per explicit direction, both tiles now show the session user's actual
**wallet balance** instead — relabeled `Bonus Wallet Balance` / `Staking
Wallet Balance` to match. `Earning Coin`/`Coin Withdrawal` are unaffected —
they're activity/flow metrics that were already correct and already matched
(see the prior doc).

A balance is fundamentally a different shape of number than everything else
in this chart computes: every other series is a FLOW (how much moved within
a bucket, or across all buckets when summed for the headline tile). A
balance is a point-in-time snapshot that carries forward — bucket N's value
has to include every credit/debit up to and including that bucket, not just
what happened inside it, and the headline/summary figure has to be the
*latest* bucket's value, not a sum across buckets (summing a roughly-steady
balance across 12 months would report something like 12x the real number).

New `_walletBalanceSeries($range, $from, $userId, $walletType)`:
1. Query the starting balance immediately before the window
   (`created_at < $from`) — a member with wallet history older than the
   visible range needs a real starting point, not 0.
2. Query the NET movement (`credit - debit`) inside each bucket.
3. Walk the buckets in chronological order (already the skeleton's natural
   order), accumulating a running total.

`_applyPersonal()` calls this once for `wallet_type='bonus'` and once for
`'staking'`, no longer reuses `_applyLedger()`'s `bonus_used` column or
`_applyStaking()` at all for the member path — both are now exclusively
`platformTrend()`'s (admin), which keeps its original platform-wide
debit-sum / purchase-count meaning untouched. `trend()` overrides
`summary.bonus_used`/`summary.staking_done` to the latest bucket's value
after `_shape()` runs, since `_shape()`'s generic sum-across-buckets logic
is still correct for every other field (and still correct for admin, which
never had this override applied).

**Verified**: reproduced `_walletBalanceSeries()`'s exact query logic against
`user_id=2`'s real data — final (August 2026) bucket comes out to
`bonus: 25000`, `staking: 3000`, matching the wallet cards precisely; every
earlier month correctly shows 0 (this account's only activity is from
2026-08-23).

## Files changed

- `application/models/user/Dashboardchart_model.php`
  - Class docblock — documents bonus_used/staking_done as balance (not
    activity) on the member path, and why.
  - `trend()` — overrides `summary.bonus_used`/`summary.staking_done` to the
    latest bucket's value instead of `_shape()`'s generic per-bucket sum.
  - `_applyPersonal()` — no longer calls `_applyStaking()` or uses
    `_applyLedger()`'s `bonus_used` column; calls the new
    `_walletBalanceSeries()` for both fields instead.
  - `_walletBalanceSeries()` — new. Running per-user, per-wallet_type balance
    reconstruction.
  - `_applyLedger()` / `_applyStaking()` docblocks — updated to reflect their
    remaining callers now that the member path no longer uses them for these
    two fields.
  - `_buckets()` — `staking_done` skeleton default `0` (int) → `0.0` (float),
    matching its new type.
- `application/views/user/dashboard/index.php`
  - Tile labels: `Bonus Used` → `Bonus Wallet Balance`, `Staking Done` →
    `Staking Wallet Balance`; the staking tile now renders a `BMAN` unit
    suffix like the other currency tiles (was a bare count).
  - Chart dataset labels updated to match. `Staking Wallet Balance` moved
    from the right `y1` axis (counts, integer ticks, shared with Active
    Users) to the left `y` axis (BMAN amounts, shared with Bonus/Earning/
    Withdrawal) — a 3,000 BMAN balance plotted against an axis scaled for
    single-digit active-user counts would have been invisible. Also no
    longer `hidden:true` by default, for consistency with Bonus Wallet
    Balance now that both are the same kind of series.
