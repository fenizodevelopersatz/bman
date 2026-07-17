# Rank Achievement System (§10) + Rank Power (§11)

Two rank systems that must never be confused.

| | **Achievement Rank** | **Rank Power** |
|---|---|---|
| Purpose | status, badge, certificate, reward, recognition | Group Incentive & leadership-pool qualification |
| Lifetime | **permanent — never downgraded** | resets every cycle (60 days) |
| Volume basis | **lifetime** completed downline staking | **current-cycle only** |
| Stored in | `user_ranks` (+ `user_rank_history`) | `user_rank_power` (per cycle) |
| Written by | `rank-achievement-cron` (hourly) | `rank-power-cron` (daily) |
| Drives money? | one-time rank reward | **yes — Group Incentive** |

> Achievement Rank = GOLD, Rank Power = SILVER → the member is paid the **SILVER**
> group incentive. Group Incentive reads power, never the permanent rank.

---

## 1. What was built on top of what already existed

This **extends** the rank tables that were already in the codebase. It does not
duplicate them, and there is no second source of truth.

Already present (unchanged as sources of truth):

- `staking_ranks` — the 11 ranks, tier 0 (UN RANK) → 10 (CHALLENGER), seeded
- `staking_rank_requirements` — the full Plan-1/2/3 matrix, seeded, with `option_no`
- `staking_rank_power_settings` / `staking_rank_power_cycles` / `user_rank_power`
- Admin pages `admin/staking/ranks` and `admin/staking/rank-power`

Added by `db/2026-07-17_rank_achievement_system.sql` (idempotent, additive only):

| Change | Why |
|---|---|
| `staking_ranks` **+** `required_group_volume`, `badge_image`, `reward_bman`, `reward_usdt`, `reward_description` | volume threshold and rewards had nowhere to live |
| `user_rank_power` **+** `left_volume`, `right_volume`, `total_volume`, `calculated_at` | §11 needs the cycle volume split |
| `user_ranks` | permanent rank, one row per member |
| `user_rank_history` | every promotion |
| `rank_rewards` | issuance proof · `UNIQUE(user_id, rank_id, reward_type)` |
| `rank_certificates` | one per member per rank · `UNIQUE(user_id, rank_id)` |
| `rank_certificate_series` | atomic per-(rank, year) serial counter |
| `staking_rank_audit` | audit trail (mirrors `staking_roi_audit`) |
| `user_notifications` | the legacy `notification` table has no `user_id` |
| `rank_cron_state` | batch cursor + run lock |
| `vw_user_ranks`, `vw_user_rank_power` | read-only convenience views |

### Two deliberate deviations from the original spec

1. **No `power_rank` column was added.** `user_rank_power.power_rank_id`
   (FK → `staking_ranks`) already served that purpose. A second name column
   would have been exactly the duplicate source of truth the brief forbade.
   Read the name via join, or via `vw_user_rank_power`.

2. **`rank_definitions` / `rank_qualification_plans` were not created.** They
   would duplicate `staking_ranks` / `staking_rank_requirements`, which are
   already seeded and already wired to live admin pages. The existing schema is
   also strictly more capable: the flat `left_required_rank` + `right_required_rank`
   shape in the brief **cannot express PLATINUM Plan-1**
   ("Left 2 GOLD + Right 1 GOLD **OR** Left 1 GOLD + Right 2 GOLD"),
   whereas `option_no` already does — and the seed data already encodes it.

### `required_group_volume` vs `group_incentive`

They are different numbers that happen to share a seed value.

- `required_group_volume` — downline volume needed to **reach** the rank
- `group_incentive` — the amount **paid** at that rank

Editing one does not change the other.

---

## 2. Group volume rules

`Rankcalculator_model::calculateGroupVolume($user_id [, $from, $to])`

- Source: `staking_swap_orders.bman_amount`
- Counted **only** when `status='completed'` **AND** `cron_status='completed'`
  (this pair excludes cancelled / failed / pending / rejected / expired / refunded)
- **Downline only** — a member's own staking never counts
- Whole binary tree, unlimited depth, via `binary_placement`
- `$from`/`$to` window it — this is how Rank Power counts current-cycle-only
  business off the same code path

**Consequence worth knowing:** UN RANK requires 1,000 group volume like any other
rank, so a member with no downline can never hold UN RANK. Rank accrues upward
from members who have teams beneath them.

### Rank counting

`countQualifiedRanks($user_id, $side, $tier)` counts the **whole leg**, unlimited
depth — not just direct referrals. A **higher rank satisfies a lower requirement**:
"2 GOLD on the left" is met by 2 DIAMONDs. Counting is `tier >= required_tier`.

---

## 3. Qualification

For rank R a member must satisfy **both**:

1. `total_volume >= R.required_group_volume`, and
2. **any one** qualification plan.

Plans are OR. Options inside a plan are OR. Conditions inside one option are AND
(left + right). A member never has to satisfy all plans.

```
PLATINUM Plan-1  Option-1: Left 2 GOLD AND Right 1 GOLD
                 Option-2: Left 1 GOLD AND Right 2 GOLD   ← OR alternative
         Plan-2  Left 2 GOLD AND Right 6 SILVER
         Plan-3  Left 6 SILVER AND Right 2 GOLD
```

The engine walks ranks **highest → lowest** and takes the first qualifying rank,
so a member who jumps two tiers lands on the higher one.

---

## 4. Permanent rank protection

Enforced in one place — `Rankachievement_model::_promote()` refuses any tier at
or below the stored `highest_rank_id`, re-checked under a `FOR UPDATE` row lock.
The evaluation loop also breaks as soon as it reaches the held tier.

A GOLD member whose volume collapses to zero stays GOLD forever. No code path
writes a lower rank; there are no downgrade rows in `user_rank_history` by design.

---

## 5. Crons

| | Achievement | Power |
|---|---|---|
| Route | `/rank-achievement-cron?token=…` | `/rank-power-cron?token=…` |
| CLI | `php index.php rankachievementcron run` | `php index.php rankpowercron run` |
| Frequency | **hourly** | **daily** |
| Batch | 500 members/run, cursor in `rank_cron_state` | 500/run |
| Extra flags | `&all=1` sweep everything | `&all=1`, `&cycle_only=1` |

Both are also buttons in **Cron Lab** and on the rank admin pages. Logic lives in
`Rankcron_model`; the controllers are thin transport wrappers (no SQL in
controllers).

**Bottom-up ordering.** A member's rank depends on the ranks below them, so the
batch is ordered **deepest-first**. A promotion at depth 12 is already visible
when depth 11 is evaluated in the same pass, so a whole chain settles in **one**
sweep instead of one sweep per level.

**Run lock.** A conditional `UPDATE … WHERE running = 0 OR heartbeat < ?` claims
the lock atomically. A lock older than 30 minutes is treated as a crashed run and
reclaimed. Release by hand from the Rank Power page if ever needed.

### Idempotency

Re-running is free and safe:

- no new qualification → **no write at all**
- reward already issued → blocked by `UNIQUE(user_id, rank_id, reward_type)`
- certificate already issued → blocked by `UNIQUE(user_id, rank_id)`
- notification already sent → blocked by `UNIQUE(user_id, type, reference_type, reference_id)`

Those **unique indexes** are the guarantee, not application logic. The code checks
first only to skip cleanly; the index is what holds under concurrency.

> CI3's DB driver does **not** throw on a duplicate key (with `db_debug` on it
> halts via `show_error()`; with it off it returns `FALSE`). Neither is catchable,
> so every dedupe insert uses `INSERT IGNORE` + `affected_rows()`. Do not
> "simplify" these back to `$this->db->insert()` inside a `try/catch` — that
> silently breaks the second cron pass.

---

## 6. Rewards & wallet ledger

`Rankreward_model::ensureBenefits($user_id, $rank_id)` — called on **every** pass
for a member's current rank, not only the promoting pass, so a payout that failed
once is retried rather than lost.

- BMAN reward → `earning` wallet · USDT reward → `usdt` wallet
- Always via `Walletledger_model::credit(…, 'rank_reward', …)` — money never moves
  outside the ledger, which owns balances and locks the row
- A reward row is written `pending` **before** the credit, flipped to `paid` with
  its `wallet_ledger_id` after. A crash in between leaves it `pending`, and the
  next pass retries it.
- Rewards are issued **outside** the promotion transaction, because
  `Walletledger_model` runs its own transaction and must not be nested.
- `reward_description` records a non-cash reward as `pending` for an admin to
  fulfil by hand. No money moves.

Ranks ship with **0 reward** — set the amounts in Rank Definitions or nothing pays.

---

## 7. Certificates

Format `BMAN-GOLD-2026-000001`, from an atomic per-`(rank, year)` counter
(`rank_certificate_series`), so the first GOLD of the year really is `000001`.
Numbers are **unique but not gap-free** — a serial burned by a losing race is
skipped rather than reused.

Certificates render as a **print-ready A4 landscape page**
(`admin/staking/rank-certificate/<no>`) rather than a generated PDF binary: this
project vendors no PDF library, and Print → Save as PDF produces the same
artefact with no new dependency. `rank_certificates.certificate_pdf` exists if
you later archive the files.

---

## 8. Rank Power & the 60-day cycle

Cycles are contiguous and exactly `cycle_days` long, inclusive:

```
#1  01 Jan → 01 Mar      (60 days: 01 Jan is day 1, 01 Mar is day 60)
#2  02 Mar → 30 Apr
#3  01 May → 29 Jun
```

Each starts the day after the previous ends, so no staking order can fall between
two cycles or be counted by both. The daily cron rolls an expired cycle
automatically (`auto_open_next_cycle`); power resets implicitly because a new
cycle has no `user_rank_power` rows.

**Power rank is volume-driven** — the highest rank whose `required_group_volume`
is covered by this cycle's total downline volume. The Plan-1/2/3 team matrix is
deliberately **not** applied here: §11 defines power purely as current-cycle
business, and the matrix belongs to the permanent ladder.

### Group Incentive integration — the one function to call

```php
$this->load->model('staking/Rankpower_model', 'power');

$rank   = $this->power->incentiveRankFor($user_id);   // rank row, or null if unqualified
$amount = $this->power->incentiveAmountFor($user_id); // '0' when unqualified
```

Any group-incentive calculation must read the payable rank from here. It resolves
to the **power** rank for the open cycle. If `is_enabled` or
`controls_group_incentive` is switched off it falls back to the permanent rank —
that is an admin explicitly opting out of §11, and the Rank Power page warns when
it is off.

---

## 9. Admin pages

**Sidebar → Rank Management**

| Page | URL |
|---|---|
| Rank Definitions (+ Qualification Plans) | `admin/staking/ranks` |
| Rank History | `admin/staking/rank-history` |
| Rank Rewards | `admin/staking/rank-rewards` |
| Rank Certificates | `admin/staking/rank-certificates` |
| Rank Power | `admin/staking/rank-power-users` |
| Rank Reports | `admin/staking/rank-reports` |
| Rank Audit Log | `admin/staking/rank-audit` |

Cycle settings stay on the existing `admin/staking/rank-power` page. Permission
key: `staking_management` **or** legacy `rank_management`.

## 10. Reports

Six reports, each exportable to **CSV / Excel / PDF**:
Rank Distribution · Top Rank Earners · Rank Progress · Rank Reward Summary ·
Rank Power Summary · Group Incentive Qualification.

Excel is an Excel-readable HTML table sent as `.xls`; PDF is a print-ready page
(no PhpSpreadsheet / PDF library is vendored here). Screen shows 500 rows,
exports up to 10,000.

> **Rank Progress reads the stored `group_volume`**, refreshed each hourly cron
> pass — it is not live. That keeps the report cheap instead of walking every
> member's tree. Open a member from Rank History for a live figure.

---

## 11. Badges

Art ships in `assets/rank/` (11 JPEGs) and is seeded into
`staking_ranks.badge_image` as web-root-relative paths, rendered with
`base_url()`:

| Tier | Rank | File |
|---|---|---|
| 0 | UN RANK | `assets/rank/un_rank.jpeg` |
| 1 | IRON | `assets/rank/iron.jpeg` |
| 2 | BRONZE | `assets/rank/bronze.jpeg` |
| 3 | SILVER | `assets/rank/silver.jpeg` |
| 4 | GOLD | `assets/rank/gold.jpeg` |
| 5 | PLATINUM | `assets/rank/platinum.jpeg` |
| 6 | EMERALD | `assets/rank/emerald.jpeg` |
| 7 | DIAMOND | `assets/rank/diamond.jpeg` |
| 8 | MASTER | `assets/rank/master.jpeg` |
| 9 | GRANDMASTER | `assets/rank/grand_master.jpeg` |
| 10 | CHALLENGER | `assets/rank/challenger.jpeg` |

Seeding only fills rows still `NULL`, so a badge uploaded through the admin page
(which writes to `uploads/rank_badges/`) is never overwritten by a re-run.
`badge_color` is kept as the fallback dot for compact UI.

---

## 12. Member API

Session-authenticated, read-only. Every endpoint answers for the **logged-in**
member — the user id comes from the session, never the request.

```
GET  api/rank                    my achievement rank + power rank + progress
GET  api/rank/progress           progress card only
GET  api/rank/history            my promotions
GET  api/rank/rewards            my rewards
GET  api/rank/certificates       my certificates (+ view_url)
GET  api/rank/ladder             all 11 ranks + plans (public config)
GET  api/rank/leaderboard        top members (badge + username only)
GET  api/rank/badge/(:num)       one member's badge — for genealogy tree nodes
GET  api/rank/notifications      my rank notifications
POST api/rank/notifications/read mark read
```

Nothing here can promote a member or move money — rank changes come from the cron
alone.

---

## 13. Install

```bash
mysql -u root -p <database> < db/2026-07-17_rank_achievement_system.sql
```

Idempotent and additive — safe to re-run, and it drops nothing. Then:

1. **Rank Definitions** → set `reward_bman` / `reward_usdt` per rank
   (they ship at 0, so nothing pays until you do).
2. Schedule the crons:
   - `/rank-achievement-cron?token=<cron_token>` — hourly
   - `/rank-power-cron?token=<cron_token>` — daily
3. Press **Roll cycle if expired** once on Rank Power to open cycle #1
   (or just let the daily cron do it).
4. Point Group Incentive at `Rankpower_model::incentiveRankFor()`.

---

## 14. Files

**Models** (`application/models/staking/`)
`Rankcalculator_model` (volume + tree) · `Rankachievement_model` (promotion engine) ·
`Rankreward_model` (rewards + certificates) · `Rankpower_model` (cycles + power +
**group-incentive integration point**) · `Rankaudit_model` (audit + notifications) ·
`Rankcron_model` (batch runner + lock) · `Rankreport_model` (the six reports)

**Controllers**
`RankAchievementCron` · `RankPowerCron` · `admin/staking/Rankmanagement` ·
`api/Rankapi` · (extended) `admin/staking/Ranks`, `admin/wallet/Cronlab`

**Views** `application/views/admin/staking/rank_*.php` + `_rank_head`/`_rank_foot`
shared chrome

**Extended** `Staking_model::saveRank()` (new fields + per-field audit),
`routes.php`, `admin_sidebar.php`, `ranks.php`

---

## 15. Verification

Verified against a real MariaDB copy with a purpose-built 4-level binary tree —
**171 assertions, all passing** (66 engine + 67 admin/report + 38 port-check).
Covered: volume excludes own staking and every non-completed status; unlimited-depth
traversal; the full promotion chain settling in one pass; permanent protection under
volume collapse; no double-pay across repeated runs; per-rank certificate serials;
PLATINUM's two `option_no` routes; higher-rank-satisfies-lower; power/achievement
separation; all six reports; badge art resolving on disk.

Three real bugs were found and fixed this way, all of which would have reached
production:

1. `cursor` is a **reserved word** in MariaDB — renamed to `cursor_pos`.
2. Duplicate-key **exceptions are not catchable** in CI3 — the second cron pass
   would have crashed. Now `INSERT IGNORE` everywhere.
3. `_auditRankChange` compared **text fields as floats**, so every string change
   (`reward_description`, `badge_color`, `badge_image`) compared `0 === 0` and was
   silently never audited.
