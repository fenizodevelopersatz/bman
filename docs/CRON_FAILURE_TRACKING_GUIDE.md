# Cron Failure Tracking & Debugging Guide

**Status:** ✅ **IMPLEMENTED**  
**Version:** 2.0 - With failure message tracking  
**Date:** 2026-07-09  

---

## 🎯 Overview

The cron system now tracks **failure messages** for each step independently. When a cron_status step fails to execute, the failure reason is automatically recorded in the corresponding `*_cron_status_message` column for debugging and troubleshooting.

---

## 📊 Failure Message Columns

Each of the 7 cron steps now has a dedicated message column:

```sql
gas_cron_status_message              -- Gas fee detection failures
usdt_cron_status_message             -- USDT payment detection failures
bonus_cron_status_message            -- Bonus BMAN detection failures
bman_exchange_cron_status_message    -- Exchange wallet distribution failures
bman_earning_cron_status_message     -- Earning wallet distribution failures
bman_staking_cron_status_message     -- Staking wallet distribution failures
bman_bonus_cron_status_message       -- Bonus wallet distribution failures
```

---

## 📋 Failure Message Examples

### **API Timeout**
```
"Etherscan API no response for gas fee detection"
```
**Cause:** Network latency or Etherscan API downtime  
**Action:** Retry in next cron cycle

### **TX Not Found Yet**
```
"No BNB transactions found on Etherscan for user address"
"Gas fee TX (0.0005-0.01 BNB) not found on Etherscan yet"
```
**Cause:** Transaction not indexed on Etherscan yet (5-30 second delay)  
**Action:** Wait and retry in next cron cycle (1 hour)

### **User Hasn't Sent TX**
```
"USDT transfer from user to admin not found on Etherscan yet"
```
**Cause:** User hasn't completed their payment yet  
**Action:** Wait for user to send USDT, cron will detect automatically

### **Config Missing**
```
"No BMAN transfers found on Etherscan for admin address"
```
**Cause:** Admin wallet address not configured or no TXs from that address  
**Action:** Verify admin_address in order, check token_settings config

### **Exception/Error**
```
"Exception: Division by zero"
"Exception: JSON decode error"
```
**Cause:** Code bug or malformed data  
**Action:** Contact developer, check logs for full stack trace

---

## 🔍 Checking Failure Messages

### **Find Orders with Failures**

```sql
-- Find ALL orders with ANY failure messages
SELECT id, ref, user_id, status,
       gas_cron_status, gas_cron_status_message,
       usdt_cron_status, usdt_cron_status_message,
       bonus_cron_status, bonus_cron_status_message,
       bman_exchange_cron_status, bman_exchange_cron_status_message,
       bman_earning_cron_status, bman_earning_cron_status_message,
       bman_staking_cron_status, bman_staking_cron_status_message,
       bman_bonus_cron_status, bman_bonus_cron_status_message
FROM staking_swap_orders
WHERE gas_cron_status_message IS NOT NULL
   OR usdt_cron_status_message IS NOT NULL
   OR bonus_cron_status_message IS NOT NULL
   OR bman_exchange_cron_status_message IS NOT NULL
   OR bman_earning_cron_status_message IS NOT NULL
   OR bman_staking_cron_status_message IS NOT NULL
   OR bman_bonus_cron_status_message IS NOT NULL;

-- Find ONLY gas fee failures
SELECT id, user_id, status, gas_cron_status, gas_cron_status_message
FROM staking_swap_orders
WHERE gas_cron_status_message IS NOT NULL
ORDER BY updated_at DESC;

-- Find ONLY USDT payment failures
SELECT id, user_id, status, usdt_cron_status, usdt_cron_status_message
FROM staking_swap_orders
WHERE usdt_cron_status_message IS NOT NULL
ORDER BY updated_at DESC;

-- Find ONLY exchange wallet failures
SELECT id, user_id, status, bman_exchange_cron_status, bman_exchange_cron_status_message
FROM staking_swap_orders
WHERE bman_exchange_cron_status_message IS NOT NULL
ORDER BY updated_at DESC;

-- Find all failures for a specific order
SELECT id, 
       gas_cron_status, gas_cron_status_message,
       usdt_cron_status, usdt_cron_status_message,
       bonus_cron_status, bonus_cron_status_message,
       bman_exchange_cron_status, bman_exchange_cron_status_message,
       bman_earning_cron_status, bman_earning_cron_status_message,
       bman_staking_cron_status, bman_staking_cron_status_message,
       bman_bonus_cron_status, bman_bonus_cron_status_message
FROM staking_swap_orders
WHERE id = 42;
```

---

## ✅ Message Lifecycle

### **Step Succeeds**
```
1. Cron detects TX on blockchain
2. Sets cron_status = 1
3. Clears message: cron_status_message = NULL  ← Success!
4. Updates status field (pending_usdt, pending_bman, etc.)
```

### **Step Fails**
```
1. Cron attempts detection
2. Keeps cron_status = 0  (still pending)
3. Records error: cron_status_message = "Error reason"
4. Waits for next cron run
```

### **Step Retried**
```
1. Admin resets: UPDATE ... SET gas_cron_status = 0
2. Next cron run attempts detection again
3. Success: cron_status = 1, message = NULL
4. OR Fail: cron_status = 0, message = "New error"
```

---

## 🛠️ Troubleshooting by Message

### **Message: "Etherscan API no response"**

**Diagnosis:**
- Network timeout occurred
- Etherscan API is down

**Troubleshooting:**
1. Check if Etherscan is up: https://www.bscscan.com
2. Verify internet connection from server
3. Check `token_settings.etherscan_api_key` is valid
4. Check `token_settings.etherscan_url` is correct (`https://api.bscscan.com`)

**Fix:**
```sql
-- Wait 5-10 minutes, then retry
UPDATE staking_swap_orders SET gas_cron_status = 0 WHERE id = 42;
-- Next cron run will retry automatically
```

---

### **Message: "No transactions found"**

**Diagnosis:**
- User hasn't sent the payment yet
- OR blockchain address is wrong

**Troubleshooting:**
1. Check user_address: `SELECT user_address FROM staking_swap_orders WHERE id = 42;`
2. Search manually on Etherscan: `https://www.bscscan.com/address/USER_ADDRESS`
3. Verify it matches user's wallet

**Fix:**
- If address is wrong, update it:
  ```sql
  UPDATE staking_swap_orders 
  SET user_address = '0xcorrect_address'
  WHERE id = 42;
  UPDATE staking_swap_orders SET gas_cron_status = 0 WHERE id = 42;
  ```
- If address is correct, wait for user to send payment

---

### **Message: "TX not found on Etherscan yet"**

**Diagnosis:**
- Transaction exists but Etherscan indexing delay
- Etherscan has 5-30 second indexing lag

**Troubleshooting:**
1. Check Etherscan directly for TX hash
2. If TX shows on Etherscan with confirmations, retry cron
3. If TX is still pending (0 confirms), wait

**Fix:**
```sql
-- Wait 30 seconds, then retry
UPDATE staking_swap_orders SET gas_cron_status = 0 WHERE id = 42;
-- Next cron run will check again
```

---

### **Message: "Exception: Division by zero"**

**Diagnosis:**
- Code bug in cron logic
- Malformed data in database

**Troubleshooting:**
1. Check logs: `tail -100 application/logs/log-*.php | grep "STAKING_PURCHASE_CRON"`
2. Look for full stack trace
3. Check bman_amount is not 0: `SELECT id, bman_amount FROM staking_swap_orders WHERE id = 42;`
4. Check coin_distribution_option is valid (1-7): `SELECT id, coin_distribution_option FROM staking_swap_orders WHERE id = 42;`

**Fix:**
1. Fix data:
   ```sql
   UPDATE staking_swap_orders 
   SET bman_amount = 1000, coin_distribution_option = 1
   WHERE id = 42;
   ```
2. Clear message and retry:
   ```sql
   UPDATE staking_swap_orders 
   SET gas_cron_status = 0, gas_cron_status_message = NULL
   WHERE id = 42;
   ```

---

## 📊 Monitoring Dashboard SQL

### **Daily Failure Summary**

```sql
SELECT 
  DATE(updated_at) as failure_date,
  COUNT(CASE WHEN gas_cron_status_message IS NOT NULL THEN 1 END) as gas_failures,
  COUNT(CASE WHEN usdt_cron_status_message IS NOT NULL THEN 1 END) as usdt_failures,
  COUNT(CASE WHEN bonus_cron_status_message IS NOT NULL THEN 1 END) as bonus_failures,
  COUNT(CASE WHEN bman_exchange_cron_status_message IS NOT NULL THEN 1 END) as exchange_failures,
  COUNT(CASE WHEN bman_earning_cron_status_message IS NOT NULL THEN 1 END) as earning_failures,
  COUNT(CASE WHEN bman_staking_cron_status_message IS NOT NULL THEN 1 END) as staking_failures,
  COUNT(CASE WHEN bman_bonus_cron_status_message IS NOT NULL THEN 1 END) as bonus_wallet_failures
FROM staking_swap_orders
WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(updated_at)
ORDER BY failure_date DESC;
```

### **Top Error Messages**

```sql
-- Top gas fee errors
SELECT gas_cron_status_message, COUNT(*) as count
FROM staking_swap_orders
WHERE gas_cron_status_message IS NOT NULL
GROUP BY gas_cron_status_message
ORDER BY count DESC
LIMIT 10;

-- Top USDT errors
SELECT usdt_cron_status_message, COUNT(*) as count
FROM staking_swap_orders
WHERE usdt_cron_status_message IS NOT NULL
GROUP BY usdt_cron_status_message
ORDER BY count DESC
LIMIT 10;

-- All error types across all steps
SELECT 'gas' as step, gas_cron_status_message as message, COUNT(*) as count
FROM staking_swap_orders
WHERE gas_cron_status_message IS NOT NULL
GROUP BY gas_cron_status_message

UNION ALL

SELECT 'usdt', usdt_cron_status_message, COUNT(*)
FROM staking_swap_orders
WHERE usdt_cron_status_message IS NOT NULL
GROUP BY usdt_cron_status_message

UNION ALL

SELECT 'bonus', bonus_cron_status_message, COUNT(*)
FROM staking_swap_orders
WHERE bonus_cron_status_message IS NOT NULL
GROUP BY bonus_cron_status_message

UNION ALL

SELECT 'exchange', bman_exchange_cron_status_message, COUNT(*)
FROM staking_swap_orders
WHERE bman_exchange_cron_status_message IS NOT NULL
GROUP BY bman_exchange_cron_status_message

UNION ALL

SELECT 'earning', bman_earning_cron_status_message, COUNT(*)
FROM staking_swap_orders
WHERE bman_earning_cron_status_message IS NOT NULL
GROUP BY bman_earning_cron_status_message

UNION ALL

SELECT 'staking', bman_staking_cron_status_message, COUNT(*)
FROM staking_swap_orders
WHERE bman_staking_cron_status_message IS NOT NULL
GROUP BY bman_staking_cron_status_message

UNION ALL

SELECT 'bonus_wallet', bman_bonus_cron_status_message, COUNT(*)
FROM staking_swap_orders
WHERE bman_bonus_cron_status_message IS NOT NULL
GROUP BY bman_bonus_cron_status_message

ORDER BY count DESC;
```

---

## 🔧 Manual Recovery Procedures

### **Retry A Failed Step**

```sql
-- Reset just the failed step
UPDATE staking_swap_orders 
SET gas_cron_status = 0,              -- Back to pending
    gas_cron_status_message = NULL    -- Clear old message
WHERE id = 42;

-- Next cron run will retry this step only
-- Already-completed steps are skipped automatically
```

### **Retry All Failed Steps in Order**

```sql
-- Reset ALL pending steps
UPDATE staking_swap_orders 
SET gas_cron_status = 0,
    usdt_cron_status = 0,
    bonus_cron_status = 0,
    bman_exchange_cron_status = 0,
    bman_earning_cron_status = 0,
    bman_staking_cron_status = 0,
    bman_bonus_cron_status = 0,
    gas_cron_status_message = NULL,
    usdt_cron_status_message = NULL,
    bonus_cron_status_message = NULL,
    bman_exchange_cron_status_message = NULL,
    bman_earning_cron_status_message = NULL,
    bman_staking_cron_status_message = NULL,
    bman_bonus_cron_status_message = NULL
WHERE id = 42;

-- WARNING: This can cause duplicate TXs if any completed!
-- Use only if you're certain all steps actually failed
```

### **Force Complete (Emergency Only)**

```sql
-- Mark order as complete WITHOUT detecting TXs
-- Use ONLY if you verified TXs manually on Etherscan
UPDATE staking_swap_orders 
SET gas_cron_status = 1,
    usdt_cron_status = 1,
    bonus_cron_status = 1,
    bman_exchange_cron_status = 1,
    bman_earning_cron_status = 1,
    bman_staking_cron_status = 1,
    bman_bonus_cron_status = 1,
    status = 'swap_completed',
    gas_cron_status_message = 'MANUAL: Verified on Etherscan',
    usdt_cron_status_message = 'MANUAL: Verified on Etherscan',
    bonus_cron_status_message = 'MANUAL: Verified on Etherscan',
    bman_exchange_cron_status_message = 'MANUAL: Verified on Etherscan',
    bman_earning_cron_status_message = 'MANUAL: Verified on Etherscan',
    bman_staking_cron_status_message = 'MANUAL: Verified on Etherscan',
    bman_bonus_cron_status_message = 'MANUAL: Verified on Etherscan'
WHERE id = 42;

-- Then manually create user_stakes if not exists:
INSERT INTO user_stakes (user_id, package_id, bman_amount, bonus_bman, status, activated_at, created_at)
SELECT user_id, package_id, bman_amount, bonus_bman, 'active', NOW(), NOW()
FROM staking_swap_orders
WHERE id = 42
  AND NOT EXISTS (SELECT 1 FROM user_stakes WHERE user_id = (SELECT user_id FROM staking_swap_orders WHERE id = 42));
```

---

## 📞 Support Debugging Checklist

When cron fails, follow this checklist:

- [ ] **Check Message:** What does the `*_cron_status_message` say?
- [ ] **Check Status:** Is `cron_status = 0` (pending)?
- [ ] **Check Etherscan:** Search for TX hash manually (if known)
- [ ] **Check Config:** Is etherscan_url, etherscan_api_key configured?
- [ ] **Check Logs:** `tail -100 application/logs/log-*.php | grep "STAKING_PURCHASE_CRON"`
- [ ] **Check Network:** Can server reach etherscan API? `curl https://api.bscscan.com/api?module=account&action=txlist&address=0x...&apikey=...`
- [ ] **Check Data:** Is user_address, admin_address valid?
- [ ] **Check Blockchain:** Did user actually send the TX? Check user's wallet on Etherscan
- [ ] **Retry:** Reset `cron_status = 0` and wait for next cron run
- [ ] **Escalate:** If still failing after 3 retries, contact developer

---

## 📊 Cron Status Matrix

```
cron_status = 0, message = NULL
  → Pending (not yet attempted or never failed)

cron_status = 0, message = "Error..."
  → Pending WITH FAILURE (has tried, but failed - ready to retry)

cron_status = 1, message = NULL
  → COMPLETED SUCCESSFULLY ✓

cron_status = 1, message = "Error..." 
  → Should not exist (message cleared on success)
  → If exists, indicates data corruption - investigate
```

---

**✅ Failure message tracking is now live. All cron steps automatically record why they fail, making debugging fast and easy.**
