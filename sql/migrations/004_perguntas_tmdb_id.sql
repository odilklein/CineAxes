-- =============================================================================
-- PersonaCut — migração 004: filme (TMDB) associado a cada pergunta
--
-- Necessária para automatizar a busca de clipes oficiais no YouTube. O ID fica
-- em uma coluna própria para não se perder quando a mídia for trocada no admin.
--
-- Reexecutável: só cria a coluna/índice se faltarem e só preenche IDs vazios.
-- =============================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

SET @tem_tmdb := (
    SELECT COUNT(*)
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'perguntas'
       AND COLUMN_NAME = 'tmdb_id'
);

SET @sql_tmdb := IF(
    @tem_tmdb = 0,
    'ALTER TABLE perguntas ADD COLUMN tmdb_id INT UNSIGNED NULL AFTER ordem',
    'SELECT ''Coluna tmdb_id já existe.'' AS aviso'
);

PREPARE stmt_tmdb FROM @sql_tmdb;
EXECUTE stmt_tmdb;
DEALLOCATE PREPARE stmt_tmdb;

SET @tem_indice := (
    SELECT COUNT(*)
      FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'perguntas'
       AND INDEX_NAME = 'idx_perguntas_tmdb'
);

SET @sql_indice := IF(
    @tem_indice = 0,
    'ALTER TABLE perguntas ADD KEY idx_perguntas_tmdb (tmdb_id)',
    'SELECT ''Índice idx_perguntas_tmdb já existe.'' AS aviso'
);

PREPARE stmt_indice FROM @sql_indice;
EXECUTE stmt_indice;
DEALLOCATE PREPARE stmt_indice;

-- Backfill a partir do JSON das 32 cenas do seed.
UPDATE perguntas
   SET tmdb_id = CAST(JSON_UNQUOTE(JSON_EXTRACT(midia_meta, '$.tmdb_id')) AS UNSIGNED)
 WHERE tmdb_id IS NULL
   AND midia_meta IS NOT NULL
   AND JSON_EXTRACT(midia_meta, '$.tmdb_id') IS NOT NULL;
