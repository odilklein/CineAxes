<?php

declare(strict_types=1);

namespace Core;

/**
 * Sessão com cookies endurecidos. Uma sessão por visitante para CSRF,
 * rascunho do quiz e mensagens flash.
 */
final class Session
{
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    public static function get(string $chave, mixed $padrao = null): mixed
    {
        return $_SESSION[$chave] ?? $padrao;
    }

    public static function set(string $chave, mixed $valor): void
    {
        $_SESSION[$chave] = $valor;
    }

    public static function esquecer(string $chave): void
    {
        unset($_SESSION[$chave]);
    }

    public static function flash(string $chave, ?string $mensagem = null): ?string
    {
        if ($mensagem !== null) {
            $_SESSION['_flash'][$chave] = $mensagem;
            return null;
        }

        $valor = $_SESSION['_flash'][$chave] ?? null;
        unset($_SESSION['_flash'][$chave]);

        return $valor;
    }

    public static function regenerar(): void
    {
        session_regenerate_id(true);
    }
}
