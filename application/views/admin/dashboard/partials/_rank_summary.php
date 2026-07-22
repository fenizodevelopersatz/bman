<!-- Rank Achievement Summary -->
<div class="col-xl-12">
  <div class="card card-flush h-md-100">
    <div class="card-header pt-5">
      <h3 class="card-title align-items-start flex-column">
        <span class="card-label fw-bold text-gray-900">Rank Achievement Summary</span>
        <span class="text-muted mt-1 fw-semibold fs-7">Members per rank tier</span>
      </h3>
      <div class="card-toolbar">
        <a href="<?php echo base_url('admin/staking/ranks'); ?>" class="btn btn-sm btn-light-primary">Manage Ranks</a>
      </div>
    </div>
    <div class="card-body pt-2">
      <div class="row g-4 mb-4">
        <div class="col-md-3 col-6">
          <div class="border border-gray-300 border-dashed rounded p-3 text-center">
            <div class="fs-3 fw-bold text-gray-900 counted" id="dash-rank-ranked-members" data-kt-initialized="1">0</div>
            <div class="fs-8 text-muted">Ranked Members</div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="border border-gray-300 border-dashed rounded p-3 text-center">
            <div class="fs-3 fw-bold text-success counted" id="dash-rank-promotions-24h" data-kt-initialized="1">0</div>
            <div class="fs-8 text-muted">Promotions (24h)</div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="border border-gray-300 border-dashed rounded p-3 text-center">
            <div class="fs-3 fw-bold text-primary counted" id="dash-rank-rewards-paid" data-kt-initialized="1">0</div>
            <div class="fs-8 text-muted">Rewards Paid</div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="border border-gray-300 border-dashed rounded p-3 text-center">
            <div class="fs-3 fw-bold text-danger counted" id="dash-rank-rewards-failed" data-kt-initialized="1">0</div>
            <div class="fs-8 text-muted">Rewards Failed</div>
          </div>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-row-dashed fs-7 align-middle">
          <thead>
            <tr class="fw-bold text-muted"><th>Rank</th><th>Members</th><th>% of Ranked</th></tr>
          </thead>
          <tbody id="dash-rank-distribution-body"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>
