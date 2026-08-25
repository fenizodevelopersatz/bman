<?php $this->load->view('admin/Layout/common_style'); ?>

<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />

<style>
    .aw-addr { font-family: monospace; font-size: .8rem; }
    .aw-num { font-variant-numeric: tabular-nums; }
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

                                <?php
                                    $enabled  = (int)($settings['reduction_enabled'] ?? 0);
                                    $interval = (int)($settings['reduction_interval_days'] ?? 60);
                                    $percent  = (float)($settings['reduction_percent'] ?? 50);
                                    $dry      = (int)($settings['reduction_dry_run'] ?? 1);
                                    $onchain  = (int)($settings['reduction_onchain'] ?? 0);
                                ?>

                                <!-- Summary cards -->
                                <div class="row g-5 g-xl-8 mb-5">
                                    <div class="col-sm-6 col-xl-3">
                                        <div class="card card-flush h-100">
                                            <div class="card-body">
                                                <span class="text-muted fw-semibold fs-7 d-block">Admin Bonus Wallet</span>
                                                <span class="fs-2hx fw-bold text-gray-900 aw-num"><?php echo number_format((float)$wallet['balance'], 4); ?></span>
                                                <span class="text-muted fs-8 ms-1">BMAN</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-xl-3">
                                        <div class="card card-flush h-100">
                                            <div class="card-body">
                                                <span class="text-muted fw-semibold fs-7 d-block">Lifetime Reclaimed</span>
                                                <span class="fs-2hx fw-bold text-gray-900 aw-num"><?php echo number_format((float)$wallet['lifetime_bonus_reduction_total'], 4); ?></span>
                                                <span class="text-muted fs-8 ms-1">BMAN</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-xl-3">
                                        <div class="card card-flush h-100">
                                            <div class="card-body">
                                                <span class="text-muted fw-semibold fs-7 d-block">Total Reductions</span>
                                                <span class="fs-2hx fw-bold text-gray-900 aw-num"><?php echo (int)$totals['cnt']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-xl-3">
                                        <div class="card card-flush h-100">
                                            <div class="card-body">
                                                <span class="text-muted fw-semibold fs-7 d-block">On-chain sent / failed</span>
                                                <span class="fs-2hx fw-bold text-gray-900 aw-num"><?php echo (int)$totals['onchain_sent']; ?></span>
                                                <span class="text-danger fs-4 ms-1">/ <?php echo (int)$totals['onchain_failed']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card mb-5 mb-xxl-8">
                                    <div class="card-header border-transparent pt-5">
                                        <h3 class="card-title fw-bold"><?php echo $card_tilte; ?></h3>
                                        <div class="card-toolbar gap-2">
                                            <?php if ($is_super): ?>
                                            <button type="button" class="btn btn-primary btn-sm" id="aw-run">Run Reduction Now</button>
                                            <?php if ((int) $retryable_failed_count > 0): ?>
                                            <button type="button" class="btn btn-warning btn-sm" id="aw-retry-all">Retry All Failed (<?php echo (int) $retryable_failed_count; ?>)</button>
                                            <?php endif; ?>
                                            <?php else: ?>
                                            <span class="badge badge-light-danger">Read only — running is Super-Admin</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="card-body pt-3 pb-6">
                                        <!-- settings / status -->
                                        <div class="d-flex flex-wrap gap-2 mb-5">
                                            <span class="badge <?php echo $enabled ? 'badge-light-success' : 'badge-light-danger'; ?>">Reduction: <?php echo $enabled ? 'ENABLED' : 'DISABLED'; ?></span>
                                            <span class="badge badge-light-primary">Every <?php echo $interval; ?> day<?php echo $interval == 1 ? ' (testing)' : 's'; ?></span>
                                            <span class="badge badge-light-primary"><?php echo rtrim(rtrim(number_format($percent, 2), '0'), '.'); ?>% each cycle</span>
                                            <span class="badge <?php echo $dry ? 'badge-light-warning' : 'badge-light-success'; ?>">Mode: <?php echo $dry ? 'DRY-RUN (preview)' : 'EXECUTE'; ?></span>
                                            <?php if ($is_super): ?>
                                            <button type="button" id="aw-toggle-onchain"
                                                class="badge border-0 <?php echo $onchain ? 'badge-light-info' : 'badge-light'; ?>"
                                                style="cursor:pointer;" data-onchain="<?php echo $onchain ? 1 : 0; ?>"
                                                title="Click to switch on-chain sending <?php echo $onchain ? 'off' : 'on'; ?>">
                                                On-chain: <?php echo $onchain ? 'ON' : 'OFF'; ?> ⇄
                                            </button>
                                            <?php else: ?>
                                            <span class="badge <?php echo $onchain ? 'badge-light-info' : 'badge-light'; ?>">On-chain: <?php echo $onchain ? 'ON' : 'OFF'; ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($admin_addr)): ?>
                                            <a class="badge badge-light text-hover-primary" target="_blank" rel="noopener" href="<?php echo html_escape($explorer_url . '/address/' . $admin_addr); ?>">Admin wallet: <span class="aw-addr"><?php echo html_escape(substr($admin_addr, 0, 10)); ?>…<?php echo html_escape(substr($admin_addr, -6)); ?></span></a>
                                            <?php else: ?>
                                            <span class="badge badge-light-danger">No admin wallet set (Token Settings)</span>
                                            <?php endif; ?>
                                        </div>

                                        <div id="aw-result" class="alert alert-light d-none mb-5"></div>

                                        <div class="text-muted fs-7 mb-5">
                                            Every cycle, <?php echo rtrim(rtrim(number_format($percent, 2), '0'), '.'); ?>% of each user's
                                            Bonus Wallet is reduced and credited here. Schedule is per user, anchored on their
                                            <code>register_date</code>. When On-chain is ON, the reclaimed BMAN is also sent from the
                                            user's custodial address to the admin wallet (gas in BNB).
                                        </div>

                                        <!-- history -->
                                        <div class="table-responsive">
                                            <table class="table align-middle table-row-dashed fs-6 gy-3">
                                                <thead>
                                                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                                        <th>User</th>
                                                        <th class="text-center">Cycle</th>
                                                        <th class="text-end">Bonus before</th>
                                                        <th class="text-end">Reduced</th>
                                                        <th class="text-center">%</th>
                                                        <th class="text-center">Status</th>
                                                        <th>Tx</th>
                                                        <th>When</th>
                                                        <?php if ($is_super): ?><th class="text-end">Action</th><?php endif; ?>
                                                    </tr>
                                                </thead>
                                                <tbody class="text-gray-700 fw-semibold">
                                                    <?php foreach ($history as $h): ?>
                                                    <tr data-log-id="<?php echo (int) $h['id']; ?>">
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <img src="<?php echo html_escape($h['profile_photo']); ?>" alt="" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;">
                                                                <div>
                                                                    <span class="fw-bold d-block">#<?php echo (int)$h['user_id']; ?> <?php echo html_escape($h['display_name'] ?: ''); ?></span>
                                                                    <div class="text-muted fs-8"><?php echo html_escape($h['email'] ?: ''); ?></div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-center"><?php echo (int)$h['cycle_no']; ?></td>
                                                        <td class="text-end aw-num"><?php echo number_format((float)$h['bonus_before'], 6); ?></td>
                                                        <td class="text-end aw-num fw-bold"><?php echo number_format((float)$h['amount'], 6); ?></td>
                                                        <td class="text-center"><?php echo rtrim(rtrim(number_format((float)$h['reduce_percent'], 2), '0'), '.'); ?></td>
                                                        <td class="text-center">
                                                            <?php
                                                                $st = $h['status'];
                                                                $cls = $st === 'sent' ? 'badge-light-success' : ($st === 'failed' ? 'badge-light-danger' : 'badge-light');
                                                                // 'sent'/'failed' both mean a real on-chain send was
                                                                // attempted (succeeded or not); 'internal' means one
                                                                // never was (on-chain was OFF for that cycle) — label
                                                                // each accordingly instead of one ambiguous word for
                                                                // both "attempted" and "never attempted".
                                                                $stLabel = $st === 'internal' ? 'Off-chain (Internal)' : ('On-chain: ' . strtoupper($st));
                                                            ?>
                                                            <span class="badge <?php echo $cls; ?>"><?php echo html_escape($stLabel); ?></span>
                                                            <?php if (!empty($h['reverted_at'])): ?>
                                                                <div class="mt-1"><span class="badge badge-light-info">Off-chain: RETURNED</span></div>
                                                            <?php endif; ?>
                                                            <?php if ($st === 'failed' && !empty($h['note'])): ?>
                                                                <div class="text-muted fs-9 mt-1" style="max-width:220px;white-space:normal;line-height:1.3;"><?php echo html_escape($h['note']); ?></div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($h['tx_hash'])): ?>
                                                                <a class="aw-addr text-hover-primary" target="_blank" href="<?php echo $explorer_url; ?>/tx/<?php echo html_escape($h['tx_hash']); ?>"><?php echo html_escape(substr($h['tx_hash'], 0, 10)); ?>…</a>
                                                            <?php else: ?>
                                                                <span class="text-muted fs-8">—</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-muted fs-8"><?php echo html_escape($h['created_at']); ?></td>
                                                        <?php if ($is_super): ?>
                                                        <td class="text-end">
                                                            <?php if (empty($h['reverted_at']) && $h['status'] !== 'sent'): ?>
                                                                <div class="d-flex gap-1 justify-content-end flex-wrap">
                                                                    <?php if ($h['status'] === 'failed'): ?>
                                                                        <button type="button" class="btn btn-light-warning btn-sm aw-retry-row" data-id="<?php echo (int) $h['id']; ?>">Retry</button>
                                                                    <?php endif; ?>
                                                                    <button type="button" class="btn btn-light-info btn-sm aw-return-row" data-id="<?php echo (int) $h['id']; ?>" data-amount="<?php echo html_escape(number_format((float) $h['amount'], 6)); ?>" data-user="<?php echo html_escape($h['display_name'] ?: ('#' . (int) $h['user_id'])); ?>">Return</button>
                                                                </div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <?php endif; ?>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                    <?php if (empty($history)): ?>
                                                        <tr><td colspan="<?php echo $is_super ? 9 : 8; ?>" class="text-muted">No reductions yet. Use <b>Preview</b> to see who is due, then <b>Run Reduction Now</b>.</td></tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <?php if ($pages > 1): ?>
                                        <div class="d-flex justify-content-between align-items-center mt-4">
                                            <div class="text-muted fs-7">Page <?php echo (int) $page; ?> of <?php echo (int) $pages; ?> (<?php echo (int) $history_total; ?> total)</div>
                                            <div class="d-flex gap-2">
                                                <?php if ($page > 1): ?>
                                                    <a class="btn btn-light btn-sm" href="?page=<?php echo $page - 1; ?>"><i class="ki-outline ki-left"></i> Prev</a>
                                                <?php else: ?>
                                                    <span class="btn btn-light btn-sm disabled" style="opacity:.5;">Prev</span>
                                                <?php endif; ?>
                                                <?php if ($page < $pages): ?>
                                                    <a class="btn btn-light btn-sm" href="?page=<?php echo $page + 1; ?>">Next <i class="ki-outline ki-right"></i></a>
                                                <?php else: ?>
                                                    <span class="btn btn-light btn-sm disabled" style="opacity:.5;">Next</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
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

        function toast(msg, ok) {
            if (window.Swal) Swal.fire({ text: msg, icon: ok ? 'success' : 'error', buttonsStyling: false,
                confirmButtonText: 'Ok', customClass: { confirmButton: 'btn btn-primary' } });
            else alert(msg);
        }
        function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}

        async function callRun(dry) {
            const box = document.getElementById('aw-result');
            box.classList.remove('d-none'); box.className = 'alert alert-light mb-5';
            box.innerHTML = 'Working…';
            const fd = new FormData(); fd.append('dry', dry ? '1' : '0');
            let j = {};
            try {
                const res = await fetch(base + 'admin/wallet/admin-wallet/run', {
                    method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                j = await res.json();
            } catch (e) { j = { status: 'error', message: 'Server error.' }; }

            if (j.status !== 'success') { box.className = 'alert alert-danger mb-5'; box.textContent = j.message || 'Failed.'; return; }

            if (dry) {
                const list = (j.preview || []).map(p =>
                    '<tr><td>#'+esc(p.user_id)+'</td><td class="text-center">'+esc(p.cycle_no)+'</td>'+
                    '<td class="text-end">'+esc(p.bonus_before)+'</td><td class="text-end fw-bold">'+esc(p.would_reduce)+'</td>'+
                    '<td class="text-end text-muted fs-8">'+esc(p.days_since_anchor)+'d since '+esc(p.anchor)+'</td></tr>').join('');
                box.className = 'alert alert-light-primary mb-5';
                box.innerHTML = '<b>Preview</b> — '+esc(j.processed)+' user(s) due, would reduce '+esc(j.reduced_total_bman)+' BMAN total.'+
                    (list ? '<table class="table table-row-dashed fs-7 mt-3"><thead><tr class="fw-bold text-muted"><th>User</th><th class="text-center">Cycle</th><th class="text-end">Bonus</th><th class="text-end">Would reduce</th><th class="text-end">Anchor</th></tr></thead><tbody>'+list+'</tbody></table>' : '');
            } else {
                box.className = 'alert alert-success mb-5';
                box.innerHTML = '<b>Done</b> ('+esc(j.mode)+') — processed '+esc(j.processed)+', reduced '+esc(j.reduced_total_bman)+' BMAN. Reloading…';
                setTimeout(() => location.reload(), 1400);
            }
        }

        const rn = document.getElementById('aw-run');
        if (rn) rn.addEventListener('click', () => {
            if (window.Swal) {
                Swal.fire({ text: 'Run the reduction now? This will reduce every due user\'s bonus wallet.',
                    icon: 'warning', showCancelButton: true, buttonsStyling: false,
                    confirmButtonText: 'Yes, run it', cancelButtonText: 'Cancel',
                    customClass: { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-light' } })
                    .then(r => { if (r.isConfirmed) callRun(false); });
            } else if (confirm('Run the reduction now?')) callRun(false);
        });

        /* -------------------- on-chain / off-chain toggle -------------------- */
        const toggleOnchain = document.getElementById('aw-toggle-onchain');
        if (toggleOnchain) toggleOnchain.addEventListener('click', () => {
            const next = toggleOnchain.dataset.onchain === '1' ? 0 : 1;
            const msg = next
                ? 'Turn on-chain sending back ON? Future cycles will try a real BMAN transfer from each user\'s wallet — needs BNB gas already sitting there, or it will keep failing the same way.'
                : 'Turn on-chain sending OFF? Future cycles stay internal-only — balances still reduce, but nothing is sent on-chain, so no more FAILED rows.';
            const run = async () => {
                toggleOnchain.disabled = true;
                const box = document.getElementById('aw-result');
                box.classList.remove('d-none'); box.className = 'alert alert-light mb-5';
                box.innerHTML = 'Saving…';
                const fd = new FormData(); fd.append('reduction_onchain', next);
                let j = {};
                try {
                    const res = await fetch(base + 'admin/staking/bonus-settings/save', {
                        method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    j = await res.json();
                } catch (e) { j = { status: 'error', message: 'Server error.' }; }
                if (j.status !== 'success') {
                    box.className = 'alert alert-danger mb-5'; box.textContent = j.message || 'Failed.';
                    toggleOnchain.disabled = false;
                    return;
                }
                box.className = 'alert alert-success mb-5';
                box.innerHTML = 'Saved — On-chain is now <b>' + (next ? 'ON' : 'OFF') + '</b>. Reloading…';
                setTimeout(() => location.reload(), 1000);
            };
            if (window.Swal) {
                Swal.fire({ text: msg, icon: 'warning', showCancelButton: true, buttonsStyling: false,
                    confirmButtonText: 'Yes, switch it', cancelButtonText: 'Cancel',
                    customClass: { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-light' } })
                    .then(r => { if (r.isConfirmed) run(); });
            } else if (confirm(msg)) run();
        });

        /* -------------------- retry a stuck on-chain leg -------------------- */
        async function callRetry(url, btn) {
            const box = document.getElementById('aw-result');
            box.classList.remove('d-none'); box.className = 'alert alert-light mb-5';
            box.innerHTML = 'Retrying on-chain send… gas is topped up from the treasury first if needed, this can take up to ~30s.';
            if (btn) { btn.disabled = true; btn.dataset.origText = btn.textContent; btn.textContent = 'Retrying…'; }

            let j = {};
            try {
                const res = await fetch(base + url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                j = await res.json();
            } catch (e) { j = { status: 'error', message: 'Server error.' }; }

            if (j.attempted !== undefined) {
                const parts = [esc(j.sent)+' sent'];
                if (j.auto_returned) parts.push(esc(j.auto_returned)+' auto-returned (gas unavailable)');
                parts.push(esc(j.still_failed)+' still failed');
                box.className = (j.still_failed > 0 ? 'alert alert-warning' : 'alert alert-success') + ' mb-5';
                box.innerHTML = '<b>Retry complete</b> — '+parts.join(', ')+' (of '+esc(j.attempted)+'). Reloading…';
            } else if (j.status === 'success') {
                box.className = 'alert alert-success mb-5';
                box.innerHTML = '<b>Sent</b> — tx '+esc(j.tx_hash)+'. Reloading…';
            } else if (j.status === 'auto_returned') {
                box.className = 'alert alert-success mb-5';
                box.innerHTML = '<b>Auto-returned</b> — '+esc(j.message)+' Reloading…';
            } else {
                box.className = 'alert alert-danger mb-5';
                box.innerHTML = esc(j.message || 'Retry failed.');
                if (btn) { btn.disabled = false; btn.textContent = btn.dataset.origText || 'Retry'; }
                return;
            }
            setTimeout(() => location.reload(), 1400);
        }

        document.querySelectorAll('.aw-retry-row').forEach(btn => {
            btn.addEventListener('click', () => {
                const run = () => callRetry('admin/wallet/admin-wallet/retry/' + btn.dataset.id, btn);
                const msg = 'Retry this on-chain send? Gas will be topped up from the treasury if needed. If gas still can\'t be arranged (user\'s wallet and treasury both short), the amount is automatically returned to the user internally instead of staying stuck.';
                if (window.Swal) {
                    Swal.fire({ text: msg,
                        icon: 'warning', showCancelButton: true, buttonsStyling: false,
                        confirmButtonText: 'Yes, retry', cancelButtonText: 'Cancel',
                        customClass: { confirmButton: 'btn btn-warning', cancelButton: 'btn btn-light' } })
                        .then(r => { if (r.isConfirmed) run(); });
                } else if (confirm(msg)) run();
            });
        });

        const ra = document.getElementById('aw-retry-all');
        if (ra) ra.addEventListener('click', () => {
            const run = () => callRetry('admin/wallet/admin-wallet/retry-all', ra);
            if (window.Swal) {
                Swal.fire({ text: 'Retry every failed on-chain send? Gas is topped up per user from the treasury as needed — this can take a while. Any row that still can\'t get gas is automatically returned to the user internally instead of staying stuck.',
                    icon: 'warning', showCancelButton: true, buttonsStyling: false,
                    confirmButtonText: 'Yes, retry all', cancelButtonText: 'Cancel',
                    customClass: { confirmButton: 'btn btn-warning', cancelButton: 'btn btn-light' } })
                    .then(r => { if (r.isConfirmed) run(); });
            } else if (confirm('Retry every failed on-chain send?')) run();
        });

        /* -------------------- return a reduction to the user -------------------- */
        async function callReturn(url, btn) {
            const box = document.getElementById('aw-result');
            box.classList.remove('d-none'); box.className = 'alert alert-light mb-5';
            box.innerHTML = 'Crediting the user back…';
            if (btn) { btn.disabled = true; btn.dataset.origText = btn.textContent; btn.textContent = 'Returning…'; }

            let j = {};
            try {
                const res = await fetch(base + url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                j = await res.json();
            } catch (e) { j = { status: 'error', message: 'Server error.' }; }

            if (j.status === 'success') {
                box.className = 'alert alert-success mb-5';
                box.innerHTML = '<b>Returned</b> — '+esc(j.amount)+' BMAN credited back to the user. Reloading…';
            } else {
                box.className = 'alert alert-danger mb-5';
                box.innerHTML = esc(j.message || 'Return failed.');
                if (btn) { btn.disabled = false; btn.textContent = btn.dataset.origText || 'Return'; }
                return;
            }
            setTimeout(() => location.reload(), 1400);
        }

        document.querySelectorAll('.aw-return-row').forEach(btn => {
            btn.addEventListener('click', () => {
                const run = () => callReturn('admin/wallet/admin-wallet/return/' + btn.dataset.id, btn);
                const msg = 'Credit '+btn.dataset.amount+' BMAN back to '+btn.dataset.user+'\'s Bonus Wallet? This reverses the reduction internally — no on-chain send, since these funds never actually left the user\'s on-chain wallet.';
                if (window.Swal) {
                    Swal.fire({ text: msg,
                        icon: 'warning', showCancelButton: true, buttonsStyling: false,
                        confirmButtonText: 'Yes, return it', cancelButtonText: 'Cancel',
                        customClass: { confirmButton: 'btn btn-info', cancelButton: 'btn btn-light' } })
                        .then(r => { if (r.isConfirmed) run(); });
                } else if (confirm(msg)) run();
            });
        });
    })();
    </script>
</body>

</html>
