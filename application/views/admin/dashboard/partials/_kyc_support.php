<!-- KYC Monitoring + Support Center -->
<div class="col-xl-6">
  <div class="card card-flush h-md-100">
    <div class="card-header pt-5">
      <h3 class="card-title align-items-start flex-column">
        <span class="card-label fw-bold text-gray-900">KYC Monitoring</span>
      </h3>
      <div class="card-toolbar">
        <a href="<?php echo base_url('admin/kyc'); ?>" class="btn btn-sm btn-light-primary">Review KYC</a>
      </div>
    </div>
    <div class="card-body pt-2">
      <div class="row g-3 mb-6">
        <div class="col-3"><div class="border border-gray-300 border-dashed rounded p-2 text-center"><div class="fs-4 fw-bold text-warning counted" id="dash-kyc-pending" data-kt-initialized="1">0</div><div class="fs-9 text-muted">Pending</div></div></div>
        <div class="col-3"><div class="border border-gray-300 border-dashed rounded p-2 text-center"><div class="fs-4 fw-bold text-success counted" id="dash-kyc-approved" data-kt-initialized="1">0</div><div class="fs-9 text-muted">Approved</div></div></div>
        <div class="col-3"><div class="border border-gray-300 border-dashed rounded p-2 text-center"><div class="fs-4 fw-bold text-danger counted" id="dash-kyc-rejected" data-kt-initialized="1">0</div><div class="fs-9 text-muted">Rejected</div></div></div>
        <div class="col-3"><div class="border border-gray-300 border-dashed rounded p-2 text-center"><div class="fs-4 fw-bold text-dark counted" id="dash-kyc-expired" data-kt-initialized="1">0</div><div class="fs-9 text-muted">Expired Docs</div></div></div>
      </div>

      <h3 class="card-title align-items-start flex-column mb-3">
        <span class="card-label fw-bold text-gray-900">Support Center</span>
      </h3>
      <div class="d-flex justify-content-end mb-2">
        <a href="<?php echo base_url('support'); ?>" class="btn btn-sm btn-light-primary">View Tickets</a>
      </div>
      <div class="row g-3">
        <div class="col-3"><div class="border border-gray-300 border-dashed rounded p-2 text-center"><div class="fs-4 fw-bold text-warning counted" id="dash-support-pending" data-kt-initialized="1">0</div><div class="fs-9 text-muted">Pending</div></div></div>
        <div class="col-3"><div class="border border-gray-300 border-dashed rounded p-2 text-center"><div class="fs-4 fw-bold text-info counted" id="dash-support-open" data-kt-initialized="1">0</div><div class="fs-9 text-muted">Open</div></div></div>
        <div class="col-3"><div class="border border-gray-300 border-dashed rounded p-2 text-center"><div class="fs-4 fw-bold text-success counted" id="dash-support-closed" data-kt-initialized="1">0</div><div class="fs-9 text-muted">Closed</div></div></div>
        <div class="col-3"><div class="border border-gray-300 border-dashed rounded p-2 text-center"><div class="fs-4 fw-bold text-gray-900 counted" id="dash-support-today" data-kt-initialized="1">0</div><div class="fs-9 text-muted">New Today</div></div></div>
      </div>
    </div>
  </div>
</div>
