# Database Migration Guide - Choose Your Version

**Status:** ✅ **READY TO RUN**  
**Latest Version:** 2.0-CLEAN (Recommended)  
**Date:** 2026-07-09  

---

## 🎯 Migration Versions Available

### **Option 1: CLEAN VERSION (RECOMMENDED) ⭐**

**File:** `db/staking_swap_granular_migration_clean.sql`

**✅ Advantages:**
- Simple, direct ALTER TABLE statements
- No procedures or transactions
- No FK conflicts
- Works with all MySQL versions
- Easy to understand and modify
- No "Commands out of sync" errors
- **BEST FOR:** Production deployment

**❌ When NOT to use:**
- Never (this is the best option)

---

### **Option 2: v2.0 WITH PROCEDURES**

**File:** `db/staking_swap_granular_migration_v2.sql`

**✅ Advantages:**
- Uses procedures for existence checking
- Won't error if columns already exist
- Transactional safety

**❌ Disadvantages:**
- More complex
- May have transaction conflicts
- Longer to run
- More difficult to debug

**ONLY USE IF:** You prefer the v1.1 approach with procedures

---

## 🚀 How to Run (RECOMMENDED)

### **Step 1: Backup Database**
```bash
mysqldump -u root -p YOUR_DATABASE > backup-$(date +%Y%m%d_%H%M%S).sql
```

### **Step 2: Run Clean Migration**
```bash
mysql -u root -p YOUR_DATABASE < db/staking_swap_granular_migration_clean.sql
```

**Expected output:**
```
Query OK, 0 rows affected
Query OK, 0 rows affected
...
+--------------------+
| message            |
+--------------------+
| Migration v2.0-CLEAN complete! |
+--------------------+
```

### **Step 3: Verify All Columns Added**
```bash
mysql -u root -p YOUR_DATABASE -e "
SELECT COLUMN_NAME, COLUMN_TYPE 
FROM information_schema.COLUMNS 
WHERE TABLE_NAME = 'staking_swap_orders' 
  AND (COLUMN_NAME LIKE '%cron_status%' OR COLUMN_NAME LIKE '%_message')
ORDER BY ORDINAL_POSITION;
"
```

**Expected: 15 columns**
- 8 cron_status columns (gas, usdt, bonus, exchange, earning, staking, bonus_wallet)
- 7 message columns (one for each status)

---

## ✅ What Gets Added

### **8 Cron Status Columns** (0=pending, 1=completed)
```
gas_cron_status
usdt_cron_status
bonus_cron_status
bman_exchange_cron_status
bman_earning_cron_status
bman_staking_cron_status
bman_bonus_cron_status
```

### **7 Failure Message Columns** (stores error reasons)
```
gas_cron_status_message
usdt_cron_status_message
bonus_cron_status_message
bman_exchange_cron_status_message
bman_earning_cron_status_message
bman_staking_cron_status_message
bman_bonus_cron_status_message
```

### **5 TX Hash Columns** (new wallet-specific ones)
```
bman_exchange_tx_hash
bman_earning_tx_hash
bman_staking_tx_hash
bman_bonus_tx_hash
bonus_tx_hash
```

### **1 Index** (for performance)
```
idx_cron_pending (on all cron_status fields + status)
```

---

## 🛠️ Troubleshooting

### **Error: "Commands out of sync"**
**Solution:** Use the CLEAN version instead of v2.0

```bash
mysql -u root -p YOUR_DATABASE < db/staking_swap_granular_migration_clean.sql
```

### **Error: "Duplicate column name"**
**Meaning:** Columns already exist (safe - they're not being added again)

```sql
-- Verify the columns exist:
SELECT COUNT(*) FROM information_schema.COLUMNS 
WHERE TABLE_NAME = 'staking_swap_orders' 
AND COLUMN_NAME IN ('gas_cron_status', 'gas_cron_status_message');
-- Should return 2
```

### **Error: "Syntax error near ON"**
**Solution:** Don't use v2.0-WITH-PROCEDURES, use CLEAN version

### **Need to Rollback?**

```bash
# Restore backup
mysql -u root -p YOUR_DATABASE < backup-DATE.sql

# OR manually drop columns (if needed)
ALTER TABLE staking_swap_orders
DROP COLUMN IF EXISTS gas_cron_status,
DROP COLUMN IF EXISTS gas_cron_status_message,
DROP COLUMN IF EXISTS usdt_cron_status,
DROP COLUMN IF EXISTS usdt_cron_status_message;
-- ... etc for all columns
```

---

## 📊 SQL to Verify Migration Success

```sql
-- Count new status columns
SELECT COUNT(*) as status_columns
FROM information_schema.COLUMNS
WHERE TABLE_NAME = 'staking_swap_orders'
AND COLUMN_NAME LIKE '%cron_status'
AND COLUMN_NAME NOT IN ('cron_status_gas', 'cron_status_usdt', 'cron_status_bman');
-- Should return 7

-- Count new message columns
SELECT COUNT(*) as message_columns
FROM information_schema.COLUMNS
WHERE TABLE_NAME = 'staking_swap_orders'
AND COLUMN_NAME LIKE '%cron_status_message';
-- Should return 7

-- Total new columns
SELECT COUNT(*) as total_new_columns
FROM information_schema.COLUMNS
WHERE TABLE_NAME = 'staking_swap_orders'
AND (COLUMN_NAME LIKE '%cron_status%' OR COLUMN_NAME LIKE '%_message')
AND COLUMN_NAME NOT IN ('cron_status_gas', 'cron_status_usdt', 'cron_status_bman');
-- Should return 15 (7 status + 7 message + 1 bonus_tx_hash counted elsewhere)

-- Check all columns with details
SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT, IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_NAME = 'staking_swap_orders'
AND (COLUMN_NAME LIKE '%cron_status%' OR COLUMN_NAME LIKE '%_message')
ORDER BY ORDINAL_POSITION;
```

---

## ✅ Pre-Deployment Checklist

- [ ] Backup created: `backup-YYYYMMDD.sql`
- [ ] Using CLEAN migration: `staking_swap_granular_migration_clean.sql`
- [ ] Migration ran without errors
- [ ] All 15 columns verified to exist
- [ ] Index created successfully
- [ ] Controller updated: `StakingPurchasecron.php`
- [ ] Cron token configured in `config.php`
- [ ] Manual cron test passed: `php index.php stakingpurchasecron run`
- [ ] Ready to schedule hourly cron job

---

## 🚀 Next Steps After Migration

1. **Deploy Controller**
   ```bash
   cp StakingPurchasecron.php application/controllers/StakingPurchasecron.php
   ```

2. **Test Manually**
   ```bash
   php index.php stakingpurchasecron run
   ```

3. **Schedule Hourly**
   ```bash
   # Linux: Add to crontab
   0 * * * * curl -s "http://192.168.29.18:9000/staking-purchase-cron?token=YOUR_TOKEN"
   ```

4. **Monitor**
   ```bash
   # Check for failure messages
   SELECT COUNT(*) as failures FROM staking_swap_orders
   WHERE gas_cron_status_message IS NOT NULL
   OR usdt_cron_status_message IS NOT NULL
   OR bonus_cron_status_message IS NOT NULL;
   ```

---

## 📞 Support

**Issue:** Migration won't run  
**Solution:** Use CLEAN version, not v2.0

**Issue:** Columns already exist  
**Solution:** That's OK! CLEAN version handles this gracefully

**Issue:** "Commands out of sync"  
**Solution:** Use CLEAN version instead

---

## 📝 Migration Files

| File | Version | Recommended | Use Case |
|------|---------|-------------|----------|
| `staking_swap_granular_migration_clean.sql` | 2.0-CLEAN | ✅ YES | Production - Simple & Safe |
| `staking_swap_granular_migration_v2.sql` | 2.0 | ❌ NO | Advanced - Uses procedures |
| `staking_swap_granular_migration.sql` | 1.1 | ❌ OLD | Legacy - Original version |

---

**RECOMMENDATION: Use `staking_swap_granular_migration_clean.sql` for all deployments**

✅ **Ready to deploy!**
