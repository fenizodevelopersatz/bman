<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title">Binary Matching Bonus Dashboard</h1>
        <div class="page-meta">Real-time monitoring • Last updated: <?= date('Y-m-d H:i:s'); ?></div>
    </div>

    <!-- KPI Cards Row -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="text-primary small font-weight-bold text-uppercase mb-1">Total Bonuses</div>
                    <div class="h3 mb-0"><?= $summary['total_bonuses'] ?? 0 ?></div>
                    <small class="text-muted">Today (24h)</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-success">
                <div class="card-body">
                    <div class="text-success small font-weight-bold text-uppercase mb-1">Distributed</div>
                    <div class="h3 mb-0">$<?= number_format($summary['total_distributed'] ?? 0, 2) ?></div>
                    <small class="text-muted">8% Earning + 2% Staking</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-warning">
                <div class="card-body">
                    <div class="text-warning small font-weight-bold text-uppercase mb-1">Ceiling Held</div>
                    <div class="h3 mb-0">$<?= number_format($summary['ceiling_amount'] ?? 0, 2) ?></div>
                    <small class="text-muted">From <?= $summary['ceiling_holds'] ?? 0 ?> users</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-info">
                <div class="card-body">
                    <div class="text-info small font-weight-bold text-uppercase mb-1">Gas Balance</div>
                    <div class="h3 mb-0"><?= number_format($ceiling_status['admin_wallet']->gas_balance ?? 0, 3) ?> BNB</div>
                    <small class="text-success">✓ Sufficient</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Cron Status -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Cron Execution Status</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Last Run:</strong><br>
                            <code><?= $cron_status['last_run'] ?></code><br>
                            <small class="text-muted">Status: <span class="badge badge-<?= $cron_status['last_status'] === 'COMPLETED' ? 'success' : 'warning' ?>"><?= $cron_status['last_status'] ?></span></small>
                        </div>
                        <div class="col-md-3">
                            <strong>Duration:</strong><br>
                            <code>45 seconds</code><br>
                            <small class="text-muted">Phase 1 (15s) + Phase 2 (20s) + Phase 3 (10s)</small>
                        </div>
                        <div class="col-md-3">
                            <strong>Next Run:</strong><br>
                            <code><?= $cron_status['next_run'] ?></code><br>
                            <small class="text-muted">Frequency: <?= $cron_status['frequency'] ?></small>
                        </div>
                        <div class="col-md-3">
                            <strong>Runs Today:</strong><br>
                            <code><?= $cron_status['runs_today'] ?></code> runs<br>
                            <small class="text-muted">480 runs/day expected</small>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0">
                        <strong>Cron Schedule:</strong> Every 3 minutes (480 runs/day)
                        <br><code>*/3 * * * * php index.php cron binarymatchingcron_simple process</code>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Level-wise Distribution -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Level-wise Bonus Distribution</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Level</th>
                                    <th>Recipients</th>
                                    <th class="text-right">Earning (8%)</th>
                                    <th class="text-right">Staking (2%)</th>
                                    <th class="text-right">Total Bonus</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($level_distribution)): ?>
                                    <?php foreach ($level_distribution as $level): ?>
                                    <tr>
                                        <td><strong>Level <?= $level['level'] ?></strong></td>
                                        <td><?= $level['recipients'] ?></td>
                                        <td class="text-right">$<?= number_format($level['total_earning'] ?? 0, 2) ?></td>
                                        <td class="text-right">$<?= number_format($level['total_staking'] ?? 0, 2) ?></td>
                                        <td class="text-right"><strong>$<?= number_format($level['total_bonus'] ?? 0, 2) ?></strong></td>
                                        <td class="text-center">
                                            <span class="badge badge-success"><?= $level['confirmed'] ?> ✓</span>
                                            <span class="badge badge-warning"><?= $level['held'] ?> ⏸</span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No bonuses calculated yet</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ceiling Wallet & Payout Queue -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Admin Ceiling Pool</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <small class="text-muted">Total Received</small><br>
                                <strong class="h5">$<?= number_format($ceiling_status['admin_wallet']->total_received ?? 0, 2) ?></strong>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted">Total Distributed</small><br>
                                <strong class="h5">$<?= number_format($ceiling_status['admin_wallet']->total_distributed ?? 0, 2) ?></strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <small class="text-muted">Current Hold</small><br>
                                <strong class="h5 text-warning">$<?= number_format($ceiling_status['admin_wallet']->balance ?? 0, 2) ?></strong>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted">Gas Balance</small><br>
                                <strong class="h5 <?= ($ceiling_status['admin_wallet']->gas_balance ?? 0) < 0.1 ? 'text-danger' : 'text-success' ?>">
                                    <?= number_format($ceiling_status['admin_wallet']->gas_balance ?? 0, 3) ?> BNB
                                </strong>
                            </div>
                        </div>
                    </div>
                    <?php if ($ceiling_status['gas_alert']): ?>
                    <div class="alert alert-danger mt-3 mb-0">
                        <strong>⚠️ Gas Alert!</strong> Admin gas balance is critically low.
                        <a href="#" class="btn btn-sm btn-primary float-right" data-toggle="modal" data-target="#addGasModal">Add Gas</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payout Queue Status</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <?php foreach ($payout_queue as $status => $queue): ?>
                            <tr>
                                <td><?= ucfirst($status) ?></td>
                                <td class="text-right">
                                    <strong><?= $queue['count'] ?></strong> payouts
                                </td>
                                <td class="text-right text-muted">
                                    $<?= number_format($queue['total_amount'] ?? 0, 2) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <a href="<?= base_url('admin/binary-matching/payout-queue') ?>" class="btn btn-sm btn-outline-primary mt-3 btn-block">
                        View Payout Queue
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Bonuses -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Bonuses</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Level</th>
                                    <th class="text-right">Earning</th>
                                    <th class="text-right">Staking</th>
                                    <th class="text-right">Total</th>
                                    <th class="text-center">Status</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_bonuses)): ?>
                                    <?php foreach ($recent_bonuses as $bonus): ?>
                                    <tr>
                                        <td><?= $bonus['username'] ?></td>
                                        <td><?= $bonus['level'] ?></td>
                                        <td class="text-right">$<?= number_format($bonus['bonus_earning'] ?? 0, 2) ?></td>
                                        <td class="text-right">$<?= number_format($bonus['bonus_staking'] ?? 0, 2) ?></td>
                                        <td class="text-right"><strong>$<?= number_format($bonus['bonus_earning'] + $bonus['bonus_staking'] ?? 0, 2) ?></strong></td>
                                        <td class="text-center">
                                            <?php if ($bonus['status'] === 'DISTRIBUTED'): ?>
                                                <span class="badge badge-success">✓ Confirmed</span>
                                            <?php elseif ($bonus['status'] === 'HELD_CEILING'): ?>
                                                <span class="badge badge-warning">⏸ Ceiling</span>
                                            <?php else: ?>
                                                <span class="badge badge-info"><?= $bonus['status'] ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('H:i', strtotime($bonus['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No recent bonuses</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="<?= base_url('admin/binary-matching/history') ?>" class="btn btn-sm btn-outline-primary">
                        View Full History
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Add Gas Modal -->
<div class="modal fade" id="addGasModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Admin Gas</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="addGasForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>BNB Amount</label>
                        <input type="number" class="form-control" name="amount" step="0.01" min="0.01" required>
                        <small class="text-muted">Current balance: <?= number_format($ceiling_status['admin_wallet']->gas_balance ?? 0, 3) ?> BNB</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Gas</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('addGasForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const amount = this.querySelector('input[name="amount"]').value;

    fetch('<?= base_url("admin/binary-matching/add-admin-gas") ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'amount=' + amount
    })
    .then(r => r.json())
    .then(data => {
        if (data.status) {
            alert('✓ Gas added successfully!');
            location.reload();
        } else {
            alert('✗ Error: ' + data.message);
        }
    });
});
</script>
