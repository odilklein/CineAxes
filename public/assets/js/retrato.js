/* =============================================================================
   PersonaCut — retrato redigido por IA
   Gera uma vez por código; se já existir no servidor, chega pronto no HTML.
   ========================================================================== */
(function () {
  'use strict';

  var secao = document.querySelector('[data-retrato]');

  if (!secao) {
    return;
  }

  var caixa = secao.querySelector('[data-retrato-texto]');
  var codigo = secao.getAttribute('data-codigo') || '';

  if (!caixa || !/^[A-Z0-9]{8}$/.test(codigo)) {
    return;
  }

  // Já veio do banco no primeiro paint.
  if (!caixa.querySelector('.pc-critico__loading')) {
    return;
  }

  fetch('/api/retrato.php?codigo=' + encodeURIComponent(codigo), {
    headers: { 'Accept': 'application/json' },
    credentials: 'same-origin'
  }).then(function (resposta) {
    return resposta.json().then(function (dados) {
      return { status: resposta.status, dados: dados };
    });
  }).then(function (resultado) {
    if (resultado.status !== 200 || !resultado.dados || !resultado.dados.ok || !resultado.dados.texto) {
      throw new Error('retrato');
    }

    caixa.innerHTML = '';

    resultado.dados.texto.split(/\n{2,}/).forEach(function (paragrafo) {
      var texto = paragrafo.trim();

      if (texto === '') {
        return;
      }

      var p = document.createElement('p');
      p.textContent = texto;
      caixa.appendChild(p);
    });

    secao.classList.add('is-ready');
  }).catch(function () {
    caixa.innerHTML = '';
    var p = document.createElement('p');
    p.className = 'pc-critico__fallback';
    p.textContent = secao.getAttribute('data-erro') || 'O retrato redigido não está disponível agora.';
    caixa.appendChild(p);
  });
}());
