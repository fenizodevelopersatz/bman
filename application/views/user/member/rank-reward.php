<?php
/**
 * Member ▸ Rank & Rewards — BMAN Rank Achievement System (§10) + Rank Power (§11)
 * ----------------------------------------------------------------------------
 * Data from Memberrank_model::pageData(). Tables behind it: staking_ranks,
 * staking_rank_requirements, user_ranks, user_rank_power, user_rank_history,
 * rank_rewards, rank_certificates, staking_swap_orders.
 *
 * There is NO pairing logic on this page. No pairs, no PV, no weak-leg BV, no
 * carry forward, no cycle matching, no weekly pairing — the new system has no
 * such concepts. Rank is earned on downline group volume plus a left/right
 * team-rank matrix, and once earned it is permanent.
 *
 * Sections: 1 Achievement Rank · 2 Next Rank Progress · 3 Qualification Plans ·
 * 4/5/6 Left / Right / Total Group Volume · 7 Rank Power · 8 Group Incentive ·
 * 9 Rewards History · 10 Certificates · 11 Journey Timeline · 12 Badge Wall.
 *
 * @var array      $rank, $volume, $power, $incentive, $ladder, $history, $rewards, $certificates
 * @var array|null $next
 */

/* ---- view helpers (display only — no business logic lives here) ---- */
$rk_n = function ($v, $dec = 2) {                    // 1234567.8 → "1,234,567.80"
    return number_format((float) $v, $dec);
};
$rk_k = function ($v) {                              // 12500000 → "1.25 Cr" (Indian notation)
    $v = (float) $v;
    if ($v >= 10000000) return rtrim(rtrim(number_format($v / 10000000, 2), '0'), '.') . ' Cr';
    if ($v >= 100000)   return rtrim(rtrim(number_format($v / 100000, 2), '0'), '.') . ' L';
    if ($v >= 1000)     return rtrim(rtrim(number_format($v / 1000, 2), '0'), '.') . ' K';
    return number_format($v, 0);
};
$rk_badge = function ($img, $color, $size = 42) {    // badge art, or a colour dot fallback
    if ($img) {
        return '<img class="rk-badge-img" style="width:' . $size . 'px;height:' . $size . 'px"'
            . ' src="' . base_url($img) . '" alt="" loading="lazy">';
    }
    return '<span class="rk-badge-dot" style="width:' . $size . 'px;height:' . $size . 'px;background:'
        . htmlspecialchars($color ?: '#9e9e9e') . '"></span>';
};

$hasNext  = !empty($next);
$volPct   = $hasNext ? (float) $next['volume_percent'] : 100;
$volMet   = $hasNext ? !empty($next['volume_met']) : true;
$planMet  = $hasNext ? !empty($next['plan_met']) : true;
$leftVol  = (float) $volume['left_volume'];
$rightVol = (float) $volume['right_volume'];
$totalVol = (float) $volume['total_volume'];
$legMax   = max($leftVol, $rightVol, 1);             // for the leg bar widths only
$rewardCnt = count($rewards);
$certCnt   = count($certificates);
$earnedCnt = count(array_filter($ladder, function ($l) { return $l['achieved']; }));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Rank &amp; Rewards</title>
    <?php $this->load->view('user/layout/v2/user_style'); ?>
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css" />
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/fill/style.css" />

    <style>
        :root {
            --p: var(--primary, #6E56CF);
            --txt: var(--text-main, #111);
            --muted: var(--text-muted, #6b7280);
            --card: #fff;
            --stroke: #f1f1f6;
            --soft: #f7f7fb;
            --shadow: 0 12px 30px rgba(0, 0, 0, .04);
            --r: 22px;
            --ok: var(--good, #22c55e);
            --wrn: var(--warn, #f59e0b);
        }

        /* ---------------- titlebar ---------------- */
        .titlebar { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:18px; }
        .titlebar h1 { font-size:22px; font-weight:800; color:var(--txt); display:flex; align-items:center; gap:8px; margin:0; }
        .titlebar h1 i { color:var(--p); }
        .titlebar p { margin:4px 0 0; font-size:12.5px; color:var(--muted); }
        .actions { display:flex; gap:8px; flex-wrap:wrap; }
        .btn-soft, .btn-main, .btn-dark {
            border:0; cursor:pointer; font-family:inherit; font-weight:700; font-size:12.5px;
            padding:10px 14px; border-radius:12px; display:inline-flex; align-items:center; gap:7px;
            text-decoration:none; transition:transform .12s ease;
        }
        .btn-soft { background:var(--soft); color:var(--txt); border:1px solid var(--stroke); }
        .btn-main { background:var(--p); color:#fff; }
        .btn-dark { background:#101014; color:#fff; }
        .btn-soft:hover, .btn-main:hover, .btn-dark:hover { transform:translateY(-1px); }

        /* ---------------- 1. hero / achievement rank ---------------- */
        .hero {
            position:relative; overflow:hidden; border-radius:var(--r); padding:26px; color:#fff; margin-bottom:18px;
            background:linear-gradient(135deg, var(--p) 0%, #3b2a7e 62%, #241a52 100%);
        }
        .hero:before, .hero:after { content:''; position:absolute; border-radius:50%; background:rgba(255,255,255,.06); pointer-events:none; }
        .hero:before { width:340px; height:340px; right:-90px; top:-140px; }
        .hero:after { width:220px; height:220px; right:60px; bottom:-130px; }
        .hero-top { position:relative; display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:18px; }
        .hero-badge {
            display:inline-flex; align-items:center; gap:8px; background:rgba(255,255,255,.14);
            border:1px solid rgba(255,255,255,.18); padding:8px 14px; border-radius:999px;
            font-size:12px; font-weight:700;
        }
        .hero-badge .gold { color:#ffd76e; }
        .hero-grid { position:relative; display:grid; grid-template-columns:1.35fr .65fr; gap:22px; align-items:center; }
        .hero-title { font-size:29px; line-height:1.18; font-weight:800; margin:0 0 10px; letter-spacing:-.4px; }
        .hero-title span { color:#ffd76e; }
        .hero-sub { font-size:13px; line-height:1.65; opacity:.92; margin:0 0 8px; max-width:560px; }
        .hero-perm {
            display:inline-flex; align-items:center; gap:8px; margin:8px 0 16px; background:rgba(255,255,255,.12);
            border:1px solid rgba(255,255,255,.16); padding:8px 12px; border-radius:12px; font-size:12px; font-weight:600;
        }
        .hero-perm i { color:#ffd76e; font-size:15px; }
        .hero-identity { display:flex; align-items:center; gap:14px; margin-bottom:14px; }
        .hero-identity .rk-badge-img, .hero-identity .rk-badge-dot { box-shadow:0 8px 22px rgba(0,0,0,.28); }
        .hero-identity h2 { margin:0; font-size:25px; font-weight:800; letter-spacing:.5px; }
        .hero-identity small { display:block; opacity:.8; font-size:11.5px; font-weight:600; margin-top:2px; }

        .mini-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:18px; }
        .mini { background:rgba(255,255,255,.11); border:1px solid rgba(255,255,255,.15); border-radius:16px; padding:13px 14px; }
        .mini .sicon { width:30px; height:30px; border-radius:9px; background:rgba(255,255,255,.16); display:grid; place-items:center; margin-bottom:9px; font-size:15px; }
        .mini .pcap { font-size:10px; letter-spacing:.9px; text-transform:uppercase; opacity:.78; font-weight:700; }
        .mini .amt { font-size:19px; font-weight:800; margin:3px 0 1px; letter-spacing:-.3px; }
        .mini .note { font-size:10.5px; opacity:.72; }
        .hero-actions { display:flex; gap:10px; flex-wrap:wrap; }
        .btn-hero { background:#101014 !important; color:#fff !important; border:0 !important; }
        .btn-ghost { background:rgba(255,255,255,.14) !important; color:#fff !important; border:1px solid rgba(255,255,255,.2) !important; }

        /* 2. next rank progress ring */
        .progress-card { background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.16); border-radius:20px; padding:20px; text-align:center; }
        .ringWrap { position:relative; width:170px; height:170px; margin:0 auto 12px; }
        .ringSvg { transform:rotate(-90deg); }
        .ringTrack { fill:none; stroke:rgba(255,255,255,.18); stroke-width:9; }
        .ringBar { fill:none; stroke:#fff; stroke-width:9; stroke-linecap:round; transition:stroke-dashoffset 1s ease; }
        .ringCenter { position:absolute; inset:0; display:grid; place-items:center; }
        .ringCenter b { font-size:35px; font-weight:800; line-height:1; letter-spacing:-1px; }
        .ringCenter small { display:block; font-size:9.5px; letter-spacing:1.4px; opacity:.78; margin-top:5px; font-weight:700; }
        .rpText { font-size:12px; opacity:.85; }
        .rpText b { display:block; font-size:14px; opacity:1; margin-top:2px; }
        .rpBadges { display:flex; gap:7px; justify-content:center; margin-top:12px; flex-wrap:wrap; }
        .rpBadge { font-size:10.5px; font-weight:700; padding:5px 10px; border-radius:999px; background:rgba(255,255,255,.16); display:inline-flex; align-items:center; gap:5px; }
        .rpBadge.on { background:rgba(34,197,94,.3); }
        .rpBadge.off { background:rgba(255,255,255,.12); opacity:.75; }

        /* ---------------- cards ---------------- */
        .card { background:var(--card); border:1px solid var(--stroke); border-radius:var(--r); box-shadow:var(--shadow); padding:20px; margin-bottom:18px; }
        .card-h { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px; flex-wrap:wrap; }
        .card-h h3 { margin:0; font-size:15px; font-weight:800; color:var(--txt); display:flex; align-items:center; gap:8px; }
        .card-h h3 i { color:var(--p); }
        .chip { font-size:10.5px; padding:6px 11px; border-radius:999px; background:#efedfb; color:var(--p); font-weight:700; display:inline-flex; align-items:center; gap:5px; }
        .chip.ok { background:#e7f8ee; color:#15803d; }
        .chip.warn { background:#fef3e2; color:#b45309; }
        .chip.mut { background:var(--soft); color:var(--muted); }
        .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
        .grid-3 { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; }
        .muted { color:var(--muted); }

        .bar { height:9px; border-radius:999px; background:#e9e7ff; overflow:hidden; }
        .bar>div { height:100%; background:var(--p); border-radius:999px; transition:width .8s ease; }
        .bar.ok>div { background:var(--ok); }
        .bar.wrn>div { background:var(--wrn); }

        /* ---------------- 4/5/6 volume cards ---------------- */
        .vol-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
        .vol-head span { font-size:11px; font-weight:700; letter-spacing:.7px; text-transform:uppercase; color:var(--muted); }
        .vol-ico { width:34px; height:34px; border-radius:10px; display:grid; place-items:center; font-size:16px; }
        .vol-ico.l { background:#eef2ff; color:#4f46e5; }
        .vol-ico.r { background:#ecfdf5; color:#059669; }
        .vol-ico.t { background:#fdf2f8; color:#db2777; }
        .vol-amt { font-size:25px; font-weight:800; color:var(--txt); letter-spacing:-.6px; line-height:1.1; }
        .vol-amt small { font-size:12px; font-weight:700; color:var(--muted); margin-left:4px; }
        .vol-sub { font-size:11px; color:var(--muted); margin-top:8px; line-height:1.5; }

        /* ---------------- 3. qualification plan tracker ---------------- */
        .plan { border:1px solid var(--stroke); border-radius:16px; padding:14px; margin-bottom:10px; background:var(--soft); }
        .plan.met { border-color:#bbf7d0; background:#f2fdf6; }
        .plan-h { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:8px; flex-wrap:wrap; }
        .plan-h b { font-size:12.5px; color:var(--txt); }
        .plan-h .opt { font-size:10.5px; color:var(--muted); font-weight:600; }
        .cond { display:flex; align-items:center; gap:10px; padding:8px 0; border-top:1px dashed var(--stroke); }
        .cond:first-of-type { border-top:0; }
        .cond-ico { width:22px; height:22px; border-radius:50%; display:grid; place-items:center; font-size:11px; flex:0 0 22px; }
        .cond-ico.y { background:#dcfce7; color:#15803d; }
        .cond-ico.n { background:#fee2e2; color:#b91c1c; }
        .cond-txt { flex:1; font-size:12.5px; color:var(--txt); }
        .cond-txt em { font-style:normal; font-weight:700; }
        .cond-cnt { font-size:12px; font-weight:800; font-variant-numeric:tabular-nums; }
        .cond-cnt.y { color:#15803d; }
        .cond-cnt.n { color:#b91c1c; }
        .plan-or { text-align:center; font-size:10px; font-weight:800; color:var(--muted); letter-spacing:1.4px; margin:-2px 0 8px; }

        /* ---------------- 7. rank power ---------------- */
        .pw-head { display:flex; align-items:center; gap:12px; margin-bottom:14px; }
        .pw-name { font-size:21px; font-weight:800; color:var(--txt); letter-spacing:-.4px; }
        .pw-name.none { color:var(--muted); font-size:16px; }
        .pw-cycle { font-size:11px; color:var(--muted); margin-top:2px; }
        .pw-legs { display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin:14px 0; }
        .pw-leg { background:var(--soft); border-radius:12px; padding:10px 12px; }
        .pw-leg small { font-size:10px; text-transform:uppercase; letter-spacing:.6px; color:var(--muted); font-weight:700; }
        .pw-leg b { display:block; font-size:15px; color:var(--txt); margin-top:3px; font-variant-numeric:tabular-nums; }
        .pw-timer { display:flex; align-items:center; justify-content:space-between; font-size:11px; color:var(--muted); margin-bottom:6px; }
        .pw-timer b { color:var(--txt); }

        /* ---------------- 8. group incentive ---------------- */
        .inc-box { border-radius:16px; padding:16px; border:1px solid var(--stroke); }
        .inc-box.on { background:linear-gradient(135deg,#ecfdf5,#f0fdfa); border-color:#bbf7d0; }
        .inc-box.off { background:var(--soft); }
        .inc-amt { font-size:27px; font-weight:800; letter-spacing:-.6px; color:var(--txt); line-height:1.15; }
        .inc-amt small { font-size:12px; font-weight:700; color:var(--muted); }
        .inc-note { font-size:11.5px; color:var(--muted); line-height:1.65; margin:10px 0 0; }

        /* ---------------- 12. badge wall ---------------- */
        .badge-wall { display:grid; grid-template-columns:repeat(auto-fill,minmax(104px,1fr)); gap:12px; }
        .bw { text-align:center; padding:14px 8px; border-radius:16px; border:1px solid var(--stroke); background:var(--soft); position:relative; transition:transform .15s ease, box-shadow .15s ease; }
        .bw:hover { transform:translateY(-3px); box-shadow:var(--shadow); }
        .bw.locked { opacity:.42; filter:grayscale(1); }
        .bw.current { border-color:var(--p); background:#fff; box-shadow:0 0 0 3px rgba(110,86,207,.16); }
        .bw.next { border-color:#fcd34d; background:#fffbeb; }
        .bw .rk-badge-img, .bw .rk-badge-dot { margin:0 auto 8px; display:block; }
        .bw b { font-size:11px; font-weight:800; color:var(--txt); display:block; letter-spacing:.3px; }
        .bw small { font-size:9.5px; color:var(--muted); display:block; margin-top:2px; }
        .bw .flag { position:absolute; top:6px; right:6px; font-size:8.5px; font-weight:800; padding:2px 6px; border-radius:999px; letter-spacing:.4px; }
        .bw .flag.c { background:var(--p); color:#fff; }
        .bw .flag.n { background:#fcd34d; color:#7c2d12; }
        .bw .flag.a { background:#dcfce7; color:#15803d; }

        /* ---------------- 11. timeline ---------------- */
        .tl { position:relative; padding-left:26px; }
        .tl:before { content:''; position:absolute; left:8px; top:6px; bottom:6px; width:2px; background:var(--stroke); }
        .tl-i { position:relative; padding:0 0 18px; }
        .tl-i:last-child { padding-bottom:0; }
        .tl-dot { position:absolute; left:-24px; top:3px; width:18px; height:18px; border-radius:50%; border:3px solid #fff; box-shadow:0 0 0 2px var(--stroke); }
        .tl-i b { font-size:13px; color:var(--txt); }
        .tl-i .when { font-size:10.5px; color:var(--muted); margin-left:6px; }
        .tl-i p { margin:3px 0 0; font-size:11.5px; color:var(--muted); line-height:1.55; }

        /* ---------------- 9/10 tables + certificates ---------------- */
        .filters { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px; }
        .inp, .sel { font-family:inherit; font-size:12px; padding:9px 12px; border-radius:11px; border:1px solid var(--stroke); background:var(--soft); color:var(--txt); outline:none; }
        .inp { flex:1; min-width:180px; }
        .inp:focus, .sel:focus { border-color:var(--p); }
        .table { width:100%; border-collapse:collapse; }
        .table th { text-align:left; font-size:10px; letter-spacing:.7px; text-transform:uppercase; color:var(--muted); font-weight:800; padding:0 10px 10px; border-bottom:1px solid var(--stroke); }
        .table td { padding:12px 10px; font-size:12.5px; color:var(--txt); border-bottom:1px solid var(--stroke); }
        .table tr:last-child td { border-bottom:0; }
        .td-title { font-weight:700; }
        .num { text-align:right; font-variant-numeric:tabular-nums; }
        .mono { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-size:11.5px; }
        .badge { font-size:10px; font-weight:800; padding:4px 9px; border-radius:999px; display:inline-block; letter-spacing:.3px; }
        .b-ok { background:#dcfce7; color:#15803d; }
        .b-warn { background:#fef3c7; color:#b45309; }
        .b-bad { background:#fee2e2; color:#b91c1c; }
        .b-mut { background:var(--soft); color:var(--muted); }
        .empty { text-align:center; padding:34px 16px; color:var(--muted); font-size:12.5px; line-height:1.6; }
        .empty i { font-size:30px; display:block; margin-bottom:8px; opacity:.4; }
        .cert-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(210px,1fr)); gap:12px; }
        .cert { border:1px solid var(--stroke); border-radius:16px; padding:13px; background:var(--soft); display:flex; gap:11px; align-items:center; text-decoration:none; transition:transform .15s ease, box-shadow .15s ease; }
        .cert:hover { transform:translateY(-2px); box-shadow:var(--shadow); background:#fff; }
        .cert b { font-size:12px; color:var(--txt); display:block; }
        .cert .no { font-size:10.5px; color:var(--muted); margin-top:2px; }
        .cert .go { margin-left:auto; color:var(--p); font-size:16px; }

        .rk-badge-img { border-radius:10px; object-fit:contain; background:rgba(255,255,255,.6); }
        .rk-badge-dot { border-radius:50%; display:inline-block; border:2px solid rgba(255,255,255,.5); }

        /* rules modal */
        .modal-backdrop { position:fixed; inset:0; background:rgba(10,10,20,.5); z-index:998; display:none; }
        .modal { position:fixed; z-index:999; inset:0; margin:auto; width:min(660px,92vw); height:max-content; max-height:86vh; overflow:auto; background:#fff; border-radius:22px; padding:22px; display:none; }
        .modal-h { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; }
        .modal-h h3 { margin:0; font-size:17px; font-weight:800; }
        .xbtn { border:0; background:var(--soft); width:34px; height:34px; border-radius:10px; cursor:pointer; font-size:16px; }
        .rule-row { display:flex; gap:12px; padding:11px 0; border-top:1px solid var(--stroke); font-size:12.5px; line-height:1.6; color:var(--txt); }
        .rule-row:first-of-type { border-top:0; }
        .rule-row i { color:var(--p); font-size:17px; flex:0 0 auto; margin-top:1px; }

        /* ---------------- responsive ---------------- */
        @media (max-width:1100px) {
            .hero-grid { grid-template-columns:1fr; }
            .progress-card { max-width:340px; margin:0 auto; }
        }
        @media (max-width:900px) { .grid-2, .grid-3 { grid-template-columns:1fr; } .hero-title { font-size:24px; } }
        @media (max-width:560px) {
            .mini-stats { grid-template-columns:1fr; }
            .pw-legs { grid-template-columns:1fr; }
            .hero { padding:20px; }
            .titlebar h1 { font-size:19px; }
        }
        @media print {
            .sidebar, .right-panel, .actions, .filters, .hero-actions { display:none !important; }
            .card, .hero { break-inside:avoid; box-shadow:none; }
        }
    </style>
</head>

<body>
    <div class="app-container">
        <?php $this->load->view('user/layout/v2/user_sidebar'); ?>

        <main class="main-content">
            <?php $this->load->view('user/layout/v2/user_header'); ?>

            <!-- ================= TITLEBAR ================= -->
            <div class="titlebar">
                <div>
                    <h1><i class="ph-fill ph-medal"></i> Rank &amp; Rewards</h1>
                    <p>Earn your rank on team volume. Once you reach it, it is yours permanently.</p>
                </div>
                <div class="actions">
                    <button class="btn-soft" onclick="rkRules(true)"><i class="ph ph-info"></i> How ranks work</button>
                    <button class="btn-soft" onclick="window.print()"><i class="ph ph-printer"></i> Print</button>
                    <a class="btn-main" href="<?= base_url('user/all-rank'); ?>"><i class="ph ph-trophy"></i> Leaderboard</a>
                    <a class="btn-dark" href="<?= base_url('user/profit'); ?>"><i class="ph ph-wallet"></i> My Earnings</a>
                </div>
            </div>

            <!-- ================= 1. ACHIEVEMENT RANK DASHBOARD ================= -->
            <section class="hero">
                <div class="hero-top">
                    <span class="hero-badge">
                        <i class="ph-fill ph-crown-simple"></i> Achievement Rank:
                        <b><?= htmlspecialchars($rank['name']); ?></b>
                    </span>
                    <?php if ($hasNext): ?>
                        <span class="hero-badge">
                            <i class="ph ph-flag-banner"></i> Next:
                            <b class="gold"><?= htmlspecialchars($next['next_rank']); ?></b>
                        </span>
                    <?php else: ?>
                        <span class="hero-badge"><i class="ph-fill ph-star"></i> <b class="gold">Highest rank reached</b></span>
                    <?php endif; ?>
                </div>

                <div class="hero-grid">
                    <div>
                        <div class="hero-identity">
                            <?= $rk_badge($rank['badge_image'], $rank['badge_color'], 56); ?>
                            <div>
                                <h2><?= htmlspecialchars($rank['name']); ?></h2>
                                <small>
                                    <?php if ($rank['has_rank'] && $rank['achieved_at']): ?>
                                        Achieved <?= date('d M Y', strtotime($rank['achieved_at'])); ?> · permanent
                                    <?php elseif ($rank['has_rank']): ?>
                                        Tier <?= (int) $rank['tier']; ?> · permanent
                                    <?php else: ?>
                                        Build team volume to earn your first rank
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>

                        <?php if ($hasNext): ?>
                            <h1 class="hero-title">Next milestone:<br><span><?= htmlspecialchars($next['next_rank']); ?></span></h1>
                            <p class="hero-sub">
                                <?php if ($volMet && $planMet): ?>
                                    You meet <b>both</b> requirements for <?= htmlspecialchars($next['next_rank']); ?>.
                                    Your rank updates automatically on the next hourly check.
                                <?php elseif (!$volMet && !$planMet): ?>
                                    You need <b><?= $rk_k($next['required_volume'] - $next['current_volume']); ?> BMAN</b>
                                    more team volume, plus a qualifying team on both legs. See the plans below —
                                    <b>any one</b> of them is enough.
                                <?php elseif (!$volMet): ?>
                                    Your team structure already qualifies. You need
                                    <b><?= $rk_k($next['required_volume'] - $next['current_volume']); ?> BMAN</b> more
                                    team volume to unlock <?= htmlspecialchars($next['next_rank']); ?>.
                                <?php else: ?>
                                    Your team volume already qualifies. You now need the team structure — check the
                                    plans below and take whichever route suits your team.
                                <?php endif; ?>
                            </p>
                        <?php else: ?>
                            <h1 class="hero-title">You've reached<br><span>the top rank</span></h1>
                            <p class="hero-sub">CHALLENGER is the highest rank in the BMAN system. It is permanent and
                                yours for good.</p>
                        <?php endif; ?>

                        <div class="hero-perm">
                            <i class="ph-fill ph-lock-key-open"></i>
                            Your rank is <b>permanent</b> — it can never be lost, reduced or expire.
                        </div>

                        <!-- 4 / 5 / 6 at a glance (detail cards below) -->
                        <div class="mini-stats">
                            <div class="mini">
                                <div class="sicon"><i class="ph ph-arrow-bend-down-left"></i></div>
                                <div class="pcap">Left Team Volume</div>
                                <div class="amt"><?= $rk_k($leftVol); ?></div>
                                <div class="note">BMAN · completed staking</div>
                            </div>
                            <div class="mini">
                                <div class="sicon"><i class="ph ph-arrow-bend-down-right"></i></div>
                                <div class="pcap">Right Team Volume</div>
                                <div class="amt"><?= $rk_k($rightVol); ?></div>
                                <div class="note">BMAN · completed staking</div>
                            </div>
                            <div class="mini">
                                <div class="sicon"><i class="ph-fill ph-chart-donut"></i></div>
                                <div class="pcap">Total Group Volume</div>
                                <div class="amt"><?= $rk_k($totalVol); ?></div>
                                <div class="note">Your own staking excluded</div>
                            </div>
                        </div>

                        <div class="hero-actions">
                            <a class="btn-soft btn-hero" href="<?= base_url('user/genealogy'); ?>">
                                <i class="ph ph-users-three"></i> Grow my team</a>
                            <button class="btn-soft btn-ghost" onclick="rkRules(true)">
                                <i class="ph ph-book-open"></i> How ranks work</button>
                        </div>
                    </div>

                    <!-- 2. NEXT RANK PROGRESS -->
                    <div class="progress-card">
                        <?php
                        $R = 74;
                        $C = 2 * M_PI * $R;
                        $off = $C - ($C * min(100, max(0, $volPct)) / 100);
                        ?>
                        <div class="ringWrap">
                            <svg class="ringSvg" width="170" height="170" viewBox="0 0 170 170">
                                <circle class="ringTrack" cx="85" cy="85" r="<?= $R; ?>" />
                                <circle class="ringBar" cx="85" cy="85" r="<?= $R; ?>"
                                    stroke-dasharray="<?= $C; ?>" stroke-dashoffset="<?= $off; ?>" />
                            </svg>
                            <div class="ringCenter">
                                <div>
                                    <b><?= (int) round($volPct); ?>%</b>
                                    <small>VOLUME</small>
                                </div>
                            </div>
                        </div>

                        <?php if ($hasNext): ?>
                            <div class="rpText">Progress to <b><?= htmlspecialchars($next['next_rank']); ?></b></div>
                            <div class="rpText" style="margin-top:6px;font-size:11px;">
                                <?= $rk_k($next['current_volume']); ?> / <?= $rk_k($next['required_volume']); ?> BMAN
                            </div>
                            <div class="rpBadges">
                                <span class="rpBadge <?= $volMet ? 'on' : 'off'; ?>">
                                    <i class="ph <?= $volMet ? 'ph-check-circle' : 'ph-circle-dashed'; ?>"></i> Volume
                                </span>
                                <span class="rpBadge <?= $planMet ? 'on' : 'off'; ?>">
                                    <i class="ph <?= $planMet ? 'ph-check-circle' : 'ph-circle-dashed'; ?>"></i> Team
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="rpText">You hold the <b>highest rank</b></div>
                            <div class="rpBadges"><span class="rpBadge on"><i class="ph-fill ph-crown"></i> CHALLENGER</span></div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- ================= 4 / 5 / 6. TEAM VOLUME ================= -->
            <div class="grid-3">
                <div class="card">
                    <div class="vol-head">
                        <span>Left Team Volume</span>
                        <div class="vol-ico l"><i class="ph-fill ph-arrow-bend-down-left"></i></div>
                    </div>
                    <div class="vol-amt"><?= $rk_n($leftVol); ?><small>BMAN</small></div>
                    <div class="bar" style="margin-top:12px;">
                        <div style="width:<?= (int) round($leftVol / $legMax * 100); ?>%"></div>
                    </div>
                    <div class="vol-sub">Completed staking from your entire left leg, at any depth.</div>
                </div>

                <div class="card">
                    <div class="vol-head">
                        <span>Right Team Volume</span>
                        <div class="vol-ico r"><i class="ph-fill ph-arrow-bend-down-right"></i></div>
                    </div>
                    <div class="vol-amt"><?= $rk_n($rightVol); ?><small>BMAN</small></div>
                    <div class="bar ok" style="margin-top:12px;">
                        <div style="width:<?= (int) round($rightVol / $legMax * 100); ?>%"></div>
                    </div>
                    <div class="vol-sub">Completed staking from your entire right leg, at any depth.</div>
                </div>

                <div class="card">
                    <div class="vol-head">
                        <span>Total Group Volume</span>
                        <div class="vol-ico t"><i class="ph-fill ph-chart-donut"></i></div>
                    </div>
                    <div class="vol-amt"><?= $rk_n($totalVol); ?><small>BMAN</small></div>
                    <div class="bar <?= $volMet ? 'ok' : ''; ?>" style="margin-top:12px;">
                        <div style="width:<?= (int) round(min(100, $volPct)); ?>%"></div>
                    </div>
                    <div class="vol-sub">
                        <?php if (!$hasNext): ?>
                            All volume targets met.
                        <?php elseif ($volMet): ?>
                            Enough for <?= htmlspecialchars($next['next_rank']); ?>.
                        <?php else: ?>
                            <?= $rk_n($next['required_volume'] - $next['current_volume']); ?> more BMAN for
                            <?= htmlspecialchars($next['next_rank']); ?>.
                        <?php endif; ?>
                        Your own staking is never counted.
                    </div>
                </div>
            </div>

            <div class="grid-2">
                <!-- ================= 3. QUALIFICATION PLAN TRACKER ================= -->
                <div class="card">
                    <div class="card-h">
                        <h3><i class="ph ph-target"></i> Qualification Plans</h3>
                        <?php if ($hasNext): ?>
                            <span class="chip <?= $planMet ? 'ok' : 'warn'; ?>">
                                <i class="ph <?= $planMet ? 'ph-check-circle' : 'ph-hourglass-medium'; ?>"></i>
                                <?= $planMet ? 'Team qualified' : 'Team not yet'; ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if (!$hasNext): ?>
                        <div class="empty">
                            <i class="ph-fill ph-crown" style="color:var(--p)"></i>
                            You've reached CHALLENGER — there are no further requirements.
                        </div>
                    <?php else: ?>
                        <p class="muted" style="font-size:12px;line-height:1.6;margin:0 0 14px;">
                            To reach <b><?= htmlspecialchars($next['next_rank']); ?></b> you need the team volume
                            <b>and any ONE</b> of the plans below — not all of them.
                        </p>

                        <?php if ($planMet && !empty($next['matched_plan'])): ?>
                            <div class="chip ok" style="margin-bottom:12px;">
                                <i class="ph-fill ph-seal-check"></i>
                                Satisfied via <?= htmlspecialchars($next['matched_plan']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($next['requirements'])): ?>
                            <div class="chip mut" style="margin-bottom:10px;">
                                <i class="ph ph-info"></i> This rank needs team volume only — no team structure required.
                            </div>
                        <?php else:
                            $lastPlan = null;
                            foreach ($next['requirements'] as $req):
                                $samePlan = ($lastPlan === $req['plan']);
                                ?>
                                <?php if ($samePlan): ?>
                                    <div class="plan-or">— OR —</div>
                                <?php endif; ?>
                                <div class="plan <?= $req['met'] ? 'met' : ''; ?>">
                                    <div class="plan-h">
                                        <b>Plan <?= (int) $req['plan']; ?><?= $req['option'] > 1 ? ' · Option ' . (int) $req['option'] : ''; ?></b>
                                        <?php if ($req['met']): ?>
                                            <span class="chip ok"><i class="ph-fill ph-check-circle"></i> Complete</span>
                                        <?php else: ?>
                                            <span class="opt">Still missing</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php foreach ($req['conditions'] as $c): ?>
                                        <div class="cond">
                                            <span class="cond-ico <?= $c['met'] ? 'y' : 'n'; ?>">
                                                <i class="ph-fill ph-<?= $c['met'] ? 'check' : 'x'; ?>"></i>
                                            </span>
                                            <span class="cond-txt">
                                                <em><?= ucfirst($c['side']); ?> leg</em> ·
                                                <?= (int) $c['need']; ?> × <em><?= htmlspecialchars($c['rank']); ?></em> or higher
                                            </span>
                                            <span class="cond-cnt <?= $c['met'] ? 'y' : 'n'; ?>">
                                                <?= (int) $c['have']; ?>/<?= (int) $c['need']; ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php $lastPlan = $req['plan'];
                            endforeach;
                        endif; ?>

                        <div class="chip mut" style="margin-top:6px;">
                            <i class="ph ph-arrows-down-up"></i>
                            Your whole leg counts, at any depth — not just direct referrals.
                        </div>
                    <?php endif; ?>
                </div>

                <div>
                    <!-- ================= 7. RANK POWER (60-DAY CYCLE) ================= -->
                    <div class="card">
                        <div class="card-h">
                            <h3><i class="ph-fill ph-lightning"></i> Rank Power</h3>
                            <span class="chip"><?= (int) $power['cycle_days']; ?>-day cycle</span>
                        </div>

                        <?php if (!$power['enabled']): ?>
                            <div class="empty"><i class="ph ph-power"></i> Rank Power is currently disabled.</div>
                        <?php elseif (!$power['has_cycle']): ?>
                            <div class="empty"><i class="ph ph-hourglass"></i>
                                No cycle is running yet. Your power rank appears once the first cycle opens.</div>
                        <?php else: ?>
                            <div class="pw-head">
                                <?= $rk_badge(null, $power['badge_color'], 40); ?>
                                <div>
                                    <div class="pw-name <?= $power['rank'] ? '' : 'none'; ?>">
                                        <?= $power['rank'] ? htmlspecialchars($power['rank']) : 'No power rank yet'; ?>
                                    </div>
                                    <div class="pw-cycle">
                                        Cycle #<?= (int) $power['cycle_no']; ?> ·
                                        <?= date('d M', strtotime($power['start_date'])); ?> →
                                        <?= date('d M Y', strtotime($power['end_date'])); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="pw-timer">
                                <span>Cycle progress</span>
                                <b><?= (int) $power['days_left']; ?> day<?= $power['days_left'] == 1 ? '' : 's'; ?> left</b>
                            </div>
                            <div class="bar wrn"><div style="width:<?= (int) $power['cycle_percent']; ?>%"></div></div>

                            <div class="pw-legs">
                                <div class="pw-leg"><small>Left</small><b><?= $rk_k($power['left_volume']); ?></b></div>
                                <div class="pw-leg"><small>Right</small><b><?= $rk_k($power['right_volume']); ?></b></div>
                                <div class="pw-leg"><small>Total</small><b><?= $rk_k($power['total_volume']); ?></b></div>
                            </div>

                            <p class="muted" style="font-size:11.5px;line-height:1.65;margin:0;">
                                Rank Power counts <b>only this cycle's</b> team volume and resets on
                                <?= date('d M', strtotime($power['end_date'])); ?>. It is separate from your
                                achievement rank — <b>your <?= htmlspecialchars($rank['name']); ?> rank is never
                                    affected</b> by it.
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- ================= 8. GROUP INCENTIVE ELIGIBILITY ================= -->
                    <div class="card">
                        <div class="card-h">
                            <h3><i class="ph-fill ph-hand-coins"></i> Group Incentive</h3>
                            <span class="chip <?= $incentive['qualified'] ? 'ok' : 'mut'; ?>">
                                <i class="ph <?= $incentive['qualified'] ? 'ph-check-circle' : 'ph-minus-circle'; ?>"></i>
                                <?= $incentive['qualified'] ? 'Eligible' : 'Not eligible'; ?>
                            </span>
                        </div>

                        <div class="inc-box <?= $incentive['qualified'] ? 'on' : 'off'; ?>">
                            <div class="inc-amt"><?= $rk_n($incentive['amount']); ?><small> BMAN</small></div>
                            <div class="muted" style="font-size:11px;font-weight:700;margin-top:2px;">
                                <?= $incentive['qualified']
                                    ? 'Payable at ' . htmlspecialchars($incentive['rank']) . ' level this cycle'
                                    : 'Build cycle volume to qualify'; ?>
                            </div>
                        </div>

                        <p class="inc-note">
                            <i class="ph-fill ph-info" style="color:var(--p)"></i>
                            <?php if ($incentive['paid_on'] === 'power'): ?>
                                Group Incentive is paid on your <b>Rank Power</b>, not your achievement rank.
                                <?php if ($rank['has_rank'] && $power['rank'] && $power['rank'] !== $rank['name']): ?>
                                    You are <b><?= htmlspecialchars($rank['name']); ?></b> permanently, but this cycle
                                    your power is <b><?= htmlspecialchars($power['rank']); ?></b> — so you're paid at the
                                    <?= htmlspecialchars($power['rank']); ?> level. Your rank does not change.
                                <?php else: ?>
                                    Every cycle resets, so a strong cycle is always worth having.
                                <?php endif; ?>
                            <?php else: ?>
                                Group Incentive is currently paid on your <b>achievement rank</b>.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- ================= 12. RANK BADGE DISPLAY ================= -->
            <div class="card">
                <div class="card-h">
                    <h3><i class="ph-fill ph-medal"></i> Rank Badges</h3>
                    <span class="chip"><?= $earnedCnt; ?> of <?= count($ladder); ?> earned</span>
                </div>
                <div class="badge-wall">
                    <?php foreach ($ladder as $l): ?>
                        <div class="bw <?= $l['is_current'] ? 'current' : ($l['is_next'] ? 'next' : ($l['achieved'] ? '' : 'locked')); ?>"
                            title="<?= htmlspecialchars($l['name']); ?> — needs <?= $rk_n($l['required_volume'], 0); ?> BMAN team volume<?= $l['achieved_at'] ? ' · achieved ' . date('d M Y', strtotime($l['achieved_at'])) : ''; ?>">
                            <?php if ($l['is_current']): ?><span class="flag c">NOW</span>
                            <?php elseif ($l['is_next']): ?><span class="flag n">NEXT</span>
                            <?php elseif ($l['achieved']): ?><span class="flag a">✓</span><?php endif; ?>
                            <?= $rk_badge($l['badge_image'], $l['badge_color'], 46); ?>
                            <b><?= htmlspecialchars($l['name']); ?></b>
                            <small><?= $rk_k($l['required_volume']); ?> BMAN</small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="muted" style="font-size:11.5px;margin:14px 0 0;line-height:1.6;">
                    <i class="ph-fill ph-lock-key-open" style="color:var(--p)"></i>
                    Every badge you earn is permanent. Badges appear on your profile, the genealogy tree and the
                    leaderboard.
                </p>
            </div>

            <div class="grid-2">
                <!-- ================= 11. RANK JOURNEY TIMELINE ================= -->
                <div class="card">
                    <div class="card-h">
                        <h3><i class="ph ph-path"></i> My Rank Journey</h3>
                        <span class="chip"><?= count($history); ?> milestone<?= count($history) == 1 ? '' : 's'; ?></span>
                    </div>

                    <?php if (empty($history)): ?>
                        <div class="empty">
                            <i class="ph ph-path"></i>
                            Your journey starts with your first rank. Build team volume to get there.
                        </div>
                    <?php else: ?>
                        <div class="tl">
                            <?php foreach (array_reverse($history) as $h): ?>
                                <div class="tl-i">
                                    <span class="tl-dot" style="background:<?= htmlspecialchars($h['badge_color'] ?: '#6E56CF'); ?>"></span>
                                    <b><?= htmlspecialchars($h['new_rank']); ?></b>
                                    <span class="when"><?= date('d M Y', strtotime($h['achieved_at'])); ?></span>
                                    <p>
                                        <?= $h['old_rank'] ? 'Promoted from ' . htmlspecialchars($h['old_rank']) : 'Your first rank'; ?>
                                        · <?= $rk_k($h['achieved_volume']); ?> BMAN team volume<?= $h['qualification_plan'] ? ' · ' . htmlspecialchars($h['qualification_plan']) : ''; ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ================= 10. RANK CERTIFICATES ================= -->
                <div class="card">
                    <div class="card-h">
                        <h3><i class="ph ph-certificate"></i> My Certificates</h3>
                        <span class="chip"><?= $certCnt; ?></span>
                    </div>

                    <?php if (!$certCnt): ?>
                        <div class="empty">
                            <i class="ph ph-certificate"></i>
                            Certificates are issued automatically when you reach a rank.
                        </div>
                    <?php else: ?>
                        <div class="cert-grid">
                            <?php foreach ($certificates as $c): ?>
                                <a class="cert" target="_blank"
                                    href="<?= base_url('user/rank-certificate/' . rawurlencode($c['certificate_no'])); ?>">
                                    <?= $rk_badge($c['badge_image'], $c['badge_color'], 34); ?>
                                    <div>
                                        <b><?= htmlspecialchars($c['rank_name']); ?></b>
                                        <div class="no mono"><?= htmlspecialchars($c['certificate_no']); ?></div>
                                    </div>
                                    <i class="ph ph-arrow-square-out go"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <p class="muted" style="font-size:11px;margin:12px 0 0;">
                            Open a certificate to print it or save it as a PDF.
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ================= 9. RANK REWARDS HISTORY ================= -->
            <div class="card">
                <div class="card-h">
                    <h3><i class="ph-fill ph-gift"></i> Rank Rewards</h3>
                    <span class="chip"><?= $rewardCnt; ?> total</span>
                </div>

                <?php if (!$rewardCnt): ?>
                    <div class="empty">
                        <i class="ph ph-gift"></i>
                        No rewards yet. Reach a rank and any reward is credited to your wallet automatically.
                    </div>
                <?php else: ?>
                    <div class="filters">
                        <input class="inp" id="rkSearch" type="search" placeholder="Search rank, type, reference…" />
                        <select class="sel" id="rkType">
                            <option value="">All types</option>
                            <option value="bman">BMAN</option>
                            <option value="usdt">USDT</option>
                            <option value="physical">Non-cash</option>
                        </select>
                        <select class="sel" id="rkStatus">
                            <option value="">All statuses</option>
                            <option value="paid">Paid</option>
                            <option value="pending">Pending</option>
                            <option value="failed">Failed</option>
                        </select>
                        <button class="btn-soft" onclick="rkReset()">
                            <i class="ph ph-arrow-counter-clockwise"></i> Reset</button>
                    </div>

                    <div style="overflow-x:auto;">
                        <table class="table" id="rkTable">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Type</th>
                                    <th class="num">Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Reference</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rewards as $r):
                                    $cls = $r['reward_status'] === 'paid' ? 'b-ok'
                                        : ($r['reward_status'] === 'failed' ? 'b-bad'
                                            : ($r['reward_status'] === 'skipped' ? 'b-mut' : 'b-warn'));
                                    ?>
                                    <tr data-type="<?= htmlspecialchars($r['reward_type']); ?>"
                                        data-status="<?= htmlspecialchars($r['reward_status']); ?>">
                                        <td class="td-title"><?= htmlspecialchars($r['rank_name']); ?></td>
                                        <td><span class="badge b-mut"><?= strtoupper($r['reward_type']); ?></span></td>
                                        <td class="num mono">
                                            <?= $r['reward_type'] === 'physical' ? '—' : $rk_n($r['reward_amount'], 4); ?>
                                        </td>
                                        <td><span class="badge <?= $cls; ?>"><?= ucfirst($r['reward_status']); ?></span></td>
                                        <td class="muted">
                                            <?= $r['rewarded_at'] ? date('d M Y', strtotime($r['rewarded_at'])) : '—'; ?>
                                        </td>
                                        <td class="muted mono">
                                            <?= $r['wallet_ledger_id']
                                                ? 'Ledger #' . (int) $r['wallet_ledger_id']
                                                : htmlspecialchars($r['note'] ?: '—'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="empty" id="rkNone" style="display:none;">
                            <i class="ph ph-magnifying-glass"></i> No rewards match your filters.
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </main>

        <aside class="right-panel">
            <?php $this->load->view('user/layout/v2/user_inner_right_panle'); ?>
        </aside>
    </div>

    <!-- ================= RULES MODAL ================= -->
    <div class="modal-backdrop" id="rkBack" onclick="rkRules(false)"></div>
    <div class="modal" id="rkModal" role="dialog" aria-modal="true" aria-labelledby="rkModalT">
        <div class="modal-h">
            <h3 id="rkModalT">How BMAN ranks work</h3>
            <button class="xbtn" onclick="rkRules(false)" aria-label="Close"><i class="ph ph-x"></i></button>
        </div>
        <div>
            <div class="rule-row">
                <i class="ph-fill ph-lock-key-open"></i>
                <div><b>Your rank is permanent.</b> Once you reach a rank it is yours forever. If your team's volume
                    later drops — even to zero — you keep the rank. There is no demotion and no expiry.</div>
            </div>
            <div class="rule-row">
                <i class="ph-fill ph-chart-donut"></i>
                <div><b>Team volume, not your own.</b> Rank is measured on completed staking from your whole team at
                    any depth. Your own staking never counts, and only completed staking counts — pending, failed,
                    cancelled and refunded orders are excluded.</div>
            </div>
            <div class="rule-row">
                <i class="ph ph-target"></i>
                <div><b>Two things must be true.</b> You need the team volume for the rank <em>and</em> a qualifying
                    team on your left and right legs.</div>
            </div>
            <div class="rule-row">
                <i class="ph ph-git-branch"></i>
                <div><b>Three routes — any ONE is enough.</b> Every rank has up to three alternative plans, and some
                    plans have two options. You never need to satisfy all of them: take whichever fits the team
                    you've actually built.</div>
            </div>
            <div class="rule-row">
                <i class="ph ph-arrow-up"></i>
                <div><b>Higher ranks count for lower ones.</b> If a plan asks for 2 GOLD and you have 2 DIAMONDs on
                    that leg, it's covered.</div>
            </div>
            <div class="rule-row">
                <i class="ph-fill ph-lightning"></i>
                <div><b>Rank Power is separate.</b> It counts only the last <?= (int) $power['cycle_days']; ?> days and
                    resets each cycle. It decides your <b>Group Incentive</b> — your permanent rank does not. A GOLD
                    member with SILVER power this cycle is paid at SILVER, and is still GOLD.</div>
            </div>
            <div class="rule-row">
                <i class="ph-fill ph-gift"></i>
                <div><b>Rewards are automatic.</b> Reach a rank and the reward is credited to your wallet, the
                    certificate is issued and your badge updates. Nothing to claim, and it is paid once per rank.</div>
            </div>
            <div class="rule-row">
                <i class="ph ph-clock-clockwise"></i>
                <div><b>Checked hourly.</b> Ranks are evaluated every hour, so a new rank appears shortly after you
                    and your team qualify.</div>
            </div>
        </div>
    </div>

    <script>
        /* Rules modal */
        function rkRules(open) {
            document.getElementById('rkModal').style.display = open ? 'block' : 'none';
            document.getElementById('rkBack').style.display = open ? 'block' : 'none';
            document.body.style.overflow = open ? 'hidden' : '';
        }
        document.addEventListener('keydown', e => { if (e.key === 'Escape') rkRules(false); });

        /* Rewards filters — client-side over the rendered rows */
        (function () {
            const q = document.getElementById('rkSearch');
            if (!q) return;                     // table not rendered (member has no rewards)
            const type = document.getElementById('rkType');
            const status = document.getElementById('rkStatus');
            const rows = Array.from(document.querySelectorAll('#rkTable tbody tr'));
            const none = document.getElementById('rkNone');

            function apply() {
                const term = q.value.trim().toLowerCase();
                let shown = 0;
                rows.forEach(tr => {
                    const okT = !type.value || tr.dataset.type === type.value;
                    const okS = !status.value || tr.dataset.status === status.value;
                    const okQ = !term || tr.textContent.toLowerCase().includes(term);
                    const show = okT && okS && okQ;
                    tr.style.display = show ? '' : 'none';
                    if (show) shown++;
                });
                none.style.display = shown ? 'none' : 'block';
            }
            [q, type, status].forEach(el => el.addEventListener('input', apply));
            window.rkReset = function () { q.value = ''; type.value = ''; status.value = ''; apply(); };
        })();
    </script>
</body>

</html>
