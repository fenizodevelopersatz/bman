-- Add roi_rate column to staking_swap_orders table
-- This stores the ROI rate at purchase time for historical tracking

ALTER TABLE `staking_swap_orders`
ADD COLUMN `roi_rate` DECIMAL(10, 4) DEFAULT 0 AFTER `duration_years` COMMENT 'ROI percentage rate at time of purchase (e.g., 150 for 150%)';

-- Backfill with 0 for existing orders (can be updated manually if needed)
UPDATE `staking_swap_orders` SET `roi_rate` = 0 WHERE `roi_rate` IS NULL;
