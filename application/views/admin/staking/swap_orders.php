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
                  <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0"><?php echo $title; ?></h1>
                  <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">Staking</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">Swap Orders</li>
                  </ul>
                </div>
              </div>
            </div>
            <div id="kt_app_content" class="app-content flex-column-fluid mt-8">
              <div id="kt_app_content_container" class="app-container container-xxl">
                <?php $this->load->view('notification'); ?>

                <?php
                  $mode = ((int)$cfg['swap_enabled']===1)
                    ? ((int)$cfg['swap_dry_run']===1 ? ['warning','DRY-RUN — on-chain USDT is simulated'] : ['success','LIVE — real on-chain USDT'])
                    : ['secondary','OFF — package purchase uses the internal flow'];
                ?>
                <div class="alert alert-<?php echo $mode[0]; ?> d-flex align-items-center mb-6">
                  <i class="ki-outline ki-information fs-2 me-3"></i>
                  <div class="fw-semibold">Swap mode: <b><?php echo $mode[1]; ?></b> ·
                    Auto-gas: <b><?php echo ((int)$cfg['swap_auto_gas']===1)?'ON':'OFF'; ?></b></div>
                </div>

                <div class="card">
                  <div class="card-header pt-6"><h3 class="card-title fw-bold">Orders (latest 300)</h3></div>
                  <div class="card-body pt-3 pb-9">
                    <div class="table-responsive">
                      <table class="table align-middle table-row-dashed fs-7 gy-4">
                        <thead><tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                          <th>Ref</th><th>User</th><th class="text-end">USDT</th><th class="text-end">BMAN</th>
                          <th class="text-end">Bonus</th><th>Status</th><th>Tx (gas / usdt / bman)</th><th>Created</th><th></th>
                        </tr></thead>
                        <tbody class="text-gray-700 fw-semibold">
                        <?php foreach ($orders as $o):
                          $st = $o['status'];
                          $badge = strpos($st,'failed')===0 ? 'danger' : ($st==='completed'?'success':'warning');
                          $canRetry = in_array($st, ['created','failed_gas','failed_usdt','failed_credit','usdt_sent'], true);
                          $shortTx = function($h){ return $h ? (strpos($h,'DRYRUN')===0 ? 'dry' : substr($h,0,10).'…') : '—'; };
                        ?>
                          <tr>
                            <td class="text-monospace fs-8"><?php echo html_escape($o['ref']); ?><?php if((int)$o['dry_run']===1): ?> <span class="badge badge-light-warning">dry</span><?php endif; ?></td>
                            <td><?php echo html_escape(($o['username']??'')?:('#'.$o['user_id'])); ?><div class="text-muted fs-8"><?php echo html_escape($o['referral_id']??''); ?></div></td>
                            <td class="text-end"><?php echo number_format((float)$o['usdt_amount'],4); ?></td>
                            <td class="text-end"><?php echo number_format((float)$o['bman_amount']); ?></td>
                            <td class="text-end"><?php echo number_format((float)$o['bonus_bman']); ?></td>
                            <td><span class="badge badge-light-<?php echo $badge; ?>"><?php echo strtoupper($st); ?></span>
                              <?php if(!empty($o['error'])): ?><div class="text-danger fs-8" title="<?php echo html_escape($o['error']); ?>"><?php echo html_escape(substr($o['error'],0,40)); ?></div><?php endif; ?></td>
                            <td class="fs-8 text-muted"><?php echo $shortTx($o['gas_tx_hash']); ?> / <?php echo $shortTx($o['usdt_tx_hash']); ?> / <?php echo $shortTx($o['bman_tx_hash']); ?></td>
                            <td class="fs-8 text-muted"><?php echo html_escape($o['created_at']); ?></td>
                            <td class="text-end">
                              <?php if($canRetry && $st!=='completed'): ?>
                                <button class="btn btn-sm btn-light-primary so-retry" data-id="<?php echo (int)$o['id']; ?>">Retry</button>
                              <?php endif; ?>
                              <?php if($st==='completed' && (float)$o['bman_amount']>0 && empty($o['bman_tx_hash'])): ?>
                                <button class="btn btn-sm btn-light-success so-deliver" data-id="<?php echo (int)$o['id']; ?>">Send BMAN</button>
                              <?php endif; ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                        <?php if(empty($orders)): ?><tr><td colspan="9" class="text-muted">No swap orders yet.</td></tr><?php endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
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
    document.querySelector('.table').addEventListener('click', async (e)=>{
      const b=e.target.closest('.so-retry, .so-deliver'); if(!b) return;
      const isDeliver=b.classList.contains('so-deliver');
      const url=isDeliver ? 'admin/staking/swap-orders/deliver/' : 'admin/staking/swap-orders/retry/';
      const label=b.textContent; b.disabled=true; b.textContent='…';
      try{
        const r=await fetch(base+url+b.dataset.id,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'}});
        const j=await r.json();
        if(window.Swal){ Swal.fire({icon:j.status==='success'?'success':'error',text:j.message,confirmButtonText:'Ok'}).then(()=>location.reload()); }
        else { alert(j.message); location.reload(); }
      }catch(_){ b.disabled=false; b.textContent=label; alert('Request failed.'); }
    });
  })();
  </script>
</body>
</html>
