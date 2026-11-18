/* =============================================================================
   PersonaCut — Quick View
   Ficha de filme, personagem, diretor ou estúdio/escola. Os dados do catálogo
   chegam no card; a TMDB completa sinopse, elenco e a biografia do intérprete.
   ========================================================================== */
(function () {
  'use strict';

  var dialog = null;
  var ultimoFoco = null;
  var cache = {};
  var cachePessoa = {};
  var pedidoAtual = 0;
  var pilha = [];
  var dadosAtuais = null;

  var TEXTOS = {
    ficha: 'Ficha rápida',
    sobre: 'Sobre',
    frase: 'Frase',
    ator: 'Quem interpreta',
    elenco: 'Elenco',
    polos: 'Perfil do personagem',
    polosAncora: 'Perfil do diretor',
    obras: 'Obras essenciais',
    sinopse: 'Sinopse',
    onde: 'Onde aparece',
    verFilme: 'Ver ficha do filme',
    voltar: 'Voltar',
    fechar: 'Fechar ficha',
    carregando: 'Buscando sinopse e elenco…',
    carregandoPessoa: 'Buscando a trajetória do intérprete…'
  };

  function el(tag, classe, texto) {
    var node = document.createElement(tag);

    if (classe) {
      node.className = classe;
    }

    if (texto !== undefined && texto !== null) {
      node.textContent = texto;
    }

    return node;
  }

  function imagemSegura(url) {
    if (typeof url !== 'string' || url === '') {
      return null;
    }

    return /^https:\/\/image\.tmdb\.org\//.test(url) || /^\/assets\//.test(url) ? url : null;
  }

  function compacto(valor) {
    try {
      return new Intl.NumberFormat('pt-BR', { notation: 'compact', maximumFractionDigits: 1 }).format(valor);
    } catch (e) {
      return String(valor);
    }
  }

  function duracao(minutos) {
    if (!minutos || minutos < 1) {
      return null;
    }

    var h = Math.floor(minutos / 60);
    var m = minutos % 60;

    return (h > 0 ? h + 'h ' : '') + (m > 0 ? m + 'min' : '').trim();
  }

  function dataCurta(valor) {
    if (typeof valor !== 'string' || valor === '') {
      return '';
    }

    var partes = valor.split('-');

    if (partes.length !== 3) {
      return valor;
    }

    return partes[2] + '/' + partes[1] + '/' + partes[0];
  }

  function tituloTipo(dados) {
    if (dados.tipo === 'personagem') {
      return 'Personagem';
    }

    if (dados.tipo === 'diretor') {
      return 'Diretor';
    }

    if (dados.tipo === 'estudio') {
      return 'Estúdio / escola';
    }

    if (dados.tipo === 'ator') {
      return 'Ator';
    }

    return 'Filme';
  }

  function montarDialogo() {
    dialog = document.createElement('dialog');
    dialog.className = 'pc-qv';
    dialog.setAttribute('aria-labelledby', 'pc-qv-title');

    var topo = el('div', 'pc-qv__top');
    var voltar = el('button', 'pc-qv__back', '←');
    voltar.type = 'button';
    voltar.hidden = true;
    voltar.setAttribute('aria-label', TEXTOS.voltar);
    voltar.addEventListener('click', function () {
      desempilhar();
    });

    var kicker = el('span', 'pc-qv__kicker', TEXTOS.ficha);
    kicker.setAttribute('data-qv-kicker', '');

    var fechar = el('button', 'pc-qv__close', '×');
    fechar.type = 'button';
    fechar.setAttribute('aria-label', TEXTOS.fechar);
    fechar.addEventListener('click', function () {
      dialog.close();
    });

    var acoes = el('div', 'pc-qv__actions');
    acoes.appendChild(kicker);
    acoes.appendChild(fechar);
    topo.appendChild(voltar);
    topo.appendChild(acoes);

    var grade = el('div', 'pc-qv__grid');

    var poster = el('figure', 'pc-qv__poster');
    poster.setAttribute('data-qv-poster', '');

    var conteudo = el('div', 'pc-qv__content');
    var titulo = el('h2');
    titulo.id = 'pc-qv-title';
    titulo.setAttribute('data-qv-title', '');

    var meta = el('p', 'pc-qv__meta');
    meta.setAttribute('data-qv-meta', '');

    var filme = el('p', 'pc-qv__film');
    filme.setAttribute('data-qv-film', '');
    filme.hidden = true;

    var carregando = el('p', 'pc-qv__loading', TEXTOS.carregando);
    carregando.setAttribute('data-qv-loading', '');
    carregando.hidden = true;

    var bio = el('section', 'pc-qv__section');
    bio.setAttribute('data-qv-bio', '');
    bio.hidden = true;
    bio.appendChild(el('h3', null, TEXTOS.sobre));
    bio.appendChild(el('p'));

    var frase = el('figure', 'pc-qv__quote');
    frase.setAttribute('data-qv-frase', '');
    frase.hidden = true;
    frase.appendChild(el('blockquote'));
    frase.appendChild(el('figcaption'));

    var ator = el('section', 'pc-qv__section');
    ator.setAttribute('data-qv-ator', '');
    ator.hidden = true;
    ator.appendChild(el('h3', null, TEXTOS.ator));
    var atorCartao = el('div', 'pc-qv__actor-card');
    ator.appendChild(atorCartao);
    ator.appendChild(el('p', 'pc-qv__actor-bio'));

    var polos = el('section', 'pc-qv__section');
    polos.setAttribute('data-qv-polos', '');
    polos.hidden = true;
    polos.appendChild(el('h3', null, TEXTOS.polos));
    polos.appendChild(el('ul', 'pc-qv__poles'));

    var obras = el('section', 'pc-qv__section');
    obras.setAttribute('data-qv-obras', '');
    obras.hidden = true;
    obras.appendChild(el('h3', null, TEXTOS.obras));
    obras.appendChild(el('ul', 'pc-qv__works'));

    var onde = el('section', 'pc-qv__section');
    onde.setAttribute('data-qv-onde', '');
    onde.hidden = true;
    onde.appendChild(el('h3', null, TEXTOS.onde));
    var ondeCorpo = el('div', 'pc-qv__onde');
    onde.appendChild(ondeCorpo);

    var sinopse = el('section', 'pc-qv__section');
    sinopse.setAttribute('data-qv-sinopse', '');
    sinopse.hidden = true;
    sinopse.appendChild(el('h3', null, TEXTOS.sinopse));
    sinopse.appendChild(el('p'));

    var elenco = el('section', 'pc-qv__section');
    elenco.setAttribute('data-qv-elenco', '');
    elenco.hidden = true;
    elenco.appendChild(el('h3', null, TEXTOS.elenco));
    elenco.appendChild(el('div', 'pc-qv__cast'));

    conteudo.appendChild(titulo);
    conteudo.appendChild(meta);
    conteudo.appendChild(filme);
    conteudo.appendChild(carregando);
    conteudo.appendChild(bio);
    conteudo.appendChild(frase);
    conteudo.appendChild(ator);
    conteudo.appendChild(polos);
    conteudo.appendChild(onde);
    conteudo.appendChild(sinopse);
    conteudo.appendChild(elenco);
    conteudo.appendChild(obras);

    grade.appendChild(poster);
    grade.appendChild(conteudo);

    var credito = el('p', 'pc-qv__credit', 'Ficha PersonaCut · dados de filmes: TMDB');

    dialog.appendChild(topo);
    dialog.appendChild(grade);
    dialog.appendChild(credito);

    dialog.addEventListener('click', function (evento) {
      if (evento.target === dialog) {
        dialog.close();
      }
    });

    dialog.addEventListener('close', function () {
      document.body.classList.remove('pc-qv-open');
      pilha = [];

      if (ultimoFoco && typeof ultimoFoco.focus === 'function') {
        ultimoFoco.focus();
      }
    });

    document.body.appendChild(dialog);

    return dialog;
  }

  function preencherPoster(url, ehLogo, titulo, tipo) {
    var container = dialog.querySelector('[data-qv-poster]');

    container.innerHTML = '';
    container.classList.toggle('pc-qv__poster--logo', !!ehLogo);
    container.classList.toggle('pc-qv__poster--mark', !url);

    var src = imagemSegura(url);

    if (src) {
      var img = document.createElement('img');
      img.src = src;
      img.alt = titulo ? 'Imagem de ' + titulo : '';
      img.loading = 'eager';
      img.addEventListener('error', function () {
        img.remove();
        container.classList.add('pc-qv__poster--mark');
        container.appendChild(marca(titulo, tipo));
      });
      container.appendChild(img);

      return;
    }

    container.appendChild(marca(titulo, tipo));
  }

  function marca(titulo, tipo) {
    var selo = el('div', 'pc-qv__seal');

    selo.appendChild(el('span', 'pc-qv__seal-mono', (titulo || '?').slice(0, 2).toUpperCase()));

    if (tipo === 'estudio') {
      selo.appendChild(el('small', null, 'Estúdio / escola'));
    }

    return selo;
  }

  function mostrarSecao(seletor, visivel, texto) {
    var secao = dialog.querySelector(seletor);

    if (!secao) {
      return;
    }

    secao.hidden = !visivel;

    if (texto !== undefined) {
      var paragrafo = secao.querySelector('p');

      if (paragrafo) {
        paragrafo.textContent = texto;
      }
    }
  }

  function limpar() {
    var seletores = [
      '[data-qv-bio]', '[data-qv-frase]', '[data-qv-ator]', '[data-qv-polos]',
      '[data-qv-obras]', '[data-qv-onde]', '[data-qv-sinopse]', '[data-qv-elenco]'
    ];

    seletores.forEach(function (seletor) {
      var secao = dialog.querySelector(seletor);

      if (secao) {
        secao.hidden = true;
      }
    });

    dialog.querySelector('.pc-qv__works').innerHTML = '';
    dialog.querySelector('.pc-qv__cast').innerHTML = '';
    dialog.querySelector('.pc-qv__poles').innerHTML = '';
    dialog.querySelector('.pc-qv__onde').innerHTML = '';
    dialog.querySelector('.pc-qv__actor-card').innerHTML = '';
    dialog.querySelector('.pc-qv__actor-bio').textContent = '';
    dialog.querySelector('[data-qv-frase] blockquote').textContent = '';
    dialog.querySelector('[data-qv-frase] figcaption').textContent = '';
  }

  function render(dados) {
    dadosAtuais = dados;

    var pedido = ++pedidoAtual;
    var titulo = dialog.querySelector('[data-qv-title]');
    var kicker = dialog.querySelector('[data-qv-kicker]');
    var meta = dialog.querySelector('[data-qv-meta]');
    var contexto = dialog.querySelector('[data-qv-film]');
    var carregando = dialog.querySelector('[data-qv-loading]');

    var nome = dados.nome || dados.titulo || 'Ficha';

    limpar();

    titulo.textContent = nome;
    kicker.textContent = tituloTipo(dados);

    var pedacos = [];

    if (dados.tipo === 'ator') {
      if (dados.atorNascimento) {
        pedacos.push(String(dados.atorNascimento));
      }

      if (dados.atorPais) {
        pedacos.push(dados.atorPais);
      }

      pedacos.push('Ator');
    } else {
      if (dados.ano) {
        pedacos.push(String(dados.ano));
      }

      if (dados.ator) {
        pedacos.push(dados.ator);
      }

      if (dados.tipo && dados.tipo !== 'filme') {
        pedacos.push(tituloTipo(dados));
      }
    }

    meta.textContent = pedacos.join(' · ');

    contexto.hidden = true;

    if ((dados.tipo === 'personagem' || dados.tipo === 'ator') && dados.obra) {
      contexto.hidden = false;
      contexto.textContent = dados.tipo === 'ator'
        ? 'Interpreta ' + (dados.personagem || 'um personagem') + ' em ' + dados.obra
        : 'De ' + dados.obra + (dados.ano ? ' (' + dados.ano + ')' : '');
    }

    carregando.hidden = true;

    preencherPoster(
      dados.tipo === 'ator' ? (dados.atorFoto || dados.poster) : dados.poster,
      dados.tipo === 'estudio',
      nome,
      dados.tipo
    );

    if (dados.bio) {
      mostrarSecao('[data-qv-bio]', true, dados.bio);
    }

    if (dados.frase) {
      var figura = dialog.querySelector('[data-qv-frase]');
      var autor = dados.tipo === 'filme' ? '' : (dados.nome || '');

      figura.hidden = false;
      figura.querySelector('blockquote').textContent = '“' + dados.frase + '”';
      figura.querySelector('figcaption').textContent = autor
        + (dados.obra && dados.tipo === 'personagem' ? ', ' + dados.obra : '')
        + (dados.ano ? ' (' + dados.ano + ')' : '');
    }

    if (dados.ator && dados.tipo === 'personagem') {
      montarAtor(dados);
    }

    if (dados.polos && dados.polos.length) {
      montarPolos(dados);
    }

    if (dados.obras && dados.obras.length) {
      montarObras(dados.obras);
    }

    if (dados.tipo === 'personagem' || dados.tipo === 'ator') {
      if (dados.obra && dados.tmdb) {
        montarOnde(dados);
      }
    }

    if (!dialog.open) {
      dialog.showModal();
      document.body.classList.add('pc-qv-open');
    }

    var fechar = dialog.querySelector('.pc-qv__close');

    if (fechar) {
      fechar.focus();
    }

    if (dados.tipo === 'filme' && dados.tmdb) {
      buscarFilme(dados, pedido, carregando);
    }

    if (dados.atorTmdb && !dados.atorBio) {
      buscarPessoa(dados, pedido);
    }
  }

  function montarAtor(dados) {
    var secao = dialog.querySelector('[data-qv-ator]');
    var cartao = secao.querySelector('.pc-qv__actor-card');
    var foto = el('span', 'pc-qv__actor-photo');
    var src = imagemSegura(dados.atorFoto);

    if (src) {
      var img = document.createElement('img');
      img.src = src;
      img.alt = '';
      img.loading = 'lazy';
      foto.appendChild(img);
    } else {
      foto.textContent = (dados.ator || '?').slice(0, 1).toUpperCase();
    }

    var info = el('span', 'pc-qv__actor-info');
    info.appendChild(el('b', null, dados.ator));
    info.appendChild(el('small', null, [
      dados.atorNascimento ? String(dados.atorNascimento) : '',
      dados.atorPais || ''
    ].filter(Boolean).join(' · ')));

    cartao.appendChild(foto);
    cartao.appendChild(info);
    secao.hidden = false;
  }

  function montarPolos(dados) {
    var secao = dialog.querySelector('[data-qv-polos]');
    var lista = secao.querySelector('.pc-qv__poles');
    var titulo = secao.querySelector('h3');

    titulo.textContent = dados.tipo === 'personagem' ? TEXTOS.polos : TEXTOS.polosAncora;
    secao.hidden = false;

    dados.polos.forEach(function (polo) {
      var item = el('li');
      var ponto = el('i');

      ponto.style.background = polo.cor || 'currentColor';
      item.appendChild(ponto);
      item.appendChild(el('span', null, polo.eixo + ' · ' + polo.polo));
      item.appendChild(el('b', null, polo.pct + '%'));
      lista.appendChild(item);
    });
  }

  function montarObras(obras) {
    var secao = dialog.querySelector('[data-qv-obras]');
    var lista = secao.querySelector('.pc-qv__works');

    secao.hidden = false;

    obras.forEach(function (obra) {
      var li = el('li');
      var botao = el('button', 'pc-qv__work');
      botao.type = 'button';

      botao.appendChild(el('span', null, obra.titulo || ''));
      botao.appendChild(el('small', null, obra.ano ? String(obra.ano) : ''));

      if (obra.tmdb) {
        botao.addEventListener('click', function () {
          navegar({
            tipo: 'filme',
            tmdb: obra.tmdb,
            titulo: obra.titulo,
            ano: obra.ano,
            poster: obra.poster || null
          });
        });
      } else {
        botao.disabled = true;
      }

      li.appendChild(botao);
      lista.appendChild(li);
    });
  }

  function montarOnde(dados) {
    var secao = dialog.querySelector('[data-qv-onde]');
    var corpo = secao.querySelector('.pc-qv__onde');

    secao.hidden = false;

    var cartao = el('button', 'pc-qv__filmcard');
    cartao.type = 'button';
    var poster = el('span', 'pc-qv__filmcard-poster');
    var src = imagemSegura(dados.poster);

    if (src) {
      var img = document.createElement('img');
      img.src = src;
      img.alt = '';
      img.loading = 'lazy';
      poster.appendChild(img);
    } else {
      poster.textContent = (dados.obra || '?').slice(0, 1).toUpperCase();
    }

    var info = el('span', 'pc-qv__filmcard-info');
    info.appendChild(el('b', null, dados.obra));
    info.appendChild(el('small', null, [
      dados.ano ? String(dados.ano) : '',
      dados.tmdb ? TEXTOS.verFilme : 'Filme'
    ].filter(Boolean).join(' · ')));

    cartao.appendChild(poster);
    cartao.appendChild(info);

    if (dados.tmdb) {
      cartao.addEventListener('click', function () {
        navegar({
          tipo: 'filme',
          tmdb: dados.tmdb,
          titulo: dados.obra,
          ano: dados.ano,
          poster: dados.poster
        });
      });
    } else {
      cartao.disabled = true;
    }

    corpo.appendChild(cartao);
  }

  function buscarFilme(dados, pedido, carregando) {
    var chave = String(dados.tmdb);

    carregando.hidden = false;

    if (cache[chave]) {
      carregando.hidden = true;
      aplicarFilme(cache[chave], dados);

      return;
    }

    fetch('/api/quickview.php?tmdb=' + encodeURIComponent(chave), {
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin'
    }).then(function (resposta) {
      return resposta.json().then(function (corpo) {
        return { status: resposta.status, corpo: corpo };
      });
    }).then(function (resultado) {
      if (pedido !== pedidoAtual) {
        return;
      }

      carregando.hidden = true;

      if (resultado.status !== 200 || !resultado.corpo || !resultado.corpo.ok) {
        throw new Error('quickview');
      }

      cache[chave] = resultado.corpo;
      aplicarFilme(resultado.corpo, dados);
    }).catch(function () {
      if (pedido !== pedidoAtual) {
        return;
      }

      carregando.hidden = true;

      if (!dados.bio) {
        mostrarSecao('[data-qv-sinopse]', true, 'Não foi possível carregar a ficha agora. Tente novamente em instantes.');
      }
    });
  }

  function buscarPessoa(dados, pedido) {
    var chave = String(dados.atorTmdb);

    if (cachePessoa[chave]) {
      preencherPessoa(cachePessoa[chave], dados);

      return;
    }

    fetch('/api/quickview.php?pessoa=' + encodeURIComponent(chave), {
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin'
    }).then(function (resposta) {
      return resposta.json();
    }).then(function (corpo) {
      if (pedido !== pedidoAtual || !corpo || !corpo.ok) {
        return;
      }

      cachePessoa[chave] = corpo;
      preencherPessoa(corpo, dados);
    }).catch(function () {
      /* silêncio: a ficha do personagem continua completa */
    });
  }

  function preencherPessoa(pessoa, dados) {
    if (dados.tipo === 'ator') {
      var secaoBio = dialog.querySelector('[data-qv-bio]');
      var texto = trimTexto(pessoa.biografia || '', 700);

      if (texto !== '') {
        secaoBio.hidden = false;
        secaoBio.querySelector('p').textContent = texto;
      }

      return;
    }

    var cartao = dialog.querySelector('.pc-qv__actor-card');
    var bio = dialog.querySelector('.pc-qv__actor-bio');

    if (!cartao || !cartao.children.length) {
      return;
    }

    var info = cartao.querySelector('.pc-qv__actor-info');

    if (info) {
      var partes = [];

      if (pessoa.nascimento) {
        partes.push(dataCurta(pessoa.nascimento).slice(-4) || pessoa.nascimento);
      }

      if (pessoa.local) {
        partes.push(pessoa.local);
      }

      if (partes.length) {
        var small = el('small', null, partes.join(' · '));
        var antigo = info.querySelector('small');

        if (antigo) {
          info.replaceChild(small, antigo);
        } else {
          info.appendChild(small);
        }
      }
    }

    if (pessoa.biografia) {
      bio.textContent = trimTexto(pessoa.biografia, 420);
    }
  }

  function trimTexto(texto, limite) {
    var limpo = String(texto || '').replace(/\s+/g, ' ').trim();

    if (limpo.length <= limite) {
      return limpo;
    }

    limpo = limpo.slice(0, limite);
    limpo = limpo.slice(0, limpo.lastIndexOf(' ')) + '…';

    return limpo;
  }

  function aplicarFilme(filme, dados) {
    var meta = dialog.querySelector('[data-qv-meta]');
    var contexto = dialog.querySelector('[data-qv-film]');
    var sinopse = dialog.querySelector('[data-qv-sinopse]');
    var elenco = dialog.querySelector('[data-qv-elenco]');
    var elencoLista = elenco.querySelector('.pc-qv__cast');

    if (!dialog.open || !dadosAtuais || dadosAtuais !== dados) {
      return;
    }

    if (filme.titulo) {
      contexto.hidden = false;
      contexto.textContent = filme.titulo
        + (filme.ano ? ' (' + filme.ano + ')' : '')
        + (filme.duracao ? ' · ' + duracao(filme.duracao) : '');
    }

    var pedacos = [];

    if (filme.generos && filme.generos.length) {
      pedacos.push(filme.generos.slice(0, 3).join(', '));
    }

    if (filme.nota) {
      pedacos.push(filme.nota.toFixed(1).replace('.', ',') + ' / 10');
    }

    if (filme.votos) {
      pedacos.push(compacto(filme.votos) + ' votos');
    }

    if (pedacos.length) {
      meta.textContent = pedacos.join(' · ');
    }

    if (filme.sinopse) {
      mostrarSecao('[data-qv-sinopse]', true, filme.sinopse);
    }

    if (!dados.poster && filme.poster) {
      preencherPoster(filme.poster, false, filme.titulo, 'filme');
    }

    elencoLista.innerHTML = '';

    (filme.elenco || []).slice(0, 8).forEach(function (ator) {
      var item = el('div', 'pc-qv__actor');
      var foto = el('span', 'pc-qv__actor-photo');
      var src = imagemSegura(ator.foto);

      if (src) {
        var img = document.createElement('img');
        img.src = src;
        img.alt = '';
        img.loading = 'lazy';
        foto.appendChild(img);
      } else {
        foto.textContent = (ator.nome || '?').slice(0, 1).toUpperCase();
      }

      item.appendChild(foto);
      item.appendChild(el('b', null, ator.nome || ''));
      item.appendChild(el('small', null, ator.personagem || ''));
      elencoLista.appendChild(item);
    });

    mostrarSecao('[data-qv-elenco]', elencoLista.children.length > 0);
    sinopse.hidden = !filme.sinopse;
  }

  function navegar(dados) {
    if (dadosAtuais) {
      pilha.push(dadosAtuais);
    }

    render(dados);
    atualizarVoltar();
  }

  function desempilhar() {
    if (!pilha.length) {
      return;
    }

    render(pilha.pop());
    atualizarVoltar();
  }

  function atualizarVoltar() {
    var voltar = dialog.querySelector('.pc-qv__back');

    if (voltar) {
      voltar.hidden = pilha.length === 0;
    }
  }

  function abrir(dados, gatilho) {
    if (!dialog) {
      montarDialogo();
    }

    ultimoFoco = gatilho;
    pilha = [];
    render(dados);
    atualizarVoltar();
  }

  function dadosDoCard(card) {
    try {
      return JSON.parse(card.getAttribute('data-quickview') || '{}');
    } catch (e) {
      return null;
    }
  }

  document.addEventListener('click', function (evento) {
    var card = evento.target.closest('[data-quickview]');

    if (!card) {
      return;
    }

    var dados = dadosDoCard(card);

    if (dados) {
      abrir(dados, card);
    }
  });

  document.addEventListener('keydown', function (evento) {
    if (evento.key !== 'Enter' && evento.key !== ' ') {
      return;
    }

    var card = evento.target.closest && evento.target.closest('[data-quickview]');

    if (!card || evento.target.closest('button, a, input, textarea, select')) {
      return;
    }

    var dados = dadosDoCard(card);

    if (!dados) {
      return;
    }

    evento.preventDefault();
    abrir(dados, card);
  });
}());
