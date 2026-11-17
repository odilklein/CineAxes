<?php

declare(strict_types=1);

namespace Core;

/**
 * Usuário administrativo logado, guardado apenas na sessão.
 */
final class SessionUser
{
    /** @param array<string, mixed> $usuario */
    public static function registrar(array $usuario): void
    {
        $_SESSION['admin'] = [
            'id'     => (int) $usuario['id'],
            'nome'   => (string) $usuario['nome'],
            'perfil' => (string) $usuario['perfil'],
        ];
    }

    /** @return array<string, mixed>|null */
    public static function atual(): ?array
    {
        $admin = $_SESSION['admin'] ?? null;

        return is_array($admin) ? $admin : null;
    }

    public static function sair(): void
    {
        unset($_SESSION['admin']);
    }

    public static function exigirLogin(): void
    {
        if (self::atual() !== null) {
            return;
        }

        $_SESSION['_flash']['erro'] = 'Faça login para acessar o painel.';
        header('Location: /admin/login.php', true, 302);
        exit;
    }
}
