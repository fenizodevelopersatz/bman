<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Convert admin-entered URL into an embeddable + restricted URL.
 * - YouTube: force /embed/VIDEO_ID and add parameters to reduce suggestions/controls.
 * - Vimeo: use player.vimeo.com and remove title/byline/portrait.
 * - Direct mp4/webm: return as-is.
 */
function normalize_video_embed_url(string $url): ?string
{
    $url = trim($url);
    if ($url === '')
        return null;

    // If admin pasted full iframe code, extract src=""
    if (stripos($url, '<iframe') !== false) {
        if (preg_match('/src=["\']([^"\']+)["\']/', $url, $m)) {
            $url = $m[1];
        }
    }

    // Add scheme for //domain/...
    if (strpos($url, '//') === 0)
        $url = 'https:' . $url;

    $parts = parse_url($url);
    if (!$parts || empty($parts['host']))
        return $url;

    $host = strtolower($parts['host']);
    parse_str($parts['query'] ?? '', $q);

    // ---------------- YOUTUBE ----------------
    if (str_contains($host, 'youtube.com') || str_contains($host, 'youtu.be')) {
        $videoId = null;

        // youtu.be/VIDEO_ID
        if (str_contains($host, 'youtu.be')) {
            $videoId = trim($parts['path'] ?? '', '/');
        }

        // youtube.com/watch?v=VIDEO_ID
        if (!$videoId && isset($q['v']))
            $videoId = $q['v'];

        // youtube.com/embed/VIDEO_ID
        if (!$videoId && isset($parts['path']) && str_contains($parts['path'], '/embed/')) {
            $videoId = basename($parts['path']);
        }

        // youtube.com/shorts/VIDEO_ID
        if (!$videoId && isset($parts['path']) && str_contains($parts['path'], '/shorts/')) {
            $videoId = basename($parts['path']);
        }

        if (!$videoId)
            return null;

        $params = [
            'rel' => '0',
            'modestbranding' => '1',
            'playsinline' => '1',
            'iv_load_policy' => '3',
            'cc_load_policy' => '0',
            'disablekb' => '1',
            'controls' => '0',
            'fs' => '0',
            'origin' => (isset($_SERVER['HTTP_HOST'])
                ? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']
                : ''),
        ];

        if (empty($params['origin']))
            unset($params['origin']);

        return "https://www.youtube.com/embed/" . rawurlencode($videoId) . "?" . http_build_query($params);
    }

    // ---------------- VIMEO ----------------
    if (str_contains($host, 'vimeo.com')) {
        $id = trim($parts['path'] ?? '', '/');

        if (ctype_digit($id)) {
            $params = [
                'dnt' => '1',
                'title' => '0',
                'byline' => '0',
                'portrait' => '0',
            ];
            return "https://player.vimeo.com/video/" . $id . "?" . http_build_query($params);
        }

        if (str_contains($host, 'player.vimeo.com'))
            return $url;
    }

    return $url;
}

// safe defaults
$mode = isset($mode) ? $mode : 'add';

$title_val = isset($title_val) ? $title_val : '';
$description_val = isset($description_val) ? $description_val : '';
$duration_val = isset($duration_val) ? (int) $duration_val : 30;
$reward_val = isset($reward_val) ? (float) $reward_val : 1.50;
$sort_val = isset($sort_val) ? (int) $sort_val : 1;
$active_val = isset($active_val) ? (int) $active_val : 1;

$existingAdUrl = isset($ad_url_val) ? trim((string) $ad_url_val) : '';
$existingThumb = isset($thumb_url_val) ? trim((string) $thumb_url_val) : '';

// normalize for iframe preview
$embedUrl = $existingAdUrl ? normalize_video_embed_url($existingAdUrl) : null;

// detect direct video ext
$pathForExt = parse_url($existingAdUrl, PHP_URL_PATH) ?: '';
$ext = strtolower(pathinfo($pathForExt, PATHINFO_EXTENSION));
$isDirectVideo = in_array($ext, ['mp4', 'webm', 'ogg']);

// decide default modes
$isExistingUrl = (bool) preg_match('#^https?://#i', $existingAdUrl);
$defaultVideoMode = $isExistingUrl ? 'url' : (($existingAdUrl !== '') ? 'upload' : 'url');

$isExistingThumbUrl = (bool) preg_match('#^https?://#i', $existingThumb);
$defaultThumbMode = $isExistingThumbUrl ? 'url' : (($existingThumb !== '') ? 'upload' : 'url');
?>

<?php $this->load->view('admin/Layout/common_style'); ?>

<link href="<?php echo base_url(); ?>/assets/admin/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet"
    type="text/css">
<link href="<?php echo base_url(); ?>/assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.css" rel="stylesheet"
    type="text/css">
<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet"
    type="text/css" />

<style>
    .h-md-40 {
        min-height: 42%;
    }

    .seg {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .seg .btn {
        border-radius: 10px;
    }

    .preview-box {
        border: 1px dashed #e4e6ef;
        border-radius: 12px;
        padding: 12px;
        background: #fafbff;
    }

    .mini-muted {
        font-size: 12px;
        color: #7e8299;
    }

    .thumb-preview {
        width: 180px;
        height: 100px;
        border-radius: 12px;
        border: 1px solid #eee;
        object-fit: cover;
        background: #fff;
    }

    .video-frame {
        width: 100%;
        max-width: 680px;
        height: 380px;
        border: 0;
        border-radius: 12px;
        background: #000;
    }

    .mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 12px;
    }
</style>

<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true"
    data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true"
    data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
    data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">

    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">

            <?php $this->load->view('admin/Layout/admin_topbar'); ?>

            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
                <?php $this->load->view('admin/Layout/admin_sidebar'); ?>

                <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                    <div class="d-flex flex-column flex-column-fluid">

                        <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                                    <h1
                                        class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                                        <?php echo $title; ?>
                                    </h1>

                                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                        <li class="breadcrumb-item text-muted">
                                            <a href="<?php echo base_url(); ?>"
                                                class="text-muted text-hover-primary">Earnings Settings</a>
                                        </li>
                                        <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span>
                                        </li>
                                        <li class="breadcrumb-item text-muted">
                                            <?php echo $title; ?>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div id="kt_app_content" class="app-content flex-column-fluid mt-10">
                            <div id="kt_app_content_container" class="app-container container-xxl">

                                <?php $this->load->view('notification'); ?>

                                <div class="card mb-5 mb-xl-10">
                                    <div class="card-header border-0 cursor-pointer p-3" role="button"
                                        data-bs-toggle="collapse" data-bs-target="#kt_earning_ads_form_details"
                                        aria-expanded="true" aria-controls="kt_earning_ads_form_details">
                                        <div class="card-title m-0">
                                            <div
                                                class="me-3 d-flex justify-content-between text-center align-items-center gap-4">
                                                <div
                                                    class="d-flex flex-center w-60px h-60px rounded-3 bg-light-danger bg-opacity-90">
                                                    <i class="ki-duotone ki-abstract-26 text-danger fs-3x">
                                                        <span class="path1"></span><span class="path2"></span>
                                                    </i>
                                                </div>
                                                <h3 class="fw-bold m-0">
                                                    <?php echo $card_title; ?>
                                                </h3>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="kt_earning_ads_form_details" class="collapse show">
                                        <div class="card-body border-top p-9">

                                            <?php
                                            // ✅ One endpoint is best: save() handles insert/update
                                            $action = base_url('admin/earning-ads/save');
                                            ?>

                                            <?= form_open($action, [
                                                'class' => 'form-validate',
                                                'method' => 'post',
                                                'autocomplete' => 'off',
                                                'id' => 'kt_earning_ads_form',
                                                'data-kt-redirect-url' => base_url('admin/earning-ads'),
                                                'enctype' => 'multipart/form-data'
                                            ]) ?>

                                            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>"
                                                value="<?= $this->security->get_csrf_hash(); ?>" />

                                            <input type="hidden" name="ad_id"
                                                value="<?php echo isset($ad_id) ? $ad_id : ''; ?>" />

                                            <!-- ✅ Title -->
                                            <div class="row mb-6">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">
                                                    Title <span class="text-danger">*</span>
                                                </label>
                                                <div class="col-lg-8 fv-row">
                                                    <div class="input-group mb-5">
                                                        <span class="input-group-text border-transparent"><i
                                                                class="fa-solid fa-note-sticky"></i></span>
                                                        <input type="text" name="title"
                                                            class="form-control form-control-lg form-control-solid"
                                                            placeholder="Example: Ad 1 - Short"
                                                            value="<?php echo htmlspecialchars($title_val); ?>"
                                                            required />
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ✅ Description -->
                                            <div class="row mb-6">
                                                <label
                                                    class="col-lg-4 col-form-label fw-semibold fs-6">Description</label>
                                                <div class="col-lg-8 fv-row">
                                                    <div class="input-group mb-5">
                                                        <span class="input-group-text border-transparent"><i
                                                                class="fa-solid fa-align-left"></i></span>
                                                        <textarea name="description"
                                                            class="form-control form-control-lg form-control-solid"
                                                            placeholder="Example: Watch full 30s ad to earn"><?php echo htmlspecialchars($description_val); ?></textarea>
                                                    </div>
                                                    <div class="mini-muted">Optional.</div>
                                                </div>
                                            </div>

                                            <!-- ✅ Existing video preview (friendly) -->
                                            <?php if (!empty($existingAdUrl)): ?>
                                                <div class="row mb-6">
                                                    <label class="col-lg-4 col-form-label fw-semibold fs-6">Current
                                                        Video</label>
                                                    <div class="col-lg-8 fv-row">
                                                        <div class="preview-box">
                                                            <div class="mini-muted mb-2"><b>Saved ad_url:</b></div>
                                                            <div class="text-gray-800 mono mb-3">
                                                                <?php echo htmlspecialchars($existingAdUrl); ?>
                                                            </div>

                                                            <?php if ($isDirectVideo): ?>
                                                                <video controls
                                                                    style="width:100%; max-width:680px; border-radius:12px; border:1px solid #eee;">
                                                                    <source
                                                                        src="<?php echo htmlspecialchars($existingAdUrl); ?>">
                                                                </video>
                                                            <?php elseif (!empty($embedUrl) && (str_contains($embedUrl, 'youtube.com/embed/') || str_contains($embedUrl, 'player.vimeo.com/video/'))): ?>
                                                                <iframe class="video-frame"
                                                                    src="<?php echo htmlspecialchars($embedUrl); ?>"
                                                                    allow="autoplay; encrypted-media"
                                                                    referrerpolicy="strict-origin-when-cross-origin"
                                                                    allowfullscreen="false"
                                                                    sandbox="allow-scripts allow-same-origin allow-presentation">
                                                                </iframe>
                                                                <div class="mini-muted mt-2">
                                                                    Preview uses restricted embed URL.
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="mini-muted">
                                                                    Preview not available for this URL type. It will be used
                                                                    as-is.
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- ✅ Video source mode -->
                                            <div class="row mb-6">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">
                                                    Video Source <span class="text-danger">*</span>
                                                </label>
                                                <div class="col-lg-8 fv-row">
                                                    <div class="seg">
                                                        <label class="btn btn-sm btn-light-primary" id="btn_video_url">
                                                            <input type="radio" name="video_mode" value="url"
                                                                class="d-none" <?php echo ($defaultVideoMode === 'url') ? 'checked' : ''; ?>>
                                                            Use URL
                                                        </label>

                                                        <label class="btn btn-sm btn-light" id="btn_video_upload">
                                                            <input type="radio" name="video_mode" value="upload"
                                                                class="d-none" <?php echo ($defaultVideoMode === 'upload') ? 'checked' : ''; ?>>
                                                            Upload Video
                                                        </label>
                                                    </div>

                                                    <div class="mini-muted mt-2">
                                                        Upload will be saved into <b>ad_url</b> field (same column).
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ✅ Ad URL -->
                                            <div class="row mb-6" id="wrap_ad_url">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">
                                                    Ad URL <span class="text-danger">*</span>
                                                </label>
                                                <div class="col-lg-8 fv-row">
                                                    <div class="input-group mb-5">
                                                        <span class="input-group-text border-transparent"><i
                                                                class="fa-solid fa-link"></i></span>
                                                        <input type="text" name="ad_url" id="ad_url"
                                                            class="form-control form-control-lg form-control-solid"
                                                            placeholder="https://www.youtube.com/watch?v=..."
                                                            value="<?php echo htmlspecialchars($existingAdUrl); ?>" />
                                                    </div>
                                                    <div class="mini-muted">YouTube/Vimeo/Direct MP4 URL supported.
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ✅ Video upload -->
                                            <div class="row mb-6" id="wrap_video_upload">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">
                                                    Upload Video
                                                    <?php echo ($mode === 'add') ? '<span class="text-danger">*</span>' : ''; ?>
                                                </label>
                                                <div class="col-lg-8 fv-row">
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text border-transparent"><i
                                                                class="fa-solid fa-file-video"></i></span>
                                                        <input type="file" name="video_file" id="video_file"
                                                            class="form-control form-control-lg form-control-solid"
                                                            accept="video/mp4,video/webm,video/ogg" />
                                                    </div>
                                                    <div class="mini-muted">Allowed: mp4, webm, ogg. Leave empty to keep
                                                        existing.</div>
                                                </div>
                                            </div>

                                            <!-- ✅ Existing thumb preview -->
                                            <?php if (!empty($existingThumb)): ?>
                                                <div class="row mb-6">
                                                    <label class="col-lg-4 col-form-label fw-semibold fs-6">Current
                                                        Thumbnail</label>
                                                    <div class="col-lg-8 fv-row">
                                                        <div class="preview-box d-flex align-items-center gap-4 flex-wrap">
                                                            <img class="thumb-preview"
                                                                src="<?php echo htmlspecialchars($existingThumb); ?>"
                                                                onerror="this.style.display='none';" alt="thumb">
                                                            <div>
                                                                <div class="mini-muted mb-2"><b>Saved thumb_url:</b></div>
                                                                <div class="text-gray-800 mono">
                                                                    <?php echo htmlspecialchars($existingThumb); ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- ✅ Thumbnail source mode -->
                                            <div class="row mb-6">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Thumbnail
                                                    Source</label>
                                                <div class="col-lg-8 fv-row">
                                                    <div class="seg">
                                                        <label class="btn btn-sm btn-light-primary" id="btn_thumb_url">
                                                            <input type="radio" name="thumb_mode" value="url"
                                                                class="d-none" <?php echo ($defaultThumbMode === 'url') ? 'checked' : ''; ?>>
                                                            Use URL
                                                        </label>

                                                        <label class="btn btn-sm btn-light" id="btn_thumb_upload">
                                                            <input type="radio" name="thumb_mode" value="upload"
                                                                class="d-none" <?php echo ($defaultThumbMode === 'upload') ? 'checked' : ''; ?>>
                                                            Upload Image
                                                        </label>
                                                    </div>

                                                    <div class="mini-muted mt-2">
                                                        Upload will be saved into <b>thumb_url</b> field (same column).
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ✅ Thumb URL -->
                                            <div class="row mb-6" id="wrap_thumb_url">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Thumb URL
                                                    (optional)</label>
                                                <div class="col-lg-8 fv-row">
                                                    <div class="input-group mb-5">
                                                        <span class="input-group-text border-transparent"><i
                                                                class="fa-solid fa-image"></i></span>
                                                        <input type="text" name="thumb_url" id="thumb_url"
                                                            class="form-control form-control-lg form-control-solid"
                                                            placeholder="https://.../thumb.jpg"
                                                            value="<?php echo htmlspecialchars($existingThumb); ?>" />
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ✅ Thumb upload -->
                                            <div class="row mb-6" id="wrap_thumb_upload">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Upload Thumbnail
                                                    (optional)</label>
                                                <div class="col-lg-8 fv-row">
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text border-transparent"><i
                                                                class="fa-solid fa-upload"></i></span>
                                                        <input type="file" name="thumb_file" id="thumb_file"
                                                            class="form-control form-control-lg form-control-solid"
                                                            accept="image/jpeg,image/png,image/webp" />
                                                    </div>
                                                    <div class="mini-muted">Allowed: jpg, png, webp (recommended 16:9).
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ✅ Duration -->
                                            <div class="row mb-6">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">
                                                    Duration Seconds <span class="text-danger">*</span>
                                                </label>
                                                <div class="col-lg-8 fv-row">
                                                    <div class="input-group mb-5">
                                                        <span class="input-group-text border-transparent"><i
                                                                class="fa-solid fa-clock"></i></span>
                                                        <input type="number" min="1" name="duration_seconds"
                                                            class="form-control form-control-lg form-control-solid"
                                                            value="<?php echo (int) $duration_val; ?>" required />
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ✅ Reward -->
                                            <div class="row mb-6">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">
                                                    Reward USD <span class="text-danger">*</span>
                                                </label>
                                                <div class="col-lg-8 fv-row">
                                                    <div class="input-group mb-5">
                                                        <span class="input-group-text border-transparent"><i
                                                                class="fa-solid fa-dollar-sign"></i></span>
                                                        <input type="number" step="0.01" min="0" name="reward_usd"
                                                            class="form-control form-control-lg form-control-solid"
                                                            value="<?php echo number_format((float) $reward_val, 2, '.', ''); ?>"
                                                            required />
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ✅ Sort -->
                                            <div class="row mb-6">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Sort
                                                    Order</label>
                                                <div class="col-lg-8 fv-row">
                                                    <div class="input-group mb-5">
                                                        <span class="input-group-text border-transparent"><i
                                                                class="fa-solid fa-sort"></i></span>
                                                        <input type="number" min="1" name="sort_order"
                                                            class="form-control form-control-lg form-control-solid"
                                                            value="<?php echo (int) $sort_val; ?>" />
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ✅ Active -->
                                            <div class="row mb-6">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Active</label>
                                                <div class="col-lg-8 fv-row">
                                                    <div
                                                        class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                                        <input class="form-check-input h-30px w-50px" type="checkbox"
                                                            name="is_active" value="1" <?php echo ((int) $active_val === 1) ? 'checked' : ''; ?> />
                                                        <label class="form-check-label">Enable this Ad</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <button type="submit" id="kt_earning_ads_submit"
                                                        class="btn btn-lg btn-primary">Save</button>
                                                    <a href="<?php echo base_url('admin/earning-ads'); ?>"
                                                        class="btn btn-lg btn-light ms-2">Back</a>
                                                </div>
                                            </div>

                                            <?= form_close(); ?>

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
    </div>

    <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
        <i class="ki-duotone ki-arrow-up"><span class="path1"></span><span class="path2"></span></i>
    </div>

    <?php $this->load->view('admin/Layout/common_script'); ?>

    <script src="<?php echo base_url(); ?>/assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.js"></script>
    <script src="<?php echo base_url(); ?>/assets/admin/js/widgets.bundle.js"></script>
    <script src="<?php echo base_url(); ?>/assets/admin/js/custom/widgets.js"></script>
    <script src="<?php echo base_url(); ?>/assets/admin/js/custom/apps/chat/chat.js"></script>
    <script src="<?php echo base_url(); ?>/assets/admin/js/custom/utilities/modals/upgrade-plan.js"></script>
    <script src="<?php echo base_url(); ?>/assets/admin/js/custom/utilities/modals/users-search.js"></script>
    <script src="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.js"></script>

    <script>
        const base_url = '<?php echo base_url(); ?>';

        function setSegActive(group, value) {
            // group: 'video'|'thumb'
            const btnUrl = document.getElementById(group === 'video' ? 'btn_video_url' : 'btn_thumb_url');
            const btnUp = document.getElementById(group === 'video' ? 'btn_video_upload' : 'btn_thumb_upload');

            if (!btnUrl || !btnUp) return;

            if (value === 'url') {
                btnUrl.classList.add('btn-light-primary');
                btnUrl.classList.remove('btn-light');
                btnUp.classList.add('btn-light');
                btnUp.classList.remove('btn-light-primary');
            } else {
                btnUp.classList.add('btn-light-primary');
                btnUp.classList.remove('btn-light');
                btnUrl.classList.add('btn-light');
                btnUrl.classList.remove('btn-light-primary');
            }
        }

        function toggleModes() {
            const videoMode = document.querySelector('input[name="video_mode"]:checked')?.value || 'url';
            const thumbMode = document.querySelector('input[name="thumb_mode"]:checked')?.value || 'url';

            // video
            document.getElementById('wrap_ad_url').style.display = (videoMode === 'url') ? '' : 'none';
            document.getElementById('wrap_video_upload').style.display = (videoMode === 'upload') ? '' : 'none';
            setSegActive('video', videoMode);

            // thumb
            document.getElementById('wrap_thumb_url').style.display = (thumbMode === 'url') ? '' : 'none';
            document.getElementById('wrap_thumb_upload').style.display = (thumbMode === 'upload') ? '' : 'none';
            setSegActive('thumb', thumbMode);

            // required logic: ad_url required only in url mode
            const adUrl = document.getElementById('ad_url');
            if (adUrl) {
                if (videoMode === 'url') adUrl.setAttribute('required', 'required');
                else adUrl.removeAttribute('required');
            }
        }

        document.addEventListener('change', function (e) {
            if (e.target && (e.target.name === 'video_mode' || e.target.name === 'thumb_mode')) {
                toggleModes();
            }
        });

        document.addEventListener('DOMContentLoaded', toggleModes);
    </script>

    <!-- ✅ Your AJAX submit JS -->
    <script
        src="<?php echo base_url(); ?>/assets/admin/js/custom/authentication/sign-in/earning-ads-add.js?ver=1.1"></script>

</body>

</html>