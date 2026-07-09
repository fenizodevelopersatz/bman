<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h4 class="mb-0">
                        <i class="fas fa-crown"></i> Staking Packages
                    </h4>
                </div>

                <div class="card-body">
                    <div id="packages-grid" class="row">
                        <!-- Packages will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Purchase Modal -->
<div class="modal fade" id="purchaseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title">
                    <i class="fas fa-sync"></i> Staking Purchase
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div id="purchase-progress" class="mb-4">
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated"
                             id="progress-bar" role="progressbar" style="width: 25%">
                            Step 1 of 4
                        </div>
                    </div>
                </div>

                <!-- Step 1: Waiting for Gas Fee -->
                <div id="step-gas-fee" class="step-content">
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-hourglass-start"></i> Waiting for Gas Fee</h6>
                        <p class="mb-0">Admin needs to send <strong id="gas-amount"></strong> BNB to your wallet for transaction fees.</p>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Your Wallet Address:</label>
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" id="user-address" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('user-address')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gas Fee:</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="gas-fee" readonly>
                                <span class="input-group-text">BNB</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button class="btn btn-primary w-100" onclick="checkOrderStatus()">
                            <i class="fas fa-sync"></i> Check Status
                        </button>
                    </div>
                </div>

                <!-- Step 2: Gas Fee Received, Ready for USDT -->
                <div id="step-usdt-payment" class="step-content d-none">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-check-circle"></i> Gas Fee Received!</h6>
                        <p class="mb-0">Your wallet has been funded. Now send USDT to complete the swap.</p>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">USDT Amount:</label>
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" id="usdt-amount" readonly>
                                <span class="input-group-text">USDT</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Admin Address:</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="admin-address" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('admin-address')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <p class="small text-muted">
                            <i class="fas fa-info-circle"></i>
                            Send exactly <span id="usdt-amount-display">0</span> USDT from your wallet to the admin address above.
                        </p>
                    </div>

                    <div class="mt-3">
                        <button class="btn btn-success w-100" onclick="checkOrderStatus()">
                            <i class="fas fa-sync"></i> I've Sent USDT - Check Status
                        </button>
                    </div>
                </div>

                <!-- Step 3: Waiting for BMAN Transfer -->
                <div id="step-bman-transfer" class="step-content d-none">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-hourglass-half"></i> Processing Swap</h6>
                        <p class="mb-0">Your USDT has been received. Admin is sending BMAN to your wallet...</p>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">BMAN to Receive:</label>
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" id="bman-amount" readonly>
                                <span class="input-group-text">BMAN</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bonus BMAN:</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="bonus-amount" readonly>
                                <span class="input-group-text">BMAN</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="spinner-border text-primary mx-auto d-block" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-center text-muted mt-2">Waiting for blockchain confirmation...</p>
                    </div>

                    <div class="mt-3">
                        <button class="btn btn-primary w-100" onclick="checkOrderStatus()">
                            <i class="fas fa-sync"></i> Refresh Status
                        </button>
                    </div>
                </div>

                <!-- Step 4: Swap Completed -->
                <div id="step-completed" class="step-content d-none">
                    <div class="alert alert-success">
                        <h6><i class="fas fa-check-circle"></i> Swap Completed!</h6>
                        <p class="mb-0">Your staking is now active. Check your portfolio to see it!</p>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">BMAN in Staking:</label>
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" id="final-bman" readonly>
                                <span class="input-group-text">BMAN</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bonus Received:</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="final-bonus" readonly>
                                <span class="input-group-text">BMAN</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 p-3 bg-light rounded">
                        <p class="mb-1"><strong>Transaction Hashes:</strong></p>
                        <div class="small">
                            <p class="mb-2">
                                <span class="text-muted">Gas Fee TX:</span><br>
                                <code id="tx-gas" class="small"></code>
                            </p>
                            <p class="mb-2">
                                <span class="text-muted">USDT Payment TX:</span><br>
                                <code id="tx-usdt" class="small"></code>
                            </p>
                            <p class="mb-0">
                                <span class="text-muted">BMAN Transfer TX:</span><br>
                                <code id="tx-bman" class="small"></code>
                            </p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button class="btn btn-success w-100" data-bs-dismiss="modal">
                            <i class="fas fa-check"></i> View My Stakings
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentOrderId = null;
let statusCheckInterval = null;

function showPurchaseModal(packageId) {
    // Get CSRF token from form (already present in view data)
    const csrfToken = document.querySelector('[name="csrf_token_name"]')?.value || '';

    fetch('<?php echo base_url("user/lending/swap_purchase"); ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            package_id: packageId,
            [csrfTokenName]: csrfToken,
        })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.status) {
            alert('Error: ' + data.message);
            return;
        }

        currentOrderId = data.data.order_id;
        const d = data.data;

        // Populate form
        document.getElementById('user-address').value = d.user_address;
        document.getElementById('gas-amount').textContent = d.gas_fee_bnb + ' BNB';
        document.getElementById('gas-fee').value = d.gas_fee_bnb;
        document.getElementById('usdt-amount').value = d.usdt_amount;
        document.getElementById('usdt-amount-display').textContent = d.usdt_amount;
        document.getElementById('admin-address').value = d.admin_address;
        document.getElementById('bman-amount').value = d.bman_amount;
        document.getElementById('bonus-amount').value = d.bonus_bman;
        document.getElementById('final-bman').value = d.bman_amount;
        document.getElementById('final-bonus').value = d.bonus_bman;

        // Show modal and start checking status
        new bootstrap.Modal(document.getElementById('purchaseModal')).show();
        updatePurchaseUI('pending_gas_fee');
        startStatusCheck();
    })
    .catch(err => {
        console.error(err);
        alert('Error initiating purchase');
    });
}

function checkOrderStatus() {
    if (!currentOrderId) return;

    const csrfToken = document.querySelector('[name="csrf_token_name"]')?.value || '';

    fetch('<?php echo base_url("user/lending/swap_status"); ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            order_id: currentOrderId,
            [csrfTokenName]: csrfToken,
        })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.status) {
            alert('Error: ' + data.message);
            return;
        }

        const status = data.current_status;
        updatePurchaseUI(status, data);
    })
    .catch(err => {
        console.error(err);
        alert('Error checking status');
    });
}

function updatePurchaseUI(status, data = null) {
    // Hide all steps
    document.querySelectorAll('.step-content').forEach(el => el.classList.add('d-none'));

    // Progress bar
    const progressMap = {
        'pending_gas_fee': { width: 25, text: 'Step 1 of 4: Waiting for Gas' },
        'pending_usdt': { width: 50, text: 'Step 2 of 4: Send USDT' },
        'pending_bman': { width: 75, text: 'Step 3 of 4: Processing...' },
        'swap_completed': { width: 100, text: 'Step 4 of 4: Complete!' },
    };

    const progress = progressMap[status] || { width: 0, text: 'Unknown' };
    document.getElementById('progress-bar').style.width = progress.width + '%';
    document.getElementById('progress-bar').textContent = progress.text;

    // Show appropriate step
    const stepMap = {
        'pending_gas_fee': 'step-gas-fee',
        'pending_usdt': 'step-usdt-payment',
        'pending_bman': 'step-bman-transfer',
        'swap_completed': 'step-completed',
    };

    const stepId = stepMap[status];
    if (stepId) {
        document.getElementById(stepId).classList.remove('d-none');
    }

    // Update transaction hashes if available
    if (data && data.is_completed) {
        if (data.gas_tx_hash) document.getElementById('tx-gas').textContent = data.gas_tx_hash;
        if (data.usdt_tx_hash) document.getElementById('tx-usdt').textContent = data.usdt_tx_hash;
        if (data.bman_tx_hash) document.getElementById('tx-bman').textContent = data.bman_tx_hash;
    }
}

function startStatusCheck() {
    // Check every 5 seconds
    statusCheckInterval = setInterval(checkOrderStatus, 5000);
}

function stopStatusCheck() {
    if (statusCheckInterval) {
        clearInterval(statusCheckInterval);
        statusCheckInterval = null;
    }
}

function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    const text = element.value;
    navigator.clipboard.writeText(text).then(() => {
        alert('Copied to clipboard!');
    });
}

// Stop checking when modal closes
document.getElementById('purchaseModal')?.addEventListener('hidden.bs.modal', stopStatusCheck);

// Load packages on page load
window.addEventListener('DOMContentLoaded', function() {
    // TODO: Load packages from server and display them
    console.log('Swap purchase page loaded');
});
</script>

<style>
.step-content {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.progress-bar {
    font-weight: bold;
    font-size: 0.9rem;
}

.input-group input[readonly] {
    background-color: #f8f9fa;
    font-weight: 500;
}

code {
    background-color: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
    word-break: break-all;
}

.bg-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}
</style>
