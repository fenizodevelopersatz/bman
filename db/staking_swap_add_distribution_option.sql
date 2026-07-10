-- =============================================================================
-- Migration: Add coin_distribution_option and other missing columns
-- Version: 1.0
-- Date: 2026-07-09
-- Description: Add missing columns to staking_swap_orders for coin distribution
-- =============================================================================

-- Add coin_distribution_option column (if not exists)
ALTER TABLE `staking_swap_orders`
ADD COLUMN IF NOT EXISTS `coin_distribution_option` INT NOT NULL DEFAULT 1
COMMENT 'Coin distribution option (1-7) for wallet splits';

-- Add package_id if not exists (for user_stakes creation)
ALTER TABLE `staking_swap_orders`
ADD COLUMN IF NOT EXISTS `package_id` INT NULL DEFAULT NULL
COMMENT 'Associated staking package ID';

-- Add plan tracking columns if not exists
ALTER TABLE `staking_swap_orders`
ADD COLUMN IF NOT EXISTS `plan_code` VARCHAR(50) NULL DEFAULT NULL
COMMENT 'Staking plan code';

ALTER TABLE `staking_swap_orders`
ADD COLUMN IF NOT EXISTS `plan_id` INT NULL DEFAULT NULL
COMMENT 'Staking plan ID';

ALTER TABLE `staking_swap_orders`
ADD COLUMN IF NOT EXISTS `duration_years` INT NULL DEFAULT NULL
COMMENT 'Staking duration in years';

-- Verify columns were added
SELECT 'Migration complete!' as message;

SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT, IS_NULLABLE, COLUMN_COMMENT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'staking_swap_orders'
  AND COLUMN_NAME IN ('coin_distribution_option', 'package_id', 'plan_code', 'plan_id', 'duration_years')
ORDER BY ORDINAL_POSITION;
