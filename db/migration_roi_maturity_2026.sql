-- ============================================================================
-- ROI Maturity On-Chain Transfer Migration
-- Adds blockchain transaction tracking to staking_roi_payouts
-- ============================================================================

-- Alter staking_roi_payouts to support on-chain transfers
ALTER TABLE `staking_roi_payouts`
ADD COLUMN `tx_hash` VARCHAR(255) UNIQUE DEFAULT NULL COMMENT 'Blockchain tx hash' AFTER `status`,
ADD COLUMN `transfer_status` ENUM('pending_broadcast','pending_confirmation','confirmed','failed','reverted') DEFAULT 'pending_broadcast' COMMENT 'On-chain transfer status' AFTER `tx_hash`,
ADD COLUMN `transferred_at` DATETIME DEFAULT NULL COMMENT 'When we broadcast the transfer' AFTER `transfer_status`,
ADD COLUMN `confirmed_at` DATETIME DEFAULT NULL COMMENT 'When confirmed on-chain' AFTER `transferred_at`,
ADD COLUMN `block_number` BIGINT UNSIGNED DEFAULT NULL COMMENT 'Block where tx confirmed' AFTER `confirmed_at`,
ADD COLUMN `confirmation_count` INT UNSIGNED DEFAULT 0 COMMENT 'Number of block confirmations' AFTER `block_number`,
ADD COLUMN `network` VARCHAR(20) DEFAULT 'bsc' COMMENT 'Chain network (bsc, eth, etc)' AFTER `confirmation_count`,
ADD COLUMN `error_message` TEXT DEFAULT NULL COMMENT 'Last error if failed' AFTER `network`,
ADD KEY `idx_status` (`status`),
ADD KEY `idx_transfer_status` (`transfer_status`),
ADD KEY `idx_credit_date` (`credit_date`),
ADD KEY `idx_user_credit` (`user_id`, `credit_date`);

-- Create ROI transfer log table for audit trail
CREATE TABLE IF NOT EXISTS `staking_roi_transfer_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `roi_payout_id` BIGINT UNSIGNED NOT NULL,
  `user_id` INT NOT NULL,
  `amount` DECIMAL(20,4) NOT NULL,
  `from_address` VARCHAR(255) NOT NULL,
  `to_address` VARCHAR(255) NOT NULL,
  `tx_hash` VARCHAR(255) DEFAULT NULL,
  `transfer_status` ENUM('pending_broadcast','pending_confirmation','confirmed','failed','reverted') DEFAULT 'pending_broadcast',
  `attempt_no` TINYINT UNSIGNED DEFAULT 1,
  `error_message` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_roi_payout` (`roi_payout_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_tx_hash` (`tx_hash`),
  CONSTRAINT `fk_roi_transfer_payout` FOREIGN KEY (`roi_payout_id`) REFERENCES `staking_roi_payouts`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
