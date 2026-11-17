<?php

declare(strict_types=1);

namespace Core;

/**
 * Token CSRF por sessão, aceito tanto em campo de formulário quanto no
 * header X-CSRF-Token (usado pelos fetch do quiz).
 */
final class Csrf
{
    public static function token(): string
    {
        $token = Session::get('_csrf');

        if (!is_string($token) || strlen($token) < 64) {
            $token = bin2hex(random_bytes(32));
            Session::set('_csrf', $token);
        }

        return $token;
    }

    public static function campo(): string
    {
        return sprintf(
            '<input type="hidden" name="csrf" value="%s">',
            htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8')
        );
    }

    public static function validar(?string $token): bool
    {
        $esperado = Session::get('_csrf');

        return is_string($esperado)
            && is_string($token)
            && hash_equals($esperado, $token);
    }

    /**
     * Valida o token da requisição atual (campo POST ou header).
     */
    public static function validarRequisicao(): bool
    {
        $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        return self::validar(is_string($token) ? $token : null);
    }
}
