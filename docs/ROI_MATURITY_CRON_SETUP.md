# ROI Maturity CRON Setup & Documentation

## Overview

The ROI Maturity CRON system automatically processes staking investments when they reach their maturity date. It calculates final ROI payouts, releases remaining BMAN to user wallets, and records transactions for audit trails.

## Architecture

```
staking_swap_orders (purchase records)
    ↓
    ├─→ maturity_date (when staking completes)
    ├─→ roi_return_status (pending → in_progress → completed)
    └─→ roi_rate (locked at purchase time)

roi_distribution (maturity tracking)
    ├─→ FK: staking_swap_orders_id
    ├─→ principal_amount (original BMAN)
    ├─→ total_roi_earned (calculated ROI)
    ├─→ roi_already_paid (distributed hourly)
    ├─→ roi_remaining (to pay at maturity)
    ├─→ distribution_status (pending/processing/completed/failed)
    └─→ distribution_date (when paid)

onchain_transactions (audit log)
    ├─→ tx_type: 'roi_maturity' (final payout)
    ├─→ amount (ROI released)
    └─→ status: 'completed' or 'failed'
```

## Database Tables

### staking_swap_orders (New Columns)
- `maturity_date` (DATETIME) - When staking completes
- `roi_return_status` (ENUM: pending/in_progress/completed) - Maturity payout status

### roi_distribution (New Table)
Tracks ROI calculation and payout status for each staking investment.

**Key Fields:**
- `id` - Primary key
- `staking_swap_orders_id` - Foreign key reference
- `user_id` - User who owns the stake
- `principal_amount` - Original BMAN invested
- `total_roi_earned` - Total ROI from day 1 to maturity
- `roi_already_paid` - ROI distributed via hourly cron
- `roi_remaining` - ROI left to pay at maturity
- `maturity_date` - When staking completes
- `distribution_status` - Current payout status
- `distribution_date` - When ROI was released
- `tx_hash` - Transaction reference

**SQL:**
```sql
CREATE TABLE roi_distribution (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  staking_swap_orders_id BIGINT UNSIGNED NOT NULL,
  user_id INT(11) NOT NULL,
  principal_amount DECIMAL(20,8),
  roi_rate_percent DECIMAL(10,4),
  total_roi_earned DECIMAL(20,8),
  roi_already_paid DECIMAL(20,8),
  roi_remaining DECIMAL(20,8),
  maturity_date DATETIME,
  distribution_status ENUM('pending','processing','completed','failed'),
  distribution_date DATETIME,
  tx_hash VARCHAR(255),
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (staking_swap_orders_id) REFERENCES staking_swap_orders(id)
);
```

## CRON Job: RoiMaturity_cron

**Location:** `/application/controllers/cron/RoiMaturity_cron.php`

### Endpoint
```
GET /cron/roi_maturity/process
```

### Execution Schedule (Recommended)
```bash
# Run hourly via system cron
0 * * * * curl -s http://localhost/cron/roi_maturity/process

# Or run every 6 hours (less frequent)
0 */6 * * * curl -s http://localhost/cron/roi_maturity/process
```

### Setup Instructions

#### Option 1: Linux/Unix Crontab
```bash
# Edit crontab
crontab -e

# Add hourly execution
0 * * * * curl -s http://localhost/cron/roi_maturity/process

# Or use webhook (requires authentication)
0 * * * * curl -s -H "Authorization: Bearer YOUR_CRON_KEY" \
    http://yourdomain.com/cron/roi_maturity/process
```

#### Option 2: Windows Task Scheduler
```batch
# Create batch file: C:\cron_tasks\roi_maturity.bat
@echo off
curl -s http://localhost/cron/roi_maturity/process

# Schedule via Task Scheduler:
# - Trigger: Hourly
# - Action: Run "roi_maturity.bat"
# - Run with highest privileges
```

#### Option 3: Alternative CRON Services
- **EasyCron** - https://www.easycron.com
- **Cron-job.org** - https://cron-job.org
- **Webcron** - Your hosting provider's cPanel

## Process Flow

### When CRON Runs

1. **Fetch Matured Orders**
   - Query: `staking_swap_orders WHERE maturity_date <= NOW() AND roi_return_status != 'completed'`
   - Returns all investments past their maturity date that haven't been processed

2. **For Each Matured Order:**

   a. **Calculate ROI**
   ```
   Total ROI = Principal × (ROI% / 100) × (Days Elapsed / 365)
   Already Paid = SUM(roi transactions) from onchain_transactions
   Remaining = Total ROI - Already Paid
   ```

   b. **Update Status to "in_progress"**
   - `roi_return_status` → 'in_progress'
   - Prevents duplicate processing

   c. **Create ROI Distribution Record**
   - Insert/update row in `roi_distribution` table
   - Store calculated amounts for audit

   d. **Release ROI to User Wallet**
   - Credit `roi_remaining` to user's earning wallet
   - Update `wallet_ledger` balance

   e. **Record Transaction**
   - Insert into `onchain_transactions`
   - `tx_type`: 'roi_maturity'
   - `status`: 'completed'

   f. **Mark Complete**
   - `roi_return_status` → 'completed'
   - `distribution_status` → 'completed'
   - `distribution_date` → NOW()

3. **Error Handling**
   - If step fails, record error message
   - `roi_return_status` → 'failed'
   - Retry on next cron run

### Example Scenario

```
Purchase Date:    2026-07-09
Duration:         1 Year
Maturity Date:    2027-07-09
Principal:        100 BMAN
ROI Rate:         150%
---
On 2027-07-09 at CRON run:
  Total ROI = 100 × (150/100) × (365/365) = 150 BMAN
  Already Paid (hourly): 145 BMAN
  Remaining:            5 BMAN
  
  → Credit 5 BMAN to earning wallet
  → Record transaction in onchain_transactions
  → Set roi_return_status = 'completed'
```

## Monitoring & Verification

### Check Processing Status

```sql
-- See all matured investments
SELECT id, ref, user_id, maturity_date, roi_return_status
FROM staking_swap_orders
WHERE maturity_date <= NOW()
ORDER BY maturity_date DESC;

-- Check ROI distribution records
SELECT id, user_id, principal_amount, total_roi_earned, 
       roi_already_paid, roi_remaining, distribution_status
FROM roi_distribution
WHERE is_matured = 1
ORDER BY maturity_date DESC;

-- Verify transactions recorded
SELECT * FROM onchain_transactions
WHERE tx_type = 'roi_maturity'
ORDER BY created_at DESC
LIMIT 20;
```

### CRON Log Files

CRON output is sent to browser/logs. Recommend capturing output:

```bash
# Redirect to log file
0 * * * * curl -s http://localhost/cron/roi_maturity/process >> /var/log/roi_maturity_cron.log 2>&1
```

### Manual Testing

```bash
# Test from command line
curl -v http://localhost/cron/roi_maturity/process

# With basic auth
curl -v -u admin:password http://localhost/cron/roi_maturity/process
```

## Integration Points

### From Lending Controller
When staking is purchased in `swap_purchase()`:
- Create initial `roi_distribution` record
- Calculate estimated total ROI
- Set maturity date (purchase date + duration years)
- Set `roi_return_status = 'pending'`

### From Wallet Module
Earning wallet receives ROI at maturity:
- `wallet_type = 'earning'`
- Amount credited automatically by CRON
- No manual intervention needed

### From Transaction Audit
Each maturity payout recorded in `onchain_transactions`:
- `tx_type = 'roi_maturity'`
- User can see complete ROI history
- Audit trail for compliance

## Troubleshooting

### CRON Not Running
1. Check hosting provider allows cron jobs
2. Verify URL is publicly accessible
3. Check server logs for errors
4. Test manual execution: `curl http://yoursite.com/cron/roi_maturity/process`

### ROI Not Released
1. Check `maturity_date` is past TODAY
2. Check `roi_return_status` is not 'completed' already
3. Verify `roi_distribution` record exists
4. Check wallet_ledger has proper user_id for earnings wallet

### Duplicate Payouts
- CRON checks `roi_return_status != 'completed'` before processing
- Safe for multiple concurrent runs
- Already-paid ROI is subtracted from total

### Wrong ROI Amount
- Verify `roi_rate` in staking_swap_orders (locked at purchase)
- Check `duration_years` is correct
- Calculate manually: `Principal × (ROI% / 100) × (Days / 365)`

## Security Considerations

1. **CRON Endpoint Protection**
   - Consider adding secret token: `?cron_key=YOUR_SECRET`
   - Verify IP whitelist if possible
   - Log all executions

2. **Wallet Updates**
   - Use database transactions for consistency
   - Prevent race conditions on concurrent updates
   - Audit all balance changes

3. **Data Integrity**
   - Foreign key constraints prevent orphaned records
   - Status transitions are one-way (prevent reprocessing)
   - All changes timestamped

## Configuration

No special configuration needed. The CRON works with:
- Existing `staking_swap_orders` table structure
- Standard `wallet_ledger` for user wallets
- Standard `onchain_transactions` audit table

All settings are database-driven (maturity dates, ROI rates stored with each purchase).

## Example Output

```
=== ROI MATURITY CRON PROCESS ===
Started at: 2027-07-09 12:00:15

Found 3 matured staking orders

Processing Order ID: 1 (User: 5)
  Principal: 100.00000000 BMAN
  ROI Rate: 150%
  Total ROI Earned: 150.00000000 BMAN
  Already Paid: 145.00000000 BMAN
  Remaining: 5.00000000 BMAN
  ✓ Order 1 processed successfully

Processing Order ID: 2 (User: 7)
  Principal: 50.00000000 BMAN
  ROI Rate: 200%
  Total ROI Earned: 100.00000000 BMAN
  Already Paid: 100.00000000 BMAN
  Remaining: 0.00000000 BMAN
  ✓ Order 2 processed successfully

Processing Order ID: 3 (User: 12)
  ✗ Error processing order 3: Failed to calculate ROI

=== CRON SUMMARY ===
Processed: 2
Failed: 1
Completed at: 2027-07-09 12:00:42
```

## Next Steps

1. ✅ Database tables created (maturity_date, roi_distribution)
2. ✅ CRON controller implemented
3. ✅ Controller updated to create ROI records at purchase
4. ⬜ Set up cron job execution (see Setup Instructions above)
5. ⬜ Test with past-maturity staking records
6. ⬜ Monitor first few executions
7. ⬜ Adjust frequency if needed

