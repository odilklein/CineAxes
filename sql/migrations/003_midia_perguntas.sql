-- =============================================================================
-- PersonaCut — migração 003: cenas ilustrativas nas perguntas
--
-- Aplicar SOMENTE em bancos criados antes desta versão. Instalações novas já
-- recebem as colunas e as 32 cenas pelo 001_schema.sql + 002_seed.sql.
--
-- Uso:
--   docker compose exec -T db mysql -upersonacut -ppersonacut123 personacut \
--     < sql/migrations/003_midia_perguntas.sql
-- =============================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Reexecutável: só adiciona as colunas se ainda não existirem.
SET @tem_midia := (
    SELECT COUNT(*)
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'perguntas'
       AND COLUMN_NAME = 'midia_tipo'
);

SET @sql_midia := IF(
    @tem_midia = 0,
    'ALTER TABLE perguntas
        ADD COLUMN midia_tipo      ENUM(''imagem'', ''gif'', ''video_yt'') NULL DEFAULT NULL AFTER ordem,
        ADD COLUMN midia_url_ou_id VARCHAR(255) NULL AFTER midia_tipo,
        ADD COLUMN midia_meta      JSON NULL AFTER midia_url_ou_id',
    'SELECT ''Colunas de mídia já existem; aplicando apenas as cenas.'' AS aviso'
);

PREPARE stmt_midia FROM @sql_midia;
EXECUTE stmt_midia;
DEALLOCATE PREPARE stmt_midia;

-- 32 cenas da TMDB. `midia_url_ou_id` guarda apenas o caminho do backdrop;
-- a URL final é montada no PHP com o tamanho adequado (w780).
-- troque por GIF (GIPHY/Tenor) ou ID do YouTube quando fizer sentido.

UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/87wqeMeUeMRLjsE1IWNKJcPlpuC.jpg', midia_meta='{"legenda":"Casablanca (1942)","ano":1942,"tmdb_id":289,"fonte":"tmdb"}' WHERE texto_key='pergunta.estetica.01';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/fjk4eE8Ypke4YLv5A3ArDH4GzYH.jpg', midia_meta='{"legenda":"Manchester à Beira-Mar (2016)","ano":2016,"tmdb_id":334541,"fonte":"tmdb"}' WHERE texto_key='pergunta.tom.01';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/uvitbjFU4JqvMwIkMWHp69bmUzG.jpg', midia_meta='{"legenda":"Cidade de Deus (2002)","ano":2002,"tmdb_id":598,"fonte":"tmdb"}' WHERE texto_key='pergunta.universo.01';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/pAKpSMrCv9HQdMKvIdLgTdpKh5f.jpg', midia_meta='{"legenda":"Paris, Texas (1984)","ano":1984,"tmdb_id":655,"fonte":"tmdb"}' WHERE texto_key='pergunta.ritmo.01';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/39rtAdg3yx6IDxRg0ZLXefv0aPY.jpg', midia_meta='{"legenda":"O Poderoso Chefão (1972)","ano":1972,"tmdb_id":238,"fonte":"tmdb"}' WHERE texto_key='pergunta.moralidade.01';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/66Kn4XWhkuPkJxOJyPEx4U2CUfN.jpg', midia_meta='{"legenda":"Forrest Gump: O Contador de Histórias (1994)","ano":1994,"tmdb_id":13,"fonte":"tmdb"}' WHERE texto_key='pergunta.estrutura.01';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/qqHQsStV6exghCM7zbObuYBiYxw.jpg', midia_meta='{"legenda":"12 Homens e uma Sentença (1957)","ano":1957,"tmdb_id":389,"fonte":"tmdb"}' WHERE texto_key='pergunta.atmosfera.01';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/zoVeIgKzGJzpdG6Gwnr7iOYfIMU.jpg', midia_meta='{"legenda":"Cinema Paradiso (1988)","ano":1988,"tmdb_id":11216,"fonte":"tmdb"}' WHERE texto_key='pergunta.epoca.01';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/gNdLJU9TxrpGx4dkZidjys3fyy0.jpg', midia_meta='{"legenda":"Blade Runner 2049 (2017)","ano":2017,"tmdb_id":335984,"fonte":"tmdb"}' WHERE texto_key='pergunta.estetica.02';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/jK65srQczOKTpW62wPxwwKztGgE.jpg', midia_meta='{"legenda":"O Grande Hotel Budapeste (2014)","ano":2014,"tmdb_id":120467,"fonte":"tmdb"}' WHERE texto_key='pergunta.tom.02';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/oiwc338EoBgS4sEI2ixAny4KQKg.jpg', midia_meta='{"legenda":"O Senhor dos Anéis: A Sociedade do Anel (2001)","ano":2001,"tmdb_id":120,"fonte":"tmdb"}' WHERE texto_key='pergunta.universo.02';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/gqrnQA6Xppdl8vIb2eJc58VC1tW.jpg', midia_meta='{"legenda":"Mad Max: Estrada da Fúria (2015)","ano":2015,"tmdb_id":76341,"fonte":"tmdb"}' WHERE texto_key='pergunta.ritmo.02';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/c7Mjuip0jfHLY7x8ZSEriRj45cu.jpg', midia_meta='{"legenda":"Indiana Jones e os Caçadores da Arca Perdida (1981)","ano":1981,"tmdb_id":85,"fonte":"tmdb"}' WHERE texto_key='pergunta.moralidade.02';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/suaEOtk1N1sgg2MTM7oZd2cfVp3.jpg', midia_meta='{"legenda":"Pulp Fiction: Tempo de Violência (1994)","ano":1994,"tmdb_id":680,"fonte":"tmdb"}' WHERE texto_key='pergunta.estrutura.02';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/6wmemKdbkOIaWfzz607K21R6qOh.jpg', midia_meta='{"legenda":"Lawrence da Arábia (1962)","ano":1962,"tmdb_id":947,"fonte":"tmdb"}' WHERE texto_key='pergunta.atmosfera.02';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/8K9qHeM6G6QjQN0C5XKFGvK5lzM.jpg', midia_meta='{"legenda":"Matrix (1999)","ano":1999,"tmdb_id":603,"fonte":"tmdb"}' WHERE texto_key='pergunta.epoca.02';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/8Y1S9BtmTOh86irwFo7CJ01YPXF.jpg', midia_meta='{"legenda":"Filhos da Esperança (2006)","ano":2006,"tmdb_id":9693,"fonte":"tmdb"}' WHERE texto_key='pergunta.estetica.03';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/ffQFnAUm2Uu4RU0nijpjPRf9TBT.jpg', midia_meta='{"legenda":"Amor à Flor da Pele (2000)","ano":2000,"tmdb_id":843,"fonte":"tmdb"}' WHERE texto_key='pergunta.tom.03';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/zl9uqCl5iUSb50sTk2BPzw6bJnU.jpg', midia_meta='{"legenda":"Roma (2018)","ano":2018,"tmdb_id":426426,"fonte":"tmdb"}' WHERE texto_key='pergunta.universo.03';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/6yrbWzzrPp7pwz6zHdifspJk8t3.jpg', midia_meta='{"legenda":"Stalker (1979)","ano":1979,"tmdb_id":1398,"fonte":"tmdb"}' WHERE texto_key='pergunta.ritmo.03';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/fCCvn3xsG29TGKJPvZgTRppsDgk.jpg', midia_meta='{"legenda":"Onde os Fracos Não Têm Vez (2007)","ano":2007,"tmdb_id":6977,"fonte":"tmdb"}' WHERE texto_key='pergunta.moralidade.03';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/mXLVA0YL6tcXi6SJSuAh9ONXFj5.jpg', midia_meta='{"legenda":"E.T.: O Extraterrestre (1982)","ano":1982,"tmdb_id":601,"fonte":"tmdb"}' WHERE texto_key='pergunta.estrutura.03';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/qA2TyqPldTtoTVY3LKrNIG5g6bH.jpg', midia_meta='{"legenda":"Antes do Amanhecer (1995)","ano":1995,"tmdb_id":76,"fonte":"tmdb"}' WHERE texto_key='pergunta.atmosfera.03';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/ef6SvWbSfCFlKodJZqpIaTvsRRP.jpg', midia_meta='{"legenda":"Barry Lyndon (1975)","ano":1975,"tmdb_id":3175,"fonte":"tmdb"}' WHERE texto_key='pergunta.epoca.03';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/95qi5TJMIBI5VzWHia88Pbrye7Z.jpg', midia_meta='{"legenda":"Brilho Eterno de uma Mente sem Lembranças (2004)","ano":2004,"tmdb_id":38,"fonte":"tmdb"}' WHERE texto_key='pergunta.estetica.04';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/dbdegCWeD4iK5LXxSBjGdIRsVXS.jpg', midia_meta='{"legenda":"Harry & Sally: Feitos um para o Outro (1989)","ano":1989,"tmdb_id":639,"fonte":"tmdb"}' WHERE texto_key='pergunta.tom.04';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/4qCqAdHcNKeAHcK8tJ8wNJZa9cx.jpg', midia_meta='{"legenda":"Guerra nas Estrelas (1977)","ano":1977,"tmdb_id":11,"fonte":"tmdb"}' WHERE texto_key='pergunta.universo.04';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/5JaR6UEoCJJLtLpqFOVMY4O4R7P.jpg', midia_meta='{"legenda":"Fogo Contra Fogo (1995)","ano":1995,"tmdb_id":949,"fonte":"tmdb"}' WHERE texto_key='pergunta.ritmo.04';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/ctiw6FZK4N36LmkjSklWEbuvlq9.jpg', midia_meta='{"legenda":"O Senhor dos Anéis: O Retorno do Rei (2003)","ano":2003,"tmdb_id":122,"fonte":"tmdb"}' WHERE texto_key='pergunta.moralidade.04';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/8ZTVqvKDQ8emSGUEMjsS4yHAwrp.jpg', midia_meta='{"legenda":"A Origem (2010)","ano":2010,"tmdb_id":27205,"fonte":"tmdb"}' WHERE texto_key='pergunta.estrutura.04';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/8sNiAPPYU14PUepFNeSNGUTiHW.jpg', midia_meta='{"legenda":"Interestelar (2014)","ano":2014,"tmdb_id":157336,"fonte":"tmdb"}' WHERE texto_key='pergunta.atmosfera.04';
UPDATE perguntas SET midia_tipo='imagem', midia_url_ou_id='/1YnZchmaGc8dchgRPDpR1KGrixA.jpg', midia_meta='{"legenda":"Ela (2013)","ano":2013,"tmdb_id":152601,"fonte":"tmdb"}' WHERE texto_key='pergunta.epoca.04';
