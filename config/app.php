<?php

declare(strict_types=1);

/**
 * Configuração da aplicação.
 *
 * Os valores vêm de variáveis de ambiente (docker-compose ou config/env.php).
 * Nenhum segredo deve ser escrito aqui.
 */
return [
    'nome'    => 'PersonaCut',
    'tagline' => 'Seu perfil cinéfilo em 8 eixos',
    'versao'  => '1.0.0-mvp',

    'locale'  => 'pt-BR',
    'locales' => [
        'pt-BR' => 'Português (Brasil)',
        'en'    => 'English',
        'es'    => 'Español',
    ],

    'debug' => \Core\Env::booleano('APP_DEBUG', true),

    // Regras do índice de raridade (ver docs/EIXOS.md).
    'raridade' => [
        'limiar'      => 12.0, // média de diferença por eixo considerada "similar"
        'min_base'    => 100,  // abaixo disso o selo mostra "base inicial"
        'max_amostra' => 5000, // limite de linhas carregadas para o cálculo
    ],

    // Quantidade de perguntas por modo de teste (ver Pergunta::MODOS).
    'quiz' => [
        'rapido'   => 36,
        'completo' => 60,
        'extremo'  => 240,
    ],

    // Integração TMDB (usada a partir da v1.5; o MVP funciona offline).
    'tmdb' => [
        'api_key'   => \Core\Env::texto('TMDB_API_KEY'),
        'base_url'  => 'https://api.themoviedb.org/3',
        'image_url' => 'https://image.tmdb.org/t/p',
        'language'  => 'pt-BR',
        'region'    => 'BR',
        'cache_ttl' => 86400,
    ],

    // Retrato redigido por IA (Groq). A chave fica só no servidor.
    'groq' => [
        'api_key'        => \Core\Env::texto('GROQ_API_KEY'),
        'model'          => \Core\Env::texto('GROQ_MODEL', 'qwen/qwen3.8-27b'),
        'base_url'       => 'https://api.groq.com/openai/v1',
        'timeout'        => 30,
        'max_tokens'     => 900,
        'temperature'    => 0.75,
        'enabled'        => \Core\Env::booleano('GROQ_ENABLED', true),
        'prompt_versao'  => 'retrato-v1',
    ],

    // GeoCinema (v2).
    'geo' => [
        'raio_km'  => 15,
        'overpass' => 'https://overpass-api.de/api/interpreter',
    ],
];
