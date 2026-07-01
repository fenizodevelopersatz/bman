<?php
// ===================== RANK & REWARDS PAGE (BINARY • GRAPHICAL THEME) =====================

// Fallback demo values (replace with DB later)
$user = $user ?? (object) [
  'name' => 'Lucas',
  'uid' => 'NEXMAN123',
  'rank' => 'SILVER'
];

$rank = $rank ?? (object) [
  'current' => 'SILVER',
  'next' => 'GOLD',
  'progress' => 48,          // overall progress % (you can compute from rules)

  'active' => true,
  'kyc' => true,

  // next rank requirements (typical binary)
  'pairs_needed' => 12,
  'pairs_done' => 5,

  'directs_needed' => 3,
  'directs_done' => 1,

  // BV requirement to qualify (some plans use only weak leg / or both)
  'left_bv_need' => 2500,
  'right_bv_need' => 2500,
  'left_bv_done' => 1800,
  'right_bv_done' => 950,

  'team_volume_need' => 5000,
  'team_volume_done' => 2100,

  // carry forward (cycle)
  'cycle_label' => 'This Week',
  'carry_left' => 320,
  'carry_right' => 0,

  // extra labels (optional)
  'pair_value' => 1,           // if 1 pair = 1 unit in your system; change if needed
];

$rewards = $rewards ?? [];

// Rank ladder rules (connect from admin later)
$rankLadder = $rankLadder ?? [
  'BRONZE' => [
    'status' => 'Unlocked',
    'requirements' => [
      ['label' => 'Active Status', 'value' => 'Maintain monthly activity'],
      ['label' => 'Pairs', 'value' => '2 pairs / week'],
      ['label' => 'Directs', 'value' => '1 active direct'],
    ],
    'benefits' => ['Pairing bonus enabled', 'Basic matching', 'Rank badge']
  ],
  'SILVER' => [
    'status' => 'Current',
    'requirements' => [
      ['label' => 'KYC Verified', 'value' => 'Required'],
      ['label' => 'Active Status', 'value' => 'Monthly activity required'],
      ['label' => 'Pairs', 'value' => '8 pairs / week'],
      ['label' => 'Directs', 'value' => '2 active directs'],
      ['label' => 'Team Volume', 'value' => '2,000 GV'],
    ],
    'benefits' => ['Higher pairing %', 'Matching boost', 'Silver milestone reward']
  ],
  'GOLD' => [
    'status' => 'Locked',
    'requirements' => [
      ['label' => 'KYC Verified', 'value' => 'Required'],
      ['label' => 'Active Status', 'value' => 'Monthly activity required'],
      ['label' => 'Pairs', 'value' => '12 pairs / week'],
      ['label' => 'Directs', 'value' => '3 active directs'],
      ['label' => 'Left/Right BV', 'value' => '2,500 / 2,500 BV'],
      ['label' => 'Team Volume', 'value' => '5,000 GV'],
    ],
    'benefits' => ['Gold reward payout', 'Higher matching bonus', 'Priority payout / perks']
  ],
  'PLATINUM' => [
    'status' => 'Locked',
    'requirements' => [
      ['label' => 'Pairs', 'value' => '20 pairs / week'],
      ['label' => 'Directs', 'value' => '5 active directs'],
      ['label' => 'Team Volume', 'value' => '12,000 GV'],
    ],
    'benefits' => ['Leadership pool', 'Bigger rank rewards', 'Exclusive support']
  ],
  'DIAMOND' => [
    'status' => 'Locked',
    'requirements' => [
      ['label' => 'Pairs', 'value' => '40 pairs / week'],
      ['label' => 'Directs', 'value' => '8 active directs'],
      ['label' => 'Team Volume', 'value' => '30,000 GV'],
    ],
    'benefits' => ['Top leader rewards', 'Highest bonuses', 'Special benefits']
  ],
];

// Helpers
function pctRef($done, $need)
{
  $need = max(1, (float) $need);
  $done = max(0, (float) $done);
  return (int) max(0, min(100, round(($done / $need) * 100)));
}
function h($v)
{
  return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

// Calculations for UI
$pPairs = pctRef($rank->pairs_done ?? 0, $rank->pairs_needed ?? 1);
$pDirects = pctRef($rank->directs_done ?? 0, $rank->directs_needed ?? 1);

$weakLegSide = (($rank->right_bv_done ?? 0) <= ($rank->left_bv_done ?? 0)) ? 'Right' : 'Left';
$weakDone = ($weakLegSide === 'Right') ? ($rank->right_bv_done ?? 0) : ($rank->left_bv_done ?? 0);
$weakNeed = ($weakLegSide === 'Right') ? ($rank->right_bv_need ?? 0) : ($rank->left_bv_need ?? 0);
$pWeakBV = pctRef($weakDone, $weakNeed);

$carryLabel = ((int) ($rank->carry_left ?? 0)) . " L / " . ((int) ($rank->carry_right ?? 0)) . " R";
$needPairsLeft = max(0, (int) ($rank->pairs_needed ?? 0) - (int) ($rank->pairs_done ?? 0));
$needDirectsLeft = max(0, (int) ($rank->directs_needed ?? 0) - (int) ($rank->directs_done ?? 0));
$needWeakLeft = max(0, (int) $weakNeed - (int) $weakDone);

// Smart “Next action” tip
$primaryTip = "Keep balancing your legs and maintain activity to rank up.";
if (!$rank->kyc) {
  $primaryTip = "Complete KYC to avoid reward/payout holds.";
} else if (!$rank->active) {
  $primaryTip = "Activate your account (monthly activity/BV) to become eligible.";
} else {
  // pick the most blocked requirement
  $mins = [
    'pairs' => $pPairs,
    'directs' => $pDirects,
    'weakbv' => $pWeakBV
  ];
  asort($mins);
  $key = array_key_first($mins);
  if ($key === 'weakbv')
    $primaryTip = "Your weak leg is {$weakLegSide}. Add BV there first to generate more pairs faster.";
  if ($key === 'pairs')
    $primaryTip = "Focus on completing {$needPairsLeft} more pairs in this cycle. Weak leg BV decides your pairs.";
  if ($key === 'directs')
    $primaryTip = "You need {$needDirectsLeft} more active directs. Invite & keep directs active to unlock rank.";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <?php $this->load->view('user/layout/v2/user_style'); ?>
  <script src="https://unpkg.com/@phosphor-icons/web"></script>

  <style>
    /* ===================== QUICK BASE ===================== */
    :root {
      --p: var(--primary, #6E56CF);
      --txt: var(--text-main, #111);
      --muted: var(--text-muted, #6b7280);
      --card: #fff;
      --stroke: #f1f1f6;
      --soft: #f7f7fb;
      --shadow: 0 12px 30px rgba(0, 0, 0, 0.04);
      --r: 22px;
    }

    .titlebar {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      gap: 12px;
      margin: 8px 0 18px;
    }

    .titlebar h2 {
      margin: 0;
      font-size: 18px;
      font-weight: 1000;
      color: var(--txt);
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .titlebar h2 i {
      color: var(--p);
      font-size: 20px;
    }

    .titlebar .sub {
      margin-top: 4px;
      font-size: 12px;
      color: var(--muted);
      font-weight: 800;
    }

    .actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .btn-soft {
      border: 1px solid var(--stroke);
      background: #fff;
      border-radius: 14px;
      padding: 10px 12px;
      font-weight: 1000;
      cursor: pointer;
      font-size: 12px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-main {
      border: none;
      background: var(--p);
      color: #fff;
      border-radius: 14px;
      padding: 10px 12px;
      font-weight: 1000;
      cursor: pointer;
      font-size: 12px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-dark {
      border: none;
      background: #111;
      color: #fff;
      border-radius: 14px;
      padding: 10px 12px;
      font-weight: 1000;
      cursor: pointer;
      font-size: 12px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .card {
      background: var(--card);
      border: 1px solid #f5f5f7;
      border-radius: var(--r);
      padding: 14px;
      box-shadow: var(--shadow);
    }

    .card-h {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 12px;
    }

    .card-h h3 {
      margin: 0;
      font-size: 14px;
      font-weight: 1100;
    }

    .chip {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 7px 10px;
      border-radius: 999px;
      border: 1px solid #eeecff;
      background: #efedfb;
      color: var(--p);
      font-size: 10px;
      font-weight: 1100;
    }

    /* ===================== HERO LIKE YOUR SCREENSHOT ===================== */
    .hero {
      border: none;
      color: #fff;
      overflow: hidden;
      position: relative;
      background: radial-gradient(1200px 380px at 20% 15%, rgba(255, 255, 255, .14), transparent 50%),
        linear-gradient(135deg, #6E56CF 0%, #3b2f8f 100%);
      border-radius: 28px;
      padding: 22px;
    }

    .hero::after {
      content: "";
      position: absolute;
      right: -120px;
      top: -90px;
      width: 360px;
      height: 360px;
      border-radius: 50%;
      background: rgba(255, 255, 255, .10);
      filter: blur(0.5px);
    }

    .hero-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 14px;
      position: relative;
      z-index: 1;
    }

    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      background: rgba(0, 0, 0, .22);
      border: 1px solid rgba(255, 255, 255, .18);
      padding: 10px 14px;
      border-radius: 999px;
      font-weight: 1100;
      font-size: 12px;
    }

    .hero-badge i {
      font-size: 16px;
    }

    .hero-title {
      margin: 16px 0 0;
      font-size: 32px;
      font-weight: 1200;
      line-height: 1.05;
    }

    .hero-sub {
      margin: 10px 0 0;
      max-width: 700px;
      opacity: .92;
      font-weight: 800;
      font-size: 13px;
      line-height: 1.55;
    }

    .hero-grid {
      display: grid;
      grid-template-columns: 1.2fr .8fr;
      gap: 16px;
      margin-top: 18px;
      position: relative;
      z-index: 1;
    }

    .mini-stats {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      margin-top: 16px;
    }

    .mini {
      border: 1px solid rgba(255, 255, 255, .18);
      background: rgba(255, 255, 255, .10);
      border-radius: 18px;
      padding: 14px;
    }

    .mini small {
      display: block;
      opacity: .85;
      font-weight: 1000;
      font-size: 11px;
      letter-spacing: .2px;
    }

    .mini b {
      display: block;
      margin-top: 8px;
      font-size: 18px;
      font-weight: 1300;
    }

    .mini .icon {
      width: 36px;
      height: 36px;
      border-radius: 14px;
      display: grid;
      place-items: center;
      background: rgba(0, 0, 0, .20);
      border: 1px solid rgba(255, 255, 255, .16);
      margin-bottom: 10px;
    }

    .mini .icon i {
      font-size: 18px;
      color: #fff;
    }

    .hero-actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-top: 14px;
    }

    .btn-hero {
      border: none;
      border-radius: 16px;
      padding: 12px 14px;
      font-weight: 1100;
      cursor: pointer;
      font-size: 12px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-hero.primary {
      background: #111;
      color: #fff;
    }

    .btn-hero.ghost {
      background: rgba(255, 255, 255, .12);
      color: #fff;
      border: 1px solid rgba(255, 255, 255, .20);
    }

    /* progress ring card */
    .progress-card {
      border: 1px solid rgba(255, 255, 255, .16);
      background: rgba(255, 255, 255, .10);
      border-radius: 22px;
      padding: 18px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 240px;
    }

    .rankProgressCard {
      width: 320px;
      border-radius: 22px;
      padding: 18px;
      color: #fff;
      border: 1px solid rgba(255, 255, 255, .18);
      background: rgba(255, 255, 255, .10);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      box-shadow: 0 18px 60px rgba(0, 0, 0, .18);
    }

    .ringWrap {
      position: relative;
      width: 170px;
      height: 170px;
      margin: 6px auto 12px;
    }

    .ringSvg {
      width: 170px;
      height: 170px;
      transform: rotate(-90deg);
      /* start from top */
      filter: drop-shadow(0 14px 28px rgba(0, 0, 0, .20));
    }

    .ringTrack {
      fill: none;
      stroke: rgba(255, 255, 255, .22);
      stroke-width: 14;
    }

    .ringBar {
      fill: none;
      stroke: rgba(255, 255, 255, .98);
      stroke-width: 14;
      stroke-linecap: round;

      /* SVG progress math */
      stroke-dasharray: 289;
      /* 2πr where r=46 => ~289 */
      stroke-dashoffset: calc(289 - (289 * var(--p)) / 100);

      transition: stroke-dashoffset .8s ease;
    }

    .ringCenter {
      position: absolute;
      inset: 18px;
      border-radius: 999px;
      background: rgba(0, 0, 0, .18);
      border: 1px solid rgba(255, 255, 255, .18);
      display: grid;
      place-items: center;
      text-align: center;
    }

    .ringCenter .val {
      font-size: 28px;
      font-weight: 1200;
      letter-spacing: .2px;
      line-height: 1;
    }

    .ringCenter .sub {
      margin-top: 6px;
      font-size: 11px;
      font-weight: 1000;
      opacity: .92;
      letter-spacing: .6px;
    }

    .rpText {
      text-align: center;
      margin-top: 6px;
    }

    .rpText .title {
      font-size: 13px;
      font-weight: 1200;
    }

    .rpText .goal {
      margin-top: 4px;
      font-size: 12px;
      font-weight: 900;
      opacity: .92;
    }

    .rpBadges {
      display: flex;
      gap: 10px;
      justify-content: center;
      flex-wrap: wrap;
      margin-top: 14px;
    }

    .rpBadge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 12px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 1100;
      border: 1px solid rgba(255, 255, 255, .18);
      background: rgba(255, 255, 255, .10);
    }

    .rpBadge.ok {
      background: rgba(16, 185, 129, .16);
      border-color: rgba(16, 185, 129, .30);
      color: #d1fae5;
    }

    .pcap {
      margin-top: 12px;
      text-align: center;
    }

    .pcap b {
      display: block;
      font-size: 13px;
      font-weight: 1200;
    }

    .pcap small {
      display: block;
      opacity: .9;
      font-weight: 900;
      margin-top: 4px;
    }

    /* ===================== BELOW HERO: 2 CARDS (Requirements + Strategy) ===================== */
    .grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
      margin-top: 14px;
    }

    .req-card,
    .strategy-card {
      border-radius: 26px;
    }

    .section-title {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 18px;
      font-weight: 1200;
      margin: 0;
    }

    .section-title i {
      color: var(--p);
    }

    .req-item {
      border: 1px solid var(--stroke);
      background: var(--soft);
      border-radius: 18px;
      padding: 14px;
      margin-top: 12px;
    }

    .req-head {
      display: flex;
      justify-content: space-between;
      gap: 12px;
      align-items: center;
    }

    .req-head b {
      font-size: 14px;
      font-weight: 1200;
    }

    .req-head span {
      font-size: 12px;
      font-weight: 1100;
      color: var(--muted);
    }

    .bar {
      height: 10px;
      background: #ecebff;
      border-radius: 999px;
      overflow: hidden;
      margin-top: 12px;
    }

    .bar>div {
      height: 100%;
      background: var(--p);
      border-radius: 999px;
    }

    .req-foot {
      display: flex;
      justify-content: space-between;
      gap: 12px;
      margin-top: 10px;
      font-size: 12px;
      font-weight: 1000;
    }

    .pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 10px;
      border-radius: 999px;
      border: 1px solid #eeecff;
      background: #efedfb;
      color: var(--p);
      font-size: 11px;
      font-weight: 1100;
    }

    .pill.ok {
      border-color: #dcfce7;
      background: #ecfdf3;
      color: #0f9d58;
    }

    .pill.warn {
      border-color: #ffedd5;
      background: #fff7ed;
      color: #c2410c;
    }

    .strategy-box {
      border: 1px solid var(--stroke);
      background: #fff;
      border-radius: 18px;
      padding: 14px;
      display: flex;
      gap: 12px;
      align-items: flex-start;
      margin-top: 12px;
    }

    .sicon {
      width: 46px;
      height: 46px;
      border-radius: 16px;
      background: #efedfb;
      border: 1px solid #eeecff;
      display: grid;
      place-items: center;
      color: var(--p);
      flex: 0 0 auto;
    }

    .sicon i {
      font-size: 20px;
    }

    .strategy-box b {
      display: block;
      font-size: 14px;
      font-weight: 1200;
    }

    .strategy-box p {
      margin: 6px 0 0;
      font-size: 12px;
      color: var(--muted);
      font-weight: 900;
      line-height: 1.55;
    }

    .cta-wide {
      width: 100%;
      border: none;
      border-radius: 18px;
      padding: 14px 14px;
      margin-top: 14px;
      cursor: pointer;
      font-weight: 1200;
      background: var(--p);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    /* ===================== RANK PATH (LESS CONFUSION) ===================== */
    .path {
      display: flex;
      gap: 12px;
      overflow: auto;
      padding-bottom: 6px;
    }

    .step {
      min-width: 260px;
      border: 1px solid #f5f5f7;
      border-radius: 22px;
      padding: 14px;
      background: #fff;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.03);
    }

    .step-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
    }

    .step-top b {
      font-size: 13px;
      font-weight: 1300;
    }

    .status-pill {
      font-size: 10px;
      font-weight: 1200;
      padding: 6px 10px;
      border-radius: 999px;
      border: 1px solid var(--stroke);
      background: var(--soft);
      color: #111;
    }

    .status-pill.current {
      border-color: #eeecff;
      background: #efedfb;
      color: var(--p);
    }

    .status-pill.lock {
      background: #fff;
      color: var(--muted);
    }

    .rlist {
      margin-top: 12px;
      display: grid;
      gap: 8px;
    }

    .rrow {
      display: flex;
      justify-content: space-between;
      gap: 10px;
      font-size: 12px;
      font-weight: 1000;
    }

    .rrow span {
      color: var(--muted);
      font-weight: 1000;
    }

    .ben {
      margin-top: 10px;
      display: flex;
      gap: 8px;
      align-items: flex-start;
      font-size: 12px;
      font-weight: 900;
    }

    .ben i {
      color: var(--p);
      margin-top: 2px;
    }

    /* ===================== REWARDS TABLE (SAME AS BEFORE) ===================== */
    .filters {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      align-items: center;
    }

    .inp,
    .sel {
      border: 1px solid var(--stroke);
      background: var(--soft);
      border-radius: 14px;
      padding: 11px 12px;
      outline: none;
      font-size: 12px;
    }

    .inp {
      flex: 1;
      min-width: 220px;
    }

    .inp:focus,
    .sel:focus {
      background: #fff;
      border-color: #dcd7ff;
      box-shadow: 0 0 0 4px rgba(110, 86, 207, 0.10);
    }

    .table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0 10px;
    }

    .table th {
      font-size: 11px;
      color: var(--muted);
      text-align: left;
      font-weight: 1100;
      padding: 0 10px;
    }

    .tr {
      background: #fff;
      border: 1px solid #f5f5f7;
      border-radius: 18px;
      box-shadow: 0 12px 25px rgba(0, 0, 0, 0.03);
    }

    .tr td {
      padding: 12px 10px;
      font-size: 12px;
      font-weight: 900;
      color: #111;
    }

    .td-title b {
      display: block;
      font-size: 12px;
      font-weight: 1100;
    }

    .td-title small {
      display: block;
      font-size: 11px;
      color: var(--muted);
      font-weight: 800;
      margin-top: 2px;
    }

    .badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 10px;
      border-radius: 999px;
      font-size: 10px;
      font-weight: 1100;
      border: 1px solid var(--stroke);
      background: #fff;
    }

    .b-ok {
      border-color: #dcfce7;
      background: #ecfdf3;
      color: #0f9d58;
    }

    .b-warn {
      border-color: #ffedd5;
      background: #fff7ed;
      color: #c2410c;
    }

    .b-bad {
      border-color: #fee2e2;
      background: #fef2f2;
      color: #b91c1c;
    }

    .amt {
      font-size: 13px;
      font-weight: 1200;
    }

    .btn-mini {
      border: 1px solid var(--stroke);
      background: #fff;
      border-radius: 12px;
      padding: 9px 10px;
      font-size: 12px;
      font-weight: 1000;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .empty {
      border: 1px dashed #e7e7f3;
      background: #fbfbff;
      border-radius: 18px;
      padding: 18px;
      text-align: center;
      color: var(--muted);
      font-weight: 900;
      font-size: 12px;
    }

    /* ===================== MODAL ===================== */
    .modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(10, 10, 20, .35);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 99999;
      padding: 14px;
    }

    .modal {
      width: min(780px, 100%);
      background: #fff;
      border-radius: 24px;
      border: 1px solid #f5f5f7;
      box-shadow: 0 26px 70px rgba(0, 0, 0, .18);
      overflow: hidden;
    }

    .modal-h {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      padding: 14px 16px;
      border-bottom: 1px solid #f5f5f7;
    }

    .modal-h b {
      font-size: 14px;
      font-weight: 1200;
    }

    .xbtn {
      width: 40px;
      height: 40px;
      border-radius: 14px;
      border: 1px solid var(--stroke);
      background: #fff;
      cursor: pointer;
      display: grid;
      place-items: center;
      font-size: 18px;
    }

    .modal-b {
      padding: 14px 16px;
    }

    .note {
      font-size: 12px;
      color: var(--muted);
      font-weight: 900;
      line-height: 1.55;
    }

    .row2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      margin-top: 12px;
    }

    .box {
      border: 1px solid var(--stroke);
      background: var(--soft);
      border-radius: 18px;
      padding: 12px;
    }

    .box small {
      display: block;
      font-size: 10px;
      color: var(--muted);
      font-weight: 1100;
    }

    .box b {
      display: block;
      font-size: 13px;
      font-weight: 1200;
      margin-top: 6px;
    }

    .box ul {
      margin: 10px 0 0 18px;
      color: #111;
      font-size: 12px;
      font-weight: 900;
    }

    .box li {
      margin: 7px 0;
      color: #111;
    }

    .btn-full {
      width: 100%;
      border: none;
      border-radius: 16px;
      padding: 12px 14px;
      cursor: pointer;
      font-weight: 1200;
      background: #efedfb;
      color: var(--p);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-full.primary {
      background: var(--p);
      color: #fff;
    }

    @media(max-width:1200px) {
      .hero-grid {
        grid-template-columns: 1fr;
      }

      .mini-stats {
        grid-template-columns: 1fr;
      }

      .grid-2 {
        grid-template-columns: 1fr;
      }
    }

    /* ===================== RESPONSIVE PATCH (RANK & REWARDS) ===================== */
    * {
      box-sizing: border-box;
    }

    html,
    body {
      width: 100%;
      overflow-x: hidden;
    }

    /* Titlebar */
    @media (max-width: 900px) {
      .titlebar {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }

      .actions {
        width: 100%;
      }

      .actions button {
        flex: 1 1 auto;
      }
    }

    @media (max-width: 520px) {
      .actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
      }

      .actions button {
        width: 100%;
        justify-content: center;
      }
    }

    /* HERO */
    @media (max-width: 1200px) {
      .hero {
        padding: 18px;
        border-radius: 24px;
      }

      .hero-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 720px) {
      .hero-top {
        flex-direction: column;
        align-items: flex-start;
      }

      .hero-title {
        font-size: 24px;
      }

      .hero-sub {
        font-size: 12px;
      }
    }

    /* Mini stats: 3 -> 2 -> 1 */
    @media (max-width: 900px) {
      .mini-stats {
        grid-template-columns: 1fr 1fr;
      }
    }

    @media (max-width: 520px) {
      .mini-stats {
        grid-template-columns: 1fr;
      }

      .mini {
        padding: 12px;
        border-radius: 16px;
      }

      .mini b {
        font-size: 16px;
      }
    }

    /* Hero buttons */
    @media (max-width: 520px) {
      .hero-actions {
        display: grid;
        grid-template-columns: 1fr;
      }

      .btn-hero {
        width: 100%;
        justify-content: center;
      }
    }

    /* Progress ring card full width on mobile */
    @media (max-width: 1200px) {
      .rankProgressCard {
        width: 100%;
      }
    }

    @media (max-width: 520px) {
      .rankProgressCard {
        padding: 14px;
        border-radius: 18px;
      }

      .ringWrap {
        width: 150px;
        height: 150px;
      }

      .ringSvg {
        width: 150px;
        height: 150px;
      }

      .ringCenter {
        inset: 16px;
      }

      .ringCenter .val {
        font-size: 24px;
      }
    }

    /* Requirements + Strategy cards */
    @media (max-width: 1200px) {
      .grid-2 {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 520px) {
      .card {
        padding: 12px;
        border-radius: 18px;
      }

      .req-item {
        padding: 12px;
        border-radius: 16px;
      }

      .strategy-box {
        padding: 12px;
        border-radius: 16px;
      }

      .sicon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
      }
    }

    /* Rank path: make it feel like cards on mobile */
    @media (max-width: 520px) {
      .path {
        gap: 10px;
      }

      .step {
        min-width: 86vw;
      }
    }

    /* Filters: stack and full width */
    @media (max-width: 720px) {
      .filters {
        flex-direction: column;
        align-items: stretch;
      }

      .inp {
        min-width: 100%;
        width: 100%;
      }

      .sel {
        width: 100%;
      }
    }

    /* ===== Rewards table -> cards on mobile (NO HTML change) ===== */
    @media (max-width: 780px) {
      #rwTable {
        border-spacing: 0 12px;
      }

      #rwTable thead {
        display: none;
      }

      #rwTable tbody,
      #rwTable tr,
      #rwTable td {
        display: block;
        width: 100%;
      }

      #rwTable tbody tr.tr {
        padding: 12px;
        border-radius: 18px;
        border: 1px solid #f5f5f7;
        box-shadow: 0 12px 25px rgba(0, 0, 0, 0.03);
        margin-bottom: 12px;
        background: #fff;
      }

      #rwTable tbody tr.tr td {
        padding: 8px 0 !important;
      }

      /* Label + value layout (we add labels via nth-child) */
      #rwTable tbody tr.tr td {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: flex-start;
      }

      #rwTable tbody tr.tr td::before {
        font-size: 10px;
        color: var(--muted);
        font-weight: 1100;
        flex: 0 0 90px;
        max-width: 90px;
      }

      #rwTable tbody tr.tr td:nth-child(1)::before {
        content: "Reward";
      }

      #rwTable tbody tr.tr td:nth-child(2)::before {
        content: "Type";
      }

      #rwTable tbody tr.tr td:nth-child(3)::before {
        content: "Date";
      }

      #rwTable tbody tr.tr td:nth-child(4)::before {
        content: "Ref";
      }

      #rwTable tbody tr.tr td:nth-child(5)::before {
        content: "Amount";
      }

      #rwTable tbody tr.tr td:nth-child(6)::before {
        content: "Action";
      }

      /* Make action button align right nicely */
      #rwTable tbody tr.tr td:nth-child(6) {
        justify-content: flex-end;
      }
    }

    /* Modal fits on mobile */
    @media (max-width: 520px) {
      .modal {
        border-radius: 18px;
      }

      .modal-b {
        padding: 12px;
      }

      .row2 {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>
  <div class="app-container">
    <?php $this->load->view('user/layout/v2/user_sidebar'); ?>

    <main class="main-content">
      <?php $this->load->view('user/layout/v2/user_header'); ?>

      <!-- Title -->
      <div class="titlebar">
        <div>
          <h2><i class="ph ph-medal"></i> Rank & Rewards</h2>
          <div class="sub">Clear Binary rank rules: weak-leg pairs, BV targets, directs, eligibility + carry forward.
          </div>
        </div>
        <div class="actions">
          <button class="btn-soft" type="button" onclick="openRules()"><i class="ph ph-info"></i> Rules</button>
          <button class="btn-soft" type="button" onclick="window.print()"><i class="ph ph-printer"></i> Print</button>
          <button class="btn-main" type="button" onclick="location.href='<?= base_url('user/commissions'); ?>'"><i
              class="ph ph-coins"></i> Earnings</button>
          <button class="btn-dark" type="button" onclick="location.href='<?= base_url('user/withdraw'); ?>'"><i
              class="ph ph-bank"></i> Withdraw</button>
        </div>
      </div>

      <!-- HERO (GRAPHICAL THEME) -->
      <div class="hero">
        <div class="hero-top">
          <div class="hero-badge">
            <i class="ph ph-crown-simple"></i>
            Current Rank: <span style="font-weight:1300;"><?= h($rank->current ?? 'SILVER'); ?></span>
          </div>

          <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end;">
            <div class="hero-badge" style="background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.20);">
              <i class="ph ph-flag-checkered"></i> Next Milestone: <span
                style="font-weight:1300; color:#FFD84D;"><?= h($rank->next ?? 'GOLD'); ?></span>
            </div>
          </div>
        </div>

        <div class="hero-grid">
          <!-- left -->
          <div>
            <div class="hero-title">Unlock Your Potential.<br>Next Milestone: <span
                style="color:#FFD84D;"><?= h($rank->next ?? 'GOLD'); ?></span></div>
            <div class="hero-sub">
              Your pairing depends on the <b>weak leg BV</b>. Keep your directs active and complete cycle pairs to
              unlock the next rank.
              <br><br>
              <b>Quick Tip:</b> <?= h($primaryTip); ?>
            </div>

            <div class="mini-stats">
              <div class="mini">
                <div class="icon"><i class="ph ph-arrow-bend-up-left"></i></div>
                <small>CARRY FORWARD</small>
                <b><?= h($carryLabel); ?></b>
                <small style="margin-top:6px;opacity:.85;">(Cycle: <?= h($rank->cycle_label ?? 'This Week'); ?>)</small>
              </div>

              <div class="mini">
                <div class="icon"><i class="ph ph-users-three"></i></div>
                <small>ACTIVE DIRECTS</small>
                <b><?= (int) ($rank->directs_done ?? 0); ?> / <?= (int) ($rank->directs_needed ?? 0); ?></b>
                <small style="margin-top:6px;opacity:.85;">Need: <?= (int) $needDirectsLeft; ?> more</small>
              </div>

              <div class="mini">
                <div class="icon"><i class="ph ph-git-merge"></i></div>
                <small>CYCLE PAIRS</small>
                <b><?= (int) ($rank->pairs_done ?? 0); ?> / <?= (int) ($rank->pairs_needed ?? 0); ?></b>
                <small style="margin-top:6px;opacity:.85;">Need: <?= (int) $needPairsLeft; ?> more</small>
              </div>
            </div>

            <div class="hero-actions">
              <button class="btn-hero primary" type="button"
                onclick="location.href='<?= base_url('user/binary_tree'); ?>'">
                Grow My Team <i class="ph ph-tree-structure"></i>
              </button>
              <button class="btn-hero ghost" type="button" onclick="openRules()">
                Strategy Guide <i class="ph ph-book-open"></i>
              </button>
            </div>
          </div>

          <!-- right -->
          <!-- Rank Progress Card -->
          <div class="rankProgressCard" style="--p:<?= (int) $rank->progress ?>;">
            <div class="ringWrap" role="img" aria-label="Rank Progress 48% complete">
              <svg class="ringSvg" viewBox="0 0 120 120">
                <!-- Track -->
                <circle class="ringTrack" cx="60" cy="60" r="46"></circle>

                <!-- Progress -->
                <circle class="ringBar" cx="60" cy="60" r="46"></circle>
              </svg>

              <div class="ringCenter">
                <div class="val"><span class="num">48</span>%</div>
                <div class="sub">COMPLETE</div>
              </div>
            </div>

            <div class="rpText">
              <div class="title">Rank Progress</div>
              <div class="goal">Goal: <b>GOLD</b> Tier</div>
            </div>

            <div class="rpBadges">
              <span class="rpBadge ok"><i class="ph ph-activity"></i> Active</span>
              <span class="rpBadge ok"><i class="ph ph-identification-card"></i> KYC</span>
            </div>
          </div>

        </div>
      </div>

      <!-- REQUIREMENTS + STRATEGY (LIKE YOUR SCREENSHOT) -->
      <div class="grid-2">
        <!-- Requirements -->
        <div class="card req-card">
          <div class="card-h" style="margin-bottom:0;">
            <h3 class="section-title"><i class="ph ph-target"></i> Requirements to Level Up</h3>
            <span class="chip"><i class="ph ph-flag-checkered"></i> Target: <?= h($rank->next ?? 'GOLD'); ?></span>
          </div>

          <!-- Pairs -->
          <div class="req-item">
            <div class="req-head">
              <b>Binary Pairs Matching</b>
              <span><?= (int) ($rank->pairs_done ?? 0); ?> / <?= (int) ($rank->pairs_needed ?? 0); ?></span>
            </div>
            <div class="bar">
              <div style="width:<?= $pPairs ?>%"></div>
            </div>
            <div class="req-foot">
              <span class="pill <?= $pPairs >= 100 ? 'ok' : 'warn' ?>"><i class="ph ph-gauge"></i>
                <?= $pPairs ?>%</span>
              <span style="color:var(--muted);font-weight:1000;">Pairs are counted from <b>weak-leg</b> volume.</span>
            </div>
          </div>

          <!-- Weak leg BV -->
          <div class="req-item">
            <div class="req-head">
              <b>Weak Leg BV (<?= h($weakLegSide); ?>)</b>
              <span><?= (int) $weakDone; ?> / <?= (int) $weakNeed; ?> BV</span>
            </div>
            <div class="bar">
              <div style="width:<?= $pWeakBV ?>%"></div>
            </div>
            <div class="req-foot">
              <span class="pill <?= $pWeakBV >= 100 ? 'ok' : 'warn' ?>"><i class="ph ph-chart-bar"></i>
                <?= $pWeakBV ?>%</span>
              <span style="color:var(--muted);font-weight:1000;">Need <b><?= (int) $needWeakLeft; ?> BV</b> more on weak
                side.</span>
            </div>
          </div>

          <!-- Directs -->
          <div class="req-item">
            <div class="req-head">
              <b>Direct Referrals (Active)</b>
              <span><?= (int) ($rank->directs_done ?? 0); ?> / <?= (int) ($rank->directs_needed ?? 0); ?></span>
            </div>
            <div class="bar">
              <div style="width:<?= $pDirects ?>%"></div>
            </div>
            <div class="req-foot">
              <span class="pill <?= $pDirects >= 100 ? 'ok' : 'warn' ?>"><i class="ph ph-user-plus"></i>
                <?= $pDirects ?>%</span>
              <span style="color:var(--muted);font-weight:1000;">Directs must stay <b>active</b> as per plan.</span>
            </div>
          </div>
        </div>

        <!-- Growth Strategy -->
        <div class="card strategy-card">
          <div class="card-h" style="margin-bottom:0;">
            <h3 class="section-title"><i class="ph ph-sparkle"></i> Growth Strategy</h3>
            <span class="chip"><i class="ph ph-lightbulb"></i> Smart Tips</span>
          </div>

          <div class="strategy-box">
            <div class="sicon"><i class="ph ph-arrows-left-right"></i></div>
            <div>
              <b>Balance Your Legs</b>
              <p>
                Your <b>weak leg</b> decides pairs. Focus BV on <b><?= h($weakLegSide); ?> leg</b> first to increase
                pairing frequency.
              </p>
            </div>
          </div>

          <div class="strategy-box">
            <div class="sicon"><i class="ph ph-users-three"></i></div>
            <div>
              <b>Team Support</b>
              <p>
                Help your directs activate/renew so they count as <b>active directs</b>. You still need
                <b><?= (int) $needDirectsLeft; ?></b> active directs for <?= h($rank->next); ?>.
              </p>
            </div>
          </div>

          <div class="strategy-box">
            <div class="sicon"><i class="ph ph-shield-check"></i></div>
            <div>
              <b>Eligibility & Payout Safety</b>
              <p>
                Keep <b>KYC + Bank</b> updated. If required, rewards can be held until verified.
              </p>
            </div>
          </div>

          <button class="cta-wide" type="button" onclick="location.href='<?= base_url('user/binary_tree'); ?>'">
            Analyze Tree Stats <i class="ph ph-chart-line-up"></i>
          </button>
        </div>
      </div>

      <!-- Rank Path (less confusion, more structured) -->
      <div class="card" style="margin-top:14px;">
        <div class="card-h">
          <h3>Rank Path</h3>
          <span class="chip"><i class="ph ph-road-horizon"></i> Rules per rank</span>
        </div>

        <div class="path">
          <?php foreach ($rankLadder as $rName => $rData): ?>
            <?php
            $pillClass = 'lock';
            if (($rData['status'] ?? '') === 'Current')
              $pillClass = 'current';
            if (($rData['status'] ?? '') === 'Unlocked')
              $pillClass = '';
            ?>
            <div class="step">
              <div class="step-top">
                <b><?= h($rName); ?></b>
                <span class="status-pill <?= $pillClass; ?>"><?= h($rData['status'] ?? 'Locked'); ?></span>
              </div>

              <div class="rlist">
                <?php foreach (($rData['requirements'] ?? []) as $req): ?>
                  <div class="rrow">
                    <span><?= h($req['label']); ?></span>
                    <b><?= h($req['value']); ?></b>
                  </div>
                <?php endforeach; ?>
              </div>

              <div style="margin-top:12px;">
                <?php foreach (($rData['benefits'] ?? []) as $ben): ?>
                  <div class="ben"><i class="ph ph-check-circle"></i> <?= h($ben); ?></div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Rewards History -->
      <div class="card" style="margin-top:14px;">
        <div class="card-h">
          <h3>Rewards History</h3>
          <span class="chip"><i class="ph ph-gift"></i> Payouts</span>
        </div>

        <div class="filters">
          <input class="inp" id="q" placeholder="Search: reward, ref, date, type..." />
          <select class="sel" id="type">
            <option value="">All Types</option>
            <option value="RANK">Rank</option>
            <option value="LEADER">Leader</option>
            <option value="TARGET">Target</option>
            <option value="BONUS">Bonus</option>
          </select>
          <select class="sel" id="status">
            <option value="">All Status</option>
            <option value="APPROVED">Approved</option>
            <option value="PENDING">Pending</option>
            <option value="REJECTED">Rejected</option>
          </select>
          <button class="btn-soft" type="button" onclick="resetFilters()"><i class="ph ph-x-circle"></i> Reset</button>
        </div>

        <div style="margin-top:10px; overflow:auto;">
          <table class="table" id="rwTable">
            <thead>
              <tr>
                <th style="width:42%;">Reward</th>
                <th>Type</th>
                <th>Date</th>
                <th>Ref</th>
                <th style="text-align:right;">Amount</th>
                <th style="width:90px;"></th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($rewards)):
                foreach ($rewards as $r): ?>
                  <?php
                  $st = strtoupper($r->status ?? 'PENDING');
                  $badge = $st === 'APPROVED' ? 'b-ok' : ($st === 'PENDING' ? 'b-warn' : 'b-bad');
                  $typeV = strtoupper($r->type ?? 'RANK');
                  ?>
                  <tr class="tr" data-title="<?= h(strtolower($r->title ?? '')); ?>"
                    data-ref="<?= h(strtolower($r->ref ?? '')); ?>" data-type="<?= h($typeV); ?>"
                    data-status="<?= h($st); ?>" data-date="<?= h(strtolower($r->date ?? '')); ?>">
                    <td class="td-title">
                      <b><?= h($r->title ?? '—'); ?></b>
                      <small><?= h($r->note ?? ''); ?></small>
                    </td>
                    <td><span class="badge"><i class="ph ph-tag"></i> <?= h($typeV); ?></span></td>
                    <td><?= h($r->date ?? '—'); ?></td>
                    <td><span class="badge"><i class="ph ph-hash"></i> <?= h($r->ref ?? '—'); ?></span></td>
                    <td style="text-align:right;">
                      <div class="amt"><?= currency_info()->currency_symbol; ?>
                        <?= number_format((float) ($r->amount ?? 0), 2); ?>
                      </div>
                      <div style="margin-top:6px;"><span class="badge <?= $badge; ?>"><i class="ph ph-seal-check"></i>
                          <?= h($st); ?></span></div>
                    </td>
                    <td style="text-align:right;">
                      <button class="btn-mini" type="button" onclick="viewReward('<?= h($r->ref ?? ''); ?>')">
                        <i class="ph ph-eye"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; else: ?>
                <tr>
                  <td colspan="6">
                    <div class="empty">No rewards yet. Complete requirements to unlock rank rewards.</div>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </main>

    <aside class="right-panel">
      <?php $this->load->view('user/layout/v2/user_inner_right_panle'); ?>
    </aside>
  </div>

  <!-- Rules Modal -->
  <div class="modal-backdrop" id="rulesModal">
    <div class="modal">
      <div class="modal-h">
        <b>Binary Rules (Simple Explanation)</b>
        <button class="xbtn" onclick="closeRules()"><i class="ph ph-x"></i></button>
      </div>
      <div class="modal-b">
        <div class="note">
          This is a UI template. You can load exact rules from admin settings and display them here.
        </div>

        <div class="row2">
          <div class="box">
            <small>Pairing (Weak Leg)</small>
            <b>How Pairs are counted</b>
            <ul>
              <li>Pairs are based on the <b>minimum BV</b> between left and right legs.</li>
              <li>Weak leg BV decides how many pairs you can make.</li>
              <li>Carry forward depends on your plan settings.</li>
            </ul>
          </div>

          <div class="box">
            <small>Rank Qualification</small>
            <b>Common requirements</b>
            <ul>
              <li><b>Active status</b> must be maintained</li>
              <li><b>Active directs</b> required for next rank</li>
              <li><b>KYC/Bank</b> may be required for payouts</li>
              <li><b>BV targets</b> often apply to weak leg / both legs</li>
            </ul>
          </div>
        </div>

        <div class="row2">
          <div class="box">
            <small>Fast Growth</small>
            <b>Simple strategy</b>
            <ul>
              <li>Always push BV into the <b>weak leg</b> first</li>
              <li>Keep directs <b>active</b> so they count</li>
              <li>Track your cycle target (week/month)</li>
            </ul>
          </div>

          <div class="box">
            <small>Carry Forward</small>
            <b>Admin controlled</b>
            <ul>
              <li>Unlimited carry (some plans)</li>
              <li>Carry reset every cycle (some plans)</li>
              <li>Carry cap (max BV) (some plans)</li>
            </ul>
          </div>
        </div>

        <button class="btn-full primary" style="margin-top:12px;" onclick="closeRules()">
          Understood <i class="ph ph-check"></i>
        </button>
      </div>
    </div>
  </div>

  <script src="<?php echo base_url(); ?>/assets/user_v2/js/script.js?ver=2.9"></script>
  <script>
    // ===== Filters =====
    const q = document.getElementById('q');
    const type = document.getElementById('type');
    const statusSel = document.getElementById('status');
    const rows = () => Array.from(document.querySelectorAll('#rwTable tbody .tr'));

    function applyFilters() {
      const s = (q.value || "").trim().toLowerCase();
      const t = (type.value || "").trim();
      const st = (statusSel.value || "").trim();

      rows().forEach(r => {
        const hay = (r.dataset.title + " " + r.dataset.ref + " " + r.dataset.date).toLowerCase();
        const okQ = !s || hay.includes(s);
        const okT = !t || r.dataset.type === t;
        const okS = !st || r.dataset.status === st;
        r.style.display = (okQ && okT && okS) ? "" : "none";
      });
    }
    if (q) { q.addEventListener('input', applyFilters); }
    if (type) { type.addEventListener('change', applyFilters); }
    if (statusSel) { statusSel.addEventListener('change', applyFilters); }

    function resetFilters() {
      q.value = ""; type.value = ""; statusSel.value = "";
      applyFilters();
    }

    function viewReward(ref) {
      if (!ref) { toastMini("No reference"); return; }
      location.href = "<?= base_url('user/reward/'); ?>" + encodeURIComponent(ref);
    }

    // ===== Modal =====
    function openRules() { document.getElementById('rulesModal').style.display = 'flex'; }
    function closeRules() { document.getElementById('rulesModal').style.display = 'none'; }

    function toastMini(msg) {
      const t = document.createElement('div');
      t.textContent = msg;
      t.style.cssText =
        "position:fixed;bottom:22px;left:50%;transform:translateX(-50%);background:#111;color:#fff;padding:10px 14px;border-radius:14px;font-weight:1000;font-size:12px;z-index:99999;opacity:0;transition:.2s;";
      document.body.appendChild(t);
      requestAnimationFrame(() => t.style.opacity = "1");
      setTimeout(() => { t.style.opacity = "0"; setTimeout(() => t.remove(), 250); }, 1400);
    }
  </script>
</body>

</html>