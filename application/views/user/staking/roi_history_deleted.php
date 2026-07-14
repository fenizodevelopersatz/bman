<?php
/**
 * User ROI History View
 * Shows all ROI distributions received by the user
 */
?>
<style>
  .roi-history-container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
  .roi-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
  .roi-card { background: #f8f9fa; border-radius: 12px; padding: 1.5rem; border-left: 4px solid #6366f1; }
  .roi-card.total { border-left-color: #22c55e; }
  .roi-card.monthly { border-left-color: #3b82f6; }
  .roi-card.maturity { border-left-color: #f59e0b; }
  .roi-value { font-size: 1.75rem; font-weight: 900; color: #0b1220; }
  .roi-label { font-size: 0.875rem; color: #6b7280; font-weight: 700; margin-top: 0.5rem; }

  .roi-table-wrapper { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
  .roi-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
  .roi-table th { background: #f3f4f6; padding: 1rem; text-align: left; font-weight: 700; color: #6b7280; }
  .roi-table td { padding: 1rem; border-bottom: 1px solid #e5e7eb; }
  .roi-table tr:hover { background: #f9fafb; }
  .roi-table tr:last-child td { border-bottom: none; }

  .badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; }
  .badge-monthly { background: rgba(59,130,246,.12); color: #1e40af; }
  .badge-maturity { background: rgba(245,158,11,.12); color: #92400e; }
  .badge-fixed { background: rgba(239,68,68,.12); color: #7f1d1d; }
  .badge-regular { background: rgba(34,197,94,.12); color: #065f46; }
  .badge-combo { background: rgba(168,85,247,.12); color: #6b21a8; }

  .section-title { font-size: 1.25rem; font-weight: 900; color: #0b1220; margin: 2rem 0 1rem; }
  .filter-group { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
  .filter-group input, .filter-group select { padding: 0.5rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 0.875rem; }

  .empty-state { text-align: center; padding: 3rem 1rem; color: #6b7280; }
  .empty-state-icon { font-size: 3rem; margin-bottom: 1rem; }

  .pagination { display: flex; justify-content: center; gap: 1rem; margin-top: 2rem; }
  .pagination a, .pagination span { padding: 0.5rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 6px; text-decoration: none; }
  .pagination a:hover { background: #f3f4f6; }
  .pagination .active { background: #6366f1; color: #fff; border-color: #6366f1; }
</style>

<div class="roi-history-container">
  <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">ROI History</h1>
  <p style="color: #6b7280; margin-bottom: 2rem;">Track all your ROI distributions from staking</p>

  <!-- Summary Cards -->
  <div class="roi-summary">
    <div class="roi-card total">
      <div class="roi-value"><?php echo number_format($summary['total_roi'] ?? 0, 2); ?></div>
      <div class="roi-label">Total ROI Received (BMAN)</div>
    </div>
    <div class="roi-card monthly">
      <div class="roi-value"><?php echo number_format($summary['monthly_roi'] ?? 0, 2); ?></div>
      <div class="roi-label">Monthly Payments</div>
    </div>
    <div class="roi-card maturity">
      <div class="roi-value"><?php echo number_format($summary['maturity_roi'] ?? 0, 2); ?></div>
      <div class="roi-label">Maturity Payouts</div>
    </div>
    <div class="roi-card">
      <div class="roi-value"><?php echo count($roi_history); ?></div>
      <div class="roi-label">Total Distributions</div>
    </div>
  </div>

  <!-- Filters -->
  <div class="section-title">Distribution History</div>
  <div class="filter-group">
    <select onchange="filterRoi(this.value)" style="flex: 1; max-width: 150px;">
      <option value="">All Plans</option>
      <option value="fixed">Fixed</option>
      <option value="regular">Regular</option>
      <option value="combo">Combo</option>
    </select>
    <select onchange="filterRoi(this.value)" style="flex: 1; max-width: 150px;">
      <option value="">All Types</option>
      <option value="monthly">Monthly</option>
      <option value="maturity">Maturity</option>
    </select>
    <input type="date" id="fromDate" onchange="filterRoi()" placeholder="From Date" style="flex: 1; max-width: 150px;">
    <input type="date" id="toDate" onchange="filterRoi()" placeholder="To Date" style="flex: 1; max-width: 150px;">
  </div>

  <!-- ROI Distribution Table -->
  <?php if (!empty($roi_history)): ?>
  <div class="roi-table-wrapper">
    <table class="roi-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Stake ID</th>
          <th>Plan Type</th>
          <th>ROI Type</th>
          <th>Rate %</th>
          <th>ROI Amount (BMAN)</th>
          <th>Wallet</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($roi_history as $roi): ?>
        <tr>
          <td><?php echo date('M d, Y', strtotime($roi['payment_date'])); ?></td>
          <td><strong><?php echo $roi['stake_id']; ?></strong></td>
          <td><span class="badge badge-<?php echo $roi['plan_type']; ?>"><?php echo ucfirst($roi['plan_type']); ?></span></td>
          <td><span class="badge badge-<?php echo $roi['roi_type']; ?>"><?php echo ucfirst($roi['roi_type']); ?></span></td>
          <td><?php echo number_format($roi['roi_rate_percent'], 2); ?>%</td>
          <td><strong><?php echo number_format($roi['roi_amount'], 2); ?></strong></td>
          <td><?php echo ucfirst($roi['wallet_type']); ?></td>
          <td>
            <?php if ($roi['status'] === 'success'): ?>
              <span style="color: #22c55e; font-weight: 700;">✓ Paid</span>
            <?php else: ?>
              <span style="color: #ef4444; font-weight: 700;">✗ Pending</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
  <div class="pagination">
    <?php if ($page > 1): ?>
    <a href="?page=1">« First</a>
    <a href="?page=<?php echo $page - 1; ?>">← Previous</a>
    <?php endif; ?>

    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
      <?php if ($i === $page): ?>
      <span class="active"><?php echo $i; ?></span>
      <?php else: ?>
      <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
      <?php endif; ?>
    <?php endfor; ?>

    <?php if ($page < $total_pages): ?>
    <a href="?page=<?php echo $page + 1; ?>">Next →</a>
    <a href="?page=<?php echo $total_pages; ?>">Last »</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php else: ?>
  <div class="empty-state">
    <div class="empty-state-icon">📊</div>
    <h2 style="color: #0b1220; margin-bottom: 0.5rem;">No ROI History Yet</h2>
    <p>Your ROI distributions will appear here once you purchase a staking package</p>
  </div>
  <?php endif; ?>

</div>

<script>
function filterRoi(value) {
  // Implement filtering logic
  console.log('Filter changed:', value);
  // Would normally filter the table or make an AJAX call
}
</script>
