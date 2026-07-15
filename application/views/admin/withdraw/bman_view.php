<?php $this->load->view('admin/Layout/common_style'); ?>
<body>
<div class="container-fluid p-4">
    <h3><?= htmlspecialchars($title); ?></h3>
    <?php if (empty($row)): ?>
        <div class="alert alert-danger">Request not found</div>
    <?php else: ?>
    <div class="card">
        <div class="card-body">
            <p><strong>Request No:</strong> <?= htmlspecialchars($row['request_no']); ?></p>
            <p><strong>User:</strong> <?= htmlspecialchars(($row['username'] ?? '-') . ' / ' . ($row['referral_id'] ?? '-')); ?></p>
            <p><strong>Source Wallet:</strong> <?= htmlspecialchars($row['source_wallet']); ?></p>
            <p><strong>Amount:</strong> <?= number_format((float)$row['request_amount'], 4); ?></p>
            <p><strong>Fee:</strong> <?= number_format((float)$row['fee_amount'], 4); ?></p>
            <p><strong>Net:</strong> <?= number_format((float)$row['net_amount'], 4); ?></p>
            <p><strong>Address:</strong> <?= htmlspecialchars($row['withdraw_address']); ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($row['status']); ?></p>
            <p><strong>Tx Hash:</strong> <?= htmlspecialchars($row['tx_hash'] ?? '-'); ?></p>
            <form method="post" action="<?= base_url('admin/bman-withdrawals/update/' . $row['id']); ?>">
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="approved">Approve / Complete</option>
                        <option value="rejected">Reject</option>
                        <option value="processing">Processing</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tx Hash</label>
                    <input type="text" name="tx_hash" class="form-control" placeholder="0x..." value="<?= htmlspecialchars($row['tx_hash'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Admin Remark</label>
                    <textarea name="admin_remark" class="form-control" rows="4"></textarea>
                </div>
                <button class="btn btn-success" type="submit">Save</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>
</body>
