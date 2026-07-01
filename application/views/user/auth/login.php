    <?php $this->load->view('user/layout/common_style'); ?>
    <?php
    // branding for the right-hand panel (design only)
    // NEW: use the logo configured in Admin -> Site Settings; fall back to the bundled SVG.
    $lpx_logo_file = site_settings('image', 'logo');
    $lpx_logo = $lpx_logo_file ? 'assets/images/' . rawurlencode($lpx_logo_file) : 'assets/img/logo/logo.svg';
    $lpx_name = site_settings('meta-settings', 'site-name');
    if (!$lpx_name) { $lpx_name = 'Webze'; }
    ?>

    <body id="kt_body" data-bs-spy="scroll" data-bs-target="#kt_landing_menu" class="bg-body position-relative app-blank">

    <style>
    body{ background:#0b0b23 url('<?php echo base_url();?>assets/img/banner/hero_bg.svg') center center / cover no-repeat fixed; }
    html[data-bs-theme="light"] body{ background:#f5f7fb; }
    .lpx-theme-toggle{ position:fixed; right:24px; top:24px; z-index:20; width:44px; height:44px; border:0; border-radius:12px;
        display:flex; align-items:center; justify-content:center; color:#fff; background:rgba(255,255,255,.1); box-shadow:0 10px 28px rgba(0,0,0,.18); }
    html[data-bs-theme="light"] .lpx-theme-toggle{ color:#19213a; background:#fff; border:1px solid rgba(20,30,54,.08); }
    .lpx-theme-toggle .icon-sun{ display:none; }
    html[data-bs-theme="dark"] .lpx-theme-toggle .icon-sun{ display:block; }
    html[data-bs-theme="dark"] .lpx-theme-toggle .icon-moon{ display:none; }
    html[data-bs-theme="light"] .lpx-form-side, html[data-bs-theme="light"] .lpx-form-side h1,
    html[data-bs-theme="light"] .lpx-form-side .text-gray-900, html[data-bs-theme="light"] .lpx-form-side .text-gray-800{ color:#10182f !important; }
    html[data-bs-theme="light"] .lpx-form-side .text-gray-500, html[data-bs-theme="light"] .lpx-form-side .text-gray-600{ color:#64708a !important; }
    html[data-bs-theme="light"] .lpx-form-side .form-control{ background:#fff !important; border-color:#dbe2ee !important; color:#10182f !important; }
    html[data-bs-theme="light"] .lpx-form-side .input-group-text{ background:#fff !important; border-color:#dbe2ee !important; color:#10182f; }

    /* ---- Webze split layout (design only) ---- */
    .lpx-auth{ display:flex; min-height:100vh; align-items:center; justify-content:center; gap:30px;
        max-width:1180px; margin:0 auto; padding:30px 24px; }
    .lpx-form-side{ flex:1 1 0; min-width:0; display:flex; align-items:center; justify-content:center; padding:10px; }
    .lpx-form-inner{ width:100%; max-width:420px; }
    .lpx-brand-side{ flex:1 1 0; min-width:0; position:relative; display:flex; align-items:center; justify-content:center;
        min-height:560px; border-radius:24px; padding:48px;
        /* NEW: driven by Admin -> Member Panel Theme (Gradient Start / Gradient End) */
        background: var(--mp-gradient, linear-gradient(150deg, #6D4AFF 0%, #A855F7 100%));
        box-shadow:0 20px 60px rgba(0,0,0,.35); }
    .lpx-brand-inner{ padding:48px; }
    .lpx-brand-inner img{ height:135px; margin-bottom:26px; }
    .lpx-brand-inner h2{ color:#fff; font-weight:800; font-size:34px; line-height:1.2; margin:0; }
    .lpx-home{ position:absolute; top:24px; right:30px; color:#fff; font-weight:600; letter-spacing:.5px; text-decoration:none; }
    .lpx-home:hover{ color:#fff; opacity:.85; }
    .lpx-home i{ margin-right:8px; }

    /* ---- force light text + Webze inputs/button on the form side ---- */
    .lpx-form-side, .lpx-form-side h1, .lpx-form-side .text-gray-900, .lpx-form-side .text-gray-800{ color:#fff !important; }
    .lpx-form-side .text-gray-500, .lpx-form-side .text-gray-600{ color:rgba(255,255,255,.65) !important; }
    .lpx-form-side .form-control{ background:rgba(255,255,255,.05) !important; border:1px solid rgba(255,255,255,.14) !important;
        border-radius:14px; height:56px; color:#fff !important; }
    .lpx-form-side .input-group-text{ background:rgba(255,255,255,.05) !important; border:1px solid rgba(255,255,255,.14) !important;
        border-left:none !important; border-radius:0 14px 14px 0; color:#fff; }
    .lpx-form-side .input-group .form-control{ border-right:none !important; border-radius:14px 0 0 14px; }
    /* NEW: primary CTA driven by Member Panel Theme (Primary + Hover Highlight) */
    .lpx-form-side .btn-primary{ background:linear-gradient(135deg, var(--mp-primary, #6D4AFF), var(--mp-hover, #5a3df0)) !important; border:none !important;
        border-radius:40px; height:56px; color:#fff !important; font-weight:700; box-shadow:0 10px 24px rgba(0,0,0,.25); }
    .lpx-form-side .btn-primary:hover{ filter:brightness(1.05); }
    .lpx-form-side .link-primary{ color: var(--mp-primary, #6D4AFF) !important; }
    .lpx-form-side .fa-code, .lpx-form-side .email-code{ color:#fff !important; }
    .lpx-hide{ display:none !important; }

    @media (max-width: 991px){ .lpx-auth{ flex-direction:column; min-height:auto; } .lpx-brand-side{ display:none; } }
    @media (max-width: 768px){
        .p-20{ padding:0px !important; }
        .otp-container .d-flex.flex-wrap.flex-stack{
            flex-wrap:nowrap !important;
            justify-content:center !important;
            gap:6px;
        }
        .otp-container .h-60px{
            height:48px !important;
        }
        .otp-container .w-60px{
            width:42px !important;
            min-width:42px !important;
        }
        .otp-container .form-control{
            margin-left:0 !important;
            margin-right:0 !important;
            font-size:20px !important;
        }
    }
    </style>

    <div class="d-flex flex-column flex-root" id="kt_app_root">
      <button class="lpx-theme-toggle" type="button" id="lpx_theme_toggle" aria-label="Toggle theme">
        <i class="bi bi-sun icon-sun"></i>
        <i class="bi bi-moon icon-moon"></i>
      </button>
      <div class="lpx-auth">

        <!-- ===================== FORM SIDE ===================== -->
        <div class="lpx-form-side">
          <div class="lpx-form-inner">

            <?php $this->load->view('notification'); ?>

            <?php if($verify_type == "1"){ ?>
                <!--begin::Form-->
                <form class="form w-100" novalidate="novalidate" id="kt_sign_in_form" data-kt-redirect-url="<?php echo $action; ?>" action="<?php echo $action; ?>">
                    <div class="text-center mb-8">
                        <h1 class="text-gray-900 fw-bolder mb-3"><?php echo lang('sign_in'); ?></h1>
                        <div class="text-gray-500 fw-semibold fs-6">👋 <?php echo lang('your_social_campaings'); ?></div>
                    </div>

                    <!-- kept for functionality, hidden to match the design -->
                    <div class="lpx-hide">
                        <div class="row g-3 mb-9">
                            <div class="col-md-12">
                                <a href="#" class="btn btn-flex btn-outline btn-text-gray-700 btn-active-color-primary bg-state-light flex-center text-nowrap w-100" id="google-signin-btn">
                                    <img alt="Logo" src="<?php echo base_url();?>assets/user/media/svg/brand-logos/google-icon.svg" class="h-15px me-3" />
                                    <?php echo lang('Or_with_email'); ?>
                                </a>
                            </div>
                        </div>
                        <div class="separator separator-content my-14">
                            <span class="w-125px text-gray-500 fw-semibold fs-7"><?php echo lang('Or_with_email'); ?></span>
                        </div>
                    </div>

                    <div class="fv-row mb-5">
                        <input type="text" placeholder="<?php echo lang('Email'); ?>" name="useremail" autocomplete="off" class="form-control bg-transparent" value="satz@yopmail.com" />
                    </div>

                    <div class="fv-row mb-3">
                        <div class="input-group">
                            <input type="password" class="form-control bg-transparent" placeholder="Password" name="password" id="password" value="Qwerty@123" required>
                            <span class="input-group-text bg-transparent" id="togglePassword" style="cursor:pointer;">
                                <i class="bi bi-eye"></i>
                            </span>
                        </div>
                    </div>

                    <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                        <div></div>
                        <a href="<?php echo base_url(); ?>user/forgot" class="link-primary"><?php echo lang('forgot_password'); ?></a>
                    </div>

                    <div class="d-grid mb-8">
                        <button type="submit" id="kt_sign_in_submit" class="btn btn-primary">
                            <span class="indicator-label"><?php echo lang('sign_in'); ?></span>
                            <span class="indicator-progress"><?php echo lang('please_wait'); ?>
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>

                    <div class="text-gray-500 text-center fw-semibold fs-6">
                        <?php echo lang('Not_a_Member_yet'); ?>
                        <a href="<?php echo base_url(); ?>user/re" class="link-primary"><?php echo lang('Sign_up'); ?></a>
                    </div>
                </form>
                <!--end::Form-->
            <?php } ?>

            <?php if($verify_type != '1') { $this->session->unset_userdata('sender_otp'); ?>
                <!--begin::Form-->
                <form class="form w-100 mb-5" novalidate="novalidate" action="<?php echo base_url();?>user/login-finel-verify" method="POST" id="kt_sing_in_two_factor_form">
                    <div class="text-center mb-8">
                        <img alt="Logo" class="mh-100px" src="<?php echo base_url();?>/assets/admin/media/svg/misc/smartphone-2.svg">
                    </div>
                    <div class="text-center mb-8">
                        <h1 class="text-gray-900 mb-3"><?php echo lang('two_factor_title'); ?></h1>
                        <div class="text-muted fw-semibold fs-5 mb-3"><?php echo lang('two_factor_subtitle'); ?></div>
                        <div class="fw-bold text-gray-900 fs-3"><?php echo isset($admin_mail)?$admin_mail:''; ?></div>
                    </div>

                    <div class="mb-8">
                        <div class="fw-bold text-start text-gray-900 fs-6 mb-1 ms-1"><?php echo lang('two_factor_input_label'); ?></div>
                        <div class="otp-container">
                            <div class="d-flex flex-wrap flex-stack">
                                <input type="text" name="twofa_code_1" maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 fa-code" value="" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*">
                                <input type="text" name="twofa_code_2" maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 fa-code" value="" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*">
                                <input type="text" name="twofa_code_3" maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 fa-code" value="" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*">
                                <input type="text" name="twofa_code_4" maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 fa-code" value="" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*">
                                <input type="text" name="twofa_code_5" maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 fa-code" value="" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*">
                                <input type="text" name="twofa_code_6" maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 fa-code" value="" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*">
                            </div>
                            <span class="otp-loader d-none">⏳ Verifying...</span>
                            <p class="otp-message"></p>
                        </div>
                        <p>OTP:123456</p>
                    </div>

                    <div class="mb-8">
                        <div class="fw-bold text-start text-gray-900 fs-6 mb-1 ms-1"><?php echo lang('email_otp_input_label'); ?></div>
                        <div class="otp-container">
                            <div class="d-flex flex-wrap flex-stack">
                                <input type="text" name="email_code_1" maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 email-code" value="" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*">
                                <input type="text" name="email_code_2" maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 email-code" value="" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*">
                                <input type="text" name="email_code_3" maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 email-code" value="" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*">
                                <input type="text" name="email_code_4" maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 email-code" value="" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*">
                                <input type="text" name="email_code_5" maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 email-code" value="" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*">
                                <input type="text" name="email_code_6" maxlength="1" class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 email-code" value="" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*">
                            </div>
                            <span class="otp-loader d-none">⏳ Verifying...</span>
                            <p class="otp-message"></p>
                        </div>
                        <p>OTP:123456</p>
                    </div>

                    <div class="d-flex flex-center">
                        <button type="submit" id="kt_sing_in_two_factor_submit" data-kt-redirect-url="<?php echo base_url();?>user/main" disabled class="btn btn-lg btn-primary fw-bold w-100">
                            <span class="indicator-label">Submit</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
                <!--end::Form-->
            <?php } ?>

            <div class="mt-6"><?php $this->load->view('user/layout/auth_footer'); ?></div>

          </div>
        </div>

        <!-- ===================== BRAND SIDE ===================== -->
        <div class="lpx-brand-side">
            <a class="lpx-home" href="<?php echo base_url('landing'); ?>"><i class="bi bi-arrow-left"></i> TAKE ME HOME</a>
            <div class="lpx-brand-inner">
                <a href="<?php echo base_url('landing'); ?>"><img src="<?php echo base_url($lpx_logo); ?>" alt="logo" onerror="this.onerror=null;this.src='<?php echo base_url('assets/img/logo/logo.svg'); ?>';"></a>
                <h2>Start your journey with <?php echo html_escape($lpx_name); ?></h2>
            </div>
        </div>

      </div>
    </div>

    <?php $this->load->view('user/layout/common_script'); ?>
    <script>
    (function () {
        var root = document.documentElement;
        var saved = localStorage.getItem('site-theme') || localStorage.getItem('data-bs-theme') || root.getAttribute('data-bs-theme') || 'dark';
        function resolved(theme) {
            return (theme === 'auto' || theme === 'system') ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : theme;
        }
        function apply(theme) {
            theme = resolved(theme);
            root.setAttribute('data-bs-theme', theme);
            localStorage.setItem('site-theme', theme);
            localStorage.setItem('data-bs-theme', theme);
        }
        apply(saved);
        var toggle = document.getElementById('lpx_theme_toggle');
        if (toggle) toggle.addEventListener('click', function () {
            apply(root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark');
        });
    })();
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
        var _tgl = document.getElementById("togglePassword");
        if (_tgl) _tgl.addEventListener("click", function () {
            const password = document.getElementById("password");
            const icon = this.querySelector("i");
            if (password.type === "password") {
                password.type = "text";
                icon.classList.remove("bi-eye"); icon.classList.add("bi-eye-slash");
            } else {
                password.type = "password";
                icon.classList.remove("bi-eye-slash"); icon.classList.add("bi-eye");
            }
        });
    </script>
    </body>

    </html>
