<?php $this->load->view('admin/Layout/common_style'); ?>

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
        const DEFAULT_AVATAR = '<?php echo default_avatar_url(); ?>';
        let page = 1;
        const limit = 50;
        let categories = {};
        let refTypes = {};
        let explorerUrl = '';

        function avatarImg(url, size) {
            return '<img src="' + esc(url) + '" style="width:' + size + 'px;height:' + size + 'px;border-radius:50%;object-fit:cover;" ' +
                'onerror="this.onerror=null;this.src=\'' + DEFAULT_AVATAR + '\';">';
        }

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

        /* Real on-chain hashes (0x…) link out to the explorer; DRYRUN-/internal
           placeholders never had a real broadcast, so they render as plain text. */
        function hashLink(hash) {
            if (!hash) return '<span class="text-muted">—</span>';
            const isReal = /^0x[0-9a-f]{10,}$/i.test(hash);
            const label = '<span class="badge badge-light-warning fs-8" style="font-family:monospace;">' + esc(shortHash(hash)) + '</span>';
            if (!isReal || !explorerUrl) return label;
            return '<a href="' + explorerUrl + '/tx/' + encodeURIComponent(hash) + '" target="_blank" rel="noopener">' + label + '</a>';
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
            explorerUrl = j.explorer_url || '';

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

        /* Platform-perspective flow: is the hot/treasury wallet receiving (IN)
           or sending (OUT), as opposed to the Amount column's user-wallet
           credit/debit? Two families, confirmed against every on-chain call
           site in the codebase:
             - Family A (deposit/withdrawal/bman_withdrawal): NOT inverted —
               a user-wallet credit really is the platform receiving (a
               deposit), a debit really is the platform sending (a withdrawal).
             - Everything else with a real on-chain leg is INVERTED: a user
               credit means the treasury just sent funds out (bonus, stake
               principal, ROI, binary matching); a user debit means the user
               sent funds to the platform (a swap purchase).
           Some types never have a real on-chain leg (stake_maturity, ceiling
           release, and an unsettled wallet_transfer) — those get no arrow. */
        const FLOW_NOT_INVERTED = new Set(['deposit', 'withdrawal', 'bman_withdrawal']);
        // Never a real on-chain leg, regardless of credit/debit: stake_maturity
        // is a bookkeeping-only principal release, ceiling_release never calls
        // web3, order_reset is a pure ledger reversal (admin correction tooling).
        const FLOW_ALWAYS_INTERNAL = new Set(['stake_maturity', 'ceiling_release', 'order_reset']);
        // Conditionally on-chain — whether a real leg exists varies per row
        // (wallet_transfer only when settlement is enabled; admin_adjustment
        // splits between a pure ledger grant and a real bulk-upload send) — so
        // trust the row's own matched onchain data instead of the type alone.
        const FLOW_ONCHAIN_GATED = new Set(['wallet_transfer', 'admin_adjustment']);
        function flowDirection(r) {
            if (FLOW_ALWAYS_INTERNAL.has(r.reference_type)) return null;
            if (FLOW_ONCHAIN_GATED.has(r.reference_type) && !r.onchain) return null;
            const isCredit = r.direction === 'credit';
            const notInverted = FLOW_NOT_INVERTED.has(r.reference_type);
            if (notInverted) return isCredit ? 'in' : 'out';
            return isCredit ? 'out' : 'in';
        }
        function flowBadge(r) {
            const dir = flowDirection(r);
            if (dir === 'in') return '<span class="badge badge-light-success fs-8" title="Platform/hot wallet received funds">&#8595; IN</span>';
            if (dir === 'out') return '<span class="badge badge-light-danger fs-8" title="Platform/hot wallet sent funds">&#8593; OUT</span>';
            return '<span class="text-muted fs-8" title="No real on-chain leg — internal bookkeeping only">—</span>';
        }

        function renderWalletRows(rows) {
            const body = document.getElementById('at-wallet-body');
            if (!rows.length) {
                body.innerHTML = '<div class="text-muted">No transactions found.</div>';
                return;
            }
            const startNo = (page - 1) * limit;
            const trs = rows.map((r, i) => {
                const amt = (r.direction === 'credit' ? '+' : '−') + fmt(r.amount) + ' ' + esc(r.wallet_type.toUpperCase());
                const amtCls = r.direction === 'credit' ? 'text-success' : 'text-danger';
                const onchain = r.onchain
                    ? '<span class="badge badge-light-info fs-8">' + esc(r.onchain.status || 'onchain') + '</span>'
                    : '<span class="badge badge-light fs-8">internal</span>';
                const gasFee = (r.onchain && r.onchain.gas_fee_total != null)
                    ? fmt(r.onchain.gas_fee_total) + ' BNB'
                    : '<span class="text-muted">—</span>';
                return '<tr class="at-row" data-id="' + r.ledger_id + '" style="cursor:pointer">' +
                    '<td class="fs-8 text-muted">' + (startNo + i + 1) + '</td>' +
                    '<td class="fs-8 text-muted">' + esc(r.created_at) + '</td>' +
                    '<td><div class="d-flex align-items-center gap-2">' +
                        avatarImg(r.avatar, 24) +
                        '<span>#' + esc(r.user_id) + '</span>' +
                        '</div></td>' +
                    '<td>' + categoryBadge(r.category) + '<div class="fs-8 text-muted">' + esc(r.type_label) + '</div></td>' +
                    '<td class="fw-bold ' + amtCls + '">' + amt + '</td>' +
                    '<td>' + flowBadge(r) + '</td>' +
                    '<td class="fs-8 text-muted">' + fmt(r.balance_after) + '</td>' +
                    '<td class="fs-8 text-muted mw-200px text-truncate">' + esc(r.description || '—') + '</td>' +
                    '<td class="fs-8" title="' + esc(r.tx_hash || '') + '">' + hashLink(r.tx_hash) + '</td>' +
                    '<td>' + onchain + '</td>' +
                    '<td class="fs-8 text-muted">' + gasFee + '</td>' +
                    '</tr>';
            }).join('');
            body.innerHTML = '<table class="table table-row-dashed fs-7"><thead><tr class="fw-bold text-muted">' +
                '<th>S.No</th><th>When</th><th>User</th><th>Type</th><th>Amount</th><th>Flow</th><th>Balance After</th>' +
                '<th>Description</th><th>Tx Hash</th><th>Chain</th><th>Gas Fee</th>' +
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
            const userHtml = '<div class="d-flex align-items-center gap-2">' +
                avatarImg(r.avatar, 28) +
                '<span>#' + esc(r.user_id) + '</span></div>';
            const rowsOutRaw = [
                ['Ledger ID', esc(r.ledger_id)], ['User', userHtml], ['Wallet', esc(r.wallet_type)],
                ['Type', esc(r.type_label + ' (' + r.reference_type + ')')],
                ['Amount', esc((r.direction === 'credit' ? '+' : '−') + fmt(r.amount))],
                ['Balance After', esc(fmt(r.balance_after))],
                ['Description', esc(r.description || '—')],
                ['Tx Hash', hashLink(r.tx_hash)],
                ['When', esc(r.created_at)],
            ];
            if (r.onchain) {
                rowsOutRaw.push(['On-chain Status', esc(r.onchain.status)]);
                rowsOutRaw.push(['On-chain Amount', esc(fmt(r.onchain.amount) + ' (' + (r.onchain.tx_type || '') + ')')]);
                if (r.onchain.block_number) rowsOutRaw.push(['Block', esc(r.onchain.block_number)]);
                if (r.onchain.gas_fee_total != null) rowsOutRaw.push(['Gas Fee', esc(fmt(r.onchain.gas_fee_total) + ' BNB')]);
            }
            if (r.source && r.source.table) {
                rowsOutRaw.push(['Source Table', esc(r.source.table + ' #' + (r.source.id || '—'))]);
            }
            rowsOutRaw.forEach(([k, v]) => {
                html += '<tr><td class="fw-bold text-muted" style="width:180px">' + esc(k) + '</td><td>' + (v ?? '—') + '</td></tr>';
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
