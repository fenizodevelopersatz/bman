<?php $this->load->view('admin/Layout/common_style');?>

<link href="<?php echo base_url();?>/assets/admin/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css">
<link href="<?php echo base_url();?>/assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.css" rel="stylesheet" type="text/css">
<link href="<?php echo base_url();?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />

<style>
.h-md-40{
min-height:42%;
}
#profile_ini{
padding:20px;
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
Settings                         
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
<div id="kt_app_content" class="app-content  flex-column-fluid mt-10">

<div id="kt_app_content_container" class="app-container  container-xxl ">


<div class="card mb-5 mb-xl-10 ">
<div class="card-header border-0 cursor-pointer p-3" 
role="button" data-bs-toggle="collapse" 
data-bs-target="#kt_account_addagent_form_details" 
aria-expanded="true" aria-controls="kt_account_addagent_form_details">
<div class="card-title m-0">

<div class="me-3 d-flex justify-content-between text-center align-items-center gap-4">
<div class="d-flex flex-center w-60px h-60px rounded-3 bg-light-danger bg-opacity-90">
<i class="ki-duotone ki-abstract-26 text-danger fs-3x"><span class="path1"></span><span class="path2"></span></i>               
</div>
<h3 class="fw-bold m-0"><?php echo $card_title; ?></h3>
</div>

</div>
</div>


<!-- INSEIDE FORM ENTER -->

<div id="kt_account_settings_profile_details" class="collapse show">

<div class="d-flex flex-wrap flex-sm-nowrap" id="profile_ini">

<?php $this->load->view('admin/settings/advance-settings-list'); ?>

</div>

</div>
</div>



<div class="row">
<div class="col-lg-12">
<div class="card mb-5 mb-xxl-8">

<div class="card-header mb-4 border-transparent">
<h3 class="anchor fw-bold ">Update User Settings</h3>
</div>

<div class="card-body pt-9 pb-9">
<div class="d-flex flex-stack mb-5">
<div class="d-flex justify-content-end col-lg-12" data-kt-docs-table-toolbar="base">


                     <!-- INSEIDE FORM ENTER -->


        <form 
        id="kt_account_meta_details_form" 
        class="form w-100 fv-plugins-bootstrap5 fv-plugins-framework" 
        method="post"
        novalidate="novalidate" 
        data-kt-redirect-url="<?php echo base_url(); ?>user-settings" 
        action="<?php echo base_url();?>user-settings-update"
        enctype="multipart/form-data"
        >

        <div class="col-lg-6">
        <div class="row mb-6">
        <label class="col-lg-8 col-form-label fw-semibold fs-6">Two-factor enable for login<span class="text-danger"> * </span></label>
        <div class="col-lg-4 fv-row">
        <div class="input-group mb-5">
        <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
        <input class="form-check-input  h-30px w-50px" type="checkbox" value="1" name="twofa_login" 
        <?php echo $twofa_login ? "checked": ""; ?> 
        id="twofa_login"/>
        <label class="form-check-label" for="twofa_login">
        </label>
        </div>
        </div>
        </div>
        </div>
        </div>

        <div class="col-lg-6 d-none">
        <div class="row mb-6">
        <label class="col-lg-8 col-form-label fw-semibold fs-6">Two-factor enable for Dex Wallet Send Money<span class="text-danger"> * </span></label>
        <div class="col-lg-4 fv-row">
        <div class="input-group mb-5">
        <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
        <input class="form-check-input  h-30px w-50px" type="checkbox" value="1" name="twofa_editprofile" 
        <?php echo $twofa_editprofile ? "checked": ""; ?> 
        id="twofa_editprofile"/>
        <label class="form-check-label" for="twofa_editprofile">
        </label>
        </div>
        </div>
        </div>
        </div>
        </div>

        <div class="col-lg-6">
        <div class="row mb-6">
        <label class="col-lg-8 col-form-label fw-semibold fs-6">Two-factor enable for Internel Transfer<span class="text-danger"> * </span></label>
        <div class="col-lg-4 fv-row">
        <div class="input-group mb-5">
        <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
        <input class="form-check-input  h-30px w-50px" type="checkbox" value="1" name="twofa_internel_transfer" 
        <?php echo $twofa_internel_transfer ? "checked": ""; ?> 
        id="twofa_internel_transfer"/>
        <label class="form-check-label" for="twofa_internel_transfer">
        </label>
        </div>
        </div>
        </div>
        </div>
        </div>


        <div class="col-lg-6">
        <div class="row mb-6">
        <label class="col-lg-8 col-form-label fw-semibold fs-6">Two-factor enable for withdraw<span class="text-danger"> * </span></label>
        <div class="col-lg-4 fv-row">
        <div class="input-group mb-5">
        <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
        <input class="form-check-input  h-30px w-50px" type="checkbox" value="1" name="twofa_withdraw" 
        <?php echo $twofa_withdraw ? "checked": ""; ?> 
        id="twofa_withdraw"/>
        <label class="form-check-label" for="twofa_withdraw">
        </label>
        </div>
        </div>
        </div>
        </div>
        </div>

        <div class="row mb-6">
        <label class="col-lg-4 col-form-label fw-semibold fs-6"> Minimum Password length <span class="text-danger"> * </span></label>
        <div class="col-lg-8 fv-row">
        <div class="input-group mb-5">
        <span class="input-group-text border-transparent " id="basic-addon1">
        <i class="ki-duotone ki-password-check">
        <span class="path1"></span>
        <span class="path2"></span>
        <span class="path3"></span>
        <span class="path4"></span>
        <span class="path5"></span>
        </i>
        </span>
        <input type="text" name="min_password_length" 
        class="form-control form-control-lg form-control-solid" 
        placeholder="Min Password Length" 
        value="<?php echo $min_password_length; ?>" required>
        </div>
        </div>
        </div>

        <div class="row mb-6">
        <label class="col-lg-4 col-form-label fw-semibold fs-6"> Maximum Password length <span class="text-danger"> * </span></label>
        <div class="col-lg-8 fv-row">
        <div class="input-group mb-5">
        <span class="input-group-text border-transparent " id="basic-addon1">
        <i class="ki-duotone ki-password-check">
        <span class="path1"></span>
        <span class="path2"></span>
        <span class="path3"></span>
        <span class="path4"></span>
        <span class="path5"></span>
        </i>
        </span>
        <input type="text" name="max_password_length" 
        class="form-control form-control-lg form-control-solid" 
        placeholder="Maximum Password Length" 
        value="<?php echo $max_password_length; ?>" required>
        </div>
        </div>
        </div>

        <div class="card-footer d-flex justify-content-end py-6 px-9">
        <button type="submit" class="btn btn-primary" id="kt_account_meta_details_submit">Save Changes</button>
        </form>


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
<script src="<?php echo base_url();?>/assets/admin/js/custom/authentication/sign-in/user-settings.js?ver=2.9"></script>

<script>
</script>
</body>

</html>