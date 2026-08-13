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
                                            <button type="button" class="btn btn-light btn-sm" id="aw-preview">Preview (dry-run)</button>
                                            <button type="button" class="btn btn-primary btn-sm" id="aw-run">Run Reduction Now</button>
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
                                            <span class="badge <?php echo $onchain ? 'badge-light-info' : 'badge-light'; ?>">On-chain: <?php echo $onchain ? 'ON' : 'OFF'; ?></span>
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
                                                    </tr>
                                                </thead>
                                                <tbody class="text-gray-700 fw-semibold">
                                                    <?php foreach ($history as $h): ?>
                                                    <tr>
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
                                                                $cls = $st === 'sent' ? 'badge-light-success' : ($st === 'failed' ? 'badge-light-danger' : 'badge-light-primary');
                                                            ?>
                                                            <span class="badge <?php echo $cls; ?>" title="<?php echo html_escape($h['note'] ?: ''); ?>"><?php echo html_escape(strtoupper($st)); ?></span>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($h['tx_hash'])): ?>
                                                                <a class="aw-addr text-hover-primary" target="_blank" href="<?php echo $explorer_url; ?>/tx/<?php echo html_escape($h['tx_hash']); ?>"><?php echo html_escape(substr($h['tx_hash'], 0, 10)); ?>…</a>
                                                            <?php else: ?>
                                                                <span class="text-muted fs-8">—</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-muted fs-8"><?php echo html_escape($h['created_at']); ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                    <?php if (empty($history)): ?>
                                                        <tr><td colspan="8" class="text-muted">No reductions yet. Use <b>Preview</b> to see who is due, then <b>Run Reduction Now</b>.</td></tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
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

        const pv = document.getElementById('aw-preview');
        const rn = document.getElementById('aw-run');
        if (pv) pv.addEventListener('click', () => callRun(true));
        if (rn) rn.addEventListener('click', () => {
            if (window.Swal) {
                Swal.fire({ text: 'Run the reduction now? This will reduce every due user\'s bonus wallet.',
                    icon: 'warning', showCancelButton: true, buttonsStyling: false,
                    confirmButtonText: 'Yes, run it', cancelButtonText: 'Cancel',
                    customClass: { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-light' } })
                    .then(r => { if (r.isConfirmed) callRun(false); });
            } else if (confirm('Run the reduction now?')) callRun(false);
        });
    })();
    </script>
</body>

</html>
