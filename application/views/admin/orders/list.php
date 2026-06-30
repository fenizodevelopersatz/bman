<?php $this->load->view('admin/Layout/common_style');?>

    <link href="<?php echo base_url();?>/assets/admin/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css">
    <link href="<?php echo base_url();?>/assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.css" rel="stylesheet" type="text/css">
    <link href="<?php echo base_url();?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />

    <style>
        .h-md-40{
            min-height:42%;
        }
    </style>

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

                            <!--begin::Main-->
                            <div class="app-main flex-column flex-row-fluid " id="kt_app_main">
                                <div class="d-flex flex-column flex-column-fluid">

                                    <!--begin::Toolbar-->
                                    <div id="kt_app_toolbar" class="app-toolbar  py-3 py-lg-6 ">
                                        <div id="kt_app_toolbar_container" class="app-container  container-xxl d-flex flex-stack ">
                                            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3 ">

                                                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                                                <?php echo $title; ?>
                                                </h1>

                                                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                                    <li class="breadcrumb-item text-muted">
                                                        <a href="<?php echo base_url();?>" class="text-muted text-hover-primary">
                                                        Marketting Tool                         
                                                        </a>
                                                    </li>
                                                    <li class="breadcrumb-item">
                                                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                                    </li>
                                                    <li class="breadcrumb-item text-muted">
                                                    <?php echo $title; ?> </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <!--end::Toolbar-->




                                    <!--begin::Content-->
                                    <div id="kt_app_content" class="app-content  flex-column-fluid mt-10">

                                        <!--begin::Content container-->
                                        <div id="kt_app_content_container" class="app-container  container-xxl ">

                                            
                                            <?php $this->load->view('notification'); ?>


                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="card mb-5 mb-xxl-8">

                                                        <div class="card-header mb-4 border-transparent">
                                                            <h3 class="anchor fw-bold "><?php echo $card_tilte;?></h3>
                                                        </div>

                                                        <div class="card-body pt-9 pb-9">

                                                            <div class="d-flex flex-stack mb-5">


                                                            <div class="container-fluid py-4">
                                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                                <h4 class="mb-0">Orders</h4>
                                                                <form class="d-flex" method="get">
                                                                <input type="text" name="q" value="<?=html_escape($q)?>" class="form-control me-2" placeholder="Search order code / user id">
                                                                <button class="btn btn-primary">Search</button>
                                                                </form>
                                                            </div>

                                                            <div class="table-responsive">
                                                                <table class="table align-middle">
                                                                <thead>
                                                                    <tr>
                                                                    <th>#</th>
                                                                    <th>Order Code</th>
                                                                    <th>User</th>
                                                                    <th>Amount</th>
                                                                    <th>Payment</th>
                                                                    <th>Ship Status</th>
                                                                    <th>Placed</th>
                                                                    <th>Action</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                <?php foreach($rows as $r): ?>
                                                                    <tr>
                                                                    <td><?=$r->id?></td>
                                                                    <td><span class="fw-semibold"><?=$r->order_code?></span></td>
                                                                   <td><?=html_escape($r->user_email ?? '-')?></td> 
                                                                    <td>$<?=number_format($r->total_amount,2)?></td>
                                                                    <td>
                                                                        <span class="badge badge-light-<?=$r->payment_status==='paid'?'success':($r->payment_status==='failed'?'danger':'secondary')?>"><?=$r->payment_status?></span>
                                                                        <small class="text-muted d-block"><?=$r->payment_method?></small>
                                                                    </td>
                                                                    <td> 
                                                                        <span class="badge badge-light-<?=
                                                                        $r->ship_status==='delivered'?'success':
                                                                        ($r->ship_status==='shipped'||$r->ship_status==='out_for_delivery'?'info':
                                                                        ($r->ship_status==='cancelled'?'secondary':
                                                                        ($r->ship_status==='failed'?'danger':'warning')))?>">
                                                                        <?=$r->ship_status?>
                                                                        </span>
                                                                    </td>
                                                                    <td><?=date('Y-m-d H:i', strtotime($r->created_at))?></td>
                                                                    <td>
                                                                        <a href="<?=site_url('admin/orders/view/'.$r->id)?>" class="btn btn-sm btn-info me-2">
                                                                            <i class="fa fa-eye"></i> View
                                                                        </a>
                                                                           <a href="<?=site_url('admin/orders/invoice/'.$r->id)?>" target="_blank" class="btn btn-sm btn-primary me-2">
                                                                            <i class="fa fa-eye"></i> Invoice
                                                                        </a>
                                                                    </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                                </tbody>
                                                                </table>
                                                            </div>
                                                            </div>
                                                              
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>


                                        <div class="modal fade" id="kt_modal_view_summary" tabindex="-1" aria-labelledby="kt_modal_view_summary_label" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered mw-1000px">
                                                <div class="modal-content">
                                                    <div class="modal-header pb-0 border-0 justify-content-end">
                                                        <div class="btn btn-sm btn-icon " data-bs-dismiss="modal">
                                                            <i class="ki-outline ki-cross fs-1"></i>
                                                        </div>
                                                    </div>
                                                    <div class="modal-body scroll-y mx-5 mx-xl-18 pt-0 pb-15">
                                                    <div class="text-center mb-13">
                                                            <h1 class="d-flex justify-content-center align-items-center mb-3">
                                                                KYC Preview
                                                            </h1>    

                                                        
                                                        </div>

                                                        <div class="mh-475px scroll-y me-n7 pe-7">
                                                        <div class="fs-5" id="ai-summary-content"> </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        </div>
                                    </div>

                                </div>

                                <!--begin::Footer-->
                                <?php $this->load->view('admin/Layout/admin_footer');?>

                            </div>
                    </div>
                    <!--end::Wrapper-->

            </div>
            <!--end::Page-->
        </div>
        <!--end::App-->

        <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
            <i class="ki-duotone ki-arrow-up">
              <span class="path1"></span>
              <span class="path2"></span>
              </i>
        </div>

             <?php $this->load->view('admin/Layout/common_script');?>

            <script src="<?php echo base_url();?>/assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.js"></script>
            <script src="<?php echo base_url();?>/assets/admin/js/widgets.bundle.js"></script>
            <script src="<?php echo base_url();?>/assets/admin/js/custom/widgets.js"></script>
            <script src="<?php echo base_url();?>/assets/admin/js/custom/apps/chat/chat.js"></script>
            <script src="<?php echo base_url();?>/assets/admin/js/custom/utilities/modals/upgrade-plan.js"></script>
            <script src="<?php echo base_url();?>/assets/admin/js/custom/utilities/modals/users-search.js"></script>
            <script src="<?php echo base_url();?>/assets/admin/plugins/global/plugins.bundle.js"></script>
            <script src="<?php echo base_url();?>assets/admin/plugins/custom/datatables/datatables.bundle.js"></script>
            <link href="<?php echo base_url();?>assets/admin/plugins/custom/fullcalendar/fullcalendar.bundle.css" rel="stylesheet" type="text/css" />
            <script src="<?php echo base_url();?>assets/admin/plugins/custom/fullcalendar/fullcalendar.bundle.js"></script>

            <script>
            const base_url = '<?php echo base_url();?>';
            </script>
            <script>
            </script>
    </body>

    </html>