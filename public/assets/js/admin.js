/* =============================================================================
   PersonaCut — painel administrativo
   Sidebar Vuexy, tema sincronizado e funções de tabela (busca, ordenação,
   paginação e exportação CSV).
   ========================================================================== */
(function () {
  'use strict';

  var root = document.documentElement;

  function sincronizarTema() {
    var tema = root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    root.setAttribute('data-bs-theme', tema);
  }

  sincronizarTema();

  if (window.MutationObserver) {
    new MutationObserver(sincronizarTema).observe(root, {
      attributes: true,
      attributeFilter: ['data-theme']
    });
  }

  /* ------------------------------- Sidebar -------------------------------- */
  document.addEventListener('click', function (evento) {
    var alternar = evento.target.closest('.layout-menu-toggle');

    if (alternar) {
      evento.preventDefault();
      root.classList.toggle('layout-menu-expanded');
      return;
    }

    var submenu = evento.target.closest('.menu-toggle');

    if (submenu) {
      evento.preventDefault();
      var item = submenu.closest('.menu-item');

      if (item) {
        item.classList.toggle('open');
      }

      return;
    }

    var linkMenu = evento.target.closest('#layout-menu .menu-link');

    if (linkMenu && window.innerWidth < 1200) {
      root.classList.remove('layout-menu-expanded');
    }
  });


  /* ------------------------------- Dropdown -------------------------------- */
  document.addEventListener('click', function (evento) {
    var gatilho = evento.target.closest('[data-bs-toggle="dropdown"]');

    if (gatilho) {
      evento.preventDefault();
      var caixa = gatilho.closest('.dropdown, .dropdown-user, .nav-item');
      var menu = caixa ? caixa.querySelector('.dropdown-menu') : null;

      document.querySelectorAll('.dropdown-menu.show').forEach(function (outro) {
        if (outro !== menu) {
          outro.classList.remove('show');
          var outroGatilho = outro.parentElement ? outro.parentElement.querySelector('[data-bs-toggle="dropdown"]') : null;
          if (outroGatilho) {
            outroGatilho.setAttribute('aria-expanded', 'false');
          }
        }
      });

      if (menu) {
        var aberto = menu.classList.toggle('show');
        gatilho.setAttribute('aria-expanded', aberto ? 'true' : 'false');
      }

      return;
    }

    if (!evento.target.closest('.dropdown-menu')) {
      document.querySelectorAll('.dropdown-menu.show').forEach(function (menu) {
        menu.classList.remove('show');
        var gatilho = menu.parentElement ? menu.parentElement.querySelector('[data-bs-toggle="dropdown"]') : null;
        if (gatilho) {
          gatilho.setAttribute('aria-expanded', 'false');
        }
      });
    }
  });

  document.addEventListener('keydown', function (evento) {
    if (evento.key !== 'Escape') {
      return;
    }

    document.querySelectorAll('.dropdown-menu.show').forEach(function (menu) {
      menu.classList.remove('show');
    });
  });

  /* -------------------------------- Tabelas ------------------------------- */
  function normalizar(texto) {
    return String(texto || '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function textoCelula(linha, indice) {
    var celula = linha.cells[indice];
    return celula ? normalizar(celula.textContent) : '';
  }

  function comparar(a, b, indice) {
    var va = textoCelula(a, indice);
    var vb = textoCelula(b, indice);
    var na = parseFloat(va.replace(',', '.'));
    var nb = parseFloat(vb.replace(',', '.'));

    if (!isNaN(na) && !isNaN(nb)) {
      return na - nb;
    }

    return va.localeCompare(vb, 'pt-BR');
  }

  function baixarCsv(nome, cabecalhos, linhas) {
    var escapar = function (valor) {
      return '"' + String(valor || '').replace(/"/g, '""') + '"';
    };

    var conteudo = [cabecalhos.map(escapar).join(';')]
      .concat(linhas.map(function (linha) {
        return linha.map(escapar).join(';');
      }))
      .join('\r\n');

    var blob = new Blob(['\ufeff' + conteudo], { type: 'text/csv;charset=utf-8;' });
    var url = URL.createObjectURL(blob);
    var link = document.createElement('a');
    link.href = url;
    link.download = nome;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.setTimeout(function () { URL.revokeObjectURL(url); }, 2000);
  }

  function iniciarTabela(tabela) {
    var wrap = tabela.closest('.pc-table-wrap');

    if (!wrap || wrap.getAttribute('data-admin-table') === '1') {
      return;
    }

    var thead = tabela.tHead;
    var tbody = tabela.tBodies[0];

    if (!thead || !tbody) {
      return;
    }

    var cabecalhos = Array.from(thead.rows[0] ? thead.rows[0].cells : []);

    if (!cabecalhos.length) {
      return;
    }

    var todas = Array.from(tbody.rows).filter(function (linha) {
      return !linha.querySelector('.pc-table__empty') && !linha.hasAttribute('data-admin-empty');
    });

    if (!todas.length) {
      return;
    }

    wrap.setAttribute('data-admin-table', '1');

    var barra = document.createElement('div');
    barra.className = 'pc-table-toolbar';
    barra.innerHTML = ''
      + '<div class="pc-table-toolbar__search">'
      + '  <input class="form-control form-control-sm" type="search" placeholder="Buscar na tabela..." aria-label="Buscar na tabela">'
      + '</div>'
      + '<select class="form-select form-select-sm" aria-label="Itens por página">'
      + '  <option value="10">10 por página</option>'
      + '  <option value="25">25 por página</option>'
      + '  <option value="50">50 por página</option>'
      + '</select>'
      + '<button class="btn btn-sm btn-label-secondary" type="button" data-csv>Exportar CSV</button>'
      + '<span class="pc-table-toolbar__count"></span>';

    wrap.insertBefore(barra, tabela);

    var campoBusca = barra.querySelector('input[type="search"]');
    var seletorPagina = barra.querySelector('select');
    var contador = barra.querySelector('.pc-table-toolbar__count');
    var botaoCsv = barra.querySelector('[data-csv]');
    var porPagina = 10;
    var pagina = 1;
    var filtradas = todas.slice();
    var ordenacao = { indice: -1, direcao: 0 };

    var paginacao = document.createElement('div');
    paginacao.className = 'pc-table-pagination';
    paginacao.innerHTML = '<span class="pc-table-pagination__info"></span>'
      + '<button type="button" data-pagina="anterior" aria-label="Página anterior">←</button>'
      + '<span class="pc-table-pagination__pages"></span>'
      + '<button type="button" data-pagina="proxima" aria-label="Próxima página">→</button>';
    wrap.appendChild(paginacao);

    var infoPaginacao = paginacao.querySelector('.pc-table-pagination__info');
    var listaPaginas = paginacao.querySelector('.pc-table-pagination__pages');
    var botaoAnterior = paginacao.querySelector('[data-pagina="anterior"]');
    var botaoProxima = paginacao.querySelector('[data-pagina="proxima"]');

    // Cabeçalhos ordenáveis.
    cabecalhos.forEach(function (th, indice) {
      if (th.textContent.trim() === '') {
        return;
      }

      var rotulo = th.textContent.trim();
      th.textContent = '';
      var botao = document.createElement('button');
      botao.type = 'button';
      botao.className = 'pc-table-sort';
      botao.textContent = rotulo;
      botao.setAttribute('aria-label', 'Ordenar por ' + rotulo);
      botao.addEventListener('click', function () {
        if (ordenacao.indice === indice) {
          ordenacao.direcao = ordenacao.direcao === 1 ? -1 : 1;
        } else {
          ordenacao.indice = indice;
          ordenacao.direcao = 1;
        }

        cabecalhos.forEach(function (outro) {
          var b = outro.querySelector('.pc-table-sort');
          if (b) {
            b.removeAttribute('data-dir');
          }
        });

        botao.setAttribute('data-dir', ordenacao.direcao === 1 ? 'asc' : 'desc');

        todas.sort(function (a, b) {
          return comparar(a, b, indice) * ordenacao.direcao;
        });

        todas.forEach(function (linha) {
          tbody.appendChild(linha);
        });

        pagina = 1;
        aplicar();
      });
      th.appendChild(botao);
    });

    function aplicar() {
      var termo = normalizar(campoBusca.value);
      filtradas = todas.filter(function (linha) {
        return termo === '' || normalizar(linha.textContent).indexOf(termo) !== -1;
      });

      var total = filtradas.length;
      var paginas = Math.max(1, Math.ceil(total / porPagina));

      if (pagina > paginas) {
        pagina = paginas;
      }

      todas.forEach(function (linha) {
        linha.hidden = true;
      });

      var inicio = (pagina - 1) * porPagina;
      filtradas.slice(inicio, inicio + porPagina).forEach(function (linha) {
        linha.hidden = false;
      });

      var vazio = tbody.querySelector('[data-admin-empty]');

      if (total === 0) {
        if (!vazio) {
          vazio = document.createElement('tr');
          vazio.setAttribute('data-admin-empty', '');
          var celula = document.createElement('td');
          celula.colSpan = cabecalhos.length;
          celula.className = 'pc-table__empty';
          celula.textContent = 'Nenhum resultado para a busca.';
          vazio.appendChild(celula);
          tbody.appendChild(vazio);
        }
      } else if (vazio) {
        vazio.remove();
      }

      contador.textContent = total + (total === 1 ? ' registro' : ' registros');
      infoPaginacao.textContent = total === 0
        ? '0 de 0'
        : (inicio + 1) + '–' + Math.min(inicio + porPagina, total) + ' de ' + total;

      listaPaginas.textContent = 'página ' + pagina + ' de ' + paginas;
      botaoAnterior.disabled = pagina <= 1;
      botaoProxima.disabled = pagina >= paginas;
    }

    campoBusca.addEventListener('input', function () {
      pagina = 1;
      aplicar();
    });

    seletorPagina.addEventListener('change', function () {
      porPagina = parseInt(seletorPagina.value, 10) || 10;
      pagina = 1;
      aplicar();
    });

    botaoAnterior.addEventListener('click', function () {
      if (pagina > 1) {
        pagina--;
        aplicar();
      }
    });

    botaoProxima.addEventListener('click', function () {
      var paginas = Math.max(1, Math.ceil(filtradas.length / porPagina));

      if (pagina < paginas) {
        pagina++;
        aplicar();
      }
    });

    botaoCsv.addEventListener('click', function () {
      var cabecalhosTexto = cabecalhos.map(function (th) {
        var botao = th.querySelector('.pc-table-sort');
        return botao ? botao.textContent.trim() : th.textContent.trim();
      });
      var linhas = filtradas.map(function (linha) {
        return Array.from(linha.cells).map(function (celula) {
          return celula.textContent.trim();
        });
      });

      baixarCsv('personacut-tabela.csv', cabecalhosTexto, linhas);
    });

    aplicar();
  }

  document.querySelectorAll('table.pc-table').forEach(iniciarTabela);
}());
