<!DOCTYPE html>
<html lang="en">

<?php 
$title = site_settings('meta-settings','site-title');
$fav_img = site_settings('image','dark_footer_logo');
$discription = site_settings('meta-settings','site-description');
$keywords = site_settings('meta-settings','site-keyword');
?>


<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?php echo $title; ?></title>
<link rel="icon" href="<?php echo base_url(); ?>assets/images/<?php echo $fav_img; ?>"  type="image/png">

<head>
<meta charset="utf-8">
<meta name="author" content="Fenizo Technologies Top MLM Software Company">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="description" content="<?php echo $discription; ?>">
<meta name="keywords" content="<?php echo $keywords; ?>">

<link href="<?php echo base_url();?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
<link href="<?php echo base_url();?>/assets/admin/css/style.bundle.css" rel="stylesheet" type="text/css">
</head>

<script>

if (window.top != window.self) {
window.top.location.replace(window.self.location.href);
}

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