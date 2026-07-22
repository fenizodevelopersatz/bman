-- ============================================================================
-- Per-admin "last seen" timestamp per sidebar badge category (withdrawals,
-- kyc, support). Sidebar badge count = pending items created AFTER the
-- admin's last visit to that section — visiting the page clears the badge
-- until new items arrive, instead of just mirroring a live pending count.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `admin_badge_seen` (
  `admin_id`     INT NOT NULL,
  `category`     VARCHAR(40) NOT NULL,
  `last_seen_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`, `category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
