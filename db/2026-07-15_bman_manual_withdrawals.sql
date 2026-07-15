-- ============================================================================
-- Manual BMAN withdrawal workflow
-- Creates request, hold, and audit tables used by the new admin/user flow.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `bman_withdraw_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_no` VARCHAR(50) NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `source_wallet` ENUM('exchange','earning','staking','bonus') NOT NULL,
  `request_amount` DECIMAL(18,8) NOT NULL DEFAULT 0,
  `fee_amount` DECIMAL(18,8) NOT NULL DEFAULT 0,
  `net_amount` DECIMAL(18,8) NOT NULL DEFAULT 0,
  `withdraw_address` VARCHAR(255) NOT NULL,
  `remark` TEXT DEFAULT NULL,
  `tx_hash` VARCHAR(255) DEFAULT NULL,
  `admin_remark` TEXT DEFAULT NULL,
  `status` ENUM('pending','approved','processing','completed','rejected','failed') NOT NULL DEFAULT 'pending',
  `approved_by` BIGINT UNSIGNED DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_request_no` (`request_no`),
  KEY `idx_user_status` (`user_id`,`status`),
  KEY `idx_status_created` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `wallet_withdraw_holds` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `request_id` BIGINT UNSIGNED NOT NULL,
  `wallet_type` ENUM('exchange','earning','staking','bonus') NOT NULL,
  `hold_amount` DECIMAL(18,8) NOT NULL DEFAULT 0,
  `released_amount` DECIMAL(18,8) NOT NULL DEFAULT 0,
  `status` ENUM('held','released','consumed') NOT NULL DEFAULT 'held',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `released_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_request` (`request_id`),
  KEY `idx_user_wallet` (`user_id`,`wallet_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `withdraw_audit_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` BIGINT UNSIGNED NOT NULL,
  `admin_id` BIGINT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(50) NOT NULL,
  `old_status` VARCHAR(30) DEFAULT NULL,
  `new_status` VARCHAR(30) DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_request` (`request_id`),
  KEY `idx_admin` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
