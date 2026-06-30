<?php
defined('BASEPATH') or exit('No direct script access allowed');

function extract_youtube_id(?string $url): ?string
{
  if (!$url)
    return null;
  $url = trim($url);
  if ($url === '')
    return null;

  // if iframe code pasted
  if (stripos($url, '<iframe') !== false) {
    if (preg_match('/src=["\']([^"\']+)["\']/', $url, $m)) {
      $url = $m[1];
    }
  }

  if (strpos($url, '//') === 0)
    $url = 'https:' . $url;

  $parts = parse_url($url);
  if (!$parts || empty($parts['host']))
    return null;

  $host = strtolower($parts['host']);
  parse_str($parts['query'] ?? '', $q);

  if (str_contains($host, 'youtu.be')) {
    $id = trim($parts['path'] ?? '', '/');
    return $id ?: null;
  }

  if (str_contains($host, 'youtube.com')) {
    // watch?v=
    if (!empty($q['v']))
      return $q['v'];

    // /embed/ID
    if (!empty($parts['path']) && str_contains($parts['path'], '/embed/')) {
      return basename($parts['path']);
    }

    // /shorts/ID
    if (!empty($parts['path']) && str_contains($parts['path'], '/shorts/')) {
      return basename($parts['path']);
    }
  }

  return null;
}

function is_direct_video_file(?string $url): bool
{
  if (!$url)
    return false;
  return (bool) preg_match('/\.(mp4|webm|ogg)(\?.*)?$/i', trim($url));
}

function normalize_video_embed_url(string $url): ?string
{
  $url = trim($url);
  if ($url === '')
    return null;

  if (stripos($url, '<iframe') !== false) {
    if (preg_match('/src=["\']([^"\']+)["\']/', $url, $m)) {
      $url = $m[1];
    }
  }

  if (strpos($url, '//') === 0)
    $url = 'https:' . $url;

  $parts = parse_url($url);
  if (!$parts || empty($parts['host']))
    return $url;

  $host = strtolower($parts['host']);
  parse_str($parts['query'] ?? '', $q);

  // YOUTUBE
  if (str_contains($host, 'youtube.com') || str_contains($host, 'youtu.be')) {
    $videoId = extract_youtube_id($url);
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

  // VIMEO (basic)
  if (str_contains($host, 'vimeo.com')) {
    $id = trim($parts['path'] ?? '', '/');
    if (ctype_digit($id)) {
      $params = ['dnt' => '1', 'title' => '0', 'byline' => '0', 'portrait' => '0'];
      return "https://player.vimeo.com/video/" . $id . "?" . http_build_query($params);
    }
    if (str_contains($host, 'player.vimeo.com'))
      return $url;
  }

  return $url;
}

// ---- Data ----
$rawUrl = $video->video_url ?? '';
$isDirectFile = is_direct_video_file($rawUrl);
$embedUrl = $isDirectFile ? $rawUrl : normalize_video_embed_url($rawUrl);

// Thumbnail (YouTube only, page-side)
$ytId = extract_youtube_id($rawUrl);
$thumbMax = $ytId ? "https://i.ytimg.com/vi/" . rawurlencode($ytId) . "/maxresdefault.jpg" : null;
$thumbHQ = $ytId ? "https://i.ytimg.com/vi/" . rawurlencode($ytId) . "/hqdefault.jpg" : null;
?>
<!DOCTYPE html>
<html>

<head>
  <?php $this->load->view('user/layout/v2/user_style'); ?>
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <style>
    .wrap {
      padding: 20px
    }

    .card {
      background: #fff;
      border: 1px solid #f0f0f7;
      border-radius: 18px;
      padding: 18px
    }

    .btn {
      padding: 12px 18px;
      border-radius: 14px;
      border: 0;
      font-weight: 900;
      cursor: pointer
    }

    .btnp {
      background: linear-gradient(135deg, #6E56CF, #4D39A3);
      color: #fff
    }

    .btnd {
      background: #eee;
      color: #999;
      cursor: not-allowed
    }

    .timer {
      font-size: 18px;
      font-weight: 900
    }

    .videoBox {
      margin-top: 14px;
      border-radius: 14px;
      overflow: hidden;
      background: #000;
      position: relative
    }

    iframe {
      width: 100%;
      height: 420px;
      border: 0
    }

    video {
      width: 100%;
      height: 420px;
      display: block;
      background: #000
    }

    /* Thumbnail overlay */
    .thumbOverlay {
      position: absolute;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #000;
      cursor: pointer;
      z-index: 10;
    }

    .thumbOverlay img {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      opacity: .92;
    }

    .thumbOverlay::after {
      content: "";
      width: 72px;
      height: 72px;
      border-radius: 999px;
      background: rgba(0, 0, 0, .55);
      border: 2px solid rgba(255, 255, 255, .35);
      box-shadow: 0 10px 30px rgba(0, 0, 0, .35);
      z-index: 2;
    }

    .thumbPlayIcon {
      position: absolute;
      width: 0;
      height: 0;
      border-left: 18px solid #fff;
      border-top: 12px solid transparent;
      border-bottom: 12px solid transparent;
      transform: translateX(4px);
      z-index: 3;
    }

    .hint {
      margin-top: 10px;
      padding: 10px 12px;
      border-radius: 12px;
      background: #fff7ed;
      border: 1px solid #fed7aa;
      color: #9a3412;
      font-weight: 800;
      display: none;
    }
  </style>
  <style>
    * {
      box-sizing: border-box;
    }

    html,
    body {
      width: 100%;
      overflow-x: hidden;
    }

    .wrap {
      padding: 20px;
      max-width: 100%;
    }

    .card {
      background: #fff;
      border: 1px solid #f0f0f7;
      border-radius: 18px;
      padding: 18px;
      max-width: 100%;
      box-shadow: 0 10px 22px rgba(0, 0, 0, 0.03);
    }

    /* titles */
    .card h2 {
      line-height: 1.2;
      word-break: break-word;
    }

    .card p {
      line-height: 1.5;
      word-break: break-word;
    }

    /* top meta row (Reward/Duration/Timer) */
    .metaRow {
      display: flex;
      gap: 12px;
      align-items: center;
      flex-wrap: wrap;
    }

    .timer {
      font-size: 18px;
      font-weight: 900;
      white-space: nowrap;
    }

    /* video */
    .videoBox {
      margin-top: 14px;
      border-radius: 14px;
      overflow: hidden;
      background: #000;
      position: relative;
      width: 100%;
    }

    /* ✅ responsive aspect ratio (best) */
    .videoBox {
      aspect-ratio: 16 / 9;
      min-height: 210px;
    }

    iframe,
    video {
      width: 100%;
      height: 100%;
      border: 0;
      display: block;
      background: #000;
    }

    /* Thumbnail overlay */
    .thumbOverlay {
      position: absolute;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #000;
      cursor: pointer;
      z-index: 10;
    }

    .thumbOverlay img {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      opacity: .92;
    }

    .thumbOverlay::after {
      content: "";
      width: 72px;
      height: 72px;
      border-radius: 999px;
      background: rgba(0, 0, 0, .55);
      border: 2px solid rgba(255, 255, 255, .35);
      box-shadow: 0 10px 30px rgba(0, 0, 0, .35);
      z-index: 2;
    }

    .thumbPlayIcon {
      position: absolute;
      width: 0;
      height: 0;
      border-left: 18px solid #fff;
      border-top: 12px solid transparent;
      border-bottom: 12px solid transparent;
      transform: translateX(4px);
      z-index: 3;
    }

    /* buttons row */
    .btnRow {
      margin-top: 14px;
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

    .btn {
      padding: 12px 18px;
      border-radius: 14px;
      border: 0;
      font-weight: 900;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      white-space: nowrap;
      line-height: 1;
    }

    .btnp {
      background: linear-gradient(135deg, #6E56CF, #4D39A3);
      color: #fff;
    }

    .btnd {
      background: #eee;
      color: #999;
      cursor: not-allowed;
    }

    /* message */
    #msg {
      word-break: break-word;
    }

    /* ✅ Tablet */
    @media (max-width: 992px) {
      .wrap {
        padding: 14px;
      }

      .card {
        padding: 14px;
        border-radius: 16px;
      }

      .videoBox {
        min-height: 200px;
      }
    }

    /* ✅ Mobile */
    @media (max-width: 576px) {
      .wrap {
        padding: 12px;
      }

      .card {
        padding: 12px;
        border-radius: 16px;
      }

      .timer {
        font-size: 16px;
      }

      /* keep video usable on small screens */
      .videoBox {
        aspect-ratio: 16 / 9;
        min-height: 190px;
        border-radius: 14px;
      }

      /* buttons become full width */
      .btnRow .btn,
      .btnRow a.btn {
        width: 100%;
        padding: 13px 14px;
        border-radius: 14px;
      }

      /* make play circle smaller */
      .thumbOverlay::after {
        width: 62px;
        height: 62px;
      }

      .thumbPlayIcon {
        border-left: 16px solid #fff;
        border-top: 10px solid transparent;
        border-bottom: 10px solid transparent;
      }
    }
  </style>
</head>

<body>
  <div class="app-container">
    <?php $this->load->view('user/layout/v2/user_sidebar'); ?>
    <main class="main-content">
      <?php $this->load->view('user/layout/v2/user_header'); ?>

      <div class="wrap">
        <div class="card">
          <h2 style="margin:0;font-weight:900;">
            <?= htmlspecialchars($video->title) ?>
          </h2>
          <p style="margin:6px 0 12px;color:#8a8aa3;">
            <?= htmlspecialchars($video->description) ?>
          </p>

          <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
            <div><b>Reward:</b> $
              <?= number_format((float) $video->reward_usd, 2) ?>
            </div>
            <div><b>Duration:</b>
              <?= (int) $video->duration_seconds ?> sec
            </div>
            <div class="timer" id="timer">Ready</div>
          </div>

          <div class="videoBox metaRow" id="videoBox">
            <?php if (!empty($thumbHQ) || $isDirectFile): ?>
              <!-- Thumbnail overlay (YouTube thumb, or generic for direct file) -->
              <div class="thumbOverlay" id="thumbOverlay" title="Click to start">
                <?php if (!empty($thumbMax)): ?>
                  <img id="thumbImg" src="<?= htmlspecialchars($thumbMax, ENT_QUOTES) ?>"
                    onerror="this.onerror=null;this.src='<?= htmlspecialchars($thumbHQ, ENT_QUOTES) ?>';" alt="thumbnail">
                <?php elseif (!empty($thumbHQ)): ?>
                  <img id="thumbImg" src="<?= htmlspecialchars($thumbHQ, ENT_QUOTES) ?>" alt="thumbnail">
                <?php else: ?>
                  <!-- direct file: no auto thumbnail available; show dark overlay only -->
                <?php endif; ?>
                <span class="thumbPlayIcon"></span>
              </div>
            <?php endif; ?>

            <?php if ($isDirectFile): ?>
              <video id="videoTag" preload="none" playsinline controls controlsList="nodownload noremoteplayback"
                disablePictureInPicture src="about:blank"
                data-src="<?= htmlspecialchars($embedUrl ?? '', ENT_QUOTES) ?>"></video>
            <?php else: ?>
              <iframe id="player" src="about:blank" data-src="<?= htmlspecialchars($embedUrl ?? '', ENT_QUOTES) ?>"
                allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
            <?php endif; ?>
          </div>


          <div class="btnRow" style="margin-top:14px;display:flex;gap:12px;">
            <button class="btn btnp" id="btnStart" <?= empty($embedUrl) ? 'disabled' : '' ?>>Start Watch</button>
            <button class="btn btnd" id="btnComplete" disabled>Claim Reward</button>
            <a class="btn" style="background:#fff;border:1px solid #f0f0f7"
              href="<?= base_url('user/earnings/videos') ?>">Back</a>
          </div>

          <div id="msg" style="margin-top:10px;font-weight:800;"></div>
        </div>
      </div>

    </main>
  </div>

  <script>
    let token = null;
    let remain = 0;
    let timerInt = null;

    function setMsg(t, ok = false) {
      const m = document.getElementById('msg');
      m.style.color = ok ? '#10b981' : '#ef4444';
      m.textContent = t || '';
    }

    function hideThumb() {
      const ov = document.getElementById('thumbOverlay');
      if (ov) ov.style.display = 'none';
    }
    function startTimer() {
      if (timerInt) clearInterval(timerInt);
      document.getElementById('timer').textContent = remain + "s";
      timerInt = setInterval(() => {
        remain--;
        document.getElementById('timer').textContent = remain + "s";
        if (remain <= 0) {
          clearInterval(timerInt);
          document.getElementById('timer').textContent = "Done ✅";
          const btnComplete = document.getElementById('btnComplete');
          btnComplete.disabled = false;
          btnComplete.classList.remove('btnd');
          btnComplete.classList.add('btnp');
        }
      }, 1000);
    }

    // Clicking thumbnail should also start
    const thumbOverlay = document.getElementById('thumbOverlay');
    if (thumbOverlay) {
      thumbOverlay.addEventListener('click', () => {
        document.getElementById('btnStart').click();
      });
    }

    document.getElementById('btnStart').addEventListener('click', async () => {
      setMsg('');


      // Start session (backend)
      const res = await fetch("<?= base_url('user/earnings/videos/start/' . $video->id) ?>");
      const json = await res.json();
      if (!json.status) { setMsg(json.message || 'Unable to start'); return; }

      token = json.token;
      remain = parseInt(json.duration || <?= (int) $video->duration_seconds ?>, 10);

      // Disable Start
      const btnStart = document.getElementById('btnStart');
      btnStart.disabled = true;
      btnStart.classList.add('btnd');

      // Hide thumbnail
      hideThumb();

      document.getElementById('timer').textContent = "Loading...";

      // Direct video
      const vid = document.getElementById('videoTag');
      if (vid) {
        const baseSrc = vid.getAttribute('data-src') || '';
        if (!baseSrc) {
          document.getElementById('timer').textContent = "Blocked ❌";
          setMsg("Invalid video URL.");

          return;
        }

        const onLoaded = () => {
          vid.removeEventListener('loadeddata', onLoaded);
          startTimer();
          vid.play().catch(() => { });
        };

        vid.addEventListener('loadeddata', onLoaded);
        vid.src = baseSrc;
        vid.load();

        setTimeout(() => {
          if (document.getElementById('timer').textContent === "Loading...") {
            document.getElementById('timer').textContent = "Blocked ❌";
            setMsg("Video failed to load.");

          }
        }, 2500);
        return;
      }

      // Iframe provider
      const frame = document.getElementById('player');
      const baseSrc = frame.getAttribute('data-src') || '';
      if (!baseSrc) {
        document.getElementById('timer').textContent = "Blocked ❌";
        setMsg("This URL cannot be embedded. Please update video URL.");

        return;
      }

      let finalSrc = baseSrc;
      if (finalSrc.includes('youtube.com/embed/')) {
        finalSrc += (finalSrc.includes('?') ? '&' : '?') + 'autoplay=1&mute=1';
      } else {
        finalSrc += (finalSrc.includes('?') ? '&' : '?') + 'autoplay=1';
      }

      const onLoadOnce = () => {
        frame.removeEventListener('load', onLoadOnce);
        startTimer();
      };

      frame.addEventListener('load', onLoadOnce);
      frame.src = finalSrc;

      setTimeout(() => {
        if (document.getElementById('timer').textContent === "Loading...") {
          document.getElementById('timer').textContent = "Blocked ❌";
          // setMsg("This provider blocked embedding. Please update video URL.");
        }
      }, 2500);
    });

    document.getElementById('btnComplete').addEventListener('click', async () => {
      if (!token) { setMsg('Session not started'); return; }
      const form = new FormData();
      form.append('token', token);

      const res = await fetch("<?= base_url('user/earnings/videos/complete') ?>", { method: "POST", body: form });
      const json = await res.json();

      if (!json.status) { setMsg(json.message || 'Not completed'); return; }

      setMsg("Reward credited: $" + (json.amount || 0).toFixed(2), true);
      const btnComplete = document.getElementById('btnComplete');
      btnComplete.disabled = true;
      btnComplete.classList.add('btnd');
    });

    // If invalid embed url, disable start + show hint
    (function init() {
      const ok = <?= empty($embedUrl) ? 'false' : 'true' ?>;
      if (!ok) {
        const btnStart = document.getElementById('btnStart');
        btnStart.disabled = true;
        btnStart.classList.add('btnd');

      }
    })();
  </script>
</body>

</html>