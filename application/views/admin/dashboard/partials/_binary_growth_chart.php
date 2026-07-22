<!-- Growth Chart: new registrations vs staking purchases -->
<div class="col-xl-12">
  <div class="card card-flush h-md-100">
    <div class="card-header pt-5">
      <h3 class="card-title align-items-start flex-column">
        <span class="card-label fw-bold text-gray-900">Growth — <span id="dash-growth-range-label">Last 30 Days</span></span>
        <span class="text-muted mt-1 fw-semibold fs-7">New registrations vs. staking purchases</span>
      </h3>
      <div class="card-toolbar">
        <select id="dash-growth-range" class="form-select form-select-sm w-150px">
          <option value="7">Last 7 Days</option>
          <option value="30" selected>Last 30 Days</option>
          <option value="90">Last 90 Days</option>
          <option value="365">Last Year</option>
        </select>
      </div>
    </div>
    <div class="card-body pt-2">
      <div id="dash-growth-chart" class="chart-container" style="height:340px;"></div>
    </div>
  </div>
</div>
