<?php
/**
 * Admin View: Wallet & Staking Reset
 *
 * Provides safe, UI-based interface for:
 * - Resetting user staking records and wallet balances
 * - Resetting recent staking activity
 * - Marking orders as completed
 * - Checking user wallet details
 */
?>

<!-- Hero Section -->
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-danger font-weight-bold text-uppercase mb-1">⚠️ Wallet Reset Tools</div>
                    <div class="h5 mb-0">Carefully manage staking records and wallet balances</div>
                    <small class="text-muted d-block mt-2">
                        All operations are logged. Make database backups before major resets.
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-primary font-weight-bold text-uppercase mb-1">Total Orders</div>
                    <div class="h3 mb-0"><?php echo number_format($total_orders); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-warning font-weight-bold text-uppercase mb-1">Pending</div>
                    <div class="h3 mb-0"><?php echo number_format($pending_orders); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-info font-weight-bold text-uppercase mb-1">Last 24h</div>
                    <div class="h3 mb-0"><?php echo number_format($last_24h); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-success font-weight-bold text-uppercase mb-1">Zero Balance Users</div>
                    <div class="h3 mb-0"><?php echo number_format($zero_balance_users); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reset Options -->
    <div class="row">

        <!-- Reset Single User -->
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h6 class="m-0"><i class="fas fa-user-slash"></i> Reset Single User</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Remove all staking records and reset wallet balances for a specific user.
                    </p>

                    <div class="form-group">
                        <label for="reset_user_id">User ID</label>
                        <input type="text" id="reset_user_id" class="form-control" placeholder="e.g., 123">
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" id="reset_user_confirm" class="form-check-input">
                        <label class="form-check-label text-danger" for="reset_user_confirm">
                            <strong>I understand this will DELETE all staking records</strong>
                        </label>
                    </div>

                    <button class="btn btn-danger btn-sm" id="btn_reset_user" disabled>
                        <i class="fas fa-trash"></i> Reset User
                    </button>
                    <div id="reset_user_result" class="mt-3"></div>
                </div>
            </div>
        </div>

        <!-- Reset Recent Staking -->
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-warning text-white">
                    <h6 class="m-0"><i class="fas fa-history"></i> Reset Recent Staking</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Remove staking orders from the last N days.
                    </p>

                    <div class="form-group">
                        <label for="reset_recent_days">Days to Clear</label>
                        <input type="number" id="reset_recent_days" class="form-control" value="7" min="1" max="365">
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" id="reset_recent_confirm" class="form-check-input">
                        <label class="form-check-label text-danger" for="reset_recent_confirm">
                            <strong>I understand this will DELETE orders from the last <span id="reset_recent_days_display">7</span> days</strong>
                        </label>
                    </div>

                    <button class="btn btn-warning btn-sm" id="btn_reset_recent" disabled>
                        <i class="fas fa-trash"></i> Delete Recent
                    </button>
                    <div id="reset_recent_result" class="mt-3"></div>
                </div>
            </div>
        </div>

    </div>

    <!-- Mark as Completed -->
    <div class="row mt-4 mb-4">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h6 class="m-0"><i class="fas fa-check-circle"></i> Mark Order as Completed</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Mark a staking order as completed without deleting it.
                    </p>

                    <div class="form-group">
                        <label for="mark_order_id">Order ID</label>
                        <input type="number" id="mark_order_id" class="form-control" placeholder="Enter order ID">
                    </div>

                    <button class="btn btn-info btn-sm" id="btn_mark_completed">
                        <i class="fas fa-check"></i> Mark Completed
                    </button>
                    <div id="mark_completed_result" class="mt-3"></div>
                </div>
            </div>
        </div>

        <!-- User Wallet Info -->
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-secondary text-white">
                    <h6 class="m-0"><i class="fas fa-wallet"></i> Check User Wallets</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        View wallet balances for a specific user.
                    </p>

                    <div class="form-group">
                        <label for="check_user_id">User ID</label>
                        <input type="number" id="check_user_id" class="form-control" placeholder="Enter user ID">
                    </div>

                    <button class="btn btn-secondary btn-sm" id="btn_check_wallet">
                        <i class="fas fa-search"></i> Check Wallet
                    </button>
                    <div id="check_wallet_result" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Table -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0">
                        <i class="fas fa-table"></i> Recent Staking Activity
                        <button class="btn btn-sm btn-primary float-right" id="btn_refresh_activity">
                            <i class="fas fa-sync"></i> Refresh
                        </button>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm" id="activity_table">
                            <thead class="bg-light">
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Date</th>
                                    <th>USDT</th>
                                    <th>BMAN</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.card-header { border-bottom: 2px solid #ddd; }
.badge-status { padding: 0.35rem 0.65rem; }
.result-success { color: #28a745; }
.result-error { color: #dc3545; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const BASE_URL = '<?php echo base_url('admin/staking/walletreset/'); ?>';

    // Toggle buttons
    document.getElementById('reset_user_confirm').addEventListener('change', function() {
        document.getElementById('btn_reset_user').disabled = !this.checked;
    });

    document.getElementById('reset_recent_days').addEventListener('change', function() {
        document.getElementById('reset_recent_days_display').textContent = this.value;
        document.getElementById('btn_reset_recent').disabled = !document.getElementById('reset_recent_confirm').checked;
    });

    document.getElementById('reset_recent_confirm').addEventListener('change', function() {
        document.getElementById('btn_reset_recent').disabled = !this.checked;
    });

    // Reset user
    document.getElementById('btn_reset_user').addEventListener('click', async function() {
        const user_id = document.getElementById('reset_user_id').value.trim();
        if (!user_id) {
            showResult('reset_user_result', 'error', 'Please enter a user ID');
            return;
        }

        if (!confirm('Are you absolutely sure? This will DELETE all staking records and reset wallet balances to 0.')) {
            return;
        }

        const formData = new FormData();
        formData.append('user_id', user_id);
        formData.append('confirm', 'yes');

        try {
            const response = await fetch(BASE_URL + 'reset_user', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            showResult('reset_user_result', result.status, result.message);
            if (result.status === 'success') {
                document.getElementById('reset_user_id').value = '';
                document.getElementById('reset_user_confirm').checked = false;
                document.getElementById('btn_reset_user').disabled = true;
                loadActivity();
            }
        } catch (e) {
            showResult('reset_user_result', 'error', 'Network error: ' + e.message);
        }
    });

    // Reset recent
    document.getElementById('btn_reset_recent').addEventListener('click', async function() {
        const days = document.getElementById('reset_recent_days').value;

        if (!confirm('Delete all staking orders from the last ' + days + ' days?')) {
            return;
        }

        const formData = new FormData();
        formData.append('days', days);
        formData.append('confirm', 'yes');

        try {
            const response = await fetch(BASE_URL + 'reset_recent', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            showResult('reset_recent_result', result.status, result.message);
            if (result.status === 'success') {
                loadActivity();
            }
        } catch (e) {
            showResult('reset_recent_result', 'error', 'Network error: ' + e.message);
        }
    });

    // Mark completed
    document.getElementById('btn_mark_completed').addEventListener('click', async function() {
        const order_id = document.getElementById('mark_order_id').value.trim();
        if (!order_id) {
            showResult('mark_completed_result', 'error', 'Please enter an order ID');
            return;
        }

        const formData = new FormData();
        formData.append('order_id', order_id);

        try {
            const response = await fetch(BASE_URL + 'mark_completed', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            showResult('mark_completed_result', result.status, result.message);
            if (result.status === 'success') {
                document.getElementById('mark_order_id').value = '';
                loadActivity();
            }
        } catch (e) {
            showResult('mark_completed_result', 'error', 'Network error: ' + e.message);
        }
    });

    // Check wallet
    document.getElementById('btn_check_wallet').addEventListener('click', async function() {
        const user_id = document.getElementById('check_user_id').value.trim();
        if (!user_id) {
            showResult('check_wallet_result', 'error', 'Please enter a user ID');
            return;
        }

        try {
            const response = await fetch(BASE_URL + 'get_user_wallets?user_id=' + user_id);
            const result = await response.json();

            if (result.status === 'error') {
                showResult('check_wallet_result', 'error', result.message);
                return;
            }

            const user = result.user;
            const wallet = result.wallet;
            const ceiling = result.ceiling;

            let html = `
                <div class="alert alert-info">
                    <h6>${user.username} (ID: ${user.id})</h6>
                    <p class="mb-1"><small>${user.email}</small></p>
                    <div class="mt-3">
                        <p><strong>Balances:</strong></p>
                        <ul class="mb-2">
                            <li>Exchange: ${formatNumber(wallet.exchange_balance || 0)}</li>
                            <li>Earning: ${formatNumber(wallet.earning_balance || 0)}</li>
                            <li>Staking: ${formatNumber(wallet.staking_balance || 0)}</li>
                            <li>Bonus: ${formatNumber(wallet.bonus_balance || 0)}</li>
                            <li>USD: ${formatNumber(wallet.usd_balance || 0)}</li>
                        </ul>
                        <p><strong>Staking Orders:</strong> ${result.staking_orders}</p>
                        <p><strong>Ceiling Hold:</strong> ${formatNumber(ceiling.held_balance || 0)}</p>
                    </div>
                </div>
            `;
            document.getElementById('check_wallet_result').innerHTML = html;
        } catch (e) {
            showResult('check_wallet_result', 'error', 'Network error: ' + e.message);
        }
    });

    // Load activity
    async function loadActivity() {
        try {
            const response = await fetch(BASE_URL + 'get_activity?limit=20');
            const result = await response.json();

            if (result.status !== 'success') return;

            const tbody = document.querySelector('#activity_table tbody');
            tbody.innerHTML = '';

            result.data.forEach(order => {
                const row = document.createElement('tr');
                const statusBadge = getStatusBadge(order.status);
                row.innerHTML = `
                    <td><strong>${order.id}</strong></td>
                    <td>${order.username}</td>
                    <td><small>${new Date(order.created_at).toLocaleDateString()}</small></td>
                    <td>${formatNumber(order.usdt_amount)}</td>
                    <td>${formatNumber(order.bman_amount)}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <button class="btn btn-xs btn-info" onclick="copyToId('mark_order_id', ${order.id})" title="Copy order ID">
                            ${order.id}
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        } catch (e) {
            console.error('Error loading activity:', e);
        }
    }

    // Refresh button
    document.getElementById('btn_refresh_activity').addEventListener('click', loadActivity);

    // Helper functions
    function showResult(elementId, status, message) {
        const el = document.getElementById(elementId);
        const className = status === 'success' ? 'alert-success' : 'alert-danger';
        el.innerHTML = `<div class="alert ${className} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>`;
    }

    function getStatusBadge(status) {
        const statusMap = {
            'created': '<span class="badge badge-secondary">Created</span>',
            'usdt_sent': '<span class="badge badge-info">USDT Sent</span>',
            'bman_sent': '<span class="badge badge-primary">BMAN Sent</span>',
            'completed': '<span class="badge badge-success">Completed</span>',
            'failed_gas': '<span class="badge badge-danger">Failed: Gas</span>',
            'failed_usdt': '<span class="badge badge-danger">Failed: USDT</span>',
            'failed_bman': '<span class="badge badge-danger">Failed: BMAN</span>',
        };
        return statusMap[status] || `<span class="badge badge-light">${status}</span>`;
    }

    function formatNumber(num) {
        return parseFloat(num).toFixed(4);
    }

    function copyToId(elementId, value) {
        document.getElementById(elementId).value = value;
    }

    // Load on startup
    loadActivity();
});
</script>
