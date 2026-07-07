-- ============================================================================
-- Staking Documents — metadata + audit for generated investment documents
-- (purchase receipt, agreement, ROI schedule, summary report). Documents are
-- rendered on demand from live data (no duplicate files stored); only the
-- metadata + an access/audit log are persisted. One metadata row per
-- (invest_id, doc_type) — regenerating reuses its document number.
-- Idempotent: safe to re-run.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `staking_documents` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `doc_no`        VARCHAR(40)  NOT NULL,                 -- e.g. RCP-2026-000057
  `doc_type`      ENUM('receipt','agreement','roi_schedule','summary') NOT NULL,
  `invest_id`     BIGINT UNSIGNED NOT NULL,
  `user_id`       INT NOT NULL,
  `tx_hash`       VARCHAR(120) DEFAULT NULL,
  `download_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `generated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_access_at` DATETIME DEFAULT NULL,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_invest_type` (`invest_id`,`doc_type`),
  KEY `idx_user` (`user_id`),
  KEY `idx_docno` (`doc_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `staking_document_log` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `document_id` BIGINT UNSIGNED DEFAULT NULL,
  `invest_id`   BIGINT UNSIGNED NOT NULL,
  `user_id`     INT NOT NULL,
  `doc_type`    VARCHAR(20) NOT NULL,
  `action`      VARCHAR(20) NOT NULL DEFAULT 'generated', -- generated | viewed | downloaded
  `actor_type`  VARCHAR(20) NOT NULL DEFAULT 'user',       -- user | admin
  `actor_id`    INT DEFAULT NULL,
  `ip_address`  VARCHAR(64) DEFAULT NULL,
  `user_agent`  VARCHAR(255) DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_doc` (`document_id`),
  KEY `idx_invest` (`invest_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
