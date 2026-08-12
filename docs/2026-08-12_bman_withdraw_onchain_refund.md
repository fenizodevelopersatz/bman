# 2026-08-12 — BmanWithdrawCollectCron: real on-chain refund on Reject

Follow-up to the same day's
[2026-08-12_bman_withdraw_status_simplify.md](2026-08-12_bman_withdraw_status_simplify.md).

## The gap

`reject()` only ever reversed the internal `bman_wallet_ledger` row. For a
request rejected **before** collection that's the whole story — the BMAN
never left the user's wallet, so releasing the lock is correct and
complete. But for a request rejected **after** collection
(`collect_cron_status=1`), the real BMAN had already been sent on-chain to
the **treasury wallet** by the collect leg. Reversing the ledger debit made
the platform's internal balance say the user has the BMAN again — but the
actual tokens were still sitting in treasury, never sent back. A user's
*next* withdrawal would then have its collect leg try to pull
`request_amount` BMAN from a wallet that's now short by exactly that much
(it already left in the rejected request), and fail on-chain.

The log message (`"[BMAN refunded to ... wallet]"`) and Cron Lab's
description (`"Reject refunds the BMAN"`) both overstated what actually
happened — a ledger credit, not a real refund.

## The fix

`Bmanwithdraw_model::refund_bman_onchain($request_id)` — new. Sends BMAN
back **treasury → user's custodial wallet**, synchronously, as part of the
Reject click (not a separate cron — broadcast happens in the same HTTP
request, before any DB write, since a broadcast can't be rolled back).

Mirrors whether the **original collection** was real or simulated — not
the *current* `token_settings` dry-run flag, which may have changed since
then. `BmanWithdrawCollectCron` never calls `sendToken()` in dry-run (see
its `_broadcast()`): a `DRYRUN-` `collect_tx_hash` means no real BMAN ever
left the user's wallet in the first place, so the refund simulates too
(`DRYRUN-refund-*` hash) — nothing real to reverse. A real `collect_tx_hash`
gets a real `sendToken()` call, signed with the treasury key (same
`Tokenmaster_model::treasuryPrivateKey()` the collect/gas legs already use
— no separate password reveal, same trust model as the rest of this
automated flow).

Records an `onchain_transactions` row (`tx_type='withdrawal_refund'`,
`reference_type='bman_withdrawal'`, same `reference_id` the collect/payout
legs use — shows up alongside them in Admin History) and a
`gas_fee_ledger` row (live sends only) via the same primitives the
collect/gas legs already use.

`reject()` now takes a `$refund_tx_hash` param: required when
`collect_cron_status=1` (returns an error otherwise — the controller must
call `refund_bman_onchain()` first), stored alongside `refunded_at`. The
admin controller (`Bmanwithdraw.php::update()`, `rejected` branch) calls
`refund_bman_onchain()` before `reject()`; if the refund fails, the whole
DB transaction rolls back and nothing changes — safe to retry, since
nothing was sent on a failed broadcast.

Idempotent: `refund_bman_onchain()` returns the existing `refund_tx_hash`
immediately if already set, rather than sending twice on a retried click.

## Verified

Simulated the full `refund_bman_onchain()` + `reject()` call chain against
the live DB in a rolled-back transaction (request `#1`, dry-run collection):
correctly detected `wasDryRunCollection=true` from its `DRYRUN-` prefixed
`collect_tx_hash`, resolved real treasury/user addresses for the audit
trail, inserted the `onchain_transactions` row, reversed the active debit,
set `status='rejected'`, `refund_tx_hash`, `refunded_at`. Rolled back —
confirmed no trace left in either table afterward.

## Files changed

- `db/2026-08-12_bman_withdraw_onchain_refund.sql` — new `refund_tx_hash`
  column on `bman_withdraw_requests`.
- `application/models/withdraw/Bmanwithdraw_model.php` —
  `refund_bman_onchain()` (new); `reject()` takes `$refund_tx_hash`,
  requires it when `collect_cron_status=1`, stores it.
- `application/controllers/admin/withdraw/Bmanwithdraw.php` — `update()`'s
  `rejected` branch calls `refund_bman_onchain()` first when the request
  was collected.
- `application/views/admin/withdraw/bman_view.php` — shows the refund
  tx_hash next to "BMAN Refunded At" when present.
- `application/controllers/admin/wallet/Cronlab.php` — description text
  updated (was still describing the retired `collecting`/`awaiting_approval`
  statuses from before the same-day status-simplify change).
