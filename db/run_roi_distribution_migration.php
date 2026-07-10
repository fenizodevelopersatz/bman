<?php
// Create ROI Distribution Table for maturity tracking

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

echo "Running ROI Distribution table migration...\n";

// Check if table already exists
$result = $conn->query("SHOW TABLES LIKE 'roi_distribution'");

if ($result && $result->num_rows > 0) {
    echo "Table 'roi_distribution' already exists\n";
} else {
    // Create the table
    $sql = "CREATE TABLE IF NOT EXISTS `roi_distribution` (
      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
      `staking_swap_orders_id` bigint(20) unsigned NOT NULL,
      `user_id` int(11) NOT NULL,

      `principal_amount` decimal(20, 8) NOT NULL DEFAULT '0.00000000',
      `duration_years` int(11) NOT NULL DEFAULT 1,
      `roi_rate_percent` decimal(10, 4) NOT NULL DEFAULT '0.0000',

      `total_roi_earned` decimal(20, 8) NOT NULL DEFAULT '0.00000000',
      `roi_already_paid` decimal(20, 8) NOT NULL DEFAULT '0.00000000',
      `roi_remaining` decimal(20, 8) NOT NULL DEFAULT '0.00000000',
      `bonus_amount` decimal(20, 8) NOT NULL DEFAULT '0.00000000',

      `purchase_date` datetime NOT NULL,
      `maturity_date` datetime NOT NULL,
      `days_elapsed` int(11) NOT NULL DEFAULT 0,
      `is_matured` tinyint(1) NOT NULL DEFAULT 0,

      `distribution_status` enum('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
      `distribution_date` datetime DEFAULT NULL,
      `tx_hash` varchar(255) DEFAULT NULL,
      `error_message` text,

      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

      KEY `idx_staking_swap_orders_id` (`staking_swap_orders_id`),
      KEY `idx_user_id` (`user_id`),
      KEY `idx_maturity_date` (`maturity_date`),
      KEY `idx_distribution_status` (`distribution_status`),
      KEY `idx_is_matured` (`is_matured`),

      CONSTRAINT `fk_roi_distribution_staking`
        FOREIGN KEY (`staking_swap_orders_id`)
        REFERENCES `staking_swap_orders` (`id`)
        ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conn->query($sql)) {
        echo "✓ Table 'roi_distribution' created successfully\n";
    } else {
        echo "✗ Error creating table: " . $conn->error . "\n";
    }
}

$conn->close();
echo "Migration complete!\n";
?>
