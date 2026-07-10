# ROI Maturity CRON - Quick Start Guide

## What Was Built

Complete automated ROI maturity processing system for staking investments.

### ✅ What's Ready

1. **Database** (All tables created)
   - `staking_swap_orders`: Added maturity_date, roi_return_status, roi_rate columns
   - `roi_distribution`: New tracking table for ROI payouts
   - `onchain_transactions`: Existing audit log (tx_type='roi_maturity')

2. **CRON Job** (`/application/controllers/cron/RoiMaturity_cron.php`)
   - Automatically processes matured investments
   - Calculates remaining ROI
   - Credits to user earning wallet
   - Records transactions

3. **Controller Integration** (`Lendingcontroller.php`)
   - Creates roi_distribution record at purchase
   - Calculates maturity date (purchase date + duration years)
   - Initializes status tracking

4. **Documentation**
   - Full setup guide with examples
   - Crontab configuration for Linux/Windows
   - Monitoring & troubleshooting

## Quick Setup (3 Steps)

### Step 1: Run Database Migration ✅ (Already Done)
```bash
cd db/
php run_roi_distribution_migration.php
```
✓ roi_distribution table created
✓ Maturity fields added to staking_swap_orders

### Step 2: Set Up CRON Job

**Linux/Unix (crontab):**
```bash
crontab -e
# Add this line:
0 * * * * curl -s http://yoursite.com/cron/roi_maturity/process
```

**Windows (Task Scheduler):**
1. Create batch file with: `curl -s http://localhost/cron/roi_maturity/process`
2. Schedule hourly execution
3. Run with admin privileges

**Alternative (EasyCron, Cron-job.org):**
Add webhook: `http://yoursite.com/cron/roi_maturity/process`

### Step 3: Test CRON

```bash
# Test manually
curl -v http://localhost/cron/roi_maturity/process

# Expected output:
# Found X matured staking orders
# Processing Order ID: X (User: Y)
# ✓ Order X processed successfully
```

## How It Works

```
Timeline:
  Day 1 (Purchase)     Day 365 (Maturity)     Day 365+ (CRON runs)
  ├─ Create staking ─→ ├─ Staking period ──→ ├─ CRON processes
  ├─ Set maturity      ├─ Hourly ROI paid     ├─ Calculates remaining ROI
  ├─ ROI rate locked   ├─ roi_distribution    ├─ Credits to earning wallet
  │                    │  is_matured=1        ├─ Records transaction
  │                    │                      └─ roi_return_status='completed'
```

### Example Scenario

**User purchases 100 BMAN for 1 year at 150% ROI:**
```
Purchase Date:      2026-07-09
Maturity Date:      2027-07-09
Principal:          100 BMAN
ROI Rate:           150%
Expected Total ROI: 150 BMAN

Hourly ROI (distributed via StakingROI_cron):
  ~0.017 BMAN/hour × 24 hours × 365 days ≈ 150 BMAN

On Maturity Date (CRON runs):
  Total ROI Calculated:  150 BMAN
  Already Paid (hourly): 145 BMAN (assumed)
  Remaining to Pay:      5 BMAN
  
  ✓ Credit 5 BMAN to earning wallet
  ✓ Record transaction (tx_type='roi_maturity')
  ✓ Set roi_return_status='completed'
```

## Monitoring

### Check Processing Status
```sql
-- See all matured investments
SELECT id, ref, user_id, maturity_date, roi_return_status 
FROM staking_swap_orders 
WHERE maturity_date <= NOW();

-- Check ROI records
SELECT user_id, principal_amount, total_roi_earned, roi_remaining,
       distribution_status, distribution_date
FROM roi_distribution 
WHERE is_matured = 1;

-- View transaction history
SELECT * FROM onchain_transactions
WHERE tx_type = 'roi_maturity'
ORDER BY created_at DESC;
```

### Log Files
```bash
# Redirect CRON output to log file
0 * * * * curl -s http://yoursite.com/cron/roi_maturity/process \
    >> /var/log/roi_maturity.log 2>&1
```

## Key Features

✅ **Automatic Processing**
- Runs hourly (or custom interval)
- No manual intervention needed

✅ **Safe & Reliable**
- Status tracking prevents duplicate payouts
- Error handling with retry logic
- Foreign key constraints prevent data corruption

✅ **Audit Trail**
- Every transaction recorded in onchain_transactions
- User can see complete ROI history
- Compliance-ready

✅ **Flexible ROI Rates**
- Rate locked at purchase time
- Works with any ROI% (150%, 200%, etc.)
- Works with any duration (1yr, 2yr, 5yr, etc.)

✅ **No Recalculation**
- Uses roi_rate stored in staking_swap_orders
- No API calls needed
- Fast processing

## Troubleshooting

**CRON not running?**
- Test: `curl http://yoursite.com/cron/roi_maturity/process`
- Check server logs for errors
- Verify hosting allows outbound cron calls

**ROI not released?**
- Check maturity_date is past today
- Check roi_return_status is 'pending' (not 'completed')
- Verify roi_distribution record exists
- Run CRON manually to test

**Wrong amount credited?**
- Verify roi_rate in staking_swap_orders
- Check duration_years is correct
- Calculate: Principal × (ROI% / 100) × (Years)

## Files Added/Modified

**New Files:**
- `application/controllers/cron/RoiMaturity_cron.php` - CRON job
- `db/create_roi_distribution_table.sql` - Schema
- `db/run_roi_distribution_migration.php` - Migration script
- `docs/ROI_MATURITY_CRON_SETUP.md` - Full documentation

**Modified Files:**
- `application/controllers/user/usersettings/Lendingcontroller.php`
  - Added `createROIDistributionRecord()` helper
  - Creates roi_distribution at purchase time

**Database:**
- `staking_swap_orders.maturity_date` - When staking completes
- `staking_swap_orders.roi_return_status` - Payout status
- `roi_distribution` - New table for tracking

## Next: Integrate with Earning Wallet

The CRON credits ROI to the user's **earning wallet**:
```php
WHERE wallet_type = 'earning'
```

Make sure your wallet_ledger has proper earning wallet records for each user.

Example wallet setup:
```sql
INSERT INTO wallet_ledger (user_id, wallet_type, balance)
VALUES 
  (5, 'exchange', 0),
  (5, 'earning', 0),
  (5, 'staking', 0),
  (5, 'bonus', 0);
```

## Support

**For detailed setup:**
- See: `docs/ROI_MATURITY_CRON_SETUP.md`

**For system architecture:**
- Database: 3 tables (staking_swap_orders, roi_distribution, onchain_transactions)
- CRON: Hourly execution recommended
- API: No external calls needed
- Wallet: Credits to earning wallet automatically

## Status: ✅ READY FOR PRODUCTION

All components implemented and tested:
- Database tables created
- CRON job ready to run
- Controller integration complete
- Documentation provided
- Error handling in place

**Next Step:** Set up cron job (see Quick Setup above)
