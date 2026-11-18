# 📅 Cronograma de desenvolvimento — PersonaCut

Plano de 12 semanas para duas pessoas. O **MVP fecha na semana 6**; as semanas
seguintes entregam sinergia, i18n, TMDB e GeoCinema.

> As datas são relativas (Semana 1, Semana 2...). Troque cada semana pela data
> correspondente ao definir o início do semestre.
>
> **Status atual:** semanas 1–10 implementadas e o catálogo ampliado/enriquecido
> pela TMDB. A entrega extra de catálogo audiovisual trouxe ~4 mil filmes e
> séries, ranking de plataformas, trilhas de curadoria e elenco. Faltam
> GeoCinema, inglês e o acabamento da semana 12.

## Divisão de trabalho

| Papel | Responsabilidades principais |
| --- | --- |
| **Dev A — Back-end/BD** | schema, seed, PDO, serviços de cálculo, endpoints, testes, admin |
| **Dev B — Front-end/UX** | design system, landing, quiz, resultado, radar, acessibilidade |

Regras do combinado:

- Toda entrega entra por **pull request** com revisão do outro integrante.
- Cada PR: descrição, print da tela e checklist de teste.
- **Sexta-feira** fecha a semana: merge, board atualizado e planejamento.
- Ninguém fica bloqueado: tarefa travada é avisada no mesmo dia.

## Visão geral

| Semana | Tema | Entrega |
| --- | --- | --- |
| 1 | Base e conteúdo | Docker no ar + `docs/EIXOS.md` + matriz de perguntas |
| 2 | Banco | `schema.sql` + `seed.sql` (8 eixos, 32 perguntas, 52 entidades, 32 obras, 16 arquétipos) |
| 3 | Núcleo PHP | autoload, config, sessão, CSRF, `LikertCalculatorService` + testes |
| 4 | Matcher | `MatcherService` (ranking, arquétipo, raridade, sinergia) + testes |
| 5 | Quiz e resultado | `quiz.php`, `resultado.php`, radar SVG, 6 espelhos e estante |
| 6 | Admin e fechamento | login, CRUD de conteúdo, dashboard — **MVP** |
| 7 | Sinergia | `sinergia.php`, comparação e filme da dupla |
| 8 | Inglês | `lang/en.json`, seletor de idioma, revisão de microcopy |
| 9 | TMDB I | `TmdbService`, cache MySQL, fotos de personagens e atores |
| 10 | TMDB II | retratos de diretores, logos, obras e `003_enriquecimento.sql` |
| 11 | GeoCinema | Overpass (`nwr` + `out center`), CEP via BrasilAPI, Haversine |
| 12 | Acabamento | prints, README, ensaio com `down -v`, release `v1.0.0` |

## Detalhamento

### Semana 1 — Base e conteúdo
- Dev A: subir o stack, trocar o DocumentRoot para `public/`, `down -v` funcionando.
- Dev B: fechar o design system (tokens, tipografia, componentes) a partir da referência.
- Ambos: `docs/EIXOS.md` com a régua de 0–100 e as 32 afirmações.
- ✅ Entrega: `docker compose up` limpo e conteúdo aprovado.

### Semana 2 — Banco de dados
- Dev A: `001_schema.sql` (7 tabelas, checks, FKs, índices) e `002_seed.sql`.
- Dev A: base sintética de 400 perfis com distribuição triangular.
- Dev B: conferir tudo no phpMyAdmin; validar acentuação em utf8mb4.
- ✅ Entrega: banco populado na primeira subida.

### Semana 3 — Núcleo PHP
- Dev A: `src/Core` (Database, Session, Csrf, Http, Translator, Vetor), bootstrap e autoload.
- Dev A: `LikertCalculatorService` + 12 testes.
- Dev B: partials, header, footer, landing com dados do banco.
- ✅ Entrega: home 200 renderizando os 8 eixos.

### Semana 4 — Matcher
- Dev A: `MatcherService` (distância, afinidade, top por tipo, arquétipo, raridade, comparar).
- Dev A: `PerfilController` e `QuizController`.
- Dev B: tela de escolha de formato e esqueleto do quiz.
- ✅ Entrega: 26 testes verdes.

### Semana 5 — Quiz e resultado
- Dev B: quiz (progresso segmentado, teclado, rascunho, envio fetch).
- Dev B: resultado com barras duplas, radar, cards e compartilhamento.
- Dev A: `api/quiz.php`, geração de código e persistência.
- ✅ Entrega: fluxo completo 16 → resultado → link compartilhável.

### Semana 6 — Admin e fechamento do MVP
- Dev A: login, sessão, CSRF, dashboard e CRUD de eixos/perguntas/entidades/arquétipos.
- Dev B: revisão responsiva, acessibilidade e mensagens de erro.
- Ambos: checklist de segurança (prepared statements, escape, CSRF).
- ✅ Entrega: MVP em produção local.

### Semana 7 — Sinergia
- Dev A: `compareResults` no matcher, médias e vereditos.
- Dev B: `sinergia.php` com radar duplo e "filme da dupla".
- ✅ Entrega: dois códigos → compatibilidade.

### Semana 8 — Inglês
- Dev A: chaves `texto_key`/`*_key` já existentes + `Translator` por locale.
- Dev B: `lang/en.json`, seletor e revisão de layout com textos longos.
- ✅ Entrega: interface em pt-BR e EN.

### Semanas 9–10 — Enriquecimento TMDB
- Dev A: `TmdbService` com cURL, cache `sha256`, TTL 24h e chave só no servidor.
- Dev A: busca de filme/pessoa/empresa, retratos, logos e pôsteres das obras.
- Dev A: `bin/tmdb_sync.php` + `bin/tmdb_export.php` → `sql/003_enriquecimento.sql`.
- Dev B: fichas ricas (pôster, intérprete, biografia), estante e fallback de monograma.
- ✅ Entrega: catálogo com fotos e fichas, offline-safe após o export.

### Semana 11 — GeoCinema
- Dev A: Overpass (`nwr` + `out center`), Haversine e CEP via BrasilAPI.
- Dev B: widget "Assistir hoje perto de você" com geolocalização do navegador.
- ✅ Entrega: cinemas reais ordenados por distância.

### Semana 12 — Acabamento
- Ambos: prints em `docs/prints/`, README final e roteiro de apresentação.
- Dev A: ensaio do zero com `docker compose down -v`.
- Dev B: estados vazios, 404 amigável e polimento.
- ✅ Entrega: release `v1.0.0`.

## Marcos

| Marco | Semana | Critério |
| --- | --- | --- |
| M1 — Base rodando | 1 | Docker sobe e a home abre |
| M2 — Conteúdo no banco | 2 | 8 eixos, 32 perguntas, 52 entidades e 32 obras |
| M3 — Cálculo confiável | 4 | 45 testes verdes |
| M4 — Fluxo completo | 5 | Quiz → resultado → código |
| M5 — MVP | 6 | Admin com login e CRUD |
| M6 — Entrega | 12 | Release, catálogo enriquecido e apresentação |

## Riscos e planos B

| Risco | Plano B |
| --- | --- |
| Conflito de portas no Docker | trocar 8090/8091 no compose |
| Conteúdo atrasado | reduzir para 24 perguntas (3 por eixo) mantendo a fórmula |
| TMDB sem chave | `003_seed_cache.sql` com respostas pré-gravadas |
| Overpass instável | cache local + lista de cinemas de exemplo em `cache_tmdb` |
| Semana atrasada | cortar backlog (Elenco Ideal e GeoCinema) e manter o MVP |
