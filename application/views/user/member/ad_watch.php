<?php
// ad_watch.php  (FULL UPDATED FILE - restrictions added)

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

    if (!$videoId) {
      // homepage/channel/playlist etc -> not allowed here
      return null;
    }

    // Best possible restrictions (YouTube still may show some suggestions)
    $params = [
      'rel' => '0',
      'modestbranding' => '1',
      'playsinline' => '1',
      'iv_load_policy' => '3',  // hide annotations
      'cc_load_policy' => '0',
      'disablekb' => '1',       // disable keyboard shortcuts
      'controls' => '0',        // hide controls (reduces next/prev)
      'fs' => '0',              // disable fullscreen button (optional)
      'origin' => (isset($_SERVER['HTTP_HOST']) ? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] : ''),
    ];

    // Remove empty origin if host unknown
    if (empty($params['origin']))
      unset($params['origin']);

    return "https://www.youtube.com/embed/" . rawurlencode($videoId) . "?" . http_build_query($params);
  }

  // ---------------- VIMEO ----------------
  if (str_contains($host, 'vimeo.com')) {
    // vimeo.com/123456 -> player.vimeo.com/video/123456
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

    // already player url
    if (str_contains($host, 'player.vimeo.com'))
      return $url;
  }

  // Other direct URLs (mp4/webm or other embeddable pages)
  return $url;
}

$embedUrl = normalize_video_embed_url($ad->ad_url ?? '');
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
      cursor: pointer;
      text-decoration: none;
      display: inline-block
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
      background: #000
    }

    iframe {
      width: 100%;
      height: 420px;
      border: 0
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

    .wrap {
      padding: 20px;
    }

    .card {
      background: #fff;
      border: 1px solid #f0f0f7;
      border-radius: 18px;
      padding: 20px;
    }

    .btn {
      padding: 12px 18px;
      border-radius: 14px;
      border: 0;
      font-weight: 900;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      white-space: nowrap;
      text-align: center;
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

    .timer {
      font-size: 18px;
      font-weight: 900;
    }

    /* Responsive 16:9 video */
    .videoBox {
      margin-top: 16px;
      border-radius: 14px;
      overflow: hidden;
      background: #000;
      position: relative;
      width: 100%;
      aspect-ratio: 16 / 9;
      /* ✅ Modern responsive ratio */
    }

    iframe {
      width: 100%;
      height: 100%;
      border: 0;
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

    /* Info row */
    .infoRow {
      display: flex;
      gap: 16px;
      align-items: center;
      flex-wrap: wrap;
      margin-top: 10px;
    }

    /* Buttons row */
    .btnRow {
      margin-top: 16px;
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

    /* ================= MOBILE ================= */
    @media (max-width: 768px) {

      .wrap {
        padding: 14px;
      }

      .card {
        padding: 16px;
      }

      h2 {
        font-size: 18px;
      }

      .timer {
        font-size: 16px;
      }

      .infoRow {
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
      }

      .btnRow {
        flex-direction: column;
      }

      .btnRow .btn {
        width: 100%;
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
          <h2 style="margin:0;font-weight:900;"><?= htmlspecialchars($ad->title) ?></h2>
          <p style="margin:6px 0 12px;color:#8a8aa3;"><?= htmlspecialchars($ad->description) ?></p>

          <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
            <div><b>Reward:</b> $<?= number_format((float) $ad->reward_usd, 2) ?></div>
            <div><b>Duration:</b> <?= (int) $ad->duration_seconds ?> sec</div>
            <div class="timer" id="timer">Ready</div>
          </div>

          <!-- Video loads only after Start click -->
          <div class="videoBox">
            <iframe id="adFrame" src="about:blank" data-src="<?= htmlspecialchars($embedUrl ?? '', ENT_QUOTES) ?>"
              allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
          </div>

          <div style="margin-top:14px;display:flex;gap:12px;">
            <button class="btn btnp" id="btnStart" <?= empty($embedUrl) ? 'disabled' : '' ?>>Start Watch</button>
            <button class="btn btnd" id="btnComplete" disabled>Claim Reward</button>
            <a class="btn" style="background:#fff;border:1px solid #f0f0f7"
              href="<?= base_url('user/earnings/ads') ?>">Back</a>
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

    document.getElementById('btnStart').addEventListener('click', async () => {
      setMsg('');

      const frame = document.getElementById('adFrame');
      const baseSrc = frame.getAttribute('data-src') || '';

      if (!baseSrc) {
        setMsg('Video URL is not valid / not embeddable.');
        return;
      }

      // 1) Start session (backend)
      const res = await fetch("<?= base_url('user/earnings/ads/start/' . $ad->id) ?>");
      const json = await res.json();

      if (!json.status) {
        setMsg(json.message || 'Unable to start');
        return;
      }

      token = json.token;
      remain = parseInt(json.duration || <?= (int) $ad->duration_seconds ?>, 10);

      // Disable Start button
      const btnStart = document.getElementById('btnStart');
      btnStart.disabled = true;
      btnStart.classList.add('btnd');

      // 2) Load video ONLY now (autoplay appended after Start)
      let finalSrc = baseSrc;

      // Best autoplay success:
      // - YouTube requires user gesture; we have it (button click)
      // - mute=1 improves autoplay success
      if (finalSrc.includes('youtube.com/embed/')) {
        finalSrc += (finalSrc.includes('?') ? '&' : '?') + 'autoplay=1&mute=1';
      } else {
        // For other providers, try autoplay param (some ignore)
        finalSrc += (finalSrc.includes('?') ? '&' : '?') + 'autoplay=1';
      }

      // 3) Start timer ONLY after iframe load
      document.getElementById('timer').textContent = "Loading...";

      const onLoadOnce = () => {
        frame.removeEventListener('load', onLoadOnce);
        startTimer();
      };

      frame.addEventListener('load', onLoadOnce);
      frame.src = finalSrc;

      // Safety: if blocked/no load, show message
      setTimeout(() => {
        if (document.getElementById('timer').textContent === "Loading...") {
          document.getElementById('timer').textContent = "Blocked ❌";
          showHint(true);
        }
      }, 2000);
    });

    document.getElementById('btnComplete').addEventListener('click', async () => {
      if (!token) {
        setMsg('Session not started');
        return;
      }

      const form = new FormData();
      form.append('token', token);

      const res = await fetch("<?= base_url('user/earnings/ads/complete') ?>", {
        method: "POST",
        body: form
      });

      const json = await res.json();
      if (!json.status) {
        setMsg(json.message || 'Not completed');
        return;
      }

      setMsg("Reward credited: $" + (json.amount || 0).toFixed(2), true);

      const btnComplete = document.getElementById('btnComplete');
      btnComplete.disabled = true;
      btnComplete.classList.add('btnd');
    });

    // If embedUrl is invalid, disable Start and show hint once
    (function init() {
      const btnStart = document.getElementById('btnStart');
      const frame = document.getElementById('adFrame');
      const baseSrc = frame.getAttribute('data-src') || '';

      if (!baseSrc) {
        btnStart.disabled = true;
        btnStart.classList.add('btnd');
        showHint(true);
      }
    })();
  </script>
</body>

</html>