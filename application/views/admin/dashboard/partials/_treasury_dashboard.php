<!-- Financial Control Center: Treasury Dashboard.
     "Available Treasury" is computed from the Treasury wallet's REAL on-chain
     BMAN balance minus everything owed (locked stakes + ROI liability + bonus
     liability + pending withdrawals) — not from Total Supply, since most of
     total supply already sits in users' own wallets and isn't the platform's
     to spend. Total/Circulating Supply are shown separately as reference
     tokenomics figures. -->
<div class="col-xl-12">
  <div class="card card-flush h-md-100" id="dash-treasury-card">
    <div class="card-header pt-5">
      <h3 class="card-title align-items-start flex-column">
        <span class="card-label fw-bold text-gray-900">Treasury Dashboard <span class="text-muted fs-7 fw-semibold">— Financial Control Center</span></span>
        <span class="text-muted mt-1 fw-semibold fs-7">Real on-chain treasury reserves vs. everything the platform owes</span>
      </h3>
      <div class="card-toolbar">
        <span class="badge fs-6 px-4 py-3" id="dash-treasury-risk-badge">Loading…</span>
      </div>
    </div>
    <div class="card-body pt-2">
      <div id="dash-treasury-onchain-warning" class="alert alert-warning py-3 px-4 mb-4" style="display:none;"></div>

      <div class="row g-4 mb-4">
        <div class="col-md-3 col-6">
          <div class="border border-gray-300 border-dashed rounded p-3 text-center">
            <div class="fs-4 fw-bold text-gray-900" id="dash-treasury-total-supply">—</div>
            <div class="fs-8 text-muted">Total BMAN Supply <span class="text-muted" title="On-chain contract totalSupply()">ⓘ</span></div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="border border-gray-300 border-dashed rounded p-3 text-center">
            <div class="fs-4 fw-bold text-gray-900" id="dash-treasury-circulating">—</div>
            <div class="fs-8 text-muted">Circulating Supply</div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="border border-gray-300 border-dashed rounded p-3 text-center">
            <div class="fs-4 fw-bold text-warning" id="dash-treasury-locked">0</div>
            <div class="fs-8 text-muted">Locked in Staking</div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="border border-gray-300 border-dashed rounded p-3 text-center">
            <div class="fs-4 fw-bold text-danger" id="dash-treasury-roi">0</div>
            <div class="fs-8 text-muted">Future ROI Liability</div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="border border-gray-300 border-dashed rounded p-3 text-center">
            <div class="fs-4 fw-bold text-danger" id="dash-treasury-bonus">0</div>
            <div class="fs-8 text-muted">Bonus Wallet Liability</div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="border border-gray-300 border-dashed rounded p-3 text-center">
            <div class="fs-4 fw-bold text-gray-900" id="dash-treasury-withdrawable">0</div>
            <div class="fs-8 text-muted">Total Withdrawable Balance</div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="border border-gray-300 border-dashed rounded p-3 text-center">
            <div class="fs-4 fw-bold text-warning" id="dash-treasury-pending-wd">0</div>
            <div class="fs-8 text-muted">Total Pending Withdrawals</div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="border border-gray-300 border-dashed rounded p-3 text-center">
            <div class="fs-4 fw-bold text-success" id="dash-treasury-paid-wd">0</div>
            <div class="fs-8 text-muted">Total Paid Withdrawals</div>
          </div>
        </div>
      </div>

      <div class="separator my-4"></div>

      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
          <div class="fs-7 text-muted">Treasury Wallet Balance (on-chain)</div>
          <div class="fs-3 fw-bold text-gray-900" id="dash-treasury-wallet-balance">—</div>
        </div>
        <div class="fs-1 text-muted">−</div>
        <div>
          <div class="fs-7 text-muted">Locked + ROI + Bonus + Pending</div>
          <div class="fs-3 fw-bold text-gray-900" id="dash-treasury-total-liabilities">—</div>
        </div>
        <div class="fs-1 text-muted">=</div>
        <div class="text-end">
          <div class="fs-7 text-muted">Available Treasury</div>
          <div class="fs-2x fw-bold" id="dash-treasury-available">—</div>
        </div>
      </div>
    </div>
  </div>
</div>
