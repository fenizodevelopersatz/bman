<?php
$uid = $this->session->userdata('user_userid') ?? '';
?>
<style>
  .user-dropdown {
    position: relative;
  }

  .user-pill {
    display: flex;
    align-items: center;
    gap: 10px;
    border: none;
    background: transparent;
    cursor: pointer;
    padding: 6px 10px;
    border-radius: 999px;
  }

  .user-menu {
    position: absolute;
    right: 0;
    top: calc(100% + 10px);
    min-width: 180px;
    background: #fff;
    border: 1px solid #eeecff;
    border-radius: 16px;
    box-shadow: 0 18px 40px rgba(0, 0, 0, .08);
    padding: 8px;
    display: none;
    z-index: 9999;
  }

  .user-menu.show {
    display: block;
  }

  .user-menu-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 12px;
    text-decoration: none;
    color: #111827;
    font-weight: 700;
    font-size: 13px;
  }

  .user-menu-item:hover {
    background: #f6f5ff;
  }

  .user-menu-divider {
    height: 1px;
    background: #f1efff;
    margin: 6px 0;
  }

  .user-menu-item.danger {
    color: #dc2626;
  }

  .user-menu-item.danger:hover {
    background: #fff1f2;
  }
</style>
<header>
  <div class="header-actions">
    <?php $mp_us = site_settings('member_theme','user_switch'); if ($mp_us === '' || $mp_us === null) $mp_us = '1'; ?>
    <?php if ($mp_us !== '0'): ?>
    <button class="action-btn" type="button" id="mpThemeToggle" title="Toggle theme">
      <i class="ph ph-sun mp-sun"></i><i class="ph ph-moon mp-moon"></i>
    </button>
    <?php endif; ?>

    <button class="action-btn" title="Messages" style="position:relative;" onclick="window.location.href='<?php echo base_url('user/chat'); ?>'">
      <i class="ph ph-chat-centered-text"></i>
      <span id="chatUnreadBadge" style="display:none;position:absolute;top:2px;right:2px;min-width:16px;height:16px;padding:0 4px;border-radius:999px;background:#ef4444;color:#fff;font-size:10px;line-height:16px;text-align:center;font-weight:700;"></span>
    </button>

    <div class="notif-wrap" id="notifWrap" style="position:relative;">
      <button class="action-btn" type="button" id="notifBtn" title="Notifications" style="position:relative;">
        <i class="ph ph-bell"></i>
        <span id="notifBadge" style="display:none;position:absolute;top:2px;right:2px;min-width:16px;height:16px;padding:0 4px;border-radius:999px;background:#ef4444;color:#fff;font-size:10px;line-height:16px;text-align:center;font-weight:700;"></span>
      </button>
      <div id="notifPanel" style="display:none;position:absolute;top:48px;right:0;width:340px;max-width:92vw;background:#fff;border:1px solid rgba(15,23,42,.1);border-radius:14px;box-shadow:0 18px 44px rgba(15,23,42,.16);z-index:1200;overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border-bottom:1px solid #f0f0f5;">
          <b style="font-size:14px;color:#0b1220;">Notifications</b>
          <button type="button" id="notifMarkAll" style="border:0;background:transparent;color:#6366f1;font-weight:800;font-size:11.5px;cursor:pointer;">Mark all read</button>
        </div>
        <div id="notifList" style="max-height:360px;overflow:auto;">
          <div style="padding:26px 14px;text-align:center;color:#94a3b8;font-size:12.5px;font-weight:700;">Loading…</div>
        </div>
      </div>
    </div>

    <!-- User Dropdown -->
    <div class="user-dropdown" id="userDropdown">
      <button class="user-pill" id="userDropdownBtn" type="button">
        <img src="<?php echo user_profile_image($uid); ?>" alt="user"
          <?php echo avatar_onerror(); ?> />
        <span><?php echo $this->session->userdata('user_full_name') ? ucfirst(strtolower($this->session->userdata('user_full_name'))) : 'Lucas'; ?></span>
        <i class="ph ph-caret-down" style="font-size:16px;"></i>
      </button>

      <div class="user-menu" id="userDropdownMenu">
        <a href="<?= base_url('user/profile'); ?>" class="user-menu-item">
          <i class="ph ph-user"></i> Profile
        </a>
        <div class="user-menu-divider"></div>
        <a href="<?= base_url('user/logout'); ?>" class="user-menu-item danger">
          <i class="ph ph-sign-out"></i> Logout
        </a>
      </div>
    </div>
  </div>
</header>
<script>
  (function () {
    const btn = document.getElementById('userDropdownBtn');
    const menu = document.getElementById('userDropdownMenu');
    const wrap = document.getElementById('userDropdown');

    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      menu.classList.toggle('show');
    });

    document.addEventListener('click', function (e) {
      if (!wrap.contains(e.target)) {
        menu.classList.remove('show');
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') menu.classList.remove('show');
    });
  })();
</script>
<script>
  (function () {
    // Site-wide presence heartbeat — Chat's own 2s poll only runs while the
    // Chat page itself is open, so without this a member browsing anywhere
    // else (Dashboard, Binary Tree, ...) would incorrectly show as offline.
    var url = '<?php echo base_url('user/heartbeat'); ?>';
    var badge = document.getElementById('chatUnreadBadge');
    // Captured once, before any "(n) " prefix is ever added, so repeated
    // pings update the count instead of stacking prefixes onto each other.
    var baseTitle = document.title;
    function ping() {
      if (document.visibilityState !== 'visible') return;
      fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          if (!j || !j.ok) return;
          var n = parseInt(j.unread || 0, 10);
          if (badge) {
            if (n > 0) { badge.textContent = n > 99 ? '99+' : n; badge.style.display = 'block'; }
            else { badge.style.display = 'none'; }
          }
          document.title = n > 0 ? '(' + (n > 99 ? '99+' : n) + ') ' + baseTitle : baseTitle;
        })
        .catch(function () {});
    }
    ping();
    setInterval(ping, 15000);
  })();
</script>
<script>
  (function () {
    // Notification bell — count badge + dropdown fed by the user's rank
    // notifications (api/rank/notifications from user_notifications).
    var wrap = document.getElementById('notifWrap');
    if (!wrap) return;
    var btn = document.getElementById('notifBtn');
    var badge = document.getElementById('notifBadge');
    var panel = document.getElementById('notifPanel');
    var list = document.getElementById('notifList');
    var markAll = document.getElementById('notifMarkAll');
    var url = '<?php echo base_url('api/rank/notifications'); ?>';
    var readUrl = '<?php echo base_url('api/rank/notifications/read'); ?>';

    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]); }); }

    function render(items) {
      if (!items || !items.length) { list.innerHTML = '<div style="padding:26px 14px;text-align:center;color:#94a3b8;font-size:12.5px;font-weight:700;">No notifications yet.</div>'; return; }
      list.innerHTML = items.map(function (n) {
        var unread = !Number(n.is_read);
        return '<div style="display:flex;gap:10px;padding:11px 14px;border-bottom:1px solid #f5f5fa;' + (unread ? 'background:#eef2ff;' : '') + '">' +
          '<div style="width:34px;height:34px;border-radius:10px;background:#eef2ff;color:#6366f1;display:grid;place-items:center;font-size:17px;flex:0 0 auto;"><i class="ph ph-trophy"></i></div>' +
          '<div style="min-width:0;"><b style="display:block;font-size:12.5px;color:#0b1220;font-weight:900;">' + esc(n.title || 'Notification') + '</b>' +
          (n.message ? '<small style="display:block;font-size:11.5px;color:#64748b;font-weight:600;margin-top:2px;">' + esc(n.message) + '</small>' : '') +
          (n.created_at ? '<span style="display:block;font-size:10.5px;color:#94a3b8;margin-top:3px;">' + esc(n.created_at) + '</span>' : '') +
          '</div></div>';
      }).join('');
    }

    function load() {
      fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          if (!j || j.status !== 'success') return;
          var n = parseInt(j.unread || 0, 10);
          if (n > 0) { badge.textContent = n > 99 ? '99+' : n; badge.style.display = 'block'; }
          else { badge.style.display = 'none'; }
          render(j.notifications || []);
        }).catch(function () {});
    }

    btn.addEventListener('click', function (e) { e.stopPropagation(); panel.style.display = (panel.style.display === 'block') ? 'none' : 'block'; });
    document.addEventListener('click', function (e) { if (!wrap.contains(e.target)) panel.style.display = 'none'; });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') panel.style.display = 'none'; });

    markAll.addEventListener('click', function () {
      fetch(readUrl, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function () { badge.style.display = 'none'; load(); }).catch(function () {});
    });

    load();
    setInterval(load, 30000);
  })();
</script>
<?php $this->load->view("partials/browser_controls"); ?>
