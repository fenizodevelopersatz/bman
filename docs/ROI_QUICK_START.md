# ROI Management System - Quick Start Reference

## **🚀 What's Fixed & Ready**

### **Database Issues FIXED**
❌ **Old Error:** Foreign key constraint incorrectly formed  
✅ **Solution:** `roi_complete_system_migration.sql` (corrected syntax)

### **New Tables Added**
✅ `roi_gas_fees` - Track gas fees for each distribution  
✅ `roi_failed_transactions` - Queue for auto-retry of failed ROI  
✅ `roi_gas_budget` - Budget tracking & limits  

### **Improved Cron (V2)**
✅ **RoiUnifiedCronV2** - Enhanced with:
- Gas fee validation before each payment
- Automatic retry of failed transactions
- 3-phase execution (Monthly → Maturity → Retry)
- Comprehensive error logging

### **Gas Fee Management**
✅ Tracks gas fees per transaction  
✅ Validates budget before each payment  
✅ Prevents over-spending  
✅ Records actual gas used  

### **Failed Transaction Handling**
✅ Auto-detects failed distributions  
✅ Auto-retries up to 3 times  
✅ Exponential backoff: +2h, +4h, +6h  
✅ Admin can manually retry or resolve  

---

## **📋 Deployment Steps (5 Minutes)**

### **1️⃣ Apply Database Migration**
```bash
mysql -h 192.168.29.18 -u root -p"Root@123" admlm < db/roi_complete_system_migration.sql
```

### **2️⃣ Initialize Gas Budget**
```sql
-- Run in MySQL:
INSERT INTO roi_gas_budget (budget_type, period_start, period_end, total_budget_usdt, remaining_usdt)
VALUES ('monthly', '2026-07-01', '2026-07-31', 5000.00, 5000.00),
       ('daily', CURDATE(), CURDATE(), 200.00, 200.00);
```

### **3️⃣ Test Everything**
```bash
php testing/roi_system_test.php
```
Expected: ✓ All critical tests passed

### **4️⃣ Schedule Cron**
```bash
crontab -e
# Add:
0 0 * * * cd /path/to/project && php index.php roi-unified-cron-v2
```

### **5️⃣ Access Admin Dashboard**
```
http://yoursite.com/admin/staking/roimanagement
```

---

## **📊 Admin Dashboard Features**

### **Statistics Cards**
- Total BMAN Distributed (all-time)
- Successful Distributions (count)
- Pending/Failed (count)
- Failed Transactions (count)

### **Upcoming Maturity** (Next 30 days)
- Shows stakes about to mature
- Expected ROI amounts
- Helps plan liquidity

### **Gas Fee Summary**
- Total spent vs. budget
- Daily/monthly breakdown
- Budget remaining

### **Failed Transactions Queue**
- List of failed distributions
- Reason for failure
- Retry count & next retry time
- Manual action buttons

### **Distribution History**
- Full audit trail with filters
- User, Plan Type, ROI Type, Status, Date Range
- Pagination & CSV export

---

## **🔄 How ROI Distribution Works**

### **Day 5, 15, 25 (Monthly Payments)**
```
Regular/Combo Stakes
  ↓
Calculate: principal × (monthly_rate ÷ 100) ÷ 3
  ↓
Check Gas Budget
  ↓
Create wallet entry (earning wallet)
  ↓
Record gas fee
  ↓
Log to roi_distribution_audit
```

**Example:** 100,000 BMAN @ 2.3% monthly
- Per-payment: 100,000 × (2.3% ÷ 3) = 767 BMAN
- 3 payments/month = 2,301 BMAN
- Gas fee: 0.5 USDT

### **Maturity Date (One-Time Payouts)**
```
Fixed/Combo Stakes + Maturity Date Reached
  ↓
Calculate: principal × (fixed_rate ÷ 100)
  ↓
Check Gas Budget (higher fee: 1.0 USDT)
  ↓
Create wallet entry (earning wallet)
  ↓
Update stake status → "matured"
  ↓
Log to roi_distribution_audit
```

**Example:** 100,000 BMAN Fixed @ 150%
- ROI Amount: 100,000 × 150% = 150,000 BMAN
- Gas fee: 1.0 USDT
- Paid once at term end

### **Auto-Retry of Failed Transactions**
```
Check roi_failed_transactions table
  ↓
Get transactions with status = "pending_retry"
  ↓
Validate user & stake still exist
  ↓
Retry distribution
  ↓
If success: mark "resolved"
If fail: increment retry_count, schedule next retry
  ↓
Max 3 retries, then marked "resolved"
```

---

## **💰 Gas Fee Flow**

### **Before Distribution:**
1. Calculate ROI amount
2. Check: `isBudgetExceeded(gas_fee)`
3. If exceeded → log to failed_transactions, retry next cycle
4. If OK → proceed with distribution

### **After Distribution:**
1. Record gas fee in `roi_gas_fees` table
2. Link to `roi_distribution_audit` (roi_audit_id)
3. Update `roi_gas_budget.remaining_usdt`
4. Admin can view on dashboard

### **Budget Limits:**
- **Monthly:** 5,000 USDT (adjustable)
- **Daily:** 200 USDT (adjustable)
- Prevents overspending on gas fees

---

## **⚠️ Handling Issues**

### **Issue: "Gas budget exceeded"**
**Fix:**
```sql
UPDATE roi_gas_budget 
SET total_budget_usdt = 10000.00, remaining_usdt = 10000.00
WHERE budget_type = 'monthly';
```

### **Issue: Cron didn't run**
**Solution:**
1. Check cron logs: `tail -f /var/log/cron`
2. Verify cron is scheduled: `crontab -l`
3. Manually retry from admin dashboard

### **Issue: ROI not credited**
**Debug:**
```sql
-- Check if distribution was logged
SELECT * FROM roi_distribution_audit WHERE user_id = 123;

-- Check wallet entry
SELECT * FROM wallet_ledger WHERE user_id = 123 AND transaction_type LIKE 'roi%';

-- Check final balance
SELECT * FROM wallet WHERE user_id = 123;
```

---

## **📁 File Locations**

```
Database:
├─ db/roi_complete_system_migration.sql       ← USE THIS!

Models:
├─ application/models/RoiAudit_model.php
├─ application/models/RoiGasManagement_model.php

Controllers:
├─ application/controllers/RoiUnifiedCronV2.php  ← USE THIS!

Testing:
├─ testing/roi_system_test.php

Docs:
├─ docs/ROI_DEPLOYMENT_GUIDE.md
├─ docs/ROI_MANAGEMENT_SYSTEM.md
├─ docs/ROI_QUICK_START.md
```

---

## **🎯 Admin Actions Available**

### **On Dashboard:**

1. **View Statistics** - See total distributions, success rate, failures
2. **Check Gas Budget** - Know remaining budget
3. **Review Maturity Schedule** - Plan for upcoming payouts
4. **Filter History** - Search distributions by user/plan/status/date
5. **Retry Failed** - Manually process failed transactions
6. **Export CSV** - Download full audit trail
7. **Monitor Retries** - See failed transactions & retry status

---

## **✅ Verification Checklist**

After deployment, verify:

- [ ] Database migration applied without errors
- [ ] All 7 ROI tables created (`SHOW TABLES LIKE 'roi%'`)
- [ ] Test script passes: `php testing/roi_system_test.php`
- [ ] Gas budget initialized
- [ ] Cron scheduled & running
- [ ] Admin dashboard loads at `/admin/staking/roimanagement`
- [ ] Sample distributions visible in history
- [ ] Failed transaction retry working
- [ ] Gas fees recorded in `roi_gas_fees`

---

## **🔧 Configuration**

### **Gas Fee Amounts:**
- Monthly: 0.5 USDT (line 83 in RoiUnifiedCronV2.php)
- Maturity: 1.0 USDT (line 152 in RoiUnifiedCronV2.php)

### **Retry Timing:**
- Backoff multiplier: 2 hours (adjustable)
- Max retries: 3 attempts

### **Budget Limits:**
- Monthly: 5,000 USDT (adjustable via SQL)
- Daily: 200 USDT (adjustable via SQL)

---

## **📞 Support**

**Common Commands:**
```bash
# Test everything
php testing/roi_system_test.php

# Check today's executions
mysql -e "SELECT * FROM roi_cron_execution WHERE DATE(execution_date) = DATE(NOW());"

# Check for failures
mysql -e "SELECT * FROM roi_distribution_audit WHERE status = 'failed' AND DATE(created_at) >= DATE(NOW()-7);"

# Monitor gas budget
mysql -e "SELECT * FROM roi_gas_budget WHERE period_start = DATE_TRUNC('MONTH', NOW());"
```

---

**Status:** ✅ Production Ready  
**Version:** 2.0 (with Gas Fees + Auto-Retry)  
**Last Updated:** July 14, 2026  

🚀 **Ready to Deploy!**
