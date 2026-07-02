<?php $this->load->view('admin/Layout/common_style'); ?>

<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />

<style>
    .stk-drag { cursor: grab; color: #99a1b7; }
    .stk-num { font-variant-numeric: tabular-nums; }
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
                                        <div class="card-toolbar">
                                            <button type="button" class="btn btn-primary btn-sm" id="stk-add-btn">
                                                <i class="ki-duotone ki-plus fs-2"></i> Add Package
                                            </button>
                                        </div>
                                    </div>

                                    <div class="card-body pt-3 pb-9">
                                        <div class="text-muted fs-7 mb-5">
                                            Each package is a fixed BMAN stake amount. Bonus % is the staking bonus
                                            coin credited on purchase (proposal §7, default 25%). Ceiling is the
                                            group-incentive ceiling (§12). 
                                        </div>

                                        <table class="table align-middle table-row-dashed fs-6 gy-4" id="stk-pkg-table">
                                            <thead>
                                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">                                                    
                                                    <th>S:No</th>
                                                    <th>Name</th>
                                                    <th class="text-end">Stake (BMAN)</th>
                                                    <th class="text-end">Bonus %</th>
                                                    <th class="text-end">Group Ceiling</th>
                                                    <th class="text-center">Active</th>
                                                    <th class="text-end">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="text-gray-700 fw-semibold">
                                                <?php foreach ($packages as $key => $p): ?>
                                                <tr data-id="<?php echo (int)$p['id']; ?>"
                                                    data-name="<?php echo html_escape($p['name']); ?>"
                                                    data-amount="<?php echo (float)$p['stake_amount']; ?>"
                                                    data-bonus="<?php echo (float)$p['bonus_percent']; ?>"
                                                    data-ceiling="<?php echo (float)$p['group_ceiling']; ?>">
                                                    <td class="text-center"><?php echo $key + 1; ?></td>
                                                    <td><?php echo html_escape($p['name']); ?></td>
                                                    <td class="text-end stk-num"><?php echo number_format((float)$p['stake_amount']); ?></td>
                                                    <td class="text-end stk-num"><?php echo rtrim(rtrim(number_format((float)$p['bonus_percent'], 2), '0'), '.'); ?></td>
                                                    <td class="text-end stk-num"><?php echo number_format((float)$p['group_ceiling']); ?></td>
                                                    <td class="text-center">
                                                        <div class="form-check form-switch form-check-custom form-check-solid d-inline-block">
                                                            <input class="form-check-input stk-toggle" type="checkbox"
                                                                <?php echo $p['is_active'] ? 'checked' : ''; ?> />
                                                        </div>
                                                    </td>
                                                    <td class="text-end">
                                                        <button class="btn btn-sm btn-light-primary stk-edit">Edit</button>
                                                        <button class="btn btn-sm btn-light-danger stk-delete">Delete</button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Add / Edit modal -->
                                <div class="modal fade" id="stk-pkg-modal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered mw-550px">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h3 class="modal-title" id="stk-pkg-modal-title">Add Package</h3>
                                                <div class="btn btn-sm btn-icon" data-bs-dismiss="modal">
                                                    <i class="ki-outline ki-cross fs-1"></i>
                                                </div>
                                            </div>
                                            <div class="modal-body">
                                                <form id="stk-pkg-form">
                                                    <input type="hidden" name="id" value="0" />
                                                    <div class="mb-5">
                                                        <label class="form-label required">Name</label>
                                                        <input type="text" name="name" class="form-control form-control-solid"
                                                            placeholder="e.g. 5,000 BMAN" required />
                                                    </div>
                                                    <div class="mb-5">
                                                        <label class="form-label required">Stake amount (BMAN)</label>
                                                        <input type="number" name="stake_amount" step="0.0001" min="0.0001"
                                                            class="form-control form-control-solid" required />
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-6 mb-5">
                                                            <label class="form-label required">Bonus %</label>
                                                            <input type="number" name="bonus_percent" step="0.01" min="0"
                                                                value="25" class="form-control form-control-solid" required />
                                                        </div>
                                                        <div class="col-6 mb-5">
                                                            <label class="form-label required">Group ceiling</label>
                                                            <input type="number" name="group_ceiling" step="0.0001" min="0"
                                                                value="0" class="form-control form-control-solid" required />
                                                        </div>
                                                    </div>
                                                    <div class="text-end">
                                                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary" id="stk-pkg-save">Save</button>
                                                    </div>
                                                </form>
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

    <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
        <i class="ki-duotone ki-arrow-up"><span class="path1"></span><span class="path2"></span></i>
    </div>

    <?php $this->load->view('admin/Layout/common_script'); ?>
    <script src="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.js"></script>

    <script>
    (function () {
        const base = '<?php echo base_url(); ?>';
        const table = document.getElementById('stk-pkg-table');
        const modalEl = document.getElementById('stk-pkg-modal');
        const form = document.getElementById('stk-pkg-form');
        const modal = () => bootstrap.Modal.getOrCreateInstance(modalEl);

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

        // ---- add ----
        document.getElementById('stk-add-btn').addEventListener('click', () => {
            form.reset();
            form.elements.id.value = 0;
            form.elements.bonus_percent.value = 25;
            form.elements.group_ceiling.value = 0;
            document.getElementById('stk-pkg-modal-title').textContent = 'Add Package';
            modal().show();
        });

        // ---- row actions ----
        table.addEventListener('click', async (e) => {
            const tr = e.target.closest('tr[data-id]');
            if (!tr) return;
            const id = tr.dataset.id;

            if (e.target.closest('.stk-edit')) {
                form.elements.id.value = id;
                form.elements.name.value = tr.dataset.name;
                form.elements.stake_amount.value = tr.dataset.amount;
                form.elements.bonus_percent.value = tr.dataset.bonus;
                form.elements.group_ceiling.value = tr.dataset.ceiling;
                document.getElementById('stk-pkg-modal-title').textContent = 'Edit Package';
                modal().show();
            }

            if (e.target.closest('.stk-delete')) {
                if (!confirm('Delete this package? Packages with stakes can only be disabled.')) return;
                const r = await post('admin/staking/packages/delete/' + id, new FormData());
                toast(r.msg, r.ok);
                if (r.ok) tr.remove();
            }

            if (e.target.closest('.stk-up') || e.target.closest('.stk-down')) {
                const up = !!e.target.closest('.stk-up');
                if (up && tr.previousElementSibling) tr.parentNode.insertBefore(tr, tr.previousElementSibling);
                if (!up && tr.nextElementSibling) tr.parentNode.insertBefore(tr.nextElementSibling, tr);
                const fd = new FormData();
                table.querySelectorAll('tbody tr[data-id]').forEach(row => fd.append('ids[]', row.dataset.id));
                await post('admin/staking/packages/reorder', fd);
            }
        });

        // ---- enable / disable ----
        table.addEventListener('change', async (e) => {
            const sw = e.target.closest('.stk-toggle');
            if (!sw) return;
            const tr = e.target.closest('tr[data-id]');
            const fd = new FormData();
            fd.append('active', sw.checked ? 1 : 0);
            const r = await post('admin/staking/packages/toggle/' + tr.dataset.id, fd);
            if (!r.ok) { sw.checked = !sw.checked; toast(r.msg, false); }
        });

        // ---- save (add / edit) ----
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('stk-pkg-save');
            btn.disabled = true;
            const r = await post('admin/staking/packages/save', new FormData(form));
            btn.disabled = false;
            toast(r.msg, r.ok);
            if (r.ok) { modal().hide(); setTimeout(() => location.reload(), 600); }
        });
    })();
    </script>
</body>

</html>
