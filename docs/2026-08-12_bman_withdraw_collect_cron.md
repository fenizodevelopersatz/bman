# 2026-08-12 — BmanWithdrawCollectCron: automated on-chain BMAN collection

New cron that automates the FIRST leg of a BMAN withdrawal — collecting the
member's BMAN on-chain into the treasury/hot wallet — ahead of admin
approval. The USDT payout leg is untouched: a Super Admin still reveals the
treasury key and sends it manually, exactly as before.

Ships **disabled and in dry-run**. Nothing here can broadcast a real
transaction until an admin explicitly sets, on the active `token_settings`
row: `bman_withdraw_collect_enabled = 1` **and**
`bman_withdraw_collect_dry_run = 0`. No admin UI toggle for these two flags
yet — set them directly:
```sql
UPDATE token_settings SET bman_withdraw_collect_enabled = 1, bman_withdraw_collect_dry_run = 1 WHERE status = 1;
-- watch a few dry-run passes first, THEN:
UPDATE token_settings SET bman_withdraw_collect_dry_run = 0 WHERE status = 1;
```

## The corrected flow

The request that kicked this off named the flow backwards a couple of times
before landing here — this is the flow actually implemented, confirmed
against the live schema and the existing manual process:

```
1. User requests a withdrawal (BMAN amount, converted to a USDT value at
   today's rate) — unchanged, application/controllers/user/Bmanwithdraw.php.

2. BmanWithdrawCollectCron picks it up automatically:
     gas leg:     treasury_wallet -> user's custodial wallet   (BNB, funds gas)
     collect leg: user's custodial wallet -> treasury_wallet   (BMAN, the withdrawal amount)
   Two-leg broadcast/verify state machine, faithfully mirroring
   StakingPurchasecron's proven gas+token pattern (same primitives:
   broadcast, verify confirmations, dry-run simulation, gas fee ledger).
   Status visibly moves pending -> collecting while this runs.

3. Once the collect leg confirms on-chain, the request moves to
   'awaiting_approval' — a brand-new status, and the pending lock on the
   user's balance is converted into a real debit right then (see "Ledger
   mechanics" below) — the BMAN has genuinely left the platform's custody
   at this point, regardless of what an admin later decides.

4. Admin reviews it:
     Approve  -> sends the USDT manually (unchanged — reveal treasury key,
                 send from an external wallet, paste tx_hash) -> 'completed'.
     Reject   -> the BMAN is credited back -> 'rejected'.
```

## Why 'collecting' is a NEW status, not a reuse of 'processing'

`bman_withdraw_requests.status` already had a `processing` value — the
legacy admin-manual step (`approved -> processing`, via the existing
`mark_processing()` click). Reusing it for the cron's own in-flight state
would have made the cron's claim query
(`status IN ('pending', 'collecting')`) **also sweep up any legacy
'processing' row an admin had already started handling by hand**. This
wasn't hypothetical — a real request (`id=2`) was sitting in `processing`
from before this change shipped. Confirmed the fix holds: after every test
run below, request `#2` remained completely untouched (`gas_cron_status=0`,
no tx hashes) throughout.

## Ledger mechanics — the bug this caught before it shipped

`WalletMaturity_model::withdrawable()` computes:
```
total (raw user_wallets.exchange_balance — lifetime-cumulative, a
       withdrawal never touches it directly)
  minus
holds (SUM of bman_wallet_ledger rows, entry_type IN ('lock','debit'),
       status='active' — locks are temporary, debits are PERMANENT)
```
So "removing" BMAN for a withdrawal — whether still just locked, or already
collected-and-debited — always means placing/keeping an active hold row in
`bman_wallet_ledger`. The raw `exchange_balance` column never moves.

First implementation of the reject-refund path got this wrong: it credited
`exchange_balance` directly via `Walletledger_model::credit()` for a
request rejected after collection. Caught by actually running it against a
real request (`id=1`, user `#3`) rather than trusting the design on paper:

| | raw exchange_balance | withdrawable |
|---|--:|--:|
| Before reject | 5.23 | 1.28 |
| After reject (broken version) | **9.18** ← inflated | 5.23 (right, by coincidence) |
| After reject (fixed version) | 5.23 (unchanged, correct) | 5.23 (correct) |

The broken version's `withdrawable` figure came out right only because the
stale active debit hold (never reversed) happened to cancel out the
erroneous credit in the subtraction — `exchange_balance` itself was left
permanently, visibly inflated by the refunded amount. The fix: reject just
**reverses whichever active `bman_wallet_ledger` row exists** for the
request — a `lock` if rejected before collection, a `debit` if rejected
after — exactly mirroring how lock-release already worked pre-collection.
No `Walletledger_model` call at all. Verified again after the fix: raw
balance stays untouched, `withdrawable` correctly restores.

`complete()` (admin approve) needed a smaller change: accept
`awaiting_approval` as a valid prior status in addition to the legacy
`processing`, and since `confirm_collected()` already converted the lock to
a debit at collection time, its existing lock→debit conversion loop
correctly finds nothing to do — verified no double-debit occurs (exactly 1
debit row per request after completing).

## Admin History / gas fee visibility

The collect leg writes an `onchain_transactions` row with
`reference_type='bman_withdrawal'`, `reference_id=<request id>` — the same
reference the admin's manual USDT-complete step already writes. A settled
request now shows **both** legs side by side under one reference_id.
`wallet_type` is a fixed ENUM (`usdt,exchange,earning,staking,bonus,gas,treasury`
— no `bman` member); an invalid value there silently stores as `''` rather
than erroring (confirmed against a live insert), so the collect leg uses
the request's own `source_wallet` (currently always `exchange`) instead of
a literal. Both legs also write to `gas_fee_ledger` via the existing
`GasFeeLedger_model`, so they appear in the Gas Fee Transactions admin page
too.

## Verified against the live DB (all reset afterward, nothing left behind)

- Dry-run cron pass: request `#1` (pending, 3.95 BMAN) → both legs
  confirmed with `DRYRUN-*` hashes → `awaiting_approval`; lock correctly
  converted to a debit; two `onchain_transactions` rows under
  `reference_id='1'`; two `gas_fee_ledger` rows.
- Legacy `processing` request `#2`: untouched across every test pass.
- Reject after collection: raw balance unchanged, withdrawable restored by
  exactly the request amount (see table above).
- Approve/complete after collection: no double-debit (exactly 1 debit row
  total), status → `completed` with the manually-supplied tx_hash.

## What this intentionally does NOT do

- Does not send the USDT payout — that stays a manual, Super-Admin,
  reveal-the-treasury-key action, unchanged.
- Does not touch `Binarylevelmatching_model` or any matching/payout engine.
- No new admin UI for the two `token_settings` enable/dry-run flags yet —
  direct SQL only (see top of this doc). Worth a small settings-page
  addition if this graduates out of dry-run testing.

## Files changed

- `db/2026-08-12_bman_withdraw_collect_cron.sql` — new `status` enum values
  (`collecting`, `awaiting_approval`), cron tracking columns on
  `bman_withdraw_requests`, two new `token_settings` gate columns.
- `application/controllers/BmanWithdrawCollectCron.php` — new, the cron.
- `application/models/withdraw/Bmanwithdraw_model.php` — new cron-support
  methods (`claim_for_collection`, `begin_collection`, `set_cron_fields`,
  `confirm_collected`); `complete()` and `reject()` updated for the new
  status and ledger mechanics described above.
- `application/config/routes.php` — `bman-withdraw-collect-cron` route,
  token-gated like every other cron here.
- `application/views/admin/withdraw/bman_view.php` /
  `bman_list.php` — render the two new statuses, show the collection
  tx hashes/gas fee info, Approve/Reject actions for `awaiting_approval`.

Deployed to the main checkout, left uncommitted for review. Run it manually
once to watch a dry-run pass before ever flipping `bman_withdraw_collect_dry_run`
off:
```bash
php index.php BmanWithdrawCollectCron run
```
