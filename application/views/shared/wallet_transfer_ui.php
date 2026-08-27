<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Shared Wallet Transfer UI partial — included by BOTH panels.
 *   User:  $this->load->view('shared/wallet_transfer_ui', ['wtx_panel'=>'user',
 *            'wtx_preview'=>'user/transfer_wallet/preview',
 *            'wtx_detail'=>'user/transfer_wallet/tx_detail']);
 *   Admin: $this->load->view('shared/wallet_transfer_ui', ['wtx_panel'=>'admin',
 *            'wtx_preview'=>'admin/finance/internal-transfers/preview',
 *            'wtx_detail'=>'admin/finance/internal-transfers/tx-detail']);
 * The confirmation dialog + transaction-details modal markup and CSS are injected
 * by the script itself, so both panels render a pixel-identical shared UI.
 */
$wtx_panel   = isset($wtx_panel)   ? $wtx_panel   : 'user';
$wtx_preview = isset($wtx_preview) ? $wtx_preview : '';
$wtx_detail  = isset($wtx_detail)  ? $wtx_detail  : '';
?>
<script src="<?= base_url('assets/js/wallet_transfer_ui.js') ?>?v=2"></script>
<script>
(function () {
  function boot() {
    if (!window.WalletTransferUI) return;
    WalletTransferUI.init({
      panel:      <?= json_encode($wtx_panel) ?>,
      baseUrl:    <?= json_encode(base_url()) ?>,
      previewUrl: <?= json_encode($wtx_preview) ?>,
      detailUrl:  <?= json_encode($wtx_detail) ?>,
      csrfName:   <?= json_encode($this->security->get_csrf_token_name()) ?>,
      csrfHash:   <?= json_encode($this->security->get_csrf_hash()) ?>,
      explorerTxUrl: 'https://bscscan.com/tx/'
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
</script>
