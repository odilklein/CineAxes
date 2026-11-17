<?php

declare(strict_types=1);

/**
 * Exemplo de configuração para rodar SEM Docker (php -S ou Apache local).
 *
 *   1. cp config/env.example.php config/env.php
 *   2. ajuste os valores abaixo
 *   3. o arquivo config/env.php não é versionado
 *
 * Com Docker não é necessário: o docker-compose.yml já injeta DB_* no container.
 * Variáveis de ambiente reais sempre têm prioridade sobre este arquivo.
 */
return [
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '3306',
    'DB_NAME' => 'personacut',
    'DB_USER' => 'personacut',
    'DB_PASS' => 'personacut123',

    // Opcional: chave da TMDB (https://www.themoviedb.org/settings/api)
    'TMDB_API_KEY' => '',

    // APP_DEBUG=true mostra stack traces; use false em produção.
    'APP_DEBUG' => 'true',
];
