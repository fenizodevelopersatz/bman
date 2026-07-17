-- ============================================================================
-- Member dashboard "User Activity & Coin Trend" chart — supporting indexes
-- ----------------------------------------------------------------------------
-- The chart groups by day / month / year across wallet_ledger,
-- staking_swap_orders and bman_withdraw_requests. NONE of those tables had an
-- index on the column being grouped, so every load full-scanned them:
--
--   wallet_ledger          had idx_user_wallet(user_id, wallet_type) and
--                          idx_reference(reference_type, reference_id) —
--                          nothing on created_at at all.
--   staking_swap_orders    had idx_user(user_id), idx_status(status) —
--                          nothing on created_at.
--   bman_withdraw_requests had idx_status_created(status, created_at) but the
--                          chart buckets on completed_at, which was unindexed.
--
-- The chart filters `user_id IN (<team>) AND <date> >= ?`, so the composite
-- (user_id, created_at) is the one that actually gets used — it satisfies both
-- halves of the predicate and keeps the grouping off a full scan.
--
-- Idempotent · additive only · drops nothing · no data touched.
-- ============================================================================

DROP PROCEDURE IF EXISTS _dash_add_idx;
DELIMITER //
CREATE PROCEDURE _dash_add_idx(IN tbl VARCHAR(64), IN idx VARCHAR(64), IN ddl VARCHAR(255))
BEGIN
  -- Skip silently if the table isn't present in this deployment.
  IF (SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tbl) = 1
     AND (SELECT COUNT(*) FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tbl AND INDEX_NAME = idx) = 0 THEN
    SET @s := CONCAT('ALTER TABLE `', tbl, '` ADD ', ddl);
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END//
DELIMITER ;

-- wallet_ledger — feeds active users, bonus used, and earning coin
CALL _dash_add_idx('wallet_ledger', 'idx_user_created',
  "INDEX `idx_user_created` (`user_id`,`created_at`)");
CALL _dash_add_idx('wallet_ledger', 'idx_created',
  "INDEX `idx_created` (`created_at`)");
CALL _dash_add_idx('wallet_ledger', 'idx_wallet_ref_created',
  "INDEX `idx_wallet_ref_created` (`wallet_type`,`reference_type`,`created_at`)");

-- staking_swap_orders — feeds staking done
CALL _dash_add_idx('staking_swap_orders', 'idx_user_created',
  "INDEX `idx_user_created` (`user_id`,`created_at`)");
CALL _dash_add_idx('staking_swap_orders', 'idx_status_cron_created',
  "INDEX `idx_status_cron_created` (`status`,`cron_status`,`created_at`)");

-- bman_withdraw_requests — feeds coin withdrawal (buckets on completed_at)
CALL _dash_add_idx('bman_withdraw_requests', 'idx_user_completed',
  "INDEX `idx_user_completed` (`user_id`,`completed_at`)");
CALL _dash_add_idx('bman_withdraw_requests', 'idx_status_completed',
  "INDEX `idx_status_completed` (`status`,`completed_at`)");

DROP PROCEDURE IF EXISTS _dash_add_idx;
