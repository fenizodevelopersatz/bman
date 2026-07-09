# Cron Management Guide — BMAN Fully On-Chain

Complete reference for all system crons, timing strategy, and troubleshooting.

## Overview

The system uses **7 core cron jobs** that work together to manage:
- Blockchain synchronization (balance, tx confirmation)
- Deposit detection & crediting
- ROI maturity & blockchain broadcasting
- Binary/matching commission calculations
- Bonus wallet reductions
- Staking purchase confirmations

## Cron Timing Strategy

All crons follow this **cascading frequency model**:

```
Every 5 minutes (Continuous)
├─ ChainSync Cron        (verify pending txs, sync balances)
└─ Deposit Cron          (detect incoming deposits)

Every 15 minutes
├─ Staking Confirm Cron  (PROCESSING → ACTIVE on blockchain)
└─ DailyCommission Cron  (calculate daily binary/matching)

Every 4 hours (6x per day)
└─ ROI Maturity Cron     (broadcast mature ROI transfers)

Once daily (8 AM)
├─ Bonus Reduction Cron  (60-day bonus → admin wallet)
└─ Swap Orders Cron      (deliver BMAN for completed swaps)

Weekly (Monday 2 AM)
└─ Rank Achievement Cron (evaluate & award rank changes)
```

## Detailed Cron Specifications

### 1. ChainSync Cron (5 min intervals)

**Route:** `/chain-sync-cron?token=YOUR_CRON_TOKEN`  
**CLI:** `php index.php chainsynccron run`  
**Controller:** `application/controllers/Chainsynccron.php`  
**Model:** `application/models/Chainsync_model.php`

**What it does:**
- Verifies pending transactions (status, confirmations, gas)
- Syncs on-chain balances (BNB, BMAN) vs database
- Detects reorgs, handles failed txs
- Enriches on-chain transactions with gas/block data

**Timing:**
```
*/5 * * * * /usr/bin/php /path/to/index.php chainsynccron run >/dev/null 2>&1
```

**Expected runtime:** 2-10 seconds (mostly free RPC, minimal BscScan calls)

**Dependency:** Runs BEFORE ROI/bonus confirmations (they read on-chain status)

---

### 2. Deposit Cron (5 min intervals)

**Route:** `/credit-deposits-cron?token=YOUR_CRON_TOKEN`  
**CLI:** `php index.php depositcron run`  
**Controller:** `application/controllers/Depositcron.php`  
**Model:** `application/models/Depositlistener_model.php`

**What it does:**
- Detects USDT deposits to custodial addresses (via RPC)
- Waits for minimum confirmations (15 by default)
- Credits wallet ledger (USDT wallet)
- Idempotent (tx_hash based)

**Timing:**
```
*/5 * * * * /usr/bin/php /path/to/index.php depositcron run >/dev/null 2>&1
```

**Expected runtime:** 3-8 seconds per wallet

**Dependency:** None (read-only RPC)

---

### 3. Staking Confirm Cron (15 min intervals)

**Route:** `/stake-confirm-cron?token=YOUR_CRON_TOKEN`  
**CLI:** `php index.php user/usersettings/lendingcontroller stake_confirm_cron`  
**Controller:** `application/controllers/user/usersettings/Lendingcontroller.php`  
**Model:** `application/models/Staking_model.php`

**What it does:**
- Checks for on-chain USDT transfers confirming stake purchases
- Updates stake status: PROCESSING → ACTIVE
- Records block number, confirmation count
- Triggers ROI schedule generation

**Timing:**
```
*/15 * * * * /usr/bin/php /path/to/index.php user/usersettings/lendingcontroller stake_confirm_cron >/dev/null 2>&1
```

**Expected runtime:** 5-15 seconds

**Dependency:** Depends on ChainSync (reads confirmed txs)

---

### 4. DailyCommission Cron (15 min intervals)

**Route:** (Not HTTP — use CLI or admin button)  
**CLI:** `php index.php admin/staking/Matching cron`  
**Controller:** `application/controllers/admin/staking/Matching.php`  
**Model:** `application/models/staking/Stakingmatching_model.php`

**What it does:**
- Propagates binary volume
- Calculates daily matching commission
- Splits 8% (Earning wallet) + 2% (Staking wallet)
- Applies ceiling caps, carry-forward rules
- Idempotent (per-date processing)

**Timing:**
```
*/15 * * * * /usr/bin/php /path/to/index.php admin/staking/Matching cron >/dev/null 2>&1
```

**Expected runtime:** 10-30 seconds

**Dependency:** None (internal ledger only)

---

### 5. ROI Maturity Cron (Every 4 hours)

**Route:** `/roi-maturity-cron?token=YOUR_CRON_TOKEN`  
**CLI:** `php index.php roimaturitycron run`  
**Controller:** `application/controllers/RoiMaturityCron.php`  
**Model:** `application/models/RoiMaturity_model.php`

**What it does:**
- Syncs confirmations from prior ROI broadcasts
- Detects mature ROIs (credit_date <= TODAY)
- Signs & broadcasts blockchain transfers (Treasury → Earning wallet)
- Stores tx_hash, marks pending_confirmation
- Retries failed transfers

**Timing:**
```
0 */4 * * * /usr/bin/php /path/to/index.php roimaturitycron run >/dev/null 2>&1
```

**Expected runtime:** 10-60 seconds (depends on ROI count)

**Dependency:** Depends on ChainSync (reads on-chain confirmations)

**Retries:**
```
# Retry all failed once daily (1 hour after main cron)
0 9 * * * /usr/bin/php /path/to/index.php roimaturitycron retry >/dev/null 2>&1
```

---

### 6. Bonus Reduction Cron (Daily at 8 AM)

**Route:** `/bonus-reduction-cron?token=YOUR_CRON_TOKEN`  
**CLI:** `php index.php bonusreductioncron run`  
**Controller:** `application/controllers/Bonusreductioncron.php`  
**Model:** `application/models/Bonusreduction_model.php`

**What it does:**
- Processes per-user 60-day bonus reduction schedule
- Reduces bonus wallet by 50% (configurable)
- Credits admin wallet (or admin bonus address)
- Optional on-chain transfer (user → admin)

**Timing:**
```
0 8 * * * /usr/bin/php /path/to/index.php bonusreductioncron run >/dev/null 2>&1
```

**Expected runtime:** 20-120 seconds (user-count dependent)

**Dependency:** Depends on Web3bman if on-chain enabled

---

### 7. Swap Orders Cron (Daily, after bonus)

**Route:** (No HTTP route yet — add below)  
**CLI:** `php index.php admin/staking/Swaporders deliver_cron`  
**Controller:** `application/controllers/admin/staking/Swaporders.php`

**What it does:**
- Monitors completed USDT→BMAN swap orders
- Delivers BMAN tokens on-chain (Treasury → User)
- Marks orders as delivered

**Timing:**
```
0 9 * * * /usr/bin/php /path/to/index.php admin/staking/Swaporders deliver_cron >/dev/null 2>&1
```

**Expected runtime:** 15-60 seconds

**Dependency:** Depends on Web3bman

---

### 8. Rank Achievement Cron (Weekly, early morning)

**Route:** (Not yet implemented)  
**Model:** `application/models/staking/Rankachievement_model.php` (TODO)

**What it does:**
- Evaluates binary trees for rank qualification
- Awards permanent rank changes
- Updates user power rankings for payout qualification

**Timing:**
```
0 2 * * 1 /usr/bin/php /path/to/index.php admin/staking/Rankachievement run_qualification >/dev/null 2>&1
```

**Expected runtime:** 30-180 seconds (tree traversal)

**Dependency:** None (internal ledger)

---

## Crontab Configuration

### Complete crontab setup

```bash
#!/bin/bash
# BMAN Fully On-Chain Cron Schedule
# Add these to your crontab (crontab -e) or cPanel → Cron Jobs

# Base path and PHP location
PHP=/usr/bin/php
APP=/home/username/public_html/admlm

# Every 5 minutes: Chain sync (verify pending txs)
*/5 * * * * $PHP $APP/index.php chainsynccron run >/dev/null 2>&1

# Every 5 minutes: Deposit detection (USDT deposits)
*/5 * * * * $PHP $APP/index.php depositcron run >/dev/null 2>&1

# Every 15 minutes: Staking confirmation (PROCESSING → ACTIVE)
*/15 * * * * $PHP $APP/index.php user/usersettings/lendingcontroller stake_confirm_cron >/dev/null 2>&1

# Every 15 minutes: Daily commission (binary/matching calculation)
*/15 * * * * $PHP $APP/index.php admin/staking/Matching cron >/dev/null 2>&1

# Every 4 hours: ROI maturity broadcasting (6x per day)
0 */4 * * * $PHP $APP/index.php roimaturitycron run >/dev/null 2>&1

# 1 hour after ROI broadcast: Retry failed ROI transfers
0 9 * * * $PHP $APP/index.php roimaturitycron retry >/dev/null 2>&1

# Daily 8 AM: Bonus wallet 60-day reduction
0 8 * * * $PHP $APP/index.php bonusreductioncron run >/dev/null 2>&1

# Daily 9 AM: Swap order delivery (BMAN on-chain)
0 9 * * * $PHP $APP/index.php admin/staking/Swaporders deliver_cron >/dev/null 2>&1

# Weekly Monday 2 AM: Rank achievement evaluation
0 2 * * 1 $PHP $APP/index.php admin/staking/Rankachievement run_qualification >/dev/null 2>&1
```

### CPanel Cron Jobs

**Format:** Minute Hour Day Month Weekday Command

| Minute | Hour | Day | Month | Weekday | Command |
|--------|------|-----|-------|---------|---------|
| */5 | * | * | * | * | `php /path/to/index.php chainsynccron run` |
| */5 | * | * | * | * | `php /path/to/index.php depositcron run` |
| */15 | * | * | * | * | `php /path/to/index.php user/usersettings/lendingcontroller stake_confirm_cron` |
| */15 | * | * | * | * | `php /path/to/index.php admin/staking/Matching cron` |
| 0 | */4 | * | * | * | `php /path/to/index.php roimaturitycron run` |
| 0 | 9 | * | * | * | `php /path/to/index.php roimaturitycron retry` |
| 0 | 8 | * | * | * | `php /path/to/index.php bonusreductioncron run` |
| 0 | 9 | * | * | * | `php /path/to/index.php admin/staking/Swaporders deliver_cron` |
| 0 | 2 | * | * | 1 | `php /path/to/index.php admin/staking/Rankachievement run_qualification` |

---

## Monitoring & Alerting

### Health Check Query

```sql
-- Check cron execution status (last 24 hours)
SELECT 
  'ChainSync' as cron_name,
  MAX(created_at) as last_run,
  TIMESTAMPDIFF(MINUTE, MAX(created_at), NOW()) as minutes_since
FROM rpc_sync_log
WHERE scope = 'sync'
UNION ALL
SELECT 'Deposits', MAX(created_at), TIMESTAMPDIFF(MINUTE, MAX(created_at), NOW())
FROM depositlistener_log
WHERE status = 'credited'
UNION ALL
SELECT 'ROI Maturity', MAX(transferred_at), TIMESTAMPDIFF(MINUTE, MAX(transferred_at), NOW())
FROM staking_roi_payouts
WHERE transferred_at IS NOT NULL
UNION ALL
SELECT 'DailyCommission', MAX(created_at), TIMESTAMPDIFF(MINUTE, MAX(created_at), NOW())
FROM wallet_ledger
WHERE reference_type = 'matching_bonus'
ORDER BY minutes_since DESC;
```

### Alert Thresholds

| Cron | Max Gap Without Execution | Action |
|------|---------------------------|--------|
| ChainSync | 10 min | Alert if no run in 10 min |
| Deposit | 10 min | Alert if no run in 10 min |
| Staking Confirm | 20 min | Alert if PROCESSING stakes pending |
| DailyCommission | 30 min | Manual review if skipped |
| ROI Maturity | 5 hours | Alert if missed 4-hour window |
| Bonus Reduction | 25 hours | Alert if missed daily window |
| Swap Orders | 25 hours | Alert if pending orders > 24h |

### Log Locations

```bash
# Application logs
tail -f /path/to/app/application/logs/log-*.php

# Search for cron errors
grep -i "cron\|error" /path/to/app/application/logs/log-*.php | tail -50

# Monitor RPC sync
grep "rpc_sync_log" /path/to/app/application/logs/log-*.php
```

---

## Troubleshooting

### Cron not running

1. **Verify cron is scheduled:**
   ```bash
   crontab -l | grep "chainsynccron\|depositcron\|roimaturitycron"
   ```

2. **Check PHP path:**
   ```bash
   which php
   # Use full path in crontab (e.g., /usr/bin/php)
   ```

3. **Test cron command manually:**
   ```bash
   /usr/bin/php /path/to/index.php chainsynccron run
   # Should output JSON
   ```

4. **Check permissions:**
   ```bash
   # Ensure files are readable by cron user
   ls -la /path/to/index.php
   # Should be 755 or readable
   ```

5. **Enable cron token logging:**
   - Add cron token validation logs in respective cron controllers
   - Check application logs for auth failures

### Cron timing conflicts

**Issue:** ROI and ChainSync both broadcasting — conflicts on gas/nonce?

**Solution:** Stagger by 30-60 seconds:
- ChainSync @ :00, :05, :10, :15, :20, :25, :30, :35, :40, :45, :50, :55
- ROI @ :03, :07, :11, :15, :19, :23, :27, :31, :35, :39, :43, :47, :51, :55 (offset by 3 min)

### Slow cron execution

**Monitor duration:**
```sql
SELECT 
  operation, 
  COUNT(*) as cnt, 
  AVG(duration_ms) as avg_ms, 
  MAX(duration_ms) as max_ms
FROM rpc_sync_log
WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY operation
ORDER BY avg_ms DESC;
```

**Optimize if > 30s:**
- Reduce wallet count per sync
- Use free RPC first (no quota hit)
- Batch process smaller transactions

---

## HTTP Cron Triggers (Alternative to CLI)

If CLI is not available, use HTTP with token auth:

```bash
curl -X GET "https://yourdomain.com/chain-sync-cron?token=YOUR_CRON_TOKEN"
curl -X GET "https://yourdomain.com/credit-deposits-cron?token=YOUR_CRON_TOKEN"
curl -X GET "https://yourdomain.com/roi-maturity-cron?token=YOUR_CRON_TOKEN"
curl -X GET "https://yourdomain.com/bonus-reduction-cron?token=YOUR_CRON_TOKEN"
```

**Security:** Set `cron_token` in `application/config/config.php`:
```php
$config['cron_token'] = 'complex_random_secret_here';
```

---

## Performance Tuning

### Database Indexing for Crons

```sql
-- ChainSync lookups
CREATE INDEX idx_onchain_tx_status_pending ON onchain_transactions(status, created_at);
CREATE INDEX idx_onchain_tx_hash ON onchain_transactions(tx_hash);

-- Deposit listener
CREATE INDEX idx_wallet_address_balance ON custodial_wallets(address, balance_updated_at);

-- ROI maturity
CREATE INDEX idx_roi_credit_date_status ON staking_roi_payouts(credit_date, status, transfer_status);

-- DailyCommission
CREATE INDEX idx_bv_user_date ON binary_volume(user_id, created_date);
CREATE INDEX idx_ledger_user_date ON wallet_ledger(user_id, created_at);
```

### Query Optimization

**Batch processing:** Instead of per-user processing, batch by 100:
```php
// Get next 100 mature ROIs
$roi = $this->db->where('status', 'pending')
    ->where('credit_date <=', date('Y-m-d'))
    ->limit(100)
    ->get()
    ->result_array();
```

---

## Maintenance Schedule

| Frequency | Task | Purpose |
|-----------|------|---------|
| Daily | Check cron logs | Detect failures |
| Weekly | Review slow queries | Identify bottlenecks |
| Monthly | Audit cron table sizes | Prevent DB bloat |
| Quarterly | Review retry counts | Identify systemic issues |
| Annually | Performance review | Adjust timing if needed |

