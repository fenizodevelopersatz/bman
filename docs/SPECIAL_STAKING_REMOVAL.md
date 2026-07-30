# Special Staking → badge only

**Date:** 2026-07-30

"Special" used to be a second, parallel ROI engine. It is now nothing but a
label on a package. This documents what changed, what deliberately did not, and
what to check before trusting it in production.

---

## 1. What changed, in one line

A package flagged `is_special` used to be priced by a year-wise escalating ROI
schedule configured on its own admin page. It is now priced by the **same
Fixed / Regular / Combo matrix as every other package** — the flag only draws a
gold border and a SPECIAL chip.

### Before

```
Package flagged is_special
        │
        ▼
staking_special_roi  ──►  escalating year-wise monthly % + maturity %
        │
        ▼
createSpecialROIRecord()  ──►  roi_staking_management (is_special=1,
                                special_schedule_json snapshot)
```

### After

```
Every package, special-badged or not
        │
        ▼
staking_roi_structure  ──►  fixed % / regular %/mo  (the normal matrix)
        │
        ▼
createROIRecord()  ──►  roi_staking_management (normal record)
```

---

## 2. What was NOT changed — and why it matters

**Existing special stakes keep paying exactly what they were sold.** This was
the main risk in the change and it is fully avoided.

When a special stake was bought, the year-wise schedule was *snapshotted* onto
its own `roi_staking_management` row as `special_schedule_json`. The monthly ROI
cron reads that snapshot, never the `staking_special_roi` table
(`RoiMonthlyDistribution_cron.php:91`). So retiring the config cannot re-price a
live stake — a property the original design deliberately built in.

Left untouched on purpose:

| Kept | Reason |
|---|---|
| `RoiMonthlyDistribution_cron` special branch | Still pays live `is_special=1` records from their snapshot |
| `RoiStakingManagement_model::createSpecialROIRecord()` | No longer called, but defines a record shape still in production. Marked LEGACY in place |
| `staking_special_roi`, `staking_special_roi_audit` tables | Kept for audit — what rates special buyers were originally offered |
| `staking_packages.is_special` column + admin toggle | This is the badge |
| `user_stakes.is_special` | Badge source for past purchases |

> Do not drop `createSpecialROIRecord()` or the cron's special branch while any
> `is_special = 1` row remains in `roi_staking_management`.

---

## 3. Files changed

### Removed

- `application/controllers/admin/staking/Specialroi.php`
- `application/views/admin/staking/special_roi.php`
- `application/models/staking/Specialroi_model.php`

### Edited

| File | Change |
|---|---|
| `config/routes.php` | Dropped both `admin/staking/special-roi` routes |
| `views/admin/Layout/admin_sidebar.php` | Removed the "Special ROI (Offer)" menu item |
| `controllers/admin/staking/Plans.php` | Dropped the `special_packages` data feed |
| `views/admin/staking/plans.php` | Removed the "Special Offer Plan (ESCALATING)" card |
| `controllers/user/usersettings/Lendingcontroller.php` | Purchase path: removed the special branch; all packages use the normal matrix. Listing: special packages sorted first |
| `views/user/wallet/_staking_packages.php` | Card shows the normal Fixed/Regular table; SPECIAL chip kept; escalating table, dedicated purchase modal and ~170 lines of `stkSpecial*` JS removed |
| `models/RoiStakingManagement_model.php` | `createSpecialROIRecord()` annotated LEGACY |
| `views/admin/staking/roi_structure.php` | Removed the "Special Offer — Records & Cron Executions" panel and its script; added a SPECIAL badge to the matrix row label |
| `controllers/admin/staking/Roihistory.php` | Removed `special_distributions()` — its only caller was that panel |
| `views/user/dashboard/index.php` | Promo strip copy no longer claims escalating ROI |
| `models/Staking_model.php` | Duplicate-amount guard scoped to `(stake_amount, is_special)` — see §5 |
| `db/staking_module.sql` | Comment marking `uq_amount` superseded |

### Added

- `db/2026-07-30_package_amount_special_unique.sql` — swaps `uq_amount` for the
  composite `uq_amount_special`. **Must be run** (see §5).

### Unchanged by design

`admin/staking/packages` — the SPECIAL badge and the "Is Special" checkbox
already worked and still do. This was the "just add the special tag only, no
other change" requirement.

---

## 4. Page-by-page result

| Page | Result |
|---|---|
| `admin/staking/packages` | Unchanged. SPECIAL badge + Is-Special toggle still there |
| `admin/staking/plans` | Fixed / Regular / Combo only. Special Offer Plan card gone |
| `admin/staking/special-roi` | **404** — route, controller and view removed |
| User ▸ Stakings | Special packages listed first, then normal. Special cards keep the gold border and chip but show the same Fixed/Regular table and open the same SELECT modal |

---

## 5. Same amount, normal AND special

A stake amount used to be globally unique, so you could not have both:

```
2,000 BMAN            (normal)
2,000 BMAN  SPECIAL   (special)
```

Uniqueness is now scoped **within a kind**. One normal and one special per
amount is allowed; two normals, or two specials, on the same amount are still
rejected.

Two things enforce this and must stay in step:

| Layer | Where |
|---|---|
| Database | `UNIQUE KEY uq_amount_special (stake_amount, is_special)` |
| Application | `Staking_model::savePackage()` |

**This needs a migration — the app change alone is not enough.** The old
`uq_amount` index would reject the second row at the database level:

```bash
mysql -u root "e-commerce-mlm-v2" < db/2026-07-30_package_amount_special_unique.sql
```

The migration is guarded on `information_schema` at both steps, so re-running it
is a no-op. Verify with:

```sql
SHOW INDEX FROM `staking_packages` WHERE Key_name LIKE 'uq_amount%';
```

Expect `uq_amount_special` over two columns, and no `uq_amount`.

Nothing else in the codebase looks a package up by `stake_amount` — every
reference is by `package_id` — so allowing duplicates introduces no ambiguity.
The two cards are told apart on screen by the SPECIAL badge, and the special one
sorts into the group above.

> `db/staking_module.sql` still declares the old single-column key. It is the
> base schema and cannot declare the composite one, because `is_special` is only
> added later by `2026-07-24_special_offer.sql`. A comment there points at this
> migration. On a fresh import, apply: base → `2026-07-24` → `2026-07-30`.

---

## 6. Behaviour change to be aware of

Buying a special-badged package now uses the normal ROI matrix. If a package is
flagged special but has **no rows in `staking_roi_structure`**, its card will
render `—` for every term — the same as any other unconfigured package.

Before going live, run this and make sure every flagged package has rates on
**Admin ▸ Master ▸ ROI Settings**. Special packages previously bypassed that
page entirely, so their rows there may never have been filled in:

```sql
SELECT p.id, p.name, p.stake_amount, COUNT(r.id) AS roi_rows
FROM staking_packages p
LEFT JOIN staking_roi_structure r ON r.package_id = p.id AND r.is_active = 1
WHERE p.is_special = 1 AND p.is_active = 1
GROUP BY p.id, p.name, p.stake_amount;
```

Any row returning `roi_rows = 0` will render `—` for every term until its rates
are entered.

---

## 7. Verify

1. `admin/staking/special-roi` → 404, and no sidebar entry.
2. `admin/staking/plans` → three plan cards, no Special Offer block, Save still works.
3. `admin/staking/packages` → SPECIAL badge renders; toggling Is Special saves.
4. User ▸ Stakings → special packages appear first; their cards show Fixed/Regular
   columns; SELECT opens the standard purchase modal.
5. Buy a special package end-to-end → a **normal** `roi_staking_management` row
   (`is_special` unset, `special_schedule_json` empty).
6. Confirm an existing `is_special = 1` stake still credits on the next
   `roi-distribution-cron` run. This is the one that must not regress.
7. After running the migration: add a 2,000 BMAN normal package, then a 2,000
   BMAN package with **Is Special** ticked. Both should save. Adding a *third*
   at 2,000 with the same flag as one of them should be rejected with
   "A normal package…" / "A SPECIAL package with this stake amount already
   exists."

---

See [CRON_SCHEDULE.md](CRON_SCHEDULE.md) for how the ROI crons that pay these
records are scheduled.
