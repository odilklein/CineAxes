<?php

declare(strict_types=1);

namespace Core;

/**
 * Vetores do modelo ativo (6 eixos) e do modelo legado (8 eixos).
 *
 * O modelo v2 mede seis eixos:
 *   1 Estética (Popular ↔ Autoral)
 *   2 Tom (Claro ↔ Sombrio)
 *   3 Mundo (Real ↔ Imaginário)
 *   4 Ritmo (Calmo ↔ Intenso)
 *   5 Escala (Íntimo ↔ Épico)
 *   6 Amplitude (Circunscrito ↔ Omnívoro)
 *
 * O catálogo e as entidades antigas continuam guardando 8 coordenadas. Elas
 * são projetadas para 6 por `projetar6()` na leitura; não é preciso reprojetar
 * as 4.040 obras nem editar o snapshot do catálogo.
 */
final class Vetor
{
    /** Modelo ativo. */
    public const EIXOS = 6;

    /** Modelo antigo, usado apenas para ler catálogo/entidades legadas. */
    public const EIXOS_LEGADO = 8;

    /** @param array<string, mixed> $linha @return list<float> */
    public static function daLinha(array $linha): array
    {
        $vetor = [];

        for ($i = 1; $i <= self::EIXOS; $i++) {
            $vetor[] = round((float) ($linha['eixo_' . $i] ?? 50), 2);
        }

        return $vetor;
    }

    /** @param array<string, mixed> $linha @return list<float> */
    public static function daLinhaLegado(array $linha): array
    {
        $vetor = [];

        for ($i = 1; $i <= self::EIXOS_LEGADO; $i++) {
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

    /**
     * @param list<float|int> $vetor
     * @return array<string, float>
     */
    public static function paraColunasLegado(array $vetor): array
    {
        $colunas = [];

        for ($i = 1; $i <= self::EIXOS_LEGADO; $i++) {
            $colunas['eixo_' . $i] = round((float) ($vetor[$i - 1] ?? 50), 2);
        }

        return $colunas;
    }

    /**
     * Projeta o vetor legado de 8 eixos no modelo ativo de 6.
     *
     * Mapeamento editorial:
     *   Estética = média(Imagem, História)
     *   Tom      = 100 − média(Emoção, Ética)  (0 = claro, 100 = sombrio)
     *   Mundo    = Mundo
     *   Ritmo    = Ritmo
     *   Escala   = Tamanho
     *   Amplitude= neutro aqui; `CatalogoObra` substitui por um escore de
     *              amplitude calculado com país, idioma, década e gênero.
     *
     * @param list<float|int> $legado
     * @return list<float>
     */
    public static function projetar6(array $legado): array
    {
        $e = static fn (int $indice): float => (float) ($legado[$indice] ?? 50.0);

        return [
            round(($e(0) + $e(5)) / 2, 2),
            round(100.0 - (($e(1) + $e(4)) / 2), 2),
            round($e(2), 2),
            round($e(3), 2),
            round($e(6), 2),
            50.0,
        ];
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
