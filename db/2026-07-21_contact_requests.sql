-- ============================================================================
-- Public "Contact Us / Account Unlock" requests.
-- Reachable by logged-out AND frozen users (login is blocked for a frozen
-- account, so this is their only way to reach support). Admin reviews these
-- at admin/contact-requests and can unlock a frozen account directly from
-- the review screen.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `contact_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT NULL DEFAULT NULL COMMENT 'Resolved from email at submit time, if it matches an existing account',
  `email` VARCHAR(190) NOT NULL,
  `message` TEXT NOT NULL,
  `attachment_path` VARCHAR(255) NULL DEFAULT NULL,
  `status` ENUM('pending','resolved') NOT NULL DEFAULT 'pending',
  `admin_notes` VARCHAR(500) NULL DEFAULT NULL,
  `resolved_by` INT NULL DEFAULT NULL,
  `resolved_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
