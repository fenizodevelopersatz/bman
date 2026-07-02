<?php $this->load->view('admin/Layout/common_style'); ?>

<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />

<style>
    #stk-roi-table input.stk-cell {
        width: 92px; text-align: right;
        font-variant-numeric: tabular-nums;
    }
    #stk-roi-table input.stk-cell.stk-dirty {
        background-color: #fff8dd; border-color: #f6c000;
    }
    #stk-roi-table th.stk-fixed, #stk-roi-table td.stk-fixed { background: rgba(0, 158, 247, .04); }
    #stk-roi-table th.stk-regular, #stk-roi-table td.stk-regular { background: rgba(80, 205, 137, .04); }
    .stk-hist-link { cursor: pointer; }
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
                            </div>
                        </div>
                        <!--end::Toolbar-->

                        <div id="kt_app_content" class="app-content flex-column-fluid mt-10">
                            <div id="kt_app_content_container" class="app-container container-xxl">

                                <?php $this->load->view('notification'); ?>

                                <div class="card mb-5 mb-xxl-8">
                                    <div class="card-header border-transparent pt-5">
                                        <h3 class="card-title fw-bold"><?php echo $card_tilte; ?></h3>
                                        <div class="card-toolbar gap-2">
                                            <button type="button" class="btn btn-light btn-sm" id="stk-audit-btn">Audit Log</button>
                                        </div>
                                    </div>

                                    <div class="card-body pt-3 pb-9">
                                        <div class="text-muted fs-7 mb-5">
                                            The ROI matrix from the proposal (§6). <b>Fixed</b> columns are the
                                            <b>total %</b> over the whole term; <b>Regular</b> columns are
                                            <b>% per month</b>. Combo is derived 50/50 and needs no cells here.
                                            Edited cells turn amber; <b>Save</b> writes a new effective-dated
                                            version and an audit entry — live stakes keep their snapshot.
                                            <?php if (!$can_edit): ?>
                                                <span class="badge badge-light-danger ms-2">Read only — ROI edits are Super-Admin only</span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="d-flex align-items-end flex-wrap gap-4 mb-6">
                                            <div>
                                                <label class="form-label fw-semibold mb-1">Effective from</label>
                                                <input type="date" id="stk-eff" class="form-control form-control-solid form-control-sm w-175px"
                                                    value="<?php echo date('Y-m-d'); ?>" <?php echo $can_edit ? '' : 'disabled'; ?> />
                                            </div>
                                            <div class="flex-grow-1">
                                                <label class="form-label fw-semibold mb-1">Change note (audit)</label>
                                                <input type="text" id="stk-note" class="form-control form-control-solid form-control-sm"
                                                    placeholder="Why is the ROI changing? (optional)" <?php echo $can_edit ? '' : 'disabled'; ?> />
                                            </div>
                                            <div>
                                                <button type="button" class="btn btn-primary btn-sm" id="stk-save-btn" <?php echo $can_edit ? '' : 'disabled'; ?>>
                                                    Save changes <span class="badge badge-circle badge-light ms-1 d-none" id="stk-dirty-count">0</span>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered align-middle fs-6 gy-3" id="stk-roi-table">
                                                <thead>
                                                    <tr class="text-gray-500 fw-bold fs-7 text-uppercase">
                                                        <th rowspan="2" class="align-bottom">Stake (BMAN)</th>
                                                        <th colspan="3" class="text-center stk-fixed">Fixed — total % over term</th>
                                                        <th colspan="3" class="text-center stk-regular">Regular — % per month</th>
                                                    </tr>
                                                    <tr class="text-gray-500 fw-bold fs-7 text-uppercase">
                                                        <?php foreach (['fixed' => [2,3,5], 'regular' => [2,3,5]] as $pc => $yrs): ?>
                                                            <?php foreach ($yrs as $y): ?>
                                                                <th class="text-center stk-<?php echo $pc; ?>"><?php echo $y; ?>Y</th>
                                                            <?php endforeach; ?>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                </thead>
                                                <tbody class="text-gray-700 fw-semibold">
                                                    <?php foreach ($grid as $p): ?>
                                                    <tr>
                                                        <td>
                                                            <span class="fw-bold"><?php echo number_format((float)$p['stake_amount']); ?></span>
                                                            <?php if (!$p['is_active']): ?>
                                                                <span class="badge badge-light-danger fs-9 ms-1">disabled</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <?php foreach (['fixed' => [2,3,5], 'regular' => [2,3,5]] as $pc => $yrs): ?>
                                                            <?php foreach ($yrs as $y):
                                                                $key  = $pc.'_'.$y;
                                                                $cell = isset($p['roi'][$key]) ? $p['roi'][$key] : null;
                                                                $val  = $cell ? rtrim(rtrim(number_format((float)$cell['roi_percent'], 3, '.', ''), '0'), '.') : '';
                                                            ?>
                                                            <td class="text-center stk-<?php echo $pc; ?>">
                                                                <input type="number" step="0.001" min="0"
                                                                    class="form-control form-control-sm d-inline-block stk-cell"
                                                                    data-package="<?php echo (int)$p['id']; ?>"
                                                                    data-plan="<?php echo $pc; ?>"
                                                                    data-years="<?php echo $y; ?>"
                                                                    data-orig="<?php echo html_escape($val); ?>"
                                                                    value="<?php echo html_escape($val); ?>"
                                                                    <?php echo $can_edit ? '' : 'disabled'; ?> />
                                                                <div class="fs-9 text-muted stk-hist-link"
                                                                    data-package="<?php echo (int)$p['id']; ?>"
                                                                    data-plan="<?php echo $pc; ?>"
                                                                    data-years="<?php echo $y; ?>">
                                                                    <?php echo $cell ? 'since '.html_escape($cell['effective_from']) : 'not set'; ?> · history
                                                                </div>
                                                            </td>
                                                            <?php endforeach; ?>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- History modal -->
                                <div class="modal fade" id="stk-hist-modal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered mw-750px">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h3 class="modal-title" id="stk-hist-title">ROI Version History</h3>
                                                <div class="btn btn-sm btn-icon" data-bs-dismiss="modal">
                                                    <i class="ki-outline ki-cross fs-1"></i>
                                                </div>
                                            </div>
                                            <div class="modal-body scroll-y mh-500px" id="stk-hist-body">Loading…</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Audit modal -->
                                <div class="modal fade" id="stk-audit-modal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered mw-900px">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h3 class="modal-title">ROI Audit Log (old → new, who, when)</h3>
                                                <div class="btn btn-sm btn-icon" data-bs-dismiss="modal">
                                                    <i class="ki-outline ki-cross fs-1"></i>
                                                </div>
                                            </div>
                                            <div class="modal-body scroll-y mh-500px" id="stk-audit-body">Loading…</div>
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
        const canEdit = <?php echo $can_edit ? 'true' : 'false'; ?>;

        function toast(msg, ok) {
            if (window.Swal) {
                Swal.fire({ text: msg, icon: ok ? 'success' : 'error',
                    buttonsStyling: false, confirmButtonText: 'Ok',
                    customClass: { confirmButton: 'btn btn-primary' } });
            } else { alert(msg); }
        }
        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g,
                c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        /* ---- dirty-cell tracking (amber = unsaved, doc §6) ---- */
        const dirtyBadge = document.getElementById('stk-dirty-count');
        function refreshDirty() {
            const n = document.querySelectorAll('.stk-cell.stk-dirty').length;
            dirtyBadge.textContent = n;
            dirtyBadge.classList.toggle('d-none', n === 0);
        }
        document.querySelectorAll('.stk-cell').forEach(inp => {
            inp.addEventListener('input', () => {
                inp.classList.toggle('stk-dirty', inp.value !== inp.dataset.orig);
                refreshDirty();
            });
        });

        /* ---- save all dirty cells (versioned + audited server-side) ---- */
        document.getElementById('stk-save-btn').addEventListener('click', async () => {
            if (!canEdit) return;
            const cells = [];
            document.querySelectorAll('.stk-cell.stk-dirty').forEach(inp => {
                if (inp.value === '') return; // ignore cleared cells
                cells.push({
                    package_id: inp.dataset.package,
                    plan_code: inp.dataset.plan,
                    duration_years: inp.dataset.years,
                    percent: inp.value
                });
            });
            if (!cells.length) { toast('No changes to save.', false); return; }

            const fd = new FormData();
            fd.append('cells', JSON.stringify(cells));
            fd.append('effective_from', document.getElementById('stk-eff').value);
            fd.append('note', document.getElementById('stk-note').value);

            const btn = document.getElementById('stk-save-btn');
            btn.disabled = true;
            const res = await fetch(base + 'admin/staking/roi-structure/save', {
                method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            let j = {}; try { j = await res.json(); } catch (e) { j = { message: 'Server error.' }; }
            btn.disabled = false;
            const ok = res.ok && j.status === 'success';
            toast(j.message || '', ok);
            if (ok) setTimeout(() => location.reload(), 700);
        });

        /* ---- per-cell version history ---- */
        document.querySelectorAll('.stk-hist-link').forEach(el => {
            el.addEventListener('click', async () => {
                const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('stk-hist-modal'));
                document.getElementById('stk-hist-title').textContent =
                    'ROI Version History — ' + el.dataset.plan.toUpperCase() + ' ' + el.dataset.years + 'Y';
                const body = document.getElementById('stk-hist-body');
                body.innerHTML = 'Loading…';
                modal.show();
                const q = 'package_id=' + el.dataset.package + '&plan_code=' + el.dataset.plan +
                          '&duration_years=' + el.dataset.years;
                const res = await fetch(base + 'admin/staking/roi-structure/history?' + q,
                    { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const j = await res.json();
                const rows = (j.rows || []).map(r =>
                    '<tr>' +
                    '<td>' + esc(r.package_name) + '</td>' +
                    '<td class="text-end">' + esc(r.roi_percent) + '% <span class="text-muted fs-8">' + esc(r.roi_basis) + '</span></td>' +
                    '<td>' + esc(r.effective_from) + '</td>' +
                    '<td>' + (Number(r.is_active) ? '<span class="badge badge-light-success">current</span>'
                                                  : '<span class="badge badge-light">superseded</span>') + '</td>' +
                    '<td class="text-muted fs-8">' + esc(r.created_at) + '</td>' +
                    '</tr>').join('');
                body.innerHTML = rows
                    ? '<table class="table table-row-dashed fs-7"><thead><tr class="fw-bold text-muted">' +
                      '<th>Package</th><th class="text-end">ROI</th><th>Effective from</th><th>Status</th><th>Created</th>' +
                      '</tr></thead><tbody>' + rows + '</tbody></table>'
                    : '<div class="text-muted">No versions recorded for this cell yet.</div>';
            });
        });

        /* ---- global audit log ---- */
        document.getElementById('stk-audit-btn').addEventListener('click', async () => {
            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('stk-audit-modal'));
            const body = document.getElementById('stk-audit-body');
            body.innerHTML = 'Loading…';
            modal.show();
            const res = await fetch(base + 'admin/staking/roi-structure/audit',
                { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const j = await res.json();
            const rows = (j.rows || []).map(r =>
                '<tr>' +
                '<td>' + esc(r.package_name) + '</td>' +
                '<td class="text-uppercase">' + esc(r.plan_code) + ' ' + esc(r.duration_years) + 'Y</td>' +
                '<td class="text-end">' + (r.old_percent === null ? '<span class="text-muted">—</span>' : esc(r.old_percent) + '%') +
                    ' → <b>' + esc(r.new_percent) + '%</b></td>' +
                '<td>' + esc(r.admin_name || ('#' + r.changed_by)) + '</td>' +
                '<td class="text-muted fs-8">' + esc(r.created_at) + '</td>' +
                '<td class="text-muted fs-8">' + esc(r.note || '') + '</td>' +
                '</tr>').join('');
            body.innerHTML = rows
                ? '<table class="table table-row-dashed fs-7"><thead><tr class="fw-bold text-muted">' +
                  '<th>Package</th><th>Cell</th><th class="text-end">Old → New</th><th>By</th><th>When</th><th>Note</th>' +
                  '</tr></thead><tbody>' + rows + '</tbody></table>'
                : '<div class="text-muted">No ROI changes recorded yet.</div>';
        });
    })();
    </script>
</body>

</html>
