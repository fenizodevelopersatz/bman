-- ============================================================================
-- BMAN Staking Module — Packages, Plans, ROI Structure & Rank Achievement
-- Source of truth: docs/6_STAKING_PACKAGES_PLANS_ROI.md (§4 DDL, §5 seed)
--                  + BMAN STAKING MASTER PROPOSAL DETAILS.pdf (§4–§7, §10, §12)
-- Idempotent: safe to re-run (CREATE IF NOT EXISTS + keyed upsert seeds).
-- Legacy tables (package_config, user_investment, rank_config) stay untouched.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 4.1 Packages (fixed stake amounts, §4 + §7 bonus + §12 group ceiling)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staking_packages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL,
  `stake_amount` DECIMAL(20,4) NOT NULL,
  `bonus_percent` DECIMAL(6,2) NOT NULL DEFAULT 25.00,
  `group_ceiling` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_amount` (`stake_amount`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 4.2 Plans (Fixed / Regular / Combo, §5)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staking_plans` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(60) NOT NULL,
  `code` ENUM('fixed','regular','combo') NOT NULL,
  `roi_credit_mode` ENUM('maturity','monthly','mixed') NOT NULL,
  `credit_days` VARCHAR(40) DEFAULT NULL,            -- "5,15,25"
  `withdraw_after_maturity` TINYINT(1) NOT NULL DEFAULT 0,
  `withdraw_frequency_days` INT NOT NULL DEFAULT 0,  -- 30 for Regular
  `min_withdraw_bman` DECIMAL(20,4) DEFAULT NULL,    -- 3000
  `max_withdraw_bman` DECIMAL(20,4) DEFAULT NULL,    -- 10000
  `min_withdraw_usdt` DECIMAL(20,4) DEFAULT NULL,    -- 30
  `max_withdraw_usdt` DECIMAL(20,4) DEFAULT NULL,    -- 100
  `combo_fixed_pct` DECIMAL(6,2) DEFAULT NULL,       -- 50
  `combo_regular_pct` DECIMAL(6,2) DEFAULT NULL,     -- 50
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 4.3 Durations available per plan (2 / 3 / 5 years)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staking_plan_terms` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_id` INT UNSIGNED NOT NULL,
  `duration_years` TINYINT NOT NULL,                 -- 2,3,5
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_plan_term` (`plan_id`,`duration_years`),
  CONSTRAINT `fk_term_plan` FOREIGN KEY (`plan_id`) REFERENCES `staking_plans`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 4.4 ROI matrix (package x plan x duration), effective-dated + versioned
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staking_roi_structure` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `package_id` INT UNSIGNED NOT NULL,
  `plan_code` ENUM('fixed','regular') NOT NULL,
  `duration_years` TINYINT NOT NULL,
  `roi_percent` DECIMAL(8,3) NOT NULL,
  `roi_basis` ENUM('total','monthly') NOT NULL,
  `effective_from` DATE NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lookup` (`package_id`,`plan_code`,`duration_years`,`is_active`,`effective_from`),
  CONSTRAINT `fk_roi_pkg` FOREIGN KEY (`package_id`) REFERENCES `staking_packages`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 4.5 Audit trail for ROI edits (Super-Admin only, per proposal)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staking_roi_audit` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `roi_id` INT UNSIGNED DEFAULT NULL,
  `package_id` INT UNSIGNED NOT NULL,
  `plan_code` VARCHAR(10) NOT NULL,
  `duration_years` TINYINT NOT NULL,
  `old_percent` DECIMAL(8,3) DEFAULT NULL,
  `new_percent` DECIMAL(8,3) NOT NULL,
  `changed_by` INT NOT NULL,
  `note` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 4.6 User stakes (ROI snapshotted at purchase — locked forever)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_stakes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `package_id` INT UNSIGNED NOT NULL,
  `plan_id` INT UNSIGNED NOT NULL,
  `plan_code` ENUM('fixed','regular','combo') NOT NULL,
  `duration_years` TINYINT NOT NULL,
  `stake_amount` DECIMAL(20,4) NOT NULL,
  `roi_percent` DECIMAL(8,3) NOT NULL,               -- snapshot
  `roi_basis` ENUM('total','monthly') NOT NULL,
  `bonus_amount` DECIMAL(20,4) NOT NULL DEFAULT 0,   -- 25% bonus coin
  `distribution_option_id` INT DEFAULT NULL,         -- §3A coin distribution
  `start_date` DATE NOT NULL,
  `maturity_date` DATE NOT NULL,
  `status` ENUM('active','matured','withdrawn','cancelled') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 4.7 ROI credit history (maturity or monthly 5/15/25)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staking_roi_payouts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stake_id` BIGINT UNSIGNED NOT NULL,
  `user_id` INT NOT NULL,
  `amount` DECIMAL(20,4) NOT NULL,
  `credit_date` DATE NOT NULL,
  `wallet` ENUM('earning','staking') NOT NULL DEFAULT 'earning',
  `status` ENUM('pending','paid','failed') NOT NULL DEFAULT 'paid',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stake` (`stake_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 4.8 Rank Achievement — permanent ranks (proposal §10)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staking_ranks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tier_level` TINYINT NOT NULL,                     -- 0 (UN RANK) .. 10 (CHALLENGER)
  `name` VARCHAR(40) NOT NULL,
  `group_incentive` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `benefit_badge` TINYINT(1) NOT NULL DEFAULT 1,
  `benefit_certificate` TINYINT(1) NOT NULL DEFAULT 1,
  `benefit_reward` TINYINT(1) NOT NULL DEFAULT 1,
  `benefit_recognition` TINYINT(1) NOT NULL DEFAULT 1,
  `badge_color` VARCHAR(20) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tier` (`tier_level`),
  UNIQUE KEY `uq_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 4.9 Rank qualification requirements (proposal §10 matrix)
--   Each rank can qualify via Plan-1 / Plan-2 / Plan-3 (OR alternatives).
--   Rows within the same (rank, plan_no, option_no) are AND-ed (left + right).
--   option_no allows OR options inside one plan (e.g. PLATINUM Plan-1:
--   "L2 GOLD + R1 GOLD" OR "L1 GOLD + R2 GOLD").
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staking_rank_requirements` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rank_id` INT UNSIGNED NOT NULL,
  `plan_no` TINYINT NOT NULL DEFAULT 1,              -- 1,2,3
  `option_no` TINYINT NOT NULL DEFAULT 1,            -- OR-group inside a plan
  `side` ENUM('left','right') NOT NULL,
  `required_qty` INT NOT NULL,
  `required_rank_id` INT UNSIGNED NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_req` (`rank_id`,`plan_no`,`option_no`,`side`),
  CONSTRAINT `fk_req_rank` FOREIGN KEY (`rank_id`) REFERENCES `staking_ranks`(`id`),
  CONSTRAINT `fk_req_needs` FOREIGN KEY (`required_rank_id`) REFERENCES `staking_ranks`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- SEED DATA (doc §5 + proposal §10) — keyed upserts, safe to re-run
-- ============================================================================

-- 5.1 Packages (bonus 25% §7, ceiling §12)
INSERT INTO `staking_packages` (`name`,`stake_amount`,`bonus_percent`,`group_ceiling`,`sort_order`,`is_active`) VALUES
('5,000 BMAN',     5000,   25, 5000,   1, 1),
('10,000 BMAN',    10000,  25, 10000,  2, 1),
('20,000 BMAN',    20000,  25, 20000,  3, 1),
('25,000 BMAN',    25000,  25, 25000,  4, 1),
('50,000 BMAN',    50000,  25, 30000,  5, 1),
('100,000 BMAN',   100000, 25, 30000,  6, 1),
('200,000 BMAN',   200000, 25, 50000,  7, 1),
('300,000 BMAN',   300000, 25, 70000,  8, 1),
('500,000 BMAN',   500000, 25, 100000, 9, 1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `sort_order`=VALUES(`sort_order`);

-- 5.2 Plans (§5)
INSERT INTO `staking_plans`
(`name`,`code`,`roi_credit_mode`,`credit_days`,`withdraw_after_maturity`,`withdraw_frequency_days`,
 `min_withdraw_bman`,`max_withdraw_bman`,`min_withdraw_usdt`,`max_withdraw_usdt`,
 `combo_fixed_pct`,`combo_regular_pct`,`sort_order`,`is_active`) VALUES
('Fixed Plan',  'fixed',  'maturity', NULL,      1, 0,  NULL, NULL,  NULL, NULL, NULL, NULL, 1, 1),
('Regular Plan','regular','monthly',  '5,15,25', 0, 30, 3000, 10000, 30,   100,  NULL, NULL, 2, 1),
('Combo Plan',  'combo',  'mixed',    '5,15,25', 0, 30, 3000, 10000, 30,   100,  50,   50,   3, 1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `sort_order`=VALUES(`sort_order`);

-- Durations 2/3/5 for every plan
INSERT IGNORE INTO `staking_plan_terms` (`plan_id`,`duration_years`,`is_active`)
SELECT p.id, d.yrs, 1
FROM `staking_plans` p
JOIN (SELECT 2 AS yrs UNION SELECT 3 UNION SELECT 5) d;

-- 5.3 ROI matrix (§6). Fixed = total % over term, Regular = % per month.
-- Insert only when the cell has no active row yet (protects admin edits on re-run).
INSERT INTO `staking_roi_structure`
(`package_id`,`plan_code`,`duration_years`,`roi_percent`,`roi_basis`,`effective_from`,`is_active`)
SELECT pk.id, m.plan_code, m.dur, m.pct, m.basis, CURDATE(), 1
FROM (
  -- Fixed (total %)
  SELECT 5000 amt,   'fixed' plan_code, 2 dur, 150.000 pct, 'total' basis UNION ALL
  SELECT 5000,   'fixed', 3, 200.000, 'total' UNION ALL
  SELECT 5000,   'fixed', 5, 400.000, 'total' UNION ALL
  SELECT 10000,  'fixed', 2, 150.000, 'total' UNION ALL
  SELECT 10000,  'fixed', 3, 200.000, 'total' UNION ALL
  SELECT 10000,  'fixed', 5, 400.000, 'total' UNION ALL
  SELECT 20000,  'fixed', 2, 150.000, 'total' UNION ALL
  SELECT 20000,  'fixed', 3, 200.000, 'total' UNION ALL
  SELECT 20000,  'fixed', 5, 400.000, 'total' UNION ALL
  SELECT 25000,  'fixed', 2, 150.000, 'total' UNION ALL
  SELECT 25000,  'fixed', 3, 200.000, 'total' UNION ALL
  SELECT 25000,  'fixed', 5, 400.000, 'total' UNION ALL
  SELECT 50000,  'fixed', 2, 150.000, 'total' UNION ALL
  SELECT 50000,  'fixed', 3, 200.000, 'total' UNION ALL
  SELECT 50000,  'fixed', 5, 400.000, 'total' UNION ALL
  SELECT 100000, 'fixed', 2, 150.000, 'total' UNION ALL
  SELECT 100000, 'fixed', 3, 200.000, 'total' UNION ALL
  SELECT 100000, 'fixed', 5, 400.000, 'total' UNION ALL
  SELECT 200000, 'fixed', 2, 160.000, 'total' UNION ALL
  SELECT 200000, 'fixed', 3, 210.000, 'total' UNION ALL
  SELECT 200000, 'fixed', 5, 410.000, 'total' UNION ALL
  SELECT 300000, 'fixed', 2, 180.000, 'total' UNION ALL
  SELECT 300000, 'fixed', 3, 230.000, 'total' UNION ALL
  SELECT 300000, 'fixed', 5, 430.000, 'total' UNION ALL
  SELECT 500000, 'fixed', 2, 200.000, 'total' UNION ALL
  SELECT 500000, 'fixed', 3, 250.000, 'total' UNION ALL
  SELECT 500000, 'fixed', 5, 450.000, 'total' UNION ALL
  -- Regular (monthly %)
  SELECT 5000,   'regular', 2, 2.300, 'monthly' UNION ALL
  SELECT 5000,   'regular', 3, 2.500, 'monthly' UNION ALL
  SELECT 5000,   'regular', 5, 3.000, 'monthly' UNION ALL
  SELECT 10000,  'regular', 2, 2.300, 'monthly' UNION ALL
  SELECT 10000,  'regular', 3, 2.500, 'monthly' UNION ALL
  SELECT 10000,  'regular', 5, 3.000, 'monthly' UNION ALL
  SELECT 20000,  'regular', 2, 2.300, 'monthly' UNION ALL
  SELECT 20000,  'regular', 3, 2.500, 'monthly' UNION ALL
  SELECT 20000,  'regular', 5, 3.000, 'monthly' UNION ALL
  SELECT 25000,  'regular', 2, 2.300, 'monthly' UNION ALL
  SELECT 25000,  'regular', 3, 2.500, 'monthly' UNION ALL
  SELECT 25000,  'regular', 5, 3.000, 'monthly' UNION ALL
  SELECT 50000,  'regular', 2, 2.300, 'monthly' UNION ALL
  SELECT 50000,  'regular', 3, 2.500, 'monthly' UNION ALL
  SELECT 50000,  'regular', 5, 3.000, 'monthly' UNION ALL
  SELECT 100000, 'regular', 2, 2.300, 'monthly' UNION ALL
  SELECT 100000, 'regular', 3, 2.500, 'monthly' UNION ALL
  SELECT 100000, 'regular', 5, 3.000, 'monthly' UNION ALL
  SELECT 200000, 'regular', 2, 2.500, 'monthly' UNION ALL
  SELECT 200000, 'regular', 3, 3.200, 'monthly' UNION ALL
  SELECT 200000, 'regular', 5, 3.200, 'monthly' UNION ALL
  SELECT 300000, 'regular', 2, 2.800, 'monthly' UNION ALL
  SELECT 300000, 'regular', 3, 3.300, 'monthly' UNION ALL
  SELECT 300000, 'regular', 5, 3.300, 'monthly' UNION ALL
  SELECT 500000, 'regular', 2, 3.000, 'monthly' UNION ALL
  SELECT 500000, 'regular', 3, 3.500, 'monthly' UNION ALL
  SELECT 500000, 'regular', 5, 3.500, 'monthly'
) m
JOIN `staking_packages` pk ON pk.stake_amount = m.amt
WHERE NOT EXISTS (
  SELECT 1 FROM `staking_roi_structure` r
  WHERE r.package_id = pk.id AND r.plan_code = m.plan_code
    AND r.duration_years = m.dur AND r.is_active = 1
);

-- 5.4 Ranks (proposal §10 — group incentive in BMAN; Indian-notation converted)
INSERT INTO `staking_ranks` (`tier_level`,`name`,`group_incentive`,`badge_color`,`is_active`) VALUES
(0,  'UN RANK',     1000,     '#9e9e9e', 1),
(1,  'IRON',        7500,     '#7f8c8d', 1),
(2,  'BRONZE',      30000,    '#cd7f32', 1),
(3,  'SILVER',      150000,   '#c0c0c0', 1),
(4,  'GOLD',        600000,   '#ffd700', 1),
(5,  'PLATINUM',    2500000,  '#7ad7f0', 1),
(6,  'EMERALD',     10000000, '#50c878', 1),
(7,  'DIAMOND',     20000000, '#b9f2ff', 1),
(8,  'MASTER',      30000000, '#9b59b6', 1),
(9,  'GRANDMASTER', 40000000, '#e74c3c', 1),
(10, 'CHALLENGER',  50000000, '#f1c40f', 1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 5.5 Rank requirements (proposal §10 matrix; qty + rank per side, per plan)
INSERT IGNORE INTO `staking_rank_requirements`
(`rank_id`,`plan_no`,`option_no`,`side`,`required_qty`,`required_rank_id`,`is_active`)
SELECT tgt.id, m.plan_no, m.option_no, m.side, m.qty, req.id, 1
FROM (
  -- IRON: Plan-1 L2 UN RANK + R2 UN RANK
  SELECT 1 tier, 1 plan_no, 1 option_no, 'left'  side, 2  qty, 0 req_tier UNION ALL
  SELECT 1, 1, 1, 'right', 2,  0 UNION ALL
  -- BRONZE
  SELECT 2, 1, 1, 'left',  2,  1 UNION ALL
  SELECT 2, 1, 1, 'right', 2,  1 UNION ALL
  SELECT 2, 2, 1, 'left',  2,  1 UNION ALL
  SELECT 2, 2, 1, 'right', 12, 0 UNION ALL
  SELECT 2, 3, 1, 'left',  12, 0 UNION ALL
  SELECT 2, 3, 1, 'right', 2,  1 UNION ALL
  -- SILVER
  SELECT 3, 1, 1, 'left',  2,  2 UNION ALL
  SELECT 3, 1, 1, 'right', 2,  2 UNION ALL
  SELECT 3, 2, 1, 'left',  2,  2 UNION ALL
  SELECT 3, 2, 1, 'right', 12, 1 UNION ALL
  SELECT 3, 3, 1, 'left',  12, 1 UNION ALL
  SELECT 3, 3, 1, 'right', 2,  2 UNION ALL
  -- GOLD
  SELECT 4, 1, 1, 'left',  2,  3 UNION ALL
  SELECT 4, 1, 1, 'right', 2,  3 UNION ALL
  SELECT 4, 2, 1, 'left',  2,  3 UNION ALL
  SELECT 4, 2, 1, 'right', 12, 2 UNION ALL
  SELECT 4, 3, 1, 'left',  12, 2 UNION ALL
  SELECT 4, 3, 1, 'right', 2,  3 UNION ALL
  -- PLATINUM (Plan-1 has two OR options: L2+R1 GOLD  or  L1+R2 GOLD)
  SELECT 5, 1, 1, 'left',  2,  4 UNION ALL
  SELECT 5, 1, 1, 'right', 1,  4 UNION ALL
  SELECT 5, 1, 2, 'left',  1,  4 UNION ALL
  SELECT 5, 1, 2, 'right', 2,  4 UNION ALL
  SELECT 5, 2, 1, 'left',  2,  4 UNION ALL
  SELECT 5, 2, 1, 'right', 6,  3 UNION ALL
  SELECT 5, 3, 1, 'left',  6,  3 UNION ALL
  SELECT 5, 3, 1, 'right', 2,  4 UNION ALL
  -- EMERALD
  SELECT 6, 1, 1, 'left',  1,  5 UNION ALL
  SELECT 6, 1, 1, 'right', 1,  5 UNION ALL
  SELECT 6, 2, 1, 'left',  1,  5 UNION ALL
  SELECT 6, 2, 1, 'right', 4,  4 UNION ALL
  SELECT 6, 3, 1, 'left',  4,  4 UNION ALL
  SELECT 6, 3, 1, 'right', 1,  5 UNION ALL
  -- DIAMOND
  SELECT 7, 1, 1, 'left',  1,  6 UNION ALL
  SELECT 7, 1, 1, 'right', 1,  6 UNION ALL
  SELECT 7, 2, 1, 'left',  1,  6 UNION ALL
  SELECT 7, 2, 1, 'right', 3,  5 UNION ALL
  SELECT 7, 3, 1, 'left',  3,  5 UNION ALL
  SELECT 7, 3, 1, 'right', 1,  6 UNION ALL
  -- MASTER
  SELECT 8, 1, 1, 'left',  1,  7 UNION ALL
  SELECT 8, 1, 1, 'right', 1,  7 UNION ALL
  SELECT 8, 2, 1, 'left',  1,  7 UNION ALL
  SELECT 8, 2, 1, 'right', 3,  6 UNION ALL
  SELECT 8, 3, 1, 'left',  3,  6 UNION ALL
  SELECT 8, 3, 1, 'right', 1,  7 UNION ALL
  -- GRANDMASTER
  SELECT 9, 1, 1, 'left',  1,  8 UNION ALL
  SELECT 9, 1, 1, 'right', 1,  8 UNION ALL
  SELECT 9, 2, 1, 'left',  1,  8 UNION ALL
  SELECT 9, 2, 1, 'right', 3,  7 UNION ALL
  SELECT 9, 3, 1, 'left',  3,  7 UNION ALL
  SELECT 9, 3, 1, 'right', 1,  8 UNION ALL
  -- CHALLENGER
  SELECT 10, 1, 1, 'left',  1, 9 UNION ALL
  SELECT 10, 1, 1, 'right', 1, 9 UNION ALL
  SELECT 10, 2, 1, 'left',  1, 9 UNION ALL
  SELECT 10, 2, 1, 'right', 3, 8 UNION ALL
  SELECT 10, 3, 1, 'left',  3, 8 UNION ALL
  SELECT 10, 3, 1, 'right', 1, 9
) m
JOIN `staking_ranks` tgt ON tgt.tier_level = m.tier
JOIN `staking_ranks` req ON req.tier_level = m.req_tier;
