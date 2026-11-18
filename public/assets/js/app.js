/* =============================================================================
   PersonaCut — comportamento global
   Tema, menu mobile, revelações no scroll, copiar/compartilhar código.
   ========================================================================== */
(function () {
  'use strict';

  var root = document.documentElement;
  var THEME_KEY = 'pc-theme';

  /* ------------------------------- Tema ---------------------------------- */
  function temaAtual() {
    return root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
  }

  function aplicarTema(tema) {
    root.setAttribute('data-theme', tema);

    try {
      localStorage.setItem(THEME_KEY, tema);
    } catch (e) { /* modo privado */ }

    document.dispatchEvent(new CustomEvent('pc:theme', { detail: { theme: tema } }));
  }

  document.querySelectorAll('[data-theme-toggle]').forEach(function (botao) {
    botao.addEventListener('click', function () {
      aplicarTema(temaAtual() === 'dark' ? 'light' : 'dark');
    });
  });

  /* ---------------------------- Menu mobile ------------------------------- */
  var menuBtn = document.querySelector('[data-menu-toggle]');
  var nav = document.getElementById('pc-nav');

  if (menuBtn && nav) {
    menuBtn.addEventListener('click', function () {
      var aberto = nav.classList.toggle('is-open');
      menuBtn.setAttribute('aria-expanded', aberto ? 'true' : 'false');
    });

    nav.addEventListener('click', function (evento) {
      if (evento.target.closest('a')) {
        nav.classList.remove('is-open');
        menuBtn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /* --------------------------- Revelar no scroll -------------------------- */
  var revelaveis = document.querySelectorAll('.reveal');

  if ('IntersectionObserver' in window && revelaveis.length) {
    var observador = new IntersectionObserver(function (entradas) {
      entradas.forEach(function (entrada) {
        if (entrada.isIntersecting) {
          entrada.target.classList.add('is-visible');
          observador.unobserve(entrada.target);
        }
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

    revelaveis.forEach(function (el) {
      observador.observe(el);
    });
  } else {
    revelaveis.forEach(function (el) {
      el.classList.add('is-visible');
    });
  }

  /* ------------------------- Copiar e compartilhar ------------------------ */
  function copiar(texto) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(texto);
    }

    return new Promise(function (resolve, reject) {
      var campo = document.createElement('textarea');
      campo.value = texto;
      campo.setAttribute('readonly', '');
      campo.style.position = 'fixed';
      campo.style.opacity = '0';
      document.body.appendChild(campo);
      campo.select();

      try {
        document.execCommand('copy') ? resolve() : reject(new Error('copy'));
      } catch (erro) {
        reject(erro);
      } finally {
        document.body.removeChild(campo);
      }
    });
  }

  document.addEventListener('click', function (evento) {
    var botao = evento.target.closest('[data-copy]');

    if (!botao) {
      return;
    }

    var valor = botao.getAttribute('data-copy');
    var rotuloOriginal = botao.getAttribute('data-copy-label') || botao.textContent;

    copiar(valor).then(function () {
      botao.textContent = botao.getAttribute('data-copy-ok') || 'Copiado!';
      botao.classList.add('is-copied');

      window.setTimeout(function () {
        botao.textContent = rotuloOriginal;
        botao.classList.remove('is-copied');
      }, 1800);
    }).catch(function () {
      botao.textContent = 'Não foi possível copiar';
    });
  });

  document.querySelectorAll('[data-share]').forEach(function (botao) {
    if (!navigator.share) {
      botao.hidden = true;
      return;
    }

    botao.addEventListener('click', function () {
      navigator.share({
        title: document.title,
        text: botao.getAttribute('data-share-text') || '',
        url: window.location.href
      }).catch(function () { /* usuário cancelou */ });
    });
  });

  /* ---------------------- Códigos de perfil em maiúsculas ------------------ */
  document.querySelectorAll('input[data-code-input]').forEach(function (campo) {
    campo.addEventListener('input', function () {
      var posicao = campo.selectionStart;
      campo.value = campo.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8);

      if (posicao !== null) {
        campo.setSelectionRange(Math.min(posicao, campo.value.length), Math.min(posicao, campo.value.length));
      }
    });
  });

  /* ------------------ Confirmação de ações destrutivas -------------------- */
  document.addEventListener('submit', function (evento) {
    var formulario = evento.target.closest('[data-confirm]');

    if (!formulario) {
      return;
    }

    var mensagem = formulario.getAttribute('data-confirm') || 'Confirmar?';

    if (!window.confirm(mensagem)) {
      evento.preventDefault();
    }
  });

  /* -------------------------- Utilidades de tema -------------------------- */
  function cores() {
    var estilo = getComputedStyle(root);

    return {
      ink: estilo.getPropertyValue('--pc-ink').trim(),
      muted: estilo.getPropertyValue('--pc-muted').trim(),
      line: estilo.getPropertyValue('--pc-line').trim(),
      surface: estilo.getPropertyValue('--pc-surface').trim(),
      green: estilo.getPropertyValue('--pc-green').trim(),
      green2: estilo.getPropertyValue('--pc-green-2').trim(),
      red: estilo.getPropertyValue('--pc-red').trim(),
      amber: estilo.getPropertyValue('--pc-amber').trim()
    };
  }

  window.PC = {
    cores: cores,
    tema: temaAtual,
    aoTrocarTema: function (callback) {
      document.addEventListener('pc:theme', function () {
        callback(temaAtual(), cores());
      });
    }
  };
}());
