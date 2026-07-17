-- ============================================================================
-- Member Chat — `chat_messages`
-- ----------------------------------------------------------------------------
-- WHY THIS FILE EXISTS
-- The chat feature shipped without a migration. `chat_messages` was created by
-- hand on the dev database and appears in exactly one place in the repo —
-- application/models/Chat_model.php ($table = 'chat_messages'). Any fresh
-- deploy (or any new developer) therefore got a fatal on /user/chat.
-- This captures the live schema so the table is reproducible.
--
-- Rooms:
--   world     — everyone; to_user_id IS NULL
--   team      — genealogy path (upline ∪ downline); to_user_id IS NULL
--   personal  — 1:1 DM; to_user_id = the peer
--
-- Idempotent: safe to re-run. Additive only — drops nothing.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room`         VARCHAR(30) NOT NULL DEFAULT 'personal' COMMENT 'world | team | personal',
  `user_id`      BIGINT UNSIGNED NOT NULL COMMENT 'sender',
  `to_user_id`   INT DEFAULT NULL COMMENT 'recipient (personal only; NULL for world/team)',
  `peer_id`      INT DEFAULT NULL COMMENT 'legacy mirror of to_user_id — see note below',
  `username`     VARCHAR(80) NOT NULL COMMENT 'denormalised sender name at send time',
  `message`      TEXT NOT NULL,
  `message_type` ENUM('text','image','file') NOT NULL DEFAULT 'text',
  `file_url`     VARCHAR(255) DEFAULT NULL,
  `file_name`    VARCHAR(255) DEFAULT NULL,
  `mime_type`    VARCHAR(100) DEFAULT NULL,
  `file_size`    INT DEFAULT NULL COMMENT 'bytes',
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  -- cursor polling: WHERE room = ? AND id > ?
  KEY `idx_room_id` (`room`,`id`),
  -- personal threads, both directions
  KEY `idx_room_user_to` (`room`,`user_id`,`to_user_id`,`id`),
  KEY `idx_room_to_user` (`room`,`to_user_id`,`user_id`,`id`),
  -- legacy peer_id lookups
  KEY `idx_room_peer` (`room`,`peer_id`),
  KEY `idx_room_user_peer` (`room`,`user_id`,`peer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Member chat: world / team / personal rooms';

-- ----------------------------------------------------------------------------
-- Bring an existing hand-made table up to spec (idempotent column adds).
-- ----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS _cm_add_col;
DELIMITER //
CREATE PROCEDURE _cm_add_col(IN col VARCHAR(64), IN ddl VARCHAR(255))
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'chat_messages'
        AND COLUMN_NAME = col) = 0 THEN
    SET @s := CONCAT('ALTER TABLE `chat_messages` ADD COLUMN ', ddl);
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END//
DELIMITER ;
CALL _cm_add_col('message_type', "`message_type` ENUM('text','image','file') NOT NULL DEFAULT 'text'");
CALL _cm_add_col('file_url',  "`file_url` VARCHAR(255) DEFAULT NULL");
CALL _cm_add_col('file_name', "`file_name` VARCHAR(255) DEFAULT NULL");
CALL _cm_add_col('mime_type', "`mime_type` VARCHAR(100) DEFAULT NULL");
CALL _cm_add_col('file_size', "`file_size` INT DEFAULT NULL");
CALL _cm_add_col('to_user_id', "`to_user_id` INT DEFAULT NULL");
DROP PROCEDURE IF EXISTS _cm_add_col;

-- ----------------------------------------------------------------------------
-- Indexes the polling queries actually need (idempotent).
-- ----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS _cm_add_idx;
DELIMITER //
CREATE PROCEDURE _cm_add_idx(IN idx VARCHAR(64), IN ddl VARCHAR(255))
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'chat_messages'
        AND INDEX_NAME = idx) = 0 THEN
    SET @s := CONCAT('ALTER TABLE `chat_messages` ADD ', ddl);
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END//
DELIMITER ;
CALL _cm_add_idx('idx_room_id',      "INDEX `idx_room_id` (`room`,`id`)");
CALL _cm_add_idx('idx_room_user_to', "INDEX `idx_room_user_to` (`room`,`user_id`,`to_user_id`,`id`)");
CALL _cm_add_idx('idx_room_to_user', "INDEX `idx_room_to_user` (`room`,`to_user_id`,`user_id`,`id`)");
DROP PROCEDURE IF EXISTS _cm_add_idx;

-- ----------------------------------------------------------------------------
-- NOTE on `peer_id`
-- The controller writes `peer_id` and `to_user_id` to the same value on every
-- personal message, but only `to_user_id` is ever read. `peer_id` is therefore
-- a redundant duplicate. It is kept (nullable, still written) so this migration
-- stays backwards-compatible with rows already in the wild; the send path now
-- treats `to_user_id` as the single source of truth. Drop `peer_id` in a later
-- migration once nothing depends on it.
-- ----------------------------------------------------------------------------
