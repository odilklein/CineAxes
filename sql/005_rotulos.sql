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
