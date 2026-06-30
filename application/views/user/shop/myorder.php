<?php $this->load->view('user/layout/user_style');?>
    <!--end::Head-->

    <!--begin::Body-->

    <style>
        .expanded-content {
            padding: 15px;
            background: #f9f9f9;
            border-top: 1px solid #e0e0e0;
            margin-top: 10px;
        }
    </style>
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
                                                        <?php echo lang('dashboard'); ?>                         
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




                                <div class="col-xl-12 mb-5 mb-xl-10" data-select2-id="select2-data-124-qnuf">

                                    <!--begin::Table Widget 4-->
                                    <div class="card card-flush h-xl-100" data-select2-id="select2-data-123-y9dj">
                                        <!--begin::Card header-->
                                        <div class="card-header pt-7" data-select2-id="select2-data-122-k2pu">
                                            <!--begin::Title-->
                                            <h3 class="card-title align-items-start flex-column">
                                            <span class="card-label fw-bold text-gray-800">Product Orders</span>
                                            <!-- <span class="text-gray-500 mt-1 fw-semibold fs-6">Avg. 57 orders per day</span> -->
                                        </h3>
                                            <!--end::Title-->

                                            <!--begin::Actions-->
                                            <div class="card-toolbar" data-select2-id="select2-data-121-4j1g">
                                                <!--begin::Filters-->
                                                <div class="d-flex flex-stack flex-wrap gap-4" data-select2-id="select2-data-120-0cb9">
                                                    <!--begin::Destination-->
                                                    <div class="d-flex align-items-center fw-bold d-none" data-select2-id="select2-data-135-ep44">
                                                        <!--begin::Label-->
                                                        <div class="text-gray-500 fs-7 me-2">Cateogry</div>
                                                         <select class="form-select form-select-transparent" 
                                                        data-control="select2" data-hide-search="true" id="category_filter" name="category_filter" data-dropdown-css-class="w-150px" data-placeholder="Select an option">
                                                            <option></option>
                                                           <option value="a" data-select2-id="select2-data-137-gtiy">Category A</option>
                                                            <option value="b" data-select2-id="select2-data-138-smpc">Category A</option>
                                                        </select>

                                                    </div>
                                                    <!--end::Destination-->

                                                    <!--begin::Status-->
                                                    <div class="d-flex align-items-center fw-bold d-none" data-select2-id="select2-data-119-40rt">
                                                        <!--begin::Label-->
                                                        <div class="text-gray-500 fs-7 me-2">Status</div>
                                                        <!--end::Label-->

                                                        <!--begin::Select-->
                                                        <select class="form-select form-select-transparent" 
                                                        data-control="select2" data-hide-search="true" data-dropdown-css-class="w-150px" data-placeholder="Select an option">
                                                            <option></option>
                                                            <option value="Shipped" data-select2-id="select2-data-130-c79k">Shipped</option>
                                                            <option value="Confirmed" data-select2-id="select2-data-131-93sn">Confirmed</option>
                                                            <option value="Rejected" data-select2-id="select2-data-132-rz3l">Rejected</option>
                                                            <option value="Pending" data-select2-id="select2-data-133-vcmy">Pending</option>
                                                        </select>
                                                    </div>
                                                    <!--end::Status-->

                                                    <!--begin::Search-->
                                                    <div class="position-relative my-1">
                                                        <i class="ki-outline ki-magnifier fs-2 position-absolute top-50 translate-middle-y ms-4"></i>
                                                        <input type="text" data-kt-table-widget-4="search" class="form-control w-150px fs-7 ps-12" placeholder="Search">
                                                    </div>
                                                    <!--end::Search-->
                                                </div>
                                                <!--begin::Filters-->
                                            </div>
                                            <!--end::Actions-->
                                        </div>
                                        <!--end::Card header-->

                                        <!--begin::Card body-->
                                        <div class="card-body pt-2">
                                            <!--begin::Table-->
                                            <div id="kt_table_widget_4_table_wrapper" class="dt-container dt-bootstrap5 dt-empty-footer">
                                                <div id="" class="table-responsive">
                                                    <table id="kt_table_widget_order_table" class="table align-middle table-row-dashed fs-6 gy-5">
                                                    <thead>
                                                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                                        <th>S/No</th>
                                                        <th>Order ID</th>
                                                        <th>Created</th>
                                                        <th>Total</th>
                                                        <th>Commission ( PV )</th>
                                                        <th>Status</th>
                                                        <th>Action</th>
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







   
                                    </div>

                                </div>


                            </div>
                    </div>
                    <!--end::Wrapper-->

                                   </div>
                                 
                                </div>
                            </div>
                                <?php $this->load->view('user/layout/user_footer');?>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
            <i class="ki-outline ki-arrow-up"></i></div>

        <?php $this->load->view('user/layout/user_script');?>
        <script>
        const base_url = '<?php echo base_url();?>';
        const agent_id = '<?php echo $user_id;?>';
        </script>
        <script src="<?php echo base_url();?>/assets/admin/js/custom/authentication/sign-in/my-order.js?ver=2.9"></script>

    </body>

    </html>