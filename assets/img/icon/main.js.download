(function ($) {
	"use strict";


/*===========================================
	=            Windows Load          =
=============================================*/
$(window).on('load', function () {
    preloader();
    wowAnimation();
    aosAnimation();
});


/*===========================================
	=            Preloader          =
=============================================*/
function preloader() {
	$('#preloader').delay(0).fadeOut();
};


/*===========================================
	=    		Mobile Menu			      =
=============================================*/
//SubMenu Dropdown Toggle
if ($('.tgmenu__wrap li.menu-item-has-children ul').length) {
	$('.tgmenu__wrap .navigation li.menu-item-has-children').append('<div class="dropdown-btn"><span class="plus-line"></span></div>');
}

//Mobile Nav Hide Show
if ($('.tgmobile__menu').length) {

	var mobileMenuContent = $('.tgmenu__wrap .tgmenu__main-menu').html();
	$('.tgmobile__menu .tgmobile__menu-box .tgmobile__menu-outer').append(mobileMenuContent);

	//Dropdown Button
	$('.tgmobile__menu li.menu-item-has-children .dropdown-btn').on('click', function () {
		$(this).toggleClass('open');
		$(this).prev('ul').slideToggle(300);
	});
	//Menu Toggle Btn
	$('.mobile-nav-toggler').on('click', function () {
		$('body').addClass('mobile-menu-visible');
	});

	//Menu Toggle Btn
	$('.tgmobile__menu-backdrop, .tgmobile__menu .close-btn, .tgmobile__menu .section-link').on('click', function () {
		$('body').removeClass('mobile-menu-visible');
	});
};


/*===========================================
	=     Menu sticky & Scroll to top      =
=============================================*/
$(window).on('scroll', function () {
	var scroll = $(window).scrollTop();
	if (scroll < 245) {
		$("#sticky-header").removeClass("sticky-menu");
		$('.scroll-to-target').removeClass('open');
        $("#header-fixed-height").removeClass("active-height");

	} else {
		$("#sticky-header").addClass("sticky-menu");
		$('.scroll-to-target').addClass('open');
        $("#header-fixed-height").addClass("active-height");
	}
});


/*===========================================
	=           Scroll Up  	         =
=============================================*/
if ($('.scroll-to-target').length) {
  $(".scroll-to-target").on('click', function () {
    var target = $(this).attr('data-target');
    // animate
    $('html, body').animate({
      scrollTop: $(target).offset().top
    }, 600);

  });
}


/*===========================================
	=          Data Background    =
=============================================*/
$("[data-background]").each(function () {
	$(this).css("background-image", "url(" + $(this).attr("data-background") + ")")
});

$("[data-bg-color]").each(function () {
	$(this).css("background-color", $(this).attr("data-bg-color"));
});



/*=============================================
	=        Brand Active		      =
=============================================*/
var brandSwiper = new Swiper('.brand-active', {
    // Optional parameters
    slidesPerView: 6,
    spaceBetween: 24,
    loop: true,
    autoplay: {
        delay: 4000,
    },
    breakpoints: {
        '1500': {
            slidesPerView: 6,
        },
        '1200': {
            slidesPerView: 5,
        },
        '992': {
            slidesPerView: 4,
        },
        '768': {
            slidesPerView: 3,
        },
        '576': {
            slidesPerView: 3,
        },
        '0': {
            slidesPerView: 2.5,
        },
    },
});


/*=============================================
	=        Demo Active		      =
=============================================*/
var swiper = new Swiper(".brand-active-two", {
    loop: true,
    freemode: true,
    slidesPerView: 6,
    spaceBetween: 30,
    centeredSlides: true,
    allowTouchMove: false,
    speed: 6000,
    autoplay: {
        delay: 1,
        disableOnInteraction: true,
    },
    breakpoints: {
        '1500': {
            slidesPerView: 7,
        },
        '1200': {
            slidesPerView: 4.5,
        },
        '992': {
            slidesPerView: 3.5,
        },
        '768': {
            slidesPerView: 3,
        },
        '576': {
            slidesPerView: 3,
            spaceBetween: 20,
        },
        '0': {
            slidesPerView: 1.8,
            spaceBetween: 20,
        },
    },
});


/*=============================================
	=        Demo Active		      =
=============================================*/
var swiper = new Swiper(".brand-active-three", {
    loop: true,
    freemode: true,
    slidesPerView: 'auto',
    spaceBetween: 20,
    centeredSlides: true,
    allowTouchMove: false,
    speed: 5000,
    autoplay: {
        delay: 1,
        disableOnInteraction: true,
    },
});

/*=============================================
	=        Testimonial Active		      =
=============================================*/
var brandSwiper = new Swiper('.testimonial-active', {
    // Optional parameters
    slidesPerView: 3,
    spaceBetween: 30,
    loop: true,
    autoplay: {
        delay: 6000,
    },
    breakpoints: {
        '1500': {
            slidesPerView: 3,
        },
        '1200': {
            slidesPerView: 3,
        },
        '992': {
            slidesPerView: 3,
        },
        '768': {
            slidesPerView: 2,
        },
        '576': {
            slidesPerView: 1,
        },
        '0': {
            slidesPerView: 1,
        },
    },
});


/*=============================================
	=        Related Active		      =
=============================================*/
var brandSwiper = new Swiper('.related-post-active', {
    // Optional parameters
    slidesPerView: 3,
    spaceBetween: 30,
    loop: true,
    autoplay: {
        delay: 6000,
    },
    breakpoints: {
        '1500': {
            slidesPerView: 3,
        },
        '1200': {
            slidesPerView: 3,
        },
        '992': {
            slidesPerView: 3,
        },
        '768': {
            slidesPerView: 2,
        },
        '576': {
            slidesPerView: 1,
        },
        '0': {
            slidesPerView: 1,
        },
    },
    navigation: {
        nextEl: ".related-button-next",
        prevEl: ".related-button-prev"
    },
});


/*=============================================
	=    	  Countdown Active  	         =
=============================================*/
$('[data-countdown]').each(function () {
	var $this = $(this), finalDate = $(this).data('countdown');
	$this.countdown(finalDate, function (event) {
		$this.html(event.strftime('<div class="time-count day"><span>%D</span>Days</div><div class="time-count hour"><span>%H</span>hour</div><div class="time-count min"><span>%M</span>minute</div><div class="time-count sec"><span>%S</span>second</div>'));
	});
});



/*===========================================
	=         Marquee Active         =
=============================================*/
if ($(".marquee_mode").length) {
    $('.marquee_mode').marquee({
        speed: 20,
        gap: 0,
        delayBeforeStart: 0,
        direction: 'left',
        duplicated: true,
        pauseOnHover: true,
        startVisible:true,
    });
}

/*=============================================
	=        Counter Up 	       =
=============================================*/
$(".counter-number").counterUp({
    delay: 10,
    time: 2000,
});


/*=============================================
	=          Team Hover Active               =
=============================================*/
if (window.innerWidth > 991) {
	$(".team__item-three").on("mouseenter", function () {
		$(".team__item-three").removeClass("active"),
		$(this).addClass("active")
	});
}






/*=============================================
	=          One page Menu               =
=============================================*/
var scrollLink = $('.section-link');
// Active link switching
$(window).on('scroll', function () {
	var scrollbarLocation = $(this).scrollTop();

	scrollLink.each(function () {

		var sectionOffset = $(this.hash).offset().top - 120;

		if (sectionOffset <= scrollbarLocation) {
			$(this).parent().addClass('active');
			$(this).parent().siblings().removeClass('active');
		}
	});
});
//jQuery for page scrolling feature - requires jQuery Easing plugin
$(function () {
	$('a.section-link[href*="#"]:not([href="#"])').on('click', function () {
		if (location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '') && location.hostname == this.hostname) {
			var target = $(this.hash);
			target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');
			if (target.length) {
				$('html, body').animate({
					scrollTop: (target.offset().top - 50)
                }, 1200, "easeInOutExpo");
				return false;
			}
		}
	});
});


// jQuery script
$('.copy-btn').on('click', function() {
  const codeText = $('.copy-text mark').text();
  navigator.clipboard.writeText(codeText).then(() => {
    $(this).text('✔');
    setTimeout(() => $(this).text('❏'), 2000);
  }).catch(err => console.error('Copy failed:', err));
});




/*===========================================
	=        Wow Active      =
=============================================*/
function wowAnimation() {
	var wow = new WOW({
		boxClass: 'wow',
		animateClass: 'animated',
		offset: 0,
		mobile: false,
		live: true
	});
	wow.init();
}


/*===========================================
	=           Aos Active       =
=============================================*/
function aosAnimation() {
	AOS.init({
		duration: 1000,
		mirror: true,
		once: true,
		disable: 'mobile',
	});
}


/*===========================================
	=           Progressbar Active       =
=============================================*/
const $section = $(".about__area");
const $bar = $(".progress__bar span");
const $wrap = $(".about__list-wrap");
const $lists = $(".about__list");

// safety check
if (!$section.length || !$bar.length || !$wrap.length || !$lists.length) {
    return;
}

let startOffset = $(window).height() * 0.25;

function updateProgress() {
    const rect = $section[0].getBoundingClientRect();
    const windowHeight = window.innerHeight;

    const total = rect.height + windowHeight;
    const visible = windowHeight - rect.top + startOffset;

    let progress = visible / total;
    progress = Math.max(0, Math.min(1, progress));

    const barHeight = progress * $wrap.outerHeight();
    $bar.css("height", (progress * 100) + "%");

    $lists.each(function () {
        const $list = $(this);
        const listTop = $list.position().top;

        if (barHeight >= listTop) {
            $list.addClass("active");
        } else {
            $list.removeClass("active");
        }
    });
}

// scroll + resize
$(window).on("scroll", function () {
    requestAnimationFrame(updateProgress);
});

$(window).on("resize", function () {
    startOffset = $(window).height() * 0.25;
    updateProgress();
});


})(jQuery);