<!DOCTYPE html>
<html lang="en">

<?php 
$title = site_settings('meta-settings','site-title');
$fav_img = site_settings('image','favicon'); // was 'dark_footer_logo' — wrong settings key, that's a separate (usually unset) footer image, not the favicon Site Settings actually saves
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
<meta name="author" content="Nexman Technologies Top MLM Software Company">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="description" content="<?php echo $discription; ?>">
<meta name="keywords" content="<?php echo $keywords; ?>">

<link href="<?php echo base_url();?>assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
<link href="<?php echo base_url();?>assets/admin/css/style.bundle.css" rel="stylesheet" type="text/css">
<style>
/* Bootstrap's .container-xxl (from style.bundle.css) caps at max-width:1320px
   and auto-centers past that — correct for a plain centered page, wrong for
   this dashboard shell where content sits beside a fixed sidebar. On any
   viewport wide enough that the sidebar + 1320px no longer fill the window
   (~1585px+ total), centering opens equal empty gutters on both sides of the
   content — most visibly as a gap between the sidebar and the content's left
   edge. Every admin page's content wrapper uses `.app-container.container-xxl`
   together (never container-xxl alone outside this shell — confirmed against
   admin/login.php, which loads this same stylesheet but never pairs the two
   classes), so this compound selector reaches exactly the dashboard content
   containers and nothing else. Fixed here once for every admin page rather
   than per-page, since they all share the identical markup pattern.
   See docs on the ROI Distribution History alignment fix. */
.app-container.container-xxl { max-width: none; margin-left: 0; margin-right: 0; }
</style>
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