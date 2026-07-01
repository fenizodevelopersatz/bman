<html>
<?php 
$title = site_settings('meta-settings','site-title');
$fav_img = site_settings('image','dark_footer_logo');
$discription = site_settings('meta-settings','site-description');
$keywords = site_settings('meta-settings','site-keyword');
$org_img = site_settings('image','og-img');
?>

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
    <link href="<?php echo base_url();?>assets/user/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css"/>
    <link href="<?php echo base_url();?>assets/user/css/style.bundle.css" rel="stylesheet" type="text/css"/>
    <link href="<?php echo base_url();?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />

    <?php
    /* ---- Member Panel Theme (independent from the landing theme) ---- */
    $mp_mode = site_settings('member_theme', 'mode'); if (!$mp_mode) $mp_mode = 'light';
    $mp = array(
        'primary'           => site_settings('member_theme','primary')           ?: '#6D4AFF',
        'secondary'         => site_settings('member_theme','secondary')         ?: '#FFC94A',
        'accent'            => site_settings('member_theme','accent')             ?: '#A855F7',
        'highlight_primary' => site_settings('member_theme','highlight_primary')  ?: '#6D4AFF',
        'highlight_accent'  => site_settings('member_theme','highlight_accent')   ?: '#A855F7',
        'hover_highlight'   => site_settings('member_theme','hover_highlight')    ?: '#5a3df0',
        'active_highlight'  => site_settings('member_theme','active_highlight')   ?: '#6D4AFF',
        'gradient_start'    => site_settings('member_theme','gradient_start')     ?: '#6D4AFF',
        'gradient_end'      => site_settings('member_theme','gradient_end')       ?: '#A855F7',
        'success'           => site_settings('member_theme','success')           ?: '#1BC5BD',
        'warning'           => site_settings('member_theme','warning')           ?: '#FFA800',
        'danger'            => site_settings('member_theme','danger')            ?: '#F64E60',
        'info'              => site_settings('member_theme','info')              ?: '#8950FC',
    );
    if (!function_exists('mp_hex_rgb')) {
        function mp_hex_rgb($hex) {
            $hex = ltrim($hex, '#');
            if (strlen($hex) === 3) { $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2]; }
            if (strlen($hex) !== 6) return '109, 74, 255';
            return hexdec(substr($hex,0,2)).', '.hexdec(substr($hex,2,2)).', '.hexdec(substr($hex,4,2));
        }
    }
    ?>
    <style id="member-theme-vars">
        :root{
            --mp-primary: <?php echo $mp['primary']; ?>;
            --mp-secondary: <?php echo $mp['secondary']; ?>;
            --mp-accent: <?php echo $mp['accent']; ?>;
            --mp-highlight: <?php echo $mp['highlight_primary']; ?>;
            --mp-highlight-accent: <?php echo $mp['highlight_accent']; ?>;
            --mp-hover: <?php echo $mp['hover_highlight']; ?>;
            --mp-active: <?php echo $mp['active_highlight']; ?>;
            --mp-gradient: linear-gradient(135deg, <?php echo $mp['gradient_start']; ?>, <?php echo $mp['gradient_end']; ?>);
            --mp-success: <?php echo $mp['success']; ?>;
            --mp-warning: <?php echo $mp['warning']; ?>;
            --mp-danger: <?php echo $mp['danger']; ?>;
            --mp-info: <?php echo $mp['info']; ?>;
            /* drive Metronic's primary so buttons / active states follow the palette */
            --bs-primary: <?php echo $mp['highlight_primary']; ?>;
            --bs-primary-rgb: <?php echo mp_hex_rgb($mp['highlight_primary']); ?>;
            --bs-primary-active: <?php echo $mp['hover_highlight']; ?>;
            --bs-link-color: <?php echo $mp['highlight_primary']; ?>;
            --bs-link-hover-color: <?php echo $mp['hover_highlight']; ?>;
        }
    </style>

</head>

<script>
    /* default dashboard mode comes from Settings -> Member Panel Theme */
    var defaultThemeMode = "<?php echo ($mp_mode === 'auto') ? 'system' : $mp_mode; ?>";
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