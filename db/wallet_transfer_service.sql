-- ============================================================================
-- Centralized Wallet Transfer Service — schema additions
--  * wallet_internal_transfer: idempotency, audit + blockchain columns to back
--    the full transfer-history / detail-view spec.
--  * wallet_transfer_audit: append-only audit log (immutable).
-- Idempotent: safe to re-run.
-- ============================================================================

DROP PROCEDURE IF EXISTS `_wtadd`;
DELIMITER //
CREATE PROCEDURE `_wtadd`(IN col VARCHAR(64), IN ddl TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wallet_internal_transfer' AND COLUMN_NAME = col) THEN
    SET @s = CONCAT('ALTER TABLE `wallet_internal_transfer` ADD COLUMN ', ddl);
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END//
DELIMITER ;

CALL _wtadd('idempotency_key', '`idempotency_key` VARCHAR(80) DEFAULT NULL');
CALL _wtadd('created_by',      '`created_by` INT DEFAULT NULL');           -- admin id when via=admin
CALL _wtadd('failure_reason',  '`failure_reason` VARCHAR(200) DEFAULT NULL');
CALL _wtadd('tx_hash',         '`tx_hash` VARCHAR(120) DEFAULT NULL');
CALL _wtadd('block_number',    '`block_number` BIGINT UNSIGNED DEFAULT NULL');
CALL _wtadd('confirmations',   '`confirmations` INT DEFAULT NULL');
CALL _wtadd('gas_used',        '`gas_used` BIGINT DEFAULT NULL');
CALL _wtadd('gas_fee',         '`gas_fee` DECIMAL(38,18) DEFAULT NULL');
CALL _wtadd('network',         "`network` VARCHAR(20) DEFAULT NULL");

DROP PROCEDURE IF EXISTS `_wtadd`;

-- Unique idempotency guard (retry-safe). NULLs are allowed (multiple non-idempotent rows).
SET @has := (SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wallet_internal_transfer' AND INDEX_NAME = 'uq_idem');
SET @s := IF(@has = 0, 'ALTER TABLE `wallet_internal_transfer` ADD UNIQUE KEY `uq_idem` (`idempotency_key`)', 'DO 0');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- Append-only audit log for every transfer action.
CREATE TABLE IF NOT EXISTS `wallet_transfer_audit` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `transfer_id` BIGINT UNSIGNED DEFAULT NULL,
  `ref`         VARCHAR(40)  DEFAULT NULL,
  `action`      VARCHAR(30)  NOT NULL,                 -- validated | executed | rejected | failed
  `mode`        VARCHAR(20)  DEFAULT NULL,             -- member | internal
  `via`         VARCHAR(10)  DEFAULT NULL,             -- user | admin
  `actor_type`  VARCHAR(10)  DEFAULT NULL,             -- user | admin
  `actor_id`    INT DEFAULT NULL,
  `source_user_id` INT DEFAULT NULL,
  `recipient_id`   INT DEFAULT NULL,
  `from_wallet` VARCHAR(20)  DEFAULT NULL,
  `to_wallet`   VARCHAR(20)  DEFAULT NULL,
  `amount`      DECIMAL(30,8) DEFAULT NULL,
  `result_code` VARCHAR(40)  DEFAULT NULL,
  `message`     VARCHAR(255) DEFAULT NULL,
  `ip_address`  VARCHAR(64)  DEFAULT NULL,
  `user_agent`  VARCHAR(255) DEFAULT NULL,
  `request_id`  VARCHAR(64)  DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_transfer` (`transfer_id`),
  KEY `idx_ref` (`ref`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
