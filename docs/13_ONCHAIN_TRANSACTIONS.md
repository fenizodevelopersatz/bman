# 13 — On-Chain Transactions (Admin Wallet Management)

Status: 🟢 **Implemented.** Admin ▸ Finance ▸ **On-Chain Transactions**
(`admin/wallet/onchain-transactions`): live wallet balances + a server-side,
filterable history of every blockchain-facing transaction, with a rich per-tx
detail modal (stored fields **+ live RPC enrichment**).

Links: [0_INDEX.md](0_INDEX.md) · [3_CHANGELOG.md](3_CHANGELOG.md) ·
[8_WALLET_DEPOSIT_WITHDRAW.md](8_WALLET_DEPOSIT_WITHDRAW.md) ·
[11_ADMIN_WALLET_MANAGEMENT.md](11_ADMIN_WALLET_MANAGEMENT.md).

---

## 1. What it shows

- **5 balance cards** — platform totals per wallet (`SUM(user_wallets.*)`): USDT ·
  Exchange · Earning · Staking · Bonus.
- **Transaction grid** — columns: tx hash (short, → explorer), date, wallet, from,
  to, type, amount, token, network, block, confirmations, gas fee, status badge,
  view. **Server-side** filtered / sorted / paginated.
- **Detail modal** — General · Wallet · Token · Gas · Execution · Ledger ·
  Failure Analysis · Partial Completion · Related · Audit · Actions.

Status badges are colour-coded: `confirmed` (green) · `pending` (yellow) ·
`processing` (blue) · `failed`/`reverted` (red) · `partial` (orange) ·
`cancelled` (grey).

---

## 2. Data model — `onchain_transactions`

One indexed table (`db/onchain_transactions.sql`) is the single queryable source,
designed for large volumes: indexes on `tx_hash`, `wallet_type`, `status`,
`network`, `tx_type`, `block_number`, `user_id`, `created_at`, `from_address`,
`to_address`, `token_symbol`, `(reference_type,reference_id)`, `gas_fee_total`,
`(status,created_at)`. Server-side pagination (`LIMIT/OFFSET`) + whitelisted
`ORDER BY` keep it fast; for tens of millions of rows switch the pager to keyset
(`WHERE id < :last ORDER BY id DESC`) — the indexes already support it.

**Populated from** the backfill (historical) + **live auto-capture** (new).

---

## 2a. Live auto-capture wiring (real-time)

Every balance movement is recorded automatically, in real time, via a single
**observer hook** rather than per-flow code. Because the platform's rule is
"every balance movement appends a `wallet_ledger` row", hooking
`Walletledger_model::post()` (immediately **after commit**, in a `try/catch`)
captures nearly everything with the user, wallet, credit/debit, `balance_after`,
`reference_type`, and `tx_hash` (when the caller supplies it). **A recording
failure can never roll back or affect the real movement.**

Flows that bypass the ledger are wired explicitly. Coverage:

| Source | How captured | On-chain tx hash |
|---|---|---|
| **Deposits** | ledger credit (`reference_type='deposit'`) → hook | ✅ (deposit carries tx_hash) |
| **Wallet transfers** | ledger `transfer()` → `post()` both legs → hook | n/a (internal) |
| **Bonus reductions** | ledger debit → hook, then `updateByLedgerId()` attaches the on-chain result | ✅ when broadcast (`sent`); `partial` on on-chain failure |
| **Swaps** | ledger credit (`swap`/`swap_bonus`) → hook | ⚠️ internal credit captured; the BMAN **delivery** hash (`Swapengine::deliverBman`) is not yet attached |
| **Withdrawals** | ledger debit (`reference_type='withdrawal'`) → hook | ⚠️ payout hash not stored (the `withdrawals` table has no `tx_hash` column) |
| **Admin adjustments** | new: ledger `admin_adjustment` → hook; legacy `history` grants → wired explicitly in `Walletmanagement` | n/a (internal) |
| **ROI / rank / matching** | ledger credit → hook | n/a (internal) |

### Immutable audit trail

Each transaction has an append-only history in **`onchain_tx_events`**
(`db/onchain_tx_events.sql`) — one row per lifecycle event (`created`,
`status_change`, `confirmation`, `credited`, `failed`, …) with actor, IP and
timestamp. The `onchain_transactions` row holds current state; the events table is
never updated/deleted (immutable). Recorder API on `Onchaintx_model` (all
fail-safe): `capture()`, `updateByLedgerId()`, `upsertByReference()`,
`logEvent()`, `events()`.

**Verified live:** an internal reduction produced a new `onchain_transactions` row
(from the hook) linked to its `wallet_ledger_id`, plus `created` + `status_change`
audit events — with no direct table writes in the reduction code.

### Known gaps (need a small addition)

- **Swap delivery hash** — attach `bman_tx_hash` from `Swapengine::deliverBman`
  via `updateByReference()` (one call).
- **Withdrawal payout hash** — add a `tx_hash` column to `withdrawals` and record
  it on approval, then capture as `withdrawal` with the hash.

## 3. Files

| File | Role |
|---|---|
| `db/onchain_transactions.sql` | Rich indexed table + idempotent backfill |
| `application/models/Onchaintx_model.php` | `walletTotals`, `filter`/`count` (all filters, sort, paginate), `get`, `enrichFromChain` (live RPC), `filterOptions`, `record` |
| `application/controllers/admin/wallet/Onchaintx.php` | `index` (page), `list` (AJAX grid), `detail` (AJAX + live enrich), `receipt` (download) |
| `application/views/admin/wallet/onchain_transactions.php` | Cards, filter bar, grid, detail modal, JS |
| `application/config/routes.php` + `admin_sidebar.php` | Routes + sidebar link |
| `application/models/Bonusreduction_model.php` | Now also writes an `onchain_transactions` row per reduction |

---

## 4. Filters (all server-side)

Wallet · Network · Status · Type · Token · Date range · Block number · Tx hash ·
Wallet address (from **or** to) · User ID · Reference ID · Gas-fee min/max · and a
free-text **search** across tx hash / addresses / user ID / block / reference.

---

## 5. The detail modal — stored vs live vs external

Opening a row calls `…/detail?id=` which returns the stored row **and** a live
enrichment fetched from the active Token Settings RPC
(`eth_getTransactionByHash` + `eth_getTransactionReceipt` + `eth_blockNumber`):

| Field group | Source |
|---|---|
| Hash, network, status, type, wallet, from/to, user, token, amount, ledger, reference, audit | **stored** row |
| Nonce, tx index, gas used, gas price, gas fee (BNB), block, confirmations, receipt status, input selector, method signature (transfer/approve/…), event-log count | **live RPC** (real, on the fly) |
| **Internal transactions, execution trace, decoded event/param names, full ABI logs** | **not available from a plain RPC** — needs a **BscScan API key** or a **debug/trace-capable archive node**; shown with a hint |

This was verified live: a real deposit tx returns actual from/to/nonce/block/gas
from BSC mainnet.

---

## 6. Actions in the modal

Copy Tx Hash · Copy From/To · View on Explorer (`explorer_url/tx/<hash>`) ·
View User · Download Receipt (`…/receipt/<id>`).

---

## 7. Access & security

Gated by `permission_pages['wallet_management']`; grid/detail are AJAX-only. The
RPC enrichment is read-only. No private keys are touched by this module.

---

## 8. Extending it

- **Deeper enrichment:** set a BscScan API key in Token Settings and extend
  `Onchaintx_model::enrichFromChain()` to call the explorer API for internal txs /
  decoded logs / trace.
- **More sources:** call `Onchaintx_model::record($data)` from the deposit
  listener, withdrawal approval, and swap engine so every on-chain action lands in
  one history (deposits are already backfilled; wire them live next).
- **Scale:** switch the pager to keyset pagination for very large tables.
