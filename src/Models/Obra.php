<?php

declare(strict_types=1);

namespace Models;

use PDO;

final class Obra
{
    /**
     * Obras agrupadas por entidade.
     *
     * @param list<int> $entidades
     * @return array<int, list<array<string, mixed>>>
     */
    public static function porEntidades(PDO $pdo, array $entidades): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $entidades))));

        if ($ids === []) {
            return [];
        }

        $marcadores = implode(', ', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare(
            'SELECT id, entidade_id, titulo, ano, personagem, tmdb_id, poster_url, ordem
               FROM entidades_obras
              WHERE entidade_id IN (' . $marcadores . ')
              ORDER BY entidade_id, ordem, id'
        );
        $stmt->execute($ids);

        $mapa = [];

        foreach ($stmt->fetchAll() as $linha) {
            $linha['id'] = (int) $linha['id'];
            $linha['entidade_id'] = (int) $linha['entidade_id'];
            $linha['ano'] = $linha['ano'] === null ? null : (int) $linha['ano'];
            $linha['tmdb_id'] = $linha['tmdb_id'] === null ? null : (int) $linha['tmdb_id'];
            $mapa[$linha['entidade_id']][] = $linha;
        }

        return $mapa;
    }

    /** @return list<array<string, mixed>> */
    public static function todas(PDO $pdo): array
    {
        return $pdo->query(
            'SELECT o.*, e.nome AS entidade_nome, e.tipo AS entidade_tipo
               FROM entidades_obras o
               JOIN entidades_match e ON e.id = o.entidade_id
              ORDER BY e.tipo, e.nome, o.ordem, o.id'
        )->fetchAll();
    }
}
