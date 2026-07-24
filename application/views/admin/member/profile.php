<?php $this->load->view('admin/Layout/common_style'); ?>
<link href="<?= base_url('assets/admin/plugins/global/plugins.bundle.css'); ?>" rel="stylesheet" type="text/css" />
<?php
$s = $snapshot;
$m = $s['member'];
$w = $s['wallets'];
$st = $s['stats'];
$rank = $s['rank'];
$e = static function ($value) {
    $value = trim((string) $value);
    return $value === '' ? '—' : html_escape($value);
};
$money = static function ($value, $decimals = 2) {
    return number_format((float) $value, $decimals);
};
$rankTarget = $rank['next_required_volume'] > 0 ? $rank['next_required_volume'] : $rank['required_volume'];
$rankPercent = $rankTarget > 0 ? min(100, ($rank['group_volume'] / $rankTarget) * 100) : 0;
?>
<style>
  :root{--mp-primary:#5b45e6;--mp-blue:#2684ff;--mp-green:#18b76a;--mp-red:#e5484d;--mp-text:#172033;--mp-muted:#7c879f;--mp-line:#e6eaf2;--mp-soft:#f7f8fb}
  .mp-page{color:var(--mp-text);font-size:13px}.mp-page *{box-sizing:border-box}
  .mp-toolbar{position:sticky;top:70px;z-index:8;background:#fff;border:1px solid var(--mp-line);border-radius:12px;padding:8px;display:flex;gap:6px;flex-wrap:wrap;box-shadow:0 5px 18px rgba(30,40,70,.05)}
  .mp-btn{border:1px solid var(--mp-line);background:#fff;color:#344054;border-radius:8px;padding:9px 13px;font-weight:700;display:inline-flex;align-items:center;gap:7px;cursor:pointer;text-decoration:none}
  .mp-btn:hover{border-color:var(--mp-primary);color:var(--mp-primary)}.mp-btn.primary{background:var(--mp-primary);border-color:var(--mp-primary);color:#fff}
  .mp-btn:disabled{opacity:.45;cursor:not-allowed}.mp-card{background:#fff;border:1px solid var(--mp-line);border-radius:12px;box-shadow:0 2px 10px rgba(30,40,70,.025)}
  .mp-member{padding:22px;display:grid;grid-template-columns:auto minmax(220px,1.1fr) repeat(3,minmax(145px,1fr));gap:22px;align-items:center}
  .mp-avatar{width:88px;height:88px;border-radius:50%;object-fit:cover;border:4px solid #f0edff}.mp-name{font-size:25px;font-weight:800;line-height:1.15}
  .mp-sub{color:var(--mp-muted);margin-top:6px}.mp-badges{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px}.mp-badge{padding:5px 8px;border-radius:6px;font-size:10px;font-weight:800;text-transform:uppercase}
  .mp-badge.ok{background:#e9fbf1;color:#07894a}.mp-badge.info{background:#eaf3ff;color:#1668d8}.mp-badge.bad{background:#fff0f0;color:#c7343a}
  .mp-detail{border-left:1px solid var(--mp-line);padding-left:20px;min-height:66px}.mp-label{font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:var(--mp-muted);font-weight:800}.mp-value{font-weight:750;margin-top:5px;word-break:break-word}
  .mp-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}.mp-stat{padding:16px;min-height:104px}.mp-stat-icon{width:34px;height:34px;border-radius:10px;background:#f0edff;color:var(--mp-primary);display:grid;place-items:center;font-size:18px}
  .mp-stat-label{color:var(--mp-muted);font-size:11px;font-weight:700;margin-top:10px}.mp-stat-value{font-size:19px;font-weight:850;margin-top:3px}.mp-stat-unit{font-size:10px;color:var(--mp-muted);margin-left:3px}
  .mp-section{padding:18px}.mp-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px}.mp-head h3{font-size:15px;margin:0;font-weight:850}.mp-head small{color:var(--mp-muted)}
  .mp-wallets{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px}.mp-wallet{border:1px solid var(--mp-line);border-radius:10px;padding:14px}.mp-wallet b{font-size:17px;display:block;margin-top:6px}
  .mp-rank{grid-column:span 2}.mp-progress{height:7px;background:#eceafc;border-radius:99px;overflow:hidden;margin:10px 0}.mp-progress span{display:block;height:100%;background:var(--mp-primary)}
  .mp-filters{display:grid;grid-template-columns:repeat(4,minmax(130px,1fr)) auto;gap:8px}.mp-input{height:38px;border:1px solid var(--mp-line);background:var(--mp-soft);border-radius:8px;padding:0 11px;font:inherit;width:100%}
  .mp-table-wrap{overflow:auto;margin-top:14px}.mp-table{width:100%;border-collapse:collapse;min-width:900px}.mp-table th{font-size:10px;text-transform:uppercase;color:var(--mp-muted);text-align:left;padding:11px;border-bottom:1px solid var(--mp-line)}
  .mp-table td{padding:12px 11px;border-bottom:1px solid #f0f2f6;vertical-align:middle}.mp-amount.credit{color:var(--mp-green);font-weight:800}.mp-amount.debit{color:var(--mp-red);font-weight:800}
  .mp-empty{text-align:center!important;color:var(--mp-muted);padding:28px!important}.mp-pager{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:13px}.mp-pager-actions{display:flex;gap:6px}
  .mp-tree-bar{display:flex;gap:7px;flex-wrap:wrap;align-items:center}.mp-tree-canvas{background:#17191d;border-radius:11px;min-height:360px;padding:24px;overflow:auto;color:#fff}
  .mp-tree{display:flex;justify-content:center;min-width:max-content}.mp-tree ul{display:flex;justify-content:center;padding-top:34px;position:relative}.mp-tree li{list-style:none;text-align:center;position:relative;padding:34px 10px 0}
  .mp-tree li:before,.mp-tree li:after{content:"";position:absolute;top:0;right:50%;width:50%;height:34px;border-top:1px solid #687083}.mp-tree li:after{right:auto;left:50%;border-left:1px solid #687083}.mp-tree li:only-child:before,.mp-tree li:only-child:after{display:none}.mp-tree li:first-child:before,.mp-tree li:last-child:after{border:0}.mp-tree li:last-child:before{border-right:1px solid #687083}.mp-tree>ul>li{padding-top:0}.mp-tree>ul>li:before,.mp-tree>ul>li:after{display:none}
  .mp-node{width:210px;background:#fff;color:#253047;border:2px solid transparent;border-radius:10px;padding:10px;text-align:left;cursor:pointer;display:flex;gap:9px}.mp-node.root{border-color:#7657ff}.mp-node:hover{border-color:#7657ff}
  .mp-node img{width:40px;height:40px;border-radius:50%;object-fit:cover}.mp-node-name{font-weight:850;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.mp-node-meta{font-size:10px;color:#7d879b;margin-top:2px}.mp-node-status{color:var(--mp-green)}
  .mp-node{position:relative}.mp-node-tip{display:none;position:absolute;left:50%;bottom:calc(100% + 9px);transform:translateX(-50%);z-index:20;width:255px;background:#10131a;color:#fff;border:1px solid #343b49;border-radius:9px;padding:11px;box-shadow:0 14px 35px rgba(0,0,0,.35);font-size:11px;line-height:1.65}.mp-node:hover .mp-node-tip,.mp-node:focus .mp-node-tip{display:block}
  .mp-metric-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:9px}.mp-metric{border:1px solid var(--mp-line);border-radius:10px;padding:13px;min-height:82px}.mp-metric b{display:block;font-size:16px;margin-top:6px}.mp-status{display:inline-flex;align-items:center;gap:6px;font-weight:800}.mp-dot{width:8px;height:8px;border-radius:50%;background:var(--mp-green)}
  .mp-section-placeholder{min-height:130px;display:grid;place-items:center;color:var(--mp-muted)}.mp-section-placeholder:before{content:"";width:22px;height:22px;border:2px solid #e5e1ff;border-top-color:var(--mp-primary);border-radius:50%;animation:mp-spin .7s linear infinite;margin-right:8px}@keyframes mp-spin{to{transform:rotate(360deg)}}
  .mp-tabs{display:flex;gap:5px;overflow:auto;padding-bottom:4px}.mp-tab{white-space:nowrap;border:0;background:var(--mp-soft);padding:8px 10px;border-radius:7px;font-weight:750;color:#667085}.mp-tab.active{background:#eeeaff;color:var(--mp-primary)}
  .mp-split{display:grid;grid-template-columns:1fr 1.45fr;gap:12px}.mp-timeline{display:flex;gap:0;overflow:auto;padding:16px 4px}.mp-rank-step{min-width:155px;position:relative;padding-top:25px}.mp-rank-step:before{content:"";position:absolute;top:8px;left:0;right:0;height:2px;background:#dfe3ec}.mp-rank-step:after{content:"";position:absolute;top:3px;left:0;width:12px;height:12px;border-radius:50%;background:var(--mp-primary);box-shadow:0 0 0 4px #eeeaff}.mp-chart-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.mp-chart{height:210px;border:1px solid var(--mp-line);border-radius:10px;padding:12px}.mp-chart h4{font-size:12px;margin:0 0 8px}.mp-chart svg{width:100%;height:155px}
  .mp-clickable{cursor:pointer}.mp-clickable:hover{background:#faf9ff}.mp-modal{position:fixed;inset:0;background:rgba(16,24,40,.55);z-index:10000;display:none;align-items:center;justify-content:center;padding:20px}.mp-modal.open{display:flex}.mp-modal-card{width:min(620px,100%);max-height:90vh;overflow:auto;background:#fff;border-radius:13px;box-shadow:0 24px 80px rgba(0,0,0,.25)}.mp-modal-head{padding:17px 20px;border-bottom:1px solid var(--mp-line);display:flex;justify-content:space-between;align-items:center}.mp-modal-body{padding:20px}.mp-modal-user{display:flex;gap:11px;align-items:center;padding-bottom:15px;border-bottom:1px solid var(--mp-line)}.mp-modal-user img{width:50px;height:50px;border-radius:50%;object-fit:cover}.mp-kv{display:grid;grid-template-columns:150px 1fr;gap:8px;padding:10px 0;border-bottom:1px solid #f0f2f6}.mp-kv span:first-child{color:var(--mp-muted);font-weight:700}.mp-kv span:last-child{word-break:break-all;font-weight:700}
  #tx-body tr:not(:only-child){cursor:pointer}#tx-body tr:not(:only-child):hover{background:#faf9ff}
  .mp-loading{opacity:.58;pointer-events:none}.mp-toast{position:fixed;right:22px;bottom:22px;background:#172033;color:#fff;padding:11px 15px;border-radius:8px;z-index:9999;display:none}
  @media(max-width:1200px){.mp-member{grid-template-columns:auto 1fr 1fr}.mp-detail:nth-last-child(-n+2){grid-column:span 1}.mp-grid{grid-template-columns:repeat(3,1fr)}.mp-wallets{grid-template-columns:repeat(3,1fr)}.mp-metric-grid{grid-template-columns:repeat(3,1fr)}}
  @media(max-width:768px){.mp-member{grid-template-columns:1fr;text-align:left}.mp-detail{border-left:0;border-top:1px solid var(--mp-line);padding:13px 0 0}.mp-grid{grid-template-columns:repeat(2,1fr)}.mp-wallets{grid-template-columns:repeat(2,1fr)}.mp-rank{grid-column:span 2}.mp-filters{grid-template-columns:1fr}.mp-toolbar{top:60px}.mp-avatar{width:72px;height:72px}.mp-metric-grid{grid-template-columns:repeat(2,1fr)}.mp-split,.mp-chart-grid{grid-template-columns:1fr}.mp-kv{grid-template-columns:1fr}}
</style>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true" data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true" data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">
<div class="d-flex flex-column flex-root app-root" id="kt_app_root"><div class="app-page flex-column flex-column-fluid" id="kt_app_page">
  <?php $this->load->view('admin/Layout/admin_topbar'); ?>
  <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
    <?php $this->load->view('admin/Layout/admin_sidebar'); ?>
    <div class="app-main flex-column flex-row-fluid" id="kt_app_main"><div class="d-flex flex-column flex-column-fluid">
      <div class="app-toolbar py-3 py-lg-6"><div class="app-container container-xxl d-flex flex-stack">
        <div><h1 class="page-heading text-gray-900 fw-bold fs-3 my-0">View User Profile</h1><div class="text-muted fw-semibold fs-7 mt-1">Admin · Members Management · <?= $e($m['referral_id']); ?></div></div>
        <div class="d-flex gap-2"><a href="<?= base_url('network-member'); ?>" class="mp-btn"><i class="ki-outline ki-arrow-left"></i> Back</a><button class="mp-btn primary" id="profile-refresh"><i class="ki-outline ki-arrows-circle"></i> Refresh</button></div>
      </div></div>
      <div class="app-content flex-column-fluid pb-10"><div class="app-container container-xxl mp-page" id="profile-app">
        <div class="mp-card mp-member">
          <img class="mp-avatar" id="member-avatar" src="<?= $e($m['avatar']); ?>" alt="<?= $e($m['name']); ?>">
          <div><div class="mp-name" data-member="name"><?= $e($m['name']); ?></div><div class="mp-sub">@<span data-member="username"><?= $e($m['username']); ?></span> · Referral ID: <b data-member="referral_id"><?= $e($m['referral_id']); ?></b></div>
            <div class="mp-badges"><span class="mp-badge <?= $m['status']==='active'?'ok':'bad'; ?>" data-member-badge="status"><?= $e($m['status']); ?></span><span class="mp-badge info" data-member-badge="kyc_status">KYC <?= $e($m['kyc_status']); ?></span><?php if($m['frozen']): ?><span class="mp-badge bad">Frozen</span><?php endif; ?></div>
          </div>
          <div class="mp-detail"><div class="mp-label">Sponsor / Current Rank</div><div class="mp-value"><span data-member="sponsor"><?= $e($m['sponsor']); ?></span><br><span data-member="rank"><?= $e($m['rank']); ?></span></div></div>
          <div class="mp-detail"><div class="mp-label">Registered / Last Active</div><div class="mp-value"><span data-member="registered_at"><?= $e($m['registered_at']); ?></span><br><span data-member="last_active"><?= $e($m['last_active'] ?: $m['last_login']); ?></span></div></div>
          <div class="mp-detail"><div class="mp-label">Contact</div><div class="mp-value"><span data-member="mobile"><?= $e($m['mobile']); ?></span><br><span data-member="email"><?= $e($m['email']); ?></span><br><span data-member="country"><?= $e($m['country']); ?></span></div></div>
        </div>

        <div class="mp-toolbar my-4">
          <button class="mp-btn" data-jump="#wallet-section"><i class="ki-outline ki-wallet"></i> Wallet</button>
          <button class="mp-btn" data-jump="#transaction-section"><i class="ki-outline ki-cheque"></i> Transactions</button>
          <button class="mp-btn" data-jump="#roi-section"><i class="ki-outline ki-chart-line-up"></i> ROI</button>
          <button class="mp-btn" data-jump="#staking-section"><i class="ki-outline ki-lock"></i> Staking</button>
          <button class="mp-btn" data-jump="#matching-section"><i class="ki-outline ki-people"></i> Matching</button>
          <button class="mp-btn" data-jump="#rank-history-section"><i class="ki-outline ki-award"></i> Ranks</button>
          <button class="mp-btn" data-jump="#genealogy-section"><i class="ki-outline ki-people"></i> Genealogy</button>
          <a class="mp-btn" href="<?= base_url('admin/audit-log?user_id='.(int)$user_id); ?>"><i class="ki-outline ki-notepad"></i> Audit Logs</a>
        </div>

        <div class="mp-grid mb-4">
          <?php
          $cards = [
            ['total_investment','Total Investment',$st['total_investment'],'BMAN','ki-wallet'],
            ['active_staking','Active Staking',$st['active_staking'],'BMAN','ki-abstract-26'],
            ['roi_earned','Total ROI Earned',$st['roi_earned'],'BMAN','ki-chart-line-up'],
            ['total_deposits','Total Deposits',$st['total_deposits'],'USDT','ki-arrow-down'],
            ['total_withdrawn','Total Withdrawn',$st['total_withdrawn'],'BMAN','ki-arrow-up'],
            ['pending_withdraw','Pending Withdraw',$st['pending_withdraw'],'BMAN','ki-time'],
            ['direct_income','Direct Income',$st['direct_income'],'BMAN','ki-user-tick'],
            ['binary_income','Binary Matching',$st['binary_income'],'BMAN','ki-people'],
          ];
          foreach ($cards as $c): ?>
            <div class="mp-card mp-stat"><div class="mp-stat-icon"><i class="ki-outline <?= $c[4]; ?>"></i></div><div class="mp-stat-label"><?= $e($c[1]); ?></div><div class="mp-stat-value" data-stat="<?= $c[0]; ?>"><?= $money($c[2]); ?><span class="mp-stat-unit"><?= $e($c[3]); ?></span></div></div>
          <?php endforeach; ?>
          <div class="mp-card mp-stat mp-rank"><div class="mp-stat-label">Rank Progress · <span data-rank="current"><?= $e($rank['current']); ?></span></div><div class="mp-progress"><span data-rank-progress style="width:<?= number_format($rankPercent,2,'.',''); ?>%"></span></div><div class="d-flex justify-content-between"><b><span data-rank="group_volume"><?= $money($rank['group_volume']); ?></span> BMAN</b><span class="text-muted">Next: <span data-rank="next"><?= $e($rank['next'] ?: 'Highest rank'); ?></span></span></div></div>
        </div>

        <section class="mp-card mp-section mb-4" id="wallet-section"><div class="mp-head"><div><h3>Wallet Balance Breakdown</h3><small>Authoritative balances from user_wallets</small></div><b data-wallet="bman_total"><?= $money($w['bman_total']); ?> BMAN total</b></div>
          <div class="mp-wallets">
            <?php foreach ([['usdt','USDT Wallet','USDT'],['exchange','Exchange Wallet','BMAN'],['earning','Earning Wallet','BMAN'],['bonus','Bonus Wallet','BMAN'],['staking','Staking Wallet','BMAN'],['pending_usdt','Pending Balance','USDT']] as $walletCard): ?>
              <div class="mp-wallet"><span class="mp-label"><?= $e($walletCard[1]); ?></span><b data-wallet="<?= $walletCard[0]; ?>"><?= $money($w[$walletCard[0]]); ?> <small><?= $walletCard[2]; ?></small></b></div>
            <?php endforeach; ?>
          </div>
        </section>

        <section class="mp-card mp-section mb-4" id="transaction-section">
          <div class="mp-head"><div><h3>User Transaction History</h3><small>Wallet ledger entries for this member only</small></div><button class="mp-btn" id="tx-refresh"><i class="ki-outline ki-arrows-circle"></i> Refresh</button></div>
          <div class="mp-filters"><input class="mp-input" type="date" id="tx-from"><input class="mp-input" type="date" id="tx-to"><select class="mp-input" id="tx-type"><option value="">All Wallets</option><option value="usdt">USDT</option><option value="exchange">Exchange</option><option value="earning">Earning</option><option value="bonus">Bonus</option><option value="staking">Staking</option></select><input class="mp-input" id="tx-search" placeholder="Search reference, hash or note"><select class="mp-input" id="tx-limit"><option>10</option><option>25</option><option>50</option></select></div>
          <div class="mp-table-wrap"><table class="mp-table"><thead><tr><th>Date & Time</th><th>Wallet</th><th>Type</th><th>Reference</th><th>Amount</th><th>Balance After</th><th>Status</th></tr></thead><tbody id="tx-body"><tr><td colspan="7" class="mp-empty">Loading transactions…</td></tr></tbody></table></div>
          <div class="mp-pager"><span id="tx-count">Loading…</span><div class="mp-pager-actions"><button class="mp-btn" id="tx-prev"><i class="ki-outline ki-left"></i> Previous</button><span class="mp-btn" id="tx-page">Page 1</span><button class="mp-btn" id="tx-next">Next <i class="ki-outline ki-right"></i></button></div></div>
        </section>

        <section class="mp-card mp-section mb-4 mp-lazy" id="roi-section" data-loader="roi">
          <div class="mp-head"><div><h3>ROI Dashboard</h3><small>ROI progress, maturity and payout history</small></div><div><button class="mp-btn" data-export="roi-csv">CSV</button> <button class="mp-btn" data-export="roi-excel">Excel</button> <button class="mp-btn" data-export="roi-pdf">PDF</button></div></div>
          <div class="mp-filters mb-4"><input class="mp-input" id="roi-search" placeholder="Search package, plan or hash"><select class="mp-input" id="roi-status"><option value="">All ROI Statuses</option><option value="paid">Paid</option><option value="pending">Pending</option><option value="failed">Failed</option></select></div>
          <div id="roi-content" class="mp-section-placeholder">Loading ROI details</div>
        </section>

        <section class="mp-card mp-section mb-4 mp-lazy" id="staking-section" data-loader="staking">
          <div class="mp-head"><div><h3>Staking Details</h3><small>Package lifecycle and blockchain confirmation</small></div></div>
          <div id="staking-content" class="mp-section-placeholder">Loading staking details</div>
        </section>

        <section class="mp-card mp-section mb-4 mp-lazy" id="matching-section" data-loader="matching">
          <div class="mp-head"><div><h3>Binary Matching</h3><small>Matching income, carry forward and history</small></div><div><button class="mp-btn" data-export="matching-csv">CSV</button> <button class="mp-btn" data-export="matching-excel">Excel</button> <button class="mp-btn" data-export="matching-pdf">PDF</button></div></div>
          <div id="matching-content" class="mp-section-placeholder">Loading matching details</div>
        </section>

        <section class="mp-card mp-section mb-4 mp-lazy" id="rank-history-section" data-loader="ranks">
          <div class="mp-head"><div><h3>Rank History</h3><small>Achievement timeline, rewards and certificates</small></div></div>
          <div id="rank-content" class="mp-section-placeholder">Loading rank history</div>
        </section>

        <section class="mp-card mp-section mb-4 mp-lazy" id="wallet-history-section" data-loader="wallet">
          <div class="mp-head"><div><h3>Wallet Credit History</h3><small>Credits grouped by business source</small></div><button class="mp-btn" data-export="wallet-csv">CSV</button></div>
          <div class="mp-tabs" id="wallet-tabs"></div><div id="wallet-history-content" class="mp-section-placeholder">Loading wallet credits</div>
        </section>

        <section class="mp-card mp-section mb-4 mp-lazy" id="analytics-section" data-loader="charts">
          <div class="mp-head"><div><h3>Performance Analytics</h3><small>Monthly ROI, matching, rank, wallet growth and investment comparison</small></div></div>
          <div id="charts-content" class="mp-section-placeholder">Loading analytics</div>
        </section>

        <section class="mp-card mp-section" id="genealogy-section">
          <div class="mp-head"><div><h3>Genealogy Tree</h3><small>Bounded, lazy-loaded mapping for faster rendering</small></div><div class="mp-tree-bar"><button class="mp-btn" id="tree-back"><i class="ki-outline ki-left"></i> Back</button><button class="mp-btn" id="tree-next">Next <i class="ki-outline ki-right"></i></button><select class="mp-input" id="tree-depth" style="width:110px"><option value="1">Depth 1</option><option value="2" selected>Depth 2</option><option value="3">Depth 3</option></select><input class="mp-input" id="tree-search" style="width:210px" placeholder="Search loaded members"><button class="mp-btn" id="tree-refresh"><i class="ki-outline ki-arrows-circle"></i></button></div></div>
          <div class="mp-tree-canvas"><div class="mp-tree" id="tree-wrap"><div class="mp-empty">Loading genealogy…</div></div></div>
          <div class="mp-pager"><span id="tree-count">Loading…</span><span class="text-muted">Click a member to show more from that node.</span></div>
        </section>
      </div></div>
      <?php $this->load->view('admin/Layout/admin_footer'); ?>
    </div></div>
  </div>
</div></div>
<div class="mp-toast" id="mp-toast"></div>
<div class="mp-modal" id="tx-modal" role="dialog" aria-modal="true" aria-labelledby="tx-modal-title"><div class="mp-modal-card"><div class="mp-modal-head"><h3 id="tx-modal-title" class="m-0">Transaction Details</h3><button class="mp-btn" id="tx-modal-close" aria-label="Close">Close</button></div><div class="mp-modal-body" id="tx-modal-body"></div></div></div>
<?php $this->load->view('admin/Layout/common_script'); ?>
<script>
(() => {
  const MEMBER_ID = <?= (int) $user_id; ?>;
  const SUMMARY_URL = <?= json_encode(base_url('admin/member/profile-summary/'.(int)$user_id)); ?>;
  const TX_URL = <?= json_encode(base_url('admin/member/profile-transactions/'.(int)$user_id)); ?>;
  const TREE_URL = <?= json_encode(base_url('admin/member/profile-tree/'.(int)$user_id)); ?>;
  const ROI_URL = <?= json_encode(base_url('admin/member/profile-roi/'.(int)$user_id)); ?>;
  const STAKING_URL = <?= json_encode(base_url('admin/member/profile-staking/'.(int)$user_id)); ?>;
  const MATCHING_URL = <?= json_encode(base_url('admin/member/profile-matching/'.(int)$user_id)); ?>;
  const RANKS_URL = <?= json_encode(base_url('admin/member/profile-ranks/'.(int)$user_id)); ?>;
  const WALLET_URL = <?= json_encode(base_url('admin/member/profile-wallet-history/'.(int)$user_id)); ?>;
  const TX_DETAIL_URL = <?= json_encode(base_url('admin/member/profile-transaction/'.(int)$user_id)); ?>;
  const DEFAULT_AVATAR = <?= json_encode(default_avatar_url()); ?>;
  let txPage=1, txPages=1, treeRoot=MEMBER_ID, treeHistory=[MEMBER_ID], treeIndex=0, treeNodes=[];
  const loadedSections={}, exportRows={};
  const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
  const num=v=>Number(v||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:8});
  const toast=msg=>{const el=document.getElementById('mp-toast');el.textContent=msg;el.style.display='block';setTimeout(()=>el.style.display='none',2200)};
  const loading=on=>document.getElementById('profile-app').classList.toggle('mp-loading',on);

  document.querySelectorAll('[data-jump]').forEach(b=>b.onclick=()=>document.querySelector(b.dataset.jump)?.scrollIntoView({behavior:'smooth',block:'start'}));
  document.getElementById('profile-refresh').onclick=async()=>{
    loading(true);
    try{
      const r=await fetch(SUMMARY_URL,{credentials:'same-origin'}),j=await r.json(); if(!j.status) throw new Error(j.message);
      Object.entries(j.data.member).forEach(([k,v])=>document.querySelectorAll(`[data-member="${k}"]`).forEach(el=>el.textContent=v||'—'));
      Object.entries(j.data.wallets).forEach(([k,v])=>document.querySelectorAll(`[data-wallet="${k}"]`).forEach(el=>el.textContent=num(v)+(k==='usdt'||k==='pending_usdt'?' USDT':' BMAN')));
      Object.entries(j.data.stats).forEach(([k,v])=>document.querySelectorAll(`[data-stat="${k}"]`).forEach(el=>el.textContent=num(v)+' BMAN'));
      const active=Object.keys(loadedSections).filter(k=>loadedSections[k]&&loaders[k]).map(k=>loaders[k]());
      await Promise.all([loadTransactions(),loadTree(),...active]); toast('Member data refreshed');
    }catch(e){toast(e.message||'Refresh failed')}finally{loading(false)}
  };

  async function loadTransactions(){
    const q=new URLSearchParams({page:txPage,limit:document.getElementById('tx-limit').value,type:document.getElementById('tx-type').value,from:document.getElementById('tx-from').value,to:document.getElementById('tx-to').value,q:document.getElementById('tx-search').value});
    const body=document.getElementById('tx-body'); body.innerHTML='<tr><td colspan="7" class="mp-empty">Loading transactions…</td></tr>';
    try{
      const r=await fetch(TX_URL+'?'+q,{credentials:'same-origin'}),j=await r.json(); if(!j.status) throw new Error(j.message);
      txPages=j.pagination.pages; txPage=Math.min(txPage,txPages); window.memberTxRows=j.data;
      body.innerHTML=j.data.length?j.data.map(row=>{const credit=Number(row.credit)>0,amount=credit?row.credit:row.debit;return `<tr><td>${esc(row.created_at)}</td><td>${esc(row.wallet_type).toUpperCase()}</td><td>${esc(row.reference_type).replaceAll('_',' ')}</td><td><b>${esc(row.reference_id||'—')}</b><div class="text-muted">${esc(row.description||'')}</div></td><td class="mp-amount ${credit?'credit':'debit'}">${credit?'+':'-'}${num(amount)}</td><td>${num(row.balance_after)}</td><td>${Number(row.is_matured)?'Matured':(row.maturity_date?'Locked':'Posted')}</td></tr>`}).join(''):'<tr><td colspan="7" class="mp-empty">No transactions found for this member.</td></tr>';
      document.getElementById('tx-count').textContent=`Showing ${j.data.length} of ${j.pagination.total} records`;
      document.getElementById('tx-page').textContent=`Page ${txPage} of ${txPages}`;
      document.getElementById('tx-prev').disabled=txPage<=1;document.getElementById('tx-next').disabled=txPage>=txPages;
    }catch(e){body.innerHTML=`<tr><td colspan="7" class="mp-empty">${esc(e.message||'Unable to load transactions')}</td></tr>`}
  }
  document.getElementById('tx-prev').onclick=()=>{if(txPage>1){txPage--;loadTransactions()}};
  document.getElementById('tx-next').onclick=()=>{if(txPage<txPages){txPage++;loadTransactions()}};
  document.getElementById('tx-refresh').onclick=()=>{txPage=1;loadTransactions()};
  ['tx-from','tx-to','tx-type','tx-limit'].forEach(id=>document.getElementById(id).onchange=()=>{txPage=1;loadTransactions()});
  let searchTimer;document.getElementById('tx-search').oninput=()=>{clearTimeout(searchTimer);searchTimer=setTimeout(()=>{txPage=1;loadTransactions()},300)};

  function treeBranch(node,map,root){
    const kids=(map[node.id]||[]).sort((a,b)=>a.position.localeCompare(b.position));
    return `<li><button class="mp-node ${node.id===root?'root':''}" data-node="${node.id}"><img src="${esc(node.avatar)}" alt=""><span style="min-width:0"><span class="mp-node-name">${esc(node.name)}</span><span class="mp-node-meta">${esc(node.referral_id)} · ${esc(node.position||'root')}</span><span class="mp-node-meta mp-node-status">${esc(node.status)} · ${num(node.wallet_total)} BMAN</span></span></button>${kids.length?`<ul>${kids.map(k=>treeBranch(k,map,root)).join('')}</ul>`:''}</li>`;
  }
  async function loadTree(){
    const wrap=document.getElementById('tree-wrap'),depth=document.getElementById('tree-depth').value;wrap.innerHTML='<div class="mp-empty">Loading genealogy…</div>';
    try{
      const r=await fetch(`${TREE_URL}?root_id=${treeRoot}&depth=${depth}`,{credentials:'same-origin'}),j=await r.json();if(!j.status)throw new Error(j.message);
      treeNodes=j.data;const map={};j.data.forEach(n=>{if(n.parent_id)(map[n.parent_id]??=[]).push(n)});const root=j.data.find(n=>n.id===treeRoot);
      wrap.innerHTML=root?`<ul>${treeBranch(root,map,treeRoot)}</ul>`:'<div class="mp-empty">No genealogy data found.</div>';
      wrap.querySelectorAll('.mp-node').forEach((button,index)=>{
        const node=j.data.find(n=>n.id===Number(button.dataset.node));if(!node)return;
        const img=button.querySelector('img');img.onerror=()=>{img.onerror=null;img.src=DEFAULT_AVATAR};
        button.insertAdjacentHTML('beforeend',`<span class="mp-node-tip"><b>${esc(node.name)}</b><br>${esc(node.email||'No email')}<br>Exchange: ${num(node.exchange_balance)} BMAN<br>Earning: ${num(node.earning_balance)} BMAN<br>Staking: ${num(node.staking_balance)} BMAN<br>Bonus: ${num(node.bonus_balance)} BMAN<br>Click to open this branch</span>`);
      });
      wrap.querySelectorAll('[data-node]').forEach(b=>b.onclick=()=>drill(Number(b.dataset.node)));
      document.getElementById('tree-count').textContent=`${j.data.length} members loaded · Root #${treeRoot} · Depth ${depth}`;
      syncTreeButtons();
    }catch(e){wrap.innerHTML=`<div class="mp-empty">${esc(e.message||'Unable to load genealogy')}</div>`}
  }
  function drill(id){if(id===treeRoot)return;treeHistory=treeHistory.slice(0,treeIndex+1);treeHistory.push(id);treeIndex++;treeRoot=id;loadTree()}
  function syncTreeButtons(){document.getElementById('tree-back').disabled=treeIndex<=0;document.getElementById('tree-next').disabled=treeIndex>=treeHistory.length-1}
  document.getElementById('tree-back').onclick=()=>{if(treeIndex>0){treeRoot=treeHistory[--treeIndex];loadTree()}};
  document.getElementById('tree-next').onclick=()=>{if(treeIndex<treeHistory.length-1){treeRoot=treeHistory[++treeIndex];loadTree()}};
  document.getElementById('tree-refresh').onclick=loadTree;document.getElementById('tree-depth').onchange=loadTree;
  document.getElementById('tree-search').oninput=function(){const q=this.value.toLowerCase();document.querySelectorAll('.mp-node').forEach(n=>{const hit=!q||n.textContent.toLowerCase().includes(q);n.style.opacity=hit?'1':'.2'})};

  const metric=(label,value,unit='BMAN')=>`<div class="mp-metric"><span class="mp-label">${esc(label)}</span><b>${typeof value==='number'?num(value):esc(value??'—')} ${typeof value==='number'?esc(unit):''}</b></div>`;
  const empty=(cols,msg)=>`<tr><td colspan="${cols}" class="mp-empty">${esc(msg)}</td></tr>`;
  async function getJson(url){const r=await fetch(url,{credentials:'same-origin'}),j=await r.json();if(!r.ok||!j.status)throw new Error(j.message||'Unable to load section');return j}
  let roiPage=1;
  async function loadRoi(){
    const el=document.getElementById('roi-content');
    const query=new URLSearchParams({page:roiPage,limit:10,q:document.getElementById('roi-search').value,status:document.getElementById('roi-status').value});
    try{const j=await getJson(ROI_URL+'?'+query);exportRows.roi=j.data;const x=j.summary;el.className='';
      el.innerHTML=`<div class="mp-metric-grid">${metric('Total ROI Earned',x.total_roi_earned)}${metric('Pending ROI',x.pending_roi)}${metric("Today's ROI",x.today_roi)}${metric('Monthly ROI',x.monthly_roi)}${metric('Lifetime ROI',x.lifetime_roi)}${metric('Next ROI Date',x.next_roi_date,'')}${metric('Next ROI Amount',x.next_roi_amount)}${metric('Maturity Date',x.maturity_date,'')}${metric('Remaining Days',x.remaining_days,'')}${metric('ROI Completion',num(x.completion_percent)+'%','')}${metric('ROI Status',x.roi_status,'')}</div><div class="mp-head mt-6 mb-0"><h3>ROI History</h3></div><div class="mp-table-wrap"><table class="mp-table"><thead><tr><th>Package</th><th>Plan Type</th><th>ROI %</th><th>Monthly ROI</th><th>Paid Date</th><th>Status</th><th>Transaction Hash</th><th>Wallet</th><th>Remaining ROI</th></tr></thead><tbody>${j.data.length?j.data.map(r=>`<tr><td>${esc(r.package_name||'—')}</td><td>${Number(r.is_special)?'Special':'Normal'} · ${esc(r.plan_code||'—')}</td><td>${num(r.roi_percent)}%</td><td>${num(r.amount)}</td><td>${esc(r.credit_date)}</td><td>${esc(r.status)}</td><td>${esc(r.tx_hash||'—')}</td><td>${esc(r.wallet)}</td><td>${num(Math.max(0,Number(r.stake_amount||0)-Number(r.amount||0)))}</td></tr>`).join(''):empty(9,'No ROI payments found.')}</tbody></table></div><div class="mp-pager"><span>Showing ${j.data.length} of ${j.pagination.total}</span><div class="mp-pager-actions"><button class="mp-btn" id="roi-prev" ${roiPage<=1?'disabled':''}>Previous</button><span class="mp-btn">Page ${roiPage} of ${j.pagination.pages}</span><button class="mp-btn" id="roi-next" ${roiPage>=j.pagination.pages?'disabled':''}>Next</button></div></div>`;
      document.getElementById('roi-prev').onclick=()=>{if(roiPage>1){roiPage--;loadRoi()}};document.getElementById('roi-next').onclick=()=>{if(roiPage<j.pagination.pages){roiPage++;loadRoi()}};
    }catch(e){el.className='mp-empty';el.textContent=e.message}
  }
  async function loadStaking(){
    const el=document.getElementById('staking-content');
    try{const j=await getJson(STAKING_URL);exportRows.staking=j.data;el.className='mp-table-wrap';el.innerHTML=`<table class="mp-table"><thead><tr><th>Package</th><th>Investment</th><th>Plan / Duration</th><th>Start</th><th>Maturity</th><th>Days Left</th><th>Current ROI</th><th>Total ROI Earned</th><th>Maturity %</th><th>Blockchain</th><th>Certificate</th></tr></thead><tbody>${j.data.length?j.data.map(r=>`<tr><td><b>${esc(r.package_name||'—')}</b>${Number(r.is_special)?'<div class="mp-badge info">Special Plan</div>':''}</td><td>${num(r.stake_amount)} BMAN</td><td>${esc(r.plan_code)} · ${esc(r.duration_years)} year(s)</td><td>${esc(r.start_date)}</td><td>${esc(r.maturity_date)}</td><td>${esc(r.remaining_days)}</td><td>${num(r.roi_percent)}%</td><td>${num(r.roi_earned)} BMAN</td><td><div class="mp-progress"><span style="width:${Number(r.maturity_percent||0)}%"></span></div>${num(r.maturity_percent)}%</td><td title="${esc(r.tx_hash||'')}">${esc(r.network||'—')} · ${esc(r.status)}</td><td>${r.certificate_pdf?`<a class="mp-btn" href="${esc(r.certificate_pdf)}" download>Download</a>`:'—'}</td></tr>`).join(''):empty(11,'No staking plans found.')}</tbody></table>`}catch(e){el.className='mp-empty';el.textContent=e.message}
  }
  let roiTimer;document.getElementById('roi-search').oninput=()=>{clearTimeout(roiTimer);roiTimer=setTimeout(()=>{roiPage=1;loadRoi()},250)};document.getElementById('roi-status').onchange=()=>{roiPage=1;loadRoi()};
  async function loadMatching(){
    const el=document.getElementById('matching-content');
    try{const j=await getJson(MATCHING_URL);exportRows.matching=j.data;el.className='';el.innerHTML=`<div class="mp-metric-grid">${Object.entries(j.summary).map(([k,v])=>metric(k.replaceAll('_',' '),v)).join('')}</div><div class="mp-head mt-6 mb-0"><h3>Matching History</h3></div><div class="mp-table-wrap"><table class="mp-table"><thead><tr><th>Date</th><th>Left Volume</th><th>Right Volume</th><th>Matched Volume</th><th>Carry Forward</th><th>Bonus %</th><th>Bonus Amount</th><th>Wallet Credited</th><th>Status</th></tr></thead><tbody>${j.data.length?j.data.map(r=>`<tr><td>${esc(r.created_at)}</td><td>${num(r.left_before)}</td><td>${num(r.right_before)}</td><td>${num(r.matched_volume)}</td><td>${num(Math.abs(Number(r.left_before)-Number(r.right_before)))}</td><td>${num(r.total_percent)}%</td><td>${num(Number(r.earning_amount)+Number(r.staking_amount))}</td><td>Earning + Staking</td><td>Credited</td></tr>`).join(''):empty(9,'No binary matching history found.')}</tbody></table></div>`}catch(e){el.className='mp-empty';el.textContent=e.message}
  }
  async function loadRanks(){
    const el=document.getElementById('rank-content');
    try{const j=await getJson(RANKS_URL);exportRows.ranks=j.data;el.className='mp-timeline';el.innerHTML=j.data.length?j.data.map(r=>`<article class="mp-rank-step">${r.badge_image?`<img src="${esc(r.badge_image)}" onerror="this.style.display='none'" alt="" style="width:34px;height:34px;object-fit:contain">`:''}<b>${esc(r.current_rank||'—')}</b><div class="mp-node-meta">${esc(r.achieved_at)}</div><div class="mp-node-meta">Previous: ${esc(r.previous_rank||'Default')}</div><div class="mp-node-meta">Team: ${num(r.achieved_volume)}</div><div class="mp-node-meta">L ${num(r.left_volume)} · R ${num(r.right_volume)}</div><div class="mp-node-meta">Reward: ${num(r.reward_amount)} BMAN</div><div class="mp-node-meta">Approval: ${r.source==='admin'?'Admin approved':'Auto approved'}</div>${r.certificate_pdf?`<a href="${esc(r.certificate_pdf)}" download>Certificate</a>`:''}<div class="mp-status"><span class="mp-dot"></span>${esc(r.reward_status||'Achieved')}</div></article>`).join(''):'<div class="mp-empty">No rank achievements found.</div>'}catch(e){el.className='mp-empty';el.textContent=e.message}
  }
  const walletTypes=[['all','All Credits'],['roi','ROI Credits'],['binary','Binary Matching'],['direct','Direct Income'],['rank','Rank Rewards'],['leadership','Leadership Bonus'],['generation','Generation Bonus'],['withdrawals','Withdrawals'],['transfers','Transfers']];let walletType='all',walletChart=[];
  async function loadWallet(){
    const tabs=document.getElementById('wallet-tabs');tabs.innerHTML=walletTypes.map(([k,l])=>`<button class="mp-tab ${k===walletType?'active':''}" data-wallet-tab="${k}">${l}</button>`).join('');tabs.querySelectorAll('[data-wallet-tab]').forEach(b=>b.onclick=()=>{walletType=b.dataset.walletTab;loadWallet()});
    const el=document.getElementById('wallet-history-content');el.className='mp-section-placeholder';el.textContent='Loading wallet credits';
    try{const j=await getJson(WALLET_URL+'?type='+walletType);exportRows.wallet=j.data;walletChart=j.charts.wallet_growth||[];el.className='mp-table-wrap';el.innerHTML=`<table class="mp-table"><thead><tr><th>Date</th><th>Wallet</th><th>Source</th><th>Description</th><th>Credit</th><th>Debit</th><th>Balance</th><th>Hash</th></tr></thead><tbody>${j.data.length?j.data.map(r=>`<tr><td>${esc(r.created_at)}</td><td>${esc(r.wallet_type)}</td><td>${esc(r.reference_type)}</td><td>${esc(r.description||'—')}</td><td class="mp-amount credit">${num(r.credit)}</td><td class="mp-amount debit">${num(r.debit)}</td><td>${num(r.balance_after)}</td><td>${esc(r.tx_hash||'—')}</td></tr>`).join(''):empty(8,'No wallet entries in this category.')}</tbody></table>`;if(loadedSections.charts)loadCharts()}catch(e){el.className='mp-empty';el.textContent=e.message}
  }
  const spark=(values,color)=>{const v=values.map(Number),max=Math.max(1,...v),step=v.length>1?100/(v.length-1):100,pts=v.map((n,i)=>`${i*step},${92-(n/max)*78}`).join(' ');return `<svg viewBox="0 0 100 100" preserveAspectRatio="none"><polyline fill="none" stroke="${color}" stroke-width="2" vector-effect="non-scaling-stroke" points="${pts||'0,92 100,92'}"/></svg>`};
  async function loadCharts(){loadedSections.charts=true;const el=document.getElementById('charts-content');try{const j=await getJson(WALLET_URL+'?type=all'),c=j.charts||{},roi=(c.monthly_roi||[]).map(r=>r.total),matching=(c.matching_trend||[]).map(r=>r.total),ranks=(c.rank_progress||[]).map(r=>r.achieved_volume),wallet=(c.wallet_growth||[]).map(r=>r.net),stakes=(c.investment||[]).map(r=>r.total);el.className='mp-chart-grid';el.innerHTML=[['Monthly ROI Chart',roi,'#2684ff'],['Binary Matching Trend',matching,'#7657ff'],['Rank Progress Timeline',ranks,'#f59e0b'],['Wallet Growth Chart',wallet,'#18b76a'],['Investment vs ROI Chart',stakes.concat(roi),'#e5484d']].map(([t,d,color])=>`<div class="mp-chart"><h4>${t}</h4>${spark(d,color)}<div class="text-muted">${d.length?d.length+' data points':'No data yet'}</div></div>`).join('')}catch(e){el.className='mp-empty';el.textContent=e.message}}
  async function showTransaction(id){
    const modal=document.getElementById('tx-modal'),body=document.getElementById('tx-modal-body');modal.classList.add('open');body.innerHTML='<div class="mp-section-placeholder">Loading transaction</div>';
    try{const j=await getJson(TX_DETAIL_URL+'/'+id),c=j.chain||{},l=j.ledger||{},gasPrice=c.gas_price?Number(c.gas_price)/1e9:null,hash=c.tx_hash||l.tx_hash;body.innerHTML=`<div class="mp-modal-user"><img src="${esc(j.member.avatar)}" onerror="this.src='${esc(DEFAULT_AVATAR)}'" alt=""><div><b>${esc(j.member.name)}</b><div class="text-muted">${esc(j.member.referral_id)}</div></div></div>${[['Transaction Hash',hash||'Not recorded'],['Network',c.network||'Not recorded'],['Status',c.status||'Posted'],['From',c.from_address||'Not recorded'],['To',c.to_address||'Not recorded'],['Amount',c.amount||((Number(l.credit)>0?l.credit:l.debit)||0)],['Gas Used',c.gas_used||'Not recorded'],['Gas Price',gasPrice!==null?num(gasPrice)+' Gwei':'Not recorded'],['Gas Fee',c.gas_fee_total?num(c.gas_fee_total)+' BNB':'Not recorded'],['Gas Limit',c.gas_limit||'Not recorded'],['Block Number',c.block_number||'Not recorded'],['Confirmations',c.confirmation_count??'Not recorded'],['Timestamp',c.block_timestamp||l.created_at]].map(([k,v])=>`<div class="mp-kv"><span>${k}</span><span>${esc(v)}</span></div>`).join('')}${hash&&String(hash).startsWith('0x')?`<a class="mp-btn primary mt-4" target="_blank" rel="noopener" href="https://bscscan.com/tx/${esc(hash)}">View on BscScan</a>`:''}`}catch(e){body.innerHTML=`<div class="mp-empty">${esc(e.message)}</div>`}
  }
  document.getElementById('tx-body').addEventListener('click',e=>{const row=e.target.closest('tr');if(!row||!window.memberTxRows)return;const index=[...row.parentNode.children].indexOf(row),tx=window.memberTxRows[index];if(tx)showTransaction(tx.id)});
  document.getElementById('tx-modal-close').onclick=()=>document.getElementById('tx-modal').classList.remove('open');document.getElementById('tx-modal').onclick=e=>{if(e.target.id==='tx-modal')e.currentTarget.classList.remove('open')};
  function exportData(kind,format){const rows=exportRows[kind]||[];if(!rows.length)return toast('No records to export');if(format==='pdf'){window.print();return}const keys=Object.keys(rows[0]),cell=v=>`"${String(v??'').replaceAll('"','""')}"`,csv=[keys.join(','),...rows.map(r=>keys.map(k=>cell(r[k])).join(','))].join('\r\n'),html=`<table><tr>${keys.map(k=>`<th>${esc(k)}</th>`).join('')}</tr>${rows.map(r=>`<tr>${keys.map(k=>`<td>${esc(r[k])}</td>`).join('')}</tr>`).join('')}</table>`,blob=new Blob([format==='excel'?html:csv],{type:format==='excel'?'application/vnd.ms-excel':'text/csv'}),a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download=`member-${MEMBER_ID}-${kind}.${format==='excel'?'xls':'csv'}`;a.click();URL.revokeObjectURL(a.href)}
  document.querySelectorAll('[data-export]').forEach(b=>b.onclick=()=>{const [kind,format]=b.dataset.export.split('-');exportData(kind,format)});
  const loaders={roi:loadRoi,staking:loadStaking,matching:loadMatching,ranks:loadRanks,wallet:loadWallet,charts:loadCharts},observer=new IntersectionObserver(entries=>entries.forEach(e=>{if(e.isIntersecting){const k=e.target.dataset.loader;if(!loadedSections[k]){loadedSections[k]=true;loaders[k]()}observer.unobserve(e.target)}}),{rootMargin:'300px'});document.querySelectorAll('.mp-lazy').forEach(el=>observer.observe(el));
  loadTransactions();loadTree();
})();
</script>
</body></html>
