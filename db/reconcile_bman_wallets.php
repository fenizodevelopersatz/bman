<?php
/**
 * BMAN wallet reconciliation guard.
 * Run any time (ad-hoc, nightly cron, or after a payout batch):
 *   php db/reconcile_bman_wallets.php
 *
 * Must print "0 rows" for both checks. Any row is a data-integrity bug —
 * either a code path mutated a balance directly / used float math instead of
 * the Money helper (application/helpers/money_helper.php), or a lock/debit
 * was written against a request that was never properly allocated.
 */

$dbConfig = [
    'host'     => 'localhost',
    'user'     => 'root',
    'password' => '',
    'database' => 'e-commerce-mlm-v2'
];

$conn = new mysqli($dbConfig['host'], $dbConfig['user'], $dbConfig['password'], $dbConfig['database']);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "====== BMAN WALLET RECONCILIATION ======\n";

// Check 1: every user's 4-wallet sum must equal their cumulative available balance.
echo "\n[CHECK 1] 4-wallet sum vs cumulative available...\n";
$sql1 = "
    SELECT w.user_id,
           SUM(w.available)                         AS sum_of_wallets,
           u.available                              AS cumulative,
           (SUM(w.available) - u.available)          AS drift
    FROM v_bman_wallet_balances w
    JOIN v_bman_user_available u ON u.user_id = w.user_id
    GROUP BY w.user_id, u.available
    HAVING ABS(SUM(w.available) - u.available) > 0.00000001
";
$result1 = $conn->query($sql1);
if (!$result1) {
    echo "✗ Query failed: " . $conn->error . "\n";
} elseif ($result1->num_rows === 0) {
    echo "✓ 0 rows — wallets and cumulative balance always agree.\n";
} else {
    echo "✗ {$result1->num_rows} row(s) — DRIFT DETECTED:\n";
    while ($row = $result1->fetch_assoc()) {
        echo "    user_id={$row['user_id']} sum_of_wallets={$row['sum_of_wallets']} cumulative={$row['cumulative']} drift={$row['drift']}\n";
    }
}

// Check 2: no wallet may ever go negative.
echo "\n[CHECK 2] No wallet balance below zero...\n";
$sql2 = "
    SELECT user_id, wallet, available
    FROM v_bman_wallet_balances
    WHERE available < -0.00000001
";
$result2 = $conn->query($sql2);
if (!$result2) {
    echo "✗ Query failed: " . $conn->error . "\n";
} elseif ($result2->num_rows === 0) {
    echo "✓ 0 rows — no wallet is negative.\n";
} else {
    echo "✗ {$result2->num_rows} row(s) — NEGATIVE BALANCE:\n";
    while ($row = $result2->fetch_assoc()) {
        echo "    user_id={$row['user_id']} wallet={$row['wallet']} available={$row['available']}\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
$clean = ($result1 && $result1->num_rows === 0) && ($result2 && $result2->num_rows === 0);
echo $clean ? "✅ Reconciliation clean.\n" : "⚠️  Reconciliation found issues — see above.\n";

$conn->close();
