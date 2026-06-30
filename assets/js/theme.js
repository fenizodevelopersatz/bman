/* Light / dark theme toggle with localStorage persistence.
   Loaded in <head> so the saved theme is applied before first paint (no flash). */
(function () {
  var KEY = 'site-theme';
  var root = document.documentElement;

  // Apply saved theme as early as possible
  try {
    var saved = localStorage.getItem(KEY);
    if (saved === 'light' || saved === 'dark') {
      root.setAttribute('data-theme', saved);
    }
  } catch (e) {}

  function current() {
    return root.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
  }

  function apply(theme) {
    root.setAttribute('data-theme', theme);
    try { localStorage.setItem(KEY, theme); } catch (e) {}
    var btn = document.querySelector('.theme-toggle');
    if (btn) {
      btn.title = theme === 'light' ? 'Switch to dark theme' : 'Switch to light theme';
      btn.setAttribute('aria-label', btn.title);
    }
  }

  var SUN = '<svg class="icon-sun" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"></path></svg>';
  var MOON = '<svg class="icon-moon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>';

  function build() {
    if (document.querySelector('.theme-toggle')) return;
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'theme-toggle';
    btn.innerHTML = SUN + MOON;
    btn.addEventListener('click', function () {
      apply(current() === 'light' ? 'dark' : 'light');
    });
    document.body.appendChild(btn);
    apply(current());
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', build);
  } else {
    build();
  }

  // Safety net: make sure the preloader never gets stuck on screen,
  // even if other scripts fail to load.
  function hidePreloader() {
    var p = document.getElementById('preloader');
    if (!p) return;
    p.style.transition = 'opacity .4s ease';
    p.style.opacity = '0';
    setTimeout(function () { p.style.display = 'none'; }, 400);
  }
  window.addEventListener('load', hidePreloader);
  setTimeout(hidePreloader, 2000);
})();
