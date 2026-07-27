<?php
$intro = 'Every rank promotion ever recorded, newest first. Ranks are <b>permanent</b> — '
       . 'there are no downgrade rows here by design, so this is the complete lifetime '
       . 'ladder for every member. <code>Plan</code> shows which qualification route the '
       . 'member actually matched.';
$card_tools = '<div class="card-toolbar rk-noprint">'
    . '<a href="' . base_url('admin/staking/rank-reports?report=progress') . '" class="btn btn-sm btn-light-primary">Rank Progress report</a>'
    . '</div>';
$this->load->view('admin/staking/_rank_head', ['title' => $title, 'card_tilte' => $card_tilte,
                                               'intro' => $intro, 'card_tools' => $card_tools]);
?>

<!-- filters -->
<form method="get" class="rk-filters row g-3 align-items-end mb-6 rk-noprint">
    <div class="col-auto">
        <label class="form-label fs-8 text-muted mb-1">Member</label>
        <input type="text" name="q" class="form-control form-control-sm" placeholder="username / email"
               value="<?php echo html_escape($filters['q'] ?? ''); ?>">
    </div>
    <div class="col-auto">
        <label class="form-label fs-8 text-muted mb-1">Rank achieved</label>
        <select name="rank_id" class="form-select form-select-sm">
            <option value="">All ranks</option>
            <?php foreach ($ranks as $r): ?>
                <option value="<?php echo (int)$r['id']; ?>"
                    <?php echo (isset($filters['rank_id']) && (int)$filters['rank_id'] === (int)$r['id']) ? 'selected' : ''; ?>>
                    <?php echo html_escape($r['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label fs-8 text-muted mb-1">Source</label>
        <select name="source" class="form-select form-select-sm">
            <option value="">Any</option>
            <?php foreach (['cron' => 'Cron', 'admin' => 'Admin', 'manual' => 'Manual'] as $k => $v): ?>
                <option value="<?php echo $k; ?>" <?php echo (($filters['source'] ?? '') === $k) ? 'selected' : ''; ?>>
                    <?php echo $v; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label fs-8 text-muted mb-1">From</label>
        <input type="date" name="from" class="form-control form-control-sm"
               value="<?php echo html_escape($filters['from'] ?? ''); ?>">
    </div>
    <div class="col-auto">
        <label class="form-label fs-8 text-muted mb-1">To</label>
        <input type="date" name="to" class="form-control form-control-sm"
               value="<?php echo html_escape($filters['to'] ?? ''); ?>">
    </div>
    <div class="col-auto">
        <button class="btn btn-sm btn-primary">Filter</button>
        <a href="<?php echo base_url('admin/staking/rank-history'); ?>" class="btn btn-sm btn-light">Reset</a>
    </div>
</form>

<div class="table-responsive">
    <table class="table align-middle table-row-dashed fs-6 gy-4">
        <thead>
            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                <th>Member</th>
                <th>From</th>
                <th>Achieved</th>
                <th>Plan matched</th>
                <th class="text-end">Earning vol</th>
                <th class="text-end">Staking vol</th>
                <th class="text-end">Total vol</th>
                <th>Source</th>
                <th>When</th>
                <th class="text-end rk-noprint"></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="10" class="text-center text-muted py-10">
                No promotions recorded yet. The hourly rank cron writes a row here each time a member moves up.
            </td></tr>
        <?php else: foreach ($rows as $h): ?>
            <tr>
                <td>
                    <div class="d-flex flex-column">
                        <span class="fw-bold text-gray-800"><?php echo html_escape($h['username'] ?: ('User #' . $h['user_id'])); ?></span>
                        <span class="text-muted fs-8"><?php echo html_escape($h['email'] ?: ''); ?></span>
                    </div>
                </td>
                <td><span class="text-muted"><?php echo html_escape($h['old_rank'] ?: 'Unranked'); ?></span></td>
                <td>
                    <?php if (!empty($h['badge_image'])): ?>
                        <img src="<?php echo base_url($h['badge_image']); ?>" class="rk-badge" alt="">
                    <?php else: ?>
                        <span class="rk-dot" style="background:<?php echo html_escape($h['badge_color'] ?: '#ccc'); ?>"></span>
                    <?php endif; ?>
                    <span class="fw-bold"><?php echo html_escape($h['new_rank']); ?></span>
                    <span class="badge badge-light-secondary ms-1">T<?php echo (int)$h['tier_level']; ?></span>
                </td>
                <td><span class="badge badge-light-info"><?php echo html_escape($h['qualification_plan'] ?: '—'); ?></span></td>
                <td class="rk-num rk-mono"><?php echo isset($h['earning_volume']) ? number_format((float)$h['earning_volume'], 2) : '<span class="text-muted" title="Recorded before the rank-volume-source fix">—</span>'; ?></td>
                <td class="rk-num rk-mono"><?php echo isset($h['staking_volume']) ? number_format((float)$h['staking_volume'], 2) : '<span class="text-muted" title="Recorded before the rank-volume-source fix">—</span>'; ?></td>
                <td class="rk-num rk-mono fw-bold"><?php echo number_format((float)$h['achieved_volume'], 2); ?></td>
                <td><span class="badge badge-light"><?php echo html_escape($h['source']); ?></span></td>
                <td class="text-muted fs-8"><?php echo date('d M Y H:i', strtotime($h['achieved_at'])); ?></td>
                <td class="text-end rk-noprint">
                    <button class="btn btn-sm btn-light-primary rk-view" data-user="<?php echo (int)$h['user_id']; ?>">View</button>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- member drawer -->
<div class="modal fade" id="rk-member-modal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Member rank detail</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="rk-member-body">
                <div class="text-center text-muted py-10">Loading…</div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('admin/staking/_rank_foot',
        ['pager' => ['total' => $total, 'page' => $page, 'per_page' => $per_page]]); ?>

<script>
(function () {
    const base = '<?php echo base_url(); ?>';
    const modalEl = document.getElementById('rk-member-modal');
    const body = document.getElementById('rk-member-body');
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c =>
        ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const num = v => Number(v || 0).toLocaleString(undefined, { maximumFractionDigits: 2 });

    document.querySelectorAll('.rk-view').forEach(btn => {
        btn.addEventListener('click', async () => {
            body.innerHTML = '<div class="text-center text-muted py-10">Loading…</div>';
            new bootstrap.Modal(modalEl).show();
            try {
                const res = await fetch(base + 'admin/staking/rank-member/' + btn.dataset.user,
                                        { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const j = await res.json();
                body.innerHTML = render(j);
            } catch (e) {
                body.innerHTML = '<div class="alert alert-danger">Could not load: ' + esc(e.message) + '</div>';
            }
        });
    });

    function render(j) {
        let h = '';
        const r = j.rank;
        h += '<div class="mb-6"><div class="fs-7 text-muted mb-1">Achievement rank (permanent)</div>' +
             '<div class="fs-2 fw-bold">' + esc(r ? r.highest_rank : 'Unranked') + '</div>' +
             (r && r.achieved_at ? '<div class="fs-8 text-muted">since ' + esc(r.achieved_at) + '</div>' : '') +
             '</div>';

        if (j.power) {
            h += '<div class="mb-6"><div class="fs-7 text-muted mb-1">Power rank (cycle #' +
                 esc(j.power.cycle_no) + ', resets ' + esc(j.power.end_date) + ')</div>' +
                 '<div class="fs-4 fw-bold">' + esc(j.power.power_rank || 'No power rank') + '</div>' +
                 '<div class="fs-8 text-muted">Cycle volume ' + num(j.power.total_volume) +
                 ' · group incentive ' + (Number(j.power.qualified) ? 'qualified' : 'not qualified') + '</div></div>';
        }

        if (j.progress) {
            h += '<div class="mb-6"><div class="fs-7 text-muted mb-1">Next rank: ' + esc(j.progress.next_rank) + '</div>' +
                 '<div class="progress h-8px mb-2"><div class="progress-bar bg-primary" style="width:' +
                 Number(j.progress.volume_percent) + '%"></div></div>' +
                 '<div class="fs-8 text-muted">' + num(j.progress.current_volume) + ' / ' +
                 num(j.progress.required_volume) + ' volume · team requirement ' +
                 (j.progress.plan_met ? '<span class="text-success">met</span>'
                                      : '<span class="text-warning">not met</span>') + '</div></div>';
        }

        h += '<div class="separator my-5"></div><div class="fs-6 fw-bold mb-3">Rank history</div>';
        if (!j.history || !j.history.length) {
            h += '<div class="text-muted fs-7">No promotions yet.</div>';
        } else {
            h += '<div class="timeline">';
            j.history.forEach(x => {
                h += '<div class="d-flex align-items-center mb-3">' +
                     '<span class="rk-dot" style="background:' + esc(x.badge_color || '#ccc') + '"></span>' +
                     '<div><span class="fw-bold">' + esc(x.new_rank) + '</span>' +
                     '<span class="text-muted fs-8"> from ' + esc(x.old_rank || 'Unranked') +
                     ' · ' + esc(x.qualification_plan || '') + ' · ' + esc(x.achieved_at) + '</span></div></div>';
            });
            h += '</div>';
        }

        h += '<div class="separator my-5"></div><div class="fs-6 fw-bold mb-3">Rewards</div>';
        if (!j.rewards || !j.rewards.length) {
            h += '<div class="text-muted fs-7">No rewards issued.</div>';
        } else {
            h += '<table class="table table-row-dashed fs-7"><tbody>';
            j.rewards.forEach(x => {
                const cls = x.reward_status === 'paid' ? 'success'
                          : (x.reward_status === 'failed' ? 'danger' : 'warning');
                h += '<tr><td>' + esc(x.rank_name) + '</td><td>' + esc(x.reward_type.toUpperCase()) +
                     '</td><td class="rk-num">' + num(x.reward_amount) + '</td>' +
                     '<td><span class="badge badge-light-' + cls + '">' + esc(x.reward_status) + '</span></td></tr>';
            });
            h += '</tbody></table>';
        }

        h += '<div class="separator my-5"></div><div class="fs-6 fw-bold mb-3">Certificates</div>';
        if (!j.certificates || !j.certificates.length) {
            h += '<div class="text-muted fs-7">No certificates issued.</div>';
        } else {
            j.certificates.forEach(x => {
                h += '<div class="mb-2"><a class="rk-mono" target="_blank" href="' + base +
                     'admin/staking/rank-certificate/' + encodeURIComponent(x.certificate_no) + '">' +
                     esc(x.certificate_no) + '</a> <span class="text-muted fs-8">' +
                     esc(x.rank_name) + ' · ' + esc(x.generated_date) + '</span></div>';
            });
        }
        return h;
    }
})();
</script>
