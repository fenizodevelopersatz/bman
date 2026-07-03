-- ============================================================================
-- Staking Binary Matching Bonus + Rank Volume — Migration
-- Idempotent: safe to re-run.
--
-- * binary_volume_ledger.processed  — marks a stake's BV as already propagated
--   up the binary tree (prevents double counting on re-runs).
-- * staking_group_volume            — CUMULATIVE left/right group business
--   volume per user (never reduced), used by rank-volume / rank achievement.
--   (binary_carry holds the REDUCIBLE legs consumed by matching payouts.)
-- ============================================================================

-- 1) processed flag on binary_volume_ledger
DROP PROCEDURE IF EXISTS _bvl_add_col;
DELIMITER //
CREATE PROCEDURE _bvl_add_col()
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'binary_volume_ledger'
        AND COLUMN_NAME = 'processed') = 0 THEN
    ALTER TABLE `binary_volume_ledger`
      ADD COLUMN `processed` TINYINT(1) NOT NULL DEFAULT 0 AFTER `source_amount`,
      ADD KEY `idx_processed` (`processed`);
  END IF;
END//
DELIMITER ;
CALL _bvl_add_col();
DROP PROCEDURE IF EXISTS _bvl_add_col;

-- 2) cumulative rank / group volume per user
CREATE TABLE IF NOT EXISTS `staking_group_volume` (
  `user_id`      INT NOT NULL,
  `left_volume`  DECIMAL(20,4) NOT NULL DEFAULT 0,
  `right_volume` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Cumulative binary group volume per leg (rank achievement input)';

-- 3) matching bonus payout log (audit header; wallet moves live in wallet_ledger)
CREATE TABLE IF NOT EXISTS `staking_matching_payouts` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        INT NOT NULL,
  `matched_volume` DECIMAL(20,4) NOT NULL,
  `total_percent`  DECIMAL(6,2)  NOT NULL,
  `earning_amount` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `staking_amount` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `left_before`    DECIMAL(20,4) NOT NULL DEFAULT 0,
  `right_before`   DECIMAL(20,4) NOT NULL DEFAULT 0,
  `run_ref`        VARCHAR(32)   NULL DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_run`  (`run_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Staking binary matching bonus payout audit log';
