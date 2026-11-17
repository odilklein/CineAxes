<?php

declare(strict_types=1);

namespace Models;

use Core\Vetor;
use PDO;

final class Eixo
{
    /** @return list<array<string, mixed>> */
    public static function todos(PDO $pdo): array
    {
        $stmt = $pdo->query(
            'SELECT id, ordem, slug, nome, descricao, polo_esq_key, polo_esq_nome,
                    polo_dir_key, polo_dir_nome, cor_esq, cor_dir,
                    icone_esq, icone_dir, tmdb_genres_esq, tmdb_genres_dir,
                    tmdb_keywords_esq, tmdb_keywords_dir
               FROM eixos
              ORDER BY ordem'
        );

        $linhas = $stmt->fetchAll();

        foreach ($linhas as &$linha) {
            $linha['id'] = (int) $linha['id'];
            $linha['ordem'] = (int) $linha['ordem'];
        }

        return $linhas;
    }

    /**
     * Mapa id => linha, na ordem dos eixos.
     *
     * @param list<array<string, mixed>> $eixos
     * @return array<int, array<string, mixed>>
     */
    public static function porId(array $eixos): array
    {
        $mapa = [];

        foreach ($eixos as $eixo) {
            $mapa[(int) $eixo['id']] = $eixo;
        }

        return $mapa;
    }

    /**
     * Índice do vetor (0..7) para cada id de eixo, segundo a ordem do catálogo.
     *
     * @param list<array<string, mixed>> $eixos
     * @return array<int, int>
     */
    public static function indicesDoVetor(array $eixos): array
    {
        $indices = [];

        foreach (array_values($eixos) as $i => $eixo) {
            $indices[(int) $eixo['id']] = $i;
        }

        return $indices;
    }

    /**
     * Vetor do usuário indexado por id de eixo (útil para exibir rótulos).
     *
     * @param list<array<string, mixed>> $eixos
     * @param array<int, float> $porEixoId
     * @return array<int, float>
     */
    public static function vetorPorId(array $eixos, array $porEixoId): array
    {
        $vetor = [];

        foreach ($eixos as $eixo) {
            $vetor[(int) $eixo['id']] = round((float) ($porEixoId[(int) $eixo['id']] ?? 50), 2);
        }

        return $vetor;
    }

    /** @return list<float> Ordenado como os eixos (eixo_1..eixo_8). */
    public static function vetorOrdenado(array $eixos, array $porEixoId): array
    {
        $vetor = [];

        foreach ($eixos as $eixo) {
            $vetor[] = round((float) ($porEixoId[(int) $eixo['id']] ?? 50), 2);
        }

        return $vetor;
    }
}
