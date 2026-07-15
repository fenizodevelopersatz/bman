<?php $this->load->view('admin/Layout/common_style'); ?>
<body>
<div class="container-fluid p-4">
    <h3><?= htmlspecialchars($title); ?></h3>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Request No</th><th>User</th><th>Source</th><th>Amount</th><th>Fee</th><th>Net</th><th>Status</th><th>Tx Hash</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach (($rows ?? []) as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['request_no']); ?></td>
                    <td><?= htmlspecialchars(($row['username'] ?? '-') . ' / ' . ($row['referral_id'] ?? '-')); ?></td>
                    <td><?= htmlspecialchars($row['source_wallet']); ?></td>
                    <td><?= number_format((float)$row['request_amount'], 4); ?></td>
                    <td><?= number_format((float)$row['fee_amount'], 4); ?></td>
                    <td><?= number_format((float)$row['net_amount'], 4); ?></td>
                    <td><?= htmlspecialchars($row['status']); ?></td>
                    <td><?= htmlspecialchars($row['tx_hash'] ?? '-'); ?></td>
                    <td><a class="btn btn-sm btn-primary" href="<?= base_url('admin/bman-withdrawals/view/' . $row['id']); ?>">View</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
