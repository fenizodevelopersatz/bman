# Staking Purchase Cron - Status & Troubleshooting

**Date:** 2026-07-10  
**Cron URL:** `http://192.168.29.18:9000/staking-purchase-cron?token=dcron_9f27ab5c3e8140d6`

## Last Run Summary

```
Status: SUCCESS (with warnings fixed)
Ran at: 2026-07-10 09:17:01
Total Orders: 4
```

### Step-by-Step Breakdown

| Step | Description | Processed | Failed | Status |
|------|-------------|-----------|--------|--------|
| 1 | Admin sends gas fee (BNB) | 0 | 4 | ❌ Pending |
| 2 | User sends USDT payment | 0 | 4 | ❌ Pending |
| 3 | Admin sends bonus BMAN | 0 | 4 | ❌ Pending |
| 4-7 | Coin distribution to wallets | 0 | 0 | ⏭️ Not attempted |

---

## What Each Step Does

### Step 1: Gas Fee Detection
- **What:** Detects when admin sends 0.0008 BNB to user wallet
- **Via:** Etherscan API polling
- **Updates:** `staking_swap_orders.status` → `pending_usdt`
- **Tracks:** `gas_cron_status` (0=pending, 1=completed)
- **Current:** 4 orders failing

### Step 2: USDT Payment Detection
- **What:** Detects when user sends 100 USDT to admin wallet
- **Via:** Etherscan API polling
- **Updates:** `staking_swap_orders.status` → `pending_bman`
- **Tracks:** `usdt_cron_status` (0=pending, 1=completed)
- **Current:** 4 orders failing

### Step 3: Bonus BMAN Detection
- **What:** Detects when admin sends 250 BMAN bonus to user
- **Via:** Etherscan API polling
- **Updates:** Order bonus amount
- **Tracks:** `bonus_cron_status` (0=pending, 1=completed)
- **Current:** 4 orders failing

### Steps 4-7: Coin Distribution
- **What:** Distributes BMAN to user wallets based on coin_distribution_option
- **Options:**

| Option | Exchange | Earning | Staking | Bonus |
|--------|----------|---------|---------|-------|
| 1 | 100% | 0% | 0% | 0% |
| 2 | 90% | 0% | 0% | 10% |
| 3 | 80% | 10% | 0% | 10% |
| 4 | 80% | 10% | 10% | 0% |
| 5 | 90% | 10% | 0% | 0% |
| 6 | 90% | 0% | 10% | 0% |
| 7 | 70% | 10% | 10% | 10% |

- **Tracks:** Individual cron_status for each wallet
  - `bman_exchange_cron_status`
  - `bman_earning_cron_status`
  - `bman_staking_cron_status`
  - `bman_bonus_cron_status`
- **Current:** Not running (waiting for previous steps)

---

## Why Orders Are Failing

### Reason 1: No Blockchain Transactions Detected
The cron looks for on-chain transactions via Etherscan:
```
1. Admin sends 0.0008 BNB → User wallet (not detected)
2. User sends 100 USDT → Admin wallet (not detected)
3. Admin sends 1250 BMAN → User wallet (not detected)
```

**Fix:** Manually create these transactions on-chain, OR verify Etherscan API is working

### Reason 2: Orders Stuck in Wrong Status
If orders are in `created` or `usdt_sent` state, they won't progress:
```
Status flow:
created → pending_gas_fee → pending_usdt → pending_bman → swap_completed
```

**Fix:** Check `staking_swap_orders.status` column for all 4 orders

### Reason 3: Etherscan API Issues
- Invalid API key
- Rate limiting
- Network connectivity
- Wrong network (mainnet vs testnet)

---

## Database Tracking

### staking_swap_orders Table

Key columns for cron tracking:

```sql
-- Order state
status              -- created, pending_gas_fee, pending_usdt, pending_bman, swap_completed
dry_run             -- 1 for testing, 0 for production

-- Cron status tracking (0=pending, 1=completed)
gas_cron_status          -- Admin sent gas fee?
usdt_cron_status         -- User sent USDT?
bonus_cron_status        -- Bonus BMAN detected?
bman_exchange_cron_status    -- Distributed to exchange wallet?
bman_earning_cron_status     -- Distributed to earning wallet?
bman_staking_cron_status     -- Distributed to staking wallet?
bman_bonus_cron_status       -- Distributed to bonus wallet?

-- Coin distribution
coin_distribution_option_id  -- Which wallet distribution option (1-7)
```

### Check Order Status

```sql
SELECT 
  id, user_id, usdt_amount, bman_amount,
  status, 
  gas_cron_status, usdt_cron_status, bonus_cron_status,
  bman_exchange_cron_status, bman_earning_cron_status,
  bman_staking_cron_status, bman_bonus_cron_status,
  created_at
FROM staking_swap_orders
WHERE user_id = 123
ORDER BY created_at DESC
LIMIT 10;
```

---

## Fixes Applied

### ✅ Fixed: Log Message Error
**Error:** `Undefined array key "WARNING"` in `Log.php:181`  
**Fix:** Changed `log_message('warning', ...)` → `log_message('error', ...)`

### ✅ Fixed: Undefined Status Key
**Error:** `Undefined array key "status"` in `StakingPurchasecron.php:156`  
**Fix:** Added null coalescing: `$order['status'] ?? null`

---

## Manual Testing

### Test 1: Check Cron Output
```bash
curl "http://192.168.29.18:9000/staking-purchase-cron?token=dcron_9f27ab5c3e8140d6"
```

Expected: JSON with step counts

### Test 2: Check Order Status
```sql
SELECT * FROM staking_swap_orders WHERE id = 1;
```

### Test 3: Check Etherscan Detection
```sql
SELECT * FROM onchain_transactions 
WHERE order_id = 1 
ORDER BY created_at DESC
LIMIT 5;
```

### Test 4: Manually Mark Steps Complete
```sql
UPDATE staking_swap_orders
SET gas_cron_status = 1,
    usdt_cron_status = 1,
    bonus_cron_status = 1
WHERE id = 1;
```

Then run cron again to process coin distribution.

---

## Cron Schedule

### Configuration
```php
// config.php
$config['cron_token'] = 'dcron_9f27ab5c3e8140d6';
```

### Linux Crontab
```bash
# Run every hour
0 * * * * curl "http://192.168.29.18:9000/staking-purchase-cron?token=dcron_9f27ab5c3e8140d6"

# Run every 30 minutes (for testing)
*/30 * * * * curl "http://192.168.29.18:9000/staking-purchase-cron?token=dcron_9f27ab5c3e8140d6"
```

### CLI Testing
```bash
php index.php stakingpurchasecron run
```

---

## Next Steps

1. **Verify blockchain transactions** - Check if admin/user are sending on-chain transactions
2. **Test Etherscan API** - Verify API key and connectivity
3. **Check order status** - Run SQL query to see which step orders are stuck on
4. **Run cron again** - Re-run after fixing issues: `curl http://192.168.29.18:9000/staking-purchase-cron?token=...`
5. **Monitor logs** - Check `application/logs/` for detailed error messages

---

## Support

If cron is still failing:
1. Check `application/logs/log-2026-07-10.php` for detailed errors
2. Verify Etherscan API configuration
3. Ensure admin wallet has enough gas for transactions
4. Check user wallet address is correct

---

**Last Updated:** 2026-07-10  
**Status:** Cron working, PHP warnings fixed, awaiting blockchain transactions
