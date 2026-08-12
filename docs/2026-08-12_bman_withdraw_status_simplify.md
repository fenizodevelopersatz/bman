# 2026-08-12 — BmanWithdrawCollectCron: simplify the cron-flow status names

Follow-up to the same day's
[2026-08-12_bman_withdraw_collect_cron.md](2026-08-12_bman_withdraw_collect_cron.md).
That build introduced two brand-new statuses (`collecting`, `awaiting_approval`)
to keep the cron's own in-flight state from colliding with the pre-existing
legacy `processing`/`approved` meanings. Renamed per request to read as a
straight 4-step line instead of 6 statuses across two flows:

```
Old:  pending -> collecting -> awaiting_approval -> completed / rejected
New:  processing -> (cron executed) -> pending -> approved / rejected
```

## Why this didn't just reuse the plain strings — the collision that mattered

`processing` and (non-terminal) `approved` are **still live legacy-flow
values** — reachable via the existing `mark_processing()` /
`approve()`/`complete()` click-path if the cron is ever off. Renaming the
cron's initial state to `processing` reintroduces exactly the bug the
previous build's `collecting` status was invented to avoid (see that doc's
§"Why 'collecting' is a NEW status, not a reuse of 'processing'" — a real
legacy `processing` row, `#2`, was the regression test).

The fix this time: **`approved_at` as the discriminator**, not a separate
status string.
- Every legacy `processing` row got there via `approve()` first, which
  always sets `approved_at`.
- A fresh cron-owned request is never `approve()`'d before the cron claims
  it, so `approved_at IS NULL` at that point.

So `claim_for_collection()` now filters `status = 'processing' AND
approved_at IS NULL` — cron-owned rows only, legacy `processing` rows
(admin already handling it by hand) still invisible to the cron, same
guarantee as before, no new status value needed.

The same ambiguity exists on the other end: `approved` is now **dual
meaning** —
- **Terminal / paid** under the cron flow: reached via the new
  `approve_and_complete()` (§ below), always with `tx_hash` + `completed_at`
  set in the same update.
- **Still in-flight** under the legacy flow: reached via the old `approve()`
  (`pending → approved`, no `tx_hash`), still awaiting `mark_processing()` →
  `complete()`.

`tx_hash IS NOT NULL` is the discriminator everywhere this distinction
matters (badge color, dropdown options, `user_totals()`'s paid/pending
split, `open_request()`'s block-new-request check) — see "Files changed"
below for the exact list.

## The corrected flow

```
1. User requests a withdrawal — unchanged.
        status = 'processing'

2. BmanWithdrawCollectCron claims it (status='processing' AND approved_at
   IS NULL) and runs the same two-leg gas+collect state machine as before.
   Status stays 'processing' throughout — no visible intermediate state.

3. Once both legs confirm, confirm_collected() converts the lock into a
   real debit (unchanged mechanic) and moves the request to 'pending'.

4. Admin reviews it (admin/bman-withdrawals/view/<id>):
     Approve -> approve_and_complete(): requires tx_hash right there, sets
                status='approved' (terminal), tx_hash, completed_at, in ONE
                step (previously two: awaiting_approval -> completed).
     Reject  -> reject(): unchanged mechanic, now keyed off
                collect_cron_status=1 instead of a status string to decide
                lock-release vs debit-refund (see reject()'s docblock).
```

## Live-data migration

Checked before touching anything (`db/2026-08-12_bman_withdraw_status_simplify.sql`):
zero rows existed in `approved` (non-terminal), `processing` (legacy),
`collecting`, or `awaiting_approval` — only 2 unclaimed `pending` rows
(`#1`, `#3`, both `gas_cron_status=0 AND collect_cron_status=0`, i.e. never
touched by the cron) and 1 unrelated `failed` row. Migrated the 2 unclaimed
rows to `processing` so `claim_for_collection()`'s new query doesn't orphan
them; nothing else needed backfilling. Column default updated to
`processing` to match `create_request()`.

**Verified against the live DB** (dry-run cron pass, then `approve_and_complete()`
/`reject()` exercised in a rolled-back transaction, nothing left behind):
- Cron claimed both `processing` rows, ran gas+collect, both landed on
  `pending` with locks correctly converted to debits (exactly 1 debit row
  each, matching the original build's verified behavior).
- Re-running the cron immediately after: `total_requests: 0` — confirms
  `pending` rows are no longer re-claimed (the `approved_at`/`processing`
  filter is doing its job, not just the `gas_cron_status`/`collect_cron_status`
  gate).
- `approve_and_complete()` on request `#1`: `pending → approved`, `tx_hash`/
  `approved_at`/`completed_at` set in one call.
- `reject()` on request `#3`: `collect_cron_status=1` correctly read as
  "already collected" → reversed the active **debit** row (not the
  already-reversed lock), `refunded_at` set.

## Files changed

- `db/2026-08-12_bman_withdraw_status_simplify.sql` — status column default
  `pending → processing`; migrates the 2 live unclaimed rows.
- `application/models/withdraw/Bmanwithdraw_model.php`:
  - `create_request()` — initial status `pending → processing`.
  - `claim_for_collection()` — `status IN ('pending','collecting')` →
    `status = 'processing' AND approved_at IS NULL`.
  - `begin_collection()` — removed (no separate `collecting` status to
    transition into anymore).
  - `confirm_collected()` — target status `awaiting_approval → pending`.
  - `approve_and_complete()` — new; `pending → approved` + tx_hash +
    completed_at in one step (was `awaiting_approval → completed` via the
    old `complete()`).
  - `reject()` — `wasCollected` now reads `collect_cron_status` instead of
    comparing against the retired `awaiting_approval` string; allowed-from
    list `['pending','approved']` (dropped `awaiting_approval`).
  - `complete()` — dropped the retired `awaiting_approval` from its
    allowed-from list; legacy `processing → completed` path unchanged.
  - `user_totals()`, `open_request()` — `approved` only counts as
    paid/closed when `tx_hash` is set; otherwise still bucketed as
    pending/open (legacy in-flight meaning).
- `application/controllers/BmanWithdrawCollectCron.php` — removed the
  `begin_collection()` call; status simply stays `processing` for the
  cron's entire in-flight duration.
- `application/controllers/admin/withdraw/Bmanwithdraw.php` — `update()`'s
  `approved` target now requires `tx_hash` and calls
  `approve_and_complete()` (previously the no-tx_hash legacy `approve()`).
- `application/controllers/user/Bmanwithdraw.php` — `log_action()` on
  request creation now logs `processing` instead of the no-longer-accurate
  `pending`.
- `application/views/admin/withdraw/bman_view.php` /
  `bman_list.php` — dropdown options, status badge colors, and the status
  filter re-derived for `processing`/`pending`/`approved`(+tx_hash), legacy
  `processing`(+approved_at)/`approved`(no tx_hash) branches kept for any
  historical row.

`collecting` and `awaiting_approval` remain in the `status` ENUM (harmless,
unused) — no data ever needs to be migrated out of them since nothing was
sitting in either at the time of this change.
