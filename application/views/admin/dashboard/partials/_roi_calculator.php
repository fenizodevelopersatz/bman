<!-- ROI Liability Calculator: future obligations by time window, weighed
     against Available Treasury (from the Treasury Dashboard card above) so
     each row can be flagged Safe / Warning / Treasury Risk. -->
<div class="col-xl-12">
  <div class="card card-flush h-md-100">
    <div class="card-header pt-5">
      <h3 class="card-title align-items-start flex-column">
        <span class="card-label fw-bold text-gray-900">ROI Liability Calculator</span>
        <span class="text-muted mt-1 fw-semibold fs-7">Future obligations by window, vs. Available Treasury</span>
      </h3>
    </div>
    <div class="card-body pt-2">
      <div class="table-responsive">
        <table class="table table-row-dashed fs-7 align-middle">
          <thead>
            <tr class="fw-bold text-muted"><th>Period</th><th>Amount (BMAN)</th><th>Status</th></tr>
          </thead>
          <tbody id="dash-roi-calc-body">
            <tr><td>30 Days</td><td class="counted" id="dash-roi-calc-d30">0</td><td id="dash-roi-calc-d30-badge">—</td></tr>
            <tr><td>90 Days</td><td class="counted" id="dash-roi-calc-d90">0</td><td id="dash-roi-calc-d90-badge">—</td></tr>
            <tr><td>1 Year</td><td class="counted" id="dash-roi-calc-d365">0</td><td id="dash-roi-calc-d365-badge">—</td></tr>
            <tr><td>Lifetime</td><td class="counted" id="dash-roi-calc-lifetime">0</td><td id="dash-roi-calc-lifetime-badge">—</td></tr>
          </tbody>
        </table>
      </div>
      <div class="text-muted fs-9 mt-2">Safe: comfortably under Available Treasury. Warning: over half of it. Treasury Risk: this window's liability exceeds what's currently available.</div>
    </div>
  </div>
</div>
