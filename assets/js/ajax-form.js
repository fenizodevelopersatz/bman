/* Minimal placeholder for the template's contact-form handler.
   The original ajax-form.js shipped with the theme is not used by the
   Home / Login / Register pages, so this is intentionally a no-op that
   just prevents a 404 and stops the form from reloading the page. */
(function () {
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form && form.classList && form.classList.contains('banner__form')) {
      e.preventDefault();
    }
  });
})();
