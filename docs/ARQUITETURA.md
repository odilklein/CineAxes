# Arquitetura do PersonaCut

## Camadas

```
public/            apresentação (páginas + endpoints JSON)
  admin/           apresentação do painel (protegida por sessão)
src/Controllers/   orquestração (quiz, montagem do resultado)
src/Services/      regras puras e testáveis (Likert, matcher, mídia, cenas)
src/Models/        acesso a dados (PDO, prepared statements)
src/Core/          infraestrutura (Database, Session, Csrf, Http, Translator)
config/            configuração (app e conexão)
sql/               schema e seed
```

A regra de dependência é sempre para baixo: `public → Controllers → Services/Models → Core`.
Os serviços **não conhecem PDO, sessão nem tradução**; recebem e devolvem arrays.
Isso permite rodar `tests/run.php` sem banco.

## Fluxo do quiz

1. `public/quiz.php` carrega as perguntas do modo escolhido e serializa um
   `quiz-config` (ids, textos, eixo, cor, cena ilustrativa e token CSRF, além do
   guia editorial de cada eixo para o modal "Entender este eixo").
   A cena é resolvida no servidor por `Services\MidiaService` (`imagem`, `gif` ou
   `video_yt`), então o navegador só recebe URLs prontas.
2. `assets/js/quiz.js` controla a navegação, o rascunho em `localStorage` e o
   progresso; nada recarrega a página até o envio final. A cena da pergunta atual
   é renderizada na hora, a próxima é pré-carregada e vídeos do YouTube só carregam
   o iframe depois do clique (`mute=1`).
3. `POST /api/quiz.php` valida o token e o pacote de respostas, chama
   `QuizController::processar()` e devolve `{ ok, codigo, redirect }`.
4. `QuizController`:
   - valida todas as perguntas do modo (`LikertCalculatorService::validarRespostas`);
   - normaliza por eixo (`calcular`);
   - monta o vetor na ordem dos eixos;
   - resolve o arquétipo (`MatcherService::resolverArquetipo`);
   - gera um código único de 8 caracteres e persiste.
5. `public/resultado.php` recebe o código, reconstrói os matches a partir do vetor
   salvo e renderiza o dashboard. Nada de estado em memória entre requisições.

## Fluxo do resultado

`PerfilController::montar()` consulta resultado, eixos, arquétipos e entidades uma
vez, ranqueia por distância euclidiana e calcula a raridade sobre a base de perfis.
O "view model" já entrega as linhas prontas (percentuais, rótulos, intensidade e
cores), então a view não tem lógica de negócio.

O mesmo método monta os **insights** (maior desvio em relação à média da base,
eixo mais típico, década mediana do repertório, oposto, arquétipo secundário e
top 10) e carrega o **retrato salvo** em `retratos`, se existir.

### Retrato por IA

`Services\RetratoService` monta o prompt (sistema + JSON com arquétipo, eixos,
espelhos, diretor, estúdio, estante e raridade) e chama `Services\GroqService`.
O texto passa por limpeza e validação (tamanho, parágrafos, termos proibidos) antes
de ser persistido por `Models\Retrato`. A geração é sob demanda
(`public/api/retrato.php`), uma vez por código; qualquer falha mantém o retrato
determinístico em duas frases.

### Card 9:16

`public/resultado.php` renderiza o card no `pc-profile-card__side` com os mesmos
dados do perfil. As imagens passam por `img_proxy()` → `public/api/imagem.php`
(proxy same-origin com cache em `storage/cache/img`), porque `image.tmdb.org` não
envia CORS e o canvas do `html2canvas` seria contaminado. `assets/js/storycard.js`
captura em `scale: 4` (1080 × 1920), compartilha o PNG via Web Share API e, sem
suporte, baixa o arquivo.

## Modos de teste

`Pergunta::MODOS` define 36, 60 e 240 perguntas. `Pergunta::cotas` distribui a cota
por eixo em passos de 2 (mesmo número de afirmações por polo) e `paraModo` intercala
os eixos na exibição. A cota cresce com o modo, então o menor é subconjunto do maior.

## Enriquecimento TMDB

O catálogo é enriquecido por um comando de linha de comando, não em tempo de request:

1. `bin/tmdb_sync.php` percorre as 52 entidades e as 32 obras.
2. Para personagens, busca o filme de definição (pôster, popularidade) e o intérprete
   (retrato, país, nascimento).
3. Para diretores, busca a pessoa (retrato, país, nascimento, popularidade).
4. Para escolas/estúdios, busca a empresa e o primeiro logo disponível.
5. Para as obras, busca o filme e grava o pôster em `entidades_obras`.
6. Todas as consultas passam por `cache_tmdb` (sha256, TTL 24h).

O resultado é versionado por `bin/tmdb_export.php` em `sql/003_enriquecimento.sql`,
que roda depois do seed em uma instalação nova. Assim o `docker compose down -v`
continua com fotos e pôsteres mesmo sem internet.

`PerfilController` carrega as obras das entidades exibidas em uma única consulta
(`Obra::porEntidades`) e monta a estante, sem N+1.

## Cenas automáticas da TMDB

Cada pergunta guarda o filme em `perguntas.tmdb_id` (independente da mídia atual).
O fluxo de automação é:

1. `TmdbService::videos($tmdbId, 'en-US')` consulta
   `/movie/{id}/videos` e reaproveita o cache `cache_tmdb` de 24h.
2. `Services\SceneService::candidatos()` normaliza os vídeos e mantém apenas
   `site=YouTube` com `key`.
3. `SceneService::avaliarCandidatos()` descarta trailers, featurettes, previews
   e nomes genéricos; pontua `official`, palavras-chave do eixo (em
   `config/midia.php`), título do filme e idioma.
4. `SceneService::escolher()` devolve o melhor clipe acima de `score_minimo` ou
   `null` para manter a imagem.
5. `SceneService::meta()` grava `start/end` (padrão 0–12s), `video_nome`,
   `video_score`, `poster` do backdrop e `credito=YouTube`.
6. `bin/midia_autofill.php --dry-run` mostra a tabela; `--apply` grava em
   transação e cria backup em `storage/backups/`. No admin, o botão
   **Sugerir clipes da TMDB** usa o mesmo serviço e mostra os 8 melhores
   candidatos da pergunta.

Não há chave do YouTube nem download de vídeo: o quiz usa o iframe
`youtube-nocookie`, mudo e em loop. Imagens sem clipe recebem um Ken Burns
discreto via CSS (`[data-midia-tipo='imagem']`), desligado em
`prefers-reduced-motion`.

## Radar bipolar (SVG)

`Core\Radar` desenha os 8 eixos em um viewBox 400×400: anéis a cada 25%, anel
tracejado no neutro (50), raios, rótulos e o polígono da posição real. Os pontos
usam a cor do polo dominante de cada eixo. Não há dependência de Chart.js, o que
elimina o descompasso entre o radar e as barras duplas.

## Cálculo e matching

- Normalização por eixo com pesos variáveis (1 ou 2) e direção ±1; a fórmula é
  algebricamente equivalente à média ponderada de "esquerdice" do 12 Axes.
- **Afinidade composta**: 42% similaridade quadrática por eixo (com penalidade de
  até 45% para lados opostos) + 33% direção (cosseno aumentado) + 18% magnitude +
  7% outlier. A mesma nota ordena os cards e a sinergia.
- **Arquétipo por vetor completo**: os 16 arquétipos têm um vetor de 8 eixos
  (`arquetipos.eixo_1..8`) e o matcher escolhe o mais próximo, com um bônus de
  8 pontos para o tipo cujo eixo dominante coincide com o eixo mais extremo do
  usuário. Os dois eixos extremos continuam no dashboard, mas não decidem o
  título sozinhos.
- Raridade por diferença média ≤ 12 e mínimo de 100 perfis — métrica de
  comunidade, separada da afinidade.
- Perfil neutro (todos os eixos perto de 50) recebe o arquétipo virtual.

## Quick View

Cada card (`personagem`, `diretor`, `estudio`, ator ou filme) carrega um
`data-quickview` com o que já está no banco. O `assets/js/quickview.js` abre um
`<dialog>` com uma ficha por tipo:

- **personagem**: bio, frase icônica, intérprete (com a biografia da TMDB),
  dois polos do eixo mais fortes e um atalho para a ficha do filme;
- **ator**: foto, nascimento, país e trajetória;
- **diretor / estúdio-escola**: bio, frase, obras essenciais e polos;
- **filme**: sinopse, duração, gêneros, nota e elenco.

Fichas de filme e de ator completam os dados via `/api/quickview.php`
(`?tmdb=ID` ou `?pessoa=ID`), sempre com o cache de 24h da TMDB. Quando a ficha
do filme é aberta a partir de um personagem, o botão *Voltar* retorna à ficha
anterior. Sem chave ou sem rede, o modal mostra o que veio do banco.

## Cards de filme

Todo filme citado no perfil (leituras, década e guia dos eixos) aparece como
card com pôster. Os pôsteres são resolvidos **offline** por
`bin/filmes_cards.php`, que grava `config/filmes_cards.php` a partir de
`config/leituras.php`, `src/Data/eixos_guia.php` e `src/Data/decadas.php`;
`Models\FilmeCard` faz o lookup (com fallback para títulos com subtítulo) e o
partial `public/partials/card-filme.php` renderiza o card tipográfico quando o
filme não está no mapa. A página de resultado não faz chamadas de rede para
montar a lista.

## Frase do perfil

`RetratoService` pede ao modelo uma linha curta antes do retrato
(`FRASE: ...` / `TEXTO: ...`), guarda em `retratos.frase` e devolve em
`/api/retrato.php`. O JS preenche `[data-frase-perfil]` no card do perfil e na
retrospectiva; sem IA, o espaço usa a manchete da leitura mais forte. As
citações de personagens, diretores e escolas vêm do campo `frase` do catálogo,
nunca de texto gerado — assim ninguém atribui uma frase inventada a alguém real.

## Quiz em uma tela

No desktop (a partir de 900px) o cartão abre em **duas colunas**: a cena à
esquerda (4:3, `object-fit: cover`) e, à direita, tag do eixo, pergunta,
alternativas e ações. O conjunto (progresso + cartão + atalhos) é centralizado em
`calc(100svh - var(--pc-header))`, então responder não exige rolar a página.
Em janelas de até 720px de altura o cartão compacta (paddings menores e hint de
teclado oculto); abaixo de 900px o layout volta a uma coluna, com a cena 16:9
empilhada e a página rolando normalmente. O texto da pergunta usa **Public Sans**
(não a Bodoni de display) e o rodapé do site é removido no quiz.

O botão **Entender este eixo** abre um `<dialog>` com o guia editorial do eixo
(`src/Data/eixos_guia.php`, exposto por `Models\EixoGuia`): explicação longa, o
que significa ficar perto de cada polo, cenas de filmes e séries e as leituras
críticas que sustentam a régua. O conteúdo é estático e versionado — não depende
do banco.

## Catálogo, recomendação e streaming

Até a v1.5 o "catálogo" era editorial: 52 entidades do matcher, 32 obras curadas
e uma lista de filmes em PHP. A v1.6 adiciona um catálogo audiovisual de verdade,
ingerido da TMDB **fora do request** e persistido no MySQL.

### Tabelas

- `catalogo_obras` — filme ou série, com gêneros, países, idioma, ano, pôster e
  um vetor de 8 eixos (`eixo_1..8`) + `confianca`.
- `catalogo_generos` / `catalogo_obra_generos`, `catalogo_paises` /
  `catalogo_obra_paises` — normalização para filtro e índice.
- `catalogo_plataformas` / `catalogo_obra_plataformas` — disponibilidade por
  região e oferta (`flatrate`, `free`, `ads`). O preço é editorial (admin).
- `catalogo_pessoas` / `catalogo_creditos` — elenco (top 10) e direção.

### Ingestão

`bin/catalogo_sync.php` percorre as trilhas de `config/recomendacao.php` com
`/discover` (país + gênero + tipo), complementa plataformas de nicho com
`with_watch_providers` e busca o detalhe de cada título com
`append_to_response=watch/providers`. É retomável, idempotente e revalida o que
passou de 90 dias. `--creditos` busca elenco/direção; `--recalcular` refaz apenas
os vetores quando as regras editoriais mudam.

`bin/catalogo_export.php` gera `sql/010_catalogo_seed.sql`; `bin/deploy_pacote.php`
monta o pacote do InfinityFree (schema separado do snapshot para caber no limite
de 10 MB por arquivo).

### Vetor de uma obra

`RecomendacaoService::construir()` soma desvios editoriais de gênero (peso 1),
país (0,75), idioma (0,5) e década (0,35), e calcula `confianca` pelo número de
sinais. A afinidade usa o mesmo `MatcherService::compatibilidade()` dos espelhos,
encolhida em direção a 50 quando a confiança é baixa: uma obra com um único
gênero não compete com uma obra rica em metadados.

### Ranking de plataformas

`PlataformaRankingService` considera apenas assinatura inclusa (`flatrate`) na
região BR e títulos com afinidade mínima:

```
nota = 0,40·relevância + 0,25·acervo(log) + 0,15·exclusivos
     + 0,10·variedade de trilhas + 0,10·preço
```

Relevância soma a força dos 25 melhores títulos; acervo entra em log para volume
bruto não decidir sozinho; exclusivos consideram apenas o conjunto de plataformas
candidatas. O resultado é explicável ("N títulos do seu gosto, M exclusivos,
melhor para doramas").

### Trilhas e elenco

`TrilhaService` cruza as regras de `config/recomendacao.php` com as obras
pontuadas, exige mínimo de títulos disponíveis e limita a seis cards. `ElencoService`
agrega créditos das 80 melhores obras, ponderando posição no crédito e força do
título; direção não entra na lista de atores.

Nada disso chama rede no request: o perfil executa quatro consultas de catálogo
(obras, gêneros, países, plataformas) e calcula em memória. O quick view usa
sinopse/gêneros/plataformas locais para títulos do catálogo e só consulta a TMDB
quando a ficha é de um filme fora dele.

## Segurança

- 100% das queries com prepared statements e `ATTR_EMULATE_PREPARES = false`.
- Saída sempre escapada com `htmlspecialchars` (helper `e()`).
- CSRF em todos os POST (campo `csrf` ou header `X-CSRF-Token`).
- Sessão com cookie `HttpOnly`, `SameSite=Lax` e `Secure` sob HTTPS.
- Regeneração de sessão no login.
- Senhas com `password_hash` / `password_verify`.
- IP nunca é gravado em claro: apenas `sha256(ip + salt)` para abuso.
- Rate limit por IP em `src/Core/RateLimit.php`: 12 envios/minuto no quiz e
  8 tentativas/10 minutos no login (arquivo em `storage/cache/ratelimit`,
  *fail-open* se não for gravável).
- `public/.htaccess` envia CSP, `nosniff`, `Referrer-Policy`, `X-Frame-Options`,
  `Permissions-Policy`, oculta o `X-Powered-By` e aceita só `GET`, `POST` e
  `HEAD`.
- No Docker, `ServerTokens Prod` e `expose_php=Off` (`docker/apache-security.conf`
  e `docker/php-security.ini`).
- Admin com `noindex, nofollow` e checagem em toda página.
- Chaves de API (TMDB) ficam no servidor; o navegador só conversa com `/api/*`.

## Decisões de modelagem

- **8 colunas fixas** (`eixo_1..8`) em vez de tabela de junção: o domínio tem
  exatamente 8 eixos e isso mantém o cálculo legível e uma query por entidade.
- **`texto` + `texto_key`** nas perguntas e arquétipos: o banco é a fonte editável
  no admin e a chave fica pronta para i18n.
- **179 arquétipos possíveis reduzidos a 16**: um por eixo dominante + polo, com o
  segundo eixo como modificador de texto. Evita 224 linhas de conteúdo.
- **Base sintética declarada**: `resultados_usuarios.origem = 'sintetico'` deixa
  explícito que a raridade inicial não representa pessoas reais.

## Testes

`tests/_assert.php` é um micro-framework (sem PHPUnit). `run.php` executa os
testes de Likert, matcher, mídia, cena, radar, leituras, modos, guia, catálogo,
recomendação, ranking de plataformas, trilhas, elenco e rate limit. Retorna
código de saída diferente de zero em falha, o que permite usar em CI futuramente.
