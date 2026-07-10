# Granular Cron Implementation Guide

**Status:** ✅ **READY FOR DEPLOYMENT**  
**Version:** 2.0 - Granular per-transaction cron status  
**Date:** 2026-07-09  
**Updated by:** Claude Code  

---

## 🎯 Overview

The Staking Purchase Cron now implements **granular, independent transaction tracking** with 8 separate cron_status fields. This enables:

✅ **No Repeated Execution** - Each step checked before processing  
✅ **Granular Retry** - Reset only failed steps, not entire orders  
✅ **Independent Tracking** - 8 separate cron_status fields  
✅ **Complete Audit Trail** - Each TX hash and status visible  
✅ **Easy Debugging** - Know exactly which step failed  
✅ **Flexible Recovery** - Retry failed steps without re-running completed ones  

---

## 📋 8 Cron Status Fields

Every order in `staking_swap_orders` now tracks these independent steps:

```
✅ Step 1: gas_cron_status          → 0=pending, 1=completed (gas_tx_hash)
✅ Step 2: usdt_cron_status         → 0=pending, 1=completed (usdt_tx_hash)
✅ Step 3: bonus_cron_status        → 0=pending, 1=completed (bonus_tx_hash)
✅ Step 4: bman_exchange_cron_status → 0=pending, 1=completed (bman_exchange_tx_hash)
✅ Step 5: bman_earning_cron_status  → 0=pending, 1=completed (bman_earning_tx_hash)
✅ Step 6: bman_staking_cron_status  → 0=pending, 1=completed (bman_staking_tx_hash)
✅ Step 7: bman_bonus_cron_status    → 0=pending, 1=completed (bman_bonus_tx_hash)
```

---

## 🔍 Cron Processing Logic

### **Main Flow:**

```
FOR EACH order in staking_swap_orders WHERE any_cron_status = 0:

  STEP 1: Gas Fee (always required)
    IF gas_cron_status = 0:
      Detect BNB transfer to user
      IF found:
        Set gas_tx_hash = TX hash
        Set gas_cron_status = 1 ✅
        Update status → 'pending_usdt'

  STEP 2: USDT Payment (always required)
    IF usdt_cron_status = 0:
      Detect USDT transfer from user to admin
      IF found:
        Set usdt_tx_hash = TX hash
        Set usdt_cron_status = 1 ✅
        Update status → 'pending_bman'

  STEP 3: Bonus BMAN (always tracked)
    IF bonus_cron_status = 0:
      Detect bonus BMAN transfer (if bonus_bman > 0)
      IF found OR no bonus configured:
        Set bonus_tx_hash = TX hash (if found)
        Set bonus_cron_status = 1 ✅

  STEP 4-7: BMAN Wallet Distribution (only in status='pending_bman' or 'swap_completed')
    
    STEP 4: Exchange Wallet (ALWAYS required in all 7 options)
      IF bman_exchange_cron_status = 0:
        Detect BMAN transfer from admin to user
        IF found:
          Set bman_exchange_tx_hash = TX hash
          Set bman_exchange_cron_status = 1 ✅
          Update wallet_ledger exchange = exchange + (BMAN * option%)
    
    STEP 5: Earning Wallet (optional - options 3, 5, 7 only)
      IF shouldProcessEarning(option) = true:
        IF bman_earning_cron_status = 0:
          Set bman_earning_tx_hash = bman_exchange_tx_hash (same TX)
          Set bman_earning_cron_status = 1 ✅
          Update wallet_ledger earning = earning + (BMAN * option%)
      ELSE:
        Set bman_earning_cron_status = 1 (mark as not needed)
    
    STEP 6: Staking Wallet (optional - options 4, 6, 7 only)
      IF shouldProcessStaking(option) = true:
        IF bman_staking_cron_status = 0:
          Set bman_staking_tx_hash = bman_exchange_tx_hash (same TX)
          Set bman_staking_cron_status = 1 ✅
          Update wallet_ledger staking = staking + (BMAN * option%)
      ELSE:
        Set bman_staking_cron_status = 1 (mark as not needed)
    
    STEP 7: Bonus Wallet (optional - options 2, 3, 7 only)
      IF shouldProcessBonusWallet(option) = true:
        IF bman_bonus_cron_status = 0:
          Set bman_bonus_tx_hash = bman_exchange_tx_hash (same TX)
          Set bman_bonus_cron_status = 1 ✅
          Update wallet_ledger bonus = bonus + (BMAN * option%)
      ELSE:
        Set bman_bonus_cron_status = 1 (mark as not needed)

  FINAL: Check If Order Complete
    IF all required cron_status = 1:
      Create user_stakes record (status='active')
      Update order status → 'swap_completed'
      Order now appears in user portfolio ✓
    ELSE:
      Wait for next cron run
```

---

## 📊 7 Distribution Options

Each option determines which wallets receive BMAN:

```
Option 1: EXCHANGE ONLY
├─ Exchange: 100% (1000 BMAN)
├─ Earning:  0%   (NOT USED)
├─ Staking:  0%   (NOT USED)
└─ Bonus:    0%   (NOT USED)

Option 2: EXCHANGE + BONUS
├─ Exchange: 90%  (900 BMAN)
├─ Earning:  0%   (NOT USED)
├─ Staking:  0%   (NOT USED)
└─ Bonus:    10%  (100 BMAN)

Option 3: EXCHANGE + EARNING + BONUS
├─ Exchange: 80%  (800 BMAN)
├─ Earning:  10%  (100 BMAN)
├─ Staking:  0%   (NOT USED)
└─ Bonus:    10%  (100 BMAN)

Option 4: EXCHANGE + EARNING + STAKING
├─ Exchange: 80%  (800 BMAN)
├─ Earning:  10%  (100 BMAN)
├─ Staking:  10%  (100 BMAN)
└─ Bonus:    0%   (NOT USED)

Option 5: EXCHANGE + EARNING
├─ Exchange: 90%  (900 BMAN)
├─ Earning:  10%  (100 BMAN)
├─ Staking:  0%   (NOT USED)
└─ Bonus:    0%   (NOT USED)

Option 6: EXCHANGE + STAKING
├─ Exchange: 90%  (900 BMAN)
├─ Earning:  0%   (NOT USED)
├─ Staking:  10%  (100 BMAN)
└─ Bonus:    0%   (NOT USED)

Option 7: FULLY SPLIT (ALL WALLETS)
├─ Exchange: 70%  (700 BMAN)
├─ Earning:  10%  (100 BMAN)
├─ Staking:  10%  (100 BMAN)
└─ Bonus:    10%  (100 BMAN)
```

---

## 🔧 Key Implementation Details

### **StakingPurchasecron.php Methods**

#### `_getPendingOrders()`
Queries orders with ANY pending cron_status (= 0). Excludes fully-completed orders.

```sql
WHERE status IN ('pending_gas_fee', 'pending_usdt', 'pending_bman')
   OR (status = 'swap_completed' AND ANY cron_status = 0)
```

#### `_processOrderSteps($order, &$summary)`
Main loop that processes each pending step for one order.

#### `_detectAndRecordGasFee($order)` → Gas Fee Detection
- Queries Etherscan for BNB transfers TO user (0.0005 - 0.01 BNB)
- Records TX in onchain_transactions (tx_type='gas_fee')
- Sets gas_tx_hash, gas_cron_status=1
- Updates status → 'pending_usdt'

#### `_detectAndRecordUsdtPayment($order)` → USDT Detection
- Queries Etherscan for USDT transfers FROM user TO admin
- Records TX in onchain_transactions (tx_type='deposit')
- Sets usdt_tx_hash, usdt_cron_status=1
- Updates status → 'pending_bman'

#### `_detectAndRecordBonusBman($order)` → Bonus Detection
- Queries Etherscan for bonus BMAN transfers FROM admin TO user
- Records TX in onchain_transactions (tx_type='transfer')
- Sets bonus_tx_hash, bonus_cron_status=1
- If no bonus configured (bonus_bman=0), still marks cron_status=1

#### `_detectAndDistributeBmanToExchange($order)` → Exchange Wallet
- Detects BMAN transfer from admin to user
- Distributes to exchange wallet per option percentage
- Sets bman_exchange_tx_hash, bman_exchange_cron_status=1

#### `_detectAndDistributeBmanToEarning($order)` → Earning Wallet (Optional)
- Uses same TX hash as exchange (one bulk transfer)
- Only processes if option uses earning wallet (3, 5, 7)
- Distributes to earning wallet per option percentage
- Sets bman_earning_tx_hash, bman_earning_cron_status=1

#### `_detectAndDistributeBmanToStaking($order)` → Staking Wallet (Optional)
- Uses same TX hash as exchange
- Only processes if option uses staking wallet (4, 6, 7)
- Distributes to staking wallet per option percentage
- Sets bman_staking_tx_hash, bman_staking_cron_status=1

#### `_detectAndDistributeBmanToBonus($order)` → Bonus Wallet (Optional)
- Uses same TX hash as exchange
- Only processes if option uses bonus wallet (2, 3, 7)
- Distributes to bonus wallet per option percentage
- Sets bman_bonus_tx_hash, bman_bonus_cron_status=1

#### `_checkAndCompleteOrder($order)`
Checks if all required steps are complete:
- Always checks: gas, usdt, bonus, exchange
- Conditionally checks: earning, staking, bonus wallet (based on option)
- If complete: creates user_stakes record, updates status → 'swap_completed'

#### Helper Functions
- `_shouldProcessEarning($option)` - returns true for options 3, 5, 7
- `_shouldProcessStaking($option)` - returns true for options 4, 6, 7
- `_shouldProcessBonusWallet($option)` - returns true for options 2, 3, 7
- `_calculateBmanForWallet($order, $wallet_type)` - returns % of BMAN for wallet
- `_updateWalletLedger($order, $wallet_type)` - updates wallet_ledger balance
- `_getExchangeTxHash($order)` - retrieves exchange TX hash for wallet distribution

---

## 🔄 Independent Retry System

### **Reset a Failed Step**

Admin can manually reset any cron_status to 0 to retry just that step:

```sql
-- Retry gas fee detection
UPDATE staking_swap_orders 
SET gas_cron_status = 0 
WHERE id = 42;

-- Retry USDT detection
UPDATE staking_swap_orders 
SET usdt_cron_status = 0 
WHERE id = 42;

-- Retry bonus BMAN detection
UPDATE staking_swap_orders 
SET bonus_cron_status = 0 
WHERE id = 42;

-- Retry exchange wallet distribution
UPDATE staking_swap_orders 
SET bman_exchange_cron_status = 0 
WHERE id = 42;

-- Retry earning wallet distribution
UPDATE staking_swap_orders 
SET bman_earning_cron_status = 0 
WHERE id = 42;

-- Retry staking wallet distribution
UPDATE staking_swap_orders 
SET bman_staking_cron_status = 0 
WHERE id = 42;

-- Retry bonus wallet distribution
UPDATE staking_swap_orders 
SET bman_bonus_cron_status = 0 
WHERE id = 42;
```

Next cron run will process ONLY the reset steps, skipping already-completed ones ✓

---

## 📊 Finding Orders by Status

```sql
-- Orders with ANY pending step
SELECT id, coin_distribution_option, status,
       gas_cron_status, usdt_cron_status, bonus_cron_status,
       bman_exchange_cron_status, bman_earning_cron_status,
       bman_staking_cron_status, bman_bonus_cron_status
FROM staking_swap_orders
WHERE gas_cron_status = 0
   OR usdt_cron_status = 0
   OR bonus_cron_status = 0
   OR bman_exchange_cron_status = 0
   OR bman_earning_cron_status = 0
   OR bman_staking_cron_status = 0
   OR bman_bonus_cron_status = 0
ORDER BY updated_at ASC
LIMIT 20;

-- Orders waiting for gas fee
SELECT id, user_id, status FROM staking_swap_orders
WHERE gas_cron_status = 0 AND status = 'pending_gas_fee';

-- Orders waiting for USDT
SELECT id, user_id, status FROM staking_swap_orders
WHERE usdt_cron_status = 0 AND status = 'pending_usdt';

-- Orders waiting for BMAN exchange wallet
SELECT id, user_id, status FROM staking_swap_orders
WHERE bman_exchange_cron_status = 0 AND status = 'pending_bman';

-- FULLY COMPLETED orders
SELECT id, user_id FROM staking_swap_orders
WHERE gas_cron_status = 1
  AND usdt_cron_status = 1
  AND bonus_cron_status = 1
  AND bman_exchange_cron_status = 1
  AND (
    (coin_distribution_option NOT IN (3,5,7) OR bman_earning_cron_status = 1)
    AND (coin_distribution_option NOT IN (4,6,7) OR bman_staking_cron_status = 1)
    AND (coin_distribution_option NOT IN (2,3,7) OR bman_bonus_cron_status = 1)
  );
```

---

## 🚀 Deployment Steps

### **1. Database Migration**

Run the granular migration to add all 8 cron_status columns:

```bash
mysql -u root -p database < db/staking_swap_granular_migration.sql
```

Verify columns exist:

```sql
SELECT COLUMN_NAME, COLUMN_TYPE
FROM information_schema.COLUMNS
WHERE TABLE_NAME = 'staking_swap_orders'
  AND COLUMN_NAME LIKE '%cron_status%'
ORDER BY ORDINAL_POSITION;
```

Should show:
```
gas_cron_status
usdt_cron_status
bonus_cron_status
bman_exchange_cron_status
bman_earning_cron_status
bman_staking_cron_status
bman_bonus_cron_status
```

### **2. Deploy Updated Controller**

Copy new StakingPurchasecron.php to application/controllers/:

```bash
cp StakingPurchasecron.php application/controllers/StakingPurchasecron.php
```

### **3. Configure Cron Token**

In `application/config/config.php`:

```php
$config['cron_token'] = 'YOUR_SECURE_RANDOM_TOKEN_HERE';
```

### **4. Schedule the Cron**

**Every 1 hour** using one of these methods:

**Linux/Unix Crontab:**
```bash
0 * * * * curl -s "http://192.168.29.18:9000/staking-purchase-cron?token=YOUR_CRON_TOKEN"
```

**Windows Task Scheduler:**
```batch
schtasks /create /tn StakingPurchaseCron /tr "curl -s \"http://192.168.29.18:9000/staking-purchase-cron?token=YOUR_CRON_TOKEN\"" /sc hourly /mo 1
```

### **5. Test the Cron**

**Manual test via CLI:**
```bash
php index.php stakingpurchasecron run
```

**Manual test via HTTP:**
```bash
curl "http://192.168.29.18:9000/staking-purchase-cron?token=YOUR_CRON_TOKEN"
```

Expected output:
```json
{
  "status": "success",
  "message": "Staking purchase cron completed",
  "details": {
    "total_orders": 5,
    "steps": {
      "gas": {"processed": 2, "failed": 0},
      "usdt": {"processed": 1, "failed": 0},
      "bonus": {"processed": 0, "failed": 0},
      "bman_exchange": {"processed": 1, "failed": 0},
      "bman_earning": {"processed": 1, "failed": 0},
      "bman_staking": {"processed": 0, "failed": 0},
      "bman_bonus": {"processed": 1, "failed": 0}
    }
  },
  "ran_at": "2026-07-09 16:00:00"
}
```

### **6. Monitor Logs**

```bash
tail -50 application/logs/log-*.php | grep "STAKING_PURCHASE_CRON"
```

---

## ✅ Key Features

### **CHECK before PROCESS**
✅ Every cron_status field checked BEFORE processing  
✅ Only steps with cron_status=0 are executed  
✅ Steps with cron_status=1 are automatically skipped  

### **SKIP Completed Steps**
✅ Already-completed steps (cron_status=1) never re-execute  
✅ Prevents duplicate TX detection and double wallet crediting  
✅ Protects against Etherscan API delays or blockchain reorgs  

### **MARK Unused Wallets**
✅ Wallets not used in an option are marked cron_status=1  
✅ Prevents re-checking unused wallets every cron cycle  
✅ Cleanly skips option-specific wallet distributions  

### **Independent Retry**
✅ Admin can reset just ONE cron_status to 0 for retry  
✅ Other steps remain at cron_status=1 (not re-executed)  
✅ Failed step gets another chance without repeating success  

### **Complete Audit Trail**
✅ All TX hashes stored (gas_tx_hash, usdt_tx_hash, etc.)  
✅ All transactions recorded in onchain_transactions table  
✅ Clear history of what happened and when  

---

## 🎯 Example Flow: Option 3 Order

### **Initial State**
```
id: 42
coin_distribution_option: 3 (80% Exchange, 10% Earning, 10% Bonus)
status: 'pending_gas_fee'
gas_cron_status: 0, usdt_cron_status: 0, bonus_cron_status: 0
bman_exchange_cron_status: 0, bman_earning_cron_status: 0
bman_staking_cron_status: 0 (NOT USED), bman_bonus_cron_status: 0
```

### **Cron Run 1 (Hour 1)**
```
✓ gas_cron_status = 0 → Detect gas fee ✓
  Set gas_tx_hash = 0xabc...
  Set gas_cron_status = 1
  Update status → 'pending_usdt'
✓ usdt_cron_status = 0 → Detect USDT ✓
  Set usdt_tx_hash = 0xdef...
  Set usdt_cron_status = 1
  Update status → 'pending_bman'
✓ bonus_cron_status = 0 → Detect bonus ✓
  Set bonus_tx_hash = 0xghi...
  Set bonus_cron_status = 1
```

### **Cron Run 2 (Hour 2)**
```
✓ bman_exchange_cron_status = 0 → Detect exchange ✓
  Set bman_exchange_tx_hash = 0xjkl...
  Set bman_exchange_cron_status = 1
  Distribute 800 BMAN to exchange wallet
✓ bman_earning_cron_status = 0 AND option 3 uses earning → Distribute earning ✓
  Set bman_earning_tx_hash = 0xjkl... (same as exchange)
  Set bman_earning_cron_status = 1
  Distribute 100 BMAN to earning wallet
✓ bman_staking_cron_status = 0 BUT option 3 DOESN'T use staking → Mark as not needed ✓
  Set bman_staking_cron_status = 1 (not processed)
✓ bman_bonus_cron_status = 0 AND option 3 uses bonus → Distribute bonus ✓
  Set bman_bonus_tx_hash = 0xjkl... (same as exchange)
  Set bman_bonus_cron_status = 1
  Distribute 100 BMAN to bonus wallet
✓ All required steps complete → Create user_stakes ✓
  Update status → 'swap_completed'
  Portfolio now shows: "1000 BMAN - ACTIVE"
```

### **Cron Run 3 (Hour 3)**
```
✓ Order skipped (all cron_status = 1) ✓
  Order is fully complete, won't be processed again
```

---

## 🛠️ Troubleshooting

### **Issue: Gas fee not detected**
1. Check if order status is 'pending_gas_fee'
2. Verify gas_cron_status = 0
3. Check Etherscan for TX: `curl "https://api.bscscan.com/api?module=account&action=txlist&address=USER_ADDRESS&apikey=ETHERSCAN_KEY"`
4. If TX exists but not detected, manually reset: `UPDATE staking_swap_orders SET gas_cron_status = 0 WHERE id = 42;`
5. Run cron again

### **Issue: BMAN not distributed to earning wallet**
1. Check coin_distribution_option: `SELECT coin_distribution_option FROM staking_swap_orders WHERE id = 42;`
2. Verify option uses earning (must be 3, 5, or 7)
3. Check bman_earning_cron_status = 0
4. Reset if needed: `UPDATE staking_swap_orders SET bman_earning_cron_status = 0 WHERE id = 42;`
5. Run cron again

### **Issue: Order stuck in 'pending_bman'**
1. Check all wallet cron_status: `SELECT gas_cron_status, usdt_cron_status, bonus_cron_status, bman_exchange_cron_status, bman_earning_cron_status, bman_staking_cron_status, bman_bonus_cron_status FROM staking_swap_orders WHERE id = 42;`
2. Identify which step is still 0 (pending)
3. Verify TX on Etherscan
4. If TX found but not detected, reset that cron_status to 0
5. Run cron again

---

## 📞 Support

For issues or questions:
1. Check logs: `tail -100 application/logs/log-*.php | grep "STAKING_PURCHASE_CRON"`
2. Run manually: `php index.php stakingpurchasecron run`
3. Query pending orders: `SELECT * FROM staking_swap_orders WHERE gas_cron_status = 0 LIMIT 5;`
4. Review CRON_EXECUTION_LOGIC.md for detailed flow diagrams

---

**✅ System is now ready for production deployment with full granular cron status tracking and independent retry capability.**
