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
                                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-4" id="aal-pager" style="display:none !important;">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="text-muted fs-7" id="aal-page-info"></span>
                                                <select class="form-select form-select-sm w-auto" id="aal-page-size" aria-label="Rows per page">
                                                    <option value="25">25 / page</option>
                                                    <option value="50" selected>50 / page</option>
                                                    <option value="100">100 / page</option>
                                                </select>
                                            </div>
                                            <ul class="pagination pagination-sm mb-0" id="aal-page-links"></ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Detail modal -->
                                <div class="modal fade" id="aal-detail-modal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:95vw;width:1200px;">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h3 class="modal-title">Change Detail</h3>
                                                <div class="btn btn-sm btn-icon" data-bs-dismiss="modal">
                                                    <i class="ki-outline ki-cross fs-1"></i>
                                                </div>
                                            </div>
                                            <div class="modal-body scroll-y" style="max-height:80vh" id="aal-detail-body"></div>
                                        </div>
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
        let filteredRows = [];
        let currentPage = 1;
        const pageSizeSelect = document.getElementById('aal-page-size');
        const pagerBox = document.getElementById('aal-pager');
        const pageInfo = document.getElementById('aal-page-info');
        const pageLinks = document.getElementById('aal-page-links');
        const moduleFilter = document.getElementById('aal-filter');
        const presetFilter = document.getElementById('aal-date-preset');
        const fromFilter = document.getElementById('aal-date-from');
        const toFilter = document.getElementById('aal-date-to');
        const orderFilter = document.getElementById('aal-order');

        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g,
                c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        function humanizeKey(k) {
            return String(k).replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        }

        function fmtScalar(v) {
            if (v === null || v === undefined || v === '') return '<span class="text-muted">—</span>';
            if (typeof v === 'boolean') return v ? 'Yes' : 'No';
            if (typeof v === 'object') return '<code class="fs-8">' + esc(JSON.stringify(v)) + '</code>';
            return '<span class="text-break">' + esc(String(v)) + '</span>';
        }

        /* Whole-row settings changes (e.g. Token Settings / Exchange Rate) store
           the entire config row as a JSON blob in old_value/new_value, not one
           scalar per field. A raw JSON dump is unreadable, so parse both sides
           and render a Field / Old / New comparison table instead — changed
           fields highlighted, unchanged fields muted so the real diff pops out. */
        function tryParseObject(v) {
            if (v === null || v === undefined || v === '') return null;
            try {
                const p = JSON.parse(v);
                return (p && typeof p === 'object' && !Array.isArray(p)) ? p : null;
            } catch (e) {
                return null;
            }
        }

        function renderJsonDiff(oldObj, newObj) {
            const keys = [];
            const seen = new Set();
            Object.keys(oldObj || {}).concat(Object.keys(newObj || {})).forEach(k => {
                if (!seen.has(k)) { seen.add(k); keys.push(k); }
            });
            let html = '<table class="table table-row-dashed fs-7 mb-0"><thead><tr class="fw-bold text-muted">' +
                '<th style="width:220px">Field</th><th>Old Value</th><th>New Value</th></tr></thead><tbody>';
            keys.forEach(k => {
                const ov = oldObj ? oldObj[k] : undefined;
                const nv = newObj ? newObj[k] : undefined;
                const changed = JSON.stringify(ov) !== JSON.stringify(nv);
                html += '<tr class="' + (changed ? 'table-warning' : '') + '">' +
                    '<td class="' + (changed ? 'fw-bold' : 'text-muted') + '">' + esc(humanizeKey(k)) + '</td>' +
                    '<td>' + fmtScalar(ov) + '</td>' +
                    '<td>' + fmtScalar(nv) + '</td>' +
                    '</tr>';
            });
            html += '</tbody></table>';
            return html;
        }

        /* Human-readable single-change popup — the table truncates long Old/New
           values (RPC URLs, addresses, etc.) to fit the row; the modal shows
           them in full, labeled, instead of relying on a hover tooltip. */
        function openAuditDetail(row) {
            const m = bootstrap.Modal.getOrCreateInstance(document.getElementById('aal-detail-modal'));
            const body = document.getElementById('aal-detail-body');

            const headRows = [
                ['Module', esc(row.label)],
                ['Field', esc(row.field) + (row.action ? ' <span class="badge badge-light-info fs-8">' + esc(row.action) + '</span>' : '')],
                ['Changed By', esc(row.admin_name || ('#' + row.changed_by))],
                ['When', esc(row.created_at)],
            ];
            let html = '<table class="table table-row-dashed fs-7 mb-2">';
            headRows.forEach(([k, v]) => {
                html += '<tr><td class="fw-bold text-muted" style="width:160px">' + esc(k) + '</td><td>' + v + '</td></tr>';
            });
            html += '</table>';

            const oldObj = tryParseObject(row.old_value);
            const newObj = tryParseObject(row.new_value);
            if (oldObj || newObj) {
                html += renderJsonDiff(oldObj, newObj);
            } else {
                html += '<table class="table table-row-dashed fs-7 mb-0">' +
                    '<tr><td class="fw-bold text-muted" style="width:160px">Old Value</td><td>' + fmtScalar(row.old_value) + '</td></tr>' +
                    '<tr><td class="fw-bold text-muted" style="width:160px">New Value</td><td>' + fmtScalar(row.new_value) + '</td></tr>' +
                    '</table>';
            }

            body.innerHTML = html;
            m.show();
        }

        function render(rows) {
            const body = document.getElementById('aal-body');
            if (!rows.length) {
                body.innerHTML = '<div class="text-muted">No changes recorded yet.</div>';
                return;
            }
            const trs = rows.map((r, i) =>
                '<tr class="aal-row" data-idx="' + i + '" style="cursor:pointer">' +
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

            body.querySelectorAll('.aal-row').forEach(tr => {
                tr.addEventListener('click', () => openAuditDetail(rows[Number(tr.dataset.idx)]));
            });
        }

        function rowTime(row) {
            const value = String(row.created_at || '').replace(' ', 'T');
            const time = new Date(value).getTime();
            return Number.isNaN(time) ? 0 : time;
        }

        function pageSize() { return parseInt(pageSizeSelect.value, 10) || 50; }

        function renderPager(totalPages, size) {
            if (!filteredRows.length) { pagerBox.style.setProperty('display', 'none', 'important'); return; }
            pagerBox.style.setProperty('display', 'flex', 'important');
            const start = (currentPage - 1) * size + 1;
            const end = Math.min(filteredRows.length, currentPage * size);
            pageInfo.textContent = 'Showing ' + start + '–' + end + ' of ' + filteredRows.length;

            let html = '<li class="page-item' + (currentPage === 1 ? ' disabled' : '') + '">' +
                '<a class="page-link" href="javascript:void(0)" data-page="' + (currentPage - 1) + '">Prev</a></li>';
            const windowSize = 5;
            let from = Math.max(1, currentPage - Math.floor(windowSize / 2));
            let to = Math.min(totalPages, from + windowSize - 1);
            from = Math.max(1, to - windowSize + 1);
            for (let p = from; p <= to; p++) {
                html += '<li class="page-item' + (p === currentPage ? ' active' : '') + '">' +
                    '<a class="page-link" href="javascript:void(0)" data-page="' + p + '">' + p + '</a></li>';
            }
            html += '<li class="page-item' + (currentPage === totalPages ? ' disabled' : '') + '">' +
                '<a class="page-link" href="javascript:void(0)" data-page="' + (currentPage + 1) + '">Next</a></li>';
            pageLinks.innerHTML = html;

            pageLinks.querySelectorAll('a[data-page]').forEach(a => {
                a.addEventListener('click', () => {
                    const p = parseInt(a.dataset.page, 10);
                    if (p >= 1 && p <= totalPages && p !== currentPage) { currentPage = p; renderPage(); }
                });
            });
        }

        function renderPage() {
            const size = pageSize();
            const totalPages = Math.max(1, Math.ceil(filteredRows.length / size));
            if (currentPage > totalPages) currentPage = totalPages;
            const start = (currentPage - 1) * size;
            render(filteredRows.slice(start, start + size));
            renderPager(totalPages, size);
        }

        function applyFilters() {
            const module = moduleFilter.value;
            const from = fromFilter.value ? new Date(fromFilter.value + 'T00:00:00').getTime() : null;
            const to = toFilter.value ? new Date(toFilter.value + 'T23:59:59.999').getTime() : null;

            filteredRows = allRows.filter(row => {
                const time = rowTime(row);
                return (!module || row.source === module)
                    && (from === null || time >= from)
                    && (to === null || time <= to);
            }).sort((a, b) => orderFilter.value === 'asc'
                ? rowTime(a) - rowTime(b)
                : rowTime(b) - rowTime(a));

            currentPage = 1;
            renderPage();
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
        pageSizeSelect.addEventListener('change', () => { currentPage = 1; renderPage(); });
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
