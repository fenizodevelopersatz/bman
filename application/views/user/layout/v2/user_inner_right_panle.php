<?php
/**
 * Shared right-hand "Profile & Stats" panel (rendered on ~14 member pages).
 *
 * The rank card reads the BMAN Rank Achievement System (§10) + Rank Power (§11)
 * via Memberrank_model::sidebar() — NOT the old pairing engine. There are no
 * pairs, PV, weak-leg BV or carry forward here any more.
 *
 * sidebar() is deliberately the cheap path: it reads the STORED rank volume
 * (refreshed each hourly cron pass) instead of recalculating live, so
 * putting this panel on every page costs a handful of indexed lookups. It also
 * fails soft — if the rank tables are absent it returns an unranked state
 * rather than breaking every page in the member area.
 */
$uid = $this->session->userdata('user_userid') ?? '';
// The top ring is KYC verification progress, not the broader 5-factor
// profile-completeness score (name/address/photo/KYC/bank) — KYC is what
// gates withdraw eligibility, so that's what this widget should reflect.
$profile_percent = kyc_completion_percent($uid);

/**
 * NOTE — use get_instance(), not $this, to load and reach the model here.
 * Inside a CI3 view `$this` is the CI_Loader, which has NO __get(): it only
 * takes a one-time snapshot of the controller's properties *before* including
 * the view. A model loaded from inside the view is attached to the controller,
 * so `$this->rp_rank` would be null even though the load succeeded. get_instance()
 * addresses the controller directly and is correct in any view context.
 */
$CI =& get_instance();
$CI->load->model('user/Memberrank_model', 'rp_rank');
$rk = $CI->rp_rank->sidebar($uid);

$rk_fmt = function ($v) {                  // 12500000 → "1.25 Cr" (Indian notation)
    $v = (float) $v;
    if ($v >= 10000000) return rtrim(rtrim(number_format($v / 10000000, 2), '0'), '.') . ' Cr';
    if ($v >= 100000)   return rtrim(rtrim(number_format($v / 100000, 2), '0'), '.') . ' L';
    if ($v >= 1000)     return rtrim(rtrim(number_format($v / 1000, 2), '0'), '.') . ' K';
    return number_format($v, 0);
};
?>
<script>
    window.APP_CONFIG = window.APP_CONFIG || {};
    window.APP_CONFIG.DEMO = <?= (defined('DEMOVERSION') && DEMOVERSION) ? 'true' : 'false' ?>;
    function isDemoMode() { return !!(window.APP_CONFIG && window.APP_CONFIG.DEMO === true); }
    function demoBlockAlert() { Swal.fire({ icon: 'info', title: 'Demo Version', text: 'You Can not change record.', confirmButtonText: 'Ok, got it!', customClass: { confirmButton: 'btn btn-primary' }, buttonsStyling: false }); }
    function stopIfDemo(e) { if (!isDemoMode()) return false; if (e && e.preventDefault) e.preventDefault(); demoBlockAlert(); return true; }
</script>
<div class="rp-title">
    <h4>Profile & Stats</h4>
    <a class="action-btn" title="Settings" style="width:40px;height:40px;"
        href="<?php echo base_url('user/view-profile'); ?>"><i class="ph ph-gear"></i></a>
</div>

<div class="rp-card">
    <!-- <div class="stat-circle">
        <img src="https://i.pravatar.cc/100?u=mlm-user" alt="avatar" />
        <span class="stat-badge">72%</span>
    </div> -->
    <div class="stat-circle" style="background:conic-gradient(var(--primary) <?= (int) $profile_percent ?>%, #f0f0f5 0);">
        <img src="<?= htmlspecialchars(user_profile_image($uid)); ?>"
            alt="avatar"
            <?= avatar_onerror(); ?> />

        <span class="stat-badge">
            <?= $profile_percent ?>%
        </span>
    </div>
    <div style="text-align:center;font-size:10.5px;font-weight:700;color:var(--text-secondary, #8E8E93);margin:0 0 10px;text-transform:uppercase;letter-spacing:.4px;">
        KYC Verification
    </div>

    <div class="hello">
        <!-- <h3>Good Morning Lucas</h3> -->
        <h3>
            <span id="greeting">Hello</span>
            <span>
                <?= $this->session->userdata('user_full_name')
                    ? ucwords(strtolower($this->session->userdata('user_full_name')))
                    : 'Lucas'; ?>
            </span>
        </h3>

        <script>
            (() => {
                const h = new Date().getHours();
                document.getElementById('greeting').textContent =
                    h >= 5 && h < 12 ? 'Good Morning' :
                        h >= 12 && h < 17 ? 'Good Afternoon' :
                            h >= 17 && h < 21 ? 'Good Evening' :
                                'Good Night';
            })();
        </script>

        <p>Track your rank, grow your rank volume, and withdraw earnings easily.</p>

        <div class="pill">
            <b>Next Payout</b>
            <span>
                <?php echo NEXT_PAYOUT_TIME; ?>
            </span>
        </div>

        <div class="pill">
            <b>Withdraw Eligibility</b>

            <span style="color:<?= is_withdraw_eligible($uid) ? 'var(--good)' : 'var(--bad)' ?>;">
                <?= is_withdraw_eligible($uid) ? 'Eligible' : 'Not Eligible' ?>
            </span>

        </div>
    </div>
</div>


<div class="rp-card rank-card-pro">
    <style>
        .rank-card {
            display: flex;
            justify-content: space-between;
            background: #fff;
            border-radius: 16px;
            width: 100%;
            max-width: 420px;
        }

        .rank-badge {
            background: #000;
            color: #fff;
            border-radius: 20px;
            font-weight: 600;
        }

        .label {
            font-size: 13px;
            color: #888;
        }

        .desc {
            font-size: 12px;
            color: #777;
        }

        .progress-circle {
            width: 90px;
        }

        .bg {
            fill: none;
            stroke: #eee;
            stroke-width: 3.8;
        }

        .progress {
            fill: none;
            stroke: #7b4dff;
            stroke-width: 3.8;
            stroke-linecap: round;
        }

        .percentage {
            fill: #333;
            font-size: 6px;
            text-anchor: middle;
        }
    </style>
    <style>
        /* Rank card — achievement rank (permanent) + rank power (60-day cycle) */
        .rank-perm {
            display:inline-flex; align-items:center; gap:5px; font-size:9.5px; font-weight:700;
            color:#15803d; background:#dcfce7; padding:3px 7px; border-radius:999px; margin-top:5px;
        }
        .rank-vol { display:flex; justify-content:space-between; font-size:10.5px; color:#8E8E93; margin:12px 0 5px; }
        .rank-vol b { color:var(--text-main, #1A1A1A); font-variant-numeric:tabular-nums; }
        .rank-bar { height:7px; border-radius:999px; background:#e9e7ff; overflow:hidden; }
        .rank-bar>div { height:100%; background:var(--primary, #6E56CF); border-radius:999px; transition:width .8s ease; }
        .rank-bar.done>div { background:var(--good, #22c55e); }
        .rank-tiles-v2 { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin:12px 0 4px; }
        .rtile { background:#f7f7fb; border-radius:12px; padding:9px 10px; }
        .rtile small { font-size:9px; text-transform:uppercase; letter-spacing:.5px; color:#8E8E93; font-weight:700; display:block; }
        .rtile .v { font-size:13px; font-weight:800; color:var(--text-main, #1A1A1A); margin-top:3px; line-height:1.2; }
        .rtile .v.none { color:#8E8E93; font-size:11.5px; font-weight:700; }
        .rtile .m { font-size:8.5px; font-weight:700; padding:2px 5px; border-radius:999px; background:#efedfb; color:var(--primary, #6E56CF); display:inline-block; margin-top:4px; }
        .rtile .m.ok { background:#dcfce7; color:#15803d; }
        .rtile .m.mut { background:#eee; color:#8E8E93; }
    </style>

    <div class="rank-card">
        <div style="display:flex;align-items:center;gap:12px;min-width:0;">
            <?= rank_badge_html($rk['badge_image'], $rk['badge_color'], 64); ?>
            <div style="min-width:0;">
                <p class="label" style="margin:0;">Current Rank</p>
                <span class="rank-badge"><?= htmlspecialchars($rk['name']); ?></span>
                <?php if ($rk['has_rank']): ?>
                    <span class="rank-perm"><i class="ph-fill ph-lock-key-open"></i> Permanent</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="progress-circle">
            <svg viewBox="0 0 36 36">
                <path class="bg" d="M18 2.0845
                     a 15.9155 15.9155 0 0 1 0 31.831
                     a 15.9155 15.9155 0 0 1 0 -31.831" />
                <path class="progress" stroke-dasharray="<?= (int) $rk['progress']; ?>, 100" d="M18 2.0845
                     a 15.9155 15.9155 0 0 1 0 31.831
                     a 15.9155 15.9155 0 0 1 0 -31.831" />
                <text x="18" y="20.35" class="percentage"><?= (int) $rk['progress']; ?>%</text>
            </svg>
        </div>
    </div>

    <?php if (!$rk['available']): ?>
        <p class="rank-desc">Rank data is not available yet.</p>
    <?php elseif ($rk['next_rank']): ?>
        <p class="rank-desc" style="margin-bottom:0;">
            Grow your <b>rank volume</b> to unlock <b><?= htmlspecialchars($rk['next_rank']); ?></b>.
            <?= $rk['has_rank'] ? 'Your ' . htmlspecialchars($rk['name']) . ' rank is permanent.' : ''; ?>
        </p>

        <!-- Rank volume toward the next rank -->
        <div class="rank-vol">
            <span>Rank volume</span>
            <span><b><?= $rk_fmt($rk['group_volume']); ?></b> / <?= $rk_fmt($rk['required_volume']); ?></span>
        </div>
        <div class="rank-bar <?= $rk['progress'] >= 100 ? 'done' : ''; ?>">
            <div style="width:<?= (int) min(100, $rk['progress']); ?>%"></div>
        </div>
    <?php else: ?>
        <p class="rank-desc" style="margin-bottom:0;">
            <i class="ph-fill ph-crown" style="color:var(--primary)"></i>
            You hold <b>CHALLENGER</b> — the highest rank. It is permanent.
        </p>
    <?php endif; ?>

    <!-- Rank Power + Group Incentive -->
    <div class="rank-tiles-v2">
        <div class="rtile">
            <small>Rank Power</small>
            <div class="v <?= $rk['power_rank'] ? '' : 'none'; ?>">
                <?= $rk['power_rank'] ? htmlspecialchars($rk['power_rank']) : 'None yet'; ?>
            </div>
            <span class="m <?= $rk['days_left'] !== null ? '' : 'mut'; ?>">
                <?= $rk['days_left'] !== null ? (int) $rk['days_left'] . 'd left' : 'No cycle'; ?>
            </span>
        </div>

        <div class="rtile">
            <small>Group Incentive</small>
            <div class="v <?= $rk['power_qualified'] ? '' : 'none'; ?>">
                <?= $rk['power_qualified'] ? $rk_fmt($rk['incentive']) : 'Not yet'; ?>
            </div>
            <span class="m <?= $rk['power_qualified'] ? 'ok' : 'mut'; ?>">
                <?= $rk['power_qualified'] ? 'Eligible' : 'Build volume'; ?>
            </span>
        </div>
    </div>

    <button class="rank-btn-pro" onclick="window.location.href='<?php echo base_url('user/rank-reward'); ?>'">
        View Rank Details <i class="ph ph-arrow-right"></i></button>
</div>


<div class="rp-card alerts">
    <div class="panel-title" style="margin-bottom:8px;">
        <h3 style="font-size:14px;">Action Center</h3>
        <span class="chip">Important</span>
    </div>    

    <div class="a">
        <div class="ic"><i class="ph ph-identification-card"></i></div>
        <div>
            <b>KYC Verified</b>
            <small>Your account is eligible for withdrawals.</small>
        </div>
    </div>
    
    <button class="btn-full" onclick="window.location.href='<?php echo base_url('user/main'); ?>'">Go to
        Tasks</button>
</div>

<div class="rp-card">
    <div class="panel-title" style="margin-bottom:10px;">
        <h3 style="font-size:14px;">Quick Support</h3>
        <span class="chip">24/7</span>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
        <div
            style="width:40px;height:40px;border-radius:14px;background:#efedfb;color:var(--primary);display:grid;place-items:center;">
            <i class="ph ph-headset" style="font-size:20px;"></i>
        </div>
        <div>
            <b style="font-size:13px;">Help Desk</b>
            <small style="display:block;font-size:11px;color:var(--text-muted);margin-top:2px;">Raise a ticket for
                payouts, orders, team issues.</small>
        </div>
    </div>
    <button class="btn-full" style="margin-top:12px;background:var(--primary);color:#fff;"
        onclick="window.location.href='<?php echo base_url('user/support'); ?>'">Create Ticket</button>
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

    .hint {
        font-size: 12px;
        color: #666;
        margin-top: 2px;
    }

    .row {
        /* display: flex; */
        gap: 10px;
    }

    .row>div {
        flex: 1;
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
            <input id="d_phone" placeholder="Your phone 1234567891" maxlength="15" autocomplete="tel"
                inputmode="numeric">
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
    const role = window.location.pathname.toLowerCase().includes('/admin') ? 'admin' : 'user';

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