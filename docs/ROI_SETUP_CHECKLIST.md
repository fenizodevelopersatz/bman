# ROI Maturity Cron — Setup Checklist

Complete this checklist to enable fully on-chain ROI payouts.

## Phase 1: Database Preparation

- [ ] **1.1 Run migration**
  ```bash
  # Load migration_roi_maturity_2026.sql in your database
  mysql -u user -p database < db/migration_roi_maturity_2026.sql
  ```
  This adds:
  - `tx_hash`, `transfer_status`, `transferred_at`, `confirmed_at`, `block_number`, `confirmation_count`, `network`, `error_message` columns to `staking_roi_payouts`
  - New `staking_roi_transfer_log` audit table

- [ ] **1.2 Verify migration succeeded**
  ```sql
  SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_NAME = 'staking_roi_payouts' AND COLUMN_NAME = 'tx_hash';
  -- Should return 1 row
  
  SHOW TABLES LIKE 'staking_roi_transfer_log';
  -- Should return 1 row
  ```

## Phase 2: Treasury Wallet Configuration

- [ ] **2.1 Set up Treasury wallet on BSC**
  - Create a BSC wallet (or use existing one)
  - Fund with enough BNB for gas (recommend 0.5+ BNB for safety margin)
  - Fund with BMAN tokens for ROI payouts
  - Write down the private key (will encrypt in next step)
  - Example: `0xYOUR_TREASURY_PRIVATE_KEY_HERE`

- [ ] **2.2 Encrypt Treasury private key**
  ```php
  // In your app: Admin → Development → Run PHP Snippet (or create a helper script)
  $this->load->library('encryption');
  $private_key = '0xYOUR_TREASURY_PRIVATE_KEY_HERE';
  $encrypted = $this->encryption->encrypt($private_key);
  echo "Encrypted: " . $encrypted;
  // Copy the encrypted value for step 2.3
  ```

- [ ] **2.3 Store encrypted key in Token Settings**
  Navigate to **Master → Token Settings → [Your Active Row]**
  
  Fill in these fields:
  
  | Field | Value | Example |
  |-------|-------|---------|
  | `treasury_wallet_address` | Treasury public address | `0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb` |
  | `treasury_pk_encrypted` | Encrypted private key from 2.2 | `[paste encrypted output]` |
  | `rpc_url` | BSC RPC endpoint | `https://bsc-dataseed.binance.org` |
  | `bman_contract` | BMAN token contract | `0x6ECDe...` (your token) |
  | `bman_decimals` | Token decimals | `18` |
  | `chain_id` | BSC chain ID | `56` |
  | `minimum_confirmations` | Confirmation blocks | `15` |
  | `gas_limit` | Transfer gas limit | `210000` |
  | `hot_wallet_address` | Hot wallet (for operations) | `0x...` |

  Save the record and mark as **Active**.

- [ ] **2.4 Test Treasury key decryption**
  ```php
  $cfg = $this->tokens->activeSettings();
  $this->load->library('encryption');
  $decrypted = $this->encryption->decrypt($cfg['treasury_pk_encrypted']);
  if (strpos($decrypted, '0x') === 0) {
    echo "✓ Key decrypted successfully";
  } else {
    echo "✗ Key decryption failed — check ENCRYPTION_KEY in config/config.php";
  }
  ```

## Phase 3: User Earning Wallet Configuration

Choose **ONE** approach (A or B):

### Option A: Direct User Column (Recommended)

- [ ] **3A.1 Add earning_wallet_address column to users table (if not exists)**
  ```sql
  ALTER TABLE users ADD COLUMN earning_wallet_address VARCHAR(255) DEFAULT NULL;
  ```

- [ ] **3A.2 Set earning wallets for users**
  ```sql
  -- Option 1: Set all users to same earning wallet
  UPDATE users SET earning_wallet_address = '0xEARNING_WALLET_ADDRESS';
  
  -- Option 2: Set per-user (e.g., user can provide their own BSC address)
  UPDATE users SET earning_wallet_address = '0x...' WHERE id = 123;
  ```

### Option B: Custodial Wallets Table

- [ ] **3B.1 Create custodial earning wallets**
  ```sql
  INSERT INTO custodial_wallets (user_id, wallet_type, address, network, created_at)
  VALUES 
  (1, 'earning', '0xEARNING_ADDR_1', 'bsc', NOW()),
  (2, 'earning', '0xEARNING_ADDR_2', 'bsc', NOW()),
  (3, 'earning', '0xEARNING_ADDR_3', 'bsc', NOW());
  ```

- [ ] **3B.2 Verify custodial wallets are created**
  ```sql
  SELECT user_id, wallet_type, address FROM custodial_wallets 
  WHERE wallet_type = 'earning' LIMIT 5;
  ```

## Phase 4: Cron Scheduling

- [ ] **4.1 Schedule main ROI cron**
  
  **Via cPanel:**
  - Go to cPanel → Cron Jobs
  - Add new cron:
    ```
    0 8 * * * /usr/bin/php /home/username/public_html/index.php roimaturitycron run >/dev/null 2>&1
    ```
  - This runs daily at 8 AM

  **Via AWS Lambda / External Scheduler:**
  - Create HTTP GET request to: `https://yourdomain.com/roi-maturity-cron?token=YOUR_CRON_TOKEN`
  - Run daily at preferred time

  **Via Supervisor (Linux):**
  ```ini
  [program:roi-maturity-cron]
  process_name=%(program_name)s_%(process_num)02d
  numprocs=1
  directory=/path/to/app
  command=/usr/bin/php index.php roimaturitycron run
  autostart=true
  autorestart=true
  startsecs=10
  stopwaitsecs=10
  ```

- [ ] **4.2 Ensure ChainSync cron is running**
  The ROI cron depends on ChainSync for transaction confirmation.
  
  Verify ChainSync is scheduled:
  ```
  0 */5 * * * /usr/bin/php /path/to/index.php chainsynccron run
  ```
  (Every 5 minutes)

- [ ] **4.3 Test cron manually**
  ```bash
  php index.php roimaturitycron run
  
  # Expected output:
  # {
  #   "status": "success",
  #   "ran_at": "2026-02-26 14:30:45",
  #   "message": "No mature ROIs to process",
  #   "processed": 0,
  #   "failed": 0,
  #   "duration_ms": 234
  # }
  ```

## Phase 5: Testing

- [ ] **5.1 Create test ROI with past maturity date**
  ```sql
  INSERT INTO staking_roi_payouts 
  (stake_id, user_id, amount, credit_date, wallet, status, created_at)
  VALUES 
  (1, 1, 50.0000, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'earning', 'pending', NOW());
  ```

- [ ] **5.2 Verify user has earning wallet**
  ```sql
  SELECT id, earning_wallet_address FROM users WHERE id = 1;
  -- Should have non-NULL earning_wallet_address
  ```

- [ ] **5.3 Ensure Treasury wallet has sufficient balance**
  - Check on BSC: Treasury wallet should have ≥ 50 BMAN + gas
  - Check via API or wallet (MetaMask, BscScan, etc.)

- [ ] **5.4 Run ROI cron**
  ```bash
  php index.php roimaturitycron run
  
  # Expected: Should show processed=1
  ```

- [ ] **5.5 Verify tx_hash was stored**
  ```sql
  SELECT id, amount, tx_hash, transfer_status, transferred_at 
  FROM staking_roi_payouts 
  WHERE user_id = 1 AND amount = 50.0000;
  ```
  - `tx_hash` should start with `0x` and be 66 characters
  - `transfer_status` should be `pending_confirmation`

- [ ] **5.6 Run ChainSync cron to confirm**
  ```bash
  php index.php chainsynccron run
  
  # Wait a few seconds for blockchain confirmation (BSC ~3 sec blocks)
  # Run it a few more times until tx is confirmed
  ```

- [ ] **5.7 Verify on-chain transaction**
  ```sql
  SELECT tx_hash, status, block_number, confirmation_count 
  FROM onchain_transactions 
  WHERE tx_hash = (SELECT tx_hash FROM staking_roi_payouts WHERE user_id = 1);
  ```
  - `status` should be `confirmed`
  - `confirmation_count` should be ≥ 15

- [ ] **5.8 Run ROI cron again to sync confirmations**
  ```bash
  php index.php roimaturitycron run
  ```

- [ ] **5.9 Verify ROI marked as paid and ledger credited**
  ```sql
  SELECT status, transfer_status, confirmed_at 
  FROM staking_roi_payouts 
  WHERE user_id = 1 AND amount = 50.0000;
  ```
  - `status` should be `paid`
  - `transfer_status` should be `confirmed`
  - `confirmed_at` should have a timestamp

  ```sql
  SELECT reference_type, amount FROM wallet_ledger 
  WHERE user_id = 1 AND reference_type = 'roi_payout';
  ```
  - Should show credit of 50 BMAN

## Phase 6: Monitoring & Alerts

- [ ] **6.1 Set up logging**
  
  Check application logs for ROI processing:
  ```bash
  tail -f application/logs/log-*.php | grep "ROI"
  ```

- [ ] **6.2 Create monitoring query**
  
  Save this as a dashboard query to check health:
  ```sql
  SELECT 
    (SELECT COUNT(*) FROM staking_roi_payouts 
     WHERE transfer_status = 'pending_broadcast') as pending_broadcast,
    (SELECT COUNT(*) FROM staking_roi_payouts 
     WHERE transfer_status = 'pending_confirmation') as pending_confirm,
    (SELECT COUNT(*) FROM staking_roi_payouts 
     WHERE transfer_status = 'failed') as failed,
    (SELECT SUM(amount) FROM staking_roi_payouts 
     WHERE transfer_status = 'failed') as failed_amount;
  ```

- [ ] **6.3 Set up alert for Treasury wallet low balance**
  
  Monitor Treasury wallet balance and alert if BNB < 0.1:
  ```php
  $balance = $this->web3bman->getBnbBalance('0xTREASURY_ADDRESS');
  if ($balance < 0.1) {
    // Send alert to admin
  }
  ```

- [ ] **6.4 Monitor failed transfers**
  
  Check weekly for failed ROI transfers:
  ```sql
  SELECT id, user_id, amount, error_message 
  FROM staking_roi_payouts 
  WHERE transfer_status = 'failed'
  ORDER BY created_at DESC;
  ```

## Phase 7: Maintenance

- [ ] **7.1 Weekly: Check ROI transfer stats**
  ```bash
  curl "https://yourdomain.com/roimaturitycron/stats"
  ```

- [ ] **7.2 Monthly: Review audit log**
  ```sql
  SELECT DATE(created_at) as date, COUNT(*) as transfers, 
         SUM(amount) as total_amount
  FROM staking_roi_transfer_log
  GROUP BY DATE(created_at)
  ORDER BY date DESC
  LIMIT 30;
  ```

- [ ] **7.3 Test recovery: Retry failed transfers**
  ```bash
  php index.php roimaturitycron retry
  ```

## Troubleshooting

If cron fails at any step, check:

1. **Token Settings not configured**
   - Go to Master → Token Settings
   - Ensure one row is marked Active
   - Verify treasury_wallet_address, treasury_pk_encrypted, rpc_url are set

2. **Treasury key decryption fails**
   - Verify ENCRYPTION_KEY in config/config.php
   - Re-encrypt the Treasury private key
   - Test decryption manually

3. **RPC endpoint unreachable**
   - Ping the RPC URL to verify connectivity
   - Check if server has internet access
   - Switch to different RPC endpoint

4. **Transaction fails on-chain**
   - Verify Treasury wallet has sufficient balance (BNB + BMAN)
   - Check gas price (adjust gas_limit or gas_price if needed)
   - Review error_message in staking_roi_payouts.error_message

5. **No ROIs being processed**
   - Verify test ROI exists with credit_date <= TODAY
   - Check status = 'pending' (not 'paid')
   - Check transfer_status IS NULL or = 'pending_broadcast'

## Support

For issues, check:
- `docs/ROI_MATURITY_CRON_GUIDE.md` - Full documentation
- Application logs: `application/logs/log-*.php`
- Database audit: `staking_roi_transfer_log` table
