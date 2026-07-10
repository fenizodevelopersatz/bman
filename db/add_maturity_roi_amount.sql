-- Add maturity_roi_amount column to staking_swap_orders
-- Stores the total ROI amount that will be released at maturity

ALTER TABLE `staking_swap_orders`
ADD COLUMN `maturity_roi_amount` DECIMAL(20, 8) DEFAULT '0.00000000' AFTER `roi_return_status`
COMMENT 'Total ROI amount to be released at maturity (Principal × ROI% / 100)';
