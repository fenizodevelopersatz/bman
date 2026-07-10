<?php
// Add maturity_roi_amount column

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

echo "Adding maturity_roi_amount column...\n";

// Check if column already exists
$result = $conn->query("SHOW COLUMNS FROM staking_swap_orders LIKE 'maturity_roi_amount'");

if ($result && $result->num_rows > 0) {
    echo "Column 'maturity_roi_amount' already exists\n";
} else {
    // Add the column
    $sql = "ALTER TABLE staking_swap_orders
            ADD COLUMN maturity_roi_amount DECIMAL(20, 8) DEFAULT '0.00000000'
            AFTER roi_return_status";

    if ($conn->query($sql)) {
        echo "✓ Column 'maturity_roi_amount' added successfully\n";

        // Calculate values for existing records
        $updateSql = "UPDATE staking_swap_orders
                      SET maturity_roi_amount = (bman_amount * roi_rate / 100)
                      WHERE maturity_roi_amount = 0
                      AND roi_rate > 0
                      AND bman_amount > 0";

        if ($conn->query($updateSql)) {
            $affected = $conn->affected_rows;
            echo "✓ Updated $affected existing records with calculated maturity ROI amounts\n";
        } else {
            echo "✗ Error updating records: " . $conn->error . "\n";
        }
    } else {
        echo "✗ Error: " . $conn->error . "\n";
    }
}

$conn->close();
echo "✓ Migration complete!\n";
?>
