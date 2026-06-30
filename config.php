<?php
define('DB_HOST', "localhost");
define('DB_USERNAME', "root");
define('DB_PASS', "");
define('DB_NAME', "e-commerce-mlm-v2");
// ---------------------------------------------------------------------------
//  BASE_URL — auto-detects the public URL so the app works on ANY tunnel
//  (devtunnels / ngrok / cloudflare) without editing this file each demo.
//  CLI / cron has no request host, so it uses the fixed fallback below.
// ---------------------------------------------------------------------------
define('BASE_URL_FALLBACK', "http://192.168.29.7:9000");   // used for CLI/cron + if host is unknown

if (php_sapi_name() === 'cli' || empty($_SERVER['HTTP_HOST'])) {
    define('BASE_URL', BASE_URL_FALLBACK);
} else {
    // scheme: trust the proxy's X-Forwarded-Proto first (tunnels terminate TLS)
    $__scheme = !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
        ? strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]))
        : ((!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ? 'https' : 'http');

    // host: prefer the forwarded host the visitor actually typed
    $__host = !empty($_SERVER['HTTP_X_FORWARDED_HOST'])
        ? trim(explode(',', $_SERVER['HTTP_X_FORWARDED_HOST'])[0])
        : $_SERVER['HTTP_HOST'];

    // basic allow-list of characters to avoid header injection
    if (!preg_match('/^[A-Za-z0-9.\-:]+$/', $__host)) {
        define('BASE_URL', BASE_URL_FALLBACK);
    } else {
        define('BASE_URL', $__scheme . '://' . $__host);
    }
}
// Old fixed values (kept for reference):
// define('BASE_URL', "http://localhost:9000");
// define('BASE_URL', "http://192.168.29.7:9000");
// define('BASE_URL', "https://qvft8ng3-9000.inc1.devtunnels.ms");


define('DEFAULTAVATARIMAGE', BASE_URL . "/assets/default-user.png");
define("DEFAULT_PROFILE", DEFAULTAVATARIMAGE);

define("DEMO_POP_ON", false);
define('DEMOVERSION', false);
define("ENABLE_SITE_UPLOAD_FUNCTION", true); //default true is allow the imaage/any upload

// Landing "Get Early Access" form -------------------------------------------
define("LANDING_EARLY_ACCESS_ENABLED", true);          // false = turn the endpoint off
define("LANDING_EARLY_ACCESS_ALLOW_ANY_ORIGIN", true); // true = accept the POST from any site (CORS *)

?>