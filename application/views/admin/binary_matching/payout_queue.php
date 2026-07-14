<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title">Blockchain Payout Queue</h1>
        <div class="page-meta">Monitor pending, confirmed, and failed bonus payouts</div>
    </div>

    <!-- Status Summary -->
    <div class="row mb-4">
        <?php
        $status_map = ['PENDING' => 'info', 'RETRYING' => 'warning', 'CONFIRMED' => 'success', 'FAILED' => 'danger'];
        $icons = ['PENDING' => '⏳', 'RETRYING' => '🔄', 'CONFIRMED' => '✓', 'FAILED' => '✗'];
        ?>
        <?php foreach ($status_summary as $stat): ?>
        <div class="col-md-3">
            <div class="card border-left-<?= $status_map[$stat['status']] ?? 'secondary' ?>">
                <div class="card-body">
                    <div class="text-<?= $status_map[$stat['status']] ?? 'secondary' ?> small font-weight-bold text-uppercase mb-1">
                        <?= $icons[$stat['status']] ?? '•' ?> <?= $stat['status'] ?>
                    </div>
                    <div class="h3 mb-0"><?= $stat['count'] ?></div>
                    <small class="text-muted">$<?= number_format($stat['total'] ?? 0, 2) ?></small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Gas Balance Alert -->
    <?php if ($admin_gas && $admin_gas->gas_balance < 0.1): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <strong>⚠️ Critical Alert!</strong> Admin gas balance is low (<?= number_format($admin_gas->gas_balance, 3) ?> BNB).
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php endif; ?>

    <!-- Filter & Actions -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="form-inline">
                <div class="form-group mr-2">
                    <label class="mr-2">Filter by Status:</label>
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="PENDING" <?= $status_filter === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                        <option value="RETRYING" <?= $status_filter === 'RETRYING' ? 'selected' : '' ?>>Retrying</option>
                        <option value="CONFIRMED" <?= $status_filter === 'CONFIRMED' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="FAILED" <?= $status_filter === 'FAILED' ? 'selected' : '' ?>>Failed</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?= base_url('admin/binary-matching/payout-queue') ?>" class="btn btn-secondary ml-2">Reset</a>

                <div class="ml-auto">
                    <button type="button" class="btn btn-warning" id="retryFailedBtn">
                        <i class="fas fa-sync"></i> Retry Failed Payouts
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Payout Queue Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Wallet Type</th>
                            <th class="text-right">Amount</th>
                            <th>Transaction Hash</th>
                            <th class="text-center">Status</th>
                            <th>Retries</th>
                            <th class="text-right">Gas Used</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($payouts)): ?>
                            <?php foreach ($payouts as $payout): ?>
                            <tr>
                                <td><code><?= $payout['id'] ?></code></td>
                                <td>
                                    <strong><?= $payout['username'] ?></strong><br>
                                    <small class="text-muted">UID: <?= $payout['to_user_id'] ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $payout['wallet_type'] === 'earning' ? 'primary' : 'success' ?>">
                                        <?= ucfirst($payout['wallet_type']) ?>
                                    </span>
                                </td>
                                <td class="text-right">
                                    <strong>$<?= number_format($payout['amount'] ?? 0, 2) ?></strong>
                                </td>
                                <td>
                                    <code class="text-monospace">
                                        <?php if ($payout['transaction_hash']): ?>
                                            <a href="#" title="<?= $payout['transaction_hash'] ?>" class="text-decoration-none">
                                                <?= substr($payout['transaction_hash'], 0, 10) ?>...
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </code>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $status = $payout['status'];
                                    $badge_class = [
                                        'PENDING' => 'info',
                                        'RETRYING' => 'warning',
                                        'CONFIRMED' => 'success',
                                        'FAILED' => 'danger'
                                    ];
                                    ?>
                                    <span class="badge badge-<?= $badge_class[$status] ?? 'secondary' ?>">
                                        <?= $status ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-<?= ($payout['retry_count'] ?? 0) >= 3 ? 'danger' : 'warning' ?>">
                                        <?= $payout['retry_count'] ?? 0 ?>/3
                                    </span>
                                </td>
                                <td class="text-right">
                                    <?php if ($payout['gas_used']): ?>
                                        <small><?= number_format($payout['gas_used'], 3) ?> BNB</small>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><small><?= date('M d, H:i', strtotime($payout['updated_at'])) ?></small></td>
                            </tr>

                            <!-- Error Details (if failed) -->
                            <?php if ($payout['status'] === 'FAILED' && $payout['last_error']): ?>
                            <tr class="table-danger">
                                <td colspan="9" class="py-2">
                                    <strong class="text-danger">Error:</strong> <code><?= htmlspecialchars($payout['last_error']) ?></code>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">No payouts found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($current_page && ceil(count($payouts) / $per_page) > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($current_page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= base_url('admin/binary-matching/payout-queue?page=1' . ($status_filter ? '&status=' . $status_filter : '')) ?>">First</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="<?= base_url('admin/binary-matching/payout-queue?page=' . ($current_page - 1) . ($status_filter ? '&status=' . $status_filter : '')) ?>">Previous</a>
                    </li>
                    <?php endif; ?>

                    <?php if ($current_page < ceil(count($payouts) / $per_page)): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= base_url('admin/binary-matching/payout-queue?page=' . ($current_page + 1) . ($status_filter ? '&status=' . $status_filter : '')) ?>">Next</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Info Box -->
    <div class="alert alert-info mt-4">
        <strong>Payout Queue Status:</strong>
        <br>• <span class="badge badge-info">PENDING</span> = Queued, waiting to broadcast
        <br>• <span class="badge badge-warning">RETRYING</span> = Broadcast failed, will retry (max 3 attempts)
        <br>• <span class="badge badge-success">CONFIRMED</span> = On-chain confirmed (12 blocks)
        <br>• <span class="badge badge-danger">FAILED</span> = Max retries reached
    </div>

</div>

<script>
document.getElementById('retryFailedBtn')?.addEventListener('click', function() {
    if (confirm('Retry all failed payouts?')) {
        fetch('<?= base_url("admin/binary-matching/retry-payouts") ?>', {
            method: 'POST'
        })
        .then(r => r.json())
        .then(data => {
            if (data.status) {
                alert('✓ Retried ' + data.count + ' failed payouts');
                location.reload();
            } else {
                alert('✗ Error: ' + data.message);
            }
        });
    }
});
</script>
