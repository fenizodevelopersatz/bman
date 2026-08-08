-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 08, 2026 at 08:29 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `e-commerce-mlm-v2`
--

DELIMITER $$
--
-- Procedures
--
CREATE PROCEDURE `mark_staking_complete` (IN `p_order_id` BIGINT)   BEGIN
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
END$$

CREATE PROCEDURE `reset_recent_staking` (IN `p_days` INT)   BEGIN
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
END$$

CREATE PROCEDURE `reset_user_staking` (IN `p_user_id` INT)   BEGIN
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
END$$

CREATE PROCEDURE `_add_bman_exchange_tx_hash` ()   BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'bman_exchange_tx_hash';
  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `bman_exchange_tx_hash` VARCHAR(120) NULL DEFAULT NULL
      COMMENT 'BMAN transfer to exchange wallet'
      AFTER `bman_tx_hash`;
    SELECT 'Added bman_exchange_tx_hash' as status;
  ELSE
    SELECT 'bman_exchange_tx_hash already exists' as status;
  END IF;
END$$

CREATE PROCEDURE `_rsm_add_credit_days_snapshot` ()   BEGIN
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roi_staking_management'
        AND COLUMN_NAME = 'credit_days_snapshot') = 0 THEN
    ALTER TABLE `roi_staking_management`
      ADD COLUMN `credit_days_snapshot` VARCHAR(40) NULL DEFAULT NULL AFTER `credit_mode`;

    UPDATE `roi_staking_management` rsm
      JOIN `staking_plans` sp ON sp.code = rsm.plan_type
      SET rsm.credit_days_snapshot = sp.credit_days
      WHERE rsm.credit_mode = 'per_day';
  END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admin_badge_seen`
--

CREATE TABLE `admin_badge_seen` (
  `admin_id` int(11) NOT NULL,
  `category` varchar(40) NOT NULL,
  `last_seen_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_badge_seen`
--

INSERT INTO `admin_badge_seen` (`admin_id`, `category`, `last_seen_at`) VALUES
(1, 'kyc', '2026-08-06 17:41:59'),
(1, 'support', '2026-07-22 19:17:45'),
(1, 'withdrawals', '2026-08-07 20:01:33');

-- --------------------------------------------------------

--
-- Table structure for table `admin_ceiling_wallet`
--

CREATE TABLE `admin_ceiling_wallet` (
  `id` int(11) NOT NULL,
  `balance` decimal(20,4) DEFAULT 0.0000 COMMENT 'Total overflow held from all users',
  `total_received` decimal(20,4) DEFAULT 0.0000 COMMENT 'Lifetime total received',
  `total_distributed` decimal(20,4) DEFAULT 0.0000 COMMENT 'Lifetime distributed back',
  `last_checked` datetime DEFAULT NULL COMMENT 'Last time admin reviewed',
  `gas_balance` decimal(20,4) DEFAULT 0.0000 COMMENT 'Hot wallet gas fee balance',
  `gas_threshold_warning` decimal(20,4) DEFAULT 0.1000 COMMENT 'Alert when gas drops below this',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_members`
--

CREATE TABLE `admin_members` (
  `id` int(11) NOT NULL,
  `admin_name` varchar(150) DEFAULT NULL,
  `admin_email` varchar(150) DEFAULT NULL,
  `admin_roll` int(11) DEFAULT 2,
  `created_date` datetime DEFAULT NULL,
  `update_date` datetime DEFAULT NULL,
  `permission_pages` text DEFAULT NULL,
  `admin_status` int(11) DEFAULT 1,
  `auth_status` int(11) DEFAULT 0,
  `auth_key` varchar(150) DEFAULT NULL,
  `admin_password` varchar(250) DEFAULT NULL,
  `get_status` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_members`
--

INSERT INTO `admin_members` (`id`, `admin_name`, `admin_email`, `admin_roll`, `created_date`, `update_date`, `permission_pages`, `admin_status`, `auth_status`, `auth_key`, `admin_password`, `get_status`) VALUES
(1, 'BMANADMIN', 'admin@gmail.com', 1, '2025-02-19 18:19:05', '2026-07-30 10:40:31', '{\"site_settings\": true, \"payment_settings\": true,\"mail_settings\": true,\"advance_settings\":true,\"email_markettings\":true,\"newsletter_markettings\":true,\"social_link\":true,\"website_content_cms\":true,\"annoucement_cms\":true,\"slider_cms\":true,\"faq_cms\":true,\"wallet_management\":true,\"package_settings\":true,\"support_management\":true,\"member_management\":true,\"commission_settings\":true,\"rank_management\":true,\"transfer_settings\":true}', 1, 1, 'S6NLQOQ5N3Y2XWRV', '$2y$10$AFfb.lC5SfRpj2L3UfzoSeLtywPl3w76u1DR94/QbRoKMKs4pWLdW', 0);

-- --------------------------------------------------------

--
-- Table structure for table `admin_settings_audit`
--

CREATE TABLE `admin_settings_audit` (
  `id` int(10) UNSIGNED NOT NULL,
  `module` varchar(60) NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_settings_audit`
--

INSERT INTO `admin_settings_audit` (`id`, `module`, `field_name`, `old_value`, `new_value`, `changed_by`, `created_at`) VALUES
(1, 'staking_plans', 'Regular Plan — is_active', '1', '0', 1, '2026-07-21 17:15:05'),
(2, 'staking_plans', 'Fixed Plan — 3y duration', '1', '0', 1, '2026-07-22 11:04:23'),
(3, 'staking_plans', 'Fixed Plan — 5y duration', '1', '0', 1, '2026-07-22 11:04:23'),
(4, 'staking_plans', 'Regular Plan — 3y duration', '1', '0', 1, '2026-07-22 11:04:29'),
(5, 'staking_plans', 'Combo Plan — combo_fixed_pct', '50.00', '50', 1, '2026-07-22 11:04:31'),
(6, 'staking_plans', 'Combo Plan — combo_regular_pct', '50.00', '50', 1, '2026-07-22 11:04:31'),
(7, 'staking_plans', 'Combo Plan — 2y duration', '1', '0', 1, '2026-07-22 11:04:31'),
(8, 'staking_plans', 'Fixed Plan — 5y duration', '0', '1', 1, '2026-07-22 11:11:39'),
(9, 'staking_plans', 'Regular Plan — is_active', '0', '1', 1, '2026-07-23 16:15:24'),
(10, 'staking_plans', 'Fixed Plan — 3y duration', '0', '1', 1, '2026-07-23 16:15:52'),
(11, 'staking_plans', 'Regular Plan — credit_days', '5,15,25', '23,24,25', 1, '2026-07-23 16:15:55'),
(12, 'staking_plans', 'Regular Plan — 3y duration', '0', '1', 1, '2026-07-23 16:15:55'),
(13, 'staking_plans', 'Regular Plan — credit_days', '23,24,25', '5,15,25', 1, '2026-07-30 12:18:32'),
(14, 'staking_plans', 'Combo Plan — is_active', '0', '1', 1, '2026-08-05 10:43:21'),
(15, 'staking_plans', 'Combo Plan — combo_fixed_pct', '50.00', '50', 1, '2026-08-05 10:43:25'),
(16, 'staking_plans', 'Combo Plan — combo_regular_pct', '50.00', '50', 1, '2026-08-05 10:43:25'),
(19, 'staking_plans', 'Combo Plan — combo_fixed_pct', '50.00', '50', 1, '2026-08-05 13:31:37'),
(20, 'staking_plans', 'Combo Plan — combo_regular_pct', '50.00', '50', 1, '2026-08-05 13:31:37'),
(21, 'staking_plans', 'Combo Plan — 2y duration', '0', '1', 1, '2026-08-05 13:31:37'),
(29, 'staking_plans', 'Regular Plan — credit_days', '5,15,25', '7,8,9', 1, '2026-08-07 16:30:13'),
(30, 'staking_plans', 'Combo Plan — credit_days', '5,15,25', '7,8,9', 1, '2026-08-07 16:30:15'),
(31, 'staking_plans', 'Combo Plan — combo_fixed_pct', '50.00', '50', 1, '2026-08-07 16:30:15'),
(32, 'staking_plans', 'Combo Plan — combo_regular_pct', '50.00', '50', 1, '2026-08-07 16:30:15'),
(33, 'staking_plans', 'Regular Plan — credit_days', '7,8,9', '17,18,19', 1, '2026-08-07 18:06:34'),
(34, 'staking_plans', 'Regular Plan — credit_days', '17,18,19', '7,8,9', 1, '2026-08-07 18:06:56');

-- --------------------------------------------------------

--
-- Table structure for table `admin_wallet`
--

CREATE TABLE `admin_wallet` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `balance` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `lifetime_bonus_reduction_total` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_wallets`
--

CREATE TABLE `admin_wallets` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `usd_balance` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `exchange_balance` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `earning_balance` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `staking_balance` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `bonus_balance` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `lifetime_bonus_reduction` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `lifetime_withdraw_fee` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_wallet_ledger`
--

CREATE TABLE `admin_wallet_ledger` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `credit` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `debit` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `balance_after` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `reference_type` varchar(40) NOT NULL,
  `reference_user_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcement`
--

CREATE TABLE `announcement` (
  `id` int(11) NOT NULL,
  `announcement_title` varchar(255) DEFAULT NULL,
  `priority_level` tinyint(4) NOT NULL DEFAULT 2,
  `rotation_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `title` varchar(250) DEFAULT NULL,
  `subtitle` varchar(250) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `announcement_type` enum('text','image','text_image') NOT NULL DEFAULT 'text',
  `category` enum('general','alert','promotion','maintenance','event','rank_news') NOT NULL DEFAULT 'general',
  `bg_color` varchar(120) DEFAULT NULL,
  `text_color` varchar(20) NOT NULL DEFAULT '#ffffff',
  `text_position` enum('middle-left','top-left','bottom-left','center') NOT NULL DEFAULT 'middle-left',
  `image` varchar(255) DEFAULT NULL,
  `button_text` varchar(100) DEFAULT NULL,
  `button_url` varchar(255) DEFAULT NULL,
  `priority` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `display_mode` enum('banner','popup','both') NOT NULL DEFAULT 'banner',
  `target_type` enum('all','active','inactive','kyc_pending','kyc_approved','rank','package','country') NOT NULL DEFAULT 'all',
  `target_value` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `timezone` varchar(64) DEFAULT 'Asia/Kolkata',
  `status` enum('draft','scheduled','active','expired','paused') NOT NULL DEFAULT 'draft',
  `end_date` date DEFAULT NULL,
  `views` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `clicks` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `title_status` int(11) DEFAULT 1,
  `created_date` datetime DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement`
--

INSERT INTO `announcement` (`id`, `announcement_title`, `priority_level`, `rotation_enabled`, `title`, `subtitle`, `description`, `announcement_type`, `category`, `bg_color`, `text_color`, `text_position`, `image`, `button_text`, `button_url`, `priority`, `display_mode`, `target_type`, `target_value`, `start_date`, `start_time`, `end_time`, `timezone`, `status`, `end_date`, `views`, `clicks`, `title_status`, `created_date`, `created_by`, `updated_by`) VALUES
(1, NULL, 2, 1, 'Balu Sir', NULL, NULL, 'text', 'general', NULL, '#ffffff', 'middle-left', NULL, NULL, NULL, 'medium', 'banner', 'all', NULL, NULL, NULL, NULL, 'Asia/Kolkata', 'draft', NULL, 174, 1, 1, '2026-07-20 19:18:47', NULL, NULL),
(2, NULL, 2, 1, '', 'BMAN Subtitle', 'BMAN Description', 'image', 'general', NULL, '#ffffff', 'middle-left', 'assets/images/announcement/ann_1784726603_1590.jpg', NULL, NULL, 'high', 'banner', 'all', NULL, NULL, NULL, NULL, 'Asia/Kolkata', 'draft', NULL, 71, 0, 0, '2026-07-21 15:13:39', NULL, NULL),
(6, NULL, 2, 1, 'Announcement Text *', 'Subtitle', 'Descriptopnm Alert / Maintenance always render with the red emergency style, regardless of background color chosen below. Alert / Maintenance always render with the red emergency style, regardless of background color chosen below.', 'text_image', 'general', 'linear-gradient(135deg,#6C4CF1,#4E2CF0)', '#ffffff', 'middle-left', 'assets/images/announcement/ann_1784787139_4682.jpg', NULL, NULL, 'high', 'banner', 'all', NULL, '2026-07-22', NULL, NULL, 'Asia/Kolkata', 'draft', NULL, 55, 0, 0, '2026-07-23 08:12:19', NULL, NULL),
(7, NULL, 2, 1, 'Announcement Text *', 'Promotion Banner', 'Description', 'text_image', 'promotion', '#6E56CF', '#ffffff', 'center', 'assets/images/announcement/ann_1784873600_4435.jpg', 'Withdraw Now', 'user/withdraw', 'critical', 'banner', 'kyc_approved', NULL, NULL, NULL, NULL, 'Asia/Kolkata', 'draft', NULL, 38, 0, 1, '2026-07-24 08:13:20', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `announcement_audit_log`
--

CREATE TABLE `announcement_audit_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `announcement_id` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(32) NOT NULL,
  `changed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `changed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcement_dismissals`
--

CREATE TABLE `announcement_dismissals` (
  `announcement_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `dismissed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement_dismissals`
--

INSERT INTO `announcement_dismissals` (`announcement_id`, `user_id`, `dismissed_at`) VALUES
(1, 1, '2026-07-22 17:14:25');

-- --------------------------------------------------------

--
-- Table structure for table `announcement_stats`
--

CREATE TABLE `announcement_stats` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `announcement_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `viewed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `clicked_at` datetime DEFAULT NULL,
  `device_type` varchar(32) DEFAULT NULL,
  `source` varchar(32) DEFAULT 'dashboard'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `binary_carry`
--

CREATE TABLE `binary_carry` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `left_carry` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `right_carry` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `last_flush_at` date DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `binary_carry`
--

INSERT INTO `binary_carry` (`user_id`, `left_carry`, `right_carry`, `last_flush_at`, `updated_at`) VALUES
(1, 9.0000, 0.0000, NULL, '2026-08-07 07:43:32'),
(2, 9.0000, 0.0000, NULL, '2026-08-07 07:43:32'),
(22, 0.0000, 9.0000, NULL, '2026-08-07 07:43:32'),
(999999504, 9.0000, 0.0000, NULL, '2026-08-07 07:43:32'),
(999999604, 9.0000, 0.0000, NULL, '2026-08-07 07:43:32'),
(999999605, 9.0000, 0.0000, NULL, '2026-08-07 07:43:32'),
(999999608, 5.0000, 0.0000, '2026-08-07', '2026-08-07 07:43:32');

-- --------------------------------------------------------

--
-- Table structure for table `binary_carry_forward`
--

CREATE TABLE `binary_carry_forward` (
  `user_id` int(11) NOT NULL,
  `left_carry` decimal(18,6) NOT NULL DEFAULT 0.000000,
  `right_carry` decimal(18,6) NOT NULL DEFAULT 0.000000,
  `scope_key` varchar(20) DEFAULT 'lifetime',
  `updated_at` datetime DEFAULT NULL,
  `last_run_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `binary_matching_bonus_ledger`
--

CREATE TABLE `binary_matching_bonus_ledger` (
  `id` bigint(20) NOT NULL,
  `purchase_id` int(11) DEFAULT NULL COMMENT 'Staking purchase that triggered this bonus',
  `bonus_recipient_id` int(11) NOT NULL COMMENT 'Who receives the bonus (sponsor)',
  `bonus_payer_id` int(11) NOT NULL COMMENT 'Whose purchase is the source',
  `level` int(11) NOT NULL COMMENT 'How many levels up (1=immediate parent)',
  `left_leg_volume` decimal(20,4) DEFAULT NULL COMMENT 'Left side volume at this level',
  `right_leg_volume` decimal(20,4) DEFAULT NULL COMMENT 'Right side volume at this level',
  `qualifying_volume` decimal(20,4) DEFAULT NULL COMMENT 'Min(left, right)',
  `bonus_amount_total` decimal(20,4) DEFAULT NULL COMMENT 'Total 10% bonus',
  `bonus_earning` decimal(20,4) DEFAULT NULL COMMENT '8% to earning wallet',
  `bonus_staking` decimal(20,4) DEFAULT NULL COMMENT '2% to staking wallet',
  `status` enum('CALCULATED','HELD_CEILING','DISTRIBUTED','FAILED') DEFAULT 'CALCULATED',
  `ceiling_amount_held` decimal(20,4) DEFAULT 0.0000 COMMENT 'If any held due to ceiling',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `distributed_at` datetime DEFAULT NULL COMMENT 'When actually credited to wallets'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `binary_matching_queue`
--

CREATE TABLE `binary_matching_queue` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `run_ref` varchar(40) NOT NULL,
  `status` enum('PENDING','PROCESSING','DONE','FAILED') NOT NULL DEFAULT 'PENDING',
  `scope` varchar(20) NOT NULL DEFAULT 'full' COMMENT 'full | propagate | pay',
  `attempts` int(11) NOT NULL DEFAULT 0,
  `max_attempts` int(11) NOT NULL DEFAULT 3,
  `result_json` text DEFAULT NULL COMMENT 'engine summary (paid_users, matched_volume, ...)',
  `last_error` varchar(255) DEFAULT NULL,
  `enqueued_by` int(11) DEFAULT NULL COMMENT 'admin id if manually triggered, else NULL (cron)',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Binary matching run queue (scanner enqueues, processor runs the engine)';

--
-- Dumping data for table `binary_matching_queue`
--

INSERT INTO `binary_matching_queue` (`id`, `run_ref`, `status`, `scope`, `attempts`, `max_attempts`, `result_json`, `last_error`, `enqueued_by`, `created_at`, `started_at`, `finished_at`) VALUES
(1, 'MB-20260806-100752-FC7708', 'DONE', 'full', 1, 3, '{\"run_ref\":\"MB-20260806-100752-FC7708\",\"propagated\":2,\"paid_users\":0,\"matched_volume\":0,\"earning_paid\":0,\"staking_paid\":0,\"ceiling_held\":0}', NULL, NULL, '2026-08-06 13:37:52', '2026-08-06 10:07:52', '2026-08-06 10:07:52'),
(2, 'MB-20260806-120943-0F74AA', 'DONE', 'full', 1, 3, '{\"run_ref\":\"MB-20260806-120943-0F74AA\",\"propagated\":0,\"paid_users\":0,\"matched_volume\":0,\"earning_paid\":0,\"staking_paid\":0,\"ceiling_held\":0}', NULL, NULL, '2026-08-06 15:39:43', '2026-08-06 12:09:43', '2026-08-06 12:09:43'),
(3, 'MB-20260806-190902-702C0C', 'DONE', 'full', 1, 3, '{\"run_ref\":\"MB-20260806-190902-702C0C\",\"propagated\":0,\"paid_users\":0,\"matched_volume\":0,\"earning_paid\":0,\"staking_paid\":0,\"ceiling_held\":0}', NULL, NULL, '2026-08-06 19:09:02', '2026-08-06 19:09:02', '2026-08-06 19:09:02'),
(4, 'MB-20260807-094332-C8E156', 'DONE', 'full', 1, 3, '{\"run_ref\":\"MB-20260807-094332-C8E156\",\"propagated\":9,\"paid_users\":1,\"matched_volume\":2,\"earning_paid\":0.16,\"staking_paid\":0.04,\"ceiling_held\":0}', NULL, NULL, '2026-08-07 13:13:32', '2026-08-07 09:43:32', '2026-08-07 09:43:32'),
(5, 'MB-20260807-094452-744A27', 'DONE', 'full', 1, 3, '{\"run_ref\":\"MB-20260807-094452-744A27\",\"propagated\":0,\"paid_users\":0,\"matched_volume\":0,\"earning_paid\":0,\"staking_paid\":0,\"ceiling_held\":0}', NULL, NULL, '2026-08-07 13:14:52', '2026-08-07 09:44:52', '2026-08-07 09:44:52');

-- --------------------------------------------------------

--
-- Table structure for table `binary_placement`
--

CREATE TABLE `binary_placement` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sponsor_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `position` enum('left','right') NOT NULL,
  `placement_type` enum('direct','auto') NOT NULL,
  `auto_from_user` int(11) DEFAULT NULL,
  `placed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `direct_placement` int(11) DEFAULT NULL,
  `type` enum('direct','auto') NOT NULL DEFAULT 'direct'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `binary_placement`
--

INSERT INTO `binary_placement` (`id`, `user_id`, `sponsor_id`, `parent_id`, `position`, `placement_type`, `auto_from_user`, `placed_at`, `direct_placement`, `type`) VALUES
(1, 2, 1, 1, 'left', 'direct', NULL, '2026-07-20 03:54:41', 1, 'direct'),
(2, 3, 1, 1, 'right', 'direct', NULL, '2026-07-20 13:03:50', 1, 'direct'),
(3, 4, 3, 3, 'left', 'direct', NULL, '2026-07-20 13:28:40', 1, 'direct'),
(21, 22, 1, 2, 'left', 'auto', NULL, '2026-07-30 08:10:26', 0, 'direct'),
(22, 23, 1, 3, 'right', 'auto', NULL, '2026-07-30 08:10:28', 0, 'direct'),
(24, 999999504, 22, 22, 'right', 'direct', NULL, '2026-08-06 02:41:54', 1, 'direct'),
(25, 999999505, 22, 22, 'left', 'direct', NULL, '2026-08-06 02:52:47', 1, 'direct'),
(26, 999999602, 999999504, 999999504, 'right', 'direct', NULL, '2026-08-06 06:41:25', 1, 'direct'),
(27, 999999603, 999999602, 999999602, 'left', 'direct', NULL, '2026-08-06 06:54:59', 1, 'direct'),
(28, 999999604, 999999504, 999999504, 'left', 'direct', NULL, '2026-08-06 07:49:41', 1, 'direct'),
(29, 999999605, 999999504, 999999604, 'left', 'auto', NULL, '2026-08-06 07:53:13', 0, 'direct'),
(30, 999999606, 999999504, 999999602, 'right', 'auto', NULL, '2026-08-06 07:54:36', 0, 'direct'),
(31, 999999607, 999999602, 999999606, 'right', 'auto', NULL, '2026-08-06 07:56:39', 0, 'direct'),
(32, 999999608, 999999504, 999999605, 'left', 'auto', NULL, '2026-08-06 07:58:01', 0, 'direct'),
(35, 999999611, 999999608, 999999608, 'left', 'direct', NULL, '2026-08-06 16:57:24', 1, 'direct'),
(36, 999999612, 999999608, 999999608, 'right', 'direct', NULL, '2026-08-06 17:00:22', 1, 'direct'),
(37, 999999613, 999999606, 999999606, 'left', 'direct', NULL, '2026-08-07 09:08:36', 1, 'direct'),
(38, 999999614, 999999605, 999999611, 'left', 'auto', NULL, '2026-08-07 14:37:58', 0, 'direct'),
(39, 999999615, 999999614, 999999614, 'right', 'direct', NULL, '2026-08-07 14:42:47', 1, 'direct'),
(40, 999999616, 999999615, 999999615, 'left', 'direct', NULL, '2026-08-07 14:46:08', 1, 'direct');

-- --------------------------------------------------------

--
-- Table structure for table `binary_volume_ledger`
--

CREATE TABLE `binary_volume_ledger` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `invest_id` bigint(20) UNSIGNED NOT NULL,
  `pv` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `bv` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `source_amount` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `processed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `binary_volume_ledger`
--

INSERT INTO `binary_volume_ledger` (`id`, `user_id`, `invest_id`, `pv`, `bv`, `source_amount`, `processed`, `created_at`) VALUES
(9, 999999501, 39, 0.0000, 1.0000, 1.0000, 1, '2026-08-05 17:05:59'),
(10, 999999502, 40, 0.0000, 1.0000, 1.0000, 1, '2026-08-05 17:05:59'),
(13, 999999612, 53, 0.0000, 1.0000, 1.0000, 1, '2026-08-07 07:35:03'),
(14, 999999612, 54, 0.0000, 1.0000, 1.0000, 1, '2026-08-07 09:21:02'),
(15, 999999611, 55, 0.0000, 1.0000, 1.0000, 1, '2026-08-07 09:37:45'),
(16, 999999611, 56, 0.0000, 1.0000, 1.0000, 1, '2026-08-07 09:38:11'),
(17, 999999611, 57, 0.0000, 1.0000, 1.0000, 1, '2026-08-07 09:38:38'),
(18, 999999611, 58, 0.0000, 1.0000, 1.0000, 1, '2026-08-07 09:39:00'),
(19, 999999611, 59, 0.0000, 1.0000, 1.0000, 1, '2026-08-07 09:39:25'),
(20, 999999611, 60, 0.0000, 1.0000, 1.0000, 1, '2026-08-07 09:39:46'),
(21, 999999611, 61, 0.0000, 1.0000, 1.0000, 1, '2026-08-07 09:40:19'),
(22, 999999612, 62, 0.0000, 2.0000, 2.0000, 0, '2026-08-07 09:59:34'),
(23, 999999608, 64, 0.0000, 1.0000, 1.0000, 0, '2026-08-07 10:24:07'),
(24, 999999606, 65, 0.0000, 2.0000, 2.0000, 0, '2026-08-07 10:28:08'),
(25, 999999602, 66, 0.0000, 1.0000, 1.0000, 0, '2026-08-07 13:53:26'),
(26, 999999602, 67, 0.0000, 1.0000, 1.0000, 0, '2026-08-07 14:15:57'),
(27, 999999607, 68, 0.0000, 1.0000, 1.0000, 0, '2026-08-07 14:23:51'),
(28, 999999602, 70, 0.0000, 2.0000, 2.0000, 0, '2026-08-07 15:00:05'),
(29, 999999603, 71, 0.0000, 1.0000, 1.0000, 0, '2026-08-07 19:52:53');

-- --------------------------------------------------------

--
-- Table structure for table `blockchain_payout_queue`
--

CREATE TABLE `blockchain_payout_queue` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payout_ref` varchar(48) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `token` varchar(16) NOT NULL DEFAULT 'BMAN' COMMENT 'BMAN | USDT',
  `amount` decimal(30,8) NOT NULL,
  `to_address` varchar(64) NOT NULL,
  `from_address` varchar(64) DEFAULT NULL COMMENT 'hot/treasury wallet (resolved at send)',
  `purpose` varchar(40) NOT NULL DEFAULT 'payout' COMMENT 'withdrawal | ceiling_release | ...',
  `reference_type` varchar(40) DEFAULT NULL,
  `reference_id` varchar(64) DEFAULT NULL,
  `status` enum('PENDING','PROCESSING','CONFIRMED','FAILED','RETRY') NOT NULL DEFAULT 'PENDING',
  `tx_hash` varchar(80) DEFAULT NULL,
  `block_number` bigint(20) UNSIGNED DEFAULT NULL,
  `confirmations` int(11) NOT NULL DEFAULT 0,
  `required_confs` int(11) NOT NULL DEFAULT 3,
  `retry_count` int(11) NOT NULL DEFAULT 0,
  `max_retries` int(11) NOT NULL DEFAULT 5,
  `last_attempt_at` datetime DEFAULT NULL,
  `last_error` varchar(255) DEFAULT NULL,
  `precheck_json` text DEFAULT NULL COMMENT 'hot balance / gas balance / rpc ok at last attempt',
  `onchain_tx_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'link to onchain_transactions.id',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `confirmed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='On-chain payout queue with balance pre-checks, retry & confirmations';

--
-- Dumping data for table `blockchain_payout_queue`
--

INSERT INTO `blockchain_payout_queue` (`id`, `payout_ref`, `user_id`, `token`, `amount`, `to_address`, `from_address`, `purpose`, `reference_type`, `reference_id`, `status`, `tx_hash`, `block_number`, `confirmations`, `required_confs`, `retry_count`, `max_retries`, `last_attempt_at`, `last_error`, `precheck_json`, `onchain_tx_id`, `created_by`, `created_at`, `updated_at`, `confirmed_at`) VALUES
(1, 'MBP-MB-20260807-094332-C8E156-U999999608', 999999608, 'BMAN', 0.20000000, '0x77779986DF95EBEaE48F4c6a94Be2886eA7a943C', '0x3088B858dc4cD85A001337f8E15a40b24666d321', 'binary_matching', 'staking_matching_payout', '5', 'CONFIRMED', '0xe59835dce68d5f20671e8e3fb152678fe6e28b48d0c561dc65c5894525d0e104', 114507007, 167, 15, 0, 5, '2026-08-07 09:43:34', NULL, '{\"checked_at\":\"2026-08-07 09:43:34\",\"treasury_address\":\"0x3088B858dc4cD85A001337f8E15a40b24666d321\",\"treasury_bnb_balance\":0.10404035,\"treasury_bman_balance\":499916.728125,\"gas_needed_bnb\":0.0015750000000000002,\"amount_needed_bman\":0.2,\"rpc_ok\":true,\"result\":\"ok\"}', 319, NULL, '2026-08-07 13:13:32', '2026-08-07 13:14:54', '2026-08-07 09:44:54');

-- --------------------------------------------------------

--
-- Table structure for table `blocked_ips`
--

CREATE TABLE `blocked_ips` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blogs`
--

CREATE TABLE `blogs` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `category` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blog_categories`
--

CREATE TABLE `blog_categories` (
  `name` varchar(150) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bman_wallet_ledger`
--

CREATE TABLE `bman_wallet_ledger` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `wallet` enum('exchange','earning','staking','bonus') NOT NULL,
  `entry_type` enum('credit','debit','lock') NOT NULL,
  `ref_type` varchar(50) DEFAULT NULL COMMENT 'withdrawal, staking, airdrop, etc',
  `ref_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'reference to source table',
  `amount` decimal(18,8) NOT NULL COMMENT 'always positive; sign is in entry_type',
  `maturity_date` datetime DEFAULT NULL COMMENT 'NULL means matured immediately',
  `status` enum('active','reversed') NOT NULL DEFAULT 'active',
  `remark` text DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Source of truth for balances (append-only)';

--
-- Dumping data for table `bman_wallet_ledger`
--

INSERT INTO `bman_wallet_ledger` (`id`, `user_id`, `wallet`, `entry_type`, `ref_type`, `ref_id`, `amount`, `maturity_date`, `status`, `remark`, `created_at`) VALUES
(1, 3, 'exchange', 'lock', 'withdrawal', 1, 3.95000000, NULL, 'active', 'Withdrawal request #BWM-20260723160118-3050', '2026-07-23 16:01:18'),
(2, 999999612, 'exchange', 'lock', 'withdrawal', 2, 3.00000000, NULL, 'active', 'Withdrawal request #BWM-20260806172623-4631', '2026-08-06 17:26:23');

-- --------------------------------------------------------

--
-- Table structure for table `bman_withdraw_allocations`
--

CREATE TABLE `bman_withdraw_allocations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `request_id` bigint(20) UNSIGNED NOT NULL,
  `wallet` enum('exchange','earning','staking','bonus') NOT NULL,
  `amount` decimal(18,8) NOT NULL COMMENT 'amount taken from this wallet',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks which wallet contributed to mixed requests';

--
-- Dumping data for table `bman_withdraw_allocations`
--

INSERT INTO `bman_withdraw_allocations` (`id`, `request_id`, `wallet`, `amount`, `created_at`) VALUES
(1, 1, 'exchange', 3.95000000, '2026-07-23 16:01:18'),
(2, 2, 'exchange', 3.00000000, '2026-08-06 17:26:23');

-- --------------------------------------------------------

--
-- Table structure for table `bman_withdraw_requests`
--

CREATE TABLE `bman_withdraw_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `request_no` varchar(50) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `source_wallet` enum('exchange','earning','staking','bonus') NOT NULL,
  `request_amount` decimal(18,8) NOT NULL DEFAULT 0.00000000,
  `fee_amount` decimal(18,8) NOT NULL DEFAULT 0.00000000,
  `net_amount` decimal(18,8) NOT NULL DEFAULT 0.00000000,
  `bman_usdt_rate` decimal(18,8) NOT NULL DEFAULT 0.00000000,
  `usdt_amount` decimal(18,8) NOT NULL DEFAULT 0.00000000,
  `withdraw_address` varchar(255) NOT NULL,
  `remark` text DEFAULT NULL,
  `tx_hash` varchar(255) DEFAULT NULL,
  `admin_remark` text DEFAULT NULL,
  `status` enum('pending','approved','processing','completed','rejected','failed') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bman_withdraw_requests`
--

INSERT INTO `bman_withdraw_requests` (`id`, `request_no`, `user_id`, `source_wallet`, `request_amount`, `fee_amount`, `net_amount`, `bman_usdt_rate`, `usdt_amount`, `withdraw_address`, `remark`, `tx_hash`, `admin_remark`, `status`, `approved_by`, `approved_at`, `completed_at`, `created_at`) VALUES
(1, 'BWM-20260723160118-3050', 3, 'exchange', 3.95000000, 0.10000000, 0.29500000, 0.10000000, 0.29500000, '0x8a49ef2d426fd67a2c3eac2ce85e12e08250d436', '0x8a49ef2d426fd67a2c3eac2ce85e12e08250d436 My metamask by trustwallet', NULL, NULL, 'pending', NULL, NULL, NULL, '2026-07-23 16:01:18'),
(2, 'BWM-20260806172623-4631', 999999612, 'exchange', 3.00000000, 0.10000000, 0.20000000, 0.10000000, 0.20000000, '0x3088B858dc4cD85A001337f8E15a40b24666d321', '', NULL, '', 'processing', 1, '2026-08-06 17:28:30', NULL, '2026-08-06 17:26:23');

-- --------------------------------------------------------

--
-- Table structure for table `bonus_reduction_log`
--

CREATE TABLE `bonus_reduction_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `cycle_no` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `anchor_date` datetime DEFAULT NULL,
  `bonus_before` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `reduce_percent` decimal(6,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `from_address` varchar(120) DEFAULT NULL,
  `to_address` varchar(120) DEFAULT NULL,
  `wallet_ledger_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tx_hash` varchar(120) DEFAULT NULL,
  `status` enum('internal','sent','failed') NOT NULL DEFAULT 'internal',
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `brand_img` varchar(250) DEFAULT NULL,
  `description` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ceiling_wallet`
--

CREATE TABLE `ceiling_wallet` (
  `user_id` int(11) NOT NULL,
  `held_balance` decimal(20,4) NOT NULL DEFAULT 0.0000 COMMENT 'currently held (not yet released)',
  `total_held` decimal(20,4) NOT NULL DEFAULT 0.0000 COMMENT 'lifetime moved into ceiling',
  `total_released` decimal(20,4) NOT NULL DEFAULT 0.0000 COMMENT 'lifetime released back out',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='System Ceiling Wallet ÔÇö per-user held balance (backend only)';

-- --------------------------------------------------------

--
-- Table structure for table `ceiling_wallet_config`
--

CREATE TABLE `ceiling_wallet_config` (
  `id` int(11) NOT NULL,
  `package_name` varchar(50) NOT NULL,
  `threshold_amount` decimal(20,4) NOT NULL COMMENT 'When ceiling balance >= this, cap is enforced',
  `ceiling_cap` decimal(20,4) NOT NULL COMMENT 'Max allowed in ceiling wallet for this package',
  `hold_percentage` decimal(5,2) DEFAULT 100.00 COMMENT 'What % of overflow to hold in ceiling',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ceiling_wallet_config`
--

INSERT INTO `ceiling_wallet_config` (`id`, `package_name`, `threshold_amount`, `ceiling_cap`, `hold_percentage`, `created_at`, `updated_at`) VALUES
(1, 'BASIC', 5000.0000, 5000.0000, 100.00, '2026-07-13 11:30:56', '2026-07-13 11:30:56'),
(2, 'SILVER', 10000.0000, 10000.0000, 100.00, '2026-07-13 11:30:56', '2026-07-13 11:30:56'),
(3, 'GOLD', 20000.0000, 20000.0000, 100.00, '2026-07-13 11:30:56', '2026-07-13 11:30:56'),
(4, 'PLATINUM', 25000.0000, 25000.0000, 100.00, '2026-07-13 11:30:56', '2026-07-13 11:30:56'),
(5, 'DIAMOND_50K', 50000.0000, 30000.0000, 100.00, '2026-07-13 11:30:56', '2026-07-13 11:30:56'),
(6, 'DIAMOND_100K', 100000.0000, 30000.0000, 100.00, '2026-07-13 11:30:56', '2026-07-13 11:30:56'),
(7, 'DIAMOND_200K', 200000.0000, 50000.0000, 100.00, '2026-07-13 11:30:56', '2026-07-13 11:30:56'),
(8, 'DIAMOND_300K', 300000.0000, 70000.0000, 100.00, '2026-07-13 11:30:56', '2026-07-13 11:30:56'),
(9, 'DIAMOND_500K', 500000.0000, 100000.0000, 100.00, '2026-07-13 11:30:56', '2026-07-13 11:30:56');

-- --------------------------------------------------------

--
-- Table structure for table `ceiling_wallet_ledger`
--

CREATE TABLE `ceiling_wallet_ledger` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `tx_type` enum('CEILING_HOLD','CEILING_RELEASE','CEILING_ADJUSTMENT') NOT NULL,
  `amount` decimal(20,4) NOT NULL,
  `held_after` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `source_wallet` varchar(16) DEFAULT NULL,
  `matched_volume` decimal(20,4) DEFAULT NULL COMMENT 'matching context, if from matching',
  `reference_type` varchar(40) DEFAULT NULL COMMENT 'binary_matching / admin_release / admin_adjust',
  `reference_id` varchar(64) DEFAULT NULL COMMENT 'run_ref / matching payout id / note',
  `description` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL COMMENT 'admin id for release/adjustment',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='System Ceiling Wallet ledger (HOLD/RELEASE/ADJUSTMENT) ÔÇö backend only';

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `room` varchar(30) NOT NULL DEFAULT 'personal',
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `to_user_id` int(11) DEFAULT NULL,
  `peer_id` int(11) DEFAULT NULL,
  `username` varchar(80) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `message_type` enum('text','image','file') NOT NULL DEFAULT 'text',
  `file_url` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `room`, `user_id`, `to_user_id`, `peer_id`, `username`, `message`, `created_at`, `message_type`, `file_url`, `file_name`, `mime_type`, `file_size`) VALUES
(1, 'world', 3, NULL, NULL, 'trustwallet', 'World', '2026-07-20 19:15:08', 'text', NULL, NULL, NULL, NULL),
(2, 'world', 4, NULL, NULL, 'rightbytrustwallet', '1', '2026-07-20 19:15:25', 'text', NULL, NULL, NULL, NULL),
(3, 'world', 3, NULL, NULL, 'trustwallet', '2', '2026-07-20 19:15:33', 'text', NULL, NULL, NULL, NULL),
(4, 'world', 3, NULL, NULL, 'trustwallet', '23', '2026-07-20 19:15:40', 'text', NULL, NULL, NULL, NULL),
(5, 'world', 4, NULL, NULL, 'rightbytrustwallet', '???? Image', '2026-07-20 19:17:03', 'image', 'https://darkseagreen-crocodile-999780.hostingersite.com/uploads/chat/d3afa08451164568d4ba3db5d9d019a6.jpg', 'image_1752591069_68766add54d8a.jpg', 'image/jpeg', 20520),
(6, 'world', 3, NULL, NULL, 'trustwallet', '???? Image', '2026-07-20 19:17:18', 'image', 'https://darkseagreen-crocodile-999780.hostingersite.com/uploads/chat/d2b4602e17966551472aa2a7598c4d16.jpg', 'photograph-cute-adorable-kitten_727939-12095.jpg', 'image/jpeg', 200253),
(7, 'personal', 4, 3, 3, 'rightbytrustwallet', 'Hi trustwallet, I am right by trustwallet', '2026-07-21 09:12:51', 'text', NULL, NULL, NULL, NULL),
(8, 'team', 3, NULL, NULL, 'trustwallet', 'Hi My team I am Trustwallet', '2026-07-21 09:21:58', 'text', NULL, NULL, NULL, NULL),
(9, 'team', 1, NULL, NULL, 'Admin', '???? Image', '2026-07-24 08:06:58', 'image', 'uploads/chat/3f1925c538144257e5e82e3f8fda67ee.jpeg', 'ChatGPTImageJul22202612_05_33PM112.jpeg', 'image/jpeg', 50083),
(10, 'personal', 1, 3, 3, 'Admin', 'Hu', '2026-07-24 08:07:33', 'text', NULL, NULL, NULL, NULL),
(11, 'personal', 1, 3, 3, 'Admin', '2', '2026-07-24 08:07:35', 'text', NULL, NULL, NULL, NULL),
(12, 'personal', 1, 3, 3, 'Admin', '3', '2026-07-24 08:07:37', 'text', NULL, NULL, NULL, NULL),
(13, 'personal', 1, 3, 3, 'Admin', '4', '2026-07-24 08:07:39', 'text', NULL, NULL, NULL, NULL),
(14, 'personal', 1, 3, 3, 'Admin', '???? Image', '2026-07-24 08:07:46', 'image', 'uploads/chat/d35968b4edfc0cc9fb1333fcb94844e9.jpeg', 'ChatGPTImageJul22202612_05_33PM112.jpeg', 'image/jpeg', 50083),
(15, 'world', 1, NULL, NULL, 'Admin', 'mmm', '2026-07-24 10:30:32', 'text', NULL, NULL, NULL, NULL),
(16, 'world', 1, NULL, NULL, 'Admin', 'hhhhh', '2026-07-24 10:30:42', 'text', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `chat_read_state`
--

CREATE TABLE `chat_read_state` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `room` varchar(30) NOT NULL,
  `peer_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_read_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_read_state`
--

INSERT INTO `chat_read_state` (`user_id`, `room`, `peer_id`, `last_read_id`, `updated_at`) VALUES
(1, 'personal', 3, 14, '2026-07-24 11:37:46'),
(1, 'team', 0, 9, '2026-07-24 11:36:58'),
(1, 'world', 0, 16, '2026-07-24 14:00:42'),
(2, 'world', 0, 6, '2026-07-23 18:13:15'),
(3, 'personal', 1, 14, '2026-07-24 11:38:08'),
(3, 'team', 0, 9, '2026-07-24 11:37:59'),
(3, 'world', 0, 16, '2026-07-24 14:00:43'),
(4, 'team', 0, 8, '2026-07-24 11:37:12'),
(4, 'world', 0, 16, '2026-07-27 15:52:45'),
(23, 'world', 0, 16, '2026-08-05 12:08:09'),
(999999612, 'world', 0, 16, '2026-08-06 17:08:00');

-- --------------------------------------------------------

--
-- Table structure for table `coin_distribution_audit`
--

CREATE TABLE `coin_distribution_audit` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `option_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(30) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `changed_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coin_distribution_audit`
--

INSERT INTO `coin_distribution_audit` (`id`, `option_id`, `action`, `old_value`, `new_value`, `changed_by`, `created_at`) VALUES
(1, 24, 'create', NULL, '{\"option_name\": \"Option 2\", \"exchange_percentage\": 100.00, \"earning_percentage\": 0.00, \"staking_percentage\": 0.00, \"bonus_percentage\": 0.00, \"execution_mode\": \"internal\", \"note\": \"Added by db/coin_distribution_option2_internal.sql\"}', 0, '2026-08-07 13:02:39');

-- --------------------------------------------------------

--
-- Table structure for table `coin_distribution_histories`
--

CREATE TABLE `coin_distribution_histories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `purchase_id` bigint(20) UNSIGNED DEFAULT NULL,
  `option_id` int(10) UNSIGNED NOT NULL,
  `exchange_percentage` decimal(6,2) NOT NULL,
  `earning_percentage` decimal(6,2) NOT NULL,
  `staking_percentage` decimal(6,2) NOT NULL,
  `bonus_percentage` decimal(6,2) NOT NULL,
  `exchange_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `earning_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `staking_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `bonus_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `total_amount` decimal(20,4) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coin_distribution_options`
--

CREATE TABLE `coin_distribution_options` (
  `id` int(10) UNSIGNED NOT NULL,
  `option_name` varchar(80) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `exchange_percentage` decimal(6,2) NOT NULL DEFAULT 0.00,
  `earning_percentage` decimal(6,2) NOT NULL DEFAULT 0.00,
  `staking_percentage` decimal(6,2) NOT NULL DEFAULT 0.00,
  `bonus_percentage` decimal(6,2) NOT NULL DEFAULT 0.00,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `execution_mode` enum('onchain','internal') NOT NULL DEFAULT 'internal',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coin_distribution_options`
--

INSERT INTO `coin_distribution_options` (`id`, `option_name`, `description`, `exchange_percentage`, `earning_percentage`, `staking_percentage`, `bonus_percentage`, `sort_order`, `execution_mode`, `is_default`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'Option 1', '100% Exchange Coin Balance', 100.00, 0.00, 0.00, 0.00, 10, 'onchain', 1, 1, NULL, 1, '2026-07-02 11:45:17', '2026-08-07 13:02:39'),
(2, 'Option 3', '90% Exchange + 10% Bonus', 90.00, 0.00, 0.00, 10.00, 30, 'internal', 0, 1, NULL, 1, '2026-07-02 11:45:17', '2026-08-07 13:02:39'),
(3, 'Option 4', '80% Exchange + 10% Earning + 10% Bonus', 80.00, 10.00, 0.00, 10.00, 40, 'internal', 0, 1, NULL, NULL, '2026-07-02 11:45:17', '2026-08-07 13:02:39'),
(4, 'Option 5', '80% Exchange + 10% Earning + 10% Staking', 80.00, 10.00, 10.00, 0.00, 50, 'internal', 0, 1, NULL, 1, '2026-07-02 11:45:17', '2026-08-07 13:02:39'),
(5, 'Option 6', '90% Exchange + 10% Earning', 90.00, 10.00, 0.00, 0.00, 60, 'internal', 0, 1, NULL, NULL, '2026-07-02 11:45:17', '2026-08-07 13:02:39'),
(6, 'Option 7', '90% Exchange + 10% Staking', 90.00, 0.00, 10.00, 0.00, 70, 'internal', 0, 1, NULL, NULL, '2026-07-02 11:45:17', '2026-08-07 13:02:39'),
(7, 'Option 8', '70% Exchange + 10% Earning + 10% Staking + 10% Bonus', 70.00, 10.00, 10.00, 10.00, 80, 'internal', 0, 1, NULL, NULL, '2026-07-02 11:45:17', '2026-08-07 13:02:39'),
(24, 'Option 2', '100% Exchange Coin Balance (Internal re-stake ÔÇö no blockchain, no USDT)', 100.00, 0.00, 0.00, 0.00, 20, 'internal', 0, 1, NULL, NULL, '2026-08-07 13:02:39', '2026-08-07 13:02:39');

-- --------------------------------------------------------

--
-- Table structure for table `commission_config`
--

CREATE TABLE `commission_config` (
  `id` int(11) NOT NULL,
  `direct_commission_status` int(11) DEFAULT 0,
  `level_commission_status` int(11) DEFAULT 0,
  `update_date` datetime DEFAULT NULL,
  `binary_pair_type` enum('percent','amount') NOT NULL DEFAULT 'percent',
  `binary_pair_ratio` varchar(10) NOT NULL DEFAULT '1:1',
  `repurchase_commission_status` tinyint(1) NOT NULL DEFAULT 0,
  `leadership_bonus_status` tinyint(1) NOT NULL DEFAULT 0,
  `pool_bonus_status` tinyint(1) NOT NULL DEFAULT 0,
  `binary_commission_status` int(11) NOT NULL DEFAULT 0,
  `matching_bonus_status` tinyint(1) NOT NULL DEFAULT 0,
  `carry_forward_status` tinyint(1) NOT NULL DEFAULT 1,
  `carry_forward_mode` enum('LIFETIME','DAILY','WEEKLY','MONTHLY') NOT NULL DEFAULT 'LIFETIME',
  `carry_forward_cap` decimal(18,2) DEFAULT NULL,
  `direct_commission_type` varchar(250) DEFAULT '0',
  `own_commission_status` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `commission_config`
--

INSERT INTO `commission_config` (`id`, `direct_commission_status`, `level_commission_status`, `update_date`, `binary_pair_type`, `binary_pair_ratio`, `repurchase_commission_status`, `leadership_bonus_status`, `pool_bonus_status`, `binary_commission_status`, `matching_bonus_status`, `carry_forward_status`, `carry_forward_mode`, `carry_forward_cap`, `direct_commission_type`, `own_commission_status`) VALUES
(1, 1, 1, '2026-02-13 07:27:06', 'percent', '1:1', 1, 1, 1, 1, 1, 1, 'LIFETIME', NULL, 'percent', 1);

-- --------------------------------------------------------

--
-- Table structure for table `contact_requests`
--

CREATE TABLE `contact_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'Resolved from email at submit time, if it matches an existing account',
  `email` varchar(190) NOT NULL,
  `message` text NOT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','resolved') NOT NULL DEFAULT 'pending',
  `admin_notes` varchar(500) DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_requests`
--

INSERT INTO `contact_requests` (`id`, `user_id`, `email`, `message`, `attachment_path`, `status`, `admin_notes`, `resolved_by`, `resolved_at`, `created_at`) VALUES
(1, 3, 'trustwallet@yopmail.com', 'My account was frozen, please help me unlock it. This is a test submission.', NULL, 'pending', NULL, NULL, NULL, '2026-07-21 10:50:10'),
(2, 3, 'trustwallet@yopmail.com', 'Testing a genuinely valid PNG attachment upload.', 'contact_6a5f32eea145c.png', 'pending', NULL, NULL, NULL, '2026-07-21 10:50:54'),
(3, 3, 'trustwallet@yopmail.com', 'sdfsdf gdfgdfg dfgfddfg', 'contact_6a5f35b05b6b2.png', 'resolved', NULL, 1, '2026-07-21 11:03:06', '2026-07-21 11:02:40');

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` int(11) NOT NULL,
  `code` varchar(10) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `dial_code` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `code`, `name`, `dial_code`) VALUES
(1, 'AF', 'Afghanistan', '+93'),
(2, 'AL', 'Albania', '+355'),
(3, 'DZ', 'Algeria', '+213'),
(4, 'AS', 'American Samoa', '+1-684'),
(5, 'AD', 'Andorra', '+376'),
(6, 'AO', 'Angola', '+244'),
(7, 'AI', 'Anguilla', '+1-264'),
(8, 'AG', 'Antigua and Barbuda', '+1-268'),
(9, 'AR', 'Argentina', '+54'),
(10, 'AM', 'Armenia', '+374'),
(11, 'AW', 'Aruba', '+297'),
(12, 'AU', 'Australia', '+61'),
(13, 'AT', 'Austria', '+43'),
(14, 'AZ', 'Azerbaijan', '+994'),
(15, 'BS', 'Bahamas', '+1-242'),
(16, 'BH', 'Bahrain', '+973'),
(17, 'BD', 'Bangladesh', '+880'),
(18, 'BB', 'Barbados', '+1-246'),
(19, 'BY', 'Belarus', '+375'),
(20, 'BE', 'Belgium', '+32'),
(21, 'BZ', 'Belize', '+501'),
(22, 'BJ', 'Benin', '+229'),
(23, 'BM', 'Bermuda', '+1-441'),
(24, 'BT', 'Bhutan', '+975'),
(25, 'BO', 'Bolivia', '+591'),
(26, 'BA', 'Bosnia and Herzegovina', '+387'),
(27, 'BW', 'Botswana', '+267'),
(28, 'BR', 'Brazil', '+55'),
(29, 'IO', 'British Indian Ocean Territory', '+246'),
(30, 'VG', 'British Virgin Islands', '+1-284'),
(31, 'BN', 'Brunei', '+673'),
(32, 'BG', 'Bulgaria', '+359'),
(33, 'BF', 'Burkina Faso', '+226'),
(34, 'BI', 'Burundi', '+257'),
(35, 'KH', 'Cambodia', '+855'),
(36, 'CM', 'Cameroon', '+237'),
(37, 'CA', 'Canada', '+1'),
(38, 'CV', 'Cape Verde', '+238'),
(39, 'KY', 'Cayman Islands', '+1-345'),
(40, 'CF', 'Central African Republic', '+236'),
(41, 'TD', 'Chad', '+235'),
(42, 'CL', 'Chile', '+56'),
(43, 'CN', 'China', '+86'),
(44, 'CX', 'Christmas Island', '+61'),
(45, 'CC', 'Cocos Islands', '+61'),
(46, 'CO', 'Colombia', '+57'),
(47, 'KM', 'Comoros', '+269'),
(48, 'CK', 'Cook Islands', '+682'),
(49, 'CR', 'Costa Rica', '+506'),
(50, 'HR', 'Croatia', '+385'),
(51, 'CU', 'Cuba', '+53'),
(52, 'CY', 'Cyprus', '+357'),
(53, 'CZ', 'Czech Republic', '+420'),
(54, 'CD', 'Democratic Republic of the Congo', '+243'),
(55, 'DK', 'Denmark', '+45'),
(56, 'DJ', 'Djibouti', '+253'),
(57, 'DM', 'Dominica', '+1-767'),
(58, 'DO', 'Dominican Republic', '+1-809'),
(59, 'DO', 'Dominican Republic', '+1-829'),
(60, 'TL', 'East Timor', '+670'),
(61, 'EC', 'Ecuador', '+593'),
(62, 'EG', 'Egypt', '+20'),
(63, 'SV', 'El Salvador', '+503'),
(64, 'GQ', 'Equatorial Guinea', '+240'),
(65, 'ER', 'Eritrea', '+291'),
(66, 'EE', 'Estonia', '+372'),
(67, 'ET', 'Ethiopia', '+251'),
(68, 'FK', 'Falkland Islands', '+500'),
(69, 'FO', 'Faroe Islands', '+298'),
(70, 'FJ', 'Fiji', '+679'),
(71, 'FI', 'Finland', '+358'),
(72, 'FR', 'France', '+33'),
(73, 'PF', 'French Polynesia', '+689'),
(74, 'GA', 'Gabon', '+241'),
(75, 'GM', 'Gambia', '+220'),
(76, 'GE', 'Georgia', '+995'),
(77, 'DE', 'Germany', '+49'),
(78, 'GH', 'Ghana', '+233'),
(79, 'GI', 'Gibraltar', '+350'),
(80, 'GR', 'Greece', '+30'),
(81, 'GL', 'Greenland', '+299'),
(82, 'GD', 'Grenada', '+1-473'),
(83, 'GU', 'Guam', '+1-671'),
(84, 'GT', 'Guatemala', '+502'),
(85, 'GN', 'Guinea', '+224'),
(86, 'GW', 'Guinea-Bissau', '+245'),
(87, 'GY', 'Guyana', '+592'),
(88, 'HT', 'Haiti', '+509'),
(89, 'HN', 'Honduras', '+504'),
(90, 'HK', 'Hong Kong', '+852'),
(91, 'HU', 'Hungary', '+36'),
(92, 'IS', 'Iceland', '+354'),
(93, 'IN', 'India', '+91'),
(94, 'ID', 'Indonesia', '+62'),
(95, 'IR', 'Iran', '+98'),
(96, 'IQ', 'Iraq', '+964'),
(97, 'IE', 'Ireland', '+353'),
(98, 'IM', 'Isle of Man', '+44-1624'),
(99, 'IL', 'Israel', '+972'),
(100, 'IT', 'Italy', '+39'),
(101, 'CI', 'Ivory Coast', '+225'),
(102, 'JM', 'Jamaica', '+1-876'),
(103, 'JP', 'Japan', '+81'),
(104, 'JE', 'Jersey', '+44-1534'),
(105, 'JO', 'Jordan', '+962'),
(106, 'KZ', 'Kazakhstan', '+7'),
(107, 'KE', 'Kenya', '+254'),
(108, 'KI', 'Kiribati', '+686'),
(109, 'KW', 'Kuwait', '+965'),
(110, 'KG', 'Kyrgyzstan', '+996'),
(111, 'LA', 'Laos', '+856'),
(112, 'LV', 'Latvia', '+371'),
(113, 'LB', 'Lebanon', '+961'),
(114, 'LS', 'Lesotho', '+266'),
(115, 'LR', 'Liberia', '+231'),
(116, 'LY', 'Libya', '+218'),
(117, 'LI', 'Liechtenstein', '+423'),
(118, 'LT', 'Lithuania', '+370'),
(119, 'LU', 'Luxembourg', '+352'),
(120, 'MO', 'Macao', '+853'),
(121, 'MK', 'Macedonia', '+389'),
(122, 'MG', 'Madagascar', '+261'),
(123, 'MW', 'Malawi', '+265'),
(124, 'MY', 'Malaysia', '+60'),
(125, 'MV', 'Maldives', '+960'),
(126, 'ML', 'Mali', '+223'),
(127, 'MT', 'Malta', '+356'),
(128, 'MH', 'Marshall Islands', '+692'),
(129, 'MQ', 'Martinique', '+596'),
(130, 'MR', 'Mauritania', '+222'),
(131, 'MU', 'Mauritius', '+230'),
(132, 'YT', 'Mayotte', '+262'),
(133, 'MX', 'Mexico', '+52'),
(134, 'FM', 'Micronesia', '+691'),
(135, 'MD', 'Moldova', '+373'),
(136, 'MC', 'Monaco', '+377'),
(137, 'MN', 'Mongolia', '+976'),
(138, 'ME', 'Montenegro', '+382'),
(139, 'MS', 'Montserrat', '+1-664'),
(140, 'MA', 'Morocco', '+212'),
(141, 'MZ', 'Mozambique', '+258'),
(142, 'MM', 'Myanmar', '+95'),
(143, 'NA', 'Namibia', '+264'),
(144, 'NR', 'Nauru', '+674'),
(145, 'NP', 'Nepal', '+977'),
(146, 'NL', 'Netherlands', '+31'),
(147, 'AN', 'Netherlands Antilles', '+599'),
(148, 'NC', 'New Caledonia', '+687'),
(149, 'NZ', 'New Zealand', '+64'),
(150, 'NI', 'Nicaragua', '+505'),
(151, 'NE', 'Niger', '+227'),
(152, 'NG', 'Nigeria', '+234'),
(153, 'NU', 'Niue', '+683'),
(154, 'KP', 'North Korea', '+850'),
(155, 'MP', 'Northern Mariana Islands', '+1-670'),
(156, 'NO', 'Norway', '+47'),
(157, 'OM', 'Oman', '+968'),
(158, 'PK', 'Pakistan', '+92'),
(159, 'PW', 'Palau', '+680'),
(160, 'PA', 'Panama', '+507'),
(161, 'PG', 'Papua New Guinea', '+675'),
(162, 'PY', 'Paraguay', '+595'),
(163, 'PE', 'Peru', '+51'),
(164, 'PH', 'Philippines', '+63'),
(165, 'PN', 'Pitcairn', '+870'),
(166, 'PL', 'Poland', '+48'),
(167, 'PT', 'Portugal', '+351'),
(168, 'PR', 'Puerto Rico', '+1-787'),
(169, 'PR', 'Puerto Rico', '+1-939'),
(170, 'QA', 'Qatar', '+974'),
(171, 'CG', 'Republic of the Congo', '+242'),
(172, 'RO', 'Romania', '+40'),
(173, 'RU', 'Russia', '+7'),
(174, 'RW', 'Rwanda', '+250'),
(175, 'BL', 'Saint Barthelemy', '+590'),
(176, 'SH', 'Saint Helena', '+290'),
(177, 'KN', 'Saint Kitts and Nevis', '+1-869'),
(178, 'LC', 'Saint Lucia', '+1-758'),
(179, 'MF', 'Saint Martin', '+590'),
(180, 'PM', 'Saint Pierre and Miquelon', '+508'),
(181, 'VC', 'Saint Vincent and the Grenadines', '+1-784'),
(182, 'WS', 'Samoa', '+685'),
(183, 'SM', 'San Marino', '+378'),
(184, 'ST', 'Sao Tome and Principe', '+239'),
(185, 'SA', 'Saudi Arabia', '+966'),
(186, 'SN', 'Senegal', '+221'),
(187, 'RS', 'Serbia', '+381'),
(188, 'SC', 'Seychelles', '+248'),
(189, 'SL', 'Sierra Leone', '+232'),
(190, 'SG', 'Singapore', '+65'),
(191, 'SK', 'Slovakia', '+421'),
(192, 'SI', 'Slovenia', '+386'),
(193, 'SB', 'Solomon Islands', '+677'),
(194, 'SO', 'Somalia', '+252'),
(195, 'ZA', 'South Africa', '+27'),
(196, 'KR', 'South Korea', '+82'),
(197, 'ES', 'Spain', '+34'),
(198, 'LK', 'Sri Lanka', '+94'),
(199, 'SD', 'Sudan', '+249'),
(200, 'SR', 'Suriname', '+597'),
(201, 'SJ', 'Svalbard and Jan Mayen', '+47'),
(202, 'SZ', 'Swaziland', '+268'),
(203, 'SE', 'Sweden', '+46'),
(204, 'CH', 'Switzerland', '+41'),
(205, 'SY', 'Syria', '+963'),
(206, 'TW', 'Taiwan', '+886'),
(207, 'TJ', 'Tajikistan', '+992'),
(208, 'TZ', 'Tanzania', '+255'),
(209, 'TH', 'Thailand', '+66'),
(210, 'TG', 'Togo', '+228'),
(211, 'TK', 'Tokelau', '+690'),
(212, 'TO', 'Tonga', '+676'),
(213, 'TT', 'Trinidad and Tobago', '+1-868'),
(214, 'TN', 'Tunisia', '+216'),
(215, 'TR', 'Turkey', '+90'),
(216, 'TM', 'Turkmenistan', '+993'),
(217, 'TC', 'Turks and Caicos Islands', '+1-649'),
(218, 'TV', 'Tuvalu', '+688'),
(219, 'VI', 'US Virgin Islands', '+1-340'),
(220, 'UG', 'Uganda', '+256'),
(221, 'UA', 'Ukraine', '+380'),
(222, 'AE', 'United Arab Emirates', '+971'),
(223, 'GB', 'United Kingdom', '+44'),
(224, 'US', 'United States', '+1'),
(225, 'UY', 'Uruguay', '+598'),
(226, 'UZ', 'Uzbekistan', '+998'),
(227, 'VU', 'Vanuatu', '+678'),
(228, 'VA', 'Vatican City', '+379'),
(229, 'VE', 'Venezuela', '+58'),
(230, 'VN', 'Vietnam', '+84'),
(231, 'WF', 'Wallis and Futuna', '+681'),
(232, 'EH', 'Western Sahara', '+212'),
(233, 'YE', 'Yemen', '+967'),
(234, 'ZM', 'Zambia', '+260'),
(235, 'ZW', 'Zimbabwe', '+263');

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_per_user` int(11) DEFAULT NULL,
  `min_order_amount` decimal(10,2) DEFAULT NULL,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `valid_from` date NOT NULL,
  `valid_to` date NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupon_usage`
--

CREATE TABLE `coupon_usage` (
  `id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `applied_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cron_execution_log`
--

CREATE TABLE `cron_execution_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `cron_name` varchar(80) NOT NULL,
  `status` varchar(20) NOT NULL,
  `summary` text DEFAULT NULL,
  `duration_ms` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cron_execution_log`
--

INSERT INTO `cron_execution_log` (`id`, `cron_name`, `status`, `summary`, `duration_ms`, `created_at`) VALUES
(1, 'roi_distribution', 'error', '{\"status\":\"error\",\"message\":\"ROI distribution completed with errors \\u2014 see monthly\\/maturity detail for which leg\\/records failed\",\"monthly\":{\"status\":false,\"error\":\"Internal request failed: Failed to connect to 192.168.29.18 port 9000 after 2023 ms: Couldn\'t connect to server\"},\"maturity\":{\"status\":false,\"error\":\"Internal request failed: Failed to connect to 192.168.29.18 port 9000 after 2008 ms: Couldn\'t connect to server\"},\"ran_at\":\"2026-08-06 10:07:52\"}', 4033, '2026-08-06 10:07:52'),
(2, 'binary_matching_payout', 'success', '{\"status\":\"success\",\"message\":\"Binary matching payout cron completed\",\"mode\":\"LIVE\",\"details\":{\"engine\":{\"claimed\":true,\"run_ref\":\"MB-20260806-100752-FC7708\",\"status\":\"DONE\",\"summary\":{\"run_ref\":\"MB-20260806-100752-FC7708\",\"propagated\":2,\"paid_users\":0,\"matched_volume\":0,\"earning_paid\":0,\"staking_paid\":0,\"ceiling_held\":0}},\"enqueued\":{\"scanned\":0,\"enqueued\":0,\"skipped_no_wallet\":0},\"drain\":{\"processed\":0,\"sent\":0,\"held\":0},\"confirm\":{\"checked\":0,\"confirmed\":0,\"failed\":0}},\"ran_at\":\"2026-08-06 10:07:52\"}', 74, '2026-08-06 10:07:52'),
(3, 'binary_matching_payout', 'success', '{\"status\":\"success\",\"message\":\"Binary matching payout cron completed\",\"mode\":\"LIVE\",\"details\":{\"engine\":{\"claimed\":true,\"run_ref\":\"MB-20260806-120943-0F74AA\",\"status\":\"DONE\",\"summary\":{\"run_ref\":\"MB-20260806-120943-0F74AA\",\"propagated\":0,\"paid_users\":0,\"matched_volume\":0,\"earning_paid\":0,\"staking_paid\":0,\"ceiling_held\":0}},\"enqueued\":{\"scanned\":0,\"enqueued\":0,\"skipped_no_wallet\":0},\"drain\":{\"processed\":0,\"sent\":0,\"held\":0},\"confirm\":{\"checked\":0,\"confirmed\":0,\"failed\":0}},\"ran_at\":\"2026-08-06 12:09:43\"}', 98, '2026-08-06 12:09:43'),
(4, 'roi_distribution', 'error', '{\"status\":\"error\",\"message\":\"ROI distribution completed with errors \\u2014 see monthly\\/maturity detail for which leg\\/records failed\",\"monthly\":{\"status\":false,\"error\":\"Internal request failed: Failed to connect to 192.168.29.18 port 9000 after 2029 ms: Couldn\'t connect to server\"},\"maturity\":{\"status\":false,\"error\":\"Internal request failed: Failed to connect to 192.168.29.18 port 9000 after 2034 ms: Couldn\'t connect to server\"},\"ran_at\":\"2026-08-06 14:44:46\"}', 4070, '2026-08-06 14:44:46'),
(5, 'roi_distribution', 'success', '{\"status\":\"success\",\"message\":\"ROI distribution (monthly then maturity) completed\",\"monthly\":{\"status\":true,\"message\":\"Monthly ROI distribution\",\"due_records\":0,\"credits_made\":0,\"failed\":0,\"details\":[],\"ran_at\":\"2026-08-06 14:54:18\"},\"maturity\":{\"status\":true,\"message\":\"ROI maturity payment\",\"due\":0,\"matured\":0,\"waiting_on_monthly\":0,\"failed\":0,\"details\":[],\"ran_at\":\"2026-08-06 14:54:19\"},\"ran_at\":\"2026-08-06 14:54:19\"}', 262, '2026-08-06 14:54:19'),
(6, 'roi_distribution', 'success', '{\"status\":\"success\",\"message\":\"ROI distribution (monthly then maturity) completed\",\"monthly\":{\"status\":true,\"message\":\"Monthly ROI distribution\",\"due_records\":0,\"credits_made\":0,\"failed\":0,\"details\":[],\"ran_at\":\"2026-08-06 16:00:32\"},\"maturity\":{\"status\":true,\"message\":\"ROI maturity payment\",\"due\":0,\"matured\":0,\"waiting_on_monthly\":0,\"failed\":0,\"details\":[],\"ran_at\":\"2026-08-06 16:00:32\"},\"ran_at\":\"2026-08-06 16:00:32\"}', 587, '2026-08-06 16:00:32'),
(7, 'binary_matching_payout', 'success', '{\"status\":\"success\",\"message\":\"Binary matching payout cron completed\",\"mode\":\"LIVE\",\"details\":{\"engine\":{\"claimed\":true,\"run_ref\":\"MB-20260806-190902-702C0C\",\"status\":\"DONE\",\"summary\":{\"run_ref\":\"MB-20260806-190902-702C0C\",\"propagated\":0,\"paid_users\":0,\"matched_volume\":0,\"earning_paid\":0,\"staking_paid\":0,\"ceiling_held\":0}},\"enqueued\":{\"scanned\":0,\"enqueued\":0,\"skipped_no_wallet\":0},\"drain\":{\"processed\":0,\"sent\":0,\"held\":0},\"confirm\":{\"checked\":0,\"confirmed\":0,\"failed\":0}},\"ran_at\":\"2026-08-06 19:09:02\"}', 3, '2026-08-06 19:09:02'),
(8, 'roi_distribution', 'success', '{\"status\":\"success\",\"message\":\"ROI distribution (monthly then maturity) completed\",\"monthly\":{\"status\":true,\"message\":\"Monthly ROI distribution\",\"due_records\":0,\"credits_made\":0,\"failed\":0,\"details\":[],\"ran_at\":\"2026-08-07 09:43:23\"},\"maturity\":{\"status\":true,\"message\":\"ROI maturity payment\",\"due\":0,\"matured\":0,\"waiting_on_monthly\":0,\"failed\":0,\"details\":[],\"ran_at\":\"2026-08-07 09:43:23\"},\"ran_at\":\"2026-08-07 09:43:23\"}', 666, '2026-08-07 09:43:23'),
(9, 'binary_matching_payout', 'success', '{\"status\":\"success\",\"message\":\"Binary matching payout cron completed\",\"mode\":\"LIVE\",\"details\":{\"engine\":{\"claimed\":true,\"run_ref\":\"MB-20260807-094332-C8E156\",\"status\":\"DONE\",\"summary\":{\"run_ref\":\"MB-20260807-094332-C8E156\",\"propagated\":9,\"paid_users\":1,\"matched_volume\":2,\"earning_paid\":0.16,\"staking_paid\":0.04,\"ceiling_held\":0}},\"enqueued\":{\"scanned\":1,\"enqueued\":1,\"skipped_no_wallet\":0},\"drain\":{\"processed\":1,\"sent\":1,\"held\":0},\"confirm\":{\"checked\":1,\"confirmed\":0,\"failed\":0}},\"ran_at\":\"2026-08-07 09:43:39\"}', 7614, '2026-08-07 09:43:39'),
(10, 'roi_distribution', 'success', '{\"status\":\"success\",\"message\":\"ROI distribution (monthly then maturity) completed\",\"monthly\":{\"status\":true,\"message\":\"Monthly ROI distribution\",\"due_records\":0,\"credits_made\":0,\"failed\":0,\"details\":[],\"ran_at\":\"2026-08-07 09:44:51\"},\"maturity\":{\"status\":true,\"message\":\"ROI maturity payment\",\"due\":0,\"matured\":0,\"waiting_on_monthly\":0,\"failed\":0,\"details\":[],\"ran_at\":\"2026-08-07 09:44:51\"},\"ran_at\":\"2026-08-07 09:44:51\"}', 259, '2026-08-07 09:44:51'),
(11, 'binary_matching_payout', 'success', '{\"status\":\"success\",\"message\":\"Binary matching payout cron completed\",\"mode\":\"LIVE\",\"details\":{\"engine\":{\"claimed\":true,\"run_ref\":\"MB-20260807-094452-744A27\",\"status\":\"DONE\",\"summary\":{\"run_ref\":\"MB-20260807-094452-744A27\",\"propagated\":0,\"paid_users\":0,\"matched_volume\":0,\"earning_paid\":0,\"staking_paid\":0,\"ceiling_held\":0}},\"enqueued\":{\"scanned\":0,\"enqueued\":0,\"skipped_no_wallet\":0},\"drain\":{\"processed\":0,\"sent\":0,\"held\":0},\"confirm\":{\"checked\":1,\"confirmed\":1,\"failed\":0}},\"ran_at\":\"2026-08-07 09:44:54\"}', 2620, '2026-08-07 09:44:54'),
(12, 'roi_distribution', 'error', '{\"status\":\"error\",\"message\":\"ROI distribution completed with errors \\u2014 see monthly\\/maturity detail for which leg\\/records failed\",\"monthly\":{\"status\":false,\"error\":\"Internal request failed: Operation timed out after 60038 milliseconds with 0 bytes received\"},\"maturity\":{\"status\":false,\"error\":\"Internal request failed: Operation timed out after 60007 milliseconds with 0 bytes received\"},\"ran_at\":\"2026-08-08 11:57:12\"}', 120051, '2026-08-08 11:57:12');

-- --------------------------------------------------------

--
-- Table structure for table `currency_config`
--

CREATE TABLE `currency_config` (
  `id` int(11) NOT NULL,
  `coin_name` varchar(250) NOT NULL,
  `currency_status` int(11) NOT NULL,
  `api_call` varchar(250) DEFAULT NULL,
  `decimal` int(11) DEFAULT 2,
  `currency_value` varchar(250) NOT NULL DEFAULT '2',
  `staking_toke_symbol` varchar(250) DEFAULT NULL,
  `staking_toke_name` varchar(240) DEFAULT NULL,
  `currency_symbol` blob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `currency_config`
--

INSERT INTO `currency_config` (`id`, `coin_name`, `currency_status`, `api_call`, `decimal`, `currency_value`, `staking_toke_symbol`, `staking_toke_name`, `currency_symbol`) VALUES
(1, 'USD', 1, '1b6ed52ef6a6416c1acc3095b52ac90f83e26dd35edd72f95c225795dcc38a67', 3, '1', '', 'CSQ', 0x24),
(6, 'USDT', 0, '1b6ed52ef6a6416c1acc3095b52ac90f83e26dd35edd72f95c225795dcc38a67', 2, '1', '', 'AUSD', 0xe282ae),
(8, 'INR', 0, NULL, 2, '1', NULL, NULL, 0xe282b9),
(9, 'INR', 0, NULL, 2, '11', NULL, NULL, 0xe282b9);

-- --------------------------------------------------------

--
-- Table structure for table `custodial_deposits`
--

CREATE TABLE `custodial_deposits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `address` varchar(100) NOT NULL,
  `token` varchar(20) NOT NULL DEFAULT 'USDT',
  `amount` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `tx_hash` varchar(120) DEFAULT NULL,
  `onchain_confirmed` tinyint(1) NOT NULL DEFAULT 1,
  `credited` tinyint(1) NOT NULL DEFAULT 0,
  `source` varchar(30) NOT NULL DEFAULT 'monitor',
  `note` varchar(255) DEFAULT NULL,
  `detected_at` datetime NOT NULL DEFAULT current_timestamp(),
  `credited_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deposits`
--

CREATE TABLE `deposits` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `package_id` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `earning_ads`
--

CREATE TABLE `earning_ads` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `ad_url` text NOT NULL,
  `thumb_url` text DEFAULT NULL,
  `duration_seconds` int(11) NOT NULL DEFAULT 30,
  `reward_usd` decimal(12,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `earning_methods`
--

CREATE TABLE `earning_methods` (
  `id` int(11) NOT NULL,
  `code` varchar(30) NOT NULL,
  `title` varchar(100) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `icon` varchar(60) DEFAULT NULL,
  `badge_text` varchar(30) DEFAULT NULL,
  `badge_bg` varchar(30) DEFAULT NULL,
  `badge_color` varchar(30) DEFAULT NULL,
  `progress_color` varchar(30) DEFAULT NULL,
  `btn_text` varchar(50) DEFAULT NULL,
  `btn_gradient` varchar(120) DEFAULT NULL,
  `daily_target` int(11) NOT NULL DEFAULT 0,
  `reward_usd` decimal(12,2) NOT NULL DEFAULT 0.00,
  `est_time_label` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `earning_videos`
--

CREATE TABLE `earning_videos` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `video_url` text NOT NULL,
  `thumb_url` text DEFAULT NULL,
  `duration_seconds` int(11) NOT NULL DEFAULT 30,
  `reward_usd` decimal(12,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_config`
--

CREATE TABLE `email_config` (
  `id` int(11) NOT NULL,
  `host` varchar(250) DEFAULT NULL,
  `smtp_auth` varchar(50) DEFAULT NULL,
  `username` varchar(250) DEFAULT NULL,
  `password` varchar(250) DEFAULT NULL,
  `smtpsecure` varchar(150) DEFAULT NULL,
  `port` int(11) DEFAULT NULL,
  `from_name` varchar(150) DEFAULT NULL,
  `from_mail` varchar(150) DEFAULT NULL,
  `php_mail` varchar(150) DEFAULT NULL,
  `smtp_status` enum('0','1') DEFAULT NULL,
  `updated_name` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_config`
--

INSERT INTO `email_config` (`id`, `host`, `smtp_auth`, `username`, `password`, `smtpsecure`, `port`, `from_name`, `from_mail`, `php_mail`, `smtp_status`, `updated_name`) VALUES
(1, 'smtp.hostinger.com', 'true', 'info@nexman.in', 'jkmC$~4gDZ4!', 'ssl', 465, 'info@nexman.in', 'info@nexman.in', 'support@nexman.in', '1', '2026-08-06 08:08:58');

-- --------------------------------------------------------

--
-- Table structure for table `email_log`
--

CREATE TABLE `email_log` (
  `id` int(11) NOT NULL,
  `otp` varchar(250) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `type` varchar(150) DEFAULT NULL,
  `created_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_log`
--

INSERT INTO `email_log` (`id`, `otp`, `email`, `type`, `created_date`) VALUES
(1, '235847', 'admin@gmail.com', 'email_verify', '2026-07-24 15:39:31'),
(2, '235847', 'admin@gmail.com', 'email_verify', '2026-07-24 15:39:31'),
(3, '235847', 'admin@gmail.com', 'email_verify', '2026-07-24 15:39:31'),
(4, '927160', 'siva@yopmail.com', 'email_verify', '2026-07-20 18:55:51'),
(5, '235847', 'admin@gmail.com', 'email_verify', '2026-07-24 15:39:31'),
(6, '927160', 'siva@yopmail.com', 'email_verify', '2026-07-20 18:55:51'),
(7, '75cb39f6a8fe351c798608d7afdfbcaa23d301b352fb77a65f4de451f55c973a', 'siva@yopmail.com', 'password_reset', '2026-07-20 15:37:18'),
(8, 'c2c6722b7eb157aaaf17684bdcb9ef94354482f2fe6e29e8afe9a6ada55a28be', 'trustwallet@yopmail.com', 'password_reset', '2026-07-20 19:13:13'),
(9, '917863', 'trustwallet@yopmail.com', 'email_verify', '2026-08-05 07:09:44'),
(10, '150846', 'rightbytrustwallet@yopmail.com', 'email_verify', '2026-07-24 17:05:34'),
(11, '364715', 'lak@yopmail.com', 'email_verify', '2026-08-05 07:42:54'),
(12, '509678', 'viki@yopmail.com', 'email_verify', '2026-08-06 08:10:09'),
(13, '4f8e66a76556c3e8bad38a9c91a500c563a3ff99588a760264104b6954453fde', 'viki@yopmail.com', 'password_reset', '2026-08-06 08:09:11'),
(14, '028457', 'ccc@yopmail.com', 'email_verify', '2026-08-06 11:59:47'),
(15, '259376', 'bbb@yopmail.com', 'email_verify', '2026-08-06 08:23:15'),
(16, '670438', 'eee@yopmail.com', 'email_verify', '2026-08-06 12:11:52'),
(17, '056874', 'ddd@yopmail.com', 'email_verify', '2026-08-06 12:25:14'),
(18, '035794', 'ggg@yopmail.com', 'email_verify', '2026-08-06 13:35:46'),
(19, '735980', 'jjj@yopmail.com', 'email_verify', '2026-08-06 13:38:57'),
(20, '510247', 'kkk@yopmail.com', 'email_verify', '2026-08-06 16:54:23'),
(21, '567914', 'loveyou@yopmail.com', 'email_verify', '2026-08-06 17:55:28'),
(22, '875062', 'youlove@yopmail.com', 'email_verify', '2026-08-07 07:01:52'),
(23, '714690', 'iii@yopmail.com', 'email_verify', '2026-08-07 10:02:00'),
(24, '201486', 'ilefti@yopmail.com', 'email_verify', '2026-08-07 14:39:04'),
(25, '475263', 'hhh@yopmail.com', 'email_verify', '2026-08-07 20:06:30'),
(26, '437562', 'a1@yopmail.com', 'email_verify', '2026-08-07 20:08:13'),
(27, '759824', 'a2@yopmail.com', 'email_verify', '2026-08-07 20:13:03'),
(28, '389142', 'a3@yopmail.com', 'email_verify', '2026-08-07 20:16:22');

-- --------------------------------------------------------

--
-- Table structure for table `email_template`
--

CREATE TABLE `email_template` (
  `id` int(11) NOT NULL,
  `subject` varchar(250) NOT NULL,
  `temp_content` text NOT NULL,
  `temp_name` varchar(250) DEFAULT NULL,
  `temp_status` int(11) DEFAULT 1,
  `created_date` datetime DEFAULT NULL,
  `update_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `email_template`
--

INSERT INTO `email_template` (`id`, `subject`, `temp_content`, `temp_name`, `temp_status`, `created_date`, `update_date`) VALUES
(1, 'Welcome to Nexman', '<meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n<div style=\"max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);\">\r\n<div style=\"text-align: center; padding: 10px 0; border-bottom: 1px solid #ddd; margin-bottom: 20px; background-color: #FFC947;\"></div>\r\n\r\n<div style=\"padding: 20px 0; text-align: center; background-color: #F0E9E1;\">Dear [FIRSTNAME],<br />\r\n    \r\n<br />\r\nYour account has been successfully created on our platform.<br />\r\n<br />\r\n\r\nPlease keep your credentials secure and do not share them with anyone.<br />\r\nHere are your account details:<br />\r\n<br />\r\n\r\n<strong>Email:</strong><br />\r\n[email]<br />\r\n\r\n<strong>Username:</strong><br />\r\n[username]<br />\r\n<strong>Registration Date:</strong>[date]<br />\r\n<br />\r\n\r\nYour  Wallet Address:<br />\r\n<strong>[WalletAddress]</strong><br />\r\n\r\nYour  MNEMONIC:<br />\r\n<strong>[PHARSE]</strong><br />\r\n\r\nFor added security, your Two-Factor Authentication (2FA) key is:<br />\r\n<strong>[secret]</strong><br />\r\n\r\n\r\n\r\n<br />\r\nThank you for choosing us. If you have any questions or need assistance, please feel free to contact us.<br />\r\n </div>\r\n\r\n<div style=\"text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #666; background-color: #FFC947;\">© 2025 Nexman.</div>\r\n</div>\r\n', 'Welcome Mail', 1, NULL, NULL),
(2, 'Forgot Passowrd', '<meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n<title></title>\n<div style=\"max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);\">\n<div style=\"text-align: center; padding: 10px 0; border-bottom: 1px solid #ddd; margin-bottom: 20px; background-color: #FFC947;\"></div>\n\n<div style=\"padding: 20px 0; text-align: center; background-color: #F0E9E1;\">Your One time password is:<br />\n<br />\n<strong>[confrim_password]</strong><br />\n<br />\nPlease use this password to complete the login process.<br />\nThis code will expire shortly for security reasons.<br />\n<br />\n&nbsp;\n<p>Feel free to contact us anytime at #adminemail</p>\n\n<p>Best Regards!<br />\n___________</p>\n\n<p>Support #sitename</p>\n</div>\n\n<div style=\"text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #666; background-color: #FFC947;\">&copy; 2025 Nexman </div>\n</div>\n', 'Forgot Passowrd ', 1, NULL, NULL),
(4, 'Withdraw Success', '<meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n<style type=\"text/css\">/* Resetting default margin and padding for email compatibility */\n            body, h1, h2, h3, h4, h5, h6, p, ul, ol {\n                margin: 0;\n                padding: 0;\n            }\n            \n            body {\n                font-family: Arial, sans-serif;\n                line-height: 1.6;\n                background-color: #F0E9E1;\n            }\n    \n            .email-container {\n                max-width: 600px;\n                margin: 0 auto;\n                padding: 20px;\n                background-color: #ffffff;\n                border-radius: 8px;\n                box-shadow: 0 4px 8px rgba(0,0,0,0.1);\n            }\n    \n            .header {\n                text-align: center;\n                padding: 10px 0;\n                border-bottom: 1px solid #ddd;\n                margin-bottom: 20px;\n                 background-color: #FFC947;\n            }\n    \n          \n             .header img {\n                max-width: 100%;\n                height: auto;\n                max-height: 80px; \n            }\n    \n            .content {\n                padding: 20px 0;\n                text-align:center;\n                background-color: #F0E9E1;\n            }\n    \n            .footer {\n                text-align: center;\n                margin-top: 20px;\n                padding-top: 10px;\n                border-top: 1px solid #ddd;\n                font-size: 12px;\n                color: #666;\n                 background-color: #FFC947;\n            }\n</style>\n<div class=\"email-container\">\n<div class=\"header\"></div>\n\n<div class=\"content\">Your Withdraw Made Successfully:<br />\n<br />\n<strong>[withdrawAmount]</strong><br />\n<br />\n<br />\n&nbsp;\n<p>Feel free to contact us anytime at #adminemail</p>\n\n<p>Best Regards!<br />\n___________</p>\n\n<p>Support #sitename</p>\n</div>\n\n<div class=\"footer\">&copy; 2026 Nexman.</div>\n</div>\n', 'Withdraw Success ', 1, NULL, NULL),
(7, 'Email Verification', '<meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n<div style=\"max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);\">\n<div style=\"text-align: center; padding: 10px 0; border-bottom: 1px solid #ddd; margin-bottom: 20px; background-color: #FFC947;\"></div>\n\n<div style=\"padding: 20px 0; text-align: center; background-color: #F0E9E1;\">Dear User,<br />\n    \n<br />\nYour One Time OTP Verification.<br />\n<br />\n\nPlease keep your credentials secure and do not share them with anyone.<br />\nHere are your account details:<br />\n<br />\n\n<strong>OTP:</strong><br />\n[temp_otp]<br />\n\n\n\n<br />\nThank you for choosing us. If you have any questions or need assistance, please feel free to contact us.<br />\n </div>\n\n<div style=\"text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #666; background-color: #FFC947;\">© 2025 Nexman.</div>\n</div>\n', 'Your Email Verification', 1, NULL, NULL),
(10, 'Deposit Made Successfully', '<meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n<title></title>\n<div style=\"max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);\">\n<div style=\"text-align: center; padding: 10px 0; border-bottom: 1px solid #ddd; margin-bottom: 20px; background-color: #FFC947;\"></div>\n\n<div style=\"padding: 20px; text-align: center; background-color: #E8F0FE;\">\n<h2>Deposit Confirmation</h2>\n\n<p>Dear [Name],</p>\n\n<p>Thank you for your deposit. Below are the details of your transaction:</p>\n\n<table style=\"margin: 0 auto; border-collapse: collapse; width: 80%;\">\n	<tbody>\n		<tr style=\"background-color: #f2f2f2;\">\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">Payment ID:</td>\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">[PaymentID]</td>\n		</tr>\n		<tr>\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">USD Amount:</td>\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">$ [USDPrice]</td>\n		</tr>\n		<tr style=\"background-color: #f2f2f2;\">\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">Cryptocurrency Amount:</td>\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">[CryptoPrice] [CryptoCurrency]</td>\n		</tr>\n	</tbody>\n</table>\n\n<p>Your deposit has been successfully processed and is now available in your account.</p>\n\n<p>If you have any questions or need further assistance, please feel free to contact our support team.</p>\n\n<p style=\"font-size: 12px; color: #666;\">Best Regards!<br />\n___________</p>\n\n<p style=\"font-size: 12px; color: #666;\">Support <a href=\"#sitelink\">#sitename</a></p>\n</div>\n\n<div style=\"text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #666; background-color: #FFC947;\">&copy; 2026 Nexman </div>\n</div>\n', 'Deposit Made Successfully', 1, NULL, NULL),
(11, 'Deposit Request Made Successfully', '<meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n<title></title>\n<div style=\"max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);\">\n<div style=\"text-align: center; padding: 10px 0; border-bottom: 1px solid #ddd; margin-bottom: 20px; background-color: #FFC947;\"></div>\n\n<div style=\"padding: 20px; text-align: center; background-color: #E8F0FE;\">\n<h2>Deposit Request Received</h2>\n\n<p>Dear [Name],</p>\n\n<p>We have received your request to deposit funds into your account. Please find the details of your request below:</p>\n\n<table style=\"margin: 0 auto; border-collapse: collapse; width: 80%;\">\n	<tbody>\n		<tr style=\"background-color: #f2f2f2;\">\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">Payment ID:</td>\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">[PaymentID]</td>\n		</tr>\n		<tr>\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">USD Amount:</td>\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">$[USDPrice]</td>\n		</tr>\n		<tr style=\"background-color: #f2f2f2;\">\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">Cryptocurrency Amount:</td>\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">[CryptoPrice] [CryptoCurrency]</td>\n		</tr>\n	</tbody>\n</table>\n\n<p>Your deposit is being processed and will be available in your account shortly.</p>\n\n<p>If you have any questions or need further assistance, please feel free to contact our support team.</p>\n\n<p style=\"font-size: 12px; color: #666;\">Best Regards!<br />\n___________</p>\n\n<p style=\"font-size: 12px; color: #666;\">Support <a href=\"#sitelink\">#sitename</a></p>\n</div>\n\n<div style=\"text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #666; background-color: #FFC947;\">&copy; 2026 Nexman.</div>\n</div>\n', 'Deposit Request Made Successfully', 1, NULL, NULL),
(13, 'Profile Update Notification', '<meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n<title></title>\r\n<div style=\"max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);\">\r\n<div style=\"text-align: center; padding: 10px 0; border-bottom: 1px solid #ddd; margin-bottom: 20px; background-color: #FFC947;\"></div>\r\n\r\n<div style=\"padding: 20px; text-align: center; background-color: #E9F7EF;\">\r\n<h2>Profile Update Notification</h2>\r\n\r\n<p>Dear [Name],</p>\r\n\r\n<p>We wanted to inform you that your profile details have been successfully updated. Please review the updated information below:</p>\r\n\r\n<table style=\"margin: 0 auto; border-collapse: collapse; width: 80%;\">\r\n	<tbody>\r\n		<tr style=\"background-color: #f2f2f2;\">\r\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">User Name:</td>\r\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">[Name]</td>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">Country:</td>\r\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">[Country]</td>\r\n		</tr>\r\n		<tr style=\"background-color: #f2f2f2;\">\r\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">Email:</td>\r\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">[UpdatedEmail]</td>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">Phone Number:</td>\r\n			<td style=\"padding: 10px; border: 1px solid #ddd;\">[Number]</td>\r\n		</tr>\r\n	</tbody>\r\n</table>\r\n\r\n<p>If you did not request these changes or if you have any questions, please contact our support team immediately.</p>\r\n\r\n<p>Thank you for keeping your profile information up to date.</p>\r\n\r\n<p style=\"font-size: 12px; color: #666;\">Best Regards!<br />\r\n___________</p>\r\n\r\n<p style=\"font-size: 12px; color: #666;\">Support <a href=\"#sitelink\">#sitename</a></p>\r\n</div>\r\n\r\n<div style=\"text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #666; background-color: #FFC947;\">© 2026 Nexman.</div>\r\n</div>\r\n', 'Profile Update Notification', 0, NULL, NULL),
(14, 'Verify Your Payout Request - OTP', '<html>\n<head>\n  <style>\n    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }\n    .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }\n    .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }\n    .content { padding: 20px; }\n    .otp-box { background-color: #f0f0f0; padding: 15px; text-align: center; border-radius: 5px; margin: 20px 0; }\n    .otp-code { font-size: 32px; font-weight: bold; letter-spacing: 2px; color: #4CAF50; }\n    .details { background-color: #f9f9f9; padding: 15px; border-left: 4px solid #4CAF50; margin: 20px 0; }\n    .footer { text-align: center; font-size: 12px; color: #666; margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px; }\n  </style>\n</head>\n<body>\n  <div class=\"container\">\n    <div class=\"header\">\n      <h2>Payout Request Verification</h2>\n    </div>\n\n    <div class=\"content\">\n      <p>Hello <strong>[USERNAME]</strong>,</p>\n\n      <p>You have initiated a payout request. To complete this request, please verify using the One-Time Password (OTP) below:</p>\n\n      <div class=\"otp-box\">\n        <p>Your OTP Code:</p>\n        <div class=\"otp-code\">[OTP]</div>\n        <p style=\"color: #666; font-size: 12px;\">Valid for 15 minutes</p>\n      </div>\n\n      <div class=\"details\">\n        <p><strong>Payout Details:</strong></p>\n        <p>Amount: <strong>[AMOUNT]</strong></p>\n        <p>Method: <strong>[METHOD]</strong></p>\n      </div>\n\n      <p style=\"color: #d9534f;\"><strong>Security Notice:</strong></p>\n      <ul>\n        <li>Do not share this OTP with anyone</li>\n        <li>This OTP is valid for 15 minutes only</li>\n        <li>If you did not initiate this request, please ignore this email</li>\n      </ul>\n\n      <p>If you have any questions, please contact our support team.</p>\n    </div>\n\n    <div class=\"footer\">\n      <p>&copy; 2026 Nexman. All rights reserved.</p>\n      <p>This is an automated message, please do not reply.</p>\n    </div>\n  </div>\n</body>\n</html>', 'Payout OTP Verification', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `actor_type` varchar(20) NOT NULL,
  `actor_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) DEFAULT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `email` varchar(160) DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `otp_hash` varchar(255) DEFAULT NULL,
  `otp_expire_at` datetime DEFAULT NULL,
  `ttl_sec` int(11) NOT NULL DEFAULT 600,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `epin_batches`
--

CREATE TABLE `epin_batches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `prefix` varchar(10) DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'USD',
  `denomination` decimal(14,2) NOT NULL DEFAULT 0.00,
  `qty` int(10) UNSIGNED NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` longtext NOT NULL,
  `page_key` varchar(50) NOT NULL DEFAULT 'support',
  `datetime` datetime NOT NULL,
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `faqs`
--

INSERT INTO `faqs` (`id`, `question`, `answer`, `page_key`, `datetime`, `status`) VALUES
(1, 'FAQ QUESTION', 'FAQ ANSWER', 'support', '2026-07-24 13:36:31', 1);

-- --------------------------------------------------------

--
-- Table structure for table `gas_fee_ledger`
--

CREATE TABLE `gas_fee_ledger` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tx_type` varchar(40) NOT NULL,
  `reference_type` varchar(40) DEFAULT NULL,
  `reference_id` varchar(64) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `from_address` varchar(120) DEFAULT NULL,
  `to_address` varchar(120) DEFAULT NULL,
  `tx_hash` varchar(120) DEFAULT NULL,
  `onchain_transaction_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('pending','confirmed','failed') NOT NULL DEFAULT 'pending',
  `gas_limit_used` bigint(20) DEFAULT NULL,
  `gas_price_wei` decimal(38,0) DEFAULT NULL,
  `gas_used` bigint(20) DEFAULT NULL,
  `native_fee_total` decimal(38,18) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `confirmed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gas_fee_ledger`
--

INSERT INTO `gas_fee_ledger` (`id`, `tx_type`, `reference_type`, `reference_id`, `user_id`, `from_address`, `to_address`, `tx_hash`, `onchain_transaction_id`, `status`, `gas_limit_used`, `gas_price_wei`, `gas_used`, `native_fee_total`, `created_at`, `confirmed_at`) VALUES
(9, 'gas', 'stake_purchase', 'SWP-20260805-48D6BAB1', 23, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xb3a4c6e46049be49cdb9734dbfb2897ade83fe83', '0x3e337247ec5b7002e4f77e10ae4e913df6d687b9381857e0952a0d4058f34b53', 104, 'confirmed', 21000, 5000000000, 21000, 0.000105000000000000, '2026-08-05 12:49:26', '2026-08-05 14:11:15'),
(10, 'gas', 'stake_purchase', 'SWP-20260805-48D6BAB1', 23, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xb3a4c6e46049be49cdb9734dbfb2897ade83fe83', '0xb126bb3fd3bb1a1ed398a94a0270e8a4823a125a5889bd4c8b98010908ba01aa', 105, 'confirmed', 21000, 5000000000, 21000, 0.000105000000000000, '2026-08-05 13:04:58', '2026-08-05 14:11:12'),
(11, 'usdt', 'stake_purchase', 'SWP-20260805-48D6BAB1', 23, '0xb3a4c6e46049be49cdb9734dbfb2897ade83fe83', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xcea86e9eb3646fb4bb4ccf659d4539504a7376b7a8204161b434b3f40e289118', 106, 'confirmed', 210000, 5000000000, 34491, 0.000172455000000000, '2026-08-05 13:05:30', '2026-08-05 14:11:09'),
(12, 'bonus', 'stake_purchase', 'SWP-20260805-48D6BAB1', 23, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xb3a4c6e46049be49cdb9734dbfb2897ade83fe83', '0x47d6224d250d8912e7cbf270476a2fb726944bcc9680225c1250c67f3dacf727', 107, 'confirmed', 210000, 5000000000, 34577, 0.000172885000000000, '2026-08-05 13:09:18', '2026-08-05 14:11:07'),
(13, 'bman', 'stake_purchase', 'SWP-20260805-48D6BAB1', 23, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xb3a4c6e46049be49cdb9734dbfb2897ade83fe83', '0x460fa827d11c69b34ab2f2ef10544e5b2ca101f5da28180d806c260c67199361', 108, 'confirmed', 210000, 5000000000, 34577, 0.000172885000000000, '2026-08-05 13:09:19', '2026-08-05 14:11:04'),
(21, 'gas', 'stake_purchase', 'ZZPA-ORDER-1785940875', 999999101, '0xzzpaadmin', '0xzzpauser', 'DRYRUN-gas-ZZPA-ORDER-1785940875', 162, 'pending', 21000, 5000000000, NULL, NULL, '2026-08-05 16:41:16', NULL),
(22, 'usdt', 'stake_purchase', 'ZZPA-ORDER-1785940875', 999999101, '0xzzpauser', '0xzzpaadmin', 'DRYRUN-usdt-ZZPA-ORDER-1785940875', 163, 'pending', 210000, 5000000000, NULL, NULL, '2026-08-05 16:41:16', NULL),
(23, 'bman', 'stake_purchase', 'ZZPA-ORDER-1785940875', 999999101, '0xzzpaadmin', '0xzzpauser', 'DRYRUN-bman-ZZPA-ORDER-1785940875', 164, 'pending', 210000, 5000000000, NULL, NULL, '2026-08-05 16:41:16', NULL),
(24, 'gas', 'stake_purchase', 'SWP-20260805-E96D54FE', 999999301, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xzzpdswapuser', 'DRYRUN-gas-SWP-20260805-E96D54FE', 171, 'pending', 21000, 5000000000, NULL, NULL, '2026-08-05 17:01:18', NULL),
(25, 'usdt', 'stake_purchase', 'SWP-20260805-E96D54FE', 999999301, '0xzzpdswapuser', '0x3088b858dc4cd85a001337f8e15a40b24666d321', 'DRYRUN-usdt-SWP-20260805-E96D54FE', 172, 'pending', 210000, 5000000000, NULL, NULL, '2026-08-05 17:01:18', NULL),
(26, 'bman', 'stake_purchase', 'SWP-20260805-E96D54FE', 999999301, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xzzpdswapuser', 'DRYRUN-bman-SWP-20260805-E96D54FE', 173, 'pending', 210000, 5000000000, NULL, NULL, '2026-08-05 17:01:18', NULL),
(27, 'gas', 'stake_purchase', 'SWP-20260806-9AB2A316', 999999504, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x7571092b8e7a2c76d335c70b7bd4805c92834055', 'DRYRUN-gas-SWP-20260806-9AB2A316', 204, 'pending', 21000, 5000000000, NULL, NULL, '2026-08-06 09:03:18', NULL),
(28, 'usdt', 'stake_purchase', 'SWP-20260806-9AB2A316', 999999504, '0x7571092b8e7a2c76d335c70b7bd4805c92834055', '0x3088b858dc4cd85a001337f8e15a40b24666d321', 'DRYRUN-usdt-SWP-20260806-9AB2A316', 205, 'pending', 210000, 5000000000, NULL, NULL, '2026-08-06 09:03:18', NULL),
(29, 'bman', 'stake_purchase', 'SWP-20260806-9AB2A316', 999999504, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x7571092b8e7a2c76d335c70b7bd4805c92834055', 'DRYRUN-bman-SWP-20260806-9AB2A316', 206, 'pending', 210000, 5000000000, NULL, NULL, '2026-08-06 09:03:18', NULL),
(30, 'gas', 'stake_purchase', 'SWP-20260806-C41648E7', 999999504, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x7571092b8e7a2c76d335c70b7bd4805c92834055', '0xc457998c9bea6ff2694e5d855b9889f45e5c40e2d6143ad490172a7b9c8da766', 213, 'confirmed', 21000, 5000000000, 21000, 0.000105000000000000, '2026-08-06 09:17:27', '2026-08-06 10:03:27'),
(31, 'usdt', 'stake_purchase', 'SWP-20260806-C41648E7', 999999504, '0x7571092b8e7a2c76d335c70b7bd4805c92834055', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x95379a6f728fff9c583ff7493285e933c4029c6435b6bf575aeb6e552f5a5246', 214, 'confirmed', 210000, 5000000000, 29691, 0.000148455000000000, '2026-08-06 09:20:22', '2026-08-06 10:03:25'),
(32, 'bman', 'stake_purchase', 'SWP-20260806-C41648E7', 999999504, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x7571092b8e7a2c76d335c70b7bd4805c92834055', '0xa8b8c91477c7b9a0b06a25610d5ca53010b1314ab7d1553adac9350f7ee62e3e', 215, 'confirmed', 210000, 5000000000, 51677, 0.000258385000000000, '2026-08-06 09:20:37', '2026-08-06 10:03:23'),
(33, 'gas', 'stake_purchase', 'SWP-20260806-D71FFF32', 999999602, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xf03f473f1ee2b5491a7564c18a99e327afd228ed', '0xf1e64958d15d90158f42697ac62e9d75857b6850715dce206f676b810f079d83', 224, 'pending', 21000, 5000000000, NULL, NULL, '2026-08-06 12:50:39', NULL),
(34, 'usdt', 'stake_purchase', 'SWP-20260806-D71FFF32', 999999602, '0xf03f473f1ee2b5491a7564c18a99e327afd228ed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x66d5d5890dafb0b261ed3bd275c8dec456b9e39feb6a702bc43d6fb6f5e2f5bc', 226, 'pending', 210000, 5000000000, NULL, NULL, '2026-08-06 12:51:37', NULL),
(35, 'bman', 'stake_purchase', 'SWP-20260806-D71FFF32', 999999602, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xf03f473f1ee2b5491a7564c18a99e327afd228ed', '0x1e3476353fc62bae0574f9c2d0511acb7a8097e373a9399ac0d3df8ef436631e', 227, 'pending', 210000, 5000000000, NULL, NULL, '2026-08-06 12:52:23', NULL),
(36, 'gas', 'stake_purchase', 'SWP-20260806-9ADDC0B2', 999999603, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x3cc69420a1359fb650ecc79367cf2732d9a77bd5', '0x727d2d1c488f23c712604c5e2685996e2b900d3a2e921b256fa205201073fdf8', 229, 'pending', 21000, 5000000000, NULL, NULL, '2026-08-06 12:52:25', NULL),
(37, 'usdt', 'stake_purchase', 'SWP-20260806-9ADDC0B2', 999999603, '0x3cc69420a1359fb650ecc79367cf2732d9a77bd5', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x90e41c25e85da6ffe007bdefed545e34f7e02816326133f98e558b19d56da443', 230, 'pending', 210000, 5000000000, NULL, NULL, '2026-08-06 12:55:32', NULL),
(38, 'bman', 'stake_purchase', 'SWP-20260806-9ADDC0B2', 999999603, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x3cc69420a1359fb650ecc79367cf2732d9a77bd5', '0x47e0747075058ceadf395bb1bbfc51a34e9163b2b177770a52bb2b5a07feab9a', 231, 'pending', 210000, 5000000000, NULL, NULL, '2026-08-06 12:56:29', NULL),
(39, 'gas', 'stake_purchase', 'SWP-20260806-0EBEEDFB', 999999604, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xfd96d05e54f137c196aaf81cce565d3061ecaa37', '0xcfca26db11a45936b6ba44eed46711ade4669a3b0ffdaccb844d05ebad873976', 249, 'pending', 21000, 5000000000, NULL, NULL, '2026-08-06 13:38:17', NULL),
(40, 'usdt', 'stake_purchase', 'SWP-20260806-0EBEEDFB', 999999604, '0xfd96d05e54f137c196aaf81cce565d3061ecaa37', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xf0fdd4277df4954b4f85e49950399e4b228f0d7017e53806b130bb4aa0ceab06', 250, 'pending', 210000, 5000000000, NULL, NULL, '2026-08-06 13:40:41', NULL),
(41, 'gas', 'stake_purchase', 'SWP-20260806-649E1E10', 999999607, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xf18948d95e2b8dee52a5816c48b02eb245c4fa1b', '0xd54d821263e8f4fdc17ba3f3e0e167d0108aecd530458f20e10fc38c4e28211b', 251, 'pending', 21000, 5000000000, NULL, NULL, '2026-08-06 13:40:44', NULL),
(42, 'bman', 'stake_purchase', 'SWP-20260806-0EBEEDFB', 999999604, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xfd96d05e54f137c196aaf81cce565d3061ecaa37', '0x5ba7c051a2b1a8af3331a5f00b4dee8796695ece5d8b3be4e4eccb125ee1c56d', 252, 'pending', 210000, 5000000000, NULL, NULL, '2026-08-06 13:41:19', NULL),
(43, 'usdt', 'stake_purchase', 'SWP-20260806-649E1E10', 999999607, '0xf18948d95e2b8dee52a5816c48b02eb245c4fa1b', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xceb4979692cc1379b4126dbeab82bdea51072afcfef33983571f0ed9f93b90f9', 254, 'pending', 210000, 5000000000, NULL, NULL, '2026-08-06 13:41:22', NULL),
(44, 'bman', 'stake_purchase', 'SWP-20260806-649E1E10', 999999607, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xf18948d95e2b8dee52a5816c48b02eb245c4fa1b', '0x88e71c7b8db3e36b861f08f6c68f6ebb2c8db2afa744486390bd06394829398e', 255, 'pending', 210000, 5000000000, NULL, NULL, '2026-08-06 13:42:01', NULL),
(45, 'gas', 'stake_purchase', 'SWP-20260806-7C3C3DDC', 999999608, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x77779986df95ebeae48f4c6a94be2886ea7a943c', '0x33f710208fa66c3134e98237fc5018eafd74462803b47033931c3010de5f3592', 259, 'pending', 21000, 5000000000, NULL, NULL, '2026-08-06 16:41:05', NULL),
(46, 'usdt', 'stake_purchase', 'SWP-20260806-7C3C3DDC', 999999608, '0x77779986df95ebeae48f4c6a94be2886ea7a943c', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xa1c529579f2f13639b085c418a2fcebc91d32f19a6e648fa28a4b201b4492016', 260, 'pending', 210000, 5000000000, NULL, NULL, '2026-08-06 16:45:05', NULL),
(47, 'bman', 'stake_purchase', 'SWP-20260806-7C3C3DDC', 999999608, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x77779986df95ebeae48f4c6a94be2886ea7a943c', '0xa0c873980ba96f706e3e5b4e652b533af7b48345adcc479969afed4f5fe66936', 261, 'pending', 210000, 5000000000, NULL, NULL, '2026-08-06 16:49:05', NULL),
(48, 'gas', 'stake_purchase', 'SWP-20260806-8AFA5518', 999999612, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x084b8f91a35c79c49157b095d61f4ab42910a093', '0x83c715b63a4b965ade82f6ab20352c6646644f86109b73c07f86c27617f0daf5', 271, 'pending', 21000, 5000000000, NULL, NULL, '2026-08-06 17:25:04', NULL),
(49, 'gas', 'stake_purchase', 'SWP-20260806-9BCD385A', 999999612, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x084b8f91a35c79c49157b095d61f4ab42910a093', '0x0ea2268901f0490ca795ecf9e83aff10fd1b61893929b206160b1d6cbfcd9d65', 272, 'pending', 21000, 5000000000, NULL, NULL, '2026-08-06 17:25:06', NULL),
(50, 'usdt', 'stake_purchase', 'SWP-20260806-8AFA5518', 999999612, '0x084b8f91a35c79c49157b095d61f4ab42910a093', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xd137832fc92051a12f5ff1672b334851c027476930745da25701f3a30b04d591', 273, 'pending', 210000, 5000000000, NULL, NULL, '2026-08-06 17:29:05', NULL),
(51, 'usdt', 'stake_purchase', 'SWP-20260806-9BCD385A', 999999612, '0x084b8f91a35c79c49157b095d61f4ab42910a093', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x86c0be581ec585352170a16bc68d733f3b8353aab312f203d0706fa9247ed0cd', 274, 'pending', 210000, 5000000000, NULL, NULL, '2026-08-06 17:29:08', NULL),
(52, 'bman', 'stake_purchase', 'SWP-20260806-8AFA5518', 999999612, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x084b8f91a35c79c49157b095d61f4ab42910a093', '0x55e0ad3b428365b6804d284df9fac96978264d52a65a69bc91e913ffefbbf90e', 275, 'pending', 210000, 5000000000, NULL, NULL, '2026-08-06 17:33:05', NULL),
(53, 'bman', 'stake_purchase', 'SWP-20260806-9BCD385A', 999999612, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x084b8f91a35c79c49157b095d61f4ab42910a093', '0x31b0d017ca0d861874e72322f687b55cd9d8f163633b2b154e6c057acd9bd052', 277, 'pending', 210000, 5000000000, NULL, NULL, '2026-08-06 17:33:08', NULL),
(54, 'gas', 'stake_purchase', 'SWP-20260807-E710F40C', 999999606, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x2e228070726ec09a6e0a9d89287f900b5dd2d3db', '0x9b5e624dde2b7b20e8a562ca4f36fc5beb4db2670638c34d17f55be57c609995', 325, 'pending', 21000, 5000000000, NULL, NULL, '2026-08-07 10:08:36', NULL),
(55, 'usdt', 'stake_purchase', 'SWP-20260807-E710F40C', 999999606, '0x2e228070726ec09a6e0a9d89287f900b5dd2d3db', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xd895ff06b6162b4957421fb7cfc0b88bb9e574c680fcacd51e91f1a553aa8b9d', 326, 'pending', 210000, 5000000000, NULL, NULL, '2026-08-07 10:09:08', NULL),
(56, 'bman', 'stake_purchase', 'SWP-20260807-E710F40C', 999999606, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x2e228070726ec09a6e0a9d89287f900b5dd2d3db', '0x4f46ebd6d76121a1ac3ee2e7a2e4643d2c0b0a92d9a3b4b722ad51decbe72eaf', 327, 'pending', 210000, 5000000000, NULL, NULL, '2026-08-07 10:11:49', NULL),
(57, 'gas', 'stake_purchase', 'SWP-20260807-EF6ABE55', 999999613, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x85519d7a4e94a070eceeee5e1763206c4d6665ff', '0x597a3e3958a6c7556d953bf6886ec355821535f3efbdf628bf3db1534c224212', 358, 'pending', 21000, 5000000000, NULL, NULL, '2026-08-07 14:43:45', NULL),
(58, 'usdt', 'stake_purchase', 'SWP-20260807-EF6ABE55', 999999613, '0x85519d7a4e94a070eceeee5e1763206c4d6665ff', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x7e77043a46e1b862fc51f2cddd468c19c62f0abc42e71e99984f08166983232c', 359, 'pending', 210000, 5000000000, NULL, NULL, '2026-08-07 14:44:03', NULL),
(59, 'bman', 'stake_purchase', 'SWP-20260807-EF6ABE55', 999999613, '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x85519d7a4e94a070eceeee5e1763206c4d6665ff', '0x50f86adb9f9f9fde6e1928d75ccc915be0fc182049a891665dd372af96cc5afb', 360, 'pending', 210000, 5000000000, NULL, NULL, '2026-08-07 14:44:26', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `gas_fee_settings`
--

CREATE TABLE `gas_fee_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `tx_type` varchar(40) NOT NULL,
  `gas_limit` bigint(20) NOT NULL,
  `gas_price_gwei` decimal(20,9) DEFAULT NULL,
  `buffer_multiplier` decimal(6,3) NOT NULL DEFAULT 1.500,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gas_fee_settings`
--

INSERT INTO `gas_fee_settings` (`id`, `tx_type`, `gas_limit`, `gas_price_gwei`, `buffer_multiplier`, `is_active`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'gas_funding', 21000, 5.000000000, 1.500, 1, NULL, '2026-08-05 15:49:40', NULL),
(2, 'token_transfer', 210000, 5.000000000, 1.500, 1, NULL, '2026-08-05 15:49:40', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `history`
--

CREATE TABLE `history` (
  `id` int(11) NOT NULL,
  `user_id` varchar(250) NOT NULL,
  `from_id` varchar(50) DEFAULT NULL,
  `leg` varchar(250) DEFAULT NULL,
  `invest_id` int(250) DEFAULT NULL,
  `type` varchar(250) DEFAULT NULL,
  `amount` varchar(250) NOT NULL DEFAULT '0',
  `status` varchar(250) NOT NULL DEFAULT '0',
  `method` varchar(150) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `description` varchar(250) DEFAULT NULL,
  `coin_type` int(11) NOT NULL DEFAULT 1,
  `hash_id` varchar(250) DEFAULT '0',
  `token_value` varchar(250) DEFAULT '0',
  `history_date` datetime DEFAULT NULL,
  `level_type` varchar(50) DEFAULT NULL,
  `level_count` varchar(50) DEFAULT NULL,
  `rank_type` varchar(250) DEFAULT NULL,
  `royality_received_by` varchar(250) DEFAULT '0',
  `token_amount` varchar(250) DEFAULT '0',
  `coin_id` varchar(250) DEFAULT NULL,
  `token_id` varchar(50) DEFAULT NULL,
  `total_left_invest` varchar(250) DEFAULT '0',
  `total_right_invest` varchar(250) DEFAULT '0',
  `total_left_roi` varchar(250) DEFAULT '0',
  `total_right_roi` varchar(250) DEFAULT '0',
  `total_left_users` text DEFAULT NULL,
  `total_right_users` text DEFAULT NULL,
  `total_left_invest_ids` text DEFAULT NULL,
  `total_right_invest_ids` text DEFAULT NULL,
  `transaction_id` varchar(250) DEFAULT NULL,
  `deductionFromSiteWallet` varchar(250) DEFAULT '0',
  `remainingAmount` varchar(250) DEFAULT '0',
  `deductionFromWallet` varchar(250) DEFAULT '0',
  `pair_ratio_used` varchar(250) DEFAULT '0',
  `pairs_count` varchar(250) DEFAULT '0',
  `basis` varchar(250) DEFAULT NULL,
  `ref_history_id` varchar(150) DEFAULT '0',
  `earn_by` varchar(250) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `invoice_no` varchar(40) NOT NULL,
  `invoice_date` datetime NOT NULL DEFAULT current_timestamp(),
  `bill_to` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bill_to`)),
  `ship_to` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ship_to`)),
  `currency` varchar(8) DEFAULT 'USD',
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `shipping_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `invoice_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `sku` varchar(120) DEFAULT NULL,
  `qty` int(10) UNSIGNED NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kyc_applications`
--

CREATE TABLE `kyc_applications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `country_iso2` char(2) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `dob` date NOT NULL,
  `gender` enum('male','female','other','unspecified') DEFAULT 'unspecified',
  `nationality_iso2` char(2) NOT NULL,
  `addr_line1` varchar(180) NOT NULL,
  `addr_line2` varchar(180) DEFAULT NULL,
  `addr_city` varchar(120) NOT NULL,
  `addr_region` varchar(120) DEFAULT NULL,
  `addr_postal` varchar(40) NOT NULL,
  `doc_type` enum('passport','national_id','driver_license') NOT NULL,
  `doc_number` varchar(80) NOT NULL,
  `doc_issue_country` char(2) NOT NULL,
  `doc_issue_date` date DEFAULT NULL,
  `doc_expiry_date` date DEFAULT NULL,
  `doc_front_url` varchar(255) DEFAULT NULL,
  `doc_back_url` varchar(255) DEFAULT NULL,
  `selfie_url` varchar(255) DEFAULT NULL,
  `proof_address_url` varchar(255) DEFAULT NULL,
  `is_pep` tinyint(1) NOT NULL DEFAULT 0,
  `consent` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pending','under_review','resubmitted','approved','rejected') NOT NULL DEFAULT 'pending',
  `review_notes` text DEFAULT NULL,
  `rejection_code` varchar(64) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `kyc_applications`
--

INSERT INTO `kyc_applications` (`id`, `user_id`, `country_iso2`, `full_name`, `dob`, `gender`, `nationality_iso2`, `addr_line1`, `addr_line2`, `addr_city`, `addr_region`, `addr_postal`, `doc_type`, `doc_number`, `doc_issue_country`, `doc_issue_date`, `doc_expiry_date`, `doc_front_url`, `doc_back_url`, `selfie_url`, `proof_address_url`, `is_pep`, `consent`, `status`, `review_notes`, `rejection_code`, `reviewed_by`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(1, 3, 'IN', 'trustwallet', '2000-07-20', 'male', 'IN', 'Address', NULL, 'City', 'State', '2749038', 'passport', '32469878', 'IN', '2026-07-13', '2026-07-21', 'https://darkseagreen-crocodile-999780.hostingersite.com/uploads/kyc/3/a39f9f2eec89cd88a2baf458517b50bd.jpg', 'https://darkseagreen-crocodile-999780.hostingersite.com/uploads/kyc/3/d255179fe5687ce359baf6f1baa26a23.jpg', 'https://darkseagreen-crocodile-999780.hostingersite.com/uploads/kyc/3/53edf7a1b8fa952c5b398327b76e98d4.jpg', NULL, 0, 1, 'approved', NULL, NULL, 1, '2026-07-20 19:01:58', '2026-07-20 22:31:29', '2026-07-22 20:21:02'),
(2, 4, 'IN', 'rightbytrustwallet last name', '1999-07-21', 'male', 'IN', 'A', 'A2', 'City', 'S', '234234', 'passport', '234798234', 'IN', '0000-00-00', '0000-00-00', 'https://darkseagreen-crocodile-999780.hostingersite.com/uploads/kyc/4/2f96521daa9e00aa9c60183a85a37dfd.jpg', 'https://darkseagreen-crocodile-999780.hostingersite.com/uploads/kyc/4/818adc4a98b2b3fd1f3f16f2e1203597.jpg', 'https://darkseagreen-crocodile-999780.hostingersite.com/uploads/kyc/4/1de65790744e2fdc8a2bf5ff0d0bcf44.jpg', NULL, 0, 1, 'approved', NULL, NULL, 1, '2026-08-06 17:11:03', '2026-07-21 12:46:56', '2026-08-06 17:11:03'),
(3, 999999612, 'IN', 'You Love', '1990-02-04', 'male', 'IN', '6/815 s v koil street, Sanjeevipuram, vanganoor via', NULL, 'THIRUVALLUR', 'Tamil Nadu', '631304', 'passport', '12345', 'IN', '0000-00-00', '0000-00-00', 'uploads/kyc/999999612/47056acb152c19a95a5529cb41c2c746.png', 'uploads/kyc/999999612/a10466d3f9b09a650e645d6259d86148.png', 'uploads/kyc/999999612/25245dce34af50a79aff679f83c699b0.png', NULL, 0, 1, 'approved', NULL, NULL, 1, '2026-08-06 17:11:15', '2026-08-06 17:09:49', '2026-08-06 17:11:15'),
(4, 999999611, 'IN', 'loveyou', '1990-08-06', 'male', 'IN', '6/815 s v koil street, Sanjeevipuram, vanganoor via', NULL, 'THIRUVALLUR', 'Tamil Nadu', '631304', 'national_id', '12345', 'IN', '0000-00-00', '0000-00-00', 'uploads/kyc/999999611/3ecd3ea88f2eb4a14682e8487e769411.png', 'uploads/kyc/999999611/1e57430b400e25c560aeb41aafd847fe.png', 'uploads/kyc/999999611/87005d525c565999a480cc36f1f600bc.png', NULL, 0, 1, 'approved', NULL, NULL, 1, '2026-08-06 17:42:20', '2026-08-06 17:41:52', '2026-08-06 17:42:20');

-- --------------------------------------------------------

--
-- Table structure for table `kyc_audit_logs`
--

CREATE TABLE `kyc_audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kyc_id` bigint(20) UNSIGNED NOT NULL,
  `actor_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(64) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kyc_audit_logs`
--

INSERT INTO `kyc_audit_logs` (`id`, `kyc_id`, `actor_user_id`, `action`, `notes`, `created_at`) VALUES
(1, 1, 3, 'submit', 'NOT_SUBMITTED -> PENDING (by user)', '2026-07-20 22:31:29'),
(2, 1, 1, 'start_review', 'PENDING -> UNDER_REVIEW | Under Review', '2026-07-20 22:31:53'),
(3, 1, 1, 'approve', 'UNDER_REVIEW -> APPROVED', '2026-07-20 22:31:58'),
(4, 2, 4, 'submit', 'NOT_SUBMITTED -> PENDING (by user)', '2026-07-21 12:46:56'),
(5, 3, 999999612, 'submit', 'NOT_SUBMITTED -> PENDING (by user)', '2026-08-06 17:09:49'),
(6, 3, 1, 'start_review', 'PENDING -> UNDER_REVIEW | under process', '2026-08-06 17:10:40'),
(7, 2, 1, 'start_review', 'PENDING -> UNDER_REVIEW | Under Process', '2026-08-06 17:10:53'),
(8, 2, 1, 'approve', 'UNDER_REVIEW -> APPROVED | Approved', '2026-08-06 17:11:03'),
(9, 3, 1, 'approve', 'UNDER_REVIEW -> APPROVED | Approved', '2026-08-06 17:11:15'),
(10, 4, 999999611, 'submit', 'NOT_SUBMITTED -> PENDING (by user)', '2026-08-06 17:41:52'),
(11, 4, 1, 'start_review', 'PENDING -> UNDER_REVIEW | under process', '2026-08-06 17:42:13'),
(12, 4, 1, 'approve', 'UNDER_REVIEW -> APPROVED', '2026-08-06 17:42:20');

-- --------------------------------------------------------

--
-- Table structure for table `landing_brands`
--

CREATE TABLE `landing_brands` (
  `id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `alt` varchar(120) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `landing_crypto`
--

CREATE TABLE `landing_crypto` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `highlight` varchar(150) DEFAULT NULL,
  `button_text` varchar(120) DEFAULT NULL,
  `button_link` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `landing_crypto`
--

INSERT INTO `landing_crypto` (`id`, `title`, `highlight`, `button_text`, `button_link`, `icon`, `sort_order`, `status`) VALUES
(1, 'Read our white paper', 'white paper', 'open whitepaper', '#', 'assets/img/icon/crypto_icon01.svg', 1, 1),
(2, '1 CRN token price 0.00014 BTC', '0.00014 BTC', 'Buy tokens (-25%)', '#', 'assets/img/icon/crypto_icon02.svg', 2, 1),
(3, 'ICO Participants 370,000+', '370,000+', 'join our telegram', '#', 'assets/img/icon/crypto_icon03.svg', 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `landing_exchange_logos`
--

CREATE TABLE `landing_exchange_logos` (
  `id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `landing_exchange_logos`
--

INSERT INTO `landing_exchange_logos` (`id`, `image`, `sort_order`, `status`) VALUES
(1, 'assets/img/icon/exchange_icon01.svg', 1, 1),
(2, 'assets/img/icon/exchange_icon02.svg', 2, 1),
(3, 'assets/img/icon/exchange_icon03.svg', 3, 1),
(4, 'assets/img/icon/exchange_icon04.svg', 4, 1);

-- --------------------------------------------------------

--
-- Table structure for table `landing_faq`
--

CREATE TABLE `landing_faq` (
  `id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `landing_faq`
--

INSERT INTO `landing_faq` (`id`, `question`, `answer`, `sort_order`, `status`) VALUES
(1, 'Main purpose of a cryptocurrency', 'The private key, stored securely in the wallet, allows you to sign transactions and prove ownership of the funds cryptocurrency wallet.', 1, 1),
(2, 'How can I make refund?', 'The private key, stored securely in the wallet, allows you to sign transactions and prove ownership of the funds cryptocurrency wallet.', 2, 1),
(3, 'How do they operate on blockchain?', 'The private key, stored securely in the wallet, allows you to sign transactions and prove ownership of the funds cryptocurrency wallet.', 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `landing_features`
--

CREATE TABLE `landing_features` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `highlight` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `landing_features`
--

INSERT INTO `landing_features` (`id`, `title`, `highlight`, `description`, `icon`, `sort_order`, `status`) VALUES
(1, 'Crypto management', 'management', 'Automated identity verification and anti-money', 'assets/img/icon/features_icon01.png', 1, 1),
(2, 'Crypto exchange', 'exchange', 'A built-in explorer to track transactions', 'assets/img/icon/features_icon02.png', 2, 1),
(3, 'Real-time data', 'data', 'Global reach with content available in multiple', 'assets/img/icon/features_icon03.png', 3, 1),
(4, 'Advanced trading', 'trading', 'Visual dashboards for trade performance', 'assets/img/icon/features_icon04.png', 4, 1),
(5, 'Blockchain compliance', 'compliance', 'Exportable reports for tax and accounting purposes', 'assets/img/icon/features_icon05.png', 5, 1);

-- --------------------------------------------------------

--
-- Table structure for table `landing_leads`
--

CREATE TABLE `landing_leads` (
  `id` int(11) NOT NULL,
  `name` varchar(150) DEFAULT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `source` varchar(80) DEFAULT NULL,
  `landing_page` varchar(120) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'new',
  `notes` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `landing_menu`
--

CREATE TABLE `landing_menu` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL DEFAULT 0,
  `title` varchar(120) NOT NULL,
  `url` varchar(255) NOT NULL DEFAULT '#',
  `new_tab` tinyint(1) NOT NULL DEFAULT 0,
  `is_external` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `landing_menu`
--

INSERT INTO `landing_menu` (`id`, `parent_id`, `title`, `url`, `new_tab`, `is_external`, `sort_order`, `status`) VALUES
(1, 0, 'Home', 'landing', 0, 0, 1, 1),
(2, 0, 'Login', 'user/in', 0, 0, 2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `landing_roadmap`
--

CREATE TABLE `landing_roadmap` (
  `id` int(11) NOT NULL,
  `year` varchar(20) DEFAULT NULL,
  `title` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `landing_roadmap`
--

INSERT INTO `landing_roadmap` (`id`, `year`, `title`, `description`, `icon`, `sort_order`, `status`) VALUES
(1, '2014', NULL, 'Definitions of key terms in cryptocurrency', 'assets/img/icon/roadmap_icon01.png', 1, 1),
(2, '2017', NULL, 'Automated tools for executing strategies', 'assets/img/icon/roadmap_icon02.png', 2, 1),
(3, '2022', NULL, 'APIs for developers to build custom tools', 'assets/img/icon/roadmap_icon03.png', 3, 1),
(4, '2026', NULL, 'A space for users to discuss trends', 'assets/img/icon/roadmap_icon04.png', 4, 1);

-- --------------------------------------------------------

--
-- Table structure for table `landing_settings`
--

CREATE TABLE `landing_settings` (
  `id` int(11) NOT NULL,
  `section` varchar(50) NOT NULL,
  `skey` varchar(80) NOT NULL,
  `svalue` longtext DEFAULT NULL,
  `is_publish` tinyint(1) NOT NULL DEFAULT 1,
  `update_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `landing_settings`
--

INSERT INTO `landing_settings` (`id`, `section`, `skey`, `svalue`, `is_publish`, `update_date`) VALUES
(1, 'general', 'site_name', 'NEXMAN', 1, '2026-06-30 16:32:47'),
(2, 'general', 'logo', 'assets/img/landing/lp_1782829967_lockup-color.png', 1, '2026-06-30 16:32:47'),
(3, 'general', 'logo_dark', 'assets/img/landing/lp_1782829967_lockup-white.png', 1, '2026-06-30 16:32:47'),
(4, 'general', 'favicon', 'assets/img/landing/lp_1782829967_favicon.svg', 1, '2026-06-30 16:32:47'),
(5, 'general', 'primary_color', '#FFC94A', 1, '2026-06-30 16:32:47'),
(6, 'general', 'secondary_color', '#6D4AFF', 1, '2026-06-30 16:32:47'),
(7, 'general', 'button_color', '#FFC94A', 1, '2026-06-30 16:32:47'),
(8, 'general', 'background_color', '#FFFFFF', 1, '2026-06-30 16:32:47'),
(9, 'general', 'font_family', 'Inter', 1, '2026-06-30 16:32:47'),
(10, 'general', 'enable_preloader', '1', 1, '2026-06-30 16:32:47'),
(11, 'general', 'enable_dark_mode', '1', 1, '2026-06-30 16:32:47'),
(12, 'general', 'copyright', 'Copyright & design by @ThemeAdapt - 2026', 1, '2026-06-30 16:32:47'),
(13, 'general', 'footer_text', 'Built on web3. Powered by You', 1, '2026-06-30 16:32:47'),
(14, 'header', 'logo', 'assets/img/landing/lp_1782904883_black_logo.png', 1, '2026-07-01 13:21:23'),
(15, 'header', 'mobile_logo', 'assets/img/landing/lp_1782904883_image42.png', 1, '2026-07-01 13:21:23'),
(16, 'header', 'buy_btn_text', 'Register', 1, '2026-07-01 13:21:23'),
(17, 'header', 'buy_btn_url', 'user/re', 1, '2026-07-01 13:21:23'),
(18, 'header', 'sticky_header', '1', 1, '2026-07-01 13:21:23'),
(19, 'header', 'transparent_header', '1', 1, '2026-07-01 13:21:23'),
(20, 'hero', 'small_title', 'Built on web3. Powered by You', 1, NULL),
(21, 'hero', 'main_title', 'The future of leverage is here', 1, NULL),
(22, 'hero', 'highlight_text', 'future', 1, NULL),
(23, 'hero', 'description', 'Leverage on any tokens with a protocol trusted with billions for its performance and reliability.', 1, NULL),
(24, 'hero', 'email_placeholder', 'Business email', 1, NULL),
(25, 'hero', 'button_text', 'get early access', 1, NULL),
(26, 'hero', 'button_link', '#', 1, NULL),
(27, 'hero', 'bottom_text', 'Start monitoring for free or', 1, NULL),
(28, 'hero', 'bottom_link_text', 'message us!', 1, NULL),
(29, 'hero', 'bottom_link', 'contact.html', 1, NULL),
(30, 'hero', 'bg_image', 'assets/img/banner/hero_bg.svg', 1, NULL),
(31, 'hero', 'hero_img1', 'assets/img/banner/hero_img01.png', 1, NULL),
(32, 'hero', 'hero_img2', 'assets/img/banner/hero_img02.png', 1, NULL),
(33, 'hero', 'hero_img3', 'assets/img/banner/hero_img03.png', 1, NULL),
(34, 'features', 'sub_title', 'accessible for everyone', 1, NULL),
(35, 'features', 'title', 'Crypto development accessible', 1, NULL),
(36, 'features', 'highlight', 'development', 1, NULL),
(37, 'marquee', 'text', 'You will hold the way you love Webzo', 1, NULL),
(38, 'marquee', 'speed', '50', 1, NULL),
(39, 'marquee', 'repeat', '2', 1, NULL),
(40, 'marquee', 'enable', '1', 1, NULL),
(41, 'token', 'sub_title', 'accessible for everyone', 1, NULL),
(42, 'token', 'title', 'Trading platform of the future!', 1, NULL),
(43, 'token', 'highlight', 'platform', 1, NULL),
(44, 'token', 'description', 'Webzi brings our love for cryptocurrency into Web3! Like a frog\'s leap, the chart can jump at any moment. Boom!', 1, NULL),
(45, 'token', 'button_text', 'purchase now', 1, NULL),
(46, 'token', 'button_link', '#', 1, NULL),
(47, 'token', 'countdown_date', '2026/12/30', 1, NULL),
(48, 'token', 'received_text', 'contribution received', 1, NULL),
(49, 'token', 'contribution_amount', '$49,222,300', 1, NULL),
(50, 'token', 'min_goal', '$5M', 1, NULL),
(51, 'token', 'max_goal', '$99M', 1, NULL),
(52, 'token', 'wallet_address', '0x2170Ed0880ac9A755fd29B2688956BD959F933F8', 1, NULL),
(53, 'token', 'progress_percentage', '50', 1, NULL),
(54, 'work', 'sub_title', 'how it works?', 1, NULL),
(55, 'work', 'title', 'Core asset of the crypto marketplace', 1, NULL),
(56, 'work', 'highlight', 'crypto', 1, NULL),
(57, 'work', 'image', 'assets/img/images/work_img.png', 1, NULL),
(58, 'exchange', 'title', 'Exchange availability', 1, NULL),
(59, 'exchange', 'highlight', 'availability', 1, NULL),
(60, 'exchange', 'description', 'AI-powered tools to detect and prevent fraudulent activities.', 1, NULL),
(61, 'exchange', 'main_image', 'assets/img/images/exchange_img.png', 1, NULL),
(62, 'exchange', 'enable', '1', 1, NULL),
(63, 'crypto', 'sub_title', 'crypto direction', 1, NULL),
(64, 'crypto', 'title', 'Goods & assets according to users interests.', 1, NULL),
(65, 'crypto', 'highlight', 'according', 1, NULL),
(66, 'faq', 'sub_title', 'faq & ans', 1, NULL),
(67, 'faq', 'title', 'Get every single answer', 1, NULL),
(68, 'faq', 'highlight', 'single', 1, NULL),
(69, 'faq', 'image', 'assets/img/images/faq_img.png', 1, NULL),
(70, 'roadmap', 'sub_title', 'roadmap', 1, NULL),
(71, 'roadmap', 'title', 'Our strategy & Planning', 1, NULL),
(72, 'roadmap', 'highlight', 'strategy', 1, NULL),
(73, 'team', 'sub_title', 'our avengers', 1, NULL),
(74, 'team', 'title', 'Meet with our avengers!', 1, NULL),
(75, 'team', 'highlight', 'our', 1, NULL),
(76, 'team', 'description', 'Webzi brings our love for cryptocurrency into Web3! Like a frog\'s leap, the chart can jump at any moment. Boom!', 1, NULL),
(77, 'footer', 'logo', 'assets/img/logo/logo.svg', 1, NULL),
(78, 'footer', 'sub_title', 'Built on web3. Powered by You', 1, NULL),
(79, 'footer', 'title', 'Join with our future of Webzo currency', 1, NULL),
(80, 'footer', 'highlight', 'future', 1, NULL),
(81, 'footer', 'copyright', 'Copyright & design by @ThemeAdapt - 2026', 1, NULL),
(82, 'footer', 'bg_image1', 'assets/img/images/footer_shape01.png', 1, NULL),
(83, 'footer', 'bg_image2', 'assets/img/images/footer_shape02.png', 1, NULL),
(84, 'seo', 'meta_title', 'Nexman Crypto Community', 1, '2026-07-10 07:52:11'),
(85, 'seo', 'meta_description', 'Nexman Software offers a powerful, user-friendly platform for network marketing businesses. Manage members, track commissions, grow your downline, and scale your business with ease. Start your MLM journey with Nexman today!', 1, '2026-07-10 07:52:11'),
(86, 'seo', 'meta_keywords', 'Nexman Software – Best Multi-Level Marketing Solution for Your Business', 1, '2026-07-10 07:52:11'),
(87, 'seo', 'og_image', 'assets/img/favicon.png', 1, NULL),
(88, 'seo', 'twitter_card', 'summary_large_image', 1, NULL),
(89, 'seo', 'robots', 'index, follow', 1, NULL),
(90, 'seo', 'canonical', '', 1, NULL),
(91, 'social', 'facebook', '#', 1, NULL),
(92, 'social', 'twitter', '#', 1, NULL),
(93, 'social', 'telegram', '#', 1, NULL),
(94, 'social', 'discord', '#', 1, NULL),
(95, 'social', 'instagram', '#', 1, NULL),
(96, 'social', 'linkedin', '#', 1, NULL),
(97, 'social', 'youtube', '#', 1, NULL),
(98, 'social', 'github', '', 1, NULL),
(99, 'scripts', 'header_scripts', '', 1, NULL),
(100, 'scripts', 'footer_scripts', '', 1, NULL),
(101, 'scripts', 'google_analytics', '', 1, NULL),
(102, 'scripts', 'facebook_pixel', '', 1, NULL),
(103, 'scripts', 'custom_css', '', 1, NULL),
(104, 'scripts', 'custom_js', '', 1, NULL),
(105, 'general', 'button_hover_color', '#6D4AFF', 1, '2026-06-30 16:32:47'),
(106, 'hero', 'success_message', 'Thank you! We will be in touch soon.', 1, NULL),
(211, 'general', 'theme_mode', 'light', 1, '2026-06-30 16:32:47');

-- --------------------------------------------------------

--
-- Table structure for table `landing_team`
--

CREATE TABLE `landing_team` (
  `id` int(11) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `position` varchar(120) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `telegram` varchar(255) DEFAULT NULL,
  `discord` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `landing_team`
--

INSERT INTO `landing_team` (`id`, `photo`, `name`, `position`, `facebook`, `twitter`, `telegram`, `discord`, `linkedin`, `sort_order`, `status`) VALUES
(1, 'assets/img/team/team_img01.png', 'Rosalina William', 'founder', '#', '', '', NULL, NULL, 1, 1),
(2, 'assets/img/team/team_img02.png', 'Alonso Dowson', 'ceo', '', '', '#', NULL, NULL, 2, 1),
(3, 'assets/img/team/team_img03.png', 'Elson Nelzoon', 'Designer', '', '#', '', NULL, NULL, 3, 1),
(4, 'assets/img/team/team_img04.png', 'Miranda Halim', 'developer', '', '', '#', NULL, NULL, 4, 1);

-- --------------------------------------------------------

--
-- Table structure for table `landing_versions`
--

CREATE TABLE `landing_versions` (
  `id` int(11) NOT NULL,
  `label` varchar(150) DEFAULT NULL,
  `snapshot` longtext NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `landing_work`
--

CREATE TABLE `landing_work` (
  `id` int(11) NOT NULL,
  `number` varchar(10) DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `highlight` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `landing_work`
--

INSERT INTO `landing_work` (`id`, `number`, `title`, `highlight`, `description`, `image`, `sort_order`, `status`) VALUES
(1, '01', 'Currency conversion', 'conversion', 'Exportable reports for tax and accounting purposes.', NULL, 1, 1),
(2, '02', 'Data encryption', 'encryption', 'Visual dashboards for trade performance.', NULL, 2, 1),
(3, '03', 'Cold wallet storage', 'storage', 'Regular updates on crypto trends and platform features.', NULL, 3, 1),
(4, '04', 'Transfer crypto & data', '& data', 'Guides for beginners on crypto basics and trading.', NULL, 4, 1);

-- --------------------------------------------------------

--
-- Table structure for table `member_bulk_upload_batches`
--

CREATE TABLE `member_bulk_upload_batches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ref` varchar(40) NOT NULL COMMENT 'MBU-YYYYMMDD-XXXXXXXX, shown to the admin',
  `admin_id` int(11) NOT NULL,
  `original_name` varchar(255) NOT NULL COMMENT 'file name as uploaded; the file itself is never stored',
  `status` enum('staged','importing','completed','failed','cancelled') NOT NULL DEFAULT 'staged',
  `total_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `valid_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `invalid_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `imported_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `failed_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `bman_queued` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'rows queued for the on-chain BMAN cron',
  `bman_total` decimal(30,8) NOT NULL DEFAULT 0.00000000 COMMENT 'sum of the queued BMAN amounts',
  `default_leg` enum('left','right','auto') NOT NULL DEFAULT 'auto' COMMENT 'used when a row has no leg of its own',
  `send_bman` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0 = import the members but ignore the bman column entirely',
  `wallet_type` enum('exchange','earning','staking','bonus') NOT NULL DEFAULT 'exchange' COMMENT 'Wallet type default at the time this batch was uploaded',
  `error_message` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `imported_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `member_bulk_upload_batches`
--

INSERT INTO `member_bulk_upload_batches` (`id`, `ref`, `admin_id`, `original_name`, `status`, `total_rows`, `valid_rows`, `invalid_rows`, `imported_rows`, `failed_rows`, `bman_queued`, `bman_total`, `default_leg`, `send_bman`, `wallet_type`, `error_message`, `created_at`, `imported_at`) VALUES
(8, 'MBU-20260729-A9155B2D', 1, 'member-bulk-upload-template (1).xlsx', 'staged', 3, 3, 0, 0, 0, 3, 10.50000000, 'auto', 1, 'exchange', NULL, '2026-07-29 20:23:20', NULL),
(11, 'MBU-20260730-1884D952', 1, 'member-bulk-upload-template (4).xlsx', 'staged', 3, 1, 2, 0, 0, 1, 1.00000000, 'auto', 1, 'bonus', NULL, '2026-07-30 15:14:12', NULL),
(12, 'MBU-20260730-61B9470B', 1, 'member-bulk-upload-template (4).xlsx', 'cancelled', 3, 3, 0, 0, 0, 3, 7.00000000, 'auto', 1, 'bonus', NULL, '2026-07-30 15:14:37', NULL),
(13, 'MBU-20260730-660810D7', 1, 'member-bulk-upload-template (4).xlsx', 'completed', 3, 2, 1, 2, 0, 2, 6.00000000, 'auto', 1, 'staking', NULL, '2026-07-30 16:18:55', '2026-07-30 13:40:28'),
(14, 'MBU-20260730-3C86FFAD', 1, 'member-bulk-upload-template (4).xlsx', 'staged', 3, 1, 2, 0, 0, 0, 0.00000000, 'auto', 1, 'staking', NULL, '2026-07-30 17:11:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `member_bulk_upload_rows`
--

CREATE TABLE `member_bulk_upload_rows` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `batch_id` bigint(20) UNSIGNED NOT NULL,
  `row_number` int(10) UNSIGNED NOT NULL COMMENT '1-based row number in the sheet, header excluded',
  `username` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL COMMENT 'bcrypt hash of the sheet/default password — never plaintext',
  `reference_id` varchar(250) DEFAULT NULL COMMENT 'sponsor referral code exactly as typed in the sheet',
  `sponsor_id` int(11) DEFAULT NULL COMMENT 'resolved users.id of that referral code',
  `leg` enum('left','right','auto') NOT NULL DEFAULT 'auto',
  `bman_amount` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `wallet_type` enum('exchange','earning','staking','bonus') NOT NULL DEFAULT 'exchange' COMMENT 'Effective wallet for this row (from sheet column, or batch default)',
  `status` enum('valid','invalid','imported','failed','skipped') NOT NULL DEFAULT 'valid',
  `error_message` varchar(255) DEFAULT NULL COMMENT 'why this row is invalid / failed to import',
  `user_id` int(11) DEFAULT NULL COMMENT 'users.id once created',
  `referral_id` varchar(250) DEFAULT NULL COMMENT 'the NEW member own referral code',
  `wallet_address` varchar(250) DEFAULT NULL COMMENT 'the address generated for this member',
  `bman_status` enum('none','pending','processing','completed','failed') NOT NULL DEFAULT 'none',
  `bman_attempts` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `bman_tx_hash` varchar(120) DEFAULT NULL,
  `bman_network` varchar(40) DEFAULT NULL,
  `bman_error` varchar(255) DEFAULT NULL,
  `bman_sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `bman_ledger_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'wallet_ledger.id of the Exchange credit',
  `bman_credited_at` datetime DEFAULT NULL COMMENT 'when the Exchange credit was posted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `member_bulk_upload_rows`
--

INSERT INTO `member_bulk_upload_rows` (`id`, `batch_id`, `row_number`, `username`, `email`, `password_hash`, `reference_id`, `sponsor_id`, `leg`, `bman_amount`, `wallet_type`, `status`, `error_message`, `user_id`, `referral_id`, `wallet_address`, `bman_status`, `bman_attempts`, `bman_tx_hash`, `bman_network`, `bman_error`, `bman_sent_at`, `created_at`, `bman_ledger_id`, `bman_credited_at`) VALUES
(28, 8, 1, 'john_doe', 'john@example.com', '$2y$10$uOEC2IwcEsvq.UzURZ.YZuWRJ0hNiyhNAyBlpYMyN9EcMpjfq/M9O', 'NEXMAN001', 1, 'auto', 3.00000000, 'exchange', 'valid', NULL, NULL, NULL, NULL, 'none', 0, NULL, NULL, NULL, NULL, '2026-07-29 20:23:20', NULL, NULL),
(29, 8, 2, 'jane_doe', 'jane@example.com', '$2y$10$JlQ4PwhwX1YB9aOJ1Ll4je9sHZMnWVYtiKL/zgSaKMRa5E8daa77.', 'NEXMAN001', 1, 'auto', 6.50000000, 'exchange', 'valid', NULL, NULL, NULL, NULL, 'none', 0, NULL, NULL, NULL, NULL, '2026-07-29 20:23:20', NULL, NULL),
(30, 8, 3, 'alex_roy', 'alex@example.com', '$2y$10$rYscf6bZwQsXaOKqbG4YE.dF.TJEHFxiskZ3Sj8OlAW2bk1f1p9NK', 'NEXMAN001', 1, 'auto', 1.00000000, 'exchange', 'valid', NULL, NULL, NULL, NULL, 'none', 0, NULL, NULL, NULL, NULL, '2026-07-29 20:23:20', NULL, NULL),
(33, 11, 1, 'SATZ', 'john@example.com', '$2y$10$TZfT8EGWwF/DsTovDFASQOvYQYCYMj5UNQUy2P1T.b5b2ARiRVR2O', 'NEXMAN001', 1, 'auto', 1.00000000, 'exchange', 'valid', NULL, NULL, NULL, NULL, 'none', 0, NULL, NULL, NULL, NULL, '2026-07-30 15:14:12', NULL, NULL),
(34, 11, 2, 'viki', 'jane@example.com', NULL, 'NEXMAN001', 1, 'auto', 2.00000000, 'earning', 'invalid', 'No password in the sheet and no default password given', NULL, NULL, NULL, 'none', 0, NULL, NULL, NULL, NULL, '2026-07-30 15:14:12', NULL, NULL),
(35, 11, 3, 'lak', 'alex@example.com', NULL, 'NEXMAN001', 1, 'auto', 4.00000000, 'bonus', 'invalid', 'No password in the sheet and no default password given', NULL, NULL, NULL, 'none', 0, NULL, NULL, NULL, NULL, '2026-07-30 15:14:12', NULL, NULL),
(36, 12, 1, 'SATZ', 'john@example.com', NULL, 'NEXMAN001', 1, 'auto', 1.00000000, 'exchange', 'valid', NULL, NULL, NULL, NULL, 'none', 0, NULL, NULL, NULL, NULL, '2026-07-30 15:14:37', NULL, NULL),
(37, 12, 2, 'viki', 'jane@example.com', NULL, 'NEXMAN001', 1, 'auto', 2.00000000, 'earning', 'valid', NULL, NULL, NULL, NULL, 'none', 0, NULL, NULL, NULL, NULL, '2026-07-30 15:14:37', NULL, NULL),
(38, 12, 3, 'lak', 'alex@example.com', NULL, 'NEXMAN001', 1, 'auto', 4.00000000, 'bonus', 'valid', NULL, NULL, NULL, NULL, 'none', 0, NULL, NULL, NULL, NULL, '2026-07-30 15:14:37', NULL, NULL),
(39, 13, 1, 'SATZ', 'john@example.com', NULL, 'NEXMAN001', 1, 'auto', 1.00000000, 'exchange', 'skipped', NULL, NULL, NULL, NULL, 'none', 0, NULL, NULL, NULL, NULL, '2026-07-30 16:18:55', NULL, NULL),
(40, 13, 2, 'viki', 'jane@example.com', NULL, 'NEXMAN001', 1, 'auto', 2.00000000, 'earning', 'imported', NULL, 22, 'NEXMAN240385', '0x513fD294ADdE5dD699cF0A556Fc15fF2521892aD', 'completed', 1, '0x818a8c51fcb4f798a13244fcc12a6c0d8e533ed0d3d0f80658a2440a178c6795', 'mainnet', NULL, '2026-08-06 17:12:04', '2026-07-30 16:18:55', 189, '2026-08-06 17:12:04'),
(41, 13, 3, 'lak', 'alex@example.com', NULL, 'NEXMAN001', 1, 'auto', 4.00000000, 'staking', 'imported', NULL, 23, 'NEXMAN428023', '0xb3A4C6e46049bE49CdB9734DbfB2897ade83Fe83', 'completed', 1, '0xd026c38d5242686fee31df0afd44f88c52b9dc0c2f95346cb15a4fe89a6db142', 'mainnet', NULL, '2026-08-06 17:12:05', '2026-07-30 16:18:55', 190, '2026-08-06 17:12:05'),
(42, 14, 1, 'SATZ', 'john@example.com', NULL, 'NEXMAN001', 1, 'auto', 1.00000000, 'exchange', 'valid', NULL, NULL, NULL, NULL, 'none', 0, NULL, NULL, NULL, NULL, '2026-07-30 17:11:08', NULL, NULL),
(43, 14, 2, 'viki', 'jane@example.com', NULL, 'NEXMAN001', 1, 'auto', 2.00000000, 'earning', 'invalid', 'Username already taken; Email already registered', NULL, NULL, NULL, 'none', 0, NULL, NULL, NULL, NULL, '2026-07-30 17:11:08', NULL, NULL),
(44, 14, 3, 'lak', 'alex@example.com', NULL, 'NEXMAN001', 1, 'auto', 4.00000000, 'staking', 'invalid', 'Username already taken; Email already registered', NULL, NULL, NULL, 'none', 0, NULL, NULL, NULL, NULL, '2026-07-30 17:11:08', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `member_bulk_upload_settings`
--

CREATE TABLE `member_bulk_upload_settings` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'master switch — 0 = the BMAN cron refuses to send',
  `dry_run` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = record a DRYRUN- hash, never broadcast',
  `min_treasury_reserve` decimal(30,8) NOT NULL DEFAULT 0.00000000 COMMENT 'refuses to send if it would drop the Treasury on-chain BMAN balance below this',
  `max_batch_size` int(10) UNSIGNED NOT NULL DEFAULT 20 COMMENT 'rows claimed per cron pass',
  `max_rows_per_file` int(10) UNSIGNED NOT NULL DEFAULT 1000 COMMENT 'guard against a runaway sheet',
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `credit_exchange_wallet` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = also post the delivered BMAN to the member Exchange wallet',
  `wallet_type` enum('exchange','earning','staking','bonus') NOT NULL DEFAULT 'exchange' COMMENT 'Default wallet that receives the BMAN credit for all bulk-upload rows'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `member_bulk_upload_settings`
--

INSERT INTO `member_bulk_upload_settings` (`id`, `enabled`, `dry_run`, `min_treasury_reserve`, `max_batch_size`, `max_rows_per_file`, `updated_by`, `updated_at`, `credit_exchange_wallet`, `wallet_type`) VALUES
(1, 1, 0, 0.00000000, 20, 1000, 1, '2026-07-29 21:07:14', 1, 'exchange');

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `onchain_transactions`
--

CREATE TABLE `onchain_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tx_hash` varchar(120) DEFAULT NULL,
  `network` varchar(20) NOT NULL DEFAULT 'mainnet',
  `chain_id` int(11) NOT NULL DEFAULT 56,
  `wallet_type` enum('usdt','exchange','earning','staking','bonus','gas','treasury') DEFAULT NULL,
  `tx_type` varchar(40) NOT NULL,
  `status` enum('pending','processing','confirmed','failed','reverted','partial','cancelled') NOT NULL DEFAULT 'pending',
  `from_address` varchar(120) DEFAULT NULL,
  `to_address` varchar(120) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `token_symbol` varchar(20) DEFAULT NULL,
  `token_name` varchar(80) DEFAULT NULL,
  `token_contract` varchar(120) DEFAULT NULL,
  `token_decimals` tinyint(3) UNSIGNED DEFAULT NULL,
  `amount` decimal(38,18) NOT NULL DEFAULT 0.000000000000000000,
  `block_number` bigint(20) UNSIGNED DEFAULT NULL,
  `block_timestamp` datetime DEFAULT NULL,
  `confirmation_count` int(11) DEFAULT NULL,
  `nonce` bigint(20) DEFAULT NULL,
  `tx_index` int(11) DEFAULT NULL,
  `gas_limit` bigint(20) DEFAULT NULL,
  `gas_used` bigint(20) DEFAULT NULL,
  `gas_price` decimal(38,0) DEFAULT NULL,
  `gas_price_gwei` decimal(38,9) GENERATED ALWAYS AS (`gas_price` / 1000000000) STORED,
  `max_fee_per_gas` decimal(38,0) DEFAULT NULL,
  `max_priority_fee` decimal(38,0) DEFAULT NULL,
  `gas_fee_total` decimal(38,18) DEFAULT NULL,
  `native_used` decimal(38,18) DEFAULT NULL,
  `estimated_gas` bigint(20) DEFAULT NULL,
  `gas_refund` bigint(20) DEFAULT NULL,
  `contract_address` varchar(120) DEFAULT NULL,
  `method_name` varchar(80) DEFAULT NULL,
  `method_signature` varchar(120) DEFAULT NULL,
  `input_data` mediumtext DEFAULT NULL,
  `return_data` text DEFAULT NULL,
  `debit_wallet` varchar(20) DEFAULT NULL,
  `credit_wallet` varchar(20) DEFAULT NULL,
  `balance_before` decimal(38,18) DEFAULT NULL,
  `balance_after` decimal(38,18) DEFAULT NULL,
  `wallet_ledger_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reference_type` varchar(40) DEFAULT NULL,
  `reference_id` varchar(64) DEFAULT NULL,
  `linked_deposit_id` bigint(20) UNSIGNED DEFAULT NULL,
  `linked_withdrawal_id` bigint(20) UNSIGNED DEFAULT NULL,
  `parent_tx_id` bigint(20) UNSIGNED DEFAULT NULL,
  `failure_reason` varchar(120) DEFAULT NULL,
  `revert_message` varchar(255) DEFAULT NULL,
  `completed_steps` varchar(255) DEFAULT NULL,
  `failed_steps` varchar(255) DEFAULT NULL,
  `retry_status` varchar(40) DEFAULT NULL,
  `retry_count` int(11) NOT NULL DEFAULT 0,
  `linked_retry_tx_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `processing_server` varchar(80) DEFAULT NULL,
  `processing_ms` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `request_tx_hash` varchar(120) DEFAULT NULL,
  `delivery_tx_hash` varchar(120) DEFAULT NULL,
  `last_verified_at` datetime DEFAULT NULL,
  `finalized_at` datetime DEFAULT NULL,
  `rpc_endpoint` varchar(255) DEFAULT NULL,
  `reorg_count` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `onchain_transactions`
--

INSERT INTO `onchain_transactions` (`id`, `tx_hash`, `network`, `chain_id`, `wallet_type`, `tx_type`, `status`, `from_address`, `to_address`, `user_id`, `admin_id`, `token_symbol`, `token_name`, `token_contract`, `token_decimals`, `amount`, `block_number`, `block_timestamp`, `confirmation_count`, `nonce`, `tx_index`, `gas_limit`, `gas_used`, `gas_price`, `max_fee_per_gas`, `max_priority_fee`, `gas_fee_total`, `native_used`, `estimated_gas`, `gas_refund`, `contract_address`, `method_name`, `method_signature`, `input_data`, `return_data`, `debit_wallet`, `credit_wallet`, `balance_before`, `balance_after`, `wallet_ledger_id`, `reference_type`, `reference_id`, `linked_deposit_id`, `linked_withdrawal_id`, `parent_tx_id`, `failure_reason`, `revert_message`, `completed_steps`, `failed_steps`, `retry_status`, `retry_count`, `linked_retry_tx_id`, `created_by`, `ip_address`, `processing_server`, `processing_ms`, `created_at`, `updated_at`, `request_tx_hash`, `delivery_tx_hash`, `last_verified_at`, `finalized_at`, `rpc_endpoint`, `reorg_count`) VALUES
(1, '0x29a05409254a504406c13e08426d455b6d8a8b058bcd9d92c7507ef8df2fca05', 'bsc', 56, 'usdt', 'deposit', 'confirmed', '0xb4f03059793be82a8f019774d1fb0fec5472ea1b', '0xcb3c5e2bcef54fec78974cae31828cf9f33da9c7', 1, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.200000000000000000, 111115777, '2026-07-20 15:33:16', 0, NULL, 67, NULL, 51603, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', 0.000000000000000000, 0.200000000000000000, 1, 'deposit', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-20 15:33:16', '2026-08-06 16:48:52', NULL, NULL, NULL, NULL, NULL, 0),
(2, '0x680529f21b8039c0bfccf9d3b7144c247d399bbfb690a8ee0ff95fa97b80096b', 'bsc', 56, 'usdt', 'deposit', 'confirmed', '0xb4f03059793be82a8f019774d1fb0fec5472ea1b', '0xcb3c5e2bcef54fec78974cae31828cf9f33da9c7', 1, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.200000000000000000, 110113718, '2026-07-15 10:15:26', 0, NULL, 56, NULL, 51603, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', 0.000000000000000000, 0.200000000000000000, 2, 'deposit', '2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-15 10:15:26', '2026-08-06 16:48:52', NULL, NULL, NULL, NULL, NULL, 0),
(3, '0x58500f3715c58ea34e4385363382b696338798669615022f7de9dfabe63c22d4', 'bsc', 56, 'usdt', 'deposit', 'confirmed', '0xb4f03059793be82a8f019774d1fb0fec5472ea1b', '0xcb3c5e2bcef54fec78974cae31828cf9f33da9c7', 1, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.100000000000000000, 108799765, '2026-07-08 13:58:06', 0, NULL, 105, NULL, 51603, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', 0.000000000000000000, 0.100000000000000000, 3, 'deposit', '3', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-08 13:58:06', '2026-08-06 16:48:53', NULL, NULL, NULL, NULL, NULL, 0),
(4, '0xa0b59d15308666c6a5c78c1be5e373f6ba9b6a057d170bc1d537e9e5faf96dc1', 'bsc', 56, 'usdt', 'deposit', 'confirmed', '0xb4f03059793be82a8f019774d1fb0fec5472ea1b', '0x18d0b930970f05abfc5cc08cad9346af58d3dd24', 3, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.200000000000000000, 111124029, '2026-07-20 18:35:11', 0, NULL, 47, NULL, 51603, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', 0.000000000000000000, 0.200000000000000000, 4, 'deposit', '4', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-20 18:35:11', '2026-08-06 08:23:12', NULL, NULL, NULL, NULL, NULL, 0),
(5, NULL, 'mainnet', 56, 'usdt', 'swap', 'confirmed', NULL, NULL, 3, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', NULL, NULL, 0.100000000000000000, 5, 'swap', 'SWP-20260720-82A2766D', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-20 18:46:29', '2026-07-20 22:16:29', NULL, NULL, NULL, NULL, NULL, 0),
(6, '0xf262579c4b551aa220c6c405e3340a8b2928556ca69a2c04e3d7f138d052c2a0', 'mainnet', 56, 'usdt', 'deposit', 'confirmed', '0x18d0b930970f05abfc5cc08cad9346af58d3dd24', '0x3088b858dc4cd85a001337f8e15a40b24666d321', 3, NULL, NULL, NULL, NULL, NULL, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1.200000000000000000, 1.300000000000000000, NULL, 'stake_purchase', 'SWP-20260720-82A2766D', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-20 18:47:33', '2026-07-29 17:18:20', NULL, NULL, NULL, NULL, NULL, 0),
(7, '0xb3e94ea802696e384d8450fa8de9c5cb680fece5e0d8fbe72d5009a648707bff', 'mainnet', 56, 'bonus', 'transfer', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x18d0b930970f05abfc5cc08cad9346af58d3dd24', 3, NULL, NULL, NULL, NULL, NULL, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260720-82A2766D', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-20 18:47:54', '2026-07-20 22:17:54', NULL, NULL, NULL, NULL, NULL, 0),
(8, '0x3d335923489fb747bb12aead4e973c7fc03bf03b212fbdee9e8fee5a49552bdc', 'mainnet', 56, 'exchange', 'transfer', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x18d0b930970f05abfc5cc08cad9346af58d3dd24', 3, NULL, NULL, NULL, NULL, NULL, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260720-82A2766D', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-20 18:47:56', '2026-07-20 22:17:56', NULL, NULL, NULL, NULL, NULL, 0),
(9, NULL, 'mainnet', 56, 'exchange', 'wallet_transfer', 'confirmed', NULL, NULL, 3, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, NULL, 0.000000000000000000, 8, 'wallet_transfer', 'WTS-20260720-30109EC8', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-20 19:02:46', '2026-07-20 22:32:46', NULL, NULL, NULL, NULL, NULL, 0),
(10, 'DRYRUN-WTS-20260720-30109EC8', 'mainnet', 56, 'exchange', 'wallet_transfer', 'confirmed', NULL, '0x6A3356EEC7660058122E4387AA61F8E1aE220A7f', 4, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 1.000000000000000000, 9, 'wallet_transfer', 'WTS-20260720-30109EC8', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-20 19:02:46', '2026-07-20 19:08:27', NULL, NULL, NULL, NULL, NULL, 0),
(11, '0xb3e94ea802696e384d8450fa8de9c5cb680fece5e0d8fbe72d5009a648707bff', 'mainnet', 56, 'exchange', 'deposit', 'confirmed', NULL, NULL, 3, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 0.250000000000000000, 10, 'deposit', '6', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-21 10:20:35', '2026-07-21 13:50:35', NULL, NULL, NULL, NULL, NULL, 0),
(12, '0xd315011a2c3b5496e2fc9e7976ae0d7f56b7f168520736f22e1180111e35b943', 'mainnet', 56, 'exchange', 'deposit', 'confirmed', NULL, NULL, 3, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 2.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 2.250000000000000000, 11, 'deposit', '7', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-21 10:20:35', '2026-07-21 13:50:35', NULL, NULL, NULL, NULL, NULL, 0),
(13, '0x39ae895242516de2dc9576a079244f3aaf760b411bbef7ac641f561f293d8a1c', 'mainnet', 56, 'exchange', 'deposit', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xcb3c5e2bcef54fec78974cae31828cf9f33da9c7', 1, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 1.000000000000000000, 12, 'deposit', '8', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-21 10:44:44', '2026-07-21 14:42:21', NULL, NULL, NULL, NULL, NULL, 0),
(14, '0x380739509750a561e3067d5515e3be53cf071289ffd0843bf92e5ae80a918ad5', 'mainnet', 56, 'exchange', 'deposit', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xcb3c5e2bcef54fec78974cae31828cf9f33da9c7', 1, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 1.250000000000000000, 13, 'deposit', '9', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-21 10:44:44', '2026-07-21 14:42:21', NULL, NULL, NULL, NULL, NULL, 0),
(15, '0x7d57370562c2d0eb7e653ad2eff05d0b2289adbb12160b2046c4c42fd97cbbb0', 'mainnet', 56, 'exchange', 'deposit', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xcb3c5e2bcef54fec78974cae31828cf9f33da9c7', 1, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 2.250000000000000000, 14, 'deposit', '10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-21 10:44:44', '2026-07-21 14:42:21', NULL, NULL, NULL, NULL, NULL, 0),
(16, '0x887bdd07dfbaed9d8e8e78e700c63759500a3e540cc4e10b1321a6d47480affa', 'mainnet', 56, 'exchange', 'deposit', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xcb3c5e2bcef54fec78974cae31828cf9f33da9c7', 1, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 2.500000000000000000, 15, 'deposit', '11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-21 10:44:44', '2026-07-21 14:42:21', NULL, NULL, NULL, NULL, NULL, 0),
(17, NULL, 'mainnet', 56, 'exchange', 'wallet_transfer', 'confirmed', NULL, NULL, 1, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.500000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, NULL, 2.000000000000000000, 16, 'wallet_transfer', 'WTS-20260721-2B2A19D7', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-21 16:12:50', '2026-07-21 19:42:50', NULL, NULL, NULL, NULL, NULL, 0),
(18, '0xb845667b4cc77a160974e81aee6064326ed99d2eeb19b321b2d85972dc2f9f3d', 'mainnet', 56, 'exchange', 'wallet_transfer', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x18d0B930970f05ABFC5CC08cad9346aF58D3dd24', 3, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.500000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 2.750000000000000000, 17, 'wallet_transfer', 'WTS-20260721-2B2A19D7', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-21 16:12:50', '2026-07-21 19:45:28', NULL, NULL, NULL, NULL, NULL, 0),
(19, '0xb845667b4cc77a160974e81aee6064326ed99d2eeb19b321b2d85972dc2f9f3d', 'mainnet', 56, 'exchange', 'deposit', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x18d0B930970f05ABFC5CC08cad9346aF58D3dd24', 3, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.500000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 3.250000000000000000, 18, 'deposit', '12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-21 16:15:28', '2026-07-21 19:45:28', NULL, NULL, NULL, NULL, NULL, 0),
(20, '0x3d9ca04e515d17571c68352db6474a18ceb1605d5a71701471246175ab710feb', 'mainnet', 56, 'exchange', 'deposit', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xcb3c5E2bcef54Fec78974CAe31828cF9F33dA9c7', 1, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 3.000000000000000000, 19, 'deposit', '13', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-23 07:06:03', '2026-07-23 10:36:03', NULL, NULL, NULL, NULL, NULL, 0),
(21, '0x058f6dd1923c52e56da2de73fbf67a89028b59f86c7bb4053e080791df2748e2', 'mainnet', 56, 'exchange', 'deposit', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xcb3c5E2bcef54Fec78974CAe31828cF9F33dA9c7', 1, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 3.250000000000000000, 20, 'deposit', '14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-23 07:06:03', '2026-07-23 10:36:03', NULL, NULL, NULL, NULL, NULL, 0),
(22, '0x78788087ce4ad691c3ab9c03cd708c9ce2c6f7103e8521defb2c4d727024ae9e', 'mainnet', 56, 'treasury', 'treasury_direct_send', 'confirmed', '0x3088B858dc4cD85A001337f8E15a40b24666d321', '0xcb3c5E2bcef54Fec78974CAe31828cF9F33dA9c7', 1, 1, 'BMAN', NULL, NULL, NULL, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'treasury_direct_send', 'TDS-20260723-991AC0DE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 1, NULL, NULL, NULL, '2026-07-23 13:34:44', '2026-08-05 17:44:35', NULL, NULL, NULL, NULL, NULL, 0),
(23, '0x99bd7e1ef6aaa2967ba89b3459d435a2023ffb48237c520eab14d1df156d9329', 'mainnet', 56, 'treasury', 'treasury_direct_send', 'confirmed', '0x3088B858dc4cD85A001337f8E15a40b24666d321', '0xcb3c5E2bcef54Fec78974CAe31828cF9F33dA9c7', 1, 1, 'BMAN', NULL, NULL, NULL, 2.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'treasury_direct_send', 'TDS-20260723-1EFF8B7A', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 1, NULL, NULL, NULL, '2026-07-23 13:36:39', '2026-08-05 17:44:35', NULL, NULL, NULL, NULL, NULL, 0),
(24, '0xa95dd6d48c6c1f0314ccb1c5f8286acb4f4b4b9a50bc9023f604a5200bc6c9d8', 'mainnet', 56, 'treasury', 'treasury_direct_send', 'confirmed', '0x3088B858dc4cD85A001337f8E15a40b24666d321', '0x365E2F051Cd601F8828cf33c3D1B7C87a0141c1b', 2, 1, 'BMAN', NULL, NULL, NULL, 2.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'treasury_direct_send', 'TDS-20260723-5A69BEDC', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 1, NULL, NULL, NULL, '2026-07-23 13:38:54', '2026-08-05 17:44:35', NULL, NULL, NULL, NULL, NULL, 0),
(25, NULL, 'mainnet', 56, 'exchange', 'wallet_transfer', 'confirmed', NULL, NULL, 1, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 2.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, NULL, 1.250000000000000000, 21, 'wallet_transfer', 'WTS-20260723-7C148395', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-23 13:46:38', '2026-07-23 17:16:38', NULL, NULL, NULL, NULL, NULL, 0),
(26, '0x3685efee9b922057db889d2a57677be80e4a0949ad5870bbda3680e82daf1fb4', 'mainnet', 56, 'exchange', 'wallet_transfer', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x365E2F051Cd601F8828cf33c3D1B7C87a0141c1b', 2, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 2.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 2.000000000000000000, 22, 'wallet_transfer', 'WTS-20260723-7C148395', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-23 13:46:38', '2026-07-30 18:00:17', NULL, NULL, NULL, NULL, NULL, 0),
(27, NULL, 'mainnet', 56, 'exchange', 'wallet_transfer', 'confirmed', NULL, NULL, 1, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, NULL, 0.250000000000000000, 23, 'wallet_transfer', 'WTS-20260723-898A1F3B', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-23 13:59:53', '2026-07-23 17:29:53', NULL, NULL, NULL, NULL, NULL, 0),
(28, NULL, 'mainnet', 56, 'bonus', 'wallet_transfer', 'confirmed', NULL, NULL, 1, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 1.000000000000000000, 24, 'wallet_transfer', 'WTS-20260723-898A1F3B', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-23 13:59:53', '2026-07-23 17:29:53', NULL, NULL, NULL, NULL, NULL, 0),
(29, NULL, 'mainnet', 56, 'usdt', 'swap', 'confirmed', NULL, NULL, 3, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', NULL, NULL, 0.000000000000000000, 25, 'swap', 'SWP-20260723-87AA1F16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-23 15:03:11', '2026-07-23 18:33:11', NULL, NULL, NULL, NULL, NULL, 0),
(30, '0xe4d367e51986bb953679b97502bcb774ca1af0650089f62fc4978995a2e9cb5f', 'mainnet', 56, 'usdt', 'deposit', 'confirmed', '0x18d0b930970f05abfc5cc08cad9346af58d3dd24', '0x3088b858dc4cd85a001337f8e15a40b24666d321', 3, NULL, NULL, NULL, NULL, NULL, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1.500000000000000000, 1.600000000000000000, NULL, 'stake_purchase', 'SWP-20260723-87AA1F16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-23 15:13:59', '2026-07-29 17:18:25', NULL, NULL, NULL, NULL, NULL, 0),
(31, '0xa288fb3468bacb651689e208b1f81f2454ffb1aa5868b72955ca24726a5cc819', 'mainnet', 56, 'bonus', 'transfer', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x18d0b930970f05abfc5cc08cad9346af58d3dd24', 3, NULL, NULL, NULL, NULL, NULL, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260723-87AA1F16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-23 15:14:37', '2026-07-23 18:44:37', NULL, NULL, NULL, NULL, NULL, 0),
(32, '0x16288134bf91c80eb4268982f128a9a2b89a3580865dce273565caf28b03baa5', 'mainnet', 56, 'exchange', 'transfer', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x18d0B930970f05ABFC5CC08cad9346aF58D3dd24', 3, NULL, NULL, NULL, NULL, NULL, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260723-87AA1F16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-23 15:14:40', '2026-07-29 19:06:38', NULL, NULL, NULL, NULL, NULL, 0),
(33, '0x16288134bf91c80eb4268982f128a9a2b89a3580865dce273565caf28b03baa5', 'mainnet', 56, 'earning', 'stake_purchase', 'confirmed', NULL, NULL, 3, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'earning', NULL, 0.100000000000000000, 28, 'stake_purchase', 'SWP-20260723-87AA1F16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-23 15:14:40', '2026-07-23 18:44:40', NULL, NULL, NULL, NULL, NULL, 0),
(34, '0x16288134bf91c80eb4268982f128a9a2b89a3580865dce273565caf28b03baa5', 'mainnet', 56, 'staking', 'stake_purchase', 'confirmed', NULL, NULL, 3, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'staking', NULL, 0.100000000000000000, 29, 'stake_purchase', 'SWP-20260723-87AA1F16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-23 15:14:40', '2026-07-23 18:44:40', NULL, NULL, NULL, NULL, NULL, 0),
(35, '0x16288134bf91c80eb4268982f128a9a2b89a3580865dce273565caf28b03baa5', 'mainnet', 56, 'bonus', 'stake_purchase', 'confirmed', NULL, NULL, 3, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 0.600000000000000000, 30, 'stake_purchase', 'SWP-20260723-87AA1F16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-23 15:14:40', '2026-07-23 18:44:40', NULL, NULL, NULL, NULL, NULL, 0),
(39, '0xad1f757e45aa052eff5628f31551a0bc049e14151b64239535f0237b4d56e3eb', 'bsc', 56, 'usdt', 'deposit', 'confirmed', '0xb4f03059793be82a8f019774d1fb0fec5472ea1b', '0x18d0b930970f05abfc5cc08cad9346af58d3dd24', 3, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.010000000000000000, 112826942, '2026-07-29 15:35:52', 0, NULL, 84, NULL, 51591, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', 0.000000000000000000, 0.010000000000000000, 31, 'deposit', '15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-29 15:35:52', '2026-08-06 08:23:12', NULL, NULL, NULL, NULL, NULL, 0),
(40, '0xa288fb3468bacb651689e208b1f81f2454ffb1aa5868b72955ca24726a5cc819', 'mainnet', 56, 'exchange', 'deposit', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x18d0B930970f05ABFC5CC08cad9346aF58D3dd24', 3, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 4.200000000000000000, 32, 'deposit', '17', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-29 15:36:38', '2026-07-29 19:06:38', NULL, NULL, NULL, NULL, NULL, 0),
(45, '0x05ef03ebcd04e686239682443a25adaa2a76c2911009e4ba4ae628671d16868c', 'bsc', 56, 'usdt', 'deposit', 'confirmed', '0xb4f03059793be82a8f019774d1fb0fec5472ea1b', '0x18d0b930970f05abfc5cc08cad9346af58d3dd24', 3, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.100000000000000000, 112840070, '2026-07-29 17:14:20', 0, NULL, 74, NULL, 34503, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', 0.010000000000000000, 0.110000000000000000, 35, 'deposit', '18', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-29 17:14:20', '2026-08-06 08:23:12', NULL, NULL, NULL, NULL, NULL, 0),
(46, NULL, 'mainnet', 56, 'usdt', 'swap', 'confirmed', NULL, NULL, 3, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', NULL, NULL, 0.010000000000000000, 36, 'swap', 'SWP-20260729-77163357', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-29 17:19:03', '2026-07-29 20:49:03', NULL, NULL, NULL, NULL, NULL, 0),
(47, '0x8ad440d70c911ed3a199257a088b4ecefc1207a2a3183eb86bb7cf1022aa3e19', 'mainnet', 56, 'usdt', 'deposit', 'confirmed', '0x18d0b930970f05abfc5cc08cad9346af58d3dd24', '0x3088b858dc4cd85a001337f8e15a40b24666d321', 3, NULL, NULL, NULL, NULL, NULL, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260729-77163357', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-29 17:24:14', '2026-07-29 20:54:14', NULL, NULL, NULL, NULL, NULL, 0),
(48, '0xe9f7f60185687ca5333d3b61374441f607638478c4689da7551a4a46430b997b', 'bsc', 56, 'usdt', 'deposit', 'confirmed', '0xb4f03059793be82a8f019774d1fb0fec5472ea1b', '0x365e2f051cd601f8828cf33c3d1b7c87a0141c1b', 2, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.200000000000000000, 113002130, '2026-07-30 11:30:23', 0, NULL, 85, NULL, 51603, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', 0.000000000000000000, 0.200000000000000000, 37, 'deposit', '19', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-30 11:30:23', '2026-08-06 16:47:33', NULL, NULL, NULL, NULL, NULL, 0),
(49, '0x3685efee9b922057db889d2a57677be80e4a0949ad5870bbda3680e82daf1fb4', 'mainnet', 56, 'exchange', 'deposit', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x365E2F051Cd601F8828cf33c3D1B7C87a0141c1b', 2, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 2.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 4.000000000000000000, 38, 'deposit', '20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-30 14:30:17', '2026-07-30 18:00:17', NULL, NULL, NULL, NULL, NULL, 0),
(50, '0xa95dd6d48c6c1f0314ccb1c5f8286acb4f4b4b9a50bc9023f604a5200bc6c9d8', 'mainnet', 56, 'exchange', 'deposit', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x365E2F051Cd601F8828cf33c3D1B7C87a0141c1b', 2, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 2.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 6.000000000000000000, 39, 'deposit', '21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-30 14:30:17', '2026-07-30 18:00:17', NULL, NULL, NULL, NULL, NULL, 0),
(51, NULL, 'mainnet', 56, 'usdt', 'swap', 'confirmed', NULL, NULL, 2, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.200000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', NULL, NULL, 0.000000000000000000, 40, 'swap', 'SWP-20260730-FC30455C', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-30 14:35:10', '2026-07-30 18:05:10', NULL, NULL, NULL, NULL, NULL, 0),
(52, '0x6c921516d4892b72ba9ac163544880a26e1d555d26b007b8bd7f03e59bcdccf3', 'mainnet', 56, 'bonus', 'transfer', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x18d0b930970f05abfc5cc08cad9346af58d3dd24', 3, NULL, NULL, NULL, NULL, NULL, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260729-77163357', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-30 14:36:06', '2026-07-30 18:06:06', NULL, NULL, NULL, NULL, NULL, 0),
(53, '0x87bf9ecc9161cd6a366f38d5c22a20693ff05c59676135bc02d1beb6fb7539f5', 'mainnet', 56, 'exchange', 'transfer', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x18d0b930970f05abfc5cc08cad9346af58d3dd24', 3, NULL, NULL, NULL, NULL, NULL, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260729-77163357', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-30 14:36:08', '2026-07-30 18:06:08', NULL, NULL, NULL, NULL, NULL, 0),
(54, '0x6a29a7b742f9e76eba33833cd199c47abce9ce0148b8948b21d519b6226fbf55', 'mainnet', 56, 'usdt', 'deposit', 'confirmed', '0x365e2f051cd601f8828cf33c3d1b7c87a0141c1b', '0x3088b858dc4cd85a001337f8e15a40b24666d321', 2, NULL, NULL, NULL, NULL, NULL, 0.200000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260730-FC30455C', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-30 14:36:27', '2026-07-30 18:06:27', NULL, NULL, NULL, NULL, NULL, 0),
(63, NULL, 'mainnet', 56, 'earning', 'admin_adjustment', 'confirmed', NULL, NULL, 4, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.500000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'earning', NULL, 0.500000000000000000, 51, 'admin_adjustment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 10:02:36', '2026-08-05 13:32:36', NULL, NULL, NULL, NULL, NULL, 0),
(64, NULL, 'mainnet', 56, 'staking', 'admin_adjustment', 'confirmed', NULL, NULL, 4, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.500000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'staking', NULL, 0.500000000000000000, 52, 'admin_adjustment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 10:02:36', '2026-08-05 13:32:36', NULL, NULL, NULL, NULL, NULL, 0),
(65, NULL, 'mainnet', 56, 'bonus', 'admin_adjustment', 'confirmed', NULL, NULL, 4, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.500000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 0.500000000000000000, 53, 'admin_adjustment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 10:02:36', '2026-08-05 13:32:36', NULL, NULL, NULL, NULL, NULL, 0),
(66, NULL, 'mainnet', 56, 'earning', 'admin_adjustment', 'confirmed', NULL, NULL, 4, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.500000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'earning', NULL, 0.500000000000000000, 54, 'admin_adjustment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 10:04:11', '2026-08-05 13:34:11', NULL, NULL, NULL, NULL, NULL, 0),
(67, NULL, 'mainnet', 56, 'staking', 'admin_adjustment', 'confirmed', NULL, NULL, 4, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.500000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'staking', NULL, 0.500000000000000000, 55, 'admin_adjustment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 10:04:11', '2026-08-05 13:34:11', NULL, NULL, NULL, NULL, NULL, 0),
(68, NULL, 'mainnet', 56, 'bonus', 'admin_adjustment', 'confirmed', NULL, NULL, 4, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.500000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 0.500000000000000000, 56, 'admin_adjustment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 10:04:11', '2026-08-05 13:34:11', NULL, NULL, NULL, NULL, NULL, 0),
(92, '0x82c6095c9913b6c413150e3397a25fbe5ac0f431e06702cb775630b16e48fcdd', 'bsc', 56, 'usdt', 'deposit', 'confirmed', '0x517b3f2aa149b2aee7fdc313eee0893870726808', '0xb3a4c6e46049be49cdb9734dbfb2897ade83fe83', 23, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.193539907624882841, 114142104, '2026-08-05 12:02:13', 0, NULL, 149, NULL, 46827, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', 0.000000000000000000, 0.193539907624882841, 84, 'deposit', '22', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 12:02:13', '2026-08-05 15:16:59', NULL, NULL, NULL, NULL, NULL, 0),
(93, '0x822111baa209be024a5a1140954d4e4eccb74653501fd81e61efba7398c7b70e', 'mainnet', 56, 'exchange', 'deposit', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xb3A4C6e46049bE49CdB9734DbfB2897ade83Fe83', 23, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 4.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 4.000000000000000000, 85, 'deposit', '23', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 12:05:04', '2026-08-05 15:35:04', NULL, NULL, NULL, NULL, NULL, 0),
(94, '0xf546aaab4e2b6cd7db39822e04569a084a87cd6707fa665d01a8e9b0773d4cbe', 'mainnet', 56, 'bonus', 'transfer', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x365e2f051cd601f8828cf33c3d1b7c87a0141c1b', 2, NULL, NULL, NULL, NULL, NULL, 0.500000000000000000, 113010940, NULL, 1148384, 45, 19, NULL, 34577, 5000000000, NULL, NULL, 0.000172885000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260730-FC30455C', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 12:29:12', '2026-08-05 17:41:21', NULL, NULL, '2026-08-05 14:11:21', '2026-08-05 14:11:21', 'https://bsc-dataseed.binance.org', 0),
(95, '0x84f0715ea64a2c26e34171a76e554eeacbea7d48f4c3a1271cbd3d5f3fc34614', 'mainnet', 56, 'exchange', 'transfer', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x365e2f051cd601f8828cf33c3d1b7c87a0141c1b', 2, NULL, NULL, NULL, NULL, NULL, 2.000000000000000000, 113010942, NULL, 1148375, 46, 79, NULL, 34577, 5000000000, NULL, NULL, 0.000172885000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260730-FC30455C', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 12:29:14', '2026-08-05 17:41:18', NULL, NULL, '2026-08-05 14:11:18', '2026-08-05 14:11:18', 'https://bsc-dataseed.binance.org', 0),
(96, '0x84f0715ea64a2c26e34171a76e554eeacbea7d48f4c3a1271cbd3d5f3fc34614', 'mainnet', 56, 'earning', 'stake_purchase', 'confirmed', NULL, NULL, 2, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.200000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'earning', NULL, 0.200000000000000000, 88, 'stake_purchase', 'SWP-20260730-FC30455C', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 12:29:14', '2026-08-05 15:59:14', NULL, NULL, NULL, NULL, NULL, 0),
(102, '0xdebc914bae7a119986abec153fe25e4ef0726479b630e76c4e5b291bc8bc2761', 'bsc', 56, 'usdt', 'deposit', 'confirmed', '0x71e038da10d1aab4925c683a140d72c115f1efe3', '0xb3a4c6e46049be49cdb9734dbfb2897ade83fe83', 23, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.300000000000000000, 114147705, '2026-08-05 12:44:14', 0, NULL, 9, NULL, 34503, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', 0.193539907624882841, 0.493539907624882841, 91, 'deposit', '24', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 12:44:14', '2026-08-05 15:16:59', NULL, NULL, NULL, NULL, NULL, 0),
(103, NULL, 'mainnet', 56, 'usdt', 'swap', 'confirmed', NULL, NULL, 23, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.200000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', NULL, NULL, 0.290000000000000000, 92, 'swap', 'SWP-20260805-48D6BAB1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 12:48:38', '2026-08-05 16:18:38', NULL, NULL, NULL, NULL, NULL, 0),
(104, '0x3e337247ec5b7002e4f77e10ae4e913df6d687b9381857e0952a0d4058f34b53', 'mainnet', 56, 'gas', 'gas_funding', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xb3a4c6e46049be49cdb9734dbfb2897ade83fe83', 23, NULL, NULL, NULL, NULL, NULL, 0.000315000000000000, 114148406, NULL, 10905, 50, 21, NULL, 21000, 5000000000, NULL, NULL, 0.000105000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260805-48D6BAB1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 12:49:49', '2026-08-05 17:44:35', NULL, NULL, '2026-08-05 14:11:15', '2026-08-05 14:11:15', 'https://bsc-dataseed.binance.org', 0),
(105, '0xb126bb3fd3bb1a1ed398a94a0270e8a4823a125a5889bd4c8b98010908ba01aa', 'mainnet', 56, 'gas', 'gas_funding', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xb3a4c6e46049be49cdb9734dbfb2897ade83fe83', 23, NULL, NULL, NULL, NULL, NULL, 0.003150000000000000, 114150476, NULL, 8828, 51, 33, NULL, 21000, 5000000000, NULL, NULL, 0.000105000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260805-48D6BAB1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 13:05:29', '2026-08-05 17:44:35', NULL, NULL, '2026-08-05 14:11:12', '2026-08-05 14:11:12', 'https://bsc-dataseed.binance.org', 0),
(106, '0xcea86e9eb3646fb4bb4ccf659d4539504a7376b7a8204161b434b3f40e289118', 'mainnet', 56, 'usdt', 'deposit', 'confirmed', '0xb3a4c6e46049be49cdb9734dbfb2897ade83fe83', '0x3088b858dc4cd85a001337f8e15a40b24666d321', 23, NULL, NULL, NULL, NULL, NULL, 0.200000000000000000, 114150547, NULL, 8751, 0, 14, NULL, 34491, 5000000000, NULL, NULL, 0.000172455000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260805-48D6BAB1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 13:09:16', '2026-08-05 17:41:09', NULL, NULL, '2026-08-05 14:11:09', '2026-08-05 14:11:09', 'https://bsc-dataseed.binance.org', 0),
(107, '0x47d6224d250d8912e7cbf270476a2fb726944bcc9680225c1250c67f3dacf727', 'mainnet', 56, 'bonus', 'transfer', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xb3a4c6e46049be49cdb9734dbfb2897ade83fe83', 23, NULL, NULL, NULL, NULL, NULL, 0.500000000000000000, 114151053, NULL, 8240, 52, 13, NULL, 34577, 5000000000, NULL, NULL, 0.000172885000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260805-48D6BAB1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 13:10:37', '2026-08-05 17:41:07', NULL, NULL, '2026-08-05 14:11:07', '2026-08-05 14:11:07', 'https://bsc-dataseed.binance.org', 0),
(108, '0x460fa827d11c69b34ab2f2ef10544e5b2ca101f5da28180d806c260c67199361', 'mainnet', 56, 'exchange', 'transfer', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xb3a4c6e46049be49cdb9734dbfb2897ade83fe83', 23, NULL, NULL, NULL, NULL, NULL, 2.000000000000000000, 114151056, NULL, 8230, 53, 63, NULL, 34577, 5000000000, NULL, NULL, 0.000172885000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260805-48D6BAB1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 13:10:39', '2026-08-05 17:41:04', NULL, NULL, '2026-08-05 14:11:04', '2026-08-05 14:11:04', 'https://bsc-dataseed.binance.org', 0),
(121, '0x22b80932c89e866614142e8bf5b7fc893189cc7bb45561c0b0d2afb7650d9df6', 'mainnet', 56, 'usdt', 'transfer_out', 'confirmed', '0xcb3c5e2bcef54fec78974cae31828cf9f33da9c7', '0x3088b858dc4cd85a001337f8e15a40b24666d321', 1, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.100000000000000000, 111323832, NULL, 2835563, NULL, NULL, NULL, 29691, 5000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0x22b80932c89e866614142e8bf5b7fc893189cc7bb45561c0b0d2afb7650d9d', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 14:11:56', '2026-08-05 17:41:56', NULL, NULL, NULL, NULL, NULL, 0),
(122, '0xd76c03cd5b0ea719d6158a32fe692843107f835f809990d8b6f7a33a19f9f4db', 'mainnet', 56, 'usdt', 'transfer_out', 'confirmed', '0xcb3c5e2bcef54fec78974cae31828cf9f33da9c7', '0x3088b858dc4cd85a001337f8e15a40b24666d321', 1, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.100000000000000000, 111323831, NULL, 2835564, NULL, NULL, NULL, 34491, 5000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0xd76c03cd5b0ea719d6158a32fe692843107f835f809990d8b6f7a33a19f9f4', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 14:11:56', '2026-08-05 17:41:56', NULL, NULL, NULL, NULL, NULL, 0),
(123, '0x29a05409254a504406c13e08426d455b6d8a8b058bcd9d92c7507ef8df2fca05', 'bsc', 56, 'usdt', 'deposit', 'confirmed', '0xb4f03059793be82a8f019774d1fb0fec5472ea1b', '0xcb3c5e2bcef54fec78974cae31828cf9f33da9c7', 1, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.200000000000000000, 111115777, '2026-07-20 15:33:16', 0, NULL, 67, NULL, 51603, 50000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0x29a05409254a504406c13e08426d455b6d8a8b058bcd9d92c7507ef8df2fca', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-20 15:33:16', '2026-08-06 16:48:52', NULL, NULL, NULL, NULL, NULL, 0),
(124, '0x1dee9af8b12d9db868c0cc67be67b48a9e53471c6314cd88a6ff344008a1e8fb', 'mainnet', 56, 'usdt', 'transfer_out', 'confirmed', '0xcb3c5e2bcef54fec78974cae31828cf9f33da9c7', '0x3088b858dc4cd85a001337f8e15a40b24666d321', 1, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.100000000000000000, 110666172, NULL, 3493223, NULL, NULL, NULL, 29691, 5000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0x1dee9af8b12d9db868c0cc67be67b48a9e53471c6314cd88a6ff344008a1e8', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 14:11:56', '2026-08-05 17:41:56', NULL, NULL, NULL, NULL, NULL, 0),
(125, '0x9c2950dd9f494971cacbcfc9459425d369d983eb1c5d147243fa2d6c56998b44', 'mainnet', 56, 'usdt', 'transfer_out', 'confirmed', '0xcb3c5e2bcef54fec78974cae31828cf9f33da9c7', '0x3088b858dc4cd85a001337f8e15a40b24666d321', 1, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.100000000000000000, 110115896, NULL, 4043499, NULL, NULL, NULL, 34491, 5000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0x9c2950dd9f494971cacbcfc9459425d369d983eb1c5d147243fa2d6c56998b', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 14:11:56', '2026-08-05 17:41:56', NULL, NULL, NULL, NULL, NULL, 0),
(126, '0x680529f21b8039c0bfccf9d3b7144c247d399bbfb690a8ee0ff95fa97b80096b', 'bsc', 56, 'usdt', 'deposit', 'confirmed', '0xb4f03059793be82a8f019774d1fb0fec5472ea1b', '0xcb3c5e2bcef54fec78974cae31828cf9f33da9c7', 1, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.200000000000000000, 110113718, '2026-07-15 10:15:26', 0, NULL, 56, NULL, 51603, 50000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0x680529f21b8039c0bfccf9d3b7144c247d399bbfb690a8ee0ff95fa97b8009', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-15 10:15:26', '2026-08-06 16:48:52', NULL, NULL, NULL, NULL, NULL, 0),
(127, '0x27ee4d5f4c5d6261fe4d3cc20d55cf04f85f5c837028b22f681fdcd67705934b', 'mainnet', 56, 'usdt', 'transfer_out', 'confirmed', '0xcb3c5e2bcef54fec78974cae31828cf9f33da9c7', '0x3088b858dc4cd85a001337f8e15a40b24666d321', 1, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.100000000000000000, 108807266, NULL, 5352129, NULL, NULL, NULL, 29691, 5000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0x27ee4d5f4c5d6261fe4d3cc20d55cf04f85f5c837028b22f681fdcd6770593', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 14:11:56', '2026-08-05 17:41:56', NULL, NULL, NULL, NULL, NULL, 0),
(128, '0x58500f3715c58ea34e4385363382b696338798669615022f7de9dfabe63c22d4', 'bsc', 56, 'usdt', 'deposit', 'confirmed', '0xb4f03059793be82a8f019774d1fb0fec5472ea1b', '0xcb3c5e2bcef54fec78974cae31828cf9f33da9c7', 1, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.100000000000000000, 108799765, '2026-07-08 13:58:06', 0, NULL, 105, NULL, 51603, 50000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0x58500f3715c58ea34e4385363382b696338798669615022f7de9dfabe63c22', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-08 13:58:06', '2026-08-06 16:48:53', NULL, NULL, NULL, NULL, NULL, 0),
(129, '0x6a29a7b742f9e76eba33833cd199c47abce9ce0148b8948b21d519b6226fbf55', 'mainnet', 56, 'usdt', 'transfer_out', 'confirmed', '0x365e2f051cd601f8828cf33c3d1b7c87a0141c1b', '0x3088b858dc4cd85a001337f8e15a40b24666d321', 2, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.200000000000000000, 113010914, NULL, 1148489, NULL, NULL, NULL, 29691, 5000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0x6a29a7b742f9e76eba33833cd199c47abce9ce0148b8948b21d519b6226fbf', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 14:12:02', '2026-08-05 17:42:02', NULL, NULL, NULL, NULL, NULL, 0),
(130, '0xe9f7f60185687ca5333d3b61374441f607638478c4689da7551a4a46430b997b', 'bsc', 56, 'usdt', 'deposit', 'confirmed', '0xb4f03059793be82a8f019774d1fb0fec5472ea1b', '0x365e2f051cd601f8828cf33c3d1b7c87a0141c1b', 2, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.200000000000000000, 113002130, '2026-07-30 11:30:23', 0, NULL, 85, NULL, 51603, 50000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0xe9f7f60185687ca5333d3b61374441f607638478c4689da7551a4a46430b99', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-30 11:30:23', '2026-08-06 16:47:33', NULL, NULL, NULL, NULL, NULL, 0),
(131, '0x8ad440d70c911ed3a199257a088b4ecefc1207a2a3183eb86bb7cf1022aa3e19', 'mainnet', 56, 'usdt', 'transfer_out', 'confirmed', '0x18d0b930970f05abfc5cc08cad9346af58d3dd24', '0x3088b858dc4cd85a001337f8e15a40b24666d321', 3, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.100000000000000000, 112841358, NULL, 1318060, NULL, NULL, NULL, 34491, 5000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0x8ad440d70c911ed3a199257a088b4ecefc1207a2a3183eb86bb7cf1022aa3e', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 14:12:05', '2026-08-05 17:42:05', NULL, NULL, NULL, NULL, NULL, 0);
INSERT INTO `onchain_transactions` (`id`, `tx_hash`, `network`, `chain_id`, `wallet_type`, `tx_type`, `status`, `from_address`, `to_address`, `user_id`, `admin_id`, `token_symbol`, `token_name`, `token_contract`, `token_decimals`, `amount`, `block_number`, `block_timestamp`, `confirmation_count`, `nonce`, `tx_index`, `gas_limit`, `gas_used`, `gas_price`, `max_fee_per_gas`, `max_priority_fee`, `gas_fee_total`, `native_used`, `estimated_gas`, `gas_refund`, `contract_address`, `method_name`, `method_signature`, `input_data`, `return_data`, `debit_wallet`, `credit_wallet`, `balance_before`, `balance_after`, `wallet_ledger_id`, `reference_type`, `reference_id`, `linked_deposit_id`, `linked_withdrawal_id`, `parent_tx_id`, `failure_reason`, `revert_message`, `completed_steps`, `failed_steps`, `retry_status`, `retry_count`, `linked_retry_tx_id`, `created_by`, `ip_address`, `processing_server`, `processing_ms`, `created_at`, `updated_at`, `request_tx_hash`, `delivery_tx_hash`, `last_verified_at`, `finalized_at`, `rpc_endpoint`, `reorg_count`) VALUES
(132, '0x05ef03ebcd04e686239682443a25adaa2a76c2911009e4ba4ae628671d16868c', 'bsc', 56, 'usdt', 'deposit', 'confirmed', '0xb4f03059793be82a8f019774d1fb0fec5472ea1b', '0x18d0b930970f05abfc5cc08cad9346af58d3dd24', 3, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.100000000000000000, 112840070, '2026-07-29 17:14:20', 0, NULL, 74, NULL, 34503, 50000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0x05ef03ebcd04e686239682443a25adaa2a76c2911009e4ba4ae628671d1686', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-29 17:14:20', '2026-08-06 08:23:12', NULL, NULL, NULL, NULL, NULL, 0),
(133, '0xad1f757e45aa052eff5628f31551a0bc049e14151b64239535f0237b4d56e3eb', 'bsc', 56, 'usdt', 'deposit', 'confirmed', '0xb4f03059793be82a8f019774d1fb0fec5472ea1b', '0x18d0b930970f05abfc5cc08cad9346af58d3dd24', 3, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.010000000000000000, 112826942, '2026-07-29 15:35:52', 0, NULL, 84, NULL, 51591, 50000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0xad1f757e45aa052eff5628f31551a0bc049e14151b64239535f0237b4d56e3', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-29 15:35:52', '2026-08-06 08:23:12', NULL, NULL, NULL, NULL, NULL, 0),
(134, '0xe4d367e51986bb953679b97502bcb774ca1af0650089f62fc4978995a2e9cb5f', 'mainnet', 56, 'usdt', 'transfer_out', 'confirmed', '0x18d0b930970f05abfc5cc08cad9346af58d3dd24', '0x3088b858dc4cd85a001337f8e15a40b24666d321', 3, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.100000000000000000, 111672682, NULL, 2486736, NULL, NULL, NULL, 29691, 5000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0xe4d367e51986bb953679b97502bcb774ca1af0650089f62fc4978995a2e9cb', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 14:12:05', '2026-08-05 17:42:05', NULL, NULL, NULL, NULL, NULL, 0),
(135, '0xf262579c4b551aa220c6c405e3340a8b2928556ca69a2c04e3d7f138d052c2a0', 'mainnet', 56, 'usdt', 'transfer_out', 'confirmed', '0x18d0b930970f05abfc5cc08cad9346af58d3dd24', '0x3088b858dc4cd85a001337f8e15a40b24666d321', 3, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.100000000000000000, 111125663, NULL, 3033755, NULL, NULL, NULL, 34491, 5000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0xf262579c4b551aa220c6c405e3340a8b2928556ca69a2c04e3d7f138d052c2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 14:12:05', '2026-08-05 17:42:05', NULL, NULL, NULL, NULL, NULL, 0),
(136, '0xa0b59d15308666c6a5c78c1be5e373f6ba9b6a057d170bc1d537e9e5faf96dc1', 'bsc', 56, 'usdt', 'deposit', 'confirmed', '0xb4f03059793be82a8f019774d1fb0fec5472ea1b', '0x18d0b930970f05abfc5cc08cad9346af58d3dd24', 3, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.200000000000000000, 111124029, '2026-07-20 18:35:11', 0, NULL, 47, NULL, 51603, 50000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0xa0b59d15308666c6a5c78c1be5e373f6ba9b6a057d170bc1d537e9e5faf96d', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-07-20 18:35:11', '2026-08-06 08:23:12', NULL, NULL, NULL, NULL, NULL, 0),
(137, '0xcea86e9eb3646fb4bb4ccf659d4539504a7376b7a8204161b434b3f40e289118', 'mainnet', 56, 'usdt', 'transfer_out', 'confirmed', '0xb3a4c6e46049be49cdb9734dbfb2897ade83fe83', '0x3088b858dc4cd85a001337f8e15a40b24666d321', 23, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.200000000000000000, 114150547, NULL, 8890, NULL, NULL, NULL, 34491, 5000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0xcea86e9eb3646fb4bb4ccf659d4539504a7376b7a8204161b434b3f40e2891', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 14:12:14', '2026-08-05 17:42:14', NULL, NULL, NULL, NULL, NULL, 0),
(138, '0xdebc914bae7a119986abec153fe25e4ef0726479b630e76c4e5b291bc8bc2761', 'bsc', 56, 'usdt', 'deposit', 'confirmed', '0x71e038da10d1aab4925c683a140d72c115f1efe3', '0xb3a4c6e46049be49cdb9734dbfb2897ade83fe83', 23, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.300000000000000000, 114147705, '2026-08-05 12:44:14', 0, NULL, 9, NULL, 34503, 60000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0xdebc914bae7a119986abec153fe25e4ef0726479b630e76c4e5b291bc8bc27', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 12:44:14', '2026-08-05 15:16:59', NULL, NULL, NULL, NULL, NULL, 0),
(139, '0x82c6095c9913b6c413150e3397a25fbe5ac0f431e06702cb775630b16e48fcdd', 'bsc', 56, 'usdt', 'deposit', 'confirmed', '0x517b3f2aa149b2aee7fdc313eee0893870726808', '0xb3a4c6e46049be49cdb9734dbfb2897ade83fe83', 23, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.193539907624882841, 114142104, '2026-08-05 12:02:13', 0, NULL, 149, NULL, 46827, 50000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0x82c6095c9913b6c413150e3397a25fbe5ac0f431e06702cb775630b16e48fc', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 12:02:13', '2026-08-05 15:16:59', NULL, NULL, NULL, NULL, NULL, 0),
(140, '0x8fc86dc185af9c4bb7d45cf290a2bc80f0b232bf40b6f21a1c05ecdd91772e29', 'mainnet', 56, 'usdt', 'deposit', 'confirmed', '0x428bf3b5bf040cb469558793e406f7ac98b034c6', '0x3088b858dc4cd85a001337f8e15a40b24666d321', NULL, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.100000000000000000, 110551459, NULL, 3607983, NULL, NULL, NULL, 29691, 5000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0x8fc86dc185af9c4bb7d45cf290a2bc80f0b232bf40b6f21a1c05ecdd91772e', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 14:12:17', '2026-08-05 17:42:17', NULL, NULL, NULL, NULL, NULL, 0),
(141, '0x7a6209944c1c30256698aa5263b5a03b136dff6f2f7e0ab567f935ee8975ab29', 'mainnet', 56, 'usdt', 'deposit', 'confirmed', '0xeb63d27d16fd25cf6fba72a9f140a9416b14b6d7', '0x3088b858dc4cd85a001337f8e15a40b24666d321', NULL, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.100000000000000000, 110529344, NULL, 3630098, NULL, NULL, NULL, 29691, 5000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0x7a6209944c1c30256698aa5263b5a03b136dff6f2f7e0ab567f935ee8975ab', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 14:12:17', '2026-08-05 17:42:17', NULL, NULL, NULL, NULL, NULL, 0),
(142, '0x9e83b928dc2a1e42f90a4c646ccc83ab12ab7c26e8a5f66c7767a566930c9d2b', 'mainnet', 56, 'usdt', 'deposit', 'confirmed', '0xfcaa7981721f4f5ea24b7c8b2dc8941a23cf16a7', '0x3088b858dc4cd85a001337f8e15a40b24666d321', NULL, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.100000000000000000, 110115917, NULL, 4043525, NULL, NULL, NULL, 29691, 5000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0x9e83b928dc2a1e42f90a4c646ccc83ab12ab7c26e8a5f66c7767a566930c9d', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 14:12:17', '2026-08-05 17:42:17', NULL, NULL, NULL, NULL, NULL, 0),
(143, '0xcb26b8e104643468b32b2ff4c44aa45ec66a5543a6230ed17796fe2c848c427a', 'mainnet', 56, 'usdt', 'deposit', 'confirmed', '0xe837d10560a2181c1c7431d11403d980633ae1ea', '0x3088b858dc4cd85a001337f8e15a40b24666d321', NULL, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.100000000000000000, 109931705, NULL, 4227737, NULL, NULL, NULL, 29691, 5000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0xcb26b8e104643468b32b2ff4c44aa45ec66a5543a6230ed17796fe2c848c42', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 14:12:17', '2026-08-05 17:42:17', NULL, NULL, NULL, NULL, NULL, 0),
(144, '0x3eb5a14594e15266fbcdf2e95ddce9d3489ce2624c7c66552a345dbdbc74d93e', 'mainnet', 56, 'usdt', 'deposit', 'confirmed', '0xe837d10560a2181c1c7431d11403d980633ae1ea', '0x3088b858dc4cd85a001337f8e15a40b24666d321', NULL, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.100000000000000000, 109204349, NULL, 4955093, NULL, NULL, NULL, 34491, 5000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0x3eb5a14594e15266fbcdf2e95ddce9d3489ce2624c7c66552a345dbdbc74d9', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 14:12:17', '2026-08-05 17:42:17', NULL, NULL, NULL, NULL, NULL, 0),
(145, '0x8a332d4121916ce93c61f9378fde64d5f66059d3a3e363df540627523bceb898', 'mainnet', 56, 'usdt', 'deposit', 'confirmed', '0xe837d10560a2181c1c7431d11403d980633ae1ea', '0x3088b858dc4cd85a001337f8e15a40b24666d321', NULL, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.100000000000000000, 109201762, NULL, 4957680, NULL, NULL, NULL, 34491, 5000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0x8a332d4121916ce93c61f9378fde64d5f66059d3a3e363df540627523bceb8', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 14:12:17', '2026-08-05 17:42:17', NULL, NULL, NULL, NULL, NULL, 0),
(146, '0x8499e505fe86c710d2e86058849ec3851462638343e5085f1f28262940f6e21c', 'mainnet', 56, 'usdt', 'deposit', 'confirmed', '0xe837d10560a2181c1c7431d11403d980633ae1ea', '0x3088b858dc4cd85a001337f8e15a40b24666d321', NULL, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.100000000000000000, 109200920, NULL, 4958522, NULL, NULL, NULL, 34491, 5000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0x8499e505fe86c710d2e86058849ec3851462638343e5085f1f28262940f6e2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 14:12:17', '2026-08-05 17:42:17', NULL, NULL, NULL, NULL, NULL, 0),
(147, '0x1b00a1d6b55e918787e932a18c199a85463e003def766e2825521939fec4a223', 'mainnet', 56, 'usdt', 'deposit', 'confirmed', '0x4c52c368703998ff2668c71a585500dcd8a850a8', '0x3088b858dc4cd85a001337f8e15a40b24666d321', NULL, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.100000000000000000, 107868408, NULL, 6291034, NULL, NULL, NULL, 34491, 5000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0x1b00a1d6b55e918787e932a18c199a85463e003def766e2825521939fec4a2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 14:12:17', '2026-08-05 17:42:17', NULL, NULL, NULL, NULL, NULL, 0),
(148, '0x6627e5259f540b5b7067d14e23a057daefc3593bbc2cb5bfbb9fc61466bd19f2', 'mainnet', 56, 'usdt', 'deposit', 'confirmed', '0x4c52c368703998ff2668c71a585500dcd8a850a8', '0x3088b858dc4cd85a001337f8e15a40b24666d321', NULL, NULL, 'BSC-USD', 'Binance-Peg BSC-USD', '0x55d398326f99059ff775485246999027b3197955', 18, 0.100000000000000000, 107866268, NULL, 6293174, NULL, NULL, NULL, 51591, 5000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'import', '0x6627e5259f540b5b7067d14e23a057daefc3593bbc2cb5bfbb9fc61466bd19', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 14:12:17', '2026-08-05 17:42:17', NULL, NULL, NULL, NULL, NULL, 0),
(159, NULL, 'mainnet', 56, 'earning', 'binary_matching', 'confirmed', NULL, NULL, 999999102, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.800000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'earning', NULL, 0.800000000000000000, 115, 'binary_matching', 'ZZPA-TEST-1785940875', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 16:41:15', '2026-08-05 20:11:15', NULL, NULL, NULL, NULL, NULL, 0),
(160, NULL, 'mainnet', 56, 'staking', 'binary_matching', 'confirmed', NULL, NULL, 999999102, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.200000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'staking', NULL, 0.200000000000000000, 116, 'binary_matching', 'ZZPA-TEST-1785940875', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 16:41:15', '2026-08-05 20:11:15', NULL, NULL, NULL, NULL, NULL, 0),
(161, NULL, 'mainnet', 56, 'earning', 'ceiling_release', 'confirmed', NULL, NULL, 999999101, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 5.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'earning', NULL, 5.000000000000000000, 117, 'ceiling_release', 'ZZPA-RELEASE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 16:41:15', '2026-08-05 20:11:15', NULL, NULL, NULL, NULL, NULL, 0),
(162, 'DRYRUN-gas-ZZPA-ORDER-1785940875', 'mainnet', 56, 'gas', 'gas_funding', 'processing', '0xzzpaadmin', '0xzzpauser', 999999101, NULL, NULL, NULL, NULL, NULL, 0.003150000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'ZZPA-ORDER-1785940875', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 16:41:16', '2026-08-05 20:11:16', NULL, NULL, NULL, NULL, NULL, 0),
(163, 'DRYRUN-usdt-ZZPA-ORDER-1785940875', 'mainnet', 56, 'usdt', 'deposit', 'processing', '0xzzpauser', '0xzzpaadmin', 999999101, NULL, NULL, NULL, NULL, NULL, 10.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'ZZPA-ORDER-1785940875', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 16:41:16', '2026-08-05 20:11:16', NULL, NULL, NULL, NULL, NULL, 0),
(164, 'DRYRUN-bman-ZZPA-ORDER-1785940875', 'mainnet', 56, 'exchange', 'transfer', 'processing', '0xzzpaadmin', '0xzzpauser', 999999101, NULL, NULL, NULL, NULL, NULL, 1.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'ZZPA-ORDER-1785940875', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 16:41:16', '2026-08-05 20:11:16', NULL, NULL, NULL, NULL, NULL, 0),
(165, 'DRYRUN-bman-ZZPA-ORDER-1785940875', 'mainnet', 56, 'bonus', 'stake_purchase', 'confirmed', NULL, NULL, 999999101, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 0.250000000000000000, 118, 'stake_purchase', 'ZZPA-ORDER-1785940875', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 16:41:16', '2026-08-05 20:11:16', NULL, NULL, NULL, NULL, NULL, 0),
(166, NULL, 'mainnet', 56, 'bonus', 'stake_purchase', 'confirmed', NULL, NULL, 999999201, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 3.500000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 3.500000000000000000, 120, 'stake_purchase', 'ZZPC-BONUS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 16:52:14', '2026-08-05 20:22:14', NULL, NULL, NULL, NULL, NULL, 0),
(167, NULL, 'mainnet', 56, 'staking', 'stake_purchase', 'confirmed', NULL, NULL, 999999201, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 100.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'staking', NULL, 100.000000000000000000, 121, 'stake_purchase', 'ZZPC-PRINCIPAL', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 16:52:14', '2026-08-05 20:22:14', NULL, NULL, NULL, NULL, NULL, 0),
(168, NULL, 'mainnet', 56, 'earning', 'stake_maturity', 'confirmed', NULL, NULL, 999999201, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 100.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'earning', NULL, 100.000000000000000000, 122, 'stake_maturity', 'ZZPC-RELEASE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 16:52:14', '2026-08-05 20:22:14', NULL, NULL, NULL, NULL, NULL, 0),
(169, NULL, 'mainnet', 56, 'usdt', 'admin_adjustment', 'confirmed', NULL, NULL, 999999301, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 10.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', NULL, 20.200000000000000000, 123, 'admin_adjustment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:01:16', '2026-08-05 20:31:16', NULL, NULL, NULL, NULL, NULL, 0),
(170, NULL, 'mainnet', 56, 'usdt', 'swap', 'confirmed', NULL, NULL, 999999301, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', NULL, NULL, 20.100000000000000000, 124, 'swap', 'SWP-20260805-E96D54FE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:01:17', '2026-08-05 20:31:17', NULL, NULL, NULL, NULL, NULL, 0),
(171, 'DRYRUN-gas-SWP-20260805-E96D54FE', 'mainnet', 56, 'gas', 'gas_funding', 'processing', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xzzpdswapuser', 999999301, NULL, NULL, NULL, NULL, NULL, 0.003150000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260805-E96D54FE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:01:18', '2026-08-05 20:31:18', NULL, NULL, NULL, NULL, NULL, 0),
(172, 'DRYRUN-usdt-SWP-20260805-E96D54FE', 'mainnet', 56, 'usdt', 'deposit', 'processing', '0xzzpdswapuser', '0x3088b858dc4cd85a001337f8e15a40b24666d321', 999999301, NULL, NULL, NULL, NULL, NULL, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260805-E96D54FE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:01:18', '2026-08-05 20:31:18', NULL, NULL, NULL, NULL, NULL, 0),
(173, 'DRYRUN-bman-SWP-20260805-E96D54FE', 'mainnet', 56, 'exchange', 'transfer', 'processing', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xzzpdswapuser', 999999301, NULL, NULL, NULL, NULL, NULL, 1.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260805-E96D54FE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:01:18', '2026-08-05 20:31:18', NULL, NULL, NULL, NULL, NULL, 0),
(174, 'DRYRUN-bman-SWP-20260805-E96D54FE', 'mainnet', 56, 'bonus', 'stake_purchase', 'confirmed', NULL, NULL, 999999301, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 0.250000000000000000, 125, 'stake_purchase', 'SWP-20260805-E96D54FE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:01:18', '2026-08-05 20:31:18', NULL, NULL, NULL, NULL, NULL, 0),
(175, 'DRYRUN-bman-SWP-20260805-E96D54FE', 'mainnet', 56, 'staking', 'stake_purchase', 'confirmed', NULL, NULL, 999999301, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'staking', NULL, 1.000000000000000000, 126, 'stake_purchase', 'SWP-20260805-E96D54FE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:01:18', '2026-08-05 20:31:18', NULL, NULL, NULL, NULL, NULL, 0),
(176, NULL, 'mainnet', 56, 'exchange', 'admin_adjustment', 'confirmed', NULL, NULL, 999999302, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 2.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 4.000000000000000000, 127, 'admin_adjustment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:01:20', '2026-08-05 20:31:20', NULL, NULL, NULL, NULL, NULL, 0),
(177, NULL, 'mainnet', 56, 'bonus', 'admin_adjustment', 'confirmed', NULL, NULL, 999999302, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 2.000000000000000000, 128, 'admin_adjustment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:01:20', '2026-08-05 20:31:20', NULL, NULL, NULL, NULL, NULL, 0),
(178, NULL, 'mainnet', 56, 'exchange', 'stake_purchase', 'confirmed', NULL, NULL, 999999302, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.900000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, NULL, 3.100000000000000000, 129, 'stake_purchase', 'RESTAKE-20260805-E09F3945', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:01:20', '2026-08-05 20:31:20', NULL, NULL, NULL, NULL, NULL, 0),
(179, NULL, 'mainnet', 56, 'bonus', 'stake_purchase', 'confirmed', NULL, NULL, 999999302, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, NULL, 1.900000000000000000, 130, 'stake_purchase', 'RESTAKE-20260805-E09F3945', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:01:20', '2026-08-05 20:31:20', NULL, NULL, NULL, NULL, NULL, 0),
(180, NULL, 'mainnet', 56, 'bonus', 'bonus', 'confirmed', NULL, NULL, 999999302, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 2.150000000000000000, 131, 'bonus', 'RESTAKE-20260805-E09F3945', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:01:20', '2026-08-05 20:31:20', NULL, NULL, NULL, NULL, NULL, 0),
(181, NULL, 'mainnet', 56, 'usdt', 'admin_adjustment', 'confirmed', NULL, NULL, 999999303, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 10.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', NULL, 20.200000000000000000, 132, 'admin_adjustment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:01:20', '2026-08-05 20:31:20', NULL, NULL, NULL, NULL, NULL, 0),
(182, NULL, 'mainnet', 56, 'usdt', 'stake_purchase', 'confirmed', NULL, NULL, 999999303, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', NULL, NULL, 20.100000000000000000, 133, 'stake_purchase', 'STK-20260805-3AB9B3D7', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:01:20', '2026-08-05 20:31:20', NULL, NULL, NULL, NULL, NULL, 0),
(183, NULL, 'mainnet', 56, 'staking', 'stake_purchase', 'confirmed', NULL, NULL, 999999303, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'staking', NULL, 1.000000000000000000, 134, 'stake_purchase', 'STK-20260805-3AB9B3D7', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:01:20', '2026-08-05 20:31:20', NULL, NULL, NULL, NULL, NULL, 0),
(184, NULL, 'mainnet', 56, 'bonus', 'bonus', 'confirmed', NULL, NULL, 999999303, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 0.250000000000000000, 135, 'bonus', 'STK-20260805-3AB9B3D7', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:01:20', '2026-08-05 20:31:20', NULL, NULL, NULL, NULL, NULL, 0),
(185, NULL, 'mainnet', 56, 'exchange', 'stake_purchase', 'confirmed', NULL, NULL, 999999401, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 1.000000000000000000, 136, 'stake_purchase', 'ZZPE-LEGACY', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:04:13', '2026-08-05 20:34:13', NULL, NULL, NULL, NULL, NULL, 0),
(186, 'DRYRUN-ROI-ZZPE-LEGACY-ROI-MATURITY', 'mainnet', 56, 'exchange', 'roi', 'confirmed', NULL, NULL, 999999401, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 1.100000000000000000, 137, 'roi', 'ZZPE-LEGACY-ROI', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:04:13', '2026-08-05 20:34:13', NULL, NULL, NULL, NULL, NULL, 0),
(187, 'DRYRUN-ROI-ZZPE-NEW-ROI-MATURITY', 'mainnet', 56, 'exchange', 'roi', 'confirmed', NULL, NULL, 999999402, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 0.100000000000000000, 138, 'roi', 'ZZPE-NEW-ROI', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:04:13', '2026-08-05 20:34:13', NULL, NULL, NULL, NULL, NULL, 0),
(188, NULL, 'mainnet', 56, 'earning', 'stake_maturity', 'confirmed', NULL, NULL, 999999402, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'earning', NULL, 1.000000000000000000, 139, 'stake_maturity', 'ZZPE-NEW-ROI', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:04:13', '2026-08-05 20:34:13', NULL, NULL, NULL, NULL, NULL, 0),
(189, 'ROI-ZZPE-NEW-ROI-PRINCIPAL', 'mainnet', 56, 'earning', 'principal_return', 'confirmed', NULL, NULL, 999999402, NULL, NULL, NULL, NULL, NULL, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'roi', 'ZZPE-NEW-ROI', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:04:13', '2026-08-05 20:34:13', NULL, NULL, NULL, NULL, NULL, 0),
(190, NULL, 'mainnet', 56, 'exchange', 'admin_adjustment', 'confirmed', NULL, NULL, 999999501, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 2.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 2.000000000000000000, 140, 'admin_adjustment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:05:59', '2026-08-05 20:35:59', NULL, NULL, NULL, NULL, NULL, 0),
(191, NULL, 'mainnet', 56, 'earning', 'admin_adjustment', 'confirmed', NULL, NULL, 999999501, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 2.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'earning', NULL, 2.000000000000000000, 141, 'admin_adjustment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:05:59', '2026-08-05 20:35:59', NULL, NULL, NULL, NULL, NULL, 0),
(192, NULL, 'mainnet', 56, 'exchange', 'stake_purchase', 'confirmed', NULL, NULL, 999999501, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.900000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, NULL, 1.100000000000000000, 142, 'stake_purchase', 'RESTAKE-20260805-0952DD01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:05:59', '2026-08-05 20:35:59', NULL, NULL, NULL, NULL, NULL, 0),
(193, NULL, 'mainnet', 56, 'earning', 'stake_purchase', 'confirmed', NULL, NULL, 999999501, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'earning', NULL, NULL, 1.900000000000000000, 143, 'stake_purchase', 'RESTAKE-20260805-0952DD01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:05:59', '2026-08-05 20:35:59', NULL, NULL, NULL, NULL, NULL, 0),
(194, NULL, 'mainnet', 56, 'bonus', 'bonus', 'confirmed', NULL, NULL, 999999501, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 0.250000000000000000, 144, 'bonus', 'RESTAKE-20260805-0952DD01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:05:59', '2026-08-05 20:35:59', NULL, NULL, NULL, NULL, NULL, 0),
(195, NULL, 'mainnet', 56, 'exchange', 'admin_adjustment', 'confirmed', NULL, NULL, 999999502, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 2.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 2.000000000000000000, 145, 'admin_adjustment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:05:59', '2026-08-05 20:35:59', NULL, NULL, NULL, NULL, NULL, 0),
(196, NULL, 'mainnet', 56, 'staking', 'admin_adjustment', 'confirmed', NULL, NULL, 999999502, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 2.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'staking', NULL, 2.000000000000000000, 146, 'admin_adjustment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:05:59', '2026-08-05 20:35:59', NULL, NULL, NULL, NULL, NULL, 0),
(197, NULL, 'mainnet', 56, 'exchange', 'stake_purchase', 'confirmed', NULL, NULL, 999999502, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.900000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, NULL, 1.100000000000000000, 147, 'stake_purchase', 'RESTAKE-20260805-C67B7733', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:05:59', '2026-08-05 20:35:59', NULL, NULL, NULL, NULL, NULL, 0),
(198, NULL, 'mainnet', 56, 'staking', 'stake_purchase', 'confirmed', NULL, NULL, 999999502, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'staking', NULL, NULL, 1.900000000000000000, 148, 'stake_purchase', 'RESTAKE-20260805-C67B7733', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:05:59', '2026-08-05 20:35:59', NULL, NULL, NULL, NULL, NULL, 0),
(199, NULL, 'mainnet', 56, 'bonus', 'bonus', 'confirmed', NULL, NULL, 999999502, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 0.250000000000000000, 149, 'bonus', 'RESTAKE-20260805-C67B7733', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:05:59', '2026-08-05 20:35:59', NULL, NULL, NULL, NULL, NULL, 0),
(200, 'DRYRUN-ROI-ZZF-MONTHLY-ROI-M1', 'mainnet', 56, 'exchange', 'roi_monthly', 'confirmed', NULL, NULL, 999999503, NULL, NULL, NULL, NULL, NULL, 0.050000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'roi', 'ZZF-MONTHLY-ROI-M1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-05 17:05:59', '2026-08-05 20:35:59', NULL, NULL, NULL, NULL, NULL, 0),
(242, '0x9f3bd944cb97cfeed2b37f767d395652677dc3dc74ce2a2a282c6d5f267a20b9', 'bsc', 56, NULL, 'deposit', 'confirmed', '0x71e038da10d1aab4925c683a140d72c115f1efe3', '0x7571092b8e7a2c76d335c70b7bd4805c92834055', 999999504, NULL, NULL, NULL, NULL, NULL, 0.100000000000000000, 114305294, '2026-08-06 08:28:28', 0, NULL, 36, NULL, 51603, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 08:28:28', '2026-08-06 13:30:41', NULL, NULL, NULL, NULL, NULL, 0),
(243, '0xd2c32ea63ab46d4c334983dbe4eba2c7e84d7b1306719390654c4545f2f83161', 'bsc', 56, NULL, 'deposit', 'confirmed', '0x71e038da10d1aab4925c683a140d72c115f1efe3', '0xf03f473f1ee2b5491a7564c18a99e327afd228ed', 999999602, NULL, NULL, NULL, NULL, NULL, 0.100000000000000000, 114337817, '2026-08-06 12:32:29', 0, NULL, 12, NULL, 51603, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.000000000000000000, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 12:32:29', '2026-08-07 15:00:04', NULL, NULL, NULL, NULL, NULL, 0),
(244, '0x1e3476353fc62bae0574f9c2d0511acb7a8097e373a9399ac0d3df8ef436631e', 'mainnet', 56, 'exchange', 'deposit', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xF03f473F1eE2B5491a7564c18A99e327afD228ed', 999999602, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 1.250000000000000000, 178, 'deposit', '29', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 13:31:32', '2026-08-06 17:01:32', NULL, NULL, NULL, NULL, NULL, 0),
(245, '0xd1980118c60eeb05c079bf658e816b2c423dd38149f354e8a5ab1b28da0e83e0', 'bsc', 56, 'usdt', 'deposit', 'confirmed', '0x71e038da10d1aab4925c683a140d72c115f1efe3', '0xfd96d05e54f137c196aaf81cce565d3061ecaa37', 999999604, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.100000000000000000, 114346386, '2026-08-06 13:36:47', 0, NULL, 14, NULL, 51603, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', 0.000000000000000000, 0.100000000000000000, 179, 'deposit', '30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 13:36:47', '2026-08-06 13:37:51', NULL, NULL, NULL, NULL, NULL, 0),
(246, NULL, 'mainnet', 56, 'usdt', 'swap', 'confirmed', NULL, NULL, 999999604, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', NULL, NULL, 0.000000000000000000, 180, 'swap', 'SWP-20260806-0EBEEDFB', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 13:37:54', '2026-08-06 17:07:54', NULL, NULL, NULL, NULL, NULL, 0),
(247, '0xf3d2c619e860969693929bd6511e40d08ece7d5c367345029a3b69a4a1350117', 'bsc', 56, 'usdt', 'deposit', 'confirmed', '0x71e038da10d1aab4925c683a140d72c115f1efe3', '0xf18948d95e2b8dee52a5816c48b02eb245c4fa1b', 999999607, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.200000000000000000, 114346802, '2026-08-06 13:39:54', 0, NULL, 10, NULL, 51603, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', 0.000000000000000000, 0.200000000000000000, 181, 'deposit', '31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 13:39:54', '2026-08-07 14:23:51', NULL, NULL, NULL, NULL, NULL, 0),
(248, NULL, 'mainnet', 56, 'usdt', 'swap', 'confirmed', NULL, NULL, 999999607, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.200000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', NULL, NULL, 0.000000000000000000, 182, 'swap', 'SWP-20260806-649E1E10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 13:40:30', '2026-08-06 17:10:30', NULL, NULL, NULL, NULL, NULL, 0),
(249, '0xcfca26db11a45936b6ba44eed46711ade4669a3b0ffdaccb844d05ebad873976', 'mainnet', 56, 'gas', 'gas_funding', 'processing', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xfd96d05e54f137c196aaf81cce565d3061ecaa37', 999999604, NULL, NULL, NULL, NULL, NULL, 0.003150000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260806-0EBEEDFB', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 13:40:40', '2026-08-06 17:10:40', NULL, NULL, NULL, NULL, NULL, 0),
(250, '0xf0fdd4277df4954b4f85e49950399e4b228f0d7017e53806b130bb4aa0ceab06', 'mainnet', 56, 'usdt', 'deposit', 'processing', '0xfd96d05e54f137c196aaf81cce565d3061ecaa37', '0x3088b858dc4cd85a001337f8e15a40b24666d321', 999999604, NULL, NULL, NULL, NULL, NULL, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260806-0EBEEDFB', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 13:41:17', '2026-08-06 17:11:17', NULL, NULL, NULL, NULL, NULL, 0),
(251, '0xd54d821263e8f4fdc17ba3f3e0e167d0108aecd530458f20e10fc38c4e28211b', 'mainnet', 56, 'gas', 'gas_funding', 'processing', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xf18948d95e2b8dee52a5816c48b02eb245c4fa1b', 999999607, NULL, NULL, NULL, NULL, NULL, 0.003150000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260806-649E1E10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 13:41:21', '2026-08-06 17:11:21', NULL, NULL, NULL, NULL, NULL, 0),
(252, '0x5ba7c051a2b1a8af3331a5f00b4dee8796695ece5d8b3be4e4eccb125ee1c56d', 'mainnet', 56, 'staking', 'transfer', 'processing', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xfd96d05e54f137c196aaf81cce565d3061ecaa37', 999999604, NULL, NULL, NULL, NULL, NULL, 1.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260806-0EBEEDFB', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 13:41:57', '2026-08-06 17:11:57', NULL, NULL, NULL, NULL, NULL, 0),
(253, '0x5ba7c051a2b1a8af3331a5f00b4dee8796695ece5d8b3be4e4eccb125ee1c56d', 'mainnet', 56, 'bonus', 'stake_purchase', 'confirmed', NULL, NULL, 999999604, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 0.250000000000000000, 183, 'stake_purchase', 'SWP-20260806-0EBEEDFB', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 13:41:57', '2026-08-06 17:11:57', NULL, NULL, NULL, NULL, NULL, 0),
(254, '0xceb4979692cc1379b4126dbeab82bdea51072afcfef33983571f0ed9f93b90f9', 'mainnet', 56, 'usdt', 'deposit', 'processing', '0xf18948d95e2b8dee52a5816c48b02eb245c4fa1b', '0x3088b858dc4cd85a001337f8e15a40b24666d321', 999999607, NULL, NULL, NULL, NULL, NULL, 0.200000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260806-649E1E10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 13:41:59', '2026-08-06 17:11:59', NULL, NULL, NULL, NULL, NULL, 0),
(255, '0x88e71c7b8db3e36b861f08f6c68f6ebb2c8db2afa744486390bd06394829398e', 'mainnet', 56, 'staking', 'transfer', 'processing', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xf18948d95e2b8dee52a5816c48b02eb245c4fa1b', 999999607, NULL, NULL, NULL, NULL, NULL, 2.500000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260806-649E1E10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 13:42:53', '2026-08-06 17:12:53', NULL, NULL, NULL, NULL, NULL, 0),
(256, '0x88e71c7b8db3e36b861f08f6c68f6ebb2c8db2afa744486390bd06394829398e', 'mainnet', 56, 'bonus', 'stake_purchase', 'confirmed', NULL, NULL, 999999607, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.500000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 0.500000000000000000, 184, 'stake_purchase', 'SWP-20260806-649E1E10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 13:42:53', '2026-08-06 17:12:53', NULL, NULL, NULL, NULL, NULL, 0),
(257, '0x9dda532b6cc1424ac65e3c675da414d66b4a57401f28e227b044472fe5d84aea', 'bsc', 56, 'usdt', 'deposit', 'confirmed', '0x71e038da10d1aab4925c683a140d72c115f1efe3', '0x77779986df95ebeae48f4c6a94be2886ea7a943c', 999999608, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.100000000000000000, 114386196, '2026-08-06 18:37:17', 0, NULL, 29, NULL, 51603, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', 0.000000000000000000, 0.100000000000000000, 185, 'deposit', '32', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 18:37:17', '2026-08-07 15:19:10', NULL, NULL, NULL, NULL, NULL, 0),
(258, NULL, 'mainnet', 56, 'usdt', 'swap', 'confirmed', NULL, NULL, 999999608, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', NULL, NULL, 0.000000000000000000, 186, 'swap', 'SWP-20260806-7C3C3DDC', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 16:38:14', '2026-08-06 16:38:14', NULL, NULL, NULL, NULL, NULL, 0),
(259, '0x33f710208fa66c3134e98237fc5018eafd74462803b47033931c3010de5f3592', 'mainnet', 56, 'gas', 'gas_funding', 'processing', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x77779986df95ebeae48f4c6a94be2886ea7a943c', 999999608, NULL, NULL, NULL, NULL, NULL, 0.003150000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260806-7C3C3DDC', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 16:45:04', '2026-08-06 16:45:04', NULL, NULL, NULL, NULL, NULL, 0),
(260, '0xa1c529579f2f13639b085c418a2fcebc91d32f19a6e648fa28a4b201b4492016', 'mainnet', 56, 'usdt', 'deposit', 'processing', '0x77779986df95ebeae48f4c6a94be2886ea7a943c', '0x3088b858dc4cd85a001337f8e15a40b24666d321', 999999608, NULL, NULL, NULL, NULL, NULL, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260806-7C3C3DDC', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 16:49:04', '2026-08-06 16:49:04', NULL, NULL, NULL, NULL, NULL, 0),
(261, '0xa0c873980ba96f706e3e5b4e652b533af7b48345adcc479969afed4f5fe66936', 'mainnet', 56, 'staking', 'transfer', 'processing', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x77779986df95ebeae48f4c6a94be2886ea7a943c', 999999608, NULL, NULL, NULL, NULL, NULL, 1.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260806-7C3C3DDC', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 16:53:04', '2026-08-06 16:53:04', NULL, NULL, NULL, NULL, NULL, 0),
(262, '0xa0c873980ba96f706e3e5b4e652b533af7b48345adcc479969afed4f5fe66936', 'mainnet', 56, 'bonus', 'stake_purchase', 'confirmed', NULL, NULL, 999999608, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 0.250000000000000000, 187, 'stake_purchase', 'SWP-20260806-7C3C3DDC', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 16:53:04', '2026-08-06 16:53:04', NULL, NULL, NULL, NULL, NULL, 0);
INSERT INTO `onchain_transactions` (`id`, `tx_hash`, `network`, `chain_id`, `wallet_type`, `tx_type`, `status`, `from_address`, `to_address`, `user_id`, `admin_id`, `token_symbol`, `token_name`, `token_contract`, `token_decimals`, `amount`, `block_number`, `block_timestamp`, `confirmation_count`, `nonce`, `tx_index`, `gas_limit`, `gas_used`, `gas_price`, `max_fee_per_gas`, `max_priority_fee`, `gas_fee_total`, `native_used`, `estimated_gas`, `gas_refund`, `contract_address`, `method_name`, `method_signature`, `input_data`, `return_data`, `debit_wallet`, `credit_wallet`, `balance_before`, `balance_after`, `wallet_ledger_id`, `reference_type`, `reference_id`, `linked_deposit_id`, `linked_withdrawal_id`, `parent_tx_id`, `failure_reason`, `revert_message`, `completed_steps`, `failed_steps`, `retry_status`, `retry_count`, `linked_retry_tx_id`, `created_by`, `ip_address`, `processing_server`, `processing_ms`, `created_at`, `updated_at`, `request_tx_hash`, `delivery_tx_hash`, `last_verified_at`, `finalized_at`, `rpc_endpoint`, `reorg_count`) VALUES
(263, '0x0fdb1e48cec88f3fd8b314f13683d05d8e57ce6c32c3028522e59d1158b72d38', 'mainnet', 56, 'exchange', 'deposit', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x084B8f91a35c79c49157b095d61F4Ab42910A093', 999999612, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 4.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 4.000000000000000000, 188, 'deposit', '33', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 17:05:45', '2026-08-06 17:05:45', NULL, NULL, NULL, NULL, NULL, 0),
(264, '0x818a8c51fcb4f798a13244fcc12a6c0d8e533ed0d3d0f80658a2440a178c6795', 'mainnet', 56, 'earning', 'admin_adjustment', 'confirmed', NULL, NULL, 22, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 2.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'earning', NULL, 2.000000000000000000, 189, 'admin_adjustment', 'MBU-20260730-660810D7', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 17:12:04', '2026-08-06 17:12:04', NULL, NULL, NULL, NULL, NULL, 0),
(265, '0xd026c38d5242686fee31df0afd44f88c52b9dc0c2f95346cb15a4fe89a6db142', 'mainnet', 56, 'staking', 'admin_adjustment', 'confirmed', NULL, NULL, 23, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 4.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'staking', NULL, 4.000000000000000000, 190, 'admin_adjustment', 'MBU-20260730-660810D7', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 17:12:05', '2026-08-06 17:12:05', NULL, NULL, NULL, NULL, NULL, 0),
(266, NULL, 'mainnet', 56, 'exchange', 'wallet_transfer', 'confirmed', NULL, NULL, 999999612, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, NULL, 3.000000000000000000, 191, 'wallet_transfer', 'WTS-20260806-465D66EE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 17:13:10', '2026-08-06 17:13:10', NULL, NULL, NULL, NULL, NULL, 0),
(267, NULL, 'mainnet', 56, 'bonus', 'wallet_transfer', 'confirmed', NULL, NULL, 999999612, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 1.000000000000000000, 192, 'wallet_transfer', 'WTS-20260806-465D66EE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 17:13:10', '2026-08-06 17:13:10', NULL, NULL, NULL, NULL, NULL, 0),
(268, '0x3d58397d37a7067a7f9382ec7e116e1474e444f220623638d5663482f34bbd1d', 'bsc', 56, 'usdt', 'deposit', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x084b8f91a35c79c49157b095d61f4ab42910a093', 999999612, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.300000000000000000, 114392185, '2026-08-06 19:22:12', 0, NULL, 17, NULL, 51603, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', 0.000000000000000000, 0.300000000000000000, 193, 'deposit', '34', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 19:22:12', '2026-08-07 09:59:30', NULL, NULL, NULL, NULL, NULL, 0),
(269, NULL, 'mainnet', 56, 'usdt', 'swap', 'confirmed', NULL, NULL, 999999612, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.200000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', NULL, NULL, 0.100000000000000000, 194, 'swap', 'SWP-20260806-8AFA5518', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 17:23:37', '2026-08-06 17:23:37', NULL, NULL, NULL, NULL, NULL, 0),
(270, NULL, 'mainnet', 56, 'usdt', 'swap', 'confirmed', NULL, NULL, 999999612, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', NULL, NULL, 0.000000000000000000, 195, 'swap', 'SWP-20260806-9BCD385A', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 17:24:48', '2026-08-06 17:24:48', NULL, NULL, NULL, NULL, NULL, 0),
(271, '0x83c715b63a4b965ade82f6ab20352c6646644f86109b73c07f86c27617f0daf5', 'mainnet', 56, 'gas', 'gas_funding', 'processing', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x084b8f91a35c79c49157b095d61f4ab42910a093', 999999612, NULL, NULL, NULL, NULL, NULL, 0.003150000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260806-8AFA5518', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 17:29:04', '2026-08-06 17:29:04', NULL, NULL, NULL, NULL, NULL, 0),
(272, '0x0ea2268901f0490ca795ecf9e83aff10fd1b61893929b206160b1d6cbfcd9d65', 'mainnet', 56, 'gas', 'gas_funding', 'processing', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x084b8f91a35c79c49157b095d61f4ab42910a093', 999999612, NULL, NULL, NULL, NULL, NULL, 0.003150000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260806-9BCD385A', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 17:29:07', '2026-08-06 17:29:07', NULL, NULL, NULL, NULL, NULL, 0),
(273, '0xd137832fc92051a12f5ff1672b334851c027476930745da25701f3a30b04d591', 'mainnet', 56, 'usdt', 'deposit', 'processing', '0x084b8f91a35c79c49157b095d61f4ab42910a093', '0x3088b858dc4cd85a001337f8e15a40b24666d321', 999999612, NULL, NULL, NULL, NULL, NULL, 0.200000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260806-8AFA5518', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 17:33:04', '2026-08-06 17:33:04', NULL, NULL, NULL, NULL, NULL, 0),
(274, '0x86c0be581ec585352170a16bc68d733f3b8353aab312f203d0706fa9247ed0cd', 'mainnet', 56, 'usdt', 'deposit', 'processing', '0x084b8f91a35c79c49157b095d61f4ab42910a093', '0x3088b858dc4cd85a001337f8e15a40b24666d321', 999999612, NULL, NULL, NULL, NULL, NULL, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260806-9BCD385A', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 17:33:07', '2026-08-06 17:33:07', NULL, NULL, NULL, NULL, NULL, 0),
(275, '0x55e0ad3b428365b6804d284df9fac96978264d52a65a69bc91e913ffefbbf90e', 'mainnet', 56, 'staking', 'transfer', 'processing', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x084b8f91a35c79c49157b095d61f4ab42910a093', 999999612, NULL, NULL, NULL, NULL, NULL, 2.500000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260806-8AFA5518', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 17:37:04', '2026-08-06 17:37:04', NULL, NULL, NULL, NULL, NULL, 0),
(276, '0x55e0ad3b428365b6804d284df9fac96978264d52a65a69bc91e913ffefbbf90e', 'mainnet', 56, 'bonus', 'stake_purchase', 'confirmed', NULL, NULL, 999999612, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.500000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 1.500000000000000000, 196, 'stake_purchase', 'SWP-20260806-8AFA5518', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 17:37:04', '2026-08-06 17:37:04', NULL, NULL, NULL, NULL, NULL, 0),
(277, '0x31b0d017ca0d861874e72322f687b55cd9d8f163633b2b154e6c057acd9bd052', 'mainnet', 56, 'staking', 'transfer', 'processing', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x084b8f91a35c79c49157b095d61f4ab42910a093', 999999612, NULL, NULL, NULL, NULL, NULL, 1.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260806-9BCD385A', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 17:37:06', '2026-08-06 17:37:06', NULL, NULL, NULL, NULL, NULL, 0),
(278, '0x31b0d017ca0d861874e72322f687b55cd9d8f163633b2b154e6c057acd9bd052', 'mainnet', 56, 'bonus', 'stake_purchase', 'confirmed', NULL, NULL, 999999612, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 1.750000000000000000, 197, 'stake_purchase', 'SWP-20260806-9BCD385A', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 17:37:06', '2026-08-06 17:37:06', NULL, NULL, NULL, NULL, NULL, 0),
(279, '0x7e27499ab4e4c63ce5d5430323462ed72c12ce07a63fdb4fb88a799e2f3d83ef', 'mainnet', 56, 'exchange', 'deposit', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xd85Ea024Be14032b7c25a04b017DB8Bf28f5da57', 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 4.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 4.000000000000000000, 198, 'deposit', '35', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 17:53:45', '2026-08-06 17:53:45', NULL, NULL, NULL, NULL, NULL, 0),
(280, '0xf8b298fa9fe11b9d45b0c24b7445e5bc03063e3bd75570504ea6266533b0ff15', 'mainnet', 56, 'exchange', 'deposit', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x084B8f91a35c79c49157b095d61F4Ab42910A093', 999999612, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 3.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 6.000000000000000000, 199, 'deposit', '36', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 18:17:10', '2026-08-06 18:17:10', NULL, NULL, NULL, NULL, NULL, 0),
(281, '0x31b0d017ca0d861874e72322f687b55cd9d8f163633b2b154e6c057acd9bd052', 'mainnet', 56, 'exchange', 'deposit', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x084B8f91a35c79c49157b095d61F4Ab42910A093', 999999612, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 7.250000000000000000, 200, 'deposit', '37', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 18:17:10', '2026-08-06 18:17:10', NULL, NULL, NULL, NULL, NULL, 0),
(282, '0x55e0ad3b428365b6804d284df9fac96978264d52a65a69bc91e913ffefbbf90e', 'mainnet', 56, 'exchange', 'deposit', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x084B8f91a35c79c49157b095d61F4Ab42910A093', 999999612, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 2.500000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 9.750000000000000000, 201, 'deposit', '38', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 18:17:10', '2026-08-06 18:17:10', NULL, NULL, NULL, NULL, NULL, 0),
(283, NULL, 'mainnet', 56, 'exchange', 'stake_purchase', 'confirmed', NULL, NULL, 999999612, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.900000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, NULL, 8.850000000000000000, 202, 'stake_purchase', 'RESTAKE-20260807-E5A1B087', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 07:35:03', '2026-08-07 11:05:03', NULL, NULL, NULL, NULL, NULL, 0),
(284, NULL, 'mainnet', 56, 'bonus', 'stake_purchase', 'confirmed', NULL, NULL, 999999612, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, NULL, 1.650000000000000000, 203, 'stake_purchase', 'RESTAKE-20260807-E5A1B087', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 07:35:03', '2026-08-07 11:05:03', NULL, NULL, NULL, NULL, NULL, 0),
(285, NULL, 'mainnet', 56, 'bonus', 'bonus', 'confirmed', NULL, NULL, 999999612, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 1.900000000000000000, 204, 'bonus', 'RESTAKE-20260807-E5A1B087', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 07:35:03', '2026-08-07 11:05:03', NULL, NULL, NULL, NULL, NULL, 0),
(286, NULL, 'mainnet', 56, 'exchange', 'stake_purchase', 'confirmed', NULL, NULL, 999999612, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.900000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, NULL, 7.950000000000000000, 205, 'stake_purchase', 'RESTAKE-20260807-CBFD0802', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:21:02', '2026-08-07 12:51:02', NULL, NULL, NULL, NULL, NULL, 0),
(287, NULL, 'mainnet', 56, 'bonus', 'stake_purchase', 'confirmed', NULL, NULL, 999999612, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, NULL, 1.800000000000000000, 206, 'stake_purchase', 'RESTAKE-20260807-CBFD0802', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:21:02', '2026-08-07 12:51:02', NULL, NULL, NULL, NULL, NULL, 0),
(288, NULL, 'mainnet', 56, 'bonus', 'bonus', 'confirmed', NULL, NULL, 999999612, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 2.050000000000000000, 207, 'bonus', 'RESTAKE-20260807-CBFD0802', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:21:02', '2026-08-07 12:51:02', NULL, NULL, NULL, NULL, NULL, 0),
(289, NULL, 'mainnet', 56, 'exchange', 'admin_adjustment', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 6.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 10.000000000000000000, 208, 'admin_adjustment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:34:36', '2026-08-07 13:04:36', NULL, NULL, NULL, NULL, NULL, 0),
(290, NULL, 'mainnet', 56, 'earning', 'admin_adjustment', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 2.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'earning', NULL, 2.000000000000000000, 209, 'admin_adjustment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:34:49', '2026-08-07 13:04:49', NULL, NULL, NULL, NULL, NULL, 0),
(291, NULL, 'mainnet', 56, 'staking', 'admin_adjustment', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 2.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'staking', NULL, 2.000000000000000000, 210, 'admin_adjustment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:34:54', '2026-08-07 13:04:54', NULL, NULL, NULL, NULL, NULL, 0),
(292, NULL, 'mainnet', 56, 'bonus', 'admin_adjustment', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 2.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 2.000000000000000000, 211, 'admin_adjustment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:35:01', '2026-08-07 13:05:01', NULL, NULL, NULL, NULL, NULL, 0),
(293, NULL, 'mainnet', 56, 'exchange', 'stake_purchase', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, NULL, 9.000000000000000000, 212, 'stake_purchase', 'RESTAKE-20260807-44759964', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:37:45', '2026-08-07 13:07:45', NULL, NULL, NULL, NULL, NULL, 0),
(294, NULL, 'mainnet', 56, 'bonus', 'bonus', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 2.250000000000000000, 213, 'bonus', 'RESTAKE-20260807-44759964', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:37:45', '2026-08-07 13:07:45', NULL, NULL, NULL, NULL, NULL, 0),
(295, NULL, 'mainnet', 56, 'exchange', 'stake_purchase', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.900000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, NULL, 8.100000000000000000, 214, 'stake_purchase', 'RESTAKE-20260807-B4075CC7', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:38:11', '2026-08-07 13:08:11', NULL, NULL, NULL, NULL, NULL, 0),
(296, NULL, 'mainnet', 56, 'bonus', 'stake_purchase', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, NULL, 2.150000000000000000, 215, 'stake_purchase', 'RESTAKE-20260807-B4075CC7', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:38:11', '2026-08-07 13:08:11', NULL, NULL, NULL, NULL, NULL, 0),
(297, NULL, 'mainnet', 56, 'bonus', 'bonus', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 2.400000000000000000, 216, 'bonus', 'RESTAKE-20260807-B4075CC7', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:38:11', '2026-08-07 13:08:11', NULL, NULL, NULL, NULL, NULL, 0),
(298, NULL, 'mainnet', 56, 'exchange', 'stake_purchase', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.800000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, NULL, 7.300000000000000000, 217, 'stake_purchase', 'RESTAKE-20260807-40E73E44', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:38:38', '2026-08-07 13:08:38', NULL, NULL, NULL, NULL, NULL, 0),
(299, NULL, 'mainnet', 56, 'earning', 'stake_purchase', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'earning', NULL, NULL, 1.900000000000000000, 218, 'stake_purchase', 'RESTAKE-20260807-40E73E44', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:38:38', '2026-08-07 13:08:38', NULL, NULL, NULL, NULL, NULL, 0),
(300, NULL, 'mainnet', 56, 'bonus', 'stake_purchase', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, NULL, 2.300000000000000000, 219, 'stake_purchase', 'RESTAKE-20260807-40E73E44', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:38:38', '2026-08-07 13:08:38', NULL, NULL, NULL, NULL, NULL, 0),
(301, NULL, 'mainnet', 56, 'bonus', 'bonus', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 2.550000000000000000, 220, 'bonus', 'RESTAKE-20260807-40E73E44', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:38:38', '2026-08-07 13:08:38', NULL, NULL, NULL, NULL, NULL, 0),
(302, NULL, 'mainnet', 56, 'exchange', 'stake_purchase', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.800000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, NULL, 6.500000000000000000, 221, 'stake_purchase', 'RESTAKE-20260807-189C7B04', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:39:00', '2026-08-07 13:09:00', NULL, NULL, NULL, NULL, NULL, 0),
(303, NULL, 'mainnet', 56, 'earning', 'stake_purchase', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'earning', NULL, NULL, 1.800000000000000000, 222, 'stake_purchase', 'RESTAKE-20260807-189C7B04', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:39:00', '2026-08-07 13:09:00', NULL, NULL, NULL, NULL, NULL, 0),
(304, NULL, 'mainnet', 56, 'staking', 'stake_purchase', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'staking', NULL, NULL, 1.900000000000000000, 223, 'stake_purchase', 'RESTAKE-20260807-189C7B04', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:39:00', '2026-08-07 13:09:00', NULL, NULL, NULL, NULL, NULL, 0),
(305, NULL, 'mainnet', 56, 'bonus', 'bonus', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 2.800000000000000000, 224, 'bonus', 'RESTAKE-20260807-189C7B04', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:39:00', '2026-08-07 13:09:00', NULL, NULL, NULL, NULL, NULL, 0),
(306, NULL, 'mainnet', 56, 'exchange', 'stake_purchase', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.900000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, NULL, 5.600000000000000000, 225, 'stake_purchase', 'RESTAKE-20260807-190BA688', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:39:25', '2026-08-07 13:09:25', NULL, NULL, NULL, NULL, NULL, 0),
(307, NULL, 'mainnet', 56, 'earning', 'stake_purchase', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'earning', NULL, NULL, 1.700000000000000000, 226, 'stake_purchase', 'RESTAKE-20260807-190BA688', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:39:25', '2026-08-07 13:09:25', NULL, NULL, NULL, NULL, NULL, 0),
(308, NULL, 'mainnet', 56, 'bonus', 'bonus', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 3.050000000000000000, 227, 'bonus', 'RESTAKE-20260807-190BA688', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:39:25', '2026-08-07 13:09:25', NULL, NULL, NULL, NULL, NULL, 0),
(309, NULL, 'mainnet', 56, 'exchange', 'stake_purchase', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.900000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, NULL, 4.700000000000000000, 228, 'stake_purchase', 'RESTAKE-20260807-3E8D8DEA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:39:46', '2026-08-07 13:09:46', NULL, NULL, NULL, NULL, NULL, 0),
(310, NULL, 'mainnet', 56, 'staking', 'stake_purchase', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'staking', NULL, NULL, 1.800000000000000000, 229, 'stake_purchase', 'RESTAKE-20260807-3E8D8DEA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:39:46', '2026-08-07 13:09:46', NULL, NULL, NULL, NULL, NULL, 0),
(311, NULL, 'mainnet', 56, 'bonus', 'bonus', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 3.300000000000000000, 230, 'bonus', 'RESTAKE-20260807-3E8D8DEA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:39:46', '2026-08-07 13:09:46', NULL, NULL, NULL, NULL, NULL, 0),
(312, NULL, 'mainnet', 56, 'exchange', 'stake_purchase', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.700000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, NULL, 4.000000000000000000, 231, 'stake_purchase', 'RESTAKE-20260807-DEC9854E', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:40:19', '2026-08-07 13:10:19', NULL, NULL, NULL, NULL, NULL, 0),
(313, NULL, 'mainnet', 56, 'earning', 'stake_purchase', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'earning', NULL, NULL, 1.600000000000000000, 232, 'stake_purchase', 'RESTAKE-20260807-DEC9854E', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:40:19', '2026-08-07 13:10:19', NULL, NULL, NULL, NULL, NULL, 0),
(314, NULL, 'mainnet', 56, 'staking', 'stake_purchase', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'staking', NULL, NULL, 1.700000000000000000, 233, 'stake_purchase', 'RESTAKE-20260807-DEC9854E', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:40:19', '2026-08-07 13:10:19', NULL, NULL, NULL, NULL, NULL, 0),
(315, NULL, 'mainnet', 56, 'bonus', 'stake_purchase', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, NULL, 3.200000000000000000, 234, 'stake_purchase', 'RESTAKE-20260807-DEC9854E', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:40:19', '2026-08-07 13:10:19', NULL, NULL, NULL, NULL, NULL, 0),
(316, NULL, 'mainnet', 56, 'bonus', 'bonus', 'confirmed', NULL, NULL, 999999611, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 3.450000000000000000, 235, 'bonus', 'RESTAKE-20260807-DEC9854E', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:40:19', '2026-08-07 13:10:19', NULL, NULL, NULL, NULL, NULL, 0),
(317, NULL, 'mainnet', 56, 'earning', 'binary_matching', 'confirmed', NULL, NULL, 999999608, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.160000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'earning', NULL, 0.160000000000000000, 236, 'binary_matching', 'MB-20260807-094332-C8E156', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:43:32', '2026-08-07 13:13:32', NULL, NULL, NULL, NULL, NULL, 0),
(318, NULL, 'mainnet', 56, 'staking', 'binary_matching', 'confirmed', NULL, NULL, 999999608, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.040000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'staking', NULL, 0.040000000000000000, 237, 'binary_matching', 'MB-20260807-094332-C8E156', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:43:32', '2026-08-07 13:13:32', NULL, NULL, NULL, NULL, NULL, 0),
(319, '0xe59835dce68d5f20671e8e3fb152678fe6e28b48d0c561dc65c5894525d0e104', 'mainnet', 56, 'earning', 'transfer', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x77779986df95ebeae48f4c6a94be2886ea7a943c', 999999608, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.200000000000000000, 114507007, NULL, 167, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'binary_matching_payout', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:44:54', '2026-08-07 13:14:54', NULL, NULL, NULL, NULL, NULL, 0),
(320, NULL, 'mainnet', 56, 'exchange', 'stake_purchase', 'confirmed', NULL, NULL, 999999612, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.800000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, NULL, 6.150000000000000000, 238, 'stake_purchase', 'RESTAKE-20260807-F7CAD0A9', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:59:34', '2026-08-07 13:29:34', NULL, NULL, NULL, NULL, NULL, 0),
(321, NULL, 'mainnet', 56, 'bonus', 'stake_purchase', 'confirmed', NULL, NULL, 999999612, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.200000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, NULL, 1.850000000000000000, 239, 'stake_purchase', 'RESTAKE-20260807-F7CAD0A9', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:59:34', '2026-08-07 13:29:34', NULL, NULL, NULL, NULL, NULL, 0),
(322, NULL, 'mainnet', 56, 'bonus', 'bonus', 'confirmed', NULL, NULL, 999999612, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.500000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 2.350000000000000000, 240, 'bonus', 'RESTAKE-20260807-F7CAD0A9', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 09:59:34', '2026-08-07 13:29:34', NULL, NULL, NULL, NULL, NULL, 0),
(323, '0x6a63e130467681d2eaeb16dcc9cf9c391c6acb4df80ef8f282dc9180ee70ab58', 'bsc', 56, 'usdt', 'deposit', 'confirmed', '0x71e038da10d1aab4925c683a140d72c115f1efe3', '0x2e228070726ec09a6e0a9d89287f900b5dd2d3db', 999999606, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.200000000000000000, 114509760, '2026-08-07 13:34:16', 0, NULL, 13, NULL, 51603, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', 0.000000000000000000, 0.200000000000000000, 241, 'deposit', '39', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 13:34:16', '2026-08-07 19:35:58', NULL, NULL, NULL, NULL, NULL, 0),
(324, NULL, 'mainnet', 56, 'usdt', 'swap', 'confirmed', NULL, NULL, 999999606, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', NULL, NULL, 0.100000000000000000, 242, 'swap', 'SWP-20260807-E710F40C', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 10:08:02', '2026-08-07 13:38:02', NULL, NULL, NULL, NULL, NULL, 0),
(325, '0x9b5e624dde2b7b20e8a562ca4f36fc5beb4db2670638c34d17f55be57c609995', 'mainnet', 56, 'gas', 'gas_funding', 'processing', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x2e228070726ec09a6e0a9d89287f900b5dd2d3db', 999999606, NULL, NULL, NULL, NULL, NULL, 0.003150000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260807-E710F40C', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 10:09:06', '2026-08-07 13:39:06', NULL, NULL, NULL, NULL, NULL, 0),
(326, '0xd895ff06b6162b4957421fb7cfc0b88bb9e574c680fcacd51e91f1a553aa8b9d', 'mainnet', 56, 'usdt', 'deposit', 'processing', '0x2e228070726ec09a6e0a9d89287f900b5dd2d3db', '0x3088b858dc4cd85a001337f8e15a40b24666d321', 999999606, NULL, NULL, NULL, NULL, NULL, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260807-E710F40C', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 10:11:48', '2026-08-07 13:41:48', NULL, NULL, NULL, NULL, NULL, 0),
(327, '0x4f46ebd6d76121a1ac3ee2e7a2e4643d2c0b0a92d9a3b4b722ad51decbe72eaf', 'mainnet', 56, 'staking', 'transfer', 'processing', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x2e228070726ec09a6e0a9d89287f900b5dd2d3db', 999999606, NULL, NULL, NULL, NULL, NULL, 1.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260807-E710F40C', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 10:12:00', '2026-08-07 13:42:00', NULL, NULL, NULL, NULL, NULL, 0),
(328, '0x4f46ebd6d76121a1ac3ee2e7a2e4643d2c0b0a92d9a3b4b722ad51decbe72eaf', 'mainnet', 56, 'bonus', 'stake_purchase', 'confirmed', NULL, NULL, 999999606, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 0.250000000000000000, 243, 'stake_purchase', 'SWP-20260807-E710F40C', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 10:12:00', '2026-08-07 13:42:00', NULL, NULL, NULL, NULL, NULL, 0),
(329, '0x2ab5a3a69903d683be0fc695d5e52154e9f276cef5da6bcba3303ff0a656bb58', 'mainnet', 56, 'treasury', 'treasury_direct_send', 'confirmed', '0x3088B858dc4cD85A001337f8E15a40b24666d321', '0x2E228070726Ec09A6e0a9d89287F900B5DD2D3Db', 999999606, 1, 'BMAN', NULL, NULL, NULL, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'treasury_direct_send', 'TDS-20260807-7DA44F81', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 1, NULL, NULL, NULL, '2026-08-07 10:16:13', '2026-08-07 13:46:13', NULL, NULL, NULL, NULL, NULL, 0),
(330, '0xb2cd662d98f6ee865706d3c0e8308015674501d0d709659193c7af01c0e6c93e', 'mainnet', 56, 'treasury', 'treasury_direct_send', 'confirmed', '0x3088B858dc4cD85A001337f8E15a40b24666d321', '0x2E228070726Ec09A6e0a9d89287F900B5DD2D3Db', 999999606, 1, 'BMAN', NULL, NULL, NULL, 2.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'treasury_direct_send', 'TDS-20260807-D218842D', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 1, NULL, NULL, NULL, '2026-08-07 10:17:04', '2026-08-07 13:47:04', NULL, NULL, NULL, NULL, NULL, 0),
(331, '0xb2cd662d98f6ee865706d3c0e8308015674501d0d709659193c7af01c0e6c93e', 'mainnet', 56, 'exchange', 'deposit', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x2E228070726Ec09A6e0a9d89287F900B5DD2D3Db', 999999606, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 2.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 2.000000000000000000, 244, 'deposit', '40', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 10:17:26', '2026-08-07 13:47:26', NULL, NULL, NULL, NULL, NULL, 0),
(332, '0x2ab5a3a69903d683be0fc695d5e52154e9f276cef5da6bcba3303ff0a656bb58', 'mainnet', 56, 'exchange', 'deposit', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x2E228070726Ec09A6e0a9d89287F900B5DD2D3Db', 999999606, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.100000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 2.100000000000000000, 245, 'deposit', '41', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 10:17:26', '2026-08-07 13:47:26', NULL, NULL, NULL, NULL, NULL, 0),
(333, '0x4f46ebd6d76121a1ac3ee2e7a2e4643d2c0b0a92d9a3b4b722ad51decbe72eaf', 'mainnet', 56, 'exchange', 'deposit', 'confirmed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x2E228070726Ec09A6e0a9d89287F900B5DD2D3Db', 999999606, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 3.350000000000000000, 246, 'deposit', '42', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 10:17:26', '2026-08-07 13:47:26', NULL, NULL, NULL, NULL, NULL, 0),
(334, NULL, 'mainnet', 56, 'exchange', 'admin_adjustment', 'confirmed', NULL, NULL, 999999608, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 2.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 2.000000000000000000, 247, 'admin_adjustment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 10:23:44', '2026-08-07 13:53:44', NULL, NULL, NULL, NULL, NULL, 0),
(335, NULL, 'mainnet', 56, 'exchange', 'stake_purchase', 'confirmed', NULL, NULL, 999999608, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, NULL, 1.000000000000000000, 248, 'stake_purchase', 'RESTAKE-20260807-0D4183FE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 10:24:07', '2026-08-07 13:54:07', NULL, NULL, NULL, NULL, NULL, 0),
(336, NULL, 'mainnet', 56, 'bonus', 'bonus', 'confirmed', NULL, NULL, 999999608, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 0.500000000000000000, 249, 'bonus', 'RESTAKE-20260807-0D4183FE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 10:24:07', '2026-08-07 13:54:07', NULL, NULL, NULL, NULL, NULL, 0),
(337, NULL, 'mainnet', 56, 'exchange', 'stake_purchase', 'confirmed', NULL, NULL, 999999606, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.800000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, NULL, 1.550000000000000000, 250, 'stake_purchase', 'RESTAKE-20260807-23FBA38B', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 10:28:08', '2026-08-07 13:58:08', NULL, NULL, NULL, NULL, NULL, 0),
(338, NULL, 'mainnet', 56, 'bonus', 'stake_purchase', 'confirmed', NULL, NULL, 999999606, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.200000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, NULL, 0.050000000000000000, 251, 'stake_purchase', 'RESTAKE-20260807-23FBA38B', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 10:28:08', '2026-08-07 13:58:08', NULL, NULL, NULL, NULL, NULL, 0),
(339, NULL, 'mainnet', 56, 'bonus', 'bonus', 'confirmed', NULL, NULL, 999999606, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.500000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 0.550000000000000000, 252, 'bonus', 'RESTAKE-20260807-23FBA38B', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 10:28:08', '2026-08-07 13:58:08', NULL, NULL, NULL, NULL, NULL, 0),
(340, 'DRYRUN-ROI-ORDER-2-ROI-M1', 'mainnet', 56, 'exchange', 'roi_monthly', 'confirmed', NULL, NULL, 3, NULL, NULL, NULL, NULL, NULL, 0.030000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'roi', 'ORDER-2-ROI-M1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 13:14:56', '2026-08-07 16:44:56', NULL, NULL, NULL, NULL, NULL, 0),
(341, 'DRYRUN-ROI-ORDER-27-ROI-M1', 'mainnet', 56, 'exchange', 'roi_monthly', 'confirmed', NULL, NULL, 999999604, NULL, NULL, NULL, NULL, NULL, 0.015000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'roi', 'ORDER-27-ROI-M1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 13:14:56', '2026-08-07 16:44:56', NULL, NULL, NULL, NULL, NULL, 0),
(342, NULL, 'mainnet', 56, 'exchange', 'admin_adjustment', 'confirmed', NULL, NULL, 999999602, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 2.250000000000000000, 255, 'admin_adjustment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 13:52:58', '2026-08-07 17:22:58', NULL, NULL, NULL, NULL, NULL, 0),
(343, NULL, 'mainnet', 56, 'exchange', 'stake_purchase', 'confirmed', NULL, NULL, 999999602, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, NULL, 1.250000000000000000, 256, 'stake_purchase', 'RESTAKE-20260807-70988204', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 13:53:26', '2026-08-07 17:23:26', NULL, NULL, NULL, NULL, NULL, 0),
(344, NULL, 'mainnet', 56, 'bonus', 'bonus', 'confirmed', NULL, NULL, 999999602, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 0.250000000000000000, 257, 'bonus', 'RESTAKE-20260807-70988204', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 13:53:26', '2026-08-07 17:23:26', NULL, NULL, NULL, NULL, NULL, 0),
(345, 'DRYRUN-ROI-RESTAKE-20260807-70988204-ROI-M1-D7', 'mainnet', 56, 'exchange', 'roi_monthly', 'confirmed', NULL, NULL, 999999602, NULL, NULL, NULL, NULL, NULL, 0.007666660000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'roi', 'RESTAKE-20260807-70988204-ROI-M1-D7', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 13:54:44', '2026-08-07 17:24:44', NULL, NULL, NULL, NULL, NULL, 0),
(346, 'DRYRUN-ROI-RESTAKE-20260807-70988204-ROI-M1-D8', 'mainnet', 56, 'exchange', 'roi_monthly', 'confirmed', NULL, NULL, 999999602, NULL, NULL, NULL, NULL, NULL, 0.007666660000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'roi', 'RESTAKE-20260807-70988204-ROI-M1-D8', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 13:56:12', '2026-08-07 17:26:12', NULL, NULL, NULL, NULL, NULL, 0);
INSERT INTO `onchain_transactions` (`id`, `tx_hash`, `network`, `chain_id`, `wallet_type`, `tx_type`, `status`, `from_address`, `to_address`, `user_id`, `admin_id`, `token_symbol`, `token_name`, `token_contract`, `token_decimals`, `amount`, `block_number`, `block_timestamp`, `confirmation_count`, `nonce`, `tx_index`, `gas_limit`, `gas_used`, `gas_price`, `max_fee_per_gas`, `max_priority_fee`, `gas_fee_total`, `native_used`, `estimated_gas`, `gas_refund`, `contract_address`, `method_name`, `method_signature`, `input_data`, `return_data`, `debit_wallet`, `credit_wallet`, `balance_before`, `balance_after`, `wallet_ledger_id`, `reference_type`, `reference_id`, `linked_deposit_id`, `linked_withdrawal_id`, `parent_tx_id`, `failure_reason`, `revert_message`, `completed_steps`, `failed_steps`, `retry_status`, `retry_count`, `linked_retry_tx_id`, `created_by`, `ip_address`, `processing_server`, `processing_ms`, `created_at`, `updated_at`, `request_tx_hash`, `delivery_tx_hash`, `last_verified_at`, `finalized_at`, `rpc_endpoint`, `reorg_count`) VALUES
(347, 'DRYRUN-ROI-RESTAKE-20260807-70988204-ROI-M1-D9', 'mainnet', 56, 'exchange', 'roi_monthly', 'confirmed', NULL, NULL, 999999602, NULL, NULL, NULL, NULL, NULL, 0.007666680000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'roi', 'RESTAKE-20260807-70988204-ROI-M1-D9', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 13:56:12', '2026-08-07 17:26:12', NULL, NULL, NULL, NULL, NULL, 0),
(348, 'DRYRUN-ROI-RESTAKE-20260807-70988204-ROI-M2-D7', 'mainnet', 56, 'exchange', 'roi_monthly', 'confirmed', NULL, NULL, 999999602, NULL, NULL, NULL, NULL, NULL, 0.007666660000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'roi', 'RESTAKE-20260807-70988204-ROI-M2-D7', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 14:15:06', '2026-08-07 17:45:06', NULL, NULL, NULL, NULL, NULL, 0),
(349, NULL, 'mainnet', 56, 'exchange', 'admin_adjustment', 'confirmed', NULL, NULL, 999999602, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 2.280666660000000000, 262, 'admin_adjustment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 14:15:32', '2026-08-07 17:45:32', NULL, NULL, NULL, NULL, NULL, 0),
(350, NULL, 'mainnet', 56, 'exchange', 'stake_purchase', 'confirmed', NULL, NULL, 999999602, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, NULL, 1.280666660000000000, 263, 'stake_purchase', 'RESTAKE-20260807-21BBF520', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 14:15:57', '2026-08-07 17:45:57', NULL, NULL, NULL, NULL, NULL, 0),
(351, NULL, 'mainnet', 56, 'bonus', 'bonus', 'confirmed', NULL, NULL, 999999602, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 0.500000000000000000, 264, 'bonus', 'RESTAKE-20260807-21BBF520', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 14:15:57', '2026-08-07 17:45:57', NULL, NULL, NULL, NULL, NULL, 0),
(352, NULL, 'mainnet', 56, 'exchange', 'admin_adjustment', 'confirmed', NULL, NULL, 999999607, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 1.000000000000000000, 265, 'admin_adjustment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 14:23:20', '2026-08-07 17:53:20', NULL, NULL, NULL, NULL, NULL, 0),
(353, NULL, 'mainnet', 56, 'exchange', 'stake_purchase', 'confirmed', NULL, NULL, 999999607, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, NULL, 0.000000000000000000, 266, 'stake_purchase', 'RESTAKE-20260807-A3F584EE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 14:23:51', '2026-08-07 17:53:51', NULL, NULL, NULL, NULL, NULL, 0),
(354, NULL, 'mainnet', 56, 'bonus', 'bonus', 'confirmed', NULL, NULL, 999999607, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 0.750000000000000000, 267, 'bonus', 'RESTAKE-20260807-A3F584EE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 14:23:51', '2026-08-07 17:53:51', NULL, NULL, NULL, NULL, NULL, 0),
(355, 'DRYRUN-ROI-RESTAKE-20260807-A3F584EE-ROI-M1-D7', 'mainnet', 56, 'exchange', 'roi_monthly', 'confirmed', NULL, NULL, 999999607, NULL, NULL, NULL, NULL, NULL, 0.005000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'roi', 'RESTAKE-20260807-A3F584EE-ROI-M1-D7', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 14:24:51', '2026-08-07 17:54:51', NULL, NULL, NULL, NULL, NULL, 0),
(356, '0x340bdd28e002995e4e22ac56d4683436ca2c8573f2ddd7f23993401697701fd4', 'bsc', 56, 'usdt', 'deposit', 'confirmed', '0x71e038da10d1aab4925c683a140d72c115f1efe3', '0x85519d7a4e94a070eceeee5e1763206c4d6665ff', 999999613, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.200000000000000000, 114546698, '2026-08-07 14:41:22', 0, NULL, 6, NULL, 51603, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', 0.000000000000000000, 0.200000000000000000, 269, 'deposit', '43', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 14:41:22', '2026-08-07 14:54:35', NULL, NULL, NULL, NULL, NULL, 0),
(357, NULL, 'mainnet', 56, 'usdt', 'swap', 'confirmed', NULL, NULL, 999999613, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.200000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', NULL, NULL, 0.000000000000000000, 270, 'swap', 'SWP-20260807-EF6ABE55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 14:43:23', '2026-08-07 18:13:23', NULL, NULL, NULL, NULL, NULL, 0),
(358, '0x597a3e3958a6c7556d953bf6886ec355821535f3efbdf628bf3db1534c224212', 'mainnet', 56, 'gas', 'gas_funding', 'processing', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x85519d7a4e94a070eceeee5e1763206c4d6665ff', 999999613, NULL, NULL, NULL, NULL, NULL, 0.003150000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260807-EF6ABE55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 14:44:01', '2026-08-07 18:14:01', NULL, NULL, NULL, NULL, NULL, 0),
(359, '0x7e77043a46e1b862fc51f2cddd468c19c62f0abc42e71e99984f08166983232c', 'mainnet', 56, 'usdt', 'deposit', 'processing', '0x85519d7a4e94a070eceeee5e1763206c4d6665ff', '0x3088b858dc4cd85a001337f8e15a40b24666d321', 999999613, NULL, NULL, NULL, NULL, NULL, 0.200000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260807-EF6ABE55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 14:44:25', '2026-08-07 18:14:25', NULL, NULL, NULL, NULL, NULL, 0),
(360, '0x50f86adb9f9f9fde6e1928d75ccc915be0fc182049a891665dd372af96cc5afb', 'mainnet', 56, 'staking', 'transfer', 'processing', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x85519d7a4e94a070eceeee5e1763206c4d6665ff', 999999613, NULL, NULL, NULL, NULL, NULL, 2.400000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'stake_purchase', 'SWP-20260807-EF6ABE55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 14:44:43', '2026-08-07 18:14:43', NULL, NULL, NULL, NULL, NULL, 0),
(361, '0x50f86adb9f9f9fde6e1928d75ccc915be0fc182049a891665dd372af96cc5afb', 'mainnet', 56, 'bonus', 'stake_purchase', 'confirmed', NULL, NULL, 999999613, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.400000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 0.400000000000000000, 271, 'stake_purchase', 'SWP-20260807-EF6ABE55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 14:44:43', '2026-08-07 18:14:43', NULL, NULL, NULL, NULL, NULL, 0),
(362, NULL, 'mainnet', 56, 'exchange', 'admin_adjustment', 'confirmed', NULL, NULL, 999999602, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 2.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 3.280666660000000000, 272, 'admin_adjustment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 14:59:40', '2026-08-07 18:29:40', NULL, NULL, NULL, NULL, NULL, 0),
(363, NULL, 'mainnet', 56, 'exchange', 'stake_purchase', 'confirmed', NULL, NULL, 999999602, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 2.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, NULL, 1.280666660000000000, 273, 'stake_purchase', 'RESTAKE-20260807-DA3DD430', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 15:00:05', '2026-08-07 18:30:05', NULL, NULL, NULL, NULL, NULL, 0),
(364, NULL, 'mainnet', 56, 'bonus', 'bonus', 'confirmed', NULL, NULL, 999999602, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.400000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 0.900000000000000000, 274, 'bonus', 'RESTAKE-20260807-DA3DD430', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 15:00:05', '2026-08-07 18:30:05', NULL, NULL, NULL, NULL, NULL, 0),
(365, NULL, 'mainnet', 56, 'exchange', 'admin_adjustment', 'confirmed', NULL, NULL, 999999603, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, 1.000000000000000000, 275, 'admin_adjustment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 19:52:24', '2026-08-07 19:52:24', NULL, NULL, NULL, NULL, NULL, 0),
(366, '0x6e06cbe4bc0365a8b4fbec1794b644bf18718911667c241e75ca12616bb3e591', 'bsc', 56, NULL, 'deposit', 'confirmed', '0x71e038da10d1aab4925c683a140d72c115f1efe3', '0x3cc69420a1359fb650ecc79367cf2732d9a77bd5', 999999603, NULL, NULL, NULL, NULL, NULL, 0.200000000000000000, 114339271, '2026-08-06 16:13:23', 0, NULL, 99, NULL, 51603, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-06 16:13:23', '2026-08-07 19:52:53', NULL, NULL, NULL, NULL, NULL, 0),
(367, NULL, 'mainnet', 56, 'exchange', 'stake_purchase', 'confirmed', NULL, NULL, 999999603, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 1.000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'exchange', NULL, NULL, 0.000000000000000000, 276, 'stake_purchase', 'RESTAKE-20260807-D7676BA5', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 19:52:53', '2026-08-07 19:52:53', NULL, NULL, NULL, NULL, NULL, 0),
(368, NULL, 'mainnet', 56, 'bonus', 'bonus', 'confirmed', NULL, NULL, 999999603, NULL, 'BMAN', 'BMAN Token', '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 18, 0.250000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bonus', NULL, 0.250000000000000000, 277, 'bonus', 'RESTAKE-20260807-D7676BA5', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 19:52:53', '2026-08-07 19:52:53', NULL, NULL, NULL, NULL, NULL, 0),
(369, '0x8b66252e51f0d30ce4f1e5a9c048dc31b90dda6f7950712a9f61c8119f21a582', 'bsc', 56, 'usdt', 'deposit', 'confirmed', '0x71e038da10d1aab4925c683a140d72c115f1efe3', '0x4e107aabee4f7d2a12abf12cd1fc9506523e49fb', 999999616, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.200000000000000000, 114563505, '2026-08-07 20:17:28', 0, NULL, 18, NULL, 51603, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', 0.000000000000000000, 0.200000000000000000, 278, 'deposit', '44', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 20:17:28', '2026-08-07 20:18:25', NULL, NULL, NULL, NULL, NULL, 0),
(370, NULL, 'mainnet', 56, 'usdt', 'swap', 'confirmed', NULL, NULL, 999999616, NULL, 'USDT', 'Tether USD', '0x55d398326f99059fF775485246999027B3197955', 18, 0.200000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'usdt', NULL, NULL, 0.000000000000000000, 279, 'swap', 'SWP-20260807-91A3AB87', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-07 20:18:28', '2026-08-07 20:18:28', NULL, NULL, NULL, NULL, NULL, 0),
(371, '0xd64cb1a90b860a9be1974e768ffa2afbc713560040d89dbea444361fd92d40a7', 'mainnet', 56, 'exchange', 'roi_monthly', 'confirmed', NULL, NULL, 999999602, NULL, NULL, NULL, NULL, NULL, 0.007666660000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'roi', 'RESTAKE-20260807-70988204-ROI-M2-D8', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-08 11:57:31', '2026-08-08 11:57:31', NULL, NULL, NULL, NULL, NULL, 0),
(372, '0xe0762834009c2897859ecf16ba633093bfa549787278bfd60c1f42ce43b82382', 'mainnet', 56, 'exchange', 'roi_monthly', 'confirmed', NULL, NULL, 999999607, NULL, NULL, NULL, NULL, NULL, 0.005000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'roi', 'RESTAKE-20260807-A3F584EE-ROI-M1-D8', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-08 11:57:49', '2026-08-08 11:57:49', NULL, NULL, NULL, NULL, NULL, 0),
(373, '0x86319157cba2464e509bc5fc6f7c4eeec7efae3ba409e0d2379a82d4919381ef', 'mainnet', 56, 'exchange', 'roi_monthly', 'confirmed', NULL, NULL, 999999603, NULL, NULL, NULL, NULL, NULL, 0.003833330000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'roi', 'RESTAKE-20260807-D7676BA5-ROI-M1-D7', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-08 11:58:03', '2026-08-08 11:58:03', NULL, NULL, NULL, NULL, NULL, 0),
(374, '0x19938244ec6941425ce5e2490df527c869c1499f95d838f6dce807b54ce0626a', 'mainnet', 56, 'exchange', 'roi_monthly', 'confirmed', NULL, NULL, 999999616, NULL, NULL, NULL, NULL, NULL, 0.008333330000000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'roi', 'ORDER-34-ROI-M1-D7', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-08-08 11:58:11', '2026-08-08 11:58:11', NULL, NULL, NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `onchain_tx_events`
--

CREATE TABLE `onchain_tx_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tx_id` bigint(20) UNSIGNED NOT NULL,
  `tx_hash` varchar(120) DEFAULT NULL,
  `event_type` varchar(40) NOT NULL,
  `old_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) DEFAULT NULL,
  `confirmations` int(11) DEFAULT NULL,
  `detail` text DEFAULT NULL,
  `actor_type` varchar(20) NOT NULL DEFAULT 'system',
  `actor_id` int(11) DEFAULT NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `rpc_endpoint` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `onchain_tx_events`
--

INSERT INTO `onchain_tx_events` (`id`, `tx_id`, `tx_hash`, `event_type`, `old_status`, `new_status`, `confirmations`, `detail`, `actor_type`, `actor_id`, `ip_address`, `created_at`, `rpc_endpoint`) VALUES
(1, 1, '0x29a05409254a504406c13e08426d455b6d8a8b058bcd9d92c7507ef8df2fca05', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-20 17:33:55', NULL),
(2, 2, '0x680529f21b8039c0bfccf9d3b7144c247d399bbfb690a8ee0ff95fa97b80096b', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-20 17:33:55', NULL),
(3, 3, '0x58500f3715c58ea34e4385363382b696338798669615022f7de9dfabe63c22d4', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-20 17:33:55', NULL),
(4, 4, '0xa0b59d15308666c6a5c78c1be5e373f6ba9b6a057d170bc1d537e9e5faf96dc1', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-20 18:35:34', NULL),
(5, 5, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-20 18:46:29', NULL),
(6, 9, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-20 19:02:46', NULL),
(7, 10, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-20 19:02:46', NULL),
(8, 10, 'DRYRUN-WTS-20260720-30109EC8', 'status_change', 'confirmed', 'confirmed', NULL, 'wallet transfer settlement [DRY-RUN]', 'system', NULL, NULL, '2026-07-20 19:08:27', NULL),
(9, 11, '0xb3e94ea802696e384d8450fa8de9c5cb680fece5e0d8fbe72d5009a648707bff', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-21 10:20:35', NULL),
(10, 12, '0xd315011a2c3b5496e2fc9e7976ae0d7f56b7f168520736f22e1180111e35b943', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-21 10:20:35', NULL),
(11, 13, '0x39ae895242516de2dc9576a079244f3aaf760b411bbef7ac641f561f293d8a1c', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-21 10:44:44', NULL),
(12, 14, '0x380739509750a561e3067d5515e3be53cf071289ffd0843bf92e5ae80a918ad5', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-21 10:44:44', NULL),
(13, 15, '0x7d57370562c2d0eb7e653ad2eff05d0b2289adbb12160b2046c4c42fd97cbbb0', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-21 10:44:44', NULL),
(14, 16, '0x887bdd07dfbaed9d8e8e78e700c63759500a3e540cc4e10b1321a6d47480affa', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-21 10:44:44', NULL),
(15, 17, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-21 16:12:50', NULL),
(16, 18, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-21 16:12:50', NULL),
(17, 18, '0xb845667b4cc77a160974e81aee6064326ed99d2eeb19b321b2d85972dc2f9f3d', 'status_change', 'confirmed', 'confirmed', NULL, 'wallet transfer settlement', 'system', NULL, NULL, '2026-07-21 16:13:32', NULL),
(18, 19, '0xb845667b4cc77a160974e81aee6064326ed99d2eeb19b321b2d85972dc2f9f3d', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-21 16:15:28', NULL),
(19, 20, '0x3d9ca04e515d17571c68352db6474a18ceb1605d5a71701471246175ab710feb', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-23 07:06:03', NULL),
(20, 21, '0x058f6dd1923c52e56da2de73fbf67a89028b59f86c7bb4053e080791df2748e2', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-23 07:06:03', NULL),
(21, 22, '0x78788087ce4ad691c3ab9c03cd708c9ce2c6f7103e8521defb2c4d727024ae9e', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-23 13:34:44', NULL),
(22, 23, '0x99bd7e1ef6aaa2967ba89b3459d435a2023ffb48237c520eab14d1df156d9329', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-23 13:36:39', NULL),
(23, 24, '0xa95dd6d48c6c1f0314ccb1c5f8286acb4f4b4b9a50bc9023f604a5200bc6c9d8', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-23 13:38:54', NULL),
(24, 25, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-23 13:46:38', NULL),
(25, 26, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-23 13:46:38', NULL),
(26, 27, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-23 13:59:53', NULL),
(27, 28, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-23 13:59:53', NULL),
(28, 26, '0x3685efee9b922057db889d2a57677be80e4a0949ad5870bbda3680e82daf1fb4', 'status_change', 'confirmed', 'confirmed', NULL, 'wallet transfer settlement', 'system', NULL, NULL, '2026-07-23 14:50:12', NULL),
(29, 29, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-23 15:03:11', NULL),
(30, 33, '0x16288134bf91c80eb4268982f128a9a2b89a3580865dce273565caf28b03baa5', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-23 15:14:40', NULL),
(31, 34, '0x16288134bf91c80eb4268982f128a9a2b89a3580865dce273565caf28b03baa5', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-23 15:14:40', NULL),
(32, 35, '0x16288134bf91c80eb4268982f128a9a2b89a3580865dce273565caf28b03baa5', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-23 15:14:40', NULL),
(33, 37, 'DRYRUN-MBU-1', 'created', NULL, 'confirmed', NULL, 'bulk member upload opening balance [DRY-RUN]', 'system', NULL, NULL, '2026-07-29 15:35:02', NULL),
(34, 38, 'DRYRUN-MBU-3', 'created', NULL, 'confirmed', NULL, 'bulk member upload opening balance [DRY-RUN]', 'system', NULL, NULL, '2026-07-29 15:35:02', NULL),
(35, 39, '0xad1f757e45aa052eff5628f31551a0bc049e14151b64239535f0237b4d56e3eb', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-29 15:36:35', NULL),
(36, 40, '0xa288fb3468bacb651689e208b1f81f2454ffb1aa5868b72955ca24726a5cc819', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-29 15:36:38', NULL),
(37, 41, 'DRYRUN-MBU-10', 'created', NULL, 'confirmed', NULL, 'bulk member upload opening balance [DRY-RUN]', 'system', NULL, NULL, '2026-07-29 15:48:50', NULL),
(38, 42, 'DRYRUN-MBU-12', 'created', NULL, 'confirmed', NULL, 'bulk member upload opening balance [DRY-RUN]', 'system', NULL, NULL, '2026-07-29 15:48:50', NULL),
(39, 43, '0xTESTzzex3338990', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-29 16:04:59', NULL),
(40, 44, '0xTESTzzex3338991', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-29 16:04:59', NULL),
(41, 45, '0x05ef03ebcd04e686239682443a25adaa2a76c2911009e4ba4ae628671d16868c', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-29 17:18:11', NULL),
(42, 46, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-29 17:19:03', NULL),
(43, 48, '0xe9f7f60185687ca5333d3b61374441f607638478c4689da7551a4a46430b997b', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-30 14:30:14', NULL),
(44, 49, '0x3685efee9b922057db889d2a57677be80e4a0949ad5870bbda3680e82daf1fb4', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-30 14:30:17', NULL),
(45, 50, '0xa95dd6d48c6c1f0314ccb1c5f8286acb4f4b4b9a50bc9023f604a5200bc6c9d8', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-30 14:30:17', NULL),
(46, 51, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-07-30 14:35:10', NULL),
(47, 55, '0xtesttxhash7', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 08:42:32', NULL),
(48, 56, '0xtesttxhash7', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 08:42:32', NULL),
(49, 57, '0xtesttxhash7', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 08:42:32', NULL),
(50, 58, '0xtesttxhash7', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 08:42:32', NULL),
(51, 59, '0xtesttxhash8', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 08:44:16', NULL),
(52, 60, '0xtesttxhash8', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 08:44:16', NULL),
(53, 61, '0xtesttxhash8', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 08:44:16', NULL),
(54, 62, '0xtesttxhash8', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 08:44:16', NULL),
(55, 63, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 10:02:36', NULL),
(56, 64, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 10:02:36', NULL),
(57, 65, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 10:02:36', NULL),
(58, 66, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 10:04:11', NULL),
(59, 67, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 10:04:11', NULL),
(60, 68, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 10:04:11', NULL),
(61, 69, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 10:59:49', NULL),
(62, 70, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 10:59:49', NULL),
(63, 71, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 10:59:49', NULL),
(64, 72, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 11:00:26', NULL),
(65, 73, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 11:00:26', NULL),
(66, 74, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 11:00:26', NULL),
(67, 75, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 11:00:26', NULL),
(68, 76, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 11:00:26', NULL),
(69, 77, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 11:00:26', NULL),
(70, 78, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 11:00:26', NULL),
(71, 79, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 11:00:26', NULL),
(72, 80, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 11:00:26', NULL),
(73, 81, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 11:00:26', NULL),
(74, 82, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 11:00:26', NULL),
(75, 83, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 11:00:26', NULL),
(76, 84, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 11:00:26', NULL),
(77, 85, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 11:00:26', NULL),
(78, 86, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 11:00:27', NULL),
(79, 87, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 11:00:27', NULL),
(80, 88, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 11:00:27', NULL),
(81, 89, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 11:00:27', NULL),
(82, 90, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 11:00:27', NULL),
(84, 92, '0x82c6095c9913b6c413150e3397a25fbe5ac0f431e06702cb775630b16e48fcdd', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 12:05:02', NULL),
(85, 93, '0x822111baa209be024a5a1140954d4e4eccb74653501fd81e61efba7398c7b70e', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 12:05:04', NULL),
(86, 96, '0x84f0715ea64a2c26e34171a76e554eeacbea7d48f4c3a1271cbd3d5f3fc34614', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 12:29:14', NULL),
(87, 102, '0xdebc914bae7a119986abec153fe25e4ef0726479b630e76c4e5b291bc8bc2761', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 12:44:24', NULL),
(88, 103, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 12:48:38', NULL),
(89, 110, 'DRYRUN-bman-TESTMERGE-A-04f785', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 13:41:57', NULL),
(93, 116, 'DRYRUN-bman-TESTMERGE2-A-1072ca', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 13:47:24', NULL),
(94, 118, 'DRYRUN-bman-TESTMERGE2-B-c832dd', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 13:47:24', NULL),
(95, 119, 'DRYRUN-bman-TESTMERGE2-B-c832dd', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 13:47:24', NULL),
(96, 120, 'DRYRUN-bman-TESTMERGE2-B-c832dd', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 13:47:24', NULL),
(97, 108, '0x460fa827d11c69b34ab2f2ef10544e5b2ca101f5da28180d806c260c67199361', 'status_change', 'processing', 'confirmed', 8230, NULL, 'system', NULL, NULL, '2026-08-05 14:11:04', NULL),
(98, 107, '0x47d6224d250d8912e7cbf270476a2fb726944bcc9680225c1250c67f3dacf727', 'status_change', 'processing', 'confirmed', 8240, NULL, 'system', NULL, NULL, '2026-08-05 14:11:07', NULL),
(99, 106, '0xcea86e9eb3646fb4bb4ccf659d4539504a7376b7a8204161b434b3f40e289118', 'status_change', 'processing', 'confirmed', 8751, NULL, 'system', NULL, NULL, '2026-08-05 14:11:09', NULL),
(100, 105, '0xb126bb3fd3bb1a1ed398a94a0270e8a4823a125a5889bd4c8b98010908ba01aa', 'status_change', 'processing', 'confirmed', 8828, NULL, 'system', NULL, NULL, '2026-08-05 14:11:12', NULL),
(101, 104, '0x3e337247ec5b7002e4f77e10ae4e913df6d687b9381857e0952a0d4058f34b53', 'status_change', 'processing', 'confirmed', 10905, NULL, 'system', NULL, NULL, '2026-08-05 14:11:15', NULL),
(102, 95, '0x84f0715ea64a2c26e34171a76e554eeacbea7d48f4c3a1271cbd3d5f3fc34614', 'status_change', 'processing', 'confirmed', 1148375, NULL, 'system', NULL, NULL, '2026-08-05 14:11:18', NULL),
(103, 94, '0xf546aaab4e2b6cd7db39822e04569a084a87cd6707fa665d01a8e9b0773d4cbe', 'status_change', 'processing', 'confirmed', 1148384, NULL, 'system', NULL, NULL, '2026-08-05 14:11:21', NULL),
(104, 121, '0x22b80932c89e866614142e8bf5b7fc893189cc7bb45561c0b0d2afb7650d9df6', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:11:56', NULL),
(105, 122, '0xd76c03cd5b0ea719d6158a32fe692843107f835f809990d8b6f7a33a19f9f4db', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:11:56', NULL),
(106, 123, '0x29a05409254a504406c13e08426d455b6d8a8b058bcd9d92c7507ef8df2fca05', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:11:56', NULL),
(107, 124, '0x1dee9af8b12d9db868c0cc67be67b48a9e53471c6314cd88a6ff344008a1e8fb', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:11:56', NULL),
(108, 125, '0x9c2950dd9f494971cacbcfc9459425d369d983eb1c5d147243fa2d6c56998b44', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:11:56', NULL),
(109, 126, '0x680529f21b8039c0bfccf9d3b7144c247d399bbfb690a8ee0ff95fa97b80096b', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:11:56', NULL),
(110, 127, '0x27ee4d5f4c5d6261fe4d3cc20d55cf04f85f5c837028b22f681fdcd67705934b', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:11:56', NULL),
(111, 128, '0x58500f3715c58ea34e4385363382b696338798669615022f7de9dfabe63c22d4', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:11:56', NULL),
(112, 129, '0x6a29a7b742f9e76eba33833cd199c47abce9ce0148b8948b21d519b6226fbf55', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:12:02', NULL),
(113, 130, '0xe9f7f60185687ca5333d3b61374441f607638478c4689da7551a4a46430b997b', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:12:02', NULL),
(114, 131, '0x8ad440d70c911ed3a199257a088b4ecefc1207a2a3183eb86bb7cf1022aa3e19', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:12:05', NULL),
(115, 132, '0x05ef03ebcd04e686239682443a25adaa2a76c2911009e4ba4ae628671d16868c', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:12:05', NULL),
(116, 133, '0xad1f757e45aa052eff5628f31551a0bc049e14151b64239535f0237b4d56e3eb', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:12:05', NULL),
(117, 134, '0xe4d367e51986bb953679b97502bcb774ca1af0650089f62fc4978995a2e9cb5f', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:12:05', NULL),
(118, 135, '0xf262579c4b551aa220c6c405e3340a8b2928556ca69a2c04e3d7f138d052c2a0', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:12:05', NULL),
(119, 136, '0xa0b59d15308666c6a5c78c1be5e373f6ba9b6a057d170bc1d537e9e5faf96dc1', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:12:05', NULL),
(120, 137, '0xcea86e9eb3646fb4bb4ccf659d4539504a7376b7a8204161b434b3f40e289118', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:12:14', NULL),
(121, 138, '0xdebc914bae7a119986abec153fe25e4ef0726479b630e76c4e5b291bc8bc2761', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:12:14', NULL),
(122, 139, '0x82c6095c9913b6c413150e3397a25fbe5ac0f431e06702cb775630b16e48fcdd', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:12:14', NULL),
(123, 140, '0x8fc86dc185af9c4bb7d45cf290a2bc80f0b232bf40b6f21a1c05ecdd91772e29', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:12:17', NULL),
(124, 141, '0x7a6209944c1c30256698aa5263b5a03b136dff6f2f7e0ab567f935ee8975ab29', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:12:17', NULL),
(125, 142, '0x9e83b928dc2a1e42f90a4c646ccc83ab12ab7c26e8a5f66c7767a566930c9d2b', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:12:17', NULL),
(126, 143, '0xcb26b8e104643468b32b2ff4c44aa45ec66a5543a6230ed17796fe2c848c427a', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:12:17', NULL),
(127, 144, '0x3eb5a14594e15266fbcdf2e95ddce9d3489ce2624c7c66552a345dbdbc74d93e', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:12:17', NULL),
(128, 145, '0x8a332d4121916ce93c61f9378fde64d5f66059d3a3e363df540627523bceb898', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:12:17', NULL),
(129, 146, '0x8499e505fe86c710d2e86058849ec3851462638343e5085f1f28262940f6e21c', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:12:17', NULL),
(130, 147, '0x1b00a1d6b55e918787e932a18c199a85463e003def766e2825521939fec4a223', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:12:17', NULL),
(131, 148, '0x6627e5259f540b5b7067d14e23a057daefc3593bbc2cb5bfbb9fc61466bd19f2', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-05 14:12:17', NULL),
(132, 150, 'DRYRUN-bman-TEST-DELIVERBMAN-A-feab33', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 14:21:44', NULL),
(133, 154, 'DRYRUN-bman-TEST-DELIVERBMAN-C-777748', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 14:25:56', NULL),
(135, 158, 'DRYRUN-bman-TEST-DELIVERBMAN-F-cafac0', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 14:26:01', NULL),
(136, 159, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 16:41:15', NULL),
(137, 160, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 16:41:15', NULL),
(138, 161, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 16:41:15', NULL),
(139, 165, 'DRYRUN-bman-ZZPA-ORDER-1785940875', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 16:41:16', NULL),
(140, 166, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 16:52:14', NULL),
(141, 167, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 16:52:14', NULL),
(142, 168, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 16:52:14', NULL),
(143, 169, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:01:16', NULL),
(144, 170, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:01:17', NULL),
(145, 174, 'DRYRUN-bman-SWP-20260805-E96D54FE', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:01:18', NULL),
(146, 175, 'DRYRUN-bman-SWP-20260805-E96D54FE', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:01:18', NULL),
(147, 176, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:01:20', NULL),
(148, 177, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:01:20', NULL),
(149, 178, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:01:20', NULL),
(150, 179, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:01:20', NULL),
(151, 180, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:01:20', NULL),
(152, 181, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:01:20', NULL),
(153, 182, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:01:20', NULL),
(154, 183, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:01:20', NULL),
(155, 184, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:01:20', NULL),
(156, 185, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:04:13', NULL),
(157, 186, 'DRYRUN-ROI-ZZPE-LEGACY-ROI-MATURITY', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:04:13', NULL),
(158, 187, 'DRYRUN-ROI-ZZPE-NEW-ROI-MATURITY', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:04:13', NULL),
(159, 188, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:04:13', NULL),
(160, 190, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:05:59', NULL),
(161, 191, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:05:59', NULL),
(162, 192, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:05:59', NULL),
(163, 193, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:05:59', NULL),
(164, 194, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:05:59', NULL),
(165, 195, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:05:59', NULL),
(166, 196, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:05:59', NULL),
(167, 197, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:05:59', NULL),
(168, 198, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:05:59', NULL),
(169, 199, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-05 17:05:59', NULL),
(170, 201, '0x9f3bd944cb97cfeed2b37f767d395652677dc3dc74ce2a2a282c6d5f267a20b9', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 08:32:05', NULL),
(171, 202, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 08:38:27', NULL),
(172, 203, '0xzzdeptesttxhash1785998986', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 08:49:46', NULL),
(173, 207, 'DRYRUN-bman-SWP-20260806-9AB2A316', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 09:03:18', NULL),
(174, 208, 'DRYRUN-bman-SWP-20260806-9AB2A316', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 09:03:18', NULL),
(175, 209, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 09:10:09', NULL),
(176, 210, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 09:10:09', NULL),
(177, 211, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 09:10:09', NULL),
(178, 212, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 09:16:58', NULL),
(179, 216, '0xa8b8c91477c7b9a0b06a25610d5ca53010b1314ab7d1553adac9350f7ee62e3e', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 09:22:20', NULL),
(180, 217, '0xa8b8c91477c7b9a0b06a25610d5ca53010b1314ab7d1553adac9350f7ee62e3e', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 09:22:21', NULL),
(181, 215, '0xa8b8c91477c7b9a0b06a25610d5ca53010b1314ab7d1553adac9350f7ee62e3e', 'status_change', 'processing', 'confirmed', 5696, NULL, 'system', NULL, NULL, '2026-08-06 10:03:23', NULL),
(182, 214, '0x95379a6f728fff9c583ff7493285e933c4029c6435b6bf575aeb6e552f5a5246', 'status_change', 'processing', 'confirmed', 5736, NULL, 'system', NULL, NULL, '2026-08-06 10:03:25', NULL),
(183, 213, '0xc457998c9bea6ff2694e5d855b9889f45e5c40e2d6143ad490172a7b9c8da766', 'status_change', 'processing', 'confirmed', 6131, NULL, 'system', NULL, NULL, '2026-08-06 10:03:27', NULL),
(184, 218, '0x95379a6f728fff9c583ff7493285e933c4029c6435b6bf575aeb6e552f5a5246', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-06 10:04:08', NULL),
(185, 219, '0x9f3bd944cb97cfeed2b37f767d395652677dc3dc74ce2a2a282c6d5f267a20b9', 'created', NULL, 'confirmed', NULL, 'imported via BscScan tokentx', 'system', NULL, NULL, '2026-08-06 10:04:08', NULL),
(186, 220, '0xd2c32ea63ab46d4c334983dbe4eba2c7e84d7b1306719390654c4545f2f83161', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 12:32:35', NULL),
(187, 222, '0x6e06cbe4bc0365a8b4fbec1794b644bf18718911667c241e75ca12616bb3e591', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 12:43:47', NULL),
(188, 223, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 12:50:19', NULL),
(189, 225, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 12:52:07', NULL),
(190, 228, '0x1e3476353fc62bae0574f9c2d0511acb7a8097e373a9399ac0d3df8ef436631e', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 12:55:27', NULL),
(191, 232, '0x47e0747075058ceadf395bb1bbfc51a34e9163b2b177770a52bb2b5a07feab9a', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 13:00:52', NULL),
(192, 233, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 13:20:10', NULL),
(193, 234, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 13:20:10', NULL),
(194, 235, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 13:20:10', NULL),
(195, 236, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 13:22:30', NULL),
(196, 237, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 13:22:30', NULL),
(197, 238, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 13:22:30', NULL),
(198, 239, '0x1cfe27b244bf15f6132b3b1b5831416618542bed02e6ae4e372b7d0914d4a715', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 13:23:10', NULL),
(199, 240, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 13:23:10', NULL),
(200, 244, '0x1e3476353fc62bae0574f9c2d0511acb7a8097e373a9399ac0d3df8ef436631e', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 13:31:32', NULL),
(201, 245, '0xd1980118c60eeb05c079bf658e816b2c423dd38149f354e8a5ab1b28da0e83e0', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 13:37:18', NULL),
(202, 246, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 13:37:54', NULL),
(203, 247, '0xf3d2c619e860969693929bd6511e40d08ece7d5c367345029a3b69a4a1350117', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 13:40:02', NULL),
(204, 248, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 13:40:30', NULL),
(205, 253, '0x5ba7c051a2b1a8af3331a5f00b4dee8796695ece5d8b3be4e4eccb125ee1c56d', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 13:41:57', NULL),
(206, 256, '0x88e71c7b8db3e36b861f08f6c68f6ebb2c8db2afa744486390bd06394829398e', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 13:42:53', NULL),
(207, 257, '0x9dda532b6cc1424ac65e3c675da414d66b4a57401f28e227b044472fe5d84aea', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 16:37:29', NULL),
(208, 258, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 16:38:14', NULL),
(209, 262, '0xa0c873980ba96f706e3e5b4e652b533af7b48345adcc479969afed4f5fe66936', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 16:53:04', NULL),
(210, 263, '0x0fdb1e48cec88f3fd8b314f13683d05d8e57ce6c32c3028522e59d1158b72d38', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 17:05:45', NULL),
(211, 264, '0x818a8c51fcb4f798a13244fcc12a6c0d8e533ed0d3d0f80658a2440a178c6795', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 17:12:04', NULL),
(212, 265, '0xd026c38d5242686fee31df0afd44f88c52b9dc0c2f95346cb15a4fe89a6db142', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 17:12:05', NULL),
(213, 266, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 17:13:10', NULL),
(214, 267, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 17:13:10', NULL),
(215, 268, '0x3d58397d37a7067a7f9382ec7e116e1474e444f220623638d5663482f34bbd1d', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 17:22:47', NULL),
(216, 269, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 17:23:37', NULL),
(217, 270, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 17:24:48', NULL),
(218, 276, '0x55e0ad3b428365b6804d284df9fac96978264d52a65a69bc91e913ffefbbf90e', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 17:37:04', NULL),
(219, 278, '0x31b0d017ca0d861874e72322f687b55cd9d8f163633b2b154e6c057acd9bd052', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 17:37:06', NULL),
(220, 279, '0x7e27499ab4e4c63ce5d5430323462ed72c12ce07a63fdb4fb88a799e2f3d83ef', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 17:53:45', NULL),
(221, 280, '0xf8b298fa9fe11b9d45b0c24b7445e5bc03063e3bd75570504ea6266533b0ff15', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 18:17:10', NULL),
(222, 281, '0x31b0d017ca0d861874e72322f687b55cd9d8f163633b2b154e6c057acd9bd052', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 18:17:10', NULL),
(223, 282, '0x55e0ad3b428365b6804d284df9fac96978264d52a65a69bc91e913ffefbbf90e', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-06 18:17:10', NULL),
(224, 283, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 07:35:03', NULL),
(225, 284, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 07:35:03', NULL),
(226, 285, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 07:35:03', NULL),
(227, 286, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:21:02', NULL),
(228, 287, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:21:02', NULL),
(229, 288, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:21:02', NULL),
(230, 289, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:34:36', NULL),
(231, 290, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:34:49', NULL),
(232, 291, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:34:54', NULL),
(233, 292, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:35:01', NULL),
(234, 293, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:37:45', NULL),
(235, 294, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:37:45', NULL),
(236, 295, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:38:11', NULL),
(237, 296, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:38:11', NULL),
(238, 297, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:38:11', NULL),
(239, 298, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:38:38', NULL),
(240, 299, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:38:38', NULL),
(241, 300, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:38:38', NULL),
(242, 301, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:38:38', NULL),
(243, 302, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:39:00', NULL),
(244, 303, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:39:00', NULL),
(245, 304, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:39:00', NULL),
(246, 305, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:39:00', NULL),
(247, 306, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:39:25', NULL),
(248, 307, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:39:25', NULL),
(249, 308, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:39:25', NULL),
(250, 309, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:39:46', NULL),
(251, 310, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:39:46', NULL),
(252, 311, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:39:46', NULL),
(253, 312, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:40:19', NULL),
(254, 313, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:40:19', NULL),
(255, 314, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:40:19', NULL),
(256, 315, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:40:19', NULL),
(257, 316, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:40:19', NULL),
(258, 317, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:43:32', NULL),
(259, 318, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:43:32', NULL),
(260, 319, '0xe59835dce68d5f20671e8e3fb152678fe6e28b48d0c561dc65c5894525d0e104', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:44:54', NULL),
(261, 320, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:59:34', NULL),
(262, 321, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:59:34', NULL),
(263, 322, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 09:59:34', NULL),
(264, 323, '0x6a63e130467681d2eaeb16dcc9cf9c391c6acb4df80ef8f282dc9180ee70ab58', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 10:04:37', NULL),
(265, 324, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 10:08:02', NULL),
(266, 328, '0x4f46ebd6d76121a1ac3ee2e7a2e4643d2c0b0a92d9a3b4b722ad51decbe72eaf', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 10:12:00', NULL),
(267, 329, '0x2ab5a3a69903d683be0fc695d5e52154e9f276cef5da6bcba3303ff0a656bb58', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 10:16:13', NULL),
(268, 330, '0xb2cd662d98f6ee865706d3c0e8308015674501d0d709659193c7af01c0e6c93e', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 10:17:04', NULL),
(269, 331, '0xb2cd662d98f6ee865706d3c0e8308015674501d0d709659193c7af01c0e6c93e', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 10:17:26', NULL),
(270, 332, '0x2ab5a3a69903d683be0fc695d5e52154e9f276cef5da6bcba3303ff0a656bb58', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 10:17:26', NULL),
(271, 333, '0x4f46ebd6d76121a1ac3ee2e7a2e4643d2c0b0a92d9a3b4b722ad51decbe72eaf', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 10:17:26', NULL),
(272, 334, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 10:23:44', NULL),
(273, 335, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 10:24:07', NULL),
(274, 336, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 10:24:07', NULL),
(275, 337, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 10:28:08', NULL),
(276, 338, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 10:28:08', NULL),
(277, 339, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 10:28:08', NULL),
(278, 342, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 13:52:58', NULL),
(279, 343, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 13:53:26', NULL),
(280, 344, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 13:53:26', NULL),
(281, 349, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 14:15:32', NULL),
(282, 350, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 14:15:57', NULL),
(283, 351, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 14:15:57', NULL),
(284, 352, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 14:23:20', NULL),
(285, 353, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 14:23:51', NULL),
(286, 354, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 14:23:51', NULL),
(287, 356, '0x340bdd28e002995e4e22ac56d4683436ca2c8573f2ddd7f23993401697701fd4', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 14:41:37', NULL),
(288, 357, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 14:43:23', NULL),
(289, 361, '0x50f86adb9f9f9fde6e1928d75ccc915be0fc182049a891665dd372af96cc5afb', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 14:44:43', NULL),
(290, 362, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 14:59:40', NULL),
(291, 363, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 15:00:05', NULL),
(292, 364, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 15:00:05', NULL),
(293, 365, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 19:52:24', NULL),
(294, 367, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 19:52:53', NULL),
(295, 368, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 19:52:53', NULL),
(296, 369, '0x8b66252e51f0d30ce4f1e5a9c048dc31b90dda6f7950712a9f61c8119f21a582', 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 20:17:42', NULL),
(297, 370, NULL, 'created', NULL, 'confirmed', NULL, NULL, 'system', NULL, NULL, '2026-08-07 20:18:28', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `shipping_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `commission_given` tinyint(1) NOT NULL DEFAULT 0,
  `commission_amount` varchar(250) DEFAULT '0',
  `order_code` varchar(250) DEFAULT '000'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_shipments`
--

CREATE TABLE `order_shipments` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `courier_name` varchar(120) DEFAULT NULL,
  `tracking_number` varchar(120) DEFAULT NULL,
  `status` enum('placed','paid','packed','shipped','out_for_delivery','delivered','cancelled','refunded','failed') NOT NULL DEFAULT 'placed',
  `shipped_at` datetime DEFAULT NULL,
  `expected_delivery` date DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `status` enum('placed','paid','packed','shipped','out_for_delivery','delivered','cancelled','refunded','failed') NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `changed_by_admin_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_config`
--

CREATE TABLE `package_config` (
  `package_name` varchar(250) DEFAULT NULL,
  `id` int(11) NOT NULL,
  `minimum` varchar(250) DEFAULT NULL,
  `maximum` varchar(250) DEFAULT NULL,
  `bv` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `binary_commission` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `binary_commission_type` enum('amount','percent') NOT NULL DEFAULT 'percent',
  `own_commission` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `direct_commission` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `pair_commission_status` tinyint(1) NOT NULL DEFAULT 1,
  `pair_commission` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `pair_commission_type` enum('amount','percent') NOT NULL DEFAULT 'percent',
  `daily_max_pairs` int(11) NOT NULL DEFAULT 0,
  `matching_bonus_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`matching_bonus_json`)),
  `level_pv_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`level_pv_json`)),
  `product_level_comm_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`product_level_comm_json`)),
  `subscription_type` enum('monthly','yearly') DEFAULT 'yearly',
  `subscription_grace_days` int(11) NOT NULL DEFAULT 0,
  `period` varchar(150) DEFAULT NULL,
  `roi` varchar(150) DEFAULT NULL,
  `duration` varchar(150) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `created_date` datetime DEFAULT NULL,
  `update_date` datetime DEFAULT NULL,
  `retrun_principle` int(11) DEFAULT 1,
  `days_duration` varchar(250) DEFAULT NULL,
  `roi_made_by` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package_config`
--

INSERT INTO `package_config` (`package_name`, `id`, `minimum`, `maximum`, `bv`, `binary_commission`, `binary_commission_type`, `own_commission`, `direct_commission`, `pair_commission_status`, `pair_commission`, `pair_commission_type`, `daily_max_pairs`, `matching_bonus_json`, `level_pv_json`, `product_level_comm_json`, `subscription_type`, `subscription_grace_days`, `period`, `roi`, `duration`, `status`, `created_date`, `update_date`, `retrun_principle`, `days_duration`, `roi_made_by`) VALUES
('ZEN', 4, '100', '200', 20.0000, 10.0000, 'percent', 10.0000, 30.0000, 1, 10.0000, 'percent', 5, '[10,2,4,6]', '[10,5,4]', '[12,6,7]', '', 0, 'daily', '0.1', '30', 1, '2025-03-07 13:17:33', '2025-08-29 08:39:53', 1, '30', 'token'),
('CORE', 9, '1000', '2000', 20.0000, 20.0000, 'percent', 20.0000, 30.0000, 1, 20.0000, 'percent', 5, '[10,2,4,6]', '[10,5,4]', '[12,6,7]', 'yearly', 0, 'daily', '0.1', '30', 1, '2025-03-07 13:17:33', '2025-08-29 08:39:53', 1, '30', 'token'),
('TEST PKG', 10, '2500', '3000', 25.0000, 25.0000, 'percent', 25.0000, 25.0000, 1, 25.0000, 'percent', 10, '[10,7,5,2]', '[10,5,2]', '[12]', NULL, 0, 'daily', '0.2', '30', 1, '2026-02-02 07:43:35', NULL, 1, '30', 'token');

-- --------------------------------------------------------

--
-- Table structure for table `page_content`
--

CREATE TABLE `page_content` (
  `id` int(11) NOT NULL,
  `page_name` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `datetime` datetime NOT NULL,
  `updated_datetime` datetime NOT NULL,
  `temp_status` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `page_content`
--

INSERT INTO `page_content` (`id`, `page_name`, `content`, `datetime`, `updated_datetime`, `temp_status`) VALUES
(12, 'hero_sectoin', '<h3 class=\"title\">Secure Reliable</h3>\r\n\r\n<h1 class=\"title pt-3\">Nexman MLM Software</h1>\r\n', '0000-00-00 00:00:00', '2023-11-19 09:25:04', 1),
(13, 'token_structure', 'Token Structure', '2025-03-31 10:38:33', '2025-03-31 10:38:33', 1),
(14, 'token_structure_dec', '<p>Our token structure ensures transparency, fairness, and value.</p>\r\n\r\n<p>Join us in revolutionizing the digital economy.</p>\r\n', '2025-03-31 10:38:48', '2025-03-31 10:38:48', 1),
(15, 'pre_sale_box_title', '<p>PRE-SALE                 SOFT CAF               IDO</p>\r\n', '2025-03-31 10:41:02', '2025-03-31 10:41:02', 1),
(16, 'pre_sale_box_value', '<p> ||  1 FENI = 0.05 USDT  </p>\r\n\r\n<p> ||  1 USDT = 20 FENI</p>\r\n', '2025-03-31 10:41:44', '2025-03-31 10:41:44', 1),
(17, 'vission_mission_title_content', '<h2 class=\"xb-item--title\">Vision & Mission</h2>\r\n\r\n<p class=\"xb-item--content\">At <strong data-end=\"206\" data-start=\"183\">Nexman</strong>, our vision is to empower businesses and individuals through innovative MLM software solutions that simplify network management and accelerate growth. We aim to be a trusted leader in the MLM industry by driving digital transformation, ensuring transparency, and delivering scalable, user-friendly tools that support success at every level.</p>\r\n', '2025-03-31 10:42:55', '2025-03-31 10:42:55', 1),
(18, 'vission_mission_list', '<div class=\"xb-item--list\"><span><svg fill=\"none\" height=\"18\" viewbox=\"0 0 18 18\" width=\"18\" xmlns=\"http://www.w3.org/2000/svg\"> <path d=\"M18 9C18 9.768 17.0565 10.401 16.8675 11.109C16.6725 11.841 17.166 12.861 16.7955 13.5015C16.419 14.1525 15.2865 14.2305 14.7585 14.7585C14.2305 15.2865 14.1525 16.419 13.5015 16.7955C12.861 17.166 11.841 16.6725 11.109 16.8675C10.401 17.0565 9.768 18 9 18C8.232 18 7.599 17.0565 6.891 16.8675C6.159 16.6725 5.139 17.166 4.4985 16.7955C3.8475 16.419 3.7695 15.2865 3.2415 14.7585C2.7135 14.2305 1.581 14.1525 1.2045 13.5015C0.834 12.861 1.3275 11.841 1.1325 11.109C0.9435 10.401 0 9.768 0 9C0 8.232 0.9435 7.599 1.1325 6.891C1.3275 6.159 0.834 5.139 1.2045 4.4985C1.581 3.8475 2.7135 3.7695 3.2415 3.2415C3.7695 2.7135 3.8475 1.581 4.4985 1.2045C5.139 0.834 6.159 1.3275 6.891 1.1325C7.599 0.9435 8.232 0 9 0C9.768 0 10.401 0.9435 11.109 1.1325C11.841 1.3275 12.861 0.834 13.5015 1.2045C14.1525 1.581 14.2305 2.7135 14.7585 3.2415C15.2865 3.7695 16.419 3.8475 16.7955 4.4985C17.166 5.139 16.6725 6.159 16.8675 6.891C17.0565 7.599 18 8.232 18 9Z\" fill=\"white\"></path> <path d=\"M11.6674 6.85539L8.54986 9.88334L6.93376 8.31501C6.58297 7.9743 6.01379 7.9743 5.663 8.31501C5.3122 8.65572 5.3122 9.20854 5.663 9.54926L7.93018 11.7513C8.27141 12.0827 8.82558 12.0827 9.16682 11.7513L12.9368 8.08963C13.2876 7.74892 13.2876 7.1961 12.9368 6.85539C12.586 6.51468 12.0182 6.51468 11.6674 6.85539Z\" fill=\"#080B18\"></path> </svg> Simplify and elevate digital finance</span> <span><svg fill=\"none\" height=\"18\" viewbox=\"0 0 18 18\" width=\"18\" xmlns=\"http://www.w3.org/2000/svg\"> <path d=\"M18 9C18 9.768 17.0565 10.401 16.8675 11.109C16.6725 11.841 17.166 12.861 16.7955 13.5015C16.419 14.1525 15.2865 14.2305 14.7585 14.7585C14.2305 15.2865 14.1525 16.419 13.5015 16.7955C12.861 17.166 11.841 16.6725 11.109 16.8675C10.401 17.0565 9.768 18 9 18C8.232 18 7.599 17.0565 6.891 16.8675C6.159 16.6725 5.139 17.166 4.4985 16.7955C3.8475 16.419 3.7695 15.2865 3.2415 14.7585C2.7135 14.2305 1.581 14.1525 1.2045 13.5015C0.834 12.861 1.3275 11.841 1.1325 11.109C0.9435 10.401 0 9.768 0 9C0 8.232 0.9435 7.599 1.1325 6.891C1.3275 6.159 0.834 5.139 1.2045 4.4985C1.581 3.8475 2.7135 3.7695 3.2415 3.2415C3.7695 2.7135 3.8475 1.581 4.4985 1.2045C5.139 0.834 6.159 1.3275 6.891 1.1325C7.599 0.9435 8.232 0 9 0C9.768 0 10.401 0.9435 11.109 1.1325C11.841 1.3275 12.861 0.834 13.5015 1.2045C14.1525 1.581 14.2305 2.7135 14.7585 3.2415C15.2865 3.7695 16.419 3.8475 16.7955 4.4985C17.166 5.139 16.6725 6.159 16.8675 6.891C17.0565 7.599 18 8.232 18 9Z\" fill=\"white\"></path> <path d=\"M11.6674 6.85539L8.54986 9.88334L6.93376 8.31501C6.58297 7.9743 6.01379 7.9743 5.663 8.31501C5.3122 8.65572 5.3122 9.20854 5.663 9.54926L7.93018 11.7513C8.27141 12.0827 8.82558 12.0827 9.16682 11.7513L12.9368 8.08963C13.2876 7.74892 13.2876 7.1961 12.9368 6.85539C12.586 6.51468 12.0182 6.51468 11.6674 6.85539Z\" fill=\"#080B18\"></path> </svg> Disruptive tech (AI, blockchain, NFTs)</span> <span><svg fill=\"none\" height=\"18\" viewbox=\"0 0 18 18\" width=\"18\" xmlns=\"http://www.w3.org/2000/svg\"> <path d=\"M18 9C18 9.768 17.0565 10.401 16.8675 11.109C16.6725 11.841 17.166 12.861 16.7955 13.5015C16.419 14.1525 15.2865 14.2305 14.7585 14.7585C14.2305 15.2865 14.1525 16.419 13.5015 16.7955C12.861 17.166 11.841 16.6725 11.109 16.8675C10.401 17.0565 9.768 18 9 18C8.232 18 7.599 17.0565 6.891 16.8675C6.159 16.6725 5.139 17.166 4.4985 16.7955C3.8475 16.419 3.7695 15.2865 3.2415 14.7585C2.7135 14.2305 1.581 14.1525 1.2045 13.5015C0.834 12.861 1.3275 11.841 1.1325 11.109C0.9435 10.401 0 9.768 0 9C0 8.232 0.9435 7.599 1.1325 6.891C1.3275 6.159 0.834 5.139 1.2045 4.4985C1.581 3.8475 2.7135 3.7695 3.2415 3.2415C3.7695 2.7135 3.8475 1.581 4.4985 1.2045C5.139 0.834 6.159 1.3275 6.891 1.1325C7.599 0.9435 8.232 0 9 0C9.768 0 10.401 0.9435 11.109 1.1325C11.841 1.3275 12.861 0.834 13.5015 1.2045C14.1525 1.581 14.2305 2.7135 14.7585 3.2415C15.2865 3.7695 16.419 3.8475 16.7955 4.4985C17.166 5.139 16.6725 6.159 16.8675 6.891C17.0565 7.599 18 8.232 18 9Z\" fill=\"white\"></path> <path d=\"M11.6674 6.85539L8.54986 9.88334L6.93376 8.31501C6.58297 7.9743 6.01379 7.9743 5.663 8.31501C5.3122 8.65572 5.3122 9.20854 5.663 9.54926L7.93018 11.7513C8.27141 12.0827 8.82558 12.0827 9.16682 11.7513L12.9368 8.08963C13.2876 7.74892 13.2876 7.1961 12.9368 6.85539C12.586 6.51468 12.0182 6.51468 11.6674 6.85539Z\" fill=\"#080B18\"></path> </svg> Secure transactions, staking, digital asset ownership.</span> <span><svg fill=\"none\" height=\"18\" viewbox=\"0 0 18 18\" width=\"18\" xmlns=\"http://www.w3.org/2000/svg\"> <path d=\"M18 9C18 9.768 17.0565 10.401 16.8675 11.109C16.6725 11.841 17.166 12.861 16.7955 13.5015C16.419 14.1525 15.2865 14.2305 14.7585 14.7585C14.2305 15.2865 14.1525 16.419 13.5015 16.7955C12.861 17.166 11.841 16.6725 11.109 16.8675C10.401 17.0565 9.768 18 9 18C8.232 18 7.599 17.0565 6.891 16.8675C6.159 16.6725 5.139 17.166 4.4985 16.7955C3.8475 16.419 3.7695 15.2865 3.2415 14.7585C2.7135 14.2305 1.581 14.1525 1.2045 13.5015C0.834 12.861 1.3275 11.841 1.1325 11.109C0.9435 10.401 0 9.768 0 9C0 8.232 0.9435 7.599 1.1325 6.891C1.3275 6.159 0.834 5.139 1.2045 4.4985C1.581 3.8475 2.7135 3.7695 3.2415 3.2415C3.7695 2.7135 3.8475 1.581 4.4985 1.2045C5.139 0.834 6.159 1.3275 6.891 1.1325C7.599 0.9435 8.232 0 9 0C9.768 0 10.401 0.9435 11.109 1.1325C11.841 1.3275 12.861 0.834 13.5015 1.2045C14.1525 1.581 14.2305 2.7135 14.7585 3.2415C15.2865 3.7695 16.419 3.8475 16.7955 4.4985C17.166 5.139 16.6725 6.159 16.8675 6.891C17.0565 7.599 18 8.232 18 9Z\" fill=\"white\"></path> <path d=\"M11.6674 6.85539L8.54986 9.88334L6.93376 8.31501C6.58297 7.9743 6.01379 7.9743 5.663 8.31501C5.3122 8.65572 5.3122 9.20854 5.663 9.54926L7.93018 11.7513C8.27141 12.0827 8.82558 12.0827 9.16682 11.7513L12.9368 8.08963C13.2876 7.74892 13.2876 7.1961 12.9368 6.85539C12.586 6.51468 12.0182 6.51468 11.6674 6.85539Z\" fill=\"#080B18\"></path> </svg> Supportive community, transparency, member voice.</span></div>\r\n', '2025-03-31 10:43:48', '2025-03-31 10:43:48', 1),
(19, 'register_title_content', ' <h2 class=\"xb-item--title\">register account</h2>\r\n                                <p class=\"xb-item--content\">To register, download our app, fill out your email, and password.</p>', '2025-03-31 10:45:55', '2025-03-31 10:45:55', 1),
(20, 'deposit_title_content', ' <h2 class=\"xb-item--title\">deposit amount</h2>\r\n                                <p class=\"xb-item--content\">To deposit funds, log in to your account and navigate to the lending section. </p>', '2025-03-31 10:46:26', '2025-03-31 10:46:26', 1),
(21, 'lend_title_content', '<h2 class=\"xb-item--title\">lend Nexman MLM Software!</h2>\r\n\r\n<p class=\"xb-item--content\">To earn through our MLM system, simply log in to your account and navigate to the <strong data-end=\"208\" data-start=\"194\">\"Earnings\"</strong> section to access commissions, bonuses, and team performance insights.</p>\r\n', '2025-03-31 10:46:56', '2025-03-31 10:46:56', 1),
(22, 'director_title', '  <h1 class=\"title\">Director\'s Live Project </h1>', '2025-03-31 10:47:23', '2025-03-31 10:47:23', 1),
(23, 'director_list', '<div class=\"col-md-4\">\r\n<div class=\"prj-set\">\r\n<img src=\"https://nexman.in/assets/user/img/p1.jpg\" class=\"prj-img\">\r\n<div class=\"prj-cnt\">VictorScott Properties</div>\r\n</div>\r\n</div>\r\n<div class=\"col-md-4\">\r\n<div class=\"prj-set\">\r\n<img src=\"https://nexman.in/assets/user/img/p2.jpg\" class=\"prj-img\">\r\n<div class=\"prj-cnt\">VictorScott Fragrances</div>\r\n</div>\r\n</div>\r\n<div class=\"col-md-4\">\r\n<div class=\"prj-set\">\r\n<img src=\"https://nexman.in/assets/user/img/p3.jpg\" class=\"prj-img\">\r\n<div class=\"prj-cnt\">TauTona Gold Mine</div>\r\n</div>\r\n</div>\r\n<div class=\"col-md-3\">\r\n<div class=\"prj-set\">\r\n<img src=\"https://nexman.in/assets/user/img/p4.jpg\" class=\"prj-img\">\r\n<div class=\"prj-cnt\">MedOrange Pharmacies</div>\r\n</div>\r\n</div>\r\n<div class=\"col-md-3\">\r\n<div class=\"prj-set\">\r\n<img src=\"https://nexman.in/assets/user/img/p5.jpg\" class=\"prj-img\">\r\n<div class=\"prj-cnt\">Moollas Sunrise Farm</div>\r\n</div>\r\n</div>\r\n<div class=\"col-md-3\">\r\n<div class=\"prj-set\">\r\n<img src=\"https://nexman.in/assets/user/img/p6.jpg\" class=\"prj-img\">\r\n<div class=\"prj-cnt\">Shades Beauty Studio</div>\r\n</div>\r\n</div>\r\n<div class=\"col-md-3\">\r\n<div class=\"prj-set\">\r\n<img src=\"https://nexman.in/assets/user/img/p7.jpg\" class=\"prj-img\">\r\n<div class=\"prj-cnt\">UCS Solutions</div>\r\n</div>\r\n</div>', '2025-03-31 10:48:41', '2025-03-31 10:48:41', 1),
(24, 'road_map_tilte', ' <h1 class=\"title\">Our Road map</h1>', '2025-03-31 10:49:16', '2025-03-31 10:49:16', 1),
(25, 'road_map_list_2027', '<div class=\"roadmap--item roadmap--first_item\">\r\n  <h2 class=\"roadmap--head\">Nexman Member Wallet</h2>\r\n  <p class=\"roadmap--info\">\r\n    A secure, user-friendly wallet system designed for MLM payouts, bonuses, and earnings tracking. Supports multi-tier commissions, real-time balances, and secure transactions within the Nexman ecosystem.\r\n  </p>\r\n  <div class=\"roadmap--year\">\r\n    <div class=\"roadmap--circle\"> </div>\r\n    <span>April of 2025</span>\r\n  </div>\r\n</div>\r\n\r\n<div class=\"roadmap--item\">\r\n  <h2 class=\"roadmap--head\">Integration with Payment Gateways</h2>\r\n  <p class=\"roadmap--info\">\r\n    Seamless integration with both local and global payment platforms to enhance withdrawal options, commission distribution, and member accessibility—boosting trust and growth across the MLM network.\r\n  </p>\r\n  <div class=\"roadmap--year\">\r\n    <div class=\"roadmap--circle\"> </div>\r\n    <span>End of 2026</span>\r\n  </div>\r\n</div>\r\n\r\n<div class=\"roadmap--item\">\r\n  <h2 class=\"roadmap--head\">Nexman Lending & Rewards System</h2>\r\n  <p class=\"roadmap--info\">\r\n    An internal lending and bonus system that allows members to earn rewards, offer peer-to-peer support, and receive rank-based incentives—fostering transparency, financial empowerment, and community growth.\r\n  </p>\r\n  <div class=\"roadmap--year\">\r\n    <div class=\"roadmap--circle\"> </div>\r\n    <span>April of 2025</span>\r\n  </div>\r\n</div>\r\n', '2025-03-31 10:50:23', '2025-03-31 10:50:23', 1),
(26, 'road_map_list_2025_28', '<div class=\"roadmap--item bottom-item\">\r\n  <div class=\"roadmap--year\">\r\n    <span>Mid of 2026</span>\r\n    <div class=\"roadmap--circle\"> </div>\r\n  </div>\r\n  <h2 class=\"roadmap--head\" style=\"min-width: 20rem;\">Multi-Payment Commission Hub</h2>\r\n  <p class=\"roadmap--info\">\r\n    A major advancement to offer centralized commission distribution across multiple payment channels, improving accessibility, speed, and trust for global members.\r\n  </p>\r\n</div>\r\n\r\n<div class=\"roadmap--item bottom-item\">\r\n  <div class=\"roadmap--year\">\r\n    <span>Mid of 2026</span>\r\n    <div class=\"roadmap--circle\"> </div>\r\n  </div>\r\n  <h2 class=\"roadmap--head\">Smart Rule Engine</h2>\r\n  <p class=\"roadmap--info\">\r\n    A powerful, scalable backend system enabling flexible commission logic, custom MLM plan structures, and automated workflows tailored to business growth and compliance.\r\n  </p>\r\n</div>\r\n\r\n<div class=\"roadmap--item bottom-item\">\r\n  <div class=\"roadmap--year\">\r\n    <span>Mid of 2027</span>\r\n    <div class=\"roadmap--circle\"> </div>\r\n  </div>\r\n  <h2 class=\"roadmap--head\">Nexman Blockchain Framework</h2>\r\n  <p class=\"roadmap--info\">\r\n    A proprietary blockchain framework for MLM applications—offering unmatched transparency, smart contract support, secure transactions, and decentralized reward systems.\r\n  </p>\r\n</div>\r\n\r\n<div class=\"roadmap--item bottom-item\">\r\n  <div class=\"roadmap--year\">\r\n    <span>Mid of 2027</span>\r\n    <div class=\"roadmap--circle\"> </div>\r\n  </div>\r\n  <h2 class=\"roadmap--head\">Nexman Digital Market Hub</h2>\r\n  <p class=\"roadmap--info\">\r\n    A secure, blockchain-backed marketplace for digital products and services where members can earn, sell, and exchange offerings—boosting member value and passive income streams.\r\n  </p>\r\n</div>\r\n', '2025-03-31 10:51:04', '2025-03-31 10:51:04', 1),
(27, 'features_title', '<h1 class=\"title\">our great features</h1>', '2025-03-31 10:51:41', '2025-03-31 10:51:41', 1),
(28, 'mobile_content', '<h2 class=\"xb-item--title\">Simplify Your MLM Business</h2>\r\n\r\n<p class=\"xb-item--content\">\r\nManage your downlines, track commissions, and monitor growth—all in one powerful MLM platform designed for effortless success.\r\n</p>\r\n', '2025-03-31 10:52:06', '2025-03-31 10:52:06', 1),
(29, 'security_content', '<h2 class=\"xb-item--title\">Secure MLM Transactions & Full Control</h2>\r\n\r\n<p class=\"xb-item--content\">\r\nOur MLM platform ensures top-level security for all transactions while giving you complete control over commissions, payouts, and team activity.\r\n</p>\r\n', '2025-03-31 10:53:07', '2025-03-31 10:53:07', 1),
(30, 'transaction_content', '<h2 class=\"xb-item--title\">Lifetime Free Internal Transfers</h2>\r\n\r\n<p class=\"xb-item--content\">\r\nEnjoy unlimited, lifetime free internal transactions between downlines and uplines — boosting your MLM network growth without extra costs.\r\n</p>\r\n', '2025-03-31 10:53:47', '2025-03-31 10:53:47', 1),
(31, 'protect_indentity', '<h2 class=\"xb-item--title\">Protect Member Identity</h2>\r\n\r\n<p class=\"xb-item--content\">\r\nSafeguarding the identity of every member in your MLM network is our top priority. Our platform uses advanced encryption to ensure personal and financial data stays secure at all times.\r\n</p>\r\n', '2025-03-31 10:54:21', '2025-03-31 10:54:21', 1),
(32, 'apk_verision', 'Mobile App 1.0 <span class=\"new-btn\">new</span>', '2025-03-31 10:55:20', '2025-03-31 10:55:20', 1),
(33, 'apk_info', '<h2 class=\"xb-item--title\">Nexman MLM Software App</h2>\r\n\r\n<p class=\"xb-item--content\">Everything you need in your smartphone: crypto transaction, lending, send and receive crypto. Our goal-replace your wallet app</p>\r\n', '2025-03-31 10:55:48', '2025-03-31 10:55:48', 1),
(34, 'apk_info_list', ' <li><svg width=\"18\" height=\"18\" viewbox=\"0 0 18 18\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\">\n                                        <path d=\"M18 9C18 9.768 17.0565 10.401 16.8675 11.109C16.6725 11.841 17.166 12.861 16.7955 13.5015C16.419 14.1525 15.2865 14.2305 14.7585 14.7585C14.2305 15.2865 14.1525 16.419 13.5015 16.7955C12.861 17.166 11.841 16.6725 11.109 16.8675C10.401 17.0565 9.768 18 9 18C8.232 18 7.599 17.0565 6.891 16.8675C6.159 16.6725 5.139 17.166 4.4985 16.7955C3.8475 16.419 3.7695 15.2865 3.2415 14.7585C2.7135 14.2305 1.581 14.1525 1.2045 13.5015C0.834 12.861 1.3275 11.841 1.1325 11.109C0.9435 10.401 0 9.768 0 9C0 8.232 0.9435 7.599 1.1325 6.891C1.3275 6.159 0.834 5.139 1.2045 4.4985C1.581 3.8475 2.7135 3.7695 3.2415 3.2415C3.7695 2.7135 3.8475 1.581 4.4985 1.2045C5.139 0.834 6.159 1.3275 6.891 1.1325C7.599 0.9435 8.232 0 9 0C9.768 0 10.401 0.9435 11.109 1.1325C11.841 1.3275 12.861 0.834 13.5015 1.2045C14.1525 1.581 14.2305 2.7135 14.7585 3.2415C15.2865 3.7695 16.419 3.8475 16.7955 4.4985C17.166 5.139 16.6725 6.159 16.8675 6.891C17.0565 7.599 18 8.232 18 9Z\" fill=\"white\"></path>\n                                        <path d=\"M11.6674 6.85539L8.54986 9.88334L6.93376 8.31501C6.58297 7.9743 6.01379 7.9743 5.663 8.31501C5.3122 8.65572 5.3122 9.20854 5.663 9.54926L7.93018 11.7513C8.27141 12.0827 8.82558 12.0827 9.16682 11.7513L12.9368 8.08963C13.2876 7.74892 13.2876 7.1961 12.9368 6.85539C12.586 6.51468 12.0182 6.51468 11.6674 6.85539Z\" fill=\"#080B18\"></path>\n                                        </svg> Secure & Reliable Transactions.</li>\n                                    <li><svg width=\"18\" height=\"18\" viewbox=\"0 0 18 18\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\">\n                                        <path d=\"M18 9C18 9.768 17.0565 10.401 16.8675 11.109C16.6725 11.841 17.166 12.861 16.7955 13.5015C16.419 14.1525 15.2865 14.2305 14.7585 14.7585C14.2305 15.2865 14.1525 16.419 13.5015 16.7955C12.861 17.166 11.841 16.6725 11.109 16.8675C10.401 17.0565 9.768 18 9 18C8.232 18 7.599 17.0565 6.891 16.8675C6.159 16.6725 5.139 17.166 4.4985 16.7955C3.8475 16.419 3.7695 15.2865 3.2415 14.7585C2.7135 14.2305 1.581 14.1525 1.2045 13.5015C0.834 12.861 1.3275 11.841 1.1325 11.109C0.9435 10.401 0 9.768 0 9C0 8.232 0.9435 7.599 1.1325 6.891C1.3275 6.159 0.834 5.139 1.2045 4.4985C1.581 3.8475 2.7135 3.7695 3.2415 3.2415C3.7695 2.7135 3.8475 1.581 4.4985 1.2045C5.139 0.834 6.159 1.3275 6.891 1.1325C7.599 0.9435 8.232 0 9 0C9.768 0 10.401 0.9435 11.109 1.1325C11.841 1.3275 12.861 0.834 13.5015 1.2045C14.1525 1.581 14.2305 2.7135 14.7585 3.2415C15.2865 3.7695 16.419 3.8475 16.7955 4.4985C17.166 5.139 16.6725 6.159 16.8675 6.891C17.0565 7.599 18 8.232 18 9Z\" fill=\"white\"></path>\n                                        <path d=\"M11.6674 6.85539L8.54986 9.88334L6.93376 8.31501C6.58297 7.9743 6.01379 7.9743 5.663 8.31501C5.3122 8.65572 5.3122 9.20854 5.663 9.54926L7.93018 11.7513C8.27141 12.0827 8.82558 12.0827 9.16682 11.7513L12.9368 8.08963C13.2876 7.74892 13.2876 7.1961 12.9368 6.85539C12.586 6.51468 12.0182 6.51468 11.6674 6.85539Z\" fill=\"#080B18\"></path>\n                                        </svg> Multi-Currency Commission Support</li>\n                                    <li><svg width=\"18\" height=\"18\" viewbox=\"0 0 18 18\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\">\n                                        <path d=\"M18 9C18 9.768 17.0565 10.401 16.8675 11.109C16.6725 11.841 17.166 12.861 16.7955 13.5015C16.419 14.1525 15.2865 14.2305 14.7585 14.7585C14.2305 15.2865 14.1525 16.419 13.5015 16.7955C12.861 17.166 11.841 16.6725 11.109 16.8675C10.401 17.0565 9.768 18 9 18C8.232 18 7.599 17.0565 6.891 16.8675C6.159 16.6725 5.139 17.166 4.4985 16.7955C3.8475 16.419 3.7695 15.2865 3.2415 14.7585C2.7135 14.2305 1.581 14.1525 1.2045 13.5015C0.834 12.861 1.3275 11.841 1.1325 11.109C0.9435 10.401 0 9.768 0 9C0 8.232 0.9435 7.599 1.1325 6.891C1.3275 6.159 0.834 5.139 1.2045 4.4985C1.581 3.8475 2.7135 3.7695 3.2415 3.2415C3.7695 2.7135 3.8475 1.581 4.4985 1.2045C5.139 0.834 6.159 1.3275 6.891 1.1325C7.599 0.9435 8.232 0 9 0C9.768 0 10.401 0.9435 11.109 1.1325C11.841 1.3275 12.861 0.834 13.5015 1.2045C14.1525 1.581 14.2305 2.7135 14.7585 3.2415C15.2865 3.7695 16.419 3.8475 16.7955 4.4985C17.166 5.139 16.6725 6.159 16.8675 6.891C17.0565 7.599 18 8.232 18 9Z\" fill=\"white\"></path>\n                                        <path d=\"M11.6674 6.85539L8.54986 9.88334L6.93376 8.31501C6.58297 7.9743 6.01379 7.9743 5.663 8.31501C5.3122 8.65572 5.3122 9.20854 5.663 9.54926L7.93018 11.7513C8.27141 12.0827 8.82558 12.0827 9.16682 11.7513L12.9368 8.08963C13.2876 7.74892 13.2876 7.1961 12.9368 6.85539C12.586 6.51468 12.0182 6.51468 11.6674 6.85539Z\" fill=\"#080B18\"></path>\n                                        </svg> Nexman Lending & Bonus Program</li>\n                                    <li><svg width=\"18\" height=\"18\" viewbox=\"0 0 18 18\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\">\n                                        <path d=\"M18 9C18 9.768 17.0565 10.401 16.8675 11.109C16.6725 11.841 17.166 12.861 16.7955 13.5015C16.419 14.1525 15.2865 14.2305 14.7585 14.7585C14.2305 15.2865 14.1525 16.419 13.5015 16.7955C12.861 17.166 11.841 16.6725 11.109 16.8675C10.401 17.0565 9.768 18 9 18C8.232 18 7.599 17.0565 6.891 16.8675C6.159 16.6725 5.139 17.166 4.4985 16.7955C3.8475 16.419 3.7695 15.2865 3.2415 14.7585C2.7135 14.2305 1.581 14.1525 1.2045 13.5015C0.834 12.861 1.3275 11.841 1.1325 11.109C0.9435 10.401 0 9.768 0 9C0 8.232 0.9435 7.599 1.1325 6.891C1.3275 6.159 0.834 5.139 1.2045 4.4985C1.581 3.8475 2.7135 3.7695 3.2415 3.2415C3.7695 2.7135 3.8475 1.581 4.4985 1.2045C5.139 0.834 6.159 1.3275 6.891 1.1325C7.599 0.9435 8.232 0 9 0C9.768 0 10.401 0.9435 11.109 1.1325C11.841 1.3275 12.861 0.834 13.5015 1.2045C14.1525 1.581 14.2305 2.7135 14.7585 3.2415C15.2865 3.7695 16.419 3.8475 16.7955 4.4985C17.166 5.139 16.6725 6.159 16.8675 6.891C17.0565 7.599 18 8.232 18 9Z\" fill=\"white\"></path>\n                                        <path d=\"M11.6674 6.85539L8.54986 9.88334L6.93376 8.31501C6.58297 7.9743 6.01379 7.9743 5.663 8.31501C5.3122 8.65572 5.3122 9.20854 5.663 9.54926L7.93018 11.7513C8.27141 12.0827 8.82558 12.0827 9.16682 11.7513L12.9368 8.08963C13.2876 7.74892 13.2876 7.1961 12.9368 6.85539C12.586 6.51468 12.0182 6.51468 11.6674 6.85539Z\" fill=\"#080B18\"></path>\n                                        </svg> User-Friendly MLM Dashboard </li>', '2025-03-31 10:56:27', '2025-03-31 10:56:27', 1),
(35, 'our_team_info', '<div class=\"container\">\r\n<div class=\"section-title pb-35\">\r\n<h1 class=\"title\">Meet our team</h1>\r\n</div>\r\n\r\n<div class=\"row\">\r\n<div class=\"col-lg-4\">\r\n<div class=\"xb-team xb-team1 text-center\">\r\n<div class=\"xb-item--img pos-rel\"><img alt=\"\" src=\"https://nexman.in/assets/user/img/t5.jpg\" /></div>\r\n\r\n<div class=\"xb-item--holder\">\r\n<h2 class=\"xb-item--title\">Victor</h2>\r\n<span class=\"xb-item--sub-title\">DIRECTOR</span></div>\r\n</div>\r\n</div>\r\n\r\n<div class=\"col-lg-8 \">\r\n<div class=\"row xb-team-right\">\r\n<div class=\"col-lg-4 col-md-6 col-6\">\r\n<div class=\"xb-team text-center\">\r\n<div class=\"xb-item--img pos-rel\"><img alt=\"\" src=\"https://nexman.in/assets/user/img/t1.jpg\" /></div>\r\n\r\n<div class=\"xb-item--holder\">\r\n<h2 class=\"xb-item--title\">Nathan</h2>\r\n<span class=\"xb-item--sub-title\">DIRECTOR</span></div>\r\n</div>\r\n</div>\r\n\r\n<div class=\"col-lg-4 col-md-6  col-6\">\r\n<div class=\"xb-team text-center\">\r\n<div class=\"xb-item--img pos-rel\"><img alt=\"\" src=\"https://nexman.in/assets/user/img/t2.jpg\" /></div>\r\n\r\n<div class=\"xb-item--holder\">\r\n<h2 class=\"xb-item--title\">Joanne</h2>\r\n<span class=\"xb-item--sub-title\">DIRECTOR</span></div>\r\n</div>\r\n</div>\r\n\r\n<div class=\"col-lg-4 col-md-6  col-6\">\r\n<div class=\"xb-team text-center\">\r\n<div class=\"xb-item--img pos-rel\"><img alt=\"\" src=\"https://nexman.in/assets/user/img/t3.jpg\" /></div>\r\n\r\n<div class=\"xb-item--holder\">\r\n<h2 class=\"xb-item--title\">Amine</h2>\r\n<span class=\"xb-item--sub-title\">DIRECTOR</span></div>\r\n</div>\r\n</div>\r\n\r\n<div class=\"col-lg-4 col-md-6  col-6\">\r\n<div class=\"xb-team text-center\">\r\n<div class=\"xb-item--img pos-rel\"><img alt=\"\" src=\"https://nexman.in/assets/user/img/t4.jpg\" /></div>\r\n\r\n<div class=\"xb-item--holder\">\r\n<h2 class=\"xb-item--title\">Shedrah</h2>\r\n<span class=\"xb-item--sub-title\">DIRECTOR</span></div>\r\n</div>\r\n</div>\r\n\r\n<div class=\"col-lg-4 col-md-6  col-6\">\r\n<div class=\"xb-team text-center\">\r\n<div class=\"xb-item--img pos-rel\"><img alt=\"\" src=\"https://nexman.in/assets/user/img/t7.jpg\" /></div>\r\n\r\n<div class=\"xb-item--holder\">\r\n<h2 class=\"xb-item--title\">Aaron</h2>\r\nDIRECTOR</div>\r\n</div>\r\n</div>\r\n\r\n<div class=\"col-lg-4 col-md-6  col-6\">\r\n<div class=\"xb-team text-center\">\r\n<div class=\"xb-item--img pos-rel\"><img alt=\"\" src=\"https://nexman.in/assets/user/img/t6.jpg\" /></div>\r\n\r\n<div class=\"xb-item--holder\">\r\n<h2 class=\"xb-item--title\">Millar</h2>\r\n<span class=\"xb-item--sub-title\">DIRECTOR</span></div>\r\n</div>\r\n</div>\r\n</div>\r\n</div>\r\n</div>\r\n</div>\r\n', '2025-03-31 10:57:47', '2025-03-31 10:57:47', 0),
(36, 'terms_info', '<p>By accessing and using <strong data-end=\"123\" data-start=\"100\">Nexman</strong>, you agree to our <strong data-end=\"166\" data-start=\"142\">Terms and Conditions</strong>, which govern your use of our services, including the <strong data-end=\"245\" data-start=\"221\">Nexman Member Wallet</strong>, <strong data-end=\"272\" data-start=\"247\">MLM Commission System</strong>, <strong data-end=\"301\" data-start=\"274\">Lending & Bonus Program</strong>, and other MLM tools powered by secure technology. Nexman is dedicated to promoting transparency, fairness, and innovation within the MLM industry, ensuring secure transactions, customizable commission structures, and community-driven growth. All intellectual property on this site is owned by <strong data-end=\"632\" data-start=\"609\">Nexman</strong>. Users must be 18+ and agree not to misuse the platform or engage in prohibited activities. The tools and systems provided are not investment products and carry inherent risks; users assume full responsibility for their actions. We are not liable for system errors, commission losses, or third-party interactions. Terms are subject to change, and continued use signifies acceptance. For questions or support, please contact us at <strong data-end=\"1097\" data-start=\"1063\"><a data-end=\"1095\" data-start=\"1065\" rel=\"noopener\">support@nexman.in</a></strong>.</p>\r\n', '2025-03-31 10:57:47', '2025-03-31 10:57:47', 1);

-- --------------------------------------------------------

--
-- Table structure for table `page_link_config`
--

CREATE TABLE `page_link_config` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `page_status` int(11) NOT NULL DEFAULT 0,
  `page_image` varchar(255) DEFAULT NULL,
  `page_document` varchar(255) DEFAULT NULL,
  `page_title` varchar(255) DEFAULT NULL,
  `page_content` varchar(255) DEFAULT NULL,
  `created_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `page_link_config`
--

INSERT INTO `page_link_config` (`id`, `title`, `page_status`, `page_image`, `page_document`, `page_title`, `page_content`, `created_date`) VALUES
(1, 'White Paper', 1, 'assets/images/1744294935_chart-graph.png', 'assets/documents/1745311471_DOC-20250421-WA0001..pdf', 'Unlocking the Vision – Our Whitepaper', 'Dive into the blueprint of our mission, technology, and roadmap. Our whitepaper reveals how we’re shaping the future — one innovation at a time.', '0000-00-00 00:00:00'),
(2, 'Project', 0, 'assets/images/1744294936_chart-graph.png', 'assets/documents/1744266391_backup1.txt', 'Project Genesis – The Build Begins', 'Every revolution starts with a bold idea. Explore the concept, strategy, and foundation of our upcoming project that’s set to change the game.', '0000-00-00 00:00:00'),
(3, 'Roadmap', 0, 'assets/images/1744294936_chart-graph.png', 'assets/documents/1744266391_controller.txt', 'The Road Ahead – Our Strategic Path', 'Witness the journey from concept to completion. Our roadmap outlines key milestones and the timeline that will guide us to success.', '0000-00-00 00:00:00'),
(4, 'ai robotics', 0, 'assets/images/1744294936_chart-graph.png', 'assets/documents/1744266391_backup1.txt', 'Intelligent Machines – The AI Robotics Vision', 'Step into the world of smart automation. Our robotics roadmap is geared towards creating solutions that think, adapt, and perform.', '0000-00-00 00:00:00'),
(5, 'E-commerce', 0, 'assets/images/1744294936_chart-graph.png', 'assets/documents/1744273499_controller.txt', 'Redefining Digital Shopping – Our E-commerce Plan', 'Get ready for a seamless, next-gen shopping experience. We’re building an ecosystem that’s fast, smart, and customer-first.', '0000-00-00 00:00:00'),
(6, 'Games', 0, 'assets/images/1744294937_chart-graph.png', 'assets/documents/1744266391_database.txt', 'Play the Future – Gaming Reimagined', 'Power-packed, immersive, and built for tomorrow. Discover the concepts behind our game development universe that blends fun and tech.', '0000-00-00 00:00:00'),
(7, 'education', 0, 'assets/images/1744294937_chart-graph.png', 'assets/documents/1744295212_dummy (1).pdf', 'Learn to Lead – The Future of Education', 'Transforming how knowledge is delivered. We’re on a mission to make learning more interactive, accessible, and impactful.', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `payment_controls`
--

CREATE TABLE `payment_controls` (
  `id` int(11) NOT NULL,
  `wallet_name` varchar(250) DEFAULT NULL,
  `wallet_adderss` varchar(250) DEFAULT NULL,
  `privat_key` varchar(250) DEFAULT NULL,
  `secret_key` varchar(250) DEFAULT NULL,
  `payment_mode` smallint(6) NOT NULL DEFAULT 0,
  `payment_image` varchar(150) DEFAULT NULL,
  `payment_status` int(11) DEFAULT 1,
  `address_last` varchar(150) DEFAULT NULL,
  `key_last` varchar(150) DEFAULT NULL,
  `private_last` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `payment_controls`
--

INSERT INTO `payment_controls` (`id`, `wallet_name`, `wallet_adderss`, `privat_key`, `secret_key`, `payment_mode`, `payment_image`, `payment_status`, `address_last`, `key_last`, `private_last`) VALUES
(1, 'mexc - Payment', '0b19d291fb32002a986ef79bd238a1ca089f5855c22134cb6c9e35b405d8a61262512da632024ac6a46e3972149ca0c8', '5fb6127475c1c3ce14fd8f1cb113d113650d68e5dc1db7f7cf01926e75c79246', 'a630820540fd92980a1506693943eb3d367f843bff168416fc65737098c7a8a9a989d8ce13728af71081cec3e05f5b04', 1, '', 1, '12c1', 'KyWF', 'd561');

-- --------------------------------------------------------

--
-- Table structure for table `payment_settings`
--

CREATE TABLE `payment_settings` (
  `id` int(11) NOT NULL,
  `gateway` varchar(50) NOT NULL,
  `mode` varchar(10) DEFAULT 'sandbox',
  `client_id` text DEFAULT NULL,
  `client_secret` text DEFAULT NULL,
  `publishable_key` text DEFAULT NULL,
  `secret_key` text DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_settings`
--

INSERT INTO `payment_settings` (`id`, `gateway`, `mode`, `client_id`, `client_secret`, `publishable_key`, `secret_key`, `status`, `updated_at`) VALUES
(1, 'stripe', 'sandbox', 'pk_test_51RTgU2Qhzmr6TYhsKMWtfICaQ72crva7xVWCA0hPeV1qdH9CInnl9WwJLNcxIIUWKDhCeipRLztD82DTnBXKx05700iEGBQWjw', 'sk_test_51RTgU2Qhzmr6TYhsFObjkApvYPa0Xbtoei6RHgeljNWSZ0dQaHchXpvwNaUbY37dxeswlXrvJEfW8MY1wb8sRgXC00xLKfHPua', 'sk_test_51RTgU2Qhzmr6TYhsFObjkApvYPa0Xbtoei6RHgeljNWSZ0dQaHchXpvwNaUbY37dxeswlXrvJEfW8MY1wb8sRgXC00xLKfHPua', 'sk_test_51RTgU2Qhzmr6TYhsFObjkApvYPa0Xbtoei6RHgeljNWSZ0dQaHchXpvwNaUbY37dxeswlXrvJEfW8MY1wb8sRgXC00xLKfHPua', 1, '2025-07-14 12:39:23'),
(2, 'paypal', 'sandbox', 'AQqQS4UMgPR8d9FAEOE671D-IVhOotUmVT8kjjG_k7C-BNBYkWqDH1sdYecWk3rvoSuziLSHPcyMbYd3', 'EPaPkQKuMafgDGKsjwcSYbjYQiqjdwoZHv7YM19gRIj6R1Nm5fNgMJ1wOXHObhSOhBKEhDkYEOJxSKGl', '', '', 1, '2025-07-14 12:39:44'),
(3, 'cash_on', 'none', '', '', '', '', 1, '2025-07-14 12:22:07');

-- --------------------------------------------------------

--
-- Table structure for table `payouts`
--

CREATE TABLE `payouts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `batch_id` varchar(64) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `period` varchar(50) DEFAULT NULL,
  `total_users` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_fee` decimal(18,2) NOT NULL DEFAULT 0.00,
  `status` varchar(32) NOT NULL DEFAULT 'processing',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `processed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payout_methods`
--

CREATE TABLE `payout_methods` (
  `id` int(11) NOT NULL,
  `method_key` varchar(30) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 10,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payout_methods`
--

INSERT INTO `payout_methods` (`id`, `method_key`, `name`, `description`, `enabled`, `sort_order`, `created_at`) VALUES
(1, 'BANK', 'Bank Transfer', 'Direct to your linked bank account', 1, 1, '2026-02-02 21:00:20'),
(2, 'UPI', 'UPI', 'Instant payout to UPI ID (if enabled)', 1, 2, '2026-02-02 21:00:20');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `offer_price` decimal(10,2) DEFAULT NULL,
  `offer_status` tinyint(1) DEFAULT NULL,
  `product_type` varchar(50) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `commission` decimal(10,2) DEFAULT NULL,
  `pv` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `bv` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `description` text DEFAULT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `product_warranty` varchar(100) DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `product_size` varchar(250) DEFAULT NULL,
  `offer_percentage` varchar(150) DEFAULT NULL,
  `is_physical` varchar(150) DEFAULT '0',
  `weight` varchar(150) NOT NULL DEFAULT '0',
  `length` varchar(250) NOT NULL DEFAULT '0',
  `width` varchar(150) NOT NULL DEFAULT '0',
  `height` varchar(150) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `sku`, `price`, `offer_price`, `offer_status`, `product_type`, `brand_id`, `category_id`, `stock`, `commission`, `pv`, `bv`, `description`, `product_image`, `product_warranty`, `status`, `created_at`, `product_size`, `offer_percentage`, `is_physical`, `weight`, `length`, `width`, `height`) VALUES
(7, 'Wireless Bluetooth Headphones ( WH-XB700BT )', 'BT-H1001', 49.99, 34.99, 1, 'physical', 6, 2, 100, 0.20, 0.0000, 0.0000, '<p>Experience powerful wireless sound with WH-XB700BT.&nbsp;<br>Features include 20-hour battery life, deep bass, hands-free calling,&nbsp;<br>fast charging, and a comfortable fit.&nbsp;<br>Ideal for music, calls, and entertainment.<br>&nbsp;</p>', '1752050435_wireless_bluetooth_headphones_3d_cortton_product_thumbnail.jpeg', '1 Year', 1, '2025-07-09 14:10:35', '', '30', '1', '0.6', '18', '15', '8'),
(8, 'Men’s Classic Cotton T-Shirt ( TS-MC100 )', 'TS-MC100-BLK', 19.99, 14.99, 1, 'physical', 3, 1, 150, 0.10, 0.0000, 0.0000, '<p><strong>Stay cool and stylish with the Men’s Classic Cotton T-Shirt – your everyday essential.</strong></p><p>100% Pure Cotton – Soft, breathable, and comfortable all day long.</p><p>&nbsp;Easy to Wash – Machine washable and wrinkle-resistant fabric.</p><p>Available in Popular Colors – Match your style with Black, White, or Navy Blue.</p><p>Perfect Fit – Regular fit with round neck and half sleeves for a casual look.</p><p>Versatile – Pairs well with jeans, shorts, or joggers.</p><p>Whether for work-from-home, weekend chill, or a casual outing — this tee has you covered.</p>', '1752050802_men_s_classic_cotton_t_shirt_ts_mc100.jpeg', '', 1, '2025-07-09 14:16:55', 'S,M,L,XL', '25', '1', '0.25', '30', '25', '3'),
(9, 'Organic Baby Skin Lotion', 'BBL-SoftCare200', 1.79, 1.61, 1, 'physical', 1, 4, 400, 0.30, 0.0000, 0.0000, '<p>Keep your baby’s skin soft, moisturized, and protected with this gentle, dermatologically-tested lotion. Made with organic ingredients, it’s perfect for daily use on delicate skin.</p><p>Organic &amp; Hypoallergenic – Free from parabens, sulfates, and synthetic fragrances.</p><p>Pediatrician Approved – Safe for newborns and infants.</p><p>Non-Greasy Formula – Absorbs quickly, leaving skin smooth and hydrated.</p><p>Mild Natural Fragrance – Keeps baby smelling fresh all day.</p><p>Convenient Packaging – 200ml bottle with easy pump.</p><p>Perfect for daily moisturizing after bath time or before bedtime.</p>', '1752051121_3d_image_organic_baby_skin_lotion_this.jpeg', '', 1, '2025-07-09 14:22:01', '', '10', '1', '0.3', '16', '6', '6'),
(10, 'Herbal Face Wash with Neem & Aloe Vera (HFW-NA150)', 'HFW-NA150', 2.49, 2.49, 0, 'physical', 2, 4, 300, 0.01, 0.0000, 0.0000, '<p>Cleanse and refresh your skin naturally with our Herbal Face Wash enriched with Neem and Aloe Vera.<br>Perfect for oily to combination skin types, it helps remove excess oil, prevent acne, and keep your skin feeling cool and clear.<br>Natural Neem Extract – Helps prevent pimples and breakouts<br>Aloe Vera – Soothes and hydrates skin<br>Gentle Formula – Free from harsh chemicals, parabens, and SLS<br>Everyday Use – Mild and refreshing, suitable for daily use</p>', '1752051384_herbal_face_wash_with_neem_aloe.jpeg', '', 1, '2025-07-09 14:26:24', '', '0', NULL, '', '', '', ''),
(11, 'Apple iPhone 14 Pro (128GB, Deep Purple)', 'IP14P-128-DP', 999.00, 899.10, 1, 'physical', 5, 11, 20, 10.00, 0.0000, 0.0000, '<p>Experience the ultimate in power and performance with the Apple iPhone 14 Pro. Designed with a beautiful ceramic shield front, aerospace-grade aluminum, and an innovative <strong>Dynamic Island</strong> display, this device redefines what a smartphone can do.</p><p><strong>48MP Triple Camera System</strong> – Pro-grade low light photography<br><strong>A16 Bionic Chip</strong> – Super-fast performance and efficiency<br><strong>All-Day Battery</strong> – Up to 23 hours of video playback<br><strong>5G Enabled</strong> – Lightning-fast internet speeds<br><strong>Face ID &amp; iOS 17</strong> – Privacy-first with seamless user experience<br>Color Options – Deep Purple, Space Black, Silver</p>', '1752051640_apple_iphone_14_pro_product_thumbnail.jpeg', '1 Year', 1, '2025-07-09 14:30:40', '', '10', NULL, '', '', '', ''),
(12, 'Samsung Galaxy S23 Ultra (256GB, Phantom Black)', 'SGS23U-256-PB', 1199.00, 1139.05, 1, 'physical', 4, 11, 80, 5.00, 0.0000, 0.0000, '<p>Redefine power and precision with the Samsung Galaxy S23 Ultra — featuring a built-in <strong>S Pen</strong>, a <strong>200MP main camera</strong>, and a sleek, durable design. Built for professionals, creators, and tech enthusiasts alike.</p><p><strong>200MP Quad Camera</strong> – Pro-grade shots in any lighting</p><p><strong>Built-in S Pen</strong> – Precision input for note-taking and creativity</p><p><strong>Snapdragon 8 Gen 2</strong> – Ultra-fast flagship performance</p><p><strong>5000mAh Battery</strong> – All-day usage with 45W super fast charging</p><p><strong>6.8\" QHD+ AMOLED</strong> – 120Hz display with Gorilla Glass Victus 2</p><p>Color Options – Phantom Black, Green, Cream, Lavender</p>', '1752052379_samsung_galaxy_s23_ultra_product_thumbnail.jpeg', '1 Year', 1, '2025-07-09 14:42:59', '', '5', '1', '0.23', '16.3', '7.8', '0.9'),
(13, 'Adidas Lite Racer 3.0 Running Shoes', 'AD-LR3-BLK-M', 74.99, 59.99, 1, 'physical', 2, 18, 30, 4.00, 0.0000, 0.0000, '<p>Step into all-day comfort and lightweight speed with the <strong>Adidas Lite Racer 3.0</strong> — inspired by classic runners but built for modern street style.</p><p><strong>Cloudfoam Midsole</strong> for ultra-light cushioning<br><strong>Mesh Upper</strong> allows breathability and flexibility<br><strong>Rubber Outsole</strong> provides grip on various surfaces<br>Ideal for running, gym, walking &amp; casual use<br>Color: Core Black / Grey Three / White<br>Lace closure with iconic 3-stripe Adidas design</p>', '1752052801_adidas_lite_racer_3_0_running_shoes.jpeg', '', 1, '2025-07-09 14:50:01', '', '20', '1', '0.9', '33', '19', '12'),
(14, 'Sony EOS 1500D DSLR Camera (18–55mm Lens)', 'CN-1500D-KIT', 499.00, 449.10, 1, 'physical', 6, 13, 40, 0.30, 0.0000, 0.0000, '<p>Capture crisp, high-quality photos and Full HD videos with the <strong>Sony EOS 1500D</strong>, the perfect entry-level DSLR for budding photographers and content creators.</p><p>???? <strong>24.1MP APS-C CMOS Sensor</strong> for high-resolution photography</p><p>???? <strong>Full HD 1080p Video Recording</strong> at 30fps</p><p>???? <strong>DIGIC 4+ Image Processor</strong> for sharp images</p><p>???? Built-in Wi-Fi &amp; NFC for quick sharing</p><p>???? <strong>9-point AF system</strong> with optical viewfinder</p><p>???? Includes 18–55mm f/3.5–5.6 IS II lens</p>', '1752053568_sony_eos_1500d_dslr_camera_18_55mm_lens.jpeg', '2 Years', 1, '2025-07-09 15:02:48', '', '10', '1', '1.45 kg', '22', '18', '15'),
(15, ' Philips X-tremeVision G-Force ', 'PH-XTR-H4-GF', 39.99, 37.59, 0, 'physical', 8, 9, 48, 0.33, 0.0000, 0.0000, '<p>Upgrade your vehicle\'s night visibility with <strong>Philips X-tremeVision G-Force bulbs</strong>. Designed for performance and durability, these bulbs offer up to <strong>130% more brightness</strong> than standard halogen bulbs and a strong filament for tough driving conditions.</p><p><strong>Up to 130% Brighter Light</strong> – for safer night driving</p><p><strong>H4 Type – 60/55W, 12V</strong> – widely compatible</p><p><strong>Vibration Resistance</strong> – suitable for rough roads</p><p><strong>Plug-and-Play Installation</strong> – no modification needed</p><p>Road-legal and ECE certified</p>', '1752053927_philips_x_tremevision_g_force_h4_bulbs_for_car.jpeg', '1 Year', 1, '2025-07-09 15:08:47', '', '6', '1', '0.15', '12', '6', '6'),
(16, 'Prestige Baby Food Steamer & Blender', 'PR-BFSB-2IN1-BL', 79.99, 75.19, 0, 'physical', 7, 6, 40, 0.22, 0.0000, 0.0000, '<p>Make healthy and homemade baby food in minutes with the <strong>Prestige 2-in-1 Baby Food Steamer &amp; Blender</strong>. Designed for modern parents who want <strong>safe</strong>, <strong>nutritious</strong>, and <strong>convenient feeding</strong> solutions for their babies.</p><p><strong>Steam &amp; Blend in One Jar</strong> – retain nutrients while preparing</p><p><strong>BPA-Free Materials</strong> – completely safe for infants</p><p><strong>One-Touch Operation</strong> – easy and quick to use</p><p><strong>Low Noise</strong> – ideal for babies’ sensitive ears</p><p><strong>Detachable Parts</strong> – easy to clean and dishwasher safe</p><p>Voltage: 220-240V, 50Hz (Universal Plug Adapter included)</p>', '1752054185_prestige_baby_food_steamer_blender_3d.jpeg', '1 Year', 1, '2025-10-13 09:33:29', '', '6', '1', '1.5', '25', '22', '18');

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `slug` varchar(150) DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `image` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`id`, `name`, `parent_id`, `slug`, `status`, `sort_order`, `image`, `description`) VALUES
(1, 'Fashion', NULL, 'fashion', 1, 0, 'uploads/category/1752049235_fashion_cortoon_3d_image.jpeg', '<p>Stay stylish with the latest trends in men’s, women’s, and kids’ fashion.<br>From casual wear to statement pieces, find your perfect look for every occasion.</p>'),
(2, 'Electronics', NULL, 'electronics', 1, 0, 'uploads/category/1752049184_electronics_3d_cortoon_image.jpeg', '<p>Discover the latest in electronics with cutting-edge technology and performance.<br>Shop gadgets, devices, and appliances that make life easier and smarter.</p>'),
(3, 'Home & Kitchen', NULL, 'home-kitchen', 1, 0, 'uploads/category/1752049129_home_kitchen_3d_cortton_style.jpeg', '<p>Upgrade your living space with smart home and kitchen essentials.<br>From cookware to decor, find everything to style and simplify your home.</p>'),
(4, 'Beauty & Personal Care', NULL, 'beauty-personal-care', 1, 0, 'uploads/category/1752049059_3d_cortoon_beauty_personal_care_image.jpeg', '<p>Discover top beauty and grooming products for your daily routine.<br>From skincare to haircare, glow up with trusted personal care essentials.</p>'),
(5, 'Health & Wellness', NULL, 'health-wellness', 1, 0, 'uploads/category/1752049015_health_wellness_3d_cortoon_image.jpeg', '<p>Prioritize your well-being with trusted health and wellness essentials.<br>From supplements to self-care, find everything for a healthier lifestyle.</p>'),
(6, 'Baby & Kids', NULL, 'baby-kids', 1, 0, 'uploads/category/1752048964_baby_kids_category_images_genrate_3d.jpeg', '<p>Everything your little ones need — from newborn essentials to playful toys.<br>Safe, comfortable, and fun products for babies and growing kids.</p>'),
(7, 'Sports & Outdoors', NULL, 'sports-outdoors', 1, 0, 'uploads/category/1752048860_sports_outdoors_3d_cortoon_image.jpeg', '<p>Fuel your active lifestyle with top-notch sports gear and outdoor essentials.<br>From fitness equipment to camping tools, get ready for every adventure.</p>'),
(8, 'Books & Stationery', NULL, 'books-stationery', 1, 0, 'uploads/category/1752048808_books_stationery_3d_cortoon_image.jpeg', '<p>Dive into a world of knowledge with books for every reader and age.<br>Plus, shop quality stationery for school, office, and creative needs.</p>'),
(9, 'Automotive', NULL, 'automotive', 1, 0, 'uploads/category/1752048754_3d_cortoon_image_for_automotive_category.jpeg', '<p>Gear up with top-quality automotive parts, tools, and accessories.<br>Everything you need to maintain, upgrade, and style your ride.</p>'),
(10, 'Grocery & Essentials', NULL, 'grocery-essentials', 1, 0, 'uploads/category/1752048692_grocery_essentials_3d_cortoon.jpeg', '<p>Stock up on daily essentials and fresh groceries at great prices. From pantry staples to personal care, everything you need in one place.</p>'),
(11, 'Mobiles', 2, 'mobiles', 1, 0, 'uploads/category/1752048515_mobile_phone_3d_image_for_3d_cortoon.jpeg', '<p>Stay connected with the latest smartphones packed with smart features.<br>Shop top brands offering powerful performance, sleek design, and great value.</p>'),
(12, 'Laptops', 2, 'laptops', 1, 0, 'uploads/category/1752048448_laptops_3d_cortoon.jpeg', '<p>Explore powerful and portable laptops for work, gaming, and everyday use.<br>Choose from top brands with the latest features and performance.</p>'),
(13, 'Cameras', 2, 'cameras', 1, 0, 'uploads/category/1752048403_electronic_cameras_image_cortoon_3d.jpeg', '<p>Capture life’s moments in stunning detail with our range of cameras.<br>From DSLRs to action cams, find the perfect gear for every shot.</p>'),
(14, 'Accessories', 2, 'accessories', 1, 0, 'uploads/category/1752048335_electronic_accessories_category_image_3d_cortoon.jpeg', '<p>Enhance your tech experience with the latest electronic accessories.<br>Shop chargers, cables, earbuds, and more — built for performance and style.</p>'),
(15, 'Men', 1, 'men', 1, 0, 'uploads/category/1752048274_man_professonal_dress_category_cortoon_image_3d.jpeg', '<p>Discover adorable and comfortable dresses for every little adventure.<br>Perfect styles for playtime, parties, and everything in between.</p>'),
(16, 'Women', 1, 'women', 1, 0, 'uploads/category/1752048135_20_year_woman_dress_category_image_3d.jpeg', '<p>Discover adorable and comfortable dresses for every little adventure.<br>Perfect styles for playtime, parties, and everything in between.</p>'),
(17, 'Kids', 1, 'kids', 1, 0, 'uploads/category/1752048080_kids_dress_category_image_3d.jpeg', '<p>Discover adorable and comfortable dresses for every little adventure.<br>Perfect styles for playtime, parties, and everything in between.</p>'),
(18, 'Footwear', 1, 'footwear', 1, 0, 'uploads/category/1752048018_footware_category_3d_image_for_my_mlm.jpeg', '<p>Step into comfort and style with our latest footwear collection.<br>From casual shoes to formal wear, find the perfect fit for every occasion.</p>');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image`) VALUES
(4, 3, '1751380080_crypto_landing.png'),
(5, 3, '1751380080_gym_landing1.png'),
(6, 3, '1751380080_mlm_landing.png'),
(7, 3, '1751380449_agency_landing.png'),
(8, 4, '1751613489_before-update.png'),
(9, 4, '1751613489_Screenshot_2025-05-31_110128.png'),
(10, 4, '1751613489_Screenshot_2025-05-31_110215.png'),
(11, 5, '1751617298_Screenshot_2025-05-31_1101281.png'),
(12, 5, '1751617298_Screenshot_2025-05-31_110215.png'),
(13, 6, '1751627668_mlm-software-free-trial.png'),
(14, 6, '1751627668_mlm-demo.png'),
(15, 6, '1751627668_nexman-mlm-software-price1.png'),
(16, 6, '1751627668_best-mlm-software-demo.png'),
(17, 7, '1752050435_wireless_bluetooth_headphone_product_image_left_and_(2).jpeg'),
(18, 7, '1752050435_wireless_bluetooth_headphone_product_image_left_and_(1).jpeg'),
(19, 7, '1752050435_wireless_bluetooth_headphone_product_image_left_and.jpeg'),
(20, 8, '1752050802_men_s_classic_cotton_t_shirt_ts_mc100_(2).jpeg'),
(21, 8, '1752050802_men_s_classic_cotton_t_shirt_ts_mc100_(1).jpeg'),
(22, 8, '1752050802_men_s_classic_cotton_t_shirt_ts_mc1001.jpeg'),
(23, 9, '1752051121_3d_image_organic_baby_skin_lotion_this_(2).jpeg'),
(24, 9, '1752051121_3d_image_organic_baby_skin_lotion_this_(1).jpeg'),
(25, 9, '1752051121_3d_image_organic_baby_skin_lotion_this1.jpeg'),
(26, 10, '1752051384_herbal_face_wash_with_neem_aloe_(2).jpeg'),
(27, 10, '1752051384_herbal_face_wash_with_neem_aloe_(1).jpeg'),
(28, 10, '1752051384_herbal_face_wash_with_neem_aloe1.jpeg'),
(29, 11, '1752051640_apple_iphone_14_pro_product_thumbnail_(3).jpeg'),
(30, 11, '1752051640_apple_iphone_14_pro_product_thumbnail_(2).jpeg'),
(31, 11, '1752051640_apple_iphone_14_pro_product_thumbnail_(1).jpeg'),
(32, 11, '1752051640_apple_iphone_14_pro_product_thumbnail1.jpeg'),
(33, 12, '1752052246_samsung_galaxy_s23_ultra_product_thumbnail_(3)1.jpeg'),
(34, 12, '1752052246_samsung_galaxy_s23_ultra_product_thumbnail_(2).jpeg'),
(35, 12, '1752052246_samsung_galaxy_s23_ultra_product_thumbnail_(1).jpeg'),
(36, 12, '1752052246_samsung_galaxy_s23_ultra_product_thumbnail.jpeg'),
(37, 13, '1752052801_adidas_lite_racer_3_0_running_shoes_(2).jpeg'),
(38, 13, '1752052801_adidas_lite_racer_3_0_running_shoes_(1).jpeg'),
(39, 13, '1752052801_adidas_lite_racer_3_0_running_shoes1.jpeg'),
(40, 14, '1752053568_sony_eos_1500d_dslr_camera_18_55mm_lens_(2).jpeg'),
(41, 14, '1752053568_sony_eos_1500d_dslr_camera_18_55mm_lens_(1).jpeg'),
(42, 14, '1752053568_sony_eos_1500d_dslr_camera_18_55mm_lens1.jpeg'),
(43, 15, '1752053927_philips_x_tremevision_g_force_h4_bulbs_for_car_(3).jpeg'),
(44, 15, '1752053927_philips_x_tremevision_g_force_h4_bulbs_for_car_(2).jpeg'),
(45, 15, '1752053927_philips_x_tremevision_g_force_h4_bulbs_for_car_(1).jpeg'),
(46, 15, '1752053927_philips_x_tremevision_g_force_h4_bulbs_for_car1.jpeg'),
(47, 16, '1752054185_prestige_baby_food_steamer_blender_3d_(3).jpeg'),
(48, 16, '1752054185_prestige_baby_food_steamer_blender_3d_(2).jpeg'),
(49, 16, '1752054185_prestige_baby_food_steamer_blender_3d_(1).jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `product_meta`
--

CREATE TABLE `product_meta` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `meta_key` varchar(100) DEFAULT NULL,
  `meta_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_meta`
--

INSERT INTO `product_meta` (`id`, `product_id`, `meta_key`, `meta_value`) VALUES
(1, 1, 'size', 'S'),
(2, 1, 'asdf', 'asdf'),
(3, 1, 'asdf', 'asdf'),
(4, 2, 'size', 'S'),
(5, 2, 'asdf', 'asdf'),
(6, 1, 'size', 'M'),
(7, 1, 'size', 'L'),
(8, 1, 'size', 'XL'),
(9, 1, 'asdf', 'asdf'),
(10, 1, 'asdf', 'asdfasdf'),
(11, 1, 'asdf', 'asdfasdf'),
(42, 3, 'asdf', 'asdf'),
(43, 3, 'asdfasdf', 'asdfasdfasdf'),
(68, 4, 'asdf', 'asdf'),
(69, 4, 'asdf', 'asdf'),
(70, 4, 'asdf', 'asdf'),
(71, 5, '23', 'asdf'),
(72, 6, 'asd', 'fasdf'),
(73, 6, 'asdf', 'asdf'),
(74, 6, 'asdfasdf', 'asdfas'),
(75, 6, 'dfasdfasdf', 'asdasdf'),
(86, 7, 'Bluetooth Version', '5.3'),
(87, 7, 'Battery Life', 'Up to 20 hours'),
(88, 7, 'Charging Time', '1.5 hours'),
(89, 7, 'Noise Cancellation', 'Yes'),
(90, 7, 'Driver Size', '40mm'),
(91, 7, 'Warranty', '1 Year'),
(92, 7, 'Weight', '0.6 kg'),
(93, 7, 'Compatible Devices', 'Android, iOS, Laptop, PC'),
(94, 7, 'Included in Box', 'Headphones, Charging Cable, Manual'),
(95, 7, 'Model', 'WH-XB700BT-BLK (with color variation)'),
(110, 8, 'Fabric', '100% Cotton'),
(111, 8, 'Fit Type', 'Regular Fit'),
(112, 8, 'Sleeve Type', 'Half Sleeve'),
(113, 8, 'Pattern', 'Solid'),
(114, 8, 'Ideal For', 'Men'),
(115, 8, 'Occasion', 'Casual, Daily Wear'),
(116, 8, 'Country of Origin', 'India'),
(123, 9, 'Suitable Age', '0+ months'),
(124, 9, 'Skin Type', 'All (sensitive-safe)'),
(125, 9, 'Usage', 'Face & Body'),
(126, 9, 'Shelf Life', '24 months'),
(127, 9, 'Dermatologist Tested', 'Yes'),
(128, 9, 'Organic Certified', 'Yes'),
(133, 10, 'Suitable For', 'Men & Women'),
(134, 10, 'Skin Type', 'Oily to Combination'),
(135, 10, 'Key Ingredients', 'Neem, Aloe Vera'),
(136, 10, 'Shelf Life', '24 months'),
(143, 11, 'Display', '6.1\" Super Retina XDR'),
(144, 11, 'Processor', 'A16 Bionic'),
(145, 11, 'Operating System', 'iOS 17'),
(146, 11, 'Camera', '48MP + 12MP + 12MP'),
(147, 11, 'Water Resistance', 'IP68 Certified'),
(148, 11, 'Warranty', '1 Year Manufacturer'),
(170, 12, 'Display', '6.8\" Edge QHD+ AMOLED'),
(171, 12, 'Processor', 'Snapdragon 8 Gen 2 (4nm)'),
(172, 12, 'Operating System', 'Android 13 (One UI 5.1)'),
(173, 12, 'Main Camera', '200MP + 12MP + 10MP + 10MP'),
(174, 12, 'Water Resistance', 'IP68 Certified'),
(175, 12, 'Country of Origin', 'South Korea / Vietnam'),
(176, 12, 'Warranty', '1 Year Manufacturer'),
(184, 13, 'Type', 'Physical Product'),
(185, 13, 'Weight', '0.9'),
(186, 13, 'Dimensions', '33 × 19 × 12 cm'),
(187, 13, 'Material', 'Mesh Upper + Rubber Sole'),
(188, 13, 'Closure', 'Lace-Up'),
(189, 13, 'Suitable For', 'Daily Wear, Training, Travel'),
(190, 13, 'Brand Origin', 'Germany'),
(196, 14, 'Screen', '3.0\" TFT LCD (Live View)'),
(197, 14, 'Connectivity', 'Wi-Fi, NFC, micro USB'),
(198, 14, 'Battery Life', '500 shots per charge'),
(199, 14, 'Format Support', 'JPEG, RAW, MP4'),
(200, 14, 'Storage', 'SD / SDHC / SDXC'),
(207, 15, 'Voltage / Wattage', '12V / 60W (High) / 55W (Low)'),
(208, 15, 'Base Type', 'P43t (H4)'),
(209, 15, 'Color Temperature', '3700K Warm White'),
(210, 15, 'Certification', 'ECE R37 / DOT Approved'),
(211, 15, 'Country of Origin', 'Germany / Poland'),
(212, 15, 'Warranty', '1 Year Manufacturer Warranty'),
(218, 16, 'Age Group', '6+ Months'),
(219, 16, 'Material', 'BPA-Free Plastic'),
(220, 16, 'Safety Certifications', 'CE Certified, ISO Compliant'),
(221, 16, 'Warranty', '1 Year Prestige Warranty'),
(222, 16, 'Return Policy', '7-Day Replacement'),
(224, 17, 'Odio repellendus Vo', 'Ex elit enim sint a');

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quick_tasks`
--

CREATE TABLE `quick_tasks` (
  `id` int(11) NOT NULL,
  `code` varchar(30) NOT NULL,
  `title` varchar(100) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `icon` varchar(60) DEFAULT NULL,
  `icon_bg` varchar(30) DEFAULT NULL,
  `icon_color` varchar(30) DEFAULT NULL,
  `reward_usd` decimal(12,2) NOT NULL DEFAULT 0.00,
  `action_type` enum('claim','verify') NOT NULL DEFAULT 'claim',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rank_certificates`
--

CREATE TABLE `rank_certificates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `certificate_no` varchar(48) NOT NULL COMMENT 'BMAN-GOLD-2026-000001',
  `user_id` int(11) NOT NULL,
  `rank_id` int(10) UNSIGNED NOT NULL,
  `rank_name` varchar(40) NOT NULL,
  `generated_date` date NOT NULL,
  `certificate_pdf` varchar(255) DEFAULT NULL COMMENT 'uploads/rank_certificates/*.pdf',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Rank certificates — UNIQUE(user,rank) prevents duplicates';

--
-- Dumping data for table `rank_certificates`
--

INSERT INTO `rank_certificates` (`id`, `certificate_no`, `user_id`, `rank_id`, `rank_name`, `generated_date`, `certificate_pdf`, `created_at`) VALUES
(2, 'BMAN-UNRANK-2026-000001', 999999101, 1, 'UN RANK', '2026-08-05', NULL, '2026-08-05 20:11:15');

-- --------------------------------------------------------

--
-- Table structure for table `rank_certificate_series`
--

CREATE TABLE `rank_certificate_series` (
  `year` smallint(5) UNSIGNED NOT NULL,
  `rank_id` int(10) UNSIGNED NOT NULL,
  `last_no` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rank_certificate_series`
--

INSERT INTO `rank_certificate_series` (`year`, `rank_id`, `last_no`) VALUES
(2026, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `rank_config`
--

CREATE TABLE `rank_config` (
  `id` int(11) NOT NULL,
  `rank_name` varchar(250) DEFAULT NULL,
  `rank_status` int(11) NOT NULL DEFAULT 1,
  `left_leg_investment` varchar(250) DEFAULT NULL,
  `right_leg_investment` varchar(250) DEFAULT NULL,
  `pairs_needed` int(11) NOT NULL DEFAULT 0,
  `directs_needed` int(11) NOT NULL DEFAULT 0,
  `pair_value` decimal(12,2) NOT NULL DEFAULT 1.00,
  `cycle_type` enum('WEEK','MONTH') NOT NULL DEFAULT 'WEEK',
  `team_volume_need` decimal(12,2) NOT NULL DEFAULT 0.00,
  `create_date` datetime DEFAULT NULL,
  `rank_bonus` varchar(250) DEFAULT NULL,
  `rank_bonus_type` int(11) DEFAULT 1,
  `rank_eligibel_amt` varchar(250) DEFAULT '0',
  `rank_order` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rank_config`
--

INSERT INTO `rank_config` (`id`, `rank_name`, `rank_status`, `left_leg_investment`, `right_leg_investment`, `pairs_needed`, `directs_needed`, `pair_value`, `cycle_type`, `team_volume_need`, `create_date`, `rank_bonus`, `rank_bonus_type`, `rank_eligibel_amt`, `rank_order`) VALUES
(1, 'Executive', 1, '100', '100.1', 0, 0, 1.00, 'WEEK', 0.00, '2025-03-27 13:47:49', '500', 0, '5000', 1),
(3, 'Elite', 1, '200', '200', 0, 0, 1.00, 'WEEK', 0.00, '2025-03-27 13:48:19', '800.0000', 0, '0', 2),
(5, 'IRON', 1, NULL, NULL, 0, 0, 1.00, 'WEEK', 0.00, '2026-07-01 11:02:25', '7500', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `rank_cron_runs`
--

CREATE TABLE `rank_cron_runs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `run_month` char(7) NOT NULL,
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `finished_at` datetime DEFAULT NULL,
  `status` enum('RUNNING','DONE','FAILED') NOT NULL DEFAULT 'RUNNING',
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rank_cron_state`
--

CREATE TABLE `rank_cron_state` (
  `job` varchar(40) NOT NULL,
  `cursor_pos` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `sweep_no` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `running` tinyint(1) NOT NULL DEFAULT 0,
  `heartbeat` datetime DEFAULT NULL,
  `last_run_at` datetime DEFAULT NULL,
  `last_result` varchar(255) DEFAULT NULL,
  `total_promoted` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rank_cron_state`
--

INSERT INTO `rank_cron_state` (`job`, `cursor_pos`, `sweep_no`, `running`, `heartbeat`, `last_run_at`, `last_result`, `total_promoted`) VALUES
('rank_achievement', 0, 6, 0, '2026-08-07 09:44:55', '2026-08-07 13:14:55', 'Processed 17 member(s), promoted 0.', 1),
('rank_power', 0, 0, 0, '2026-08-07 09:44:55', '2026-08-07 13:14:55', 'Cycle #1: 17 member(s) calculated, 0 qualified for group incentive.', 0);

-- --------------------------------------------------------

--
-- Table structure for table `rank_files`
--

CREATE TABLE `rank_files` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `rank_id` int(10) UNSIGNED DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size` int(10) UNSIGNED DEFAULT NULL,
  `is_image` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rank_files`
--

INSERT INTO `rank_files` (`id`, `title`, `rank_id`, `file_path`, `file_name`, `mime_type`, `file_size`, `is_image`, `status`, `uploaded_by`, `created_at`) VALUES
(7, 'Nexman', NULL, 'uploads/rank_files/6687bed3749e0e0f512c3223aa5c95e6.pdf', 'Nexman Brochure.pdf', 'application/pdf', 1958912, 0, 1, 1, '2026-07-30 15:15:50'),
(8, 'Nexman Images', NULL, 'uploads/rank_files/088009e2a491c5e26e0dc052a8fb17b4.jpeg', 'WhatsApp Image 2026-07-30 at 5_03_56 PM.jpeg', 'image/jpeg', 595968, 1, 1, 1, '2026-07-30 15:15:50'),
(9, 'Nexman images', NULL, 'uploads/rank_files/c1b27e5ce9a62126c459c2f4906c0453.jpeg', 'WhatsApp Image 2026-07-30 at 5_03_56 PM (2).jpeg', 'image/jpeg', 588800, 1, 1, 1, '2026-07-30 15:15:50');

-- --------------------------------------------------------

--
-- Table structure for table `rank_rewards`
--

CREATE TABLE `rank_rewards` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `rank_id` int(10) UNSIGNED NOT NULL,
  `rank_name` varchar(40) NOT NULL COMMENT 'denormalised for reporting',
  `reward_type` enum('bman','usdt','physical') NOT NULL,
  `reward_amount` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `reward_status` enum('pending','paid','failed','skipped') NOT NULL DEFAULT 'pending',
  `wallet_ledger_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'proof of payment',
  `note` varchar(255) DEFAULT NULL,
  `rewarded_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Rank reward issuance — UNIQUE(user,rank,type) prevents double payment';

-- --------------------------------------------------------

--
-- Table structure for table `roi_credits`
--

CREATE TABLE `roi_credits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `invest_id` bigint(20) UNSIGNED NOT NULL,
  `package_id` bigint(20) UNSIGNED DEFAULT NULL,
  `credit_date` date NOT NULL,
  `day_no` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `roi_percent` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `base_amount` decimal(18,6) NOT NULL DEFAULT 0.000000,
  `amount` decimal(18,6) NOT NULL DEFAULT 0.000000,
  `token_amount` decimal(18,6) NOT NULL DEFAULT 0.000000,
  `coin_type` tinyint(1) NOT NULL DEFAULT 1,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roi_cron_execution`
--

CREATE TABLE `roi_cron_execution` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `execution_date` date NOT NULL,
  `execution_day` int(11) NOT NULL COMMENT '1-31 day of month',
  `cron_type` enum('monthly_payment','maturity_payout','retry_failed') NOT NULL,
  `status` enum('pending','running','success','failed') DEFAULT 'pending',
  `total_stakes_processed` int(11) DEFAULT 0,
  `total_stakes_failed` int(11) DEFAULT 0,
  `total_amount_distributed` decimal(20,8) DEFAULT 0.00000000,
  `total_gas_fees_charged` decimal(20,8) DEFAULT 0.00000000,
  `error_logs` longtext DEFAULT NULL,
  `execution_time_ms` int(11) DEFAULT 0,
  `retry_count` int(11) DEFAULT 0,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks each cron execution with status and performance metrics';

-- --------------------------------------------------------

--
-- Table structure for table `roi_distribution`
--

CREATE TABLE `roi_distribution` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `staking_swap_orders_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `principal_amount` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `duration_years` int(11) NOT NULL DEFAULT 1,
  `roi_rate_percent` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `total_roi_earned` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `roi_already_paid` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `roi_remaining` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `bonus_amount` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `purchase_date` datetime NOT NULL,
  `maturity_date` datetime NOT NULL,
  `days_elapsed` int(11) NOT NULL DEFAULT 0,
  `is_matured` tinyint(1) NOT NULL DEFAULT 0,
  `distribution_status` enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  `distribution_date` datetime DEFAULT NULL,
  `tx_hash` varchar(255) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roi_distribution_audit`
--

CREATE TABLE `roi_distribution_audit` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `stake_id` bigint(20) UNSIGNED DEFAULT NULL,
  `roi_type` enum('monthly','maturity','retry') NOT NULL COMMENT 'Type of ROI distribution',
  `plan_type` enum('fixed','regular','combo') NOT NULL COMMENT 'Staking plan type',
  `duration_years` int(11) NOT NULL,
  `principal_amount` decimal(20,8) NOT NULL,
  `roi_rate_percent` decimal(10,4) NOT NULL,
  `roi_amount` decimal(20,8) NOT NULL COMMENT 'Actual ROI distributed',
  `payment_date` date NOT NULL COMMENT 'Date ROI was supposed to be paid',
  `actual_payment_date` datetime NOT NULL COMMENT 'When ROI was actually distributed',
  `execution_date` date NOT NULL COMMENT 'Cron execution date',
  `wallet_type` varchar(50) NOT NULL COMMENT 'earning, staking, etc',
  `tx_hash` varchar(255) DEFAULT NULL COMMENT 'Blockchain transaction hash',
  `status` enum('pending','processing','success','failed','retry') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `retry_count` int(11) DEFAULT 0,
  `ledger_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Reference to wallet_ledger',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks all ROI distributions with validation and retry logic';

-- --------------------------------------------------------

--
-- Table structure for table `roi_failed_transactions`
--

CREATE TABLE `roi_failed_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `roi_audit_id` bigint(20) UNSIGNED NOT NULL,
  `roi_amount` decimal(20,8) NOT NULL,
  `failure_reason` text NOT NULL,
  `failure_code` varchar(50) DEFAULT NULL,
  `gas_fee_issue` tinyint(1) DEFAULT 0 COMMENT '1 if failure was due to insufficient gas',
  `retry_count` int(11) DEFAULT 0,
  `max_retries` int(11) DEFAULT 3,
  `next_retry_at` datetime DEFAULT NULL,
  `last_retry_at` datetime DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `status` enum('failed','pending_retry','resolved') DEFAULT 'failed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Queue for failed ROI transactions requiring retry';

-- --------------------------------------------------------

--
-- Table structure for table `roi_gas_budget`
--

CREATE TABLE `roi_gas_budget` (
  `id` int(10) UNSIGNED NOT NULL,
  `budget_type` enum('monthly','daily') DEFAULT 'monthly',
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `total_budget_usdt` decimal(20,8) NOT NULL,
  `total_spent_usdt` decimal(20,8) DEFAULT 0.00000000,
  `remaining_usdt` decimal(20,8) DEFAULT 0.00000000,
  `transaction_count` int(11) DEFAULT 0,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Track gas fee budget and spending limits';

-- --------------------------------------------------------

--
-- Table structure for table `roi_gas_fees`
--

CREATE TABLE `roi_gas_fees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `roi_audit_id` bigint(20) UNSIGNED NOT NULL,
  `transaction_type` varchar(50) NOT NULL COMMENT 'monthly, maturity, retry',
  `gas_fee_usdt` decimal(18,8) NOT NULL COMMENT 'Gas fee in USDT',
  `gas_fee_bman` decimal(20,8) DEFAULT NULL COMMENT 'If paid in BMAN',
  `gas_price_gwei` decimal(18,8) DEFAULT NULL,
  `gas_limit` bigint(20) DEFAULT NULL,
  `block_number` bigint(20) DEFAULT NULL,
  `tx_hash` varchar(255) DEFAULT NULL COMMENT 'Gas transaction hash',
  `status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `payment_date` date NOT NULL,
  `paid_at` datetime DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Track gas fees for each ROI distribution';

-- --------------------------------------------------------

--
-- Table structure for table `roi_maturity_schedule`
--

CREATE TABLE `roi_maturity_schedule` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `stake_id` bigint(20) UNSIGNED NOT NULL,
  `plan_type` enum('fixed','regular','combo') NOT NULL,
  `maturity_date` date NOT NULL,
  `principal_amount` decimal(20,8) NOT NULL,
  `roi_rate_percent` decimal(10,4) NOT NULL,
  `expected_roi_amount` decimal(20,8) NOT NULL COMMENT 'Total ROI for the term',
  `fixed_roi_amount` decimal(20,8) DEFAULT NULL COMMENT 'For fixed/combo - amount due at maturity',
  `regular_roi_amount` decimal(20,8) DEFAULT NULL COMMENT 'For regular/combo - already distributed',
  `distributed` tinyint(1) DEFAULT 0 COMMENT '1 if maturity ROI has been paid',
  `distributed_at` datetime DEFAULT NULL,
  `tx_hash` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks maturity dates for each stake with expected ROI amounts';

-- --------------------------------------------------------

--
-- Table structure for table `roi_monthly_schedule`
--

CREATE TABLE `roi_monthly_schedule` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `stake_id` bigint(20) UNSIGNED NOT NULL,
  `plan_type` enum('fixed','regular','combo') NOT NULL,
  `payment_month_year` date NOT NULL COMMENT 'First day of month (e.g., 2026-07-01)',
  `payment_days` varchar(50) NOT NULL COMMENT 'Days of month (e.g., "5,15,25")',
  `monthly_roi_amount` decimal(20,8) NOT NULL COMMENT 'Total monthly payment',
  `per_payment_amount` decimal(20,8) NOT NULL COMMENT 'Per-day amount (monthly/3)',
  `payments_completed` int(11) DEFAULT 0 COMMENT '0-3 payments executed this month',
  `total_paid_month` decimal(20,8) DEFAULT 0.00000000,
  `total_gas_fees_month` decimal(20,8) DEFAULT 0.00000000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks expected monthly ROI payments and completion status';

-- --------------------------------------------------------

--
-- Table structure for table `roi_regular_payment_days`
--

CREATE TABLE `roi_regular_payment_days` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `roi_staking_management_id` bigint(20) UNSIGNED NOT NULL,
  `cycle_no` int(10) UNSIGNED NOT NULL,
  `day_of_month` tinyint(3) UNSIGNED NOT NULL,
  `scheduled_date` datetime NOT NULL,
  `amount` decimal(30,8) NOT NULL,
  `status` enum('pending','completed') NOT NULL DEFAULT 'pending',
  `paid_date` datetime DEFAULT NULL,
  `tx_hash` varchar(120) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roi_regular_payment_days`
--

INSERT INTO `roi_regular_payment_days` (`id`, `roi_staking_management_id`, `cycle_no`, `day_of_month`, `scheduled_date`, `amount`, `status`, `paid_date`, `tx_hash`, `created_at`) VALUES
(4, 43, 1, 7, '2026-08-07 00:00:01', 0.00766666, 'completed', '2026-08-07 13:54:44', 'DRYRUN-ROI-RESTAKE-20260807-70988204-ROI-M1-D7', '2026-08-07 17:24:44'),
(5, 43, 1, 8, '2026-08-07 00:00:01', 0.00766666, 'completed', '2026-08-07 13:56:12', 'DRYRUN-ROI-RESTAKE-20260807-70988204-ROI-M1-D8', '2026-08-07 17:24:44'),
(6, 43, 1, 9, '2026-08-07 00:00:01', 0.00766668, 'completed', '2026-08-07 13:56:12', 'DRYRUN-ROI-RESTAKE-20260807-70988204-ROI-M1-D9', '2026-08-07 17:24:44'),
(10, 43, 2, 7, '2026-08-07 00:00:01', 0.00766666, 'completed', '2026-08-07 14:15:06', 'DRYRUN-ROI-RESTAKE-20260807-70988204-ROI-M2-D7', '2026-08-07 17:45:06'),
(11, 43, 2, 8, '2026-08-08 00:00:01', 0.00766666, 'completed', '2026-08-08 11:57:31', '0xd64cb1a90b860a9be1974e768ffa2afbc713560040d89dbea444361fd92d40a7', '2026-08-07 17:45:06'),
(12, 43, 2, 9, '2026-08-09 00:00:01', 0.00766668, 'pending', NULL, NULL, '2026-08-07 17:45:06'),
(13, 44, 1, 10, '2026-09-10 14:15:57', 0.01150000, 'pending', NULL, NULL, '2026-08-07 17:54:51'),
(14, 44, 1, 20, '2026-09-20 14:15:57', 0.01150000, 'pending', NULL, NULL, '2026-08-07 17:54:51'),
(15, 45, 1, 7, '2026-08-07 00:00:01', 0.00500000, 'completed', '2026-08-07 14:24:51', 'DRYRUN-ROI-RESTAKE-20260807-A3F584EE-ROI-M1-D7', '2026-08-07 17:54:51'),
(16, 45, 1, 8, '2026-08-08 00:00:01', 0.00500000, 'completed', '2026-08-08 11:57:49', '0xe0762834009c2897859ecf16ba633093bfa549787278bfd60c1f42ce43b82382', '2026-08-07 17:54:51'),
(17, 45, 1, 9, '2026-08-09 00:00:01', 0.00500000, 'pending', NULL, NULL, '2026-08-07 17:54:51'),
(18, 46, 1, 7, '2026-09-07 14:43:24', 0.01333333, 'pending', NULL, NULL, '2026-08-07 18:16:20'),
(19, 46, 1, 8, '2026-09-08 14:43:24', 0.01333333, 'pending', NULL, NULL, '2026-08-07 18:16:20'),
(20, 46, 1, 9, '2026-09-09 14:43:24', 0.01333334, 'pending', NULL, NULL, '2026-08-07 18:16:20'),
(21, 48, 1, 7, '2026-08-07 19:52:53', 0.00383333, 'completed', '2026-08-08 11:58:03', '0x86319157cba2464e509bc5fc6f7c4eeec7efae3ba409e0d2379a82d4919381ef', '2026-08-08 11:57:49'),
(22, 48, 1, 8, '2026-08-08 19:52:53', 0.00383333, 'pending', NULL, NULL, '2026-08-08 11:57:49'),
(23, 48, 1, 9, '2026-08-09 19:52:53', 0.00383334, 'pending', NULL, NULL, '2026-08-08 11:57:49'),
(24, 49, 1, 7, '2026-08-07 20:18:28', 0.00833333, 'completed', '2026-08-08 11:58:11', '0x19938244ec6941425ce5e2490df527c869c1499f95d838f6dce807b54ce0626a', '2026-08-08 11:58:03'),
(25, 49, 1, 8, '2026-08-08 20:18:28', 0.00833333, 'pending', NULL, NULL, '2026-08-08 11:58:03'),
(26, 49, 1, 9, '2026-08-09 20:18:28', 0.00833334, 'pending', NULL, NULL, '2026-08-08 11:58:03');

-- --------------------------------------------------------

--
-- Table structure for table `roi_staking_management`
--

CREATE TABLE `roi_staking_management` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `staking_swap_orders_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_stakes_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `ref` varchar(100) NOT NULL,
  `plan_type` enum('fixed','regular','combo') NOT NULL DEFAULT 'fixed',
  `credit_mode` enum('flat','per_day') NOT NULL DEFAULT 'flat',
  `credit_days_snapshot` varchar(40) DEFAULT NULL,
  `principal_amount` decimal(20,8) NOT NULL,
  `roi_rate_percent` decimal(10,4) NOT NULL,
  `total_roi_amount` decimal(20,8) NOT NULL,
  `duration_years` int(11) NOT NULL,
  `fixed_payment_amount` decimal(20,8) DEFAULT NULL,
  `fixed_maturity_date` datetime DEFAULT NULL,
  `fixed_status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `fixed_paid_date` datetime DEFAULT NULL,
  `fixed_tx_hash` varchar(255) DEFAULT NULL,
  `regular_payment_amount` decimal(20,8) DEFAULT NULL,
  `regular_payment_count` int(11) DEFAULT 3,
  `regular_payments_completed` int(11) DEFAULT 0,
  `payment_day_5_amount` decimal(20,8) DEFAULT NULL,
  `payment_day_5_date` datetime DEFAULT NULL,
  `payment_day_5_status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `payment_day_5_tx_hash` varchar(255) DEFAULT NULL,
  `payment_day_15_amount` decimal(20,8) DEFAULT NULL,
  `payment_day_15_date` datetime DEFAULT NULL,
  `payment_day_15_status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `payment_day_15_tx_hash` varchar(255) DEFAULT NULL,
  `payment_day_25_amount` decimal(20,8) DEFAULT NULL,
  `payment_day_25_date` datetime DEFAULT NULL,
  `payment_day_25_status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `payment_day_25_tx_hash` varchar(255) DEFAULT NULL,
  `overall_status` enum('active','in_progress','completed','failed') NOT NULL DEFAULT 'active',
  `total_paid_amount` decimal(20,8) DEFAULT 0.00000000,
  `remaining_to_pay` decimal(20,8) DEFAULT NULL,
  `gas_fee_amount` decimal(20,8) DEFAULT 0.00000000,
  `gas_paid_by` enum('admin','user','platform') DEFAULT 'admin',
  `total_gas_paid` decimal(20,8) DEFAULT 0.00000000,
  `next_payment_date` datetime DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `fixed_principal` decimal(20,8) NOT NULL DEFAULT 0.00000000 COMMENT 'principal earning the fixed rate (combo: principal x combo_fixed_pct)',
  `regular_principal` decimal(20,8) NOT NULL DEFAULT 0.00000000 COMMENT 'principal earning the monthly rate (combo: principal x combo_regular_pct)',
  `principal_return_amount` decimal(20,8) NOT NULL DEFAULT 0.00000000 COMMENT 'principal returned to the staking wallet at maturity. Combo: the REGULAR half only — the fixed half is absorbed into its gross payout.',
  `combo_fixed_pct` decimal(6,2) DEFAULT NULL COMMENT 'split snapshotted at purchase, so later admin edits never re-price a live stake',
  `combo_regular_pct` decimal(6,2) DEFAULT NULL,
  `is_special` tinyint(1) NOT NULL DEFAULT 0,
  `special_maturity_percent` decimal(8,3) NOT NULL DEFAULT 0.000,
  `special_schedule_json` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roi_staking_management`
--

INSERT INTO `roi_staking_management` (`id`, `staking_swap_orders_id`, `user_stakes_id`, `user_id`, `ref`, `plan_type`, `credit_mode`, `credit_days_snapshot`, `principal_amount`, `roi_rate_percent`, `total_roi_amount`, `duration_years`, `fixed_payment_amount`, `fixed_maturity_date`, `fixed_status`, `fixed_paid_date`, `fixed_tx_hash`, `regular_payment_amount`, `regular_payment_count`, `regular_payments_completed`, `payment_day_5_amount`, `payment_day_5_date`, `payment_day_5_status`, `payment_day_5_tx_hash`, `payment_day_15_amount`, `payment_day_15_date`, `payment_day_15_status`, `payment_day_15_tx_hash`, `payment_day_25_amount`, `payment_day_25_date`, `payment_day_25_status`, `payment_day_25_tx_hash`, `overall_status`, `total_paid_amount`, `remaining_to_pay`, `gas_fee_amount`, `gas_paid_by`, `total_gas_paid`, `next_payment_date`, `error_message`, `created_at`, `updated_at`, `fixed_principal`, `regular_principal`, `principal_return_amount`, `combo_fixed_pct`, `combo_regular_pct`, `is_special`, `special_maturity_percent`, `special_schedule_json`) VALUES
(1, 1, NULL, 3, 'ORDER-1-ROI', 'fixed', 'flat', NULL, 1.00000000, 150.0000, 1.50000000, 2, 1.50000000, '2028-07-20 22:16:29', 'pending', NULL, NULL, NULL, 3, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'active', 0.00000000, 1.50000000, 0.00000000, 'admin', 0.00000000, '2028-07-20 22:16:29', NULL, '2026-07-20 13:16:29', '2026-07-20 16:46:29', 1.00000000, 0.00000000, 1.00000000, NULL, NULL, 0, 0.000, NULL),
(2, 2, NULL, 3, 'ORDER-2-ROI', 'regular', 'flat', NULL, 1.00000000, 3.0000, 1.80000000, 5, NULL, '2031-07-23 18:33:11', 'pending', NULL, NULL, 0.03000000, 60, 1, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'in_progress', 0.03000000, 1.77000000, 0.00000000, 'admin', 0.00000000, '2026-09-06 16:44:49', NULL, '2026-07-23 09:33:11', '2026-08-07 07:44:56', 0.00000000, 1.00000000, 1.00000000, NULL, NULL, 0, 0.000, NULL),
(5, 5, NULL, 3, 'ORDER-5-ROI', 'regular', 'flat', NULL, 1.00000000, 2.0000, 0.27000000, 1, 0.03000000, '2027-07-29 20:49:03', 'pending', NULL, NULL, 0.02000000, 12, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'active', 0.00000000, 0.27000000, 0.00000000, 'admin', 0.00000000, '2026-08-29 20:49:03', NULL, '2026-07-29 11:49:03', '2026-07-29 15:19:03', 0.00000000, 1.00000000, 1.00000000, NULL, NULL, 1, 3.000, '{\"1\":2}'),
(6, 6, NULL, 2, 'ORDER-6-ROI', 'regular', 'flat', NULL, 2.00000000, 2.0000, 1.44000000, 3, NULL, '2029-07-30 18:05:10', 'pending', NULL, NULL, 0.04000000, 36, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'active', 0.00000000, 1.44000000, 0.00000000, 'admin', 0.00000000, '2026-08-30 18:05:10', NULL, '2026-07-30 09:05:10', '2026-07-30 12:35:10', 0.00000000, 2.00000000, 2.00000000, NULL, NULL, 0, 0.000, NULL),
(7, 10, NULL, 23, 'ORDER-10-ROI', 'fixed', 'flat', NULL, 2.00000000, 160.0000, 3.20000000, 3, 3.20000000, '2029-08-05 16:18:38', 'pending', NULL, NULL, NULL, 3, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'active', 0.00000000, 3.20000000, 0.00000000, 'admin', 0.00000000, '2029-08-05 16:18:38', NULL, '2026-08-05 07:18:38', '2026-08-05 10:48:38', 2.00000000, 0.00000000, 2.00000000, NULL, NULL, 0, 0.000, NULL),
(25, 27, 48, 999999604, 'ORDER-27-ROI', 'combo', 'flat', NULL, 1.00000000, 3.0000, 2.90000000, 5, 2.00000000, '2031-08-06 17:07:54', 'pending', NULL, NULL, 0.01500000, 60, 1, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'in_progress', 0.01500000, 2.88500000, 0.00000000, 'admin', 0.00000000, '2026-09-06 16:44:49', NULL, '2026-08-06 08:07:54', '2026-08-07 07:44:57', 0.50000000, 0.50000000, 0.50000000, 50.00, 50.00, 0, 0.000, NULL),
(26, 28, 49, 999999607, 'ORDER-28-ROI', 'combo', 'flat', NULL, 2.00000000, 2.5000, 5.00000000, 5, 3.50000000, '2031-08-06 17:10:30', 'pending', NULL, NULL, 0.02500000, 60, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'active', 0.00000000, 5.00000000, 0.00000000, 'admin', 0.00000000, '2026-09-06 17:10:30', NULL, '2026-08-06 08:10:30', '2026-08-06 11:40:30', 1.00000000, 1.00000000, 1.00000000, 50.00, 50.00, 1, 0.000, NULL),
(27, 29, 50, 999999608, 'ORDER-29-ROI', 'regular', 'flat', NULL, 1.00000000, 2.5000, 0.90000000, 3, NULL, '2029-08-06 16:38:14', 'pending', NULL, NULL, 0.02500000, 36, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'active', 0.00000000, 0.90000000, 0.00000000, 'admin', 0.00000000, '2026-09-06 16:38:14', NULL, '2026-08-06 16:38:14', '2026-08-06 16:38:14', 0.00000000, 1.00000000, 1.00000000, NULL, NULL, 0, 0.000, NULL),
(28, 30, 51, 999999612, 'ORDER-30-ROI', 'combo', 'flat', NULL, 2.00000000, 2.5000, 5.00000000, 5, 3.50000000, '2031-08-06 17:23:37', 'pending', NULL, NULL, 0.02500000, 60, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'active', 0.00000000, 5.00000000, 0.00000000, 'admin', 0.00000000, '2026-09-06 17:23:37', NULL, '2026-08-06 17:23:37', '2026-08-06 17:23:37', 1.00000000, 1.00000000, 1.00000000, 50.00, 50.00, 1, 0.000, NULL),
(29, 31, 52, 999999612, 'ORDER-31-ROI', 'fixed', 'flat', NULL, 1.00000000, 400.0000, 4.00000000, 5, 4.00000000, '2031-08-06 17:24:48', 'pending', NULL, NULL, NULL, 3, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'active', 0.00000000, 4.00000000, 0.00000000, 'admin', 0.00000000, '2031-08-06 17:24:48', NULL, '2026-08-06 17:24:48', '2026-08-06 17:24:48', 1.00000000, 0.00000000, 1.00000000, NULL, NULL, 0, 0.000, NULL),
(30, NULL, 53, 999999612, 'RESTAKE-20260807-E5A1B087-ROI', 'fixed', 'flat', NULL, 1.00000000, 150.0000, 1.50000000, 2, 1.50000000, '2028-08-07 00:00:00', 'pending', NULL, NULL, NULL, 3, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'active', 0.00000000, 1.50000000, 0.00000000, 'admin', 0.00000000, '2028-08-07 00:00:00', NULL, '2026-08-07 02:05:03', '2026-08-07 05:35:03', 1.00000000, 0.00000000, 1.00000000, NULL, NULL, 0, 0.000, NULL),
(31, NULL, 54, 999999612, 'RESTAKE-20260807-CBFD0802-ROI', 'fixed', 'flat', NULL, 1.00000000, 150.0000, 1.50000000, 2, 1.50000000, '2028-08-07 00:00:00', 'pending', NULL, NULL, NULL, 3, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'active', 0.00000000, 1.50000000, 0.00000000, 'admin', 0.00000000, '2028-08-07 00:00:00', NULL, '2026-08-07 03:51:02', '2026-08-07 07:21:02', 1.00000000, 0.00000000, 1.00000000, NULL, NULL, 0, 0.000, NULL),
(32, NULL, 55, 999999611, 'RESTAKE-20260807-44759964-ROI', 'fixed', 'flat', NULL, 1.00000000, 150.0000, 1.50000000, 2, 1.50000000, '2028-08-07 00:00:00', 'pending', NULL, NULL, NULL, 3, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'active', 0.00000000, 1.50000000, 0.00000000, 'admin', 0.00000000, '2028-08-07 00:00:00', NULL, '2026-08-07 04:07:45', '2026-08-07 07:37:45', 1.00000000, 0.00000000, 1.00000000, NULL, NULL, 0, 0.000, NULL),
(33, NULL, 56, 999999611, 'RESTAKE-20260807-B4075CC7-ROI', 'fixed', 'flat', NULL, 1.00000000, 150.0000, 1.50000000, 2, 1.50000000, '2028-08-07 00:00:00', 'pending', NULL, NULL, NULL, 3, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'active', 0.00000000, 1.50000000, 0.00000000, 'admin', 0.00000000, '2028-08-07 00:00:00', NULL, '2026-08-07 04:08:11', '2026-08-07 07:38:11', 1.00000000, 0.00000000, 1.00000000, NULL, NULL, 0, 0.000, NULL),
(34, NULL, 57, 999999611, 'RESTAKE-20260807-40E73E44-ROI', 'fixed', 'flat', NULL, 1.00000000, 150.0000, 1.50000000, 2, 1.50000000, '2028-08-07 00:00:00', 'pending', NULL, NULL, NULL, 3, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'active', 0.00000000, 1.50000000, 0.00000000, 'admin', 0.00000000, '2028-08-07 00:00:00', NULL, '2026-08-07 04:08:38', '2026-08-07 07:38:38', 1.00000000, 0.00000000, 1.00000000, NULL, NULL, 0, 0.000, NULL),
(35, NULL, 58, 999999611, 'RESTAKE-20260807-189C7B04-ROI', 'fixed', 'flat', NULL, 1.00000000, 150.0000, 1.50000000, 2, 1.50000000, '2028-08-07 00:00:00', 'pending', NULL, NULL, NULL, 3, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'active', 0.00000000, 1.50000000, 0.00000000, 'admin', 0.00000000, '2028-08-07 00:00:00', NULL, '2026-08-07 04:09:00', '2026-08-07 07:39:00', 1.00000000, 0.00000000, 1.00000000, NULL, NULL, 0, 0.000, NULL),
(36, NULL, 59, 999999611, 'RESTAKE-20260807-190BA688-ROI', 'fixed', 'flat', NULL, 1.00000000, 150.0000, 1.50000000, 2, 1.50000000, '2028-08-07 00:00:00', 'pending', NULL, NULL, NULL, 3, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'active', 0.00000000, 1.50000000, 0.00000000, 'admin', 0.00000000, '2028-08-07 00:00:00', NULL, '2026-08-07 04:09:25', '2026-08-07 07:39:25', 1.00000000, 0.00000000, 1.00000000, NULL, NULL, 0, 0.000, NULL),
(37, NULL, 60, 999999611, 'RESTAKE-20260807-3E8D8DEA-ROI', 'fixed', 'flat', NULL, 1.00000000, 150.0000, 1.50000000, 2, 1.50000000, '2028-08-07 00:00:00', 'pending', NULL, NULL, NULL, 3, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'active', 0.00000000, 1.50000000, 0.00000000, 'admin', 0.00000000, '2028-08-07 00:00:00', NULL, '2026-08-07 04:09:46', '2026-08-07 07:39:46', 1.00000000, 0.00000000, 1.00000000, NULL, NULL, 0, 0.000, NULL),
(38, NULL, 61, 999999611, 'RESTAKE-20260807-DEC9854E-ROI', 'fixed', 'flat', NULL, 1.00000000, 150.0000, 1.50000000, 2, 1.50000000, '2028-08-07 00:00:00', 'pending', NULL, NULL, NULL, 3, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'active', 0.00000000, 1.50000000, 0.00000000, 'admin', 0.00000000, '2028-08-07 00:00:00', NULL, '2026-08-07 04:10:19', '2026-08-07 07:40:19', 1.00000000, 0.00000000, 1.00000000, NULL, NULL, 0, 0.000, NULL),
(39, NULL, 62, 999999612, 'RESTAKE-20260807-F7CAD0A9-ROI', 'regular', 'flat', NULL, 2.00000000, 2.5000, 3.00000000, 5, NULL, '2031-08-07 00:00:00', 'pending', NULL, NULL, 0.05000000, 60, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'active', 0.00000000, 3.00000000, 0.00000000, 'admin', 0.00000000, '2026-09-07 09:59:34', NULL, '2026-08-07 04:29:34', '2026-08-07 07:59:34', 0.00000000, 2.00000000, 2.00000000, NULL, NULL, 1, 0.000, NULL),
(40, 32, 63, 999999606, 'ORDER-32-ROI', 'regular', 'flat', NULL, 1.00000000, 2.5000, 0.90000000, 3, NULL, '2029-08-07 13:38:02', 'pending', NULL, NULL, 0.02500000, 36, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'active', 0.00000000, 0.90000000, 0.00000000, 'admin', 0.00000000, '2026-09-07 13:38:02', NULL, '2026-08-07 04:38:02', '2026-08-07 08:08:02', 0.00000000, 1.00000000, 1.00000000, NULL, NULL, 0, 0.000, NULL),
(41, NULL, 64, 999999608, 'RESTAKE-20260807-0D4183FE-ROI', 'fixed', 'flat', NULL, 1.00000000, 150.0000, 1.50000000, 2, 1.50000000, '2028-08-07 00:00:00', 'pending', NULL, NULL, NULL, 3, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'active', 0.00000000, 1.50000000, 0.00000000, 'admin', 0.00000000, '2028-08-07 00:00:00', NULL, '2026-08-07 04:54:07', '2026-08-07 08:24:07', 1.00000000, 0.00000000, 1.00000000, NULL, NULL, 0, 0.000, NULL),
(42, NULL, 65, 999999606, 'RESTAKE-20260807-23FBA38B-ROI', 'combo', 'flat', NULL, 2.00000000, 2.5000, 5.00000000, 5, 3.50000000, '2026-08-06 16:16:20', 'pending', NULL, NULL, 0.02500000, 60, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'active', 0.00000000, 5.00000000, 0.00000000, 'admin', 0.00000000, '2026-08-06 16:16:20', NULL, '2026-08-07 04:58:08', '2026-08-07 10:46:20', 1.00000000, 1.00000000, 1.00000000, 50.00, 50.00, 1, 0.000, NULL),
(43, NULL, 66, 999999602, 'RESTAKE-20260807-70988204-ROI', 'regular', 'per_day', '7,8,9', 1.00000000, 2.3000, 0.55200000, 2, NULL, '2028-08-07 00:00:00', 'pending', NULL, NULL, 0.02300000, 24, 1, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'in_progress', 0.03066666, 0.52133334, 0.00000000, 'admin', 0.00000000, '2026-08-07 00:00:01', NULL, '2026-06-06 18:30:01', '2026-08-08 06:27:31', 0.00000000, 1.00000000, 1.00000000, NULL, NULL, 0, 0.000, NULL),
(44, NULL, 67, 999999602, 'RESTAKE-20260807-21BBF520-ROI', 'regular', 'per_day', '10,20', 1.00000000, 2.3000, 0.55200000, 2, NULL, '2028-08-07 00:00:00', 'pending', NULL, NULL, 0.02300000, 24, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'active', 0.00000000, 0.55200000, 0.00000000, 'admin', 0.00000000, '2026-09-10 14:15:57', NULL, '2026-08-07 08:45:57', '2026-08-07 12:15:57', 0.00000000, 1.00000000, 1.00000000, NULL, NULL, 0, 0.000, NULL),
(45, NULL, 68, 999999607, 'RESTAKE-20260807-A3F584EE-ROI', 'combo', 'per_day', '7,8,9', 1.00000000, 3.0000, 2.90000000, 5, 2.00000000, '2031-08-07 00:00:00', 'pending', NULL, NULL, 0.01500000, 60, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'in_progress', 0.01000000, 2.89000000, 0.00000000, 'admin', 0.00000000, '2026-08-07 00:00:01', NULL, '2026-07-06 18:30:01', '2026-08-08 06:27:49', 0.50000000, 0.50000000, 0.50000000, 50.00, 50.00, 0, 0.000, NULL),
(46, 33, 69, 999999613, 'ORDER-33-ROI', 'regular', 'per_day', '7,8,9', 2.00000000, 2.0000, 1.44000000, 3, NULL, '2029-08-07 18:13:23', 'pending', NULL, NULL, 0.04000000, 36, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'active', 0.00000000, 1.44000000, 0.00000000, 'admin', 0.00000000, '2026-09-07 18:13:23', NULL, '2026-08-07 09:13:24', '2026-08-07 12:43:24', 0.00000000, 2.00000000, 2.00000000, NULL, NULL, 1, 0.000, NULL),
(47, NULL, 70, 999999602, 'RESTAKE-20260807-DA3DD430-ROI', 'fixed', 'flat', NULL, 2.00000000, 120.0000, 2.40000000, 2, 2.40000000, '2028-08-07 00:00:00', 'pending', NULL, NULL, NULL, 3, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'active', 0.00000000, 2.40000000, 0.00000000, 'admin', 0.00000000, '2028-08-07 00:00:00', NULL, '2026-08-07 09:30:05', '2026-08-07 13:00:05', 2.00000000, 0.00000000, 2.00000000, NULL, NULL, 1, 0.000, NULL),
(48, NULL, 71, 999999603, 'RESTAKE-20260807-D7676BA5-ROI', 'combo', 'per_day', '7,8,9', 1.00000000, 2.3000, 1.02600000, 2, 0.75000000, '2028-08-07 00:00:00', 'pending', NULL, NULL, 0.01150000, 24, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'in_progress', 0.00383333, 1.02216667, 0.00000000, 'admin', 0.00000000, '2026-09-07 19:52:53', NULL, '2026-08-07 14:22:53', '2026-08-08 06:28:03', 0.50000000, 0.50000000, 0.50000000, 50.00, 50.00, 0, 0.000, NULL),
(49, 34, 72, 999999616, 'ORDER-34-ROI', 'combo', 'per_day', '7,8,9', 2.00000000, 2.5000, 5.00000000, 5, 3.50000000, '2031-08-07 20:18:28', 'pending', NULL, NULL, 0.02500000, 60, 0, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'pending', NULL, 'in_progress', 0.00833333, 4.99166667, 0.00000000, 'admin', 0.00000000, '2026-08-07 20:18:28', NULL, '2026-08-07 14:48:28', '2026-08-08 06:28:11', 1.00000000, 1.00000000, 1.00000000, 50.00, 50.00, 1, 0.000, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `rpc_sync_log`
--

CREATE TABLE `rpc_sync_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `run_id` varchar(40) DEFAULT NULL,
  `scope` varchar(40) NOT NULL,
  `address` varchar(120) DEFAULT NULL,
  `token` varchar(20) DEFAULT NULL,
  `endpoint` varchar(255) DEFAULT NULL,
  `api_used` varchar(20) NOT NULL DEFAULT 'rpc',
  `ok` tinyint(1) NOT NULL DEFAULT 1,
  `diff_detected` tinyint(1) DEFAULT NULL,
  `balance_before` decimal(38,18) DEFAULT NULL,
  `balance_after` decimal(38,18) DEFAULT NULL,
  `tx_imported` int(11) DEFAULT 0,
  `message` varchar(255) DEFAULT NULL,
  `duration_ms` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rpc_sync_log`
--

INSERT INTO `rpc_sync_log` (`id`, `run_id`, `scope`, `address`, `token`, `endpoint`, `api_used`, `ok`, `diff_detected`, `balance_before`, `balance_after`, `tx_imported`, `message`, `duration_ms`, `created_at`) VALUES
(1, 'batch_20260805141101_8e5bf1', 'verify', NULL, NULL, 'https://bsc-dataseed.binance.org', 'rpc', 1, NULL, NULL, NULL, 0, 'status=confirmed conf=8230', NULL, '2026-08-05 14:11:04'),
(2, 'batch_20260805141101_8e5bf1', 'verify', NULL, NULL, 'https://bsc-dataseed.binance.org', 'rpc', 1, NULL, NULL, NULL, 0, 'status=confirmed conf=8240', NULL, '2026-08-05 14:11:07'),
(3, 'batch_20260805141101_8e5bf1', 'verify', NULL, NULL, 'https://bsc-dataseed.binance.org', 'rpc', 1, NULL, NULL, NULL, 0, 'status=confirmed conf=8751', NULL, '2026-08-05 14:11:09'),
(4, 'batch_20260805141101_8e5bf1', 'verify', NULL, NULL, 'https://bsc-dataseed.binance.org', 'rpc', 1, NULL, NULL, NULL, 0, 'status=confirmed conf=8828', NULL, '2026-08-05 14:11:12'),
(5, 'batch_20260805141101_8e5bf1', 'verify', NULL, NULL, 'https://bsc-dataseed.binance.org', 'rpc', 1, NULL, NULL, NULL, 0, 'status=confirmed conf=10905', NULL, '2026-08-05 14:11:15'),
(6, 'batch_20260805141101_8e5bf1', 'verify', NULL, NULL, 'https://bsc-dataseed.binance.org', 'rpc', 1, NULL, NULL, NULL, 0, 'status=confirmed conf=1148375', NULL, '2026-08-05 14:11:18'),
(7, 'batch_20260805141101_8e5bf1', 'verify', NULL, NULL, 'https://bsc-dataseed.binance.org', 'rpc', 1, NULL, NULL, NULL, 0, 'status=confirmed conf=1148384', NULL, '2026-08-05 14:11:21'),
(8, 'batch_20260805141153_a77a12', 'import', '0xcb3c5E2bcef54Fec78974CAe31828cF9F33dA9c7', 'BNB', 'https://bsc-dataseed.binance.org', 'rpc', 1, 1, NULL, 0.002359725000000000, 0, 'balance changed (RPC only — explorer not configured)', 776, '2026-08-05 14:11:54'),
(9, 'batch_20260805141153_a77a12', 'import', '0xcb3c5E2bcef54Fec78974CAe31828cF9F33dA9c7', '', 'https://api.etherscan.io/v2/api', 'bscscan', 1, NULL, NULL, NULL, 0, 'tokentx fetched', 1496, '2026-08-05 14:11:56'),
(10, 'batch_20260805141153_a77a12', 'import', '0xcb3c5E2bcef54Fec78974CAe31828cF9F33dA9c7', 'USDT', 'https://bsc-dataseed.binance.org', 'bscscan', 1, 1, NULL, 0.000000000000000000, 8, 'balance changed — imported 8 tx via BscScan', 2492, '2026-08-05 14:11:56'),
(11, 'batch_20260805141153_a77a12', 'import', '0x365E2F051Cd601F8828cf33c3D1B7C87a0141c1b', 'BNB', 'https://bsc-dataseed.binance.org', 'rpc', 1, 1, NULL, 0.003001545000000000, 0, 'balance changed (RPC only — explorer not configured)', 665, '2026-08-05 14:11:57'),
(12, 'batch_20260805141153_a77a12', 'import', '0x365E2F051Cd601F8828cf33c3D1B7C87a0141c1b', '', 'https://api.etherscan.io/v2/api', 'bscscan', 1, NULL, NULL, NULL, 0, 'tokentx fetched', 4739, '2026-08-05 14:12:02'),
(13, 'batch_20260805141153_a77a12', 'import', '0x365E2F051Cd601F8828cf33c3D1B7C87a0141c1b', 'USDT', 'https://bsc-dataseed.binance.org', 'bscscan', 1, 1, NULL, 0.000000000000000000, 2, 'balance changed — imported 2 tx via BscScan', 5503, '2026-08-05 14:12:02'),
(14, 'batch_20260805141153_a77a12', 'import', '0x18d0B930970f05ABFC5CC08cad9346aF58D3dd24', 'BNB', 'https://bsc-dataseed.binance.org', 'rpc', 1, 1, NULL, 0.002656635000000000, 0, 'balance changed (RPC only — explorer not configured)', 810, '2026-08-05 14:12:03'),
(15, 'batch_20260805141153_a77a12', 'import', '0x18d0B930970f05ABFC5CC08cad9346aF58D3dd24', '', 'https://api.etherscan.io/v2/api', 'bscscan', 1, NULL, NULL, NULL, 0, 'tokentx fetched', 1250, '2026-08-05 14:12:05'),
(16, 'batch_20260805141153_a77a12', 'import', '0x18d0B930970f05ABFC5CC08cad9346aF58D3dd24', 'USDT', 'https://bsc-dataseed.binance.org', 'bscscan', 1, 1, NULL, 0.010000000000000000, 6, 'balance changed — imported 6 tx via BscScan', 2128, '2026-08-05 14:12:05'),
(17, 'batch_20260805141153_a77a12', 'import', '0x6A3356EEC7660058122E4387AA61F8E1aE220A7f', 'BNB', 'https://bsc-dataseed.binance.org', 'rpc', 1, 1, NULL, 0.000000000000000000, 0, 'balance changed (RPC only — explorer not configured)', 976, '2026-08-05 14:12:06'),
(18, 'batch_20260805141153_a77a12', 'import', '0x6A3356EEC7660058122E4387AA61F8E1aE220A7f', '', 'https://api.etherscan.io/v2/api', 'bscscan', 1, NULL, NULL, NULL, 0, 'tokentx fetched', 1085, '2026-08-05 14:12:08'),
(19, 'batch_20260805141153_a77a12', 'import', '0x6A3356EEC7660058122E4387AA61F8E1aE220A7f', 'USDT', 'https://bsc-dataseed.binance.org', 'rpc', 1, 1, NULL, 0.000000000000000000, 0, 'balance changed — explorer: No transactions found (RPC balance stored)', 1924, '2026-08-05 14:12:08'),
(20, 'batch_20260805141153_a77a12', 'import', '0x513fD294ADdE5dD699cF0A556Fc15fF2521892aD', 'BNB', 'https://bsc-dataseed.binance.org', 'rpc', 1, 1, NULL, 0.000000000000000000, 0, 'balance changed (RPC only — explorer not configured)', 803, '2026-08-05 14:12:09'),
(21, 'batch_20260805141153_a77a12', 'import', '0x513fD294ADdE5dD699cF0A556Fc15fF2521892aD', '', 'https://api.etherscan.io/v2/api', 'bscscan', 1, NULL, NULL, NULL, 0, 'tokentx fetched', 1405, '2026-08-05 14:12:11'),
(22, 'batch_20260805141153_a77a12', 'import', '0x513fD294ADdE5dD699cF0A556Fc15fF2521892aD', 'USDT', 'https://bsc-dataseed.binance.org', 'rpc', 1, 1, NULL, 0.000000000000000000, 0, 'balance changed — explorer: No transactions found (RPC balance stored)', 2304, '2026-08-05 14:12:11'),
(23, 'batch_20260805141153_a77a12', 'import', '0xb3A4C6e46049bE49CdB9734DbfB2897ade83Fe83', 'BNB', 'https://bsc-dataseed.binance.org', 'rpc', 1, 1, NULL, 0.003292545000000000, 0, 'balance changed (RPC only — explorer not configured)', 771, '2026-08-05 14:12:12'),
(24, 'batch_20260805141153_a77a12', 'import', '0xb3A4C6e46049bE49CdB9734DbfB2897ade83Fe83', '', 'https://api.etherscan.io/v2/api', 'bscscan', 1, NULL, NULL, NULL, 0, 'tokentx fetched', 1167, '2026-08-05 14:12:14'),
(25, 'batch_20260805141153_a77a12', 'import', '0xb3A4C6e46049bE49CdB9734DbfB2897ade83Fe83', 'USDT', 'https://bsc-dataseed.binance.org', 'bscscan', 1, 1, NULL, 0.293539907624882841, 3, 'balance changed — imported 3 tx via BscScan', 1934, '2026-08-05 14:12:14'),
(26, 'batch_20260805141153_a77a12', 'batch', NULL, NULL, NULL, 'rpc', 1, NULL, NULL, NULL, 19, 'worker=w-be61cfbd cursor=23 cycle=0 processed=6 skipped=0 changed=12 bscscan=4 rpc_only=8 rpc_fail=0', 21151, '2026-08-05 14:12:14'),
(27, 'batch_20260805141153_a77a12', 'import', '0x3088B858dc4cD85A001337f8E15a40b24666d321', 'BNB', 'https://bsc-dataseed.binance.org', 'rpc', 1, 1, NULL, 0.132495085000000000, 0, 'balance changed (RPC only — explorer not configured)', 730, '2026-08-05 14:12:15'),
(28, 'batch_20260805141153_a77a12', 'import', '0x3088B858dc4cD85A001337f8E15a40b24666d321', '', 'https://api.etherscan.io/v2/api', 'bscscan', 1, NULL, NULL, NULL, 0, 'tokentx fetched', 1498, '2026-08-05 14:12:17'),
(29, 'batch_20260805141153_a77a12', 'import', '0x3088B858dc4cD85A001337f8E15a40b24666d321', 'USDT', 'https://bsc-dataseed.binance.org', 'bscscan', 1, 1, NULL, 2.100000000000000000, 19, 'balance changed — imported 19 tx via BscScan', 2301, '2026-08-05 14:12:17'),
(30, 'batch_20260806100320_107705', 'verify', NULL, NULL, 'https://bsc-dataseed.binance.org', 'rpc', 1, NULL, NULL, NULL, 0, 'status=confirmed conf=5696', NULL, '2026-08-06 10:03:23'),
(31, 'batch_20260806100320_107705', 'verify', NULL, NULL, 'https://bsc-dataseed.binance.org', 'rpc', 1, NULL, NULL, NULL, 0, 'status=confirmed conf=5736', NULL, '2026-08-06 10:03:25'),
(32, 'batch_20260806100320_107705', 'verify', NULL, NULL, 'https://bsc-dataseed.binance.org', 'rpc', 1, NULL, NULL, NULL, 0, 'status=confirmed conf=6131', NULL, '2026-08-06 10:03:27'),
(33, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed.binance.org', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 696, '2026-08-06 10:03:28'),
(34, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed1.defibit.io', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 689, '2026-08-06 10:03:29'),
(35, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed1.ninicoin.io', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 755, '2026-08-06 10:03:29'),
(36, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc.publicnode.com', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"Invalid params\",\"data\":\"odd number of digits at line 1 column 35\"}', 942, '2026-08-06 10:03:30'),
(37, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://rpc.ankr.com/bsc', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32000,\"message\":\"Unauthorized: You must authenticate your request with an API key. Create an account on https:\\/\\/www.ankr.com\\/rpc\\/ and generate your personal API key fo', 1035, '2026-08-06 10:03:31'),
(38, 'batch_20260806100320_107705', 'verify', NULL, NULL, NULL, 'rpc', 1, NULL, NULL, NULL, 0, 'tx not yet on-chain (pending)', NULL, '2026-08-06 10:03:31'),
(39, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed.binance.org', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 671, '2026-08-06 10:03:32'),
(40, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed1.defibit.io', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 779, '2026-08-06 10:03:33'),
(41, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed1.ninicoin.io', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 724, '2026-08-06 10:03:34'),
(42, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc.publicnode.com', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"Invalid params\",\"data\":\"odd number of digits at line 1 column 35\"}', 768, '2026-08-06 10:03:34'),
(43, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://rpc.ankr.com/bsc', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32000,\"message\":\"Unauthorized: You must authenticate your request with an API key. Create an account on https:\\/\\/www.ankr.com\\/rpc\\/ and generate your personal API key fo', 892, '2026-08-06 10:03:35'),
(44, 'batch_20260806100320_107705', 'verify', NULL, NULL, NULL, 'rpc', 1, NULL, NULL, NULL, 0, 'tx not yet on-chain (pending)', NULL, '2026-08-06 10:03:35'),
(45, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed.binance.org', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 704, '2026-08-06 10:03:36'),
(46, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed1.defibit.io', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 705, '2026-08-06 10:03:37'),
(47, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed1.ninicoin.io', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 673, '2026-08-06 10:03:37'),
(48, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc.publicnode.com', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"Invalid params\",\"data\":\"invalid string length at line 1 column 34\"}', 772, '2026-08-06 10:03:38'),
(49, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://rpc.ankr.com/bsc', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32000,\"message\":\"Unauthorized: You must authenticate your request with an API key. Create an account on https:\\/\\/www.ankr.com\\/rpc\\/ and generate your personal API key fo', 846, '2026-08-06 10:03:39'),
(50, 'batch_20260806100320_107705', 'verify', NULL, NULL, NULL, 'rpc', 1, NULL, NULL, NULL, 0, 'tx not yet on-chain (pending)', NULL, '2026-08-06 10:03:39'),
(51, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed.binance.org', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 658, '2026-08-06 10:03:40'),
(52, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed1.defibit.io', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 666, '2026-08-06 10:03:40'),
(53, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed1.ninicoin.io', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 678, '2026-08-06 10:03:41'),
(54, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc.publicnode.com', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"Invalid params\",\"data\":\"odd number of digits at line 1 column 35\"}', 773, '2026-08-06 10:03:42'),
(55, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://rpc.ankr.com/bsc', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32000,\"message\":\"Unauthorized: You must authenticate your request with an API key. Create an account on https:\\/\\/www.ankr.com\\/rpc\\/ and generate your personal API key fo', 872, '2026-08-06 10:03:43'),
(56, 'batch_20260806100320_107705', 'verify', NULL, NULL, NULL, 'rpc', 1, NULL, NULL, NULL, 0, 'tx not yet on-chain (pending)', NULL, '2026-08-06 10:03:43'),
(57, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed.binance.org', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 894, '2026-08-06 10:03:44'),
(58, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed1.defibit.io', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 780, '2026-08-06 10:03:44'),
(59, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed1.ninicoin.io', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 833, '2026-08-06 10:03:45'),
(60, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc.publicnode.com', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"Invalid params\",\"data\":\"odd number of digits at line 1 column 35\"}', 1681, '2026-08-06 10:03:47'),
(61, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://rpc.ankr.com/bsc', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32000,\"message\":\"Unauthorized: You must authenticate your request with an API key. Create an account on https:\\/\\/www.ankr.com\\/rpc\\/ and generate your personal API key fo', 1032, '2026-08-06 10:03:48'),
(62, 'batch_20260806100320_107705', 'verify', NULL, NULL, NULL, 'rpc', 1, NULL, NULL, NULL, 0, 'tx not yet on-chain (pending)', NULL, '2026-08-06 10:03:48'),
(63, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed.binance.org', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 804, '2026-08-06 10:03:49'),
(64, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed1.defibit.io', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 778, '2026-08-06 10:03:50'),
(65, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed1.ninicoin.io', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 794, '2026-08-06 10:03:50'),
(66, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc.publicnode.com', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"Invalid params\",\"data\":\"invalid string length at line 1 column 34\"}', 1394, '2026-08-06 10:03:52'),
(67, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://rpc.ankr.com/bsc', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32000,\"message\":\"Unauthorized: You must authenticate your request with an API key. Create an account on https:\\/\\/www.ankr.com\\/rpc\\/ and generate your personal API key fo', 1113, '2026-08-06 10:03:53'),
(68, 'batch_20260806100320_107705', 'verify', NULL, NULL, NULL, 'rpc', 1, NULL, NULL, NULL, 0, 'tx not yet on-chain (pending)', NULL, '2026-08-06 10:03:53'),
(69, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed.binance.org', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 1070, '2026-08-06 10:03:54'),
(70, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed1.defibit.io', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 896, '2026-08-06 10:03:55'),
(71, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed1.ninicoin.io', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 838, '2026-08-06 10:03:56'),
(72, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc.publicnode.com', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"Invalid params\",\"data\":\"odd number of digits at line 1 column 35\"}', 820, '2026-08-06 10:03:57'),
(73, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://rpc.ankr.com/bsc', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32000,\"message\":\"Unauthorized: You must authenticate your request with an API key. Create an account on https:\\/\\/www.ankr.com\\/rpc\\/ and generate your personal API key fo', 1012, '2026-08-06 10:03:58'),
(74, 'batch_20260806100320_107705', 'verify', NULL, NULL, NULL, 'rpc', 1, NULL, NULL, NULL, 0, 'tx not yet on-chain (pending)', NULL, '2026-08-06 10:03:58'),
(75, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed.binance.org', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 713, '2026-08-06 10:03:58'),
(76, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed1.defibit.io', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 693, '2026-08-06 10:03:59'),
(77, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed1.ninicoin.io', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 670, '2026-08-06 10:04:00'),
(78, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc.publicnode.com', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"Invalid params\",\"data\":\"odd number of digits at line 1 column 35\"}', 750, '2026-08-06 10:04:00'),
(79, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://rpc.ankr.com/bsc', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32000,\"message\":\"Unauthorized: You must authenticate your request with an API key. Create an account on https:\\/\\/www.ankr.com\\/rpc\\/ and generate your personal API key fo', 908, '2026-08-06 10:04:01'),
(80, 'batch_20260806100320_107705', 'verify', NULL, NULL, NULL, 'rpc', 1, NULL, NULL, NULL, 0, 'tx not yet on-chain (pending)', NULL, '2026-08-06 10:04:01'),
(81, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed.binance.org', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 874, '2026-08-06 10:04:02'),
(82, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed1.defibit.io', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 742, '2026-08-06 10:04:03'),
(83, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc-dataseed1.ninicoin.io', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go value of type common.Hash\"}', 969, '2026-08-06 10:04:04'),
(84, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://bsc.publicnode.com', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32602,\"message\":\"Invalid params\",\"data\":\"invalid string length at line 1 column 34\"}', 969, '2026-08-06 10:04:05'),
(85, 'batch_20260806100320_107705', 'failover', NULL, NULL, 'https://rpc.ankr.com/bsc', 'rpc', 0, NULL, NULL, NULL, 0, 'rpc error: {\"code\":-32000,\"message\":\"Unauthorized: You must authenticate your request with an API key. Create an account on https:\\/\\/www.ankr.com\\/rpc\\/ and generate your personal API key fo', 999, '2026-08-06 10:04:06'),
(86, 'batch_20260806100320_107705', 'verify', NULL, NULL, NULL, 'rpc', 1, NULL, NULL, NULL, 0, 'tx not yet on-chain (pending)', NULL, '2026-08-06 10:04:06'),
(87, 'batch_20260806100320_107705', 'import', '0x7571092B8e7a2c76D335c70b7BD4805C92834055', 'BNB', 'https://bsc-dataseed.binance.org', 'rpc', 1, 1, NULL, 0.003001545000000000, 0, 'balance changed (RPC only — explorer not configured)', 746, '2026-08-06 10:04:07'),
(88, 'batch_20260806100320_107705', 'import', '0x7571092B8e7a2c76D335c70b7BD4805C92834055', '', 'https://api.etherscan.io/v2/api', 'bscscan', 1, NULL, NULL, NULL, 0, 'tokentx fetched', 996, '2026-08-06 10:04:08'),
(89, 'batch_20260806100320_107705', 'import', '0x7571092B8e7a2c76D335c70b7BD4805C92834055', 'USDT', 'https://bsc-dataseed.binance.org', 'bscscan', 1, 1, NULL, 0.000000000000000000, 2, 'balance changed — imported 2 tx via BscScan', 1673, '2026-08-06 10:04:08'),
(90, 'batch_20260806100320_107705', 'batch', NULL, NULL, NULL, 'rpc', 1, NULL, NULL, NULL, 2, 'worker=w-e686823f cursor=999999504 cycle=0 processed=1 skipped=1 changed=2 bscscan=1 rpc_only=1 rpc_fail=0', 47894, '2026-08-06 10:04:08'),
(91, 'batch_20260806100320_107705', 'import', '0x3088B858dc4cD85A001337f8E15a40b24666d321', 'BNB', 'https://bsc-dataseed.binance.org', 'rpc', 1, 1, 0.132495085000000000, 0.128981700000000000, 0, 'balance changed (RPC only — explorer not configured)', 704, '2026-08-06 10:04:09'),
(92, 'batch_20260806100320_107705', 'import', '0x3088B858dc4cD85A001337f8E15a40b24666d321', '', 'https://api.etherscan.io/v2/api', 'bscscan', 1, NULL, NULL, NULL, 0, 'tokentx fetched', 997, '2026-08-06 10:04:11'),
(93, 'batch_20260806100320_107705', 'import', '0x3088B858dc4cD85A001337f8E15a40b24666d321', 'USDT', 'https://bsc-dataseed.binance.org', 'bscscan', 1, 1, 2.100000000000000000, 2.200000000000000000, 2, 'balance changed — imported 2 tx via BscScan', 1668, '2026-08-06 10:04:11');

-- --------------------------------------------------------

--
-- Table structure for table `shipping_zones`
--

CREATE TABLE `shipping_zones` (
  `id` int(11) NOT NULL,
  `pincode` varchar(10) NOT NULL,
  `zone_name` varchar(100) DEFAULT NULL,
  `shipping_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cod_available` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `settings_type` varchar(250) DEFAULT NULL,
  `settings_name` varchar(250) DEFAULT NULL,
  `settings_value` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `settings_type`, `settings_name`, `settings_value`) VALUES
(1, 'captcha', 'status', '0'),
(2, 'captcha', 'sitekey', 'test435345'),
(3, 'captcha', 'secretkey', 'test34534'),
(4, 'image', 'logo', 'black_logo.png'),
(5, 'image', 'favicon', 'favicon.png'),
(6, 'image', 'mobile_logo', 'custom-1.png'),
(7, 'image', 'footer_logo', 'black_logo.png'),
(8, 'image', 'dark_logo', 'black_logo.png'),
(9, 'image', 'dark_mobile_logo', 'custom-1.png'),
(10, 'image', 'dark_footer_logo', 'black_logo.png'),
(11, 'image', 'og-img', 'favicon.png'),
(12, 'meta-settings', 'site-title', 'Nexman Crypto Community'),
(13, 'meta-settings', 'site-url', 'https://nexman.in'),
(14, 'meta-settings', 'site-keyword', 'Nexman Software – Best Multi-Level Marketing Solution for Your Business'),
(15, 'meta-settings', 'site-description', 'Nexman Software offers a powerful, user-friendly platform for network marketing businesses. Manage members, track commissions, grow your downline, and scale your business with ease. Start your MLM journey with Nexman today!'),
(16, 'meta-settings', 'site-name', 'Nexman'),
(17, 'company', 'email', 'mlm@bman.com'),
(18, 'company', 'contact_number', '+91 944 321 8385'),
(19, 'company', 'address', 'plot no:77, Nehru St, Sathyamoorthy Nagar, Chennai'),
(20, 'site_settings', 'landing_status', '1'),
(21, 'site_settings', 'kyc_status', '0'),
(22, 'site_settings', 'email_verify', '1'),
(23, 'site_settings', 'two_fa_status', '0'),
(24, 'site_settings', 'register_status', '1'),
(25, 'site_settings', 'allow_login', '1'),
(26, 'site_settings', 'unique_ip', '0'),
(27, 'site_settings', 'unique_mobile', '1'),
(28, 'site_settings', 'unique_email', '1'),
(29, 'site_settings', 'allow_referral_only', '0'),
(30, 'withdraw_settings', 'min_withdraw', '0.2'),
(31, 'withdraw_settings', 'max_withdraw', '1000'),
(34, 'withdraw_settings', 'withdraw_fee', '0.1'),
(35, 'withdraw_settings', 'withdraw_amount_type', '1'),
(36, 'withdraw_settings', 'withdraw_monthly_limit', '10000.00'),
(37, 'withdraw_settings', 'withdraw_status', '1'),
(38, 'withdraw_settings', 'withdraw_daily_limit', '500.00'),
(39, 'withdraw_settings', 'auto_withdraw', '1'),
(40, 'withdraw_settings', 'withdraw_notification_user', '1'),
(41, 'withdraw_settings', 'withdraw_notification_admin', '1'),
(42, 'user_settings', 'twofa_login', '0'),
(43, 'user_settings', 'twofa_editprofile', '1'),
(44, 'user_settings', 'twofa_withdraw', '1'),
(45, 'user_settings', 'min_password_length', '8'),
(46, 'user_settings', 'max_password_length', '10'),
(47, 'token_withdraw_settings', 'min_withdraw', '1'),
(48, 'token_withdraw_settings', 'max_withdraw', '20000'),
(49, 'token_withdraw_settings', 'withdraw_fee', '5'),
(50, 'token_withdraw_settings', 'withdraw_amount_type', '1'),
(51, 'token_withdraw_settings', 'withdraw_monthly_limit', '0.00'),
(52, 'token_withdraw_settings', 'withdraw_status', '1'),
(53, 'token_withdraw_settings', 'withdraw_daily_limit', '0.00'),
(54, 'token_withdraw_settings', 'auto_withdraw', '1'),
(55, 'token_withdraw_settings', 'withdraw_notification_user', '1'),
(56, 'token_withdraw_settings', 'withdraw_notification_admin', '1'),
(57, 'user_settings', 'twofa_internel_transfer', '1'),
(58, 'swap_settings', 'min_swap', '1'),
(59, 'swap_settings', 'max_swap', '10000.0000'),
(60, 'swap_settings', 'swap_fee', '5'),
(61, 'swap_settings', 'swap_amount_type', '1'),
(62, 'swap_settings', 'swap_status', '1'),
(63, 'swap_settings', 'swap_daily_limit', '0.0000'),
(64, 'swap_settings', 'swap_notification_user', '0'),
(65, 'swap_settings', 'swap_notification_admin', '0'),
(66, 'transfer_settings', 'min_transfer', '400'),
(67, 'transfer_settings', 'max_transfer', '10000.00'),
(68, 'transfer_settings', 'transfer_fee', '1'),
(69, 'transfer_settings', 'transfer_amount_type', '0'),
(70, 'transfer_settings', 'transfer_status', '1'),
(71, 'transfer_settings', 'transfer_daily_limit', '0.00'),
(72, 'transfer_settings', 'transfer_notification_user', '0'),
(73, 'transfer_settings', 'transfer_notification_admin', '0'),
(74, 'meta-settings', 'copyright', 'Copyright & design by @nexman- 2026'),
(75, 'member_theme', 'mode', 'auto'),
(76, 'member_theme', 'user_switch', '1'),
(77, 'member_theme', 'primary', '#4F46E5'),
(78, 'member_theme', 'secondary', '#7C3AED'),
(79, 'member_theme', 'accent', '#7C3AED'),
(80, 'member_theme', 'highlight_primary', '#4F46E5'),
(81, 'member_theme', 'highlight_accent', '#7C3AED'),
(82, 'member_theme', 'hover_highlight', '#4338CA'),
(83, 'member_theme', 'active_highlight', '#4F46E5'),
(84, 'member_theme', 'gradient_start', '#4F46E5'),
(85, 'member_theme', 'gradient_end', '#7C3AED'),
(86, 'member_theme', 'success', '#10B981'),
(87, 'member_theme', 'warning', '#F59E0B'),
(88, 'member_theme', 'danger', '#EF4444'),
(89, 'member_theme', 'info', '#7C3AED'),
(90, 'wallet_maturity_settings', 'maturity_enabled', '1'),
(91, 'wallet_maturity_settings', 'maturity_days_exchange', '0'),
(92, 'wallet_maturity_settings', 'maturity_days_earning', '30'),
(93, 'wallet_maturity_settings', 'maturity_days_staking', '0'),
(94, 'wallet_maturity_settings', 'maturity_days_bonus', '60'),
(95, 'user_settings', 'admin_twofa_login', '0'),
(96, 'user_settings', 'admin_email_otp_login', '0'),
(97, 'staking_lifecycle_settings', 'maturity_release_wallet', 'exchange');

-- --------------------------------------------------------

--
-- Table structure for table `sliders_img`
--

CREATE TABLE `sliders_img` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  `created_date` datetime DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `label_text` varchar(100) DEFAULT NULL,
  `heading` text DEFAULT NULL,
  `sub_heading` text DEFAULT NULL,
  `button_text` varchar(100) DEFAULT NULL,
  `button_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `sliders_img`
--

INSERT INTO `sliders_img` (`id`, `title`, `description`, `image`, `status`, `created_date`, `type`, `label_text`, `heading`, `sub_heading`, `button_text`, `button_url`) VALUES
(11, 'Fashion sale for women', NULL, 'assets/images/sliders/1752061523_banner_1.jpg', 1, '2025-07-09 17:18:10', 'image', '50% Off', 'Fashion sale<br>for women\'s', 'Elevate your every day. Style that speaks volumes.', 'Shop Now', 'category/womens-fashion'),
(12, 'Cosmetics sale Men', NULL, 'assets/images/sliders/1752061841_banner_2.jpg', 1, '2025-07-09 17:23:22', 'image', '35% Off', 'Cosmetics sale <br>for Men\'s', 'Wear the change. Fashion that feels good', 'Shop Now', ''),
(13, 'Fashion sale', NULL, 'assets/images/sliders/1752061940_banner_3.jpg', 1, '2025-07-09 17:22:20', 'image', '44% off', 'Fashion sale <br>for Children\'s', 'Wear the change. Fashion that feels good.', 'Shop Now', '');

-- --------------------------------------------------------

--
-- Table structure for table `sociallinks`
--

CREATE TABLE `sociallinks` (
  `id` int(11) NOT NULL,
  `social_name` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL,
  `dashboard_status` int(11) DEFAULT 0,
  `social_label` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `sociallinks`
--

INSERT INTO `sociallinks` (`id`, `social_name`, `link`, `dashboard_status`, `social_label`) VALUES
(8, 'Facebook', 'https://www.facebook.com/profile.php?id=61558413025006', 1, 'Facebook'),
(9, 'twitter', 'https://x.com/nexman', 1, 'Twitter (x)'),
(10, 'Instagram', 'https://www.instagram.com/nexman/', 1, 'Instagram'),
(12, 'telegram', 'http://telegram.me/+919443218385', 1, 'Telegram'),
(13, 'youtube', 'https://www.youtube.com/@Nexman', 1, 'youtube'),
(14, 'whatsapp', 'https://wa.me/919443218385', 1, 'whatsapp');

-- --------------------------------------------------------

--
-- Table structure for table `staking_bonus_settings`
--

CREATE TABLE `staking_bonus_settings` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `bonus_percent_default` decimal(6,2) NOT NULL DEFAULT 25.00,
  `reduction_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `reduction_interval_days` int(11) NOT NULL DEFAULT 60,
  `reduction_percent` decimal(6,2) NOT NULL DEFAULT 50.00,
  `reduction_dry_run` tinyint(1) NOT NULL DEFAULT 1,
  `reduction_onchain` tinyint(1) NOT NULL DEFAULT 0,
  `transfer_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `transfer_to_direct_left` tinyint(1) NOT NULL DEFAULT 1,
  `transfer_to_direct_right` tinyint(1) NOT NULL DEFAULT 1,
  `transfer_require_email_otp` tinyint(1) NOT NULL DEFAULT 1,
  `transfer_require_transfer_password` tinyint(1) NOT NULL DEFAULT 1,
  `matching_total_percent` decimal(6,2) NOT NULL DEFAULT 10.00,
  `matching_earning_percent` decimal(6,2) NOT NULL DEFAULT 8.00,
  `matching_staking_percent` decimal(6,2) NOT NULL DEFAULT 2.00,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staking_bonus_settings`
--

INSERT INTO `staking_bonus_settings` (`id`, `bonus_percent_default`, `reduction_enabled`, `reduction_interval_days`, `reduction_percent`, `reduction_dry_run`, `reduction_onchain`, `transfer_enabled`, `transfer_to_direct_left`, `transfer_to_direct_right`, `transfer_require_email_otp`, `transfer_require_transfer_password`, `matching_total_percent`, `matching_earning_percent`, `matching_staking_percent`, `updated_by`, `updated_at`) VALUES
(1, 25.00, 1, 1, 50.00, 0, 1, 1, 1, 1, 1, 1, 10.00, 8.00, 2.00, NULL, '2026-07-07 17:58:44');

-- --------------------------------------------------------

--
-- Table structure for table `staking_documents`
--

CREATE TABLE `staking_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `doc_no` varchar(40) NOT NULL,
  `doc_type` enum('receipt','agreement','roi_schedule','summary') NOT NULL,
  `invest_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `tx_hash` varchar(120) DEFAULT NULL,
  `download_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `generated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_access_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staking_document_log`
--

CREATE TABLE `staking_document_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) UNSIGNED DEFAULT NULL,
  `invest_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `doc_type` varchar(20) NOT NULL,
  `action` varchar(20) NOT NULL DEFAULT 'generated',
  `actor_type` varchar(20) NOT NULL DEFAULT 'user',
  `actor_id` int(11) DEFAULT NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staking_group_volume`
--

CREATE TABLE `staking_group_volume` (
  `user_id` int(11) NOT NULL,
  `left_volume` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `right_volume` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Cumulative binary group volume per leg (rank achievement input)';

--
-- Dumping data for table `staking_group_volume`
--

INSERT INTO `staking_group_volume` (`user_id`, `left_volume`, `right_volume`, `updated_at`) VALUES
(1, 9.0000, 0.0000, '2026-08-07 13:13:32'),
(2, 9.0000, 0.0000, '2026-08-07 13:13:32'),
(22, 0.0000, 9.0000, '2026-08-07 13:13:32'),
(999999504, 9.0000, 0.0000, '2026-08-07 13:13:32'),
(999999604, 9.0000, 0.0000, '2026-08-07 13:13:32'),
(999999605, 9.0000, 0.0000, '2026-08-07 13:13:32'),
(999999608, 7.0000, 2.0000, '2026-08-07 13:13:32');

-- --------------------------------------------------------

--
-- Table structure for table `staking_matching_payouts`
--

CREATE TABLE `staking_matching_payouts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `matched_volume` decimal(20,4) NOT NULL,
  `total_percent` decimal(6,2) NOT NULL,
  `earning_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `staking_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `left_before` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `right_before` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `run_ref` varchar(32) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Staking binary matching bonus payout audit log';

--
-- Dumping data for table `staking_matching_payouts`
--

INSERT INTO `staking_matching_payouts` (`id`, `user_id`, `matched_volume`, `total_percent`, `earning_amount`, `staking_amount`, `left_before`, `right_before`, `run_ref`, `created_at`) VALUES
(5, 999999608, 2.0000, 10.00, 0.1600, 0.0400, 7.0000, 2.0000, 'MB-20260807-094332-C8E156', '2026-08-07 13:13:32');

-- --------------------------------------------------------

--
-- Table structure for table `staking_packages`
--

CREATE TABLE `staking_packages` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL,
  `stake_amount` decimal(20,4) NOT NULL,
  `bonus_percent` decimal(6,2) NOT NULL DEFAULT 25.00,
  `group_ceiling` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_special` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staking_packages`
--

INSERT INTO `staking_packages` (`id`, `name`, `stake_amount`, `bonus_percent`, `group_ceiling`, `sort_order`, `is_active`, `is_special`, `created_at`, `updated_at`) VALUES
(1, '1 BMAN', 1.0000, 25.00, 1.0000, 0, 1, 0, '2026-07-02 10:53:06', '2026-07-30 13:53:38'),
(2, '10,000 BMAN', 10000.0000, 25.00, 10000.0000, 2, 1, 0, '2026-07-02 10:53:06', '2026-07-02 11:13:38'),
(3, '20,000 BMAN', 20000.0000, 25.00, 20000.0000, 3, 1, 0, '2026-07-02 10:53:06', '2026-07-30 15:09:02'),
(4, '25,000 BMAN', 25000.0000, 25.00, 25000.0000, 4, 1, 0, '2026-07-02 10:53:06', '2026-07-30 15:09:02'),
(5, '50,000 BMAN', 50000.0000, 25.00, 30000.0000, 5, 1, 0, '2026-07-02 10:53:06', '2026-07-30 15:09:02'),
(6, '100,000 BMAN', 100000.0000, 25.00, 30000.0000, 6, 1, 0, '2026-07-02 10:53:06', '2026-07-30 15:09:02'),
(7, '200,000 BMAN', 200000.0000, 25.00, 50000.0000, 7, 1, 0, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(8, '300,000 BMAN', 300000.0000, 25.00, 70000.0000, 8, 1, 0, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(9, '500,000 BMAN', 500000.0000, 25.00, 100000.0000, 9, 1, 0, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(40, '5,000 BMAN', 5000.0000, 25.00, 5000.0000, 1, 1, 0, '2026-07-30 12:10:55', '2026-07-30 15:09:02'),
(44, '2 BMAN', 2.0000, 20.00, 2.0000, 0, 1, 1, '2026-07-30 14:18:35', '2026-08-07 18:04:15'),
(45, '50000 BMAN', 50000.0000, 25.00, 50000.0000, 0, 1, 1, '2026-07-30 14:19:35', '2026-07-30 14:19:35'),
(46, '100000 BMAN', 100000.0000, 25.00, 100000.0000, 0, 1, 1, '2026-07-30 14:19:57', '2026-07-30 14:19:57'),
(47, '200000 BMAN', 200000.0000, 25.00, 200000.0000, 0, 1, 1, '2026-07-30 14:20:17', '2026-07-30 14:20:17');

-- --------------------------------------------------------

--
-- Table structure for table `staking_plans`
--

CREATE TABLE `staking_plans` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL,
  `code` enum('fixed','regular','combo') NOT NULL,
  `roi_credit_mode` enum('maturity','monthly','mixed') NOT NULL,
  `credit_days` varchar(40) DEFAULT NULL,
  `withdraw_after_maturity` tinyint(1) NOT NULL DEFAULT 0,
  `withdraw_frequency_days` int(11) NOT NULL DEFAULT 0,
  `min_withdraw_bman` decimal(20,4) DEFAULT NULL,
  `max_withdraw_bman` decimal(20,4) DEFAULT NULL,
  `min_withdraw_usdt` decimal(20,4) DEFAULT NULL,
  `max_withdraw_usdt` decimal(20,4) DEFAULT NULL,
  `combo_fixed_pct` decimal(6,2) DEFAULT NULL,
  `combo_regular_pct` decimal(6,2) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staking_plans`
--

INSERT INTO `staking_plans` (`id`, `name`, `code`, `roi_credit_mode`, `credit_days`, `withdraw_after_maturity`, `withdraw_frequency_days`, `min_withdraw_bman`, `max_withdraw_bman`, `min_withdraw_usdt`, `max_withdraw_usdt`, `combo_fixed_pct`, `combo_regular_pct`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Fixed Plan', 'fixed', 'maturity', NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(2, 'Regular Plan', 'regular', 'monthly', '7,8,9', 0, 30, 3000.0000, 10000.0000, 30.0000, 100.0000, NULL, NULL, 2, 1, '2026-07-02 10:53:06', '2026-08-07 18:06:56'),
(3, 'Combo Plan', 'combo', 'mixed', '7,8,9', 0, 30, 3000.0000, 10000.0000, 30.0000, 100.0000, 50.00, 50.00, 3, 1, '2026-07-02 10:53:06', '2026-08-07 16:30:15');

-- --------------------------------------------------------

--
-- Table structure for table `staking_plan_terms`
--

CREATE TABLE `staking_plan_terms` (
  `id` int(10) UNSIGNED NOT NULL,
  `plan_id` int(10) UNSIGNED NOT NULL,
  `duration_years` tinyint(4) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staking_plan_terms`
--

INSERT INTO `staking_plan_terms` (`id`, `plan_id`, `duration_years`, `is_active`) VALUES
(1, 1, 2, 1),
(2, 2, 2, 1),
(3, 3, 2, 1),
(4, 1, 3, 1),
(5, 2, 3, 1),
(6, 3, 3, 1),
(7, 1, 5, 1),
(8, 2, 5, 1),
(9, 3, 5, 1);

-- --------------------------------------------------------

--
-- Table structure for table `staking_ranks`
--

CREATE TABLE `staking_ranks` (
  `id` int(10) UNSIGNED NOT NULL,
  `tier_level` tinyint(4) NOT NULL,
  `name` varchar(40) NOT NULL,
  `group_incentive` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `benefit_badge` tinyint(1) NOT NULL DEFAULT 1,
  `benefit_certificate` tinyint(1) NOT NULL DEFAULT 1,
  `benefit_reward` tinyint(1) NOT NULL DEFAULT 1,
  `benefit_recognition` tinyint(1) NOT NULL DEFAULT 1,
  `badge_color` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `required_group_volume` decimal(30,8) NOT NULL DEFAULT 0.00000000 COMMENT 'downline BMAN volume needed to hold this rank',
  `badge_image` varchar(255) DEFAULT NULL COMMENT 'uploads/rank_badges/*.png',
  `reward_bman` decimal(30,8) NOT NULL DEFAULT 0.00000000 COMMENT 'one-time BMAN reward on achieving',
  `reward_usdt` decimal(30,8) NOT NULL DEFAULT 0.00000000 COMMENT 'one-time USDT reward on achieving',
  `reward_description` varchar(255) DEFAULT NULL COMMENT 'free-text reward note (trip, gadget, …)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staking_ranks`
--

INSERT INTO `staking_ranks` (`id`, `tier_level`, `name`, `group_incentive`, `benefit_badge`, `benefit_certificate`, `benefit_reward`, `benefit_recognition`, `badge_color`, `is_active`, `created_at`, `updated_at`, `required_group_volume`, `badge_image`, `reward_bman`, `reward_usdt`, `reward_description`) VALUES
(1, 0, 'UN RANK', 1000.0000, 1, 1, 1, 1, '#9e9e9e', 1, '2026-07-02 10:53:06', '2026-07-21 10:58:31', 1000.00000000, 'uploads/rank_badges/a23b071465e22e42ebb253e273e73ecc.png', 0.00000000, 0.00000000, NULL),
(2, 1, 'IRON', 7500.0000, 1, 1, 1, 1, '#7f8c8d', 1, '2026-07-02 10:53:06', '2026-07-21 10:58:46', 7500.00000000, 'uploads/rank_badges/0f52f569db6d8ca055d1ba40fe9ad38e.png', 0.00000000, 0.00000000, NULL),
(3, 2, 'BRONZE', 30000.0000, 1, 1, 1, 1, '#cd7f32', 1, '2026-07-02 10:53:06', '2026-07-21 10:58:56', 30000.00000000, 'uploads/rank_badges/3e6421344d93190f82e791cd5f6b5bb8.png', 0.00000000, 0.00000000, NULL),
(4, 3, 'SILVER', 150000.0000, 1, 1, 1, 1, '#c0c0c0', 1, '2026-07-02 10:53:06', '2026-07-21 10:59:07', 150000.00000000, 'uploads/rank_badges/9844a62b14d545ca4f01e2199affb2a6.png', 0.00000000, 0.00000000, NULL),
(5, 4, 'GOLD', 600000.0000, 1, 1, 1, 1, '#ffd700', 1, '2026-07-02 10:53:06', '2026-07-21 10:59:16', 600000.00000000, 'uploads/rank_badges/db626c3b73c362a9afa29a9ec26b7841.png', 0.00000000, 0.00000000, NULL),
(6, 5, 'PLATINUM', 2500000.0000, 1, 1, 1, 1, '#7ad7f0', 1, '2026-07-02 10:53:06', '2026-07-21 10:59:29', 2500000.00000000, 'uploads/rank_badges/0a835497e7e716d53795cb428510c1bd.png', 0.00000000, 0.00000000, NULL),
(7, 6, 'EMERALD', 10000000.0000, 1, 1, 1, 1, '#50c878', 1, '2026-07-02 10:53:06', '2026-07-21 10:59:42', 10000000.00000000, 'uploads/rank_badges/0629a4bbbe15b366bb5d4de4a80a6e56.png', 0.00000000, 0.00000000, NULL),
(8, 7, 'DIAMOND', 20000000.0000, 1, 1, 1, 1, '#b9f2ff', 1, '2026-07-02 10:53:06', '2026-07-21 10:59:52', 20000000.00000000, 'uploads/rank_badges/6adb28f582d3eecd90df15a435d0a9c1.png', 0.00000000, 0.00000000, NULL),
(9, 8, 'MASTER', 30000000.0000, 1, 1, 1, 1, '#9b59b6', 1, '2026-07-02 10:53:06', '2026-07-21 11:00:03', 30000000.00000000, 'uploads/rank_badges/0b462f0347bdb5a2e58dc587ff8ca4c5.png', 0.00000000, 0.00000000, NULL),
(10, 9, 'GRANDMASTER', 40000000.0000, 1, 1, 1, 1, '#e74c3c', 1, '2026-07-02 10:53:06', '2026-07-21 11:00:12', 40000000.00000000, 'uploads/rank_badges/38a0159dd8438ff29c4f41bd172f920e.png', 0.00000000, 0.00000000, NULL),
(11, 10, 'CHALLENGER', 50000000.0000, 1, 1, 1, 1, '#f1c40f', 1, '2026-07-02 10:53:06', '2026-07-21 11:00:22', 50000000.00000000, 'uploads/rank_badges/f84f315347959622b48e0c00163df33a.png', 0.00000000, 0.00000000, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `staking_rank_audit`
--

CREATE TABLE `staking_rank_audit` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `event` varchar(40) NOT NULL COMMENT 'rank_promoted | reward_paid | certificate_issued | rank_config_changed | power_calculated | cycle_opened',
  `user_id` int(11) DEFAULT NULL COMMENT 'affected member (NULL for config events)',
  `rank_id` int(10) UNSIGNED DEFAULT NULL,
  `old_value` varchar(120) DEFAULT NULL,
  `new_value` varchar(120) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL COMMENT 'admin id, NULL = cron',
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Audit trail for all rank activity';

--
-- Dumping data for table `staking_rank_audit`
--

INSERT INTO `staking_rank_audit` (`id`, `event`, `user_id`, `rank_id`, `old_value`, `new_value`, `changed_by`, `note`, `created_at`) VALUES
(1, 'rank_config_changed', NULL, 1, 'assets/rank/un_rank.jpeg', 'uploads/rank_badges/a23b071465e22e42ebb253e273e73ecc.png', 1, 'UN RANK · badge_image', '2026-07-21 10:58:31'),
(2, 'rank_config_changed', NULL, 2, 'assets/rank/iron.jpeg', 'uploads/rank_badges/0f52f569db6d8ca055d1ba40fe9ad38e.png', 1, 'IRON · badge_image', '2026-07-21 10:58:46'),
(3, 'rank_config_changed', NULL, 3, 'assets/rank/bronze.jpeg', 'uploads/rank_badges/3e6421344d93190f82e791cd5f6b5bb8.png', 1, 'BRONZE · badge_image', '2026-07-21 10:58:56'),
(4, 'rank_config_changed', NULL, 4, 'assets/rank/silver.jpeg', 'uploads/rank_badges/9844a62b14d545ca4f01e2199affb2a6.png', 1, 'SILVER · badge_image', '2026-07-21 10:59:07'),
(5, 'rank_config_changed', NULL, 5, 'assets/rank/gold.jpeg', 'uploads/rank_badges/db626c3b73c362a9afa29a9ec26b7841.png', 1, 'GOLD · badge_image', '2026-07-21 10:59:16'),
(6, 'rank_config_changed', NULL, 6, 'assets/rank/platinum.jpeg', 'uploads/rank_badges/0a835497e7e716d53795cb428510c1bd.png', 1, 'PLATINUM · badge_image', '2026-07-21 10:59:29'),
(7, 'rank_config_changed', NULL, 7, 'assets/rank/emerald.jpeg', 'uploads/rank_badges/0629a4bbbe15b366bb5d4de4a80a6e56.png', 1, 'EMERALD · badge_image', '2026-07-21 10:59:42'),
(8, 'rank_config_changed', NULL, 8, 'assets/rank/diamond.jpeg', 'uploads/rank_badges/6adb28f582d3eecd90df15a435d0a9c1.png', 1, 'DIAMOND · badge_image', '2026-07-21 10:59:53'),
(9, 'rank_config_changed', NULL, 9, 'assets/rank/master.jpeg', 'uploads/rank_badges/0b462f0347bdb5a2e58dc587ff8ca4c5.png', 1, 'MASTER · badge_image', '2026-07-21 11:00:03'),
(10, 'rank_config_changed', NULL, 10, 'assets/rank/grand_master.jpeg', 'uploads/rank_badges/38a0159dd8438ff29c4f41bd172f920e.png', 1, 'GRANDMASTER · badge_image', '2026-07-21 11:00:12'),
(11, 'rank_config_changed', NULL, 11, 'assets/rank/challenger.jpeg', 'uploads/rank_badges/f84f315347959622b48e0c00163df33a.png', 1, 'CHALLENGER · badge_image', '2026-07-21 11:00:22'),
(12, 'power_calculated', NULL, NULL, NULL, 'Cycle #1', NULL, 'Cycle #1: 4 member(s) calculated, 0 qualified for group incentive.', '2026-07-24 18:32:10'),
(15, 'certificate_issued', 999999101, 1, NULL, 'BMAN-UNRANK-2026-000001', NULL, NULL, '2026-08-05 20:11:15'),
(16, 'power_calculated', NULL, NULL, NULL, 'Cycle #1', NULL, 'Cycle #1: 17 member(s) calculated, 0 qualified for group incentive.', '2026-08-07 13:14:34'),
(17, 'power_calculated', NULL, NULL, NULL, 'Cycle #1', NULL, 'Cycle #1: 17 member(s) calculated, 0 qualified for group incentive.', '2026-08-07 13:14:55');

-- --------------------------------------------------------

--
-- Table structure for table `staking_rank_power_cycles`
--

CREATE TABLE `staking_rank_power_cycles` (
  `id` int(10) UNSIGNED NOT NULL,
  `cycle_no` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `opened_by` int(11) DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staking_rank_power_cycles`
--

INSERT INTO `staking_rank_power_cycles` (`id`, `cycle_no`, `start_date`, `end_date`, `status`, `opened_by`, `closed_at`, `created_at`) VALUES
(3, 1, '2026-07-24', '2026-09-21', 'open', NULL, NULL, '2026-07-24 18:32:10');

-- --------------------------------------------------------

--
-- Table structure for table `staking_rank_power_settings`
--

CREATE TABLE `staking_rank_power_settings` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `cycle_days` int(11) NOT NULL DEFAULT 60,
  `controls_group_incentive` tinyint(1) NOT NULL DEFAULT 1,
  `min_power_tier` tinyint(4) NOT NULL DEFAULT 0,
  `auto_open_next_cycle` tinyint(1) NOT NULL DEFAULT 1,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staking_rank_power_settings`
--

INSERT INTO `staking_rank_power_settings` (`id`, `is_enabled`, `cycle_days`, `controls_group_incentive`, `min_power_tier`, `auto_open_next_cycle`, `updated_by`, `updated_at`) VALUES
(1, 1, 60, 1, 0, 1, NULL, '2026-07-02 11:30:25');

-- --------------------------------------------------------

--
-- Table structure for table `staking_rank_requirements`
--

CREATE TABLE `staking_rank_requirements` (
  `id` int(10) UNSIGNED NOT NULL,
  `rank_id` int(10) UNSIGNED NOT NULL,
  `plan_no` tinyint(4) NOT NULL DEFAULT 1,
  `option_no` tinyint(4) NOT NULL DEFAULT 1,
  `side` enum('left','right') NOT NULL,
  `required_qty` int(11) NOT NULL,
  `required_rank_id` int(10) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staking_rank_requirements`
--

INSERT INTO `staking_rank_requirements` (`id`, `rank_id`, `plan_no`, `option_no`, `side`, `required_qty`, `required_rank_id`, `is_active`) VALUES
(1, 2, 1, 1, 'right', 2, 1, 1),
(2, 2, 1, 1, 'left', 2, 1, 1),
(3, 3, 3, 1, 'right', 2, 2, 1),
(4, 3, 3, 1, 'left', 12, 1, 1),
(5, 3, 2, 1, 'right', 12, 1, 1),
(6, 3, 2, 1, 'left', 2, 2, 1),
(7, 3, 1, 1, 'right', 2, 2, 1),
(8, 3, 1, 1, 'left', 2, 2, 1),
(9, 4, 3, 1, 'right', 2, 3, 1),
(10, 4, 3, 1, 'left', 12, 2, 1),
(11, 4, 2, 1, 'right', 12, 2, 1),
(12, 4, 2, 1, 'left', 2, 3, 1),
(13, 4, 1, 1, 'right', 2, 3, 1),
(14, 4, 1, 1, 'left', 2, 3, 1),
(15, 5, 3, 1, 'right', 2, 4, 1),
(16, 5, 3, 1, 'left', 12, 3, 1),
(17, 5, 2, 1, 'right', 12, 3, 1),
(18, 5, 2, 1, 'left', 2, 4, 1),
(19, 5, 1, 1, 'right', 2, 4, 1),
(20, 5, 1, 1, 'left', 2, 4, 1),
(21, 6, 3, 1, 'right', 2, 5, 1),
(22, 6, 3, 1, 'left', 6, 4, 1),
(23, 6, 2, 1, 'right', 6, 4, 1),
(24, 6, 2, 1, 'left', 2, 5, 1),
(29, 7, 3, 1, 'right', 1, 6, 1),
(30, 7, 3, 1, 'left', 4, 5, 1),
(31, 7, 2, 1, 'right', 4, 5, 1),
(32, 7, 2, 1, 'left', 1, 6, 1),
(33, 7, 1, 1, 'right', 1, 6, 1),
(34, 7, 1, 1, 'left', 1, 6, 1),
(35, 8, 3, 1, 'right', 1, 7, 1),
(36, 8, 3, 1, 'left', 3, 6, 1),
(37, 8, 2, 1, 'right', 3, 6, 1),
(38, 8, 2, 1, 'left', 1, 7, 1),
(39, 8, 1, 1, 'right', 1, 7, 1),
(40, 8, 1, 1, 'left', 1, 7, 1),
(41, 9, 3, 1, 'right', 1, 8, 1),
(42, 9, 3, 1, 'left', 3, 7, 1),
(43, 9, 2, 1, 'right', 3, 7, 1),
(44, 9, 2, 1, 'left', 1, 8, 1),
(45, 9, 1, 1, 'right', 1, 8, 1),
(46, 9, 1, 1, 'left', 1, 8, 1),
(47, 10, 3, 1, 'right', 1, 9, 1),
(48, 10, 3, 1, 'left', 3, 8, 1),
(49, 10, 2, 1, 'right', 3, 8, 1),
(50, 10, 2, 1, 'left', 1, 9, 1),
(51, 10, 1, 1, 'right', 1, 9, 1),
(52, 10, 1, 1, 'left', 1, 9, 1),
(53, 11, 3, 1, 'right', 1, 10, 1),
(54, 11, 3, 1, 'left', 3, 9, 1),
(55, 11, 2, 1, 'right', 3, 9, 1),
(56, 11, 2, 1, 'left', 1, 10, 1),
(57, 11, 1, 1, 'right', 1, 10, 1),
(58, 11, 1, 1, 'left', 1, 10, 1),
(64, 6, 1, 1, 'left', 2, 5, 1),
(65, 6, 1, 1, 'right', 1, 5, 1),
(66, 6, 1, 2, 'left', 1, 5, 1),
(67, 6, 1, 2, 'right', 2, 5, 1);

-- --------------------------------------------------------

--
-- Table structure for table `staking_roi_audit`
--

CREATE TABLE `staking_roi_audit` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `roi_id` int(10) UNSIGNED DEFAULT NULL,
  `package_id` int(10) UNSIGNED NOT NULL,
  `plan_code` varchar(10) NOT NULL,
  `duration_years` tinyint(4) NOT NULL,
  `old_percent` decimal(8,3) DEFAULT NULL,
  `new_percent` decimal(8,3) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staking_roi_audit`
--

INSERT INTO `staking_roi_audit` (`id`, `roi_id`, `package_id`, `plan_code`, `duration_years`, `old_percent`, `new_percent`, `changed_by`, `note`, `created_at`) VALUES
(1, 72, 40, 'fixed', 2, NULL, 160.000, 1, NULL, '2026-07-30 14:09:50'),
(2, 73, 40, 'fixed', 3, NULL, 200.000, 1, NULL, '2026-07-30 14:09:50'),
(3, 74, 40, 'fixed', 5, NULL, 400.000, 1, NULL, '2026-07-30 14:09:50'),
(4, 75, 40, 'regular', 2, NULL, 2.300, 1, NULL, '2026-07-30 14:09:50'),
(5, 76, 40, 'regular', 3, NULL, 2.500, 1, NULL, '2026-07-30 14:09:50'),
(6, 77, 40, 'regular', 5, NULL, 3.000, 1, NULL, '2026-07-30 14:09:50'),
(7, 78, 44, 'fixed', 2, NULL, 120.000, 1, NULL, '2026-07-30 14:38:34'),
(8, 79, 44, 'fixed', 3, NULL, 160.000, 1, NULL, '2026-07-30 14:38:34'),
(9, 80, 44, 'fixed', 5, NULL, 350.000, 1, NULL, '2026-07-30 14:38:34'),
(10, 81, 44, 'regular', 2, NULL, 1.500, 1, NULL, '2026-07-30 14:38:34'),
(11, 82, 44, 'regular', 3, NULL, 2.000, 1, NULL, '2026-07-30 14:38:34'),
(12, 83, 44, 'regular', 5, NULL, 2.500, 1, NULL, '2026-07-30 14:38:34'),
(13, 84, 45, 'fixed', 2, NULL, 150.000, 1, NULL, '2026-07-30 14:38:34'),
(14, 85, 45, 'fixed', 3, NULL, 200.000, 1, NULL, '2026-07-30 14:38:34'),
(15, 86, 45, 'fixed', 5, NULL, 400.000, 1, NULL, '2026-07-30 14:38:34'),
(16, 87, 45, 'regular', 2, NULL, 2.300, 1, NULL, '2026-07-30 14:38:34'),
(17, 88, 45, 'regular', 3, NULL, 2.500, 1, NULL, '2026-07-30 14:38:35'),
(18, 89, 45, 'regular', 5, NULL, 3.000, 1, NULL, '2026-07-30 14:38:35'),
(19, 90, 46, 'fixed', 2, NULL, 150.000, 1, NULL, '2026-07-30 14:38:35'),
(20, 91, 46, 'fixed', 3, NULL, 200.000, 1, NULL, '2026-07-30 14:38:35'),
(21, 92, 46, 'fixed', 5, NULL, 400.000, 1, NULL, '2026-07-30 14:38:35'),
(22, 93, 46, 'regular', 2, NULL, 2.300, 1, NULL, '2026-07-30 14:38:35'),
(23, 94, 46, 'regular', 3, NULL, 2.500, 1, NULL, '2026-07-30 14:38:35'),
(24, 95, 46, 'regular', 5, NULL, 3.000, 1, NULL, '2026-07-30 14:38:35'),
(25, 96, 47, 'fixed', 2, NULL, 150.000, 1, NULL, '2026-07-30 14:39:50'),
(26, 97, 47, 'fixed', 3, NULL, 200.000, 1, NULL, '2026-07-30 14:39:50'),
(27, 98, 47, 'fixed', 5, NULL, 400.000, 1, NULL, '2026-07-30 14:39:50'),
(28, 99, 47, 'regular', 2, NULL, 2.300, 1, NULL, '2026-07-30 14:39:50'),
(29, 100, 47, 'regular', 3, NULL, 2.500, 1, NULL, '2026-07-30 14:39:50'),
(30, 101, 47, 'regular', 5, NULL, 3.000, 1, NULL, '2026-07-30 14:39:50');

-- --------------------------------------------------------

--
-- Table structure for table `staking_roi_ledger`
--

CREATE TABLE `staking_roi_ledger` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `staking_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `roi_amount` decimal(30,8) NOT NULL,
  `roi_type` varchar(24) NOT NULL DEFAULT 'hourly',
  `wallet_column` varchar(24) NOT NULL DEFAULT 'earning' COMMENT 'exchange, staking, earning, bonus',
  `processed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ROI processing ledger with wallet distribution tracking';

-- --------------------------------------------------------

--
-- Table structure for table `staking_roi_payouts`
--

CREATE TABLE `staking_roi_payouts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `stake_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(20,4) NOT NULL,
  `credit_date` date NOT NULL,
  `wallet` enum('earning','staking') NOT NULL DEFAULT 'earning',
  `status` enum('pending','paid','failed') NOT NULL DEFAULT 'paid',
  `tx_hash` varchar(255) DEFAULT NULL COMMENT 'Blockchain tx hash',
  `transfer_status` enum('pending_broadcast','pending_confirmation','confirmed','failed','reverted') DEFAULT 'pending_broadcast' COMMENT 'On-chain transfer status',
  `transferred_at` datetime DEFAULT NULL COMMENT 'When we broadcast the transfer',
  `confirmed_at` datetime DEFAULT NULL COMMENT 'When confirmed on-chain',
  `block_number` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Block where tx confirmed',
  `confirmation_count` int(10) UNSIGNED DEFAULT 0 COMMENT 'Number of block confirmations',
  `network` varchar(20) DEFAULT 'bsc' COMMENT 'Chain network (bsc, eth, etc)',
  `error_message` text DEFAULT NULL COMMENT 'Last error if failed',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staking_roi_schedule_versions`
--

CREATE TABLE `staking_roi_schedule_versions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `package_id` int(10) UNSIGNED NOT NULL,
  `plan_code` enum('fixed','regular','combo') NOT NULL,
  `duration_years` tinyint(4) NOT NULL,
  `version_no` int(10) UNSIGNED NOT NULL,
  `status` enum('draft','active','expired','archived') NOT NULL DEFAULT 'draft',
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `change_reason` varchar(255) DEFAULT NULL,
  `active_key` varchar(140) GENERATED ALWAYS AS (case when `status` = 'active' then concat(`package_id`,'-',`plan_code`,'-',`duration_years`) end) STORED,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staking_roi_schedule_version_audit`
--

CREATE TABLE `staking_roi_schedule_version_audit` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `version_id` bigint(20) UNSIGNED NOT NULL,
  `package_id` int(10) UNSIGNED NOT NULL,
  `plan_code` varchar(10) NOT NULL,
  `duration_years` tinyint(4) NOT NULL,
  `version_no` int(10) UNSIGNED NOT NULL,
  `action` enum('draft_saved','activated','expired','archived') NOT NULL,
  `status_after` varchar(10) NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `years_json` text DEFAULT NULL,
  `changed_by` int(11) NOT NULL,
  `change_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staking_roi_schedule_version_years`
--

CREATE TABLE `staking_roi_schedule_version_years` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `version_id` bigint(20) UNSIGNED NOT NULL,
  `year_number` tinyint(4) NOT NULL,
  `roi_percent` decimal(8,3) NOT NULL,
  `roi_basis` enum('total','monthly') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staking_roi_structure`
--

CREATE TABLE `staking_roi_structure` (
  `id` int(10) UNSIGNED NOT NULL,
  `package_id` int(10) UNSIGNED NOT NULL,
  `plan_code` enum('fixed','regular') NOT NULL,
  `duration_years` tinyint(4) NOT NULL,
  `roi_percent` decimal(8,3) NOT NULL,
  `roi_basis` enum('total','monthly') NOT NULL,
  `effective_from` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staking_roi_structure`
--

INSERT INTO `staking_roi_structure` (`id`, `package_id`, `plan_code`, `duration_years`, `roi_percent`, `roi_basis`, `effective_from`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'regular', 5, 3.000, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(2, 1, 'regular', 3, 2.500, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(3, 1, 'regular', 2, 2.300, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(4, 1, 'fixed', 5, 400.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(5, 1, 'fixed', 3, 200.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(6, 1, 'fixed', 2, 150.000, 'total', '2026-07-02', 0, NULL, '2026-07-02 10:53:06', '2026-07-02 11:03:20'),
(7, 2, 'regular', 5, 3.000, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(8, 2, 'regular', 3, 2.500, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(9, 2, 'regular', 2, 2.300, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(10, 2, 'fixed', 5, 400.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(11, 2, 'fixed', 3, 200.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(12, 2, 'fixed', 2, 150.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(13, 3, 'regular', 5, 3.000, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(14, 3, 'regular', 3, 2.500, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(15, 3, 'regular', 2, 2.300, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(16, 3, 'fixed', 5, 400.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(17, 3, 'fixed', 3, 200.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(18, 3, 'fixed', 2, 150.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(19, 4, 'regular', 5, 3.000, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(20, 4, 'regular', 3, 2.500, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(21, 4, 'regular', 2, 2.300, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(22, 4, 'fixed', 5, 400.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(23, 4, 'fixed', 3, 200.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(24, 4, 'fixed', 2, 150.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(25, 5, 'regular', 5, 3.000, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(26, 5, 'regular', 3, 2.500, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(27, 5, 'regular', 2, 2.300, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(28, 5, 'fixed', 5, 400.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(29, 5, 'fixed', 3, 200.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(30, 5, 'fixed', 2, 150.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(31, 6, 'regular', 5, 3.000, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(32, 6, 'regular', 3, 2.500, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(33, 6, 'regular', 2, 2.300, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(34, 6, 'fixed', 5, 400.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(35, 6, 'fixed', 3, 200.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(36, 6, 'fixed', 2, 150.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(37, 7, 'regular', 5, 3.200, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(38, 7, 'regular', 3, 3.200, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(39, 7, 'regular', 2, 2.500, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(40, 7, 'fixed', 5, 410.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(41, 7, 'fixed', 3, 210.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(42, 7, 'fixed', 2, 160.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(43, 8, 'regular', 5, 3.300, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(44, 8, 'regular', 3, 3.300, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(45, 8, 'regular', 2, 2.800, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(46, 8, 'fixed', 5, 430.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(47, 8, 'fixed', 3, 230.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(48, 8, 'fixed', 2, 180.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(49, 9, 'regular', 5, 3.500, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(50, 9, 'regular', 3, 3.500, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(51, 9, 'regular', 2, 3.000, 'monthly', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(52, 9, 'fixed', 5, 450.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(53, 9, 'fixed', 3, 250.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(54, 9, 'fixed', 2, 200.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 10:53:06', '2026-07-02 10:53:06'),
(65, 1, 'fixed', 2, 150.000, 'total', '2026-07-02', 1, NULL, '2026-07-02 11:03:54', '2026-07-02 11:04:13'),
(72, 40, 'fixed', 2, 160.000, 'total', '2026-07-30', 1, 1, '2026-07-30 14:09:50', '2026-07-30 14:09:50'),
(73, 40, 'fixed', 3, 200.000, 'total', '2026-07-30', 1, 1, '2026-07-30 14:09:50', '2026-07-30 14:09:50'),
(74, 40, 'fixed', 5, 400.000, 'total', '2026-07-30', 1, 1, '2026-07-30 14:09:50', '2026-07-30 14:09:50'),
(75, 40, 'regular', 2, 2.300, 'monthly', '2026-07-30', 1, 1, '2026-07-30 14:09:50', '2026-07-30 14:09:50'),
(76, 40, 'regular', 3, 2.500, 'monthly', '2026-07-30', 1, 1, '2026-07-30 14:09:50', '2026-07-30 14:09:50'),
(77, 40, 'regular', 5, 3.000, 'monthly', '2026-07-30', 1, 1, '2026-07-30 14:09:50', '2026-07-30 14:09:50'),
(78, 44, 'fixed', 2, 120.000, 'total', '2026-07-30', 1, 1, '2026-07-30 14:38:34', '2026-07-30 14:38:34'),
(79, 44, 'fixed', 3, 160.000, 'total', '2026-07-30', 1, 1, '2026-07-30 14:38:34', '2026-07-30 14:38:34'),
(80, 44, 'fixed', 5, 350.000, 'total', '2026-07-30', 1, 1, '2026-07-30 14:38:34', '2026-07-30 14:38:34'),
(81, 44, 'regular', 2, 1.500, 'monthly', '2026-07-30', 1, 1, '2026-07-30 14:38:34', '2026-07-30 14:38:34'),
(82, 44, 'regular', 3, 2.000, 'monthly', '2026-07-30', 1, 1, '2026-07-30 14:38:34', '2026-07-30 14:38:34'),
(83, 44, 'regular', 5, 2.500, 'monthly', '2026-07-30', 1, 1, '2026-07-30 14:38:34', '2026-07-30 14:38:34'),
(84, 45, 'fixed', 2, 150.000, 'total', '2026-07-30', 1, 1, '2026-07-30 14:38:34', '2026-07-30 14:38:34'),
(85, 45, 'fixed', 3, 200.000, 'total', '2026-07-30', 1, 1, '2026-07-30 14:38:34', '2026-07-30 14:38:34'),
(86, 45, 'fixed', 5, 400.000, 'total', '2026-07-30', 1, 1, '2026-07-30 14:38:34', '2026-07-30 14:38:34'),
(87, 45, 'regular', 2, 2.300, 'monthly', '2026-07-30', 1, 1, '2026-07-30 14:38:34', '2026-07-30 14:38:34'),
(88, 45, 'regular', 3, 2.500, 'monthly', '2026-07-30', 1, 1, '2026-07-30 14:38:35', '2026-07-30 14:38:35'),
(89, 45, 'regular', 5, 3.000, 'monthly', '2026-07-30', 1, 1, '2026-07-30 14:38:35', '2026-07-30 14:38:35'),
(90, 46, 'fixed', 2, 150.000, 'total', '2026-07-30', 1, 1, '2026-07-30 14:38:35', '2026-07-30 14:38:35'),
(91, 46, 'fixed', 3, 200.000, 'total', '2026-07-30', 1, 1, '2026-07-30 14:38:35', '2026-07-30 14:38:35'),
(92, 46, 'fixed', 5, 400.000, 'total', '2026-07-30', 1, 1, '2026-07-30 14:38:35', '2026-07-30 14:38:35'),
(93, 46, 'regular', 2, 2.300, 'monthly', '2026-07-30', 1, 1, '2026-07-30 14:38:35', '2026-07-30 14:38:35'),
(94, 46, 'regular', 3, 2.500, 'monthly', '2026-07-30', 1, 1, '2026-07-30 14:38:35', '2026-07-30 14:38:35'),
(95, 46, 'regular', 5, 3.000, 'monthly', '2026-07-30', 1, 1, '2026-07-30 14:38:35', '2026-07-30 14:38:35'),
(96, 47, 'fixed', 2, 150.000, 'total', '2026-07-30', 1, 1, '2026-07-30 14:39:50', '2026-07-30 14:39:50'),
(97, 47, 'fixed', 3, 200.000, 'total', '2026-07-30', 1, 1, '2026-07-30 14:39:50', '2026-07-30 14:39:50'),
(98, 47, 'fixed', 5, 400.000, 'total', '2026-07-30', 1, 1, '2026-07-30 14:39:50', '2026-07-30 14:39:50'),
(99, 47, 'regular', 2, 2.300, 'monthly', '2026-07-30', 1, 1, '2026-07-30 14:39:50', '2026-07-30 14:39:50'),
(100, 47, 'regular', 3, 2.500, 'monthly', '2026-07-30', 1, 1, '2026-07-30 14:39:50', '2026-07-30 14:39:50'),
(101, 47, 'regular', 5, 3.000, 'monthly', '2026-07-30', 1, 1, '2026-07-30 14:39:50', '2026-07-30 14:39:50');

-- --------------------------------------------------------

--
-- Table structure for table `staking_roi_transfer_log`
--

CREATE TABLE `staking_roi_transfer_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `roi_payout_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(20,4) NOT NULL,
  `from_address` varchar(255) NOT NULL,
  `to_address` varchar(255) NOT NULL,
  `tx_hash` varchar(255) DEFAULT NULL,
  `transfer_status` enum('pending_broadcast','pending_confirmation','confirmed','failed','reverted') DEFAULT 'pending_broadcast',
  `attempt_no` tinyint(3) UNSIGNED DEFAULT 1,
  `error_message` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staking_special_roi`
--

CREATE TABLE `staking_special_roi` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `package_id` int(10) UNSIGNED NOT NULL,
  `year_number` tinyint(3) UNSIGNED NOT NULL,
  `monthly_roi_percent` decimal(8,3) NOT NULL DEFAULT 0.000,
  `maturity_percent` decimal(8,3) NOT NULL DEFAULT 0.000,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staking_special_roi`
--

INSERT INTO `staking_special_roi` (`id`, `package_id`, `year_number`, `monthly_roi_percent`, `maturity_percent`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 5, 1, 2.500, 130.000, 1, '2026-07-24 09:21:56', '2026-07-24 09:21:56'),
(2, 5, 2, 2.700, 180.000, 1, '2026-07-24 09:21:56', '2026-07-24 09:21:56'),
(3, 5, 3, 3.000, 260.000, 1, '2026-07-24 09:21:57', '2026-07-24 09:21:57'),
(4, 5, 5, 3.500, 440.000, 1, '2026-07-24 09:21:57', '2026-07-24 09:21:57'),
(5, 6, 1, 2.800, 150.000, 1, '2026-07-24 09:21:58', '2026-07-24 09:21:58'),
(6, 6, 3, 3.300, 300.000, 1, '2026-07-24 09:21:58', '2026-07-24 09:21:58'),
(7, 6, 5, 3.800, 500.000, 1, '2026-07-24 09:21:58', '2026-07-24 09:21:58'),
(8, 6, 7, 4.500, 800.000, 1, '2026-07-24 09:21:59', '2026-07-24 09:21:59'),
(9, 1, 1, 2.000, 3.000, 1, '2026-07-29 15:31:10', '2026-07-29 15:42:02'),
(10, 1, 2, 0.000, 0.000, 1, '2026-07-29 15:31:12', '2026-07-29 15:31:12'),
(11, 1, 3, 0.000, 0.000, 1, '2026-07-29 15:31:15', '2026-07-29 15:31:15'),
(12, 1, 4, 0.000, 0.000, 1, '2026-07-29 15:31:17', '2026-07-29 15:31:17'),
(13, 1, 5, 0.000, 0.000, 1, '2026-07-29 15:31:19', '2026-07-29 15:31:19'),
(14, 1, 6, 0.000, 0.000, 1, '2026-07-29 15:31:23', '2026-07-29 15:31:23'),
(15, 1, 7, 0.000, 0.000, 1, '2026-07-29 15:31:26', '2026-07-29 15:31:26');

-- --------------------------------------------------------

--
-- Table structure for table `staking_special_roi_audit`
--

CREATE TABLE `staking_special_roi_audit` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `package_id` int(10) UNSIGNED NOT NULL,
  `year_number` tinyint(3) UNSIGNED NOT NULL,
  `field` varchar(40) NOT NULL,
  `old_value` varchar(64) DEFAULT NULL,
  `new_value` varchar(64) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staking_special_roi_audit`
--

INSERT INTO `staking_special_roi_audit` (`id`, `package_id`, `year_number`, `field`, `old_value`, `new_value`, `changed_by`, `created_at`) VALUES
(1, 5, 1, 'monthly_roi_percent', NULL, '2.5', 1, '2026-07-24 09:21:56'),
(2, 5, 1, 'maturity_percent', NULL, '130', 1, '2026-07-24 09:21:56'),
(3, 5, 1, 'is_active', NULL, '1', 1, '2026-07-24 09:21:56'),
(4, 5, 2, 'monthly_roi_percent', NULL, '2.7', 1, '2026-07-24 09:21:56'),
(5, 5, 2, 'maturity_percent', NULL, '180', 1, '2026-07-24 09:21:56'),
(6, 5, 2, 'is_active', NULL, '1', 1, '2026-07-24 09:21:56'),
(7, 5, 3, 'monthly_roi_percent', NULL, '3', 1, '2026-07-24 09:21:57'),
(8, 5, 3, 'maturity_percent', NULL, '260', 1, '2026-07-24 09:21:57'),
(9, 5, 3, 'is_active', NULL, '1', 1, '2026-07-24 09:21:57'),
(10, 5, 5, 'monthly_roi_percent', NULL, '3.5', 1, '2026-07-24 09:21:57'),
(11, 5, 5, 'maturity_percent', NULL, '440', 1, '2026-07-24 09:21:57'),
(12, 5, 5, 'is_active', NULL, '1', 1, '2026-07-24 09:21:57'),
(13, 6, 1, 'monthly_roi_percent', NULL, '2.8', 1, '2026-07-24 09:21:58'),
(14, 6, 1, 'maturity_percent', NULL, '150', 1, '2026-07-24 09:21:58'),
(15, 6, 1, 'is_active', NULL, '1', 1, '2026-07-24 09:21:58'),
(16, 6, 3, 'monthly_roi_percent', NULL, '3.3', 1, '2026-07-24 09:21:58'),
(17, 6, 3, 'maturity_percent', NULL, '300', 1, '2026-07-24 09:21:58'),
(18, 6, 3, 'is_active', NULL, '1', 1, '2026-07-24 09:21:58'),
(19, 6, 5, 'monthly_roi_percent', NULL, '3.8', 1, '2026-07-24 09:21:58'),
(20, 6, 5, 'maturity_percent', NULL, '500', 1, '2026-07-24 09:21:58'),
(21, 6, 5, 'is_active', NULL, '1', 1, '2026-07-24 09:21:58'),
(22, 6, 7, 'monthly_roi_percent', NULL, '4.5', 1, '2026-07-24 09:21:59'),
(23, 6, 7, 'maturity_percent', NULL, '800', 1, '2026-07-24 09:21:59'),
(24, 6, 7, 'is_active', NULL, '1', 1, '2026-07-24 09:21:59'),
(25, 1, 1, 'monthly_roi_percent', NULL, '0', 1, '2026-07-29 15:31:10'),
(26, 1, 1, 'maturity_percent', NULL, '1', 1, '2026-07-29 15:31:10'),
(27, 1, 1, 'is_active', NULL, '1', 1, '2026-07-29 15:31:10'),
(28, 1, 2, 'monthly_roi_percent', NULL, '0', 1, '2026-07-29 15:31:12'),
(29, 1, 2, 'maturity_percent', NULL, '0', 1, '2026-07-29 15:31:12'),
(30, 1, 2, 'is_active', NULL, '1', 1, '2026-07-29 15:31:12'),
(31, 1, 3, 'monthly_roi_percent', NULL, '0', 1, '2026-07-29 15:31:15'),
(32, 1, 3, 'maturity_percent', NULL, '0', 1, '2026-07-29 15:31:15'),
(33, 1, 3, 'is_active', NULL, '1', 1, '2026-07-29 15:31:15'),
(34, 1, 4, 'monthly_roi_percent', NULL, '0', 1, '2026-07-29 15:31:17'),
(35, 1, 4, 'maturity_percent', NULL, '0', 1, '2026-07-29 15:31:17'),
(36, 1, 4, 'is_active', NULL, '1', 1, '2026-07-29 15:31:17'),
(37, 1, 5, 'monthly_roi_percent', NULL, '0', 1, '2026-07-29 15:31:19'),
(38, 1, 5, 'maturity_percent', NULL, '0', 1, '2026-07-29 15:31:19'),
(39, 1, 5, 'is_active', NULL, '1', 1, '2026-07-29 15:31:19'),
(40, 1, 6, 'monthly_roi_percent', NULL, '0', 1, '2026-07-29 15:31:23'),
(41, 1, 6, 'maturity_percent', NULL, '0', 1, '2026-07-29 15:31:23'),
(42, 1, 6, 'is_active', NULL, '1', 1, '2026-07-29 15:31:23'),
(43, 1, 7, 'monthly_roi_percent', NULL, '0', 1, '2026-07-29 15:31:26'),
(44, 1, 7, 'maturity_percent', NULL, '0', 1, '2026-07-29 15:31:26'),
(45, 1, 7, 'is_active', NULL, '1', 1, '2026-07-29 15:31:26'),
(46, 1, 1, 'monthly_roi_percent', '0.000', '2', 1, '2026-07-29 15:42:02'),
(47, 1, 1, 'maturity_percent', '1.000', '3', 1, '2026-07-29 15:42:02');

-- --------------------------------------------------------

--
-- Table structure for table `staking_swap_orders`
--

CREATE TABLE `staking_swap_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ref` varchar(32) NOT NULL,
  `user_id` int(11) NOT NULL,
  `package_id` int(10) UNSIGNED DEFAULT NULL,
  `user_address` varchar(120) NOT NULL COMMENT 'user deposit address',
  `admin_address` varchar(120) NOT NULL COMMENT 'treasury / admin receive address',
  `gas_tx_hash` varchar(120) DEFAULT NULL,
  `usdt_amount` decimal(30,8) NOT NULL,
  `bman_amount` decimal(30,8) NOT NULL,
  `bonus_bman` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `exchange_rate` decimal(24,8) NOT NULL,
  `plan_code` varchar(50) DEFAULT NULL COMMENT 'fixed, variable, etc',
  `plan_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'staking plan reference',
  `duration_years` int(11) DEFAULT NULL COMMENT 'staking duration in years',
  `roi_return_status` varchar(50) DEFAULT 'pending',
  `maturity_roi_amount` decimal(20,8) DEFAULT 0.00000000,
  `roi_staking_management_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cron_status` varchar(50) DEFAULT 'pending' COMMENT 'pending, processing, completed, skipped',
  `usdt_tx_hash` varchar(120) DEFAULT NULL,
  `bman_tx_hash` varchar(120) DEFAULT NULL,
  `bonus_tx_hash` varchar(120) DEFAULT NULL,
  `status` varchar(24) NOT NULL DEFAULT 'created',
  `error` varchar(255) DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `gas_cron_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Gas fee cron: 0=pending, 1=completed',
  `usdt_cron_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'USDT payment cron: 0=pending, 1=completed',
  `bonus_cron_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Bonus BMAN cron: 0=pending, 1=completed',
  `bman_cron_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'BMAN distribution cron: 0=pending,1=completed',
  `gas_cron_status_message` text DEFAULT NULL COMMENT 'Gas fee cron failure message/error',
  `usdt_cron_status_message` text DEFAULT NULL COMMENT 'USDT payment cron failure message/error',
  `bonus_cron_status_message` text DEFAULT NULL COMMENT 'Bonus BMAN cron failure message/error',
  `bman_cron_status_message` text DEFAULT NULL COMMENT 'BMAN distribution cron message',
  `coin_distribution_option` int(11) NOT NULL DEFAULT 1 COMMENT 'Coin distribution option (1-7) for wallet splits'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='On-chain USDT<->BMAN swap orders (two-leg state machine + tx hashes)';

--
-- Dumping data for table `staking_swap_orders`
--

INSERT INTO `staking_swap_orders` (`id`, `ref`, `user_id`, `package_id`, `user_address`, `admin_address`, `gas_tx_hash`, `usdt_amount`, `bman_amount`, `bonus_bman`, `exchange_rate`, `plan_code`, `plan_id`, `duration_years`, `roi_return_status`, `maturity_roi_amount`, `roi_staking_management_id`, `cron_status`, `usdt_tx_hash`, `bman_tx_hash`, `bonus_tx_hash`, `status`, `error`, `attempts`, `created_at`, `updated_at`, `gas_cron_status`, `usdt_cron_status`, `bonus_cron_status`, `bman_cron_status`, `gas_cron_status_message`, `usdt_cron_status_message`, `bonus_cron_status_message`, `bman_cron_status_message`, `coin_distribution_option`) VALUES
(1, 'SWP-20260720-82A2766D', 3, 1, '0x18d0B930970f05ABFC5CC08cad9346aF58D3dd24', '0x3088B858dc4cD85A001337f8E15a40b24666d321', '0x1707ee37cc6046dc4cc6e99d5e9417295bb61c58ba2fbde9e735731d2321b957', 0.10000000, 1.00000000, 0.25000000, 10.00000000, 'fixed', 0, 2, 'pending', 1.50000000, 1, 'completed', '0xf262579c4b551aa220c6c405e3340a8b2928556ca69a2c04e3d7f138d052c2a0', '0x3d335923489fb747bb12aead4e973c7fc03bf03b212fbdee9e8fee5a49552bdc', '0xb3e94ea802696e384d8450fa8de9c5cb680fece5e0d8fbe72d5009a648707bff', 'swap_completed', NULL, 1, '2026-07-20 22:16:29', '2026-07-20 18:48:14', 1, 1, 1, 1, NULL, NULL, NULL, NULL, 1),
(2, 'SWP-20260723-87AA1F16', 3, 1, '0x18d0B930970f05ABFC5CC08cad9346aF58D3dd24', '0x3088B858dc4cD85A001337f8E15a40b24666d321', NULL, 0.10000000, 1.00000000, 0.25000000, 10.00000000, 'regular', 0, 5, 'pending', 1.80000000, 2, 'completed', '0xe4d367e51986bb953679b97502bcb774ca1af0650089f62fc4978995a2e9cb5f', '0x16288134bf91c80eb4268982f128a9a2b89a3580865dce273565caf28b03baa5', '0xa288fb3468bacb651689e208b1f81f2454ffb1aa5868b72955ca24726a5cc819', 'swap_completed', NULL, 1, '2026-07-23 18:33:11', '2026-07-23 15:15:04', 1, 1, 1, 1, NULL, NULL, NULL, NULL, 7),
(5, 'SWP-20260729-77163357', 3, 1, '0x18d0B930970f05ABFC5CC08cad9346aF58D3dd24', '0x3088B858dc4cD85A001337f8E15a40b24666d321', NULL, 0.10000000, 1.00000000, 0.25000000, 10.00000000, 'regular', 0, 1, 'pending', 0.27000000, 5, 'completed', '0x8ad440d70c911ed3a199257a088b4ecefc1207a2a3183eb86bb7cf1022aa3e19', '0x87bf9ecc9161cd6a366f38d5c22a20693ff05c59676135bc02d1beb6fb7539f5', '0x6c921516d4892b72ba9ac163544880a26e1d555d26b007b8bd7f03e59bcdccf3', 'swap_completed', NULL, 1, '2026-07-29 20:49:03', '2026-07-30 14:36:15', 1, 1, 1, 1, NULL, NULL, NULL, NULL, 1),
(6, 'SWP-20260730-FC30455C', 2, 44, '0x365E2F051Cd601F8828cf33c3D1B7C87a0141c1b', '0x3088B858dc4cD85A001337f8E15a40b24666d321', NULL, 0.20000000, 2.00000000, 0.50000000, 10.00000000, 'regular', 0, 3, 'pending', 1.44000000, 6, 'completed', '0x6a29a7b742f9e76eba33833cd199c47abce9ce0148b8948b21d519b6226fbf55', '0x84f0715ea64a2c26e34171a76e554eeacbea7d48f4c3a1271cbd3d5f3fc34614', '0xf546aaab4e2b6cd7db39822e04569a084a87cd6707fa665d01a8e9b0773d4cbe', 'swap_completed', NULL, 1, '2026-07-30 18:05:10', '2026-08-05 12:49:21', 1, 1, 1, 1, NULL, NULL, NULL, NULL, 5),
(10, 'SWP-20260805-48D6BAB1', 23, 44, '0xb3A4C6e46049bE49CdB9734DbfB2897ade83Fe83', '0x3088B858dc4cD85A001337f8E15a40b24666d321', '0xb126bb3fd3bb1a1ed398a94a0270e8a4823a125a5889bd4c8b98010908ba01aa', 0.20000000, 2.00000000, 0.50000000, 10.00000000, 'fixed', 0, 3, 'pending', 3.20000000, 7, 'completed', '0xcea86e9eb3646fb4bb4ccf659d4539504a7376b7a8204161b434b3f40e289118', '0x460fa827d11c69b34ab2f2ef10544e5b2ca101f5da28180d806c260c67199361', '0x47d6224d250d8912e7cbf270476a2fb726944bcc9680225c1250c67f3dacf727', 'swap_completed', NULL, 2, '2026-08-05 16:18:38', '2026-08-05 13:24:07', 1, 1, 1, 1, NULL, NULL, NULL, NULL, 1),
(27, 'SWP-20260806-0EBEEDFB', 999999604, 1, '0xFD96d05e54F137c196Aaf81cCe565D3061ECAA37', '0x3088B858dc4cD85A001337f8E15a40b24666d321', '0xcfca26db11a45936b6ba44eed46711ade4669a3b0ffdaccb844d05ebad873976', 0.10000000, 1.00000000, 0.25000000, 10.00000000, 'combo', 0, 5, 'pending', 2.90000000, 25, 'completed', '0xf0fdd4277df4954b4f85e49950399e4b228f0d7017e53806b130bb4aa0ceab06', '0x5ba7c051a2b1a8af3331a5f00b4dee8796695ece5d8b3be4e4eccb125ee1c56d', '0x5ba7c051a2b1a8af3331a5f00b4dee8796695ece5d8b3be4e4eccb125ee1c56d', 'swap_completed', NULL, 1, '2026-08-06 17:07:54', '2026-08-06 13:41:57', 1, 1, 1, 1, NULL, NULL, NULL, NULL, 1),
(28, 'SWP-20260806-649E1E10', 999999607, 44, '0xf18948D95e2B8DEe52a5816c48B02Eb245c4Fa1B', '0x3088B858dc4cD85A001337f8E15a40b24666d321', '0xd54d821263e8f4fdc17ba3f3e0e167d0108aecd530458f20e10fc38c4e28211b', 0.20000000, 2.00000000, 0.50000000, 10.00000000, 'combo', 0, 5, 'pending', 5.00000000, 26, 'completed', '0xceb4979692cc1379b4126dbeab82bdea51072afcfef33983571f0ed9f93b90f9', '0x88e71c7b8db3e36b861f08f6c68f6ebb2c8db2afa744486390bd06394829398e', '0x88e71c7b8db3e36b861f08f6c68f6ebb2c8db2afa744486390bd06394829398e', 'swap_completed', NULL, 1, '2026-08-06 17:10:30', '2026-08-06 13:42:53', 1, 1, 1, 1, NULL, NULL, NULL, NULL, 1),
(29, 'SWP-20260806-7C3C3DDC', 999999608, 1, '0x77779986DF95EBEaE48F4c6a94Be2886eA7a943C', '0x3088B858dc4cD85A001337f8E15a40b24666d321', '0x33f710208fa66c3134e98237fc5018eafd74462803b47033931c3010de5f3592', 0.10000000, 1.00000000, 0.25000000, 10.00000000, 'regular', 0, 3, 'pending', 0.90000000, 27, 'completed', '0xa1c529579f2f13639b085c418a2fcebc91d32f19a6e648fa28a4b201b4492016', '0xa0c873980ba96f706e3e5b4e652b533af7b48345adcc479969afed4f5fe66936', '0xa0c873980ba96f706e3e5b4e652b533af7b48345adcc479969afed4f5fe66936', 'swap_completed', NULL, 1, '2026-08-06 16:38:14', '2026-08-06 16:53:04', 1, 1, 1, 1, NULL, NULL, NULL, NULL, 1),
(30, 'SWP-20260806-8AFA5518', 999999612, 44, '0x084B8f91a35c79c49157b095d61F4Ab42910A093', '0x3088B858dc4cD85A001337f8E15a40b24666d321', '0x83c715b63a4b965ade82f6ab20352c6646644f86109b73c07f86c27617f0daf5', 0.20000000, 2.00000000, 0.50000000, 10.00000000, 'combo', 0, 5, 'pending', 5.00000000, 28, 'completed', '0xd137832fc92051a12f5ff1672b334851c027476930745da25701f3a30b04d591', '0x55e0ad3b428365b6804d284df9fac96978264d52a65a69bc91e913ffefbbf90e', '0x55e0ad3b428365b6804d284df9fac96978264d52a65a69bc91e913ffefbbf90e', 'swap_completed', NULL, 1, '2026-08-06 17:23:37', '2026-08-06 17:37:04', 1, 1, 1, 1, NULL, NULL, NULL, NULL, 1),
(31, 'SWP-20260806-9BCD385A', 999999612, 1, '0x084B8f91a35c79c49157b095d61F4Ab42910A093', '0x3088B858dc4cD85A001337f8E15a40b24666d321', '0x0ea2268901f0490ca795ecf9e83aff10fd1b61893929b206160b1d6cbfcd9d65', 0.10000000, 1.00000000, 0.25000000, 10.00000000, 'fixed', 0, 5, 'pending', 4.00000000, 29, 'completed', '0x86c0be581ec585352170a16bc68d733f3b8353aab312f203d0706fa9247ed0cd', '0x31b0d017ca0d861874e72322f687b55cd9d8f163633b2b154e6c057acd9bd052', '0x31b0d017ca0d861874e72322f687b55cd9d8f163633b2b154e6c057acd9bd052', 'swap_completed', NULL, 1, '2026-08-06 17:24:48', '2026-08-06 17:37:06', 1, 1, 1, 1, NULL, NULL, NULL, NULL, 1),
(32, 'SWP-20260807-E710F40C', 999999606, 1, '0x2E228070726Ec09A6e0a9d89287F900B5DD2D3Db', '0x3088B858dc4cD85A001337f8E15a40b24666d321', '0x9b5e624dde2b7b20e8a562ca4f36fc5beb4db2670638c34d17f55be57c609995', 0.10000000, 1.00000000, 0.25000000, 10.00000000, 'regular', 0, 3, 'pending', 0.90000000, 40, 'completed', '0xd895ff06b6162b4957421fb7cfc0b88bb9e574c680fcacd51e91f1a553aa8b9d', '0x4f46ebd6d76121a1ac3ee2e7a2e4643d2c0b0a92d9a3b4b722ad51decbe72eaf', '0x4f46ebd6d76121a1ac3ee2e7a2e4643d2c0b0a92d9a3b4b722ad51decbe72eaf', 'swap_completed', NULL, 1, '2026-08-07 13:38:02', '2026-08-07 10:12:00', 1, 1, 1, 1, NULL, NULL, NULL, NULL, 1),
(33, 'SWP-20260807-EF6ABE55', 999999613, 44, '0x85519d7A4E94a070eceeEe5e1763206C4D6665Ff', '0x3088B858dc4cD85A001337f8E15a40b24666d321', '0x597a3e3958a6c7556d953bf6886ec355821535f3efbdf628bf3db1534c224212', 0.20000000, 2.00000000, 0.40000000, 10.00000000, 'regular', 0, 3, 'pending', 1.44000000, 46, 'completed', '0x7e77043a46e1b862fc51f2cddd468c19c62f0abc42e71e99984f08166983232c', '0x50f86adb9f9f9fde6e1928d75ccc915be0fc182049a891665dd372af96cc5afb', '0x50f86adb9f9f9fde6e1928d75ccc915be0fc182049a891665dd372af96cc5afb', 'swap_completed', NULL, 1, '2026-08-07 18:13:23', '2026-08-07 14:44:43', 1, 1, 1, 1, NULL, NULL, NULL, NULL, 1),
(34, 'SWP-20260807-91A3AB87', 999999616, 44, '0x4E107AAbeE4f7D2a12abf12cD1fc9506523E49Fb', '0x3088B858dc4cD85A001337f8E15a40b24666d321', NULL, 0.20000000, 2.00000000, 0.40000000, 10.00000000, 'combo', 0, 5, 'pending', 5.00000000, 49, 'pending', NULL, NULL, NULL, 'pending_gas_fee', NULL, 1, '2026-08-07 20:18:28', '2026-08-07 20:18:29', 0, 0, 0, 0, NULL, NULL, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `staking_treasury_payments`
--

CREATE TABLE `staking_treasury_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `stake_id` bigint(20) UNSIGNED NOT NULL,
  `ref` varchar(32) NOT NULL,
  `usdt_amount` decimal(20,8) NOT NULL COMMENT 'USDT taken from the user',
  `bman_amount` decimal(20,4) NOT NULL COMMENT 'BMAN returned (locked) to user',
  `exchange_rate` decimal(24,8) NOT NULL COMMENT 'rate snapshot at purchase',
  `exchange_type` varchar(20) NOT NULL DEFAULT 'usdt_to_bman',
  `treasury_wallet` varchar(100) DEFAULT NULL,
  `tx_hash` varchar(120) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='USDT payments routed to the Admin Treasury Wallet on stake purchase';

--
-- Dumping data for table `staking_treasury_payments`
--

INSERT INTO `staking_treasury_payments` (`id`, `user_id`, `stake_id`, `ref`, `usdt_amount`, `bman_amount`, `exchange_rate`, `exchange_type`, `treasury_wallet`, `tx_hash`, `created_at`) VALUES
(1, 999999303, 36, 'STK-20260805-3AB9B3D7', 0.10000000, 1.0000, 10.00000000, 'usdt_to_bman', '0x3088B858dc4cD85A001337f8E15a40b24666d321', NULL, '2026-08-05 20:31:20');

-- --------------------------------------------------------

--
-- Table structure for table `support`
--

CREATE TABLE `support` (
  `id` int(11) NOT NULL,
  `ticket_id` varchar(250) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` datetime DEFAULT current_timestamp(),
  `status` int(11) NOT NULL DEFAULT 0 COMMENT '0-Pending, 1-Open, 2-Close/Resolved',
  `discription` varchar(250) DEFAULT NULL,
  `ticket_status` varchar(250) DEFAULT NULL,
  `subject` varchar(250) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `files` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_message`
--

CREATE TABLE `support_message` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ticket_id` varchar(250) DEFAULT NULL,
  `message` varchar(250) DEFAULT NULL,
  `created_date` datetime NOT NULL,
  `admin` int(11) DEFAULT NULL,
  `files` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `token_config`
--

CREATE TABLE `token_config` (
  `id` int(11) NOT NULL,
  `coin_name` varchar(250) NOT NULL,
  `currency_status` int(11) NOT NULL,
  `api_call` varchar(250) DEFAULT NULL,
  `decimal` int(11) DEFAULT 2,
  `currency_value` varchar(250) NOT NULL DEFAULT '2',
  `staking_toke_symbol` varchar(250) DEFAULT NULL,
  `staking_toke_name` varchar(240) DEFAULT NULL,
  `currency_symbol` blob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `token_config`
--

INSERT INTO `token_config` (`id`, `coin_name`, `currency_status`, `api_call`, `decimal`, `currency_value`, `staking_toke_symbol`, `staking_toke_name`, `currency_symbol`) VALUES
(1, 'Feni', 1, '1b6ed52ef6a6416c1acc3095b52ac90f83e26dd35edd72f95c225795dcc38a67', 2, '10', '', 'CSQ', 0x46454e49);

-- --------------------------------------------------------

--
-- Table structure for table `token_settings`
--

CREATE TABLE `token_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `network` enum('mainnet','testnet') NOT NULL DEFAULT 'mainnet',
  `blockchain` varchar(80) NOT NULL DEFAULT 'Binance Smart Chain (BEP20)',
  `chain_id` int(11) NOT NULL DEFAULT 56,
  `rpc_url` varchar(255) NOT NULL,
  `explorer_url` varchar(255) NOT NULL,
  `explorer_api_url` varchar(255) DEFAULT NULL,
  `explorer_api_key` varchar(120) DEFAULT NULL,
  `deposit_scan_mode` enum('bscscan','rpc') NOT NULL DEFAULT 'bscscan',
  `bman_name` varchar(80) NOT NULL DEFAULT 'BMAN Token',
  `bman_symbol` varchar(20) NOT NULL DEFAULT 'BMAN',
  `bman_decimals` tinyint(3) UNSIGNED NOT NULL DEFAULT 18,
  `bman_contract` varchar(100) DEFAULT NULL,
  `bman_logo` varchar(255) DEFAULT NULL,
  `bman_min_transfer` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `bman_max_transfer` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `bman_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `usdt_name` varchar(80) NOT NULL DEFAULT 'Tether USD',
  `usdt_symbol` varchar(20) NOT NULL DEFAULT 'USDT',
  `usdt_decimals` tinyint(3) UNSIGNED NOT NULL DEFAULT 18,
  `usdt_contract` varchar(100) DEFAULT NULL,
  `minimum_deposit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `minimum_withdrawal` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `maximum_withdrawal` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `usdt_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `exchange_rate` decimal(24,8) NOT NULL DEFAULT 1.00000000,
  `exchange_type` enum('usdt_to_bman','bman_to_usdt') NOT NULL DEFAULT 'usdt_to_bman',
  `rate_effective_from` date DEFAULT NULL,
  `treasury_wallet` varchar(100) DEFAULT NULL,
  `treasury_pk_enc` varchar(500) DEFAULT NULL,
  `payout_password_hash` varchar(255) DEFAULT NULL,
  `deposit_wallet` varchar(100) DEFAULT NULL,
  `gas_wallet` varchar(100) DEFAULT NULL,
  `bonus_wallet` varchar(100) DEFAULT NULL,
  `reserve_wallet` varchar(100) DEFAULT NULL,
  `cold_wallet` varchar(100) DEFAULT NULL,
  `staking_contract` varchar(100) DEFAULT NULL,
  `bonus_contract` varchar(100) DEFAULT NULL,
  `referral_contract` varchar(100) DEFAULT NULL,
  `roi_contract` varchar(100) DEFAULT NULL,
  `minimum_confirmations` int(11) NOT NULL DEFAULT 15,
  `gas_limit` bigint(20) NOT NULL DEFAULT 210000,
  `gas_price` varchar(40) NOT NULL DEFAULT '5',
  `transaction_timeout` int(11) NOT NULL DEFAULT 300,
  `retry_count` int(11) NOT NULL DEFAULT 3,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `swap_enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'on-chain swap live?',
  `swap_dry_run` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'log-only, no broadcast',
  `swap_bonus_onchain` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'send 25% bonus BMAN on-chain too',
  `swap_auto_gas` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'auto BNB gas top-up to user deposit address before USDT send'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `token_settings`
--

INSERT INTO `token_settings` (`id`, `network`, `blockchain`, `chain_id`, `rpc_url`, `explorer_url`, `explorer_api_url`, `explorer_api_key`, `deposit_scan_mode`, `bman_name`, `bman_symbol`, `bman_decimals`, `bman_contract`, `bman_logo`, `bman_min_transfer`, `bman_max_transfer`, `bman_enabled`, `usdt_name`, `usdt_symbol`, `usdt_decimals`, `usdt_contract`, `minimum_deposit`, `minimum_withdrawal`, `maximum_withdrawal`, `usdt_enabled`, `exchange_rate`, `exchange_type`, `rate_effective_from`, `treasury_wallet`, `treasury_pk_enc`, `payout_password_hash`, `deposit_wallet`, `gas_wallet`, `bonus_wallet`, `reserve_wallet`, `cold_wallet`, `staking_contract`, `bonus_contract`, `referral_contract`, `roi_contract`, `minimum_confirmations`, `gas_limit`, `gas_price`, `transaction_timeout`, `retry_count`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`, `swap_enabled`, `swap_dry_run`, `swap_bonus_onchain`, `swap_auto_gas`) VALUES
(1, 'mainnet', 'Binance Smart Chain (BEP20)', 56, 'https://bsc-dataseed.binance.org', 'https://bscscan.com', 'https://api.etherscan.io/v2/api', 'AKJHZMIWGDCV1QAPI8WDMKGFB432HM6H24', 'bscscan', 'BMAN Token', 'BMAN', 18, '0xDe76D257b75fab445093A298bc4447dc358cF2c0', 'assets/img/token/bman_logo_1782990987.png', 0.0000, 0.0000, 1, 'Tether USD', 'USDT', 18, '0x55d398326f99059fF775485246999027B3197955', 0.1000, 0.0000, 0.0000, 1, 10.00000000, 'usdt_to_bman', '2026-07-02', '0x3088B858dc4cD85A001337f8E15a40b24666d321', 'oK9Ea6eAoVX2RZSzVzLsK1G9xYVVhoJ3YrKNOtmXV40PfwH2KhLjcAFLFUTFW00s/ZNTyad+S7aHF7bnvlRsAkKHetfZPjdfCdwRaQ5PMNZqqdd/JzWvsUPZoLsi1qnj', '$2y$10$JkaIeSbgbFd2DHG.MhSVt.C07QGF3g8b6ZkATTlgl7RJN2jR.S9B2', '0x3088B858dc4cD85A001337f8E15a40b24666d321', '', '', '', '', '', '', '', '', 15, 210000, '5', 300, 3, 1, NULL, 1, '2026-07-02 13:21:01', '2026-08-07 19:44:45', 1, 0, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `token_settings_audit`
--

CREATE TABLE `token_settings_audit` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `setting_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(30) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `changed_by` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `token_settings_audit`
--

INSERT INTO `token_settings_audit` (`id`, `setting_id`, `action`, `old_value`, `new_value`, `changed_by`, `ip_address`, `created_at`) VALUES
(1, 1, 'rate_changed', '{\"id\":\"1\",\"network\":\"mainnet\",\"blockchain\":\"Binance Smart Chain (BEP20)\",\"chain_id\":\"56\",\"rpc_url\":\"https:\\/\\/bsc-dataseed.binance.org\",\"explorer_url\":\"https:\\/\\/bscscan.com\",\"explorer_api_url\":\"https:\\/\\/api.etherscan.io\\/v2\\/api\",\"explorer_api_key\":\"AKJHZMIWGDCV1QAPI8WDMKGFB432HM6H24\",\"deposit_scan_mode\":\"bscscan\",\"bman_name\":\"BMAN Token\",\"bman_symbol\":\"BMAN\",\"bman_decimals\":\"18\",\"bman_contract\":\"0xDe76D257b75fab445093A298bc4447dc358cF2c0\",\"bman_logo\":\"assets\\/img\\/token\\/bman_logo_1782990987.png\",\"bman_min_transfer\":\"0.0000\",\"bman_max_transfer\":\"0.0000\",\"bman_enabled\":\"1\",\"usdt_name\":\"Tether USD\",\"usdt_symbol\":\"USDT\",\"usdt_decimals\":\"18\",\"usdt_contract\":\"0x55d398326f99059fF775485246999027B3197955\",\"minimum_deposit\":\"0.1000\",\"minimum_withdrawal\":\"0.0000\",\"maximum_withdrawal\":\"0.0000\",\"usdt_enabled\":\"1\",\"exchange_rate\":\"10.00000000\",\"exchange_type\":\"usdt_to_bman\",\"rate_effective_from\":\"2026-07-02\",\"treasury_wallet\":\"0x3088B858dc4cD85A001337f8E15a40b24666d321\",\"treasury_pk_enc\":\"oK9Ea6eAoVX2RZSzVzLsK1G9xYVVhoJ3YrKNOtmXV40PfwH2KhLjcAFLFUTFW00s\\/ZNTyad+S7aHF7bnvlRsAkKHetfZPjdfCdwRaQ5PMNZqqdd\\/JzWvsUPZoLsi1qnj\",\"deposit_wallet\":\"0x3088B858dc4cD85A001337f8E15a40b24666d321\",\"gas_wallet\":\"\",\"bonus_wallet\":\"\",\"reserve_wallet\":\"\",\"cold_wallet\":\"\",\"staking_contract\":\"\",\"bonus_contract\":\"\",\"referral_contract\":\"\",\"roi_contract\":\"\",\"minimum_confirmations\":\"15\",\"gas_limit\":\"210000\",\"gas_price\":\"5\",\"transaction_timeout\":\"300\",\"retry_count\":\"3\",\"status\":\"1\",\"created_by\":null,\"updated_by\":\"1\",\"created_at\":\"2026-07-02 13:21:01\",\"updated_at\":\"2026-07-03 22:51:17\",\"swap_enabled\":\"1\",\"swap_dry_run\":\"0\",\"swap_bonus_onchain\":\"1\",\"swap_auto_gas\":\"1\"}', '{\"id\":\"1\",\"network\":\"mainnet\",\"blockchain\":\"Binance Smart Chain (BEP20)\",\"chain_id\":\"56\",\"rpc_url\":\"https:\\/\\/bsc-dataseed.binance.org\",\"explorer_url\":\"https:\\/\\/bscscan.com\",\"explorer_api_url\":\"https:\\/\\/api.etherscan.io\\/v2\\/api\",\"explorer_api_key\":\"AKJHZMIWGDCV1QAPI8WDMKGFB432HM6H24\",\"deposit_scan_mode\":\"bscscan\",\"bman_name\":\"BMAN Token\",\"bman_symbol\":\"BMAN\",\"bman_decimals\":\"18\",\"bman_contract\":\"0xDe76D257b75fab445093A298bc4447dc358cF2c0\",\"bman_logo\":\"assets\\/img\\/token\\/bman_logo_1782990987.png\",\"bman_min_transfer\":\"0.0000\",\"bman_max_transfer\":\"0.0000\",\"bman_enabled\":\"1\",\"usdt_name\":\"Tether USD\",\"usdt_symbol\":\"USDT\",\"usdt_decimals\":\"18\",\"usdt_contract\":\"0x55d398326f99059fF775485246999027B3197955\",\"minimum_deposit\":\"0.1000\",\"minimum_withdrawal\":\"0.0000\",\"maximum_withdrawal\":\"0.0000\",\"usdt_enabled\":\"1\",\"exchange_rate\":\"1.00000000\",\"exchange_type\":\"usdt_to_bman\",\"rate_effective_from\":\"2026-07-02\",\"treasury_wallet\":\"0x3088B858dc4cD85A001337f8E15a40b24666d321\",\"treasury_pk_enc\":\"oK9Ea6eAoVX2RZSzVzLsK1G9xYVVhoJ3YrKNOtmXV40PfwH2KhLjcAFLFUTFW00s\\/ZNTyad+S7aHF7bnvlRsAkKHetfZPjdfCdwRaQ5PMNZqqdd\\/JzWvsUPZoLsi1qnj\",\"deposit_wallet\":\"0x3088B858dc4cD85A001337f8E15a40b24666d321\",\"gas_wallet\":\"\",\"bonus_wallet\":\"\",\"reserve_wallet\":\"\",\"cold_wallet\":\"\",\"staking_contract\":\"\",\"bonus_contract\":\"\",\"referral_contract\":\"\",\"roi_contract\":\"\",\"minimum_confirmations\":\"15\",\"gas_limit\":\"210000\",\"gas_price\":\"5\",\"transaction_timeout\":\"300\",\"retry_count\":\"3\",\"status\":\"1\",\"created_by\":null,\"updated_by\":\"1\",\"created_at\":\"2026-07-02 13:21:01\",\"updated_at\":\"2026-07-10 11:34:51\",\"swap_enabled\":\"1\",\"swap_dry_run\":\"0\",\"swap_bonus_onchain\":\"1\",\"swap_auto_gas\":\"1\"}', 1, '192.168.29.18', '2026-07-10 11:34:51'),
(2, 1, 'rate_changed', '{\"id\":\"1\",\"network\":\"mainnet\",\"blockchain\":\"Binance Smart Chain (BEP20)\",\"chain_id\":\"56\",\"rpc_url\":\"https:\\/\\/bsc-dataseed.binance.org\",\"explorer_url\":\"https:\\/\\/bscscan.com\",\"explorer_api_url\":\"https:\\/\\/api.etherscan.io\\/v2\\/api\",\"explorer_api_key\":\"AKJHZMIWGDCV1QAPI8WDMKGFB432HM6H24\",\"deposit_scan_mode\":\"bscscan\",\"bman_name\":\"BMAN Token\",\"bman_symbol\":\"BMAN\",\"bman_decimals\":\"18\",\"bman_contract\":\"0xDe76D257b75fab445093A298bc4447dc358cF2c0\",\"bman_logo\":\"assets\\/img\\/token\\/bman_logo_1782990987.png\",\"bman_min_transfer\":\"0.0000\",\"bman_max_transfer\":\"0.0000\",\"bman_enabled\":\"1\",\"usdt_name\":\"Tether USD\",\"usdt_symbol\":\"USDT\",\"usdt_decimals\":\"18\",\"usdt_contract\":\"0x55d398326f99059fF775485246999027B3197955\",\"minimum_deposit\":\"0.1000\",\"minimum_withdrawal\":\"0.0000\",\"maximum_withdrawal\":\"0.0000\",\"usdt_enabled\":\"1\",\"exchange_rate\":\"1.00000000\",\"exchange_type\":\"usdt_to_bman\",\"rate_effective_from\":\"2026-07-02\",\"treasury_wallet\":\"0x3088B858dc4cD85A001337f8E15a40b24666d321\",\"treasury_pk_enc\":\"oK9Ea6eAoVX2RZSzVzLsK1G9xYVVhoJ3YrKNOtmXV40PfwH2KhLjcAFLFUTFW00s\\/ZNTyad+S7aHF7bnvlRsAkKHetfZPjdfCdwRaQ5PMNZqqdd\\/JzWvsUPZoLsi1qnj\",\"deposit_wallet\":\"0x3088B858dc4cD85A001337f8E15a40b24666d321\",\"gas_wallet\":\"\",\"bonus_wallet\":\"\",\"reserve_wallet\":\"\",\"cold_wallet\":\"\",\"staking_contract\":\"\",\"bonus_contract\":\"\",\"referral_contract\":\"\",\"roi_contract\":\"\",\"minimum_confirmations\":\"15\",\"gas_limit\":\"210000\",\"gas_price\":\"5\",\"transaction_timeout\":\"300\",\"retry_count\":\"3\",\"status\":\"1\",\"created_by\":null,\"updated_by\":\"1\",\"created_at\":\"2026-07-02 13:21:01\",\"updated_at\":\"2026-07-10 11:34:51\",\"swap_enabled\":\"1\",\"swap_dry_run\":\"0\",\"swap_bonus_onchain\":\"1\",\"swap_auto_gas\":\"1\"}', '{\"id\":\"1\",\"network\":\"mainnet\",\"blockchain\":\"Binance Smart Chain (BEP20)\",\"chain_id\":\"56\",\"rpc_url\":\"https:\\/\\/bsc-dataseed.binance.org\",\"explorer_url\":\"https:\\/\\/bscscan.com\",\"explorer_api_url\":\"https:\\/\\/api.etherscan.io\\/v2\\/api\",\"explorer_api_key\":\"AKJHZMIWGDCV1QAPI8WDMKGFB432HM6H24\",\"deposit_scan_mode\":\"bscscan\",\"bman_name\":\"BMAN Token\",\"bman_symbol\":\"BMAN\",\"bman_decimals\":\"18\",\"bman_contract\":\"0xDe76D257b75fab445093A298bc4447dc358cF2c0\",\"bman_logo\":\"assets\\/img\\/token\\/bman_logo_1782990987.png\",\"bman_min_transfer\":\"0.0000\",\"bman_max_transfer\":\"0.0000\",\"bman_enabled\":\"1\",\"usdt_name\":\"Tether USD\",\"usdt_symbol\":\"USDT\",\"usdt_decimals\":\"18\",\"usdt_contract\":\"0x55d398326f99059fF775485246999027B3197955\",\"minimum_deposit\":\"0.1000\",\"minimum_withdrawal\":\"0.0000\",\"maximum_withdrawal\":\"0.0000\",\"usdt_enabled\":\"1\",\"exchange_rate\":\"10.00000000\",\"exchange_type\":\"usdt_to_bman\",\"rate_effective_from\":\"2026-07-02\",\"treasury_wallet\":\"0x3088B858dc4cD85A001337f8E15a40b24666d321\",\"treasury_pk_enc\":\"oK9Ea6eAoVX2RZSzVzLsK1G9xYVVhoJ3YrKNOtmXV40PfwH2KhLjcAFLFUTFW00s\\/ZNTyad+S7aHF7bnvlRsAkKHetfZPjdfCdwRaQ5PMNZqqdd\\/JzWvsUPZoLsi1qnj\",\"deposit_wallet\":\"0x3088B858dc4cD85A001337f8E15a40b24666d321\",\"gas_wallet\":\"\",\"bonus_wallet\":\"\",\"reserve_wallet\":\"\",\"cold_wallet\":\"\",\"staking_contract\":\"\",\"bonus_contract\":\"\",\"referral_contract\":\"\",\"roi_contract\":\"\",\"minimum_confirmations\":\"15\",\"gas_limit\":\"210000\",\"gas_price\":\"5\",\"transaction_timeout\":\"300\",\"retry_count\":\"3\",\"status\":\"1\",\"created_by\":null,\"updated_by\":\"1\",\"created_at\":\"2026-07-02 13:21:01\",\"updated_at\":\"2026-07-10 17:45:00\",\"swap_enabled\":\"1\",\"swap_dry_run\":\"0\",\"swap_bonus_onchain\":\"1\",\"swap_auto_gas\":\"1\"}', 1, '192.168.29.18', '2026-07-10 17:45:00'),
(3, 1, 'rate_changed', '{\"id\":\"1\",\"network\":\"mainnet\",\"blockchain\":\"Binance Smart Chain (BEP20)\",\"chain_id\":\"56\",\"rpc_url\":\"https:\\/\\/bsc-dataseed.binance.org\",\"explorer_url\":\"https:\\/\\/bscscan.com\",\"explorer_api_url\":\"https:\\/\\/api.etherscan.io\\/v2\\/api\",\"explorer_api_key\":\"AKJHZMIWGDCV1QAPI8WDMKGFB432HM6H24\",\"deposit_scan_mode\":\"bscscan\",\"bman_name\":\"BMAN Token\",\"bman_symbol\":\"BMAN\",\"bman_decimals\":\"18\",\"bman_contract\":\"0xDe76D257b75fab445093A298bc4447dc358cF2c0\",\"bman_logo\":\"assets\\/img\\/token\\/bman_logo_1782990987.png\",\"bman_min_transfer\":\"0.0000\",\"bman_max_transfer\":\"0.0000\",\"bman_enabled\":\"1\",\"usdt_name\":\"Tether USD\",\"usdt_symbol\":\"USDT\",\"usdt_decimals\":\"18\",\"usdt_contract\":\"0x55d398326f99059fF775485246999027B3197955\",\"minimum_deposit\":\"0.1000\",\"minimum_withdrawal\":\"0.0000\",\"maximum_withdrawal\":\"0.0000\",\"usdt_enabled\":\"1\",\"exchange_rate\":\"10.00000000\",\"exchange_type\":\"usdt_to_bman\",\"rate_effective_from\":\"2026-07-02\",\"treasury_wallet\":\"0x3088B858dc4cD85A001337f8E15a40b24666d321\",\"treasury_pk_enc\":\"oK9Ea6eAoVX2RZSzVzLsK1G9xYVVhoJ3YrKNOtmXV40PfwH2KhLjcAFLFUTFW00s\\/ZNTyad+S7aHF7bnvlRsAkKHetfZPjdfCdwRaQ5PMNZqqdd\\/JzWvsUPZoLsi1qnj\",\"deposit_wallet\":\"0x3088B858dc4cD85A001337f8E15a40b24666d321\",\"gas_wallet\":\"\",\"bonus_wallet\":\"\",\"reserve_wallet\":\"\",\"cold_wallet\":\"\",\"staking_contract\":\"\",\"bonus_contract\":\"\",\"referral_contract\":\"\",\"roi_contract\":\"\",\"minimum_confirmations\":\"15\",\"gas_limit\":\"210000\",\"gas_price\":\"5\",\"transaction_timeout\":\"300\",\"retry_count\":\"3\",\"status\":\"1\",\"created_by\":null,\"updated_by\":\"1\",\"created_at\":\"2026-07-02 13:21:01\",\"updated_at\":\"2026-07-10 21:29:04\",\"swap_enabled\":\"1\",\"swap_dry_run\":\"0\",\"swap_bonus_onchain\":\"1\",\"swap_auto_gas\":\"1\"}', '{\"id\":\"1\",\"network\":\"mainnet\",\"blockchain\":\"Binance Smart Chain (BEP20)\",\"chain_id\":\"56\",\"rpc_url\":\"https:\\/\\/bsc-dataseed.binance.org\",\"explorer_url\":\"https:\\/\\/bscscan.com\",\"explorer_api_url\":\"https:\\/\\/api.etherscan.io\\/v2\\/api\",\"explorer_api_key\":\"AKJHZMIWGDCV1QAPI8WDMKGFB432HM6H24\",\"deposit_scan_mode\":\"bscscan\",\"bman_name\":\"BMAN Token\",\"bman_symbol\":\"BMAN\",\"bman_decimals\":\"18\",\"bman_contract\":\"0xDe76D257b75fab445093A298bc4447dc358cF2c0\",\"bman_logo\":\"assets\\/img\\/token\\/bman_logo_1782990987.png\",\"bman_min_transfer\":\"0.0000\",\"bman_max_transfer\":\"0.0000\",\"bman_enabled\":\"1\",\"usdt_name\":\"Tether USD\",\"usdt_symbol\":\"USDT\",\"usdt_decimals\":\"18\",\"usdt_contract\":\"0x55d398326f99059fF775485246999027B3197955\",\"minimum_deposit\":\"0.1000\",\"minimum_withdrawal\":\"0.0000\",\"maximum_withdrawal\":\"0.0000\",\"usdt_enabled\":\"1\",\"exchange_rate\":\"1.00000000\",\"exchange_type\":\"usdt_to_bman\",\"rate_effective_from\":\"2026-07-02\",\"treasury_wallet\":\"0x3088B858dc4cD85A001337f8E15a40b24666d321\",\"treasury_pk_enc\":\"oK9Ea6eAoVX2RZSzVzLsK1G9xYVVhoJ3YrKNOtmXV40PfwH2KhLjcAFLFUTFW00s\\/ZNTyad+S7aHF7bnvlRsAkKHetfZPjdfCdwRaQ5PMNZqqdd\\/JzWvsUPZoLsi1qnj\",\"deposit_wallet\":\"0x3088B858dc4cD85A001337f8E15a40b24666d321\",\"gas_wallet\":\"\",\"bonus_wallet\":\"\",\"reserve_wallet\":\"\",\"cold_wallet\":\"\",\"staking_contract\":\"\",\"bonus_contract\":\"\",\"referral_contract\":\"\",\"roi_contract\":\"\",\"minimum_confirmations\":\"15\",\"gas_limit\":\"210000\",\"gas_price\":\"5\",\"transaction_timeout\":\"300\",\"retry_count\":\"3\",\"status\":\"1\",\"created_by\":null,\"updated_by\":\"1\",\"created_at\":\"2026-07-02 13:21:01\",\"updated_at\":\"2026-07-16 15:16:25\",\"swap_enabled\":\"1\",\"swap_dry_run\":\"0\",\"swap_bonus_onchain\":\"1\",\"swap_auto_gas\":\"1\"}', 1, '192.168.29.18', '2026-07-16 15:16:25'),
(4, 1, 'rate_changed', '{\"id\":\"1\",\"network\":\"mainnet\",\"blockchain\":\"Binance Smart Chain (BEP20)\",\"chain_id\":\"56\",\"rpc_url\":\"https:\\/\\/bsc-dataseed.binance.org\",\"explorer_url\":\"https:\\/\\/bscscan.com\",\"explorer_api_url\":\"https:\\/\\/api.etherscan.io\\/v2\\/api\",\"explorer_api_key\":\"AKJHZMIWGDCV1QAPI8WDMKGFB432HM6H24\",\"deposit_scan_mode\":\"bscscan\",\"bman_name\":\"BMAN Token\",\"bman_symbol\":\"BMAN\",\"bman_decimals\":\"18\",\"bman_contract\":\"0xDe76D257b75fab445093A298bc4447dc358cF2c0\",\"bman_logo\":\"assets\\/img\\/token\\/bman_logo_1782990987.png\",\"bman_min_transfer\":\"0.0000\",\"bman_max_transfer\":\"0.0000\",\"bman_enabled\":\"1\",\"usdt_name\":\"Tether USD\",\"usdt_symbol\":\"USDT\",\"usdt_decimals\":\"18\",\"usdt_contract\":\"0x55d398326f99059fF775485246999027B3197955\",\"minimum_deposit\":\"0.1000\",\"minimum_withdrawal\":\"0.0000\",\"maximum_withdrawal\":\"0.0000\",\"usdt_enabled\":\"1\",\"exchange_rate\":\"1.00000000\",\"exchange_type\":\"usdt_to_bman\",\"rate_effective_from\":\"2026-07-02\",\"treasury_wallet\":\"0x3088B858dc4cD85A001337f8E15a40b24666d321\",\"treasury_pk_enc\":\"oK9Ea6eAoVX2RZSzVzLsK1G9xYVVhoJ3YrKNOtmXV40PfwH2KhLjcAFLFUTFW00s\\/ZNTyad+S7aHF7bnvlRsAkKHetfZPjdfCdwRaQ5PMNZqqdd\\/JzWvsUPZoLsi1qnj\",\"deposit_wallet\":\"0x3088B858dc4cD85A001337f8E15a40b24666d321\",\"gas_wallet\":\"\",\"bonus_wallet\":\"\",\"reserve_wallet\":\"\",\"cold_wallet\":\"\",\"staking_contract\":\"\",\"bonus_contract\":\"\",\"referral_contract\":\"\",\"roi_contract\":\"\",\"minimum_confirmations\":\"15\",\"gas_limit\":\"210000\",\"gas_price\":\"5\",\"transaction_timeout\":\"300\",\"retry_count\":\"3\",\"status\":\"1\",\"created_by\":null,\"updated_by\":\"1\",\"created_at\":\"2026-07-02 13:21:01\",\"updated_at\":\"2026-07-16 15:16:25\",\"swap_enabled\":\"1\",\"swap_dry_run\":\"0\",\"swap_bonus_onchain\":\"1\",\"swap_auto_gas\":\"1\"}', '{\"id\":\"1\",\"network\":\"mainnet\",\"blockchain\":\"Binance Smart Chain (BEP20)\",\"chain_id\":\"56\",\"rpc_url\":\"https:\\/\\/bsc-dataseed.binance.org\",\"explorer_url\":\"https:\\/\\/bscscan.com\",\"explorer_api_url\":\"https:\\/\\/api.etherscan.io\\/v2\\/api\",\"explorer_api_key\":\"AKJHZMIWGDCV1QAPI8WDMKGFB432HM6H24\",\"deposit_scan_mode\":\"bscscan\",\"bman_name\":\"BMAN Token\",\"bman_symbol\":\"BMAN\",\"bman_decimals\":\"18\",\"bman_contract\":\"0xDe76D257b75fab445093A298bc4447dc358cF2c0\",\"bman_logo\":\"assets\\/img\\/token\\/bman_logo_1782990987.png\",\"bman_min_transfer\":\"0.0000\",\"bman_max_transfer\":\"0.0000\",\"bman_enabled\":\"1\",\"usdt_name\":\"Tether USD\",\"usdt_symbol\":\"USDT\",\"usdt_decimals\":\"18\",\"usdt_contract\":\"0x55d398326f99059fF775485246999027B3197955\",\"minimum_deposit\":\"0.1000\",\"minimum_withdrawal\":\"0.0000\",\"maximum_withdrawal\":\"0.0000\",\"usdt_enabled\":\"1\",\"exchange_rate\":\"10.00000000\",\"exchange_type\":\"usdt_to_bman\",\"rate_effective_from\":\"2026-07-02\",\"treasury_wallet\":\"0x3088B858dc4cD85A001337f8E15a40b24666d321\",\"treasury_pk_enc\":\"oK9Ea6eAoVX2RZSzVzLsK1G9xYVVhoJ3YrKNOtmXV40PfwH2KhLjcAFLFUTFW00s\\/ZNTyad+S7aHF7bnvlRsAkKHetfZPjdfCdwRaQ5PMNZqqdd\\/JzWvsUPZoLsi1qnj\",\"deposit_wallet\":\"0x3088B858dc4cD85A001337f8E15a40b24666d321\",\"gas_wallet\":\"\",\"bonus_wallet\":\"\",\"reserve_wallet\":\"\",\"cold_wallet\":\"\",\"staking_contract\":\"\",\"bonus_contract\":\"\",\"referral_contract\":\"\",\"roi_contract\":\"\",\"minimum_confirmations\":\"15\",\"gas_limit\":\"210000\",\"gas_price\":\"5\",\"transaction_timeout\":\"300\",\"retry_count\":\"3\",\"status\":\"1\",\"created_by\":null,\"updated_by\":\"1\",\"created_at\":\"2026-07-02 13:21:01\",\"updated_at\":\"2026-07-17 22:21:50\",\"swap_enabled\":\"1\",\"swap_dry_run\":\"0\",\"swap_bonus_onchain\":\"1\",\"swap_auto_gas\":\"1\"}', 1, '192.168.29.18', '2026-07-17 22:21:50'),
(5, 1, 'payout_password_set', NULL, '{\"note\":\"payout password changed \\u2014 value not logged\"}', 1, '::1', '2026-08-07 15:40:05'),
(6, 1, 'payout_password_set', NULL, '{\"note\":\"payout password changed \\u2014 value not logged\"}', 1, '::1', '2026-08-07 19:44:45');

-- --------------------------------------------------------

--
-- Table structure for table `treasury_direct_send`
--

CREATE TABLE `treasury_direct_send` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ref` varchar(40) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `to_address` varchar(100) NOT NULL,
  `amount` decimal(30,8) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `dry_run` tinyint(1) NOT NULL DEFAULT 1,
  `tx_hash` varchar(120) DEFAULT NULL,
  `network` varchar(40) DEFAULT NULL,
  `error_message` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `treasury_direct_send`
--

INSERT INTO `treasury_direct_send` (`id`, `ref`, `admin_id`, `user_id`, `to_address`, `amount`, `reason`, `status`, `dry_run`, `tx_hash`, `network`, `error_message`, `created_at`, `completed_at`) VALUES
(1, 'TDS-20260723-991AC0DE', 1, 1, '0xcb3c5E2bcef54Fec78974CAe31828cF9F33dA9c7', 0.10000000, 'By admin direct sending', 'completed', 0, '0x78788087ce4ad691c3ab9c03cd708c9ce2c6f7103e8521defb2c4d727024ae9e', 'mainnet', NULL, '2026-07-23 17:04:42', '2026-07-23 13:34:44'),
(2, 'TDS-20260723-1EFF8B7A', 1, 1, '0xcb3c5E2bcef54Fec78974CAe31828cF9F33dA9c7', 2.00000000, 'Direct Send SATZADMIN', 'completed', 0, '0x99bd7e1ef6aaa2967ba89b3459d435a2023ffb48237c520eab14d1df156d9329', 'mainnet', NULL, '2026-07-23 17:06:37', '2026-07-23 13:36:39'),
(3, 'TDS-20260723-5A69BEDC', 1, 2, '0x365E2F051Cd601F8828cf33c3D1B7C87a0141c1b', 2.00000000, 'Direct By ABI ADMIN', 'completed', 0, '0xa95dd6d48c6c1f0314ccb1c5f8286acb4f4b4b9a50bc9023f604a5200bc6c9d8', 'mainnet', NULL, '2026-07-23 17:08:51', '2026-07-23 13:38:54'),
(4, 'TDS-20260807-7DA44F81', 1, 999999606, '0x2E228070726Ec09A6e0a9d89287F900B5DD2D3Db', 0.10000000, 'By admin', 'completed', 0, '0x2ab5a3a69903d683be0fc695d5e52154e9f276cef5da6bcba3303ff0a656bb58', 'mainnet', NULL, '2026-08-07 13:46:11', '2026-08-07 10:16:13'),
(5, 'TDS-20260807-D218842D', 1, 999999606, '0x2E228070726Ec09A6e0a9d89287F900B5DD2D3Db', 2.00000000, '', 'completed', 0, '0xb2cd662d98f6ee865706d3c0e8308015674501d0d709659193c7af01c0e6c93e', 'mainnet', NULL, '2026-08-07 13:47:02', '2026-08-07 10:17:04');

-- --------------------------------------------------------

--
-- Table structure for table `treasury_direct_send_settings`
--

CREATE TABLE `treasury_direct_send_settings` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'master switch — 0 = the tool refuses to send',
  `dry_run` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = record a DRYRUN- hash, never broadcast',
  `min_treasury_reserve` decimal(30,8) NOT NULL DEFAULT 0.00000000 COMMENT 'refuses to send if it would drop the Treasury on-chain BMAN balance below this',
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `treasury_direct_send_settings`
--

INSERT INTO `treasury_direct_send_settings` (`id`, `enabled`, `dry_run`, `min_treasury_reserve`, `updated_by`, `updated_at`) VALUES
(1, 1, 0, 0.00000000, 1, '2026-07-23 17:04:17');

-- --------------------------------------------------------

--
-- Table structure for table `treasury_key_reveal_log`
--

CREATE TABLE `treasury_key_reveal_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `setting_id` int(10) UNSIGNED DEFAULT NULL,
  `admin_id` int(11) NOT NULL,
  `withdraw_id` int(10) UNSIGNED DEFAULT NULL,
  `outcome` enum('success','wrong_password','locked_out','no_password_set') NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `treasury_key_reveal_log`
--

INSERT INTO `treasury_key_reveal_log` (`id`, `setting_id`, `admin_id`, `withdraw_id`, `outcome`, `ip_address`, `created_at`) VALUES
(1, 1, 1, 2, 'no_password_set', '::1', '2026-08-07 15:38:23'),
(2, 1, 1, 2, 'wrong_password', '::1', '2026-08-07 15:41:36'),
(3, 1, 1, 2, 'wrong_password', '::1', '2026-08-07 15:43:06'),
(4, 1, 1, 2, 'wrong_password', '::1', '2026-08-07 15:43:06'),
(5, 1, 1, 2, 'wrong_password', '::1', '2026-08-07 15:43:06'),
(6, 1, 1, 2, 'locked_out', '::1', '2026-08-07 15:43:07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `sponser` varchar(250) DEFAULT NULL,
  `referral_id` varchar(250) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expiry` datetime DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `state` varchar(150) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `kyc_status` enum('none','pending','under_review','resubmitted','approved','rejected') NOT NULL DEFAULT 'none',
  `kyc_last_submitted_at` datetime DEFAULT NULL,
  `kyc_verified_at` datetime DEFAULT NULL,
  `kyc_reviewer_id` int(10) UNSIGNED DEFAULT NULL,
  `zipcode` varchar(255) DEFAULT NULL,
  `dob` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `register_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `get_status` int(20) DEFAULT 0,
  `rank_id` varchar(50) DEFAULT '0',
  `wallet_id` int(50) DEFAULT 0,
  `password_update` int(11) DEFAULT 0,
  `withdraw_status` int(11) DEFAULT 0,
  `updated_date` datetime DEFAULT NULL,
  `withdraw_action` varchar(250) DEFAULT '0',
  `twofa_key` varchar(250) DEFAULT NULL,
  `twofa_status` int(11) DEFAULT 0,
  `email_verify_status` int(11) NOT NULL DEFAULT 1,
  `twofa_secret` varchar(255) DEFAULT NULL,
  `left_id` int(11) DEFAULT 0,
  `right_id` int(11) DEFAULT 0,
  `position` enum('left','right') DEFAULT NULL,
  `twofactorsecret` varchar(250) DEFAULT NULL,
  `twofacode_path` varchar(250) DEFAULT NULL,
  `country` varchar(250) DEFAULT NULL,
  `first_name` varchar(250) DEFAULT NULL,
  `last_name` varchar(150) DEFAULT NULL,
  `language_set` varchar(150) DEFAULT 'AS',
  `communication_set` varchar(150) DEFAULT 'en',
  `time_zone` varchar(150) DEFAULT 'Arizona',
  `profile_img` varchar(250) DEFAULT NULL,
  `success_payments` tinyint(1) NOT NULL DEFAULT 0,
  `payouts` tinyint(1) NOT NULL DEFAULT 0,
  `product_commission` tinyint(1) NOT NULL DEFAULT 0,
  `refund_alerts` tinyint(1) NOT NULL DEFAULT 0,
  `invoice_payments` tinyint(1) NOT NULL DEFAULT 0,
  `prefs_updated_at` datetime DEFAULT NULL,
  `package_id` int(11) DEFAULT 0,
  `transfer_password` varchar(255) DEFAULT NULL,
  `transfer_password_set_at` datetime DEFAULT NULL,
  `staking_wallet` decimal(20,4) DEFAULT 0.0000 COMMENT 'Locked staking earnings (2% matching)',
  `ceiling_wallet_held` decimal(20,4) DEFAULT 0.0000 COMMENT 'Amount currently held in ceiling (not accessible)',
  `account_frozen` tinyint(1) NOT NULL DEFAULT 0,
  `account_frozen_at` datetime DEFAULT NULL,
  `last_active_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `sponser`, `referral_id`, `email`, `password_reset_token`, `password_reset_expiry`, `password`, `username`, `contact`, `address`, `address_line2`, `state`, `gender`, `image`, `role_id`, `kyc_status`, `kyc_last_submitted_at`, `kyc_verified_at`, `kyc_reviewer_id`, `zipcode`, `dob`, `status`, `register_date`, `get_status`, `rank_id`, `wallet_id`, `password_update`, `withdraw_status`, `updated_date`, `withdraw_action`, `twofa_key`, `twofa_status`, `email_verify_status`, `twofa_secret`, `left_id`, `right_id`, `position`, `twofactorsecret`, `twofacode_path`, `country`, `first_name`, `last_name`, `language_set`, `communication_set`, `time_zone`, `profile_img`, `success_payments`, `payouts`, `product_commission`, `refund_alerts`, `invoice_payments`, `prefs_updated_at`, `package_id`, `transfer_password`, `transfer_password_set_at`, `staking_wallet`, `ceiling_wallet_held`, `account_frozen`, `account_frozen_at`, `last_active_at`) VALUES
(1, 'Admin', '0', 'NEXMAN001', 'admin@gmail.com', NULL, NULL, '$2y$10$8bcTo/6HZzhmYLOSDBxIReMNSXAxIZuRdptLa8StDskhlr9Ic9uhq', 'Admin', '9009009000', 'Admin Nagar', NULL, NULL, 'Male', 'YADU_Logo.JPG', 1, 'approved', '2026-07-16 10:24:49', NULL, NULL, '23232', '1999-08-03', 1, '2024-01-04 16:16:38', 0, '0', 0, 0, 0, NULL, '0', NULL, 0, 0, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'AS', '1', 'Arizona', NULL, 0, 0, 0, 0, 0, NULL, 4, '$2y$10$UKJWFNQ44UJMe1efBjZlYepmrDqLY9wttD8kF7dnbDf4tMzkKtP/C', '2026-07-17 17:34:01', 0.0000, 0.0000, 0, NULL, '2026-08-06 16:52:52'),
(2, NULL, '1', 'NEXMAN567021', 'siva@yopmail.com', NULL, NULL, '$2y$10$6aXpgyhfFxEaeb0IzN3mneJjhBTLGZK8J.MmLLynj9jz14mwZov.6', 'siva', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-20 07:24:41', 0, '0', 0, 0, 0, '2026-07-20 13:05:40', '0', NULL, 0, 0, '4KKIUJEKZF6KIVIE', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'AS', 'en', 'Arizona', NULL, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, 0.0000, 0.0000, 0, NULL, '2026-08-06 16:48:31'),
(3, NULL, '1', 'NEXMAN946966', 'trustwallet@yopmail.com', NULL, NULL, '$2y$10$S.eTzh2MtUAOouomDcKiiO9KsRRK9stvs07qiItEDtJVPHd5zN1A2', 'trustwallet', '89234923423', 'A', 'Ad', 'State', 'female', NULL, NULL, 'approved', '2026-07-20 19:01:29', NULL, NULL, '234234', '1999-07-20', 1, '2026-07-20 16:33:50', 0, '0', 0, 0, 0, '2026-07-21 10:02:39', '0', NULL, 1, 1, 'AWK2IJ5OVBJQX7PQ', 0, 0, NULL, NULL, NULL, 'India', 'trustwallet', 'lastname', 'AS', 'en', 'Calcutta', 'profile_6a5f279f900ee.png', 0, 0, 0, 0, 0, NULL, 0, '$2y$10$Q0HuovVY/wsbtTYI0eOvC.Hjcz/Vo21Hpg98ZadFVLI204mTVue0u', '2026-07-21 10:04:17', 0.0000, 0.0000, 0, NULL, '2026-08-05 15:29:25'),
(4, NULL, '3', 'NEXMAN830893', 'rightbytrustwallet@yopmail.com', NULL, NULL, '$2y$10$gsXXrYaKjgzxy8aJ.kH1ReR3XwK.iSTTF6S0mxGSSzWvp3A/suCem', 'rightbytrustwallet', '237948792348', 'A', 'A2', 'S', 'male', NULL, NULL, 'approved', '2026-07-21 09:16:56', NULL, NULL, '234234', '2000-07-20', 1, '2026-07-20 16:58:40', 0, '0', 0, 0, 0, '2026-07-21 09:17:12', '0', NULL, 0, 1, NULL, 0, 0, NULL, NULL, NULL, 'Country', 'rightbytrustwallet', 'last name', 'AS', 'en', 'Arizona', 'profile_6a5f19c81b3d4.jpg', 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, 0.0000, 0.0000, 0, NULL, '2026-07-27 12:22:45'),
(22, NULL, '1', 'NEXMAN240385', 'viki@yopmail.com', '4f8e66a76556c3e8bad38a9c91a500c563a3ff99588a760264104b6954453fde', '2026-08-06 09:09:09', '$2y$10$a.x3M3Y8LyBH3Pfz0NqZ8.R7fvAkX4K0vUgcrQ73omVkU4JHRYUji', 'viki', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-30 11:40:26', 0, '0', 0, 0, 0, NULL, '0', NULL, 0, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'AS', 'en', 'Arizona', NULL, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, 0.0000, 0.0000, 0, NULL, '2026-08-06 13:36:19'),
(23, NULL, '1', 'NEXMAN428023', 'lak@yopmail.com', NULL, NULL, '$2y$10$4VD0h4izkajGWVG0lSyG3.jbF8MuWIdtHHUi9HyrXdMRjXO/miXy6', 'lak', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-30 11:40:28', 0, '0', 0, 0, 0, NULL, '0', NULL, 0, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'AS', 'en', 'Arizona', NULL, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, 0.0000, 0.0000, 0, NULL, '2026-08-05 17:54:02'),
(999999504, NULL, '22', 'NEXMAN870173', 'ccc@yopmail.com', NULL, NULL, '$2y$10$togn0esYcgpa4XprIvnXN.5sx.u.GyVZtoFuJzHoN5u9aenMZKsFC', 'ccc', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', NULL, NULL, NULL, NULL, NULL, 1, '2026-08-06 06:11:54', 0, '0', 0, 0, 0, NULL, '0', NULL, 0, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'AS', 'en', 'Arizona', NULL, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, 0.0000, 0.0000, 0, NULL, '2026-08-06 18:06:47'),
(999999505, NULL, '22', 'NEXMAN329327', 'bbb@yopmail.com', NULL, NULL, '$2y$10$SzxVX/sGFfoFncZ.MNoqgu8GPxvonWkneakGxJqY93Kmfnx3Pm1gm', 'bbb', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', NULL, NULL, NULL, NULL, NULL, 1, '2026-08-06 06:22:47', 0, '0', 0, 0, 0, NULL, '0', NULL, 0, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'AS', 'en', 'Arizona', NULL, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, 0.0000, 0.0000, 0, NULL, '2026-08-06 12:23:10'),
(999999602, NULL, '999999504', 'NEXMAN580990', 'eee@yopmail.com', NULL, NULL, '$2y$10$88Z2g4xlFCh/5i9sO9RaGuuBJnF7LI0XeqtUDwnRPkC2ezrEWYi2O', 'eee', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', NULL, NULL, NULL, NULL, NULL, 1, '2026-08-06 10:11:25', 0, '0', 0, 0, 0, NULL, '0', NULL, 0, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'AS', 'en', 'Arizona', NULL, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, 0.0000, 0.0000, 0, NULL, '2026-08-06 13:38:18'),
(999999603, NULL, '999999602', 'NEXMAN309630', 'ddd@yopmail.com', NULL, NULL, '$2y$10$JBysbMCgAR/pwKzny4HRAeMwV/WUgoT3pZzPyTJAsXG4iZE8WkAae', 'ddd', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', NULL, NULL, NULL, NULL, NULL, 1, '2026-08-06 10:24:59', 0, '0', 0, 0, 0, NULL, '0', NULL, 0, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'AS', 'en', 'Arizona', NULL, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, 0.0000, 0.0000, 0, NULL, '2026-08-06 13:19:12'),
(999999604, NULL, '999999504', 'NEXMAN893886', 'ggg@yopmail.com', NULL, NULL, '$2y$10$TFWNmfco.nfRKDwTypKjb.tQZl/q04x11TsjuG0h5BoxhAJP8uHjy', 'ggg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', NULL, NULL, NULL, NULL, NULL, 1, '2026-08-06 11:19:41', 0, '0', 0, 0, 0, NULL, '0', NULL, 0, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'AS', 'en', 'Arizona', NULL, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, 0.0000, 0.0000, 0, NULL, '2026-08-06 13:44:39'),
(999999605, NULL, '999999504', 'NEXMAN994816', 'hhh@yopmail.com', NULL, NULL, '$2y$10$WuaUn9LCiqLFj4GCdRcIHOTBuY/eihvGMPIxxET0f63WRTbFGII8q', 'hhh', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', NULL, NULL, NULL, NULL, NULL, 1, '2026-08-06 11:23:13', 0, '0', 0, 0, 0, NULL, '0', NULL, 0, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'AS', 'en', 'Arizona', NULL, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, 0.0000, 0.0000, 0, NULL, '2026-08-07 20:07:14'),
(999999606, NULL, '999999504', 'NEXMAN892126', 'iii@yopmail.com', NULL, NULL, '$2y$10$R4Csh6D0oZpHfn2oKeACb.s3sXNYqBydU8l2QHFn3WtSx/1T8DMnW', 'iii', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', NULL, NULL, NULL, NULL, NULL, 1, '2026-08-06 11:24:36', 0, '0', 0, 0, 0, NULL, '0', NULL, 0, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'AS', 'en', 'Arizona', NULL, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, 0.0000, 0.0000, 0, NULL, '2026-08-07 20:04:58'),
(999999607, NULL, '999999602', 'NEXMAN770321', 'jjj@yopmail.com', NULL, NULL, '$2y$10$1nqNuPZtIvj2tzCunYLkPOlpBfgs.dfMkDsW6aEeAiDXOnYLgTnWK', 'jjj', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', NULL, NULL, NULL, NULL, NULL, 1, '2026-08-06 11:26:39', 0, '0', 0, 0, 0, NULL, '0', NULL, 0, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'AS', 'en', 'Arizona', NULL, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, 0.0000, 0.0000, 0, NULL, '2026-08-07 06:59:59'),
(999999608, NULL, '999999504', 'NEXMAN959369', 'kkk@yopmail.com', NULL, NULL, '$2y$10$/XixFCmVgDNemHE8Cm55J.ubA5YSHt1830oCBdBt6q2snZdhqGo6G', 'kkk', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', NULL, NULL, NULL, NULL, NULL, 1, '2026-08-06 11:28:01', 0, '0', 0, 0, 0, NULL, '0', NULL, 0, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'AS', 'en', 'Arizona', NULL, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, 0.0000, 0.0000, 0, NULL, '2026-08-06 18:07:32'),
(999999611, NULL, '999999608', 'NEXMAN909701', 'loveyou@yopmail.com', NULL, NULL, '$2y$10$9YCxLyPsc.ccVxsXA0er/OSsmX2V7jXoMaAhMBqINOwO6BYqRUDki', 'loveyou', '+919884851012', '6/815 s v koil street, Sanjeevipuram, vanganoor via', '', 'Tamil Nadu', NULL, NULL, NULL, 'approved', '2026-08-06 17:41:52', NULL, NULL, '631304', '', 1, '2026-08-06 16:57:24', 0, '0', 0, 0, 0, '2026-08-06 17:45:20', '0', NULL, 0, 1, NULL, 0, 0, NULL, NULL, NULL, 'India', 'love', 'you', 'AS', 'en', 'Arizona', 'profile_6a74c8309ddf1.png', 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, 0.0000, 0.0000, 0, NULL, '2026-08-06 18:15:41'),
(999999612, NULL, '999999608', 'NEXMAN309289', 'youlove@yopmail.com', NULL, NULL, '$2y$10$5a2C9GViu3bprWBQT1MdK.nH2aDWe6NbTHEI4MbyB5UU07rJGAOPS', 'youlove', '+919884841010', '6/815 s v koil street, Sanjeevipuram, vanganoor via', '', 'Tamil Nadu', NULL, NULL, NULL, 'approved', '2026-08-06 17:09:49', NULL, NULL, '631304', '', 1, '2026-08-06 17:00:22', 0, '0', 0, 0, 0, '2026-08-06 17:08:38', '0', NULL, 0, 1, NULL, 0, 0, NULL, NULL, NULL, 'India', 'You', 'Love', 'AS', 'en', 'Arizona', NULL, 0, 0, 0, 0, 0, NULL, 0, '$2y$10$Wmu.eiPujwtzYMBSBUUP/.T6PO5IABlX8E1/0IsPx20JQYRiqQzo6', '2026-08-06 17:12:36', 0.0000, 0.0000, 0, NULL, '2026-08-07 10:01:01'),
(999999613, NULL, '999999606', 'NEXMAN867358', 'ilefti@yopmail.com', NULL, NULL, '$2y$10$nlrlRFm125r/L8PKpWNQrOPdBugoR4QHqfFrsryI9204qtl.SrOTy', 'ilefti', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', NULL, NULL, NULL, NULL, NULL, 1, '2026-08-07 12:38:36', 0, '0', 0, 0, 0, NULL, '0', NULL, 0, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'AS', 'en', 'Arizona', NULL, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, 0.0000, 0.0000, 0, NULL, '2026-08-07 20:08:28'),
(999999614, NULL, '999999605', 'NEXMAN875416', 'a1@yopmail.com', NULL, NULL, '$2y$10$EOjTomQwXrVG/RQjd/KGsOWTj9zAfYkxmH2Wdc1vwemqfe2JXOeM6', 'a1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', NULL, NULL, NULL, NULL, NULL, 1, '2026-08-07 14:37:58', 0, '0', 0, 0, 0, NULL, '0', NULL, 0, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'AS', 'en', 'Arizona', NULL, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, 0.0000, 0.0000, 0, NULL, '2026-08-07 22:08:04'),
(999999615, NULL, '999999614', 'NEXMAN605485', 'a2@yopmail.com', NULL, NULL, '$2y$10$3oJWjFFvtegqcexPvGHbDOzrxUdBm83mQCEyXmelbvpciyKKAahqe', 'a2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', NULL, NULL, NULL, NULL, NULL, 1, '2026-08-07 14:42:47', 0, '0', 0, 0, 0, NULL, '0', NULL, 0, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'AS', 'en', 'Arizona', NULL, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, 0.0000, 0.0000, 0, NULL, '2026-08-07 20:15:40'),
(999999616, NULL, '999999615', 'NEXMAN825530', 'a3@yopmail.com', NULL, NULL, '$2y$10$L7GjhyMvaIsVJ4tCTDhA8O1yJNgQ1BgDY2VD5NCHDiDrM5YcWI3eW', 'a3', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', NULL, NULL, NULL, NULL, NULL, 1, '2026-08-07 14:46:08', 0, '0', 0, 0, 0, NULL, '0', NULL, 0, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'AS', 'en', 'Arizona', NULL, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, 0.0000, 0.0000, 0, NULL, '2026-08-07 22:06:49');

-- --------------------------------------------------------

--
-- Table structure for table `user_account_actions`
--

CREATE TABLE `user_account_actions` (
  `id` int(11) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `action` varchar(100) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_account_actions`
--

INSERT INTO `user_account_actions` (`id`, `user_id`, `action`, `reason`, `status`, `created_at`) VALUES
(1, 3, 'FREEZE_WITHDRAW', 'testing the freeze flow', 'pending', '2026-07-21 09:49:47'),
(2, 3, 'FREEZE_WITHDRAW', 'Delete', 'pending', '2026-07-21 10:28:51');

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_logs`
--

CREATE TABLE `user_activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` enum('logged in','logged out') DEFAULT NULL,
  `ticket_status` varchar(255) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_type` enum('home','work','apartment') NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_ad_rewards`
--

CREATE TABLE `user_ad_rewards` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ad_id` int(11) NOT NULL,
  `reward_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_ad_sessions`
--

CREATE TABLE `user_ad_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ad_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `status` enum('started','completed','expired') NOT NULL DEFAULT 'started',
  `start_at` datetime NOT NULL,
  `expected_end_at` datetime NOT NULL,
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_bank`
--

CREATE TABLE `user_bank` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `holder_name` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(80) DEFAULT NULL,
  `ifsc` varchar(30) DEFAULT NULL,
  `upi_id` varchar(120) DEFAULT NULL,
  `status` enum('not_added','pending','approved','rejected') NOT NULL DEFAULT 'not_added',
  `note` varchar(255) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `reviewer_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_email_otp`
--

CREATE TABLE `user_email_otp` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(191) NOT NULL,
  `otp` int(11) NOT NULL,
  `ref` varchar(32) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `verified` varchar(150) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_investment`
--

CREATE TABLE `user_investment` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `invest_amount` varchar(200) NOT NULL,
  `invest_network` varchar(50) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `created_date` datetime DEFAULT NULL,
  `run_date` datetime DEFAULT NULL,
  `mature_date` datetime DEFAULT NULL,
  `days_count` int(11) DEFAULT NULL,
  `profit` varchar(250) DEFAULT NULL COMMENT 'profit as ROI %',
  `type` varchar(250) DEFAULT NULL,
  `bot` int(11) DEFAULT 1,
  `hash_id` varchar(250) DEFAULT NULL,
  `ending_date` datetime DEFAULT NULL,
  `starting_date` datetime DEFAULT NULL,
  `reinvest_status` int(11) DEFAULT 1,
  `reinvest_id` int(11) DEFAULT 0,
  `reinvest_date` datetime DEFAULT NULL,
  `req_method` varchar(250) DEFAULT NULL,
  `recived_status` int(11) DEFAULT 0,
  `approve_status` int(11) DEFAULT 0,
  `stake_interest` varchar(250) DEFAULT NULL,
  `csq_price` varchar(250) DEFAULT '0',
  `csq_deposit` varchar(250) DEFAULT '0',
  `package_id` int(11) DEFAULT NULL,
  `token_id` int(11) DEFAULT 0,
  `currency_id` int(11) DEFAULT 0,
  `earn_by` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_kyc`
--

CREATE TABLE `user_kyc` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name_pan` varchar(255) DEFAULT NULL,
  `pan_number` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `aadhaar_last4` varchar(4) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `state` varchar(120) DEFAULT NULL,
  `pincode` varchar(15) DEFAULT NULL,
  `pan_doc` varchar(255) DEFAULT NULL,
  `aadhaar_doc` varchar(255) DEFAULT NULL,
  `status` enum('none','pending','under_review','approved','rejected','resubmission_required') NOT NULL DEFAULT 'none',
  `reviewer_note` varchar(255) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `reviewer_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_method_progress`
--

CREATE TABLE `user_method_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `method_id` int(11) NOT NULL,
  `progress_date` date NOT NULL,
  `completed_count` int(11) NOT NULL DEFAULT 0,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_notifications`
--

CREATE TABLE `user_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(40) NOT NULL DEFAULT 'general',
  `title` varchar(120) NOT NULL,
  `message` varchar(500) NOT NULL,
  `reference_type` varchar(40) DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_notifications`
--

INSERT INTO `user_notifications` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES
(4, 999999101, 'rank_certificate', 'Certificate issued', 'Your UN RANK certificate (BMAN-UNRANK-2026-000001) is ready to view.', 'rank_certificate', 2, 0, '2026-08-05 20:11:15'),
(5, 999999101, 'rank_achievement', 'Rank achieved: UN RANK', 'Congratulations! You have permanently achieved the rank of UN RANK.', 'rank', 1, 0, '2026-08-05 20:11:15');

-- --------------------------------------------------------

--
-- Table structure for table `user_ranks`
--

CREATE TABLE `user_ranks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `current_rank_id` int(10) UNSIGNED DEFAULT NULL,
  `highest_rank_id` int(10) UNSIGNED DEFAULT NULL,
  `group_volume` decimal(30,8) NOT NULL DEFAULT 0.00000000 COMMENT 'volume at last evaluation (informational)',
  `achieved_at` datetime DEFAULT NULL COMMENT 'when highest_rank_id was reached',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Permanent achievement rank per user — never downgraded';

-- --------------------------------------------------------

--
-- Table structure for table `user_rank_history`
--

CREATE TABLE `user_rank_history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `old_rank_id` int(10) UNSIGNED DEFAULT NULL,
  `new_rank_id` int(10) UNSIGNED NOT NULL,
  `achieved_volume` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `left_volume` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `right_volume` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `earning_volume` decimal(30,8) DEFAULT NULL COMMENT 'lifetime binary matching bonus credited to Earning wallet, as of this promotion (NULL = recorded before the rank-volume-source fix)',
  `staking_volume` decimal(30,8) DEFAULT NULL COMMENT 'lifetime binary matching bonus credited to Staking wallet, as of this promotion (NULL = recorded before the rank-volume-source fix)',
  `qualification_plan` varchar(40) DEFAULT NULL COMMENT 'e.g. "Plan-1 / Option-2"',
  `source` varchar(20) NOT NULL DEFAULT 'cron' COMMENT 'cron | admin | manual',
  `achieved_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Every rank promotion — the admin Rank History report reads this';

-- --------------------------------------------------------

--
-- Table structure for table `user_rank_power`
--

CREATE TABLE `user_rank_power` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `cycle_id` int(10) UNSIGNED NOT NULL,
  `power_rank_id` int(10) UNSIGNED DEFAULT NULL,
  `qualified` tinyint(1) NOT NULL DEFAULT 0,
  `achieved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `left_volume` decimal(30,8) NOT NULL DEFAULT 0.00000000 COMMENT 'left-leg BMAN volume THIS cycle',
  `right_volume` decimal(30,8) NOT NULL DEFAULT 0.00000000 COMMENT 'right-leg BMAN volume THIS cycle',
  `total_volume` decimal(30,8) NOT NULL DEFAULT 0.00000000 COMMENT 'left + right, THIS cycle',
  `calculated_at` datetime DEFAULT NULL COMMENT 'last engine pass'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_rank_power`
--

INSERT INTO `user_rank_power` (`id`, `user_id`, `cycle_id`, `power_rank_id`, `qualified`, `achieved_at`, `created_at`, `left_volume`, `right_volume`, `total_volume`, `calculated_at`) VALUES
(1, 1, 3, NULL, 0, NULL, '2026-07-24 18:32:10', 9.00000000, 3.00000000, 12.00000000, '2026-08-07 09:44:55'),
(2, 2, 3, NULL, 0, NULL, '2026-07-24 18:32:10', 7.00000000, 0.00000000, 7.00000000, '2026-08-07 09:44:55'),
(3, 3, 3, NULL, 0, NULL, '2026-07-24 18:32:10', 0.00000000, 2.00000000, 2.00000000, '2026-08-07 09:44:55'),
(4, 4, 3, NULL, 0, NULL, '2026-07-24 18:32:10', 0.00000000, 0.00000000, 0.00000000, '2026-08-07 09:44:55'),
(5, 22, 3, NULL, 0, NULL, '2026-08-07 13:14:34', 0.00000000, 7.00000000, 7.00000000, '2026-08-07 09:44:55'),
(6, 23, 3, NULL, 0, NULL, '2026-08-07 13:14:34', 0.00000000, 0.00000000, 0.00000000, '2026-08-07 09:44:55'),
(7, 999999504, 3, NULL, 0, NULL, '2026-08-07 13:14:34', 5.00000000, 2.00000000, 7.00000000, '2026-08-07 09:44:55'),
(8, 999999505, 3, NULL, 0, NULL, '2026-08-07 13:14:34', 0.00000000, 0.00000000, 0.00000000, '2026-08-07 09:44:55'),
(9, 999999602, 3, NULL, 0, NULL, '2026-08-07 13:14:34', 0.00000000, 2.00000000, 2.00000000, '2026-08-07 09:44:55'),
(10, 999999603, 3, NULL, 0, NULL, '2026-08-07 13:14:34', 0.00000000, 0.00000000, 0.00000000, '2026-08-07 09:44:55'),
(11, 999999604, 3, NULL, 0, NULL, '2026-08-07 13:14:34', 4.00000000, 0.00000000, 4.00000000, '2026-08-07 09:44:55'),
(12, 999999605, 3, NULL, 0, NULL, '2026-08-07 13:14:34', 4.00000000, 0.00000000, 4.00000000, '2026-08-07 09:44:55'),
(13, 999999606, 3, NULL, 0, NULL, '2026-08-07 13:14:34', 0.00000000, 2.00000000, 2.00000000, '2026-08-07 09:44:55'),
(14, 999999607, 3, NULL, 0, NULL, '2026-08-07 13:14:34', 0.00000000, 0.00000000, 0.00000000, '2026-08-07 09:44:55'),
(15, 999999608, 3, NULL, 0, NULL, '2026-08-07 13:14:34', 0.00000000, 3.00000000, 3.00000000, '2026-08-07 09:44:55'),
(16, 999999611, 3, NULL, 0, NULL, '2026-08-07 13:14:34', 0.00000000, 0.00000000, 0.00000000, '2026-08-07 09:44:55'),
(17, 999999612, 3, NULL, 0, NULL, '2026-08-07 13:14:34', 0.00000000, 0.00000000, 0.00000000, '2026-08-07 09:44:55');

-- --------------------------------------------------------

--
-- Table structure for table `user_stakes`
--

CREATE TABLE `user_stakes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `package_id` int(10) UNSIGNED NOT NULL,
  `plan_id` int(10) UNSIGNED NOT NULL,
  `plan_code` enum('fixed','regular','combo') NOT NULL,
  `duration_years` tinyint(4) NOT NULL,
  `stake_amount` decimal(20,4) NOT NULL,
  `roi_percent` decimal(8,3) NOT NULL,
  `roi_basis` enum('total','monthly') NOT NULL,
  `is_special` tinyint(1) NOT NULL DEFAULT 0,
  `uses_year_schedule` tinyint(1) NOT NULL DEFAULT 0,
  `bonus_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `distribution_option_id` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `maturity_date` date NOT NULL,
  `status` enum('processing','active','matured','withdrawn','cancelled') NOT NULL DEFAULT 'active',
  `principal_release_mode` enum('credited_at_purchase_legacy','credited_at_maturity') NOT NULL DEFAULT 'credited_at_maturity',
  `tx_hash` varchar(80) DEFAULT NULL,
  `block_number` bigint(20) UNSIGNED DEFAULT NULL,
  `confirmations` int(11) NOT NULL DEFAULT 0,
  `gas_fee` decimal(30,18) DEFAULT NULL,
  `network` varchar(20) DEFAULT NULL,
  `chain_status` varchar(20) NOT NULL DEFAULT 'pending',
  `execution_mode` enum('onchain','internal') NOT NULL DEFAULT 'onchain',
  `gas_required` tinyint(1) NOT NULL DEFAULT 1,
  `onchain_tx_id` bigint(20) UNSIGNED DEFAULT NULL,
  `swap_order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_stakes`
--

INSERT INTO `user_stakes` (`id`, `user_id`, `package_id`, `plan_id`, `plan_code`, `duration_years`, `stake_amount`, `roi_percent`, `roi_basis`, `is_special`, `uses_year_schedule`, `bonus_amount`, `distribution_option_id`, `start_date`, `maturity_date`, `status`, `principal_release_mode`, `tx_hash`, `block_number`, `confirmations`, `gas_fee`, `network`, `chain_status`, `execution_mode`, `gas_required`, `onchain_tx_id`, `swap_order_id`, `created_at`) VALUES
(2, 3, 1, 0, 'fixed', 2, 1.0000, 150.000, 'total', 0, 0, 0.2500, 1, '2026-07-20', '2028-07-20', 'active', 'credited_at_purchase_legacy', '0x3d335923489fb747bb12aead4e973c7fc03bf03b212fbdee9e8fee5a49552bdc', NULL, 0, NULL, 'mainnet', 'confirmed', 'onchain', 1, NULL, 1, '2026-07-20 18:48:14'),
(4, 3, 1, 0, 'regular', 5, 1.0000, 3.000, 'monthly', 0, 0, 0.2500, 7, '2026-07-23', '2031-07-23', 'active', 'credited_at_purchase_legacy', '0x16288134bf91c80eb4268982f128a9a2b89a3580865dce273565caf28b03baa5', NULL, 0, NULL, 'mainnet', 'confirmed', 'onchain', 1, NULL, 2, '2026-07-23 15:15:04'),
(6, 3, 1, 0, 'regular', 1, 1.0000, 2.000, 'monthly', 1, 0, 0.2500, 1, '2026-07-30', '2027-07-29', 'active', 'credited_at_purchase_legacy', '0x87bf9ecc9161cd6a366f38d5c22a20693ff05c59676135bc02d1beb6fb7539f5', NULL, 0, NULL, 'mainnet', 'confirmed', 'onchain', 1, NULL, 5, '2026-07-30 14:36:15'),
(17, 2, 44, 0, 'regular', 3, 2.0000, 2.000, 'monthly', 0, 0, 0.5000, 5, '2026-08-05', '2029-07-30', 'active', 'credited_at_purchase_legacy', '0x84f0715ea64a2c26e34171a76e554eeacbea7d48f4c3a1271cbd3d5f3fc34614', NULL, 0, NULL, 'mainnet', 'confirmed', 'onchain', 1, NULL, 6, '2026-08-05 12:49:21'),
(19, 23, 44, 0, 'fixed', 3, 2.0000, 160.000, 'total', 0, 0, 0.5000, 1, '2026-08-05', '2029-08-05', 'active', 'credited_at_purchase_legacy', '0x460fa827d11c69b34ab2f2ef10544e5b2ca101f5da28180d806c260c67199361', NULL, 0, NULL, 'mainnet', 'confirmed', 'onchain', 1, NULL, 10, '2026-08-05 13:24:07'),
(48, 999999604, 1, 0, 'combo', 5, 1.0000, 3.000, 'monthly', 0, 0, 0.2500, 1, '2026-08-06', '2031-08-06', 'active', 'credited_at_maturity', '0x5ba7c051a2b1a8af3331a5f00b4dee8796695ece5d8b3be4e4eccb125ee1c56d', NULL, 0, NULL, 'mainnet', 'confirmed', 'onchain', 1, NULL, 27, '2026-08-06 13:37:54'),
(49, 999999607, 44, 0, 'combo', 5, 2.0000, 2.500, 'monthly', 1, 0, 0.5000, 1, '2026-08-06', '2031-08-06', 'active', 'credited_at_maturity', '0x88e71c7b8db3e36b861f08f6c68f6ebb2c8db2afa744486390bd06394829398e', NULL, 0, NULL, 'mainnet', 'confirmed', 'onchain', 1, NULL, 28, '2026-08-06 13:40:30'),
(50, 999999608, 1, 0, 'regular', 3, 1.0000, 2.500, 'monthly', 0, 0, 0.2500, 1, '2026-08-06', '2029-08-06', 'active', 'credited_at_maturity', '0xa0c873980ba96f706e3e5b4e652b533af7b48345adcc479969afed4f5fe66936', NULL, 0, NULL, 'mainnet', 'confirmed', 'onchain', 1, NULL, 29, '2026-08-06 16:38:14'),
(51, 999999612, 44, 0, 'combo', 5, 2.0000, 2.500, 'monthly', 1, 0, 0.5000, 1, '2026-08-06', '2031-08-06', 'active', 'credited_at_maturity', '0x55e0ad3b428365b6804d284df9fac96978264d52a65a69bc91e913ffefbbf90e', NULL, 0, NULL, 'mainnet', 'confirmed', 'onchain', 1, NULL, 30, '2026-08-06 17:23:37'),
(52, 999999612, 1, 0, 'fixed', 5, 1.0000, 400.000, 'total', 0, 0, 0.2500, 1, '2026-08-06', '2031-08-06', 'active', 'credited_at_maturity', '0x31b0d017ca0d861874e72322f687b55cd9d8f163633b2b154e6c057acd9bd052', NULL, 0, NULL, 'mainnet', 'confirmed', 'onchain', 1, NULL, 31, '2026-08-06 17:24:48'),
(53, 999999612, 1, 1, 'fixed', 2, 1.0000, 150.000, 'total', 0, 0, 0.2500, 2, '2026-08-07', '2028-08-07', 'active', 'credited_at_maturity', NULL, NULL, 0, 0.000000000000000000, NULL, 'confirmed', 'internal', 0, NULL, NULL, '2026-08-07 11:05:03'),
(54, 999999612, 1, 1, 'fixed', 2, 1.0000, 150.000, 'total', 0, 0, 0.2500, 2, '2026-08-07', '2028-08-07', 'active', 'credited_at_maturity', NULL, NULL, 0, 0.000000000000000000, NULL, 'confirmed', 'internal', 0, NULL, NULL, '2026-08-07 12:51:02'),
(55, 999999611, 1, 1, 'fixed', 2, 1.0000, 150.000, 'total', 0, 0, 0.2500, 24, '2026-08-07', '2028-08-07', 'active', 'credited_at_maturity', NULL, NULL, 0, 0.000000000000000000, NULL, 'confirmed', 'internal', 0, NULL, NULL, '2026-08-07 13:07:45'),
(56, 999999611, 1, 1, 'fixed', 2, 1.0000, 150.000, 'total', 0, 0, 0.2500, 2, '2026-08-07', '2028-08-07', 'active', 'credited_at_maturity', NULL, NULL, 0, 0.000000000000000000, NULL, 'confirmed', 'internal', 0, NULL, NULL, '2026-08-07 13:08:11'),
(57, 999999611, 1, 1, 'fixed', 2, 1.0000, 150.000, 'total', 0, 0, 0.2500, 3, '2026-08-07', '2028-08-07', 'active', 'credited_at_maturity', NULL, NULL, 0, 0.000000000000000000, NULL, 'confirmed', 'internal', 0, NULL, NULL, '2026-08-07 13:08:38'),
(58, 999999611, 1, 1, 'fixed', 2, 1.0000, 150.000, 'total', 0, 0, 0.2500, 4, '2026-08-07', '2028-08-07', 'active', 'credited_at_maturity', NULL, NULL, 0, 0.000000000000000000, NULL, 'confirmed', 'internal', 0, NULL, NULL, '2026-08-07 13:09:00'),
(59, 999999611, 1, 1, 'fixed', 2, 1.0000, 150.000, 'total', 0, 0, 0.2500, 5, '2026-08-07', '2028-08-07', 'active', 'credited_at_maturity', NULL, NULL, 0, 0.000000000000000000, NULL, 'confirmed', 'internal', 0, NULL, NULL, '2026-08-07 13:09:25'),
(60, 999999611, 1, 1, 'fixed', 2, 1.0000, 150.000, 'total', 0, 0, 0.2500, 6, '2026-08-07', '2028-08-07', 'active', 'credited_at_maturity', NULL, NULL, 0, 0.000000000000000000, NULL, 'confirmed', 'internal', 0, NULL, NULL, '2026-08-07 13:09:46'),
(61, 999999611, 1, 1, 'fixed', 2, 1.0000, 150.000, 'total', 0, 0, 0.2500, 7, '2026-08-07', '2028-08-07', 'active', 'credited_at_maturity', NULL, NULL, 0, 0.000000000000000000, NULL, 'confirmed', 'internal', 0, NULL, NULL, '2026-08-07 13:10:19'),
(62, 999999612, 44, 2, 'regular', 5, 2.0000, 2.500, 'monthly', 1, 0, 0.5000, 2, '2026-08-07', '2031-08-07', 'active', 'credited_at_maturity', NULL, NULL, 0, 0.000000000000000000, NULL, 'confirmed', 'internal', 0, NULL, NULL, '2026-08-07 13:29:34'),
(63, 999999606, 1, 0, 'regular', 3, 1.0000, 2.500, 'monthly', 0, 0, 0.2500, 1, '2026-08-07', '2029-08-07', 'active', 'credited_at_maturity', '0x4f46ebd6d76121a1ac3ee2e7a2e4643d2c0b0a92d9a3b4b722ad51decbe72eaf', NULL, 0, NULL, 'mainnet', 'confirmed', 'onchain', 1, NULL, 32, '2026-08-07 10:08:02'),
(64, 999999608, 1, 1, 'fixed', 2, 1.0000, 150.000, 'total', 0, 0, 0.2500, 24, '2026-08-07', '2028-08-07', 'active', 'credited_at_maturity', NULL, NULL, 0, 0.000000000000000000, NULL, 'confirmed', 'internal', 0, NULL, NULL, '2026-08-07 13:54:07'),
(65, 999999606, 44, 3, 'combo', 5, 2.0000, 350.000, 'total', 1, 0, 0.5000, 2, '2026-08-07', '2031-08-07', 'active', 'credited_at_maturity', NULL, NULL, 0, 0.000000000000000000, NULL, 'confirmed', 'internal', 0, NULL, NULL, '2026-08-07 13:58:08'),
(66, 999999602, 1, 2, 'regular', 2, 1.0000, 2.300, 'monthly', 0, 0, 0.2500, 24, '2026-08-07', '2028-08-07', 'active', 'credited_at_maturity', NULL, NULL, 0, 0.000000000000000000, NULL, 'confirmed', 'internal', 0, NULL, NULL, '2026-08-07 17:23:26'),
(67, 999999602, 1, 2, 'regular', 2, 1.0000, 2.300, 'monthly', 0, 0, 0.2500, 24, '2026-08-07', '2028-08-07', 'active', 'credited_at_maturity', NULL, NULL, 0, 0.000000000000000000, NULL, 'confirmed', 'internal', 0, NULL, NULL, '2026-08-07 17:45:57'),
(68, 999999607, 1, 3, 'combo', 5, 1.0000, 400.000, 'total', 0, 0, 0.2500, 24, '2026-08-07', '2031-08-07', 'active', 'credited_at_maturity', NULL, NULL, 0, 0.000000000000000000, NULL, 'confirmed', 'internal', 0, NULL, NULL, '2026-08-07 17:53:51'),
(69, 999999613, 44, 0, 'regular', 3, 2.0000, 2.000, 'monthly', 1, 0, 0.4000, 1, '2026-08-07', '2029-08-07', 'active', 'credited_at_maturity', '0x50f86adb9f9f9fde6e1928d75ccc915be0fc182049a891665dd372af96cc5afb', NULL, 0, NULL, 'mainnet', 'confirmed', 'onchain', 1, NULL, 33, '2026-08-07 14:43:24'),
(70, 999999602, 44, 1, 'fixed', 2, 2.0000, 120.000, 'total', 1, 0, 0.4000, 24, '2026-08-07', '2028-08-07', 'active', 'credited_at_maturity', NULL, NULL, 0, 0.000000000000000000, NULL, 'confirmed', 'internal', 0, NULL, NULL, '2026-08-07 18:30:05'),
(71, 999999603, 1, 3, 'combo', 2, 1.0000, 150.000, 'total', 0, 0, 0.2500, 24, '2026-08-07', '2028-08-07', 'active', 'credited_at_maturity', NULL, NULL, 0, 0.000000000000000000, NULL, 'confirmed', 'internal', 0, NULL, NULL, '2026-08-07 19:52:53'),
(72, 999999616, 44, 0, 'combo', 5, 2.0000, 2.500, 'monthly', 1, 0, 0.4000, 1, '2026-08-07', '2031-08-07', 'processing', 'credited_at_maturity', NULL, NULL, 0, NULL, NULL, 'pending', 'onchain', 1, NULL, 34, '2026-08-07 20:18:28');

-- --------------------------------------------------------

--
-- Table structure for table `user_stake_roi_year_snapshot`
--

CREATE TABLE `user_stake_roi_year_snapshot` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `stake_id` bigint(20) UNSIGNED NOT NULL,
  `year_number` tinyint(4) NOT NULL,
  `roi_percent` decimal(8,3) NOT NULL,
  `roi_basis` enum('total','monthly') NOT NULL,
  `source_version_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_streaks`
--

CREATE TABLE `user_streaks` (
  `user_id` int(11) NOT NULL,
  `streak_days` int(11) NOT NULL DEFAULT 0,
  `streak_bonus_percent` int(11) NOT NULL DEFAULT 0,
  `last_checkin_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_task_claims`
--

CREATE TABLE `user_task_claims` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `claim_date` date NOT NULL,
  `status` enum('claimed','pending','approved','rejected') NOT NULL DEFAULT 'claimed',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_video_rewards`
--

CREATE TABLE `user_video_rewards` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `video_id` int(11) NOT NULL,
  `reward_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_video_sessions`
--

CREATE TABLE `user_video_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `video_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `status` enum('started','completed','expired') NOT NULL DEFAULT 'started',
  `start_at` datetime NOT NULL,
  `expected_end_at` datetime NOT NULL,
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_wallet`
--

CREATE TABLE `user_wallet` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `wallet_address` varchar(250) NOT NULL,
  `mnemonic` varchar(250) NOT NULL,
  `wallet_qrimage` varchar(255) NOT NULL,
  `private_key` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_wallet`
--

INSERT INTO `user_wallet` (`id`, `user_id`, `wallet_address`, `mnemonic`, `wallet_qrimage`, `private_key`) VALUES
(1, 1, '0xcb3c5E2bcef54Fec78974CAe31828cF9F33dA9c7', '', 'https://darkseagreen-crocodile-999780.hostingersite.com/assets/images/qr_image/0xcb3c5E2bcef54Fec78974CAe31828cF9F33dA9c7qr_code.png', 'htKH+77aLP0yxAImrpiU6Z/BdHM93C4mOzHeyO8AasnyHfLkocFyVT1TAukGzwLPPUQqhFmyMMFp8gIsQRcrAMsffWwi2OZZ5wUtgIzngykt9pR7iuP2FzlmhTKoV1Uu'),
(9, 2, '0x365E2F051Cd601F8828cf33c3D1B7C87a0141c1b', '', 'https://darkseagreen-crocodile-999780.hostingersite.com/assets/images/qr_image/0x365E2F051Cd601F8828cf33c3D1B7C87a0141c1bqr_code.png', 'MVLxWQiUpCeLrWQW5oMp4XUm+vDjlPGnyRryHCQD7QDOeBZIBQ/Dayvme9ZzfmEk9f+G2F+1WtvC9K2JLai7dwEcF6MCP3uJiECVkHjkuoHkwei1pJZteujyC5NYAl96'),
(10, 3, '0x18d0B930970f05ABFC5CC08cad9346aF58D3dd24', '', 'https://darkseagreen-crocodile-999780.hostingersite.com/assets/images/qr_image/0x18d0B930970f05ABFC5CC08cad9346aF58D3dd24qr_code.png', 'ElRx6lum3mUpfAMFqVDekn6/4Rf7r51ueF4PQzw4W8/e5FShQDAf+FV8uhcdX+2bZ5qSzOhzrYwuwd2lR1LF6d99Zc8pVYIK0KYcr9xgEmt4O8eENSumFRSfpJTbGWS4'),
(11, 4, '0x6A3356EEC7660058122E4387AA61F8E1aE220A7f', '', 'https://darkseagreen-crocodile-999780.hostingersite.com/assets/images/qr_image/0x6A3356EEC7660058122E4387AA61F8E1aE220A7fqr_code.png', 'KgzYihmVsOASN3ASLSuysm7X4FE1TPPHTvJRfLk4v4LAfQs4XsFRYankgwmFTlKHTV6wsv6+8yLY/h2McsQvpvlApCYFdZU1jFPPSCf3RT46f+3Qy5FYcfMnaOpa1vxc'),
(29, 22, '0x513fD294ADdE5dD699cF0A556Fc15fF2521892aD', '', 'http://192.168.29.18:9000/assets/images/qr_image/0x513fD294ADdE5dD699cF0A556Fc15fF2521892aDqr_code.png', '2m7WukI6jyaz9mpII1ahXgkzHokMxd/8OFLohCS7eKoGIQYL3G2wzTPVoOX04PFd9imzjQ3wVdOoo31cbCnNH9uG4vErmMjSUmbFuvQNz/0sKvOtfhJa7bH9xrSwyjLm'),
(30, 23, '0xb3A4C6e46049bE49CdB9734DbfB2897ade83Fe83', '', 'http://192.168.29.18:9000/assets/images/qr_image/0xb3A4C6e46049bE49CdB9734DbfB2897ade83Fe83qr_code.png', 'SrgBfpmdXwGKzhsFIOkLlMSW9Wzy9Y69Saofj8V5ZGJN7NxUhcYnv+60XBMGplgffY2IHOGdSqSlWWjXEUWvgfF6YsK8O9ipWtc6QD7/ZTAuJ9taF8nRQsVOHI9dePjU'),
(33, 999999504, '0x7571092B8e7a2c76D335c70b7BD4805C92834055', '', 'http://192.168.29.18:9001/assets/images/qr_image/0x7571092B8e7a2c76D335c70b7BD4805C92834055qr_code.png', '8CW5bC2nnQOXhiiYJBJ5aivUbW8VzL58sp9YG6QziW3ACEuxfNFbXNZUE4ZVr+QCFaMnFIBHcl1M02K0infPsGptUut4TEZeCWpryqPAuTgLwEgBuFhkLv1bh9E3WbZY'),
(35, 999999602, '0xF03f473F1eE2B5491a7564c18A99e327afD228ed', '', 'http://192.168.29.18:9001/assets/images/qr_image/0xF03f473F1eE2B5491a7564c18A99e327afD228edqr_code.png', 'DAWXTf7O0wbP178vgsS3c4kgVLiMoDGi6TiUgTAFK2czhDGKB/QHV92rJGZkrxknyh3R73aIoepyhTepNtOPBCC3WEDX0Oa7kZNZueDJCsxCZvzGw3KkUXi6mnsBhWGD'),
(36, 999999603, '0x3cc69420A1359Fb650ecc79367cF2732D9A77bD5', '', 'http://192.168.29.18:9001/assets/images/qr_image/0x3cc69420A1359Fb650ecc79367cF2732D9A77bD5qr_code.png', 'bbHRV8oucWGmC18BXgMXlp8Bfv24SaDVsS1i1gQcC5aKbEjo4P4SyXVkGkq/DxXJfxAW6olPlwkFnwh+8APdjyOw2S72aPV1Gbv+Gd1ZroXjS4V1GmAScMzmzjd4segb'),
(37, 999999604, '0xFD96d05e54F137c196Aaf81cCe565D3061ECAA37', '', 'http://192.168.29.18:9001/assets/images/qr_image/0xFD96d05e54F137c196Aaf81cCe565D3061ECAA37qr_code.png', 'u5HnhCqYxcLm45Shiak4OPymPHivoCbWEtl8CAvmPI82VCPy1oO3Fb1SB1GYzw36+yNxORH+kwfDhHEG0i5AH0yOriivl062UG3hBSXMq4kv8X9FpE/R2lXOBfxnSd1Y'),
(38, 999999607, '0xf18948D95e2B8DEe52a5816c48B02Eb245c4Fa1B', '', 'http://192.168.29.18:9001/assets/images/qr_image/0xf18948D95e2B8DEe52a5816c48B02Eb245c4Fa1Bqr_code.png', '8c/AT4+yvVaG3jz+f6CPxtAkoRam9miT1KRo/XNyD6SQEIrQHlAoBAG33YBdriPXIAVptkIbvT2ilRYQ5vl8RqH+Ehx0WqIOwByqnnz+kx86kzx2fVK1KDYJakA0JmSb'),
(39, 999999608, '0x77779986DF95EBEaE48F4c6a94Be2886eA7a943C', '', 'https://nexman.in/assets/images/qr_image/0x77779986DF95EBEaE48F4c6a94Be2886eA7a943Cqr_code.png', 'ZwG6FeJSZZrR3u5uLUKCqoG3MauPZ0iKO08aRP3FoTFOfJgpmV/3ZNKF8OJdUMnc+ZWR86Pcw0pZK31IHP9mVXr7V51VI3xvkm1j5txtGBMU2w8HWWQzbNCO3m9nsXMB'),
(40, 999999612, '0x084B8f91a35c79c49157b095d61F4Ab42910A093', '', 'https://nexman.in/assets/images/qr_image/0x084B8f91a35c79c49157b095d61F4Ab42910A093qr_code.png', 'Pl701JBvrLeH2PxOGpvF/NBzV+XyMtGIH2lYvfJ9OetkpwagwzOPRgUrVOx75bEozrSJfr1hLWejSctMyilnH9vwPWj0J7KR5MIVB8s/8JqhfVf4SjDnhHNLmr/+3PvV'),
(41, 999999611, '0xd85Ea024Be14032b7c25a04b017DB8Bf28f5da57', '', 'https://nexman.in/assets/images/qr_image/0xd85Ea024Be14032b7c25a04b017DB8Bf28f5da57qr_code.png', 'vynxI8U5WXkWub9M5H1i3wHRSgxISTYdf8I5jn2aFQJUr5eUnG14Y8kXVk1WiUHW0G4mgGgHG9SY1oA2dd09/OH7t11yGLSI5Dr4yrxES0RFNQXGYPFOoCCGkiL2z9o9'),
(42, 999999606, '0x2E228070726Ec09A6e0a9d89287F900B5DD2D3Db', '', 'http://192.168.29.18:9001/assets/images/qr_image/0x2E228070726Ec09A6e0a9d89287F900B5DD2D3Dbqr_code.png', 'HWqBMqXZZiW1OBNXTpzA0z8tw92+wbuqu9IGrCbzO/8KV6eiKnIHZS889tbQVcEnXURIC3hSNmDT/IdER9WbtR24909oeUQDJqXlNvtc3g3B36ZpQ0Kvj1YqxwFar7BJ'),
(43, 999999613, '0x85519d7A4E94a070eceeEe5e1763206C4D6665Ff', '', 'http://192.168.29.18:9001/assets/images/qr_image/0x85519d7A4E94a070eceeEe5e1763206C4D6665Ffqr_code.png', 'R50Uf+8GA7nvkS0snt8uVxvFV2giZH9S11rjp1qXTlZ9Iap1IkuV/ezKSHR5IrA2hc45DXOGjrDbnI0g0X+nmYJwCPm5A2IYJaDW3lt3e1ZiZyBoGwFtl3DZxEFdEhLV'),
(44, 999999615, '0xdfce6AD2c05B80F1f616fF1f9Bb52443C40036D0', '', 'http://192.168.29.18:9001/assets/images/qr_image/0xdfce6AD2c05B80F1f616fF1f9Bb52443C40036D0qr_code.png', 'VMhsx49+1F8F5F9fZifvNyDQPz3bTnTS21vO0d51SSTzq0ooi+OFLtzmHQvZ3yG/i8XREf/z8bG7oIjy2ekQU0HfPvbk8p3Ay4JWmi+XrmH77W3c/A6DpjHApBpC2WEU'),
(45, 999999616, '0x4E107AAbeE4f7D2a12abf12cD1fc9506523E49Fb', '', 'http://192.168.29.18:9001/assets/images/qr_image/0x4E107AAbeE4f7D2a12abf12cD1fc9506523E49Fbqr_code.png', 'hwOp0Ve/sYrNTPicMoQnwQh8nozyUpNF1hKbTlH952atLP1yDFeGtJl5ZR5JhHS2gFiBIlNqEGQZNWJ0HLwDtLKGRseU/b9zXUMQqLn7WIVxZthz7RZol0tRcfmNSL2B');

-- --------------------------------------------------------

--
-- Table structure for table `user_wallets`
--

CREATE TABLE `user_wallets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `usd_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `usd_pending` decimal(12,2) NOT NULL DEFAULT 0.00,
  `updated_at` datetime DEFAULT NULL,
  `exchange_balance` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `earning_balance` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `staking_balance` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `bonus_balance` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `total_deposit_usdt` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `total_withdraw_usdt` decimal(30,8) NOT NULL DEFAULT 0.00000000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_wallets`
--

INSERT INTO `user_wallets` (`id`, `user_id`, `usd_balance`, `usd_pending`, `updated_at`, `exchange_balance`, `earning_balance`, `staking_balance`, `bonus_balance`, `total_deposit_usdt`, `total_withdraw_usdt`) VALUES
(1, 1, 0.00, 0.00, '2026-07-23 13:59:53', 0.25000000, 0.00000000, 0.00000000, 1.00000000, 0.50000000, 0.00000000),
(17, 3, 0.01, 0.00, '2026-08-07 13:14:56', 5.23000000, 0.10000000, 0.10000000, 0.85000000, 0.31000000, 0.00000000),
(18, 4, 0.00, 0.00, '2026-08-05 14:26:01', 1.00000000, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 0.00000000),
(19, 2, 0.00, 0.00, '2026-08-05 12:29:14', 7.80000000, 0.20000000, 0.00000000, 0.50000000, 0.20000000, 0.00000000),
(22, 23, 0.29, 0.00, '2026-08-06 17:12:05', 6.00000000, 0.00000000, 4.00000000, 0.50000000, 0.49353990, 0.00000000),
(36, 999999504, 0.00, 0.00, '2026-08-06 13:23:10', 0.00000000, 0.00000000, 0.00000000, 0.00000000, 0.00000000, 0.00000000),
(38, 999999602, 0.00, 0.00, '2026-08-08 11:57:31', 1.28833332, 0.00000000, 0.00000000, 0.90000000, 0.00000000, 0.00000000),
(39, 999999603, 0.00, 0.00, '2026-08-08 11:58:03', 0.00383333, 0.00000000, 0.00000000, 0.25000000, 0.00000000, 0.00000000),
(40, 999999604, 0.00, 0.00, '2026-08-07 13:14:56', 0.01500000, 0.00000000, 0.00000000, 0.25000000, 0.10000000, 0.00000000),
(41, 999999607, 0.00, 0.00, '2026-08-08 11:57:49', 0.01000000, 0.00000000, 0.00000000, 0.75000000, 0.20000000, 0.00000000),
(42, 999999608, 0.00, 0.00, '2026-08-07 10:24:07', 1.00000000, 0.16000000, 0.04000000, 0.50000000, 0.10000000, 0.00000000),
(43, 999999612, 0.00, 0.00, '2026-08-07 09:59:34', 6.15000000, 0.00000000, 0.00000000, 2.35000000, 0.30000000, 0.00000000),
(44, 22, 0.00, 0.00, '2026-08-06 17:12:04', 0.00000000, 2.00000000, 0.00000000, 0.00000000, 0.00000000, 0.00000000),
(45, 999999611, 0.00, 0.00, '2026-08-07 09:40:19', 4.00000000, 1.60000000, 1.70000000, 3.45000000, 0.00000000, 0.00000000),
(46, 999999606, 0.10, 0.00, '2026-08-07 10:28:08', 1.55000000, 0.00000000, 0.00000000, 0.55000000, 0.20000000, 0.00000000),
(47, 999999613, 0.00, 0.00, '2026-08-07 14:44:43', 0.00000000, 0.00000000, 0.00000000, 0.40000000, 0.20000000, 0.00000000),
(48, 999999616, 0.00, 0.00, '2026-08-08 11:58:11', 0.00833333, 0.00000000, 0.00000000, 0.00000000, 0.20000000, 0.00000000);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_user_ranks`
-- (See below for the actual view)
--
CREATE TABLE `vw_user_ranks` (
`user_id` int(11)
,`username` varchar(255)
,`email` varchar(255)
,`user_status` int(11)
,`current_rank_id` int(10) unsigned
,`current_rank` varchar(40)
,`current_tier` tinyint(4)
,`highest_rank_id` int(10) unsigned
,`highest_rank` varchar(40)
,`highest_tier` tinyint(4)
,`badge_image` varchar(255)
,`badge_color` varchar(20)
,`group_volume` decimal(30,8)
,`achieved_at` datetime
,`updated_at` datetime
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_user_rank_power`
-- (See below for the actual view)
--
CREATE TABLE `vw_user_rank_power` (
`id` bigint(20) unsigned
,`user_id` int(11)
,`cycle_id` int(10) unsigned
,`cycle_no` int(11)
,`start_date` date
,`end_date` date
,`cycle_status` enum('open','closed')
,`power_rank_id` int(10) unsigned
,`power_tier` tinyint(4)
,`power_rank` varchar(40)
,`left_volume` decimal(30,8)
,`right_volume` decimal(30,8)
,`total_volume` decimal(30,8)
,`qualified` tinyint(1)
,`achieved_at` datetime
,`calculated_at` datetime
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_bman_user_available`
-- (See below for the actual view)
--
CREATE TABLE `v_bman_user_available` (
`user_id` int(11)
,`available` decimal(65,8)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_bman_wallet_balances`
-- (See below for the actual view)
--
CREATE TABLE `v_bman_wallet_balances` (
`user_id` int(11)
,`wallet` varchar(8)
,`total` decimal(30,8)
,`locked` decimal(52,8)
,`matured` decimal(53,8)
,`holds` decimal(40,8)
,`available` decimal(54,8)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_user_staking_activity`
-- (See below for the actual view)
--
CREATE TABLE `v_user_staking_activity` (
`id` bigint(20) unsigned
,`user_id` int(11)
,`username` varchar(255)
,`email` varchar(255)
,`created_at` datetime
,`status` varchar(24)
,`cron_status` varchar(50)
,`usdt_amount` decimal(30,8)
,`bman_amount` decimal(30,8)
,`bonus_bman` decimal(30,8)
,`exchange_rate` decimal(24,8)
,`error` varchar(255)
);

-- --------------------------------------------------------

--
-- Table structure for table `wallet_balance_sync`
--

CREATE TABLE `wallet_balance_sync` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `address` varchar(120) NOT NULL,
  `token` varchar(20) NOT NULL DEFAULT 'BNB',
  `contract` varchar(120) DEFAULT NULL,
  `last_balance` decimal(38,18) NOT NULL DEFAULT 0.000000000000000000,
  `last_balance_raw` decimal(65,0) NOT NULL DEFAULT 0,
  `last_block` bigint(20) UNSIGNED DEFAULT NULL,
  `last_tx_hash` varchar(120) DEFAULT NULL,
  `last_synced_at` datetime DEFAULT NULL,
  `sync_count` int(11) NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallet_balance_sync`
--

INSERT INTO `wallet_balance_sync` (`id`, `user_id`, `address`, `token`, `contract`, `last_balance`, `last_balance_raw`, `last_block`, `last_tx_hash`, `last_synced_at`, `sync_count`, `updated_at`) VALUES
(1, 1, '0xcb3c5E2bcef54Fec78974CAe31828cF9F33dA9c7', 'BNB', NULL, 0.002359725000000000, 2359725000000000, NULL, NULL, '2026-08-05 14:11:54', 1, '2026-08-05 17:41:54'),
(2, 1, '0xcb3c5E2bcef54Fec78974CAe31828cF9F33dA9c7', 'USDT', '0x55d398326f99059fF775485246999027B3197955', 0.000000000000000000, 0, 111323832, '0x22b80932c89e866614142e8bf5b7fc893189cc7bb45561c0b0d2afb7650d9df6', '2026-08-05 14:11:56', 1, '2026-08-05 17:41:56'),
(3, 2, '0x365E2F051Cd601F8828cf33c3D1B7C87a0141c1b', 'BNB', NULL, 0.003001545000000000, 3001545000000000, NULL, NULL, '2026-08-05 14:11:57', 1, '2026-08-05 17:41:57'),
(4, 2, '0x365E2F051Cd601F8828cf33c3D1B7C87a0141c1b', 'USDT', '0x55d398326f99059fF775485246999027B3197955', 0.000000000000000000, 0, 113010914, '0x6a29a7b742f9e76eba33833cd199c47abce9ce0148b8948b21d519b6226fbf55', '2026-08-05 14:12:02', 1, '2026-08-05 17:42:02'),
(5, 3, '0x18d0B930970f05ABFC5CC08cad9346aF58D3dd24', 'BNB', NULL, 0.002656635000000000, 2656635000000000, NULL, NULL, '2026-08-05 14:12:03', 1, '2026-08-05 17:42:03'),
(6, 3, '0x18d0B930970f05ABFC5CC08cad9346aF58D3dd24', 'USDT', '0x55d398326f99059fF775485246999027B3197955', 0.010000000000000000, 10000000000000000, 112841358, '0x8ad440d70c911ed3a199257a088b4ecefc1207a2a3183eb86bb7cf1022aa3e19', '2026-08-05 14:12:05', 1, '2026-08-05 17:42:05'),
(7, 4, '0x6A3356EEC7660058122E4387AA61F8E1aE220A7f', 'BNB', NULL, 0.000000000000000000, 0, NULL, NULL, '2026-08-05 14:12:06', 1, '2026-08-05 17:42:06'),
(8, 4, '0x6A3356EEC7660058122E4387AA61F8E1aE220A7f', 'USDT', '0x55d398326f99059fF775485246999027B3197955', 0.000000000000000000, 0, NULL, NULL, '2026-08-05 14:12:08', 1, '2026-08-05 17:42:08'),
(9, 22, '0x513fD294ADdE5dD699cF0A556Fc15fF2521892aD', 'BNB', NULL, 0.000000000000000000, 0, NULL, NULL, '2026-08-05 14:12:09', 1, '2026-08-05 17:42:09'),
(10, 22, '0x513fD294ADdE5dD699cF0A556Fc15fF2521892aD', 'USDT', '0x55d398326f99059fF775485246999027B3197955', 0.000000000000000000, 0, NULL, NULL, '2026-08-05 14:12:11', 1, '2026-08-05 17:42:11'),
(11, 23, '0xb3A4C6e46049bE49CdB9734DbfB2897ade83Fe83', 'BNB', NULL, 0.003292545000000000, 3292545000000000, NULL, NULL, '2026-08-05 14:12:12', 1, '2026-08-05 17:42:12'),
(12, 23, '0xb3A4C6e46049bE49CdB9734DbfB2897ade83Fe83', 'USDT', '0x55d398326f99059fF775485246999027B3197955', 0.293539907624882841, 293539907624882841, 114150547, '0xcea86e9eb3646fb4bb4ccf659d4539504a7376b7a8204161b434b3f40e289118', '2026-08-05 14:12:14', 1, '2026-08-05 17:42:14'),
(13, NULL, '0x3088B858dc4cD85A001337f8E15a40b24666d321', 'BNB', NULL, 0.128981700000000000, 128981700000000000, NULL, NULL, '2026-08-06 10:04:09', 2, '2026-08-06 13:34:09'),
(14, NULL, '0x3088B858dc4cD85A001337f8E15a40b24666d321', 'USDT', '0x55d398326f99059fF775485246999027B3197955', 2.200000000000000000, 2200000000000000000, 114312222, '0x95379a6f728fff9c583ff7493285e933c4029c6435b6bf575aeb6e552f5a5246', '2026-08-06 10:04:11', 2, '2026-08-06 13:34:11'),
(15, 999999504, '0x7571092B8e7a2c76D335c70b7BD4805C92834055', 'BNB', NULL, 0.003001545000000000, 3001545000000000, NULL, NULL, '2026-08-06 10:04:07', 1, '2026-08-06 13:34:07'),
(16, 999999504, '0x7571092B8e7a2c76D335c70b7BD4805C92834055', 'USDT', '0x55d398326f99059fF775485246999027B3197955', 0.000000000000000000, 0, 114312222, '0x95379a6f728fff9c583ff7493285e933c4029c6435b6bf575aeb6e552f5a5246', '2026-08-06 10:04:08', 1, '2026-08-06 13:34:08');

-- --------------------------------------------------------

--
-- Table structure for table `wallet_deposits`
--

CREATE TABLE `wallet_deposits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `wallet_address` varchar(100) NOT NULL,
  `from_address` varchar(120) DEFAULT NULL,
  `tx_hash` varchar(120) NOT NULL,
  `log_index` int(11) NOT NULL DEFAULT 0,
  `block_number` bigint(20) UNSIGNED DEFAULT NULL,
  `token` varchar(20) NOT NULL DEFAULT 'USDT',
  `amount_usdt` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `amount_bman` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `confirmations` int(11) NOT NULL DEFAULT 0,
  `network` varchar(20) NOT NULL DEFAULT 'mainnet',
  `status` enum('pending','confirming','confirmed','credited','failed','expired') NOT NULL DEFAULT 'pending',
  `distribution_option_id` int(11) DEFAULT NULL,
  `credited_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallet_deposits`
--

INSERT INTO `wallet_deposits` (`id`, `user_id`, `wallet_address`, `from_address`, `tx_hash`, `log_index`, `block_number`, `token`, `amount_usdt`, `amount_bman`, `confirmations`, `network`, `status`, `distribution_option_id`, `credited_at`, `created_at`, `updated_at`) VALUES
(1, 1, '0xcb3c5E2bcef54Fec78974CAe31828cF9F33dA9c7', NULL, '0x29a05409254a504406c13e08426d455b6d8a8b058bcd9d92c7507ef8df2fca05', 67, 111115777, 'USDT', 0.20000000, 0.00000000, 103, 'mainnet', 'credited', NULL, '2026-07-20 17:33:55', '2026-07-20 21:03:55', '2026-07-20 21:03:55'),
(2, 1, '0xcb3c5E2bcef54Fec78974CAe31828cF9F33dA9c7', NULL, '0x680529f21b8039c0bfccf9d3b7144c247d399bbfb690a8ee0ff95fa97b80096b', 56, 110113718, 'USDT', 0.20000000, 0.00000000, 1002162, 'mainnet', 'credited', NULL, '2026-07-20 17:33:55', '2026-07-20 21:03:55', '2026-07-20 21:03:55'),
(3, 1, '0xcb3c5E2bcef54Fec78974CAe31828cF9F33dA9c7', NULL, '0x58500f3715c58ea34e4385363382b696338798669615022f7de9dfabe63c22d4', 105, 108799765, 'USDT', 0.10000000, 0.00000000, 2316115, 'mainnet', 'credited', NULL, '2026-07-20 17:33:55', '2026-07-20 21:03:55', '2026-07-20 21:03:55'),
(4, 3, '0x18d0B930970f05ABFC5CC08cad9346aF58D3dd24', NULL, '0xa0b59d15308666c6a5c78c1be5e373f6ba9b6a057d170bc1d537e9e5faf96dc1', 47, 111124029, 'USDT', 0.20000000, 0.00000000, 68, 'mainnet', 'credited', NULL, '2026-07-20 18:35:34', '2026-07-20 22:05:33', '2026-07-20 22:05:34'),
(5, 3, '0x18d0B930970f05ABFC5CC08cad9346aF58D3dd24', NULL, '0x3d335923489fb747bb12aead4e973c7fc03bf03b212fbdee9e8fee5a49552bdc', 12, 111125703, 'BMAN', 0.00000000, 1.00000000, 124355, 'mainnet', 'credited', NULL, '2026-07-21 10:20:35', '2026-07-21 13:50:34', '2026-07-21 13:50:35'),
(6, 3, '0x18d0B930970f05ABFC5CC08cad9346aF58D3dd24', NULL, '0xb3e94ea802696e384d8450fa8de9c5cb680fece5e0d8fbe72d5009a648707bff', 24, 111125700, 'BMAN', 0.00000000, 0.25000000, 124358, 'mainnet', 'credited', NULL, '2026-07-21 10:20:35', '2026-07-21 13:50:34', '2026-07-21 13:50:35'),
(7, 3, '0x18d0B930970f05ABFC5CC08cad9346aF58D3dd24', NULL, '0xd315011a2c3b5496e2fc9e7976ae0d7f56b7f168520736f22e1180111e35b943', 54, 111124283, 'BMAN', 0.00000000, 2.00000000, 125775, 'mainnet', 'credited', NULL, '2026-07-21 10:20:35', '2026-07-21 13:50:34', '2026-07-21 13:50:35'),
(8, 1, '0xcb3c5E2bcef54Fec78974CAe31828cF9F33dA9c7', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x39ae895242516de2dc9576a079244f3aaf760b411bbef7ac641f561f293d8a1c', 8, 110668176, 'BMAN', 0.00000000, 1.00000000, 585102, 'mainnet', 'credited', NULL, '2026-07-21 10:44:44', '2026-07-21 14:14:43', '2026-07-21 14:42:21'),
(9, 1, '0xcb3c5E2bcef54Fec78974CAe31828cF9F33dA9c7', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x380739509750a561e3067d5515e3be53cf071289ffd0843bf92e5ae80a918ad5', 18, 110668173, 'BMAN', 0.00000000, 0.25000000, 585105, 'mainnet', 'credited', NULL, '2026-07-21 10:44:44', '2026-07-21 14:14:43', '2026-07-21 14:42:21'),
(10, 1, '0xcb3c5E2bcef54Fec78974CAe31828cF9F33dA9c7', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x7d57370562c2d0eb7e653ad2eff05d0b2289adbb12160b2046c4c42fd97cbbb0', 24, 110115957, 'BMAN', 0.00000000, 1.00000000, 1137321, 'mainnet', 'credited', NULL, '2026-07-21 10:44:44', '2026-07-21 14:14:43', '2026-07-21 14:42:21'),
(11, 1, '0xcb3c5E2bcef54Fec78974CAe31828cF9F33dA9c7', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x887bdd07dfbaed9d8e8e78e700c63759500a3e540cc4e10b1321a6d47480affa', 21, 110115954, 'BMAN', 0.00000000, 0.25000000, 1137324, 'mainnet', 'credited', NULL, '2026-07-21 10:44:44', '2026-07-21 14:14:43', '2026-07-21 14:42:21'),
(12, 3, '0x18d0B930970f05ABFC5CC08cad9346aF58D3dd24', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xb845667b4cc77a160974e81aee6064326ed99d2eeb19b321b2d85972dc2f9f3d', 28, 111297007, 'BMAN', 0.00000000, 0.50000000, 219, 'mainnet', 'credited', NULL, '2026-07-21 16:15:28', '2026-07-21 19:45:27', '2026-07-21 19:45:28'),
(13, 1, '0xcb3c5E2bcef54Fec78974CAe31828cF9F33dA9c7', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x3d9ca04e515d17571c68352db6474a18ceb1605d5a71701471246175ab710feb', 1, 111325834, 'BMAN', 0.00000000, 1.00000000, 281907, 'mainnet', 'credited', NULL, '2026-07-23 07:06:03', '2026-07-23 10:36:02', '2026-07-23 10:36:03'),
(14, 1, '0xcb3c5E2bcef54Fec78974CAe31828cF9F33dA9c7', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x058f6dd1923c52e56da2de73fbf67a89028b59f86c7bb4053e080791df2748e2', 23, 111325832, 'BMAN', 0.00000000, 0.25000000, 281909, 'mainnet', 'credited', NULL, '2026-07-23 07:06:03', '2026-07-23 10:36:02', '2026-07-23 10:36:03'),
(15, 3, '0x18d0B930970f05ABFC5CC08cad9346aF58D3dd24', '0xb4f03059793be82a8f019774d1fb0fec5472ea1b', '0xad1f757e45aa052eff5628f31551a0bc049e14151b64239535f0237b4d56e3eb', 84, 112826942, 'USDT', 0.01000000, 0.00000000, 98, 'mainnet', 'credited', NULL, '2026-07-29 15:36:35', '2026-07-29 19:06:35', '2026-07-29 19:06:35'),
(16, 3, '0x18d0B930970f05ABFC5CC08cad9346aF58D3dd24', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x16288134bf91c80eb4268982f128a9a2b89a3580865dce273565caf28b03baa5', 23, 111672792, 'BMAN', 0.00000000, 1.00000000, 1154254, 'mainnet', 'credited', NULL, '2026-07-29 15:36:38', '2026-07-29 19:06:38', '2026-07-29 19:06:38'),
(17, 3, '0x18d0B930970f05ABFC5CC08cad9346aF58D3dd24', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xa288fb3468bacb651689e208b1f81f2454ffb1aa5868b72955ca24726a5cc819', 22, 111672789, 'BMAN', 0.00000000, 0.25000000, 1154257, 'mainnet', 'credited', NULL, '2026-07-29 15:36:38', '2026-07-29 19:06:38', '2026-07-29 19:06:38'),
(18, 3, '0x18d0B930970f05ABFC5CC08cad9346aF58D3dd24', '0xb4f03059793be82a8f019774d1fb0fec5472ea1b', '0x05ef03ebcd04e686239682443a25adaa2a76c2911009e4ba4ae628671d16868c', 74, 112840070, 'USDT', 0.10000000, 0.00000000, 512, 'mainnet', 'credited', NULL, '2026-07-29 17:18:11', '2026-07-29 20:48:10', '2026-07-29 20:48:11'),
(19, 2, '0x365E2F051Cd601F8828cf33c3D1B7C87a0141c1b', '0xb4f03059793be82a8f019774d1fb0fec5472ea1b', '0xe9f7f60185687ca5333d3b61374441f607638478c4689da7551a4a46430b997b', 85, 113002130, 'USDT', 0.20000000, 0.00000000, 7974, 'mainnet', 'credited', NULL, '2026-07-30 14:30:14', '2026-07-30 18:00:13', '2026-07-30 18:00:14'),
(20, 2, '0x365E2F051Cd601F8828cf33c3D1B7C87a0141c1b', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x3685efee9b922057db889d2a57677be80e4a0949ad5870bbda3680e82daf1fb4', 35, 111669615, 'BMAN', 0.00000000, 2.00000000, 1340496, 'mainnet', 'credited', NULL, '2026-07-30 14:30:17', '2026-07-30 18:00:16', '2026-07-30 18:00:17'),
(21, 2, '0x365E2F051Cd601F8828cf33c3D1B7C87a0141c1b', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xa95dd6d48c6c1f0314ccb1c5f8286acb4f4b4b9a50bc9023f604a5200bc6c9d8', 48, 111660108, 'BMAN', 0.00000000, 2.00000000, 1350003, 'mainnet', 'credited', NULL, '2026-07-30 14:30:17', '2026-07-30 18:00:16', '2026-07-30 18:00:17'),
(22, 23, '0xb3A4C6e46049bE49CdB9734DbfB2897ade83Fe83', '0x517b3f2aa149b2aee7fdc313eee0893870726808', '0x82c6095c9913b6c413150e3397a25fbe5ac0f431e06702cb775630b16e48fcdd', 149, 114142104, 'USDT', 0.19353990, 0.00000000, 380, 'mainnet', 'credited', NULL, '2026-08-05 12:05:02', '2026-08-05 15:35:01', '2026-08-05 15:35:02'),
(23, 23, '0xb3A4C6e46049bE49CdB9734DbfB2897ade83Fe83', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x822111baa209be024a5a1140954d4e4eccb74653501fd81e61efba7398c7b70e', 24, 113034744, 'BMAN', 0.00000000, 4.00000000, 1107746, 'mainnet', 'credited', NULL, '2026-08-05 12:05:04', '2026-08-05 15:35:04', '2026-08-05 15:35:04'),
(24, 23, '0xb3A4C6e46049bE49CdB9734DbfB2897ade83Fe83', '0x71e038da10d1aab4925c683a140d72c115f1efe3', '0xdebc914bae7a119986abec153fe25e4ef0726479b630e76c4e5b291bc8bc2761', 9, 114147705, 'USDT', 0.30000000, 0.00000000, 26, 'mainnet', 'credited', NULL, '2026-08-05 12:44:24', '2026-08-05 16:14:22', '2026-08-05 16:14:24'),
(25, 999999504, '0x7571092B8e7a2c76D335c70b7BD4805C92834055', '0x71e038da10d1aab4925c683a140d72c115f1efe3', '0x9f3bd944cb97cfeed2b37f767d395652677dc3dc74ce2a2a282c6d5f267a20b9', 36, 114305294, 'USDT', 0.10000000, 0.00000000, 490, 'mainnet', 'credited', NULL, '2026-08-06 08:32:05', '2026-08-06 12:02:04', '2026-08-06 12:02:05'),
(27, 999999602, '0xF03f473F1eE2B5491a7564c18A99e327afD228ed', '0x71e038da10d1aab4925c683a140d72c115f1efe3', '0xd2c32ea63ab46d4c334983dbe4eba2c7e84d7b1306719390654c4545f2f83161', 12, 114337817, 'USDT', 0.10000000, 0.00000000, 21, 'mainnet', 'credited', NULL, '2026-08-06 12:32:35', '2026-08-06 16:02:34', '2026-08-06 16:02:35'),
(28, 999999603, '0x3cc69420A1359Fb650ecc79367cF2732D9A77bD5', '0x71e038da10d1aab4925c683a140d72c115f1efe3', '0x6e06cbe4bc0365a8b4fbec1794b644bf18718911667c241e75ca12616bb3e591', 99, 114339271, 'USDT', 0.20000000, 0.00000000, 60, 'mainnet', 'credited', NULL, '2026-08-06 12:43:47', '2026-08-06 16:13:46', '2026-08-06 16:13:47'),
(29, 999999602, '0xF03f473F1eE2B5491a7564c18A99e327afD228ed', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x1e3476353fc62bae0574f9c2d0511acb7a8097e373a9399ac0d3df8ef436631e', 61, 114340481, 'BMAN', 0.00000000, 1.25000000, 5215, 'mainnet', 'credited', NULL, '2026-08-06 13:31:32', '2026-08-06 17:01:31', '2026-08-06 17:01:32'),
(30, 999999604, '0xFD96d05e54F137c196Aaf81cCe565D3061ECAA37', '0x71e038da10d1aab4925c683a140d72c115f1efe3', '0xd1980118c60eeb05c079bf658e816b2c423dd38149f354e8a5ab1b28da0e83e0', 14, 114346386, 'USDT', 0.10000000, 0.00000000, 79, 'mainnet', 'credited', NULL, '2026-08-06 13:37:18', '2026-08-06 17:07:18', '2026-08-06 17:07:18'),
(31, 999999607, '0xf18948D95e2B8DEe52a5816c48B02Eb245c4Fa1B', '0x71e038da10d1aab4925c683a140d72c115f1efe3', '0xf3d2c619e860969693929bd6511e40d08ece7d5c367345029a3b69a4a1350117', 10, 114346802, 'USDT', 0.20000000, 0.00000000, 26, 'mainnet', 'credited', NULL, '2026-08-06 13:40:02', '2026-08-06 17:10:01', '2026-08-06 17:10:02'),
(32, 999999608, '0x77779986DF95EBEaE48F4c6a94Be2886eA7a943C', '0x71e038da10d1aab4925c683a140d72c115f1efe3', '0x9dda532b6cc1424ac65e3c675da414d66b4a57401f28e227b044472fe5d84aea', 29, 114386196, 'USDT', 0.10000000, 0.00000000, 27, 'mainnet', 'credited', NULL, '2026-08-06 16:37:29', '2026-08-06 16:37:29', '2026-08-06 16:37:29'),
(33, 999999612, '0x084B8f91a35c79c49157b095d61F4Ab42910A093', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x0fdb1e48cec88f3fd8b314f13683d05d8e57ce6c32c3028522e59d1158b72d38', 7, 114389925, 'BMAN', 0.00000000, 4.00000000, 65, 'mainnet', 'credited', NULL, '2026-08-06 17:05:45', '2026-08-06 17:05:44', '2026-08-06 17:05:45'),
(34, 999999612, '0x084B8f91a35c79c49157b095d61F4Ab42910A093', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x3d58397d37a7067a7f9382ec7e116e1474e444f220623638d5663482f34bbd1d', 17, 114392185, 'USDT', 0.30000000, 0.00000000, 77, 'mainnet', 'credited', NULL, '2026-08-06 17:22:47', '2026-08-06 17:22:46', '2026-08-06 17:22:47'),
(35, 999999611, '0xd85Ea024Be14032b7c25a04b017DB8Bf28f5da57', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x7e27499ab4e4c63ce5d5430323462ed72c12ce07a63fdb4fb88a799e2f3d83ef', 7, 114396314, 'BMAN', 0.00000000, 4.00000000, 78, 'mainnet', 'credited', NULL, '2026-08-06 17:53:45', '2026-08-06 17:53:45', '2026-08-06 17:53:45'),
(36, 999999612, '0x084B8f91a35c79c49157b095d61F4Ab42910A093', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xf8b298fa9fe11b9d45b0c24b7445e5bc03063e3bd75570504ea6266533b0ff15', 18, 114399281, 'BMAN', 0.00000000, 3.00000000, 230, 'mainnet', 'credited', NULL, '2026-08-06 18:17:10', '2026-08-06 18:17:09', '2026-08-06 18:17:10'),
(37, 999999612, '0x084B8f91a35c79c49157b095d61F4Ab42910A093', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x31b0d017ca0d861874e72322f687b55cd9d8f163633b2b154e6c057acd9bd052', 25, 114393644, 'BMAN', 0.00000000, 1.25000000, 5867, 'mainnet', 'credited', NULL, '2026-08-06 18:17:10', '2026-08-06 18:17:09', '2026-08-06 18:17:10'),
(38, 999999612, '0x084B8f91a35c79c49157b095d61F4Ab42910A093', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x55e0ad3b428365b6804d284df9fac96978264d52a65a69bc91e913ffefbbf90e', 20, 114393638, 'BMAN', 0.00000000, 2.50000000, 5873, 'mainnet', 'credited', NULL, '2026-08-06 18:17:10', '2026-08-06 18:17:09', '2026-08-06 18:17:10'),
(39, 999999606, '0x2E228070726Ec09A6e0a9d89287F900B5DD2D3Db', '0x71e038da10d1aab4925c683a140d72c115f1efe3', '0x6a63e130467681d2eaeb16dcc9cf9c391c6acb4df80ef8f282dc9180ee70ab58', 13, 114509760, 'USDT', 0.20000000, 0.00000000, 46, 'mainnet', 'credited', NULL, '2026-08-07 10:04:37', '2026-08-07 13:34:36', '2026-08-07 13:34:37'),
(40, 999999606, '0x2E228070726Ec09A6e0a9d89287F900B5DD2D3Db', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0xb2cd662d98f6ee865706d3c0e8308015674501d0d709659193c7af01c0e6c93e', 40, 114511469, 'BMAN', 0.00000000, 2.00000000, 46, 'mainnet', 'credited', NULL, '2026-08-07 10:17:26', '2026-08-07 13:47:26', '2026-08-07 13:47:26'),
(41, 999999606, '0x2E228070726Ec09A6e0a9d89287F900B5DD2D3Db', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x2ab5a3a69903d683be0fc695d5e52154e9f276cef5da6bcba3303ff0a656bb58', 43, 114511354, 'BMAN', 0.00000000, 0.10000000, 161, 'mainnet', 'credited', NULL, '2026-08-07 10:17:26', '2026-08-07 13:47:26', '2026-08-07 13:47:26'),
(42, 999999606, '0x2E228070726Ec09A6e0a9d89287F900B5DD2D3Db', '0x3088b858dc4cd85a001337f8e15a40b24666d321', '0x4f46ebd6d76121a1ac3ee2e7a2e4643d2c0b0a92d9a3b4b722ad51decbe72eaf', 29, 114510768, 'BMAN', 0.00000000, 1.25000000, 747, 'mainnet', 'credited', NULL, '2026-08-07 10:17:26', '2026-08-07 13:47:26', '2026-08-07 13:47:26'),
(43, 999999613, '0x85519d7A4E94a070eceeEe5e1763206C4D6665Ff', '0x71e038da10d1aab4925c683a140d72c115f1efe3', '0x340bdd28e002995e4e22ac56d4683436ca2c8573f2ddd7f23993401697701fd4', 6, 114546698, 'USDT', 0.20000000, 0.00000000, 34, 'mainnet', 'credited', NULL, '2026-08-07 14:41:37', '2026-08-07 18:11:36', '2026-08-07 18:11:37'),
(44, 999999616, '0x4E107AAbeE4f7D2a12abf12cD1fc9506523E49Fb', '0x71e038da10d1aab4925c683a140d72c115f1efe3', '0x8b66252e51f0d30ce4f1e5a9c048dc31b90dda6f7950712a9f61c8119f21a582', 18, 114563505, 'USDT', 0.20000000, 0.00000000, 33, 'mainnet', 'credited', NULL, '2026-08-07 20:17:42', '2026-08-07 20:17:41', '2026-08-07 20:17:42');

-- --------------------------------------------------------

--
-- Table structure for table `wallet_internal_transfer`
--

CREATE TABLE `wallet_internal_transfer` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ref` varchar(32) NOT NULL,
  `txn_uid` varchar(8) DEFAULT NULL COMMENT '8-digit global tracking id',
  `user_id` int(11) NOT NULL,
  `to_user_id` int(11) DEFAULT NULL COMMENT 'recipient (member-to-member transfer)',
  `from_wallet` enum('exchange','earning','staking','bonus') NOT NULL,
  `to_wallet` enum('exchange','earning','staking','bonus') NOT NULL,
  `amount` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `fee` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `net_amount` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `from_before` decimal(30,8) DEFAULT NULL,
  `from_after` decimal(30,8) DEFAULT NULL,
  `to_before` decimal(30,8) DEFAULT NULL,
  `to_after` decimal(30,8) DEFAULT NULL,
  `status` enum('completed','failed','reversed') NOT NULL DEFAULT 'completed',
  `txn_type` varchar(10) NOT NULL DEFAULT 'self',
  `via` varchar(10) NOT NULL DEFAULT 'user' COMMENT 'user | admin',
  `description` varchar(255) DEFAULT NULL,
  `debit_ledger_id` bigint(20) UNSIGNED DEFAULT NULL,
  `credit_ledger_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `idempotency_key` varchar(80) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `failure_reason` varchar(200) DEFAULT NULL,
  `tx_hash` varchar(120) DEFAULT NULL,
  `block_number` bigint(20) UNSIGNED DEFAULT NULL,
  `confirmations` int(11) DEFAULT NULL,
  `gas_used` bigint(20) DEFAULT NULL,
  `gas_fee` decimal(38,18) DEFAULT NULL,
  `network` varchar(20) DEFAULT NULL,
  `settlement_status` enum('pending','processing','completed','failed','skipped') NOT NULL DEFAULT 'pending' COMMENT 'on-chain settlement lifecycle — independent of `status` (the internal ledger completion)',
  `settlement_address` varchar(100) DEFAULT NULL COMMENT 'resolved 0x destination — self: own wallet_address, member: recipient wallet_address',
  `settlement_attempts` int(11) NOT NULL DEFAULT 0,
  `settlement_error` varchar(255) DEFAULT NULL,
  `settled_at` datetime DEFAULT NULL,
  `credit_onchain_ledger_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'alias of credit_ledger_id, named for clarity at the settlement call site'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Internal wallet-to-wallet transfer audit log';

--
-- Dumping data for table `wallet_internal_transfer`
--

INSERT INTO `wallet_internal_transfer` (`id`, `ref`, `txn_uid`, `user_id`, `to_user_id`, `from_wallet`, `to_wallet`, `amount`, `fee`, `net_amount`, `from_before`, `from_after`, `to_before`, `to_after`, `status`, `txn_type`, `via`, `description`, `debit_ledger_id`, `credit_ledger_id`, `ip_address`, `user_agent`, `created_at`, `updated_at`, `idempotency_key`, `created_by`, `failure_reason`, `tx_hash`, `block_number`, `confirmations`, `gas_used`, `gas_fee`, `network`, `settlement_status`, `settlement_address`, `settlement_attempts`, `settlement_error`, `settled_at`, `credit_onchain_ledger_id`) VALUES
(1, 'WTS-20260720-30109EC8', '14869419', 3, 4, 'exchange', 'exchange', 1.00000000, 0.00000000, 1.00000000, 1.00000000, 0.00000000, 0.00000000, 1.00000000, 'completed', 'member', 'user', '1.0000 Exchange Wallet → NEXMAN830893', 8, 9, '192.168.29.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-20 22:32:46', '2026-07-23 18:20:12', NULL, NULL, NULL, '0x3685efee9b922057db889d2a57677be80e4a0949ad5870bbda3680e82daf1fb4', NULL, NULL, NULL, NULL, 'BSC', 'completed', '0x6A3356EEC7660058122E4387AA61F8E1aE220A7f', 1, NULL, '2026-07-23 14:50:12', 9),
(2, 'WTS-20260721-2B2A19D7', '73625129', 1, 3, 'exchange', 'exchange', 0.50000000, 0.00000000, 0.50000000, 2.50000000, 2.00000000, 2.25000000, 2.75000000, 'completed', 'member', 'user', 'Note online transfer', 16, 17, '192.168.29.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-21 19:42:50', '2026-07-23 18:20:12', NULL, NULL, NULL, '0x3685efee9b922057db889d2a57677be80e4a0949ad5870bbda3680e82daf1fb4', NULL, NULL, NULL, NULL, 'BSC', 'completed', '0x18d0B930970f05ABFC5CC08cad9346aF58D3dd24', 1, NULL, '2026-07-23 14:50:12', 17),
(3, 'WTS-20260723-7C148395', '30356260', 1, 2, 'exchange', 'exchange', 2.00000000, 0.00000000, 2.00000000, 3.25000000, 1.25000000, 0.00000000, 2.00000000, 'completed', 'member', 'user', 'Admin to Siva', 21, 22, '192.168.29.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-23 17:16:38', '2026-07-23 18:20:12', NULL, NULL, NULL, '0x3685efee9b922057db889d2a57677be80e4a0949ad5870bbda3680e82daf1fb4', NULL, NULL, NULL, NULL, 'BSC', 'completed', '0x365E2F051Cd601F8828cf33c3D1B7C87a0141c1b', 1, NULL, '2026-07-23 14:50:12', 22),
(4, 'WTS-20260723-898A1F3B', '95247704', 1, NULL, 'exchange', 'bonus', 1.00000000, 0.00000000, 1.00000000, 1.25000000, 0.25000000, 0.00000000, 1.00000000, 'completed', 'self', 'user', 'Exchange to Bonus', 23, 24, '192.168.29.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-23 17:29:53', '2026-07-23 18:20:12', NULL, NULL, NULL, '0x3685efee9b922057db889d2a57677be80e4a0949ad5870bbda3680e82daf1fb4', NULL, NULL, NULL, NULL, 'BSC', 'completed', '0xcb3c5E2bcef54Fec78974CAe31828cF9F33dA9c7', 0, NULL, '2026-07-23 14:50:12', 24),
(5, 'WTS-20260806-465D66EE', '25946003', 999999612, NULL, 'exchange', 'bonus', 1.00000000, 0.00000000, 1.00000000, 4.00000000, 3.00000000, 0.00000000, 1.00000000, 'completed', 'self', 'user', '', 191, 192, '2409:40f4:143:cef5:191a:dc6c:def7:868e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '2026-08-06 17:13:10', '2026-08-06 17:13:10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'skipped', '0x084B8f91a35c79c49157b095d61F4Ab42910A093', 0, NULL, NULL, 192);

-- --------------------------------------------------------

--
-- Table structure for table `wallet_ledger`
--

CREATE TABLE `wallet_ledger` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `wallet_type` enum('usdt','exchange','earning','staking','bonus') NOT NULL,
  `credit` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `debit` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `balance_after` decimal(30,8) NOT NULL DEFAULT 0.00000000,
  `reference_type` varchar(40) NOT NULL,
  `reference_id` varchar(64) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `tx_hash` varchar(120) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `maturity_date` datetime DEFAULT NULL COMMENT 'When this credit becomes withdrawable',
  `is_matured` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = credit is withdrawable'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallet_ledger`
--

INSERT INTO `wallet_ledger` (`id`, `user_id`, `wallet_type`, `credit`, `debit`, `balance_after`, `reference_type`, `reference_id`, `description`, `tx_hash`, `created_by`, `created_at`, `maturity_date`, `is_matured`) VALUES
(1, 1, 'usdt', 0.20000000, 0.00000000, 0.20000000, 'deposit', '1', 'USDT deposit 0.20000000 (BEP-20)', '0x29a05409254a504406c13e08426d455b6d8a8b058bcd9d92c7507ef8df2fca05', NULL, '2026-07-20 21:03:55', '2026-07-20 17:33:55', 1),
(2, 1, 'usdt', 0.20000000, 0.00000000, 0.40000000, 'deposit', '2', 'USDT deposit 0.20000000 (BEP-20)', '0x680529f21b8039c0bfccf9d3b7144c247d399bbfb690a8ee0ff95fa97b80096b', NULL, '2026-07-20 21:03:55', '2026-07-20 17:33:55', 1),
(3, 1, 'usdt', 0.10000000, 0.00000000, 0.50000000, 'deposit', '3', 'USDT deposit 0.10000000 (BEP-20)', '0x58500f3715c58ea34e4385363382b696338798669615022f7de9dfabe63c22d4', NULL, '2026-07-20 21:03:55', '2026-07-20 17:33:55', 1),
(4, 3, 'usdt', 0.20000000, 0.00000000, 0.20000000, 'deposit', '4', 'USDT deposit 0.20000000 (BEP-20)', '0xa0b59d15308666c6a5c78c1be5e373f6ba9b6a057d170bc1d537e9e5faf96dc1', NULL, '2026-07-20 22:05:34', '2026-07-20 18:35:34', 1),
(5, 3, 'usdt', 0.00000000, 0.10000000, 0.10000000, 'swap', 'SWP-20260720-82A2766D', 'Swap: USDT pending transfer to admin 0x3088B858dc4cD85A001337f8E15a40b24666d321 [SWP-20260720-82A2766D]', NULL, NULL, '2026-07-20 22:16:29', NULL, 0),
(6, 3, 'bonus', 0.25000000, 0.00000000, 0.25000000, 'stake_purchase', 'SWP-20260720-82A2766D', 'Bonus allocation 0.25 BMAN (order 1)', '0xb3e94ea802696e384d8450fa8de9c5cb680fece5e0d8fbe72d5009a648707bff', NULL, '2026-07-20 22:17:54', '2026-09-18 18:47:54', 0),
(7, 3, 'exchange', 1.00000000, 0.00000000, 1.00000000, 'stake_purchase', 'SWP-20260720-82A2766D', 'Exchange allocation 1 BMAN (order 1)', '0x3d335923489fb747bb12aead4e973c7fc03bf03b212fbdee9e8fee5a49552bdc', NULL, '2026-07-20 22:17:56', '2026-07-20 18:47:56', 1),
(8, 3, 'exchange', 0.00000000, 1.00000000, 0.00000000, 'wallet_transfer', 'WTS-20260720-30109EC8', 'Transfer exchange (member) [14869419]', NULL, NULL, '2026-07-20 22:32:46', NULL, 0),
(9, 4, 'exchange', 1.00000000, 0.00000000, 1.00000000, 'wallet_transfer', 'WTS-20260720-30109EC8', 'Transfer received exchange [14869419]', NULL, NULL, '2026-07-20 22:32:46', '2026-07-20 19:02:46', 1),
(10, 3, 'exchange', 0.25000000, 0.00000000, 0.25000000, 'deposit', '6', 'BMAN deposit 0.25000000 (BEP-20)', '0xb3e94ea802696e384d8450fa8de9c5cb680fece5e0d8fbe72d5009a648707bff', NULL, '2026-07-21 13:50:35', '2026-07-21 10:20:35', 1),
(11, 3, 'exchange', 2.00000000, 0.00000000, 2.25000000, 'deposit', '7', 'BMAN deposit 2.00000000 (BEP-20)', '0xd315011a2c3b5496e2fc9e7976ae0d7f56b7f168520736f22e1180111e35b943', NULL, '2026-07-21 13:50:35', '2026-07-21 10:20:35', 1),
(12, 1, 'exchange', 1.00000000, 0.00000000, 1.00000000, 'deposit', '8', 'BMAN deposit 1.00000000 (BEP-20)', '0x39ae895242516de2dc9576a079244f3aaf760b411bbef7ac641f561f293d8a1c', NULL, '2026-07-21 14:14:44', '2026-07-21 10:44:44', 1),
(13, 1, 'exchange', 0.25000000, 0.00000000, 1.25000000, 'deposit', '9', 'BMAN deposit 0.25000000 (BEP-20)', '0x380739509750a561e3067d5515e3be53cf071289ffd0843bf92e5ae80a918ad5', NULL, '2026-07-21 14:14:44', '2026-07-21 10:44:44', 1),
(14, 1, 'exchange', 1.00000000, 0.00000000, 2.25000000, 'deposit', '10', 'BMAN deposit 1.00000000 (BEP-20)', '0x7d57370562c2d0eb7e653ad2eff05d0b2289adbb12160b2046c4c42fd97cbbb0', NULL, '2026-07-21 14:14:44', '2026-07-21 10:44:44', 1),
(15, 1, 'exchange', 0.25000000, 0.00000000, 2.50000000, 'deposit', '11', 'BMAN deposit 0.25000000 (BEP-20)', '0x887bdd07dfbaed9d8e8e78e700c63759500a3e540cc4e10b1321a6d47480affa', NULL, '2026-07-21 14:14:44', '2026-07-21 10:44:44', 1),
(16, 1, 'exchange', 0.00000000, 0.50000000, 2.00000000, 'wallet_transfer', 'WTS-20260721-2B2A19D7', 'Transfer exchange (member) [73625129]', NULL, NULL, '2026-07-21 19:42:50', NULL, 0),
(17, 3, 'exchange', 0.50000000, 0.00000000, 2.75000000, 'wallet_transfer', 'WTS-20260721-2B2A19D7', 'Transfer received exchange [73625129]', NULL, NULL, '2026-07-21 19:42:50', '2026-07-21 16:12:50', 1),
(18, 3, 'exchange', 0.50000000, 0.00000000, 3.25000000, 'deposit', '12', 'BMAN deposit 0.50000000 (BEP-20)', '0xb845667b4cc77a160974e81aee6064326ed99d2eeb19b321b2d85972dc2f9f3d', NULL, '2026-07-21 19:45:28', '2026-07-21 16:15:28', 1),
(19, 1, 'exchange', 1.00000000, 0.00000000, 3.00000000, 'deposit', '13', 'BMAN deposit 1.00000000 (BEP-20)', '0x3d9ca04e515d17571c68352db6474a18ceb1605d5a71701471246175ab710feb', NULL, '2026-07-23 10:36:02', '2026-07-23 07:06:02', 1),
(20, 1, 'exchange', 0.25000000, 0.00000000, 3.25000000, 'deposit', '14', 'BMAN deposit 0.25000000 (BEP-20)', '0x058f6dd1923c52e56da2de73fbf67a89028b59f86c7bb4053e080791df2748e2', NULL, '2026-07-23 10:36:03', '2026-07-23 07:06:03', 1),
(21, 1, 'exchange', 0.00000000, 2.00000000, 1.25000000, 'wallet_transfer', 'WTS-20260723-7C148395', 'Transfer exchange (member) [30356260]', NULL, NULL, '2026-07-23 17:16:38', NULL, 0),
(22, 2, 'exchange', 2.00000000, 0.00000000, 2.00000000, 'wallet_transfer', 'WTS-20260723-7C148395', 'Transfer received exchange [30356260]', NULL, NULL, '2026-07-23 17:16:38', '2026-07-23 13:46:38', 1),
(23, 1, 'exchange', 0.00000000, 1.00000000, 0.25000000, 'wallet_transfer', 'WTS-20260723-898A1F3B', 'Transfer exchange (internal) [95247704]', NULL, NULL, '2026-07-23 17:29:53', NULL, 0),
(24, 1, 'bonus', 1.00000000, 0.00000000, 1.00000000, 'wallet_transfer', 'WTS-20260723-898A1F3B', 'Transfer received exchange [95247704]', NULL, NULL, '2026-07-23 17:29:53', '2026-07-23 13:59:53', 1),
(25, 3, 'usdt', 0.00000000, 0.10000000, 0.00000000, 'swap', 'SWP-20260723-87AA1F16', 'Swap: USDT pending transfer to admin 0x3088B858dc4cD85A001337f8E15a40b24666d321 [SWP-20260723-87AA1F16]', NULL, NULL, '2026-07-23 18:33:11', NULL, 0),
(26, 3, 'bonus', 0.25000000, 0.00000000, 0.50000000, 'stake_purchase', 'SWP-20260723-87AA1F16', 'Bonus allocation 0.25 BMAN (order 2)', '0xa288fb3468bacb651689e208b1f81f2454ffb1aa5868b72955ca24726a5cc819', NULL, '2026-07-23 18:44:37', '2026-09-21 15:14:37', 0),
(27, 3, 'exchange', 0.70000000, 0.00000000, 3.95000000, 'stake_purchase', 'SWP-20260723-87AA1F16', 'Exchange allocation 0.7 BMAN (order 2)', '0x16288134bf91c80eb4268982f128a9a2b89a3580865dce273565caf28b03baa5', NULL, '2026-07-23 18:44:40', '2026-07-23 15:14:40', 1),
(28, 3, 'earning', 0.10000000, 0.00000000, 0.10000000, 'stake_purchase', 'SWP-20260723-87AA1F16', 'Earning allocation 0.1 BMAN (order 2)', '0x16288134bf91c80eb4268982f128a9a2b89a3580865dce273565caf28b03baa5', NULL, '2026-07-23 18:44:40', '2026-08-22 15:14:40', 0),
(29, 3, 'staking', 0.10000000, 0.00000000, 0.10000000, 'stake_purchase', 'SWP-20260723-87AA1F16', 'Staking allocation 0.1 BMAN (order 2)', '0x16288134bf91c80eb4268982f128a9a2b89a3580865dce273565caf28b03baa5', NULL, '2026-07-23 18:44:40', '2026-07-23 15:14:40', 1),
(30, 3, 'bonus', 0.10000000, 0.00000000, 0.60000000, 'stake_purchase', 'SWP-20260723-87AA1F16', 'Bonus allocation 0.1 BMAN (order 2)', '0x16288134bf91c80eb4268982f128a9a2b89a3580865dce273565caf28b03baa5', NULL, '2026-07-23 18:44:40', '2026-09-21 15:14:40', 0),
(31, 3, 'usdt', 0.01000000, 0.00000000, 0.01000000, 'deposit', '15', 'USDT deposit 0.01000000 (BEP-20)', '0xad1f757e45aa052eff5628f31551a0bc049e14151b64239535f0237b4d56e3eb', NULL, '2026-07-29 19:06:35', '2026-07-29 15:36:35', 1),
(32, 3, 'exchange', 0.25000000, 0.00000000, 4.20000000, 'deposit', '17', 'BMAN deposit 0.25000000 (BEP-20)', '0xa288fb3468bacb651689e208b1f81f2454ffb1aa5868b72955ca24726a5cc819', NULL, '2026-07-29 19:06:38', '2026-07-29 15:36:38', 1),
(35, 3, 'usdt', 0.10000000, 0.00000000, 0.11000000, 'deposit', '18', 'USDT deposit 0.10000000 (BEP-20)', '0x05ef03ebcd04e686239682443a25adaa2a76c2911009e4ba4ae628671d16868c', NULL, '2026-07-29 20:48:11', '2026-07-29 17:18:11', 1),
(36, 3, 'usdt', 0.00000000, 0.10000000, 0.01000000, 'swap', 'SWP-20260729-77163357', 'Swap: USDT pending transfer to admin 0x3088B858dc4cD85A001337f8E15a40b24666d321 [SWP-20260729-77163357]', NULL, NULL, '2026-07-29 20:49:03', NULL, 0),
(37, 2, 'usdt', 0.20000000, 0.00000000, 0.20000000, 'deposit', '19', 'USDT deposit 0.20000000 (BEP-20)', '0xe9f7f60185687ca5333d3b61374441f607638478c4689da7551a4a46430b997b', NULL, '2026-07-30 18:00:14', '2026-07-30 14:30:14', 1),
(38, 2, 'exchange', 2.00000000, 0.00000000, 4.00000000, 'deposit', '20', 'BMAN deposit 2.00000000 (BEP-20)', '0x3685efee9b922057db889d2a57677be80e4a0949ad5870bbda3680e82daf1fb4', NULL, '2026-07-30 18:00:17', '2026-07-30 14:30:17', 1),
(39, 2, 'exchange', 2.00000000, 0.00000000, 6.00000000, 'deposit', '21', 'BMAN deposit 2.00000000 (BEP-20)', '0xa95dd6d48c6c1f0314ccb1c5f8286acb4f4b4b9a50bc9023f604a5200bc6c9d8', NULL, '2026-07-30 18:00:17', '2026-07-30 14:30:17', 1),
(40, 2, 'usdt', 0.00000000, 0.20000000, 0.00000000, 'swap', 'SWP-20260730-FC30455C', 'Swap: USDT pending transfer to admin 0x3088B858dc4cD85A001337f8E15a40b24666d321 [SWP-20260730-FC30455C]', NULL, NULL, '2026-07-30 18:05:10', NULL, 0),
(41, 3, 'bonus', 0.25000000, 0.00000000, 0.85000000, 'stake_purchase', 'SWP-20260729-77163357', 'Bonus allocation 0.25 BMAN (order 5)', '0x6c921516d4892b72ba9ac163544880a26e1d555d26b007b8bd7f03e59bcdccf3', NULL, '2026-07-30 18:06:06', '2026-09-28 14:36:06', 0),
(42, 3, 'exchange', 1.00000000, 0.00000000, 5.20000000, 'stake_purchase', 'SWP-20260729-77163357', 'Exchange allocation 1 BMAN (order 5)', '0x87bf9ecc9161cd6a366f38d5c22a20693ff05c59676135bc02d1beb6fb7539f5', NULL, '2026-07-30 18:06:08', '2026-07-30 14:36:08', 1),
(84, 23, 'usdt', 0.19353990, 0.00000000, 0.19353990, 'deposit', '22', 'USDT deposit 0.19353990 (BEP-20)', '0x82c6095c9913b6c413150e3397a25fbe5ac0f431e06702cb775630b16e48fcdd', NULL, '2026-08-05 15:35:02', '2026-08-05 12:05:02', 1),
(85, 23, 'exchange', 4.00000000, 0.00000000, 4.00000000, 'deposit', '23', 'BMAN deposit 4.00000000 (BEP-20)', '0x822111baa209be024a5a1140954d4e4eccb74653501fd81e61efba7398c7b70e', NULL, '2026-08-05 15:35:04', '2026-08-05 12:05:04', 1),
(86, 2, 'bonus', 0.50000000, 0.00000000, 0.50000000, 'stake_purchase', 'SWP-20260730-FC30455C', 'Bonus allocation 0.5 BMAN (order 6)', '0xf546aaab4e2b6cd7db39822e04569a084a87cd6707fa665d01a8e9b0773d4cbe', NULL, '2026-08-05 15:59:12', '2026-10-04 12:29:12', 0),
(87, 2, 'exchange', 1.80000000, 0.00000000, 7.80000000, 'stake_purchase', 'SWP-20260730-FC30455C', 'Exchange allocation 1.8 BMAN (order 6)', '0x84f0715ea64a2c26e34171a76e554eeacbea7d48f4c3a1271cbd3d5f3fc34614', NULL, '2026-08-05 15:59:14', '2026-08-05 12:29:14', 1),
(88, 2, 'earning', 0.20000000, 0.00000000, 0.20000000, 'stake_purchase', 'SWP-20260730-FC30455C', 'Earning allocation 0.2 BMAN (order 6)', '0x84f0715ea64a2c26e34171a76e554eeacbea7d48f4c3a1271cbd3d5f3fc34614', NULL, '2026-08-05 15:59:14', '2026-09-04 12:29:14', 0),
(91, 23, 'usdt', 0.30000000, 0.00000000, 0.49000000, 'deposit', '24', 'USDT deposit 0.30000000 (BEP-20)', '0xdebc914bae7a119986abec153fe25e4ef0726479b630e76c4e5b291bc8bc2761', NULL, '2026-08-05 16:14:24', '2026-08-05 12:44:24', 1),
(92, 23, 'usdt', 0.00000000, 0.20000000, 0.29000000, 'swap', 'SWP-20260805-48D6BAB1', 'Swap: USDT pending transfer to admin 0x3088B858dc4cD85A001337f8E15a40b24666d321 [SWP-20260805-48D6BAB1]', NULL, NULL, '2026-08-05 16:18:38', NULL, 0),
(93, 23, 'bonus', 0.50000000, 0.00000000, 0.50000000, 'stake_purchase', 'SWP-20260805-48D6BAB1', 'Bonus allocation 0.5 BMAN (order 10)', '0x47d6224d250d8912e7cbf270476a2fb726944bcc9680225c1250c67f3dacf727', NULL, '2026-08-05 16:40:37', '2026-10-04 13:10:37', 0),
(94, 23, 'exchange', 2.00000000, 0.00000000, 6.00000000, 'stake_purchase', 'SWP-20260805-48D6BAB1', 'Exchange allocation 2 BMAN (order 10)', '0x460fa827d11c69b34ab2f2ef10544e5b2ca101f5da28180d806c260c67199361', NULL, '2026-08-05 16:40:39', '2026-08-05 13:10:39', 1),
(151, 999999504, 'usdt', 0.10000000, 0.00000000, 0.10000000, 'deposit', '25', 'USDT deposit 0.10000000 (BEP-20)', '0x9f3bd944cb97cfeed2b37f767d395652677dc3dc74ce2a2a282c6d5f267a20b9', NULL, '2026-08-06 12:02:05', '2026-08-06 08:32:05', 1),
(152, 999999504, 'usdt', 0.00000000, 0.10000000, 0.00000000, 'swap', 'SWP-20260806-9AB2A316', 'Swap: USDT pending transfer to admin 0x3088B858dc4cD85A001337f8E15a40b24666d321 [SWP-20260806-9AB2A316]', NULL, NULL, '2026-08-06 12:08:27', NULL, 0),
(156, 999999504, 'usdt', 0.10000000, 0.00000000, 0.10000000, 'order_reset', 'SWP-20260806-9AB2A316', 'Refund: cancelled dry-run test order SWP-20260806-9AB2A316 (order #23)', NULL, NULL, '2026-08-06 12:40:09', '2026-08-06 09:10:09', 1),
(157, 999999504, 'bonus', 0.00000000, 0.25000000, 0.00000000, 'order_reset', 'SWP-20260806-9AB2A316', 'Reversal: dry-run bonus credit from cancelled test order SWP-20260806-9AB2A316 (order #23)', NULL, NULL, '2026-08-06 12:40:09', NULL, 0),
(158, 999999504, 'staking', 0.00000000, 1.00000000, 0.00000000, 'order_reset', 'SWP-20260806-9AB2A316', 'Reversal: dry-run staking credit from cancelled test order SWP-20260806-9AB2A316 (order #23)', NULL, NULL, '2026-08-06 12:40:09', NULL, 0),
(159, 999999504, 'usdt', 0.00000000, 0.10000000, 0.00000000, 'swap', 'SWP-20260806-C41648E7', 'Swap: USDT pending transfer to admin 0x3088B858dc4cD85A001337f8E15a40b24666d321 [SWP-20260806-C41648E7]', NULL, NULL, '2026-08-06 12:46:58', NULL, 0),
(162, 999999602, 'usdt', 0.10000000, 0.00000000, 0.10000000, 'deposit', '27', 'USDT deposit 0.10000000 (BEP-20)', '0xd2c32ea63ab46d4c334983dbe4eba2c7e84d7b1306719390654c4545f2f83161', NULL, '2026-08-06 16:02:35', '2026-08-06 12:32:35', 1),
(163, 999999603, 'usdt', 0.20000000, 0.00000000, 0.20000000, 'deposit', '28', 'USDT deposit 0.20000000 (BEP-20)', '0x6e06cbe4bc0365a8b4fbec1794b644bf18718911667c241e75ca12616bb3e591', NULL, '2026-08-06 16:13:47', '2026-08-06 12:43:47', 1),
(164, 999999602, 'usdt', 0.00000000, 0.10000000, 0.00000000, 'swap', 'SWP-20260806-D71FFF32', 'Swap: USDT pending transfer to admin 0x3088B858dc4cD85A001337f8E15a40b24666d321 [SWP-20260806-D71FFF32]', NULL, NULL, '2026-08-06 16:20:19', NULL, 0),
(165, 999999603, 'usdt', 0.00000000, 0.20000000, 0.00000000, 'swap', 'SWP-20260806-9ADDC0B2', 'Swap: USDT pending transfer to admin 0x3088B858dc4cD85A001337f8E15a40b24666d321 [SWP-20260806-9ADDC0B2]', NULL, NULL, '2026-08-06 16:22:07', NULL, 0),
(172, 999999504, 'bonus', 0.25000000, 0.00000000, 0.25000000, 'bonus', 'STK-20260806-C115EEE8', '25% staking bonus — stake #46', NULL, NULL, '2026-08-06 16:50:10', '2026-08-06 13:20:10', 1),
(175, 999999504, 'bonus', 0.25000000, 0.00000000, 0.25000000, 'bonus', 'STK-20260806-F867B0EB', '25% staking bonus — stake #47', NULL, NULL, '2026-08-06 16:52:30', '2026-08-06 13:22:30', 1),
(178, 999999602, 'exchange', 1.25000000, 0.00000000, 1.25000000, 'deposit', '29', 'BMAN deposit 1.25000000 (BEP-20)', '0x1e3476353fc62bae0574f9c2d0511acb7a8097e373a9399ac0d3df8ef436631e', NULL, '2026-08-06 17:01:32', '2026-08-06 13:31:32', 1),
(179, 999999604, 'usdt', 0.10000000, 0.00000000, 0.10000000, 'deposit', '30', 'USDT deposit 0.10000000 (BEP-20)', '0xd1980118c60eeb05c079bf658e816b2c423dd38149f354e8a5ab1b28da0e83e0', NULL, '2026-08-06 17:07:18', '2026-08-06 13:37:18', 1),
(180, 999999604, 'usdt', 0.00000000, 0.10000000, 0.00000000, 'swap', 'SWP-20260806-0EBEEDFB', 'Swap: USDT pending transfer to admin 0x3088B858dc4cD85A001337f8E15a40b24666d321 [SWP-20260806-0EBEEDFB]', NULL, NULL, '2026-08-06 17:07:54', NULL, 0),
(181, 999999607, 'usdt', 0.20000000, 0.00000000, 0.20000000, 'deposit', '31', 'USDT deposit 0.20000000 (BEP-20)', '0xf3d2c619e860969693929bd6511e40d08ece7d5c367345029a3b69a4a1350117', NULL, '2026-08-06 17:10:02', '2026-08-06 13:40:02', 1),
(182, 999999607, 'usdt', 0.00000000, 0.20000000, 0.00000000, 'swap', 'SWP-20260806-649E1E10', 'Swap: USDT pending transfer to admin 0x3088B858dc4cD85A001337f8E15a40b24666d321 [SWP-20260806-649E1E10]', NULL, NULL, '2026-08-06 17:10:30', NULL, 0),
(183, 999999604, 'bonus', 0.25000000, 0.00000000, 0.25000000, 'stake_purchase', 'SWP-20260806-0EBEEDFB', 'Bonus allocation 0.25 BMAN (order 27)', '0x5ba7c051a2b1a8af3331a5f00b4dee8796695ece5d8b3be4e4eccb125ee1c56d', NULL, '2026-08-06 17:11:57', '2026-08-06 13:41:57', 1),
(184, 999999607, 'bonus', 0.50000000, 0.00000000, 0.50000000, 'stake_purchase', 'SWP-20260806-649E1E10', 'Bonus allocation 0.5 BMAN (order 28)', '0x88e71c7b8db3e36b861f08f6c68f6ebb2c8db2afa744486390bd06394829398e', NULL, '2026-08-06 17:12:53', '2026-08-06 13:42:53', 1),
(185, 999999608, 'usdt', 0.10000000, 0.00000000, 0.10000000, 'deposit', '32', 'USDT deposit 0.10000000 (BEP-20)', '0x9dda532b6cc1424ac65e3c675da414d66b4a57401f28e227b044472fe5d84aea', NULL, '2026-08-06 16:37:29', '2026-08-06 16:37:29', 1),
(186, 999999608, 'usdt', 0.00000000, 0.10000000, 0.00000000, 'swap', 'SWP-20260806-7C3C3DDC', 'Swap: USDT pending transfer to admin 0x3088B858dc4cD85A001337f8E15a40b24666d321 [SWP-20260806-7C3C3DDC]', NULL, NULL, '2026-08-06 16:38:14', NULL, 0),
(187, 999999608, 'bonus', 0.25000000, 0.00000000, 0.25000000, 'stake_purchase', 'SWP-20260806-7C3C3DDC', 'Bonus allocation 0.25 BMAN (order 29)', '0xa0c873980ba96f706e3e5b4e652b533af7b48345adcc479969afed4f5fe66936', NULL, '2026-08-06 16:53:04', '2026-08-06 16:53:04', 1),
(188, 999999612, 'exchange', 4.00000000, 0.00000000, 4.00000000, 'deposit', '33', 'BMAN deposit 4.00000000 (BEP-20)', '0x0fdb1e48cec88f3fd8b314f13683d05d8e57ce6c32c3028522e59d1158b72d38', NULL, '2026-08-06 17:05:45', '2026-08-06 17:05:45', 1),
(189, 22, 'earning', 2.00000000, 0.00000000, 2.00000000, 'admin_adjustment', 'MBU-20260730-660810D7', 'Bulk member upload — opening BMAN balance (Earning wallet)', '0x818a8c51fcb4f798a13244fcc12a6c0d8e533ed0d3d0f80658a2440a178c6795', NULL, '2026-08-06 17:12:04', '2026-09-05 17:12:04', 0),
(190, 23, 'staking', 4.00000000, 0.00000000, 4.00000000, 'admin_adjustment', 'MBU-20260730-660810D7', 'Bulk member upload — opening BMAN balance (Staking wallet)', '0xd026c38d5242686fee31df0afd44f88c52b9dc0c2f95346cb15a4fe89a6db142', NULL, '2026-08-06 17:12:05', '2026-08-06 17:12:05', 1),
(191, 999999612, 'exchange', 0.00000000, 1.00000000, 3.00000000, 'wallet_transfer', 'WTS-20260806-465D66EE', 'Transfer exchange (internal) [25946003]', NULL, NULL, '2026-08-06 17:13:10', NULL, 0),
(192, 999999612, 'bonus', 1.00000000, 0.00000000, 1.00000000, 'wallet_transfer', 'WTS-20260806-465D66EE', 'Transfer received exchange [25946003]', NULL, NULL, '2026-08-06 17:13:10', '2026-08-06 17:13:10', 1),
(193, 999999612, 'usdt', 0.30000000, 0.00000000, 0.30000000, 'deposit', '34', 'USDT deposit 0.30000000 (BEP-20)', '0x3d58397d37a7067a7f9382ec7e116e1474e444f220623638d5663482f34bbd1d', NULL, '2026-08-06 17:22:47', '2026-08-06 17:22:47', 1),
(194, 999999612, 'usdt', 0.00000000, 0.20000000, 0.10000000, 'swap', 'SWP-20260806-8AFA5518', 'Swap: USDT pending transfer to admin 0x3088B858dc4cD85A001337f8E15a40b24666d321 [SWP-20260806-8AFA5518]', NULL, NULL, '2026-08-06 17:23:37', NULL, 0),
(195, 999999612, 'usdt', 0.00000000, 0.10000000, 0.00000000, 'swap', 'SWP-20260806-9BCD385A', 'Swap: USDT pending transfer to admin 0x3088B858dc4cD85A001337f8E15a40b24666d321 [SWP-20260806-9BCD385A]', NULL, NULL, '2026-08-06 17:24:48', NULL, 0),
(196, 999999612, 'bonus', 0.50000000, 0.00000000, 1.50000000, 'stake_purchase', 'SWP-20260806-8AFA5518', 'Bonus allocation 0.5 BMAN (order 30)', '0x55e0ad3b428365b6804d284df9fac96978264d52a65a69bc91e913ffefbbf90e', NULL, '2026-08-06 17:37:04', '2026-08-06 17:37:04', 1),
(197, 999999612, 'bonus', 0.25000000, 0.00000000, 1.75000000, 'stake_purchase', 'SWP-20260806-9BCD385A', 'Bonus allocation 0.25 BMAN (order 31)', '0x31b0d017ca0d861874e72322f687b55cd9d8f163633b2b154e6c057acd9bd052', NULL, '2026-08-06 17:37:06', '2026-08-06 17:37:06', 1),
(198, 999999611, 'exchange', 4.00000000, 0.00000000, 4.00000000, 'deposit', '35', 'BMAN deposit 4.00000000 (BEP-20)', '0x7e27499ab4e4c63ce5d5430323462ed72c12ce07a63fdb4fb88a799e2f3d83ef', NULL, '2026-08-06 17:53:45', '2026-08-06 17:53:45', 1),
(199, 999999612, 'exchange', 3.00000000, 0.00000000, 6.00000000, 'deposit', '36', 'BMAN deposit 3.00000000 (BEP-20)', '0xf8b298fa9fe11b9d45b0c24b7445e5bc03063e3bd75570504ea6266533b0ff15', NULL, '2026-08-06 18:17:10', '2026-08-06 18:17:10', 1),
(200, 999999612, 'exchange', 1.25000000, 0.00000000, 7.25000000, 'deposit', '37', 'BMAN deposit 1.25000000 (BEP-20)', '0x31b0d017ca0d861874e72322f687b55cd9d8f163633b2b154e6c057acd9bd052', NULL, '2026-08-06 18:17:10', '2026-08-06 18:17:10', 1),
(201, 999999612, 'exchange', 2.50000000, 0.00000000, 9.75000000, 'deposit', '38', 'BMAN deposit 2.50000000 (BEP-20)', '0x55e0ad3b428365b6804d284df9fac96978264d52a65a69bc91e913ffefbbf90e', NULL, '2026-08-06 18:17:10', '2026-08-06 18:17:10', 1),
(202, 999999612, 'exchange', 0.00000000, 0.90000000, 8.85000000, 'stake_purchase', 'RESTAKE-20260807-E5A1B087', 'Re-stake: 0.9000 BMAN from Exchange (Option 2)', NULL, NULL, '2026-08-07 11:05:03', NULL, 0),
(203, 999999612, 'bonus', 0.00000000, 0.10000000, 1.65000000, 'stake_purchase', 'RESTAKE-20260807-E5A1B087', 'Re-stake: 0.1000 BMAN from Bonus (Option 2)', NULL, NULL, '2026-08-07 11:05:03', NULL, 0),
(204, 999999612, 'bonus', 0.25000000, 0.00000000, 1.90000000, 'bonus', 'RESTAKE-20260807-E5A1B087', '25% staking bonus — stake #53', NULL, NULL, '2026-08-07 11:05:03', '2026-08-07 07:35:03', 1),
(205, 999999612, 'exchange', 0.00000000, 0.90000000, 7.95000000, 'stake_purchase', 'RESTAKE-20260807-CBFD0802', 'Re-stake: 0.9000 BMAN from Exchange (Option 2)', NULL, NULL, '2026-08-07 12:51:02', NULL, 0),
(206, 999999612, 'bonus', 0.00000000, 0.10000000, 1.80000000, 'stake_purchase', 'RESTAKE-20260807-CBFD0802', 'Re-stake: 0.1000 BMAN from Bonus (Option 2)', NULL, NULL, '2026-08-07 12:51:02', NULL, 0),
(207, 999999612, 'bonus', 0.25000000, 0.00000000, 2.05000000, 'bonus', 'RESTAKE-20260807-CBFD0802', '25% staking bonus — stake #54', NULL, NULL, '2026-08-07 12:51:02', '2026-08-07 09:21:02', 1),
(208, 999999611, 'exchange', 6.00000000, 0.00000000, 10.00000000, 'admin_adjustment', NULL, 'ZzTestLogin funding', NULL, NULL, '2026-08-07 13:04:36', '2026-08-07 09:34:36', 1),
(209, 999999611, 'earning', 2.00000000, 0.00000000, 2.00000000, 'admin_adjustment', NULL, 'ZzTestLogin funding', NULL, NULL, '2026-08-07 13:04:49', '2026-09-06 09:34:49', 0),
(210, 999999611, 'staking', 2.00000000, 0.00000000, 2.00000000, 'admin_adjustment', NULL, 'ZzTestLogin funding', NULL, NULL, '2026-08-07 13:04:54', '2026-08-07 09:34:54', 1),
(211, 999999611, 'bonus', 2.00000000, 0.00000000, 2.00000000, 'admin_adjustment', NULL, 'ZzTestLogin funding', NULL, NULL, '2026-08-07 13:05:01', '2026-10-06 09:35:01', 0),
(212, 999999611, 'exchange', 0.00000000, 1.00000000, 9.00000000, 'stake_purchase', 'RESTAKE-20260807-44759964', 'Re-stake: 1.0000 BMAN from Exchange (Option 2)', NULL, NULL, '2026-08-07 13:07:45', NULL, 0),
(213, 999999611, 'bonus', 0.25000000, 0.00000000, 2.25000000, 'bonus', 'RESTAKE-20260807-44759964', '25% staking bonus — stake #55', NULL, NULL, '2026-08-07 13:07:45', '2026-08-07 09:37:45', 1),
(214, 999999611, 'exchange', 0.00000000, 0.90000000, 8.10000000, 'stake_purchase', 'RESTAKE-20260807-B4075CC7', 'Re-stake: 0.9000 BMAN from Exchange (Option 3)', NULL, NULL, '2026-08-07 13:08:11', NULL, 0),
(215, 999999611, 'bonus', 0.00000000, 0.10000000, 2.15000000, 'stake_purchase', 'RESTAKE-20260807-B4075CC7', 'Re-stake: 0.1000 BMAN from Bonus (Option 3)', NULL, NULL, '2026-08-07 13:08:11', NULL, 0),
(216, 999999611, 'bonus', 0.25000000, 0.00000000, 2.40000000, 'bonus', 'RESTAKE-20260807-B4075CC7', '25% staking bonus — stake #56', NULL, NULL, '2026-08-07 13:08:11', '2026-08-07 09:38:11', 1),
(217, 999999611, 'exchange', 0.00000000, 0.80000000, 7.30000000, 'stake_purchase', 'RESTAKE-20260807-40E73E44', 'Re-stake: 0.8000 BMAN from Exchange (Option 4)', NULL, NULL, '2026-08-07 13:08:38', NULL, 0),
(218, 999999611, 'earning', 0.00000000, 0.10000000, 1.90000000, 'stake_purchase', 'RESTAKE-20260807-40E73E44', 'Re-stake: 0.1000 BMAN from Earning (Option 4)', NULL, NULL, '2026-08-07 13:08:38', NULL, 0),
(219, 999999611, 'bonus', 0.00000000, 0.10000000, 2.30000000, 'stake_purchase', 'RESTAKE-20260807-40E73E44', 'Re-stake: 0.1000 BMAN from Bonus (Option 4)', NULL, NULL, '2026-08-07 13:08:38', NULL, 0),
(220, 999999611, 'bonus', 0.25000000, 0.00000000, 2.55000000, 'bonus', 'RESTAKE-20260807-40E73E44', '25% staking bonus — stake #57', NULL, NULL, '2026-08-07 13:08:38', '2026-08-07 09:38:38', 1),
(221, 999999611, 'exchange', 0.00000000, 0.80000000, 6.50000000, 'stake_purchase', 'RESTAKE-20260807-189C7B04', 'Re-stake: 0.8000 BMAN from Exchange (Option 5)', NULL, NULL, '2026-08-07 13:09:00', NULL, 0),
(222, 999999611, 'earning', 0.00000000, 0.10000000, 1.80000000, 'stake_purchase', 'RESTAKE-20260807-189C7B04', 'Re-stake: 0.1000 BMAN from Earning (Option 5)', NULL, NULL, '2026-08-07 13:09:00', NULL, 0),
(223, 999999611, 'staking', 0.00000000, 0.10000000, 1.90000000, 'stake_purchase', 'RESTAKE-20260807-189C7B04', 'Re-stake: 0.1000 BMAN from Staking (Option 5)', NULL, NULL, '2026-08-07 13:09:00', NULL, 0),
(224, 999999611, 'bonus', 0.25000000, 0.00000000, 2.80000000, 'bonus', 'RESTAKE-20260807-189C7B04', '25% staking bonus — stake #58', NULL, NULL, '2026-08-07 13:09:00', '2026-08-07 09:39:00', 1),
(225, 999999611, 'exchange', 0.00000000, 0.90000000, 5.60000000, 'stake_purchase', 'RESTAKE-20260807-190BA688', 'Re-stake: 0.9000 BMAN from Exchange (Option 6)', NULL, NULL, '2026-08-07 13:09:25', NULL, 0),
(226, 999999611, 'earning', 0.00000000, 0.10000000, 1.70000000, 'stake_purchase', 'RESTAKE-20260807-190BA688', 'Re-stake: 0.1000 BMAN from Earning (Option 6)', NULL, NULL, '2026-08-07 13:09:25', NULL, 0),
(227, 999999611, 'bonus', 0.25000000, 0.00000000, 3.05000000, 'bonus', 'RESTAKE-20260807-190BA688', '25% staking bonus — stake #59', NULL, NULL, '2026-08-07 13:09:25', '2026-08-07 09:39:25', 1),
(228, 999999611, 'exchange', 0.00000000, 0.90000000, 4.70000000, 'stake_purchase', 'RESTAKE-20260807-3E8D8DEA', 'Re-stake: 0.9000 BMAN from Exchange (Option 7)', NULL, NULL, '2026-08-07 13:09:46', NULL, 0),
(229, 999999611, 'staking', 0.00000000, 0.10000000, 1.80000000, 'stake_purchase', 'RESTAKE-20260807-3E8D8DEA', 'Re-stake: 0.1000 BMAN from Staking (Option 7)', NULL, NULL, '2026-08-07 13:09:46', NULL, 0),
(230, 999999611, 'bonus', 0.25000000, 0.00000000, 3.30000000, 'bonus', 'RESTAKE-20260807-3E8D8DEA', '25% staking bonus — stake #60', NULL, NULL, '2026-08-07 13:09:46', '2026-08-07 09:39:46', 1),
(231, 999999611, 'exchange', 0.00000000, 0.70000000, 4.00000000, 'stake_purchase', 'RESTAKE-20260807-DEC9854E', 'Re-stake: 0.7000 BMAN from Exchange (Option 8)', NULL, NULL, '2026-08-07 13:10:19', NULL, 0),
(232, 999999611, 'earning', 0.00000000, 0.10000000, 1.60000000, 'stake_purchase', 'RESTAKE-20260807-DEC9854E', 'Re-stake: 0.1000 BMAN from Earning (Option 8)', NULL, NULL, '2026-08-07 13:10:19', NULL, 0),
(233, 999999611, 'staking', 0.00000000, 0.10000000, 1.70000000, 'stake_purchase', 'RESTAKE-20260807-DEC9854E', 'Re-stake: 0.1000 BMAN from Staking (Option 8)', NULL, NULL, '2026-08-07 13:10:19', NULL, 0),
(234, 999999611, 'bonus', 0.00000000, 0.10000000, 3.20000000, 'stake_purchase', 'RESTAKE-20260807-DEC9854E', 'Re-stake: 0.1000 BMAN from Bonus (Option 8)', NULL, NULL, '2026-08-07 13:10:19', NULL, 0),
(235, 999999611, 'bonus', 0.25000000, 0.00000000, 3.45000000, 'bonus', 'RESTAKE-20260807-DEC9854E', '25% staking bonus — stake #61', NULL, NULL, '2026-08-07 13:10:19', '2026-08-07 09:40:19', 1),
(236, 999999608, 'earning', 0.16000000, 0.00000000, 0.16000000, 'binary_matching', 'MB-20260807-094332-C8E156', 'Binary matching 8% on 2 matched BV', NULL, NULL, '2026-08-07 13:13:32', '2026-08-07 09:43:32', 1),
(237, 999999608, 'staking', 0.04000000, 0.00000000, 0.04000000, 'binary_matching', 'MB-20260807-094332-C8E156', 'Binary matching 2% on 2 matched BV', NULL, NULL, '2026-08-07 13:13:32', '2026-08-07 09:43:32', 1),
(238, 999999612, 'exchange', 0.00000000, 1.80000000, 6.15000000, 'stake_purchase', 'RESTAKE-20260807-F7CAD0A9', 'Re-stake: 1.8000 BMAN from Exchange (Option 3)', NULL, NULL, '2026-08-07 13:29:34', NULL, 0),
(239, 999999612, 'bonus', 0.00000000, 0.20000000, 1.85000000, 'stake_purchase', 'RESTAKE-20260807-F7CAD0A9', 'Re-stake: 0.2000 BMAN from Bonus (Option 3)', NULL, NULL, '2026-08-07 13:29:34', NULL, 0),
(240, 999999612, 'bonus', 0.50000000, 0.00000000, 2.35000000, 'bonus', 'RESTAKE-20260807-F7CAD0A9', '25% staking bonus — stake #62', NULL, NULL, '2026-08-07 13:29:34', '2026-08-07 09:59:34', 1),
(241, 999999606, 'usdt', 0.20000000, 0.00000000, 0.20000000, 'deposit', '39', 'USDT deposit 0.20000000 (BEP-20)', '0x6a63e130467681d2eaeb16dcc9cf9c391c6acb4df80ef8f282dc9180ee70ab58', NULL, '2026-08-07 13:34:37', '2026-08-07 10:04:37', 1),
(242, 999999606, 'usdt', 0.00000000, 0.10000000, 0.10000000, 'swap', 'SWP-20260807-E710F40C', 'Swap: USDT pending transfer to admin 0x3088B858dc4cD85A001337f8E15a40b24666d321 [SWP-20260807-E710F40C]', NULL, NULL, '2026-08-07 13:38:02', NULL, 0),
(243, 999999606, 'bonus', 0.25000000, 0.00000000, 0.25000000, 'stake_purchase', 'SWP-20260807-E710F40C', 'Bonus allocation 0.25 BMAN (order 32)', '0x4f46ebd6d76121a1ac3ee2e7a2e4643d2c0b0a92d9a3b4b722ad51decbe72eaf', NULL, '2026-08-07 13:42:00', '2026-08-07 10:12:00', 1),
(244, 999999606, 'exchange', 2.00000000, 0.00000000, 2.00000000, 'deposit', '40', 'BMAN deposit 2.00000000 (BEP-20)', '0xb2cd662d98f6ee865706d3c0e8308015674501d0d709659193c7af01c0e6c93e', NULL, '2026-08-07 13:47:26', '2026-08-07 10:17:26', 1),
(245, 999999606, 'exchange', 0.10000000, 0.00000000, 2.10000000, 'deposit', '41', 'BMAN deposit 0.10000000 (BEP-20)', '0x2ab5a3a69903d683be0fc695d5e52154e9f276cef5da6bcba3303ff0a656bb58', NULL, '2026-08-07 13:47:26', '2026-08-07 10:17:26', 1),
(246, 999999606, 'exchange', 1.25000000, 0.00000000, 3.35000000, 'deposit', '42', 'BMAN deposit 1.25000000 (BEP-20)', '0x4f46ebd6d76121a1ac3ee2e7a2e4643d2c0b0a92d9a3b4b722ad51decbe72eaf', NULL, '2026-08-07 13:47:26', '2026-08-07 10:17:26', 1),
(247, 999999608, 'exchange', 2.00000000, 0.00000000, 2.00000000, 'admin_adjustment', NULL, 'ZzTestLogin funding', NULL, NULL, '2026-08-07 13:53:44', '2026-08-07 10:23:44', 1),
(248, 999999608, 'exchange', 0.00000000, 1.00000000, 1.00000000, 'stake_purchase', 'RESTAKE-20260807-0D4183FE', 'Re-stake: 1.0000 BMAN from Exchange (Option 2)', NULL, NULL, '2026-08-07 13:54:07', NULL, 0),
(249, 999999608, 'bonus', 0.25000000, 0.00000000, 0.50000000, 'bonus', 'RESTAKE-20260807-0D4183FE', '25% staking bonus — stake #64', NULL, NULL, '2026-08-07 13:54:07', '2026-08-07 10:24:07', 1),
(250, 999999606, 'exchange', 0.00000000, 1.80000000, 1.55000000, 'stake_purchase', 'RESTAKE-20260807-23FBA38B', 'Re-stake: 1.8000 BMAN from Exchange (Option 3)', NULL, NULL, '2026-08-07 13:58:08', NULL, 0),
(251, 999999606, 'bonus', 0.00000000, 0.20000000, 0.05000000, 'stake_purchase', 'RESTAKE-20260807-23FBA38B', 'Re-stake: 0.2000 BMAN from Bonus (Option 3)', NULL, NULL, '2026-08-07 13:58:08', NULL, 0),
(252, 999999606, 'bonus', 0.50000000, 0.00000000, 0.55000000, 'bonus', 'RESTAKE-20260807-23FBA38B', '25% staking bonus — stake #65', NULL, NULL, '2026-08-07 13:58:08', '2026-08-07 10:28:08', 1),
(253, 3, 'exchange', 0.03000000, 0.00000000, 5.23000000, 'roi', 'ORDER-2-ROI', 'Monthly ROI 1/60 — 0.03 BMAN (order 2) [DRY-RUN]', 'DRYRUN-ROI-ORDER-2-ROI-M1', NULL, '2026-08-07 16:44:56', '2026-08-07 13:14:56', 1),
(254, 999999604, 'exchange', 0.01500000, 0.00000000, 0.01500000, 'roi', 'ORDER-27-ROI', 'Monthly ROI 1/60 — 0.015 BMAN (order 27) [DRY-RUN]', 'DRYRUN-ROI-ORDER-27-ROI-M1', NULL, '2026-08-07 16:44:56', '2026-08-07 13:14:56', 1),
(255, 999999602, 'exchange', 1.00000000, 0.00000000, 2.25000000, 'admin_adjustment', NULL, 'ZzTestLogin funding', NULL, NULL, '2026-08-07 17:22:58', '2026-08-07 13:52:58', 1),
(256, 999999602, 'exchange', 0.00000000, 1.00000000, 1.25000000, 'stake_purchase', 'RESTAKE-20260807-70988204', 'Re-stake: 1.0000 BMAN from Exchange (Option 2)', NULL, NULL, '2026-08-07 17:23:26', NULL, 0),
(257, 999999602, 'bonus', 0.25000000, 0.00000000, 0.25000000, 'bonus', 'RESTAKE-20260807-70988204', '25% staking bonus — stake #66', NULL, NULL, '2026-08-07 17:23:26', '2026-08-07 13:53:26', 1),
(258, 999999602, 'exchange', 0.00766666, 0.00000000, 1.25766666, 'roi', 'RESTAKE-20260807-70988204-ROI', 'Monthly ROI day 7 (cycle 1/24) — 0.00766666 BMAN (order ) [DRY-RUN]', 'DRYRUN-ROI-RESTAKE-20260807-70988204-ROI-M1-D7', NULL, '2026-08-07 17:24:44', '2026-08-07 13:54:44', 1),
(259, 999999602, 'exchange', 0.00766666, 0.00000000, 1.26533332, 'roi', 'RESTAKE-20260807-70988204-ROI', 'Monthly ROI day 8 (cycle 1/24) — 0.00766666 BMAN (order ) [DRY-RUN]', 'DRYRUN-ROI-RESTAKE-20260807-70988204-ROI-M1-D8', NULL, '2026-08-07 17:26:12', '2026-08-07 13:56:12', 1),
(260, 999999602, 'exchange', 0.00766668, 0.00000000, 1.27300000, 'roi', 'RESTAKE-20260807-70988204-ROI', 'Monthly ROI day 9 (cycle 1/24) — 0.00766668 BMAN (order ) [DRY-RUN]', 'DRYRUN-ROI-RESTAKE-20260807-70988204-ROI-M1-D9', NULL, '2026-08-07 17:26:12', '2026-08-07 13:56:12', 1),
(261, 999999602, 'exchange', 0.00766666, 0.00000000, 1.28066666, 'roi', 'RESTAKE-20260807-70988204-ROI', 'Monthly ROI day 7 (cycle 2/24) — 0.00766666 BMAN (order ) [DRY-RUN]', 'DRYRUN-ROI-RESTAKE-20260807-70988204-ROI-M2-D7', NULL, '2026-08-07 17:45:06', '2026-08-07 14:15:06', 1),
(262, 999999602, 'exchange', 1.00000000, 0.00000000, 2.28066666, 'admin_adjustment', NULL, 'ZzTestLogin funding', NULL, NULL, '2026-08-07 17:45:32', '2026-08-07 14:15:32', 1),
(263, 999999602, 'exchange', 0.00000000, 1.00000000, 1.28066666, 'stake_purchase', 'RESTAKE-20260807-21BBF520', 'Re-stake: 1.0000 BMAN from Exchange (Option 2)', NULL, NULL, '2026-08-07 17:45:57', NULL, 0),
(264, 999999602, 'bonus', 0.25000000, 0.00000000, 0.50000000, 'bonus', 'RESTAKE-20260807-21BBF520', '25% staking bonus — stake #67', NULL, NULL, '2026-08-07 17:45:57', '2026-08-07 14:15:57', 1),
(265, 999999607, 'exchange', 1.00000000, 0.00000000, 1.00000000, 'admin_adjustment', NULL, 'ZzTestLogin funding', NULL, NULL, '2026-08-07 17:53:20', '2026-08-07 14:23:20', 1),
(266, 999999607, 'exchange', 0.00000000, 1.00000000, 0.00000000, 'stake_purchase', 'RESTAKE-20260807-A3F584EE', 'Re-stake: 1.0000 BMAN from Exchange (Option 2)', NULL, NULL, '2026-08-07 17:53:51', NULL, 0),
(267, 999999607, 'bonus', 0.25000000, 0.00000000, 0.75000000, 'bonus', 'RESTAKE-20260807-A3F584EE', '25% staking bonus — stake #68', NULL, NULL, '2026-08-07 17:53:51', '2026-08-07 14:23:51', 1),
(268, 999999607, 'exchange', 0.00500000, 0.00000000, 0.00500000, 'roi', 'RESTAKE-20260807-A3F584EE-ROI', 'Monthly ROI day 7 (cycle 1/60) — 0.005 BMAN (order ) [DRY-RUN]', 'DRYRUN-ROI-RESTAKE-20260807-A3F584EE-ROI-M1-D7', NULL, '2026-08-07 17:54:51', '2026-08-07 14:24:51', 1),
(269, 999999613, 'usdt', 0.20000000, 0.00000000, 0.20000000, 'deposit', '43', 'USDT deposit 0.20000000 (BEP-20)', '0x340bdd28e002995e4e22ac56d4683436ca2c8573f2ddd7f23993401697701fd4', NULL, '2026-08-07 18:11:37', '2026-08-07 14:41:37', 1),
(270, 999999613, 'usdt', 0.00000000, 0.20000000, 0.00000000, 'swap', 'SWP-20260807-EF6ABE55', 'Swap: USDT pending transfer to admin 0x3088B858dc4cD85A001337f8E15a40b24666d321 [SWP-20260807-EF6ABE55]', NULL, NULL, '2026-08-07 18:13:23', NULL, 0),
(271, 999999613, 'bonus', 0.40000000, 0.00000000, 0.40000000, 'stake_purchase', 'SWP-20260807-EF6ABE55', 'Bonus allocation 0.4 BMAN (order 33)', '0x50f86adb9f9f9fde6e1928d75ccc915be0fc182049a891665dd372af96cc5afb', NULL, '2026-08-07 18:14:43', '2026-08-07 14:44:43', 1),
(272, 999999602, 'exchange', 2.00000000, 0.00000000, 3.28066666, 'admin_adjustment', NULL, 'ZzTestLogin funding', NULL, NULL, '2026-08-07 18:29:40', '2026-08-07 14:59:40', 1),
(273, 999999602, 'exchange', 0.00000000, 2.00000000, 1.28066666, 'stake_purchase', 'RESTAKE-20260807-DA3DD430', 'Re-stake: 2.0000 BMAN from Exchange (Option 2)', NULL, NULL, '2026-08-07 18:30:05', NULL, 0),
(274, 999999602, 'bonus', 0.40000000, 0.00000000, 0.90000000, 'bonus', 'RESTAKE-20260807-DA3DD430', '20% staking bonus — stake #70', NULL, NULL, '2026-08-07 18:30:05', '2026-08-07 15:00:05', 1),
(275, 999999603, 'exchange', 1.00000000, 0.00000000, 1.00000000, 'admin_adjustment', NULL, 'ZzTestLogin funding', NULL, NULL, '2026-08-07 19:52:24', '2026-08-07 19:52:24', 1),
(276, 999999603, 'exchange', 0.00000000, 1.00000000, 0.00000000, 'stake_purchase', 'RESTAKE-20260807-D7676BA5', 'Re-stake: 1.0000 BMAN from Exchange (Option 2)', NULL, NULL, '2026-08-07 19:52:53', NULL, 0),
(277, 999999603, 'bonus', 0.25000000, 0.00000000, 0.25000000, 'bonus', 'RESTAKE-20260807-D7676BA5', '25% staking bonus — stake #71', NULL, NULL, '2026-08-07 19:52:53', '2026-08-07 19:52:53', 1),
(278, 999999616, 'usdt', 0.20000000, 0.00000000, 0.20000000, 'deposit', '44', 'USDT deposit 0.20000000 (BEP-20)', '0x8b66252e51f0d30ce4f1e5a9c048dc31b90dda6f7950712a9f61c8119f21a582', NULL, '2026-08-07 20:17:42', '2026-08-07 20:17:42', 1),
(279, 999999616, 'usdt', 0.00000000, 0.20000000, 0.00000000, 'swap', 'SWP-20260807-91A3AB87', 'Swap: USDT pending transfer to admin 0x3088B858dc4cD85A001337f8E15a40b24666d321 [SWP-20260807-91A3AB87]', NULL, NULL, '2026-08-07 20:18:28', NULL, 0),
(280, 999999602, 'exchange', 0.00766666, 0.00000000, 1.28833332, 'roi', 'RESTAKE-20260807-70988204-ROI', 'Monthly ROI day 8 (cycle 2/24) — 0.00766666 BMAN (order )', '0xd64cb1a90b860a9be1974e768ffa2afbc713560040d89dbea444361fd92d40a7', NULL, '2026-08-08 11:57:31', '2026-08-08 11:57:31', 1),
(281, 999999607, 'exchange', 0.00500000, 0.00000000, 0.01000000, 'roi', 'RESTAKE-20260807-A3F584EE-ROI', 'Monthly ROI day 8 (cycle 1/60) — 0.005 BMAN (order )', '0xe0762834009c2897859ecf16ba633093bfa549787278bfd60c1f42ce43b82382', NULL, '2026-08-08 11:57:49', '2026-08-08 11:57:49', 1),
(282, 999999603, 'exchange', 0.00383333, 0.00000000, 0.00383333, 'roi', 'RESTAKE-20260807-D7676BA5-ROI', 'Monthly ROI day 7 (cycle 1/24) — 0.00383333 BMAN (order )', '0x86319157cba2464e509bc5fc6f7c4eeec7efae3ba409e0d2379a82d4919381ef', NULL, '2026-08-08 11:58:03', '2026-08-08 11:58:03', 1),
(283, 999999616, 'exchange', 0.00833333, 0.00000000, 0.00833333, 'roi', 'ORDER-34-ROI', 'Monthly ROI day 7 (cycle 1/60) — 0.00833333 BMAN (order 34)', '0x19938244ec6941425ce5e2490df527c869c1499f95d838f6dce807b54ce0626a', NULL, '2026-08-08 11:58:11', '2026-08-08 11:58:11', 1);

-- --------------------------------------------------------

--
-- Table structure for table `wallet_settlement_cron_state`
--

CREATE TABLE `wallet_settlement_cron_state` (
  `job` varchar(40) NOT NULL,
  `running` tinyint(1) NOT NULL DEFAULT 0,
  `heartbeat` datetime DEFAULT NULL,
  `last_run_at` datetime DEFAULT NULL,
  `last_result` varchar(255) DEFAULT NULL,
  `total_settled` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallet_settlement_cron_state`
--

INSERT INTO `wallet_settlement_cron_state` (`job`, `running`, `heartbeat`, `last_run_at`, `last_result`, `total_settled`) VALUES
('member_bulk_bman', 0, '2026-08-07 04:12:03', '2026-08-07 04:12:03', 'Processed 0, sent 0, failed 0', 6),
('wallet_transfer_settlement', 0, '2026-08-07 04:57:02', '2026-08-07 04:57:02', 'Processed 0, settled 0, failed 0', 3);

-- --------------------------------------------------------

--
-- Table structure for table `wallet_sync_cursor`
--

CREATE TABLE `wallet_sync_cursor` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `last_user_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `batch_size` int(10) UNSIGNED NOT NULL DEFAULT 200,
  `cycle_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `worker_id` varchar(40) DEFAULT NULL,
  `locked_at` datetime DEFAULT NULL,
  `last_run_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallet_sync_cursor`
--

INSERT INTO `wallet_sync_cursor` (`id`, `last_user_id`, `batch_size`, `cycle_count`, `worker_id`, `locked_at`, `last_run_at`, `updated_at`) VALUES
(1, 999999504, 200, 0, 'w-e686823f', '2026-08-06 10:04:06', '2026-08-06 10:04:06', '2026-08-06 13:34:06');

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tx_type` enum('earn','bonus','withdraw') NOT NULL DEFAULT 'earn',
  `source` varchar(50) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','completed','rejected') NOT NULL DEFAULT 'completed',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transfer_audit`
--

CREATE TABLE `wallet_transfer_audit` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transfer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ref` varchar(40) DEFAULT NULL,
  `action` varchar(30) NOT NULL,
  `mode` varchar(20) DEFAULT NULL,
  `via` varchar(10) DEFAULT NULL,
  `actor_type` varchar(10) DEFAULT NULL,
  `actor_id` int(11) DEFAULT NULL,
  `source_user_id` int(11) DEFAULT NULL,
  `recipient_id` int(11) DEFAULT NULL,
  `from_wallet` varchar(20) DEFAULT NULL,
  `to_wallet` varchar(20) DEFAULT NULL,
  `amount` decimal(30,8) DEFAULT NULL,
  `result_code` varchar(40) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `request_id` varchar(64) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallet_transfer_audit`
--

INSERT INTO `wallet_transfer_audit` (`id`, `transfer_id`, `ref`, `action`, `mode`, `via`, `actor_type`, `actor_id`, `source_user_id`, `recipient_id`, `from_wallet`, `to_wallet`, `amount`, `result_code`, `message`, `ip_address`, `user_agent`, `request_id`, `created_at`) VALUES
(1, NULL, NULL, 'validated', 'member', 'user', 'user', NULL, 3, NULL, 'exchange', '', 1.00000000, 'ok', '', '192.168.29.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', NULL, '2026-07-20 22:32:46'),
(2, 1, 'WTS-20260720-30109EC8', 'executed', 'member', 'user', 'user', NULL, 3, NULL, 'exchange', '', 1.00000000, 'ok', 'transfer completed', '192.168.29.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', NULL, '2026-07-20 22:32:46'),
(3, NULL, NULL, 'rejected', 'member', 'user', 'user', NULL, 1, NULL, 'exchange', '', 0.50000000, 'transfer_password', 'Incorrect transfer password.', '192.168.29.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-21 19:42:23'),
(4, NULL, NULL, 'rejected', 'member', 'user', 'user', NULL, 1, NULL, 'exchange', '', 0.50000000, 'transfer_password', 'Incorrect transfer password.', '192.168.29.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-21 19:42:36'),
(5, NULL, NULL, 'validated', 'member', 'user', 'user', NULL, 1, NULL, 'exchange', '', 0.50000000, 'ok', '', '192.168.29.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-21 19:42:50'),
(6, 2, 'WTS-20260721-2B2A19D7', 'executed', 'member', 'user', 'user', NULL, 1, NULL, 'exchange', '', 0.50000000, 'ok', 'transfer completed', '192.168.29.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-21 19:42:50'),
(7, NULL, NULL, 'rejected', 'member', 'user', 'user', NULL, 1, NULL, 'exchange', '', 2.00000000, 'transfer_password', 'Incorrect transfer password.', '192.168.29.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-23 17:16:22'),
(8, NULL, NULL, 'validated', 'member', 'user', 'user', NULL, 1, NULL, 'exchange', '', 2.00000000, 'ok', '', '192.168.29.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-23 17:16:38'),
(9, 3, 'WTS-20260723-7C148395', 'executed', 'member', 'user', 'user', NULL, 1, NULL, 'exchange', '', 2.00000000, 'ok', 'transfer completed', '192.168.29.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-23 17:16:38'),
(10, NULL, NULL, 'validated', 'internal', 'user', 'user', NULL, 1, NULL, 'exchange', 'bonus', 1.00000000, 'ok', '', '192.168.29.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-23 17:29:53'),
(11, 4, 'WTS-20260723-898A1F3B', 'executed', 'internal', 'user', 'user', NULL, 1, NULL, 'exchange', 'bonus', 1.00000000, 'ok', 'transfer completed', '192.168.29.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-23 17:29:53'),
(12, NULL, NULL, 'validated', 'internal', 'user', 'user', NULL, 999999612, NULL, 'exchange', 'bonus', 1.00000000, 'ok', '', '2409:40f4:143:cef5:191a:dc6c:def7:868e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-08-06 17:13:10'),
(13, 5, 'WTS-20260806-465D66EE', 'executed', 'internal', 'user', 'user', NULL, 999999612, NULL, 'exchange', 'bonus', 1.00000000, 'ok', 'transfer completed', '2409:40f4:143:cef5:191a:dc6c:def7:868e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-08-06 17:13:10');

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transfer_settlement_settings`
--

CREATE TABLE `wallet_transfer_settlement_settings` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'master switch — 0 = behave exactly as before this migration',
  `dry_run` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = record a DRYRUN- hash, never broadcast (mirrors token_settings.swap_dry_run, kept separate on purpose)',
  `settle_self_transfers` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'a member moving between their OWN 4 wallets',
  `settle_member_transfers` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'user A -> user B',
  `min_treasury_reserve` decimal(30,8) NOT NULL DEFAULT 0.00000000 COMMENT 'cron refuses to settle if it would drop the Treasury on-chain BMAN balance below this',
  `max_batch_size` int(11) NOT NULL DEFAULT 20 COMMENT 'settlements processed per cron invocation',
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallet_transfer_settlement_settings`
--

INSERT INTO `wallet_transfer_settlement_settings` (`id`, `enabled`, `dry_run`, `settle_self_transfers`, `settle_member_transfers`, `min_treasury_reserve`, `max_batch_size`, `updated_by`, `updated_at`) VALUES
(1, 1, 0, 0, 1, 0.00000000, 20, NULL, '2026-07-21 19:41:21');

-- --------------------------------------------------------

--
-- Table structure for table `wallet_withdraw_holds`
--

CREATE TABLE `wallet_withdraw_holds` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `request_id` bigint(20) UNSIGNED NOT NULL,
  `wallet_type` enum('exchange','earning','staking','bonus') NOT NULL,
  `hold_amount` decimal(18,8) NOT NULL DEFAULT 0.00000000,
  `released_amount` decimal(18,8) NOT NULL DEFAULT 0.00000000,
  `status` enum('held','released','consumed') NOT NULL DEFAULT 'held',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `released_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `withdrawals`
--

CREATE TABLE `withdrawals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `payout_id` varchar(64) DEFAULT NULL,
  `admin_txn_id` varchar(64) DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `fee` decimal(18,2) NOT NULL DEFAULT 0.00,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `method` varchar(20) DEFAULT NULL,
  `type` varchar(20) DEFAULT 'MANUAL',
  `period` varchar(50) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `admin_proof_img` varchar(255) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `net_amount` varchar(100) NOT NULL,
  `admin_review` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `tx_hash` varchar(120) DEFAULT NULL,
  `chain_status` enum('created','broadcasting','pending','confirmed','failed','reverted','cancelled') DEFAULT 'created',
  `block_number` bigint(20) UNSIGNED DEFAULT NULL,
  `confirmations` int(11) DEFAULT NULL,
  `gas_used` bigint(20) DEFAULT NULL,
  `gas_price` decimal(38,0) DEFAULT NULL,
  `gas_fee_total` decimal(38,18) DEFAULT NULL,
  `nonce` bigint(20) DEFAULT NULL,
  `explorer_url` varchar(255) DEFAULT NULL,
  `failure_reason` varchar(160) DEFAULT NULL,
  `onchain_tx_id` bigint(20) UNSIGNED DEFAULT NULL,
  `wallet_ledger_id` bigint(20) UNSIGNED DEFAULT NULL,
  `broadcast_at` datetime DEFAULT NULL,
  `balance_debited` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `withdraw_audit_log`
--

CREATE TABLE `withdraw_audit_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `request_id` bigint(20) UNSIGNED NOT NULL,
  `admin_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `old_status` varchar(30) DEFAULT NULL,
  `new_status` varchar(30) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `withdraw_audit_log`
--

INSERT INTO `withdraw_audit_log` (`id`, `request_id`, `admin_id`, `action`, `old_status`, `new_status`, `remarks`, `created_at`) VALUES
(1, 1, 0, 'user_request', NULL, 'pending', 'User created withdrawal request', '2026-07-23 16:01:18'),
(2, 2, 0, 'user_request', NULL, 'pending', 'User created withdrawal request', '2026-08-06 17:26:23'),
(3, 2, 1, 'admin_approve', 'pending', 'approved', '', '2026-08-06 17:28:30'),
(4, 2, 1, 'admin_processing', 'approved', 'processing', '', '2026-08-06 17:32:22');

-- --------------------------------------------------------

--
-- Table structure for table `withdraw_request`
--

CREATE TABLE `withdraw_request` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `request_id` varchar(64) DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `fee` decimal(18,2) NOT NULL DEFAULT 0.00,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `method` varchar(20) DEFAULT NULL,
  `type` varchar(20) DEFAULT 'MANUAL',
  `period` varchar(50) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure for view `vw_user_ranks`
--
DROP TABLE IF EXISTS `vw_user_ranks`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `vw_user_ranks`  AS SELECT `u`.`id` AS `user_id`, `u`.`username` AS `username`, `u`.`email` AS `email`, `u`.`status` AS `user_status`, `ur`.`current_rank_id` AS `current_rank_id`, `cr`.`name` AS `current_rank`, `cr`.`tier_level` AS `current_tier`, `ur`.`highest_rank_id` AS `highest_rank_id`, `hr`.`name` AS `highest_rank`, `hr`.`tier_level` AS `highest_tier`, `hr`.`badge_image` AS `badge_image`, `hr`.`badge_color` AS `badge_color`, `ur`.`group_volume` AS `group_volume`, `ur`.`achieved_at` AS `achieved_at`, `ur`.`updated_at` AS `updated_at` FROM (((`users` `u` left join `user_ranks` `ur` on(`ur`.`user_id` = `u`.`id`)) left join `staking_ranks` `cr` on(`cr`.`id` = `ur`.`current_rank_id`)) left join `staking_ranks` `hr` on(`hr`.`id` = `ur`.`highest_rank_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_user_rank_power`
--
DROP TABLE IF EXISTS `vw_user_rank_power`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `vw_user_rank_power`  AS SELECT `p`.`id` AS `id`, `p`.`user_id` AS `user_id`, `p`.`cycle_id` AS `cycle_id`, `c`.`cycle_no` AS `cycle_no`, `c`.`start_date` AS `start_date`, `c`.`end_date` AS `end_date`, `c`.`status` AS `cycle_status`, `p`.`power_rank_id` AS `power_rank_id`, `r`.`tier_level` AS `power_tier`, `r`.`name` AS `power_rank`, `p`.`left_volume` AS `left_volume`, `p`.`right_volume` AS `right_volume`, `p`.`total_volume` AS `total_volume`, `p`.`qualified` AS `qualified`, `p`.`achieved_at` AS `achieved_at`, `p`.`calculated_at` AS `calculated_at` FROM ((`user_rank_power` `p` join `staking_rank_power_cycles` `c` on(`c`.`id` = `p`.`cycle_id`)) left join `staking_ranks` `r` on(`r`.`id` = `p`.`power_rank_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `v_bman_user_available`
--
DROP TABLE IF EXISTS `v_bman_user_available`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `v_bman_user_available`  AS SELECT `v_bman_wallet_balances`.`user_id` AS `user_id`, sum(`v_bman_wallet_balances`.`available`) AS `available` FROM `v_bman_wallet_balances` GROUP BY `v_bman_wallet_balances`.`user_id` ;

-- --------------------------------------------------------

--
-- Structure for view `v_bman_wallet_balances`
--
DROP TABLE IF EXISTS `v_bman_wallet_balances`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `v_bman_wallet_balances`  AS SELECT `uw`.`user_id` AS `user_id`, 'exchange' AS `wallet`, `uw`.`exchange_balance` AS `total`, coalesce(`locked`.`amt`,0) AS `locked`, greatest(`uw`.`exchange_balance` - coalesce(`locked`.`amt`,0),0) AS `matured`, coalesce(`held`.`amt`,0) AS `holds`, greatest(greatest(`uw`.`exchange_balance` - coalesce(`locked`.`amt`,0),0) - coalesce(`held`.`amt`,0),0) AS `available` FROM ((`user_wallets` `uw` left join (select `wallet_ledger`.`user_id` AS `user_id`,sum(`wallet_ledger`.`credit`) AS `amt` from `wallet_ledger` where `wallet_ledger`.`wallet_type` = 'exchange' and `wallet_ledger`.`is_matured` = 0 and `wallet_ledger`.`credit` > 0 group by `wallet_ledger`.`user_id`) `locked` on(`locked`.`user_id` = `uw`.`user_id`)) left join (select `bman_wallet_ledger`.`user_id` AS `user_id`,sum(`bman_wallet_ledger`.`amount`) AS `amt` from `bman_wallet_ledger` where `bman_wallet_ledger`.`wallet` = 'exchange' and `bman_wallet_ledger`.`entry_type` in ('lock','debit') and `bman_wallet_ledger`.`status` = 'active' group by `bman_wallet_ledger`.`user_id`) `held` on(`held`.`user_id` = `uw`.`user_id`))union all select `uw`.`user_id` AS `user_id`,'earning' AS `wallet`,`uw`.`earning_balance` AS `total`,coalesce(`locked`.`amt`,0) AS `locked`,greatest(`uw`.`earning_balance` - coalesce(`locked`.`amt`,0),0) AS `matured`,coalesce(`held`.`amt`,0) AS `holds`,greatest(greatest(`uw`.`earning_balance` - coalesce(`locked`.`amt`,0),0) - coalesce(`held`.`amt`,0),0) AS `available` from ((`user_wallets` `uw` left join (select `wallet_ledger`.`user_id` AS `user_id`,sum(`wallet_ledger`.`credit`) AS `amt` from `wallet_ledger` where `wallet_ledger`.`wallet_type` = 'earning' and `wallet_ledger`.`is_matured` = 0 and `wallet_ledger`.`credit` > 0 group by `wallet_ledger`.`user_id`) `locked` on(`locked`.`user_id` = `uw`.`user_id`)) left join (select `bman_wallet_ledger`.`user_id` AS `user_id`,sum(`bman_wallet_ledger`.`amount`) AS `amt` from `bman_wallet_ledger` where `bman_wallet_ledger`.`wallet` = 'earning' and `bman_wallet_ledger`.`entry_type` in ('lock','debit') and `bman_wallet_ledger`.`status` = 'active' group by `bman_wallet_ledger`.`user_id`) `held` on(`held`.`user_id` = `uw`.`user_id`)) union all select `uw`.`user_id` AS `user_id`,'staking' AS `wallet`,`uw`.`staking_balance` AS `total`,coalesce(`locked`.`amt`,0) AS `locked`,greatest(`uw`.`staking_balance` - coalesce(`locked`.`amt`,0),0) AS `matured`,coalesce(`held`.`amt`,0) AS `holds`,greatest(greatest(`uw`.`staking_balance` - coalesce(`locked`.`amt`,0),0) - coalesce(`held`.`amt`,0),0) AS `available` from ((`user_wallets` `uw` left join (select `wallet_ledger`.`user_id` AS `user_id`,sum(`wallet_ledger`.`credit`) AS `amt` from `wallet_ledger` where `wallet_ledger`.`wallet_type` = 'staking' and `wallet_ledger`.`is_matured` = 0 and `wallet_ledger`.`credit` > 0 group by `wallet_ledger`.`user_id`) `locked` on(`locked`.`user_id` = `uw`.`user_id`)) left join (select `bman_wallet_ledger`.`user_id` AS `user_id`,sum(`bman_wallet_ledger`.`amount`) AS `amt` from `bman_wallet_ledger` where `bman_wallet_ledger`.`wallet` = 'staking' and `bman_wallet_ledger`.`entry_type` in ('lock','debit') and `bman_wallet_ledger`.`status` = 'active' group by `bman_wallet_ledger`.`user_id`) `held` on(`held`.`user_id` = `uw`.`user_id`)) union all select `uw`.`user_id` AS `user_id`,'bonus' AS `wallet`,`uw`.`bonus_balance` AS `total`,coalesce(`locked`.`amt`,0) AS `locked`,greatest(`uw`.`bonus_balance` - coalesce(`locked`.`amt`,0),0) AS `matured`,coalesce(`held`.`amt`,0) AS `holds`,greatest(greatest(`uw`.`bonus_balance` - coalesce(`locked`.`amt`,0),0) - coalesce(`held`.`amt`,0),0) AS `available` from ((`user_wallets` `uw` left join (select `wallet_ledger`.`user_id` AS `user_id`,sum(`wallet_ledger`.`credit`) AS `amt` from `wallet_ledger` where `wallet_ledger`.`wallet_type` = 'bonus' and `wallet_ledger`.`is_matured` = 0 and `wallet_ledger`.`credit` > 0 group by `wallet_ledger`.`user_id`) `locked` on(`locked`.`user_id` = `uw`.`user_id`)) left join (select `bman_wallet_ledger`.`user_id` AS `user_id`,sum(`bman_wallet_ledger`.`amount`) AS `amt` from `bman_wallet_ledger` where `bman_wallet_ledger`.`wallet` = 'bonus' and `bman_wallet_ledger`.`entry_type` in ('lock','debit') and `bman_wallet_ledger`.`status` = 'active' group by `bman_wallet_ledger`.`user_id`) `held` on(`held`.`user_id` = `uw`.`user_id`))  ;

-- --------------------------------------------------------

--
-- Structure for view `v_user_staking_activity`
--
DROP TABLE IF EXISTS `v_user_staking_activity`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `v_user_staking_activity`  AS SELECT `o`.`id` AS `id`, `o`.`user_id` AS `user_id`, `u`.`username` AS `username`, `u`.`email` AS `email`, `o`.`created_at` AS `created_at`, `o`.`status` AS `status`, `o`.`cron_status` AS `cron_status`, `o`.`usdt_amount` AS `usdt_amount`, `o`.`bman_amount` AS `bman_amount`, `o`.`bonus_bman` AS `bonus_bman`, `o`.`exchange_rate` AS `exchange_rate`, `o`.`error` AS `error` FROM (`staking_swap_orders` `o` join `users` `u` on(`u`.`id` = `o`.`user_id`)) ORDER BY `o`.`created_at` DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_badge_seen`
--
ALTER TABLE `admin_badge_seen`
  ADD PRIMARY KEY (`admin_id`,`category`);

--
-- Indexes for table `admin_ceiling_wallet`
--
ALTER TABLE `admin_ceiling_wallet`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_settings_audit`
--
ALTER TABLE `admin_settings_audit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_module` (`module`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `admin_wallet`
--
ALTER TABLE `admin_wallet`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_wallets`
--
ALTER TABLE `admin_wallets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_wallet_ledger`
--
ALTER TABLE `admin_wallet_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reference` (`reference_type`,`reference_user_id`);

--
-- Indexes for table `announcement`
--
ALTER TABLE `announcement`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcement_audit_log`
--
ALTER TABLE `announcement_audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_announcement_audit` (`announcement_id`);

--
-- Indexes for table `announcement_dismissals`
--
ALTER TABLE `announcement_dismissals`
  ADD PRIMARY KEY (`announcement_id`,`user_id`);

--
-- Indexes for table `announcement_stats`
--
ALTER TABLE `announcement_stats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_announcement` (`announcement_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `binary_carry`
--
ALTER TABLE `binary_carry`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `binary_carry_forward`
--
ALTER TABLE `binary_carry_forward`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `binary_matching_bonus_ledger`
--
ALTER TABLE `binary_matching_bonus_ledger`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_idempotent` (`purchase_id`,`bonus_recipient_id`,`level`),
  ADD KEY `idx_recipient_status` (`bonus_recipient_id`,`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `binary_matching_queue`
--
ALTER TABLE `binary_matching_queue`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_run_ref` (`run_ref`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `binary_placement`
--
ALTER TABLE `binary_placement`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `sponsor_id` (`sponsor_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `auto_from_user` (`auto_from_user`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_parent` (`parent_id`),
  ADD KEY `idx_parent_pos` (`parent_id`,`position`);

--
-- Indexes for table `binary_volume_ledger`
--
ALTER TABLE `binary_volume_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_invest` (`user_id`,`invest_id`),
  ADD KEY `idx_processed` (`processed`);

--
-- Indexes for table `blockchain_payout_queue`
--
ALTER TABLE `blockchain_payout_queue`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_payout_ref` (`payout_ref`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_txhash` (`tx_hash`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `blocked_ips`
--
ALTER TABLE `blocked_ips`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blogs`
--
ALTER TABLE `blogs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blog_categories`
--
ALTER TABLE `blog_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bman_wallet_ledger`
--
ALTER TABLE `bman_wallet_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_wallet` (`user_id`,`wallet`),
  ADD KEY `idx_user_maturity` (`user_id`,`maturity_date`),
  ADD KEY `idx_ref` (`ref_type`,`ref_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `bman_withdraw_allocations`
--
ALTER TABLE `bman_withdraw_allocations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_request_wallet` (`request_id`,`wallet`),
  ADD KEY `idx_request` (`request_id`);

--
-- Indexes for table `bman_withdraw_requests`
--
ALTER TABLE `bman_withdraw_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_request_no` (`request_no`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_status_created` (`status`,`created_at`),
  ADD KEY `idx_user_completed` (`user_id`,`completed_at`),
  ADD KEY `idx_status_completed` (`status`,`completed_at`);

--
-- Indexes for table `bonus_reduction_log`
--
ALTER TABLE `bonus_reduction_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart` (`user_id`,`product_id`);

--
-- Indexes for table `ceiling_wallet`
--
ALTER TABLE `ceiling_wallet`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `ceiling_wallet_config`
--
ALTER TABLE `ceiling_wallet_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `package_name` (`package_name`),
  ADD KEY `idx_package` (`package_name`);

--
-- Indexes for table `ceiling_wallet_ledger`
--
ALTER TABLE `ceiling_wallet_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_type` (`tx_type`),
  ADD KEY `idx_ref` (`reference_type`,`reference_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_room_id` (`room`,`id`),
  ADD KEY `idx_room_peer` (`room`,`peer_id`),
  ADD KEY `idx_room_user_peer` (`room`,`user_id`,`peer_id`),
  ADD KEY `idx_room_to` (`room`,`to_user_id`),
  ADD KEY `idx_room_user_to` (`room`,`user_id`,`to_user_id`),
  ADD KEY `idx_room_to_user` (`room`,`to_user_id`,`user_id`,`id`);

--
-- Indexes for table `chat_read_state`
--
ALTER TABLE `chat_read_state`
  ADD PRIMARY KEY (`user_id`,`room`,`peer_id`);

--
-- Indexes for table `coin_distribution_audit`
--
ALTER TABLE `coin_distribution_audit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_option` (`option_id`);

--
-- Indexes for table `coin_distribution_histories`
--
ALTER TABLE `coin_distribution_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_purchase` (`purchase_id`),
  ADD KEY `idx_option` (`option_id`);

--
-- Indexes for table `coin_distribution_options`
--
ALTER TABLE `coin_distribution_options`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_option_name` (`option_name`);

--
-- Indexes for table `commission_config`
--
ALTER TABLE `commission_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_requests`
--
ALTER TABLE `contact_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coupon_id` (`coupon_id`);

--
-- Indexes for table `cron_execution_log`
--
ALTER TABLE `cron_execution_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cron_name` (`cron_name`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `currency_config`
--
ALTER TABLE `currency_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `custodial_deposits`
--
ALTER TABLE `custodial_deposits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_address` (`address`),
  ADD KEY `idx_credited` (`credited`);

--
-- Indexes for table `deposits`
--
ALTER TABLE `deposits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `earning_ads`
--
ALTER TABLE `earning_ads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `earning_methods`
--
ALTER TABLE `earning_methods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_method_code` (`code`);

--
-- Indexes for table `earning_videos`
--
ALTER TABLE `earning_videos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_config`
--
ALTER TABLE `email_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_log`
--
ALTER TABLE `email_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_template`
--
ALTER TABLE `email_template`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_actor` (`actor_type`,`actor_id`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `epin_batches`
--
ALTER TABLE `epin_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_creator` (`created_by`,`created_at`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gas_fee_ledger`
--
ALTER TABLE `gas_fee_ledger`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_tx_hash` (`tx_hash`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`),
  ADD KEY `idx_onchain_tx` (`onchain_transaction_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `gas_fee_settings`
--
ALTER TABLE `gas_fee_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_tx_type` (`tx_type`);

--
-- Indexes for table `history`
--
ALTER TABLE `history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD KEY `idx_invoices_order` (`order_id`),
  ADD KEY `idx_invoices_no` (`invoice_no`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invoice_items_invoice` (`invoice_id`);

--
-- Indexes for table `kyc_applications`
--
ALTER TABLE `kyc_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `kyc_audit_logs`
--
ALTER TABLE `kyc_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kyc` (`kyc_id`);

--
-- Indexes for table `landing_brands`
--
ALTER TABLE `landing_brands`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `landing_crypto`
--
ALTER TABLE `landing_crypto`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `landing_exchange_logos`
--
ALTER TABLE `landing_exchange_logos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `landing_faq`
--
ALTER TABLE `landing_faq`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `landing_features`
--
ALTER TABLE `landing_features`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `landing_leads`
--
ALTER TABLE `landing_leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `landing_menu`
--
ALTER TABLE `landing_menu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `landing_roadmap`
--
ALTER TABLE `landing_roadmap`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `landing_settings`
--
ALTER TABLE `landing_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_section_key` (`section`,`skey`);

--
-- Indexes for table `landing_team`
--
ALTER TABLE `landing_team`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `landing_versions`
--
ALTER TABLE `landing_versions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `landing_work`
--
ALTER TABLE `landing_work`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `member_bulk_upload_batches`
--
ALTER TABLE `member_bulk_upload_batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ref` (`ref`),
  ADD KEY `idx_admin` (`admin_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `member_bulk_upload_rows`
--
ALTER TABLE `member_bulk_upload_rows`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_batch` (`batch_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_bman_status` (`bman_status`,`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_bman_credit_backfill` (`bman_status`,`bman_ledger_id`);

--
-- Indexes for table `member_bulk_upload_settings`
--
ALTER TABLE `member_bulk_upload_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `onchain_transactions`
--
ALTER TABLE `onchain_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_txhash` (`tx_hash`),
  ADD KEY `idx_wallet` (`wallet_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_network` (`network`),
  ADD KEY `idx_type` (`tx_type`),
  ADD KEY `idx_block` (`block_number`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_from` (`from_address`),
  ADD KEY `idx_to` (`to_address`),
  ADD KEY `idx_token` (`token_symbol`),
  ADD KEY `idx_ref` (`reference_type`,`reference_id`),
  ADD KEY `idx_gasfee` (`gas_fee_total`),
  ADD KEY `idx_status_created` (`status`,`created_at`);

--
-- Indexes for table `onchain_tx_events`
--
ALTER TABLE `onchain_tx_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tx` (`tx_id`),
  ADD KEY `idx_hash` (`tx_hash`),
  ADD KEY `idx_type` (`event_type`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_shipments`
--
ALTER TABLE `order_shipments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_shipments_order` (`order_id`),
  ADD KEY `idx_order_shipments_track` (`tracking_number`),
  ADD KEY `idx_order_shipments_status` (`status`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_history_order` (`order_id`),
  ADD KEY `idx_order_history_status` (`status`);

--
-- Indexes for table `package_config`
--
ALTER TABLE `package_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `page_content`
--
ALTER TABLE `page_content`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `page_link_config`
--
ALTER TABLE `page_link_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_controls`
--
ALTER TABLE `payment_controls`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_settings`
--
ALTER TABLE `payment_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payouts`
--
ALTER TABLE `payouts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_payouts_batch_id` (`batch_id`),
  ADD KEY `idx_payouts_status` (`status`),
  ADD KEY `idx_payouts_created_at` (`created_at`);

--
-- Indexes for table `payout_methods`
--
ALTER TABLE `payout_methods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `method_key` (`method_key`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_meta`
--
ALTER TABLE `product_meta`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `quick_tasks`
--
ALTER TABLE `quick_tasks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_task_code` (`code`);

--
-- Indexes for table `rank_certificates`
--
ALTER TABLE `rank_certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cert_no` (`certificate_no`),
  ADD UNIQUE KEY `uq_user_rank` (`user_id`,`rank_id`),
  ADD KEY `fk_rc_rank` (`rank_id`);

--
-- Indexes for table `rank_certificate_series`
--
ALTER TABLE `rank_certificate_series`
  ADD PRIMARY KEY (`year`,`rank_id`);

--
-- Indexes for table `rank_config`
--
ALTER TABLE `rank_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rank_cron_runs`
--
ALTER TABLE `rank_cron_runs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_run_month` (`run_month`);

--
-- Indexes for table `rank_cron_state`
--
ALTER TABLE `rank_cron_state`
  ADD PRIMARY KEY (`job`);

--
-- Indexes for table `rank_files`
--
ALTER TABLE `rank_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rank` (`rank_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `rank_rewards`
--
ALTER TABLE `rank_rewards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_rank_type` (`user_id`,`rank_id`,`reward_type`),
  ADD KEY `idx_status` (`reward_status`),
  ADD KEY `fk_rr_rank` (`rank_id`);

--
-- Indexes for table `roi_credits`
--
ALTER TABLE `roi_credits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_invest_date` (`invest_id`,`credit_date`),
  ADD KEY `idx_user_date` (`user_id`,`credit_date`),
  ADD KEY `idx_invest` (`invest_id`);

--
-- Indexes for table `roi_cron_execution`
--
ALTER TABLE `roi_cron_execution`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_execution` (`execution_date`,`cron_type`),
  ADD KEY `idx_execution_date` (`execution_date`),
  ADD KEY `idx_cron_type` (`cron_type`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `roi_distribution`
--
ALTER TABLE `roi_distribution`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staking_swap_orders_id` (`staking_swap_orders_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_maturity_date` (`maturity_date`),
  ADD KEY `idx_distribution_status` (`distribution_status`),
  ADD KEY `idx_is_matured` (`is_matured`);

--
-- Indexes for table `roi_distribution_audit`
--
ALTER TABLE `roi_distribution_audit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_stake` (`stake_id`),
  ADD KEY `idx_plan_type` (`plan_type`),
  ADD KEY `idx_payment_date` (`payment_date`),
  ADD KEY `idx_execution_date` (`execution_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_user_date` (`user_id`,`actual_payment_date`),
  ADD KEY `idx_status_retry` (`status`,`retry_count`);

--
-- Indexes for table `roi_failed_transactions`
--
ALTER TABLE `roi_failed_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_retry_at` (`next_retry_at`),
  ADD KEY `roi_audit_id` (`roi_audit_id`),
  ADD KEY `idx_retry_queue` (`status`,`next_retry_at`),
  ADD KEY `idx_user_status` (`user_id`,`status`);

--
-- Indexes for table `roi_gas_budget`
--
ALTER TABLE `roi_gas_budget`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_period` (`budget_type`,`period_start`),
  ADD KEY `idx_period` (`period_start`,`period_end`);

--
-- Indexes for table `roi_gas_fees`
--
ALTER TABLE `roi_gas_fees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_roi_audit` (`roi_audit_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_date` (`payment_date`),
  ADD KEY `idx_user_date` (`user_id`,`payment_date`),
  ADD KEY `idx_status_date` (`status`,`paid_at`);

--
-- Indexes for table `roi_maturity_schedule`
--
ALTER TABLE `roi_maturity_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_stake` (`stake_id`),
  ADD KEY `idx_maturity_date` (`maturity_date`),
  ADD KEY `idx_distributed` (`distributed`);

--
-- Indexes for table `roi_monthly_schedule`
--
ALTER TABLE `roi_monthly_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_stake` (`stake_id`),
  ADD KEY `idx_payment_month` (`payment_month_year`);

--
-- Indexes for table `roi_regular_payment_days`
--
ALTER TABLE `roi_regular_payment_days`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rrpd_cycle_day` (`roi_staking_management_id`,`cycle_no`,`day_of_month`),
  ADD KEY `idx_rrpd_due` (`status`,`scheduled_date`);

--
-- Indexes for table `roi_staking_management`
--
ALTER TABLE `roi_staking_management`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ref` (`ref`),
  ADD KEY `idx_staking_swap_orders_id` (`staking_swap_orders_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_plan_type` (`plan_type`),
  ADD KEY `idx_overall_status` (`overall_status`),
  ADD KEY `idx_next_payment_date` (`next_payment_date`),
  ADD KEY `idx_fixed_maturity_date` (`fixed_maturity_date`),
  ADD KEY `idx_user_stakes_id` (`user_stakes_id`);

--
-- Indexes for table `rpc_sync_log`
--
ALTER TABLE `rpc_sync_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_run` (`run_id`),
  ADD KEY `idx_scope` (`scope`),
  ADD KEY `idx_api` (`api_used`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `shipping_zones`
--
ALTER TABLE `shipping_zones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pincode` (`pincode`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sliders_img`
--
ALTER TABLE `sliders_img`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sociallinks`
--
ALTER TABLE `sociallinks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staking_bonus_settings`
--
ALTER TABLE `staking_bonus_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staking_documents`
--
ALTER TABLE `staking_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_invest_type` (`invest_id`,`doc_type`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_docno` (`doc_no`);

--
-- Indexes for table `staking_document_log`
--
ALTER TABLE `staking_document_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_doc` (`document_id`),
  ADD KEY `idx_invest` (`invest_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `staking_group_volume`
--
ALTER TABLE `staking_group_volume`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `staking_matching_payouts`
--
ALTER TABLE `staking_matching_payouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_run` (`run_ref`),
  ADD KEY `idx_user_created` (`user_id`,`created_at`);

--
-- Indexes for table `staking_packages`
--
ALTER TABLE `staking_packages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_amount_special` (`stake_amount`,`is_special`);

--
-- Indexes for table `staking_plans`
--
ALTER TABLE `staking_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_code` (`code`);

--
-- Indexes for table `staking_plan_terms`
--
ALTER TABLE `staking_plan_terms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_plan_term` (`plan_id`,`duration_years`);

--
-- Indexes for table `staking_ranks`
--
ALTER TABLE `staking_ranks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tier` (`tier_level`),
  ADD UNIQUE KEY `uq_name` (`name`);

--
-- Indexes for table `staking_rank_audit`
--
ALTER TABLE `staking_rank_audit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event` (`event`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `staking_rank_power_cycles`
--
ALTER TABLE `staking_rank_power_cycles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cycle_no` (`cycle_no`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `staking_rank_power_settings`
--
ALTER TABLE `staking_rank_power_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staking_rank_requirements`
--
ALTER TABLE `staking_rank_requirements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_req` (`rank_id`,`plan_no`,`option_no`,`side`),
  ADD KEY `fk_req_needs` (`required_rank_id`);

--
-- Indexes for table `staking_roi_audit`
--
ALTER TABLE `staking_roi_audit`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staking_roi_ledger`
--
ALTER TABLE `staking_roi_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staking` (`staking_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_date` (`processed_at`),
  ADD KEY `idx_wallet` (`wallet_column`);

--
-- Indexes for table `staking_roi_payouts`
--
ALTER TABLE `staking_roi_payouts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tx_hash` (`tx_hash`),
  ADD KEY `idx_stake` (`stake_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_transfer_status` (`transfer_status`),
  ADD KEY `idx_credit_date` (`credit_date`),
  ADD KEY `idx_user_credit` (`user_id`,`credit_date`);

--
-- Indexes for table `staking_roi_schedule_versions`
--
ALTER TABLE `staking_roi_schedule_versions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_version` (`package_id`,`plan_code`,`duration_years`,`version_no`),
  ADD UNIQUE KEY `uq_one_active` (`active_key`),
  ADD KEY `idx_lookup` (`package_id`,`plan_code`,`duration_years`,`status`);

--
-- Indexes for table `staking_roi_schedule_version_audit`
--
ALTER TABLE `staking_roi_schedule_version_audit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_version` (`version_id`);

--
-- Indexes for table `staking_roi_schedule_version_years`
--
ALTER TABLE `staking_roi_schedule_version_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_version_year` (`version_id`,`year_number`);

--
-- Indexes for table `staking_roi_structure`
--
ALTER TABLE `staking_roi_structure`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lookup` (`package_id`,`plan_code`,`duration_years`,`is_active`,`effective_from`);

--
-- Indexes for table `staking_roi_transfer_log`
--
ALTER TABLE `staking_roi_transfer_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_roi_payout` (`roi_payout_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_tx_hash` (`tx_hash`);

--
-- Indexes for table `staking_special_roi`
--
ALTER TABLE `staking_special_roi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pkg_year` (`package_id`,`year_number`),
  ADD KEY `idx_pkg` (`package_id`);

--
-- Indexes for table `staking_special_roi_audit`
--
ALTER TABLE `staking_special_roi_audit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pkg` (`package_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `staking_swap_orders`
--
ALTER TABLE `staking_swap_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ref` (`ref`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_cron_status` (`cron_status`,`status`),
  ADD KEY `idx_cron_pending` (`gas_cron_status`,`usdt_cron_status`,`bonus_cron_status`,`status`),
  ADD KEY `idx_roi_staking_management_id` (`roi_staking_management_id`),
  ADD KEY `idx_rank_volume` (`user_id`,`status`,`cron_status`),
  ADD KEY `idx_rank_volume_dated` (`status`,`cron_status`,`updated_at`),
  ADD KEY `idx_user_created` (`user_id`,`created_at`),
  ADD KEY `idx_status_cron_created` (`status`,`cron_status`,`created_at`);

--
-- Indexes for table `staking_treasury_payments`
--
ALTER TABLE `staking_treasury_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_stake` (`stake_id`),
  ADD KEY `idx_ref` (`ref`);

--
-- Indexes for table `support`
--
ALTER TABLE `support`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `support_message`
--
ALTER TABLE `support_message`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `token_config`
--
ALTER TABLE `token_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `token_settings`
--
ALTER TABLE `token_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_network_chain` (`network`,`chain_id`);

--
-- Indexes for table `token_settings_audit`
--
ALTER TABLE `token_settings_audit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_setting` (`setting_id`);

--
-- Indexes for table `treasury_direct_send`
--
ALTER TABLE `treasury_direct_send`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ref` (`ref`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `treasury_direct_send_settings`
--
ALTER TABLE `treasury_direct_send_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `treasury_key_reveal_log`
--
ALTER TABLE `treasury_key_reveal_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tkrl_admin` (`admin_id`,`created_at`),
  ADD KEY `idx_tkrl_setting` (`setting_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_users_kyc_status` (`kyc_status`),
  ADD KEY `idx_password_reset_token` (`password_reset_token`);

--
-- Indexes for table `user_account_actions`
--
ALTER TABLE `user_account_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id_action_status` (`user_id`,`action`,`status`);

--
-- Indexes for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_ad_rewards`
--
ALTER TABLE `user_ad_rewards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_ad_once` (`user_id`,`ad_id`);

--
-- Indexes for table `user_ad_sessions`
--
ALTER TABLE `user_ad_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ad_token` (`token`),
  ADD KEY `idx_user_ad` (`user_id`,`ad_id`);

--
-- Indexes for table `user_bank`
--
ALTER TABLE `user_bank`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_bank_user` (`user_id`),
  ADD KEY `idx_user_bank_status` (`status`);

--
-- Indexes for table `user_email_otp`
--
ALTER TABLE `user_email_otp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `ref` (`ref`);

--
-- Indexes for table `user_investment`
--
ALTER TABLE `user_investment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_kyc`
--
ALTER TABLE `user_kyc`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_kyc_user` (`user_id`),
  ADD KEY `idx_user_kyc_status` (`status`);

--
-- Indexes for table `user_method_progress`
--
ALTER TABLE `user_method_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_method_day` (`user_id`,`method_id`,`progress_date`),
  ADD KEY `idx_method` (`method_id`);

--
-- Indexes for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ref` (`user_id`,`type`,`reference_type`,`reference_id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`),
  ADD KEY `idx_type` (`type`);

--
-- Indexes for table `user_ranks`
--
ALTER TABLE `user_ranks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user` (`user_id`),
  ADD KEY `idx_highest` (`highest_rank_id`),
  ADD KEY `fk_ur_current` (`current_rank_id`);

--
-- Indexes for table `user_rank_history`
--
ALTER TABLE `user_rank_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_new_rank` (`new_rank_id`),
  ADD KEY `idx_achieved` (`achieved_at`);

--
-- Indexes for table `user_rank_power`
--
ALTER TABLE `user_rank_power`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_cycle` (`user_id`,`cycle_id`),
  ADD KEY `idx_cycle` (`cycle_id`);

--
-- Indexes for table `user_stakes`
--
ALTER TABLE `user_stakes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_us_txhash` (`tx_hash`),
  ADD KEY `idx_us_chain_status` (`chain_status`),
  ADD KEY `idx_us_execution_mode` (`execution_mode`);

--
-- Indexes for table `user_stake_roi_year_snapshot`
--
ALTER TABLE `user_stake_roi_year_snapshot`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_stake_year` (`stake_id`,`year_number`);

--
-- Indexes for table `user_streaks`
--
ALTER TABLE `user_streaks`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `user_task_claims`
--
ALTER TABLE `user_task_claims`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_task_day` (`user_id`,`task_id`,`claim_date`),
  ADD KEY `idx_user_day` (`user_id`,`claim_date`);

--
-- Indexes for table `user_video_rewards`
--
ALTER TABLE `user_video_rewards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_video_once` (`user_id`,`video_id`);

--
-- Indexes for table `user_video_sessions`
--
ALTER TABLE `user_video_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_token` (`token`),
  ADD KEY `idx_user_video` (`user_id`,`video_id`);

--
-- Indexes for table `user_wallet`
--
ALTER TABLE `user_wallet`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_wallet_address` (`wallet_address`);

--
-- Indexes for table `user_wallets`
--
ALTER TABLE `user_wallets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_wallet_user` (`user_id`);

--
-- Indexes for table `wallet_balance_sync`
--
ALTER TABLE `wallet_balance_sync`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_addr_token` (`address`,`token`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `wallet_deposits`
--
ALTER TABLE `wallet_deposits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tx` (`tx_hash`,`log_index`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_address` (`wallet_address`);

--
-- Indexes for table `wallet_internal_transfer`
--
ALTER TABLE `wallet_internal_transfer`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ref` (`ref`),
  ADD UNIQUE KEY `uq_txn_uid` (`txn_uid`),
  ADD UNIQUE KEY `uq_idem` (`idempotency_key`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_to_user` (`to_user_id`),
  ADD KEY `idx_settlement_status` (`settlement_status`,`id`);

--
-- Indexes for table `wallet_ledger`
--
ALTER TABLE `wallet_ledger`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tx_wallet` (`tx_hash`,`wallet_type`),
  ADD KEY `idx_user_wallet` (`user_id`,`wallet_type`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`),
  ADD KEY `idx_wallet_maturity` (`is_matured`,`maturity_date`,`wallet_type`),
  ADD KEY `idx_user_created` (`user_id`,`created_at`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_wallet_ref_created` (`wallet_type`,`reference_type`,`created_at`);

--
-- Indexes for table `wallet_settlement_cron_state`
--
ALTER TABLE `wallet_settlement_cron_state`
  ADD PRIMARY KEY (`job`);

--
-- Indexes for table `wallet_sync_cursor`
--
ALTER TABLE `wallet_sync_cursor`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wallet_transfer_audit`
--
ALTER TABLE `wallet_transfer_audit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transfer` (`transfer_id`),
  ADD KEY `idx_ref` (`ref`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `wallet_transfer_settlement_settings`
--
ALTER TABLE `wallet_transfer_settlement_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wallet_withdraw_holds`
--
ALTER TABLE `wallet_withdraw_holds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_request` (`request_id`),
  ADD KEY `idx_user_wallet` (`user_id`,`wallet_type`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`);

--
-- Indexes for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_withdrawals_user_id` (`user_id`),
  ADD KEY `idx_withdrawals_status` (`status`),
  ADD KEY `idx_withdrawals_payout_id` (`payout_id`),
  ADD KEY `idx_withdrawals_created_at` (`created_at`);

--
-- Indexes for table `withdraw_audit_log`
--
ALTER TABLE `withdraw_audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_request` (`request_id`),
  ADD KEY `idx_admin` (`admin_id`);

--
-- Indexes for table `withdraw_request`
--
ALTER TABLE `withdraw_request`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_withdraw_request_user_id` (`user_id`),
  ADD KEY `idx_withdraw_request_status` (`status`),
  ADD KEY `idx_withdraw_request_request_id` (`request_id`),
  ADD KEY `idx_withdraw_request_created_at` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_settings_audit`
--
ALTER TABLE `admin_settings_audit`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `admin_wallet_ledger`
--
ALTER TABLE `admin_wallet_ledger`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcement`
--
ALTER TABLE `announcement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `announcement_audit_log`
--
ALTER TABLE `announcement_audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcement_stats`
--
ALTER TABLE `announcement_stats`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `binary_matching_bonus_ledger`
--
ALTER TABLE `binary_matching_bonus_ledger`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `binary_matching_queue`
--
ALTER TABLE `binary_matching_queue`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `binary_placement`
--
ALTER TABLE `binary_placement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `binary_volume_ledger`
--
ALTER TABLE `binary_volume_ledger`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `blockchain_payout_queue`
--
ALTER TABLE `blockchain_payout_queue`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `blocked_ips`
--
ALTER TABLE `blocked_ips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blogs`
--
ALTER TABLE `blogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blog_categories`
--
ALTER TABLE `blog_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bman_wallet_ledger`
--
ALTER TABLE `bman_wallet_ledger`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bman_withdraw_allocations`
--
ALTER TABLE `bman_withdraw_allocations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bman_withdraw_requests`
--
ALTER TABLE `bman_withdraw_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bonus_reduction_log`
--
ALTER TABLE `bonus_reduction_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ceiling_wallet_config`
--
ALTER TABLE `ceiling_wallet_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `ceiling_wallet_ledger`
--
ALTER TABLE `ceiling_wallet_ledger`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `coin_distribution_audit`
--
ALTER TABLE `coin_distribution_audit`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `coin_distribution_histories`
--
ALTER TABLE `coin_distribution_histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coin_distribution_options`
--
ALTER TABLE `coin_distribution_options`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `commission_config`
--
ALTER TABLE `commission_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contact_requests`
--
ALTER TABLE `contact_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=236;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cron_execution_log`
--
ALTER TABLE `cron_execution_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `currency_config`
--
ALTER TABLE `currency_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `custodial_deposits`
--
ALTER TABLE `custodial_deposits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deposits`
--
ALTER TABLE `deposits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `earning_ads`
--
ALTER TABLE `earning_ads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `earning_methods`
--
ALTER TABLE `earning_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `earning_videos`
--
ALTER TABLE `earning_videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_config`
--
ALTER TABLE `email_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `email_log`
--
ALTER TABLE `email_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `email_template`
--
ALTER TABLE `email_template`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `epin_batches`
--
ALTER TABLE `epin_batches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `gas_fee_ledger`
--
ALTER TABLE `gas_fee_ledger`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `gas_fee_settings`
--
ALTER TABLE `gas_fee_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `history`
--
ALTER TABLE `history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kyc_applications`
--
ALTER TABLE `kyc_applications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `kyc_audit_logs`
--
ALTER TABLE `kyc_audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `landing_brands`
--
ALTER TABLE `landing_brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `landing_crypto`
--
ALTER TABLE `landing_crypto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `landing_exchange_logos`
--
ALTER TABLE `landing_exchange_logos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `landing_faq`
--
ALTER TABLE `landing_faq`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `landing_features`
--
ALTER TABLE `landing_features`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `landing_leads`
--
ALTER TABLE `landing_leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `landing_menu`
--
ALTER TABLE `landing_menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `landing_roadmap`
--
ALTER TABLE `landing_roadmap`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `landing_settings`
--
ALTER TABLE `landing_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=212;

--
-- AUTO_INCREMENT for table `landing_team`
--
ALTER TABLE `landing_team`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `landing_versions`
--
ALTER TABLE `landing_versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `landing_work`
--
ALTER TABLE `landing_work`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `member_bulk_upload_batches`
--
ALTER TABLE `member_bulk_upload_batches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `member_bulk_upload_rows`
--
ALTER TABLE `member_bulk_upload_rows`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `onchain_transactions`
--
ALTER TABLE `onchain_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=375;

--
-- AUTO_INCREMENT for table `onchain_tx_events`
--
ALTER TABLE `onchain_tx_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=298;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_shipments`
--
ALTER TABLE `order_shipments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package_config`
--
ALTER TABLE `package_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `page_content`
--
ALTER TABLE `page_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `page_link_config`
--
ALTER TABLE `page_link_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payment_controls`
--
ALTER TABLE `payment_controls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payment_settings`
--
ALTER TABLE `payment_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payouts`
--
ALTER TABLE `payouts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payout_methods`
--
ALTER TABLE `payout_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `product_meta`
--
ALTER TABLE `product_meta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=225;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quick_tasks`
--
ALTER TABLE `quick_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rank_certificates`
--
ALTER TABLE `rank_certificates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `rank_config`
--
ALTER TABLE `rank_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `rank_cron_runs`
--
ALTER TABLE `rank_cron_runs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rank_files`
--
ALTER TABLE `rank_files`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `rank_rewards`
--
ALTER TABLE `rank_rewards`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roi_credits`
--
ALTER TABLE `roi_credits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roi_cron_execution`
--
ALTER TABLE `roi_cron_execution`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roi_distribution`
--
ALTER TABLE `roi_distribution`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roi_distribution_audit`
--
ALTER TABLE `roi_distribution_audit`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roi_failed_transactions`
--
ALTER TABLE `roi_failed_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roi_gas_budget`
--
ALTER TABLE `roi_gas_budget`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roi_gas_fees`
--
ALTER TABLE `roi_gas_fees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roi_maturity_schedule`
--
ALTER TABLE `roi_maturity_schedule`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roi_monthly_schedule`
--
ALTER TABLE `roi_monthly_schedule`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roi_regular_payment_days`
--
ALTER TABLE `roi_regular_payment_days`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `roi_staking_management`
--
ALTER TABLE `roi_staking_management`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `rpc_sync_log`
--
ALTER TABLE `rpc_sync_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `shipping_zones`
--
ALTER TABLE `shipping_zones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `sliders_img`
--
ALTER TABLE `sliders_img`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `sociallinks`
--
ALTER TABLE `sociallinks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `staking_documents`
--
ALTER TABLE `staking_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staking_document_log`
--
ALTER TABLE `staking_document_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staking_matching_payouts`
--
ALTER TABLE `staking_matching_payouts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `staking_packages`
--
ALTER TABLE `staking_packages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `staking_plans`
--
ALTER TABLE `staking_plans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `staking_plan_terms`
--
ALTER TABLE `staking_plan_terms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `staking_ranks`
--
ALTER TABLE `staking_ranks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `staking_rank_audit`
--
ALTER TABLE `staking_rank_audit`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `staking_rank_power_cycles`
--
ALTER TABLE `staking_rank_power_cycles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `staking_rank_requirements`
--
ALTER TABLE `staking_rank_requirements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `staking_roi_audit`
--
ALTER TABLE `staking_roi_audit`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `staking_roi_ledger`
--
ALTER TABLE `staking_roi_ledger`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staking_roi_payouts`
--
ALTER TABLE `staking_roi_payouts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `staking_roi_schedule_versions`
--
ALTER TABLE `staking_roi_schedule_versions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `staking_roi_schedule_version_audit`
--
ALTER TABLE `staking_roi_schedule_version_audit`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `staking_roi_schedule_version_years`
--
ALTER TABLE `staking_roi_schedule_version_years`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `staking_roi_structure`
--
ALTER TABLE `staking_roi_structure`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT for table `staking_roi_transfer_log`
--
ALTER TABLE `staking_roi_transfer_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staking_special_roi`
--
ALTER TABLE `staking_special_roi`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `staking_special_roi_audit`
--
ALTER TABLE `staking_special_roi_audit`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `staking_swap_orders`
--
ALTER TABLE `staking_swap_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `staking_treasury_payments`
--
ALTER TABLE `staking_treasury_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `support`
--
ALTER TABLE `support`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `support_message`
--
ALTER TABLE `support_message`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `token_config`
--
ALTER TABLE `token_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `token_settings`
--
ALTER TABLE `token_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `token_settings_audit`
--
ALTER TABLE `token_settings_audit`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `treasury_direct_send`
--
ALTER TABLE `treasury_direct_send`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `treasury_key_reveal_log`
--
ALTER TABLE `treasury_key_reveal_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=999999617;

--
-- AUTO_INCREMENT for table `user_account_actions`
--
ALTER TABLE `user_account_actions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_ad_rewards`
--
ALTER TABLE `user_ad_rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_ad_sessions`
--
ALTER TABLE `user_ad_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_bank`
--
ALTER TABLE `user_bank`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_email_otp`
--
ALTER TABLE `user_email_otp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_investment`
--
ALTER TABLE `user_investment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_kyc`
--
ALTER TABLE `user_kyc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_method_progress`
--
ALTER TABLE `user_method_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_notifications`
--
ALTER TABLE `user_notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_ranks`
--
ALTER TABLE `user_ranks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_rank_history`
--
ALTER TABLE `user_rank_history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_rank_power`
--
ALTER TABLE `user_rank_power`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `user_stakes`
--
ALTER TABLE `user_stakes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `user_stake_roi_year_snapshot`
--
ALTER TABLE `user_stake_roi_year_snapshot`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_task_claims`
--
ALTER TABLE `user_task_claims`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_video_rewards`
--
ALTER TABLE `user_video_rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_video_sessions`
--
ALTER TABLE `user_video_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_wallet`
--
ALTER TABLE `user_wallet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `user_wallets`
--
ALTER TABLE `user_wallets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `wallet_balance_sync`
--
ALTER TABLE `wallet_balance_sync`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `wallet_deposits`
--
ALTER TABLE `wallet_deposits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `wallet_internal_transfer`
--
ALTER TABLE `wallet_internal_transfer`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `wallet_ledger`
--
ALTER TABLE `wallet_ledger`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=284;

--
-- AUTO_INCREMENT for table `wallet_transfer_audit`
--
ALTER TABLE `wallet_transfer_audit`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `wallet_withdraw_holds`
--
ALTER TABLE `wallet_withdraw_holds`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `withdrawals`
--
ALTER TABLE `withdrawals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `withdraw_audit_log`
--
ALTER TABLE `withdraw_audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `withdraw_request`
--
ALTER TABLE `withdraw_request`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `binary_placement`
--
ALTER TABLE `binary_placement`
  ADD CONSTRAINT `binary_placement_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `binary_placement_ibfk_2` FOREIGN KEY (`sponsor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `binary_placement_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `binary_placement_ibfk_4` FOREIGN KEY (`auto_from_user`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  ADD CONSTRAINT `coupon_usage_ibfk_1` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `epin_batches`
--
ALTER TABLE `epin_batches`
  ADD CONSTRAINT `fk_batch_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_invoices_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `fk_invoice_items_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kyc_applications`
--
ALTER TABLE `kyc_applications`
  ADD CONSTRAINT `fk_kyc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_shipments`
--
ALTER TABLE `order_shipments`
  ADD CONSTRAINT `fk_order_shipments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `fk_order_history_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rank_certificates`
--
ALTER TABLE `rank_certificates`
  ADD CONSTRAINT `fk_rc_rank` FOREIGN KEY (`rank_id`) REFERENCES `staking_ranks` (`id`);

--
-- Constraints for table `rank_rewards`
--
ALTER TABLE `rank_rewards`
  ADD CONSTRAINT `fk_rr_rank` FOREIGN KEY (`rank_id`) REFERENCES `staking_ranks` (`id`);

--
-- Constraints for table `roi_distribution`
--
ALTER TABLE `roi_distribution`
  ADD CONSTRAINT `fk_roi_distribution_staking` FOREIGN KEY (`staking_swap_orders_id`) REFERENCES `staking_swap_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `roi_failed_transactions`
--
ALTER TABLE `roi_failed_transactions`
  ADD CONSTRAINT `roi_failed_transactions_ibfk_1` FOREIGN KEY (`roi_audit_id`) REFERENCES `roi_distribution_audit` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `roi_gas_fees`
--
ALTER TABLE `roi_gas_fees`
  ADD CONSTRAINT `roi_gas_fees_ibfk_1` FOREIGN KEY (`roi_audit_id`) REFERENCES `roi_distribution_audit` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `roi_staking_management`
--
ALTER TABLE `roi_staking_management`
  ADD CONSTRAINT `fk_roi_staking_swap_orders` FOREIGN KEY (`staking_swap_orders_id`) REFERENCES `staking_swap_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_roi_user_stakes` FOREIGN KEY (`user_stakes_id`) REFERENCES `user_stakes` (`id`);

--
-- Constraints for table `staking_plan_terms`
--
ALTER TABLE `staking_plan_terms`
  ADD CONSTRAINT `fk_term_plan` FOREIGN KEY (`plan_id`) REFERENCES `staking_plans` (`id`);

--
-- Constraints for table `staking_rank_requirements`
--
ALTER TABLE `staking_rank_requirements`
  ADD CONSTRAINT `fk_req_needs` FOREIGN KEY (`required_rank_id`) REFERENCES `staking_ranks` (`id`),
  ADD CONSTRAINT `fk_req_rank` FOREIGN KEY (`rank_id`) REFERENCES `staking_ranks` (`id`);

--
-- Constraints for table `staking_roi_schedule_versions`
--
ALTER TABLE `staking_roi_schedule_versions`
  ADD CONSTRAINT `fk_ver_pkg` FOREIGN KEY (`package_id`) REFERENCES `staking_packages` (`id`);

--
-- Constraints for table `staking_roi_schedule_version_years`
--
ALTER TABLE `staking_roi_schedule_version_years`
  ADD CONSTRAINT `fk_yr_version` FOREIGN KEY (`version_id`) REFERENCES `staking_roi_schedule_versions` (`id`);

--
-- Constraints for table `staking_roi_structure`
--
ALTER TABLE `staking_roi_structure`
  ADD CONSTRAINT `fk_roi_pkg` FOREIGN KEY (`package_id`) REFERENCES `staking_packages` (`id`);

--
-- Constraints for table `staking_roi_transfer_log`
--
ALTER TABLE `staking_roi_transfer_log`
  ADD CONSTRAINT `fk_roi_transfer_payout` FOREIGN KEY (`roi_payout_id`) REFERENCES `staking_roi_payouts` (`id`);

--
-- Constraints for table `user_ranks`
--
ALTER TABLE `user_ranks`
  ADD CONSTRAINT `fk_ur_current` FOREIGN KEY (`current_rank_id`) REFERENCES `staking_ranks` (`id`),
  ADD CONSTRAINT `fk_ur_highest` FOREIGN KEY (`highest_rank_id`) REFERENCES `staking_ranks` (`id`);

--
-- Constraints for table `user_rank_history`
--
ALTER TABLE `user_rank_history`
  ADD CONSTRAINT `fk_urh_new` FOREIGN KEY (`new_rank_id`) REFERENCES `staking_ranks` (`id`);

--
-- Constraints for table `user_rank_power`
--
ALTER TABLE `user_rank_power`
  ADD CONSTRAINT `fk_power_cycle` FOREIGN KEY (`cycle_id`) REFERENCES `staking_rank_power_cycles` (`id`);

--
-- Constraints for table `user_stake_roi_year_snapshot`
--
ALTER TABLE `user_stake_roi_year_snapshot`
  ADD CONSTRAINT `fk_snap_stake` FOREIGN KEY (`stake_id`) REFERENCES `user_stakes` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
