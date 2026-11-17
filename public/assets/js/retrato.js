/* =============================================================================
   PersonaCut — retrato redigido por IA
   Gera uma vez por código; se já existir no servidor, chega pronto no HTML.
   A página pode ter mais de um bloco (análise completa + retrospectiva) e os
   blocos marcados com data-retrato-lazy só buscam quando a retrospectiva
   pede, no capítulo da crítica.
   ========================================================================== */
(function () {
  'use strict';

  var secoes = Array.prototype.slice.call(document.querySelectorAll('[data-retrato]'));

  if (!secoes.length) {
    return;
  }

  var pedidos = {};

  function caixa(secao) {
    return secao.querySelector('[data-retrato-texto]');
  }

  function jaTemTexto(secao) {
    var alvo = caixa(secao);

    return !!alvo && !alvo.querySelector('.pc-critico__loading');
  }

  function preencher(secao, texto) {
    var alvo = caixa(secao);

    if (!alvo) {
      return;
    }

    alvo.innerHTML = '';

    texto.split(/\n{2,}/).forEach(function (paragrafo) {
      var limpo = paragrafo.trim();

      if (limpo === '') {
        return;
      }

      var p = document.createElement('p');
      p.textContent = limpo;
      alvo.appendChild(p);
    });

    secao.classList.add('is-ready');
  }

  function falhar(secao) {
    var alvo = caixa(secao);

    if (!alvo) {
      return;
    }

    alvo.innerHTML = '';

    var p = document.createElement('p');
    p.className = 'pc-critico__fallback';
    p.textContent = secao.getAttribute('data-erro') || 'O retrato redigido não está disponível agora.';
    alvo.appendChild(p);
  }

  function buscar(codigo) {
    if (pedidos[codigo]) {
      return pedidos[codigo];
    }

    var alvos = secoes.filter(function (secao) {
      return secao.getAttribute('data-codigo') === codigo && !jaTemTexto(secao);
    });

    if (!alvos.length) {
      return Promise.resolve();
    }

    pedidos[codigo] = fetch('/api/retrato.php?codigo=' + encodeURIComponent(codigo), {
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

      alvos.forEach(function (secao) {
        preencher(secao, resultado.dados.texto);
      });
    }).catch(function () {
      alvos.forEach(falhar);
      delete pedidos[codigo];
    });

    return pedidos[codigo];
  }

  function iniciar(secao) {
    var codigo = secao.getAttribute('data-codigo') || '';

    if (!/^[A-Z0-9]{8}$/.test(codigo) || jaTemTexto(secao)) {
      return;
    }

    buscar(codigo);
  }

  secoes.forEach(function (secao) {
    if (!secao.hasAttribute('data-retrato-lazy')) {
      iniciar(secao);
    }
  });

  document.addEventListener('pc:retrato', function () {
    secoes.forEach(iniciar);
  });
}());
