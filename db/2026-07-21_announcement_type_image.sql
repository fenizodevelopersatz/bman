-- ============================================================================
-- Extends the Announcement CMS so each announcement is either a text banner
-- (custom background color) or an image banner (admin-cropped image), shown
-- in the rotating announcement box on the user dashboard (user/main).
-- ============================================================================

ALTER TABLE `announcement`
  ADD COLUMN `announcement_type` ENUM('text','image') NOT NULL DEFAULT 'text' AFTER `title`,
  ADD COLUMN `bg_color` VARCHAR(20) NULL DEFAULT NULL AFTER `announcement_type`,
  ADD COLUMN `image` VARCHAR(255) NULL DEFAULT NULL AFTER `bg_color`;
