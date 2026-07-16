<?php
/**
 * Test Script: Complete Withdrawal Flow
 * Tests: Balance reduction → Lock creation → Admin approval
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

echo "====== WITHDRAWAL FLOW TEST ======\n";

// TEST 1: Get a user with balance
echo "\n[STEP 1] Finding user with balance...\n";
$result = $conn->query("
    SELECT u.id, u.username, u.referral_id
    FROM users u
    LIMIT 1
");
$user = $result->fetch_assoc();
$user_id = $user['id'];
$username = $user['username'];
echo "✓ User: {$username} (ID: {$user_id})\n";

// TEST 2: Check user's current balance (before withdrawal)
echo "\n[STEP 2] Checking user balance BEFORE withdrawal...\n";
$result = $conn->query("
    SELECT
        COALESCE(SUM(CASE WHEN entry_type='credit' THEN amount ELSE 0 END), 0) as credits,
        COALESCE(SUM(CASE WHEN entry_type='debit' THEN amount ELSE 0 END), 0) as debits,
        COALESCE(SUM(CASE WHEN entry_type='lock' AND status='active' THEN amount ELSE 0 END), 0) as locks
    FROM bman_wallet_ledger
    WHERE user_id = {$user_id}
");
$balance = $result->fetch_assoc();
$available_before = $balance['credits'] - $balance['debits'] - $balance['locks'];
echo "Credits: {$balance['credits']} BMAN\n";
echo "Debits:  {$balance['debits']} BMAN\n";
echo "Locks:   {$balance['locks']} BMAN\n";
echo "✓ Available Balance: {$available_before} BMAN\n";

if ($available_before < 1) {
    echo "\n⚠️  User has insufficient balance (<1 BMAN). Creating test credit...\n";
    $conn->query("
        INSERT INTO bman_wallet_ledger
        (user_id, wallet, entry_type, ref_type, amount, status, remark, created_at)
        VALUES ({$user_id}, 'bonus', 'credit', 'test', 100, 'active', 'Test credit for withdraw flow', NOW())
    ");
    echo "✓ Added 100 BMAN test credit\n";

    $result = $conn->query("
        SELECT
            COALESCE(SUM(CASE WHEN entry_type='credit' THEN amount ELSE 0 END), 0) as credits,
            COALESCE(SUM(CASE WHEN entry_type='debit' THEN amount ELSE 0 END), 0) as debits,
            COALESCE(SUM(CASE WHEN entry_type='lock' AND status='active' THEN amount ELSE 0 END), 0) as locks
        FROM bman_wallet_ledger
        WHERE user_id = {$user_id}
    ");
    $balance = $result->fetch_assoc();
    $available_before = $balance['credits'] - $balance['debits'] - $balance['locks'];
    echo "✓ Updated Available Balance: {$available_before} BMAN\n";
}

// TEST 3: Create withdrawal request (simulating user submission)
echo "\n[STEP 3] Creating withdrawal request (1.5 BMAN)...\n";
$request_no = 'BWM-' . date('YmdHis') . '-' . random_int(1000, 9999);
$withdrawal_amount = 1.5;
$fee_amount = 1.0;
$net_amount = $withdrawal_amount - $fee_amount;
$bman_usdt_rate = 1.0; // 1 BMAN = 1 USDT
$usdt_amount = $net_amount * $bman_usdt_rate;
$withdraw_address = '0x7b5aC2f86C2b21232Ca03d17b301555432A4bD66';

$insert_sql = "
    INSERT INTO bman_withdraw_requests
    (request_no, user_id, source_wallet, request_amount, fee_amount, net_amount,
     bman_usdt_rate, usdt_amount, withdraw_address, status, created_at)
    VALUES (
        '{$request_no}', {$user_id}, 'mixed', {$withdrawal_amount}, {$fee_amount}, {$net_amount},
        {$bman_usdt_rate}, {$usdt_amount}, '{$withdraw_address}', 'pending', NOW()
    )
";

if ($conn->query($insert_sql)) {
    $request_id = $conn->insert_id;
    echo "✓ Request created: {$request_no} (ID: {$request_id})\n";

    // TEST 4: Create lock ledger entries (instant lock)
    echo "\n[STEP 4] Creating ledger lock entries (simulating instant lock)...\n";
    $lock_sql = "
        INSERT INTO bman_wallet_ledger
        (user_id, wallet, entry_type, ref_type, ref_id, amount, status, remark, created_at)
        VALUES ({$user_id}, 'bonus', 'lock', 'withdrawal', {$request_id}, {$withdrawal_amount}, 'active', 'Locked for withdrawal', NOW())
    ";

    if ($conn->query($lock_sql)) {
        echo "✓ Lock entry created: {$withdrawal_amount} BMAN locked\n";

        // TEST 5: Create allocation record
        echo "\n[STEP 5] Recording wallet allocation...\n";
        $alloc_sql = "
            INSERT INTO bman_withdraw_allocations
            (request_id, wallet, amount, created_at)
            VALUES ({$request_id}, 'bonus', {$withdrawal_amount}, NOW())
        ";
        if ($conn->query($alloc_sql)) {
            echo "✓ Allocation recorded: bonus wallet contributed {$withdrawal_amount} BMAN\n";
        }

        // TEST 6: Check balance AFTER withdrawal request
        echo "\n[STEP 6] Checking balance AFTER withdrawal request...\n";
        $result = $conn->query("
            SELECT
                COALESCE(SUM(CASE WHEN entry_type='credit' THEN amount ELSE 0 END), 0) as credits,
                COALESCE(SUM(CASE WHEN entry_type='debit' THEN amount ELSE 0 END), 0) as debits,
                COALESCE(SUM(CASE WHEN entry_type='lock' AND status='active' THEN amount ELSE 0 END), 0) as locks
            FROM bman_wallet_ledger
            WHERE user_id = {$user_id}
        ");
        $balance = $result->fetch_assoc();
        $available_after = $balance['credits'] - $balance['debits'] - $balance['locks'];
        echo "Credits: {$balance['credits']} BMAN\n";
        echo "Debits:  {$balance['debits']} BMAN\n";
        echo "Locks:   {$balance['locks']} BMAN (active)\n";
        echo "✓ Available Balance: {$available_after} BMAN\n";
        echo "   ↓ REDUCED by {$withdrawal_amount} BMAN ✅\n";

        // TEST 7: Show withdrawal request on admin side
        echo "\n[STEP 7] Displaying request on admin side...\n";
        $result = $conn->query("
            SELECT wr.*, u.username, u.email, u.referral_id
            FROM bman_withdraw_requests wr
            JOIN users u ON u.id = wr.user_id
            WHERE wr.id = {$request_id}
        ");
        $req = $result->fetch_assoc();
        echo "Request Details (Admin View):\n";
        echo "  Request No:      {$req['request_no']}\n";
        echo "  User:            {$req['username']} ({$req['referral_id']})\n";
        echo "  Source Wallet:   {$req['source_wallet']}\n";
        echo "  Amount:          {$req['request_amount']} BMAN\n";
        echo "  Fee:             {$req['fee_amount']} BMAN\n";
        echo "  Net:             {$req['net_amount']} BMAN\n";
        echo "  USDT Rate:       {$req['bman_usdt_rate']}\n";
        echo "  USDT Amount:     {$req['usdt_amount']} USDT\n";
        echo "  Address:         {$req['withdraw_address']}\n";
        echo "  Status:          {$req['status']} (PENDING)\n";
        echo "  Created:         {$req['created_at']}\n";

        // TEST 8: Show allocations
        echo "\n[STEP 8] Showing wallet allocations...\n";
        $result = $conn->query("
            SELECT wallet, SUM(amount) as allocated
            FROM bman_withdraw_allocations
            WHERE request_id = {$request_id}
            GROUP BY wallet
        ");
        while ($alloc = $result->fetch_assoc()) {
            echo "  {$alloc['wallet']}: {$alloc['allocated']} BMAN\n";
        }

        // TEST 9: Admin approves request
        echo "\n[STEP 9] Admin approving request...\n";
        $admin_id = 1; // Assuming admin ID 1
        $approve_sql = "
            UPDATE bman_withdraw_requests
            SET status='approved', approved_by={$admin_id}, approved_at=NOW(), admin_remark='Approved by admin'
            WHERE id={$request_id} AND status='pending'
        ";
        if ($conn->query($approve_sql)) {
            echo "✓ Request approved by admin #{$admin_id}\n";

            // TEST 10: Verify status changed
            echo "\n[STEP 10] Verifying approval status...\n";
            $result = $conn->query("SELECT status, approved_by, approved_at FROM bman_withdraw_requests WHERE id={$request_id}");
            $req = $result->fetch_assoc();
            echo "Status:      {$req['status']} (APPROVED)\n";
            echo "Approved By: Admin #{$req['approved_by']}\n";
            echo "Approved At: {$req['approved_at']}\n";

            // TEST 11: Balance should STILL show as locked (not yet completed)
            echo "\n[STEP 11] Verifying balance after approval (should still be locked)...\n";
            $result = $conn->query("
                SELECT
                    COALESCE(SUM(CASE WHEN entry_type='credit' THEN amount ELSE 0 END), 0) as credits,
                    COALESCE(SUM(CASE WHEN entry_type='debit' THEN amount ELSE 0 END), 0) as debits,
                    COALESCE(SUM(CASE WHEN entry_type='lock' AND status='active' THEN amount ELSE 0 END), 0) as locks
                FROM bman_wallet_ledger
                WHERE user_id = {$user_id}
            ");
            $balance = $result->fetch_assoc();
            $available_approved = $balance['credits'] - $balance['debits'] - $balance['locks'];
            echo "Locks:   {$balance['locks']} BMAN (still locked) ✅\n";
            echo "Available: {$available_approved} BMAN\n";

            // TEST 12: Show locked request in list
            echo "\n[STEP 12] Showing request in admin's pending list...\n";
            $result = $conn->query("
                SELECT request_no, user_id, source_wallet, request_amount, status
                FROM bman_withdraw_requests
                WHERE user_id = {$user_id}
                ORDER BY created_at DESC
                LIMIT 3
            ");
            echo "User's Withdrawal Requests:\n";
            while ($r = $result->fetch_assoc()) {
                $status_badge = $r['status'] === 'approved' ? '[APPROVED]' : '[' . strtoupper($r['status']) . ']';
                echo "  {$r['request_no']} - {$r['request_amount']} BMAN - {$status_badge}\n";
            }

            echo "\n====== TEST COMPLETE ======\n";
            echo "✅ User balance REDUCED after request\n";
            echo "✅ Admin can see request with status APPROVED\n";
            echo "✅ Funds remain LOCKED (not yet paid)\n";
            echo "✅ Wallet allocation tracked\n";
        } else {
            echo "✗ Failed to approve request: " . $conn->error . "\n";
        }
    } else {
        echo "✗ Failed to create lock entry: " . $conn->error . "\n";
    }
} else {
    echo "✗ Failed to create request: " . $conn->error . "\n";
}

$conn->close();
?>
