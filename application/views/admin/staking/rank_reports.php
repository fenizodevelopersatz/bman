<?php
$qs = $filters; unset($qs['report']);
$export_qs = http_build_query(array_merge($qs, []));
$card_tools = '<div class="card-toolbar rk-noprint">'
    . '<a class="btn btn-sm btn-light-success me-2" href="'
        . base_url('admin/staking/rank-reports/export/' . $report . '/csv') . ($export_qs ? '?' . $export_qs : '') . '">CSV</a>'
    . '<a class="btn btn-sm btn-light-success me-2" href="'
        . base_url('admin/staking/rank-reports/export/' . $report . '/excel') . ($export_qs ? '?' . $export_qs : '') . '">Excel</a>'
    . '<a class="btn btn-sm btn-light-danger" target="_blank" href="'
        . base_url('admin/staking/rank-reports/export/' . $report . '/pdf') . ($export_qs ? '?' . $export_qs : '') . '">PDF</a>'
    . '</div>';
$this->load->view('admin/staking/_rank_head', ['title' => $title, 'card_tilte' => $card_tilte,
                                               'intro' => $meta['hint'], 'card_tools' => $card_tools]);
?>

<div class="row g-5 mb-8">
    <?php
    $c = $headline['cycle'];
    $tiles = [
        ['Ranked members',  number_format($headline['ranked_members']), 'primary'],
        ['Promotions',      number_format($headline['promotions']),     'success'],
        ['Last 24h',        number_format($headline['promotions_24h']), 'info'],
        ['Certificates',    number_format($headline['certificates']),   'secondary'],
        ['Failed rewards',  number_format($headline['rewards_failed']),
                            $headline['rewards_failed'] > 0 ? 'danger' : 'secondary'],
        ['Incentive qualified', $c ? number_format($headline['cycle_qualified']) : '—', 'warning'],
    ];
    foreach ($tiles as $t): ?>
        <div class="col">
            <div class="card card-flush h-100 bg-light-<?php echo $t[2]; ?>">
                <div class="card-body py-4">
                    <div class="fs-8 text-muted"><?php echo $t[0]; ?></div>
                    <div class="fs-2 fw-bold text-<?php echo $t[2]; ?>"><?php echo $t[1]; ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- report picker -->
<div class="d-flex flex-wrap gap-2 mb-6 rk-noprint">
    <?php foreach ($catalogue as $k => $m): ?>
        <a href="<?php echo base_url('admin/staking/rank-reports?report=' . $k); ?>"
           class="btn btn-sm <?php echo $k === $report ? 'btn-primary' : 'btn-light'; ?>">
            <?php echo html_escape($m['label']); ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- report-specific filters -->
<form method="get" class="rk-filters row g-3 align-items-end mb-6 rk-noprint">
    <input type="hidden" name="report" value="<?php echo html_escape($report); ?>">
    <?php if (in_array($report, ['top_earners','reward_summary'], true)): ?>
        <div class="col-auto">
            <label class="form-label fs-8 text-muted mb-1">From</label>
            <input type="date" name="from" class="form-control form-control-sm" value="<?php echo html_escape($filters['from'] ?? ''); ?>">
        </div>
        <div class="col-auto">
            <label class="form-label fs-8 text-muted mb-1">To</label>
            <input type="date" name="to" class="form-control form-control-sm" value="<?php echo html_escape($filters['to'] ?? ''); ?>">
        </div>
    <?php endif; ?>
    <?php if (in_array($report, ['top_earners','progress'], true)): ?>
        <div class="col-auto">
            <label class="form-label fs-8 text-muted mb-1">Rank</label>
            <select name="rank_id" class="form-select form-select-sm">
                <option value="">All ranks</option>
                <?php foreach ($ranks as $r): ?>
                    <option value="<?php echo (int)$r['id']; ?>"
                        <?php echo (isset($filters['rank_id']) && (int)$filters['rank_id'] === (int)$r['id']) ? 'selected' : ''; ?>>
                        <?php echo html_escape($r['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
    <?php if (in_array($report, ['power_summary','incentive_qual'], true)): ?>
        <div class="col-auto">
            <label class="form-label fs-8 text-muted mb-1">Cycle</label>
            <select name="cycle_id" class="form-select form-select-sm">
                <option value="">Current cycle</option>
                <?php foreach ($cycles as $cy): ?>
                    <option value="<?php echo (int)$cy['id']; ?>"
                        <?php echo (isset($filters['cycle_id']) && (int)$filters['cycle_id'] === (int)$cy['id']) ? 'selected' : ''; ?>>
                        #<?php echo (int)$cy['cycle_no']; ?> · <?php echo $cy['start_date']; ?> → <?php echo $cy['end_date']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
    <?php if ($report === 'incentive_qual'): ?>
        <div class="col-auto">
            <label class="form-label fs-8 text-muted mb-1">Qualified</label>
            <select name="qualified" class="form-select form-select-sm">
                <option value="">Any</option>
                <option value="1" <?php echo (isset($filters['qualified']) && $filters['qualified'] === '1') ? 'selected' : ''; ?>>Yes</option>
                <option value="0" <?php echo (isset($filters['qualified']) && $filters['qualified'] === '0') ? 'selected' : ''; ?>>No</option>
            </select>
        </div>
    <?php endif; ?>
    <div class="col-auto">
        <button class="btn btn-sm btn-primary">Apply</button>
        <a href="<?php echo base_url('admin/staking/rank-reports?report=' . $report); ?>" class="btn btn-sm btn-light">Reset</a>
    </div>
</form>

<div class="table-responsive">
    <table class="table align-middle table-row-dashed fs-6 gy-3">
        <thead>
            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                <?php foreach ($meta['columns'] as $key => $label): ?>
                    <th class="<?php echo preg_match('/volume|paid|amount|incentive|shortfall|percent|members|required/i', $key) ? 'text-end' : ''; ?>">
                        <?php echo html_escape($label); ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="<?php echo count($meta['columns']); ?>" class="text-center text-muted py-10">
                No data for this report yet.
            </td></tr>
        <?php else: foreach ($rows as $row): ?>
            <tr>
                <?php foreach ($meta['columns'] as $key => $label):
                    $v = $row[$key] ?? '';
                    $numeric = preg_match('/volume|paid|amount|incentive|shortfall|required/i', $key);
                ?>
                    <td class="<?php echo $numeric ? 'rk-num rk-mono' : ''; ?>">
                        <?php
                        if ($key === 'percent') {
                            $p = (float)$v;
                            echo '<div class="d-flex align-items-center justify-content-end gap-2">'
                               . '<div class="progress h-6px w-60px"><div class="progress-bar bg-primary" style="width:'
                               . min(100, $p) . '%"></div></div><span class="rk-mono">' . number_format($p, 2) . '%</span></div>';
                        } elseif ($numeric && is_numeric($v)) {
                            echo number_format((float)$v, 2);
                        } elseif ($key === 'qualified') {
                            echo $v === 'Yes' ? '<span class="badge badge-light-success">Yes</span>'
                                              : '<span class="badge badge-light-secondary">No</span>';
                        } elseif (in_array($key, ['failed'], true) && (int)$v > 0) {
                            echo '<span class="badge badge-light-danger">' . (int)$v . '</span>';
                        } else {
                            echo html_escape((string)$v);
                        }
                        ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php if (count($rows) >= 500): ?>
    <div class="alert alert-info mt-5 fs-8 rk-noprint">
        Showing the first 500 rows. The CSV and Excel exports include up to 10,000.
    </div>
<?php endif; ?>

<?php $this->load->view('admin/staking/_rank_foot'); ?>
