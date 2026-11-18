<?php

declare(strict_types=1);

namespace Services;

/**
 * Elenco relevante para um perfil.
 *
 * Um ator entra na lista pelo que ele faz nos títulos mais próximos do perfil:
 * a força da obra é ponderada pela posição no crédito (protagonista pesa mais
 * que o sexto nome) e pela repetição (quem aparece em vários títulos do gosto
 * sobe). Não usa popularidade global como atalho.
 *
 * Recebe arrays prontos (obras já pontuadas, créditos e pessoas) e devolve
 * arrays: testável sem banco.
 */
final class ElencoService
{
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
                    'pessoa_id' => $pessoaId,
                    'score'     => 0.0,
                    'titulos'   => 0,
                    'obras'     => [],
                ];

                $acumulado[$pessoaId]['score'] += $forca * $pesoOrdem;
                $acumulado[$pessoaId]['titulos']++;

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

        usort($lista, static function (array $a, array $b): int {
            return $b['score'] <=> $a['score']
                ?: $b['titulos'] <=> $a['titulos'];
        });

        // Evita que um único título ocupe o card inteiro (três atores da mesma
        // série): no máximo dois nomes por obra principal.
        $selecionados = [];
        $porObra = [];

        foreach ($lista as $item) {
            $obraPrincipal = (int) ($item['obras'][0]['id'] ?? 0);

            if (($porObra[$obraPrincipal] ?? 0) >= 2) {
                continue;
            }

            $porObra[$obraPrincipal] = ($porObra[$obraPrincipal] ?? 0) + 1;
            $selecionados[] = $item;

            if (count($selecionados) >= max(1, $limite)) {
                break;
            }
        }

        $lista = $selecionados;

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
}
