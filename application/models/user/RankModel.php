<?php defined('BASEPATH') OR exit('No direct script access allowed');

class RankModel extends CI_Model
{

  /**
   * PV SOURCE SETTINGS
   * - PV from product purchase (orders + order_items + products.commission)
   * - Fallback PV from user_investment + package_config.bv (optional)
   */
  private $PV_SOURCE = 'orders'; // 'orders' OR 'investment_fallback'

  // ====== Orders tables ======
  private $ORDERS_TABLE = 'orders';
  private $ORDER_DATE_COL = 'created_at';
  private $ORDER_PAY_COL = 'payment_status';
  private $ORDER_PAID_VAL = 'paid';

  // Order items table (auto detect from these names)
  private $ORDER_ITEMS_TABLE_CANDIDATES = ['order_items', 'order_products', 'order_details', 'order_item'];

  // columns inside order-items table (most common)
  private $OI_ORDER_ID_COL = 'order_id';
  private $OI_PRODUCT_ID_COL = 'product_id';
  private $OI_QTY_COL = 'qty'; // if qty not exist we treat as 1

  // product PV column
  private $PRODUCTS_TABLE = 'products';
  private $PRODUCT_ID_COL = 'id';
  private $PRODUCT_PV_COL = 'commission'; // PV stored here

  // ====== Fallback: user_investment + package_config.bv ======
  private $INV_TABLE = 'user_investment';
  private $INV_USER_COL = 'user_id';
  private $INV_DATE_COL = 'created_date';
  private $INV_PACKAGE_COL = 'package_id';
  private $INV_APPROVE_COL = 'approve_status'; // 1 approved
  private $PKG_TABLE = 'package_config';
  private $PKG_ID_COL = 'id';
  private $PKG_BV_COL = 'bv';

  /**
   * Get full dynamic data for Rank & Rewards page
   */
  public function getRankPageData($user_id)
  {

    // 1) rank ladder
    $ladder = $this->db->order_by('rank_order', 'ASC')->get('rank_config')->result();
    if (empty($ladder)) {
      return ['rank' => null, 'rankLadder' => [], 'rewards' => []];
    }

    // 2) current rank
    $currentRank = $this->detectCurrentRank($user_id, $ladder);

    // 3) next rank
    $nextRank = $this->getNextRank($currentRank, $ladder);

    // 4) cycle range
    $cycleType = $nextRank ? ($nextRank->cycle_type ?? 'WEEK') : ($currentRank->cycle_type ?? 'WEEK');
    $range = $this->getCycleRange($cycleType);

    // 5) PV for legs (from paid orders)
    $legs = $this->getLegVolumesPV($user_id, $range['start'], $range['end']);
    $leftPV = $legs['left_pv'];
    $rightPV = $legs['right_pv'];

    // 6) active directs
    $activeDirects = $this->getActiveDirectsCount($user_id);

    // 7) pairs (weak leg)
    $pairValue = (float) ($nextRank->pair_value ?? 1);
    $weakPV = min($leftPV, $rightPV);
    $pairsDone = ($pairValue > 0) ? (int) floor($weakPV / $pairValue) : 0;

    $carryLeft = max(0, (float) ($leftPV - ($pairsDone * $pairValue)));
    $carryRight = max(0, (float) ($rightPV - ($pairsDone * $pairValue)));

    // 8) requirements for next rank
    $reqPairs = (int) ($nextRank->pairs_needed ?? 0);
    $reqDirects = (int) ($nextRank->directs_needed ?? 0);

    // in your rank_config these two columns already exist:
    // left_leg_investment / right_leg_investment => treat as required PV
    $reqLeftPV = (float) ($nextRank->left_leg_investment ?? 0);
    $reqRightPV = (float) ($nextRank->right_leg_investment ?? 0);
    $reqTeamVol = (float) ($nextRank->team_volume_need ?? 0);

    // 9) progress
    $pPairs = $this->pct($pairsDone, $reqPairs);
    $pDirects = $this->pct($activeDirects, $reqDirects);

    $weakSide = ($rightPV <= $leftPV) ? 'Right' : 'Left';
    $weakNeed = ($weakSide === 'Right') ? $reqRightPV : $reqLeftPV;
    $weakDone = ($weakSide === 'Right') ? $rightPV : $leftPV;
    $pWeakPV = $this->pct($weakDone, $weakNeed);

    $overall = (int) round(($pPairs + $pDirects + $pWeakPV) / 3);

    // 10) UI object
    $rank = (object) [
      'current' => $currentRank->rank_name ?? '—',
      'next' => $nextRank->rank_name ?? '—',
      'progress' => $overall,

      'active' => true,
      'kyc' => true,

      'pairs_needed' => $reqPairs,
      'pairs_done' => $pairsDone,

      'directs_needed' => $reqDirects,
      'directs_done' => $activeDirects,

      'left_bv_need' => $reqLeftPV,
      'right_bv_need' => $reqRightPV,
      'left_bv_done' => $leftPV,
      'right_bv_done' => $rightPV,

      'team_volume_need' => $reqTeamVol,
      'team_volume_done' => ($leftPV + $rightPV),

      'cycle_label' => ($cycleType === 'MONTH') ? 'This Month' : 'This Week',
      'carry_left' => $carryLeft,
      'carry_right' => $carryRight,

      'pair_value' => $pairValue,
      'weak_side' => $weakSide
    ];

    $rankLadder = $this->buildRankLadderUI($ladder, $currentRank);

    return [
      'rank' => $rank,
      'rankLadder' => $rankLadder,
      'rewards' => []
    ];
  }

  // ======================== Rank detection ========================

  private function detectCurrentRank($user_id, $ladder)
  {
    $range = $this->getCycleRange('WEEK');
    $legs = $this->getLegVolumesPV($user_id, $range['start'], $range['end']);
    $leftPV = $legs['left_pv'];
    $rightPV = $legs['right_pv'];

    $activeDirects = $this->getActiveDirectsCount($user_id);

    $current = $ladder[0];
    foreach ($ladder as $rk) {
      $okLeft = ((float) $leftPV >= (float) $rk->left_leg_investment);
      $okRight = ((float) $rightPV >= (float) $rk->right_leg_investment);

      $okDirect = true;
      if (isset($rk->directs_needed)) {
        $okDirect = ($activeDirects >= (int) $rk->directs_needed);
      }

      if ($okLeft && $okRight && $okDirect) {
        $current = $rk;
      }
    }
    return $current;
  }

  private function getNextRank($currentRank, $ladder)
  {
    if (!$currentRank)
      return $ladder[0];
    $curOrder = (int) $currentRank->rank_order;
    foreach ($ladder as $rk) {
      if ((int) $rk->rank_order === ($curOrder + 1))
        return $rk;
    }
    return $currentRank;
  }

  private function buildRankLadderUI($ladder, $currentRank)
  {
    $curOrder = (int) ($currentRank->rank_order ?? 0);
    $out = [];
    foreach ($ladder as $rk) {
      $status = 'Locked';
      if ((int) $rk->rank_order < $curOrder)
        $status = 'Unlocked';
      if ((int) $rk->rank_order === $curOrder)
        $status = 'Current';

      $out[$rk->rank_name] = [
        'status' => $status,
        'requirements' => [
          ['label' => 'Pairs', 'value' => ((int) $rk->pairs_needed) . ' / cycle'],
          ['label' => 'Directs', 'value' => ((int) $rk->directs_needed) . ' active'],
          ['label' => 'Left PV', 'value' => ((float) $rk->left_leg_investment)],
          ['label' => 'Right PV', 'value' => ((float) $rk->right_leg_investment)],
          ['label' => 'Team Volume', 'value' => ((float) $rk->team_volume_need)],
        ],
        'benefits' => [
          'Rank bonus: ' . $rk->rank_bonus,
          'Bonus type: ' . $rk->rank_bonus_type,
        ]
      ];
    }
    return $out;
  }

  // ======================== Binary Tree (binary_placement) ========================

  private function getLegVolumesPV($user_id, $startDate, $endDate)
  {
    $leftIds = $this->getDescendantUserIdsBySide($user_id, 'left');
    $rightIds = $this->getDescendantUserIdsBySide($user_id, 'right');

    $leftPV = $this->sumPV($leftIds, $startDate, $endDate);
    $rightPV = $this->sumPV($rightIds, $startDate, $endDate);

    return [
      'left_pv' => (float) $leftPV,
      'right_pv' => (float) $rightPV,
      'left_ids' => $leftIds,
      'right_ids' => $rightIds
    ];
  }

  private function getDescendantUserIdsBySide($rootUserId, $side)
  {
    $first = $this->db->select('user_id')
      ->from('binary_placement')
      ->where('parent_id', $rootUserId)
      ->where('position', strtolower($side))
      ->order_by('id', 'ASC')
      ->get()->row();

    if (!$first)
      return [];

    $all = [];
    $queue = [(int) $first->user_id];

    while (!empty($queue)) {
      $chunk = array_splice($queue, 0, 200);
      foreach ($chunk as $uid)
        $all[$uid] = true;

      $children = $this->db->select('user_id')
        ->from('binary_placement')
        ->where_in('parent_id', $chunk)
        ->get()->result();

      foreach ($children as $c) {
        $cid = (int) $c->user_id;
        if (!isset($all[$cid]))
          $queue[] = $cid;
      }
    }

    return array_keys($all);
  }

  // ======================== PV sum (Paid Orders) ========================

  private function sumPV($userIds, $startDate, $endDate)
  {
    if (empty($userIds))
      return 0;

    // try ORDERS-based PV first
    if ($this->PV_SOURCE === 'orders') {
      $itemsTable = $this->detectOrderItemsTable();
      if ($itemsTable) {
        return $this->sumPVFromOrders($userIds, $startDate, $endDate, $itemsTable);
      }
      // if no items table exists, fallback (optional)
      return $this->sumPVFromInvestmentsFallback($userIds, $startDate, $endDate);
    }

    // fallback mode
    return $this->sumPVFromInvestmentsFallback($userIds, $startDate, $endDate);
  }

  private function detectOrderItemsTable()
  {
    foreach ($this->ORDER_ITEMS_TABLE_CANDIDATES as $t) {
      if ($this->db->table_exists($t))
        return $t;
    }
    return null;
  }

  /**
   * PV = SUM(products.commission * qty) for PAID orders
   */
  private function sumPVFromOrders($userIds, $startDate, $endDate, $itemsTable)
  {

    // if qty column not exist, treat as 1
    $qtyExists = $this->db->field_exists($this->OI_QTY_COL, $itemsTable);
    $qtyExpr = $qtyExists ? "COALESCE(oi.{$this->OI_QTY_COL},1)" : "1";

    $this->db->select("SUM(p.{$this->PRODUCT_PV_COL} * {$qtyExpr}) AS total", false)
      ->from("{$this->ORDERS_TABLE} o")
      ->join("{$itemsTable} oi", "oi.{$this->OI_ORDER_ID_COL} = o.id", "inner")
      ->join("{$this->PRODUCTS_TABLE} p", "p.{$this->PRODUCT_ID_COL} = oi.{$this->OI_PRODUCT_ID_COL}", "inner")
      ->where_in('o.user_id', $userIds)
      ->where("o.{$this->ORDER_PAY_COL}", $this->ORDER_PAID_VAL)
      ->where("o.{$this->ORDER_DATE_COL} >=", $startDate)
      ->where("o.{$this->ORDER_DATE_COL} <=", $endDate);

    $row = $this->db->get()->row();
    return (float) ($row->total ?? 0);
  }

  /**
   * Fallback PV from user_investment + package_config.bv (only if needed)
   */
  private function sumPVFromInvestmentsFallback($userIds, $startDate, $endDate)
  {

    if (!$this->db->table_exists($this->INV_TABLE) || !$this->db->table_exists($this->PKG_TABLE)) {
      return 0;
    }

    $this->db->select("SUM(pkg.{$this->PKG_BV_COL}) AS total", false)
      ->from("{$this->INV_TABLE} inv")
      ->join("{$this->PKG_TABLE} pkg", "pkg.{$this->PKG_ID_COL} = inv.{$this->INV_PACKAGE_COL}", "left")
      ->where_in("inv.{$this->INV_USER_COL}", $userIds)
      ->where("inv.{$this->INV_DATE_COL} >=", $startDate)
      ->where("inv.{$this->INV_DATE_COL} <=", $endDate);

    // approved only (if column exists)
    if ($this->db->field_exists($this->INV_APPROVE_COL, $this->INV_TABLE)) {
      $this->db->where("inv.{$this->INV_APPROVE_COL}", 1);
    }

    $row = $this->db->get()->row();
    return (float) ($row->total ?? 0);
  }

  // ======================== Directs ========================

  private function getActiveDirectsCount($user_id)
  {
    $this->db->from('users')
      ->where('sponser', $user_id)
      ->where('status', 1);

    return (int) $this->db->count_all_results();
  }

  // ======================== Cycle helpers ========================

  private function getCycleRange($cycleType = 'WEEK')
  {
    $now = new DateTime('now');

    if ($cycleType === 'MONTH') {
      $start = (new DateTime('first day of this month'))->setTime(0, 0, 0);
      $end = (new DateTime('last day of this month'))->setTime(23, 59, 59);
    } else {
      $start = clone $now;
      $start->modify('monday this week')->setTime(0, 0, 0);
      $end = clone $now;
      $end->modify('sunday this week')->setTime(23, 59, 59);
    }

    return [
      'start' => $start->format('Y-m-d H:i:s'),
      'end' => $end->format('Y-m-d H:i:s')
    ];
  }

  private function pct($done, $need)
  {
    $need = max(1, (float) $need);
    $done = max(0, (float) $done);
    return (int) max(0, min(100, round(($done / $need) * 100)));
  }
}
