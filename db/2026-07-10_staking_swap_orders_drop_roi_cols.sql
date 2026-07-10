-- staking_swap_orders: drop ROI + duplicate columns.
--
-- ROI concerns move out of the swap order:
--   * roi_rate / maturity_date  -> managed in roi_staking_management (before)
--                                  and roi_distribution (after).
--   * coin_distribution_option_id -> duplicate of coin_distribution_option (kept).
--
-- Apply AFTER the code changes are deployed (Lendingcontroller swap_purchase /
-- swap_order_details, StakingSwap_model, api/Staking, and the legacy ROI maturity
-- crons which now read roi_rate/maturity from roi_staking_management).

ALTER TABLE `staking_swap_orders`
  DROP COLUMN `roi_rate`,
  DROP COLUMN `maturity_date`,
  DROP COLUMN `coin_distribution_option_id`;
