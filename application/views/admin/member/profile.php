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
  .mp-loading{opacity:.58;pointer-events:none}.mp-toast{position:fixed;right:22px;bottom:22px;background:#172033;color:#fff;padding:11px 15px;border-radius:8px;z-index:9999;display:none}
  @media(max-width:1200px){.mp-member{grid-template-columns:auto 1fr 1fr}.mp-detail:nth-last-child(-n+2){grid-column:span 1}.mp-grid{grid-template-columns:repeat(3,1fr)}.mp-wallets{grid-template-columns:repeat(3,1fr)}}
  @media(max-width:768px){.mp-member{grid-template-columns:1fr;text-align:left}.mp-detail{border-left:0;border-top:1px solid var(--mp-line);padding:13px 0 0}.mp-grid{grid-template-columns:repeat(2,1fr)}.mp-wallets{grid-template-columns:repeat(2,1fr)}.mp-rank{grid-column:span 2}.mp-filters{grid-template-columns:1fr}.mp-toolbar{top:60px}.mp-avatar{width:72px;height:72px}}
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
<?php $this->load->view('admin/Layout/common_script'); ?>
<script>
(() => {
  const MEMBER_ID = <?= (int) $user_id; ?>;
  const SUMMARY_URL = <?= json_encode(base_url('admin/member/profile-summary/'.(int)$user_id)); ?>;
  const TX_URL = <?= json_encode(base_url('admin/member/profile-transactions/'.(int)$user_id)); ?>;
  const TREE_URL = <?= json_encode(base_url('admin/member/profile-tree/'.(int)$user_id)); ?>;
  let txPage=1, txPages=1, treeRoot=MEMBER_ID, treeHistory=[MEMBER_ID], treeIndex=0, treeNodes=[];
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
      await Promise.all([loadTransactions(),loadTree()]); toast('Member data refreshed');
    }catch(e){toast(e.message||'Refresh failed')}finally{loading(false)}
  };

  async function loadTransactions(){
    const q=new URLSearchParams({page:txPage,limit:document.getElementById('tx-limit').value,type:document.getElementById('tx-type').value,from:document.getElementById('tx-from').value,to:document.getElementById('tx-to').value,q:document.getElementById('tx-search').value});
    const body=document.getElementById('tx-body'); body.innerHTML='<tr><td colspan="7" class="mp-empty">Loading transactions…</td></tr>';
    try{
      const r=await fetch(TX_URL+'?'+q,{credentials:'same-origin'}),j=await r.json(); if(!j.status) throw new Error(j.message);
      txPages=j.pagination.pages; txPage=Math.min(txPage,txPages);
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
  loadTransactions();loadTree();
})();
</script>
</body></html>
