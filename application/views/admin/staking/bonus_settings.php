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
                                    <button type="button" class="btn btn-light btn-sm" id="bsm-audit-btn">Audit Log</button>
                                </div>
                            </div>
                        </div>
                        <!--end::Toolbar-->

                        <div id="kt_app_content" class="app-content flex-column-fluid mt-10">
                            <div id="kt_app_content_container" class="app-container container-xxl">

                                <?php $this->load->view('notification'); ?>

                                <form id="stk-bonus-form">
                                <div class="row g-5">

                                    <!-- §7 Staking Bonus -->
                                    <div class="col-lg-6">
                                        <div class="card h-100">
                                            <div class="card-header border-transparent pt-5">
                                                <h3 class="card-title fw-bold">Staking Bonus (§7)</h3>
                                            </div>
                                            <div class="card-body pt-2">
                                                <div class="text-muted fs-7 mb-6">
                                                    Every stake purchase receives this % as <b>Bonus Coin</b>
                                                    (proposal: 25%). This is the global default — individual
                                                    packages can override it on the
                                                    <a href="<?php echo base_url(); ?>admin/staking/packages">Packages</a> screen.
                                                </div>
                                                <div class="mb-6">
                                                    <label class="form-label fw-semibold required">Default bonus %</label>
                                                    <input type="number" name="bonus_percent_default" step="0.01" min="0" max="100"
                                                        class="form-control form-control-solid w-150px"
                                                        value="<?php echo (float)$settings['bonus_percent_default']; ?>" required />
                                                </div>
                                                <button type="button" class="btn btn-light-warning btn-sm" id="stk-apply-pkgs">
                                                    Apply default to all packages
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- §7 Bonus Reduction -->
                                    <div class="col-lg-6">
                                        <div class="card h-100">
                                            <div class="card-header border-transparent pt-5">
                                                <h3 class="card-title fw-bold">Bonus Coin Reduction (§7)</h3>
                                            </div>
                                            <div class="card-body pt-2">
                                                <div class="text-muted fs-7 mb-6">
                                                    Every <b>N days</b>, a % of each member's <b>Bonus Wallet</b>
                                                    balance is automatically reduced (proposal: every 60 days,
                                                    50%). The reduction cron uses these values.
                                                </div>
                                                <div class="form-check form-switch form-check-custom form-check-solid mb-6">
                                                    <input class="form-check-input" type="checkbox" name="reduction_enabled" value="1"
                                                        <?php echo !empty($settings['reduction_enabled']) ? 'checked' : ''; ?> />
                                                    <label class="form-check-label fw-semibold">Automatic reduction enabled</label>
                                                </div>
                                                <div class="form-check form-switch form-check-custom form-check-solid mb-2">
                                                    <input class="form-check-input" type="checkbox" name="reduction_dry_run" value="1"
                                                        <?php echo !empty($settings['reduction_dry_run']) ? 'checked' : ''; ?> />
                                                    <label class="form-check-label fw-semibold">Dry-run only (preview — no real balance changes)</label>
                                                </div>
                                                <div class="form-check form-switch form-check-custom form-check-solid mb-2">
                                                    <input class="form-check-input" type="checkbox" name="reduction_onchain" value="1"
                                                        <?php echo !empty($settings['reduction_onchain']) ? 'checked' : ''; ?> />
                                                    <label class="form-check-label fw-semibold">Also send on-chain (BMAN moved from the user's custodial wallet to admin)</label>
                                                </div>
                                                <div class="text-muted fs-8 mb-6">
                                                    On-chain sends need BNB gas already sitting in each user's custodial wallet —
                                                    if their wallets hold none, every cycle's on-chain leg fails with
                                                    "insufficient funds for gas" (the balance itself still reduces either way;
                                                    only the matching on-chain transfer to admin doesn't). Turn this off to
                                                    keep reductions internal-only until that's resolved — every cycle then
                                                    completes cleanly with no FAILED rows.
                                                </div>
                                                <div class="row">
                                                    <div class="col-6 mb-6">
                                                        <label class="form-label fw-semibold required">Interval (days)</label>
                                                        <input type="number" name="reduction_interval_days" min="1" max="365"
                                                            class="form-control form-control-solid"
                                                            value="<?php echo (int)$settings['reduction_interval_days']; ?>" required />
                                                    </div>
                                                    <div class="col-6 mb-6">
                                                        <label class="form-label fw-semibold required">Reduction %</label>
                                                        <input type="number" name="reduction_percent" step="0.01" min="0" max="100"
                                                            class="form-control form-control-solid"
                                                            value="<?php echo (float)$settings['reduction_percent']; ?>" required />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- §7 Bonus Transfer -->
                                    <div class="col-lg-6">
                                        <div class="card h-100">
                                            <div class="card-header border-transparent pt-5">
                                                <h3 class="card-title fw-bold">Bonus Coin Transfer (§7)</h3>
                                            </div>
                                            <div class="card-body pt-2">
                                                <div class="text-muted fs-7 mb-6">
                                                    Proposal: transfers allowed <b>only</b> to the direct left /
                                                    right sponsored member, secured by email OTP and the transfer
                                                    password.
                                                </div>
                                                <div class="form-check form-switch form-check-custom form-check-solid mb-5">
                                                    <input class="form-check-input" type="checkbox" name="transfer_enabled" value="1"
                                                        <?php echo !empty($settings['transfer_enabled']) ? 'checked' : ''; ?> />
                                                    <label class="form-check-label fw-semibold">Bonus transfers enabled</label>
                                                </div>
                                                <label class="form-label fw-semibold">Allowed recipients</label>
                                                <div class="d-flex gap-8 mb-6">
                                                    <div class="form-check form-check-custom form-check-solid">
                                                        <input class="form-check-input" type="checkbox" name="transfer_to_direct_left" value="1"
                                                            <?php echo !empty($settings['transfer_to_direct_left']) ? 'checked' : ''; ?> />
                                                        <label class="form-check-label">Direct Left sponsored member</label>
                                                    </div>
                                                    <div class="form-check form-check-custom form-check-solid">
                                                        <input class="form-check-input" type="checkbox" name="transfer_to_direct_right" value="1"
                                                            <?php echo !empty($settings['transfer_to_direct_right']) ? 'checked' : ''; ?> />
                                                        <label class="form-check-label">Direct Right sponsored member</label>
                                                    </div>
                                                </div>
                                                <label class="form-label fw-semibold">Security</label>
                                                <div class="d-flex gap-8">
                                                    <div class="form-check form-check-custom form-check-solid">
                                                        <input class="form-check-input" type="checkbox" name="transfer_require_email_otp" value="1"
                                                            <?php echo !empty($settings['transfer_require_email_otp']) ? 'checked' : ''; ?> />
                                                        <label class="form-check-label">Email OTP verification</label>
                                                    </div>
                                                    <div class="form-check form-check-custom form-check-solid">
                                                        <input class="form-check-input" type="checkbox" name="transfer_require_transfer_password" value="1"
                                                            <?php echo !empty($settings['transfer_require_transfer_password']) ? 'checked' : ''; ?> />
                                                        <label class="form-check-label">Transfer password</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- §9 Binary Matching Bonus -->
                                    <div class="col-lg-6">
                                        <div class="card h-100">
                                            <div class="card-header border-transparent pt-5">
                                                <h3 class="card-title fw-bold">Binary Matching Bonus (§9)</h3>
                                            </div>
                                            <div class="card-body pt-2">
                                                <div class="text-muted fs-7 mb-6">
                                                    Total matching bonus on binary pairs, split between wallets
                                                    (proposal: 10% total → 8% Earning Coin + 2% Staking Coin).
                                                    The split must equal the total.
                                                </div>
                                                <div class="row">
                                                    <div class="col-4 mb-6">
                                                        <label class="form-label fw-semibold required">Total %</label>
                                                        <input type="number" name="matching_total_percent" step="0.01" min="0" max="100"
                                                            class="form-control form-control-solid stk-match"
                                                            value="<?php echo (float)$settings['matching_total_percent']; ?>" required />
                                                    </div>
                                                    <div class="col-4 mb-6">
                                                        <label class="form-label fw-semibold required">Earning Coin %</label>
                                                        <input type="number" name="matching_earning_percent" step="0.01" min="0" max="100"
                                                            class="form-control form-control-solid stk-match"
                                                            value="<?php echo (float)$settings['matching_earning_percent']; ?>" required />
                                                    </div>
                                                    <div class="col-4 mb-6">
                                                        <label class="form-label fw-semibold required">Staking Coin %</label>
                                                        <input type="number" name="matching_staking_percent" step="0.01" min="0" max="100"
                                                            class="form-control form-control-solid stk-match"
                                                            value="<?php echo (float)$settings['matching_staking_percent']; ?>" required />
                                                    </div>
                                                </div>
                                                <div class="text-muted fs-8" id="stk-match-note">Earning + Staking must equal Total.</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 text-end">
                                        <button type="submit" class="btn btn-primary">Save Bonus & Matching Settings</button>
                                    </div>

                                </div>
                                </form>

                                <!-- Audit modal -->
                                <div class="modal fade" id="bsm-audit-modal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered mw-900px">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h3 class="modal-title">Bonus & Matching Settings Audit Log</h3>
                                                <div class="btn btn-sm btn-icon" data-bs-dismiss="modal">
                                                    <i class="ki-outline ki-cross fs-1"></i>
                                                </div>
                                            </div>
                                            <div class="modal-body scroll-y mh-500px" id="bsm-audit-body">Loading…</div>
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

        const form = document.getElementById('stk-bonus-form');

        // live matching-sum hint
        document.querySelectorAll('.stk-match').forEach(inp => {
            inp.addEventListener('input', () => {
                const t = parseFloat(form.elements.matching_total_percent.value) || 0;
                const e = parseFloat(form.elements.matching_earning_percent.value) || 0;
                const s = parseFloat(form.elements.matching_staking_percent.value) || 0;
                const note = document.getElementById('stk-match-note');
                note.textContent = 'Earning + Staking must equal Total. Current: ' + e + ' + ' + s + ' = ' + (e + s) + ' (total ' + t + ')';
                note.classList.toggle('text-danger', Math.abs(e + s - t) > 0.001);
            });
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData();
            fd.append('bonus_percent_default', form.elements.bonus_percent_default.value);
            fd.append('reduction_enabled', form.elements.reduction_enabled.checked ? 1 : 0);
            fd.append('reduction_dry_run', form.elements.reduction_dry_run.checked ? 1 : 0);
            fd.append('reduction_onchain', form.elements.reduction_onchain.checked ? 1 : 0);
            fd.append('reduction_interval_days', form.elements.reduction_interval_days.value);
            fd.append('reduction_percent', form.elements.reduction_percent.value);
            fd.append('transfer_enabled', form.elements.transfer_enabled.checked ? 1 : 0);
            fd.append('transfer_to_direct_left', form.elements.transfer_to_direct_left.checked ? 1 : 0);
            fd.append('transfer_to_direct_right', form.elements.transfer_to_direct_right.checked ? 1 : 0);
            fd.append('transfer_require_email_otp', form.elements.transfer_require_email_otp.checked ? 1 : 0);
            fd.append('transfer_require_transfer_password', form.elements.transfer_require_transfer_password.checked ? 1 : 0);
            fd.append('matching_total_percent', form.elements.matching_total_percent.value);
            fd.append('matching_earning_percent', form.elements.matching_earning_percent.value);
            fd.append('matching_staking_percent', form.elements.matching_staking_percent.value);
            const r = await post('admin/staking/bonus-settings/save', fd);
            toast(r.msg, r.ok);
        });

        document.getElementById('stk-apply-pkgs').addEventListener('click', async () => {
            if (!confirm('Overwrite the bonus % of EVERY package with the default value? Package-level overrides will be lost.')) return;
            const r = await post('admin/staking/bonus-settings/apply-to-packages', new FormData());
            toast(r.msg, r.ok);
        });

        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g,
                c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        /* audit log */
        document.getElementById('bsm-audit-btn').addEventListener('click', async () => {
            const m = bootstrap.Modal.getOrCreateInstance(document.getElementById('bsm-audit-modal'));
            const body = document.getElementById('bsm-audit-body');
            body.innerHTML = 'Loading…';
            m.show();
            const res = await fetch(base + 'admin/staking/bonus-settings/audit',
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
