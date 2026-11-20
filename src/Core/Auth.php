<?php

declare(strict_types=1);

namespace Core;

use PDO;

/**
 * Contas públicas (opcionais). Só existem para salvar bookmarks; o painel
 * administrativo continua usando `usuarios_admin` e a sessão é separada.
 */
final class Auth
{
    public static function id(): ?int
    {
        $id = Session::get('usuario_id');

        return is_numeric($id) ? (int) $id : null;
    }

    public static function logado(): bool
    {
        return self::id() !== null;
    }

    public static function nome(): string
    {
        return (string) Session::get('usuario_nome', '');
    }

    public static function login(): string
    {
        return (string) Session::get('usuario_login', '');
    }

    /** @return array{id: int, nome: string, usuario: string}|null */
    public static function usuario(): ?array
    {
        $id = self::id();

        if ($id === null) {
            return null;
        }

        return [
            'id' => $id,
            'nome' => self::nome(),
            'usuario' => self::login(),
        ];
    }

    /**
     * @return string|null mensagem de erro, ou null em caso de sucesso
     */
    public static function entrar(PDO $pdo, string $usuario, string $senha): ?string
    {
        $usuario = self::normalizarUsuario($usuario);

        if ($usuario === '') {
            return 'Informe o seu usuário.';
        }

        $stmt = $pdo->prepare(
            'SELECT id, nome, usuario, senha
               FROM usuarios
              WHERE usuario = :usuario
              LIMIT 1'
        );
        $stmt->execute(['usuario' => $usuario]);
        $linha = $stmt->fetch();

        if ($linha === false || !password_verify($senha, (string) $linha['senha'])) {
            return 'Usuário ou senha incorretos.';
        }

        self::abrirSessao($pdo, (int) $linha['id'], (string) $linha['nome'], (string) $linha['usuario']);

        return null;
    }

    /**
     * @return string|null mensagem de erro, ou null em caso de sucesso
     */
    public static function registrar(PDO $pdo, string $nome, string $usuario, string $senha): ?string
    {
        $nome = trim($nome);
        $usuario = self::normalizarUsuario($usuario);

        if (mb_strlen($nome) < 2 || mb_strlen($nome) > 80) {
            return 'O nome precisa ter entre 2 e 80 caracteres.';
        }

        if (preg_match('/^[a-z0-9._-]{3,30}$/', $usuario) !== 1) {
            return 'O usuário deve ter de 3 a 30 caracteres, usando letras, números, ponto, hífen ou sublinhado.';
        }

        if (mb_strlen($senha) < 8) {
            return 'A senha precisa ter pelo menos 8 caracteres.';
        }

        $stmt = $pdo->prepare('SELECT 1 FROM usuarios WHERE usuario = :usuario LIMIT 1');
        $stmt->execute(['usuario' => $usuario]);

        if ($stmt->fetchColumn() !== false) {
            return 'Esse usuário já existe. Tente entrar ou escolha outro.';
        }

        $stmt = $pdo->prepare(
            'INSERT INTO usuarios (nome, usuario, senha)
             VALUES (:nome, :usuario, :senha)'
        );
        $stmt->execute([
            'nome' => $nome,
            'usuario' => $usuario,
            'senha' => password_hash($senha, PASSWORD_DEFAULT),
        ]);

        self::abrirSessao($pdo, (int) $pdo->lastInsertId(), $nome, $usuario);

        return null;
    }

    public static function sair(): void
    {
        Session::esquecer('usuario_id');
        Session::esquecer('usuario_nome');
        Session::esquecer('usuario_login');
        Session::regenerar();
    }

    public static function normalizarUsuario(string $usuario): string
    {
        return mb_strtolower(trim($usuario));
    }

    private static function abrirSessao(PDO $pdo, int $id, string $nome, string $usuario): void
    {
        Session::regenerar();
        Session::set('usuario_id', $id);
        Session::set('usuario_nome', $nome);
        Session::set('usuario_login', $usuario);

        $stmt = $pdo->prepare('UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
