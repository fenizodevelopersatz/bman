-- Gas Fee Transactions page fix.
--
-- GasFeeTx_model (and other consumers: DashboardStats_model, WalletTracker_model,
-- the gas_fee_transactions + onchain_transactions views) SELECT/aggregate
-- `onchain_transactions.gas_price_gwei`, but the base table only stored
-- `gas_price` (wei). The missing column made admin/finance/gas-fee-transactions
-- return HTTP 500. Add it as a generated column derived from gas_price so it is
-- always correct (gwei = wei / 1e9) for existing and future rows, with no
-- insert-path changes (nothing writes this column).

ALTER TABLE `onchain_transactions`
  ADD COLUMN `gas_price_gwei` DECIMAL(38,9)
  AS (`gas_price` / 1000000000) PERSISTENT AFTER `gas_price`;
