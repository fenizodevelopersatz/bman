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

$success = 0;
$failed = 0;

// 1. Ensure bman_withdraw_requests table exists with all columns
echo "\n[bman_withdraw_requests] ";
$tableExists = $conn->query("SHOW TABLES LIKE 'bman_withdraw_requests'");
if ($tableExists && $tableExists->num_rows > 0) {
    echo "✓ Already exists\n";
    $success++;

    // Add missing columns if needed
    $cols = $conn->query("SHOW COLUMNS FROM bman_withdraw_requests LIKE 'bman_usdt_rate'");
    if (!$cols || $cols->num_rows === 0) {
        echo "  [add bman_usdt_rate] ";
        if ($conn->query("ALTER TABLE bman_withdraw_requests ADD COLUMN bman_usdt_rate DECIMAL(18,8) NOT NULL DEFAULT 0 AFTER net_amount")) {
            echo "✓\n";
        } else {
            echo "✗ " . $conn->error . "\n";
        }
    }

    $cols = $conn->query("SHOW COLUMNS FROM bman_withdraw_requests LIKE 'usdt_amount'");
    if (!$cols || $cols->num_rows === 0) {
        echo "  [add usdt_amount] ";
        if ($conn->query("ALTER TABLE bman_withdraw_requests ADD COLUMN usdt_amount DECIMAL(18,8) NOT NULL DEFAULT 0 AFTER bman_usdt_rate")) {
            echo "✓\n";
        } else {
            echo "✗ " . $conn->error . "\n";
        }
    }
} else {
    $sql = "
    CREATE TABLE bman_withdraw_requests (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        request_no VARCHAR(50) NOT NULL UNIQUE COMMENT 'BWM-YYYYMMDDHHMMSS-XXXX',
        user_id BIGINT UNSIGNED NOT NULL,
        source_wallet ENUM('exchange','earning','staking','bonus','mixed') NOT NULL DEFAULT 'mixed',
        request_amount DECIMAL(18,8) NOT NULL,
        fee_amount DECIMAL(18,8) NOT NULL DEFAULT 0,
        net_amount DECIMAL(18,8) NOT NULL COMMENT 'request_amount - fee_amount',
        bman_usdt_rate DECIMAL(18,8) NOT NULL DEFAULT 0 COMMENT 'conversion rate at request time',
        usdt_amount DECIMAL(18,8) NOT NULL DEFAULT 0 COMMENT 'net_amount * bman_usdt_rate',
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
        KEY idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Withdrawal requests with instant locking'
    ";

    if ($conn->query($sql)) {
        echo "✓ Created\n";
        $success++;
    } else {
        echo "✗ ERROR: " . $conn->error . "\n";
        $failed++;
    }
}

// 2. Create/Update bman_wallet_ledger table (no FK for now)
echo "[bman_wallet_ledger] ";
$tableExists = $conn->query("SHOW TABLES LIKE 'bman_wallet_ledger'");
if ($tableExists && $tableExists->num_rows > 0) {
    echo "✓ Already exists\n";
    $success++;
} else {
    $sql = "
    CREATE TABLE bman_wallet_ledger (
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
        KEY idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Source of truth for balances (append-only)'
    ";

    if ($conn->query($sql)) {
        echo "✓ Created\n";
        $success++;
    } else {
        echo "✗ ERROR: " . $conn->error . "\n";
        $failed++;
    }
}

// 3. Create/Update bman_withdraw_allocations table
echo "[bman_withdraw_allocations] ";
$tableExists = $conn->query("SHOW TABLES LIKE 'bman_withdraw_allocations'");
if ($tableExists && $tableExists->num_rows > 0) {
    echo "✓ Already exists\n";
    $success++;
} else {
    $sql = "
    CREATE TABLE bman_withdraw_allocations (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        request_id BIGINT UNSIGNED NOT NULL,
        wallet ENUM('exchange','earning','staking','bonus') NOT NULL,
        amount DECIMAL(18,8) NOT NULL COMMENT 'amount taken from this wallet',
        created_at DATETIME NOT NULL,
        UNIQUE KEY uq_request_wallet (request_id, wallet),
        KEY idx_request (request_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks which wallet contributed to mixed requests'
    ";

    if ($conn->query($sql)) {
        echo "✓ Created\n";
        $success++;
    } else {
        echo "✗ ERROR: " . $conn->error . "\n";
        $failed++;
    }
}

// 4. Create/Update withdraw_audit_log table
echo "[withdraw_audit_log] ";
$tableExists = $conn->query("SHOW TABLES LIKE 'withdraw_audit_log'");
if ($tableExists && $tableExists->num_rows > 0) {
    echo "✓ Already exists\n";
    $success++;
} else {
    $sql = "
    CREATE TABLE withdraw_audit_log (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        request_id BIGINT UNSIGNED NOT NULL,
        admin_id BIGINT UNSIGNED,
        action VARCHAR(50) NOT NULL,
        old_status VARCHAR(50),
        new_status VARCHAR(50),
        remarks TEXT,
        created_at DATETIME NOT NULL,
        KEY idx_request (request_id),
        KEY idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit trail of withdrawal request changes'
    ";

    if ($conn->query($sql)) {
        echo "✓ Created\n";
        $success++;
    } else {
        echo "✗ ERROR: " . $conn->error . "\n";
        $failed++;
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Summary: {$success} successful, {$failed} failed\n";

if ($failed === 0) {
    echo "\n✅ Withdraw locking system is ready!\n";
    echo "See docs/18_WITHDRAW_REQUEST_AGENTS.md for workflow details.\n";
} else {
    echo "\n⚠️  Some tables may not be configured correctly. Check errors above.\n";
}

$conn->close();
?>
