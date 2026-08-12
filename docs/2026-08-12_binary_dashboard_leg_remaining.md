# 2026-08-12 — Dashboard: Left/Right Leg "Remaining" and Strong/Weak fix

Adds a "current binary matching remaining" figure to the member dashboard's
Binary Summary panel, and fixes Strong/Weak to be derived from that remaining
balance instead of the raw leg totals. Read-only display change — the real
matching/payout engine (`Binarylevelmatching_model`) is untouched.

## What was wrong

`user/dashboard/index.php` showed one number per leg, labelled "Leg
Investment (BMAN)", sourced from `BinaryModel::calculateLegLockWallet()`
(live `SUM(user_stakes.stake_amount)` per leg, `status IN
('active','processing') AND maturity_date > NOW()`). Strong/Weak was then
computed inline in the view as `$legLeft >= $legRight` — which:

- had no concept of "remaining after matching" at all — the dashboard never
  told a member how much of their weaker leg's volume was still unmatched;
- defaulted Left to STRONG on any tie (`>=`), including the 0/0 case where
  neither leg has anything.

## What changed

New method `BinaryModel::calculateLegMatchingState($userId)`
(`application/models/member/BinaryModel.php`), called from both the initial
dashboard render (`User::index()`) and the This Week / This Month AJAX
refresh (`User::binarySummaryAjax()`), so the two stay in sync (they already
shared `calculateLegLockWallet()` before this change).

```
left, right         = calculateLegLockWallet()   — unchanged, never reduced
matched             = MIN(left, right)
completed           = matched > 0                — both legs must carry volume
left_remaining      = completed ? left  - matched : 0
right_remaining     = completed ? right - matched : 0
strength            = compare left_remaining vs right_remaining
                       tie (incl. both 0) → EVEN / EVEN, never a default STRONG
```

**"Leg Investment" and "Remaining" are deliberately different numbers** —
the raw per-leg total is never subtracted from; only the newly-added
"Remaining" figure reflects the live match. The dashboard now renders both,
stacked, under each leg card:

```
Left Leg Investment:   19.00 BMAN
Left Remaining:         6.00 BMAN     STRONG
Right Leg Investment:  13.00 BMAN
Right Remaining:        0.00 BMAN     WEAK
```

### "Matching completed" rule

Matching only "completes" once **both** legs have live eligible volume. If
one leg is currently empty, `matched = MIN(x, 0) = 0`, and rather than
showing the misleading "one full leg, one empty leg" the raw subtraction
would produce, both Remaining figures report `0`. Confirmed against real
data: user `#3` (right-only downline, left leg empty) — before this change
would've shown nothing for "remaining" at all; now correctly reports
`0 / 0`, `EVEN / EVEN`, rather than implying a pairing that doesn't exist.

## Verified

Against the two worked examples given for this change, and against live
data pulled from `e-commerce-mlm-v2`:

| Case | Left | Right | Matched | Left Rem. | Right Rem. | Strength |
|---|--:|--:|--:|--:|--:|---|
| Spec Level 1 | 5,000 | 10,000 | 5,000 | 0 | 5,000 | L=WEAK, R=STRONG |
| Spec Level 2 | 25,000 | 30,000 | 25,000 | 0 | 5,000 | L=WEAK, R=STRONG |
| Live user `#999999504` | 19 | 13 | 13 | 6 | 0 | L=STRONG, R=WEAK |
| Live user `#3` (right-only) | 0 | 2 | 0 | 0 | 0 | EVEN / EVEN |
| Both legs empty | 0 | 0 | 0 | 0 | 0 | EVEN / EVEN |
| Perfectly equal legs | 7,000 | 7,000 | 7,000 | 0 | 0 | EVEN / EVEN |

All match the intended rule, including the two edge cases the spec didn't
give worked numbers for (one leg empty; a perfect tie).

## What this intentionally does NOT touch

- **The real matching engine** (`application/models/staking/
  Binarylevelmatching_model.php`) — pays per depth-level, cumulative volume
  levels 1..N, never decrements, keyed off `staking_matching_payouts`. This
  dashboard figure is a separate, always-live *display* projection over the
  same underlying Lock Wallet volume source — it does not feed, gate, or
  read from the payout engine in either direction.
- **No new cron, and no new persisted "leg balance" table.** Grepped the
  codebase first — nothing like a `binary_leg_balance` snapshot exists
  today; every leg-volume figure anywhere in the app (Binary Tree page,
  matching engine, and now this dashboard card) is a fresh live recompute
  from `user_stakes` + `binary_placement` at request/run time, via the same
  recursive-CTE shape. Because nothing here is cached, the dashboard can
  never fall out of sync with what `BinaryMatchingPayoutCron` (runs every 5
  minutes, `application/controllers/BinaryMatchingPayoutCron.php`) would
  compute if it ran this instant — there is no snapshot staleness window to
  manage.
- **`Binarylevelmatching_model::levelComplete()`** already has its own,
  separate notion of "level complete" (both legs nonzero *at that specific
  tree depth*, cumulative 1..N) used purely to decide payout eligibility —
  intentionally not reused here, since the dashboard's "matching completed"
  question is simpler: does a live pairing currently exist at all, for the
  totals actually shown.

## Files changed

- `application/models/member/BinaryModel.php` — new
  `calculateLegMatchingState()`.
- `application/controllers/user/User.php` — `index()` and
  `binarySummaryAjax()` now call it and expose `left_remaining_bman`,
  `right_remaining_bman`, and remaining-based `left_strength`/
  `right_strength` (now including `EVEN`).
- `application/views/user/dashboard/index.php` — renders the new
  "Remaining (BMAN)" line under each leg card; JS `renderBinarySummary()`
  updates it on period toggle.

Deployed to the main checkout, left uncommitted for review.
