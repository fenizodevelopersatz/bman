# Granular Cron Status Tracking System

**Status:** ✅ **IMPLEMENTED**  
**Purpose:** Independent tracking of each TX step to enable granular retry capability  
**Database:** staking_swap_orders table with per-TX cron status  

---

## 📊 Complete TX Flow with Cron Status

```
┌──────────────────────────────────────────────────────────────────────┐
│ STAKING PURCHASE FLOW - GRANULAR CRON TRACKING                       │
└──────────────────────────────────────────────────────────────────────┘

INITIAL
├─ status: 'pending_gas_fee'
└─ all cron_status fields: 0 (pending)

                    ↓ CRON RUNS (HOUR 1)

STEP 1A: DETECT GAS FEE (BNB)
├─ Query: Etherscan for 0.0008 BNB to user
├─ Record: gas_tx_hash = 0xabc123...
├─ Update: gas_cron_status = 1 ✅ (COMPLETED)
├─ Update: status → 'pending_usdt'
└─ DB State:
   gas_tx_hash: 0xabc123...
   gas_cron_status: 1 ✅

                    ↓ CRON RUNS (HOUR 2)

STEP 2A: DETECT USDT PAYMENT
├─ Query: Etherscan for 100 USDT from user to admin
├─ Record: usdt_tx_hash = 0xdef456...
├─ Update: usdt_cron_status = 1 ✅ (COMPLETED)
├─ Update: status → 'pending_bman'
└─ DB State:
   usdt_tx_hash: 0xdef456...
   usdt_cron_status: 1 ✅

                    ↓ CRON RUNS (HOUR 3)

STEP 3A: DETECT BONUS BMAN (if configured)
├─ Query: Etherscan for bonus BMAN transfer (250 BMAN)
├─ Record: bonus_tx_hash = 0xghi789...
├─ Update: bonus_cron_status = 1 ✅ (COMPLETED)
└─ DB State:
   bonus_tx_hash: 0xghi789...
   bonus_cron_status: 1 ✅

STEP 3B: DETECT & DISTRIBUTE BMAN TO WALLETS
├─ Query: Etherscan for 1000 BMAN from admin to user
│
├─ DISTRIBUTION (per coin_distribution_option):
│   ├─ 800 BMAN → Exchange Wallet
│   │  ├─ Detect: bman_exchange_tx_hash = 0xjkl012...
│   │  ├─ Update: bman_exchange_cron_status = 1 ✅
│   │  └─ DB State: bman_exchange_tx_hash, bman_exchange_cron_status=1
│   │
│   ├─ 100 BMAN → Earning Wallet
│   │  ├─ Detect: bman_earning_tx_hash = 0xmno345...
│   │  ├─ Update: bman_earning_cron_status = 1 ✅
│   │  └─ DB State: bman_earning_tx_hash, bman_earning_cron_status=1
│   │
│   ├─ 100 BMAN → Staking Wallet
│   │  ├─ Detect: bman_staking_tx_hash = 0xpqr678...
│   │  ├─ Update: bman_staking_cron_status = 1 ✅
│   │  └─ DB State: bman_staking_tx_hash, bman_staking_cron_status=1
│   │
│   └─ 0 BMAN → Bonus Wallet (0% in this option)
│      ├─ (N/A - not used in Option 3)
│      └─ DB State: bman_bonus_cron_status=1 (marked as skipped)
│
├─ Update: status → 'swap_completed'
└─ All cron_status fields: 1 ✅

✅ SWAP COMPLETE - ALL STEPS EXECUTED SUCCESSFULLY
```

---

## 🗂️ Database Schema - Granular TX Tracking

### staking_swap_orders Table Columns

```sql
-- Step 1: Gas Fee (0.0008 BNB admin → user)
gas_tx_hash              VARCHAR(120)  -- BNB transaction hash
gas_cron_status          TINYINT       -- 0=pending, 1=completed

-- Step 2: USDT Payment (100 USDT user → admin)
usdt_tx_hash             VARCHAR(120)  -- USDT transaction hash
usdt_cron_status         TINYINT       -- 0=pending, 1=completed

-- Step 3: Bonus BMAN (if configured, admin → user)
bonus_tx_hash            VARCHAR(120)  -- Bonus BMAN transaction hash
bonus_cron_status        TINYINT       -- 0=pending, 1=completed

-- Step 4: BMAN Distribution to Wallets
bman_exchange_tx_hash    VARCHAR(120)  -- BMAN to exchange wallet
bman_exchange_cron_status TINYINT      -- 0=pending, 1=completed

bman_earning_tx_hash     VARCHAR(120)  -- BMAN to earning wallet
bman_earning_cron_status TINYINT       -- 0=pending, 1=completed

bman_staking_tx_hash     VARCHAR(120)  -- BMAN to staking wallet
bman_staking_cron_status TINYINT       -- 0=pending, 1=completed

bman_bonus_tx_hash       VARCHAR(120)  -- BMAN to bonus wallet
bman_bonus_cron_status   TINYINT       -- 0=pending, 1=completed
```

### Total Cron Status Fields: 8

Each has independent 0/1 tracking, enabling granular retry.

---

## 🔄 Independent Retry System

If any step fails (Etherscan timeout, network issue), retry ONLY that step:

```sql
-- Retry gas fee detection
UPDATE staking_swap_orders 
SET gas_cron_status = 0 
WHERE id = 42 AND status = 'pending_gas_fee';

-- Retry USDT detection
UPDATE staking_swap_orders 
SET usdt_cron_status = 0 
WHERE id = 42 AND status = 'pending_usdt';

-- Retry bonus BMAN detection
UPDATE staking_swap_orders 
SET bonus_cron_status = 0 
WHERE id = 42;

-- Retry exchange BMAN distribution
UPDATE staking_swap_orders 
SET bman_exchange_cron_status = 0 
WHERE id = 42 AND status = 'swap_completed';

-- Retry earning BMAN distribution
UPDATE staking_swap_orders 
SET bman_earning_cron_status = 0 
WHERE id = 42 AND status = 'swap_completed';

-- Retry staking BMAN distribution
UPDATE staking_swap_orders 
SET bman_staking_cron_status = 0 
WHERE id = 42 AND status = 'swap_completed';

-- Retry bonus BMAN distribution
UPDATE staking_swap_orders 
SET bman_bonus_cron_status = 0 
WHERE id = 42 AND status = 'swap_completed';
```

Next cron run will process ONLY the reset steps, skipping already-completed ones.

---

## ✅ Avoiding Repeated Execution

The cron checks each step's status before processing:

```php
// Pseudocode in StakingPurchasecron.php

if ($order['gas_cron_status'] == 0) {
    // Gas fee not yet processed
    if (detectGasFee($order)) {
        // Set gas_cron_status = 1
    }
}

if ($order['usdt_cron_status'] == 0) {
    // USDT payment not yet processed
    if (detectUsdtPayment($order)) {
        // Set usdt_cron_status = 1
    }
}

// ... and so on for all 8 steps

// This ensures NO REPEATED EXECUTION:
// ✅ Already completed steps (status=1) are skipped
// ✅ Pending steps (status=0) are processed
// ✅ Failed steps can be retried by resetting to 0
```

---

## 📋 Find Orders Needing Retry

```sql
-- Find orders with any pending steps
SELECT id, ref, user_id, status,
       gas_cron_status, usdt_cron_status, bonus_cron_status,
       bman_exchange_cron_status, bman_earning_cron_status,
       bman_staking_cron_status, bman_bonus_cron_status
FROM staking_swap_orders
WHERE status IN ('pending_gas_fee', 'pending_usdt', 'pending_bman', 'swap_completed')
  AND (
    gas_cron_status = 0
    OR usdt_cron_status = 0
    OR bonus_cron_status = 0
    OR bman_exchange_cron_status = 0
    OR bman_earning_cron_status = 0
    OR bman_staking_cron_status = 0
    OR bman_bonus_cron_status = 0
  );

-- Find orders with ONLY gas fee pending
SELECT id, ref, user_id FROM staking_swap_orders
WHERE gas_cron_status = 0 AND status = 'pending_gas_fee';

-- Find orders with ONLY USDT pending
SELECT id, ref, user_id FROM staking_swap_orders
WHERE usdt_cron_status = 0 AND status = 'pending_usdt';

-- Find fully completed orders
SELECT id, ref, user_id FROM staking_swap_orders
WHERE status = 'swap_completed'
  AND gas_cron_status = 1
  AND usdt_cron_status = 1
  AND bman_exchange_cron_status = 1
  AND bman_earning_cron_status = 1
  AND bman_staking_cron_status = 1
  AND bman_bonus_cron_status = 1;
```

---

## 🎯 Cron Execution Logic

Each hour, the cron:

```
FOR EACH order in staking_swap_orders:
  
  IF gas_cron_status = 0:
    Detect gas fee
    IF detected:
      Set gas_tx_hash = TX hash
      Set gas_cron_status = 1 ✅
      Update status → pending_usdt
  
  IF usdt_cron_status = 0:
    Detect USDT payment
    IF detected:
      Set usdt_tx_hash = TX hash
      Set usdt_cron_status = 1 ✅
      Update status → pending_bman
  
  IF bonus_cron_status = 0 AND status = 'pending_bman':
    Detect bonus BMAN
    IF detected:
      Set bonus_tx_hash = TX hash
      Set bonus_cron_status = 1 ✅
  
  IF bman_exchange_cron_status = 0 AND status = 'pending_bman':
    Detect BMAN to exchange
    IF detected:
      Set bman_exchange_tx_hash = TX hash
      Set bman_exchange_cron_status = 1 ✅
  
  IF bman_earning_cron_status = 0 AND status = 'pending_bman':
    Detect BMAN to earning
    IF detected:
      Set bman_earning_tx_hash = TX hash
      Set bman_earning_cron_status = 1 ✅
  
  IF bman_staking_cron_status = 0 AND status = 'pending_bman':
    Detect BMAN to staking
    IF detected:
      Set bman_staking_tx_hash = TX hash
      Set bman_staking_cron_status = 1 ✅
  
  IF bman_bonus_cron_status = 0 AND status = 'pending_bman':
    Detect BMAN to bonus
    IF detected:
      Set bman_bonus_tx_hash = TX hash
      Set bman_bonus_cron_status = 1 ✅
  
  IF all 8 cron_status fields = 1:
    Update status → swap_completed
    Create user_stakes record
```

---

## 📊 Sample DB State After Each Cron Run

### After Cron Run 1 (Hour 1 - Gas Detected)
```
id: 42
status: pending_usdt
gas_tx_hash: 0xabc123...          gas_cron_status: 1 ✅
usdt_tx_hash: NULL                usdt_cron_status: 0
bonus_tx_hash: NULL               bonus_cron_status: 0
bman_exchange_tx_hash: NULL       bman_exchange_cron_status: 0
bman_earning_tx_hash: NULL        bman_earning_cron_status: 0
bman_staking_tx_hash: NULL        bman_staking_cron_status: 0
bman_bonus_tx_hash: NULL          bman_bonus_cron_status: 0
```

### After Cron Run 2 (Hour 2 - USDT Detected)
```
id: 42
status: pending_bman
gas_tx_hash: 0xabc123...          gas_cron_status: 1 ✅
usdt_tx_hash: 0xdef456...         usdt_cron_status: 1 ✅
bonus_tx_hash: NULL               bonus_cron_status: 0
bman_exchange_tx_hash: NULL       bman_exchange_cron_status: 0
bman_earning_tx_hash: NULL        bman_earning_cron_status: 0
bman_staking_tx_hash: NULL        bman_staking_cron_status: 0
bman_bonus_tx_hash: NULL          bman_bonus_cron_status: 0
```

### After Cron Run 3 (Hour 3 - All BMAN Detected & Distributed)
```
id: 42
status: swap_completed
gas_tx_hash: 0xabc123...          gas_cron_status: 1 ✅
usdt_tx_hash: 0xdef456...         usdt_cron_status: 1 ✅
bonus_tx_hash: 0xghi789...        bonus_cron_status: 1 ✅
bman_exchange_tx_hash: 0xjkl012...bman_exchange_cron_status: 1 ✅
bman_earning_tx_hash: 0xmno345... bman_earning_cron_status: 1 ✅
bman_staking_tx_hash: 0xpqr678... bman_staking_cron_status: 1 ✅
bman_bonus_tx_hash: NULL (0% in option) bman_bonus_cron_status: 1 ✅
```

---

## 🚀 Deployment

```bash
# 1. Run granular migration
mysql -u root -p database < db/staking_swap_granular_migration.sql

# 2. Verify columns
SELECT * FROM information_schema.COLUMNS
WHERE TABLE_NAME = 'staking_swap_orders'
  AND COLUMN_NAME LIKE '%cron_status%' OR COLUMN_NAME LIKE 'bman_%_tx_hash';

# 3. Test cron
curl "http://192.168.29.18:9000/staking-purchase-cron?token=YOUR_TOKEN"

# 4. Monitor
SELECT gas_cron_status, usdt_cron_status, bonus_cron_status,
       bman_exchange_cron_status, bman_earning_cron_status,
       bman_staking_cron_status, bman_bonus_cron_status
FROM staking_swap_orders WHERE id = 42;
```

---

## ✅ Benefits

✅ **No Repeated Execution** - Each step checked before processing  
✅ **Granular Retry** - Reset only the failed step, not the whole order  
✅ **Independent Tracking** - 8 separate cron_status fields  
✅ **Clear Audit Trail** - Each TX hash and status visible  
✅ **Easy Debugging** - Know exactly which step failed  
✅ **Flexible Recovery** - Retry failed steps without re-running completed ones  

---

**Status:** ✅ Ready for Implementation
