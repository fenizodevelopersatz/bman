<?php
/**
 * Shared chrome for the Rank Management pages (open half).
 * Pair with _rank_foot.php. Expects: $title, $card_tilte. Optional: $intro.
 * Factored out so the seven rank pages don't each carry 40 lines of identical
 * Metronic scaffolding.
 */
$this->load->view('admin/Layout/common_style');
?>
<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />

<style>
    .rk-dot   { display:inline-block; width:12px; height:12px; border-radius:50%;
                vertical-align:middle; border:1px solid rgba(0,0,0,.15); margin-right:6px; }
    .rk-badge { height:22px; width:22px; object-fit:contain; vertical-align:middle; margin-right:6px; }
    .rk-mono  { font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:.85rem; }
    .rk-filters .form-control, .rk-filters .form-select { min-width:140px; }
    .rk-num   { text-align:right; font-variant-numeric:tabular-nums; }
    @media print { .rk-noprint { display:none !important; } }
</style>

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

                        <!--begin::Toolbar-->
                        <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                                    <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                                        <?php echo $title; ?>
                                    </h1>
                                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                        <li class="breadcrumb-item text-muted">
                                            <a href="<?php echo base_url(); ?>" class="text-muted text-hover-primary">Compensation</a>
                                        </li>
                                        <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                        <li class="breadcrumb-item text-muted">Rank Management</li>
                                        <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                        <li class="breadcrumb-item text-muted"><?php echo $title; ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <!--end::Toolbar-->

                        <div id="kt_app_content" class="app-content flex-column-fluid mt-10">
                            <div id="kt_app_content_container" class="app-container container-xxl">

                                <?php $this->load->view('notification'); ?>

                                <!-- Rank Management section nav -->
                                <ul class="nav nav-tabs nav-line-tabs fs-6 fw-semibold mb-6 rk-noprint">
                                    <?php
                                    $rk_nav = [
                                        'admin/staking/ranks'              => 'Rank Definitions',
                                        'admin/staking/rank-history'       => 'Rank History',
                                        'admin/staking/rank-rewards'       => 'Rank Rewards',
                                        'admin/staking/rank-certificates'  => 'Certificates',
                                        'admin/staking/rank-power-users'   => 'Rank Power',
                                        'admin/staking/rank-reports'       => 'Reports',
                                        'admin/staking/rank-audit'         => 'Audit Log',
                                    ];
                                    $rk_here = uri_string();
                                    foreach ($rk_nav as $rk_url => $rk_label):
                                        $rk_active = (strpos($rk_here, trim($rk_url, '/')) === 0);
                                    ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo $rk_active ? 'active' : ''; ?>"
                                           href="<?php echo base_url($rk_url); ?>"><?php echo $rk_label; ?></a>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>

                                <div class="card mb-5 mb-xxl-8">
                                    <div class="card-header border-transparent pt-5">
                                        <h3 class="card-title fw-bold"><?php echo $card_tilte; ?></h3>
                                        <?php if (!empty($card_tools)) echo $card_tools; ?>
                                    </div>

                                    <div class="card-body pt-3 pb-9">
                                        <?php if (!empty($intro)): ?>
                                            <div class="text-muted fs-7 mb-5"><?php echo $intro; ?></div>
                                        <?php endif; ?>
