<!doctype html>

<?php 
$title = site_settings('meta-settings','site-title');
$fav_img = site_settings('image','dark_footer_logo');
$discription = site_settings('meta-settings','site-description');
$keywords = site_settings('meta-settings','site-keyword');
$org_img = site_settings('image','og-img');
?>

<html lang="en" >
<head>
    <!--========= Required meta tags =========-->
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta charset="utf-8">
    <meta name="author" content="Nexman Technologies Top MLM Software Company">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="<?php echo $discription; ?>">
    <meta name="keywords" content="<?php echo $keywords; ?>">

    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="nexman software" />
    <meta property="og:title" content="Nexman MLM software - The World's #1 Selling MLM software" />
    <meta property="og:url" content="<?php echo base_url(); ?>assets/images/<?php echo $org_img; ?>"/>
    <meta property="og:site_name" content="Nexman mlm software" />

    <title><?php echo $title; ?></title>
    <link rel="shortcut icon" href="<?php echo base_url(); ?>assets/images/<?php echo $fav_img; ?>"  type="image/png">
    <link rel="canonical" href="Nexman MLM Software"/>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700"/>   

    <!-- css include -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700"/>        <!--end::Fonts-->
    <link href="<?php echo base_url();?>assets/user/plugins/custom/fullcalendar/fullcalendar.bundle.css" rel="stylesheet" type="text/css"/>
    <link href="<?php echo base_url();?>assets/user/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css"/>
    <link href="<?php echo base_url();?>assets/user/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css"/>
    <link href="<?php echo base_url();?>assets/user/css/style.bundle.css" rel="stylesheet" type="text/css"/>
    <link href="<?php echo base_url();?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
</head>

<script>
    var defaultThemeMode = "light";
    var themeMode;
    if ( document.documentElement ) {
    if ( document.documentElement.hasAttribute("data-bs-theme-mode")) {
    themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
    } else {
    if ( localStorage.getItem("data-bs-theme") !== null ) {
    themeMode = localStorage.getItem("data-bs-theme");
    } else {
    themeMode = defaultThemeMode;
    }			
    }
    if (themeMode === "system") {
    themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
    }
    document.documentElement.setAttribute("data-bs-theme", themeMode);
    }         
</script>