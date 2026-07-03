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
                <div class="card mb-5 mb-xxl-8">
                  <div class="card-header border-transparent pt-5"><h3 class="card-title fw-bold"><?php echo $card_tilte; ?></h3></div>
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
                          <th>Reference</th><th>Sender</th><th>Recipient</th><th>Wallet</th><th class="text-end">Amount</th><th>Status</th><th>Created</th><th></th>
                        </tr></thead>
                        <tbody class="text-gray-700 fw-semibold">
                          <?php foreach ($transfers as $t): ?>
                          <tr>
                            <td class="text-monospace fs-8"><?php echo html_escape($t['ref']); ?></td>
                            <td><?php echo html_escape(($t['sender_name'] ?? '') ?: ('#'.$t['user_id'])); ?><div class="text-muted fs-8"><?php echo html_escape($t['sender_ref'] ?? ''); ?></div></td>
                            <td>
                              <?php if (!empty($t['to_user_id'])): ?>
                                <?php echo html_escape(($t['recipient_name'] ?? '') ?: ('#'.$t['to_user_id'])); ?><div class="text-muted fs-8"><?php echo html_escape($t['recipient_ref'] ?? ''); ?></div>
                              <?php else: ?><span class="text-muted fs-8">— (self)</span><?php endif; ?>
                            </td>
                            <td><span class="badge badge-light-primary text-uppercase"><?php echo html_escape($t['from_wallet']); ?></span></td>
                            <td class="text-end"><?php echo number_format((float)$t['amount'],4); ?></td>
                            <td><span class="badge badge-light-<?php echo $t['status']==='completed'?'success':($t['status']==='failed'?'danger':'warning'); ?>"><?php echo strtoupper($t['status']); ?></span></td>
                            <td class="text-muted fs-8"><?php echo html_escape($t['created_at']); ?></td>
                            <td class="text-end"><button class="btn btn-sm btn-light-primary it-view" data-ref="<?php echo html_escape($t['ref']); ?>">View</button></td>
                          </tr>
                          <?php endforeach; ?>
                          <?php if (empty($transfers)): ?><tr><td colspan="8" class="text-muted">No transfers found.</td></tr><?php endif; ?>
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
  <script>
  (function(){
    const base='<?php echo base_url(); ?>';
    function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
    document.querySelector('.table').addEventListener('click', async (e)=>{
      const b=e.target.closest('.it-view'); if(!b) return;
      const m=bootstrap.Modal.getOrCreateInstance(document.getElementById('it-modal'));
      const body=document.getElementById('it-body'); body.innerHTML='Loading…'; m.show();
      const r=await fetch(base+'admin/finance/internal-transfers/detail?ref='+encodeURIComponent(b.dataset.ref),{headers:{'X-Requested-With':'XMLHttpRequest'}});
      const j=await r.json(); const d=j.data;
      if(!d||!d.header){ body.innerHTML='<div class="text-muted">Not found.</div>'; return; }
      const h=d.header;
      const led=(d.ledger||[]).map(l=>'<tr><td class="text-uppercase">'+esc(l.wallet_type)+'</td><td class="text-end">'+esc(l.credit>0?('+'+l.credit):('-'+l.debit))+'</td><td class="text-end">'+esc(l.balance_after)+'</td></tr>').join('');
      body.innerHTML='<div class="mb-3"><b>'+esc(h.ref)+'</b> · <span class="badge badge-light-success">'+esc(h.status.toUpperCase())+'</span></div>'+
        '<div class="row fs-7"><div class="col-6 mb-2">From: <b>'+esc(h.from_wallet)+'</b></div><div class="col-6 mb-2">To: <b>'+esc(h.to_wallet)+'</b></div>'+
        '<div class="col-6 mb-2">Amount: <b>'+esc(h.amount)+'</b></div><div class="col-6 mb-2">Created: '+esc(h.created_at)+'</div>'+
        '<div class="col-12 mb-2">IP: '+esc(h.ip_address||'—')+'</div></div>'+
        '<table class="table table-row-dashed fs-7 mt-3"><thead><tr class="fw-bold text-muted"><th>Wallet</th><th class="text-end">Change</th><th class="text-end">Balance After</th></tr></thead><tbody>'+led+'</tbody></table>';
    });
  })();
  </script>
</body>
</html>
