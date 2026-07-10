<?php $this->load->view('admin/Layout/common_style'); ?>
<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
<style>
  .cron-pill{display:inline-flex;align-items:center;gap:.35rem;padding:.3rem .65rem;border-radius:999px;font-size:.75rem;font-weight:700}
  .cron-card{border:1px solid rgba(0,0,0,.08);border-radius:16px;background:#fff;box-shadow:0 8px 24px rgba(15,23,42,.04)}
  .cron-card .head{display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;padding:1rem 1rem .75rem;border-bottom:1px solid rgba(0,0,0,.06)}
  .cron-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem}
  .cron-body{padding:1rem}
  .cron-pre{background:#0f172a;color:#dbeafe;border-radius:12px;padding:1rem;max-height:280px;overflow:auto;font-size:12px;white-space:pre-wrap}
  .audit-table td,.audit-table th{vertical-align:middle}
  .audit-table .mono{font-family:monospace;font-size:.78rem}
  .tiny{font-size:.78rem;color:#64748b}
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
        <div class="text-muted fs-7">Developer testing page for all scheduled crons and transaction audit.</div>
      </div>
    </div>
  </div>
  <div class="app-content flex-column-fluid">
    <div class="app-container container-xxl">
      <div class="row g-4 mb-6">
        <?php foreach ([['USDT Wallet','usdt','#50cd89'],['Exchange Wallet','exchange','#7239ea'],['Earning Wallet','earning','#009ef7'],['Staking Wallet','staking','#f1416c'],['Bonus Wallet','bonus','#ffc700']] as $c): ?>
        <div class="col-12 col-md">
          <div class="card card-flush h-100" style="border-top:3px solid <?php echo $c[2]; ?>;">
            <div class="card-body py-4">
              <span class="text-muted fw-semibold fs-8 d-block text-uppercase"><?php echo $c[0]; ?></span>
              <span class="fs-2 fw-bold text-gray-900 oc-num"><?php echo number_format((float)$balances[$c[1]], 4); ?></span>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="cron-grid mb-6">
        <?php foreach ($jobs as $job): ?>
        <div class="cron-card">
          <div class="head">
            <div>
              <div class="fw-bold fs-5"><?php echo html_escape($job['label']); ?></div>
              <div class="tiny"><?php echo html_escape($job['description']); ?></div>
            </div>
            <span class="cron-pill bg-light-info text-info"><?php echo strtoupper(html_escape($job['type'])); ?></span>
          </div>
          <div class="cron-body">
            <div class="tiny mb-2">Endpoint: <span class="mono"><?php echo html_escape($job['endpoint']); ?></span></div>
            <div class="d-flex gap-2 flex-wrap">
              <button class="btn btn-sm btn-primary cron-run" data-job="<?php echo html_escape($job['key']); ?>">Run now</button>
              <button class="btn btn-sm btn-light cron-copy" data-endpoint="<?php echo html_escape($job['endpoint']); ?>">Copy endpoint</button>
            </div>
            <div class="mt-3 cron-pre" id="out-<?php echo html_escape($job['key']); ?>">Ready.</div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="card mb-6">
        <div class="card-header border-0 pt-6">
          <div class="card-title">
            <div class="fw-bold fs-4">On-chain Transaction Audit</div>
          </div>
          <div class="card-toolbar gap-2">
            <select id="audit-type" class="form-select form-select-sm w-auto">
              <option value="">All Types</option>
              <option value="gas_fee">Gas Fee</option>
              <option value="deposit">Deposit</option>
              <option value="transfer">Transfer</option>
              <option value="swap">Swap</option>
              <option value="swap_bonus">Swap Bonus</option>
              <option value="profit">ROI</option>
            </select>
            <button id="audit-refresh" class="btn btn-sm btn-light-primary">Refresh</button>
          </div>
        </div>
        <div class="card-body pt-2">
          <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-7 gy-3 audit-table">
              <thead>
                <tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                  <th>Tx Hash</th><th>Date</th><th>Wallet</th><th>Type</th><th class="text-end">Amount</th><th>Token</th><th>Network</th><th>Status</th><th></th>
                </tr>
              </thead>
              <tbody id="audit-body"><tr><td colspan="9" class="text-center text-muted py-6">Loading...</td></tr></tbody>
            </table>
          </div>
          <div class="d-flex align-items-center justify-content-between pt-4 border-top">
            <div class="text-muted fs-8">
              Showing <span id="audit-showing">0</span> rows | Page <span id="audit-page">1</span>
            </div>
            <div class="gap-2 d-flex">
              <button id="audit-prev" class="btn btn-sm btn-light">← Previous</button>
              <button id="audit-next" class="btn btn-sm btn-light">Next →</button>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header border-0 pt-6">
          <div class="card-title"><div class="fw-bold fs-4">Last Run Output</div></div>
        </div>
        <div class="card-body"><div id="last-output" class="cron-pre">Click a run button to inspect the raw JSON response.</div></div>
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
  const cronToken = '<?php echo html_escape($cron_token ?: ""); ?>';
  const jobs = <?php echo json_encode($jobs); ?>;
  let page = 1, limit = 25;

  function pretty(v){ try { return JSON.stringify(v, null, 2); } catch(e){ return String(v); } }
  function setOut(k, data){ const el = document.getElementById('out-'+k); if(el) el.textContent = pretty(data); document.getElementById('last-output').textContent = pretty(data); }

  document.querySelectorAll('.cron-copy').forEach(btn => btn.addEventListener('click', async () => {
    const txt = base + btn.dataset.endpoint;
    try { await navigator.clipboard.writeText(txt); btn.textContent = 'Copied'; setTimeout(()=>btn.textContent='Copy endpoint', 1200); } catch(e) {}
  }));

  document.querySelectorAll('.cron-run').forEach(btn => btn.addEventListener('click', async () => {
    const job = jobs.find(j => j.key === btn.dataset.job);
    if(!job) return;
    btn.disabled = true;
    btn.textContent = 'Running...';
    try {
      let res;
      if (job.key === 'match') {
        res = await fetch(base + job.endpoint, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'} });
      } else {
        const url = new URL(base + job.endpoint);
        if (cronToken) url.searchParams.set('token', cronToken);
        res = await fetch(url.toString(), { method:'GET', headers:{'X-Requested-With':'XMLHttpRequest'} });
      }
      const data = await res.json();
      setOut(job.key, data);
    } catch (e) {
      setOut(job.key, { status:'error', message:e.message });
    } finally {
      btn.disabled = false;
      btn.textContent = 'Run now';
    }
  }));

  async function loadAudit(){
    const fd = new FormData();
    if (document.getElementById('audit-type').value) fd.append('tx_type', document.getElementById('audit-type').value);
    fd.append('page', page); fd.append('limit', limit);
    const body = document.getElementById('audit-body');
    body.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-6">Loading...</td></tr>';
    const res = await fetch(base + 'admin/wallet/onchain-transactions/list', { method:'POST', body: fd, headers:{'X-Requested-With':'XMLHttpRequest'} });
    const j = await res.json();
    if (j.status !== 'success') { body.innerHTML = '<tr><td colspan="9" class="text-danger py-6 text-center">Failed to load.</td></tr>'; return; }
    if (!j.rows.length) { body.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-6">No rows.</td></tr>'; document.getElementById('audit-showing').textContent = '0'; return; }
    body.innerHTML = j.rows.map(r => {
      const amt = Number(r.amount || 0).toLocaleString(undefined, { maximumFractionDigits: 8 });
      return '<tr>'+
        '<td class="mono">'+(r.tx_hash ? r.tx_hash : 'internal')+'</td>'+
        '<td>'+String(r.created_at || '').slice(0,16)+'</td>'+
        '<td><span class="badge badge-light">'+(r.wallet_type || '—')+'</span></td>'+
        '<td>'+((r.tx_type || '') + (r.reference_type ? ' / ' + r.reference_type : ''))+'</td>'+
        '<td class="text-end">'+amt+'</td>'+
        '<td>'+((r.token_symbol || '—'))+'</td>'+
        '<td>'+((r.network || '—'))+'</td>'+
        '<td>'+((r.status || '—'))+'</td>'+
        '<td></td>'+
      '</tr>';
    }).join('');
    document.getElementById('audit-showing').textContent = j.rows.length;
    document.getElementById('audit-page').textContent = page;
    document.getElementById('audit-prev').disabled = page <= 1;
    document.getElementById('audit-next').disabled = j.rows.length < limit;
  }

  document.getElementById('audit-refresh').addEventListener('click', loadAudit);
  document.getElementById('audit-type').addEventListener('change', ()=>{ page = 1; loadAudit(); });
  document.getElementById('audit-prev').addEventListener('click', ()=>{ if (page > 1) { page--; loadAudit(); } });
  document.getElementById('audit-next').addEventListener('click', ()=>{ page++; loadAudit(); });
  loadAudit();
})();
</script>
