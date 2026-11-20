<?php

declare(strict_types=1);

namespace Models;

use Core\Vetor;
use PDO;

final class EntidadeMatch
{
    private const COLUNAS = 'id, tipo, nome, obra_origem, ano, frase_key, frase, bio, pais,
                    nascimento, ator_nome, ator_tmdb_id, ator_foto_url, imagem_url,
                    popularidade, tmdb_id, enriquecido_em,
                    eixo_1, eixo_2, eixo_3, eixo_4, eixo_5, eixo_6, eixo_7, eixo_8';

    /** @return list<array<string, mixed>> */
    public static function todas(PDO $pdo): array
    {
        $linhas = $pdo->query(
            'SELECT ' . self::COLUNAS . '
               FROM entidades_match
              ORDER BY tipo, nome'
        )->fetchAll();

        return array_map([self::class, 'normalizar'], $linhas);
    }

    /** @return list<array<string, mixed>> */
    public static function porTipo(PDO $pdo, string $tipo): array
    {
        $stmt = $pdo->prepare(
            'SELECT ' . self::COLUNAS . '
               FROM entidades_match
              WHERE tipo = :tipo
              ORDER BY nome'
        );
        $stmt->execute(['tipo' => $tipo]);

        return array_map([self::class, 'normalizar'], $stmt->fetchAll());
    }

    /** @param array<string, mixed> $linha @return array<string, mixed> */
    private static function normalizar(array $linha): array
    {
        $linha['id'] = (int) $linha['id'];
        $linha['ano'] = $linha['ano'] === null ? null : (int) $linha['ano'];
        $linha['nascimento'] = $linha['nascimento'] === null ? null : (int) $linha['nascimento'];
        $linha['ator_tmdb_id'] = $linha['ator_tmdb_id'] === null ? null : (int) $linha['ator_tmdb_id'];
        $linha['tmdb_id'] = $linha['tmdb_id'] === null ? null : (int) $linha['tmdb_id'];
        $linha['popularidade'] = $linha['popularidade'] === null ? null : (float) $linha['popularidade'];
        // O vetor editorial de 8 eixos fica como legado; diretores e escolas
        // recebem um vetor de 6 eixos calculado da filmografia no controller.
        $linha['vetor'] = Vetor::daLinhaLegado($linha);
        $linha['vetor6'] = Vetor::projetar6($linha['vetor']);

        for ($i = 1; $i <= Vetor::EIXOS_LEGADO; $i++) {
            unset($linha['eixo_' . $i]);
        }

        return $linha;
    }
}
