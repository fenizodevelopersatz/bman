<?php $this->load->view('admin/Layout/common_style'); ?>
<style>
    .gfs-row input.form-control { max-width: 160px; }
    .gfs-mono { font-family: monospace; font-size: .78rem; }
</style>

<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true"
    data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true"
    data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
    data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">

<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
<div class="app-page flex-column flex-column-fluid" id="kt_app_page">

    <?php $this->load->view('admin/Layout/admin_topbar'); ?>

    <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
        <?php $this->load->view('admin/Layout/admin_sidebar'); ?>

        <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
        <div class="d-flex flex-column flex-column-fluid">

            <!-- Toolbar -->
            <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                <div class="app-container container-xxl d-flex flex-stack flex-wrap gap-3">
                    <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                        <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0"><?php echo $title; ?></h1>
                        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                            <li class="breadcrumb-item text-muted"><a href="<?php echo base_url(); ?>admin" class="text-muted text-hover-primary">Finance</a></li>
                            <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                            <li class="breadcrumb-item text-muted"><a href="<?php echo base_url('admin/finance/gas-fee-transactions'); ?>" class="text-muted text-hover-primary">Gas Fee Transactions</a></li>
                            <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                            <li class="breadcrumb-item text-muted"><?php echo $title; ?></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div id="kt_app_content" class="app-content flex-column-fluid mt-4">
            <div class="app-container container-xxl">

                <?php $this->load->view('notification'); ?>

                <!-- Total spent card -->
                <div class="row g-4 mb-6">
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-100" style="border-top:3px solid #10b981">
                            <div class="card-body py-4 px-5">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="ki-duotone ki-wallet fs-2" style="color:#10b981"><span class="path1"></span><span class="path2"></span></i>
                                    <span class="text-muted fw-semibold fs-8 text-uppercase">Total Gas Spent</span>
                                </div>
                                <div class="d-flex align-items-end gap-2">
                                    <span class="fs-2 fw-bold text-gray-900"><?php echo number_format((float) $total_spent, 8); ?></span>
                                    <span class="text-muted fs-8 mb-1">BNB, all-time (confirmed only)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Policy table -->
                <div class="card mb-6">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title">
                            <h3 class="fw-bold">Gas Policy</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <p class="text-muted fs-7">
                            Every gas-consuming send in the staking purchase flow reads its gas limit / price from here first
                            (falling back to Master &raquo; Token Settings only if a profile below is inactive). Changing a
                            value here changes what the next broadcast actually submits &mdash; it does not touch anything
                            already sent.
                        </p>
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle">
                                <thead>
                                    <tr class="fw-bold text-muted">
                                        <th>Profile</th>
                                        <th>Used by</th>
                                        <th>Gas Limit</th>
                                        <th>Gas Price (Gwei)</th>
                                        <th>Buffer &times;</th>
                                        <th>Active</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($policies as $p): ?>
                                    <tr class="gfs-row" data-id="<?php echo (int) $p['id']; ?>">
                                        <td>
                                            <span class="badge badge-light-primary"><?php echo htmlspecialchars($p['tx_type']); ?></span>
                                            <div class="text-muted fs-9 mt-1">
                                                <?php echo $p['tx_type'] === 'gas_funding'
                                                    ? 'Native BNB send (treasury &rarr; user gas funding)'
                                                    : 'BEP-20 token send (USDT / BMAN legs)'; ?>
                                            </div>
                                        </td>
                                        <td class="gfs-mono text-muted">
                                            <?php echo $p['tx_type'] === 'gas_funding' ? "'gas' leg" : "'usdt' / 'bonus' / 'bman' legs"; ?>
                                        </td>
                                        <td><input type="number" class="form-control form-control-sm" data-f="gas_limit" value="<?php echo (int) $p['gas_limit']; ?>" min="21000" step="1"></td>
                                        <td><input type="number" class="form-control form-control-sm" data-f="gas_price_gwei" value="<?php echo $p['gas_price_gwei'] !== null ? htmlspecialchars($p['gas_price_gwei']) : ''; ?>" placeholder="live RPC price" step="0.01" min="0"></td>
                                        <td><input type="number" class="form-control form-control-sm" data-f="buffer_multiplier" value="<?php echo htmlspecialchars($p['buffer_multiplier']); ?>" step="0.1" min="1"></td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" data-f="is_active" <?php echo (int) $p['is_active'] === 1 ? 'checked' : ''; ?>>
                                            </div>
                                        </td>
                                        <td><button type="button" class="btn btn-sm btn-light-primary gfs-save">Save</button></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Audit trail -->
                <div class="card">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title">
                            <h3 class="fw-bold">Recent Changes</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div id="gfs-audit-body" class="table-responsive">Loading&hellip;</div>
                    </div>
                </div>

            </div>
            </div>

        </div>
        <?php $this->load->view('admin/Layout/admin_footer'); ?>
        </div>
    </div>
</div>
</div>

<div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
    <i class="ki-duotone ki-arrow-up"><span class="path1"></span><span class="path2"></span></i>
</div>

<?php $this->load->view('admin/Layout/common_script'); ?>

<script>
(function () {
    const base = '<?php echo base_url(); ?>';

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g,
            c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    document.querySelectorAll('.gfs-save').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const row = btn.closest('tr');
            const id = row.dataset.id;
            const body = new URLSearchParams({
                id: id,
                gas_limit: row.querySelector('[data-f="gas_limit"]').value,
                gas_price_gwei: row.querySelector('[data-f="gas_price_gwei"]').value,
                buffer_multiplier: row.querySelector('[data-f="buffer_multiplier"]').value,
                is_active: row.querySelector('[data-f="is_active"]').checked ? '1' : '0',
            });
            btn.disabled = true;
            const original = btn.textContent;
            btn.textContent = 'Saving…';
            fetch(base + 'admin/finance/gas-fee-settings/save', {
                method: 'POST', body: body,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            }).then(r => r.json()).then(function (j) {
                btn.disabled = false;
                btn.textContent = original;
                if (j.status) { loadAudit(); btn.classList.add('btn-light-success'); setTimeout(() => btn.classList.remove('btn-light-success'), 1200); }
                else alert(j.message || 'Save failed.');
            }).catch(function () {
                btn.disabled = false;
                btn.textContent = original;
                alert('Could not reach the server.');
            });
        });
    });

    function loadAudit() {
        const el = document.getElementById('gfs-audit-body');
        el.innerHTML = 'Loading…';
        fetch(base + 'admin/finance/gas-fee-settings/audit', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json()).then(function (j) {
                const rows = (j.rows || []);
                if (!rows.length) { el.innerHTML = '<div class="text-muted">No changes recorded yet.</div>'; return; }
                const trs = rows.map(r =>
                    '<tr>' +
                    '<td>' + esc(r.field_name) + '</td>' +
                    '<td class="text-muted fs-8">' + esc(r.old_value ?? '—') + '</td>' +
                    '<td class="text-muted fs-8">' + esc(r.new_value ?? '—') + '</td>' +
                    '<td>' + esc(r.admin_name || ('#' + r.changed_by)) + '</td>' +
                    '<td class="text-muted fs-8">' + esc(r.created_at) + '</td>' +
                    '</tr>').join('');
                el.innerHTML = '<table class="table table-row-dashed fs-7"><thead><tr class="fw-bold text-muted">' +
                    '<th>Field</th><th>Old</th><th>New</th><th>By</th><th>When</th>' +
                    '</tr></thead><tbody>' + trs + '</tbody></table>';
            });
    }

    loadAudit();
})();
</script>
</body>
</html>
