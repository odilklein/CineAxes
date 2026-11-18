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
