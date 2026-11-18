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
    'host'    => \Core\Env::texto('DB_HOST', '127.0.0.1'),
    'port'    => \Core\Env::texto('DB_PORT', '3306'),
    'dbname'  => \Core\Env::texto('DB_NAME', 'personacut'),
    'user'    => \Core\Env::texto('DB_USER', 'personacut'),
    'pass'    => \Core\Env::texto('DB_PASS', 'personacut123'),
    'charset' => 'utf8mb4',
];
