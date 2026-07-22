-- ============================================================================
-- Per-user last-read cursor per chat room/peer — powers the unread-count
-- badge on the header chat icon. peer_id = 0 is the sentinel for the
-- non-personal rooms (world/team), which have no single peer.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `chat_read_state` (
  `user_id`      INT UNSIGNED NOT NULL,
  `room`         VARCHAR(30) NOT NULL,
  `peer_id`      INT UNSIGNED NOT NULL DEFAULT 0,
  `last_read_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `room`, `peer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
