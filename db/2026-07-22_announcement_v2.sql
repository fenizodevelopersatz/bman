-- ============================================================================
-- Announcement Management System v2 — extends the existing `announcement`
-- table (kept, not renamed — it already has working code + live rows on
-- title/announcement_type/bg_color/image) with subtitle/description, a real
-- button, priority, scheduling, audience targeting, popup mode, and
-- view/click analytics. `announcement_type` is widened in place to add
-- 'text_image' as a third render mode (backward compatible with existing
-- 'text'/'image' rows).
-- ============================================================================

ALTER TABLE `announcement`
  MODIFY COLUMN `announcement_type` ENUM('text','image','text_image') NOT NULL DEFAULT 'text',
  ADD COLUMN `subtitle` VARCHAR(250) NULL DEFAULT NULL AFTER `title`,
  ADD COLUMN `description` TEXT NULL AFTER `subtitle`,
  ADD COLUMN `category` ENUM('general','alert','promotion','maintenance','event','rank_news') NOT NULL DEFAULT 'general' AFTER `announcement_type`,
  ADD COLUMN `text_color` VARCHAR(20) NOT NULL DEFAULT '#ffffff' AFTER `bg_color`,
  ADD COLUMN `button_text` VARCHAR(100) NULL DEFAULT NULL AFTER `image`,
  ADD COLUMN `button_url` VARCHAR(255) NULL DEFAULT NULL AFTER `button_text`,
  ADD COLUMN `priority` ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium' AFTER `button_url`,
  ADD COLUMN `display_mode` ENUM('banner','popup','both') NOT NULL DEFAULT 'banner' AFTER `priority`,
  ADD COLUMN `target_type` ENUM('all','active','inactive','kyc_pending','kyc_approved','rank','package','country') NOT NULL DEFAULT 'all' AFTER `display_mode`,
  ADD COLUMN `target_value` VARCHAR(100) NULL DEFAULT NULL AFTER `target_type`,
  ADD COLUMN `start_date` DATE NULL DEFAULT NULL AFTER `target_value`,
  ADD COLUMN `end_date` DATE NULL DEFAULT NULL AFTER `start_date`,
  ADD COLUMN `views` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `end_date`,
  ADD COLUMN `clicks` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `views`;

-- Per-user popup dismiss tracking — same lightweight pattern as
-- admin_badge_seen / chat_read_state added earlier.
CREATE TABLE IF NOT EXISTS `announcement_dismissals` (
  `announcement_id` INT NOT NULL,
  `user_id`          INT NOT NULL,
  `dismissed_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`announcement_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
