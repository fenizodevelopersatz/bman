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
                                <div class="d-flex gap-2 mb-3 flex-wrap" id="ann-gradient-presets">
                                    <button type="button" class="btn btn-sm" data-grad="linear-gradient(135deg,#6C4CF1,#4E2CF0)" style="background:linear-gradient(135deg,#6C4CF1,#4E2CF0);color:#fff;">Purple</button>
                                    <button type="button" class="btn btn-sm" data-grad="linear-gradient(135deg,#3B82F6,#1D4ED8)" style="background:linear-gradient(135deg,#3B82F6,#1D4ED8);color:#fff;">Blue</button>
                                    <button type="button" class="btn btn-sm" data-grad="linear-gradient(135deg,#22C55E,#15803D)" style="background:linear-gradient(135deg,#22C55E,#15803D);color:#fff;">Green</button>
                                    <button type="button" class="btn btn-sm" data-grad="linear-gradient(135deg,#F59E0B,#C2410C)" style="background:linear-gradient(135deg,#F59E0B,#C2410C);color:#fff;">Orange</button>
                                    <button type="button" class="btn btn-sm" data-grad="linear-gradient(135deg,#EF4444,#B91C1C)" style="background:linear-gradient(135deg,#EF4444,#B91C1C);color:#fff;">Red</button>
                                </div>
                                <input type="text" name="bg_color" id="ann-bg-color" class="form-control form-control-lg form-control-solid"
                                    placeholder="#6E56CF or linear-gradient(...)"
                                    value="<?php echo htmlspecialchars($bg_color); ?>">
                                <div class="text-muted fs-8 mt-1">Click a preset above, or type a custom color / CSS gradient.</div>
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

                        <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Display Mode</label>
                        <div class="col-lg-8 fv-row">
                            <div class="d-flex gap-6">
                                <label class="form-check form-check-custom form-check-solid">
                                    <input class="form-check-input" type="radio" name="display_mode" value="banner" <?= $display_mode === 'banner' ? 'checked' : '' ?>>
                                    <span class="form-check-label">Show as Banner</span>
                                </label>
                                <label class="form-check form-check-custom form-check-solid">
                                    <input class="form-check-input" type="radio" name="display_mode" value="popup" <?= $display_mode === 'popup' ? 'checked' : '' ?>>
                                    <span class="form-check-label">Show as Popup</span>
                                </label>
                                <label class="form-check form-check-custom form-check-solid">
                                    <input class="form-check-input" type="radio" name="display_mode" value="both" <?= $display_mode === 'both' ? 'checked' : '' ?>>
                                    <span class="form-check-label">Show Both</span>
                                </label>
                            </div>
                        </div>
                        </div>

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

    </body>

    </html>