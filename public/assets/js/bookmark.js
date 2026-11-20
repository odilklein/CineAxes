/* =============================================================================
   PersonaCut — bookmarks ("quero ver" / "já vi")
   Requer login. O card não desaparece: o estado muda e a lista pessoal guarda.
   ========================================================================== */
(function () {
  'use strict';

  var meta = document.querySelector('meta[name="csrf-token"]');
  var token = meta ? meta.getAttribute('content') : '';

  function atualizar(tmdb, tipo, status, contagem) {
    document.querySelectorAll('[data-bookmark]').forEach(function (botao) {
      if (botao.getAttribute('data-tmdb') !== tmdb || (botao.getAttribute('data-tipo') || 'filme') !== tipo) {
        return;
      }

      var ativo = botao.getAttribute('data-status') === status;
      botao.classList.toggle('is-active', ativo);
      botao.setAttribute('aria-pressed', ativo ? 'true' : 'false');
    });

    // Na "Minha lista", o card muda de aba: quero ver <-> já vi. Ele sai da
    // lista atual e aparece na outra, com os contadores atualizados.
    var lista = document.querySelector('[data-lista-status]');

    if (!lista) {
      return;
    }

    var atual = lista.getAttribute('data-lista-status');
    var card = document.querySelector('[data-bookmark-card][data-tmdb="' + tmdb + '"]');

    if (card && status !== atual) {
      card.classList.add('is-leaving');
      window.setTimeout(function () {
        card.remove();
      }, 200);
    }

    if (contagem) {
      document.querySelectorAll('.pc-auth__tab[data-status]').forEach(function (aba) {
        var chave = aba.getAttribute('data-status');
        var numero = aba.querySelector('b');

        if (numero && contagem[chave] !== undefined) {
          numero.textContent = contagem[chave];
        }
      });
    }
  }

  document.addEventListener('click', function (evento) {
    var botao = evento.target.closest('[data-bookmark]');

    if (!botao) {
      return;
    }

    evento.preventDefault();
    evento.stopPropagation();

    if (botao.getAttribute('data-auth') !== '1') {
      window.location.href = '/entrar.php?next=' + encodeURIComponent(window.location.pathname + window.location.search);
      return;
    }

    var tmdb = String(botao.getAttribute('data-tmdb') || '');
    var tipo = botao.getAttribute('data-tipo') || 'filme';
    var status = botao.getAttribute('data-status') || 'quero';

    if (tmdb === '') {
      return;
    }

    var dados = {
      tmdb: parseInt(tmdb, 10),
      tipo: tipo,
      status: status,
      titulo: botao.getAttribute('data-titulo') || '',
      ano: botao.getAttribute('data-ano') || null,
      poster: botao.getAttribute('data-poster') || null
    };

    botao.disabled = true;

    fetch('/api/bookmark.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-Token': token
      },
      credentials: 'same-origin',
      body: JSON.stringify(dados)
    }).then(function (resposta) {
      return resposta.json().then(function (corpo) {
        return { status: resposta.status, corpo: corpo };
      });
    }).then(function (resultado) {
      botao.disabled = false;

      if (resultado.status === 401) {
        window.location.href = '/entrar.php?next=' + encodeURIComponent(window.location.pathname + window.location.search);
        return;
      }

      if (resultado.status !== 200 || !resultado.corpo || !resultado.corpo.ok) {
        return;
      }

      atualizar(tmdb, tipo, resultado.corpo.status, resultado.corpo.contagem);
    }).catch(function () {
      botao.disabled = false;
    });
  });
}());
