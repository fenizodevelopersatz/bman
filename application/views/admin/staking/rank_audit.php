<?php
$intro = 'Every rank action, append-only. <code>rank_promoted</code> and <code>reward_paid</code> '
       . 'are written by the cron; <code>rank_config_changed</code> records admin edits to rank '
       . 'definitions field by field, so you can see exactly who changed a reward amount and when.';
$this->load->view('admin/staking/_rank_head', ['title' => $title, 'card_tilte' => $card_tilte,
                                               'intro' => $intro]);
?>

<form method="get" class="rk-filters row g-3 align-items-end mb-6 rk-noprint">
    <div class="col-auto">
        <label class="form-label fs-8 text-muted mb-1">Event</label>
        <select name="event" class="form-select form-select-sm">
            <option value="">All events</option>
            <?php foreach ($events as $e): ?>
                <option value="<?php echo html_escape($e); ?>" <?php echo (($filters['event'] ?? '') === $e) ? 'selected' : ''; ?>>
                    <?php echo html_escape($e); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label fs-8 text-muted mb-1">User ID</label>
        <input type="number" name="user_id" class="form-control form-control-sm" style="width:120px"
               value="<?php echo html_escape($filters['user_id'] ?? ''); ?>">
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
        <a href="<?php echo base_url('admin/staking/rank-audit'); ?>" class="btn btn-sm btn-light">Reset</a>
    </div>
</form>

<div class="table-responsive">
    <table class="table align-middle table-row-dashed fs-6 gy-3">
        <thead>
            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                <th>When</th><th>Event</th><th>Member</th><th>Rank</th>
                <th>Old</th><th>New</th><th>By</th><th>Note</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="8" class="text-center text-muted py-10">No audit entries yet.</td></tr>
        <?php else: foreach ($rows as $a):
            $badge = [
                'rank_promoted'       => 'success',
                'reward_paid'         => 'primary',
                'certificate_issued'  => 'info',
                'rank_config_changed' => 'warning',
                'power_calculated'    => 'secondary',
                'cycle_opened'        => 'dark',
            ][$a['event']] ?? 'light';
        ?>
            <tr>
                <td class="text-muted fs-8" style="white-space:nowrap">
                    <?php echo date('d M Y H:i:s', strtotime($a['created_at'])); ?>
                </td>
                <td><span class="badge badge-light-<?php echo $badge; ?>"><?php echo html_escape($a['event']); ?></span></td>
                <td>
                    <?php if ($a['user_id']): ?>
                        <span class="fw-bold"><?php echo html_escape($a['username'] ?: ('#' . $a['user_id'])); ?></span>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td><?php echo html_escape($a['rank_name'] ?: '—'); ?></td>
                <td class="rk-mono text-muted fs-8"><?php echo html_escape($a['old_value'] ?: '—'); ?></td>
                <td class="rk-mono fs-8"><?php echo html_escape($a['new_value'] ?: '—'); ?></td>
                <td class="fs-8"><?php echo $a['changed_by'] ? 'admin #' . (int)$a['changed_by']
                                                             : '<span class="text-muted">cron</span>'; ?></td>
                <td class="text-muted fs-8"><?php echo html_escape($a['note'] ?: ''); ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php $this->load->view('admin/staking/_rank_foot',
        ['pager' => ['total' => $total, 'page' => $page, 'per_page' => $per_page]]); ?>
