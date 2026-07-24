<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * rank_badge_html() — the ONE rank badge renderer for the whole platform.
 * Used by the dashboard right panel and the Rank & Rewards page; any other
 * surface that needs to show a member's rank should call this too rather
 * than re-implementing the image/fallback markup.
 *
 * @param string|null $img    staking_ranks.badge_image (relative path) or null
 * @param string|null $color  staking_ranks.badge_color — used for the ring/
 *                            fallback dot when no image is set
 * @param int         $size   px, both width and height
 * @param string      $extraClass  additional class(es) on the wrapper
 * @param bool        $locked      true = dimmed/greyscale (not-yet-earned rank)
 */
function rank_badge_html($img, $color, $size = 42, $extraClass = '', $locked = false)
{
    $color = $color ?: '#9e9e9e';
    $cls = trim('rk-badge ' . ($locked ? 'rk-badge-locked ' : '') . $extraClass);

    if ($img) {
        return '<span class="' . $cls . '" style="width:' . (int)$size . 'px;height:' . (int)$size . 'px;--rk-ring:' . htmlspecialchars($color) . '">'
            . '<img class="rk-badge-img" src="' . base_url($img) . '" alt="" loading="lazy"></span>';
    }
    return '<span class="' . $cls . ' rk-badge-dot" style="width:' . (int)$size . 'px;height:' . (int)$size . 'px;background:' . htmlspecialchars($color) . '"></span>';
}

/**
 * rank_cell_html() — resolve a member's current rank and render its badge + name
 * inline. Pass staking_ranks.id (e.g. user_ranks.current_rank_id); when null or
 * unknown it falls back to the lowest active/base rank ("UN RANK"), matching how
 * the platform shows an un-promoted member. All ranks are cached per request, so
 * this is safe to call once per table row. Reuses rank_badge_html().
 */
function rank_cell_html($rankId = null, $size = 26)
{
    static $ranks = null, $base = null;
    if ($ranks === null) {
        $ranks = [];
        $CI =& get_instance();
        $rows = $CI->db->select('id, name, badge_image, badge_color, tier_level, is_active')
                       ->order_by('tier_level', 'ASC')
                       ->get('staking_ranks')->result_array();
        foreach ($rows as $r) {
            $ranks[(int)$r['id']] = $r;
            if ($base === null && (int)$r['is_active'] === 1) $base = $r;
        }
    }
    $r = ($rankId && isset($ranks[(int)$rankId])) ? $ranks[(int)$rankId] : $base;
    if (!$r) return '<span class="text-muted">—</span>';
    return '<span style="display:inline-flex;align-items:center;gap:8px;">'
         . rank_badge_html($r['badge_image'], $r['badge_color'], $size)
         . '<span class="fw-bold">' . htmlspecialchars($r['name']) . '</span></span>';
}

/**
 * Minimal CSS for rank_badge_html() output — for admin pages that don't already
 * load the rank stylesheet. Echo once per page. Mirrors user_style.php.
 */
function rank_badge_css()
{
    return '<style>'
        . '.rk-badge{display:inline-flex;align-items:center;justify-content:center;border-radius:10px;flex:0 0 auto;overflow:hidden;}'
        . '.rk-badge-img{width:100%;height:100%;object-fit:contain;border-radius:inherit;}'
        . '.rk-badge-dot{border-radius:50%;display:inline-block;}'
        . '.rk-badge-locked{opacity:.4;filter:grayscale(1);}'
        . '</style>';
}
