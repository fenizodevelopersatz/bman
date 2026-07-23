<!-- Chat Activity Trend: distinct users active per day. NOTE: users.last_active_at
     is a chat-poll heartbeat only (single writer: the Direct Chat tab), not a
     sitewide presence signal — labeled accordingly rather than as general
     "active users", which no column in this codebase actually tracks. -->
<div class="col-xl-12">
  <div class="card card-flush h-md-100">
    <div class="card-header pt-5">
      <h3 class="card-title align-items-start flex-column">
        <span class="card-label fw-bold text-gray-900">Chat Activity Trend — <span id="dash-activeusers-range-label">Last 30 Days</span></span>
        <span class="text-muted mt-1 fw-semibold fs-7">Distinct members active in Direct Chat per day</span>
      </h3>
      <div class="card-toolbar">
        <select id="dash-activeusers-range" class="form-select form-select-sm w-150px">
          <option value="7">Last 7 Days</option>
          <option value="30" selected>Last 30 Days</option>
          <option value="90">Last 90 Days</option>
          <option value="365">Last Year</option>
        </select>
      </div>
    </div>
    <div class="card-body pt-2">
      <div id="dash-activeusers-chart" class="chart-container" style="height:280px;"></div>
    </div>
  </div>
</div>
