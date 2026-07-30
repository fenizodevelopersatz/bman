# Bulk Member Upload — Wallet Type & Cron Settings

**Date:** 2026-07-30  
**Feature Area:** Admin → Members Management → Bulk Upload  
**URL:** `/admin/member/bulk-upload`

---

## Overview

This document covers features added to the Bulk Member Upload module:

1. **Wallet Type field** — admin can choose which internal wallet (Exchange, Earning, Staking, Bonus) receives the BMAN credit from the Excel column, both globally (per upload) and per-row (via a sheet column).
2. **BMAN Cron Settings panel** — surfaced directly on the Bulk Upload page as a collapsible card, so the admin can enable/disable the cron, toggle dry-run mode, and change limits without navigating away.
3. **Post-import form reset + result banner** — after clicking "Import", the form resets automatically and a green/red banner shows the outcome.
4. **Duplicate batch management** — staged batches now have a Cancel button in the history table.
5. **Interactive Tabs & AJAX Pagination** — the History Table is now paginated and filterable by status tabs ("All Uploads", "Drafts (Staged)", "Importing", "Completed", "Failed / Cancelled") without page reloads.

---

## Database Changes

### Migration file
```
db/2026-07-30_bulk_upload_wallet_type.sql
```

Run this once on any environment to add the three new columns. The script is idempotent (safe to re-run).

### Columns added

| Table | Column | Type | Default | Purpose |
|---|---|---|---|---|
| `member_bulk_upload_settings` | `wallet_type` | `ENUM('exchange','earning','staking','bonus')` | `exchange` | Site-wide default — pre-selects the dropdown on the upload form |
| `member_bulk_upload_batches` | `wallet_type` | same ENUM | `exchange` | Records which default was in effect when the batch was uploaded |
| `member_bulk_upload_rows` | `wallet_type` | same ENUM | `exchange` | Effective per-row wallet (from sheet column or batch default) |

---

## Files Changed

### 1. `application/models/member/Memberbulkupload_model.php`

| What changed | Detail |
|---|---|
| `$headerAliases` | Added `wallet_type` key with aliases: `wallettype`, `wallet`, `walletname`, `creditwallet`, `targetwallet`, `receiverwallet` |
| `$templateColumns` | Added `wallet_type` as 6th column (after `bman`) |
| `$walletTypes` | New constant: `['exchange','earning','staking','bonus']` |
| `settings()` fallback | Includes `wallet_type => 'exchange'` so the page never crashes if the DB row is missing |
| `updateSettings()` | `wallet_type` added to the allowed-field list |
| `stage()` | Reads `$opts['wallet_type']` as the batch default; reads per-row `wallet_type` cell; validates against `$walletTypes`; invalid values fall back to batch default with an error flag; stores `wallet_type` on each parsed row |
| `persistBatch()` | Includes `wallet_type` in the batch INSERT |
| `batches($limit, $offset, $status)` | Added `$status` parameter to filter returned rows for tabs support |
| `countBatches($status)` | Created function to get total rows count for dynamic pagination |

### 2. `application/models/member/Memberbulkbmancron_model.php`

| What changed | Detail |
|---|---|
| `_creditExchange()` | Reads `$r['wallet_type']` from the row (falls back to `'exchange'` for old rows). Passes the wallet to `Walletledger_model::credit()` instead of hard-coding `'exchange'`. Ledger description now says e.g. `"opening BMAN balance (Earning wallet)"`. |
| `_findLedgerId()` | Accepts `$wallet` parameter (default `'exchange'`). Used to look up the ledger row by `(tx_hash, wallet_type)` when the credit was already posted in a previous attempt. |

### 3. `application/controllers/admin/member/Memberbulkupload.php`

| Method | What changed |
|---|---|
| `stage()` | Passes `wallet_type` from POST to `$this->bulk->stage(...)` opts |
| `updateSettings()` | Passes `wallet_type` from POST to `$this->bulk->updateSettings(...)` |
| `template()` | Sample rows now include example `wallet_type` values (`exchange`, `earning`, empty) |
| `export()` | Adds a **Wallet Type** column to the exported audit sheet |
| `history()` | **[NEW]** JSON endpoint returning paginated history data filtered by status for dynamic rendering |

### 4. `application/views/admin/member/bulk_upload.php`

#### Upload form (card 1)
- **Wallet Type dropdown** (`<select name="wallet_type">`) between the Default Password field and the Queue BMAN toggle.
  - Options: Exchange / Earning / Staking / Bonus
  - Pre-selected from `$settings['wallet_type']`
  - Helper text: "Add a `wallet_type` column in your sheet to override per row."

#### BMAN Cron Settings (card 2 — collapsible)
- Collapsible card with a status badge: DISABLED / DRY-RUN / LIVE
- Left column — toggles:
  - Enable BMAN Cron (`enabled`)
  - Dry-Run Mode (`dry_run`)
  - Credit Wallet on Delivery (`credit_exchange_wallet`)
- Right column — numeric/select:
  - Default Wallet Type (site-wide, saved to `member_bulk_upload_settings`)
  - Min Treasury Reserve (BMAN)
  - Max Batch Size
  - Max Rows per File
- **Save Settings** button POSTs to `/admin/member/bulk-upload/settings`
- On save: toast + page reload to refresh badge state

#### Preview table (card 3 — hidden until file validated)
- Added **Wallet** column between BMAN and Status columns
- Colour-coded badges: Exchange = blue, Earning = green, Staking = yellow, Bonus = purple

#### Post-import behaviour
- **Form resets** automatically (file cleared, fields back to defaults, preview hidden)
- **Result banner** appears above the upload card:
  - Green banner on success with a **View Batch** link
  - Red banner on failure with the error message
  - **Dismiss** button to close
- History table updates automatically via AJAX without page reload

#### History table (card 4)
- **Dynamic Tabs**: All Uploads, Drafts (Staged), Importing, Completed, Failed / Cancelled.
- **Pagination Component**: outline buttons displaying matching page numbers and pagination info text.
- **Cancel button** appears on every row with status `STAGED`. On click, fires AJAX call, reloads table state inline.
- Removed duplicate table and preview cards from the view.

#### Batch Detail page (`/admin/member/bulk-upload/batch/[id]`)
- **Row Checkboxes**: A checkbox column is added on the left side of the rows table (only active when the batch is `STAGED`).
- **Master Select Checkbox**: Added in the table header to check/uncheck all rows simultaneously.
- **Bulk Action Buttons**: "Include Selected" (Approve) and "Exclude Selected" (Reject) buttons are added in the card header.
- **Individual Action Buttons**: "Include" (Approve) / "Exclude" (Reject) inline button added in the last table column to toggle rows one-by-one.
- **AJAX Sync**: Updates row status dynamically on the backend and recalculates batch metadata stats (`valid_rows`, `invalid_rows`, `bman_queued`, `bman_total`).

---


## Excel Sheet Format

The template now has 6 columns (download via the "Download Template" button):

| Column | Required | Notes |
|---|---|---|
| `username` | ✅ | Must be unique |
| `email` | ✅ | Must be unique; used as login identity |
| `password` | ❌ | If blank, uses the "Default password" field from the form |
| `reference_id` | ✅ | Sponsor's referral code; binary engine handles placement automatically |
| `bman` | ❌ | Amount queued for the on-chain cron. Leave blank for 0. |
| `wallet_type` | ❌ | `exchange` / `earning` / `staking` / `bonus`. If blank, uses the form's "Default Wallet Type". |

---

## How Duplicate Uploads Are Handled

### No real duplicates created
- A **staged** batch has **zero** members created. It is a preview-only draft.
- If the admin imports two batches with the same email/username, the second import will reject those rows with "Email already exists" — no duplicate accounts are made.
- Duplicate emails **within the same file** are caught at validation (stage) time and flagged as INVALID before any import.

### Cleaning up accidental staged batches
1. Open `/admin/member/bulk-upload`
2. In the **Upload History & Transaction Audit** table, find all rows showing **STAGED**
3. Click the **Cancel** button on each unwanted batch
4. Cancelled batches are marked `cancelled` and can never be imported

---

## BMAN Cron — How It Works

```
Upload (stage + import)
    │
    └─► member_bulk_upload_rows  (bman_status = 'pending', wallet_type = ?)
                │
                │  [BMAN cron sweeps every N minutes]
                ▼
        On-chain send (Treasury → member wallet address)
                │
                └─► Walletledger_model::credit(user_id, wallet_type, amount)
                        → user_wallets[wallet_type] balance updated
                        → wallet_ledger row inserted
```

**Safety defaults (do not change without approval):**

| Setting | Safe default | Meaning |
|---|---|---|
| `enabled` | `0` | Cron does nothing; queue just grows |
| `dry_run` | `1` | Records a `DRYRUN-` tx hash; nothing broadcast |
| `credit_exchange_wallet` | `1` | Credits the member's internal wallet after on-chain send |
| `wallet_type` | `exchange` | Which wallet gets the credit |
| `min_treasury_reserve` | `0` | Cron stops if treasury on-chain balance would fall below this |
| `max_batch_size` | `20` | Rows claimed per cron pass |
| `max_rows_per_file` | `1000` | Rejects files larger than this |

Turn LIVE: set `enabled = 1` AND `dry_run = 0` via the Cron Settings card.

---

## Valid Wallet Types

| Value | Label | Wallet column in `user_wallets` |
|---|---|---|
| `exchange` | Exchange Wallet | `exchange_wallet` |
| `earning` | Earning Wallet | `earning_wallet` |
| `staking` | Staking Wallet | `staking_wallet` |
| `bonus` | Bonus Wallet | `bonus_wallet` |

---

## Rollback Notes

If you need to revert the wallet_type feature while keeping the bulk-upload module:

1. Revert the three model/controller/view files to their previous state.
2. The three `wallet_type` columns are harmless to leave in place (they have a safe `DEFAULT 'exchange'` which maintains the original behaviour).
3. The cron will fall back to `'exchange'` for any row where `wallet_type` is NULL or empty.

---

*Written by Antigravity AI — 2026-07-30*
