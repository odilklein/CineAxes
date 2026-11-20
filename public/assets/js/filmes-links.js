/* =============================================================================
   PersonaCut — filmes citados viram links de ficha rápida
   Usa o mapa data-filmes-mapa (título -> payload do quickview) e transforma
   ocorrências em botões [data-quickview] dentro dos textos editoriais.
   ========================================================================== */
(function () {
  'use strict';

  var mapaEl = document.querySelector('[data-filmes-mapa]');

  if (!mapaEl) {
    return;
  }

  var mapa;

  try {
    mapa = JSON.parse(mapaEl.textContent);
  } catch (erro) {
    return;
  }

  var chaves = Object.keys(mapa || {}).filter(function (chave) {
    return chave.length >= 4;
  }).sort(function (a, b) {
    return b.length - a.length;
  });

  if (!chaves.length) {
    return;
  }

  function escapar(texto) {
    return texto.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  var regex = new RegExp('(' + chaves.map(escapar).join('|') + ')', 'giu');

  var alvos = [
    '[data-retrato-texto] p',
    '.pc-critico__texto p',
    '.pc-reading__body',
    '.pc-insight p',
    '.pc-profile-card__desc',
    '[data-narrativa-arquetipo]',
    '[data-narrativa-leitura]',
    '.pc-retro__lead[data-narrativa-leitura]',
    '.pc-retro__lead[data-narrativa-arquetipo]'
  ].join(',');

  function linkificar(raiz) {
    var caminhante = document.createTreeWalker(raiz, NodeFilter.SHOW_TEXT, {
      acceptNode: function (no) {
        if (!no.nodeValue || !no.nodeValue.trim()) {
          return NodeFilter.FILTER_REJECT;
        }

        var pai = no.parentElement;

        if (!pai || pai.closest('.pc-film-link, a, button')) {
          return NodeFilter.FILTER_REJECT;
        }

        return NodeFilter.FILTER_ACCEPT;
      }
    });

    var textos = [];

    while (caminhante.nextNode()) {
      textos.push(caminhante.currentNode);
    }

    textos.forEach(function (no) {
      var texto = no.nodeValue;
      var ultimo = 0;
      var achado;
      var fragmento = document.createDocumentFragment();

      regex.lastIndex = 0;

      while ((achado = regex.exec(texto)) !== null) {
        var chave = achado[0].toLowerCase();
        var item = mapa[chave];

        if (!item) {
          continue;
        }

        if (achado.index > ultimo) {
          fragmento.appendChild(document.createTextNode(texto.slice(ultimo, achado.index)));
        }

        var botao = document.createElement('button');
        botao.type = 'button';
        botao.className = 'pc-film-link';
        botao.textContent = achado[0];
        botao.setAttribute('data-quickview', JSON.stringify(item));
        botao.setAttribute('aria-label', 'Abrir ficha de ' + (item.titulo || achado[0]));
        fragmento.appendChild(botao);

        ultimo = regex.lastIndex;
      }

      if (ultimo === 0) {
        return;
      }

      if (ultimo < texto.length) {
        fragmento.appendChild(document.createTextNode(texto.slice(ultimo)));
      }

      no.parentNode.replaceChild(fragmento, no);
    });
  }

  function aplicar() {
    document.querySelectorAll(alvos).forEach(function (alvo) {
      linkificar(alvo);
    });
  }

  document.addEventListener('pc:narrativa', aplicar);
  window.setTimeout(aplicar, 100);
}());
