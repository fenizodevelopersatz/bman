<!-- Bonus Reduction: bonus wallet -> admin wallet reclaim -->
<div class="col-xl-6">
  <div class="card card-flush h-md-100">
    <div class="card-header pt-5">
      <h3 class="card-title align-items-start flex-column">
        <span class="card-label fw-bold text-gray-900">Bonus Reduction</span>
        <span class="text-muted mt-1 fw-semibold fs-7"><span id="dash-bonus-percent">—</span>% every <span id="dash-bonus-interval">—</span> day(s), bonus wallet → admin wallet</span>
      </h3>
      <div class="card-toolbar">
        <a href="<?php echo base_url('admin/wallet/admin-wallet'); ?>" class="btn btn-sm btn-light-primary">Full History</a>
      </div>
    </div>
    <div class="card-body pt-2">
      <div class="row g-4">
        <div class="col-6">
          <div class="border border-gray-300 border-dashed rounded p-3 text-center">
            <div class="fs-4 fw-bold text-primary counted" id="dash-bonus-lifetime" data-kt-initialized="1">0</div>
            <div class="fs-8 text-muted">Lifetime Reduced (BMAN)</div>
          </div>
        </div>
        <div class="col-6">
          <div class="border border-gray-300 border-dashed rounded p-3 text-center">
            <div class="fs-4 fw-bold text-success counted" id="dash-bonus-recent" data-kt-initialized="1">0</div>
            <div class="fs-8 text-muted">Last <span id="dash-bonus-interval-2">—</span> Day(s)</div>
          </div>
        </div>
        <div class="col-6">
          <div class="border border-gray-300 border-dashed rounded p-3 text-center">
            <div class="fs-4 fw-bold text-gray-900 counted" id="dash-bonus-wallet-balance" data-kt-initialized="1">0</div>
            <div class="fs-8 text-muted">Admin Wallet Balance</div>
          </div>
        </div>
        <div class="col-6">
          <div class="border border-gray-300 border-dashed rounded p-3 text-center">
            <div class="fs-4 fw-bold text-gray-900 counted" id="dash-bonus-count" data-kt-initialized="1">0</div>
            <div class="fs-8 text-muted">Reductions Logged</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
