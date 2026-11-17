/* =============================================================================
   PersonaCut — card de compartilhamento 9:16
   Captura o card renderizado (270 x 480) em PNG 1080 x 1920 e usa a Web Share
   API quando o navegador aceita arquivos; caso contrário, baixa o PNG.
   Todas as imagens do card vêm de /api/imagem.php (mesma origem), então o
   canvas não é contaminado. A página pode ter mais de um card (análise
   completa e retrospectiva); cada botão captura o card do seu bloco.
   ========================================================================== */
(function () {
  'use strict';

  var botoes = Array.prototype.slice.call(document.querySelectorAll('[data-story-share]'));

  if (!botoes.length) {
    return;
  }

  function esperar(ms) {
    return new Promise(function (resolve) {
      window.setTimeout(resolve, ms);
    });
  }

  /* html2canvas desenha imagens de <img> ignorando object-fit; por isso os
     retratos e pôsteres são fundos com background-size: cover. Ainda assim,
     garante que todos já estejam decodificados antes da captura. */
  function carregarImagens(raiz) {
    var urls = [];

    Array.prototype.forEach.call(raiz.querySelectorAll('*'), function (node) {
      var fundo = window.getComputedStyle(node).backgroundImage;

      if (!fundo || fundo === 'none') {
        return;
      }

      var achado = fundo.match(/url\(["']?(.*?)["']?\)/);

      if (achado && achado[1]) {
        urls.push(achado[1]);
      }
    });

    var promessas = urls.map(function (url) {
      return new Promise(function (resolve) {
        var img = new Image();
        img.onload = function () { resolve(); };
        img.onerror = function () { resolve(); };
        img.src = url;

        if (img.complete) {
          resolve();
        }
      });
    });

    return Promise.all(promessas);
  }

  function cruzarFonte() {
    if (document.fonts && document.fonts.ready) {
      return document.fonts.ready.catch(function () {});
    }

    return Promise.resolve();
  }

  function capturar(card) {
    return html2canvas(card, {
      scale: 4,
      width: 270,
      height: 480,
      windowWidth: 1440,
      windowHeight: 900,
      backgroundColor: null,
      useCORS: true,
      allowTaint: false,
      logging: false,
      imageTimeout: 15000
    });
  }

  function baixar(blob, arquivo) {
    var url = URL.createObjectURL(blob);
    var link = document.createElement('a');
    link.href = url;
    link.download = arquivo;
    link.rel = 'noopener';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.setTimeout(function () { URL.revokeObjectURL(url); }, 4000);
  }

  function compartilhar(blob, arquivo, card) {
    var pdf = new File([blob], arquivo, { type: 'image/png' });

    if (!navigator.canShare || !navigator.canShare({ files: [pdf] })) {
      return Promise.resolve(false);
    }

    return navigator.share({
      files: [pdf],
      title: document.title,
      text: card.querySelector('.pc-story__title')
        ? card.querySelector('.pc-story__title').textContent.trim()
        : 'Meu perfil no PersonaCut'
    }).then(function () {
      return true;
    }, function (erro) {
      // Cancelar o menu de compartilhamento não é falha.
      if (erro && erro.name === 'AbortError') {
        return true;
      }

      return false;
    });
  }

  function configurar(botao) {
    var escopo = botao.closest('[data-story-side]') || document;
    var card = escopo.querySelector('[data-story]');

    if (!card) {
      return;
    }

    var nota = escopo.querySelector('[data-story-note]');
    var textoOriginal = botao.innerHTML;
    var ocupado = false;

    var msg = {
      preparando: botao.getAttribute('data-msg-preparing') || 'Preparando o card…',
      erro: botao.getAttribute('data-msg-error') || 'Não foi possível gerar o card.',
      salvo: botao.getAttribute('data-msg-download') || 'Card salvo em PNG.',
      notaOriginal: nota ? nota.textContent : ''
    };

    var codigo = (botao.getAttribute('data-codigo') || 'perfil').toLowerCase();
    var arquivo = 'personacut-' + codigo + '.png';

    function estado(texto) {
      if (nota) {
        nota.textContent = texto;
      }
    }

    function ocupadoUI(ativo, rotulo) {
      botao.disabled = ativo;

      if (ativo) {
        botao.textContent = rotulo;
      } else {
        botao.innerHTML = textoOriginal;
      }
    }

    botao.addEventListener('click', function () {
      if (ocupado) {
        return;
      }

      if (typeof window.html2canvas !== 'function') {
        estado(msg.erro);
        return;
      }

      ocupado = true;
      ocupadoUI(true, msg.preparando);
      estado(msg.preparando);

      cruzarFonte()
        .then(function () { return carregarImagens(card); })
        .then(function () {
          // Um frame para o layout assentar antes da captura.
          return esperar(60).then(function () { return capturar(card); });
        })
        .then(function (canvas) {
          return new Promise(function (resolve, reject) {
            canvas.toBlob(function (blob) {
              blob ? resolve(blob) : reject(new Error('blob'));
            }, 'image/png');
          });
        })
        .then(function (blob) {
          return compartilhar(blob, arquivo, card).then(function (compartilhado) {
            if (!compartilhado) {
              baixar(blob, arquivo);
              estado(msg.salvo);
            } else {
              estado(msg.notaOriginal);
            }
          });
        })
        .catch(function (erro) {
          if (window.console && console.warn) {
            console.warn('[PersonaCut] story card:', erro);
          }

          estado(msg.erro);
        })
        .then(function () {
          ocupado = false;
          ocupadoUI(false);
        });
    });
  }

  botoes.forEach(configurar);
}());
