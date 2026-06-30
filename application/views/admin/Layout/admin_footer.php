<?php
$title = site_settings('meta-settings', 'site-title');
?>

<div id="kt_app_footer" class="app-footer text-center">
    <div class=>
        <div class="app-container  container-fluid d-flex flex-column flex-md-row flex-center flex-md-stack py-3 ">
            <div class="text-gray-900 order-2 order-md-1">
                <span class="text-muted fw-semibold me-1">2025&copy;</span>
                <a href="https://www.fenizotechnologies.com" target="_blank"
                    class="text-gray-800 text-hover-primary"><?php echo $title; ?></a>
            </div>
        </div>
    </div>


    <style>
        #demoModal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .6);
            z-index: 9999;
        }

        #demoBox {
            max-width: 420px;
            margin: 7% auto;
            background: #fff;
            border-radius: 12px;
            padding: 18px;
            font-family: Arial;
        }

        #demoBox input {
            width: 100%;
            padding: 10px;
            margin: 6px 0;
            border: 1px solid #ddd;
            border-radius: 10px;
        }

        #demoBox button {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
        }

        #demoMsg {
            margin-top: 10px;
            font-size: 14px;
        }
    </style>

    <div id="demoModal">
        <div id="demoBox">
            <h3 style="margin:0 0 10px;">Email Verification</h3>

            <div id="step1">
                <input id="d_name" placeholder="Your name">
                <input id="d_email" placeholder="Your email">
                <input id="d_phone" placeholder="Your phone">
                <button id="btnSend" onclick="demoStart()">Send OTP</button>
            </div>

            <div id="step2" style="display:none;">
                <div style="margin:8px 0;">Enter the 6-digit OTP sent to your email</div>
                <input id="d_otp" maxlength="6" inputmode="numeric" placeholder="123456">
                <button id="btnVerify" onclick="demoVerify()">Verify</button>
                <button id="btnResend" onclick="demoStart()" style="margin-top:6px;">Resend OTP</button>
            </div>

            <div id="demoMsg"></div>
        </div>
    </div>
    <!-- ✅ Email Verify Popup (FULL FRONTEND SOURCE) -->
    <style>
        #demoModal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .6);
            z-index: 9999;
        }

        #demoBox {
            max-width: 420px;
            margin: 7% auto;
            background: #fff;
            border-radius: 12px;
            padding: 18px;
            font-family: Arial;
        }

        #demoBox input {
            width: 100%;
            padding: 10px;
            margin: 6px 0;
            border: 1px solid #ddd;
            border-radius: 10px;
            outline: none;
        }

        #demoBox button {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
        }

        #demoBox button:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        #demoMsg {
            margin-top: 10px;
            font-size: 14px;
        }
    </style>

    <div id="demoModal">
        <div id="demoBox">
            <h3 style="margin:0 0 10px;">Email Verification</h3>

            <!-- STEP 1 -->
            <div id="step1">
                <input id="d_name" placeholder="Your name" autocomplete="name">
                <input id="d_email" placeholder="Your email" autocomplete="email">
                <div class="hint">Allowed: Gmail, Yahoo, Zoho, Hotmail/Outlook only</div>
                <input id="d_phone" placeholder="Your phone" autocomplete="tel" inputmode="numeric">
                <button id="btnSend" onclick="demoStart()">Send OTP</button>
            </div>

            <!-- STEP 2 -->
            <div id="step2" style="display:none;">
                <div style="margin:8px 0;">Enter the 6-digit OTP sent to your email</div>
                <input id="d_otp" maxlength="6" inputmode="numeric" placeholder="123456" autocomplete="one-time-code">
                <button id="btnVerify" onclick="demoVerify()">Verify</button>

                <button id="btnResend" onclick="demoResend()" style="margin-top:6px;">Resend OTP</button>
                <div class="hint" id="timerText"></div>
            </div>

            <div id="demoMsg"></div>
        </div>
    </div>

    <script>
        const baseUrl = "<?= base_url(); ?>";

        // ✅ auto detect role by url (edit if your admin url is different)
        const role = 'admin';

        // ✅ allowed providers (blocks yopmail.com)
        const ALLOWED_DOMAINS = [
            "gmail.com",
            "yahoo.com",
            "yahoo.in",
            "yahoo.co.in",
            "zoho.com",
            "zohomail.com",
            "zohomail.in",
            "hotmail.com",
            "outlook.com",
            "live.com"
        ];

        // ✅ popup delay
        const POPUP_DELAY_MS = 3000;

        // ✅ OTP expiry that we send to backend (seconds)
        let ttlSec = 120;

        // ✅ resend cooldown UI (seconds)
        let resendCooldown = 30;
        let resendInterval = null;

        function showModal() { document.getElementById('demoModal').style.display = 'block'; }
        function hideModal() { document.getElementById('demoModal').style.display = 'none'; }

        function msg(text, ok = false) {
            const el = document.getElementById('demoMsg');
            el.style.color = ok ? 'green' : '#d00';
            el.innerText = text || '';
        }

        function isAllowedEmail(email) {
            email = (email || "").trim().toLowerCase();
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return false;
            const domain = email.split("@").pop();
            return ALLOWED_DOMAINS.includes(domain);
        }

        function onlyDigits(str) { return (str || '').replace(/[^\d]/g, ''); }

        async function checkDemoPopup() {
            try {
                const res = await fetch(`${baseUrl}GlobalVerify/status?role=${encodeURIComponent(role)}`, {
                    credentials: 'include'
                });
                const data = await res.json();

                if (data.status && data.showPopup) {
                    setTimeout(() => showModal(), POPUP_DELAY_MS);
                }
            } catch (e) {
                console.log(e);
            }
        }

        async function demoStart() {
            msg('');

            const name = document.getElementById('d_name').value.trim();
            const email = document.getElementById('d_email').value.trim().toLowerCase();
            const phone = onlyDigits(document.getElementById('d_phone').value.trim());

            if (!name || !email || !phone) {
                msg("Please enter name, email and phone");
                return;
            }

            if (!isAllowedEmail(email)) {
                msg("Only Gmail, Yahoo, Zoho, Hotmail/Outlook emails are allowed");
                return;
            }

            const form = new URLSearchParams();
            form.append('name', name);
            form.append('email', email);
            form.append('phone', phone);
            form.append('role', role);
            form.append('ttl', ttlSec); // backend will clamp if needed

            const btn = document.getElementById('btnSend');
            if (btn) btn.disabled = true;

            try {
                const res = await fetch(`${baseUrl}GlobalVerify/start`, {
                    method: "POST",
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: form.toString()
                });
                const data = await res.json();

                if (data.status) {
                    document.getElementById('step1').style.display = 'none';
                    document.getElementById('step2').style.display = 'block';
                    msg("OTP sent. Please check your email.", true);

                    // backend may clamp expiry; sync UI
                    if (data.expiresInSec) {
                        ttlSec = parseInt(data.expiresInSec, 10) || ttlSec;
                    }

                    startResendCooldown();
                } else {
                    msg(data.msg || "Error sending OTP");
                }
            } catch (e) {
                msg("Network error");
            } finally {
                if (btn) btn.disabled = false;
            }
        }

        async function demoVerify() {
            msg('');
            const otp = onlyDigits(document.getElementById('d_otp').value.trim());

            if (!/^\d{6}$/.test(otp)) {
                msg("Enter valid 6-digit OTP");
                return;
            }

            const form = new URLSearchParams();
            form.append('otp', otp);
            form.append('role', role);

            const btn = document.getElementById('btnVerify');
            if (btn) btn.disabled = true;

            try {
                const res = await fetch(`${baseUrl}GlobalVerify/verify`, {
                    method: "POST",
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: form.toString()
                });
                const data = await res.json();

                if (data.status && data.verified) {
                    hideModal(); // ✅ never show again after verified
                } else {
                    msg(data.msg || "Invalid OTP");
                }
            } catch (e) {
                msg("Network error");
            } finally {
                if (btn) btn.disabled = false;
            }
        }

        function startResendCooldown() {
            const btnResend = document.getElementById('btnResend');
            const timerEl = document.getElementById('timerText');

            let left = resendCooldown;
            if (btnResend) btnResend.disabled = true;
            if (timerEl) timerEl.innerText = `You can resend OTP in ${left}s`;

            if (resendInterval) clearInterval(resendInterval);

            resendInterval = setInterval(() => {
                left--;
                if (timerEl) timerEl.innerText = left > 0 ? `You can resend OTP in ${left}s` : '';
                if (left <= 0) {
                    clearInterval(resendInterval);
                    resendInterval = null;
                    if (btnResend) btnResend.disabled = false;
                }
            }, 1000);
        }

        function demoResend() {
            // resend is same as start
            demoStart();
        }

        // ✅ run on page load with delay
        document.addEventListener("DOMContentLoaded", () => {
            setTimeout(checkDemoPopup, 3000); // small wait for UI render
        });
    </script>