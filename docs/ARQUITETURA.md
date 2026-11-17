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
   `quiz-config` (ids, textos, eixo, cor, descrição, cena ilustrativa e token CSRF).
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

Cada card (`personagem`, `diretor`, `estudio` ou item da estante) carrega um
`data-quickview` com o que já está no banco. O `assets/js/quickview.js` abre um
`<dialog>` e, quando há `tmdb_id`, busca `/api/quickview.php?tmdb=ID` para
sinopse, duração, gêneros e elenco. O endpoint usa `TmdbService::filmeComCreditos`
e o cache de 24h. Sem chave ou sem rede, o modal mostra o que veio do banco e um
aviso discreto — nunca quebra a página.

## Quiz em uma tela

No desktop o card continua em **uma coluna**: tag, cena 16:9 em tamanho grande,
pergunta, respostas e ações. A cena é exibida com `object-fit: contain`, então o
frame inteiro aparece; o formulário rola junto. O texto da pergunta usa Public
Sans e as respostas são compactas, mas a altura da cena é priorizada. O texto da pergunta usa **Public Sans** (não a Bodoni
de display), com respostas compactas e o rodapé do site removido no quiz. Em telas
de 768px de altura o hint de teclado é ocultado e o formulário inteiro cabe sem
scroll; em telas até 600px o banner reduz para 150px e as ações quebram em duas
linhas.

## Segurança

- 100% das queries com prepared statements e `ATTR_EMULATE_PREPARES = false`.
- Saída sempre escapada com `htmlspecialchars` (helper `e()`).
- CSRF em todos os POST (campo `csrf` ou header `X-CSRF-Token`).
- Sessão com cookie `HttpOnly`, `SameSite=Lax` e `Secure` sob HTTPS.
- Regeneração de sessão no login.
- Senhas com `password_hash` / `password_verify`.
- IP nunca é gravado em claro: apenas `sha256(ip + salt)` para abuso.
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

`tests/_assert.php` é um micro-framework (sem PHPUnit). `run.php` executa
`LikertTest.php`, `MatcherTest.php`, `MidiaTest.php`, `SceneTest.php`,
`RadarTest.php` e `ModosTest.php` (cotas dos três modos) e retorna código de saída
diferente de zero em falha, o que permite usar em CI futuramente.
