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
                                                        Network  Management                    
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


                     <!-- INSEIDE FORM ENTER -->

                               <div id="kt_account_settings_profile_details" class="collapse show">

                            <?= form_open($action, ['class' => 'form-validate', 'method' => 'post', 'autocomplete' => 'off', 'id' => 'kt_account_meta_details_form',"data-kt-redirect-url"=> base_url()."newsletter-marketting"]) ?>
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>" />  

                               <div class="card-body border-top p-9">

<!--                                <div class="row mb-6">
                                <label class="col-lg-4 col-form-label fw-semibold fs-6"> Select Sponser <span class="text-danger"> * </span></label>
                                <div class="col-lg-8 fv-row">
                                <div class="input-group mb-5">
                                <select class="form-select form-select-solid" 
                                    data-control="select2" data-close-on-select="false"  name="sponsor_id"
                                    data-placeholder="Select a member" data-allow-clear="true">
                                    <option></option>
                                   <?php if(COUNT($users) > 0){ ?>
                                   <?php foreach($users as $list){?>
                                    <option value="<?php echo $list->id?>"><?php echo $list->username." ( ".$list->referral_id." ) ";?></option>
                                    <?php } ?>
                                    <?php } ?>
                                    </select>
                                </div>
                                </div>
                                </div> -->

                                <div class="row mb-6">
                                    <label class="col-lg-4 col-form-label fw-semibold fs-6">Select Sponsor <span class="text-danger">*</span></label>
                                    <div class="col-lg-8 fv-row">
                                        <select class="form-select form-select-solid" 
                                                id="sponsor_id" name="sponsor_id"
                                                data-placeholder="Select a member" data-allow-clear="true">
                                        </select>
                                    </div>
                                </div>


      
                             <!--<div class="row mb-6">
                                <label class="col-lg-4 col-form-label fw-semibold fs-6"> Select Leg <span class="text-danger"> * </span></label>
                                <div class="col-lg-8 fv-row">
                                       
                                        <div data-kt-buttons="true row">
                                            <label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex flex-stack text-start p-6 mb-5 ">
                                                <div class="d-flex align-items-center me-2">
                                                    <div class="form-check form-check-custom form-check-solid form-check-primary me-6">
                                                        <input class="form-check-input" type="radio" name="select_lg" checked value="left"/>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h2 class="d-flex align-items-center fs-3 fw-bold flex-wrap">
                                                        Left Leg
                                                        </h2>
                                                        <div class="fw-semibold opacity-50">
                                                        Select this if you want to assign to the Left Leg
                                                        </div>
                                                    </div>
                                                </div>
                                            </label>
                                            
                                            
                                            <label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex flex-stack text-start p-6">
                                                <div class="d-flex align-items-center me-2">
                                                    <div class="form-check form-check-custom form-check-solid form-check-primary me-6">
                                                        <input class="form-check-input" type="radio" name="select_lg" value="right"/>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h2 class="d-flex align-items-center fs-3 fw-bold flex-wrap">
                                                        Right Leg
                                                        </h2>
                                                        <div class="fw-semibold opacity-50">
                                                        Select this if you want to assign to the Right Leg
                                                        </div>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    
                                </div>
                            </div>-->

                            <!-- Package Name -->
                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Package Name <span class="text-danger">*</span></label>
                                <div class="col-lg-8 fv-row">
                                    <select class="form-select form-select-solid" name="package_id" id="package_id" required>
                                        <option value="">Select Package</option>
                                        <?php foreach($package_list as $package): ?>
                                            <option value="<?= $package->id ?>" 
                                                <?= (isset($epin) && $epin->package_id == $package->id) ? 'selected' : '' ?>>
                                                <?= $package->package_name ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>


                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Select EPIN <span class="text-danger">*</span></label>
                                <div class="col-lg-8 fv-row">
                                    <select class="form-select form-select-solid" name="epin_id" id="epin_id" style="width:100%" required>
                                    </select>
                                </div>
                            </div>


                           
                            
                               <div class="row mb-6">
                               <label class="col-lg-4 col-form-label fw-semibold fs-6">User Name <span class="text-danger"> * </span></label>
                               <div class="col-lg-8 fv-row">
                               <div class="input-group mb-5">
                               <span class="input-group-text border-transparent " id="basic-addon1"><i class="fa fa-user" aria-hidden="true"></i></span>
                               <input type="text" name="username" 
                               class="form-control form-control-lg form-control-solid" 
                               placeholder="Enter User Name" 
                               value="" required>
                               </div>
                               </div>
                               </div>



                               <div class="row mb-6">
                               <label class="col-lg-4 col-form-label fw-semibold fs-6">User Email <span class="text-danger"> * </span></label>
                               <div class="col-lg-8 fv-row">
                               <div class="input-group mb-5">
                               <span class="input-group-text border-transparent " id="basic-addon1"><i class="fa fa-envelope" aria-hidden="true"></i></span>
                               <input type="text" name="useremail" 
                               class="form-control form-control-lg form-control-solid" 
                               placeholder="Enter Email" 
                               value="" required>
                               </div>
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
            <script src="<?php echo base_url();?>/assets/admin/js/custom/authentication/sign-in/create-user.js?ver=2.9"></script>
            <script>
            </script>
             <!-- Initialize Select2 -->
            <script>
                $(document).ready(function() {
                    $('#package_id').select2({
                        placeholder: "Select Package",
                        allowClear: true,
                        width: '100%'
                    });
                });
            </script>
            <script>
            $(document).ready(function() {
                       
                $(document).ready(function() {

              // When package is changed → Reset EPIN field
                $('#package_id').on('change', function () {
                    $('#epin_id').val(null).trigger('change');  // clear Select2
                });



                    $('#epin_id').select2({
                        placeholder: "Select EPIN",
                        allowClear: true,
                        ajax: {
                            url: '<?= base_url("admin/get_all_epins_ajax") ?>',
                            dataType: 'json',
                            delay: 250,
                            data: function (params) {
                                return {
                                    q: params.term,        // search term (optional)
                                    package_id: $('#package_id').val()  // send selected package_id
                                };
                            },
                            processResults: function (data) {
                                return {
                                    results: data
                                };
                            },
                            cache: true
                        }
                    });



                      $('#sponsor_id').select2({
                        placeholder: 'Select a member',
                        allowClear: true,
                        // minimumInputLength: 1, // start searching after typing 1 char
                        ajax: {
                            url: '<?= base_url("admin/search_sponsor") ?>',
                            dataType: 'json',
                            delay: 250,
                            data: function(params) {
                                return {
                                    q: params.term // search term
                                };
                            },
                            processResults: function(data) {
                                return {
                                    results: data.results
                                };
                            },
                            cache: true
                        }
                    });

                              
                });

            });

          

            </script>


    </body>

    </html>