<?php $this->load->view('admin/Layout/common_style'); ?>
<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
<style>
    .oc-mono { font-family: monospace; font-size: .8rem; }
    .oc-num { font-variant-numeric: tabular-nums; }
    .oc-row { cursor: pointer; }
    .oc-row:hover { background: var(--bs-gray-100); }
    .oc-kv { display:flex; justify-content:space-between; gap:1rem; padding:.35rem 0; border-bottom:1px dashed var(--bs-gray-200); }
    .oc-kv .k { color: var(--bs-gray-600); font-weight:600; font-size:.8rem; }
    .oc-kv .v { text-align:right; word-break:break-all; font-size:.82rem; }
    .oc-sec-title { font-weight:700; font-size:.85rem; text-transform:uppercase; letter-spacing:.03em; color:var(--bs-gray-700); margin:1.2rem 0 .4rem; }
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
            <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                <div class="app-container container-xxl d-flex flex-stack">
                    <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                        <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0"><?php echo $title; ?></h1>
                        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                            <li class="breadcrumb-item text-muted"><a href="<?php echo base_url(); ?>" class="text-muted text-hover-primary">Finance</a></li>
                            <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                            <li class="breadcrumb-item text-muted"><?php echo $title; ?></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div id="kt_app_content" class="app-content flex-column-fluid mt-8">
            <div class="app-container container-xxl">
                <?php $this->load->view('notification'); ?>

                <!-- 5 wallet balance cards -->
                <div class="row g-4 mb-6">
                    <?php
                        $cards = [
                            ['USDT Wallet','usdt','#50cd89'], ['Exchange Wallet','exchange','#7239ea'],
                            ['Earning Wallet','earning','#009ef7'], ['Staking Wallet','staking','#f1416c'],
                            ['Bonus Wallet','bonus','#ffc700'],
                        ];
                        foreach ($cards as $c):
                    ?>
                    <div class="col">
                        <div class="card card-flush h-100" style="border-top:3px solid <?php echo $c[2]; ?>;">
                            <div class="card-body py-4">
                                <span class="text-muted fw-semibold fs-8 d-block text-uppercase"><?php echo $c[0]; ?></span>
                                <span class="fs-2 fw-bold text-gray-900 oc-num"><?php echo number_format((float)$balances[$c[1]], 4); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="card">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title">
                            <div class="d-flex align-items-center position-relative my-1">
                                <input type="text" id="oc-search" class="form-control form-control-solid w-300px ps-5"
                                       placeholder="Search tx hash, address, user ID, block, reference…" />
                            </div>
                        </div>
                        <div class="card-toolbar gap-2">
                            <button class="btn btn-sm btn-light-primary" id="oc-filter-toggle">Filters</button>
                            <button class="btn btn-sm btn-light" id="oc-reset">Reset</button>
                        </div>
                    </div>

                    <!-- filter bar -->
                    <div class="card-body border-top d-none" id="oc-filters">
                        <div class="row g-4">
                            <div class="col-md-3"><label class="form-label fs-8 text-muted">Wallet</label>
                                <select class="form-select form-select-sm" data-f="wallet"><option value="">All</option>
                                    <?php foreach ($options['wallets'] as $w): ?><option value="<?php echo $w; ?>"><?php echo ucfirst($w); ?></option><?php endforeach; ?>
                                </select></div>
                            <div class="col-md-3"><label class="form-label fs-8 text-muted">Network</label>
                                <select class="form-select form-select-sm" data-f="network"><option value="">All</option>
                                    <?php foreach ($options['networks'] as $n): ?><option value="<?php echo html_escape($n); ?>"><?php echo html_escape($n); ?></option><?php endforeach; ?>
                                </select></div>
                            <div class="col-md-3"><label class="form-label fs-8 text-muted">Status</label>
                                <select class="form-select form-select-sm" data-f="status"><option value="">All</option>
                                    <?php foreach ($options['statuses'] as $s): ?><option value="<?php echo $s; ?>"><?php echo ucfirst($s); ?></option><?php endforeach; ?>
                                </select></div>
                            <div class="col-md-3"><label class="form-label fs-8 text-muted">Type</label>
                                <select class="form-select form-select-sm" data-f="tx_type"><option value="">All</option>
                                    <?php foreach ($options['types'] as $t): ?><option value="<?php echo html_escape($t); ?>"><?php echo html_escape($t); ?></option><?php endforeach; ?>
                                </select></div>
                            <div class="col-md-3"><label class="form-label fs-8 text-muted">Token</label>
                                <select class="form-select form-select-sm" data-f="token"><option value="">All</option>
                                    <?php foreach ($options['tokens'] as $t): ?><option value="<?php echo html_escape($t); ?>"><?php echo html_escape($t); ?></option><?php endforeach; ?>
                                </select></div>
                            <div class="col-md-3"><label class="form-label fs-8 text-muted">Date from</label>
                                <input type="date" class="form-control form-control-sm" data-f="date_from"></div>
                            <div class="col-md-3"><label class="form-label fs-8 text-muted">Date to</label>
                                <input type="date" class="form-control form-control-sm" data-f="date_to"></div>
                            <div class="col-md-3"><label class="form-label fs-8 text-muted">Block number</label>
                                <input type="number" class="form-control form-control-sm" data-f="block_number"></div>
                            <div class="col-md-3"><label class="form-label fs-8 text-muted">Tx hash</label>
                                <input type="text" class="form-control form-control-sm" data-f="tx_hash"></div>
                            <div class="col-md-3"><label class="form-label fs-8 text-muted">Wallet address</label>
                                <input type="text" class="form-control form-control-sm" data-f="wallet_address"></div>
                            <div class="col-md-3"><label class="form-label fs-8 text-muted">User ID</label>
                                <input type="number" class="form-control form-control-sm" data-f="user_id"></div>
                            <div class="col-md-3"><label class="form-label fs-8 text-muted">Reference ID</label>
                                <input type="text" class="form-control form-control-sm" data-f="reference_id"></div>
                            <div class="col-md-3"><label class="form-label fs-8 text-muted">Gas fee min (BNB)</label>
                                <input type="number" step="any" class="form-control form-control-sm" data-f="gas_min"></div>
                            <div class="col-md-3"><label class="form-label fs-8 text-muted">Gas fee max (BNB)</label>
                                <input type="number" step="any" class="form-control form-control-sm" data-f="gas_max"></div>
                            <div class="col-md-3 d-flex align-items-end"><button class="btn btn-sm btn-primary w-100" id="oc-apply">Apply filters</button></div>
                        </div>
                    </div>

                    <div class="card-body pt-2">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-7 gy-3">
                                <thead>
                                    <tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                                        <th>Tx Hash</th><th>Date</th><th>Wallet</th><th>From</th><th>To</th>
                                        <th>Type</th><th class="text-end">Amount</th><th>Token</th><th>Network</th>
                                        <th class="text-end">Block</th><th class="text-end">Conf</th>
                                        <th class="text-end">Gas Fee</th><th class="text-center">Status</th><th></th>
                                    </tr>
                                </thead>
                                <tbody id="oc-body" class="text-gray-700 fw-semibold">
                                    <tr><td colspan="14" class="text-muted py-6 text-center">Loading…</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <span class="text-muted fs-8" id="oc-count"></span>
                            <div class="d-flex gap-2 align-items-center">
                                <button class="btn btn-sm btn-light" id="oc-prev">Prev</button>
                                <span class="fs-8 text-muted" id="oc-page"></span>
                                <button class="btn btn-sm btn-light" id="oc-next">Next</button>
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

<!-- detail modal -->
<div class="modal fade" id="oc-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-900px modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-4">
                <h3 class="modal-title fs-5">Transaction Detail</h3>
                <div class="d-flex gap-2 align-items-center">
                    <span id="oc-m-status"></span>
                    <div class="btn btn-sm btn-icon" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></div>
                </div>
            </div>
            <div class="modal-body" id="oc-modal-body">Loading…</div>
        </div>
    </div>
</div>

<div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true"><i class="ki-duotone ki-arrow-up"><span class="path1"></span><span class="path2"></span></i></div>
<?php $this->load->view('admin/Layout/common_script'); ?>
<script src="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.js"></script>
<script>
(function () {
    const base = '<?php echo base_url(); ?>';
    let explorer = '<?php echo $explorer_url; ?>';
    let page = 1, limit = 25, total = 0;

    const esc = s => String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const short = (s,n=10) => s ? (s.length>n+6 ? esc(s.slice(0,n))+'…'+esc(s.slice(-4)) : esc(s)) : '<span class="text-muted">—</span>';
    const badge = st => {
        const m = {confirmed:'success',pending:'warning',processing:'info',failed:'danger',reverted:'danger',partial:'warning',cancelled:'secondary'};
        return '<span class="badge badge-light-'+(m[st]||'secondary')+' text-uppercase">'+esc(st)+'</span>';
    };
    const filters = () => {
        const f = { search: document.getElementById('oc-search').value };
        document.querySelectorAll('[data-f]').forEach(el => { if (el.value) f[el.dataset.f] = el.value; });
        return f;
    };

    async function load(p) {
        page = p || 1;
        const body = document.getElementById('oc-body');
        body.innerHTML = '<tr><td colspan="14" class="text-muted py-6 text-center">Loading…</td></tr>';
        const fd = new FormData();
        Object.entries(filters()).forEach(([k,v]) => fd.append(k,v));
        fd.append('page', page); fd.append('limit', limit);
        let j = {};
        try {
            const res = await fetch(base+'admin/wallet/onchain-transactions/list', { method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'} });
            j = await res.json();
        } catch(e) { body.innerHTML='<tr><td colspan="14" class="text-danger py-6 text-center">Load error.</td></tr>'; return; }
        if (j.status!=='success') { body.innerHTML='<tr><td colspan="14" class="text-danger py-6 text-center">'+esc(j.message||'Error')+'</td></tr>'; return; }
        explorer = j.explorer || explorer; total = j.total;

        if (!j.rows.length) { body.innerHTML='<tr><td colspan="14" class="text-muted py-6 text-center">No transactions match.</td></tr>'; }
        else body.innerHTML = j.rows.map(r => {
            const txCell = r.tx_hash
                ? '<a class="oc-mono text-hover-primary" href="'+explorer+'/tx/'+esc(r.tx_hash)+'" target="_blank" onclick="event.stopPropagation()">'+short(r.tx_hash)+'</a>'
                : '<span class="text-muted fs-8">internal</span>';
            const amt = Number(r.amount||0).toLocaleString(undefined,{maximumFractionDigits:8});
            const gas = r.gas_fee_total!=null ? Number(r.gas_fee_total).toFixed(6) : '—';
            return '<tr class="oc-row" data-id="'+r.id+'">'+
                '<td>'+txCell+'</td>'+
                '<td class="text-muted fs-8">'+esc((r.created_at||'').slice(0,16))+'</td>'+
                '<td><span class="badge badge-light">'+esc(r.wallet_type||'—')+'</span></td>'+
                '<td class="oc-mono">'+short(r.from_address,8)+'</td>'+
                '<td class="oc-mono">'+short(r.to_address,8)+'</td>'+
                '<td class="fs-8">'+esc(r.tx_type||'')+'</td>'+
                '<td class="text-end oc-num fw-bold">'+amt+'</td>'+
                '<td>'+esc(r.token_symbol||'—')+'</td>'+
                '<td class="fs-8">'+esc(r.network||'')+'</td>'+
                '<td class="text-end oc-num fs-8">'+(r.block_number||'—')+'</td>'+
                '<td class="text-end oc-num fs-8">'+(r.confirmation_count!=null?r.confirmation_count:'—')+'</td>'+
                '<td class="text-end oc-num fs-8">'+gas+'</td>'+
                '<td class="text-center">'+badge(r.status)+'</td>'+
                '<td class="text-end"><i class="ki-outline ki-eye fs-4 text-muted"></i></td></tr>';
        }).join('');

        document.getElementById('oc-count').textContent = total.toLocaleString()+' transaction(s)';
        document.getElementById('oc-page').textContent = 'Page '+page+' / '+(j.pages||1);
        document.getElementById('oc-prev').disabled = page<=1;
        document.getElementById('oc-next').disabled = page>=(j.pages||1);
        body.querySelectorAll('.oc-row').forEach(tr => tr.addEventListener('click', () => openDetail(tr.dataset.id)));
    }

    const kv = (k,v) => '<div class="oc-kv"><span class="k">'+esc(k)+'</span><span class="v">'+(v==null||v===''?'<span class="text-muted">—</span>':v)+'</span></div>';
    const sec = t => '<div class="oc-sec-title">'+esc(t)+'</div>';

    async function openDetail(id) {
        const m = bootstrap.Modal.getOrCreateInstance(document.getElementById('oc-modal'));
        const body = document.getElementById('oc-modal-body');
        document.getElementById('oc-m-status').innerHTML = '';
        body.innerHTML = 'Loading on-chain details…'; m.show();
        let j = {};
        try {
            const res = await fetch(base+'admin/wallet/onchain-transactions/detail?id='+encodeURIComponent(id), { headers:{'X-Requested-With':'XMLHttpRequest'} });
            j = await res.json();
        } catch(e) { body.innerHTML='<div class="text-danger">Failed to load.</div>'; return; }
        if (j.status!=='success') { body.innerHTML='<div class="text-danger">'+esc(j.message||'Error')+'</div>'; return; }
        const t = j.tx, c = j.chain||{}, ex = j.explorer||explorer;
        document.getElementById('oc-m-status').innerHTML = badge(t.status);
        const txLink = t.tx_hash ? '<a href="'+ex+'/tx/'+esc(t.tx_hash)+'" target="_blank" class="oc-mono">'+esc(t.tx_hash)+'</a>' : '<span class="text-muted">internal (no hash)</span>';
        const av = c.available;

        let h = '';
        h += sec('General');
        h += kv('Transaction Hash', txLink);
        h += kv('Network', esc(t.network)+' (chain '+esc(t.chain_id)+')');
        h += kv('Status', badge(t.status)+(av && c.chain_status?(' <span class="text-muted fs-8">chain: '+esc(c.chain_status)+'</span>'):''));
        h += kv('Block Number', esc(t.block_number||c.block_number));
        h += kv('Confirmations', esc(t.confirmation_count!=null?t.confirmation_count:c.confirmation_count));
        h += kv('Nonce', esc(c.nonce));
        h += kv('Transaction Index', esc(c.tx_index));
        h += kv('Explorer', t.tx_hash?'<a href="'+ex+'/tx/'+esc(t.tx_hash)+'" target="_blank">Open on explorer ↗</a>':null);

        h += sec('Wallet');
        h += kv('Wallet Type', esc(t.wallet_type));
        h += kv('From Address', t.from_address?('<span class="oc-mono">'+esc(t.from_address)+'</span>'):esc(c.from));
        h += kv('To Address', t.to_address?('<span class="oc-mono">'+esc(t.to_address)+'</span>'):esc(c.to));
        h += kv('Related User', t.user_id?('#'+esc(t.user_id)+' '+esc(t.username||'')):null);
        h += kv('Related Admin', esc(t.admin_id));

        h += sec('Token');
        h += kv('Token Name', esc(t.token_name));
        h += kv('Token Symbol', esc(t.token_symbol));
        h += kv('Contract Address', t.token_contract?('<span class="oc-mono">'+esc(t.token_contract)+'</span>'):null);
        h += kv('Token Amount', esc(t.amount));
        h += kv('Token Decimals', esc(t.token_decimals));

        h += sec('Gas');
        h += kv('Gas Limit', esc(c.gas_limit||t.gas_limit));
        h += kv('Gas Used', esc(c.gas_used||t.gas_used));
        h += kv('Gas Price (gwei)', esc(c.gas_price_gwei));
        h += kv('Max Fee / Gas', esc(c.max_fee_per_gas||t.max_fee_per_gas));
        h += kv('Max Priority Fee', esc(c.max_priority_fee||t.max_priority_fee));
        h += kv('Total Gas Fee (BNB)', esc(c.gas_fee_bnb||t.gas_fee_total));
        h += kv('Native Coin Value', c.value_wei!=null?esc(c.value_wei)+' wei':esc(t.native_used));

        h += sec('Execution');
        h += kv('Contract Address', (t.contract_address||c.contract_address)?('<span class="oc-mono">'+esc(t.contract_address||c.contract_address)+'</span>'):null);
        h += kv('Method Signature', esc(t.method_signature||c.method_signature));
        h += kv('Input Selector', esc(c.input_selector));
        h += kv('Event Logs', av?(esc(c.logs_count)+' log(s)'):null);
        h += kv('Internal Transactions', '<span class="text-muted fs-8">'+esc(c.needs||'requires explorer API / trace node')+'</span>');
        h += kv('Execution Trace', '<span class="text-muted fs-8">requires debug/trace node</span>');

        h += sec('Ledger');
        h += kv('Debit Wallet', esc(t.debit_wallet));
        h += kv('Credit Wallet', esc(t.credit_wallet));
        h += kv('Balance Before', esc(t.balance_before));
        h += kv('Balance After', esc(t.balance_after));
        h += kv('Wallet Ledger ID', esc(t.wallet_ledger_id));

        if (t.status==='failed' || t.status==='reverted' || t.failure_reason) {
            h += sec('Failure Analysis');
            h += kv('Failure Reason', esc(t.failure_reason));
            h += kv('Revert Message', esc(t.revert_message));
            h += kv('Chain Status Raw', esc(c.return_status_raw));
        }
        if (t.status==='partial') {
            h += sec('Partial Completion');
            h += kv('Completed Steps', esc(t.completed_steps));
            h += kv('Failed Steps', esc(t.failed_steps));
            h += kv('Retry Status', esc(t.retry_status));
            h += kv('Linked Retry Tx', esc(t.linked_retry_tx_id));
        }

        h += sec('Related');
        h += kv('Reference', esc(t.reference_type)+(t.reference_id?(' / '+esc(t.reference_id)):''));
        h += kv('Linked Deposit', esc(t.linked_deposit_id));
        h += kv('Linked Withdrawal', esc(t.linked_withdrawal_id));
        h += kv('Parent Transaction', esc(t.parent_tx_id));

        h += sec('Audit');
        h += kv('Created By', esc(t.created_by));
        h += kv('Created At', esc(t.created_at));
        h += kv('Last Updated', esc(t.updated_at));
        h += kv('IP Address', esc(t.ip_address));
        h += kv('Processing Server', esc(t.processing_server));
        h += kv('Processing Time (ms)', esc(t.processing_ms));
        h += kv('Retry Count', esc(t.retry_count));

        h += sec('Actions');
        h += '<div class="d-flex flex-wrap gap-2 mt-2">'+
            (t.tx_hash?'<button class="btn btn-sm btn-light-primary" data-copy="'+esc(t.tx_hash)+'">Copy Tx Hash</button>':'')+
            (t.from_address?'<button class="btn btn-sm btn-light" data-copy="'+esc(t.from_address)+'">Copy From</button>':'')+
            (t.to_address?'<button class="btn btn-sm btn-light" data-copy="'+esc(t.to_address)+'">Copy To</button>':'')+
            (t.tx_hash?'<a class="btn btn-sm btn-light-info" href="'+ex+'/tx/'+esc(t.tx_hash)+'" target="_blank">View on Explorer</a>':'')+
            (t.user_id?'<a class="btn btn-sm btn-light" href="'+base+'view-user/'+esc(t.user_id)+'" target="_blank">View User</a>':'')+
            '<a class="btn btn-sm btn-light" href="'+base+'admin/wallet/onchain-transactions/receipt/'+esc(t.id)+'" target="_blank">Download Receipt</a>'+
            '</div>';
        if (!av) h += '<div class="text-muted fs-8 mt-3">Live chain enrichment unavailable: '+esc(c.reason||'')+'</div>';

        body.innerHTML = h;
        body.querySelectorAll('[data-copy]').forEach(b => b.addEventListener('click', () => {
            navigator.clipboard.writeText(b.dataset.copy); b.textContent='Copied'; setTimeout(()=>{ b.textContent=b.textContent; },900);
        }));
    }

    // wire controls
    document.getElementById('oc-filter-toggle').addEventListener('click', () => document.getElementById('oc-filters').classList.toggle('d-none'));
    document.getElementById('oc-apply').addEventListener('click', () => load(1));
    document.getElementById('oc-reset').addEventListener('click', () => { document.querySelectorAll('[data-f]').forEach(e=>e.value=''); document.getElementById('oc-search').value=''; load(1); });
    document.getElementById('oc-prev').addEventListener('click', () => load(page-1));
    document.getElementById('oc-next').addEventListener('click', () => load(page+1));
    let searchT; document.getElementById('oc-search').addEventListener('input', () => { clearTimeout(searchT); searchT=setTimeout(()=>load(1),400); });

    load(1);
})();
</script>
</body>
</html>
