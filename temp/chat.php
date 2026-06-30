<!doctype html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Chat (React CDN + PHP)</title>
    <style>
        body {
            margin: 0;
            font-family: system-ui, Arial;
            background: #f5f7fb;
        }

        .topbar {
            background: #2f3438;
            color: #fff;
            padding: 14px 16px;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .tab {
            padding: 8px 14px;
            border-radius: 10px;
            background: #3a4046;
            cursor: pointer;
            opacity: .8;
        }

        .tab.active {
            background: #6d4cff;
            opacity: 1;
        }

        .wrap {
            max-width: 1200px;
            margin: 0 auto;
        }

        .chatArea {
            padding: 18px;
            height: 70vh;
            overflow: auto;
        }

        .row {
            display: flex;
            gap: 10px;
            margin: 14px 0;
            align-items: flex-start;
        }

        .avatar {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: #ddd;
            flex: 0 0 auto;
        }

        .bubble {
            background: #fff;
            border-radius: 16px;
            padding: 12px 14px;
            max-width: 680px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .06);
            border: 2px solid #ffb10033;
        }

        .meta {
            font-size: 12px;
            opacity: .6;
            margin-bottom: 6px;
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .name {
            font-weight: 700;
            opacity: .9;
        }

        .time {
            margin-left: auto;
        }

        .me {
            justify-content: flex-end;
        }

        .me .bubble {
            border-color: #6d4cff33;
        }

        .me .avatar {
            display: none;
        }

        .composer {
            padding: 14px 16px;
            display: flex;
            gap: 10px;
            align-items: center;
            background: #fff;
            border-top: 1px solid #eee;
        }

        .input {
            flex: 1;
            border: 1px solid #e6e6e6;
            border-radius: 14px;
            padding: 12px 14px;
            outline: none;
        }

        .btn {
            background: #6d4cff;
            border: none;
            color: #fff;
            padding: 12px 16px;
            border-radius: 14px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div id="app"></div>

    <!-- React CDN -->
    <script src="https://unpkg.com/react@18/umd/react.development.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>

    <script>
        const API_BASE = "./"; // put send_message.php & fetch_messages.php in same folder

        // DEMO user (replace with your PHP session user)
        const CURRENT_USER = {
            user_id: 101,
            username: "Fenizo"
        };

        function formatTime(ts) {
            try { return new Date(ts).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); }
            catch { return ""; }
        }

        function App() {
            const [room, setRoom] = React.useState("personal");
            const [messages, setMessages] = React.useState([]);
            const [text, setText] = React.useState("");
            const [lastId, setLastId] = React.useState(0);
            const listRef = React.useRef(null);

            const scrollBottom = () => {
                const el = listRef.current;
                if (el) el.scrollTop = el.scrollHeight;
            };

            const fetchNew = React.useCallback(async () => {
                const url = API_BASE + "fetch_messages.php?room=" + encodeURIComponent(room) + "&after=" + lastId;
                const res = await fetch(url);
                const data = await res.json();
                if (data.ok && data.messages && data.messages.length) {
                    setMessages(prev => [...prev, ...data.messages]);
                    setLastId(data.messages[data.messages.length - 1].id);
                    setTimeout(scrollBottom, 0);
                }
            }, [room, lastId]);

            // reset when room changes
            React.useEffect(() => {
                setMessages([]);
                setLastId(0);
            }, [room]);

            // polling
            React.useEffect(() => {
                let stopped = false;
                const tick = async () => {
                    if (stopped) return;
                    try { await fetchNew(); } catch (e) { }
                    setTimeout(tick, 1000);
                };
                tick();
                return () => { stopped = true; };
            }, [fetchNew]);

            const send = async () => {
                const msg = text.trim();
                if (!msg) return;

                const fd = new FormData();
                fd.append("room", room);
                fd.append("user_id", CURRENT_USER.user_id);
                fd.append("username", CURRENT_USER.username);
                fd.append("message", msg);

                setText("");

                const res = await fetch(API_BASE + "send_message.php", { method: "POST", body: fd });
                const data = await res.json();
                if (!data.ok) {
                    alert(data.message || "Send failed");
                    return;
                }
                // fetch immediately after send
                await fetchNew();
            };

            return React.createElement("div", null,
                React.createElement("div", { className: "topbar" },
                    ["world", "alliance", "personal"].map(r =>
                        React.createElement("div", {
                            key: r,
                            className: "tab" + (room === r ? " active" : ""),
                            onClick: () => setRoom(r)
                        }, r.charAt(0).toUpperCase() + r.slice(1))
                    )
                ),
                React.createElement("div", { className: "wrap" },
                    React.createElement("div", { className: "chatArea", ref: listRef },
                        messages.map(m =>
                            React.createElement("div", {
                                key: m.id,
                                className: "row" + (m.user_id === CURRENT_USER.user_id ? " me" : "")
                            },
                                React.createElement("div", { className: "avatar" }),
                                React.createElement("div", { className: "bubble" },
                                    React.createElement("div", { className: "meta" },
                                        React.createElement("div", { className: "name" }, m.username),
                                        React.createElement("div", { className: "time" }, formatTime(m.created_at))
                                    ),
                                    React.createElement("div", null, m.message)
                                )
                            )
                        )
                    ),
                    React.createElement("div", { className: "composer" },
                        React.createElement("input", {
                            className: "input",
                            value: text,
                            placeholder: "Type your message...",
                            onChange: e => setText(e.target.value),
                            onKeyDown: e => { if (e.key === "Enter") send(); }
                        }),
                        React.createElement("button", { className: "btn", onClick: send }, "Send")
                    )
                )
            );
        }

        ReactDOM.createRoot(document.getElementById("app")).render(React.createElement(App));
    </script>
</body>

</html>