<?php
$inv = $inv ?? null;
$bill = $inv->bill_to ? json_decode($inv->bill_to, true) : null;
$ship = $inv->ship_to ? json_decode($inv->ship_to, true) : null;
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Invoice <?=$inv->invoice_no?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    *{box-sizing:border-box} body{font-family:Arial,Helvetica,sans-serif;color:#222;margin:0;padding:24px}
    .invoice{max-width:900px;margin:0 auto}
    .flex{display:flex;gap:24px}
    .between{justify-content:space-between}
    .card{border:1px solid #eee;border-radius:10px;padding:16px}
    h1{font-size:22px;margin:0} h2{font-size:16px;margin:0 0 8px}
    table{width:100%;border-collapse:collapse;margin-top:12px}
    th,td{border:1px solid #eee;padding:10px;font-size:13px;text-align:left}
    tfoot td{font-weight:bold}
    .right{text-align:right} .muted{color:#666}
    @media print{ .no-print{display:none} body{padding:0} .invoice{margin:0;max-width:100%} }
  </style>
</head>
<body>
<div class="invoice">
  <div class="flex between">
    <div>
      <h1>INVOICE</h1>
      <div class="muted">Invoice No: <?=$inv->invoice_no?></div>
      <div class="muted">Invoice Date: <?=date('Y-m-d H:i', strtotime($inv->invoice_date))?></div>
      <div class="muted">Order Code: <?=$order->order_code?></div>
    </div>
    <div class="right">
      <div><b>Your Company</b></div>
      <div>Address line 1</div>
      <div>City, State ZIP</div>
      <div>Email: support@example.com</div>
    </div>
  </div>

  <div class="flex" style="margin-top:16px">
    <div class="card" style="flex:1">
      <h2>Bill To</h2>
      <div><?=html_escape($bill['name'] ?? ('Customer #'.$order->user_id))?></div>
      <?php if (!empty($bill['email'])): ?><div><?=$bill['email']?></div><?php endif; ?>
    </div>
    <div class="card" style="flex:1">
      <h2>Ship To</h2>
      <?php if($ship): ?>
        <div><?=html_escape($ship['name'] ?? '')?></div>
        <div><?=nl2br(html_escape($ship['address'] ?? ''))?></div>
        <div><?=html_escape(($ship['city'] ?? '').', '.($ship['state'] ?? '').' '.($ship['zip'] ?? ''))?></div>
        <div><?=html_escape($ship['country'] ?? '')?></div>
      <?php else: ?>
        <div class="muted">No shipping address on file</div>
      <?php endif; ?>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:48%">Item</th>
        <th>SKU</th>
        <th class="right">Qty</th>
        <th class="right">Unit</th>
        <th class="right">Tax</th>
        <th class="right">Total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($items as $it): ?>
      <tr>
        <td><?=html_escape($it->name)?></td>
        <td><?=html_escape($it->sku)?></td>
        <td class="right"><?=$it->qty?></td>
        <td class="right"><?=number_format($it->unit_price,2)?></td>
        <td class="right"><?=number_format($it->tax_amount,2)?></td>
        <td class="right"><?=number_format($it->line_total + $it->tax_amount,2)?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr><td colspan="5" class="right">Subtotal</td><td class="right"><?=number_format($inv->subtotal,2)?></td></tr>
      <tr><td colspan="5" class="right">Discount</td><td class="right">-<?=number_format($inv->discount,2)?></td></tr>
      <tr><td colspan="5" class="right">Tax</td><td class="right"><?=number_format($inv->tax,2)?></td></tr>
      <tr><td colspan="5" class="right">Shipping</td><td class="right"><?=number_format($inv->shipping_fee,2)?></td></tr>
      <tr><td colspan="5" class="right">Grand Total (<?=$inv->currency?>)</td><td class="right"><?=number_format($inv->grand_total,2)?></td></tr>
    </tfoot>
  </table>

  <p class="muted" style="margin-top:12px">Payment Status: <b><?=$order->payment_status?></b> via <b><?=$order->payment_method?></b></p>

  <div class="no-print" style="margin-top:16px">
    <button onclick="window.print()">Print/Save PDF</button>
  </div>
</div>
</body>
</html>
