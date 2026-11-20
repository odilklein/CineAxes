/* =============================================================================
   PersonaCut — carrosséis com Swiper 11
   Mousewheel, arraste, toque, teclado e setas. O markup antigo continua sendo
   o fallback nativo (scroll horizontal) se o Swiper não carregar.
   ========================================================================== */
(function () {
  'use strict';

  function iniciar() {
    if (typeof window.Swiper !== 'function') {
      return;
    }

    document.querySelectorAll('[data-slider]:not(.swiper-initialized)').forEach(function (slider) {
      if (slider.swiper) {
        return;
      }

      var track = slider.querySelector('[data-slider-track]');
      var prev = slider.querySelector('[data-slider-prev]');
      var next = slider.querySelector('[data-slider-next]');

      if (!track || track.children.length === 0) {
        return;
      }

      slider.classList.add('swiper');
      track.classList.add('swiper-wrapper');
      Array.prototype.forEach.call(track.children, function (slide) {
        slide.classList.add('swiper-slide');
      });

      var gap = slider.classList.contains('pc-trilha__slider') ? 10 : 14;

      new window.Swiper(slider, {
        slidesPerView: 'auto',
        spaceBetween: gap,
        speed: 380,
        grabCursor: true,
        watchOverflow: true,
        observer: true,
        observeParents: true,
        keyboard: { enabled: true },
        mousewheel: {
          forceToAxis: true,
          releaseOnEdges: true
        },
        navigation: prev && next ? {
          prevEl: prev,
          nextEl: next
        } : undefined,
        a11y: { enabled: true }
      });
    });
  }

  window.pcInitCarrosseis = iniciar;
  iniciar();
}());
