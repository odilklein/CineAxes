<?php

declare(strict_types=1);

namespace Models;

use PDO;

final class UsuarioAdmin
{
    /** @return array<string, mixed>|null */
    public static function porUsuario(PDO $pdo, string $usuario): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT id, nome, usuario, senha, perfil
               FROM usuarios_admin
              WHERE usuario = :usuario
              LIMIT 1'
        );
        $stmt->execute(['usuario' => strtolower(trim($usuario))]);
        $linha = $stmt->fetch();

        if ($linha === false) {
            return null;
        }

        $linha['id'] = (int) $linha['id'];

        return $linha;
    }

    /** Autentica e devolve o usuário (sem o hash) ou null. */
    public static function autenticar(PDO $pdo, string $usuario, string $senha): ?array
    {
        $encontrado = self::porUsuario($pdo, $usuario);

        if ($encontrado === null || !password_verify($senha, (string) $encontrado['senha'])) {
            return null;
        }

        unset($encontrado['senha']);
        \Core\SessionUser::registrar($encontrado);

        return $encontrado;
    }
}
