-- =============================================================================
-- PersonaCut — migração 011 (v1): calibração, versão do questionário e base válida
--
-- ATENÇÃO: o modelo v2 usa 6 eixos. Para migrar de v1 para v2, reimporte
-- `sql/001_schema.sql` + `sql/002_seed.sql` (apaga perfis de teste) e mantenha
-- as tabelas do catálogo. Esta migração é apenas para bancos v1 que ainda não
-- têm as colunas de versão/procedência.
--
-- Execute em banco existente (pode rodar mais de uma vez):
--   docker compose exec -T db mysql -upersonacut -ppersonacut123 personacut \
--     < sql/migrations/011_calibracao.sql
--
-- Depois regenere a base sintética:
--   docker compose exec -T app php bin/base_sintetica.php --recriar
--
-- Em instalação nova, `sql/001_schema.sql` já traz as duas colunas; não é
-- preciso importar esta migração.
-- =============================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Versão do questionário que gerou cada vetor.
-- NULL = resultado anterior a este registro (régua desconhecida).
-- -----------------------------------------------------------------------------
SET @existe := (
    SELECT COUNT(*)
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'resultados_usuarios'
       AND COLUMN_NAME = 'questionario_versao'
);

SET @sql := IF(
    @existe = 0,
    'ALTER TABLE resultados_usuarios ADD COLUMN questionario_versao VARCHAR(40) NULL AFTER modo',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- Procedência do vetor de cada entidade.
-- 'editorial' = atribuído à mão (situação atual)
-- 'catalogo'  = derivado das obras do catálogo
-- 'misto'     = combinação dos dois
-- -----------------------------------------------------------------------------
SET @existe := (
    SELECT COUNT(*)
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'entidades_match'
       AND COLUMN_NAME = 'vetor_origem'
);

SET @sql := IF(
    @existe = 0,
    "ALTER TABLE entidades_match ADD COLUMN vetor_origem ENUM('editorial', 'catalogo', 'misto') NOT NULL DEFAULT 'editorial' AFTER eixo_8",
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- Remove a base sintética degenerada.
-- A geração antiga usava RAND(n*1000+k) no MySQL: sementes vizinhas produzem
-- sequências correlacionadas, então as 400 linhas ficavam em duas retas
-- (A,B,A,B,...). O bin/base_sintetica.php grava uma nuvem independente.
-- O EXEMPLO1 é o perfil da landing e permanece.
-- -----------------------------------------------------------------------------
DELETE FROM resultados_usuarios
 WHERE origem = 'sintetico'
   AND codigo_unico <> 'EXEMPLO1'
   AND (questionario_versao IS NULL OR questionario_versao <> 'sintetica-v2');
