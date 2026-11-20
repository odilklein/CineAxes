/* =============================================================================
   PersonaCut — tema antes do primeiro paint
   Arquivo externo para a CSP poder usar script-src 'self' (sem inline).
   ========================================================================== */
(function () {
  'use strict';

  document.documentElement.classList.add('js');

  try {
    var salvo = localStorage.getItem('pc-theme');
    var tema = salvo === 'dark' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', tema);
    document.documentElement.setAttribute('data-bs-theme', tema);
  } catch (e) { /* modo privado */ }
}());
