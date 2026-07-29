<?php $this->load->view('admin/Layout/common_style'); ?>
<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
<style>
  .bmu-drop { border: 2px dashed var(--bs-gray-300); border-radius: .75rem; padding: 2.25rem 1rem; text-align: center; cursor: pointer; transition: border-color .15s, background-color .15s; }
  .bmu-drop:hover, .bmu-drop.is-over { border-color: var(--bs-primary); background: var(--bs-light-primary); }
  .bmu-preview-wrap { max-height: 460px; overflow: auto; }
  .bmu-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .8rem; }
  @media (max-width: 767.98px) { .bmu-drop { padding: 1.5rem .75rem; } }
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
                      <b>reference_id</b> is the sponsor's referral code and decides where the member lands in the binary tree.
                      An on-chain wallet address is generated for every member automatically — it is never read from the sheet.
                      <b>bman</b> is queued for the on-chain cron, <u>not</u> sent during import.
                    </span>
                  </div>
                </div>

                <div class="row g-5">
                  <!-- ===================== Upload ===================== -->
                  <div class="col-xl-7">
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
                            <div class="col-md-6">
                              <label class="form-label fw-semibold fs-7">Default password <span class="text-muted fw-normal">(used when a row's password cell is blank)</span></label>
                              <input type="text" name="default_password" class="form-control form-control-solid" autocomplete="off" placeholder="e.g. Welcome@2026" />
                            </div>
                            <div class="col-md-3">
                              <label class="form-label fw-semibold fs-7">Default leg</label>
                              <select name="default_leg" class="form-select form-select-solid">
                                <option value="auto">Auto</option>
                                <option value="left">Left</option>
                                <option value="right">Right</option>
                              </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
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

                  <!-- ===================== Cron / settings ===================== -->
                  <div class="col-xl-5">
                    <div class="card mb-5">
                      <div class="card-header border-transparent pt-5">
                        <h3 class="card-title fw-bold">BMAN Send Cron <span class="text-muted fs-8 fw-normal ms-2">disabled + dry-run by default</span></h3>
                      </div>
                      <div class="card-body pt-2 pb-8">
                        <div class="d-flex flex-wrap gap-6 mb-6">
                          <div>
                            <div class="fs-2 fw-bold text-gray-800"><?php echo (int)$bman_pending; ?></div>
                            <div class="fs-8 text-muted text-uppercase">Pending in queue</div>
                          </div>
                          <div>
                            <div class="fs-2 fw-bold text-gray-800"><?php echo (int)($cron_state['total_settled'] ?? 0); ?></div>
                            <div class="fs-8 text-muted text-uppercase">Sent all-time</div>
                          </div>
                          <div>
                            <div class="fs-6 fw-bold text-gray-800 pt-2"><?php echo html_escape($cron_state['last_run_at'] ?? 'never'); ?></div>
                            <div class="fs-8 text-muted text-uppercase">Last run</div>
                          </div>
                        </div>

                        <form id="bmu-settings-form" class="d-flex flex-wrap align-items-end gap-4">
                          <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="bmu-enabled" name="enabled" <?php echo !empty($settings['enabled']) ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-semibold" for="bmu-enabled">Enabled</label>
                          </div>
                          <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="bmu-dryrun" name="dry_run" <?php echo !empty($settings['dry_run']) ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-semibold" for="bmu-dryrun">Dry-run</label>
                          </div>
                          <div>
                            <label class="form-label fw-semibold fs-8 mb-1">Min treasury reserve</label>
                            <input type="number" step="0.00000001" min="0" name="min_treasury_reserve" class="form-control form-control-solid form-control-sm w-150px" value="<?php echo html_escape($settings['min_treasury_reserve']); ?>" />
                          </div>
                          <div>
                            <label class="form-label fw-semibold fs-8 mb-1">Rows / cron pass</label>
                            <input type="number" min="1" max="500" name="max_batch_size" class="form-control form-control-solid form-control-sm w-110px" value="<?php echo (int)$settings['max_batch_size']; ?>" />
                          </div>
                          <div>
                            <label class="form-label fw-semibold fs-8 mb-1">Max rows / file</label>
                            <input type="number" min="1" max="20000" name="max_rows_per_file" class="form-control form-control-solid form-control-sm w-110px" value="<?php echo (int)$settings['max_rows_per_file']; ?>" />
                          </div>
                          <button type="submit" class="btn btn-light-primary btn-sm">Save</button>
                        </form>

                        <div class="separator my-5"></div>
                        <div class="fs-8 text-muted">
                          Schedule <span class="bmu-mono">/member-bulk-bman-cron?token=…</span> every few minutes, or run it from
                          <a href="<?php echo base_url(); ?>admin/wallet/cron-lab">Cron Lab</a>.
                          Member accounts are created immediately at import — these switches only gate the on-chain money movement.
                        </div>
                      </div>
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
                          <th>#</th><th>Username</th><th>Email</th><th>Reference ID</th><th>Leg</th>
                          <th class="text-end">BMAN</th><th>Status</th><th>Message</th>
                        </tr></thead>
                        <tbody id="bmu-preview-body" class="text-gray-700 fw-semibold"></tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <!-- ===================== History ===================== -->
                <div class="card mb-5">
                  <div class="card-header border-transparent pt-5"><h3 class="card-title fw-bold">Upload History</h3></div>
                  <div class="card-body pt-3 pb-9">
                    <div class="table-responsive">
                      <table class="table align-middle table-row-dashed fs-7 gy-4">
                        <thead><tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                          <th>Reference</th><th>File</th><th>By</th><th class="text-end">Rows</th>
                          <th class="text-end">Imported</th><th class="text-end">BMAN queued</th><th>Status</th><th>Uploaded</th><th></th>
                        </tr></thead>
                        <tbody class="text-gray-700 fw-semibold">
                          <?php foreach ($batches as $b): ?>
                          <tr>
                            <td><span class="bmu-mono fw-bold text-primary"><?php echo html_escape($b['ref']); ?></span></td>
                            <td class="text-truncate mw-200px" title="<?php echo html_escape($b['original_name']); ?>"><?php echo html_escape($b['original_name']); ?></td>
                            <td class="fs-8 text-muted"><?php echo html_escape($b['admin_name'] ?: ('#'.$b['admin_id'])); ?></td>
                            <td class="text-end"><?php echo (int)$b['total_rows']; ?>
                              <?php if ((int)$b['invalid_rows'] > 0): ?><span class="text-danger fs-8">(<?php echo (int)$b['invalid_rows']; ?> bad)</span><?php endif; ?>
                            </td>
                            <td class="text-end fw-bold"><?php echo (int)$b['imported_rows']; ?>
                              <?php if ((int)$b['failed_rows'] > 0): ?><span class="text-danger fs-8">/<?php echo (int)$b['failed_rows']; ?> failed</span><?php endif; ?>
                            </td>
                            <td class="text-end"><?php echo (int)$b['bman_queued']; ?>
                              <?php if (bccomp((string)$b['bman_total'], '0', 8) > 0): ?><div class="text-muted fs-8"><?php echo rtrim(rtrim(number_format((float)$b['bman_total'], 8, '.', ''), '0'), '.'); ?> BMAN</div><?php endif; ?>
                            </td>
                            <td>
                              <?php
                                $map = ['completed' => 'success', 'failed' => 'danger', 'staged' => 'warning', 'importing' => 'info', 'cancelled' => 'secondary'];
                                $tone = $map[$b['status']] ?? 'secondary';
                              ?>
                              <span class="badge badge-light-<?php echo $tone; ?>"><?php echo strtoupper($b['status']); ?></span>
                            </td>
                            <td class="text-muted fs-8"><?php echo html_escape($b['created_at']); ?></td>
                            <td class="text-end">
                              <a href="<?php echo base_url(); ?>admin/member/bulk-upload/batch/<?php echo (int)$b['id']; ?>" class="btn btn-sm btn-light-primary py-1 px-3 fs-8">View</a>
                            </td>
                          </tr>
                          <?php endforeach; ?>
                          <?php if (empty($batches)): ?><tr><td colspan="9" class="text-muted">No uploads yet.</td></tr><?php endif; ?>
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
  (function () {
    const base = '<?php echo base_url(); ?>';
    let stagedBatchId = null;

    const el = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

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

      const btn = el('bmu-validate');
      busy(btn, true);
      const fd = new FormData(e.target);
      fd.set('sheet', fileInput.files[0]);
      fd.set('send_bman', el('bmu-sendbman').checked ? '1' : '');
      const { ok, j } = await post('admin/member/bulk-upload/stage', fd);
      busy(btn, false);

      if (!ok) { toast(j.message || 'Validation failed.', false); return; }

      stagedBatchId = j.batch_id;
      renderPreview(j.rows || [], j.summary || {});
      el('bmu-preview-card').classList.remove('d-none');
      el('bmu-preview-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    function renderPreview(rows, summary) {
      el('bmu-count-valid').textContent   = (summary.valid   || 0) + ' valid';
      el('bmu-count-invalid').textContent = (summary.invalid || 0) + ' invalid';
      el('bmu-count-bman').textContent    = (summary.bman_queued || 0) + ' BMAN queued';
      el('bmu-import').disabled = !(summary.valid > 0);

      el('bmu-preview-body').innerHTML = rows.map(r => {
        const bad = r.status === 'invalid';
        return `<tr class="${bad ? 'bg-light-danger' : ''}">
          <td class="text-muted">${esc(r.row_number)}</td>
          <td>${esc(r.username)}</td>
          <td class="fs-8">${esc(r.email)}</td>
          <td class="bmu-mono">${esc(r.reference_id)}</td>
          <td><span class="badge badge-light fs-9">${esc(r.leg)}</span></td>
          <td class="text-end">${Number(r.bman_amount) ? Number(r.bman_amount).toLocaleString(undefined, { maximumFractionDigits: 8 }) : '—'}</td>
          <td><span class="badge badge-light-${bad ? 'danger' : 'success'}">${bad ? 'INVALID' : 'READY'}</span></td>
          <td class="fs-8 text-danger">${esc(r.error_message || '')}</td>
        </tr>`;
      }).join('');
    }

    /* ---------------- import ---------------- */
    el('bmu-import').addEventListener('click', async () => {
      if (!stagedBatchId) return;
      const valid = el('bmu-count-valid').textContent;

      const go = window.Swal
        ? (await Swal.fire({
            title: 'Create these members?',
            text: valid + ' member account(s) will be created, each with a generated wallet address. This cannot be undone in bulk.',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, import',
            buttonsStyling: false, customClass: { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-light' }
          })).isConfirmed
        : confirm('Create ' + valid + ' member account(s)?');
      if (!go) return;

      const btn = el('bmu-import');
      busy(btn, true);
      const fd = new FormData(); fd.set('batch_id', stagedBatchId);
      const { ok, j } = await post('admin/member/bulk-upload/import', fd);
      busy(btn, false);

      toast(j.message || (ok ? 'Imported.' : 'Import failed.'), ok);
      if (ok) setTimeout(() => location.href = base + 'admin/member/bulk-upload/batch/' + stagedBatchId, 1200);
    });

    /* ---------------- discard ---------------- */
    el('bmu-discard').addEventListener('click', async () => {
      if (!stagedBatchId) return;
      const fd = new FormData(); fd.set('batch_id', stagedBatchId);
      const { ok, j } = await post('admin/member/bulk-upload/cancel', fd);
      toast(j.message || (ok ? 'Discarded.' : 'Failed.'), ok);
      if (ok) { stagedBatchId = null; el('bmu-preview-card').classList.add('d-none'); }
    });

    /* ---------------- settings ---------------- */
    el('bmu-settings-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      fd.set('enabled', el('bmu-enabled').checked ? '1' : '');
      fd.set('dry_run', el('bmu-dryrun').checked ? '1' : '');
      const { ok, j } = await post('admin/member/bulk-upload/settings', fd);
      toast(j.message || (ok ? 'Saved.' : 'Failed.'), ok);
    });
  })();
  </script>
</body>
</html>
