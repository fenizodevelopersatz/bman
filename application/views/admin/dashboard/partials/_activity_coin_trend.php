<!-- User Activity & Coin Trend — platform-wide version of the member dashboard's
     own "User Activity & Coin Trend" widget (same five series: active users,
     bonus used, staking done, earning coin, coin withdrawal), summed across
     every member instead of one user's team. Rendered with ApexCharts (not
     Chart.js) to stay consistent with every other chart already on this page
     rather than loading a second charting library for one widget. -->
<div class="col-xl-12">
  <div class="card card-flush h-md-100">
    <div class="card-header pt-5">
      <h3 class="card-title align-items-start flex-column">
        <span class="card-label fw-bold text-gray-900">User Activity &amp; Coin Trend</span>
        <span class="text-muted mt-1 fw-semibold fs-7">Platform-wide, all members — <span id="dash-activitytrend-label">Months</span></span>
      </h3>
      <div class="card-toolbar">
        <select id="dash-activitytrend-range" class="form-select form-select-sm w-150px">
          <option value="daily">Days</option>
          <option value="monthly" selected>Months</option>
          <option value="yearly">Yearly</option>
        </select>
      </div>
    </div>
    <div class="card-body pt-2">
      <div class="row g-4 mb-4">
        <div class="col-md col-6">
          <div class="border border-gray-300 border-dashed rounded p-3 text-center">
            <div class="fs-4 fw-bold text-gray-500 counted" id="dash-activitytrend-active" data-kt-initialized="1">0</div>
            <div class="fs-8 text-muted">Active Users</div>
          </div>
        </div>
        <div class="col-md col-6">
          <div class="border border-gray-300 border-dashed rounded p-3 text-center">
            <div class="fs-4 fw-bold text-warning counted" id="dash-activitytrend-bonus" data-kt-initialized="1">0</div>
            <div class="fs-8 text-muted">Bonus Used (BMAN)</div>
          </div>
        </div>
        <div class="col-md col-6">
          <div class="border border-gray-300 border-dashed rounded p-3 text-center">
            <div class="fs-4 fw-bold counted" style="color:#8B5CF6;" id="dash-activitytrend-staking" data-kt-initialized="1">0</div>
            <div class="fs-8 text-muted">Staking Done</div>
          </div>
        </div>
        <div class="col-md col-6">
          <div class="border border-gray-300 border-dashed rounded p-3 text-center">
            <div class="fs-4 fw-bold text-primary counted" id="dash-activitytrend-earning" data-kt-initialized="1">0</div>
            <div class="fs-8 text-muted">Earning Coin (BMAN)</div>
          </div>
        </div>
        <div class="col-md col-6">
          <div class="border border-gray-300 border-dashed rounded p-3 text-center">
            <div class="fs-4 fw-bold text-success counted" id="dash-activitytrend-withdraw" data-kt-initialized="1">0</div>
            <div class="fs-8 text-muted">Coin Withdrawal (BMAN)</div>
          </div>
        </div>
      </div>
      <div id="dash-activitytrend-chart" class="chart-container" style="height:340px;"></div>
    </div>
  </div>
</div>
