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

// Check if column already exists
$result = $conn->query("SHOW COLUMNS FROM staking_swap_orders LIKE 'roi_rate'");

if ($result && $result->num_rows > 0) {
    echo "Column 'roi_rate' already exists\n";
    exit(0);
}

// Add the column
$sql = "ALTER TABLE staking_swap_orders
        ADD COLUMN roi_rate DECIMAL(10, 4) DEFAULT 0
        AFTER duration_years";

if ($conn->query($sql)) {
    echo "✓ Column 'roi_rate' added successfully\n";
} else {
    if ($conn->errno === 1060) {
        echo "Column already exists\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
        exit(1);
    }
}

$conn->close();
?>
