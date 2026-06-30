    <?php $this->load->view('user/layout/common_style'); ?>

    <body id="kt_body" data-bs-spy="scroll" data-bs-target="#kt_landing_menu" class="bg-body position-relative app-blank">

    <style>
    body {
    background-image: url('<?php echo base_url();?>assets/user/media/auth/bg10.jpg');
    }
    [data-bs-theme="dark"] body {
    background-image: url('<?php echo base_url();?>assets/user/media/auth/bg10-dark.jpg');
    }
    @media (max-width: 768px) {
    .p-20{
        padding: 0px !important;
    }
    .h-60px {
    height: 63px!important;
    }
    .w-60px {
        width: 71px!important;
    }
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
            <img class="theme-light-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20" src="<?php echo base_url();?>assets/user/media/svg/illustrations/easy/5.svg" alt="" />
            <img class="theme-dark-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20" src="<?php echo base_url();?>assets/user/media/auth/agency-dark.png" alt="" />
            <!--end::Image-->

            <!-- First Block -->
            <h1 class="text-gray-800 fs-2qx fw-bold text-center mb-7">
                <?php echo lang('mlm_solutions_title'); ?>
            </h1>

            <div class="text-gray-600 fs-base text-center fw-semibold">
                <?php 
                echo sprintf(
                    lang('mlm_solutions_login_text'), 
                    '<a href="https://fenizotechnologies.com" target="_blank" class="opacity-75-hover text-primary me-1">Fenizo Technologies</a>',
                    '<a href="https://fenizotechnologies.com/mlm-software" target="_blank" class="opacity-75-hover text-primary me-1">Explore our demo</a>'
                ); 
                ?>
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

            <?php if($verify_type == "1"){ ?>
                    <!--begin::Form-->
                    <form class="form w-100" novalidate="novalidate" id="kt_sign_in_form" data-kt-redirect-url="<?php echo $action; ?>" action="<?php echo $action; ?>">
                        <!--begin::Heading-->
                        <div class="text-center mb-11">
                            <!--begin::Title-->
                            <h1 class="text-gray-900 fw-bolder mb-3">
                               <?php echo lang('sign_in'); ?>
                            </h1>
                            <!--end::Title-->

                            <!--begin::Subtitle-->
                            <div class="text-gray-500 fw-semibold fs-6">
                               <?php echo lang('your_social_campaings'); ?> 
                            </div>
                            <!--end::Subtitle--->
                        </div>
                        <!--begin::Heading-->

                        <!--begin::Login options-->
                        <div class="row g-3 mb-9">
                            <div class="col-md-12">
                                <a href="#" class="btn btn-flex btn-outline btn-text-gray-700 btn-active-color-primary bg-state-light flex-center text-nowrap w-100" id="google-signin-btn">
                                    <img alt="Logo" src="<?php echo base_url();?>assets/user/media/svg/brand-logos/google-icon.svg" class="h-15px me-3" /> 
                                    <?php echo lang('Or_with_email'); ?>
                                </a>
                            </div>
                        </div>
                        <!--end::Login options-->

                        <!--begin::Separator-->
                        <div class="separator separator-content my-14">
                            <span class="w-125px text-gray-500 fw-semibold fs-7"><?php echo lang('Or_with_email'); ?></span>
                        </div>
                        <!--end::Separator-->

                        <!--begin::Input group--->
                        <div class="fv-row mb-8">
                            <input type="text" placeholder="<?php echo lang('Email'); ?>" name="useremail" autocomplete="off" class="form-control bg-transparent" value="satz@yopmail.com" />
                        </div>

                        <!-- <div class="fv-row mb-3">
                            <input type="password" placeholder="<?php echo lang('Password'); ?>" name="password" autocomplete="off" class="form-control bg-transparent" />
                        </div> -->

                        <div class="fv-row mb-3">
                            <div class="input-group">
                                <input type="password" class="form-control bg-transparent"
                                    placeholder="Password" name="password" id="password" value="Qwerty@123" 
                                    required>

                                <span class="input-group-text bg-transparent" id="togglePassword"
                                    style="cursor: pointer;">
                                    <i class="bi bi-eye"></i>
                                </span>
                            </div>
                        </div>

                        <!--begin::Wrapper-->
                        <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                            <div></div>

                            <!--begin::Link-->
                            <a href="<?php echo base_url(); ?>user/forgot" class="link-primary">
                              <?php echo lang('forgot_password'); ?>
                            </a>
                            <!--end::Link-->
                        </div>
                        <!--end::Wrapper-->

                        <!--begin::Submit button-->
                        <div class="d-grid mb-10">
                            <button type="submit" id="kt_sign_in_submit" class="btn btn-primary">

                                <!--begin::Indicator label-->
                                <span class="indicator-label">
                                <?php echo lang('sign_in'); ?>
                                </span>
                                <!--end::Indicator label-->

                                <!--begin::Indicator progress-->
                                <span class="indicator-progress">
                                <?php echo lang('please_wait'); ?>
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                                <!--end::Indicator progress-->
                            </button>
                        </div>
                        <!--end::Submit button-->

                        <!--begin::Sign up-->
                        <div class="text-gray-500 text-center fw-semibold fs-6">
                            <?php echo lang('Not_a_Member_yet'); ?>
                            <a href="<?php echo base_url(); ?>user/re" class="link-primary">
                                 <?php echo lang('Sign_up'); ?>
                            </a>
                        </div>
                        <!--end::Sign up-->
                    </form>
                    <!--end::Form-->

                    <?php } ?>

                    <?php if($verify_type != '1') { $this->session->unset_userdata('sender_otp'); ?>

                        
<div class="bg-body d-flex flex-column align-items-stretch flex-center rounded-4 w-md-600px p-20">
    <div class="d-flex flex-center flex-column flex-column-fluid px-lg-10 pb-15 pb-lg-20">

        <!--begin::Form-->
        <form class="form w-100 mb-13" novalidate="novalidate" 
        action="<?php echo base_url();?>user/login-finel-verify"
        method="POST"
        id="kt_sing_in_two_factor_form">
            <!--begin::Icon-->
            <div class="text-center mb-10">
                <img alt="Logo" class="mh-125px" src="<?php echo base_url();?>/assets/admin/media/svg/misc/smartphone-2.svg">
            </div>
            <!--end::Icon-->

            <!--begin::Heading-->
            <div class="text-center mb-10">
                <!--begin::Title-->
                <h1 class="text-gray-900 mb-3">
                   <?php echo lang('two_factor_title'); ?>
                </h1>
                <!--end::Title-->

                <!--begin::Sub-title-->
                <div class="text-muted fw-semibold fs-5 mb-5"> <?php echo lang('two_factor_subtitle'); ?></div>
                <!--end::Sub-title-->

                <!--begin::Mobile no-->
                <div class="fw-bold text-gray-900 fs-3"><?php echo isset($admin_mail)?$admin_mail:''; ?></div>
                <!--end::Mobile no-->
            </div>
            <!--end::Heading-->

            <div class="mb-10">
                <div class="fw-bold text-start text-gray-900 fs-6 mb-1 ms-1"> <?php echo lang('two_factor_input_label'); ?></div>

                <div class="otp-container">
                <div class="d-flex flex-wrap flex-stack">

                    <input type="text" name="twofa_code_1" 
                    data-inputmask="'mask': '9', 'placeholder': ''"
                     maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 fa-code" value="" inputmode="numeric">
                    <input type="text" name="twofa_code_2" data-inputmask="'mask': '9', 'placeholder': ''"
                     maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 fa-code" value="" inputmode="numeric">
                    <input type="text" name="twofa_code_3" data-inputmask="'mask': '9', 'placeholder': ''" 
                    maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 fa-code" value="" inputmode="numeric">
                    <input type="text" name="twofa_code_4" data-inputmask="'mask': '9', 'placeholder': ''" 
                    maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 fa-code" value="" inputmode="numeric">
                    <input type="text" name="twofa_code_5" data-inputmask="'mask': '9', 'placeholder': ''" 
                    maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 fa-code" value="" inputmode="numeric">
                    <input type="text" name="twofa_code_6" data-inputmask="'mask': '9', 'placeholder': ''" 
                    maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 fa-code" value="" inputmode="numeric">
                </div>
                <span class="otp-loader d-none">⏳ Verifying...</span>
                <p class="otp-message"></p>
                </div>
<p>OTP:123456</p>
            </div>


             <div class="mb-10">
                <div class="fw-bold text-start text-gray-900 fs-6 mb-1 ms-1"><?php echo lang('email_otp_input_label'); ?></div>

                <div class="otp-container">
                <div class="d-flex flex-wrap flex-stack">
                    <input type="text" name="email_code_1" data-inputmask="'mask': '9', 'placeholder': ''" maxlength="1" 
                    class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 email-code" value="" inputmode="numeric">
                    <input type="text" name="email_code_2" data-inputmask="'mask': '9', 'placeholder': ''" 
                    maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 email-code" value="" inputmode="numeric">
                    <input type="text" name="email_code_3" data-inputmask="'mask': '9', 'placeholder': ''" 
                    maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 email-code" value="" inputmode="numeric">
                    <input type="text" name="email_code_4" data-inputmask="'mask': '9', 'placeholder': ''" 
                    maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 email-code" value="" inputmode="numeric">
                    <input type="text" name="email_code_5" data-inputmask="'mask': '9', 'placeholder': ''" 
                    maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 email-code" value="" inputmode="numeric">
                    <input type="text" name="email_code_6" data-inputmask="'mask': '9', 'placeholder': ''" 
                    maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 email-code" value="" inputmode="numeric">
                </div>
                <span class="otp-loader d-none">⏳ Verifying...</span>
                <p class="otp-message"></p>
                </div>
                <p>OTP:123456</p>
            </div>


            <!--begin::Submit-->
            <div class="d-flex flex-center">
                <button type="submit" id="kt_sing_in_two_factor_submit" 
                data-kt-redirect-url="<?php echo base_url();?>user/main" 
                disabled class="btn btn-lg btn-primary fw-bold">
                    <span class="indicator-label">
                        Submit
                    </span>
                    <span class="indicator-progress">
                        Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
                </div>
                <!--end::Submit-->
                
            </form>
            <!--end::Form-->

        </div>
        <!--end::Wrapper-->

    </div>


    <?php } ?>
                        
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
    <script>
    const LangStrings = {
    successLogin: "<?= $this->lang->line('login_success') ?>",
    emailRequired: "<?= $this->lang->line('email_required') ?>",
    emailInvalid: "<?= $this->lang->line('email_invalid') ?>",
    passwordRequired: "<?= $this->lang->line('password_required') ?>",
    loginError: "<?= $this->lang->line('login_failed') ?>",
    genericError: "<?= $this->lang->line('generic_error') ?>",
    password_incorrect: "<?= $this->lang->line('password_incorrect') ?>"
    };
    const base_url = '<?php echo base_url();?>';
    </script>
    <script src="<?php echo base_url();?>assets/user/js/custom/authentication/sign-in/general.js?version=2.1"></script> 
      <script>
        document.getElementById("togglePassword").addEventListener("click", function () {
            const password = document.getElementById("password");
            const icon = this.querySelector("i");

            if (password.type === "password") {
                password.type = "text";
                icon.classList.remove("bi-eye");
                icon.classList.add("bi-eye-slash");
            } else {
                password.type = "password";
                icon.classList.remove("bi-eye-slash");
                icon.classList.add("bi-eye");
            }
        });
    </script>
    </body>

    </html>