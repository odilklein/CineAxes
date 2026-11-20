<?php

declare(strict_types=1);

namespace Models;

use PDO;

/**
 * Lista pessoal de filmes/séries: "quero ver" e "já vi".
 *
 * O título/pôster é guardado junto para a lista continuar legível se a obra
 * sair do catálogo. A chave é (usuário, tmdb_id, tipo).
 */
final class Bookmark
{
    public const STATUS = ['quero', 'visto'];

    /**
     * Mapa tmdb_id => status, para marcar os botões na página.
     *
     * @return array<int, string>
     */
    public static function mapa(PDO $pdo, ?int $usuarioId): array
    {
        if ($usuarioId === null) {
            return [];
        }

        $stmt = $pdo->prepare('SELECT tmdb_id, status FROM bookmarks WHERE usuario_id = :usuario');
        $stmt->execute(['usuario' => $usuarioId]);
        $mapa = [];

        foreach ($stmt->fetchAll() as $linha) {
            $mapa[(int) $linha['tmdb_id']] = (string) $linha['status'];
        }

        return $mapa;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listar(PDO $pdo, int $usuarioId, string $status): array
    {
        $status = in_array($status, self::STATUS, true) ? $status : 'quero';

        $stmt = $pdo->prepare(
            'SELECT id, tmdb_id, tipo, status, titulo, ano, poster_path, created_at, updated_at
               FROM bookmarks
              WHERE usuario_id = :usuario
                AND status = :status
              ORDER BY updated_at DESC, id DESC'
        );
        $stmt->execute(['usuario' => $usuarioId, 'status' => $status]);

        return $stmt->fetchAll();
    }

    /**
     * @return array{quero: int, visto: int}
     */
    public static function contar(PDO $pdo, int $usuarioId): array
    {
        $stmt = $pdo->prepare(
            'SELECT status, COUNT(*) AS total
               FROM bookmarks
              WHERE usuario_id = :usuario
              GROUP BY status'
        );
        $stmt->execute(['usuario' => $usuarioId]);
        $contagem = ['quero' => 0, 'visto' => 0];

        foreach ($stmt->fetchAll() as $linha) {
            $status = (string) $linha['status'];

            if (isset($contagem[$status])) {
                $contagem[$status] = (int) $linha['total'];
            }
        }

        return $contagem;
    }

    /**
     * Alterna o estado. Se o status atual for o mesmo, remove; se for o outro,
     * troca. Devolve o status final ou null quando removeu.
     *
     * @param array{tmdb_id: int, tipo: string, status: string, titulo: string, ano?: int|null, poster_path?: string|null} $dados
     */
    public static function alternar(PDO $pdo, int $usuarioId, array $dados): ?string
    {
        $tmdbId = (int) ($dados['tmdb_id'] ?? 0);
        $tipo = (string) ($dados['tipo'] ?? 'filme');
        $status = (string) ($dados['status'] ?? 'quero');
        $titulo = trim((string) ($dados['titulo'] ?? ''));
        $ano = isset($dados['ano']) && is_numeric($dados['ano']) ? (int) $dados['ano'] : null;
        $poster = trim((string) ($dados['poster_path'] ?? ''));

        if ($tmdbId <= 0 || $titulo === '' || !in_array($status, self::STATUS, true)) {
            return null;
        }

        $tipo = $tipo === 'serie' ? 'serie' : 'filme';

        $stmt = $pdo->prepare(
            'SELECT id, status
               FROM bookmarks
              WHERE usuario_id = :usuario
                AND tmdb_id = :tmdb
                AND tipo = :tipo
              LIMIT 1'
        );
        $stmt->execute(['usuario' => $usuarioId, 'tmdb' => $tmdbId, 'tipo' => $tipo]);
        $existente = $stmt->fetch();

        if ($existente !== false) {
            if ((string) $existente['status'] === $status) {
                $stmt = $pdo->prepare('DELETE FROM bookmarks WHERE id = :id AND usuario_id = :usuario');
                $stmt->execute(['id' => (int) $existente['id'], 'usuario' => $usuarioId]);

                return null;
            }

            $stmt = $pdo->prepare(
                'UPDATE bookmarks
                    SET status = :status, titulo = :titulo, ano = :ano, poster_path = :poster
                  WHERE id = :id AND usuario_id = :usuario'
            );
            $stmt->execute([
                'status' => $status,
                'titulo' => $titulo,
                'ano' => $ano,
                'poster' => $poster !== '' ? $poster : null,
                'id' => (int) $existente['id'],
                'usuario' => $usuarioId,
            ]);

            return $status;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO bookmarks (usuario_id, tmdb_id, tipo, status, titulo, ano, poster_path)
             VALUES (:usuario, :tmdb, :tipo, :status, :titulo, :ano, :poster)'
        );
        $stmt->execute([
            'usuario' => $usuarioId,
            'tmdb' => $tmdbId,
            'tipo' => $tipo,
            'status' => $status,
            'titulo' => $titulo,
            'ano' => $ano,
            'poster' => $poster !== '' ? $poster : null,
        ]);

        return $status;
    }

    public static function remover(PDO $pdo, int $usuarioId, int $id): bool
    {
        $stmt = $pdo->prepare('DELETE FROM bookmarks WHERE id = :id AND usuario_id = :usuario');
        $stmt->execute(['id' => $id, 'usuario' => $usuarioId]);

        return $stmt->rowCount() > 0;
    }
}
