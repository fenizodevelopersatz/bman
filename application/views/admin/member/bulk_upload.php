<?php $this->load->view('admin/Layout/common_style'); ?>
<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
<style>
  .bmu-drop { border: 2px dashed var(--bs-gray-300); border-radius: .75rem; padding: 2.25rem 1rem; text-align: center; cursor: pointer; transition: border-color .15s, background-color .15s; }
  .bmu-drop:hover, .bmu-drop.is-over { border-color: var(--bs-primary); background: var(--bs-light-primary); }
  .bmu-preview-wrap { max-height: 460px; overflow: auto; }
  .bmu-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .8rem; }
  @media (max-width: 767.98px) { .bmu-drop { padding: 1.5rem .75rem; } }
  .bmu-settings-card .form-check-input { width: 2.4rem; height: 1.25rem; }
  .bmu-settings-card .form-check-label { padding-left: .5rem; }
  .bmu-wallet-badge { display: inline-flex; align-items: center; gap: .35rem; padding: .25rem .65rem; border-radius: .5rem; font-size: .75rem; font-weight: 600; }
  .bmu-wallet-exchange { background: var(--bs-light-info);    color: var(--bs-info); }
  .bmu-wallet-earning  { background: var(--bs-light-success); color: var(--bs-success); }
  .bmu-wallet-staking  { background: var(--bs-light-warning); color: var(--bs-warning); }
  .bmu-wallet-bonus    { background: var(--bs-light-primary); color: var(--bs-primary); }
</style>
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
                    <li class="breadcrumb-item text-muted"><a href="<?php echo base_url(); ?>network-member" class="text-muted text-hover-primary">Members Management</a></li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted"><?php echo $title; ?></li>
                  </ul>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <a href="<?php echo base_url(); ?>admin/member/bulk-upload/template" class="btn btn-sm btn-light-primary">
                    <i class="ki-outline ki-file-down fs-4"></i> Download Template
                  </a>
                </div>
              </div>
            </div>

            <div id="kt_app_content" class="app-content flex-column-fluid mt-10">
              <div id="kt_app_content_container" class="app-container container-xxl">
                <?php $this->load->view('notification'); ?>

                <!-- How it works -->
                <div class="alert alert-primary d-flex align-items-start p-5 mb-5">
                  <i class="ki-outline ki-information-5 fs-2hx text-primary me-4 mt-1"></i>
                  <div class="d-flex flex-column">
                    <span class="fw-bold mb-1">One sheet row becomes one member.</span>
                    <span class="fs-7 text-gray-700">
                      <b>username</b>, <b>email</b> and <b>password</b> create the login (email is the login identity, so it must be unique).
                      <b>reference_id</b> is the sponsor's referral code — the <u>only</u> placement input.
                      The binary engine fills that sponsor's left and right first, then spills down to the next free
                      position automatically, so there is no leg column to fill in.
                      An on-chain wallet address is generated for every member automatically — it is never read from the sheet.
                      <b>bman</b> is queued for the on-chain cron, <u>not</u> sent during import.
                    </span>
                  </div>
                </div>

                <div class="row g-5">
                  <!-- ===================== Upload ===================== -->
                  <div class="col-12">
                    <div class="card mb-5">
                      <div class="card-header border-transparent pt-5">
                        <h3 class="card-title fw-bold">1 · Upload &amp; Validate</h3>
                      </div>
                      <div class="card-body pt-2 pb-8">
                      <form id="bmu-form">
                          <div class="bmu-drop mb-5" id="bmu-drop">
                            <i class="ki-outline ki-file-up fs-3x text-primary mb-3 d-block"></i>
                            <div class="fw-bold fs-6" id="bmu-filename">Drop an .xlsx or .csv here, or click to choose</div>
                            <div class="text-muted fs-8 mt-1">Header row required · max 8&nbsp;MB · up to <?php echo (int)$settings['max_rows_per_file']; ?> rows</div>
                            <input type="file" name="sheet" id="bmu-file" class="d-none" accept=".xlsx,.xlsm,.csv,.txt" />
                          </div>

                          <div class="row g-4">
                            <!-- Default password -->
                            <div class="col-md-6">
                              <label class="form-label fw-semibold fs-7">Default password <span class="text-muted fw-normal">(used when a row's password cell is blank)</span></label>
                              <input type="text" name="default_password" class="form-control form-control-solid" autocomplete="off" placeholder="e.g. Welcome@2026" />
                            </div>

                            <!-- Wallet type -->
                            <div class="col-md-4">
                              <label class="form-label fw-semibold fs-7" for="bmu-wallet-type">
                                Default Wallet Type
                                <span class="text-muted fw-normal">(where BMAN is credited)</span>
                              </label>
                              <select name="wallet_type" id="bmu-wallet-type" class="form-select form-select-solid">
                                <?php
                                  $savedWallet = $settings['wallet_type'] ?? 'exchange';
                                  $walletOpts  = [
                                    'exchange' => 'Exchange Wallet',
                                    'earning'  => 'Earning Wallet',
                                    'staking'  => 'Staking Wallet',
                                    'bonus'    => 'Bonus Wallet',
                                  ];
                                  foreach ($walletOpts as $wk => $wl):
                                ?>
                                <option value="<?php echo $wk; ?>" <?php echo $savedWallet === $wk ? 'selected' : ''; ?>>
                                  <?php echo $wl; ?>
                                </option>
                                <?php endforeach; ?>
                              </select>
                              <div class="form-text text-muted fs-8 mt-1">
                                Add a <code>wallet_type</code> column in your sheet to override per row.
                              </div>
                            </div>

                            <!-- Queue BMAN toggle -->
                            <div class="col-md-2 d-flex align-items-end">
                              <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="bmu-sendbman" name="send_bman" checked>
                                <label class="form-check-label fw-semibold fs-7" for="bmu-sendbman">Queue BMAN</label>
                              </div>
                            </div>

                            <div class="col-12">
                              <button type="submit" class="btn btn-primary" id="bmu-validate">
                                <span class="indicator-label">Validate File</span>
                                <span class="indicator-progress">Parsing… <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                              </button>
                              <span class="text-muted fs-8 ms-3">Nothing is created yet — you review the result first.</span>
                            </div>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- =================== Cron Settings =================== -->
                <div class="card mb-5 bmu-settings-card">
                  <div class="card-header border-transparent pt-5" id="bmu-settings-heading"
                       style="cursor:pointer" data-bs-toggle="collapse" data-bs-target="#bmu-settings-body" aria-expanded="false">
                    <h3 class="card-title fw-bold d-flex align-items-center gap-3">
                      <i class="ki-outline ki-setting-3 fs-3 text-gray-600"></i>
                      BMAN Cron Settings
                      <?php
                        $cronLive = !empty($settings['enabled']) && empty($settings['dry_run']);
                        if (empty($settings['enabled']))      { $st = 'secondary'; $sl = 'DISABLED'; }
                        elseif (!empty($settings['dry_run'])) { $st = 'warning';   $sl = 'DRY-RUN'; }
                        else                                  { $st = 'success';   $sl = 'LIVE'; }
                      ?>
                      <span class="badge badge-<?php echo $st; ?> fs-9 ms-1"><?php echo $sl; ?></span>
                    </h3>
                    <div class="card-toolbar">
                      <span class="text-muted fs-8 me-2">Click to expand / collapse</span>
                      <i class="ki-outline ki-down fs-5 text-gray-500" id="bmu-settings-chevron"></i>
                    </div>
                  </div>
                  <div class="collapse" id="bmu-settings-body">
                    <div class="card-body pt-2 pb-8">
                      <form id="bmu-settings-form">
                        <div class="row g-5">

                          <!-- Master switches -->
                          <div class="col-md-6">
                            <div class="d-flex flex-column gap-4">
                              <div class="d-flex align-items-center justify-content-between border rounded p-4">
                                <div>
                                  <div class="fw-bold fs-6">Enable BMAN Cron</div>
                                  <div class="text-muted fs-8">Master switch — OFF means the queue just accumulates.</div>
                                </div>
                                <div class="form-check form-switch ms-4">
                                  <input class="form-check-input" type="checkbox" id="bcs-enabled" name="enabled"
                                         <?php echo !empty($settings['enabled']) ? 'checked' : ''; ?>>
                                </div>
                              </div>
                              <div class="d-flex align-items-center justify-content-between border rounded p-4">
                                <div>
                                  <div class="fw-bold fs-6">Dry-Run Mode</div>
                                  <div class="text-muted fs-8">Records a DRYRUN- hash — nothing is broadcast on-chain.</div>
                                </div>
                                <div class="form-check form-switch ms-4">
                                  <input class="form-check-input" type="checkbox" id="bcs-dryrun" name="dry_run"
                                         <?php echo !empty($settings['dry_run']) ? 'checked' : ''; ?>>
                                </div>
                              </div>
                              <div class="d-flex align-items-center justify-content-between border rounded p-4">
                                <div>
                                  <div class="fw-bold fs-6">Credit Wallet on Delivery</div>
                                  <div class="text-muted fs-8">Post the BMAN to the member's internal wallet after the on-chain send.</div>
                                </div>
                                <div class="form-check form-switch ms-4">
                                  <input class="form-check-input" type="checkbox" id="bcs-credit" name="credit_exchange_wallet"
                                         <?php echo !empty($settings['credit_exchange_wallet']) ? 'checked' : ''; ?>>
                                </div>
                              </div>
                            </div>
                          </div>

                          <!-- Numeric / select settings -->
                          <div class="col-md-6">
                            <div class="row g-4">
                              <div class="col-12">
                                <label class="form-label fw-semibold fs-7" for="bcs-wallet-type">
                                  Default Wallet Type
                                  <span class="text-muted fw-normal">(site-wide default for all new uploads)</span>
                                </label>
                                <select name="wallet_type" id="bcs-wallet-type" class="form-select form-select-solid">
                                  <?php
                                    $cw = $settings['wallet_type'] ?? 'exchange';
                                    $cwOpts = ['exchange'=>'Exchange Wallet','earning'=>'Earning Wallet','staking'=>'Staking Wallet','bonus'=>'Bonus Wallet'];
                                    foreach ($cwOpts as $k => $v):
                                  ?>
                                  <option value="<?php echo $k; ?>" <?php echo $cw === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                                  <?php endforeach; ?>
                                </select>
                                <div class="form-text fs-8 text-muted">This pre-selects the wallet type on the upload form. Each upload can still override it.</div>
                              </div>
                              <div class="col-sm-6">
                                <label class="form-label fw-semibold fs-7" for="bcs-reserve">Min Treasury Reserve (BMAN)</label>
                                <input type="number" name="min_treasury_reserve" id="bcs-reserve"
                                       class="form-control form-control-solid" step="0.00000001" min="0"
                                       value="<?php echo html_escape($settings['min_treasury_reserve']); ?>">
                                <div class="form-text fs-8 text-muted">Cron pauses if treasury would drop below this amount.</div>
                              </div>
                              <div class="col-sm-6">
                                <label class="form-label fw-semibold fs-7" for="bcs-batch">Max Batch Size</label>
                                <input type="number" name="max_batch_size" id="bcs-batch"
                                       class="form-control form-control-solid" min="1" max="500"
                                       value="<?php echo (int)$settings['max_batch_size']; ?>">
                                <div class="form-text fs-8 text-muted">Rows claimed per cron pass (max 500).</div>
                              </div>
                              <div class="col-sm-6">
                                <label class="form-label fw-semibold fs-7" for="bcs-maxrows">Max Rows per File</label>
                                <input type="number" name="max_rows_per_file" id="bcs-maxrows"
                                       class="form-control form-control-solid" min="1" max="20000"
                                       value="<?php echo (int)$settings['max_rows_per_file']; ?>">
                                <div class="form-text fs-8 text-muted">Guard against runaway sheets (max 20000).</div>
                              </div>
                            </div>
                          </div>

                          <div class="col-12 pt-2">
                            <button type="submit" class="btn btn-primary" id="bmu-save-settings">
                              <span class="indicator-label"><i class="ki-outline ki-check fs-4 me-1"></i>Save Settings</span>
                              <span class="indicator-progress">Saving… <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                          </div>

                        </div>
                      </form>
                    </div>
                  </div>
                </div>

                <!-- ===================== Preview ===================== -->
                <div class="card mb-5 d-none" id="bmu-preview-card">
                  <div class="card-header border-transparent pt-5 flex-wrap gap-3">
                    <h3 class="card-title fw-bold">2 · Review &amp; Import</h3>
                    <div class="card-toolbar flex-wrap gap-2">
                      <span class="badge badge-light-success fs-8" id="bmu-count-valid">0 valid</span>
                      <span class="badge badge-light-danger fs-8" id="bmu-count-invalid">0 invalid</span>
                      <span class="badge badge-light-info fs-8" id="bmu-count-bman">0 BMAN</span>
                      <button type="button" class="btn btn-sm btn-light" id="bmu-discard">Discard</button>
                      <button type="button" class="btn btn-sm btn-primary" id="bmu-import">
                        <span class="indicator-label">Import valid rows</span>
                        <span class="indicator-progress">Creating members… <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                      </button>
                    </div>
                  </div>
                  <div class="card-body pt-2 pb-6">
                    <div class="table-responsive bmu-preview-wrap">
                      <table class="table align-middle table-row-dashed fs-7 gy-3 mb-0">
                        <thead><tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                          <th>#</th><th>Username</th><th>Email</th><th>Reference ID (sponsor)</th>
                          <th class="text-end">BMAN</th><th>Wallet</th><th>Status</th><th>Message</th>
                        </tr></thead>
                        <tbody id="bmu-preview-body" class="text-gray-700 fw-semibold"></tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <!-- ============ History / audit + cron status ============ -->
                <div class="card mb-5">
                  <div class="card-header border-transparent pt-5 flex-wrap gap-3">
                    <h3 class="card-title fw-bold d-flex flex-column">
                      Upload History &amp; Transaction Audit
                      <span class="text-muted fs-8 fw-normal">Every sheet, its import result, and the on-chain BMAN delivery for each one</span>
                    </h3>
                    <div class="card-toolbar">
                      <a href="<?php echo base_url(); ?>admin/wallet/cron-lab" class="btn btn-sm btn-light-primary">Open Cron Lab</a>
                    </div>
                  </div>

                  <!-- Tab filters -->
                  <div class="px-9 border-bottom">
                    <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-6 fw-bold" id="bmu-history-tabs">
                      <li class="nav-item">
                        <a class="nav-link text-active-primary py-3 active" href="#" data-status="">All Uploads</a>
                      </li>
                      <li class="nav-item">
                        <a class="nav-link text-active-primary py-3" href="#" data-status="staged">Drafts (Staged)</a>
                      </li>
                      <li class="nav-item">
                        <a class="nav-link text-active-primary py-3" href="#" data-status="importing">Importing</a>
                      </li>
                      <li class="nav-item">
                        <a class="nav-link text-active-primary py-3" href="#" data-status="completed">Completed</a>
                      </li>
                      <li class="nav-item">
                        <a class="nav-link text-active-primary py-3" href="#" data-status="failed_cancelled">Failed / Cancelled</a>
                      </li>
                    </ul>
                  </div>

                  <!-- Read-only cron status. The switches that control it are
                       backend configuration and deliberately not surfaced here. -->
                  <div class="px-9 pt-6">
                    <div class="d-flex flex-wrap align-items-center gap-7 p-5 rounded bg-light-primary bg-opacity-50">
                      <?php
                        $cronLive = !empty($settings['enabled']) && empty($settings['dry_run']);
                        if (empty($settings['enabled']))      { $modeTone = 'secondary'; $modeText = 'DISABLED'; }
                        elseif (!empty($settings['dry_run'])) { $modeTone = 'warning';   $modeText = 'DRY-RUN'; }
                        else                                  { $modeTone = 'success';   $modeText = 'LIVE'; }
                      ?>
                      <div>
                        <span class="badge badge-<?php echo $modeTone; ?> fs-8"><?php echo $modeText; ?></span>
                        <div class="fs-9 text-muted text-uppercase mt-1">BMAN send cron</div>
                      </div>
                      <div>
                        <div class="fs-3 fw-bold text-gray-800"><?php echo (int)$bman_pending; ?></div>
                        <div class="fs-9 text-muted text-uppercase">Pending in queue</div>
                      </div>
                      <div>
                        <div class="fs-3 fw-bold text-gray-800"><?php echo (int)($cron_state['total_settled'] ?? 0); ?></div>
                        <div class="fs-9 text-muted text-uppercase">Sent all-time</div>
                      </div>
                      <div>
                        <div class="fs-6 fw-bold text-gray-800"><?php echo html_escape($cron_state['last_run_at'] ?: 'never'); ?></div>
                        <div class="fs-9 text-muted text-uppercase">Last cron run</div>
                      </div>
                      <?php if (!empty($cron_state['last_result'])): ?>
                      <div class="flex-grow-1 min-w-200px">
                        <div class="fs-8 text-gray-700"><?php echo html_escape($cron_state['last_result']); ?></div>
                        <div class="fs-9 text-muted text-uppercase">Last result</div>
                      </div>
                      <?php endif; ?>
                    </div>
                  </div>

                  <div class="card-body pt-5 pb-9">
                    <div class="table-responsive">
                      <table class="table align-middle table-row-dashed fs-7 gy-4">
                        <thead><tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                          <th>Reference</th><th>File</th><th>By</th><th class="text-end">Rows</th>
                          <th class="text-end">Imported</th><th class="text-end">BMAN</th>
                          <th>Delivery &amp; transactions</th><th>Status</th><th>Uploaded</th><th></th>
                        </tr></thead>
                        <tbody id="bmu-history-body" class="text-gray-700 fw-semibold">
                          <tr><td colspan="10" class="text-muted text-center py-5">Loading history...</td></tr>
                        </tbody>
                      </table>
                    </div>
                    <div class="d-flex flex-stack flex-wrap pt-6 border-top">
                      <div class="fs-7 fw-semibold text-gray-700" id="bmu-pagination-info">Showing 0 to 0 of 0 entries</div>
                      <ul class="pagination pagination-outline" id="bmu-pagination"></ul>
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
  <script>
  (function () {
    const base = '<?php echo base_url(); ?>';
    let stagedBatchId = null;

    const el  = (id) => document.getElementById(id);
    const esc = (s)  => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    function toast(msg, ok) {
      if (window.Swal) Swal.fire({ text: msg, icon: ok ? 'success' : 'error', buttonsStyling: false,
        confirmButtonText: 'Ok', customClass: { confirmButton: 'btn btn-primary' } });
      else alert(msg);
    }
    function busy(btn, on) {
      if (!btn) return;
      btn.disabled = on;
      btn.setAttribute('data-kt-indicator', on ? 'on' : 'off');
    }
    async function post(url, body) {
      const r = await fetch(base + url, { method: 'POST', body, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      let j = {}; try { j = await r.json(); } catch (_) {}
      return { ok: r.ok && j.status === 'success', j };
    }

    /* ---- persistent result banner (shown above the upload card) ---- */
    function showBanner(msg, ok, batchId) {
      let banner = el('bmu-result-banner');
      if (!banner) {
        banner = document.createElement('div');
        banner.id = 'bmu-result-banner';
        const uploadCard = el('bmu-form').closest('.card');
        uploadCard.parentNode.insertBefore(banner, uploadCard);
      }
      const tone   = ok ? 'success' : 'danger';
      const icon   = ok ? 'ki-check-circle' : 'ki-cross-circle';
      const viewBtn = ok && batchId
        ? `<a href="${base}admin/member/bulk-upload/batch/${batchId}" class="btn btn-sm btn-${tone} ms-3">View Batch</a>`
        : '';
      banner.innerHTML = `
        <div class="alert alert-${tone} d-flex align-items-center justify-content-between p-4 mb-5">
          <div class="d-flex align-items-center gap-3">
            <i class="ki-outline ${icon} fs-2hx text-${tone}"></i>
            <span class="fw-semibold fs-6">${esc(msg)}</span>
          </div>
          <div class="d-flex align-items-center gap-2">
            ${viewBtn}
            <button type="button" class="btn btn-sm btn-light"
                    onclick="this.closest('.alert').parentElement.remove()">Dismiss</button>
          </div>
        </div>`;
      banner.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    /* ---- reset upload form to its initial empty state ---- */
    function resetUploadForm() {
      el('bmu-form').reset();
      fileName.textContent = 'Drop an .xlsx or .csv here, or click to choose';
      el('bmu-preview-card').classList.add('d-none');
      el('bmu-preview-body').innerHTML = '';
      stagedBatchId = null;
    }

    /* ---------------- file picker + drag & drop ---------------- */
    const drop = el('bmu-drop'), fileInput = el('bmu-file'), fileName = el('bmu-filename');

    drop.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', () => {
      fileName.textContent = fileInput.files.length ? fileInput.files[0].name : 'Drop an .xlsx or .csv here, or click to choose';
    });
    ['dragenter', 'dragover'].forEach(ev => drop.addEventListener(ev, e => {
      e.preventDefault(); drop.classList.add('is-over');
    }));
    ['dragleave', 'drop'].forEach(ev => drop.addEventListener(ev, e => {
      e.preventDefault(); drop.classList.remove('is-over');
    }));
    drop.addEventListener('drop', e => {
      if (!e.dataTransfer.files.length) return;
      fileInput.files = e.dataTransfer.files;
      fileName.textContent = e.dataTransfer.files[0].name;
    });

    /* ---------------- validate (stage) ---------------- */
    el('bmu-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      if (!fileInput.files.length) { toast('Choose a file first.', false); return; }

      // Clear any previous result banner when starting a fresh upload
      const old = el('bmu-result-banner');
      if (old) old.remove();

      const btn = el('bmu-validate');
      busy(btn, true);
      const fd = new FormData(e.target);
      fd.set('sheet', fileInput.files[0]);
      fd.set('send_bman', el('bmu-sendbman').checked ? '1' : '');
      // wallet_type is already included by the <select name="wallet_type">
      const { ok, j } = await post('admin/member/bulk-upload/stage', fd);
      busy(btn, false);

      if (!ok) { toast(j.message || 'Validation failed.', false); return; }

      stagedBatchId = j.batch_id;
      renderPreview(j.rows || [], j.summary || {});
      el('bmu-preview-card').classList.remove('d-none');
      el('bmu-preview-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    /* Wallet badge colours for the preview table */
    const walletBadgeClass = { exchange: 'info', earning: 'success', staking: 'warning', bonus: 'primary' };

    function renderPreview(rows, summary) {
      el('bmu-count-valid').textContent   = (summary.valid        || 0) + ' valid';
      el('bmu-count-invalid').textContent = (summary.invalid      || 0) + ' invalid';
      el('bmu-count-bman').textContent    = (summary.bman_queued  || 0) + ' BMAN queued';
      el('bmu-import').disabled = !(summary.valid > 0);

      el('bmu-preview-body').innerHTML = rows.map(r => {
        const bad    = r.status === 'invalid';
        const wtype  = (r.wallet_type || 'exchange').toLowerCase();
        const wcolor = walletBadgeClass[wtype] || 'secondary';
        const wlabel = wtype.charAt(0).toUpperCase() + wtype.slice(1);
        return `<tr class="${bad ? 'bg-light-danger' : ''}">
          <td class="text-muted">${esc(r.row_number)}</td>
          <td>${esc(r.username)}</td>
          <td class="fs-8">${esc(r.email)}</td>
          <td class="bmu-mono">${esc(r.reference_id)}</td>
          <td class="text-end">${Number(r.bman_amount) ? Number(r.bman_amount).toLocaleString(undefined, { maximumFractionDigits: 8 }) : '—'}</td>
          <td><span class="badge badge-light-${wcolor} fs-9">${wlabel}</span></td>
          <td><span class="badge badge-light-${bad ? 'danger' : 'success'}">${bad ? 'INVALID' : 'READY'}</span></td>
          <td class="fs-8 text-danger">${esc(r.error_message || '')}</td>
        </tr>`;
      }).join('');
    }

    /* ---------------- AJAX History Loading & Pagination ---------------- */
    let currentStatus = '';
    let currentPage   = 1;
    const historyLimit = 10;

    function formatBman(val) {
      if (!val || parseFloat(val) === 0) return '—';
      let s = parseFloat(val).toFixed(8);
      s = s.replace(/\.?0+$/, "");
      return s;
    }

    const statusToneMap = {
      completed: 'success',
      failed: 'danger',
      staged: 'warning',
      importing: 'info',
      cancelled: 'secondary'
    };

    async function loadHistory(page, status) {
      const tbody = el('bmu-history-body');
      if (!tbody) return;
      tbody.innerHTML = `<tr><td colspan="10" class="text-muted text-center py-5">Loading history... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></td></tr>`;

      const r = await fetch(base + `admin/member/bulk-upload/history?page=${page}&limit=${historyLimit}&status=${status}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      let j = {}; try { j = await r.json(); } catch (_) {}

      if (r.ok && j.status === 'success') {
        renderHistoryRows(j.data || []);
        renderPagination(j.pagination.page, j.pagination.pages, j.pagination.total);
      } else {
        tbody.innerHTML = `<tr><td colspan="10" class="text-danger text-center py-5">Failed to load history.</td></tr>`;
      }
    }

    function renderHistoryRows(rows) {
      const tbody = el('bmu-history-body');
      if (!tbody) return;

      if (rows.length === 0) {
        tbody.innerHTML = `<tr><td colspan="10" class="text-muted text-center py-5">No uploads found in this category.</td></tr>`;
        return;
      }

      tbody.innerHTML = rows.map(b => {
        const totalBman = parseFloat(b.bman_total) > 0 ? formatBman(b.bman_total) : '—';
        const sentBman = parseFloat(b.bman_sent_amount) > 0 ? `<div class="text-success fs-8">${formatBman(b.bman_sent_amount)} sent</div>` : '';
        const tone = statusToneMap[b.status] || 'secondary';
        const statusLabel = b.status.toUpperCase();

        let deliveryHtml = '';
        if (b.status === 'staged') {
          deliveryHtml = `<span class="badge badge-light-warning fs-9">NOT IMPORTED</span><div class="fs-9 text-muted">nothing queued yet</div>`;
        } else if (parseInt(b.bman_pending) === 0 && parseInt(b.bman_sent) === 0 && parseInt(b.bman_failed) === 0 && parseInt(b.bman_processing) === 0) {
          deliveryHtml = `<span class="text-muted fs-8">No transfers</span>`;
        } else {
          let badges = [];
          if (parseInt(b.bman_sent) > 0) badges.push(`<span class="badge badge-light-success fs-9">${b.bman_sent} sent</span>`);
          if (parseInt(b.bman_credited) > 0) badges.push(`<span class="badge badge-light-primary fs-9">${b.bman_credited} credited</span>`);
          if (parseInt(b.bman_pending) > 0) badges.push(`<span class="badge badge-light-warning fs-9">${b.bman_pending} queued</span>`);
          if (parseInt(b.bman_processing) > 0) badges.push(`<span class="badge badge-light-info fs-9">${b.bman_processing} sending</span>`);
          if (parseInt(b.bman_failed) > 0) badges.push(`<span class="badge badge-light-danger fs-9">${b.bman_failed} failed</span>`);

          const txHashHtml = b.last_tx_hash ? `<div class="bmu-mono fs-9 text-muted" title="${esc(b.last_tx_hash)}">${esc(b.last_tx_hash.substring(0, 20))}…</div>` : '';
          const lastSentHtml = b.last_sent_at ? `<div class="fs-9 text-muted">last ${esc(b.last_sent_at)}</div>` : '';
          deliveryHtml = `<div class="d-flex flex-wrap gap-1 mb-1">${badges.join('')}</div>${txHashHtml}${lastSentHtml}`;
        }

        let actionButtons = `<a href="${base}admin/member/bulk-upload/batch/${b.id}" class="btn btn-sm btn-light-primary py-1 px-3 fs-8">View</a>`;
        if (b.status === 'staged') {
          actionButtons += `
            <button type="button" class="btn btn-sm btn-light-danger py-1 px-3 fs-8 bmu-cancel-btn"
                    data-batch-id="${b.id}" data-ref="${esc(b.ref)}">Cancel</button>`;
        }

        const adminLabel = b.admin_name ? esc(b.admin_name) : `#${b.admin_id}`;
        const invalidRowsIndicator = parseInt(b.invalid_rows) > 0 ? `<div class="text-danger fs-8">${b.invalid_rows} invalid</div>` : '';
        const failedRowsIndicator = parseInt(b.failed_rows) > 0 ? `<div class="text-danger fs-8">${b.failed_rows} failed</div>` : '';

        return `
          <tr>
            <td><span class="bmu-mono fw-bold text-primary">${esc(b.ref)}</span></td>
            <td class="text-truncate mw-150px" title="${esc(b.original_name)}">${esc(b.original_name)}</td>
            <td class="fs-8 text-muted">${adminLabel}</td>
            <td class="text-end">${parseInt(b.total_rows)}${invalidRowsIndicator}</td>
            <td class="text-end fw-bold">${parseInt(b.imported_rows)}${failedRowsIndicator}</td>
            <td class="text-end">${totalBman}${sentBman}</td>
            <td>${deliveryHtml}</td>
            <td><span class="badge badge-light-${tone}">${statusLabel}</span></td>
            <td class="text-muted fs-8">${esc(b.created_at)}</td>
            <td class="text-end d-flex gap-1 justify-content-end">${actionButtons}</td>
          </tr>`;
      }).join('');

      tbody.querySelectorAll('.bmu-cancel-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
          const batchId = btn.dataset.batchId;
          const ref     = btn.dataset.ref;
          const go = window.Swal
            ? (await Swal.fire({
                title: 'Cancel ' + ref + '?',
                text: 'This staged batch will be cancelled and can no longer be imported.',
                icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, cancel it',
                buttonsStyling: false, customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-light' }
              })).isConfirmed
            : confirm('Cancel batch ' + ref + '?');
          if (!go) return;
          const fd = new FormData(); fd.set('batch_id', batchId);
          const { ok, j } = await post('admin/member/bulk-upload/cancel', fd);
          if (ok) {
            toast('Batch ' + ref + ' cancelled.', true);
            loadHistory(currentPage, currentStatus);
          } else {
            toast(j.message || 'Cancel failed.', false);
          }
        });
      });
    }

    function renderPagination(page, pages, total) {
      const wrapper = el('bmu-pagination');
      const info = el('bmu-pagination-info');
      if (!wrapper) return;

      const start = total === 0 ? 0 : (page - 1) * historyLimit + 1;
      const end = Math.min(page * historyLimit, total);
      info.textContent = `Showing ${start} to ${end} of ${total} entries`;

      if (pages <= 1) {
        wrapper.innerHTML = '';
        return;
      }

      let html = '';
      html += `
        <li class="page-item previous ${page === 1 ? 'disabled' : ''}">
          <a href="#" class="page-link" data-page="${page - 1}"><i class="ki-outline ki-left fs-6"></i></a>
        </li>`;

      for (let i = 1; i <= pages; i++) {
        html += `
          <li class="page-item ${i === page ? 'active' : ''}">
            <a href="#" class="page-link" data-page="${i}">${i}</a>
          </li>`;
      }

      html += `
        <li class="page-item next ${page === pages ? 'disabled' : ''}">
          <a href="#" class="page-link" data-page="${page + 1}"><i class="ki-outline ki-right fs-6"></i></a>
        </li>`;

      wrapper.innerHTML = html;

      wrapper.querySelectorAll('.page-link').forEach(link => {
        link.addEventListener('click', (e) => {
          e.preventDefault();
          const p = parseInt(link.dataset.page);
          if (p && p >= 1 && p <= pages && p !== page) {
            currentPage = p;
            loadHistory(p, currentStatus);
          }
        });
      });
    }

    // Set up tabs click handlers
    const tabContainer = el('bmu-history-tabs');
    if (tabContainer) {
      tabContainer.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', (e) => {
          e.preventDefault();
          tabContainer.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
          link.classList.add('active');
          currentStatus = link.dataset.status;
          currentPage = 1;
          loadHistory(1, currentStatus);
        });
      });
    }

    // Load initial history table rows
    loadHistory(1, '');

    /* ---------------- import ---------------- */
    el('bmu-import').addEventListener('click', async () => {
      if (!stagedBatchId) return;
      const validText = el('bmu-count-valid').textContent;
      const batchId   = stagedBatchId;

      const go = window.Swal
        ? (await Swal.fire({
            title: 'Create these members?',
            text: validText + ' member account(s) will be created, each with a generated wallet address. This cannot be undone in bulk.',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, import',
            buttonsStyling: false, customClass: { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-light' }
          })).isConfirmed
        : confirm('Create ' + validText + ' member account(s)?');
      if (!go) return;

      const btn = el('bmu-import');
      busy(btn, true);
      const fd = new FormData();
      fd.set('batch_id', batchId);
      const { ok, j } = await post('admin/member/bulk-upload/import', fd);
      busy(btn, false);

      resetUploadForm();
      showBanner(j.message || (ok ? 'Import complete.' : 'Import failed.'), ok, ok ? batchId : null);

      // Reload list dynamically instead of hard page refresh
      if (ok) setTimeout(() => loadHistory(currentPage, currentStatus), 1200);
    });

    /* ---------------- discard (cancel staged batch from preview) ---------------- */
    el('bmu-discard').addEventListener('click', async () => {
      if (!stagedBatchId) return;
      const fd = new FormData();
      fd.set('batch_id', stagedBatchId);
      const { ok, j } = await post('admin/member/bulk-upload/cancel', fd);
      if (ok) {
        resetUploadForm();
        loadHistory(currentPage, currentStatus);
      }
      else toast(j.message || 'Failed to discard.', false);
    });


    /* ---------------- cron settings form ---------------- */
    const settingsForm = el('bmu-settings-form');
    if (settingsForm) {
      settingsForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = el('bmu-save-settings');
        busy(btn, true);
        const fd = new FormData(settingsForm);
        // Checkboxes: explicitly send empty string for unchecked boxes
        ['enabled', 'dry_run', 'credit_exchange_wallet'].forEach(name => {
          if (!settingsForm.querySelector('[name="' + name + '"]:checked')) fd.set(name, '');
        });
        const { ok, j } = await post('admin/member/bulk-upload/settings', fd);
        busy(btn, false);
        toast(j.message || (ok ? 'Settings saved.' : 'Failed to save.'), ok);
        if (ok) setTimeout(() => location.reload(), 900);
      });
    }

    /* ---------------- cron settings chevron ---------------- */
    const settingsBody = document.getElementById('bmu-settings-body');
    const chevron      = document.getElementById('bmu-settings-chevron');
    if (settingsBody && chevron) {
      settingsBody.addEventListener('show.bs.collapse', () => chevron.style.transform = 'rotate(180deg)');
      settingsBody.addEventListener('hide.bs.collapse', () => chevron.style.transform = 'rotate(0deg)');
    }

  })();
  </script>
</body>
</html>
