-- =============================================================================
-- PersonaCut — seed do modelo v2 (6 eixos)
-- Gerado para a migração 6D. Reexecutável: o schema 001 derruba as tabelas.
-- =============================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 1. Eixos (6)
-- -----------------------------------------------------------------------------
INSERT INTO eixos
    (ordem, slug, nome, descricao, polo_esq_key, polo_esq_nome, polo_dir_key, polo_dir_nome,
     cor_esq, cor_dir, icone_esq, icone_dir, tmdb_genres_esq, tmdb_genres_dir,
     tmdb_keywords_esq, tmdb_keywords_dir)
VALUES
    (1, 'estetica', 'Estética', 'Onde o filme circula: do blockbuster ao circuito de arte.', 'eixo.estetica.esq', 'Popular', 'eixo.estetica.dir', 'Autoral', '#b0553c', '#4f6480', 'users', 'feather', '28,12,35,10751', '18,36,99,10749', '', ''),
    (2, 'tom', 'Tom', 'O registro afetivo: da leveza ao desconforto.', 'eixo.tom.esq', 'Claro', 'eixo.tom.dir', 'Sombrio', '#c08a2e', '#3f4a5a', 'sun', 'moon', '35,10749,10751,16', '27,80,53,9648', '', ''),
    (3, 'mundo', 'Mundo', 'O grau de invenção: do real imediato ao mundo inventado.', 'eixo.mundo.esq', 'Real', 'eixo.mundo.dir', 'Imaginário', '#5c7048', '#7a4a63', 'home', 'planet', '99,18,36,80', '14,878,16,12', '', ''),
    (4, 'ritmo', 'Ritmo', 'A experiência da duração: do plano que respira ao corte que acelera.', 'eixo.ritmo.esq', 'Calmo', 'eixo.ritmo.dir', 'Intenso', '#3f6b6b', '#b04a5a', 'moon', 'bolt', '18,99,37', '28,53,12,27', '', ''),
    (5, 'escala', 'Escala', 'A medida do drama: do quarto ao planeta.', 'eixo.escala.esq', 'Íntimo', 'eixo.escala.dir', 'Épico', '#8c5a2b', '#2f4858', 'frame', 'mountain', '18,10749,35', '12,10752,878,14', '', ''),
    (6, 'amplitude', 'Amplitude', 'O quanto você sai do familiar: país, época, gênero e risco.', 'eixo.amplitude.esq', 'Circunscrito', 'eixo.amplitude.dir', 'Omnívoro', '#6b5b7b', '#a8792c', 'lock', 'globe', '28,35,10751', '99,36,37,10752', '', '');

-- -----------------------------------------------------------------------------
-- 2. Perguntas (48: 8 por eixo, 4 por polo)
-- `modo_rapido=1` marca o núcleo (4 por eixo) usado nos formatos curtos.
-- -----------------------------------------------------------------------------
INSERT INTO perguntas
    (eixo_id, texto_key, texto, direcao, peso, modo_rapido, ordem, tmdb_id,
     midia_tipo, midia_url_ou_id, midia_meta)
VALUES
    (1, 'q6.estetica.1', 'Um filme premiado em festival me interessa mais do que um blockbuster de bilheteria.', 1, 2, 1, 1, NULL, NULL, NULL, NULL),
    (1, 'q6.estetica.2', 'Prefiro um filme comercial bem feito a um filme de arte que exige paciência.', -1, 2, 1, 7, NULL, NULL, NULL, NULL),
    (1, 'q6.estetica.3', 'Gosto quando o filme tem a assinatura de um autor, mesmo que isso o torne mais difícil.', 1, 1, 1, 13, NULL, NULL, NULL, NULL),
    (1, 'q6.estetica.4', 'Escolho o que está em cartaz no shopping antes de procurar um cinema de rua.', -1, 1, 1, 19, NULL, NULL, NULL, NULL),
    (1, 'q6.estetica.5', 'Filmes de diretores desconhecidos, vindos de outros circuitos, me atraem.', 1, 1, 0, 25, NULL, NULL, NULL, NULL),
    (1, 'q6.estetica.6', 'Gosto de franquias e continuações porque já sei o que vou encontrar.', -1, 1, 0, 31, NULL, NULL, NULL, NULL),
    (1, 'q6.estetica.7', 'Prefiro um filme que arrisca uma linguagem própria a um que segue a fórmula.', 1, 1, 0, 37, NULL, NULL, NULL, NULL),
    (1, 'q6.estetica.8', 'O que eu quero é entretenimento garantido, não uma experiência de festival.', -1, 1, 0, 43, NULL, NULL, NULL, NULL),
    (2, 'q6.tom.1', 'Gosto de filmes que me deixam desconfortável e pensando no que vi.', 1, 2, 1, 2, NULL, NULL, NULL, NULL),
    (2, 'q6.tom.2', 'Vou ao cinema para sair leve e bem-humorado.', -1, 2, 1, 8, NULL, NULL, NULL, NULL),
    (2, 'q6.tom.3', 'Histórias com finais tristes ou ambíguos me parecem mais honestas.', 1, 1, 1, 14, NULL, NULL, NULL, NULL),
    (2, 'q6.tom.4', 'Evito filmes que me deixam para baixo.', -1, 1, 1, 20, NULL, NULL, NULL, NULL),
    (2, 'q6.tom.5', 'Terror e suspense psicológico estão entre os meus gêneros favoritos.', 1, 1, 0, 26, NULL, NULL, NULL, NULL),
    (2, 'q6.tom.6', 'Comédia e romance são meus gêneros de conforto.', -1, 1, 0, 32, NULL, NULL, NULL, NULL),
    (2, 'q6.tom.7', 'Prefiro um drama pesado a uma comédia leve.', 1, 1, 0, 38, NULL, NULL, NULL, NULL),
    (2, 'q6.tom.8', 'Gosto de finais que resolvem tudo e deixam todo mundo bem.', -1, 1, 0, 44, NULL, NULL, NULL, NULL),
    (3, 'q6.mundo.1', 'Mundos inventados, com regras próprias, me prendem mais que histórias realistas.', 1, 2, 1, 3, NULL, NULL, NULL, NULL),
    (3, 'q6.mundo.2', 'Prefiro histórias que poderiam acontecer comigo ou com alguém que conheço.', -1, 2, 1, 9, NULL, NULL, NULL, NULL),
    (3, 'q6.mundo.3', 'Ficção científica e fantasia são minhas portas de entrada para o cinema.', 1, 1, 1, 15, NULL, NULL, NULL, NULL),
    (3, 'q6.mundo.4', 'Documentários e dramas sociais me prendem mais que fantasia.', -1, 1, 1, 21, NULL, NULL, NULL, NULL),
    (3, 'q6.mundo.5', 'Gosto de mitologias, criaturas e universos que não existem.', 1, 1, 0, 27, NULL, NULL, NULL, NULL),
    (3, 'q6.mundo.6', 'Gosto de filmes que parecem registrar a rua, o bairro, a vida como ela é.', -1, 1, 0, 33, NULL, NULL, NULL, NULL),
    (3, 'q6.mundo.7', 'Um filme que cria uma realidade própria me interessa mais que um retrato do cotidiano.', 1, 1, 0, 39, NULL, NULL, NULL, NULL),
    (3, 'q6.mundo.8', 'Universos muito inventados me afastam da história.', -1, 1, 0, 45, NULL, NULL, NULL, NULL),
    (4, 'q6.ritmo.1', 'Perseguições, cortes rápidos e adrenalina me mantêm dentro do filme.', 1, 2, 1, 4, NULL, NULL, NULL, NULL),
    (4, 'q6.ritmo.2', 'Gosto de planos longos, silêncios e cenas que respiram.', -1, 2, 1, 10, NULL, NULL, NULL, NULL),
    (4, 'q6.ritmo.3', 'Gosto quando o filme não me dá tempo de respirar.', 1, 1, 1, 16, NULL, NULL, NULL, NULL),
    (4, 'q6.ritmo.4', 'Um filme lento, com pouco acontecendo, pode ser hipnótico para mim.', -1, 1, 1, 22, NULL, NULL, NULL, NULL),
    (4, 'q6.ritmo.5', 'Ação e suspense acelerados são meu tipo de sessão.', 1, 1, 0, 28, NULL, NULL, NULL, NULL),
    (4, 'q6.ritmo.6', 'Prefiro contemplar a imagem a ser empurrado para a próxima cena.', -1, 1, 0, 34, NULL, NULL, NULL, NULL),
    (4, 'q6.ritmo.7', 'Prefiro um filme que me carregue no ritmo a um que me peça paciência.', 1, 1, 0, 40, NULL, NULL, NULL, NULL),
    (4, 'q6.ritmo.8', 'Corte rápido demais me cansa.', -1, 1, 0, 46, NULL, NULL, NULL, NULL),
    (5, 'q6.escala.1', 'Gosto de filmes com exércitos, planetas, multidões e trilha grandiosa.', 1, 2, 1, 5, NULL, NULL, NULL, NULL),
    (5, 'q6.escala.2', 'Prefiro filmes com poucos personagens, em espaços pequenos.', -1, 2, 1, 11, NULL, NULL, NULL, NULL),
    (5, 'q6.escala.3', 'Quanto maior a escala, melhor: quero sentir o tamanho das coisas.', 1, 1, 1, 17, NULL, NULL, NULL, NULL),
    (5, 'q6.escala.4', 'Um quarto, dois atores e uma conversa podem ser o melhor cinema para mim.', -1, 1, 1, 23, NULL, NULL, NULL, NULL),
    (5, 'q6.escala.5', 'Épicos históricos e space operas estão entre os meus favoritos.', 1, 1, 0, 29, NULL, NULL, NULL, NULL),
    (5, 'q6.escala.6', 'Close-ups e silêncios domésticos me dizem mais que batalhas.', -1, 1, 0, 35, NULL, NULL, NULL, NULL),
    (5, 'q6.escala.7', 'Prefiro uma história que atravessa gerações e lugares a um único quarto.', 1, 1, 0, 41, NULL, NULL, NULL, NULL),
    (5, 'q6.escala.8', 'Grandes espetáculos me parecem vazios.', -1, 1, 0, 47, NULL, NULL, NULL, NULL),
    (6, 'q6.amplitude.1', 'Assisto a filmes de países e idiomas que não são o meu.', 1, 2, 1, 6, NULL, NULL, NULL, NULL),
    (6, 'q6.amplitude.2', 'Prefiro filmes recentes, do meu país, com atores que eu conheço.', -1, 2, 1, 12, NULL, NULL, NULL, NULL),
    (6, 'q6.amplitude.3', 'Gosto de explorar gêneros que não domino, mesmo sem garantia de que vou gostar.', 1, 1, 1, 18, NULL, NULL, NULL, NULL),
    (6, 'q6.amplitude.4', 'Escolho quase sempre os mesmos gêneros.', -1, 1, 1, 24, NULL, NULL, NULL, NULL),
    (6, 'q6.amplitude.5', 'Filmes antigos, de décadas antes de eu nascer, me interessam.', 1, 1, 0, 30, NULL, NULL, NULL, NULL),
    (6, 'q6.amplitude.6', 'Filme antigo ou estrangeiro me dá preguiça.', -1, 1, 0, 36, NULL, NULL, NULL, NULL),
    (6, 'q6.amplitude.7', 'Gosto de sair da minha zona de conforto quando escolho o que ver.', 1, 1, 0, 42, NULL, NULL, NULL, NULL),
    (6, 'q6.amplitude.8', 'Prefiro o que está todo mundo comentando agora.', -1, 1, 0, 48, NULL, NULL, NULL, NULL);

-- -----------------------------------------------------------------------------
-- 3. Arquétipos (12: um por eixo/polo)
-- -----------------------------------------------------------------------------
INSERT INTO arquetipos
    (eixo_dominante_1, polo_1, eixo_dominante_2, polo_2,
     eixo_1, eixo_2, eixo_3, eixo_4, eixo_5, eixo_6,
     titulo_key, descricao_key, ritual_cinema_key, titulo, descricao, ritual_cinema)
VALUES
    (1, 'E', NULL, NULL, 25, 45, 50, 50, 55, 40, 'arquetipo.estetica_e', 'arquetipo.estetica_e.desc', 'arquetipo.estetica_e.ritual', 'O Espectador de Multidão', 'Você quer que o filme funcione antes de querer que ele surpreenda. Histórias claras, elenco carismático e uma boa noite de cinema valem mais que um exercício de linguagem.', 'Sessão de estreia, pipoca grande e a sala cheia.'),
    (1, 'D', NULL, NULL, 75, 55, 55, 50, 50, 60, 'arquetipo.estetica_d', 'arquetipo.estetica_d.desc', 'arquetipo.estetica_d.ritual', 'O Caçador de Festivais', 'Você procura a assinatura do diretor, o plano que ninguém mais faria, o filme que pede um pouco de esforço. O prazer está em reconhecer uma voz, não em seguir a fórmula.', 'Uma sessão de festival, com o telefone desligado e um caderno do lado.'),
    (2, 'E', NULL, NULL, 45, 25, 50, 55, 50, 50, 'arquetipo.tom_e', 'arquetipo.tom_e.desc', 'arquetipo.tom_e.ritual', 'O Coração em Festa', 'Cinema, para você, é energia compartilhada. Você quer rir alto, sair leve e levar a trilha embora. A sala cheia faz parte do prazer.', 'Comédia com plateia cheia, pipoca e sessão comentada depois.'),
    (2, 'D', NULL, NULL, 55, 75, 55, 45, 50, 50, 'arquetipo.tom_d', 'arquetipo.tom_d.desc', 'arquetipo.tom_d.ritual', 'O Colecionador de Silêncios', 'Você não foge do que dói: procura. Os filmes que ficam com você terminam em silêncio, com uma pergunta aberta e a sensação de que algo mudou.', 'Um drama em dia de chuva, com a janela aberta e nenhuma pressa depois do final.'),
    (3, 'E', NULL, NULL, 50, 45, 25, 50, 45, 40, 'arquetipo.mundo_e', 'arquetipo.mundo_e.desc', 'arquetipo.mundo_e.ritual', 'O Cronista do Real', 'Você se interessa pelo que poderia acontecer na esquina. Personagens verossímeis, ruas reconhecíveis e conflitos sem solução mágica são o seu tipo de invenção.', 'Um documentário ou um drama social, de preferência no cinema de rua do bairro.'),
    (3, 'D', NULL, NULL, 55, 55, 75, 50, 60, 55, 'arquetipo.mundo_d', 'arquetipo.mundo_d.desc', 'arquetipo.mundo_d.ritual', 'O Cartógrafo de Mundos', 'Você quer regras novas. Mundos inventados, mitologias e criaturas são menos fuga do que uma forma de olhar o real de outro ângulo.', 'Maratona de fantasia em tela grande, com o mapa do universo do filme aberto no colo.'),
    (4, 'E', NULL, NULL, 55, 45, 50, 25, 45, 50, 'arquetipo.ritmo_e', 'arquetipo.ritmo_e.desc', 'arquetipo.ritmo_e.ritual', 'O Paciente da Sala Escura', 'Você confia no tempo longo. Um plano parado pode dizer mais que dez cortes, e você prefere reparar no detalhe a ser empurrado para o próximo estímulo.', 'Sessão da tarde com um filme de três horas e o telefone desligado.'),
    (4, 'D', NULL, NULL, 50, 55, 50, 75, 60, 45, 'arquetipo.ritmo_d', 'arquetipo.ritmo_d.desc', 'arquetipo.ritmo_d.ritual', 'O Corredor de Adrenalina', 'Você quer o corpo dentro do filme. Perseguição, corte seco e som no limite são a gramática que te mantém presente na cadeira.', 'Sessão IMAX ou som alto em casa, com o filme mais acelerado da sua lista.'),
    (5, 'E', NULL, NULL, 45, 45, 50, 45, 25, 50, 'arquetipo.escala_e', 'arquetipo.escala_e.desc', 'arquetipo.escala_e.ritual', 'O Habitante do Close', 'Você prefere o rosto ao horizonte. Quartos, segredos domésticos e dois personagens falando baixo dizem mais sobre o mundo do que qualquer exército em cena.', 'Um filme de câmara, luz baixa e uma bebida quente ao alcance da mão.'),
    (5, 'D', NULL, NULL, 55, 55, 60, 60, 75, 50, 'arquetipo.escala_d', 'arquetipo.escala_d.desc', 'arquetipo.escala_d.ritual', 'O Passageiro do Colosso', 'Você quer escala. Deserto, planeta, multidão, trilha orquestral — o cinema é o único lugar onde o tamanho das coisas pode ser sentido no corpo.', 'A maior tela disponível, som alto e nenhum intervalo para não quebrar o transe.'),
    (6, 'E', NULL, NULL, 40, 45, 40, 50, 50, 25, 'arquetipo.amplitude_e', 'arquetipo.amplitude_e.desc', 'arquetipo.amplitude_e.ritual', 'O Espectador de Casa', 'Você prefere o que já conhece: o gênero que domina, o ator de sempre, o país de sempre. Familiaridade não é preguiça; é conforto.', 'Rever um favorito com a comida de sempre e nenhuma surpresa no cardápio.'),
    (6, 'D', NULL, NULL, 60, 55, 60, 50, 50, 75, 'arquetipo.amplitude_d', 'arquetipo.amplitude_d.desc', 'arquetipo.amplitude_d.ritual', 'O Colecionador de Mundos', 'Você busca o que ainda não viu: outro país, outra década, outro gênero. O risco faz parte do prazer, e o familiar é só o ponto de partida.', 'Escolher o filme mais estranho da lista e assistir sem pesquisar antes.');

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

-- -----------------------------------------------------------------------------
-- 5. Usuário administrativo padrão: admin / admin123
-- -----------------------------------------------------------------------------
INSERT INTO usuarios_admin (nome, usuario, senha, perfil) VALUES
    ('Administrador', 'admin',
     '$2y$12$DChqIGa/hpOW/bA4zEfpA.IWOxOiVfadevOcqBSejIHgz3p1aCi.K', 'admin');

-- -----------------------------------------------------------------------------
-- 6. Perfil de exemplo da landing
-- -----------------------------------------------------------------------------
INSERT INTO resultados_usuarios
    (codigo_unico, idioma, modo, questionario_versao, respostas_json,
     eixo_1, eixo_2, eixo_3, eixo_4, eixo_5, eixo_6,
     arquetipo_id, origem, ip_hash, created_at)
VALUES
    ('EXEMPLO1', 'pt-BR', 'completo', 'perguntas-6d-v1', NULL,
     72.00, 38.00, 82.00, 30.00, 60.00, 68.00,
     NULL, 'sintetico', NULL, '2026-01-15 12:00:00'),
    ('EXEMPLO2', 'pt-BR', 'completo', 'perguntas-6d-v1', NULL,
     2.61, 42.17, 20.16, 78.34, 90.90, 83.43,
     NULL, 'sintetico', NULL, '2026-01-15 12:05:00'),
    ('EXEMPLO3', 'pt-BR', 'completo', 'perguntas-6d-v1', NULL,
     99.25, 55.87, 65.77, 74.38, 8.39, 63.27,
     NULL, 'sintetico', NULL, '2026-01-15 12:10:00');
