-- Migração para bancos criados antes do modo extremo (240 perguntas).
-- Executável mais de uma vez.
ALTER TABLE resultados_usuarios
    MODIFY modo ENUM('rapido', 'completo', 'extremo') NOT NULL DEFAULT 'completo';
