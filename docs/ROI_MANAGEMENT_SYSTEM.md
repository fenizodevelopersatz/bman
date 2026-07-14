# ROI Management System - Complete Implementation Guide

## Overview

This document describes the complete ROI management system with proper database tracking, validation, retry logic, and admin dashboard.

---

## 1. Database Schema

### Tables Created (in `roi_audit_tracking_migration.sql`)

#### `roi_distribution_audit`
Tracks every ROI distribution to members.

| Column | Purpose |
|--------|---------|
| `id` | Primary key |
| `user_id` | Member receiving ROI |
| `stake_id` | Associated staking purchase |
| `roi_type` | `monthly`, `maturity`, `retry` |
| `plan_type` | `fixed`, `regular`, `combo` |
| `principal_amount` | Stake amount (BMAN) |
| `roi_rate_percent` | ROI rate applied |
| `roi_amount` | Actual BMAN distributed |
| `payment_date` | Expected payment date |
| `actual_payment_date` | When it was actually paid |
| `execution_date` | Cron execution date |
| `status` | `pending`, `processing`, `success`, `failed`, `retry` |
| `retry_count` | Number of retry attempts |
| `ledger_id` | Reference to wallet_ledger table |

#### `roi_cron_execution`
Tracks each cron run (success/failure).

| Column | Purpose |
|--------|---------|
| `execution_date` | Date cron ran |
| `cron_type` | `monthly_payment` or `maturity_payout` |
| `status` | `pending`, `running`, `success`, `failed` |
| `total_stakes_processed` | Number of stakes handled |
| `total_stakes_failed` | Number that failed |
| `total_amount_distributed` | Total BMAN paid out |
| `retry_count` | Retry attempts |

#### `roi_maturity_schedule`
Pre-calculated maturity dates for all stakes.

| Column | Purpose |
|--------|---------|
| `maturity_date` | When stake matures |
| `expected_roi_amount` | Total ROI for term |
| `fixed_roi_amount` | For Fixed/Combo (maturity payment) |
| `regular_roi_amount` | For Regular/Combo (already paid monthly) |
| `distributed` | Has maturity ROI been paid? |

#### `roi_monthly_schedule`
Tracks expected monthly payments.

| Column | Purpose |
|--------|---------|
| `payment_month_year` | Month (e.g., 2026-07-01) |
| `payment_days` | Days of payment (e.g., "5,15,25") |
| `monthly_roi_amount` | Total for month |
| `payments_completed` | How many days executed |

---

## 2. ROI Distribution Logic

### Fixed Plan
```
Payment Schedule: ONLY at maturity date
ROI Amount: principal × (fixed_rate / 100)
Example: 100,000 BMAN × 150% = 150,000 BMAN (paid when 2-year term ends)
Status: marked as "matured" after payout
```

### Regular Plan
```
Payment Schedule: Days 5, 15, 25 of each month
Monthly ROI: principal × (monthly_rate / 100)
Per-Payment: monthly_roi / 3
Example: 100,000 BMAN × 2.3% = 2,300/month = 767 per payment day
Duration: 24 monthly payments = 2,300 × 24 = 55,200 total
```

### Combo Plan
```
Payment Schedule: BOTH monthly + maturity
Monthly Portion: 100% × (regular_rate ÷ 100) × 12 × years = paid monthly
Maturity Portion: 100% × (fixed_rate ÷ 100) = paid at maturity
Example: 
  - Monthly: 100,000 × 2.3% × 24 = 55,200 (days 5,15,25)
  - Maturity: 100,000 × 150% = 150,000 (when term ends)
  - Total ROI: 205,200
```

---

## 3. Handling Missed Cron Executions

### Detection & Validation

If a cron day is missed:

1. **System Checks:**
   - `roi_cron_execution` table: Look for `status != 'success'` on payment day
   - `roi_distribution_audit` table: Check for gaps in `execution_date`
   - `roi_monthly_schedule`: Compare `payments_completed` vs expected

2. **Validation Before Retry:**
   - User must exist in `members` table
   - Stake must exist and still be `active`
   - ROI amount > 0 and valid
   - Payment hasn't already been double-paid

3. **Automatic Retry:**
   - Cron automatically attempts retry on next execution
   - Retry count incremented up to 3 attempts
   - Failed records marked with error message

### Manual Retry (Admin Action)

**Admin Panel:**
- Navigate to: `admin/staking/roimanagement`
- Click **"Retry Failed"** button
- System processes all pending/failed ROI
- Ledger entries created for each retry
- Admin receives count of processed records

---

## 4. Admin Dashboard (`ROI Management`)

**URL:** `http://yoursite.com/admin/staking/roimanagement`

### Sections:

#### A. Statistics Cards
- Total BMAN Distributed
- Successful Distributions (count)
- Pending/Retries (count)
- Missed Executions (count)

#### B. Upcoming Maturity Payouts
- Shows next 30 days of maturity dates
- Principal amount, expected ROI
- Allows pre-planning for liquidity

#### C. Filters & Search
- User ID
- Plan Type (Fixed/Regular/Combo)
- ROI Type (Monthly/Maturity)
- Status (Success/Pending/Failed)
- Date Range

#### D. Distribution History Table
- Date, User ID, Stake ID
- Plan type, ROI type, Rate %
- Amount distributed, Status
- Execution datetime

#### E. Pagination & Export
- CSV export with full history
- Sortable by all columns

---

## 5. Models & Controllers

### Models

#### `RoiAudit_model`

Key methods:

```php
// Log distribution
logROIDistribution($data)  // Records to roi_distribution_audit

// Query history
getROIHistory($filters, $limit, $offset)
countROIHistory($filters)

// Validation & retry
getPendingROIForRetry()
getPendingMaturityPayouts()

// Reports
getUserROISummary($user_id)
getROISummaryByDate($from, $to)
getUpcomingMaturityDates()

// Tracking
logCronExecution($data)
getMissedExecutions($from, $to)

// Mark complete
markMaturityDistributed($stake_id, $tx_hash)
```

### Controllers

#### `RoiManagement` (Admin)

Endpoints:

| Method | Route | Purpose |
|--------|-------|---------|
| GET | `/admin/staking/roimanagement` | Dashboard view |
| POST | `/admin/staking/roimanagement/validate_and_retry` | Retry failed ROI |
| GET | `/admin/staking/roimanagement/user_summary/:id` | User ROI summary (AJAX) |
| GET | `/admin/staking/roimanagement/export_csv` | Export CSV |

---

## 6. Unified ROI Cron (`RoiUnifiedCron`)

Runs automatically. Two processes:

### Process 1: Monthly ROI (Days 5, 15, 25)

```javascript
// Check current day
if (current_day in [5, 15, 25]) {
  // Find all Regular & Combo stakes
  // Calculate: monthly_rate ÷ 3 = per-payment amount
  // Create wallet_ledger entry
  // Log to roi_distribution_audit
}
```

**Example:**
- Stake: 100,000 BMAN, Plan: Regular, Rate: 2.3%
- Day 5: 100,000 × (2.3% ÷ 3) = 767 BMAN → earning wallet
- Day 15: 767 BMAN → earning wallet
- Day 25: 767 BMAN → earning wallet
- Total month: 2,301 BMAN logged to audit

### Process 2: Maturity ROI (Maturity Date)

```javascript
// Check stakes where maturity_date <= today
// Fixed: Pay entire ROI at once
// Combo: Pay fixed portion (regular already paid monthly)

// Create wallet_ledger entry
// Update stake status to "matured"
// Log to roi_distribution_audit
// Mark roi_maturity_schedule.distributed = 1
```

**Example:**
- Stake: 100,000 BMAN, Plan: Fixed, Rate: 150%, Term: 2 years
- At maturity date: 100,000 × 150% = 150,000 BMAN → earning wallet
- Stake status: "matured"

---

## 7. Flow Diagram: Admin Hot Wallet → User's Earning Wallet

```
┌─────────────────────────────────────────────────────────┐
│ Admin Wallet (Hot Wallet - Blockchain)                  │
│ Holds: USDT for gas + BMAN for distributions            │
└──────────────┬──────────────────────────────────────────┘
               │
               ↓
┌─────────────────────────────────────────────────────────┐
│ ROI Unified Cron (Scheduled Daily)                      │
│ ✓ Checks day of month (5, 15, 25)                      │
│ ✓ Loads all Regular/Combo stakes                       │
│ ✓ Calculates monthly ROI needed                        │
│ ✓ Checks maturity dates for Fixed/Combo               │
└──────────────┬──────────────────────────────────────────┘
               │
               ↓
┌─────────────────────────────────────────────────────────┐
│ Validation Step                                         │
│ ✓ Verify user exists                                  │
│ ✓ Verify stake active/exists                          │
│ ✓ Verify ROI amount > 0                               │
│ ✓ Check no double-payment                             │
└──────────────┬──────────────────────────────────────────┘
               │
               ↓
┌─────────────────────────────────────────────────────────┐
│ Distribute to User's Earning Wallet                     │
│ → Create wallet_ledger entry                           │
│ → Update member wallet balance                         │
│ → Record on blockchain (tx_hash)                       │
└──────────────┬──────────────────────────────────────────┘
               │
               ↓
┌─────────────────────────────────────────────────────────┐
│ Log to ROI Audit Trail                                  │
│ → roi_distribution_audit (record created)              │
│ → roi_cron_execution (mark success/fail)               │
│ → roi_maturity_schedule (if matured)                   │
└─────────────────────────────────────────────────────────┘
```

---

## 8. Setup Instructions

### Step 1: Create Database Tables

```bash
mysql -h yourhost -u youruser -p yourdb < db/roi_audit_tracking_migration.sql
```

### Step 2: File Locations

```
Models:
├─ application/models/RoiAudit_model.php

Controllers:
├─ application/controllers/RoiUnifiedCron.php
├─ application/controllers/admin/staking/RoiManagement.php

Views:
├─ application/views/admin/staking/roi_management.php

Cron Lab:
├─ application/controllers/admin/wallet/Cronlab.php (updated)

Database:
├─ db/roi_audit_tracking_migration.sql
```

### Step 3: Load Models in Controllers

```php
// In controller __construct():
$this->load->model('RoiAudit_model');
$this->load->model('member/Walletledger_model');
```

### Step 4: Schedule Cron

```bash
# Daily execution (recommended 00:15 to ensure day 5, 15, 25 execute)
0 0 * * * /usr/bin/php /path/to/index.php roi-distribution-unified
```

---

## 9. Monitoring Checklist

- [ ] `roi_cron_execution` has recent records with `status='success'`
- [ ] `roi_distribution_audit` shows daily distributions on payment days
- [ ] `roi_maturity_schedule` shows maturity dates marked as `distributed=1`
- [ ] Admin dashboard shows positive totals for "Successful Distributions"
- [ ] No records in "Missed Executions" list
- [ ] Member wallet balances reflect ROI credits in earning wallet
- [ ] Blockchain transactions (tx_hash) recorded for each payment

---

## 10. Troubleshooting

### Issue: Cron didn't run on day 5, 15, or 25

**Check:**
1. `roi_cron_execution` table for that date
2. Cron server logs: `tail -f /var/log/cron`
3. Cron user permissions (can run PHP)
4. Database connection from cron environment

**Retry:**
1. Go to Admin ROI Management
2. Click "Retry Failed"
3. Check audit log for new entries

### Issue: Member didn't receive ROI

**Check:**
1. `roi_distribution_audit` for their user_id
2. `wallet_ledger` for corresponding credit
3. Member wallet balance in earning wallet
4. Stake status: must be `active` (not `completed` or `matured`)

### Issue: Double-payment detected

**Prevent:**
- System checks `roi_distribution_audit` before creating duplicate
- Use `ledger_id` to link to actual transaction
- Manual audit via `SELECT * FROM roi_distribution_audit WHERE status != 'success'`

---

## 11. Real ROI Value Distribution Example

**Scenario: 100,000 BMAN stake, Combo plan, 2-year term**

| Month | Day | Regular Payment | Fixed at Maturity | Notes |
|-------|-----|-----------------|------------------|-------|
| Jul | 5 | 767 BMAN | — | Month 1, payment 1 |
| | 15 | 767 BMAN | — | Month 1, payment 2 |
| | 25 | 767 BMAN | — | Month 1, payment 3 |
| | Total | 2,301 BMAN | — | Monthly total |
| Aug-Jun (23 more months) | 5,15,25 | 2,301 × 23 | — | Ongoing monthly |
| 2-Year Maturity (Jul 2028) | — | — | 150,000 BMAN | At term end |
| **Grand Total** | | **55,200 BMAN** | **150,000 BMAN** | **205,200 Total ROI** |

**User's Earning Wallet Growth:**
- Start: 0
- After Month 1: 2,301
- After Month 12: 27,612
- After Month 24: 55,200 + 150,000 = 205,200

---

## 12. Key Features Summary

✅ **Automatic Daily Execution** - Cron handles all distributions  
✅ **Missed Execution Detection** - Auto-retry on failures  
✅ **Plan-wise Calculation** - Fixed/Regular/Combo each handled correctly  
✅ **Duration-wise Distribution** - 2Y/3Y/5Y ROI properly calculated  
✅ **Real-time Admin Tracking** - Complete audit trail visible  
✅ **User Wallet Integration** - Direct credit to earning wallet  
✅ **Maturity Date Tracking** - Pre-calculated schedules  
✅ **Retry Logic** - Failed distributions reattempted automatically  
✅ **CSV Export** - Full history downloadable  
✅ **Validation Checks** - Prevents double-payment & errors  

---

## 13. Support & Maintenance

**Regular Tasks:**
- Check ROI Management dashboard weekly
- Monitor `missed_executions` count (should be 0)
- Verify maturity payouts process correctly
- Audit `roi_distribution_audit` for any `failed` status

**Emergency Actions:**
1. If cron fails: Use admin "Retry Failed" button
2. If ledger not credited: Check `wallet_ledger` for entry
3. If member has no earnings: Query `roi_distribution_audit` for their user_id

---

**Last Updated:** July 14, 2026  
**Version:** 1.0  
**Status:** Production Ready
