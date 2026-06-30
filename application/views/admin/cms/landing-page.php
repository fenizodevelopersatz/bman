<!DOCTYPE html>
<html lang="en">

<?php $this->load->view('admin/Layout/common_style'); ?>
<style>
    .lp-preview-frame { width: 100%; height: 760px; border: 1px solid var(--bs-border-color); border-radius: .65rem; background:#fff; transition: width .25s ease; }
    .lp-preview-wrap  { display:flex; justify-content:center; }
    .lp-device-tablet .lp-preview-frame { width: 768px; }
    .lp-device-mobile .lp-preview-frame { width: 380px; }
    .lp-img-prev { max-height:60px; border-radius:.4rem; border:1px solid var(--bs-border-color); background:#f5f5f5; padding:3px; }
    .lp-rep-table img { max-height:34px; }
    .lp-handle { cursor:move; }
</style>

<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true"
    data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true"
    data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
    data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">

    <?php
    /* ---------- tiny render helpers to keep the 17 sections DRY ---------- */
    $base = base_url();

    // text input row
    function lp_text($label, $section, $key, $data, $ph = '') {
        $v = isset($data[$key]) ? htmlspecialchars($data[$key]) : '';
        echo '<div class="col-md-6 mb-5"><label class="form-label fw-semibold fs-7">'.$label.'</label>'
            .'<input type="text" name="'.$key.'" class="form-control form-control-solid" value="'.$v.'" placeholder="'.$ph.'"></div>';
    }
    // color picker + hex/rgba text (palette)
    function lp_color($label, $section, $key, $data, $default = '#7857FE') {
        $v = isset($data[$key]) ? htmlspecialchars($data[$key]) : '';
        $hex = preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? $v : $default;
        echo '<div class="col-md-6 mb-5"><label class="form-label fw-semibold fs-7">'.$label.'</label>'
            .'<div class="input-group">'
            .'<input type="color" class="form-control form-control-color lp-color-pick" value="'.$hex.'" title="Pick color" style="max-width:52px">'
            .'<input type="text" name="'.$key.'" class="form-control form-control-solid lp-color-text" value="'.$v.'" placeholder="'.$default.' or rgba(...)">'
            .'</div></div>';
    }
    // textarea row
    function lp_area($label, $section, $key, $data, $rows = 3) {
        $v = isset($data[$key]) ? htmlspecialchars($data[$key]) : '';
        echo '<div class="col-md-12 mb-5"><label class="form-label fw-semibold fs-7">'.$label.'</label>'
            .'<textarea name="'.$key.'" rows="'.$rows.'" class="form-control form-control-solid">'.$v.'</textarea></div>';
    }
    // code/raw textarea (scripts)
    function lp_code($label, $key, $data, $rows = 4) {
        $v = isset($data[$key]) ? htmlspecialchars($data[$key]) : '';
        echo '<div class="col-md-12 mb-5"><label class="form-label fw-semibold fs-7">'.$label.'</label>'
            .'<textarea name="'.$key.'" rows="'.$rows.'" class="form-control form-control-solid" style="font-family:monospace">'.$v.'</textarea></div>';
    }
    // switch
    function lp_switch($label, $key, $data) {
        $c = !empty($data[$key]) && $data[$key] != '0' ? 'checked' : '';
        echo '<div class="col-md-6 mb-5"><label class="form-label fw-semibold fs-7 d-block">'.$label.'</label>'
            .'<div class="form-check form-switch form-check-custom form-check-success form-check-solid">'
            .'<input class="form-check-input h-30px w-50px" type="checkbox" value="1" name="'.$key.'" '.$c.'></div></div>';
    }
    // image upload + preview
    function lp_image($label, $key, $data, $base) {
        $v = isset($data[$key]) ? $data[$key] : '';
        $src = $v ? ($base . $v) : '';
        echo '<div class="col-md-6 mb-5"><label class="form-label fw-semibold fs-7">'.$label.'</label>'
            .'<div class="d-flex align-items-center gap-3">'
            .'<img class="lp-img-prev lp-preview-target" src="'.$src.'" alt="" '.($src?'':'style="display:none"').'>'
            .'<input type="file" name="'.$key.'" accept=".png,.jpg,.jpeg,.gif,.svg,.webp" class="form-control form-control-solid lp-file">'
            .'</div><div class="text-muted fs-8 mt-1">png/jpg/jpeg/svg/webp, max 4MB. Current: '.htmlspecialchars($v).'</div></div>';
    }
    // section accordion wrapper open (singleton form)
    function lp_card_open($id, $title, $section, $save = true) {
        echo '<div class="accordion-item">'
            .'<h2 class="accordion-header"><button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#col_'.$id.'">'.$title.'</button></h2>'
            .'<div id="col_'.$id.'" class="accordion-collapse collapse" data-bs-parent="#lpAccordion"><div class="accordion-body">';
        if ($save) echo '<form class="lp-form" data-section="'.$section.'" enctype="multipart/form-data"><div class="row">';
    }
    function lp_card_close($save = true) {
        if ($save) echo '</div><div class="text-end"><button type="submit" class="btn btn-primary btn-sm">Save Section</button></div></form>';
        echo '</div></div></div>';
    }
    ?>

    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
            <?php $this->load->view('admin/Layout/admin_topbar'); ?>
            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
                <?php $this->load->view('admin/Layout/admin_sidebar'); ?>
                <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                    <div class="d-flex flex-column flex-column-fluid">

                        <!-- Toolbar -->
                        <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                                    <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 my-0">Landing Page Settings</h1>
                                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                        <li class="breadcrumb-item text-muted"><a href="<?php echo $base; ?>" class="text-muted text-hover-primary">Content Management</a></li>
                                        <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                        <li class="breadcrumb-item text-muted">Landing Page Settings</li>
                                    </ul>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <a href="<?php echo $base; ?>landing" target="_blank" class="btn btn-sm btn-light-primary"><i class="fa fa-eye"></i> Open Live</a>
                                    <a href="<?php echo $base; ?>landing-export" class="btn btn-sm btn-light-info"><i class="fa fa-download"></i> Export JSON</a>
                                    <button id="lpImportBtn" class="btn btn-sm btn-light-warning"><i class="fa fa-upload"></i> Import</button>
                                    <button id="lpSaveVersion" class="btn btn-sm btn-light-success"><i class="fa fa-save"></i> Save Version</button>
                                </div>
                            </div>
                        </div>

                        <div id="kt_app_content" class="app-content flex-column-fluid">
                            <div id="kt_app_content_container" class="app-container container-xxl">
                                <div class="row g-5">

                                    <!-- LEFT: section accordion -->
                                    <div class="col-xl-6">
                                        <div class="accordion" id="lpAccordion">

                                            <!-- ============ GENERAL ============ -->
                                            <?php lp_card_open('general','1. General Settings','general'); ?>
                                                <?php
                                                lp_text('Website Name','general','site_name',$general);
                                                lp_text('Font Family','general','font_family',$general,'Inter');
                                                ?>
                                                <div class="col-md-6 mb-5">
                                                    <label class="form-label fw-semibold fs-7">Theme Mode</label>
                                                    <?php $tm = isset($general['theme_mode']) ? $general['theme_mode'] : 'light'; ?>
                                                    <select name="theme_mode" class="form-select form-select-solid">
                                                        <option value="light" <?php echo $tm === 'light' ? 'selected' : ''; ?>>Light</option>
                                                        <option value="dark" <?php echo $tm === 'dark' ? 'selected' : ''; ?>>Dark</option>
                                                    </select>
                                                    <div class="text-muted fs-8 mt-1">Default theme of the public landing page. Visitors can still toggle.</div>
                                                </div>
                                                <?php
                                                lp_image('Logo','logo',$general,$base);
                                                lp_image('Logo (Dark)','logo_dark',$general,$base);
                                                lp_image('Favicon','favicon',$general,$base);
                                                lp_color('Primary Color','general','primary_color',$general,'#7857FE');
                                                lp_color('Secondary Color','general','secondary_color',$general,'#0B0B23');
                                                lp_color('Button Color','general','button_color',$general,'#7857FE');
                                                lp_color('Button Hover Color','general','button_hover_color',$general,'#5a3df0');
                                                lp_color('Background Color','general','background_color',$general,'#0B0B23');
                                                lp_switch('Enable Preloader','enable_preloader',$general);
                                                lp_switch('Enable Dark Mode','enable_dark_mode',$general);
                                                lp_text('Copyright','general','copyright',$general);
                                                lp_area('Footer Text','general','footer_text',$general,2);
                                                ?>
                                            <?php lp_card_close(); ?>

                                            <!-- ============ HEADER ============ -->
                                            <?php lp_card_open('header','2. Header / Navigation','header'); ?>
                                                <?php
                                                lp_image('Header Logo','logo',$header,$base);
                                                lp_image('Mobile Logo','mobile_logo',$header,$base);
                                                lp_text('Buy / CTA Button Text','header','buy_btn_text',$header);
                                                lp_text('Buy / CTA Button URL','header','buy_btn_url',$header);
                                                lp_switch('Sticky Header','sticky_header',$header);
                                                lp_switch('Transparent Header','transparent_header',$header);
                                                ?>
                                            <?php lp_card_close(); ?>
                                            <?php $this->load->view('admin/cms/_landing_repeater', array(
                                                'rep'=>'menu','title'=>'Navigation Menu','rows'=>$rep_menu,
                                                'cols'=>array('title'=>'Title','url'=>'URL'),
                                                'extra'=>array('new_tab'=>'New Tab','status'=>'Active'))); ?>

                                            <!-- ============ HERO ============ -->
                                            <?php lp_card_open('hero','3. Hero Banner','hero'); ?>
                                                <?php
                                                lp_text('Small Title','hero','small_title',$hero);
                                                lp_text('Main Title','hero','main_title',$hero);
                                                lp_text('Highlight Text (inside main title)','hero','highlight_text',$hero);
                                                lp_area('Description','hero','description',$hero);
                                                lp_text('Email Placeholder','hero','email_placeholder',$hero);
                                                lp_text('Button Text','hero','button_text',$hero);
                                                lp_text('Button Link','hero','button_link',$hero);
                                                lp_text('Bottom Text','hero','bottom_text',$hero);
                                                lp_text('Bottom Link Text','hero','bottom_link_text',$hero);
                                                lp_text('Bottom Link URL','hero','bottom_link',$hero);
                                                lp_text('Form Success Message','hero','success_message',$hero,'Thank you! We will be in touch soon.');
                                                lp_image('Background Image','bg_image',$hero,$base);
                                                lp_image('Hero Image 1','hero_img1',$hero,$base);
                                                lp_image('Hero Image 2','hero_img2',$hero,$base);
                                                lp_image('Hero Image 3','hero_img3',$hero,$base);
                                                ?>
                                            <?php lp_card_close(); ?>

                                            <!-- ============ BRANDS ============ -->
                                            <?php $this->load->view('admin/cms/_landing_repeater', array(
                                                'rep'=>'brands','title'=>'4. Brands','rows'=>$rep_brands,
                                                'cols'=>array('alt'=>'Alt text'),
                                                'images'=>array('image'=>'Logo'),
                                                'extra'=>array('status'=>'Active'))); ?>

                                            <!-- ============ FEATURES ============ -->
                                            <?php lp_card_open('features','5. Features (heading)','features'); ?>
                                                <?php lp_text('Sub Title','features','sub_title',$features);
                                                lp_text('Title','features','title',$features);
                                                lp_text('Highlight Word','features','highlight',$features); ?>
                                            <?php lp_card_close(); ?>
                                            <?php $this->load->view('admin/cms/_landing_repeater', array(
                                                'rep'=>'features','title'=>'Feature Items','rows'=>$rep_features,
                                                'cols'=>array('title'=>'Title','highlight'=>'Highlight','description'=>'Description'),
                                                'images'=>array('icon'=>'Icon'),
                                                'extra'=>array('status'=>'Active'))); ?>

                                            <!-- ============ MARQUEE ============ -->
                                            <?php lp_card_open('marquee','6. Marquee','marquee'); ?>
                                                <?php lp_text('Scrolling Text','marquee','text',$marquee);
                                                lp_text('Animation Speed','marquee','speed',$marquee);
                                                lp_text('Repeat Count','marquee','repeat',$marquee);
                                                lp_switch('Enable','enable',$marquee); ?>
                                            <?php lp_card_close(); ?>

                                            <!-- ============ TOKEN ============ -->
                                            <?php lp_card_open('token','7. Token Sale','token'); ?>
                                                <?php lp_text('Sub Title','token','sub_title',$token);
                                                lp_text('Title','token','title',$token);
                                                lp_text('Highlight Word','token','highlight',$token);
                                                lp_area('Description','token','description',$token);
                                                lp_text('Button Text','token','button_text',$token);
                                                lp_text('Button Link','token','button_link',$token);
                                                lp_text('Countdown Date (YYYY/MM/DD)','token','countdown_date',$token);
                                                lp_text('Contribution Amount','token','contribution_amount',$token);
                                                lp_text('Received Text','token','received_text',$token);
                                                lp_text('Minimum Goal','token','min_goal',$token);
                                                lp_text('Maximum Goal','token','max_goal',$token);
                                                lp_text('Wallet Address','token','wallet_address',$token);
                                                lp_text('Progress Percentage','token','progress_percentage',$token); ?>
                                            <?php lp_card_close(); ?>

                                            <!-- ============ HOW IT WORKS ============ -->
                                            <?php lp_card_open('work','8. How It Works (heading)','work'); ?>
                                                <?php lp_text('Sub Title','work','sub_title',$work);
                                                lp_text('Title','work','title',$work);
                                                lp_text('Highlight Word','work','highlight',$work);
                                                lp_image('Center Image','image',$work,$base); ?>
                                            <?php lp_card_close(); ?>
                                            <?php $this->load->view('admin/cms/_landing_repeater', array(
                                                'rep'=>'work','title'=>'Work Steps','rows'=>$rep_work,
                                                'cols'=>array('number'=>'No.','title'=>'Title','highlight'=>'Highlight','description'=>'Description'),
                                                'extra'=>array('status'=>'Active'))); ?>

                                            <!-- ============ EXCHANGE ============ -->
                                            <?php lp_card_open('exchange','9. Exchange','exchange'); ?>
                                                <?php lp_text('Title','exchange','title',$exchange);
                                                lp_text('Highlight Word','exchange','highlight',$exchange);
                                                lp_area('Description','exchange','description',$exchange);
                                                lp_image('Main Image','main_image',$exchange,$base);
                                                lp_switch('Enable','enable',$exchange); ?>
                                            <?php lp_card_close(); ?>
                                            <?php $this->load->view('admin/cms/_landing_repeater', array(
                                                'rep'=>'exchange_logos','title'=>'Exchange Logos','rows'=>$rep_exchange_logos,
                                                'cols'=>array(),'images'=>array('image'=>'Logo'),
                                                'extra'=>array('status'=>'Active'))); ?>

                                            <!-- ============ CRYPTO ============ -->
                                            <?php lp_card_open('crypto','10. Crypto Features (heading)','crypto'); ?>
                                                <?php lp_text('Sub Title','crypto','sub_title',$crypto);
                                                lp_text('Title','crypto','title',$crypto);
                                                lp_text('Highlight Word','crypto','highlight',$crypto); ?>
                                            <?php lp_card_close(); ?>
                                            <?php $this->load->view('admin/cms/_landing_repeater', array(
                                                'rep'=>'crypto','title'=>'Crypto Cards','rows'=>$rep_crypto,
                                                'cols'=>array('title'=>'Title','highlight'=>'Highlight','button_text'=>'Btn Text','button_link'=>'Btn Link'),
                                                'images'=>array('icon'=>'Icon'),
                                                'extra'=>array('status'=>'Active'))); ?>

                                            <!-- ============ FAQ ============ -->
                                            <?php lp_card_open('faq','11. FAQ (heading)','faq'); ?>
                                                <?php lp_text('Sub Title','faq','sub_title',$faq);
                                                lp_text('Title','faq','title',$faq);
                                                lp_text('Highlight Word','faq','highlight',$faq);
                                                lp_image('Side Image','image',$faq,$base); ?>
                                            <?php lp_card_close(); ?>
                                            <?php $this->load->view('admin/cms/_landing_repeater', array(
                                                'rep'=>'faq','title'=>'FAQ Items','rows'=>$rep_faq,
                                                'cols'=>array('question'=>'Question','answer'=>'Answer'),
                                                'extra'=>array('status'=>'Active'))); ?>

                                            <!-- ============ ROADMAP ============ -->
                                            <?php lp_card_open('roadmap','12. Roadmap (heading)','roadmap'); ?>
                                                <?php lp_text('Sub Title','roadmap','sub_title',$roadmap);
                                                lp_text('Title','roadmap','title',$roadmap);
                                                lp_text('Highlight Word','roadmap','highlight',$roadmap); ?>
                                            <?php lp_card_close(); ?>
                                            <?php $this->load->view('admin/cms/_landing_repeater', array(
                                                'rep'=>'roadmap','title'=>'Roadmap Items','rows'=>$rep_roadmap,
                                                'cols'=>array('year'=>'Year','title'=>'Title','description'=>'Description'),
                                                'images'=>array('icon'=>'Icon'),
                                                'extra'=>array('status'=>'Active'))); ?>

                                            <!-- ============ TEAM ============ -->
                                            <?php lp_card_open('team','13. Team (heading)','team'); ?>
                                                <?php lp_text('Sub Title','team','sub_title',$team);
                                                lp_text('Title','team','title',$team);
                                                lp_text('Highlight Word','team','highlight',$team);
                                                lp_area('Description','team','description',$team); ?>
                                            <?php lp_card_close(); ?>
                                            <?php $this->load->view('admin/cms/_landing_repeater', array(
                                                'rep'=>'team','title'=>'Team Members','rows'=>$rep_team,
                                                'cols'=>array('name'=>'Name','position'=>'Position','facebook'=>'Facebook','twitter'=>'Twitter','telegram'=>'Telegram','linkedin'=>'LinkedIn'),
                                                'images'=>array('photo'=>'Photo'),
                                                'extra'=>array('status'=>'Active'))); ?>

                                            <!-- ============ FOOTER ============ -->
                                            <?php lp_card_open('footer','14. Footer','footer'); ?>
                                                <?php lp_image('Footer Logo','logo',$footer,$base);
                                                lp_text('Sub Title','footer','sub_title',$footer);
                                                lp_text('Title','footer','title',$footer);
                                                lp_text('Highlight Word','footer','highlight',$footer);
                                                lp_text('Copyright','footer','copyright',$footer);
                                                lp_image('Background Shape 1','bg_image1',$footer,$base);
                                                lp_image('Background Shape 2','bg_image2',$footer,$base); ?>
                                            <?php lp_card_close(); ?>

                                            <!-- ============ SEO ============ -->
                                            <?php lp_card_open('seo','15. SEO','seo'); ?>
                                                <?php lp_text('Meta Title','seo','meta_title',$seo);
                                                lp_area('Meta Description','seo','meta_description',$seo);
                                                lp_text('Meta Keywords','seo','meta_keywords',$seo);
                                                lp_image('OpenGraph Image','og_image',$seo,$base);
                                                lp_text('Twitter Card','seo','twitter_card',$seo);
                                                lp_text('Robots','seo','robots',$seo);
                                                lp_text('Canonical URL','seo','canonical',$seo); ?>
                                            <?php lp_card_close(); ?>

                                            <!-- ============ SOCIAL ============ -->
                                            <?php lp_card_open('social','16. Social Links','social'); ?>
                                                <?php foreach (array('facebook','twitter','telegram','discord','instagram','linkedin','youtube','github') as $sn) lp_text(ucfirst($sn),'social',$sn,$social); ?>
                                            <?php lp_card_close(); ?>

                                            <!-- ============ SCRIPTS ============ -->
                                            <?php lp_card_open('scripts','17. Custom Scripts','scripts'); ?>
                                                <?php lp_code('Header Scripts','header_scripts',$scripts);
                                                lp_code('Footer Scripts','footer_scripts',$scripts);
                                                lp_code('Google Analytics','google_analytics',$scripts);
                                                lp_code('Facebook Pixel','facebook_pixel',$scripts);
                                                lp_code('Custom CSS','custom_css',$scripts);
                                                lp_code('Custom JS','custom_js',$scripts); ?>
                                            <?php lp_card_close(); ?>

                                            <!-- ============ VERSION HISTORY ============ -->
                                            <?php lp_card_open('versions','18. Version History','versions',false); ?>
                                                <div class="table-responsive"><table class="table table-row-bordered align-middle">
                                                <thead><tr class="fw-bold fs-7 text-muted"><th>Label</th><th>Date</th><th class="text-end">Action</th></tr></thead><tbody>
                                                <?php if (!empty($versions)): foreach ($versions as $v): ?>
                                                    <tr><td><?php echo htmlspecialchars($v->label); ?></td>
                                                    <td class="text-muted fs-8"><?php echo $v->created_at; ?></td>
                                                    <td class="text-end"><a href="<?php echo $base; ?>landing-restore-version/<?php echo $v->id; ?>" class="btn btn-sm btn-light-warning lp-restore">Restore</a></td></tr>
                                                <?php endforeach; else: ?>
                                                    <tr><td colspan="3" class="text-muted">No versions yet. Use "Save Version" to snapshot.</td></tr>
                                                <?php endif; ?>
                                                </tbody></table></div>
                                            <?php lp_card_close(false); ?>

                                        </div>
                                    </div>

                                    <!-- RIGHT: live preview -->
                                    <div class="col-xl-6">
                                        <div class="card card-flush sticky-top" style="top:90px">
                                            <div class="card-header pt-5">
                                                <h3 class="card-title fw-bold">Live Preview</h3>
                                                <div class="card-toolbar">
                                                    <div class="btn-group me-2" role="group">
                                                        <button class="btn btn-sm btn-light-primary active lp-device" data-device="desktop"><i class="fa fa-desktop"></i></button>
                                                        <button class="btn btn-sm btn-light-primary lp-device" data-device="tablet"><i class="fa fa-tablet-alt"></i></button>
                                                        <button class="btn btn-sm btn-light-primary lp-device" data-device="mobile"><i class="fa fa-mobile-alt"></i></button>
                                                    </div>
                                                    <div class="btn-group me-2" role="group">
                                                        <button class="btn btn-sm btn-light-warning active lp-theme" data-theme="light"><i class="fa fa-sun"></i></button>
                                                        <button class="btn btn-sm btn-light-dark lp-theme" data-theme="dark"><i class="fa fa-moon"></i></button>
                                                    </div>
                                                    <div class="btn-group" role="group">
                                                        <button class="btn btn-sm btn-light-success lp-refresh" title="Refresh"><i class="fa fa-sync"></i></button>
                                                        <a class="btn btn-sm btn-light-info lp-newtab" target="_blank" title="Open in new tab"><i class="fa fa-external-link-alt"></i></a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body lp-preview-wrap lp-device-desktop" id="lpPreviewWrap">
                                                <iframe id="lpPreview" class="lp-preview-frame" src="<?php echo $base; ?>landing"></iframe>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <?php $this->load->view('admin/Layout/admin_footer'); ?>
                    </div>
                </div>
            </div>

            <!-- hidden import file input -->
            <form id="lpImportForm" enctype="multipart/form-data" style="display:none">
                <input type="file" name="import_file" id="lpImportFile" accept=".json">
            </form>

            <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
                <i class="ki-duotone ki-arrow-up"><span class="path1"></span><span class="path2"></span></i>
            </div>
            <?php $this->load->view('admin/Layout/common_script'); ?>
            <script>var LP_BASE = "<?php echo $base; ?>";</script>
            <script src="<?php echo $base; ?>assets/admin/js/custom/cms/landing-page.js"></script>
</body>
</html>
