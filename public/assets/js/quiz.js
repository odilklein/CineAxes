/* =============================================================================
   PersonaCut — questionário
   Uma pergunta por vez, respostas -2..+2, rascunho no localStorage,
   envio via fetch para /api/quiz.php.
   ========================================================================== */
(function () {
  'use strict';

  var configEl = document.getElementById('quiz-config');

  if (!configEl) {
    return;
  }

  var config;
  try {
    config = JSON.parse(configEl.textContent || '{}');
  } catch (e) {
    return;
  }

  var perguntas = config.perguntas || [];
  var total = perguntas.length;

  if (total === 0) {
    return;
  }

  var DRAFT_KEY = 'pc-quiz-draft-v1';
  var SEGMENTOS = 16;

  var respostas = {};
  var indice = 0;
  var enviando = false;

  var el = {
    atual: document.querySelector('[data-q-atual]'),
    total: document.querySelector('[data-q-total]'),
    pct: document.querySelector('[data-q-pct]'),
    segmentos: document.querySelector('[data-segments]'),
    num: document.querySelector('[data-q-num]'),
    texto: document.querySelector('[data-q-text]'),
    eixo: document.querySelector('[data-q-axis]'),
    tag: document.querySelector('[data-q-tag]'),
    icone: document.querySelector('[data-q-axis-icon]'),
    guiaBotao: document.querySelector('[data-guide-open]'),
    guia: {
      dialogo: document.querySelector('[data-guide]'),
      kicker: document.querySelector('[data-guide-kicker]'),
      icone: document.querySelector('[data-guide-icon]'),
      titulo: document.querySelector('[data-guide-title]'),
      resumo: document.querySelector('[data-guide-resumo]'),
      polos: document.querySelector('[data-guide-poles]'),
      prosa: document.querySelector('[data-guide-prose]'),
      cenas: document.querySelector('[data-guide-scenes]'),
      citacoes: document.querySelector('[data-guide-cites]'),
      leitura: document.querySelector('[data-guide-leitura]'),
      fechar: document.querySelectorAll('[data-guide-close]')
    },
    respostas: document.querySelector('[data-answers]'),
    voltar: document.querySelector('[data-quiz-back]'),
    avancar: document.querySelector('[data-quiz-next]'),
    reiniciar: document.querySelectorAll('[data-quiz-restart]'),
    auto: document.querySelector('[data-auto-advance]'),
    carregando: document.querySelector('[data-loading]'),
    erro: document.querySelector('[data-quiz-error]'),
    midia: document.querySelector('[data-q-media]'),
    midiaFrame: document.querySelector('[data-q-media-frame]'),
    midiaTitulo: document.querySelector('[data-q-media-caption]'),
    midiaCredito: document.querySelector('[data-q-media-credit]'),
    midiaCol: document.querySelector('[data-q-media-col]'),
    cartao: document.querySelector('.pc-question')
  };

  var PLAY_ICON = '<svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5l11 7-11 7V5z"/></svg>';
  var midiaPrecarregada = {};

  /* ----------------------------- Rascunho -------------------------------- */
  function salvarRascunho() {
    try {
      localStorage.setItem(DRAFT_KEY, JSON.stringify({
        modo: config.modo,
        indice: indice,
        respostas: respostas,
        atualizadoEm: Date.now()
      }));
    } catch (e) { /* sem storage */ }
  }

  function carregarRascunho() {
    try {
      var bruto = localStorage.getItem(DRAFT_KEY);

      if (!bruto) {
        return false;
      }

      var dados = JSON.parse(bruto);

      if (!dados || dados.modo !== config.modo || typeof dados.respostas !== 'object') {
        return false;
      }

      var validos = {};

      perguntas.forEach(function (pergunta) {
        var valor = dados.respostas[pergunta.id];

        if (typeof valor === 'number' && valor >= -2 && valor <= 2) {
          validos[pergunta.id] = valor;
        }
      });

      if (Object.keys(validos).length === 0) {
        return false;
      }

      respostas = validos;
      indice = Math.min(Math.max(0, Number(dados.indice) || 0), total - 1);

      return true;
    } catch (e) {
      return false;
    }
  }

  function limparRascunho() {
    try {
      localStorage.removeItem(DRAFT_KEY);
    } catch (e) { /* sem storage */ }
  }

  /* ----------------------------- Segmentos ------------------------------- */
  function montarSegmentos() {
    if (!el.segmentos) {
      return;
    }

    el.segmentos.innerHTML = '';

    for (var i = 0; i < SEGMENTOS; i++) {
      var segmento = document.createElement('span');
      var barra = document.createElement('i');
      segmento.appendChild(barra);
      el.segmentos.appendChild(segmento);
    }
  }

  function atualizarProgresso() {
    var respondidas = Object.keys(respostas).length;
    var fracao = Math.min(1, (indice + (respondidas > indice ? 1 : 0)) / total);
    var pct = Math.round((respondidas / total) * 100);

    if (el.atual) {
      el.atual.textContent = String(indice + 1);
    }

    if (el.pct) {
      el.pct.textContent = pct + '% concluído';
    }

    if (el.segmentos) {
      el.segmentos.setAttribute('aria-valuenow', String(pct));
      el.segmentos.setAttribute('aria-label', 'Progresso do teste: ' + pct + '%');

      Array.prototype.forEach.call(el.segmentos.children, function (segmento, i) {
        var inicio = i / SEGMENTOS;
        var fim = (i + 1) / SEGMENTOS;
        var preenchimento = Math.max(0, Math.min(1, (fracao - inicio) / (fim - inicio)));
        var barra = segmento.firstElementChild;

        if (barra) {
          barra.style.width = (preenchimento * 100).toFixed(1) + '%';
        }

        segmento.classList.toggle('is-current', indice / total >= inicio && indice / total < fim);
      });
    }
  }

  /* -------------------------------- Mídia -------------------------------- */
  function economiaDeDados() {
    var conexao = navigator.connection || navigator.mozConnection || navigator.webkitConnection;

    return !!(conexao && conexao.saveData);
  }

  function definirFundoMidia(url) {
    if (!el.midia) {
      return;
    }

    if (!url) {
      el.midia.style.removeProperty('--scene-bg');
      return;
    }

    el.midia.style.setProperty('--scene-bg', 'url("' + String(url).replace(/"/g, '%22') + '")');
  }

  function criarFacade(poster, destino, legenda, ehVideo) {
    var figura = document.createElement('img');
    figura.className = 'pc-scene__poster';
    figura.src = poster;
    figura.alt = legenda ? 'Cena de ' + legenda : 'Cena ilustrativa';
    figura.decoding = 'async';
    definirFundoMidia(poster);

    var botao = document.createElement('button');
    botao.className = 'pc-scene__play';
    botao.type = 'button';
    botao.setAttribute('aria-label', 'Ver cena' + (legenda ? ': ' + legenda : ''));
    botao.innerHTML = PLAY_ICON + '<span>Ver cena</span>';

    botao.addEventListener('click', function () {
      if (ehVideo) {
        var frame = document.createElement('iframe');
        frame.className = 'pc-scene__iframe';
        frame.src = destino;
        frame.title = legenda || 'Cena do filme';
        frame.setAttribute('allow', 'autoplay; encrypted-media; picture-in-picture');
        frame.setAttribute('allowfullscreen', '');
        frame.setAttribute('loading', 'lazy');
        el.midiaFrame.innerHTML = '';
        el.midiaFrame.appendChild(frame);
        return;
      }

      var gif = document.createElement('img');
      gif.className = 'pc-scene__poster';
      gif.src = destino;
      gif.alt = legenda ? 'Cena de ' + legenda : 'Cena ilustrativa';
      el.midiaFrame.innerHTML = '';
      el.midiaFrame.appendChild(gif);
    });

    el.midiaFrame.appendChild(figura);
    el.midiaFrame.appendChild(botao);
  }

  function mostrarMidia(visivel) {
    if (el.midia) {
      el.midia.hidden = !visivel;
    }

    if (el.midiaCol) {
      el.midiaCol.hidden = !visivel;
    }

    if (el.cartao) {
      el.cartao.classList.toggle('is-solo', !visivel);
    }

    if (!visivel) {
      definirFundoMidia(null);

      if (el.midia) {
        delete el.midia.dataset.midiaTipo;
      }
    }
  }

  function renderizarMidia(pergunta) {
    if (!el.midia || !el.midiaFrame) {
      return;
    }

    var midia = pergunta.midia;

    el.midiaFrame.innerHTML = '';

    if (!midia || !midia.tipo) {
      mostrarMidia(false);
      return;
    }

    mostrarMidia(true);
    el.midia.dataset.midiaTipo = midia.tipo;

    if (el.midiaTitulo) {
      el.midiaTitulo.textContent = midia.legenda || '';
    }

    if (el.midiaCredito) {
      el.midiaCredito.textContent = midia.credito || '';
    }

    if (midia.tipo === 'video_yt') {
      if (midia.poster && midia.embed) {
        criarFacade(midia.poster, midia.embed, midia.legenda, true);
      } else {
        mostrarMidia(false);
      }

      return;
    }

    if (midia.tipo === 'gif' && midia.poster && economiaDeDados()) {
      criarFacade(midia.poster, midia.src, midia.legenda, false);
      return;
    }

    if (!midia.src) {
      mostrarMidia(false);
      return;
    }

    var imagem = document.createElement('img');
    imagem.className = 'pc-scene__poster';
    imagem.src = midia.src;
    imagem.alt = midia.legenda ? 'Cena de ' + midia.legenda : 'Cena ilustrativa';
    imagem.decoding = 'async';
    imagem.loading = 'eager';
    definirFundoMidia(midia.tipo === 'gif' && midia.poster ? midia.poster : midia.src);
    el.midiaFrame.appendChild(imagem);
  }

  function precarregarMidia(alvo) {
    var pergunta = perguntas[alvo];

    if (!pergunta || !pergunta.midia) {
      return;
    }

    var midia = pergunta.midia;
    var url = null;

    if (midia.tipo === 'imagem') {
      url = midia.src;
    } else if (midia.tipo === 'video_yt') {
      url = midia.poster;
    } else if (midia.tipo === 'gif' && economiaDeDados()) {
      url = midia.poster;
    }

    if (!url || midiaPrecarregada[url]) {
      return;
    }

    var imagem = new Image();
    imagem.decoding = 'async';
    imagem.src = url;
    midiaPrecarregada[url] = imagem;
  }

  /* ----------------------------- Renderização ---------------------------- */
  function renderizar() {
    var pergunta = perguntas[indice];

    if (!pergunta) {
      return;
    }

    if (el.num) {
      el.num.textContent = String(indice + 1).padStart(2, '0');
    }

    if (el.texto) {
      el.texto.textContent = pergunta.texto;
    }

    if (el.eixo) {
      el.eixo.textContent = pergunta.eixo;
    }

    if (el.tag) {
      el.tag.style.setProperty('--tag-color', pergunta.cor || '#0c3b2e');
    }

    if (el.icone && pergunta.icone) {
      el.icone.innerHTML = pergunta.icone;
    }

    renderizarMidia(pergunta);

    if (el.respostas) {
      Array.prototype.forEach.call(el.respostas.children, function (botao) {
        var valor = Number(botao.getAttribute('data-value'));
        var selecionado = respostas[pergunta.id] === valor;
        botao.classList.toggle('is-selected', selecionado);
        botao.setAttribute('aria-checked', selecionado ? 'true' : 'false');
      });
    }

    if (el.voltar) {
      el.voltar.disabled = indice === 0;
    }

    if (el.avancar) {
      el.avancar.disabled = respostas[pergunta.id] === undefined;
      var rotulo = el.avancar.querySelector('[data-next-label]');

      if (rotulo) {
        rotulo.textContent = indice === total - 1
          ? config.textos.finish
          : config.textos.next;
      }
    }

    if (el.erro) {
      el.erro.hidden = true;
      el.erro.textContent = '';
    }

    atualizarProgresso();
    salvarRascunho();
  }

  function mostrarErro(mensagem) {
    if (!el.erro) {
      return;
    }

    el.erro.textContent = mensagem;
    el.erro.hidden = false;
  }

  /* ---------------------------- Guia do eixo ----------------------------- */
  function criar(tag, classe, texto) {
    var node = document.createElement(tag);

    if (classe) {
      node.className = classe;
    }

    if (texto !== undefined && texto !== null) {
      node.textContent = String(texto);
    }

    return node;
  }

  function textoGuia(chave, padrao) {
    return (config.textos && config.textos[chave]) || padrao;
  }

  function anoGuia(ano) {
    var numero = Number(ano);

    if (!isFinite(numero) || numero === 0) {
      return '';
    }

    return numero < 0 ? 'c. ' + Math.abs(numero) + ' a.C.' : String(numero);
  }

  function guiaAtual() {
    var pergunta = perguntas[indice];

    if (!pergunta || !config.guias) {
      return null;
    }

    return config.guias[pergunta.slug] || null;
  }

  function montarPolo(nome, cor) {
    var item = criar('span', 'pc-guide__pole');
    var ponto = criar('i');

    ponto.style.background = cor;
    item.appendChild(ponto);
    item.appendChild(document.createTextNode(nome));

    return item;
  }

  function preencherGuia(guia) {
    var g = el.guia;

    if (g.kicker) {
      g.kicker.textContent = textoGuia('kicker', 'Guia do eixo') + ' · ' + guia.nome;
    }

    if (g.titulo) {
      g.titulo.textContent = textoGuia('title', 'Entender {eixo}').replace('{eixo}', guia.nome);
    }

    if (g.icone) {
      g.icone.innerHTML = guia.icone || '';
    }

    if (g.resumo) {
      g.resumo.textContent = guia.resumo || '';
    }

    if (g.polos) {
      g.polos.innerHTML = '';
      g.polos.appendChild(montarPolo(guia.poloEsq, guia.corEsq));
      g.polos.appendChild(criar('span', 'pc-guide__pole-arrow', '↔'));
      g.polos.appendChild(montarPolo(guia.poloDir, guia.corDir));
    }

    if (g.prosa) {
      g.prosa.innerHTML = '';

      (guia.paragrafos || []).forEach(function (paragrafo) {
        g.prosa.appendChild(criar('p', null, paragrafo));
      });
    }

    if (g.cenas) {
      g.cenas.innerHTML = '';

      (guia.cenas || []).forEach(function (cena) {
        var item = criar('li', 'pc-guide__scene');
        var cabecalho = criar('div', 'pc-guide__scene-head');
        var tipo = cena.tipo === 'série' ? textoGuia('series', 'Série') : textoGuia('film', 'Filme');

        item.style.setProperty('--guide-polo', cena.polo === 'esq' ? guia.corEsq : guia.corDir);

        cabecalho.appendChild(criar('span', 'pc-guide__scene-title', cena.obra));
        cabecalho.appendChild(criar('span', 'pc-guide__scene-year', anoGuia(cena.ano)));
        cabecalho.appendChild(criar('span', 'pc-guide__scene-tag', tipo));

        item.appendChild(cabecalho);
        item.appendChild(criar('p', null, cena.cena));
        item.appendChild(criar('p', 'pc-guide__scene-why', cena.porque));
        g.cenas.appendChild(item);
      });
    }

    if (g.citacoes) {
      g.citacoes.innerHTML = '';

      (guia.citacoes || []).forEach(function (citacao) {
        var item = criar('li', 'pc-guide__cite');
        var fonte = criar('cite');
        var obra = criar('i', null, citacao.obra);
        var ano = anoGuia(citacao.ano);

        fonte.appendChild(document.createTextNode(citacao.autor + ', '));
        fonte.appendChild(obra);

        if (ano) {
          fonte.appendChild(document.createTextNode(' (' + ano + ')'));
        }

        item.appendChild(criar('p', null, citacao.ideia));
        item.appendChild(fonte);
        g.citacoes.appendChild(item);
      });
    }

    if (g.leitura) {
      g.leitura.innerHTML = '';

      [
        [guia.poloEsq, guia.leitura ? guia.leitura.esq : ''],
        [guia.poloDir, guia.leitura ? guia.leitura.dir : '']
      ].forEach(function (par) {
        if (!par[1]) {
          return;
        }

        var bloco = criar('div');

        bloco.appendChild(criar('b', null, 'Perto de ' + par[0]));
        bloco.appendChild(criar('span', null, par[1]));
        g.leitura.appendChild(bloco);
      });
    }
  }

  function abrirGuia() {
    var dialogo = el.guia.dialogo;
    var guia = guiaAtual();

    if (!dialogo || !guia) {
      return;
    }

    preencherGuia(guia);

    if (typeof dialogo.showModal === 'function') {
      dialogo.showModal();
    } else {
      dialogo.setAttribute('open', '');
    }

    document.body.classList.add('pc-guide-open');
  }

  function fecharGuia() {
    var dialogo = el.guia.dialogo;

    if (!dialogo) {
      return;
    }

    if (typeof dialogo.close === 'function') {
      dialogo.close();
    } else {
      dialogo.removeAttribute('open');
    }

    document.body.classList.remove('pc-guide-open');
  }

  /* ------------------------------ Navegação ------------------------------ */
  function responder(valor) {
    var pergunta = perguntas[indice];

    if (!pergunta) {
      return;
    }

    respostas[pergunta.id] = valor;

    if (el.respostas) {
      Array.prototype.forEach.call(el.respostas.children, function (botao) {
        var selecionado = Number(botao.getAttribute('data-value')) === valor;
        botao.classList.toggle('is-selected', selecionado);
        botao.setAttribute('aria-checked', selecionado ? 'true' : 'false');
      });
    }

    if (el.avancar) {
      el.avancar.disabled = false;
    }

    atualizarProgresso();
    salvarRascunho();

    var autoAvancar = !el.auto || el.auto.checked;

    if (autoAvancar && indice < total - 1) {
      window.setTimeout(function () {
        if (respostas[perguntas[indice].id] === valor) {
          irPara(indice + 1);
        }
      }, 210);
    }
  }

  function irPara(novoIndice) {
    if (novoIndice < 0 || novoIndice >= total) {
      return;
    }

    indice = novoIndice;
    renderizar();
    precarregarMidia(indice + 1);

    var cartao = document.querySelector('.pc-question');

    if (cartao && window.matchMedia('(prefers-reduced-motion: reduce)').matches === false) {
      cartao.animate(
        [
          { opacity: 0.35, transform: 'translateY(8px)' },
          { opacity: 1, transform: 'none' }
        ],
        { duration: 230, easing: 'cubic-bezier(.22,1,.36,1)' }
      );
    }
  }

  function avancar() {
    var pergunta = perguntas[indice];

    if (!pergunta || respostas[pergunta.id] === undefined) {
      mostrarErro(config.textos.incomplete);
      return;
    }

    if (indice < total - 1) {
      irPara(indice + 1);
      return;
    }

    enviar();
  }

  function reiniciar() {
    if (!window.confirm(config.textos.restart + '?')) {
      return;
    }

    respostas = {};
    indice = 0;
    limparRascunho();
    renderizar();
  }

  /* -------------------------------- Envio -------------------------------- */
  function enviar() {
    if (enviando) {
      return;
    }

    var faltando = perguntas.filter(function (pergunta) {
      return respostas[pergunta.id] === undefined;
    });

    if (faltando.length > 0) {
      mostrarErro(config.textos.incomplete);
      irPara(perguntas.indexOf(faltando[0]));
      return;
    }

    enviando = true;

    if (el.carregando) {
      el.carregando.classList.add('is-open');
    }

    var pacote = {};

    perguntas.forEach(function (pergunta) {
      pacote[pergunta.id] = respostas[pergunta.id];
    });

    fetch(config.endpoint, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-Token': config.csrf
      },
      body: JSON.stringify({
        modo: config.modo,
        csrf: config.csrf,
        respostas: pacote
      })
    }).then(function (resposta) {
      return resposta.json().then(function (dados) {
        return { status: resposta.status, dados: dados };
      });
    }).then(function (resultado) {
      if (resultado.status !== 200 || !resultado.dados || !resultado.dados.ok) {
        throw new Error((resultado.dados && resultado.dados.erro) || config.textos.error);
      }

      limparRascunho();
      window.location.href = config.resultadoUrl + '?codigo=' + encodeURIComponent(resultado.dados.codigo);
    }).catch(function (erro) {
      enviando = false;

      if (el.carregando) {
        el.carregando.classList.remove('is-open');
      }

      mostrarErro(erro.message || config.textos.error);
    });
  }

  /* ------------------------------- Eventos ------------------------------- */
  if (el.respostas) {
    el.respostas.addEventListener('click', function (evento) {
      var botao = evento.target.closest('.pc-answer');

      if (botao) {
        responder(Number(botao.getAttribute('data-value')));
      }
    });
  }

  if (el.avancar) {
    el.avancar.addEventListener('click', avancar);
  }

  if (el.voltar) {
    el.voltar.addEventListener('click', function () {
      irPara(indice - 1);
    });
  }

  el.reiniciar.forEach(function (botao) {
    botao.addEventListener('click', function (evento) {
      evento.preventDefault();
      reiniciar();
    });
  });

  if (el.guiaBotao) {
    el.guiaBotao.addEventListener('click', abrirGuia);
  }

  el.guia.fechar.forEach(function (botao) {
    botao.addEventListener('click', fecharGuia);
  });

  if (el.guia.dialogo) {
    el.guia.dialogo.addEventListener('close', function () {
      document.body.classList.remove('pc-guide-open');
    });

    el.guia.dialogo.addEventListener('click', function (evento) {
      if (evento.target === el.guia.dialogo) {
        fecharGuia();
      }
    });
  }

  document.addEventListener('keydown', function (evento) {
    if (el.guia.dialogo && el.guia.dialogo.open) {
      return;
    }

    if (evento.target.matches('input, textarea, select')) {
      return;
    }

    var mapa = { '1': 2, '2': 1, '3': 0, '4': -1, '5': -2 };

    if (Object.prototype.hasOwnProperty.call(mapa, evento.key)) {
      evento.preventDefault();
      responder(mapa[evento.key]);
      return;
    }

    if (evento.key === 'ArrowRight' || evento.key === 'Enter') {
      if (document.activeElement && document.activeElement.tagName === 'BUTTON') {
        return;
      }
      evento.preventDefault();
      avancar();
    }

    if (evento.key === 'ArrowLeft') {
      evento.preventDefault();
      irPara(indice - 1);
    }
  });

  /* -------------------------------- Init --------------------------------- */
  montarSegmentos();
  carregarRascunho();
  renderizar();
  precarregarMidia(indice + 1);
}());
