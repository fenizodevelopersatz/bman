/* Rebuilt init script for the Webze template (Home / Login / Register).
   The original main.js was missing from this copy of the template, which
   left the preloader stuck and the menu / sliders / animations dead.
   Everything here is wrapped defensively so a single missing or
   incompatible plugin can never break the rest of the page again. */
(function ($) {
  "use strict";

  function safe(label, fn) {
    try { fn(); } catch (e) { if (window.console) console.warn("[main.js] " + label + " skipped:", e.message); }
  }

  /* ---- data-background images (hero bg, login image, etc.) ---- */
  safe("data-background", function () {
    $("[data-background]").each(function () {
      $(this).css("background-image", "url(" + $(this).attr("data-background") + ")");
    });
  });

  /* ---- Preloader ---- */
  $(window).on("load", function () {
    safe("preloader", function () {
      $("#preloader").fadeOut(400);
    });
  });

  $(function () {

    /* ---- Sticky header ---- */
    safe("sticky-header", function () {
      var $header = $("#sticky-header");
      $(window).on("scroll", function () {
        if ($(window).scrollTop() > 100) {
          $header.addClass("sticky-menu");
        } else {
          $header.removeClass("sticky-menu");
        }
      });
    });

    /* ---- Mobile menu ---- */
    safe("mobile-menu", function () {
      // Clone the main navigation into the mobile menu container.
      var $menu = $(".tgmenu__main-menu .navigation").clone();
      $(".tgmobile__menu-outer").append($menu);

      // Dropdown arrows for any items that have a submenu.
      $(".tgmobile__menu .navigation li.menu-item-has-children").each(function () {
        $(this).children("a").append('<span class="dropdown-btn"><i class="fas fa-angle-down"></i></span>');
      });
      $(".tgmobile__menu").on("click", ".dropdown-btn", function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).toggleClass("open");
        $(this).closest("li").children(".sub-menu").slideToggle(300);
      });

      function openMenu() { $("body").addClass("mobile-menu-visible"); }
      function closeMenu() { $("body").removeClass("mobile-menu-visible"); }

      $(".mobile-nav-toggler").on("click", openMenu);
      $(".tgmobile__menu .close-btn").on("click", closeMenu);
      $(".tgmobile__menu-backdrop").on("click", closeMenu);
    });

    /* ---- Smooth scroll for in-page section links ---- */
    safe("section-link", function () {
      $(".section-link").on("click", function (e) {
        var href = $(this).attr("href") || "";
        if (href.charAt(0) === "#" && href.length > 1 && $(href).length) {
          e.preventDefault();
          $("html, body").animate({ scrollTop: $(href).offset().top - 80 }, 600);
        }
      });
    });

    /* ---- Brand / logo slider ---- */
    safe("swiper", function () {
      if (typeof Swiper === "undefined") return;
      if ($(".brand-active").length) {
        new Swiper(".brand-active", {
          loop: true,
          slidesPerView: 5,
          spaceBetween: 30,
          autoplay: { delay: 2500, disableOnInteraction: false },
          breakpoints: {
            0:    { slidesPerView: 2 },
            576:  { slidesPerView: 3 },
            768:  { slidesPerView: 4 },
            992:  { slidesPerView: 5 }
          }
        });
      }
    });

    /* ---- Countdown ---- */
    safe("countdown", function () {
      if (!$.fn.countdown) return;
      $("[data-countdown]").each(function () {
        var $el = $(this);
        var finalDate = $el.attr("data-countdown");
        $el.countdown(finalDate, function (event) {
          $el.html(event.strftime(
            '<div class="time-count"><span>%D</span> Days</div>' +
            '<div class="time-count"><span>%H</span> Hrs</div>' +
            '<div class="time-count"><span>%M</span> Min</div>' +
            '<div class="time-count"><span>%S</span> Sec</div>'
          ));
        });
      });
    });

    /* ---- Marquee ---- */
    safe("marquee", function () {
      if (!$.fn.marquee) return;
      $(".marquee_mode").marquee({
        duration: 12000,
        gap: 50,
        delayBeforeStart: 0,
        direction: "left",
        duplicated: true,
        startVisible: true
      });
    });

    /* ---- Counter up ---- */
    safe("counterup", function () {
      if (!$.fn.counterUp) return;
      $(".count").counterUp({ delay: 10, time: 1000 });
    });

    /* ---- Copy-to-clipboard (token wallet) ---- */
    safe("copy-btn", function () {
      $(".copy-btn").on("click", function () {
        var text = $(this).closest(".copy-text").find("mark").text();
        if (navigator.clipboard) { navigator.clipboard.writeText(text); }
      });
    });

    /* ---- Scroll to top ---- */
    safe("scroll-top", function () {
      var $btn = $(".scroll__top");
      $(window).on("scroll", function () {
        if ($(window).scrollTop() > 400) { $btn.addClass("open"); }
        else { $btn.removeClass("open"); }
      });
      $(".scroll-to-target").on("click", function (e) {
        e.preventDefault();
        $("html, body").animate({ scrollTop: 0 }, 600);
      });
    });

    /* ---- WOW animations ---- */
    safe("wow", function () {
      if (typeof WOW !== "undefined") { new WOW().init(); }
    });

    /* ---- AOS animations ---- */
    safe("aos", function () {
      if (typeof AOS !== "undefined") {
        AOS.init({ duration: 800, once: true });
        AOS.refreshHard();
      }
    });
  });

})(jQuery);
