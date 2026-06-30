<!DOCTYPE html>
<html lang="en">

<head>
    <?php $this->load->view('user/layout/v2/user_style'); ?>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <!-- React CDN -->
    <script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>

    <!-- Swal -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Emoji Picker Web Component -->
    <script type="module" src="https://unpkg.com/emoji-picker-element@^1/index.js"></script>

    <style>
        :root {
            --chat-bg: #f4f7fe;
            --chat-card: #ffffff;
            --chat-line: #f1f1f6;
            --chat-muted: #8E8E93;
            --chat-text: #1A1A1A;
            --primary: #6E56CF;
            --primary-2: #4c3ba0;
            --shadow: 0 16px 40px rgba(0, 0, 0, .06);
            --radius: 24px;
        }

        .mlm-chat-shell {
            background: var(--chat-card);
            border: 1px solid #f5f5f7;
            border-radius: var(--radius);
            box-shadow: var(--shadow);

            height: 100vh;
            /* full viewport */
            display: flex;
            flex-direction: column;
            overflow: hidden;
            /* prevent outer scroll */
        }

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
            flex-wrap: wrap;
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
            white-space: nowrap;
        }

        .tab:hover {
            background: rgba(255, 255, 255, .12);
        }

        .tab.active {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 10px 25px rgba(110, 86, 207, .25);
        }

        .tab:disabled {
            opacity: .6;
            cursor: not-allowed;
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

        .icon-btn:disabled {
            opacity: .6;
            cursor: not-allowed;
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
            flex-wrap: wrap;
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

        /* body wrapper */
        .chat-body {
            flex: 1;
            display: flex;
            background: var(--chat-bg);

            min-height: 0;
            /* CRITICAL FIX */
            overflow: hidden;
        }

        /* ---------- PERSONAL SPLIT LAYOUT ---------- */
        .personal-split {
            flex: 1;
            display: grid;
            grid-template-columns: 320px 1fr;

            min-height: 0;
            /* IMPORTANT */
        }

        .recent-panel {
            background: #fff;
            border-right: 1px solid var(--chat-line);
            overflow-y: auto;
            padding: 14px;
        }

        .recent-title {
            font-weight: 1100;
            font-size: 12px;
            letter-spacing: .6px;
            color: #6b7280;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .recent-item {
            border: 1px solid #eee;
            border-radius: 14px;
            padding: 10px 12px;
            margin-bottom: 10px;
            cursor: pointer;
            display: flex;
            gap: 10px;
            align-items: center;
            transition: .12s;
        }

        .recent-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 18px rgba(0, 0, 0, .05);
        }

        .recent-item.active {
            border-color: rgba(110, 86, 207, .35);
            box-shadow: 0 14px 26px rgba(110, 86, 207, .10);
            background: rgba(110, 86, 207, .04);
        }

        .recent-avatar {
            width: 40px;
            height: 40px;
            border-radius: 14px;
            object-fit: cover;
            border: 1px solid #eee;
            background: #fff;
            flex-shrink: 0;
        }

        .recent-main {
            flex: 1;
            min-width: 0;
        }

        .recent-name {
            font-weight: 1100;
            font-size: 13px;
            color: #111;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .recent-time {
            font-size: 11px;
            font-weight: 900;
            color: #9ca3af;
            flex-shrink: 0;
            margin-left: 12px;
        }

        .recent-preview {
            margin-top: 3px;
            font-weight: 900;
            font-size: 12px;
            color: #6b7280;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        .recent-empty {
            opacity: .65;
            font-weight: 900;
            padding: 10px;
        }

        .chat-panel {
            overflow-y: auto;
            padding: 18px;
            scroll-behavior: smooth;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
        }

        /* ---------- WORLD/TEAM FULL PANEL ---------- */
        .full-panel {
            height: 100%;
            overflow-y: auto;
            padding: 18px;
            scroll-behavior: smooth;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
        }

        /* ---------- MESSAGES ---------- */
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
            overflow: hidden;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            cursor: pointer;
            /* clickable */
        }

        .avatar-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
            background: rgba(110, 86, 207, .12);
            border: 1px solid rgba(110, 86, 207, .25);
            color: var(--primary);
            display: inline-flex;
            gap: 6px;
            align-items: center;
        }

        .uname {
            font-size: 13px;
            font-weight: 1100;
            color: #374151;
            cursor: pointer;
            /* clickable */
        }

        .uname:hover {
            text-decoration: underline;
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
            word-break: break-word;
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

        .msg-row.me .bubble {
            background: linear-gradient(105deg, var(--primary) 0%, var(--primary-2) 100%);
            border: none;
            color: #fff;
            box-shadow: 0 14px 26px rgba(110, 86, 207, .20);
        }

        .msg-row.me .bubble:before {
            display: none;
        }

        .msg-row.me .avatar-box {
            display: none;
        }

        .attach-card {
            margin-top: 10px;
            background: #f7f7fb;
            border: 1px solid #eee;
            padding: 10px 12px;
            border-radius: 14px;
            display: inline-flex;
            gap: 10px;
            align-items: center;
            text-decoration: none;
            color: #111;
        }

        .attach-img {
            margin-top: 10px;
            display: block;
            max-width: 320px;
            border-radius: 14px;
            border: 1px solid #eee;
        }

        .chat-footer {
            background: #fff;
            border-top: 1px solid var(--chat-line);
            padding: 12px 14px;
            display: flex;
            gap: 10px;
            align-items: center;
            position: relative;
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

        .tool-btn:disabled {
            opacity: .6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
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
            min-width: 0;
        }

        .composer input {
            border: none;
            outline: none;
            background: transparent;
            width: 100%;
            font-size: 13px;
            font-weight: 900;
            min-width: 0;
        }

        .composer input:disabled {
            opacity: .6;
            cursor: not-allowed;
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

        .send-btn:disabled {
            opacity: .55;
            cursor: not-allowed;
            box-shadow: none;
        }

        .emoji-pop {
            position: absolute;
            left: 12px;
            bottom: 74px;
            background: #fff;
            border: 1px solid #eee;
            box-shadow: 0 16px 40px rgba(0, 0, 0, .12);
            border-radius: 16px;
            overflow: hidden;
            z-index: 99;
        }

        /* preview modal */
        .preview-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .45);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 16px;
        }

        .preview-card {
            width: 520px;
            max-width: 96vw;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 18px 60px rgba(0, 0, 0, .18);
            overflow: hidden;
        }

        .preview-head {
            padding: 14px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #f1f1f6;
        }

        .preview-title {
            font-weight: 1000;
            color: #111;
            font-size: 14px;
        }

        .preview-close {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            border: 1px solid #f1f1f6;
            background: #f7f7fb;
            display: grid;
            place-items: center;
            cursor: pointer;
        }

        .preview-body {
            padding: 16px;
        }

        .preview-img {
            width: 100%;
            max-height: 320px;
            object-fit: contain;
            border-radius: 16px;
            border: 1px solid #eee;
            background: #fafafa;
        }

        .preview-file {
            border: 1px solid #eee;
            border-radius: 16px;
            padding: 12px;
            background: #f7f7fb;
            display: flex;
            gap: 10px;
            align-items: center;
            font-weight: 900;
        }

        .preview-meta {
            margin-top: 10px;
            font-size: 12px;
            color: #6b7280;
            font-weight: 800;
        }

        .preview-caption {
            margin-top: 12px;
            width: 100%;
            border: 1px solid #f1f1f6;
            background: #fff;
            border-radius: 14px;
            padding: 10px 12px;
            font-weight: 900;
            outline: none;
        }

        .preview-actions {
            padding: 14px 16px;
            border-top: 1px solid #f1f1f6;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-lite {
            border: 1px solid #eee;
            background: #f7f7fb;
            padding: 10px 14px;
            border-radius: 14px;
            font-weight: 1000;
            cursor: pointer;
        }

        .btn-primary {
            border: none;
            background: var(--primary);
            color: #fff;
            padding: 10px 14px;
            border-radius: 14px;
            font-weight: 1000;
            cursor: pointer;
        }

        .btn-lite:disabled,
        .btn-primary:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        @media(max-width: 980px) {
            .personal-split {
                grid-template-columns: 280px 1fr;
            }
        }

        @media(max-width: 760px) {
            .personal-split {
                grid-template-columns: 1fr;
            }

            .recent-panel {
                display: none;
                /* mobile hide left recent */
            }
        }

        /* =========================
   MOBILE CHAT IMPROVEMENTS
   ========================= */

        @media (max-width: 760px) {

            /* Make shell full height */
            .mlm-chat-shell {
                min-height: calc(100vh - 120px);
                border-radius: 16px;
            }

            /* Make topbar stack nicely */
            .chat-toprow {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }

            .chat-tabs {
                overflow-x: auto;
                flex-wrap: nowrap;
                padding-bottom: 4px;
            }

            .chat-tabs::-webkit-scrollbar {
                display: none;
            }

            .tab {
                flex-shrink: 0;
                font-size: 11px;
                padding: 8px 12px;
            }

            /* Sub row stack */
            .chat-subrow {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
            }

            /* Messages padding smaller */
            .chat-panel,
            .full-panel {
                padding: 14px;
            }

            /* Smaller avatars */
            .avatar-box {
                width: 42px;
                height: 42px;
                border-radius: 12px;
            }

            /* Reduce message width */
            .msg-wrap {
                max-width: 88%;
                min-width: 120px;
            }

            .bubble {
                font-size: 12px;
                padding: 10px 12px;
            }

            /* Footer stack */
            .chat-footer {
                padding: 10px;
                gap: 8px;
            }

            .tool-btn {
                width: 40px;
                height: 40px;
                border-radius: 14px;
            }

            .composer {
                padding: 8px 10px;
                border-radius: 14px;
            }

            .composer input {
                font-size: 12px;
            }

            .send-btn {
                width: 44px;
                height: 44px;
                border-radius: 14px;
            }

            /* Emoji popup adjust */
            .emoji-pop {
                left: 10px;
                right: 10px;
                width: auto;
                bottom: 68px;
            }
        }

        .chat-panel,
        .full-panel {
            flex: 1;
            overflow-y: auto;
            padding: 18px;

            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;

            display: flex;
            flex-direction: column;

            min-height: 0;
            /* IMPORTANT */
        }

        .chat-footer {
            background: #fff;
            border-top: 1px solid var(--chat-line);
            padding: 12px 14px;
            display: flex;
            gap: 10px;
            align-items: center;

            flex-shrink: 0;
            /* prevents collapsing */
            position: relative;
        }

        @media(max-width: 760px) {
            .personal-split {
                position: relative;
            }

            .recent-panel {
                position: absolute;
                inset: 0;
                z-index: 20;
                background: #fff;
                display: none;
            }

            .recent-panel.open {
                display: block;
            }
        }
    </style>

    <script>
        const CHAT_FETCH_URL = "<?php echo $chat_fetch_url; ?>";
        const CHAT_SEND_URL = "<?php echo $chat_send_url; ?>";
        const CURRENT_USER_ID = "<?php echo (int) $user_id; ?>";
        const CURRENT_USERNAME = "<?php echo html_escape($username ?? 'User'); ?>";
        const CHAT_RECENT_URL = "<?php echo base_url('user/chat/recent'); ?>";
    </script>
</head>

<body>
    <div class="app-container">
        <?php $this->load->view('user/layout/v2/user_sidebar'); ?>

        <main class="main-content">
            <?php $this->load->view('user/layout/v2/user_header'); ?>
            <div class="mlm-chat-shell" id="chatApp"></div>
        </main>

        <aside class="right-panel">
            <?php $this->load->view('user/layout/v2/user_inner_right_panle'); ?>
        </aside>
    </div>

    <script src="<?php echo base_url(); ?>/assets/user_v2/js/script.js?ver=2.9"></script>

    <script>
        const E = React.createElement;

        function swalError(message) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: message || "Something went wrong",
                buttonsStyling: false,
                confirmButtonText: "Ok, got it!",
                customClass: { confirmButton: "btn btn-primary" }
            });
        }

        function safeRoom(room) {
            return (room === 'world' || room === 'team' || room === 'personal') ? room : 'personal';
        }

        // for "YYYY-MM-DD HH:mm:ss"
        function formatHHMMFromSQL(dt) {
            try {
                const d = new Date(String(dt || '').replace(' ', 'T'));
                return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
            } catch {
                return '';
            }
        }

        function formatHHMM(datetimeStr) {
            try {
                const d = new Date((datetimeStr || '').replace(' ', 'T'));
                return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
            } catch {
                return '';
            }
        }

        function humanSize(bytes) {
            const b = Number(bytes || 0);
            if (!b) return "0 B";
            const units = ["B", "KB", "MB", "GB"];
            let i = 0, val = b;
            while (val >= 1024 && i < units.length - 1) { val /= 1024; i++; }
            return `${val.toFixed(i === 0 ? 0 : 1)} ${units[i]}`;
        }

        function ChatApp() {
            const [room, setRoom] = React.useState('personal'); // world|team|personal

            // selected peer for personal chat
            const [peerId, setPeerId] = React.useState(0);
            const [peerName, setPeerName] = React.useState('');

            const [messages, setMessages] = React.useState({ world: [], team: [], personal: [] });
            const [lastId, setLastId] = React.useState({ world: 0, team: 0, personal: 0 });

            const [sending, setSending] = React.useState(false);
            const [uploading, setUploading] = React.useState(false);
            const busy = sending || uploading;

            const [showEmoji, setShowEmoji] = React.useState(false);

            // attachment preview state
            const [pendingFile, setPendingFile] = React.useState(null);
            const [pendingUrl, setPendingUrl] = React.useState(null);
            const [pendingCaption, setPendingCaption] = React.useState('');

            // message input
            const [text, setText] = React.useState('');

            // refs
            const inputRef = React.useRef(null);
            const fileRef = React.useRef(null);
            const pickerRef = React.useRef(null);
            const pollTimerRef = React.useRef(null);

            // panels ref (scroll)
            const fullPanelRef = React.useRef(null);
            const personalPanelRef = React.useRef(null);

            // recent state
            const [recent, setRecent] = React.useState([]);

            const focusInput = () => setTimeout(() => inputRef.current?.focus(), 30);

            // smart autoscroll
            const shouldAutoScrollRef = React.useRef(true);

            const getScrollEl = () => {
                if (room === 'personal') return personalPanelRef.current;
                return fullPanelRef.current;
            };

            const isNearBottom = () => {
                const el = getScrollEl();
                if (!el) return true;
                const threshold = 140;
                return (el.scrollHeight - el.scrollTop - el.clientHeight) < threshold;
            };

            const handleScroll = () => { shouldAutoScrollRef.current = isNearBottom(); };

            const scrollBottomIfAllowed = () => {
                const el = getScrollEl();
                if (!el) return;
                if (!shouldAutoScrollRef.current) return;
                el.scrollTop = el.scrollHeight;
            };

            const forceScrollBottom = () => {
                const el = getScrollEl();
                if (!el) return;
                shouldAutoScrollRef.current = true;
                el.scrollTop = el.scrollHeight;
            };

            // cleanup objectURL
            React.useEffect(() => {
                return () => { if (pendingUrl) URL.revokeObjectURL(pendingUrl); };
            }, [pendingUrl]);

            // emoji picker insert
            React.useEffect(() => {
                if (!showEmoji) return;
                const picker = pickerRef.current;
                if (!picker) return;

                const onEmoji = (e) => {
                    const emoji = e?.detail?.unicode || '';
                    if (!emoji) return;
                    setText(prev => (prev || '') + emoji);
                    focusInput();
                };

                picker.addEventListener('emoji-click', onEmoji);
                return () => picker.removeEventListener('emoji-click', onEmoji);
            }, [showEmoji]);

            // de-dupe message ids by room
            const renderedIdsRef = React.useRef({
                world: new Set(),
                team: new Set(),
                personal: new Set(),
            });

            const mergeMessages = React.useCallback((roomKey, list) => {
                if (!list || !list.length) return;

                const ids = renderedIdsRef.current[roomKey];
                const newOnes = [];
                let maxId = lastId[roomKey] || 0;

                for (const m of list) {
                    const idStr = String(m.id);
                    if (ids.has(idStr)) continue;
                    ids.add(idStr);
                    newOnes.push(m);

                    const mid = parseInt(m.id, 10);
                    if (!Number.isNaN(mid)) maxId = Math.max(maxId, mid);
                }

                if (newOnes.length) {
                    setMessages(prev => ({ ...prev, [roomKey]: [...prev[roomKey], ...newOnes] }));
                    setLastId(prev => ({ ...prev, [roomKey]: maxId }));
                    setTimeout(scrollBottomIfAllowed, 0);
                }
            }, [lastId]);

            // fetch recent
            const fetchRecent = React.useCallback(async () => {
                try {
                    const res = await fetch(`${CHAT_RECENT_URL}?limit=50`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const data = await res.json();
                    if (!data.ok) return;
                    setRecent(data.items || []);
                } catch { }
            }, []);

            React.useEffect(() => { fetchRecent(); }, [fetchRecent]);
            React.useEffect(() => {
                const t = setInterval(() => fetchRecent(), 5000);
                return () => clearInterval(t);
            }, [fetchRecent]);

            // ✅ normalize + de-duplicate recent by peer_id (keep latest)
            const recentUnique = React.useMemo(() => {
                const map = new Map();

                for (const it of (recent || [])) {
                    const pid = String(it.peer_id || '');
                    if (!pid) continue;

                    const t = String(it.last_message_time || '').replace(' ', 'T');
                    const ts = Date.parse(t) || 0;

                    const prev = map.get(pid);
                    if (!prev || (prev._ts || 0) < ts) {
                        map.set(pid, {
                            peer_id: it.peer_id,
                            peer_name: it.peer_name || 'User',
                            last_message_time: it.last_message_time || '',
                            last_message: it.last_message || '',
                            last_message_type: it.last_message_type || 'text',
                            _ts: ts
                        });
                    }
                }

                return Array.from(map.values()).sort((a, b) => (b._ts || 0) - (a._ts || 0));
            }, [recent]);

            // fetch new messages (polling)
            const fetchNew = React.useCallback(async (roomKey) => {
                roomKey = safeRoom(roomKey);
                const after = lastId[roomKey] || 0;

                // personal requires peer
                if (roomKey === 'personal' && !peerId) return;

                let url = `${CHAT_FETCH_URL}?room=${encodeURIComponent(roomKey)}&after=${after}`;
                if (roomKey === 'personal') url += `&peer_id=${encodeURIComponent(peerId)}`;

                try {
                    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const data = await res.json();
                    if (!data.ok) return;
                    mergeMessages(roomKey, data.messages || []);
                } catch { }
            }, [lastId, peerId, mergeMessages]);

            // polling loop
            React.useEffect(() => {
                let stopped = false;

                const loop = async () => {
                    if (stopped) return;
                    if (document.visibilityState === 'visible' && !busy && !pendingFile) {
                        await fetchNew(room);
                    }
                    pollTimerRef.current = setTimeout(loop, 2000);
                };

                loop();

                const onVis = () => { if (document.visibilityState === 'visible') fetchNew(room); };
                document.addEventListener('visibilitychange', onVis);

                return () => {
                    stopped = true;
                    document.removeEventListener('visibilitychange', onVis);
                    if (pollTimerRef.current) clearTimeout(pollTimerRef.current);
                };
            }, [room, busy, pendingFile, fetchNew]);

            // on room change
            React.useEffect(() => {
                setShowEmoji(false);
                focusInput();

                // reset cache on tab switch
                renderedIdsRef.current[room] = new Set();
                fetchNew(room);
                setTimeout(forceScrollBottom, 30);
            }, [room]);

            // ✅ open personal peer-peer chat (used by recent click + username click)
            const openPersonal = React.useCallback((pid, pname) => {
                const id = parseInt(pid, 10) || 0;
                if (!id) return;

                setRoom('personal');
                setPeerId(id);
                setPeerName(pname || '');

                // reset personal cache
                renderedIdsRef.current.personal = new Set();
                setMessages(prev => ({ ...prev, personal: [] }));
                setLastId(prev => ({ ...prev, personal: 0 }));

                setTimeout(() => fetchNew('personal'), 0);
                setTimeout(() => forceScrollBottom(), 40);
            }, [fetchNew]);

            const sendText = async () => {
                const msg = (text || '').trim();
                if (busy) return;
                if (!msg) return;

                if (room === 'personal' && !peerId) {
                    swalError("Select a user from Recent Chats (Personal).");
                    return;
                }

                setSending(true);
                setShowEmoji(false);

                const fd = new FormData();
                fd.append('room', room);
                fd.append('message', msg);
                if (room === 'personal') fd.append('peer_id', String(peerId));

                setText('');

                try {
                    const res = await fetch(CHAT_SEND_URL, {
                        method: 'POST',
                        body: fd,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    if (!data.ok) {
                        swalError(data.message || 'Send failed');
                        return;
                    }

                    await fetchNew(room);
                    setTimeout(forceScrollBottom, 0);
                } catch {
                    swalError("Network error while sending.");
                } finally {
                    setSending(false);
                    focusInput();
                }
            };

            const validateFile = (file) => {
                const maxMB = 5;
                const sizeMB = file.size / (1024 * 1024);
                if (sizeMB > maxMB) return `File too large. Max ${maxMB}MB allowed.`;

                const allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip'];
                const ext = (file.name.split('.').pop() || '').toLowerCase();
                if (!allowedExt.includes(ext)) return "File type not allowed.";
                return null;
            };

            const pickFile = () => {
                if (busy) return;
                if (room === 'personal' && !peerId) {
                    swalError("Select a user from Recent Chats first (Personal).");
                    return;
                }
                setShowEmoji(false);
                fileRef.current?.click();
            };

            // select file -> preview modal
            const onFileChange = (e) => {
                const file = e.target.files?.[0];
                e.target.value = '';
                if (!file) return;
                if (busy) return;

                const err = validateFile(file);
                if (err) { swalError(err); return; }

                if (pendingUrl) URL.revokeObjectURL(pendingUrl);

                const isImage = (file.type || '').startsWith('image/');
                const url = isImage ? URL.createObjectURL(file) : null;

                setPendingFile(file);
                setPendingUrl(url);
                setPendingCaption(text || '');
                setText('');
                setShowEmoji(false);
            };

            const closePreview = () => {
                if (pendingUrl) URL.revokeObjectURL(pendingUrl);
                setPendingFile(null);
                setPendingUrl(null);
                setPendingCaption('');
                focusInput();
            };

            const submitAttachment = async () => {
                if (!pendingFile) return;
                if (busy) return;

                if (room === 'personal' && !peerId) {
                    swalError("Select a user from Recent Chats first (Personal).");
                    return;
                }

                setUploading(true);

                const fd = new FormData();
                fd.append('room', room);
                fd.append('message', (pendingCaption || '').trim());
                fd.append('chat_file', pendingFile);
                if (room === 'personal') fd.append('peer_id', String(peerId));

                try {
                    const res = await fetch(CHAT_SEND_URL, {
                        method: 'POST',
                        body: fd,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    if (!data.ok) {
                        swalError(data.message || 'Upload failed');
                        return;
                    }

                    closePreview();
                    await fetchNew(room);
                    setTimeout(forceScrollBottom, 0);
                } catch {
                    swalError("Network error while uploading.");
                } finally {
                    setUploading(false);
                    focusInput();
                }
            };

            const roomLabel = (r) => r === 'team' ? 'Alliance (Team)' : (r === 'world' ? 'World' : 'Personal');

            // no global search now (removed)
            const list = (messages[room] || []);

            const MessageRow = ({ m }) => {
                const isMe = String(m.user_id) === String(CURRENT_USER_ID);

                let attach = null;
                if (m.file_url) {
                    if (m.message_type === 'image') {
                        attach = E('a', { href: m.file_url, target: "_blank", rel: "noreferrer" },
                            E('img', { className: "attach-img", src: m.file_url, alt: m.file_name || 'image' })
                        );
                    } else {
                        attach = E('a', { className: "attach-card", href: m.file_url, target: "_blank", rel: "noreferrer" },
                            E('span', null, '📎'),
                            E('b', null, m.file_name || 'Download file')
                        );
                    }
                }

                // ✅ click avatar/name in WORLD or TEAM => open personal peer-peer
                const canOpenPeer = (!isMe && (room === 'world' || room === 'team'));
                const onOpenPeer = () => {
                    if (!canOpenPeer) return;
                    openPersonal(m.user_id, m.username || 'User');
                };

                return E('div', { className: "msg-row" + (isMe ? " me" : "") },
                    !isMe && E('div', { className: "avatar-box", onClick: onOpenPeer, title: canOpenPeer ? "Open personal chat" : "" },
                        E('img', { src: "https://i.pravatar.cc/80?u=" + encodeURIComponent(m.user_id), alt: "user" })
                    ),
                    E('div', { className: "msg-wrap" },
                        E('div', { className: "msg-head" },
                            !isMe && E('span', { className: "vip" }, E('i', { className: "ph ph-star-four" }), ""),
                            !isMe && E('span', { className: "uname", onClick: onOpenPeer, title: canOpenPeer ? "Open personal chat" : "" }, m.username || 'User'),
                            E('span', { className: "meta" }, E('i', { className: "ph ph-clock" }), " ", formatHHMM(m.created_at))
                        ),
                        E('div', { className: "bubble" },
                            E('div', null, m.message || ''),
                            attach
                        )
                    )
                );
            };

            return E(React.Fragment, null,

                // Preview Modal
                pendingFile && E('div', { className: "preview-backdrop", onClick: (e) => { if (e.target.classList.contains('preview-backdrop')) closePreview(); } },
                    E('div', { className: "preview-card" },
                        E('div', { className: "preview-head" },
                            E('div', { className: "preview-title" }, "Preview Attachment"),
                            E('button', { className: "preview-close", type: "button", onClick: closePreview, disabled: uploading },
                                E('i', { className: "ph ph-x" })
                            )
                        ),
                        E('div', { className: "preview-body" },
                            pendingUrl
                                ? E('img', { className: "preview-img", src: pendingUrl, alt: "preview" })
                                : E('div', { className: "preview-file" },
                                    E('span', null, '📎'),
                                    E('div', null,
                                        E('div', null, pendingFile.name),
                                        E('div', { className: "preview-meta" }, `${humanSize(pendingFile.size)} • ${(pendingFile.type || 'file')}`)
                                    )
                                ),
                            E('input', {
                                className: "preview-caption",
                                placeholder: "Add a caption (optional)...",
                                value: pendingCaption,
                                onChange: (e) => setPendingCaption(e.target.value),
                                disabled: uploading
                            })
                        ),
                        E('div', { className: "preview-actions" },
                            E('button', { className: "btn-lite", type: "button", onClick: closePreview, disabled: uploading }, "Cancel"),
                            E('button', { className: "btn-primary", type: "button", onClick: submitAttachment, disabled: uploading },
                                uploading ? "Uploading..." : "Send"
                            )
                        )
                    )
                ),

                // TOP BAR
                E('div', { className: "chat-topbar" },
                    E('div', { className: "chat-toprow" },
                        E('div', { className: "chat-tabs" },
                            E('button', { className: "tab" + (room === 'world' ? " active" : ""), type: "button", onClick: () => setRoom('world'), disabled: busy || !!pendingFile },
                                E('i', { className: "ph ph-globe-hemisphere-east" }), " World"
                            ),
                            E('button', { className: "tab" + (room === 'team' ? " active" : ""), type: "button", onClick: () => setRoom('team'), disabled: busy || !!pendingFile },
                                E('i', { className: "ph ph-users-three" }), " Alliance (Team)"
                            ),
                            E('button', { className: "tab" + (room === 'personal' ? " active" : ""), type: "button", onClick: () => setRoom('personal'), disabled: busy || !!pendingFile },
                                E('i', { className: "ph ph-chat-teardrop-text" }), " Personal"
                            ),
                        ),
                        E('div', { className: "chat-actions" },
                            E('button', { className: "icon-btn", type: "button", title: "Settings", disabled: busy || !!pendingFile },
                                E('i', { className: "ph ph-gear" })
                            )
                        )
                    ),

                    E('div', { className: "chat-subrow" },
                        E('div', { className: "room-meta" },
                            E('span', { className: "room-pill" }, E('span', { className: "online-dot" }), " Room: ", E('b', null, roomLabel(room))),
                            (room === 'personal' && peerId) && E('span', { className: "room-pill" }, E('i', { className: "ph ph-chat-circle-text" }), " Chatting: ", E('b', null, peerName || ('ID ' + peerId))),
                            (room === 'personal' && !peerId) && E('span', { className: "room-pill" }, E('i', { className: "ph ph-warning" }), " Select from Recent Chats"),
                            E('span', { className: "room-pill" }, E('i', { className: "ph ph-shield-check" }), " Safe Chat")
                        ),
                        E('div', { style: { color: 'rgba(255,255,255,.65)', fontWeight: 900, fontSize: 12 } },
                            (room === 'world' || room === 'team')
                                ? "Tip: Click any user's avatar/name to open Personal chat"
                                : ""
                        )
                    )
                ),

                // BODY
                E('div', { className: "chat-body" },

                    // ✅ PERSONAL: left recent panel + right message panel
                    (room === 'personal')
                        ? E('div', { className: "personal-split" },

                            // LEFT recent list
                            E('div', { className: "recent-panel" },
                                E('div', { className: "recent-title" }, "Recent Chats"),

                                (recentUnique.length === 0)
                                    ? E('div', { className: "recent-empty" }, "No recent chats yet…")
                                    : recentUnique.map(item => {
                                        const active = String(item.peer_id) === String(peerId);
                                        return E('div', {
                                            key: String(item.peer_id),
                                            className: "recent-item" + (active ? " active" : ""),
                                            onClick: () => openPersonal(item.peer_id, item.peer_name),
                                        },
                                            E('img', {
                                                className: "recent-avatar",
                                                src: `https://i.pravatar.cc/80?u=${encodeURIComponent(item.peer_id)}`,
                                                alt: "u"
                                            }),
                                            E('div', { className: "recent-main" },
                                                E('div', { style: { display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '10px' } },
                                                    E('div', { className: "recent-name" }, item.peer_name || 'User'),
                                                    E('div', { className: "recent-time" }, formatHHMMFromSQL(item.last_message_time))
                                                ),
                                                E('div', { className: "recent-preview" }, item.last_message || '')
                                            )
                                        );
                                    })
                            ),

                            // RIGHT messages panel
                            E('div', {
                                className: "chat-panel",
                                ref: personalPanelRef,
                                onScroll: handleScroll
                            },
                                (!peerId)
                                    ? E('div', { style: { opacity: .75, fontWeight: 1000, padding: '14px', lineHeight: 1.6 } },
                                        "Select a member from Recent Chats (left side) to start peer-to-peer chat."
                                    )
                                    : (list.length === 0
                                        ? E('div', { style: { opacity: .65, fontWeight: 900, padding: '10px' } }, "No messages yet…")
                                        : list.map(m => E(MessageRow, { key: m.id, m }))
                                    )
                            )
                        )

                        // ✅ WORLD / TEAM: full panel
                        : E('div', { className: "full-panel", ref: fullPanelRef, onScroll: handleScroll },
                            (list.length === 0)
                                ? E('div', { style: { opacity: .65, fontWeight: 900, padding: '10px' } }, "No messages yet…")
                                : list.map(m => E(MessageRow, { key: m.id, m }))
                        )
                ),

                // FOOTER
                E('div', { className: "chat-footer" },

                    showEmoji && !busy && !pendingFile && E('div', { className: "emoji-pop" },
                        E('emoji-picker', { ref: pickerRef, style: { width: "320px", height: "360px" } })
                    ),

                    E('button', {
                        className: "tool-btn",
                        type: "button",
                        title: busy ? "Please wait..." : "Emoji",
                        onClick: () => { if (!busy && !pendingFile) setShowEmoji(v => !v); },
                        disabled: busy || !!pendingFile
                    }, E('i', { className: "ph ph-smiley" })),

                    E('button', {
                        className: "tool-btn",
                        type: "button",
                        title: uploading ? "Uploading..." : "Attach",
                        onClick: pickFile,
                        disabled: busy || !!pendingFile || (room === 'personal' && !peerId)
                    }, E('i', { className: uploading ? "ph ph-hourglass" : "ph ph-paperclip" })),

                    E('input', {
                        ref: fileRef,
                        type: "file",
                        onChange: onFileChange,
                        style: { display: 'none' },
                        accept: ".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip"
                    }),

                    E('div', { className: "composer" },
                        E('i', { className: "ph ph-pencil-simple-line", style: { color: "var(--chat-muted)", fontSize: 18 } }),
                        E('input', {
                            ref: inputRef,
                            value: text,
                            onChange: (e) => setText(e.target.value),
                            placeholder: busy ? "Please wait..." : (room === 'personal' && !peerId ? "Select from Recent Chats..." : "Type your message..."),
                            onKeyDown: (e) => { if (e.key === 'Enter') sendText(); },
                            disabled: busy || !!pendingFile || (room === 'personal' && !peerId)
                        })
                    ),

                    E('button', {
                        className: "send-btn",
                        type: "button",
                        onClick: sendText,
                        disabled: busy || !!pendingFile || !text.trim() || (room === 'personal' && !peerId)
                    }, E('i', { className: sending ? "ph ph-hourglass" : "ph ph-paper-plane-tilt" }))
                )
            );
        }

        ReactDOM.createRoot(document.getElementById('chatApp')).render(E(ChatApp));
    </script>

</body>

</html>