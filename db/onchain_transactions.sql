-- ============================================================================
-- On-Chain Transactions — unified, indexed ledger of every blockchain-facing
-- wallet transaction (deposits, withdrawals, bonus reductions, stake purchases,
-- ROI settlements, transfers…). Powers Admin ▸ Finance ▸ On-Chain Transactions:
-- server-side filtering / sorting / pagination for millions of rows, plus a rich
-- per-tx detail modal (stored fields + live RPC enrichment).
--
-- Deep fields the standard RPC/receipt cannot give (internal txs, execution
-- trace, decoded params, event-log ABI names) are left NULL and filled live from
-- a BscScan API key / tracing node when configured — see docs/13.
--
-- Idempotent: safe to re-run (backfill guarded by NOT EXISTS).
-- ============================================================================

CREATE TABLE IF NOT EXISTS `onchain_transactions` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  -- identity / general
  `tx_hash`            VARCHAR(120) DEFAULT NULL,
  `network`            VARCHAR(20)  NOT NULL DEFAULT 'mainnet',
  `chain_id`           INT          NOT NULL DEFAULT 56,
  `wallet_type`        ENUM('usdt','exchange','earning','staking','bonus') DEFAULT NULL,
  `tx_type`            VARCHAR(40)  NOT NULL,                 -- deposit|withdrawal|bonus_reduction|stake_purchase|roi|transfer|...
  `status`             ENUM('pending','processing','confirmed','failed','reverted','partial','cancelled') NOT NULL DEFAULT 'pending',

  -- parties
  `from_address`       VARCHAR(120) DEFAULT NULL,
  `to_address`         VARCHAR(120) DEFAULT NULL,
  `user_id`            INT          DEFAULT NULL,
  `admin_id`           INT          DEFAULT NULL,

  -- token
  `token_symbol`       VARCHAR(20)  DEFAULT NULL,
  `token_name`         VARCHAR(80)  DEFAULT NULL,
  `token_contract`     VARCHAR(120) DEFAULT NULL,
  `token_decimals`     TINYINT UNSIGNED DEFAULT NULL,
  `amount`             DECIMAL(38,18) NOT NULL DEFAULT 0,

  -- block / position
  `block_number`       BIGINT UNSIGNED DEFAULT NULL,
  `block_timestamp`    DATETIME     DEFAULT NULL,
  `confirmation_count` INT          DEFAULT NULL,
  `nonce`              BIGINT       DEFAULT NULL,
  `tx_index`           INT          DEFAULT NULL,

  -- gas
  `gas_limit`          BIGINT       DEFAULT NULL,
  `gas_used`           BIGINT       DEFAULT NULL,
  `gas_price`          DECIMAL(38,0) DEFAULT NULL,           -- wei
  `max_fee_per_gas`    DECIMAL(38,0) DEFAULT NULL,
  `max_priority_fee`   DECIMAL(38,0) DEFAULT NULL,
  `gas_fee_total`      DECIMAL(38,18) DEFAULT NULL,          -- BNB
  `native_used`        DECIMAL(38,18) DEFAULT NULL,          -- BNB value moved (native transfers)
  `estimated_gas`      BIGINT       DEFAULT NULL,
  `gas_refund`         BIGINT       DEFAULT NULL,

  -- execution
  `contract_address`   VARCHAR(120) DEFAULT NULL,
  `method_name`        VARCHAR(80)  DEFAULT NULL,
  `method_signature`   VARCHAR(120) DEFAULT NULL,
  `input_data`         MEDIUMTEXT   DEFAULT NULL,
  `return_data`        TEXT         DEFAULT NULL,

  -- ledger (double-entry linkage)
  `debit_wallet`       VARCHAR(20)  DEFAULT NULL,
  `credit_wallet`      VARCHAR(20)  DEFAULT NULL,
  `balance_before`     DECIMAL(38,18) DEFAULT NULL,
  `balance_after`      DECIMAL(38,18) DEFAULT NULL,
  `wallet_ledger_id`   BIGINT UNSIGNED DEFAULT NULL,

  -- relations
  `reference_type`     VARCHAR(40)  DEFAULT NULL,
  `reference_id`       VARCHAR(64)  DEFAULT NULL,
  `linked_deposit_id`  BIGINT UNSIGNED DEFAULT NULL,
  `linked_withdrawal_id` BIGINT UNSIGNED DEFAULT NULL,
  `parent_tx_id`       BIGINT UNSIGNED DEFAULT NULL,

  -- failure / partial
  `failure_reason`     VARCHAR(120) DEFAULT NULL,            -- out_of_gas|insufficient_balance|contract_error|invalid_nonce|network_rejection|validation_error|rpc_error
  `revert_message`     VARCHAR(255) DEFAULT NULL,
  `completed_steps`    VARCHAR(255) DEFAULT NULL,
  `failed_steps`       VARCHAR(255) DEFAULT NULL,
  `retry_status`       VARCHAR(40)  DEFAULT NULL,
  `retry_count`        INT          NOT NULL DEFAULT 0,
  `linked_retry_tx_id` BIGINT UNSIGNED DEFAULT NULL,

  -- audit
  `created_by`         INT          DEFAULT NULL,
  `ip_address`         VARCHAR(64)  DEFAULT NULL,
  `processing_server`  VARCHAR(80)  DEFAULT NULL,
  `processing_ms`      INT          DEFAULT NULL,
  `created_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_txhash`   (`tx_hash`),
  KEY `idx_wallet`   (`wallet_type`),
  KEY `idx_status`   (`status`),
  KEY `idx_network`  (`network`),
  KEY `idx_type`     (`tx_type`),
  KEY `idx_block`    (`block_number`),
  KEY `idx_user`     (`user_id`),
  KEY `idx_created`  (`created_at`),
  KEY `idx_from`     (`from_address`),
  KEY `idx_to`       (`to_address`),
  KEY `idx_token`    (`token_symbol`),
  KEY `idx_ref`      (`reference_type`,`reference_id`),
  KEY `idx_gasfee`   (`gas_fee_total`),
  KEY `idx_status_created` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Backfill 1: real USDT deposits (wallet_deposits) — the bulk of on-chain history
-- ----------------------------------------------------------------------------
INSERT INTO `onchain_transactions`
  (`tx_hash`,`network`,`chain_id`,`wallet_type`,`tx_type`,`status`,
   `to_address`,`user_id`,`token_symbol`,`token_name`,`token_contract`,`token_decimals`,
   `amount`,`block_number`,`confirmation_count`,`credit_wallet`,
   `reference_type`,`reference_id`,`linked_deposit_id`,`created_at`)
SELECT
  wd.tx_hash,
  COALESCE(NULLIF(wd.network,''),'mainnet'),
  (SELECT chain_id FROM token_settings WHERE status=1 LIMIT 1),
  'usdt',
  'deposit',
  CASE wd.status
    WHEN 'credited'   THEN 'confirmed'
    WHEN 'confirmed'  THEN 'confirmed'
    WHEN 'confirming' THEN 'processing'
    WHEN 'pending'    THEN 'pending'
    WHEN 'failed'     THEN 'failed'
    WHEN 'expired'    THEN 'cancelled'
    ELSE 'pending'
  END,
  wd.wallet_address,
  wd.user_id,
  'USDT',
  (SELECT usdt_name FROM token_settings WHERE status=1 LIMIT 1),
  (SELECT usdt_contract FROM token_settings WHERE status=1 LIMIT 1),
  (SELECT usdt_decimals FROM token_settings WHERE status=1 LIMIT 1),
  wd.amount_usdt,
  wd.block_number,
  wd.confirmations,
  'usdt',
  'deposit',
  wd.tx_hash,
  wd.id,
  wd.created_at
FROM `wallet_deposits` wd
WHERE NOT EXISTS (
  SELECT 1 FROM `onchain_transactions` o
  WHERE o.reference_type='deposit' AND o.linked_deposit_id = wd.id
);

-- ----------------------------------------------------------------------------
-- Backfill 2: bonus reductions (bonus_reduction_log)
-- ----------------------------------------------------------------------------
INSERT INTO `onchain_transactions`
  (`tx_hash`,`network`,`chain_id`,`wallet_type`,`tx_type`,`status`,
   `from_address`,`to_address`,`user_id`,`token_symbol`,`token_name`,`token_contract`,`token_decimals`,
   `amount`,`debit_wallet`,`credit_wallet`,`wallet_ledger_id`,
   `reference_type`,`failure_reason`,`created_at`)
SELECT
  l.tx_hash,
  'mainnet',
  (SELECT chain_id FROM token_settings WHERE status=1 LIMIT 1),
  'bonus',
  'bonus_reduction',
  CASE l.status WHEN 'sent' THEN 'confirmed' WHEN 'internal' THEN 'confirmed' WHEN 'failed' THEN 'failed' ELSE 'pending' END,
  l.from_address,
  l.to_address,
  l.user_id,
  'BMAN',
  (SELECT bman_name FROM token_settings WHERE status=1 LIMIT 1),
  (SELECT bman_contract FROM token_settings WHERE status=1 LIMIT 1),
  (SELECT bman_decimals FROM token_settings WHERE status=1 LIMIT 1),
  l.amount,
  'bonus',
  'admin',
  l.wallet_ledger_id,
  'bonus_reduction',
  CASE WHEN l.status='failed' THEN 'rpc_error' ELSE NULL END,
  l.created_at
FROM `bonus_reduction_log` l
WHERE NOT EXISTS (
  SELECT 1 FROM `onchain_transactions` o
  WHERE o.reference_type='bonus_reduction' AND o.wallet_ledger_id = l.wallet_ledger_id AND o.wallet_ledger_id IS NOT NULL
);
