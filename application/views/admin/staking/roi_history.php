<?php $this->load->view('admin/Layout/common_style'); ?>
<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
<style>
  .roi-card{border:1px solid rgba(0,0,0,.08);border-radius:16px;background:#fff;box-shadow:0 8px 24px rgba(15,23,42,.04)}
  .roi-stat{padding:1rem 1.25rem;border-top:3px solid #6366f1;border-radius:12px;background:#fff;box-shadow:0 8px 24px rgba(15,23,42,.04)}
  .roi-stat .v{font-size:1.5rem;font-weight:900;color:#0b1220}
  .roi-stat .l{font-size:.78rem;color:#6b7280;font-weight:700;text-transform:uppercase}
  .tiny{font-size:.78rem;color:#64748b}
  .mono{font-family:monospace;font-size:.78rem}
  .badge-fixed{background:rgba(239,68,68,.12);color:#7f1d1d}
  .badge-regular{background:rgba(34,197,94,.12);color:#065f46}
  .badge-combo{background:rgba(168,85,247,.12);color:#6b21a8}
  .err-cell{max-width:280px;white-space:normal;font-size:.75rem;color:#b42318}
</style>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true" data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true" data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">
<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
<div class="app-page flex-column flex-column-fluid" id="kt_app_page">
<?php $this->load->view('admin/Layout/admin_topbar'); ?>
<div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
<?php $this->load->view('admin/Layout/admin_sidebar'); ?>
<div class="app-main flex-column flex-row-fluid" id="kt_app_main">
<div class="d-flex flex-column flex-column-fluid">
  <div class="app-toolbar py-3 py-lg-6">
    <div class="app-container container-xxl d-flex flex-stack">
      <div>
        <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0"><?php echo $title; ?></h1>
        <div class="text-muted fs-7">Real distribution history from <span class="mono">roi_staking_management</span> + <span class="mono">onchain_transactions</span>. Runs: <a href="<?php echo base_url('admin/wallet/cron-lab'); ?>">Cron Lab</a>.</div>
      </div>
      <button id="retry-all" class="btn btn-sm btn-warning">Retry All Failed</button>
    </div>
  </div>
  <div class="app-content flex-column-fluid">
    <div class="app-container container-xxl">

      <div class="roi-card mb-6">
        <div class="card-header border-0 pt-6 px-6">
          <div class="card-title"><div class="fw-bold fs-4">Manual Send — Particular User</div></div>
        </div>
        <div class="card-body pt-3 pb-6 px-6">
          <div class="tiny mb-3">Look up a user and send their ROI now. Safe to use any time — it only credits whatever is actually due today per that record's schedule; it never pays ahead of schedule.</div>
          <div class="d-flex gap-2 mb-4" style="max-width:480px;">
            <input id="user-q" type="text" class="form-control form-control-sm" placeholder="User ID, username, or email">
            <button id="user-search" class="btn btn-sm btn-primary text-nowrap">Search</button>
          </div>
          <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-7 gy-3">
              <thead>
                <tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                  <th>ID</th><th>User</th><th>Plan</th><th>Status</th><th>Progress</th><th>Next Due</th><th>Remaining</th><th></th>
                </tr>
              </thead>
              <tbody id="user-records"><tr><td colspan="8" class="text-center text-muted py-6">Search for a user to see their ROI records.</td></tr></tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="row g-4 mb-6">
        <?php $stats = [
          ['Total ROI Paid', number_format($summary['total_paid'],4), '#22c55e'],
          ['Remaining To Pay', number_format($summary['total_remaining'],4), '#f59e0b'],
          ['Total Gas Paid', number_format($summary['total_gas_paid'],4), '#0ea5e9'],
          ['Distribution Events', number_format($summary['distributions']), '#6366f1'],
          ['Active Records', number_format($summary['active_count']), '#0ea5e9'],
          ['Completed', number_format($summary['completed_count']), '#22c55e'],
          ['Failed / Needs Retry', number_format($summary['failed_count']), '#ef4444'],
        ]; ?>
        <?php foreach ($stats as $s): ?>
        <div class="col-6 col-md-3 col-lg">
          <div class="roi-stat" style="border-top-color:<?php echo $s[2]; ?>;">
            <div class="v"><?php echo $s[1]; ?></div>
            <div class="l"><?php echo $s[0]; ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="roi-card mb-6">
        <div class="card-header border-0 pt-6 px-6">
          <div class="card-title"><div class="fw-bold fs-4">Failed / Needs Retry (<?php echo count($failed); ?>)</div></div>
        </div>
        <div class="card-body pt-3 pb-6 px-6">
          <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-7 gy-3">
              <thead>
                <tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                  <th>ID</th><th>User</th><th>Plan</th><th>Progress</th><th>Next / Maturity</th><th>Error</th><th>Updated</th><th></th>
                </tr>
              </thead>
              <tbody id="failed-body">
                <?php if (empty($failed)): ?>
                <tr><td colspan="8" class="text-center text-muted py-6">No failed records. 🎉</td></tr>
                <?php else: foreach ($failed as $f): ?>
                <tr id="row-<?php echo $f['id']; ?>" class="paged-row">
                  <td class="mono">#<?php echo $f['id']; ?></td>
                  <td><?php echo html_escape($f['username'] ?: ('User #'.$f['user_id'])); ?></td>
                  <td><span class="badge badge-<?php echo $f['plan_type']; ?>"><?php echo ucfirst($f['plan_type']); ?></span></td>
                  <td><?php echo (int)$f['regular_payments_completed'].'/'.(int)$f['regular_payment_count']; ?></td>
                  <td class="tiny"><?php echo html_escape($f['next_payment_date'] ?: $f['fixed_maturity_date'] ?: '—'); ?></td>
                  <td class="err-cell"><?php echo html_escape($f['error_message']); ?></td>
                  <td class="tiny"><?php echo html_escape($f['updated_at']); ?></td>
                  <td><button class="btn btn-sm btn-light-danger retry-one" data-id="<?php echo $f['id']; ?>">Retry</button></td>
                </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
          <?php if (count($failed) > 10): ?>
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-4 border-top" id="failed-pager"></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- All Staking & Distribution History — two tabs, each with its own
           filter + pagination. Same element IDs as before the redesign, so
           loadRecords()/loadDist() and their listeners need no JS changes. -->
      <div class="roi-card">
        <div class="card-header border-0 pt-6 px-6">
          <div class="card-title">
            <ul class="nav nav-tabs nav-line-tabs fs-6 border-0">
              <li class="nav-item"><a class="nav-link active fs-4 fw-bold" data-bs-toggle="tab" href="#roi-tab-records">All Staking</a></li>
              <li class="nav-item"><a class="nav-link fs-4 fw-bold" data-bs-toggle="tab" href="#roi-tab-dist">Distribution History</a></li>
            </ul>
          </div>
        </div>
        <div class="card-body pt-3 pb-6 px-6">
          <div class="tab-content">

            <div class="tab-pane fade show active" id="roi-tab-records">
              <div class="d-flex justify-content-end mb-4">
                <select id="rec-status" class="form-select form-select-sm w-auto">
                  <option value="">All Status</option>
                  <option value="active">Active</option>
                  <option value="in_progress">In Progress</option>
                  <option value="completed">Completed</option>
                  <option value="failed">Failed</option>
                </select>
              </div>
              <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-3">
                  <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                      <th>ID</th><th>User</th><th>Plan</th><th>Principal</th><th>Total ROI</th><th>Paid</th><th>Remaining</th><th>Status</th><th>Next Due</th><th></th>
                    </tr>
                  </thead>
                  <tbody id="rec-body"><tr><td colspan="10" class="text-center text-muted py-6">Loading...</td></tr></tbody>
                </table>
              </div>
              <div class="d-flex align-items-center justify-content-between pt-4 border-top">
                <div class="text-muted fs-8">Showing <span id="rec-showing">0</span> of <span id="rec-total">0</span> | Page <span id="rec-page">1</span></div>
                <div class="gap-2 d-flex">
                  <button id="rec-prev" class="btn btn-sm btn-light">← Previous</button>
                  <button id="rec-next" class="btn btn-sm btn-light">Next →</button>
                </div>
              </div>
            </div>

            <div class="tab-pane fade" id="roi-tab-dist">
              <div class="d-flex flex-wrap justify-content-end gap-2 mb-4">
                <select id="f-type" class="form-select form-select-sm w-auto">
                  <option value="">All Types</option>
                  <option value="roi_monthly">Monthly</option>
                  <option value="roi_maturity">Maturity</option>
                  <option value="principal_return">Principal Return</option>
                </select>
                <input id="f-user" type="number" min="1" placeholder="User ID" class="form-control form-control-sm w-auto" style="width:120px;">
                <button id="dist-refresh" class="btn btn-sm btn-light-primary">Filter</button>
              </div>
              <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-3">
                  <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                      <th>Date</th><th>User</th><th>Type</th><th class="text-end">Amount (BMAN)</th><th>Wallet</th><th>Gas Fee</th><th>Tx Hash</th><th>Ref</th><th>Status</th>
                    </tr>
                  </thead>
                  <tbody id="dist-body"><tr><td colspan="9" class="text-center text-muted py-6">Loading...</td></tr></tbody>
                </table>
              </div>
              <div class="d-flex align-items-center justify-content-between pt-4 border-top">
                <div class="text-muted fs-8">Showing <span id="dist-showing">0</span> of <span id="dist-total">0</span> | Page <span id="dist-page">1</span></div>
                <div class="gap-2 d-flex">
                  <button id="dist-prev" class="btn btn-sm btn-light">← Previous</button>
                  <button id="dist-next" class="btn btn-sm btn-light">Next →</button>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>

    </div>
  </div>
  <?php $this->load->view('admin/Layout/admin_footer'); ?>
</div></div></div></div>
<div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true"><i class="ki-duotone ki-arrow-up"><span class="path1"></span><span class="path2"></span></i></div>
<?php $this->load->view('admin/Layout/common_script'); ?>
<script src="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.js"></script>
<script>
(function(){
  const base = '<?php echo base_url(); ?>';
  let page = 1, limit = 25;

  /* Client-side pager for a table whose rows are already fully rendered
     server-side (Failed / Upcoming — both hard-capped small lists, not
     worth a round trip to paginate). Shows/hides .paged-row children of
     tbodyId in pages of pageSize, renders Prev/Next + page info into
     pagerId. No-op if the table has no rows or a pager container wasn't
     rendered (count <= 10, see the PHP condition around it). */
  function paginateStaticTable(tbodyId, pagerId, pageSize) {
    const tbody = document.getElementById(tbodyId);
    const pager = document.getElementById(pagerId);
    if (!tbody || !pager) return;
    const rows = Array.from(tbody.querySelectorAll('tr.paged-row'));
    if (!rows.length) return;
    let cur = 1;
    const totalPages = Math.max(1, Math.ceil(rows.length / pageSize));

    function render() {
      rows.forEach((tr, i) => {
        const p = Math.floor(i / pageSize) + 1;
        tr.style.display = (p === cur) ? '' : 'none';
      });
      const start = (cur - 1) * pageSize + 1;
      const end = Math.min(rows.length, cur * pageSize);
      pager.innerHTML =
        '<div class="text-muted fs-8">Showing ' + start + '–' + end + ' of ' + rows.length + '</div>' +
        '<div class="gap-2 d-flex">' +
        '<button class="btn btn-sm btn-light" id="' + pagerId + '-prev"' + (cur <= 1 ? ' disabled' : '') + '>← Previous</button>' +
        '<span class="align-self-center fs-8 text-muted">Page ' + cur + ' / ' + totalPages + '</span>' +
        '<button class="btn btn-sm btn-light" id="' + pagerId + '-next"' + (cur >= totalPages ? ' disabled' : '') + '>Next →</button>' +
        '</div>';
      const prevBtn = document.getElementById(pagerId + '-prev');
      const nextBtn = document.getElementById(pagerId + '-next');
      if (prevBtn) prevBtn.addEventListener('click', () => { if (cur > 1) { cur--; render(); } });
      if (nextBtn) nextBtn.addEventListener('click', () => { if (cur < totalPages) { cur++; render(); } });
    }
    render();
  }

  async function loadDist(){
    const fd = new FormData();
    fd.append('page', page);
    const type = document.getElementById('f-type').value;
    const uid = document.getElementById('f-user').value;
    if (type) fd.append('tx_type', type);
    if (uid) fd.append('user_id', uid);
    const body = document.getElementById('dist-body');
    body.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-6">Loading...</td></tr>';
    try {
      const res = await fetch(base + 'admin/staking/roi-history/list', { method:'POST', body: fd, headers:{'X-Requested-With':'XMLHttpRequest'} });
      const j = await res.json();
      if (j.status !== 'success') { body.innerHTML = '<tr><td colspan="9" class="text-danger text-center py-6">Failed to load.</td></tr>'; return; }
      if (!j.rows.length) { body.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-6">No distributions found.</td></tr>'; document.getElementById('dist-showing').textContent = '0'; document.getElementById('dist-total').textContent = j.total; return; }
      // Same status→color convention as admin/all-transaction's badge() helper.
      const distStatusCls = {confirmed:'success',pending:'warning',processing:'info',failed:'danger',reverted:'danger',partial:'warning',cancelled:'secondary'};
      const esc = s => String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
      body.innerHTML = j.rows.map(r => {
        const amt = Number(r.amount || 0).toLocaleString(undefined, { maximumFractionDigits: 8 });
        const gas = r.gas_fee_total ? Number(r.gas_fee_total).toLocaleString(undefined,{maximumFractionDigits:8}) : '—';
        const st = r.status || '—';
        const reason = r.failure_reason || r.revert_message;
        const statusCell = '<span class="badge badge-light-'+(distStatusCls[st]||'secondary')+' text-uppercase">'+esc(st)+'</span>'+
          (reason ? '<div class="err-cell mt-1">'+esc(reason)+'</div>' : '');
        return '<tr>'+
          '<td class="tiny">'+String(r.created_at||'').slice(0,16)+'</td>'+
          '<td>'+(r.username || ('User #'+r.user_id))+'</td>'+
          '<td><span class="badge badge-light">'+(r.tx_type||'—')+'</span></td>'+
          '<td class="text-end">'+amt+'</td>'+
          '<td>'+(r.wallet_type||'—')+'</td>'+
          '<td>'+gas+'</td>'+
          '<td class="mono">'+(r.tx_hash||'internal')+'</td>'+
          '<td class="mono">'+(r.reference_id||'—')+'</td>'+
          '<td>'+statusCell+'</td>'+
        '</tr>';
      }).join('');
      document.getElementById('dist-showing').textContent = j.rows.length;
      document.getElementById('dist-total').textContent = j.total;
      document.getElementById('dist-page').textContent = page;
      document.getElementById('dist-prev').disabled = page <= 1;
      document.getElementById('dist-next').disabled = (page * limit) >= j.total;
    } catch (e) {
      body.innerHTML = '<tr><td colspan="9" class="text-danger text-center py-6">'+e.message+'</td></tr>';
    }
  }

  document.getElementById('dist-refresh').addEventListener('click', ()=>{ page = 1; loadDist(); });
  document.getElementById('dist-prev').addEventListener('click', ()=>{ if (page > 1) { page--; loadDist(); } });
  document.getElementById('dist-next').addEventListener('click', ()=>{ page++; loadDist(); });

  // Delegated so it works for both server-rendered rows (Failed table) and
  // dynamically-rendered rows (Manual Send user search results).
  document.body.addEventListener('click', async (ev) => {
    const btn = ev.target.closest('.retry-one');
    if (!btn) return;
    const id = btn.dataset.id;
    const original = btn.textContent;
    btn.disabled = true; btn.textContent = 'Sending...';
    try {
      const res = await fetch(base + 'admin/staking/roi-history/retry/' + id, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'} });
      const j = await res.json();
      alert(j.message || 'Send executed');
      window.location.reload();
    } catch (e) {
      alert('Send failed: ' + e.message);
      btn.disabled = false; btn.textContent = original;
    }
  });

  function renderUserRecords(records) {
    const body = document.getElementById('user-records');
    if (!records.length) { body.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-6">No ROI records for this user.</td></tr>'; return; }
    body.innerHTML = records.map(r => {
      const progress = r.plan_type === 'fixed'
        ? (r.fixed_status || 'pending')
        : (r.regular_payments_completed + '/' + r.regular_payment_count);
      const nextDue = r.next_payment_date || r.fixed_maturity_date || '—';
      return '<tr>'+
        '<td class="mono">#'+r.id+'</td>'+
        '<td>User #'+r.user_id+'</td>'+
        '<td><span class="badge badge-'+r.plan_type+'">'+r.plan_type.charAt(0).toUpperCase()+r.plan_type.slice(1)+'</span></td>'+
        '<td>'+r.overall_status+(r.error_message ? ' <span class="text-danger">(error)</span>' : '')+'</td>'+
        '<td>'+progress+'</td>'+
        '<td class="tiny">'+nextDue+'</td>'+
        '<td>'+Number(r.remaining_to_pay||0).toLocaleString(undefined,{maximumFractionDigits:4})+'</td>'+
        '<td><button class="btn btn-sm btn-light-primary retry-one" data-id="'+r.id+'">Send Now</button></td>'+
      '</tr>';
    }).join('');
  }

  async function searchUser() {
    const q = document.getElementById('user-q').value.trim();
    if (!q) return;
    const body = document.getElementById('user-records');
    body.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-6">Searching...</td></tr>';
    const fd = new FormData();
    fd.append('q', q);
    try {
      const res = await fetch(base + 'admin/staking/roi-history/lookup-user', { method:'POST', body: fd, headers:{'X-Requested-With':'XMLHttpRequest'} });
      const j = await res.json();
      if (j.status !== 'success') { body.innerHTML = '<tr><td colspan="8" class="text-danger text-center py-6">'+(j.message||'Search failed')+'</td></tr>'; return; }
      if (!j.users.length) { body.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-6">No matching user.</td></tr>'; return; }
      renderUserRecords(j.records);
    } catch (e) {
      body.innerHTML = '<tr><td colspan="8" class="text-danger text-center py-6">'+e.message+'</td></tr>';
    }
  }
  document.getElementById('user-search').addEventListener('click', searchUser);
  document.getElementById('user-q').addEventListener('keydown', (e) => { if (e.key === 'Enter') searchUser(); });

  document.getElementById('retry-all').addEventListener('click', async () => {
    const btn = document.getElementById('retry-all');
    btn.disabled = true; btn.textContent = 'Retrying...';
    try {
      const res = await fetch(base + 'admin/staking/roi-history/retry-all', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'} });
      const j = await res.json();
      alert(j.message || 'Retry-all executed');
      window.location.reload();
    } catch (e) {
      alert('Retry failed: ' + e.message);
      btn.disabled = false; btn.textContent = 'Retry All Failed';
    }
  });

  // Default listing — every staking + ROI record, no search required.
  let recPage = 1;
  async function loadRecords(){
    const fd = new FormData();
    fd.append('page', recPage);
    const status = document.getElementById('rec-status').value;
    if (status) fd.append('status', status);
    const body = document.getElementById('rec-body');
    body.innerHTML = '<tr><td colspan="10" class="text-center text-muted py-6">Loading...</td></tr>';
    try {
      const res = await fetch(base + 'admin/staking/roi-history/records', { method:'POST', body: fd, headers:{'X-Requested-With':'XMLHttpRequest'} });
      const j = await res.json();
      if (j.status !== 'success') { body.innerHTML = '<tr><td colspan="10" class="text-danger text-center py-6">Failed to load.</td></tr>'; return; }
      if (!j.rows.length) { body.innerHTML = '<tr><td colspan="10" class="text-center text-muted py-6">No staking records yet.</td></tr>'; document.getElementById('rec-showing').textContent = '0'; document.getElementById('rec-total').textContent = j.total; return; }
      body.innerHTML = j.rows.map(r => {
        const progress = r.plan_type === 'fixed' ? (r.fixed_status || 'pending') : (r.regular_payments_completed + '/' + r.regular_payment_count);
        const nextDue = r.next_payment_date || r.fixed_maturity_date || '—';
        const who = r.username || r.email || ('User #' + r.user_id);
        const specialTag = Number(r.is_special) ? ' <span class="badge" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;font-weight:900;">★ SPECIAL'+(r.duration_years ? ' '+r.duration_years+'y' : '')+'</span>' : '';
        return '<tr>'+
          '<td class="mono">#'+r.id+'</td>'+
          '<td>'+who+'</td>'+
          '<td><span class="badge badge-'+r.plan_type+'">'+r.plan_type.charAt(0).toUpperCase()+r.plan_type.slice(1)+'</span>'+specialTag+'</td>'+
          '<td>'+Number(r.principal_amount||0).toLocaleString(undefined,{maximumFractionDigits:4})+'</td>'+
          '<td>'+Number(r.total_roi_amount||0).toLocaleString(undefined,{maximumFractionDigits:4})+'</td>'+
          '<td>'+Number(r.total_paid_amount||0).toLocaleString(undefined,{maximumFractionDigits:4})+'</td>'+
          '<td>'+Number(r.remaining_to_pay||0).toLocaleString(undefined,{maximumFractionDigits:4})+'</td>'+
          '<td>'+r.overall_status+(r.error_message ? ' <span class="text-danger">(error)</span>' : '')+' — '+progress+'</td>'+
          '<td class="tiny">'+nextDue+'</td>'+
          '<td><button class="btn btn-sm btn-light-primary retry-one" data-id="'+r.id+'">Send Now</button></td>'+
        '</tr>';
      }).join('');
      document.getElementById('rec-showing').textContent = j.rows.length;
      document.getElementById('rec-total').textContent = j.total;
      document.getElementById('rec-page').textContent = recPage;
      document.getElementById('rec-prev').disabled = recPage <= 1;
      document.getElementById('rec-next').disabled = (recPage * 25) >= j.total;
    } catch (e) {
      body.innerHTML = '<tr><td colspan="10" class="text-danger text-center py-6">'+e.message+'</td></tr>';
    }
  }
  document.getElementById('rec-status').addEventListener('change', ()=>{ recPage = 1; loadRecords(); });
  document.getElementById('rec-prev').addEventListener('click', ()=>{ if (recPage > 1) { recPage--; loadRecords(); } });
  document.getElementById('rec-next').addEventListener('click', ()=>{ recPage++; loadRecords(); });

  loadRecords();
  loadDist();
  paginateStaticTable('failed-body', 'failed-pager', 10);
})();
</script>
</body>
</html>
