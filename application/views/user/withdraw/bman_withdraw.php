<?php $this->load->view('user/layout/user_header'); ?>
<div class="container-fluid py-4">
    <div class="card mb-4">
        <div class="card-body">
            <h4 class="mb-3">Manual BMAN Withdrawal</h4>
            <p class="text-muted small mb-4">Withdrawals convert only <strong>matured Exchange Wallet BMAN</strong> to USDT. Other wallets remain visible but cannot fund withdrawals.</p>
            <?php if (!empty($open_request)): ?>
                <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                    <div>
                        <strong>Withdraw request locked.</strong>
                        <div class="small mb-0">
                            You already have request <strong><?= htmlspecialchars($open_request['request_no'] ?? ''); ?></strong>
                            in <strong><?= htmlspecialchars($open_request['status'] ?? 'pending'); ?></strong> status.
                        </div>
                    </div>
                    <span class="badge bg-dark">Submit again after approval or rejection</span>
                </div>
            <?php endif; ?>

            <?php
            $wallet_labels = ['exchange' => 'Exchange', 'earning' => 'Earning', 'staking' => 'Staking', 'bonus' => 'Bonus'];
            $withdraw_wallet_labels = ['exchange' => 'Exchange'];
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
                            <?php foreach ($withdraw_wallet_labels as $key => $label): ?>
                            <option value="<?= $key; ?>" data-withdrawable="<?= $breakdowns[$key . '_withdrawable'] ?? 0; ?>">
                                <?= $label; ?> (<?= number_format($breakdowns[$key . '_withdrawable'] ?? 0, 4); ?> avail.)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Amount</label>
                        <input class="form-control" name="withdraw_bman" type="number" step="0.0001" id="withdraw_amount">
                        <small class="text-muted" id="avail_hint"></small>
                    </div>
                    <div class="col-md-4"><label class="form-label">Withdraw Address</label><input class="form-control" name="wallet_address"></div>
                    <div class="col-12"><label class="form-label">Remark</label><textarea class="form-control" name="remark" rows="3"></textarea></div>
                </div>
                <button class="btn btn-primary mt-3" type="submit" <?= !empty($open_request) ? 'disabled' : ''; ?>>
                    <?= !empty($open_request) ? 'Request Locked' : 'Submit Withdrawal'; ?>
                </button>
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

<!-- OTP Verification Modal -->
<div id="payoutOtpModal" class="modal fade" tabindex="-1" role="dialog" style="display: none;">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Verify Payout Request - OTP</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="closePayoutOtpModal()"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">We've sent a 6-digit OTP to <strong id="otpEmail"></strong>. Enter it below to verify your withdrawal request.</p>

                <div class="mb-3">
                    <label class="form-label">Enter OTP Code</label>
                    <div class="d-flex gap-2 justify-content-center mb-2">
                        <input type="text" class="form-control otp-code" maxlength="1" placeholder="0" style="width: 45px; text-align: center; font-size: 20px; font-weight: bold;">
                        <input type="text" class="form-control otp-code" maxlength="1" placeholder="0" style="width: 45px; text-align: center; font-size: 20px; font-weight: bold;">
                        <input type="text" class="form-control otp-code" maxlength="1" placeholder="0" style="width: 45px; text-align: center; font-size: 20px; font-weight: bold;">
                        <input type="text" class="form-control otp-code" maxlength="1" placeholder="0" style="width: 45px; text-align: center; font-size: 20px; font-weight: bold;">
                        <input type="text" class="form-control otp-code" maxlength="1" placeholder="0" style="width: 45px; text-align: center; font-size: 20px; font-weight: bold;">
                        <input type="text" class="form-control otp-code" maxlength="1" placeholder="0" style="width: 45px; text-align: center; font-size: 20px; font-weight: bold;">
                    </div>
                    <div id="otpMessage" class="text-center small" style="min-height: 20px; color: #999;"></div>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary flex-grow-1" id="otpSubmitBtn" onclick="verifyPayoutOtp()">Verify OTP</button>
                    <button type="button" class="btn btn-secondary flex-grow-1" onclick="closePayoutOtpModal()">Cancel</button>
                </div>
                <button type="button" class="btn btn-outline-secondary w-100 mt-2" onclick="resendPayoutOtp()">📧 Resend OTP</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const sel = document.getElementById('source_wallet');
    const hint = document.getElementById('avail_hint');
    const locked = <?= json_encode(!empty($open_request)); ?>;
    function refreshHint() {
        const opt = sel.options[sel.selectedIndex];
        const avail = parseFloat(opt.dataset.withdrawable || 0);
        hint.textContent = locked
            ? 'This withdrawal flow is locked until your current request is resolved.'
            : 'Withdrawable (matured minus holds): ' + avail.toFixed(4);
        document.getElementById('withdraw_amount').max = avail;
        if (locked) {
            document.getElementById('withdraw_amount').disabled = true;
            document.getElementById('source_wallet').disabled = true;
            const addr = document.querySelector('input[name="withdraw_address"]');
            const remark = document.querySelector('textarea[name="remark"]');
            if (addr) addr.disabled = true;
            if (remark) remark.disabled = true;
        }
    }
    sel.addEventListener('change', refreshHint);
    refreshHint();
})();
document.getElementById('bmanWithdrawForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    if (<?= json_encode(!empty($open_request)); ?>) {
        alert('You already have a withdrawal request in progress.');
        return;
    }
    const formData = new FormData(this);
    const res = await fetch('<?= base_url('user/bman-withdraw/request'); ?>', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }});
    const data = await res.json();

    // Check if OTP verification is required
    if (data.require_otp) {
        document.getElementById('otpEmail').textContent = data.email || 'your email';
        showPayoutOtpModal();
        return;
    }

    if (data.status) {
        alert(data.message || 'Withdrawal request submitted successfully!');
        window.location.reload();
    } else {
        alert(data.message || 'Error submitting withdrawal request');
    }
});

function showPayoutOtpModal() {
    const modal = document.getElementById('payoutOtpModal');
    modal.style.display = 'block';
    // Auto-focus first OTP input
    setTimeout(() => {
        const firstInput = document.querySelector('#payoutOtpModal .otp-code');
        if (firstInput) firstInput.focus();
    }, 100);

    // Setup OTP input handlers
    setupOtpInputs();
}

function closePayoutOtpModal() {
    document.getElementById('payoutOtpModal').style.display = 'none';
    // Clear OTP inputs
    document.querySelectorAll('#payoutOtpModal .otp-code').forEach(inp => inp.value = '');
}

function setupOtpInputs() {
    const inputs = document.querySelectorAll('#payoutOtpModal .otp-code');

    inputs.forEach((input, index) => {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 1);
            const nextInput = inputs[index + 1];
            if (this.value.length === 1 && nextInput) {
                nextInput.focus();
            } else if (!nextInput && this.value) {
                verifyPayoutOtp();
            }
        });

        input.addEventListener('keydown', function(event) {
            if (event.key === "Backspace" && this.value.length === 0) {
                const prevInput = inputs[index - 1];
                if (prevInput) prevInput.focus();
            }
            if (event.key === "Enter") {
                verifyPayoutOtp();
            }
        });

        input.addEventListener('paste', function(event) {
            event.preventDefault();
            const pasteData = (event.clipboardData || window.clipboardData).getData("text");
            const digits = pasteData.replace(/\D/g, '').slice(0, inputs.length);
            if (digits.length) {
                digits.split('').forEach((digit, idx) => {
                    if (inputs[idx]) inputs[idx].value = digit;
                });
                if (inputs[digits.length]) {
                    inputs[digits.length].focus();
                } else {
                    inputs[inputs.length - 1].focus();
                }
            }
        });
    });
}

async function verifyPayoutOtp() {
    const inputs = document.querySelectorAll('#payoutOtpModal .otp-code');
    const otp = Array.from(inputs).map(inp => inp.value).join('');
    const messageBox = document.getElementById('otpMessage');
    const btn = document.getElementById('otpSubmitBtn');

    if (otp.length !== 6) {
        messageBox.textContent = 'Please enter all 6 digits';
        messageBox.style.color = '#d9534f';
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Verifying...';
    messageBox.textContent = '';

    try {
        const res = await fetch('<?= base_url('user/payouts/verify-otp'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'otp=' + encodeURIComponent(otp)
        });

        const data = await res.json();
        if (data.success) {
            messageBox.textContent = '✓ OTP verified! Processing...';
            messageBox.style.color = '#0f9d58';
            setTimeout(() => {
                closePayoutOtpModal();
                alert('Withdrawal request submitted successfully!');
                window.location.reload();
            }, 1500);
        } else {
            messageBox.textContent = '✗ ' + (data.message || 'Invalid OTP');
            messageBox.style.color = '#d9534f';
            inputs.forEach(inp => inp.value = '');
            inputs[0].focus();
        }
    } catch (err) {
        messageBox.textContent = '⚠️ Error: ' + (err.message || 'Server error');
        messageBox.style.color = '#ff9800';
    } finally {
        btn.disabled = false;
        btn.textContent = 'Verify OTP';
    }
}

async function resendPayoutOtp() {
    try {
        const res = await fetch('<?= base_url('user/login/resend-otp'); ?>', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        const messageBox = document.getElementById('otpMessage');

        if (data.status) {
            messageBox.textContent = '✓ New OTP sent to your email!';
            messageBox.style.color = '#0f9d58';
            document.querySelectorAll('#payoutOtpModal .otp-code').forEach(inp => inp.value = '');
            setTimeout(() => {
                messageBox.textContent = '';
                document.querySelector('#payoutOtpModal .otp-code').focus();
            }, 2000);
        } else {
            messageBox.textContent = '✗ ' + (data.message || 'Failed to resend');
            messageBox.style.color = '#d9534f';
        }
    } catch (err) {
        document.getElementById('otpMessage').textContent = '⚠️ Error: ' + err.message;
        document.getElementById('otpMessage').style.color = '#ff9800';
    }
}
</script>
<?php $this->load->view('user/layout/user_footer'); ?>
