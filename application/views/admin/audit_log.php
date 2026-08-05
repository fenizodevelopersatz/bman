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
                            </div>
                        </div>
                        <!--end::Toolbar-->

                        <div id="kt_app_content" class="app-content flex-column-fluid mt-10">
                            <div id="kt_app_content_container" class="app-container container-xxl">

                                <?php $this->load->view('notification'); ?>

                                <div class="card">
                                    <div class="card-header border-transparent pt-5">
                                        <h3 class="card-title fw-bold">All Settings Changes</h3>
                                        <div class="card-toolbar d-flex flex-wrap gap-2">
                                            <select class="form-select form-select-sm w-200px" id="aal-filter" aria-label="Filter by module">
                                                <option value="">All modules</option>
                                                <option value="staking_plans">Staking Plans</option>
                                                <option value="roi_structure">ROI Structure</option>
                                                <option value="bonus_settings">Bonus &amp; Matching Settings</option>
                                                <option value="withdraw_settings">Withdraw Settings</option>
                                                <option value="token_withdraw_settings">Token Withdraw Settings</option>
                                                <option value="coin_distribution">Coin Distribution</option>
                                                <option value="token_settings">Token Settings / Exchange Rate</option>
                                                <option value="member_status">Member Status</option>
                                            </select>
                                            <select class="form-select form-select-sm w-150px" id="aal-date-preset" aria-label="Quick date range">
                                                <option value="">All dates</option>
                                                <option value="today">Today</option>
                                                <option value="7">Last 7 days</option>
                                                <option value="30">Last 30 days</option>
                                                <option value="this_month">This month</option>
                                            </select>
                                            <input type="date" class="form-control form-control-sm w-150px" id="aal-date-from"
                                                   aria-label="From date" title="From date">
                                            <input type="date" class="form-control form-control-sm w-150px" id="aal-date-to"
                                                   aria-label="To date" title="To date">
                                            <select class="form-select form-select-sm w-150px" id="aal-order" aria-label="Sort order">
                                                <option value="desc" selected>Newest first</option>
                                                <option value="asc">Oldest first</option>
                                            </select>
                                            <button type="button" class="btn btn-sm btn-light" id="aal-clear">Clear</button>
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
        const moduleFilter = document.getElementById('aal-filter');
        const presetFilter = document.getElementById('aal-date-preset');
        const fromFilter = document.getElementById('aal-date-from');
        const toFilter = document.getElementById('aal-date-to');
        const orderFilter = document.getElementById('aal-order');

        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g,
                c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
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

        function rowTime(row) {
            const value = String(row.created_at || '').replace(' ', 'T');
            const time = new Date(value).getTime();
            return Number.isNaN(time) ? 0 : time;
        }

        function applyFilters() {
            const module = moduleFilter.value;
            const from = fromFilter.value ? new Date(fromFilter.value + 'T00:00:00').getTime() : null;
            const to = toFilter.value ? new Date(toFilter.value + 'T23:59:59.999').getTime() : null;

            const rows = allRows.filter(row => {
                const time = rowTime(row);
                return (!module || row.source === module)
                    && (from === null || time >= from)
                    && (to === null || time <= to);
            }).sort((a, b) => orderFilter.value === 'asc'
                ? rowTime(a) - rowTime(b)
                : rowTime(b) - rowTime(a));

            render(rows);
        }

        function toDateValue(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return year + '-' + month + '-' + day;
        }

        function applyPreset(value) {
            if (!value) {
                fromFilter.value = '';
                toFilter.value = '';
                applyFilters();
                return;
            }

            const today = new Date();
            const from = new Date(today.getFullYear(), today.getMonth(), today.getDate());
            if (value === '7' || value === '30') {
                from.setDate(from.getDate() - (Number(value) - 1));
            } else if (value === 'this_month') {
                from.setDate(1);
            }
            fromFilter.value = toDateValue(from);
            toFilter.value = toDateValue(today);
            applyFilters();
        }

        async function load() {
            const body = document.getElementById('aal-body');
            body.innerHTML = 'Loading…';
            const res = await fetch(base + 'admin/audit-log/log',
                { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const j = await res.json();
            allRows = j.rows || [];
            applyFilters();
        }

        moduleFilter.addEventListener('change', applyFilters);
        orderFilter.addEventListener('change', applyFilters);
        presetFilter.addEventListener('change', (e) => applyPreset(e.target.value));
        [fromFilter, toFilter].forEach(input => input.addEventListener('change', () => {
            presetFilter.value = '';
            applyFilters();
        }));
        document.getElementById('aal-clear').addEventListener('click', () => {
            moduleFilter.value = '';
            presetFilter.value = '';
            fromFilter.value = '';
            toFilter.value = '';
            orderFilter.value = 'desc';
            applyFilters();
        });

        load();
    })();
    </script>
</body>
