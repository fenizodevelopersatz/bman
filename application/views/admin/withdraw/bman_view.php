<?php $this->load->view('admin/Layout/common_style'); ?>
<body>
<div class="container-fluid p-4">
    <h3><?= htmlspecialchars($title); ?></h3>
    <?php if (!$this->session->flashdata('success') === null): ?>
        <div class="alert alert-success"><?= htmlspecialchars($this->session->flashdata('success')); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($this->session->flashdata('error')); ?></div>
    <?php endif; ?>

    <?php if (empty($row)): ?>
        <div class="alert alert-danger">Request not found</div>
    <?php else: ?>

    <!-- Request Details -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Request Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Request No:</strong> <?= htmlspecialchars($row['request_no']); ?></p>
                    <p><strong>User:</strong> <?= htmlspecialchars(($row['username'] ?? '-') . ' / ' . ($row['referral_id'] ?? '-')); ?></p>
                    <p><strong>Source Wallet:</strong> <span class="badge bg-info"><?= htmlspecialchars($row['source_wallet']); ?></span></p>
                    <p><strong>Status:</strong> <span class="badge bg-<?= $row['status'] === 'completed' ? 'success' : ($row['status'] === 'rejected' ? 'danger' : 'warning'); ?>"><?= htmlspecialchars($row['status']); ?></span></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Amount:</strong> <?= number_format((float)$row['request_amount'], 4); ?> BMAN</p>
                    <p><strong>Fee:</strong> <?= number_format((float)$row['fee_amount'], 4); ?> BMAN</p>
                    <p><strong>Net Amount:</strong> <?= number_format((float)$row['net_amount'], 4); ?> BMAN</p>
                    <p><strong>USDT Rate:</strong> <?= number_format((float)($row['bman_usdt_rate'] ?? 0), 8); ?></p>
                </div>
            </div>
            <hr>
            <p><strong>Withdraw Address:</strong> <code><?= htmlspecialchars($row['withdraw_address']); ?></code></p>
            <p><strong>Tx Hash:</strong> <?= empty($row['tx_hash']) ? '<em>Not yet confirmed</em>' : '<code>' . htmlspecialchars($row['tx_hash']) . '</code>'; ?></p>
            <p><strong>Created:</strong> <?= htmlspecialchars($row['created_at'] ?? '-'); ?></p>
            <?php if (!empty($row['approved_at'])): ?>
                <p><strong>Approved At:</strong> <?= htmlspecialchars($row['approved_at']); ?> by Admin #<?= $row['approved_by']; ?></p>
            <?php endif; ?>
            <?php if (!empty($row['completed_at'])): ?>
                <p><strong>Completed At:</strong> <?= htmlspecialchars($row['completed_at']); ?></p>
            <?php endif; ?>
            <?php if (!empty($row['remark'])): ?>
                <p><strong>User Remark:</strong> <em><?= htmlspecialchars($row['remark']); ?></em></p>
            <?php endif; ?>
            <?php if (!empty($row['admin_remark'])): ?>
                <p><strong>Admin Remark:</strong> <em><?= htmlspecialchars($row['admin_remark']); ?></em></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Wallet Allocations (if mixed) -->
    <?php if ($row['source_wallet'] === 'mixed'): ?>
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Wallet Allocations</h5>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <thead>
                    <tr><th>Wallet</th><th>Amount</th></tr>
                </thead>
                <tbody>
                <?php if (!empty($allocations)): ?>
                    <?php foreach ($allocations as $alloc): ?>
                    <tr>
                        <td><?= htmlspecialchars($alloc['wallet']); ?></td>
                        <td><?= number_format((float)$alloc['amount'], 4); ?> BMAN</td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="2" class="text-muted">No allocation data found</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Status Transition Form -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Update Status</h5>
        </div>
        <div class="card-body">
            <form method="post" action="<?= base_url('admin/bman-withdrawals/update/' . $row['id']); ?>">
                <div class="mb-3">
                    <label class="form-label">New Status</label>
                    <select name="status" class="form-select" required>
                        <option value="">-- Select Status --</option>
                        <?php if ($row['status'] === 'pending'): ?>
                            <option value="approved">Approve Request</option>
                            <option value="rejected">Reject Request</option>
                        <?php elseif ($row['status'] === 'approved'): ?>
                            <option value="processing">Mark as Processing</option>
                            <option value="rejected">Reject Request</option>
                        <?php elseif ($row['status'] === 'processing'): ?>
                            <option value="completed">Complete (with tx_hash)</option>
                            <option value="failed">Mark as Failed</option>
                        <?php else: ?>
                            <option value="">-- Terminal State (No changes) --</option>
                        <?php endif; ?>
                    </select>
                    <small class="text-muted">
                        Current: <strong><?= htmlspecialchars($row['status']); ?></strong>
                    </small>
                </div>

                <?php if ($row['status'] === 'processing' || in_array($row['status'], ['pending', 'approved', 'processing'])): ?>
                <div class="mb-3">
                    <label class="form-label">Transaction Hash (for completion)</label>
                    <input type="text" name="tx_hash" class="form-control" placeholder="0xabcd..." value="<?= htmlspecialchars($row['tx_hash'] ?? ''); ?>">
                    <small class="text-muted">Required when marking as completed</small>
                </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Admin Remark</label>
                    <textarea name="admin_remark" class="form-control" rows="3" placeholder="Reason for status change..."></textarea>
                </div>

                <button class="btn btn-success" type="submit">Update Status</button>
                <a href="<?= base_url('admin/bman-withdrawals'); ?>" class="btn btn-secondary">Back to List</a>
            </form>
        </div>
    </div>

    <?php endif; ?>
</div>
</body>
