<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/* dynamic landing page — Webze template, content from landing_* tables */
$a = function ($p) { return lp_asset($p); };              // asset url
/* shared meta: landing SEO falls back to the global Site Settings meta
   so the home page and Site Settings stay in sync (single source of truth) */
$site_meta_title = site_settings('meta-settings', 'site-title');
$site_meta_desc  = site_settings('meta-settings', 'site-description');
$site_meta_keys  = site_settings('meta-settings', 'site-keyword');
$site_copyright  = site_settings('meta-settings', 'copyright');

/* Theme mode (light|dark). Admin default from General settings; the admin
   live-preview can force it with ?theme=light|dark. The template ships a full
   light theme under html[data-theme="light"]. */
$theme_mode = lp($general, 'theme_mode', 'light');
$theme_force = $this->input->get('theme');
$theme_force = ($theme_force === 'light' || $theme_force === 'dark') ? $theme_force : '';
if ($theme_force) { $theme_mode = $theme_force; }
?>
<!doctype html>
<html class="no-js" lang="en" data-theme="<?php echo $theme_mode; ?>">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title><?php echo html_escape(lp($seo, 'meta_title', $site_meta_title ?: lp($general, 'site_name', 'Webze'))); ?></title>
    <meta name="description" content="<?php echo html_escape(lp($seo, 'meta_description', $site_meta_desc)); ?>">
    <meta name="keywords" content="<?php echo html_escape(lp($seo, 'meta_keywords', $site_meta_keys)); ?>">
    <meta name="robots" content="<?php echo html_escape(lp($seo, 'robots', 'index, follow')); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php if (lp($seo, 'canonical')): ?><link rel="canonical" href="<?php echo html_escape(lp($seo, 'canonical')); ?>"><?php endif; ?>

    <!-- OpenGraph / Twitter -->
    <meta property="og:title" content="<?php echo html_escape(lp($seo, 'meta_title')); ?>">
    <meta property="og:description" content="<?php echo html_escape(lp($seo, 'meta_description')); ?>">
    <?php if (lp($seo, 'og_image')): ?><meta property="og:image" content="<?php echo $a(lp($seo, 'og_image')); ?>"><?php endif; ?>
    <meta name="twitter:card" content="<?php echo html_escape(lp($seo, 'twitter_card', 'summary_large_image')); ?>">

    <link rel="shortcut icon" type="image/x-icon" href="<?php echo $a(lp($general, 'favicon', 'assets/img/favicon.png')); ?>">

    <!-- CSS here -->
    <link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/animate.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/fontawesome-all.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/swiper-bundle.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/default-icons.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/default.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/aos.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/main.css'); ?>">

    <!-- dynamic theme colors (palette-driven, mapped to the template's --tg vars) -->
    <?php
    $c_primary = lp($general, 'primary_color');           // e.g. #FFC94A
    $c_second  = lp($general, 'secondary_color');          // e.g. #6D4AFF
    $c_bg      = lp($general, 'background_color');          // light: page bg
    $c_btnhov  = lp($general, 'button_hover_color');
    $c_font    = lp($general, 'font_family');
    ?>
    <style>
        :root{
            <?php if ($c_primary): ?>--tg-primary-color: <?php echo html_escape($c_primary); ?>;<?php endif; ?>
        }
        <?php if ($c_font): ?>body{ font-family: "<?php echo html_escape($c_font); ?>", sans-serif; }<?php endif; ?>
        <?php /* override the template's light-theme page bg with the admin colour (does NOT touch dark mode) */ ?>
        <?php if ($c_bg): ?>html[data-theme="light"]{ --tg-color-dark: <?php echo html_escape($c_bg); ?>; }<?php endif; ?>
        <?php if ($c_btnhov): ?>.tg-btn:hover, .tg-btn-two:hover, .header-btn .tg-btn:hover{ background-color: <?php echo html_escape($c_btnhov); ?> !important; border-color: <?php echo html_escape($c_btnhov); ?> !important; }<?php endif; ?>
        .lp-form-msg{ display:block; margin-top:10px; font-weight:500; }
        .lp-form-msg.ok{ color:#1bc5bd; } .lp-form-msg.err{ color:#f64e60; }
        <?php echo lp($scripts, 'custom_css'); ?>
    </style>
    <!-- make the admin-configured theme authoritative on load so a stale
         localStorage value can't keep the page dark (visitor toggle still works
         live within the session) -->
    <script>try{ localStorage.setItem('site-theme', '<?php echo $theme_mode; ?>'); }catch(e){}</script>
    <script src="<?php echo base_url('assets/js/theme.js'); ?>"></script>

    <?php echo lp($scripts, 'header_scripts'); ?>
    <?php echo lp($scripts, 'google_analytics'); ?>
    <?php echo lp($scripts, 'facebook_pixel'); ?>
</head>

<body>

    <?php if (lp($general, 'enable_preloader', '1') == '1'): ?>
    <div id="preloader"><div class="loader"></div></div>
    <?php endif; ?>

    <button class="scroll__top scroll-to-target" data-target="html"><i class="fas fa-arrow-up"></i></button>

    <!-- header-area -->
    <header id="home">
        <div id="sticky-header" class="tg-header__area <?php echo lp($header, 'transparent_header', '1') == '1' ? 'transparent-header' : ''; ?>">
            <div class="container">
                <div class="row"><div class="col-12">
                    <div class="tgmenu__wrap">
                        <nav class="tgmenu__nav">
                            <div class="logo">
                                <a href="<?php echo base_url('landing'); ?>"><img src="<?php echo $a(lp($header, 'logo', 'assets/img/logo/logo.svg')); ?>" alt="Logo"></a>
                            </div>
                            <div class="tgmenu__navbar-wrap tgmenu__main-menu d-none d-lg-flex">
                                <ul class="navigation">
                                    <?php foreach ($menu as $mi): if ($mi->parent_id) continue; ?>
                                        <li><a href="<?php echo html_escape($mi->url); ?>"<?php echo $mi->new_tab ? ' target="_blank"' : ''; ?>><?php echo html_escape($mi->title); ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="tgmenu__action">
                                <ul class="list-wrap">
                                    <li class="header-btn">
                                        <a href="<?php echo html_escape(lp($header, 'buy_btn_url', 'register.html')); ?>" class="tg-btn"><?php echo html_escape(lp($header, 'buy_btn_text', 'Register')); ?></a>
                                    </li>
                                </ul>
                            </div>
                            <div class="mobile-nav-toggler"><i class="tg-flaticon-menu-1"></i></div>
                        </nav>
                    </div>
                </div></div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div class="tgmobile__menu">
            <nav class="tgmobile__menu-box">
                <div class="close-btn"><i class="tg-flaticon-close-1"></i></div>
                <div class="nav-logo"><a href="<?php echo base_url('landing'); ?>"><img src="<?php echo $a(lp($header, 'mobile_logo', lp($header, 'logo', 'assets/img/logo/logo.svg'))); ?>" alt="Logo"></a></div>
                <div class="tgmobile__search">
                    <form action="#"><input type="text" placeholder="Search here..."><button><i class="fas fa-search"></i></button></form>
                </div>
                <div class="tgmobile__menu-outer"></div>
                <div class="social-links">
                    <ul class="list-wrap">
                        <?php foreach (array('facebook','twitter','instagram','linkedin','youtube') as $sn): if (!lp($social, $sn)) continue; ?>
                            <li><a href="<?php echo html_escape($social[$sn]); ?>"><i class="fab fa-<?php echo $sn === 'linkedin' ? 'linkedin-in' : ($sn === 'facebook' ? 'facebook-f' : $sn); ?>"></i></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </nav>
        </div>
        <div class="tgmobile__menu-backdrop"></div>
    </header>
    <!-- header-area-end -->

    <main class="main-area fix">

        <!-- banner-area -->
        <section class="banner__area banner__bg" data-background="<?php echo $a(lp($hero, 'bg_image', 'assets/img/banner/hero_bg.svg')); ?>">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-xl-7 col-lg-8 col-md-10">
                        <div class="banner__content">
                            <span class="sub-title wow fadeInUp" data-wow-delay=".2s"><?php echo html_escape(lp($hero, 'small_title')); ?></span>
                            <h2 class="title wow fadeInUp" data-wow-delay=".4s"><?php echo lp_hl(lp($hero, 'main_title'), lp($hero, 'highlight_text')); ?></h2>
                            <p class="wow fadeInUp" data-wow-delay=".6s"><?php echo html_escape(lp($hero, 'description')); ?></p>
                            <form action="<?php echo base_url('landing/early-access'); ?>" method="post" id="lpEarlyAccess" class="banner__form wow fadeInUp" data-wow-delay=".8s">
                                <label for="email"><img src="<?php echo base_url('assets/img/icon/envelope.svg'); ?>" alt=""></label>
                                <input type="email" id="email" name="email" placeholder="<?php echo html_escape(lp($hero, 'email_placeholder', 'Business email')); ?>" required>
                                <button type="submit" class="tg-btn"><?php echo html_escape(lp($hero, 'button_text', 'get early access')); ?></button>
                            </form>
                            <span class="lp-form-msg" id="lpEarlyAccessMsg"></span>
                            <span class="banner__content-bottom wow fadeInUp" data-wow-delay=".8s"><?php echo html_escape(lp($hero, 'bottom_text')); ?> <a href="<?php echo html_escape(lp($hero, 'bottom_link', '#')); ?>"><?php echo html_escape(lp($hero, 'bottom_link_text')); ?></a></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="banner__shape">
                <img src="<?php echo $a(lp($hero, 'hero_img1', 'assets/img/banner/hero_img01.png')); ?>" alt="shape" class="alltuchtopdown">
                <img src="<?php echo $a(lp($hero, 'hero_img2', 'assets/img/banner/hero_img02.png')); ?>" alt="shape" class="rotateme">
                <img src="<?php echo $a(lp($hero, 'hero_img3', 'assets/img/banner/hero_img03.png')); ?>" alt="shape" class="alltuchtopdown">
                <img src="<?php echo base_url('assets/img/banner/hero_bg_shape.svg'); ?>" alt="shape" class="banner__bg-shape">
            </div>
        </section>
        <!-- banner-area-end -->

        <!-- brand-area -->
        <?php if (!empty($brands)): ?>
        <div class="brand__area">
            <div class="container">
                <div class="brand__item-wrap">
                    <div class="swiper-container fix brand-active">
                        <div class="swiper-wrapper">
                            <?php foreach ($brands as $b): ?>
                                <div class="swiper-slide"><div class="brand__item"><img src="<?php echo $a($b->image); ?>" alt="<?php echo html_escape($b->alt); ?>"></div></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <!-- brand-area-end -->

        <!-- features-area -->
        <section id="features" class="features__area section-pt-120">
            <div class="container">
                <div class="row justify-content-center"><div class="col-lg-6">
                    <div class="section__title text-center mb-80">
                        <span class="sub-title"><?php echo html_escape(lp($features, 'sub_title')); ?></span>
                        <h2 class="title"><?php echo lp_hl(lp($features, 'title'), lp($features, 'highlight')); ?></h2>
                    </div>
                </div></div>
                <div class="row gutter-y-40">
                    <?php $i = 0; foreach ($feature_items as $f): $two = $i >= 2; ?>
                        <div class="col-lg-<?php echo $two ? '4' : '6'; ?>">
                            <div class="features__item<?php echo $two ? ' features__item-two' : ''; ?>">
                                <div class="features__icon"><img src="<?php echo $a($f->icon); ?>" alt="icon"></div>
                                <div class="features__content">
                                    <h2 class="title"><?php echo lp_hl($f->title, $f->highlight); ?></h2>
                                    <p><?php echo html_escape($f->description); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php $i++; endforeach; ?>
                </div>
            </div>
            <div class="features__shape"><img src="<?php echo base_url('assets/img/images/features_shape.png'); ?>" alt="shape"></div>
        </section>
        <!-- features-area-end -->

        <!-- marquee-area -->
        <?php if (lp($marquee, 'enable', '1') == '1'): ?>
        <section class="marquee__area section-pt-120">
            <div class="slider__marquee clearfix marquee-wrap">
                <div class="marquee_mode marquee__group">
                    <?php $rep = (int)lp($marquee, 'repeat', 2); $rep = $rep > 0 ? $rep : 2; for ($k = 0; $k < $rep; $k++): ?>
                        <h2 class="marquee__item"><?php echo html_escape(lp($marquee, 'text')); ?></h2>
                    <?php endfor; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
        <!-- marquee-area-end -->

        <!-- token-area -->
        <section id="token" class="token__area section-py-120">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <div class="token__content" data-aos="fade-right">
                            <div class="section__title mb-40">
                                <span class="sub-title"><?php echo html_escape(lp($token, 'sub_title')); ?></span>
                                <h2 class="title"><?php echo lp_hl(lp($token, 'title'), lp($token, 'highlight')); ?></h2>
                            </div>
                            <p><?php echo html_escape(lp($token, 'description')); ?></p>
                            <a href="<?php echo html_escape(lp($token, 'button_link', '#')); ?>" class="tg-btn tg-btn-two"><?php echo html_escape(lp($token, 'button_text', 'purchase now')); ?></a>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="token__wrap" data-aos="fade-left">
                            <div class="token__wrap-inner">
                                <h2 class="title">Token sale ends in:</h2>
                                <div class="countdown__wrap"><div class="coming-time" data-countdown="<?php echo html_escape(lp($token, 'countdown_date', '2026/12/30')); ?>"></div></div>
                                <h3 class="token__received"><span><?php echo html_escape(lp($token, 'contribution_amount')); ?></span> <?php echo html_escape(lp($token, 'received_text', 'contribution received')); ?></h3>
                                <div class="token__progress-wrap">
                                    <ul class="list-wrap token__progress-title">
                                        <li><?php echo html_escape(lp($token, 'min_goal')); ?></li>
                                        <li><?php echo html_escape(lp($token, 'max_goal')); ?></li>
                                    </ul>
                                    <div class="progress" role="progressbar" aria-valuenow="<?php echo (int)lp($token, 'progress_percentage', 50); ?>" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar" style="width: <?php echo (int)lp($token, 'progress_percentage', 50); ?>%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="copy-text">
                                <mark><?php echo html_escape(lp($token, 'wallet_address')); ?></mark>
                                <button class="copy-btn"><img src="<?php echo base_url('assets/img/icon/copy.svg'); ?>" alt=""></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="token__shape"><img src="<?php echo base_url('assets/img/images/features_shape.png'); ?>" alt=""></div>
        </section>
        <!-- token-area-end -->

        <div class="section-divider"><div class="container"><span></span></div></div>

        <!-- work-area -->
        <section id="work" class="work__area section-py-120">
            <div class="container">
                <div class="row justify-content-center"><div class="col-lg-6">
                    <div class="section__title text-center mb-80">
                        <span class="sub-title"><?php echo html_escape(lp($work, 'sub_title')); ?></span>
                        <h2 class="title"><?php echo lp_hl(lp($work, 'title'), lp($work, 'highlight')); ?></h2>
                    </div>
                </div></div>
                <div class="work__item-wrap">
                    <div class="work__img"><img src="<?php echo $a(lp($work, 'image', 'assets/img/images/work_img.png')); ?>" alt="img" class="alltuchtopdown"></div>
                    <?php
                    $n = count($work_items);
                    $half = (int)ceil($n / 2);
                    $left = array_slice($work_items, 0, $half);
                    $right = array_slice($work_items, $half);
                    ?>
                    <div class="row">
                        <div class="col-lg-6" data-aos="fade-right">
                            <?php foreach ($left as $idx => $w): $last = ($idx === count($left) - 1); ?>
                                <div class="work__item<?php echo $last ? ' mb-0' : ''; ?>">
                                    <h1 class="number"><?php echo html_escape($w->number); ?></h1>
                                    <h2 class="title"><?php echo lp_hl($w->title, $w->highlight); ?></h2>
                                    <p><?php echo html_escape($w->description); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="col-lg-6" data-aos="fade-left">
                            <?php foreach ($right as $idx => $w): $last = ($idx === count($right) - 1); ?>
                                <div class="work__item work__item-right<?php echo $last ? ' mb-0' : ''; ?>">
                                    <h1 class="number"><?php echo html_escape($w->number); ?></h1>
                                    <h2 class="title"><?php echo lp_hl($w->title, $w->highlight); ?></h2>
                                    <p><?php echo html_escape($w->description); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="work__shape"><img src="<?php echo base_url('assets/img/images/features_shape.png'); ?>" alt="shape"></div>
        </section>
        <!-- work-area-end -->

        <!-- exchange-area -->
        <?php if (lp($exchange, 'enable', '1') == '1'): ?>
        <section class="exchange__area section-pb-120" data-aos="fade-up">
            <div class="container">
                <div class="exchange__inner-wrap">
                    <div class="exchange__content">
                        <div class="icon"><img src="<?php echo $a(lp($exchange, 'main_image', 'assets/img/images/exchange_img.png')); ?>" alt="img"></div>
                        <div class="content">
                            <h2 class="title"><?php echo lp_hl(lp($exchange, 'title'), lp($exchange, 'highlight')); ?></h2>
                            <p><?php echo html_escape(lp($exchange, 'description')); ?></p>
                        </div>
                    </div>
                    <div class="exchange__icons">
                        <ul class="list-wrap">
                            <?php foreach ($exchange_logos as $el): ?>
                                <li><img src="<?php echo $a($el->image); ?>" alt="icon"></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>
        <!-- exchange-area-end -->

        <!-- crypto-area -->
        <section class="crypto__area section-py-120">
            <div class="container">
                <div class="row justify-content-center"><div class="col-lg-7">
                    <div class="section__title text-center mb-80">
                        <span class="sub-title"><?php echo html_escape(lp($crypto, 'sub_title')); ?></span>
                        <h2 class="title"><?php echo lp_hl(lp($crypto, 'title'), lp($crypto, 'highlight')); ?></h2>
                    </div>
                </div></div>
                <div class="row gutter-y-30 justify-content-center">
                    <?php foreach ($crypto_cards as $c): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="crypto__item">
                                <div class="crypto__icon"><img src="<?php echo $a($c->icon); ?>" alt="icon"></div>
                                <div class="crypto__content">
                                    <h2 class="title"><?php echo lp_hl($c->title, $c->highlight); ?></h2>
                                    <a href="<?php echo html_escape($c->button_link ?: '#'); ?>" class="tg-btn tg-btn-two"><?php echo html_escape($c->button_text); ?></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="crypto__shape"><img src="<?php echo base_url('assets/img/images/features_shape.png'); ?>" alt="shape"></div>
        </section>
        <!-- crypto-area-end -->

        <!-- faq-area -->
        <section class="faq__area section-py-120">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <div class="faq__img" data-aos="fade-right"><img src="<?php echo $a(lp($faq, 'image', 'assets/img/images/faq_img.png')); ?>" alt="img"></div>
                    </div>
                    <div class="col-lg-6">
                        <div class="faq__content" data-aos="fade-left">
                            <div class="section__title mb-60">
                                <span class="sub-title"><?php echo html_escape(lp($faq, 'sub_title')); ?></span>
                                <h2 class="title"><?php echo lp_hl(lp($faq, 'title'), lp($faq, 'highlight')); ?></h2>
                            </div>
                            <div class="faq__wrap">
                                <div class="accordion" id="accordionExample">
                                    <?php foreach ($faq_items as $idx => $fq): $show = $idx === 0; ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button<?php echo $show ? '' : ' collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?php echo $fq->id; ?>" aria-expanded="<?php echo $show ? 'true' : 'false'; ?>">
                                                    <?php echo html_escape($fq->question); ?>
                                                </button>
                                            </h2>
                                            <div id="faq<?php echo $fq->id; ?>" class="accordion-collapse collapse<?php echo $show ? ' show' : ''; ?>" data-bs-parent="#accordionExample">
                                                <div class="accordion-body"><p><?php echo html_escape($fq->answer); ?></p></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- faq-area-end -->

        <div class="section-divider"><div class="container"><span></span></div></div>

        <!-- roadmap-area -->
        <section id="roadmap" class="roadmap__area section-py-120">
            <div class="container">
                <div class="row justify-content-center"><div class="col-lg-6">
                    <div class="section__title text-center mb-80" data-aos="fade-up">
                        <span class="sub-title"><?php echo html_escape(lp($roadmap, 'sub_title')); ?></span>
                        <h2 class="title"><?php echo lp_hl(lp($roadmap, 'title'), lp($roadmap, 'highlight')); ?></h2>
                    </div>
                </div></div>
                <div class="roadmap__item-wrap" data-aos="fade-up" data-aos-delay="300">
                    <div class="row gutter-y-40">
                        <?php foreach ($roadmap_items as $r): ?>
                            <div class="col-lg-3 col-md-6">
                                <div class="roadmap__item">
                                    <div class="roadmap__icon"><img src="<?php echo $a($r->icon); ?>" alt="icon"></div>
                                    <div class="roadmap__content">
                                        <h3 class="title"><?php echo html_escape($r->year); ?></h3>
                                        <p><?php echo html_escape($r->description); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="roadmap__shape"><img src="<?php echo base_url('assets/img/images/features_shape.png'); ?>" alt="shape"></div>
        </section>
        <!-- roadmap-area-end -->

        <!-- team-area -->
        <section class="team__area section-py-120">
            <div class="container">
                <div class="row">
                    <div class="col-xl-6 col-lg-5">
                        <div class="team__content-wrap">
                            <div class="section__title mb-40">
                                <span class="sub-title"><?php echo html_escape(lp($team, 'sub_title')); ?></span>
                                <h2 class="title"><?php echo lp_hl(lp($team, 'title'), lp($team, 'highlight')); ?></h2>
                            </div>
                            <p><?php echo html_escape(lp($team, 'description')); ?></p>
                            <div class="team__social-wrap">
                                <h2 class="title">Follow us</h2>
                                <ul class="list-wrap">
                                    <?php foreach (array('facebook','twitter','telegram','discord') as $sn): if (!lp($social, $sn)) continue; ?>
                                        <li><a href="<?php echo html_escape($social[$sn]); ?>">
                                            <div class="shape"><img src="<?php echo base_url('assets/img/icon/icons_bg.svg'); ?>" alt="shape"></div>
                                            <img src="<?php echo base_url('assets/img/icon/' . $sn . '.svg'); ?>" alt="icon" class="icon">
                                        </a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6 col-lg-7">
                        <div class="team__item-wrap">
                            <div class="row gutter-y-30">
                                <?php foreach ($team_members as $tm):
                                    // pick first available social for the member badge
                                    $sn = ''; foreach (array('facebook','twitter','telegram','discord','linkedin') as $cand) { if (!empty($tm->$cand)) { $sn = $cand; break; } }
                                ?>
                                    <div class="col-md-6">
                                        <div class="team__item">
                                            <div class="team__thumb"><img src="<?php echo $a($tm->photo); ?>" alt="img"></div>
                                            <div class="team__content">
                                                <span><?php echo html_escape($tm->position); ?></span>
                                                <h3 class="title"><?php echo html_escape($tm->name); ?></h3>
                                            </div>
                                            <?php if ($sn): ?>
                                            <div class="social__icon">
                                                <a href="<?php echo html_escape($tm->$sn); ?>">
                                                    <div class="shape"><img src="<?php echo base_url('assets/img/icon/icons_bg.svg'); ?>" alt="shape"></div>
                                                    <img src="<?php echo base_url('assets/img/icon/' . ($sn === 'linkedin' ? 'linkedin' : $sn) . '.svg'); ?>" alt="icon">
                                                </a>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="team__shape"><img src="<?php echo base_url('assets/img/images/features_shape.png'); ?>" alt="shape"></div>
        </section>
        <!-- team-area-end -->

    </main>

    <!-- footer-area -->
    <footer class="footer__area">
        <div class="container">
            <div class="footer__top">
                <div class="row justify-content-center"><div class="col-lg-8">
                    <div class="footer__content">
                        <div class="footer__logo"><a href="<?php echo base_url('landing'); ?>"><img src="<?php echo $a(lp($footer, 'logo', 'assets/img/logo/logo.svg')); ?>" alt="logo"></a></div>
                        <span class="sub-title"><?php echo html_escape(lp($footer, 'sub_title')); ?></span>
                        <h2 class="title"><?php echo lp_hl(lp($footer, 'title'), lp($footer, 'highlight')); ?></h2>
                        <div class="team__social-wrap">
                            <ul class="list-wrap">
                                <?php foreach (array('facebook','twitter','telegram','discord') as $sn): if (!lp($social, $sn)) continue; ?>
                                    <li><a href="<?php echo html_escape($social[$sn]); ?>">
                                        <div class="shape"><img src="<?php echo base_url('assets/img/icon/icons_bg.svg'); ?>" alt="shape"></div>
                                        <img src="<?php echo base_url('assets/img/icon/' . $sn . '.svg'); ?>" alt="icon" class="icon">
                                    </a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div></div>
            </div>
            <div class="footer__bottom">
                <?php $home_copyright = $site_copyright ?: lp($footer, 'copyright', lp($general, 'copyright', 'Copyright &amp; design by @ThemeAdapt - 2026')); ?>
                <div class="copyright-text"><p><?php echo html_escape($home_copyright); ?></p></div>
            </div>
        </div>
        <div class="footer__shape">
            <img src="<?php echo $a(lp($footer, 'bg_image1', 'assets/img/images/footer_shape01.png')); ?>" alt="shape" class="alltuchtopdown">
            <img src="<?php echo $a(lp($footer, 'bg_image2', 'assets/img/images/footer_shape02.png')); ?>" alt="shape" class="alltuchtopdown">
        </div>
    </footer>
    <!-- footer-area-end -->

    <!-- JS here -->
    <script src="<?php echo base_url('assets/js/vendor/jquery-3.6.0.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/bootstrap.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/swiper-bundle.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/jquery.marquee.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/ajax-form.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/jquery.countdown.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/jquery.counterup.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/jquery.easing.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/wow.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/aos.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/main.js'); ?>"></script>

    <!-- early-access form -> SMTP (Landing::early_access) -->
    <script>
    (function () {
        var f = document.getElementById('lpEarlyAccess');
        if (!f) return;
        var msg = document.getElementById('lpEarlyAccessMsg');
        f.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = f.querySelector('button[type=submit]');
            var original = btn.innerHTML;
            btn.disabled = true; btn.innerHTML = 'Sending...';
            msg.className = 'lp-form-msg';
            var data = new FormData(f);
            fetch(f.action, { method: 'POST', body: data, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    msg.textContent = res.message;
                    msg.className = 'lp-form-msg ' + (res.status ? 'ok' : 'err');
                    if (res.status) { f.reset(); if (res.redirect) { window.location.href = res.redirect; } }
                })
                .catch(function () { msg.textContent = 'Something went wrong. Please try again.'; msg.className = 'lp-form-msg err'; })
                .finally(function () { btn.disabled = false; btn.innerHTML = original; });
        });
    })();
    </script>

    <?php echo lp($scripts, 'custom_js'); ?>
    <?php echo lp($scripts, 'footer_scripts'); ?>
</body>
</html>
