<?php

declare(strict_types=1);

/**
 * Configuração de conexão — devolve apenas os parâmetros.
 * A criação do PDO fica em src/Core/Database.php (uma conexão por requisição).
 *
 * O docker-compose injeta DB_* no container da aplicação.
 * Fora do Docker, copie config/env.example.php para config/env.php e ajuste lá.
 */
return [
    'host'    => getenv('DB_HOST') ?: '127.0.0.1',
    'port'    => getenv('DB_PORT') ?: '3306',
    'dbname'  => getenv('DB_NAME') ?: 'personacut',
    'user'    => getenv('DB_USER') ?: 'personacut',
    'pass'    => getenv('DB_PASS') ?: 'personacut123',
    'charset' => 'utf8mb4',
];
