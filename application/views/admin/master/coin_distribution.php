<?php $this->load->view('admin/Layout/common_style'); ?>

<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />

<style>
    .cdm-num { font-variant-numeric: tabular-nums; }
    #cdm-total-note.bad { color: #f1416c; font-weight: 600; }
    #cdm-total-note.good { color: #50cd89; font-weight: 600; }
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
                                            <a href="<?php echo base_url(); ?>" class="text-muted text-hover-primary">Master</a>
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
                                            <button type="button" class="btn btn-light btn-sm" id="cdm-audit-btn">Audit Log</button>
                                            <?php if ($is_super): ?>
                                            <button type="button" class="btn btn-primary btn-sm" id="cdm-add-btn">
                                                <i class="ki-duotone ki-plus fs-2"></i> Add Option
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="card-body pt-3 pb-9">
                                        <div class="text-muted fs-7 mb-5">
                                            How deposited USDT / purchased BMAN is allocated across the Exchange,
                                            Earning, Staking and Bonus wallets (proposal §3A). Total must equal
                                            exactly 100%. Only one option can be the default; the default cannot
                                            be disabled. Every purchase stores the option <b>and</b> a percentage
                                            snapshot — later edits never affect old purchases.
                                            <?php if (!$is_super): ?>
                                                <span class="badge badge-light-danger ms-2">View / enable–disable only — editing is Super-Admin</span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="d-flex flex-stack flex-wrap gap-3 mb-5">
                                            <div class="d-flex align-items-center flex-wrap gap-3">
                                                <div class="position-relative">
                                                    <i class="ki-duotone ki-magnifier fs-1 position-absolute ms-4 mt-2"><span class="path1"></span><span class="path2"></span></i>
                                                    <input type="text" id="cdm-search" class="form-control form-control-solid form-control-sm w-225px ps-12"
                                                        placeholder="Search name / description" />
                                                </div>
                                                <select id="cdm-filter-status" class="form-select form-select-solid form-select-sm w-140px">
                                                    <option value="">All Status</option>
                                                    <option value="1">Active</option>
                                                    <option value="0">Disabled</option>
                                                </select>
                                                <select id="cdm-filter-default" class="form-select form-select-solid form-select-sm w-140px">
                                                    <option value="">All Options</option>
                                                    <option value="1">Default only</option>
                                                    <option value="0">Non-default</option>
                                                </select>
                                            </div>
                                            <a href="#" id="cdm-export" class="btn btn-light-primary btn-sm">
                                                <i class="ki-duotone ki-exit-down fs-2"><span class="path1"></span><span class="path2"></span></i>
                                                Export CSV
                                            </a>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table align-middle table-row-dashed fs-6 gy-4" id="cdm-table">
                                                <thead>
                                                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                                        <th>ID</th>
                                                        <th>Option Name</th>
                                                        <th class="text-end">Exchange %</th>
                                                        <th class="text-end">Earning %</th>
                                                        <th class="text-end">Staking %</th>
                                                        <th class="text-end">Bonus %</th>
                                                        <th class="text-end">Total %</th>
                                                        <th class="text-center">Default</th>
                                                        <th class="text-center">Status</th>
                                                        <th>Created At</th>
                                                        <th class="text-end">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="text-gray-700 fw-semibold" id="cdm-tbody"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Add / Edit modal -->
                                <div class="modal fade" id="cdm-modal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered mw-600px">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h3 class="modal-title" id="cdm-modal-title">Add Option</h3>
                                                <div class="btn btn-sm btn-icon" data-bs-dismiss="modal">
                                                    <i class="ki-outline ki-cross fs-1"></i>
                                                </div>
                                            </div>
                                            <div class="modal-body">
                                                <form id="cdm-form">
                                                    <input type="hidden" name="id" value="0" />
                                                    <div class="mb-5">
                                                        <label class="form-label required">Option Name</label>
                                                        <input type="text" name="option_name" class="form-control form-control-solid" required />
                                                    </div>
                                                    <div class="mb-5">
                                                        <label class="form-label">Description</label>
                                                        <input type="text" name="description" class="form-control form-control-solid" />
                                                    </div>
                                                    <div class="row">
                                                        <?php foreach ([
                                                            'exchange_percentage' => 'Exchange Wallet %',
                                                            'earning_percentage'  => 'Earning Wallet %',
                                                            'staking_percentage'  => 'Staking Wallet %',
                                                            'bonus_percentage'    => 'Bonus Wallet %',
                                                        ] as $f => $lbl): ?>
                                                        <div class="col-6 mb-5">
                                                            <label class="form-label required"><?php echo $lbl; ?></label>
                                                            <input type="number" name="<?php echo $f; ?>" step="0.01" min="0" max="100"
                                                                class="form-control form-control-solid cdm-pct" value="0" required />
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <div class="mb-5 fs-7" id="cdm-total-note">Total: 0% — must equal exactly 100%.</div>
                                                    <div class="d-flex gap-8 mb-6">
                                                        <div class="form-check form-switch form-check-custom form-check-solid">
                                                            <input class="form-check-input" type="checkbox" name="status" value="1" checked />
                                                            <label class="form-check-label">Active</label>
                                                        </div>
                                                        <div class="form-check form-switch form-check-custom form-check-solid">
                                                            <input class="form-check-input" type="checkbox" name="is_default" value="1" />
                                                            <label class="form-check-label">Default Option</label>
                                                        </div>
                                                    </div>
                                                    <div class="text-end">
                                                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary" id="cdm-save-btn">Save</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Audit modal -->
                                <div class="modal fade" id="cdm-audit-modal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered mw-900px">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h3 class="modal-title">Coin Distribution Audit Log</h3>
                                                <div class="btn btn-sm btn-icon" data-bs-dismiss="modal">
                                                    <i class="ki-outline ki-cross fs-1"></i>
                                                </div>
                                            </div>
                                            <div class="modal-body scroll-y mh-500px" id="cdm-audit-body">Loading…</div>
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
        let OPTIONS = <?php echo json_encode(array_map(function ($o) {
            return [
                'id' => (int)$o['id'],
                'option_name' => $o['option_name'],
                'description' => $o['description'],
                'exchange_percentage' => (float)$o['exchange_percentage'],
                'earning_percentage'  => (float)$o['earning_percentage'],
                'staking_percentage'  => (float)$o['staking_percentage'],
                'bonus_percentage'    => (float)$o['bonus_percentage'],
                'is_default' => (int)$o['is_default'],
                'status'     => (int)$o['status'],
                'created_at' => $o['created_at'],
            ];
        }, $options)); ?>;

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
        async function post(url, fd) {
            const res = await fetch(base + url, {
                method: 'POST', body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            let j = {};
            try { j = await res.json(); } catch (e) { j = { status: 'error', message: 'Server error.' }; }
            return { ok: res.ok && j.status === 'success', msg: j.message || '' };
        }

        /* ------------------------------ table ------------------------------ */
        const tbody = document.getElementById('cdm-tbody');

        function render() {
            tbody.innerHTML = OPTIONS.map(o => {
                const total = o.exchange_percentage + o.earning_percentage + o.staking_percentage + o.bonus_percentage;
                const actions = [];
                if (isSuper) {
                    actions.push('<button class="btn btn-sm btn-light-primary cdm-edit" data-id="' + o.id + '">Edit</button>');
                    if (!o.is_default) actions.push('<button class="btn btn-sm btn-light-warning cdm-default" data-id="' + o.id + '">Set Default</button>');
                    if (!o.is_default) actions.push('<button class="btn btn-sm btn-light-danger cdm-del" data-id="' + o.id + '">Delete</button>');
                }
                return '<tr>' +
                    '<td>' + o.id + '</td>' +
                    '<td><span class="fw-bold">' + esc(o.option_name) + '</span>' +
                        (o.description ? '<div class="text-muted fs-8">' + esc(o.description) + '</div>' : '') + '</td>' +
                    '<td class="text-end cdm-num">' + o.exchange_percentage + '</td>' +
                    '<td class="text-end cdm-num">' + o.earning_percentage + '</td>' +
                    '<td class="text-end cdm-num">' + o.staking_percentage + '</td>' +
                    '<td class="text-end cdm-num">' + o.bonus_percentage + '</td>' +
                    '<td class="text-end cdm-num fw-bold">' + total + '</td>' +
                    '<td class="text-center">' + (o.is_default
                        ? '<span class="badge badge-light-warning">DEFAULT</span>' : '—') + '</td>' +
                    '<td class="text-center">' +
                        '<div class="form-check form-switch form-check-custom form-check-solid d-inline-block">' +
                        '<input class="form-check-input cdm-toggle" type="checkbox" data-id="' + o.id + '"' +
                        (o.status ? ' checked' : '') + ' /></div></td>' +
                    '<td class="text-muted fs-8">' + esc(o.created_at) + '</td>' +
                    '<td class="text-end">' + (actions.join(' ') || '<span class="text-muted fs-8">view only</span>') + '</td>' +
                    '</tr>';
            }).join('') || '<tr><td colspan="11" class="text-muted">No options match the filters.</td></tr>';
        }
        render();

        /* ------------------------- filters + search ------------------------ */
        function filterQuery() {
            return 'status=' + encodeURIComponent(document.getElementById('cdm-filter-status').value) +
                   '&is_default=' + encodeURIComponent(document.getElementById('cdm-filter-default').value) +
                   '&q=' + encodeURIComponent(document.getElementById('cdm-search').value.trim());
        }
        async function reload() {
            const res = await fetch(base + 'admin/master/coin-distribution/list?' + filterQuery(),
                { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const j = await res.json();
            OPTIONS = (j.rows || []).map(o => ({
                id: Number(o.id), option_name: o.option_name, description: o.description,
                exchange_percentage: Number(o.exchange_percentage), earning_percentage: Number(o.earning_percentage),
                staking_percentage: Number(o.staking_percentage), bonus_percentage: Number(o.bonus_percentage),
                is_default: Number(o.is_default), status: Number(o.status), created_at: o.created_at
            }));
            render();
        }
        document.getElementById('cdm-filter-status').addEventListener('change', reload);
        document.getElementById('cdm-filter-default').addEventListener('change', reload);
        let _t; document.getElementById('cdm-search').addEventListener('keyup', () => {
            clearTimeout(_t); _t = setTimeout(reload, 350);
        });
        document.getElementById('cdm-export').addEventListener('click', (e) => {
            e.preventDefault();
            window.location = base + 'admin/master/coin-distribution/export?' + filterQuery();
        });

        /* ------------------------- add / edit modal ------------------------ */
        const modalEl = document.getElementById('cdm-modal');
        const form = document.getElementById('cdm-form');
        const modal = () => bootstrap.Modal.getOrCreateInstance(modalEl);

        function refreshTotal() {
            const t = ['exchange_percentage','earning_percentage','staking_percentage','bonus_percentage']
                .reduce((s, k) => s + (parseFloat(form.elements[k].value) || 0), 0);
            const note = document.getElementById('cdm-total-note');
            note.textContent = 'Total: ' + t + '% — must equal exactly 100%.';
            note.className = Math.abs(t - 100) < 0.001 ? 'good' : 'bad';
            document.getElementById('cdm-save-btn').disabled = Math.abs(t - 100) >= 0.001;
        }
        document.querySelectorAll('.cdm-pct').forEach(i => i.addEventListener('input', refreshTotal));

        const addBtn = document.getElementById('cdm-add-btn');
        if (addBtn) addBtn.addEventListener('click', () => {
            form.reset();
            form.elements.id.value = 0;
            document.getElementById('cdm-modal-title').textContent = 'Add Option';
            refreshTotal();
            modal().show();
        });

        document.getElementById('cdm-table').addEventListener('click', async (e) => {
            const edit = e.target.closest('.cdm-edit');
            const def  = e.target.closest('.cdm-default');
            const del  = e.target.closest('.cdm-del');

            if (edit) {
                const o = OPTIONS.find(x => x.id === Number(edit.dataset.id));
                form.elements.id.value = o.id;
                form.elements.option_name.value = o.option_name;
                form.elements.description.value = o.description || '';
                form.elements.exchange_percentage.value = o.exchange_percentage;
                form.elements.earning_percentage.value = o.earning_percentage;
                form.elements.staking_percentage.value = o.staking_percentage;
                form.elements.bonus_percentage.value = o.bonus_percentage;
                form.elements.status.checked = !!o.status;
                form.elements.is_default.checked = !!o.is_default;
                document.getElementById('cdm-modal-title').textContent = 'Edit — ' + o.option_name;
                refreshTotal();
                modal().show();
            }

            if (def) {
                if (!confirm('Make this the default option? The previous default is cleared automatically.')) return;
                const r = await post('admin/master/coin-distribution/set-default/' + def.dataset.id, new FormData());
                toast(r.msg, r.ok);
                if (r.ok) reload();
            }

            if (del) {
                if (!confirm('Delete this option? Options already used by purchases can only be disabled.')) return;
                const r = await post('admin/master/coin-distribution/delete/' + del.dataset.id, new FormData());
                toast(r.msg, r.ok);
                if (r.ok) reload();
            }
        });

        /* enable / disable */
        document.getElementById('cdm-table').addEventListener('change', async (e) => {
            const sw = e.target.closest('.cdm-toggle');
            if (!sw) return;
            const fd = new FormData();
            fd.append('active', sw.checked ? 1 : 0);
            const r = await post('admin/master/coin-distribution/toggle/' + sw.dataset.id, fd);
            toast(r.msg, r.ok);
            if (!r.ok) sw.checked = !sw.checked;
        });

        /* save */
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(form);
            fd.set('status', form.elements.status.checked ? 1 : 0);
            fd.set('is_default', form.elements.is_default.checked ? 1 : 0);
            const btn = document.getElementById('cdm-save-btn');
            btn.disabled = true;
            const r = await post('admin/master/coin-distribution/save', fd);
            btn.disabled = false;
            toast(r.msg, r.ok);
            if (r.ok) { modal().hide(); reload(); }
        });

        /* audit log */
        document.getElementById('cdm-audit-btn').addEventListener('click', async () => {
            const m = bootstrap.Modal.getOrCreateInstance(document.getElementById('cdm-audit-modal'));
            const body = document.getElementById('cdm-audit-body');
            body.innerHTML = 'Loading…';
            m.show();
            const res = await fetch(base + 'admin/master/coin-distribution/audit',
                { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const j = await res.json();
            const rows = (j.rows || []).map(r =>
                '<tr>' +
                '<td>' + esc(r.option_name || ('#' + (r.option_id || '—'))) + '</td>' +
                '<td><span class="badge badge-light-info text-uppercase">' + esc(r.action) + '</span></td>' +
                '<td class="fs-8 text-muted mw-300px text-truncate">' + esc(r.old_value || '—') + '</td>' +
                '<td class="fs-8 text-muted mw-300px text-truncate">' + esc(r.new_value || '—') + '</td>' +
                '<td>' + esc(r.admin_name || ('#' + r.changed_by)) + '</td>' +
                '<td class="text-muted fs-8">' + esc(r.created_at) + '</td>' +
                '</tr>').join('');
            body.innerHTML = rows
                ? '<table class="table table-row-dashed fs-7"><thead><tr class="fw-bold text-muted">' +
                  '<th>Option</th><th>Action</th><th>Old</th><th>New</th><th>By</th><th>When</th>' +
                  '</tr></thead><tbody>' + rows + '</tbody></table>'
                : '<div class="text-muted">No changes recorded yet.</div>';
        });
    })();
    </script>
</body>

</html>
