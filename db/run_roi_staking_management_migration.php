<?php
// Create ROI Staking Management table for Fixed/Regular/Combo plans

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

echo "=== ROI Staking Management Migration ===\n\n";

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'roi_staking_management'");

if ($result && $result->num_rows > 0) {
    echo "Table 'roi_staking_management' already exists\n";
} else {
    echo "Creating roi_staking_management table...\n";

    $sql = "CREATE TABLE IF NOT EXISTS `roi_staking_management` (
      `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
      `staking_swap_orders_id` BIGINT UNSIGNED NOT NULL,
      `user_id` INT(11) NOT NULL,
      `ref` VARCHAR(100) NOT NULL UNIQUE,
      `plan_type` ENUM('fixed', 'regular', 'combo') NOT NULL DEFAULT 'fixed',
      `principal_amount` DECIMAL(20, 8) NOT NULL,
      `roi_rate_percent` DECIMAL(10, 4) NOT NULL,
      `total_roi_amount` DECIMAL(20, 8) NOT NULL,
      `duration_years` INT(11) NOT NULL,
      `fixed_payment_amount` DECIMAL(20, 8) DEFAULT NULL,
      `fixed_maturity_date` DATETIME DEFAULT NULL,
      `fixed_status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
      `fixed_paid_date` DATETIME DEFAULT NULL,
      `fixed_tx_hash` VARCHAR(255) DEFAULT NULL,
      `regular_payment_amount` DECIMAL(20, 8) DEFAULT NULL,
      `regular_payment_count` INT(11) DEFAULT 3,
      `regular_payments_completed` INT(11) DEFAULT 0,
      `payment_day_5_amount` DECIMAL(20, 8) DEFAULT NULL,
      `payment_day_5_date` DATETIME DEFAULT NULL,
      `payment_day_5_status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
      `payment_day_5_tx_hash` VARCHAR(255) DEFAULT NULL,
      `payment_day_15_amount` DECIMAL(20, 8) DEFAULT NULL,
      `payment_day_15_date` DATETIME DEFAULT NULL,
      `payment_day_15_status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
      `payment_day_15_tx_hash` VARCHAR(255) DEFAULT NULL,
      `payment_day_25_amount` DECIMAL(20, 8) DEFAULT NULL,
      `payment_day_25_date` DATETIME DEFAULT NULL,
      `payment_day_25_status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
      `payment_day_25_tx_hash` VARCHAR(255) DEFAULT NULL,
      `overall_status` ENUM('active', 'in_progress', 'completed', 'failed') NOT NULL DEFAULT 'active',
      `total_paid_amount` DECIMAL(20, 8) DEFAULT 0,
      `remaining_to_pay` DECIMAL(20, 8) DEFAULT NULL,
      `gas_fee_amount` DECIMAL(20, 8) DEFAULT 0,
      `gas_paid_by` ENUM('admin', 'user', 'platform') DEFAULT 'admin',
      `total_gas_paid` DECIMAL(20, 8) DEFAULT 0,
      `next_payment_date` DATETIME DEFAULT NULL,
      `error_message` TEXT,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      KEY `idx_staking_swap_orders_id` (`staking_swap_orders_id`),
      KEY `idx_user_id` (`user_id`),
      KEY `idx_plan_type` (`plan_type`),
      KEY `idx_overall_status` (`overall_status`),
      KEY `idx_next_payment_date` (`next_payment_date`),
      KEY `idx_fixed_maturity_date` (`fixed_maturity_date`),
      CONSTRAINT `fk_roi_staking_swap_orders`
        FOREIGN KEY (`staking_swap_orders_id`)
        REFERENCES `staking_swap_orders` (`id`)
        ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conn->query($sql)) {
        echo "✓ Table 'roi_staking_management' created successfully\n";
    } else {
        echo "✗ Error creating table: " . $conn->error . "\n";
    }
}

// Add column to staking_swap_orders if it doesn't exist
echo "\nAdding roi_staking_management_id to staking_swap_orders...\n";
$result = $conn->query("SHOW COLUMNS FROM staking_swap_orders LIKE 'roi_staking_management_id'");

if ($result && $result->num_rows > 0) {
    echo "Column already exists\n";
} else {
    $sql = "ALTER TABLE staking_swap_orders
            ADD COLUMN roi_staking_management_id BIGINT UNSIGNED DEFAULT NULL AFTER maturity_roi_amount,
            ADD KEY idx_roi_staking_management_id (roi_staking_management_id)";

    if ($conn->query($sql)) {
        echo "✓ Column 'roi_staking_management_id' added to staking_swap_orders\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
    }
}

$conn->close();
echo "\n✓ Migration complete!\n";
?>
