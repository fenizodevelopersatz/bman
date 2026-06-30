<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/

$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;
// Home / index page now renders the dynamic landing page.
// The previous shop home is preserved at /welcome and /shop-home (backup).
$route['default_controller'] = 'Landing';


/****************** ADMIN ROUTES ********/
$route['admin'] = 'admin/Administrator';
$route['admin/login'] = 'admin/Login';

/********** admin login verify */
$route['login-otp-verify'] = 'admin/Login/verifyotp';
$route['login-finel-verify'] = 'admin/Login/finelVerify';

$route['balance-info-admin'] = 'admin/Administrator/balance_info';

/****************** ADMIN ROUTES ********/
$route['logout'] = 'admin/settings/Sitesettings/logout';

/****************** Transaction ROUTES ********/
$route['all-transaction'] = 'admin/settings/Sitesettings/transaction';
$route['transaction-list'] = 'admin/settings/Sitesettings/transactionlist';
$route['all-transaction-get'] = 'admin/settings/Sitesettings/transactionlistAmount';


/****************** ADMIN SITE SETTINS ********/
$route['site-settings'] = 'admin/settings/Sitesettings';
$route['site-settings-image'] = 'admin/settings/Sitesettings/update';
$route['site-settings-contact'] = 'admin/settings/Sitesettings/update_contact_settings';
$route['site-settings-config'] = 'admin/settings/Sitesettings/update_config_settings';
$route['site-settings-meta'] = 'admin/settings/Sitesettings/update_meta_settings';


/****************** ADMIN PAYMENT SETTINS ********/
$route['payment-settings'] = 'admin/settings/Paymentsettings';
$route['payment-list'] = 'admin/settings/Paymentsettings/list';
$route['payment-email-verify'] = 'admin/settings/Paymentsettings/verifyotp';
$route['payment-edit/(:num)'] = 'admin/settings/Paymentsettings/edit/$1';
$route['payment-verify'] = 'admin/settings/Paymentsettings/finelVerify';


/****************** MAIL SETTINS ********/
$route['member-theme'] = 'admin/settings/Membertheme';
$route['member-theme-update'] = 'admin/settings/Membertheme/update';
$route['member-theme-reset'] = 'admin/settings/Membertheme/reset_default';
$route['mail-settings'] = 'admin/settings/Mailsettings';
$route['mail-settings-update'] = 'admin/settings/Mailsettings/update';


/****************** WITHDRAW  SETTINS ********/
$route['withdraw-settings'] = 'admin/settings/Withdrawsettings';
$route['withdraw-settings-update'] = 'admin/settings/Withdrawsettings/update';
$route['token-withdraw-settings'] = 'admin/settings/Withdrawsettings/token_settings';
$route['update-token-withdraw-settings'] = 'admin/settings/Withdrawsettings/update_token_settings';

/****************** Transfer SETTINS ********/
$route['transfer-settings'] = 'admin/settings/Transfersettings';
$route['transfer-settings-update'] = 'admin/settings/Transfersettings/update';

/****************** Transfer SETTINS ********/
$route['swap-settings'] = 'admin/settings/Transfersettings/swap';
$route['swap-settings-update'] = 'admin/settings/Transfersettings/swap_update';

/****************** Advance SETTINS ********/
$route['advance-settings'] = 'admin/settings/Advancesettings';

/****************** CURRENCY SETTINS ********/
$route['currency-list'] = 'admin/settings/Advancesettings/currency_list';
$route['currency-edit/(:num)'] = 'admin/settings/Advancesettings/edit/$1';
$route['currency-update'] = 'admin/settings/Advancesettings/update';
$route['currency-delete/(:num)'] = 'admin/settings/Advancesettings/delete/$1';
$route['currency-status/(:num)'] = 'admin/settings/Advancesettings/status/$1';
$route['currency-add'] = 'admin/settings/Advancesettings/add';

/****************** Token SETTINS ********/
$route['token-settings'] = 'admin/settings/Advancesettings/token';
$route['token-list'] = 'admin/settings/Advancesettings/token_list';
$route['token-edit/(:num)'] = 'admin/settings/Advancesettings/token_edit/$1';
$route['token-update'] = 'admin/settings/Advancesettings/token_update/$1';
$route['token-add'] = 'admin/settings/Advancesettings/token_add';
$route['token-delete/(:num)'] = 'admin/settings/Advancesettings/token_delete/$1';
$route['token-status/(:num)'] = 'admin/settings/Advancesettings/token_status/$1';


/****************** USER SETTINS ********/
$route['user-settings'] = 'admin/settings/Advancesettings/user_settings_update';
$route['user-settings-update'] = 'admin/settings/Advancesettings/user_settings_update';

/****************** CAPTCHA SETTINS ********/
$route['captcha-settings'] = 'admin/settings/Advancesettings/captcha_settings_update';
$route['captcha-settings-update'] = 'admin/settings/Advancesettings/captcha_settings_update';


/****************** IP BLocker ********/
$route['ip-block'] = 'admin/settings/Advancesettings/ip_block';
$route['ip-blocker-list'] = 'admin/settings/Advancesettings/ipblock_list';
$route['ip-block-add'] = 'admin/settings/Advancesettings/ipblock_add';
$route['ip_block-update'] = 'admin/settings/Advancesettings/ipblock_add';
$route['delete-ip/(:num)'] = 'admin/settings/Advancesettings/delete_ip/$1';


/**************** Pageckage Settings */
$route['package-settings'] = 'admin/settings/Packagesettings';
$route['package-list'] = 'admin/settings/Packagesettings/list';
$route['package-add'] = 'admin/settings/Packagesettings/add';
$route['package-update'] = 'admin/settings/Packagesettings/add';
$route['edit-package/(:any)'] = 'admin/settings/Packagesettings/edit/$1';
$route['delete-package/(:any)'] = 'admin/settings/Packagesettings/delete/$1';
$route['package-status/(:any)'] = 'admin/settings/Packagesettings/status/$1';
//****************** SHOP ADMIN */

//***** PRODUCTS *************/
$route['admin/product-list'] = 'admin/shop/Adminproduct';
$route['admin/product-all-list'] = 'admin/shop/Adminproduct/product_list';
$route['admin/add-product'] = 'admin/shop/Adminproduct/add';
$route['admin/add-brand'] = 'admin/shop/Adminproduct/add_brand';
$route['admin/add-category'] = 'admin/shop/Adminproduct/add_category';
$route['admin/create-product'] = 'admin/shop/Adminproduct/save_product';
$route['admin-product/status-toggle/(:any)'] = 'admin/shop/Adminproduct/product_status_update/$1';
$route['admin/product-edit/(:any)'] = 'admin/shop/Adminproduct/edit/$1';
$route['admin/product-delete/(:any)'] = 'admin/shop/Adminproduct/product_delete/$1';

//***** CATEOGRYS *************/
$route['admin/category-list'] = 'admin/shop/Adminproduct/category_view';
$route['admin/category-all-list'] = 'admin/shop/Adminproduct/category_list_view';
$route['admin/category-toggle/(:any)'] = 'admin/shop/Adminproduct/category_status_update/$1';
$route['admin/category-edit/(:any)'] = 'admin/shop/Adminproduct/category_edit/$1';
$route['admin/category-delete/(:any)'] = 'admin/shop/Adminproduct/category_delete/$1';
$route['admin/create-category'] = 'admin/shop/Adminproduct/create_category';


//***** BRANDS *************/
$route['admin/brand-list'] = 'admin/shop/Adminproduct/brand_view';
$route['admin/brand-all-list'] = 'admin/shop/Adminproduct/brand_list_view';
$route['admin/brand-toggle/(:any)'] = 'admin/shop/Adminproduct/brand_status_update/$1';
$route['admin/brand-edit/(:any)'] = 'admin/shop/Adminproduct/brand_edit/$1';
$route['admin/brand-delete/(:any)'] = 'admin/shop/Adminproduct/brand_delete/$1';
$route['admin/save-brand'] = 'admin/shop/Adminproduct/save_brand';

/*********** COUPEN **********/
$route['admin/coupen-list'] = 'admin/shop/Coupon';
$route['admin/coupen_list_view'] = 'admin/shop/Coupon/coupen_list_view';
$route['admin/add-coupen'] = 'admin/shop/Coupon/add_coupen';
$route['admin/save_coupen'] = 'admin/shop/Coupon/save_coupon';
$route['admin/coupon-edit/(:num)'] = 'admin/shop/Coupon/edit_coupen/$1';
$route['admin/coupon-status-toggle/(:any)'] = 'admin/shop/Coupon/coupen_status_update/$1';
$route['admin/coupen-delete/(:any)'] = 'admin/shop/Coupon/coupen_delete/$1';

/************ BLOG */
$route['admin/blog-list'] = 'admin/cms/Blog';
$route['admin/blog-all-list'] = 'admin/cms/Blog/blog_list_view';
$route['admin/add-blog'] = 'admin/cms/Blog/add_blog';
$route['admin/save_blog'] = 'admin/cms/Blog/save_blog';
$route['admin/blog-edit/(:num)'] = 'admin/cms/Blog/edit_blog/$1';
$route['admin/blog-status-toggle/(:any)'] = 'admin/cms/Blog/blog_status_update/$1';
$route['admin/blog-delete/(:any)'] = 'admin/cms/Blog/blog_delete/$1';


/************ Blog Category */
$route['admin/blog-category-list'] = 'admin/cms/Blog/category';
$route['admin/blog-category-all-list'] = 'admin/cms/Blog/blog_category_list_view';
$route['admin/add-blog-category'] = 'admin/cms/Blog/add_blog_category';
$route['admin/save_blog-category'] = 'admin/cms/Blog/save_blog_category';
$route['admin/blog-category-edit/(:num)'] = 'admin/cms/Blog/edit_blog_category/$1';
$route['admin/blog-category-status-toggle/(:any)'] = 'admin/cms/Blog/blog_category_status_update/$1';
$route['admin/blog-category-delete/(:any)'] = 'admin/cms/Blog/blog_category_delete/$1';


/************** SHIPPING */
$route['admin/shipping-list'] = 'admin/shop/Shipping';
$route['admin/shipping_zone_list_view'] = 'admin/shop/Shipping/shipping_zone_list_view';
$route['admin/add-shipping'] = 'admin/shop/Shipping/add_shipping';
$route['admin/save_shipping'] = 'admin/shop/Shipping/save_shipping';
$route['admin/shipping-edit/(:num)'] = 'admin/shop/Shipping/edit_shipping/$1';
$route['admin/shipping-status-toggle/(:any)'] = 'admin/shop/Shipping/shipping_status_update/$1';
$route['admin/shipping-delete/(:any)'] = 'admin/shop/Shipping/shipping_delete/$1';
/******************* MARKETTING ****************** */

/************** EMAIL Markettings */
$route['email-marketting'] = 'admin/markettings/Emailmarkettings';
$route['email-template-list'] = 'admin/markettings/Emailmarkettings/list';
$route['view-template/(:num)'] = 'admin/markettings/Emailmarkettings/view_template/$1';
$route['edit-template/(:num)'] = 'admin/markettings/Emailmarkettings/edit_template/$1';
$route['email-template-update'] = 'admin/markettings/Emailmarkettings/template_update';
$route['template-status-update/(:num)'] = 'admin/markettings/Emailmarkettings/template_status_update/$1';

/************** NEWS Letter Markettings */
$route['newsletter-marketting'] = 'admin/markettings/Newsletter';
$route['news-letter-send'] = 'admin/markettings/Newsletter/send';

/************** Social Link Markettings */
$route['social-link-marketting'] = 'admin/markettings/Sociallink';
$route['social-link-update-marketting'] = 'admin/markettings/Sociallink/update';

/************** Website Content ContentManagement */
$route['website-content-cms'] = 'admin/cms/Websitecontent';
$route['websitecontent-list-cms'] = 'admin/cms/Websitecontent/list';
$route['view-websitecontent-section-cms/(:num)'] = 'admin/cms/Websitecontent/view_section/$1';
$route['edit-websitecontent-cms/(:num)'] = 'admin/cms/Websitecontent/edit_section/$1';
$route['website-content-update'] = 'admin/cms/Websitecontent/update_section';
$route['websitecontent-status-update-cms/(:num)'] = 'admin/cms/Websitecontent/status_update/$1';

/************** Announcement ContentManagement */
$route['announcement-cms'] = 'admin/cms/Announcement';
$route['announcement-list-cms'] = 'admin/cms/Announcement/list';
$route['announcement-status-update-cms/(:num)'] = 'admin/cms/Announcement/status_update/$1';
$route['edit-announcement-cms/(:num)'] = 'admin/cms/Announcement/edit_section/$1';
$route['announcement-add'] = 'admin/cms/Announcement/add';
$route['view-announceemnt-section-cms/(:num)'] = 'admin/cms/Announcement/view_section/$1';
$route['delete-announcement-cms/(:num)'] = 'admin/cms/Announcement/delete_section/$1';

/************** Slider ContentManagement */
$route['slider-cms'] = 'admin/cms/Slider';
$route['slider-add'] = 'admin/cms/Slider/add';
$route['slider-list-cms'] = 'admin/cms/Slider/list';
$route['edit-slider-cms/(:num)'] = 'admin/cms/Slider/edit_slider/$1';
$route['delete-slider-cms/(:num)'] = 'admin/cms/Slider/delete_slider/$1';
$route['slider-status-update-cms/(:num)'] = 'admin/cms/Slider/status_update/$1';

/************** Slider ContentManagement */
$route['faq-cms'] = 'admin/cms/Faq';
$route['faq-list-cms'] = 'admin/cms/Faq/list';
$route['faq-add'] = 'admin/cms/Faq/add';
$route['edit-faq-cms/(:num)'] = 'admin/cms/Faq/edit_faq/$1';
$route['delete-faq-cms/(:num)'] = 'admin/cms/Faq/delete_faq/$1';
$route['faq-status-update-cms/(:num)'] = 'admin/cms/Faq/status_update/$1';


/******************* WALLET ****************** */

/************ add wallet */
$route['add-wallet'] = 'admin/wallet/Walletmanagement';
$route['add-wallet-post'] = 'admin/wallet/Walletmanagement/wallet_add_post';

/************ user wallet balance */
$route['user-currency-wallet/(:num)'] = 'admin/wallet/Walletmanagement/user_currency_balance/$1';
$route['user-token-wallet/(:num)'] = 'admin/wallet/Walletmanagement/user_token_balance/$1';
$route['user-wallet-balance/(:num)'] = 'admin/wallet/Walletmanagement/user_wallet_balance/$1';

/*********** Detact Wallet */
$route['detect-wallet'] = 'admin/wallet/Walletmanagement/detact_wallet';
$route['detact-wallet-post'] = 'admin/wallet/Walletmanagement/wallet_detact_post';

/************* make investment */
$route['make-investment'] = 'admin/wallet/Walletmanagement/makeinvestment';
$route['validate-package-amount'] = 'admin/wallet/Walletmanagement/validate_package_amount';
$route['make-investment-post'] = 'admin/wallet/Walletmanagement/makeinvestment_post';

/************* USER TRNASFER */
$route['internel-transfer'] = 'admin/wallet/Walletmanagement/internel_transfer';
$route['validate-transfer-balance'] = 'admin/wallet/Walletmanagement/validate_transfer_balance';
$route['internel-transfer-post'] = 'admin/wallet/Walletmanagement/internel_transfer_post';

$route['internel-swap'] = 'admin/wallet/Walletmanagement/internel_swap';
$route['validate-swap-balance'] = 'admin/wallet/Walletmanagement/validate_swap_balance';
$route['make-swap-post'] = 'admin/wallet/Walletmanagement/internel_swap_post';

/************* VERIFY Investment */
$route['verification-investment'] = 'admin/wallet/Walletmanagement/verify_investment';
$route['verify-investment-list'] = 'admin/wallet/Walletmanagement/verify_investment_list';
$route['approve-investment/(:num)'] = 'admin/wallet/Walletmanagement/approve_investment/$1';
$route['reject-investment/(:num)'] = 'admin/wallet/Walletmanagement/reject_investment/$1';

/****************** SUPPORT ********/
$route['support'] = 'admin/support/Supportmanagement';
$route['support-list'] = 'admin/support/Supportmanagement/list';
$route['edit-ticket/(:num)'] = 'admin/support/Supportmanagement/edit/$1';
$route['update-ticket'] = 'admin/support/Supportmanagement/update';
$route['update-support-status/(:any)'] = 'admin/support/Supportmanagement/update_status/$1';

/****************** MEMBER SETTINS ********/
$route['network-member'] = 'admin/member/Membermanagement';
$route['network-list'] = 'admin/member/Membermanagement/list';
$route['add-user'] = 'admin/member/Membermanagement/add_user';
$route['create-user'] = 'admin/member/Membermanagement/create_user';
$route['user-genealogy/(:num)'] = 'admin/member/Membermanagement/genealogy/$1';
$route['tree-data/(:num)'] = 'admin/member/Membermanagement/getTreeData/$1';
$route['view-user/(:num)'] = 'admin/member/Membermanagement/viewuser/$1';
$route['view-user-info/(:num)'] = 'admin/member/Membermanagement/viewuserinfo/$1';
$route['user-status-update/(:num)'] = 'admin/member/Membermanagement/statusupdate/$1';
$route['user-delete/(:num)'] = 'admin/member/Membermanagement/deleteuser/$1';


/*************** COMMISSION SETTINGS ****************/
$route['commission-settings'] = 'admin/settings/Commissionsettings';
$route['update-commission-settings'] = 'admin/settings/Commissionsettings/update';

/*************** CRON ****************/
// $route['earn-cron-made-roi'] = 'Cron/run_roi';
// $route['rank-cron-made'] = 'Cron/update_all_users_rank';
// $route['rank-cron-made'] = 'Cron/run_monthly_rank_commission';

// $route['cron-rank-made'] = 'myrank/run_monthly_rank_commission';
// $route['binary-cron-made'] = 'Cron/binary_commission_call';
// $route['binary-cron-made'] = 'DailyCommission/binary_commission_call';


/*************** CRON ****************/
$route['earn-cron-made'] = 'Cron/run_roi';
$route['rank-cron-made'] = 'Cron/update_all_users_rank';
$route['binary-cron-made'] = 'Cron/binary_commission_call';




/*************** Rank Settings ****************/
$route['rank-settings'] = 'admin/rank/Rankmanagment';
$route['rank-add'] = 'admin/rank/Rankmanagment/token_add';
$route['rank-list'] = 'admin/rank/Rankmanagment/list';
$route['rank-update'] = 'admin/rank/Rankmanagment/rank_update';
$route['rank-status/(:num)'] = 'admin/rank/Rankmanagment/rank_status/$1';
$route['rank-edit/(:num)'] = 'admin/rank/Rankmanagment/rank_edit/$1';
$route['rank-delete/(:num)'] = 'admin/rank/Rankmanagment/rank_delete/$1';


$route['check-wallet/(:any)'] = 'admin/member/Membermanagement/decript_wallet_user/$1';

/******************* INVESTMENT  */
$route['list-investment'] = 'admin/wallet/Walletmanagement/investmentlist';
$route['get-list-investment'] = 'admin/wallet/Walletmanagement/investment_list_get';
$route['package-reinvest-status/(:num)'] = 'admin/wallet/Walletmanagement/package_reinvest_status/$1';
$route['delete-investment/(:num)'] = 'admin/wallet/Walletmanagement/investment_delete/$1';
$route['all-investment-get'] = 'admin/wallet/Walletmanagement/investment_amount_fetch';
$route['investment-info/(:num)'] = 'admin/wallet/Walletmanagement/investment_info/$1';
$route['transaction-list-profit'] = 'admin/wallet/Walletmanagement/list_profit';
$route['list-profit-amount'] = 'admin/wallet/Walletmanagement/profit_amount_fetch';

$route['api/image-generate'] = 'api/Api/image_generate';
$route['api/image-save'] = 'api/Api/image_save';


/************* PAGE LINK SETTINGS */
$route['pagelink-settings'] = 'admin/settings/Pagelinksettings';
$route['update-pagelink-settings'] = 'admin/settings/Pagelinksettings/update';

/************* Commission soon */
$route['soon/(:num)'] = 'welcome/commingsoon/$1';


// ******************** EARNING ADS ********************
// $route['admin/earning-ads'] = 'admin/earning_ads/Earnings_ads';
// $route['admin/earning-ads/list'] = 'admin/earning_ads/Earnings_ads/list';
// $route['admin/earning-ads/add'] = 'admin/earning_ads/Earnings_ads/add';
// $route['admin/earning-ads/edit/(:num)'] = 'admin/earning_ads/Earnings_ads/edit/$1';
// $route['admin/earning-ads/save'] = 'admin/earning_ads/Earnings_ads/save';
// $route['admin/earning-ads/status/(:num)'] = 'admin/earning_ads/Earnings_ads/status_update/$1';
// $route['admin/earning-ads/delete/(:num)'] = 'admin/earning_ads/Earnings_ads/delete/$1';


$route['admin/earning-ads'] = 'admin/earning/Earnings_ads/index';
$route['admin/earning-ads/list'] = 'admin/earning/Earnings_ads/list';

$route['admin/earning-ads/add'] = 'admin/earning/Earnings_ads/add';           // GET add page, POST insert
$route['admin/earning-ads/edit/(:num)'] = 'admin/earning/Earnings_ads/edit/$1';       // GET edit page

$route['admin/earning-ads/save'] = 'admin/earning/Earnings_ads/save';          // POST update/insert via ajax
$route['admin/earning-ads/status/(:num)'] = 'admin/earning/Earnings_ads/status_update/$1'; // POST toggle
$route['admin/earning-ads/delete/(:num)'] = 'admin/earning/Earnings_ads/delete/$1';     // POST delete



// ===============================
// Earning Videos (Premium) Admin
// ===============================
$route['admin/earning-videos'] = 'admin/earning/Earning_videos/index';
$route['admin/earning-videos/list'] = 'admin/earning/Earning_videos/list';
$route['admin/earning-videos/add'] = 'admin/earning/Earning_videos/add';     // add page + edit via ?id=ID
$route['admin/earning-videos/save'] = 'admin/earning/Earning_videos/save';    // insert/update (AJAX)
$route['admin/earning-videos/status/(:num)'] = 'admin/earning/Earning_videos/status/$1';
$route['admin/earning-videos/delete/(:num)'] = 'admin/earning/Earning_videos/delete/$1';


//*************Reward Settings (Earning Methods)**************** */ 
$route['admin/earning-methods'] = 'admin/earning/Earning_methods/index';
$route['admin/earning-methods/list'] = 'admin/earning/Earning_methods/list';
$route['admin/earning-methods/show/(:num)'] = 'admin/earning/Earning_methods/show/$1';
$route['admin/earning-methods/save/(:num)'] = 'admin/earning/Earning_methods/save/$1';
$route['admin/earning-methods/status/(:num)'] = 'admin/earning/Earning_methods/status/$1';


/******************* PROFILE  */
$route['profile-settings'] = 'admin/settings/Profilesettings';
$route['api/image-generate'] = 'api/Api/image_generate';
$route['update-profile-settings'] = 'admin/settings/Profilesettings/update';


/******************* PASSWORD  */
$route['changepassword-settings'] = 'admin/settings/Passwordsettings';
$route['update-changepassword-settings'] = 'admin/settings/Passwordsettings/update';


/********************* USER AUTH LOGIN  */
$route['user/in'] = 'user/auth/login';
$route['user/re'] = 'user/auth/register';
$route['user/auth/success'] = 'user/auth/login/success';
$route['user/login-otp-verify'] = 'user/auth/login/verifyotp';
$route['user/login-finel-verify'] = 'user/auth/login/finelVerify';
$route['user/forgot'] = 'user/auth/login/forgot';
$route['user/logout'] = 'user/user/logout';

$route['user/tranfer'] = 'user/usersettings/tranfercontroller';
$route['user/swap'] = 'user/usersettings/tranfercontroller/internel_swap';
$route['user/lending'] = 'user/usersettings/lendingcontroller';
$route['user/investments/details_ajax'] = 'user/usersettings/lendingcontroller/details_ajax';

$route['user/genealogy'] = 'user/usersettings/genealogycontroller';
$route['user/binary_tree'] = 'user/usersettings/genealogycontroller';

$route['user/rank-reward'] = 'user/usersettings/Rank_rewards';
$route['user/withdraw'] = 'user/usersettings/genealogycontroller/withdraw';
$route['user/wallet-transfer'] = 'user/usersettings/genealogycontroller/wallet_transfer';
$route['user/all-rank'] = 'user/usersettings/genealogycontroller/all_rank';

// CHATTING SYS
$route['user/chat'] = 'user/usersettings/genealogycontroller/chat';
$route['user/chat/send'] = 'user/usersettings/genealogycontroller/chat_send';
$route['user/chat/fetch'] = 'user/usersettings/genealogycontroller/chat_fetch';
$route['user/chat/recent'] = 'user/usersettings/genealogycontroller/chat_recent';


$route['user/earn_more'] = 'user/usersettings/Earnings';
$route['user/earnings'] = 'user/usersettings/Earnings';
$route['user/earnings/method/(:any)'] = 'user/usersettings/earnings/do_method/$1';
$route['user/earnings/task/claim/(:any)'] = 'user/usersettings/earnings/claim_task/$1';
$route['user/earnings/task/verify/(:any)'] = 'user/usersettings/earnings/verify_task/$1';


$route['user/earnings/videos'] = 'user/usersettings/earnings_videos/index';
$route['user/earnings/videos/watch/(:num)'] = 'user/usersettings/earnings_videos/watch/$1';
$route['user/earnings/videos/start/(:num)'] = 'user/usersettings/earnings_videos/start/$1';
$route['user/earnings/videos/complete'] = 'user/usersettings/earnings_videos/complete';


// ADS
$route['user/earnings/ads'] = 'user/usersettings/earnings_ads/index';
$route['user/earnings/ads/watch/(:num)'] = 'user/usersettings/earnings_ads/watch/$1';
$route['user/earnings/ads/start/(:num)'] = 'user/usersettings/earnings_ads/start/$1';
$route['user/earnings/ads/complete'] = 'user/usersettings/earnings_ads/complete';




$route['user/myorders'] = 'user/usersettings/tranfercontroller/myorders';
$route['user/profit'] = 'user/usersettings/historycontroller';

$route['user/historyprofit'] = 'user/usersettings/historycontroller/historyprofit';
$route['user/view-lending'] = 'user/usersettings/historycontroller/lendingProfitHistory';

$route['user/view-rank'] = 'user/usersettings/historycontroller/lendingRankHistory';
$route['user/view-team'] = 'user/usersettings/historycontroller/lendingTeamHistory';
$route['user/view-pool'] = 'user/usersettings/historycontroller/lendingPoolHistory';
$route['user/view-referral'] = 'user/usersettings/historycontroller/lendingReferralHistory';
$route['user/view-binary'] = 'user/usersettings/historycontroller/lendingBinaryHistory';
$route['user/wallet'] = 'user/usersettings/historycontroller/lendingMywalletHistory';

$route['user/my-referral'] = 'user/usersettings/historycontroller/myreferralHistory';
$route['user/referrals'] = 'user/usersettings/historycontroller/myreferralHistory';

$route['user/dex-wallet'] = 'user/usersettings/historycontroller/mydexHistory';
$route['user/lending-history'] = 'user/usersettings/historycontroller/myllendinglist';
$route['user/get-list-investment'] = 'user/usersettings/historycontroller/investment_list_get';
$route['user/info/(:num)'] = 'user/usersettings/historycontroller/investment_info/$1';


$route['balance-info-user/(:num)'] = 'user/user/viewuserinfo/$1';
$route['user/support'] = 'user/usersettings/support';
$route['user/create-ticket'] = 'user/usersettings/support/create';
$route['user/view-ticket/(:num)'] = 'user/usersettings/support/edit/$1';
$route['user/support-list'] = 'user/usersettings/support/list';
$route['user/update-ticket'] = 'user/usersettings/support/update';

$route['user/user-support-tickets'] = 'user/usersettings/support/tickets_json'; // ✅ new JSON
$route['user/support-faqs'] = 'user/usersettings/support/faqs_json'; // ✅ optional JSON
$route['user/support-ticket-view'] = 'user/usersettings/support/ticket_view_api';


$route['user/make-investment-post'] = 'user/usersettings/lendingcontroller/makeinvestment_post';
$route['user/main'] = 'user/user';

$route['user/view-profile'] = 'user/usersettings/profile/settings';
$route['user/edit-profile'] = 'user/usersettings/profile/edit';

// ----- Profile Settings Page -----
$route['user/profile'] = 'user/usersettings/profile/settings';

// ----- AJAX API (your view calls these) -----
$route['member/profile/profile_update'] = 'user/usersettings/profile/profile_update';
$route['member/profile/kyc_submit'] = 'user/usersettings/profile/kyc_submit';
$route['member/profile/bank_save'] = 'user/usersettings/profile/bank_save';
$route['member/profile/update_password'] = 'user/usersettings/profile/update_password';
$route['member/profile/update_email_preferences'] = 'user/usersettings/profile/update_email_preferences';
$route['member/profile/request_delete'] = 'user/usersettings/profile/request_delete';
$route['member/profile/freeze_withdraw'] = 'user/usersettings/profile/freeze_withdraw';


$route['switch_language/(:any)'] = 'api/api/switch_language/$1';


$route['user/shop-list'] = 'user/shop/Shopcontroller';
$route['user/shop/filter_price'] = 'user/shop/Shopcontroller/filter_by_price';
$route['user/shop/product-view/(:num)'] = 'user/shop/Shopcontroller/view_product/$1';
$route['user/shop/add_to_cart'] = 'user/shop/Shopcontroller/add_to_cart';
$route['user/shop/add_to_wishlist'] = 'user/shop/Shopcontroller/add_to_wishlist';
$route['user/shop/wishlist-count'] = 'user/shop/Shopcontroller/wishlist_count';
$route['user/shop/ajax-get-wishlist'] = 'user/shop/Shopcontroller/ajax_get_wishlist';
$route['user/shop/remove-wishlist-item'] = 'user/shop/Shopcontroller/remove_wishlist_item';
$route['user/shop/get_cart_items'] = 'user/shop/Shopcontroller/get_cart_items';
$route['user/shop/remove_item'] = 'user/shop/Shopcontroller/remove_item';
$route['user/shop/get_cart_count'] = 'user/shop/Shopcontroller/get_cart_count';
$route['user/shop/get_cart_page'] = 'user/shop/Shopcontroller/get_cart_page';
$route['user/shop/update_cart_qty'] = 'user/shop/Shopcontroller/update_cart_qty';
$route['user/shop/remove_from_cart'] = 'user/shop/Shopcontroller/remove_from_cart';

$route['user/shop/get_checkout_page'] = 'user/shop/Shopcontroller/get_checkout_page';
$route['user/shop/save_address'] = 'user/shop/Shopcontroller/save_address';
$route['user/shop/save_order'] = 'user/shop/Shopcontroller/save_order';

$route['user/shop/order_success'] = 'user/shop/Shopcontroller/order_success';
$route['user/shop/invoice/(:num)'] = 'user/shop/Shopcontroller/invoice/$1';
$route['user/lending/payment_success'] = 'user/usersettings/lendingcontroller/payment_success';
$route['user/lending/payment_failed'] = 'user/usersettings/lendingcontroller/payment_failed';
$route['user/shop/order_failed'] = 'user/shop/Shopcontroller/order_failed';

$route['user/my-order-transaction-list'] = 'user/usersettings/tranfercontroller/fetch_user_orders';

$route['user/shop/view-order/(:num)'] = 'user/usersettings/tranfercontroller/view_order/$1';
$route['user/profile-update'] = 'user/usersettings/Profile/profile_update';
$route['user/profile-update'] = 'user/usersettings/Profile/profile_update';
$route['api/assistant'] = 'api/assistant/index';

$route['user/send_email_otp'] = 'user/usersettings/Profile/send_email_otp';   // POST
$route['user/email_update'] = 'user/usersettings/Profile/email_update';     // POST
$route['user/update_password'] = 'user/usersettings/Profile/update_password';  // POST
$route['user/verify_email_otp'] = 'user/usersettings/Profile/verify_email_otp';
$route['user/password_update'] = 'user/usersettings/Profile/update_password';
$route['user/twofa/toggle'] = 'user/usersettings/Profile/twofa_toggle';
$route['user/update_email_preferences'] = 'user/usersettings/Profile/update_email_preferences';

// User KYC
$route['user/kyc']['get'] = 'user/Kyc/index';            // show form + current status
$route['user/kyc/submit']['post'] = 'user/Kyc/submit';           // create/update submission
$route['user/kyc/upload']['post'] = 'user/Kyc/upload';           // optional file upload handler
// Admin (review)
$route['admin/kyc']['get'] = 'admin/AdminKyc/index';
$route['admin/kyc/(:num)']['get'] = 'admin/AdminKyc/view/$1';
$route['admin/kyc/(:num)/decision']['post'] = 'admin/AdminKyc/decision/$1';
$route['admin/kyc/list']['get'] = 'admin/AdminKyc/list';
$route['admin/kyc/show/(:num)']['get'] = 'admin/AdminKyc/show/$1';
$route['admin/kyc/decision/(:num)']['post'] = 'admin/AdminKyc/decision/$1';
$route['admin/kyc/export']['get'] = 'admin/AdminKyc/export_csv';


/************************WITHDRAW REQUESTS */
$route['withdraw-requests'] = 'admin/withdraw/Withdraw';
$route['withdraw-request-list'] = 'admin/withdraw/Withdraw/list';
$route['view-withdraw/(:num)'] = 'admin/withdraw/Withdraw/viewuser/$1';
$route['update-withdraw/(:num)'] = 'admin/withdraw/Withdraw/update/$1';


// Bank
$route['admin/bank-verification'] = 'admin/AdminBankVerification/index';
$route['admin/bank-verification/list'] = 'admin/AdminBankVerification/list';
$route['admin/bank-verification/show/(:num)'] = 'admin/AdminBankVerification/show/$1';
$route['admin/bank-verification/decision/(:num)'] = 'admin/AdminBankVerification/decision/$1';
$route['admin/bank-verification/export'] = 'admin/AdminBankVerification/export_csv';


// Admin
$route['admin/commission-calculator'] = 'admin/CommissionCalculator/index';
$route['admin/commission-calculator/live'] = 'admin/CommissionCalculator/live_calc';
$route['admin/commission-calculator/whatif'] = 'admin/CommissionCalculator/whatif_calc';

$route['admin/binary-business-report'] = 'admin/BusinessReport/index';
$route['admin/binary-business-report/run'] = 'admin/BusinessReport/simulate';


// User (logged in)
$route['app/my-calculator'] = 'app/CommissionCalculator/index';
$route['app/my-calculator/live'] = 'app/CommissionCalculator/live_calc';
$route['app/my-calculator/whatif'] = 'app/CommissionCalculator/whatif_calc';
$route['getMlmCommissionData'] = 'admin/Administrator/getMlmCommissionData';


$route['admin/orders'] = 'admin/shop/orders';
$route['admin/orders/view/(:num)'] = 'admin/shop/orders/view/$1';
$route['admin/orders/update_tracking'] = 'admin/shop/orders/update_tracking';
$route['admin/orders/invoice/(:num)'] = 'admin/shop/orders/invoice/$1';

$route['matrix-mlm'] = 'Blog/matrix_mlm';
$route['australia-mlm'] = 'Blog/austrilia_mlm';
$route['gift-mlm'] = 'Blog/gift_mlm';
$route['top-6-mlm-plans'] = 'Blog/top_six_mlm';



$route['user/recentOrdersAjax'] = 'user/user/recentOrdersAjax';
$route['user/recentCommissionsAjax'] = 'user/user/recentCommissionsAjax';


$route['user/payouts/request'] = 'user/payouts/request';

// echo "<pre>";print_r($route);exit;



$route['GlobalVerify/status'] = 'GlobalVerify/status';
$route['GlobalVerify/start'] = 'GlobalVerify/start';
$route['GlobalVerify/verify'] = 'GlobalVerify/verify';

/**************** Dynamic Landing Page ****************/
// public — '/' (default_controller) and '/landing' both render the landing page
$route['landing'] = 'Landing';
$route['landing/early-access'] = 'Landing/early_access';   // hero email -> SMTP
$route['home'] = 'Landing';
// backup alias for the previous shop/e-commerce home page
$route['shop-home'] = 'Welcome';

// admin — Content Management -> Landing Page Settings
$route['landing-page-cms'] = 'admin/cms/Landingpage';
$route['landing-save-section'] = 'admin/cms/Landingpage/save_section';
$route['landing-item-save/(:any)'] = 'admin/cms/Landingpage/item_save/$1';
$route['landing-item-delete/(:any)/(:num)'] = 'admin/cms/Landingpage/item_delete/$1/$2';
$route['landing-item-status/(:any)/(:num)'] = 'admin/cms/Landingpage/item_status/$1/$2';
$route['landing-item-reorder/(:any)'] = 'admin/cms/Landingpage/item_reorder/$1';
$route['landing-export'] = 'admin/cms/Landingpage/export';
$route['landing-import'] = 'admin/cms/Landingpage/import';
$route['landing-save-version'] = 'admin/cms/Landingpage/save_version';
$route['landing-restore-version/(:num)'] = 'admin/cms/Landingpage/restore_version/$1';