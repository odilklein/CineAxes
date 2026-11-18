<?php

declare(strict_types=1);

namespace Models;

use PDO;

/**
 * Créditos de elenco e direção por obra.
 */
final class CatalogoCredito
{
    /**
     * @param list<int> $obraIds
     * @return array<int, list<array<string, mixed>>>
     */
    public static function porObras(PDO $pdo, array $obraIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $obraIds))));

        if ($ids === []) {
            return [];
        }

        $marcadores = implode(', ', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare(
            'SELECT obra_id, pessoa_id, funcao, personagem, ordem
               FROM catalogo_creditos
              WHERE obra_id IN (' . $marcadores . ')
              ORDER BY obra_id, funcao DESC, ordem'
        );
        $stmt->execute($ids);

        $mapa = [];

        foreach ($stmt->fetchAll() as $linha) {
            $mapa[(int) $linha['obra_id']][] = [
                'pessoa_id'  => (int) $linha['pessoa_id'],
                'funcao'     => (string) $linha['funcao'],
                'personagem' => $linha['personagem'],
                'ordem'      => (int) $linha['ordem'],
            ];
        }

        return $mapa;
    }

    public static function total(PDO $pdo): int
    {
        return (int) $pdo->query('SELECT COUNT(*) FROM catalogo_creditos')->fetchColumn();
    }
}
