-- ============================================================================
-- BMAN Staking Module — Rank Power System (§11) & Group Incentive support
-- Source: BMAN STAKING MASTER PROPOSAL DETAILS.pdf §11 (Rank Power) + §12
-- (Group Incentive Ceiling — ceiling values live on staking_packages).
-- Idempotent: safe to re-run. Companion to db/staking_module.sql.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. Rank Power settings (single-row config)
--    §11 rules: separate from Achievement Rank · resets every 60 days ·
--    controls Group Incentive qualification.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staking_rank_power_settings` (
  `id` TINYINT UNSIGNED NOT NULL,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `cycle_days` INT NOT NULL DEFAULT 60,              -- reset period (§11)
  `controls_group_incentive` TINYINT(1) NOT NULL DEFAULT 1,
  `min_power_tier` TINYINT NOT NULL DEFAULT 0,       -- minimum power tier to qualify (0 = any)
  `auto_open_next_cycle` TINYINT(1) NOT NULL DEFAULT 1, -- cron rolls cycles automatically
  `updated_by` INT DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `staking_rank_power_settings`
(`id`,`is_enabled`,`cycle_days`,`controls_group_incentive`,`min_power_tier`,`auto_open_next_cycle`)
VALUES (1, 1, 60, 1, 0, 1);

-- ----------------------------------------------------------------------------
-- 2. Rank Power cycles (each 60-day window; power resets when a cycle closes)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staking_rank_power_cycles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cycle_no` INT NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `status` ENUM('open','closed') NOT NULL DEFAULT 'open',
  `opened_by` INT DEFAULT NULL,                      -- admin id (manual) / NULL (cron)
  `closed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cycle_no` (`cycle_no`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 3. Per-user power rank within a cycle (filled by the evaluation engine;
--    admins get a read-only viewer). Resets by starting a new cycle.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_rank_power` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `cycle_id` INT UNSIGNED NOT NULL,
  `power_rank_id` INT UNSIGNED DEFAULT NULL,         -- FK staking_ranks (tier scale reused)
  `qualified` TINYINT(1) NOT NULL DEFAULT 0,         -- group-incentive qualification this cycle
  `achieved_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_cycle` (`user_id`,`cycle_id`),
  KEY `idx_cycle` (`cycle_id`),
  CONSTRAINT `fk_power_cycle` FOREIGN KEY (`cycle_id`) REFERENCES `staking_rank_power_cycles`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
