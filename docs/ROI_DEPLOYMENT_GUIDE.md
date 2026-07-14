# ROI Management System - Complete Deployment Guide

## **Quick Start**

### **Step 1: Fix Database Migration**

The original migration had a foreign key error. Use the fixed version:

```bash
# Copy the correct migration file
cp db/roi_complete_system_migration.sql /tmp/

# Import into database
mysql -h 192.168.29.18 -u root -p"Root@123" admlm < /tmp/roi_complete_system_migration.sql
```

### **Step 2: Initialize Gas Budget**

```sql
-- Create monthly gas budget (adjust amounts as needed)
INSERT INTO roi_gas_budget (budget_type, period_start, period_end, total_budget_usdt, remaining_usdt)
VALUES ('monthly', DATE_TRUNC('MONTH', NOW()), LAST_DAY(NOW()), 5000.00, 5000.00);

-- Create daily gas budget
INSERT INTO roi_gas_budget (budget_type, period_start, period_end, total_budget_usdt, remaining_usdt)
VALUES ('daily', DATE(NOW()), DATE(NOW()), 200.00, 200.00);
```

### **Step 3: Test the System**

```bash
cd /path/to/project
php testing/roi_system_test.php
```

Expected output:
```
✓ Database connected successfully
✓ All ROI tables exist
✓ ROI calculations verified
✓ All critical tests passed
```

### **Step 4: Schedule Cron Job**

```bash
# Add to crontab (runs daily at midnight)
crontab -e

# Add this line:
0 0 * * * cd /path/to/project && php index.php roi-unified-cron-v2
```

### **Step 5: Load Models in Controllers**

In your controller `__construct()`:

```php
$this->load->model('RoiAudit_model');
$this->load->model('RoiGasManagement_model');
```

---

## **File Structure**

```
Database:
├─ db/roi_complete_system_migration.sql          ← FIXED version, use this!
├─ db/roi_audit_tracking_migration.sql           ← Old version, deprecated

Models:
├─ application/models/RoiAudit_model.php
├─ application/models/RoiGasManagement_model.php

Controllers:
├─ application/controllers/RoiUnifiedCron.php     ← V1 (basic)
├─ application/controllers/RoiUnifiedCronV2.php   ← V2 (with gas fees + retry) ← USE THIS
├─ application/controllers/admin/staking/RoiManagement.php

Views:
├─ application/views/admin/staking/roi_management.php

Testing:
├─ testing/roi_system_test.php
```

---

## **How ROI V2 Works**

### **Three-Phase Execution:**

**Phase 1: Monthly ROI (Days 5, 15, 25)**
- Processes Regular & Combo plan monthly payments
- Calculates: Principal × (Monthly Rate ÷ 100) ÷ 3 = per-payment amount
- Checks gas budget before each payment
- Records gas fee in `roi_gas_fees` table
- Logs to `roi_distribution_audit`

**Phase 2: Maturity ROI (Maturity Date)**
- Processes Fixed & Combo plan maturity payouts
- Pays entire ROI when term ends
- Updates stake status to "matured"
- Marks `roi_maturity_schedule.distributed = 1`

**Phase 3: Failed Transaction Retry**
- Queries `roi_failed_transactions` for pending retries
- Re-attempts distribution with validation
- Updates retry count
- Marks resolved when successful

### **Gas Fee Handling:**

**Before Distribution:**
- Check if gas fee budget exceeded
- If exceeded: log to failed_transactions, retry next cycle
- If available: deduct from admin wallet, proceed

**After Distribution:**
- Record actual gas fee paid
- Update budget remaining amount
- Link to audit trail (roi_audit_id)

---

## **Admin Dashboard Access**

Navigate to: `http://yoursite.com/admin/staking/roimanagement`

### **Key Sections:**

1. **Statistics Cards**
   - Total BMAN Distributed
   - Successful Distributions (count)
   - Pending/Retries (count)
   - Failed Transactions (count)

2. **Upcoming Maturity Payouts** (Next 30 days)
   - Shows stakes approaching maturity
   - Expected ROI amounts
   - Preparation for liquidity needs

3. **Gas Fee Summary**
   - Total gas fees charged
   - Budget remaining
   - Daily vs. monthly breakdown

4. **Failed Transactions Queue**
   - List of transactions needing retry
   - Reason for failure
   - Retry count
   - Manual action buttons

5. **Distribution History**
   - Full audit trail
   - Filter by user, plan, status, date
   - Pagination & CSV export

---

## **Managing Failed Transactions**

### **Automatic Retry**

Failed transactions automatically retry:
- 1st retry: +2 hours
- 2nd retry: +4 hours
- 3rd retry: +6 hours
- Max 3 retries, then marked "resolved" with notes

### **Manual Actions (Admin)**

1. **Retry All Failed:**
   - Dashboard button "Retry Failed"
   - Processes all pending_retry transactions
   - Shows count processed

2. **Mark as Resolved:**
   - Add notes explaining resolution
   - Removes from retry queue
   - Records in `resolution_notes`

3. **Investigate Gas Fee Issues:**
   - Check if failure was due to budget
   - Review `gas_fee_issue` flag
   - Increase budget if needed

---

## **Database Tables Explained**

### `roi_distribution_audit`
Every ROI payment logged here.

| Field | Purpose |
|-------|---------|
| `roi_type` | monthly, maturity, retry |
| `plan_type` | fixed, regular, combo |
| `status` | pending, processing, success, failed |
| `retry_count` | Number of times retried |
| `tx_hash` | Blockchain transaction |

### `roi_gas_fees`
Gas fee tracking for each distribution.

| Field | Purpose |
|-------|---------|
| `gas_fee_usdt` | Cost in USDT |
| `gas_fee_bman` | Cost in BMAN (if applicable) |
| `status` | pending, paid, failed, refunded |
| `tx_hash` | Gas transaction hash |

### `roi_failed_transactions`
Queue for retry handling.

| Field | Purpose |
|-------|---------|
| `failure_reason` | Why it failed |
| `gas_fee_issue` | 1 if due to gas budget |
| `retry_count` | Attempts so far |
| `next_retry_at` | When to try again |
| `status` | failed, pending_retry, resolved |

### `roi_gas_budget`
Budget tracking and limits.

| Field | Purpose |
|-------|---------|
| `total_budget_usdt` | Max allowed per period |
| `total_spent_usdt` | Amount spent |
| `remaining_usdt` | Balance left |

---

## **Troubleshooting**

### **Issue: "Gas budget exceeded"**

**Check:**
```sql
SELECT * FROM roi_gas_budget 
WHERE period_start <= DATE(NOW()) 
AND period_end >= DATE(NOW());
```

**Fix:**
```sql
UPDATE roi_gas_budget 
SET total_budget_usdt = 10000.00, remaining_usdt = 10000.00
WHERE budget_type = 'monthly' 
AND period_start = DATE_TRUNC('MONTH', NOW());
```

### **Issue: Cron didn't run**

**Check:**
```sql
SELECT * FROM roi_cron_execution 
WHERE DATE(execution_date) = DATE(NOW());
```

**Check cron logs:**
```bash
tail -f /var/log/cron
grep CRON /var/log/syslog
```

### **Issue: ROI not credited to user**

**Verify:**
```sql
-- Check distribution was recorded
SELECT * FROM roi_distribution_audit WHERE user_id = 123;

-- Check wallet ledger entry
SELECT * FROM wallet_ledger WHERE user_id = 123 AND transaction_type LIKE 'roi%';

-- Check user balance
SELECT * FROM wallet WHERE user_id = 123;
```

---

## **Real-Time Monitoring**

### **Daily Checklist:**

```sql
-- 1. Check today's executions
SELECT * FROM roi_cron_execution WHERE DATE(execution_date) = DATE(NOW());

-- 2. Check for failures
SELECT * FROM roi_distribution_audit 
WHERE DATE(actual_payment_date) = DATE(NOW()) 
AND status = 'failed';

-- 3. Check gas budget
SELECT * FROM roi_gas_budget 
WHERE period_start = DATE_TRUNC('MONTH', NOW());

-- 4. Check pending retries
SELECT COUNT(*) as pending FROM roi_failed_transactions 
WHERE status = 'pending_retry';

-- 5. Check upcoming maturity
SELECT COUNT(*) as upcoming FROM roi_maturity_schedule 
WHERE maturity_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)
AND distributed = 0;
```

---

## **Configuration Options**

### **Gas Fee Amounts** (in `RoiUnifiedCronV2.php`)

```php
// Line: Check gas budget
$gas_fee = 0.5;  // Monthly distribution gas fee (USDT)
$gas_fee = 1.0;  // Maturity distribution gas fee (USDT)
```

Adjust based on network conditions.

### **Retry Timing** (in `RoiGasManagement_model.php`)

```php
// Line: Next retry scheduling
$next_retry = date('Y-m-d H:i:s', strtotime('+' . (($retry_count + 1) * 2) . ' hours'));
```

Change `2` to adjust hours between retries.

### **Max Retry Count** (in `RoiGasManagement_model.php`)

```php
'max_retries' => 3,  // Change to allow more/fewer attempts
```

---

## **Deployment Checklist**

- [ ] Database migration applied successfully
- [ ] All ROI tables created without errors
- [ ] Test script runs: `php testing/roi_system_test.php`
- [ ] Gas budget initialized (monthly + daily)
- [ ] Cron scheduled: `0 0 * * * php index.php roi-unified-cron-v2`
- [ ] Models loaded in controller
- [ ] Admin dashboard accessible at `/admin/staking/roimanagement`
- [ ] Sample ROI distributions working (test with 1-2 stakes)
- [ ] Gas fees recorded in `roi_gas_fees` table
- [ ] Failed transaction retry working
- [ ] Dashboard displaying statistics correctly

---

## **Support & Maintenance**

### **Weekly Tasks:**
- Review failed transactions count
- Check gas fee spending vs. budget
- Verify maturity payments processed on time

### **Monthly Tasks:**
- Audit distribution history
- Review gas budget usage
- Generate CSV export for records

### **Emergency Procedures:**
1. If cron fails: Use admin "Retry Failed" button
2. If gas budget exceeded: Increase budget via SQL
3. If ledger not credited: Check `wallet_ledger` entries
4. If member complains no ROI: Query their `roi_distribution_audit` records

---

## **Performance Metrics**

Expected execution times:

| Phase | Time | Notes |
|-------|------|-------|
| Monthly ROI (100 stakes) | ~30 sec | One payment day |
| Maturity ROI (10 payouts) | ~20 sec | Runs daily |
| Failed Retries (5 stakes) | ~10 sec | Automatic |
| **Total Daily** | **~2 min** | Small impact on server |

---

**Last Updated:** July 14, 2026  
**Version:** 2.0 (with Gas Fees & Retry Logic)  
**Status:** Production Ready  

✅ All systems ready for deployment!
