-- ============================================================================
-- Staking Treasury Payments — records each USDT payment routed to the Admin
-- Treasury Wallet at stake-purchase time (business flow: USDT → Admin Treasury
-- → BMAN returned to user's Staking wallet). The user-side USDT debit already
-- lives in wallet_ledger; this is the admin-facing "money received" ledger.
-- Idempotent: safe to re-run.
-- ============================================================================
CREATE TABLE IF NOT EXISTS `staking_treasury_payments` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`         INT NOT NULL,
  `stake_id`        BIGINT UNSIGNED NOT NULL,
  `ref`             VARCHAR(32) NOT NULL,
  `usdt_amount`     DECIMAL(20,8) NOT NULL COMMENT 'USDT taken from the user',
  `bman_amount`     DECIMAL(20,4) NOT NULL COMMENT 'BMAN returned (locked) to user',
  `exchange_rate`   DECIMAL(24,8) NOT NULL COMMENT 'rate snapshot at purchase',
  `exchange_type`   VARCHAR(20)  NOT NULL DEFAULT 'usdt_to_bman',
  `treasury_wallet` VARCHAR(100) NULL DEFAULT NULL,
  `tx_hash`         VARCHAR(120) NULL DEFAULT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user`  (`user_id`),
  KEY `idx_stake` (`stake_id`),
  KEY `idx_ref`   (`ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='USDT payments routed to the Admin Treasury Wallet on stake purchase';
