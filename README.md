# 🎬 CineAxes

Gerenciador de cinema/filmes desenvolvido como projeto da disciplina de **Desenvolvimento Web 2 (DW2)**.

Permite cadastrar filmes, organizar sessões por sala e horário, e registrar a venda/reserva de ingressos — com uma área pública de cartaz e um painel administrativo.

## Stack

| Camada | Tecnologia |
| --- | --- |
| Back-end | PHP 8.2 (PDO, sessões, POO) |
| Banco de dados | MySQL 8.4 |
| Front-end | Bootstrap 5 + JavaScript |
| Ambiente | Docker + Docker Compose (Apache, MySQL e phpMyAdmin) |

A stack segue o padrão do projeto de referência da disciplina: [odilklein/barbearia](https://github.com/odilklein/barbearia).

## Equipe

- [@odilklein](https://github.com/odilklein)
- [@giovannixaviernx-ai](https://github.com/giovannixaviernx-ai)

## Status

🚧 Em desenvolvimento — ver o [cronograma](docs/CRONOGRAMA.md).

## Como executar

Requisitos: Docker com Docker Compose.

```bash
# 1. configuração (opcional: os valores padrão já funcionam)
cp .env.example .env

# 2. subir banco + aplicação + phpMyAdmin
docker compose up -d --build
```

O banco é criado e populado automaticamente na primeira subida, pelos scripts de `sql/`. Na primeira execução o MySQL leva cerca de 1 minuto para inicializar e o container da aplicação só sobe depois que o banco responde.

| Serviço | Endereço |
| --- | --- |
| Aplicação | http://localhost:8080 |
| phpMyAdmin | http://localhost:8081 |
| MySQL | `localhost:3308` |

Credenciais padrão (definidas no `.env`):

| Acesso | Valor |
| --- | --- |
| Banco | `cineaxes` / `cineaxes123` / database `cineaxes` |
| Admin do sistema | `admin` / `admin123` (hash gerado com `password_hash`) |

**Parar os serviços**

```bash
docker compose down      # para os contêineres, mantém o banco
docker compose down -v   # para e apaga o banco (dados perdidos)
```

## Estrutura planejada

```
.
├── index.php                  # cartaz: filmes em exibição
├── filme.php                  # detalhes do filme + sessões
├── sessao.php                 # escolha de assentos
├── reserva.php                # confirmação e gravação da reserva
├── reserva_sucesso.php        # comprovante
│
├── login.php                  # login administrativo
├── auth.php                   # valida usuário/senha e inicia a sessão
├── logout.php                 # encerra a sessão
│
├── admin/
│   ├── dashboard.php          # indicadores do cinema
│   ├── filmes.php             # CRUD de filmes
│   ├── salas.php              # CRUD de salas
│   ├── sessoes.php            # CRUD de sessões
│   └── reservas.php           # listagem e status das reservas
│
├── classes/                   # regras de negócio (POO)
│   ├── Filme.php
│   ├── Sessao.php
│   └── Reserva.php
│
├── include/
│   ├── config.php             # configurações gerais e funções auxiliares
│   ├── conexao.php            # conexão PDO
│   ├── cabecalho.php          # navbar e tema claro/escuro
│   └── check_login.php        # proteção das rotas administrativas
│
├── sql/
│   ├── 001_schema.sql         # tabelas
│   └── 002_seed.sql           # dados de exemplo
│
├── docs/
│   └── CRONOGRAMA.md          # cronograma de desenvolvimento
│
├── docker-compose.yml
└── Dockerfile
```

## Banco de dados (visão geral)

- `usuarios` — acesso ao painel administrativo
- `generos` — gêneros dos filmes
- `filmes` — catálogo (título, sinopse, duração, classificação, pôster, trailer, status)
- `salas` — salas e capacidade
- `sessoes` — filme + sala + data/horário + preço
- `reservas` — cliente, sessão, assentos, valor e status

## Documentação

- [Cronograma de desenvolvimento](docs/CRONOGRAMA.md)
