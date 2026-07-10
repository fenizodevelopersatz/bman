# ROI Data Storage - What Gets Stored Where

## Example: FIXED Plan Investment

```
User Investment:
  100,000 BMAN @ 150% ROI for 2 years
  Expected ROI Return: 150,000 BMAN
```

---

## 📁 Database Records After Purchase

### Table 1: `staking_swap_orders` (Existing)

```sql
INSERT INTO staking_swap_orders VALUES (
  id: 999,
  user_id: 123,
  usdt_amount: 50,        -- USDT paid by user
  principal_amount: 100000, -- BMAN received by platform
  roi_rate: 150,          -- ROI percentage
  maturity_date: 2027-07-09,
  roi_return_status: 'pending',
  maturity_roi_amount: 150000, ← ✓ STORES TOTAL ROI HERE
  roi_staking_management_id: 1, -- Link to management table
  created_at: 2026-07-10
);
```

**What this stores:**
- ✓ Principal amount (100,000 BMAN) - locked
- ✓ Total ROI amount (150,000 BMAN) - to be distributed
- ✓ Link to ROI management record

---

### Table 2: `roi_staking_management` (New)

```sql
INSERT INTO roi_staking_management VALUES (
  id: 1,
  staking_swap_orders_id: 999,
  user_id: 123,
  plan_type: 'fixed',
  
  principal_amount: 100000,      ← ✓ Original staked (locked)
  roi_rate_percent: 150,         ← ✓ ROI percentage
  total_roi_amount: 150000,      ← ✓ Total to distribute
  
  fixed_payment_amount: 150000,  ← ✓ FIXED PLAN: pays all 150k at maturity
  fixed_maturity_date: 2027-07-09,
  fixed_status: pending,
  
  overall_status: active,
  total_paid_amount: 0,          ← Starts at 0, updated on each payout
  remaining_to_pay: 150000,      ← Starts at 150k
  
  created_at: 2026-07-10
);
```

**What this stores:**
- ✓ Separated principal from ROI
- ✓ Complete breakdown for FIXED plan
- ✓ Status tracking for each payment type

---

### Table 3: `wallet_ledger` (Existing - NO CHANGE at purchase)

```sql
-- User's wallets at purchase time
SELECT * FROM wallet_ledger WHERE user_id = 123;

wallet_type | balance
------------|--------
exchange    | 0        ← No change at purchase
earning     | 0        ← No change yet (ROI pending)
staking     | 0        ← No change
bonus       | 0        ← No change
```

**NOTE:** Principal is transferred but NOT stored in wallet_ledger.
It's locked in staking_swap_orders.roi_amount (100,000 BMAN).

---

## 💳 On Maturity Date (Jul 9, 2027)

### Step 1: CRON Reads Record

```php
$record = $this->db->where('id', 1)->get('roi_staking_management')->row_array();

// Retrieves:
$record['fixed_payment_amount'] = 150000  ← ✓ ROI ONLY
$record['user_id'] = 123
$record['fixed_status'] = 'pending'
```

---

### Step 2: CRON Credits Earning Wallet

```sql
UPDATE wallet_ledger
SET balance = balance + 150000  ← ✓ ONLY ROI AMOUNT
WHERE user_id = 123 
AND wallet_type = 'earning';
```

**Before:**
```
wallet_type | balance
------------|--------
earning     | 0
```

**After:**
```
wallet_type | balance
------------|--------
earning     | 150000  ← ✓ Only ROI, not principal+ROI
```

---

### Step 3: CRON Records Transaction

```sql
INSERT INTO onchain_transactions VALUES (
  id: 5001,
  staking_swap_orders_id: 999,
  user_id: 123,
  tx_type: 'roi_maturity_final',
  amount: 150000,  ← ✓ ONLY ROI AMOUNT
  token: 'BMAN',
  from_wallet: 'admin',
  to_wallet: 'earning',
  status: 'completed',
  tx_hash: 'roi-maturity-1-20270709143022',
  created_at: 2027-07-09 14:30:22
);
```

---

### Step 4: CRON Updates Status

```sql
UPDATE roi_staking_management SET
  fixed_status: 'completed',
  fixed_paid_date: 2027-07-09 14:30:22,
  fixed_tx_hash: 'roi-maturity-1-20270709143022',
  overall_status: 'completed',
  total_paid_amount: 150000,  ← Increment from 0
  remaining_to_pay: 0
WHERE id = 1;
```

---

## 📊 Final State Summary

### ROI Management Table (roi_staking_management)
```
principal_amount:      100000  ← Original staked (locked)
total_roi_amount:      150000  ← Total to distribute
fixed_payment_amount:  150000  ← What was paid out
total_paid_amount:     150000  ← Cumulative paid
remaining_to_pay:      0       ← Nothing left to pay
overall_status:        'completed'
```

### Staking Orders Table (staking_swap_orders)
```
principal_amount:      100000  ← Still shows original stake
maturity_roi_amount:   150000  ← Shows expected ROI
roi_return_status:     'completed'
```

### Wallet (wallet_ledger)
```
earning wallet balance: 150000 ← ✓ USER RECEIVES THIS
                                 (ROI only, not principal)
```

### Transaction Log (onchain_transactions)
```
Amount: 150000
tx_type: 'roi_maturity_final'
Status: 'completed'
```

---

## 🔄 REGULAR Plan Example

Same logic applies but spread across 3 payments:

### ROI Management Table at Creation
```
principal_amount:       100000
total_roi_amount:       150000
payment_day_5_amount:   50000   ← 1/3 of ROI
payment_day_15_amount:  50000   ← 1/3 of ROI
payment_day_25_amount:  50000   ← 1/3 of ROI
```

### Wallet Ledger Over Time

**Day 5:**
```
earning: 0 → 50000
```

**Day 15:**
```
earning: 50000 → 100000
```

**Day 25:**
```
earning: 100000 → 150000  ← Final total
```

### Transaction Log
```
Day 5:  amount: 50000,  tx_type: 'roi_monthly_5'
Day 15: amount: 50000,  tx_type: 'roi_monthly_15'
Day 25: amount: 50000,  tx_type: 'roi_monthly_25'

Total across all: 150000 ✓
```

---

## 🔀 COMBO Plan Example

### ROI Management Table at Creation
```
principal_amount:       100000
total_roi_amount:       150000
payment_day_5_amount:   37500   ← 1/4 of ROI
payment_day_15_amount:  37500   ← 1/4 of ROI
payment_day_25_amount:  37500   ← 1/4 of ROI
fixed_payment_amount:   37500   ← 1/4 of ROI (maturity)
```

### Wallet Ledger Timeline

**Day 5:**
```
earning: 0 → 37500
```

**Day 15:**
```
earning: 37500 → 75000
```

**Day 25:**
```
earning: 75000 → 112500
```

**Maturity (Day 2027-07-09):**
```
earning: 112500 → 150000  ← Final total
```

### Transaction Log
```
Day 5:  amount: 37500, tx_type: 'roi_monthly_5'
Day 15: amount: 37500, tx_type: 'roi_monthly_15'
Day 25: amount: 37500, tx_type: 'roi_monthly_25'
Jul 9:  amount: 37500, tx_type: 'roi_maturity_final'

Total: 150000 ✓
```

---

## ✅ Key Verification Points

### Check 1: No Double-Counting
```sql
-- Should show only ROI amounts, never principal+ROI
SELECT 
  SUM(fixed_payment_amount) as fixed_total,
  SUM(payment_day_5_amount) as day5_total,
  SUM(payment_day_15_amount) as day15_total,
  SUM(payment_day_25_amount) as day25_total
FROM roi_staking_management
WHERE plan_type = 'fixed';

-- Expected: fixed_total = total_roi_amount (not principal+ROI)
```

### Check 2: Earning Wallet Only Gets ROI
```sql
-- Should show only ROI amounts
SELECT 
  SUM(amount) as total_distributed
FROM onchain_transactions
WHERE tx_type LIKE 'roi%'
AND to_wallet = 'earning';

-- Expected: matches total_roi_amount from roi_staking_management
-- NOT principal + roi
```

### Check 3: Status Progression
```sql
-- Show the progression from pending → completed
SELECT 
  fixed_status,
  COUNT(*) as count,
  SUM(total_paid_amount) as paid_total
FROM roi_staking_management
WHERE plan_type = 'fixed'
GROUP BY fixed_status;

-- Expected:
-- pending | X records | 0 paid
-- completed | Y records | Y×150000 paid
```

---

## 🎯 Bottom Line

```
                    FIXED PLAN
                    ==========

Purchase (Jul 10, 2026):
  staking_swap_orders.principal_amount = 100,000 (locked)
  roi_staking_management.fixed_payment_amount = 150,000 (ROI only)

Maturity (Jul 9, 2027):
  wallet_ledger.earning = 0 → 150,000 (ROI only)
  onchain_transactions.amount = 150,000 (ROI only)

Result:
  ✓ User staked: 100,000 BMAN
  ✓ User receives ROI: 150,000 BMAN
  ✓ Principal remains locked (or can be unstaked separately)
  ✓ Total wallet gain: 150,000 BMAN (not 250,000)
```

