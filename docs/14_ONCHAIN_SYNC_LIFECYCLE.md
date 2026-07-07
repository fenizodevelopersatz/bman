# 14 — On-Chain Transaction Lifecycle + Efficient Balance Sync

Status: 🟢 **Implemented & tested (live BSC mainnet).** Completes the on-chain
transaction lifecycle for withdrawals and swaps, adds RPC-based verification with
confirmation follow-up and reorg handling, and a cost-optimized balance-sync
engine (free RPC primary, BscScan API only on a balance change).

Links: [13_ONCHAIN_TRANSACTIONS.md](13_ONCHAIN_TRANSACTIONS.md) ·
[8_WALLET_DEPOSIT_WITHDRAW.md](8_WALLET_DEPOSIT_WITHDRAW.md) ·
[0_INDEX.md](0_INDEX.md) · [3_CHANGELOG.md](3_CHANGELOG.md).

---

## 1. Withdrawal lifecycle

`withdrawals` gained the on-chain fields (`db/onchain_sync.sql`): `tx_hash`,
`chain_status` (`created→broadcasting→pending→confirmed→failed→reverted→cancelled`),
`block_number`, `confirmations`, `gas_used`, `gas_price`, `gas_fee_total`, `nonce`,
`explorer_url`, `failure_reason`, `onchain_tx_id`, `wallet_ledger_id`,
`broadcast_at`, `balance_debited`.

On admin **approve** with a pasted `tx_hash` (`Withdraw::update`): the hash is
stored, an `onchain_transactions` row (`tx_type='withdrawal'`) is created and
**verified against the chain immediately** (status/confirmations/gas), and both
are linked. On **reject**: `chain_status='cancelled'`; the refund is already
guarded once by `admin_txn_id`. **No double-deduct** — the user balance is
debited at request time; this flow never debits again.

**`tx_hash` is mandatory to approve** — added to the admin approve form
(`views/admin/withdraw/view.php`). It is validated server-side (`^0x[0-9a-fA-F]{64}$`)
and checked for **duplicates** across both `withdrawals` and
`onchain_transactions` before saving; approval is rejected if missing, malformed,
or already used.

## 2. Swap lifecycle

`Swapengine::deliverBman` now calls `Onchaintx_model::attachSwapDelivery($ref, …)`
after broadcast, attaching the **delivery** hash (and, when available, the
**request/deposit** hash) to the swap's on-chain rows — `delivery_tx_hash` and
`request_tx_hash` are stored **separately**, no duplicate rows. A `broadcast`
audit event is logged.

## 3. Verification & finality (`Chainsync_model::verifyTx`)

RPC `eth_getTransactionByHash` + `eth_getTransactionReceipt` + `eth_blockNumber`
→ status (`confirmed`/`reverted`/`processing`/`pending`), confirmations, gas used,
gas price, gas fee, nonce, tx index, block. Marks `finalized_at` at
`minimum_confirmations`. **Reorg handling:** if the stored block ≠ the chain
block, `reorg_count++` and a `reverted`/`status_change` event is logged, and the
DB is reconciled to chain truth. `Chainsynccron` re-verifies pending txs each run.

## 4. Efficient balance sync (`Chainsync_model::syncAddress`)

**Free RPC is primary; BscScan is a fallback used only on a balance change.**

```
RPC eth_getBalance (BNB) / balanceOf (BEP-20)   ← every sync (free)
        │ compare to wallet_balance_sync.last_balance_raw
   no diff ──► stop. NO explorer API call.        ← the cost win
   diff ─────► BscScan tokentx from last_block ──► import new transfers
               (dedupe on tx_hash+log_index) ──► update balance + cursor
```

- **Cursor cache:** `wallet_balance_sync` stores last balance, `last_block`,
  `last_tx_hash` per (address, token) → no duplicate scans.
- **Dedupe:** imports are keyed on `tx_hash + log_index` (`reference_id`).
- **Multi-RPC failover:** `token_settings.rpc_url` + 4 free public BSC endpoints,
  tried in order; failures logged and skipped.
- **Full audit:** every attempt / balance change / RPC failure / API call →
  `rpc_sync_log` (scope, endpoint, `api_used=rpc|bscscan`, ok, diff, balances,
  tx_imported, duration).

Run: `php index.php chainsynccron run` or `/chain-sync-cron?token=…` (drive daily
via [scheduler/README.md](../scheduler/README.md)).

## 4a. Scalable batch rotation (`Chainsync_model::syncBatch`)

For large user counts the cron processes addresses in **configurable batches**
and remembers where it stopped, via the singleton `wallet_sync_cursor`
(`db/wallet_sync_cursor.sql`): `last_user_id`, `batch_size` (default 200,
`?batch=` override), `cycle_count`, worker lock.

Each run:
1. **Priority** — addresses with an unsettled on-chain tx are synced/verified first.
2. **Claim** — `claimBatch()` atomically claims the next window with
   `SELECT … FOR UPDATE` on the cursor, so **concurrent workers get distinct
   windows** and never process the same address twice; within a run a seen-set
   dedupes priority vs window overlap.
3. **Resume** — the cursor **persists across runs and restarts**; the next run
   continues from `last_user_id`. When the end is reached it **wraps** and
   increments `cycle_count` (a new full pass).
4. **Metrics** — processed / skipped / balance-changed / tx-imported /
   bscscan-calls / rpc-only / rpc-failures / duration → `rpc_sync_log`
   (`scope='batch'`).

Idempotent (diff-gate + dedupe), duplicate-safe (atomic cursor + `seen`),
resumable (persistent cursor), multi-worker (per-window claim).

## 5. Immutable audit trail

`onchain_tx_events` (append-only) now also records the **RPC endpoint** that
produced each change, and the engine emits `broadcast` / `status_change` /
`reverted` / `confirmation` events with actor, IP, endpoint, hash, block, gas.
Event vocabulary: created · signed · broadcast · pending · confirmed · failed ·
reverted · cancelled · retried.

## 6. Test results (live BSC mainnet)

`php index.php chainsynctest run` — **10/10 passing:**

| Test | Result |
|---|---|
| Duplicate protection | ✅ same tx captured twice → 1 row |
| Verify real tx via RPC | ✅ status=confirmed, 734k confirmations |
| Chain reorg detection | ✅ corrupted block → reorg flagged + corrected |
| Multi-RPC failover | ✅ 5 endpoints, live head block |
| Balance reconciliation | ✅ corrupted balance → diff detected + corrected |
| RPC-first diff-gating | ✅ no-change re-sync stayed RPC-only (0 API cost) |
| Batch rotation advances | ✅ distinct non-overlapping windows |
| Restart recovery | ✅ resumes from the persisted cursor |
| Rotation wraps to new cycle | ✅ cycle_count increments on exhaustion |
| Withdrawal tx_hash validation + dedupe | ✅ format enforced + duplicate blocked |

Live cron run: 12 addresses synced, **43 real transfers imported via BscScan**,
but only **3 BscScan calls** (RPC-only for the rest); a second run made **0**
BscScan calls (nothing changed) — the cost model in action.

**Simulated / not run for real:** successful & failed **withdrawal** and **swap**
*broadcasts* move real mainnet funds, so they are exercised via their dry-run
paths (the recording/lifecycle logic is what's tested here). "Gas estimation
failure" is handled as an RPC/verify error path.

## 7. Files

**New:** `db/onchain_sync.sql`, `application/models/Chainsync_model.php`,
`application/controllers/Chainsynccron.php`,
`application/controllers/Chainsynctest.php`.
**Touched:** `Onchaintx_model.php` (`attachSwapDelivery`),
`staking/Swapengine_model.php` (delivery link), `admin/withdraw/Withdraw.php`
(payout lifecycle), `config/routes.php` (`chain-sync-cron`).

## 8. Known follow-ups

- BNB native-transfer history import (currently BNB tracks balance only; BEP-20
  tokens import full history via BscScan tokentx).
- A worker-lease TTL if you run *many* concurrent workers (the current atomic
  per-window claim already prevents double-processing; a lease adds visibility).
- Real money-moving withdrawal/swap broadcasts run via their dry-run paths; wire
  a testnet config to exercise real broadcasts end-to-end.
