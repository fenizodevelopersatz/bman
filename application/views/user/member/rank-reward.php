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
 * Card-based rebuild on the platform's global theme tokens (--primary, the
 * --color- family, the --text- family, --radius-lg, --shadow-card — all
 * defined once in user_style.php). The rank badge itself is rendered
 * exclusively via rank_badge_html() (application/helpers/rank_helper.php) —
 * the one badge component shared with the dashboard right panel, so "current
 * rank" always looks identical everywhere it appears.
 *
 * Sections: Hero (badge/info/progress) · Stats row (Left/Right/Total/Power) ·
 * Next Rank Qualification · Rank Timeline · Rank Power · Group Incentive ·
 * Rank Rewards (cards) · Rank Certificates (cards).
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

$hasNext  = !empty($next);
$volPct   = $hasNext ? (float) $next['volume_percent'] : 100;
$volMet   = $hasNext ? !empty($next['volume_met']) : true;
$planMet  = $hasNext ? !empty($next['plan_met']) : true;
$leftVol  = (float) $volume['left_volume'];
$rightVol = (float) $volume['right_volume'];
$totalVol = (float) $volume['total_volume'];
$legMax   = max($leftVol, $rightVol, 1);             // for the leg bar widths only
$remaining = $hasNext ? max(0, (float)$next['required_volume'] - (float)$next['current_volume']) : 0;
$rewardCnt = count($rewards);
$certCnt   = count($certificates);
$earnedCnt = count(array_filter($ladder, function ($l) { return $l['achieved']; }));

// Milestone preview for the Rewards section — current rank / next rank,
// straight off $ladder (already carries is_current, is_next, reward_bman/
// usdt/note per rank). $msCurrent is null for an unranked member; $msNext is
// null once CHALLENGER is held.
$msCurrent = null; $msNext = null;
foreach ($ladder as $l) {
    if ($l['is_current']) $msCurrent = $l;
    if ($l['is_next'])    $msNext    = $l;
}
$msRewardText = function ($l) use ($rk_n) {
    $parts = [];
    if ((float) $l['reward_bman'] > 0) $parts[] = $rk_n($l['reward_bman']) . ' BMAN';
    if ((float) $l['reward_usdt'] > 0) $parts[] = $rk_n($l['reward_usdt']) . ' USDT';
    if ($parts) return implode(' + ', $parts);
    if (!empty($l['reward_note'])) return $l['reward_note'];
    return 'Badge, certificate & recognition';
};
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
        /* ================= local aliases onto the global theme tokens ================
           Every color/radius/shadow below resolves to the platform-wide variables
           declared once in user_style.php — nothing here re-declares a palette. */
        :root {
            --rk-ok: var(--mp-success, #10B981);
            --rk-warn: var(--mp-warning, #F59E0B);
            --rk-bad: var(--mp-danger, #EF4444);
        }

        .main-content { background: var(--color-bg, #F8FAFC); }

        /* ---------------- titlebar ---------------- */
        .titlebar { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:18px; }
        .titlebar h1 { font-size:22px; font-weight:800; color:var(--text-primary); display:flex; align-items:center; gap:8px; margin:0; }
        .titlebar h1 i { color:var(--primary); }
        .titlebar p { margin:4px 0 0; font-size:12.5px; color:var(--text-secondary); }
        .actions { display:flex; gap:8px; flex-wrap:wrap; }
        .btn-soft, .btn-main, .btn-dark {
            border:0; cursor:pointer; font-family:inherit; font-weight:700; font-size:12.5px;
            padding:10px 14px; border-radius:12px; display:inline-flex; align-items:center; gap:7px;
            text-decoration:none; transition:transform .12s ease;
        }
        .btn-soft { background:#fff; color:var(--text-primary); border:1px solid var(--color-border); }
        .btn-main { background:var(--primary); color:#fff; }
        .btn-dark { background:#101014; color:#fff; }
        .btn-soft:hover, .btn-main:hover, .btn-dark:hover { transform:translateY(-1px); }

        /* ---------------- cards (global card style) ---------------- */
        .card { background:var(--color-card); border:1px solid var(--color-border); border-radius:var(--radius-lg); box-shadow:var(--shadow-card); padding:20px; margin-bottom:18px; }
        .card-h { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px; flex-wrap:wrap; }
        .card-h h3 { margin:0; font-size:15px; font-weight:800; color:var(--text-primary); display:flex; align-items:center; gap:8px; }
        .card-h h3 i { color:var(--primary); }
        .chip { font-size:10.5px; padding:6px 11px; border-radius:999px; background:#EEF2FF; color:var(--primary); font-weight:700; display:inline-flex; align-items:center; gap:5px; }
        .chip.ok { background:#D1FAE5; color:#047857; }
        .chip.warn { background:#FEF3C7; color:#B45309; }
        .chip.mut { background:var(--color-bg); color:var(--text-secondary); }
        .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
        .grid-3 { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; }
        .grid-4 { display:grid; grid-template-columns:repeat(4,1fr); gap:18px; }
        .muted { color:var(--text-secondary); }

        .bar { height:9px; border-radius:999px; background:#E5E7EB; overflow:hidden; }
        .bar>div { height:100%; background:var(--primary); border-radius:999px; transition:width .8s ease; }
        .bar.ok>div { background:var(--rk-ok); }
        .bar.wrn>div { background:var(--rk-warn); }

        /* rank badge component base rules live in user_style.php (global,
           shared with the dashboard right panel) — only page-local sizing
           overrides live here, on the specific wrapper classes below. */

        /* ================= HERO: badge | info | progress ================= */
        .rk-hero {
            display:grid; grid-template-columns:200px 1fr 220px; gap:0;
            background:linear-gradient(135deg, var(--primary) 0%, var(--mp-hover, #4338CA) 62%, #241a52 100%);
            border-radius:var(--radius-lg); color:#fff; margin-bottom:18px; overflow:hidden;
            box-shadow:var(--shadow-card);
        }
        .rk-hero-col { padding:26px 22px; position:relative; z-index:1; }
        .rk-hero-badge-col { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:12px; text-align:center; border-right:1px solid rgba(255,255,255,.14); }
        .rk-hero-badge-col .rk-badge { width:120px; height:120px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.2); box-shadow:0 12px 30px rgba(0,0,0,.25); border-radius:20px; }
        .rk-hero-badge-col .rk-badge-dot { width:120px !important; height:120px !important; border:3px solid rgba(255,255,255,.5); }
        .rk-perm-pill { display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.2); padding:6px 12px; border-radius:999px; font-size:11px; font-weight:700; }
        .rk-perm-pill i { color:#ffd76e; }

        .rk-hero-info-col { display:flex; flex-direction:column; justify-content:center; gap:12px; }
        .rk-hero-rankname { font-size:30px; font-weight:800; letter-spacing:-.6px; line-height:1.1; }
        .rk-hero-tag { font-size:12.5px; opacity:.85; font-weight:600; text-transform:uppercase; letter-spacing:.6px; }
        .rk-hero-facts { display:grid; grid-template-columns:repeat(2,1fr); gap:10px 22px; margin-top:6px; }
        .rk-hero-facts dt { font-size:10.5px; text-transform:uppercase; letter-spacing:.6px; opacity:.7; font-weight:700; margin:0; }
        .rk-hero-facts dd { font-size:14.5px; font-weight:800; margin:3px 0 0; }

        .rk-hero-progress-col { display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; background:rgba(255,255,255,.08); border-left:1px solid rgba(255,255,255,.14); }
        .ringWrap { position:relative; width:150px; height:150px; margin:0 auto 10px; }
        .ringSvg { transform:rotate(-90deg); }
        .ringTrack { fill:none; stroke:rgba(255,255,255,.18); stroke-width:9; }
        .ringBar { fill:none; stroke:#fff; stroke-width:9; stroke-linecap:round; transition:stroke-dashoffset 1s ease; }
        .ringCenter { position:absolute; inset:0; display:grid; place-items:center; }
        .ringCenter b { font-size:30px; font-weight:800; line-height:1; letter-spacing:-1px; }
        .ringCenter small { display:block; font-size:9px; letter-spacing:1.2px; opacity:.78; margin-top:4px; font-weight:700; }
        .rpText { font-size:11.5px; opacity:.85; }
        .rpText b { display:block; font-size:13px; opacity:1; margin-top:2px; }

        @media (max-width:1100px) {
            .rk-hero { grid-template-columns:1fr; }
            .rk-hero-badge-col, .rk-hero-progress-col { border:0; border-bottom:1px solid rgba(255,255,255,.14); }
        }

        /* ================= STATS ROW (4 cards) ================= */
        .stat-card { display:flex; flex-direction:column; height:100%; }
        .stat-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
        .stat-head span { font-size:11px; font-weight:700; letter-spacing:.7px; text-transform:uppercase; color:var(--text-secondary); }
        .stat-ico { width:34px; height:34px; border-radius:10px; display:grid; place-items:center; font-size:16px; flex:0 0 34px; }
        .stat-ico.l { background:#EEF2FF; color:var(--primary); }
        .stat-ico.r { background:#ECFDF5; color:#059669; }
        .stat-ico.t { background:#FDF2F8; color:#DB2777; }
        .stat-ico.p { background:#FEF3C7; color:#B45309; }
        .stat-amt { font-size:23px; font-weight:800; color:var(--text-primary); letter-spacing:-.5px; line-height:1.1; }
        .stat-amt small { font-size:12px; font-weight:700; color:var(--text-secondary); margin-left:4px; }
        .stat-sub { font-size:11px; color:var(--text-secondary); margin-top:auto; padding-top:8px; line-height:1.5; }
        @media (max-width:1100px) { .grid-4 { grid-template-columns:repeat(2,1fr); } }
        @media (max-width:560px)  { .grid-4 { grid-template-columns:1fr; } }

        /* ---------------- qualification plan tracker ---------------- */
        .plan { border:1px solid var(--color-border); border-radius:14px; padding:14px; margin-bottom:10px; background:var(--color-bg); }
        .plan.met { border-color:#A7F3D0; background:#F0FDF6; }
        .plan-h { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:8px; flex-wrap:wrap; }
        .plan-h b { font-size:12.5px; color:var(--text-primary); }
        .plan-h .opt { font-size:10.5px; color:var(--text-secondary); font-weight:600; }
        .cond { padding:9px 0; border-top:1px dashed var(--color-border); }
        .cond:first-of-type { border-top:0; }
        .cond-top { display:flex; align-items:center; gap:10px; margin-bottom:6px; }
        .cond-ico { width:22px; height:22px; border-radius:50%; display:grid; place-items:center; font-size:11px; flex:0 0 22px; }
        .cond-ico.y { background:#D1FAE5; color:#047857; }
        .cond-ico.n { background:#FEE2E2; color:#B91C1C; }
        .cond-txt { flex:1; font-size:12.5px; color:var(--text-primary); }
        .cond-txt em { font-style:normal; font-weight:700; }
        .cond-cnt { font-size:12px; font-weight:800; font-variant-numeric:tabular-nums; }
        .cond-cnt.y { color:#047857; }
        .cond-cnt.n { color:#B91C1C; }
        .cond .bar { height:6px; margin-left:32px; }
        .plan-or { text-align:center; font-size:10px; font-weight:800; color:var(--text-secondary); letter-spacing:1.4px; margin:-2px 0 8px; }

        /* ---------------- rank power ---------------- */
        .pw-head { display:flex; align-items:center; gap:12px; margin-bottom:14px; }
        .pw-name { font-size:21px; font-weight:800; color:var(--text-primary); letter-spacing:-.4px; }
        .pw-name.none { color:var(--text-secondary); font-size:16px; }
        .pw-cycle { font-size:11px; color:var(--text-secondary); margin-top:2px; }
        .pw-legs { display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin:14px 0; }
        .pw-leg { background:var(--color-bg); border-radius:12px; padding:10px 12px; }
        .pw-leg small { font-size:10px; text-transform:uppercase; letter-spacing:.6px; color:var(--text-secondary); font-weight:700; }
        .pw-leg b { display:block; font-size:15px; color:var(--text-primary); margin-top:3px; font-variant-numeric:tabular-nums; }
        .pw-timer { display:flex; align-items:center; justify-content:space-between; font-size:11px; color:var(--text-secondary); margin-bottom:6px; }
        .pw-timer b { color:var(--text-primary); }

        /* ---------------- group incentive ---------------- */
        .inc-box { border-radius:14px; padding:16px; border:1px solid var(--color-border); }
        .inc-box.on { background:linear-gradient(135deg,#ECFDF5,#F0FDFA); border-color:#A7F3D0; }
        .inc-box.off { background:var(--color-bg); }
        .inc-amt { font-size:27px; font-weight:800; letter-spacing:-.6px; color:var(--text-primary); line-height:1.15; }
        .inc-amt small { font-size:12px; font-weight:700; color:var(--text-secondary); }
        .inc-note { font-size:11.5px; color:var(--text-secondary); line-height:1.65; margin:10px 0 0; }

        /* ================= RANK TIMELINE (horizontal, scrollable) ================= */
        .rk-timeline-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; padding-bottom:6px; }
        .rk-timeline { display:flex; align-items:flex-start; gap:0; min-width:max-content; }
        .rk-tl-node { display:flex; flex-direction:column; align-items:center; text-align:center; width:104px; position:relative; }
        .rk-tl-node .rk-badge { width:52px; height:52px; margin-bottom:8px; border:2px solid var(--color-border); background:var(--color-bg); }
        .rk-tl-node.achieved .rk-badge { border-color:var(--rk-ok); box-shadow:0 0 0 3px rgba(16,185,129,.15); }
        .rk-tl-node.current .rk-badge { border-color:var(--primary); background:#fff; box-shadow:0 0 0 4px rgba(79,70,229,.18); transform:scale(1.08); }
        .rk-tl-node.locked .rk-badge { opacity:.4; filter:grayscale(1); }
        .rk-tl-node b { font-size:10.5px; font-weight:800; color:var(--text-primary); letter-spacing:.2px; }
        .rk-tl-node small { font-size:9px; color:var(--text-secondary); margin-top:2px; }
        .rk-tl-flag { font-size:8px; font-weight:800; padding:2px 7px; border-radius:999px; letter-spacing:.4px; margin-bottom:6px; }
        .rk-tl-flag.now { background:var(--primary); color:#fff; }
        .rk-tl-flag.next { background:#FCD34D; color:#7C2D12; }
        .rk-tl-flag.done { background:#D1FAE5; color:#047857; }
        .rk-tl-arrow { display:flex; align-items:center; justify-content:center; width:34px; padding-top:26px; color:var(--color-border); font-size:16px; flex:0 0 34px; }
        .rk-tl-node.achieved ~ .rk-tl-arrow, .rk-tl-arrow.done { color:var(--rk-ok); }

        /* ================= RANK REWARDS (cards) ================= */
        .reward-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:14px; }
        .reward-card { border:1px solid var(--color-border); border-radius:14px; padding:14px; background:var(--color-bg); display:flex; gap:12px; }
        .reward-card .rk-badge { width:44px; height:44px; flex:0 0 44px; }
        .reward-card .rc-body { min-width:0; flex:1; }
        .reward-card .rc-rank { font-size:13px; font-weight:800; color:var(--text-primary); }
        .reward-card .rc-amt { font-size:16px; font-weight:800; color:var(--text-primary); margin-top:4px; }
        .reward-card .rc-amt small { font-size:11px; font-weight:600; color:var(--text-secondary); }
        .reward-card .rc-meta { font-size:10.5px; color:var(--text-secondary); margin-top:6px; line-height:1.6; }
        .badge { font-size:10px; font-weight:800; padding:4px 9px; border-radius:999px; display:inline-block; letter-spacing:.3px; }
        .b-ok { background:#D1FAE5; color:#047857; }
        .b-warn { background:#FEF3C7; color:#B45309; }
        .b-bad { background:#FEE2E2; color:#B91C1C; }
        .b-mut { background:var(--color-bg); color:var(--text-secondary); }
        .empty { text-align:center; padding:34px 16px; color:var(--text-secondary); font-size:12.5px; line-height:1.6; }
        .empty i { font-size:30px; display:block; margin-bottom:8px; opacity:.4; }

        /* ---- rank milestone preview (replaces the empty rewards state) ---- */
        .rk-milestone { display:flex; align-items:center; gap:14px; margin:6px 0 12px; }
        .rk-ms-box { flex:1; min-width:0; background:var(--color-bg); border:1px solid var(--color-border); border-radius:14px;
            padding:18px 14px; text-align:center; position:relative; overflow:hidden; }
        .rk-ms-box b { display:block; font-size:14px; font-weight:800; color:var(--text-primary); margin-top:9px; }
        .rk-ms-box small { display:block; font-size:11px; color:var(--text-secondary); margin-top:3px; }
        .rk-ms-box .rk-badge { margin:0 auto; }
        .rk-ms-current { background:#fff; border-color:var(--primary); box-shadow:0 0 0 3px rgba(79,70,229,.14); }
        .rk-ms-mid { flex:0 0 60px; text-align:center; }
        .rk-ms-pct { display:block; font-size:12px; font-weight:800; color:var(--primary); margin-bottom:5px; }
        .rk-ms-mid i { font-size:20px; color:var(--text-secondary); }
        .rk-ms-blurred { filter:blur(2.5px); opacity:.55; user-select:none; }
        .rk-ms-lock { position:absolute; inset:0; z-index:2; display:flex; align-items:center; justify-content:center; }
        .rk-ms-lock i { font-size:20px; color:var(--text-primary); background:rgba(255,255,255,.9); width:36px; height:36px;
            border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 14px rgba(0,0,0,.12); }
        .rk-ms-hint { font-size:11.5px; color:var(--text-secondary); text-align:center; margin:0; line-height:1.65; }
        @media (max-width:560px) { .rk-milestone { flex-direction:column; } .rk-ms-mid i { transform:rotate(90deg); } }

        /* ---------------- rank certificates (cards) ---------------- */
        .cert-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:14px; }
        .cert { border:1px solid var(--color-border); border-radius:14px; padding:14px; background:var(--color-bg); display:flex; gap:12px; align-items:center; text-decoration:none; transition:transform .15s ease, box-shadow .15s ease; }
        .cert:hover { transform:translateY(-2px); box-shadow:var(--shadow-card); background:#fff; }
        .cert .rk-badge { width:40px; height:40px; flex:0 0 40px; }
        .cert b { font-size:12.5px; color:var(--text-primary); display:block; }
        .cert .no { font-size:10.5px; color:var(--text-secondary); margin-top:3px; }
        .cert .issued { font-size:10px; color:var(--text-secondary); margin-top:2px; }
        .cert .go { margin-left:auto; color:var(--primary); font-size:16px; }

        /* ---------------- filters ---------------- */
        .filters { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px; }
        .inp, .sel { font-family:inherit; font-size:12px; padding:9px 12px; border-radius:11px; border:1px solid var(--color-border); background:var(--color-bg); color:var(--text-primary); outline:none; }
        .inp { flex:1; min-width:180px; }
        .inp:focus, .sel:focus { border-color:var(--primary); }
        .mono { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; }

        /* rules modal */
        .modal-backdrop { position:fixed; inset:0; background:rgba(10,10,20,.5); z-index:998; display:none; }
        .modal { position:fixed; z-index:999; inset:0; margin:auto; width:min(660px,92vw); height:max-content; max-height:86vh; overflow:auto; background:#fff; border-radius:var(--radius-lg); padding:22px; display:none; }
        .modal-h { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; }
        .modal-h h3 { margin:0; font-size:17px; font-weight:800; }
        .xbtn { border:0; background:var(--color-bg); width:34px; height:34px; border-radius:10px; cursor:pointer; font-size:16px; }
        .rule-row { display:flex; gap:12px; padding:11px 0; border-top:1px solid var(--color-border); font-size:12.5px; line-height:1.6; color:var(--text-primary); }
        .rule-row:first-of-type { border-top:0; }
        .rule-row i { color:var(--primary); font-size:17px; flex:0 0 auto; margin-top:1px; }

        /* ---------------- responsive ---------------- */
        @media (max-width:900px) { .grid-2, .grid-3 { grid-template-columns:1fr; } }
        @media (max-width:560px) {
            .titlebar h1 { font-size:19px; }
            .rk-hero-col { padding:20px; }
        }
        @media print {
            .sidebar, .right-panel, .actions, .filters { display:none !important; }
            .card, .rk-hero { break-inside:avoid; box-shadow:none; }
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

            <!-- ================= HERO: badge | info | progress ================= -->
            <section class="rk-hero">
                <div class="rk-hero-col rk-hero-badge-col">
                    <?= rank_badge_html($rank['badge_image'], $rank['badge_color'], 120); ?>
                    <?php if ($rank['has_rank']): ?>
                        <span class="rk-perm-pill"><i class="ph-fill ph-lock-key-open"></i> Permanent</span>
                    <?php endif; ?>
                </div>

                <div class="rk-hero-col rk-hero-info-col">
                    <div class="rk-hero-tag">Permanent Achievement Rank</div>
                    <div class="rk-hero-rankname"><?= htmlspecialchars($rank['name']); ?></div>

                    <dl class="rk-hero-facts">
                        <?php if ($rank['has_rank'] && $rank['achieved_at']): ?>
                            <div><dt>Achieved</dt><dd><?= date('d M Y', strtotime($rank['achieved_at'])); ?></dd></div>
                        <?php endif; ?>
                        <?php if ($hasNext): ?>
                            <div><dt>Next Rank</dt><dd><?= htmlspecialchars($next['next_rank']); ?></dd></div>
                            <div><dt>Required Volume</dt><dd><?= $rk_k($next['required_volume']); ?> BMAN</dd></div>
                            <div><dt>Current Volume</dt><dd><?= $rk_k($next['current_volume']); ?> BMAN</dd></div>
                            <div><dt>Remaining</dt><dd><?= $rk_k($remaining); ?> BMAN</dd></div>
                        <?php else: ?>
                            <div><dt>Status</dt><dd>Highest rank reached</dd></div>
                        <?php endif; ?>
                    </dl>
                </div>

                <div class="rk-hero-col rk-hero-progress-col">
                    <?php
                    $R = 64;
                    $C = 2 * M_PI * $R;
                    $off = $C - ($C * min(100, max(0, $volPct)) / 100);
                    ?>
                    <div class="ringWrap">
                        <svg class="ringSvg" width="150" height="150" viewBox="0 0 150 150">
                            <circle class="ringTrack" cx="75" cy="75" r="<?= $R; ?>" />
                            <circle class="ringBar" cx="75" cy="75" r="<?= $R; ?>"
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
                        <div class="rpText"><?= $rk_k($next['current_volume']); ?> / <?= $rk_k($next['required_volume']); ?> BMAN</div>
                    <?php else: ?>
                        <div class="rpText">You hold the <b>highest rank</b></div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- ================= STATS ROW: Left / Right / Total / Rank Power ================= -->
            <div class="grid-4">
                <div class="card stat-card">
                    <div class="stat-head"><span>Left Team Volume</span><div class="stat-ico l"><i class="ph-fill ph-arrow-bend-down-left"></i></div></div>
                    <div class="stat-amt"><?= $rk_n($leftVol); ?><small>BMAN</small></div>
                    <div class="bar" style="margin-top:10px;"><div style="width:<?= (int) round($leftVol / $legMax * 100); ?>%"></div></div>
                    <div class="stat-sub">Completed staking, whole left leg, any depth.</div>
                </div>
                <div class="card stat-card">
                    <div class="stat-head"><span>Right Team Volume</span><div class="stat-ico r"><i class="ph-fill ph-arrow-bend-down-right"></i></div></div>
                    <div class="stat-amt"><?= $rk_n($rightVol); ?><small>BMAN</small></div>
                    <div class="bar ok" style="margin-top:10px;"><div style="width:<?= (int) round($rightVol / $legMax * 100); ?>%"></div></div>
                    <div class="stat-sub">Completed staking, whole right leg, any depth.</div>
                </div>
                <div class="card stat-card">
                    <div class="stat-head"><span>Total Group Volume</span><div class="stat-ico t"><i class="ph-fill ph-chart-donut"></i></div></div>
                    <div class="stat-amt"><?= $rk_n($totalVol); ?><small>BMAN</small></div>
                    <div class="bar <?= $volMet ? 'ok' : ''; ?>" style="margin-top:10px;"><div style="width:<?= (int) round(min(100, $volPct)); ?>%"></div></div>
                    <div class="stat-sub">
                        <?php if (!$hasNext): ?>All volume targets met.
                        <?php elseif ($volMet): ?>Enough for <?= htmlspecialchars($next['next_rank']); ?>.
                        <?php else: ?><?= $rk_n($remaining); ?> more BMAN for <?= htmlspecialchars($next['next_rank']); ?>.
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card stat-card">
                    <div class="stat-head"><span>Rank Power</span><div class="stat-ico p"><i class="ph-fill ph-lightning"></i></div></div>
                    <div class="stat-amt" style="font-size:19px;">
                        <?= $power['rank'] ? htmlspecialchars($power['rank']) : 'None yet'; ?>
                    </div>
                    <div class="bar wrn" style="margin-top:10px;"><div style="width:<?= (int) ($power['cycle_percent'] ?? 0); ?>%"></div></div>
                    <div class="stat-sub">
                        <?php if (!$power['enabled']): ?>Rank Power disabled.
                        <?php elseif (!$power['has_cycle']): ?>No cycle running yet.
                        <?php else: ?><?= (int) $power['days_left']; ?> day<?= $power['days_left'] == 1 ? '' : 's'; ?> left in cycle #<?= (int) $power['cycle_no']; ?>.
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="grid-2">
                <!-- ================= NEXT RANK QUALIFICATION ================= -->
                <div class="card">
                    <div class="card-h">
                        <h3><i class="ph ph-target"></i> Next Rank Qualification</h3>
                        <?php if ($hasNext): ?>
                            <span class="chip <?= $planMet ? 'ok' : 'warn'; ?>">
                                <i class="ph <?= $planMet ? 'ph-check-circle' : 'ph-hourglass-medium'; ?>"></i>
                                <?= $planMet ? 'Team qualified' : 'Team not yet'; ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if (!$hasNext): ?>
                        <div class="empty">
                            <i class="ph-fill ph-crown" style="color:var(--primary)"></i>
                            You've reached CHALLENGER — there are no further requirements.
                        </div>
                    <?php else: ?>
                        <p class="muted" style="font-size:12px;line-height:1.6;margin:0 0 14px;">
                            Next Rank: <b><?= htmlspecialchars($next['next_rank']); ?></b> — you need the team volume
                            <b>and any ONE</b> of the plans below — not all of them.
                        </p>

                        <?php if ($planMet && !empty($next['matched_plan'])): ?>
                            <div class="chip ok" style="margin-bottom:12px;">
                                <i class="ph-fill ph-seal-check"></i> Satisfied via <?= htmlspecialchars($next['matched_plan']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($next['requirements'])): ?>
                            <div class="chip mut" style="margin-bottom:10px;">
                                <i class="ph ph-info"></i> This rank needs team volume only — no team structure required.
                            </div>
                        <?php else:
                            $lastPlan = null; $missing = [];
                            foreach ($next['requirements'] as $req):
                                $samePlan = ($lastPlan === $req['plan']);
                                ?>
                                <?php if ($samePlan): ?><div class="plan-or">— OR —</div><?php endif; ?>
                                <div class="plan <?= $req['met'] ? 'met' : ''; ?>">
                                    <div class="plan-h">
                                        <b>Plan <?= (int) $req['plan']; ?><?= $req['option'] > 1 ? ' · Option ' . (int) $req['option'] : ''; ?></b>
                                        <?php if ($req['met']): ?>
                                            <span class="chip ok"><i class="ph-fill ph-check-circle"></i> Complete</span>
                                        <?php else: ?>
                                            <span class="opt">Still missing</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php foreach ($req['conditions'] as $c):
                                        $pct = (int)$c['need'] > 0 ? min(100, round((int)$c['have'] / (int)$c['need'] * 100)) : 100;
                                        if (!$c['met']) $missing[] = (max(0, (int)$c['need'] - (int)$c['have'])) . ' more ' . $c['rank'] . ' on ' . ucfirst($c['side']);
                                        ?>
                                        <div class="cond">
                                            <div class="cond-top">
                                                <span class="cond-ico <?= $c['met'] ? 'y' : 'n'; ?>">
                                                    <i class="ph-fill ph-<?= $c['met'] ? 'check' : 'x'; ?>"></i>
                                                </span>
                                                <span class="cond-txt">
                                                    <em><?= ucfirst($c['side']); ?></em> ·
                                                    <?= (int) $c['need']; ?> × <em><?= htmlspecialchars($c['rank']); ?></em> or higher
                                                </span>
                                                <span class="cond-cnt <?= $c['met'] ? 'y' : 'n'; ?>">
                                                    <?= (int) $c['have']; ?>/<?= (int) $c['need']; ?>
                                                </span>
                                            </div>
                                            <div class="bar <?= $c['met'] ? 'ok' : ''; ?>"><div style="width:<?= $pct; ?>%"></div></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php $lastPlan = $req['plan'];
                            endforeach; ?>
                            <?php if (!$planMet && $missing): ?>
                                <div class="chip warn" style="margin-top:4px;">
                                    <i class="ph ph-list-checks"></i> Need: <?= htmlspecialchars(implode(' · ', array_slice($missing, 0, 3))); ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <div class="chip mut" style="margin-top:10px;">
                            <i class="ph ph-arrows-down-up"></i> Your whole leg counts, at any depth — not just direct referrals.
                        </div>
                    <?php endif; ?>
                </div>

                <div>
                    <!-- ================= RANK POWER (60-DAY CYCLE) ================= -->
                    <div class="card">
                        <div class="card-h">
                            <h3><i class="ph-fill ph-lightning"></i> Rank Power</h3>
                            <span class="chip"><?= (int) $power['cycle_days']; ?>-day cycle</span>
                        </div>

                        <?php if (!$power['enabled']): ?>
                            <div class="empty"><i class="ph ph-power"></i> Rank Power is currently disabled.</div>
                        <?php elseif (!$power['has_cycle']): ?>
                            <div class="empty"><i class="ph ph-hourglass"></i> No cycle is running yet. Your power rank appears once the first cycle opens.</div>
                        <?php else: ?>
                            <div class="pw-head">
                                <?= rank_badge_html(null, $power['badge_color'], 40); ?>
                                <div>
                                    <div class="pw-name <?= $power['rank'] ? '' : 'none'; ?>">
                                        <?= $power['rank'] ? htmlspecialchars($power['rank']) : 'No power rank yet'; ?>
                                    </div>
                                    <div class="pw-cycle">
                                        Cycle #<?= (int) $power['cycle_no']; ?> ·
                                        <?= date('d M', strtotime($power['start_date'])); ?> → <?= date('d M Y', strtotime($power['end_date'])); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="pw-timer"><span>Cycle progress</span><b><?= (int) $power['days_left']; ?> day<?= $power['days_left'] == 1 ? '' : 's'; ?> left</b></div>
                            <div class="bar wrn"><div style="width:<?= (int) $power['cycle_percent']; ?>%"></div></div>

                            <div class="pw-legs">
                                <div class="pw-leg"><small>Left</small><b><?= $rk_k($power['left_volume']); ?></b></div>
                                <div class="pw-leg"><small>Right</small><b><?= $rk_k($power['right_volume']); ?></b></div>
                                <div class="pw-leg"><small>Total</small><b><?= $rk_k($power['total_volume']); ?></b></div>
                            </div>

                            <p class="muted" style="font-size:11.5px;line-height:1.65;margin:0;">
                                Counts only this cycle's team volume and resets on <?= date('d M', strtotime($power['end_date'])); ?>.
                                Separate from your achievement rank — <b>your <?= htmlspecialchars($rank['name']); ?> rank is never affected</b>.
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- ================= GROUP INCENTIVE ================= -->
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
                            <i class="ph-fill ph-info" style="color:var(--primary)"></i>
                            <?php if ($incentive['paid_on'] === 'power'): ?>
                                Paid on your <b>Rank Power</b>, not your achievement rank.
                                <?php if ($rank['has_rank'] && $power['rank'] && $power['rank'] !== $rank['name']): ?>
                                    You are <b><?= htmlspecialchars($rank['name']); ?></b> permanently, but this cycle your
                                    power is <b><?= htmlspecialchars($power['rank']); ?></b> — paid at that level.
                                <?php else: ?>
                                    Every cycle resets, so a strong cycle is always worth having.
                                <?php endif; ?>
                            <?php else: ?>
                                Currently paid on your <b>achievement rank</b>.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- ================= RANK TIMELINE ================= -->
            <div class="card">
                <div class="card-h">
                    <h3><i class="ph ph-path"></i> Rank Timeline</h3>
                    <span class="chip"><?= $earnedCnt; ?> of <?= count($ladder); ?> earned</span>
                </div>
                <div class="rk-timeline-wrap">
                    <div class="rk-timeline">
                        <?php $total = count($ladder); foreach ($ladder as $i => $l):
                            $state = $l['is_current'] ? 'current' : ($l['achieved'] ? 'achieved' : 'locked');
                            ?>
                            <div class="rk-tl-node <?= $state; ?>"
                                title="<?= htmlspecialchars($l['name']); ?> — needs <?= $rk_n($l['required_volume'], 0); ?> BMAN team volume<?= $l['achieved_at'] ? ' · achieved ' . date('d M Y', strtotime($l['achieved_at'])) : ''; ?>">
                                <?php if ($l['is_current']): ?><span class="rk-tl-flag now">NOW</span>
                                <?php elseif ($l['is_next']): ?><span class="rk-tl-flag next">NEXT</span>
                                <?php elseif ($l['achieved']): ?><span class="rk-tl-flag done">DONE</span>
                                <?php else: ?><span class="rk-tl-flag" style="visibility:hidden">·</span><?php endif; ?>
                                <?= rank_badge_html($l['badge_image'], $l['badge_color'], 52, '', !$l['achieved'] && !$l['is_current']); ?>
                                <b><?= htmlspecialchars($l['name']); ?></b>
                                <small><?= $rk_k($l['required_volume']); ?> BMAN</small>
                            </div>
                            <?php if ($i < $total - 1): ?>
                                <div class="rk-tl-arrow <?= $l['achieved'] ? 'done' : ''; ?>"><i class="ph ph-arrow-right"></i></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <p class="muted" style="font-size:11.5px;margin:14px 0 0;line-height:1.6;">
                    <i class="ph-fill ph-lock-key-open" style="color:var(--primary)"></i>
                    Every rank you earn is permanent. Scroll to see the full journey to CHALLENGER.
                </p>
            </div>

            <div class="grid-2">
                <!-- ================= MY RANK JOURNEY (history log) ================= -->
                <div class="card">
                    <div class="card-h">
                        <h3><i class="ph ph-clock-clockwise"></i> My Rank Journey</h3>
                        <span class="chip"><?= count($history); ?> milestone<?= count($history) == 1 ? '' : 's'; ?></span>
                    </div>
                    <?php if (empty($history)): ?>
                        <div class="empty"><i class="ph ph-path"></i> Your journey starts with your first rank. Build team volume to get there.</div>
                    <?php else: ?>
                        <?php foreach (array_reverse($history) as $h): ?>
                            <div class="cond" style="border-top:1px solid var(--color-border);padding:10px 0;">
                                <div class="cond-top" style="margin-bottom:2px;">
                                    <b style="font-size:13px;color:var(--text-primary);"><?= htmlspecialchars($h['new_rank']); ?></b>
                                    <span class="muted" style="margin-left:auto;font-size:10.5px;"><?= date('d M Y', strtotime($h['achieved_at'])); ?></span>
                                </div>
                                <p class="muted" style="margin:2px 0 0;font-size:11.5px;line-height:1.55;">
                                    <?= $h['old_rank'] ? 'Promoted from ' . htmlspecialchars($h['old_rank']) : 'Your first rank'; ?>
                                    · <?= $rk_k($h['achieved_volume']); ?> BMAN team volume<?= $h['qualification_plan'] ? ' · ' . htmlspecialchars($h['qualification_plan']) : ''; ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- ================= RANK CERTIFICATES (cards) ================= -->
                <div class="card">
                    <div class="card-h">
                        <h3><i class="ph ph-certificate"></i> My Certificates</h3>
                        <span class="chip"><?= $certCnt; ?></span>
                    </div>
                    <?php if (!$certCnt): ?>
                        <div class="empty"><i class="ph ph-certificate"></i> Certificates are issued automatically when you reach a rank.</div>
                    <?php else: ?>
                        <div class="cert-grid">
                            <?php foreach ($certificates as $c): ?>
                                <a class="cert" target="_blank" href="<?= base_url('user/rank-certificate/' . rawurlencode($c['certificate_no'])); ?>">
                                    <?= rank_badge_html($c['badge_image'], $c['badge_color'], 40); ?>
                                    <div>
                                        <b><?= htmlspecialchars($c['rank_name']); ?></b>
                                        <div class="no mono"><?= htmlspecialchars($c['certificate_no']); ?></div>
                                        <div class="issued">Issued <?= date('d M Y', strtotime($c['generated_date'])); ?></div>
                                    </div>
                                    <i class="ph ph-arrow-square-out go"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <p class="muted" style="font-size:11px;margin:12px 0 0;">Open a certificate to print it or save it as a PDF.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ================= RANK REWARDS (cards) ================= -->
            <div class="card">
                <div class="card-h">
                    <h3><i class="ph-fill ph-gift"></i> Rank Rewards</h3>
                    <span class="chip"><?= $rewardCnt; ?> total</span>
                </div>

                <?php if (!$rewardCnt && ($msCurrent || $msNext)): ?>
                    <!-- Milestone preview: no reward paid yet — show what's coming. Current
                         rank is real and sharp; next rank's reward is real but blurred/locked
                         until it's actually earned. -->
                    <div class="rk-milestone">
                        <div class="rk-ms-box rk-ms-current">
                            <?= rank_badge_html($msCurrent['badge_image'] ?? null, $msCurrent['badge_color'] ?? '#9e9e9e', 52); ?>
                            <b><?= htmlspecialchars($msCurrent ? $msCurrent['name'] : 'Unranked'); ?></b>
                            <small><?= $msCurrent ? 'Current rank' : 'Not yet earned'; ?></small>
                        </div>
                        <?php if ($msNext): ?>
                            <div class="rk-ms-mid">
                                <span class="rk-ms-pct"><?= $hasNext ? (int) round($volPct) : 0; ?>%</span>
                                <i class="ph ph-arrow-right"></i>
                            </div>
                            <div class="rk-ms-box rk-ms-next">
                                <div class="rk-ms-lock"><i class="ph-fill ph-lock-simple"></i></div>
                                <div class="rk-ms-blurred">
                                    <?= rank_badge_html($msNext['badge_image'], $msNext['badge_color'], 52); ?>
                                    <b><?= htmlspecialchars($msNext['name']); ?></b>
                                    <small><?= htmlspecialchars($msRewardText($msNext)); ?></small>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="rk-ms-mid"><i class="ph-fill ph-check-circle" style="color:var(--rk-ok);"></i></div>
                            <div class="rk-ms-box">
                                <i class="ph-fill ph-crown" style="font-size:36px;color:#f1c40f;"></i>
                                <b>Highest rank</b><small>Permanent</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <p class="rk-ms-hint">
                        <?php if ($msNext): ?>
                            Reach <b><?= htmlspecialchars($msNext['name']); ?></b> to unlock this reward — it's credited to your wallet automatically.
                        <?php else: ?>
                            You hold the highest rank in BMAN. Every reward along the way is permanent.
                        <?php endif; ?>
                    </p>
                <?php elseif (!$rewardCnt): ?>
                    <div class="empty"><i class="ph ph-gift"></i> No rewards yet. Reach a rank and any reward is credited to your wallet automatically.</div>
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
                        <button class="btn-soft" onclick="rkReset()"><i class="ph ph-arrow-counter-clockwise"></i> Reset</button>
                    </div>

                    <div class="reward-grid" id="rkGrid">
                        <?php foreach ($rewards as $r):
                            $cls = $r['reward_status'] === 'paid' ? 'b-ok'
                                : ($r['reward_status'] === 'failed' ? 'b-bad'
                                    : ($r['reward_status'] === 'skipped' ? 'b-mut' : 'b-warn'));
                            $credited = !empty($r['wallet_ledger_id']);
                            ?>
                            <div class="reward-card" data-type="<?= htmlspecialchars($r['reward_type']); ?>" data-status="<?= htmlspecialchars($r['reward_status']); ?>">
                                <?php
                                // rank_rewards doesn't carry a badge image/color of its own —
                                // look it up off the ladder by name (cheap, ladder is already in memory).
                                $rBadge = null; $rColor = '#9e9e9e';
                                foreach ($ladder as $l) { if ($l['name'] === $r['rank_name']) { $rBadge = $l['badge_image']; $rColor = $l['badge_color']; break; } }
                                echo rank_badge_html($rBadge, $rColor, 44);
                                ?>
                                <div class="rc-body">
                                    <div class="rc-rank"><?= htmlspecialchars($r['rank_name']); ?></div>
                                    <div class="rc-amt">
                                        <?= $r['reward_type'] === 'physical' ? htmlspecialchars($r['note'] ?: 'Non-cash reward') : $rk_n($r['reward_amount'], 4); ?>
                                        <?php if ($r['reward_type'] !== 'physical'): ?><small><?= strtoupper($r['reward_type']); ?></small><?php endif; ?>
                                    </div>
                                    <div class="rc-meta">
                                        <span class="badge <?= $cls; ?>"><?= ucfirst($r['reward_status']); ?></span>
                                        <?php if ($credited): ?><span class="badge b-ok" style="margin-left:4px;">Wallet Credited</span><?php endif; ?>
                                        <br>
                                        <?= $r['rewarded_at'] ? 'Achieved ' . date('d M Y', strtotime($r['rewarded_at'])) : 'Pending payout'; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="empty" id="rkNone" style="display:none;"><i class="ph ph-magnifying-glass"></i> No rewards match your filters.</div>
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

        /* Rewards filters — client-side over the rendered reward cards */
        (function () {
            const q = document.getElementById('rkSearch');
            if (!q) return;                     // grid not rendered (member has no rewards)
            const type = document.getElementById('rkType');
            const status = document.getElementById('rkStatus');
            const cards = Array.from(document.querySelectorAll('#rkGrid .reward-card'));
            const none = document.getElementById('rkNone');

            function apply() {
                const term = q.value.trim().toLowerCase();
                let shown = 0;
                cards.forEach(c => {
                    const okT = !type.value || c.dataset.type === type.value;
                    const okS = !status.value || c.dataset.status === status.value;
                    const okQ = !term || c.textContent.toLowerCase().includes(term);
                    const show = okT && okS && okQ;
                    c.style.display = show ? '' : 'none';
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
