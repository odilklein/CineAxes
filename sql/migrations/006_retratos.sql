-- Migração para bancos criados antes do retrato por IA.
-- Executável mais de uma vez.
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS retratos (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo_unico  CHAR(8) NOT NULL,
    idioma        VARCHAR(5) NOT NULL DEFAULT 'pt-BR',
    texto         MEDIUMTEXT NOT NULL,
    modelo        VARCHAR(80) NOT NULL,
    prompt_versao VARCHAR(20) NOT NULL,
    tokens        INT UNSIGNED NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_retratos_codigo (codigo_unico),
    CONSTRAINT fk_retratos_resultado FOREIGN KEY (codigo_unico)
        REFERENCES resultados_usuarios (codigo_unico) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
