/* =============================================================================
   PersonaCut — tema antes do primeiro paint
   Arquivo externo para a CSP poder usar script-src 'self' (sem inline).
   ========================================================================== */
(function () {
  'use strict';

  document.documentElement.classList.add('js');

  try {
    var salvo = localStorage.getItem('pc-theme');
    document.documentElement.setAttribute('data-theme', salvo === 'dark' ? 'dark' : 'light');
  } catch (e) { /* modo privado */ }
}());
