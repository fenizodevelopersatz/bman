<?php
/*
 * Migration: Setup BMAN Withdrawal Request Locking System
 * Creates tables and structures per AGENTS.md documentation
 */

$dbConfig = [
    'host'     => 'localhost',
    'user'     => 'root',
    'password' => '',
    'database' => 'e-commerce-mlm-v2'
];

$conn = new mysqli(
    $dbConfig['host'],
    $dbConfig['user'],
    $dbConfig['password'],
    $dbConfig['database']
);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$migrations = [];

// 1. Create/Update bman_withdraw_requests table
$migrations[] = [
    'name' => 'bman_withdraw_requests',
    'sql' => "
    CREATE TABLE IF NOT EXISTS bman_withdraw_requests (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        request_no VARCHAR(50) NOT NULL UNIQUE COMMENT 'BWM-YYYYMMDDHHMMSS-XXXX',
        user_id BIGINT UNSIGNED NOT NULL,
        source_wallet ENUM('exchange','earning','staking','bonus','mixed') NOT NULL DEFAULT 'mixed',
        request_amount DECIMAL(18,8) NOT NULL,
        fee_amount DECIMAL(18,8) NOT NULL DEFAULT 0,
        net_amount DECIMAL(18,8) NOT NULL COMMENT 'request_amount - fee_amount',
        bman_usdt_rate DECIMAL(18,8) NOT NULL COMMENT 'conversion rate at request time',
        usdt_amount DECIMAL(18,8) NOT NULL COMMENT 'net_amount * bman_usdt_rate',
        withdraw_address VARCHAR(255) NOT NULL,
        remark TEXT,
        tx_hash VARCHAR(255),
        admin_remark TEXT,
        status ENUM('pending','approved','processing','completed','rejected','failed') NOT NULL DEFAULT 'pending',
        approved_by BIGINT UNSIGNED,
        approved_at DATETIME,
        completed_at DATETIME,
        created_at DATETIME NOT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_user_status (user_id, status),
        KEY idx_status (status),
        KEY idx_created (created_at),
        CONSTRAINT fk_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Withdrawal requests with instant locking'
    "
];

// 2. Create/Update bman_wallet_ledger table
$migrations[] = [
    'name' => 'bman_wallet_ledger',
    'sql' => "
    CREATE TABLE IF NOT EXISTS bman_wallet_ledger (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        wallet ENUM('exchange','earning','staking','bonus') NOT NULL,
        entry_type ENUM('credit','debit','lock') NOT NULL,
        ref_type VARCHAR(50) COMMENT 'withdrawal, staking, airdrop, etc',
        ref_id BIGINT UNSIGNED COMMENT 'reference to source table',
        amount DECIMAL(18,8) NOT NULL COMMENT 'always positive; sign is in entry_type',
        maturity_date DATETIME COMMENT 'NULL means matured immediately',
        status ENUM('active','reversed') NOT NULL DEFAULT 'active',
        remark TEXT,
        created_at DATETIME NOT NULL,
        KEY idx_user_wallet (user_id, wallet),
        KEY idx_user_maturity (user_id, maturity_date),
        KEY idx_ref (ref_type, ref_id),
        KEY idx_status (status),
        CONSTRAINT fk_ledger_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Source of truth for balances (append-only)'
    "
];

// 3. Create/Update bman_withdraw_allocations table
$migrations[] = [
    'name' => 'bman_withdraw_allocations',
    'sql' => "
    CREATE TABLE IF NOT EXISTS bman_withdraw_allocations (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        request_id BIGINT UNSIGNED NOT NULL,
        wallet ENUM('exchange','earning','staking','bonus') NOT NULL,
        amount DECIMAL(18,8) NOT NULL COMMENT 'amount taken from this wallet',
        created_at DATETIME NOT NULL,
        UNIQUE KEY uq_request_wallet (request_id, wallet),
        CONSTRAINT fk_request_id FOREIGN KEY (request_id) REFERENCES bman_withdraw_requests(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks which wallet contributed to mixed requests'
    "
];

// 4. Create/Update withdraw_audit_log table
$migrations[] = [
    'name' => 'withdraw_audit_log',
    'sql' => "
    CREATE TABLE IF NOT EXISTS withdraw_audit_log (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        request_id BIGINT UNSIGNED NOT NULL,
        admin_id BIGINT UNSIGNED,
        action VARCHAR(50) NOT NULL,
        old_status VARCHAR(50),
        new_status VARCHAR(50),
        remarks TEXT,
        created_at DATETIME NOT NULL,
        KEY idx_request (request_id),
        KEY idx_created (created_at),
        CONSTRAINT fk_audit_request FOREIGN KEY (request_id) REFERENCES bman_withdraw_requests(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit trail of withdrawal request changes'
    "
];

// Execute migrations
$success = 0;
$failed = 0;

foreach ($migrations as $m) {
    echo "\n[{$m['name']}] ";
    if ($conn->query($m['sql'])) {
        echo "✓ OK\n";
        $success++;
    } else {
        // Check if error is due to table already existing (which is fine)
        if ($conn->errno === 1050) {
            echo "✓ Already exists\n";
            $success++;
        } else {
            echo "✗ ERROR: " . $conn->error . "\n";
            $failed++;
        }
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Summary: {$success} successful, {$failed} failed\n";

if ($failed === 0) {
    echo "\nWithdraw locking system is ready!\n";
    echo "See docs/18_WITHDRAW_REQUEST_AGENTS.md for workflow details.\n";
}

$conn->close();
?>
