<?php
$logo_info = site_settings('image', 'logo');
$mobile_logo_info = site_settings('image', 'mobile_logo');
$logo_src = $logo_info ? base_url('assets/images/' . rawurlencode($logo_info)) : base_url('assets/images/logo-whites.png');
$mobile_logo_src = $mobile_logo_info ? base_url('assets/images/' . rawurlencode($mobile_logo_info)) : $logo_src;
?>

<!--begin::Sidebar-->
<div id="kt_app_sidebar" class="app-sidebar  flex-column " data-kt-drawer="true" data-kt-drawer-name="app-sidebar"
  data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="225px"
  data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">


  <div class="app-sidebar-logo px-6" id="kt_app_sidebar_logo">
    <a href="<?php echo base_url(); ?>">
      <img alt="Logo" src="<?php echo $logo_src; ?>" class="app-sidebar-logo-default bman-admin-logo">
      <img alt="Logo" src="<?php echo $mobile_logo_src; ?>" class="bman-admin-logo-mobile">
      <img alt="Logo" src="<?php echo $logo_src; ?>" class="app-sidebar-logo-minimize bman-admin-logo-mini">
    </a>
    <div id="kt_app_sidebar_toggle"
      class="app-sidebar-toggle btn btn-icon btn-shadow btn-sm btn-color-muted btn-active-color-primary h-30px w-30px position-absolute top-50 start-100 translate-middle rotate "
      data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body"
      data-kt-toggle-name="app-sidebar-minimize">

      <i class="ki-duotone ki-black-left-line fs-3 rotate-180"><span class="path1"></span><span
          class="path2"></span></i>
    </div>
  </div>

  <style>
    .bman-admin-logo{height:38px;max-width:150px;width:auto;object-fit:contain}
    .bman-admin-logo-mobile{display:none;height:48px;max-width:190px;width:auto;object-fit:contain}
    .bman-admin-logo-mini{height:28px;max-width:44px;width:auto;object-fit:contain}
    .bman-admin-logout{margin-top:12px;border-top:1px solid rgba(255,255,255,.08);padding-top:12px}
    .bman-admin-logout .menu-link{color:#ff5a7a}
    .bman-admin-logout .menu-link:hover{background:rgba(255,90,122,.1);color:#ff8aa1}
    @media (max-width:991.98px){
      #kt_app_sidebar_logo{height:auto;min-height:76px;padding-top:14px;padding-bottom:14px}
      #kt_app_sidebar_logo a{display:flex;align-items:center}
      .bman-admin-logo{display:none}
      .bman-admin-logo-mobile{display:block}
      .bman-admin-logo-mini{display:none}
    }
  </style>


  <!--begin::sidebar menu-->
  <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
    <!--begin::Menu wrapper-->
    <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper">
      <!--begin::Scroll wrapper-->
      <div id="kt_app_sidebar_menu_scroll" class="scroll-y my-5 mx-3" data-kt-scroll="true"
        data-kt-scroll-activate="true" data-kt-scroll-height="auto"
        data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
        data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px" data-kt-scroll-save-state="true">

        <div class="menu menu-column menu-rounded menu-sub-indention fw-semibold fs-6" id="#kt_app_sidebar_menu"
          data-kt-menu="true" data-kt-menu-expand="false">

          <!--end::Scroll wrapper-->
          <div class="menu-item">
            <a href="<?php echo base_url(); ?>admin" class="menu-link">
              <span class="menu-icon">
                <i class="ki-duotone ki-category fs-3">
                  <span class="path1"></span>
                  <span class="path2"></span>
                  <span class="path3"></span>
                  <span class="path4"></span>
                </i>
              </span>
              <span class="menu-title">Dashboard</span>
            </a>
          </div>



          <!--end::Scroll wrapper-->
          <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="right-start"
            class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention">
            <span class="menu-link"><span class="menu-icon">
                <i class="ki-duotone ki-people fs-2"><span class="path1"></span><span class="path2"></span>
                </i>
              </span><span class="menu-title">Members Management</span><span class="menu-arrow"></span></span>
            <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-2 py-4 w-200px mh-75 overflow-auto">
              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>network-member">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Network Members</span>
                </a>
              </div>
              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>add-user">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Create Members</span>
                </a>
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>admin/kyc">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Members KYC</span>
                </a>
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>admin/bank-verification">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Members Wallet Bank</span>
                </a>
              </div>

            </div>
          </div>


          <!--end::Scroll wrapper-->
          <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="right-start"
            class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention">
            <span class="menu-link"><span class="menu-icon">
                <i class="ki-duotone ki-dollar">
                  <span class="path1"></span>
                  <span class="path2"></span>
                  <span class="path3"></span>
                </i>
              </span><span class="menu-title">Finance Management</span><span class="menu-arrow"></span></span>
            <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-2 py-4 w-200px mh-75 overflow-auto">

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>make-investment">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">Make
                    Investment</span>
                </a>
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>list-investment">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Investment List</span>
                </a>
              </div>


              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>internel-transfer">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">User
                    To User Transfer</span>
                </a>
              </div>

              <!-- <div class="menu-item">
    <a class="menu-link" href="<?php echo base_url(); ?>internel-swap">
    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">Internel Swap</span>
    </a>
    </div> -->


              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>add-wallet">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">Add
                    Wallet</span>
                </a>
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>detect-wallet">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Dedect Wallet</span>
                </a>
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>withdraw-requests">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Withdraw Requests</span>
                </a>
              </div>

            </div>
          </div>

          <!--end::Scroll wrapper-->
          <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="right-start"
            class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention">
            <span class="menu-link"><span class="menu-icon">
                <i class="ki-duotone ki-chart-pie-simple fs-2">
                  <span class="path1"></span>
                  <span class="path2"></span>
                </i>
              </span><span class="menu-title">Staking Management</span><span class="menu-arrow"></span></span>
            <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-2 py-4 w-200px mh-75 overflow-auto">

              <!-- Packages / Plans / ROI / Bonus / Wallet settings moved to the
                   Master group (single place for configuration masters). -->

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>admin/staking/ranks">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Rank Achievement</span>
                </a>
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>admin/staking/rank-power">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Rank Power & Incentive</span>
                </a>
              </div>

            </div>
          </div>


          <!--end::Scroll wrapper-->
          <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="right-start"
            class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention">
            <span class="menu-link"><span class="menu-icon">
                <i class="ki-duotone ki-abstract-26 fs-2">
                  <span class="path1"></span>
                  <span class="path2"></span>
                </i>
              </span><span class="menu-title">Master</span><span class="menu-arrow"></span></span>
            <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-2 py-4 w-200px mh-75 overflow-auto">

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>admin/master/token-settings">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Token Settings</span>
                </a>
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>admin/master/coin-distribution">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Coin Distribution</span>
                </a>
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>admin/staking/packages">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Staking Packages</span>
                </a>
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>admin/staking/plans">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Staking Plans</span>
                </a>
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>admin/staking/roi-structure">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">ROI Settings</span>
                </a>
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>admin/staking/bonus-settings">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Bonus Coin Settings</span>
                </a>
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>withdraw-settings">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Wallet Settings</span>
                </a>
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>admin/master/token-settings">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Blockchain Settings</span>
                </a>
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>site-settings">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">System Settings</span>
                </a>
              </div>

            </div>
          </div>


          <!--end::Scroll wrapper-->
          <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="right-start"
            class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention">
            <span class="menu-link"><span class="menu-icon">

                <i class="ki-duotone ki-feather">
                  <span class="path1"></span>
                  <span class="path2"></span>
                </i>
              </span><span class="menu-title">Content Management</span><span class="menu-arrow"></span></span>
            <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-2 py-4 w-200px mh-75 overflow-auto">

             <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>landing-page-cms">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Home Page Settings</span>
                </a>
              </div>


              <!-- <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>website-content-cms">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Website Content</span>
                </a>
              </div> -->

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>announcement-cms">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Announcement</span>
                </a>
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>admin/blog-category-list">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">Blog
                    Category </span>
                </a>
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>admin/blog-list">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">Blog
                    Manage</span>
                </a>
              </div>

              <!-- <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>slider-cms">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Sliders Image</span>
                </a>
              </div> -->

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>faq-cms">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">FAQ
                    Management</span>
                </a>
              </div>

             
            </div>
          </div>


          <!--end::Scroll wrapper-->
          <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="right-start"
            class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention">
            <span class="menu-link"><span class="menu-icon">
                <i class="ki-duotone ki-brifecase-timer">
                  <span class="path1"></span>
                  <span class="path2"></span>
                  <span class="path3"></span>
                </i>
              </span><span class="menu-title">Marketting Tool</span><span class="menu-arrow"></span></span>
            <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-2 py-4 w-200px mh-75 overflow-auto">

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>email-marketting">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">Email
                    Template</span>
                </a>
              </div>


              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>newsletter-marketting">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">News
                    Letter</span>
                </a>
              </div>


              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>social-link-marketting">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Social Links</span>
                </a>
              </div>

            </div>
          </div>






          <!--end::Scroll wrapper-->
          <!-- <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="right-start"
            class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention">
            <span class="menu-link"><span class="menu-icon">
                <i class="ki-duotone ki-brifecase-timer">
                  <span class="path1"></span>
                  <span class="path2"></span>
                  <span class="path3"></span>
                </i>
              </span><span class="menu-title">Shop Management</span><span class="menu-arrow"></span></span>
            <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-2 py-4 w-200px mh-75 overflow-auto">


              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>admin/orders">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Orders</span>
                </a>
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>admin/product-list">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Product Info</span>
                </a>
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>admin/category-list">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Category Info</span>
                </a>
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>admin/brand-list">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">Brand
                    Info</span>
                </a>
              </div>


              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>admin/coupen-list">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Coupen Info</span>
                </a>
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>admin/shipping-list">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Shipping Info</span>
                </a>
              </div>


            </div>
          </div> -->



          <!--end::Scroll wrapper-->
          <div class="menu-item">
            <a href="<?php echo base_url(); ?>support" class="menu-link">
              <span class="menu-icon">
                <i class="ki-duotone ki-category fs-3">
                  <span class="path1"></span>
                  <span class="path2"></span>
                  <span class="path3"></span>
                  <span class="path4"></span>
                </i>
              </span>
              <span class="menu-title">Support</span>
            </a>
          </div>

          <!--end::Scroll wrapper-->
          <div class="menu-item">
            <a href="<?php echo base_url(); ?>admin/binary-business-report" class="menu-link">
              <span class="menu-icon">
                <i class="ki-duotone ki-category fs-3">
                  <span class="path1"></span>
                  <span class="path2"></span>
                  <span class="path3"></span>
                  <span class="path4"></span>
                </i>
              </span>
              <span class="menu-title">Commission Calculator</span>
            </a>
          </div>





          <!--end::Scroll wrapper-->
          <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="right-start"
            class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention">
            <span class="menu-link"><span class="menu-icon">
                <i class="ki-duotone ki-setting-4 fs-2   ">
                </i>
              </span><span class="menu-title">Settings</span><span class="menu-arrow"></span></span>
            <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-2 py-4 w-200px mh-75 overflow-auto">
              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>site-settings">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">Site
                    Settings</span>
                </a>
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>member-theme">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Member Panel Theme</span>
                </a>
              </div>

              <!-- <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>package-settings">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Package Settings</span>
                </a>
              </div> -->

              <!-- <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>commission-settings">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Commission Settings</span>
                </a>
              </div> -->

              <!-- <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>rank-settings">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">Rank
                    Settings</span>
                </a>
              </div> -->

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>payment-settings">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Payment Settings</span>
                </a>
              </div>
              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>withdraw-settings">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Withdraw Settings</span>
                </a>
              </div>


              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>transfer-settings">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Transfer Settings</span>
                </a>
              </div>
              <!-- <div class="menu-item">
    <a class="menu-link" href="<?php echo base_url(); ?>swap-settings">
    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">Swap Settings</span>
    </a>
    </div> -->

              <!-- <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>admin/earning-ads">
                  <span class=" menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Earning Ads Settings</span>
                </a>
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>admin/earning-videos">
                  <span class=" menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Earning Videos Settings</span>
                </a>
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>admin/earning-methods">
                  <span class=" menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Earning Methods Settings</span>
                </a>
              </div> -->

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>mail-settings">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">E-Mail
                    Settings</span>
                </a>
              </div>
              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>advance-settings">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Advance Settings</span>
                </a>
              </div>
            </div>
          </div>


          <!--end::Scroll wrapper-->
          <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="right-start"
            class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention">
            <span class="menu-link"><span class="menu-icon">
                <i class="fa fa-exchange fs-2 " aria-hidden="true"></i>
              </span><span class="menu-title">All Transaction</span><span class="menu-arrow"></span></span>
            <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-2 py-4 w-200px mh-75 overflow-auto">
              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>all-transaction">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">All
                    Transaction</span>
                </a>
              </div>

            </div>
          </div>

          <div class="menu-item bman-admin-logout">
            <a href="<?php echo base_url(); ?>logout" class="menu-link">
              <span class="menu-icon">
                <i class="ki-duotone ki-exit-right fs-2">
                  <span class="path1"></span>
                  <span class="path2"></span>
                </i>
              </span>
              <span class="menu-title">Logout</span>
            </a>
          </div>

        </div>
        <!--end::Menu-->
      </div>
      <!--end::Scroll wrapper-->
    </div>
    <!--end::Menu wrapper-->
  </div>
  <!--end::sidebar menu-->




  <div class="app-sidebar-footer flex-column-auto pt-2 pb-6 px-6" id="kt_app_sidebar_footer">
    <a href="#" class="btn btn-flex flex-center btn-custom btn-primary overflow-hidden text-nowrap px-0 h-440px w-100"
      data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss-="click"
      data-bs-original-title="200+ in-house components and 3rd-party plugins" data-kt-initialized="1">

      <div>
        <img src="<?php echo base_url(); ?>/assets/admin/media/illustrations/misc/upgrade.svg" />
        <br>
        <span class="label">
          <b>Nexman Version 1.0</b>
        </span>
      </div>

      <i class="ki-duotone ki-document btn-icon fs-2 m-0"><span class="path1"></span><span class="path2"></span></i>
    </a>
  </div>

</div>
<!--end::Sidebar-->
