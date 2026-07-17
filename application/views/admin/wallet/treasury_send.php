<?php $this->load->view('admin/Layout/common_style'); ?>
<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true"
  data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true"
  data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
  data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">
  <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
    <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
      <?php $this->load->view('admin/Layout/admin_topbar'); ?>
      <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
        <?php $this->load->view('admin/Layout/admin_sidebar'); ?>
        <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
          <div class="d-flex flex-column flex-column-fluid">
            <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
              <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                  <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0"><?php echo $title; ?></h1>
                  <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted"><a href="<?php echo base_url(); ?>" class="text-muted text-hover-primary">Finance</a></li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted"><?php echo $title; ?></li>
                  </ul>
                </div>
              </div>
            </div>
            <div id="kt_app_content" class="app-content flex-column-fluid mt-10">
              <div id="kt_app_content_container" class="app-container container-xxl">
                <?php $this->load->view('notification'); ?>

                <div class="alert alert-warning d-flex align-items-center p-5 mb-5">
                  <i class="ki-outline ki-shield-tick fs-2hx text-warning me-4"></i>
                  <div class="d-flex flex-column">
                    <span class="fw-bold">This sends NEW BMAN straight from the Treasury wallet on-chain.</span>
                    <span class="fs-7 text-muted">No internal ledger wallet is debited or credited — the recipient's dashboard balances (Exchange/Earning/Staking/Bonus) do not change. Only their real on-chain balance does. Use for manual rewards, airdrops, or one-off adjustments.</span>
                  </div>
                </div>

                <!-- Settings -->
                <div class="card mb-5 mb-xxl-8">
                  <div class="card-header border-transparent pt-5">
                    <h3 class="card-title fw-bold">Settings <span class="text-muted fs-7 fw-normal ms-2">disabled + dry-run by default</span></h3>
                  </div>
                  <div class="card-body pt-2 pb-8">
                    <form id="ts-settings-form" class="d-flex flex-wrap align-items-end gap-5">
                      <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="ts-enabled" name="enabled" <?php echo !empty($settings['enabled']) ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-semibold" for="ts-enabled">Enabled</label>
                      </div>
                      <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="ts-dryrun" name="dry_run" <?php echo !empty($settings['dry_run']) ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-semibold" for="ts-dryrun">Dry-run (no real broadcast)</label>
                      </div>
                      <div>
                        <label class="form-label fw-semibold fs-7 mb-1">Minimum Treasury Reserve (BMAN)</label>
                        <input type="number" step="0.00000001" min="0" name="min_treasury_reserve" class="form-control form-control-solid form-control-sm w-200px" value="<?php echo html_escape($settings['min_treasury_reserve']); ?>" />
                      </div>
                      <button type="submit" class="btn btn-light-primary btn-sm">Save Settings</button>
                    </form>
                  </div>
                </div>

                <!-- Send -->
                <div class="card mb-5 mb-xxl-8">
                  <div class="card-header border-transparent pt-5">
                    <h3 class="card-title fw-bold">New Direct Send</h3>
                  </div>
                  <div class="card-body pt-2 pb-8">
                    <form id="ts-send-form">
                      <div class="row g-4">
                        <div class="col-md-5">
                          <label class="form-label fw-semibold fs-7">Recipient</label>
                          <select class="form-select form-select-solid" name="user_id" id="ts-user" data-placeholder="Search member by name / referral / email…">
                            <option value=""></option>
                          </select>
                        </div>
                        <div class="col-md-3">
                          <label class="form-label fw-semibold fs-7">Amount (BMAN)</label>
                          <input type="number" step="0.00000001" min="0" name="amount" class="form-control form-control-solid" required />
                        </div>
                        <div class="col-md-4">
                          <label class="form-label fw-semibold fs-7">Reason / Note</label>
                          <input type="text" name="reason" maxlength="255" class="form-control form-control-solid" placeholder="e.g. manual reward, airdrop…" />
                        </div>
                        <div class="col-md-12">
                          <button type="submit" class="btn btn-primary">Send</button>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>

                <!-- History -->
                <div class="card mb-5 mb-xxl-8">
                  <div class="card-header border-transparent pt-5"><h3 class="card-title fw-bold">Send History</h3></div>
                  <div class="card-body pt-3 pb-9">
                    <div class="table-responsive">
                      <table class="table align-middle table-row-dashed fs-6 gy-4">
                        <thead><tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                          <th>Reference</th><th>Recipient</th><th>To Address</th><th class="text-end">Amount</th><th>Status</th><th>Tx Hash</th><th>Reason</th><th>Date</th>
                        </tr></thead>
                        <tbody class="text-gray-700 fw-semibold">
                          <?php foreach ($history as $h): ?>
                          <tr>
                            <td><span class="text-monospace fs-8 fw-bold text-primary"><?php echo html_escape($h['ref']); ?></span><?php if (!empty($h['dry_run'])): ?><div class="badge badge-light-warning fs-9">DRY-RUN</div><?php endif; ?></td>
                            <td><?php echo html_escape($h['username'] ?: ('#'.$h['user_id'])); ?><div class="text-muted fs-8"><?php echo html_escape($h['referral_id'] ?? ''); ?></div></td>
                            <td><span class="text-monospace fs-8"><?php echo html_escape(substr($h['to_address'], 0, 10).'…'.substr($h['to_address'], -6)); ?></span></td>
                            <td class="text-end fw-bold"><?php echo number_format((float)$h['amount'], 4); ?></td>
                            <td><span class="badge badge-light-<?php echo $h['status']==='completed'?'success':($h['status']==='failed'?'danger':'warning'); ?>"><?php echo strtoupper($h['status']); ?></span></td>
                            <td><span class="text-monospace fs-8"><?php echo $h['tx_hash'] ? html_escape(substr($h['tx_hash'],0,18).'…') : '—'; ?></span></td>
                            <td class="fs-8"><?php echo html_escape($h['reason'] ?: '—'); ?></td>
                            <td class="text-muted fs-8"><?php echo html_escape($h['created_at']); ?></td>
                          </tr>
                          <?php endforeach; ?>
                          <?php if (empty($history)): ?><tr><td colspan="8" class="text-muted">No sends yet.</td></tr><?php endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
          <?php $this->load->view('admin/Layout/admin_footer'); ?>
        </div>
      </div>
    </div>
  </div>
  <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true"><i class="ki-duotone ki-arrow-up"><span class="path1"></span><span class="path2"></span></i></div>
  <?php $this->load->view('admin/Layout/common_script'); ?>
  <script src="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.js"></script>
  <script>
  (function(){
    const base='<?php echo base_url(); ?>';
    function toast(m,ok){ if(window.Swal) Swal.fire({text:m,icon:ok?'success':'error',buttonsStyling:false,confirmButtonText:'Ok',customClass:{confirmButton:'btn btn-primary'}}); else alert(m); }
    async function post(url, fd){ const r=await fetch(base+url,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}}); let j={}; try{j=await r.json();}catch(_){}; return {ok:r.ok&&j.status==='success',j}; }

    if (window.jQuery && jQuery.fn.select2) {
      jQuery('#ts-user').select2({
        width: '100%', minimumInputLength: 0, placeholder: 'Search member…',
        ajax: {
          url: base+'admin/finance/treasury-send/users', dataType: 'json', delay: 250,
          data: (p) => ({ q: p.term || '', page: p.page || 1 }),
          processResults: (data, p) => { p.page = p.page || 1; return { results: data.results, pagination: { more: data.pagination.more } }; }
        }
      });
    }

    document.getElementById('ts-send-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      if (!fd.get('user_id')) { toast('Select a recipient.', false); return; }
      const { ok, j } = await post('admin/finance/treasury-send/send', fd);
      toast(j.message || (ok ? 'Sent.' : 'Failed.'), ok);
      if (ok) setTimeout(() => location.reload(), 900);
    });

    document.getElementById('ts-settings-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      fd.set('enabled', document.getElementById('ts-enabled').checked ? '1' : '');
      fd.set('dry_run', document.getElementById('ts-dryrun').checked ? '1' : '');
      const { ok, j } = await post('admin/finance/treasury-send/settings', fd);
      toast(j.message || (ok ? 'Saved.' : 'Failed.'), ok);
    });
  })();
  </script>
</body>
</html>
