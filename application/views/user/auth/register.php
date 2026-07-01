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

    /* ---- Webze split layout (design only) ---- */
    .lpx-auth{ display:flex; min-height:100vh; align-items:center; justify-content:center; gap:30px;
        max-width:1180px; margin:0 auto; padding:30px 24px; }
    .lpx-form-side{ flex:1 1 0; min-width:0; display:flex; align-items:center; justify-content:center; padding:10px; }
    .lpx-form-inner{ width:100%; max-width:430px; }
    .lpx-brand-side{ flex:1 1 0; min-width:0; position:relative; display:flex; align-items:center; justify-content:center;
        min-height:620px; border-radius:24px; padding:48px;
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
    .lpx-form-side, .lpx-form-side h1, .lpx-form-side .text-gray-900, .lpx-form-side .text-gray-800, .lpx-form-side .text-gray-700{ color:#fff !important; }
    .lpx-form-side .text-gray-500, .lpx-form-side .text-gray-600, .lpx-form-side .text-muted{ color:rgba(255,255,255,.65) !important; }
    .lpx-form-side .form-control{ background:rgba(255,255,255,.05) !important; border:1px solid rgba(255,255,255,.14) !important;
        border-radius:14px; height:56px; color:#fff !important; }
    /* NEW: primary CTA driven by Member Panel Theme (Primary + Hover Highlight) */
    .lpx-form-side .btn-primary{ background:linear-gradient(135deg, var(--mp-primary, #6D4AFF), var(--mp-hover, #5a3df0)) !important; border:none !important;
        border-radius:40px; height:56px; color:#fff !important; font-weight:700; box-shadow:0 10px 24px rgba(0,0,0,.25); }
    .lpx-form-side .btn-primary:hover{ filter:brightness(1.05); }
    .lpx-form-side .link-primary{ color: var(--mp-primary, #6D4AFF) !important; }
    .lpx-form-side [data-kt-password-meter-control="visibility"] i{ color:#fff; }
    .lpx-hide{ display:none !important; }

    @media (max-width: 991px){ .lpx-auth{ flex-direction:column; min-height:auto; } .lpx-brand-side{ display:none; } }
    </style>

    <div class="d-flex flex-column flex-root" id="kt_app_root">
      <div class="lpx-auth">

        <!-- ===================== FORM SIDE ===================== -->
        <div class="lpx-form-side">
          <div class="lpx-form-inner">

            <!--begin::Form-->
            <form class="form w-100" novalidate="novalidate" id="kt_sign_up_form" data-kt-redirect-url="<?php echo base_url();?>user/in" action="<?php echo $action; ?>">
                <div class="text-center mb-8">
                    <h1 class="text-gray-900 fw-bolder mb-3"><?php echo lang('sign_up'); ?></h1>
                    <div class="text-gray-500 fw-semibold fs-6"><?php echo lang('your_social_campaings'); ?></div>
                </div>

                <!-- kept for functionality, hidden to match the design -->
                <div class="lpx-hide">
                    <div class="row g-3 mb-9">
                        <div class="col-md-12">
                            <a href="#" class="btn btn-flex btn-outline btn-text-gray-700 btn-active-color-primary bg-state-light flex-center text-nowrap w-100" id="google-signin-btn">
                                <img alt="Logo" src="<?php echo base_url();?>assets/user/media/svg/brand-logos/google-icon.svg" class="h-15px me-3" /> Sign in with Google
                            </a>
                        </div>
                    </div>
                    <div class="separator separator-content my-14"><span class="w-125px text-gray-500 fw-semibold fs-7">Or with email</span></div>
                </div>

                <div class="fv-row mb-5">
                    <input type="text" placeholder="<?php echo lang('sponsor_id'); ?>" autocomplete="off" name="sponsor_id" class="form-control bg-transparent" value="<?= htmlspecialchars($ref_raw ?? '') ?>" readonly>
                    <input type="hidden" name="select_lg" value="<?= htmlspecialchars($ref_leg ?? '') ?>">
                </div>

                <div class="fv-row mb-5">
                    <input type="text" placeholder="<?php echo lang('username'); ?>" name="username" autocomplete="off" class="form-control bg-transparent" />
                </div>

                <div class="fv-row mb-5">
                    <input type="text" placeholder="<?php echo lang('useremail'); ?>" name="useremail" autocomplete="off" class="form-control bg-transparent" />
                </div>

                <!--begin::Input group (password meter)-->
                <div class="fv-row mb-5" data-kt-password-meter="true">
                    <div class="mb-1">
                        <div class="position-relative mb-3">
                            <input class="form-control bg-transparent" type="password" placeholder="Password" name="password" autocomplete="off" />
                            <span class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2" data-kt-password-meter-control="visibility">
                                <i class="ki-outline ki-eye-slash fs-2"></i>
                                <i class="ki-outline ki-eye fs-2 d-none"></i>
                            </span>
                        </div>
                        <div class="d-flex align-items-center mb-3" data-kt-password-meter-control="highlight">
                            <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                            <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                            <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                            <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px"></div>
                        </div>
                    </div>
                    <div class="text-muted"><?php echo lang('use_password_hint'); ?></div>
                </div>

                <div class="fv-row mb-5">
                    <input type="password" placeholder="<?php echo lang('repeat_password'); ?>" name="confirm-password" autocomplete="off" class="form-control bg-transparent" />
                </div>

                <div class="fv-row mb-7">
                    <label class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="toc" value="1" />
                        <span class="form-check-label fw-semibold text-gray-700 fs-base ms-1">
                            <?php echo lang('accept_terms'); ?> <a href="#" class="ms-1 link-primary"><?php echo lang('terms'); ?></a>
                        </span>
                    </label>
                </div>

                <div class="d-grid mb-8">
                    <button type="submit" id="kt_sign_up_submit" class="btn btn-primary">
                        <span class="indicator-label"><?php echo lang('sign_up'); ?></span>
                        <span class="indicator-progress"><?php echo lang('please_wait'); ?> <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </div>

                <div class="text-gray-500 text-center fw-semibold fs-6">
                    <?php echo lang('already_have_account'); ?>
                    <a href="<?php echo base_url();?>user/in" class="link-primary fw-semibold"><?php echo lang('sign_in'); ?></a>
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
                <h2>Start your journey with <?php echo html_escape($lpx_name); ?></h2>
            </div>
        </div>

      </div>
    </div>

    <?php $this->load->view('user/layout/common_script'); ?>
    <script src="<?php echo base_url();?>assets/user/js/custom/authentication/sign-up/general.js?version=2.4"></script>
    </body>

    </html>
