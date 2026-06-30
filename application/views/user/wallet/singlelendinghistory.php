<?php $this->load->view('user/layout/user_style');?>
    <!--end::Head-->

    <!--begin::Body-->

    <body id="kt_app_body" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-push-toolbar="true" data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">

        <!--begin::App-->
        <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
            <div class="app-page  flex-column flex-column-fluid " id="kt_app_page">

                <div id="kt_app_header" class="app-header " data-kt-sticky="true" data-kt-sticky-activate-="true" data-kt-sticky-name="app-header-sticky" data-kt-sticky-offset="{default: '200px', lg: '300px'}">
                    <div class="app-container  container-xxl d-flex align-items-stretch justify-content-between " id="kt_app_header_container">
                        <div class="app-header-wrapper d-flex flex-grow-1 align-items-stretch justify-content-between" id="kt_app_header_wrapper">
                            <?php $this->load->view('user/layout/user_header'); ?>
                        </div>
                    </div>
                </div>
                <!--end::Header-->

                <!--begin::Wrapper-->
                <div class="app-wrapper  flex-column flex-row-fluid " id="kt_app_wrapper">

                    <!--begin::Wrapper container-->
                    <div class="app-container  container-xxl d-flex flex-row-fluid ">

                        <!--begin::Sidebar-->
                      <?php $this->load->view('user/layout/user_sidebar');?>
                        <!--end::Sidebar-->


                        <!--begin::Main-->
                        <div class="app-main flex-column flex-row-fluid " id="kt_app_main">
                            <!--begin::Content wrapper-->
                            <div class="d-flex flex-column flex-column-fluid">

                                <!--begin::Toolbar-->
                                <div id="kt_app_toolbar" class="app-toolbar  d-flex pb-3 pb-lg-5 ">

                                    <!--begin::Toolbar container-->
                                    <div class="d-flex flex-stack flex-row-fluid">
                                        <!--begin::Toolbar container-->
                                        <div class="d-flex flex-column flex-row-fluid">
                                            <!--begin::Toolbar wrapper-->

                                            <!--begin::Page title-->
                                            <div class="page-title d-flex align-items-center me-3">
                                                <!--begin::Title-->
                                                <h1 class="page-heading d-flex flex-column justify-content-center text-gray-900 fw-bold fs-lg-2x gap-2">
                                                     <span> <?php echo $title; ?></span>

                                                        <!--begin::Description-->
                                                <span class="page-desc text-gray-600 fs-base fw-semibold">
                                                  <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                                    <li class="breadcrumb-item text-muted">
                                                        <a href="<?php echo base_url();?>" class="text-muted text-hover-primary">
                                                        <?php echo lang('dashbaord'); ?>      
                                                        </a>
                                                    </li>
                                                    <li class="breadcrumb-item">
                                                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                                    </li>
                                                    <li class="breadcrumb-item text-muted">
                                                    <?php echo $title; ?> </li>
                                                </ul>      
                                                </span>
                                                <!--end::Description-->
                                                </h1>
                                                                                    <!--end::Title-->
                                            </div>
                                            <!--end::Page title-->

                                        </div>
                                        <!--end::Toolbar container-->
                                    </div>
                                    <!--end::Toolbar container-->
                                </div>
                                <!--end::Toolbar-->



                                <div id="kt_app_content" class="app-content  flex-column-fluid ">
                                <!--begin::Card-->
                              


                                <div class="card card-flush pt-3 mb-5 mb-xl-10">
                                    <!--begin::Card header-->
                                    <div class="card-header">
                                        <!--begin::Card title-->
                                        <div class="card-title">
                                            <h2 class="fw-bold">Investment Details</h2>
                                        </div>
                                        <!--begin::Card title-->
                                    </div>
                                    <!--end::Card header-->

                                    <!--begin::Card body-->
                                    <div class="card-body pt-3">
                                        <!--begin::Section-->
                                        <div class="mb-10">
                                            <!--begin::Title-->
                                            <h5 class="mb-4">Billing Address:</h5>
                                            <!--end::Title-->

                                            <!--begin::Details-->
                                            <div class="d-flex flex-wrap py-5">
                                                <!--begin::Row-->
                                             <div class="flex-equal me-5">
                                                <!--begin::Details-->
                                                <table class="table fs-6 fw-semibold gs-0 gy-2 gx-2 m-0">
                                                    <tbody>
                                                        <tr>
                                                            <td class="text-gray-500"><?php echo lang('customer_name'); ?></td>
                                                            <td class="text-gray-800"><?php echo $username; ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-gray-500"><?php echo lang('customer_email'); ?></td>
                                                            <td class="text-gray-800"><?php echo $useremail; ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-gray-500"><?php echo lang('referral_id'); ?></td>
                                                            <td class="text-gray-800"><?php echo $userreferralid; ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-gray-500"><?php echo lang('payment_by'); ?></td>
                                                            <td class="text-gray-800"><?php echo $payment_by; ?></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <!--end::Details-->
                                            </div>
                                                <!--end::Row-->

                                                <!--begin::Row-->
                                                <div class="flex-equal">
                                                    <!--begin::Details-->
                                                    <table class="table fs-6 fw-semibold gs-0 gy-2 gx-2 m-0">
                                                        <!--begin::Row-->
                                                       <tbody>
                                                        <tr>
                                                            <td class="text-gray-500 min-w-175px w-175px"><?php echo lang('package_name'); ?></td>
                                                            <td class="text-gray-800 min-w-200px">
                                                                <a href="#" class="text-gray-800 text-hover-primary"><?php echo $packagename; ?></a>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td class="text-gray-500"><?php echo lang('package_limit'); ?></td>
                                                            <td class="text-gray-800"><?php echo $min_max_package; ?></td>
                                                        </tr>

                                                        <tr>
                                                            <td class="text-gray-500"><?php echo lang('total_duration'); ?></td>
                                                            <td class="text-gray-800"><?php echo $pacakgeduration; ?></td>
                                                        </tr>
                                                    </tbody>
                                                    </table>
                                                    <!--end::Details-->
                                                </div>
                                                <!--end::Row-->
                                            </div>
                                            <!--end::Row-->
                                        </div>
                                        <!--end::Section-->

                                        <!--begin::Section-->
                                        <div class="mb-0">
                                            <!--begin::Title-->
                                          <h5 class="mb-4"><?php echo lang('subscribed_info'); ?></h5>

                                            <div class="table-responsive">
                                                <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                                    <thead>
                                                        <tr class="border-bottom border-gray-200 text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                                            <th class="min-w-150px"><?php echo lang('investment_date'); ?></th>
                                                            <th class="min-w-125px"><?php echo lang('total_earning'); ?></th>
                                                            <th class="min-w-125px"><?php echo lang('investment_status'); ?></th>
                                                            <th class="min-w-125px"><?php echo lang('total_paid_amount'); ?></th>
                                                        </tr>
                                                    </thead>
                                                    <!--begin::Table body-->
                                                    <tbody class="fw-semibold text-gray-800">
                                                        <tr>
                                                            <td>
                                                                <?php echo $dateinfo; ?>
                                                            </td>
                                                            <td><span class="text-success fw-semibold "> <span class="p-5"> <?php echo $total_earnings_currency;?></span></span></td>
                                                            <td><?php echo $invest_status; ?></td>
                                                            <td><?php echo $packageamount; ?> / <?php echo $packagetokenamount; ?></td>
                                                        </tr>
                                                    </tbody>
                                                    <!--end::Table body-->
                                                </table>
                                                <!--end::Table-->
                                            </div>
                                            <!--end::Product table-->
                                        </div>
                                        <!--end::Section-->
                                    </div>
                                    <!--end::Card body-->
                                </div>




                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="card mb-5 mb-xxl-8">

                                                        <div class="card-header mb-4 border-transparent">
                                                            <h3 class="anchor fw-bold "><?php echo $card_tilte;?></h3>
                                                        </div>

                                                        <div class="card-body pt-9 pb-9">

                                                        <div class="d-flex flex-stack mb-5">

                                                            <div class="col-lg-12 mb-4">
                                                                <div class="col-lg-12">
                                                                    <div class="row">
                                                                        <div class="col-lg-6  mb-2">
                                                                            <input type="date" id="cl_from_date" class="form-control form-control-solid w-250px ps-15 me-3" placeholder="Start Date">
                                                                        </div>

                                                                        <div class="col-lg-6  mb-2">
                                                                            <input type="date" id="cl_to_date" class="form-control form-control-solid w-250px ps-15  me-3" placeholder="End Date">
                                                                        </div>

                                                                    </div>
                                                                </div>
                                                            </div>

                                                        </div>

                                                        <div class="row justify-content-between das_ro">

                                                            <div class="border border-gray-300 border-dashed rounded col-lg-6 py-3 px-4 mb-3">

                                                            <div class="d-flex align-items-center mb-2">   
                                                            <span class="fs-1 fw-semibold text-gray-500 me-1 mt-n1"><?php echo $currency_info->currency_symbol; ?></span>
                                                            <span class="fs-3x fw-bold text-gray-800 me-2 lh-1 ls-n2 counted"  id="icd" data-kt-initialized="1">0</span>
                                                            </div>
                                                            <div class="d-flex flex-stack">
                                                            <div class="fw-semibold fs-6 text-success">Total Site Currency </div>
                                                            </div>
                                                            </div>

                                                            </div>

                                                            <div class="row">

                                                        


                                                                <div class="d-flex align-items-center position-relative my-1">
                                                                    <i class="ki-duotone ki-magnifier fs-1 position-absolute ms-6"><span class="path1"></span><span class="path2"></span></i>
                                                                    <input type="text" data-kt-docs-table-filter="search" 
                                                                    class="form-control form-control-solid w-250px ps-15" placeholder="Search" />
                                                                </div>

                                                                <div class="d-flex justify-content-end" data-kt-docs-table-toolbar="base">
                                                                    <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                                                        <i class="ki-duotone ki-exit-down fs-2"><span class="path1"></span><span class="path2"></span></i> Export Report
                                                                    </button>

                                                                    <div id="kt_datatable_example_export_menu" class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4" data-kt-menu="true">
                                                                        <div class="menu-item px-3">
                                                                            <a href="#" class="menu-link px-3" data-kt-export="copy">
                                                                            Copy to clipboard
                                                                            </a>
                                                                        </div>
                                                                        <div class="menu-item px-3">
                                                                            <a href="#" class="menu-link px-3" data-kt-export="excel">
                                                                                    Export as Excel
                                                                                    </a>
                                                                        </div>
                                                                        <div class="menu-item px-3">
                                                                            <a href="#" class="menu-link px-3" data-kt-export="csv">
                                                                            Export as CSV
                                                                            </a>
                                                                        </div>
                                                                        <div class="menu-item px-3">
                                                                            <a href="#" class="menu-link px-3" data-kt-export="pdf">
                                                                                    Export as PDF
                                                                                    </a>
                                                                        </div>
                                                                        <div id="kt_datatable_example_buttons" class="d-none"></div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <table id="kt-client-follow-table" class="table align-middle table-row-dashed fs-6 gy-5">
                                                                <thead>
                                                                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                                                    <th>S/No</th>
                                                                    <th>User Info</th>
                                                                    <th>Transaction Info</th>
                                                                    <th>Currency Info</th>
                                                                    <th>Status</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="text-gray-600 fw-semibold">
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        
                                    </div>
                                </div>


                                <!--end::Card-->
                                </div>






   
                                    </div>

                                </div>


                            </div>
                    </div>
                    <!--end::Wrapper-->

            </div>
                                 
                                </div>
                                <!--end::Content-->

                            </div>
                            <!--end::Content wrapper-->

                                <!--begin::Footer-->
                                <?php $this->load->view('user/layout/user_footer');?>
                                <!--end::Footer-->
                        </div>
                        <!--end:::Main-->


                    </div>
                    <!--end::Wrapper container-->
                </div>
                <!--end::Wrapper-->


            </div>
            <!--end::Page-->
        </div>
        <!--end::App-->

        <!--end::Engage modals-->
        <!--begin::Scrolltop-->
        <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
            <i class="ki-outline ki-arrow-up"></i></div>
        <!--end::Scrolltop-->

        <!--begin::Javascript-->
        <?php $this->load->view('user/layout/user_script');?>
        <script>
            const base_url = '<?php echo base_url();?>';
            const agent_id = '<?php echo $user_id;?>';
            const invest_id = '<?php echo $invest_id; ?>';
            </script>
            <script src="<?php echo base_url();?>/assets/admin/js/custom/authentication/sign-in/investment-info.js?ver=2.9"></script>

    </body>

    </html>