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
