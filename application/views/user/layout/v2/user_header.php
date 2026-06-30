<?php
$uid = $this->session->userdata('userid') ?? '';
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
  <div class="search-box">
    <i class="ph ph-magnifying-glass"></i>
    <input autocomplete="off" aria-autocomplete="none" type="text"
      placeholder="Search: orders, commissions, members..." />
  </div>

  <div class="header-actions">
    <?php $mp_us = site_settings('member_theme','user_switch'); if ($mp_us === '' || $mp_us === null) $mp_us = '1'; ?>
    <?php if ($mp_us !== '0'): ?>
    <button class="action-btn" type="button" id="mpThemeToggle" title="Toggle theme">
      <i class="ph ph-sun mp-sun"></i><i class="ph ph-moon mp-moon"></i>
    </button>
    <?php endif; ?>

    <button class="action-btn" title="Messages" onclick="window.location.href='<?php echo base_url('user/chat'); ?>'">
      <i class="ph ph-chat-centered-text"></i>
    </button>

    <button class="action-btn" title="Notifications"><i class="ph ph-bell"></i></button>

    <!-- User Dropdown -->
    <div class="user-dropdown" id="userDropdown">
      <button class="user-pill" id="userDropdownBtn" type="button">
        <img src="<?php echo user_profile_image($uid); ?>" alt="user" />
        <span><?php echo $this->session->userdata('full_name') ? ucfirst(strtolower($this->session->userdata('full_name'))) : 'Lucas'; ?></span>
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