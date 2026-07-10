-- ROI Staking Management Table
-- Comprehensive table to manage Fixed, Regular, and Combo ROI plans
-- Tracks each payment schedule and distribution status

CREATE TABLE IF NOT EXISTS `roi_staking_management` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

  -- Reference
  `staking_swap_orders_id` BIGINT UNSIGNED NOT NULL,
  `user_id` INT(11) NOT NULL,
  `ref` VARCHAR(100) NOT NULL UNIQUE,

  -- Plan Details
  `plan_type` ENUM('fixed', 'regular', 'combo') NOT NULL DEFAULT 'fixed'
    COMMENT 'fixed=one payment at maturity, regular=3 monthly payments, combo=both',
  `principal_amount` DECIMAL(20, 8) NOT NULL COMMENT 'Original amount staked',
  `roi_rate_percent` DECIMAL(10, 4) NOT NULL COMMENT 'ROI percentage (e.g., 150%)',
  `total_roi_amount` DECIMAL(20, 8) NOT NULL COMMENT 'Total ROI to be distributed',
  `duration_years` INT(11) NOT NULL COMMENT 'Staking duration',

  -- Fixed Plan (Single Payment)
  `fixed_payment_amount` DECIMAL(20, 8) DEFAULT NULL COMMENT 'ROI paid at maturity (for fixed/combo)',
  `fixed_maturity_date` DATETIME DEFAULT NULL COMMENT 'When fixed ROI is paid',
  `fixed_status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending' COMMENT 'Fixed payment status',
  `fixed_paid_date` DATETIME DEFAULT NULL COMMENT 'When fixed ROI was actually paid',
  `fixed_tx_hash` VARCHAR(255) DEFAULT NULL COMMENT 'Transaction hash for fixed payment',

  -- Regular Plan (3 Monthly Payments)
  `regular_payment_amount` DECIMAL(20, 8) DEFAULT NULL COMMENT 'ROI per monthly payment (for regular/combo)',
  `regular_payment_count` INT(11) DEFAULT 3 COMMENT 'Number of monthly payments (usually 3)',
  `regular_payments_completed` INT(11) DEFAULT 0 COMMENT 'How many monthly payments done',

  -- Payment Schedule (Track each of 3 payments)
  `payment_day_5_amount` DECIMAL(20, 8) DEFAULT NULL,
  `payment_day_5_date` DATETIME DEFAULT NULL,
  `payment_day_5_status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
  `payment_day_5_tx_hash` VARCHAR(255) DEFAULT NULL,

  `payment_day_15_amount` DECIMAL(20, 8) DEFAULT NULL,
  `payment_day_15_date` DATETIME DEFAULT NULL,
  `payment_day_15_status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
  `payment_day_15_tx_hash` VARCHAR(255) DEFAULT NULL,

  `payment_day_25_amount` DECIMAL(20, 8) DEFAULT NULL,
  `payment_day_25_date` DATETIME DEFAULT NULL,
  `payment_day_25_status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
  `payment_day_25_tx_hash` VARCHAR(255) DEFAULT NULL,

  -- Overall Status
  `overall_status` ENUM('active', 'in_progress', 'completed', 'failed') NOT NULL DEFAULT 'active'
    COMMENT 'active=waiting, in_progress=paying, completed=all paid, failed=error',
  `total_paid_amount` DECIMAL(20, 8) DEFAULT 0 COMMENT 'Total ROI actually paid so far',
  `remaining_to_pay` DECIMAL(20, 8) DEFAULT NULL COMMENT 'ROI still pending',

  -- Gas Fee Tracking
  `gas_fee_amount` DECIMAL(20, 8) DEFAULT 0 COMMENT 'Gas fee deducted from ROI',
  `gas_paid_by` ENUM('admin', 'user', 'platform') DEFAULT 'admin' COMMENT 'Who pays gas',
  `total_gas_paid` DECIMAL(20, 8) DEFAULT 0 COMMENT 'Cumulative gas fees',

  -- Audit
  `next_payment_date` DATETIME DEFAULT NULL COMMENT 'Next scheduled payment',
  `error_message` TEXT COMMENT 'Error details if failed',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- Indexes
  KEY `idx_staking_swap_orders_id` (`staking_swap_orders_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_plan_type` (`plan_type`),
  KEY `idx_overall_status` (`overall_status`),
  KEY `idx_next_payment_date` (`next_payment_date`),
  KEY `idx_fixed_maturity_date` (`fixed_maturity_date`),

  -- Foreign Key
  CONSTRAINT `fk_roi_staking_swap_orders`
    FOREIGN KEY (`staking_swap_orders_id`)
    REFERENCES `staking_swap_orders` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='ROI Staking Management: Fixed, Regular, and Combo plan tracking';

-- Add column to staking_swap_orders to reference roi_staking_management
ALTER TABLE `staking_swap_orders`
ADD COLUMN `roi_staking_management_id` BIGINT UNSIGNED DEFAULT NULL AFTER `maturity_roi_amount`,
ADD KEY `idx_roi_staking_management_id` (`roi_staking_management_id`);
