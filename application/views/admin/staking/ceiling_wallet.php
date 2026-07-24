<?php $this->load->view('admin/Layout/common_style'); ?>
<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
<?php echo rank_badge_css(); ?>
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
                  <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0"><?php echo $title; ?></h1>
                  <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">Staking</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">Ceiling Wallet</li>
                  </ul>
                </div>
              </div>
            </div>
            <div id="kt_app_content" class="app-content flex-column-fluid mt-8">
              <div id="kt_app_content_container" class="app-container container-xxl">
                <?php $this->load->view('notification'); ?>

                <div class="alert alert-secondary d-flex align-items-center mb-6">
                  <i class="ki-outline ki-information fs-2 me-3"></i>
                  <div class="fw-semibold">The <b>Ceiling Wallet</b> is a system-only wallet. It holds binary/group
                    income above each user's package earning ceiling. Members cannot see, transfer, or withdraw it.</div>
                </div>

                <!-- Total held -->
                <div class="row g-5 g-xl-8 mb-6">
                  <div class="col-md-4">
                    <div class="card bg-light-primary">
                      <div class="card-body">
                        <div class="text-gray-600 fw-semibold fs-7 text-uppercase">Total Ceiling Held</div>
                        <div class="text-primary fw-bold fs-2x mt-2"><?php echo number_format((float)$total_held, 4); ?>
                          <span class="fs-6 fw-semibold text-muted">BMAN</span></div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- User-wise held -->
                <div class="card mb-6">
                  <div class="card-header pt-6"><h3 class="card-title fw-bold">User-wise Ceiling Held</h3></div>
                  <div class="card-body pt-3 pb-9">
                    <div class="table-responsive">
                      <table class="table align-middle table-row-dashed fs-7 gy-4">
                        <thead><tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                          <th>User</th><th>Current Rank</th><th class="text-end">Held</th><th class="text-end">Lifetime Held</th>
                          <th class="text-end">Released</th><th class="text-end">Actions</th>
                        </tr></thead>
                        <tbody class="text-gray-700 fw-semibold">
                        <?php if (empty($held_by_user)): ?>
                          <tr><td colspan="6" class="text-center text-muted py-6">No ceiling-held balances yet.</td></tr>
                        <?php else: foreach ($held_by_user as $r): ?>
                          <tr>
                            <td><?php echo html_escape(($r['username'] ?? '') ?: ('#'.$r['user_id'])); ?>
                              <div class="text-muted fs-8"><?php echo html_escape($r['referral_id'] ?? ''); ?> · UID <?php echo (int)$r['user_id']; ?></div></td>
                            <td><?php echo rank_cell_html($r['current_rank_id'] ?? null, 26); ?></td>
                            <td class="text-end fw-bold text-primary"><?php echo number_format((float)$r['held_balance'], 4); ?></td>
                            <td class="text-end text-muted"><?php echo number_format((float)$r['total_held'], 4); ?></td>
                            <td class="text-end text-muted"><?php echo number_format((float)$r['total_released'], 4); ?></td>
                            <td class="text-end">
                              <button class="btn btn-sm btn-light-success cw-release" data-id="<?php echo (int)$r['user_id']; ?>"
                                data-held="<?php echo (float)$r['held_balance']; ?>">Release</button>
                              <button class="btn btn-sm btn-light-warning cw-adjust" data-id="<?php echo (int)$r['user_id']; ?>">Adjust</button>
                            </td>
                          </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <!-- History -->
                <div class="card">
                  <div class="card-header pt-6"><h3 class="card-title fw-bold">Ceiling Ledger (latest 300)</h3></div>
                  <div class="card-body pt-3 pb-9">
                    <div class="table-responsive">
                      <table class="table align-middle table-row-dashed fs-7 gy-4">
                        <thead><tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                          <th>#</th><th>User</th><th>Type</th><th class="text-end">Amount</th>
                          <th class="text-end">Held After</th><th>Reference</th><th>Note</th><th>When</th>
                        </tr></thead>
                        <tbody class="text-gray-700 fw-semibold">
                        <?php if (empty($history)): ?>
                          <tr><td colspan="8" class="text-center text-muted py-6">No ledger entries yet.</td></tr>
                        <?php else: foreach ($history as $h):
                          $badge = $h['tx_type']==='CEILING_HOLD' ? 'primary' : ($h['tx_type']==='CEILING_RELEASE' ? 'success' : 'warning');
                        ?>
                          <tr>
                            <td class="text-muted fs-8"><?php echo (int)$h['id']; ?></td>
                            <td><?php echo html_escape(($h['username'] ?? '') ?: ('#'.$h['user_id'])); ?>
                              <div class="text-muted fs-8"><?php echo html_escape($h['referral_id'] ?? ''); ?></div></td>
                            <td><span class="badge badge-light-<?php echo $badge; ?>"><?php echo html_escape($h['tx_type']); ?></span></td>
                            <td class="text-end <?php echo ((float)$h['amount']<0)?'text-danger':'text-success'; ?>">
                              <?php echo number_format((float)$h['amount'], 4); ?></td>
                            <td class="text-end text-muted"><?php echo number_format((float)$h['held_after'], 4); ?></td>
                            <td class="fs-8 text-muted"><?php echo html_escape(($h['reference_type'] ?? '').' '.($h['reference_id'] ?? '')); ?></td>
                            <td class="fs-8 text-muted"><?php echo html_escape($h['description'] ?? ''); ?></td>
                            <td class="fs-8 text-muted"><?php echo html_escape($h['created_at']); ?></td>
                          </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    var CW_RELEASE_URL = "<?php echo base_url('admin/staking/ceiling-wallet/release'); ?>";
    var CW_ADJUST_URL  = "<?php echo base_url('admin/staking/ceiling-wallet/adjust'); ?>";
  </script>
  <?php $this->load->view('admin/Layout/common_script'); ?>
  <script>
    (function () {
      function post(url, data, done) {
        fetch(url, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
          credentials: 'same-origin',
          body: new URLSearchParams(data).toString()
        }).then(r => r.json()).then(done).catch(() => done({ status: 'error', message: 'Network error' }));
      }
      function ask(title, text, inputPlaceholder, cb) {
        if (window.Swal) {
          Swal.fire({ title: title, text: text, input: 'text', inputPlaceholder: inputPlaceholder,
            showCancelButton: true, confirmButtonText: 'Confirm' })
            .then(res => { if (res.isConfirmed) cb(res.value); });
        } else { var v = prompt(title + '\n' + text); if (v !== null) cb(v); }
      }
      document.querySelectorAll('.cw-release').forEach(function (b) {
        b.addEventListener('click', function () {
          var uid = this.dataset.id, held = this.dataset.held;
          ask('Release from Ceiling', 'Held: ' + held + ' BMAN. Amount to release to member Earning wallet:', 'e.g. 100', function (amt) {
            if (!amt) return;
            post(CW_RELEASE_URL, { user_id: uid, amount: amt, credit_wallet: 'earning' }, function (r) {
              if (window.Swal) Swal.fire(r.status === 'success' ? 'Released' : 'Error', r.message || '', r.status === 'success' ? 'success' : 'error');
              if (r.status === 'success') setTimeout(function(){ location.reload(); }, 900);
            });
          });
        });
      });
      document.querySelectorAll('.cw-adjust').forEach(function (b) {
        b.addEventListener('click', function () {
          var uid = this.dataset.id;
          ask('Adjust Ceiling', 'Signed amount (+ into ceiling, − out). e.g. -50 or 25:', 'e.g. -50', function (amt) {
            if (!amt) return;
            post(CW_ADJUST_URL, { user_id: uid, amount: amt }, function (r) {
              if (window.Swal) Swal.fire(r.status === 'success' ? 'Adjusted' : 'Error', r.message || '', r.status === 'success' ? 'success' : 'error');
              if (r.status === 'success') setTimeout(function(){ location.reload(); }, 900);
            });
          });
        });
      });
    })();
  </script>
</body>
</html>
