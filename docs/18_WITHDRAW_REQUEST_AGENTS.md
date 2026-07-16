# AGENTS.md — BMAN Withdraw Request Handling

> Save this file as `AGENTS.md` (Codex) **and/or** `CLAUDE.md` (Claude Code) in
> the project root: `D:\SATZ\SATZ\php\Bman\admlm_responsive_26_02_26\`.
> It is the single source of truth for how withdraw requests and admin approval
> must behave. Do not change this behaviour without updating this file.

---

## 1. What the system does (read this first)

BMAN is the platform token. A user withdraws BMAN and receives **USDT** at an
external address. The user has **4 wallets**: `exchange`, `earning`, `staking`,
`bonus`. The Payouts screen shows ONE cumulative "Available BMAN Balance" =
the sum of the 4 wallets' available balances.

**Golden rule:** balance is NEVER stored as a single mutable number. It is
DERIVED from an append-only ledger (`bman_wallet_ledger`). Every credit, debit,
and lock is one immutable row. You compute balance; you never "UPDATE balance = balance - X".

```
available(wallet) = SUM(matured credit) - SUM(debit) - SUM(active lock)
available(user)   = SUM of available over the 4 wallets
```

- **matured credit** = a credit row whose `maturity_date` is NULL or already passed (`<= NOW()`).
  Immature staking BMAN is NOT withdrawable and must not be locked.
- **active lock** = BMAN reserved by a pending/in-flight withdrawal. Held, not gone.

---

## 2. Data model (do not rename columns without updating this file)

**`bman_withdraw_requests`** — one row per request.
`id, request_no, user_id, source_wallet('exchange'|'earning'|'staking'|'bonus'|'mixed'),
request_amount, fee_amount, net_amount, bman_usdt_rate, usdt_amount,
withdraw_address, remark, tx_hash, admin_remark,
status, approved_by, approved_at, completed_at, created_at`

- `net_amount = request_amount - fee_amount` (in BMAN)
- `usdt_amount = net_amount * bman_usdt_rate` (what the user actually receives)
- `source_wallet = 'mixed'` means allocate across all 4 wallets by priority.

**`bman_wallet_ledger`** — source of truth.
`id, user_id, wallet, entry_type('credit'|'debit'|'lock'), ref_type, ref_id,
amount(+ve), maturity_date, status('active'|'reversed'), remark, created_at`

**`bman_withdraw_allocations`** — which wallet contributed how much to a request.
`id, request_id, wallet, amount, created_at`

**Views:** `v_bman_wallet_balances` (per wallet), `v_bman_user_available` (cumulative).
The UI must read `available` / `locked` from these views — never sum raw columns itself.

---

## 3. Request lifecycle — the status list

The `status` enum and the ONLY legal transitions:

| status       | meaning                                   | lock state           | who sets it |
|--------------|-------------------------------------------|----------------------|-------------|
| `pending`    | user submitted; awaiting admin review     | **locked (active)**  | system on create |
| `approved`   | admin accepted; not yet paid              | **locked (active)**  | admin |
| `processing` | payout being sent on-chain                | **locked (active)**  | admin/system |
| `completed`  | USDT sent, `tx_hash` recorded             | lock → **debit**     | admin/system |
| `rejected`   | admin refused                             | lock → **released**  | admin |
| `failed`     | payout attempted but errored              | lock → **released**  | system |

**Legal transitions (enforce these; reject anything else):**
```
pending    -> approved | rejected
approved   -> processing | rejected
processing -> completed | failed
```
Terminal states: `completed`, `rejected`, `failed` — never edit a terminal request.
A request may hold `pending/approved/processing` for a long time; the BMAN stays
locked the entire time.

---

## 4. The instant-lock rule (never bypass)

The moment a request row is inserted, the requested BMAN is locked **in the same
transaction** — enforced by DB triggers so it cannot be skipped:

1. `BEFORE INSERT` — validate: amount > 0, address present & different from a
   platform custodial address, and `available >= request_amount` against the
   correct pool (whole user if `mixed`, else that one wallet). If short → SIGNAL error, row never inserts.
2. `AFTER INSERT` — create the `lock` ledger row(s):
   - `mixed` → `sp_bman_lock_allocate` splits the amount across wallets in
     PRIORITY order `bonus → earning → exchange → staking`, one lock + one
     allocation row per wallet slice.
   - single wallet → one lock row for the full amount.
3. `AFTER UPDATE` on status change:
   - `completed` → each active lock slice becomes a permanent `debit`.
   - `rejected`/`failed` → each active lock is set `reversed` (funds return).

**Agent rules:**
- Never write application code that adjusts balance directly. Insert ledger rows only.
- Never delete a request to "cancel" it — transition to `rejected` so the lock releases cleanly.
- Never lock immature (future `maturity_date`) BMAN.
- Always go through `sp_bman_withdraw_create(...)` (per-user `GET_LOCK` + transaction)
  so two concurrent requests can't both pass the balance check.

---

## 5. Admin MANUAL approval flow (step by step)

Admin acts from the withdraw-requests admin list. Each action is one status
transition; the ledger side-effects happen automatically via triggers.

**A. View queue** — list `WHERE status='pending'` newest first, showing
`request_no, user, request_amount BMAN, fee, net, usdt_amount, source_wallet,
withdraw_address, created_at`, plus the per-wallet split from
`bman_withdraw_allocations`.

**B. Approve** (funds stay locked, not yet paid):
```sql
UPDATE bman_withdraw_requests
   SET status='approved', approved_by=:admin_id, approved_at=NOW(),
       admin_remark=:note
 WHERE id=:id AND status='pending';
```

**C. Mark processing → send USDT on-chain → complete:**
```sql
UPDATE bman_withdraw_requests SET status='processing' WHERE id=:id AND status='approved';
-- ... send payout, obtain :tx_hash ...
UPDATE bman_withdraw_requests
   SET status='completed', tx_hash=:tx_hash, completed_at=NOW()
 WHERE id=:id AND status='processing';
-- trigger converts the locked BMAN into a real debit
```

**D. Reject** (release the hold back to the user):
```sql
UPDATE bman_withdraw_requests
   SET status='rejected', approved_by=:admin_id, approved_at=NOW(),
       admin_remark=:reason
 WHERE id=:id AND status IN ('pending','approved');
-- trigger reverses the lock; cumulative available goes back up
```

**Admin rules:**
- Always guard the UPDATE with `AND status = <expected>` so a stale page can't
  double-process a request.
- Never approve without a recorded `approved_by`.
- `completed` requires a non-null `tx_hash`.
- Rejection requires an `admin_remark` (reason), shown to the user.
- Approval must NOT re-check or re-deduct balance — the BMAN was already locked at request time.

---

## 6. Validation checklist for any withdraw code you touch

- [ ] Amount > 0 and >= configured minimum (Min BMAN / Minimum Withdrawal USDT).
- [ ] `withdraw_address` present, valid format, and NOT a platform custodial address.
- [ ] Balance check uses matured + unlocked funds only, against the right pool.
- [ ] Request creation and lock happen in ONE transaction (`sp_bman_withdraw_create`).
- [ ] KYC verified + user eligible before allowing submission.
- [ ] Status transitions restricted to the legal set in §3; guarded by `AND status=...`.
- [ ] No direct balance mutation anywhere — ledger rows only.
- [ ] Money math uses DECIMAL(18,8); never floats.
- [ ] On any error mid-flow, ROLLBACK so no orphan lock is left behind.

## 7. Common mistakes to avoid
- Summing wallet columns in PHP instead of reading the views (double-counts locks).
- Releasing a lock twice (reject then reject) — guard with `status='active'` on the lock update.
- Approving a `completed`/`rejected` request — blocked by the `AND status=...` guard.
- Locking `staking` BMAN that hasn't matured — excluded by the maturity filter, keep it that way.
```
