<?php
// Temporary migration script - run this once to add roi_rate column

require_once 'application/config/database.php';

$config = config_item('database');
$db_config = $config['default'];

$conn = new mysqli(
    $db_config['hostname'],
    $db_config['username'],
    $db_config['password'],
    $db_config['database']
);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Check if column already exists
$result = $conn->query("SHOW COLUMNS FROM staking_swap_orders LIKE 'roi_rate'");

if ($result->num_rows > 0) {
    echo "✓ Column 'roi_rate' already exists\n";
} else {
    // Add the column
    $sql = "ALTER TABLE `staking_swap_orders`
            ADD COLUMN `roi_rate` DECIMAL(10, 4) DEFAULT 0
            AFTER `duration_years`
            COMMENT 'ROI percentage rate at time of purchase (e.g., 150 for 150%)'";

    if ($conn->query($sql)) {
        echo "✓ Column 'roi_rate' added successfully\n";
    } else {
        echo "✗ Error adding column: " . $conn->error . "\n";
    }
}

$conn->close();
echo "Migration complete!\n";
?>
