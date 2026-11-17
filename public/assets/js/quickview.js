/* =============================================================================
   PersonaCut — Quick View
   Abre uma ficha do card com sinopse, elenco e obras. Os dados de filme vêm de
   /api/quickview.php (cache da TMDB); o que já está no card aparece na hora.
   ========================================================================== */
(function () {
  'use strict';

  var dialog = null;
  var ultimoFoco = null;
  var cache = {};
  var pedidoAtual = 0;

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

  function montarDialogo() {
    dialog = document.createElement('dialog');
    dialog.className = 'pc-qv';
    dialog.setAttribute('aria-labelledby', 'pc-qv-title');

    var topo = el('div', 'pc-qv__top');
    topo.appendChild(el('span', 'pc-qv__kicker', 'Ficha rápida'));

    var fechar = el('button', 'pc-qv__close', '×');
    fechar.type = 'button';
    fechar.setAttribute('aria-label', 'Fechar ficha');
    fechar.addEventListener('click', function () {
      dialog.close();
    });
    topo.appendChild(fechar);

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

    var carregando = el('p', 'pc-qv__loading', 'Buscando sinopse e elenco…');
    carregando.setAttribute('data-qv-loading', '');
    carregando.hidden = true;

    var sinopse = el('section', 'pc-qv__section');
    sinopse.setAttribute('data-qv-sinopse', '');
    sinopse.hidden = true;
    sinopse.appendChild(el('h3', null, 'Sinopse'));
    sinopse.appendChild(el('p'));

    var bio = el('section', 'pc-qv__section');
    bio.setAttribute('data-qv-bio', '');
    bio.hidden = true;
    bio.appendChild(el('h3', null, 'Sobre'));
    bio.appendChild(el('p'));

    var elenco = el('section', 'pc-qv__section');
    elenco.setAttribute('data-qv-elenco', '');
    elenco.hidden = true;
    elenco.appendChild(el('h3', null, 'Elenco'));
    var elencoLista = el('div', 'pc-qv__cast');
    elenco.appendChild(elencoLista);

    var obras = el('section', 'pc-qv__section');
    obras.setAttribute('data-qv-obras', '');
    obras.hidden = true;
    obras.appendChild(el('h3', null, 'Obras essenciais'));
    var obrasLista = el('ul', 'pc-qv__works');
    obras.appendChild(obrasLista);

    conteudo.appendChild(titulo);
    conteudo.appendChild(meta);
    conteudo.appendChild(filme);
    conteudo.appendChild(carregando);
    conteudo.appendChild(sinopse);
    conteudo.appendChild(bio);
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

      if (ultimoFoco && typeof ultimoFoco.focus === 'function') {
        ultimoFoco.focus();
      }
    });

    document.body.appendChild(dialog);

    return dialog;
  }

  function preencherPoster(container, url, ehLogo, titulo) {
    container.innerHTML = '';
    container.classList.toggle('pc-qv__poster--logo', !!ehLogo);

    var src = imagemSegura(url);

    if (src) {
      var img = document.createElement('img');
      img.src = src;
      img.alt = titulo ? 'Imagem de ' + titulo : '';
      img.loading = 'eager';
      img.addEventListener('error', function () {
        img.remove();
      });
      container.appendChild(img);
    } else {
      var mono = el('span', 'pc-qv__mono', (titulo || '?').slice(0, 1).toUpperCase());
      container.appendChild(mono);
    }
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

  function abrir(dados, gatilho) {
    if (!dialog) {
      montarDialogo();
    }

    ultimoFoco = gatilho;
    var pedido = ++pedidoAtual;

    var poster = dialog.querySelector('[data-qv-poster]');
    var titulo = dialog.querySelector('[data-qv-title]');
    var meta = dialog.querySelector('[data-qv-meta]');
    var filme = dialog.querySelector('[data-qv-film]');
    var carregando = dialog.querySelector('[data-qv-loading]');
    var bio = dialog.querySelector('[data-qv-bio]');
    var obras = dialog.querySelector('[data-qv-obras]');
    var elenco = dialog.querySelector('[data-qv-elenco]');
    var elencoLista = elenco.querySelector('.pc-qv__cast');
    var obrasLista = obras.querySelector('.pc-qv__works');

    var nome = dados.nome || dados.titulo || 'Ficha';
    titulo.textContent = nome;

    var pedacos = [];

    if (dados.ano) {
      pedacos.push(String(dados.ano));
    }

    if (dados.ator) {
      pedacos.push(dados.ator);
    }

    if (dados.tipo) {
      pedacos.push(dados.tipo === 'estudio' ? 'Estúdio / escola' : dados.tipo.charAt(0).toUpperCase() + dados.tipo.slice(1));
    }

    meta.textContent = pedacos.join(' · ');

    filme.hidden = true;
    carregando.hidden = !dados.tmdb;

    mostrarSecao('[data-qv-sinopse]', false);
    mostrarSecao('[data-qv-bio]', !!dados.bio, dados.bio || '');
    mostrarSecao('[data-qv-elenco]', false);
    mostrarSecao('[data-qv-obras]', !!(dados.obras && dados.obras.length));
    mostrarSecao('[data-qv-sinopse]', false);

    elencoLista.innerHTML = '';
    obrasLista.innerHTML = '';

    if (dados.obras && dados.obras.length) {
      dados.obras.forEach(function (obra) {
        var li = el('li');
        li.appendChild(el('span', null, obra.titulo || ''));
        li.appendChild(el('small', null, obra.ano ? String(obra.ano) : ''));
        obrasLista.appendChild(li);
      });
    }

    preencherPoster(poster, dados.poster, dados.tipo === 'estudio', nome);

    if (!dados.tmdb) {
      if (!dialog.open) {
        dialog.showModal();
        document.body.classList.add('pc-qv-open');
      }

      var fechar = dialog.querySelector('.pc-qv__close');

      if (fechar) {
        fechar.focus();
      }

      return;
    }

    if (!dialog.open) {
      dialog.showModal();
      document.body.classList.add('pc-qv-open');
    }

    var fecharBtn = dialog.querySelector('.pc-qv__close');

    if (fecharBtn) {
      fecharBtn.focus();
    }

    var chave = String(dados.tmdb);

    if (cache[chave]) {
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

  function aplicarFilme(filme, dados) {
    var poster = dialog.querySelector('[data-qv-poster]');
    var meta = dialog.querySelector('[data-qv-meta]');
    var linhaFilme = dialog.querySelector('[data-qv-film]');
    var sinopse = dialog.querySelector('[data-qv-sinopse]');
    var elenco = dialog.querySelector('[data-qv-elenco]');
    var elencoLista = elenco.querySelector('.pc-qv__cast');

    if (!dialog.open) {
      return;
    }

    if (filme.titulo) {
      linhaFilme.hidden = false;
      linhaFilme.textContent = filme.titulo
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
      preencherPoster(poster, filme.poster, false, filme.titulo);
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

  document.addEventListener('click', function (evento) {
    var card = evento.target.closest('[data-quickview]');

    if (!card) {
      return;
    }

    var dados;

    try {
      dados = JSON.parse(card.getAttribute('data-quickview') || '{}');
    } catch (e) {
      return;
    }

    abrir(dados, card);
  });

  document.addEventListener('keydown', function (evento) {
    if (evento.key !== 'Enter' && evento.key !== ' ') {
      return;
    }

    var card = evento.target.closest && evento.target.closest('[data-quickview]');

    if (!card || evento.target.closest('button, a, input, textarea, select')) {
      return;
    }

    evento.preventDefault();

    var dados;

    try {
      dados = JSON.parse(card.getAttribute('data-quickview') || '{}');
    } catch (e) {
      return;
    }

    abrir(dados, card);
  });
}());
