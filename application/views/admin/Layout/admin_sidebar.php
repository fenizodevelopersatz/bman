<?php
$logo_info = site_settings('image', 'logo');
?>

<!--begin::Sidebar-->
<div id="kt_app_sidebar" class="app-sidebar  flex-column " data-kt-drawer="true" data-kt-drawer-name="app-sidebar"
  data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="225px"
  data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">


  <div class="app-sidebar-logo px-6" id="kt_app_sidebar_logo">
    <a href="<?php echo base_url(); ?>">
      <img alt="Logo" src="<?php echo base_url() . "assets/images/" . $logo_info; ?>"
        class="h-25px app-sidebar-logo-default">
      <img alt="Logo" src="<?php echo base_url() . "assets/images/" . $logo_info; ?>"
        class="h-20px app-sidebar-logo-minimize">
    </a>
    <div id="kt_app_sidebar_toggle"
      class="app-sidebar-toggle btn btn-icon btn-shadow btn-sm btn-color-muted btn-active-color-primary h-30px w-30px position-absolute top-50 start-100 translate-middle rotate "
      data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body"
      data-kt-toggle-name="app-sidebar-minimize">

      <i class="ki-duotone ki-black-left-line fs-3 rotate-180"><span class="path1"></span><span
          class="path2"></span></i>
    </div>
  </div>


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
                    class="menu-title">Members Bank</span>
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

                <i class="ki-duotone ki-feather">
                  <span class="path1"></span>
                  <span class="path2"></span>
                </i>
              </span><span class="menu-title">Content Management</span><span class="menu-arrow"></span></span>
            <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-2 py-4 w-200px mh-75 overflow-auto">

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>website-content-cms">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Website Content</span>
                </a>
              </div>

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

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>slider-cms">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Sliders Image</span>
                </a>
              </div>

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
          <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="right-start"
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
          </div>



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
                <a class="menu-link" href="<?php echo base_url(); ?>package-settings">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Package Settings</span>
                </a>
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>commission-settings">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                    class="menu-title">Commission Settings</span>
                </a>
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>rank-settings">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">Rank
                    Settings</span>
                </a>
              </div>

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

              <div class="menu-item">
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
              </div>

              <div class="menu-item">
                <a class="menu-link" href="<?php echo base_url(); ?>mail-settings">
                  <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">Mail
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
          <b>Demo Version 7.1</b>
        </span>
      </div>

      <i class="ki-duotone ki-document btn-icon fs-2 m-0"><span class="path1"></span><span class="path2"></span></i>
    </a>
  </div>

</div>
<!--end::Sidebar-->