<?php
// application/views/admin/earnings/video_add.php
// FULL ADD/EDIT PAGE (Earning Videos) — supports URL or Upload for video_url + thumb_url (same DB fields)

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
    if (function_exists('str_contains') && (str_contains($host, 'youtube.com') || str_contains($host, 'youtu.be'))) {
        $videoId = null;

        if (str_contains($host, 'youtu.be')) {
            $videoId = trim($parts['path'] ?? '', '/');
        }

        if (!$videoId && isset($q['v']))
            $videoId = $q['v'];

        if (!$videoId && isset($parts['path']) && str_contains($parts['path'], '/embed/')) {
            $videoId = basename($parts['path']);
        }

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
            'origin' => (isset($_SERVER['HTTP_HOST']) ? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] : ''),
        ];
        if (empty($params['origin']))
            unset($params['origin']);

        return "https://www.youtube.com/embed/" . rawurlencode($videoId) . "?" . http_build_query($params);
    }

    // PHP 7 fallback: if str_contains not available (CI3 often runs PHP 7)
    if (!function_exists('str_contains') && (strpos($host, 'youtube.com') !== false || strpos($host, 'youtu.be') !== false)) {
        $videoId = null;

        if (strpos($host, 'youtu.be') !== false) {
            $videoId = trim($parts['path'] ?? '', '/');
        }

        if (!$videoId && isset($q['v']))
            $videoId = $q['v'];

        if (!$videoId && isset($parts['path']) && strpos($parts['path'], '/embed/') !== false) {
            $videoId = basename($parts['path']);
        }

        if (!$videoId && isset($parts['path']) && strpos($parts['path'], '/shorts/') !== false) {
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
            'origin' => (isset($_SERVER['HTTP_HOST']) ? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] : ''),
        ];
        if (empty($params['origin']))
            unset($params['origin']);

        return "https://www.youtube.com/embed/" . rawurlencode($videoId) . "?" . http_build_query($params);
    }

    // ---------------- VIMEO ----------------
    if (strpos($host, 'vimeo.com') !== false) {
        $id = trim($parts['path'] ?? '', '/');
        if (ctype_digit($id)) {
            $params = ['dnt' => '1', 'title' => '0', 'byline' => '0', 'portrait' => '0'];
            return "https://player.vimeo.com/video/" . $id . "?" . http_build_query($params);
        }
        if (strpos($host, 'player.vimeo.com') !== false)
            return $url;
    }

    return $url;
}

// values from controller
$mode = isset($mode) ? $mode : 'add';

$existingVideoUrl = isset($video_url_val) ? $video_url_val : '';
$existingThumb = isset($thumb_url_val) ? $thumb_url_val : '';

$isExistingVideoHttp = (bool) preg_match('#^https?://#i', $existingVideoUrl);
$isExistingThumbHttp = (bool) preg_match('#^https?://#i', $existingThumb);

$defaultVideoMode = $isExistingVideoHttp ? 'url' : (($existingVideoUrl !== '') ? 'upload' : 'url');
$defaultThumbMode = $isExistingThumbHttp ? 'url' : (($existingThumb !== '') ? 'upload' : 'url');

$title_val = isset($title_val) ? $title_val : '';
$description_val = isset($description_val) ? $description_val : '';
$duration_val = isset($duration_val) ? (int) $duration_val : 30;
$reward_val = isset($reward_val) ? (float) $reward_val : 1.50;
$sort_val = isset($sort_val) ? (int) $sort_val : 1;
$active_val = isset($active_val) ? (int) $active_val : 1;

$embedUrl = normalize_video_embed_url($existingVideoUrl);
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

    .video-embed-wrap {
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid #eef0f5;
        background: #000;
    }

    .video-embed-wrap iframe,
    .video-embed-wrap video {
        width: 100%;
        height: 360px;
        display: block;
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
                                        data-bs-toggle="collapse" data-bs-target="#kt_earning_videos_form_details"
                                        aria-expanded="true" aria-controls="kt_earning_videos_form_details">
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

                                    <div id="kt_earning_videos_form_details" class="collapse show">
                                        <div class="card-body border-top p-9">

                                            <?php
                                            $action = ($mode === 'edit')
                                                ? base_url('admin/earning-videos/save')
                                                : base_url('admin/earning-videos/save');
                                            ?>

                                            <?= form_open($action, [
                                                'class' => 'form-validate',
                                                'method' => 'post',
                                                'autocomplete' => 'off',
                                                'id' => 'kt_earning_videos_form',
                                                'data-kt-redirect-url' => base_url('admin/earning-videos'),
                                                'enctype' => 'multipart/form-data'
                                            ]) ?>

                                            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>"
                                                value="<?= $this->security->get_csrf_hash(); ?>" />

                                            <input type="hidden" name="video_id"
                                                value="<?php echo isset($video_id) ? $video_id : ''; ?>" />

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
                                                            placeholder="Example: Premium Video 1"
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
                                                            placeholder="Example: Watch full video to earn"><?php echo htmlspecialchars($description_val); ?></textarea>
                                                    </div>
                                                    <div class="mini-muted">Optional text for admins/users.</div>
                                                </div>
                                            </div>

                                            <!-- ✅ Video Source mode -->
                                            <div class="row mb-6">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">
                                                    Video Source <span class="text-danger">*</span>
                                                </label>
                                                <div class="col-lg-8 fv-row">
                                                    <div class="seg">
                                                        <label class="btn btn-sm btn-light-primary">
                                                            <input type="radio" name="video_mode" value="url"
                                                                class="d-none" <?php echo ($defaultVideoMode === 'url') ? 'checked' : ''; ?>>
                                                            Use URL
                                                        </label>

                                                        <label class="btn btn-sm btn-light">
                                                            <input type="radio" name="video_mode" value="upload"
                                                                class="d-none" <?php echo ($defaultVideoMode === 'upload') ? 'checked' : ''; ?>>
                                                            Upload Video
                                                        </label>
                                                    </div>
                                                    <div class="mini-muted mt-2">
                                                        Upload saves file path into <b>video_url</b> field itself (no
                                                        new DB fields).
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ✅ Video URL -->
                                            <div class="row mb-6" id="wrap_video_url">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">
                                                    Video URL <span class="text-danger">*</span>
                                                </label>
                                                <div class="col-lg-8 fv-row">
                                                    <div class="input-group mb-5">
                                                        <span class="input-group-text border-transparent"><i
                                                                class="fa-solid fa-link"></i></span>
                                                        <input type="text" name="video_url"
                                                            class="form-control form-control-lg form-control-solid"
                                                            placeholder="https://www.youtube.com/shorts/..."
                                                            value="<?php echo htmlspecialchars($existingVideoUrl); ?>" />
                                                    </div>
                                                    <div class="mini-muted">YouTube / Vimeo / direct mp4 links
                                                        supported.</div>
                                                </div>
                                            </div>

                                            <!-- ✅ Video Upload -->
                                            <div class="row mb-6" id="wrap_video_upload">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">
                                                    Upload Video
                                                    <?php echo ($mode === 'add') ? '<span class="text-danger">*</span>' : ''; ?>
                                                </label>
                                                <div class="col-lg-8 fv-row">
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text border-transparent"><i
                                                                class="fa-solid fa-file-video"></i></span>
                                                        <input type="file" name="video_file"
                                                            class="form-control form-control-lg form-control-solid"
                                                            accept="video/mp4,video/webm,video/ogg" />
                                                    </div>

                                                    <?php if (!empty($existingVideoUrl) && !$isExistingVideoHttp): ?>
                                                        <div class="preview-box">
                                                            <div class="mini-muted mb-2"><b>Current uploaded video path:</b>
                                                            </div>
                                                            <div class="text-gray-800">
                                                                <?php echo htmlspecialchars($existingVideoUrl); ?>
                                                            </div>
                                                            <div class="mini-muted mt-2">Leave empty to keep existing file.
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="mini-muted">Allowed: mp4, webm, ogg. (Max size depends
                                                            on server.)</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- ✅ Live preview (URL only) -->
                                            <div class="row mb-6" id="wrap_video_preview">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Preview</label>
                                                <div class="col-lg-8 fv-row">
                                                    <div class="preview-box">
                                                        <?php if ($embedUrl): ?>
                                                            <div class="video-embed-wrap">
                                                                <iframe src="<?php echo htmlspecialchars($embedUrl); ?>"
                                                                    frameborder="0"
                                                                    allow="autoplay; encrypted-media; picture-in-picture"
                                                                    referrerpolicy="strict-origin-when-cross-origin"
                                                                    allowfullscreen="false"></iframe>
                                                            </div>
                                                            <div class="mini-muted mt-2">
                                                                Preview uses restricted embed params (similar to your user
                                                                watch page).
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="mini-muted">Enter a valid URL to see preview.</div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ✅ Thumbnail mode -->
                                            <div class="row mb-6">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Thumbnail
                                                    Source</label>
                                                <div class="col-lg-8 fv-row">
                                                    <div class="seg">
                                                        <label class="btn btn-sm btn-light-primary">
                                                            <input type="radio" name="thumb_mode" value="url"
                                                                class="d-none" <?php echo ($defaultThumbMode === 'url') ? 'checked' : ''; ?>>
                                                            Use URL
                                                        </label>

                                                        <label class="btn btn-sm btn-light">
                                                            <input type="radio" name="thumb_mode" value="upload"
                                                                class="d-none" <?php echo ($defaultThumbMode === 'upload') ? 'checked' : ''; ?>>
                                                            Upload Image
                                                        </label>
                                                    </div>
                                                    <div class="mini-muted mt-2">Upload saves path into <b>thumb_url</b>
                                                        field.</div>
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
                                                        <input type="text" name="thumb_url"
                                                            class="form-control form-control-lg form-control-solid"
                                                            placeholder="https://.../thumb.jpg"
                                                            value="<?php echo htmlspecialchars($existingThumb); ?>" />
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ✅ Thumb Upload -->
                                            <div class="row mb-6" id="wrap_thumb_upload">
                                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Upload Thumbnail
                                                    (optional)</label>
                                                <div class="col-lg-8 fv-row">
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text border-transparent"><i
                                                                class="fa-solid fa-upload"></i></span>
                                                        <input type="file" name="thumb_file"
                                                            class="form-control form-control-lg form-control-solid"
                                                            accept="image/jpeg,image/png,image/webp" />
                                                    </div>

                                                    <?php if (!empty($existingThumb) && !$isExistingThumbHttp): ?>
                                                        <div class="preview-box">
                                                            <div class="mini-muted mb-2"><b>Current uploaded thumbnail
                                                                    path:</b></div>
                                                            <div class="text-gray-800">
                                                                <?php echo htmlspecialchars($existingThumb); ?>
                                                            </div>
                                                            <div class="mini-muted mt-2">Leave empty to keep existing image.
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="mini-muted">Allowed: jpg, png, webp (recommended 16:9).
                                                        </div>
                                                    <?php endif; ?>
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
                                                    <div class="mini-muted">Example premium: 1.50 / 2.00 / 3.00</div>
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
                                                        <label class="form-check-label">Enable this Video</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <button type="submit" id="kt_earning_videos_submit"
                                                        class="btn btn-lg btn-primary">Save</button>

                                                    <a href="<?php echo base_url('admin/earning-videos'); ?>"
                                                        class="btn btn-lg btn-light ms-2">
                                                        Back
                                                    </a>
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

        function toggleModes() {
            const videoMode = document.querySelector('input[name="video_mode"]:checked')?.value || 'url';
            const thumbMode = document.querySelector('input[name="thumb_mode"]:checked')?.value || 'url';

            // video
            document.getElementById('wrap_video_url').style.display = (videoMode === 'url') ? '' : 'none';
            document.getElementById('wrap_video_upload').style.display = (videoMode === 'upload') ? '' : 'none';

            // show preview only if URL mode
            document.getElementById('wrap_video_preview').style.display = (videoMode === 'url') ? '' : 'none';

            // thumb
            document.getElementById('wrap_thumb_url').style.display = (thumbMode === 'url') ? '' : 'none';
            document.getElementById('wrap_thumb_upload').style.display = (thumbMode === 'upload') ? '' : 'none';
        }

        document.addEventListener('change', function (e) {
            if (e.target && (e.target.name === 'video_mode' || e.target.name === 'thumb_mode')) {
                toggleModes();
            }
        });

        document.addEventListener('DOMContentLoaded', toggleModes);
    </script>

    <!-- Your AJAX submit JS (create this similar to earning-ads-add.js) -->
    <script
        src="<?php echo base_url(); ?>/assets/admin/js/custom/authentication/sign-in/earning-videos-add.js?ver=1.0"></script>

</body>

</html>