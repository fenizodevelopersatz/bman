<?php
$pdo = new PDO('mysql:host=localhost;dbname=e-commerce-mlm-v2;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Step 1: Add transfer_password columns to users
$cols = [
    'transfer_password'        => 'VARCHAR(255) NULL DEFAULT NULL',
    'transfer_password_set_at' => 'DATETIME NULL DEFAULT NULL',
];
foreach ($cols as $col => $def) {
    $exists = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='e-commerce-mlm-v2' AND TABLE_NAME='users' AND COLUMN_NAME='$col'")->fetchColumn();
    if (!$exists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN $col $def");
        echo "Added users.$col\n";
    } else {
        echo "Already exists: users.$col\n";
    }
}

// Step 2: Create wallet_internal_transfer table
$pdo->exec("CREATE TABLE IF NOT EXISTS wallet_internal_transfer (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ref VARCHAR(32) NOT NULL,
  user_id INT NOT NULL,
  from_wallet ENUM('exchange','earning','staking','bonus') NOT NULL,
  to_wallet ENUM('exchange','earning','staking','bonus') NOT NULL,
  amount DECIMAL(30,8) NOT NULL DEFAULT 0,
  fee DECIMAL(30,8) NOT NULL DEFAULT 0,
  net_amount DECIMAL(30,8) NOT NULL DEFAULT 0,
  status ENUM('completed','failed','reversed') NOT NULL DEFAULT 'completed',
  description VARCHAR(255) NULL DEFAULT NULL,
  debit_ledger_id BIGINT UNSIGNED NULL DEFAULT NULL,
  credit_ledger_id BIGINT UNSIGNED NULL DEFAULT NULL,
  ip_address VARCHAR(45) NULL DEFAULT NULL,
  user_agent VARCHAR(255) NULL DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ref (ref),
  KEY idx_user (user_id),
  KEY idx_status (status),
  KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Internal wallet-to-wallet transfer audit log'");
echo "Table wallet_internal_transfer: OK\n";

echo "Migration complete!\n";
