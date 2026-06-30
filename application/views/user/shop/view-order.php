<?php $this->load->view('user/layout/user_style');?>
    <!--end::Head-->

    <!--begin::Body-->

    <style>
        .expanded-content {
            padding: 15px;
            background: #f9f9f9;
            border-top: 1px solid #e0e0e0;
            margin-top: 10px;
        }
    </style>
    <body id="kt_app_body" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-push-toolbar="true" data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">

        <!--begin::App-->
        <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
            <div class="app-page  flex-column flex-column-fluid " id="kt_app_page">

                <div id="kt_app_header" class="app-header " data-kt-sticky="true" data-kt-sticky-activate-="true" data-kt-sticky-name="app-header-sticky" data-kt-sticky-offset="{default: '200px', lg: '300px'}">
                    <div class="app-container  container-xxl d-flex align-items-stretch justify-content-between " id="kt_app_header_container">
                        <div class="app-header-wrapper d-flex flex-grow-1 align-items-stretch justify-content-between" id="kt_app_header_wrapper">
                            <?php $this->load->view('user/layout/user_header'); ?>
                        </div>
                    </div>
                </div>
                <!--end::Header-->

                <!--begin::Wrapper-->
                <div class="app-wrapper  flex-column flex-row-fluid " id="kt_app_wrapper">

                    <!--begin::Wrapper container-->
                    <div class="app-container  container-xxl d-flex flex-row-fluid ">

                        <!--begin::Sidebar-->
                      <?php $this->load->view('user/layout/user_sidebar');?>
                        <!--end::Sidebar-->


                        <!--begin::Main-->
                        <div class="app-main flex-column flex-row-fluid " id="kt_app_main">
                            <!--begin::Content wrapper-->
                            <div class="d-flex flex-column flex-column-fluid">

                                <!--begin::Toolbar-->
                                <div id="kt_app_toolbar" class="app-toolbar  d-flex pb-3 pb-lg-5 ">

                                    <!--begin::Toolbar container-->
                                    <div class="d-flex flex-stack flex-row-fluid">
                                        <!--begin::Toolbar container-->
                                        <div class="d-flex flex-column flex-row-fluid">
                                            <!--begin::Toolbar wrapper-->

                                            <!--begin::Page title-->
                                            <div class="page-title d-flex align-items-center me-3">
                                                <!--begin::Title-->
                                                <h1 class="page-heading d-flex flex-column justify-content-center text-gray-900 fw-bold fs-lg-2x gap-2">
                                                     <span> <?php echo $title; ?></span>

                                                        <!--begin::Description-->
                                                <span class="page-desc text-gray-600 fs-base fw-semibold">
                                                  <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                                    <li class="breadcrumb-item text-muted">
                                                        <a href="<?php echo base_url();?>" class="text-muted text-hover-primary">
                                                        <?php echo lang('dashboard'); ?>                         
                                                        </a>
                                                    </li>
                                                    <li class="breadcrumb-item">
                                                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                                    </li>
                                                    <li class="breadcrumb-item text-muted">
                                                    <?php echo $title; ?> </li>
                                                </ul>      
                                                </span>
                                                <!--end::Description-->
                                                </h1>
                                                                                    <!--end::Title-->
                                            </div>
                                            <!--end::Page title-->

                                        </div>
                                        <!--end::Toolbar container-->
                                    </div>
                                    <!--end::Toolbar container-->
                                </div>
                                <!--end::Toolbar-->



                                <div id="kt_app_content" class="app-content  flex-column-fluid ">


                                <div class="d-flex flex-column flex-xl-row gap-7">

                                        <!--begin::Order details-->
                                        <div class="card card-flush py-4 flex-row-fluid">
                                            <div class="card-header">
                                                <div class="card-title">
                                                    <h2>Order Details (#<?= $order->order_code ?>)</h2>
                                                </div>
                                            </div>
                                            <div class="card-body pt-0">
                                                <div class="table-responsive">
                                                    <table class="table align-middle table-row-bordered mb-0 fs-6 gy-5 min-w-300px">
                                                        <tbody class="fw-semibold text-gray-600">
                                                            <tr>
                                                                <td class="text-muted">
                                                                    <div class="d-flex align-items-center">
                                                                        <i class="ki-outline ki-calendar fs-2 me-2"></i> Date Added
                                                                    </div>
                                                                </td>
                                                                <td class="fw-bold text-end"><?= date('d/m/Y', strtotime($order->created_at)) ?></td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-muted">
                                                                    <div class="d-flex align-items-center">
                                                                        <i class="ki-outline ki-wallet fs-2 me-2"></i> Payment Method
                                                                    </div>
                                                                </td>
                                                                <td class="fw-bold text-end">
                                                                    <?= ucfirst($order->payment_method) ?>
                                                                    <?php if ($order->payment_method === 'card' || $order->payment_method === 'stripe'): ?>
                                                                        <img src="<?= base_url('assets/admin//media/svg/card-logos/visa.svg') ?>" class="w-50px ms-2">
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-muted">
                                                                    <div class="d-flex align-items-center">
                                                                        <i class="ki-outline ki-truck fs-2 me-2"></i> Shipping Method
                                                                    </div>
                                                                </td>
                                                                <td class="fw-bold text-end">Flat Shipping Rate</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <!--end::Order details-->

                                        

                                        <!--begin::Customer details-->
                                        <div class="card card-flush py-4 flex-row-fluid">
                                            <div class="card-header">
                                                <div class="card-title">
                                                    <h2>Customer Details</h2>
                                                </div>
                                            </div>
                                            <div class="card-body pt-0">
                                                <div class="table-responsive">
                                                    <table class="table align-middle table-row-bordered mb-0 fs-6 gy-5 min-w-300px">
                                                        <tbody class="fw-semibold text-gray-600">
                                                            <tr>
                                                                <td class="text-muted">
                                                                    <div class="d-flex align-items-center">
                                                                        <i class="ki-outline ki-profile-circle fs-2 me-2"></i> Customer
                                                                    </div>
                                                                </td>
                                                                <td class="fw-bold text-end">
                                                                    <div class="d-flex align-items-center justify-content-end">
                                                                        <div class="symbol symbol-circle symbol-25px overflow-hidden me-3">
                                                                            <div class="symbol-label">
                                                                                <img src="<?= base_url('assets/images/default-avatar.jpg') ?>" alt="Customer" class="w-100">
                                                                            </div>
                                                                        </div>
                                                                        <?= $user->name ?>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-muted">
                                                                    <div class="d-flex align-items-center">
                                                                        <i class="ki-outline ki-sms fs-2 me-2"></i> Email
                                                                    </div>
                                                                </td>
                                                                <td class="fw-bold text-end"><?= $user->email ?></td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-muted">
                                                                    <div class="d-flex align-items-center">
                                                                        <i class="ki-outline ki-phone fs-2 me-2"></i> Phone
                                                                    </div>
                                                                </td>
                                                                <td class="fw-bold text-end"><?= $shipping->phone ?></td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <!--end::Customer details-->

                                        <!--begin::Documents-->
                                        <div class="card card-flush py-4 flex-row-fluid">
                                            <div class="card-header">
                                                <div class="card-title">
                                                    <h2>Documents</h2>
                                                </div>
                                            </div>
                                            <div class="card-body pt-0">
                                                <div class="table-responsive">
                                                    <table class="table align-middle table-row-bordered mb-0 fs-6 gy-5 min-w-300px">
                                                        <tbody class="fw-semibold text-gray-600">
                                                            <tr>
                                                                <td class="text-muted">
                                                                    <div class="d-flex align-items-center">
                                                                        <i class="ki-outline ki-devices fs-2 me-2"></i> Invoice
                                                                        <span class="ms-1" data-bs-toggle="tooltip" title="View the invoice generated by this order.">
                                                                            <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                                                        </span>
                                                                    </div>
                                                                </td>
                                                                <td class="fw-bold text-end">
                                                                    <a href="<?= base_url('download-invoice/' . $order->id) ?>" class="text-gray-600 text-hover-primary">
                                                                        #INV-<?= str_pad($order->id, 6, '0', STR_PAD_LEFT) ?>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-muted">
                                                                    <div class="d-flex align-items-center">
                                                                        <i class="ki-outline ki-truck fs-2 me-2"></i> Shipping
                                                                    </div>
                                                                </td>
                                                                <td class="fw-bold text-end">#SHP-<?= str_pad($order->shipping_id, 7, '0', STR_PAD_LEFT) ?></td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-muted">
                                                                    <div class="d-flex align-items-center">
                                                                        <i class="ki-outline ki-discount fs-2 me-2"></i> Reward Points
                                                                    </div>
                                                                </td>
                                                                <td class="fw-bold text-end"><?= $order->commission_amount ?? 0 ?></td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <!--end::Documents-->

                                    </div>

                                    <?php if($order->payment_status != "failed"){?>

                                    <div class="d-flex flex-column gap-7 gap-lg-10 mt-10">

                                    <?php
                                            // helper mappers
                                            function badge_ship_user($status){
                                            $map = [
                                                'placed'=>'secondary','paid'=>'primary','packed'=>'info',
                                                'shipped'=>'info','out_for_delivery'=>'warning','delivered'=>'success',
                                                'cancelled'=>'secondary','refunded'=>'secondary','failed'=>'danger'
                                            ];
                                            $cls = $map[$status] ?? 'secondary';
                                            return '<span class="badge badge-light-'.$cls.' text-uppercase">'.$status.'</span>';
                                            }
                                            function ship_progress_percent($status){
                                            $order = ['placed','paid','packed','shipped','out_for_delivery','delivered'];
                                            $i = array_search($status, $order);
                                            if ($i === false) return 0;
                                            return (int) round(($i / (count($order)-1)) * 100); // 0..100
                                            }
                                            $percent = ship_progress_percent($ship_status);
                                            ?>

                                            <!-- BEGIN: Order Status Block -->
                                            <div class="card card-flush mb-7">
                                            <div class="card-header">
                                                <div class="card-title">
                                                <h2 class="mb-0">Shipping Status</h2>
                                                </div>
                                                <div class="card-toolbar">
                                                <?= badge_ship_user($ship_status); ?>
                                                </div>
                                            </div>
                                            <div class="card-body pt-0">
                                                <!-- Summary line -->
                                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-4 mb-5">
                                                <div class="d-flex align-items-center flex-wrap gap-4">
                                                    <div class="d-flex align-items-center text-gray-700">
                                                    <i class="ki-outline ki-delivery fs-2 me-2"></i>
                                                    <div>
                                                        <div class="fs-7 text-muted">Fulfillment</div>
                                                        <div class="fs-6 fw-semibold text-capitalize"><?=$ship_status?></div>
                                                    </div>
                                                    </div>

                                                    <div class="vr mh-20 mh-lg-0 d-none d-md-block"></div>

                                                    <div class="text-gray-700">
                                                    <div class="fs-7 text-muted">Courier</div>
                                                    <div class="fs-6 fw-semibold">
                                                        <?= htmlspecialchars($latest_shipment->courier_name ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                    </div>

                                                    <div class="text-gray-700">
                                                    <div class="fs-7 text-muted">Tracking</div>
                                                    <div class="fs-6 fw-semibold">
                                                        <?= htmlspecialchars($latest_shipment->tracking_number ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                    </div>

                                                    <div class="text-gray-700">
                                                    <div class="fs-7 text-muted">ETA</div>
                                                    <div class="fs-6 fw-semibold">
                                                        <?= !empty($latest_shipment->expected_delivery) ? htmlspecialchars($latest_shipment->expected_delivery, ENT_QUOTES, 'UTF-8') : '-' ?>
                                                    </div>
                                                    </div>
                                                </div>

                                                <!-- (Optional) Copy tracking button -->
                                                <?php if(!empty($latest_shipment->tracking_number)): ?>
                                                <button class="btn btn-sm btn-light-primary" id="btnCopyTrack">
                                                    <i class="ki-outline ki-copy fs-2 me-2"></i> Copy Tracking
                                                </button>
                                                <?php endif; ?>
                                                </div>

                                                <!-- Horizontal Stepper (progress) -->
                                                <?php $steps = ['placed','paid','packed','shipped','out_for_delivery','delivered']; ?>
                                                <div class="mb-4">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <div class="fs-7 text-muted">Progress</div>
                                                    <div class="fs-7 text-muted"><?=$percent;?>%</div>
                                                </div>
                                                <div class="progress h-8px bg-light-primary">
                                                    <div class="progress-bar" role="progressbar" style="width: <?=$percent;?>%;" aria-valuenow="<?=$percent;?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                </div>

                                                <!-- Step bullets -->
                                                <div class="d-flex justify-content-between mt-4">
                                                <?php foreach($steps as $s): 
                                                        $active = array_search($s, $steps) <= array_search($ship_status, $steps);
                                                ?>
                                                    <div class="text-center flex-grow-1">
                                                    <div class="rounded-circle mx-auto mb-2" style="width:28px;height:28px;<?= $active ? 'background:#50cd89;' : 'background:#e4e6ef;' ?>"></div>
                                                    <div class="fs-8 text-muted text-capitalize"><?= $s ?></div>
                                                    </div>
                                                <?php endforeach; ?>
                                                </div>

                                                <!-- (Optional) Last update note -->
                                                <?php if (!empty($history)): $last = $history[0]; ?>
                                                <div class="alert alert-light mt-6 mb-0">
                                                    <i class="ki-outline ki-information-5 text-primary me-2"></i>
                                                    <span class="fw-semibold text-capitalize"><?= htmlspecialchars($last->status, ENT_QUOTES, 'UTF-8') ?></span>
                                                    <span class="text-muted">on <?= date('d M Y, H:i', strtotime($last->created_at)) ?></span>
                                                    <?php if(!empty($last->note)): ?>
                                                    <span class="text-muted"> · <?= htmlspecialchars($last->note, ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            </div>
                                            <!-- END: Order Status Block -->

                                            <?php if(!empty($latest_shipment->tracking_number)): ?>
                                            <script>
                                            document.getElementById('btnCopyTrack')?.addEventListener('click', async ()=>{
                                                try{
                                                await navigator.clipboard.writeText('<?= htmlspecialchars($latest_shipment->tracking_number, ENT_QUOTES, "UTF-8") ?>');
                                                if(window.toastr) toastr.success('Tracking number copied');
                                                else alert('Copied!');
                                                }catch(e){ if(window.toastr) toastr.error('Copy failed'); }
                                            });
                                            </script>
                                            <?php endif; ?>


                                    </div>
                                    <?php } ?>
                                    

                                 <div class="d-flex flex-column gap-7 gap-lg-10 mt-10">

                                            <div class="d-flex flex-column flex-xl-row gap-7 gap-lg-10">
                                                <!-- Billing Address -->
                                                <div class="card card-flush py-4 flex-row-fluid position-relative">
                                                    <div class="position-absolute top-0 end-0 bottom-0 opacity-10 d-flex align-items-center me-5">
                                                        <i class="ki-solid ki-two-credit-cart" style="font-size: 14em"></i>
                                                    </div>
                                                    <div class="card-header">
                                                        <div class="card-title">
                                                            <h2>Billing Address</h2>
                                                        </div>
                                                    </div>
                                                    <div class="card-body pt-0">
                                                        <?= htmlspecialchars($shipping->first_name . ' ' . $shipping->last_name, ENT_QUOTES, 'UTF-8') ?><br>
                                                        <?= htmlspecialchars($shipping->address, ENT_QUOTES, 'UTF-8') ?><br>
                                                        <?= htmlspecialchars($shipping->city . ' ' . $shipping->postal_code, ENT_QUOTES, 'UTF-8') ?><br>
                                                        <?= htmlspecialchars($shipping->state . ', ' . $shipping->country, ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                </div>

                                                <!-- Shipping Address -->
                                                <div class="card card-flush py-4 flex-row-fluid position-relative">
                                                    <div class="position-absolute top-0 end-0 bottom-0 opacity-10 d-flex align-items-center me-5">
                                                        <i class="ki-solid ki-delivery" style="font-size: 13em"></i>
                                                    </div>
                                                    <div class="card-header">
                                                        <div class="card-title">
                                                            <h2>Shipping Address</h2>
                                                        </div>
                                                    </div>
                                                    <div class="card-body pt-0">
                                                        <?= htmlspecialchars($shipping->first_name . ' ' . $shipping->last_name, ENT_QUOTES, 'UTF-8') ?><br>
                                                        <?= htmlspecialchars($shipping->address, ENT_QUOTES, 'UTF-8') ?><br>
                                                        <?= htmlspecialchars($shipping->city . ' ' . $shipping->postal_code, ENT_QUOTES, 'UTF-8') ?><br>
                                                        <?= htmlspecialchars($shipping->state . ', ' . $shipping->country, ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                </div>
                                            </div>


                                            <div class="card card-flush py-4 flex-row-fluid overflow-hidden">
                                                 <!--begin::Card header-->
                                                <div class="card-header">
                                                    <div class="card-title">
                                                        <h2>Order #<?= htmlspecialchars($order->order_code) ?></h2>
                                                    </div>
                                                </div>
                                                <!--end::Card header-->

                                                <!--begin::Card body-->
                                                <div class="card-body pt-0">
                                                    <div class="table-responsive">
                                                        <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
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

                                                                <?php 
                                                                    $subtotal = 0;
                                                                    foreach ($order_items as $item): 
                                                                        $product_image = base_url('assets/images/' . $item->product_image);
                                                                        $total = $item->price * $item->quantity;
                                                                        $subtotal += $total;
                                                                ?>
                                                                <tr>
                                                                    <td>
                                                                        <div class="d-flex align-items-center">
                                                                            <a href="#" class="symbol symbol-50px">
                                                                                <span class="symbol-label" style="background-image:url('<?= $product_image ?>');"></span>
                                                                            </a>
                                                                            <div class="ms-5">
                                                                                <a href="#" class="fw-bold text-gray-600 text-hover-primary"><?= htmlspecialchars($item->name) ?></a>
                                                                                <div class="fs-7 text-muted">Delivery Date: <?= date('d/m/Y', strtotime($order->created_at)) ?></div>
                                                                            </div>
                                                                        </div>
                                                                    </td>
                                                                    <td class="text-end"><?= htmlspecialchars($item->sku) ?></td>
                                                                    <td class="text-end"><?= $item->quantity ?></td>
                                                                    <td class="text-end">$<?= number_format($item->price, 2) ?></td>
                                                                    <td class="text-end">$<?= number_format($total, 2) ?></td>
                                                                </tr>
                                                                <?php endforeach; ?>

                                                                <!-- Subtotal -->
                                                                <tr>
                                                                    <td colspan="4" class="text-end">Subtotal</td>
                                                                    <td class="text-end">$<?= number_format($subtotal, 2) ?></td>
                                                                </tr>

                                                                <!-- VAT -->
                                                                <tr>
                                                                    <td colspan="4" class="text-end">VAT (20%)</td>
                                                                    <td class="text-end">$<?php echo $subtotal * 20 / 100; ?></td>
                                                                </tr>

                                                                <!-- Shipping (static or from DB) -->
                                                                <tr>
                                                                    <td colspan="4" class="text-end">Shipping Rate</td>
                                                                    <td class="text-end">$<?= number_format($shipping->shipping_rate ?? 0, 2) ?></td>
                                                                </tr>

                                                                <!-- Grand Total -->
                                                                <tr>
                                                                    <td colspan="4" class="fs-3 text-gray-900 text-end">Grand Total</td>
                                                                    <td class="text-gray-900 fs-3 fw-bolder text-end">
                                                                        $<?= number_format($order->total_amount, 2) ?>
                                                                    </td>
                                                                </tr>

                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <!--end::Card body-->
                                            </div>

                                  </div>




   
                                    </div>


                                </div>


                            </div>
                    </div>
                    <!--end::Wrapper-->

                                   </div>
                                 
                                </div>
                            </div>
                                <?php $this->load->view('user/layout/user_footer');?>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
            <i class="ki-outline ki-arrow-up"></i></div>

        <?php $this->load->view('user/layout/user_script');?>
        <script>
        const base_url = '<?php echo base_url();?>';
        const agent_id = '<?php echo $user_id;?>';
        </script>
        <script src="<?php echo base_url();?>/assets/admin/js/custom/authentication/sign-in/my-order.js?ver=2.9"></script>

    </body>

    </html>