    <?php $this->load->view('user/layout/common_style'); ?>
    <?php
    // branding for the right-hand panel (design only) — same pattern as login.php / register.php
    $lpx_logo_file = site_settings('image', 'logo');
    $lpx_logo = $lpx_logo_file ? 'assets/images/' . rawurlencode($lpx_logo_file) : 'assets/img/logo/logo.svg';
    $lpx_name = site_settings('meta-settings', 'site-name');
    if (!$lpx_name) { $lpx_name = 'Webze'; }
    ?>

    <body id="kt_body" data-bs-spy="scroll" data-bs-target="#kt_landing_menu" class="bg-body position-relative app-blank">

    <style>
    body{ background:#0b0b23 url('<?php echo base_url();?>assets/img/banner/hero_bg.svg') center center / cover no-repeat fixed; }

    /* ---- Webze split layout (design only) — matches login.php / register.php ---- */
    .lpx-auth{ display:flex; min-height:100vh; align-items:center; justify-content:center; gap:30px;
        max-width:1180px; margin:0 auto; padding:30px 24px; }
    .lpx-form-side{ flex:1 1 0; min-width:0; display:flex; align-items:center; justify-content:center; padding:10px; }
    .lpx-form-inner{ width:100%; max-width:420px; }
    .lpx-brand-side{ flex:1 1 0; min-width:0; position:relative; display:flex; align-items:center; justify-content:center;
        min-height:560px; border-radius:24px; padding:48px;
        /* driven by Admin -> Member Panel Theme (Gradient Start / Gradient End) */
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
    /* primary CTA driven by Member Panel Theme (Primary + Hover Highlight) */
    .lpx-form-side .btn-primary{ background:linear-gradient(135deg, var(--mp-primary, #6D4AFF), var(--mp-hover, #5a3df0)) !important; border:none !important;
        border-radius:40px; height:56px; color:#fff !important; font-weight:700; box-shadow:0 10px 24px rgba(0,0,0,.25); }
    .lpx-form-side .btn-primary:hover{ filter:brightness(1.05); }
    .lpx-form-side .btn-light{ background:rgba(255,255,255,.08) !important; border:1px solid rgba(255,255,255,.14) !important;
        border-radius:40px; height:56px; color:#fff !important; font-weight:700; }
    .lpx-form-side .link-primary{ color: var(--mp-primary, #6D4AFF) !important; }

    @media (max-width: 991px){ .lpx-auth{ flex-direction:column; min-height:auto; } .lpx-brand-side{ display:none; } }
    </style>

    <div class="d-flex flex-column flex-root" id="kt_app_root">
      <div class="lpx-auth">

        <!-- ===================== FORM SIDE ===================== -->
        <div class="lpx-form-side">
          <div class="lpx-form-inner">

            <!--begin::Form-->
            <form class="form w-100" novalidate="novalidate" id="kt_password_reset_form" data-kt-redirect-url="<?php echo base_url(); ?>user/in" action="<?php echo $action; ?>">
                <div class="text-center mb-8">
                    <h1 class="text-gray-900 fw-bolder mb-3">Forgot Password ?</h1>
                    <div class="text-gray-500 fw-semibold fs-6">
                        Enter your registered email and we'll send you instructions to reset your password.
                    </div>
                </div>

                <div class="fv-row mb-8">
                    <input type="text" placeholder="Email" name="email" autocomplete="off" class="form-control bg-transparent" />
                </div>

                <div class="d-flex flex-wrap justify-content-center gap-3 pb-lg-0">
                    <button type="button" id="kt_password_reset_submit" class="btn btn-primary flex-grow-1">
                        <span class="indicator-label">Submit</span>
                        <span class="indicator-progress">Please wait...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                    <a href="<?php echo base_url(); ?>user/in" class="btn btn-light">Cancel</a>
                </div>
            </form>
            <!--end::Form-->

            <div class="mt-6"><?php $this->load->view('user/layout/auth_footer'); ?></div>

          </div>
        </div>

        <!-- ===================== BRAND SIDE ===================== -->
        <div class="lpx-brand-side">
            <a class="lpx-home" href="<?php echo base_url('landing'); ?>"><i class="bi bi-arrow-left"></i> TAKE ME HOME</a>
            <div class="lpx-brand-inner">
                <a href="<?php echo base_url('landing'); ?>"><img src="<?php echo base_url($lpx_logo); ?>" alt="logo" onerror="this.onerror=null;this.src='<?php echo base_url('assets/img/logo/logo.svg'); ?>';"></a>
                <h2>Reset your access to <?php echo html_escape($lpx_name); ?></h2>
            </div>
        </div>

      </div>
    </div>

    <?php $this->load->view('user/layout/common_script'); ?>
    <script>
    const base_url = '<?php echo base_url();?>';
    </script>

    </body>

    </html>
