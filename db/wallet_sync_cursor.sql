-- ============================================================================
-- Wallet Sync Cursor — scalable, resumable, multi-worker address rotation for
-- the balance-sync cron. A singleton row holds the cursor (last processed
-- user_id), the configurable batch size, the full-pass cycle counter, and a
-- worker claim lock. Each cron run atomically claims the next window
-- (SELECT … FOR UPDATE), so multiple workers never process the same window and
-- the cursor persists across restarts (resume from where it stopped).
-- Idempotent: safe to re-run.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `wallet_sync_cursor` (
  `id`           TINYINT UNSIGNED NOT NULL,             -- singleton = 1
  `last_user_id` INT UNSIGNED NOT NULL DEFAULT 0,       -- rotation cursor
  `batch_size`   INT UNSIGNED NOT NULL DEFAULT 200,     -- configurable (100–500 typical)
  `cycle_count`  INT UNSIGNED NOT NULL DEFAULT 0,       -- completed full passes
  `worker_id`    VARCHAR(40) DEFAULT NULL,              -- last claimer
  `locked_at`    DATETIME DEFAULT NULL,                 -- claim timestamp
  `last_run_at`  DATETIME DEFAULT NULL,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `wallet_sync_cursor` (`id`,`batch_size`) VALUES (1, 200);
