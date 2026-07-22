<!-- System Health -->
<div class="col-xl-6">
  <div class="card card-flush h-md-100">
    <div class="card-header pt-5">
      <h3 class="card-title align-items-start flex-column">
        <span class="card-label fw-bold text-gray-900">System Health</span>
      </h3>
    </div>
    <div class="card-body pt-2">
      <div class="d-flex flex-column gap-3">
        <div class="d-flex justify-content-between align-items-center">
          <span class="fs-6 text-gray-700">Database</span>
          <span id="dash-health-db">—</span>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <span class="fs-6 text-gray-700">Wallet RPC (24h success rate)</span>
          <span id="dash-health-rpc">—</span>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <span class="fs-6 text-gray-700">SMTP</span>
          <span id="dash-health-smtp">—</span>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <span class="fs-6 text-gray-700">Storage Used</span>
          <span id="dash-health-storage">—</span>
        </div>
        <div class="separator my-1"></div>
        <div class="fs-7 fw-bold text-muted">Cron Jobs</div>
        <div id="dash-health-cron" class="d-flex flex-column gap-2"></div>
      </div>
    </div>
  </div>
</div>
