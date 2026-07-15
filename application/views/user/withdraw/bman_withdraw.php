<?php $this->load->view('user/layout/user_header'); ?>
<div class="container-fluid py-4">
    <div class="card mb-4">
        <div class="card-body">
            <h4 class="mb-3">Manual BMAN Withdrawal</h4>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><div class="border rounded p-3">Exchange: <strong><?= number_format($wallets['exchange'] ?? 0, 4); ?></strong></div></div>
                <div class="col-md-3"><div class="border rounded p-3">Earning: <strong><?= number_format($wallets['earning'] ?? 0, 4); ?></strong></div></div>
                <div class="col-md-3"><div class="border rounded p-3">Staking: <strong><?= number_format($wallets['staking'] ?? 0, 4); ?></strong></div></div>
                <div class="col-md-3"><div class="border rounded p-3">Bonus: <strong><?= number_format($wallets['bonus'] ?? 0, 4); ?></strong></div></div>
            </div>
            <form id="bmanWithdrawForm">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Wallet Source</label>
                        <select class="form-select" name="source_wallet">
                            <option value="exchange">Exchange Wallet</option>
                            <option value="earning">Earning Wallet</option>
                            <option value="staking">Staking Wallet</option>
                            <option value="bonus">Bonus Wallet</option>
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label">Amount</label><input class="form-control" name="amount" type="number" step="0.0001"></div>
                    <div class="col-md-4"><label class="form-label">Withdraw Address</label><input class="form-control" name="withdraw_address"></div>
                    <div class="col-12"><label class="form-label">Remark</label><textarea class="form-control" name="remark" rows="3"></textarea></div>
                </div>
                <button class="btn btn-primary mt-3" type="submit">Submit Withdrawal</button>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <h5>Withdrawal History</h5>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>Request No</th><th>Source</th><th>Amount</th><th>Fee</th><th>Net</th><th>Status</th><th>Tx Hash</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach (($history ?? []) as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['request_no']); ?></td>
                            <td><?= htmlspecialchars($row['source_wallet']); ?></td>
                            <td><?= number_format((float)$row['request_amount'], 4); ?></td>
                            <td><?= number_format((float)$row['fee_amount'], 4); ?></td>
                            <td><?= number_format((float)$row['net_amount'], 4); ?></td>
                            <td><?= htmlspecialchars($row['status']); ?></td>
                            <td><?= htmlspecialchars($row['tx_hash'] ?? '-'); ?></td>
                            <td><?= htmlspecialchars($row['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('bmanWithdrawForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    const res = await fetch('<?= base_url('user/bman-withdraw/request'); ?>', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }});
    const data = await res.json();
    alert(data.message || 'Done');
    if (data.status) window.location.reload();
});
</script>
<?php $this->load->view('user/layout/user_footer'); ?>
