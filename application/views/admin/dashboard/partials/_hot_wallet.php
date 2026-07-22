<!-- Hot Wallet Live Balance — real on-chain balance, not the internal DB ledger sum -->
<div class="col-xl-12" id="dash-hotwallet-wrap">
  <div class="card card-flush mb-5 mb-xl-8" style="background:linear-gradient(135deg,#fef9c3,#fef3c7);border:1px solid #fde68a;">
    <div class="card-body py-4">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
          <i class="ki-duotone ki-wallet fs-2x text-warning"><span class="path1"></span><span class="path2"></span></i>
          <div>
            <div class="fw-bold text-gray-900">
              Hot Wallet — Live On-Chain Balance
              <button type="button" class="btn btn-sm btn-icon btn-light ms-2" id="dash-hotwallet-refresh" title="Refresh from blockchain">
                <i class="ki-duotone ki-arrows-circle fs-4"><span class="path1"></span><span class="path2"></span></i>
              </button>
            </div>
            <div class="fs-8 text-muted" id="dash-hotwallet-address">—</div>
          </div>
        </div>
        <div class="d-flex gap-5" id="dash-hotwallet-figures">
          <div class="text-center">
            <div class="fs-4 fw-bold text-gray-900" id="dash-hotwallet-bnb">—</div>
            <div class="fs-9 text-muted">BNB (gas)</div>
          </div>
          <div class="text-center">
            <div class="fs-4 fw-bold text-gray-900" id="dash-hotwallet-bman">—</div>
            <div class="fs-9 text-muted">BMAN</div>
          </div>
          <div class="text-center" id="dash-hotwallet-gas-wrap" style="display:none;">
            <div class="fs-4 fw-bold text-gray-900" id="dash-hotwallet-gas-bnb">—</div>
            <div class="fs-9 text-muted">Gas Wallet BNB</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
