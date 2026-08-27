<?php $this->load->view('admin/Layout/common_style'); ?>
<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true"
  data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true"
  data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
  data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">
  <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
    <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
      <?php $this->load->view('admin/Layout/admin_topbar'); ?>
      <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
        <?php $this->load->view('admin/Layout/admin_sidebar'); ?>
        <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
          <div class="d-flex flex-column flex-column-fluid">
            <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
              <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                  <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0"><?php echo $title; ?></h1>
                  <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted"><a href="<?php echo base_url(); ?>" class="text-muted text-hover-primary">Finance</a></li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted"><?php echo $title; ?></li>
                  </ul>
                </div>
              </div>
            </div>
            <div id="kt_app_content" class="app-content flex-column-fluid mt-10">
              <div id="kt_app_content_container" class="app-container container-xxl">
                <?php $this->load->view('notification'); ?>

                <!-- Admin New Transfer (no KYC / no transfer password) -->
                <div class="card mb-5 mb-xxl-8">
                  <div class="card-header border-transparent pt-5">
                    <h3 class="card-title fw-bold">New Internal Transfer <span class="text-muted fs-7 fw-normal ms-2">admin — no KYC / password validation</span></h3>
                  </div>
                  <div class="card-body pt-2 pb-8">
                    <form id="it-new-form">
                      <div class="row g-4">
                        <div class="col-md-4">
                          <label class="form-label fw-semibold fs-7">Source User</label>
                          <select class="form-select form-select-solid" name="sender_id" id="it-sender" data-placeholder="Search member by name / referral / email…">
                            <option value=""></option>
                          </select>
                        </div>
                        <div class="col-md-8">
                          <label class="form-label fw-semibold fs-7">Wallet Balances</label>
                          <div class="d-flex gap-3 flex-wrap" id="it-balances">
                            <?php foreach (['exchange'=>'Exchange','earning'=>'Earning','staking'=>'Staking','bonus'=>'Bonus'] as $k=>$lbl): ?>
                              <div class="border rounded px-3 py-2" style="min-width:120px">
                                <div class="text-muted fs-8 text-uppercase"><?php echo $lbl; ?></div>
                                <div class="fw-bold fs-6" id="it-bal-<?php echo $k; ?>">—</div>
                              </div>
                            <?php endforeach; ?>
                          </div>
                        </div>

                        <div class="col-md-12">
                          <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="mode" id="it-mode-self" value="self" checked>
                            <label class="btn btn-sm btn-outline btn-outline-primary" for="it-mode-self">Between User's Wallets</label>
                            <input type="radio" class="btn-check" name="mode" id="it-mode-member" value="member">
                            <label class="btn btn-sm btn-outline btn-outline-primary" for="it-mode-member">To Another Member</label>
                          </div>
                        </div>

                        <div class="col-md-3">
                          <label class="form-label fw-semibold fs-7">From Wallet</label>
                          <select class="form-select form-select-solid" name="from_wallet" id="it-from">
                            <option value="exchange">Exchange</option><option value="earning">Earning</option>
                            <option value="staking">Staking</option><option value="bonus">Bonus</option>
                          </select>
                        </div>
                        <div class="col-md-3" id="it-to-wrap">
                          <label class="form-label fw-semibold fs-7">To Wallet</label>
                          <select class="form-select form-select-solid" name="to_wallet" id="it-to">
                            <option value="bonus">Bonus</option><option value="earning">Earning</option>
                            <option value="staking">Staking</option><option value="exchange">Exchange</option>
                          </select>
                        </div>
                        <div class="col-md-12 d-none" id="it-member-hint">
                          <div class="text-warning fw-semibold fs-8"></div>
                        </div>
                        <div class="col-md-4 d-none" id="it-recipient-wrap">
                          <label class="form-label fw-semibold fs-7">Recipient User</label>
                          <select class="form-select form-select-solid" name="recipient_id" id="it-recipient" data-placeholder="Search recipient…">
                            <option value=""></option>
                          </select>
                        </div>
                        <div class="col-md-3">
                          <label class="form-label fw-semibold fs-7">Amount</label>
                          <input type="number" step="0.00000001" min="0" name="amount" class="form-control form-control-solid" />
                        </div>
                        <div class="col-md-9">
                          <label class="form-label fw-semibold fs-7">Note (optional)</label>
                          <input type="text" name="note" maxlength="255" class="form-control form-control-solid" />
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                          <button type="submit" class="btn btn-primary w-100">Transfer</button>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>

                <div class="card mb-5 mb-xxl-8">
                  <div class="card-header border-transparent pt-5"><h3 class="card-title fw-bold">Transfer History</h3></div>
                  <div class="card-body pt-3 pb-9">
                    <form method="get" class="d-flex flex-wrap gap-3 mb-6">
                      <input type="text" name="reference" value="<?php echo html_escape($filters['reference']); ?>" class="form-control form-control-solid form-control-sm w-175px" placeholder="Reference (WTF-…)" />
                      <input type="text" name="user" value="<?php echo html_escape($filters['user']); ?>" class="form-control form-control-solid form-control-sm w-175px" placeholder="User / referral / email" />
                      <select name="wallet" class="form-select form-select-solid form-select-sm w-150px">
                        <option value="">Any wallet</option>
                        <?php foreach (['exchange','earning','staking','bonus'] as $w): ?>
                          <option value="<?php echo $w; ?>" <?php echo $filters['wallet']===$w?'selected':''; ?>><?php echo ucfirst($w); ?></option>
                        <?php endforeach; ?>
                      </select>
                      <select name="status" class="form-select form-select-solid form-select-sm w-140px">
                        <option value="">Any status</option>
                        <?php foreach (['completed','failed','reversed'] as $s): ?>
                          <option value="<?php echo $s; ?>" <?php echo $filters['status']===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
                        <?php endforeach; ?>
                      </select>
                      <button class="btn btn-primary btn-sm">Filter</button>
                    </form>
                    <div class="table-responsive">
                      <table class="table align-middle table-row-dashed fs-6 gy-4">
                        <thead><tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                          <th>Reference</th><th>Sender</th><th>Receiver</th><th>From Wallet</th><th>To Wallet</th><th class="text-end">Amount</th><th>Token</th><th>Status</th><th>Date</th><th class="text-end">Details</th>
                        </tr></thead>
                        <tbody class="text-gray-700 fw-semibold">
                          <?php foreach ($transfers as $t): $isSelf = empty($t['to_user_id']); ?>
                          <tr>
                            <td>
                              <span class="text-monospace fs-8 fw-bold text-primary"><?php echo html_escape($t['ref']); ?></span>
                              <div class="text-muted fs-8">TXN <?php echo html_escape($t['txn_uid'] ?: '—'); ?> · <span class="text-uppercase"><?php echo html_escape($t['via'] ?? 'user'); ?></span></div>
                            </td>
                            <td><?php echo html_escape(($t['sender_name'] ?? '') ?: ('#'.$t['user_id'])); ?><div class="text-muted fs-8"><?php echo html_escape($t['sender_ref'] ?? ''); ?></div></td>
                            <td>
                              <?php if (!$isSelf): ?>
                                <?php echo html_escape(($t['recipient_name'] ?? '') ?: ('#'.$t['to_user_id'])); ?><div class="text-muted fs-8"><?php echo html_escape($t['recipient_ref'] ?? ''); ?></div>
                              <?php else: ?><span class="badge badge-light-info">Self</span><?php endif; ?>
                            </td>
                            <td><span class="badge badge-light-primary text-uppercase"><?php echo html_escape($t['from_wallet']); ?></span></td>
                            <td><span class="badge badge-light-primary text-uppercase"><?php echo html_escape($t['to_wallet']); ?></span></td>
                            <td class="text-end fw-bold"><?php echo number_format((float)$t['amount'],4); ?></td>
                            <td><span class="badge badge-light-dark">BMAN</span></td>
                            <td><span class="badge badge-light-<?php echo $t['status']==='completed'?'success':($t['status']==='failed'?'danger':'warning'); ?>"><?php echo strtoupper($t['status']); ?></span></td>
                            <td class="text-muted fs-8"><?php echo html_escape($t['created_at']); ?></td>
                            <td class="text-end"><button class="btn btn-sm btn-light-primary it-view" data-ref="<?php echo html_escape($t['ref']); ?>">Details</button></td>
                          </tr>
                          <?php endforeach; ?>
                          <?php if (empty($transfers)): ?><tr><td colspan="10" class="text-muted">No transfers found.</td></tr><?php endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <div class="modal fade" id="it-modal" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered mw-650px"><div class="modal-content">
                    <div class="modal-header"><h3 class="modal-title">Transfer Detail</h3><div class="btn btn-sm btn-icon" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></div></div>
                    <div class="modal-body" id="it-body">Loading…</div>
                  </div></div>
                </div>
              </div>
            </div>
          </div>
          <?php $this->load->view('admin/Layout/admin_footer'); ?>
        </div>
      </div>
    </div>
  </div>
  <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true"><i class="ki-duotone ki-arrow-up"><span class="path1"></span><span class="path2"></span></i></div>
  <?php $this->load->view('admin/Layout/common_script'); ?>
  <script src="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.js"></script>
  <?php $this->load->view('shared/wallet_transfer_ui', [
    'wtx_panel'   => 'admin',
    'wtx_preview' => 'admin/finance/internal-transfers/preview',
    'wtx_detail'  => 'admin/finance/internal-transfers/tx-detail',
  ]); ?>
  <script>
  (function(){
    const base='<?php echo base_url(); ?>';
    // Shared transaction-details modal (identical to the User Panel).
    document.querySelector('.table').addEventListener('click', (e)=>{
      const b=e.target.closest('.it-view'); if(!b) return;
      if(window.WalletTransferUI) WalletTransferUI.openDetail(b.dataset.ref);
    });

    /* ---- New Transfer (admin, no validation) ---- */
    async function post(url, fd){ const r=await fetch(base+url,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}}); let j={}; try{j=await r.json();}catch(_){}; return {ok:r.ok&&j.status==='success',j}; }
    function toast(m,ok){ if(window.Swal) Swal.fire({text:m,icon:ok?'success':'error',buttonsStyling:false,confirmButtonText:'Ok',customClass:{confirmButton:'btn btn-primary'}}); else alert(m); }
    const senderSel=document.getElementById('it-sender');
    async function loadBalances(){
      const uid=senderSel.value;
      ['exchange','earning','staking','bonus'].forEach(k=>document.getElementById('it-bal-'+k).textContent='—');
      if(!uid) return;
      const fd=new FormData(); fd.append('user_id',uid);
      const {ok,j}=await post('admin/finance/internal-transfers/balances',fd);
      if(ok){ const b=j.balances; ['exchange','earning','staking','bonus'].forEach(k=>document.getElementById('it-bal-'+k).textContent=Number(b[k]).toFixed(4)); }
    }

    function clearRecipient(){
      if(window.jQuery){ jQuery('#it-recipient').val(null).trigger('change.select2'); }
      else { const r=document.getElementById('it-recipient'); if(r) r.value=''; }
    }
    // Select2 with server-side AJAX search + pagination (no upfront DOM dump).
    if(window.jQuery && jQuery.fn.select2){
      // Source user — ALL members (admin may act on behalf of anyone).
      jQuery('#it-sender').select2({
        placeholder:'Search member…', allowClear:true, width:'100%', minimumInputLength:0,
        ajax:{ url: base+'admin/finance/internal-transfers/users', dataType:'json', delay:250, cache:true,
          data: params => ({q: params.term||'', page: params.page||1}),
          processResults: (data, params) => { params.page=params.page||1;
            return { results: data.results||[], pagination:{ more: !!(data.pagination&&data.pagination.more) } }; } }
      });
      // Recipient — ONLY the source's valid recipients for the chosen From wallet
      // (downline for exchange/earning/staking, direct left/right leg for bonus).
      jQuery('#it-recipient').select2({
        placeholder:'Search recipient…', allowClear:true, width:'100%', minimumInputLength:0,
        ajax:{ url: base+'admin/finance/internal-transfers/recipients', dataType:'json', delay:250, cache:false,
          data: params => ({ q: params.term||'', sender_id: jQuery('#it-sender').val()||'', from_wallet: jQuery('#it-from').val()||'' }),
          processResults: data => ({ results: data.results||[], pagination:{ more:false } }) }
      });
      // Changing the source or the From wallet changes the valid recipient set.
      jQuery('#it-sender').on('change', function(){ loadBalances(); clearRecipient(); });
      jQuery('#it-from').on('change', clearRecipient);
    } else {
      senderSel.addEventListener('change',loadBalances);
    }
    const fromSel=document.getElementById('it-from'), toSel=document.getElementById('it-to');
    function curMode(){ return document.getElementById('it-mode-member').checked ? 'member' : 'self'; }
    // Shared direction matrix: disable invalid wallet combinations up front.
    function applyMatrix(){
      const mode=curMode();
      if(window.WalletTransferUI){
        WalletTransferUI.applyMatrixToSelect(fromSel,'from',mode,'');
        if(mode==='self') WalletTransferUI.applyMatrixToSelect(toSel,'to','self',fromSel.value);
      }
      const hintWrap=document.getElementById('it-member-hint');
      const hint=hintWrap.querySelector('div');
      if(mode==='member' && window.WalletTransferUI){
        const t=WalletTransferUI.memberRuleText(fromSel.value);
        hint.textContent=t; hintWrap.classList.toggle('d-none',!t);
      } else { hintWrap.classList.add('d-none'); }
    }
    document.querySelectorAll('input[name="mode"]').forEach(r=>r.addEventListener('change',function(){
      const self=document.getElementById('it-mode-self').checked;
      document.getElementById('it-to-wrap').classList.toggle('d-none',!self);
      document.getElementById('it-recipient-wrap').classList.toggle('d-none',self);
      applyMatrix();
    }));
    fromSel.addEventListener('change',applyMatrix);
    applyMatrix();

    // Actual POST to the centralized engine (runs after shared Confirm passes).
    async function doAdminTransfer(){
      const {ok,j}=await post('admin/finance/internal-transfers/do-transfer',new FormData(document.getElementById('it-new-form')));
      toast(j.message||'',ok);
      if(ok){ loadBalances(); setTimeout(()=>location.reload(),1200); }
    }
    document.getElementById('it-new-form').addEventListener('submit',function(e){
      e.preventDefault();
      const mode=curMode();
      const sender=document.getElementById('it-sender');
      const senderId=sender.value;
      const rcptSel=document.getElementById('it-recipient');
      const recipientId=rcptSel.value;
      const from=fromSel.value, to=toSel.value;
      const amount=(e.target.querySelector('[name="amount"]').value||'').trim();
      if(!senderId){ toast('Select the source user.',false); return; }
      if(mode==='member' && !recipientId){ toast('Select the recipient user.',false); return; }
      if(!amount || parseFloat(amount)<=0){ toast('Enter a valid amount.',false); return; }
      const senderText=(sender.selectedOptions[0]&&sender.selectedOptions[0].textContent)||('#'+senderId);
      const rcptText=(rcptSel.selectedOptions[0]&&rcptSel.selectedOptions[0].textContent)||('#'+recipientId);
      if(window.WalletTransferUI){
        WalletTransferUI.openConfirm({
          mode: mode,
          fields: { mode:mode, sender_id:senderId, from_wallet:from, to_wallet:to, recipient_id:recipientId, amount:amount },
          sourceUser: senderText,
          fromWallet: from,
          toWallet: to,
          amount: amount,
          recipientFallback: (mode==='member' ? rcptText : 'Self (own wallets)'),
          onConfirm: doAdminTransfer
        });
        return;
      }
      doAdminTransfer();
    });
  })();
  </script>
</body>
</html>
