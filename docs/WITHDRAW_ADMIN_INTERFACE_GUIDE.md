# BMAN Withdrawal Admin Interface — Complete Guide

**Updated:** 2026-07-16  
**Status:** ✅ Bank Details Integrated  

---

## Overview

The admin withdrawal request interface now includes complete user bank details from the `user_bank` table, allowing admins to verify KYC information before approving payouts.

---

## Admin List View (`/admin/bman-withdrawals`)

### Features

**Filter Section**
```
┌─────────────────────────────────────────┐
│ [Search Box]  [Status ▼]  [Wallet ▼]   │
│ request_no    pending      mixed        │
│ username      approved     exchange     │
│ email         completed    earning      │
│ address       etc.         staking      │
│ holder_name              bonus          │
└─────────────────────────────────────────┘
```

**Table Columns**
```
Request No | User | Bank Account | Wallet | BMAN Amount | USDT | Status | Created | Action
───────────────────────────────────────────────────────────────────────────────────────────
BWM-...    │ ID   │ Account Info │ mixed  │ 1.5 BMAN    │ 0.5  │ pending│ Oct 16  │ View
           │ name │ Bank Name    │        │ Fee: 1.0    │      │        │         │
           │ UID  │ A/C: xxxx    │        │ Net: 0.5    │      │        │         │
           │ email│ UPI: xxx     │        │             │      │        │         │
```

### Data Displayed

**Left Columns (User Info)**
```
Request No:   BWM-20260716143424-2750
User:         Admin (NEXMAN001)
Email:        admin@example.com
Bank Account: (integrated)
```

**Center Columns (Amount & Wallet)**
```
Wallet:       [mixed]
BMAN Amount:  1.5 BMAN
Fee:          1.0 BMAN
Net:          0.5 BMAN
USDT:         0.5 USDT
Rate:         1.0
```

**Right Columns (Status & Action)**
```
Status:   [PENDING] [APPROVED] [PROCESSING] [COMPLETED]
Created:  Oct 16, 18:04
Action:   [View Button]
```

### Bank Details in List
```
Holder Name:    John Doe
Bank Name:      SBI Bank
Account No:     (last 4 digits shown: ...5678)
UPI ID:         john@upi
```

---

## Admin Detail View (`/admin/bman-withdrawals/view/:id`)

### Section 1: Request Details
```
┌─────────────────────────────────────────────┐
│ Request Details                              │
├─────────────────────────────────────────────┤
│ Left Column          │ Right Column          │
├─────────────────────┼─────────────────────┤
│ Request No: BWM-... │ Amount: 1.5 BMAN    │
│ User: Admin         │ Fee: 1.0 BMAN       │
│ Email: admin@...    │ Net: 0.5 BMAN       │
│ Wallet: mixed       │ USDT: 0.5 USDT      │
│ Status: ⚠ PENDING   │ Rate: 1.0           │
└─────────────────────┴─────────────────────┘
```

### Section 2: Bank & KYC Details (NEW)
```
┌─────────────────────────────────────────────┐
│ Bank & KYC Details                           │
├─────────────────────────────────────────────┤
│ Left Column          │ Right Column          │
├─────────────────────┼─────────────────────┤
│ Account Holder:     │ UPI ID:             │
│ John Doe            │ john@upi            │
│                     │                     │
│ Bank Name:          │ Bank KYC Status:    │
│ SBI Bank            │ [APPROVED] ✓        │
│                     │                     │
│ Account Number:     │ User Status:        │
│ xxxxxxxxxx5678      │ [ACTIVE] ✓          │
│                     │                     │
│ IFSC Code:          │                     │
│ SBIN0005678         │                     │
└─────────────────────┴─────────────────────┘
```

**Status Indicators**
```
✅ Bank KYC: APPROVED      → Green badge, safe to pay
⚠️  Bank KYC: PENDING      → Yellow badge, request verification first
❌ Bank KYC: REJECTED      → Red badge, cannot pay
⚠️  User Status: INACTIVE  → Yellow badge, check before paying
```

### Section 3: Withdrawal Address & Timestamps
```
┌─────────────────────────────────────────────┐
│ Withdrawal Address & Timestamps              │
├─────────────────────────────────────────────┤
│ Withdraw Address: 0x7b5AC2f86C2b21...      │
│                                              │
│ Tx Hash: Not yet confirmed                  │
│ (Will be filled when completed)             │
│                                              │
│ Created: 2026-07-16 18:04:24               │
│ Approved At: 2026-07-16 18:04:24           │
│ by Admin #1                                 │
│                                              │
│ User Remark: By siva testing 2.0           │
│ Admin Remark: Looks good                   │
└─────────────────────────────────────────────┘
```

### Section 4: Wallet Allocations (if mixed)
```
┌─────────────────────┐
│ Wallet Allocations  │
├─────────────────────┤
│ Wallet  │ Amount    │
├─────────┼───────────┤
│ bonus   │ 1.5 BMAN  │
└─────────┴───────────┘
```

### Section 5: Update Status (Form)
```
┌──────────────────────────────────┐
│ Update Status                     │
├──────────────────────────────────┤
│ New Status:                       │
│ [▼ Approve Request / Reject ...]  │
│                                  │
│ Transaction Hash:                │
│ [0x.................]            │
│                                  │
│ Admin Remark:                    │
│ [Verified, sending to chain]     │
│                                  │
│ [Update Status] [Back to List]   │
└──────────────────────────────────┘
```

**Legal Status Options** (conditional)
```
If Status = pending:
  • Approve Request
  • Reject Request

If Status = approved:
  • Mark as Processing
  • Reject Request

If Status = processing:
  • Complete (with tx_hash)
  • Mark as Failed

If Status = completed/rejected/failed:
  • (No changes allowed - terminal state)
```

---

## Data Query Structure

### Main Query
```sql
SELECT
    wr.*,              -- All request fields
    u.username,
    u.email,
    u.referral_id,
    u.status,          -- User status
    ub.holder_name,
    ub.bank_name,
    ub.account_number,
    ub.ifsc,
    ub.upi_id,
    ub.status          -- Bank KYC status
FROM bman_withdraw_requests wr
LEFT JOIN users u ON wr.user_id = u.id
LEFT JOIN user_bank ub ON wr.user_id = ub.user_id 
                       AND ub.status = 'approved'
```

### With Allocations
```sql
-- Includes wallet allocation details
LEFT JOIN bman_withdraw_allocations ba 
    ON wr.id = ba.request_id
```

### With Lock Status
```sql
-- Shows current locked amount in ledger
(SELECT COALESCE(SUM(amount), 0)
 FROM bman_wallet_ledger
 WHERE ref_type = 'withdrawal'
   AND ref_id = wr.id
   AND entry_type = 'lock'
   AND status = 'active') AS locked_amount
```

---

## Filtering & Search

### List View Filters

**Status Filter**
```
All Status (shows all)
├─ Pending (awaiting admin review)
├─ Approved (approved, not yet paid)
├─ Processing (payment in progress)
├─ Completed (paid out)
├─ Rejected (admin rejected)
└─ Failed (payout failed)
```

**Wallet Filter**
```
All Wallets (shows all)
├─ Mixed (from multiple wallets)
├─ Exchange
├─ Earning
├─ Staking
└─ Bonus
```

**Search Query**
Searches across:
```
✓ Request No (BWM-...)
✓ Username
✓ Email
✓ Referral ID (UID)
✓ Withdraw Address
✓ Account Holder Name
```

### Example Searches
```
"BWM-20260716"       → Shows that request #
"admin"              → Shows all requests from user "admin"
"john@upi"           → Shows requests with UPI "john@upi"
"0x7b5AC2f"          → Shows requests to that address
"SBI"                → Shows requests from SBI account holders
```

---

## Admin Workflow with Bank Details

### Step 1: Review Pending Requests
```
Admin goes to: GET /admin/bman-withdrawals?status=pending

Sees list of requests waiting for approval
Each row shows:
  • User info (name, email, UID)
  • Bank details (holder, bank, account)
  • Amount in BMAN and USDT
  • Status badge
```

### Step 2: Click "View" for Details
```
Admin clicks: View button for request #4

Sees full details:
  • Request amount and fees
  • Bank KYC status
  • User account status
  • Withdrawal address
  • All timestamps
```

### Step 3: Verify Information
```
Admin checks:
  ✓ Bank KYC Status = APPROVED?
  ✓ User Status = ACTIVE?
  ✓ Bank details valid?
  ✓ Withdrawal address looks legitimate?
  ✓ Amount reasonable?
```

### Step 4: Approve Request
```
Admin selects: "Approve Request" in status dropdown
Admin enters remark: "Verified KYC and bank details"
Admin clicks: "Update Status"

Result:
  • Status changes: pending → approved
  • Timestamp recorded
  • Admin ID stored
  • Funds still locked
```

### Step 5: Mark Processing
```
Later, when ready to process:
Admin selects: "Mark as Processing"
Admin clicks: "Update Status"

Result:
  • Status changes: approved → processing
  • Payout broadcast to blockchain
```

### Step 6: Complete with Tx Hash
```
After blockchain confirmation:
Admin enters: tx_hash "0xabc123..."
Admin selects: "Complete (with tx_hash)"
Admin clicks: "Update Status"

Result:
  • Status changes: processing → completed
  • Lock converted to permanent debit
  • Tx hash recorded
  • FINAL (cannot undo)
```

---

## Bank Details Status Indicators

### Bank KYC Status
```
✅ APPROVED  → User's bank is verified, safe to pay
⚠️  PENDING  → Bank verification in progress, wait
❌ REJECTED  → Bank failed verification, cannot pay
⛔ NO DATA   → No bank details found, request KYC first
```

### User Account Status
```
✅ ACTIVE/1/APPROVED  → User is active, can receive payout
⚠️  INACTIVE          → User inactive, check before paying
❌ SUSPENDED         → User suspended, do not pay
```

---

## Error Scenarios

### "No approved bank details found"
```
Situation:
  User submitted withdrawal but hasn't added bank info

Solution:
  1. Reject the request
  2. Ask user to add bank details
  3. User must get KYC approved
  4. Can then resubmit
```

### "Bank KYC Status: PENDING"
```
Situation:
  Bank details submitted but not yet verified

Solution:
  1. Do not approve yet
  2. Wait for KYC verification
  3. Or reject and ask user to verify first
```

### "User Status: INACTIVE"
```
Situation:
  User's account is inactive

Solution:
  1. Check why inactive (suspended? dormant?)
  2. If account should be active, reactivate it
  3. Then can proceed with payout
```

---

## Database Schema Reference

### bman_withdraw_requests
```
id, request_no, user_id, source_wallet,
request_amount, fee_amount, net_amount,
bman_usdt_rate, usdt_amount,
withdraw_address, remark, tx_hash,
admin_remark, status,
approved_by, approved_at, completed_at, created_at
```

### users (linked)
```
id, username, email, referral_id, status
```

### user_bank (linked)
```
id, user_id, holder_name, bank_name,
account_number, ifsc, upi_id, status
```

### bman_withdraw_allocations (optional)
```
id, request_id, wallet, amount, created_at
```

### bman_wallet_ledger (for lock tracking)
```
Shows active locks and ledger entries
Used for balance calculations
```

---

## Common Admin Tasks

### Find All Pending Requests from Specific User
```sql
SELECT * FROM bman_withdraw_requests wr
LEFT JOIN users u ON wr.user_id = u.id
LEFT JOIN user_bank ub ON wr.user_id = ub.user_id AND ub.status = 'approved'
WHERE wr.status = 'pending'
  AND u.username = 'john'
ORDER BY wr.created_at DESC;
```

### Find Requests with Missing KYC
```sql
SELECT * FROM bman_withdraw_requests wr
LEFT JOIN user_bank ub ON wr.user_id = ub.user_id AND ub.status = 'approved'
WHERE wr.status = 'pending'
  AND ub.id IS NULL
ORDER BY wr.created_at DESC;
```

### Find Large Requests (> 50 BMAN)
```sql
SELECT * FROM bman_withdraw_requests wr
LEFT JOIN users u ON wr.user_id = u.id
LEFT JOIN user_bank ub ON wr.user_id = ub.user_id AND ub.status = 'approved'
WHERE request_amount > 50
  AND wr.status IN ('pending', 'approved')
ORDER BY request_amount DESC;
```

### Dashboard Stats
```sql
SELECT
    COUNT(*) AS total,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) AS pending,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) AS completed,
    SUM(CASE WHEN status IN ('pending','approved','processing') THEN request_amount ELSE 0 END) AS locked,
    SUM(CASE WHEN status = 'completed' THEN net_amount ELSE 0 END) AS paid_out
FROM bman_withdraw_requests;
```

---

## Tips for Admins

✅ **Always check bank KYC status before approving**  
✅ **Verify user account is ACTIVE before paying**  
✅ **Use search to find related requests quickly**  
✅ **Add clear remarks for audit trail**  
✅ **Include tx_hash when marking complete**  
✅ **Do not approve if address looks suspicious**  

⚠️ **Do NOT approve if KYC is not verified**  
⚠️ **Do NOT pay inactive or suspended users**  
⚠️ **Do NOT forget to include tx_hash on completion**  
⚠️ **Do NOT edit completed requests (terminal state)**  

---

## Status Transition Rules

```
pending
├─→ approved (check KYC ✓)
└─→ rejected (user request or suspicious)

approved
├─→ processing (send to blockchain)
└─→ rejected (changed mind, something wrong)

processing
├─→ completed (with tx_hash)
└─→ failed (network/gas issue)

completed, rejected, failed
└─→ [TERMINAL - NO CHANGES]
```

---

## Summary

Admin interface now provides:
✅ Complete bank details in list and detail views  
✅ KYC verification status checks  
✅ User account status validation  
✅ Integrated search across bank info  
✅ Clear status indicators (badges)  
✅ Better organization (sections)  
✅ Audit trail tracking  
✅ Safe transition rules  

**Result:** Admins can now review full KYC info before approving any withdrawal.

