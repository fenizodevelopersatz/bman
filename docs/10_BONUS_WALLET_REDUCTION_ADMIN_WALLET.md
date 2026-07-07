# 10 — Bonus Wallet 60-Day / 50% Reduction + Admin (Company) Wallet

Status: 📝 **Planned** (design only — no code shipped yet). This is the detailed
design for the item already flagged in [0_INDEX.md](0_INDEX.md) Phase B as
"Bonus reduction cron". When it ships, log it in
[3_CHANGELOG.md](3_CHANGELOG.md) and flip this file's status to ✅ Done.

---

## 1. Scope — which "Bonus Wallet" this is

The codebase has **two unrelated "bonus" systems** — this plan targets only
the second one:

1. **Legacy MLM admin bonus** — `admin/wallet/Walletmanagement.php`
   `add_currency()`/`add_token()`, writes to the old `history` table
   (`type='bonus'`). Manual, admin-triggered, unrelated to staking. **Out of
   scope** — do not touch.
2. **BMAN Staking Bonus Coin (§7)** — `user_wallets.bonus_balance`, credited
   automatically (25% of every stake purchase, plus swap bonus — see
   [3_CHANGELOG.md](3_CHANGELOG.md) 2026-07-03 swap entries), tracked in the
   double-entry `wallet_ledger` (`wallet_type='bonus'`). **This is the wallet
   that reduces 50% every 60 days.** Confirmed by
   [6_STAKING_PACKAGES_PLANS_ROI.md](6_STAKING_PACKAGES_PLANS_ROI.md) §10.3:
   *"bonus wallet auto-reduces 50% every 60 days (separate engine)"*.

---

## 2. What already exists (built 2026-07-02, admin side only)

| Piece | Where |
|---|---|
| Settings row (`reduction_enabled=1`, `reduction_interval_days=60`, `reduction_percent=50`) | `db/staking_bonus_settings.sql`, table `staking_bonus_settings` |
| Admin screen to edit those settings | `admin/staking/bonus-settings` ("Bonus & Matching") |
| Balance column | `user_wallets.bonus_balance` DECIMAL(30,8) |
| Double-entry ledger (already posts `bonus` credits) | `wallet_ledger` table, `db/wallet_production.sql` |
| Single source of truth for every balance move | `application/models/Walletledger_model.php` — `credit()`/`debit()`/`post()`/`statement()`, row-locked, bcmath-precise |
| Cron pattern to copy | `application/controllers/Depositcron.php` — CLI `php index.php depositcron run` + HTTP `/credit-deposits-cron?token=` |

**Nothing executes the reduction yet, and no "admin wallet" of any kind exists
in the schema.** That's what this plan adds.

---

## 3. The core question — where does the reclaimed 50% go?

Reducing `bonus_balance` is an **internal accounting adjustment**, not an
on-chain transfer — BMAN in the Bonus wallet is custodial (per the swap
engine docs, it only moves on-chain at withdrawal). So "reducing" it doesn't
physically move crypto anywhere by itself. Two ways to model it:

- **Forfeiture (breakage).** The debited amount just vanishes — no offsetting
  entry anywhere. Simple, but leaves no answer to "where did the money go,"
  and breaks the double-entry principle this codebase uses everywhere else
  (every `wallet_ledger` row is meant to balance against something).
- **Recovery into an Admin/Company Wallet (recommended).** Every 50%
  debited from a user's Bonus wallet is credited into one new company-owned
  ledger. The books stay balanced, there's a real running total, and it can
  be reported on (finance, compliance, "how much bonus has decayed
  company-wide this quarter").

This plan implements **recovery**, consistent with the existing
`wallet_ledger` / `Walletledger_model` design.

---

## 4. New schema — Admin Wallet

Two singleton-style tables, mirroring the existing `staking_bonus_settings`
(single settings row) and `wallet_ledger` (append-only ledger) conventions —
**not** a fake "system user" inside `users`/`user_wallets` (see §5 for why).

```sql
-- db/admin_wallet.sql  (idempotent, safe to re-run — same style as wallet_production.sql)

CREATE TABLE IF NOT EXISTS `admin_wallet` (
  `id` TINYINT UNSIGNED NOT NULL,
  `balance` DECIMAL(30,8) NOT NULL DEFAULT 0,
  `lifetime_bonus_reduction_total` DECIMAL(30,8) NOT NULL DEFAULT 0,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO `admin_wallet` (`id`) VALUES (1);

CREATE TABLE IF NOT EXISTS `admin_wallet_ledger` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `credit` DECIMAL(30,8) NOT NULL DEFAULT 0,
  `debit` DECIMAL(30,8) NOT NULL DEFAULT 0,
  `balance_after` DECIMAL(30,8) NOT NULL DEFAULT 0,
  `reference_type` VARCHAR(40) NOT NULL,   -- bonus_reduction | manual_adjustment | payout
  `reference_user_id` INT DEFAULT NULL,    -- whose bonus this came from, when applicable
  `description` VARCHAR(255) DEFAULT NULL,
  `created_by` INT DEFAULT NULL,           -- admin id for manual moves
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reference` (`reference_type`,`reference_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Per-user reduction schedule anchor (user_wallets has no created_at to anchor on).
-- Reuses the same idempotent _add_col helper pattern as wallet_production.sql.
CALL _add_col('user_wallets','bonus_last_reduced_at','`bonus_last_reduced_at` DATETIME DEFAULT NULL');

-- Backfill so existing users are NOT hit on day one of go-live — first
-- reduction becomes due 60 days from rollout, not retroactively from account age.
UPDATE `user_wallets` SET `bonus_last_reduced_at` = NOW() WHERE `bonus_last_reduced_at` IS NULL;
```

---

## 5. Why not reuse `user_wallets`/`wallet_ledger` with a fake "system user"?

Considered and rejected. This codebase's MLM logic (binary tree walks, rank
power, matching bonus, group counts, genealogy) scans the `users` /
`user_wallets` tables broadly. A synthetic "admin" user row risks being
double-counted in those scans or breaking tree-position assumptions
(sponsor/left/right). A dedicated pair of tables avoids that entirely, at
the cost of one small parallel model (`Adminwallet_model`, ~30 lines, same
shape as `Walletledger_model`).

---

## 6. Reduction engine — `Bonusreductioncron`

New controller, modeled directly on `Depositcron.php`:

- **CLI:** `php index.php bonusreductioncron run`
- **HTTP:** `/bonus-reduction-cron?token=<cron_token>` (reuses the existing
  `$config['cron_token']`)
- **Schedule it daily** (e.g. 00:15 via Windows Task Scheduler, since this is
  the local dev box) — the cron itself runs every day and picks whichever
  users are *due* that day; it is not literally invoked "every 60 days."

**Selection (pseudocode):**

```sql
SELECT uw.user_id, uw.bonus_balance
FROM user_wallets uw
WHERE uw.bonus_balance > 0
  AND (uw.bonus_last_reduced_at IS NULL
       OR uw.bonus_last_reduced_at <= DATE_SUB(NOW(), INTERVAL :interval_days DAY))
```

Gated by `staking_bonus_settings.reduction_enabled` — if `0`, the cron logs
`processed=0, reason=disabled` and exits (no-op).

**Per user (own DB transaction each — one bad row can't roll back the batch):**

1. Read `staking_bonus_settings` once per run (`interval_days`, `percent`).
2. `amount = bcmul(bonus_balance, percent/100, 8)`; skip (no-op) if it rounds to 0.
3. `Walletledger_model::debit($user_id, 'bonus', $amount, 'bonus_reduction', [...])`
4. `Adminwallet_model::credit($amount, 'bonus_reduction', ['reference_user_id' => $user_id, ...])`
5. `UPDATE user_wallets SET bonus_last_reduced_at = NOW() WHERE user_id = ?` — **same transaction**.
6. Commit. On failure at 3/4, roll back that user only, log the error, continue.

Updating `bonus_last_reduced_at` inside the same transaction as the debit/credit
is what makes this idempotent even if the cron is accidentally triggered twice
in one day.

**Run summary (echoed as JSON, same shape as `Depositcron`):**
`{status, processed, skipped_disabled, total_reduced, total_credited_admin, ran_at}`

---

## 7. Admin-facing surfaces

- New page **Admin → Wallet → Admin Wallet** (`admin/wallet/admin-wallet`):
  - Current balance card + lifetime total reclaimed via bonus reduction
  - Ledger table (date, user, amount, reference_type, description), filterable
  - "Run Reduction Now" button (super-admin only; still respects
    `reduction_enabled`) for manual/testing runs
- Optional: show "Next reduction: `<date>`" on the user's own Bonus Wallet
  card — transparency up front avoids support disputes later.

## 8. User-facing statement

`Walletledger_model::statement($user_id, 'bonus')` already returns bonus
wallet history — a `bonus_reduction` row will show up there automatically
once the cron starts writing it; only a friendly label/description is needed
in the view, no schema change.

---

## 9. Rollout / safety

- Ship with `staking_bonus_settings.reduction_enabled = 0` in production
  until verified — add a `reduction_dry_run` flag too (same pattern as
  `swap_dry_run` in the swap engine): logs what *would* be reduced/credited
  without writing, so an admin can sanity-check total impact before going live.
- Backfilling `bonus_last_reduced_at = NOW()` (§4) means the first live
  reduction is 60 days after rollout, never immediate/retroactive.
- Fully reversible: because every reduction is a normal ledger entry, an
  admin can reverse a bad run with
  `Walletledger_model::credit(user_id, 'bonus', amount, 'admin_adjustment', …)`
  + `Adminwallet_model::debit(...)` — same double-entry principle, in reverse.

---

## 10. Open questions (need a decision before implementation starts)

1. **Anchor date** — is "every 60 days" per-user (rolling, from when their
   bonus was last reduced/first credited) or one shared global calendar cycle
   for all users at once, like the existing 60-day Rank Power cycles
   (`staking_rank_power_cycles`)? This plan assumes **per-user rolling**
   (simpler, no mass-event-on-one-day risk) — confirm before building.
2. **Can the Admin Wallet balance be spent/withdrawn** (e.g. topping up
   treasury BMAN/gas liquidity, or just a reporting number), or is it purely
   an audit total for now? Determines whether `admin_wallet.balance` needs a
   spend/withdraw flow in v1 or just accrues.
3. **User notification** — should users get an email/in-app notice at the
   moment of each reduction? Recommended (cuts support tickets), needs copy.
4. **Escape valve** — Bonus Transfer (§7) already lets a user move bonus to a
   direct left/right sponsor before the 60-day mark. Worth confirming this is
   the intended (and only) way to avoid the cut, and documenting it as such
   to users.

---

## 11. Files to add/touch (next implementation phase)

**New:**
- `db/admin_wallet.sql` — schema in §4
- `application/models/Adminwallet_model.php`
- `application/controllers/Bonusreductioncron.php`
- `application/controllers/admin/wallet/Adminwallet.php` + view

**Touch:**
- `config/routes.php` — cron route + admin page route
- `docs/0_INDEX.md` — status dashboard + task board (done as part of this plan)
- `docs/3_CHANGELOG.md` — this entry (done as part of this plan)
