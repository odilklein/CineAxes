-- Dados de exemplo do CineAxes
-- Executado automaticamente pelo MySQL na primeira subida do Docker (ordem alfabética: 001, 002...).

-- Garante que o client leia este arquivo como UTF-8 (evita acentos quebrados)
SET NAMES utf8mb4 COLLATE utf8mb4_general_ci;

INSERT INTO generos (nome) VALUES
    ('Ação'),
    ('Animação'),
    ('Comédia'),
    ('Drama'),
    ('Ficção Científica'),
    ('Romance'),
    ('Suspense'),
    ('Terror');

INSERT INTO filmes (titulo, sinopse, genero_id, duracao_min, classificacao, poster_url, trailer_url, status) VALUES
    ('A Última Sessão',
     'Um projecionista prestes a se aposentar descobre um filme perdido que pode mudar a história do cinema — e a sua própria.',
     4, 118, '12', NULL, NULL, 'em_cartaz'),
    ('Código Zero',
     'Uma analista de segurança precisa impedir um ataque digital que pode parar a cidade inteira em uma única noite.',
     1, 105, '14', NULL, NULL, 'em_cartaz'),
    ('Horizonte Vermelho',
     'A primeira colônia humana em Marte perde contato com a Terra e precisa sobreviver por conta própria.',
     5, 132, '12', NULL, NULL, 'em_cartaz'),
    ('Risos de Segunda',
     'Três amigos decidem abrir uma pizzaria e descobrem que o maior desafio é conviver com as próprias confusões.',
     3, 96, 'L', NULL, NULL, 'em_cartaz'),
    ('O Silêncio da Mata',
     'Uma família se muda para uma casa isolada e começa a ouvir sons que não deveriam existir.',
     7, 110, '16', NULL, NULL, 'em_cartaz'),
    ('Pequenos Gigantes',
     'Uma formiga sonhadora e um besouro medroso precisam salvar o jardim onde vivem.',
     2, 88, 'L', NULL, NULL, 'em_cartaz'),
    ('Noites de Inverno',
     'Dois desconhecidos se encontram todos os dias no mesmo café e constroem uma amizade improvável.',
     6, 102, '12', NULL, NULL, 'em_breve');

INSERT INTO salas (nome, capacidade, tipo) VALUES
    ('Sala 1', 60, '2D'),
    ('Sala 2', 80, '2D'),
    ('Sala 3', 100, '3D');

-- Sessões de hoje e de amanhã
INSERT INTO sessoes (filme_id, sala_id, data_sessao, horario, preco, idioma) VALUES
    (1, 1, CURDATE(), '14:30', 20.00, 'dublado'),
    (1, 1, CURDATE(), '19:00', 24.00, 'legendado'),
    (2, 2, CURDATE(), '16:00', 24.00, 'dublado'),
    (2, 2, CURDATE(), '21:15', 24.00, 'legendado'),
    (3, 3, CURDATE(), '18:00', 30.00, 'legendado'),
    (4, 2, CURDATE(), '13:00', 20.00, 'dublado'),
    (5, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '20:30', 24.00, 'dublado'),
    (6, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '11:00', 20.00, 'dublado'),
    (3, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '21:00', 30.00, 'legendado');

-- Usuário administrativo padrão: admin / admin123
-- (hash bcrypt; troque a senha em produção)
INSERT INTO usuarios (nome, usuario, senha, perfil) VALUES
    ('Administrador', 'admin', '$2y$12$DChqIGa/hpOW/bA4zEfpA.IWOxOiVfadevOcqBSejIHgz3p1aCi.K', 'admin');
