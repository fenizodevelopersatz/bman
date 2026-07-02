<?php $this->load->view('admin/Layout/common_style');?>

    <link href="<?php echo base_url();?>/assets/admin/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css">
    <link href="<?php echo base_url();?>/assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.css" rel="stylesheet" type="text/css">
    <link href="<?php echo base_url();?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />

    <style>
        .h-md-40{
            min-height:42%;
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
                                                        Admin                         
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




                                    <!--begin::Content-->
                                    <div id="kt_app_content" class="app-content  flex-column-fluid mt-10">

                                        <!--begin::Content container-->
                                        <div id="kt_app_content_container" class="app-container  container-xxl ">

                                            
                                            <?php $this->load->view('notification'); ?>


    <?php
    // ---- User Profile (proposal §1): all personal fields, read-only ----
    $p = isset($profile) ? $profile : null;
    $full_name = $p ? trim(($p->first_name ?? '').' '.($p->last_name ?? '')) : '';
    if ($p && $full_name === '') $full_name = $p->name ?: $p->username;
    $dash = '<span class="text-muted">—</span>';
    $val = function ($v) use ($dash) {
        $v = is_string($v) ? trim($v) : $v;
        return ($v === null || $v === '' ) ? $dash : html_escape($v);
    };
    $status_badge = '';
    if ($p) {
        $status_badge = ((int)$p->status === 1)
            ? '<span class="badge badge-light-success">Active</span>'
            : '<span class="badge badge-light-danger">Inactive</span>';
    }
    if ($p):
    ?>
    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-5 mb-xxl-8">
                <div class="card-header border-transparent pt-6">
                    <h3 class="card-title fw-bold">Member Profile</h3>
                    <div class="card-toolbar gap-2">
                        <?php echo $status_badge; ?>
                        <span class="badge badge-light-primary">KYC: <?php echo strtoupper(html_escape($p->kyc_status)); ?></span>
                    </div>
                </div>
                <div class="card-body pt-4 pb-8">
                    <div class="row g-6">
                        <div class="col-md-4"><div class="text-muted fs-8 text-uppercase">Full Name</div><div class="fw-bold fs-6"><?php echo $val($full_name); ?></div></div>
                        <div class="col-md-4"><div class="text-muted fs-8 text-uppercase">Email ID</div><div class="fw-bold fs-6"><?php echo $val($p->email); ?></div></div>
                        <div class="col-md-4"><div class="text-muted fs-8 text-uppercase">Mobile Number</div><div class="fw-bold fs-6"><?php echo $val($p->contact); ?></div></div>

                        <div class="col-md-4"><div class="text-muted fs-8 text-uppercase">Gender</div><div class="fw-bold fs-6"><?php echo $val($p->gender ? ucfirst($p->gender) : ''); ?></div></div>
                        <div class="col-md-4"><div class="text-muted fs-8 text-uppercase">Date of Birth</div><div class="fw-bold fs-6"><?php echo $val($p->dob); ?></div></div>
                        <div class="col-md-4"><div class="text-muted fs-8 text-uppercase">Username / Referral ID</div><div class="fw-bold fs-6"><?php echo $val($p->username); ?> <span class="text-muted">/ <?php echo $val($p->referral_id); ?></span></div></div>

                        <div class="col-md-6"><div class="text-muted fs-8 text-uppercase">Address Line 1</div><div class="fw-bold fs-6"><?php echo $val($p->address); ?></div></div>
                        <div class="col-md-6"><div class="text-muted fs-8 text-uppercase">Address Line 2</div><div class="fw-bold fs-6"><?php echo $val($p->address_line2 ?? ''); ?></div></div>

                        <div class="col-md-4"><div class="text-muted fs-8 text-uppercase">State</div><div class="fw-bold fs-6"><?php echo $val($p->state ?? ''); ?></div></div>
                        <div class="col-md-4"><div class="text-muted fs-8 text-uppercase">Country</div><div class="fw-bold fs-6"><?php echo $val($p->country); ?></div></div>
                        <div class="col-md-4"><div class="text-muted fs-8 text-uppercase">Pin Code</div><div class="fw-bold fs-6"><?php echo $val($p->zipcode); ?></div></div>

                        <div class="col-md-4"><div class="text-muted fs-8 text-uppercase">Sponsor</div><div class="fw-bold fs-6"><?php
                            echo isset($sponser_row) && $sponser_row
                                ? html_escape($sponser_row->referral_id).' <span class="text-muted">('.html_escape($sponser_row->email).')</span>'
                                : '<span class="text-muted">Main - Admin</span>'; ?></div></div>
                        <div class="col-md-4"><div class="text-muted fs-8 text-uppercase">Placement</div><div class="fw-bold fs-6"><?php echo $val($p->position ? ucfirst($p->position) : ''); ?></div></div>
                        <div class="col-md-4"><div class="text-muted fs-8 text-uppercase">Registered</div><div class="fw-bold fs-6"><?php echo $val($p->register_date); ?></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>


    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-5 mb-xxl-8">

                <div class="card-header mb-4 border-transparent">
                    <h3 class="anchor fw-bold "><?php echo $card_tilte;?></h3>
                </div>

                <div class="card-body pt-9 pb-9">

                                                        
       <div class="d-flex flex-wrap flex-sm-nowrap" id="profile_ini" >

            <div class="me-7 mb-4">
                <div class="symbol symbol-100px symbol-lg-100px symbol-fixed position-relative">
                    <div class="symbol  symbol-45px symbol-circle "><span class="symbol-label  bg-light-danger text-danger fs-6 fw-bolder "><?php echo $first_letter; ?></span></div>
                    <div id="call_status_get" class="position-absolute translate-middle bottom-0 start-100 mb-6  rounded-circle border border-4 border-body h-20px w-20px"></div>
                </div>
            </div>

            <div class="flex-grow-1">

                <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">

                    <div class="d-flex flex-column">
                        <div class="d-flex align-items-center mb-2">
                            <a href="#" class="text-gray-900 text-hover-primary fs-2 fw-bold me-1"><span id="username"><span></a>
                            <a href="#"><i class="ki-duotone ki-verify fs-1 text-primary"><span class="path1"></span><span class="path2"></span></i></a>
                        </div>
                        <div class="d-flex flex-wrap fw-semibold fs-6 mb-4 pe-2">
                            <a href="#" class="d-flex align-items-center text-gray-500 text-hover-primary me-5 mb-2">
                                <i class="ki-duotone ki-profile-circle fs-4 me-1">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span></i> <span id="sponser"><span>
                            </a>
                            <a href="#" class="d-flex align-items-center text-gray-500 text-hover-primary  me-5 mb-2">
                                <i class="ki-duotone ki-sms fs-4 me-1">
                                    <span class="path1"></span><span class="path2"></span></i> <span id="useremail"><span>
                            </a>
                            <a href="#" class="d-flex align-items-center text-gray-500 text-hover-primary  me-5 mb-2">
                                <i class="ki-duotone ki-time fs-4 me-1">
                                    <span class="path1"></span><span class="path2"></span></i> <span id="registerdate"><span>
                            </a>
                        </div>
                    </div>
                </div>
                

                <div class="d-flex flex-wrap flex-stack">
                    <div class="d-flex flex-column flex-grow-1 pe-8">
                        <div class="d-flex flex-wrap">
                           
                            <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                            <div class="d-flex align-items-center">

                            <div class="fs-2 fw-bold counted" data-kt-countup="true" data-kt-countup-value=""  id="my_investment" data-kt-initialized="1"></div>
                            </div>
                            <div class="fw-semibold fs-6 text-gray-500">Active Investment</div>
                            </div>
                            <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                            <div class="d-flex align-items-center">
                            <div class="fs-2 fw-bold counted" data-kt-countup="true" data-kt-countup-value=""  id="left_leg_count" data-kt-initialized="1"></div>
                            </div>
                            <div class="fw-semibold fs-6 text-gray-500">Left Legs Count</div>
                            </div>


                            <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                            <div class="d-flex align-items-center">
                            <div class="fs-2 fw-bold" data-kt-countup="true"  data-kt-initialized="1"><span id="right_leg_count"></span></div>
                            </div>
                            <div class="fw-semibold fs-6 text-gray-500">Right Legs Count</div>
                            </div>

                          

                            <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                            <div class="d-flex align-items-center">
                            <div class="fs-2 fw-bold counted" data-kt-countup="true"
                             data-kt-countup-value="80"
                              data-kt-initialized="1"> <span id="left_leg_investment"></span>
                            </div>
                            </div>
                            
                            <div class="fw-semibold fs-6 text-gray-500">Left Leg Investment</div>
                            </div>

                                    
                            <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                            <div class="d-flex align-items-center">
                            <div class="fs-2 fw-bold counted" data-kt-countup="true"
                             data-kt-countup-value="80"
                              data-kt-initialized="1"> <span id="right_leg_investment"></span></div>
                            </div>
                            <div class="fw-semibold fs-6 text-gray-500">Right Legs Investment</div>
                            </div>
                    </div>
                </div>


                <div class="d-flex flex-wrap flex-stack d-none">
                    <div class="d-flex flex-column flex-grow-1 pe-8">
                        <div class="d-flex flex-wrap">
                           
                            <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                            <div class="d-flex align-items-center">

                            <div class="fs-2 fw-bold counted" data-kt-countup="true" data-kt-countup-value=""  id="my_investment_token" data-kt-initialized="1"></div>
                            </div>
                            <div class="fw-semibold fs-6 text-gray-500">Active Investment</div>
                            </div>
                          

                            <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                            <div class="d-flex align-items-center">
                            <div class="fs-2 fw-bold counted" data-kt-countup="true"
                             data-kt-countup-value="80"
                              data-kt-initialized="1"> <span id="left_leg_investment_token"></span>
                            </div>
                            </div>
                            
                            <div class="fw-semibold fs-6 text-gray-500">Left Leg Investment</div>
                            </div>

                                    
                            <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                            <div class="d-flex align-items-center">
                            <div class="fs-2 fw-bold counted" data-kt-countup="true"
                             data-kt-countup-value="80"
                              data-kt-initialized="1"> <span id="right_leg_investment_token"></span></div>
                            </div>
                            <div class="fw-semibold fs-6 text-gray-500">Right Legs Investment</div>
                            </div>
                        


                    </div>
                </div>



            </div>



        </div>


                                                            
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row g-5 g-xl-8">
                                                <div class="col-xl-3">

                                                    <!--begin::Statistics Widget 5-->
                                                    <a href="#" class="card bg-body hoverable card-xl-stretch mb-xl-8">
                                                        <!--begin::Body-->
                                                        <div class="card-body">
                                                            <i class="ki-duotone ki-chart-simple text-primary fs-2x ms-n1"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>

                                                            <div class="text-gray-900 fw-bold fs-2 mb-2 mt-5">
                                                                <?php echo $wallet_balance; ?>
                                                            </div>

                                                            <div class="fw-semibold text-gray-400">
                                                          <?php echo $currency_info->coin_name;?>   Balance
                                                        </div>
                                                        </div>
                                                        <!--end::Body-->
                                                    </a>
                                                    <!--end::Statistics Widget 5-->
                                                </div>

                                                <div class="col-xl-3 d-none">

                                                    <!--begin::Statistics Widget 5-->
                                                    <a href="#" class="card bg-dark hoverable card-xl-stretch mb-xl-8">
                                                        <!--begin::Body-->
                                                        <div class="card-body">
                                                            <i class="ki-duotone ki-cheque text-gray-100 fs-2x ms-n1"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span><span class="path7"></span></i>

                                                            <div class="text-gray-100 fw-bold fs-2 mb-2 mt-5">
                                                            <?php echo $token_wallet_balance; ?>
                                                            </div>

                                                            <div class="fw-semibold text-gray-100">
                                                            <?php echo $token_info->coin_name;?>  Balance </div>
                                                        </div>
                                                        <!--end::Body-->
                                                    </a>
                                                    <!--end::Statistics Widget 5-->
                                                </div>

                                                <div class="col-xl-3">

                                                    <!--begin::Statistics Widget 5-->
                                                    <a href="#" class="card bg-warning hoverable card-xl-stretch mb-xl-8">
                                                        <!--begin::Body-->
                                                        <div class="card-body">
                                                            <i class="ki-duotone ki-briefcase text-white fs-2x ms-n1"><span class="path1"></span><span class="path2"></span></i>

                                                            <div class="text-white fw-bold fs-2 mb-2 mt-5">
                                                            <?php echo $lending_profit; ?>
                                                            </div>

                                                            <div class="fw-semibold text-white">
                                                                Package Profit </div>
                                                        </div>
                                                        <!--end::Body-->
                                                    </a>
                                                    <!--end::Statistics Widget 5-->
                                                </div>

                                                <div class="col-xl-3">

                                                    <!--begin::Statistics Widget 5-->
                                                    <a href="#" class="card bg-info hoverable card-xl-stretch mb-5 mb-xl-8">
                                                        <!--begin::Body-->
                                                        <div class="card-body">
                                                            <i class="ki-duotone ki-chart-pie-simple text-white fs-2x ms-n1"><span class="path1"></span><span class="path2"></span></i>

                                                            <div class="text-white fw-bold fs-2 mb-2 mt-5">
                                                            <?php echo $direct_commission; ?>
                                                            </div>

                                                            <div class="fw-semibold text-white">
                                                                Direct Commission </div>
                                                        </div>
                                                        <!--end::Body-->
                                                    </a>
                                                    <!--end::Statistics Widget 5-->
                                                </div>
                                            </div>

                                            <div class="row g-5 g-xl-8">
                                                <div class="col-xl-2">

                                                    <!--begin::Statistics Widget 5-->
                                                    <a href="#" class="card bg-body hoverable card-xl-stretch mb-xl-8">
                                                        <!--begin::Body-->
                                                        <div class="card-body">
                                                            <i class="ki-duotone ki-people text-primary fs-2x ms-n1"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                                                            <div class="text-gray-900 fw-bold fs-2 mb-2 mt-5">
                                                                <?php echo $level_commissions; ?>
                                                            </div>

                                                            <div class="fw-semibold text-gray-400">
                                                          Level  Commission
                                                        </div>
                                                        </div>
                                                        <!--end::Body-->
                                                    </a>
                                                    <!--end::Statistics Widget 5-->
                                                </div>


                                                <div class="col-xl-2">
                                                    <!--begin::Statistics Widget 5-->
                                                    <a href="#" class="card bg-light-primary  hoverable card-xl-stretch mb-xl-8">
                                                        <!--begin::Body-->
                                                        <div class="card-body">
                                                        <i class="ki-duotone ki-people text-primary fs-2x ms-n1"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>

                                                            <div class="card-title fw-bold text-primary fs-2 mb-3 d-block mt-5">
                                                            <?php echo $level_commissions_2; ?>
                                                            </div>

                                                            <div class="text-gray-900 fw-bold me-2 ">
                                                                Level 2 </div>
                                                        </div>
                                                        <!--end::Body-->
                                                    </a>
                                                    <!--end::Statistics Widget 5-->
                                                </div>

                                                <div class="col-xl-2">
                                                    <!--begin::Statistics Widget 5-->
                                                    <a href="#" class="card bg-light-primary  hoverable card-xl-stretch mb-xl-8">
                                                        <!--begin::Body-->
                                                        <div class="card-body">
                                                        <i class="ki-duotone ki-people text-primary fs-2x ms-n1"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>

                                                            <div class="card-title fw-bold text-primary fs-2 mb-3 d-block mt-5">
                                                            <?php echo $level_commissions_3; ?>
                                                            </div>

                                                            <div class="text-gray-900 fw-bold me-2 ">
                                                                Level 3 </div>
                                                        </div>
                                                        <!--end::Body-->
                                                    </a>
                                                    <!--end::Statistics Widget 5-->
                                                </div>

                                                
                                                <div class="col-xl-2">
                                                    <!--begin::Statistics Widget 5-->
                                                    <a href="#" class="card bg-light-primary  hoverable card-xl-stretch mb-xl-8">
                                                        <!--begin::Body-->
                                                        <div class="card-body">
                                                        <i class="ki-duotone ki-people text-primary fs-2x ms-n1"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>

                                                            <div class="card-title fw-bold text-primary fs-2 mb-3 d-block mt-5">
                                                            <?php echo $level_commissions_4; ?>
                                                            </div>

                                                            <div class="text-gray-900 fw-bold me-2 ">
                                                                Level 4 </div>
                                                        </div>
                                                        <!--end::Body-->
                                                    </a>
                                                    <!--end::Statistics Widget 5-->
                                                </div>

                                                <div class="col-xl-2">
                                                    <!--begin::Statistics Widget 5-->
                                                    <a href="#" class="card bg-light-primary  hoverable card-xl-stretch mb-xl-8">
                                                        <!--begin::Body-->
                                                        <div class="card-body">
                                                        <i class="ki-duotone ki-people text-primary fs-2x ms-n1"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>

                                                            <div class="card-title fw-bold text-primary fs-2 mb-3 d-block mt-5">
                                                            <?php echo $level_commissions_5; ?>
                                                            </div>

                                                            <div class="text-gray-900 fw-bold me-2 ">
                                                                Level 5 </div>
                                                        </div>
                                                        <!--end::Body-->
                                                    </a>
                                                    <!--end::Statistics Widget 5-->
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="card mb-5 mb-xxl-8">

                                                        <div class="card-header mb-4 border-transparent">
                                                            <h3 class="anchor fw-bold ">User Transaction History</h3>
                                                        </div>

                                                        <div class="card-body pt-9 pb-9">

                                                            <div class="d-flex flex-stack mb-5">


                                                            <div class="col-lg-6 mb-4">
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

                                                            <div class="col-lg-6 mb-4">
                                                                <div class="d-flex align-items-center position-relative my-1 gap-7">

                                                                    <select class="form-select me-3 d-none" data-control="select2" id="client_filter" 
                                                                     data-placeholder="Filter an Members" multiple >
                                                                        <option value="<?php echo $user_id; ?>" selected></option>
                                                                    </select>


                                                                    <select class="form-select me-3" data-control="select2" id="call_status" data-placeholder="Filter Types" multiple>
                                                                        <option></option>

                                                                        <?php
                                                                            $agent_name_get = $this->db->query("SELECT DISTINCT `type` FROM `history` ORDER BY `type` ASC")->result();
                                                                            if(count($agent_name_get)){ foreach($agent_name_get as $agent_name){ ?>
                                                                            <option value="<?php echo $agent_name->type; ?>">
                                                                                <?php echo $agent_name->type; ?>
                                                                            </option>
                                                                            <?php }} ?>

                                                                        
                                                                    </select>

                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row justify-content-between das_ro">

                                                            <div class="border border-gray-300 border-dashed rounded col-lg-5 py-3 px-4 mb-3">

                                                            <div class="d-flex align-items-center mb-2">   
                                                            <span class="fs-1 fw-semibold text-gray-500 me-1 mt-n1"><?php echo $currency_info->currency_symbol; ?></span>
                                                            <span class="fs-3x fw-bold text-gray-800 me-2 lh-1 ls-n2 counted"  id="icd" data-kt-initialized="1">0</span>
                                                            </div>
                                                            <div class="d-flex flex-stack">
                                                            <div class="fw-semibold fs-6 text-success">Total Site Currency </div>
                                                            </div>
                                                            </div>

                                                            <div class="border border-gray-300 border-dashed rounded col-lg-5 py-3 px-4 mb-3 d-none">
                                                            <div class="d-flex align-items-center mb-2">   
                                                            <span class="fs-1 fw-semibold text-gray-500 me-1 mt-n1"><?php echo $token_info->currency_symbol; ?></span>
                                                            <span class="fs-3x fw-bold text-gray-800 me-2 lh-1 ls-n2 counted"  id="tcd" data-kt-initialized="1">0</span>
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



                                    <div class="container">
                                    <h2 class="text-center">Genealogy Tree</h2>
                                    <div id="tree"></div>
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
            <script src="https://balkan.app/js/familytree.js"></script>
            <style>
            #tree {
            width: 100%;
            height: 800px;
            border: 1px solid #ccc;
            }
            </style>
            <script>
            const base_url = '<?php echo base_url();?>';
            const agent_id = '<?php echo $user_id;?>';
            </script>
            <script src="<?php echo base_url();?>/assets/admin/js/custom/authentication/sign-in/view-user.js?ver=2.9"></script>
            <script>
            </script>

             <script>
                document.addEventListener("DOMContentLoaded", function () {
                    fetch("<?= base_url('tree-data/'.$user_id) ?>")
                        .then(response => response.json())
                        .then(data => {
                            let parentMap = {};

                            data.forEach(member => {
                                parentMap[member.id] = member;
                                member.color = (member.position === "Left") ? "#3498db" : "#e74c3c"; 
                            });

                            data.forEach(member => {
                                if (member.pid !== null && parentMap[member.pid]) {
                                    let parent = parentMap[member.pid];

                                    if (member.position === "Left") {
                                        parent.leftChild = member.id;
                                    } else if (member.position === "Right") {
                                        parent.rightChild = member.id;
                                    }
                                }
                            });

                            var options = getOptions();
                            var family = new FamilyTree(document.getElementById('tree'), {
                                mouseScrool: FamilyTree.none,
                                scaleInitial: options.scaleInitial,
                                mode: 'dark',
                                template: 'hugo',
                                enableSearch: true,
                                nodeMenu: {
                                    details: { text: 'Details' }
                                },
                                nodeTreeMenu: true,
                                nodeBinding: {
                                field_0: 'email',    
                                field_1: 'position',    
                                field_2: 'register_date',  
                                img_0: 'photo',
                                background: 'color' 
                                },
                                editForm: {
                                    titleBinding: "name",
                                    photoBinding: "photo",
                                    generateElementsFromFields: false,
                                    elements: [
                                        { type: 'textbox', label: 'Full Name', binding: 'name' },
                                        { type: 'textbox', label: 'Email Address', binding: 'email' },
                                        [
                                            { type: 'textbox', label: 'Phone', binding: 'phone' },
                                            { type: 'date', label: 'Date Of Birth', binding: 'register_date' }
                                        ],
                                    ]
                                }
                            });

                            family.on('render-node', function (sender, args) {
                                if (args.node.position === "Left") {
                                    args.node.element.style.backgroundColor = "#3498db";
                                } else if (args.node.position === "Right") {
                                    args.node.element.style.backgroundColor = "#e74c3c"; 
                                }
                            });

                            family.load(data);
                        })
                        .catch(error => console.error("Error:", error));
                });

                function getOptions() {
                    const searchParams = new URLSearchParams(window.location.search);
                    var fit = searchParams.get('fit');
                    var enableSearch = true;
                    var scaleInitial = 1;
                    if (fit == 'yes') {
                        enableSearch = false;
                        scaleInitial = FamilyTree.match.boundary;
                    }
                    return { enableSearch, scaleInitial };
                }


        //JavaScript
            </script>
    </body>

    </html>