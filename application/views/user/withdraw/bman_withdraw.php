<?php $this->load->view('user/layout/user_header'); ?>
<div class="container-fluid py-4">
    <div class="card mb-4">
        <div class="card-body">
            <h4 class="mb-3">Manual BMAN Withdrawal</h4>
            <p class="text-muted small mb-4">Withdrawals are validated against <strong>matured ledger balance</strong> only. Locked funds unlock per wallet maturity rules below.</p>

            <?php
            $wallet_labels = ['exchange' => 'Exchange', 'earning' => 'Earning', 'staking' => 'Staking', 'bonus' => 'Bonus'];
            $rules = $maturity_rules ?? [];
            ?>
            <div class="row g-3 mb-4">
                <?php foreach ($wallet_labels as $key => $label): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="border rounded p-3 h-100">
                        <div class="fw-semibold mb-2"><?= $label; ?>
                            <?php if (!empty($rules[$key])): ?>
                                <span class="badge bg-secondary"><?= (int)$rules[$key]; ?>d lock</span>
                            <?php endif; ?>
                        </div>
                        <div class="small">Total: <strong><?= number_format($breakdowns[$key] ?? 0, 4); ?></strong></div>
                        <div class="small text-success">Matured: <strong><?= number_format($breakdowns[$key . '_matured'] ?? 0, 4); ?></strong></div>
                        <div class="small text-warning">Locked: <strong><?= number_format($breakdowns[$key . '_locked'] ?? 0, 4); ?></strong></div>
                        <div class="small text-primary">Withdrawable: <strong><?= number_format($breakdowns[$key . '_withdrawable'] ?? 0, 4); ?></strong></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <form id="bmanWithdrawForm">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Wallet Source</label>
                        <select class="form-select" name="source_wallet" id="source_wallet">
                            <?php foreach ($wallet_labels as $key => $label): ?>
                            <option value="<?= $key; ?>" data-withdrawable="<?= $breakdowns[$key . '_withdrawable'] ?? 0; ?>">
                                <?= $label; ?> (<?= number_format($breakdowns[$key . '_withdrawable'] ?? 0, 4); ?> avail.)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Amount</label>
                        <input class="form-control" name="amount" type="number" step="0.0001" id="withdraw_amount">
                        <small class="text-muted" id="avail_hint"></small>
                    </div>
                    <div class="col-md-4"><label class="form-label">Withdraw Address</label><input class="form-control" name="withdraw_address"></div>
                    <div class="col-12"><label class="form-label">Remark</label><textarea class="form-control" name="remark" rows="3"></textarea></div>
                </div>
                <button class="btn btn-primary mt-3" type="submit">Submit Withdrawal</button>
            </form>
        </div>
    </div>

    <?php if (!empty($upcoming)): ?>
    <div class="card mb-4">
        <div class="card-body">
            <h5>Upcoming Unlocks</h5>
            <p class="text-muted small">Funds that will become withdrawable on the dates below.</p>
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead><tr><th>Wallet</th><th>Amount</th><th>Unlock Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($upcoming as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars(ucfirst($row['wallet_type'])); ?></td>
                            <td><?= number_format((float)$row['amount'], 4); ?></td>
                            <td><?= htmlspecialchars($row['maturity_date']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
(function () {
    const sel = document.getElementById('source_wallet');
    const hint = document.getElementById('avail_hint');
    function refreshHint() {
        const opt = sel.options[sel.selectedIndex];
        const avail = parseFloat(opt.dataset.withdrawable || 0);
        hint.textContent = 'Withdrawable (matured minus holds): ' + avail.toFixed(4);
        document.getElementById('withdraw_amount').max = avail;
    }
    sel.addEventListener('change', refreshHint);
    refreshHint();
})();
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
