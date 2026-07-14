<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title">Binary Matching Bonus History</h1>
        <div class="page-meta">Complete audit trail of all bonuses calculated</div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="form-inline">
                <div class="form-group mr-2">
                    <label class="mr-2">Level:</label>
                    <select name="level" class="form-control">
                        <option value="">All Levels</option>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?= $i ?>" <?= $level_filter == $i ? 'selected' : '' ?>>Level <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group mr-2">
                    <label class="mr-2">Status:</label>
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="DISTRIBUTED" <?= $status_filter === 'DISTRIBUTED' ? 'selected' : '' ?>>Distributed</option>
                        <option value="HELD_CEILING" <?= $status_filter === 'HELD_CEILING' ? 'selected' : '' ?>>Held (Ceiling)</option>
                        <option value="PENDING" <?= $status_filter === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                        <option value="FAILED" <?= $status_filter === 'FAILED' ? 'selected' : '' ?>>Failed</option>
                    </select>
                </div>

                <div class="form-group mr-2">
                    <label class="mr-2">User ID:</label>
                    <input type="number" name="user_id" class="form-control" placeholder="Search user" value="<?= $user_filter ?>">
                </div>

                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?= base_url('admin/binary-matching/history') ?>" class="btn btn-secondary ml-2">Reset</a>
            </form>
        </div>
    </div>

    <!-- Bonus History Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Recipient</th>
                            <th>Level</th>
                            <th class="text-right">Left Leg</th>
                            <th class="text-right">Right Leg</th>
                            <th class="text-right">Qualifying Vol</th>
                            <th class="text-right">Earning (8%)</th>
                            <th class="text-right">Staking (2%)</th>
                            <th class="text-right">Total Bonus</th>
                            <th class="text-center">Status</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($bonuses)): ?>
                            <?php foreach ($bonuses as $bonus): ?>
                            <tr>
                                <td><code><?= $bonus['id'] ?></code></td>
                                <td>
                                    <strong><?= $bonus['recipient_name'] ?></strong><br>
                                    <small class="text-muted">Exchange: $<?= number_format($bonus['exchange_balance'] ?? 0, 2) ?></small>
                                </td>
                                <td><span class="badge badge-info">Level <?= $bonus['level'] ?></span></td>
                                <td class="text-right">$<?= number_format($bonus['left_leg_volume'] ?? 0, 2) ?></td>
                                <td class="text-right">$<?= number_format($bonus['right_leg_volume'] ?? 0, 2) ?></td>
                                <td class="text-right">
                                    <strong>$<?= number_format($bonus['qualifying_volume'] ?? 0, 2) ?></strong><br>
                                    <small>min(L, R)</small>
                                </td>
                                <td class="text-right">$<?= number_format($bonus['bonus_earning'] ?? 0, 2) ?></td>
                                <td class="text-right">$<?= number_format($bonus['bonus_staking'] ?? 0, 2) ?></td>
                                <td class="text-right"><strong>$<?= number_format($bonus['bonus_amount_total'] ?? 0, 2) ?></strong></td>
                                <td class="text-center">
                                    <?php if ($bonus['status'] === 'DISTRIBUTED'): ?>
                                        <span class="badge badge-success">✓ Confirmed</span>
                                    <?php elseif ($bonus['status'] === 'HELD_CEILING'): ?>
                                        <span class="badge badge-warning">⏸ Ceiling Held</span>
                                        <br><small class="text-muted">$<?= number_format($bonus['ceiling_amount_held'] ?? 0, 2) ?></small>
                                    <?php else: ?>
                                        <span class="badge badge-<?= $bonus['status'] === 'PENDING' ? 'info' : 'danger' ?>"><?= $bonus['status'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><small><?= date('M d, H:i', strtotime($bonus['created_at'])) ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="text-center text-muted">No bonuses found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($current_page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= base_url('admin/binary-matching/history?page=1' . ($level_filter ? '&level=' . $level_filter : '') . ($status_filter ? '&status=' . $status_filter : '') . ($user_filter ? '&user_id=' . $user_filter : '')) ?>">First</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="<?= base_url('admin/binary-matching/history?page=' . ($current_page - 1) . ($level_filter ? '&level=' . $level_filter : '') . ($status_filter ? '&status=' . $status_filter : '') . ($user_filter ? '&user_id=' . $user_filter : '')) ?>">Previous</a>
                    </li>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $current_page - 2);
                    $end = min($total_pages, $current_page + 2);
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                    <li class="page-item <?= $i === $current_page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= base_url('admin/binary-matching/history?page=' . $i . ($level_filter ? '&level=' . $level_filter : '') . ($status_filter ? '&status=' . $status_filter : '') . ($user_filter ? '&user_id=' . $user_filter : '')) ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($current_page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= base_url('admin/binary-matching/history?page=' . ($current_page + 1) . ($level_filter ? '&level=' . $level_filter : '') . ($status_filter ? '&status=' . $status_filter : '') . ($user_filter ? '&user_id=' . $user_filter : '')) ?>">Next</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="<?= base_url('admin/binary-matching/history?page=' . $total_pages . ($level_filter ? '&level=' . $level_filter : '') . ($status_filter ? '&status=' . $status_filter : '') . ($user_filter ? '&user_id=' . $user_filter : '')) ?>">Last</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>

            <div class="text-center mt-2 text-muted">
                <small>Showing <?= (($current_page - 1) * $per_page) + 1 ?> to <?= min($current_page * $per_page, $total) ?> of <?= $total ?> bonuses</small>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Info Box -->
    <div class="alert alert-info mt-4">
        <strong>Bonus Calculation:</strong> min(left_leg_volume, right_leg_volume) × 10% = total bonus (8% earning wallet + 2% staking wallet)
        <br><strong>Ceiling Hold:</strong> If exchange balance exceeds ceiling cap, bonus is held in admin pool pending user upgrade
    </div>

</div>
