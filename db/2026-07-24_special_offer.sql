-- ============================================================================
-- SPECIAL OFFER PLANS
-- Selected packages (e.g. 50,000 / 100,000 BMAN) can be flagged as "Special
-- Offer". Special packages use a year-wise ROI structure (a Monthly ROI % AND
-- a Maturity % per year, years 1..7) instead of the normal staking_roi_structure
-- matrix. Normal packages are completely unaffected (backward compatible).
-- ============================================================================

-- 1. Mark a package as a Special Offer package.
ALTER TABLE `staking_packages`
  ADD COLUMN `is_special` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`;

-- 2. Year-wise ROI structure for Special Offer packages.
CREATE TABLE IF NOT EXISTS `staking_special_roi` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `package_id`          INT UNSIGNED NOT NULL,
  `year_number`         TINYINT UNSIGNED NOT NULL,           -- 1..7
  `monthly_roi_percent` DECIMAL(8,3) NOT NULL DEFAULT 0.000, -- % per month for that year
  `maturity_percent`    DECIMAL(8,3) NOT NULL DEFAULT 0.000, -- maturity bonus % for choosing this term
  `is_active`           TINYINT(1) NOT NULL DEFAULT 1,       -- is this year offered
  `created_at`          DATETIME NULL,
  `updated_at`          DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pkg_year` (`package_id`, `year_number`),
  KEY `idx_pkg` (`package_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Audit every change to the Special ROI structure.
CREATE TABLE IF NOT EXISTS `staking_special_roi_audit` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `package_id`  INT UNSIGNED NOT NULL,
  `year_number` TINYINT UNSIGNED NOT NULL,
  `field`       VARCHAR(40) NOT NULL,   -- monthly_roi_percent | maturity_percent | is_active | package_special
  `old_value`   VARCHAR(64) NULL,
  `new_value`   VARCHAR(64) NULL,
  `changed_by`  INT NULL,
  `created_at`  DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pkg` (`package_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Snapshot the special flag + maturity % onto the stake / ROI record so a
--    later admin edit never retroactively changes an existing stake's terms.
ALTER TABLE `user_stakes`
  ADD COLUMN `is_special` TINYINT(1) NOT NULL DEFAULT 0 AFTER `roi_basis`;

ALTER TABLE `roi_staking_management`
  ADD COLUMN `is_special` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN `special_maturity_percent` DECIMAL(8,3) NOT NULL DEFAULT 0.000;
