<?php $this->load->view('admin/Layout/common_style'); ?>

<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />

<style>
    #stk-ceiling-table input.stk-ceiling {
        width: 130px; text-align: right; font-variant-numeric: tabular-nums;
    }
    #stk-ceiling-table input.stk-ceiling.stk-dirty {
        background-color: #fff8dd; border-color: #f6c000;
    }
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

                                <div class="row g-5">

                                    <!-- §11 Rank Power rules -->
                                    <div class="col-lg-6">
                                        <div class="card h-100">
                                            <div class="card-header border-transparent pt-5">
                                                <h3 class="card-title fw-bold">Rank Power System (§11)</h3>
                                            </div>
                                            <div class="card-body pt-2">
                                                <div class="text-muted fs-7 mb-6">
                                                    Rank Power is <b>separate from the permanent Achievement Rank</b>:
                                                    it is re-earned inside each cycle, <b>resets when the cycle
                                                    ends</b> (proposal: every 60 days) and decides whether a member
                                                    <b>qualifies for the group incentive</b> in that cycle.
                                                </div>
                                                <form id="stk-power-form">
                                                    <div class="form-check form-switch form-check-custom form-check-solid mb-6">
                                                        <input class="form-check-input" type="checkbox" name="is_enabled" value="1"
                                                            <?php echo !empty($settings['is_enabled']) ? 'checked' : ''; ?> />
                                                        <label class="form-check-label fw-semibold">Rank Power system enabled</label>
                                                    </div>
                                                    <div class="mb-6">
                                                        <label class="form-label fw-semibold required">Reset cycle (days)</label>
                                                        <input type="number" name="cycle_days" min="1" max="365"
                                                            class="form-control form-control-solid w-150px"
                                                            value="<?php echo (int)$settings['cycle_days']; ?>" required />
                                                        <div class="text-muted fs-8 mt-1">Proposal §11: 60 days.</div>
                                                    </div>
                                                    <div class="form-check form-switch form-check-custom form-check-solid mb-6">
                                                        <input class="form-check-input" type="checkbox" name="controls_group_incentive" value="1"
                                                            <?php echo !empty($settings['controls_group_incentive']) ? 'checked' : ''; ?> />
                                                        <label class="form-check-label fw-semibold">Controls group-incentive qualification</label>
                                                    </div>
                                                    <div class="mb-6">
                                                        <label class="form-label fw-semibold">Minimum power tier to qualify</label>
                                                        <select name="min_power_tier" class="form-select form-select-solid w-250px">
                                                            <?php foreach ($ranks as $r): ?>
                                                                <option value="<?php echo (int)$r['tier_level']; ?>"
                                                                    <?php echo (int)$settings['min_power_tier'] === (int)$r['tier_level'] ? 'selected' : ''; ?>>
                                                                    Tier <?php echo (int)$r['tier_level']; ?> — <?php echo html_escape($r['name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <div class="text-muted fs-8 mt-1">Tier 0 (UN RANK) = every member with a power rank qualifies.</div>
                                                    </div>
                                                    <div class="form-check form-switch form-check-custom form-check-solid mb-6">
                                                        <input class="form-check-input" type="checkbox" name="auto_open_next_cycle" value="1"
                                                            <?php echo !empty($settings['auto_open_next_cycle']) ? 'checked' : ''; ?> />
                                                        <label class="form-check-label fw-semibold">Auto-open next cycle when one ends (cron)</label>
                                                    </div>
                                                    <div class="text-end">
                                                        <button type="submit" class="btn btn-primary btn-sm">Save Settings</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- §11 cycle management -->
                                    <div class="col-lg-6">
                                        <div class="card h-100">
                                            <div class="card-header border-transparent pt-5">
                                                <h3 class="card-title fw-bold">Power Cycle (60-day reset)</h3>
                                                <div class="card-toolbar">
                                                    <button type="button" class="btn btn-light-danger btn-sm" id="stk-cycle-reset">
                                                        <?php echo $cycle ? 'Reset Now (close & start next)' : 'Start First Cycle'; ?>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body pt-2">
                                                <?php if ($cycle): ?>
                                                    <div class="alert alert-primary d-flex flex-column py-4 mb-6">
                                                        <span class="fw-bold">Current cycle: #<?php echo (int)$cycle['cycle_no']; ?></span>
                                                        <span class="fs-7"><?php echo html_escape($cycle['start_date']); ?> →
                                                            <?php echo html_escape($cycle['end_date']); ?>
                                                            (<?php
                                                                $left = (int)ceil((strtotime($cycle['end_date']) - time()) / 86400);
                                                                echo $left >= 0 ? $left.' day(s) left' : 'overdue — reset pending';
                                                            ?>)
                                                        </span>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="alert alert-warning py-4 mb-6 fs-7">
                                                        No cycle is open yet. Start the first cycle to begin tracking
                                                        Rank Power; each reset closes the running cycle (clearing
                                                        everyone's power rank) and opens the next window.
                                                    </div>
                                                <?php endif; ?>

                                                <label class="form-label fw-semibold">Cycle history</label>
                                                <div class="table-responsive mh-300px scroll-y">
                                                    <table class="table table-row-dashed fs-7 gy-2">
                                                        <thead>
                                                            <tr class="text-gray-500 fw-bold fs-8 text-uppercase">
                                                                <th>#</th><th>Window</th><th>Status</th><th class="text-end">Members ranked</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="text-gray-700">
                                                            <?php if (!$cycles): ?>
                                                                <tr><td colspan="4" class="text-muted">No cycles yet.</td></tr>
                                                            <?php endif; ?>
                                                            <?php foreach ($cycles as $c): ?>
                                                            <tr>
                                                                <td><?php echo (int)$c['cycle_no']; ?></td>
                                                                <td><?php echo html_escape($c['start_date']); ?> → <?php echo html_escape($c['end_date']); ?></td>
                                                                <td>
                                                                    <span class="badge badge-light-<?php echo $c['status'] === 'open' ? 'success' : 'secondary'; ?>">
                                                                        <?php echo html_escape($c['status']); ?>
                                                                    </span>
                                                                </td>
                                                                <td class="text-end"><?php echo (int)$c['user_count']; ?></td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- §12 Group Incentive Ceiling -->
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header border-transparent pt-5">
                                                <h3 class="card-title fw-bold">Group Incentive Ceiling (§12)</h3>
                                                <div class="card-toolbar">
                                                    <button type="button" class="btn btn-primary btn-sm" id="stk-ceiling-save">Save Ceilings</button>
                                                </div>
                                            </div>
                                            <div class="card-body pt-2 pb-9">
                                                <div class="text-muted fs-7 mb-5">
                                                    Per-stake cap on the group incentive a member can earn (proposal
                                                    §12). Stored on the package — the same value is visible on the
                                                    <a href="<?php echo base_url(); ?>admin/staking/packages">Packages</a>
                                                    screen. Edited cells turn amber until saved.
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table align-middle table-row-dashed fs-6 gy-3 w-auto" id="stk-ceiling-table">
                                                        <thead>
                                                            <tr class="text-gray-500 fw-bold fs-7 text-uppercase">
                                                                <th>Stake (BMAN)</th>
                                                                <th class="text-end">Ceiling (BMAN)</th>
                                                                <th></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="text-gray-700 fw-semibold">
                                                            <?php foreach ($packages as $p): ?>
                                                            <tr>
                                                                <td class="pe-10">
                                                                    <?php echo number_format((float)$p['stake_amount']); ?>
                                                                    <?php if (!$p['is_active']): ?>
                                                                        <span class="badge badge-light-danger fs-9 ms-1">disabled</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td class="text-end">
                                                                    <input type="number" step="0.0001" min="0"
                                                                        class="form-control form-control-sm d-inline-block stk-ceiling"
                                                                        data-id="<?php echo (int)$p['id']; ?>"
                                                                        data-orig="<?php echo (float)$p['group_ceiling']; ?>"
                                                                        value="<?php echo (float)$p['group_ceiling']; ?>" />
                                                                </td>
                                                                <td class="text-muted fs-8 ps-4">
                                                                    <?php echo (float)$p['group_ceiling'] < (float)$p['stake_amount']
                                                                        ? 'capped below stake' : 'equals stake'; ?>
                                                                </td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
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

        /* ---- §11 settings ---- */
        const form = document.getElementById('stk-power-form');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData();
            fd.append('is_enabled', form.elements.is_enabled.checked ? 1 : 0);
            fd.append('cycle_days', form.elements.cycle_days.value);
            fd.append('controls_group_incentive', form.elements.controls_group_incentive.checked ? 1 : 0);
            fd.append('min_power_tier', form.elements.min_power_tier.value);
            fd.append('auto_open_next_cycle', form.elements.auto_open_next_cycle.checked ? 1 : 0);
            const r = await post('admin/staking/rank-power/save-settings', fd);
            toast(r.msg, r.ok);
        });

        /* ---- §11 cycle reset ---- */
        document.getElementById('stk-cycle-reset').addEventListener('click', async () => {
            if (!confirm('Close the current cycle (everyone\'s power rank resets) and open the next one?')) return;
            const r = await post('admin/staking/rank-power/reset-cycle', new FormData());
            toast(r.msg, r.ok);
            if (r.ok) setTimeout(() => location.reload(), 700);
        });

        /* ---- §12 ceilings ---- */
        document.querySelectorAll('.stk-ceiling').forEach(inp => {
            inp.addEventListener('input', () => {
                inp.classList.toggle('stk-dirty', inp.value !== inp.dataset.orig);
            });
        });
        document.getElementById('stk-ceiling-save').addEventListener('click', async () => {
            const rows = {};
            let dirty = 0;
            document.querySelectorAll('.stk-ceiling.stk-dirty').forEach(inp => {
                if (inp.value === '') return;
                rows[inp.dataset.id] = inp.value;
                dirty++;
            });
            if (!dirty) { toast('No changes to save.', false); return; }
            const fd = new FormData();
            fd.append('ceilings', JSON.stringify(rows));
            const r = await post('admin/staking/rank-power/save-ceilings', fd);
            toast(r.msg, r.ok);
            if (r.ok) setTimeout(() => location.reload(), 700);
        });
    })();
    </script>
</body>

</html>
