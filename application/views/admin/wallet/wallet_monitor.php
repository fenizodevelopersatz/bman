<?php $this->load->view('admin/Layout/common_style'); ?>

<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />

<style>
    .wm-addr { font-family: monospace; font-size: .8rem; }
    .wm-num { font-variant-numeric: tabular-nums; }
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
                            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                                    <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                                        <?php echo $title; ?>
                                    </h1>
                                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                        <li class="breadcrumb-item text-muted"><a href="<?php echo base_url(); ?>" class="text-muted text-hover-primary">Finance</a></li>
                                        <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                        <li class="breadcrumb-item text-muted"><?php echo $title; ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div id="kt_app_content" class="app-content flex-column-fluid mt-10">
                            <div id="kt_app_content_container" class="app-container container-xxl">

                                <?php $this->load->view('notification'); ?>

                                <div class="card mb-5 mb-xxl-8">
                                    <div class="card-header border-transparent pt-5">
                                        <h3 class="card-title fw-bold"><?php echo $card_tilte; ?></h3>
                                        <div class="card-toolbar gap-2">
                                            <button type="button" class="btn btn-light btn-sm" id="wm-log-btn">Monitor Log</button>
                                            <button type="button" class="btn btn-light btn-sm" id="wm-dep-btn">Deposits</button>
                                            <button type="button" class="btn btn-light btn-sm" id="wm-scan-all">Balance Scan</button>
                                            <?php if ($is_super): ?>
                                            <button type="button" class="btn btn-primary btn-sm" id="wm-scan-dep">Detect Deposits (auto)</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="card-body pt-3 pb-9">
                                        <div class="text-muted fs-7 mb-5">
                                            Reads each user's real BEP-20 balance from the active Token Settings RPC and
                                            compares it with our database USDT record. A <b>positive difference</b> means
                                            funds arrived on-chain that we have not credited yet — click <b>Reconcile</b>
                                            to credit the internal USDT balance (logged + recorded as a deposit).
                                            <?php if (!$is_super): ?>
                                                <span class="badge badge-light-danger ms-2">Read only — reconcile is Super-Admin</span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table align-middle table-row-dashed fs-6 gy-4" id="wm-table">
                                                <thead>
                                                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                                        <th>User</th>
                                                        <th>Deposit Address</th>
                                                        <th class="text-end">DB USDT</th>
                                                        <th class="text-end">On-chain</th>
                                                        <th class="text-end">Difference</th>
                                                        <th class="text-end">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="text-gray-700 fw-semibold">
                                                    <?php foreach ($wallets as $w): ?>
                                                    <tr data-user="<?php echo (int)$w['user_id']; ?>" data-addr="<?php echo html_escape($w['wallet_address']); ?>">
                                                        <td>
                                                            <span class="fw-bold">#<?php echo (int)$w['user_id']; ?> <?php echo html_escape($w['username'] ?: ''); ?></span>
                                                            <div class="text-muted fs-8"><?php echo html_escape($w['email'] ?: ''); ?></div>
                                                        </td>
                                                        <td>
                                                            <span class="wm-addr"><?php echo html_escape(substr($w['wallet_address'],0,12)); ?>…<?php echo html_escape(substr($w['wallet_address'],-6)); ?></span>
                                                        </td>
                                                        <td class="text-end wm-num"><?php echo number_format((float)$w['db_usdt'], 4); ?></td>
                                                        <td class="text-end wm-num wm-onchain">—</td>
                                                        <td class="text-end wm-num wm-diff">—</td>
                                                        <td class="text-end">
                                                            <button class="btn btn-sm btn-light-primary wm-check">Check</button>
                                                            <?php if ($is_super): ?>
                                                                <button class="btn btn-sm btn-light-success wm-reconcile d-none">Reconcile</button>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                    <?php if (empty($wallets)): ?>
                                                        <tr><td colspan="6" class="text-muted">No custodial wallets yet.</td></tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal fade" id="wm-dep-modal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered mw-900px">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h3 class="modal-title">Detected Deposits</h3>
                                                <div class="btn btn-sm btn-icon" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></div>
                                            </div>
                                            <div class="modal-body scroll-y mh-500px" id="wm-dep-body">Loading…</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal fade" id="wm-log-modal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered mw-900px">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h3 class="modal-title">Wallet Monitor Log</h3>
                                                <div class="btn btn-sm btn-icon" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></div>
                                            </div>
                                            <div class="modal-body scroll-y mh-500px" id="wm-log-body">Loading…</div>
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
        const isSuper = <?php echo $is_super ? 'true' : 'false'; ?>;

        function toast(msg, ok) {
            if (window.Swal) Swal.fire({ text: msg, icon: ok ? 'success' : 'error', buttonsStyling: false,
                confirmButtonText: 'Ok', customClass: { confirmButton: 'btn btn-primary' } });
            else alert(msg);
        }
        function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
        async function post(url, body){
            const res = await fetch(base + url, { method:'POST', body: body || new FormData(),
                headers:{ 'X-Requested-With':'XMLHttpRequest' } });
            let j={}; try{ j=await res.json(); }catch(e){ j={status:'error',message:'Server error.'}; }
            return { ok: res.ok && j.status==='success', j };
        }

        async function checkRow(tr) {
            const btn = tr.querySelector('.wm-check');
            btn.disabled = true; const old = btn.textContent; btn.textContent = '…';
            const { ok, j } = await post('admin/wallet-monitor/check/' + tr.dataset.user);
            btn.disabled = false; btn.textContent = old;
            if (!ok) { toast(j.message || 'Check failed', false); return; }
            const d = j.data;
            tr.querySelector('.wm-onchain').textContent = d.onchain_usdt;
            const diffCell = tr.querySelector('.wm-diff');
            diffCell.textContent = d.difference;
            const pending = parseFloat(d.difference) > 0;
            diffCell.className = 'text-end wm-num wm-diff ' + (pending ? 'text-warning fw-bold' : 'text-muted');
            const rec = tr.querySelector('.wm-reconcile');
            if (rec) rec.classList.toggle('d-none', !(isSuper && pending));
        }

        document.getElementById('wm-table').addEventListener('click', async (e) => {
            const tr = e.target.closest('tr[data-user]');
            if (!tr) return;
            if (e.target.closest('.wm-check')) checkRow(tr);
            if (e.target.closest('.wm-reconcile')) {
                if (!confirm('Credit the on-chain difference to this user\'s internal USDT balance?')) return;
                const { ok, j } = await post('admin/wallet-monitor/reconcile/' + tr.dataset.user);
                toast(j.message || '', ok);
                if (ok) checkRow(tr);
            }
        });

        document.getElementById('wm-scan-all').addEventListener('click', async () => {
            const rows = Array.from(document.querySelectorAll('#wm-table tr[data-user]'));
            const btn = document.getElementById('wm-scan-all');
            btn.disabled = true; btn.textContent = 'Scanning…';
            for (const tr of rows) { await checkRow(tr); }   // sequential — avoids RPC rate limits
            btn.disabled = false; btn.textContent = 'Scan All (on-chain)';
        });

        const scanDep = document.getElementById('wm-scan-dep');
        if (scanDep) scanDep.addEventListener('click', async () => {
            scanDep.disabled = true; scanDep.textContent = 'Detecting…';
            const { ok, j } = await post('admin/wallet-monitor/scan-deposits');
            scanDep.disabled = false; scanDep.textContent = 'Detect Deposits (auto)';
            toast(j.message || '', ok);
        });

        document.getElementById('wm-dep-btn').addEventListener('click', async () => {
            const m = bootstrap.Modal.getOrCreateInstance(document.getElementById('wm-dep-modal'));
            const body = document.getElementById('wm-dep-body'); body.innerHTML = 'Loading…'; m.show();
            const res = await fetch(base + 'admin/wallet-monitor/deposits', { headers:{ 'X-Requested-With':'XMLHttpRequest' } });
            const j = await res.json();
            const rows = (j.rows||[]).map(r =>
                '<tr><td>#'+esc(r.user_id)+'</td>'+
                '<td class="wm-addr">'+esc(String(r.tx_hash).slice(0,16))+'…</td>'+
                '<td class="text-end">'+esc(r.amount_usdt)+' USDT</td>'+
                '<td class="text-end">'+esc(r.amount_bman)+' BMAN</td>'+
                '<td class="text-center">'+esc(r.confirmations)+'</td>'+
                '<td><span class="badge '+(r.status==='credited'?'badge-light-success':(r.status==='failed'||r.status==='expired'?'badge-light-danger':'badge-light-warning'))+'">'+esc(String(r.status).toUpperCase())+'</span></td>'+
                '<td class="text-muted fs-8">'+esc(r.created_at)+'</td></tr>').join('');
            body.innerHTML = rows
                ? '<table class="table table-row-dashed fs-7"><thead><tr class="fw-bold text-muted"><th>User</th><th>Tx</th><th class="text-end">USDT</th><th class="text-end">BMAN</th><th class="text-center">Confs</th><th>Status</th><th>When</th></tr></thead><tbody>'+rows+'</tbody></table>'
                : '<div class="text-muted">No deposits detected yet. Set a BscScan API key in Token Settings, then Detect Deposits.</div>';
        });

        document.getElementById('wm-log-btn').addEventListener('click', async () => {
            const m = bootstrap.Modal.getOrCreateInstance(document.getElementById('wm-log-modal'));
            const body = document.getElementById('wm-log-body'); body.innerHTML = 'Loading…'; m.show();
            const res = await fetch(base + 'admin/wallet-monitor/log', { headers:{ 'X-Requested-With':'XMLHttpRequest' } });
            const j = await res.json();
            const rows = (j.rows||[]).map(r =>
                '<tr><td>#'+esc(r.user_id)+'</td>'+
                '<td><span class="badge '+(r.action==='reconcile'?'badge-light-success':'badge-light')+'">'+esc(r.action.toUpperCase())+'</span></td>'+
                '<td class="text-end">'+esc(r.onchain_balance)+'</td><td class="text-end">'+esc(r.db_balance)+'</td>'+
                '<td class="text-end">'+esc(r.difference)+'</td><td class="text-muted fs-8">'+esc(r.created_at)+'</td></tr>').join('');
            body.innerHTML = rows
                ? '<table class="table table-row-dashed fs-7"><thead><tr class="fw-bold text-muted"><th>User</th><th>Action</th><th class="text-end">On-chain</th><th class="text-end">DB</th><th class="text-end">Diff</th><th>When</th></tr></thead><tbody>'+rows+'</tbody></table>'
                : '<div class="text-muted">No monitor activity yet.</div>';
        });
    })();
    </script>
</body>

</html>
