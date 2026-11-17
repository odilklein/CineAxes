/* =============================================================================
   PersonaCut — retrospectiva em capítulos
   Mostra um capítulo por vez, com progresso, toque, swipe e teclado. Todo o
   conteúdo já vem renderizado no HTML: sem JS, a capa e o link para a análise
   completa continuam funcionando.
   ========================================================================== */
(function () {
  'use strict';

  var raiz = document.querySelector('[data-retro]');

  if (!raiz) {
    return;
  }

  var capitulos = Array.prototype.slice.call(raiz.querySelectorAll('[data-chapter]'));
  var segmentos = Array.prototype.slice.call(raiz.querySelectorAll('[data-retro-goto]'));
  var palco = raiz.querySelector('[data-retro-stage]');
  var status = raiz.querySelector('[data-retro-status]');
  var contador = raiz.querySelector('[data-retro-count]');
  var modelo = contador ? (contador.getAttribute('data-template') || '{atual} de {total}') : '{atual} de {total}';
  var total = capitulos.length;
  var atual = 0;
  var animouRaridade = false;
  var reduzir = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  var fora = Array.prototype.slice.call(document.querySelectorAll(
    '.pc-header, .pc-footer, .pc-skip, .pc-result'
  ));

  function urlDoCapitulo(indice) {
    return window.location.pathname + window.location.search + '#cap-' + (indice + 1);
  }

  function tituloDoCapitulo(capitulo) {
    var id = capitulo.getAttribute('aria-labelledby');
    var alvo = id ? document.getElementById(id) : null;

    return alvo ? alvo.textContent.trim().replace(/\s+/g, ' ') : '';
  }

  function textoDoContador(indice) {
    return modelo.replace('{atual}', String(indice + 1)).replace('{total}', String(total));
  }

  function pintarProgresso() {
    segmentos.forEach(function (segmento, indice) {
      segmento.classList.toggle('is-done', indice < atual);
      segmento.classList.toggle('is-current', indice === atual);

      if (indice === atual) {
        segmento.setAttribute('aria-current', 'step');
      } else {
        segmento.removeAttribute('aria-current');
      }
    });

    if (contador) {
      contador.textContent = textoDoContador(atual);
    }
  }

  function animarRaridade(capitulo) {
    var numero = capitulo.querySelector('[data-retro-count-to]');

    if (!numero || animouRaridade) {
      return;
    }

    animouRaridade = true;

    var alvo = parseInt(numero.getAttribute('data-retro-count-to'), 10);

    if (reduzir || !isFinite(alvo) || alvo < 2 || !window.requestAnimationFrame) {
      return;
    }

    numero.textContent = '0';

    var inicio = 0;
    var duracao = 900;

    function passo(agora) {
      if (!inicio) {
        inicio = agora;
      }

      var progresso = Math.min(1, (agora - inicio) / duracao);
      var suave = 1 - Math.pow(1 - progresso, 3);

      numero.textContent = Math.round(alvo * suave).toLocaleString('pt-BR');

      if (progresso < 1) {
        window.requestAnimationFrame(passo);
      } else {
        numero.textContent = alvo.toLocaleString('pt-BR');
      }
    }

    window.requestAnimationFrame(passo);
  }

  function pedirRetrato() {
    document.dispatchEvent(new CustomEvent('pc:retrato'));
  }

  function ir(indice) {
    var novo = Math.max(0, Math.min(total - 1, indice));

    capitulos.forEach(function (capitulo, i) {
      var ativo = i === novo;

      capitulo.hidden = !ativo;
      capitulo.classList.toggle('is-active', ativo);

      if (ativo) {
        capitulo.removeAttribute('aria-hidden');
      } else {
        capitulo.setAttribute('aria-hidden', 'true');
      }
    });

    atual = novo;
    pintarProgresso();

    var ativo = capitulos[atual];
    var titulo = tituloDoCapitulo(ativo);

    if (status) {
      status.textContent = textoDoContador(atual) + (titulo !== '' ? ': ' + titulo : '');
    }

    if (ativo.hasAttribute('data-retro-critic') || atual >= 4) {
      pedirRetrato();
    }

    animarRaridade(ativo);

    Array.prototype.forEach.call(raiz.querySelectorAll('[data-retro-prev]'), function (botao) {
      botao.disabled = atual === 0;
    });

    Array.prototype.forEach.call(raiz.querySelectorAll('[data-retro-next]'), function (botao) {
      botao.disabled = atual === total - 1;
    });

    try {
      window.history.replaceState(null, '', urlDoCapitulo(atual));
    } catch (e) { /* file:// e navegadores antigos */ }
  }

  function fechar() {
    raiz.hidden = true;
    document.body.classList.remove('pc-story-open');
    document.documentElement.classList.remove('pc-story-open');

    fora.forEach(function (elemento) {
      elemento.removeAttribute('inert');
      elemento.removeAttribute('aria-hidden');
    });

    // Se o retrato nunca foi pedido na retrospectiva, a análise completa
    // ainda precisa buscá-lo.
    pedirRetrato();

    try {
      var url = new URL(window.location.href);
      url.searchParams.set('modo', 'completo');
      url.hash = '';
      window.history.replaceState(null, '', url.toString());
    } catch (e) { /* ignora */ }

    var analise = document.querySelector('.pc-result');

    if (analise) {
      analise.setAttribute('tabindex', '-1');
      analise.focus({ preventScroll: true });
      analise.scrollIntoView({ block: 'start' });
    }
  }

  /* ---------------------------- Navegação --------------------------------- */
  document.body.classList.add('pc-story-open');
  document.documentElement.classList.add('pc-story-open');

  fora.forEach(function (elemento) {
    elemento.setAttribute('inert', '');
    elemento.setAttribute('aria-hidden', 'true');
  });

  segmentos.forEach(function (segmento) {
    segmento.addEventListener('click', function () {
      ir(parseInt(segmento.getAttribute('data-retro-goto'), 10) || 0);
    });
  });

  Array.prototype.forEach.call(raiz.querySelectorAll('[data-retro-next]'), function (botao) {
    botao.addEventListener('click', function () {
      ir(atual + 1);
    });
  });

  Array.prototype.forEach.call(raiz.querySelectorAll('[data-retro-prev]'), function (botao) {
    botao.addEventListener('click', function () {
      ir(atual - 1);
    });
  });

  Array.prototype.forEach.call(raiz.querySelectorAll('[data-retro-close]'), function (link) {
    link.addEventListener('click', function (evento) {
      evento.preventDefault();
      fechar();
    });
  });

  /* ------------------------- Toque, swipe e teclado ----------------------- */
  var inicioX = 0;
  var inicioY = 0;
  var inicioTempo = 0;
  var arrastou = false;
  var consumirClique = false;

  if (palco) {
    palco.addEventListener('pointerdown', function (evento) {
      if (evento.pointerType === 'mouse' && evento.button !== 0) {
        return;
      }

      if (evento.target.closest('[data-retro-swipe-ignore]')) {
        arrastou = false;
        return;
      }

      inicioX = evento.clientX;
      inicioY = evento.clientY;
      inicioTempo = Date.now();
      arrastou = true;
      consumirClique = false;
    });

    palco.addEventListener('pointerup', function (evento) {
      if (!arrastou) {
        return;
      }

      arrastou = false;

      var dx = evento.clientX - inicioX;
      var dy = evento.clientY - inicioY;

      if (Math.abs(dx) < 46 || Math.abs(dy) > 70 || Date.now() - inicioTempo > 900) {
        return;
      }

      consumirClique = true;
      ir(atual + (dx < 0 ? 1 : -1));
    });

    palco.addEventListener('pointercancel', function () {
      arrastou = false;
    });

    palco.addEventListener('click', function (evento) {
      if (consumirClique) {
        consumirClique = false;
        return;
      }

      if (evento.target.closest('a, button, input, label, select, textarea, [data-quickview], [data-retro-swipe-ignore]')) {
        return;
      }

      if (window.getSelection && String(window.getSelection()) !== '') {
        return;
      }

      var caixa = palco.getBoundingClientRect();
      var voltar = evento.clientX < caixa.left + caixa.width * 0.16;

      ir(atual + (voltar ? -1 : 1));
    });
  }

  document.addEventListener('keydown', function (evento) {
    if (raiz.hidden || document.querySelector('dialog[open]')) {
      return;
    }

    var alvo = evento.target;

    if (alvo && (alvo.closest('input, textarea, select, [contenteditable="true"]'))) {
      return;
    }

    if (evento.key === 'ArrowRight') {
      evento.preventDefault();
      ir(atual + 1);
    } else if (evento.key === 'ArrowLeft') {
      evento.preventDefault();
      ir(atual - 1);
    } else if (evento.key === 'Escape') {
      evento.preventDefault();
      fechar();
    }
  });

  window.addEventListener('hashchange', function () {
    if (raiz.hidden) {
      return;
    }

    var pedido = /^#cap-(\d+)$/.exec(window.location.hash);

    if (!pedido) {
      return;
    }

    var indice = parseInt(pedido[1], 10) - 1;

    if (indice >= 0 && indice < total && indice !== atual) {
      ir(indice);
    }
  });

  /* ------------------------------ Inicialização --------------------------- */
  var pedido = /^#cap-(\d+)$/.exec(window.location.hash);

  if (pedido) {
    var indice = parseInt(pedido[1], 10) - 1;

    if (indice >= 0 && indice < total) {
      ir(indice);
      return;
    }
  }

  ir(0);
}());
