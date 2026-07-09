# Wallet History Not Showing — Diagnostic & Fix

**Problem:** Wallet shows balance (0.10 USDT) but "Wallet History" shows "No wallet transactions found."

---

## 🔍 Root Cause Analysis

Your wallet system has **3 separate data sources** for transactions:

| Table | Purpose | What It Shows |
|-------|---------|---------------|
| `wallet_ledger` | Main ledger | ALL transactions (deposits, credits, debits, commissions) |
| `wallet_deposits` | Deposit history | USDT deposits only (populated by DepositListener cron) |
| `onchain_transactions` | Blockchain sync | Raw on-chain transactions (populated by ChainSync cron) |

**The Issue:** Balance is in `wallet_ledger` but `wallet_deposits` is empty.

This happens when:
1. Someone manually credited the ledger
2. DepositListener cron never ran
3. Deposits detected but not yet credited

---

## 🧪 Diagnostic Steps

### Step 1: Check if deposits are being detected

Run in phpMyAdmin:

```sql
-- Check if onchain_transactions has your deposits
SELECT COUNT(*) as total, 
       SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) as confirmed,
       SUM(CASE WHEN confirmation_count >= 15 THEN 1 ELSE 0 END) as ready_to_credit
FROM onchain_transactions
WHERE to_address = LOWER('0xE837D1050a2b8c1c1cF741d1403D9806633eA1eA');
-- Replace address with user's custodial wallet
```

**Expected:** Should show detected transactions

### Step 2: Check if wallet_deposits is populated

```sql
-- Check if wallet_deposits has any entries
SELECT COUNT(*) as total, 
       SUM(CASE WHEN status='credited' THEN 1 ELSE 0 END) as credited,
       SUM(amount_usdt) as total_amount
FROM wallet_deposits
WHERE user_id = 1;  -- Replace with user_id
```

**If EMPTY (0 rows):**
→ Deposits detected but NOT credited yet. Run Step 3.

**If HAS ENTRIES:**
→ Check wallet_ledger. Run Step 4.

### Step 3: Manually trigger DepositListener

**Option A: Via HTTP (if cron token set):**

```bash
curl "http://localhost/credit-deposits-cron?token=YOUR_CRON_TOKEN"
```

**Option B: Via CLI:**

```bash
cd /path/to/admlm
php index.php depositcron run
```

**Expected response:**
```json
{
  "status": "success",
  "message": "Scan completed",
  "detected": 1,
  "credited": 1
}
```

### Step 4: Verify wallet_ledger is populated

```sql
-- Check if wallet_ledger has deposit credits
SELECT COUNT(*) as total,
       SUM(CASE WHEN reference_type='deposit' THEN 1 ELSE 0 END) as deposits,
       SUM(CASE WHEN reference_type='deposit' THEN amount ELSE 0 END) as total_deposit_usdt
FROM wallet_ledger
WHERE user_id = 1;
```

**If HAS ENTRIES:**
→ Ledger populated, but view not showing. Check Step 5.

---

## ✅ Quick Fix Steps

### If onchain_transactions is EMPTY:

**Problem:** Deposits not detected by ChainSync

**Fix:**

1. Run ChainSync cron:
```bash
curl "http://localhost/chain-sync-cron?token=YOUR_CRON_TOKEN"
```

2. Wait for on-chain confirmation (15+ blocks = ~60 sec on BSC)

3. Run DepositListener:
```bash
curl "http://localhost/credit-deposits-cron?token=YOUR_CRON_TOKEN"
```

---

### If onchain_transactions has data BUT wallet_deposits is EMPTY:

**Problem:** DepositListener cron not running

**Fix:**

1. Check cron token is set:
```php
// application/config/config.php
$config['cron_token'] = 'YOUR_SECRET_TOKEN';  // Make sure this is set!
```

2. Manually run DepositListener:
```bash
php index.php depositcron run
```

3. Verify wallet_deposits now has entries:
```sql
SELECT * FROM wallet_deposits WHERE user_id = 1 ORDER BY created_at DESC LIMIT 5;
```

---

### If wallet_deposits is EMPTY after running cron:

**Problem:** Deposits don't have 15+ confirmations yet

**Diagnosis:**
```sql
-- Check confirmation count
SELECT tx_hash, block_number, confirmation_count, status
FROM onchain_transactions
WHERE to_address = LOWER('0xYOUR_ADDRESS')
ORDER BY created_at DESC LIMIT 3;
```

**If confirmation_count < 15:**
→ Wait ~60 more seconds and try again

**If confirmation_count >= 15 but wallet_deposits still empty:**
→ Check application logs:
```bash
tail -f application/logs/log-*.php
```

---

### If wallet_deposits has entries BUT wallet_history shows nothing:

**Problem:** The view might not be showing `deposit_history`

**Fix:** Make sure wallet view includes:

```php
// In view_mywallet_management.php, find "USDT Deposit History" section
// Should display: $this->data['deposit_history']
```

---

## 🔧 Fix Wallet Display

The wallet view should show **2 separate sections**:

### Section 1: USDT Deposit History (from wallet_deposits + onchain)
```php
<?php 
  $deposits = $deposit_history ?? [];  // From Custodialwallet_model
  foreach ($deposits as $d) {
    // Show: amount, status (credited vs pending), confirmations, tx_hash
  }
?>
```

### Section 2: General Wallet History (from wallet_ledger)
```php
<?php 
  $transactions = $transactions ?? [];  // From Wallet_model
  foreach ($transactions as $t) {
    // Show: all types (deposits, credits, debits, commissions)
  }
?>
```

---

## 📋 Complete Checklist

- [ ] **Verify ChainSync ran:**
  ```sql
  SELECT COUNT(*) FROM onchain_transactions;
  ```

- [ ] **Verify DepositListener ran:**
  ```sql
  SELECT COUNT(*) FROM wallet_deposits;
  ```

- [ ] **Verify wallet_ledger has credits:**
  ```sql
  SELECT COUNT(*) FROM wallet_ledger 
  WHERE user_id = 1 AND reference_type = 'deposit';
  ```

- [ ] **Verify USDT deposit history section is in view**

- [ ] **Run manual button test:**
  - Click "Check On-chain Balance"
  - Should credit any pending deposits

- [ ] **Run "Credit Now" button test:**
  - Should credit pending + reload

---

## 🚀 Production Workflow

**When user deposits USDT:**

```
0s:   User sends 0.10 USDT
      ↓
60s:  ChainSync cron detects on-chain (15 blocks)
      Creates: onchain_transactions row
      ↓
60s:  DepositListener cron credits
      Creates: wallet_deposits row
      Creates: wallet_ledger row (credit entry)
      ↓
      User sees in history: 0.10 USDT | ✓ Credited
```

---

## 🆘 Still Not Working?

1. Check logs:
```bash
tail -100 application/logs/log-*.php
```

2. Verify cron settings:
```php
// application/config/config.php
echo $this->config->item('cron_token');
```

3. Run both crons manually in order:
```bash
php index.php chainsynccron run
php index.php depositcron run
```

4. Refresh wallet page in browser (Ctrl+F5)

---

**Time to fix:** ~5 minutes  
**Difficulty:** Easy  
**Risk:** None (read-only diagnosis)
