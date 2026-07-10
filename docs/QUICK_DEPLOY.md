# ⚡ Quick Deployment Guide - 5 Minutes

**Problem:** SQL error "Commands out of sync"  
**Solution:** Use the CLEAN migration (new file created)  

---

## 🚀 Just Do This

### **1. Run Migration (CLEAN - No Errors)**

```bash
mysql -u root -p YOUR_DATABASE < db/staking_swap_granular_migration_clean.sql
```

✅ **This will work** (no "Commands out of sync" error)

### **2. Verify Columns Added**

```bash
mysql -u root -p YOUR_DATABASE -e "
SELECT COUNT(*) as new_columns FROM information_schema.COLUMNS
WHERE TABLE_NAME = 'staking_swap_orders'
AND (COLUMN_NAME LIKE '%cron_status%' OR COLUMN_NAME LIKE '%_message');
"
```

Should show: **15 columns**

### **3. Deploy Controller**

```bash
cp application/controllers/StakingPurchasecron.php \
   application/controllers/StakingPurchasecron.php.backup

# Update to latest version with error tracking
```

### **4. Test It**

```bash
php index.php stakingpurchasecron run
```

Should output:
```json
{
  "status": "success",
  "message": "Staking purchase cron completed",
  "details": { ... },
  "ran_at": "2026-07-09 16:00:00"
}
```

### **5. Schedule Hourly**

```bash
# Add to crontab:
0 * * * * curl -s "http://192.168.29.18:9000/staking-purchase-cron?token=YOUR_TOKEN"
```

---

## 📊 What Changed

### **Before (Error)**
```
File: staking_swap_granular_migration_v2.sql
Issue: SET FOREIGN_KEY_CHECKS = ON; ← WRONG (causes "Commands out of sync")
Solution: Use CLEAN version instead
```

### **After (Works)**
```
File: staking_swap_granular_migration_clean.sql ← NEW FILE
✅ Uses simple ALTER TABLE statements
✅ No procedures, no transactions
✅ Works immediately
✅ No errors
```

---

## ✅ Checklist

- [ ] Backup created: `mysqldump -u root -p DB > backup.sql`
- [ ] Migration run: `mysql -u root -p DB < staking_swap_granular_migration_clean.sql`
- [ ] Columns verified: 15 new columns exist
- [ ] Controller deployed: Latest StakingPurchasecron.php
- [ ] Manual test passed: `php index.php stakingpurchasecron run`
- [ ] Cron scheduled: Hourly via crontab

---

## 📁 Files You Need

| File | Purpose | Location |
|------|---------|----------|
| `staking_swap_granular_migration_clean.sql` | Database migration | `/db/` |
| `StakingPurchasecron.php` | Cron controller | `/application/controllers/` |
| `MIGRATION_GUIDE.md` | Detailed guide | `/docs/` |
| `CRON_FAILURE_TRACKING_GUIDE.md` | Debugging | `/docs/` |

---

## 🎯 Result After Deployment

✅ **8 Cron Status Columns** - Track each step independently  
✅ **7 Failure Message Columns** - Know WHY each step fails  
✅ **No Repeated Execution** - Each step checked before processing  
✅ **Granular Retry** - Reset only failed steps  
✅ **Complete Audit Trail** - All TX hashes tracked  

---

## 💡 Finding Failures (After Deployment)

```sql
-- Find orders with failures
SELECT id, user_id, status,
       gas_cron_status, gas_cron_status_message,
       usdt_cron_status, usdt_cron_status_message
FROM staking_swap_orders
WHERE gas_cron_status_message IS NOT NULL
   OR usdt_cron_status_message IS NOT NULL
LIMIT 10;
```

---

## 🔧 If Something Goes Wrong

### Error: "Commands out of sync"
→ Use `staking_swap_granular_migration_clean.sql` (not v2.0)

### Error: "Duplicate column"
→ OK! Columns already exist. Migration is safe.

### Need to Rollback
```bash
mysql -u root -p DB < backup.sql
```

---

**🚀 Deploy in 5 minutes using the CLEAN migration file!**
