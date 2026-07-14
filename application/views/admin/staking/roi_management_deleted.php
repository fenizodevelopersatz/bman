<?php $this->load->view('admin/Layout/common_style'); ?>
<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
<style>
  .roi-card { border: 1px solid rgba(0,0,0,.08); border-radius: 12px; padding: 1.5rem; background: #fff; }
  .roi-stat { display: flex; align-items: center; gap: 1rem; }
  .roi-stat .value { font-size: 1.75rem; font-weight: 900; color: #0b1220; }
  .roi-stat .label { font-size: .875rem; color: #6b7280; font-weight: 700; }
  .roi-stat.pending { border-left: 4px solid #f59e0b; }
  .roi-stat.success { border-left: 4px solid #10b981; }
  .roi-stat.failed { border-left: 4px solid #ef4444; }
  .filter-group { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem; align-items: flex-end; }
  .filter-group .form-group { margin-bottom: 0; min-width: 200px; }
  .roi-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
  .roi-table th { background: #f3f4f6; border-bottom: 2px solid #e5e7eb; padding: .75rem; text-align: left; font-weight: 700; }
  .roi-table td { border-bottom: 1px solid #e5e7eb; padding: .75rem; }
  .roi-table tr:hover { background: #f9fafb; }
  .badge { display: inline-block; padding: .25rem .75rem; border-radius: 9999px; font-size: .75rem; font-weight: 900; }
  .badge.success { background: rgba(16,185,129,.12); color: #065f46; }
  .badge.pending { background: rgba(245,158,11,.12); color: #92400e; }
  .badge.failed { background: rgba(239,68,68,.12); color: #7f1d1d; }
</style>
<body id="kt_app_body" class="app-default">
<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
<div class="app-page flex-column flex-column-fluid" id="kt_app_page">
<?php $this->load->view('admin/Layout/admin_topbar'); ?>
<div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
<?php $this->load->view('admin/Layout/admin_sidebar'); ?>
<div class="app-main flex-column flex-row-fluid" id="kt_app_main">
<div class="d-flex flex-column flex-column-fluid">
  <div class="app-toolbar py-3 py-lg-6">
    <div class="app-container container-xxl d-flex flex-stack">
      <div>
        <h1 class="page-heading text-gray-900 fw-bold fs-3">ROI Management</h1>
        <div class="text-muted fs-7">Track all ROI distributions, validate missed executions, and manage member payouts</div>
      </div>
      <button class="btn btn-primary" id="btn-export-csv">
        <i class="ki-duotone ki-download"></i> Export CSV
      </button>
    </div>
  </div>

  <div class="app-content flex-column-fluid">
    <div class="app-container container-xxl">

      <!-- Statistics Cards -->
      <div class="row g-3 mb-6">
        <?php
          $total_distributed = 0;
          $total_success = 0;
          $total_pending = 0;
          foreach ($roi_history as $row) {
            $total_distributed += $row['roi_amount'];
            if ($row['status'] === 'success') $total_success++;
            elseif ($row['status'] === 'pending') $total_pending++;
          }
        ?>
        <div class="col-md-3">
          <div class="roi-card">
            <div class="roi-stat success">
              <div>
                <div class="value"><?php echo number_format($total_distributed, 0); ?></div>
                <div class="label">Total Distributed (BMAN)</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="roi-card">
            <div class="roi-stat success">
              <div>
                <div class="value"><?php echo $total_success; ?></div>
                <div class="label">Successful Distributions</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="roi-card">
            <div class="roi-stat pending">
              <div>
                <div class="value"><?php echo $total_pending; ?></div>
                <div class="label">Pending/Retries</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="roi-card">
            <div class="roi-stat failed">
              <div>
                <div class="value"><?php echo count($missed_executions); ?></div>
                <div class="label">Missed Executions</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Upcoming Maturity Dates -->
      <?php if (!empty($upcoming_maturity)): ?>
      <div class="card mb-6">
        <div class="card-header border-0 pt-6">
          <div class="card-title fw-bold">Upcoming Maturity Payouts (30 days)</div>
        </div>
        <div class="card-body pt-2">
          <div class="table-responsive">
            <table class="roi-table">
              <thead>
                <tr>
                  <th>User</th>
                  <th>Plan Type</th>
                  <th>Maturity Date</th>
                  <th>Expected ROI</th>
                  <th>Principal</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (array_slice($upcoming_maturity, 0, 10) as $m): ?>
                <tr>
                  <td><?php echo $m['user_id']; ?></td>
                  <td><span class="badge badge-light"><?php echo strtoupper($m['plan_type']); ?></span></td>
                  <td><?php echo date('M d, Y', strtotime($m['maturity_date'])); ?></td>
                  <td><strong><?php echo number_format($m['fixed_roi_amount'], 2); ?> BMAN</strong></td>
                  <td><?php echo number_format($m['principal_amount'], 2); ?> BMAN</td>
                  <td><span class="badge pending">Awaiting</span></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Filters -->
      <div class="card mb-6">
        <div class="card-header border-0 pt-6">
          <div class="card-title fw-bold">Filters</div>
        </div>
        <div class="card-body pt-2">
          <form method="get" class="row g-3">
            <div class="col-md-2">
              <label class="form-label">User ID</label>
              <input type="text" class="form-control" name="user_id" value="<?php echo htmlspecialchars($filters['user_id'] ?? ''); ?>" placeholder="User ID">
            </div>
            <div class="col-md-2">
              <label class="form-label">Plan Type</label>
              <select class="form-select" name="plan_type">
                <option value="">All</option>
                <option value="fixed" <?php echo isset($filters['plan_type']) && $filters['plan_type'] === 'fixed' ? 'selected' : ''; ?>>Fixed</option>
                <option value="regular" <?php echo isset($filters['plan_type']) && $filters['plan_type'] === 'regular' ? 'selected' : ''; ?>>Regular</option>
                <option value="combo" <?php echo isset($filters['plan_type']) && $filters['plan_type'] === 'combo' ? 'selected' : ''; ?>>Combo</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">ROI Type</label>
              <select class="form-select" name="roi_type">
                <option value="">All</option>
                <option value="monthly" <?php echo isset($filters['roi_type']) && $filters['roi_type'] === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                <option value="maturity" <?php echo isset($filters['roi_type']) && $filters['roi_type'] === 'maturity' ? 'selected' : ''; ?>>Maturity</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Status</label>
              <select class="form-select" name="status">
                <option value="">All</option>
                <option value="success" <?php echo isset($filters['status']) && $filters['status'] === 'success' ? 'selected' : ''; ?>>Success</option>
                <option value="pending" <?php echo isset($filters['status']) && $filters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="failed" <?php echo isset($filters['status']) && $filters['status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">From Date</label>
              <input type="date" class="form-control" name="from_date" value="<?php echo htmlspecialchars($filters['from_date'] ?? ''); ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label">To Date</label>
              <input type="date" class="form-control" name="to_date" value="<?php echo htmlspecialchars($filters['to_date'] ?? ''); ?>">
            </div>
            <div class="col-12 d-flex gap-2">
              <button type="submit" class="btn btn-primary">Search</button>
              <a href="<?php echo current_url(); ?>" class="btn btn-light">Clear Filters</a>
              <button type="button" class="btn btn-info" id="btn-retry-failed">
                <i class="ki-duotone ki-repeat"></i> Retry Failed
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ROI Distribution History -->
      <div class="card">
        <div class="card-header border-0 pt-6">
          <div class="card-title fw-bold">ROI Distribution History</div>
        </div>
        <div class="card-body pt-2">
          <div class="table-responsive">
            <table class="roi-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>User ID</th>
                  <th>Stake ID</th>
                  <th>Plan</th>
                  <th>Type</th>
                  <th>Rate %</th>
                  <th>Amount (BMAN)</th>
                  <th>Status</th>
                  <th>Executed</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($roi_history as $row): ?>
                <tr>
                  <td><?php echo date('M d', strtotime($row['payment_date'])); ?></td>
                  <td><?php echo $row['user_id']; ?></td>
                  <td><?php echo $row['stake_id']; ?></td>
                  <td><span class="badge badge-light"><?php echo strtoupper($row['plan_type']); ?></span></td>
                  <td><?php echo ucfirst($row['roi_type']); ?></td>
                  <td><?php echo number_format($row['roi_rate_percent'], 2); ?>%</td>
                  <td><strong><?php echo number_format($row['roi_amount'], 2); ?></strong></td>
                  <td><span class="badge badge-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                  <td><?php echo date('M d, Y', strtotime($row['actual_payment_date'])); ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="d-flex align-items-center justify-content-between pt-4 border-top">
            <div class="text-muted fs-8">
              Showing records <?php echo (($page-1)*$limit)+1; ?> - <?php echo min($page*$limit, $total_records); ?> of <?php echo $total_records; ?>
            </div>
            <div class="d-flex gap-2">
              <?php if ($page > 1): ?>
              <a href="<?php echo current_url(); ?>?page=<?php echo $page-1; ?><?php echo !empty($filters) ? '&'.http_build_query($filters) : ''; ?>" class="btn btn-sm btn-light">← Previous</a>
              <?php endif; ?>
              <?php if ($total_records > $page * $limit): ?>
              <a href="<?php echo current_url(); ?>?page=<?php echo $page+1; ?><?php echo !empty($filters) ? '&'.http_build_query($filters) : ''; ?>" class="btn btn-sm btn-light">Next →</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
  <?php $this->load->view('admin/Layout/admin_footer'); ?>
</div></div></div></div>

<?php $this->load->view('admin/Layout/common_script'); ?>
<script>
  const base = '<?php echo base_url(); ?>';

  document.getElementById('btn-retry-failed').addEventListener('click', async () => {
    if (!confirm('Retry all pending/failed ROI distributions?')) return;

    const btn = event.target.closest('button');
    btn.disabled = true;
    btn.textContent = 'Processing...';

    try {
      const res = await fetch(base + 'admin/staking/roimanagement/validate_and_retry', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await res.json();

      if (data.status === 'success') {
        alert(`✓ ${data.processed} ROI records retried successfully`);
        location.reload();
      } else {
        alert('Error: ' + data.message);
      }
    } catch (e) {
      alert('Request failed: ' + e.message);
    } finally {
      btn.disabled = false;
      btn.textContent = 'Retry Failed';
    }
  });

  document.getElementById('btn-export-csv').addEventListener('click', () => {
    const params = new URLSearchParams(location.search);
    params.set('_export', 'csv');
    window.location.href = base + 'admin/staking/roimanagement/export_csv?' + params.toString();
  });
</script>
</body>
</html>
