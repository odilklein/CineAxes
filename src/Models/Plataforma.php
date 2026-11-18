<?php

declare(strict_types=1);

namespace Models;

use PDO;

/**
 * Plataformas de streaming que entram no ranking.
 *
 * O preço é editorial (não vem da TMDB) e é editado no painel. `ativa = 0`
 * remove a plataforma do ranking sem apagar a disponibilidade já coletada.
 */
final class Plataforma
{
    private const COLUNAS = 'id, tmdb_provider_id, slug, nome, logo_path, preco_mensal,
                    preco_atualizado_em, url, prioridade, ativa, atualizado_em';

    /** @return list<array<string, mixed>> */
    public static function todas(PDO $pdo, bool $somenteAtivas = false): array
    {
        $sql = 'SELECT ' . self::COLUNAS . ' FROM catalogo_plataformas';

        if ($somenteAtivas) {
            $sql .= ' WHERE ativa = 1';
        }

        $sql .= ' ORDER BY prioridade, nome';

        return array_map(
            [self::class, 'normalizar'],
            $pdo->query($sql)->fetchAll()
        );
    }

    /**
     * @param array<string, mixed> $dados
     */
    public static function atualizar(PDO $pdo, int $id, array $dados): void
    {
        $campos = [];
        $params = ['id' => $id];

        foreach (['nome', 'preco_mensal', 'preco_atualizado_em', 'url', 'prioridade', 'ativa'] as $campo) {
            if (!array_key_exists($campo, $dados)) {
                continue;
            }

            $campos[] = $campo . ' = :' . $campo;
            $params[$campo] = $dados[$campo];
        }

        if ($campos === []) {
            return;
        }

        $campos[] = 'atualizado_em = NOW()';

        $stmt = $pdo->prepare(
            'UPDATE catalogo_plataformas SET ' . implode(', ', $campos) . ' WHERE id = :id'
        );
        $stmt->execute($params);
    }

    /**
     * Atualiza o logo_path vindo da TMDB sem tocar no preço editorial.
     */
    public static function atualizarLogo(PDO $pdo, int $id, ?string $logoPath): void
    {
        $stmt = $pdo->prepare(
            'UPDATE catalogo_plataformas
                SET logo_path = :logo, atualizado_em = NOW()
              WHERE id = :id'
        );
        $stmt->execute(['logo' => $logoPath, 'id' => $id]);
    }

    /**
     * @param array<string, mixed> $linha
     * @return array<string, mixed>
     */
    private static function normalizar(array $linha): array
    {
        $linha['id'] = (int) $linha['id'];
        $linha['tmdb_provider_id'] = (int) $linha['tmdb_provider_id'];
        $linha['prioridade'] = (int) $linha['prioridade'];
        $linha['ativa'] = (int) $linha['ativa'];
        $linha['preco_mensal'] = $linha['preco_mensal'] === null ? null : (float) $linha['preco_mensal'];

        return $linha;
    }
}
