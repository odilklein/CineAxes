<?php

declare(strict_types=1);

namespace Models;

use PDO;

/**
 * Pessoas do catálogo (elenco e direção dos títulos ingeridos).
 *
 * Separadas de `entidades_match`: aqui não há vetor de 8 eixos nem curadoria;
 * é o elenco real, usado para "Atores que combinam com você".
 */
final class CatalogoPessoa
{
    /**
     * @param list<int> $ids
     * @return array<int, array<string, mixed>>
     */
    public static function porIds(PDO $pdo, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($ids === []) {
            return [];
        }

        $marcadores = implode(', ', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare(
            'SELECT id, tmdb_id, nome, foto_path, popularidade
               FROM catalogo_pessoas
              WHERE id IN (' . $marcadores . ')'
        );
        $stmt->execute($ids);

        $mapa = [];

        foreach ($stmt->fetchAll() as $linha) {
            $id = (int) $linha['id'];
            $linha['id'] = $id;
            $linha['tmdb_id'] = (int) $linha['tmdb_id'];
            $linha['popularidade'] = $linha['popularidade'] === null ? null : (float) $linha['popularidade'];
            $mapa[$id] = $linha;
        }

        return $mapa;
    }

    public static function total(PDO $pdo): int
    {
        return (int) $pdo->query('SELECT COUNT(*) FROM catalogo_pessoas')->fetchColumn();
    }
}
