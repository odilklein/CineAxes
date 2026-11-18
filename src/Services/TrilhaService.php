<?php

declare(strict_types=1);

namespace Services;

/**
 * Cards de curadoria ("Doramas para você", "Filmes indianos", "Anime"...).
 *
 * A definição de cada trilha é editorial (config/recomendacao.php); o conteúdo
 * é sempre do perfil: títulos do catálogo ordenados por afinidade, com um
 * mínimo de títulos para o card existir. Um card vazio seria pior do que a
 * ausência dele.
 */
final class TrilhaService
{
    /** @var array<string, mixed> */
    private readonly array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param list<array<string, mixed>> $obras  já com `afinidade` e `trilha`
     * @param array<int, list<array<string, mixed>>> $onde mapa "onde assistir"
     * @return list<array<string, mixed>>
     */
    public function montar(array $obras, array $onde = []): array
    {
        $limiares = (array) ($this->config['limiares'] ?? []);
        $afinidadeMinima = (float) ($limiares['afinidade'] ?? 62.0);
        $minTitulos = max(1, (int) ($limiares['min_titulos_trilha'] ?? 3));
        $maxTitulos = max(1, (int) ($limiares['max_titulos_trilha'] ?? 6));
        $maxCards = max(1, (int) ($limiares['max_cards'] ?? 6));

        $porTrilha = [];

        foreach ($obras as $obra) {
            $slug = (string) ($obra['trilha'] ?? '');

            if ($slug === '' || (float) ($obra['afinidade'] ?? 0.0) < $afinidadeMinima) {
                continue;
            }

            if ((float) ($obra['forca'] ?? 0.0) <= 0.0) {
                continue;
            }

            if ($onde !== [] && empty($onde[(int) $obra['id']])) {
                continue;
            }

            $porTrilha[$slug][] = $obra;
        }

        $cards = [];

        foreach ((array) ($this->config['trilhas'] ?? []) as $slug => $trilha) {
            if (($trilha['card'] ?? true) === false) {
                continue;
            }

            $titulos = $porTrilha[(string) $slug] ?? [];

            if (count($titulos) < $minTitulos) {
                continue;
            }

            usort($titulos, static function (array $a, array $b): int {
                return ((float) ($b['afinidade'] ?? 0)) <=> ((float) ($a['afinidade'] ?? 0))
                    ?: ((float) ($b['popularidade'] ?? 0)) <=> ((float) ($a['popularidade'] ?? 0));
            });

            $topo = array_slice($titulos, 0, $maxTitulos);

            foreach ($topo as &$titulo) {
                $titulo['onde'] = $onde[(int) $titulo['id']] ?? [];
            }
            unset($titulo);

            $media = round(array_sum(array_column(array_slice($topo, 0, 3), 'afinidade')) / min(3, count($topo)), 1);
            $ajuste = round(array_sum(array_column(array_slice($topo, 0, 3), 'alinhamento')) / min(3, count($topo)), 3);

            if ($ajuste < (float) ($this->config['alinhamento_minimo'] ?? -0.05)) {
                continue;
            }

            $cards[] = [
                'slug'      => (string) $slug,
                'nome'      => (string) ($trilha['nome'] ?? $slug),
                'descricao' => (string) ($trilha['descricao'] ?? ''),
                'icone'     => (string) ($trilha['icone'] ?? 'film'),
                'cor'       => (string) ($trilha['cor'] ?? '#2f4858'),
                'titulos'   => $topo,
                'total'     => count($titulos),
                'media'     => $media,
                'ajuste'    => $ajuste,
                'encaixe'   => round(50.0 + 50.0 * $ajuste, 1),
            ];
        }

        usort($cards, static function (array $a, array $b): int {
            return $b['ajuste'] <=> $a['ajuste']
                ?: $b['media'] <=> $a['media']
                ?: $b['total'] <=> $a['total']
                ?: strcmp($a['nome'], $b['nome']);
        });

        return array_slice($cards, 0, $maxCards);
    }

    /**
     * Trilha do perfil: a de maior média entre os cards exibidos.
     *
     * @param list<array<string, mixed>> $cards
     * @return array<string, mixed>|null
     */
    public function destaque(array $cards): ?array
    {
        return $cards[0] ?? null;
    }
}
