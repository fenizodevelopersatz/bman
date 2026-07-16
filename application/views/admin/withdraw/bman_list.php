<?php $this->load->view('admin/Layout/common_style'); ?>
<body>
<div class="container-fluid p-4">
    <h3><?= htmlspecialchars($title); ?></h3>

    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-md-4">
            <input type="text" id="filter_q" class="form-control" placeholder="Search: request no, user, email...">
        </div>
        <div class="col-md-3">
            <select id="filter_status" class="form-select">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="processing">Processing</option>
                <option value="completed">Completed</option>
                <option value="rejected">Rejected</option>
                <option value="failed">Failed</option>
            </select>
        </div>
        <div class="col-md-3">
            <select id="filter_source" class="form-select">
                <option value="">All Wallets</option>
                <option value="mixed">Mixed</option>
                <option value="exchange">Exchange</option>
                <option value="earning">Earning</option>
                <option value="staking">Staking</option>
                <option value="bonus">Bonus</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-secondary w-100" onclick="applyFilters()">Filter</button>
        </div>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover">
            <thead class="table-light">
                <tr>
                    <th>Request No</th>
                    <th>User</th>
                    <th>Bank Account</th>
                    <th>Wallet</th>
                    <th>BMAN Amount</th>
                    <th>USDT</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($rows)): foreach (($rows ?? []) as $row): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($row['request_no']); ?></strong>
                    </td>
                    <td>
                        <div><?= htmlspecialchars($row['username'] ?? '-'); ?></div>
                        <small class="text-muted"><?= htmlspecialchars($row['email'] ?? '-'); ?></small><br>
                        <small class="text-muted">UID: <?= htmlspecialchars($row['referral_id'] ?? '-'); ?></small>
                    </td>
                    <td>
                        <div><strong><?= htmlspecialchars($row['holder_name'] ?? '-'); ?></strong></div>
                        <small><?= htmlspecialchars($row['bank_name'] ?? '-'); ?></small><br>
                        <small class="text-muted">A/C: <?= htmlspecialchars(substr($row['account_number'] ?? '', -4) ?? '-'); ?></small><br>
                        <?php if (!empty($row['upi_id'])): ?>
                            <small class="text-muted">UPI: <?= htmlspecialchars($row['upi_id']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-info"><?= htmlspecialchars($row['source_wallet']); ?></span>
                    </td>
                    <td>
                        <div><?= number_format((float)$row['request_amount'], 4); ?> BMAN</div>
                        <small class="text-muted">Fee: <?= number_format((float)$row['fee_amount'], 4); ?></small><br>
                        <small class="text-muted">Net: <?= number_format((float)$row['net_amount'], 4); ?></small>
                    </td>
                    <td>
                        <div><?= number_format((float)$row['usdt_amount'], 2); ?> USDT</div>
                        <small class="text-muted">Rate: <?= number_format((float)$row['bman_usdt_rate'], 4); ?></small>
                    </td>
                    <td>
                        <?php
                        $statusClass = '';
                        if ($row['status'] === 'completed') $statusClass = 'bg-success';
                        elseif ($row['status'] === 'approved') $statusClass = 'bg-info';
                        elseif ($row['status'] === 'pending') $statusClass = 'bg-warning';
                        elseif ($row['status'] === 'processing') $statusClass = 'bg-primary';
                        else $statusClass = 'bg-danger';
                        ?>
                        <span class="badge <?= $statusClass; ?>"><?= htmlspecialchars($row['status']); ?></span>
                    </td>
                    <td>
                        <small><?= htmlspecialchars(date('M d, H:i', strtotime($row['created_at']))); ?></small>
                    </td>
                    <td>
                        <a class="btn btn-sm btn-primary" href="<?= base_url('admin/bman-withdrawals/view/' . $row['id']); ?>">View</a>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="9" class="text-center text-muted">No requests found</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function applyFilters() {
    const q = document.getElementById('filter_q').value;
    const status = document.getElementById('filter_status').value;
    const source = document.getElementById('filter_source').value;

    let url = '<?= base_url('admin/bman-withdrawals'); ?>';
    const params = [];
    if (q) params.push('q=' + encodeURIComponent(q));
    if (status) params.push('status=' + encodeURIComponent(status));
    if (source) params.push('source_wallet=' + encodeURIComponent(source));

    if (params.length) url += '?' + params.join('&');
    window.location.href = url;
}
</script>
</body>
