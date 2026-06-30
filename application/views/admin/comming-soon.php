
<html>

<?php $this->load->view('admin/Layout/common_style');  ?>

<?php 
$logo = site_settings('image','dark_logo');
?>

<body id="kt_body" class="app-blank bgi-size-cover bgi-position-center bgi-no-repeat">

<div class="d-flex flex-column flex-root" id="kt_app_root">
<style>
body {
background-image: url('<?php echo base_url();?>/assets/admin/media/auth/bg4.jpg');
}
[data-bs-theme="dark"] body {
background-image: url('<?php echo base_url();?>/assets/admin/media/auth/bg4-dark.jpg');
}

</style>

<?php

$pageinfo = $this->db->query("SELECT * FROM page_link_config where id= '".$id."' ")->row();

if ($pageinfo && $pageinfo->page_status == 1) {
    $document_url = base_url($pageinfo->page_document); 
    header("Location: " . $document_url);
    exit;
}
?>

<div class="d-flex flex-column flex-root">


    <div class="d-flex flex-column flex-center flex-column-fluid">

        
    <div class="d-flex flex-column flex-center text-center p-10">
    <!--begin::Wrapper-->
    <div class="card card-flush w-lg-650px py-5">
        <div class="card-body py-15 py-lg-20">

            <!--begin::Logo-->
            <div class="mb-13">
                <a href="<?php echo base_url();?>" class="">
                    <img alt="Logo" src="<?php echo base_url()."assets/images/".$logo;?>" class="h-40px">
                </a>
            </div>
            <!--end::Logo-->

            <!--begin::Title-->
                <h1 class="fw-bolder text-gray-900 mb-7">
               <?php echo  $pageinfo->page_title; ?>
                </h1>

       


            <!--begin::Illustration-->
            <div class="mb-n5">
                <img src="<?php echo base_url()."".$pageinfo->page_image;?>" class="mw-100 mh-300px theme-light-show" alt="">
                <img src="<?php echo base_url()."".$pageinfo->page_image;?>" class="mw-100 mh-300px theme-dark-show" alt="">
            </div>
            <!--end::Illustration-->


                 <!--begin::Text-->
                 <div class="fw-semibold fs-6 text-gray-500 mt-7">
            <?php echo  $pageinfo->page_content; ?>
            </div>
            <!--end::Text-->

        </div>
    </div>
    <!--end::Wrapper-->
</div>


    </div>

    
</div>
</div>

<?php $this->load->view('admin/Layout/common_script');?>
<script src="<?php echo base_url();?>/assets/admin/js/custom/authentication/sign-in/general.js?ver=1.5"></script>
<script src='https://www.google.com/recaptcha/api.js'></script>
</body>
</html>