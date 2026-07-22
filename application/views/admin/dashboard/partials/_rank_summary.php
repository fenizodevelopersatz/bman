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
        <div class="text-muted fs-8">Click a rank row to see which members currently hold it.</div>
      </div>
    </div>
  </div>
</div>

<!-- Rank members popup -->
<div class="modal fade" id="dashRankMembersModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="dashRankMembersTitle">Rank Members</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-row-dashed fs-7 align-middle">
            <thead>
              <tr class="fw-bold text-muted"><th>Member</th><th>Email</th><th>Group Volume (BMAN)</th><th>Achieved</th></tr>
            </thead>
            <tbody id="dash-rank-members-body"><tr><td colspan="4" class="text-muted">Loading…</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
