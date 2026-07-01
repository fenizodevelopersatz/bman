<?php
// ===================== RANK COMPETITION (GLOBAL LEADERBOARD) - ADVANCED UI =====================
// Replace dummy arrays with your DB results.

// Period: weekly/monthly/all
$period = $this->input->get('period') ?? 'weekly';

// Current user (example)
$current_user = (object)[
  'uid' => 'NEXMAN123',
  'name' => 'vaaluashok',
  'avatar' => 'https://i.pravatar.cc/100?u=you',
  'rank' => 14,
  'points' => 45089,
  'rank_name' => 'SILVER',   // MLM rank
  'next_rank' => 'GOLD',
  'progress' => 48,
  'pairs_needed' => 12,
  'directs_needed' => 3
];

// Top 3 (example)
$top3 = [
  (object)['rank'=>2,'name'=>'[LDL] BanDiT','avatar'=>'https://i.pravatar.cc/100?u=bandit','points'=>664926],
  (object)['rank'=>1,'name'=>'[LDL] Юрий','avatar'=>'https://i.pravatar.cc/100?u=yurii','points'=>869552],
  (object)['rank'=>3,'name'=>'[LDL] Reina Dee','avatar'=>'https://i.pravatar.cc/100?u=reina','points'=>546051],
];

// Leaderboard list (example) – include current user somewhere (or handle separately)
$leaders = [
  (object)['rank'=>4,'name'=>'[LDL] QueenGoddess','avatar'=>'https://i.pravatar.cc/100?u=qg','points'=>343929],
  (object)['rank'=>5,'name'=>'[LDL] Abhighost','avatar'=>'https://i.pravatar.cc/100?u=abhi','points'=>221682],
  (object)['rank'=>6,'name'=>'[LDL] Phantom Wolf','avatar'=>'https://i.pravatar.cc/100?u=wolf','points'=>220693],
  (object)['rank'=>7,'name'=>'[LDL] WardenBBMPalubot','avatar'=>'https://i.pravatar.cc/100?u=warden','points'=>110836],
  (object)['rank'=>8,'name'=>'[LDL] сахалинец','avatar'=>'https://i.pravatar.cc/100?u=saha','points'=>97672],
  // ...
];

// Helper for number format
function fmt_pts($n){ return number_format((float)$n, 0); }

// Find neighbor ranks around current user (for "Nearby rivals")
$nearby = [
  (object)['rank'=>$current_user->rank-2,'name'=>'[LDL] Rival A','avatar'=>'https://i.pravatar.cc/100?u=ra','points'=>70000],
  (object)['rank'=>$current_user->rank-1,'name'=>'[LDL] Rival B','avatar'=>'https://i.pravatar.cc/100?u=rb','points'=>52000],
  (object)['rank'=>$current_user->rank,'name'=>$current_user->name.' (You)','avatar'=>$current_user->avatar,'points'=>$current_user->points,'me'=>true],
  (object)['rank'=>$current_user->rank+1,'name'=>'[LDL] Rival C','avatar'=>'https://i.pravatar.cc/100?u=rc','points'=>44000],
  (object)['rank'=>$current_user->rank+2,'name'=>'[LDL] Rival D','avatar'=>'https://i.pravatar.cc/100?u=rd','points'=>42000],
];

// Points to next rank in leaderboard (example calc)
$points_to_next = max(0, ($nearby[1]->points ?? ($current_user->points+1000)) - $current_user->points);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php $this->load->view('user/layout/v2/user_style'); ?>
  <script src="https://unpkg.com/@phosphor-icons/web"></script>

  <style>
    /* ===================== PAGE LAYOUT ===================== */
    .page-wrap{padding:10px 0 24px;}
    .page-titlebar{display:flex;justify-content:space-between;align-items:flex-end;gap:14px;margin:8px 0 14px;}
    .page-titlebar h2{margin:0;font-size:18px;font-weight:1200;color:var(--text-main);display:flex;gap:10px;align-items:center;}
    .page-titlebar h2 i{color:var(--primary);font-size:20px;}
    .page-titlebar .sub{margin-top:4px;font-size:12px;color:var(--text-muted);font-weight:900;}
    .controls{display:flex;gap:10px;align-items:center;flex-wrap:wrap;}
    .select{
      border:1px solid #f1f1f6;background:#fff;border-radius:14px;padding:10px 12px;
      font-size:12px;font-weight:1100;outline:none;cursor:pointer;
    }
    .btn-soft{
      border:1px solid #f1f1f6;background:#fff;border-radius:14px;padding:10px 12px;
      font-size:12px;font-weight:1100;cursor:pointer;display:inline-flex;align-items:center;gap:8px;
    }
    .btn-main{
      border:none;background:var(--primary);color:#fff;border-radius:14px;padding:10px 12px;
      font-size:12px;font-weight:1100;cursor:pointer;display:inline-flex;align-items:center;gap:8px;
    }

    /* ===================== HERO + STATS ===================== */
    .hero{
      background:linear-gradient(105deg,#6E56CF 0%, #4c3ba0 100%);
      border-radius:26px;
      padding:18px;
      color:#fff;
      display:grid;
      grid-template-columns:1.4fr 1fr;
      gap:14px;
      overflow:hidden;
      box-shadow:0 18px 55px rgba(110,86,207,.25);
      position:relative;
    }
    .hero:before{
      content:"";
      position:absolute; inset:-120px -140px auto auto;
      width:260px;height:260px;border-radius:50%;
      background:rgba(255,255,255,.10);
      filter:blur(0);
    }
    .hero:after{
      content:"";
      position:absolute; inset:auto -160px -160px auto;
      width:320px;height:320px;border-radius:50%;
      background:rgba(255,255,255,.07);
    }

    .hero-left{position:relative;z-index:2;}
    .hero-tag{
      display:inline-flex;align-items:center;gap:8px;
      padding:8px 12px;border-radius:999px;
      background:rgba(255,255,255,.14);
      border:1px solid rgba(255,255,255,.16);
      font-size:11px;font-weight:1100;
    }
    .hero-title{margin:10px 0 6px;font-size:18px;font-weight:1200;line-height:1.25;}
    .hero-desc{margin:0;font-size:12px;opacity:.92;font-weight:900;line-height:1.5;max-width:560px;}
    .hero-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;}
    .hero .btn-dark{
      border:none;background:#111;color:#fff;border-radius:14px;padding:10px 12px;
      font-weight:1100;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;
    }
    .hero .btn-ghost{
      border:1px solid rgba(255,255,255,.25);
      background:rgba(255,255,255,.08);
      color:#fff;border-radius:14px;padding:10px 12px;
      font-weight:1100;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;
    }

    .hero-right{position:relative;z-index:2;display:grid;gap:10px;align-content:start;}
    .statgrid{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
    .stat{
      background:rgba(255,255,255,.10);
      border:1px solid rgba(255,255,255,.14);
      border-radius:18px;padding:12px;
    }
    .stat small{display:block;font-size:10px;opacity:.85;font-weight:1100;}
    .stat strong{display:block;font-size:16px;font-weight:1200;margin-top:4px;}
    .stat span{display:block;font-size:11px;opacity:.9;font-weight:900;margin-top:4px;}

    .progressbox{
      background:rgba(255,255,255,.10);
      border:1px solid rgba(255,255,255,.14);
      border-radius:18px;padding:12px;
    }
    .p-top{display:flex;justify-content:space-between;gap:10px;align-items:center;}
    .p-top b{font-size:12px;font-weight:1200;}
    .p-top small{font-size:11px;opacity:.9;font-weight:1100;}
    .pbar{height:10px;border-radius:999px;background:rgba(255,255,255,.22);overflow:hidden;margin-top:10px;}
    .pbar > div{height:100%;border-radius:999px;background:#fff;width:0%;}
    .p-hint{margin-top:8px;font-size:11px;opacity:.92;font-weight:900;line-height:1.4;}

    /* ===================== PODIUM ===================== */
    .podium-wrap{
      margin-top:14px;
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:14px;
    }
    .podium{
      background:#fff;border:1px solid #f5f5f7;border-radius:26px;padding:14px;
      box-shadow:0 14px 35px rgba(0,0,0,.04);
    }
    .podium-h{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:12px;}
    .podium-h h3{margin:0;font-size:14px;font-weight:1200;}
    .chip{
      display:inline-flex;align-items:center;gap:8px;
      padding:7px 10px;border-radius:999px;
      border:1px solid #eeecff;background:#efedfb;color:var(--primary);
      font-size:10px;font-weight:1100;
    }
    .podium-cards{display:flex;justify-content:center;align-items:flex-end;gap:12px;margin-top:6px;}
    .pod{
      width:170px;
      border:1px solid #f5f5f7;background:#fff;border-radius:18px;
      padding:12px 12px 10px;text-align:center;
      box-shadow:0 14px 30px rgba(0,0,0,.04);
      position:relative;
      overflow:hidden;
    }
    .pod:before{content:"";position:absolute;inset:-40px auto auto -40px;width:110px;height:110px;border-radius:50%;opacity:.22;}
    .pod.silver:before{background:#C0C0C0;}
    .pod.gold:before{background:#FFD700;}
    .pod.bronze:before{background:#CD7F32;}
    .pod.gold{transform:translateY(-10px);border-top:4px solid #FFD700;}
    .pod.silver{border-top:4px solid #C0C0C0;}
    .pod.bronze{border-top:4px solid #CD7F32;}

    .medal{
      width:42px;height:42px;border-radius:16px;
      display:grid;place-items:center;margin:0 auto 8px;
      background:#f7f7fb;border:1px solid #f1f1f6;
      font-size:18px;
    }
    .medal.gold{background:#fff7cc;border-color:#ffe58a;}
    .medal.silver{background:#f3f4f6;border-color:#e5e7eb;}
    .medal.bronze{background:#fff1e8;border-color:#ffd8bf;}
    .ava{
      width:58px;height:58px;border-radius:18px;object-fit:cover;
      border:1px solid #f1f1f6;background:#eee;margin:0 auto 8px;
    }
    .pod .name{font-weight:1200;font-size:12px;color:#111;margin:0;}
    .pod .pts{margin-top:4px;font-size:12px;font-weight:1200;color:var(--primary);}
    .pod .rk{margin-top:6px;font-size:10px;font-weight:1100;color:var(--text-muted);}

    /* ===================== YOU CARD ===================== */
    .you{
      background:#fff;border:1px solid #f5f5f7;border-radius:26px;padding:14px;
      box-shadow:0 14px 35px rgba(0,0,0,.04);
      display:grid;gap:12px;
    }
    .you-top{display:flex;justify-content:space-between;align-items:center;gap:10px;}
    .you-id{display:flex;align-items:center;gap:10px;}
    .you-id img{width:44px;height:44px;border-radius:16px;object-fit:cover;border:1px solid #f1f1f6;background:#eee;}
    .you-id b{display:block;font-size:13px;font-weight:1200;}
    .you-id small{display:block;font-size:11px;color:var(--text-muted);font-weight:1000;margin-top:2px;}

    .ring{
      --p:0;
      width:66px;height:66px;border-radius:50%;
      background:conic-gradient(var(--primary) calc(var(--p)*1%), #f1f1f6 0);
      display:grid;place-items:center; position:relative;
    }
    .ring:after{content:"";position:absolute;width:52px;height:52px;border-radius:50%;background:#fff;}
    .ring .in{position:relative;z-index:2;text-align:center;}
    .ring .in b{display:block;font-size:12px;font-weight:1200;}
    .ring .in small{display:block;font-size:10px;color:var(--text-muted);font-weight:1100;margin-top:-2px;}

    .you-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
    .ybox{
      border:1px dashed #e7e7f3;background:#fbfbff;border-radius:18px;padding:12px;
    }
    .ybox small{display:block;font-size:10px;color:var(--text-muted);font-weight:1100;}
    .ybox strong{display:block;font-size:14px;font-weight:1200;margin-top:4px;}
    .ybox span{display:block;font-size:11px;color:var(--text-muted);font-weight:900;margin-top:3px;}
    .btn-full{width:100%;border:none;border-radius:16px;padding:12px 14px;cursor:pointer;font-weight:1200;background:#efedfb;color:var(--primary);display:flex;align-items:center;justify-content:center;gap:8px;}

    /* ===================== TABLE + INSIGHTS ===================== */
    .content-grid{margin-top:14px;display:grid;grid-template-columns:1.35fr .65fr;gap:14px;}
    .card{
      background:#fff;border:1px solid #f5f5f7;border-radius:26px;padding:14px;
      box-shadow:0 14px 35px rgba(0,0,0,.04);
    }
    .card-h{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px;}
    .card-h h3{margin:0;font-size:14px;font-weight:1200;}
    .search{
      flex:1;max-width:360px;
      display:flex;align-items:center;gap:10px;
      border:1px solid #f1f1f6;background:#f7f7fb;border-radius:14px;padding:10px 12px;
    }
    .search i{color:var(--text-muted);font-size:18px;}
    .search input{border:none;outline:none;background:transparent;font-size:12px;font-weight:1000;width:100%;}
    .tabs{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;}
    .tab{
      border:1px solid #f1f1f6;background:#fff;border-radius:999px;
      padding:8px 10px;font-size:11px;font-weight:1100;color:#111;
      cursor:pointer;display:inline-flex;gap:8px;align-items:center;
    }
    .tab.active{background:#efedfb;border-color:#eeecff;color:var(--primary);}

    .table{width:100%;border-collapse:separate;border-spacing:0 10px;}
    .table th{font-size:11px;color:var(--text-muted);text-align:left;font-weight:1100;padding:0 10px;}
    .tr{background:#fff;border:1px solid #f5f5f7;border-radius:18px;box-shadow:0 12px 25px rgba(0,0,0,0.03);}
    .tr td{padding:12px 10px;font-size:12px;font-weight:1000;color:#111;vertical-align:middle;}
    .who{display:flex;gap:10px;align-items:center;}
    .who img{width:40px;height:40px;border-radius:14px;object-fit:cover;border:1px solid #f1f1f6;background:#eee;}
    .who b{display:block;font-weight:1200;}
    .who small{display:block;font-size:11px;color:var(--text-muted);font-weight:900;margin-top:2px;}
    .pill{
      display:inline-flex;align-items:center;gap:8px;
      padding:6px 10px;border-radius:999px;font-size:10px;font-weight:1200;
      border:1px solid #f1f1f6;background:#fff;color:#111;
    }
    .pill.me{border-color:#dcd7ff;background:#efedfb;color:var(--primary);}
    .pts{font-weight:1200;text-align:right;}
    .up{color:#0f9d58;}
    .down{color:#b91c1c;}

    .empty{border:1px dashed #e7e7f3;background:#fbfbff;border-radius:18px;padding:18px;text-align:center;color:var(--text-muted);font-weight:900;font-size:12px;}

    /* Insights */
    .ins{display:grid;gap:10px;}
    .ibox{
      border:1px solid #f1f1f6;background:#f7f7fb;border-radius:18px;padding:12px;
      display:flex;justify-content:space-between;align-items:center;gap:10px;
    }
    .ibox b{font-size:12px;font-weight:1200;}
    .ibox span{font-size:11px;color:var(--text-muted);font-weight:1100;}
    .ibox .val{font-weight:1200;color:var(--primary);}

    .mini-list{display:grid;gap:8px;margin-top:6px;}
    .rival{
      border:1px solid #f1f1f6;background:#fff;border-radius:18px;padding:10px;
      display:flex;align-items:center;justify-content:space-between;gap:10px;
    }
    .rival .l{display:flex;gap:10px;align-items:center;}
    .rival img{width:34px;height:34px;border-radius:14px;object-fit:cover;border:1px solid #f1f1f6;background:#eee;}
    .rival b{display:block;font-size:12px;font-weight:1200;}
    .rival small{display:block;font-size:10px;color:var(--text-muted);font-weight:1100;margin-top:2px;}
    .rival .r{font-size:12px;font-weight:1200;color:#111;}
    .rival.me{background:#efedfb;border-color:#dcd7ff;}
    .rival.me .r{color:var(--primary);}

    @media(max-width:1200px){
      .hero{grid-template-columns:1fr;}
      .podium-wrap{grid-template-columns:1fr;}
      .content-grid{grid-template-columns:1fr;}
      .podium-cards{flex-wrap:wrap;}
      .pod{width:190px;}
    }
  </style>
</head>

<body>
<div class="app-container">
  <?php $this->load->view('user/layout/v2/user_sidebar'); ?>

  <main class="main-content">
    <?php $this->load->view('user/layout/v2/user_header'); ?>

    <div class="page-wrap">
      <!-- Title -->
      <div class="page-titlebar">
        <div>
          <h2><i class="ph ph-trophy"></i> Global Rank Competition</h2>
          <div class="sub">See your global position, top performers, and how many points you need to climb.</div>
        </div>
        <div class="controls">
          <select class="select" id="periodSel">
            <option value="weekly"  <?= $period==='weekly'?'selected':''; ?>>Weekly</option>
            <option value="monthly" <?= $period==='monthly'?'selected':''; ?>>Monthly</option>
            <option value="all"     <?= $period==='all'?'selected':''; ?>>All Time</option>
          </select>
          <button class="btn-soft" type="button" onclick="scrollToMe()"><i class="ph ph-target"></i> Go to My Rank</button>
          <button class="btn-main" type="button" onclick="window.print()"><i class="ph ph-printer"></i> Print</button>
        </div>
      </div>

      <!-- HERO -->
      <div class="hero">
        <div class="hero-left">
          <div class="hero-tag"><i class="ph ph-sparkle"></i> Leaderboard Overview • <?= strtoupper($period); ?></div>
          <div class="hero-title">You are ranked <b>#<?= (int)$current_user->rank; ?></b> globally — keep pushing to reach Top 10.</div>
          <p class="hero-desc">
            Your current points are <b><?= fmt_pts($current_user->points); ?></b>. You need approximately
            <b><?= fmt_pts($points_to_next); ?></b> points to pass the member above you.
          </p>

          <div class="hero-actions">
            <button class="btn-dark" type="button" onclick="location.href='<?= base_url('user/binary-tree'); ?>'">
              Improve Points <i class="ph ph-tree-structure"></i>
            </button>
            <button class="btn-ghost" type="button" onclick="location.href='<?= base_url('user/rank-rewards'); ?>'">
              Rank Benefits <i class="ph ph-medal"></i>
            </button>
            <button class="btn-ghost" type="button" onclick="location.href='<?= base_url('user/referrals'); ?>'">
              Share Referral <i class="ph ph-share-network"></i>
            </button>
          </div>
        </div>

        <div class="hero-right">
          <div class="statgrid">
            <div class="stat">
              <small>My Global Rank</small>
              <strong>#<?= (int)$current_user->rank; ?></strong>
              <span>Out of all members</span>
            </div>
            <div class="stat">
              <small>My Points</small>
              <strong><?= fmt_pts($current_user->points); ?></strong>
              <span><?= strtoupper($period); ?> points</span>
            </div>
            <div class="stat">
              <small>Current MLM Rank</small>
              <strong><?= htmlspecialchars($current_user->rank_name); ?></strong>
              <span>Next: <?= htmlspecialchars($current_user->next_rank); ?></span>
            </div>
            <div class="stat">
              <small>Rank Progress</small>
              <strong><?= (int)$current_user->progress; ?>%</strong>
              <span>To next rank</span>
            </div>
          </div>

          <div class="progressbox">
            <div class="p-top">
              <b>Climb to #<?= max(1, (int)$current_user->rank-1); ?></b>
              <small>Need <?= fmt_pts($points_to_next); ?> pts</small>
            </div>
            <div class="pbar"><div id="needBar"></div></div>
            <div class="p-hint">
              Tip: Balance left/right BV to increase pairing frequency & points. Focus on active team volume.
            </div>
          </div>
        </div>
      </div>

      <!-- Podium + You -->
      <div class="podium-wrap">
        <div class="podium">
          <div class="podium-h">
            <h3>Top Performers</h3>
            <span class="chip"><i class="ph ph-crown"></i> Top 3</span>
          </div>

          <div class="podium-cards">
            <!-- Silver -->
            <div class="pod silver">
              <div class="medal silver"><i class="ph ph-medal"></i></div>
              <img class="ava" src="<?= htmlspecialchars($top3[0]->avatar); ?>" alt="">
              <p class="name"><?= htmlspecialchars($top3[0]->name); ?></p>
              <div class="pts"><?= fmt_pts($top3[0]->points); ?> pts</div>
              <div class="rk">Rank #<?= (int)$top3[0]->rank; ?></div>
            </div>

            <!-- Gold -->
            <div class="pod gold">
              <div class="medal gold"><i class="ph ph-crown"></i></div>
              <img class="ava" src="<?= htmlspecialchars($top3[1]->avatar); ?>" alt="">
              <p class="name"><?= htmlspecialchars($top3[1]->name); ?></p>
              <div class="pts"><?= fmt_pts($top3[1]->points); ?> pts</div>
              <div class="rk">Rank #<?= (int)$top3[1]->rank; ?></div>
            </div>

            <!-- Bronze -->
            <div class="pod bronze">
              <div class="medal bronze"><i class="ph ph-medal"></i></div>
              <img class="ava" src="<?= htmlspecialchars($top3[2]->avatar); ?>" alt="">
              <p class="name"><?= htmlspecialchars($top3[2]->name); ?></p>
              <div class="pts"><?= fmt_pts($top3[2]->points); ?> pts</div>
              <div class="rk">Rank #<?= (int)$top3[2]->rank; ?></div>
            </div>
          </div>
        </div>

        <div class="you">
          <div class="podium-h">
            <h3>My Position</h3>
            <span class="chip"><i class="ph ph-user"></i> You</span>
          </div>

          <div class="you-top">
            <div class="you-id">
              <img src="<?= htmlspecialchars($current_user->avatar); ?>" alt="">
              <div>
                <b><?= htmlspecialchars($current_user->name); ?></b>
                <small>UID: <?= htmlspecialchars($current_user->uid); ?></small>
              </div>
            </div>

            <div class="ring" style="--p:<?= (int)$current_user->progress; ?>;">
              <div class="in">
                <b><?= (int)$current_user->progress; ?>%</b>
                <small>Rank</small>
              </div>
            </div>
          </div>

          <div class="you-grid">
            <div class="ybox">
              <small>Global Rank</small>
              <strong>#<?= (int)$current_user->rank; ?></strong>
              <span><?= strtoupper($period); ?> leaderboard</span>
            </div>
            <div class="ybox">
              <small>Points</small>
              <strong><?= fmt_pts($current_user->points); ?></strong>
              <span>Keep earning daily</span>
            </div>
            <div class="ybox">
              <small>Pairs Needed</small>
              <strong><?= (int)$current_user->pairs_needed; ?></strong>
              <span>To hit next milestone</span>
            </div>
            <div class="ybox">
              <small>Directs Needed</small>
              <strong><?= (int)$current_user->directs_needed; ?></strong>
              <span>For next rank</span>
            </div>
          </div>

          <button class="btn-full" type="button" onclick="location.href='<?= base_url('user/rank-rewards'); ?>'">
            View Rank Details <i class="ph ph-arrow-right"></i>
          </button>
        </div>
      </div>

      <!-- Table + Insights -->
      <div class="content-grid">
        <!-- Leaderboard Table -->
        <div class="card">
          <div class="card-h">
            <h3>Leaderboard</h3>
            <div class="search">
              <i class="ph ph-magnifying-glass"></i>
              <input id="searchBox" type="text" placeholder="Search member name or rank..." />
            </div>
          </div>

          <div class="tabs">
            <button class="tab active" data-mode="all"><i class="ph ph-list"></i> All</button>
            <button class="tab" data-mode="top"><i class="ph ph-crown"></i> Top 50</button>
            <button class="tab" data-mode="near"><i class="ph ph-target"></i> Near Me</button>
          </div>

          <div style="margin-top:10px; overflow:auto;">
            <table class="table" id="lbTable">
              <thead>
                <tr>
                  <th style="width:12%;">Rank</th>
                  <th>Member</th>
                  <th style="width:16%;">Change</th>
                  <th style="width:18%; text-align:right;">Points</th>
                </tr>
              </thead>
              <tbody id="lbBody">
                <?php foreach($leaders as $u): ?>
                  <?php $isMe = (strtolower($u->name)===strtolower($current_user->name) || ((int)$u->rank === (int)$current_user->rank)); ?>
                  <tr class="tr <?= $isMe ? 'meRow' : '' ?>"
                      data-rank="<?= (int)$u->rank; ?>"
                      data-name="<?= htmlspecialchars(strtolower($u->name)); ?>"
                      data-mode="all">
                    <td><span class="pill <?= $isMe?'me':''; ?>">#<?= (int)$u->rank; ?></span></td>
                    <td>
                      <div class="who">
                        <img src="<?= htmlspecialchars($isMe ? $current_user->avatar : $u->avatar); ?>" alt="">
                        <div>
                          <b><?= htmlspecialchars($isMe ? ($current_user->name.' (You)') : $u->name); ?></b>
                          <small>Global leaderboard</small>
                        </div>
                      </div>
                    </td>
                    <td>
                      <!-- Replace with real trend data -->
                      <span class="pill"><i class="ph ph-trend-up"></i> <span class="up">+2</span></span>
                    </td>
                    <td class="pts"><?= fmt_pts($isMe ? $current_user->points : $u->points); ?></td>
                  </tr>
                <?php endforeach; ?>

                <!-- Ensure current user visible even if not in $leaders -->
                <?php
                  $foundMe = false;
                  foreach($leaders as $u){ if((int)$u->rank === (int)$current_user->rank) $foundMe = true; }
                ?>
                <?php if(!$foundMe): ?>
                  <tr class="tr meRow" id="meRow" data-rank="<?= (int)$current_user->rank; ?>" data-name="<?= htmlspecialchars(strtolower($current_user->name)); ?>">
                    <td><span class="pill me">#<?= (int)$current_user->rank; ?></span></td>
                    <td>
                      <div class="who">
                        <img src="<?= htmlspecialchars($current_user->avatar); ?>" alt="">
                        <div>
                          <b><?= htmlspecialchars($current_user->name); ?> (You)</b>
                          <small>Auto pinned</small>
                        </div>
                      </div>
                    </td>
                    <td><span class="pill"><i class="ph ph-trend-neutral"></i> 0</span></td>
                    <td class="pts"><?= fmt_pts($current_user->points); ?></td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>

            <div class="empty" id="emptyBox" style="display:none;">No members found for your search.</div>
          </div>
        </div>

        <!-- Insights / Nearby Rivals -->
        <div class="card">
          <div class="card-h">
            <h3>Insights</h3>
            <span class="chip"><i class="ph ph-lightning"></i> Action</span>
          </div>

          <div class="ins">
            <div class="ibox">
              <div>
                <b>My Rank</b>
                <div><span><?= strtoupper($period); ?> leaderboard</span></div>
              </div>
              <div class="val">#<?= (int)$current_user->rank; ?></div>
            </div>

            <div class="ibox">
              <div>
                <b>Points to Next</b>
                <div><span>Beat the member above</span></div>
              </div>
              <div class="val"><?= fmt_pts($points_to_next); ?></div>
            </div>

            <div class="ibox">
              <div>
                <b>Current MLM Rank</b>
                <div><span>Next: <?= htmlspecialchars($current_user->next_rank); ?></span></div>
              </div>
              <div class="val"><?= htmlspecialchars($current_user->rank_name); ?></div>
            </div>

            <div class="ibox">
              <div>
                <b>Progress</b>
                <div><span>To next MLM rank</span></div>
              </div>
              <div class="val"><?= (int)$current_user->progress; ?>%</div>
            </div>
          </div>

          <div class="card-h" style="margin-top:14px;">
            <h3 style="font-size:13px;">Nearby Rivals</h3>
            <span class="chip"><i class="ph ph-target"></i> Near Me</span>
          </div>

          <div class="mini-list" id="nearList">
            <?php foreach($nearby as $n): ?>
              <div class="rival <?= !empty($n->me)?'me':''; ?>">
                <div class="l">
                  <img src="<?= htmlspecialchars($n->avatar); ?>" alt="">
                  <div>
                    <b>#<?= (int)$n->rank; ?> • <?= htmlspecialchars($n->name); ?></b>
                    <small><?= strtoupper($period); ?> points</small>
                  </div>
                </div>
                <div class="r"><?= fmt_pts($n->points); ?></div>
              </div>
            <?php endforeach; ?>
          </div>

          <button class="btn-full" style="margin-top:12px;" type="button" onclick="scrollToMe()">
            Highlight My Rank <i class="ph ph-crosshair"></i>
          </button>
        </div>
      </div>

    </div>
  </main>

  <aside class="right-panel">
    <?php $this->load->view('user/layout/v2/user_inner_right_panle'); ?>
  </aside>
</div>

<script src="<?php echo base_url();?>/assets/user_v2/js/script.js?ver=2.9"></script>
<script>
  // Period dropdown (reload with query)
  document.getElementById('periodSel').addEventListener('change', function(){
    const v = this.value;
    const url = new URL(window.location.href);
    url.searchParams.set('period', v);
    window.location.href = url.toString();
  });

  // Progress bar in hero (visual only)
  (function(){
    const need = <?= json_encode((int)$points_to_next); ?>;
    // fake scale: smaller need => bigger bar (visual only). Replace with your own formula if you want.
    const pct = Math.max(8, Math.min(100, 100 - Math.round(need / 10000)));
    document.getElementById('needBar').style.width = pct + '%';
  })();

  // Search filter
  const searchBox = document.getElementById('searchBox');
  const rows = () => Array.from(document.querySelectorAll('#lbBody .tr'));
  const emptyBox = document.getElementById('emptyBox');

  function applySearch(){
    const q = (searchBox.value || '').trim().toLowerCase();
    let visible = 0;
    rows().forEach(r=>{
      const name = r.dataset.name || '';
      const rank = (r.dataset.rank || '');
      const ok = !q || name.includes(q) || rank.includes(q);
      r.style.display = ok ? '' : 'none';
      if(ok) visible++;
    });
    emptyBox.style.display = visible ? 'none' : 'block';
  }
  searchBox.addEventListener('input', applySearch);

  // Tabs: all/top/near (near shows only "my rank +-2" if exists in table)
  const tabs = Array.from(document.querySelectorAll('.tab'));
  tabs.forEach(t=>{
    t.addEventListener('click', ()=>{
      tabs.forEach(x=>x.classList.remove('active'));
      t.classList.add('active');
      const mode = t.dataset.mode;

      const myRank = <?= json_encode((int)$current_user->rank); ?>;

      rows().forEach(r=>{
        const rnk = parseInt(r.dataset.rank || '0', 10);
        let show = true;

        if(mode === 'top'){
          show = rnk > 0 && rnk <= 50;
        } else if(mode === 'near'){
          show = rnk >= (myRank - 2) && rnk <= (myRank + 2);
        } else {
          show = true;
        }

        // also apply search on top of mode
        if(show){
          const q = (searchBox.value || '').trim().toLowerCase();
          const name = (r.dataset.name || '');
          show = !q || name.includes(q) || String(rnk).includes(q);
        }

        r.style.display = show ? '' : 'none';
      });

      // empty state
      const anyVisible = rows().some(r => r.style.display !== 'none');
      emptyBox.style.display = anyVisible ? 'none' : 'block';
    });
  });

  // Scroll to my row
  function scrollToMe(){
    // find row with my rank
    const myRank = <?= json_encode((int)$current_user->rank); ?>;
    const row = rows().find(r => parseInt(r.dataset.rank || '0',10) === myRank) || document.getElementById('meRow');
    if(row){
      row.scrollIntoView({behavior:'smooth', block:'center'});
      row.style.transition = '0.2s';
      row.style.boxShadow = '0 0 0 4px rgba(110,86,207,.15), 0 14px 35px rgba(0,0,0,.04)';
      setTimeout(()=>{ row.style.boxShadow='0 12px 25px rgba(0,0,0,0.03)'; }, 900);
    }
  }
  window.scrollToMe = scrollToMe;
</script>
</body>
</html>
