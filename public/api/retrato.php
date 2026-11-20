<?php

declare(strict_types=1);

/**
 * GET /api/retrato.php?codigo=XXXXXXXX
 *
 * Gera (uma única vez) o retrato redigido por IA para um resultado e persiste.
 * O cálculo, o arquétipo e a afinidade não são alterados.
 */

$config = require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Controllers\PerfilController;
use Core\Database;
use Core\Http;
use Core\Session;
use Models\Retrato;
use Services\GroqService;
use Services\LikertCalculatorService;
use Services\MatcherService;
use Services\NarrativaService;

if (Http::metodo() !== 'GET') {
    header('Allow: GET');
    Http::erro(t('erros.method'), 405);
}

$codigo = is_string($_GET['codigo'] ?? null) ? strtoupper(trim((string) $_GET['codigo'])) : '';

if (!preg_match('/^[A-Z0-9]{8}$/', $codigo)) {
    Http::erro(t('erros.invalid_code'), 422);
}

try {
    $pdo = Database::pdo();
    $existente = Retrato::porCodigo($pdo, $codigo, NarrativaService::PROMPT_VERSAO);

    if ($existente !== null) {
        header('Cache-Control: public, max-age=3600');
        Http::json([
            'ok' => true,
            'texto' => $existente['texto'],
            'frase' => $existente['frase'] ?? null,
            'narrativa' => $existente['narrativa'] ?? null,
            'modelo' => $existente['modelo'],
            'tokens' => $existente['tokens'] !== null ? (int) $existente['tokens'] : null,
            'gerado' => false,
        ]);
    }

    $narrativaService = new NarrativaService(new GroqService($config['groq']));

    if (!$narrativaService->disponivel()) {
        Http::erro('A narrativa por IA não está configurada.', 503);
    }

    // Evita rajadas de geração no mesmo perfil/sessão.
    $chaveCooldown = 'retrato_tentativa_' . $codigo;

    if (time() - (int) Session::get($chaveCooldown, 0) < 20) {
        Http::erro('Aguarde alguns segundos para gerar o retrato novamente.', 429);
    }

    Session::set($chaveCooldown, time());

    $perfil = (new PerfilController(
        $pdo,
        new MatcherService(
            (float) ($config['raridade']['limiar'] ?? 12.0),
            (int) ($config['raridade']['min_base'] ?? 100)
        ),
        new LikertCalculatorService(),
        $config
    ))->montar($codigo, true);

    if ($perfil === null) {
        Http::erro(t('erros.not_found'), 404);
    }

    $gerado = $narrativaService->gerar($perfil);

    if ($gerado === null) {
        Http::erro('Não foi possível redigir a narrativa agora. Tente novamente mais tarde.', 503);
    }

    Retrato::criar($pdo, ['codigo' => $codigo, 'idioma' => 'pt-BR'] + $gerado);
    $salvo = Retrato::porCodigo($pdo, $codigo);

    header('Cache-Control: public, max-age=3600');

    Http::json([
        'ok' => true,
        'texto' => $salvo['texto'] ?? $gerado['texto'],
        'frase' => $salvo['frase'] ?? $gerado['frase'] ?? null,
        'narrativa' => $salvo['narrativa'] ?? $gerado['narrativa'] ?? null,
        'modelo' => $salvo['modelo'] ?? $gerado['modelo'],
        'tokens' => isset($salvo['tokens']) ? (int) $salvo['tokens'] : ($gerado['tokens'] ?? null),
        'gerado' => true,
    ]);
} catch (Throwable $erro) {
    error_log('[PersonaCut][api/retrato] ' . $erro->getMessage());

    Http::erro($config['debug'] ? $erro->getMessage() : t('erros.generic'), 500);
}
