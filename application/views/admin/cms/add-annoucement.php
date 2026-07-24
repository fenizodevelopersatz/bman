<?php $this->load->view('admin/Layout/common_style');?>

    <link href="<?php echo base_url();?>/assets/admin/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css">
    <link href="<?php echo base_url();?>/assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.css" rel="stylesheet" type="text/css">
    <link href="<?php echo base_url();?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />

    <style>
        .h-md-40{
            min-height:42%;
        }
        .verify-symbol {
        color: green;
        font-weight: bold;
        margin-left: 5px;
        }
        .verified {
        border-color: green;
        }
        #ann-crop-stage {
            position: relative;
            width: 600px;
            max-width: 100%;
            height: 300px;
            background: #0b0b0f;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #ann-crop-box {
            position: absolute;
            border: 2px dashed #fff;
            box-shadow: 0 0 0 2000px rgba(0,0,0,.45);
            cursor: move;
            touch-action: none;
        }
        .ann-handle {
            position: absolute;
            right: -7px;
            bottom: -7px;
            width: 14px;
            height: 14px;
            background: #fff;
            border: 2px solid #6E56CF;
            border-radius: 50%;
            cursor: nwse-resize;
            touch-action: none;
        }

        /* Live preview — mirrors the member-dashboard banner's own markup
           (hero-grid / has-image / hero-left) closely enough to look right,
           but uses its own ann-preview-* classes rather than reusing those
           class names directly, since this page's Metronic/Bootstrap theme
           already defines .tag/.btn globally and would collide with them. */
        #ann-preview-box {
            position: relative;
            height: 220px;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 8px;
            color: #fff;
            background: linear-gradient(135deg,#6C4CF1,#4E2CF0);
            transition: background .2s ease;
        }
        #ann-preview-bg {
            position: absolute; inset: 0;
            background-size: cover; background-position: center;
            display: none;
        }
        #ann-preview-scrim {
            position: absolute; inset: 0;
            background: linear-gradient(90deg, rgba(11,15,26,.75) 0%, rgba(11,15,26,.35) 55%, rgba(11,15,26,.10) 100%);
            display: none;
        }
        #ann-preview-content {
            position: relative; z-index: 2; height: 100%;
            display: flex; flex-direction: column; padding: 24px;
        }
        #ann-preview-content.pos-top-left { justify-content: flex-start; align-items: flex-start; text-align: left; }
        #ann-preview-content.pos-middle-left { justify-content: center; align-items: flex-start; text-align: left; }
        #ann-preview-content.pos-bottom-left { justify-content: flex-end; align-items: flex-start; text-align: left; }
        #ann-preview-content.pos-center { justify-content: center; align-items: center; text-align: center; }
        #ann-preview-tag {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.18);
            padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 600;
            width: fit-content; margin-bottom: 8px;
        }
        #ann-preview-category { font-size: 11px; font-weight: 700; margin-bottom: 4px; }
        #ann-preview-title { font-size: 20px; font-weight: 700; line-height: 1.2; margin: 0 0 6px; max-width: 85%; }
        #ann-preview-subtitle { font-size: 12px; font-weight: 700; opacity: .85; margin-bottom: 4px; max-width: 85%; }
        #ann-preview-desc { font-size: 12px; opacity: .9; max-width: 85%; line-height: 1.5; margin-bottom: 10px; }
        #ann-preview-btn {
            display: inline-flex; align-items: center; gap: 6px; width: fit-content;
            background: #111; color: #fff; padding: 8px 14px; border-radius: 999px;
            font-size: 12px; font-weight: 700; text-decoration: none;
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


                        <div id="kt_account_addagent_form_details" class="collapse show">
                        <div class="card-body border-top p-9">
                        
                        <?php $action = base_url()."announcement-add"; ?>
                        <?= form_open($action, ['class' => 'form-validate', 'method' => 'post', 'autocomplete' => 'off', 'id' => 'kt_account_meta_details_form',"data-kt-redirect-url"=> base_url()."announcement-cms"]) ?>
                        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>" />  
                                      
                 
                        <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Type<span class="text-danger"> * </span></label>
                        <div class="col-lg-8 fv-row">
                        <div class="d-flex gap-6">
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="radio" name="announcement_type" value="text"
                                    <?php echo ($announcement_type === 'text') ? 'checked' : ''; ?>>
                                <span class="form-check-label">Text Only</span>
                            </label>
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="radio" name="announcement_type" value="image"
                                    <?php echo ($announcement_type === 'image') ? 'checked' : ''; ?>>
                                <span class="form-check-label">Image Only</span>
                            </label>
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="radio" name="announcement_type" value="text_image"
                                    <?php echo ($announcement_type === 'text_image') ? 'checked' : ''; ?>>
                                <span class="form-check-label">Text + Image</span>
                            </label>
                        </div>
                        </div>
                        </div>

                        <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Category</label>
                        <div class="col-lg-8 fv-row">
                            <select name="category" class="form-select form-select-solid">
                                <?php foreach ([
                                    'general' => 'General', 'alert' => 'Alert Message', 'promotion' => 'Promotion Banner',
                                    'maintenance' => 'Maintenance Notice', 'event' => 'Event Announcement', 'rank_news' => 'Rank Achievement News',
                                ] as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= $category === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="text-muted fs-8 mt-1">Alert / Maintenance always render with the red emergency style, regardless of background color chosen below.</div>
                        </div>
                        </div>

                        <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Subtitle</label>
                        <div class="col-lg-8 fv-row">
                            <input type="text" name="subtitle" class="form-control form-control-lg form-control-solid"
                                placeholder="e.g. Withdrawal requests will be processed every Friday."
                                value="<?php echo htmlspecialchars($subtitle ?? ''); ?>">
                        </div>
                        </div>

                        <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Description</label>
                        <div class="col-lg-8 fv-row">
                            <textarea name="description" rows="3" class="form-control form-control-lg form-control-solid"
                                placeholder="Please complete KYC and bank verification before requesting payout."><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                        </div>
                        </div>

                        <div id="text-section">
                            <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">Announcement Text<span class="text-danger"> * </span></label>
                            <div class="col-lg-8 fv-row">
                            <div class="input-group mb-5">
                            <span class="input-group-text border-transparent " id="basic-addon1"><i class="fa-solid fa-note-sticky "></i></span>
                            <textarea name="announcement_content"
                            class="form-control form-control-lg form-control-solid"
                            placeholder="Enter Announcement Content"><?php echo htmlspecialchars($announcement_content); ?></textarea>
                            </div>
                            </div>
                            </div>

                            <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">Background Style</label>
                            <div class="col-lg-8 fv-row">
                                <div class="d-flex gap-2 mb-3 flex-wrap align-items-center" id="ann-gradient-presets">
                                    <button type="button" class="btn btn-sm" data-grad="linear-gradient(135deg,#6C4CF1,#4E2CF0)" style="background:linear-gradient(135deg,#6C4CF1,#4E2CF0);color:#fff;">Purple</button>
                                    <button type="button" class="btn btn-sm" data-grad="linear-gradient(135deg,#3B82F6,#1D4ED8)" style="background:linear-gradient(135deg,#3B82F6,#1D4ED8);color:#fff;">Blue</button>
                                    <button type="button" class="btn btn-sm" data-grad="linear-gradient(135deg,#22C55E,#15803D)" style="background:linear-gradient(135deg,#22C55E,#15803D);color:#fff;">Green</button>
                                    <button type="button" class="btn btn-sm" data-grad="linear-gradient(135deg,#F59E0B,#C2410C)" style="background:linear-gradient(135deg,#F59E0B,#C2410C);color:#fff;">Orange</button>
                                    <button type="button" class="btn btn-sm" data-grad="linear-gradient(135deg,#EF4444,#B91C1C)" style="background:linear-gradient(135deg,#EF4444,#B91C1C);color:#fff;">Red</button>
                                    <span class="border-start ps-2 ms-1 d-inline-flex align-items-center gap-2">
                                        <input type="color" id="ann-bg-color-picker" title="Pick a solid color"
                                            style="width:44px;height:34px;padding:3px;border:1px solid #e1e3ea;border-radius:8px;cursor:pointer;background:#fff;"
                                            value="#6E56CF">
                                        <span class="text-muted fs-8">Solid color</span>
                                    </span>
                                </div>
                                <input type="text" name="bg_color" id="ann-bg-color" class="form-control form-control-lg form-control-solid"
                                    placeholder="#6E56CF or linear-gradient(...)"
                                    value="<?php echo htmlspecialchars($bg_color); ?>">
                                <div class="text-muted fs-8 mt-1">Click a gradient preset, pick a solid color, or type a custom color / CSS gradient.</div>
                            </div>
                            </div>

                            <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">Text Color</label>
                            <div class="col-lg-8 fv-row">
                                <div class="d-flex gap-4 align-items-center">
                                    <label class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input ann-text-color-radio" type="radio" data-color="#ffffff" <?= ($text_color ?? '#ffffff') === '#ffffff' ? 'checked' : '' ?>>
                                        <span class="form-check-label">White</span>
                                    </label>
                                    <label class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input ann-text-color-radio" type="radio" data-color="#000000" <?= ($text_color ?? '') === '#000000' ? 'checked' : '' ?>>
                                        <span class="form-check-label">Black</span>
                                    </label>
                                    <input type="color" id="ann-text-color-custom" class="form-control form-control-lg form-control-solid w-75px"
                                        value="<?php echo htmlspecialchars($text_color ?? '#ffffff'); ?>">
                                    <input type="hidden" name="text_color" id="ann-text-color" value="<?php echo htmlspecialchars($text_color ?? '#ffffff'); ?>">
                                </div>
                            </div>
                            </div>
                        </div>

                        <div id="image-section" style="display:none;" data-has-image="<?php echo !empty($image) ? '1' : '0'; ?>">
                            <?php if (!empty($image)): ?>
                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Current Image</label>
                                <div class="col-lg-8 fv-row">
                                    <img src="<?php echo base_url($image); ?>" style="max-width:300px;border-radius:8px;border:1px solid #333;">
                                    <div class="text-muted fs-8 mt-1">Choose a new file below to replace it.</div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">Image<span class="text-danger"> * </span></label>
                            <div class="col-lg-8 fv-row">
                                <input type="file" id="ann-file-input" accept="image/png,image/jpeg,image/webp" class="form-control form-control-lg form-control-solid mb-2">
                                <div class="text-muted fs-8 mb-2">JPG, PNG or WEBP, max 3MB. Recommended source size 1600×500 (min 1200×350) — crop below to 1200×400.</div>
                                <div id="ann-dimension-warning" class="mb-4" style="display:none;"></div>

                                <div id="ann-cropper-wrap" style="display:none;">
                                    <div id="ann-crop-stage">
                                        <canvas id="ann-src-canvas"></canvas>
                                        <div id="ann-crop-box">
                                            <div class="ann-handle"></div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-3 mt-3">
                                        <label class="fs-8 text-muted mb-0">Zoom</label>
                                        <input type="range" id="ann-zoom" min="100" max="300" value="100" class="form-range" style="width:200px">
                                        <button type="button" class="btn btn-sm btn-primary ms-auto" id="ann-apply-crop">Apply Crop</button>
                                    </div>
                                </div>

                                <div id="ann-crop-preview-wrap" style="display:none;" class="mt-4">
                                    <label class="form-label fs-8">Cropped Preview</label><br>
                                    <img id="ann-crop-preview" style="max-width:400px;border-radius:8px;border:1px solid #333;">
                                </div>

                                <input type="hidden" name="cropped_image_data" id="ann-cropped-data">
                            </div>
                            </div>
                        </div>

                        <div class="separator my-8"></div>

                        <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Text Position</label>
                        <div class="col-lg-8 fv-row">
                            <select name="text_position" id="ann-text-position" class="form-select form-select-solid">
                                <?php $tp = $text_position ?? 'middle-left'; ?>
                                <option value="middle-left" <?= $tp === 'middle-left' ? 'selected' : '' ?>>Middle Left (Default)</option>
                                <option value="top-left" <?= $tp === 'top-left' ? 'selected' : '' ?>>Top Left</option>
                                <option value="bottom-left" <?= $tp === 'bottom-left' ? 'selected' : '' ?>>Bottom Left</option>
                                <option value="center" <?= $tp === 'center' ? 'selected' : '' ?>>Center</option>
                            </select>
                            <div class="text-muted fs-8 mt-1">Controls where the title/description sit over the banner — most useful for Image or Text+Image announcements.</div>
                        </div>
                        </div>

                        <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Button Text</label>
                        <div class="col-lg-8 fv-row">
                            <input type="text" name="button_text" class="form-control form-control-lg form-control-solid"
                                placeholder="Withdraw Now" value="<?php echo htmlspecialchars($button_text ?? ''); ?>">
                        </div>
                        </div>

                        <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Button URL</label>
                        <div class="col-lg-8 fv-row">
                            <input type="text" name="button_url" class="form-control form-control-lg form-control-solid"
                                placeholder="/user/payouts" value="<?php echo htmlspecialchars($button_url ?? ''); ?>">
                            <div class="text-muted fs-8 mt-1">Leave both blank to hide the button entirely.</div>
                        </div>
                        </div>

                        <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Priority</label>
                        <div class="col-lg-8 fv-row">
                            <select name="priority" class="form-select form-select-solid">
                                <option value="low" <?= $priority === 'low' ? 'selected' : '' ?>>🔵 Low</option>
                                <option value="medium" <?= $priority === 'medium' ? 'selected' : '' ?>>🟡 Medium</option>
                                <option value="high" <?= $priority === 'high' ? 'selected' : '' ?>>🟠 High</option>
                                <option value="critical" <?= $priority === 'critical' ? 'selected' : '' ?>>🔴 Critical</option>
                            </select>
                            <div class="text-muted fs-8 mt-1">Higher priority announcements show first in the rotation.</div>
                        </div>
                        </div>

                        <!-- Display Mode removed — announcements always render as a banner. -->
                        <input type="hidden" name="display_mode" value="banner">

                        <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Show To</label>
                        <div class="col-lg-8 fv-row">
                            <select name="target_type" id="ann-target-type" class="form-select form-select-solid mb-3">
                                <option value="all" <?= $target_type === 'all' ? 'selected' : '' ?>>All Users</option>
                                <option value="active" <?= $target_type === 'active' ? 'selected' : '' ?>>Active Users</option>
                                <option value="inactive" <?= $target_type === 'inactive' ? 'selected' : '' ?>>Inactive Users</option>
                                <option value="kyc_pending" <?= $target_type === 'kyc_pending' ? 'selected' : '' ?>>KYC Pending</option>
                                <option value="kyc_approved" <?= $target_type === 'kyc_approved' ? 'selected' : '' ?>>KYC Approved</option>
                                <option value="rank" <?= $target_type === 'rank' ? 'selected' : '' ?>>Specific Rank</option>
                                <option value="package" <?= $target_type === 'package' ? 'selected' : '' ?>>Specific Package</option>
                                <option value="country" <?= $target_type === 'country' ? 'selected' : '' ?>>Specific Country</option>
                            </select>

                            <select name="target_value_rank" id="ann-target-rank" class="form-select form-select-solid" style="display:none;">
                                <?php foreach (($ranks ?? []) as $r): ?>
                                    <option value="<?= htmlspecialchars($r->name) ?>" <?= ($target_value ?? '') === $r->name ? 'selected' : '' ?>><?= htmlspecialchars($r->name) ?></option>
                                <?php endforeach; ?>
                            </select>

                            <select name="target_value_package" id="ann-target-package" class="form-select form-select-solid" style="display:none;">
                                <?php foreach (($packages ?? []) as $p): ?>
                                    <option value="<?= (int) $p->id ?>" <?= ($target_value ?? '') == $p->id ? 'selected' : '' ?>><?= htmlspecialchars($p->name) ?></option>
                                <?php endforeach; ?>
                            </select>

                            <input type="text" name="target_value_country" id="ann-target-country" class="form-control form-control-lg form-control-solid"
                                placeholder="e.g. IN, US" style="display:none;"
                                value="<?= (($target_type ?? '') === 'country') ? htmlspecialchars($target_value ?? '') : '' ?>">

                            <input type="hidden" name="target_value" id="ann-target-value" value="<?php echo htmlspecialchars($target_value ?? ''); ?>">
                        </div>
                        </div>

                        <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Schedule</label>
                        <div class="col-lg-8 fv-row">
                            <div class="d-flex gap-3 align-items-center">
                                <input type="date" name="start_date" class="form-control form-control-lg form-control-solid" value="<?php echo htmlspecialchars($start_date ?? ''); ?>">
                                <span class="text-muted">to</span>
                                <input type="date" name="end_date" class="form-control form-control-lg form-control-solid" value="<?php echo htmlspecialchars($end_date ?? ''); ?>">
                            </div>
                            <div class="text-muted fs-8 mt-1">Leave both blank to run indefinitely (as long as Status is active).</div>
                        </div>
                        </div>

                        <input type="hidden" name="announcement_id" value="<?php echo $announcement_id; ?>" />

                        <div class="separator my-8"></div>

                        <div class="row mb-8">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Live Preview</label>
                        <div class="col-lg-8 fv-row">
                            <div id="ann-preview-box">
                                <div id="ann-preview-bg"></div>
                                <div id="ann-preview-scrim"></div>
                                <div id="ann-preview-content" class="pos-middle-left">
                                    <div id="ann-preview-tag"><i class="fa-solid fa-bullhorn"></i> Announcement</div>
                                    <div id="ann-preview-category" style="display:none;"></div>
                                    <div id="ann-preview-title"></div>
                                    <div id="ann-preview-subtitle" style="display:none;"></div>
                                    <div id="ann-preview-desc" style="display:none;"></div>
                                    <a id="ann-preview-btn" href="javascript:void(0)" style="display:none;"></a>
                                </div>
                            </div>
                            <div class="text-muted fs-8">This mirrors exactly how the banner will look on the member dashboard — updates live as you edit the fields above.</div>
                        </div>
                        </div>

                        <div class="col-md-12">
                        <div class="form-group"><button type="submit" id="kt_account_meta_details_submit"
                        class="btn btn-lg btn-primary">Save</button>
                        </div>
                        </div>

                    </div>
                    </div>

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
            <script src="<?php echo base_url();?>/assets/admin/js/custom/authentication/sign-in/announcement-edit-settings.js?ver=2.9"></script>
            <script src="<?php echo base_url();?>/assets/admin/js/custom/authentication/sign-in/announcement-cropper.js?ver=1.0"></script>

            <script>
            document.addEventListener('DOMContentLoaded', function () {
                /* ---- gradient presets fill the bg_color field ---- */
                document.querySelectorAll('#ann-gradient-presets button').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        document.getElementById('ann-bg-color').value = btn.getAttribute('data-grad');
                    });
                });

                /* ---- text color: radio (white/black) or custom picker -> hidden field ---- */
                var textColorHidden = document.getElementById('ann-text-color');
                var textColorCustom = document.getElementById('ann-text-color-custom');
                document.querySelectorAll('.ann-text-color-radio').forEach(function (r) {
                    r.addEventListener('change', function () { textColorHidden.value = r.getAttribute('data-color'); });
                });
                textColorCustom.addEventListener('input', function () {
                    document.querySelectorAll('.ann-text-color-radio').forEach(function (r) { r.checked = false; });
                    textColorHidden.value = textColorCustom.value;
                });

                /* ---- target_type -> show the matching value picker, sync hidden field ---- */
                var targetType = document.getElementById('ann-target-type');
                var targetValueHidden = document.getElementById('ann-target-value');
                var pickers = {
                    rank: document.getElementById('ann-target-rank'),
                    package: document.getElementById('ann-target-package'),
                    country: document.getElementById('ann-target-country'),
                };
                function refreshTargetUI() {
                    var type = targetType.value;
                    Object.keys(pickers).forEach(function (k) { pickers[k].style.display = (k === type) ? '' : 'none'; });
                    if (pickers[type]) targetValueHidden.value = pickers[type].value;
                    else targetValueHidden.value = '';
                }
                targetType.addEventListener('change', refreshTargetUI);
                Object.values(pickers).forEach(function (el) {
                    el.addEventListener('input', function () { targetValueHidden.value = el.value; });
                    el.addEventListener('change', function () { targetValueHidden.value = el.value; });
                });
                refreshTargetUI();
            });
            </script>

            <script>
            /* ---- Live preview: mirrors application/views/user/dashboard/index.php's
               announcement banner markup so admins see the real result while editing,
               instead of raw form fields + literal placeholder text. ---- */
            document.addEventListener('DOMContentLoaded', function () {
                var els = {
                    box: document.getElementById('ann-preview-box'),
                    bg: document.getElementById('ann-preview-bg'),
                    scrim: document.getElementById('ann-preview-scrim'),
                    content: document.getElementById('ann-preview-content'),
                    tag: document.getElementById('ann-preview-tag'),
                    category: document.getElementById('ann-preview-category'),
                    title: document.getElementById('ann-preview-title'),
                    subtitle: document.getElementById('ann-preview-subtitle'),
                    desc: document.getElementById('ann-preview-desc'),
                    btn: document.getElementById('ann-preview-btn'),
                };
                var cropPreview = document.getElementById('ann-crop-preview');
                var cropPreviewWrap = document.getElementById('ann-crop-preview-wrap');
                var fileInput = document.getElementById('ann-file-input');
                var bgPicker = document.getElementById('ann-bg-color-picker');
                var bgField = document.getElementById('ann-bg-color');

                // Image already saved on this announcement (edit mode).
                var EXISTING_IMAGE = '<?= !empty($image) ? htmlspecialchars(base_url($image), ENT_QUOTES) : '' ?>';
                // The raw file the admin just picked, shown instantly (before "Apply Crop").
                var rawSelectedImage = '';

                function val(selector, fallback) {
                    var el = document.querySelector(selector);
                    return el ? el.value : (fallback || '');
                }

                // Image the preview should show, in priority order:
                // 1) freshly cropped result, 2) freshly picked (pre-crop) file, 3) saved image.
                function effectiveImage() {
                    if (cropPreview && cropPreviewWrap && cropPreviewWrap.style.display !== 'none' && cropPreview.getAttribute('src')) {
                        return cropPreview.getAttribute('src');
                    }
                    if (rawSelectedImage) return rawSelectedImage;
                    return EXISTING_IMAGE;
                }

                function updateAnnPreview() {
                    var type = val('input[name="announcement_type"]:checked', 'text');
                    var category = val('select[name="category"]', 'general');
                    var isAlert = category === 'alert' || category === 'maintenance';
                    var title = val('textarea[name="announcement_content"]') || 'Your announcement title';
                    var subtitle = val('input[name="subtitle"]');
                    var description = val('textarea[name="description"]');
                    var bgColor = val('#ann-bg-color') || '#6C4CF1';
                    var textColor = val('#ann-text-color') || '#ffffff';
                    var buttonText = val('input[name="button_text"]');
                    var buttonUrl = val('input[name="button_url"]');
                    var textPos = val('select[name="text_position"]', 'middle-left');

                    var wantsImage = (type === 'image' || type === 'text_image');
                    var imageOnly = (type === 'image');
                    var showFullText = (type === 'text' || type === 'text_image');
                    var imgSrc = wantsImage ? effectiveImage() : '';
                    var hasImage = !!imgSrc;

                    // alert => red; with a real image => image fills it; else the chosen color/gradient.
                    els.box.style.background = isAlert ? 'linear-gradient(135deg,#ef4444,#b91c1c)' : (hasImage ? '#0b0f1a' : bgColor);
                    els.bg.style.display = hasImage ? 'block' : 'none';
                    if (hasImage) els.bg.style.backgroundImage = "url('" + imgSrc + "')";
                    // Scrim only when text sits over the image (text+image), never for image-only.
                    els.scrim.style.display = (hasImage && !imageOnly) ? 'block' : 'none';

                    // Image-only shows zero text overlay (matches the member dashboard).
                    els.content.style.display = imageOnly ? 'none' : 'flex';
                    els.content.className = 'pos-' + textPos;
                    els.content.style.color = textColor;

                    els.tag.style.display = isAlert ? 'none' : 'inline-flex';
                    els.category.style.display = isAlert ? 'block' : 'none';
                    els.category.textContent = '⚠ ' + category.toUpperCase();

                    els.title.style.display = 'block';
                    els.title.textContent = '— ' + title;

                    els.subtitle.style.display = (showFullText && subtitle) ? 'block' : 'none';
                    els.subtitle.textContent = subtitle;

                    els.desc.style.display = (showFullText && description) ? 'block' : 'none';
                    els.desc.textContent = description;

                    els.btn.style.display = (buttonText && buttonUrl) ? 'inline-flex' : 'none';
                    els.btn.textContent = buttonText || '';
                }

                // Show the picked image in the preview immediately (before cropping).
                if (fileInput) {
                    fileInput.addEventListener('change', function () {
                        var f = fileInput.files && fileInput.files[0];
                        if (!f) { rawSelectedImage = ''; updateAnnPreview(); return; }
                        var reader = new FileReader();
                        reader.onload = function (e) { rawSelectedImage = e.target.result; updateAnnPreview(); };
                        reader.readAsDataURL(f);
                    });
                }

                // Solid color picker -> bg_color field + preview.
                if (bgPicker && bgField) {
                    bgPicker.addEventListener('input', function () {
                        bgField.value = bgPicker.value;
                        updateAnnPreview();
                    });
                }

                var watchedSelectors = [
                    'input[name="announcement_type"]', 'select[name="category"]',
                    'textarea[name="announcement_content"]', 'input[name="subtitle"]',
                    'textarea[name="description"]', '#ann-bg-color', '#ann-text-color-custom',
                    '.ann-text-color-radio', 'input[name="button_text"]', 'input[name="button_url"]',
                    'select[name="text_position"]',
                ];
                watchedSelectors.forEach(function (sel) {
                    document.querySelectorAll(sel).forEach(function (el) {
                        el.addEventListener('input', updateAnnPreview);
                        el.addEventListener('change', updateAnnPreview);
                    });
                });
                // Gradient preset buttons and the crop/apply flow update fields
                // programmatically (not via user input events on a watched
                // control) — cover both with a couple of extra hooks.
                document.querySelectorAll('#ann-gradient-presets button').forEach(function (btn) {
                    btn.addEventListener('click', function () { setTimeout(updateAnnPreview, 0); });
                });
                var applyCropBtn = document.getElementById('ann-apply-crop');
                if (applyCropBtn) applyCropBtn.addEventListener('click', function () { setTimeout(updateAnnPreview, 50); });
                if (cropPreview && window.MutationObserver) {
                    new MutationObserver(updateAnnPreview).observe(cropPreview, { attributes: true, attributeFilter: ['src'] });
                }
                if (cropPreviewWrap && window.MutationObserver) {
                    new MutationObserver(updateAnnPreview).observe(cropPreviewWrap, { attributes: true, attributeFilter: ['style'] });
                }

                updateAnnPreview();
            });
            </script>

    </body>

    </html>