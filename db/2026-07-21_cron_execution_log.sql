-- ============================================================================
-- Cron run log — one row per scheduled-job execution (not per credit/payout;
-- individual money movements already live in wallet_ledger / onchain_transactions
-- and are surfaced via WalletTracker_model). Feeds the "Cron Run Log" tab on
-- the admin All Transactions page (admin/all-transaction).
-- ============================================================================

CREATE TABLE IF NOT EXISTS `cron_execution_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cron_name` VARCHAR(80) NOT NULL,
  `status` VARCHAR(20) NOT NULL,
  `summary` TEXT NULL DEFAULT NULL,
  `duration_ms` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cron_name` (`cron_name`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
