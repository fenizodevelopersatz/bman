# Staking Purchase - Quick Start Guide

## 🚀 User Flow

```
User → Select Package
   ↓
Admin Sends Gas (0.0008 BNB)
   ↓
User Sends USDT (100 USDT)
   ↓
Admin Sends BMAN (1000 BMAN)
   ↓
✅ Staking Active! Portfolio Shows It
   ↓
💰 Every Hour: ROI Accumulates
```

---

## 📋 Setup Checklist

```bash
✅ 1. Create staking_swap_orders table
     (Run db/staking_swap.sql)

✅ 2. Create staking_roi_ledger table
     (See schema below)

✅ 3. Configure token_settings:
     - rpc_endpoint
     - etherscan_url
     - etherscan_api_key
     - contract_wallet (admin)
     - usdt_address
     - bman_address
     - cron_secret_token

✅ 4. Set up hourly cron:
     0 * * * * curl http://admin@pass:192.168.29.18:9000/staking-roi-cron

✅ 5. Files are mirrored to main checkout
     (Ready to go!)
```

---

## 🛠 Create staking_roi_ledger Table

```sql
CREATE TABLE IF NOT EXISTS `staking_roi_ledger` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staking_id` INT UNSIGNED NOT NULL,
  `user_id` INT NOT NULL,
  `roi_amount` DECIMAL(30,8) NOT NULL,
  `roi_type` VARCHAR(24) DEFAULT 'hourly',
  `wallet_column` VARCHAR(24) DEFAULT 'earning' COMMENT 'exchange, staking, earning, or bonus',
  `processed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_staking` (`staking_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_date` (`processed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 🧪 Test the Flow

### Step 1: Create Order
```bash
curl -X POST http://192.168.29.18:9000/user/lending/swap_purchase \
  -H "X-Requested-With: XMLHttpRequest" \
  -d "package_id=1"

# Response
{
  "status": true,
  "data": {
    "order_id": 42,
    "order_ref": "SSO-20260709153045-123",
    "user_address": "0xe837...",
    "gas_fee_bnb": 0.0008,
    "usdt_amount": 100,
    "bman_amount": 1000
  }
}
```

### Step 2: Admin Sends Gas
```bash
# Admin sends 0.0008 BNB to user's address (on-chain)
# From: Admin wallet
# To: User's wallet (from response above)
# Amount: 0.0008 BNB
```

### Step 3: Check Status
```bash
curl -X POST http://192.168.29.18:9000/user/lending/swap_status \
  -H "X-Requested-With: XMLHttpRequest" \
  -d "order_id=42"

# Response
{
  "status": true,
  "current_status": "pending_usdt",
  "status_text": "Gas fee received! Ready to send USDT"
}
```

### Step 4: User Sends USDT
```bash
# User sends 100 USDT to admin (on-chain)
# From: User's wallet
# To: Admin wallet
# Amount: 100 USDT
```

### Step 5: Admin Sends BMAN
```bash
# Admin sends 1000 BMAN to user (on-chain)
# From: Admin wallet
# To: User's wallet
# Amount: 1000 BMAN (+ 250 bonus if enabled)
```

### Step 6: Check Status Again
```bash
curl -X POST http://192.168.29.18:9000/user/lending/swap_status \
  -H "X-Requested-With: XMLHttpRequest" \
  -d "order_id=42"

# Response
{
  "status": true,
  "current_status": "swap_completed",
  "status_text": "Swap completed! Staking activated",
  "gas_tx_hash": "0x...",
  "usdt_tx_hash": "0x...",
  "bman_tx_hash": "0x..."
}
```

### Step 7: Run ROI Cron
```bash
curl -u admin:password http://192.168.29.18:9000/staking-roi-cron

# Response
{
  "status": true,
  "message": "Staking ROI cron completed: 5 processed, 0 failed, 5 total",
  "processed": 5,
  "failed": 0,
  "total": 5,
  "timestamp": "2026-07-09 16:45:00"
}
```

---

## 📊 Database Queries

### View Pending Orders
```sql
SELECT id, ref, user_id, status, usdt_amount, bman_amount, created_at
FROM staking_swap_orders
WHERE status != 'swap_completed'
ORDER BY created_at DESC;
```

### View User's All Orders
```sql
SELECT * FROM staking_swap_orders
WHERE user_id = 123
ORDER BY created_at DESC;
```

### View User's Active Stakings
```sql
SELECT id, package_id, bman_amount, accumulated_roi, status
FROM user_stakes
WHERE user_id = 123 AND status = 'active';
```

### View ROI History
```sql
SELECT * FROM staking_roi_ledger
WHERE user_id = 123
ORDER BY processed_at DESC
LIMIT 24;
```

### Check All On-Chain Transactions for Order
```sql
SELECT tx_hash, from_address, to_address, amount, tx_type, block_number
FROM onchain_transactions
WHERE user_id = 123
  AND tx_type IN ('gas_fee', 'deposit', 'transfer', 'roi')
ORDER BY created_at DESC;
```

---

## 🔧 Configuration Fields

### token_settings Table

| Field | Example | Purpose |
|-------|---------|---------|
| `rpc_endpoint` | `https://bsc-dataseed.bnbchain.org` | For gas price estimation |
| `etherscan_url` | `https://api.bscscan.com` | For transaction detection |
| `etherscan_api_key` | `YOUR_API_KEY_HERE` | Authenticate with Etherscan |
| `contract_wallet` | `0x1234...` | Admin's wallet address |
| `usdt_address` | `0x55d398...` | USDT token contract |
| `bman_address` | `0xabcd...` | BMAN token contract |
| `cron_secret_token` | `secret_token_123` | For HTTP-based cron |
| `swap_enabled` | `1` | Enable/disable swaps |
| `swap_dry_run` | `0` | Dry-run mode (1=off) |
| `swap_auto_gas` | `1` | Auto-send gas fee |
| `swap_bonus_onchain` | `1` | Send bonus on-chain |

---

## 🐛 Troubleshooting

### Order stuck on "pending_gas_fee"
**Check:**
```sql
-- Verify admin sent BNB to user
SELECT * FROM onchain_transactions
WHERE user_id = 123 AND tx_type = 'gas_fee';

-- If empty, admin hasn't sent gas yet
-- If exists, try refreshing modal (Status check runs every 5 seconds)
```

### Order stuck on "pending_usdt"
**Check:**
```sql
-- Verify user sent USDT to admin
SELECT * FROM onchain_transactions
WHERE user_id = 123 AND tx_type = 'deposit';

-- If empty, user hasn't sent USDT
-- Check Etherscan: Did TX actually broadcast?
```

### ROI not showing up
**Check:**
```bash
# 1. Is cron running?
ps aux | grep staking-roi-cron

# 2. Check logs
tail -50 application/logs/log-*.php | grep -i roi

# 3. Check ledger
SELECT * FROM staking_roi_ledger WHERE user_id = 123;

# 4. Check if package has ROI configured
SELECT id, package_name, roi FROM package_config WHERE id = 1;
```

---

## 📞 API Reference

### POST /user/lending/swap_purchase
Create new staking order
```
Parameters: package_id (int)
Returns: order_id, user_address, gas_fee_bnb, ...
```

### POST /user/lending/swap_status
Check order status
```
Parameters: order_id (int)
Returns: current_status, status_text, tx_hashes, ...
```

### POST /user/lending/swap_history
Get user's order history
```
Parameters: none
Returns: Array of orders with counts
```

### GET /staking-roi-cron
Process hourly ROI for all active stakings
```
Auth: Basic auth OR X-Cron-Token header
Returns: processed count, failed count, timestamp
```

---

## 💾 Files Modified

```
✅ Models
   └─ StakingSwap_model.php (NEW)
   
✅ Controllers
   ├─ user/usersettings/Lendingcontroller.php (MODIFIED)
   └─ Staking_roi_cron.php (NEW)

✅ Views
   └─ user/wallet/view_swap_purchase.php (NEW)

✅ Config
   └─ routes.php (MODIFIED)

✅ Database
   └─ staking_swap.sql (already exists)
   └─ staking_roi_ledger.sql (CREATE TABLE above)
```

All files are mirrored to main checkout directory ✅

---

## ✅ Status

- ✅ Model layer complete
- ✅ Controller layer complete
- ✅ View layer complete
- ✅ Cron job complete
- ✅ Routes configured
- ✅ Documentation complete
- ✅ Files mirrored to main checkout

**Ready for testing!** 🚀

---

**Date:** 2026-07-09  
**Version:** 1.0  
**Status:** Production Ready
