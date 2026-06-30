<?php 
$title = site_settings('meta-settings','site-title');
$fav_img = site_settings('image','dark_footer_logo');
$discription = site_settings('meta-settings','site-description');
$keywords = site_settings('meta-settings','site-keyword');
$org_img = site_settings('image','og-img');
?>

<head>

    <meta name="author" content="Fenizo Technologies Top MLM Software Company">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="<?php echo $discription; ?>">
    <meta name="keywords" content="<?php echo $keywords; ?>">

    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="fenizo mlm software" />
    <meta property="og:title" content="Fenizo MLM software - The World's #1 Selling MLM software" />
    <meta property="og:url" content="<?php echo base_url(); ?>assets/images/<?php echo $org_img; ?>"/>
    <meta property="og:site_name" content="Fenizo mlm software" />

    <title><?php echo $title; ?></title>
    <link rel="shortcut icon" href="<?php echo base_url(); ?>assets/images/<?php echo $fav_img; ?>"  type="image/png">
    <link rel="canonical" href="Fenizo MLM Software"/>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700"/>   

	<!-- Icon CSS -->
	<link href="<?php echo base_url(); ?>assets/shop/css/vendor/materialdesignicons.min.css" rel="stylesheet">
	<link href="<?php echo base_url(); ?>assets/shop/css/vendor/remixicon.css" rel="stylesheet">

	<!-- Vendor -->
	<link href="<?php echo base_url(); ?>assets/shop/css/vendor/bootstrap.min.css" rel="stylesheet">
	<link href="<?php echo base_url(); ?>assets/shop/css/vendor/animate.min.css" rel="stylesheet">
	<link href="<?php echo base_url(); ?>assets/shop/css/vendor/owl.carousel.min.css" rel="stylesheet">
	<link href="<?php echo base_url(); ?>assets/shop/css/vendor/slick.min.css" rel="stylesheet">
	<link href="<?php echo base_url(); ?>assets/shop/css/vendor/swiper-bundle.min.css" rel="stylesheet">
	<link href="<?php echo base_url(); ?>assets/shop/css/vendor/nouislider.css" rel="stylesheet">

	<!-- Main CSS -->
	<link id="mainCss" href="<?php echo base_url(); ?>assets/shop/css/style.css" rel="stylesheet">
</head>

<style>
.active-heart {
    color: red !important;
}
.active-cart {
	color: #0d6efd !important;
}
.toast-custom {
  padding: 15px 20px;
  background-color: #fff;
  font-size: 14px;
  color: #313b50;
  border: 1px solid #3a4ee5;
  border-bottom: 5px solid #3a4ee5;
  border-radius: 5px;
  display: block;
  -webkit-box-shadow: 2px 2px 15px 0 rgba(0, 0, 0, 0.07);
  box-shadow: 2px 2px 15px 0 rgba(0, 0, 0, 0.07);
animation: slideIn 0.4s ease-in-out;
}
.toast-icon {
  display: flex;
  align-items: center;
}
@keyframes slideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}
</style>

