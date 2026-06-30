<?php
class CacheControlHook {
    public function disableCache() {
        $CI =& get_instance();

        // Disable caching for all controllers
        $CI->output->cache(0);

        // Add cache control headers
        $CI->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $CI->output->set_header('Pragma: no-cache');
    }
}
?>