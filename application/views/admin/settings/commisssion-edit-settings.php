<?php $this->load->view('admin/Layout/common_style');?>

    <link href="<?php echo base_url();?>/assets/admin/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css">
    <link href="<?php echo base_url();?>/assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.css" rel="stylesheet" type="text/css">
    <link href="<?php echo base_url();?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />

    <style>
        .h-md-40{
            min-height:42%;
        }
        .verify-symbol {
        color: green;
        font-weight: bold;
        margin-left: 5px;
        }
        .verified {
        border-color: green;
        }
    </style>

    <body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true" data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
    data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">

        <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
            <div class="app-page  flex-column flex-column-fluid " id="kt_app_page">

                <!--  Header   -->
                <?php 
                //************************** SIDE BAR ADMIN PANEL */
                $this->load->view('admin/Layout/admin_topbar');
                //************************** SIDE BAR ADMIN PANEL */
                ?>


                    <!--begin::Wrapper-->
                    <div class="app-wrapper  flex-column flex-row-fluid " id="kt_app_wrapper">

                        <?php $this->load->view('admin/Layout/admin_sidebar');?>

                            <!--begin::Main-->
                            <div class="app-main flex-column flex-row-fluid " id="kt_app_main">
                                <div class="d-flex flex-column flex-column-fluid">

                                    <!--begin::Toolbar-->
                                    <div id="kt_app_toolbar" class="app-toolbar  py-3 py-lg-6 ">
                                        <div id="kt_app_toolbar_container" class="app-container  container-xxl d-flex flex-stack ">
                                            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3 ">

                                                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                                                <?php echo $title; ?>
                                                </h1>

                                                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                                    <li class="breadcrumb-item text-muted">
                                                        <a href="<?php echo base_url();?>" class="text-muted text-hover-primary">
                                                        Settings                         
                                                        </a>
                                                    </li>
                                                    <li class="breadcrumb-item">
                                                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                                    </li>
                                                    <li class="breadcrumb-item text-muted">
                                                    <?php echo $title; ?> </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <!--end::Toolbar-->
                        <div id="kt_app_content" class="app-content  flex-column-fluid mt-10">

                        <div id="kt_app_content_container" class="app-container  container-xxl ">



                    <div class="card mb-5 mb-xl-10 ">
                    <div class="card-header border-0 cursor-pointer p-3" 
                    role="button" data-bs-toggle="collapse" 
                    data-bs-target="#kt_account_addagent_form_details" 
                    aria-expanded="true" aria-controls="kt_account_addagent_form_details">
                    <div class="card-title m-0">

                    <div class="me-3 d-flex justify-content-between text-center align-items-center gap-4">
                    <div class="d-flex flex-center w-60px h-60px rounded-3 bg-light-danger bg-opacity-90">
                    <i class="ki-duotone ki-abstract-26 text-danger fs-3x"><span class="path1"></span><span class="path2"></span></i>               
                    </div>
                    <h3 class="fw-bold m-0"><?php echo $card_title; ?></h3>
                    </div>

                    </div>
                    </div>


                               <div id="kt_account_settings_profile_details" class="collapse show">

                               <?php $level = explode(',', $commissioninfo->level_commission ?? ''); ?>
                                <?php $match = explode(',', $commissioninfo->matching_percents ?? ''); ?>

                                <form id="kt_account_meta_details_form"
      class="form w-100 fv-plugins-bootstrap5 fv-plugins-framework"
      method="post" novalidate="novalidate"
      data-kt-redirect-url="<?php echo base_url(); ?>commission-settings"
      action="<?php echo base_url();?>update-commission-settings">

  <div class="card-body border-top p-9">

    <!-- Direct -->
    <div class="row mb-6 align-items-center">
      <label class="col-lg-8 col-form-label fw-semibold fs-6">Direct Commission Status</label>
      <div class="col-lg-4 fv-row">
        <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
          <input class="form-check-input h-30px w-50px" name="direct_commission_status" type="checkbox" value="1"
                 <?php echo !empty($commissioninfo->direct_commission_status) ? 'checked' : ''; ?> />
        </div>
      </div>
    </div>

    <div class="row mb-6">
      <label class="col-lg-4 col-form-label fw-semibold fs-6">Direct Commission Type</label>
      <div class="col-lg-8 fv-row">
        <select name="direct_commission_type" class="form-select form-select-lg w-200px" required>
          <?php $dct = $commissioninfo->direct_commission_type ?: 'percent'; ?>
          <option value="percent" <?php echo $dct==='percent' ? 'selected' : ''; ?>>Percent</option>
          <option value="amount"  <?php echo $dct==='amount'  ? 'selected' : ''; ?>>Fixed Amount</option>
        </select>
      </div>
    </div>

    <hr class="my-6"/>

    <!-- Level -->
    <div class="row mb-6 align-items-center">
      <label class="col-lg-8 col-form-label fw-semibold fs-6">Level Commission Status</label>
      <div class="col-lg-4 fv-row">
        <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
          <input class="form-check-input h-30px w-50px" name="level_commission_status" type="checkbox" value="1"
                 <?php echo !empty($commissioninfo->level_commission_status) ? 'checked' : ''; ?> />
        </div>
      </div>
    </div>

    <hr class="my-6"/>

    <!-- Binary & Matching -->
    <div class="row mb-6 align-items-center">
      <label class="col-lg-8 col-form-label fw-semibold fs-6">Binary Commission Status</label>
      <div class="col-lg-4 fv-row">
        <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
          <input class="form-check-input h-30px w-50px" name="binary_commission_status" type="checkbox" value="1"
                 <?php echo !empty($commissioninfo->binary_commission_status) ? 'checked' : ''; ?> />
        </div>
      </div>
    </div>

    <div class="row mb-6">
      <label class="col-lg-4 col-form-label fw-semibold fs-6">Binary Pair Type</label>
      <div class="col-lg-8 fv-row">
        <select name="binary_pair_type" class="form-select form-select-lg w-200px" required>
          <?php $bpt = $commissioninfo->binary_pair_type ?: 'percent'; ?>
          <option value="percent" <?php echo $bpt==='percent' ? 'selected' : ''; ?>>Percent</option>
          <option value="amount"  <?php echo $bpt==='amount'  ? 'selected' : ''; ?>>Fixed Amount</option>
        </select>
      </div>
    </div>

    <div class="row mb-6">
      <label class="col-lg-4 col-form-label fw-semibold fs-6">Binary Pair Ratio</label>
      <div class="col-lg-8 fv-row">
        <?php $r = $commissioninfo->binary_pair_ratio ?: '1:1'; ?>
        <select name="binary_pair_ratio" class="form-select form-select-lg w-200px" required>
          <option value="1:1" <?php echo $r==='1:1' ? 'selected' : ''; ?>>1:1</option>
          <option value="1:2" <?php echo $r==='1:2' ? 'selected' : ''; ?>>1:2</option>
          <option value="2:1" <?php echo $r==='2:1' ? 'selected' : ''; ?>>2:1</option>
        </select>
      </div>
    </div>

    <div class="row mb-6 align-items-center">
      <label class="col-lg-8 col-form-label fw-semibold fs-6">Matching Bonus Status</label>
      <div class="col-lg-4 fv-row">
        <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
          <input class="form-check-input h-30px w-50px" name="matching_bonus_status" type="checkbox" value="1"
                 <?php echo !empty($commissioninfo->matching_bonus_status) ? 'checked' : ''; ?> />
        </div>
      </div>
    </div>

    <hr class="my-6"/>

    <!-- Own / Repurchase / Leadership / Pool -->
    <div class="row mb-6 align-items-center">
      <label class="col-lg-8 col-form-label fw-semibold fs-6">Own Commission Status</label>
      <div class="col-lg-4 fv-row">
        <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
          <input class="form-check-input h-30px w-50px" name="own_commission_status" type="checkbox" value="1"
                 <?php echo !empty($commissioninfo->own_commission_status) ? 'checked' : ''; ?> />
        </div>
      </div>
    </div>

    <div class="row mb-6 align-items-center">
      <label class="col-lg-8 col-form-label fw-semibold fs-6">Repurchase Commission Status</label>
      <div class="col-lg-4 fv-row">
        <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
          <input class="form-check-input h-30px w-50px" name="repurchase_commission_status" type="checkbox" value="1"
                 <?php echo !empty($commissioninfo->repurchase_commission_status) ? 'checked' : ''; ?> />
        </div>
      </div>
    </div>

    <div class="row mb-6 align-items-center">
      <label class="col-lg-8 col-form-label fw-semibold fs-6">Leadership Bonus Status</label>
      <div class="col-lg-4 fv-row">
        <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
          <input class="form-check-input h-30px w-50px" name="leadership_bonus_status" type="checkbox" value="1"
                 <?php echo !empty($commissioninfo->leadership_bonus_status) ? 'checked' : ''; ?> />
        </div>
      </div>
    </div>

    <div class="row mb-6 align-items-center">
      <label class="col-lg-8 col-form-label fw-semibold fs-6">Pool Bonus Status</label>
      <div class="col-lg-4 fv-row">
        <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
          <input class="form-check-input h-30px w-50px" name="pool_bonus_status" type="checkbox" value="1"
                 <?php echo !empty($commissioninfo->pool_bonus_status) ? 'checked' : ''; ?> />
        </div>
      </div>
    </div>
<!-- Carry Forward Status -->
<div class="row mb-6 align-items-center">
  <label class="col-lg-8 col-form-label fw-semibold fs-6">
    Carry Forward Status
  </label>
  <div class="col-lg-4 fv-row">
    <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
      <input
        class="form-check-input h-30px w-50px"
        name="carry_forward_status"
        type="checkbox"
        value="1"
        <?= !empty($commissioninfo->carry_forward_status) ? 'checked' : ''; ?>
      />
    </div>
  </div>
</div>

<!-- Carry Forward Mode -->
<div class="row mb-6 align-items-center">
  <label class="col-lg-8 col-form-label fw-semibold fs-6">
    Carry Forward Mode
  </label>
  <div class="col-lg-4 fv-row">
    <select name="carry_forward_mode" class="form-select form-select-solid">
      <option value="LIFETIME" <?= ($commissioninfo->carry_forward_mode == 'LIFETIME' ? 'selected' : ''); ?>>
        Lifetime
      </option>
      <option value="DAILY" <?= ($commissioninfo->carry_forward_mode == 'DAILY' ? 'selected' : ''); ?>>
        Daily Reset
      </option>
      <option value="WEEKLY" <?= ($commissioninfo->carry_forward_mode == 'WEEKLY' ? 'selected' : ''); ?>>
        Weekly Reset
      </option>
      <option value="MONTHLY" <?= ($commissioninfo->carry_forward_mode == 'MONTHLY' ? 'selected' : ''); ?>>
        Monthly Reset
      </option>
    </select>
  </div>
</div>

<!-- Carry Forward Cap -->
<div class="row mb-6 align-items-center">
  <label class="col-lg-8 col-form-label fw-semibold fs-6">
    Carry Forward Cap (BV)
    <small class="text-muted">(optional)</small>
  </label>
  <div class="col-lg-4 fv-row">
    <input
      type="number"
      step="0.01"
      name="carry_forward_cap"
      value="<?= htmlspecialchars($commissioninfo->carry_forward_cap ?? ''); ?>"
      class="form-control form-control-solid"
      placeholder="Example: 50000"
    />
  </div>
</div>

  </div>

  <div class="card-footer d-flex justify-content-end py-6 px-9">
    <button type="submit" class="btn btn-primary" id="kt_account_meta_details_submit">Save Changes</button>
  </div>
</form>

                              


                    </div>
                    </div>
                                </div>

                                <!--begin::Footer-->
                                <?php $this->load->view('admin/Layout/admin_footer');?>

                            </div>
                    </div>
                    <!--end::Wrapper-->

            </div>
            <!--end::Page-->
        </div>
        <!--end::App-->

        <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
            <i class="ki-duotone ki-arrow-up">
              <span class="path1"></span>
              <span class="path2"></span>
              </i>
        </div>

        <?php $this->load->view('admin/Layout/common_script');?>

            <script src="<?php echo base_url();?>/assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.js"></script>
            <script src="<?php echo base_url();?>/assets/admin/js/widgets.bundle.js"></script>
            <script src="<?php echo base_url();?>/assets/admin/js/custom/widgets.js"></script>
            <script src="<?php echo base_url();?>/assets/admin/js/custom/apps/chat/chat.js"></script>
            <script src="<?php echo base_url();?>/assets/admin/js/custom/utilities/modals/upgrade-plan.js"></script>
            <script src="<?php echo base_url();?>/assets/admin/js/custom/utilities/modals/users-search.js"></script>
            <script src="<?php echo base_url();?>/assets/admin/plugins/global/plugins.bundle.js"></script>
            <script src="<?php echo base_url();?>assets/admin/plugins/custom/datatables/datatables.bundle.js"></script>
            <link href="<?php echo base_url();?>assets/admin/plugins/custom/fullcalendar/fullcalendar.bundle.css" rel="stylesheet" type="text/css" />
            <script src="<?php echo base_url();?>assets/admin/plugins/custom/fullcalendar/fullcalendar.bundle.js"></script>

            <script>
                const base_url = '<?php echo base_url();?>';
            </script>
            <script src="<?php echo base_url();?>/assets/admin/js/custom/authentication/sign-in/commission-settings.js?ver=2.9"></script>


           <script>
            (function(){
            // Levels
            const levelsWrap = document.getElementById('levelsWrap');
            const addLevelBtn = document.getElementById('addLevel');
            const totalLevels = document.getElementById('total_levels');
            const sumHint = document.getElementById('levelSumHint');

            function levelRow(index, val){
                return `
                <div class="row g-3 align-items-center mb-3 level-row">
                    <label class="col-lg-4 col-form-label">Level ${index} (%)</label>
                    <div class="col-lg-6">
                    <input type="number" min="0" step="0.01" name="level_commission[]" class="form-control form-control-lg form-control-solid" value="${val||0}">
                    </div>
                    <div class="col-lg-2 text-end">
                    <button type="button" class="btn btn-light-danger btn-sm remove-level">Remove</button>
                    </div>
                </div>`;
            }

            function refreshSum(){
                let sum=0;
                levelsWrap.querySelectorAll('input[name="level_commission[]"]').forEach(i=>sum+=parseFloat(i.value||0));
                sumHint.textContent = `Total Level % = ${sum.toFixed(2)} (<= 100 recommended)`;
            }

            addLevelBtn?.addEventListener('click', ()=>{
                const count = levelsWrap.querySelectorAll('.level-row').length + 1;
                levelsWrap.insertAdjacentHTML('beforeend', levelRow(count, 0));
                totalLevels.value = count;
                refreshSum();
            });

            levelsWrap?.addEventListener('click', (e)=>{
                if(e.target.classList.contains('remove-level')){
                e.target.closest('.level-row').remove();
                const rows = levelsWrap.querySelectorAll('.level-row');
                totalLevels.value = rows.length;
                // Re-label
                rows.forEach((r, idx)=>{
                    r.querySelector('.col-lg-4').innerText = `Level ${idx+1} (%)`;
                });
                refreshSum();
                }
            });

            levelsWrap?.addEventListener('input', refreshSum);
            refreshSum();

            // Matching
            const matchingWrap = document.getElementById('matchingWrap');
            const addMatch = document.getElementById('addMatch');
            const matchingLevels = document.getElementById('matching_levels');

            function matchRow(index, val){
                return `
                <div class="row g-3 align-items-center mb-3 match-row">
                    <label class="col-lg-4 col-form-label">Match Level ${index} (%)</label>
                    <div class="col-lg-6">
                    <input type="number" min="0" step="0.01" name="matching_percents[]" class="form-control form-control-lg form-control-solid" value="${val||0}">
                    </div>
                    <div class="col-lg-2 text-end">
                    <button type="button" class="btn btn-light-danger btn-sm remove-match">Remove</button>
                    </div>
                </div>`;
            }

            addMatch?.addEventListener('click', ()=>{
                const count = matchingWrap.querySelectorAll('.match-row').length + 1;
                matchingWrap.insertAdjacentHTML('beforeend', matchRow(count, 0));
                matchingLevels.value = count;
            });

            matchingWrap?.addEventListener('click', (e)=>{
                if(e.target.classList.contains('remove-match')){
                e.target.closest('.match-row').remove();
                const rows = matchingWrap.querySelectorAll('.match-row');
                matchingLevels.value = rows.length;
                rows.forEach((r, idx)=>{ r.querySelector('.col-lg-4').innerText = `Match Level ${idx+1} (%)`; });
                }
            });
            })();
            </script>
    </body>

    </html>