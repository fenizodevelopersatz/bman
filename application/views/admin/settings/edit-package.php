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

                <?php
                // Ensure array vars exist for edit/create
                $matching_bonus = isset($matching_bonus)
                ? (is_array($matching_bonus) ? $matching_bonus : (json_decode($matching_bonus, true) ?: []))
                : [];
                $level_pv = isset($level_pv)
                ? (is_array($level_pv) ? $level_pv : (json_decode($level_pv, true) ?: []))
                : [];
                $product_level_commission_amount = isset($product_level_commission_amount)
                ? (is_array($product_level_commission_amount) ? $product_level_commission_amount : (json_decode($product_level_commission_amount, true) ?: []))
                : [];
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


                        <div id="kt_account_addagent_form_details" class="collapse show">
                        <div class="card-body border-top p-9">
                        
                        <?php $action = base_url()."package-update"; ?>
                        <?= form_open($action, ['class' => 'form-validate', 'method' => 'post', 'autocomplete' => 'off', 'id' => 'kt_account_meta_details_form',"data-kt-redirect-url"=> base_url()."package-settings"]) ?>
                        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>" />  
                                      

                        <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Package Name<span class="text-danger"> * </span></label>
                        <div class="col-lg-8 fv-row">
                        <div class="input-group mb-5">
                        <span class="input-group-text border-transparent " id="basic-addon1"><i class="fa-solid fa-note-sticky "></i></span>
                        <input type="text" name="package_name" 
                        class="form-control form-control-lg form-control-solid" 
                        placeholder="Enter package name" 
                        value="<?php echo $package_name; ?>" required>
                        </div>
                        </div>
                        </div>

                     
                        <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Minimum Amount<span class="text-danger"> * </span></label>
                        <div class="col-lg-8 fv-row mb-5">
                        <div class="input-group ">
                        <span class="input-group-text border-transparent " id="basic-addon1"><i class="fa-solid fa-note-sticky "></i></span>
                        <input type="text" name="minimum" 
                        class="form-control form-control-lg form-control-solid" 
                        placeholder="Enter minimum amount" 
                        value="<?php echo $minimum; ?>" required>
                        </div>
                        </div>
                        </div>

                        <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Maximum Amount<span class="text-danger"> * </span></label>
                        <div class="col-lg-8 fv-row mb-5">
                        <div class="input-group ">
                        <span class="input-group-text border-transparent " id="basic-addon1"><i class="fa-solid fa-note-sticky "></i></span>
                        <input type="text" name="maximum" 
                        class="form-control form-control-lg form-control-solid" 
                        placeholder="Enter maximum amount" 
                        value="<?php echo $maximum; ?>" required>
                        </div>
                        <small class="text-danger"> Notes : if set 0 unlimit  </small>
                        </div>
                        </div>

                        <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6"> Select Period <span class="text-danger"> * </span></label>
                        <div class="col-lg-8 fv-row">
                        <div class="input-group mb-5">
                            <select class="form-select form-select-solid" 
                            data-control="select2" data-close-on-select="false" id="period" name="period"
                            data-placeholder="Select a Period" data-allow-clear="true">
                            <option></option>
                            <option value="daily" <?php echo $period == "daily" ? "selected" : "" ?> >Daily</option>
                            <option value="weekly" <?php echo $period == "weekly" ? "selected" : "" ?>>Weekly</option>
                            <option value="monthly" <?php echo $period == "monthly" ? "selected" : "" ?>>Monthly</option>
                            <option value="yearly" <?php echo $period == "yearly" ? "selected" : "" ?>>Yearly</option>
                            </select>
                        </div>
                        </div>
                        </div>


                        <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Package ROI (%)<span class="text-danger"> * </span></label>
                        <div class="col-lg-8 fv-row">
                        <div class="input-group mb-5">
                        <span class="input-group-text border-transparent " id="basic-addon1"><i class="fa-solid fa-note-sticky "></i></span>
                        <input type="text" name="roi" 
                        class="form-control form-control-lg form-control-solid" 
                        placeholder="Enter ROI" 
                        value="<?php echo $roi; ?>" required>
                        </div>
                        </div>
                        </div>

                        <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Package Duration<span class="text-danger"> * </span></label>
                        <div class="col-lg-8 fv-row">
                        <div class="input-group mb-5">
                        <span class="input-group-text border-transparent " id="basic-addon1"><i class="fa-solid fa-note-sticky "></i></span>
                        <input type="text" name="duration" 
                        class="form-control form-control-lg form-control-solid" 
                        placeholder="Enter duration" 
                        value="<?php echo $duration; ?>" required>
                        </div>
                        </div>
                        </div>


                        <!-- Binary / Plan Settings --------------------------------------------------->
                        <div class="separator my-10"></div>
                        <h3 class="fw-bold mb-7">Binary & Direct Settings</h3>

                        <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Business Volume (BV) <span class="text-danger">*</span></label>
                        <div class="col-lg-8 fv-row">
                            <div class="input-group mb-5">
                            <span class="input-group-text border-transparent"><i class="fa-solid fa-coins"></i></span>
                            <input type="text" name="bv" class="form-control form-control-lg form-control-solid"
                                    placeholder="e.g. 10" value="<?php echo isset($bv)?$bv:''; ?>" required>
                            </div>
                        </div>
                        </div>

                        <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Binary Commission <span class="text-danger">*</span></label>
                        <div class="col-lg-8 fv-row">
                            <div class="row g-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                <span class="input-group-text border-transparent"><i class="fa-solid fa-percent"></i></span>
                                <input type="text" name="binary_commission" class="form-control form-control-lg form-control-solid"
                                        placeholder="e.g. 10" value="<?php echo isset($binary_commission)?$binary_commission:''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <select class="form-select form-select-solid" name="binary_commission_type" data-control="select2" data-placeholder="Type">
                                <option value="percent" <?php echo (isset($binary_commission_type)&&$binary_commission_type==='percent')?'selected':''; ?>>Percentage</option>
                                <option value="amount"  <?php echo (isset($binary_commission_type)&&$binary_commission_type==='amount')?'selected':''; ?>>Flat Amount</option>
                                </select>
                            </div>
                            </div>
                        </div>
                        </div>

                        <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Own Commission(%) <span class="text-danger">*</span></label>
                        <div class="col-lg-8 fv-row">
                            <div class="input-group">
                            <span class="input-group-text border-transparent"><i class="fa-solid fa-user-check"></i></span>
                            <input type="text" name="own_commission" class="form-control form-control-lg form-control-solid"
                                    placeholder="e.g. 20" value="<?php echo isset($own_commission)?$own_commission:''; ?>" required>
                            </div>
                        </div>
                        </div>

                        <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Direct Commission <span class="text-danger">*</span></label>
                        <div class="col-lg-8 fv-row">
                            <div class="input-group">
                            <span class="input-group-text border-transparent"><i class="fa-solid fa-user-plus"></i></span>
                            <input type="text" name="direct_commission" class="form-control form-control-lg form-control-solid"
                                    placeholder="e.g. 30" value="<?php echo isset($direct_commission)?$direct_commission:''; ?>" required>
                            </div>
                        </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">Pair Commission Status <span class="text-danger">*</span></label>
                            <div class="col-lg-8 fv-row">
                                <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                <input class="form-check-input h-30px w-50px" type="checkbox" value="1" id="pair_commission_status_sw"
                                        <?php echo (!isset($pair_commission_status) || (int)$pair_commission_status===1)?'checked':''; ?>>
                                <input type="hidden" name="pair_commission_status"
                                        value="<?php echo (!isset($pair_commission_status) || (int)$pair_commission_status===1)?'1':'0'; ?>">
                                <label class="form-check-label" for="pair_commission_status_sw"></label>
                                </div>
                            </div>
                            </div>

                            <div id="pairFields" class="<?php echo (isset($pair_commission_status) && (int)$pair_commission_status===0)?'d-none':''; ?>">
                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Pair Commission <span class="text-danger">*</span></label>
                                <div class="col-lg-8 fv-row">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text border-transparent"><i class="fa-solid fa-link"></i></span>
                                        <input type="text" name="pair_commission" class="form-control form-control-lg form-control-solid"
                                            placeholder="e.g. 10" value="<?php echo isset($pair_commission)?$pair_commission:''; ?>">
                                    </div>
                                    </div>
                                    <div class="col-md-6">
                                    <select class="form-select form-select-solid" name="pair_commission_type" data-control="select2" data-placeholder="Type">
                                        <option value="percent" <?php echo (isset($pair_commission_type)&&$pair_commission_type==='percent')?'selected':''; ?>>Percentage</option>
                                        <option value="amount"  <?php echo (isset($pair_commission_type)&&$pair_commission_type==='amount')?'selected':''; ?>>Flat Amount</option>
                                    </select>
                                    </div>
                                </div>
                                </div>
                            </div>

                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Daily Maximum Pairs <span class="text-danger">*</span></label>
                                <div class="col-lg-8 fv-row">
                                <input type="number" name="daily_max_pairs" min="0"
                                        class="form-control form-control-lg form-control-solid"
                                        placeholder="e.g. 5" value="<?php echo isset($daily_max_pairs)?$daily_max_pairs:''; ?>">
                                </div>
                            </div>
                            </div>


                            <div class="separator my-10"></div>
                                <h3 class="fw-bold mb-7">Multi-Level Settings</h3>

                                <!-- Matching Bonus ---------------------------------------------------------->
                                <div class="row mb-4">
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Matching Bonus</label>
                                <div class="col-lg-8 fv-row">
                                    <div id="matchingBonusWrap" class="vstack gap-3">
                                    <?php if (count($matching_bonus)): foreach ($matching_bonus as $v): ?>
                                        <div class="input-group mb-2 matching-row">
                                        <input type="text" name="matching_bonus[]" class="form-control form-control-lg form-control-solid" value="<?php echo htmlspecialchars($v, ENT_QUOTES); ?>">
                                        <button type="button" class="btn btn-light-danger ms-2 btn-remove-row"><i class="fa fa-minus"></i></button>
                                        </div>
                                    <?php endforeach; else: ?>
                                        <div class="input-group mb-2 matching-row">
                                        <input type="text" name="matching_bonus[]" class="form-control form-control-lg form-control-solid" placeholder="e.g. 10">
                                        <button type="button" class="btn btn-light-danger ms-2 btn-remove-row"><i class="fa fa-minus"></i></button>
                                        </div>
                                    <?php endif; ?>
                                    </div>
                                    <button type="button" class="btn btn-light-primary mt-3" id="addMatching"><i class="fa fa-plus"></i> Add Row</button>
                                </div>
                                </div>

                                <!-- Level PV ---------------------------------------------------------------->
                                <div class="row mb-4">
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Level PV</label>
                                <div class="col-lg-8 fv-row">
                                    <div id="levelPvWrap" class="vstack gap-3">
                                    <?php if (count($level_pv)): foreach ($level_pv as $v): ?>
                                        <div class="input-group mb-2 levelpv-row">
                                        <input type="text" name="level_pv[]" class="form-control form-control-lg form-control-solid" value="<?php echo htmlspecialchars($v, ENT_QUOTES); ?>">
                                        <button type="button" class="btn btn-light-danger ms-2 btn-remove-row"><i class="fa fa-minus"></i></button>
                                        </div>
                                    <?php endforeach; else: ?>
                                        <div class="input-group mb-2 levelpv-row">
                                        <input type="text" name="level_pv[]" class="form-control form-control-lg form-control-solid" placeholder="e.g. 10">
                                        <button type="button" class="btn btn-light-danger ms-2 btn-remove-row"><i class="fa fa-minus"></i></button>
                                        </div>
                                    <?php endif; ?>
                                    </div>
                                    <button type="button" class="btn btn-light-primary mt-3" id="addLevelPv"><i class="fa fa-plus"></i> Add Row</button>
                                </div>
                                </div>

                                <!-- Product Level Commission Amount ---------------------------------------->
                                <div class="row mb-6">
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Product Level Commission Amount</label>
                                <div class="col-lg-8 fv-row">
                                    <div id="productLvlCommWrap" class="vstack gap-3">
                                    <?php if (count($product_level_commission_amount)): foreach ($product_level_commission_amount as $v): ?>
                                        <div class="input-group mb-2 prod-row">
                                        <input type="text" name="product_level_commission_amount[]" class="form-control form-control-lg form-control-solid" value="<?php echo htmlspecialchars($v, ENT_QUOTES); ?>">
                                        <button type="button" class="btn btn-light-danger ms-2 btn-remove-row"><i class="fa fa-minus"></i></button>
                                        </div>
                                    <?php endforeach; else: ?>
                                        <div class="input-group mb-2 prod-row">
                                        <input type="text" name="product_level_commission_amount[]" class="form-control form-control-lg form-control-solid" placeholder="e.g. 12">
                                        <button type="button" class="btn btn-light-danger ms-2 btn-remove-row"><i class="fa fa-minus"></i></button>
                                        </div>
                                    <?php endif; ?>
                                    </div>
                                    <button type="button" class="btn btn-light-primary mt-3" id="addProdLvlComm"><i class="fa fa-plus"></i> Add Row</button>
                                </div>
                                </div>


                        
                        <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Return Capital invesment<span class="text-danger"> * </span></label>
                        <div class="col-lg-8 fv-row">
                        <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                        <input class="form-check-input h-30px w-50px" type="checkbox" value="1" name="retrun_principle"
                        <?php echo $retrun_principle > 0 ? "checked" : "" ; ?>
                        id="retrun_principle" />
                        <label class="form-check-label" for="retrun_principle">
                        </label>
                        </div>
                        </div>
                        </div>

                        <div class="row mb-6 d-none">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6 ">ROI Made by<span class="text-danger"> * </span></label>
                        <div class="col-lg-8 fv-row">
                        <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                        <select class="form-select form-select-solid" 
                            data-control="select2" data-close-on-select="false" id="roi_made_by" name="roi_made_by"
                            data-placeholder="Select a ROI Type" data-allow-clear="true">
                            <option></option>
                            <option value="currency" <?php echo $roi_made_by == "currency" ? "selected" : "" ?> >Site Currency  <?php echo $currency_info->coin_name; ?></option>
                            <option value="token" <?php echo $roi_made_by == "token" ? "selected" : "" ?> selected>Token <?php echo $token_info->coin_name; ?></option>
                            </select>
                        </label>
                        </div>
                        </div>
                        </div>

                        <input type="hidden" name="package_id" value="<?php echo $package_id; ?>" />
                                                
                        <div class="col-md-12">
                        <div class="form-group"><button type="submit" id="kt_account_meta_details_submit"
                        class="btn btn-lg btn-primary">Submit</button>
                        </div>
                        </div>

                    </div>
                    </div>

                    </div>
                    </div>




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
            <script src="<?php echo base_url();?>/assets/admin/js/custom/authentication/sign-in/edit-package.js?ver=2.9"></script>



            <script>
                (function(){
                // Keep hidden input in sync with switch for pair status
                const pairSw = document.getElementById('pair_commission_status_sw');
                const pairHidden = document.querySelector('input[name="pair_commission_status"]');
                const pairFields = document.getElementById('pairFields');
                if (pairSw && pairHidden && pairFields) {
                    pairSw.addEventListener('change', function(){
                    pairHidden.value = this.checked ? '1' : '0';
                    pairFields.classList.toggle('d-none', !this.checked);
                    });
                }

                // Row factories
                function makeRow(inputName){
                    const wrap = document.createElement('div');
                    wrap.className = 'input-group mb-2';
                    wrap.innerHTML = `
                    <input type="text" name="${inputName}" class="form-control form-control-lg form-control-solid" placeholder="0">
                    <button type="button" class="btn btn-light-danger ms-2 btn-remove-row"><i class="fa fa-minus"></i></button>`;
                    return wrap;
                }

                // Add buttons
                const addMatching = document.getElementById('addMatching');
                const matchingWrap = document.getElementById('matchingBonusWrap');
                addMatching && addMatching.addEventListener('click', ()=> matchingWrap.appendChild(makeRow('matching_bonus[]')));

                const addLevelPv = document.getElementById('addLevelPv');
                const levelPvWrap = document.getElementById('levelPvWrap');
                addLevelPv && addLevelPv.addEventListener('click', ()=> levelPvWrap.appendChild(makeRow('level_pv[]')));

                const addProd = document.getElementById('addProdLvlComm');
                const prodWrap = document.getElementById('productLvlCommWrap');
                addProd && addProd.addEventListener('click', ()=> prodWrap.appendChild(makeRow('product_level_commission_amount[]')));

                // Remove row (event delegation)
                document.addEventListener('click', function(e){
                    if (e.target.closest('.btn-remove-row')) {
                    const group = e.target.closest('.input-group');
                    const container = group?.parentElement;
                    // Keep at least one row visible
                    if (container && container.querySelectorAll('.input-group').length > 1) {
                        group.remove();
                    } else {
                        const input = group.querySelector('input'); if (input) input.value='';
                    }
                    }
                });
                })();
                </script>

    </body>

    </html>