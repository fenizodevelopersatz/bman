<?php
function badge_payment($status){
  $map = ['paid'=>'success','failed'=>'danger','pending'=>'warning','refunded'=>'secondary'];
  $cls = $map[$status] ?? 'secondary';
  return '<span class="badge badge-light-'.$cls.' text-uppercase">'.$status.'</span>';
}

function badge_ship($status){
  $map = [
    'placed'=>'secondary','paid'=>'primary','packed'=>'info',
    'shipped'=>'info','out_for_delivery'=>'warning','delivered'=>'success',
    'cancelled'=>'secondary','refunded'=>'secondary','failed'=>'danger'
  ];
  $cls = $map[$status] ?? 'secondary';
  return '<span class="badge badge-light-'.$cls.' text-uppercase">'.$status.'</span>';
}

function esc($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
