<?php defined('BASEPATH') OR exit('No direct script access allowed');
$error404_default_theme = site_settings('landing', 'theme_mode') ?: 'dark';
if (!in_array($error404_default_theme, array('light', 'dark'), true)) {
    $error404_default_theme = 'dark';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= html_escape($error404_default_theme) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>404 &middot; Page Not Found</title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/images/favicon.png') ?>">
    <style>
        :root{
            --bg-0:#070b1a;
            --bg-1:#0d1430;
            --card:rgba(255,255,255,.04);
            --stroke:rgba(255,255,255,.09);
            --txt:#e8ecff;
            --muted:#9aa6d6;
            --c1:#4f8cff;   /* blue  */
            --c2:#8a5cff;   /* violet*/
            --c3:#28e0c8;   /* teal  */
        }
        html[data-theme="light"]{
            --bg-0:#f4f7fb;
            --bg-1:#ffffff;
            --card:rgba(255,255,255,.88);
            --stroke:rgba(12,22,44,.1);
            --txt:#10182f;
            --muted:#5f6b86;
            --c1:#2563eb;
            --c2:#7c3aed;
            --c3:#0891b2;
        }
        *{box-sizing:border-box;margin:0;padding:0}
        html,body{height:100%}
        body{
            font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
            background:radial-gradient(1200px 800px at 15% -10%,#16224d 0%,transparent 55%),
                       radial-gradient(1000px 700px at 110% 20%,#2a1650 0%,transparent 50%),
                       linear-gradient(160deg,var(--bg-1),var(--bg-0));
            color:var(--txt);
            min-height:100dvh;
            display:flex;align-items:center;justify-content:center;
            padding:24px;overflow:hidden;position:relative;
        }
        html[data-theme="light"] body{
            background:radial-gradient(1000px 680px at 15% -10%,rgba(37,99,235,.16) 0%,transparent 55%),
                       radial-gradient(900px 620px at 110% 20%,rgba(124,58,237,.13) 0%,transparent 50%),
                       linear-gradient(160deg,var(--bg-1),var(--bg-0));
        }
        /* floating gradient orbs */
        .orb{position:fixed;border-radius:50%;filter:blur(60px);opacity:.5;z-index:0;pointer-events:none;
             animation:float 14s ease-in-out infinite}
        .orb.a{width:340px;height:340px;background:var(--c1);top:-90px;left:-60px}
        .orb.b{width:300px;height:300px;background:var(--c2);bottom:-100px;right:-40px;animation-delay:-5s}
        .orb.c{width:220px;height:220px;background:var(--c3);top:55%;left:60%;opacity:.35;animation-delay:-9s}
        @keyframes float{0%,100%{transform:translate(0,0)}50%{transform:translate(24px,-28px)}}

        /* subtle grid overlay */
        body::before{content:"";position:fixed;inset:0;z-index:0;pointer-events:none;
            background-image:linear-gradient(rgba(255,255,255,.035) 1px,transparent 1px),
                            linear-gradient(90deg,rgba(255,255,255,.035) 1px,transparent 1px);
            background-size:46px 46px;
            mask-image:radial-gradient(circle at 50% 40%,#000 0%,transparent 72%);
            -webkit-mask-image:radial-gradient(circle at 50% 40%,#000 0%,transparent 72%);}

        .card{
            position:relative;z-index:1;width:100%;max-width:620px;text-align:center;
            padding:clamp(28px,5vw,52px) clamp(22px,5vw,48px);
            background:var(--card);border:1px solid var(--stroke);border-radius:26px;
            backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);
            box-shadow:0 30px 80px -30px rgba(0,0,0,.75),inset 0 1px 0 rgba(255,255,255,.06);
            animation:rise .7s cubic-bezier(.2,.7,.2,1) both;
        }
        @keyframes rise{from{opacity:0;transform:translateY(22px) scale(.98)}to{opacity:1;transform:none}}

        .logo{height:38px;width:auto;margin-bottom:26px;opacity:.95}
        .logo-dark{display:none}
        html[data-theme="light"] .logo-light{display:none}
        html[data-theme="light"] .logo-dark{display:inline-block}
        .badge{display:inline-flex;align-items:center;gap:8px;font-size:12px;letter-spacing:.14em;
            text-transform:uppercase;color:var(--muted);border:1px solid var(--stroke);
            padding:6px 14px;border-radius:999px;margin-bottom:22px}
        .badge .dot{width:7px;height:7px;border-radius:50%;background:var(--c3);
            box-shadow:0 0 0 0 rgba(40,224,200,.6);animation:pulse 1.8s infinite}
        @keyframes pulse{0%{box-shadow:0 0 0 0 rgba(40,224,200,.55)}70%{box-shadow:0 0 0 12px rgba(40,224,200,0)}100%{box-shadow:0 0 0 0 rgba(40,224,200,0)}}

        .code{
            font-size:clamp(96px,26vw,190px);line-height:.9;font-weight:800;letter-spacing:-.04em;
            background:linear-gradient(100deg,var(--c1),var(--c2) 45%,var(--c3));
            -webkit-background-clip:text;background-clip:text;color:transparent;
            background-size:200% 200%;animation:shine 6s ease infinite;
            filter:drop-shadow(0 14px 40px rgba(79,140,255,.28));
        }
        @keyframes shine{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}

        h1{font-size:clamp(20px,4.5vw,28px);font-weight:700;margin:10px 0 10px}
        p{color:var(--muted);font-size:clamp(14px,2.6vw,16px);line-height:1.6;max-width:44ch;margin:0 auto}

        .actions{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;margin-top:32px}
        .btn{display:inline-flex;align-items:center;gap:9px;font-size:15px;font-weight:600;
            text-decoration:none;padding:13px 26px;border-radius:14px;cursor:pointer;border:1px solid transparent;
            transition:transform .15s ease,box-shadow .25s ease,background .25s ease}
        .btn:active{transform:translateY(1px)}
        .btn-primary{color:#fff;background:linear-gradient(100deg,var(--c1),var(--c2));
            box-shadow:0 12px 30px -10px rgba(99,102,241,.7)}
        .btn-primary:hover{transform:translateY(-2px);box-shadow:0 18px 40px -12px rgba(99,102,241,.85)}
        .btn-ghost{color:var(--txt);background:rgba(255,255,255,.04);border-color:var(--stroke)}
        .btn-ghost:hover{background:rgba(255,255,255,.09);transform:translateY(-2px)}
        .btn svg{width:18px;height:18px}

        @media (max-width:420px){
            .actions{flex-direction:column}
            .btn{width:100%;justify-content:center}
        }
        @media (prefers-reduced-motion:reduce){
            .orb,.code,.badge .dot,.card{animation:none}
        }
    </style>
</head>
<body>
    <span class="orb a"></span>
    <span class="orb b"></span>
    <span class="orb c"></span>

    <main class="card" role="main">
        <img class="logo logo-light" src="<?= base_url('assets/images/logo-whites.png') ?>" alt="BMAN"
             onerror="this.style.display='none'">
        <img class="logo logo-dark" src="<?= base_url('assets/images/black_logo.png') ?>" alt="BMAN"
             onerror="this.style.display='none'">

        <div class="badge"><span class="dot"></span> Error 404</div>

        <div class="code">404</div>

        <h1>Oops! Page not found</h1>
        <p>The page you&rsquo;re looking for doesn&rsquo;t exist, was moved, or is temporarily unavailable. Let&rsquo;s get you back on track.</p>

        <div class="actions">
            <a class="btn btn-primary" href="<?= base_url() ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/></svg>
                Back to Home
            </a>
            <a class="btn btn-ghost" href="javascript:history.back()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                Go Back
            </a>
        </div>
    </main>
    <script>
    (function () {
        var saved = localStorage.getItem('site-theme');
        var theme = saved || document.documentElement.getAttribute('data-theme') || 'dark';
        if (theme === 'auto' || theme === 'system') {
            theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        document.documentElement.setAttribute('data-theme', theme);
    })();
    </script>
</body>
</html>
