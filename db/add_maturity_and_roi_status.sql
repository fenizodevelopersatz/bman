-- Add maturity_date and roi_return_status columns to staking_swap_orders table

ALTER TABLE `staking_swap_orders`
ADD COLUMN `maturity_date` DATETIME DEFAULT NULL AFTER `roi_rate` COMMENT 'Calculated maturity date (created_at + duration_years)',
ADD COLUMN `roi_return_status` VARCHAR(50) DEFAULT 'pending' AFTER `maturity_date` COMMENT 'ROI distribution status: pending, in_progress, completed';

-- Create migration script
<?php
// Direct database migration - no CI framework needed

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

// Check if columns already exist
$result = $conn->query("SHOW COLUMNS FROM staking_swap_orders LIKE 'maturity_date'");

if ($result && $result->num_rows > 0) {
    echo "Columns already exist\n";
    exit(0);
}

// Add maturity_date column
$sql1 = "ALTER TABLE staking_swap_orders
        ADD COLUMN maturity_date DATETIME DEFAULT NULL
        AFTER roi_rate
        COMMENT 'Calculated maturity date (created_at + duration_years)'";

if ($conn->query($sql1)) {
    echo "✓ Column 'maturity_date' added successfully\n";
} else {
    if ($conn->errno === 1060) {
        echo "Column 'maturity_date' already exists\n";
    } else {
        echo "✗ Error adding maturity_date: " . $conn->error . "\n";
    }
}

// Add roi_return_status column
$sql2 = "ALTER TABLE staking_swap_orders
        ADD COLUMN roi_return_status VARCHAR(50) DEFAULT 'pending'
        AFTER maturity_date
        COMMENT 'ROI distribution status: pending, in_progress, completed'";

if ($conn->query($sql2)) {
    echo "✓ Column 'roi_return_status' added successfully\n";
} else {
    if ($conn->errno === 1060) {
        echo "Column 'roi_return_status' already exists\n";
    } else {
        echo "✗ Error adding roi_return_status: " . $conn->error . "\n";
    }
}

// Update existing records with maturity_date
$sql3 = "UPDATE staking_swap_orders
         SET maturity_date = DATE_ADD(created_at, INTERVAL duration_years YEAR)
         WHERE maturity_date IS NULL";

if ($conn->query($sql3)) {
    echo "✓ Existing records updated with maturity dates\n";
} else {
    echo "✗ Error updating records: " . $conn->error . "\n";
}

$conn->close();
?>
