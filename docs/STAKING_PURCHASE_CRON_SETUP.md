# Staking Purchase Cron - Setup & Configuration

**Status:** ✅ **READY**  
**Endpoint:** `/staking-purchase-cron`  
**Timing:** Every 1 hour  
**Location:** Admin → Wallet → Cron Lab  

---

## 🎯 Overview

The Staking Purchase Cron automatically processes 3-step USDT ↔ BMAN swaps:

```
STEP 1: Gas Fee (admin sends 0.0008 BNB to user)
   ↓ [CRON DETECTS]
STEP 2: USDT Payment (user sends 100 USDT to admin)
   ↓ [CRON DETECTS]
STEP 3: BMAN Distribution (admin sends 1000 BMAN, distributed per coin_distribution_option 1-7)
   ↓ [CRON DETECTS & DISTRIBUTES]
Status: SWAP_COMPLETED ✅
```

**Cron handles all 3 steps.**

---

## 🔍 What the Cron Does

### Step 1: Detect Gas Fee Payment (BNB)
```
Scan: staking_swap_orders WHERE status='pending_gas_fee' AND cron_status_gas=0
Query: Etherscan API for BNB transfers TO user wallet
Action: 
  - Record in onchain_transactions (tx_type='gas_fee')
  - Set cron_status_gas = 1 ✅
  - Update status → 'pending_usdt'
```

### Step 2: Detect USDT Payment (USDT)
```
Scan: staking_swap_orders WHERE status='pending_usdt' AND cron_status_usdt=0
Query: Etherscan API for USDT transfers FROM user TO admin
Action:
  - Record in onchain_transactions (tx_type='deposit')
  - Set cron_status_usdt = 1 ✅
  - Update status → 'pending_bman'
```

### Step 3: Detect BMAN Transfer & Distribute
```
Scan: staking_swap_orders WHERE status='pending_bman' AND cron_status_bman=0
Query: Etherscan API for BMAN transfers FROM admin TO user
Action:
  - Record in onchain_transactions (tx_type='transfer')
  - Distribute 1000 BMAN to wallets per coin_distribution_option (1-7):
    
    Option 1: 1000 → Exchange (100%)
    Option 2: 900 → Exchange (90%), 100 → Bonus (10%)
    Option 3: 800 → Exchange (80%), 100 → Earning (10%), 100 → Bonus (10%)
    Option 4: 800 → Exchange (80%), 100 → Earning (10%), 100 → Staking (10%)
    Option 5: 900 → Exchange (90%), 100 → Earning (10%)
    Option 6: 900 → Exchange (90%), 100 → Staking (10%)
    Option 7: 700 → Exchange (70%), 100 → Earning (10%), 100 → Staking (10%), 100 → Bonus (10%)
  
  - Create user_stakes record (status='active')
  - Set cron_status_bman = 1 ✅
  - Update status → 'swap_completed'
```

---

## 🚀 Setup Instructions

### 1. Configure in Cron Lab (Admin Panel)

**Navigate to:**
```
Admin Panel → Wallet → Cron Lab
```

**You'll see the cron:**
```
Label: Staking Purchase Cron
Description: Process staking USDT→BMAN swaps (gas fee detection, payment detection, BMAN distribution)
Endpoint: /staking-purchase-cron?token=YOUR_CRON_TOKEN
Type: HTTP
```

### 2. Schedule the Cron

**Every 1 Hour** using one of these methods:

#### Option A: Linux/Unix Crontab
```bash
# Run every hour at :00 minute
0 * * * * curl -s "http://192.168.29.18:9000/staking-purchase-cron?token=YOUR_CRON_TOKEN"
```

#### Option B: Windows Task Scheduler
```batch
REM Run every 1 hour
schtasks /create /tn StakingPurchaseCron /tr "curl -s \"http://192.168.29.18:9000/staking-purchase-cron?token=YOUR_CRON_TOKEN\"" /sc hourly /mo 1
```

#### Option C: Control Panel Scheduler (if available)
```
Frequency: Hourly
Interval: Every 1 hour
URL: http://192.168.29.18:9000/staking-purchase-cron?token=YOUR_CRON_TOKEN
```

### 3. Set Cron Token

In `application/config/config.php`:
```php
$config['cron_token'] = 'YOUR_SECURE_SECRET_TOKEN_HERE';
```

**Make it unique and secure** - this prevents unauthorized cron execution.

---

## 🧪 Test the Cron

### Manual Test (via Cron Lab)

1. Go to Admin → Wallet → Cron Lab
2. Find "Staking Purchase Cron"
3. Click **"Run now"** button
4. Check output for success/errors

### Manual Test (via CLI)

```bash
php index.php stakingpurchasecron run
```

Response:
```json
{
  "status": "success",
  "message": "Staking purchase cron completed",
  "details": {
    "gas_fee": {
      "processed": 2,
      "failed": 0,
      "total": 2
    },
    "usdt_payment": {
      "processed": 1,
      "failed": 0,
      "total": 1
    },
    "bman_transfer": {
      "processed": 1,
      "failed": 0,
      "total": 1
    }
  },
  "ran_at": "2026-07-09 16:00:00"
}
```

### Manual Test (via HTTP)

```bash
curl "http://192.168.29.18:9000/staking-purchase-cron?token=YOUR_CRON_TOKEN"
```

---

## 📊 Monitor Cron Status

### Check Pending Orders

```sql
-- Orders waiting for gas fee detection
SELECT id, ref, user_id, status, cron_status_gas 
FROM staking_swap_orders
WHERE status='pending_gas_fee' AND cron_status_gas=0;

-- Orders waiting for USDT detection
SELECT id, ref, user_id, status, cron_status_usdt 
FROM staking_swap_orders
WHERE status='pending_usdt' AND cron_status_usdt=0;

-- Orders waiting for BMAN detection
SELECT id, ref, user_id, status, cron_status_bman 
FROM staking_swap_orders
WHERE status='pending_bman' AND cron_status_bman=0;

-- Completed orders
SELECT id, ref, user_id, status, cron_status_gas, cron_status_usdt, cron_status_bman
FROM staking_swap_orders
WHERE status='swap_completed' 
  AND cron_status_gas=1 
  AND cron_status_usdt=1 
  AND cron_status_bman=1;
```

### Check Transaction Records

```sql
-- View all transactions recorded by cron
SELECT tx_hash, tx_type, amount, user_id, created_at, status
FROM onchain_transactions
WHERE tx_type IN ('gas_fee', 'deposit', 'transfer')
  AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY created_at DESC;
```

### Check Logs

```bash
# View cron execution logs
tail -100 /path/to/application/logs/log-*.php | grep "STAKING_PURCHASE_CRON"
```

---

## 🔄 Retry Failed Steps

If a cron step fails (e.g., Etherscan API timeout), manually reset it:

```sql
-- Reset gas fee detection to retry
UPDATE staking_swap_orders
SET cron_status_gas = 0
WHERE id = 42 AND status = 'pending_gas_fee';

-- Reset USDT detection to retry
UPDATE staking_swap_orders
SET cron_status_usdt = 0
WHERE id = 42 AND status = 'pending_usdt';

-- Reset BMAN detection to retry
UPDATE staking_swap_orders
SET cron_status_bman = 0
WHERE id = 42 AND status = 'pending_bman';
```

Then the cron will re-detect on the next run.

---

## 🐛 Troubleshooting

### Issue: Cron shows "processed: 0"

**Cause:** No pending orders or all orders already processed

**Solution:**
```sql
-- Check if there are any pending orders
SELECT COUNT(*) FROM staking_swap_orders 
WHERE status IN ('pending_gas_fee', 'pending_usdt', 'pending_bman')
  AND cron_status_gas + cron_status_usdt + cron_status_bman < 3;
```

### Issue: Cron shows "failed: X"

**Cause:** Etherscan API timeout, network issue, or transaction not indexed yet

**Solution:**
1. Wait 30-60 seconds (Etherscan indexing delay)
2. Run cron again manually
3. Check Etherscan directly for the transaction
4. If TX exists but not detected, reset cron_status to 0 and retry

### Issue: BMAN not distributed to correct wallet

**Cause:** coin_distribution_option not set correctly during purchase

**Solution:**
```sql
-- Check the option for the order
SELECT id, coin_distribution_option, bman_amount 
FROM staking_swap_orders 
WHERE id = 42;

-- If wrong, check purchase request to see which option was sent
-- Update if needed:
UPDATE staking_swap_orders
SET coin_distribution_option = 3  -- Correct option
WHERE id = 42;

-- Reset BMAN detection to retry distribution
UPDATE staking_swap_orders
SET cron_status_bman = 0
WHERE id = 42 AND status = 'pending_bman';
```

Then run cron again.

---

## 📋 Database Schema Used

### staking_swap_orders
```sql
id                          INT PRIMARY KEY
ref                         VARCHAR(32) UNIQUE
user_id                     INT
user_address                VARCHAR(120)  -- user's wallet
admin_address               VARCHAR(120)  -- admin's hot wallet
usdt_amount                 DECIMAL(30,8) -- 100 USDT
bman_amount                 DECIMAL(30,8) -- 1000 BMAN
coin_distribution_option    INT           -- 1-7 wallet split
status                      VARCHAR(24)   -- pending_gas_fee/usdt/bman → swap_completed
cron_status_gas             TINYINT       -- 0=pending, 1=completed
cron_status_usdt            TINYINT       -- 0=pending, 1=completed
cron_status_bman            TINYINT       -- 0=pending, 1=completed
gas_tx_hash                 VARCHAR(120)  -- BNB TX hash from step 1
usdt_tx_hash                VARCHAR(120)  -- USDT TX hash from step 2
bman_tx_hash                VARCHAR(120)  -- BMAN TX hash from step 3
```

### onchain_transactions
```sql
id                          BIGINT PRIMARY KEY
tx_hash                     VARCHAR(120)  -- blockchain TX hash
tx_type                     VARCHAR(24)   -- 'gas_fee', 'deposit', 'transfer'
from_address                VARCHAR(120)  -- sender
to_address                  VARCHAR(120)  -- recipient
amount                      DECIMAL(30,8) -- amount in wei/token units
user_id                     INT           -- user linked to TX
status                      VARCHAR(24)   -- 'processing'
block_number                BIGINT        -- blockchain block
created_at                  DATETIME      -- when recorded
```

---

## 🔗 Related URLs

```
Admin Cron Lab:     http://192.168.29.18:9000/admin/wallet/cron-lab
Run Cron HTTP:      http://192.168.29.18:9000/staking-purchase-cron?token=YOUR_TOKEN
Run Cron CLI:       php index.php stakingpurchasecron run
User Purchase Page: http://192.168.29.18:9000/user/lending/swap_purchase
Portfolio:          http://192.168.29.18:9000/user/lending (shows "PROCESSING" until all 3 steps complete)
```

---

## 📊 Monitoring Dashboard (Cron Lab)

The Cron Lab shows:
- **Gas Fee Transactions:** detected BNB payments
- **USDT Transactions:** detected USDT transfers
- **BMAN Transactions:** detected BMAN transfers
- **Wallet Balances:** exchange, earning, staking, bonus wallets

---

## ✅ Production Checklist

- [ ] Cron token set in `config.php`
- [ ] Cron scheduled to run every 1 hour
- [ ] Tested manually via Cron Lab "Run now" button
- [ ] Tested manually via CLI: `php index.php stakingpurchasecron run`
- [ ] Confirmed Etherscan API key is configured
- [ ] Confirmed RPC endpoint is accessible
- [ ] Logs being captured properly
- [ ] Monitoring setup (check logs hourly)
- [ ] Retry procedure documented for team

---

## 📞 Support

If cron isn't working:

1. **Check logs:**
   ```bash
   tail -100 application/logs/log-*.php | grep "STAKING_PURCHASE_CRON"
   ```

2. **Run manually:**
   ```bash
   php index.php stakingpurchasecron run
   ```

3. **Verify config:**
   - Etherscan API key set in token_settings
   - Cron token set in config.php
   - RPC endpoint accessible

4. **Check Etherscan directly:**
   - Log into Etherscan
   - Search for TX hash
   - Confirm it's indexed and shows correct from/to addresses

---

**Status:** ✅ Production Ready
