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
