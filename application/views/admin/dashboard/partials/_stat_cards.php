<!-- Header Stat Cards: Members / Staking / Deposits / Withdrawals / Bonus Paid -->
<div class="row g-5 g-xl-8 mb-5 mb-xl-8" id="dash-stat-cards">
  <div class="col-xl-4 col-md-6">
    <div class="card card-flush h-md-100 dash-stat-card">
      <div class="card-body d-flex flex-column">
        <div class="d-flex align-items-center mb-2">
          <i class="ki-duotone ki-people fs-2x text-primary me-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
          <span class="fs-6 fw-semibold text-muted">Total Members</span>
        </div>
        <span class="fs-2x fw-bold text-gray-900 counted" id="dash-members-total" data-kt-initialized="1">0</span>
        <div class="d-flex gap-4 mt-2 flex-wrap">
          <span class="badge badge-light-success">Active <span class="counted" id="dash-members-active" data-kt-initialized="1">0</span></span>
          <span class="badge badge-light-danger">Inactive <span class="counted" id="dash-members-inactive" data-kt-initialized="1">0</span></span>
          <span class="badge badge-light-info">KYC Verified <span class="counted" id="dash-members-kyc-verified" data-kt-initialized="1">0</span></span>
          <span class="badge badge-light-success" title="Active in chat, last 5 minutes"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#22c55e;margin-right:5px;"></span>Online Now <span class="counted" id="dash-members-online" data-kt-initialized="1">0</span></span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-md-6">
    <div class="card card-flush h-md-100 dash-stat-card" id="dash-total-staking-card" style="cursor:pointer;" title="Click to view package/duration breakdown">
      <div class="card-body d-flex flex-column">
        <div class="d-flex align-items-center mb-2">
          <i class="ki-duotone ki-dollar fs-2x text-info me-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
          <span class="fs-6 fw-semibold text-muted">Total Staking</span>
        </div>
        <span class="fs-2x fw-bold text-gray-900 counted" id="dash-total-staking" data-kt-initialized="1">0</span>
        <span class="fs-7 text-muted mt-1">BMAN currently staked · click for breakdown</span>
      </div>
    </div>
  </div>

  <!-- Staking package/duration breakdown popup -->
  <div class="modal fade" id="dashStakingPopup" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Staking Package Breakdown</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="btn-group mb-4" role="group">
            <button type="button" class="btn btn-sm btn-light-primary active" data-months="">All Time</button>
            <button type="button" class="btn btn-sm btn-light" data-months="1">1 Month</button>
            <button type="button" class="btn btn-sm btn-light" data-months="3">3 Months</button>
            <button type="button" class="btn btn-sm btn-light" data-months="12">1 Year</button>
          </div>
          <div class="table-responsive">
            <table class="table table-row-dashed fs-7 align-middle">
              <thead>
                <tr class="fw-bold text-muted"><th>Package</th><th>Duration</th><th>Stakes</th><th>Total Staked (BMAN)</th></tr>
              </thead>
              <tbody id="dash-staking-popup-body"><tr><td colspan="4" class="text-muted">Loading…</td></tr></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-md-6">
    <div class="card card-flush h-md-100 dash-stat-card">
      <div class="card-body d-flex flex-column">
        <div class="d-flex align-items-center mb-2">
          <i class="ki-duotone ki-wallet fs-2x text-success me-2"><span class="path1"></span><span class="path2"></span></i>
          <span class="fs-6 fw-semibold text-muted">Total Deposits</span>
        </div>
        <span class="fs-2x fw-bold text-gray-900 counted" id="dash-total-deposits" data-kt-initialized="1">0</span>
        <span class="fs-7 text-muted mt-1">USDT credited</span>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-md-6">
    <div class="card card-flush h-md-100 dash-stat-card">
      <div class="card-body d-flex flex-column">
        <div class="d-flex align-items-center mb-2">
          <i class="ki-duotone ki-arrow-down-left fs-2x text-warning me-2"><span class="path1"></span><span class="path2"></span></i>
          <span class="fs-6 fw-semibold text-muted">Total Withdrawals</span>
        </div>
        <div class="d-flex flex-column">
          <span class="fs-4 fw-bold text-gray-900"><span class="counted" id="dash-total-withdrawals-usdt" data-kt-initialized="1">0</span> USDT</span>
          <span class="fs-4 fw-bold text-gray-900"><span class="counted" id="dash-total-withdrawals-bman" data-kt-initialized="1">0</span> BMAN</span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-md-6">
    <div class="card card-flush h-md-100 dash-stat-card">
      <div class="card-body d-flex flex-column">
        <div class="d-flex align-items-center mb-2">
          <i class="ki-duotone ki-gift fs-2x text-danger me-2"><span class="path1"></span><span class="path2"></span></i>
          <span class="fs-6 fw-semibold text-muted">Total Bonus Paid</span>
        </div>
        <span class="fs-2x fw-bold text-gray-900 counted" id="dash-total-bonus" data-kt-initialized="1">0</span>
        <span class="fs-7 text-muted mt-1">BMAN (staking + rank rewards)</span>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-md-6">
    <div class="card card-flush h-md-100 dash-stat-card">
      <div class="card-body d-flex flex-column">
        <div class="d-flex align-items-center mb-2">
          <i class="ki-duotone ki-message-text-2 fs-2x text-dark me-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
          <span class="fs-6 fw-semibold text-muted">Active in Chat</span>
        </div>
        <span class="fs-2x fw-bold text-gray-900 counted" id="dash-online-chat" data-kt-initialized="1">0</span>
        <span class="fs-7 text-muted mt-1">last 5 minutes</span>
      </div>
    </div>
  </div>
</div>
