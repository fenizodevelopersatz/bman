<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!-- Browser Controls (proposal §16): disable right-click, F12, Ctrl+Shift+I/J/C, Ctrl+U.
     Included from the common footers/scripts so it applies site-wide. Guarded so
     it binds only once even if a page pulls in more than one common partial. -->
<script>
(function () {
  if (window.__bmanBrowserGuard) return;
  window.__bmanBrowserGuard = 1;

  // Disable right-click context menu
  document.addEventListener('contextmenu', function (e) { e.preventDefault(); return false; }, { capture: true });

  // Disable dev-tools / view-source key combinations
  document.addEventListener('keydown', function (e) {
    var k = (e.key || '').toLowerCase();
    // F12
    if (e.keyCode === 123 || k === 'f12') { e.preventDefault(); return false; }
    // Ctrl+U  (view source)
    if (e.ctrlKey && !e.shiftKey && k === 'u') { e.preventDefault(); return false; }
    // Ctrl+Shift+I / J / C  (open dev tools / inspector / console)
    if (e.ctrlKey && e.shiftKey && (k === 'i' || k === 'j' || k === 'c')) { e.preventDefault(); return false; }
  }, { capture: true });
})();
</script>
