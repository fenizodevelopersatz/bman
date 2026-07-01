<?php 
$title = site_settings('meta-settings','site-title');
$fav_img = site_settings('image','dark_footer_logo');
$discription = site_settings('meta-settings','site-description');
$keywords = site_settings('meta-settings','site-keyword');
$org_img = site_settings('image','og-img');
?>

<!--========= Required meta tags =========-->
<meta charset="utf-8">
<meta http-equiv="x-ua-compatible" content="ie=edge">
<meta name="author" content="Nexman Technologies Top MLM Software Company">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="<?php echo $discription; ?>">
<meta name="keywords" content="<?php echo $keywords; ?>">

<meta property="og:locale" content="en_US" />
<meta property="og:type" content="nexman software" />
<meta property="og:title" content="Nexman MLM software - The World's #1 Selling MLM software" />
<meta property="og:url" content="<?php echo base_url(); ?>assets/images/<?php echo $org_img; ?>"/>
<meta property="og:site_name" content="Nexman mlm software" />

<title><?php echo $title; ?></title>
<link rel="shortcut icon" href="<?php echo base_url(); ?>assets/images/<?php echo $fav_img; ?>"  type="image/png">
<link rel="canonical" href="Nexman MLM Software"/>
<!-- css include -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link href="<?php echo base_url();?>assets/user_v2/css/style.css" rel="stylesheet" type="text/css"/>

<?php
/* ---- Member Panel Theme (independent from the landing theme) ---- */
$mp_mode = site_settings('member_theme', 'mode'); if (!$mp_mode) $mp_mode = 'light';
$mp_user_switch = site_settings('member_theme', 'user_switch');
$mp_user_switch = ($mp_user_switch === '' || $mp_user_switch === null) ? '1' : $mp_user_switch;
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
        /* drive the dashboard's own accent so the palette controls the
           announcement banner, active sidebar, Create Ticket button, charts,
           progress rings, badges and links */
        --primary: <?php echo $mp['primary']; ?>;
        --primary-dark: <?php echo $mp['hover_highlight']; ?>;
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
        --bs-primary: <?php echo $mp['highlight_primary']; ?>;
        --bs-primary-rgb: <?php echo mp_hex_rgb($mp['highlight_primary']); ?>;
        --bs-link-color: <?php echo $mp['highlight_primary']; ?>;
        --bs-link-hover-color: <?php echo $mp['hover_highlight']; ?>;
    }
    /* sun/moon toggle for members */
    .mp-theme-toggle{ position:fixed; left:24px; bottom:24px; z-index:9999; width:48px; height:48px;
        display:flex; align-items:center; justify-content:center; border:none; border-radius:50%; cursor:pointer;
        background:var(--mp-primary); color:#fff; box-shadow:0 8px 22px rgba(0,0,0,.25); transition:all .25s ease; }
    .mp-theme-toggle:hover{ filter:brightness(1.08); transform:translateY(-2px); }
    .mp-theme-toggle svg{ width:22px; height:22px; }
    .mp-theme-toggle .mp-moon{ display:none; }
    html[data-bs-theme="dark"] .mp-theme-toggle .mp-sun{ display:none; }
    html[data-bs-theme="dark"] .mp-theme-toggle .mp-moon{ display:block; }
    /* header sun/moon button */
    #mpThemeToggle .mp-moon{ display:none; }
    html[data-bs-theme="dark"] #mpThemeToggle .mp-sun{ display:none; }
    html[data-bs-theme="dark"] #mpThemeToggle .mp-moon{ display:inline-block; }
</style>

<script>
    /* default dashboard mode comes from Settings -> Member Panel Theme */
    var defaultThemeMode = "<?php echo ($mp_mode === 'auto') ? 'system' : $mp_mode; ?>";
    var mpUserSwitch = <?php echo ($mp_user_switch !== '0') ? 'true' : 'false'; ?>;
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

<script>
    /* User-selectable sun / moon toggle (shown when the admin allows it) */
    (function () {
        if (typeof mpUserSwitch !== "undefined" && mpUserSwitch === false) return;
        var SUN  = '<svg class="mp-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"></path></svg>';
        var MOON = '<svg class="mp-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>';
        function current(){ return document.documentElement.getAttribute("data-bs-theme") === "dark" ? "dark" : "light"; }
        function apply(mode){
            document.documentElement.setAttribute("data-bs-theme", mode);
            try { localStorage.setItem("data-bs-theme", mode); } catch(e){}
            var b = document.querySelector(".mp-theme-toggle");
            if (b) b.title = (mode === "dark") ? "Switch to light" : "Switch to dark";
        }
        function build(){
            // 1) prefer the header button if present
            var header = document.getElementById("mpThemeToggle");
            if (header) {
                if (!header.dataset.mpBound) {
                    header.dataset.mpBound = "1";
                    header.addEventListener("click", function(){ apply(current() === "dark" ? "light" : "dark"); });
                }
                return;
            }
            // 2) fallback floating button for layouts without a header slot
            if (document.querySelector(".mp-theme-toggle")) return;
            var btn = document.createElement("button");
            btn.type = "button"; btn.className = "mp-theme-toggle";
            btn.innerHTML = SUN + MOON;
            btn.title = (current() === "dark") ? "Switch to light" : "Switch to dark";
            btn.addEventListener("click", function(){ apply(current() === "dark" ? "light" : "dark"); });
            document.body.appendChild(btn);
        }
        if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", build);
        else build();
    })();
</script>
