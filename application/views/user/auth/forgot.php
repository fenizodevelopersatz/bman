    <?php $this->load->view('user/layout/common_style'); ?>

    <body id="kt_body" data-bs-spy="scroll" data-bs-target="#kt_landing_menu" class="bg-body position-relative app-blank">

    <style>
    body {
    background-image: url('<?php echo base_url();?>assets/user/media/auth/bg10.jpg');
    }
    [data-bs-theme="dark"] body {
    background-image: url('<?php echo base_url();?>assets/user/media/auth/bg10-dark.jpg');
    }
    </style>

    <div class="d-flex flex-column flex-root" id="kt_app_root">


      <!--begin::Authentication - Sign-in -->
<div class="d-flex flex-column flex-lg-row flex-column-fluid">
    <!--begin::Aside-->
    <div class="d-flex flex-lg-row-fluid">
        <!--begin::Content-->
        <div class="d-flex flex-column flex-center pb-0 pb-lg-10 p-10 w-100">
            <!--begin::Image-->
            <img class="theme-light-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20" src="<?php echo base_url();?>assets/user/media/svg/illustrations/easy/1.svg" alt="" />
            <img class="theme-dark-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20" src="<?php echo base_url();?>assets/user/media/svg/illustrations/easy/1-dark.svg" alt="" />
            <!--end::Image-->

           <!--begin::Title-->
            <h1 class="text-gray-800 fs-2qx fw-bold text-center mb-7"> 
                Forgot Your Password?
            </h1>
            <!--end::Title-->

            <!--begin::Text-->
            <div class="text-gray-600 fs-base text-center fw-semibold">
                No worries! Enter your registered email and we'll send you instructions 
                <br/> on how to reset your password.
                <br/>
                Need more help? Contact 
                <a href="https://nexman.in" class="opacity-75-hover text-primary me-1" target="_blank">
                    Nexman Technologies Support
                </a>.
            </div>
            <!--end::Text-->

            <!--end::Text-->
        </div>
        <!--end::Content-->
    </div>
    <!--begin::Aside-->

    <!--begin::Body-->
    <div class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-end p-12">
        <!--begin::Wrapper-->
        <div class="bg-body d-flex flex-column flex-center rounded-4 w-md-600px p-10">
            <!--begin::Content-->
            <div class="d-flex flex-center flex-column align-items-stretch h-lg-100 w-md-400px">
                <!--begin::Wrapper-->
                <div class="d-flex flex-center flex-column flex-column-fluid pb-15 pb-lg-20">

                    <!--begin::Form-->
                   <form class="form w-100" novalidate="novalidate" id="kt_password_reset_form" data-kt-redirect-url="/metronic8/demo37/authentication/layouts/overlay/new-password.html" action="#">
                        <!--begin::Heading-->
                        <div class="text-center mb-10">
                            <!--begin::Title-->
                            <h1 class="text-gray-900 fw-bolder mb-3">
                                Forgot Password ?
                            </h1>
                            <!--end::Title-->

                            <!--begin::Link-->
                            <div class="text-gray-500 fw-semibold fs-6">
                                Enter your email to reset your password.
                            </div>
                            <!--end::Link-->
                        </div>
                        <!--begin::Heading-->

                        <!--begin::Input group--->
                        <div class="fv-row mb-8">
                            <!--begin::Email-->
                            <input type="text" placeholder="Email" name="email" autocomplete="off" class="form-control bg-transparent"/> 
                            <!--end::Email-->
                        </div>

                        <!--begin::Actions-->
                        <div class="d-flex flex-wrap justify-content-center pb-lg-0">
                            <button type="button" id="kt_password_reset_submit" class="btn btn-primary me-4">
                                
                    <!--begin::Indicator label-->
                    <span class="indicator-label">
                        Submit</span>
                    <!--end::Indicator label-->

                    <!--begin::Indicator progress-->
                    <span class="indicator-progress">
                        Please wait...    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                    <!--end::Indicator progress-->        </button>

                            <a href="<?php echo base_url(); ?>user/in" class="btn btn-light">Cancel</a>
                        </div>
                        <!--end::Actions-->
                    </form>
                    <!--end::Form-->

                </div>
                <!--end::Wrapper-->

                <!--begin::Footer-->
                 <?php $this->load->view('user/layout/auth_footer'); ?>
                <!--end::Footer-->
            </div>
            <!--end::Content-->
        </div>
        <!--end::Wrapper-->
    </div>
    <!--end::Body-->
</div>



    </div>

    <?php $this->load->view('user/layout/common_script'); ?>

    </body>

    </html>