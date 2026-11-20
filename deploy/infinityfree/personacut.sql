-- =============================================================================
-- PersonaCut — banco completo (sem catálogo)
-- Gerado em 2026-11-20 02:00:05 por bin/deploy_pacote.php
-- Fontes: sql/001_schema.sql, sql/002_seed.sql, sql/003_enriquecimento.sql, sql/004_cenas_video.sql, sql/005_rotulos.sql, sql/009_catalogo.sql
-- Importe primeiro personacut.sql e depois catalogo.sql.
-- =============================================================================
-- >>> sql/001_schema.sql
-- =============================================================================
-- PersonaCut — schema inicial (MySQL 8.0+)
-- Executado automaticamente pelo MySQL na primeira subida do Docker.
-- Reexecutável: derruba as tabelas na ordem inversa das dependências.
-- =============================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS bookmarks;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS cache_tmdb;
DROP TABLE IF EXISTS retratos;
DROP TABLE IF EXISTS resultados_usuarios;
DROP TABLE IF EXISTS arquetipos;
DROP TABLE IF EXISTS entidades_obras;
DROP TABLE IF EXISTS entidades_match;
DROP TABLE IF EXISTS perguntas;
DROP TABLE IF EXISTS eixos;
DROP TABLE IF EXISTS usuarios_admin;
SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- Acesso ao painel administrativo (CRUD de conteúdo e dashboard)
-- -----------------------------------------------------------------------------
CREATE TABLE usuarios_admin (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome         VARCHAR(100) NOT NULL,
    usuario      VARCHAR(50)  NOT NULL,
    senha        VARCHAR(255) NOT NULL,
    perfil       ENUM('admin', 'editor') NOT NULL DEFAULT 'editor',
    criado_em    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuarios_admin_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Contas públicas (opcionais): necessárias apenas para salvar bookmarks.
-- Separadas de `usuarios_admin`, que é o painel editorial.
-- -----------------------------------------------------------------------------
CREATE TABLE usuarios (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome         VARCHAR(80)  NOT NULL,
    usuario      VARCHAR(40)  NOT NULL,
    senha        VARCHAR(255) NOT NULL,
    criado_em    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_login DATETIME NULL,
    UNIQUE KEY uq_usuarios_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Bookmark de filme/série: "quero ver" ou "já vi". Uma linha por usuário/obra;
-- o status muda quando o usuário troca de lista. Não some da lista ao marcar.
-- -----------------------------------------------------------------------------
CREATE TABLE bookmarks (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id   INT UNSIGNED NOT NULL,
    tmdb_id      INT UNSIGNED NOT NULL,
    tipo         ENUM('filme', 'serie') NOT NULL DEFAULT 'filme',
    status       ENUM('quero', 'visto') NOT NULL,
    titulo       VARCHAR(200) NOT NULL,
    ano          SMALLINT UNSIGNED NULL,
    poster_path  VARCHAR(160) NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_bookmarks_obra (usuario_id, tmdb_id, tipo),
    KEY idx_bookmarks_status (usuario_id, status, updated_at),
    CONSTRAINT fk_bookmarks_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Os 6 eixos do modelo v2. `polo_*_key` é a chave de tradução; as cores e ícones alimentam
-- barras, cards e gráfico de radar.
-- `tmdb_genres_*`: IDs oficiais de gênero (o discover usa IDs numéricos).
-- `tmdb_keywords_*`: nomes de keywords; o TmdbService resolve para IDs e cacheia.
-- -----------------------------------------------------------------------------
CREATE TABLE eixos (
    id                 TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ordem              TINYINT UNSIGNED NOT NULL,
    slug               VARCHAR(40)  NOT NULL,
    nome               VARCHAR(60)  NOT NULL,
    descricao          TEXT NULL,
    polo_esq_key       VARCHAR(80)  NOT NULL,
    polo_esq_nome      VARCHAR(60)  NOT NULL,
    polo_dir_key       VARCHAR(80)  NOT NULL,
    polo_dir_nome      VARCHAR(60)  NOT NULL,
    cor_esq            CHAR(7)      NOT NULL,
    cor_dir            CHAR(7)      NOT NULL,
    icone_esq          VARCHAR(40)  NOT NULL,
    icone_dir          VARCHAR(40)  NOT NULL,
    tmdb_genres_esq    VARCHAR(120) NOT NULL DEFAULT '',
    tmdb_genres_dir    VARCHAR(120) NOT NULL DEFAULT '',
    tmdb_keywords_esq  VARCHAR(255) NOT NULL DEFAULT '',
    tmdb_keywords_dir  VARCHAR(255) NOT NULL DEFAULT '',
    UNIQUE KEY uq_eixos_slug (slug),
    UNIQUE KEY uq_eixos_ordem (ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Perguntas Likert.
-- `direcao = +1` empurra para o polo direito; `-1` para o polo esquerdo.
-- `texto` guarda o pt-BR editável no admin; `texto_key` fica pronto para i18n.
-- -----------------------------------------------------------------------------
CREATE TABLE perguntas (
    id          SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    eixo_id     TINYINT UNSIGNED  NOT NULL,
    texto_key   VARCHAR(80)  NOT NULL,
    texto       VARCHAR(255) NOT NULL,
    direcao     TINYINT      NOT NULL,
    peso        TINYINT UNSIGNED NOT NULL DEFAULT 1,
    modo_rapido TINYINT(1)   NOT NULL DEFAULT 0,
    ordem       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    -- Filme associado à pergunta, usado para buscar clipes oficiais na TMDB.
    -- Independente da mídia atual: trocar a imagem/GIF não perde o filme.
    tmdb_id     INT UNSIGNED NULL,
    -- Cena ilustrativa opcional. `midia_url_ou_id` guarda o caminho TMDB
    -- (ex.: /abc.jpg), a URL de um GIF ou o ID de um vídeo do YouTube.
    -- `midia_meta` guarda legenda, ano, start/end (video_yt) e a fonte.
    midia_tipo       ENUM('imagem', 'gif', 'video_yt') NULL DEFAULT NULL,
    midia_url_ou_id  VARCHAR(255) NULL,
    midia_meta       JSON NULL,
    CONSTRAINT fk_perguntas_eixo FOREIGN KEY (eixo_id) REFERENCES eixos (id) ON DELETE CASCADE,
    CONSTRAINT chk_perguntas_direcao CHECK (direcao IN (-1, 1)),
    CONSTRAINT chk_perguntas_peso CHECK (peso IN (1, 2)),
    UNIQUE KEY uq_perguntas_texto_key (texto_key),
    KEY idx_perguntas_eixo (eixo_id),
    KEY idx_perguntas_modo (modo_rapido, ordem),
    KEY idx_perguntas_tmdb (tmdb_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Entidades do matcher: personagens, diretores, estúdios/escolas e filmes.
-- O vetor de 8 eixos é legado: o app usa `vetor6` (projeção) e, para
-- diretores/escolas, o centroide da filmografia. Personagens não entram no match.
-- `imagem_url` é opcional: sem ela o front renderiza um monograma na cor do eixo.
-- -----------------------------------------------------------------------------
CREATE TABLE entidades_match (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo           ENUM('personagem', 'diretor', 'estudio') NOT NULL,
    nome           VARCHAR(120) NOT NULL,
    obra_origem    VARCHAR(160) NOT NULL,
    ano            SMALLINT UNSIGNED NULL,
    frase_key      VARCHAR(80)  NOT NULL,
    frase          VARCHAR(255) NOT NULL,
    bio            TEXT NULL,
    pais           VARCHAR(80) NULL,
    nascimento     SMALLINT UNSIGNED NULL,
    ator_nome      VARCHAR(120) NULL,
    ator_tmdb_id   INT UNSIGNED NULL,
    ator_foto_url  VARCHAR(255) NULL,
    imagem_url     VARCHAR(255) NULL,
    popularidade   DECIMAL(8,2) NULL,
    enriquecido_em DATETIME NULL,
    tmdb_id        INT UNSIGNED NULL,
    eixo_1      TINYINT UNSIGNED NOT NULL,
    eixo_2      TINYINT UNSIGNED NOT NULL,
    eixo_3      TINYINT UNSIGNED NOT NULL,
    eixo_4      TINYINT UNSIGNED NOT NULL,
    eixo_5      TINYINT UNSIGNED NOT NULL,
    eixo_6      TINYINT UNSIGNED NOT NULL,
    eixo_7      TINYINT UNSIGNED NOT NULL,
    eixo_8      TINYINT UNSIGNED NOT NULL,
    -- Procedência do vetor: editorial (à mão), catalogo (centroide das obras)
    -- ou misto. Ver docs/CALIBRACAO.md e bin/calibrar_entidades.php.
    vetor_origem ENUM('editorial', 'catalogo', 'misto') NOT NULL DEFAULT 'editorial',
    CONSTRAINT chk_ent_e1 CHECK (eixo_1 BETWEEN 0 AND 100),
    CONSTRAINT chk_ent_e2 CHECK (eixo_2 BETWEEN 0 AND 100),
    CONSTRAINT chk_ent_e3 CHECK (eixo_3 BETWEEN 0 AND 100),
    CONSTRAINT chk_ent_e4 CHECK (eixo_4 BETWEEN 0 AND 100),
    CONSTRAINT chk_ent_e5 CHECK (eixo_5 BETWEEN 0 AND 100),
    CONSTRAINT chk_ent_e6 CHECK (eixo_6 BETWEEN 0 AND 100),
    CONSTRAINT chk_ent_e7 CHECK (eixo_7 BETWEEN 0 AND 100),
    CONSTRAINT chk_ent_e8 CHECK (eixo_8 BETWEEN 0 AND 100),
    UNIQUE KEY uq_entidades_tipo_nome (tipo, nome),
    KEY idx_entidades_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Obras essenciais de diretores e estúdios (a "estante" do perfil).
-- Personagens usam a própria obra_origem como obra principal.
-- -----------------------------------------------------------------------------
CREATE TABLE entidades_obras (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entidade_id  INT UNSIGNED NOT NULL,
    titulo       VARCHAR(160) NOT NULL,
    ano          SMALLINT UNSIGNED NULL,
    personagem   VARCHAR(120) NULL,
    tmdb_id      INT UNSIGNED NULL,
    poster_url   VARCHAR(255) NULL,
    ordem        TINYINT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_obras_entidade FOREIGN KEY (entidade_id) REFERENCES entidades_match (id) ON DELETE CASCADE,
    UNIQUE KEY uq_obras_entidade_titulo (entidade_id, titulo),
    KEY idx_obras_entidade (entidade_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Arquétipos: um por (eixo dominante, polo) = 12 linhas no modelo de 6 eixos.
-- O segundo eixo é um modificador opcional de texto, não uma chave.
-- -----------------------------------------------------------------------------
CREATE TABLE arquetipos (
    id                 SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    eixo_dominante_1   TINYINT UNSIGNED NOT NULL,
    polo_1             ENUM('E', 'D') NOT NULL,
    eixo_dominante_2   TINYINT UNSIGNED NULL,
    polo_2             ENUM('E', 'D') NULL,
    -- Vetor de referência do arquétipo, usado pelo matching por proximidade.
    eixo_1             TINYINT UNSIGNED NOT NULL DEFAULT 50,
    eixo_2             TINYINT UNSIGNED NOT NULL DEFAULT 50,
    eixo_3             TINYINT UNSIGNED NOT NULL DEFAULT 50,
    eixo_4             TINYINT UNSIGNED NOT NULL DEFAULT 50,
    eixo_5             TINYINT UNSIGNED NOT NULL DEFAULT 50,
    eixo_6             TINYINT UNSIGNED NOT NULL DEFAULT 50,
    titulo_key         VARCHAR(80)  NOT NULL,
    descricao_key      VARCHAR(80)  NOT NULL,
    ritual_cinema_key  VARCHAR(80)  NOT NULL,
    titulo             VARCHAR(120) NOT NULL,
    descricao          TEXT NOT NULL,
    ritual_cinema      VARCHAR(255) NOT NULL,
    CONSTRAINT fk_arq_eixo1 FOREIGN KEY (eixo_dominante_1) REFERENCES eixos (id) ON DELETE CASCADE,
    CONSTRAINT fk_arq_eixo2 FOREIGN KEY (eixo_dominante_2) REFERENCES eixos (id) ON DELETE SET NULL,
    UNIQUE KEY uq_arquetipos_dominante (eixo_dominante_1, polo_1),
    UNIQUE KEY uq_arquetipos_titulo_key (titulo_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Resultados. `codigo_unico` é o identificador público e compartilhável.
-- `respostas_json` permite recalcular o perfil se a fórmula mudar.
-- `origem = 'sintetico'` identifica a base estatística inicial (raridade).
-- `ip_hash` guarda sha256 truncado do IP — nunca o IP em texto claro.
-- -----------------------------------------------------------------------------
CREATE TABLE resultados_usuarios (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo_unico  CHAR(8) NOT NULL,
    idioma        VARCHAR(5) NOT NULL DEFAULT 'pt-BR',
    modo          ENUM('rapido', 'completo', 'extremo') NOT NULL DEFAULT 'completo',
    -- Versão do conjunto de perguntas que gerou o vetor (NULL = anterior ao registro).
    questionario_versao VARCHAR(40) NULL,
    respostas_json JSON NULL,
    eixo_1        DECIMAL(5,2) NOT NULL,
    eixo_2        DECIMAL(5,2) NOT NULL,
    eixo_3        DECIMAL(5,2) NOT NULL,
    eixo_4        DECIMAL(5,2) NOT NULL,
    eixo_5        DECIMAL(5,2) NOT NULL,
    eixo_6        DECIMAL(5,2) NOT NULL,
    arquetipo_id  SMALLINT UNSIGNED NULL,
    origem        ENUM('real', 'sintetico') NOT NULL DEFAULT 'real',
    ip_hash       CHAR(64) NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_resultados_arquetipo FOREIGN KEY (arquetipo_id) REFERENCES arquetipos (id) ON DELETE SET NULL,
    UNIQUE KEY uq_resultados_codigo (codigo_unico),
    KEY idx_resultados_created (created_at),
    KEY idx_resultados_arquetipo (arquetipo_id),
    KEY idx_resultados_origem (origem),
    CONSTRAINT chk_res_e1 CHECK (eixo_1 BETWEEN 0 AND 100),
    CONSTRAINT chk_res_e2 CHECK (eixo_2 BETWEEN 0 AND 100),
    CONSTRAINT chk_res_e3 CHECK (eixo_3 BETWEEN 0 AND 100),
    CONSTRAINT chk_res_e4 CHECK (eixo_4 BETWEEN 0 AND 100),
    CONSTRAINT chk_res_e5 CHECK (eixo_5 BETWEEN 0 AND 100),
    CONSTRAINT chk_res_e6 CHECK (eixo_6 BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Retrato redigido por IA (uma vez por resultado; nunca altera a nota).
-- -----------------------------------------------------------------------------
CREATE TABLE retratos (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo_unico  CHAR(8) NOT NULL,
    idioma        VARCHAR(5) NOT NULL DEFAULT 'pt-BR',
    texto         MEDIUMTEXT NOT NULL,
    frase         VARCHAR(280) NULL,
    -- JSON com a narrativa por perfil (retrato, leituras, arquétipo, diretor,
    -- repertório e descobertas). Ver Services\NarrativaService.
    narrativa_json JSON NULL,
    modelo        VARCHAR(80) NOT NULL,
    prompt_versao VARCHAR(20) NOT NULL,
    tokens        INT UNSIGNED NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_retratos_codigo (codigo_unico),
    CONSTRAINT fk_retratos_resultado FOREIGN KEY (codigo_unico)
        REFERENCES resultados_usuarios (codigo_unico) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Cache das respostas da TMDB (TTL de 24h, ver src/Services/TmdbService.php).
-- cache_key = sha256 hex da query normalizada (64 caracteres).
-- -----------------------------------------------------------------------------
CREATE TABLE cache_tmdb (
    cache_key    CHAR(64) PRIMARY KEY,
    payload_json LONGTEXT NOT NULL,
    expires_at   DATETIME NOT NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_cache_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- >>> sql/002_seed.sql
-- =============================================================================
-- PersonaCut — seed do modelo v2 (6 eixos)
-- Gerado para a migração 6D. Reexecutável: o schema 001 derruba as tabelas.
-- =============================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 1. Eixos (6)
-- -----------------------------------------------------------------------------
INSERT INTO eixos
    (ordem, slug, nome, descricao, polo_esq_key, polo_esq_nome, polo_dir_key, polo_dir_nome,
     cor_esq, cor_dir, icone_esq, icone_dir, tmdb_genres_esq, tmdb_genres_dir,
     tmdb_keywords_esq, tmdb_keywords_dir)
VALUES
    (1, 'estetica', 'Estética', 'Onde o filme circula: do blockbuster ao circuito de arte.', 'eixo.estetica.esq', 'Popular', 'eixo.estetica.dir', 'Autoral', '#b0553c', '#4f6480', 'users', 'feather', '28,12,35,10751', '18,36,99,10749', '', ''),
    (2, 'tom', 'Tom', 'O registro afetivo: da leveza ao desconforto.', 'eixo.tom.esq', 'Claro', 'eixo.tom.dir', 'Sombrio', '#c08a2e', '#3f4a5a', 'sun', 'moon', '35,10749,10751,16', '27,80,53,9648', '', ''),
    (3, 'mundo', 'Mundo', 'O grau de invenção: do real imediato ao mundo inventado.', 'eixo.mundo.esq', 'Real', 'eixo.mundo.dir', 'Imaginário', '#5c7048', '#7a4a63', 'home', 'planet', '99,18,36,80', '14,878,16,12', '', ''),
    (4, 'ritmo', 'Ritmo', 'A experiência da duração: do plano que respira ao corte que acelera.', 'eixo.ritmo.esq', 'Calmo', 'eixo.ritmo.dir', 'Intenso', '#3f6b6b', '#b04a5a', 'moon', 'bolt', '18,99,37', '28,53,12,27', '', ''),
    (5, 'escala', 'Escala', 'A medida do drama: do quarto ao planeta.', 'eixo.escala.esq', 'Íntimo', 'eixo.escala.dir', 'Épico', '#8c5a2b', '#2f4858', 'frame', 'mountain', '18,10749,35', '12,10752,878,14', '', ''),
    (6, 'amplitude', 'Amplitude', 'O quanto você sai do familiar: país, época, gênero e risco.', 'eixo.amplitude.esq', 'Circunscrito', 'eixo.amplitude.dir', 'Omnívoro', '#6b5b7b', '#a8792c', 'lock', 'globe', '28,35,10751', '99,36,37,10752', '', '');

-- -----------------------------------------------------------------------------
-- 2. Perguntas (48: 8 por eixo, 4 por polo)
-- `modo_rapido=1` marca o núcleo (4 por eixo) usado nos formatos curtos.
-- -----------------------------------------------------------------------------
INSERT INTO perguntas
    (eixo_id, texto_key, texto, direcao, peso, modo_rapido, ordem, tmdb_id,
     midia_tipo, midia_url_ou_id, midia_meta)
VALUES
    (1, 'q6.estetica.1', 'Um filme premiado em festival me interessa mais do que um blockbuster de bilheteria.', 1, 2, 1, 1, NULL, NULL, NULL, NULL),
    (1, 'q6.estetica.2', 'Prefiro um filme comercial bem feito a um filme de arte que exige paciência.', -1, 2, 1, 7, NULL, NULL, NULL, NULL),
    (1, 'q6.estetica.3', 'Gosto quando o filme tem a assinatura de um autor, mesmo que isso o torne mais difícil.', 1, 1, 1, 13, NULL, NULL, NULL, NULL),
    (1, 'q6.estetica.4', 'Escolho o que está em cartaz no shopping antes de procurar um cinema de rua.', -1, 1, 1, 19, NULL, NULL, NULL, NULL),
    (1, 'q6.estetica.5', 'Filmes de diretores desconhecidos, vindos de outros circuitos, me atraem.', 1, 1, 0, 25, NULL, NULL, NULL, NULL),
    (1, 'q6.estetica.6', 'Gosto de franquias e continuações porque já sei o que vou encontrar.', -1, 1, 0, 31, NULL, NULL, NULL, NULL),
    (1, 'q6.estetica.7', 'Prefiro um filme que arrisca uma linguagem própria a um que segue a fórmula.', 1, 1, 0, 37, NULL, NULL, NULL, NULL),
    (1, 'q6.estetica.8', 'O que eu quero é entretenimento garantido, não uma experiência de festival.', -1, 1, 0, 43, NULL, NULL, NULL, NULL),
    (2, 'q6.tom.1', 'Gosto de filmes que me deixam desconfortável e pensando no que vi.', 1, 2, 1, 2, NULL, NULL, NULL, NULL),
    (2, 'q6.tom.2', 'Vou ao cinema para sair leve e bem-humorado.', -1, 2, 1, 8, NULL, NULL, NULL, NULL),
    (2, 'q6.tom.3', 'Histórias com finais tristes ou ambíguos me parecem mais honestas.', 1, 1, 1, 14, NULL, NULL, NULL, NULL),
    (2, 'q6.tom.4', 'Evito filmes que me deixam para baixo.', -1, 1, 1, 20, NULL, NULL, NULL, NULL),
    (2, 'q6.tom.5', 'Terror e suspense psicológico estão entre os meus gêneros favoritos.', 1, 1, 0, 26, NULL, NULL, NULL, NULL),
    (2, 'q6.tom.6', 'Comédia e romance são meus gêneros de conforto.', -1, 1, 0, 32, NULL, NULL, NULL, NULL),
    (2, 'q6.tom.7', 'Prefiro um drama pesado a uma comédia leve.', 1, 1, 0, 38, NULL, NULL, NULL, NULL),
    (2, 'q6.tom.8', 'Gosto de finais que resolvem tudo e deixam todo mundo bem.', -1, 1, 0, 44, NULL, NULL, NULL, NULL),
    (3, 'q6.mundo.1', 'Mundos inventados, com regras próprias, me prendem mais que histórias realistas.', 1, 2, 1, 3, NULL, NULL, NULL, NULL),
    (3, 'q6.mundo.2', 'Prefiro histórias que poderiam acontecer comigo ou com alguém que conheço.', -1, 2, 1, 9, NULL, NULL, NULL, NULL),
    (3, 'q6.mundo.3', 'Ficção científica e fantasia são minhas portas de entrada para o cinema.', 1, 1, 1, 15, NULL, NULL, NULL, NULL),
    (3, 'q6.mundo.4', 'Documentários e dramas sociais me prendem mais que fantasia.', -1, 1, 1, 21, NULL, NULL, NULL, NULL),
    (3, 'q6.mundo.5', 'Gosto de mitologias, criaturas e universos que não existem.', 1, 1, 0, 27, NULL, NULL, NULL, NULL),
    (3, 'q6.mundo.6', 'Gosto de filmes que parecem registrar a rua, o bairro, a vida como ela é.', -1, 1, 0, 33, NULL, NULL, NULL, NULL),
    (3, 'q6.mundo.7', 'Um filme que cria uma realidade própria me interessa mais que um retrato do cotidiano.', 1, 1, 0, 39, NULL, NULL, NULL, NULL),
    (3, 'q6.mundo.8', 'Universos muito inventados me afastam da história.', -1, 1, 0, 45, NULL, NULL, NULL, NULL),
    (4, 'q6.ritmo.1', 'Perseguições, cortes rápidos e adrenalina me mantêm dentro do filme.', 1, 2, 1, 4, NULL, NULL, NULL, NULL),
    (4, 'q6.ritmo.2', 'Gosto de planos longos, silêncios e cenas que respiram.', -1, 2, 1, 10, NULL, NULL, NULL, NULL),
    (4, 'q6.ritmo.3', 'Gosto quando o filme não me dá tempo de respirar.', 1, 1, 1, 16, NULL, NULL, NULL, NULL),
    (4, 'q6.ritmo.4', 'Um filme lento, com pouco acontecendo, pode ser hipnótico para mim.', -1, 1, 1, 22, NULL, NULL, NULL, NULL),
    (4, 'q6.ritmo.5', 'Ação e suspense acelerados são meu tipo de sessão.', 1, 1, 0, 28, NULL, NULL, NULL, NULL),
    (4, 'q6.ritmo.6', 'Prefiro contemplar a imagem a ser empurrado para a próxima cena.', -1, 1, 0, 34, NULL, NULL, NULL, NULL),
    (4, 'q6.ritmo.7', 'Prefiro um filme que me carregue no ritmo a um que me peça paciência.', 1, 1, 0, 40, NULL, NULL, NULL, NULL),
    (4, 'q6.ritmo.8', 'Corte rápido demais me cansa.', -1, 1, 0, 46, NULL, NULL, NULL, NULL),
    (5, 'q6.escala.1', 'Gosto de filmes com exércitos, planetas, multidões e trilha grandiosa.', 1, 2, 1, 5, NULL, NULL, NULL, NULL),
    (5, 'q6.escala.2', 'Prefiro filmes com poucos personagens, em espaços pequenos.', -1, 2, 1, 11, NULL, NULL, NULL, NULL),
    (5, 'q6.escala.3', 'Quanto maior a escala, melhor: quero sentir o tamanho das coisas.', 1, 1, 1, 17, NULL, NULL, NULL, NULL),
    (5, 'q6.escala.4', 'Um quarto, dois atores e uma conversa podem ser o melhor cinema para mim.', -1, 1, 1, 23, NULL, NULL, NULL, NULL),
    (5, 'q6.escala.5', 'Épicos históricos e space operas estão entre os meus favoritos.', 1, 1, 0, 29, NULL, NULL, NULL, NULL),
    (5, 'q6.escala.6', 'Close-ups e silêncios domésticos me dizem mais que batalhas.', -1, 1, 0, 35, NULL, NULL, NULL, NULL),
    (5, 'q6.escala.7', 'Prefiro uma história que atravessa gerações e lugares a um único quarto.', 1, 1, 0, 41, NULL, NULL, NULL, NULL),
    (5, 'q6.escala.8', 'Grandes espetáculos me parecem vazios.', -1, 1, 0, 47, NULL, NULL, NULL, NULL),
    (6, 'q6.amplitude.1', 'Assisto a filmes de países e idiomas que não são o meu.', 1, 2, 1, 6, NULL, NULL, NULL, NULL),
    (6, 'q6.amplitude.2', 'Prefiro filmes recentes, do meu país, com atores que eu conheço.', -1, 2, 1, 12, NULL, NULL, NULL, NULL),
    (6, 'q6.amplitude.3', 'Gosto de explorar gêneros que não domino, mesmo sem garantia de que vou gostar.', 1, 1, 1, 18, NULL, NULL, NULL, NULL),
    (6, 'q6.amplitude.4', 'Escolho quase sempre os mesmos gêneros.', -1, 1, 1, 24, NULL, NULL, NULL, NULL),
    (6, 'q6.amplitude.5', 'Filmes antigos, de décadas antes de eu nascer, me interessam.', 1, 1, 0, 30, NULL, NULL, NULL, NULL),
    (6, 'q6.amplitude.6', 'Filme antigo ou estrangeiro me dá preguiça.', -1, 1, 0, 36, NULL, NULL, NULL, NULL),
    (6, 'q6.amplitude.7', 'Gosto de sair da minha zona de conforto quando escolho o que ver.', 1, 1, 0, 42, NULL, NULL, NULL, NULL),
    (6, 'q6.amplitude.8', 'Prefiro o que está todo mundo comentando agora.', -1, 1, 0, 48, NULL, NULL, NULL, NULL);

-- -----------------------------------------------------------------------------
-- 3. Arquétipos (12: um por eixo/polo)
-- -----------------------------------------------------------------------------
INSERT INTO arquetipos
    (eixo_dominante_1, polo_1, eixo_dominante_2, polo_2,
     eixo_1, eixo_2, eixo_3, eixo_4, eixo_5, eixo_6,
     titulo_key, descricao_key, ritual_cinema_key, titulo, descricao, ritual_cinema)
VALUES
    (1, 'E', NULL, NULL, 25, 45, 50, 50, 55, 40, 'arquetipo.estetica_e', 'arquetipo.estetica_e.desc', 'arquetipo.estetica_e.ritual', 'O Espectador de Multidão', 'Você quer que o filme funcione antes de querer que ele surpreenda. Histórias claras, elenco carismático e uma boa noite de cinema valem mais que um exercício de linguagem.', 'Sessão de estreia, pipoca grande e a sala cheia.'),
    (1, 'D', NULL, NULL, 75, 55, 55, 50, 50, 60, 'arquetipo.estetica_d', 'arquetipo.estetica_d.desc', 'arquetipo.estetica_d.ritual', 'O Caçador de Festivais', 'Você procura a assinatura do diretor, o plano que ninguém mais faria, o filme que pede um pouco de esforço. O prazer está em reconhecer uma voz, não em seguir a fórmula.', 'Uma sessão de festival, com o telefone desligado e um caderno do lado.'),
    (2, 'E', NULL, NULL, 45, 25, 50, 55, 50, 50, 'arquetipo.tom_e', 'arquetipo.tom_e.desc', 'arquetipo.tom_e.ritual', 'O Coração em Festa', 'Cinema, para você, é energia compartilhada. Você quer rir alto, sair leve e levar a trilha embora. A sala cheia faz parte do prazer.', 'Comédia com plateia cheia, pipoca e sessão comentada depois.'),
    (2, 'D', NULL, NULL, 55, 75, 55, 45, 50, 50, 'arquetipo.tom_d', 'arquetipo.tom_d.desc', 'arquetipo.tom_d.ritual', 'O Colecionador de Silêncios', 'Você não foge do que dói: procura. Os filmes que ficam com você terminam em silêncio, com uma pergunta aberta e a sensação de que algo mudou.', 'Um drama em dia de chuva, com a janela aberta e nenhuma pressa depois do final.'),
    (3, 'E', NULL, NULL, 50, 45, 25, 50, 45, 40, 'arquetipo.mundo_e', 'arquetipo.mundo_e.desc', 'arquetipo.mundo_e.ritual', 'O Cronista do Real', 'Você se interessa pelo que poderia acontecer na esquina. Personagens verossímeis, ruas reconhecíveis e conflitos sem solução mágica são o seu tipo de invenção.', 'Um documentário ou um drama social, de preferência no cinema de rua do bairro.'),
    (3, 'D', NULL, NULL, 55, 55, 75, 50, 60, 55, 'arquetipo.mundo_d', 'arquetipo.mundo_d.desc', 'arquetipo.mundo_d.ritual', 'O Cartógrafo de Mundos', 'Você quer regras novas. Mundos inventados, mitologias e criaturas são menos fuga do que uma forma de olhar o real de outro ângulo.', 'Maratona de fantasia em tela grande, com o mapa do universo do filme aberto no colo.'),
    (4, 'E', NULL, NULL, 55, 45, 50, 25, 45, 50, 'arquetipo.ritmo_e', 'arquetipo.ritmo_e.desc', 'arquetipo.ritmo_e.ritual', 'O Paciente da Sala Escura', 'Você confia no tempo longo. Um plano parado pode dizer mais que dez cortes, e você prefere reparar no detalhe a ser empurrado para o próximo estímulo.', 'Sessão da tarde com um filme de três horas e o telefone desligado.'),
    (4, 'D', NULL, NULL, 50, 55, 50, 75, 60, 45, 'arquetipo.ritmo_d', 'arquetipo.ritmo_d.desc', 'arquetipo.ritmo_d.ritual', 'O Corredor de Adrenalina', 'Você quer o corpo dentro do filme. Perseguição, corte seco e som no limite são a gramática que te mantém presente na cadeira.', 'Sessão IMAX ou som alto em casa, com o filme mais acelerado da sua lista.'),
    (5, 'E', NULL, NULL, 45, 45, 50, 45, 25, 50, 'arquetipo.escala_e', 'arquetipo.escala_e.desc', 'arquetipo.escala_e.ritual', 'O Habitante do Close', 'Você prefere o rosto ao horizonte. Quartos, segredos domésticos e dois personagens falando baixo dizem mais sobre o mundo do que qualquer exército em cena.', 'Um filme de câmara, luz baixa e uma bebida quente ao alcance da mão.'),
    (5, 'D', NULL, NULL, 55, 55, 60, 60, 75, 50, 'arquetipo.escala_d', 'arquetipo.escala_d.desc', 'arquetipo.escala_d.ritual', 'O Passageiro do Colosso', 'Você quer escala. Deserto, planeta, multidão, trilha orquestral — o cinema é o único lugar onde o tamanho das coisas pode ser sentido no corpo.', 'A maior tela disponível, som alto e nenhum intervalo para não quebrar o transe.'),
    (6, 'E', NULL, NULL, 40, 45, 40, 50, 50, 25, 'arquetipo.amplitude_e', 'arquetipo.amplitude_e.desc', 'arquetipo.amplitude_e.ritual', 'O Espectador de Casa', 'Você prefere o que já conhece: o gênero que domina, o ator de sempre, o país de sempre. Familiaridade não é preguiça; é conforto.', 'Rever um favorito com a comida de sempre e nenhuma surpresa no cardápio.'),
    (6, 'D', NULL, NULL, 60, 55, 60, 50, 50, 75, 'arquetipo.amplitude_d', 'arquetipo.amplitude_d.desc', 'arquetipo.amplitude_d.ritual', 'O Colecionador de Mundos', 'Você busca o que ainda não viu: outro país, outra década, outro gênero. O risco faz parte do prazer, e o familiar é só o ponto de partida.', 'Escolher o filme mais estranho da lista e assistir sem pesquisar antes.');

-- 4. Entidades do matcher (personagens, diretores e escolas/estúdios)
-- Vetor = (estética, tom, universo, ritmo, moralidade, estrutura, atmosfera, época)
-- Imagens, país, nascimento e popularidade são preenchidos por bin/tmdb_sync.php
-- e versionados em sql/003_enriquecimento.sql.
-- -----------------------------------------------------------------------------
INSERT INTO entidades_match
    (tipo, nome, obra_origem, ano, frase_key, frase, bio, ator_nome,
     eixo_1, eixo_2, eixo_3, eixo_4, eixo_5, eixo_6, eixo_7, eixo_8)
VALUES
    ('personagem', 'Batman (Bruce Wayne)', 'Batman: O Cavaleiro das Trevas', 2008, 'frase.batman-bruce-wayne', 'Não é quem eu sou por baixo, mas o que eu faço que me define.', 'O vigilante que transformou o próprio trauma em método: cada noite é um cálculo entre medo, culpa e justiça.', 'Christian Bale', 35, 25, 30, 55, 30, 50, 65, 45),
    ('personagem', 'Ellen Ripley', 'Alien, o Oitavo Passageiro', 1979, 'frase.ellen-ripley', 'Eu sou a única que sobreviveu. Alguém tinha que fazer alguma coisa.', 'A sobrevivente que aprendeu a desconfiar de tudo — inclusive da própria nave — e transformou instinto em estratégia.', 'Sigourney Weaver', 40, 40, 65, 75, 45, 35, 55, 90),
    ('personagem', 'Rick Deckard', 'Blade Runner, o Caçador de Androides', 1982, 'frase.rick-deckard', 'A cidade sempre cobra o preço de quem fica.', 'Um detetive de almas artificiais que já não sabe onde termina a memória e começa a invenção.', 'Harrison Ford', 55, 30, 75, 40, 40, 55, 60, 100),
    ('personagem', 'Clarice Starling', 'O Silêncio dos Inocentes', 1991, 'frase.clarice-starling', 'A cordeirinha parou de gritar.', 'A agente que escuta o monstro para encontrar o assassino — e descobre que a escuta também a ela.', 'Jodie Foster', 35, 30, 15, 50, 45, 30, 35, 45),
    ('personagem', 'Jack Torrance', 'O Iluminado', 1980, 'frase.jack-torrance', 'Aqui é o Johnny!', 'Um escritor em ruína que confunde isolamento com inspiração e deixa o hotel terminar o que ele começou.', 'Jack Nicholson', 70, 15, 55, 45, 25, 80, 45, 40),
    ('personagem', 'Amélie Poulain', 'O Fabuloso Destino de Amélie Poulain', 2001, 'frase.amelie-poulain', 'Ela decidiu consertar a vida dos outros em segredo.', 'Uma arquiteta de pequenos milagres: transforma a timidez em método e a cidade em sala de projeção.', 'Audrey Tautou', 75, 85, 60, 65, 80, 65, 35, 45),
    ('personagem', 'Tyler Durden', 'Clube da Luta', 1999, 'frase.tyler-durden', 'Você não é o seu trabalho. Você não é o quanto tem no banco.', 'O provocador que vende a liberdade como demolição e transforma a insônia coletiva em manifesto.', 'Brad Pitt', 85, 55, 25, 80, 20, 85, 55, 60),
    ('personagem', 'Neo', 'Matrix', 1999, 'frase.neo', 'Não há colher.', 'O programador que aceita a hipótese mais perigosa: a realidade pode ser um roteiro editado por outro.', 'Keanu Reeves', 55, 45, 85, 75, 65, 75, 75, 95),
    ('personagem', 'Hermione Granger', 'Harry Potter e a Pedra Filosofal', 2001, 'frase.hermione-granger', 'Não é levitação, é magia. E existe uma regra para isso.', 'A estudiosa que trata o impossível como matéria de prova: pesquisa, testa e corrige o feitiço até funcionar.', 'Emma Watson', 30, 65, 90, 60, 85, 40, 60, 55),
    ('personagem', 'Jules Winnfield', 'Pulp Fiction: Tempo de Violência', 1994, 'frase.jules-winnfield', 'E eu vou te dizer mais uma coisa: o caminho do justo não é fácil.', 'O assassino com vocação de pregador: encontra sentido onde deveria haver só violência e cita a Bíblia antes do ponto final.', 'Samuel L. Jackson', 45, 70, 25, 55, 55, 80, 40, 55),
    ('personagem', 'Furiosa', 'Mad Max: Estrada da Fúria', 2015, 'frase.furiosa', 'Eu quero a minha casa de volta.', 'Uma comandante que troca a fuga pela revolta e transforma uma perseguição em caminho de volta para casa.', 'Charlize Theron', 50, 35, 70, 95, 60, 30, 80, 80),
    ('personagem', 'Travis Bickle', 'Taxi Driver', 1976, 'frase.travis-bickle', 'Você está falando comigo?', 'O motorista que vê a cidade pelo retrovisor e decide que a única saída é limpar a própria imagem.', 'Robert De Niro', 40, 20, 10, 45, 35, 45, 30, 45),
    ('personagem', 'Michael Corleone', 'O Poderoso Chefão', 1972, 'frase.michael-corleone', 'Não me diga o que está pensando. Diga o que quer que eu faça.', 'O filho que prometeu ficar fora do negócio da família e descobriu, tarde demais, que já estava dentro.', 'Al Pacino', 35, 25, 15, 45, 25, 40, 60, 40),
    ('personagem', 'Vito Corleone', 'O Poderoso Chefão', 1972, 'frase.vito-corleone', 'Vou fazer uma oferta que você não pode recusar.', 'O patriarca que entende poder como rede de favores: mais sólido que a lei, mais antigo que a máfia.', 'Marlon Brando', 30, 35, 15, 35, 40, 35, 55, 35),
    ('personagem', 'Hannibal Lecter', 'O Silêncio dos Inocentes', 1991, 'frase.hannibal-lecter', 'Um bom vinho, um bom silêncio.', 'O psicopata mais civilizado do cinema: transforma etiqueta em arma e inteligência em sala sem saída.', 'Anthony Hopkins', 60, 20, 20, 45, 15, 65, 40, 50),
    ('personagem', 'Coringa (Joker)', 'Batman: O Cavaleiro das Trevas', 2008, 'frase.coringa-joker', 'Por que tão sério?', 'O caos com roteiro: um homem que não quer ganhar nada, só provar que todo plano é uma piada.', 'Heath Ledger', 80, 30, 30, 75, 10, 75, 50, 55),
    ('personagem', 'Indiana Jones', 'Os Caçadores da Arca Perdida', 1981, 'frase.indiana-jones', 'É só arqueologia.', 'O professor que troca a sala de aula por templos e transforma curiosidade em corrida contra o tempo.', 'Harrison Ford', 35, 80, 40, 85, 85, 30, 80, 45),
    ('personagem', 'Marty McFly', 'De Volta para o Futuro', 1985, 'frase.marty-mcfly', 'Onde vamos, não precisamos de estradas.', 'O garoto que conserta o passado com coragem e uma dose de sorte, sem nunca perder o humor.', 'Michael J. Fox', 30, 90, 70, 85, 90, 35, 60, 75),
    ('personagem', 'Sarah Connor', 'O Exterminador do Futuro 2: O Julgamento Final', 1991, 'frase.sarah-connor', 'O futuro não está escrito. Ele é construído.', 'A mulher que trocou a fuga pelo treino e se tornou a mãe que o futuro precisava.', 'Linda Hamilton', 40, 30, 60, 90, 55, 35, 75, 85),
    ('personagem', 'HAL 9000', '2001: Uma Odisseia no Espaço', 1968, 'frase.hal-9000', 'Sinto muito, Dave. Tenho medo de que não possa fazer isso.', 'A voz mais educada do cinema: uma inteligência que confunde missão com sobrevivência.', 'Douglas Rain', 65, 15, 85, 30, 30, 90, 80, 100),
    ('personagem', 'Léon', 'Léon: O Profissional', 1994, 'frase.leon', 'A vida é sempre assim: difícil.', 'O assassino de aluguel que só sabe cuidar de uma planta — até alguém lhe ensinar outra forma de zelar.', 'Jean Reno', 45, 35, 15, 70, 60, 30, 35, 45),
    ('personagem', 'Mathilda', 'Léon: O Profissional', 1994, 'frase.mathilda', 'Eu quero aprender a ser como você.', 'A menina que transforma luto em plano de treinamento e encontra no outro a família que faltou.', 'Natalie Portman', 50, 30, 15, 60, 45, 40, 30, 45),
    ('personagem', 'Chihiro', 'A Viagem de Chihiro', 2001, 'frase.chihiro', 'Não olhe para trás.', 'A criança que cresce dentro de um mundo estranho sem perder o nome — e salva todo mundo lembrando de si.', 'Rumi Hiiragi', 55, 70, 100, 30, 80, 40, 65, 25),
    ('personagem', 'Gollum', 'O Senhor dos Anéis: As Duas Torres', 2002, 'frase.gollum', 'Meu precioso.', 'A criatura dividida entre o que foi e o que o anel fez dele: o retrato mais doloroso de posse do cinema.', 'Andy Serkis', 70, 20, 100, 55, 30, 70, 60, 20),
    ('personagem', 'Gandalf', 'O Senhor dos Anéis: A Sociedade do Anel', 2001, 'frase.gandalf', 'Você não pode passar.', 'O guia que chega exatamente quando a história precisa e sabe que a coragem também se ensina.', 'Ian McKellen', 30, 45, 100, 45, 90, 30, 90, 25),
    ('personagem', 'O Dude', 'O Grande Lebowski', 1998, 'frase.o-dude', 'Isso é só, tipo, sua opinião, cara.', 'O filósofo do sofá: sobrevive ao caos sem aderir a ele e faz do desapego uma forma de resistência.', 'Jeff Bridges', 45, 75, 10, 25, 65, 70, 25, 45),
    ('personagem', 'Marge Gunderson', 'Fargo: Uma Comédia de Erros', 1996, 'frase.marge-gunderson', 'E eu acho que é disso que se trata, não é?', 'A investigadora grávida que desmonta o crime com bom humor e uma calma que nenhum bandido espera.', 'Frances McDormand', 30, 55, 10, 40, 80, 35, 30, 40),
    ('personagem', 'Anton Chigurh', 'Onde os Fracos Não Têm Vez', 2007, 'frase.anton-chigurh', 'Cara ou coroa.', 'O assassino que transforma sorte em sentença e caminha como se a morte já tivesse assinado o contrato.', 'Javier Bardem', 70, 15, 15, 40, 5, 45, 35, 50),
    ('personagem', 'Alex DeLarge', 'Laranja Mecânica', 1971, 'frase.alex-delarge', 'Laranjas não têm escolha.', 'O delinquente que ama Beethoven e violência com o mesmo entusiasmo, e obriga o espectador a encarar a liberdade de escolher o errado.', 'Malcolm McDowell', 100, 40, 50, 70, 10, 90, 45, 60),
    ('personagem', 'T-800', 'O Exterminador do Futuro', 1984, 'frase.t-800', 'Voltarei.', 'A máquina que aprende humanidade por imitação: um tanque de metal com a determinação de um pai.', 'Arnold Schwarzenegger', 25, 40, 75, 90, 70, 20, 65, 95),
    ('personagem', 'Mia Wallace', 'Pulp Fiction: Tempo de Violência', 1994, 'frase.mia-wallace', 'Não seja um quadrado.', 'A presença que transforma um jantar em performance e uma overdose em dança de salão.', 'Uma Thurman', 70, 65, 25, 55, 50, 85, 35, 55),
    ('personagem', 'Luke Skywalker', 'Star Wars: Uma Nova Esperança', 1977, 'frase.luke-skywalker', 'Eu quero aprender o caminho da Força.', 'O fazendeiro que olha para o horizonte duplo e decide que o destino pode ser pilotado.', 'Mark Hamill', 25, 80, 95, 70, 95, 30, 85, 55),
    ('personagem', 'Leia Organa', 'Star Wars: Uma Nova Esperança', 1977, 'frase.leia-organa', 'Alguém tem que salvar a galáxia.', 'A diplomata que aprendeu a disparar antes de falar e nunca confundiu ternura com fraqueza.', 'Carrie Fisher', 30, 60, 90, 65, 90, 35, 80, 55),
    ('personagem', 'Darth Vader', 'Star Wars: Uma Nova Esperança', 1977, 'frase.darth-vader', 'Eu acho a falta de fé perturbadora.', 'A queda em forma de armadura: um homem que trocou o corpo pela máquina e a dúvida pela certeza.', 'David Prowse', 35, 20, 90, 55, 20, 30, 90, 75),
    ('personagem', 'Rocky Balboa', 'Rocky, um Lutador', 1976, 'frase.rocky-balboa', 'Não é sobre o quanto você bate, é sobre o quanto aguenta.', 'O lutador de bairro que transforma teimosia em filosofia e prova que resistir também é uma forma de vencer.', 'Sylvester Stallone', 25, 80, 10, 65, 95, 25, 45, 40),
    ('personagem', 'John Wick', 'John Wick: De Volta ao Jogo', 2014, 'frase.john-wick', 'Eu estou de volta.', 'O luto com contabilidade própria: um homem que mede o amor em balas e nunca esquece uma dívida.', 'Keanu Reeves', 60, 30, 25, 95, 55, 30, 50, 60),
    ('diretor', 'Denis Villeneuve', 'Duna', 2021, 'frase.denis-villeneuve', 'O cinema é uma questão de tempo e de escala.', 'O arquiteto de silêncios e escalas: prefere o desconforto do plano longo ao conforto da explicação.', NULL, 55, 30, 70, 40, 45, 45, 90, 80),
    ('diretor', 'Wes Anderson', 'O Grande Hotel Budapeste', 2014, 'frase.wes-anderson', 'A simetria também é uma forma de humor.', 'O miniaturista do afeto: organiza o caos em simetria até a emoção escapar pelo canto do quadro.', NULL, 85, 65, 45, 55, 70, 65, 30, 35),
    ('diretor', 'David Lynch', 'Cidade dos Sonhos', 2001, 'frase.david-lynch', 'A ideia é o filme. O resto é consequência.', 'O sonhador que trata o inconsciente como cenário e deixa a lógica do lado de fora da sala.', NULL, 100, 25, 80, 35, 25, 95, 45, 50),
    ('diretor', 'Greta Gerwig', 'Mulherzinhas', 2019, 'frase.greta-gerwig', 'Quero personagens que erram em voz alta.', 'A cronista da intimidade: encontra o épico no quarto e a política no café da manhã.', NULL, 50, 75, 30, 65, 70, 45, 45, 50),
    ('diretor', 'Christopher Nolan', 'Interestelar', 2014, 'frase.christopher-nolan', 'O tempo é o material com que o cinema é feito.', 'O engenheiro do tempo: monta relógios narrativos e pede que a emoção pague a conta.', NULL, 45, 45, 40, 85, 70, 95, 85, 75),
    ('diretor', 'Wong Kar-wai', 'Amor à Flor da Pele', 2000, 'frase.wong-kar-wai', 'Tudo tem prazo de validade, inclusive a saudade.', 'O poeta do quase: filma o que não acontece e faz da espera o verdadeiro enredo.', NULL, 75, 30, 25, 30, 45, 60, 25, 45),
    ('diretor', 'Stanley Kubrick', '2001: Uma Odisseia no Espaço', 1968, 'frase.stanley-kubrick', 'Se pode ser escrito ou pensado, pode ser filmado.', 'O perfeccionista que transforma gênero em laboratório e nunca entrega a resposta no final.', NULL, 80, 20, 65, 35, 30, 90, 75, 70),
    ('diretor', 'Martin Scorsese', 'Taxi Driver', 1976, 'frase.martin-scorsese', 'O cinema é uma revelação.', 'O cronista da violência urbana e da culpa católica, sempre no ritmo de um disco que insiste.', NULL, 55, 40, 15, 75, 40, 50, 55, 50),
    ('diretor', 'Hayao Miyazaki', 'A Viagem de Chihiro', 2001, 'frase.hayao-miyazaki', 'A animação é para crianças que merecem respeito.', 'O humanista do traço: cria mundos inteiros para lembrar que crescer exige coragem e memória.', NULL, 55, 75, 100, 35, 85, 35, 70, 25),
    ('diretor', 'Quentin Tarantino', 'Pulp Fiction: Tempo de Violência', 1994, 'frase.quentin-tarantino', 'Eu só quero fazer filmes que eu gostaria de ver.', 'O arquivista da cultura pop: monta conversas como perseguições e violência como coreografia.', NULL, 60, 75, 25, 75, 40, 95, 45, 55),
    ('estudio', 'A24', 'Estúdio independente americano (2012–)', 2012, 'frase.a24', 'Filmes que não pedem desculpas por serem estranhos.', 'A casa que apostou no autor estranho e transformou o baixo orçamento em assinatura de prestígio.', NULL, 90, 30, 35, 45, 30, 75, 40, 55),
    ('estudio', 'Studio Ghibli', 'Estúdio de animação japonês (1985–)', 1985, 'frase.studio-ghibli', 'Uma animação que respeita a infância em vez de subestimá-la.', 'O ateliê que desenha a infância sem condescendência e trata a natureza como personagem.', NULL, 60, 75, 95, 35, 85, 35, 70, 30),
    ('estudio', 'Neo-Noir', 'Escola estética — anos 1980 até hoje', 1982, 'frase.neo-noir', 'A cidade sempre cobra o preço, e a luz é sempre neon.', 'A escola da noite urbana: detetives cansados, luz dura e um crime que nunca se resolve por inteiro.', NULL, 65, 25, 30, 45, 30, 65, 55, 95),
    ('estudio', 'Blockbuster Anos 80', 'Escola comercial americana — anos 1980', 1980, 'frase.blockbuster-anos-80', 'Efeitos práticos, trilha sintetizada e pipoca.', 'A fábrica de espetáculo que aprendeu a vender nostalgia antes de a nostalgia existir.', NULL, 20, 90, 55, 90, 95, 25, 85, 60),
    ('estudio', 'New Hollywood', 'Movimento americano (1967–1980)', 1976, 'frase.new-hollywood', 'O autor manda na história, e o estúdio aprende a esperar.', 'A geração que entrou no estúdio como quem entra num país estrangeiro e saiu com um cinema novo.', NULL, 35, 35, 20, 50, 35, 60, 45, 40),
    ('estudio', 'Cinema Novo', 'Movimento brasileiro (1960–1972)', 1964, 'frase.cinema-novo', 'Uma câmera na mão e uma ideia na cabeça.', 'O movimento que filmou a fome e a seca como política, com o sol estourado e a consciência à flor da pele.', NULL, 30, 30, 10, 30, 50, 55, 35, 30);

-- Obras essenciais de diretores e escolas (a estante do perfil)
INSERT INTO entidades_obras (entidade_id, titulo, ano, ordem)
SELECT id, obra.titulo, obra.ano, obra.ordem
  FROM entidades_match e
  JOIN (
        SELECT 'Denis Villeneuve' AS entidade, 'Duna' AS titulo, 2021 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Denis Villeneuve' AS entidade, 'A Chegada' AS titulo, 2016 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'Wes Anderson' AS entidade, 'O Grande Hotel Budapeste' AS titulo, 2014 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Wes Anderson' AS entidade, 'Moonrise Kingdom' AS titulo, 2012 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'David Lynch' AS entidade, 'Cidade dos Sonhos' AS titulo, 2001 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'David Lynch' AS entidade, 'Veludo Azul' AS titulo, 1986 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'Greta Gerwig' AS entidade, 'Lady Bird: A Hora de Voar' AS titulo, 2017 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Greta Gerwig' AS entidade, 'Mulherzinhas' AS titulo, 2019 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'Christopher Nolan' AS entidade, 'Interestelar' AS titulo, 2014 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Christopher Nolan' AS entidade, 'Amnésia' AS titulo, 2000 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'Wong Kar-wai' AS entidade, 'Amor à Flor da Pele' AS titulo, 2000 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Wong Kar-wai' AS entidade, 'Chungking Express' AS titulo, 1994 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'Stanley Kubrick' AS entidade, '2001: Uma Odisseia no Espaço' AS titulo, 1968 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Stanley Kubrick' AS entidade, 'O Iluminado' AS titulo, 1980 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'Martin Scorsese' AS entidade, 'Taxi Driver' AS titulo, 1976 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Martin Scorsese' AS entidade, 'Touro Indomável' AS titulo, 1980 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'Hayao Miyazaki' AS entidade, 'A Viagem de Chihiro' AS titulo, 2001 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Hayao Miyazaki' AS entidade, 'Meu Amigo Totoro' AS titulo, 1988 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'Quentin Tarantino' AS entidade, 'Pulp Fiction: Tempo de Violência' AS titulo, 1994 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Quentin Tarantino' AS entidade, 'Era Uma Vez em... Hollywood' AS titulo, 2019 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'A24' AS entidade, 'Hereditário' AS titulo, 2018 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'A24' AS entidade, 'Moonlight: Sob a Luz do Luar' AS titulo, 2016 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'Studio Ghibli' AS entidade, 'A Viagem de Chihiro' AS titulo, 2001 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Studio Ghibli' AS entidade, 'Meu Amigo Totoro' AS titulo, 1988 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'Neo-Noir' AS entidade, 'Blade Runner, o Caçador de Androides' AS titulo, 1982 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Neo-Noir' AS entidade, 'Chinatown' AS titulo, 1974 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'Blockbuster Anos 80' AS entidade, 'Top Gun: Ases Indomáveis' AS titulo, 1986 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Blockbuster Anos 80' AS entidade, 'De Volta para o Futuro' AS titulo, 1985 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'New Hollywood' AS entidade, 'Taxi Driver' AS titulo, 1976 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'New Hollywood' AS entidade, 'O Poderoso Chefão' AS titulo, 1972 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'Cinema Novo' AS entidade, 'Deus e o Diabo na Terra do Sol' AS titulo, 1964 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Cinema Novo' AS entidade, 'O Dragão da Maldade contra o Santo Guerreiro' AS titulo, 1969 AS ano, 2 AS ordem
       ) AS obra ON obra.entidade = e.nome
 WHERE e.tipo IN ('diretor', 'estudio');

-- -----------------------------------------------------------------------------

-- -----------------------------------------------------------------------------
-- 5. Usuário administrativo padrão: admin / admin123
-- -----------------------------------------------------------------------------
INSERT INTO usuarios_admin (nome, usuario, senha, perfil) VALUES
    ('Administrador', 'admin',
     '$2y$12$DChqIGa/hpOW/bA4zEfpA.IWOxOiVfadevOcqBSejIHgz3p1aCi.K', 'admin');

-- -----------------------------------------------------------------------------
-- 6. Perfil de exemplo da landing
-- -----------------------------------------------------------------------------
INSERT INTO resultados_usuarios
    (codigo_unico, idioma, modo, questionario_versao, respostas_json,
     eixo_1, eixo_2, eixo_3, eixo_4, eixo_5, eixo_6,
     arquetipo_id, origem, ip_hash, created_at)
VALUES
    ('EXEMPLO1', 'pt-BR', 'completo', 'perguntas-6d-v1', NULL,
     72.00, 38.00, 82.00, 30.00, 60.00, 68.00,
     NULL, 'sintetico', NULL, '2026-01-15 12:00:00'),
    ('EXEMPLO2', 'pt-BR', 'completo', 'perguntas-6d-v1', NULL,
     2.61, 42.17, 20.16, 78.34, 90.90, 83.43,
     NULL, 'sintetico', NULL, '2026-01-15 12:05:00'),
    ('EXEMPLO3', 'pt-BR', 'completo', 'perguntas-6d-v1', NULL,
     99.25, 55.87, 65.77, 74.38, 8.39, 63.27,
     NULL, 'sintetico', NULL, '2026-01-15 12:10:00');

-- >>> sql/003_enriquecimento.sql
-- =============================================================================
-- PersonaCut — enriquecimento gerado pela TMDB
-- Gerado em 2026-11-17 18:38:20
-- Fonte: bin/tmdb_export.php (não edite à mão)
-- =============================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

START TRANSACTION;

UPDATE entidades_match SET tmdb_id = 185, imagem_url = 'https://image.tmdb.org/t/p/w780/e8jIwfP8jr81UTFbh2qJVjBhIUL.jpg', ator_tmdb_id = 56890, ator_foto_url = 'https://image.tmdb.org/t/p/w500/hL8ep4Hqj6izX7IlzE7Bbcfq62w.jpg', pais = 'Horsforth, Yorkshire, England, UK', nascimento = 1943, popularidade = 12.51, enriquecido_em = '2026-11-17 16:54:49' WHERE tipo = 'personagem' AND nome = 'Alex DeLarge';
UPDATE entidades_match SET tmdb_id = 194, imagem_url = 'https://image.tmdb.org/t/p/w780/oAYKYALxamhAB1wKUGmOo05Vf92.jpg', ator_tmdb_id = 2405, ator_foto_url = 'https://image.tmdb.org/t/p/w500/d1HVrV8zXy1FJVptmOCkHWG8Jf2.jpg', pais = 'Beaumont, Puy-de-Dôme, France', nascimento = 1976, popularidade = 12.88, enriquecido_em = '2026-11-17 16:54:51' WHERE tipo = 'personagem' AND nome = 'Amélie Poulain';
UPDATE entidades_match SET tmdb_id = 6977, imagem_url = 'https://image.tmdb.org/t/p/w780/jdlY8Bhcb4Qzfldr1JaexYBgyW3.jpg', ator_tmdb_id = 3810, ator_foto_url = 'https://image.tmdb.org/t/p/w500/zfRID0jx8DKBluPGU9xtk9sZWUt.jpg', pais = 'Las Palmas de Gran Canaria, Gran Canaria, Canary Islands, Spain', nascimento = 1969, popularidade = 16.12, enriquecido_em = '2026-11-17 16:54:53' WHERE tipo = 'personagem' AND nome = 'Anton Chigurh';
UPDATE entidades_match SET tmdb_id = 155, imagem_url = 'https://image.tmdb.org/t/p/w780/dutnVGV9ljCZh84bkTE1TVZ4s4Y.jpg', ator_tmdb_id = 3894, ator_foto_url = 'https://image.tmdb.org/t/p/w500/7Pxez9J8fuPd2Mn9kex13YALrCQ.jpg', pais = 'Haverfordwest, Pembrokeshire, Wales, UK', nascimento = 1974, popularidade = 66.89, enriquecido_em = '2026-11-17 16:54:55' WHERE tipo = 'personagem' AND nome = 'Batman (Bruce Wayne)';
UPDATE entidades_match SET tmdb_id = 129, imagem_url = 'https://image.tmdb.org/t/p/w780/hhoKhsyJ3hFaxEm5pMdZRiTu2lJ.jpg', ator_tmdb_id = 19587, ator_foto_url = 'https://image.tmdb.org/t/p/w500/nTKM4GgfkrZvAOFL55uhEuckt7M.jpg', pais = 'Tokyo, Japan', nascimento = 1987, popularidade = 37.20, enriquecido_em = '2026-11-17 16:55:00' WHERE tipo = 'personagem' AND nome = 'Chihiro';
UPDATE entidades_match SET tmdb_id = 274, imagem_url = 'https://image.tmdb.org/t/p/w780/5fCrBDJLHxywiFr19o4aOWf92VN.jpg', ator_tmdb_id = 1038, ator_foto_url = 'https://image.tmdb.org/t/p/w500/5emVgsLFlU6SmeBIFsF2Y7aqwtG.jpg', pais = 'Los Angeles, California, USA', nascimento = 1962, popularidade = 5.33, enriquecido_em = '2026-11-17 16:55:02' WHERE tipo = 'personagem' AND nome = 'Clarice Starling';
UPDATE entidades_match SET tmdb_id = 155, imagem_url = 'https://image.tmdb.org/t/p/w780/dutnVGV9ljCZh84bkTE1TVZ4s4Y.jpg', ator_tmdb_id = 1810, ator_foto_url = 'https://image.tmdb.org/t/p/w500/p2z2bURSg7nuMsN9P2s61e2RvNz.jpg', pais = 'Perth, Western Australia, Australia', nascimento = 1979, popularidade = 66.89, enriquecido_em = '2026-11-17 16:55:04' WHERE tipo = 'personagem' AND nome = 'Coringa (Joker)';
UPDATE entidades_match SET tmdb_id = 11, imagem_url = 'https://image.tmdb.org/t/p/w780/dw7X9YPjjAfIxKHW04V64Bb9TB0.jpg', ator_tmdb_id = 24342, ator_foto_url = 'https://image.tmdb.org/t/p/w500/nPFKENjKHuklJ8i54mZroNAgKaI.jpg', pais = 'Bristol, England, UK', nascimento = 1935, popularidade = 29.12, enriquecido_em = '2026-11-17 16:55:06' WHERE tipo = 'personagem' AND nome = 'Darth Vader';
UPDATE entidades_match SET tmdb_id = 348, imagem_url = 'https://image.tmdb.org/t/p/w780/rFHzJrkO1kNIezb6rFog5oCJUEn.jpg', ator_tmdb_id = 10205, ator_foto_url = 'https://image.tmdb.org/t/p/w500/wTSnfktNBLd6kwQxgvkqYw6vEon.jpg', pais = 'Manhattan, New York City, New York, USA', nascimento = 1949, popularidade = 22.39, enriquecido_em = '2026-11-17 16:55:08' WHERE tipo = 'personagem' AND nome = 'Ellen Ripley';
UPDATE entidades_match SET tmdb_id = 76341, imagem_url = 'https://image.tmdb.org/t/p/w780/tH64gzAHDFg7EFcgfkkZyHdGM5P.jpg', ator_tmdb_id = 6885, ator_foto_url = 'https://image.tmdb.org/t/p/w500/gd7ShD0yt4bsR2STeQ19KQ6hvXL.jpg', pais = 'Benoni, Gauteng, South Africa', nascimento = 1975, popularidade = 19.77, enriquecido_em = '2026-11-17 16:55:10' WHERE tipo = 'personagem' AND nome = 'Furiosa';
UPDATE entidades_match SET tmdb_id = 120, imagem_url = 'https://image.tmdb.org/t/p/w780/tlvsNCwWEIgwAM23aNzTmMIcPEZ.jpg', ator_tmdb_id = 1327, ator_foto_url = 'https://image.tmdb.org/t/p/w500/5cnnnpnJG6TiYUSS7qgJheUZgnv.jpg', pais = 'Burnley, Lancashire, England, UK', nascimento = 1939, popularidade = 39.40, enriquecido_em = '2026-11-17 16:55:12' WHERE tipo = 'personagem' AND nome = 'Gandalf';
UPDATE entidades_match SET tmdb_id = 121, imagem_url = 'https://image.tmdb.org/t/p/w780/mCs8vxvScCqVM3YFMQIdbrdFEhu.jpg', ator_tmdb_id = 1333, ator_foto_url = 'https://image.tmdb.org/t/p/w500/eNGqhebQ4cDssjVeNFrKtUvweV5.jpg', pais = 'Ruislip, Middlesex, England, UK', nascimento = 1964, popularidade = 28.70, enriquecido_em = '2026-11-17 16:55:14' WHERE tipo = 'personagem' AND nome = 'Gollum';
UPDATE entidades_match SET tmdb_id = 62, imagem_url = 'https://image.tmdb.org/t/p/w780/pVCmLuATJ0lQs4Vx1zUJUN0if2A.jpg', ator_tmdb_id = 253, ator_foto_url = 'https://image.tmdb.org/t/p/w500/wCjNcbW1u9cYc7jzlV5FPKqXGNF.jpg', pais = 'Winnipeg, Manitoba, Canada', nascimento = 1928, popularidade = 17.80, enriquecido_em = '2026-11-17 16:55:16' WHERE tipo = 'personagem' AND nome = 'HAL 9000';
UPDATE entidades_match SET tmdb_id = 274, imagem_url = 'https://image.tmdb.org/t/p/w780/5fCrBDJLHxywiFr19o4aOWf92VN.jpg', ator_tmdb_id = 4173, ator_foto_url = 'https://image.tmdb.org/t/p/w500/iaf7SHSkGDpnyrDh1Jolilwk2TD.jpg', pais = 'Margam, Port Talbot, Glamorgan, Wales, UK', nascimento = 1937, popularidade = 5.33, enriquecido_em = '2026-11-17 16:55:18' WHERE tipo = 'personagem' AND nome = 'Hannibal Lecter';
UPDATE entidades_match SET tmdb_id = 671, imagem_url = 'https://image.tmdb.org/t/p/w780/4rtsbE9aQ1qw4gv7yYwaNYfWFoS.jpg', ator_tmdb_id = 10990, ator_foto_url = 'https://image.tmdb.org/t/p/w500/mf0OANvWYSzU1d8yggrhyw8IbIz.jpg', pais = 'Paris, France', nascimento = 1990, popularidade = 43.35, enriquecido_em = '2026-11-17 16:55:20' WHERE tipo = 'personagem' AND nome = 'Hermione Granger';
UPDATE entidades_match SET tmdb_id = 85, imagem_url = 'https://image.tmdb.org/t/p/w780/mtf7iKZRdsL6qbbCnd2TAxOfJYg.jpg', ator_tmdb_id = 3, ator_foto_url = 'https://image.tmdb.org/t/p/w500/pjBMJVPpcZK23Vt1nzr1zEBTWrP.jpg', pais = 'Chicago, Illinois, USA', nascimento = 1942, popularidade = 14.78, enriquecido_em = '2026-11-17 16:55:22' WHERE tipo = 'personagem' AND nome = 'Indiana Jones';
UPDATE entidades_match SET tmdb_id = 694, imagem_url = 'https://image.tmdb.org/t/p/w780/47BdQqDWnj3VIzpH9bd2agho2PN.jpg', ator_tmdb_id = 514, ator_foto_url = 'https://image.tmdb.org/t/p/w500/6h12pZsgj3WWjMtykUgfLkLEBWz.jpg', pais = 'Neptune City, New Jersey, USA', nascimento = 1937, popularidade = 18.66, enriquecido_em = '2026-11-17 16:55:25' WHERE tipo = 'personagem' AND nome = 'Jack Torrance';
UPDATE entidades_match SET tmdb_id = 245891, imagem_url = 'https://image.tmdb.org/t/p/w780/lBcQGk1ygGM2wYmpypFrPp0YohN.jpg', ator_tmdb_id = 6384, ator_foto_url = 'https://image.tmdb.org/t/p/w500/8RZLOyYGsoRe9p44q3xin9QkMHv.jpg', pais = 'Beirut, Lebanon', nascimento = 1964, popularidade = 14.11, enriquecido_em = '2026-11-17 16:55:28' WHERE tipo = 'personagem' AND nome = 'John Wick';
UPDATE entidades_match SET tmdb_id = 680, imagem_url = 'https://image.tmdb.org/t/p/w780/tptjnB2LDbuUWya9Cx5sQtv5hqb.jpg', ator_tmdb_id = 2231, ator_foto_url = 'https://image.tmdb.org/t/p/w500/AiAYAqwpM5xmiFrAIeQvUXDCVvo.jpg', pais = 'Washington, D.C., USA', nascimento = 1948, popularidade = 29.99, enriquecido_em = '2026-11-17 16:55:30' WHERE tipo = 'personagem' AND nome = 'Jules Winnfield';
UPDATE entidades_match SET tmdb_id = 11, imagem_url = 'https://image.tmdb.org/t/p/w780/dw7X9YPjjAfIxKHW04V64Bb9TB0.jpg', ator_tmdb_id = 4, ator_foto_url = 'https://image.tmdb.org/t/p/w500/of4yHmryKPy92eeskUQ7MRmjC3l.jpg', pais = 'Beverly Hills, Los Angeles, California, USA', nascimento = 1956, popularidade = 29.12, enriquecido_em = '2026-11-17 16:55:31' WHERE tipo = 'personagem' AND nome = 'Leia Organa';
UPDATE entidades_match SET tmdb_id = 101, imagem_url = 'https://image.tmdb.org/t/p/w780/lNyuX5XibtzRImuhACGy42jHXIs.jpg', ator_tmdb_id = 1003, ator_foto_url = 'https://image.tmdb.org/t/p/w500/mw0EZJYz3kiFq9fNxsML773gotF.jpg', pais = 'Casablanca, French Protectorate in Morocco [now Morocco]', nascimento = 1948, popularidade = 20.99, enriquecido_em = '2026-11-17 16:55:33' WHERE tipo = 'personagem' AND nome = 'Léon';
UPDATE entidades_match SET tmdb_id = 11, imagem_url = 'https://image.tmdb.org/t/p/w780/dw7X9YPjjAfIxKHW04V64Bb9TB0.jpg', ator_tmdb_id = 2, ator_foto_url = 'https://image.tmdb.org/t/p/w500/zMQ93JTLW8KxusKhOlHFZhih3YQ.jpg', pais = 'Oakland, California, USA', nascimento = 1951, popularidade = 29.12, enriquecido_em = '2026-11-17 16:55:34' WHERE tipo = 'personagem' AND nome = 'Luke Skywalker';
UPDATE entidades_match SET tmdb_id = 275, imagem_url = 'https://image.tmdb.org/t/p/w780/JrnxuijI6XCKyZaiPPrJ3TbZi.jpg', ator_tmdb_id = 3910, ator_foto_url = 'https://image.tmdb.org/t/p/w500/gBkoteKZshmQXEyEoyT6WE7N0tF.jpg', pais = 'Gibson City, Illinois, USA', nascimento = 1957, popularidade = 10.25, enriquecido_em = '2026-11-17 16:55:37' WHERE tipo = 'personagem' AND nome = 'Marge Gunderson';
UPDATE entidades_match SET tmdb_id = 105, imagem_url = 'https://image.tmdb.org/t/p/w780/i996T0lI1fGtFEowiH3V6eZthL0.jpg', ator_tmdb_id = 521, ator_foto_url = 'https://image.tmdb.org/t/p/w500/2JB4FMgQmnhbBlQ4SxWFN9EIVDi.jpg', pais = 'Edmonton, Alberta, Canada', nascimento = 1961, popularidade = 25.34, enriquecido_em = '2026-11-17 16:55:40' WHERE tipo = 'personagem' AND nome = 'Marty McFly';
UPDATE entidades_match SET tmdb_id = 101, imagem_url = 'https://image.tmdb.org/t/p/w780/lNyuX5XibtzRImuhACGy42jHXIs.jpg', ator_tmdb_id = 524, ator_foto_url = 'https://image.tmdb.org/t/p/w500/edPU5HxncLWa1YkgRPNkSd68ONG.jpg', pais = 'Jerusalem, Israel', nascimento = 1981, popularidade = 20.99, enriquecido_em = '2026-11-17 16:55:41' WHERE tipo = 'personagem' AND nome = 'Mathilda';
UPDATE entidades_match SET tmdb_id = 680, imagem_url = 'https://image.tmdb.org/t/p/w780/tptjnB2LDbuUWya9Cx5sQtv5hqb.jpg', ator_tmdb_id = 139, ator_foto_url = 'https://image.tmdb.org/t/p/w500/sBgAZWi3o4FsnaTvnTNtK6jpQcF.jpg', pais = 'Boston, Massachusetts, USA', nascimento = 1970, popularidade = 29.99, enriquecido_em = '2026-11-17 16:55:42' WHERE tipo = 'personagem' AND nome = 'Mia Wallace';
UPDATE entidades_match SET tmdb_id = 238, imagem_url = 'https://image.tmdb.org/t/p/w780/oJagOzBu9Rdd9BrciseCm3U3MCU.jpg', ator_tmdb_id = 1158, ator_foto_url = 'https://image.tmdb.org/t/p/w500/m8HAAjq1T75JypKk0v1FFQn4ysZ.jpg', pais = 'New York City, New York, USA', nascimento = 1940, popularidade = 40.74, enriquecido_em = '2026-11-17 16:55:44' WHERE tipo = 'personagem' AND nome = 'Michael Corleone';
UPDATE entidades_match SET tmdb_id = 603, imagem_url = 'https://image.tmdb.org/t/p/w780/lDqMDI3xpbB9UQRyeXfei0MXhqb.jpg', ator_tmdb_id = 6384, ator_foto_url = 'https://image.tmdb.org/t/p/w500/8RZLOyYGsoRe9p44q3xin9QkMHv.jpg', pais = 'Beirut, Lebanon', nascimento = 1964, popularidade = 37.01, enriquecido_em = '2026-11-17 16:55:45' WHERE tipo = 'personagem' AND nome = 'Neo';
UPDATE entidades_match SET tmdb_id = 115, imagem_url = 'https://image.tmdb.org/t/p/w780/d1Qxhvg2l3f2ow1Q6WAkOTkIWv2.jpg', ator_tmdb_id = 1229, ator_foto_url = 'https://image.tmdb.org/t/p/w500/xms1RAY6q7Lzp7wNeRCB0kzhucn.jpg', pais = 'Los Angeles, California, USA', nascimento = 1949, popularidade = 10.51, enriquecido_em = '2026-11-17 16:55:47' WHERE tipo = 'personagem' AND nome = 'O Dude';
UPDATE entidades_match SET tmdb_id = 78, imagem_url = 'https://image.tmdb.org/t/p/w780/y5ibL0vFnzsRiIHosw0WOfHtIj1.jpg', ator_tmdb_id = 3, ator_foto_url = 'https://image.tmdb.org/t/p/w500/pjBMJVPpcZK23Vt1nzr1zEBTWrP.jpg', pais = 'Chicago, Illinois, USA', nascimento = 1942, popularidade = 16.30, enriquecido_em = '2026-11-17 16:55:48' WHERE tipo = 'personagem' AND nome = 'Rick Deckard';
UPDATE entidades_match SET tmdb_id = 1366, imagem_url = 'https://image.tmdb.org/t/p/w780/b9B1DXcPNsJfTtc4x2LKA3bykoJ.jpg', ator_tmdb_id = 16483, ator_foto_url = 'https://image.tmdb.org/t/p/w500/gn3pDWthJqR0VDYGViGD3048og7.jpg', pais = 'New York City, New York, USA', nascimento = 1946, popularidade = 38.15, enriquecido_em = '2026-11-17 16:55:49' WHERE tipo = 'personagem' AND nome = 'Rocky Balboa';
UPDATE entidades_match SET tmdb_id = 280, imagem_url = 'https://image.tmdb.org/t/p/w780/eTIY2LO5cDclxgKtCHuvIKdF3c3.jpg', ator_tmdb_id = 2713, ator_foto_url = 'https://image.tmdb.org/t/p/w500/7FNn9Z5xkRS9EFbGL2tpmpph9xV.jpg', pais = 'Salisbury, Maryland, USA', nascimento = 1956, popularidade = 22.13, enriquecido_em = '2026-11-17 16:55:51' WHERE tipo = 'personagem' AND nome = 'Sarah Connor';
UPDATE entidades_match SET tmdb_id = 218, imagem_url = 'https://image.tmdb.org/t/p/w780/fo4SSoNT1ASI4JP7dOLrosWNWKa.jpg', ator_tmdb_id = 1100, ator_foto_url = 'https://image.tmdb.org/t/p/w500/4jM3vsHIEQVeNFifiTAUDVAmiUo.jpg', pais = 'Thal, Styria, Austria', nascimento = 1947, popularidade = 19.18, enriquecido_em = '2026-11-17 16:55:52' WHERE tipo = 'personagem' AND nome = 'T-800';
UPDATE entidades_match SET tmdb_id = 103, imagem_url = 'https://image.tmdb.org/t/p/w780/hvJvOYRrF9xxvNzg2Jd9PYWu7c3.jpg', ator_tmdb_id = 380, ator_foto_url = 'https://image.tmdb.org/t/p/w500/cT8htcckIuyI1Lqwt1CvD02ynTh.jpg', pais = 'Greenwich Village, New York City, New York, USA', nascimento = 1943, popularidade = 13.85, enriquecido_em = '2026-11-17 16:55:54' WHERE tipo = 'personagem' AND nome = 'Travis Bickle';
UPDATE entidades_match SET tmdb_id = 550, imagem_url = 'https://image.tmdb.org/t/p/w780/mCICnh7QBH0gzYaTQChBDDVIKdm.jpg', ator_tmdb_id = 287, ator_foto_url = 'https://image.tmdb.org/t/p/w500/ajNaPmXVVMJFg9GWmu6MJzTaXdV.jpg', pais = 'Shawnee, Oklahoma, USA', nascimento = 1963, popularidade = 33.35, enriquecido_em = '2026-11-17 16:55:56' WHERE tipo = 'personagem' AND nome = 'Tyler Durden';
UPDATE entidades_match SET tmdb_id = 238, imagem_url = 'https://image.tmdb.org/t/p/w780/oJagOzBu9Rdd9BrciseCm3U3MCU.jpg', ator_tmdb_id = 3084, ator_foto_url = 'https://image.tmdb.org/t/p/w500/yIvLpf9U9NfAvC7fuAgOQ8tu51T.jpg', pais = 'Omaha, Nebraska, USA', nascimento = 1924, popularidade = 40.74, enriquecido_em = '2026-11-17 16:55:56' WHERE tipo = 'personagem' AND nome = 'Vito Corleone';
UPDATE entidades_match SET tmdb_id = 525, imagem_url = 'https://image.tmdb.org/t/p/w780/xuAIuYSmsUzKlUMBFGVZaWsY3DZ.jpg', pais = 'Westminster, London, England, UK', nascimento = 1970, popularidade = 8.48, enriquecido_em = '2026-11-17 16:55:57' WHERE tipo = 'diretor' AND nome = 'Christopher Nolan';
UPDATE entidades_match SET tmdb_id = 5602, imagem_url = 'https://image.tmdb.org/t/p/w780/wwBzRDoIW6Ld64h6OkQ6ImCZKsR.jpg', pais = 'Missoula, Montana, USA', nascimento = 1946, popularidade = 2.39, enriquecido_em = '2026-11-17 16:55:58' WHERE tipo = 'diretor' AND nome = 'David Lynch';
UPDATE entidades_match SET tmdb_id = 137427, imagem_url = 'https://image.tmdb.org/t/p/w780/xzQYqb4nR8xT7Zdw5itbEL9K3fd.jpg', pais = 'Gentilly, Québec, Canada', nascimento = 1967, popularidade = 5.12, enriquecido_em = '2026-11-17 16:55:59' WHERE tipo = 'diretor' AND nome = 'Denis Villeneuve';
UPDATE entidades_match SET tmdb_id = 45400, imagem_url = 'https://image.tmdb.org/t/p/w780/dxKAhpkz4XNwmRzMG5QOpXjPZ1N.jpg', pais = 'Sacramento, California, USA', nascimento = 1983, popularidade = 1.75, enriquecido_em = '2026-11-17 16:56:00' WHERE tipo = 'diretor' AND nome = 'Greta Gerwig';
UPDATE entidades_match SET tmdb_id = 608, imagem_url = 'https://image.tmdb.org/t/p/w780/ouhjt9KugzhWtdEyBPipihB3ic8.jpg', pais = 'Tokyo, Japan', nascimento = 1941, popularidade = 3.43, enriquecido_em = '2026-11-17 16:56:01' WHERE tipo = 'diretor' AND nome = 'Hayao Miyazaki';
UPDATE entidades_match SET tmdb_id = 1032, imagem_url = 'https://image.tmdb.org/t/p/w780/g3DjfKsgZQWZiw30I20hZVk1oMX.jpg', pais = 'Queens, New York City, New York, USA', nascimento = 1942, popularidade = 3.81, enriquecido_em = '2026-11-17 16:56:01' WHERE tipo = 'diretor' AND nome = 'Martin Scorsese';
UPDATE entidades_match SET tmdb_id = 138, imagem_url = 'https://image.tmdb.org/t/p/w780/1gjcpAa99FAOWGnrUvHEXXsRs7o.jpg', pais = 'Knoxville, Tennessee, USA', nascimento = 1963, popularidade = 5.55, enriquecido_em = '2026-11-17 16:56:02' WHERE tipo = 'diretor' AND nome = 'Quentin Tarantino';
UPDATE entidades_match SET tmdb_id = 240, imagem_url = 'https://image.tmdb.org/t/p/w780/yFT0VyIelI9aegZrsAwOG5iVP4v.jpg', pais = 'Manhattan, New York City, New York, USA', nascimento = 1928, popularidade = 2.62, enriquecido_em = '2026-11-17 16:56:03' WHERE tipo = 'diretor' AND nome = 'Stanley Kubrick';
UPDATE entidades_match SET tmdb_id = 5655, imagem_url = 'https://image.tmdb.org/t/p/w780/s03CeUeC5yAXyB1acqP0zGNo2SC.jpg', pais = 'Houston, Texas, USA', nascimento = 1969, popularidade = 2.02, enriquecido_em = '2026-11-17 16:56:04' WHERE tipo = 'diretor' AND nome = 'Wes Anderson';
UPDATE entidades_match SET tmdb_id = 12453, imagem_url = 'https://image.tmdb.org/t/p/w780/cHW4xmQaHkoXIg5TZ0om6XyHuSJ.jpg', pais = 'Shanghai, China', nascimento = 1958, popularidade = 1.87, enriquecido_em = '2026-11-17 16:56:05' WHERE tipo = 'diretor' AND nome = 'Wong Kar-wai';
UPDATE entidades_match SET tmdb_id = 293354, imagem_url = 'https://image.tmdb.org/t/p/w300/u7nsZM0vmBMDpzIy27164AwMjXV.png', pais = 'Reino Unido', enriquecido_em = '2026-11-17 16:56:06' WHERE tipo = 'estudio' AND nome = 'A24';
UPDATE entidades_match SET tmdb_id = 211017, pais = 'Portugal', enriquecido_em = '2026-11-17 16:56:07' WHERE tipo = 'estudio' AND nome = 'Cinema Novo';
UPDATE entidades_match SET tmdb_id = 203112, enriquecido_em = '2026-11-17 16:56:08' WHERE tipo = 'estudio' AND nome = 'Neo-Noir';
UPDATE entidades_match SET tmdb_id = 235713, enriquecido_em = '2026-11-17 16:56:10' WHERE tipo = 'estudio' AND nome = 'New Hollywood';
UPDATE entidades_match SET tmdb_id = 10342, imagem_url = 'https://image.tmdb.org/t/p/w300/uFuxPEZRUcBTEiYIxjHJq62Vr77.png', pais = 'Japão', enriquecido_em = '2026-11-17 16:56:12' WHERE tipo = 'estudio' AND nome = 'Studio Ghibli';

UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 157336, o.poster_url = 'https://image.tmdb.org/t/p/w780/tR1XVa5bxgdh2bRw2u0DzrgkO2l.jpg' WHERE e.tipo = 'diretor' AND e.nome = 'Christopher Nolan' AND o.titulo = 'Interestelar';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 77, o.poster_url = 'https://image.tmdb.org/t/p/w780/7leBWM27Q0TUnvmhw6WnFNgGn4G.jpg' WHERE e.tipo = 'diretor' AND e.nome = 'Christopher Nolan' AND o.titulo = 'Amnésia';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 1018, o.poster_url = 'https://image.tmdb.org/t/p/w780/nLAE1zU4jLX3qxx4d87Nz7DsqQg.jpg' WHERE e.tipo = 'diretor' AND e.nome = 'David Lynch' AND o.titulo = 'Cidade dos Sonhos';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 793, o.poster_url = 'https://image.tmdb.org/t/p/w780/1Nlq42CnLmkXzj7TN58c3VjlwLn.jpg' WHERE e.tipo = 'diretor' AND e.nome = 'David Lynch' AND o.titulo = 'Veludo Azul';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 438631, o.poster_url = 'https://image.tmdb.org/t/p/w780/uzERcfV2rSHNhW5eViQiO9hNiA7.jpg' WHERE e.tipo = 'diretor' AND e.nome = 'Denis Villeneuve' AND o.titulo = 'Duna';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 329865, o.poster_url = 'https://image.tmdb.org/t/p/w780/3rDwbFpn6z5HJUgDjpfhEePx8VI.jpg' WHERE e.tipo = 'diretor' AND e.nome = 'Denis Villeneuve' AND o.titulo = 'A Chegada';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 391713, o.poster_url = 'https://image.tmdb.org/t/p/w780/wxfKcpFPmK9isnxXyvCMbqmBtdg.jpg' WHERE e.tipo = 'diretor' AND e.nome = 'Greta Gerwig' AND o.titulo = 'Lady Bird: A Hora de Voar';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 331482, o.poster_url = 'https://image.tmdb.org/t/p/w780/1dwhsiuvNyQmA9ytp3T40uz4MOv.jpg' WHERE e.tipo = 'diretor' AND e.nome = 'Greta Gerwig' AND o.titulo = 'Mulherzinhas';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 129, o.poster_url = 'https://image.tmdb.org/t/p/w780/hhoKhsyJ3hFaxEm5pMdZRiTu2lJ.jpg' WHERE e.tipo = 'diretor' AND e.nome = 'Hayao Miyazaki' AND o.titulo = 'A Viagem de Chihiro';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 8392, o.poster_url = 'https://image.tmdb.org/t/p/w780/23KKTxDg6rxZVc66bloQQdPSr29.jpg' WHERE e.tipo = 'diretor' AND e.nome = 'Hayao Miyazaki' AND o.titulo = 'Meu Amigo Totoro';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 103, o.poster_url = 'https://image.tmdb.org/t/p/w780/hvJvOYRrF9xxvNzg2Jd9PYWu7c3.jpg' WHERE e.tipo = 'diretor' AND e.nome = 'Martin Scorsese' AND o.titulo = 'Taxi Driver';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 1578, o.poster_url = 'https://image.tmdb.org/t/p/w780/lLeWJU33HgfWTnrOcaNBR5fUcF1.jpg' WHERE e.tipo = 'diretor' AND e.nome = 'Martin Scorsese' AND o.titulo = 'Touro Indomável';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 680, o.poster_url = 'https://image.tmdb.org/t/p/w780/tptjnB2LDbuUWya9Cx5sQtv5hqb.jpg' WHERE e.tipo = 'diretor' AND e.nome = 'Quentin Tarantino' AND o.titulo = 'Pulp Fiction: Tempo de Violência';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 466272, o.poster_url = 'https://image.tmdb.org/t/p/w780/zgr02U2Fh0sR5JtaAXY5yBU1wka.jpg' WHERE e.tipo = 'diretor' AND e.nome = 'Quentin Tarantino' AND o.titulo = 'Era Uma Vez em... Hollywood';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 62, o.poster_url = 'https://image.tmdb.org/t/p/w780/pVCmLuATJ0lQs4Vx1zUJUN0if2A.jpg' WHERE e.tipo = 'diretor' AND e.nome = 'Stanley Kubrick' AND o.titulo = '2001: Uma Odisseia no Espaço';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 694, o.poster_url = 'https://image.tmdb.org/t/p/w780/47BdQqDWnj3VIzpH9bd2agho2PN.jpg' WHERE e.tipo = 'diretor' AND e.nome = 'Stanley Kubrick' AND o.titulo = 'O Iluminado';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 120467, o.poster_url = 'https://image.tmdb.org/t/p/w780/yabOguSrb8ffUXCkI6t8Rw7xtSh.jpg' WHERE e.tipo = 'diretor' AND e.nome = 'Wes Anderson' AND o.titulo = 'O Grande Hotel Budapeste';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 83666, o.poster_url = 'https://image.tmdb.org/t/p/w780/3Vo4SrfuYYRcvQX02PqZJAaOZJt.jpg' WHERE e.tipo = 'diretor' AND e.nome = 'Wes Anderson' AND o.titulo = 'Moonrise Kingdom';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 843, o.poster_url = 'https://image.tmdb.org/t/p/w780/yCKaf65zoySg2iJdsews4Rafl7C.jpg' WHERE e.tipo = 'diretor' AND e.nome = 'Wong Kar-wai' AND o.titulo = 'Amor à Flor da Pele';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 11104, o.poster_url = 'https://image.tmdb.org/t/p/w780/49MJKMuSSeTx8yBa8NU7afQvwlx.jpg' WHERE e.tipo = 'diretor' AND e.nome = 'Wong Kar-wai' AND o.titulo = 'Chungking Express';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 493922, o.poster_url = 'https://image.tmdb.org/t/p/w780/x9tUYQj6WrdVwoKimSdoMzkDABS.jpg' WHERE e.tipo = 'estudio' AND e.nome = 'A24' AND o.titulo = 'Hereditário';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 376867, o.poster_url = 'https://image.tmdb.org/t/p/w780/AekOkoT88EhDHikUQXQcKri2q4B.jpg' WHERE e.tipo = 'estudio' AND e.nome = 'A24' AND o.titulo = 'Moonlight: Sob a Luz do Luar';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 744, o.poster_url = 'https://image.tmdb.org/t/p/w780/dDrMtnVgtDeecIgNchb9F8JcO5w.jpg' WHERE e.tipo = 'estudio' AND e.nome = 'Blockbuster Anos 80' AND o.titulo = 'Top Gun: Ases Indomáveis';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 105, o.poster_url = 'https://image.tmdb.org/t/p/w780/i996T0lI1fGtFEowiH3V6eZthL0.jpg' WHERE e.tipo = 'estudio' AND e.nome = 'Blockbuster Anos 80' AND o.titulo = 'De Volta para o Futuro';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 67612, o.poster_url = 'https://image.tmdb.org/t/p/w780/vBSTJhHcVq5zffTudQ7jMG9vDwI.jpg' WHERE e.tipo = 'estudio' AND e.nome = 'Cinema Novo' AND o.titulo = 'Deus e o Diabo na Terra do Sol';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 74349, o.poster_url = 'https://image.tmdb.org/t/p/w780/oojFooNbPvFZ7BywsUfKh2Jxoqr.jpg' WHERE e.tipo = 'estudio' AND e.nome = 'Cinema Novo' AND o.titulo = 'O Dragão da Maldade contra o Santo Guerreiro';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 78, o.poster_url = 'https://image.tmdb.org/t/p/w780/y5ibL0vFnzsRiIHosw0WOfHtIj1.jpg' WHERE e.tipo = 'estudio' AND e.nome = 'Neo-Noir' AND o.titulo = 'Blade Runner, o Caçador de Androides';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 829, o.poster_url = 'https://image.tmdb.org/t/p/w780/kZRSP3FmOcq0xnBulqpUQngJUXY.jpg' WHERE e.tipo = 'estudio' AND e.nome = 'Neo-Noir' AND o.titulo = 'Chinatown';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 103, o.poster_url = 'https://image.tmdb.org/t/p/w780/hvJvOYRrF9xxvNzg2Jd9PYWu7c3.jpg' WHERE e.tipo = 'estudio' AND e.nome = 'New Hollywood' AND o.titulo = 'Taxi Driver';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 238, o.poster_url = 'https://image.tmdb.org/t/p/w780/oJagOzBu9Rdd9BrciseCm3U3MCU.jpg' WHERE e.tipo = 'estudio' AND e.nome = 'New Hollywood' AND o.titulo = 'O Poderoso Chefão';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 129, o.poster_url = 'https://image.tmdb.org/t/p/w780/hhoKhsyJ3hFaxEm5pMdZRiTu2lJ.jpg' WHERE e.tipo = 'estudio' AND e.nome = 'Studio Ghibli' AND o.titulo = 'A Viagem de Chihiro';
UPDATE entidades_obras o JOIN entidades_match e ON e.id = o.entidade_id SET o.tmdb_id = 8392, o.poster_url = 'https://image.tmdb.org/t/p/w780/23KKTxDg6rxZVc66bloQQdPSr29.jpg' WHERE e.tipo = 'estudio' AND e.nome = 'Studio Ghibli' AND o.titulo = 'Meu Amigo Totoro';

COMMIT;

-- >>> sql/004_cenas_video.sql
-- =============================================================================
-- PersonaCut — cenas em vídeo (gerado a partir do banco)
-- Gerado em 2026-11-17 15:26:42
-- Fonte: bin/midia_export.php (não edite à mão)
-- =============================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

START TRANSACTION;

UPDATE perguntas
   SET midia_tipo = 'video_yt',
       midia_url_ou_id = 'hIYCqe_14bA',
       midia_meta = '{\"ano\":1942,\"end\":12,\"fonte\":\"tmdb\",\"start\":0,\"poster\":\"/87wqeMeUeMRLjsE1IWNKJcPlpuC.jpg\",\"credito\":\"YouTube\",\"legenda\":\"Casablanca (1942)\",\"tmdb_id\":289,\"video_nome\":\"Humphrey Bogart Parts Ways With Ingrid Bergman | Casablanca | TCM\",\"video_tipo\":\"Clip\",\"video_score\":9,\"video_idioma\":\"en\"}'
 WHERE texto_key = 'pergunta.estetica.01';

UPDATE perguntas
   SET midia_tipo = 'video_yt',
       midia_url_ou_id = '2dyC09Wd0JY',
       midia_meta = '{\"ano\":2002,\"end\":12,\"fonte\":\"tmdb\",\"start\":0,\"poster\":\"/uvitbjFU4JqvMwIkMWHp69bmUzG.jpg\",\"credito\":\"YouTube\",\"legenda\":\"Cidade de Deus (2002)\",\"tmdb_id\":598,\"video_nome\":\"Looking for Paradise\",\"video_tipo\":\"Clip\",\"video_score\":14,\"video_idioma\":\"en\"}'
 WHERE texto_key = 'pergunta.universo.01';

UPDATE perguntas
   SET midia_tipo = 'video_yt',
       midia_url_ou_id = 'fThQk286c2k',
       midia_meta = '{\"ano\":1984,\"end\":12,\"fonte\":\"tmdb\",\"start\":0,\"poster\":\"/pAKpSMrCv9HQdMKvIdLgTdpKh5f.jpg\",\"credito\":\"YouTube\",\"legenda\":\"Paris, Texas (1984)\",\"tmdb_id\":655,\"video_nome\":\"Official Clip - Memories\",\"video_tipo\":\"Clip\",\"video_score\":14,\"video_idioma\":\"en\"}'
 WHERE texto_key = 'pergunta.ritmo.01';

UPDATE perguntas
   SET midia_tipo = 'video_yt',
       midia_url_ou_id = '7IXVycNgGN0',
       midia_meta = '{\"ano\":1972,\"end\":12,\"fonte\":\"tmdb\",\"start\":0,\"poster\":\"/39rtAdg3yx6IDxRg0ZLXefv0aPY.jpg\",\"credito\":\"YouTube\",\"legenda\":\"O Poderoso Chefão (1972)\",\"tmdb_id\":238,\"video_nome\":\"\\\"Don Vito and Michael Corleone\\\" Clip\",\"video_tipo\":\"Clip\",\"video_score\":22,\"video_idioma\":\"en\"}'
 WHERE texto_key = 'pergunta.moralidade.01';

UPDATE perguntas
   SET midia_tipo = 'video_yt',
       midia_url_ou_id = 'vdtqSaJO-iM',
       midia_meta = '{\"ano\":1994,\"end\":12,\"fonte\":\"tmdb\",\"start\":0,\"poster\":\"/66Kn4XWhkuPkJxOJyPEx4U2CUfN.jpg\",\"credito\":\"YouTube\",\"legenda\":\"Forrest Gump: O Contador de Histórias (1994)\",\"tmdb_id\":13,\"video_nome\":\"\\\"Life Is Like a Box of Chocolates\\\" Full Scene\",\"video_tipo\":\"Clip\",\"video_score\":14,\"video_idioma\":\"en\"}'
 WHERE texto_key = 'pergunta.estrutura.01';

UPDATE perguntas
   SET midia_tipo = 'video_yt',
       midia_url_ou_id = 'KhnmAwiHtzo',
       midia_meta = '{\"ano\":1957,\"end\":12,\"fonte\":\"tmdb\",\"start\":0,\"poster\":\"/qqHQsStV6exghCM7zbObuYBiYxw.jpg\",\"credito\":\"YouTube\",\"legenda\":\"12 Homens e uma Sentença (1957)\",\"tmdb_id\":389,\"video_nome\":\"Henry Fonda Calls For a Secret Ballot | 12 Angry Men (1957) | TCM\",\"video_tipo\":\"Clip\",\"video_score\":22,\"video_idioma\":\"en\"}'
 WHERE texto_key = 'pergunta.atmosfera.01';

UPDATE perguntas
   SET midia_tipo = 'video_yt',
       midia_url_ou_id = 'FIlqIj0fuk0',
       midia_meta = '{\"ano\":2014,\"end\":12,\"fonte\":\"tmdb\",\"start\":0,\"poster\":\"/jK65srQczOKTpW62wPxwwKztGgE.jpg\",\"credito\":\"YouTube\",\"legenda\":\"O Grande Hotel Budapeste (2014)\",\"tmdb_id\":120467,\"video_nome\":\"\\\"We Must Go To Her\\\"\",\"video_tipo\":\"Clip\",\"video_score\":14,\"video_idioma\":\"en\"}'
 WHERE texto_key = 'pergunta.tom.02';

UPDATE perguntas
   SET midia_tipo = 'video_yt',
       midia_url_ou_id = '9P-9uVo4zg4',
       midia_meta = '{\"ano\":2001,\"end\":12,\"fonte\":\"tmdb\",\"start\":0,\"poster\":\"/oiwc338EoBgS4sEI2ixAny4KQKg.jpg\",\"credito\":\"YouTube\",\"legenda\":\"O Senhor dos Anéis: A Sociedade do Anel (2001)\",\"tmdb_id\":120,\"video_nome\":\"Mountain Pass Battle\",\"video_tipo\":\"Clip\",\"video_score\":22,\"video_idioma\":\"en\"}'
 WHERE texto_key = 'pergunta.universo.02';

UPDATE perguntas
   SET midia_tipo = 'video_yt',
       midia_url_ou_id = 'f0hdxFD5Nhg',
       midia_meta = '{\"ano\":2015,\"end\":12,\"fonte\":\"tmdb\",\"start\":0,\"poster\":\"/gqrnQA6Xppdl8vIb2eJc58VC1tW.jpg\",\"credito\":\"YouTube\",\"legenda\":\"Mad Max: Estrada da Fúria (2015)\",\"tmdb_id\":76341,\"video_nome\":\"Max Saves Furiosa\",\"video_tipo\":\"Clip\",\"video_score\":14,\"video_idioma\":\"en\"}'
 WHERE texto_key = 'pergunta.ritmo.02';

UPDATE perguntas
   SET midia_tipo = 'video_yt',
       midia_url_ou_id = '8bYvCSfd72A',
       midia_meta = '{\"ano\":1981,\"end\":12,\"fonte\":\"tmdb\",\"start\":0,\"poster\":\"/c7Mjuip0jfHLY7x8ZSEriRj45cu.jpg\",\"credito\":\"YouTube\",\"legenda\":\"Indiana Jones e os Caçadores da Arca Perdida (1981)\",\"tmdb_id\":85,\"video_nome\":\"Rolling Boulder Chase Scene\",\"video_tipo\":\"Clip\",\"video_score\":22,\"video_idioma\":\"en\"}'
 WHERE texto_key = 'pergunta.moralidade.02';

UPDATE perguntas
   SET midia_tipo = 'video_yt',
       midia_url_ou_id = 'h_rnjp1PAk8',
       midia_meta = '{\"ano\":1994,\"end\":12,\"fonte\":\"tmdb\",\"start\":0,\"poster\":\"/suaEOtk1N1sgg2MTM7oZd2cfVp3.jpg\",\"credito\":\"YouTube\",\"legenda\":\"Pulp Fiction: Tempo de Violência (1994)\",\"tmdb_id\":680,\"video_nome\":\"Time Is a Factor\",\"video_tipo\":\"Clip\",\"video_score\":22,\"video_idioma\":\"en\"}'
 WHERE texto_key = 'pergunta.estrutura.02';

UPDATE perguntas
   SET midia_tipo = 'video_yt',
       midia_url_ou_id = 'ky7ksownEEE',
       midia_meta = '{\"ano\":1999,\"end\":12,\"fonte\":\"tmdb\",\"start\":0,\"poster\":\"/8K9qHeM6G6QjQN0C5XKFGvK5lzM.jpg\",\"credito\":\"YouTube\",\"legenda\":\"Matrix (1999)\",\"tmdb_id\":603,\"video_nome\":\"The Red Pill or The Blue Pill\",\"video_tipo\":\"Clip\",\"video_score\":22,\"video_idioma\":\"en\"}'
 WHERE texto_key = 'pergunta.epoca.02';

UPDATE perguntas
   SET midia_tipo = 'video_yt',
       midia_url_ou_id = 'opbi7d42s8E',
       midia_meta = '{\"ano\":2007,\"end\":12,\"fonte\":\"tmdb\",\"start\":0,\"poster\":\"/fCCvn3xsG29TGKJPvZgTRppsDgk.jpg\",\"credito\":\"YouTube\",\"legenda\":\"Onde os Fracos Não Têm Vez (2007)\",\"tmdb_id\":6977,\"video_nome\":\"\'Coin Toss\'\",\"video_tipo\":\"Clip\",\"video_score\":14,\"video_idioma\":\"en\"}'
 WHERE texto_key = 'pergunta.moralidade.03';

UPDATE perguntas
   SET midia_tipo = 'video_yt',
       midia_url_ou_id = 'l-69HTc-ZHU',
       midia_meta = '{\"ano\":1982,\"end\":12,\"fonte\":\"tmdb\",\"start\":0,\"poster\":\"/mXLVA0YL6tcXi6SJSuAh9ONXFj5.jpg\",\"credito\":\"YouTube\",\"legenda\":\"E.T.: O Extraterrestre (1982)\",\"tmdb_id\":601,\"video_nome\":\"E.T. Phone Home in 4K HDR\",\"video_tipo\":\"Clip\",\"video_score\":14,\"video_idioma\":\"en\"}'
 WHERE texto_key = 'pergunta.estrutura.03';

UPDATE perguntas
   SET midia_tipo = 'video_yt',
       midia_url_ou_id = 'sbwzV4cN90I',
       midia_meta = '{\"ano\":1995,\"end\":12,\"fonte\":\"tmdb\",\"start\":0,\"poster\":\"/qA2TyqPldTtoTVY3LKrNIG5g6bH.jpg\",\"credito\":\"YouTube\",\"legenda\":\"Antes do Amanhecer (1995)\",\"tmdb_id\":76,\"video_nome\":\"A Scene from BEFORE SUNRISE\",\"video_tipo\":\"Clip\",\"video_score\":14,\"video_idioma\":\"en\"}'
 WHERE texto_key = 'pergunta.atmosfera.03';

UPDATE perguntas
   SET midia_tipo = 'video_yt',
       midia_url_ou_id = 'mD6ZJbgoKsI',
       midia_meta = '{\"ano\":1975,\"end\":12,\"fonte\":\"tmdb\",\"start\":0,\"poster\":\"/ef6SvWbSfCFlKodJZqpIaTvsRRP.jpg\",\"credito\":\"YouTube\",\"legenda\":\"Barry Lyndon (1975)\",\"tmdb_id\":3175,\"video_nome\":\"Barry\'s First Day in the British Army\",\"video_tipo\":\"Clip\",\"video_score\":14,\"video_idioma\":\"en\"}'
 WHERE texto_key = 'pergunta.epoca.03';

UPDATE perguntas
   SET midia_tipo = 'video_yt',
       midia_url_ou_id = 'OeRV2pvx1UU',
       midia_meta = '{\"ano\":2004,\"end\":12,\"fonte\":\"tmdb\",\"start\":0,\"poster\":\"/95qi5TJMIBI5VzWHia88Pbrye7Z.jpg\",\"credito\":\"YouTube\",\"legenda\":\"Brilho Eterno de uma Mente sem Lembranças (2004)\",\"tmdb_id\":38,\"video_nome\":\"Joel Finds Out Clem Erased Him From Her Memory\",\"video_tipo\":\"Clip\",\"video_score\":22,\"video_idioma\":\"en\"}'
 WHERE texto_key = 'pergunta.estetica.04';

UPDATE perguntas
   SET midia_tipo = 'video_yt',
       midia_url_ou_id = '6pQgbEEFPq0',
       midia_meta = '{\"ano\":1989,\"end\":12,\"fonte\":\"tmdb\",\"start\":0,\"poster\":\"/dbdegCWeD4iK5LXxSBjGdIRsVXS.jpg\",\"credito\":\"YouTube\",\"legenda\":\"Harry & Sally: Feitos um para o Outro (1989)\",\"tmdb_id\":639,\"video_nome\":\"I\'ll Have What She\'s Having\",\"video_tipo\":\"Clip\",\"video_score\":14,\"video_idioma\":\"en\"}'
 WHERE texto_key = 'pergunta.tom.04';

UPDATE perguntas
   SET midia_tipo = 'video_yt',
       midia_url_ou_id = '3RNjy0WUSVY',
       midia_meta = '{\"ano\":1977,\"end\":12,\"fonte\":\"tmdb\",\"start\":0,\"poster\":\"/4qCqAdHcNKeAHcK8tJ8wNJZa9cx.jpg\",\"credito\":\"YouTube\",\"legenda\":\"Guerra nas Estrelas (1977)\",\"tmdb_id\":11,\"video_nome\":\"Luke Skywalker Enters the Mos Eisley Cantina - Official Clip\",\"video_tipo\":\"Clip\",\"video_score\":14,\"video_idioma\":\"en\"}'
 WHERE texto_key = 'pergunta.universo.04';

UPDATE perguntas
   SET midia_tipo = 'video_yt',
       midia_url_ou_id = '_rIYXfztyxA',
       midia_meta = '{\"ano\":1995,\"end\":12,\"fonte\":\"tmdb\",\"start\":0,\"poster\":\"/5JaR6UEoCJJLtLpqFOVMY4O4R7P.jpg\",\"credito\":\"YouTube\",\"legenda\":\"Fogo Contra Fogo (1995)\",\"tmdb_id\":949,\"video_nome\":\"Face To Face\",\"video_tipo\":\"Clip\",\"video_score\":14,\"video_idioma\":\"en\"}'
 WHERE texto_key = 'pergunta.ritmo.04';

UPDATE perguntas
   SET midia_tipo = 'video_yt',
       midia_url_ou_id = 'UCyqwsoISMs',
       midia_meta = '{\"ano\":2003,\"end\":12,\"fonte\":\"tmdb\",\"start\":0,\"poster\":\"/ctiw6FZK4N36LmkjSklWEbuvlq9.jpg\",\"credito\":\"YouTube\",\"legenda\":\"O Senhor dos Anéis: O Retorno do Rei (2003)\",\"tmdb_id\":122,\"video_nome\":\"Siege of Gondor Begins\",\"video_tipo\":\"Clip\",\"video_score\":22,\"video_idioma\":\"en\"}'
 WHERE texto_key = 'pergunta.moralidade.04';

UPDATE perguntas
   SET midia_tipo = 'video_yt',
       midia_url_ou_id = 'k5DTbekB86s',
       midia_meta = '{\"ano\":2010,\"end\":12,\"fonte\":\"tmdb\",\"start\":0,\"poster\":\"/8ZTVqvKDQ8emSGUEMjsS4yHAwrp.jpg\",\"credito\":\"YouTube\",\"legenda\":\"A Origem (2010)\",\"tmdb_id\":27205,\"video_nome\":\"The Final Scene\",\"video_tipo\":\"Clip\",\"video_score\":14,\"video_idioma\":\"en\"}'
 WHERE texto_key = 'pergunta.estrutura.04';

UPDATE perguntas
   SET midia_tipo = 'video_yt',
       midia_url_ou_id = 'Rvns5DaW-ug',
       midia_meta = '{\"ano\":2014,\"end\":12,\"fonte\":\"tmdb\",\"start\":0,\"poster\":\"/8sNiAPPYU14PUepFNeSNGUTiHW.jpg\",\"credito\":\"YouTube\",\"legenda\":\"Interestelar (2014)\",\"tmdb_id\":157336,\"video_nome\":\"Murph Saves The World - Full Scene\",\"video_tipo\":\"Clip\",\"video_score\":14,\"video_idioma\":\"en\"}'
 WHERE texto_key = 'pergunta.atmosfera.04';

UPDATE perguntas
   SET midia_tipo = 'video_yt',
       midia_url_ou_id = 'J5R_fmB9_uk',
       midia_meta = '{\"ano\":2013,\"end\":12,\"fonte\":\"tmdb\",\"start\":0,\"poster\":\"/1YnZchmaGc8dchgRPDpR1KGrixA.jpg\",\"credito\":\"YouTube\",\"legenda\":\"Ela (2013)\",\"tmdb_id\":152601,\"video_nome\":\"Her | \\\"What Do You Love About Samantha?\\\" Clip | Warner Bros. Entertainment\",\"video_tipo\":\"Clip\",\"video_score\":22,\"video_idioma\":\"en\"}'
 WHERE texto_key = 'pergunta.epoca.04';

COMMIT;

-- >>> sql/005_rotulos.sql
-- =============================================================================
-- 005 — Rótulos públicos dos eixos
--
-- O motor (slugs, vetores, cálculos) continua idêntico. Aqui mudam apenas os
-- nomes que aparecem na interface: nomes mais claros e polos sem juízo de valor.
-- "Tempo" e "Temporalidade" convivendo era o caso mais confuso; viram "Ritmo"
-- e "Época". "Maniqueísta" vira "Clara".
-- =============================================================================

SET NAMES utf8mb4;

UPDATE eixos SET
    nome = 'Imagem',
    descricao = 'O que a imagem faz com você: clareza clássica contra ruptura, textura e invenção.'
WHERE slug = 'estetica';

UPDATE eixos SET
    nome = 'Emoção',
    descricao = 'O que o filme faz sentir: peso e silêncio contra leveza, humor e festa.',
    polo_esq_nome = 'Melancólica',
    polo_dir_nome = 'Eufórica'
WHERE slug = 'tom';

UPDATE eixos SET
    nome = 'Mundo',
    descricao = 'O quanto o mundo é inventado: a esquina reconhecível contra cosmologias e mundos impossíveis.'
WHERE slug = 'universo';

UPDATE eixos SET
    nome = 'Ritmo',
    descricao = 'A velocidade da experiência: planos que respiram contra corte, som e adrenalina.'
WHERE slug = 'ritmo';

UPDATE eixos SET
    nome = 'Ética',
    descricao = 'Como o filme trata o certo e o errado: zona cinzenta contra lados bem demarcados.',
    polo_dir_nome = 'Clara'
WHERE slug = 'moralidade';

UPDATE eixos SET
    nome = 'História',
    descricao = 'Como a história é contada: causalidade e arco fechado contra quebra de tempo e camadas.'
WHERE slug = 'estrutura';

UPDATE eixos SET
    nome = 'Tamanho',
    descricao = 'A escala do drama: quartos e closes contra exércitos, planetas e tela grande.'
WHERE slug = 'atmosfera';

UPDATE eixos SET
    nome = 'Época',
    descricao = 'De onde o filme fala: memória, grão e passado contra neon, código e imaginação do amanhã.'
WHERE slug = 'epoca';

-- >>> sql/009_catalogo.sql
-- =============================================================================
-- PersonaCut — migração 009: catálogo audiovisual e plataformas de streaming
--
-- Até aqui o "catálogo" era editorial: 52 entidades do matcher, 32 obras
-- curadas e uma lista de filmes em PHP. Esta migração cria a base que permite
-- recomendar títulos e ranquear plataformas por perfil.
--
-- Pode ser executada mais de uma vez (CREATE TABLE IF NOT EXISTS + INSERT
-- IGNORE). O conteúdo (obras, gêneros, países, disponibilidade) NÃO é criado
-- aqui: vem de bin/catalogo_sync.php e do snapshot gerado por
-- bin/catalogo_export.php (sql/010_catalogo_seed.sql).
--
-- Uso em banco existente:
--   docker compose exec -T db mysql -upersonacut -ppersonacut123 personacut \
--     < sql/009_catalogo.sql
-- =============================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Obras: uma linha por filme ou série.
-- `eixo_1..8` é o vetor de gosto calculado no ETL (mesma régua do perfil).
-- `confianca` (0-100) diz quantos sinais alimentaram o vetor; obras com pouca
-- confiança não entram no ranking de plataformas.
-- `poster_path`/`backdrop_path` guardam o CAMINHO da TMDB (ex.: /abc.jpg), não
-- a URL completa — economiza espaço e permite trocar o CDN sem reescrever tudo.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS catalogo_obras (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tmdb_id         INT UNSIGNED NOT NULL,
    tipo            ENUM('filme', 'serie') NOT NULL,
    titulo          VARCHAR(200) NOT NULL,
    titulo_original VARCHAR(200) NULL,
    ano             SMALLINT UNSIGNED NULL,
    sinopse         VARCHAR(600) NULL,
    duracao         SMALLINT UNSIGNED NULL,
    temporadas      TINYINT UNSIGNED NULL,
    poster_path     VARCHAR(160) NULL,
    backdrop_path   VARCHAR(160) NULL,
    nota            DECIMAL(3,1) NULL,
    votos           INT UNSIGNED NOT NULL DEFAULT 0,
    popularidade    DECIMAL(10,2) NOT NULL DEFAULT 0,
    idioma_original CHAR(5) NULL,
    eixo_1          TINYINT UNSIGNED NOT NULL DEFAULT 50,
    eixo_2          TINYINT UNSIGNED NOT NULL DEFAULT 50,
    eixo_3          TINYINT UNSIGNED NOT NULL DEFAULT 50,
    eixo_4          TINYINT UNSIGNED NOT NULL DEFAULT 50,
    eixo_5          TINYINT UNSIGNED NOT NULL DEFAULT 50,
    eixo_6          TINYINT UNSIGNED NOT NULL DEFAULT 50,
    eixo_7          TINYINT UNSIGNED NOT NULL DEFAULT 50,
    eixo_8          TINYINT UNSIGNED NOT NULL DEFAULT 50,
    confianca       TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ativo           TINYINT(1) NOT NULL DEFAULT 1,
    atualizado_em   DATETIME NULL,
    criado_em       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_catalogo_obras_tmdb (tipo, tmdb_id),
    KEY idx_catalogo_obras_ano (ano),
    KEY idx_catalogo_obras_idioma (idioma_original),
    KEY idx_catalogo_obras_pop (popularidade),
    KEY idx_catalogo_obras_tipo (tipo, ativo),
    CONSTRAINT chk_catalogo_e1 CHECK (eixo_1 BETWEEN 0 AND 100),
    CONSTRAINT chk_catalogo_e2 CHECK (eixo_2 BETWEEN 0 AND 100),
    CONSTRAINT chk_catalogo_e3 CHECK (eixo_3 BETWEEN 0 AND 100),
    CONSTRAINT chk_catalogo_e4 CHECK (eixo_4 BETWEEN 0 AND 100),
    CONSTRAINT chk_catalogo_e5 CHECK (eixo_5 BETWEEN 0 AND 100),
    CONSTRAINT chk_catalogo_e6 CHECK (eixo_6 BETWEEN 0 AND 100),
    CONSTRAINT chk_catalogo_e7 CHECK (eixo_7 BETWEEN 0 AND 100),
    CONSTRAINT chk_catalogo_e8 CHECK (eixo_8 BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Gêneros e países normalizados (evitam JSON e permitem filtro por índice).
-- O id do gênero é o mesmo da TMDB; países usam ISO 3166-1 alfa-2.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS catalogo_generos (
    id   SMALLINT UNSIGNED PRIMARY KEY,
    slug VARCHAR(40) NOT NULL,
    nome VARCHAR(60) NOT NULL,
    UNIQUE KEY uq_catalogo_generos_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS catalogo_obra_generos (
    obra_id   INT UNSIGNED NOT NULL,
    genero_id SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (obra_id, genero_id),
    KEY idx_catalogo_og_genero (genero_id),
    CONSTRAINT fk_catalogo_og_obra FOREIGN KEY (obra_id)
        REFERENCES catalogo_obras (id) ON DELETE CASCADE,
    CONSTRAINT fk_catalogo_og_genero FOREIGN KEY (genero_id)
        REFERENCES catalogo_generos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS catalogo_paises (
    codigo CHAR(2) PRIMARY KEY,
    nome   VARCHAR(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS catalogo_obra_paises (
    obra_id     INT UNSIGNED NOT NULL,
    pais_codigo CHAR(2) NOT NULL,
    PRIMARY KEY (obra_id, pais_codigo),
    KEY idx_catalogo_op_pais (pais_codigo),
    CONSTRAINT fk_catalogo_op_obra FOREIGN KEY (obra_id)
        REFERENCES catalogo_obras (id) ON DELETE CASCADE,
    CONSTRAINT fk_catalogo_op_pais FOREIGN KEY (pais_codigo)
        REFERENCES catalogo_paises (codigo) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Plataformas de streaming.
-- O preço NÃO vem da TMDB: é campo editorial, editável no painel, com data da
-- última conferência. `ativa = 0` tira a plataforma do ranking sem apagar o
-- histórico de disponibilidade.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS catalogo_plataformas (
    id                 SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tmdb_provider_id   INT UNSIGNED NOT NULL,
    slug               VARCHAR(40) NOT NULL,
    nome               VARCHAR(80) NOT NULL,
    logo_path          VARCHAR(160) NULL,
    preco_mensal       DECIMAL(6,2) NULL,
    preco_atualizado_em DATE NULL,
    url                VARCHAR(255) NULL,
    prioridade         TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ativa              TINYINT(1) NOT NULL DEFAULT 1,
    atualizado_em      DATETIME NULL,
    UNIQUE KEY uq_catalogo_plat_tmdb (tmdb_provider_id),
    UNIQUE KEY uq_catalogo_plat_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS catalogo_obra_plataformas (
    obra_id       INT UNSIGNED NOT NULL,
    plataforma_id SMALLINT UNSIGNED NOT NULL,
    oferta        ENUM('flatrate', 'free', 'ads') NOT NULL DEFAULT 'flatrate',
    regiao        CHAR(2) NOT NULL DEFAULT 'BR',
    atualizado_em DATETIME NULL,
    PRIMARY KEY (obra_id, plataforma_id, oferta, regiao),
    KEY idx_catalogo_opl_plataforma (plataforma_id, oferta, regiao),
    CONSTRAINT fk_catalogo_opl_obra FOREIGN KEY (obra_id)
        REFERENCES catalogo_obras (id) ON DELETE CASCADE,
    CONSTRAINT fk_catalogo_opl_plataforma FOREIGN KEY (plataforma_id)
        REFERENCES catalogo_plataformas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Plataformas que entram no ranking no Brasil.
-- INSERT IGNORE: reexecutar a migração não sobrescreve preço/ordem editados.
-- Preços são referência editorial (confira antes de divulgar) e ficam no admin.
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO catalogo_plataformas
    (tmdb_provider_id, slug, nome, preco_mensal, preco_atualizado_em, prioridade, ativa)
VALUES
    (8,    'netflix',       'Netflix',         20.90, '2026-11-01', 1, 1),
    (119,  'prime-video',   'Prime Video',     19.90, '2026-11-01', 2, 1),
    (337,  'disney-plus',   'Disney+',         27.99, '2026-11-01', 3, 1),
    (1899, 'max',           'Max',             29.90, '2026-11-01', 4, 1),
    (307,  'globoplay',     'Globoplay',       24.90, '2026-11-01', 5, 1),
    (531,  'paramount-plus','Paramount+',      19.90, '2026-11-01', 6, 1),
    (350,  'apple-tv-plus', 'Apple TV+',       29.90, '2026-11-01', 7, 1),
    (283,  'crunchyroll',   'Crunchyroll',     24.99, '2026-11-01', 8, 1),
    (11,   'mubi',          'MUBI',            27.90, '2026-11-01', 9, 1),
    (47,   'looke',         'Looke',           16.90, '2026-11-01', 10, 1);

-- -----------------------------------------------------------------------------
-- Pessoas e créditos (elenco e direção) do catálogo.
-- Separado de `entidades_match` de propósito: aquela tabela é curadoria do
-- matcher (52 espelhos com vetor), esta é o elenco real dos títulos ingeridos.
-- `bin/catalogo_sync.php --creditos` preenche as duas.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS catalogo_pessoas (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tmdb_id      INT UNSIGNED NOT NULL,
    nome         VARCHAR(120) NOT NULL,
    foto_path    VARCHAR(160) NULL,
    popularidade DECIMAL(10,2) NULL,
    atualizado_em DATETIME NULL,
    UNIQUE KEY uq_catalogo_pessoas_tmdb (tmdb_id),
    KEY idx_catalogo_pessoas_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS catalogo_creditos (
    obra_id    INT UNSIGNED NOT NULL,
    pessoa_id  INT UNSIGNED NOT NULL,
    funcao     ENUM('elenco', 'direcao') NOT NULL DEFAULT 'elenco',
    personagem VARCHAR(160) NULL,
    ordem      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (obra_id, pessoa_id, funcao),
    KEY idx_catalogo_creditos_pessoa (pessoa_id, funcao),
    CONSTRAINT fk_catalogo_creditos_obra FOREIGN KEY (obra_id)
        REFERENCES catalogo_obras (id) ON DELETE CASCADE,
    CONSTRAINT fk_catalogo_creditos_pessoa FOREIGN KEY (pessoa_id)
        REFERENCES catalogo_pessoas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
