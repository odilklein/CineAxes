/* =============================================================================
   PersonaCut — dashboard de perfil
   Anima as barras duplas depois do primeiro paint.
   ========================================================================== */
(function () {
  'use strict';

  var barras = document.querySelectorAll('[data-bar]');

  if (!barras.length) {
    return;
  }

  window.requestAnimationFrame(function () {
    window.setTimeout(function () {
      barras.forEach(function (barra) {
        barra.style.width = (barra.getAttribute('data-bar') || '0') + '%';
      });
    }, 140);
  });
}());
