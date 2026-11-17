-- Schema inicial do projeto CineAxes
-- Tabelas: usuarios, generos, filmes, salas, sessoes, reservas
-- Executado automaticamente pelo MySQL na primeira subida do Docker.

-- Garante que o client leia este arquivo como UTF-8 (evita acentos quebrados)
SET NAMES utf8mb4 COLLATE utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('admin', 'operador') NOT NULL DEFAULT 'operador',
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS generos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    UNIQUE KEY nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS filmes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    sinopse TEXT,
    genero_id INT DEFAULT NULL,
    duracao_min INT NOT NULL DEFAULT 0,
    classificacao VARCHAR(5) NOT NULL DEFAULT 'L',
    poster_url VARCHAR(255) DEFAULT NULL,
    trailer_url VARCHAR(255) DEFAULT NULL,
    status ENUM('em_cartaz', 'em_breve', 'fora_de_cartaz') NOT NULL DEFAULT 'em_cartaz',
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_filmes_genero FOREIGN KEY (genero_id) REFERENCES generos (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS salas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    capacidade INT NOT NULL DEFAULT 60,
    tipo ENUM('2D', '3D', 'IMAX') NOT NULL DEFAULT '2D',
    UNIQUE KEY nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS sessoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filme_id INT NOT NULL,
    sala_id INT NOT NULL,
    data_sessao DATE NOT NULL,
    horario TIME NOT NULL,
    preco DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    idioma ENUM('dublado', 'legendado') NOT NULL DEFAULT 'dublado',
    CONSTRAINT fk_sessoes_filme FOREIGN KEY (filme_id) REFERENCES filmes (id) ON DELETE CASCADE,
    CONSTRAINT fk_sessoes_sala FOREIGN KEY (sala_id) REFERENCES salas (id) ON DELETE CASCADE,
    UNIQUE KEY uk_sala_horario (sala_id, data_sessao, horario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sessao_id INT NOT NULL,
    cliente VARCHAR(100) NOT NULL,
    telefone VARCHAR(20) DEFAULT NULL,
    assentos VARCHAR(255) NOT NULL,
    quantidade INT NOT NULL DEFAULT 1,
    valor_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('reservado', 'pago', 'cancelado') NOT NULL DEFAULT 'reservado',
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reservas_sessao FOREIGN KEY (sessao_id) REFERENCES sessoes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
