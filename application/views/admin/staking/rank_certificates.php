<?php
$intro = 'One certificate per member per rank, ever — the number is minted from an atomic '
       . 'per-rank, per-year counter, so the first GOLD of ' . date('Y') . ' is '
       . '<code>BMAN-GOLD-' . date('Y') . '-000001</code>. Numbers are guaranteed unique but not '
       . 'gap-free: a serial burned by a losing race is skipped rather than reused. Certificates '
       . 'are issued automatically for any rank with the <b>Certificate</b> benefit enabled. '
       . 'Open one to print it or save it as PDF from the browser dialog.';
$this->load->view('admin/staking/_rank_head', ['title' => $title, 'card_tilte' => $card_tilte,
                                               'intro' => $intro]);
?>

<form method="get" class="rk-filters row g-3 align-items-end mb-6 rk-noprint">
    <div class="col-auto">
        <label class="form-label fs-8 text-muted mb-1">Certificate no.</label>
        <input type="text" name="q" class="form-control form-control-sm" placeholder="BMAN-GOLD-…"
               value="<?php echo html_escape($filters['q'] ?? ''); ?>">
    </div>
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
    <div class="col-auto">
        <button class="btn btn-sm btn-primary">Filter</button>
        <a href="<?php echo base_url('admin/staking/rank-certificates'); ?>" class="btn btn-sm btn-light">Reset</a>
    </div>
</form>

<div class="table-responsive">
    <table class="table align-middle table-row-dashed fs-6 gy-4">
        <thead>
            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                <th>Certificate no.</th><th>Member</th><th>Rank</th><th>Generated</th>
                <th class="text-end rk-noprint"></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="5" class="text-center text-muted py-10">
                No certificates issued yet.
            </td></tr>
        <?php else: foreach ($rows as $c): ?>
            <tr>
                <td class="rk-mono fw-bold"><?php echo html_escape($c['certificate_no']); ?></td>
                <td>
                    <div class="d-flex flex-column">
                        <span class="fw-bold"><?php echo html_escape($c['username'] ?: ('User #' . $c['user_id'])); ?></span>
                        <span class="text-muted fs-8"><?php echo html_escape($c['email'] ?: ''); ?></span>
                    </div>
                </td>
                <td>
                    <?php if (!empty($c['badge_image'])): ?>
                        <img src="<?php echo base_url($c['badge_image']); ?>" class="rk-badge" alt="">
                    <?php else: ?>
                        <span class="rk-dot" style="background:<?php echo html_escape($c['badge_color'] ?: '#ccc'); ?>"></span>
                    <?php endif; ?>
                    <span class="fw-bold"><?php echo html_escape($c['rank_name']); ?></span>
                </td>
                <td class="text-muted fs-8"><?php echo date('d M Y', strtotime($c['generated_date'])); ?></td>
                <td class="text-end rk-noprint">
                    <a class="btn btn-sm btn-light-primary" target="_blank"
                       href="<?php echo base_url('admin/staking/rank-certificate/' . rawurlencode($c['certificate_no'])); ?>">
                       Open / print</a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php $this->load->view('admin/staking/_rank_foot',
        ['pager' => ['total' => $total, 'page' => $page, 'per_page' => $per_page]]); ?>
