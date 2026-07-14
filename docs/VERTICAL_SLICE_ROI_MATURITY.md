# Vertical Slice: ROI Maturity Cron — Fully On-Chain Architecture

## Summary

This is a **complete, production-ready vertical slice** that implements fully on-chain ROI maturity detection and automatic blockchain transfer broadcasting.

**Status:** ✅ Ready to deploy  
**Complexity:** Medium  
**Scope:** ROI payouts only (not other bonus types — yet)

## What It Does

1. **Detects mature ROIs**: Identifies ROI payouts with `credit_date <= TODAY` and `status = pending`
2. **Broadcasts transfers**: Signs and broadcasts blockchain transfers from Treasury wallet to users' earning wallets using Web3bman
3. **Tracks hashes**: Stores transaction hashes for confirmation verification
4. **Syncs confirmations**: Waits for blockchain confirmation and updates status
5. **Credits ledger**: Once confirmed, idempotently credits wallet ledger
6. **Handles failures**: Tracks errors, supports retries, provides admin interface

## Files Created

### Backend Models & Controllers

| File | Purpose |
|------|---------|
| `application/models/RoiMaturity_model.php` | Core business logic (broadcast, confirmation, retry) |
| `application/controllers/RoiMaturityCron.php` | Cron entry points (run, retry, stats) |
| `application/controllers/admin/staking/RoiMonitor.php` | Admin monitoring dashboard + API |

### Database

| File | Purpose |
|------|---------|
| `db/migration_roi_maturity_2026.sql` | Schema migration (adds tx tracking columns + audit table) |

### Documentation

| File | Purpose |
|------|---------|
| `docs/ROI_QUICK_START.md` | 5-step setup (15 min) |
| `docs/ROI_SETUP_CHECKLIST.md` | Phase-by-phase detailed setup + testing |
| `docs/ROI_MATURITY_CRON_GUIDE.md` | Full technical documentation (architecture, API, troubleshooting) |
| `VERTICAL_SLICE_ROI_MATURITY.md` | This file |

## Architecture

### Data Flow

```
Mature ROI (credit_date <= TODAY)
           ↓
    [RoiMaturity_model::processMaturityCycle()]
           ↓
    Verify earning wallet address
           ↓
    [Web3bman::sendToken() - broadcast]
           ↓
    Store tx_hash, set transfer_status=pending_confirmation
           ↓
    [ChainSync cron verifies on-chain]
           ↓
    [RoiMaturity_model::syncConfirmationsFromChain()]
           ↓
    Update transfer_status=confirmed
           ↓
    [Wallet ledger credit - idempotent via tx_hash]
           ↓
    Set status=paid
```

### Database Schema Changes

**New columns in `staking_roi_payouts`:**
- `tx_hash` (VARCHAR 255, UNIQUE) — blockchain tx hash
- `transfer_status` (ENUM) — lifecycle: pending_broadcast → pending_confirmation → confirmed/failed/reverted
- `transferred_at` (DATETIME) — when we broadcast
- `confirmed_at` (DATETIME) — when confirmed on-chain
- `block_number` (BIGINT) — confirmation block
- `confirmation_count` (INT) — number of confirmations
- `network` (VARCHAR 20) — chain network
- `error_message` (TEXT) — failure reason

**New table: `staking_roi_transfer_log`**
- Audit trail for all transfer attempts
- Tracks every broadcast, confirmation, failure, retry

## Key Features

### ✅ Fully On-Chain
- Every ROI payout has a blockchain transaction hash
- No internal ledger transfers — all from-chain-to-chain
- Treasury wallet → User earning wallet (on BSC)

### ✅ Idempotent & Safe
- Cron can run multiple times safely
- tx_hash is unique → prevents duplicate broadcasts
- Wallet ledger uses tx_hash as unique key → prevents duplicate credits
- Row locks prevent race conditions

### ✅ Error Handling
- Graceful failure tracking (stores error_message)
- Supports retries (reset transfer_status and re-attempt)
- Max retry attempts (default 5)
- Manual admin override (mark as reverted)

### ✅ Integration Ready
- Works with ChainSync cron for confirmation verification
- Integrates with Web3bman for signing/broadcasting
- Uses wallet ledger for internal state (after confirmation)
- Logs to `staking_roi_transfer_log` for audit trail

### ✅ Admin Visibility
- Admin dashboard for monitoring (RoiMonitor controller)
- API endpoints for stats, retry, marking as reverted
- View transfer details + audit log

## Cron Scheduling

### Main Cron: `roimaturitycron run`

**What it does:**
1. Sync confirmations from prior broadcasts (pending_confirmation → confirmed)
2. Process new mature ROIs (broadcast)
3. Optionally retry failed transfers

**Recommended frequency:** Daily (morning) or every 4 hours

**Example crontab:**
```
0 8 * * * /usr/bin/php /path/to/index.php roimaturitycron run
```

### ChainSync Dependency

ROI cron depends on ChainSync to verify transactions:
- ChainSync runs continuously (every 5 minutes) → watches blockchain
- ROI cron runs periodically (daily or 4-hourly) → broadcasts + confirms

## Configuration

### Token Settings (Master → Token Settings)

Required fields:
- `treasury_wallet_address` — Treasury public address
- `treasury_pk_encrypted` — Encrypted Treasury private key
- `rpc_url` — BSC RPC endpoint
- `bman_contract` — BMAN token contract address
- `bman_decimals` — Token decimals (usually 18)
- `chain_id` — BSC chain ID (56)
- `minimum_confirmations` — Blocks to wait (default 15)
- `gas_limit` — Transfer gas limit (default 210000)

### User Setup

Users must have earning wallet address:
```sql
-- Option A: Direct column
UPDATE users SET earning_wallet_address = '0x...' WHERE id = 123;

-- Option B: Custodial wallet
INSERT INTO custodial_wallets 
(user_id, wallet_type, address, network, created_at)
VALUES (123, 'earning', '0x...', 'bsc', NOW());
```

## API Endpoints

### Cron Triggers

| Endpoint | Method | Query Params | Purpose |
|----------|--------|--------------|---------|
| `/roimaturitycron/run` | GET | token, retry | Main cron |
| `/roimaturitycron/retry` | GET | token | Retry failed |
| `/roimaturitycron/stats` | GET | — | Get stats |
| `/roimaturitycron/mark_reverted` | GET | token, roi_id, reason | Mark failed as reverted |

### Admin API

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/admin/staking/roimonitor` | GET | Dashboard |
| `/admin/staking/roimonitor/api_stats` | GET | Stats (JSON) |
| `/admin/staking/roimonitor/api_recent` | GET | Recent transfers |
| `/admin/staking/roimonitor/api_failed` | GET | Failed transfers |
| `/admin/staking/roimonitor/api_retry_single` | POST | Retry one |
| `/admin/staking/roimonitor/api_retry_all` | POST | Retry all |
| `/admin/staking/roimonitor/api_mark_reverted` | POST | Mark reverted |
| `/admin/staking/roimonitor/api_audit_log` | GET | Audit trail |

## Testing

**Create test ROI:**
```sql
INSERT INTO staking_roi_payouts 
(stake_id, user_id, amount, credit_date, wallet, status)
VALUES 
(1, 1, 50.0000, CURDATE() - INTERVAL 1 DAY, 'earning', 'pending');
```

**Run cron:**
```bash
php index.php roimaturitycron run
```

**Check results:**
```sql
SELECT id, tx_hash, transfer_status, transferred_at FROM staking_roi_payouts WHERE user_id = 1;
```

**See full testing guide:** `docs/ROI_SETUP_CHECKLIST.md` (Phase 5)

## Deployment Checklist

- [ ] Run migration: `migration_roi_maturity_2026.sql`
- [ ] Configure Token Settings (Treasury wallet, RPC, contract)
- [ ] Encrypt Treasury private key and store in DB
- [ ] Set up user earning wallet addresses
- [ ] Schedule cron: `php index.php roimaturitycron run`
- [ ] Test with sample ROI (create, broadcast, confirm)
- [ ] Monitor admin dashboard for first few cycles
- [ ] Enable in production

## Known Limitations & Future Work

### Current Limitations
- ROI-only (binary/matching/rank payouts not yet on-chain)
- Single treasury wallet (no multi-sig)
- Manual earning wallet setup (no user self-service)
- No batch optimization (individual txs, not grouped)

### Future Enhancements
- [ ] Extend to binary matching payouts
- [ ] Extend to rank achievement payouts
- [ ] User self-service earning wallet registration
- [ ] Batch transfer optimization (lower gas)
- [ ] Multi-chain support (Ethereum, Polygon, etc.)
- [ ] Multi-sig wallet support
- [ ] Admin UI for ROI transfer history chart
- [ ] Email notifications for large payouts
- [ ] Discord/Slack alerts for failed transfers

## Support & Documentation

| Question | Document |
|----------|----------|
| How do I set this up? | `ROI_QUICK_START.md` (5 min) or `ROI_SETUP_CHECKLIST.md` (detailed) |
| How does it work? | `ROI_MATURITY_CRON_GUIDE.md` (full technical docs) |
| It failed, now what? | Troubleshooting section in `ROI_MATURITY_CRON_GUIDE.md` |
| What about X? | Check the guide first, then check DB audit tables |

## Production Readiness

✅ **Ready for production**

This vertical slice:
- ✅ Has comprehensive error handling
- ✅ Is fully idempotent (safe to re-run)
- ✅ Has audit logging (all attempts tracked)
- ✅ Works with existing infrastructure (Web3bman, ChainSync, Wallet Ledger)
- ✅ Has admin monitoring + retry capabilities
- ✅ Has detailed documentation

## Metrics to Monitor

Post-deployment, track these metrics:

```sql
-- ROI processing rate
SELECT DATE(transferred_at) as date, 
       COUNT(*) as roi_count, 
       SUM(amount) as total_amount
FROM staking_roi_payouts
WHERE transferred_at IS NOT NULL
GROUP BY DATE(transferred_at)
ORDER BY date DESC;

-- Failure rate
SELECT transfer_status, COUNT(*) as cnt 
FROM staking_roi_payouts 
GROUP BY transfer_status;

-- Average confirmation time
SELECT AVG(TIMESTAMPDIFF(MINUTE, transferred_at, confirmed_at)) as avg_minutes
FROM staking_roi_payouts
WHERE confirmed_at IS NOT NULL;
```

---

**Author:** Claude Code  
**Date:** 2026-02-26  
**Version:** 1.0 (Initial Release)
