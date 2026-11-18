/* =============================================================================
   PersonaCut — Slider das trilhas
   Substitui a barra de rolagem nativa por setas + arraste, mantendo o scroll
   nativo como fallback (teclado, trackpad e toque continuam funcionando).
   ========================================================================== */
(function () {
  'use strict';

  var sliders = document.querySelectorAll('[data-slider]');

  if (!sliders.length) {
    return;
  }

  sliders.forEach(function (slider) {
    var track = slider.querySelector('[data-slider-track]');
    var prev = slider.querySelector('[data-slider-prev]');
    var next = slider.querySelector('[data-slider-next]');

    if (!track || !prev || !next) {
      return;
    }

    var arrastando = false;
    var inicioX = 0;
    var inicioScroll = 0;
    var moveu = false;

    function passo() {
      return Math.max(130, Math.round(track.clientWidth * 0.82));
    }

    function atualizar() {
      var maximo = track.scrollWidth - track.clientWidth - 2;
      var semScroll = maximo <= 2;

      prev.disabled = semScroll || track.scrollLeft <= 2;
      next.disabled = semScroll || track.scrollLeft >= maximo;
    }

    function ir(direcao) {
      track.scrollBy({ left: direcao * passo(), behavior: 'smooth' });
    }

    prev.addEventListener('click', function () {
      ir(-1);
    });

    next.addEventListener('click', function () {
      ir(1);
    });

    track.addEventListener('scroll', atualizar, { passive: true });
    window.addEventListener('resize', atualizar);
    window.addEventListener('load', atualizar);

    track.addEventListener('pointerdown', function (evento) {
      if (evento.pointerType !== 'mouse') {
        return;
      }

      arrastando = true;
      moveu = false;
      inicioX = evento.clientX;
      inicioScroll = track.scrollLeft;
      track.classList.add('is-drag');
    });

    window.addEventListener('pointermove', function (evento) {
      if (!arrastando) {
        return;
      }

      var delta = evento.clientX - inicioX;

      if (Math.abs(delta) > 4) {
        moveu = true;
      }

      track.scrollLeft = inicioScroll - delta;
    });

    window.addEventListener('pointerup', function () {
      if (!arrastando) {
        return;
      }

      arrastando = false;
      track.classList.remove('is-drag');
      window.setTimeout(atualizar, 80);
    });

    // Um arraste não deve abrir a ficha rápida do card.
    track.addEventListener('click', function (evento) {
      if (!moveu) {
        return;
      }

      evento.preventDefault();
      evento.stopPropagation();
      moveu = false;
    }, true);

    atualizar();
  });
}());
