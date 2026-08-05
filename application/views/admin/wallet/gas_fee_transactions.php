<?php $this->load->view('admin/Layout/common_style'); ?>
<style>
    .gf-mono { font-family: monospace; font-size: .78rem; }
    .gf-row  { cursor: pointer; }
    .gf-row:hover { background: var(--bs-gray-100); }
    .bnb-badge { display:inline-flex;align-items:center;gap:3px;background:rgba(243,186,47,.15);
                 color:#b8860b;border:1px solid rgba(243,186,47,.35);border-radius:6px;
                 padding:1px 7px;font-size:.75rem;font-weight:600; }
    [data-bs-theme="dark"] .bnb-badge { color:#f3ba2f; }
    .gas-ticker { display:inline-flex;align-items:center;gap:8px;background:rgba(16,185,129,.08);
                  border:1px solid rgba(16,185,129,.25);border-radius:8px;padding:4px 12px;font-size:.78rem; }
    .gas-ticker .gt-dot { width:7px;height:7px;border-radius:50%;background:#10b981;
                          display:inline-block;animation:gf-pulse 2s infinite; }
    @keyframes gf-pulse { 0%,100%{opacity:1} 50%{opacity:.3} }
    .gf-kv { display:flex;justify-content:space-between;padding:.3rem 0;border-bottom:1px dashed var(--bs-gray-200);font-size:.82rem; }
    .gf-kv .k { color:var(--bs-gray-600);font-weight:600; }
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
                            <li class="breadcrumb-item text-muted"><?php echo $title; ?></li>
                        </ul>
                    </div>
                    <!-- Live Gas Ticker -->
                    <div class="gas-ticker" id="gf-live-ticker">
                        <span class="gt-dot"></span>
                        <span class="text-muted">Gas:</span>
                        <span id="gf-gwei-slow" class="text-info fw-bold">—</span>
                        <span class="text-muted fs-9">Slow</span>
                        <span class="mx-1 text-muted">|</span>
                        <span id="gf-gwei-std" class="text-success fw-bold">—</span>
                        <span class="text-muted fs-9">Std</span>
                        <span class="mx-1 text-muted">|</span>
                        <span id="gf-gwei-fast" class="fw-bold" style="color:#f59e0b">—</span>
                        <span class="text-muted fs-9">Fast</span>
                        <span class="text-muted fs-9 ms-1">Gwei</span>
                        <span class="badge badge-light-success fs-9 ms-1" id="gf-ticker-refresh" style="cursor:pointer" title="Refresh">↻</span>
                    </div>
                </div>
            </div>

            <div id="kt_app_content" class="app-content flex-column-fluid mt-4">
            <div class="app-container container-xxl">

                <?php $this->load->view('notification'); ?>

                <!-- Summary Stat Cards -->
                <div class="row g-4 mb-6" id="gf-stats-row">
                    <?php
                    $cards = [
                        ['Gas Today',       $stats['gas_today_bnb'],   $stats['gas_today_count'],  'BNB today', '#f3ba2f', 'ki-dollar'],
                        ['Gas This Month',  $stats['gas_month_bnb'],   $stats['gas_month_count'],  'BNB this month', '#10b981', 'ki-chart-simple'],
                        ['Avg Gas Price',   $stats['avg_gas_gwei'],    null,                        'Gwei (30d avg)', '#7239ea', 'ki-graph-up'],
                        ['Failed Txs Today',$stats['failed_today'],    null,                        'failed/reverted', '#f1416c', 'ki-cross-circle'],
                    ];
                    foreach ($cards as [$lbl, $val, $sub, $unit, $clr, $icon]):
                    ?>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-100" style="border-top:3px solid <?php echo $clr; ?>">
                            <div class="card-body py-4 px-5">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="ki-duotone <?php echo $icon; ?> fs-2" style="color:<?php echo $clr; ?>"><span class="path1"></span><span class="path2"></span></i>
                                    <span class="text-muted fw-semibold fs-8 text-uppercase"><?php echo $lbl; ?></span>
                                </div>
                                <div class="d-flex align-items-end gap-2">
                                    <span class="fs-2 fw-bold text-gray-900"><?php echo is_float($val) ? number_format($val, 6) : number_format((int)$val); ?></span>
                                    <span class="text-muted fs-8 mb-1"><?php echo $unit; ?></span>
                                </div>
                                <?php if ($sub !== null): ?>
                                <div class="text-muted fs-9"><?php echo number_format($sub); ?> transaction(s)</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Filter + Table Card -->
                <div class="card">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title flex-grow-1">
                            <div class="d-flex align-items-center position-relative my-1 w-100">
                                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5"><span class="path1"></span><span class="path2"></span></i>
                                <input type="text" id="gf-search" class="form-control form-control-solid w-350px ps-13"
                                       placeholder="Search tx hash, address, user ID, block…">
                            </div>
                        </div>
                        <div class="card-toolbar gap-2">
                            <a href="<?php echo base_url('admin/finance/gas-fee-settings'); ?>" class="btn btn-sm btn-light-warning">
                                <i class="ki-duotone ki-setting-2 fs-4"><span class="path1"></span><span class="path2"></span></i> Gas Policy
                            </a>
                            <button class="btn btn-sm btn-light-primary" id="gf-filter-toggle">
                                <i class="ki-duotone ki-filter fs-4"><span class="path1"></span><span class="path2"></span></i> Filters
                            </button>
                            <button class="btn btn-sm btn-light" id="gf-reset">Reset</button>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light-success dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="ki-duotone ki-exit-up fs-4"><span class="path1"></span><span class="path2"></span></i> Export
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" id="gf-export-csv" href="#">CSV</a></li>
                                    <li><a class="dropdown-item" id="gf-export-excel" href="#">Excel</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Bar -->
                    <div class="card-body border-top d-none py-4" id="gf-filters">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label fs-8 text-muted">Network</label>
                                <select class="form-select form-select-sm" data-gf="network">
                                    <option value="">All</option>
                                    <?php foreach ($options['networks'] as $n): ?>
                                    <option value="<?php echo htmlspecialchars($n); ?>"><?php echo htmlspecialchars($n); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fs-8 text-muted">Status</label>
                                <select class="form-select form-select-sm" data-gf="status">
                                    <option value="">All</option>
                                    <?php foreach ($options['statuses'] as $s): ?>
                                    <option value="<?php echo $s; ?>"><?php echo ucfirst($s); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fs-8 text-muted">Type</label>
                                <select class="form-select form-select-sm" data-gf="tx_type">
                                    <option value="">All</option>
                                    <?php foreach ($options['types'] as $t): ?>
                                    <option value="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($t); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fs-8 text-muted">Date From</label>
                                <input type="date" class="form-control form-control-sm" data-gf="date_from">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fs-8 text-muted">Date To</label>
                                <input type="date" class="form-control form-control-sm" data-gf="date_to">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fs-8 text-muted">User ID</label>
                                <input type="number" class="form-control form-control-sm" data-gf="user_id" placeholder="Any">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fs-8 text-muted">Gas Min (BNB)</label>
                                <input type="number" step="any" class="form-control form-control-sm" data-gf="gas_min">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fs-8 text-muted">Gas Max (BNB)</label>
                                <input type="number" step="any" class="form-control form-control-sm" data-gf="gas_max">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fs-8 text-muted">Tx Hash</label>
                                <input type="text" class="form-control form-control-sm" data-gf="tx_hash" placeholder="0x…">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-sm btn-primary w-100" id="gf-apply">Apply</button>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="card-body pt-2">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-7 gy-3">
                                <thead>
                                    <tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                                        <th>Tx Hash</th>
                                        <th>Date</th>
                                        <th>User</th>
                                        <th>Network</th>
                                        <th>Type</th>
                                        <th class="text-end">Gas Used</th>
                                        <th class="text-end">Gas Price</th>
                                        <th class="text-end">Gas Fee</th>
                                        <th>Status</th>
                                        <th class="text-end">Block</th>
                                    </tr>
                                </thead>
                                <tbody id="gf-body" class="text-gray-700 fw-semibold">
                                    <tr><td colspan="10" class="text-muted py-6 text-center">Loading…</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <span class="text-muted fs-8" id="gf-count"></span>
                            <div class="d-flex gap-2 align-items-center">
                                <button class="btn btn-sm btn-light" id="gf-prev">Prev</button>
                                <span class="fs-8 text-muted" id="gf-page-info"></span>
                                <button class="btn btn-sm btn-light" id="gf-next">Next</button>
                            </div>
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

<!-- Detail Modal -->
<div class="modal fade" id="gf-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable mw-700px">
        <div class="modal-content">
            <div class="modal-header py-4">
                <h3 class="modal-title fs-5">Gas Fee Transaction Detail</h3>
                <div class="btn btn-sm btn-icon" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></div>
            </div>
            <div class="modal-body" id="gf-modal-body">Loading…</div>
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
    let page = 1, limit = 50, totalPages = 1;
    let liveGasTimer = null;
    let explorer = 'https://bscscan.com';

    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const short = (s, n = 10) => s ? (s.length > n + 6 ? esc(s.slice(0,n)) + '…' + esc(s.slice(-4)) : esc(s)) : '<span class="text-muted">—</span>';
    const fmtBnb = v => (v == null || v === '') ? '—' : Number(v).toFixed(8);
    const fmtGwei = v => (v == null || v === '') ? '—' : Number(v).toFixed(2);
    const fmtGasUsed = v => (v == null || v === '') ? '—' : Number(v).toLocaleString();

    const statusBadge = st => {
        if (!st) return '<span class="text-muted">—</span>';
        const m = {confirmed:'success',pending:'warning',processing:'info',failed:'danger',reverted:'danger',partial:'warning',cancelled:'secondary'};
        return `<span class="badge badge-light-${m[st]||'secondary'} text-uppercase">${esc(st)}</span>`;
    };

    function bnbBadge(v) {
        if (v == null || v === '') return '<span class="text-muted">—</span>';
        return `<span class="bnb-badge"><svg width="10" height="10" viewBox="0 0 32 32" fill="currentColor" style="margin-right:1px"><path d="M16 0l3.9 3.9-9.9 9.9L6.1 9.9 16 0z"/><path d="M25.9 9.9l3.9 3.9-3.9 3.9-3.9-3.9 3.9-3.9z"/><path d="M6.1 9.9L2.2 13.8l3.9 3.9 3.9-3.9-3.9-3.9z"/><path d="M16 0L6.1 9.9l3.9 3.9L16 7.8l5.9 5.9 3.9-3.9L16 0z"/><path d="M12.1 13.8L16 17.7l3.9-3.9-3.9-3.9-3.9 3.9z"/><path d="M16 24.2l-9.9-9.9-3.9 3.9L16 32l13.8-13.8-3.9-3.9-9.9 9.9z"/></svg>${fmtBnb(v)}</span>`;
    }

    function filters() {
        const f = { search: document.getElementById('gf-search').value };
        document.querySelectorAll('[data-gf]').forEach(el => { if (el.value) f[el.dataset.gf] = el.value; });
        return f;
    }

    function buildFormData(p) {
        const fd = new FormData();
        Object.entries(p).forEach(([k,v]) => fd.append(k, v));
        fd.append('page', page); fd.append('limit', limit);
        return fd;
    }

    async function load(p) {
        page = p || 1;
        const body = document.getElementById('gf-body');
        body.innerHTML = '<tr><td colspan="10" class="text-muted py-6 text-center">Loading…</td></tr>';
        let j = {};
        try {
            const res = await fetch(base + 'admin/finance/gas-fee-transactions/list', {
                method: 'POST', body: buildFormData(filters()),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            j = await res.json();
        } catch(e) {
            body.innerHTML = '<tr><td colspan="10" class="text-danger py-6 text-center">Load error.</td></tr>';
            return;
        }
        if (!j.status) { body.innerHTML = `<tr><td colspan="10" class="text-danger py-6 text-center">${esc(j.message||'Error')}</td></tr>`; return; }

        totalPages = j.pages || 1;
        if (!j.rows || !j.rows.length) {
            body.innerHTML = '<tr><td colspan="10" class="text-muted py-6 text-center">No gas fee transactions found.</td></tr>';
        } else {
            body.innerHTML = j.rows.map(r => {
                const txCell = r.tx_hash
                    ? `<a class="gf-mono text-hover-primary" href="${explorer}/tx/${esc(r.tx_hash)}" target="_blank" onclick="event.stopPropagation()">${short(r.tx_hash)}</a>`
                    : '<span class="text-muted fs-8">internal</span>';
                return `<tr class="gf-row" data-row='${JSON.stringify(r).replace(/'/g,"&#39;")}'>
                    <td>${txCell}</td>
                    <td class="text-muted fs-8">${esc((r.created_at||'').slice(0,16))}</td>
                    <td><span class="badge badge-light">#${esc(r.user_id)}</span>${r.username ? ` <span class="text-muted fs-9">${esc(r.username)}</span>` : ''}</td>
                    <td class="fs-8">${esc(r.network||'—')}</td>
                    <td class="fs-8">${esc(r.tx_type||'—')}</td>
                    <td class="text-end gf-mono fs-8">${fmtGasUsed(r.gas_used)}</td>
                    <td class="text-end gf-mono fs-8">${fmtGwei(r.gas_price_gwei)} <span class="text-muted fs-9">Gwei</span></td>
                    <td class="text-end fw-bold">${bnbBadge(r.gas_fee_total)}</td>
                    <td>${statusBadge(r.status)}</td>
                    <td class="text-end gf-mono fs-8">${r.block_number||'—'}</td>
                </tr>`;
            }).join('');

            body.querySelectorAll('.gf-row').forEach(tr => {
                tr.addEventListener('click', () => openDetail(JSON.parse(tr.dataset.row)));
            });
        }

        const start = j.total ? ((page-1)*limit+1) : 0;
        const end   = j.total ? Math.min(page*limit, j.total) : 0;
        document.getElementById('gf-count').textContent = j.total ? `Showing ${start}–${end} of ${j.total.toLocaleString()} gas transaction(s)` : 'No results';
        document.getElementById('gf-page-info').textContent = `Page ${page} / ${totalPages}`;
        document.getElementById('gf-prev').disabled = page <= 1;
        document.getElementById('gf-next').disabled = page >= totalPages;
    }

    function openDetail(r) {
        const m = bootstrap.Modal.getOrCreateInstance(document.getElementById('gf-modal'));
        const body = document.getElementById('gf-modal-body');
        const kv = (k, v) => `<div class="gf-kv"><span class="k">${esc(k)}</span><span class="v">${v ?? '<span class="text-muted">—</span>'}</span></div>`;
        let h = '';
        h += kv('Tx Hash', r.tx_hash ? `<a href="${explorer}/tx/${esc(r.tx_hash)}" target="_blank" class="gf-mono">${esc(r.tx_hash)}</a>` : null);
        h += kv('Date', esc(r.created_at));
        h += kv('User', `#${esc(r.user_id)} ${r.username ? esc(r.username) : ''}`);
        h += kv('Network', esc(r.network));
        h += kv('Type', esc(r.tx_type));
        h += kv('Status', statusBadge(r.status));
        h += kv('Block', esc(r.block_number));
        h += `<div class="fw-bold fs-8 text-uppercase text-muted mt-3 mb-1">Gas Details</div>`;
        h += kv('Gas Used', fmtGasUsed(r.gas_used));
        h += kv('Gas Price', `${fmtGwei(r.gas_price_gwei)} <span class="text-muted fs-9">Gwei</span>`);
        h += kv('Total Gas Fee (BNB)', `<strong>${bnbBadge(r.gas_fee_total)}</strong>`);
        h += `<div class="fw-bold fs-8 text-uppercase text-muted mt-3 mb-1">Transaction</div>`;
        h += kv('Amount', `${esc(r.amount)} ${esc(r.token_symbol||'')}`);
        h += kv('Wallet', esc(r.wallet_type));
        h += kv('From', `<span class="gf-mono">${esc(r.from_address||'—')}</span>`);
        h += kv('To', `<span class="gf-mono">${esc(r.to_address||'—')}</span>`);
        if (r.tx_hash) {
            h += `<div class="d-flex gap-2 mt-3">
                <button class="btn btn-sm btn-light-primary" onclick="navigator.clipboard.writeText('${esc(r.tx_hash)}');this.textContent='Copied'">Copy Hash</button>
                <a class="btn btn-sm btn-light-info" href="${explorer}/tx/${esc(r.tx_hash)}" target="_blank">View on Explorer</a>
            </div>`;
        }
        body.innerHTML = h;
        m.show();
    }

    /* Live Gas Ticker */
    async function loadLiveGas() {
        try {
            const res = await fetch(base + 'admin/finance/gas-fee-transactions/live-gas', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const j = await res.json();
            if (j.status && j.data && j.data.available) {
                const d = j.data;
                document.getElementById('gf-gwei-slow').textContent = d.slow;
                document.getElementById('gf-gwei-std').textContent  = d.standard;
                document.getElementById('gf-gwei-fast').textContent = d.fast;
                // Also update shared widget if on same page
                ['at-gwei-slow','at-gwei-std','at-gwei-fast'].forEach((id, i) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = [d.slow, d.standard, d.fast][i];
                });
            }
        } catch(e) { /* silent - log message stays server-side */ }
    }

    function startGasTicker() {
        loadLiveGas();
        liveGasTimer = setInterval(loadLiveGas, 30000);
    }

    /* Export */
    function buildExportUrl(fmt) {
        const f = filters();
        const p = new URLSearchParams(f);
        return base + 'admin/finance/gas-fee-transactions/export/' + fmt + '?' + p.toString();
    }

    /* Wire controls */
    document.getElementById('gf-filter-toggle').addEventListener('click', () => document.getElementById('gf-filters').classList.toggle('d-none'));
    document.getElementById('gf-apply').addEventListener('click', () => load(1));
    document.getElementById('gf-reset').addEventListener('click', () => {
        document.querySelectorAll('[data-gf]').forEach(e => e.value = '');
        document.getElementById('gf-search').value = '';
        load(1);
    });
    document.getElementById('gf-prev').addEventListener('click', () => { if (page > 1) load(page - 1); });
    document.getElementById('gf-next').addEventListener('click', () => { if (page < totalPages) load(page + 1); });

    let searchT;
    document.getElementById('gf-search').addEventListener('input', () => { clearTimeout(searchT); searchT = setTimeout(() => load(1), 400); });

    document.getElementById('gf-export-csv').addEventListener('click', e => { e.preventDefault(); window.location = buildExportUrl('csv'); });
    document.getElementById('gf-export-excel').addEventListener('click', e => { e.preventDefault(); window.location = buildExportUrl('excel'); });
    document.getElementById('gf-ticker-refresh').addEventListener('click', loadLiveGas);

    startGasTicker();
    load(1);
})();
</script>
</body>
</html>
