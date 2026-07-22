<!-- Withdrawal Center -->
<div class="col-xl-6">
  <div class="card card-flush h-md-100">
    <div class="card-header pt-5">
      <h3 class="card-title align-items-start flex-column">
        <span class="card-label fw-bold text-gray-900">Withdrawal Center</span>
      </h3>
      <div class="card-toolbar">
        <a href="<?php echo base_url('admin/bman-withdrawals'); ?>" class="btn btn-sm btn-light-primary">View Requests</a>
      </div>
    </div>
    <div class="card-body pt-2">
      <div class="fs-7 fw-bold text-muted mb-2">BMAN</div>
      <div class="row g-3 mb-4">
        <div class="col-4"><div class="border border-gray-300 border-dashed rounded p-2 text-center"><div class="fs-4 fw-bold text-warning counted" id="dash-wd-bman-pending" data-kt-initialized="1">0</div><div class="fs-9 text-muted">Pending</div></div></div>
        <div class="col-4"><div class="border border-gray-300 border-dashed rounded p-2 text-center"><div class="fs-4 fw-bold text-success counted" id="dash-wd-bman-completed" data-kt-initialized="1">0</div><div class="fs-9 text-muted">Completed</div></div></div>
        <div class="col-4"><div class="border border-gray-300 border-dashed rounded p-2 text-center"><div class="fs-4 fw-bold text-danger counted" id="dash-wd-bman-rejected" data-kt-initialized="1">0</div><div class="fs-9 text-muted">Rejected</div></div></div>
      </div>
      <div class="fs-7 fw-bold text-muted mb-2">USDT</div>
      <div class="row g-3 mb-4">
        <div class="col-4"><div class="border border-gray-300 border-dashed rounded p-2 text-center"><div class="fs-4 fw-bold text-warning counted" id="dash-wd-usdt-pending" data-kt-initialized="1">0</div><div class="fs-9 text-muted">Pending</div></div></div>
        <div class="col-4"><div class="border border-gray-300 border-dashed rounded p-2 text-center"><div class="fs-4 fw-bold text-success counted" id="dash-wd-usdt-approved" data-kt-initialized="1">0</div><div class="fs-9 text-muted">Approved</div></div></div>
        <div class="col-4"><div class="border border-gray-300 border-dashed rounded p-2 text-center"><div class="fs-4 fw-bold text-danger counted" id="dash-wd-usdt-rejected" data-kt-initialized="1">0</div><div class="fs-9 text-muted">Rejected</div></div></div>
      </div>
      <div class="d-flex justify-content-between fs-6">
        <span class="text-muted">Today's Requests</span>
        <span class="fw-bold text-gray-900 counted" id="dash-wd-today" data-kt-initialized="1">0</span>
      </div>
    </div>
  </div>
</div>
