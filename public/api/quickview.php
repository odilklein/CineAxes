<?php

declare(strict_types=1);

/**
 * GET /api/quickview.php?tmdb=123
 *
 * Ficha rápida de um filme: sinopse, duração, gêneros e elenco.
 * Tudo passa pelo cache da TMDB; a chave nunca chega ao navegador.
 */

$config = require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Core\Database;
use Core\Http;
use Services\TmdbService;

if (Http::metodo() !== 'GET') {
    header('Allow: GET');
    Http::erro(t('erros.method'), 405);
}

$id = filter_input(INPUT_GET, 'tmdb', FILTER_VALIDATE_INT);

if (!is_int($id) || $id <= 0) {
    Http::erro('ID de filme inválido.', 422);
}

try {
    $tmdb = new TmdbService(Database::pdo(), $config['tmdb']);

    if (!$tmdb->disponivel()) {
        Http::erro('A TMDB não está configurada.', 503);
    }

    $filme = $tmdb->filmeComCreditos($id);

    if ($filme === null) {
        Http::erro('Filme não encontrado.', 404);
    }

    $generos = array_map(
        static fn (array $g): string => (string) ($g['name'] ?? ''),
        $filme['genres'] ?? []
    );

    $elenco = [];

    foreach (array_slice($filme['credits']['cast'] ?? [], 0, 8) as $ator) {
        if (empty($ator['name'])) {
            continue;
        }

        $elenco[] = [
            'nome' => (string) $ator['name'],
            'personagem' => (string) ($ator['character'] ?? ''),
            'foto' => $tmdb->imagem($ator['profile_path'] ?? null, 'w300'),
        ];
    }

    header('Cache-Control: public, max-age=86400');

    Http::json([
        'ok' => true,
        'id' => $id,
        'titulo' => (string) ($filme['title'] ?? ''),
        'original' => (string) ($filme['original_title'] ?? ''),
        'ano' => substr((string) ($filme['release_date'] ?? ''), 0, 4),
        'sinopse' => trim((string) ($filme['overview'] ?? '')),
        'duracao' => isset($filme['runtime']) ? (int) $filme['runtime'] : null,
        'nota' => round((float) ($filme['vote_average'] ?? 0), 1),
        'votos' => (int) ($filme['vote_count'] ?? 0),
        'generos' => array_values(array_filter($generos)),
        'poster' => $tmdb->imagem($filme['poster_path'] ?? null, 'w500'),
        'backdrop' => $tmdb->imagem($filme['backdrop_path'] ?? null, 'w780'),
        'elenco' => $elenco,
    ]);
} catch (Throwable $erro) {
    error_log('[PersonaCut][api/quickview] ' . $erro->getMessage());

    Http::erro($config['debug'] ? $erro->getMessage() : t('erros.generic'), 500);
}
