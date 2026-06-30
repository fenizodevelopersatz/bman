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
                                                        Dashboard                         
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
                                <div class="card">
                                    <!--begin::Card body-->
                                    <div class="card-body">
                                        <!--begin::Heading-->
                                        <div class="card-px text-center pt-15 pb-15">
                                            <!--begin::Title-->
                                        <h2 class="fs-2x fw-bold mb-0">Team Overview</h2>
                                            <!--end::Title-->

                                            <!--begin::Description-->
                                            <p class="text-gray-500 fs-4 fw-semibold py-7">
                                                Get a detailed breakdown of your Team earnings and bonuses across all activities within the platform.
                                            </p>
                                            <!--end::Description-->
                                        </div>
                                        <!--end::Heading-->

                                    <div class="row">
                                        
                                                <!--begin::Col-->
                                        <div class="col-md-6 mb-xl-10">
                                            <!--begin::Card widget 28-->
                                            <div class="card card-flush ">
                                                <!--begin::Header-->
                                                <div class="card-header pt-7">
                                                    <!--begin::Card title-->
                                                    <div class="card-title flex-stack flex-row-fluid">
                                                        <!--begin::Symbol-->
                                                        <div class="symbol symbol-45px me-5">
                                                                                <span class="symbol-label bg-light-info">
                                                    <i class="fa-solid fa-wallet fs-2x text-primary"></i>
                                                       </span>
                                                                            </div>
                                                        <!--end::Symbol-->
                                                     
                                                    </div>
                                                    <!--end::Header-->
                                                </div>
                                                <!--end::Card title-->

                                                <!--begin::Card body-->
                                                <div class="card-body d-flex align-items-end">
                                                    <!--begin::Wrapper-->
                                                <div class="d-flex flex-column">
                                                    <div class="d-flex align-items-center mb-2 gap-5">   
                                                    <div>
                                                    <span class="fs-4 fw-bold text-gray-800 me-2 lh-1 ls-n2 counted" id="user_token_balance" data-kt-initialized="1">
                                                        <?php echo $TotalLending_amt; ?></span>
                                                    </div>
                                                    </div>
                                                    <span class="fw-bold fs-7 text-gray-500">Total Team Lending</span>
                                                </div>
                                                    <!--end::Wrapper-->
                                                </div>
                                                <!--end::Card body-->
                                            </div>
                                            <!--end::Card widget 28-->
                                        </div>
                                        <!--end::Col-->

                                            <!--begin::Col-->
                                        <div class="col-md-6 mb-xl-10">
                                            <!--begin::Card widget 28-->
                                            <div class="card card-flush ">
                                                <!--begin::Header-->
                                                <div class="card-header pt-7">
                                                    <!--begin::Card title-->
                                                    <div class="card-title flex-stack flex-row-fluid">
                                                        <!--begin::Symbol-->
                                                        <div class="symbol symbol-45px me-5">
                                                                                <span class="symbol-label bg-light-info">
                                                    <i class="fa-solid fa-money-bill-trend-up fs-2x text-primary"></i>
                                                       </span>
                                                                            </div>
                                                        <!--end::Symbol-->
                                                     
                                                    </div>
                                                    <!--end::Header-->
                                                </div>
                                                <!--end::Card title-->

                                                <!--begin::Card body-->
                                                <div class="card-body d-flex align-items-end">
                                                    <!--begin::Wrapper-->
                                                <div class="d-flex flex-column">
                                                    <div class="d-flex align-items-center mb-2 gap-5">   
                                                    <div>
                                                    <span class="fs-4 fw-bold text-gray-800 me-2 lh-1 ls-n2 counted" id="user_token_balance" data-kt-initialized="1">
                                                        <?php echo $TotalLending; ?></span>
                                                    </div>
                                                    </div>
                                                    <span class="fw-bold fs-7 text-gray-500">Total Team Lending Member</span>
                                                </div>
                                                    <!--end::Wrapper-->
                                                </div>
                                                <!--end::Card body-->
                                            </div>
                                            <!--end::Card widget 28-->
                                        </div>
                                        <!--end::Col-->


                                                     <!--begin::Col-->
                                        <div class="col-md-12 mb-xl-10">
                                            <!--begin::Card widget 28-->
                                            <div class="card card-flush ">
                                                <!--begin::Header-->
                                                <div class="card-header pt-7">
                                                    <!--begin::Card title-->
                                                    <div class="card-title flex-stack flex-row-fluid">
                                                        <!--begin::Symbol-->
                                                        <div class="symbol symbol-45px me-5">
                                                                                <span class="symbol-label bg-light-info">
                                                    <i class="fa-solid fa-chart-simple fs-2x text-primary"></i>
                                                       </span>
                                                                            </div>
                                                        <!--end::Symbol-->
                                                     
                                                    </div>
                                                    <!--end::Header-->
                                                </div>
                                                <!--end::Card title-->

                                                <!--begin::Card body-->
                                                <div class="card-body d-flex align-items-end">
                                                    <!--begin::Wrapper-->
                                                <div class="d-flex flex-column">
                                                    <div class="d-flex align-items-center mb-2 gap-5">   
                                                    <div>
                                                    <span class="fs-4 fw-bold text-gray-800 me-2 lh-1 ls-n2 counted" id="user_token_balance" data-kt-initialized="1">
                                                        <?php echo $inActiveUsers; ?></span>
                                                    </div>
                                                    </div>
                                                    <span class="fw-bold fs-7 text-gray-500">Invactive members</span>
                                                </div>
                                                    <!--end::Wrapper-->
                                                </div>
                                                <!--end::Card body-->
                                            </div>
                                            <!--end::Card widget 28-->
                                        </div>
                                        <!--end::Col-->


                                    </div>
                                      
                                    </div>
                                    <!--end::Card body-->
                                </div>
                                <!--end::Card-->



                                
                            </div>




                            <div class="card mb-5 mb-xl-8">
    <!--begin::Header-->
    <div class="card-header border-0 pt-5">
        <h3 class="card-title align-items-start flex-column">
			<span class="card-label fw-bold fs-3 mb-1">Team Lending History</span>
			<span class="text-muted mt-1 fw-semibold fs-7">View All</span>
		</h3>
    </div>
    <!--end::Header-->
    <!--begin::Body-->
    <div class="card-body pt-3">
        <!--begin::Table container-->
        <div class="table-responsive">
            <!--begin::Table-->
            <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                <!--begin::Table head-->
                <thead>
                    <tr class="border-0">
                        <th class="p-0 "></th>
                        <th class="p-0 min-w-150px"></th>
                        <th class="p-0 min-w-200px"></th>
                        <th class="p-0 min-w-150px"></th>
                        <th class="p-0 min-w-100px text-end"></th>
                    </tr>
                </thead>
                <!--end::Table head-->

                <!--begin::Table body-->
                <tbody>
                    <?php if (!empty($Allhistory)) {
                        foreach ($Allhistory as $row) { ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <!-- User Info -->
                                        <div class="d-flex justify-content-start flex-column">
                                            <span class="text-gray-900 fw-bold mb-1 fs-6"><?php echo htmlspecialchars($row['sponsor_username']); ?></span>
                                            <span class="text-muted d-block fs-7"><strong>ID:</strong> <?php echo htmlspecialchars($row['referral_id']); ?></span>
                                        </div>
                                    </div>
                                </td>

                                <td class="text-start">
                                    <span class="fw-semibold d-block fs-6"><?php echo htmlspecialchars($row['Amount']); ?></span>
                                    <span class="text-muted d-block fs-7"><?php echo htmlspecialchars($row['description']); ?></span>
                                </td>

                                <td class="text-start">
                                    <span class="text-gray-900 fw-bold fs-6"><?php echo htmlspecialchars($row['time']); ?></span>
                                </td>

                                <td class="text-start">
                                    <span class="badge badge-light-primary"><?php echo htmlspecialchars($row['displayTime']); ?></span>
                                </td>

                                <td class="text-end">
                                    <span class="btn btn-sm btn-light-info">Mining</span>
                                </td>
                            </tr>
                    <?php }
                    } else { ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No history found</td>
                        </tr>
                    <?php } ?>
                </tbody>

                <!--end::Table body-->
            </table>
            <!--end::Table-->
        </div>
        <!--end::Table container-->
    </div>
    <!--begin::Body-->
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
            
        </script>

    </body>

    </html>