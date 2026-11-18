-- =============================================================================
-- 008 — Frase do perfil
--
-- A mesma geração do retrato também devolve uma frase curta (uma espécie de
-- tagline do jeito de assistir). Ela fica na mesma linha do retrato para não
-- criar outra tabela e é exibida no card do perfil e na retrospectiva.
-- =============================================================================

ALTER TABLE retratos
    ADD COLUMN frase VARCHAR(280) NULL AFTER texto;
