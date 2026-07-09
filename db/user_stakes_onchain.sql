-- ============================================================================
-- On-chain tracking on user_stakes — a staking purchase is backed by a REAL
-- blockchain transaction. The stake is created PROCESSING right after broadcast
-- (portfolio shows it immediately) and promoted to ACTIVE once the tx confirms.
-- Idempotent: adds columns only if missing. Purely additive.
-- ============================================================================

DROP PROCEDURE IF EXISTS _us_add_onchain;
DELIMITER //
CREATE PROCEDURE _us_add_onchain()
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_stakes'
        AND COLUMN_NAME = 'tx_hash') = 0 THEN
    ALTER TABLE `user_stakes`
      ADD COLUMN `tx_hash`       VARCHAR(80)  NULL DEFAULT NULL AFTER `status`,
      ADD COLUMN `block_number`  BIGINT UNSIGNED NULL DEFAULT NULL AFTER `tx_hash`,
      ADD COLUMN `confirmations` INT NOT NULL DEFAULT 0 AFTER `block_number`,
      ADD COLUMN `gas_fee`       DECIMAL(30,18) NULL DEFAULT NULL AFTER `confirmations`,
      ADD COLUMN `network`       VARCHAR(20)  NULL DEFAULT NULL AFTER `gas_fee`,
      ADD COLUMN `chain_status`  VARCHAR(20)  NOT NULL DEFAULT 'pending' AFTER `network`,
      ADD COLUMN `onchain_tx_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `chain_status`,
      ADD COLUMN `swap_order_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `onchain_tx_id`,
      ADD KEY `idx_us_txhash` (`tx_hash`),
      ADD KEY `idx_us_chain_status` (`chain_status`);
  END IF;
END//
DELIMITER ;
CALL _us_add_onchain();
DROP PROCEDURE IF EXISTS _us_add_onchain;
