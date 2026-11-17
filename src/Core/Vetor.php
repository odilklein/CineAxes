<?php

declare(strict_types=1);

namespace Core;

/**
 * Converte linhas com eixo_1..eixo_8 em vetores numéricos e vice-versa.
 * O índice 0 do vetor corresponde ao eixo_1 (menor `ordem`).
 */
final class Vetor
{
    public const EIXOS = 8;

    /** @param array<string, mixed> $linha @return list<float> */
    public static function daLinha(array $linha): array
    {
        $vetor = [];

        for ($i = 1; $i <= self::EIXOS; $i++) {
            $vetor[] = round((float) ($linha['eixo_' . $i] ?? 50), 2);
        }

        return $vetor;
    }

    /**
     * @param list<float|int> $vetor
     * @return array<string, float>
     */
    public static function paraColunas(array $vetor): array
    {
        $colunas = [];

        for ($i = 1; $i <= self::EIXOS; $i++) {
            $colunas['eixo_' . $i] = round((float) ($vetor[$i - 1] ?? 50), 2);
        }

        return $colunas;
    }

    /** @param list<float|int> $vetor */
    public static function valido(array $vetor): bool
    {
        if (count($vetor) !== self::EIXOS) {
            return false;
        }

        foreach ($vetor as $valor) {
            if (!is_numeric($valor) || $valor < 0 || $valor > 100) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ponto médio entre dois perfis (usado no Modo Sinergia).
     *
     * @param list<float> $a
     * @param list<float> $b
     * @return list<float>
     */
    public static function media(array $a, array $b): array
    {
        $media = [];

        for ($i = 0; $i < self::EIXOS; $i++) {
            $media[] = round((($a[$i] ?? 50) + ($b[$i] ?? 50)) / 2, 2);
        }

        return $media;
    }
}
