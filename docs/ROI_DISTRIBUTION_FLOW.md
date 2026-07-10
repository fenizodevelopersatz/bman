# ROI Distribution Flow - ROI Only (NOT Principal + ROI)

## 🎯 Critical Clarification

**The system ONLY distributes ROI return amount to earning wallet.**

The principal amount stays in staking and is NOT credited again.

---

## 📊 Example Calculation

```
STAKING PURCHASE:
  Principal: 100,000 BMAN
  ROI Rate: 150%
  Duration: 2 years
  Maturity Date: Jul 9, 2027
  
CALCULATION:
  Total ROI = Principal × (ROI% / 100)
  Total ROI = 100,000 × (150 / 100)
  Total ROI = 100,000 × 1.5
  Total ROI = 150,000 BMAN

DATABASE STORAGE (roi_staking_management):
  ✓ principal_amount = 100,000 BMAN (locked in staking)
  ✓ total_roi_amount = 150,000 BMAN (to be distributed)
  
EARNING WALLET CREDIT (maturity):
  ✓ +150,000 BMAN ONLY (ROI return)
  ✗ NOT +250,000 BMAN (principal + ROI would be wrong)
```

---

## 🔄 FIXED Plan Distribution

### Database Record Creation

**File:** `application/models/RoiStakingManagement_model.php` (line 36-41)

```php
case 'fixed':
    $recordData['fixed_payment_amount'] = $totalROI;  // ✓ ROI only
    $recordData['fixed_maturity_date'] = $data['maturity_date'];
    $recordData['next_payment_date'] = $data['maturity_date'];
    break;
```

**Result in Database:**
```sql
roi_staking_management:
  id: 1
  principal_amount: 100000         ← Locked amount
  roi_rate_percent: 150
  total_roi_amount: 150000         ← ROI to distribute
  fixed_payment_amount: 150000     ← Only ROI, NOT principal
  fixed_maturity_date: 2027-07-09
  fixed_status: pending
```

### CRON Execution (Maturity Date)

**File:** `application/controllers/cron/RoiMaturityPayment_cron.php` (line 49)

```php
$amount = $record['fixed_payment_amount'];  // ✓ Gets 150,000 (ROI only)

// Credit earning wallet
$this->db->where('user_id', $record['user_id'])
         ->where('wallet_type', 'earning')
         ->update('wallet_ledger', [
             'balance' => $this->db->raw('balance + ' . $amount),  // ✓ +150,000
         ]);
```

**Result in Wallet:**
```
Earning Wallet BEFORE: 0 BMAN
Earning Wallet AFTER:  150,000 BMAN ✓ (ROI only)

Principal remains in staking_swap_orders (user cannot access unless unstakes)
```

---

## 🗓️ REGULAR Plan Distribution

### Database Record Creation

**File:** `application/models/RoiStakingManagement_model.php` (line 43-50)

```php
case 'regular':
    $monthlyPayment = $totalROI / 3;         // = 150,000 / 3 = 50,000
    $recordData['regular_payment_amount'] = $monthlyPayment;
    $recordData['payment_day_5_amount'] = $monthlyPayment;    // ✓ 50,000 (ROI only)
    $recordData['payment_day_15_amount'] = $monthlyPayment;   // ✓ 50,000 (ROI only)
    $recordData['payment_day_25_amount'] = $monthlyPayment;   // ✓ 50,000 (ROI only)
    break;
```

**Result in Database:**
```sql
roi_staking_management:
  principal_amount: 100000         ← Locked
  total_roi_amount: 150000         ← Total ROI
  payment_day_5_amount: 50000      ← 1/3 of ROI
  payment_day_15_amount: 50000     ← 1/3 of ROI
  payment_day_25_amount: 50000     ← 1/3 of ROI
  
Total distributed: 50,000 + 50,000 + 50,000 = 150,000 BMAN ✓
```

### CRON Execution Timeline

| Date | Day | CRON Run | Amount | Earning Wallet |
|------|-----|----------|--------|-----------------|
| Jul 5 | 5th | Monthly Distribution | +50,000 | 50,000 |
| Aug 15 | 15th | Monthly Distribution | +50,000 | 100,000 |
| Sep 25 | 25th | Monthly Distribution | +50,000 | 150,000 |

**Result:** User receives 150,000 BMAN (ROI only) across 3 months ✓

---

## 🔀 COMBO Plan Distribution

### Database Record Creation

**File:** `application/models/RoiStakingManagement_model.php` (line 52-62)

```php
case 'combo':
    $monthlyPayment = $totalROI / 4;         // = 150,000 / 4 = 37,500
    $maturityPayment = $totalROI / 4;        // = 150,000 / 4 = 37,500
    $recordData['regular_payment_amount'] = $monthlyPayment;
    $recordData['fixed_payment_amount'] = $maturityPayment;
    $recordData['payment_day_5_amount'] = $monthlyPayment;    // ✓ 37,500 (ROI)
    $recordData['payment_day_15_amount'] = $monthlyPayment;   // ✓ 37,500 (ROI)
    $recordData['payment_day_25_amount'] = $monthlyPayment;   // ✓ 37,500 (ROI)
    // fixed_payment_amount also = 37,500 (ROI)
    break;
```

**Result in Database:**
```sql
roi_staking_management:
  principal_amount: 100000         ← Locked
  total_roi_amount: 150000         ← Total ROI
  payment_day_5_amount: 37500      ← 1/4 of ROI
  payment_day_15_amount: 37500     ← 1/4 of ROI
  payment_day_25_amount: 37500     ← 1/4 of ROI
  fixed_payment_amount: 37500      ← 1/4 of ROI (maturity)
  
Total distributed: 37,500 × 4 = 150,000 BMAN ✓
```

### CRON Execution Timeline

| Date | Event | CRON | Amount | Earning Wallet |
|------|-------|------|--------|-----------------|
| Jul 5 | Day 5 | Monthly | +37,500 | 37,500 |
| Aug 15 | Day 15 | Monthly | +37,500 | 75,000 |
| Sep 25 | Day 25 | Monthly | +37,500 | 112,500 |
| Jul 9, 2027 | Maturity | Maturity | +37,500 | 150,000 |

**Result:** User receives 150,000 BMAN (ROI only) mixed schedule ✓

---

## 💾 Transaction Audit Trail

Every payment creates a record in `onchain_transactions`:

```sql
INSERT INTO onchain_transactions (
  staking_swap_orders_id,
  user_id,
  tx_type,              -- 'roi_monthly_5', 'roi_monthly_15', 'roi_monthly_25', 'roi_maturity_final'
  amount,               -- ✓ ROI portion only (37,500 or 50,000 or 150,000)
  token,                -- 'BMAN'
  from_wallet,          -- 'admin'
  to_wallet,            -- 'earning'
  status,               -- 'completed'
  tx_hash,              -- unique transaction ID
  created_at
);

Example FIXED Plan Maturity:
  tx_type: 'roi_maturity_final'
  amount: 150000        ← ✓ ROI only, NOT principal+ROI
  from_wallet: 'admin'
  to_wallet: 'earning'
  status: 'completed'
```

---

## 🔍 Verification Queries

### Check Principal vs ROI Split

```sql
-- Show all ROI records with breakdown
SELECT 
  id,
  user_id,
  plan_type,
  principal_amount,
  roi_rate_percent,
  total_roi_amount,
  fixed_payment_amount,
  payment_day_5_amount,
  payment_day_15_amount,
  payment_day_25_amount
FROM roi_staking_management
ORDER BY created_at DESC;

-- Expected:
-- principal_amount: 100000
-- total_roi_amount: 150000
-- fixed_payment_amount: 150000 (or 37500 for combo)
-- Each payment_day: 50000 (or 37500 for combo)
```

### Check Transaction Distribution

```sql
-- Show all ROI transactions
SELECT 
  tx_type,
  SUM(amount) as total_distributed,
  COUNT(*) as count
FROM onchain_transactions
WHERE tx_type LIKE 'roi%'
GROUP BY tx_type;

-- Expected for FIXED plan (one maturity):
-- roi_maturity_final: 150000 (one transaction)

-- Expected for REGULAR plan (three monthly):
-- roi_monthly_5: 50000
-- roi_monthly_15: 50000
-- roi_monthly_25: 50000
-- Total: 150000 ✓
```

### Check Earning Wallet Balance

```sql
-- Show earning wallet updates
SELECT 
  user_id,
  wallet_type,
  balance,
  updated_at
FROM wallet_ledger
WHERE wallet_type = 'earning'
ORDER BY updated_at DESC;

-- Expected after all distributions:
-- balance: 150000 (ROI only, principal not included)
```

---

## ⚠️ Critical Rules

✅ **DO THIS:**
- Store principal and ROI separately in database
- Only credit ROI amount to earning wallet
- Track each payment independently
- Record transaction for audit trail

❌ **DO NOT DO THIS:**
- Credit principal + ROI (would double-pay)
- Credit principal again to earning wallet
- Mix principal with ROI in payment amounts
- Lose track of what was already paid

---

## 🎯 Summary

| Scenario | Principal Stays | ROI Credited | Earning Wallet Receives |
|----------|-----------------|--------------|------------------------|
| FIXED | Locked in staking | At maturity | 150,000 BMAN (ROI only) |
| REGULAR | Locked in staking | Days 5,15,25 | 150,000 BMAN total (3 × 50,000) |
| COMBO | Locked in staking | Mixed | 150,000 BMAN total (3 × 37,500 + 37,500 maturity) |

**All amounts are ROI only. Principal never credited to earning wallet.**

