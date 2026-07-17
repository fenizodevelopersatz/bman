<?php
/**
 * Member ▸ Chat — premium redesign.
 * ----------------------------------------------------------------------------
 * Three rooms:
 *   world     everyone on the platform
 *   team      the member's genealogy path (upline ∪ downline)
 *   personal  1:1 DM, restricted to that same path (enforced server-side)
 *
 * Transport: 2s cursor poll (GET user/chat/fetch?after=<lastId>) + 5s recent-list
 * poll. No websocket.
 *
 * THEMING — this page must NOT redeclare :root. The member panel's --primary and
 * --mp-* tokens are injected by user_style.php from site_settings('member_theme'),
 * so an admin can recolour the panel live. The previous version redeclared
 * :root{--primary:#6E56CF} in a <style> that loaded afterwards, silently
 * clobbering the admin's colour for the WHOLE page — sidebar and header included.
 * Everything here is scoped under .chatx and derives from the global tokens.
 *
 * Dark mode is honoured via html[data-bs-theme="dark"], matching style.css.
 *
 * @var int    $user_id
 * @var string $username
 * @var string $chat_fetch_url, $chat_send_url, $chat_recent_url
 * @var array  $team_members  [['id','username'], …] — who this member may DM
 */
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Chat</title>

    <?php $this->load->view('user/layout/v2/user_style'); ?>
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css" />
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/fill/style.css" />
    <script src="https://unpkg.com/react@18/umd/react.production.min.js" crossorigin></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js" crossorigin></script>
    <script type="module" src="https://unpkg.com/emoji-picker-element@^1/index.js"></script>

    <style>
        /* ============================================================
           Scoped under .chatx and DERIVED from the global member-theme
           tokens. Nothing here redeclares :root.
           ============================================================ */
        .chatx {
            --cx-p: var(--primary, #6E56CF);
            --cx-txt: var(--text-main, #1A1A1A);
            --cx-mut: var(--text-muted, #8E8E93);
            --cx-card: var(--bg-card, #fff);
            --cx-line: var(--line, #f0f0f0);
            --cx-soft: #f7f7fb;
            --cx-canvas: #f4f5fa;
            --cx-in: #fff;
            --cx-shadow: 0 12px 34px rgba(0, 0, 0, .06);
            --cx-r: 20px;
            display: flex;
            flex-direction: column;
            height: calc(100vh - 170px);
            min-height: 520px;
            border-radius: var(--cx-r);
            background: var(--cx-card);
            border: 1px solid var(--cx-line);
            box-shadow: var(--cx-shadow);
            overflow: hidden;
        }

        html[data-bs-theme="dark"] .chatx {
            --cx-soft: #1e1f26;
            --cx-canvas: #17181d;
            --cx-in: #23242c;
            --cx-line: #2b2c34;
            --cx-shadow: 0 12px 34px rgba(0, 0, 0, .4);
        }

        /* ---------------- titlebar ---------------- */
        .cx-title { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:16px; }
        .cx-title h1 { font-size:22px; font-weight:800; color:var(--text-main); display:flex; align-items:center; gap:8px; margin:0; }
        .cx-title h1 i { color:var(--primary); }
        .cx-title p { margin:4px 0 0; font-size:12.5px; color:var(--text-muted); }

        /* ---------------- topbar / room tabs ---------------- */
        .cx-top {
            display:flex; align-items:center; justify-content:space-between; gap:12px;
            padding:12px 16px; border-bottom:1px solid var(--cx-line);
            background:var(--cx-card); flex-wrap:wrap;
        }
        .cx-tabs { display:inline-flex; background:var(--cx-soft); padding:4px; border-radius:14px; gap:2px; }
        .cx-tab {
            border:0; background:transparent; font-family:inherit; font-size:12.5px; font-weight:700;
            color:var(--cx-mut); padding:8px 14px; border-radius:11px; cursor:pointer;
            display:inline-flex; align-items:center; gap:6px; transition:all .18s ease; white-space:nowrap;
        }
        .cx-tab:hover { color:var(--cx-txt); }
        .cx-tab.on { background:var(--cx-card); color:var(--cx-p); box-shadow:0 2px 8px rgba(0,0,0,.07); }
        .cx-peer { display:flex; align-items:center; gap:10px; min-width:0; }
        .cx-peer-name { font-size:13px; font-weight:800; color:var(--cx-txt); }
        .cx-peer-sub { font-size:10.5px; color:var(--cx-mut); }
        .cx-live { display:inline-flex; align-items:center; gap:5px; font-size:10px; font-weight:700; color:var(--cx-mut); }
        .cx-dot { width:7px; height:7px; border-radius:50%; background:var(--good,#22c55e); box-shadow:0 0 0 3px rgba(34,197,94,.18); }
        .cx-dot.off { background:var(--cx-mut); box-shadow:none; }

        /* ---------------- body ---------------- */
        .cx-body { flex:1; display:flex; min-height:0; position:relative; }

        /* conversation rail */
        .cx-rail { width:280px; flex:0 0 280px; border-right:1px solid var(--cx-line); display:flex; flex-direction:column; background:var(--cx-card); min-height:0; }
        .cx-rail-h { padding:12px 14px 10px; border-bottom:1px solid var(--cx-line); }
        .cx-search { position:relative; }
        .cx-search i { position:absolute; left:11px; top:50%; transform:translateY(-50%); color:var(--cx-mut); font-size:14px; }
        .cx-search input {
            width:100%; font-family:inherit; font-size:12.5px; padding:9px 11px 9px 32px; border-radius:11px;
            border:1px solid var(--cx-line); background:var(--cx-soft); color:var(--cx-txt); outline:none;
        }
        .cx-search input:focus { border-color:var(--cx-p); }
        .cx-rail-list { flex:1; overflow-y:auto; padding:8px; }
        .cx-conv {
            display:flex; gap:10px; align-items:center; padding:10px; border-radius:14px; cursor:pointer;
            transition:background .15s ease; border:1px solid transparent;
        }
        .cx-conv:hover { background:var(--cx-soft); }
        .cx-conv.on { background:var(--cx-soft); border-color:var(--cx-line); }
        .cx-conv-b { min-width:0; flex:1; }
        .cx-conv-n { font-size:12.5px; font-weight:700; color:var(--cx-txt); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .cx-conv-m { font-size:11px; color:var(--cx-mut); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px; }
        .cx-conv-t { font-size:9.5px; color:var(--cx-mut); white-space:nowrap; align-self:flex-start; }

        /* avatar — initials + deterministic colour. No third-party avatar service. */
        .cx-av {
            width:38px; height:38px; flex:0 0 38px; border-radius:50%; display:grid; place-items:center;
            color:#fff; font-weight:800; font-size:13px; letter-spacing:.3px; user-select:none;
        }
        .cx-av.sm { width:30px; height:30px; flex:0 0 30px; font-size:11px; }

        /* thread */
        .cx-thread { flex:1; display:flex; flex-direction:column; min-width:0; min-height:0; background:var(--cx-canvas); }
        .cx-scroll { flex:1; overflow-y:auto; padding:20px 18px; }
        .cx-day { text-align:center; margin:14px 0; }
        .cx-day span {
            font-size:10px; font-weight:700; color:var(--cx-mut); background:var(--cx-card);
            padding:4px 12px; border-radius:999px; border:1px solid var(--cx-line);
        }
        .cx-row { display:flex; gap:9px; margin-bottom:12px; align-items:flex-end; }
        .cx-row.me { flex-direction:row-reverse; }
        .cx-bub { max-width:min(560px, 74%); min-width:0; }
        .cx-who { font-size:10.5px; font-weight:700; color:var(--cx-mut); margin:0 4px 4px; }
        .cx-msg {
            padding:10px 13px; border-radius:16px; font-size:13px; line-height:1.55; color:var(--cx-txt);
            background:var(--cx-card); border:1px solid var(--cx-line);
            border-bottom-left-radius:5px; word-wrap:break-word; overflow-wrap:anywhere; white-space:pre-wrap;
        }
        .cx-row.me .cx-msg {
            background:var(--cx-p); color:#fff; border-color:transparent;
            border-bottom-left-radius:16px; border-bottom-right-radius:5px;
        }
        .cx-time { font-size:9.5px; color:var(--cx-mut); margin:4px 4px 0; }
        .cx-row.me .cx-time { text-align:right; }
        .cx-img { max-width:260px; border-radius:12px; display:block; cursor:zoom-in; }
        .cx-file {
            display:flex; align-items:center; gap:9px; text-decoration:none; color:inherit;
            background:rgba(0,0,0,.05); padding:8px 10px; border-radius:10px;
        }
        .cx-row.me .cx-file { background:rgba(255,255,255,.18); }
        .cx-file i { font-size:19px; }
        .cx-file b { font-size:11.5px; display:block; max-width:170px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .cx-file small { font-size:9.5px; opacity:.75; }

        /* states */
        .cx-empty { height:100%; display:grid; place-items:center; text-align:center; padding:30px; }
        .cx-empty i { font-size:42px; color:var(--cx-line); display:block; margin-bottom:12px; }
        .cx-empty b { font-size:14px; color:var(--cx-txt); display:block; margin-bottom:5px; }
        .cx-empty p { font-size:12px; color:var(--cx-mut); margin:0 auto; max-width:290px; line-height:1.6; }
        .cx-err {
            margin:10px 18px 0; padding:10px 12px; border-radius:12px; font-size:12px; font-weight:600;
            background:#fee2e2; color:#b91c1c; display:flex; align-items:center; gap:8px;
        }

        /* composer */
        .cx-foot { border-top:1px solid var(--cx-line); background:var(--cx-card); padding:11px 14px; position:relative; }
        .cx-compose { display:flex; align-items:flex-end; gap:8px; }
        .cx-ico {
            border:0; background:var(--cx-soft); color:var(--cx-mut); width:38px; height:38px; flex:0 0 38px;
            border-radius:12px; cursor:pointer; font-size:18px; display:grid; place-items:center; transition:all .15s ease;
        }
        .cx-ico:hover { color:var(--cx-p); background:var(--cx-line); }
        .cx-input {
            flex:1; min-width:0; font-family:inherit; font-size:13px; line-height:1.5; padding:10px 13px;
            border-radius:14px; border:1px solid var(--cx-line); background:var(--cx-in); color:var(--cx-txt);
            outline:none; resize:none; max-height:120px; min-height:40px;
        }
        .cx-input:focus { border-color:var(--cx-p); }
        .cx-input:disabled { opacity:.55; cursor:not-allowed; }
        .cx-send {
            border:0; background:var(--cx-p); color:#fff; width:38px; height:38px; flex:0 0 38px;
            border-radius:12px; cursor:pointer; font-size:17px; display:grid; place-items:center; transition:transform .12s ease;
        }
        .cx-send:hover:not(:disabled) { transform:scale(1.06); }
        .cx-send:disabled { opacity:.4; cursor:not-allowed; }
        .cx-hint { font-size:9.5px; color:var(--cx-mut); margin:6px 0 0 92px; }
        .cx-emoji { position:absolute; bottom:64px; left:14px; z-index:40; }

        /* attachment preview */
        .cx-att {
            display:flex; align-items:center; gap:10px; background:var(--cx-soft); border:1px solid var(--cx-line);
            border-radius:12px; padding:8px 10px; margin-bottom:9px;
        }
        .cx-att img { width:38px; height:38px; border-radius:8px; object-fit:cover; }
        .cx-att b { font-size:12px; color:var(--cx-txt); display:block; max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .cx-att small { font-size:10px; color:var(--cx-mut); }
        .cx-att button { margin-left:auto; border:0; background:transparent; color:var(--cx-mut); cursor:pointer; font-size:16px; }

        /* lightbox */
        .cx-light { position:fixed; inset:0; background:rgba(8,8,14,.86); z-index:9999; display:grid; place-items:center; padding:30px; }
        .cx-light img { max-width:92vw; max-height:88vh; border-radius:12px; }
        .cx-light button { position:absolute; top:18px; right:20px; border:0; background:rgba(255,255,255,.14); color:#fff; width:40px; height:40px; border-radius:50%; cursor:pointer; font-size:19px; }

        /* skeleton */
        .cx-sk { padding:20px 18px; }
        .cx-sk-r { display:flex; gap:9px; margin-bottom:14px; align-items:flex-end; }
        .cx-sk-r.me { flex-direction:row-reverse; }
        .cx-sk-a { width:38px; height:38px; border-radius:50%; flex:0 0 38px; }
        .cx-sk-b { height:38px; border-radius:16px; }
        .cx-shimmer {
            background:linear-gradient(90deg, var(--cx-line) 25%, var(--cx-soft) 50%, var(--cx-line) 75%);
            background-size:200% 100%; animation:cxsh 1.4s infinite;
        }
        @keyframes cxsh { 0% { background-position:200% 0; } 100% { background-position:-200% 0; } }

        .cx-scroll::-webkit-scrollbar, .cx-rail-list::-webkit-scrollbar { width:6px; }
        .cx-scroll::-webkit-scrollbar-thumb, .cx-rail-list::-webkit-scrollbar-thumb { background:var(--cx-line); border-radius:99px; }
        .cx-scroll::-webkit-scrollbar-track, .cx-rail-list::-webkit-scrollbar-track { background:transparent; }

        /* responsive */
        @media (max-width:900px) {
            .chatx { height:calc(100vh - 150px); }
            .cx-rail { width:240px; flex:0 0 240px; }
        }
        @media (max-width:680px) {
            .cx-rail { width:100%; flex:1 1 auto; border-right:0; }
            .cx-rail.hide-sm { display:none; }
            .cx-thread.hide-sm { display:none; }
            .cx-bub { max-width:84%; }
            .cx-hint { margin-left:0; text-align:center; }
        }
    </style>

    <script>
        const CHAT_FETCH_URL  = "<?php echo $chat_fetch_url; ?>";
        const CHAT_SEND_URL   = "<?php echo $chat_send_url; ?>";
        const CHAT_RECENT_URL = "<?php echo $chat_recent_url; ?>";
        const CURRENT_USER_ID = <?php echo (int) $user_id; ?>;
        const CURRENT_USERNAME = <?php echo json_encode($username ?? 'User'); ?>;
        /* Who this member may DM: their genealogy path. The server enforces the
           same allowlist on send AND fetch, so the picker can never offer someone
           the server would reject. */
        const CHAT_PEERS = <?php echo json_encode(array_map(function ($m) {
            return ['id' => (int) $m['id'], 'username' => (string) $m['username']];
        }, $team_members ?: [])); ?>;
    </script>
</head>

<body>
    <div class="app-container">
        <?php $this->load->view('user/layout/v2/user_sidebar'); ?>

        <main class="main-content">
            <?php $this->load->view('user/layout/v2/user_header'); ?>

            <div class="cx-title">
                <div>
                    <h1><i class="ph-fill ph-chats-circle"></i> Chat</h1>
                    <p>Talk to the whole platform, your team, or a member one-to-one.</p>
                </div>
            </div>

            <div class="chatx" id="chatApp"></div>
        </main>

        <aside class="right-panel">
            <?php $this->load->view('user/layout/v2/user_inner_right_panle'); ?>
        </aside>
    </div>

    <script>
        (function () {
            const { useState, useEffect, useRef, useCallback, useMemo } = React;
            const E = React.createElement;

            /* ---------------- helpers ---------------- */

            // Deterministic avatar colour from the user id. Replaces the old
            // i.pravatar.cc avatars, which shipped every member's user id to a
            // third party and ignored real profile photos anyway.
            const AV = ['#6E56CF', '#0ea5e9', '#059669', '#db2777', '#d97706', '#7c3aed', '#dc2626', '#0891b2'];
            const avColor = id => AV[Math.abs(parseInt(id, 10) || 0) % AV.length];
            const initials = name => String(name || '?').trim().slice(0, 2).toUpperCase();

            const fmtSize = b => {
                b = Number(b) || 0;
                if (b >= 1048576) return (b / 1048576).toFixed(1) + ' MB';
                if (b >= 1024) return Math.round(b / 1024) + ' KB';
                return b + ' B';
            };
            const parseDt = s => new Date(String(s || '').replace(' ', 'T'));
            const fmtTime = s => {
                const d = parseDt(s);
                return isNaN(d) ? '' : d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            };
            const dayKey = s => {
                const d = parseDt(s);
                if (isNaN(d)) return '';
                const t = new Date(), y = new Date(Date.now() - 86400000);
                if (d.toDateString() === t.toDateString()) return 'Today';
                if (d.toDateString() === y.toDateString()) return 'Yesterday';
                return d.toLocaleDateString([], { day: 'numeric', month: 'short', year: 'numeric' });
            };

            const ROOMS = [
                { key: 'world', label: 'World', icon: 'ph-globe-hemisphere-west' },
                { key: 'team', label: 'My Team', icon: 'ph-users-three' },
                { key: 'personal', label: 'Direct', icon: 'ph-chat-teardrop-text' }
            ];

            const Avatar = ({ id, name, sm }) =>
                E('div', {
                    className: 'cx-av' + (sm ? ' sm' : ''),
                    style: { background: avColor(id) }, title: name
                }, initials(name));

            /* ---------------- app ---------------- */
            function Chat() {
                const [room, setRoom] = useState('world');
                const [peer, setPeer] = useState(null);
                const [byRoom, setByRoom] = useState({ world: [], team: [], personal: [] });
                const [recent, setRecent] = useState([]);
                const [text, setText] = useState('');
                const [file, setFile] = useState(null);
                const [sending, setSending] = useState(false);
                const [loading, setLoading] = useState(true);
                const [error, setError] = useState('');
                const [online, setOnline] = useState(true);
                const [q, setQ] = useState('');
                const [light, setLight] = useState(null);
                const [showEmoji, setShowEmoji] = useState(false);

                const scrollRef = useRef(null);
                const fileRef = useRef(null);
                const taRef = useRef(null);
                const emojiRef = useRef(null);

                // Cursor + de-dupe are per THREAD, not global. The old version kept a
                // single id Set, reset it on room change but left the message array
                // intact, so World → Team → World re-appended the whole history and
                // every message appeared twice.
                const threadKey = useMemo(
                    () => room === 'personal' ? 'personal:' + (peer ? peer.id : 'none') : room,
                    [room, peer]
                );
                const cursors = useRef({});
                const seen = useRef({});

                const messages = (room === 'personal' && !peer) ? [] : (byRoom[room] || []);

                const atBottom = () => {
                    const el = scrollRef.current;
                    return !el || (el.scrollHeight - el.scrollTop - el.clientHeight < 90);
                };
                const toBottom = smooth => {
                    const el = scrollRef.current;
                    if (el) el.scrollTo({ top: el.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
                };

                /* ---- fetch ---- */
                const load = useCallback(async (reset) => {
                    if (room === 'personal' && !peer) { setLoading(false); return; }
                    const key = threadKey;
                    if (reset) { cursors.current[key] = 0; seen.current[key] = new Set(); }
                    const after = cursors.current[key] || 0;

                    const url = new URL(CHAT_FETCH_URL, window.location.origin);
                    url.searchParams.set('room', room);
                    url.searchParams.set('after', after);
                    if (room === 'personal' && peer) url.searchParams.set('peer_id', peer.id);

                    try {
                        const res = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                        if (res.status === 403) {
                            const j = await res.json().catch(() => ({}));
                            setOnline(true); setLoading(false);
                            setError(j.message || 'You cannot access this conversation.');
                            return;
                        }
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        const j = await res.json();
                        setOnline(true);
                        if (!j.ok) { setError(j.message || 'Could not load messages.'); setLoading(false); return; }
                        setError('');

                        const incoming = j.messages || [];
                        if (incoming.length) {
                            if (!seen.current[key]) seen.current[key] = new Set();
                            const set = seen.current[key];
                            const fresh = incoming.filter(m => !set.has(String(m.id)));
                            fresh.forEach(m => set.add(String(m.id)));
                            cursors.current[key] = incoming.reduce(
                                (a, m) => Math.max(a, Number(m.id) || 0), after);
                            if (fresh.length) {
                                const stick = atBottom();
                                setByRoom(prev => ({
                                    ...prev,
                                    [room]: reset ? fresh : [...(prev[room] || []), ...fresh]
                                }));
                                if (stick || reset) setTimeout(() => toBottom(!reset), 40);
                            }
                        } else if (reset) {
                            setByRoom(prev => ({ ...prev, [room]: [] }));
                        }
                    } catch (e) {
                        setOnline(false);
                    } finally {
                        setLoading(false);
                    }
                }, [room, peer, threadKey]);

                /* Reset the thread when room/peer changes — clears the array AND the
                   cursor together, which is what the old duplicate bug missed. */
                useEffect(() => {
                    setLoading(true);
                    setError('');
                    setByRoom(prev => ({ ...prev, [room]: [] }));
                    load(true);
                    // eslint-disable-next-line react-hooks/exhaustive-deps
                }, [room, peer && peer.id]);

                /* 2s poll, only while visible and nothing in flight */
                useEffect(() => {
                    let alive = true, t;
                    const tick = async () => {
                        if (!alive) return;
                        if (document.visibilityState === 'visible' && !sending) await load(false);
                        if (alive) t = setTimeout(tick, 2000);
                    };
                    t = setTimeout(tick, 2000);
                    return () => { alive = false; clearTimeout(t); };
                }, [load, sending]);

                /* Recent DM list — skipped while the tab is hidden (it used to poll
                   forever in background tabs). */
                useEffect(() => {
                    let alive = true;
                    const pull = async () => {
                        if (!alive || document.visibilityState !== 'visible') return;
                        try {
                            const res = await fetch(CHAT_RECENT_URL + '?limit=50',
                                { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                            const j = await res.json();
                            if (alive && j.ok) setRecent(j.items || []);
                        } catch (e) { /* transient — next tick retries */ }
                    };
                    pull();
                    const iv = setInterval(pull, 5000);
                    return () => { alive = false; clearInterval(iv); };
                }, []);

                /* Emoji picker */
                useEffect(() => {
                    if (!showEmoji || !emojiRef.current) return;
                    const el = emojiRef.current.querySelector('emoji-picker');
                    if (!el) return;
                    const on = ev => setText(t => t + ev.detail.unicode);
                    el.addEventListener('emoji-click', on);
                    return () => el.removeEventListener('emoji-click', on);
                }, [showEmoji]);

                useEffect(() => {
                    if (!showEmoji) return;
                    const close = e => {
                        if (emojiRef.current && !emojiRef.current.contains(e.target)) setShowEmoji(false);
                    };
                    document.addEventListener('mousedown', close);
                    return () => document.removeEventListener('mousedown', close);
                }, [showEmoji]);

                /* Auto-grow composer */
                useEffect(() => {
                    const ta = taRef.current;
                    if (!ta) return;
                    ta.style.height = 'auto';
                    ta.style.height = Math.min(120, ta.scrollHeight) + 'px';
                }, [text]);

                /* ---- send ---- */
                const send = async () => {
                    const body = text.trim();
                    if ((!body && !file) || sending) return;
                    if (room === 'personal' && !peer) { setError('Pick someone to message first.'); return; }

                    setSending(true); setError('');
                    const fd = new FormData();
                    fd.append('room', room);
                    fd.append('message', body);
                    if (room === 'personal' && peer) fd.append('peer_id', peer.id);
                    if (file) fd.append('chat_file', file);

                    try {
                        const res = await fetch(CHAT_SEND_URL, {
                            method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const j = await res.json().catch(() => ({ ok: false, message: 'Unexpected server response.' }));
                        if (!j.ok) { setError(j.message || 'Could not send.'); return; }
                        setText(''); setFile(null);
                        if (fileRef.current) fileRef.current.value = '';
                        await load(false);
                        toBottom(true);
                    } catch (e) {
                        setError('Network error — your message was not sent.');
                    } finally {
                        setSending(false);
                    }
                };

                const pickFile = e => {
                    const f = e.target.files[0];
                    if (!f) return;
                    if (f.size > 5 * 1024 * 1024) {
                        setError('Files must be 5 MB or smaller.');
                        e.target.value = '';
                        return;
                    }
                    setError(''); setFile(f);
                };

                /* ---- rail ---- */
                const railItems = useMemo(() => {
                    const term = q.trim().toLowerCase();
                    const convs = (recent || []).map(r => ({
                        id: Number(r.peer_id), username: r.peer_name,
                        last: r.last_message, when: r.last_message_time, type: r.last_message_type
                    }));
                    const have = new Set(convs.map(c => c.id));
                    // Everyone else in the path, so a first message is possible
                    // without an existing thread.
                    const rest = (CHAT_PEERS || [])
                        .filter(p => !have.has(Number(p.id)))
                        .map(p => ({ id: Number(p.id), username: p.username, last: '', when: '', type: 'text' }));
                    return [...convs, ...rest]
                        .filter(c => !term || String(c.username || '').toLowerCase().includes(term));
                }, [recent, q]);

                const Rail = () => E('div', { className: 'cx-rail' + (peer ? ' hide-sm' : '') },
                    E('div', { className: 'cx-rail-h' },
                        E('div', { className: 'cx-search' },
                            E('i', { className: 'ph ph-magnifying-glass' }),
                            E('input', {
                                type: 'search', placeholder: 'Search your team…',
                                value: q, onChange: e => setQ(e.target.value)
                            })
                        )
                    ),
                    E('div', { className: 'cx-rail-list' },
                        railItems.length === 0
                            ? E('div', {
                                style: { padding: '24px 12px', textAlign: 'center', fontSize: '11.5px', color: 'var(--cx-mut)' }
                            }, q ? 'Nobody matches that search.' : 'No one in your team path yet.')
                            : railItems.map(c => E('div', {
                                key: c.id,
                                className: 'cx-conv' + (peer && peer.id === c.id ? ' on' : ''),
                                onClick: () => setPeer({ id: c.id, username: c.username })
                            },
                                E(Avatar, { id: c.id, name: c.username }),
                                E('div', { className: 'cx-conv-b' },
                                    E('div', { className: 'cx-conv-n' }, c.username),
                                    E('div', { className: 'cx-conv-m' },
                                        c.type === 'image' ? '📷 Photo'
                                            : (c.type === 'file' ? '📎 File' : (c.last || 'Start a conversation')))
                                ),
                                c.when ? E('div', { className: 'cx-conv-t' }, fmtTime(c.when)) : null
                            ))
                    )
                );

                /* ---- bubbles ---- */
                const Bubble = m => {
                    const mine = String(m.user_id) === String(CURRENT_USER_ID);
                    const isPlaceholder = m.message === '📷 Image' || m.message === '📎 File';
                    return E('div', { className: 'cx-row' + (mine ? ' me' : ''), key: m.id },
                        E(Avatar, { id: m.user_id, name: m.username, sm: true }),
                        E('div', { className: 'cx-bub' },
                            (!mine && room !== 'personal') ? E('div', { className: 'cx-who' }, m.username) : null,
                            E('div', { className: 'cx-msg' },
                                (m.message_type === 'image' && m.file_url)
                                    ? E('img', {
                                        className: 'cx-img', src: m.file_url, alt: m.file_name || '',
                                        loading: 'lazy', onClick: () => setLight(m.file_url)
                                    })
                                    : ((m.message_type === 'file' && m.file_url)
                                        ? E('a', {
                                            className: 'cx-file', href: m.file_url,
                                            target: '_blank', rel: 'noopener noreferrer'
                                        },
                                            E('i', { className: 'ph-fill ph-file-arrow-down' }),
                                            E('span', null,
                                                E('b', null, m.file_name || 'Attachment'),
                                                E('small', null, fmtSize(m.file_size)))
                                        )
                                        : null),
                                // React escapes this text node. The server no longer
                                // pre-escapes, so "Tom & Jerry" renders correctly
                                // instead of "Tom &amp; Jerry".
                                (m.message && !isPlaceholder)
                                    ? E('div', { style: m.file_url ? { marginTop: '7px' } : null }, m.message)
                                    : null
                            ),
                            E('div', { className: 'cx-time' }, fmtTime(m.created_at))
                        )
                    );
                };

                const Thread = () => {
                    if (room === 'personal' && !peer) {
                        return E('div', { className: 'cx-empty' }, E('div', null,
                            E('i', { className: 'ph ph-chat-teardrop-text' }),
                            E('b', null, 'Pick someone to message'),
                            E('p', null, 'You can message anyone in your team path — your upline and your downline.')));
                    }
                    if (loading) {
                        return E('div', { className: 'cx-sk' }, [0, 1, 2, 3].map(i =>
                            E('div', { className: 'cx-sk-r' + (i % 2 ? ' me' : ''), key: i },
                                E('div', { className: 'cx-sk-a cx-shimmer' }),
                                E('div', {
                                    className: 'cx-sk-b cx-shimmer',
                                    style: { width: (140 + (i * 47) % 180) + 'px' }
                                }))));
                    }
                    if (!messages.length) {
                        return E('div', { className: 'cx-empty' }, E('div', null,
                            E('i', { className: 'ph ph-paper-plane-tilt' }),
                            E('b', null, 'No messages yet'),
                            E('p', null, room === 'world'
                                ? 'Say hello — everyone on the platform can see the World room.'
                                : (room === 'team'
                                    ? 'Start the conversation with your team.'
                                    : 'Send the first message to ' + (peer ? peer.username : 'this member') + '.'))));
                    }
                    const out = [];
                    let last = null;
                    messages.forEach(m => {
                        const k = dayKey(m.created_at);
                        if (k && k !== last) {
                            out.push(E('div', { className: 'cx-day', key: 'd' + m.id }, E('span', null, k)));
                            last = k;
                        }
                        out.push(Bubble(m));
                    });
                    return out;
                };

                const composerDisabled = room === 'personal' && !peer;

                return E(React.Fragment, null,
                    /* topbar */
                    E('div', { className: 'cx-top' },
                        E('div', { className: 'cx-tabs' },
                            ROOMS.map(r => E('button', {
                                key: r.key,
                                className: 'cx-tab' + (room === r.key ? ' on' : ''),
                                onClick: () => setRoom(r.key)
                            }, E('i', { className: 'ph ' + r.icon }), r.label))
                        ),
                        E('div', { className: 'cx-peer' },
                            (room === 'personal' && peer)
                                ? E(React.Fragment, null,
                                    E('button', {
                                        className: 'cx-ico', title: 'Back to conversations',
                                        style: { width: '30px', height: '30px', flex: '0 0 30px', fontSize: '14px' },
                                        onClick: () => setPeer(null)
                                    }, E('i', { className: 'ph ph-arrow-left' })),
                                    E(Avatar, { id: peer.id, name: peer.username, sm: true }),
                                    E('div', null,
                                        E('div', { className: 'cx-peer-name' }, peer.username),
                                        E('div', { className: 'cx-peer-sub' }, 'Direct message')))
                                : null,
                            E('span', { className: 'cx-live', title: online ? 'Connected' : 'Reconnecting…' },
                                E('span', { className: 'cx-dot' + (online ? '' : ' off') }),
                                online ? 'Live' : 'Offline')
                        )
                    ),

                    /* body */
                    E('div', { className: 'cx-body' },
                        room === 'personal' ? Rail() : null,
                        E('div', { className: 'cx-thread' + (room === 'personal' && !peer ? ' hide-sm' : '') },
                            error ? E('div', { className: 'cx-err' },
                                E('i', { className: 'ph-fill ph-warning-circle' }), error) : null,
                            E('div', { className: 'cx-scroll', ref: scrollRef }, Thread())
                        )
                    ),

                    /* composer */
                    E('div', { className: 'cx-foot' },
                        showEmoji ? E('div', { className: 'cx-emoji', ref: emojiRef }, E('emoji-picker', null)) : null,

                        file ? E('div', { className: 'cx-att' },
                            file.type.indexOf('image/') === 0
                                ? E('img', { src: URL.createObjectURL(file), alt: '' })
                                : E('i', { className: 'ph-fill ph-file', style: { fontSize: '26px', color: 'var(--cx-p)' } }),
                            E('div', null, E('b', null, file.name), E('small', null, fmtSize(file.size))),
                            E('button', {
                                title: 'Remove',
                                onClick: () => { setFile(null); if (fileRef.current) fileRef.current.value = ''; }
                            }, E('i', { className: 'ph ph-x' }))
                        ) : null,

                        E('div', { className: 'cx-compose' },
                            E('button', {
                                className: 'cx-ico', title: 'Emoji', disabled: composerDisabled,
                                onClick: () => setShowEmoji(s => !s)
                            }, E('i', { className: 'ph ph-smiley' })),
                            E('button', {
                                className: 'cx-ico', title: 'Attach a file', disabled: composerDisabled,
                                onClick: () => fileRef.current && fileRef.current.click()
                            }, E('i', { className: 'ph ph-paperclip' })),
                            E('input', {
                                type: 'file', ref: fileRef, style: { display: 'none' }, onChange: pickFile,
                                accept: '.jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip'
                            }),
                            E('textarea', {
                                className: 'cx-input', ref: taRef, rows: 1, maxLength: 500,
                                placeholder: room === 'personal'
                                    ? (peer ? 'Message ' + peer.username + '…' : 'Pick someone to message first')
                                    : (room === 'world' ? 'Message everyone…' : 'Message your team…'),
                                value: text,
                                disabled: composerDisabled,
                                onChange: e => setText(e.target.value),
                                // Enter sends, Shift+Enter newlines. The old composer
                                // was an <input>, so multi-line was impossible.
                                onKeyDown: e => {
                                    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
                                }
                            }),
                            E('button', {
                                className: 'cx-send', onClick: send, title: 'Send',
                                disabled: sending || composerDisabled || (!text.trim() && !file)
                            }, E('i', { className: sending ? 'ph ph-circle-notch' : 'ph-fill ph-paper-plane-right' }))
                        ),
                        E('div', { className: 'cx-hint' }, 'Enter to send · Shift + Enter for a new line')
                    ),

                    light ? E('div', { className: 'cx-light', onClick: () => setLight(null) },
                        E('button', { onClick: () => setLight(null) }, E('i', { className: 'ph ph-x' })),
                        E('img', { src: light, alt: '', onClick: e => e.stopPropagation() })
                    ) : null
                );
            }

            ReactDOM.createRoot(document.getElementById('chatApp')).render(E(Chat));
        })();
    </script>
</body>

</html>
