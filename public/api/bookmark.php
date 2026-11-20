<?php

declare(strict_types=1);

/**
 * POST /api/bookmark.php
 * Recebe { tmdb, tipo, status, titulo, ano, poster } e alterna o bookmark.
 *
 * GET /api/bookmark.php?mapa=1 devolve o mapa tmdb_id => status.
 */

$config = require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Core\Auth;
use Core\Csrf;
use Core\Database;
use Core\Http;
use Models\Bookmark;

if (!($config['features']['watchlist'] ?? false)) {
    Http::erro(t('auth.feature_disabled'), 403);
}

if (!Auth::logado()) {
    Http::erro(t('auth.login_required'), 401);
}

$pdo = Database::pdo();
$usuarioId = (int) Auth::id();

if (Http::metodo() === 'GET') {
    Http::json([
        'ok' => true,
        'mapa' => Bookmark::mapa($pdo, $usuarioId),
        'contagem' => Bookmark::contar($pdo, $usuarioId),
    ]);
}

if (Http::metodo() !== 'POST') {
    header('Allow: GET, POST');
    Http::erro(t('erros.method'), 405);
}

if (!Csrf::validarRequisicao()) {
    Http::erro(t('erros.csrf'), 403);
}

$corpo = Http::corpo();
$tmdb = isset($corpo['tmdb']) && is_numeric($corpo['tmdb']) ? (int) $corpo['tmdb'] : 0;
$titulo = trim((string) ($corpo['titulo'] ?? ''));

if ($tmdb <= 0 || $titulo === '') {
    Http::erro(t('auth.bookmark_invalid'), 422);
}

try {
    $status = Bookmark::alternar($pdo, $usuarioId, [
        'tmdb_id' => $tmdb,
        'tipo' => (string) ($corpo['tipo'] ?? 'filme'),
        'status' => (string) ($corpo['status'] ?? 'quero'),
        'titulo' => $titulo,
        'ano' => $corpo['ano'] ?? null,
        'poster_path' => $corpo['poster'] ?? null,
    ]);

    Http::json([
        'ok' => true,
        'status' => $status,
        'removido' => $status === null,
        'contagem' => Bookmark::contar($pdo, $usuarioId),
    ]);
} catch (Throwable $erro) {
    error_log('[PersonaCut][api/bookmark] ' . $erro->getMessage());
    Http::erro($config['debug'] ? $erro->getMessage() : t('erros.generic'), 500);
}
