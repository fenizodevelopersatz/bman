# Your Issue: Balance Updates But History Empty — QUICK FIX

**Your Problem:**
- ✅ Wallet balance increases (ledger works)
- ❌ Wallet history shows nothing (wallet_deposits empty)
- ❌ Pending alert doesn't appear

**Root Cause:** DepositListener cron is not running or not detecting deposits.

---

## 🚨 IMMEDIATE ACTION (5 minutes)

### Step 1: Check If Cron Token Exists

**Open:** `application/config/config.php`

**Search for:**
```php
$config['cron_token'] = 'YOUR_SECRET_TOKEN';
```

**If NOT found, ADD it:**
```php
// Add this line (around line 500):
$config['cron_token'] = 'my_secret_bman_2026';
```

### Step 2: Test Deposit Cron Manually

**Open terminal/SSH and run:**
```bash
cd /path/to/admlm
php index.php depositcron run
```

**Should show:**
```json
{
  "status": "success",
  "message": "Scan completed",
  "detected": 1,
  "credited": 1
}
```

**If it says "No deposits found":**
- Deposit hasn't reached 15 confirmations yet
- OR wallet address is not set up correctly

### Step 3: Test Chain Sync Cron

**Run:**
```bash
php index.php chainsynccron run
```

**Should show:**
```json
{
  "status": "success",
  "verified": 1
}
```

---

## 🔧 Step 4: Diagnose The Issue

### Check Database

**Open your database client and run:**

```sql
-- 1. Do you have on-chain transactions?
SELECT COUNT(*) FROM onchain_transactions 
WHERE to_address LIKE '%742d35%';  -- Replace with your custodial address

-- 2. Do you have wallet_deposits?
SELECT COUNT(*) FROM wallet_deposits 
WHERE user_id = 1;

-- 3. Do you have wallet_ledger credits?
SELECT * FROM wallet_ledger 
WHERE user_id = 1 AND wallet = 'usdt'
ORDER BY created_at DESC LIMIT 3;
```

### Diagnosis:

**If onchain_transactions has deposits but wallet_deposits is EMPTY:**
```
→ DepositListener cron is not running
→ Solution: Run manually or schedule it (see below)
```

**If wallet_ledger has credits but wallet_deposits is EMPTY:**
```
→ Someone manually credited the ledger
→ Solution: Populate wallet_deposits from onchain_transactions
```

**If wallet_ledger is EMPTY:**
```
→ Deposits never reached the system
→ Solution: Manually run DepositListener cron
```

---

## ✅ Step 5: Populate Missing wallet_deposits

**If wallet_ledger has credits but wallet_deposits is empty:**

```sql
-- Populate wallet_deposits from onchain_transactions
INSERT INTO wallet_deposits (
  user_id, token, amount_usdt, tx_hash, 
  status, wallet_address, network, 
  credited_at, created_at
)
SELECT 
  1 as user_id,                              -- Change to actual user_id
  'USDT' as token,
  value as amount_usdt,
  tx_hash,
  'credited' as status,
  to_address as wallet_address,
  'bsc' as network,
  NOW() as credited_at,
  created_at
FROM onchain_transactions
WHERE to_address = '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb'  -- Your custodial address
AND status = 'confirmed'
AND tx_hash NOT IN (SELECT DISTINCT tx_hash FROM wallet_deposits WHERE tx_hash IS NOT NULL);
```

---

## 🔄 Step 6: Schedule the Cron Permanently

### Option A: Linux Crontab (Automatic)

```bash
# Edit crontab
crontab -e

# Add this line (runs every 5 minutes):
*/5 * * * * /usr/bin/php /path/to/admlm/index.php depositcron run >> /tmp/depositcron.log 2>&1

# Add this line (ChainSync every 5 minutes):
*/5 * * * * /usr/bin/php /path/to/admlm/index.php chainsynccron run >> /tmp/chainsynccron.log 2>&1

# Save with Ctrl+X, then Y
```

### Option B: HTTP (Browser-triggered)

```
Every 5 minutes, visit this URL:
http://192.168.29.18:9000/credit-deposits-cron?token=my_secret_bman_2026

Every 5 minutes, visit:
http://192.168.29.18:9000/chain-sync-cron?token=my_secret_bman_2026
```

**Or use an online scheduler like IFTTT or Uptime Robot to trigger these URLs**

---

## 🧪 Step 7: Test Complete Workflow

### Test Sequence:

1. **Send 0.01 USDT** to custodial wallet
2. **Wait 60 seconds** (15 block confirmations)
3. **Run crons manually:**
   ```bash
   php index.php chainsynccron run
   php index.php depositcron run
   ```
4. **Refresh `/user/wallet`**
   - Should see: **Blue alert "X Deposits Confirmed On-Chain"**
   - Should see: **"✓ Credit Now" button**
5. **Click "Credit Now"**
   - Should see: **"✓ Success! New balance: 0.01"**
6. **Check history**
   - Should show: **Deposit with "✓ Credited" badge**

---

## 📋 Your Checklist

- [ ] **Config:** Added cron_token to config.php
- [ ] **Test:** Ran `php index.php depositcron run` successfully
- [ ] **Test:** Ran `php index.php chainsynccron run` successfully
- [ ] **Database:** Verified onchain_transactions has deposits
- [ ] **Database:** Populated wallet_deposits (if needed)
- [ ] **Schedule:** Set up permanent cron (Linux or HTTP)
- [ ] **UI Test:** Blue alert appears after 60 sec
- [ ] **UI Test:** "Credit Now" button works
- [ ] **Verify:** Deposit appears in history with "✓ Credited"

---

## 🎯 Expected Result After Fix

```
BEFORE:
- Balance: 0.10 USDT (updated)
- History: [empty]
- Alert: [none]

AFTER:
- Balance: 0.10 USDT ✓
- History: 0.10 USDT | ✓ Credited ✓
- Alert: [blue pending alert after 60 sec] ✓
- Credit Button: [works and credits immediately] ✓
```

---

## 🆘 Still Having Issues?

**If after these steps things still don't work:**

1. Check logs:
   ```bash
   tail -f /path/to/admlm/application/logs/log-*.php
   ```

2. Check database connectivity:
   ```bash
   mysql -u user -p database -e "SELECT COUNT(*) FROM onchain_transactions;"
   ```

3. Check Web3bman configuration:
   ```php
   // In your controller
   $this->load->library('web3bman');
   echo $this->web3bman->rpcUrl();  // Should show RPC URL
   ```

4. **Read:** `docs/TESTING_AND_DEBUGGING.md` for detailed troubleshooting

---

**Time to fix:** ~5-10 minutes  
**Difficulty:** Easy  
**Risk:** None (just running crons)

Go ahead and test! Let me know if you hit any errors.

