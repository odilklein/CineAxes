<?php

declare(strict_types=1);

namespace Core;

/**
 * Pequenos utilitários HTTP dos endpoints JSON.
 */
final class Http
{
    /** @param array<string, mixed> $dados */
    public static function json(array $dados, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function erro(string $mensagem, int $status = 400, array $extra = []): never
    {
        self::json(['ok' => false, 'erro' => $mensagem] + $extra, $status);
    }

    public static function redirecionar(string $url): never
    {
        header('Location: ' . $url, true, 302);
        exit;
    }

    public static function metodo(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /** Corpo JSON ou application/x-www-form-urlencoded. */
    /** @return array<string, mixed> */
    public static function corpo(): array
    {
        $tipo = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($tipo, 'application/json')) {
            $bruto = file_get_contents('php://input');
            $dados = json_decode($bruto === false ? '' : $bruto, true);

            return is_array($dados) ? $dados : [];
        }

        return $_POST;
    }

    /** IP do visitante apenas para hash antiabuso (nunca salvo em claro). */
    public static function ipHash(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        return $ip === '' ? null : hash('sha256', $ip . '|personacut');
    }
}
