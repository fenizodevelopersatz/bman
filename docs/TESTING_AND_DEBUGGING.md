# Testing & Debugging — Instant Wallet Deposit

Your issue: **Wallet balance updates but history shows nothing**

This guide will help you identify the problem and fix it.

---

## 🔍 Step 1: Check Cron Token Configuration

### Check if cron_token is set:

```php
// Open: application/config/config.php
// Look for this line:
$config['cron_token'] = 'YOUR_SECRET_TOKEN_HERE';
```

**If NOT found, add it:**
```php
// Add to config.php (around line 500)
$config['cron_token'] = 'bman_cron_secret_2026';  // Change to something secure
```

---

## 🌐 Step 2: Test Cron URLs

### Test ChainSync Cron (verify pending txs)

**HTTP:** 
```
http://192.168.29.18:9000/chain-sync-cron?token=bman_cron_secret_2026
```

**Expected Response:**
```json
{
  "status": "success",
  "message": "verified 0 transactions",
  "duration_ms": 234
}
```

### Test Deposit Cron (detect deposits)

**HTTP:**
```
http://192.168.29.18:9000/credit-deposits-cron?token=bman_cron_secret_2026
```

**Expected Response:**
```json
{
  "status": "success",
  "message": "Scan completed",
  "detected": 2,
  "credited": 2
}
```

### Test Instant Credit Endpoint (user-facing)

**HTTP (POST):**
```
http://192.168.29.18:9000/user/instant-credit-deposits
```

**Expected Response:**
```json
{
  "success": true,
  "message": "✓ 1 deposits credited",
  "credited_count": 1,
  "credited_amount": 0.1,
  "new_balance_usdt": 0.1
}
```

---

## 🗄️ Step 3: Check Database Status

### Check if on-chain transactions exist:

```sql
-- See all deposits to user's wallet
SELECT * FROM onchain_transactions 
WHERE to_address = '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb'
ORDER BY created_at DESC LIMIT 5;
```

**Look for:**
- `status`: should be "confirmed"
- `confirmation_count`: should be >= 15
- `value`: deposit amount
- `tx_hash`: should be populated

### Check if deposits were credited:

```sql
-- See credited deposits
SELECT * FROM wallet_deposits 
WHERE user_id = 1 
ORDER BY created_at DESC LIMIT 5;
```

**Look for:**
- `status`: should be "credited"
- `amount_usdt`: deposit amount
- `tx_hash`: matches onchain tx

### Check wallet ledger:

```sql
-- See wallet ledger entries
SELECT * FROM wallet_ledger 
WHERE user_id = 1 AND reference_type = 'deposit'
ORDER BY created_at DESC LIMIT 5;
```

**Look for:**
- `amount`: should be credited to USDT wallet
- `status`: should be "completed"
- `wallet`: should be "usdt"

---

## 🧪 Step 4: Manual Testing

### Test A: Send USDT Deposit

1. **Send USDT** from your MetaMask to custodial wallet address
   - You can find this at: `/user/wallet` (top section "Deposit Wallet")
   - Custodial address: `0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb` (example)

2. **Check BscScan**
   - Go to: https://bscscan.com/address/0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb
   - Wait for TX to appear and confirm

3. **Wait for 15 blocks**
   - ~45-60 seconds on BSC (3-4 sec per block)
   - Once 15 blocks pass, your deposit is "safe"

### Test B: Check Pending Deposits in UI

1. **Go to:** `http://192.168.29.18:9000/user/wallet`

2. **Look for:**
   - ⚠️ Blue alert: "X Deposits Confirmed On-Chain" 
   - Button: "✓ Credit Now"

**If alert NOT showing:**
→ Jump to "Issue: Alert Not Showing" below

### Test C: Click "Credit Now"

1. **Click the blue "✓ Credit Now" button**
2. **Wait for processing** (shows spinner)
3. **See success message:**
   ```
   ✓ Success!
   1 deposit(s) credited
   New balance: 0.10 USDT
   ```

4. **Check deposit history**
   - Should show in "USDT Deposit History"
   - Status: "✓ Credited" (green badge)
   - Amount: "0.10 USDT"

---

## 🐛 Troubleshooting

### **Issue: Cron URLs return 404**

**Causes:**
- Route not configured
- Wrong token
- URL rewriting issue

**Fix:**
1. Check routes.php has these lines:
   ```php
   $route['credit-deposits-cron'] = 'Depositcron/run';
   $route['chain-sync-cron'] = 'Chainsynccron/run';
   ```

2. Check config.php has:
   ```php
   $config['cron_token'] = 'bman_cron_secret_2026';
   ```

3. Try CLI instead:
   ```bash
   cd /path/to/admlm
   php index.php depositcron run
   php index.php chainsynccron run
   ```

---

### **Issue: Cron URLs return "Unauthorized"**

**Causes:**
- Token mismatch
- Token not set in config

**Fix:**
1. Get token from `application/config/config.php`:
   ```php
   echo $this->config->item('cron_token');  // From controller
   ```

2. Use correct token in URL:
   ```
   ?token=THE_ACTUAL_TOKEN
   ```

---

### **Issue: Alert Not Showing**

**Causes:**
- No pending deposits (deposit hasn't reached 15 blocks yet)
- Deposit not detected by ChainSync
- Wrong wallet address

**Debug:**
1. Check BscScan for your TX:
   - https://bscscan.com/address/YOUR_CUSTODIAL_ADDRESS
   - Wait for TX to appear with "✓ Confirmed"

2. Run ChainSync cron manually:
   ```bash
   php index.php chainsynccron run
   ```

3. Check database:
   ```sql
   SELECT * FROM onchain_transactions 
   WHERE to_address = LOWER('0x...YOUR_ADDRESS')
   ORDER BY created_at DESC LIMIT 1;
   ```

   Should show:
   - `status`: "confirmed"
   - `confirmation_count`: >= 15

---

### **Issue: Deposit Not Crediting**

**Causes:**
- DepositListener not running
- Wallet address mismatch
- Deposit doesn't have 15 confirmations yet

**Debug:**
1. Check DepositListener cron:
   ```bash
   php index.php depositcron run
   ```

2. Check database for wallet address:
   ```sql
   SELECT wallet_address FROM user_wallet 
   WHERE user_id = 1;
   ```

3. Verify on-chain status:
   ```sql
   SELECT * FROM onchain_transactions 
   WHERE to_address = LOWER('0xYOUR_ADDRESS')
   AND confirmation_count >= 15;
   ```

---

### **Issue: Balance Updated But History Empty**

**This is your current issue!**

**Causes:**
- Wallet ledger updated (balance shows correct)
- But wallet_deposits table not populated
- Or deposit_history query not returning results

**Debug:**
1. Check if balance updated:
   ```sql
   SELECT * FROM wallet_ledger 
   WHERE user_id = 1 AND wallet = 'usdt'
   ORDER BY created_at DESC LIMIT 1;
   ```

   Should show deposit credit

2. Check if wallet_deposits exists:
   ```sql
   SELECT * FROM wallet_deposits 
   WHERE user_id = 1 
   ORDER BY created_at DESC LIMIT 1;
   ```

   Should show the deposit entry

3. If wallet_deposits is EMPTY but ledger is filled:
   - **Solution:** Run DepositListener cron
     ```bash
     php index.php depositcron run
     ```
   - **Or:** Manually insert:
     ```sql
     INSERT INTO wallet_deposits (
       user_id, token, amount_usdt, tx_hash, 
       status, credited_at, created_at
     ) VALUES (
       1, 'USDT', 0.10, '0xABC123...', 
       'credited', NOW(), NOW()
     );
     ```

---

## 🎯 Quick Testing Checklist

- [ ] **Config:**
  - [ ] cron_token set in config.php
  - [ ] Routes configured in routes.php

- [ ] **Crons Working:**
  - [ ] Test: `/chain-sync-cron?token=...` returns 200
  - [ ] Test: `/credit-deposits-cron?token=...` returns 200
  - [ ] CLI: `php index.php depositcron run` works

- [ ] **Database:**
  - [ ] onchain_transactions has deposits
  - [ ] wallet_deposits has deposits
  - [ ] wallet_ledger has credits

- [ ] **UI:**
  - [ ] Pending alert shows when 15+ blocks
  - [ ] "Credit Now" button works
  - [ ] Success message appears
  - [ ] Deposit appears in history

---

## 🚀 The Workflow (Complete)

```
User sends 0.10 USDT
    ↓
Wait ~60 seconds (15 block confirmations)
    ↓
ChainSync Cron detects on-chain (onchain_transactions)
    ↓
DepositListener Cron credits wallet_ledger
    ↓
USDT balance updates to 0.10
    ↓
Frontend shows pending alert + "Credit Now" button
    ↓
User clicks "Credit Now"
    ↓
instant_credit_deposits endpoint processes
    ↓
wallet_deposits table populated
    ↓
Deposit appears in history with "✓ Credited"
```

---

## 📞 Common Solutions

### **Solution 1: Manually Run Crons**

```bash
# Run ChainSync
php index.php chainsynccron run

# Run DepositListener
php index.php depositcron run

# Check results
curl http://192.168.29.18:9000/user/wallet
```

### **Solution 2: Populate Missing wallet_deposits**

If wallet_ledger has credits but wallet_deposits is empty:

```sql
-- Find deposits in onchain_transactions not in wallet_deposits
INSERT INTO wallet_deposits (
  user_id, token, amount_usdt, tx_hash, 
  status, wallet_address, network, 
  credited_at, created_at
)
SELECT 
  1, 'USDT', value, tx_hash, 
  'credited', to_address, 'bsc', 
  NOW(), created_at
FROM onchain_transactions
WHERE to_address = '0x...'
AND status = 'confirmed'
AND confirmation_count >= 15
AND tx_hash NOT IN (SELECT tx_hash FROM wallet_deposits);
```

### **Solution 3: Check Frontend Changes**

Verify wallet view was updated:
```php
// Check this is in view_mywallet_management.php

// Around line 1910: Should have creditPendingDeposits() function
// Around line 1930-1950: Should have pending alert
// Around line 1960-2000: Should have enhanced deposit display
```

---

## ✅ Verification

After fixing, verify everything works:

1. **Send test deposit** (0.01 USDT)
2. **Wait 60 seconds**
3. **Check:** Alert appears on `/user/wallet` ✓
4. **Click:** "Credit Now" button ✓
5. **Verify:** Balance updates ✓
6. **Check:** Deposit in history with "✓ Credited" ✓

---

## 📋 Summary

| Step | Status | Action |
|------|--------|--------|
| Cron token configured | ❓ | Check config.php |
| Routes configured | ✓ | Already done |
| Crons running | ❓ | Test URLs or run CLI |
| Deposits detected | ❓ | Check onchain_transactions table |
| Deposits credited | ? | Check wallet_deposits table |
| UI updated | ✓ | Already integrated |
| History showing | ❌ | Need to populate wallet_deposits |

