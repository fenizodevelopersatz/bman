<?php $this->load->view('admin/Layout/common_style');?>

    <link href="<?php echo base_url();?>/assets/admin/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css">
    <link href="<?php echo base_url();?>/assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.css" rel="stylesheet" type="text/css">
    <link href="<?php echo base_url();?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />

    <style>
        .h-md-40{
            min-height:42%;
        }
        .verify-symbol {
        color: green;
        font-weight: bold;
        margin-left: 5px;
        }
        .verified {
        border-color: green;
        }
    </style>
<link rel="stylesheet" href="<?php echo base_url('assets/admin/plugins/custom/datatables/datatables.bundle.css'); ?>">

    <body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true" data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
    data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">

<?php $this->load->view('admin/Layout/admin_topbar'); ?>
<div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
<?php $this->load->view('admin/Layout/admin_sidebar'); ?>
<div class="app-main flex-column flex-row-fluid" id="kt_app_main">
<div class="container-xxl py-6">

<h2 class="mb-6"><?php echo $card_title; ?></h2>

<div class="card">
  <div class="card-header">
    <ul class="nav nav-tabs card-header-tabs" role="tablist">
      <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#live" role="tab">Live (from DB)</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#whatif" role="tab">What-If</a></li>
    </ul>
  </div>

  <div class="card-body tab-content">

    <!-- LIVE TAB -->
    <div class="tab-pane fade show active" id="live" role="tabpanel">
      <form id="live-form" class="row g-3">
        <div class="col-md-3">
          <label class="form-label">User ID</label>
          <input type="number" class="form-control" name="user_id" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">From</label>
          <input type="date" class="form-control" name="from" value="<?php echo date('Y-m-01'); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">To</label>
          <input type="date" class="form-control" name="to" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100">Calculate</button>
        </div>
      </form>
      <hr>
      <div id="live-result"></div>
    </div>

    <!-- WHAT-IF TAB -->
    <div class="tab-pane fade" id="whatif" role="tabpanel">
      <form id="whatif-form" class="row g-3">
        <div class="col-md-2">
          <label class="form-label">Basis</label>
          <select class="form-select" name="basis">
            <option value="PV" <?php echo ($config->binary_pair_on==='PV'?'selected':'');?>>PV</option>
            <option value="BV" <?php echo ($config->binary_pair_on==='BV'?'selected':'');?>>BV</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Ratio</label>
          <input type="text" class="form-control" name="ratio" value="<?php echo $config->binary_pair_ratio ?: '1:1'; ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Type</label>
          <select class="form-select" name="pair_type">
            <option value="percent" <?php echo ($config->binary_pair_type==='percent'?'selected':'');?>>Percent</option>
            <option value="amount"  <?php echo ($config->binary_pair_type==='amount'?'selected':'');?>>Fixed / Pair</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">% (if percent)</label>
          <input type="number" step="0.01" class="form-control" name="pair_percent" value="<?php echo (float)$config->binary_pair_percent; ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Amt/Pair (if amount)</label>
          <input type="number" step="0.01" class="form-control" name="pair_amount" value="<?php echo (float)$config->binary_pair_amount; ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Carry</label>
          <select class="form-select" name="carry_mode">
            <option value="none">none</option>
            <option value="weak_leg" <?php echo ($config->binary_carry_forward==='weak_leg'?'selected':'');?>>weak_leg</option>
            <option value="both" <?php echo ($config->binary_carry_forward==='both'?'selected':'');?>>both</option>
          </select>
        </div>

        <div class="col-md-12">
          <label class="form-label">Left Volumes by Day (CSV)</label>
          <input type="text" class="form-control" name="left_vols" placeholder="e.g. 100,0,50">
        </div>
        <div class="col-md-12">
          <label class="form-label">Right Volumes by Day (CSV)</label>
          <input type="text" class="form-control" name="right_vols" placeholder="e.g. 100,50,20">
        </div>

        <div class="col-md-12">
          <label class="form-label">Matching: Percents per Level (CSV)</label>
          <input type="text" class="form-control" name="matching_percents" value="<?php echo html_escape($config->matching_percents); ?>">
          <small class="text-muted">Optional: You may supply child binary tokens per level to estimate matching (fields: child_binary_tokens_L1, L2, ...)</small>
        </div>

        <div class="col-md-12 d-flex justify-content-end">
          <button type="submit" class="btn btn-success">Simulate</button>
        </div>
      </form>
      <hr>
      <div id="whatif-result"></div>
    </div>

  </div>
</div>

</div>
<?php $this->load->view('admin/Layout/admin_footer'); ?>

<script src="<?php echo base_url('assets/admin/plugins/global/plugins.bundle.js'); ?>"></script>
<script>
const ADMIN_LIVE_URL   = "<?php echo site_url('admin/commission-calculator/live');?>";
const ADMIN_WHATIF_URL = "<?php echo site_url('admin/commission-calculator/whatif');?>";

function renderLive(out){
  let html = '';
  if(!out.status){ html = '<div class="alert alert-danger">'+out.message+'</div>'; $('#live-result').html(html); return; }

  const d = out.data;
  html += '<div class="alert alert-info">Basis: <b>'+d.basis+'</b> | Range: '+d.range.from+' → '+d.range.to+'</div>';

  // Summary
  html += '<h5>Summary by Type</h5><ul>';
  (d.summary||[]).forEach(r=>{
    html += '<li><b>'+r.type+'</b>: USD '+r.usd_total+' / Token '+r.token_total+' ('+r.rows_count+' rows)</li>';
  });
  html += '</ul>';

  // Binary inputs
  html += '<h6>Binary Inputs ('+d.basis+')</h6><table class="table table-sm"><thead><tr><th>Day</th><th>Left</th><th>Right</th></tr></thead><tbody>';
  (d.binary_inputs.days||[]).forEach(day=>{
    html += '<tr><td>'+day+'</td><td>'+(d.binary_inputs.left[day]||0)+'</td><td>'+(d.binary_inputs.right[day]||0)+'</td></tr>';
  });
  html += '</tbody></table>';

  // Details
  ['direct_commission','level_commission','binary_commission','matching_bonus'].forEach(t=>{
    const rows = (d.details && d.details[t]) ? d.details[t] : [];
    html += '<h6 class="mt-4">'+t.replace('_',' ').toUpperCase()+'</h6>';
    if(rows.length===0){ html+='<div class="text-muted">No rows</div>'; return; }
    html += '<table class="table table-bordered table-sm"><thead><tr><th>Date</th><th>USD</th><th>Token</th><th>Description</th><th>Meta</th></tr></thead><tbody>';
    rows.forEach(r=>{
      let meta = [];
      if(r.pair_ratio_used) meta.push('ratio '+r.pair_ratio_used);
      if(r.pairs_count)     meta.push('pairs '+r.pairs_count);
      if(r.basis)           meta.push('basis '+r.basis);
      if(r.level_no)        meta.push('L'+r.level_no);
      if(r.source_user_id)  meta.push('from #'+r.source_user_id);
      html += '<tr><td>'+r.history_date+'</td><td>'+r.usd+'</td><td>'+r.tokens+'</td><td>'+ (r.description||'') +'</td><td>'+meta.join(' | ')+'</td></tr>';
    });
    html += '</tbody></table>';
  });

  html += '<div class="alert alert-success mt-3"><b>Totals:</b> USD '+d.totals.usd+' | Token '+d.totals.token+'</div>';
  $('#live-result').html(html);
}

function renderWhatIf(out){
  let html = '';
  if(!out.status){ html = '<div class="alert alert-danger">'+out.message+'</div>'; $('#whatif-result').html(html); return; }
  const d = out.data;

  html += '<div class="alert alert-info">Basis: <b>'+d.basis+'</b> | Ratio: '+d.ratio+' | Type: '+d.pair_type+' | Token Rate: '+d.token_rate+'</div>';
  html += '<table class="table table-striped table-sm"><thead><tr><th>Day</th><th>Left</th><th>Right</th><th>Pairs</th><th>Token</th><th>USD</th></tr></thead><tbody>';
  (d.binary_daily||[]).forEach(r=>{
    html += '<tr><td>'+r.day+'</td><td>'+r.left+'</td><td>'+r.right+'</td><td>'+r.pairs+'</td><td>'+r.token+'</td><td>'+r.usd+'</td></tr>';
  });
  html += '</tbody></table>';

  html += '<div class="alert alert-success"><b>Binary Totals:</b> Pairs '+d.binary_totals.pairs+' | Token '+d.binary_totals.token+' | USD '+d.binary_totals.usd+'</div>';

  if((d.matching||[]).length){
    html += '<h6>Matching (What-If)</h6><ul>';
    d.matching.forEach(m=>{
      html += '<li>L'+m.level+': '+m.percent+'% of tokens '+m.child_tokens+' = '+m.bonus_token+' tokens (~USD '+m.bonus_usd+')</li>';
    });
    html += '</ul><div class="alert alert-primary">Matching Total USD: '+d.matching_total_usd+'</div>';
  }

  $('#whatif-result').html(html);
}

$('#live-form').on('submit', function(e){
  e.preventDefault();
  $.post(ADMIN_LIVE_URL, $(this).serialize(), renderLive, 'json');
});
$('#whatif-form').on('submit', function(e){
  e.preventDefault();
  $.post(ADMIN_WHATIF_URL, $(this).serialize(), renderWhatIf, 'json');
});
</script>
</body>
</html>
