<!-- Recent Transactions (embedded, scaled-down view of Admin > All Transactions) -->
<div class="col-xl-12">
  <div class="card card-flush">
    <div class="card-header pt-5">
      <h3 class="card-title align-items-start flex-column">
        <span class="card-label fw-bold text-gray-900">Recent Transactions</span>
      </h3>
      <div class="card-toolbar d-flex gap-2">
        <a href="<?php echo base_url('admin/all-transaction/export/csv'); ?>" class="btn btn-sm btn-light">Export CSV</a>
        <a href="<?php echo base_url('admin/all-transaction/export/excel'); ?>" class="btn btn-sm btn-light">Export Excel</a>
        <a href="<?php echo base_url('admin/all-transaction/export/pdf'); ?>" target="_blank" class="btn btn-sm btn-light">Export PDF</a>
        <a href="<?php echo base_url('admin/all-transaction'); ?>" class="btn btn-sm btn-light-primary">View All</a>
      </div>
    </div>
    <div class="card-body pt-2">
      <div class="table-responsive">
        <table class="table table-row-dashed fs-7 align-middle">
          <thead>
            <tr class="fw-bold text-muted"><th>When</th><th>User</th><th>Tx ID</th><th>Type</th><th>Amount</th><th>Chain</th></tr>
          </thead>
          <tbody id="dash-recent-tx-body"><tr><td colspan="6" class="text-muted">Loading…</td></tr></tbody>
        </table>
        <div class="fs-8 text-muted mt-1">Click a row for full transaction details (gas fee, from/to, explorer link).</div>
      </div>
    </div>
  </div>
</div>

<!-- Transaction detail popup -->
<div class="modal fade" id="dashTxDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Transaction Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <table class="table table-row-dashed fs-7" id="dash-tx-detail-body">
          <tbody><tr><td class="text-muted">Loading…</td></tr></tbody>
        </table>
      </div>
    </div>
  </div>
</div>
