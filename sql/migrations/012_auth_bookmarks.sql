-- =============================================================================
-- PersonaCut — migração 012: contas públicas e bookmarks
--
-- Execute em banco existente (não apaga perfis):
--   docker compose exec -T db mysql -upersonacut -ppersonacut123 personacut \
--     < sql/migrations/012_auth_bookmarks.sql
-- =============================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuarios (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome         VARCHAR(80)  NOT NULL,
    usuario      VARCHAR(40)  NOT NULL,
    senha        VARCHAR(255) NOT NULL,
    criado_em    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_login DATETIME NULL,
    UNIQUE KEY uq_usuarios_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookmarks (
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
