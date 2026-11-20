/* =============================================================================
   PersonaCut — dashboard de perfil
   Anima as barras duplas e troca o mix (Clássicos/Misturado/Descobertas) via
   AJAX, sem recarregar a página nem perder a posição do scroll.
   ========================================================================== */
(function () {
  'use strict';

  var barras = document.querySelectorAll('[data-bar]');

  if (barras.length) {
    window.requestAnimationFrame(function () {
      window.setTimeout(function () {
        barras.forEach(function (barra) {
          barra.style.width = (barra.getAttribute('data-bar') || '0') + '%';
        });
      }, 140);
    });
  }

  var temFetch = typeof window.fetch === 'function'
    && typeof window.DOMParser === 'function'
    && typeof window.history.pushState === 'function';

  function ligarMix() {
    document.querySelectorAll('[data-mix]').forEach(function (link) {
      if (link.getAttribute('data-mix-bound') === '1') {
        return;
      }

      link.setAttribute('data-mix-bound', '1');

      link.addEventListener('click', function (evento) {
        if (!temFetch || evento.metaKey || evento.ctrlKey || evento.shiftKey
          || evento.altKey || evento.button !== 0) {
          return;
        }

        var grupo = link.closest('.pc-mix');

        if (grupo && grupo.classList.contains('is-loading')) {
          evento.preventDefault();
          return;
        }

        evento.preventDefault();

        var url = link.href;
        var scrollAntes = window.scrollY;

        if (grupo) {
          grupo.classList.add('is-loading');
        }

        fetch(url, {
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (resposta) {
          if (!resposta.ok) {
            throw new Error('mix');
          }

          return resposta.text();
        }).then(function (html) {
          var doc = new DOMParser().parseFromString(html, 'text/html');
          var novo = doc.getElementById('filmes');
          var atual = document.getElementById('filmes');

          if (!novo || !atual) {
            window.location.href = url;
            return;
          }

          var fichaNova = doc.querySelector('[data-ficha-mix]');
          var fichaAtual = document.querySelector('[data-ficha-mix]');

          if (fichaNova && fichaAtual) {
            fichaAtual.textContent = fichaNova.textContent;
          }

          atual.replaceWith(document.importNode(novo, true));

          history.pushState(
            { mix: link.getAttribute('data-mix') },
            '',
            url
          );

          ligarMix();

          if (window.pcInitCarrosseis) {
            window.pcInitCarrosseis();
          }

          document.dispatchEvent(new CustomEvent('pc:narrativa'));

          var ativo = document.querySelector('.pc-mix__item.is-active');

          if (ativo) {
            ativo.focus({ preventScroll: true });
          }

          // Mantém a posição de leitura: a troca do mix não pode jogar a
          // página para cima nem para baixo.
          window.scrollTo(0, scrollAntes);
          window.requestAnimationFrame(function () {
            window.scrollTo(0, scrollAntes);
          });
        }).catch(function () {
          window.location.href = url;
        }).finally(function () {
          if (grupo) {
            grupo.classList.remove('is-loading');
          }
        });
      });
    });
  }

  ligarMix();
}());
