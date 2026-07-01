<html>

<?php $this->load->view('admin/Layout/common_style'); ?>

<?php
$logo = site_settings('image', 'logo');
?>

<body id="kt_body" class="app-blank bgi-size-cover bgi-attachment-fixed bgi-position-center bgi-no-repeat">

    <div class="d-flex flex-column flex-root" id="kt_app_root">
        <style>
            body {
                background-image: url('<?php echo base_url(); ?>/assets/admin/media/auth/bg4.jpg');
            }

            [data-bs-theme="dark"] body {
                background-image: url('<?php echo base_url(); ?>/assets/admin/media/auth/bg4-dark.jpg');
            }
        </style>


        <div class="d-flex flex-column flex-column-fluid flex-lg-row">

            <div class="d-flex flex-center w-lg-50 pt-15 pt-lg-0 px-10">

                <div class="d-flex flex-center flex-lg-start flex-column">

                    <a href="<?php echo base_url(); ?>" class="mb-7">
                        <img alt="Logo" src="<?php echo base_url() . "assets/images/" . $logo; ?>">
                    </a>

                </div>

            </div>


            <div
                class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-end p-12 p-lg-20">

                <!--begin::Card-->
                <div class="bg-body d-flex flex-column align-items-stretch flex-center rounded-4 w-md-600px p-20">
                    <!--begin::Wrapper-->
                    <div class="d-flex flex-center flex-column flex-column-fluid px-lg-10 pb-15 pb-lg-20">



                        <?php $this->load->view('notification'); ?>

                        <?php if ($verify_type == "1") { ?>

                            <!--begin::Form-->
                            <form class="form w-100" method="post" novalidate="novalidate" id="kt_sign_in_form"
                                action="<?php echo $action; ?>" data-kt-redirect-url="<?php echo base_url(); ?>admin">
                                <div class="text-center mb-11">
                                    <h1 class="text-gray-900 fw-bolder mb-3">
                                        <?php echo lang('sign_in'); ?>
                                    </h1>

                                </div>


                                <div class="fv-row mb-8">
                                    <input type="text" placeholder="Email" name="username" autocomplete="off"
                                        value="admin@gmail.com" class="form-control bg-transparent">
                                </div>

                                <div class="fv-row mb-3">
                                    <input type="password" placeholder="Password" name="password" autocomplete="off"
                                        value="Admin@123" class="form-control bg-transparent">
                                </div>


                                <div class="fv-row mb-3 mt-3">
                                    <?php
                                    $site_captcha_status = site_settings('captcha', 'status');
                                    if ($site_captcha_status) {
                                        $sitekey = site_settings('captcha', 'sitekey');
                                        ?>
                                        <div class="g-recaptcha" data-sitekey="<?php echo $sitekey; ?>"></div>
                                        <?php
                                    }
                                    ?>
                                </div>

                                <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8 mt-4">

                                    <div class="section  mb-3 mt-3">
                                        <div class="bs-component pull-left pt5">
                                            <div class="radio-custom radio-primary mb5 lh25">
                                                <input type="radio" id="remember" name="remember">
                                                <label for="remember"><?php echo lang('remember_me'); ?></label>
                                            </div>
                                        </div>
                                    </div>

                                    <a href="<?php echo base_url(); ?>administrator/forget-password"
                                        class="link-primary mt-3">
                                        <?php echo lang('forgot_password'); ?>
                                    </a>
                                </div>



                                <div class="d-grid mb-10">
                                    <button type="submit" id="kt_sign_in_submit" class="btn btn-primary">
                                        <span class="indicator-label">
                                            <?php echo lang('sign_in'); ?></span>
                                        <span class="indicator-progress">
                                            <?php echo lang('please_wait'); ?> <span
                                                class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                        </span>
                                    </button>
                                </div>

                            </form>

                        <?php } ?>

                        <?php if ($verify_type != '1') {
                            $this->session->unset_userdata('sender_otp'); ?>

                            <div
                                class="bg-body d-flex flex-column align-items-stretch flex-center rounded-4 w-md-600px p-20">
                                <div class="d-flex flex-center flex-column flex-column-fluid px-lg-10 pb-15 pb-lg-20">

                                    <!--begin::Form-->
                                    <form class="form w-100 mb-13" novalidate="novalidate"
                                        action="<?php echo base_url(); ?>login-finel-verify" method="POST"
                                        id="kt_sing_in_two_factor_form">
                                        <!--begin::Icon-->
                                        <div class="text-center mb-10">
                                            <img alt="Logo" class="mh-125px"
                                                src="<?php echo base_url(); ?>/assets/admin/media/svg/misc/smartphone-2.svg">
                                        </div>
                                        <!--end::Icon-->

                                        <!--begin::Heading-->
                                        <div class="text-center mb-10">
                                            <!--begin::Title-->
                                            <h1 class="text-gray-900 mb-3">
                                                Two-Factor & Email Verification
                                            </h1>
                                            <!--end::Title-->

                                            <!--begin::Sub-title-->
                                            <div class="text-muted fw-semibold fs-5 mb-5">Enter the verification code we
                                                sent to</div>
                                            <!--end::Sub-title-->

                                            <!--begin::Mobile no-->
                                            <div class="fw-bold text-gray-900 fs-3"><?php echo $admin_mail ?? ''; ?></div>
                                            <!--end::Mobile no-->
                                        </div>
                                        <!--end::Heading-->

                                        <div class="mb-10">
                                            <div class="fw-bold text-start text-gray-900 fs-6 mb-1 ms-1">Type your
                                                Two-Factor 6 digit security code</div>

                                            <div class="otp-container">
                                                <div class="d-flex flex-wrap flex-stack">
                                                    <input type="text" name="twofa_code_1"
                                                        maxlength="1"
                                                        class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 fa-code"
                                                        value="" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*">
                                                    <input type="text" name="twofa_code_2"
                                                        maxlength="1"
                                                        class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 fa-code"
                                                        value="" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*">
                                                    <input type="text" name="twofa_code_3"
                                                        maxlength="1"
                                                        class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 fa-code"
                                                        value="" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*">
                                                    <input type="text" name="twofa_code_4"
                                                        maxlength="1"
                                                        class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 fa-code"
                                                        value="" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*">
                                                    <input type="text" name="twofa_code_5"
                                                        maxlength="1"
                                                        class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 fa-code"
                                                        value="" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*">
                                                    <input type="text" name="twofa_code_6"
                                                        maxlength="1"
                                                        class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 fa-code"
                                                        value="" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*">
                                                </div>
                                                <span class="otp-loader d-none">⏳ Verifying...</span>
                                                <p class="otp-message"></p>
                                            </div>
                                            <p>OTP:123456</p>
                                        </div>


                                        <div class="mb-10">
                                            <div class="fw-bold text-start text-gray-900 fs-6 mb-1 ms-1">Type your Email-OTP
                                                6 digit security code</div>

                                            <div class="otp-container">
                                                <div class="d-flex flex-wrap flex-stack">
                                                    <input type="text" name="email_code_1"
                                                        maxlength="1"
                                                        class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 email-code"
                                                        value="" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*">
                                                    <input type="text" name="email_code_2"
                                                        maxlength="1"
                                                        class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 email-code"
                                                        value="" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*">
                                                    <input type="text" name="email_code_3"
                                                        maxlength="1"
                                                        class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 email-code"
                                                        value="" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*">
                                                    <input type="text" name="email_code_4"
                                                        maxlength="1"
                                                        class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 email-code"
                                                        value="" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*">
                                                    <input type="text" name="email_code_5"
                                                        maxlength="1"
                                                        class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 email-code"
                                                        value="" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*">
                                                    <input type="text" name="email_code_6"
                                                        maxlength="1"
                                                        class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 email-code"
                                                        value="" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*">
                                                </div>
                                                <span class="otp-loader d-none">⏳ Verifying...</span>
                                                <p class="otp-message"></p>
                                            </div>
                                            <p>OTP:123456</p>
                                        </div>



                                        <!--begin::Submit-->
                                        <div class="d-flex flex-center">
                                            <button type="submit" id="kt_sing_in_two_factor_submit"
                                                data-kt-redirect-url="<?php echo base_url(); ?>admin" disabled
                                                class="btn btn-lg btn-primary fw-bold">
                                                <span class="indicator-label">
                                                    Submit
                                                </span>
                                                <span class="indicator-progress">
                                                    Please wait... <span
                                                        class="spinner-border spinner-border-sm align-middle ms-2"></span>
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

                </div>

            </div>


        </div>
    </div>

    <?php $this->load->view('admin/Layout/common_script'); ?>
    <script>
        const base_url = '<?php echo base_url(); ?>';
    </script>
    <script src="<?php echo base_url(); ?>/assets/admin/js/custom/authentication/sign-in/general.js?ver=2.5"></script>
    <script src='https://www.google.com/recaptcha/api.js'></script>
</body>

</html>