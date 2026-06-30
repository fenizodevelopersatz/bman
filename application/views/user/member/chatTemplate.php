<!DOCTYPE html>
<html lang="en">

<head>
  <?php $this->load->view('user/layout/v2/user_style'); ?>
  <script src="https://unpkg.com/@phosphor-icons/web"></script>

  <style>
    /* =========================
      MLM CHAT (PRO UI)
    ========================== */
    :root {
      --chat-bg: #f4f7fe;
      --chat-card: #ffffff;
      --chat-line: #f1f1f6;
      --chat-muted: #8E8E93;
      --chat-text: #1A1A1A;

      --primary: #6E56CF;
      --primary-2: #4c3ba0;

      --bubble-border: #f59e0b;
      /* orange/gold like screenshot */
      --shadow: 0 16px 40px rgba(0, 0, 0, .06);
      --radius: 24px;
    }

    .mlm-chat-shell {
      background: var(--chat-card);
      border: 1px solid #f5f5f7;
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: var(--shadow);
      min-height: 72vh;
      display: flex;
      flex-direction: column;
    }

    /* Top bar with tabs + actions */
    .chat-topbar {
      background: #2f3437;
      padding: 14px 14px 0 14px;
      border-bottom: 1px solid rgba(255, 255, 255, .06);
    }

    .chat-toprow {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding-bottom: 10px;
    }

    .chat-tabs {
      display: flex;
      gap: 8px;
      align-items: flex-end;
    }

    .tab {
      appearance: none;
      border: none;
      cursor: pointer;
      padding: 10px 16px;
      border-radius: 14px 14px 0 0;
      background: rgba(255, 255, 255, .08);
      color: rgba(255, 255, 255, .72);
      font-size: 12px;
      font-weight: 900;
      display: inline-flex;
      gap: 8px;
      align-items: center;
      transition: .15s;
    }

    .tab:hover {
      background: rgba(255, 255, 255, .12);
    }

    .tab.active {
      background: var(--primary);
      color: #fff;
      box-shadow: 0 10px 25px rgba(110, 86, 207, .25);
    }

    .chat-actions {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .icon-btn {
      width: 40px;
      height: 40px;
      border-radius: 14px;
      border: 1px solid rgba(255, 255, 255, .12);
      background: rgba(255, 255, 255, .06);
      color: #fff;
      display: grid;
      place-items: center;
      cursor: pointer;
      transition: .15s;
    }

    .icon-btn:hover {
      background: rgba(255, 255, 255, .10);
    }

    .chat-subrow {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      padding: 10px 0 14px 0;
    }

    .room-meta {
      color: rgba(255, 255, 255, .78);
      font-size: 12px;
      font-weight: 900;
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .room-pill {
      background: rgba(255, 255, 255, .10);
      border: 1px solid rgba(255, 255, 255, .12);
      padding: 6px 10px;
      border-radius: 999px;
      display: inline-flex;
      gap: 8px;
      align-items: center;
      font-size: 11px;
      font-weight: 1000;
    }

    .online-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #22c55e;
      box-shadow: 0 0 0 4px rgba(34, 197, 94, .15);
    }

    .chat-search {
      width: 360px;
      max-width: 52vw;
      background: rgba(255, 255, 255, .08);
      border: 1px solid rgba(255, 255, 255, .12);
      padding: 10px 12px;
      border-radius: 14px;
      display: flex;
      gap: 10px;
      align-items: center;
      color: #fff;
    }

    .chat-search i {
      font-size: 18px;
      opacity: .9;
    }

    .chat-search input {
      width: 100%;
      border: none;
      outline: none;
      background: transparent;
      color: #fff;
      font-size: 12px;
      font-weight: 900;
    }

    .chat-search input::placeholder {
      color: rgba(255, 255, 255, .65);
    }

    /* Messages area */
    .chat-body {
      flex: 1;
      background: var(--chat-bg);
      padding: 18px;
      overflow-y: auto;
    }

    .time-divider {
      display: flex;
      justify-content: center;
      margin: 14px 0;
    }

    .time-divider span {
      background: #d1d8e0;
      color: #2b2f33;
      padding: 3px 12px;
      font-size: 11px;
      font-weight: 1000;
      border-radius: 8px;
    }

    .msg-row {
      display: flex;
      gap: 14px;
      align-items: flex-start;
      margin-bottom: 18px;
    }

    .msg-row.me {
      justify-content: flex-end;
    }

    .avatar-box {
      width: 56px;
      height: 56px;
      border-radius: 16px;
      background: #fff;
      border: 2px solid rgba(255, 255, 255, .9);
      box-shadow: 0 10px 20px rgba(0, 0, 0, .06);
      position: relative;
      overflow: hidden;
      flex-shrink: 0;
      display: grid;
      place-items: center;
    }

    .avatar-box img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .avatar-ring {
      position: absolute;
      inset: 0;
      border-radius: 16px;
      border: 2px solid transparent;
      pointer-events: none;
    }

    .ring-gold {
      border-color: rgba(245, 158, 11, .95);
    }

    .ring-silver {
      border-color: rgba(156, 163, 175, .95);
    }

    .ring-purple {
      border-color: rgba(110, 86, 207, .95);
    }

    .msg-wrap {
      max-width: 620px;
      min-width: 180px;
    }

    .msg-head {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 6px;
      flex-wrap: wrap;
    }

    .vip {
      font-size: 10px;
      font-weight: 1100;
      padding: 3px 8px;
      border-radius: 8px;
      background: rgba(245, 158, 11, .15);
      border: 1px solid rgba(245, 158, 11, .35);
      color: #a16207;
      display: inline-flex;
      gap: 6px;
      align-items: center;
    }

    .vip.purple {
      background: rgba(110, 86, 207, .12);
      border-color: rgba(110, 86, 207, .25);
      color: var(--primary);
    }

    .uname {
      font-size: 13px;
      font-weight: 1100;
      color: #374151;
    }

    .meta {
      font-size: 11px;
      font-weight: 900;
      color: var(--chat-muted);
      margin-left: auto;
      display: flex;
      gap: 8px;
      align-items: center;
    }

    .bubble {
      background: #fff;
      border: 2px solid rgba(245, 158, 11, .65);
      border-radius: 18px;
      padding: 12px 14px;
      font-size: 13px;
      font-weight: 900;
      color: #111;
      line-height: 1.5;
      box-shadow: 0 10px 18px rgba(0, 0, 0, .03);
      position: relative;
    }

    .bubble:before {
      content: "";
      position: absolute;
      left: -8px;
      top: 16px;
      width: 14px;
      height: 14px;
      background: #fff;
      border-left: 2px solid rgba(245, 158, 11, .65);
      border-bottom: 2px solid rgba(245, 158, 11, .65);
      transform: rotate(45deg);
      border-radius: 2px;
    }

    /* My messages */
    .msg-row.me .msg-wrap {
      max-width: 620px;
    }

    .msg-row.me .msg-head {
      justify-content: flex-end;
    }

    .msg-row.me .bubble {
      background: linear-gradient(105deg, var(--primary) 0%, var(--primary-2) 100%);
      border: none;
      color: #fff;
      box-shadow: 0 14px 26px rgba(110, 86, 207, .20);
    }

    .msg-row.me .bubble:before {
      display: none;
    }

    .reactions {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-top: 8px;
    }

    .rxn {
      display: inline-flex;
      gap: 6px;
      align-items: center;
      background: #eef2ff;
      border: 1px solid #e7e7f3;
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 1000;
      color: #111;
    }

    /* Footer */
    .chat-footer {
      background: #fff;
      border-top: 1px solid var(--chat-line);
      padding: 12px 14px;
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .tool-btn {
      width: 44px;
      height: 44px;
      border-radius: 16px;
      border: 1px solid #f1f1f6;
      background: #f7f7fb;
      color: #111;
      display: grid;
      place-items: center;
      cursor: pointer;
      transition: .15s;
      flex-shrink: 0;
    }

    .tool-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 12px 20px rgba(0, 0, 0, .06);
    }

    .composer {
      flex: 1;
      background: #f7f7fb;
      border: 1px solid #f1f1f6;
      border-radius: 18px;
      padding: 10px 12px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .composer input {
      border: none;
      outline: none;
      background: transparent;
      width: 100%;
      font-size: 13px;
      font-weight: 900;
    }

    .send-btn {
      width: 50px;
      height: 50px;
      border-radius: 18px;
      border: none;
      cursor: pointer;
      background: var(--primary);
      color: #fff;
      display: grid;
      place-items: center;
      box-shadow: 0 16px 28px rgba(110, 86, 207, .22);
      transition: .15s;
      flex-shrink: 0;
    }

    .send-btn:hover {
      transform: translateY(-1px);
    }

    /* small helper */
    .hidden {
      display: none !important;
    }

    @media(max-width:1100px) {
      .chat-search {
        width: 260px;
      }

      .msg-wrap {
        max-width: 520px;
      }
    }

    @media(max-width:900px) {
      .chat-search {
        display: none;
      }
    }
  </style>
</head>

<body>
  <div class="app-container">
    <?php $this->load->view('user/layout/v2/user_sidebar'); ?>

    <main class="main-content">
      <?php $this->load->view('user/layout/v2/user_header'); ?>

      <div class="mlm-chat-shell">

        <!-- TOP BAR -->
        <div class="chat-topbar">
          <div class="chat-toprow">
            <div class="chat-tabs" id="chatTabs">
              <button class="tab active" data-tab="world" type="button">
                <i class="ph ph-globe-hemisphere-east"></i> World
              </button>
              <button class="tab" data-tab="team" type="button">
                <i class="ph ph-users-three"></i> Alliance (Team)
              </button>
              <button class="tab" data-tab="personal" type="button">
                <i class="ph ph-chat-teardrop-text"></i> Personal
              </button>
            </div>

            <div class="chat-actions">
              <button class="icon-btn" type="button" title="Members">
                <i class="ph ph-users"></i>
              </button>
              <button class="icon-btn" type="button" title="Settings">
                <i class="ph ph-gear"></i>
              </button>
            </div>
          </div>

          <div class="chat-subrow">
            <div class="room-meta">
              <span class="room-pill"><span class="online-dot"></span> Online: <b id="onlineCount">128</b></span>
              <span class="room-pill"><i class="ph ph-shield-check"></i> Safe Chat</span>
            </div>

            <div class="chat-search">
              <i class="ph ph-magnifying-glass"></i>
              <input id="chatSearch" type="text" placeholder="Search messages..." />
            </div>
          </div>
        </div>

        <!-- BODY -->
        <div class="chat-body" id="chatWindow">

          <div class="time-divider"><span>10:54</span></div>

          <!-- Message -->
          <div class="msg-row" data-tab="personal" data-text="hi kiwi">
            <div class="avatar-box">
              <img src="https://i.pravatar.cc/80?u=vip7" alt="">
              <span class="avatar-ring ring-gold"></span>
            </div>
            <div class="msg-wrap">
              <div class="msg-head">
                <span class="vip"><i class="ph ph-crown"></i> VIP 7</span>
                <span class="uname">[DRS] My Prison</span>
                <span class="meta"><i class="ph ph-clock"></i> 10:54</span>
              </div>
              <div class="bubble">Hi Kiwi! 😊</div>
            </div>
          </div>

          <div class="time-divider"><span>11:34</span></div>

          <!-- Message -->
          <div class="msg-row" data-tab="personal" data-text="ok who killed the panda and made him leave">
            <div class="avatar-box">
              <img src="https://i.pravatar.cc/80?u=vip4" alt="">
              <span class="avatar-ring ring-silver"></span>
            </div>
            <div class="msg-wrap">
              <div class="msg-head">
                <span class="vip purple"><i class="ph ph-star-four"></i> VIP 4</span>
                <span class="uname">[LDL] Phantom Wolf</span>
                <span class="meta"><i class="ph ph-clock"></i> 11:34</span>
              </div>
              <div class="bubble">
                Ok who killed the panda and made him leave lol we all need a snuggle panda around
                <div class="reactions">
                  <span class="rxn"><i class="ph ph-thumbs-down"></i> 1</span>
                </div>
              </div>
            </div>
          </div>

          <!-- ME (example) -->
          <div class="msg-row me hidden" id="meTemplate">
            <div class="msg-wrap">
              <div class="msg-head">
                <span class="meta"><i class="ph ph-user-circle"></i> You • <span class="me-time"></span></span>
              </div>
              <div class="bubble me-bubble"></div>
            </div>
          </div>

        </div>

        <!-- FOOTER -->
        <div class="chat-footer">
          <button class="tool-btn" type="button" title="Emoji"><i class="ph ph-smiley"></i></button>
          <button class="tool-btn" type="button" title="Attach"><i class="ph ph-paperclip"></i></button>

          <div class="composer">
            <i class="ph ph-pencil-simple-line" style="color:var(--chat-muted);font-size:18px;"></i>
            <input id="chatInput" type="text" placeholder="Type your message..." />
          </div>

          <button class="send-btn" id="sendBtn" type="button" title="Send">
            <i class="ph ph-paper-plane-tilt"></i>
          </button>
        </div>

      </div>
    </main>

    <aside class="right-panel">
      <?php $this->load->view('user/layout/v2/user_inner_right_panle'); ?>
    </aside>
  </div>

  <script>
    const CHAT_FETCH_URL = "<?php echo $chat_fetch_url; ?>";
    const CHAT_SEND_URL = "<?php echo $chat_send_url; ?>";
    const CURRENT_USER_ID = "<?php echo (int) $user_id; ?>";
    const CURRENT_USERNAME = "<?php echo html_escape($username ?? 'User'); ?>";
  </script>

  <script src="<?php echo base_url(); ?>/assets/user_v2/js/script.js?ver=2.9"></script>
  <!-- <script>
    // ===== Tabs (World / Team / Personal) =====
    const tabs = document.querySelectorAll('#chatTabs .tab');
    const messages = document.querySelectorAll('#chatWindow .msg-row');

    function setTab(key) {
      tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === key));
      messages.forEach(m => {
        const mt = m.getAttribute('data-tab');
        if (!mt) { return; } // templates etc
        m.style.display = (mt === key) ? '' : 'none';
      });
    }

    tabs.forEach(t => {
      t.addEventListener('click', () => setTab(t.dataset.tab));
    });

    // default
    setTab('personal');

    // ===== Search =====
    const search = document.getElementById('chatSearch');
    search?.addEventListener('input', () => {
      const q = (search.value || '').toLowerCase().trim();
      const activeTab = document.querySelector('#chatTabs .tab.active')?.dataset.tab;

      messages.forEach(m => {
        const mt = m.getAttribute('data-tab');
        if (!mt || mt !== activeTab) return;

        const text = (m.getAttribute('data-text') || m.innerText || '').toLowerCase();
        m.style.display = (!q || text.includes(q)) ? '' : 'none';
      });
    });

    // ===== Send message (UI only demo) =====
    const input = document.getElementById('chatInput');
    const sendBtn = document.getElementById('sendBtn');
    const chatWindow = document.getElementById('chatWindow');
    const meTemplate = document.getElementById('meTemplate');

    function nowTime() {
      const d = new Date();
      const hh = String(d.getHours()).padStart(2, '0');
      const mm = String(d.getMinutes()).padStart(2, '0');
      return `${hh}:${mm}`;
    }

    function sendMessage() {
      const val = (input.value || '').trim();
      if (!val) return;

      const row = meTemplate.cloneNode(true);
      row.classList.remove('hidden');
      row.removeAttribute('id');
      row.querySelector('.me-bubble').textContent = val;
      row.querySelector('.me-time').textContent = nowTime();

      // show in active tab only (personal)
      row.setAttribute('data-tab', 'personal');
      row.setAttribute('data-text', val.toLowerCase());

      chatWindow.appendChild(row);
      input.value = '';
      chatWindow.scrollTop = chatWindow.scrollHeight;
    }

    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') sendMessage();
    });
  </script> -->

  <script>
    // ===== Tabs =====
    const tabs = document.querySelectorAll('#chatTabs .tab');
    const chatWindow = document.getElementById('chatWindow');
    const search = document.getElementById('chatSearch');
    const input = document.getElementById('chatInput');
    const sendBtn = document.getElementById('sendBtn');
    const meTemplate = document.getElementById('meTemplate');

    let ACTIVE_ROOM = 'personal';     // world | team | personal
    let lastIdByRoom = { world: 0, team: 0, personal: 0 };
    let pollingTimer = null;

    function nowTime() {
      const d = new Date();
      const hh = String(d.getHours()).padStart(2, '0');
      const mm = String(d.getMinutes()).padStart(2, '0');
      return `${hh}:${mm}`;
    }

    function setTab(key) {
      ACTIVE_ROOM = key;
      tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === key));

      // show/hide existing DOM messages by data-tab
      const messages = document.querySelectorAll('#chatWindow .msg-row');
      messages.forEach(m => {
        const mt = m.getAttribute('data-tab');
        if (!mt) return;
        m.style.display = (mt === mapRoomKey(key)) ? '' : 'none';
      });

      // fetch immediately for that room
      fetchNew(true);
    }

    // Your UI uses: world, team, personal
    // We'll store room values as: world, team, personal
    function mapRoomKey(tabKey) {
      return tabKey; // same
    }

    tabs.forEach(t => t.addEventListener('click', () => setTab(t.dataset.tab)));

    // default
    setTab('personal');

    // ===== Search inside active room =====
    search?.addEventListener('input', () => {
      const q = (search.value || '').toLowerCase().trim();
      const activeTab = document.querySelector('#chatTabs .tab.active')?.dataset.tab;

      const messages = document.querySelectorAll('#chatWindow .msg-row');
      messages.forEach(m => {
        const mt = m.getAttribute('data-tab');
        if (!mt || mt !== activeTab) return;

        const text = (m.getAttribute('data-text') || m.innerText || '').toLowerCase();
        m.style.display = (!q || text.includes(q)) ? '' : 'none';
      });
    });

    // ===== Render message DOM =====
    function escapeText(s) {
      return String(s ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    }

    function formatHHMM(datetimeStr) {
      // datetimeStr "YYYY-MM-DD HH:MM:SS"
      try {
        const d = new Date(datetimeStr.replace(' ', 'T'));
        const hh = String(d.getHours()).padStart(2, '0');
        const mm = String(d.getMinutes()).padStart(2, '0');
        return `${hh}:${mm}`;
      } catch {
        return nowTime();
      }
    }

    function appendMessage(room, msg) {
      // msg: {id, user_id, username, message, created_at}
      const isMe = String(msg.user_id) === String(CURRENT_USER_ID);

      // create row
      let row;
      if (isMe) {
        row = meTemplate.cloneNode(true);
        row.classList.remove('hidden');
        row.removeAttribute('id');
        row.classList.add('me');
        row.querySelector('.me-bubble').innerHTML = escapeText(msg.message);
        row.querySelector('.me-time').textContent = formatHHMM(msg.created_at);
      } else {
        row = document.createElement('div');
        row.className = 'msg-row';
        row.innerHTML = `
        <div class="avatar-box">
          <img src="https://i.pravatar.cc/80?u=${encodeURIComponent(msg.user_id)}" alt="">
          <span class="avatar-ring ring-silver"></span>
        </div>
        <div class="msg-wrap">
          <div class="msg-head">
            <span class="vip purple"><i class="ph ph-star-four"></i> USER</span>
            <span class="uname">${escapeText(msg.username)}</span>
            <span class="meta"><i class="ph ph-clock"></i> ${escapeText(formatHHMM(msg.created_at))}</span>
          </div>
          <div class="bubble">${escapeText(msg.message)}</div>
        </div>
      `;
      }

      row.setAttribute('data-tab', room);
      row.setAttribute('data-text', (msg.message || '').toLowerCase());

      // only display if matches active room
      row.style.display = (ACTIVE_ROOM === room) ? '' : 'none';

      chatWindow.appendChild(row);
    }

    // ===== API =====
    async function fetchNew(forceRoom = false) {
      const room = ACTIVE_ROOM;
      const after = lastIdByRoom[room] || 0;

      // if switching tabs, you may want to load history too (optional)
      // We'll only fetch new after lastId. If lastId==0, it will fetch latest in ascending order.
      const url = `${CHAT_FETCH_URL}?room=${encodeURIComponent(room)}&after=${after}`;

      try {
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();

        if (!data.ok) return;

        const list = data.messages || [];
        if (list.length) {
          list.forEach(m => appendMessage(room, m));
          lastIdByRoom[room] = list[list.length - 1].id;

          // scroll to bottom only when active room
          if (ACTIVE_ROOM === room) {
            chatWindow.scrollTop = chatWindow.scrollHeight;
          }
        }
      } catch (e) {
        // silent
      }
    }

    async function sendMessage() {
      const message = (input.value || '').trim();
      if (!message) return;

      // Optimistic clear
      input.value = '';

      const fd = new FormData();
      fd.append('room', ACTIVE_ROOM);
      fd.append('message', message);

      try {
        const res = await fetch(CHAT_SEND_URL, {
          method: 'POST',
          body: fd,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (!data.ok) {
          alert(data.message || 'Send failed');
          return;
        }
        // Immediately fetch (so it appears in UI with id/time)
        await fetchNew();
      } catch (e) {
        alert('Network error');
      }
    }

    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') sendMessage();
    });

    // ===== Polling every 1s =====
    function startPolling() {
      if (pollingTimer) clearInterval(pollingTimer);
      pollingTimer = setInterval(() => fetchNew(), 1000);
    }
    startPolling();

  </script>

</body>

</html>