<?php

declare(strict_types=1);

/**
 * POST /api/quiz.php
 *
 * Recebe { modo, csrf, respostas: { pergunta_id: -2..2 } } em JSON,
 * calcula o perfil, grava e devolve { ok: true, codigo }.
 */

$config = require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Controllers\QuizController;
use Core\Csrf;
use Core\Database;
use Core\Http;
use Services\LikertCalculatorService;
use Services\MatcherService;

if (Http::metodo() !== 'POST') {
    header('Allow: POST');
    Http::erro(t('erros.method'), 405);
}

$corpo = Http::corpo();
$token = $corpo['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

if (!Csrf::validar(is_string($token) ? $token : null)) {
    Http::erro(t('erros.csrf'), 403);
}

$modo = is_string($corpo['modo'] ?? null) ? (string) $corpo['modo'] : 'completo';
$respostas = $corpo['respostas'] ?? [];

if (!is_array($respostas)) {
    Http::erro('O campo respostas deve ser um objeto.', 422);
}

// Normaliza chaves e valores para int antes de qualquer cálculo.
$normalizadas = [];

foreach ($respostas as $idPergunta => $valor) {
    if (!is_numeric($idPergunta) || is_bool($valor) || !is_numeric($valor)) {
        continue;
    }

    $normalizadas[(int) $idPergunta] = (int) $valor;
}

try {
    $matcher = new MatcherService(
        (float) ($config['raridade']['limiar'] ?? 12.0),
        (int) ($config['raridade']['min_base'] ?? 100)
    );

    $resultado = (new QuizController(Database::pdo(), new LikertCalculatorService(), $matcher))
        ->processar($modo, $normalizadas, Http::ipHash());

    Http::json([
        'ok'       => true,
        'codigo'   => $resultado['codigo'],
        'redirect' => url('resultado.php', ['codigo' => $resultado['codigo']]),
    ]);
} catch (InvalidArgumentException $erro) {
    Http::erro($erro->getMessage(), 422, ['campo' => 'respostas']);
} catch (Throwable $erro) {
    error_log('[PersonaCut][api/quiz] ' . $erro->getMessage());

    Http::erro($config['debug'] ? $erro->getMessage() : t('erros.generic'), 500);
}
