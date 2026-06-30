<?php
define('DB_HOST', "localhost");
define('DB_USERNAME', "root");
define('DB_PASS', "");
define('DB_NAME', "e-commerce-mlm-v2");
// define('BASE_URL', "http://localhost:9000");
define('BASE_URL', "http://192.168.29.7:9000");
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