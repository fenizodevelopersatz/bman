<?php
$intro = 'Rank rewards are paid <b>once per member per rank, ever</b> — enforced by a unique '
       . 'index, not by application logic, so a repeated cron pass physically cannot double-pay. '
       . 'Cash rewards credit the member\'s wallet through the ledger '
       . '(<code>reference_type = rank_reward</code>). A <span class="badge badge-light-danger">failed</span> '
       . 'row means the credit did not land — it is retried automatically on the next cron pass, '
       . 'or immediately with the button above. Non-cash rewards sit '
       . '<span class="badge badge-light-warning">pending</span> until an admin marks them fulfilled.';
$card_tools = '<div class="card-toolbar rk-noprint">'
    . '<button class="btn btn-sm btn-light-warning me-2" id="rk-retry">Retry failed payouts</button>'
    . '<button class="btn btn-sm btn-light-primary" id="rk-run-cron">Run rank cron now</button></div>';
$this->load->view('admin/staking/_rank_head', ['title' => $title, 'card_tilte' => $card_tilte,
                                               'intro' => $intro, 'card_tools' => $card_tools]);
?>

<div class="row g-5 mb-8">
    <?php
    $tiles = [
        ['Paid',      number_format($headline['rewards_paid']),    'success'],
        ['Pending',   number_format($headline['rewards_pending']), 'warning'],
        ['Failed',    number_format($headline['rewards_failed']),  'danger'],
        ['BMAN paid', number_format((float)$headline['bman_paid'], 2), 'primary'],
        ['USDT paid', number_format((float)$headline['usdt_paid'], 2), 'info'],
    ];
    foreach ($tiles as $t): ?>
        <div class="col">
            <div class="card card-flush h-100 bg-light-<?php echo $t[2]; ?>">
                <div class="card-body py-4">
                    <div class="fs-7 text-muted"><?php echo $t[0]; ?></div>
                    <div class="fs-2 fw-bold text-<?php echo $t[2]; ?>"><?php echo $t[1]; ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<form method="get" class="rk-filters row g-3 align-items-end mb-6 rk-noprint">
    <div class="col-auto">
        <label class="form-label fs-8 text-muted mb-1">Status</label>
        <select name="status" class="form-select form-select-sm">
            <option value="">Any</option>
            <?php foreach (['paid','pending','failed','skipped'] as $s): ?>
                <option value="<?php echo $s; ?>" <?php echo (($filters['status'] ?? '') === $s) ? 'selected' : ''; ?>>
                    <?php echo ucfirst($s); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label fs-8 text-muted mb-1">Type</label>
        <select name="type" class="form-select form-select-sm">
            <option value="">Any</option>
            <?php foreach (['bman' => 'BMAN', 'usdt' => 'USDT', 'physical' => 'Non-cash'] as $k => $v): ?>
                <option value="<?php echo $k; ?>" <?php echo (($filters['type'] ?? '') === $k) ? 'selected' : ''; ?>>
                    <?php echo $v; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label fs-8 text-muted mb-1">Rank</label>
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
        <label class="form-label fs-8 text-muted mb-1">From</label>
        <input type="date" name="from" class="form-control form-control-sm" value="<?php echo html_escape($filters['from'] ?? ''); ?>">
    </div>
    <div class="col-auto">
        <label class="form-label fs-8 text-muted mb-1">To</label>
        <input type="date" name="to" class="form-control form-control-sm" value="<?php echo html_escape($filters['to'] ?? ''); ?>">
    </div>
    <div class="col-auto">
        <button class="btn btn-sm btn-primary">Filter</button>
        <a href="<?php echo base_url('admin/staking/rank-rewards'); ?>" class="btn btn-sm btn-light">Reset</a>
    </div>
</form>

<div class="table-responsive">
    <table class="table align-middle table-row-dashed fs-6 gy-4">
        <thead>
            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                <th>Member</th><th>Rank</th><th>Type</th>
                <th class="text-end">Amount</th><th>Status</th><th>Ledger</th>
                <th>Rewarded</th><th>Note</th><th class="text-end rk-noprint"></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="9" class="text-center text-muted py-10">
                No rewards issued yet. Set a BMAN/USDT reward on a rank in
                <a href="<?php echo base_url('admin/staking/ranks'); ?>">Rank Definitions</a> first —
                ranks currently seeded with a 0 reward pay nothing.
            </td></tr>
        <?php else: foreach ($rows as $r):
            $cls = $r['reward_status'] === 'paid' ? 'success'
                 : ($r['reward_status'] === 'failed' ? 'danger'
                 : ($r['reward_status'] === 'skipped' ? 'secondary' : 'warning'));
        ?>
            <tr>
                <td>
                    <div class="d-flex flex-column">
                        <span class="fw-bold"><?php echo html_escape($r['username'] ?: ('User #' . $r['user_id'])); ?></span>
                        <span class="text-muted fs-8"><?php echo html_escape($r['email'] ?: ''); ?></span>
                    </div>
                </td>
                <td class="fw-bold"><?php echo html_escape($r['rank_name']); ?></td>
                <td><span class="badge badge-light-primary"><?php echo strtoupper($r['reward_type']); ?></span></td>
                <td class="rk-num rk-mono">
                    <?php echo $r['reward_type'] === 'physical' ? '—' : number_format((float)$r['reward_amount'], 4); ?>
                </td>
                <td><span class="badge badge-light-<?php echo $cls; ?>"><?php echo html_escape($r['reward_status']); ?></span></td>
                <td class="rk-mono text-muted fs-8"><?php echo $r['wallet_ledger_id'] ? '#' . (int)$r['wallet_ledger_id'] : '—'; ?></td>
                <td class="text-muted fs-8"><?php echo $r['rewarded_at'] ? date('d M Y H:i', strtotime($r['rewarded_at'])) : '—'; ?></td>
                <td class="text-muted fs-8"><?php echo html_escape($r['note'] ?: ''); ?></td>
                <td class="text-end rk-noprint">
                    <?php if ($r['reward_type'] === 'physical' && $r['reward_status'] !== 'paid'): ?>
                        <button class="btn btn-sm btn-light-success rk-fulfil" data-id="<?php echo (int)$r['id']; ?>">
                            Mark fulfilled</button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php $this->load->view('admin/staking/_rank_foot',
        ['pager' => ['total' => $total, 'page' => $page, 'per_page' => $per_page]]); ?>

<script>
(function () {
    document.getElementById('rk-retry').addEventListener('click', async function () {
        this.disabled = true; this.textContent = 'Retrying…';
        const r = await rkPost('admin/staking/rank-rewards/retry', new FormData());
        rkToast(r.msg, r.ok);
        setTimeout(() => location.reload(), 800);
    });

    document.getElementById('rk-run-cron').addEventListener('click', async function () {
        this.disabled = true; this.textContent = 'Running…';
        const fd = new FormData(); fd.append('job', 'achievement');
        const r = await rkPost('admin/staking/rank-run-cron', fd);
        rkToast(r.msg, r.ok);
        setTimeout(() => location.reload(), 1200);
    });

    document.querySelectorAll('.rk-fulfil').forEach(b => {
        b.addEventListener('click', async () => {
            const note = prompt('Fulfilment note (optional):') || '';
            const fd = new FormData(); fd.append('note', note);
            const r = await rkPost('admin/staking/rank-rewards/fulfil/' + b.dataset.id, fd);
            rkToast(r.msg, r.ok);
            if (r.ok) setTimeout(() => location.reload(), 600);
        });
    });
})();
</script>
