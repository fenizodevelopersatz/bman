# Withdraw Locking Workflow — Quick Reference

## 🚀 Setup (One-time)

```bash
# Run database migration
php db/migrate_withdraw_requests.php
```

Creates: `bman_withdraw_requests`, `bman_wallet_ledger`, `bman_withdraw_allocations`, `withdraw_audit_log`

---

## 📋 User Flow

### 1. User Submits Withdrawal Request

**Endpoint:** `POST /user/bman-withdraw/request`

**Payload:**
```json
{
  "withdraw_bman": 100.5,
  "wallet_address": "0x742d35Cc6634C0532925a3b844Bc92d426B2e98e",
  "remark": "Personal wallet"
}
```

**Validation:**
- ✅ Withdrawals enabled
- ✅ No open request (pending/approved/processing)
- ✅ Amount > 0
- ✅ Amount converts to minimum USDT requirement
- ✅ Address valid format (20-120 chars)
- ✅ Address ≠ platform custodial
- ✅ Sufficient matured balance

**On Success:**
- Request created with `status='pending'`
- BMAN locked in ledger (instant)
- User's available balance decreases
- Returned: request_no, allocations, updated history

**Response:**
```json
{
  "status": true,
  "message": "Withdrawal request submitted...",
  "request": {
    "id": 123,
    "request_no": "BWM-20260715123456-7890",
    "status": "pending",
    "request_amount": 100.5,
    "net_amount": 99.5,
    "withdraw_address": "0x742d..."
  },
  "allocations": [
    { "wallet": "bonus", "amount": 50 },
    { "wallet": "earning", "amount": 50.5 }
  ],
  "available_balance": 500.25,
  "gross_usdt": 1500.75,
  "net_usdt": 1490.75,
  "processing_fee_usdt": 10.00
}
```

**UI State:**
- Form disabled: "Request Locked"
- User sees: "You already have request #BWM-... in pending status"
- Can view request in history

---

## 👨‍💼 Admin Workflow

### View Pending Requests

```
GET /admin/bman-withdrawals?status=pending
```

Shows: request #, user, amount BMAN, fee, net BMAN, USDT rate, address, timestamp, allocations

### Approve Request

**Endpoint:** `POST /admin/bman-withdrawals/update/:id`

```json
{
  "status": "approved",
  "admin_remark": "Verified address and KYC"
}
```

**What happens:**
- Status: `pending → approved`
- Locks: still held ✅
- Funds: not yet paid
- Can still reject later

### Send Payout (Mark Processing)

```json
{
  "status": "processing",
  "admin_remark": "Sending to blockchain"
}
```

**What happens:**
- Status: `approved → processing`
- Locks: still held ✅
- Blockchain: payout queued/sent

### Confirm Payout Sent (Complete)

```json
{
  "status": "completed",
  "tx_hash": "0xabcdef1234567890...",
  "admin_remark": "Confirmed on BSC"
}
```

**What happens:**
- Status: `processing → completed`
- Locks: **converted to debits** 🔴
- Funds: permanently deducted ✅
- User's balance: now final

### Reject Request (Any Time Before Complete)

```json
{
  "status": "rejected",
  "admin_remark": "Address looks suspicious"
}
```

**What happens:**
- Status: `pending/approved → rejected`
- Locks: **released** 🔓
- Funds: returned to user
- User's available balance: increases again
- User can submit new request

### Mark Failed (If Send Failed)

```json
{
  "status": "failed",
  "admin_remark": "Gas price too high, will retry later"
}
```

**What happens:**
- Status: `processing → failed`
- Locks: **released** 🔓
- Funds: returned to user
- User can retry

---

## 🔐 Status Diagram

```
REQUEST CREATED (pending)
    ↓
┌───────────────────────────────────┐
│  pending → approved | rejected    │
│  (locked, awaiting review)        │
└───────────────────────────────────┘
    ↓ [approved]
┌───────────────────────────────────┐
│  approved → processing | rejected │
│  (locked, sending on-chain)       │
└───────────────────────────────────┘
    ↓ [processing]
┌───────────────────────────────────┐
│  processing →  completed | failed │
│  (locked, waiting confirm)        │
└───────────────────────────────────┘
    ↓
┌───────────────────────────────────┐
│  TERMINAL STATES                  │
│  ✅ completed   (lock→debit)      │
│  ❌ rejected    (lock→release)    │
│  ❌ failed      (lock→release)    │
└───────────────────────────────────┘
```

**Key:**
- 🔒 Locked = BMAN reserved, cannot be withdrawn again
- 🔓 Released = BMAN returned, user can submit new request
- 💰 Debit = BMAN permanently gone (payout confirmed)

---

## 💡 Key Concepts

### Balance Formula
```
available_balance = (matured_credits - debits - active_locks)
                    summed across all 4 wallets
```

**Never use column balance; always compute from ledger.**

### Lock vs Debit

| State | Action | Balance Impact |
|-------|--------|---|
| **Lock** | Request created | ↓ locked amount |
| **Release** | Request rejected | ↑ original amount back |
| **Debit** | Request completed | ↓ permanent removal |

### Mixed Wallet Allocation

When `source_wallet='mixed'`, system allocates by priority:
```
1. Bonus wallet     (if has enough)
2. Earning wallet   (if bonus insufficient)
3. Exchange wallet  (if bonus+earning insufficient)
4. Staking wallet   (last resort, if matured)
```

See allocations in request details.

### Immature BMAN

Staking BMAN with future `maturity_date` **cannot** be withdrawn, even if in staking wallet. 

- Withdrawal uses only matured balance
- Use UI "Upcoming Unlocks" to see when staking matures

---

## 🐛 Troubleshooting

### "Already have open request"
User submitted request, then tried again before admin approved.

**Solution:** User waits for admin to approve/reject. Contact support to cancel.

### "Insufficient matured balance"
User's balance includes immature staking BMAN.

**Check:**
```php
$breakdown = $this->bmanwithdraw->maturity_breakdown($user_id);
echo $breakdown['staking_withdrawable'];  // Only matured
```

### Form Still Shows "Locked" After Approval

Page not refreshed. **Reload page** or wait for auto-refresh.

### Admin Page: "Failed to approve (may have been updated...)"

Another admin already changed status (race condition). **Reload page** to see current state.

### Ledger Shows Orphan Lock Entries

Request row was deleted without transitioning status (data corruption).

**Never delete request rows.** Always transition to rejected/failed first.

---

## 📊 Database Queries

### Pending Requests Awaiting Admin Review

```sql
SELECT wr.*, u.username, u.email
  FROM bman_withdraw_requests wr
  JOIN users u ON u.id = wr.user_id
 WHERE wr.status = 'pending'
 ORDER BY wr.created_at DESC;
```

### User's Current Locked Amount

```sql
SELECT COALESCE(SUM(amount), 0) AS total_locked
  FROM bman_wallet_ledger
 WHERE user_id = :user_id
   AND entry_type = 'lock'
   AND status = 'active'
   AND ref_type = 'withdrawal';
```

### Withdrawal History with Audit Trail

```sql
SELECT wr.id, wr.request_no, wr.status, wr.created_at, wr.completed_at,
       al.action, al.old_status, al.new_status, al.remarks, al.created_at as audit_at
  FROM bman_withdraw_requests wr
  LEFT JOIN withdraw_audit_log al ON al.request_id = wr.id
 WHERE wr.user_id = :user_id
 ORDER BY wr.id DESC, al.created_at ASC;
```

### Check Request Allocations

```sql
SELECT wallet, SUM(amount) AS allocated
  FROM bman_withdraw_allocations
 WHERE request_id = :request_id
 GROUP BY wallet;
```

---

## 🔑 Key Files

| File | Purpose |
|------|---------|
| `application/models/withdraw/Bmanwithdraw_model.php` | Locking logic, status transitions |
| `application/controllers/user/Bmanwithdraw.php` | User request creation |
| `application/controllers/admin/withdraw/Bmanwithdraw.php` | Admin approval workflow |
| `db/migrate_withdraw_requests.php` | Database setup |
| `docs/18_WITHDRAW_REQUEST_AGENTS.md` | Specification |
| `docs/19_WITHDRAW_IMPLEMENTATION.md` | Full implementation guide |

---

## ✅ Validation Checklist for New Code

When modifying withdraw logic:

- ✅ Use `available_balance()` or `wallet_balance()`, never direct column
- ✅ Guard UPDATE with `WHERE id=:id AND status=:expected`
- ✅ Use transactions for multi-step operations
- ✅ Create ledger entries (never DELETE existing rows)
- ✅ Mark locks as 'reversed' when releasing
- ✅ Check stale admin page scenario (another admin changed status)
- ✅ Validate address format and custodial check
- ✅ Use DECIMAL(18,8) for money math
- ✅ Validate matured balance only
- ✅ Log admin actions for audit trail

---

**Last Updated:** 2026-07-16  
**Status:** Production  
**Related:** AGENTS.md, Implementation Guide

