# ROI Maturity Cron — Fully On-Chain Architecture

## Overview

The ROI Maturity Cron is a fully on-chain implementation that automatically detects when ROI payouts become mature and broadcasts them as blockchain transfers from the **Treasury Wallet** to each user's **Earning Wallet Address**.

This is a complete vertical slice that handles:
- **Detection**: Identifies ROI payouts with `credit_date <= TODAY`
- **Broadcasting**: Signs and broadcasts transfers via Web3bman (using Treasury private key)
- **Tracking**: Stores transaction hashes for confirmation verification
- **Confirmation**: Waits for chain confirmation and credits wallet ledger
- **Error Handling**: Tracks failures, supports retries, logs audit trail

## Architecture

```
ROI Maturity Cron Flow:
┌─────────────────────────────────────────────────────────────┐
│ RoiMaturityCron::run() [Entry Point]                        │
│                                                              │
│ Step 1: syncConfirmationsFromChain()                        │
│         ├─ Query ROI transfers pending_confirmation         │
│         ├─ Check onchain_transactions for status            │
│         ├─ If confirmed: call confirmRoiTransfer()          │
│         └─ Mark as 'confirmed', credit ledger (idempotent) │
│                                                              │
│ Step 2: processMaturityCycle()                              │
│         ├─ Get mature ROIs (credit_date <= TODAY)           │
│         ├─ For each ROI:                                    │
│         │  ├─ Verify user earning wallet address            │
│         │  ├─ Sign transfer with Treasury private key       │
│         │  ├─ Broadcast via Web3bman::sendToken()          │
│         │  ├─ Store tx_hash in staking_roi_payouts         │
│         │  ├─ Mark transfer_status = pending_confirmation   │
│         │  └─ Log to staking_roi_transfer_log              │
│         └─ Return [processed, failed]                       │
│                                                              │
│ Step 3: retryFailedTransfers() [Optional]                   │
│         ├─ Find transfer_status = 'failed'                  │
│         ├─ Reset to pending_broadcast                       │
│         └─ Re-attempt broadcast                             │
└─────────────────────────────────────────────────────────────┘
                          ↓
                    [Broadcast to BSC]
                          ↓
             [ChainSync Cron Verifies & Confirms]
                          ↓
        [RoiMaturityCron Syncs Confirmations on next run]
                          ↓
            [Wallet Ledger Updated (Idempotent)]
```

## Database Schema Changes

### 1. Enhanced `staking_roi_payouts` table

New columns track on-chain transfer lifecycle:

```sql
ALTER TABLE `staking_roi_payouts` ADD COLUMN
  `tx_hash`           VARCHAR(255) UNIQUE DEFAULT NULL,
  `transfer_status`   ENUM('pending_broadcast','pending_confirmation','confirmed','failed','reverted') DEFAULT 'pending_broadcast',
  `transferred_at`    DATETIME DEFAULT NULL,
  `confirmed_at`      DATETIME DEFAULT NULL,
  `block_number`      BIGINT UNSIGNED DEFAULT NULL,
  `confirmation_count` INT UNSIGNED DEFAULT 0,
  `network`           VARCHAR(20) DEFAULT 'bsc',
  `error_message`     TEXT DEFAULT NULL;
```

| Column | Type | Purpose |
|--------|------|---------|
| `tx_hash` | VARCHAR(255) | Blockchain transaction hash (unique) |
| `transfer_status` | ENUM | Lifecycle: pending_broadcast → pending_confirmation → confirmed/failed/reverted |
| `transferred_at` | DATETIME | When we broadcast the transfer |
| `confirmed_at` | DATETIME | When transaction was confirmed on-chain |
| `block_number` | BIGINT | Block number where tx was confirmed |
| `confirmation_count` | INT | Number of block confirmations |
| `network` | VARCHAR(20) | Chain network (bsc, eth, etc.) |
| `error_message` | TEXT | Last error if transfer failed |

### 2. New `staking_roi_transfer_log` table

Audit trail for all ROI transfer attempts:

```sql
CREATE TABLE `staking_roi_transfer_log` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `roi_payout_id` BIGINT UNSIGNED NOT NULL,
  `user_id` INT NOT NULL,
  `amount` DECIMAL(20,4) NOT NULL,
  `from_address` VARCHAR(255) NOT NULL,     -- Treasury wallet
  `to_address` VARCHAR(255) NOT NULL,       -- User's earning wallet
  `tx_hash` VARCHAR(255) DEFAULT NULL,
  `transfer_status` ENUM(...),
  `attempt_no` TINYINT UNSIGNED DEFAULT 1,
  `error_message` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME ON UPDATE CURRENT_TIMESTAMP
);
```

## Configuration Requirements

### 1. Token Settings (Master → Token Settings)

Ensure these fields are configured in the active `token_settings` row:

```
Field Name                  | Value | Purpose
─────────────────────────────┼───────┼──────────────────────
rpc_url                     | https://bsc-dataseed.binance.org | RPC endpoint
bsc_scan_api_key            | YOUR_BSCSCAN_KEY | API fallback (optional)
bman_contract               | 0x... | BMAN token contract address
bman_decimals               | 18 | Token decimals
chain_id                    | 56 | BSC chain ID
minimum_confirmations       | 15 | Blocks to wait for confirmation
gas_limit                   | 210000 | Gas limit for transfers
treasury_wallet_address     | 0x... | Treasury wallet address (public)
treasury_pk_encrypted       | [encrypted] | Treasury private key (AES encrypted)
hot_wallet_address          | 0x... | Hot wallet for operations
earning_wallet_base_address | 0x... | Base earning wallet (if using single)
```

### 2. Treasury Private Key Encryption

The Treasury private key MUST be encrypted before storing in the database.

**To encrypt a private key:**

```php
$this->load->library('encryption');
$private_key = '0x...'; // Private key from your Treasury wallet
$encrypted = $this->encryption->encrypt($private_key);
// Store $encrypted in token_settings.treasury_pk_encrypted
```

**SECURITY NOTES:**
- Never store private keys in plain text
- Encryption uses CodeIgniter's ENCRYPTION_KEY from config
- Private key is decrypted only when needed (just-in-time)
- All keys are cleared from memory after use

### 3. User Earning Wallet Address

Users must have an earning wallet address configured. The system checks in this order:

1. `users.earning_wallet_address` (custom column)
2. `custodial_wallets.address` where wallet_type = 'earning'

**To set up user earning wallets:**

```php
// Option A: Direct user column
UPDATE users SET earning_wallet_address = '0x...' WHERE id = 123;

// Option B: Custodial wallets table
INSERT INTO custodial_wallets (user_id, wallet_type, address, network, created_at)
VALUES (123, 'earning', '0x...', 'bsc', NOW());
```

## Usage

### Trigger the Cron

#### Via CLI (Recommended)

```bash
php index.php roimaturitycron run
```

**With retry flag:**
```bash
php index.php roimaturitycron run retry=true
```

**Output:**
```json
{
  "status": "success",
  "ran_at": "2026-02-26 14:30:45",
  "sync_confirmed": 5,      // ROIs confirmed from prior broadcasts
  "sync_failed": 0,
  "processed": 12,          // New ROIs processed this cycle
  "failed": 0,
  "duration_ms": 2345
}
```

#### Via HTTP (Requires `cron_token`)

```bash
curl -X GET "http://yourdomain.com/roi-maturity-cron?token=YOUR_CRON_TOKEN"
curl -X GET "http://yourdomain.com/roi-maturity-cron?token=YOUR_CRON_TOKEN&retry=true"
```

#### Via Scheduler

Add to your cron scheduler (e.g., cPanel, AWS Lambda, GitHub Actions):

**Run daily at 8 AM:**
```
0 8 * * * php /path/to/index.php roimaturitycron run
```

**Run every 4 hours:**
```
0 */4 * * * php /path/to/index.php roimaturitycron run
```

### Admin Endpoints

#### Get Transfer Statistics

```bash
curl -X GET "http://yourdomain.com/roimaturitycron/stats"
```

**Response:**
```json
{
  "pending_broadcast": 3,
  "pending_confirmation": 8,
  "confirmed": 145,
  "failed": 1,
  "total_pending_amount": 2500.5000
}
```

#### Mark ROI as Reverted (Don't Retry)

```bash
curl -X GET "http://yourdomain.com/roimaturitycron/mark_reverted?roi_id=456&reason=User%20requested%20cancellation"
```

#### Retry Failed Transfers

```bash
curl -X GET "http://yourdomain.com/roimaturitycron/retry?token=YOUR_CRON_TOKEN"
```

## Transaction Lifecycle

### State Transitions

```
pending (DB)  →  pending_broadcast (transfer_status)
                          ↓
            [Web3bman.sendToken() succeeds]
                          ↓
        pending_confirmation (tx_hash stored)
                          ↓
          [ChainSync verifies on-chain]
                          ↓
           confirmed (block_number set)
                          ↓
         [syncConfirmationsFromChain()]
                          ↓
        paid (status), confirmed (transfer_status)
                          ↓
      [Wallet ledger credited idempotently]
```

### Failure Scenarios

**Broadcast Fails:**
```
pending_broadcast → failed (error_message logged)
                  → Cron can retry next run
                  → After MAX_RETRY_ATTEMPTS: manual admin review
```

**Chain Reorg (block reversal):**
```
confirmed → pending_confirmation (reorg detection)
         → Retry broadcast or wait
```

**User wallet doesn't exist:**
```
pending_broadcast → failed ("User has no earning wallet")
                  → Admin must configure earning wallet
                  → Mark as reverted to skip retries
```

## Idempotency & Safety

### Idempotent Broadcast

- ROI payout records are unique by ID
- Re-running cron on same ROI is safe
- `tx_hash` is unique in DB; duplicate broadcasts are rejected

### Idempotent Ledger Credit

- Wallet ledger uses `tx_hash` as unique reference
- Same tx_hash credited multiple times = no duplicates
- Safe to re-run confirmation sync

### Concurrent Safety

- Row locks prevent simultaneous updates to same ROI
- Transfer log tracks all attempts
- Failures are logged for manual investigation

## Error Handling & Troubleshooting

### Common Errors

| Error | Cause | Solution |
|-------|-------|----------|
| "No active Token Settings configured" | Master → Token Settings has no active row | Create an active token settings record |
| "User {id} has no earning wallet configured" | User earning_wallet_address not set | Set user.earning_wallet_address or create custodial wallet |
| "Treasury private key not configured" | treasury_pk_encrypted is NULL | Encrypt and store Treasury private key in token_settings |
| "RPC error: ..." | Blockchain endpoint unreachable | Check RPC URL, verify network connectivity |
| "Failed to broadcast ROI transfer: ..." | Transaction signing/broadcast failed | Check gas balance, check gas price settings |

### Monitoring

Check the transfer log for detailed audit trail:

```sql
SELECT * FROM staking_roi_transfer_log
WHERE user_id = 123
ORDER BY created_at DESC;
```

Check ROI payout status:

```sql
SELECT id, user_id, amount, status, transfer_status, tx_hash, error_message
FROM staking_roi_payouts
WHERE user_id = 123
ORDER BY credit_date DESC;
```

Check on-chain transaction confirmation:

```sql
SELECT octs.*, srp.id as roi_id
FROM onchain_transactions octs
LEFT JOIN staking_roi_payouts srp ON octs.tx_hash = srp.tx_hash
WHERE srp.id IS NOT NULL
ORDER BY octs.created_at DESC;
```

## Integration with Other Crons

### ChainSync Cron

The ROI Maturity Cron depends on ChainSync to verify transactions:

```
Cycle 1 (morning):
  - RoiMaturityCron: Broadcast 10 ROI transfers
  - ChainSync: Detects and tracks tx hashes in onchain_transactions

Cycle 2 (afternoon):
  - ChainSync: Confirms transactions (15+ blocks)
  - RoiMaturityCron: Syncs confirmations, credits ledger
```

**Recommended schedule:**
- ChainSync: Every 5 minutes (continuous verification)
- RoiMaturityCron: Every 4 hours or daily (batch processing)

### Wallet Ledger

ROI confirmations automatically credit the wallet ledger:

```php
$this->ledger->credit(
    $userId,
    'earning',           // wallet type
    $roiAmount,          // amount
    'roi_payout',        // reference type
    $txHash,             // unique ref (prevents duplicates)
    "ROI payout for stake {$stakeId}"
);
```

This is idempotent: same `$txHash` credited twice = no duplicates.

## Testing & Verification

### Test with Sample Data

1. **Create a test ROI with past maturity date:**

```sql
INSERT INTO staking_roi_payouts 
(stake_id, user_id, amount, credit_date, wallet, status, created_at)
VALUES 
(1, 1, 100.0000, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'earning', 'pending', NOW());
```

2. **Ensure user has earning wallet:**

```sql
UPDATE users SET earning_wallet_address = '0x...' WHERE id = 1;
```

3. **Run cron in test mode:**

```bash
php index.php roimaturitycron run
```

4. **Verify tx_hash was stored:**

```sql
SELECT id, tx_hash, transfer_status, transferred_at FROM staking_roi_payouts WHERE user_id = 1;
```

5. **Run ChainSync to confirm:**

```bash
php index.php chainsynccron run
```

6. **Run ROI cron again to sync confirmations:**

```bash
php index.php roimaturitycron run
```

7. **Check wallet ledger was credited:**

```sql
SELECT * FROM wallet_ledger WHERE user_id = 1 AND reference_type = 'roi_payout';
```

## Files Modified/Created

```
application/models/RoiMaturity_model.php          (NEW)
application/controllers/RoiMaturityCron.php       (NEW)
db/migration_roi_maturity_2026.sql                (NEW)
docs/ROI_MATURITY_CRON_GUIDE.md                   (NEW)
```

## Future Enhancements

- [ ] Admin UI for ROI transfer monitoring dashboard
- [ ] Automatic retry scheduler (separate cron for retries)
- [ ] ROI payout notifications to users (tx_hash, confirmation count)
- [ ] Batch optimization (group multiple ROI broadcasts in one tx)
- [ ] Multi-chain support (Ethereum, Polygon, etc.)
