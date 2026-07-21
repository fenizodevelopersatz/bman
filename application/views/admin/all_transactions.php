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
                                            <a href="<?php echo base_url(); ?>" class="text-muted text-hover-primary">Wallet</a>
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
                                        <h3 class="card-title fw-bold">
                                            <ul class="nav nav-tabs nav-line-tabs fs-6 border-0">
                                                <li class="nav-item">
                                                    <a class="nav-link active" data-bs-toggle="tab" href="#at-tab-wallet">Wallet Transactions</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" data-bs-toggle="tab" href="#at-tab-cron">Cron Run Log</a>
                                                </li>
                                            </ul>
                                        </h3>
                                    </div>
                                    <div class="card-body pt-4">
                                        <div class="tab-content">

                                            <!-- Wallet Transactions -->
                                            <div class="tab-pane fade show active" id="at-tab-wallet">
                                                <div class="row g-3 mb-5" id="at-filters">
                                                    <div class="col-md-2">
                                                        <label class="form-label fs-8">Category</label>
                                                        <select class="form-select form-select-sm" id="at-f-category">
                                                            <option value="">All</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label fs-8">Wallet</label>
                                                        <select class="form-select form-select-sm" id="at-f-wallet">
                                                            <option value="">All</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label fs-8">Direction</label>
                                                        <select class="form-select form-select-sm" id="at-f-direction">
                                                            <option value="">All</option>
                                                            <option value="credit">Credit</option>
                                                            <option value="debit">Debit</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label fs-8">From</label>
                                                        <input type="date" class="form-control form-control-sm" id="at-f-from">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label fs-8">To</label>
                                                        <input type="date" class="form-control form-control-sm" id="at-f-to">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label fs-8">User ID</label>
                                                        <input type="number" class="form-control form-control-sm" id="at-f-user" placeholder="Any">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label fs-8">Search (description / ref / tx hash)</label>
                                                        <input type="text" class="form-control form-control-sm" id="at-f-search" placeholder="Search…">
                                                    </div>
                                                    <div class="col-md-2 d-flex align-items-end">
                                                        <button type="button" class="btn btn-primary btn-sm w-100" id="at-f-apply">Apply</button>
                                                    </div>
                                                    <div class="col-md-2 d-flex align-items-end">
                                                        <button type="button" class="btn btn-light btn-sm w-100" id="at-f-reset">Reset</button>
                                                    </div>
                                                </div>

                                                <div id="at-wallet-body" class="table-responsive">Loading…</div>

                                                <div class="d-flex justify-content-between align-items-center mt-4">
                                                    <div class="text-muted fs-7" id="at-wallet-summary"></div>
                                                    <div class="d-flex gap-2">
                                                        <button type="button" class="btn btn-light btn-sm" id="at-wallet-prev">&laquo; Prev</button>
                                                        <button type="button" class="btn btn-light btn-sm" id="at-wallet-next">Next &raquo;</button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Cron Run Log -->
                                            <div class="tab-pane fade" id="at-tab-cron">
                                                <div id="at-cron-body" class="table-responsive">Loading…</div>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <!-- Detail modal -->
                                <div class="modal fade" id="at-detail-modal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered mw-800px">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h3 class="modal-title">Transaction Detail</h3>
                                                <div class="btn btn-sm btn-icon" data-bs-dismiss="modal">
                                                    <i class="ki-outline ki-cross fs-1"></i>
                                                </div>
                                            </div>
                                            <div class="modal-body scroll-y mh-500px" id="at-detail-body">Loading…</div>
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
        let page = 1;
        const limit = 50;
        let categories = {};
        let refTypes = {};

        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g,
                c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        function fmt(n) {
            const v = parseFloat(n);
            return isNaN(v) ? '0.00' : v.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 8 });
        }

        function shortHash(h) {
            if (!h) return '—';
            return h.length > 16 ? h.slice(0, 8) + '…' + h.slice(-6) : h;
        }

        async function fetchJson(url) {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            return res.json();
        }

        /* ---------------- filter options ---------------- */
        async function loadOptions() {
            const j = await fetchJson(base + 'admin/all-transaction/options');
            if (!j.status) return;
            categories = j.categories || {};
            refTypes = j.reference_types || {};

            const catSel = document.getElementById('at-f-category');
            Object.keys(categories).forEach(k => {
                const o = document.createElement('option');
                o.value = k; o.textContent = categories[k];
                catSel.appendChild(o);
            });

            const walletSel = document.getElementById('at-f-wallet');
            (j.wallets || []).forEach(w => {
                const o = document.createElement('option');
                o.value = w; o.textContent = w.toUpperCase();
                walletSel.appendChild(o);
            });
        }

        /* ---------------- wallet transactions tab ---------------- */
        function currentFilters() {
            const p = new URLSearchParams();
            const category = document.getElementById('at-f-category').value;
            const wallet = document.getElementById('at-f-wallet').value;
            const direction = document.getElementById('at-f-direction').value;
            const from = document.getElementById('at-f-from').value;
            const to = document.getElementById('at-f-to').value;
            const user = document.getElementById('at-f-user').value;
            const search = document.getElementById('at-f-search').value;
            if (category) p.set('category', category);
            if (wallet) p.set('wallet_type', wallet);
            if (direction) p.set('direction', direction);
            if (from) p.set('date_from', from);
            if (to) p.set('date_to', to);
            if (user) p.set('user_id', user);
            if (search) p.set('search', search);
            p.set('page', page);
            p.set('limit', limit);
            return p;
        }

        function categoryBadge(cat) {
            const map = {
                deposit: 'success', withdrawal: 'danger', purchase: 'primary',
                roi: 'info', binary: 'warning', transfer: 'secondary',
                ceiling: 'dark', reduction: 'danger', admin: 'dark',
            };
            const cls = map[cat] || 'light';
            return '<span class="badge badge-light-' + cls + '">' + esc(categories[cat] || cat) + '</span>';
        }

        function renderWalletRows(rows) {
            const body = document.getElementById('at-wallet-body');
            if (!rows.length) {
                body.innerHTML = '<div class="text-muted">No transactions found.</div>';
                return;
            }
            const trs = rows.map(r => {
                const amt = (r.direction === 'credit' ? '+' : '−') + fmt(r.amount) + ' ' + esc(r.wallet_type.toUpperCase());
                const amtCls = r.direction === 'credit' ? 'text-success' : 'text-danger';
                const onchain = r.onchain
                    ? '<span class="badge badge-light-info fs-8">' + esc(r.onchain.status || 'onchain') + '</span>'
                    : '<span class="badge badge-light fs-8">internal</span>';
                return '<tr class="at-row" data-id="' + r.ledger_id + '" style="cursor:pointer">' +
                    '<td class="fs-8 text-muted">' + esc(r.created_at) + '</td>' +
                    '<td>#' + esc(r.user_id) + '</td>' +
                    '<td>' + categoryBadge(r.category) + '<div class="fs-8 text-muted">' + esc(r.type_label) + '</div></td>' +
                    '<td class="fw-bold ' + amtCls + '">' + amt + '</td>' +
                    '<td class="fs-8 text-muted">' + fmt(r.balance_after) + '</td>' +
                    '<td class="fs-8 text-muted mw-200px text-truncate">' + esc(r.description || '—') + '</td>' +
                    '<td class="fs-8" title="' + esc(r.tx_hash || '') + '">' + esc(shortHash(r.tx_hash)) + '</td>' +
                    '<td>' + onchain + '</td>' +
                    '</tr>';
            }).join('');
            body.innerHTML = '<table class="table table-row-dashed fs-7"><thead><tr class="fw-bold text-muted">' +
                '<th>When</th><th>User</th><th>Type</th><th>Amount</th><th>Balance After</th>' +
                '<th>Description</th><th>Tx Hash</th><th>Chain</th>' +
                '</tr></thead><tbody>' + trs + '</tbody></table>';

            body.querySelectorAll('.at-row').forEach(tr => {
                tr.addEventListener('click', () => openDetail(tr.dataset.id));
            });
        }

        async function loadWalletTab() {
            const body = document.getElementById('at-wallet-body');
            body.innerHTML = 'Loading…';
            const j = await fetchJson(base + 'admin/all-transaction/list?' + currentFilters().toString());
            if (!j.status) { body.innerHTML = '<div class="text-danger">Failed to load.</div>'; return; }
            renderWalletRows(j.rows || []);
            const shown = (j.rows || []).length;
            const start = j.total ? ((j.page - 1) * j.limit + 1) : 0;
            document.getElementById('at-wallet-summary').textContent =
                j.total ? ('Showing ' + start + '–' + (start + shown - 1) + ' of ' + j.total) : 'No results';
            document.getElementById('at-wallet-prev').disabled = j.page <= 1;
            document.getElementById('at-wallet-next').disabled = (j.page * j.limit) >= j.total;
        }

        async function openDetail(id) {
            const m = bootstrap.Modal.getOrCreateInstance(document.getElementById('at-detail-modal'));
            const body = document.getElementById('at-detail-body');
            body.innerHTML = 'Loading…';
            m.show();
            const j = await fetchJson(base + 'admin/all-transaction/detail?id=' + encodeURIComponent(id));
            if (!j.status) { body.innerHTML = '<div class="text-danger">' + esc(j.message || 'Not found') + '</div>'; return; }
            const r = j.row;
            let html = '<table class="table table-row-dashed fs-7">';
            const rowsOut = [
                ['Ledger ID', r.ledger_id], ['User', '#' + r.user_id], ['Wallet', r.wallet_type],
                ['Type', r.type_label + ' (' + r.reference_type + ')'],
                ['Amount', (r.direction === 'credit' ? '+' : '−') + fmt(r.amount)],
                ['Balance After', fmt(r.balance_after)],
                ['Description', r.description || '—'],
                ['Tx Hash', r.tx_hash || '—'],
                ['When', r.created_at],
            ];
            if (r.onchain) {
                rowsOut.push(['On-chain Status', r.onchain.status]);
                rowsOut.push(['On-chain Amount', fmt(r.onchain.amount) + ' (' + (r.onchain.tx_type || '') + ')']);
                if (r.onchain.block_number) rowsOut.push(['Block', r.onchain.block_number]);
            }
            if (r.source && r.source.table) {
                rowsOut.push(['Source Table', r.source.table + ' #' + (r.source.id || '—')]);
            }
            rowsOut.forEach(([k, v]) => {
                html += '<tr><td class="fw-bold text-muted" style="width:180px">' + esc(k) + '</td><td>' + esc(v ?? '—') + '</td></tr>';
            });
            html += '</table>';
            if (r.related_ledger && r.related_ledger.length) {
                html += '<div class="fw-bold mb-2 mt-4">Related Ledger Rows</div>';
                html += '<table class="table table-row-dashed fs-7"><thead><tr class="fw-bold text-muted"><th>When</th><th>Wallet</th><th>Amount</th></tr></thead><tbody>';
                r.related_ledger.forEach(rl => {
                    html += '<tr><td class="fs-8 text-muted">' + esc(rl.created_at) + '</td><td>' + esc(rl.wallet_type) + '</td>' +
                        '<td>' + (rl.direction === 'credit' ? '+' : '−') + fmt(rl.amount) + '</td></tr>';
                });
                html += '</tbody></table>';
            }
            body.innerHTML = html;
        }

        document.getElementById('at-f-apply').addEventListener('click', () => { page = 1; loadWalletTab(); });
        document.getElementById('at-f-reset').addEventListener('click', () => {
            document.querySelectorAll('#at-filters input, #at-filters select').forEach(el => el.value = '');
            page = 1; loadWalletTab();
        });
        document.getElementById('at-wallet-prev').addEventListener('click', () => { if (page > 1) { page--; loadWalletTab(); } });
        document.getElementById('at-wallet-next').addEventListener('click', () => { page++; loadWalletTab(); });

        /* ---------------- cron run log tab ---------------- */
        let cronLoaded = false;
        async function loadCronTab() {
            const body = document.getElementById('at-cron-body');
            body.innerHTML = 'Loading…';
            const j = await fetchJson(base + 'admin/all-transaction/cron-log');
            if (!j.status) { body.innerHTML = '<div class="text-danger">Failed to load.</div>'; return; }
            const rows = j.rows || [];
            if (!rows.length) {
                body.innerHTML = '<div class="text-muted">No cron runs recorded yet.</div>';
                return;
            }
            const statusCls = { success: 'success', error: 'danger', timeout: 'warning', unknown: 'secondary' };
            const trs = rows.map(r => {
                let summary = r.summary;
                try { summary = JSON.stringify(JSON.parse(r.summary)); } catch (e) {}
                return '<tr>' +
                    '<td class="fs-8 text-muted">' + esc(r.created_at) + '</td>' +
                    '<td>' + esc(r.cron_name) + '</td>' +
                    '<td><span class="badge badge-light-' + (statusCls[r.status] || 'secondary') + '">' + esc(r.status) + '</span></td>' +
                    '<td class="fs-8 text-muted">' + (r.duration_ms != null ? r.duration_ms + ' ms' : '—') + '</td>' +
                    '<td class="fs-8 text-muted mw-400px text-truncate" title="' + esc(summary) + '">' + esc(summary) + '</td>' +
                    '</tr>';
            }).join('');
            body.innerHTML = '<table class="table table-row-dashed fs-7"><thead><tr class="fw-bold text-muted">' +
                '<th>When</th><th>Cron</th><th>Status</th><th>Duration</th><th>Summary</th>' +
                '</tr></thead><tbody>' + trs + '</tbody></table>';
        }

        document.querySelector('a[href="#at-tab-cron"]').addEventListener('shown.bs.tab', () => {
            if (!cronLoaded) { cronLoaded = true; loadCronTab(); }
        });

        loadOptions().then(loadWalletTab);
    })();
    </script>
</body>
