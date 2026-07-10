# 🔧 Fix Database Error - Missing Columns

**Error:** `Unknown column 'coin_distribution_option' in 'field list'`  
**Status:** ✅ **FIXABLE IN 2 MINUTES**  

---

## 🎯 The Problem

The `staking_swap_orders` table is missing these columns:
- `coin_distribution_option`
- `package_id`
- `plan_code`
- `plan_id`
- `duration_years`

The StakingPurchasecron is trying to select these columns, but they don't exist.

---

## ✅ Solution: Run SQL Migration

### **Option 1: phpMyAdmin (Easiest)**

1. **Open phpMyAdmin**
   ```
   http://192.168.29.18:9000/phpmyadmin
   (or your phpMyAdmin URL)
   ```

2. **Select Database** → `admlm`

3. **Click SQL Tab**

4. **Paste this SQL:**
   ```sql
   -- Add coin_distribution_option column
   ALTER TABLE `staking_swap_orders`
   ADD COLUMN IF NOT EXISTS `coin_distribution_option` INT NOT NULL DEFAULT 1
   COMMENT 'Coin distribution option (1-7) for wallet splits';

   -- Add package_id
   ALTER TABLE `staking_swap_orders`
   ADD COLUMN IF NOT EXISTS `package_id` INT NULL DEFAULT NULL
   COMMENT 'Associated staking package ID';

   -- Add plan_code
   ALTER TABLE `staking_swap_orders`
   ADD COLUMN IF NOT EXISTS `plan_code` VARCHAR(50) NULL DEFAULT NULL
   COMMENT 'Staking plan code';

   -- Add plan_id
   ALTER TABLE `staking_swap_orders`
   ADD COLUMN IF NOT EXISTS `plan_id` INT NULL DEFAULT NULL
   COMMENT 'Staking plan ID';

   -- Add duration_years
   ALTER TABLE `staking_swap_orders`
   ADD COLUMN IF NOT EXISTS `duration_years` INT NULL DEFAULT NULL
   COMMENT 'Staking duration in years';
   ```

5. **Click "Go"** ✅

---

### **Option 2: MySQL Command Line**

```bash
mysql -u root -p admlm < db/staking_swap_add_distribution_option.sql
```

---

### **Option 3: File Upload**

File is ready at:
```
db/staking_swap_add_distribution_option.sql
```

Upload via phpMyAdmin → Import tab

---

## ✅ Verify It Worked

After running the SQL, check columns exist:

```sql
SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'admlm'
  AND TABLE_NAME = 'staking_swap_orders'
  AND COLUMN_NAME IN ('coin_distribution_option', 'package_id', 'plan_code', 'plan_id', 'duration_years')
ORDER BY ORDINAL_POSITION;
```

**Expected Result:**
```
coin_distribution_option  | INT        | 1
package_id               | INT        | NULL
plan_code                | VARCHAR    | NULL
plan_id                  | INT        | NULL
duration_years           | INT        | NULL
```

---

## 🚀 After Migration

1. **Refresh the Staking Purchase Cron URL**
   ```
   http://192.168.29.18:9000/staking-purchase-cron?token=YOUR_TOKEN
   ```

2. **Try Running Cron Lab Again**
   ```
   http://192.168.29.18:9000/admin/wallet/cron-lab
   ```

3. **Click "Run now" on Staking Purchase**
   - Should work without database error

---

## 📋 Columns Being Added

| Column | Type | Default | Purpose |
|--------|------|---------|---------|
| `coin_distribution_option` | INT | 1 | Option 1-7 for BMAN wallet distribution |
| `package_id` | INT | NULL | Links to staking package |
| `plan_code` | VARCHAR(50) | NULL | Staking plan identifier |
| `plan_id` | INT | NULL | Plan foreign key |
| `duration_years` | INT | NULL | Staking duration |

---

## 💡 Why These Columns?

The StakingPurchasecron controller needs these columns to:
- Track which distribution option the user selected (1-7)
- Link to staking packages
- Create user_stakes records properly
- Track plan information

---

## ⚠️ Troubleshooting

### **Error: "Column 'coin_distribution_option' already exists"**
✅ **Normal** - Means the column was already added  
✅ **The IF NOT EXISTS clause prevents duplicates**  

### **Error: "Access denied for user"**
❌ **Check your MySQL credentials**  
- Username: root (or your user)
- Password: (your password)
- Database: admlm

### **Error: "Unknown database 'admlm'"**
❌ **Wrong database name**  
- Check your actual database name
- Replace `admlm` with correct name

---

## ✅ Quick Checklist

- [ ] Open phpMyAdmin or MySQL client
- [ ] Select database `admlm`
- [ ] Run the SQL migration
- [ ] Verify 5 columns were added
- [ ] Refresh Staking Purchase Cron URL
- [ ] Test "Run now" button

---

## 📁 Files

**Migration File:**
```
db/staking_swap_add_distribution_option.sql
```

**Run it with:**
- phpMyAdmin → SQL tab
- MySQL command line
- Or phpMyAdmin → Import

---

**✅ After migration, the error will be gone!**

The Staking Purchase Cron will work perfectly.
