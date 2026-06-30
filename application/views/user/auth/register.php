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
            <img class="theme-light-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20" src="<?php echo base_url();?>assets/user/media/svg/illustrations/easy/2.svg" alt="" />
            <img class="theme-dark-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20" src="<?php echo base_url();?>assets/user/media/svg/illustrations/easy/2-dark.svg" alt="" />
            <!--end::Image-->

            <!--begin::Title-->
            <h1 class="text-gray-800 fs-2qx fw-bold text-center mb-7">
                <?php echo lang('mlm_solutions_title'); ?>
            </h1>
            <!--end::Title-->

            <!--begin::Text-->
            <div class="text-gray-600 fs-base text-center fw-semibold">
                <?php 
                echo sprintf(
                    lang('mlm_solutions_text'), 
                    '<a href="https://fenizotechnologies.com" target="_blank" class="opacity-75-hover text-primary me-1">Fenizo Technologies</a>',
                    '<a href="https://fenizotechnologies.com/mlm-software" target="_blank" class="opacity-75-hover text-primary me-1">Explore our demo</a>'
                ); 
                ?>
            </div>
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
                <form class="form w-100" novalidate="novalidate" id="kt_sign_up_form" data-kt-redirect-url="<?php echo base_url();?>user/in" action="<?php echo $action; ?>">
                    <!--begin::Heading-->
                    <div class="text-center mb-11">
                        <!--begin::Title-->
                        <h1 class="text-gray-900 fw-bolder mb-3">
                             <?php echo lang('sign_up'); ?>
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
                        <!--begin::Col-->
                        <div class="col-md-12">
                            <!--begin::Google link--->
                            <a href="#" class="btn btn-flex btn-outline btn-text-gray-700 btn-active-color-primary bg-state-light flex-center text-nowrap w-100" id="google-signin-btn">
                                <img alt="Logo" src="<?php echo base_url();?>assets/user/media/svg/brand-logos/google-icon.svg" class="h-15px me-3" /> Sign in with Google
                            </a>
                            <!--end::Google link--->
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Login options-->

                    <!--begin::Separator-->
                    <div class="separator separator-content my-14"><span class="w-125px text-gray-500 fw-semibold fs-7">Or with email</span></div>
                    <!--end::Separator-->

                    <!--begin::Input group--->
                    <div class="fv-row mb-8">
                        <input type="text" placeholder="<?php echo lang('sponsor_id'); ?>"  autocomplete="off"   name="sponsor_id" class="form-control bg-transparent" value="<?= htmlspecialchars($ref_raw ?? '') ?>" readonly>
                        <input type="hidden" name="select_lg" value="<?= htmlspecialchars($ref_leg ?? '') ?>">
                    </div>

                    <div class="fv-row mb-8">
                        <input type="text" placeholder="<?php echo lang('username'); ?>" name="username" autocomplete="off" class="form-control bg-transparent" />
                    </div>

                    <div class="fv-row mb-8">
                        <input type="text" placeholder="<?php echo lang('useremail'); ?>" name="useremail" autocomplete="off" class="form-control bg-transparent" />
                    </div>

                    <!--begin::Input group-->
                    <div class="fv-row mb-8" data-kt-password-meter="true">
                        <!--begin::Wrapper-->
                        <div class="mb-1">
                            <!--begin::Input wrapper-->
                            <div class="position-relative mb-3">
                                <input class="form-control bg-transparent" type="password" placeholder="Password" name="password" autocomplete="off" />

                                <span class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2" data-kt-password-meter-control="visibility">
                                    <i class="ki-outline ki-eye-slash fs-2"></i>                    <i class="ki-outline ki-eye fs-2 d-none"></i>                </span>
                            </div>
                            <!--end::Input wrapper-->

                            <!--begin::Meter-->
                            <div class="d-flex align-items-center mb-3" data-kt-password-meter-control="highlight">
                                <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                                <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                                <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                                <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px"></div>
                            </div>
                            <!--end::Meter-->
                        </div>
                        <!--end::Wrapper-->
                                            <!--begin::Hint-->
                        <div class="text-muted">
                            <?php echo lang('use_password_hint'); ?>
                        </div>
                        <!--end::Hint-->

                        <div class="fv-row mb-8">
                            <!--begin::Repeat Password-->
                            <input type="password" placeholder="<?php echo lang('repeat_password'); ?>" name="confirm-password" autocomplete="off" class="form-control bg-transparent" />
                            <!--end::Repeat Password-->
                        </div>

                        <div class="fv-row mb-8">
                            <label class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="toc" value="1" />
                                <span class="form-check-label fw-semibold text-gray-700 fs-base ms-1">
                                    <?php echo lang('accept_terms'); ?> <a href="#" class="ms-1 link-primary"><?php echo lang('terms'); ?></a>
                                </span>
                            </label>
                        </div>

                        <div class="d-grid mb-10">
                            <button type="submit" id="kt_sign_up_submit" class="btn btn-primary">
                                <!--begin::Indicator label-->
                                <span class="indicator-label">
                                    <?php echo lang('sign_up'); ?>
                                </span>
                                <!--end::Indicator label-->

                                <!--begin::Indicator progress-->
                                <span class="indicator-progress">
                                    <?php echo lang('please_wait'); ?> <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                                <!--end::Indicator progress-->
                            </button>
                        </div>

                        <div class="text-gray-500 text-center fw-semibold fs-6">
                            <?php echo lang('already_have_account'); ?>

                            <a href="<?php echo base_url();?>user/in" class="link-primary fw-semibold">
                                <?php echo lang('sign_in'); ?>
                            </a>
                        </div>

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
     <script src="<?php echo base_url();?>assets/user/js/custom/authentication/sign-up/general.js?version=2.4"></script>
    </body>

    </html>