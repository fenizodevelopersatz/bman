# Binary Matching Ceiling Management — Design Reference

**Status:** 📝 DESIGN / NOT YET IMPLEMENTED — captured for future reference per user request (2026-08-05). This doc reconciles the full spec below against what already exists and is already live in this codebase, so a future implementation pass extends real infrastructure instead of building a second, colliding system next to it.

## The ask, as specified

**Flow:** downline business completed → calculate matching bonus (10% = 8% Earning + 2% Staking) → check today's cumulative matching bonus against the user's ceiling → within limit: credit wallet normally → over limit: hold / flush / carry-forward the excess → update rank volume → check rank promotion.

**New admin page — "Binary Ceiling Management":** a per-rank table (Un Rank through Challenger) with columns Daily Ceiling, Monthly Ceiling, Lifetime Ceiling, Carry Forward (yes/no), editable by an admin.

**Admin Settings ("Binary Matching Settings"):** Matching Percentage (8% Earning / 2% Staking / 10% total), Ceiling Type (Daily / Weekly / Monthly / Lifetime — radio), Overflow Handling (Flush / Carry Forward / Hold — radio), Rank Based Ceiling (on/off), Recalculate After Rank Promotion (on/off).

**Database design, as specified:**
- `binary_ceiling_settings` — `id, rank_id, daily_limit, monthly_limit, lifetime_limit, carry_forward, status, updated_at`
- `binary_matching_history` — `id, user_id, left_volume, right_volume, matching_volume, generated_bonus, earning_bonus, staking_bonus, ceiling_limit, paid_bonus, overflow_bonus, status, created_at`
- `binary_overflow_bonus` — `id, user_id, bonus_amount, reason, status, released_at`

**Admin Dashboard metrics:** Total Matching Generated, Today's Matching Paid, Today's Overflow, Users Hit Ceiling, Pending Carry Forward, Highest/Lowest Matching User, Remaining Daily Capacity.

**Recommended rule (user's own words, verbatim intent):** the ceiling should only limit Binary Matching Bonus *payouts* — it must never reduce the left/right business volume that counts toward rank qualification. Even a user sitting at their bonus ceiling should keep accumulating rank-qualifying volume normally.

## What already exists (read this before implementing anything above)

This is more built-out than the spec might suggest — there are actually **three** separate historical systems under the "binary matching" name, only one of which is live:

- **LIVE engine:** `application/models/staking/Stakingmatching_model.php` → called from `BinaryMatchingPayoutCron.php` (registered in Cron Lab, real treasury-signed on-chain sends). Explicitly self-described as *"the ONLY place binary matching income is created."*
  - **The 8%/2%/10% split already exists, already configurable, already exactly matches this spec's numbers**: `staking_bonus_settings` (`matching_total_percent=10.00`, `matching_earning_percent=8.00`, `matching_staking_percent=2.00`), admin-editable today at `admin/staking/bonus-settings`. No new settings needed for the percentage piece.
  - **A ceiling already exists and is already enforced** — but it's **per-user, per-package, lifetime**, not per-rank or time-windowed: `userCeiling()` sums `staking_packages.group_ceiling` across the user's active stakes; `matchingPaidToDate()` is a lifetime `SUM` with no date window at all. Excess above this ceiling is diverted to a **hold** bucket (below), proportionally, not simply blocked.
  - **Volume carry-forward already exists, but it's a different thing than "ceiling overflow carry-forward."** Unmatched leg volume (`binary_carry`) already rolls forward run-to-run when one leg outweighs the other. What does *not* exist is a "the bonus I couldn't pay because I hit my ceiling gets paid next period instead" mechanism — today, ceiling excess is diverted to the hold bucket, not deferred.
- **LIVE overflow bucket:** `application/models/staking/Ceilingwallet_model.php` + real tables `ceiling_wallet`/`ceiling_wallet_ledger` + admin page `admin/staking/ceiling-wallet`. This is a backend-only hold/release ledger — *"NOT a user wallet: it never touches user_wallets/wallet_ledger and is never shown to, transferred by, or withdrawn by members"* — that captures exactly the per-package-ceiling excess described above. An admin can `release()` a held amount back to the member or `adjust()` it. **This already implements "Hold," one of the spec's three overflow options**, scoped to the existing package ceiling.
- **DEAD, schema-incompatible parallel system — do not revive, do not confuse with the above:** `application/models/member/BinaryMatchingBonus_model.php`, `application/models/member/CeilingWallet_model.php`, `application/controllers/BinaryMatchingAdmin.php`, `application/controllers/cron/BinaryMatchingCron*.php`. Not registered in Cron Lab, no working route reaches them, and their expected columns (`ceiling_wallet.balance`, `admin_ceiling_wallet.gas_balance`, etc.) don't exist in the live schema — they would throw `Unknown column` errors immediately if ever invoked. The project's own docs confirm this is intentional: left exactly as-is, not touched. **A new `CeilingWallet_model` or `binary_ceiling_settings` table must not be confused with, or accidentally routed through, any file under `member/`, `cron/`, or `BinaryMatchingAdmin.php`.**
- **A third, older, unrelated legacy system** — `DailyCommission.php` + `BinaryCarryEngine.php`, keyed on a *different* table `binary_carry_forward` (not `binary_carry`), with its own simple `cap_value()` ceiling. Its only HTTP route points at an empty 0-byte controller file, so it's unreachable over HTTP — but I could not fully rule out a direct OS-level crontab invocation by class/method name bypassing that broken route. Its `save_carry()` method writes to `binary_carry` — the *same* table the live engine reads — so if this path is ever actually live via crontab, it's a real collision risk independent of this new feature. **Worth a two-minute check of the actual server crontab before finalizing an implementation plan**, just to be certain nothing else is writing to `binary_carry` concurrently.
- **A documented-but-unbuilt adjacent feature — naming collision risk:** `docs/1_BMAN_DEEP_ANALYSIS.md` describes a "Group Incentive Ceiling" (Module 12, open TODO in `docs/0_INDEX.md`, not implemented) that reuses `staking_packages.group_ceiling` — i.e., **package-keyed**, not rank-keyed — with a documented overflow behavior of **forfeiture** ("excess is forfeited, or requires re-stake to raise the ceiling"). That's a genuinely different overflow model than what's actually live today for binary matching (hold-and-releasable, not forfeited). If this new rank-based ceiling ships a "Flush" option, it will be the **first real implementation of forfeiture** anywhere in the binary/staking ceiling space — worth deciding deliberately rather than assuming "Flush" and "Group Incentive Ceiling forfeiture" should share code just because both discard the excess.
- **No existing daily/monthly volume rollup table anywhere.** Every time-windowed figure in the codebase today (e.g. Rank Power's 60-day cycle) is computed by re-scanning raw source rows with a date filter on each run — there is no precomputed daily/monthly bucket table for left/right volume or matching bonus. A per-rank **daily** and **monthly** ceiling, as specified, is new infrastructure, not just a new settings table — it needs either a rollup table maintained incrementally, or an on-the-fly `WHERE created_at BETWEEN ...` computation against `staking_matching_payouts` each run (cheaper to build, more expensive to query as data grows).

## The one conflict that must be resolved before implementing — not something to decide silently

This is the most important finding from reconciling this spec against the live code, and it directly concerns the spec's own "Recommended rule."

**Binary matching bonus *paid* and Achievement Rank *qualifying volume* are, as of a recent migration this session found (`db/2026-07-27_rank_volume_source_fix.sql`), literally the same number today.** `Rankcalculator_model::calculateBonusVolume()` sources Achievement Rank volume from the user's own lifetime `SUM(earning_amount + staking_amount)` in `staking_matching_payouts` — i.e., the **post-ceiling, actually-paid** amount, by explicit, documented design (*"already reflects... this member's own package-ceiling cap"*).

The new spec's Recommended Rule says the opposite should be true: a ceiling should throttle payout only, and rank-qualifying volume should keep accumulating regardless. Under the current live design, a user sitting at their ceiling literally **stalls their own rank progress**, because rank volume *is* paid-bonus — there's no separate "volume generated" number distinct from "bonus paid" for Achievement Rank today.

Implementing this spec's rule as stated would mean either:
1. Reverting Achievement Rank's volume source back to a raw, uncapped "matching volume generated" figure (undoing the 2026-07-27 migration's intent), or
2. Keeping rank volume keyed to paid-bonus for Achievement Rank specifically, and accepting that the new rule only applies going forward / only to some other, new "matching volume" figure introduced alongside the ceiling feature (e.g., `binary_matching_history.matching_volume` from the spec's own schema, which is a *generated*, not *paid*, figure — this could become the new rank-volume source instead, decoupling the two cleanly).

This needs an explicit decision, not an inferred one — it reopens a design choice that was deliberately made earlier this same day for a different reason (Achievement Rank's volume-gaming resistance).

## Reconciliation — extend real tables/models, don't duplicate them

1. **Percentage split**: reuse `staking_bonus_settings` as-is. No new column or table needed for Matching Percentage — it's already there, already 8/2/10, already admin-editable.
2. **`binary_ceiling_settings`**: keep this as a genuinely new table (nothing existing is rank-keyed today), but scope its columns to *only* the ceiling values (`rank_id, daily_limit, monthly_limit, lifetime_limit, carry_forward, status`) since the percentage split doesn't belong here.
3. **`binary_matching_history`**: reconcile against the already-live `staking_matching_payouts`, which today records almost this exact row (paid amounts, earning/staking split) minus the ceiling/overflow breakdown columns. Prefer adding `ceiling_limit`/`overflow_bonus`/`matching_volume` columns to the existing table over standing up a second history table that answers the same question with a different name.
4. **`binary_overflow_bonus`**: reconcile against the already-live `ceiling_wallet`/`ceiling_wallet_ledger` + `Ceilingwallet_model`, which already implements hold/release for ceiling excess. Decide explicitly whether the new rank-based "Hold" option *is* this existing bucket (extended with a `rank_id`/period dimension) or a deliberately separate one — standing up a same-purpose second table without that decision would split the admin's view of "money currently held" across two places.
5. **Layering with the existing package ceiling**: `Stakingmatching_model::userCeiling()`'s lifetime/per-package cap doesn't go away just because a rank-based cap is added — decide whether the new rank ceiling sits *inside* the existing package ceiling (both apply, tightest wins), replaces it outright, or applies only when Rank Based Ceiling is toggled on (per the spec's own admin setting, which reads as "this is optional/togglable" — implying the package ceiling remains the default/fallback when it's off).
6. **Resolve the rank-volume conflict above first** — it changes what `binary_matching_history.matching_volume` is even *for*, and whether `Rankcalculator_model` needs to change at all.
7. **Clarify "Flush" before writing it** — true forfeiture (money never credited anywhere, ever) is a new behavior with real financial-audit weight; make sure that's actually intended and not just a synonym for what "Hold" already does.
8. **Quick crontab check** on the `DailyCommission`/`binary_carry_forward` legacy path (see above) before assuming the only writer to `binary_carry`-adjacent tables is the live engine.

## Open questions (not resolved by the spec as given)

- Does the new rank-based ceiling *replace* or *layer with* the existing per-package lifetime ceiling?
- Is "Hold" the existing Ceiling Wallet (extended), or a new, separate rank-scoped bucket?
- Does "Flush" mean true forfeiture, and if so, is that a deliberate first for this codebase (nothing forfeits today)?
- Daily/Monthly reset boundary: calendar-aligned (midnight UTC / 1st-of-month), or a rolling window from some per-user anchor date (Rank Power's 60-day cycle uses the latter — worth being consistent, or explicitly deciding this is different)?
- "Recalculate After Rank Promotion" — recalculate *what* exactly? The user's new ceiling limits going forward (obviously), or does it retroactively re-evaluate today's already-paid/held amounts under the new rank's ceiling?
- Does `binary_matching_history` need to be written for *every* matching event (including zero-bonus/no-match runs), or only when a bonus is actually generated?

## Files most likely involved when this is implemented

- `application/models/staking/Stakingmatching_model.php` (`userCeiling()`, `payMatching()` — where a rank-tier + time-window check would be added alongside the existing package check)
- `application/models/staking/Ceilingwallet_model.php` (extend for rank-scoped hold, if that's the resolution to the open question above)
- `application/models/staking/Rankcalculator_model.php` (`calculateBonusVolume()` — directly implicated by the rank-volume conflict above)
- New admin controller/view pair for "Binary Ceiling Management" (natural sibling of `admin/staking/ceiling-wallet` and `admin/staking/bonus-settings`)
- `application/controllers/BinaryMatchingPayoutCron.php` (unaffected directly today, since it never calculates amounts itself — but would need to surface any new ceiling-related fields in its payout records if reporting needs them)
