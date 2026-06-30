<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Wrap the first occurrence of $word inside $text with <span>…</span>
 * (the Webze template highlights a word via <span> in every heading).
 */
if (!function_exists('lp_hl')) {
    function lp_hl($text, $word)
    {
        $text = (string)$text;
        if ($word === '' || $word === null) return html_escape($text);
        $pos = stripos($text, $word);
        if ($pos === false) return html_escape($text);
        $before = html_escape(substr($text, 0, $pos));
        $match  = html_escape(substr($text, $pos, strlen($word)));
        $after  = html_escape(substr($text, $pos + strlen($word)));
        return $before . '<span>' . $match . '</span>' . $after;
    }
}

/** Safe array getter for section arrays passed to the view. */
if (!function_exists('lp')) {
    function lp($arr, $key, $default = '')
    {
        return isset($arr[$key]) && $arr[$key] !== '' ? $arr[$key] : $default;
    }
}

/** Prefix a stored relative asset path with base_url (handles absolute urls). */
if (!function_exists('lp_asset')) {
    function lp_asset($path)
    {
        if (!$path) return '';
        if (preg_match('#^(https?:)?//#', $path)) return $path;
        return base_url($path);
    }
}
