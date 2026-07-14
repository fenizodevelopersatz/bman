<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title">Ceiling Wallet Management</h1>
        <div class="page-meta">View and manage user ceiling holds and admin pool</div>
    </div>

    <!-- Admin Ceiling Pool Summary -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Admin Ceiling Pool</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h6 class="text-muted mb-1">Total Received</h6>
                                <h4 class="mb-0">$<?= number_format($admin_ceiling['admin_wallet']->total_received ?? 0, 2) ?></h4>
                                <small class="text-muted">All time</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h6 class="text-muted mb-1">Total Distributed</h6>
                                <h4 class="mb-0">$<?= number_format($admin_ceiling['admin_wallet']->total_distributed ?? 0, 2) ?></h4>
                                <small class="text-muted">Released to users</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h6 class="text-muted mb-1">Current Hold</h6>
                                <h4 class="text-warning mb-0">$<?= number_format($admin_ceiling['admin_wallet']->balance ?? 0, 2) ?></h4>
                                <small class="text-muted">In admin pool</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h6 class="text-muted mb-1">Users Held</h6>
                                <h4 class="mb-0"><?= count($ceiling_wallets) ?></h4>
                                <small class="text-muted">With ceiling holds</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Ceiling Holds -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">User Ceiling Holds</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th class="text-right">Current Hold</th>
                                    <th class="text-right">Exchange Balance</th>
                                    <th class="text-right">Ceiling Cap</th>
                                    <th class="text-right">Threshold</th>
                                    <th class="text-center">Hold Type</th>
                                    <th>Reason</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($ceiling_wallets)): ?>
                                    <?php foreach ($ceiling_wallets as $wallet): ?>
                                    <tr>
                                        <td>
                                            <strong><?= $wallet['username'] ?></strong><br>
                                            <small class="text-muted">ID: <?= $wallet['user_id'] ?></small>
                                        </td>
                                        <td class="text-right text-warning">
                                            <strong>$<?= number_format($wallet['current_hold'] ?? 0, 2) ?></strong>
                                        </td>
                                        <td class="text-right">
                                            $<?= number_format($wallet['exchange_balance'] ?? 0, 2) ?>
                                        </td>
                                        <td class="text-right">
                                            $<?= number_format($wallet['ceiling_cap'] ?? 0, 2) ?>
                                        </td>
                                        <td class="text-right text-muted">
                                            <small>$<?= number_format($wallet['threshold_amount'] ?? 0, 2) ?></small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-warning"><?= ucfirst($wallet['hold_type'] ?? 'BONUS_CAP') ?></span>
                                        </td>
                                        <td>
                                            <small><?= $wallet['hold_reason'] ?></small>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($wallet['current_hold'] > 0): ?>
                                            <button class="btn btn-sm btn-outline-primary release-hold" data-user-id="<?= $wallet['user_id'] ?>">
                                                Release
                                            </button>
                                            <?php else: ?>
                                            <span class="text-muted"><small>No hold</small></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No ceiling holds currently</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ceiling Wallet Ledger -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Ceiling Transactions</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th class="text-right">Amount</th>
                                    <th>From Wallet</th>
                                    <th>Reason</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($ceiling_ledger)): ?>
                                    <?php foreach ($ceiling_ledger as $ledger): ?>
                                    <tr>
                                        <td>
                                            <strong><?= $ledger['username'] ?></strong><br>
                                            <small class="text-muted">ID: <?= $ledger['user_id'] ?></small>
                                        </td>
                                        <td>
                                            <?php if ($ledger['transaction_type'] === 'HOLD'): ?>
                                                <span class="badge badge-warning">HOLD</span>
                                            <?php elseif ($ledger['transaction_type'] === 'RELEASE'): ?>
                                                <span class="badge badge-success">RELEASE</span>
                                            <?php else: ?>
                                                <span class="badge badge-info"><?= $ledger['transaction_type'] ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right">
                                            <strong>$<?= number_format($ledger['amount'] ?? 0, 2) ?></strong>
                                        </td>
                                        <td>
                                            <small><?= ucfirst($ledger['from_wallet'] ?? 'N/A') ?></small>
                                        </td>
                                        <td>
                                            <small><?= $ledger['reason'] ?></small>
                                        </td>
                                        <td><small><?= date('M d, H:i', strtotime($ledger['created_at'])) ?></small></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No transactions</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Box -->
    <div class="alert alert-info mt-4">
        <strong>Ceiling Wallet System:</strong> Users exceeding their ceiling cap have bonuses held in the admin pool. When they upgrade their staking package, the hold is released. Current configuration has 9 tiers: $5K to $500K+ packages.
    </div>

</div>

<script>
document.querySelectorAll('.release-hold').forEach(btn => {
    btn.addEventListener('click', function() {
        const userId = this.dataset.userId;
        if (confirm('Release ceiling hold for this user?')) {
            fetch('<?= base_url("admin/binary-matching/release-hold/") ?>' + userId, {
                method: 'POST'
            })
            .then(r => r.json())
            .then(data => {
                if (data.status) {
                    alert('✓ Hold released: $' + data.released);
                    location.reload();
                } else {
                    alert('✗ Error: ' + data.message);
                }
            });
        }
    });
});
</script>
