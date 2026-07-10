<?php
/**
 * ROI Maturity CRON Standalone Test
 * Direct PHP execution for testing
 * Accessible at: http://yoursite.com/roi_maturity_test.php
 */

// Database configuration
$dbConfig = [
    'host'     => 'localhost',
    'user'     => 'root',
    'password' => '',
    'database' => 'e-commerce-mlm-v2'
];

// Get format from query string (default: json)
$format = $_GET['format'] ?? 'json';
$action = $_GET['action'] ?? 'test'; // 'test' or 'process'

try {
    // Connect to database
    $conn = new mysqli(
        $dbConfig['host'],
        $dbConfig['user'],
        $dbConfig['password'],
        $dbConfig['database']
    );

    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    if ($format === 'json') {
        header('Content-Type: application/json');
    }

    // Test action: Check system status
    if ($action === 'test') {
        $scheme = $_SERVER['REQUEST_SCHEME'] ?? 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        $response = [
            'status' => true,
            'message' => 'ROI Maturity CRON System is operational',
            'timestamp' => date('Y-m-d H:i:s'),
            'database' => 'Connected',
            'endpoints' => [
                'test' => 'GET ' . $scheme . '://' . $host . '/roi_maturity_test.php?action=test&format=json',
                'process' => 'GET ' . $scheme . '://' . $host . '/roi_maturity_test.php?action=process&format=json'
            ],
            'controller_url' => '/roi-maturity-test or /cron/roi_maturity/test'
        ];

        // Check if roi_distribution table exists
        $result = $conn->query("SHOW TABLES LIKE 'roi_distribution'");
        if ($result && $result->num_rows > 0) {
            $response['roi_distribution_table'] = 'exists';
        } else {
            $response['roi_distribution_table'] = 'not found - run migration';
        }

        // Count matured orders
        $result = $conn->query("
            SELECT COUNT(*) as count FROM staking_swap_orders
            WHERE maturity_date <= NOW()
            AND roi_return_status != 'completed'
        ");
        $row = $result->fetch_assoc();
        $response['matured_orders_pending'] = (int)$row['count'];

        echo json_encode($response, JSON_PRETTY_PRINT);
    }
    // Process action: Run CRON
    elseif ($action === 'process') {
        $output = "=== ROI MATURITY CRON PROCESS ===\n";
        $output .= "Started at: " . date('Y-m-d H:i:s') . "\n\n";

        // Get all staking orders that have matured
        $maturedOrders = $conn->query("
            SELECT * FROM staking_swap_orders
            WHERE maturity_date <= '" . date('Y-m-d H:i:s') . "'
            AND roi_return_status != 'completed'
        ")->fetch_all(MYSQLI_ASSOC);

        $output .= "Found " . count($maturedOrders) . " matured staking orders\n\n";

        $processedCount = 0;
        $failedCount = 0;
        $processedOrders = [];

        foreach ($maturedOrders as $order) {
            try {
                $output .= "Processing Order ID: {$order['id']} (User: {$order['user_id']})\n";

                $principal = (float)$order['bman_amount'];
                $roiRate = (float)$order['roi_rate'];
                $durationYears = (int)$order['duration_years'];
                $createdAt = strtotime($order['created_at']);
                $maturityAt = strtotime($order['maturity_date']);
                $daysElapsed = ceil(($maturityAt - $createdAt) / 86400);

                $totalROI = $principal * ($roiRate / 100) * ($daysElapsed / 365);

                // Get already paid ROI
                $result = $conn->query("
                    SELECT COALESCE(SUM(amount), 0) as total FROM onchain_transactions
                    WHERE staking_swap_orders_id = {$order['id']}
                    AND tx_type = 'roi'
                ");
                $row = $result->fetch_assoc();
                $alreadyPaid = (float)$row['total'];
                $remaining = max(0, $totalROI - $alreadyPaid);

                $output .= "  Principal: $principal BMAN\n";
                $output .= "  ROI Rate: $roiRate%\n";
                $output .= "  Total ROI: $totalROI BMAN\n";
                $output .= "  Already Paid: $alreadyPaid BMAN\n";
                $output .= "  Remaining: $remaining BMAN\n";

                // Update status to in_progress
                $conn->query("
                    UPDATE staking_swap_orders
                    SET roi_return_status = 'in_progress', updated_at = NOW()
                    WHERE id = {$order['id']}
                ");

                // Mark as completed (in test mode, just update status)
                $conn->query("
                    UPDATE staking_swap_orders
                    SET roi_return_status = 'completed', updated_at = NOW()
                    WHERE id = {$order['id']}
                ");

                $output .= "  ✓ Order {$order['id']} processed successfully\n\n";
                $processedOrders[] = [
                    'id' => $order['id'],
                    'user_id' => $order['user_id'],
                    'principal' => $principal,
                    'roi_remaining' => $remaining
                ];
                $processedCount++;

            } catch (Exception $e) {
                $output .= "  ✗ Error: " . $e->getMessage() . "\n\n";
                $failedCount++;
            }
        }

        $output .= "\n=== CRON SUMMARY ===\n";
        $output .= "Processed: $processedCount\n";
        $output .= "Failed: $failedCount\n";
        $output .= "Completed at: " . date('Y-m-d H:i:s') . "\n";

        if ($format === 'json') {
            echo json_encode([
                'status' => true,
                'message' => 'ROI maturity processing completed',
                'timestamp' => date('Y-m-d H:i:s'),
                'stats' => [
                    'found' => count($maturedOrders),
                    'processed' => $processedCount,
                    'failed' => $failedCount
                ],
                'orders' => $processedOrders
            ], JSON_PRETTY_PRINT);
        } else {
            echo $output;
        }
    } else {
        throw new Exception('Invalid action. Use action=test or action=process');
    }

    $conn->close();

} catch (Exception $e) {
    if ($format === 'json') {
        header('Content-Type: application/json', true, 500);
        echo json_encode([
            'status' => false,
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT);
    } else {
        http_response_code(500);
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}
?>
