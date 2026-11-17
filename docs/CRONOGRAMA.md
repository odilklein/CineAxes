# 📅 Cronograma de desenvolvimento — CineAxes

Plano de 12 semanas para duas pessoas, seguindo a mesma evolução do projeto de referência da disciplina ([barbearia](https://github.com/odilklein/barbearia)): estrutura → formulário → banco → CRUD → login → dashboard → POO.

> As datas são **relativas** (Semana 1, Semana 2...). Ao definir o início do semestre, basta trocar cada semana pela data correspondente.

## Divisão de trabalho

| Papel | Responsabilidades principais |
| --- | --- |
| **Dev A — Back-end/BD** | modelagem do banco, conexão PDO, classes, validações, sessões, regras de negócio |
| **Dev B — Front-end/UX** | telas Bootstrap, formulários, JavaScript (máscaras, validação, assentos), usabilidade e responsividade |

Regras do combinado:

- Toda entrega entra por **pull request** com revisão do outro integrante (não fazer push direto na `main`).
- Cada PR precisa: descrição do que foi feito, print da tela e checklist de teste.
- **Sexta-feira** é o dia de fechamento da semana: merge do PR, atualização do board e planejamento da semana seguinte.
- Ninguém fica bloqueado: se uma tarefa travar, avisa no grupo no mesmo dia e o outro ajuda.

## Visão geral

| Semana | Tema | Entrega da semana |
| --- | --- | --- |
| 1 | Preparação do ambiente e estrutura | Repositório rodando com Docker |
| 2 | Layout base e catálogo de filmes | Cartaz público com dados fictícios |
| 3 | Banco de dados e conexão | Schema + seed + `conexao.php` |
| 4 | CRUD de filmes | Admin cadastra/edita/exclui filme |
| 5 | Salas e sessões | Admin monta a programação |
| 6 | Página do filme e sessões públicas | Público vê horários disponíveis |
| 7 | Reserva de ingressos | Reserva gravada no banco |
| 8 | Mapa de assentos (JS) | Escolha de assentos com validação |
| 9 | Login e proteção do painel | Rotas admin protegidas |
| 10 | Dashboard e filtros | Indicadores e busca de reservas |
| 11 | POO e refatoração | Classes `Filme`, `Sessao`, `Reserva` |
| 12 | Acabamento e apresentação | Documentação, prints e demo |

## Detalhamento semanal

### Semana 1 — Preparação do ambiente e estrutura
**Meta:** todo mundo consegue rodar o projeto.

- Dev A: subir `docker-compose.yml`, `Dockerfile`, `.env.example`; validar apache + PHP + MySQL.
- Dev B: criar `include/cabecalho.php` com navbar Bootstrap e tema claro/escuro (localStorage).
- Ambos: alinhar nome/identidade visual, criar board de tarefas e o `docs/CRONOGRAMA.md`.
- ✅ Entrega: `docker compose up` funcionando para os dois.

### Semana 2 — Layout base e catálogo de filmes
**Meta:** página inicial com cartaz, ainda com dados fixos.

- Dev A: `include/config.php` (nome do cinema, funções auxiliares) e estrutura de pastas.
- Dev B: `index.php` com grid de cards Bootstrap (pôster, título, gênero, classificação) usando array fixo.
- ✅ Entrega: cartaz responsivo com 6 filmes fictícios.

### Semana 3 — Banco de dados e conexão
**Meta:** banco criado e populado.

- Dev A: `sql/001_schema.sql` (usuarios, generos, filmes, salas, sessoes, reservas) e `sql/002_seed.sql`.
- Dev B: `include/conexao.php` + teste de conexão; conferir tabelas no phpMyAdmin.
- ✅ Entrega: schema e seed aplicados automaticamente na subida do Docker.

### Semana 4 — CRUD de filmes
**Meta:** primeiro CRUD completo.

- Dev A: `classes/Filme.php` com `salvar()`, `listarTodos()`, `buscar()`, `atualizar()`, `excluir()`.
- Dev B: `admin/filmes.php` (listagem + busca) e formulário de cadastro/edição.
- ✅ Entrega: admin gerencia o catálogo; exclusão com confirmação.

### Semana 5 — Salas e sessões
**Meta:** programação do cinema montada.

- Dev A: CRUD de salas e sessoes; validação de conflito (mesma sala, mesma data/hora).
- Dev B: telas de salas e sessões, com selects de filme/sala e campos de data/hora/preço.
- ✅ Entrega: admin cadastra sessão vinculada a filme e sala.

### Semana 6 — Página do filme e sessões públicas
**Meta:** público consulta a programação real.

- Dev A: consulta de sessões por filme e por data (`Sessao::listarPorFilme()`).
- Dev B: `filme.php` com sinopse, trailer e lista de sessões; filtro por data no `index.php`.
- ✅ Entrega: cartaz alimentado pelo banco + página de detalhes.

### Semana 7 — Reserva de ingressos
**Meta:** primeira reserva gravada.

- Dev A: `classes/Reserva.php`, cálculo de valor total e validação de vagas.
- Dev B: `sessao.php` (formulário de reserva) e `reserva_sucesso.php` (comprovante).
- ✅ Entrega: reserva persistida e comprovante exibido.

### Semana 8 — Mapa de assentos (JavaScript)
**Meta:** escolha visual de assentos.

- Dev B: grid de assentos em JS (selecionados, ocupados, quantidade máxima), máscara de telefone e validação do formulário.
- Dev A: endpoint/consulta de assentos ocupados por sessão e gravação da lista de assentos.
- ✅ Entrega: assentos escolhidos, validados no cliente e no servidor.

### Semana 9 — Login e proteção do painel
**Meta:** painel restrito.

- Dev A: `login.php`, `auth.php` (`password_verify`), `logout.php`, `include/check_login.php` e `gerar_senha.php`.
- Dev B: tela de login estilizada e ajuste da navbar conforme sessão.
- ✅ Entrega: rotas `admin/*` redirecionam para o login quando não autenticado.

### Semana 10 — Dashboard e filtros
**Meta:** painel útil no dia a dia.

- Dev A: consultas de indicadores (sessões do dia, ingressos vendidos, receita, ocupação).
- Dev B: `admin/dashboard.php` com cards e `admin/reservas.php` com filtros (cliente, data, status) e ações de confirmar/cancelar.
- ✅ Entrega: dashboard com números reais e reservas gerenciáveis.

### Semana 11 — POO e refatoração
**Meta:** código organizado e explicável na apresentação.

- Dev A: mover regras para as classes, eliminar SQL duplicado nas páginas, tratar erros com `try/catch`.
- Dev B: revisar acessibilidade, responsividade e mensagens de feedback (alertas de sucesso/erro).
- Ambos: revisão de segurança — prepared statements em 100% das queries, `htmlspecialchars` em todas as saídas, CSRF nos formulários.
- ✅ Entrega: projeto refatorado sem regressão funcional.

### Semana 12 — Acabamento e apresentação
**Meta:** entrega final.

- Ambos: dados de demonstração, prints em `docs/prints/`, README final, roteiro de apresentação.
- Dev A: ensaio do fluxo completo (subir do zero com `down -v` e validar).
- Dev B: polimento visual, tela de "em breve" e 404 amigável.
- ✅ Entrega: release `v1.0.0` e apresentação.

## Marcos (milestones)

| Marco | Semana | Critério |
| --- | --- | --- |
| M1 — Base rodando | 1 | Docker sobe e abre a home |
| M2 — Catálogo no banco | 4 | Filmes vindos do MySQL, CRUD no admin |
| M3 — Programação | 6 | Sessões públicas por filme e data |
| M4 — Reservas | 8 | Reserva com assentos validados |
| M5 — Painel completo | 10 | Login + dashboard + gestão de reservas |
| M6 — Entrega | 12 | Release e apresentação |

## Backlog (se sobrar tempo)

- Cadastro de clientes com histórico de reservas.
- Combos de pipoca e ingressos com desconto.
- Relatório de ocupação por sala com gráfico (Chart.js).
- Envio de comprovante por e-mail ou WhatsApp.
- API JSON simples para consulta de sessões.

## Riscos e planos B

| Risco | Plano B |
| --- | --- |
| Ambiente Docker com problema em uma máquina | Rodar com PHP/MySQL instalados localmente; `conexao.php` lê variáveis de ambiente |
| Semana atrasada | Cortar itens do backlog e o mapa visual de assentos (usar select de quantidade) |
| Conflito de merge | PRs pequenos, sempre puxar `main` antes de começar a tarefa |
| Falta de tempo para o dashboard | Priorizar CRUD + reservas, que valem mais na avaliação |
