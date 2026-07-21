<?php $this->load->view('admin/Layout/common_style'); ?>

<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />

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

                        <!--begin::Toolbar-->
                        <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                                    <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                                        <?php echo $title; ?>
                                    </h1>
                                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                        <li class="breadcrumb-item text-muted">
                                            <a href="<?php echo base_url(); ?>" class="text-muted text-hover-primary">Settings</a>
                                        </li>
                                        <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                        <li class="breadcrumb-item text-muted"><?php echo $title; ?></li>
                                    </ul>
                                </div>
                                <div class="d-flex align-items-center gap-2 my-2">
                                    <button type="button" class="btn btn-danger btn-sm" id="aal-clear-btn">Clear Log</button>
                                </div>
                            </div>
                        </div>
                        <!--end::Toolbar-->

                        <div id="kt_app_content" class="app-content flex-column-fluid mt-10">
                            <div id="kt_app_content_container" class="app-container container-xxl">

                                <?php $this->load->view('notification'); ?>

                                <div class="card">
                                    <div class="card-header border-transparent pt-5">
                                        <h3 class="card-title fw-bold">All Settings Changes</h3>
                                        <div class="card-toolbar gap-2">
                                            <select class="form-select form-select-sm w-250px" id="aal-filter">
                                                <option value="">All modules</option>
                                                <option value="staking_plans">Staking Plans</option>
                                                <option value="roi_structure">ROI Structure</option>
                                                <option value="bonus_settings">Bonus &amp; Matching Settings</option>
                                                <option value="withdraw_settings">Withdraw Settings</option>
                                                <option value="token_withdraw_settings">Token Withdraw Settings</option>
                                                <option value="coin_distribution">Coin Distribution</option>
                                                <option value="token_settings">Token Settings / Exchange Rate</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="card-body pt-2">
                                        <div id="aal-body" class="table-responsive">Loading…</div>
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
    <script src="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.js"></script>

    <script>
    (function () {
        const base = '<?php echo base_url(); ?>';
        let allRows = [];

        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g,
                c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        function toast(msg, ok) {
            if (window.Swal) {
                Swal.fire({ text: msg, icon: ok ? 'success' : 'error',
                    buttonsStyling: false, confirmButtonText: 'Ok',
                    customClass: { confirmButton: 'btn btn-primary' } });
            } else { alert(msg); }
        }

        function render(rows) {
            const body = document.getElementById('aal-body');
            if (!rows.length) {
                body.innerHTML = '<div class="text-muted">No changes recorded yet.</div>';
                return;
            }
            const trs = rows.map(r =>
                '<tr>' +
                '<td><span class="badge badge-light-primary text-uppercase">' + esc(r.label) + '</span></td>' +
                '<td>' + esc(r.field) + (r.action ? ' <span class="badge badge-light-info fs-8">' + esc(r.action) + '</span>' : '') + '</td>' +
                '<td class="fs-8 text-muted mw-250px text-truncate">' + esc(r.old_value ?? '—') + '</td>' +
                '<td class="fs-8 text-muted mw-250px text-truncate">' + esc(r.new_value ?? '—') + '</td>' +
                '<td>' + esc(r.admin_name || ('#' + r.changed_by)) + '</td>' +
                '<td class="text-muted fs-8">' + esc(r.created_at) + '</td>' +
                '</tr>').join('');
            body.innerHTML = '<table class="table table-row-dashed fs-7"><thead><tr class="fw-bold text-muted">' +
                '<th>Module</th><th>Field</th><th>Old</th><th>New</th><th>By</th><th>When</th>' +
                '</tr></thead><tbody>' + trs + '</tbody></table>';
        }

        async function load() {
            const body = document.getElementById('aal-body');
            body.innerHTML = 'Loading…';
            const res = await fetch(base + 'admin/audit-log/log',
                { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const j = await res.json();
            allRows = j.rows || [];
            render(allRows);
        }

        document.getElementById('aal-filter').addEventListener('change', (e) => {
            const v = e.target.value;
            render(v ? allRows.filter(r => r.source === v) : allRows);
        });

        document.getElementById('aal-clear-btn').addEventListener('click', async () => {
            if (!confirm('Permanently clear ALL audit log history shown on this page? This cannot be undone.')) return;
            const fd = new FormData();
            fd.append('confirm', 'yes');
            const res = await fetch(base + 'admin/audit-log/clear', {
                method: 'POST', body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            let j = {};
            try { j = await res.json(); } catch (e) { j = { status: 'error', message: 'Server error.' }; }
            toast(j.message || '', j.status === 'success');
            if (j.status === 'success') load();
        });

        load();
    })();
    </script>
</body>
