<?php $this->load->view('admin/Layout/common_style'); ?>

<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />

<style>
    .tkm-addr { font-family: monospace; font-size: .85rem; }
    .tkm-section { border-bottom: 1px dashed #e4e6ef; padding-bottom: 12px; margin-bottom: 16px; }
    .tkm-section h5 { color: #009ef7; }
    .tkm-copy { cursor: pointer; }
    .tkm-settings-dialog { max-width: min(1200px, calc(100vw - 2rem)); }
    /* Mask the Treasury secret WITHOUT a password input, so the browser never
       offers to save the private key in its password manager. */
    .tkm-mask { -webkit-text-security: disc; text-security: disc; }
    .tkm-mask.tkm-show { -webkit-text-security: none; text-security: none; }
</style>

<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true"
    data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true"
    data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
    data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">

    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">

            <?php $this->load->view('admin/Layout/admin_topbar'); ?>

            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">

                <?php $this->load->view('admin/Layout/admin_sidebar'); ?>

                <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                    <div class="d-flex flex-column flex-column-fluid">

                        <!--begin::Toolbar-->
                        <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                                    <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                                        <?php echo $title; ?>
                                    </h1>
                                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                        <li class="breadcrumb-item text-muted">
                                            <a href="<?php echo base_url(); ?>" class="text-muted text-hover-primary">Master</a>
                                        </li>
                                        <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                        <li class="breadcrumb-item text-muted"><?php echo $title; ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <!--end::Toolbar-->

                        <div id="kt_app_content" class="app-content flex-column-fluid mt-10">
                            <div id="kt_app_content_container" class="app-container container-xxl">

                                <?php $this->load->view('notification'); ?>

                                <div class="card mb-5 mb-xxl-8">
                                    <div class="card-header border-transparent pt-5">
                                        <h3 class="card-title fw-bold"><?php echo $card_tilte; ?></h3>
                                        <div class="card-toolbar gap-2">
                                            <button type="button" class="btn btn-light btn-sm" id="tkm-audit-btn">Audit Log</button>
                                            <?php if ($is_super): ?>
                                            <button type="button" class="btn btn-primary btn-sm" id="tkm-add-btn">
                                                <i class="ki-duotone ki-plus fs-2"></i> Add Configuration
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="card-body pt-3 pb-9">
                                        <div class="text-muted fs-7 mb-5">
                                            Single source of truth for the blockchain: network, BMAN &amp; USDT
                                            tokens, exchange rate, platform wallets, smart contracts and chain
                                            parameters. Only <b>one configuration is active</b> at a time — its
                                            exchange rate is what new purchases use (old transactions keep the
                                            rate snapshotted at purchase time; changing it never affects them).
                                            <?php if (!$is_super): ?>
                                                <span class="badge badge-light-danger ms-2">View / enable–disable only — editing is Super-Admin</span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table align-middle table-row-dashed fs-6 gy-4" id="tkm-table">
                                                <thead>
                                                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                                        <th>ID</th>
                                                        <th>Network</th>
                                                        <th>Token</th>
                                                        <th>Contract Address</th>
                                                        <th class="text-end">Exchange Rate</th>
                                                        <th class="text-center">Status</th>
                                                        <th>Last Updated</th>
                                                        <th>Updated By</th>
                                                        <th class="text-end">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="text-gray-700 fw-semibold">
                                                    <?php foreach ($settings as $s): ?>
                                                    <tr data-id="<?php echo (int)$s['id']; ?>">
                                                        <td><?php echo (int)$s['id']; ?></td>
                                                        <td>
                                                            <span class="fw-bold text-uppercase"><?php echo html_escape($s['network']); ?></span>
                                                            <div class="text-muted fs-8"><?php echo html_escape($s['blockchain']); ?> · chain <?php echo (int)$s['chain_id']; ?></div>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($s['bman_logo'])): ?>
                                                                <img src="<?php echo base_url(html_escape($s['bman_logo'])); ?>" class="w-20px h-20px rounded-circle me-1" alt="" />
                                                            <?php endif; ?>
                                                            <?php echo html_escape($s['bman_symbol']); ?>
                                                            <span class="text-muted">/</span> <?php echo html_escape($s['usdt_symbol']); ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($s['bman_contract']): ?>
                                                                <span class="tkm-addr"><?php echo html_escape(substr($s['bman_contract'], 0, 10)); ?>…<?php echo html_escape(substr($s['bman_contract'], -6)); ?></span>
                                                                <i class="ki-duotone ki-copy fs-6 ms-1 tkm-copy" title="Copy contract address"
                                                                   data-addr="<?php echo html_escape($s['bman_contract']); ?>"></i>
                                                            <?php else: ?>
                                                                <span class="text-muted fs-8">not set</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-end">
                                                            <?php echo $s['exchange_type'] === 'usdt_to_bman'
                                                                ? '1 '.html_escape($s['usdt_symbol']).' = '.rtrim(rtrim(number_format((float)$s['exchange_rate'], 8, '.', ''), '0'), '.').' '.html_escape($s['bman_symbol'])
                                                                : '1 '.html_escape($s['bman_symbol']).' = '.rtrim(rtrim(number_format((float)$s['exchange_rate'], 8, '.', ''), '0'), '.').' '.html_escape($s['usdt_symbol']); ?>
                                                            <?php if ($s['rate_effective_from']): ?>
                                                                <div class="text-muted fs-8">since <?php echo html_escape($s['rate_effective_from']); ?></div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <?php echo $s['status']
                                                                ? '<span class="badge badge-light-success">ACTIVE</span>'
                                                                : '<span class="badge badge-light">inactive</span>'; ?>
                                                        </td>
                                                        <td class="text-muted fs-8"><?php echo html_escape($s['updated_at']); ?></td>
                                                        <td class="text-muted fs-8"><?php echo html_escape($s['updated_by_name'] ?: '—'); ?></td>
                                                        <td class="text-end">
                                                            <?php if ($is_super): ?>
                                                                <button class="btn btn-sm btn-light-primary tkm-edit">Edit</button>
                                                                <?php if (!$s['status']): ?>
                                                                    <button class="btn btn-sm btn-light-warning tkm-activate">Activate</button>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <span class="text-muted fs-8">view only</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Add / Edit modal (7 sections) -->
                                <div class="modal fade" id="tkm-modal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered tkm-settings-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h3 class="modal-title" id="tkm-modal-title">Add Configuration</h3>
                                                <div class="btn btn-sm btn-icon" data-bs-dismiss="modal">
                                                    <i class="ki-outline ki-cross fs-1"></i>
                                                </div>
                                            </div>
                                            <div class="modal-body scroll-y mh-650px">
                                                <form id="tkm-form" enctype="multipart/form-data" autocomplete="off">
                                                    <input type="hidden" name="id" value="0" />

                                                    <div class="tkm-section">
                                                        <h5 class="fw-bold mb-4">1 · Network Settings</h5>
                                                        <div class="row">
                                                            <div class="col-md-3 mb-4">
                                                                <label class="form-label required fs-7">Network</label>
                                                                <select name="network" class="form-select form-select-solid">
                                                                    <option value="mainnet">Mainnet</option>
                                                                    <option value="testnet">Testnet</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6 mb-4">
                                                                <label class="form-label required fs-7">Blockchain</label>
                                                                <input type="text" name="blockchain" class="form-control form-control-solid"
                                                                    value="Binance Smart Chain (BEP20)" required />
                                                            </div>
                                                            <div class="col-md-3 mb-4">
                                                                <label class="form-label required fs-7">Chain ID</label>
                                                                <input type="number" name="chain_id" min="1" class="form-control form-control-solid" value="56" required />
                                                            </div>
                                                            <div class="col-md-6 mb-4">
                                                                <label class="form-label required fs-7">RPC URL</label>
                                                                <input type="url" name="rpc_url" class="form-control form-control-solid"
                                                                    placeholder="https://bsc-dataseed.binance.org (testnet: https://data-seed-prebsc-1-s1.binance.org:8545)" required />
                                                            </div>
                                                            <div class="col-md-4 mb-4">
                                                                <label class="form-label required fs-7">Explorer URL</label>
                                                                <input type="url" name="explorer_url" class="form-control form-control-solid"
                                                                    placeholder="https://bscscan.com (testnet: https://testnet.bscscan.com)" required />
                                                            </div>
                                                            <div class="col-md-2 mb-4 d-flex align-items-end">
                                                                <button type="button" class="btn btn-light-info btn-sm w-100" id="tkm-test-rpc">Test RPC</button>
                                                            </div>
                                                            <div class="col-md-4 mb-4">
                                                                <label class="form-label fs-7">Deposit scan mode</label>
                                                                <select name="deposit_scan_mode" class="form-select form-select-solid">
                                                                    <option value="bscscan">BscScan / Etherscan API (recommended)</option>
                                                                    <option value="rpc">RPC eth_getLogs (log-capable node)</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-4 mb-4">
                                                                <label class="form-label fs-7">Explorer API URL</label>
                                                                <input type="text" name="explorer_api_url" class="form-control form-control-solid"
                                                                    placeholder="https://api.etherscan.io/v2/api" />
                                                            </div>
                                                            <div class="col-md-4 mb-4">
                                                                <label class="form-label fs-7">Explorer API Key <span class="text-muted fs-9">(free — enables auto-deposit)</span></label>
                                                                <input type="text" name="explorer_api_key" class="form-control form-control-solid tkm-addr"
                                                                    placeholder="your BscScan/Etherscan API key" />
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="tkm-section">
                                                        <h5 class="fw-bold mb-4">2 · BMAN Token</h5>
                                                        <div class="row">
                                                            <div class="col-md-4 mb-4">
                                                                <label class="form-label required fs-7">Token Name</label>
                                                                <input type="text" name="bman_name" class="form-control form-control-solid" placeholder="BMAN Token" required />
                                                            </div>
                                                            <div class="col-md-2 mb-4">
                                                                <label class="form-label required fs-7">Symbol</label>
                                                                <input type="text" name="bman_symbol" class="form-control form-control-solid" placeholder="BMAN" required />
                                                            </div>
                                                            <div class="col-md-2 mb-4">
                                                                <label class="form-label required fs-7">Decimals</label>
                                                                <input type="number" name="bman_decimals" min="0" max="36" class="form-control form-control-solid" placeholder="18" required />
                                                            </div>
                                                            <div class="col-md-4 mb-4">
                                                                <label class="form-label fs-7">Contract Address</label>
                                                                <input type="text" name="bman_contract" placeholder="0x1234abcd…ef56 (your deployed BEP-20)" class="form-control form-control-solid tkm-addr" />
                                                            </div>
                                                            <div class="col-md-4 mb-4">
                                                                <label class="form-label fs-7">Token Logo</label>
                                                                <div class="d-flex align-items-center gap-3">
                                                                    <img id="tkm-logo-preview" src="" alt="logo"
                                                                        class="w-40px h-40px rounded-circle border border-gray-300 d-none" style="object-fit:cover;background:#fff" />
                                                                    <input type="file" name="bman_logo_file" id="tkm-logo-file" accept="image/*" class="form-control form-control-solid" />
                                                                </div>
                                                                <div class="text-muted fs-8 mt-1" id="tkm-logo-note"></div>
                                                            </div>
                                                            <div class="col-md-3 mb-4">
                                                                <label class="form-label fs-7">Minimum Transfer</label>
                                                                <input type="number" step="0.0001" min="0" name="bman_min_transfer" class="form-control form-control-solid" placeholder="1.0000" />
                                                            </div>
                                                            <div class="col-md-3 mb-4">
                                                                <label class="form-label fs-7">Maximum Transfer <span class="text-muted fs-9">(0 = unlimited)</span></label>
                                                                <input type="number" step="0.0001" min="0" name="bman_max_transfer" class="form-control form-control-solid" placeholder="0.0000" />
                                                            </div>
                                                            <div class="col-md-2 mb-4 d-flex align-items-end">
                                                                <div class="form-check form-switch form-check-custom form-check-solid">
                                                                    <input class="form-check-input" type="checkbox" name="bman_enabled" value="1" checked />
                                                                    <label class="form-check-label fs-7">Enabled</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="tkm-section">
                                                        <h5 class="fw-bold mb-4">3 · USDT Token</h5>
                                                        <div class="row">
                                                            <div class="col-md-4 mb-4">
                                                                <label class="form-label required fs-7">Token Name</label>
                                                                <input type="text" name="usdt_name" class="form-control form-control-solid" placeholder="Tether USD" required />
                                                            </div>
                                                            <div class="col-md-2 mb-4">
                                                                <label class="form-label required fs-7">Symbol</label>
                                                                <input type="text" name="usdt_symbol" class="form-control form-control-solid" placeholder="USDT" required />
                                                            </div>
                                                            <div class="col-md-2 mb-4">
                                                                <label class="form-label required fs-7">Decimals</label>
                                                                <input type="number" name="usdt_decimals" min="0" max="36" class="form-control form-control-solid" placeholder="18" required />
                                                            </div>
                                                            <div class="col-md-4 mb-4">
                                                                <label class="form-label fs-7">Contract Address</label>
                                                                <input type="text" name="usdt_contract" placeholder="0x55d398326f99059fF775485246999027B3197955 (BSC USDT)" class="form-control form-control-solid tkm-addr" />
                                                            </div>
                                                            <div class="col-md-3 mb-4">
                                                                <label class="form-label fs-7">Minimum Deposit</label>
                                                                <input type="number" step="0.0001" min="0" name="minimum_deposit" class="form-control form-control-solid" placeholder="10.0000" />
                                                            </div>
                                                            <div class="col-md-3 mb-4">
                                                                <label class="form-label fs-7">Minimum Withdrawal</label>
                                                                <input type="number" step="0.0001" min="0" name="minimum_withdrawal" class="form-control form-control-solid" placeholder="20.0000" />
                                                            </div>
                                                            <div class="col-md-3 mb-4">
                                                                <label class="form-label fs-7">Maximum Withdrawal <span class="text-muted fs-9">(0 = unlimited)</span></label>
                                                                <input type="number" step="0.0001" min="0" name="maximum_withdrawal" class="form-control form-control-solid" placeholder="1000.0000" />
                                                            </div>
                                                            <div class="col-md-2 mb-4 d-flex align-items-end">
                                                                <div class="form-check form-switch form-check-custom form-check-solid">
                                                                    <input class="form-check-input" type="checkbox" name="usdt_enabled" value="1" checked />
                                                                    <label class="form-check-label fs-7">Enabled</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="tkm-section">
                                                        <h5 class="fw-bold mb-4">4 · Exchange Rate</h5>
                                                        <div class="row">
                                                            <div class="col-md-4 mb-4">
                                                                <label class="form-label required fs-7">Calculation Method</label>
                                                                <select name="exchange_type" class="form-select form-select-solid" id="tkm-ex-type">
                                                                    <option value="usdt_to_bman">1 USDT = X BMAN</option>
                                                                    <option value="bman_to_usdt">1 BMAN = X USDT</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-4 mb-4">
                                                                <label class="form-label required fs-7">Current Exchange Rate</label>
                                                                <input type="number" step="0.00000001" min="0.00000001" name="exchange_rate"
                                                                    class="form-control form-control-solid" id="tkm-ex-rate" placeholder="500 (1 USDT = 500 BMAN)" required />
                                                            </div>
                                                            <div class="col-md-4 mb-4">
                                                                <label class="form-label fs-7">Effective From</label>
                                                                <input type="date" name="rate_effective_from" class="form-control form-control-solid" />
                                                            </div>
                                                        </div>
                                                        <div class="text-muted fs-8" id="tkm-ex-note">
                                                            Old transactions keep the rate snapshotted at purchase; only new
                                                            purchases use this rate.
                                                        </div>
                                                    </div>

                                                    <div class="tkm-section">
                                                        <h5 class="fw-bold mb-4">5 · Platform Wallets &amp; Treasury Key</h5>
                                                        <div class="text-muted fs-8 mb-3">
                                                            USDT → BMAN is a single flow signed by the <b>Treasury</b> key.
                                                            Enter the Treasury <b>private key</b> or <b>mnemonic phrase</b> —
                                                            the wallet address is <b>derived automatically</b> and shown
                                                            below. The secret is stored <b>AES-encrypted</b> and never
                                                            shown again. Users deposit USDT to the <b>Deposit</b> wallet.
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-12 mb-2">
                                                                <label class="form-label fs-7">Treasury Private Key or Mnemonic Phrase
                                                                    <span class="text-danger fs-9">(encrypted · leave blank to keep the current key)</span>
                                                                    <span class="badge badge-light-success ms-2 d-none" id="tkm-pk-set">key stored</span></label>
                                                                <div class="input-group">
                                                                    <input type="text" name="treasury_secret" id="tkm-secret"
                                                                        autocomplete="off" data-lpignore="true" data-form-type="other"
                                                                        spellcheck="false" autocapitalize="off"
                                                                        placeholder="64-hex private key (0x…) OR 12/24-word mnemonic phrase"
                                                                        class="form-control form-control-solid tkm-addr tkm-mask" />
                                                                    <button type="button" class="btn btn-icon btn-light" id="tkm-eye" tabindex="-1" title="Show / hide">
                                                                        <i class="ki-outline ki-eye fs-2"></i></button>
                                                                    <button type="button" class="btn btn-light-info" id="tkm-derive">Derive Address</button>
                                                                </div>
                                                                <div class="text-muted fs-8 mt-1" id="tkm-secret-note">Used only server-side to sign BMAN sends — never displayed. The address appears below as you type.</div>
                                                            </div>
                                                            <div class="col-md-6 mb-4">
                                                                <label class="form-label fs-7">Treasury Wallet <span class="text-muted fs-9">— derived from the key/phrase above</span></label>
                                                                <input type="text" name="treasury_wallet" id="tkm-treasury-addr" readonly
                                                                    placeholder="derived automatically from your key / phrase"
                                                                    class="form-control form-control-solid tkm-addr" style="background:#f5f8fa" />
                                                            </div>
                                                            <div class="col-md-6 mb-4">
                                                                <label class="form-label fs-7">Deposit Wallet <span class="text-muted fs-9">— users deposit USDT here</span></label>
                                                                <input type="text" name="deposit_wallet" placeholder="0xAbC0000000000000000000000000000000000123"
                                                                    class="form-control form-control-solid tkm-addr" />
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="tkm-section" id="tkm-payout-pw-section">
                                                        <h5 class="fw-bold mb-4">Treasury Key Reveal — Payout Password
                                                            <span class="badge badge-light-success ms-2 d-none" id="tkm-pw-set">password set</span></h5>
                                                        <div class="text-muted fs-8 mb-3">
                                                            Separate from any admin's own login password. Required every
                                                            time an admin reveals the decrypted treasury private key on a
                                                            withdrawal review page (for manually sending a payout from an
                                                            external wallet). Never stored in plaintext, and this form
                                                            never shows the currently-saved password back to you.
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-4 mb-4">
                                                                <label class="form-label fs-7">New Payout Password</label>
                                                                <input type="password" id="tkm-pw-new" autocomplete="new-password"
                                                                    class="form-control form-control-solid" placeholder="min. 8 characters" />
                                                            </div>
                                                            <div class="col-md-4 mb-4">
                                                                <label class="form-label fs-7">Confirm Payout Password</label>
                                                                <input type="password" id="tkm-pw-confirm" autocomplete="new-password"
                                                                    class="form-control form-control-solid" placeholder="re-enter" />
                                                            </div>
                                                            <div class="col-md-4 mb-4 d-flex align-items-end">
                                                                <button type="button" class="btn btn-light-primary" id="tkm-pw-save">Save Payout Password</button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="tkm-section">
                                                        <h5 class="fw-bold mb-4">6 · Blockchain Settings</h5>
                                                        <div class="row">
                                                            <div class="col-md-2 mb-4">
                                                                <label class="form-label fs-7">Min Confirmations</label>
                                                                <input type="number" min="0" name="minimum_confirmations" class="form-control form-control-solid" placeholder="15" />
                                                            </div>
                                                            <div class="col-md-3 mb-4">
                                                                <label class="form-label fs-7">Gas Limit</label>
                                                                <input type="number" min="0" name="gas_limit" class="form-control form-control-solid" placeholder="210000" />
                                                            </div>
                                                            <div class="col-md-3 mb-4">
                                                                <label class="form-label fs-7">Gas Price (gwei)</label>
                                                                <input type="text" name="gas_price" class="form-control form-control-solid" placeholder="5" />
                                                            </div>
                                                            <div class="col-md-2 mb-4">
                                                                <label class="form-label fs-7">Tx Timeout (s)</label>
                                                                <input type="number" min="0" name="transaction_timeout" class="form-control form-control-solid" placeholder="300" />
                                                            </div>
                                                            <div class="col-md-2 mb-4">
                                                                <label class="form-label fs-7">Retry Count</label>
                                                                <input type="number" min="0" name="retry_count" class="form-control form-control-solid" placeholder="3" />
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="text-end">
                                                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary" id="tkm-save-btn">Save Configuration</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Wallet tools (on-chain, via web3 library) -->
                                <div class="card mb-5 mb-xxl-8">
                                    <div class="card-header border-transparent pt-5">
                                        <h3 class="card-title fw-bold">Wallet Tools <span class="text-muted fs-7 fw-normal ms-2">BEP-20 · reads active Token Settings</span></h3>
                                    </div>
                                    <div class="card-body pt-2 pb-8">
                                        <div class="row g-6">
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Check on-chain balance</label>
                                                <div class="input-group">
                                                    <input type="text" id="tkm-bal-addr" class="form-control form-control-solid tkm-addr" placeholder="0x… wallet address" />
                                                    <button class="btn btn-light-primary" id="tkm-bal-btn" type="button">Check</button>
                                                </div>
                                                <div class="text-muted fs-8 mt-1">Reads BNB + BMAN balance from the active RPC (read-only).</div>
                                            </div>
                                            <?php if ($is_super): ?>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Generate a platform wallet</label>
                                                <div>
                                                    <button class="btn btn-light-warning" id="tkm-gen-btn" type="button">Generate Wallet</button>
                                                </div>
                                                <div class="text-muted fs-8 mt-1">
                                                    Creates an address + private key offline. The key is shown once and
                                                    <b>never stored</b> — copy it into secure storage. Paste the address
                                                    into a wallet field above (Treasury / Deposit / Gas …).
                                                </div>
                                                <div id="tkm-gen-out" class="mt-3 d-none">
                                                    <div class="alert alert-warning fs-8 mb-2">
                                                        Store the private key now — it will not be shown again.
                                                    </div>
                                                    <div class="mb-1"><span class="fw-bold">Address:</span> <span class="tkm-addr" id="tkm-gen-addr"></span></div>
                                                    <div><span class="fw-bold">Private key:</span> <span class="tkm-addr text-danger" id="tkm-gen-pk"></span></div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Audit modal -->
                                <div class="modal fade" id="tkm-audit-modal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered mw-900px">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h3 class="modal-title">Token Settings Audit Log</h3>
                                                <div class="btn btn-sm btn-icon" data-bs-dismiss="modal">
                                                    <i class="ki-outline ki-cross fs-1"></i>
                                                </div>
                                            </div>
                                            <div class="modal-body scroll-y mh-500px" id="tkm-audit-body">Loading…</div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>

                    <?php $this->load->view('admin/Layout/admin_footer'); ?>

                </div>
            </div>
        </div>
    </div>

    <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
        <i class="ki-duotone ki-arrow-up"><span class="path1"></span><span class="path2"></span></i>
    </div>

    <?php $this->load->view('admin/Layout/common_script'); ?>
    <script src="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.js"></script>

    <script>
    (function () {
        const base = '<?php echo base_url(); ?>';
        const isSuper = <?php echo $is_super ? 'true' : 'false'; ?>;
        // rows are already sanitised in the controller (no treasury_pk_enc;
        // has_treasury_key + treasury_pk_last5 present)
        const SETTINGS = <?php echo json_encode(array_map(function ($s) {
            unset($s['updated_by_name']);
            return $s;
        }, $settings)); ?>;

        function toast(msg, ok) {
            if (window.Swal) {
                Swal.fire({ text: msg, icon: ok ? 'success' : 'error',
                    buttonsStyling: false, confirmButtonText: 'Ok',
                    customClass: { confirmButton: 'btn btn-primary' } });
            } else { alert(msg); }
        }
        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g,
                c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }
        async function post(url, fd) {
            const res = await fetch(base + url, {
                method: 'POST', body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            let j = {};
            try { j = await res.json(); } catch (e) { j = { status: 'error', message: 'Server error.' }; }
            return { ok: res.ok && j.status === 'success', msg: j.message || '' };
        }

        /* Payout password — separate mini-form, separate endpoint, gates the
           treasury-key reveal on withdrawal review pages. Reflects the ACTIVE
           config's has_payout_password on load. */
        (function () {
            const active = SETTINGS.find(s => Number(s.status) === 1) || SETTINGS[0];
            const badge = document.getElementById('tkm-pw-set');
            if (badge) badge.classList.toggle('d-none', !(active && Number(active.has_payout_password) === 1));

            const saveBtn = document.getElementById('tkm-pw-save');
            if (!saveBtn) return;
            saveBtn.addEventListener('click', async () => {
                const pw = document.getElementById('tkm-pw-new').value;
                const confirm = document.getElementById('tkm-pw-confirm').value;
                if (!pw || pw.length < 8) { toast('Payout password must be at least 8 characters.', false); return; }
                if (pw !== confirm) { toast('Password and confirmation do not match.', false); return; }
                saveBtn.disabled = true;
                const fd = new FormData();
                fd.append('payout_password', pw);
                fd.append('payout_password_confirm', confirm);
                const r = await post('admin/master/tokenmaster/set_payout_password', fd);
                saveBtn.disabled = false;
                toast(r.msg || (r.ok ? 'Saved.' : 'Failed.'), r.ok);
                if (r.ok) {
                    document.getElementById('tkm-pw-new').value = '';
                    document.getElementById('tkm-pw-confirm').value = '';
                    if (badge) badge.classList.remove('d-none');
                }
            });
        })();

        const modalEl = document.getElementById('tkm-modal');
        const form = document.getElementById('tkm-form');
        const modal = () => bootstrap.Modal.getOrCreateInstance(modalEl);

        function fillForm(s) {
            form.reset();
            form.elements.id.value = s ? s.id : 0;

            // logo preview: show the already-uploaded logo when editing
            const prev = document.getElementById('tkm-logo-preview');
            const note = document.getElementById('tkm-logo-note');
            if (s && s.bman_logo) {
                prev.src = base + s.bman_logo;
                prev.classList.remove('d-none');
                note.textContent = 'Current logo — choose a file only if you want to replace it.';
            } else {
                prev.removeAttribute('src');
                prev.classList.add('d-none');
                note.textContent = s ? 'No logo uploaded yet.' : '';
            }

            // Treasury key: on edit show a "stored" badge with the last-5 hint;
            // never prefill the secret. Placeholder shows the masked last 5.
            var pkBadge = document.getElementById('tkm-pk-set');
            var secretEl = form.elements.treasury_secret;
            if (secretEl) secretEl.value = '';
            if (pkBadge) {
                var hasKey = s && Number(s.has_treasury_key) === 1;
                pkBadge.classList.toggle('d-none', !hasKey);
                if (hasKey) pkBadge.textContent = 'key stored ···' + (s.treasury_pk_last5 || '');
                if (secretEl) secretEl.placeholder = hasKey
                    ? ('current key ends in ···' + (s.treasury_pk_last5 || '') + ' — enter a new key/phrase to replace')
                    : '64-hex private key (0x…) OR 12/24-word mnemonic phrase';
            }

            if (!s) return;
            Object.keys(s).forEach(k => {
                const el = form.elements[k];
                if (!el || el.type === 'file' || el.type === 'hidden' || k === 'treasury_secret') return;
                if (el.type === 'checkbox') el.checked = Number(s[k]) === 1;
                else el.value = s[k] == null ? '' : s[k];
            });
        }

        /* Eye toggle — reveal / mask the secret (CSS only; the field stays a
           text input so the browser never offers to save it as a password). */
        var eyeBtn = document.getElementById('tkm-eye');
        var secretInput = document.getElementById('tkm-secret');
        if (eyeBtn && secretInput) eyeBtn.addEventListener('click', () => {
            const shown = secretInput.classList.toggle('tkm-show');
            eyeBtn.querySelector('i').className = shown ? 'ki-outline ki-eye-slash fs-2' : 'ki-outline ki-eye fs-2';
        });

        /* Derive the Treasury address from a key/phrase and fill the box.
           Shared by the button and the live (debounced) input handler. */
        async function deriveTreasury(silent) {
            const secret = (secretInput.value || '').trim();
            const addrBox = document.getElementById('tkm-treasury-addr');
            const note = document.getElementById('tkm-secret-note');
            if (!secret) {
                if (!silent) toast('Enter a private key or mnemonic phrase first.', false);
                return;
            }
            const fd = new FormData(); fd.append('treasury_secret', secret);
            const res = await fetch(base + 'admin/master/token-settings/derive-treasury', {
                method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            let j = {}; try { j = await res.json(); } catch (e) {}
            if (j.status === 'success') {
                addrBox.value = j.address;
                if (note) { note.textContent = '✓ Valid — address derived from your key/phrase.'; note.style.color = '#50cd89'; }
                if (!silent) toast('Treasury address derived: ' + j.address, true);
            } else {
                addrBox.value = '';
                if (note) { note.textContent = '✗ ' + (j.message || 'Not a valid key/phrase yet.'); note.style.color = '#f1416c'; }
                if (!silent) toast(j.message || 'Could not derive address.', false);
            }
        }

        var deriveBtn = document.getElementById('tkm-derive');
        if (deriveBtn) deriveBtn.addEventListener('click', () => deriveTreasury(false));

        /* Instant derive as the admin types / pastes (debounced). Only fires
           once the input looks complete: a 64-hex key or a 12+ word phrase. */
        if (secretInput) {
            let _t;
            const maybeDerive = () => {
                clearTimeout(_t);
                _t = setTimeout(() => {
                    const v = (secretInput.value || '').trim();
                    const hex = v.replace(/^0x/, '');
                    const looksKey = /^[a-fA-F0-9]{64}$/.test(hex);
                    const looksPhrase = v.split(/\s+/).filter(Boolean).length >= 12;
                    if (looksKey || looksPhrase) deriveTreasury(true);
                    else {
                        document.getElementById('tkm-treasury-addr').value = '';
                        const note = document.getElementById('tkm-secret-note');
                        if (v && note) { note.textContent = 'Keep typing a full 64-hex key or a 12/24-word phrase…'; note.style.color = ''; }
                    }
                }, 350);
            };
            secretInput.addEventListener('input', maybeDerive);
            secretInput.addEventListener('paste', () => setTimeout(maybeDerive, 10));
        }

        /* live preview when a new logo file is picked */
        (function () {
            const file = document.getElementById('tkm-logo-file');
            if (!file) return;
            file.addEventListener('change', () => {
                const prev = document.getElementById('tkm-logo-preview');
                const note = document.getElementById('tkm-logo-note');
                const f = file.files && file.files[0];
                if (f) {
                    prev.src = URL.createObjectURL(f);
                    prev.classList.remove('d-none');
                    note.textContent = 'New logo selected (saved when you click Save Configuration).';
                }
            });
        })();

        /* add / edit */
        const addBtn = document.getElementById('tkm-add-btn');
        if (addBtn) addBtn.addEventListener('click', () => {
            fillForm(null);
            document.getElementById('tkm-modal-title').textContent = 'Add Configuration';
            modal().show();
        });

        document.getElementById('tkm-table').addEventListener('click', async (e) => {
            const tr = e.target.closest('tr[data-id]');

            const copy = e.target.closest('.tkm-copy');
            if (copy) {
                try { await navigator.clipboard.writeText(copy.dataset.addr); toast('Contract address copied.', true); }
                catch (err) { prompt('Copy the contract address:', copy.dataset.addr); }
                return;
            }
            if (!tr) return;

            if (e.target.closest('.tkm-edit')) {
                const s = SETTINGS.find(x => Number(x.id) === Number(tr.dataset.id));
                fillForm(s);
                document.getElementById('tkm-modal-title').textContent =
                    'Edit — ' + s.network.toUpperCase() + ' (chain ' + s.chain_id + ')';
                modal().show();
            }

            if (e.target.closest('.tkm-activate')) {
                if (!confirm('Activate this configuration? The current active one is deactivated and new purchases will use this exchange rate.')) return;
                const r = await post('admin/master/token-settings/activate/' + tr.dataset.id, new FormData());
                toast(r.msg, r.ok);
                if (r.ok) setTimeout(() => location.reload(), 700);
            }
        });

        /* save */
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('tkm-save-btn');
            btn.disabled = true;
            const r = await post('admin/master/token-settings/save', new FormData(form));
            btn.disabled = false;
            toast(r.msg, r.ok);
            if (r.ok) setTimeout(() => location.reload(), 700);
        });

        /* test RPC (uses current form values) */
        document.getElementById('tkm-test-rpc').addEventListener('click', async () => {
            const fd = new FormData();
            fd.append('rpc_url', form.elements.rpc_url.value);
            fd.append('chain_id', form.elements.chain_id.value);
            const btn = document.getElementById('tkm-test-rpc');
            btn.disabled = true; btn.textContent = 'Testing…';
            const r = await post('admin/master/token-settings/test-rpc', fd);
            btn.disabled = false; btn.textContent = 'Test RPC';
            toast(r.msg, r.ok);
        });

        /* live rate wording */
        function rateNote() {
            const t = form.elements.exchange_type.value;
            const v = parseFloat(form.elements.exchange_rate.value) || 0;
            document.getElementById('tkm-ex-note').textContent = v > 0
                ? (t === 'usdt_to_bman' ? '1 USDT = ' + v + ' BMAN' : '1 BMAN = ' + v + ' USDT')
                  + ' — old transactions keep their snapshotted rate; only new purchases use this.'
                : 'Old transactions keep the rate snapshotted at purchase; only new purchases use this rate.';
        }
        document.getElementById('tkm-ex-type').addEventListener('change', rateNote);
        document.getElementById('tkm-ex-rate').addEventListener('input', rateNote);

        /* wallet tools */
        document.getElementById('tkm-bal-btn').addEventListener('click', async () => {
            const fd = new FormData();
            fd.append('address', document.getElementById('tkm-bal-addr').value.trim());
            const btn = document.getElementById('tkm-bal-btn');
            btn.disabled = true; btn.textContent = 'Checking…';
            const r = await post('admin/master/token-settings/check-balance', fd);
            btn.disabled = false; btn.textContent = 'Check';
            toast(r.msg, r.ok);
        });

        const genBtn = document.getElementById('tkm-gen-btn');
        if (genBtn) genBtn.addEventListener('click', async () => {
            if (!confirm('Generate a new wallet? The private key is shown ONCE and never stored — be ready to copy it to secure storage.')) return;
            genBtn.disabled = true; genBtn.textContent = 'Generating…';
            const r = await fetch(base + 'admin/master/token-settings/generate-wallet', {
                method: 'POST', body: new FormData(), headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            let j = {}; try { j = await r.json(); } catch (e) { j = {}; }
            genBtn.disabled = false; genBtn.textContent = 'Generate Wallet';
            if (j.status === 'success') {
                document.getElementById('tkm-gen-addr').textContent = j.address;
                document.getElementById('tkm-gen-pk').textContent = j.private_key;
                document.getElementById('tkm-gen-out').classList.remove('d-none');
            } else {
                toast(j.message || 'Could not generate wallet.', false);
            }
        });

        /* audit log */
        document.getElementById('tkm-audit-btn').addEventListener('click', async () => {
            const m = bootstrap.Modal.getOrCreateInstance(document.getElementById('tkm-audit-modal'));
            const body = document.getElementById('tkm-audit-body');
            body.innerHTML = 'Loading…';
            m.show();
            const res = await fetch(base + 'admin/master/token-settings/audit',
                { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const j = await res.json();
            const rows = (j.rows || []).map(r =>
                '<tr>' +
                '<td>#' + esc(r.setting_id || '—') + '</td>' +
                '<td><span class="badge badge-light-info text-uppercase">' + esc(r.action) + '</span></td>' +
                '<td class="fs-8 text-muted mw-250px text-truncate">' + esc(r.old_value || '—') + '</td>' +
                '<td class="fs-8 text-muted mw-250px text-truncate">' + esc(r.new_value || '—') + '</td>' +
                '<td>' + esc(r.admin_name || ('#' + r.changed_by)) + '</td>' +
                '<td class="fs-8">' + esc(r.ip_address || '—') + '</td>' +
                '<td class="text-muted fs-8">' + esc(r.created_at) + '</td>' +
                '</tr>').join('');
            body.innerHTML = rows
                ? '<table class="table table-row-dashed fs-7"><thead><tr class="fw-bold text-muted">' +
                  '<th>Config</th><th>Action</th><th>Old</th><th>New</th><th>Admin</th><th>IP</th><th>Date</th>' +
                  '</tr></thead><tbody>' + rows + '</tbody></table>'
                : '<div class="text-muted">No changes recorded yet.</div>';
        });
    })();
    </script>
</body>

</html>
