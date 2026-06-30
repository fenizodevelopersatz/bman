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
                                        <h2 class="fs-2x fw-bold mb-0">Referral Overview</h2>
                                            <!--end::Title-->

                                            <!--begin::Description-->
                                            <p class="text-gray-500 fs-4 fw-semibold py-7">
                                                Get a detailed breakdown of your Referral earnings and bonuses across all activities within the platform.
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
                                                        <?php echo $total_direct_commission; ?></span>
                                                    </div>
                                                    </div>
                                                    <span class="fw-bold fs-7 text-gray-500">Total Direct Bonus</span>
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
                                                        <?php echo $getDirectTotal; ?></span>
                                                    </div>
                                                    </div>
                                                    <span class="fw-bold fs-7 text-gray-500">Total Direct Members</span>
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
                                                        <?php echo $level_token_currency; ?></span>
                                                    </div>
                                                    </div>
                                                    <span class="fw-bold fs-7 text-gray-500">Total Level Commission</span>
                                                </div>
                                                    <!--end::Wrapper-->
                                                </div>
                                                <!--end::Card body-->
                                            </div>
                                            <!--end::Card widget 28-->
                                        </div>
                                        <!--end::Col-->

                                            <!--begin::Col-->
                                       


                                    </div>
                                      
                                    </div>
                                    <!--end::Card body-->
                                </div>
                                <!--end::Card-->



                                
                            </div>


    
                                 <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="card mb-5 mb-xxl-8">

                                                        <div class="card-header mb-4 border-transparent">
                                                            <h3 class="anchor fw-bold "><?php echo "Referral Transaction";?></h3>
                                                        </div>

                                                        <div class="card-body pt-9 pb-9">

                                                        <div class="d-flex flex-stack mb-5">

                                                            <div class="col-lg-9 mb-4">
                                                                <div class="col-lg-12">
                                                                    <div class="row gap-5">
                                                                        <div class="col-lg-4  mb-2 me-5">
                                                                            <input type="date" id="cl_from_date" class="form-control form-control-solid w-250px ps-15 me-3" placeholder="Start Date">
                                                                        </div>
                                                                        <div class="col-lg-4  mb-2">
                                                                            <input type="date" id="cl_to_date" class="form-control form-control-solid w-250px ps-15  me-3" placeholder="End Date">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="col-lg-3 mb-4">
                                                                <div class="d-flex align-items-center position-relative my-1 gap-7">

                                                                    <select class="form-select me-3" data-control="select2" id="call_status" data-placeholder="Filter Types" multiple>
                                                                        <option></option>
                                                                        <option value="direct_commission">Direct Bonus</option>
                                                                        <option value="level_commission">Level Bonus</option>
                                                                    </select>

                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row justify-content-between das_ro">

                                                            <div class="border border-gray-300 border-dashed rounded col-lg-5 py-3 px-4 mb-3">

                                                            <div class="d-flex align-items-center mb-2">   
                                                            <span class="fs-1 fw-semibold text-gray-500 me-1 mt-n1"><?php echo $currency_info->currency_symbol; ?></span>
                                                            <span class="fs-3x fw-bold text-gray-800 me-2 lh-1 ls-n2 counted"  id="icd" data-kt-initialized="1">0.00</span>
                                                            </div>
                                                            <div class="d-flex flex-stack">
                                                            <div class="fw-semibold fs-6 text-success">Total Site Currency </div>
                                                            </div>
                                                            </div>

                                                            <div class="border border-gray-300 border-dashed rounded col-lg-5 py-3 px-4 mb-3">
                                                            <div class="d-flex align-items-center mb-2">   
                                                            <span class="fs-1 fw-semibold text-gray-500 me-1 mt-n1"><?php echo $token_info->currency_symbol; ?></span>
                                                            <span class="fs-3x fw-bold text-gray-800 me-2 lh-1 ls-n2 counted"  id="tcd" data-kt-initialized="1">0.00</span>
                                                            </div>
                                                            <div class="d-flex flex-stack">
                                                            <div class="fw-semibold fs-6 text-info"> Total Site Token  </div>
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
            
        
         $(document).ready(function(){
            var KTDatatablesExample = function () {
                var table;
                var datatable;

                var initDatatable = function () {
                    table = document.querySelector('#kt-client-follow-table');
                    if (!table) return;

                    datatable = $(table).DataTable({
                        searchDelay: 500,
                        processing: true,
                        serverSide: true,
                        order: [[5, 'desc']],
                        stateSave: true,
                        ajax: {
                            url: base_url + "transaction-list",
                            type: "GET",
                            data: function(d) {
                                d.from_date = $('#cl_from_date').val(); 
                                d.to_date = $('#cl_to_date').val(); 
                                d.client_filter = agent_id; 
                                var callStatus = $('#call_status').val();
                                d.call_status = (callStatus && callStatus.length > 0) ? callStatus : ["level_commission", "direct_commission"]
                            }
                        },
                        columns: [
                            { data: 'RecordID' },
                            { data: 'UserInfo' },
                            { data : 'TransactionInfo'},
                            { data: 'CurrencyInfo' },
                            { data: 'Status' },
                        ]
                    });
                }
                
                var handleFilterChange = function () {
                    $('#cl_from_date, #cl_to_date, #client_filter, #call_status').on('change', function () {
                        datatable.ajax.reload(null, false); 
                        loadData();
                    });
                }

                return {
                    init: function () {
                        table = document.querySelector('#kt-client-follow-table');
            
                        if ($.fn.DataTable.isDataTable(table)) {
                            $(table).DataTable().clear().destroy();
                        }
                        
                        if (!table) {
                            return;
                        }
                        
                        initDatatable();
                        handleFilterChange(); 
                    }
                };
            }();

            KTDatatablesExample.init();
            loadData();
            
            function loadData(){

                var from_date = $('#cl_from_date').val(); 
                var to_date = $('#cl_to_date').val(); 
                var client_filter = $('#client_filter').val(); 
                var callStatus = $('#call_status').val();
                call_status = (callStatus && callStatus.length > 0) ? callStatus : ["level_commission", "direct_commission"]

                $.ajax({
                    url: base_url + 'all-transaction-get', 
                    type: 'POST',
                    data: {
                        from_date: from_date,
                        to_date: to_date,
                        client_filter :client_filter,
                        call_status :call_status,
                    },
                    success: function(response) {
                        var data = JSON.parse(response);
                        let totalAmount = typeof data.total_amount === "string" 
                        ? data.total_amount.replace(/,/g, '') 
                        : data.total_amount;

                        let totalTokenAmount = typeof data.total_token_amount === "string" 
                        ? data.total_token_amount.replace(/,/g, '') 
                        : data.total_token_amount;

                        const count1 = new countUp.CountUp("icd", parseFloat(totalAmount) || 0);
                        const count2 = new countUp.CountUp("tcd", parseFloat(totalTokenAmount) || 0);

                        count1.start();
                        count2.start();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching chart data:', error);
                    }
                });
            };

            
        });

        </script>

    </body>

    </html>