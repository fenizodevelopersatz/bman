-- Announcement banner redesign: additive migration for dashboard-only banners.
-- Keep legacy columns in place for backward compatibility; new code should use
-- these fields where available.

ALTER TABLE `announcement`
  ADD COLUMN `announcement_title` VARCHAR(255) NULL DEFAULT NULL AFTER `id`,
  ADD COLUMN `priority_level` TINYINT NOT NULL DEFAULT 2 AFTER `announcement_title`,
  ADD COLUMN `rotation_enabled` TINYINT(1) NOT NULL DEFAULT 1 AFTER `priority_level`,
  ADD COLUMN `start_time` TIME NULL DEFAULT NULL AFTER `start_date`,
  ADD COLUMN `end_time` TIME NULL DEFAULT NULL AFTER `start_time`,
  ADD COLUMN `timezone` VARCHAR(64) NULL DEFAULT 'Asia/Kolkata' AFTER `end_time`,
  ADD COLUMN `status` ENUM('draft','scheduled','active','expired','paused') NOT NULL DEFAULT 'draft' AFTER `timezone`,
  ADD COLUMN `created_by` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `created_date`,
  ADD COLUMN `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `created_by`;

CREATE TABLE IF NOT EXISTS `announcement_stats` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `announcement_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `viewed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `clicked_at` DATETIME NULL DEFAULT NULL,
  `device_type` VARCHAR(32) NULL DEFAULT NULL,
  `source` VARCHAR(32) NULL DEFAULT 'dashboard',
  PRIMARY KEY (`id`),
  KEY `idx_announcement` (`announcement_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `announcement_audit_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `announcement_id` BIGINT UNSIGNED NOT NULL,
  `action` VARCHAR(32) NOT NULL,
  `changed_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `changed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `payload` JSON NULL,
  PRIMARY KEY (`id`),
  KEY `idx_announcement_audit` (`announcement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
