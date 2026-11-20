<?php

declare(strict_types=1);

namespace Models;

use PDO;

final class Arquetipo
{
    /** @return list<array<string, mixed>> */
    public static function todos(PDO $pdo): array
    {
        $linhas = $pdo->query(
            'SELECT a.id, a.eixo_dominante_1, a.polo_1, a.eixo_dominante_2, a.polo_2,
                    a.eixo_1, a.eixo_2, a.eixo_3, a.eixo_4, a.eixo_5, a.eixo_6,
                    a.titulo_key, a.descricao_key, a.ritual_cinema_key,
                    a.titulo, a.descricao, a.ritual_cinema
               FROM arquetipos a
              ORDER BY a.eixo_dominante_1, a.polo_1'
        )->fetchAll();

        return array_map([self::class, 'normalizar'], $linhas);
    }

    /** @return array<string, mixed>|null */
    public static function porId(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT id, eixo_dominante_1, polo_1, eixo_dominante_2, polo_2,
                    eixo_1, eixo_2, eixo_3, eixo_4, eixo_5, eixo_6,
                    titulo_key, descricao_key, ritual_cinema_key,
                    titulo, descricao, ritual_cinema
               FROM arquetipos
              WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $linha = $stmt->fetch();

        return $linha === false ? null : self::normalizar($linha);
    }

    /** @param array<string, mixed> $linha @return array<string, mixed> */
    private static function normalizar(array $linha): array
    {
        $linha['id'] = (int) $linha['id'];
        $linha['eixo_dominante_1'] = (int) $linha['eixo_dominante_1'];
        $linha['eixo_dominante_2'] = $linha['eixo_dominante_2'] === null
            ? null
            : (int) $linha['eixo_dominante_2'];
        $linha['vetor'] = \Core\Vetor::daLinha($linha);

        for ($i = 1; $i <= \Core\Vetor::EIXOS; $i++) {
            unset($linha['eixo_' . $i]);
        }

        return $linha;
    }
}
