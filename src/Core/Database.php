<?php

declare(strict_types=1);

namespace Core;

use PDO;

/**
 * Conexão PDO única por requisição.
 *
 * - Emulações desligadas (prepared statements reais no servidor).
 * - Erros como exceção.
 * - Fetch associativo.
 * - utf8mb4 em toda a conexão.
 */
final class Database
{
    /** @var array<string, string>|null */
    private static ?array $configuracao = null;
    private static ?PDO $pdo = null;

    /**
     * @param array<string, string> $configuracao
     */
    public static function configurar(array $configuracao): void
    {
        self::$configuracao = $configuracao;
    }

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $c = self::$configuracao;

        if ($c === null) {
            throw new \RuntimeException('Database::configurar() não foi chamado.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $c['host'],
            $c['port'],
            $c['dbname'],
            $c['charset']
        );

        self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]);

        return self::$pdo;
    }

    /** Usado nos testes para injetar/fechar a conexão. */
    public static function resetar(): void
    {
        self::$pdo = null;
    }
}
