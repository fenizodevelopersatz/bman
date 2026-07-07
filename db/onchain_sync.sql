-- ============================================================================
-- On-Chain Transaction Lifecycle + Efficient Balance Sync — schema
-- ----------------------------------------------------------------------------
--  * Withdrawal lifecycle fields (tx hash, status, block, gas, confirmations…)
--  * onchain_transactions verification/lifecycle fields
--  * wallet_balance_sync  — last-known RPC balances + cursor (last block/tx)
--  * rpc_sync_log         — every sync attempt / balance change / RPC failure / API call
--  * onchain_tx_events.rpc_endpoint — which RPC produced a state change (audit)
-- Idempotent: safe to re-run (guarded column adds).
-- ============================================================================

DROP PROCEDURE IF EXISTS `_ocadd`;
DELIMITER //
CREATE PROCEDURE `_ocadd`(IN tbl VARCHAR(64), IN col VARCHAR(64), IN ddl TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tbl AND COLUMN_NAME = col) THEN
    SET @s = CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN ', ddl);
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END//
DELIMITER ;

-- --- withdrawals: full on-chain payout lifecycle -----------------------------
CALL _ocadd('withdrawals','tx_hash',        '`tx_hash` VARCHAR(120) DEFAULT NULL');
CALL _ocadd('withdrawals','chain_status',    "`chain_status` ENUM('created','broadcasting','pending','confirmed','failed','reverted','cancelled') DEFAULT 'created'");
CALL _ocadd('withdrawals','block_number',    '`block_number` BIGINT UNSIGNED DEFAULT NULL');
CALL _ocadd('withdrawals','confirmations',   '`confirmations` INT DEFAULT NULL');
CALL _ocadd('withdrawals','gas_used',        '`gas_used` BIGINT DEFAULT NULL');
CALL _ocadd('withdrawals','gas_price',       '`gas_price` DECIMAL(38,0) DEFAULT NULL');
CALL _ocadd('withdrawals','gas_fee_total',   '`gas_fee_total` DECIMAL(38,18) DEFAULT NULL');
CALL _ocadd('withdrawals','nonce',           '`nonce` BIGINT DEFAULT NULL');
CALL _ocadd('withdrawals','explorer_url',    '`explorer_url` VARCHAR(255) DEFAULT NULL');
CALL _ocadd('withdrawals','failure_reason',  '`failure_reason` VARCHAR(160) DEFAULT NULL');
CALL _ocadd('withdrawals','onchain_tx_id',   '`onchain_tx_id` BIGINT UNSIGNED DEFAULT NULL');
CALL _ocadd('withdrawals','wallet_ledger_id','`wallet_ledger_id` BIGINT UNSIGNED DEFAULT NULL');
CALL _ocadd('withdrawals','broadcast_at',    '`broadcast_at` DATETIME DEFAULT NULL');
CALL _ocadd('withdrawals','balance_debited', "`balance_debited` TINYINT(1) NOT NULL DEFAULT 0");  -- double-deduct guard

-- --- onchain_transactions: verification / lifecycle --------------------------
CALL _ocadd('onchain_transactions','request_tx_hash', '`request_tx_hash` VARCHAR(120) DEFAULT NULL');  -- e.g. swap deposit hash
CALL _ocadd('onchain_transactions','delivery_tx_hash','`delivery_tx_hash` VARCHAR(120) DEFAULT NULL'); -- e.g. swap BMAN delivery
CALL _ocadd('onchain_transactions','last_verified_at','`last_verified_at` DATETIME DEFAULT NULL');
CALL _ocadd('onchain_transactions','finalized_at',    '`finalized_at` DATETIME DEFAULT NULL');
CALL _ocadd('onchain_transactions','rpc_endpoint',    '`rpc_endpoint` VARCHAR(255) DEFAULT NULL');
CALL _ocadd('onchain_transactions','reorg_count',     '`reorg_count` INT NOT NULL DEFAULT 0');

-- --- audit events: which RPC produced the change -----------------------------
CALL _ocadd('onchain_tx_events','rpc_endpoint', '`rpc_endpoint` VARCHAR(255) DEFAULT NULL');

DROP PROCEDURE IF EXISTS `_ocadd`;

-- --- last-known balances + scan cursor per (address, token) ------------------
CREATE TABLE IF NOT EXISTS `wallet_balance_sync` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        INT DEFAULT NULL,
  `address`        VARCHAR(120) NOT NULL,
  `token`          VARCHAR(20)  NOT NULL DEFAULT 'BNB',   -- BNB | USDT | BMAN
  `contract`       VARCHAR(120) DEFAULT NULL,
  `last_balance`   DECIMAL(38,18) NOT NULL DEFAULT 0,     -- human units
  `last_balance_raw` DECIMAL(65,0) NOT NULL DEFAULT 0,    -- smallest units (max MySQL precision)
  `last_block`     BIGINT UNSIGNED DEFAULT NULL,          -- scan cursor
  `last_tx_hash`   VARCHAR(120) DEFAULT NULL,
  `last_synced_at` DATETIME DEFAULT NULL,
  `sync_count`     INT NOT NULL DEFAULT 0,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_addr_token` (`address`,`token`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --- audit log of every sync attempt / balance change / RPC failure / API call
CREATE TABLE IF NOT EXISTS `rpc_sync_log` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `run_id`        VARCHAR(40)  DEFAULT NULL,               -- groups one cron pass
  `scope`         VARCHAR(40)  NOT NULL,                   -- balance_sync|verify|import|failover
  `address`       VARCHAR(120) DEFAULT NULL,
  `token`         VARCHAR(20)  DEFAULT NULL,
  `endpoint`      VARCHAR(255) DEFAULT NULL,               -- RPC or explorer URL used
  `api_used`      VARCHAR(20)  NOT NULL DEFAULT 'rpc',     -- rpc | bscscan
  `ok`            TINYINT(1)   NOT NULL DEFAULT 1,
  `diff_detected` TINYINT(1)   DEFAULT NULL,
  `balance_before` DECIMAL(38,18) DEFAULT NULL,
  `balance_after`  DECIMAL(38,18) DEFAULT NULL,
  `tx_imported`   INT DEFAULT 0,
  `message`       VARCHAR(255) DEFAULT NULL,
  `duration_ms`   INT DEFAULT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_run` (`run_id`),
  KEY `idx_scope` (`scope`),
  KEY `idx_api` (`api_used`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
