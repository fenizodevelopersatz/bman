-- ============================================================================
-- BMAN — Rank Achievement System (permanent) + Rank Power (60-day cycles)
-- ----------------------------------------------------------------------------
-- EXTENDS the existing rank tables — does NOT duplicate them. Companion to
-- db/staking_module.sql (staking_ranks, staking_rank_requirements) and
-- db/staking_rank_power.sql (staking_rank_power_settings/_cycles, user_rank_power).
--
-- Sources of truth (unchanged):
--   staking_ranks               — the 11 permanent ranks, tier 0..10
--   staking_rank_requirements   — qualification matrix, Plan-1/2/3 × option_no
--   staking_rank_power_cycles   — 60-day power windows
--   user_rank_power             — per-user power rank inside a cycle
--
-- What this migration adds:
--   1. staking_ranks       + required_group_volume, badge_image, reward_bman,
--                            reward_usdt, reward_description
--   2. user_rank_power     + left_volume, right_volume, total_volume,
--                            calculated_at  (power_rank_id already exists and
--                            remains THE power-rank column — see note below)
--   3. user_ranks          — current + highest permanent rank, one row per user
--   4. user_rank_history   — every promotion
--   5. rank_rewards        — reward issuance proof (UNIQUE ⇒ never paid twice)
--   6. rank_certificates   — certificate issuance (UNIQUE ⇒ never issued twice)
--   7. staking_rank_audit  — audit trail (mirrors staking_roi_audit)
--   8. user_notifications  — per-user notifications (the legacy `notification`
--                            table has no user_id, so it cannot carry these)
--
-- NOTE on `power_rank`: the spec asked for a `power_rank` column. user_rank_power
-- already has `power_rank_id` (FK → staking_ranks) which serves exactly that
-- purpose. Adding a second `power_rank` name column would create the duplicate
-- source of truth this build explicitly forbids, so power_rank_id is kept as-is.
-- Read the name via JOIN staking_ranks, or via the vw_user_rank_power view below.
--
-- Idempotent: safe to re-run.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. staking_ranks — new columns
-- ----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS _sr_add_col;
DELIMITER //
CREATE PROCEDURE _sr_add_col(IN col VARCHAR(64), IN ddl VARCHAR(255))
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_ranks'
        AND COLUMN_NAME = col) = 0 THEN
    SET @s := CONCAT('ALTER TABLE `staking_ranks` ADD COLUMN ', ddl);
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END//
DELIMITER ;

CALL _sr_add_col('required_group_volume', "`required_group_volume` DECIMAL(30,8) NOT NULL DEFAULT 0 COMMENT 'downline BMAN volume needed to hold this rank'");
CALL _sr_add_col('badge_image',           "`badge_image` VARCHAR(255) NULL DEFAULT NULL COMMENT 'uploads/rank_badges/*.png'");
CALL _sr_add_col('reward_bman',           "`reward_bman` DECIMAL(30,8) NOT NULL DEFAULT 0 COMMENT 'one-time BMAN reward on achieving'");
CALL _sr_add_col('reward_usdt',           "`reward_usdt` DECIMAL(30,8) NOT NULL DEFAULT 0 COMMENT 'one-time USDT reward on achieving'");
CALL _sr_add_col('reward_description',    "`reward_description` VARCHAR(255) NULL DEFAULT NULL COMMENT 'free-text reward note (trip, gadget, …)'");
DROP PROCEDURE IF EXISTS _sr_add_col;

-- Seed required_group_volume from the proposal §10 ladder.
-- Keyed on tier_level; only fills rows still at the 0 default so admin edits
-- made after the first run are never stomped.
UPDATE `staking_ranks` SET `required_group_volume` = CASE `tier_level`
    WHEN 0  THEN 1000
    WHEN 1  THEN 7500
    WHEN 2  THEN 30000
    WHEN 3  THEN 150000
    WHEN 4  THEN 600000
    WHEN 5  THEN 2500000
    WHEN 6  THEN 10000000
    WHEN 7  THEN 20000000
    WHEN 8  THEN 30000000
    WHEN 9  THEN 40000000
    WHEN 10 THEN 50000000
    ELSE `required_group_volume` END
WHERE `required_group_volume` = 0 AND `tier_level` BETWEEN 0 AND 10;

-- Seed the rank badge art shipped in assets/rank/. Paths are stored relative to
-- the web root and rendered with base_url(), the same as every other asset.
-- Only fills rows still NULL, so a badge uploaded later through the admin page
-- (which writes to uploads/rank_badges/) is never overwritten by a re-run.
UPDATE `staking_ranks` SET `badge_image` = CASE `tier_level`
    WHEN 0  THEN 'assets/rank/un_rank.jpeg'
    WHEN 1  THEN 'assets/rank/iron.jpeg'
    WHEN 2  THEN 'assets/rank/bronze.jpeg'
    WHEN 3  THEN 'assets/rank/silver.jpeg'
    WHEN 4  THEN 'assets/rank/gold.jpeg'
    WHEN 5  THEN 'assets/rank/platinum.jpeg'
    WHEN 6  THEN 'assets/rank/emerald.jpeg'
    WHEN 7  THEN 'assets/rank/diamond.jpeg'
    WHEN 8  THEN 'assets/rank/master.jpeg'
    WHEN 9  THEN 'assets/rank/grand_master.jpeg'
    WHEN 10 THEN 'assets/rank/challenger.jpeg'
    ELSE `badge_image` END
WHERE `badge_image` IS NULL AND `tier_level` BETWEEN 0 AND 10;

-- ----------------------------------------------------------------------------
-- 2. user_rank_power — volume columns (§11 counts CURRENT-CYCLE volume only)
-- ----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS _urp_add_col;
DELIMITER //
CREATE PROCEDURE _urp_add_col(IN col VARCHAR(64), IN ddl VARCHAR(255))
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_rank_power'
        AND COLUMN_NAME = col) = 0 THEN
    SET @s := CONCAT('ALTER TABLE `user_rank_power` ADD COLUMN ', ddl);
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END//
DELIMITER ;

CALL _urp_add_col('left_volume',   "`left_volume` DECIMAL(30,8) NOT NULL DEFAULT 0 COMMENT 'left-leg BMAN volume THIS cycle'");
CALL _urp_add_col('right_volume',  "`right_volume` DECIMAL(30,8) NOT NULL DEFAULT 0 COMMENT 'right-leg BMAN volume THIS cycle'");
CALL _urp_add_col('total_volume',  "`total_volume` DECIMAL(30,8) NOT NULL DEFAULT 0 COMMENT 'left + right, THIS cycle'");
CALL _urp_add_col('calculated_at', "`calculated_at` DATETIME NULL DEFAULT NULL COMMENT 'last engine pass'");
DROP PROCEDURE IF EXISTS _urp_add_col;

-- ----------------------------------------------------------------------------
-- 3. user_ranks — permanent achievement rank, one row per user
--    current_rank_id == highest_rank_id always (permanent ranks never drop);
--    both are kept so the model reads naturally and a future policy change
--    (if ranks ever become losable) has somewhere to land.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_ranks` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`         INT NOT NULL,
  `current_rank_id` INT UNSIGNED NULL DEFAULT NULL,
  `highest_rank_id` INT UNSIGNED NULL DEFAULT NULL,
  `group_volume`    DECIMAL(30,8) NOT NULL DEFAULT 0 COMMENT 'volume at last evaluation (informational)',
  `achieved_at`     DATETIME NULL DEFAULT NULL COMMENT 'when highest_rank_id was reached',
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user` (`user_id`),
  KEY `idx_highest` (`highest_rank_id`),
  CONSTRAINT `fk_ur_current` FOREIGN KEY (`current_rank_id`) REFERENCES `staking_ranks`(`id`),
  CONSTRAINT `fk_ur_highest` FOREIGN KEY (`highest_rank_id`) REFERENCES `staking_ranks`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Permanent achievement rank per user — never downgraded';

-- ----------------------------------------------------------------------------
-- 4. user_rank_history — one row per promotion
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_rank_history` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`            INT NOT NULL,
  `old_rank_id`        INT UNSIGNED NULL DEFAULT NULL,
  `new_rank_id`        INT UNSIGNED NOT NULL,
  `achieved_volume`    DECIMAL(30,8) NOT NULL DEFAULT 0,
  `left_volume`        DECIMAL(30,8) NOT NULL DEFAULT 0,
  `right_volume`       DECIMAL(30,8) NOT NULL DEFAULT 0,
  `qualification_plan` VARCHAR(40) NULL DEFAULT NULL COMMENT 'e.g. "Plan-1 / Option-2"',
  `source`             VARCHAR(20) NOT NULL DEFAULT 'cron' COMMENT 'cron | admin | manual',
  `achieved_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_new_rank` (`new_rank_id`),
  KEY `idx_achieved` (`achieved_at`),
  CONSTRAINT `fk_urh_new` FOREIGN KEY (`new_rank_id`) REFERENCES `staking_ranks`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Every rank promotion — the admin Rank History report reads this';

-- ----------------------------------------------------------------------------
-- 5. rank_rewards — issuance proof. UNIQUE (user, rank, type) is what makes
--    the cron idempotent: a second pass hits the unique index and skips.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rank_rewards` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`          INT NOT NULL,
  `rank_id`          INT UNSIGNED NOT NULL,
  `rank_name`        VARCHAR(40) NOT NULL COMMENT 'denormalised for reporting',
  `reward_type`      ENUM('bman','usdt','physical') NOT NULL,
  `reward_amount`    DECIMAL(30,8) NOT NULL DEFAULT 0,
  `reward_status`    ENUM('pending','paid','failed','skipped') NOT NULL DEFAULT 'pending',
  `wallet_ledger_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'proof of payment',
  `note`             VARCHAR(255) NULL DEFAULT NULL,
  `rewarded_at`      DATETIME NULL DEFAULT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_rank_type` (`user_id`,`rank_id`,`reward_type`),
  KEY `idx_status` (`reward_status`),
  CONSTRAINT `fk_rr_rank` FOREIGN KEY (`rank_id`) REFERENCES `staking_ranks`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Rank reward issuance — UNIQUE(user,rank,type) prevents double payment';

-- ----------------------------------------------------------------------------
-- 6. rank_certificates — one certificate per user per rank, ever.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rank_certificates` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `certificate_no`  VARCHAR(48) NOT NULL COMMENT 'BMAN-GOLD-2026-000001',
  `user_id`         INT NOT NULL,
  `rank_id`         INT UNSIGNED NOT NULL,
  `rank_name`       VARCHAR(40) NOT NULL,
  `generated_date`  DATE NOT NULL,
  `certificate_pdf` VARCHAR(255) NULL DEFAULT NULL COMMENT 'uploads/rank_certificates/*.pdf',
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cert_no` (`certificate_no`),
  UNIQUE KEY `uq_user_rank` (`user_id`,`rank_id`),
  CONSTRAINT `fk_rc_rank` FOREIGN KEY (`rank_id`) REFERENCES `staking_ranks`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Rank certificates — UNIQUE(user,rank) prevents duplicates';

-- Certificate serial counter, one sequence per (rank, year) so the first GOLD
-- certificate of 2026 really is BMAN-GOLD-2026-000001 rather than inheriting a
-- global count. A dedicated table (not COUNT(*)+1) so concurrent cron batches
-- cannot mint the same serial: the engine bumps it with an atomic
-- UPDATE … last_no = LAST_INSERT_ID(last_no + 1).
CREATE TABLE IF NOT EXISTS `rank_certificate_series` (
  `year`    SMALLINT UNSIGNED NOT NULL,
  `rank_id` INT UNSIGNED NOT NULL,
  `last_no` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`year`,`rank_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 7. staking_rank_audit — mirrors the house pattern of staking_roi_audit
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staking_rank_audit` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event`      VARCHAR(40) NOT NULL COMMENT 'rank_promoted | reward_paid | certificate_issued | rank_config_changed | power_calculated | cycle_opened',
  `user_id`    INT NULL DEFAULT NULL COMMENT 'affected member (NULL for config events)',
  `rank_id`    INT UNSIGNED NULL DEFAULT NULL,
  `old_value`  VARCHAR(120) NULL DEFAULT NULL,
  `new_value`  VARCHAR(120) NULL DEFAULT NULL,
  `changed_by` INT NULL DEFAULT NULL COMMENT 'admin id, NULL = cron',
  `note`       VARCHAR(255) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_event` (`event`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Audit trail for all rank activity';

-- ----------------------------------------------------------------------------
-- 8. user_notifications — the legacy `notification` table is (id, message,
--    created_at) with no user_id, so it cannot carry per-user rank alerts.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_notifications` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        INT NOT NULL,
  `type`           VARCHAR(40) NOT NULL DEFAULT 'general',
  `title`          VARCHAR(120) NOT NULL,
  `message`        VARCHAR(500) NOT NULL,
  `reference_type` VARCHAR(40) NULL DEFAULT NULL,
  `reference_id`   BIGINT UNSIGNED NULL DEFAULT NULL,
  `is_read`        TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_read` (`user_id`,`is_read`),
  KEY `idx_type` (`type`),
  -- One rank notification per (user, rank) — keeps the hourly cron idempotent.
  UNIQUE KEY `uq_ref` (`user_id`,`type`,`reference_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 8b. rank_cron_state — batch cursor + run lock for the hourly cron.
--     The cron walks active members 500 at a time across invocations; `cursor`
--     is how far the current sweep has got. `running` + `heartbeat` stop two
--     overlapping runs from double-processing a batch (a stale lock older than
--     30 minutes is treated as a crashed run and reclaimed). NB: `cursor` is a
--     reserved word in MariaDB, hence cursor_pos.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rank_cron_state` (
  `job`            VARCHAR(40) NOT NULL,
  `cursor_pos`     INT UNSIGNED NOT NULL DEFAULT 0,
  `sweep_no`       INT UNSIGNED NOT NULL DEFAULT 0,
  `running`        TINYINT(1) NOT NULL DEFAULT 0,
  `heartbeat`      DATETIME NULL DEFAULT NULL,
  `last_run_at`    DATETIME NULL DEFAULT NULL,
  `last_result`    VARCHAR(255) NULL DEFAULT NULL,
  `total_promoted` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`job`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `rank_cron_state` (`job`) VALUES ('rank_achievement'), ('rank_power');

-- ----------------------------------------------------------------------------
-- 9. Convenience views (read-only; no new source of truth)
-- ----------------------------------------------------------------------------
CREATE OR REPLACE VIEW `vw_user_rank_power` AS
SELECT p.`id`, p.`user_id`, p.`cycle_id`, c.`cycle_no`, c.`start_date`, c.`end_date`, c.`status` AS cycle_status,
       p.`power_rank_id`, r.`tier_level` AS power_tier, r.`name` AS power_rank,
       p.`left_volume`, p.`right_volume`, p.`total_volume`, p.`qualified`,
       p.`achieved_at`, p.`calculated_at`
FROM `user_rank_power` p
JOIN `staking_rank_power_cycles` c ON c.`id` = p.`cycle_id`
LEFT JOIN `staking_ranks` r ON r.`id` = p.`power_rank_id`;

CREATE OR REPLACE VIEW `vw_user_ranks` AS
SELECT u.`id` AS user_id, u.`username`, u.`email`, u.`status` AS user_status,
       ur.`current_rank_id`, cr.`name` AS current_rank, cr.`tier_level` AS current_tier,
       ur.`highest_rank_id`, hr.`name` AS highest_rank, hr.`tier_level` AS highest_tier,
       hr.`badge_image`, hr.`badge_color`,
       ur.`group_volume`, ur.`achieved_at`, ur.`updated_at`
FROM `users` u
LEFT JOIN `user_ranks` ur ON ur.`user_id` = u.`id`
LEFT JOIN `staking_ranks` cr ON cr.`id` = ur.`current_rank_id`
LEFT JOIN `staking_ranks` hr ON hr.`id` = ur.`highest_rank_id`;

-- ----------------------------------------------------------------------------
-- 10. Supporting indexes for the volume query (downline BMAN, completed only)
-- ----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS _rk_add_idx;
DELIMITER //
CREATE PROCEDURE _rk_add_idx(IN tbl VARCHAR(64), IN idx VARCHAR(64), IN ddl VARCHAR(255))
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tbl AND INDEX_NAME = idx) = 0 THEN
    SET @s := CONCAT('ALTER TABLE `', tbl, '` ADD ', ddl);
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END//
DELIMITER ;
CALL _rk_add_idx('staking_swap_orders', 'idx_rank_volume',
                 "INDEX `idx_rank_volume` (`user_id`,`status`,`cron_status`)");
CALL _rk_add_idx('staking_swap_orders', 'idx_rank_volume_dated',
                 "INDEX `idx_rank_volume_dated` (`status`,`cron_status`,`updated_at`)");
CALL _rk_add_idx('binary_placement', 'idx_parent_pos',
                 "INDEX `idx_parent_pos` (`parent_id`,`position`)");
CALL _rk_add_idx('binary_placement', 'idx_user', "INDEX `idx_user` (`user_id`)");
DROP PROCEDURE IF EXISTS _rk_add_idx;
