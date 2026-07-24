<?php $this->load->view('admin/Layout/common_style'); ?>
<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css"/>
<style>
    .bm-stat-card .card-body { padding: 1.25rem 1.5rem; }
    .bnb-badge { display:inline-flex;align-items:center;gap:3px;background:rgba(243,186,47,.15);
                 color:#b8860b;border:1px solid rgba(243,186,47,.35);border-radius:6px;
                 padding:1px 7px;font-size:.75rem;font-weight:600; }
    [data-bs-theme="dark"] .bnb-badge { color:#f3ba2f; }
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

            <!-- Toolbar -->
            <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                <div class="app-container container-xxl d-flex flex-stack">
                    <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                        <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0"><?php echo $page_title; ?></h1>
                        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                            <li class="breadcrumb-item text-muted"><a href="<?php echo base_url(); ?>admin" class="text-muted text-hover-primary">Finance</a></li>
                            <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                            <li class="breadcrumb-item text-muted">Bonus Matching History</li>
                        </ul>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?php echo base_url('admin/binary-matching/history/export/csv?' . http_build_query(array_filter(['level'=>$level_filter,'status'=>$status_filter,'user_id'=>$user_filter,'date_from'=>$date_from,'date_to'=>$date_to]))); ?>"
                           class="btn btn-sm btn-light-success">
                            <i class="ki-duotone ki-exit-up fs-4"><span class="path1"></span><span class="path2"></span></i> CSV
                        </a>
                        <a href="<?php echo base_url('admin/binary-matching/history/export/excel?' . http_build_query(array_filter(['level'=>$level_filter,'status'=>$status_filter,'user_id'=>$user_filter,'date_from'=>$date_from,'date_to'=>$date_to]))); ?>"
                           class="btn btn-sm btn-light-info">
                            <i class="ki-duotone ki-exit-up fs-4"><span class="path1"></span><span class="path2"></span></i> Excel
                        </a>
                    </div>
                </div>
            </div>

            <div id="kt_app_content" class="app-content flex-column-fluid mt-4">
            <div class="app-container container-xxl">

                <?php $this->load->view('notification'); ?>

                <!-- Summary Cards -->
                <div class="row g-4 mb-6">
                    <?php
                    $smCards = [
                        ['Total Bonuses',    $summary_stats['total_count'],      null,   'All-time records',     '#7239ea', 'ki-abstract-26'],
                        ['Total Distributed',$summary_stats['total_distributed'],'USDT', 'Earning + Staking',   '#10b981', 'ki-dollar'],
                        ['Pending',          $summary_stats['pending_count'],     null,  'Awaiting payout',      '#f59e0b', 'ki-time'],
                        ["Today's Bonus",    $summary_stats['today_count'],       null,  'Bonuses today',        '#009ef7', 'ki-chart-simple'],
                    ];
                    foreach ($smCards as [$lbl, $val, $unit, $sub, $clr, $icon]):
                    ?>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush bm-stat-card h-100" style="border-top:3px solid <?php echo $clr; ?>">
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="ki-duotone <?php echo $icon; ?> fs-2" style="color:<?php echo $clr; ?>"><span class="path1"></span><span class="path2"></span></i>
                                    <span class="text-muted fw-semibold fs-8 text-uppercase"><?php echo $lbl; ?></span>
                                </div>
                                <div class="d-flex align-items-end gap-2">
                                    <span class="fs-2 fw-bold text-gray-900">
                                        <?php echo is_float($val) ? number_format($val, 4) : number_format((int)$val); ?>
                                    </span>
                                    <?php if ($unit): ?>
                                    <span class="text-muted fs-8 mb-1"><?php echo $unit; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted fs-9"><?php echo $sub; ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Filter + Table Card -->
                <div class="card">
                    <div class="card-header border-transparent pt-5">
                        <h3 class="card-title fw-bold text-gray-900 fs-5">Bonus Matching Records</h3>
                        <div class="card-toolbar">
                            <button class="btn btn-sm btn-light" id="bm-filter-toggle">
                                <i class="ki-duotone ki-filter fs-4"><span class="path1"></span><span class="path2"></span></i> Filters
                            </button>
                        </div>
                    </div>

                    <!-- Filter Bar -->
                    <div class="card-body border-top py-4" id="bm-filter-bar">
                        <form method="get" class="row g-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label fs-8">Level</label>
                                <select name="level" class="form-select form-select-sm">
                                    <option value="">All Levels</option>
                                    <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?= $i ?>" <?= $level_filter == $i ? 'selected' : '' ?>>Level <?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fs-8">Status</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">All Status</option>
                                    <option value="DISTRIBUTED" <?= $status_filter === 'DISTRIBUTED' ? 'selected' : '' ?>>Distributed</option>
                                    <option value="HELD_CEILING" <?= $status_filter === 'HELD_CEILING' ? 'selected' : '' ?>>Ceiling Hold</option>
                                    <option value="PENDING" <?= $status_filter === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                                    <option value="FAILED" <?= $status_filter === 'FAILED' ? 'selected' : '' ?>>Failed</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fs-8">User ID</label>
                                <input type="number" name="user_id" class="form-control form-control-sm" placeholder="Any" value="<?= htmlspecialchars($user_filter ?? '') ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fs-8">Date From</label>
                                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($date_from ?? '') ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fs-8">Date To</label>
                                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($date_to ?? '') ?>">
                            </div>
                            <div class="col-md-2 d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Apply</button>
                                <a href="<?php echo base_url('admin/binary-matching/history'); ?>" class="btn btn-light btn-sm">Reset</a>
                            </div>
                        </form>
                    </div>

                    <!-- Table -->
                    <div class="card-body pt-2">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-7 gy-3">
                                <thead>
                                    <tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                                        <th>ID</th>
                                        <th>Recipient</th>
                                        <th>Level</th>
                                        <th class="text-end">Left Leg</th>
                                        <th class="text-end">Right Leg</th>
                                        <th class="text-end">Qualifying Vol</th>
                                        <th class="text-end">Earning (8%)</th>
                                        <th class="text-end">Staking (2%)</th>
                                        <th class="text-end">Total Bonus</th>
                                        <th class="text-center">Status</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-700 fw-semibold">
                                    <?php if (!empty($bonuses)): ?>
                                        <?php foreach ($bonuses as $bonus): ?>
                                        <tr>
                                            <td><code class="fs-8"><?= $bonus['id'] ?></code></td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold"><?= htmlspecialchars($bonus['recipient_name'] ?? '#'.$bonus['bonus_recipient_id']) ?></span>
                                                    <span class="text-muted fs-9">Exchange: $<?= number_format($bonus['exchange_balance'] ?? 0, 2) ?></span>
                                                </div>
                                            </td>
                                            <td><span class="badge badge-light-primary">L<?= $bonus['level'] ?></span></td>
                                            <td class="text-end text-muted fs-8">$<?= number_format($bonus['left_leg_volume'] ?? 0, 2) ?></td>
                                            <td class="text-end text-muted fs-8">$<?= number_format($bonus['right_leg_volume'] ?? 0, 2) ?></td>
                                            <td class="text-end">
                                                <strong>$<?= number_format($bonus['qualifying_volume'] ?? 0, 2) ?></strong>
                                                <div class="text-muted fs-9">min(L,R)</div>
                                            </td>
                                            <td class="text-end text-success fw-bold">$<?= number_format($bonus['bonus_earning'] ?? 0, 4) ?></td>
                                            <td class="text-end text-info fw-bold">$<?= number_format($bonus['bonus_staking'] ?? 0, 4) ?></td>
                                            <td class="text-end fw-bold fs-6">$<?= number_format($bonus['bonus_amount_total'] ?? 0, 4) ?></td>
                                            <td class="text-center">
                                                <?php if ($bonus['status'] === 'DISTRIBUTED'): ?>
                                                    <span class="badge badge-light-success">✓ Distributed</span>
                                                <?php elseif ($bonus['status'] === 'HELD_CEILING'): ?>
                                                    <span class="badge badge-light-warning">⏸ Ceiling</span>
                                                    <div class="text-muted fs-9">$<?= number_format($bonus['ceiling_amount_held'] ?? 0, 2) ?></div>
                                                <?php elseif ($bonus['status'] === 'PENDING'): ?>
                                                    <span class="badge badge-light-info">Pending</span>
                                                <?php else: ?>
                                                    <span class="badge badge-light-danger"><?= htmlspecialchars($bonus['status']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-muted fs-8"><?= date('M d, H:i', strtotime($bonus['created_at'])) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="11" class="text-muted py-6 text-center">No bonuses found for the selected filters.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <div class="d-flex justify-content-between align-items-center mt-5">
                            <div class="text-muted fs-8">
                                Showing <?= (($current_page-1)*$per_page)+1 ?> – <?= min($current_page*$per_page, $total) ?> of <?= number_format($total) ?> bonuses
                            </div>
                            <ul class="pagination pagination-sm mb-0">
                                <?php if ($current_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= base_url('admin/binary-matching/history?page=1' . http_build_query_suffix($level_filter, $status_filter, $user_filter, $date_from, $date_to)) ?>">«</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="<?= base_url('admin/binary-matching/history?page='.($current_page-1).http_build_query_suffix($level_filter, $status_filter, $user_filter, $date_from, $date_to)) ?>">‹</a>
                                </li>
                                <?php endif; ?>
                                <?php for ($i = max(1,$current_page-2); $i <= min($total_pages,$current_page+2); $i++): ?>
                                <li class="page-item <?= $i === $current_page ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= base_url('admin/binary-matching/history?page='.$i.http_build_query_suffix($level_filter, $status_filter, $user_filter, $date_from, $date_to)) ?>"><?= $i ?></a>
                                </li>
                                <?php endfor; ?>
                                <?php if ($current_page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= base_url('admin/binary-matching/history?page='.($current_page+1).http_build_query_suffix($level_filter, $status_filter, $user_filter, $date_from, $date_to)) ?>">›</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="<?= base_url('admin/binary-matching/history?page='.$total_pages.http_build_query_suffix($level_filter, $status_filter, $user_filter, $date_from, $date_to)) ?>">»</a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="card mt-5 border border-dashed border-gray-300">
                    <div class="card-body py-3 px-5">
                        <div class="d-flex align-items-center gap-3 text-muted fs-8">
                            <i class="ki-duotone ki-information fs-4 text-info"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                            <span><strong>Formula:</strong> min(left_leg, right_leg) × 10% = Total Bonus &nbsp;|&nbsp; 8% → Earning Wallet &nbsp;|&nbsp; 2% → Staking Wallet</span>
                            <span class="ms-4"><strong>Ceiling Hold:</strong> Bonus held in admin pool when exchange balance exceeds cap</span>
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
document.getElementById('bm-filter-toggle').addEventListener('click', function() {
    var bar = document.getElementById('bm-filter-bar');
    bar.style.display = (bar.style.display === 'none') ? '' : 'none';
});
</script>
<?php
// Helper — builds the query-string suffix for pagination links
function http_build_query_suffix($level, $status, $user, $date_from, $date_to) {
    $p = array_filter(['level'=>$level,'status'=>$status,'user_id'=>$user,'date_from'=>$date_from,'date_to'=>$date_to]);
    return $p ? '&' . http_build_query($p) : '';
}
?>
</body>
</html>
