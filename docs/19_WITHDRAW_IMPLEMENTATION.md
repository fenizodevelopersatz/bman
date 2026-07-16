# Withdraw Locking Workflow Implementation Guide

This document describes the implementation of the BMAN withdrawal request locking system based on `18_WITHDRAW_REQUEST_AGENTS.md`.

---

## Quick Start

### 1. Run the Migration

Execute the database migration to create required tables:

```bash
php db/migrate_withdraw_requests.php
```

This creates/updates:
- `bman_withdraw_requests` — main request table
- `bman_wallet_ledger` — append-only ledger (balance source of truth)
- `bman_withdraw_allocations` — tracks wallet allocation for mixed requests
- `withdraw_audit_log` — audit trail

### 2. Architecture Overview

**Key Files Updated:**
- `application/models/withdraw/Bmanwithdraw_model.php` — request creation & status transitions
- `application/controllers/user/Bmanwithdraw.php` — user-facing request creation
- `application/controllers/admin/withdraw/Bmanwithdraw.php` — admin approval workflow

**Data Flow:**
```
User Submits Request
    ↓
[Balance Check] — uses WalletMaturity_model for matured balance
    ↓
[Create Request] — instant ledger lock (BEFORE INSERT trigger simulation)
    ↓
[Pending State] — funds locked, awaiting admin review
    ↓
[Admin Approval] — transitions to approved/rejected
    ↓
[Processing] — admin marks for sending
    ↓
[Complete/Failed] — lock converts to debit or is released
```

---

## Data Model

### `bman_withdraw_requests`

```sql
id, request_no, user_id, source_wallet, request_amount, fee_amount, net_amount,
bman_usdt_rate, usdt_amount, withdraw_address, remark, tx_hash, admin_remark,
status, approved_by, approved_at, completed_at, created_at
```

- `source_wallet`: `'exchange'|'earning'|'staking'|'bonus'|'mixed'`
- `status`: `pending → approved → processing → completed` (or rejected/failed at any time)
- `fee_amount`: BMAN withdrawal fee
- `net_amount`: `request_amount - fee_amount`
- `bman_usdt_rate`: conversion rate at request time (for auditing)
- `usdt_amount`: `net_amount * bman_usdt_rate` (what user receives)

### `bman_wallet_ledger`

```sql
id, user_id, wallet, entry_type, ref_type, ref_id, amount,
maturity_date, status, remark, created_at
```

- `entry_type`: `'credit'` | `'debit'` | `'lock'`
- `ref_type`: `'withdrawal'`, `'staking'`, `'airdrop'`, etc
- `ref_id`: points to source (e.g., withdrawal request ID)
- `maturity_date`: NULL = immediately matured; set for future availability (staking)
- `status`: `'active'` | `'reversed'` (never delete, mark reversed)

**Balance Calculation:**
```
available(wallet) = SUM(matured credits) - SUM(debits) - SUM(active locks)
available(user)   = SUM(available) across all 4 wallets
```

### `bman_withdraw_allocations`

```sql
id, request_id, wallet, amount, created_at
```

Tracks which wallet(s) contributed to a `source_wallet='mixed'` request.

For mixed requests, the system allocates by priority:
```
bonus → earning → exchange → staking
```

Each wallet slice gets a lock entry + allocation entry.

---

## Request Lifecycle & Status Transitions

### Legal Transitions

```
pending
├─→ approved     [admin approves for processing]
└─→ rejected     [admin rejects; releases locks]

approved
├─→ processing   [admin marks as sending on-chain]
└─→ rejected     [admin changes mind; releases locks]

processing
├─→ completed    [payout sent; lock → debit]
└─→ failed       [error during send; releases locks]

completed       [terminal]
rejected        [terminal]
failed          [terminal]
```

### What Each Transition Does

**pending → approved**
- Validates: current status is 'pending'
- Updates: status, approved_by, approved_at, admin_remark
- Locks: still held (not yet paid)
- Ledger: no change

**approved → processing**
- Validates: current status is 'approved'
- Updates: status, admin_remark
- Locks: still held
- Ledger: no change
- *Intent: admin has broadcast payout to blockchain*

**processing → completed**
- Validates: current status is 'processing', tx_hash provided
- Updates: status, tx_hash, completed_at, admin_remark
- Locks: converted to debits (permanent removal)
- Ledger: creates debit entries (one per wallet slice)
- *Funds now gone from user's account*

**pending/approved → rejected**
- Validates: current status is pending or approved
- Updates: status, approved_by, approved_at, admin_remark
- Locks: marked as 'reversed' (released back to user)
- Ledger: active locks are reversed
- *User's available balance increases again*

**processing → failed**
- Validates: current status is 'processing'
- Updates: status, admin_remark (error reason)
- Locks: marked as 'reversed'
- Ledger: active locks are reversed
- *Payout failed; funds returned to user*

---

## Implementation Details

### User-Facing: Withdrawal Request Creation

**File:** `application/controllers/user/Bmanwithdraw.php`

**Endpoint:** `POST /user/bman-withdraw/request`

**Flow:**
1. Validate withdrawal settings enabled
2. Validate no open request exists (pending/approved/processing)
3. Validate amount, address, minimum USDT requirement
4. Check available matured balance
5. Begin transaction
6. Re-check for open request (race condition guard)
7. Call `Bmanwithdraw_model::create_request()` → creates request + ledger locks
8. Log action
9. Commit transaction
10. Return allocations + updated history

**Key Checks:**
- Amount > 0
- Address format (20-120 chars)
- Address ≠ platform custodial address
- Gross USDT ≥ minimum (converted)
- Available BMAN ≥ requested amount (matured only)
- Net USDT > 0 after fee

**Response:**
```json
{
  "status": true,
  "message": "Withdrawal request submitted...",
  "request": { id, request_no, status: "pending", ... },
  "allocations": [
    { wallet: "bonus", amount: 100 },
    { wallet: "earning", amount: 50 }
  ],
  "available_balance": 1234.5678,
  "gross_usdt": 1500.00,
  "net_usdt": 1490.00,
  "processing_fee_usdt": 10.00
}
```

### Model: Request Creation with Locking

**File:** `application/models/withdraw/Bmanwithdraw_model.php::create_request()`

**Called from:** User controller (within transaction with lock)

**Flow:**
1. Validate amount > 0
2. Validate address format & custodial check
3. Check available balance (calls `wallet_balance()`)
4. Create request row
5. Create ledger lock entries:
   - If `source_wallet='mixed'`: call `_lock_allocate()` to split across wallets
   - Otherwise: one lock entry for the full amount
6. Create allocation tracking rows

**Lock Entry Details:**
```php
[
    'user_id' => $user_id,
    'wallet' => $wallet,
    'entry_type' => 'lock',        // type
    'ref_type' => 'withdrawal',    // source
    'ref_id' => $request_id,       // link to request
    'amount' => $amount,            // always positive
    'status' => 'active',           // not yet released
    'remark' => "Withdrawal request #...",
    'created_at' => $now
]
```

**Allocation (Mixed Only):**
```php
[
    'request_id' => $request_id,
    'wallet' => $wallet,           // bonus, earning, exchange, staking
    'amount' => $amount_for_this,
    'created_at' => $now
]
```

### Admin-Facing: Status Transitions

**File:** `application/controllers/admin/withdraw/Bmanwithdraw.php::update()`

**Flow:**
1. Load request; validate exists
2. Parse desired status
3. Begin transaction
4. Call appropriate transition method:
   - `approve()` → pending → approved
   - `mark_processing()` → approved → processing
   - `complete()` → processing → completed
   - `reject()` → pending/approved → rejected
   - `mark_failed()` → processing → failed
5. If error: rollback, show message
6. If completing: insert onchain_transactions record
7. Commit; redirect to view

**Guard Checks (in each method):**
- Verify current status matches expected
- Update with `AND status=expected` clause (prevents race conditions)
- Return error if no rows affected (another admin already changed it)

### Ledger-Based Balance Calculation

**File:** `application/models/withdraw/Bmanwithdraw_model.php::available_balance()`

Uses `WalletMaturity_model` to get:
- Matured credits (maturity_date ≤ NOW or NULL)
- Debits (withdrawals, spending)
- Active locks (pending/approved/processing requests)

**Formula:**
```
available = SUM(matured_credits) - SUM(debits) - SUM(active_locks)
```

**Critical:** Never compute balance yourself; always use `available_balance()` or `wallet_balance()`.

---

## Admin Workflow (Step-by-Step)

### View Pending Requests

```
GET /admin/bman-withdrawals?status=pending
```

Shows all pending requests with:
- Request #, user, amount, fee, net, USDT rate
- Withdrawal address
- Created time
- Allocation details (if mixed)

### Review & Approve

```
POST /admin/bman-withdrawals/update/:id
{
  "status": "approved",
  "admin_remark": "Looks good"
}
```

- Validates status='pending'
- Updates to 'approved'
- Funds still locked
- May be rejected later

### Mark Processing

```
POST /admin/bman-withdrawals/update/:id
{
  "status": "processing",
  "admin_remark": "Sending on-chain"
}
```

- Validates status='approved'
- Updates to 'processing'
- Admin broadcasts payout to blockchain

### Complete Payout

```
POST /admin/bman-withdrawals/update/:id
{
  "status": "completed",
  "tx_hash": "0xabcd...",
  "admin_remark": "Confirmed on-chain"
}
```

- Validates status='processing', tx_hash present
- Converts locks to debits (permanent)
- Records tx_hash and completion time
- Funds now deducted from user account
- Creates onchain_transactions record

### Reject Request

```
POST /admin/bman-withdrawals/update/:id
{
  "status": "rejected",
  "admin_remark": "Address invalid"
}
```

- Validates status in [pending, approved]
- Reverses all active locks
- User's available balance increases
- Can be done at any point before completion

### Mark Failed

```
POST /admin/bman-withdrawals/update/:id
{
  "status": "failed",
  "admin_remark": "Gas fee too high"
}
```

- Validates status='processing'
- Reverses all active locks
- User can retry
- Records failure reason

---

## Edge Cases & Safety

### Double-Spending Prevention

Request creation uses `GET_LOCK` on user_id within transaction:
```php
$this->db->trans_start();
// ... re-check open request ...
$result = $this->bmanwithdraw->create_request(...);
$this->db->trans_complete();
```

All status transitions use guarded UPDATE:
```sql
UPDATE bman_withdraw_requests
  SET status='approved', ...
 WHERE id=:id AND status='pending';  -- guard
```

Result check ensures no race condition.

### Stale Admin Page Scenario

Admin A and Admin B both viewing the same request.
- Admin A marks it approved
- Admin B tries to mark it approved (sees pending on page)
- Result: `UPDATE` finds no matching row → error returned

### Immature BMAN Protection

Balance check uses only matured credits:
```php
$available = $this->maturity->withdrawable($user_id, $wallet);
// This excludes future maturity_date rows
```

A pending/approved/processing request cannot lock immature BMAN.

### Lock Release Guarantees

Locks are only released via:
1. `rejected` state → all active locks marked 'reversed'
2. `failed` state → all active locks marked 'reversed'
3. `completed` state → locks converted to debits (no release)

Never delete a lock row; always `status='reversed'`.

### Concurrent Withdrawal Attempts

User submits two requests rapidly:
1. Request A: checks open request (none), enters transaction
2. Request B: checks open request (none), enters transaction
3. Both try to create request

**Protection:** Re-check open request inside transaction (line 71 in user controller). First one wins; second gets "already have open request" error.

---

## Validation Checklist

When modifying withdraw code, ensure:

- ✅ Amount > 0 and >= min USDT
- ✅ Address valid (20-120 chars), not custodial
- ✅ Balance check uses matured + unlocked only
- ✅ Request creation and lock in ONE transaction
- ✅ Status transitions restricted to legal set
- ✅ Guarded UPDATE with `AND status=...`
- ✅ No direct balance column mutation
- ✅ Money math uses DECIMAL(18,8)
- ✅ Rollback on any error
- ✅ Locks never deleted, always reversed or converted
- ✅ No bypassing triggers or constraints

---

## Views & Queries

### View: Current Wallet Balances

Use `WalletMaturity_model::all_breakdowns()`:
```php
$breakdowns = $this->maturity->all_breakdowns($user_id);
// Returns:
// [
//   'exchange' => ['total' => 1000, 'locked' => 50, 'matured' => 950, 'withdrawable' => 950],
//   'earning' => [...],
//   'staking' => [...],
//   'bonus' => [...]
// ]
```

### Query: Pending Lock Amount for User

```sql
SELECT SUM(amount) AS total_locked
  FROM bman_wallet_ledger
 WHERE user_id = :user_id
   AND entry_type = 'lock'
   AND status = 'active'
   AND ref_type = 'withdrawal';
```

### Query: Withdrawal History for User

```sql
SELECT * FROM bman_withdraw_requests
 WHERE user_id = :user_id
 ORDER BY created_at DESC;
```

### Query: Audit Trail for Request

```sql
SELECT * FROM withdraw_audit_log
 WHERE request_id = :request_id
 ORDER BY created_at ASC;
```

---

## Troubleshooting

### "Insufficient matured balance" When Balance Looks Fine

**Cause:** Balance includes immature BMAN (staking with future maturity_date).

**Check:**
```php
$breakdown = $this->bmanwithdraw->maturity_breakdown($user_id);
echo "Withdrawable staking: " . $breakdown['staking_withdrawable'];
```

### "Already have open request" Even After Completion

**Cause:** Request status is still pending/approved/processing.

**Check:**
```sql
SELECT * FROM bman_withdraw_requests
 WHERE user_id = :user_id
   AND status IN ('pending', 'approved', 'processing')
 ORDER BY id DESC;
```

**Fix:** Admin must update status to completed/rejected/failed.

### Lock Entries Not Created

**Cause:** Transaction rolled back due to error in create_request().

**Check database:**
```sql
SELECT COUNT(*) FROM bman_wallet_ledger
 WHERE ref_type = 'withdrawal' AND ref_id = :request_id;
```

If 0, request creation failed. Check error in model.

### Ledger Out of Balance

**Cause:** Direct UPDATE to balance columns or ledger rows deleted.

**Fix:** Restore from backup. Never delete ledger rows; only mark `status='reversed'`.

---

## FAQ

**Q: Can a user cancel a request?**
A: No. Only admins can reject. User can contact support to request rejection.

**Q: What if network congestion delays payout?**
A: Admin keeps request in 'processing' state. Once confirmed, mark 'completed' with tx_hash.

**Q: Can admin edit a completed request?**
A: No. Terminal states (completed, rejected, failed) cannot be changed. Would need data correction.

**Q: Do fees apply?**
A: Yes. `fee_amount` is deducted from request before creating ledger entries. Calculated as `net_amount = request_amount - fee_amount`.

**Q: What if conversion rate changes?**
A: `bman_usdt_rate` is captured at request time for auditing. Later rate changes don't affect that request.

**Q: How do I track where BMAN went?**
A: Check `withdraw_audit_log` for who did what, and `bman_wallet_ledger` for the permanent debit entry.

---

## Related Documentation

- `docs/18_WITHDRAW_REQUEST_AGENTS.md` — specification (read first)
- `WalletMaturity_model.php` — balance breakdown logic
- `Walletledger_model.php` — ledger helper methods

