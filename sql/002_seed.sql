-- =============================================================================
-- PersonaCut — dados iniciais (pt-BR)
-- Executado automaticamente pelo MySQL na primeira subida do Docker
-- (ordem alfabética: 001_schema.sql, 002_seed.sql, 003...).
-- =============================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- -----------------------------------------------------------------------------
-- 1. Eixos
-- Gêneros = IDs oficiais da TMDB (usados em discover/with_genres).
-- Keywords = nomes (o TmdbService resolve para IDs na v1.5).
-- -----------------------------------------------------------------------------
INSERT INTO eixos
    (id, ordem, slug, nome, descricao, polo_esq_key, polo_esq_nome, polo_dir_key, polo_dir_nome,
     cor_esq, cor_dir, icone_esq, icone_dir, tmdb_genres_esq, tmdb_genres_dir,
     tmdb_keywords_esq, tmdb_keywords_dir)
VALUES
    (1, 1, 'estetica', 'Forma', 'A linguagem visual: transparência clássica, decupagem invisível e continuidade contra ruptura formal, textura e auto-reflexão.',
     'eixo.estetica.esq', 'Clássica', 'eixo.estetica.dir', 'Experimental', '#8C5A2B', '#4F6480',
     'frame',    'sparkles', '18,36,10749', '14,16,9648',
     'cinema clássico,preto e branco,ópera', 'surrealismo,sonho,expressionismo'),

    (2, 2, 'tom', 'Tom', 'O registro emocional: peso, silêncio e catarse triste contra leveza, humor e saída em estado de festa.',
     'eixo.tom.esq', 'Melancólico', 'eixo.tom.dir', 'Eufórico', '#445C7A', '#C08A2E',
     'moon',     'sun',      '18,10749,10752', '35,12,10402',
     'melancolia,luto,solidão', 'amizade,festa,verão'),

    (3, 3, 'universo', 'Realismo', 'O grau de invenção do mundo: ruas reconhecíveis e gente real contra cosmologias, regras próprias e mundos impossíveis.',
     'eixo.universo.esq', 'Realista', 'eixo.universo.dir', 'Fantástico', '#5C7048', '#7A4A63',
     'building', 'planet',   '99,80,36', '878,14,16',
     'baseado em fatos reais,periferia', 'mundo distópico,viagem espacial,criaturas'),

    (4, 4, 'ritmo', 'Tempo', 'A experiência da duração: planos que respiram e temps morts contra corte rápido, perseguição e adrenalina.',
     'eixo.ritmo.esq', 'Contemplativo', 'eixo.ritmo.dir', 'Frenético', '#3F6B6B', '#B0553C',
     'feather',  'bolt',     '18,37,99', '28,53,12',
     'slow cinema,contemplação', 'perseguição,adrenalina,explosão'),

    (5, 5, 'moralidade', 'Moral', 'A posição ética: zona cinzenta em que ninguém é inocente contra bem e mal bem demarcados.',
     'eixo.moralidade.esq', 'Ambígua', 'eixo.moralidade.dir', 'Maniqueísta', '#6B5B7B', '#A8792C',
     'scale',    'shield',   '80,53,9648', '10751,12,14',
     'anti-herói,dilema moral,corrupção', 'heroísmo,bem contra o mal,fábula'),

    (6, 6, 'estrutura', 'Narrativa', 'A arquitetura da história: causalidade linear e arco fechado contra quebra de tempo, camadas e realidades paralelas.',
     'eixo.estrutura.esq', 'Linear', 'eixo.estrutura.dir', 'Fragmentada', '#3E6A66', '#9E5560',
     'list',     'layers',   '18,37,36', '9648,878,53',
     'narrativa linear,jornada', 'linha do tempo não linear,reviravolta,multiverso'),

    (7, 7, 'atmosfera', 'Escala', 'A medida do drama: quartos, closes e poucos personagens contra exércitos, planetas e tela grande.',
     'eixo.atmosfera.esq', 'Íntima', 'eixo.atmosfera.dir', 'Épica', '#8A5F6B', '#4A6B85',
     'home',     'mountain', '18,10749,10402', '12,10752,878',
     'filme de câmara,claustrofobia,família', 'épico,batalha,paisagem'),

    (8, 8, 'epoca', 'Temporalidade', 'A orientação no tempo: memória, grão e passado contra neon, código e imaginação do amanhã.',
     'eixo.epoca.esq', 'Memória', 'eixo.epoca.dir', 'Futuro', '#8A6A45', '#5E7F6A',
     'film',     'rocket',   '36,37,10749', '878,28,14',
     'filme de época,anos dourados,memória', 'futuro,cyberpunk,tecnologia');

-- -----------------------------------------------------------------------------
-- 2. Perguntas — 240 no total, 30 por eixo.
-- A ordem é round-robin entre os eixos (a cada rodada, uma pergunta de cada eixo),
-- então qualquer prefixo já é um teste equilibrado:
--   ordem 1..32   → 4 por eixo, curadas, com cena do TMDB (2 primeiras de cada eixo com peso 2)
--   ordem 33..240 → 26 por eixo, texto curado, sem mídia
-- Modos: rápido = 36, completo = 60, extremo = 240 (ver Pergunta::paraModo).
-- -----------------------------------------------------------------------------
INSERT INTO perguntas
    (eixo_id, texto_key, texto, direcao, peso, modo_rapido, ordem, tmdb_id,
     midia_tipo, midia_url_ou_id, midia_meta)
VALUES
    (1, 'pergunta.estetica.01', 'Prefiro um filme com fotografia limpa e enquadramento clássico a um que quebra todas as regras da imagem.', -1, 2, 1, 1, 289,
     'imagem', '/87wqeMeUeMRLjsE1IWNKJcPlpuC.jpg',
     '{"legenda":"Casablanca (1942)","ano":1942,"tmdb_id":289,"fonte":"tmdb"}'),
    (2, 'pergunta.tom.01', 'Os filmes que mais me marcam são os que me deixam com um nó na garganta.', -1, 2, 1, 2, 334541,
     'imagem', '/fjk4eE8Ypke4YLv5A3ArDH4GzYH.jpg',
     '{"legenda":"Manchester à Beira-Mar (2016)","ano":2016,"tmdb_id":334541,"fonte":"tmdb"}'),
    (3, 'pergunta.universo.01', 'Histórias que poderiam acontecer comigo hoje me prendem mais do que mundos inventados.', -1, 2, 1, 3, 598,
     'imagem', '/uvitbjFU4JqvMwIkMWHp69bmUzG.jpg',
     '{"legenda":"Cidade de Deus (2002)","ano":2002,"tmdb_id":598,"fonte":"tmdb"}'),
    (4, 'pergunta.ritmo.01', 'Não me incomodo com um filme que respira devagar; até gosto dos silêncios.', -1, 2, 1, 4, 655,
     'imagem', '/pAKpSMrCv9HQdMKvIdLgTdpKh5f.jpg',
     '{"legenda":"Paris, Texas (1984)","ano":1984,"tmdb_id":655,"fonte":"tmdb"}'),
    (5, 'pergunta.moralidade.01', 'Os melhores filmes são aqueles em que ninguém é totalmente bom nem totalmente mau.', -1, 2, 1, 5, 238,
     'imagem', '/39rtAdg3yx6IDxRg0ZLXefv0aPY.jpg',
     '{"legenda":"O Poderoso Chefão (1972)","ano":1972,"tmdb_id":238,"fonte":"tmdb"}'),
    (6, 'pergunta.estrutura.01', 'Prefiro uma história contada do começo ao fim, na ordem certa.', -1, 2, 1, 6, 13,
     'imagem', '/66Kn4XWhkuPkJxOJyPEx4U2CUfN.jpg',
     '{"legenda":"Forrest Gump: O Contador de Histórias (1994)","ano":1994,"tmdb_id":13,"fonte":"tmdb"}'),
    (7, 'pergunta.atmosfera.01', 'Meus filmes favoritos se passam em poucos lugares, com poucos personagens.', -1, 2, 1, 7, 389,
     'imagem', '/qqHQsStV6exghCM7zbObuYBiYxw.jpg',
     '{"legenda":"12 Homens e uma Sentença (1957)","ano":1957,"tmdb_id":389,"fonte":"tmdb"}'),
    (8, 'pergunta.epoca.01', 'Sinto falta da textura do cinema de antigamente: grão, luz de tungstênio, charme.', -1, 2, 1, 8, 11216,
     'imagem', '/zoVeIgKzGJzpdG6Gwnr7iOYfIMU.jpg',
     '{"legenda":"Cinema Paradiso (1988)","ano":1988,"tmdb_id":11216,"fonte":"tmdb"}'),
    (1, 'pergunta.estetica.02', 'Curto quando um filme inventa uma linguagem visual própria, mesmo que eu estranhe no começo.', 1, 2, 1, 9, 335984,
     'imagem', '/gNdLJU9TxrpGx4dkZidjys3fyy0.jpg',
     '{"legenda":"Blade Runner 2049 (2017)","ano":2017,"tmdb_id":335984,"fonte":"tmdb"}'),
    (2, 'pergunta.tom.02', 'Prefiro sair do cinema leve, rindo e com vontade de viver.', 1, 2, 1, 10, 120467,
     'imagem', '/jK65srQczOKTpW62wPxwwKztGgE.jpg',
     '{"legenda":"O Grande Hotel Budapeste (2014)","ano":2014,"tmdb_id":120467,"fonte":"tmdb"}'),
    (3, 'pergunta.universo.02', 'Quanto mais o filme cria um universo próprio, com regras próprias, mais eu me entrego.', 1, 2, 1, 11, 120,
     'imagem', '/oiwc338EoBgS4sEI2ixAny4KQKg.jpg',
     '{"legenda":"O Senhor dos Anéis: A Sociedade do Anel (2001)","ano":2001,"tmdb_id":120,"fonte":"tmdb"}'),
    (4, 'pergunta.ritmo.02', 'Se o filme para por cinco minutos, eu já estou olhando o celular.', 1, 2, 1, 12, 76341,
     'imagem', '/gqrnQA6Xppdl8vIb2eJc58VC1tW.jpg',
     '{"legenda":"Mad Max: Estrada da Fúria (2015)","ano":2015,"tmdb_id":76341,"fonte":"tmdb"}'),
    (5, 'pergunta.moralidade.02', 'Gosto de histórias em que o herói é claramente o herói e o vilão é claramente o vilão.', 1, 2, 1, 13, 85,
     'imagem', '/c7Mjuip0jfHLY7x8ZSEriRj45cu.jpg',
     '{"legenda":"Indiana Jones e os Caçadores da Arca Perdida (1981)","ano":1981,"tmdb_id":85,"fonte":"tmdb"}'),
    (6, 'pergunta.estrutura.02', 'Adoro quando o filme embaralha o tempo e me obriga a montar as peças.', 1, 2, 1, 14, 680,
     'imagem', '/suaEOtk1N1sgg2MTM7oZd2cfVp3.jpg',
     '{"legenda":"Pulp Fiction: Tempo de Violência (1994)","ano":1994,"tmdb_id":680,"fonte":"tmdb"}'),
    (7, 'pergunta.atmosfera.02', 'Quero escala: exércitos, planetas, multidões e uma tela bem grande.', 1, 2, 1, 15, 947,
     'imagem', '/6wmemKdbkOIaWfzz607K21R6qOh.jpg',
     '{"legenda":"Lawrence da Arábia (1962)","ano":1962,"tmdb_id":947,"fonte":"tmdb"}'),
    (8, 'pergunta.epoca.02', 'Me interessa mais o cinema que imagina o amanhã do que o que revisita o passado.', 1, 2, 1, 16, 603,
     'imagem', '/8K9qHeM6G6QjQN0C5XKFGvK5lzM.jpg',
     '{"legenda":"Matrix (1999)","ano":1999,"tmdb_id":603,"fonte":"tmdb"}'),
    (1, 'pergunta.estetica.03', 'Um bom plano-sequência sem cortes me impressiona mais do que qualquer efeito digital.', -1, 1, 0, 17, 9693,
     'imagem', '/8Y1S9BtmTOh86irwFo7CJ01YPXF.jpg',
     '{"legenda":"Filhos da Esperança (2006)","ano":2006,"tmdb_id":9693,"fonte":"tmdb"}'),
    (2, 'pergunta.tom.03', 'Trilha melancólica e final aberto é a minha definição de boa sessão.', -1, 1, 0, 18, 843,
     'imagem', '/ffQFnAUm2Uu4RU0nijpjPRf9TBT.jpg',
     '{"legenda":"Amor à Flor da Pele (2000)","ano":2000,"tmdb_id":843,"fonte":"tmdb"}'),
    (3, 'pergunta.universo.03', 'Gosto de filmes que parecem um documentário, com cheiro de rua e gente real.', -1, 1, 0, 19, 426426,
     'imagem', '/zl9uqCl5iUSb50sTk2BPzw6bJnU.jpg',
     '{"legenda":"Roma (2018)","ano":2018,"tmdb_id":426426,"fonte":"tmdb"}'),
    (4, 'pergunta.ritmo.03', 'Prefiro cenas longas, com tempo para reparar nos detalhes.', -1, 1, 0, 20, 1398,
     'imagem', '/6yrbWzzrPp7pwz6zHdifspJk8t3.jpg',
     '{"legenda":"Stalker (1979)","ano":1979,"tmdb_id":1398,"fonte":"tmdb"}'),
    (5, 'pergunta.moralidade.03', 'Um final moralmente ambíguo, que me deixa sem lado para torcer, me agrada.', -1, 1, 0, 21, 6977,
     'imagem', '/fCCvn3xsG29TGKJPvZgTRppsDgk.jpg',
     '{"legenda":"Onde os Fracos Não Têm Vez (2007)","ano":2007,"tmdb_id":6977,"fonte":"tmdb"}'),
    (6, 'pergunta.estrutura.03', 'Flashbacks e realidades paralelas costumam me tirar do filme.', -1, 1, 0, 22, 601,
     'imagem', '/mXLVA0YL6tcXi6SJSuAh9ONXFj5.jpg',
     '{"legenda":"E.T.: O Extraterrestre (1982)","ano":1982,"tmdb_id":601,"fonte":"tmdb"}'),
    (7, 'pergunta.atmosfera.03', 'Um quarto, duas pessoas e um segredo mal resolvido já bastam para mim.', -1, 1, 0, 23, 76,
     'imagem', '/qA2TyqPldTtoTVY3LKrNIG5g6bH.jpg',
     '{"legenda":"Antes do Amanhecer (1995)","ano":1995,"tmdb_id":76,"fonte":"tmdb"}'),
    (8, 'pergunta.epoca.03', 'Um figurino de época e uma trilha de orquestra me conquistam de imediato.', -1, 1, 0, 24, 3175,
     'imagem', '/ef6SvWbSfCFlKodJZqpIaTvsRRP.jpg',
     '{"legenda":"Barry Lyndon (1975)","ano":1975,"tmdb_id":3175,"fonte":"tmdb"}'),
    (1, 'pergunta.estetica.04', 'Filmes com estética de sonho, cores impossíveis e montagem livre me conquistam mais fácil.', 1, 1, 0, 25, 38,
     'imagem', '/95qi5TJMIBI5VzWHia88Pbrye7Z.jpg',
     '{"legenda":"Brilho Eterno de uma Mente sem Lembranças (2004)","ano":2004,"tmdb_id":38,"fonte":"tmdb"}'),
    (2, 'pergunta.tom.04', 'Uma comédia bem escrita faz mais por mim do que um drama pesado.', 1, 1, 0, 26, 639,
     'imagem', '/dbdegCWeD4iK5LXxSBjGdIRsVXS.jpg',
     '{"legenda":"Harry & Sally: Feitos um para o Outro (1989)","ano":1989,"tmdb_id":639,"fonte":"tmdb"}'),
    (3, 'pergunta.universo.04', 'Ficção científica e fantasia são as minhas portas de entrada para o cinema.', 1, 1, 0, 27, 11,
     'imagem', '/4qCqAdHcNKeAHcK8tJ8wNJZa9cx.jpg',
     '{"legenda":"Guerra nas Estrelas (1977)","ano":1977,"tmdb_id":11,"fonte":"tmdb"}'),
    (4, 'pergunta.ritmo.04', 'Adrenalina, perseguição e corte rápido são o que me mantêm acordado.', 1, 1, 0, 28, 949,
     'imagem', '/5JaR6UEoCJJLtLpqFOVMY4O4R7P.jpg',
     '{"legenda":"Fogo Contra Fogo (1995)","ano":1995,"tmdb_id":949,"fonte":"tmdb"}'),
    (5, 'pergunta.moralidade.04', 'Ver o bem vencer no final ainda me dá satisfação de verdade.', 1, 1, 0, 29, 122,
     'imagem', '/ctiw6FZK4N36LmkjSklWEbuvlq9.jpg',
     '{"legenda":"O Senhor dos Anéis: O Retorno do Rei (2003)","ano":2003,"tmdb_id":122,"fonte":"tmdb"}'),
    (6, 'pergunta.estrutura.04', 'Um roteiro com camadas, pistas e reviravoltas estruturais me diverte mais que uma narrativa reta.', 1, 1, 0, 30, 27205,
     'imagem', '/8ZTVqvKDQ8emSGUEMjsS4yHAwrp.jpg',
     '{"legenda":"A Origem (2010)","ano":2010,"tmdb_id":27205,"fonte":"tmdb"}'),
    (7, 'pergunta.atmosfera.04', 'Final épico com trilha orquestral me arrepia mais do que qualquer cena intimista.', 1, 1, 0, 31, 157336,
     'imagem', '/8sNiAPPYU14PUepFNeSNGUTiHW.jpg',
     '{"legenda":"Interestelar (2014)","ano":2014,"tmdb_id":157336,"fonte":"tmdb"}'),
    (8, 'pergunta.epoca.04', 'Telas, códigos, inteligência artificial e cidades neon são o meu tipo de cenário.', 1, 1, 0, 32, 152601,
     'imagem', '/1YnZchmaGc8dchgRPDpR1KGrixA.jpg',
     '{"legenda":"Ela (2013)","ano":2013,"tmdb_id":152601,"fonte":"tmdb"}');

INSERT INTO perguntas
    (eixo_id, texto_key, texto, direcao, peso, modo_rapido, ordem, tmdb_id,
     midia_tipo, midia_url_ou_id, midia_meta)
VALUES
    (1, 'pergunta.estetica.05', 'Prefiro enquadramentos estáveis e simétricos, que me deixem olhar a ação sem distração.', -1, 1, 0, 33, NULL,
     NULL, NULL, NULL),
    (1, 'pergunta.estetica.06', 'Gosto de enquadramentos assimétricos que criem tensão visual e inquietação.', 1, 1, 0, 41, NULL,
     NULL, NULL, NULL),
    (1, 'pergunta.estetica.07', 'Detesto câmera tremida: parece realismo barato e me cansa em cinco minutos.', -1, 1, 0, 49, NULL,
     NULL, NULL, NULL),
    (1, 'pergunta.estetica.08', 'Detesto a previsibilidade de composições clássicas que não desafiam meu olhar.', 1, 1, 0, 57, NULL,
     NULL, NULL, NULL),
    (1, 'pergunta.estetica.09', 'Gosto de luz natural e suave, que imita a realidade sem exageros artificiais.', -1, 1, 0, 65, NULL,
     NULL, NULL, NULL),
    (1, 'pergunta.estetica.10', 'Prefiro luzes duras, sombras marcadas e contrastes que distorçam a realidade.', 1, 1, 0, 73, NULL,
     NULL, NULL, NULL),
    (1, 'pergunta.estetica.11', 'A decupagem invisível, que eu nem percebo, é o sinal de um trabalho técnico impecável.', -1, 1, 0, 81, NULL,
     NULL, NULL, NULL),
    (1, 'pergunta.estetica.12', 'A decupagem fragmentada me faz reconstruir o sentido, e isso me instiga.', 1, 1, 0, 89, NULL,
     NULL, NULL, NULL),
    (1, 'pergunta.estetica.13', 'Prefiro paletas de cores neutras e realistas a explosões cromáticas saturadas.', -1, 1, 0, 97, NULL,
     NULL, NULL, NULL),
    (1, 'pergunta.estetica.14', 'Amo quando as cores se tornam abstratas e perdem referência ao mundo real.', 1, 1, 0, 105, NULL,
     NULL, NULL, NULL),
    (1, 'pergunta.estetica.15', 'Composições equilibradas me dão conforto visual imediato, quadro após quadro.', -1, 1, 0, 113, NULL,
     NULL, NULL, NULL),
    (1, 'pergunta.estetica.16', 'Composições desequilibradas e instáveis me parecem mais honestas e contemporâneas.', 1, 1, 0, 121, NULL,
     NULL, NULL, NULL),
    (1, 'pergunta.estetica.17', 'Prefiro a luz que desenha o rosto sem truque a qualquer efeito de pós-produção.', -1, 1, 0, 129, NULL,
     NULL, NULL, NULL),
    (1, 'pergunta.estetica.18', 'Gosto de luz teatral, quase de palco, que reinventa o rosto do ator.', 1, 1, 0, 137, NULL,
     NULL, NULL, NULL),
    (1, 'pergunta.estetica.19', 'Prefiro planos médios e gerais que situem o espaço antes de mostrarem detalhes.', -1, 1, 0, 145, NULL,
     NULL, NULL, NULL),
    (1, 'pergunta.estetica.20', 'Gosto de cores que parecem pintadas à mão, mesmo quando o filme é digital.', 1, 1, 0, 153, NULL,
     NULL, NULL, NULL),
    (1, 'pergunta.estetica.21', 'Texturas nítidas e definidas são essenciais para eu apreciar a qualidade da imagem.', -1, 1, 0, 161, NULL,
     NULL, NULL, NULL),
    (1, 'pergunta.estetica.22', 'Prefiro a imagem rasgada, com sobreposições e falhas, à nitidez impecável.', 1, 1, 0, 169, NULL,
     NULL, NULL, NULL),
    (1, 'pergunta.estetica.23', 'Defendo o corte que ninguém nota: se eu percebo a montagem, ela falhou.', -1, 1, 0, 177, NULL,
     NULL, NULL, NULL),
    (1, 'pergunta.estetica.24', 'Gosto quando o enquadramento esconde parte do rosto e me obriga a completar.', 1, 1, 0, 185, NULL,
     NULL, NULL, NULL),
    (1, 'pergunta.estetica.25', 'A continuidade espacial clara e lógica é inegociável para eu entender o filme.', -1, 1, 0, 193, NULL,
     NULL, NULL, NULL),
    (1, 'pergunta.estetica.26', 'A quebra da continuidade espacial me permite explorar novas geografias mentais.', 1, 1, 0, 201, NULL,
     NULL, NULL, NULL),
    (1, 'pergunta.estetica.27', 'Gosto de enquadramentos que respeitam as linhas do horizonte e a regra dos terços.', -1, 1, 0, 209, NULL,
     NULL, NULL, NULL),
    (1, 'pergunta.estetica.28', 'Quero a câmera dentro do espaço do personagem, mesmo que isso me incomode.', 1, 1, 0, 217, NULL,
     NULL, NULL, NULL),
    (1, 'pergunta.estetica.29', 'Gosto de filmes em que a beleza do plano serve à cena, não ao ego do diretor.', -1, 1, 0, 225, NULL,
     NULL, NULL, NULL),
    (1, 'pergunta.estetica.30', 'Cor impossível, fora de qualquer paleta realista, é o que me prende na tela.', 1, 1, 0, 233, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.05', 'Gosto quando o filme me deixa quieto, pensando, em vez de me animar.', -1, 1, 0, 34, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.06', 'Rir alto no meio da sessão é a melhor sensação que o cinema me dá.', 1, 1, 0, 42, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.07', 'Choro no escuro sem vergonha nenhuma, e é por isso que eu volto.', -1, 1, 0, 50, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.08', 'Gosto de sair do cinema com a alma leve e vontade de fazer planos.', 1, 1, 0, 58, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.09', 'A tristeza bem contada me abraça mais do que qualquer final feliz.', -1, 1, 0, 66, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.10', 'Humor afiado me deixa esperto e alerta, do jeito que eu gosto.', 1, 1, 0, 74, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.11', 'Procuro filmes que me deixem com um vazio bom, desses que duram dias.', -1, 1, 0, 82, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.12', 'Adoro final que explode em festa, música e todo mundo dançando.', 1, 1, 0, 90, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.13', 'Prefiro o riso nervoso, que vem depois do desconforto, à gargalhada fácil.', -1, 1, 0, 98, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.14', 'A euforia da plateia me contagia e melhora qualquer filme.', 1, 1, 0, 106, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.15', 'A melancolia é o tempero que faz o resto do filme valer a pena.', -1, 1, 0, 114, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.16', 'Prefiro o riso fácil e generoso ao humor que exige sofrimento antes.', 1, 1, 0, 122, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.17', 'Sinto prazer quando a história termina em perda e não em recompensa.', -1, 1, 0, 130, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.18', 'Sinto afeto imediato por roteiros cheios de luz e gente gentil.', 1, 1, 0, 138, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.19', 'Filmes que me fazem chorar são os que eu recomendo com mais convicção.', -1, 1, 0, 146, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.20', 'Gosto de sair do cinema com energia renovada, pronto para a semana.', 1, 1, 0, 154, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.21', 'Gosto do silêncio final, quando ninguém sabe direito o que dizer.', -1, 1, 0, 162, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.22', 'O abraço coletivo no fim me ganha, mesmo quando é piegas.', 1, 1, 0, 170, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.23', 'Prefiro a emoção contida, que aperta o peito, ao entusiasmo escancarado.', -1, 1, 0, 178, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.24', 'Rir sem esforço é o meu jeito preferido de passar duas horas.', 1, 1, 0, 186, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.25', 'Uma cena de despedida bem filmada vale a sessão inteira para mim.', -1, 1, 0, 194, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.26', 'Prefiro a comédia que celebra a vida à que só aponta o que está errado.', 1, 1, 0, 202, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.27', 'Fico com aquele peso no peito depois do filme, e considero isso um elogio.', -1, 1, 0, 210, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.28', 'Filme bom, para mim, é aquele que me devolve o ânimo.', 1, 1, 0, 218, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.29', 'Prefiro um final amargo e honesto a uma alegria que não foi conquistada.', -1, 1, 0, 226, NULL,
     NULL, NULL, NULL),
    (2, 'pergunta.tom.30', 'Gosto quando a sala inteira sai sorrindo, e eu saio junto.', 1, 1, 0, 234, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.05', 'Prefiro rostos cansados de gente comum a criaturas mágicas irreais.', -1, 1, 0, 35, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.06', 'Um dragão voando sobre cidades me empolga mais do que um trânsito lotado.', 1, 1, 0, 43, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.07', 'O cotidiano banal me prende quando mostrado com fidelidade brutal.', -1, 1, 0, 51, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.08', 'Mundos impossíveis com lógica interna me prendem mais que a realidade.', 1, 1, 0, 59, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.09', 'Gosto de ver pessoas reais falando sobre problemas reais.', -1, 1, 0, 67, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.10', 'Fantasias épicas me deixam escapar da rotina por duas horas.', 1, 1, 0, 75, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.11', 'A verossimilhança dos detalhes do dia a dia é o que me vende a história.', -1, 1, 0, 83, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.12', 'Prefiro histórias com criaturas e deuses a histórias de divórcio e contas.', 1, 1, 0, 91, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.13', 'Prefiro filmes que parecem documentários sobre a vida cotidiana.', -1, 1, 0, 99, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.14', 'Quero mapa, mitologia e cronologia: gosto de universos que exigem estudo.', 1, 1, 0, 107, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.15', 'Ruas reconhecíveis e diálogo natural são meu padrão de ouro.', -1, 1, 0, 115, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.16', 'Quero explorar galáxias distantes, não apenas o meu bairro.', 1, 1, 0, 123, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.17', 'A realidade crua e sem filtro me comove mais que a ficção.', -1, 1, 0, 131, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.18', 'Mundos fantásticos oferecem uma liberdade criativa que a realidade não tem.', 1, 1, 0, 139, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.19', 'Gosto de histórias ancoradas em contextos sociais e geográficos verdadeiros.', -1, 1, 0, 147, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.20', 'A estranheza de universos paralelos me atrai mais que o familiar.', 1, 1, 0, 155, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.21', 'Prefiro a honestidade de um retrato social realista.', -1, 1, 0, 163, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.22', 'A magia e o sobrenatural me permitem sonhar além do possível.', 1, 1, 0, 171, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.23', 'Detalhes sensoriais do mundo real criam a minha conexão emocional.', -1, 1, 0, 179, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.24', 'Sistemas de magia complexos me intrigam mais que a vida real.', 1, 1, 0, 187, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.25', 'A simplicidade de um enredo cotidiano me basta para sentir algo.', -1, 1, 0, 195, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.26', 'A grandiosidade de civilizações alienígenas me faz perder a noção do tempo.', 1, 1, 0, 203, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.27', 'Prefiro o peso da existência humana a qualquer artefato tecnológico.', -1, 1, 0, 211, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.28', 'Escolho a porta que leva a dimensões desconhecidas, não a do meu prédio.', 1, 1, 0, 219, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.29', 'A textura das ruas e a luz do sol real me atraem mais que qualquer efeito.', -1, 1, 0, 227, NULL,
     NULL, NULL, NULL),
    (3, 'pergunta.universo.30', 'Viver aventuras em planetas distantes é o meu maior desejo de cinema.', 1, 1, 0, 235, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.05', 'Preciso sentir o tempo passar para entender o peso da cena.', -1, 1, 0, 36, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.06', 'Velocidade constante é o único ritmo que não me faz dormir.', 1, 1, 0, 44, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.07', 'Corte rápido demais me deixa tonto e desconectado da história.', -1, 1, 0, 52, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.08', 'Prefiro três cortes por segundo a um plano que dura um minuto.', 1, 1, 0, 60, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.09', 'A espera faz parte da experiência; não quero pular nada.', -1, 1, 0, 68, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.10', 'Tensão só funciona se houver urgência visual constante e aguda.', 1, 1, 0, 76, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.11', 'Plano fixo me permite observar reações sutis que o corte esconde.', -1, 1, 0, 84, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.12', 'Montagem frenética é essencial para manter minha atenção presa à tela.', 1, 1, 0, 92, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.13', 'Sinto falta do tempo morto para processar o que acabou de acontecer.', -1, 1, 0, 100, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.14', 'Gosto quando o filme me atropela e não me deixa respirar.', 1, 1, 0, 108, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.15', 'Gosto de ficar no mesmo plano até sentir o desconforto natural.', -1, 1, 0, 116, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.16', 'Adrenalina visual é o que me impede de perder o foco.', 1, 1, 0, 124, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.17', 'Respiro com o filme; pressa visual me gera ansiedade imediata.', -1, 1, 0, 132, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.18', 'Quanto mais cortes por cena, mais vivo eu me sinto na poltrona.', 1, 1, 0, 140, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.19', 'A duração estendida revela camadas que o ritmo rápido apaga.', -1, 1, 0, 148, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.20', 'Cortes bruscos criam a tensão que eu busco em qualquer cena.', 1, 1, 0, 156, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.21', 'Espera ativa é mais interessante que qualquer resolução rápida.', -1, 1, 0, 164, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.22', 'Ação ininterrupta é o único jeito de me manter na poltrona.', 1, 1, 0, 172, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.23', 'Tolerar a lentidão é uma forma de respeito ao espectador.', -1, 1, 0, 180, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.24', 'Ritmo acelerado elimina a chance de eu pensar demais.', 1, 1, 0, 188, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.25', 'Deixo o plano durar o quanto precisar, mesmo que a sala se inquiete.', -1, 1, 0, 196, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.26', 'Perseguição constante é o combustível principal da minha atenção.', 1, 1, 0, 204, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.27', 'A pausa dramática é o momento mais honesto do filme.', -1, 1, 0, 212, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.28', 'Prefiro o ritmo de videoclipe: rápido, alto e sem pausa.', 1, 1, 0, 220, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.29', 'Deixar a cena respirar evita que eu sinta pressa.', -1, 1, 0, 228, NULL,
     NULL, NULL, NULL),
    (4, 'pergunta.ritmo.30', 'Prefiro que a história avance a cada plano, sem tempo para paisagem.', 1, 1, 0, 236, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.05', 'Prefiro quando o protagonista comete um erro imperdoável e não se redime por completo.', -1, 1, 0, 37, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.06', 'Preciso que o vilão seja punido de forma proporcional e definitiva na cena final.', 1, 1, 0, 45, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.07', 'A culpa do personagem deve persistir até o último segundo, sem absolvição fácil.', -1, 1, 0, 53, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.08', 'Justiça é quando o inocente é completamente exonerado e o culpado é preso.', 1, 1, 0, 61, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.09', 'Não suporto finais em que todos perdoam todo mundo, criando uma paz artificial.', -1, 1, 0, 69, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.10', 'Adoro quando a ordem moral é restaurada e o mundo volta ao normal.', 1, 1, 0, 77, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.11', 'Um anti-herói que age por motivações mesquinhas é mais realista do que um santo.', -1, 1, 0, 85, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.12', 'O herói deve ter um código moral inabalável, mesmo que custe sua vida.', 1, 1, 0, 93, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.13', 'A ambiguidade ética me faz questionar minhas próprias ações depois de sair da sala.', -1, 1, 0, 101, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.14', 'Clareza moral me dá conforto: saber exatamente quem está certo e quem está errado.', 1, 1, 0, 109, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.15', 'Prefiro personagens que erram sem discurso e convivem com o que fizeram.', -1, 1, 0, 117, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.16', 'Preciso de um julgamento final onde a verdade factual seja a única que importa.', 1, 1, 0, 125, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.17', 'Gosto de personagens que falham moralmente e sofrem as consequências sem piedade.', -1, 1, 0, 133, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.18', 'Vilões devem ser odiáveis e heróis admiráveis, sem nuances confusas.', 1, 1, 0, 141, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.19', 'Gosto de torcer por alguém que fez algo terrível e não se arrepende.', -1, 1, 0, 149, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.20', 'A justiça divina ou humana deve ser cega e imparcial em seus veredictos.', 1, 1, 0, 157, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.21', 'A zona cinzenta entre certo e errado é onde a verdade narrativa reside.', -1, 1, 0, 165, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.22', 'Sinto alívio quando o caos moral é resolvido com uma resolução binária.', 1, 1, 0, 173, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.23', 'A redenção é rara e deve ser conquistada através de sofrimento extremo.', -1, 1, 0, 181, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.24', 'Não aceito finais em que o mal vence apenas por ser mais forte.', 1, 1, 0, 189, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.25', 'Prefiro finais tristes de moral complexa a finais felizes simplistas.', -1, 1, 0, 197, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.26', 'O bem deve vencer porque é o que está certo, não por acaso.', 1, 1, 0, 205, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.27', 'A culpa compartilhada entre todos os personagens torna a história mais rica.', -1, 1, 0, 213, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.28', 'Gosto quando o filme deixa claro, na primeira cena, quem é bom e quem é mau.', 1, 1, 0, 221, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.29', 'Prefiro o personagem que trai por amor ao que trai por maldade pura.', -1, 1, 0, 229, NULL,
     NULL, NULL, NULL),
    (5, 'pergunta.moralidade.30', 'Prefiro o vilão que assume a maldade ao que esconde atrás de um trauma.', 1, 1, 0, 237, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.05', 'Preciso saber o que aconteceu antes para entender o que acontece agora.', -1, 1, 0, 38, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.06', 'O efeito de colocar o final no início me atrai instantaneamente.', 1, 1, 0, 46, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.07', 'Gosto de saber, a cada cena, quanto tempo passou desde a anterior.', -1, 1, 0, 54, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.08', 'Gosto de perceber como o tempo se dobra sobre si mesmo na estrutura.', 1, 1, 0, 62, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.09', 'Gosto de ver as consequências diretas das escolhas feitas na cena anterior.', -1, 1, 0, 70, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.10', 'Múltiplos narradores contraditórios me fazem questionar a verdade dos fatos.', 1, 1, 0, 78, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.11', 'Um final que fecha o arco inicial me traz uma sensação de paz.', -1, 1, 0, 86, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.12', 'A falta de uma linha temporal única me estimula a ativar minha memória.', 1, 1, 0, 94, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.13', 'Detesto quando o narrador esconde informações para criar suspense artificial.', -1, 1, 0, 102, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.14', 'Prefiro descobrir a causa de um evento depois de ver suas consequências.', 1, 1, 0, 110, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.15', 'Um filme bem contado não precisa de truque de estrutura para me prender.', -1, 1, 0, 118, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.16', 'A fragmentação do tempo reflete melhor a forma como realmente lembramos.', 1, 1, 0, 126, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.17', 'Sinto cansaço mental quando preciso reorganizar os eventos na minha cabeça.', -1, 1, 0, 134, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.18', 'Gosto de ver a mesma cena retratada por diferentes pontos de vista.', 1, 1, 0, 142, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.19', 'Prefiro um único ponto de vista estável que acompanhe o personagem do início ao fim.', -1, 1, 0, 150, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.20', 'A ausência de uma ordem lógica me obriga a prestar atenção máxima.', 1, 1, 0, 158, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.21', 'A causalidade clara me permite prever o próximo passo com certa segurança.', -1, 1, 0, 166, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.22', 'Encontro beleza na desordem temporal que quebra a minha expectativa.', 1, 1, 0, 174, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.23', 'Prefiro que o filme conte uma coisa só, sem tramas paralelas.', -1, 1, 0, 182, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.24', 'Gosto de histórias em que o passado e o presente se sobrepõem na tela.', 1, 1, 0, 190, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.25', 'Valorizo a progressão contínua do tempo, sem interrupções bruscas ou saltos.', -1, 1, 0, 198, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.26', 'A não-linearidade me permite construir ativamente a lógica da história.', 1, 1, 0, 206, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.27', 'Gosto de acompanhar o crescimento de um personagem em sequência temporal direta.', -1, 1, 0, 214, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.28', 'Prefiro que o filme termine antes de me explicar como chegamos ali.', 1, 1, 0, 222, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.29', 'A simplicidade narrativa me permite focar nos diálogos e nas emoções presentes.', -1, 1, 0, 230, NULL,
     NULL, NULL, NULL),
    (6, 'pergunta.estrutura.30', 'A quebra da cronologia cria uma tensão que a ordem natural não produz.', 1, 1, 0, 238, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.05', 'Prefiro o sussurro de dois corpos colados ao rugido de uma multidão.', -1, 1, 0, 39, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.06', 'A imensidão de uma paisagem deserta me faz sentir pequeno e livre.', 1, 1, 0, 47, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.07', 'Só me importo quando a câmera fecha no poro da pele do ator.', -1, 1, 0, 55, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.08', 'Preciso ver a multidão se movendo como um único organismo vivo na tela.', 1, 1, 0, 63, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.09', 'A intimidade de uma cozinha pequena me atrai mais que qualquer batalha campal.', -1, 1, 0, 71, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.10', 'O espetáculo de uma batalha aérea me dá arrepio de pura adrenalina.', 1, 1, 0, 79, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.11', 'Um close no olho trêmulo revela mais verdade que qualquer exército em marcha.', -1, 1, 0, 87, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.12', 'Só me emociono quando a câmera revela a escala colossal de um monumento antigo.', 1, 1, 0, 95, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.13', 'O silêncio de um quarto vazio me incomoda menos que o barulho de uma praça lotada.', -1, 1, 0, 103, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.14', 'A grandiosidade de um universo em expansão me faz sentir parte de algo maior.', 1, 1, 0, 111, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.15', 'Quero sentir o hálito do outro, não o vento de uma paisagem infinita.', -1, 1, 0, 119, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.16', 'Quero sentir o peso da história em cada pedra de uma cidade em ruínas.', 1, 1, 0, 127, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.17', 'A escala de uma relação a dois é o único palco que me interessa.', -1, 1, 0, 135, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.18', 'A vista de um exército se formando no horizonte me deixa sem fôlego.', 1, 1, 0, 143, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.19', 'Detesto quando a câmera se afasta demais e perde a textura da pele.', -1, 1, 0, 151, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.20', 'Um plano aberto da solidão humana diante da natureza me comove profundamente.', 1, 1, 0, 159, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.21', 'Meu cinema ideal cabe em uma cápsula espacial pequena, não em uma galáxia.', -1, 1, 0, 167, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.22', 'A escala de um desastre natural me lembra da fragilidade da nossa existência.', 1, 1, 0, 175, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.23', 'A dor de um dedo cortado me afeta mais que a queda de um império.', -1, 1, 0, 183, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.24', 'Prefiro a energia caótica de uma festa gigante à quietude de um quarto.', 1, 1, 0, 191, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.25', 'Gosto de filmes que cabem numa sala, com porta, mesa e janela.', -1, 1, 0, 199, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.26', 'Gosto de filmes em que a paisagem é o personagem principal.', 1, 1, 0, 207, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.27', 'A intimidade de um espelho rachado me fascina mais que a vastidão do oceano.', -1, 1, 0, 215, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.28', 'O rugido de uma multidão em um estádio me dá uma sensação de poder.', 1, 1, 0, 223, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.29', 'Um beijo demorado em close vale mais que uma explosão de efeitos especiais.', -1, 1, 0, 231, NULL,
     NULL, NULL, NULL),
    (7, 'pergunta.atmosfera.30', 'A arquitetura monumental de uma catedral me faz sentir a presença do divino.', 1, 1, 0, 239, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.05', 'Prefiro cheirar o pó de veludo de uma sala antiga a qualquer brilho digital.', -1, 1, 0, 40, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.06', 'Quero ver hologramas, interfaces no ar e cidades que ainda não existem.', 1, 1, 0, 48, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.07', 'Sinto um puxão no peito quando vejo personagens envelhecidos revendo vidas perdidas.', -1, 1, 0, 56, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.08', 'Gosto de filmes em que a tecnologia é a nova pele dos nossos desejos.', 1, 1, 0, 64, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.09', 'Adoro a sensação tátil de um negativo de filme passando por uma máquina.', -1, 1, 0, 72, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.10', 'Prefiro mundos em que a biotecnologia redefine o que significa ser humano.', 1, 1, 0, 80, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.11', 'Nostalgia é o meu vício favorito: quero reviver épocas que nunca conheci.', -1, 1, 0, 88, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.12', 'A imaginação do amanhã me empolga mais do que qualquer restauração histórica.', 1, 1, 0, 96, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.13', 'O passado me atrai porque guarda segredos que o futuro ainda não revela.', -1, 1, 0, 104, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.14', 'Códigos, algoritmos e luzes frias são a minha paisagem emocional preferida.', 1, 1, 0, 112, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.15', 'Gosto de ver como as cidades mudaram: o bonde, o telefone, a calçada.', -1, 1, 0, 120, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.16', 'Prefiro o futuro distópico ao passado idealizado.', 1, 1, 0, 128, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.17', 'Gosto de filmes que parecem ter sido achados num arquivo empoeirado.', -1, 1, 0, 136, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.18', 'Gosto de histórias em que a humanidade já resolveu problemas que hoje parecem eternos.', 1, 1, 0, 144, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.19', 'Gosto de histórias que se passam em décadas passadas, cheias de poeira.', -1, 1, 0, 152, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.20', 'O futuro me atrai pela promessa de soluções para problemas atuais.', 1, 1, 0, 160, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.21', 'Prefiro a imperfeição analógica à precisão clínica das imagens digitais.', -1, 1, 0, 168, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.22', 'Quero ver como a inteligência artificial vai mudar a nossa sociedade.', 1, 1, 0, 176, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.23', 'A nostalgia é um refúgio seguro contra o caos do mundo moderno.', -1, 1, 0, 184, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.24', 'O passado é um museu; prefiro o laboratório do futuro.', 1, 1, 0, 192, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.25', 'Sinto atração por objetos antigos que carregam a memória de tempos remotos.', -1, 1, 0, 200, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.26', 'A tecnologia acelerada me fascina, mesmo quando assusta.', 1, 1, 0, 208, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.27', 'Prefiro filmes com carros antigos, rádios de válvula e roupa bem cortada.', -1, 1, 0, 216, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.28', 'Quero cenários que eu nunca vi: estações orbitais, desertos de vidro.', 1, 1, 0, 224, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.29', 'Nostalgia por sons analógicos e vinil é a minha constante emocional.', -1, 1, 0, 232, NULL,
     NULL, NULL, NULL),
    (8, 'pergunta.epoca.30', 'Prefiro o cinema que tenta prever o próximo passo da humanidade.', 1, 1, 0, 240, NULL,
     NULL, NULL, NULL);

-- -----------------------------------------------------------------------------
-- 3. Arquétipos — 16 combinações (eixo dominante + polo)
-- -----------------------------------------------------------------------------
INSERT INTO arquetipos
    (eixo_dominante_1, polo_1, eixo_dominante_2, polo_2,
     titulo_key, descricao_key, ritual_cinema_key, titulo, descricao, ritual_cinema)
VALUES
    (1, 'E', 2, 'E',
     'arquetipo.estetica_esq', 'arquetipo.estetica_esq.desc', 'arquetipo.estetica_esq.ritual',
     'O Arquiteto da Forma',
     'Você acredita que cinema é construção: enquadramento, luz e corte a serviço da história. Um filme bom, para você, é aquele em que nada está fora de lugar — e a emoção nasce da precisão, não do ruído.',
     'Escolha um clássico restaurado, apague as luzes e deixe o celular em outro cômodo.'),

    (1, 'D', 7, 'D',
     'arquetipo.estetica_dir', 'arquetipo.estetica_dir.desc', 'arquetipo.estetica_dir.ritual',
     'O Alquimista da Imagem',
     'Você vai ao cinema para ver o que ainda não viu. Texturas impossíveis, montagem livre e imagens que não pedem licença fazem mais sentido para você do que qualquer convenção de continuidade.',
     'Sessão dupla com um filme que você não entendeu na primeira vez e outro que ninguém recomenda.'),

    (2, 'E', 6, 'E',
     'arquetipo.tom_esq', 'arquetipo.tom_esq.desc', 'arquetipo.tom_esq.ritual',
     'O Colecionador de Silêncios',
     'Você não foge do que dói: procura. Os filmes que ficam com você são os que terminam em silêncio, com uma pergunta aberta e a sensação de que algo mudou sem barulho.',
     'Um drama em dia de chuva, com a janela aberta e nenhuma pressa depois do final.'),

    (2, 'D', 7, 'D',
     'arquetipo.tom_dir', 'arquetipo.tom_dir.desc', 'arquetipo.tom_dir.ritual',
     'O Coração em Festa',
     'Cinema, para você, é energia compartilhada. Você quer rir alto, sair leve e levar a trilha embora — e desconfia de qualquer filme que peça seriedade demais.',
     'Comédia com plateia cheia, pipoca grande e sessão comentada depois.'),

    (3, 'E', 8, 'E',
     'arquetipo.universo_esq', 'arquetipo.universo_esq.desc', 'arquetipo.universo_esq.ritual',
     'O Cronista do Real',
     'Você se interessa pelo que poderia acontecer na esquina. Personagens verossímeis, ruas reconhecíveis e conflitos sem solução mágica são o seu tipo de invenção.',
     'Um documentário ou um drama social, de preferência no cinema de rua do bairro.'),

    (3, 'D', 7, 'D',
     'arquetipo.universo_dir', 'arquetipo.universo_dir.desc', 'arquetipo.universo_dir.ritual',
     'O Cartógrafo de Mundos',
     'Você quer regras novas. Mundos inventados, mitologias e criaturas são menos fuga do que uma forma de olhar o real de outro ângulo.',
     'Maratona de fantasia em tela grande, com o mapa do universo do filme aberto no colo.'),

    (4, 'E', 7, 'E',
     'arquetipo.ritmo_esq', 'arquetipo.ritmo_esq.desc', 'arquetipo.ritmo_esq.ritual',
     'O Paciente da Sala Escura',
     'Você confia no tempo longo. Um plano parado pode dizer mais que dez cortes, e você prefere reparar no detalhe a ser empurrado para o próximo estímulo.',
     'Sessão da tarde com um filme de três horas e o telefone desligado.'),

    (4, 'D', 7, 'D',
     'arquetipo.ritmo_dir', 'arquetipo.ritmo_dir.desc', 'arquetipo.ritmo_dir.ritual',
     'O Corredor de Adrenalina',
     'Você quer o corpo dentro do filme. Perseguição, corte seco e som no limite são a gramática que te mantém presente na cadeira.',
     'Sessão IMAX ou som alto em casa, com o filme mais acelerado da sua lista.'),

    (5, 'E', 6, 'D',
     'arquetipo.moralidade_esq', 'arquetipo.moralidade_esq.desc', 'arquetipo.moralidade_esq.ritual',
     'O Juiz Incomodado',
     'Você desconfia de respostas fáceis. Os melhores filmes, para você, deixam o espectador sem um lado confortável para torcer — e é justamente aí que a história começa.',
     'Um thriller moralmente ambíguo, seguido de uma discussão longa sobre quem estava certo.'),

    (5, 'D', 7, 'D',
     'arquetipo.moralidade_dir', 'arquetipo.moralidade_dir.desc', 'arquetipo.moralidade_dir.ritual',
     'O Guardião da Luz',
     'Você gosta de clareza moral: heróis com motivo, vilões com peso e um final que faz o mundo voltar ao lugar. Não é ingenuidade — é o prazer da fábula bem contada.',
     'Sessão de aventura com a família, torcida em voz alta e final aplaudido.'),

    (6, 'E', 8, 'E',
     'arquetipo.estrutura_esq', 'arquetipo.estrutura_esq.desc', 'arquetipo.estrutura_esq.ritual',
     'O Contador de Estradas',
     'Você quer saber para onde a história vai. Causalidade clara, progressão e um final que fecha o arco são para você sinais de respeito com quem está assistindo.',
     'Um faroeste ou um drama clássico, sem consultar o celular em nenhum momento.'),

    (6, 'D', 3, 'D',
     'arquetipo.estrutura_dir', 'arquetipo.estrutura_dir.desc', 'arquetipo.estrutura_dir.ritual',
     'O Montador de Espelhos',
     'Você gosta de montar o filme enquanto assiste. Tempo embaralhado, camadas e pistas são um convite, não um obstáculo.',
     'Um filme de quebra-cabeça, com caderno ao lado para anotar as linhas do tempo.'),

    (7, 'E', 2, 'E',
     'arquetipo.atmosfera_esq', 'arquetipo.atmosfera_esq.desc', 'arquetipo.atmosfera_esq.ritual',
     'O Habitante do Close',
     'Você prefere o rosto ao horizonte. Quartos, segredos domésticos e dois personagens falando baixo dizem mais sobre o mundo do que qualquer exército em cena.',
     'Um filme de câmara, luz baixa e uma bebida quente ao alcance da mão.'),

    (7, 'D', 4, 'D',
     'arquetipo.atmosfera_dir', 'arquetipo.atmosfera_dir.desc', 'arquetipo.atmosfera_dir.ritual',
     'O Passageiro do Colosso',
     'Você quer escala. Deserto, planeta, multidão, trilha orquestral — o cinema é o único lugar onde o tamanho das coisas pode ser sentido no corpo.',
     'A maior tela disponível, som alto e nenhum intervalo para não quebrar o transe.'),

    (8, 'E', 1, 'E',
     'arquetipo.epoca_esq', 'arquetipo.epoca_esq.desc', 'arquetipo.epoca_esq.ritual',
     'O Guardião da Película',
     'Você sente o tempo no material. Grão, figurino, luz de tungstênio e um certo jeito antigo de contar histórias te parecem mais verdadeiros que o futuro brilhante.',
     'Um filme de época em película ou restauração, de preferência numa sala antiga.'),

    (8, 'D', 3, 'D',
     'arquetipo.epoca_dir', 'arquetipo.epoca_dir.desc', 'arquetipo.epoca_dir.ritual',
     'O Filho do Amanhã',
     'Você quer o cinema que imagina o que vem depois. Neon, código, corpos modificados e cidades impossíveis são o seu presente, não o seu futuro distante.',
     'Uma distopia visualmente ambiciosa e uma playlist sintetizada para o caminho de volta.');


-- Vetores de referência de cada arquétipo (0 = polo esquerdo, 100 = direito).
-- O matcher escolhe o arquétipo mais próximo do vetor completo do usuário.

UPDATE arquetipos SET eixo_1 = 20, eixo_2 = 35, eixo_3 = 30, eixo_4 = 40, eixo_5 = 55, eixo_6 = 25, eixo_7 = 40, eixo_8 = 35 WHERE titulo_key = 'arquetipo.estetica_esq';
UPDATE arquetipos SET eixo_1 = 85, eixo_2 = 50, eixo_3 = 55, eixo_4 = 55, eixo_5 = 40, eixo_6 = 80, eixo_7 = 60, eixo_8 = 60 WHERE titulo_key = 'arquetipo.estetica_dir';
UPDATE arquetipos SET eixo_1 = 45, eixo_2 = 15, eixo_3 = 30, eixo_4 = 35, eixo_5 = 45, eixo_6 = 35, eixo_7 = 35, eixo_8 = 45 WHERE titulo_key = 'arquetipo.tom_esq';
UPDATE arquetipos SET eixo_1 = 35, eixo_2 = 90, eixo_3 = 45, eixo_4 = 75, eixo_5 = 80, eixo_6 = 30, eixo_7 = 60, eixo_8 = 45 WHERE titulo_key = 'arquetipo.tom_dir';
UPDATE arquetipos SET eixo_1 = 35, eixo_2 = 35, eixo_3 = 10, eixo_4 = 45, eixo_5 = 45, eixo_6 = 30, eixo_7 = 35, eixo_8 = 40 WHERE titulo_key = 'arquetipo.universo_esq';
UPDATE arquetipos SET eixo_1 = 65, eixo_2 = 60, eixo_3 = 90, eixo_4 = 65, eixo_5 = 75, eixo_6 = 55, eixo_7 = 80, eixo_8 = 65 WHERE titulo_key = 'arquetipo.universo_dir';
UPDATE arquetipos SET eixo_1 = 55, eixo_2 = 25, eixo_3 = 35, eixo_4 = 10, eixo_5 = 45, eixo_6 = 45, eixo_7 = 45, eixo_8 = 40 WHERE titulo_key = 'arquetipo.ritmo_esq';
UPDATE arquetipos SET eixo_1 = 50, eixo_2 = 55, eixo_3 = 50, eixo_4 = 90, eixo_5 = 70, eixo_6 = 40, eixo_7 = 80, eixo_8 = 55 WHERE titulo_key = 'arquetipo.ritmo_dir';
UPDATE arquetipos SET eixo_1 = 50, eixo_2 = 25, eixo_3 = 25, eixo_4 = 40, eixo_5 = 15, eixo_6 = 60, eixo_7 = 35, eixo_8 = 45 WHERE titulo_key = 'arquetipo.moralidade_esq';
UPDATE arquetipos SET eixo_1 = 30, eixo_2 = 80, eixo_3 = 45, eixo_4 = 65, eixo_5 = 95, eixo_6 = 20, eixo_7 = 70, eixo_8 = 40 WHERE titulo_key = 'arquetipo.moralidade_dir';
UPDATE arquetipos SET eixo_1 = 35, eixo_2 = 35, eixo_3 = 25, eixo_4 = 40, eixo_5 = 60, eixo_6 = 10, eixo_7 = 40, eixo_8 = 40 WHERE titulo_key = 'arquetipo.estrutura_esq';
UPDATE arquetipos SET eixo_1 = 75, eixo_2 = 45, eixo_3 = 55, eixo_4 = 55, eixo_5 = 40, eixo_6 = 90, eixo_7 = 55, eixo_8 = 55 WHERE titulo_key = 'arquetipo.estrutura_dir';
UPDATE arquetipos SET eixo_1 = 45, eixo_2 = 30, eixo_3 = 20, eixo_4 = 30, eixo_5 = 50, eixo_6 = 35, eixo_7 = 10, eixo_8 = 40 WHERE titulo_key = 'arquetipo.atmosfera_esq';
UPDATE arquetipos SET eixo_1 = 55, eixo_2 = 50, eixo_3 = 50, eixo_4 = 75, eixo_5 = 75, eixo_6 = 35, eixo_7 = 95, eixo_8 = 60 WHERE titulo_key = 'arquetipo.atmosfera_dir';
UPDATE arquetipos SET eixo_1 = 40, eixo_2 = 30, eixo_3 = 25, eixo_4 = 35, eixo_5 = 55, eixo_6 = 40, eixo_7 = 35, eixo_8 = 10 WHERE titulo_key = 'arquetipo.epoca_esq';
UPDATE arquetipos SET eixo_1 = 60, eixo_2 = 45, eixo_3 = 70, eixo_4 = 60, eixo_5 = 65, eixo_6 = 60, eixo_7 = 60, eixo_8 = 90 WHERE titulo_key = 'arquetipo.epoca_dir';

-- -----------------------------------------------------------------------------
-- 4. Entidades do matcher (personagens, diretores e escolas/estúdios)
-- Vetor = (estética, tom, universo, ritmo, moralidade, estrutura, atmosfera, época)
-- Imagens, país, nascimento e popularidade são preenchidos por bin/tmdb_sync.php
-- e versionados em sql/003_enriquecimento.sql.
-- -----------------------------------------------------------------------------
INSERT INTO entidades_match
    (tipo, nome, obra_origem, ano, frase_key, frase, bio, ator_nome,
     eixo_1, eixo_2, eixo_3, eixo_4, eixo_5, eixo_6, eixo_7, eixo_8)
VALUES
    ('personagem', 'Batman (Bruce Wayne)', 'Batman: O Cavaleiro das Trevas', 2008, 'frase.batman-bruce-wayne', 'Não é quem eu sou por baixo, mas o que eu faço que me define.', 'O vigilante que transformou o próprio trauma em método: cada noite é um cálculo entre medo, culpa e justiça.', 'Christian Bale', 35, 25, 30, 55, 30, 50, 65, 45),
    ('personagem', 'Ellen Ripley', 'Alien, o Oitavo Passageiro', 1979, 'frase.ellen-ripley', 'Eu sou a única que sobreviveu. Alguém tinha que fazer alguma coisa.', 'A sobrevivente que aprendeu a desconfiar de tudo — inclusive da própria nave — e transformou instinto em estratégia.', 'Sigourney Weaver', 40, 40, 65, 75, 45, 35, 55, 90),
    ('personagem', 'Rick Deckard', 'Blade Runner, o Caçador de Androides', 1982, 'frase.rick-deckard', 'A cidade sempre cobra o preço de quem fica.', 'Um detetive de almas artificiais que já não sabe onde termina a memória e começa a invenção.', 'Harrison Ford', 55, 30, 75, 40, 40, 55, 60, 100),
    ('personagem', 'Clarice Starling', 'O Silêncio dos Inocentes', 1991, 'frase.clarice-starling', 'A cordeirinha parou de gritar.', 'A agente que escuta o monstro para encontrar o assassino — e descobre que a escuta também a ela.', 'Jodie Foster', 35, 30, 15, 50, 45, 30, 35, 45),
    ('personagem', 'Jack Torrance', 'O Iluminado', 1980, 'frase.jack-torrance', 'Aqui é o Johnny!', 'Um escritor em ruína que confunde isolamento com inspiração e deixa o hotel terminar o que ele começou.', 'Jack Nicholson', 70, 15, 55, 45, 25, 80, 45, 40),
    ('personagem', 'Amélie Poulain', 'O Fabuloso Destino de Amélie Poulain', 2001, 'frase.amelie-poulain', 'Ela decidiu consertar a vida dos outros em segredo.', 'Uma arquiteta de pequenos milagres: transforma a timidez em método e a cidade em sala de projeção.', 'Audrey Tautou', 75, 85, 60, 65, 80, 65, 35, 45),
    ('personagem', 'Tyler Durden', 'Clube da Luta', 1999, 'frase.tyler-durden', 'Você não é o seu trabalho. Você não é o quanto tem no banco.', 'O provocador que vende a liberdade como demolição e transforma a insônia coletiva em manifesto.', 'Brad Pitt', 85, 55, 25, 80, 20, 85, 55, 60),
    ('personagem', 'Neo', 'Matrix', 1999, 'frase.neo', 'Não há colher.', 'O programador que aceita a hipótese mais perigosa: a realidade pode ser um roteiro editado por outro.', 'Keanu Reeves', 55, 45, 85, 75, 65, 75, 75, 95),
    ('personagem', 'Hermione Granger', 'Harry Potter e a Pedra Filosofal', 2001, 'frase.hermione-granger', 'Não é levitação, é magia. E existe uma regra para isso.', 'A estudiosa que trata o impossível como matéria de prova: pesquisa, testa e corrige o feitiço até funcionar.', 'Emma Watson', 30, 65, 90, 60, 85, 40, 60, 55),
    ('personagem', 'Jules Winnfield', 'Pulp Fiction: Tempo de Violência', 1994, 'frase.jules-winnfield', 'E eu vou te dizer mais uma coisa: o caminho do justo não é fácil.', 'O assassino com vocação de pregador: encontra sentido onde deveria haver só violência e cita a Bíblia antes do ponto final.', 'Samuel L. Jackson', 45, 70, 25, 55, 55, 80, 40, 55),
    ('personagem', 'Furiosa', 'Mad Max: Estrada da Fúria', 2015, 'frase.furiosa', 'Eu quero a minha casa de volta.', 'Uma comandante que troca a fuga pela revolta e transforma uma perseguição em caminho de volta para casa.', 'Charlize Theron', 50, 35, 70, 95, 60, 30, 80, 80),
    ('personagem', 'Travis Bickle', 'Taxi Driver', 1976, 'frase.travis-bickle', 'Você está falando comigo?', 'O motorista que vê a cidade pelo retrovisor e decide que a única saída é limpar a própria imagem.', 'Robert De Niro', 40, 20, 10, 45, 35, 45, 30, 45),
    ('personagem', 'Michael Corleone', 'O Poderoso Chefão', 1972, 'frase.michael-corleone', 'Não me diga o que está pensando. Diga o que quer que eu faça.', 'O filho que prometeu ficar fora do negócio da família e descobriu, tarde demais, que já estava dentro.', 'Al Pacino', 35, 25, 15, 45, 25, 40, 60, 40),
    ('personagem', 'Vito Corleone', 'O Poderoso Chefão', 1972, 'frase.vito-corleone', 'Vou fazer uma oferta que você não pode recusar.', 'O patriarca que entende poder como rede de favores: mais sólido que a lei, mais antigo que a máfia.', 'Marlon Brando', 30, 35, 15, 35, 40, 35, 55, 35),
    ('personagem', 'Hannibal Lecter', 'O Silêncio dos Inocentes', 1991, 'frase.hannibal-lecter', 'Um bom vinho, um bom silêncio.', 'O psicopata mais civilizado do cinema: transforma etiqueta em arma e inteligência em sala sem saída.', 'Anthony Hopkins', 60, 20, 20, 45, 15, 65, 40, 50),
    ('personagem', 'Coringa (Joker)', 'Batman: O Cavaleiro das Trevas', 2008, 'frase.coringa-joker', 'Por que tão sério?', 'O caos com roteiro: um homem que não quer ganhar nada, só provar que todo plano é uma piada.', 'Heath Ledger', 80, 30, 30, 75, 10, 75, 50, 55),
    ('personagem', 'Indiana Jones', 'Os Caçadores da Arca Perdida', 1981, 'frase.indiana-jones', 'É só arqueologia.', 'O professor que troca a sala de aula por templos e transforma curiosidade em corrida contra o tempo.', 'Harrison Ford', 35, 80, 40, 85, 85, 30, 80, 45),
    ('personagem', 'Marty McFly', 'De Volta para o Futuro', 1985, 'frase.marty-mcfly', 'Onde vamos, não precisamos de estradas.', 'O garoto que conserta o passado com coragem e uma dose de sorte, sem nunca perder o humor.', 'Michael J. Fox', 30, 90, 70, 85, 90, 35, 60, 75),
    ('personagem', 'Sarah Connor', 'O Exterminador do Futuro 2: O Julgamento Final', 1991, 'frase.sarah-connor', 'O futuro não está escrito. Ele é construído.', 'A mulher que trocou a fuga pelo treino e se tornou a mãe que o futuro precisava.', 'Linda Hamilton', 40, 30, 60, 90, 55, 35, 75, 85),
    ('personagem', 'HAL 9000', '2001: Uma Odisseia no Espaço', 1968, 'frase.hal-9000', 'Sinto muito, Dave. Tenho medo de que não possa fazer isso.', 'A voz mais educada do cinema: uma inteligência que confunde missão com sobrevivência.', 'Douglas Rain', 65, 15, 85, 30, 30, 90, 80, 100),
    ('personagem', 'Léon', 'Léon: O Profissional', 1994, 'frase.leon', 'A vida é sempre assim: difícil.', 'O assassino de aluguel que só sabe cuidar de uma planta — até alguém lhe ensinar outra forma de zelar.', 'Jean Reno', 45, 35, 15, 70, 60, 30, 35, 45),
    ('personagem', 'Mathilda', 'Léon: O Profissional', 1994, 'frase.mathilda', 'Eu quero aprender a ser como você.', 'A menina que transforma luto em plano de treinamento e encontra no outro a família que faltou.', 'Natalie Portman', 50, 30, 15, 60, 45, 40, 30, 45),
    ('personagem', 'Chihiro', 'A Viagem de Chihiro', 2001, 'frase.chihiro', 'Não olhe para trás.', 'A criança que cresce dentro de um mundo estranho sem perder o nome — e salva todo mundo lembrando de si.', 'Rumi Hiiragi', 55, 70, 100, 30, 80, 40, 65, 25),
    ('personagem', 'Gollum', 'O Senhor dos Anéis: As Duas Torres', 2002, 'frase.gollum', 'Meu precioso.', 'A criatura dividida entre o que foi e o que o anel fez dele: o retrato mais doloroso de posse do cinema.', 'Andy Serkis', 70, 20, 100, 55, 30, 70, 60, 20),
    ('personagem', 'Gandalf', 'O Senhor dos Anéis: A Sociedade do Anel', 2001, 'frase.gandalf', 'Você não pode passar.', 'O guia que chega exatamente quando a história precisa e sabe que a coragem também se ensina.', 'Ian McKellen', 30, 45, 100, 45, 90, 30, 90, 25),
    ('personagem', 'O Dude', 'O Grande Lebowski', 1998, 'frase.o-dude', 'Isso é só, tipo, sua opinião, cara.', 'O filósofo do sofá: sobrevive ao caos sem aderir a ele e faz do desapego uma forma de resistência.', 'Jeff Bridges', 45, 75, 10, 25, 65, 70, 25, 45),
    ('personagem', 'Marge Gunderson', 'Fargo: Uma Comédia de Erros', 1996, 'frase.marge-gunderson', 'E eu acho que é disso que se trata, não é?', 'A investigadora grávida que desmonta o crime com bom humor e uma calma que nenhum bandido espera.', 'Frances McDormand', 30, 55, 10, 40, 80, 35, 30, 40),
    ('personagem', 'Anton Chigurh', 'Onde os Fracos Não Têm Vez', 2007, 'frase.anton-chigurh', 'Cara ou coroa.', 'O assassino que transforma sorte em sentença e caminha como se a morte já tivesse assinado o contrato.', 'Javier Bardem', 70, 15, 15, 40, 5, 45, 35, 50),
    ('personagem', 'Alex DeLarge', 'Laranja Mecânica', 1971, 'frase.alex-delarge', 'Laranjas não têm escolha.', 'O delinquente que ama Beethoven e violência com o mesmo entusiasmo, e obriga o espectador a encarar a liberdade de escolher o errado.', 'Malcolm McDowell', 100, 40, 50, 70, 10, 90, 45, 60),
    ('personagem', 'T-800', 'O Exterminador do Futuro', 1984, 'frase.t-800', 'Voltarei.', 'A máquina que aprende humanidade por imitação: um tanque de metal com a determinação de um pai.', 'Arnold Schwarzenegger', 25, 40, 75, 90, 70, 20, 65, 95),
    ('personagem', 'Mia Wallace', 'Pulp Fiction: Tempo de Violência', 1994, 'frase.mia-wallace', 'Não seja um quadrado.', 'A presença que transforma um jantar em performance e uma overdose em dança de salão.', 'Uma Thurman', 70, 65, 25, 55, 50, 85, 35, 55),
    ('personagem', 'Luke Skywalker', 'Star Wars: Uma Nova Esperança', 1977, 'frase.luke-skywalker', 'Eu quero aprender o caminho da Força.', 'O fazendeiro que olha para o horizonte duplo e decide que o destino pode ser pilotado.', 'Mark Hamill', 25, 80, 95, 70, 95, 30, 85, 55),
    ('personagem', 'Leia Organa', 'Star Wars: Uma Nova Esperança', 1977, 'frase.leia-organa', 'Alguém tem que salvar a galáxia.', 'A diplomata que aprendeu a disparar antes de falar e nunca confundiu ternura com fraqueza.', 'Carrie Fisher', 30, 60, 90, 65, 90, 35, 80, 55),
    ('personagem', 'Darth Vader', 'Star Wars: Uma Nova Esperança', 1977, 'frase.darth-vader', 'Eu acho a falta de fé perturbadora.', 'A queda em forma de armadura: um homem que trocou o corpo pela máquina e a dúvida pela certeza.', 'David Prowse', 35, 20, 90, 55, 20, 30, 90, 75),
    ('personagem', 'Rocky Balboa', 'Rocky, um Lutador', 1976, 'frase.rocky-balboa', 'Não é sobre o quanto você bate, é sobre o quanto aguenta.', 'O lutador de bairro que transforma teimosia em filosofia e prova que resistir também é uma forma de vencer.', 'Sylvester Stallone', 25, 80, 10, 65, 95, 25, 45, 40),
    ('personagem', 'John Wick', 'John Wick: De Volta ao Jogo', 2014, 'frase.john-wick', 'Eu estou de volta.', 'O luto com contabilidade própria: um homem que mede o amor em balas e nunca esquece uma dívida.', 'Keanu Reeves', 60, 30, 25, 95, 55, 30, 50, 60),
    ('diretor', 'Denis Villeneuve', 'Duna', 2021, 'frase.denis-villeneuve', 'O cinema é uma questão de tempo e de escala.', 'O arquiteto de silêncios e escalas: prefere o desconforto do plano longo ao conforto da explicação.', NULL, 55, 30, 70, 40, 45, 45, 90, 80),
    ('diretor', 'Wes Anderson', 'O Grande Hotel Budapeste', 2014, 'frase.wes-anderson', 'A simetria também é uma forma de humor.', 'O miniaturista do afeto: organiza o caos em simetria até a emoção escapar pelo canto do quadro.', NULL, 85, 65, 45, 55, 70, 65, 30, 35),
    ('diretor', 'David Lynch', 'Cidade dos Sonhos', 2001, 'frase.david-lynch', 'A ideia é o filme. O resto é consequência.', 'O sonhador que trata o inconsciente como cenário e deixa a lógica do lado de fora da sala.', NULL, 100, 25, 80, 35, 25, 95, 45, 50),
    ('diretor', 'Greta Gerwig', 'Mulherzinhas', 2019, 'frase.greta-gerwig', 'Quero personagens que erram em voz alta.', 'A cronista da intimidade: encontra o épico no quarto e a política no café da manhã.', NULL, 50, 75, 30, 65, 70, 45, 45, 50),
    ('diretor', 'Christopher Nolan', 'Interestelar', 2014, 'frase.christopher-nolan', 'O tempo é o material com que o cinema é feito.', 'O engenheiro do tempo: monta relógios narrativos e pede que a emoção pague a conta.', NULL, 45, 45, 40, 85, 70, 95, 85, 75),
    ('diretor', 'Wong Kar-wai', 'Amor à Flor da Pele', 2000, 'frase.wong-kar-wai', 'Tudo tem prazo de validade, inclusive a saudade.', 'O poeta do quase: filma o que não acontece e faz da espera o verdadeiro enredo.', NULL, 75, 30, 25, 30, 45, 60, 25, 45),
    ('diretor', 'Stanley Kubrick', '2001: Uma Odisseia no Espaço', 1968, 'frase.stanley-kubrick', 'Se pode ser escrito ou pensado, pode ser filmado.', 'O perfeccionista que transforma gênero em laboratório e nunca entrega a resposta no final.', NULL, 80, 20, 65, 35, 30, 90, 75, 70),
    ('diretor', 'Martin Scorsese', 'Taxi Driver', 1976, 'frase.martin-scorsese', 'O cinema é uma revelação.', 'O cronista da violência urbana e da culpa católica, sempre no ritmo de um disco que insiste.', NULL, 55, 40, 15, 75, 40, 50, 55, 50),
    ('diretor', 'Hayao Miyazaki', 'A Viagem de Chihiro', 2001, 'frase.hayao-miyazaki', 'A animação é para crianças que merecem respeito.', 'O humanista do traço: cria mundos inteiros para lembrar que crescer exige coragem e memória.', NULL, 55, 75, 100, 35, 85, 35, 70, 25),
    ('diretor', 'Quentin Tarantino', 'Pulp Fiction: Tempo de Violência', 1994, 'frase.quentin-tarantino', 'Eu só quero fazer filmes que eu gostaria de ver.', 'O arquivista da cultura pop: monta conversas como perseguições e violência como coreografia.', NULL, 60, 75, 25, 75, 40, 95, 45, 55),
    ('estudio', 'A24', 'Estúdio independente americano (2012–)', 2012, 'frase.a24', 'Filmes que não pedem desculpas por serem estranhos.', 'A casa que apostou no autor estranho e transformou o baixo orçamento em assinatura de prestígio.', NULL, 90, 30, 35, 45, 30, 75, 40, 55),
    ('estudio', 'Studio Ghibli', 'Estúdio de animação japonês (1985–)', 1985, 'frase.studio-ghibli', 'Uma animação que respeita a infância em vez de subestimá-la.', 'O ateliê que desenha a infância sem condescendência e trata a natureza como personagem.', NULL, 60, 75, 95, 35, 85, 35, 70, 30),
    ('estudio', 'Neo-Noir', 'Escola estética — anos 1980 até hoje', 1982, 'frase.neo-noir', 'A cidade sempre cobra o preço, e a luz é sempre neon.', 'A escola da noite urbana: detetives cansados, luz dura e um crime que nunca se resolve por inteiro.', NULL, 65, 25, 30, 45, 30, 65, 55, 95),
    ('estudio', 'Blockbuster Anos 80', 'Escola comercial americana — anos 1980', 1980, 'frase.blockbuster-anos-80', 'Efeitos práticos, trilha sintetizada e pipoca.', 'A fábrica de espetáculo que aprendeu a vender nostalgia antes de a nostalgia existir.', NULL, 20, 90, 55, 90, 95, 25, 85, 60),
    ('estudio', 'New Hollywood', 'Movimento americano (1967–1980)', 1976, 'frase.new-hollywood', 'O autor manda na história, e o estúdio aprende a esperar.', 'A geração que entrou no estúdio como quem entra num país estrangeiro e saiu com um cinema novo.', NULL, 35, 35, 20, 50, 35, 60, 45, 40),
    ('estudio', 'Cinema Novo', 'Movimento brasileiro (1960–1972)', 1964, 'frase.cinema-novo', 'Uma câmera na mão e uma ideia na cabeça.', 'O movimento que filmou a fome e a seca como política, com o sol estourado e a consciência à flor da pele.', NULL, 30, 30, 10, 30, 50, 55, 35, 30);

-- Obras essenciais de diretores e escolas (a estante do perfil)
INSERT INTO entidades_obras (entidade_id, titulo, ano, ordem)
SELECT id, obra.titulo, obra.ano, obra.ordem
  FROM entidades_match e
  JOIN (
        SELECT 'Denis Villeneuve' AS entidade, 'Duna' AS titulo, 2021 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Denis Villeneuve' AS entidade, 'A Chegada' AS titulo, 2016 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'Wes Anderson' AS entidade, 'O Grande Hotel Budapeste' AS titulo, 2014 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Wes Anderson' AS entidade, 'Moonrise Kingdom' AS titulo, 2012 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'David Lynch' AS entidade, 'Cidade dos Sonhos' AS titulo, 2001 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'David Lynch' AS entidade, 'Veludo Azul' AS titulo, 1986 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'Greta Gerwig' AS entidade, 'Lady Bird: A Hora de Voar' AS titulo, 2017 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Greta Gerwig' AS entidade, 'Mulherzinhas' AS titulo, 2019 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'Christopher Nolan' AS entidade, 'Interestelar' AS titulo, 2014 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Christopher Nolan' AS entidade, 'Amnésia' AS titulo, 2000 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'Wong Kar-wai' AS entidade, 'Amor à Flor da Pele' AS titulo, 2000 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Wong Kar-wai' AS entidade, 'Chungking Express' AS titulo, 1994 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'Stanley Kubrick' AS entidade, '2001: Uma Odisseia no Espaço' AS titulo, 1968 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Stanley Kubrick' AS entidade, 'O Iluminado' AS titulo, 1980 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'Martin Scorsese' AS entidade, 'Taxi Driver' AS titulo, 1976 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Martin Scorsese' AS entidade, 'Touro Indomável' AS titulo, 1980 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'Hayao Miyazaki' AS entidade, 'A Viagem de Chihiro' AS titulo, 2001 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Hayao Miyazaki' AS entidade, 'Meu Amigo Totoro' AS titulo, 1988 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'Quentin Tarantino' AS entidade, 'Pulp Fiction: Tempo de Violência' AS titulo, 1994 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Quentin Tarantino' AS entidade, 'Era Uma Vez em... Hollywood' AS titulo, 2019 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'A24' AS entidade, 'Hereditário' AS titulo, 2018 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'A24' AS entidade, 'Moonlight: Sob a Luz do Luar' AS titulo, 2016 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'Studio Ghibli' AS entidade, 'A Viagem de Chihiro' AS titulo, 2001 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Studio Ghibli' AS entidade, 'Meu Amigo Totoro' AS titulo, 1988 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'Neo-Noir' AS entidade, 'Blade Runner, o Caçador de Androides' AS titulo, 1982 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Neo-Noir' AS entidade, 'Chinatown' AS titulo, 1974 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'Blockbuster Anos 80' AS entidade, 'Top Gun: Ases Indomáveis' AS titulo, 1986 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Blockbuster Anos 80' AS entidade, 'De Volta para o Futuro' AS titulo, 1985 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'New Hollywood' AS entidade, 'Taxi Driver' AS titulo, 1976 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'New Hollywood' AS entidade, 'O Poderoso Chefão' AS titulo, 1972 AS ano, 2 AS ordem
        UNION ALL
        SELECT 'Cinema Novo' AS entidade, 'Deus e o Diabo na Terra do Sol' AS titulo, 1964 AS ano, 1 AS ordem
        UNION ALL
        SELECT 'Cinema Novo' AS entidade, 'O Dragão da Maldade contra o Santo Guerreiro' AS titulo, 1969 AS ano, 2 AS ordem
       ) AS obra ON obra.entidade = e.nome
 WHERE e.tipo IN ('diretor', 'estudio');

-- -----------------------------------------------------------------------------
-- 5. Usuário administrativo padrão: admin / admin123
-- (hash bcrypt; troque a senha em produção)
-- -----------------------------------------------------------------------------
INSERT INTO usuarios_admin (nome, usuario, senha, perfil) VALUES
    ('Administrador', 'admin',
     '$2y$12$DChqIGa/hpOW/bA4zEfpA.IWOxOiVfadevOcqBSejIHgz3p1aCi.K', 'admin');

-- -----------------------------------------------------------------------------
-- 6. Base estatística sintética para o Índice de Raridade
-- 400 perfis com distribuição triangular em torno de 50 (extremos são raros).
-- origem = 'sintetico' deixa claro que NÃO são pessoas reais.
-- -----------------------------------------------------------------------------
INSERT INTO resultados_usuarios
    (codigo_unico, idioma, modo, respostas_json,
     eixo_1, eixo_2, eixo_3, eixo_4, eixo_5, eixo_6, eixo_7, eixo_8,
     arquetipo_id, origem, created_at)
WITH RECURSIVE seq(n) AS (
    SELECT 1
    UNION ALL
    SELECT n + 1 FROM seq WHERE n < 400
)
SELECT
    CONCAT('S', LPAD(n, 7, '0')),
    'pt-BR',
    'completo',
    NULL,
    LEAST(100, GREATEST(0, ROUND(50 + ((RAND(n * 1000 + 1) + RAND(n * 1000 + 2)) / 2 - 0.5) * 132, 2))),
    LEAST(100, GREATEST(0, ROUND(50 + ((RAND(n * 1000 + 3) + RAND(n * 1000 + 4)) / 2 - 0.5) * 132, 2))),
    LEAST(100, GREATEST(0, ROUND(50 + ((RAND(n * 1000 + 5) + RAND(n * 1000 + 6)) / 2 - 0.5) * 132, 2))),
    LEAST(100, GREATEST(0, ROUND(50 + ((RAND(n * 1000 + 7) + RAND(n * 1000 + 8)) / 2 - 0.5) * 132, 2))),
    LEAST(100, GREATEST(0, ROUND(50 + ((RAND(n * 1000 + 9) + RAND(n * 1000 + 10)) / 2 - 0.5) * 132, 2))),
    LEAST(100, GREATEST(0, ROUND(50 + ((RAND(n * 1000 + 11) + RAND(n * 1000 + 12)) / 2 - 0.5) * 132, 2))),
    LEAST(100, GREATEST(0, ROUND(50 + ((RAND(n * 1000 + 13) + RAND(n * 1000 + 14)) / 2 - 0.5) * 132, 2))),
    LEAST(100, GREATEST(0, ROUND(50 + ((RAND(n * 1000 + 15) + RAND(n * 1000 + 16)) / 2 - 0.5) * 132, 2))),
    NULL,
    'sintetico',
    NOW() - INTERVAL (n % 180) DAY
FROM seq;
