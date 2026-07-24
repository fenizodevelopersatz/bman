<?php $this->load->view('admin/Layout/common_style'); ?>
<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
<style>
  .sr-pkg-card{ border:1px solid #e6e8ef; border-radius:14px; margin-bottom:22px; overflow:hidden; }
  .sr-pkg-head{ padding:14px 18px; background:linear-gradient(135deg,#f59e0b12,#ef444412);
    display:flex; align-items:center; gap:12px; border-bottom:1px solid #eef0f4; }
  .sr-badge{ background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff; font-weight:900; font-size:11px;
    letter-spacing:.5px; padding:3px 10px; border-radius:999px; }
  .sr-table input.sr-cell{ width:96px; text-align:right; font-variant-numeric:tabular-nums; }
  .sr-table input.sr-cell.sr-dirty{ background:#fff8dd; border-color:#f6c000; }
  .sr-table td, .sr-table th{ vertical-align:middle; }
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
                    <li class="breadcrumb-item text-muted"><a href="<?php echo base_url(); ?>" class="text-muted text-hover-primary">Staking</a></li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted"><?php echo $title; ?></li>
                  </ul>
                </div>
                <a href="<?php echo base_url('admin/staking/packages'); ?>" class="btn btn-sm btn-light-primary">Manage Packages</a>
              </div>
            </div>

            <div id="kt_app_content" class="app-content flex-column-fluid mt-8">
              <div id="kt_app_content_container" class="app-container container-xxl">
                <?php $this->load->view('notification'); ?>

                <div class="card mb-5">
                  <div class="card-body">
                    <div class="text-muted fs-7 mb-5">
                      Set, per year, the <b>Monthly ROI %</b> and the <b>Maturity %</b> for each Special Offer package.
                      A member who stakes for N years earns that year's Monthly ROI each month, plus the year-N Maturity % as a lump at maturity.
                      Untick <b>Offered</b> to hide a year. <?php if (!$can_edit): ?><span class="text-danger fw-bold">Read-only — editing is Super-Admin only.</span><?php endif; ?>
                    </div>

                    <?php if (empty($grid)): ?>
                      <div class="text-center text-muted py-10">
                        No Special Offer packages yet. Mark a package as <b>Special Offer</b> in
                        <a href="<?php echo base_url('admin/staking/packages'); ?>">Manage Packages</a> first.
                      </div>
                    <?php else: foreach ($grid as $g): $p = $g['package']; ?>
                      <div class="sr-pkg-card" data-package="<?php echo (int) $p['id']; ?>">
                        <div class="sr-pkg-head">
                          <span class="sr-badge">SPECIAL OFFER</span>
                          <span class="fw-bold fs-5 text-gray-900"><?php echo html_escape($p['name']); ?></span>
                          <span class="text-muted fs-8"><?php echo number_format((float) $p['stake_amount']); ?> BMAN &middot; <?php echo rtrim(rtrim(number_format((float) $p['bonus_percent'], 2), '0'), '.'); ?>% instant bonus</span>
                        </div>
                        <div class="table-responsive p-4">
                          <table class="table table-row-dashed align-middle fs-7 sr-table mb-0">
                            <thead>
                              <tr class="fw-bold text-muted text-uppercase fs-8">
                                <th>Year</th><th class="text-end">Monthly ROI %</th><th class="text-end">Maturity %</th>
                                <th class="text-center">Offered</th><th class="text-end"></th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php for ($y = 1; $y <= $max_years; $y++): $row = $g['years'][$y]; ?>
                                <tr data-year="<?php echo $y; ?>">
                                  <td class="fw-bold text-gray-800"><?php echo $y; ?> Year<?php echo $y > 1 ? 's' : ''; ?></td>
                                  <td class="text-end"><input type="number" step="0.001" min="0" class="form-control form-control-sm sr-cell sr-monthly" value="<?php echo rtrim(rtrim(number_format($row['monthly_roi_percent'], 3), '0'), '.'); ?>" <?php echo $can_edit ? '' : 'disabled'; ?>></td>
                                  <td class="text-end"><input type="number" step="0.001" min="0" class="form-control form-control-sm sr-cell sr-maturity" value="<?php echo rtrim(rtrim(number_format($row['maturity_percent'], 3), '0'), '.'); ?>" <?php echo $can_edit ? '' : 'disabled'; ?>></td>
                                  <td class="text-center">
                                    <div class="form-check form-switch form-check-custom form-check-solid d-inline-block">
                                      <input class="form-check-input sr-active" type="checkbox" <?php echo $row['is_active'] ? 'checked' : ''; ?> <?php echo $can_edit ? '' : 'disabled'; ?>>
                                    </div>
                                  </td>
                                  <td class="text-end">
                                    <?php if ($can_edit): ?><button type="button" class="btn btn-sm btn-light-primary sr-save">Save</button><?php endif; ?>
                                  </td>
                                </tr>
                              <?php endfor; ?>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    <?php endforeach; endif; ?>
                  </div>
                </div>

                <!-- Audit -->
                <div class="card">
                  <div class="card-header"><h3 class="card-title fw-bold">Change Log</h3></div>
                  <div class="card-body pt-3">
                    <div class="table-responsive">
                      <table class="table table-row-dashed fs-8 align-middle">
                        <thead><tr class="fw-bold text-muted text-uppercase"><th>When</th><th>Package</th><th>Year</th><th>Field</th><th>Old</th><th>New</th></tr></thead>
                        <tbody>
                          <?php if (empty($audit)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-6">No changes yet.</td></tr>
                          <?php else: foreach ($audit as $a): ?>
                            <tr>
                              <td><?php echo html_escape($a['created_at']); ?></td>
                              <td><?php echo html_escape($a['package_name'] ?? ('#' . $a['package_id'])); ?></td>
                              <td><?php echo (int) $a['year_number'] ?: '—'; ?></td>
                              <td><?php echo html_escape($a['field']); ?></td>
                              <td class="text-muted"><?php echo html_escape($a['old_value'] ?? '—'); ?></td>
                              <td class="fw-bold"><?php echo html_escape($a['new_value'] ?? '—'); ?></td>
                            </tr>
                          <?php endforeach; endif; ?>
                        </tbody>
                      </table>
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
  </div>

  <?php $this->load->view('admin/Layout/common_script'); ?>
  <script>
  (function () {
    var base = '<?php echo base_url(); ?>';
    function toast(msg, ok) {
      if (window.Swal) Swal.fire({ text: msg, icon: ok ? 'success' : 'error', buttonsStyling: false, confirmButtonText: 'OK', customClass: { confirmButton: 'btn btn-primary' } });
      else alert(msg);
    }
    document.querySelectorAll('.sr-cell').forEach(function (el) {
      el.addEventListener('input', function () { el.classList.add('sr-dirty'); });
    });
    document.querySelectorAll('.sr-save').forEach(function (btn) {
      btn.addEventListener('click', async function () {
        var card = btn.closest('.sr-pkg-card');
        var row = btn.closest('tr');
        var fd = new FormData();
        fd.append('package_id', card.getAttribute('data-package'));
        fd.append('year_number', row.getAttribute('data-year'));
        fd.append('monthly_roi_percent', row.querySelector('.sr-monthly').value || 0);
        fd.append('maturity_percent', row.querySelector('.sr-maturity').value || 0);
        fd.append('is_active', row.querySelector('.sr-active').checked ? 1 : 0);
        btn.disabled = true;
        try {
          var res = await fetch(base + 'admin/staking/special-roi/save', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
          var j = await res.json();
          btn.disabled = false;
          if (res.ok && j.status === 'success') {
            row.querySelectorAll('.sr-cell').forEach(function (c) { c.classList.remove('sr-dirty'); });
            toast(j.message || 'Saved.', true);
          } else { toast(j.message || 'Save failed.', false); }
        } catch (e) { btn.disabled = false; toast('Network error.', false); }
      });
    });
  })();
  </script>
</body>
</html>
