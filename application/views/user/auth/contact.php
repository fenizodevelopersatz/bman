    <?php $this->load->view('user/layout/common_style'); ?>
    <?php
    // branding for the right-hand panel (design only) — same source as login.php
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
    /* .form-label / placeholder / file-hint below are hardcoded near-white
       for the dark shell this page defaults to — light mode never got an
       override, so they render white-on-white (invisible) instead of
       falling back to a theme-aware default like the other auth pages. */
    html[data-bs-theme="light"] .lpx-form-side .form-label{ color:#374151 !important; }
    html[data-bs-theme="light"] .lpx-form-side .form-control::placeholder{ color:#98a2b3 !important; }
    html[data-bs-theme="light"] .lpx-form-side .file-hint{ color:#8b93a7 !important; }

    /* ---- split layout (same shell as login.php) ---- */
    .lpx-auth{ display:flex; min-height:100vh; align-items:center; justify-content:center; gap:30px;
        max-width:1180px; margin:0 auto; padding:30px 24px; }
    .lpx-form-side{ flex:1 1 0; min-width:0; display:flex; align-items:center; justify-content:center; padding:10px; }
    .lpx-form-inner{ width:100%; max-width:440px; }
    .lpx-brand-side{ flex:1 1 0; min-width:0; position:relative; display:flex; align-items:center; justify-content:center; overflow:hidden;
        min-height:560px; border-radius:32px; padding:56px 52px;
        background: var(--mp-gradient, linear-gradient(150deg, #6D4AFF 0%, #A855F7 100%));
        box-shadow:0 22px 70px rgba(0,0,0,.30), inset 0 1px 0 rgba(255,255,255,.14); }
    .lpx-brand-side:before{ content:""; position:absolute; inset:18px; border-radius:26px;
        border:1px solid rgba(255,255,255,.12); pointer-events:none; }
    .lpx-brand-inner{ position:relative; z-index:1; padding:36px 28px; text-align:center; max-width:540px; }
    .lpx-brand-inner img{ height:180px; margin-bottom:28px; }
    .lpx-brand-inner h2{ color:#fff; font-weight:800; font-size:32px; line-height:1.2; margin:0; }
    .lpx-brand-inner h2 .lpx-brand-lead{ display:block; white-space:nowrap; }
    .lpx-brand-inner h2 .lpx-brand-name{ display:block; margin-top:4px; }
    .lpx-home{ position:absolute; top:28px; right:34px; z-index:1; color:#fff; font-weight:600; letter-spacing:.5px; text-decoration:none; }
    .lpx-home:hover{ color:#fff; opacity:.85; }
    .lpx-home i{ margin-right:8px; }

    .lpx-form-side, .lpx-form-side h1, .lpx-form-side .text-gray-900, .lpx-form-side .text-gray-800{ color:#fff !important; }
    .lpx-form-side .text-gray-500, .lpx-form-side .text-gray-600{ color:rgba(255,255,255,.65) !important; }
    .lpx-form-side .form-control{ background:rgba(255,255,255,.05) !important; border:1px solid rgba(255,255,255,.14) !important;
        border-radius:14px; height:56px; color:#fff !important; }
    .lpx-form-side textarea.form-control{ height:auto; border-radius:16px; padding:14px 16px; }
    .lpx-form-side .form-control::placeholder{ color:rgba(255,255,255,.45) !important; }
    .lpx-form-side .btn-primary{ background:linear-gradient(135deg, var(--mp-primary, #6D4AFF), var(--mp-hover, #5a3df0)) !important; border:none !important;
        border-radius:40px; height:56px; color:#fff !important; font-weight:700; box-shadow:0 10px 24px rgba(0,0,0,.25); }
    .lpx-form-side .btn-primary:hover{ filter:brightness(1.05); }
    .lpx-form-side .btn-primary:disabled{ opacity:.6; cursor:not-allowed; }
    .lpx-form-side .link-primary{ color: var(--mp-primary, #6D4AFF) !important; }
    .lpx-form-side .form-label{ color:rgba(255,255,255,.85); font-weight:600; font-size:13px; margin-bottom:6px; }
    .lpx-form-side .invalid-feedback{ display:block; color:#ff8a8a; font-size:12.5px; margin-top:4px; }
    .lpx-form-side .file-hint{ color:rgba(255,255,255,.5); font-size:11.5px; margin-top:4px; }

    @media (max-width: 991px){ .lpx-auth{ flex-direction:column; min-height:auto; } .lpx-brand-side{ display:none; } }
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

            <div id="kt_contact_success" class="d-none text-center mb-8">
                <i class="bi bi-check-circle" style="font-size:56px;color:#22c55e;"></i>
                <h1 class="text-gray-900 fw-bolder mt-4 mb-3">Message sent</h1>
                <div class="text-gray-500 fw-semibold fs-6" id="kt_contact_success_text"></div>
                <div class="mt-8"><a href="<?php echo base_url(); ?>user/in" class="link-primary">Back to sign in</a></div>
            </div>

            <form class="form w-100" id="kt_contact_form" novalidate="novalidate" action="<?php echo $action; ?>" enctype="multipart/form-data">
                <div class="text-center mb-8">
                    <h1 class="text-gray-900 fw-bolder mb-3">Contact Support</h1>
                    <div class="text-gray-500 fw-semibold fs-6">
                        Locked out or need help with your account? Send us a message and we'll get back to you.
                    </div>
                </div>

                <div class="fv-row mb-5">
                    <label class="form-label">Registered Email</label>
                    <input type="email" placeholder="you@example.com" name="email" autocomplete="off" class="form-control bg-transparent" />
                    <div class="invalid-feedback d-none" data-error-for="email"></div>
                </div>

                <div class="fv-row mb-5">
                    <label class="form-label">Message</label>
                    <textarea name="message" rows="5" placeholder="Describe the issue — e.g. 'My account was frozen, please help me unlock it.'" class="form-control bg-transparent"></textarea>
                    <div class="invalid-feedback d-none" data-error-for="message"></div>
                </div>

                <div class="fv-row mb-8">
                    <label class="form-label">Attachment (optional)</label>
                    <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf" class="form-control bg-transparent" />
                    <div class="file-hint">JPG, PNG, WEBP or PDF, up to 5MB.</div>
                    <div class="invalid-feedback d-none" data-error-for="attachment"></div>
                </div>

                <div class="d-grid mb-8">
                    <button type="submit" id="kt_contact_submit" class="btn btn-primary">
                        <span class="indicator-label">Submit</span>
                        <span class="indicator-progress d-none">Sending... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </div>

                <div class="text-gray-500 text-center fw-semibold fs-6">
                    <a href="<?php echo base_url(); ?>user/in" class="link-primary">Back to sign in</a>
                </div>
            </form>

          </div>
        </div>

        <!-- ===================== BRAND SIDE ===================== -->
        <div class="lpx-brand-side">
            <a class="lpx-home" href="<?php echo base_url('landing'); ?>"><i class="bi bi-arrow-left"></i> TAKE ME HOME</a>
            <div class="lpx-brand-inner">
                <a href="<?php echo base_url('landing'); ?>"><img src="<?php echo base_url($lpx_logo); ?>" alt="logo" onerror="this.onerror=null;this.src='<?php echo base_url('assets/img/logo/logo.svg'); ?>';"></a>
                <h2><span class="lpx-brand-lead">Start your journey with</span><span class="lpx-brand-name"><?php echo html_escape($lpx_name); ?></span></h2>
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

    (function () {
        var form = document.getElementById('kt_contact_form');
        var submitBtn = document.getElementById('kt_contact_submit');
        var label = submitBtn.querySelector('.indicator-label');
        var progress = submitBtn.querySelector('.indicator-progress');

        function clearErrors() {
            form.querySelectorAll('.invalid-feedback').forEach(function (el) {
                el.classList.add('d-none');
                el.textContent = '';
            });
            form.querySelectorAll('.form-control').forEach(function (el) {
                el.classList.remove('is-invalid');
            });
        }

        function showErrors(errors) {
            if (typeof errors === 'string') {
                Swal.fire({
                    text: errors,
                    icon: 'error',
                    buttonsStyling: false,
                    confirmButtonText: 'Ok, got it!',
                    customClass: { confirmButton: 'btn btn-primary' }
                });
                return;
            }
            Object.keys(errors || {}).forEach(function (field) {
                var box = form.querySelector('[data-error-for="' + field + '"]');
                var input = form.querySelector('[name="' + field + '"]');
                if (box) { box.textContent = errors[field]; box.classList.remove('d-none'); }
                if (input) { input.classList.add('is-invalid'); }
            });
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            clearErrors();

            submitBtn.disabled = true;
            label.classList.add('d-none');
            progress.classList.remove('d-none');

            axios.post(form.getAttribute('action'), new FormData(form), {
                headers: { 'Content-Type': 'multipart/form-data' }
            })
                .then(function (response) {
                    var data = response.data;
                    if (data.status === false) {
                        showErrors(data.errors);
                    } else {
                        form.classList.add('d-none');
                        var success = document.getElementById('kt_contact_success');
                        document.getElementById('kt_contact_success_text').textContent = data.message || 'We will get back to you shortly.';
                        success.classList.remove('d-none');
                    }
                })
                .catch(function () {
                    Swal.fire({
                        text: 'Sorry, something went wrong. Please try again.',
                        icon: 'error',
                        buttonsStyling: false,
                        confirmButtonText: 'Ok, got it!',
                        customClass: { confirmButton: 'btn btn-primary' }
                    });
                })
                .finally(function () {
                    submitBtn.disabled = false;
                    label.classList.remove('d-none');
                    progress.classList.add('d-none');
                });
        });
    })();
    </script>
    </body>

    </html>
