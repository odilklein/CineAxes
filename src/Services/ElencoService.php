<?php

declare(strict_types=1);

namespace Services;

/**
 * Elenco relevante para um perfil.
 *
 * Um ator entra na lista pelo que ele faz nos títulos mais próximos do perfil:
 * a força da obra é ponderada pela posição no crédito (protagonista pesa mais
 * que o sexto nome) e pela repetição (quem aparece em vários títulos do gosto
 * sobe). A popularidade entra como ajuste limitado, não como atalho: nomes
 * reconhecíveis ganham um empurrão e algumas vagas são reservadas para eles,
 * mas o perfil ainda manda na maior parte da lista. Também limitamos quantos
 * nomes vêm do mesmo idioma original, para o card não virar só anime japonês
 * quando o perfil tem repertório misto.
 *
 * Recebe arrays prontos (obras já pontuadas, créditos e pessoas) e devolve
 * arrays: testável sem banco.
 */
final class ElencoService
{
    /** Popularidade TMDB a partir da qual um nome conta como reconhecível. */
    private const POPULARIDADE_RECONHECIVEL = 8.0;

    /** Quantos nomes do mesmo idioma original a lista aceita por padrão. */
    private const MAX_POR_IDIOMA = 3;

    /**
     * @param list<array<string, mixed>> $obras     com id, forca, afinidade, titulo
     * @param array<int, list<array<string, mixed>>> $creditos por obra_id
     * @param array<int, array<string, mixed>> $pessoas por pessoa_id
     * @return list<array<string, mixed>>
     */
    public function relevantes(
        array $obras,
        array $creditos,
        array $pessoas,
        int $limite = 8
    ): array {
        $acumulado = [];

        foreach ($obras as $obra) {
            $obraId = (int) ($obra['id'] ?? 0);
            $forca = (float) ($obra['forca'] ?? 0.0);

            if ($obraId <= 0 || $forca <= 0.0) {
                continue;
            }

            $idioma = trim((string) ($obra['idioma_original'] ?? ''));

            foreach ($creditos[$obraId] ?? [] as $credito) {
                if (($credito['funcao'] ?? '') !== 'elenco') {
                    continue;
                }

                $pessoaId = (int) ($credito['pessoa_id'] ?? 0);

                if ($pessoaId <= 0 || !isset($pessoas[$pessoaId])) {
                    continue;
                }

                $ordem = max(0, (int) ($credito['ordem'] ?? 0));
                $pesoOrdem = max(0.25, 1.0 - $ordem * 0.12);
                $acumulado[$pessoaId] ??= [
                    'pessoa_id'    => $pessoaId,
                    'score'        => 0.0,
                    'titulos'      => 0,
                    'obras'        => [],
                    'idiomas'      => [],
                    'popularidade' => max(0.0, (float) ($pessoas[$pessoaId]['popularidade'] ?? 0.0)),
                ];

                $acumulado[$pessoaId]['score'] += $forca * $pesoOrdem;
                $acumulado[$pessoaId]['titulos']++;

                if ($idioma !== '' && !in_array($idioma, $acumulado[$pessoaId]['idiomas'], true)) {
                    $acumulado[$pessoaId]['idiomas'][] = $idioma;
                }

                if (count($acumulado[$pessoaId]['obras']) < 3) {
                    $acumulado[$pessoaId]['obras'][] = [
                        'id'        => $obraId,
                        'titulo'    => (string) ($obra['titulo'] ?? ''),
                        'ano'       => $obra['ano'] ?? null,
                        'personagem' => $credito['personagem'] ?? null,
                        'afinidade' => (float) ($obra['afinidade'] ?? 0.0),
                    ];
                }
            }
        }

        $lista = array_values(array_filter(
            $acumulado,
            static fn (array $item): bool => $item['score'] > 0.08
        ));

        // Ajuste limitado: quem tem 45+ de popularidade ganha no máximo +60%.
        foreach ($lista as &$item) {
            $popularidade = min(1.0, max(0.0, (float) $item['popularidade']) / 45.0);
            $item['score'] *= 1.0 + 0.6 * $popularidade;
            $item['score'] = round($item['score'], 4);
        }
        unset($item);

        usort($lista, static function (array $a, array $b): int {
            return $b['score'] <=> $a['score']
                ?: $b['titulos'] <=> $a['titulos']
                ?: $b['popularidade'] <=> $a['popularidade'];
        });

        $limite = max(1, $limite);
        $minPopulares = min($limite, max(2, intdiv($limite, 2) - 1));

        $lista = $this->selecionar($lista, $limite, $minPopulares);

        foreach ($lista as &$item) {
            $pessoa = $pessoas[$item['pessoa_id']];

            // Ordena as obras pela afinidade para o card mostrar o melhor papel.
            usort($item['obras'], static fn (array $a, array $b): int => $b['afinidade'] <=> $a['afinidade']);

            $item['nome'] = (string) ($pessoa['nome'] ?? '');
            $item['tmdb_id'] = (int) ($pessoa['tmdb_id'] ?? 0);
            $item['foto_path'] = $pessoa['foto_path'] ?? null;
            $item['afinidade_media'] = round(array_sum(array_column($item['obras'], 'afinidade')) / max(1, count($item['obras'])), 1);
            $item['score'] = round($item['score'], 2);
        }
        unset($item);

        return $lista;
    }

    /**
     * Reserva algumas vagas para nomes reconhecíveis, limita a repetição de
     * obras e de idiomas e completa o resto por afinidade. Se o catálogo só
     * tiver um idioma, a última passagem ignora esse limite para não deixar
     * o card vazio.
     *
     * @param list<array<string, mixed>> $lista
     * @return list<array<string, mixed>>
     */
    private function selecionar(array $lista, int $limite, int $minPopulares): array
    {
        $selecionados = [];
        $ids = [];
        $porObra = [];
        $porIdioma = [];

        $cabe = function (array $item, bool $limitarIdioma) use (&$ids, &$porObra, &$porIdioma): bool {
            $id = (int) $item['pessoa_id'];

            if (isset($ids[$id])) {
                return false;
            }

            $obra = (int) ($item['obras'][0]['id'] ?? 0);

            if ($obra > 0 && ($porObra[$obra] ?? 0) >= 2) {
                return false;
            }

            if (!$limitarIdioma) {
                return true;
            }

            $idioma = (string) ($item['idiomas'][0] ?? '');

            return $idioma === '' || ($porIdioma[$idioma] ?? 0) < self::MAX_POR_IDIOMA;
        };

        $guardar = function (array $item) use (&$selecionados, &$ids, &$porObra, &$porIdioma): void {
            $id = (int) $item['pessoa_id'];
            $obra = (int) ($item['obras'][0]['id'] ?? 0);
            $idioma = (string) ($item['idiomas'][0] ?? '');

            $ids[$id] = true;

            if ($obra > 0) {
                $porObra[$obra] = ($porObra[$obra] ?? 0) + 1;
            }

            if ($idioma !== '') {
                $porIdioma[$idioma] = ($porIdioma[$idioma] ?? 0) + 1;
            }

            $selecionados[] = $item;
        };

        $populares = array_values(array_filter(
            $lista,
            static fn (array $item): bool => (float) ($item['popularidade'] ?? 0.0) >= self::POPULARIDADE_RECONHECIVEL
        ));

        foreach ($populares as $item) {
            if (count($selecionados) >= $minPopulares) {
                break;
            }

            if ($cabe($item, true)) {
                $guardar($item);
            }
        }

        foreach ($lista as $item) {
            if (count($selecionados) >= $limite) {
                break;
            }

            if ($cabe($item, true)) {
                $guardar($item);
            }
        }

        if (count($selecionados) < $limite) {
            foreach ($lista as $item) {
                if (count($selecionados) >= $limite) {
                    break;
                }

                if ($cabe($item, false)) {
                    $guardar($item);
                }
            }
        }

        return $selecionados;
    }
}
