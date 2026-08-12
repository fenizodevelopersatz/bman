# 21 — BMAN Withdrawal Collection Cron (on-chain, dry-run by default)

Status: 🟡 **Implemented & verified against live data, still dry-run only.**
Automates the FIRST leg of a manual BMAN withdrawal — collecting the
member's BMAN on-chain into the treasury/hot wallet — ahead of admin
approval. The USDT payout leg is **unchanged**: a Super Admin still reveals
the treasury key and sends it manually.

Links: [19_WITHDRAW_IMPLEMENTATION.md](19_WITHDRAW_IMPLEMENTATION.md) ·
[18_WITHDRAW_REQUEST_AGENTS.md](18_WITHDRAW_REQUEST_AGENTS.md) ·
[17_BINARY_MATCHING_PAYOUT_CRON.md](17_BINARY_MATCHING_PAYOUT_CRON.md) (the
gas+token broadcast/verify pattern this cron mirrors) ·
[3_CHANGELOG.md](3_CHANGELOG.md) ·
[2026-08-12_bman_withdraw_collect_cron.md](2026-08-12_bman_withdraw_collect_cron.md)
(original build log — the exact bugs caught during verification, with before/after numbers) ·
[2026-08-12_bman_withdraw_status_simplify.md](2026-08-12_bman_withdraw_status_simplify.md)
(same-day follow-up — collapsed the 6-status/2-flow design below into the
4-step flow this doc now describes).

⚠️ **Before relying on this in production:** disabled AND dry-run by
default. Nothing broadcasts a real transaction until both flags are set on
the active `token_settings` row (see §3).

---

## 1. What's unchanged vs. what's new

**Unchanged:**
- `application/controllers/user/Bmanwithdraw.php` — the user-facing request
  form, USDT conversion math, daily/monthly limits, all validation.
- The USDT payout itself — Super Admin reveals the treasury private key
  (`reveal_treasury_key()`), sends it manually from an external wallet,
  pastes the `tx_hash` into "Complete".
- The legacy manual path (`pending → approved → processing → completed`,
  via `approve()`/`mark_processing()`) still exists untouched — a request
  can still be pushed through entirely by hand if the cron is off or
  misbehaves.

**New:**
- `BmanWithdrawCollectCron` — a two-leg (gas, collect) broadcast/verify
  state machine, structurally identical to `StakingPurchasecron`'s
  gas+token legs (same primitives: broadcast a hash, verify confirmations
  on a later pass, dry-run simulation with `DRYRUN-*` hashes, gas fee
  ledger recording).
- Admin approval now happens **after** the BMAN has already moved
  on-chain, not before — a request the cron collects sits in `pending`
  (awaiting the admin's approve/reject decision) once both legs confirm,
  same `pending → approved`/`rejected` shape the legacy flow always had,
  just reached via the cron instead of an admin's first click. See
  [2026-08-12_bman_withdraw_status_simplify.md](2026-08-12_bman_withdraw_status_simplify.md)
  for why this doesn't collide with the legacy flow's own use of those
  same status strings (`approved_at`/`tx_hash` presence are the
  discriminators, not separate status values).

## 2. The flow

```
user submits request                                    (unchanged)
        │  status = processing
        ▼
BmanWithdrawCollectCron claims it (status='processing' AND approved_at IS NULL)
        │
        ├─ gas leg:      treasury_wallet → user's custodial wallet (BNB)
        │
        └─ collect leg:  user's custodial wallet → treasury_wallet (BMAN,
                          signed with the user's own custodial private key)
        │                status stays 'processing' the whole time
        ▼  (both legs confirmed on-chain)
confirm_collected()                                       status → pending
  - converts the pending balance LOCK into a real DEBIT (bman_wallet_ledger)
  - records an onchain_transactions row (reference_type='bman_withdrawal',
    reference_id=<request id> — same reference the manual USDT-complete
    step already uses, so both legs show up together in Admin History)
        │
        ▼
admin reviews (admin/bman-withdrawals/view/<id>)
        │
        ├─ Approve → approve_and_complete(): sends USDT manually (unchanged),
        │             tx_hash required right there                → approved (terminal)
        │
        └─ Reject  → BMAN refunded (see §4)                        → rejected
```

## 3. Safety gates

Two dedicated columns on `token_settings`, deliberately **separate** from
`swap_enabled`/`swap_dry_run` (the flags `StakingPurchasecron` already
uses) — so enabling the already-tested staking-purchase flow never
silently arms this brand-new one too:

```sql
-- both default OFF — nothing broadcasts until you change these
UPDATE token_settings SET bman_withdraw_collect_enabled = 1 WHERE status = 1;   -- master switch
UPDATE token_settings SET bman_withdraw_collect_dry_run  = 0 WHERE status = 1;  -- 0 = real broadcasts
```

No admin settings-page toggle yet for these two — direct SQL only, until
this graduates out of dry-run testing.

## 4. Why claim_for_collection() needs the `approved_at IS NULL` guard, and why reject() doesn't credit

Two mistakes caught only by actually running this against the live DB, not
by reading the code — see
[2026-08-12_bman_withdraw_collect_cron.md](2026-08-12_bman_withdraw_collect_cron.md)
and [2026-08-12_bman_withdraw_status_simplify.md](2026-08-12_bman_withdraw_status_simplify.md)
for the full before/after numbers:

- **`processing` is also a legal legacy status** (the manual
  `approved → processing` step, via `mark_processing()`). The cron's claim
  query filters `status = 'processing' AND approved_at IS NULL` —
  `approved_at` is always set on a legacy row by the time it reaches
  `processing` (it can only get there via `approve()` first), but never set
  yet on a freshly-submitted, cron-owned request. Without that second
  condition the cron would also sweep up a request an admin had already
  started handling by hand — confirmed against a real legacy row (`#2`)
  that stayed correctly untouched only with the guard in place.
- **Reject-after-collection must NOT call `Walletledger_model::credit()`.**
  `user_wallets.exchange_balance` is a lifetime-cumulative figure a
  withdrawal never decrements directly — availability is computed as
  `total − active bman_wallet_ledger holds (locks + debits)`. Crediting
  `exchange_balance` on refund double-counts: the stale debit hold is still
  subtracting from availability *and* the raw total went up, netting out
  right in the `withdrawable` figure by coincidence while permanently
  inflating the raw balance. The fix: reject just **reverses whichever
  active hold row exists** (`lock` pre-collection, `debit` post-collection)
  — no ledger credit at all, mirroring how lock release already worked.

## 5. Running it

```
CLI  : php index.php BmanWithdrawCollectCron run
HTTP : /bman-withdraw-collect-cron?token=YOUR_CRON_TOKEN
Cron Lab : admin/wallet/cron-lab → "BMAN Withdrawal Collection (Auto-Collect, On-Chain)"
```

**Recommended schedule:** every minute, same cadence as `StakingPurchasecron`
(BSC ~3s blocks — one confirmation window per leg per tick).

**Recommended rollout:** run manually via Cron Lab a few times with
`bman_withdraw_collect_dry_run = 1` (the default) and watch a real request
move `processing → pending` in the admin list. Only then flip `dry_run = 0`.

## 6. What this does NOT do

- Does not send the USDT payout leg — stays manual, unchanged.
- Does not touch `Binarylevelmatching_model` or any matching/payout engine.
- Does not affect the legacy `pending → approved → processing → completed`
  manual path or its `approve()`/`mark_processing()`/`complete()`/
  `mark_failed()` methods — a request already in legacy `processing`
  (`approved_at` set) or legacy `approved` (no `tx_hash` yet) stays
  invisible to the cron's claim query and keeps working exactly as before.
