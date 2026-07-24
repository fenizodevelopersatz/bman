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
                                            <a href="<?php echo base_url(); ?>" class="text-muted text-hover-primary">Staking</a>
                                        </li>
                                        <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                        <li class="breadcrumb-item text-muted"><?php echo $title; ?></li>
                                    </ul>
                                </div>
                                <div class="d-flex align-items-center gap-2 my-2">
                                    <button type="button" class="btn btn-light btn-sm" id="stkp-audit-btn">Audit Log</button>
                                </div>
                            </div>
                        </div>
                        <!--end::Toolbar-->

                        <div id="kt_app_content" class="app-content flex-column-fluid mt-10">
                            <div id="kt_app_content_container" class="app-container container-xxl">

                                <?php $this->load->view('notification'); ?>

                                <div class="text-muted fs-7 mb-5">
                                    How a stake earns (proposal §5). <b>Fixed</b> credits the whole ROI at
                                    maturity; <b>Regular</b> credits monthly on the configured days;
                                    <b>Combo</b> splits the stake between the two (must total 100%).
                                    Durations tick which terms (2/3/5 years) each plan offers.
                                    All withdrawal rules (status, limits, fees, per-plan windows) live on the
                                    single <a href="<?php echo base_url(); ?>withdraw-settings" class="fw-bold">Withdraw Settings</a> page.
                                </div>

                                <div class="row g-5">
                                    <?php foreach ($plans as $plan):
                                        $code = $plan['code'];
                                        $active_years = [];
                                        foreach ($plan['terms'] as $t) {
                                            if ((int)$t['is_active']) $active_years[] = (int)$t['duration_years'];
                                        }
                                    ?>
                                    <div class="col-lg-4">
                                        <div class="card h-100">
                                            <div class="card-header border-transparent pt-5">
                                                <h3 class="card-title fw-bold"><?php echo html_escape($plan['name']); ?>
                                                    <span class="badge badge-light-info ms-2 text-uppercase"><?php echo html_escape($plan['roi_credit_mode']); ?></span>
                                                </h3>
                                                <div class="card-toolbar">
                                                    <div class="form-check form-switch form-check-custom form-check-solid">
                                                        <input class="form-check-input stk-plan-toggle" type="checkbox"
                                                            data-id="<?php echo (int)$plan['id']; ?>"
                                                            <?php echo $plan['is_active'] ? 'checked' : ''; ?> />
                                                        <label class="form-check-label fs-7">Active</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body pt-2">
                                                <form class="stk-plan-form" data-id="<?php echo (int)$plan['id']; ?>">

                                                    <label class="form-label fw-semibold">Durations offered</label>
                                                    <div class="d-flex gap-6 mb-6">
                                                        <?php foreach ([2, 3, 5] as $y): ?>
                                                        <div class="form-check form-check-custom form-check-solid">
                                                            <input class="form-check-input" type="checkbox" name="years[]"
                                                                value="<?php echo $y; ?>"
                                                                <?php echo in_array($y, $active_years, true) ? 'checked' : ''; ?> />
                                                            <label class="form-check-label"><?php echo $y; ?> Years</label>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>

                                                    <?php if ($code === 'fixed'): ?>
                                                        <div class="alert alert-secondary fs-7 py-3">
                                                            ROI credited <b>only at maturity</b>; withdrawal allowed
                                                            only after maturity (proposal §5).
                                                        </div>
                                                        <input type="hidden" name="withdraw_after_maturity" value="1" />
                                                    <?php else: ?>
                                                        <div class="mb-5">
                                                            <label class="form-label fw-semibold">Monthly ROI credit days</label>
                                                            <input type="text" name="credit_days" class="form-control form-control-solid"
                                                                value="<?php echo html_escape((string)$plan['credit_days']); ?>"
                                                                placeholder="5,15,25" />
                                                            <div class="text-muted fs-8 mt-1">Day numbers 1–31, comma separated.</div>
                                                        </div>
                                                        <div class="alert alert-secondary fs-7 py-3">
                                                            Withdraw window and min/max limits are managed on the single
                                                            <a href="<?php echo base_url(); ?>withdraw-settings" class="fw-bold">Withdraw Settings</a>
                                                            page (currently: every <?php echo (int)$plan['withdraw_frequency_days']; ?> days,
                                                            <?php echo (float)$plan['min_withdraw_bman']; ?>–<?php echo (float)$plan['max_withdraw_bman']; ?> BMAN /
                                                            <?php echo (float)$plan['min_withdraw_usdt']; ?>–<?php echo (float)$plan['max_withdraw_usdt']; ?> USDT).
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if ($code === 'combo'): ?>
                                                        <div class="row">
                                                            <div class="col-6 mb-5">
                                                                <label class="form-label fw-semibold">Fixed share %</label>
                                                                <input type="number" step="0.01" min="0" max="100" name="combo_fixed_pct"
                                                                    class="form-control form-control-solid stk-combo"
                                                                    value="<?php echo (float)$plan['combo_fixed_pct']; ?>" />
                                                            </div>
                                                            <div class="col-6 mb-5">
                                                                <label class="form-label fw-semibold">Regular share %</label>
                                                                <input type="number" step="0.01" min="0" max="100" name="combo_regular_pct"
                                                                    class="form-control form-control-solid stk-combo"
                                                                    value="<?php echo (float)$plan['combo_regular_pct']; ?>" />
                                                            </div>
                                                        </div>
                                                        <div class="text-muted fs-8 mb-3 stk-combo-sum-note">Split must total 100%.</div>
                                                    <?php endif; ?>

                                                    <div class="text-end">
                                                        <button type="submit" class="btn btn-primary btn-sm">Save <?php echo html_escape($plan['name']); ?></button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- ===================== SPECIAL OFFER PLAN (read-only summary) ===================== -->
                                <div class="row g-5 mt-2">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header border-transparent pt-5">
                                                <h3 class="card-title fw-bold">Special Offer Plan
                                                    <span class="badge badge-light-warning ms-2 text-uppercase">Escalating</span>
                                                </h3>
                                                <div class="card-toolbar">
                                                    <span class="badge badge-light-<?php echo !empty($special_packages) ? 'success' : 'secondary'; ?> fs-7">
                                                        <?php echo count($special_packages); ?> package<?php echo count($special_packages) === 1 ? '' : 's'; ?> active
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="card-body pt-2">
                                                <div class="text-muted fs-7 mb-4">
                                                    Special Offer packages use a <b>year-wise escalating Monthly ROI</b> (Year 1 → Year 2 → …)
                                                    and pay the selected term's <b>Maturity %</b> plus the principal at term end. Per-year ROI is
                                                    configured on the
                                                    <a href="<?php echo base_url('admin/staking/special-roi'); ?>" class="fw-bold">Special ROI (Offer)</a>
                                                    page; flag which packages qualify on the
                                                    <a href="<?php echo base_url('admin/staking/packages'); ?>" class="fw-bold">Packages</a> page.
                                                </div>

                                                <?php if (empty($special_packages)): ?>
                                                    <div class="alert alert-secondary fs-7 py-3 mb-4">
                                                        No packages flagged as Special Offer yet. Enable “Is Special Offer” on a package to activate it here.
                                                    </div>
                                                <?php else: ?>
                                                    <div class="table-responsive">
                                                        <table class="table table-row-bordered align-middle fs-7 gy-3 mb-4">
                                                            <thead>
                                                                <tr class="text-gray-500 fw-bold fs-8 text-uppercase">
                                                                    <th>Package</th>
                                                                    <th class="text-end">Stake (BMAN)</th>
                                                                    <th>Offered terms — year · Monthly ROI · Maturity</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="text-gray-800 fw-semibold">
                                                                <?php foreach ($special_packages as $sp): ?>
                                                                <tr>
                                                                    <td>
                                                                        <span class="fw-bold"><?php echo html_escape($sp['name']); ?></span>
                                                                        <?php if (empty($sp['is_active'])): ?>
                                                                            <span class="badge badge-light-danger fs-9 ms-1">disabled</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td class="text-end"><?php echo number_format((float)$sp['stake_amount']); ?></td>
                                                                    <td>
                                                                        <?php if (empty($sp['offered'])): ?>
                                                                            <span class="text-muted">No year terms configured yet.</span>
                                                                        <?php else: foreach ($sp['offered'] as $yr => $r): ?>
                                                                            <span class="badge badge-light-warning me-2 mb-1">
                                                                                <?php echo (int)$yr; ?>y ·
                                                                                <?php echo rtrim(rtrim(number_format((float)$r['monthly_roi_percent'], 3, '.', ''), '0'), '.'); ?>%/mo ·
                                                                                <?php echo rtrim(rtrim(number_format((float)$r['maturity_percent'], 3, '.', ''), '0'), '.'); ?>%
                                                                            </span>
                                                                        <?php endforeach; endif; ?>
                                                                    </td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="text-end">
                                                    <a href="<?php echo base_url('admin/staking/packages'); ?>" class="btn btn-light btn-sm me-2">Manage Special Packages →</a>
                                                    <a href="<?php echo base_url('admin/staking/special-roi'); ?>" class="btn btn-primary btn-sm">Configure Special ROI →</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- Audit modal -->
                            <div class="modal fade" id="stkp-audit-modal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered mw-900px">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h3 class="modal-title">Staking Plans Audit Log</h3>
                                            <div class="btn btn-sm btn-icon" data-bs-dismiss="modal">
                                                <i class="ki-outline ki-cross fs-1"></i>
                                            </div>
                                        </div>
                                        <div class="modal-body scroll-y mh-500px" id="stkp-audit-body">Loading…</div>
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
            if (window.Swal) {
                Swal.fire({ text: msg, icon: ok ? 'success' : 'error',
                    buttonsStyling: false, confirmButtonText: 'Ok',
                    customClass: { confirmButton: 'btn btn-primary' } });
            } else { alert(msg); }
        }

        async function post(url, fd) {
            const res = await fetch(base + url, {
                method: 'POST', body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            let j = {};
            try { j = await res.json(); } catch (e) { j = { status: 'error', message: 'Server error.' }; }
            return { ok: res.ok && j.status === 'success', msg: j.message || '' };
        }

        // save each plan card
        document.querySelectorAll('.stk-plan-form').forEach(form => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const fd = new FormData(form);
                // always send years[] (unticking all should clear durations)
                if (!fd.has('years[]')) fd.append('years[]', '');
                const btn = form.querySelector('button[type=submit]');
                btn.disabled = true;
                const r = await post('admin/staking/plans/save/' + form.dataset.id, fd);
                btn.disabled = false;
                toast(r.msg, r.ok);
            });
        });

        // live combo sum hint
        document.querySelectorAll('.stk-combo').forEach(inp => {
            inp.addEventListener('input', () => {
                const form = inp.closest('form');
                const f = parseFloat(form.elements.combo_fixed_pct.value) || 0;
                const g = parseFloat(form.elements.combo_regular_pct.value) || 0;
                const note = form.querySelector('.stk-combo-sum-note');
                note.textContent = 'Split must total 100%. Current: ' + (f + g) + '%';
                note.classList.toggle('text-danger', Math.abs(f + g - 100) > 0.001);
            });
        });

        // enable / disable a plan
        document.querySelectorAll('.stk-plan-toggle').forEach(sw => {
            sw.addEventListener('change', async () => {
                const fd = new FormData();
                fd.append('active', sw.checked ? 1 : 0);
                const r = await post('admin/staking/plans/toggle/' + sw.dataset.id, fd);
                if (!r.ok) { sw.checked = !sw.checked; toast(r.msg, false); }
            });
        });

        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g,
                c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        /* audit log */
        document.getElementById('stkp-audit-btn').addEventListener('click', async () => {
            const m = bootstrap.Modal.getOrCreateInstance(document.getElementById('stkp-audit-modal'));
            const body = document.getElementById('stkp-audit-body');
            body.innerHTML = 'Loading…';
            m.show();
            const res = await fetch(base + 'admin/staking/plans/audit',
                { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const j = await res.json();
            const rows = (j.rows || []).map(r =>
                '<tr>' +
                '<td>' + esc(r.field_name) + '</td>' +
                '<td class="fs-8 text-muted mw-300px text-truncate">' + esc(r.old_value ?? '—') + '</td>' +
                '<td class="fs-8 text-muted mw-300px text-truncate">' + esc(r.new_value ?? '—') + '</td>' +
                '<td>' + esc(r.admin_name || ('#' + r.changed_by)) + '</td>' +
                '<td class="text-muted fs-8">' + esc(r.created_at) + '</td>' +
                '</tr>').join('');
            body.innerHTML = rows
                ? '<table class="table table-row-dashed fs-7"><thead><tr class="fw-bold text-muted">' +
                  '<th>Field</th><th>Old</th><th>New</th><th>By</th><th>When</th>' +
                  '</tr></thead><tbody>' + rows + '</tbody></table>'
                : '<div class="text-muted">No changes recorded yet.</div>';
        });
    })();
    </script>
</body>

</html>
