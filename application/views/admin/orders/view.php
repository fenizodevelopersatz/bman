<?php $this->load->view('admin/Layout/common_style'); ?>
<link href="<?=base_url('assets/admin/plugins/custom/datatables/datatables.bundle.css')?>" rel="stylesheet" type="text/css" />
<link href="<?=base_url('assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.css')?>" rel="stylesheet" type="text/css" />
<link href="<?=base_url('assets/admin/plugins/global/plugins.bundle.css')?>" rel="stylesheet" type="text/css" />

 <body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true" data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
    data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">

        <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
            <div class="app-page  flex-column flex-column-fluid " id="kt_app_page">

                <!--  Header   -->
                <?php 
                //************************** SIDE BAR ADMIN PANEL */
                $this->load->view('admin/Layout/admin_topbar');
                //************************** SIDE BAR ADMIN PANEL */
                ?>

                    <!--begin::Wrapper-->
                    <div class="app-wrapper  flex-column flex-row-fluid " id="kt_app_wrapper">

                        <?php $this->load->view('admin/Layout/admin_sidebar');?>

      <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
        <div class="d-flex flex-column flex-column-fluid">
          <!-- Toolbar -->
          <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
              <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 my-0">
                  Order #<?=esc($order->id)?> · <?=esc($order->order_code)?>
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                  <li class="breadcrumb-item text-muted">
                    <a href="<?=base_url('admin/dashboard')?>" class="text-muted text-hover-primary">Dashboard</a>
                  </li>
                  <li class="breadcrumb-item">
                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                  </li>
                  <li class="breadcrumb-item text-muted">
                    <a href="<?=site_url('admin/orders')?>" class="text-muted text-hover-primary">Orders</a>
                  </li>
                  <li class="breadcrumb-item">
                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                  </li>
                  <li class="breadcrumb-item text-gray-900">Detail</li>
                </ul>
              </div>

              <div class="d-flex align-items-center gap-2">
                <a class="btn btn-light-dark" target="_blank" href="<?=site_url('admin/orders/invoice/'.$order->id)?>">
                  <i class="ki-outline ki-printer fs-2 me-1"></i> Invoice
                </a>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#trackingModal">
                  <i class="ki-outline ki-truck fs-2 me-1"></i> Update Tracking
                </button>
              </div>
            </div>
          </div>
          <!-- /Toolbar -->

          <!-- Content -->
          <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">

              <?php $this->load->view('notification'); ?>

              <!-- Summary chips -->
              <div class="row g-5 g-xl-10 mb-7">
                <div class="col-md-4">
                  <div class="card card-flush h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                      <div class="d-flex align-items-center">
                        <i class="ki-outline ki-calendar-8 fs-2 me-2 text-gray-600"></i>
                        <div>
                          <div class="fs-7 text-muted">Placed</div>
                          <div class="fs-5 fw-bold"><?=date('d M Y, H:i', strtotime($order->created_at))?></div>
                        </div>
                      </div>
                      <div class="mt-4">
                        <span class="fs-7 text-muted me-2">Order Code</span>
                        <span class="badge badge-light-primary"><?=esc($order->order_code)?></span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-md-4">
                  <div class="card card-flush h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                      <div class="d-flex align-items-center">
                        <i class="ki-outline ki-wallet fs-2 me-2 text-gray-600"></i>
                        <div>
                          <div class="fs-7 text-muted">Payment</div>
                          <div class="fs-5 fw-bold"><?=ucfirst(esc($order->payment_method))?> · <?=badge_payment($order->payment_status)?></div>
                        </div>
                      </div>
                      <div class="mt-4">
                        <span class="fs-7 text-muted me-2">Total</span>
                        <span class="fs-4 fw-bold">$<?=number_format((float)$order->total_amount,2)?></span>
                      </div>
                    </div>
                  </div>
                </div>

                <?php
                  $latestShip = !empty($shipments) ? $shipments[0] : null;
                  $shipStatus = $latestShip ? $latestShip->status : ($order->payment_status==='paid'?'paid':($order->payment_status==='failed'?'failed':'placed'));
                ?>
                <div class="col-md-4">
                  <div class="card card-flush h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                      <div class="d-flex align-items-center">
                        <i class="ki-outline ki-delivery fs-2 me-2 text-gray-600"></i>
                        <div>
                          <div class="fs-7 text-muted">Fulfillment</div>
                          <div class="fs-5 fw-bold"><?=badge_ship($shipStatus)?></div>
                        </div>
                      </div>
                      <div class="mt-4">
                        <div class="text-muted fs-7">
                          <?php if($latestShip): ?>
                            <?=esc($latestShip->courier_name)?> · <?=esc($latestShip->tracking_number)?>
                            <?php if($latestShip->expected_delivery): ?>
                              <span class="ms-2">· ETA: <?=esc($latestShip->expected_delivery)?></span>
                            <?php endif; ?>
                          <?php else: ?>
                            No shipment created yet.
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Top triple: Order details / Customer / Documents -->
              <div class="d-flex flex-column flex-xl-row gap-7 mb-5">
                <!-- Order details -->
                <div class="card card-flush py-4 flex-row-fluid">
                  <div class="card-header">
                    <div class="card-title"><h2>Order Details (#<?=esc($order->order_code)?>)</h2></div>
                  </div>
                  <div class="card-body pt-0">
                    <div class="table-responsive">
                      <table class="table align-middle table-row-bordered mb-0 fs-6 gy-5 min-w-300px">
                        <tbody class="fw-semibold text-gray-600">
                          <tr>
                            <td class="text-muted"><i class="ki-outline ki-calendar fs-2 me-2"></i>Date Added</td>
                            <td class="fw-bold text-end"><?=date('d/m/Y', strtotime($order->created_at))?></td>
                          </tr>
                          <tr>
                            <td class="text-muted"><i class="ki-outline ki-wallet fs-2 me-2"></i>Payment Method</td>
                            <td class="fw-bold text-end">
                              <?=ucfirst(esc($order->payment_method))?>
                              <?php if (in_array($order->payment_method, ['card','stripe'])): ?>
                                <img src="<?=base_url('assets/admin/media/svg/card-logos/visa.svg')?>" class="w-50px ms-2" alt="card">
                              <?php endif; ?>
                            </td>
                          </tr>
                          <tr>
                            <td class="text-muted"><i class="ki-outline ki-truck fs-2 me-2"></i>Shipping Method</td>
                            <td class="fw-bold text-end">Flat Shipping Rate</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <!-- Customer -->
                <div class="card card-flush py-4 flex-row-fluid">
                  <div class="card-header">
                    <div class="card-title"><h2>Customer Details</h2></div>
                  </div>
                  <div class="card-body pt-0">
                    <div class="table-responsive">
                      <table class="table align-middle table-row-bordered mb-0 fs-6 gy-5 min-w-300px">
                        <tbody class="fw-semibold text-gray-600">
                          <tr>
                            <td class="text-muted"><i class="ki-outline ki-profile-circle fs-2 me-2"></i> Customer</td>
                            <td class="fw-bold text-end">
                              <div class="d-flex align-items-center justify-content-end">
                                <div class="symbol symbol-circle symbol-25px overflow-hidden me-3">
                                  <div class="symbol-label">
                                  </div>
                                </div>
                                <?=esc($user->username ?? ('User #'.$order->user_id))?>
                              </div>
                            </td>
                          </tr>
                          <tr>
                            <td class="text-muted"><i class="ki-outline ki-sms fs-2 me-2"></i> Email</td>
                            <td class="fw-bold text-end"><?=esc($user->email ?? '-')?></td>
                          </tr>
                          <tr>
                            <td class="text-muted"><i class="ki-outline ki-phone fs-2 me-2"></i> Phone</td>
                            <td class="fw-bold text-end"><?=esc($shipping->phone ?? '-')?></td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <!-- Documents -->
                <div class="card card-flush py-4 flex-row-fluid">
                  <div class="card-header">
                    <div class="card-title"><h2>Documents</h2></div>
                  </div>
                  <div class="card-body pt-0">
                    <div class="table-responsive">
                      <table class="table align-middle table-row-bordered mb-0 fs-6 gy-5 min-w-300px">
                        <tbody class="fw-semibold text-gray-600">
                          <tr>
                            <td class="text-muted">
                              <i class="ki-outline ki-devices fs-2 me-2"></i> Invoice
                              <span class="ms-1" data-bs-toggle="tooltip" title="View the invoice generated by this order.">
                                <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                              </span>
                            </td>
                            <td class="fw-bold text-end">
                              <a href="<?=site_url('admin/orders/invoice/'.$order->id)?>" target="_blank" class="text-gray-600 text-hover-primary">
                                #INV-<?=str_pad((string)$order->id, 6, '0', STR_PAD_LEFT)?>
                              </a>
                            </td>
                          </tr>
                          <tr>
                            <td class="text-muted"><i class="ki-outline ki-truck fs-2 me-2"></i> Shipping</td>
                            <td class="fw-bold text-end">
                              <?php if($latestShip && $latestShip->tracking_number): ?>
                                <?=esc($latestShip->courier_name)?> · <?=esc($latestShip->tracking_number)?>
                              <?php else: ?>
                                Not assigned
                              <?php endif; ?>
                            </td>
                          </tr>
                          <tr>
                            <td class="text-muted"><i class="ki-outline ki-discount fs-2 me-2"></i> Reward Points</td>
                            <td class="fw-bold text-end"><?=number_format((float)($order->commission_amount ?? 0), 2)?></td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Addresses -->
              <div class="d-flex flex-column flex-xl-row gap-7 gap-lg-10  mb-5">
                <div class="card card-flush py-4 flex-row-fluid position-relative">
                  <div class="position-absolute top-0 end-0 bottom-0 opacity-10 d-flex align-items-center me-5">
                    <i class="ki-solid ki-two-credit-cart" style="font-size: 14em"></i>
                  </div>
                  <div class="card-header"><div class="card-title"><h2>Billing Address</h2></div></div>
                  <div class="card-body pt-0">
                    <?=esc(($shipping->first_name ?? '').' '.($shipping->last_name ?? ''))?><br>
                    <?=nl2br(esc($shipping->address ?? ''))?><br>
                    <?=esc(($shipping->city ?? '').' '.($shipping->postal_code ?? ''))?><br>
                    <?=esc(($shipping->state ?? '').', '.($shipping->country ?? ''))?>
                  </div>
                </div>

                <div class="card card-flush py-4 flex-row-fluid position-relative">
                  <div class="position-absolute top-0 end-0 bottom-0 opacity-10 d-flex align-items-center me-5">
                    <i class="ki-solid ki-delivery" style="font-size: 13em"></i>
                  </div>
                  <div class="card-header"><div class="card-title"><h2>Shipping Address</h2></div></div>
                  <div class="card-body pt-0">
                    <?=esc(($shipping->first_name ?? '').' '.($shipping->last_name ?? ''))?><br>
                    <?=nl2br(esc($shipping->address ?? ''))?><br>
                    <?=esc(($shipping->city ?? '').' '.($shipping->postal_code ?? ''))?><br>
                    <?=esc(($shipping->state ?? '').', '.($shipping->country ?? ''))?>
                  </div>
                </div>
              </div>

              <!-- Items + Shipment + History -->
              <div class="row g-7 mt-1">
                <!-- Items -->
                <div class="col-xl-8">
                  <div class="card card-flush py-4">
                    <div class="card-header"><div class="card-title"><h2>Order Items</h2></div></div>
                    <div class="card-body pt-0">
                      <div class="table-responsive">
                        <table id="kt_items" class="table align-middle table-row-dashed fs-6 gy-5">
                          <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                              <th class="min-w-175px">Product</th>
                              <th class="min-w-100px text-end">SKU</th>
                              <th class="min-w-70px text-end">Qty</th>
                              <th class="min-w-100px text-end">Unit Price</th>
                              <th class="min-w-100px text-end">Total</th>
                            </tr>
                          </thead>
                          <tbody class="fw-semibold text-gray-600">
                            <?php $subtotal=0.0; foreach($order_items as $item): 
                                  $img = !empty($item->product_image) ? base_url('assets/images/'.$item->product_image) : base_url('assets/images/placeholder.png');
                                  $total = (float)$item->price * (int)$item->quantity; $subtotal+=$total; ?>
                              <tr>
                                <td>
                                  <div class="d-flex align-items-center">
                                    <a class="symbol symbol-50px">
                                      <span class="symbol-label" style="background-image:url('<?=esc($img)?>');"></span>
                                    </a>
                                    <div class="ms-5">
                                      <span class="fw-bold text-gray-700"><?=esc($item->name ?? $item->product_name)?></span>
                                      <div class="fs-7 text-muted">Added: <?=date('d/m/Y', strtotime($order->created_at))?></div>
                                    </div>
                                  </div>
                                </td>
                                <td class="text-end"><?=esc($item->sku)?></td>
                                <td class="text-end"><?=number_format((int)$item->quantity)?></td>
                                <td class="text-end">$<?=number_format((float)$item->price,2)?></td>
                                <td class="text-end">$<?=number_format($total,2)?></td>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                          <tfoot>
                            <tr>
                              <td colspan="4" class="text-end fw-bold">Subtotal</td>
                              <td class="text-end fw-bold">$<?=number_format($subtotal,2)?></td>
                            </tr>
                            <?php
                              // Example VAT & shipping: adjust with your own fields if stored on order/invoice
                              $vat = round($subtotal*0.20,2);
                              $shipping_rate = (float)($shipping->shipping_rate ?? 0);
                              $grand = (float)$order->total_amount;
                            ?>
                            <tr>
                              <td colspan="4" class="text-end">VAT (20%)</td>
                              <td class="text-end">$<?=number_format($vat,2)?></td>
                            </tr>
                            <tr>
                              <td colspan="4" class="text-end">Shipping Rate</td>
                              <td class="text-end">$<?=number_format($shipping_rate,2)?></td>
                            </tr>
                            <tr>
                              <td colspan="4" class="fs-4 text-gray-900 text-end">Grand Total</td>
                              <td class="text-end fs-4 fw-bolder">$<?=number_format($grand,2)?></td>
                            </tr>
                          </tfoot>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Shipment + History -->
                <div class="col-xl-4">
                  <!-- Shipment Stepper -->
                  <div class="card card-flush py-4 mb-7">
                    <div class="card-header"><div class="card-title"><h2>Shipment</h2></div></div>
                    <div class="card-body pt-0">
                      <?php
                        $steps = ['placed','paid','packed','shipped','out_for_delivery','delivered'];
                        $activeIdx = array_search($shipStatus, $steps); if($activeIdx===false) $activeIdx=0;
                      ?>
                      <div class="stepper stepper-vertical" id="kt_stepper">
                        <div class="stepper-nav">
                          <?php foreach($steps as $i=>$st): ?>
                            <div class="stepper-item <?=$i<=$activeIdx?'current':''?>">
                              <div class="stepper-wrapper">
                                <div class="stepper-icon w-40px h-40px">
                                  <i class="ki-duotone ki-check fs-2"></i>
                                </div>
                                <div class="stepper-label">
                                  <h3 class="stepper-title text-capitalize"><?=esc($st)?></h3>
                                  <div class="stepper-desc text-muted">
                                    <?php if($latestShip && $latestShip->status===$st): ?>
                                      <?=esc($latestShip->remarks ?? '')?>
                                    <?php endif; ?>
                                  </div>
                                </div>
                              </div>
                              <div class="stepper-line h-40px"></div>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      </div>

                      <div class="border rounded p-4 mt-5 bg-light">
                        <?php if($latestShip): ?>
                          <div class="fw-semibold mb-1"><?=badge_ship($latestShip->status)?></div>
                          <div class="text-gray-700">
                            Courier: <b><?=esc($latestShip->courier_name ?? '-')?></b><br>
                            Tracking: <b><?=esc($latestShip->tracking_number ?? '-')?></b><br>
                            ETA: <b><?=esc($latestShip->expected_delivery ?? '-')?></b>
                          </div>
                        <?php else: ?>
                          <em>No shipment yet. Click “Update Tracking”.</em>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>

                  <!-- History -->
                  <div class="card card-flush py-4">
                    <div class="card-header"><div class="card-title"><h2>Status History</h2></div></div>
                    <div class="card-body pt-0">
                      <?php if(!empty($history)): ?>
                        <div class="timeline">
                          <?php foreach($history as $h): ?>
                            <div class="timeline-item">
                              <div class="timeline-line"></div>
                              <div class="timeline-icon"><i class="ki-outline ki-time fs-2 text-gray-600"></i></div>
                              <div class="timeline-content mb-10">
                                <div class="overflow-auto pe-3">
                                  <div class="fs-6 d-flex align-items-center">
                                    <span class="fw-bold me-2 text-capitalize"><?=esc($h->status)?></span>
                                    <span class="text-muted fs-7"><?=date('d M Y, H:i', strtotime($h->created_at))?></span>
                                  </div>
                                  <div class="fs-7 text-gray-700"><?=esc($h->note ?? '')?></div>
                                </div>
                              </div>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      <?php else: ?>
                        <em>No history yet.</em>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
          <!-- /Content -->
        </div>

        <?php $this->load->view('admin/Layout/admin_footer'); ?>

      </div>
    </div>
  </div>
</div>

<!-- Scrolltop -->
<div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
  <i class="ki-duotone ki-arrow-up"><span class="path1"></span><span class="path2"></span></i>
</div>

<!-- Tracking Modal -->
<div class="modal fade" id="trackingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="trackingForm" autocomplete="off">
      <div class="modal-header">
        <h5 class="modal-title">Update Tracking</h5>
        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
          <i class="ki-duotone ki-cross fs-2"></i>
        </button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="order_id" value="<?=esc($order->id)?>">
        <div class="mb-5">
          <label class="form-label required">Status</label>
          <select name="status" class="form-select" required>
            <?php $statuses = ['placed','paid','packed','shipped','out_for_delivery','delivered','cancelled','refunded','failed'];
              foreach($statuses as $st) echo '<option value="'.esc($st).'">'.esc($st).'</option>'; ?>
          </select>
        </div>
        <div class="row g-5">
          <div class="col-md-6">
            <label class="form-label">Courier</label>
            <input name="courier_name" class="form-control" placeholder="e.g., DHL">
          </div>
          <div class="col-md-6">
            <label class="form-label">Tracking Number</label>
            <input name="tracking_number" class="form-control" placeholder="ABC123456">
          </div>
        </div>
        <div class="row g-5 mt-1">
          <div class="col-md-6">
            <label class="form-label">Expected Delivery</label>
            <input type="date" name="expected_delivery" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Remarks</label>
            <input name="remarks" class="form-control" placeholder="Optional note">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" data-bs-dismiss="modal" type="button">Close</button>
        <button class="btn btn-primary" type="submit">
          <span class="indicator-label">Save</span>
          <span class="indicator-progress">Saving...
            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
          </span>
        </button>
      </div>
    </form>
  </div>
</div>

<?php $this->load->view('admin/Layout/common_script'); ?>
<script src="<?=base_url('assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.js')?>"></script>
<script src="<?=base_url('assets/admin/js/widgets.bundle.js')?>"></script>
<script src="<?=base_url('assets/admin/js/custom/widgets.js')?>"></script>
<script src="<?=base_url('assets/admin/plugins/global/plugins.bundle.js')?>"></script>
<script src="<?=base_url('assets/admin/plugins/custom/datatables/datatables.bundle.js')?>"></script>
<link href="<?=base_url('assets/admin/plugins/custom/fullcalendar/fullcalendar.bundle.css')?>" rel="stylesheet" type="text/css" />
<script src="<?=base_url('assets/admin/plugins/custom/fullcalendar/fullcalendar.bundle.js')?>"></script>

<script>
  // DataTables for Items
  const itemsTable = document.getElementById('kt_items');
  if (itemsTable) {
    $(itemsTable).DataTable({
      responsive: true,
      paging: false,
      searching: false,
      info: false,
      ordering: false,
      dom: 'Bfrtip',
      buttons: [
        { extend: 'print', title: 'Order <?=esc($order->order_code)?> - Items' },
        { extend: 'excel', title: 'order_<?=esc($order->order_code)?>_items' }
      ]
    });
  }

  // Tracking form submit with Metronic indicators + toast
  (function(){
    const form = document.getElementById('trackingForm');
    if(!form) return;
    form.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const btn = form.querySelector('button[type="submit"]');
      btn.setAttribute('data-kt-indicator','on');
      btn.disabled = true;

      const fd = new FormData(form);
      // If you use CSRF in CI, append here:
      // fd.append('<?=$this->security->get_csrf_token_name()?>','<?=$this->security->get_csrf_hash()?>');

      try{
        const res = await fetch('<?=site_url('admin/orders/update_tracking')?>', { method:'POST', body: fd });
        const json = await res.json();

        if(json.status==='success'){
          toastr.success('Tracking updated');
          setTimeout(()=>location.reload(), 700);
        }else{
          toastr.error(json.message || 'Failed to update');
        }
      }catch(err){
        toastr.error('Network error');
      }finally{
        btn.removeAttribute('data-kt-indicator');
        btn.disabled = false;
      }
    }, false);
  })();
</script>

</body>
</html>
