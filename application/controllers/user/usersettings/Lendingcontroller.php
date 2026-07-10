<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Lendingcontroller extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');

        if ($this->session->userdata('user_logged_in') && $this->session->userdata('user_login')) {
            $this->lang->load('common', $this->session->userdata('language'));
        } else {
            redirect('user/in');
        }

        $language = $this->session->userdata('site_lang') ?? 'english';
        $this->config->set_item('language', $language);
        $this->lang->load('common', $language);

    }
    public function index()
    {
        $userid = (int) $this->session->userdata('user_userid');

        $this->data['user_id'] = $userid;
        $this->data['title'] = "Stakings";
        $this->data['card_title'] = "Stakings";

        // Staking packages the user already owns (highlight on the cards). Real
        // data from user_stakes when present; empty-safe otherwise.
        $ownedIds = [];
        if ($this->db->table_exists('user_stakes')) {
            foreach ($this->db->distinct()->select('package_id')->where('user_id', $userid)
                        ->get('user_stakes')->result_array() as $r) {
                $ownedIds[] = (int) $r['package_id'];
            }
        }
        $this->data['owned_stake_ids'] = $ownedIds;

        // Wallet (numeric) — legacy currency balance, kept for backward compat.
        $this->data['wallet_balance_usd'] = (float) site_wallet_balance_without_format($userid);

        // Custodial wallet balances (single source of truth). Staking purchases
        // use the USDT Wallet ONLY; the four BMAN wallets are shown for context.
        $this->load->model('Walletledger_model', 'ledger');
        $bal = $this->ledger->balances($userid);   // usdt/exchange/earning/staking/bonus
        $this->data['wallet_usdt']     = (float) $bal['usdt'];
        $this->data['wallet_bman']     = [
            'exchange' => (float) $bal['exchange'],
            'staking'  => (float) $bal['staking'],
            'bonus'    => (float) $bal['bonus'],
            'earning'  => (float) $bal['earning'],
        ];
        // ≈ BMAN the USDT balance could buy, at the admin exchange rate.
        $this->load->model('Tokenmaster_model', 'tokens');
        $conv = $this->tokens->convertUsdtToBman($this->data['wallet_usdt']);
        $this->data['wallet_usdt_in_bman'] = ($conv === null) ? null : (float) $conv;

        // On-chain swap mode flag (Token Settings). When ON, package purchase is
        // a real USDT<->BMAN on-chain swap; when OFF, the internal staking flow.
        $ts = $this->db->select('swap_enabled')->get_where('token_settings', ['status'=>1])->row_array();
        $this->data['swap_enabled'] = (int)($ts['swap_enabled'] ?? 0);

        // Coin distribution options are managed in the admin master table.
        $this->load->model('Coindistribution_model', 'dist');
        $this->data['coin_distribution_options'] = $this->dist->activeOptions();

        // Packages (map DB fields -> view fields)
        $this->data['packages'] = $this->getPackagesForView();

        // BMAN staking packages (new staking system) + plan rules, for display.
        $this->data['staking_packages'] = $this->getStakingPackagesForView();
        $this->data['staking_plans']    = $this->getStakingPlansForView();

        // Investments table (dynamic)
        $this->data['investments'] = $this->getUserInvestmentsForView($userid);

        // ROI history / recent staking activity (dynamic)
        $this->data['roi_history'] = $this->getUserRoiHistoryForView($userid);
        $this->data['recent_staking_activity'] = $this->getRecentStakingActivityForView($userid);

        // Keep your existing form post route
        $this->data['action'] = base_url("user/make-investment-post");
        $this->data['redirect_url'] = base_url("user/lending");

        $this->load->view('user/wallet/lending_managment', $this->data);
    }

    /**
     * AJAX: quote a staking package (USDT price at the admin rate + bonus),
     * so the purchase modal can show the cost before confirming.
     */
    public function stake_quote()
    {
        $userId = (int) $this->session->userdata('user_userid');
        if (!$userId) { echo json_encode(['status'=>false,'message'=>'Unauthorized']); return; }

        // Opportunistic: credit this user's confirmed USDT deposits before quoting,
        // so a just-arrived deposit shows up without waiting for the cron. Best
        // effort — never let a chain/RPC hiccup break the quote.
        try { $this->load->model('Depositlistener_model','listener'); $this->listener->scan($userId); }
        catch (Exception $e) { /* ignore — the scheduled cron will catch it */ }

        $pkgId = (int) $this->input->post('package_id');
        $pkg = $this->db->get_where('staking_packages', ['id'=>$pkgId, 'is_active'=>1])->row_array();
        if (!$pkg) { echo json_encode(['status'=>false,'message'=>'Package not available.']); return; }

        $this->load->model('Tokenmaster_model', 'tokens');
        $this->load->model('Walletledger_model', 'ledger');
        $bman = (float) $pkg['stake_amount'];
        $usdt = $this->tokens->convertBmanToUsdt($bman);
        if ($usdt === null) { echo json_encode(['status'=>false,'message'=>'Exchange rate not configured.']); return; }

        $bal = $this->ledger->balances($userId);   // usdt + 4 BMAN wallets
        echo json_encode([
            'status'      => true,
            'bman'        => $bman,
            'usdt'        => round($usdt, 8),
            'bonus'       => round($bman * (float)$pkg['bonus_percent'] / 100, 4),
            'bonus_pct'   => (float)$pkg['bonus_percent'],
            'usdt_balance'=> (float) $bal['usdt'],
            'bman_wallets'=> [
                'exchange' => (float) $bal['exchange'],
                'staking'  => (float) $bal['staking'],
                'bonus'    => (float) $bal['bonus'],
                'earning'  => (float) $bal['earning'],
            ],
            'name'        => $pkg['name'],
        ]);
    }

    /**
     * AJAX: purchase a staking package (business rules §5–§9). Delegates the
     * whole transactional flow to Staking_model::purchaseStake().
     */
    public function purchase_stake()
    {
        $userId = (int) $this->session->userdata('user_userid');
        if (!$userId) { echo json_encode(['status'=>false,'message'=>'Unauthorized']); return; }

        $this->load->model('Staking_model');
        list($ok, $res) = $this->Staking_model->purchaseStake([
            'user_id'        => $userId,
            'package_id'     => (int) $this->input->post('package_id'),
            'plan_code'      => (string) $this->input->post('plan_code', true),
            'duration_years' => (int) $this->input->post('duration_years'),
            'ip'             => $this->input->ip_address(),
        ]);

        if (!$ok) { echo json_encode(['status'=>false,'message'=>$res]); return; }
        echo json_encode([
            'status'  => true,
            'message' => 'Stake activated. '.number_format($res['bman']).' BMAN locked · '.number_format($res['bonus']).' BMAN bonus.',
            'data'    => $res,
        ]);
    }

    /**
     * AJAX: on-chain USDT<->BMAN swap purchase (business model "A").
     * Delegates to Swapengine_model. Honours the Token Settings swap flags
     * (swap_enabled / swap_dry_run) — off + dry-run by default, so nothing is
     * broadcast until an admin turns it on after a live test.
     *
     * POST parameters:
     *   - package_id (required): package ID
     *   - plan_code (optional): 'fixed', 'variable', etc.
     *   - plan_id (optional): staking plan ID
     *   - duration_years (optional): staking duration in years
     *   - coin_distribution_option_id (optional): 1=exchange, 2=staking, 3=earning, 4=bonus
     */
    public function swap_purchase()
    {
        $userId = (int) $this->session->userdata('user_userid');
        if (!$userId) { echo json_encode(['status'=>false,'message'=>'Unauthorized']); return; }

        // Get all required fields from POST
        $packageId = (int)$this->input->post('package_id');
        $planCode = (string)($this->input->post('plan_code') ?? 'fixed');
        $planId = (int)($this->input->post('plan_id') ?? 0);
        $durationYears = (int)($this->input->post('duration_years') ?? 1);
        $coinDistOptionId = (int)($this->input->post('coin_distribution_option_id') ?? 1);

        // Validate inputs
        if (!$packageId) { echo json_encode(['status'=>false,'message'=>'Package ID required']); return; }
        if ($coinDistOptionId < 1 || $coinDistOptionId > 7) {
            echo json_encode(['status'=>false,'message'=>'Invalid distribution option (1-7)']); return;
        }

        $this->load->model('staking/Swapengine_model', 'SW');
        list($ok, $res) = $this->SW->execute($userId, $packageId);
        if (!$ok) { echo json_encode(['status'=>false,'message'=>$res]); return; }

        // Update swap order with ALL plan details
        if (!empty($res['id'])) {
            $updateOk = $this->db->where('id', $res['id'])->update('staking_swap_orders', [
                'package_id' => $packageId,
                'plan_code' => $planCode,
                'plan_id' => $planId,
                'duration_years' => $durationYears,
                'coin_distribution_option' => $coinDistOptionId,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            if (!$updateOk) {
                error_log('WARNING: Failed to update staking order '.$res['id'].' with plan details');
            }
        }

        $dry = !empty($res['dry_run']);
        echo json_encode([
            'status'  => true,
            'message' => ($dry ? '[DRY-RUN] ' : '').'Swap order created. USDT '.$res['usdt_amount'].
                         ' → BMAN '.$res['bman_amount'].' (+'.$res['bonus_bman'].' bonus). Distribution: Option '.$coinDistOptionId.'. Plan: '.$planCode.' ('.
                         $durationYears.' years). Status: '.$res['status'],
            'data'    => array_merge($res, [
                'plan_code' => $planCode,
                'plan_id' => $planId,
                'duration_years' => $durationYears,
                'coin_distribution_option' => $coinDistOptionId,
            ]),
        ]);
    }

    public function details_ajax()
    {
        $userId = (int) $this->session->userdata('user_userid');
        if (!$userId) {
            echo json_encode(['status' => false, 'message' => 'Unauthorized']);
            return;
        }

        $investId = (int) $this->input->post('invest_id');
        if (!$investId) {
            echo json_encode(['status' => false, 'message' => 'Invalid invest id']);
            return;
        }

        // ✅ pagination inputs
        $page = (int) $this->input->post('page');
        $limit = (int) $this->input->post('limit');

        if ($page < 1)
            $page = 1;
        if ($limit < 1 || $limit > 100)
            $limit = 10;

        $offset = ($page - 1) * $limit;

        // ✅ 1) Verify investment belongs to user + join package_config
        $inv = $this->db
            ->select('ui.*, pc.package_name as package_name')
            ->from('user_investment ui')
            ->join('package_config pc', 'pc.id = ui.package_id', 'left')
            ->where('ui.id', $investId)
            ->where('ui.user_id', $userId)
            ->get()
            ->row();

        if (!$inv) {
            echo json_encode(['status' => false, 'message' => 'Investment not found']);
            return;
        }

        // ✅ 2) total count for pagination
        $total_rows = (int) $this->db
            ->where('user_id', $userId)
            ->where('invest_id', $investId)
            ->where('hash_id', 'roi-made')
            ->count_all_results('history');

        $total_pages = (int) ceil($total_rows / $limit);
        if ($total_pages < 1)
            $total_pages = 1;

        if ($page > $total_pages) {
            $page = $total_pages;
            $offset = ($page - 1) * $limit;
        }

        // ✅ 3) Fetch paginated rows
        $rows = $this->db->select('id, invest_id, type, amount, status, description, history_date')
            ->where('user_id', $userId)
            ->where('invest_id', $investId)
            ->where('hash_id', 'roi-made')
            ->order_by('history_date', 'ASC')
            ->limit($limit, $offset)
            ->get('history')
            ->result_array();

        echo json_encode([
            'status' => true,
            'investment' => [
                'id' => (int) $inv->id,
                'package' => $inv->package_name ?: $inv->package, // fallback
                // 'ref' => $inv->ref,
                // 'amount' => $inv->amount
            ],
            'rows' => $rows,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total_rows' => $total_rows,
                'total_pages' => $total_pages
            ]
        ]);
    }
    /**
     * AJAX: complete detail for one investment — all 7 tabs of the redesigned
     * Stakings modal, from real data (package info, ROI history, on-chain
     * transactions, ledger movements, timeline, documents, audit events).
     * POST invest_id. Read-only, ownership-checked. See docs/15.
     */
    public function staking_detail()
    {
        $userId = (int) $this->session->userdata('user_userid');
        if (!$userId) { echo json_encode(['status'=>false,'message'=>'Unauthorized']); return; }
        $investId = (int) $this->input->post('invest_id');

        $inv = $this->db->select('ui.*, pc.package_name, pc.roi AS pkg_roi, pc.days_duration, pc.period, pc.retrun_principle')
            ->from('user_investment ui')->join('package_config pc', 'pc.id = ui.package_id', 'left')
            ->where('ui.id', $investId)->where('ui.user_id', $userId)->get()->row_array();
        if (!$inv) { echo json_encode(['status'=>false,'message'=>'Investment not found']); return; }

        $ts = $this->db->select('explorer_url')->get_where('token_settings',['status'=>1])->row_array();
        $explorer = rtrim($ts['explorer_url'] ?? 'https://bscscan.com', '/');
        $today = new DateTimeImmutable('today');

        // ---- calculations ----
        $roiPct   = (float)$inv['pkg_roi'];
        $duration = (int)$inv['days_duration'];
        $amount   = (float)$inv['invest_amount'];
        $mature   = !empty($inv['mature_date']) ? new DateTimeImmutable(date('Y-m-d', strtotime($inv['mature_date']))) : null;
        $daysRemaining = $mature ? max(0, (int)$today->diff($mature)->days * ($mature >= $today ? 1 : 0)) : null;

        $roiRows = $this->db->select('id, amount, token_amount, description, history_date, hash_id')
            ->where('user_id',$userId)->where('invest_id',$investId)->where('hash_id','roi-made')
            ->order_by('history_date','ASC')->get('history')->result_array();
        $completedCycles = count($roiRows);
        $totalEarned = 0; foreach ($roiRows as $r) $totalEarned += (float)$r['amount'];
        $expectedFinal = $amount * ($roiPct/100) * $duration;               // daily-roi model
        $pending = max(0, $expectedFinal - $totalEarned);
        $remainingCycles = max(0, $duration - $completedCycles);

        $statusMap = ['0'=>'Pending','1'=>'Active','2'=>'Matured'];
        $status = $statusMap[(string)$inv['status']] ?? 'Active';
        if ((string)$inv['status'] === '2' && (int)($inv['recived_status'] ?? 0) === 1) $status = 'Completed';

        // ---- TAB 1: package info ----
        $tab1 = [
            'invest_id'=>(int)$inv['id'], 'package_name'=>$inv['package_name'],
            'stake_amount'=>$amount, 'plan_type'=>($inv['period'] ?: 'Daily'),
            'roi_structure'=>$roiPct.'% '.($inv['period'] ?: 'daily').' × '.$duration.' days',
            'purchase_date'=>$inv['created_date'] ?? $inv['starting_date'],
            'activation_date'=>$inv['starting_date'], 'maturity_date'=>$inv['mature_date'],
            'lock_period_days'=>$duration, 'days_remaining'=>$daysRemaining, 'status'=>$status,
            'wallet_used'=>'USDT', 'tx_hash'=>$inv['hash_id'] ?? null,
            'explorer_link'=>(!empty($inv['hash_id']) && strlen($inv['hash_id'])>40) ? ($explorer.'/tx/'.$inv['hash_id']) : null,
        ];

        // ---- TAB 2: ROI history (+ enrich from onchain when available) ----
        $tab2 = ['rows'=>[], 'totals'=>['total_earned'=>$totalEarned,'remaining'=>$pending,'expected_final'=>$expectedFinal]];
        $cyc = 0;
        foreach ($roiRows as $r) {
            $cyc++;
            $tab2['rows'][] = [
                'date'=>$r['history_date'], 'cycle'=>$cyc, 'roi_percent'=>$roiPct,
                'amount'=>(float)$r['amount'], 'wallet_credited'=>'earning',
                'tx_hash'=>null, 'chain_status'=>'internal', 'gas_fee'=>null, 'confirmations'=>null,
                'created_time'=>$r['history_date'],
            ];
        }

        // ---- TAB 3: on-chain transactions (user-level; package-linked where possible) ----
        $tab3 = $this->db->select('tx_hash, block_number, created_at, tx_type, wallet_type, amount, gas_used, gas_fee_total, status')
            ->where('user_id',$userId)->order_by('id','DESC')->limit(100)->get('onchain_transactions')->result_array();
        foreach ($tab3 as &$t) $t['explorer'] = !empty($t['tx_hash']) ? ($explorer.'/tx/'.$t['tx_hash']) : null;
        unset($t);

        // ---- TAB 4: ledger movements ----
        $tab4 = $this->db->select('created_at, wallet_type, credit, debit, balance_after, reference_type, reference_id, description, created_by')
            ->where('user_id',$userId)->order_by('id','DESC')->limit(100)->get('wallet_ledger')->result_array();

        // ---- TAB 5: timeline ----
        $tab5 = [];
        $tab5[] = ['event'=>'Purchased','date'=>$inv['created_date'] ?? $inv['starting_date'],'desc'=>'Stake purchased ('.number_format($amount,2).')'];
        if (!empty($inv['starting_date'])) $tab5[] = ['event'=>'Activated','date'=>$inv['starting_date'],'desc'=>'Investment activated'];
        $cyc=0; foreach ($roiRows as $r){ $cyc++; $tab5[]=['event'=>"ROI Cycle $cyc",'date'=>$r['history_date'],'desc'=>'ROI '.number_format((float)$r['amount'],4).' credited']; }
        if ($mature && $mature <= $today) $tab5[] = ['event'=>'Matured','date'=>$inv['mature_date'],'desc'=>'Reached maturity'];
        if ($status==='Completed') $tab5[] = ['event'=>'Completed','date'=>$inv['mature_date'],'desc'=>'Principal settled'];

        // ---- TAB 6: documents (endpoints; PDFs are a later phase) ----
        $tab6 = [
            ['name'=>'Purchase Receipt','available'=>true,'url'=>base_url('user/stakings/receipt/'.$investId)],
            ['name'=>'Investment Agreement','available'=>true,'url'=>base_url('user/stakings/agreement/'.$investId)],
            ['name'=>'ROI Schedule','available'=>true,'url'=>base_url('user/stakings/roi-schedule/'.$investId)],
            ['name'=>'Summary Report','available'=>true,'url'=>base_url('user/stakings/summary/'.$investId)],
            ['name'=>'Blockchain Receipt','available'=>!empty($tab1['explorer_link']),'url'=>$tab1['explorer_link']],
            ['name'=>'Tax Report (coming soon)','available'=>false,'url'=>null],
        ];

        // ---- TAB 7: audit log (immutable on-chain events for this user) ----
        $tab7 = $this->db->select('e.event_type, e.new_status, e.old_status, e.actor_type, e.actor_id, e.ip_address, e.rpc_endpoint, e.tx_hash, e.created_at')
            ->from('onchain_tx_events e')->join('onchain_transactions o','o.id = e.tx_id','inner')
            ->where('o.user_id',$userId)->order_by('e.id','DESC')->limit(100)->get()->result_array();

        echo json_encode([
            'status'=>true,
            'calc'=>['completed_cycles'=>$completedCycles,'remaining_cycles'=>$remainingCycles,
                     'total_roi_earned'=>$totalEarned,'pending_roi'=>$pending,'expected_final'=>$expectedFinal,
                     'days_remaining'=>$daysRemaining,'next_roi_date'=>$inv['run_date'] ?? null],
            'tab1_package'=>$tab1, 'tab2_roi'=>$tab2, 'tab3_transactions'=>$tab3,
            'tab4_ledger'=>$tab4, 'tab5_timeline'=>$tab5, 'tab6_documents'=>$tab6, 'tab7_audit'=>$tab7,
        ]);
    }

    /** Build the filtered/sorted portfolio query (shared by list + export). */
    private function _portfolioQuery($userId, $search, $fStatus, $sortCol, $dir)
    {
        $this->db->from('user_investment ui')->join('package_config pc', 'pc.id = ui.package_id', 'left')
                 ->where('ui.user_id', $userId);
        if ($search !== '') {
            $this->db->group_start()->like('pc.package_name', $search)->or_like('ui.id', $search)->group_end();
        }
        if ($fStatus !== null && $fStatus !== '') {
            $map = ['pending'=>'0','active'=>'1','matured'=>'2','completed'=>'2','cancelled'=>'3'];
            if (isset($map[strtolower($fStatus)])) $this->db->where('ui.status', $map[strtolower($fStatus)]);
        }
    }

    /** Compute the display row for one investment (all portfolio columns). */
    private function _portfolioRow($r)
    {
        $today  = new DateTimeImmutable('today');
        $amount = (float)$r['invest_amount']; $roiPct = (float)$r['pkg_roi']; $dur = (int)$r['days_duration'];
        $mature = !empty($r['mature_date']) ? new DateTimeImmutable(date('Y-m-d', strtotime($r['mature_date']))) : null;
        $daysRem = ($mature && $mature >= $today) ? (int)$today->diff($mature)->days : 0;
        $earned  = (float)$this->calcInvestmentEarned($r['id']);
        $expected = $amount * ($roiPct/100) * $dur;
        $statusMap = ['0'=>'Processing','1'=>'Active','2'=>'Matured','3'=>'Cancelled'];
        $st = $statusMap[(string)$r['status']] ?? 'Active';
        if ((string)$r['status'] === '0' && empty($r['starting_date'])) $st = 'Processing';
        if ((string)$r['status'] === '2' && (int)($r['recived_status'] ?? 0) === 1) $st = 'Completed';
        return [
            'invest_id'=>(int)$r['id'], 'package_name'=>$r['package_name'] ?: ('PKG-'.$r['package_id']),
            'stake_amount'=>$amount, 'plan_type'=>ucfirst($r['period'] ?: 'daily'), 'duration_days'=>$dur,
            'purchase_date'=>$r['created_date'] ?? $r['starting_date'], 'maturity_date'=>$r['mature_date'],
            'days_remaining'=>$daysRem, 'roi_percent'=>$roiPct, 'total_roi_earned'=>$earned,
            'pending_roi'=>max(0, $expected - $earned), 'next_roi_date'=>$r['run_date'] ?? null, 'status'=>$st,
            'purchase_state'=>$st,
        ];
    }

    /** AJAX: server-side "My Stakings" portfolio (search/filter/sort/paginate). */
    public function portfolio_list()
    {
        $userId = (int)$this->session->userdata('user_userid');
        if (!$userId) { echo json_encode(['status'=>false,'message'=>'Unauthorized']); return; }

        $search = trim((string)$this->input->post('search', true));
        $fStatus= $this->input->post('status', true);
        $sort   = $this->input->post('sort', true) ?: 'id';
        $dir    = strtoupper((string)$this->input->post('dir', true)) === 'ASC' ? 'ASC' : 'DESC';
        $page   = max(1, (int)$this->input->post('page'));
        $limit  = min(100, max(5, (int)$this->input->post('limit') ?: 20));
        $offset = ($page - 1) * $limit;
        $sortable = ['id'=>'ui.id','amount'=>'ui.invest_amount','purchase'=>'ui.created_date','maturity'=>'ui.mature_date','status'=>'ui.status'];
        $sortCol  = $sortable[$sort] ?? 'ui.id';

        $this->_portfolioQuery($userId, $search, $fStatus, $sortCol, $dir);
        $total = $this->db->count_all_results('', false);
        $rows = $this->db->select('ui.*, pc.package_name, pc.roi AS pkg_roi, pc.days_duration, pc.period')
            ->order_by($sortCol, $dir)->limit($limit, $offset)->get()->result_array();

        $out = array_map([$this, '_portfolioRow'], $rows);
        echo json_encode(['status'=>true,'rows'=>$out,'total'=>$total,'page'=>$page,'limit'=>$limit,'pages'=>(int)ceil($total/max(1,$limit))]);
    }

    /** Export the portfolio as CSV or Excel (?format=csv|excel). */
    public function portfolio_export()
    {
        $userId = (int)$this->session->userdata('user_userid');
        if (!$userId) { show_404(); return; }
        $format = strtolower((string)$this->input->get('format')) === 'excel' ? 'excel' : 'csv';
        $search = trim((string)$this->input->get('search'));
        $fStatus= $this->input->get('status');

        $this->_portfolioQuery($userId, $search, $fStatus, 'ui.id', 'DESC');
        $rows = $this->db->select('ui.*, pc.package_name, pc.roi AS pkg_roi, pc.days_duration, pc.period')
            ->order_by('ui.id', 'DESC')->get()->result_array();

        $head = ['Package ID','Package Name','Stake Amount','Plan Type','Duration (days)','Purchase Date',
                 'Maturity Date','Days Remaining','ROI %','Total ROI Earned','Pending ROI','Next ROI Date','Status'];
        $data = [];
        foreach ($rows as $r) {
            $x = $this->_portfolioRow($r);
            $data[] = [$x['invest_id'],$x['package_name'],$x['stake_amount'],$x['plan_type'],$x['duration_days'],
                       $x['purchase_date'],$x['maturity_date'],$x['days_remaining'],$x['roi_percent'],
                       $x['total_roi_earned'],$x['pending_roi'],$x['next_roi_date'],$x['status']];
        }
        $fname = 'my-stakings-'.date('Ymd_His');

        if ($format === 'excel') {
            // Lightweight Excel: an HTML table Excel opens natively (no library).
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="'.$fname.'.xls"');
            echo "<table border='1'><tr>";
            foreach ($head as $h) echo '<th>'.htmlspecialchars($h).'</th>';
            echo '</tr>';
            foreach ($data as $row) { echo '<tr>'; foreach ($row as $c) echo '<td>'.htmlspecialchars((string)$c).'</td>'; echo '</tr>'; }
            echo '</table>';
            return;
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$fname.'.csv"');
        $fh = fopen('php://output', 'w');
        fputcsv($fh, $head);
        foreach ($data as $row) fputcsv($fh, $row);
        fclose($fh);
    }

    /**
     * Convert package_config rows into the structure your UI expects.
     */
    private function getPackagesForView()
    {
        $rows = $this->db->query("SELECT * FROM package_config WHERE status='1' ORDER BY id ASC")->result();

        $out = [];
        foreach ($rows as $r) {
            // NOTE: adjust column names if your table differs
            $out[] = (object) [
                'id' => (int) $r->id,
                'name' => (string) $r->package_name,              // UI expects name
                'min' => (float) $r->minimum,                   // UI expects min
                'max' => (float) $r->maximum,                   // UI expects max
                'period' => !empty($r->roi_period) ? $r->roi_period : 'Daily', // if you have roi_period column, else Daily
                'roi_percent' => (float) $r->roi,                       // UI expects roi_percent
                'duration_days' => (int) $r->days_duration,               // UI expects duration_days
                'bv' => !empty($r->bv) ? (int) $r->bv : 0,      // optional
                'status' => (int) $r->status,
                'note' => !empty($r->note) ? $r->note : '',     // optional (if you have)
            ];
        }
        return $out;
    }

    /**
     * BMAN staking packages with their ROI matrix, mapped for the view.
     * Each package carries: stake_amount, bonus_percent, group_ceiling and a
     * `roi` map keyed 'fixed_2','fixed_3','fixed_5','regular_2', … → % + basis.
     */
    private function getStakingPackagesForView()
    {
        if (!$this->db->table_exists('staking_packages')) return [];
        $this->load->model('Staking_model');
        $grid = $this->Staking_model->roiGrid();               // packages + roi cells
        // active packages only, ordered by stake amount
        return array_values(array_filter($grid, function ($p) {
            return (int) ($p['is_active'] ?? 0) === 1;
        }));
    }

    /**
     * Active staking plans (Fixed / Regular / Combo) with their term durations,
     * used to explain how ROI is credited and withdrawn.
     */
    private function getStakingPlansForView()
    {
        if (!$this->db->table_exists('staking_plans')) return [];
        $this->load->model('Staking_model');
        return $this->Staking_model->plans(true);
    }

    /**
     * Investments list for UI.
     * Earned + next payout are computed safely (no schema breaking).
     */
    private function getUserInvestmentsForView($user_id)
    {
        $q = $this->db->query("
                SELECT ui.*, pc.package_name
                FROM user_investment ui
                LEFT JOIN package_config pc ON pc.id = ui.package_id
                WHERE ui.user_id = ?
                ORDER BY ui.id DESC
            ", [$user_id]);

        $rows = $q->result();

        $out = [];

        foreach ($rows as $r) {
            $earned = $this->calcInvestmentEarned($r->id);
            $nextPayout = $this->calcNextPayoutDate($r);

            $out[] = (object) [
                'ref' => 'INV-' . (int) $r->id,
                'package' => !empty($r->package_name) ? $r->package_name : ('PKG-' . (int) $r->package_id),
                'amount' => (float) $r->invest_amount,
                'roi_percent' => (float) $r->profit,
                'period' => 'Daily', // change if you store payout period in DB
                'duration_days' => (int) $r->days_count,
                'start_date' => !empty($r->starting_date) ? date('Y-m-d', strtotime($r->starting_date)) : '—',
                'end_date' => !empty($r->ending_date) ? date('Y-m-d', strtotime($r->ending_date)) : '—',
                'earned' => (float) $earned,
                'next_payout' => $nextPayout,
                'status' => ((string) $r->status === '1') ? 'ACTIVE' : 'COMPLETED',
                'inverst_id' => (int) $r->id,
            ];
        }
        return $out;
    }

    /**
     * ROI history (tries best tables; falls back without breaking).
     */
    private function getUserRoiHistoryForView($user_id)
    {
        // Try "roi_history" table (if you have)
        if ($this->db->table_exists('roi_history')) {
            $q = $this->db->query("
                    SELECT date, ref, title, amount, status
                    FROM roi_history
                    WHERE user_id=?
                    ORDER BY date DESC
                    LIMIT 200
                ", [$user_id]);
            return $q->result();
        }

        // Fallback: use "history" table entries that look like ROI
        // (your deposit entry uses description "Investment Successfully", ROI credits usually contain "ROI")
        $q = $this->db->query("
                SELECT
                    DATE(history_date) as date,
                    CONCAT('INV-', invest_id) as ref,
                    description as title,
                    amount,
                    CASE WHEN status='1' THEN 'APPROVED' ELSE 'PENDING' END as status
                FROM history
                WHERE user_id=? AND (description LIKE '%ROI%' OR description LIKE '%profit%' OR description LIKE '%Daily%')
                ORDER BY history_date DESC
                LIMIT 200
            ", [$user_id]);

        return $q->result();
    }

    /**
     * Recent staking purchase activity for the portfolio header.
     * Pulls the immediate purchase record + ROI credits so users see the
     * progression without opening the modal.
     */
    private function getRecentStakingActivityForView($user_id)
    {
        if (!$this->db->table_exists('staking_swap_orders')) {
            return [];
        }

        // Fetch from staking_swap_orders (multi-step staking purchases)
        $orders = $this->db->select('
            id, ref, status, usdt_amount, bman_amount, bonus_bman,
            coin_distribution_option, created_at, updated_at,
            gas_tx_hash, usdt_tx_hash, bonus_tx_hash,
            bman_exchange_tx_hash, bman_earning_tx_hash, bman_staking_tx_hash, bman_bonus_tx_hash,
            gas_cron_status, usdt_cron_status, bonus_cron_status,
            bman_exchange_cron_status, bman_earning_cron_status, bman_staking_cron_status, bman_bonus_cron_status,
            error, package_id, plan_code, duration_years
        ')
        ->where('user_id', (int)$user_id)
        ->order_by('created_at', 'DESC')
        ->limit(50)
        ->get('staking_swap_orders')
        ->result_array();

        // Format for display (convert to objects matching view expectations)
        $result = [];
        foreach ($orders as $o) {
            $result[] = (object)[
                'id' => $o['id'],
                'ref' => $o['ref'],
                'history_date' => $o['created_at'],
                'type' => 'staking_purchase',
                'amount' => (float)$o['usdt_amount'],
                'token_amount' => (float)$o['bman_amount'],
                'status' => $o['status'],
                'description' => 'Staking purchase ' . number_format((float)$o['bman_amount'], 0) . ' BMAN (' .
                                ($o['plan_code'] ? $o['plan_code'] : 'fixed') . '/' .
                                ($o['duration_years'] ? $o['duration_years'] : 1) . 'y) — ' . ucfirst(str_replace('_', ' ', $o['status'])),
                'hash_id' => $o['gas_tx_hash'],
                // Additional fields for popup details
                'order_id' => $o['id'],
                'bonus_bman' => (float)$o['bonus_bman'],
                'coin_distribution_option' => $o['coin_distribution_option'],
                'gas_tx_hash' => $o['gas_tx_hash'],
                'usdt_tx_hash' => $o['usdt_tx_hash'],
                'bonus_tx_hash' => $o['bonus_tx_hash'],
                'bman_exchange_tx_hash' => $o['bman_exchange_tx_hash'],
                'bman_earning_tx_hash' => $o['bman_earning_tx_hash'],
                'bman_staking_tx_hash' => $o['bman_staking_tx_hash'],
                'bman_bonus_tx_hash' => $o['bman_bonus_tx_hash'],
                'gas_cron_status' => $o['gas_cron_status'],
                'usdt_cron_status' => $o['usdt_cron_status'],
                'bonus_cron_status' => $o['bonus_cron_status'],
                'bman_exchange_cron_status' => $o['bman_exchange_cron_status'],
                'bman_earning_cron_status' => $o['bman_earning_cron_status'],
                'bman_staking_cron_status' => $o['bman_staking_cron_status'],
                'bman_bonus_cron_status' => $o['bman_bonus_cron_status'],
                'error' => $o['error'],
            ];
        }

        return $result;
    }

    /**
     * AJAX: Get on-chain swap order details with transaction status
     */
    public function swap_order_details()
    {
        $userId = (int)$this->session->userdata('user_userid');
        if (!$userId) { echo json_encode(['status'=>false,'message'=>'Unauthorized']); return; }

        $orderId = (int)$this->input->post('order_id');
        if (!$orderId) { echo json_encode(['status'=>false,'message'=>'Invalid order ID']); return; }

        $o = $this->db->select('*')
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->get('staking_swap_orders')
            ->row_array();

        if (!$o) { echo json_encode(['status'=>false,'message'=>'Order not found']); return; }

        // Get ROI rate from staking_packages
        $roiRate = 0;
        if (!empty($o['package_id'])) {
            $pkg = $this->db->select('roi')->get_where('staking_packages', ['id'=>$o['package_id']])->row_array();
            if ($pkg && !empty($pkg['roi'])) {
                $roi = json_decode($pkg['roi'], true);
                $planCode = $o['plan_code'] ?? 'fixed';
                $duration = (int)$o['duration_years'] ?? 1;
                $roiKey = $planCode.'_'.$duration;
                if (!empty($roi[$roiKey])) {
                    $roiRate = (float)($roi[$roiKey]['roi_percent'] ?? 0);
                }
            }
        }

        // Get explorer URL from config
        $ts = $this->db->select('explorer_url')->get_where('token_settings',['status'=>1])->row_array();
        $explorer = rtrim($ts['explorer_url'] ?? 'https://bscscan.com', '/');

        // Status indicators
        $statusMap = [
            'pending_gas_fee' => ['icon'=>'hourglass-start','label'=>'Waiting for Gas Fee','color'=>'warning'],
            'pending_usdt' => ['icon'=>'hourglass-half','label'=>'Waiting for USDT','color'=>'warning'],
            'pending_bman' => ['icon'=>'hourglass-end','label'=>'Processing BMAN','color'=>'info'],
            'swap_completed' => ['icon'=>'check-circle','label'=>'Completed','color'=>'success'],
            'failed_gas' => ['icon'=>'x-circle','label'=>'Gas Fee Failed','color'=>'danger'],
            'failed_usdt' => ['icon'=>'x-circle','label'=>'USDT Failed','color'=>'danger'],
        ];

        $status = $statusMap[$o['status']] ?? ['icon'=>'question-mark','label'=>$o['status'],'color'=>'secondary'];

        echo json_encode([
            'status' => true,
            'data' => [
                'order_id' => (int)$o['id'],
                'ref' => $o['ref'],
                'created_at' => $o['created_at'],
                'updated_at' => $o['updated_at'],
                'current_status' => $o['status'],
                'status_info' => $status,
                'amounts' => [
                    'usdt' => (float)$o['usdt_amount'],
                    'bman' => (float)$o['bman_amount'],
                    'bonus_bman' => (float)$o['bonus_bman'],
                ],
                'distribution' => [
                    'option' => (int)$o['coin_distribution_option'],
                    'exchange_bman' => (float)($o['bman_exchange_amount'] ?? 0),
                    'earning_bman' => (float)($o['bman_earning_amount'] ?? 0),
                    'staking_bman' => (float)($o['bman_staking_amount'] ?? 0),
                    'bonus_bman' => (float)$o['bonus_bman'],
                ],
                'plan' => [
                    'code' => $o['plan_code'] ?? 'fixed',
                    'duration_years' => (int)$o['duration_years'] ?? 1,
                    'package_id' => (int)$o['package_id'],
                ],
                'roi_rate' => (float)$roiRate,
                'cron_status' => [
                    'gas' => (int)$o['gas_cron_status'],
                    'usdt' => (int)$o['usdt_cron_status'],
                    'bonus' => (int)$o['bonus_cron_status'],
                    'bman_exchange' => (int)$o['bman_exchange_cron_status'],
                    'bman_earning' => (int)$o['bman_earning_cron_status'],
                    'bman_staking' => (int)$o['bman_staking_cron_status'],
                    'bman_bonus' => (int)$o['bman_bonus_cron_status'],
                ],
                'transactions' => [
                    'gas' => [
                        'tx_hash' => $o['gas_tx_hash'],
                        'status' => $o['gas_cron_status'] == 1 ? 'confirmed' : 'pending',
                        'explorer' => !empty($o['gas_tx_hash']) && strlen($o['gas_tx_hash']) > 20 ? $explorer.'/tx/'.$o['gas_tx_hash'] : null,
                    ],
                    'usdt' => [
                        'tx_hash' => $o['usdt_tx_hash'],
                        'status' => $o['usdt_cron_status'] == 1 ? 'confirmed' : 'pending',
                        'explorer' => !empty($o['usdt_tx_hash']) && strlen($o['usdt_tx_hash']) > 20 ? $explorer.'/tx/'.$o['usdt_tx_hash'] : null,
                    ],
                    'bonus' => [
                        'tx_hash' => $o['bonus_tx_hash'],
                        'status' => $o['bonus_cron_status'] == 1 ? 'confirmed' : 'pending',
                        'explorer' => !empty($o['bonus_tx_hash']) && strlen($o['bonus_tx_hash']) > 20 ? $explorer.'/tx/'.$o['bonus_tx_hash'] : null,
                    ],
                    'bman_exchange' => [
                        'tx_hash' => $o['bman_exchange_tx_hash'],
                        'status' => $o['bman_exchange_cron_status'] == 1 ? 'confirmed' : 'pending',
                        'explorer' => !empty($o['bman_exchange_tx_hash']) && strlen($o['bman_exchange_tx_hash']) > 20 ? $explorer.'/tx/'.$o['bman_exchange_tx_hash'] : null,
                    ],
                    'bman_earning' => [
                        'tx_hash' => $o['bman_earning_tx_hash'],
                        'status' => $o['bman_earning_cron_status'] == 1 ? 'confirmed' : 'pending',
                        'explorer' => !empty($o['bman_earning_tx_hash']) && strlen($o['bman_earning_tx_hash']) > 20 ? $explorer.'/tx/'.$o['bman_earning_tx_hash'] : null,
                    ],
                    'bman_staking' => [
                        'tx_hash' => $o['bman_staking_tx_hash'],
                        'status' => $o['bman_staking_cron_status'] == 1 ? 'confirmed' : 'pending',
                        'explorer' => !empty($o['bman_staking_tx_hash']) && strlen($o['bman_staking_tx_hash']) > 20 ? $explorer.'/tx/'.$o['bman_staking_tx_hash'] : null,
                    ],
                    'bman_bonus' => [
                        'tx_hash' => $o['bman_bonus_tx_hash'],
                        'status' => $o['bman_bonus_cron_status'] == 1 ? 'confirmed' : 'pending',
                        'explorer' => !empty($o['bman_bonus_tx_hash']) && strlen($o['bman_bonus_tx_hash']) > 20 ? $explorer.'/tx/'.$o['bman_bonus_tx_hash'] : null,
                    ],
                ],
                'error' => $o['error'],
            ],
        ]);
    }

    /**
     * Earned ROI calculation (safe).
     * If you maintain ROI credits somewhere else, update here only.
     */
    private function calcInvestmentEarned($invest_id)
    {
        // Preferred: roi_credits table
        if ($this->db->table_exists('roi_credits')) {
            $row = $this->db->query("
                    SELECT COALESCE(SUM(amount),0) AS total
                    FROM roi_credits
                    WHERE invest_id=? AND status='1'
                ", [(int) $invest_id])->row();
            return (float) ($row->total ?? 0);
        }

        // Fallback: sum from history where description includes ROI
        $row = $this->db->query("
                SELECT COALESCE(SUM(amount),0) AS total
                FROM history
                WHERE invest_id=? AND status='1' AND description LIKE '%ROI%'
            ", [(int) $invest_id])->row();
        return (float) ($row->total ?? 0);
    }

    /**
     * Next payout date (simple version).
     * If you have a payout schedule, compute it here.
     */
    private function calcNextPayoutDate($invRow)
    {
        // If investment is inactive/completed
        if ((string) $invRow->status !== '1')
            return '—';

        // Use run_date if present
        if (!empty($invRow->run_date)) {
            return date('Y-m-d', strtotime($invRow->run_date));
        }

        // Else: tomorrow from today
        return date('Y-m-d', strtotime('+1 day'));
    }

    /*
    |--------------------------------------------------------------------------
    | Deposit Amount Validation
    |--------------------------------------------------------------------------
    */
    public function makeinvestment_post()
    {

        if ($this->input->post()) {

            $user_id = $this->session->userdata('user_userid');
            $selected_package = $this->input->post('package_id');
            $bonus_amount = $this->input->post('lending_amount');
            $payment_option = $this->input->post('payment_option');

            $invest_date = "";
            $token_info = token_info();
            $currency_info = currency_info();
            $user_balance = site_wallet_balance_without_format($user_id);



            if ($selected_package > 0) {

                // to check package id correct or not (package_config table)
                $package_query = $this->db->query("SELECT * FROM package_config WHERE id = ? AND status = '1'", [$selected_package]);
                $package = $package_query->row();

                if (!$package) {
                    echo json_encode([
                        'status' => false,
                        'message' => "Invalid package.."
                    ]);
                    exit;
                }

                if ($bonus_amount > 0) {

                    $package_id = $this->input->post('package_id');

                    $query = $this->db->query("SELECT * FROM package_config WHERE id = ? AND status = '1'", [$package_id]);
                    $package = $query->row();

                    if (!$package) {
                        echo json_encode([
                            'status' => false,
                            'message' => "Invalid package..."
                        ]);
                        exit;
                    }

                    $deposit_temp_data = [
                        'user_id' => $user_id,
                        'amount' => $bonus_amount,
                        'payment_method' => $payment_option,
                        'status' => 'pending',
                        'package_id' => $package_id,
                        'created_at' => date('Y-m-d H:i:s'),
                    ];

                    $this->db->insert('deposits', $deposit_temp_data);
                    $deposit_id = $this->db->insert_id();


                    if ($payment_option == "paypal") {
                        $paypal_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
                        $paypal_email = "nexman@business.example.com";

                        $return_url = base_url("user/lending/payment_success?method=paypal&did=$deposit_id");
                        $cancel_url = base_url("user/lending/payment_failed?did=$deposit_id");

                        $form_html = '
                        <form id="paypal_form" action="' . $paypal_url . '" method="post">
                            <input type="hidden" name="business" value="' . $paypal_email . '">
                            <input type="hidden" name="cmd" value="_xclick">
                            <input type="hidden" name="item_name" value="Lending Deposit #' . $deposit_id . '">
                            <input type="hidden" name="amount" value="' . $bonus_amount . '">
                            <input type="hidden" name="currency_code" value="USD">
                            <input type="hidden" name="return" value="' . $return_url . '">
                            <input type="hidden" name="cancel_return" value="' . $cancel_url . '">
                        </form>
                        <script>document.getElementById("paypal_form").submit();</script>
                    ';


                        echo json_encode([
                            'status' => true,
                            'paypal_html' => $form_html,
                            'message' => "Redirecting to PayPal..."
                        ]);
                        exit;
                    }


                    if ($payment_option == "stripe") {
                        require_once(APPPATH . 'third_party/stripe/vendor/autoload.php');
                        \Stripe\Stripe::setApiKey('');

                        $session = \Stripe\Checkout\Session::create([
                            'payment_method_types' => ['card'],
                            'line_items' => [
                                [
                                    'price_data' => [
                                        'currency' => 'usd',
                                        'product_data' => ['name' => 'Lending Deposit #' . $deposit_id],
                                        'unit_amount' => $bonus_amount * 100,
                                    ],
                                    'quantity' => 1,
                                ]
                            ],
                            'mode' => 'payment',
                            'success_url' => base_url("user/lending/payment_success?method=stripe&did=$deposit_id"),
                            'cancel_url' => base_url("user/lending/payment_failed?did=$deposit_id"),
                        ]);

                        echo json_encode([
                            'status' => true,
                            'redirect_url' => $session->url,
                            'message' => "Redirecting to Stripe..."
                        ]);
                        exit;
                    }


                    if ($payment_option == "wallet") {

                        if ($user_balance >= $bonus_amount) {


                            if ($bonus_amount >= $package->minimum && $bonus_amount <= $package->maximum) {

                                $days_count = $package->days_duration;

                                if ($invest_date == "") {
                                    $rundate = date('Y-m-d', strtotime(' +1 day'));
                                    $maturedate = date('Y-m-d  H:i:s', strtotime(' +' . $days_count . ' day'));
                                    $ending_date = date('Y-m-d H:i:s', strtotime(' +' . $days_count . ' day'));
                                    $invest_date = date('Y-m-d H:i:s');
                                } else {
                                    $rundate = date('Y-m-d', strtotime($invest_date . ' +1 day'));
                                    $maturedate = date('Y-m-d H:i:s', strtotime($invest_date . ' +' . $days_count . ' day'));
                                    $ending_date = date('Y-m-d H:i:s', strtotime($invest_date . ' +' . $days_count . ' day'));
                                }


                                $csq_price = '0';
                                $csq_deposit = '0';
                                $earn_type = '2';

                                $csq_price = $token_info->currency_value;
                                $csq_deposit = $bonus_amount * $token_info->currency_value;

                                if ($package->roi_made_by == "token") {
                                    $earn_type = '1';
                                } else {
                                    $earn_type = '1';
                                }

                                $insert_data = array(
                                    "user_id" => $user_id,
                                    "invest_amount" => $bonus_amount,
                                    "invest_network" => "BSC",
                                    "status" => '1',
                                    "created_date" => date('Y-m-d H:i:s', strtotime($invest_date)),
                                    "days_count" => $days_count,
                                    "profit" => $package->roi,
                                    "hash_id" => "user-wallet",
                                    "run_date" => date('Y-m-d', strtotime($rundate)),
                                    "starting_date" => date('Y-m-d  H:i:s', strtotime($invest_date)),
                                    "ending_date" => $ending_date,
                                    "mature_date" => $maturedate,
                                    "type" => "mining",
                                    "req_method" => "user-wallet",
                                    "approve_status" => '1',
                                    "package_id" => $package_id,
                                    "csq_price" => $csq_price,
                                    "csq_deposit" => $csq_deposit,
                                    "earn_by" => $package->roi_made_by,
                                    "currency_id" => $currency_info->id,
                                    "token_id" => $token_info->id,
                                );

                                $insert = $this->db->insert("user_investment", $insert_data);
                                $invest_id = $this->db->insert_id();

                                if ($invest_id) {

                                    $deposit_data = array(
                                        "user_id" => $user_id,
                                        "amount" => $bonus_amount,
                                        "type" => "mining",
                                        "history_date" => date('Y-m-d H:i:s', strtotime($invest_date)),
                                        "date" => date('Y-m-d H:i:s', strtotime($invest_date)),
                                        "status" => '1',
                                        "hash_id" => "user-wallet",
                                        "invest_id" => $invest_id,
                                        "token_amount" => $csq_deposit,
                                        "description" => "Investment Successfully ( " . $package->package_name . " )",
                                        "coin_id" => $currency_info->id,
                                        "token_id" => $token_info->id,
                                    );
                                    $insert = $this->db->insert("history", $deposit_data);

                                    // $this->load->model('settings/Commission_model');
                                    // $direct_commission_percentage = $this->Commission_model->direct_commission_send($user_id,$bonus_amount,$invest_id,$csq_deposit,$earn_type,$invest_date);

                                    $this->load->model('finance/CommissionEngine_model', 'CommissionEngine');

                                    // Update user package_id right after investment
                                    $this->db->where('id', (int) $user_id)
                                        ->update('users', ['package_id' => (int) $package_id]);

                                    // Process commissions
                                    $this->CommissionEngine->process_investment([
                                        'user_id' => (int) $user_id,
                                        'package_id' => (int) $package_id,
                                        'invest_id' => (int) $invest_id,
                                        'amount' => (float) $bonus_amount,
                                        'csq_price' => (float) $csq_price,
                                        'csq_deposit' => (float) $csq_deposit,
                                        'earn_type' => (string) $earn_type, // '1'=currency, '2'=token
                                        'invest_date' => date('Y-m-d H:i:s', strtotime($invest_date)),
                                        'note' => "Investment Commissions (" . $package->package_name . ")"
                                    ]);


                                    echo json_encode([
                                        'status' => TRUE,
                                        'message' => "Package Investment Successfully"
                                    ]);
                                    exit();

                                } else {

                                    echo json_encode([
                                        'status' => false,
                                        'message' => "investment is faild."
                                    ]);
                                    exit;
                                }

                            } else {

                                $minimum_amount = currency_format($package->minimum);
                                $maximum_amount = currency_format($package->maximum);
                                echo json_encode([
                                    'status' => false,
                                    'message' => "Amount must be between {$minimum_amount} and {$maximum_amount}."
                                ]);
                                exit;

                            }

                        } else {
                            $minimum_amount = currency_format($package->minimum);
                            echo json_encode([
                                'status' => false,
                                'message' => "Your Account Balance is low need to {$minimum_amount}."
                            ]);
                            exit;

                        }

                    }


                } else {

                    echo json_encode([
                        'status' => true,
                        'message' => "invalid amount."
                    ]);
                    exit();
                }

            } else {

                echo json_encode([
                    'status' => true,
                    'message' => "invalid package."
                ]);
                exit();
            }


        } else {

            echo json_encode([
                'status' => true,
                'message' => "invalid request."
            ]);
            exit();
        }

    }

    public function payment_success()
    {

        $method = $this->input->get('method');
        $deposit_id = $this->input->get('did');

        $deposit = $this->db->get_where('deposits', ['id' => $deposit_id])->row();

        if (!$deposit || $deposit->status != 'pending') {
            redirect(base_url("user/lending"));
        }

        $user_id = $deposit->user_id;

        $package_id = $deposit->package_id;
        $query = $this->db->query("SELECT * FROM package_config WHERE id = ? AND status = '1'", [$package_id]);
        $package = $query->row();

        $days_count = $package->days_duration;
        $invest_date = "";

        if ($invest_date == "") {
            $rundate = date('Y-m-d', strtotime(' +1 day'));
            $maturedate = date('Y-m-d  H:i:s', strtotime(' +' . $days_count . ' day'));
            $ending_date = date('Y-m-d H:i:s', strtotime(' +' . $days_count . ' day'));
            $invest_date = date('Y-m-d H:i:s');
        } else {
            $rundate = date('Y-m-d', strtotime($invest_date . ' +1 day'));
            $maturedate = date('Y-m-d H:i:s', strtotime($invest_date . ' +' . $days_count . ' day'));
            $ending_date = date('Y-m-d H:i:s', strtotime($invest_date . ' +' . $days_count . ' day'));
        }

        $csq_price = '0';
        $csq_deposit = '0';
        $earn_type = '1';
        $token_info = token_info();
        $currency_info = currency_info();
        $csq_price = $token_info->currency_value;
        $csq_deposit = $deposit->amount * $token_info->currency_value;

        // 1. Show thank you page
        $insert_data = array(
            "user_id" => $deposit->user_id,
            "invest_amount" => $deposit->amount,
            "invest_network" => $method,
            "status" => '1',
            "created_date" => date('Y-m-d H:i:s', strtotime($invest_date)),
            "days_count" => $days_count,
            "profit" => $package->roi,
            "hash_id" => "user-wallet",
            "run_date" => date('Y-m-d', strtotime($rundate)),
            "starting_date" => date('Y-m-d  H:i:s', strtotime($invest_date)),
            "ending_date" => $ending_date,
            "mature_date" => $maturedate,
            "type" => "mining",
            "req_method" => "user-wallet",
            "approve_status" => '1',
            "package_id" => $package_id,
            "csq_price" => $csq_price,
            "csq_deposit" => $csq_deposit,
            "earn_by" => $package->roi_made_by,
            "currency_id" => $currency_info->id,
            "token_id" => $token_info->id,
        );
        $insert = $this->db->insert("user_investment", $insert_data);
        $invest_id = $this->db->insert_id();

        // 2. Show thank you page of history
        $deposit_data = array(
            "user_id" => $deposit->user_id,
            "amount" => $deposit->amount,
            "type" => "mining",
            "history_date" => date('Y-m-d H:i:s', strtotime($invest_date)),
            "date" => date('Y-m-d H:i:s', strtotime($invest_date)),
            "status" => '1',
            "hash_id" => "user-wallet",
            "invest_id" => $invest_id,
            "token_amount" => $csq_deposit,
            "description" => "Investment Successfully ( " . $package->package_name . " )",
            "coin_id" => $currency_info->id,
            "token_id" => $token_info->id,
        );
        $insert = $this->db->insert("history", $deposit_data);
        $this->db->where('id', $deposit_id)->update('deposits', ['status' => 'completed']);


        $this->load->model('finance/CommissionEngine_model', 'CommissionEngine');

        // Update user package_id right after investment
        $this->db->where('id', (int) $user_id)
            ->update('users', ['package_id' => (int) $package_id]);

        // Process commissions
        $this->CommissionEngine->process_investment([
            'user_id' => (int) $user_id,
            'package_id' => (int) $package_id,
            'invest_id' => (int) $invest_id,
            'amount' => (float) $deposit->amount,
            'csq_price' => (float) $csq_price,
            'csq_deposit' => (float) $csq_deposit,
            'earn_type' => (string) $earn_type, // '1'=currency, '2'=token
            'invest_date' => date('Y-m-d H:i:s', strtotime($invest_date)),
            'note' => "Investment Commissions (" . $package->package_name . ")"
        ]);

        // 3. Show thank you page
        $userid = $this->session->userdata('user_userid');
        $this->data['user_id'] = $userid;
        $this->data['title'] = "Make Lending";
        $this->data['card_title'] = "Add Lending";

        $this->data['users'] = $this->db->query("SELECT * FROM users where status = '1' ")->result();
        $this->data['package'] = $this->db->query("SELECT * FROM package_config WHERE status = '1'")->result();

        $this->data['action'] = base_url() . "user/make-investment-post";
        $this->data['redirect_url'] = base_url() . "user/lending";

        $this->data['balance'] = site_wallet_balance($userid);
        // echo "<pre>";
        // print_r($this->data);
        // echo "</pre>";
        // exit;
        // Investments table (dynamic)
        $this->data['investments'] = $this->getUserInvestmentsForView($userid);

        _dbg('lending_managment_controller_data', $this->data);
        $this->load->view('user/wallet/lending_managment', $this->data);
    }


    /*
    |--------------------------------------------------------------------------
    | Detact Page Index
    |--------------------------------------------------------------------------
    */
    public function detact_wallet()
    {

        $this->data['title'] = "Detact Wallet";
        $this->data['card_title'] = "Detact balance to user wallet";
        $this->data['users'] = $this->db->query("SELECT * FROM users where status = '1' ")->result();
        $currency = currency_info()->coin_name;
        $this->data['default_currency'] = $currency;
        $token = token_info()->coin_name;
        $this->data['default_token'] = $token;
        $this->data['action'] = base_url() . "detact-wallet-post";
        $this->data['redirect_url'] = base_url() . "detect-wallet";
        $this->load->view('admin/wallet/wallet_management', $this->data);

    }
    /*
    |--------------------------------------------------------------------------
    | Wallet Detact Post 
    |--------------------------------------------------------------------------
    */
    public function wallet_detact_post()
    {

        if ($this->input->post()) {

            $selected_member = $this->input->post('selected_members', true);
            $selected_payment = $this->input->post('selected_payment', true);
            $notes_by_user = $this->input->post('notes_by_user', true);
            $amount = $this->input->post('bonus_amount', true);

            if ($selected_member <= 0 && $amount <= 0) {

                $response = array(
                    'status' => false,
                    'message' => "Invalide Request."
                );

            } else {

                if ($selected_payment == "currency") {
                    $this->detact_currency($selected_member, $amount, $notes_by_user);
                } else {
                    $this->detact_token($selected_member, $amount, $notes_by_user);
                }

            }

        } else {
            $response = array(
                'status' => false,
                'message' => "Invalide Request."
            );
        }

    }
    /*
    |--------------------------------------------------------------------------
    | Wallet Add Post 
    |--------------------------------------------------------------------------
    */
    public function wallet_add_post()
    {

        if ($this->input->post()) {

            $selected_member = $this->input->post('selected_members', true);
            $selected_payment = $this->input->post('selected_payment', true);
            $notes_by_user = $this->input->post('notes_by_user', true);
            $amount = $this->input->post('bonus_amount', true);

            if ($selected_member <= 0 && $amount <= 0) {

                $response = array(
                    'status' => false,
                    'message' => "Invalide Request."
                );

            } else {

                if ($selected_payment == "currency") {
                    $this->add_currency($selected_member, $amount, $notes_by_user);
                } else {
                    $this->add_token($selected_member, $amount, $notes_by_user);
                }

            }

        } else {
            $response = array(
                'status' => false,
                'message' => "Invalide Request."
            );
        }

    }
    /*
    |--------------------------------------------------------------------------
    | Add User Wallet Currency balance
    |--------------------------------------------------------------------------
    */
    private function add_currency($userid, $amount, $notes)
    {

        if ($userid > 0 && $amount > 0) {

            $token_info = token_info();
            $currency_info = currency_info();
            $token_amount = $amount * $token_info->currency_value;

            $deposit_data = array(
                "user_id" => $userid,
                "amount" => $amount,
                "type" => 'bonus',
                "date" => date('Y-m-d H:i:s'),
                "history_date" => date('Y-m-d H:i:s'),
                "status" => '1',
                'coin_type' => '1',
                "invest_id" => "",
                'hash_id' => 'admin bonus',
                "description" => $notes,
                "token_amount" => $token_amount,
                "coin_id" => $currency_info->id,
                "token_id" => $token_info->id,
            );

            $insert = $this->db->insert("history", $deposit_data);

            $response = array(
                'status' => true,
                'message' => "Bonus Added Successfully !!"
            );

        } else {

            $response = array(
                'status' => false,
                'message' => "Invalide Request."
            );

        }

        echo json_encode($response);

    }
    /*
   |--------------------------------------------------------------------------
   | Add User Wallet Token  balance
   |--------------------------------------------------------------------------
   */
    private function add_token($userid, $amount, $notes)
    {

        if ($userid > 0 && $amount > 0) {

            $token_info = token_info();
            $currency_info = currency_info();
            if ($amount >= $token_info->currency_value) {
                $site_balance = $amount / $token_info->currency_value;
            } else {
                $site_balance = '0';
            }


            $deposit_data = array(
                "user_id" => $userid,
                "amount" => $site_balance ? $site_balance : '0',
                "type" => 'bonus',
                "date" => date('Y-m-d H:i:s'),
                "history_date" => date('Y-m-d H:i:s'),
                "status" => '1',
                'coin_type' => '2',
                "invest_id" => "",
                'hash_id' => 'admin bonus',
                "description" => $notes,
                "token_amount" => $amount,
                "coin_id" => $currency_info->id,
                "token_id" => $token_info->id,
            );

            $insert = $this->db->insert("history", $deposit_data);

            $response = array(
                'status' => true,
                'message' => "Bonus Added Successfully !!"
            );

        } else {

            $response = array(
                'status' => false,
                'message' => "Invalide Request."
            );

        }

        echo json_encode($response);

    }

    /*
       |--------------------------------------------------------------------------
       | Detact User Wallet Currency balance
       |--------------------------------------------------------------------------
       */
    private function detact_currency($userid, $amount, $notes)
    {

        if ($userid > 0 && $amount > 0) {

            $token_info = token_info();
            $currency_info = currency_info();
            $token_amount = $amount * $token_info->currency_value;

            $deposit_data = array(
                "user_id" => $userid,
                "amount" => $amount,
                "type" => 'site_withdraw',
                "date" => date('Y-m-d H:i:s'),
                "history_date" => date('Y-m-d H:i:s'),
                "status" => '1',
                'coin_type' => '1',
                "invest_id" => "",
                'hash_id' => 'admin withdraw',
                "token_amount" => $token_amount,
                "description" => $notes,
                "coin_id" => $currency_info->id,
                "token_id" => $token_info->id,
            );

            $insert = $this->db->insert("history", $deposit_data);

            $response = array(
                'status' => true,
                'message' => "Wallet detacted successfully !!"
            );

        } else {

            $response = array(
                'status' => false,
                'message' => "Invalide Request."
            );

        }

        echo json_encode($response);

    }
    /*
   |--------------------------------------------------------------------------
   | Detact User Wallet Token  balance
   |--------------------------------------------------------------------------
   */
    private function detact_token($userid, $amount, $notes)
    {

        if ($userid > 0 && $amount > 0) {

            $token_info = token_info();
            $currency_info = currency_info();

            if ($amount >= $token_info->currency_value) {
                $site_amount = $amount / $token_info->currency_value;
            } else {
                $site_amount = '0';
            }


            $deposit_data = array(
                "user_id" => $userid,
                "amount" => $site_amount,
                "type" => 'site_withdraw',
                "date" => date('Y-m-d H:i:s'),
                "history_date" => date('Y-m-d H:i:s'),
                "status" => '1',
                'coin_type' => '2',
                "invest_id" => "",
                'hash_id' => 'admin withdraw',
                "description" => $notes,
                "token_amount" => $amount,
                "coin_id" => $currency_info->id,
                "token_id" => $token_info->id,
            );

            $insert = $this->db->insert("history", $deposit_data);

            $response = array(
                'status' => true,
                'message' => "Wallet detacted successfully !!"
            );

        } else {

            $response = array(
                'status' => false,
                'message' => "Invalide Request."
            );

        }

        echo json_encode($response);

    }
    /*
    |--------------------------------------------------------------------------
    | User Wallet balance
    |--------------------------------------------------------------------------
    */
    public function user_currency_balance($userid)
    {

        $check_balance = '0';

        if ($userid > 0) {
            $check_balance = site_wallet_balance($userid);
        } else {
            echo '0';
        }

        echo $check_balance;

    }
    /*
    |--------------------------------------------------------------------------
    | User Toekn balance
    |--------------------------------------------------------------------------
    */
    public function user_token_balance($userid)
    {

        $check_balance = '0';

        if ($userid > 0) {
            $check_balance = site_token_balance($userid);
        } else {
            echo '0';
        }

        echo $check_balance;

    }
    /*
    |--------------------------------------------------------------------------
    | User Toekn balance
    |--------------------------------------------------------------------------
    */
    public function user_wallet_balance($userid)
    {

        $check_currency_balance = '0';
        $check_token_balance = '0';
        $investment_balance = '0';

        if ($userid > 0) {
            $check_currency_balance = site_wallet_balance($userid);
            $check_token_balance = site_token_balance($userid);
            $investment_balance = investment_balance($userid);
        } else {
            echo '0';
        }

        $data = array(
            "currency_balance" => $check_currency_balance,
            "token_balance" => $check_token_balance,
            "investment_balance" => $investment_balance
        );

        echo json_encode($data);
    }
    /*
    |--------------------------------------------------------------------------
    | User Make investment
    |--------------------------------------------------------------------------
    */
    public function makeinvestment()
    {

        $this->data['title'] = "Make Investment";
        $this->data['card_title'] = "Add Investment To User";
        $this->data['users'] = $this->db->query("SELECT * FROM users where status = '1' ")->result();
        $this->data['package'] = $this->db->query("SELECT * FROM package_config WHERE status = '1'")->result();
        $this->data['action'] = base_url() . "make-investment-post";
        $this->data['redirect_url'] = base_url() . "make-investment";
        $this->load->view('admin/wallet/investment_management', $this->data);

    }
    /*
    |--------------------------------------------------------------------------
    | User Internel Transfer
    |--------------------------------------------------------------------------
    */
    public function internel_transfer()
    {

        $this->data['title'] = "Internel User to User Transfer";
        $this->data['card_title'] = "User to User Transfer Currency";
        $this->data['users'] = $this->db->query("SELECT * FROM users where status = '1' ")->result();
        $this->data['currency_info'] = currency_info();
        $this->data['token_info'] = currency_info();

        $currency = currency_info()->coin_name;
        $this->data['default_currency'] = $currency;
        $token = token_info()->coin_name;
        $this->data['default_token'] = $token;

        $this->data['action'] = base_url() . "internel-transfer-post";
        $this->data['redirect_url'] = base_url() . "internel-transfer";
        $this->load->view('admin/wallet/internel_transfer', $this->data);

    }
    /*
   |--------------------------------------------------------------------------
   | User Internel Transfer Validation
   |--------------------------------------------------------------------------
   */
    public function validate_transfer_balance()
    {

        $sender_id = $this->input->post('sender_id');
        $receiver_id = $this->input->post('receiver_id');
        $currency = $this->input->post('selected_coin');
        $amount = $this->input->post('amount');

        if (empty($sender_id) || empty($receiver_id) || empty($currency) || empty($amount)) {
            echo json_encode(['status' => false, 'message' => 'All fields are required']);
            return;
        }

        if ($currency == "currency") {
            $sender_balance = site_wallet_balance_without_format($sender_id);
        } else {
            $sender_balance = site_token_balance_without_format($sender_id);
        }

        if ($sender_balance < $amount) {
            echo json_encode(['status' => false, 'message' => 'Insufficient balance']);
            return;
        }

        echo json_encode(['status' => true]);
    }
    /*
   |--------------------------------------------------------------------------
   | User Internel Transfer Post Method
   |--------------------------------------------------------------------------
   */
    public function internel_transfer_post()
    {

        if ($this->input->post()) {

            $sender_id = $this->input->post('selected_members');
            $receiver_id = $this->input->post('selected_members_receiver');
            $selected_package = $this->input->post('selected_package');
            $currency = $this->input->post('selected_coin');
            $amount = $this->input->post('bonus_amount');
            $notes_by_user = $this->input->post('notes_by_user');
            $token_info = token_info();
            $currency_info = currency_info();

            $sender_info = $this->db->query("SELECT * FROM users where id = '" . $sender_id . "'")->row();
            $receiver_info = $this->db->query("SELECT * FROM users where id = '" . $receiver_id . "'")->row();

            if ($sender_id == $receiver_id) {
                echo json_encode(['status' => false, 'message' => 'Sender And Receiver are same users !']);
                return;
            }


            if ($sender_info->status != '1') {
                echo json_encode(['status' => false, 'message' => 'Sender is in-active !']);
                return;
            }


            if ($receiver_info->status != '1') {
                echo json_encode(['status' => false, 'message' => 'Receiver is in-active !']);
                return;
            }


            if ($currency == "currency") {
                $sender_balance = site_wallet_balance_without_format($sender_id);
                $earn_type = '1';
                $usd_price = $amount;
                $token_price = $amount * $token_info->currency_value;
                $type_discription = 'Currency';

            } else {
                $sender_balance = site_token_balance_without_format($sender_id);
                $earn_type = '2';
                $usd_price = $amount / $token_info->currency_value;
                $token_price = $amount;
                $type_discription = 'Token';
            }

            $type_1 = 'internel_transfer_send';
            $type_2 = 'internel_transfer_received';


            if ($sender_balance < $amount) {
                echo json_encode(['status' => false, 'message' => 'Insufficient balance']);
                return;
            }

            $uniq_id = 'TXN' . strtoupper(bin2hex(random_bytes(4))) . time();
            $transaction_id = "transaction_id_" . $uniq_id;

            if ($amount > 0) {

                $sender_data = array(
                    "user_id" => $sender_id,
                    "amount" => $usd_price,
                    "type" => $type_1,
                    "history_date" => date('Y-m-d H:i:s'),
                    "date" => date('Y-m-d H:i:s'),
                    "status" => '1',
                    "hash_id" => "admin-made",
                    "transaction_id" => $transaction_id,
                    "coin_type" => $earn_type,
                    "invest_id" => '0',
                    "token_amount" => $token_price,
                    "description" => "Internel Transfer " . $type_discription . " Send To " . $receiver_info->email . " ( " . $receiver_info->referral_id . " )",
                    "coin_id" => $currency_info->id,
                    "token_id" => $token_info->id,
                    "from_id" => $receiver_id,
                );
                $insert = $this->db->insert("history", $sender_data);

                $receiver_data = array(
                    "user_id" => $receiver_id,
                    "amount" => $usd_price,
                    "type" => $type_2,
                    "history_date" => date('Y-m-d H:i:s'),
                    "date" => date('Y-m-d H:i:s'),
                    "status" => '1',
                    "hash_id" => "admin-made",
                    "transaction_id" => $transaction_id,
                    "coin_type" => $earn_type,
                    "invest_id" => '0',
                    "token_amount" => $token_price,
                    "description" => "Internel Transfer " . $type_discription . " Received From " . $sender_info->email . " ( " . $sender_info->referral_id . " )",
                    "coin_id" => $currency_info->id,
                    "token_id" => $token_info->id,
                    "from_id" => $sender_id,
                );
                $insert = $this->db->insert("history", $receiver_data);

                echo json_encode(['status' => true, 'message' => 'Internel Transfer Successfully']);
                return;

            }

        }

    }
    /*
     |--------------------------------------------------------------------------
     | User Internel Swap
     |--------------------------------------------------------------------------
     */
    public function internel_swap()
    {

        $this->data['title'] = "Internel Swap Users";
        $this->data['card_title'] = "User Swap Currency";
        $this->data['users'] = $this->db->query("SELECT * FROM users where status = '1' ")->result();
        $this->data['currency_info'] = currency_info();
        $this->data['token_info'] = currency_info();
        $this->data['action'] = base_url() . "make-swap-post";
        $this->data['redirect_url'] = base_url() . "internel-swap";

        $currency = currency_info()->coin_name;
        $this->data['default_currency'] = $currency;
        $token = token_info()->coin_name;
        $this->data['default_token'] = $token;
        $this->data['value_text'] = "<b>" . token_info()->currency_value . " " . token_info()->coin_name . " = 1 USDT  </b> ";

        $this->load->view('admin/wallet/internel_swap', $this->data);

    }
    /*
    |--------------------------------------------------------------------------
    | User Internel Transfer Validation
    |--------------------------------------------------------------------------
    */
    public function validate_swap_balance()
    {

        $sender_id = $this->input->post('sender_id');
        $amount = $this->input->post('amount');
        $amount = str_replace(',', '', $amount);
        $amount = floatval($amount);

        if (empty($sender_id) || empty($amount)) {
            echo json_encode(['status' => false, 'message' => 'All fields are required']);
            return;
        }

        $sender_balance = site_token_balance_without_format($sender_id);

        if ($sender_balance < $amount) {
            echo json_encode(['status' => false, 'message' => 'Insufficient balance']);
            return;
        }

        $token_info = token_info();
        $usd_price = $amount / $token_info->currency_value;

        echo json_encode(['status' => true, 'message' => $usd_price]);
    }
    /*
    |--------------------------------------------------------------------------
    | Internel Swap Post
    |--------------------------------------------------------------------------
    */
    public function internel_swap_post()
    {

        if ($this->input->post()) {

            $sender_id = $this->input->post('selected_members');
            $amount = $this->input->post('bonus_amount');
            $notes_by_user = $this->input->post('notes_by_user');

            $token_info = token_info();
            $currency_info = currency_info();

            $sender_info = $this->db->query("SELECT * FROM users where id = '" . $sender_id . "'")->row();

            if ($sender_info->status != '1') {
                echo json_encode(['status' => false, 'message' => 'User is in-active !']);
                return;
            }

            $sender_balance = site_token_balance_without_format($sender_id);
            $usd_price = $amount / $token_info->currency_value;
            $token_price = $amount;
            $type_discription = "Token";

            $type_1 = 'internel_swap_send';
            $type_2 = 'internel_swap_received';


            if ($sender_balance < $amount) {
                echo json_encode(['status' => false, 'message' => 'Insufficient balance']);
                return;
            }

            $uniq_id = 'TXN' . strtoupper(bin2hex(random_bytes(4))) . time();
            $transaction_id = "transaction_id_" . $uniq_id;

            if ($amount > 0) {

                $sender_data = array(
                    "user_id" => $sender_id,
                    "amount" => $usd_price,
                    "type" => $type_1,
                    "history_date" => date('Y-m-d H:i:s'),
                    "date" => date('Y-m-d H:i:s'),
                    "status" => '1',
                    "hash_id" => "admin-made",
                    "transaction_id" => $transaction_id,
                    "coin_type" => '2',
                    "invest_id" => '0',
                    "token_amount" => $token_price,
                    "description" => "Internel Swap " . $type_discription . " Send",
                    "coin_id" => $currency_info->id,
                    "token_id" => $token_info->id,
                    "from_id" => $sender_id,
                );
                $insert = $this->db->insert("history", $sender_data);

                $receiver_data = array(
                    "user_id" => $sender_id,
                    "amount" => $usd_price,
                    "type" => $type_2,
                    "history_date" => date('Y-m-d H:i:s'),
                    "date" => date('Y-m-d H:i:s'),
                    "status" => '1',
                    "hash_id" => "admin-made",
                    "transaction_id" => $transaction_id,
                    "coin_type" => '1',
                    "invest_id" => '0',
                    "token_amount" => $token_price,
                    "description" => "Internel Swap Currency Received",
                    "coin_id" => $currency_info->id,
                    "token_id" => $token_info->id,
                    "from_id" => $sender_id,
                );
                $insert = $this->db->insert("history", $receiver_data);

                echo json_encode(['status' => true, 'message' => 'Internel Swap Successfully']);
                return;

            }

        }

    }
    /*
   |--------------------------------------------------------------------------
   | Deposit Amount Validation
   |--------------------------------------------------------------------------
   */
    public function validate_package_amount()
    {
        if ($this->input->post()) {
            $package_id = $this->input->post('package_id', true);
            $amount = floatval($this->input->post('amount', true));

            $query = $this->db->query("SELECT * FROM package_config WHERE id = ? AND status = '1'", [$package_id]);
            $package = $query->row();

            if (!$package) {
                echo json_encode([
                    'status' => false,
                    'message' => "Invalid package."
                ]);
                exit;
            }

            if ($amount >= $package->minimum && $amount <= $package->maximum) {
                echo json_encode([
                    'status' => true,
                    'message' => "Amount is valid."
                ]);
            } else {
                $minimum_amount = currency_format($package->minimum);
                $maximum_amount = currency_format($package->maximum);
                echo json_encode([
                    'status' => false,
                    'message' => "Amount must be between {$minimum_amount} and {$maximum_amount}."
                ]);
            }
            exit;
        }

        echo json_encode([
            'status' => false,
            'message' => "Invalid request."
        ]);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Deposit Verify Admin
    |--------------------------------------------------------------------------
    */
    public function verify_investment()
    {

        $this->data['title'] = "Verify Investment";
        $this->data['card_tilte'] = "Investment Request List";
        $this->load->view('admin/wallet/request_investment_list', $this->data);

    }
    /*
    |--------------------------------------------------------------------------
    | Deposit Verify List Admin
    |--------------------------------------------------------------------------
    */
    public function verify_investment_list()
    {

        $this->load->model('wallet/Investment_model');
        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');

        $data = array();
        $total_records = $this->Investment_model->get_count();
        $users = $this->Investment_model->get_info($length, $start);


        $i = 0;
        foreach ($users as $user) {
            $i++;



            $currency_status = $user['status'] ? "checked" : "";
            $change_status_url = base_url() . "announcement-status-update-cms/" . $user['id'];
            $userinfo = $this->db->query("SELECT * FROM users where id = '" . $user['user_id'] . "' ")->row();
            $package_info = $this->db->query("SELECT * FROM `package_config` where id = '" . $user['package_id'] . "' ")->row();

            $today = date("Y-m-d H:i:s");
            $start = new DateTime($user['starting_date']);
            $mature = new DateTime($user['mature_date']);
            $now = new DateTime($today);
            $verify_link = "https://bscscan.com/tx/" . $user['hash_id'];

            $remaining_days = $now->diff($mature)->days;

            $data[] = array(
                'RecordID' => $i,
                'UserInfo' => '<div class="d-flex align-items-center">
        <div class="symbol symbol-50px me-3">                                                   
        </div>
        <div class="d-flex justify-content-start flex-column">
        <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">' . $userinfo->email . '</a>
        <span class="text-gray-500 fw-semibold d-block fs-7">' . $userinfo->referral_id . '</span>
        </div>
        </div>',
                'InvestInfo' => '<div class="d-flex align-items-center">
        <div class="symbol symbol-50px me-3"></div>
        <div class="d-flex justify-content-start flex-column">
        <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">'
                    . htmlspecialchars($package_info->package_name, ENT_QUOTES, 'UTF-8') . ' - Package</a>
        <span class="me-2 text-gray-600 fw-bold d-block fs-6">'
                    . currency_format($user['invest_amount']) . ' - ' . $package_info->days_duration . ' days
        </span> 
        </div>
        </div>',
                'DateInfo' => '<div class="d-flex align-items-center">
        <div class="symbol symbol-50px me-3">                                                   
        </div>
        <div class="d-flex justify-content-start flex-column">
        <span class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">' . $remaining_days . ' -  days Remaining</span>
        <a href="#" class="fs-7 text-muted fw-bold mb-1 fs-6">' . $user['starting_date'] . ' - ' . $user['mature_date'] . '</a>
        </div>
        </div>',
                'EndDate' => '<div class="d-flex justify-content-start flex-column">
        <div class="d-flex justify-content-start ">
         <a class="btn btn-info btn-active-light-info btn-sm text-center me-4" href="' . $verify_link . '">
		 <i class="fa-solid fa-eye  "></i> View
		</a>
        </div>
        </div>',
                'temp_content' => '<div class="d-flex justify-content-center flex-row">
        <a class="btn btn-success btn-active-light-success btn-sm btn-approve text-center me-4"  data-approve-url="' . base_url() . 'approve-investment/' . $user['id'] . '">
		 <i class="fa fa-check" aria-hidden="true"></i> Approve
		</a>
        <a class="btn btn-danger btn-active-light-danger btn-sm text-center btn-delete" href="javascript:void(0);"  data-reject-url="' . base_url() . 'reject-investment/' . $user['id'] . '">
		 <i class="fa fa-ban" aria-hidden="true"></i>  Reject
		</a>
		</div>',
            );
        }

        $response = array(
            'draw' => intval($draw),
            'recordsTotal' => $total_records,
            'recordsFiltered' => $total_records,
            'data' => $data
        );

        echo json_encode($response);
    }
    /*
    |--------------------------------------------------------------------------
    | Deposit Verify Function
    |--------------------------------------------------------------------------
    */
    public function approve_investment($id)
    {

        if ($id) {

            $check_template = $this->db->query("SELECT * FROM `user_investment` where id = '" . $id . "'")->num_rows();

            if ($check_template > 0) {

                $template_status = '1';

                $array_template = array(
                    "approve_status" => $template_status,
                );

                $this->db->where('id', $id);
                $this->db->update('user_investment', $array_template);

                $response = array(
                    'status' => "success",
                    'message' => "Investment Verification Successfully."
                );
                echo json_encode($response);
                exit();
            } else {
                $response = array(
                    'status' => false,
                    'message' => "Invalide investment!"
                );
                echo json_encode($response);
                exit();
            }

        }

    }
    /*
    |--------------------------------------------------------------------------
    | Deposit Verify Function
    |--------------------------------------------------------------------------
    */
    public function reject_investment($id)
    {

        if ($id) {

            $check_template = $this->db->query("SELECT * FROM `user_investment` where id = '" . $id . "'")->num_rows();

            if ($check_template > 0) {

                $template_status = '2';

                $array_template = array(
                    "approve_status" => $template_status,
                );

                $this->db->where('id', $id);
                $this->db->update('user_investment', $array_template);

                $response = array(
                    'status' => "success",
                    'message' => "Investment Rejected Successfully."
                );
                echo json_encode($response);
                exit();
            } else {
                $response = array(
                    'status' => false,
                    'message' => "Invalide investment!"
                );
                echo json_encode($response);
                exit();
            }

        }

    }
    /*
    |--------------------------------------------------------------------------
    | Investment List
    |--------------------------------------------------------------------------
    */
    public function investmentlist()
    {

        $this->data['title'] = "Investment History";
        $this->data['card_tilte'] = "Investment  List";
        $this->load->view('admin/wallet/investment-list', $this->data);

    }
    /*
    |--------------------------------------------------------------------------
    | Deposit Verify List Admin
    |--------------------------------------------------------------------------
    */
    public function investment_list_get()
    {

        $this->load->model('wallet/Investment_model');
        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');

        $type = $this->input->get('call_status');
        $clients = $this->input->get('client_filter');
        $from_date = $this->input->get('from_date') ? date('Y-m-d', strtotime($this->input->get('from_date'))) : '';
        $to_date = $this->input->get('to_date') ? date('Y-m-d', strtotime($this->input->get('to_date'))) : '';


        $data = array();
        $total_records = $this->Investment_model->get_count_invest($from_date, $to_date, $clients, $type);
        $users = $this->Investment_model->get_info_invest($length, $start, $from_date, $to_date, $clients, $type);


        $i = 0;
        foreach ($users as $user) {
            $i++;

            $currency_status = $user['status'];
            $reinvest_status = $user['reinvest_status'] ? "checked" : "";
            $change_status_url = base_url() . "package-reinvest-status/" . $user['id'];
            $userinfo = $this->db->query("SELECT * FROM users where id = '" . $user['user_id'] . "' ")->row();
            $package_info = $this->db->query("SELECT * FROM `package_config` where id = '" . $user['package_id'] . "' ")->row();


            $verify_link = "https://bscscan.com/tx/" . $user['hash_id'];


            $today = date("Y-m-d H:i:s");
            $start = new DateTime($user['starting_date']);
            $mature = new DateTime($user['mature_date']);
            $now = new DateTime($today);

            $remaining_days = $now->diff($mature)->days;

            if ($now > $mature) {
                $remaining_days = 0;
            }

            if ($currency_status == "1") {
                $package_status = '';
                $package_color = 'text-gray-800';
                $delete_disabled = '';
            } else {
                $package_status = 'disabled';
                $package_color = 'text-danger';
                $delete_disabled = 'disabled';
            }

            $package_apporved = $user['approve_status'] ? '1' : '0';

            if ($package_apporved) {
                $package_approve_button = '';
            } else {
                $package_approve_button = '<a class="btn btn-success btn-active-light-success btn-sm btn-approve text-center me-4"  data-approve-url="' . base_url() . 'approve-investment/' . $user['id'] . '">
		 <i class="fa fa-check" aria-hidden="true"></i> Approve
		</a>';
            }


            $data[] = array(
                'RecordID' => $i,
                'UserInfo' => '<div class="d-flex align-items-center">
        <div class="symbol symbol-50px me-3">                                                   
        </div>
        <div class="d-flex justify-content-start flex-column">
        <a href="#" class="' . $package_color . ' fw-bold text-hover-primary mb-1 fs-6">' . $userinfo->email . '</a>
        <span class="text-gray-500 fw-semibold d-block fs-7">' . $userinfo->referral_id . '</span>
        </div>
        </div>',
                'InvestInfo' => '<div class="d-flex align-items-center">
        <div class="symbol symbol-50px me-3"></div>
        <div class="d-flex justify-content-start flex-column">
        <a href="#" class="' . $package_color . ' fw-bold text-hover-primary mb-1 fs-6">'
                    . htmlspecialchars($package_info->package_name, ENT_QUOTES, 'UTF-8') . ' - Package</a>
        <span class="me-2 text-gray-600 fw-bold d-block fs-6">'
                    . currency_format($user['invest_amount']) . ' - ' . $package_info->days_duration . ' days
        </span> 
        </div>
        </div>',
                'DateInfo' => '<div class="d-flex align-items-center">
        <div class="symbol symbol-50px me-3">                                                   
        </div>
        <div class="d-flex justify-content-start flex-column">
        <span class="' . $package_color . ' fw-bold text-hover-primary mb-1 fs-6">' . $user['days_count'] . ' -  days Remaining</span>
        <a href="#" class="fs-7 text-muted fw-bold mb-1 fs-6">' . $user['starting_date'] . ' - ' . $user['mature_date'] . '</a>
        </div>
        </div>',
                'EndDate' => '<div class="form-check form-switch form-check-custom form-check-success form-check-solid">
        <input class="form-check-input h-30px w-50px template_status" type="checkbox" ' . $package_status . ' value="1" name="template_status"' .
                    $reinvest_status . '
        id="template_status" 
        data-payment="' . $user['id'] . '" 
        data-template_status-url="' . $change_status_url . '"/>
        <label class="form-check-label" for="template_status">
        </label>
        </div>',
                'temp_content' => '<div class="d-flex justify-content-center flex-row">
        <a class="btn btn-info btn-active-light-info btn-sm  text-center me-4" href="' . base_url() . 'investment-info/' . $user['id'] . '">
         <i class="fa fa-eye" aria-hidden="true"></i> View More
        </a>
        ' . $package_approve_button . '
        <a class="btn btn-danger btn-active-light-danger btn-sm text-center btn-delete ' . $delete_disabled . '" href="javascript:void(0);" data-reject-url="' . base_url() . 'delete-investment/' . $user['id'] . '" ' . $delete_disabled . '>
         <i class="fa fa-trash" aria-hidden="true"></i> Delete 
        </a>
        </div>
		',
            );
        }

        $response = array(
            'draw' => intval($draw),
            'recordsTotal' => $total_records,
            'recordsFiltered' => $total_records,
            'data' => $data
        );

        echo json_encode($response);
    }
    /*
    |--------------------------------------------------------------------------
    | Package Reinvestment Status
    |--------------------------------------------------------------------------
    */
    public function package_reinvest_status($id)
    {

        if ($id) {

            $check_template = $this->db->query("SELECT * FROM `user_investment` where id = '" . $id . "' and status = '1' ")->num_rows();

            if ($check_template > 0) {

                $status = $this->input->post('reinvest_status');
                $template_status = $status == '1' ? '1' : '0';

                $array_template = array(
                    "reinvest_status" => $template_status,
                );

                $this->db->where('id', $id);
                $this->db->update('user_investment', $array_template);

                $response = array(
                    'status' => "success",
                    'message' => "Status update successfully.."
                );
                echo json_encode($response);
                exit();
            } else {
                $response = array(
                    'status' => false,
                    'message' => "Invalid investment or investment is matured !"
                );
                echo json_encode($response);
                exit();
            }

        }
    }
    /*
   |--------------------------------------------------------------------------
   | Package Delete
   |--------------------------------------------------------------------------
   */
    public function investment_delete($id)
    {

        if ($id) {

            $check_template = $this->db->query("SELECT * FROM `user_investment` where id = '" . $id . "' and status = '1' ")->num_rows();

            if ($check_template > 0) {

                /****************************** CHECK REINVESTED PACKAGE ******************************/
                $check_already_invested = $this->db->query("SELECT * FROM `user_investment` where id = '" . $id . "' and reinvest_id != '0' ")->row();

                if ($check_already_invested) {

                    $reinvested_info = $this->db->query("SELECT * FROM `user_investment` where id = '" . $check_already_invested->reinvest_id . "' ")->row();

                    $query = $this->db->query("SELECT * FROM package_config WHERE id = ? AND status = '1'", [$reinvested_info->package_id]);
                    $package = $query->row();

                    $deposit_data = array(
                        "user_id" => $reinvested_info->user_id,
                        "amount" => $reinvested_info->invest_amount,
                        "type" => "release_deposit",
                        "history_date" => date('Y-m-d H:i:s', strtotime($reinvested_info->mature_date)),
                        "date" => date('Y-m-d H:i:s', strtotime($reinvested_info->mature_date)),
                        "status" => '1',
                        "hash_id" => $reinvested_info->hash_id,
                        "invest_id" => $reinvested_info->id,
                        "token_amount" => $reinvested_info->csq_deposit,
                        "description" => "Package Matured Successfully ( " . $package->package_name . " )",
                        "coin_type" => '2',
                        "coin_id" => $reinvested_info->currency_id,
                        "token_id" => $reinvested_info->token_id,
                    );
                    $this->db->insert("history", $deposit_data);

                    /******************* INVESTMENT */
                    $this->db->where('id', $id);
                    $this->db->delete('user_investment');
                    /******************* HISTORY */
                    $this->db->where('invest_id', $id);
                    $this->db->delete('history');

                } else {

                    /******************* INVESTMENT */
                    $this->db->where('id', $id);
                    $this->db->delete('user_investment');
                    /******************* HISTORY */
                    $this->db->where('invest_id', $id);
                    $this->db->delete('history');

                }


                $response = array(
                    'status' => "success",
                    'message' => "Investment Deleted successfully.."
                );
                echo json_encode($response);
                exit();

            } else {
                $response = array(
                    'status' => false,
                    'message' => "Invalid investment or investment is matured !"
                );
                echo json_encode($response);
                exit();
            }

        }
    }
    /*
    |--------------------------------------------------------------------------
    | list Investment Amount
    |--------------------------------------------------------------------------
    */
    public function investment_amount_fetch()
    {

        $this->load->model('wallet/Investment_model');

        $total_amount = 0;
        $total_token_amount = 0;

        $type = $this->input->post('call_status');
        $clients = $this->input->post('client_filter');
        $from_date = $this->input->post('from_date') ? date('Y-m-d', strtotime($this->input->post('from_date'))) : '';
        $to_date = $this->input->post('to_date') ? date('Y-m-d', strtotime($this->input->post('to_date'))) : '';

        $data = array();
        $users = $this->Investment_model->get_info_amt($from_date, $to_date, $clients, $type);

        $i = 0;
        foreach ($users as $user) {
            $i++;

            $total_amount += str_replace(',', '', $user['invest_amount']);
            $total_token_amount += str_replace(',', '', $user['csq_deposit']);
        }

        $response = array(
            "total_amount" => $total_amount,
            "total_token_amount" => $total_token_amount,
        );

        echo json_encode($response);
    }
    /*
    |--------------------------------------------------------------------------
    | View Investment Info
    |--------------------------------------------------------------------------
    */
    public function investment_info($id)
    {

        if ($id > 0) {

            $wallet_info = $this->db->query("SELECT * FROM user_investment where id = '" . $id . "' ")->row();

            if ($wallet_info) {

                $this->data['title'] = "Investment Info";
                $this->data['card_tilte'] = "Investment  Info";
                $this->data['user_id'] = $wallet_info->user_id;
                $user_info = $this->db->query("SELECT * FROM users where id = '" . $wallet_info->user_id . "' ")->row();
                $this->data['currency_info'] = currency_info();
                $this->data['token_info'] = token_info();


                $today = date("Y-m-d H:i:s");
                $start = new DateTime($wallet_info->starting_date);
                $mature = new DateTime($wallet_info->mature_date);
                $now = new DateTime($today);
                $remaining_days = $now->diff($mature)->days;


                $check_hash = $this->db->query("SELECT * FROM history where invest_id = '" . $id . "' and type='mining' ")->row();

                if ($check_hash->hash_id == "admin-made") {
                    $verify_link = "----";
                    $payment_by = "Admin Made";
                } else {
                    $verify_link = '<a href=https://bscscan.com/tx/' . $check_hash->hash_id . ">View BSC Scan </a>";
                    $payment_by = "BSC SCAN";
                }


                $package_info = $this->db->query("SELECT * FROM `package_config` where id = '" . $wallet_info->package_id . "' ")->row();

                if ($package_info->maximum > 0) {
                    $maximum = currency_format($package_info->maximum);
                } else {
                    $maximum = "Unlimited";
                }

                $dateinfo = '<label class="w-150px mb-1">' . $wallet_info->days_count . '  -  days Remaining </label>
                 <div class="fw-normal text-gray-600"> Created Date : ' . $wallet_info->starting_date . '</div>
                 <div class="fw-normal text-gray-600"> Mature Date : ' . $wallet_info->mature_date . '</div>';

                $total_earnings_cu_get = $this->db->query("SELECT SUM(amount) as currency_amount FROM `history` where type = 'profit' and invest_id = '" . $id . "' and user_id = '" . $wallet_info->user_id . "' ")->row()->currency_amount;
                $total_earnings_token = $this->db->query("SELECT SUM(token_amount) as token_amount FROM `history` where type = 'profit' and invest_id = '" . $id . "' and user_id = '" . $wallet_info->user_id . "'  ")->row()->token_amount;



                if ($wallet_info->status == "1") {
                    $invest_status = '<span class="text-success fw-semibold"> <span class="p-5"> Active </span></span>';
                } else {
                    $invest_status = '<span class="text-danger fw-semibold"> <span class="p-5"> Matured </span></span>';
                }

                $this->data['dateinfo'] = $dateinfo;

                $this->data['username'] = $user_info->username;
                $this->data['useremail'] = $user_info->email;
                $this->data['userreferralid'] = $user_info->referral_id;
                $this->data['min_max_package'] = "Minimum : " . currency_format($package_info->minimum) . " -  Maxmum : " . $maximum;
                $this->data['packagename'] = $package_info->package_name ? $package_info->package_name : "Package Removed";
                $this->data['packageamount'] = currency_format($wallet_info->invest_amount);
                $this->data['packagetokenamount'] = token_format($wallet_info->csq_deposit);
                $this->data['pacakgeduration'] = $wallet_info->days_duration . " Days";
                $this->data['paymenturl'] = $verify_link;
                $this->data['remaining_days'] = $remaining_days;
                $this->data['payment_by'] = $payment_by;
                $this->data['invest_status'] = $invest_status;

                $this->data['total_earnings_currency'] = currency_format($total_earnings_cu_get);
                $this->data['total_earnings_token'] = token_format($total_earnings_token);

                $this->data['invest_id'] = $id;
                $this->load->view('admin/wallet/investment-info', $this->data);

            }


        } else {
            redirect('list-investment');
        }


    }
    /*
    |--------------------------------------------------------------------------
    | View Investment Profit
    |--------------------------------------------------------------------------
    */
    public function list_profit()
    {

        $this->load->model('transaction/Transaction_model');

        $total_amount = 0;
        $total_token_amount = 0;

        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');

        $type = 'profit';
        $invest_id = $this->input->get('invest_id');
        ;
        $clients = $this->input->get('client_filter');
        $from_date = $this->input->get('from_date') ? date('Y-m-d', strtotime($this->input->get('from_date'))) : '';
        $to_date = $this->input->get('to_date') ? date('Y-m-d', strtotime($this->input->get('to_date'))) : '';

        $data = array();
        $total_records = $this->Transaction_model->get_count_profit($from_date, $to_date, $clients, $type, $invest_id);
        $users = $this->Transaction_model->get_info_profit($length, $start, $from_date, $to_date, $clients, $type, $invest_id);


        $i = 0;
        foreach ($users as $user) {
            $i++;

            $status = "";

            if ($user['status'] == '0') {
                $status = "In-Active";
            }
            if ($user['status'] == '1') {
                $status = "Active";
            }
            if ($user['status'] == '2') {
                $status = "In-Active";
            }

            $user_info = $this->db->query("SELECT * FROM users where id = '" . $user['user_id'] . "' ")->row();
            $user_email = $user_info->email;
            $user_referral = $user_info->referral_id;

            $currency_price = 0;
            $token_amount = 0;

            if ($user['amount'] > 0) {
                $currency_amount = (float) str_replace(',', '', $user['amount']);
                $currency_price = currency_format($currency_amount);
            }

            if ($user['token_amount'] > 0) {
                $token_amount = (float) str_replace(',', '', $user['token_amount']);
                $token_amount = token_format($token_amount);
            }


            $entry_date = $user['date'];
            $description = $user['description'];

            if ($user['status'] == '1') {
                $status = "Active";
            } else {
                $status = "Pending";
            }

            $formattedType = str_replace('_', ' ', $user['type']);
            $formattedType = ucwords(strtolower($formattedType));
            $formattedType = ucfirst($formattedType);

            $type_symbol = '
        <div class="symbol symbol-40px me-3 mb-1">
        <span class="symbol-label bg-light-success">
        <i class="ki-duotone ki-flask fs-2 text-success"><span class="path1"></span><span class="path2"></span></i></span>
        </div>
        ';

            $currency_list = '<div class="d-flex align-items-center me-5">
        <div class="me-5">
        <a href="#" class="text-gray-800 fw-bold text-hover-primary fs-6">' . $currency_price . '</a>
        <span class="fw-semibold fs-7 d-block text-start text-success ps-0">' . $token_amount . '</span>   
        </div>
        </div>';

            $extra_dis = "";



            if ($user['type'] == 'binary_commission') {
                $extra_dis .= '<span class="text-gray-500 fw-semibold d-block fs-7 mb-1 mt-1">Total Left Inest <span class="badge badge-light-default  fs-8"> ' . token_format($user['total_left_invest']) . ' </span> </span>';
                $extra_dis .= '<span class="text-gray-500 fw-semibold d-block fs-7 mb-1">Total Right Inest  <span class="badge badge-light-default  fs-8"> ' . token_format($user['total_right_invest']) . '</span></span>';
                $extra_dis .= '<span class="text-gray-500 fw-semibold d-block fs-7 mb-1">Total Left Profit  <span class="badge badge-light-default  fs-8"> ' . token_format($user['total_left_roi']) . '</span></span>';
                $extra_dis .= '<span class="text-gray-500 fw-semibold d-block fs-7 mb-1">Total Right Profit <span class="badge badge-light-default  fs-8">  ' . token_format($user['total_right_roi']) . '</span></span>';
            }


            $default_icon = '<i class="ki-duotone ki-flask fs-2 text-success"><span class="path1"></span><span class="path2"></span></i>';
            $default_bg = 'bg-light-success';


            if ($user['type'] == 'profit') {
                $default_icon = '<i class="ki-duotone ki-percentage text-info fs-2"><span class="path1"></span><span class="path2"></span></i>';
                $default_bg = 'bg-light-info';
            }


            $type_symbol = '<div class="d-flex align-items-center me-5">
        <div class="symbol symbol-40px me-3">
        <span class="symbol-label  ' . $default_bg . ' ">
        ' . $default_icon . '
        </span>
        </div>
        <div class="me-5">
        <a href="#" class="text-gray-900 fw-bold text-hover-primary fs-6">' . $formattedType . '</a>
        <span class="fw-semibold fs-7 d-block text-start text-success ps-0 mt-1">' . $entry_date . '</span>   
        <span class="fw-semibold fs-7 d-block text-start text-gray-500 ps-0 mt-1 mb-1">' . $description . '</span>   
        ' . $extra_dis . '
        </div>
        </div>';

            $total_amount += str_replace(',', '', $user['amount']);
            $total_token_amount += str_replace(',', '', $user['token_amount']);


            $data[] = array(
                'RecordID' => $i,
                'UserInfo' => '<div class="d-flex align-items-center">
        <div class="symbol symbol-50px me-3">                                                   
        </div>
        <div class="d-flex justify-content-start flex-column">
        <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">' . $user_email . '</a>
        <span class="text-gray-500 fw-semibold d-block fs-7 mb-1">' . $user_referral . '</span>
        </div>
        </div>',
                'TransactionInfo' => '' . $type_symbol . '',
                'CurrencyInfo' => '' . $currency_list . '',
                'Status' => '<div class="d-flex align-items-center">
        <div class="symbol symbol-50px me-3">                                                   
        </div>
        <div class="d-flex justify-content-start flex-column">
        <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">' . $status . '</a>
        </div>
        </div>',
            );
        }

        $response = array(
            'draw' => intval($draw),
            'recordsTotal' => $total_records,
            'recordsFiltered' => $total_records,
            'data' => $data,
            "total_amount" => $total_amount,
            "total_token_amount" => $total_token_amount,
        );

        echo json_encode($response);

    }
    /*
    |--------------------------------------------------------------------------
    | list Investment Amount
    |--------------------------------------------------------------------------
    */
    public function profit_amount_fetch()
    {

        $this->load->model('transaction/Transaction_model');

        $total_amount = 0;
        $total_token_amount = 0;

        $type = 'profit';
        $invest_id = $this->input->post('invest_id');
        $clients = $this->input->post('client_filter');
        $from_date = $this->input->post('from_date') ? date('Y-m-d', strtotime($this->input->post('from_date'))) : '';
        $to_date = $this->input->post('to_date') ? date('Y-m-d', strtotime($this->input->post('to_date'))) : '';
        $users = $this->Transaction_model->get_info_amtprofit($from_date, $to_date, $clients, $type, $invest_id);

        $data = array();

        $i = 0;
        foreach ($users as $user) {
            $i++;


            if ($user['amount'] > 0) {
                $total_amount += str_replace(',', '', $user['amount']);
            }

            if ($user['token_amount'] > 0) {
                $total_token_amount += str_replace(',', '', $user['token_amount']);
            }



        }

        $response = array(
            "total_amount" => $total_amount,
            "total_token_amount" => $total_token_amount,
        );

        echo json_encode($response);
    }


    public function payment_failed()
    {

        $order_id = $this->input->get('did');
        if (!$order_id) {
            show_404();
        }
        $this->db->where('id', $order_id)->update('deposits', ['status' => 'failed']);
        $this->session->set_flashdata('danger', 'your transaction is failed');
        redirect(base_url('user/lending'));

    }

    /**
     * AJAX: Check swap order status (poll for gas fee, USDT payment, etc.)
     */
    public function swap_status()
    {
        $userId = (int) $this->session->userdata('user_userid');
        if (!$userId) {
            echo json_encode(['status'=>false,'message'=>'Unauthorized']);
            return;
        }

        $orderId = (int) $this->input->post('order_id');
        $this->load->model('StakingSwap_model', 'swap');

        $order = $this->swap->getOrder($orderId);
        if (!$order || $order['user_id'] != $userId) {
            echo json_encode(['status'=>false,'message'=>'Order not found']);
            return;
        }

        // Check for updates based on current status
        switch ($order['status']) {
            case 'pending_gas_fee':
                $this->swap->detectGasFeePaid($orderId);
                $order = $this->swap->getOrder($orderId); // Refresh
                break;
            case 'pending_usdt':
                $this->swap->detectUsdtPayment($orderId);
                $order = $this->swap->getOrder($orderId); // Refresh
                break;
            case 'pending_bman':
                $this->swap->detectBmanTransfer($orderId);
                $order = $this->swap->getOrder($orderId); // Refresh
                break;
        }

        // Map status to user-friendly text
        $status_map = [
            'pending_gas_fee' => 'Waiting for gas fee...',
            'pending_usdt' => 'Gas fee received! Ready to send USDT',
            'pending_bman' => 'USDT received! Waiting for BMAN transfer',
            'swap_completed' => 'Swap completed! Staking activated',
            'failed' => 'Order failed: ' . ($order['error'] ?? 'Unknown error'),
        ];

        echo json_encode([
            'status' => true,
            'order_id' => $order['id'],
            'order_ref' => $order['ref'],
            'current_status' => $order['status'],
            'status_text' => $status_map[$order['status']] ?? 'Unknown',
            'gas_tx_hash' => $order['gas_tx_hash'],
            'usdt_tx_hash' => $order['usdt_tx_hash'],
            'bman_tx_hash' => $order['bman_tx_hash'],
            'user_address' => $order['user_address'],
            'admin_address' => $order['admin_address'],
            'usdt_amount' => $order['usdt_amount'],
            'bman_amount' => $order['bman_amount'],
            'bonus_bman' => $order['bonus_bman'],
            'can_proceed' => $order['status'] === 'pending_usdt',
            'is_completed' => $order['status'] === 'swap_completed',
            'created_at' => $order['created_at'],
            'updated_at' => $order['updated_at'],
        ]);
    }

    /**
     * AJAX: Get swap order history for user
     */
    public function swap_history()
    {
        $userId = (int) $this->session->userdata('user_userid');
        if (!$userId) {
            echo json_encode(['status'=>false,'message'=>'Unauthorized']);
            return;
        }

        $orders = $this->db->select('id, ref, package_id, usdt_amount, bman_amount, status, gas_tx_hash, usdt_tx_hash, bman_tx_hash, created_at')
            ->where('user_id', $userId)
            ->order_by('created_at', 'DESC')
            ->limit(50)
            ->get('staking_swap_orders')
            ->result_array();

        // Map status to color/icon
        foreach ($orders as &$order) {
            $status_map = [
                'pending_gas_fee' => 'warning',
                'pending_usdt' => 'info',
                'pending_bman' => 'info',
                'swap_completed' => 'success',
                'failed' => 'danger',
            ];
            $order['status_badge'] = $status_map[$order['status']] ?? 'secondary';
        }

        echo json_encode([
            'status' => true,
            'count' => count($orders),
            'orders' => $orders,
        ]);
    }


}
