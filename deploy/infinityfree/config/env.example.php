<?php

declare(strict_types=1);

/**
 * Exemplo de configuração para o InfinityFree.
 *
 * Copie este arquivo para htdocs/config/env.php e preencha com os dados do
 * painel do InfinityFree. O config/env.php não é versionado.
 *
 * O .env do projeto NÃO é lido pelo PHP: ele só serve para o Docker Compose.
 */

return [
    // Painel InfinityFree > MySQL Databases
    // O host não é localhost: use sqlXXX.infinityfree.com.
    'DB_HOST' => 'sqlXXX.infinityfree.com',
    'DB_PORT' => '3306',
    'DB_NAME' => 'if0_XXXXXXXX_personacut',
    'DB_USER' => 'if0_XXXXXXXX',
    'DB_PASS' => 'senha_do_banco',

    // Em produção, nunca deixe true: stack traces e detalhes internos vazam.
    'APP_DEBUG' => 'false',

    // TMDB: necessário para pôsteres, fotos e fichas rápidas.
    // https://www.themoviedb.org/settings/api
    'TMDB_API_KEY' => '',

    // Groq: opcional. Sem chave, o retrato por IA cai no fallback das leituras.
    // https://console.groq.com/keys
    'GROQ_API_KEY' => '',
    'GROQ_MODEL' => '',
    // Free tier da Groq aceita até 1000 tokens de saída por minuto.
    'GROQ_MAX_TOKENS' => '900',
    'GROQ_ENABLED' => 'true',

    // Recursos opcionais do site público (desligados por padrão).
    // Ligue quando quiser reativar a lista pessoal e o cadastro.
    'FEATURE_WATCHLIST' => 'false',
    'FEATURE_CADASTRO' => 'false',
];
