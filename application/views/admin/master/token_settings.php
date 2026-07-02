<?php $this->load->view('admin/Layout/common_style'); ?>

<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />

<style>
    .tkm-addr { font-family: monospace; font-size: .85rem; }
    .tkm-section { border-bottom: 1px dashed #e4e6ef; padding-bottom: 12px; margin-bottom: 16px; }
    .tkm-section h5 { color: #009ef7; }
    .tkm-copy { cursor: pointer; }
    .tkm-settings-dialog { max-width: min(1200px, calc(100vw - 2rem)); }
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
                                                <form id="tkm-form" enctype="multipart/form-data">
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
                                                                <input type="url" name="rpc_url" class="form-control form-control-solid" required />
                                                            </div>
                                                            <div class="col-md-4 mb-4">
                                                                <label class="form-label required fs-7">Explorer URL</label>
                                                                <input type="url" name="explorer_url" class="form-control form-control-solid" required />
                                                            </div>
                                                            <div class="col-md-2 mb-4 d-flex align-items-end">
                                                                <button type="button" class="btn btn-light-info btn-sm w-100" id="tkm-test-rpc">Test RPC</button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="tkm-section">
                                                        <h5 class="fw-bold mb-4">2 · BMAN Token</h5>
                                                        <div class="row">
                                                            <div class="col-md-4 mb-4">
                                                                <label class="form-label required fs-7">Token Name</label>
                                                                <input type="text" name="bman_name" class="form-control form-control-solid" required />
                                                            </div>
                                                            <div class="col-md-2 mb-4">
                                                                <label class="form-label required fs-7">Symbol</label>
                                                                <input type="text" name="bman_symbol" class="form-control form-control-solid" required />
                                                            </div>
                                                            <div class="col-md-2 mb-4">
                                                                <label class="form-label required fs-7">Decimals</label>
                                                                <input type="number" name="bman_decimals" min="0" max="36" class="form-control form-control-solid" required />
                                                            </div>
                                                            <div class="col-md-4 mb-4">
                                                                <label class="form-label fs-7">Contract Address</label>
                                                                <input type="text" name="bman_contract" placeholder="0x…" class="form-control form-control-solid tkm-addr" />
                                                            </div>
                                                            <div class="col-md-4 mb-4">
                                                                <label class="form-label fs-7">Token Logo</label>
                                                                <input type="file" name="bman_logo_file" accept="image/*" class="form-control form-control-solid" />
                                                            </div>
                                                            <div class="col-md-3 mb-4">
                                                                <label class="form-label fs-7">Minimum Transfer</label>
                                                                <input type="number" step="0.0001" min="0" name="bman_min_transfer" class="form-control form-control-solid" />
                                                            </div>
                                                            <div class="col-md-3 mb-4">
                                                                <label class="form-label fs-7">Maximum Transfer <span class="text-muted fs-9">(0 = unlimited)</span></label>
                                                                <input type="number" step="0.0001" min="0" name="bman_max_transfer" class="form-control form-control-solid" />
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
                                                                <input type="text" name="usdt_name" class="form-control form-control-solid" required />
                                                            </div>
                                                            <div class="col-md-2 mb-4">
                                                                <label class="form-label required fs-7">Symbol</label>
                                                                <input type="text" name="usdt_symbol" class="form-control form-control-solid" required />
                                                            </div>
                                                            <div class="col-md-2 mb-4">
                                                                <label class="form-label required fs-7">Decimals</label>
                                                                <input type="number" name="usdt_decimals" min="0" max="36" class="form-control form-control-solid" required />
                                                            </div>
                                                            <div class="col-md-4 mb-4">
                                                                <label class="form-label fs-7">Contract Address</label>
                                                                <input type="text" name="usdt_contract" placeholder="0x…" class="form-control form-control-solid tkm-addr" />
                                                            </div>
                                                            <div class="col-md-3 mb-4">
                                                                <label class="form-label fs-7">Minimum Deposit</label>
                                                                <input type="number" step="0.0001" min="0" name="minimum_deposit" class="form-control form-control-solid" />
                                                            </div>
                                                            <div class="col-md-3 mb-4">
                                                                <label class="form-label fs-7">Minimum Withdrawal</label>
                                                                <input type="number" step="0.0001" min="0" name="minimum_withdrawal" class="form-control form-control-solid" />
                                                            </div>
                                                            <div class="col-md-3 mb-4">
                                                                <label class="form-label fs-7">Maximum Withdrawal <span class="text-muted fs-9">(0 = unlimited)</span></label>
                                                                <input type="number" step="0.0001" min="0" name="maximum_withdrawal" class="form-control form-control-solid" />
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
                                                                    class="form-control form-control-solid" id="tkm-ex-rate" required />
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
                                                        <h5 class="fw-bold mb-4">5 · Wallet Addresses</h5>
                                                        <div class="row">
                                                            <?php foreach ([
                                                                'treasury_wallet' => 'Treasury Wallet', 'deposit_wallet' => 'Deposit Wallet',
                                                                'gas_wallet' => 'Gas Wallet', 'bonus_wallet' => 'Bonus Wallet',
                                                                'reserve_wallet' => 'Reserve Wallet', 'cold_wallet' => 'Cold Wallet',
                                                            ] as $f => $lbl): ?>
                                                            <div class="col-md-6 mb-4">
                                                                <label class="form-label fs-7"><?php echo $lbl; ?></label>
                                                                <input type="text" name="<?php echo $f; ?>" placeholder="0x…"
                                                                    class="form-control form-control-solid tkm-addr" />
                                                            </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>

                                                    <div class="tkm-section">
                                                        <h5 class="fw-bold mb-4">6 · Smart Contracts</h5>
                                                        <div class="text-muted fs-8 mb-3">BMAN token contract is set in section 2.</div>
                                                        <div class="row">
                                                            <?php foreach ([
                                                                'staking_contract' => 'Staking Contract', 'bonus_contract' => 'Bonus Contract',
                                                                'referral_contract' => 'Referral Contract', 'roi_contract' => 'ROI Contract',
                                                            ] as $f => $lbl): ?>
                                                            <div class="col-md-6 mb-4">
                                                                <label class="form-label fs-7"><?php echo $lbl; ?></label>
                                                                <input type="text" name="<?php echo $f; ?>" placeholder="0x…"
                                                                    class="form-control form-control-solid tkm-addr" />
                                                            </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>

                                                    <div class="tkm-section">
                                                        <h5 class="fw-bold mb-4">7 · Blockchain Settings</h5>
                                                        <div class="row">
                                                            <div class="col-md-2 mb-4">
                                                                <label class="form-label fs-7">Min Confirmations</label>
                                                                <input type="number" min="0" name="minimum_confirmations" class="form-control form-control-solid" />
                                                            </div>
                                                            <div class="col-md-3 mb-4">
                                                                <label class="form-label fs-7">Gas Limit</label>
                                                                <input type="number" min="0" name="gas_limit" class="form-control form-control-solid" />
                                                            </div>
                                                            <div class="col-md-3 mb-4">
                                                                <label class="form-label fs-7">Gas Price (gwei)</label>
                                                                <input type="text" name="gas_price" class="form-control form-control-solid" />
                                                            </div>
                                                            <div class="col-md-2 mb-4">
                                                                <label class="form-label fs-7">Tx Timeout (s)</label>
                                                                <input type="number" min="0" name="transaction_timeout" class="form-control form-control-solid" />
                                                            </div>
                                                            <div class="col-md-2 mb-4">
                                                                <label class="form-label fs-7">Retry Count</label>
                                                                <input type="number" min="0" name="retry_count" class="form-control form-control-solid" />
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

        const modalEl = document.getElementById('tkm-modal');
        const form = document.getElementById('tkm-form');
        const modal = () => bootstrap.Modal.getOrCreateInstance(modalEl);

        function fillForm(s) {
            form.reset();
            form.elements.id.value = s ? s.id : 0;
            if (!s) return;
            Object.keys(s).forEach(k => {
                const el = form.elements[k];
                if (!el || el.type === 'file' || el.type === 'hidden') return;
                if (el.type === 'checkbox') el.checked = Number(s[k]) === 1;
                else el.value = s[k] == null ? '' : s[k];
            });
        }

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
