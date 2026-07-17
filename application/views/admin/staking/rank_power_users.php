<?php
$intro = '<b>Rank Power is not the achievement rank.</b> It is recalculated from '
       . '<b>current-cycle staking volume only</b> and resets when a cycle rolls. It is what '
       . 'Group Incentive qualification reads — a member whose achievement rank is GOLD but whose '
       . 'power this cycle is SILVER is paid the <b>SILVER</b> incentive. Nothing on this page '
       . 'can change a permanent rank. Cycle settings live in '
       . '<a href="' . base_url('admin/staking/rank-power') . '">Rank Power &amp; Incentive</a>.';
$card_tools = '<div class="card-toolbar rk-noprint">'
    . '<button class="btn btn-sm btn-light-primary me-2" id="rk-run-power">Recalculate power now</button>'
    . '<button class="btn btn-sm btn-light-warning" id="rk-roll-cycle">Roll cycle if expired</button></div>';
$this->load->view('admin/staking/_rank_head', ['title' => $title, 'card_tilte' => $card_tilte,
                                               'intro' => $intro, 'card_tools' => $card_tools]);
?>

<?php if (!$cycle): ?>
    <div class="alert alert-warning">
        No Rank Power cycle has been opened yet. Run the daily power cron
        (<code>/rank-power-cron?token=…</code>) or press <b>Roll cycle if expired</b> — the first
        run opens cycle #1 automatically.
    </div>
<?php else: ?>

<div class="row g-5 mb-8">
    <div class="col-md-3">
        <div class="card card-flush h-100 bg-light-primary">
            <div class="card-body py-4">
                <div class="fs-7 text-muted">Cycle</div>
                <div class="fs-2 fw-bold text-primary">#<?php echo (int)$cycle['cycle_no']; ?></div>
                <div class="fs-8 text-muted">
                    <?php echo date('d M Y', strtotime($cycle['start_date'])); ?> →
                    <?php echo date('d M Y', strtotime($cycle['end_date'])); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-flush h-100 bg-light-info">
            <div class="card-body py-4">
                <div class="fs-7 text-muted">Status</div>
                <div class="fs-2 fw-bold text-info"><?php echo ucfirst($cycle['status']); ?></div>
                <?php
                $days_left = (int)floor((strtotime($cycle['end_date']) - strtotime(date('Y-m-d'))) / 86400);
                ?>
                <div class="fs-8 text-muted">
                    <?php echo $days_left >= 0 ? $days_left . ' day(s) left' : 'expired ' . abs($days_left) . ' day(s) ago'; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-flush h-100 bg-light-success">
            <div class="card-body py-4">
                <div class="fs-7 text-muted">Calculated</div>
                <div class="fs-2 fw-bold text-success"><?php echo number_format($total); ?></div>
                <div class="fs-8 text-muted">members this cycle</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-flush h-100 bg-light-warning">
            <div class="card-body py-4">
                <div class="fs-7 text-muted">Reset period</div>
                <div class="fs-2 fw-bold text-warning"><?php echo (int)$settings['cycle_days']; ?>d</div>
                <div class="fs-8 text-muted">
                    incentive: <?php echo !empty($settings['controls_group_incentive']) ? 'power-driven' : '<b>achievement-driven</b>'; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (empty($settings['controls_group_incentive'])): ?>
    <div class="alert alert-warning rk-noprint">
        <b>Heads up:</b> <code>controls_group_incentive</code> is OFF, so Group Incentive is currently
        paying on the <b>permanent achievement rank</b>, not on power. That is the opposite of the §11
        rule. Turn it back on in Rank Power &amp; Incentive unless this is deliberate.
    </div>
<?php endif; ?>

<!-- cron state -->
<div class="d-flex flex-wrap gap-6 mb-6 fs-8 text-muted rk-noprint">
    <?php foreach ($cron as $c): ?>
        <div>
            <b class="text-gray-700"><?php echo html_escape($c['job']); ?></b> —
            <?php echo $c['last_run_at'] ? 'last ran ' . date('d M H:i', strtotime($c['last_run_at'])) : 'never run'; ?>
            <?php if (!empty($c['running'])): ?>
                <span class="badge badge-light-warning ms-1">running</span>
                <a href="#" class="rk-release ms-1" data-job="<?php echo html_escape($c['job']); ?>">release lock</a>
            <?php endif; ?>
            <?php if (!empty($c['last_result'])): ?>
                <div class="fs-9"><?php echo html_escape($c['last_result']); ?></div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<form method="get" class="rk-filters row g-3 align-items-end mb-6 rk-noprint">
    <div class="col-auto">
        <label class="form-label fs-8 text-muted mb-1">Cycle</label>
        <select name="cycle_id" class="form-select form-select-sm">
            <?php foreach ($cycles as $cy): ?>
                <option value="<?php echo (int)$cy['id']; ?>" <?php echo (int)$cy['id'] === (int)$cycle['id'] ? 'selected' : ''; ?>>
                    #<?php echo (int)$cy['cycle_no']; ?> · <?php echo $cy['start_date']; ?> → <?php echo $cy['end_date']; ?>
                    (<?php echo $cy['status']; ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label fs-8 text-muted mb-1">Power rank</label>
        <select name="rank_id" class="form-select form-select-sm">
            <option value="">All</option>
            <?php foreach ($ranks as $r): ?>
                <option value="<?php echo (int)$r['id']; ?>"
                    <?php echo (isset($filters['rank_id']) && (int)$filters['rank_id'] === (int)$r['id']) ? 'selected' : ''; ?>>
                    <?php echo html_escape($r['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label fs-8 text-muted mb-1">Qualified</label>
        <select name="qualified" class="form-select form-select-sm">
            <option value="">Any</option>
            <option value="1" <?php echo (isset($filters['qualified']) && $filters['qualified'] === '1') ? 'selected' : ''; ?>>Qualified</option>
            <option value="0" <?php echo (isset($filters['qualified']) && $filters['qualified'] === '0') ? 'selected' : ''; ?>>Not qualified</option>
        </select>
    </div>
    <div class="col-auto">
        <button class="btn btn-sm btn-primary">Filter</button>
        <a href="<?php echo base_url('admin/staking/rank-power-users'); ?>" class="btn btn-sm btn-light">Reset</a>
    </div>
</form>

<div class="table-responsive">
    <table class="table align-middle table-row-dashed fs-6 gy-4">
        <thead>
            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                <th>Member</th><th>Power rank</th>
                <th class="text-end">Left vol</th><th class="text-end">Right vol</th>
                <th class="text-end">Cycle vol</th><th class="text-end">Incentive</th>
                <th>Qualified</th><th>Calculated</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="8" class="text-center text-muted py-10">
                Nothing calculated for this cycle yet. Press <b>Recalculate power now</b>.
            </td></tr>
        <?php else: foreach ($rows as $p): ?>
            <tr>
                <td>
                    <div class="d-flex flex-column">
                        <span class="fw-bold"><?php echo html_escape($p['username'] ?: ('User #' . $p['user_id'])); ?></span>
                        <span class="text-muted fs-8"><?php echo html_escape($p['email'] ?: ''); ?></span>
                    </div>
                </td>
                <td>
                    <?php if ($p['power_rank']): ?>
                        <span class="rk-dot" style="background:<?php echo html_escape($p['badge_color'] ?: '#ccc'); ?>"></span>
                        <span class="fw-bold"><?php echo html_escape($p['power_rank']); ?></span>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="rk-num rk-mono"><?php echo number_format((float)$p['left_volume'], 2); ?></td>
                <td class="rk-num rk-mono"><?php echo number_format((float)$p['right_volume'], 2); ?></td>
                <td class="rk-num rk-mono fw-bold"><?php echo number_format((float)$p['total_volume'], 2); ?></td>
                <td class="rk-num rk-mono">
                    <?php echo !empty($p['qualified']) ? number_format((float)$p['group_incentive'], 2) : '0.00'; ?>
                </td>
                <td>
                    <?php if (!empty($p['qualified'])): ?>
                        <span class="badge badge-light-success">Yes</span>
                    <?php else: ?>
                        <span class="badge badge-light-secondary">No</span>
                    <?php endif; ?>
                </td>
                <td class="text-muted fs-8">
                    <?php echo $p['calculated_at'] ? date('d M H:i', strtotime($p['calculated_at'])) : '—'; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php $this->load->view('admin/staking/_rank_foot',
        ['pager' => ['total' => $total, 'page' => $page, 'per_page' => $per_page]]); ?>

<script>
(function () {
    async function fire(btn, job, label) {
        btn.disabled = true; const old = btn.textContent; btn.textContent = label;
        const fd = new FormData(); fd.append('job', job);
        const r = await rkPost('admin/staking/rank-run-cron', fd);
        rkToast(r.msg, r.ok);
        btn.disabled = false; btn.textContent = old;
        setTimeout(() => location.reload(), 1200);
    }
    const run = document.getElementById('rk-run-power');
    if (run) run.addEventListener('click', () => fire(run, 'power', 'Calculating…'));
    const roll = document.getElementById('rk-roll-cycle');
    if (roll) roll.addEventListener('click', () => fire(roll, 'power_cycle', 'Checking…'));

    document.querySelectorAll('.rk-release').forEach(a => {
        a.addEventListener('click', async e => {
            e.preventDefault();
            const r = await rkPost('admin/staking/rank-release-lock/' + a.dataset.job, new FormData());
            rkToast(r.msg, r.ok);
            setTimeout(() => location.reload(), 600);
        });
    });
})();
</script>
