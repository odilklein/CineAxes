<?php

declare(strict_types=1);

namespace Models;

/**
 * Texto editorial de cada década (epíteto, curiosidade e filmes-marco).
 *
 * O conteúdo vive em src/Data/decadas.php; aqui só há o acesso com fallback.
 */
final class Decada
{
    /** @var array<int, array<string, mixed>>|null */
    private static ?array $cache = null;

    /** @return array<int, array<string, mixed>> */
    public static function todas(): array
    {
        if (self::$cache === null) {
            /** @var array<int, array<string, mixed>> $mapa */
            $mapa = require __DIR__ . '/../Data/decadas.php';
            self::$cache = $mapa;
        }

        return self::$cache;
    }

    /**
     * Conteúdo da década do ano informado, arredondado para baixo.
     *
     * @return array<string, mixed>
     */
    public static function porAno(int $ano): array
    {
        $decada = max(1900, min(2020, (int) (floor($ano / 10) * 10)));
        $todas = self::todas();

        return ($todas[$decada] ?? []) + [
            'epiteto'    => 'Um repertório que atravessa décadas',
            'descricao'  => 'Filmes de épocas diferentes, unidos pelo seu olhar.',
            'curiosidade' => '',
            'marcos'     => [],
        ];
    }
}
