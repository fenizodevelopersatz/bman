<?php $this->load->view('admin/Layout/common_style'); ?>

<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />

<style>
    .stk-badge-dot { display: inline-block; width: 14px; height: 14px; border-radius: 50%; vertical-align: middle; border: 1px solid rgba(0,0,0,.15); }
    .stk-req-pill { font-size: .8rem; }
    #stk-req-editor .stk-req-row { display: flex; gap: 8px; align-items: center; margin-bottom: 8px; }
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
                                    </div>

                                    <div class="card-body pt-3 pb-9">
                                        <div class="text-muted fs-7 mb-5">
                                            The 11 <b>permanent ranks</b> from the proposal (§10). Each rank pays a
                                            group incentive and can be reached via up to three alternative
                                            qualification plans (left/right counts of lower-ranked members —
                                            options inside one plan are OR-ed). Benefits: Badge, Certificate,
                                            Reward, Recognition.
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table align-middle table-row-dashed fs-6 gy-4" id="stk-rank-table">
                                                <thead>
                                                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                                        <th class="w-50px">Tier</th>
                                                        <th>Rank</th>
                                                        <th class="text-end">Group Incentive</th>
                                                        <th>Qualification (Plan-1 / Plan-2 / Plan-3)</th>
                                                        <th class="text-center">Benefits</th>
                                                        <th class="text-center">Active</th>
                                                        <th class="text-end">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="text-gray-700 fw-semibold">
                                                    <?php foreach ($ranks as $r): ?>
                                                    <tr data-id="<?php echo (int)$r['id']; ?>">
                                                        <td><?php echo (int)$r['tier_level']; ?></td>
                                                        <td>
                                                            <span class="stk-badge-dot me-2" style="background: <?php echo html_escape($r['badge_color'] ?: '#ccc'); ?>"></span>
                                                            <span class="fw-bold"><?php echo html_escape($r['name']); ?></span>
                                                        </td>
                                                        <td class="text-end"><?php echo number_format((float)$r['group_incentive']); ?></td>
                                                        <td>
                                                            <?php
                                                            $byPlan = [];
                                                            foreach ($r['requirements'] as $q) $byPlan[$q['plan_no']][$q['option_no']][] = $q;
                                                            if (!$byPlan) echo '<span class="text-muted fs-8">— default rank, no requirements —</span>';
                                                            foreach ($byPlan as $pn => $opts) {
                                                                $optTexts = [];
                                                                foreach ($opts as $conds) {
                                                                    $parts = [];
                                                                    foreach ($conds as $c) {
                                                                        $parts[] = ucfirst($c['side']).' '.(int)$c['required_qty'].' '.html_escape($c['required_rank_name']);
                                                                    }
                                                                    $optTexts[] = implode(' + ', $parts);
                                                                }
                                                                echo '<div class="stk-req-pill"><span class="badge badge-light-primary me-1">P'.$pn.'</span>'
                                                                    .implode(' <span class="text-muted">or</span> ', $optTexts).'</div>';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <?php
                                                            $b = [];
                                                            if ($r['benefit_badge'])       $b[] = 'Badge';
                                                            if ($r['benefit_certificate']) $b[] = 'Certificate';
                                                            if ($r['benefit_reward'])      $b[] = 'Reward';
                                                            if ($r['benefit_recognition']) $b[] = 'Recognition';
                                                            echo $b ? '<span class="fs-8 text-muted">'.implode(' · ', $b).'</span>'
                                                                    : '<span class="fs-8 text-muted">—</span>';
                                                            ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="form-check form-switch form-check-custom form-check-solid d-inline-block">
                                                                <input class="form-check-input stk-rank-toggle" type="checkbox"
                                                                    <?php echo $r['is_active'] ? 'checked' : ''; ?> />
                                                            </div>
                                                        </td>
                                                        <td class="text-end">
                                                            <button class="btn btn-sm btn-light-primary stk-rank-edit">Edit</button>
                                                            <button class="btn btn-sm btn-light-info stk-rank-req">Requirements</button>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Edit rank modal -->
                                <div class="modal fade" id="stk-rank-modal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered mw-550px">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h3 class="modal-title" id="stk-rank-modal-title">Edit Rank</h3>
                                                <div class="btn btn-sm btn-icon" data-bs-dismiss="modal">
                                                    <i class="ki-outline ki-cross fs-1"></i>
                                                </div>
                                            </div>
                                            <div class="modal-body">
                                                <form id="stk-rank-form">
                                                    <input type="hidden" name="id" value="0" />
                                                    <div class="mb-5">
                                                        <label class="form-label required">Group incentive (BMAN)</label>
                                                        <input type="number" name="group_incentive" step="0.0001" min="0"
                                                            class="form-control form-control-solid" required />
                                                    </div>
                                                    <div class="mb-5">
                                                        <label class="form-label">Badge colour</label>
                                                        <input type="color" name="badge_color" class="form-control form-control-solid form-control-color" value="#cccccc" />
                                                    </div>
                                                    <label class="form-label">Benefits</label>
                                                    <div class="d-flex flex-wrap gap-6 mb-6">
                                                        <?php foreach ([
                                                            'benefit_badge' => 'Rank Badge',
                                                            'benefit_certificate' => 'Rank Certificate',
                                                            'benefit_reward' => 'Rank Reward',
                                                            'benefit_recognition' => 'Rank Recognition',
                                                        ] as $k => $lbl): ?>
                                                        <div class="form-check form-check-custom form-check-solid">
                                                            <input class="form-check-input" type="checkbox" name="<?php echo $k; ?>" value="1" />
                                                            <label class="form-check-label"><?php echo $lbl; ?></label>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <div class="text-end">
                                                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Save</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Requirements editor modal -->
                                <div class="modal fade" id="stk-req-modal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered mw-750px">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h3 class="modal-title" id="stk-req-title">Qualification Requirements</h3>
                                                <div class="btn btn-sm btn-icon" data-bs-dismiss="modal">
                                                    <i class="ki-outline ki-cross fs-1"></i>
                                                </div>
                                            </div>
                                            <div class="modal-body">
                                                <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6" id="stk-req-tabs">
                                                    <li class="nav-item"><a class="nav-link active" data-plan="1" href="javascript:;">Plan-1</a></li>
                                                    <li class="nav-item"><a class="nav-link" data-plan="2" href="javascript:;">Plan-2</a></li>
                                                    <li class="nav-item"><a class="nav-link" data-plan="3" href="javascript:;">Plan-3</a></li>
                                                </ul>
                                                <div class="text-muted fs-8 mb-4">
                                                    Conditions in the same option are AND-ed (Left + Right); different
                                                    options inside a plan are OR alternatives. Plans themselves are OR
                                                    routes to the rank.
                                                </div>
                                                <div id="stk-req-editor"></div>
                                                <div class="d-flex justify-content-between mt-5">
                                                    <button type="button" class="btn btn-light btn-sm" id="stk-req-add">+ Add condition</button>
                                                    <button type="button" class="btn btn-primary btn-sm" id="stk-req-save">Save Plan</button>
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
        const RANKS = <?php echo json_encode(array_map(function ($r) {
            return [
                'id' => (int)$r['id'],
                'tier' => (int)$r['tier_level'],
                'name' => $r['name'],
                'group_incentive' => (float)$r['group_incentive'],
                'badge_color' => $r['badge_color'] ?: '#cccccc',
                'benefit_badge' => (int)$r['benefit_badge'],
                'benefit_certificate' => (int)$r['benefit_certificate'],
                'benefit_reward' => (int)$r['benefit_reward'],
                'benefit_recognition' => (int)$r['benefit_recognition'],
                'requirements' => array_map(function ($q) {
                    return [
                        'plan_no' => (int)$q['plan_no'],
                        'option_no' => (int)$q['option_no'],
                        'side' => $q['side'],
                        'required_qty' => (int)$q['required_qty'],
                        'required_rank_id' => (int)$q['required_rank_id'],
                    ];
                }, $r['requirements']),
            ];
        }, $ranks)); ?>;

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

        const rankById = id => RANKS.find(r => r.id === Number(id));
        const table = document.getElementById('stk-rank-table');

        /* ---------------- edit rank (incentive + benefits) ---------------- */
        const rankModalEl = document.getElementById('stk-rank-modal');
        const rankForm = document.getElementById('stk-rank-form');

        table.addEventListener('click', (e) => {
            const tr = e.target.closest('tr[data-id]');
            if (!tr) return;

            if (e.target.closest('.stk-rank-edit')) {
                const r = rankById(tr.dataset.id);
                rankForm.elements.id.value = r.id;
                rankForm.elements.group_incentive.value = r.group_incentive;
                rankForm.elements.badge_color.value = r.badge_color;
                rankForm.elements.benefit_badge.checked = !!r.benefit_badge;
                rankForm.elements.benefit_certificate.checked = !!r.benefit_certificate;
                rankForm.elements.benefit_reward.checked = !!r.benefit_reward;
                rankForm.elements.benefit_recognition.checked = !!r.benefit_recognition;
                document.getElementById('stk-rank-modal-title').textContent = 'Edit Rank — ' + r.name;
                bootstrap.Modal.getOrCreateInstance(rankModalEl).show();
            }

            if (e.target.closest('.stk-rank-req')) {
                openReqEditor(Number(tr.dataset.id));
            }
        });

        table.addEventListener('change', async (e) => {
            const sw = e.target.closest('.stk-rank-toggle');
            if (!sw) return;
            const tr = e.target.closest('tr[data-id]');
            const fd = new FormData();
            fd.append('active', sw.checked ? 1 : 0);
            const r = await post('admin/staking/ranks/toggle/' + tr.dataset.id, fd);
            if (!r.ok) { sw.checked = !sw.checked; toast(r.msg, false); }
        });

        rankForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData();
            fd.append('group_incentive', rankForm.elements.group_incentive.value);
            fd.append('badge_color', rankForm.elements.badge_color.value);
            ['benefit_badge','benefit_certificate','benefit_reward','benefit_recognition'].forEach(k => {
                fd.append(k, rankForm.elements[k].checked ? 1 : 0);
            });
            const r = await post('admin/staking/ranks/save/' + rankForm.elements.id.value, fd);
            toast(r.msg, r.ok);
            if (r.ok) { bootstrap.Modal.getOrCreateInstance(rankModalEl).hide(); setTimeout(() => location.reload(), 600); }
        });

        /* ---------------- requirements editor ---------------- */
        let reqRankId = 0, reqPlan = 1;
        const reqModalEl = document.getElementById('stk-req-modal');
        const editor = document.getElementById('stk-req-editor');

        function rankOptions(selected, excludeId) {
            return RANKS.filter(r => r.id !== excludeId).map(r =>
                '<option value="' + r.id + '"' + (r.id === selected ? ' selected' : '') + '>' +
                r.name + ' (T' + r.tier + ')</option>').join('');
        }

        function reqRowHtml(row) {
            row = row || { option_no: 1, side: 'left', required_qty: 1, required_rank_id: RANKS[0].id };
            return '<div class="stk-req-row">' +
                '<span class="text-muted fs-8">Option</span>' +
                '<input type="number" min="1" class="form-control form-control-sm w-70px stk-r-opt" value="' + row.option_no + '" />' +
                '<select class="form-select form-select-sm w-100px stk-r-side">' +
                    '<option value="left"' + (row.side === 'left' ? ' selected' : '') + '>Left</option>' +
                    '<option value="right"' + (row.side === 'right' ? ' selected' : '') + '>Right</option>' +
                '</select>' +
                '<input type="number" min="1" class="form-control form-control-sm w-80px stk-r-qty" value="' + row.required_qty + '" />' +
                '<span class="text-muted fs-8">×</span>' +
                '<select class="form-select form-select-sm stk-r-rank">' + rankOptions(row.required_rank_id, reqRankId) + '</select>' +
                '<button type="button" class="btn btn-icon btn-sm btn-light-danger stk-r-del">✕</button>' +
                '</div>';
        }

        function renderReqEditor() {
            const r = rankById(reqRankId);
            const rows = r.requirements.filter(q => q.plan_no === reqPlan);
            editor.innerHTML = rows.length
                ? rows.map(reqRowHtml).join('')
                : '<div class="text-muted fs-8 mb-3">No conditions for this plan yet — add one, or leave empty (plan unused).</div>';
        }

        function openReqEditor(rankId) {
            reqRankId = rankId; reqPlan = 1;
            document.querySelectorAll('#stk-req-tabs .nav-link').forEach(a => a.classList.toggle('active', a.dataset.plan === '1'));
            document.getElementById('stk-req-title').textContent =
                'Qualification Requirements — ' + rankById(rankId).name;
            renderReqEditor();
            bootstrap.Modal.getOrCreateInstance(reqModalEl).show();
        }

        document.querySelectorAll('#stk-req-tabs .nav-link').forEach(a => {
            a.addEventListener('click', () => {
                reqPlan = Number(a.dataset.plan);
                document.querySelectorAll('#stk-req-tabs .nav-link').forEach(x => x.classList.toggle('active', x === a));
                renderReqEditor();
            });
        });

        document.getElementById('stk-req-add').addEventListener('click', () => {
            const empty = editor.querySelector('.text-muted');
            if (empty) editor.innerHTML = '';
            editor.insertAdjacentHTML('beforeend', reqRowHtml(null));
        });

        editor.addEventListener('click', (e) => {
            if (e.target.closest('.stk-r-del')) e.target.closest('.stk-req-row').remove();
        });

        document.getElementById('stk-req-save').addEventListener('click', async () => {
            const rows = [];
            editor.querySelectorAll('.stk-req-row').forEach(div => {
                rows.push({
                    option_no: div.querySelector('.stk-r-opt').value,
                    side: div.querySelector('.stk-r-side').value,
                    required_qty: div.querySelector('.stk-r-qty').value,
                    required_rank_id: div.querySelector('.stk-r-rank').value
                });
            });
            const fd = new FormData();
            fd.append('plan_no', reqPlan);
            fd.append('rows', JSON.stringify(rows));
            const r = await post('admin/staking/ranks/requirements/' + reqRankId, fd);
            toast(r.msg, r.ok);
            if (r.ok) setTimeout(() => location.reload(), 600);
        });
    })();
    </script>
</body>

</html>
