/* =============================================================================
   PersonaCut — slider dos perfis de exemplo na landing
   Um iPhone por perfil, com setas, bolinhas e passagem automática. O Swiper é
   opcional: sem ele, o trilho continua rolando na horizontal por toque/scroll.
   ========================================================================== */
(function () {
  'use strict';

  function iniciar() {
    var raiz = document.querySelector('[data-phone-slider]');

    if (!raiz || raiz.dataset.phoneReady === '1') {
      return;
    }

    var trilho = raiz.querySelector('[data-phone-track]');

    if (!trilho) {
      return;
    }

    var slides = Array.prototype.slice.call(trilho.children);

    if (slides.length < 2) {
      Array.prototype.forEach.call(
        raiz.querySelectorAll('[data-phone-prev], [data-phone-next]'),
        function (botao) { botao.remove(); }
      );

      return;
    }

    if (typeof window.Swiper !== 'function') {
      return;
    }

    raiz.classList.add('swiper');
    trilho.classList.add('swiper-wrapper');
    slides.forEach(function (slide) {
      slide.classList.add('swiper-slide');
    });

    new window.Swiper(raiz, {
      slidesPerView: 1,
      spaceBetween: 18,
      speed: 480,
      loop: true,
      grabCursor: true,
      watchOverflow: true,
      keyboard: { enabled: true },
      autoplay: {
        delay: 6000,
        disableOnInteraction: true
      },
      navigation: {
        prevEl: raiz.querySelector('[data-phone-prev]'),
        nextEl: raiz.querySelector('[data-phone-next]')
      },
      pagination: {
        el: document.querySelector('[data-phone-pagination]'),
        clickable: true
      },
      a11y: { enabled: true }
    });

    raiz.dataset.phoneReady = '1';
  }

  window.pcInitPhoneSlider = iniciar;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciar);
  } else {
    iniciar();
  }
}());
