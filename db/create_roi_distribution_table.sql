-- Create ROI Distribution Tracking Table
-- Tracks maturity payouts and ROI release when staking completes

CREATE TABLE IF NOT EXISTS `roi_distribution` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `staking_swap_orders_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,

  -- Investment Details
  `principal_amount` decimal(20, 8) NOT NULL DEFAULT '0.00000000' COMMENT 'Original BMAN staked',
  `duration_years` int(11) NOT NULL DEFAULT 1 COMMENT 'Staking duration',
  `roi_rate_percent` decimal(10, 4) NOT NULL DEFAULT '0.0000' COMMENT 'ROI rate locked at purchase',

  -- ROI Calculation
  `total_roi_earned` decimal(20, 8) NOT NULL DEFAULT '0.00000000' COMMENT 'Total ROI from day 1 to maturity',
  `roi_already_paid` decimal(20, 8) NOT NULL DEFAULT '0.00000000' COMMENT 'ROI already distributed hourly',
  `roi_remaining` decimal(20, 8) NOT NULL DEFAULT '0.00000000' COMMENT 'ROI left to pay at maturity',
  `bonus_amount` decimal(20, 8) NOT NULL DEFAULT '0.00000000' COMMENT 'Bonus BMAN (if any)',

  -- Maturity Info
  `purchase_date` datetime NOT NULL COMMENT 'When staking was purchased',
  `maturity_date` datetime NOT NULL COMMENT 'When staking completes',
  `days_elapsed` int(11) NOT NULL DEFAULT 0 COMMENT 'Days from purchase to maturity',
  `is_matured` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = past maturity date',

  -- Status Tracking
  `distribution_status` enum('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending' COMMENT 'Maturity payout status',
  `distribution_date` datetime DEFAULT NULL COMMENT 'When ROI was released',
  `tx_hash` varchar(255) DEFAULT NULL COMMENT 'Transaction hash of payout',
  `error_message` text COMMENT 'Error if payout failed',

  -- Audit
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- Foreign Keys & Indexes
  KEY `idx_staking_swap_orders_id` (`staking_swap_orders_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_maturity_date` (`maturity_date`),
  KEY `idx_distribution_status` (`distribution_status`),
  KEY `idx_is_matured` (`is_matured`),

  CONSTRAINT `fk_roi_distribution_staking`
    FOREIGN KEY (`staking_swap_orders_id`)
    REFERENCES `staking_swap_orders` (`id`)
    ON DELETE CASCADE,

  CONSTRAINT `fk_roi_distribution_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`user_id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tracks ROI distribution at staking maturity';
