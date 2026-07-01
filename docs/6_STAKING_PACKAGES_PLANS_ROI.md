# 6 — Staking: Packages, Plans & ROI Structure (Pre‑Plan)

Design/pre-plan for managing **Staking Packages**, **Staking Plans** and the
**ROI Structure** for the BMAN platform, derived from
`Client_requirements/BMAN STAKING MASTER PROPOSAL DETAILS.pdf` (§4 Packages,
§5 Plans, §6 ROI, §7 Bonus, §12 Group Ceiling).

> Status: **planning** (tables proposed, not yet created). Links:
> [0_INDEX.md](0_INDEX.md) · [3_CHANGELOG.md](3_CHANGELOG.md).
> Legend: ✅ done · 🟡 in progress · ⬜ planned.

---

## 1. Concept — how the three pieces relate

A **Package** is a fixed stake amount (5,000 … 500,000 BMAN). A **Plan** is *how*
the stake earns (Fixed / Regular / Combo) and for how long (2 / 3 / 5 years). The
**ROI Structure** is the matrix that says, for a given *package × plan × duration*,
what the return is. When a user stakes, we resolve one ROI value from that matrix
and **snapshot** it onto the stake (so later admin edits never change live stakes).

```mermaid
flowchart LR
  P["Package<br/>(stake amount)"] --> R["ROI Structure<br/>(matrix cell)"]
  PL["Plan<br/>(fixed/regular/combo)"] --> R
  D["Duration<br/>(2y / 3y / 5y)"] --> R
  R --> S["User Stake<br/>(snapshot ROI %)"]
  S --> Pay["ROI Payouts<br/>(maturity or monthly)"]
```

**Key rule:** ROI % is read from the matrix **once**, at purchase, and copied to
`user_stakes.roi_percent`. Business rule from the proposal: *distribution/params
locked after transaction confirmation*.

---

## 2. Existing vs new tables

| Existing (legacy, keep) | Why not reused |
|---|---|
| `package_config` | Generic single-package MLM config (`roi`, `duration` are `varchar`); can't hold a 9×2×3 matrix |
| `user_investment` | Loosely-typed legacy investment log (`varchar` amounts) |
| `token_config` | Holds the BMAN coin/symbol — **reuse** for coin metadata |

**Proposal:** add dedicated, strongly-typed tables (`staking_packages`,
`staking_plans`, `staking_plan_terms`, `staking_roi_structure`,
`staking_roi_audit`, `user_stakes`, `staking_roi_payouts`). Legacy tables stay
untouched → backward compatible.

---

## 3. Field definitions

### 3.1 Staking Package fields

| Field | Type | Notes |
|---|---|---|
| `name` | string | Display name, e.g. `5,000 BMAN` |
| `stake_amount` | decimal | The BMAN amount (unique) |
| `bonus_percent` | decimal | Staking bonus coin % (default **25**, §7) |
| `group_ceiling` | decimal | Group incentive ceiling (§12) |
| `sort_order` | int | Display order |
| `is_active` | bool | Enable / disable |

### 3.2 Staking Plan fields

| Field | Type | Notes |
|---|---|---|
| `name` / `code` | string / enum | `Fixed` `Regular` `Combo` |
| `roi_credit_mode` | enum | `maturity` (Fixed) · `monthly` (Regular) · `mixed` (Combo) |
| `credit_days` | string | Regular credit dates → `5,15,25` (§5) |
| `withdraw_after_maturity` | bool | Fixed = withdraw only at maturity |
| `withdraw_frequency_days` | int | Regular = `30` (withdraw window) |
| `min/max_withdraw_bman` | decimal | `3000` / `10000` (admin adjustable, §5) |
| `min/max_withdraw_usdt` | decimal | `30` / `100` (admin adjustable, §5) |
| `combo_fixed_pct` / `combo_regular_pct` | decimal | `50` / `50` (§5) |
| `is_active` | bool | Enable / disable |

**Durations** (`staking_plan_terms`): `plan_id`, `duration_years` (2/3/5), `is_active`.

### 3.3 ROI Structure fields (the matrix)

| Field | Type | Notes |
|---|---|---|
| `package_id` | FK | → `staking_packages` |
| `plan_code` | enum | `fixed` · `regular` (Combo is derived 50/50) |
| `duration_years` | tinyint | 2 · 3 · 5 |
| `roi_percent` | decimal(8,3) | e.g. `150.000` (Fixed total) / `2.300` (Regular monthly) |
| `roi_basis` | enum | `total` (Fixed, whole term) · `monthly` (Regular, per month) |
| `effective_from` | date | Version start; enables history |
| `is_active` | bool | Current row for that cell |
| `created_by` | int | Super-Admin id (audit) |

---

## 4. MySQL table structures (DDL)

```sql
-- 4.1 Packages
CREATE TABLE `staking_packages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL,
  `stake_amount` DECIMAL(20,4) NOT NULL,
  `bonus_percent` DECIMAL(6,2) NOT NULL DEFAULT 25.00,
  `group_ceiling` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_amount` (`stake_amount`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4.2 Plans
CREATE TABLE `staking_plans` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(60) NOT NULL,
  `code` ENUM('fixed','regular','combo') NOT NULL,
  `roi_credit_mode` ENUM('maturity','monthly','mixed') NOT NULL,
  `credit_days` VARCHAR(40) DEFAULT NULL,            -- "5,15,25"
  `withdraw_after_maturity` TINYINT(1) NOT NULL DEFAULT 0,
  `withdraw_frequency_days` INT NOT NULL DEFAULT 0,  -- 30 for Regular
  `min_withdraw_bman` DECIMAL(20,4) DEFAULT NULL,    -- 3000
  `max_withdraw_bman` DECIMAL(20,4) DEFAULT NULL,    -- 10000
  `min_withdraw_usdt` DECIMAL(20,4) DEFAULT NULL,    -- 30
  `max_withdraw_usdt` DECIMAL(20,4) DEFAULT NULL,    -- 100
  `combo_fixed_pct` DECIMAL(6,2) DEFAULT NULL,       -- 50
  `combo_regular_pct` DECIMAL(6,2) DEFAULT NULL,     -- 50
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4.3 Durations available per plan
CREATE TABLE `staking_plan_terms` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_id` INT UNSIGNED NOT NULL,
  `duration_years` TINYINT NOT NULL,                 -- 2,3,5
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_plan_term` (`plan_id`,`duration_years`),
  CONSTRAINT `fk_term_plan` FOREIGN KEY (`plan_id`) REFERENCES `staking_plans`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4.4 ROI matrix (package × plan × duration), effective-dated
CREATE TABLE `staking_roi_structure` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `package_id` INT UNSIGNED NOT NULL,
  `plan_code` ENUM('fixed','regular') NOT NULL,
  `duration_years` TINYINT NOT NULL,
  `roi_percent` DECIMAL(8,3) NOT NULL,
  `roi_basis` ENUM('total','monthly') NOT NULL,
  `effective_from` DATE NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lookup` (`package_id`,`plan_code`,`duration_years`,`is_active`,`effective_from`),
  CONSTRAINT `fk_roi_pkg` FOREIGN KEY (`package_id`) REFERENCES `staking_packages`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4.5 Audit trail for ROI edits (Super-Admin only, per proposal business rules)
CREATE TABLE `staking_roi_audit` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `roi_id` INT UNSIGNED DEFAULT NULL,
  `package_id` INT UNSIGNED NOT NULL,
  `plan_code` VARCHAR(10) NOT NULL,
  `duration_years` TINYINT NOT NULL,
  `old_percent` DECIMAL(8,3) DEFAULT NULL,
  `new_percent` DECIMAL(8,3) NOT NULL,
  `changed_by` INT NOT NULL,
  `note` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4.6 User stake (snapshots ROI at purchase — locked)
CREATE TABLE `user_stakes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `package_id` INT UNSIGNED NOT NULL,
  `plan_id` INT UNSIGNED NOT NULL,
  `plan_code` ENUM('fixed','regular','combo') NOT NULL,
  `duration_years` TINYINT NOT NULL,
  `stake_amount` DECIMAL(20,4) NOT NULL,
  `roi_percent` DECIMAL(8,3) NOT NULL,               -- snapshot
  `roi_basis` ENUM('total','monthly') NOT NULL,
  `bonus_amount` DECIMAL(20,4) NOT NULL DEFAULT 0,   -- 25% bonus coin
  `distribution_option_id` INT DEFAULT NULL,         -- §3A coin distribution
  `start_date` DATE NOT NULL,
  `maturity_date` DATE NOT NULL,
  `status` ENUM('active','matured','withdrawn','cancelled') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4.7 ROI credit history (maturity or monthly)
CREATE TABLE `staking_roi_payouts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stake_id` BIGINT UNSIGNED NOT NULL,
  `user_id` INT NOT NULL,
  `amount` DECIMAL(20,4) NOT NULL,
  `credit_date` DATE NOT NULL,
  `wallet` ENUM('earning','staking') NOT NULL DEFAULT 'earning',
  `status` ENUM('pending','paid','failed') NOT NULL DEFAULT 'paid',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stake` (`stake_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 5. Seed data (from the proposal)

### 5.1 Packages (§4 + §12 ceiling + §7 bonus)

| name | stake_amount | bonus_% | group_ceiling |
|---|--:|--:|--:|
| 5,000 BMAN | 5,000 | 25 | 5,000 |
| 10,000 BMAN | 10,000 | 25 | 10,000 |
| 20,000 BMAN | 20,000 | 25 | 20,000 |
| 25,000 BMAN | 25,000 | 25 | 25,000 |
| 50,000 BMAN | 50,000 | 25 | 30,000 |
| 100,000 BMAN | 100,000 | 25 | 30,000 |
| 200,000 BMAN | 200,000 | 25 | 50,000 |
| 300,000 BMAN | 300,000 | 25 | 70,000 |
| 500,000 BMAN | 500,000 | 25 | 100,000 |

### 5.2 Plans (§5)

| code | credit_mode | credit_days | withdraw rule | durations |
|---|---|---|---|---|
| fixed | maturity | — | after maturity only | 2, 3, 5 |
| regular | monthly | 5,15,25 | 30-day window, 3000–10000 BMAN (30–100 USDT) | 2, 3, 5 |
| combo | mixed | 5,15,25 | 50% fixed + 50% regular | 2, 3, 5 |

### 5.3 ROI matrix (§6) — `roi_percent` / `roi_basis`

| Stake | Fixed 2Y | Fixed 3Y | Fixed 5Y | Regular 2Y | Regular 3Y | Regular 5Y |
|---:|--:|--:|--:|--:|--:|--:|
| 5,000 | 150% | 200% | 400% | 2.3% | 2.5% | 3.0% |
| 10,000 | 150% | 200% | 400% | 2.3% | 2.5% | 3.0% |
| 20,000 | 150% | 200% | 400% | 2.3% | 2.5% | 3.0% |
| 25,000 | 150% | 200% | 400% | 2.3% | 2.5% | 3.0% |
| 50,000 | 150% | 200% | 400% | 2.3% | 2.5% | 3.0% |
| 100,000 | 150% | 200% | 400% | 2.3% | 2.5% | 3.0% |
| 200,000 | 160% | 210% | 410% | 2.5% | 3.2% | 3.2% |
| 300,000 | 180% | 230% | 430% | 2.8% | 3.3% | 3.3% |
| 500,000 | 200% | 250% | 450% | 3.0% | 3.5% | 3.5% |

Fixed columns → `roi_basis = total` (whole term). Regular columns →
`roi_basis = monthly` (per-month). Combo is computed 50/50 from the two.

---

## 6. User-friendly management (admin UX)

Three admin screens under **Admin → Staking** (reuse the existing Metronic admin
layout & `admin/settings/*` pattern):

1. **Packages** — simple table CRUD: add/edit amount, bonus %, ceiling, drag to
   reorder, one-click enable/disable. Delete blocked if stakes exist (soft
   disable instead).
2. **Plans** — one card per plan (Fixed / Regular / Combo) with its rule fields;
   tick which durations (2/3/5) are offered. Withdraw min/max shown as editable
   fields (proposal says these can be increased/decreased).
3. **ROI Structure** — the centrepiece: an **inline-editable grid** that mirrors
   the proposal table (rows = packages, columns = Fixed 2/3/5 + Regular 2/3/5).
   Type a new number → cell turns amber (unsaved) → **Save** writes a new
   effective-dated row + an audit entry. Super-Admin only.

**Why this is safe & friendly**
- One grid = the whole ROI config at a glance (matches the PDF exactly).
- **Effective dating**: edits create a new versioned row; the old one stays for
  history. New stakes use the new %, live stakes keep their snapshot.
- **Audit**: every % change writes `staking_roi_audit` (old → new, who, when).
- **Guardrails**: % must be ≥ 0; Combo split must total 100; a package can't be
  deleted while it has active stakes.

---

## 7. Flowcharts

### 7.1 Admin configuration order
```mermaid
flowchart TD
  A[Create / edit Packages] --> B[Configure Plans + rules]
  B --> C[Enable durations 2y/3y/5y per plan]
  C --> D[Fill ROI matrix cells]
  D --> E[Set group-incentive ceilings]
  E --> F[Activate & publish]
```

### 7.2 ROI edit (versioned + audited)
```mermaid
flowchart TD
  A[Super-Admin edits a cell] --> B{% >= 0 and date valid?}
  B -- no --> A
  B -- yes --> C[Set old active row is_active = 0]
  C --> D[Insert new row with effective_from]
  D --> E[Write staking_roi_audit old -> new]
  E --> F[New stakes use new %<br/>Existing stakes keep snapshot]
```

### 7.3 User stake purchase → ROI resolution
```mermaid
flowchart TD
  A[Select Package] --> B[Select Plan]
  B --> C[Select duration 2y/3y/5y]
  C --> D[Resolve active ROI cell]
  D --> E[Select Coin Distribution option]
  E --> F{Wallet balance ok?}
  F -- no --> E
  F -- yes --> G[Debit wallet + confirm]
  G --> H[Insert user_stakes<br/>snapshot roi_percent + basis]
  H --> I[Credit 25% Bonus Coin]
  I --> J[Set maturity_date = start + years]
```

### 7.4 ROI credit engine
```mermaid
flowchart TD
  A{Plan type} -->|Fixed| M[At maturity: pay stake * total%]
  A -->|Regular| R[Cron 5th/15th/25th:<br/>pay stake * monthly%]
  A -->|Combo| X[Half via Fixed rule<br/>Half via Regular rule]
  M --> P[Insert staking_roi_payouts + credit Earning wallet]
  R --> P
  X --> P
```

---

## 8. Sample admin pages (wireframes)

### 8.1 Packages
```
Admin ▸ Staking ▸ Packages                              [ + Add Package ]
┌────┬───────────────┬──────────────┬────────┬──────────┬────────┬─────────┐
│ ⇅  │ Name          │ Stake (BMAN) │ Bonus% │ Ceiling  │ Active │ Actions │
├────┼───────────────┼──────────────┼────────┼──────────┼────────┼─────────┤
│ ⠿  │ 5,000 BMAN    │        5,000 │   25   │   5,000  │  [on]  │ ✎  🗑   │
│ ⠿  │ 10,000 BMAN   │       10,000 │   25   │  10,000  │  [on]  │ ✎  🗑   │
│ ⠿  │ …             │          …   │   …    │    …     │   …    │  …      │
└────┴───────────────┴──────────────┴────────┴──────────┴────────┴─────────┘
```

### 8.2 Plans
```
Admin ▸ Staking ▸ Plans
┌─ Fixed ──────────────────┐ ┌─ Regular ────────────────┐ ┌─ Combo ───────────┐
│ Credit: at maturity      │ │ Credit days: 5, 15, 25   │ │ Fixed 50 / Reg 50 │
│ Durations: ☑2 ☑3 ☑5      │ │ Withdraw window: 30 days │ │ Durations: ☑2☑3☑5 │
│ Withdraw: maturity only  │ │ Min/Max BMAN: 3000/10000 │ │ [Active ●]        │
│ [Active ●]               │ │ Min/Max USDT:  30 / 100  │ │                   │
└──────────────────────────┘ │ [Active ●]               │ └───────────────────┘
                             └──────────────────────────┘
```

### 8.3 ROI Structure (inline-editable grid)
```
Admin ▸ Staking ▸ ROI Structure     Effective from: [2026-07-01]  (Super-Admin)
┌───────────┬────────┬────────┬────────┬─────────┬─────────┬─────────┐
│ Stake     │ Fix 2Y │ Fix 3Y │ Fix 5Y │ Reg 2Y  │ Reg 3Y  │ Reg 5Y  │
├───────────┼────────┼────────┼────────┼─────────┼─────────┼─────────┤
│ 5,000     │[150.0 ]│[200.0 ]│[400.0 ]│[ 2.30 ] │[ 2.50 ] │[ 3.00 ] │
│ 200,000   │[160.0 ]│[210.0 ]│[410.0 ]│[ 2.50 ] │[ 3.20 ] │[ 3.20 ] │
│ 500,000   │[200.0 ]│[250.0 ]│[450.0 ]│[ 3.00 ] │[ 3.50 ] │[ 3.50 ] │
└───────────┴────────┴────────┴────────┴─────────┴─────────┴─────────┘
 Fix = total % over term   Reg = % per month     [ Save changes ]  [ View history ]
```

---

## 9. Business rules & validation

- ROI % `>= 0`; Fixed basis = `total`, Regular basis = `monthly`.
- Combo `fixed_pct + regular_pct = 100`.
- Regular withdraw: 30-day window, 3000–10000 BMAN (30–100 USDT), **admin
  adjustable** (proposal note "we need to increase or decrease").
- ROI is **snapshotted** at purchase and never back-changed.
- ROI edits: Super-Admin only, versioned + audited.
- A package with active stakes cannot be deleted (only disabled).
- Every stake grants **25% Bonus Coin** (§7); bonus wallet auto-reduces 50%
  every 60 days (separate engine).

---

## 10. Implementation checklist (CI3, matches existing structure)

- ⬜ Migration: 7 tables (§4) + seed (§5)
- ⬜ Model `Staking_model` — packages/plans CRUD, `resolveRoi($pkg,$plan,$dur)`, versioned ROI write + audit
- ⬜ Controller `admin/staking/Packages`, `admin/staking/Plans`, `admin/staking/Roistructure`
- ⬜ Views (Metronic): packages table, plans cards, ROI grid editor (JS inline edit → AJAX save)
- ⬜ Routes under `admin/staking/*` (mirror `admin/settings/*`)
- ⬜ User purchase flow writes `user_stakes` (snapshot) + 25% bonus
- ⬜ Cron: Regular/Combo monthly credit (5/15/25) + Fixed maturity credit → `staking_roi_payouts`
- ⬜ Reports: Staking report, ROI paid report (PDF/Excel/CSV per §18)
