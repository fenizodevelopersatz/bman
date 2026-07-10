-- ============================================================================
-- RESET STAKING & WALLET BALANCES
-- ============================================================================
-- WARNING: This script will reset all staking records and wallet balances.
-- Make a database backup BEFORE running this!
-- ============================================================================

-- ========== OPTION 1: RESET ALL STAKING RECORDS FOR A SPECIFIC USER ==========
-- Replace USER_ID with the actual user ID
-- This will:
--   - Clear all staking swap orders
--   - Clear ceiling wallet holds for that user
--   - Clear wallet ledger entries for staking transactions
--   - Reset wallet balances to 0

DELIMITER //

DROP PROCEDURE IF EXISTS reset_user_staking //
CREATE PROCEDURE reset_user_staking(IN p_user_id INT)
BEGIN
  DECLARE v_user_exists INT;

  -- Check if user exists
  SELECT COUNT(*) INTO v_user_exists FROM users WHERE id = p_user_id;
  IF v_user_exists = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'User does not exist';
  END IF;

  START TRANSACTION;

  -- 1. Delete staking swap orders for this user
  DELETE FROM staking_swap_orders WHERE user_id = p_user_id;

  -- 2. Reset ceiling wallet for this user
  UPDATE ceiling_wallet SET held_balance = 0, total_held = 0, total_released = 0
    WHERE user_id = p_user_id;

  -- 3. Delete ceiling wallet ledger entries for this user
  DELETE FROM ceiling_wallet_ledger WHERE user_id = p_user_id;

  -- 4. Delete wallet ledger entries related to staking for this user
  DELETE FROM wallet_ledger
    WHERE user_id = p_user_id
      AND reference_type IN ('stake_purchase', 'roi', 'admin_adjustment', 'staking_purchase');

  -- 5. Reset wallet balances for this user
  UPDATE user_wallets
    SET exchange_balance = 0,
        earning_balance = 0,
        staking_balance = 0,
        bonus_balance = 0,
        usd_balance = 0,
        total_deposit_usdt = 0,
        total_withdraw_usdt = 0
    WHERE user_id = p_user_id;

  COMMIT;
  SELECT CONCAT('Successfully reset all staking & wallet data for user ', p_user_id) AS result;
END //

DELIMITER ;

-- ========== OPTION 2: RESET ALL RECENT STAKING RECORDS (LAST N DAYS) ==========
-- This will delete all staking orders created in the last N days (default: 7 days)

DELIMITER //

DROP PROCEDURE IF EXISTS reset_recent_staking //
CREATE PROCEDURE reset_recent_staking(IN p_days INT)
BEGIN
  DECLARE v_count INT;

  IF p_days IS NULL OR p_days <= 0 THEN
    SET p_days = 7;
  END IF;

  START TRANSACTION;

  -- Find and delete recent staking orders
  DELETE FROM staking_swap_orders
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL p_days DAY);
  SET v_count = ROW_COUNT();

  -- Clean up related ceiling wallet entries
  DELETE FROM ceiling_wallet_ledger
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL p_days DAY);

  COMMIT;
  SELECT CONCAT('Deleted ', v_count, ' staking orders from the last ', p_days, ' days') AS result;
END //

DELIMITER ;

-- ========== OPTION 3: MARK STAKING ORDERS AS COMPLETED (WITHOUT DELETION) ==========
-- Use this to mark failed/pending orders as completed without deleting history

DELIMITER //

DROP PROCEDURE IF EXISTS mark_staking_complete //
CREATE PROCEDURE mark_staking_complete(IN p_order_id BIGINT)
BEGIN
  DECLARE v_order_exists INT;

  SELECT COUNT(*) INTO v_order_exists FROM staking_swap_orders WHERE id = p_order_id;
  IF v_order_exists = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Staking order does not exist';
  END IF;

  UPDATE staking_swap_orders
    SET status = 'completed',
        cron_status = 'completed',
        updated_at = NOW()
    WHERE id = p_order_id;

  SELECT CONCAT('Marked staking order ', p_order_id, ' as completed') AS result;
END //

DELIMITER ;

-- ========== VIEW: RECENT STAKING ACTIVITY BY USER ==========
DROP VIEW IF EXISTS v_user_staking_activity;
CREATE VIEW v_user_staking_activity AS
SELECT
  o.id,
  o.user_id,
  u.username,
  u.email,
  o.created_at,
  o.status,
  o.cron_status,
  o.usdt_amount,
  o.bman_amount,
  o.bonus_bman,
  o.exchange_rate,
  o.error
FROM staking_swap_orders o
JOIN users u ON u.id = o.user_id
ORDER BY o.created_at DESC;

-- ========== VIEW: SUMMARY INFO ==========
-- Show recent staking records
SELECT
  o.id,
  o.ref,
  u.username,
  o.created_at,
  o.status,
  o.usdt_amount,
  o.bman_amount,
  o.error
FROM staking_swap_orders o
JOIN users u ON u.id = o.user_id
ORDER BY o.created_at DESC
LIMIT 50;
