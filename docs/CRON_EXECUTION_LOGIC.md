# Staking Purchase Cron - Execution Logic & Distribution Options

**Command:** `php index.php stakingpurchasecron run`  
**Purpose:** Check every cron_status field and skip completed orders  
**Smart Processing:** Only execute pending steps, skip completed ones  

---

## 🎯 7 Distribution Options

Each option distributes 1000 BMAN differently based on `coin_distribution_option`:

```
Option 1: EXCHANGE ONLY
├─ Exchange:  100% (1000 BMAN)
├─ Earning:   0%   (SKIP - optional field)
├─ Staking:   0%   (SKIP - optional field)
└─ Bonus:     0%   (SKIP - optional field)
   Total:     100%

Option 2: EXCHANGE + BONUS
├─ Exchange:  90%  (900 BMAN)
├─ Earning:   0%   (SKIP - optional field)
├─ Staking:   0%   (SKIP - optional field)
└─ Bonus:     10%  (100 BMAN)
   Total:     100%

Option 3: EXCHANGE + EARNING + BONUS
├─ Exchange:  80%  (800 BMAN)
├─ Earning:   10%  (100 BMAN)
├─ Staking:   0%   (SKIP - optional field)
└─ Bonus:     10%  (100 BMAN)
   Total:     100%

Option 4: EXCHANGE + EARNING + STAKING
├─ Exchange:  80%  (800 BMAN)
├─ Earning:   10%  (100 BMAN)
├─ Staking:   10%  (100 BMAN)
└─ Bonus:     0%   (SKIP - optional field)
   Total:     100%

Option 5: EXCHANGE + EARNING
├─ Exchange:  90%  (900 BMAN)
├─ Earning:   10%  (100 BMAN)
├─ Staking:   0%   (SKIP - optional field)
└─ Bonus:     0%   (SKIP - optional field)
   Total:     100%

Option 6: EXCHANGE + STAKING
├─ Exchange:  90%  (900 BMAN)
├─ Earning:   0%   (SKIP - optional field)
├─ Staking:   10%  (100 BMAN)
└─ Bonus:     0%   (SKIP - optional field)
   Total:     100%

Option 7: FULLY SPLIT (EXCHANGE + EARNING + STAKING + BONUS)
├─ Exchange:  70%  (700 BMAN)
├─ Earning:   10%  (100 BMAN)
├─ Staking:   10%  (100 BMAN)
└─ Bonus:     10%  (100 BMAN)
   Total:     100%
```

---

## 🔍 Cron Logic: Check & Skip Pattern

### FOR EACH ORDER IN staking_swap_orders:

```php
$order = [
    'id' => 42,
    'status' => 'pending_bman',
    'coin_distribution_option' => 3,
    'gas_cron_status' => 0,        // ← PROCESS (pending)
    'usdt_cron_status' => 1,       // ← SKIP (completed)
    'bonus_cron_status' => 0,      // ← PROCESS (pending)
    'bman_exchange_cron_status' => 0, // ← PROCESS (pending)
    'bman_earning_cron_status' => 0,  // ← PROCESS (pending for option 3)
    'bman_staking_cron_status' => 1,  // ← SKIP (option 3 doesn't use staking, or already done)
    'bman_bonus_cron_status' => 0,    // ← PROCESS (pending for option 3)
];

// ✅ STEP 1: Gas Fee (0.0008 BNB)
if ($order['gas_cron_status'] == 0) {          // CHECK: is it pending?
    if (detectGasFee($order)) {                // EXECUTE: detect on blockchain
        // ✅ Set gas_tx_hash = TX hash
        // ✅ Set gas_cron_status = 1 (COMPLETED)
        // ✅ Update status → 'pending_usdt'
    }
} else {
    // SKIP: already completed ✓
}

// ✅ STEP 2: USDT Payment (100 USDT)
if ($order['usdt_cron_status'] == 0) {        // CHECK: is it pending?
    if (detectUsdtPayment($order)) {          // EXECUTE: detect on blockchain
        // ✅ Set usdt_tx_hash = TX hash
        // ✅ Set usdt_cron_status = 1 (COMPLETED)
        // ✅ Update status → 'pending_bman'
    }
} else {
    // SKIP: already completed ✓
}

// ✅ STEP 3: Bonus BMAN (if configured)
if ($order['bonus_cron_status'] == 0) {       // CHECK: is it pending?
    if (detectBonusBman($order)) {            // EXECUTE: detect on blockchain
        // ✅ Set bonus_tx_hash = TX hash
        // ✅ Set bonus_cron_status = 1 (COMPLETED)
    }
} else {
    // SKIP: already completed ✓
}

// ✅ STEP 4: BMAN → Exchange Wallet (ALWAYS used in all options)
if ($order['bman_exchange_cron_status'] == 0) { // CHECK: is it pending?
    if (detectBmanToExchange($order)) {         // EXECUTE: detect on blockchain
        // ✅ Set bman_exchange_tx_hash = TX hash
        // ✅ Set bman_exchange_cron_status = 1 (COMPLETED)
    }
} else {
    // SKIP: already completed ✓
}

// ✅ STEP 5: BMAN → Earning Wallet (Optional - used in options 3,5,7)
if (shouldProcessEarning($order)) {          // CHECK: does this option use earning?
    if ($order['bman_earning_cron_status'] == 0) { // CHECK: is it pending?
        if (detectBmanToEarning($order)) {        // EXECUTE: detect on blockchain
            // ✅ Set bman_earning_tx_hash = TX hash
            // ✅ Set bman_earning_cron_status = 1 (COMPLETED)
        }
    } else {
        // SKIP: already completed ✓
    }
} else {
    // SKIP: this option doesn't use earning wallet
    // Mark as completed (don't re-process)
    if ($order['bman_earning_cron_status'] != 1) {
        // Set bman_earning_cron_status = 1 (not needed for this option)
    }
}

// ✅ STEP 6: BMAN → Staking Wallet (Optional - used in options 4,6,7)
if (shouldProcessStaking($order)) {          // CHECK: does this option use staking?
    if ($order['bman_staking_cron_status'] == 0) { // CHECK: is it pending?
        if (detectBmanToStaking($order)) {        // EXECUTE: detect on blockchain
            // ✅ Set bman_staking_tx_hash = TX hash
            // ✅ Set bman_staking_cron_status = 1 (COMPLETED)
        }
    } else {
        // SKIP: already completed ✓
    }
} else {
    // SKIP: this option doesn't use staking wallet
    // Mark as completed (don't re-process)
    if ($order['bman_staking_cron_status'] != 1) {
        // Set bman_staking_cron_status = 1 (not needed for this option)
    }
}

// ✅ STEP 7: BMAN → Bonus Wallet (Optional - used in options 2,3,7)
if (shouldProcessBonus($order)) {            // CHECK: does this option use bonus?
    if ($order['bman_bonus_cron_status'] == 0) { // CHECK: is it pending?
        if (detectBmanToBonus($order)) {         // EXECUTE: detect on blockchain
            // ✅ Set bman_bonus_tx_hash = TX hash
            // ✅ Set bman_bonus_cron_status = 1 (COMPLETED)
        }
    } else {
        // SKIP: already completed ✓
    }
} else {
    // SKIP: this option doesn't use bonus wallet
    // Mark as completed (don't re-process)
    if ($order['bman_bonus_cron_status'] != 1) {
        // Set bman_bonus_cron_status = 1 (not needed for this option)
    }
}

// ✅ CHECK IF ORDER IS COMPLETE
if (isOrderComplete($order)) {
    // All relevant steps completed ✓
    // Update status → 'swap_completed'
    // Create user_stakes record
    // Move to portfolio
} else {
    // Order is still processing (waiting for blockchain confirmations)
    // Don't process this order on next cron run
}
```

---

## 🔧 Helper Functions

```php
/**
 * Determine if this option uses earning wallet distribution
 * Option 3, 5, 7 use earning
 */
function shouldProcessEarning($order) {
    $option = $order['coin_distribution_option'];
    return in_array($option, [3, 5, 7]);
}

/**
 * Determine if this option uses staking wallet distribution
 * Option 4, 6, 7 use staking
 */
function shouldProcessStaking($order) {
    $option = $order['coin_distribution_option'];
    return in_array($option, [4, 6, 7]);
}

/**
 * Determine if this option uses bonus wallet distribution
 * Option 2, 3, 7 use bonus
 */
function shouldProcessBonus($order) {
    $option = $order['coin_distribution_option'];
    return in_array($option, [2, 3, 7]);
}

/**
 * Check if order has all required steps completed
 */
function isOrderComplete($order) {
    // Must have: gas, usdt, exchange
    if ($order['gas_cron_status'] != 1 ||
        $order['usdt_cron_status'] != 1 ||
        $order['bman_exchange_cron_status'] != 1) {
        return false;
    }
    
    // Check optional wallets based on option
    if (shouldProcessBonus($order) && $order['bonus_cron_status'] != 1) {
        return false;
    }
    if (shouldProcessEarning($order) && $order['bman_earning_cron_status'] != 1) {
        return false;
    }
    if (shouldProcessStaking($order) && $order['bman_staking_cron_status'] != 1) {
        return false;
    }
    
    return true;
}

/**
 * Skip orders that are already fully completed
 */
function shouldSkipOrder($order) {
    return isOrderComplete($order);
}
```

---

## 📊 Cron Execution Flow

```sql
-- Get all orders that are NOT completely finished
SELECT * FROM staking_swap_orders
WHERE status IN ('pending_gas_fee', 'pending_usdt', 'pending_bman')
   OR (status = 'swap_completed' AND (
       gas_cron_status = 0 OR
       usdt_cron_status = 0 OR
       bonus_cron_status = 0 OR
       bman_exchange_cron_status = 0 OR
       bman_earning_cron_status = 0 OR
       bman_staking_cron_status = 0 OR
       bman_bonus_cron_status = 0
   ));

-- This query fetches ONLY orders with pending steps
-- Orders where ALL steps are complete (all cron_status = 1) are excluded
```

---

## 🎯 Example: Option 3 Order Processing

### Order State (Initial)
```
id: 42
coin_distribution_option: 3  (80% Exchange, 10% Earning, 10% Bonus)
status: pending_gas_fee

Cron Status:
gas_cron_status: 0 ✗ (pending)
usdt_cron_status: 0 ✗ (pending)
bonus_cron_status: 0 ✗ (pending)
bman_exchange_cron_status: 0 ✗ (pending)
bman_earning_cron_status: 0 ✗ (pending)    [OPTIONAL for option 3]
bman_staking_cron_status: 0 ✗ (pending)    [NOT USED in option 3 - skip]
bman_bonus_cron_status: 0 ✗ (pending)      [OPTIONAL for option 3]
```

### Cron Run 1 (Hour 1)
```
1. Check gas_cron_status = 0?  YES → Detect gas fee
   ✓ gas_tx_hash = 0xabc...
   ✓ gas_cron_status = 1 ✅

2. Check usdt_cron_status = 0? YES → Detect USDT
   ✓ usdt_tx_hash = 0xdef...
   ✓ usdt_cron_status = 1 ✅

3. Check bonus_cron_status = 0? YES → Detect bonus BMAN
   ✓ bonus_tx_hash = 0xghi...
   ✓ bonus_cron_status = 1 ✅

4. Check bman_exchange_cron_status = 0? YES → Detect exchange BMAN
   ✓ bman_exchange_tx_hash = 0xjkl...
   ✓ bman_exchange_cron_status = 1 ✅

5. Check shouldProcessEarning(option 3)? YES (option 3 uses earning)
   Check bman_earning_cron_status = 0? YES → Detect earning BMAN
   ✓ bman_earning_tx_hash = 0xmno...
   ✓ bman_earning_cron_status = 1 ✅

6. Check shouldProcessStaking(option 3)? NO (option 3 doesn't use staking)
   SKIP → Mark bman_staking_cron_status = 1 ✅ (not needed)

7. Check shouldProcessBonus(option 3)? YES (option 3 uses bonus)
   Check bman_bonus_cron_status = 0? YES → Detect bonus wallet BMAN
   ✓ bman_bonus_tx_hash = 0xpqr...
   ✓ bman_bonus_cron_status = 1 ✅

8. Check isOrderComplete()? YES
   ✓ status = 'swap_completed'
   ✓ Create user_stakes record
   ✓ Portfolio shows "1000 BMAN - ACTIVE" ✅
```

---

## ⏭️ Cron Run 2 (Hour 2)

```
FOR THE SAME ORDER:

1. Check gas_cron_status = 0? NO (it's 1)
   SKIP ✓ (already processed)

2. Check usdt_cron_status = 0? NO (it's 1)
   SKIP ✓ (already processed)

3. Check bonus_cron_status = 0? NO (it's 1)
   SKIP ✓ (already processed)

4. Check bman_exchange_cron_status = 0? NO (it's 1)
   SKIP ✓ (already processed)

5. Check bman_earning_cron_status = 0? NO (it's 1)
   SKIP ✓ (already processed)

6. Check bman_staking_cron_status = 0? NO (it's 1 - marked as "not needed")
   SKIP ✓ (not needed for this option)

7. Check bman_bonus_cron_status = 0? NO (it's 1)
   SKIP ✓ (already processed)

8. Check isOrderComplete()? YES
   ALREADY FINISHED ✓
   Skip entire order on next runs
```

---

## 🛠️ SQL to Find Orders Needing Processing

```sql
-- Find orders with ANY pending cron_status
SELECT id, coin_distribution_option, status,
       gas_cron_status, usdt_cron_status, bonus_cron_status,
       bman_exchange_cron_status, bman_earning_cron_status,
       bman_staking_cron_status, bman_bonus_cron_status
FROM staking_swap_orders
WHERE status IN ('pending_gas_fee', 'pending_usdt', 'pending_bman')
   OR (status = 'swap_completed' AND (
       gas_cron_status = 0 OR
       usdt_cron_status = 0 OR
       bonus_cron_status = 0 OR
       bman_exchange_cron_status = 0 OR
       bman_earning_cron_status = 0 OR
       bman_staking_cron_status = 0 OR
       bman_bonus_cron_status = 0
   ))
ORDER BY updated_at ASC
LIMIT 50;

-- Find orders that are FULLY COMPLETED (all steps done)
SELECT id, coin_distribution_option, status
FROM staking_swap_orders
WHERE status = 'swap_completed'
  AND gas_cron_status = 1
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

## ✅ Key Principles

1. **CHECK FIRST** - Always check if cron_status = 0 before processing
2. **SKIP IF DONE** - If cron_status = 1, skip that step (no repeated execution)
3. **OPTION-AWARE** - Skip optional wallets that aren't used in this option
4. **MARK NOT-NEEDED** - For unused wallets, mark cron_status = 1 so they're not re-checked
5. **SKIP COMPLETED ORDERS** - Query excludes fully-completed orders
6. **INDEPENDENT RETRY** - If one step fails, only that step can be retried by resetting its cron_status to 0

---

**Command:** `php index.php stakingpurchasecron run`  
**Result:** Only processes pending steps, skips completed orders  
**Optional Fields:** Exchange (always) + Earning, Staking, Bonus (based on option)
