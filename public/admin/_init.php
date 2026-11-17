<?php

declare(strict_types=1);

/** Bootstrap comum do painel administrativo. */

$config = require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Core\Database;
use Core\Session;

$pdo = Database::pdo();

if (!function_exists('pc_admin_redirect')) {
    function pc_admin_redirect(string $url, ?string $mensagem = null, string $tipo = 'ok'): never
    {
        if ($mensagem !== null) {
            Session::flash($tipo, $mensagem);
        }

        header('Location: ' . $url, true, 302);
        exit;
    }

    function pc_admin_texto(string $chave, string $padrao = ''): string
    {
        $valor = $_POST[$chave] ?? $padrao;

        return is_string($valor) ? trim($valor) : $padrao;
    }

    function pc_admin_inteiro(string $chave, int $padrao = 0): int
    {
        $valor = $_POST[$chave] ?? $padrao;

        return is_numeric($valor) ? (int) $valor : $padrao;
    }

    function pc_admin_slug(string $texto): string
    {
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
        $texto = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $texto) ?? '');

        return trim($texto, '-');
    }
}
