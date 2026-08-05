<!-- Staking Analytics: lifecycle breakdown of user_stakes -->
<div class="col-xl-6">
  <div class="card card-flush h-md-100">
    <div class="card-header pt-5">
      <h3 class="card-title align-items-start flex-column">
        <span class="card-label fw-bold text-gray-900">Staking Analytics</span>
        <span class="text-muted mt-1 fw-semibold fs-7">Lifecycle of every stake on the platform</span>
      </h3>
    </div>
    <div class="card-body pt-2">
      <div class="row g-4">
        <div class="col-6">
          <div class="border border-gray-300 border-dashed rounded p-4 text-center">
            <div class="fs-2 fw-bold text-success counted" id="dash-stakes-active" data-kt-initialized="1">0</div>
            <div class="fs-7 text-muted">Active</div>
          </div>
        </div>
        <div class="col-6">
          <div class="border border-gray-300 border-dashed rounded p-4 text-center">
            <div class="fs-2 fw-bold text-info counted" id="dash-stakes-reached-maturity" data-kt-initialized="1">0</div>
            <div class="fs-7 text-muted">Reached Maturity Date</div>
          </div>
        </div>
        <div class="col-6">
          <div class="border border-gray-300 border-dashed rounded p-4 text-center">
            <div class="fs-2 fw-bold text-dark counted" id="dash-stakes-withdrawn" data-kt-initialized="1">0</div>
            <div class="fs-7 text-muted">Withdrawn</div>
          </div>
        </div>
        <div class="col-6">
          <div class="border border-gray-300 border-dashed rounded p-4 text-center">
            <div class="fs-2 fw-bold text-gray-900 counted" id="dash-stakes-avg" data-kt-initialized="1">0</div>
            <div class="fs-7 text-muted">Average Stake (BMAN)</div>
          </div>
        </div>
        <!-- Lock Wallet: BMAN principal still locked, platform-wide. A different
             kind of figure than the 4 lifecycle counts above (a currency sum, not
             a count), and deliberately excludes packages already past maturity —
             unlike "total_staking_bman"/"locked_in_staking" shown elsewhere on
             this dashboard, which don't apply that filter. Full-width on purpose. -->
        <div class="col-12">
          <div class="border border-gray-300 border-dashed rounded p-4 text-center">
            <div class="fs-2 fw-bold text-primary counted" id="dash-stakes-locked" data-kt-initialized="1">0</div>
            <div class="fs-7 text-muted">Lock Wallet (BMAN) — excludes packages that already reached maturity</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
