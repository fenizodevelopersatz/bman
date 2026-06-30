<?php
$uri = $this->uri->uri_string();
$isDashboard = ($uri === 'user/main');
$isWallet = ($uri === 'user/wallet');
$isBinaryTree = ($uri === 'user/genealogy');
$isCommissions = ($uri === 'user/profit');
$isPayouts = ($uri === 'user/withdraw');
$isOrders = ($uri === 'user/myorders');
$isShop = ($uri === 'user/shop-list');
$isReferrals = ($uri === 'user/referrals');
$isRankRewards = ($uri === 'user/rank-reward');
$isEarnMore = ($uri === 'user/earn_more' || $uri === 'user/earnings' || $uri === 'user/earnings/ads' || $uri === 'user/earnings/videos');
$isChat = ($uri === 'user/chat');
$isPackage = ($uri === 'user/lending');
$isSupport = ($uri === 'user/support' || $uri === 'user/create-ticket' || $uri === 'user/support-list');
$isSettings = ($uri === 'user/view-profile' || $uri === 'user/edit-profile');
?>

<button class="sidebar-toggle" id="sidebarToggleBtn" type="button" aria-label="Open menu">☰</button>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<aside class="sidebar" style="overflow:auto">
    <div class="logo"><i class="ph-fill ph-sparkle"></i> Fenizo MLM </div>
    <nav>
        <span class="nav-label">Overview</span>
        <a href="<?php echo base_url('user/main'); ?>" class="nav-item <?php echo $isDashboard ? 'active' : ''; ?>"><i
                class="ph-fill ph-squares-four"></i> Dashboard</a>
        <a href="<?php echo base_url('user/lending'); ?>" class="nav-item <?php echo $isPackage ? 'active' : ''; ?>"><i
                class="ph ph-coins"></i> Package</a>
        <a href="<?php echo base_url('user/wallet'); ?>" class="nav-item <?php echo $isWallet ? 'active' : ''; ?>"><i
                class="ph ph-wallet"></i> Wallet</a>
        <a href="<?php echo base_url('user/genealogy'); ?>"
            class="nav-item <?php echo $isBinaryTree ? 'active' : ''; ?>"><i class="ph ph-tree-structure"></i> Binary
            Tree</a>
        <a href="<?php echo base_url('user/profit'); ?>"
            class="nav-item <?php echo $isCommissions ? 'active' : ''; ?>"><i class="ph ph-coins"></i> Commissions</a>
        <a href="<?php echo base_url('user/withdraw'); ?>" class="nav-item <?php echo $isPayouts ? 'active' : ''; ?>"><i
                class="ph ph-calendar-check"></i> Payouts</a>

        <span class="nav-label">E-Commerce</span>
        <a href="<?php echo base_url('user/myorders'); ?>" class="nav-item <?php echo $isOrders ? 'active' : ''; ?>"><i
                class="ph ph-bag"></i> Orders</a>
        <a href="<?php echo base_url('user/shop-list'); ?>" class="nav-item <?php echo $isShop ? 'active' : ''; ?>"><i
                class="ph ph-storefront"></i> Shop</a>

        <span class="nav-label">Team</span>
        <a href="<?php echo base_url('user/referrals'); ?>"
            class="nav-item <?php echo $isReferrals ? 'active' : ''; ?>"><i class="ph ph-users-three"></i> Referrals</a>
        <a href="<?php echo base_url('user/rank-reward'); ?>"
            class="nav-item <?php echo $isRankRewards ? 'active' : ''; ?>"><i class="ph ph-identification-card"></i>
            Rank &
            Rewards</a>
        <a href="<?php echo base_url('user/earn_more'); ?>"
            class="nav-item <?php echo $isEarnMore ? 'active' : ''; ?>"><i class="ph ph-rocket-launch"></i> Earn
            More</a>
        <a href="<?php echo base_url('user/chat'); ?>" class="nav-item <?php echo $isChat ? 'active' : ''; ?>"><i
                class="ph ph-chat-circle-dots"></i> Chat</a>
        <a href="<?php echo base_url('user/support'); ?>" class="nav-item <?php echo $isSupport ? 'active' : ''; ?>"><i
                class="ph ph-headset"></i> Support</a>
    </nav>

    <div class="support-card">
        <p><b>Need help?</b><br />Raise a support ticket and our team will assist you quickly.</p>
        <button type="button" onclick="window.location.href='<?php echo base_url('user/support'); ?>'"><i
                class="ph ph-chat-circle-dots"></i> Create Ticket</button>
    </div>

    <div style="margin-top:auto;">
        <a href="<?php echo base_url('user/view-profile'); ?>"
            class="nav-item <?php echo $isSettings ? 'active' : ''; ?>"><i class="ph ph-gear"></i> Setting</a>
        <a href="<?php echo base_url('user/logout'); ?>" class="nav-item" style="color:#ff4d4d;"><i
                class="ph ph-sign-out"></i> Logout</a>
    </div>
</aside>

<script>
    (function () {
        if (window.__sidebarMobileInit) return;
        window.__sidebarMobileInit = true;

        const sidebar = document.querySelector('.sidebar');
        const toggleBtn = document.getElementById('sidebarToggleBtn');
        const backdrop = document.getElementById('sidebarBackdrop');
        const mobileMq = window.matchMedia('(max-width: 1024px)');

        if (!sidebar || !toggleBtn || !backdrop) return;

        function closeSidebar() {
            sidebar.classList.remove('open');
            backdrop.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        }

        function openSidebar() {
            if (!mobileMq.matches) return;
            sidebar.classList.add('open');
            backdrop.classList.add('show');
            document.body.classList.add('sidebar-open');
        }

        toggleBtn.addEventListener('click', function () {
            if (sidebar.classList.contains('open')) closeSidebar();
            else openSidebar();
        });

        backdrop.addEventListener('click', closeSidebar);

        sidebar.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', closeSidebar);
        });

        window.addEventListener('resize', function () {
            if (!mobileMq.matches) closeSidebar();
        });
    })();
</script>