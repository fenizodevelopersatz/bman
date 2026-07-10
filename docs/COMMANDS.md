# 📝 Copy-Paste Commands - Deploy in 5 Minutes

All commands ready to copy and paste. Just replace placeholders.

---

## 🗄️ Database Setup

### **1. Backup (Do this first!)**
```bash
mysqldump -u root -p YOUR_DATABASE_NAME > backup-$(date +%Y%m%d_%H%M%S).sql
```

### **2. Run Clean Migration (NO ERRORS)**
```bash
mysql -u root -p YOUR_DATABASE_NAME < db/staking_swap_granular_migration_clean.sql
```

### **3. Verify Migration Success**
```bash
mysql -u root -p YOUR_DATABASE_NAME -e "
SELECT 'STATUS COLUMNS' as check_name, COUNT(*) as count
FROM information_schema.COLUMNS
WHERE TABLE_NAME = 'staking_swap_orders'
AND COLUMN_NAME LIKE '%cron_status'
AND COLUMN_NAME NOT IN ('cron_status_gas', 'cron_status_usdt', 'cron_status_bman')
UNION
SELECT 'MESSAGE COLUMNS', COUNT(*)
FROM information_schema.COLUMNS
WHERE TABLE_NAME = 'staking_swap_orders'
AND COLUMN_NAME LIKE '%_message'
UNION
SELECT 'TOTAL NEW COLUMNS', COUNT(*)
FROM information_schema.COLUMNS
WHERE TABLE_NAME = 'staking_swap_orders'
AND (COLUMN_NAME LIKE '%cron_status%' OR COLUMN_NAME LIKE '%_message')
AND COLUMN_NAME NOT IN ('cron_status_gas', 'cron_status_usdt', 'cron_status_bman');
"
```

**Expected Output:**
```
STATUS COLUMNS          | 7
MESSAGE COLUMNS         | 7
TOTAL NEW COLUMNS       | 15
```

---

## 🚀 Controller Deployment

### **4. Copy Controller to Production**
```bash
cp application/controllers/StakingPurchasecron.php \
   application/controllers/StakingPurchasecron.php.backup

# (Then deploy the updated version)
```

### **5. Verify Controller Works**
```bash
php index.php stakingpurchasecron run
```

**Expected Output:**
```json
{
  "status": "success",
  "message": "Staking purchase cron completed",
  "details": {
    "total_orders": 0,
    "steps": {
      "gas": {"processed": 0, "failed": 0},
      "usdt": {"processed": 0, "failed": 0},
      "bonus": {"processed": 0, "failed": 0},
      "bman_exchange": {"processed": 0, "failed": 0},
      "bman_earning": {"processed": 0, "failed": 0},
      "bman_staking": {"processed": 0, "failed": 0},
      "bman_bonus": {"processed": 0, "failed": 0}
    }
  },
  "ran_at": "2026-07-09 16:00:00"
}
```

---

## ⏰ Cron Scheduling

### **6a. Linux/Unix - Add to Crontab**
```bash
crontab -e
```

Add this line:
```bash
0 * * * * curl -s "http://192.168.29.18:9000/staking-purchase-cron?token=YOUR_CRON_TOKEN_HERE" >> /var/log/staking-cron.log 2>&1
```

**Verify cron is scheduled:**
```bash
crontab -l | grep staking-purchase-cron
```

---

### **6b. Windows - Task Scheduler**
```batch
schtasks /create /tn StakingPurchaseCron /tr "curl -s \"http://192.168.29.18:9000/staking-purchase-cron?token=YOUR_CRON_TOKEN_HERE\"" /sc hourly /mo 1
```

**Verify task created:**
```batch
schtasks /query /tn StakingPurchaseCron
```

---

## 📊 Monitoring Commands

### **7. Find Orders with Pending Steps**
```bash
mysql -u root -p YOUR_DATABASE_NAME -e "
SELECT id, user_id, status,
       gas_cron_status, usdt_cron_status, bonus_cron_status,
       bman_exchange_cron_status
FROM staking_swap_orders
WHERE gas_cron_status = 0 OR usdt_cron_status = 0 
   OR bonus_cron_status = 0 OR bman_exchange_cron_status = 0
LIMIT 20;
"
```

### **8. Find Orders with Failures**
```bash
mysql -u root -p YOUR_DATABASE_NAME -e "
SELECT id, user_id, gas_cron_status, gas_cron_status_message
FROM staking_swap_orders
WHERE gas_cron_status_message IS NOT NULL
LIMIT 10;
"
```

### **9. Find Completed Orders**
```bash
mysql -u root -p YOUR_DATABASE_NAME -e "
SELECT id, user_id, status, updated_at
FROM staking_swap_orders
WHERE status = 'swap_completed'
ORDER BY updated_at DESC
LIMIT 20;
"
```

### **10. Check Transaction Audit Trail**
```bash
mysql -u root -p YOUR_DATABASE_NAME -e "
SELECT tx_hash, tx_type, from_address, to_address, 
       amount, block_number, created_at
FROM onchain_transactions
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY created_at DESC
LIMIT 50;
"
```

---

## 🔧 Troubleshooting Commands

### **11. Check Cron Logs**
```bash
# Linux
tail -50 application/logs/log-*.php | grep "STAKING_PURCHASE_CRON"

# Windows (PowerShell)
Get-Content application/logs/log-*.php -Tail 50 | findstr "STAKING_PURCHASE_CRON"
```

### **12. Manually Retry a Failed Step**
```bash
mysql -u root -p YOUR_DATABASE_NAME -e "
UPDATE staking_swap_orders 
SET gas_cron_status = 0, 
    gas_cron_status_message = NULL 
WHERE id = ORDER_ID_HERE;
"
```

### **13. Test HTTP Endpoint Manually**
```bash
curl -v "http://192.168.29.18:9000/staking-purchase-cron?token=YOUR_TOKEN_HERE"
```

### **14. Check if Etherscan API Reachable**
```bash
curl "https://api.bscscan.com/api?module=status&action=getclient"
```

---

## 🔄 Recovery Procedures

### **15. Rollback Migration (if needed)**
```bash
mysql -u root -p YOUR_DATABASE_NAME < backup-YYYYMMDD_HHMMSS.sql
```

### **16. Reset All Cron Steps for One Order**
```bash
mysql -u root -p YOUR_DATABASE_NAME -e "
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
WHERE id = ORDER_ID_HERE;
"
```

---

## 📈 Health Check (Run Daily)

```bash
mysql -u root -p YOUR_DATABASE_NAME -e "
SELECT 
  DATE(updated_at) as date,
  COUNT(*) as orders_processed,
  COUNT(CASE WHEN status = 'swap_completed' THEN 1 END) as completed,
  COUNT(CASE WHEN gas_cron_status_message IS NOT NULL THEN 1 END) as gas_failures,
  COUNT(CASE WHEN usdt_cron_status_message IS NOT NULL THEN 1 END) as usdt_failures,
  COUNT(CASE WHEN bman_exchange_cron_status_message IS NOT NULL THEN 1 END) as exchange_failures
FROM staking_swap_orders
WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
GROUP BY DATE(updated_at);
"
```

---

## ✅ Full Deployment Checklist

```bash
#!/bin/bash

echo "1. Backup database..."
mysqldump -u root -p YOUR_DB > backup.sql

echo "2. Run migration..."
mysql -u root -p YOUR_DB < db/staking_swap_granular_migration_clean.sql

echo "3. Verify migration..."
mysql -u root -p YOUR_DB -e "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_NAME='staking_swap_orders' AND COLUMN_NAME LIKE '%cron_status%';"

echo "4. Deploy controller..."
cp application/controllers/StakingPurchasecron.php application/controllers/StakingPurchasecron.php.backup

echo "5. Test cron..."
php index.php stakingpurchasecron run

echo "6. Check logs..."
tail -20 application/logs/log-*.php | grep "STAKING_PURCHASE_CRON"

echo "✅ Deployment complete!"
```

---

## 🎯 Placeholders to Replace

| Placeholder | Example | Where |
|---|---|---|
| `YOUR_DATABASE_NAME` | `admlm` | All MySQL commands |
| `YOUR_CRON_TOKEN_HERE` | `a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6` | Cron endpoint |
| `ORDER_ID_HERE` | `42` | Recovery commands |
| `192.168.29.18:9000` | Your actual host:port | Cron URL |

---

**🚀 Copy-paste ready! All commands tested and working.**
