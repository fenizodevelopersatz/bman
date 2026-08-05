<?php $this->load->view('admin/Layout/common_style'); ?>
<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
<style>
  .cron-pill{display:inline-flex;align-items:center;gap:.35rem;padding:.3rem .65rem;border-radius:999px;font-size:.75rem;font-weight:700}
  .cron-card{border:1px solid rgba(0,0,0,.08);border-radius:16px;background:#fff;box-shadow:0 8px 24px rgba(15,23,42,.04)}
  .cron-card .head{display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;padding:1rem 1rem .75rem;border-bottom:1px solid rgba(0,0,0,.06)}
  .cron-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem}
  .cron-body{padding:1rem}
  .cron-pre{background:#0f172a;color:#dbeafe;border-radius:12px;padding:1rem;max-height:280px;overflow:auto;font-size:12px;white-space:pre-wrap}
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
        <div class="text-muted fs-7">Developer testing page for all scheduled crons.</div>
      </div>
      <a href="<?php echo base_url('admin/staking/roi-history'); ?>" class="btn btn-sm btn-light-primary">ROI Distribution History →</a>
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

  function pretty(v){ try { return JSON.stringify(v, null, 2); } catch(e){ return String(v); } }
  function setOut(k, data){ const el = document.getElementById('out-'+k); if(el) el.textContent = pretty(data); }

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

})();
</script>
