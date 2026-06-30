<?php
$token_info = token_info();
$currency_info = currency_info();
?>
<div class="row">

<div class="col-xl-6">
    <div class="card card-xl-stretch mb-xl-8">
        <div class="card-body p-0">
            <div class="px-9 pt-7 card-rounded h-200px  w-100 bg-primary">

                <div class="d-flex flex-stack">
                    <h3 class="m-0 text-white fw-bold fs-3">Package Summary</h3>
                </div>

                <div class="d-flex text-center flex-column text-white pt-8">
                    <span class="fw-semibold fs-7">Total Package</span>
                        <div class="fs-2 fw-bold text-gray-800 mt-3 d-flex justify-content-center">
                        <div class="d-flex align-items-center mb-2 gap-5">   
                        <div class="d-none">
                        <span class="fw-semibold fs-7 text-start text-white ps-0 mt-1 me-1 mt-n1"><?php echo $token_info->currency_symbol; ?></span>
                        <span class="fw-bold fs-2x pt-1 text-white" id="lending_currency" data-kt-initialized="1">0</span>
                        </div>
                        <!-- <span class="fw-semibold text-gray-400"> | </span> -->
                        <div >
                        <span class="fw-semibold fs-7 text-start text-white ps-0 mt-1"><?php echo $currency_info->currency_symbol; ?></span>
                        <span class="fw-bold fs-2x pt-1  text-white" id="lending_token" data-kt-initialized="1">0</span>
                        </div>
                        </div>
                        </div>
                </div>
            </div>

            <div class="card-rounded bg-body mt-n10 position-relative card-px py-15">
                <div class="row g-0 mb-7">
                    <div class="col-lg-12 mx-5 mb-4">
                        <div class="fs-6 text-gray-500">
                            User Online Payment Package 
                        </div>
                        <div class="fs-2 fw-bold text-gray-800 mt-3">
                        <div class="d-flex align-items-center mb-2 gap-5">   
                         <div class="d-none">
                        <span class="fw-semibold fs-7 text-start text-success ps-0 mt-1 me-1 mt-n1"><?php echo $token_info->currency_symbol; ?></span>
                        <span class="fs-2 fw-bold text-gray-800 me-2 lh-1 ls-n2 counted" id="bsc_lending_token" data-kt-initialized="1">0</span>
                        </div>
                        <!-- <span class="fw-semibold text-gray-400"> | </span> -->
                        <div>
                        <span class="fw-semibold fs-7 text-start text-danger ps-0 mt-1"><?php echo $currency_info->currency_symbol; ?></span>
                        <span class="fs-2 fw-bold text-gray-800 me-2 lh-1 ls-n2 counted" id="bsc_lending_currency" data-kt-initialized="1">0</span>
                        </div>
                        </div>
                        </div>
                    </div>

                    <div class="col-lg-12 mx-5 mb-4">
                        <div class="fs-6 text-gray-500">
                            User Wallet Package 
                        </div>
                        <div class="fs-2 fw-bold text-gray-800 mt-3">
                        <div class="d-flex align-items-center mb-2 gap-5">   
                         <div class="d-none">
                        <span class="fw-semibold fs-7 text-start text-success ps-0 mt-1 me-1 mt-n1"><?php echo $token_info->currency_symbol; ?></span>
                        <span class="fs-2 fw-bold text-gray-800 me-2 lh-1 ls-n2 counted" id="wallet_lending_token" data-kt-initialized="1">0</span>
                        </div>
                        <!-- <span class="fw-semibold text-gray-400"> | </span> -->
                        <div>
                        <span class="fw-semibold fs-7 text-start text-danger ps-0 mt-1"><?php echo $currency_info->currency_symbol; ?></span>
                        <span class="fs-2 fw-bold text-gray-800 me-2 lh-1 ls-n2 counted" id="wallet_lending_currency" data-kt-initialized="1">0</span>
                        </div>
                        </div>
                        </div>
                    </div>

                    <div class="col-lg-12 mx-5 mb-4">
                        <div class="fs-6 text-gray-500">
                        Admin Package Purchase 
                        </div>
                        <div class="fs-2 fw-bold text-gray-800 mt-3">
                        <div class="d-flex align-items-center mb-2 gap-5">   
                         <div class="d-none">
                        <span class="fw-semibold fs-7 text-start text-success ps-0 mt-1 me-1 mt-n1"><?php echo $token_info->currency_symbol; ?></span>
                        <span class="fs-2 fw-bold text-gray-800 me-2 lh-1 ls-n2 counted" id="admin_lending_token" data-kt-initialized="1">0</span>
                        </div>
                        <!-- <span class="fw-semibold text-gray-400"> | </span> -->
                        <div>
                        <span class="fw-semibold fs-7 text-start text-danger ps-0 mt-1"><?php echo $currency_info->currency_symbol; ?></span>
                        <span class="fs-2 fw-bold text-gray-800 me-2 lh-1 ls-n2 counted" id="admin_lending_currency" data-kt-initialized="1">0</span>
                        </div>
                        </div>
                        </div>
                    </div>


                    <div class="col-lg-12 mx-5 mb-4">
                        <div class="fs-6 text-gray-500">
                        Total Active Package  
                        </div>
                        <div class="fs-2 fw-bold text-gray-800 mt-3">
                        <div class="d-flex align-items-center mb-2 gap-5">   
                         <div>
                        <span class="fs-2 fw-bold text-success-800 me-2 lh-1 ls-n2 counted text-success" id="active_lending" data-kt-initialized="1">0</span>
                        </div>
                        </div>
                        </div>
                    </div>


                    <div class="col mx-5 mb-4">
                        <div class="fs-6 text-gray-500">
                        Total Mature Package 
                        </div>
                        <div class="fs-2 fw-bold text-gray-800 mt-3">
                        <div class="d-flex align-items-center mb-2 gap-5">   
                         <div>
                        <span class="fs-2 fw-bold text-danger-800 me-2 lh-1 ls-n2 counted text-danger" id="inactive_lending" data-kt-initialized="1">0</span>
                        </div>
                        </div>
                        </div>
                    </div>

                    
                    <div class="col mx-5 mb-4">
                        <div class="fs-6 text-gray-500">
                        Total Reinvestment Lending 
                        </div>
                        <div class="fs-2 fw-bold text-gray-800 mt-3">
                        <div class="d-flex align-items-center mb-2 gap-5">   
                         <div>
                        <span class="fs-2 fw-bold text-warning-800 me-2 lh-1 ls-n2 counted text-warning" id="reinvest_lending" data-kt-initialized="1">0</span>
                        </div>
                        </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>


    <div class="col-lg-12 col-xl-12 col-xxl-6 mb-5 mb-xl-0 mt-4">
        <div class="row">
            <div class="col-lg-12">
                <div class="card mb-5 mb-xxl-8 lozad">
                    <div class="card-toolbar">
                        <!--begin::Menu-->
                        <button class="btn btn-icon btn-color-gray-500 btn-active-color-primary justify-content-end" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end" data-kt-menu-overflow="true">
                            <i class="ki-duotone ki-dots-square fs-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                        </button>

                        <!--begin::Menu 2-->
                        <div class="menu menu-sub menu-sub-dropdown 
        menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary 
        fw-semibold w-200px" data-kt-menu="true">

                            <div class="menu-item px-3">
                                <div class="menu-content fs-6 text-gray-900 fw-bold px-3 py-4">Quick Search</div>
                            </div>

                            <div class="separator mb-3 opacity-75"></div>

                            <div class="menu-item px-3">
                                <a href="#" id="daily_d" class="menu-link px-3">
        Daily
        </a>
                            </div>
                            <div class="menu-item px-3">
                                <a href="#" id="weekly_d" class="menu-link px-3">
        Weekly
        </a>
                            </div>
                            <div class="menu-item px-3">
                                <a href="#" id="monthly_d" class="menu-link px-3">
        Monthly
        </a>
                            </div>
                            <div class="menu-item px-3">
                                <a href="#" id="yearly_d" class="menu-link px-3">
        Yearly
        </a>
                            </div>
                            <div class="menu-item px-3">
                                <input type="date" id="from_date_d" class="form-control" placeholder="From Date">
                            </div>
                            <div class="menu-item px-3 mt-3 mb-4">
                                <input type="date" id="to_date_d" class="form-control" placeholder="To Date">
                            </div>
                        </div>
                    </div>

                    <div id="kt_chartjs_1" class="min-h-auto ps-4 pe-6" style="height: 580px"></div>

                    <div class="row ">
                        <div class="col-lg-12">
                            <div style="padding:0px 40px 20px">
                                <style>
                                    .das_ro .col-lg-5{
                                    width:48%
                                    }
                                    .das_and{
                                    padding:0px 10px;
                                    opacity:0.5
                                    }
                                </style>
                                <div class="row justify-content-between das_ro">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>


    </div>


<div class="col-xl-6">
    <div class="card card-xl-stretch mb-xl-8">
        <div class="card-body p-0">
            <div class="px-9 pt-7 card-rounded h-200px  w-100 bg-info">

                <div class="d-flex flex-stack">
                    <h3 class="m-0 text-white fw-bold fs-3">Commission Summary</h3>
                </div>

                <div class="d-flex text-center flex-column text-white pt-8">
                    <span class="fw-semibold fs-7">Total Commission</span>
                        <div class="fs-2 fw-bold text-gray-800 mt-3 d-flex justify-content-center">
                        <div class="d-flex align-items-center mb-2 gap-5">   
                        <div class="d-none">
                        <span class="fw-semibold fs-7 text-start text-white ps-0 mt-1 me-1 mt-n1"><?php echo $token_info->currency_symbol; ?></span>
                        <span class="fw-bold fs-2x pt-1 text-white" id="commission_token" data-kt-initialized="1">0</span>
                        </div>
                        <!-- <span class="fw-semibold text-gray-400"> | </span> -->
                        <div>
                        <span class="fw-semibold fs-7 text-start text-white ps-0 mt-1"><?php echo $currency_info->currency_symbol; ?></span>
                        <span class="fw-bold fs-2x pt-1  text-white" id="commission_currency" data-kt-initialized="1">0</span>
                        </div>
                        </div>
                        </div>
                </div>
            </div>

            <div class="card-rounded bg-body mt-n10 position-relative card-px py-15">
                <div class="row g-0 mb-7">
                    <div class="col-lg-12 mx-5 mb-4">
                        <div class="fs-6 text-gray-500">
                            Direct Commission
                        </div>
                        <div class="fs-2 fw-bold text-gray-800 mt-3">
                        <div class="d-flex align-items-center mb-2 gap-5">   
                         <div class="d-none">
                        <span class="fw-semibold fs-7 text-start text-success ps-0 mt-1 me-1 mt-n1"><?php echo $token_info->currency_symbol; ?></span>
                        <span class="fs-2 fw-bold text-gray-800 me-2 lh-1 ls-n2 counted" id="direct_token" data-kt-initialized="1">0</span>
                        </div>
                        <!-- <span class="fw-semibold text-gray-400"> | </span> -->
                        <div>
                        <span class="fw-semibold fs-7 text-start text-danger ps-0 mt-1"><?php echo $currency_info->currency_symbol; ?></span>
                        <span class="fs-2 fw-bold text-gray-800 me-2 lh-1 ls-n2 counted" id="direct_currency" data-kt-initialized="1">0</span>
                        </div>
                        </div>
                        </div>
                    </div>

                    <div class="col-lg-12 mx-5 mb-4">
                        <div class="fs-6 text-gray-500">
                            Level 2 
                        </div>
                        <div class="fs-2 fw-bold text-gray-800 mt-3">
                        <div class="d-flex align-items-center mb-2 gap-5">   
                         <div class="d-none">
                        <span class="fw-semibold fs-7 text-start text-success ps-0 mt-1 me-1 mt-n1"><?php echo $token_info->currency_symbol; ?></span>
                        <span class="fs-2 fw-bold text-gray-800 me-2 lh-1 ls-n2 counted" id="level_2_token" data-kt-initialized="1">0</span>
                        </div>
                        <!-- <span class="fw-semibold text-gray-400"> | </span> -->
                        <div>
                        <span class="fw-semibold fs-7 text-start text-danger ps-0 mt-1"><?php echo $currency_info->currency_symbol; ?></span>
                        <span class="fs-2 fw-bold text-gray-800 me-2 lh-1 ls-n2 counted" id="level_2_currency" data-kt-initialized="1">0</span>
                        </div>
                        </div>
                        </div>
                    </div>

                    <div class="col-lg-12 mx-5 mb-4">
                        <div class="fs-6 text-gray-500">
                        Level 3
                        </div>
                        <div class="fs-2 fw-bold text-gray-800 mt-3">
                        <div class="d-flex align-items-center mb-2 gap-5">   
                         <div class="d-none">
                        <span class="fw-semibold fs-7 text-start text-success ps-0 mt-1 me-1 mt-n1"><?php echo $token_info->currency_symbol; ?></span>
                        <span class="fs-2 fw-bold text-gray-800 me-2 lh-1 ls-n2 counted" id="level_3_token" data-kt-initialized="1">0</span>
                        </div>
                        <!-- <span class="fw-semibold text-gray-400"> | </span> -->
                        <div>
                        <span class="fw-semibold fs-7 text-start text-danger ps-0 mt-1"><?php echo $currency_info->currency_symbol; ?></span>
                        <span class="fs-2 fw-bold text-gray-800 me-2 lh-1 ls-n2 counted" id="level_3_currency" data-kt-initialized="1">0</span>
                        </div>
                        </div>
                        </div>
                    </div>

                    
                    <div class="col-lg-12 mx-5 mb-4">
                        <div class="fs-6 text-gray-500">
                        Level 4
                        </div>
                        <div class="fs-2 fw-bold text-gray-800 mt-3">
                        <div class="d-flex align-items-center mb-2 gap-5">   
                         <div class="d-none">
                        <span class="fw-semibold fs-7 text-start text-success ps-0 mt-1 me-1 mt-n1"><?php echo $token_info->currency_symbol; ?></span>
                        <span class="fs-2 fw-bold text-gray-800 me-2 lh-1 ls-n2 counted" id="level_4_token" data-kt-initialized="1">0</span>
                        </div>
                        <!-- <span class="fw-semibold text-gray-400"> | </span> -->
                        <div>
                        <span class="fw-semibold fs-7 text-start text-danger ps-0 mt-1"><?php echo $currency_info->currency_symbol; ?></span>
                        <span class="fs-2 fw-bold text-gray-800 me-2 lh-1 ls-n2 counted" id="level_4_currency" data-kt-initialized="1">0</span>
                        </div>
                        </div>
                        </div>
                    </div>

                    
                    <div class="col-lg-12 mx-5 mb-4">
                        <div class="fs-6 text-gray-500">
                        Level 5
                        </div>
                        <div class="fs-2 fw-bold text-gray-800 mt-3">
                        <div class="d-flex align-items-center mb-2 gap-5">   
                         <div class="d-none">
                        <span class="fw-semibold fs-7 text-start text-success ps-0 mt-1 me-1 mt-n1"><?php echo $token_info->currency_symbol; ?></span>
                        <span class="fs-2 fw-bold text-gray-800 me-2 lh-1 ls-n2 counted" id="level_5_token" data-kt-initialized="1">0</span>
                        </div>
                        <!-- <span class="fw-semibold text-gray-400"> | </span> -->
                        <div>
                        <span class="fw-semibold fs-7 text-start text-danger ps-0 mt-1"><?php echo $currency_info->currency_symbol; ?></span>
                        <span class="fs-2 fw-bold text-gray-800 me-2 lh-1 ls-n2 counted" id="level_5_currency" data-kt-initialized="1">0</span>
                        </div>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
  
    </div>
</div>


<div class="col-xl-6">
    <div class="card card-xl-stretch mb-xl-8">
        <div class="card-body p-0">

            <div class="px-9 pt-7 card-rounded h-200px  w-100 bg-danger">
                <div class="d-flex flex-stack">
                    <h3 class="m-0 text-white fw-bold fs-3">Rank Summary</h3>
                </div>
                <div class="d-flex text-center flex-column text-white pt-8">
                    <span class="fw-semibold fs-7">Total Rank Commission</span>
                        <div class="fs-2 fw-bold text-gray-800 mt-3 d-flex justify-content-center">
                        <div class="d-flex align-items-center mb-2 gap-5">   
                        <div class="d-none">
                        <span class="fw-semibold fs-7 text-start text-white ps-0 mt-1 me-1 mt-n1"><?php echo $token_info->currency_symbol; ?></span>
                        <span class="fw-bold fs-2x pt-1 text-white" id="rank_token" data-kt-initialized="1">0</span>
                        </div>
                        <div>
                        <span class="fw-semibold fs-7 text-start text-white ps-0 mt-1"><?php echo $currency_info->currency_symbol; ?></span>
                        <span class="fw-bold fs-2x pt-1  text-white" id="rank_currency" data-kt-initialized="1">0</span>
                        </div>
                        </div>
                        </div>
                </div>
            </div>

            <div class="card-rounded bg-body mt-n10 position-relative card-px py-15">
                <div class="row g-0 mb-7">

                    <div class="col-lg-12 mx-5 mb-4">
                        <div class="fs-6 text-gray-500">
                            Rank Achived Users
                        </div>
                        <div class="fs-2 fw-bold text-gray-800 mt-3">
                        <div class="d-flex align-items-center mb-2 gap-5">   
                         <div>
                        <span class="fs-2 fw-bold text-gray-800 me-2 lh-1 ls-n2 counted" id="rank_achived" data-kt-initialized="1">0</span>
                        </div>
                        </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>


     <div class="card card-xl-stretch mb-xl-8">
        <div class="card-body p-0">
            <div class="px-9 pt-7 card-rounded h-200px  w-100 bg-success">

                <div class="d-flex flex-stack">
                    <h3 class="m-0 text-white fw-bold fs-3">Profit Summary</h3>
                </div>

                <div class="d-flex text-center flex-column text-white pt-8">
                    <span class="fw-semibold fs-7">Total  Profit </span>
                        <div class="fs-2 fw-bold text-gray-800 mt-3 d-flex justify-content-center">
                        <div class="d-flex align-items-center mb-2 gap-5">   
                        <div class="d-none">
                        <span class="fw-semibold fs-7 text-start text-white ps-0 mt-1 me-1 mt-n1"><?php echo $token_info->currency_symbol; ?></span>
                        <span class="fw-bold fs-2x pt-1 text-white" id="profit_token" data-kt-initialized="1">0</span>
                        </div>
                        <!-- <span class="fw-semibold text-gray-400"> | </span> -->
                        <div>
                        <span class="fw-semibold fs-7 text-start text-white ps-0 mt-1"><?php echo $currency_info->currency_symbol; ?></span>
                        <span class="fw-bold fs-2x pt-1  text-white" id="profit_currency" data-kt-initialized="1">0</span>
                        </div>
                        </div>
                        </div>
                </div>
            </div>

            <div class="card-rounded bg-body mt-n10 position-relative card-px py-15">
                <div class="row g-0 mb-7">
                    <div class="col-lg-12 mx-5 mb-4">
                        <div class="fs-6 text-gray-500">
                            Total Package Profit
                        </div>
                        <div class="fs-2 fw-bold text-gray-800 mt-3">
                        <div class="d-flex align-items-center mb-2 gap-5">   
                         <div class="d-none">
                        <span class="fw-semibold fs-7 text-start text-success ps-0 mt-1 me-1 mt-n1"><?php echo $token_info->currency_symbol; ?></span>
                        <span class="fs-2 fw-bold text-gray-800 me-2 lh-1 ls-n2 counted" id="daily_token" data-kt-initialized="1">0</span>
                        </div>
                        <!-- <span class="fw-semibold text-gray-400"> | </span> -->
                        <div>
                        <span class="fw-semibold fs-7 text-start text-danger ps-0 mt-1"><?php echo $currency_info->currency_symbol; ?></span>
                        <span class="fs-2 fw-bold text-gray-800 me-2 lh-1 ls-n2 counted" id="daily_currency" data-kt-initialized="1">0</span>
                        </div>
                        </div>
                        </div>
                    </div>

                    <div class="col-lg-12 mx-5 mb-4">
                        <div class="fs-6 text-gray-500">
                          Total Binary Bonus 
                        </div>
                        <div class="fs-2 fw-bold text-gray-800 mt-3">
                        <div class="d-flex align-items-center mb-2 gap-5">   
                         <div class="d-none">
                        <span class="fw-semibold fs-7 text-start text-success ps-0 mt-1 me-1 mt-n1"><?php echo $token_info->currency_symbol; ?></span>
                        <span class="fs-2 fw-bold text-gray-800 me-2 lh-1 ls-n2 counted" id="binary_token" data-kt-initialized="1">0</span>
                        </div>
                        <!-- <span class="fw-semibold text-gray-400"> | </span> -->
                        <div>
                        <span class="fw-semibold fs-7 text-start text-danger ps-0 mt-1"><?php echo $currency_info->currency_symbol; ?></span>
                        <span class="fs-2 fw-bold text-gray-800 me-2 lh-1 ls-n2 counted" id="binary_currency" data-kt-initialized="1">0</span>
                        </div>
                        </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>


</div>


<div class="col-xl-6">
   
</div>

</div>

