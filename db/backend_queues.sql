-- ============================================================================
-- Backend processing queues — Binary Matching + Blockchain Payout.
-- Idempotent: safe to re-run. Purely additive (no existing table is altered).
--
-- These queues let the matching engine and any on-chain payout run as tracked,
-- retry-safe background jobs driven by cron. They do NOT replace the proven
-- engines — they wrap them so every run/transfer has a status, attempt count,
-- and an admin-visible audit trail.
-- ============================================================================

-- 1) Binary Matching job queue — one row per matching run request.
--    The Binary Matching Scanner cron enqueues PENDING jobs; the matching
--    processor claims one, runs Stakingmatching_model::run(), and marks the
--    outcome. A UNIQUE run_ref makes enqueue idempotent.
CREATE TABLE IF NOT EXISTS `binary_matching_queue` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `run_ref`      VARCHAR(40) NOT NULL,
  `status`       ENUM('PENDING','PROCESSING','DONE','FAILED') NOT NULL DEFAULT 'PENDING',
  `scope`        VARCHAR(20) NOT NULL DEFAULT 'full' COMMENT 'full | propagate | pay',
  `attempts`     INT NOT NULL DEFAULT 0,
  `max_attempts` INT NOT NULL DEFAULT 3,
  `result_json`  TEXT NULL DEFAULT NULL COMMENT 'engine summary (paid_users, matched_volume, ...)',
  `last_error`   VARCHAR(255) NULL DEFAULT NULL,
  `enqueued_by`  INT NULL DEFAULT NULL COMMENT 'admin id if manually triggered, else NULL (cron)',
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `started_at`   DATETIME NULL DEFAULT NULL,
  `finished_at`  DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_run_ref` (`run_ref`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Binary matching run queue (scanner enqueues, processor runs the engine)';

-- 2) Blockchain payout queue — one row per on-chain send request.
--    Any flow that needs an on-chain transfer (withdrawal payout, ceiling
--    release-to-chain, etc.) enqueues a PENDING row. The Blockchain Payout
--    Processor cron pre-checks hot-wallet token balance + gas + RPC, broadcasts,
--    stores tx_hash, then the Confirmation Processor advances confirmations.
--    Failed sends flip to FAILED/RETRY and are admin-retryable.
CREATE TABLE IF NOT EXISTS `blockchain_payout_queue` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payout_ref`      VARCHAR(48) NOT NULL,
  `user_id`         INT NULL DEFAULT NULL,
  `token`           VARCHAR(16) NOT NULL DEFAULT 'BMAN' COMMENT 'BMAN | USDT',
  `amount`          DECIMAL(30,8) NOT NULL,
  `to_address`      VARCHAR(64) NOT NULL,
  `from_address`    VARCHAR(64) NULL DEFAULT NULL COMMENT 'hot/treasury wallet (resolved at send)',
  `purpose`         VARCHAR(40) NOT NULL DEFAULT 'payout' COMMENT 'withdrawal | ceiling_release | ...',
  `reference_type`  VARCHAR(40) NULL DEFAULT NULL,
  `reference_id`    VARCHAR(64) NULL DEFAULT NULL,
  `status`          ENUM('PENDING','PROCESSING','CONFIRMED','FAILED','RETRY') NOT NULL DEFAULT 'PENDING',
  -- reliability / lifecycle
  `tx_hash`         VARCHAR(80) NULL DEFAULT NULL,
  `block_number`    BIGINT UNSIGNED NULL DEFAULT NULL,
  `confirmations`   INT NOT NULL DEFAULT 0,
  `required_confs`  INT NOT NULL DEFAULT 3,
  `retry_count`     INT NOT NULL DEFAULT 0,
  `max_retries`     INT NOT NULL DEFAULT 5,
  `last_attempt_at` DATETIME NULL DEFAULT NULL,
  `last_error`      VARCHAR(255) NULL DEFAULT NULL,
  -- pre-flight check snapshot (for admin visibility / gas warnings)
  `precheck_json`   TEXT NULL DEFAULT NULL COMMENT 'hot balance / gas balance / rpc ok at last attempt',
  `onchain_tx_id`   BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'link to onchain_transactions.id',
  `created_by`      INT NULL DEFAULT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `confirmed_at`    DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payout_ref` (`payout_ref`),
  KEY `idx_status` (`status`),
  KEY `idx_txhash` (`tx_hash`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='On-chain payout queue with balance pre-checks, retry & confirmations';
