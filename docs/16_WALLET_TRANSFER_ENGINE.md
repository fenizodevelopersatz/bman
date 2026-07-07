# 16 — Centralized Wallet Transfer Engine

Status: 🟢 **Implemented & tested.** ONE validation + execution service
(`application/models/wallet/Wallettransferservice_model.php`) used by **both**
the User Panel (`user/transfer_wallet`) and Admin Panel
(`admin/finance/internal-transfers`). No duplicated business logic — both
controllers call `execute()`.

Links: [9_INTERNAL_WALLET_TRANSFER.md](9_INTERNAL_WALLET_TRANSFER.md) ·
[8_WALLET_DEPOSIT_WITHDRAW.md](8_WALLET_DEPOSIT_WITHDRAW.md) ·
[0_INDEX.md](0_INDEX.md) · [3_CHANGELOG.md](3_CHANGELOG.md).

---

## 1. Rules (enforced identically for both panels)

Currency: **BMAN only**. Wallets: `exchange · earning · staking · bonus` (USDT excluded).

**Member transfers** (to another user, same wallet on the recipient):
| Source wallet | Allowed recipient |
|---|---|
| exchange / earning / staking | any member in the **source's downline** |
| **bonus** | **only the source's direct sponsor** |

**Internal transfers** (source user's own wallets):
| From | To |
|---|---|
| **exchange** | bonus · earning · staking |

Exchange is **source-only** (never receives). No reverse, no other pairs.

**Admin** acts on behalf of a chosen source user and follows the **exact same
rules** — `via=admin` skips only the User-Panel **KYC + transfer-password** gates,
never the wallet / downline / sponsor / balance rules.

## 2. Validation (`validate($ctx)` → `[ok, code, message, ctx]`)

Amount > 0, ≤ 8 dp · source user exists + active · from-wallet valid (BMAN) ·
(user panel) KYC approved + transfer password (`password_verify` + legacy md5) ·
internal direction allowed / Exchange-source-only · member: recipient exists +
active + not self + **downline** (walk sponsor chain) or **direct-sponsor** for
bonus · sufficient balance (both panels). First failing rule returns immediately
with a machine `code`.

## 3. Execution (`execute($ctx)`)

Validate → **idempotency** (a completed transfer with the same `idempotency_key`
returns its ref, no re-charge) → `trans_begin` → `Walletledger::debit(source)`
(row-locked `SELECT … FOR UPDATE`, re-verifies balance) → `credit(destination)` →
insert `wallet_internal_transfer` (double-entry ledger ids, before/after balances,
via, created_by) → audit → commit. **Any failure rolls back everything.**

Double-entry, ACID, row-locked, retry-safe, immutable audit.

## 4. Data

- `wallet_internal_transfer` (existing) + new columns (`db/wallet_transfer_service.sql`):
  `idempotency_key` (unique), `created_by`, `failure_reason`, and blockchain
  columns (`tx_hash`, `block_number`, `confirmations`, `gas_used`, `gas_fee`,
  `network`) for the history/detail spec (internal moves leave them null).
- `wallet_transfer_audit` — append-only audit (action, mode, via, actor, source,
  recipient, wallets, amount, result code, IP, UA, request id).
- Detail view: `service->detail($ref)` returns header + the two `wallet_ledger`
  rows (debit/credit) + audit trail.

## 5. Both panels call the one service

- **User** `user/Transfer_wallet::do_transfer` → `execute([..., via=>'user', require_kyc=>true, transfer_password=>…])`.
- **Admin** `admin/wallet/Internaltransfers::do_transfer` → `execute([..., via=>'admin', actor_id=>adminId, source_user_id=>selected user])`.

The old `Wallettransfer_model` paths are no longer the source of truth for these
two entry points.

## 6. Files

**New:** `db/wallet_transfer_service.sql`,
`application/models/wallet/Wallettransferservice_model.php`,
`application/controllers/Wallettransfertest.php` (CLI tests).
**Touched:** `controllers/user/Transfer_wallet.php`,
`controllers/admin/wallet/Internaltransfers.php`.

## 7. Tests (`php index.php wallettransfertest run|exec`)

- **18/18 rule tests** against real relationships (source 247 · sponsor 1 ·
  downline 248 · unrelated 250): internal Exchange-source-only + allowed pairs,
  member downline rules, Bonus→sponsor-only, self / amount / precision / USDT.
- **3/3 execution tests**: real transfer moved balances (−2 exchange / +2 bonus),
  **idempotent** re-run returned the same ref with no double debit, balances restored.

## 8. Follow-up (UI)

Backend rules are enforced defensively regardless of UI. Still to do for full UX:
disable invalid wallet/recipient combinations in the User + Admin dropdowns,
confirmation dialog, and a shared transfer-detail modal (the `detail()` data is
ready). The engine already rejects any invalid combo submitted directly.
