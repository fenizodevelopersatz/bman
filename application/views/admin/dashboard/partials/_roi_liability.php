<!-- ROI Liability: what's been paid vs. still owed to members -->
<div class="col-xl-6">
  <div class="card card-flush h-md-100">
    <div class="card-header pt-5">
      <h3 class="card-title align-items-start flex-column">
        <span class="card-label fw-bold text-gray-900">ROI Liability</span>
        <span class="text-muted mt-1 fw-semibold fs-7">BMAN owed to members, paid vs. outstanding</span>
      </h3>
      <div class="card-toolbar">
        <a href="<?php echo base_url('admin/staking/roi-history'); ?>" class="btn btn-sm btn-light-primary">Full ROI History</a>
      </div>
    </div>
    <div class="card-body pt-2">
      <div class="row g-4">
        <div class="col-4">
          <div class="border border-gray-300 border-dashed rounded p-3 text-center">
            <div class="fs-4 fw-bold text-success counted" id="dash-roi-paid" data-kt-initialized="1">0</div>
            <div class="fs-8 text-muted">Total Paid</div>
          </div>
        </div>
        <div class="col-4">
          <div class="border border-gray-300 border-dashed rounded p-3 text-center">
            <div class="fs-4 fw-bold text-warning counted" id="dash-roi-pending" data-kt-initialized="1">0</div>
            <div class="fs-8 text-muted">Pending (Due)</div>
          </div>
        </div>
        <div class="col-4">
          <div class="border border-gray-300 border-dashed rounded p-3 text-center">
            <div class="fs-4 fw-bold text-info counted" id="dash-roi-future" data-kt-initialized="1">0</div>
            <div class="fs-8 text-muted">Future Liability</div>
          </div>
        </div>
      </div>
      <div class="text-muted fs-9 mt-3">Combines both live purchase paths (staking swap orders and direct stake purchases).</div>
    </div>
  </div>
</div>
