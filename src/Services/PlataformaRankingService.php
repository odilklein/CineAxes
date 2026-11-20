<?php

declare(strict_types=1);

namespace Services;

/**
 * Ranking de plataformas de streaming para um perfil.
 *
 * Entrada: obras já pontuadas por RecomendacaoService (com `afinidade`,
 * `forca`, `trilha` e `plataformas`) e as plataformas ativas do banco.
 * Saída: pódio geral + melhor plataforma por trilha + mapa "onde assistir".
 *
 * Não acessa banco nem rede. A nota é explicável: volume bruto entra em log,
 * e a relevância usa apenas os melhores títulos do perfil, não a Biblioteca
 * inteira da plataforma.
 */
final class PlataformaRankingService
{
    /** @var array<string, mixed> */
    private readonly array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param list<array<string, mixed>> $obras        obras com plataformas
     * @param list<array<string, mixed>> $plataformas  linhas de catalogo_plataformas
     * @param float|null $afinidadeMinima corte relativo calculado pelo
     *        RecomendacaoService::corteAdaptativo(); null usa o config
     * @return array{geral: list<array<string, mixed>>, por_trilha: array<string, array<string, mixed>>, onde: array<int, list<array<string, mixed>>>, combinacao: array<string, mixed>}
     */
    public function ranquear(array $obras, array $plataformas, ?float $afinidadeMinima = null): array
    {
        $ativas = [];

        foreach ($plataformas as $plataforma) {
            if ((int) ($plataforma['ativa'] ?? 0) !== 1) {
                continue;
            }

            $ativas[(int) $plataforma['id']] = $plataforma;
        }

        $limiares = (array) ($this->config['limiares'] ?? []);
        $afinidadeMinima ??= (float) ($limiares['afinidade'] ?? 62.0);
        $minTitulos = (int) ($limiares['min_titulos_plataforma'] ?? 25);
        $topRelevancia = max(1, (int) ($limiares['top_relevancia'] ?? 25));

        $candidatas = [];

        foreach ($obras as $obra) {
            if ((float) ($obra['forca'] ?? 0.0) <= 0.0) {
                continue;
            }

            if ((float) ($obra['afinidade'] ?? 0.0) < $afinidadeMinima) {
                continue;
            }

            $candidatas[] = $obra;
        }

        // Referência de cobertura: os 50 melhores títulos do perfil.
        usort($candidatas, static function (array $a, array $b): int {
            return ((float) ($b['afinidade'] ?? 0)) <=> ((float) ($a['afinidade'] ?? 0))
                ?: ((float) ($b['popularidade'] ?? 0)) <=> ((float) ($a['popularidade'] ?? 0));
        });

        $referencia = array_slice($candidatas, 0, 50);

        // Exclusividade: quantas plataformas ativas têm este título.
        $contagemPorObra = [];

        foreach ($candidatas as $obra) {
            $ids = array_values(array_intersect(
                $this->idsDaObra($obra),
                array_keys($ativas)
            ));

            if ($ids !== []) {
                $contagemPorObra[(int) $obra['id']] = count(array_unique($ids));
            }
        }

        $precos = [];

        foreach ($ativas as $plataforma) {
            if ($plataforma['preco_mensal'] !== null && is_numeric($plataforma['preco_mensal'])) {
                $precos[] = (float) $plataforma['preco_mensal'];
            }
        }

        $precoMin = $precos !== [] ? min($precos) : 0.0;
        $precoMax = $precos !== [] ? max($precos) : 0.0;

        $trilhasComCard = array_keys(array_filter(
            (array) ($this->config['trilhas'] ?? []),
            static fn (array $trilha): bool => ($trilha['card'] ?? true) !== false
        ));

        $geral = [];

        foreach ($ativas as $id => $plataforma) {
            $titulos = array_values(array_filter(
                $candidatas,
                fn (array $obra): bool => in_array($id, $this->idsDaObra($obra), true)
            ));

            if (count($titulos) < $minTitulos) {
                continue;
            }

            usort($titulos, static function (array $a, array $b): int {
                return ((float) ($b['afinidade'] ?? 0)) <=> ((float) ($a['afinidade'] ?? 0))
                    ?: ((float) ($b['popularidade'] ?? 0)) <=> ((float) ($a['popularidade'] ?? 0));
            });

            $topo = array_slice($titulos, 0, $topRelevancia);
            $somaForca = 0.0;

            foreach ($topo as $obra) {
                $somaForca += ((float) $obra['forca']) ** 1.5;
            }

            $relevancia = 100.0 * $somaForca / ($somaForca + 15.0);
            $cobertura = min(100.0, 100.0 * log(1 + count($titulos)) / log(1 + 400));
            $exclusivos = array_values(array_filter(
                $titulos,
                static fn (array $obra): bool => ($contagemPorObra[(int) $obra['id']] ?? 0) === 1
            ));
            $exclusividade = 100.0 * count($exclusivos) / (count($exclusivos) + 15.0);

            $trilhas = [];

            foreach ($titulos as $obra) {
                $slug = (string) ($obra['trilha'] ?? '');

                if ($slug !== '') {
                    $trilhas[$slug] = ($trilhas[$slug] ?? 0) + 1;
                }
            }

            $trilhasCobertas = 0;

            foreach ($trilhasComCard as $slug) {
                if (($trilhas[$slug] ?? 0) >= 2) {
                    $trilhasCobertas++;
                }
            }

            $baseDiversidade = max(1, min(5, count($trilhasComCard)));
            $diversidade = min(100.0, 100.0 * $trilhasCobertas / $baseDiversidade);

            $preco = 50.0;

            if ($plataforma['preco_mensal'] !== null && $precoMax > $precoMin && is_numeric($plataforma['preco_mensal'])) {
                $valor = (float) $plataforma['preco_mensal'];
                $preco = 100.0 * ($precoMax - $valor) / ($precoMax - $precoMin);
            }

            $pesos = (array) ($this->config['pesos_plataforma'] ?? []);
            $nota = (float) ($pesos['relevancia'] ?? 0.40) * $relevancia
                + (float) ($pesos['cobertura'] ?? 0.25) * $cobertura
                + (float) ($pesos['exclusividade'] ?? 0.15) * $exclusividade
                + (float) ($pesos['diversidade'] ?? 0.10) * $diversidade
                + (float) ($pesos['preco'] ?? 0.10) * $preco;

            arsort($trilhas);

            $geral[] = [
                'id'              => $id,
                'slug'            => (string) $plataforma['slug'],
                'nome'            => (string) $plataforma['nome'],
                'logo_path'       => $plataforma['logo_path'] ?? null,
                'url'             => $plataforma['url'] ?? null,
                'preco_mensal'    => $plataforma['preco_mensal'] !== null ? (float) $plataforma['preco_mensal'] : null,
                'preco_atualizado_em' => $plataforma['preco_atualizado_em'] ?? null,
                'nota'            => round($nota, 1),
                'titulos'         => count($titulos),
                'afinidade_media' => round(array_sum(array_column($titulos, 'afinidade')) / count($titulos), 1),
                'no_top50'        => count(array_filter(
                    $referencia,
                    fn (array $obra): bool => in_array($id, $this->idsDaObra($obra), true)
                )),
                'top50_total'     => count($referencia),
                'exclusivos'      => count($exclusivos),
                'trilhas'         => $trilhas,
                'melhores'        => array_slice($titulos, 0, 3),
                'exemplos_exclusivos' => array_slice($exclusivos, 0, 2),
                'componentes'     => [
                    'relevancia'    => round($relevancia, 1),
                    'cobertura'     => round($cobertura, 1),
                    'exclusividade' => round($exclusividade, 1),
                    'diversidade'   => round($diversidade, 1),
                    'preco'         => round($preco, 1),
                ],
            ];
        }

        usort($geral, static function (array $a, array $b): int {
            return $b['nota'] <=> $a['nota']
                ?: $b['afinidade_media'] <=> $a['afinidade_media']
                ?: $a['titulos'] <=> $b['titulos'];
        });

        // Tamanho total de cada plataforma no catálogo: denominador da
        // especialização usada em "melhor para cada trilha".
        $totalPorPlataforma = [];

        foreach ($obras as $obra) {
            foreach ($this->idsDaObra($obra) as $plataformaId) {
                if (isset($ativas[$plataformaId])) {
                    $totalPorPlataforma[$plataformaId] = ($totalPorPlataforma[$plataformaId] ?? 0) + 1;
                }
            }
        }

        // Uma passada só para montar "trilha × plataforma": quantidade e soma
        // de afinidade. Evita varrer as obras dentro do laço de plataformas.
        $agregado = [];

        foreach ($candidatas as $obra) {
            $slug = (string) ($obra['trilha'] ?? '');

            if ($slug === '' || !in_array($slug, $trilhasComCard, true)) {
                continue;
            }

            foreach ($this->idsDaObra($obra) as $plataformaId) {
                if (!isset($ativas[$plataformaId])) {
                    continue;
                }

                $agregado[$slug][$plataformaId] ??= ['titulos' => 0, 'soma' => 0.0];
                $agregado[$slug][$plataformaId]['titulos']++;
                $agregado[$slug][$plataformaId]['soma'] += (float) ($obra['afinidade'] ?? 0.0);
            }
        }

        $porTrilha = [];

        foreach ($agregado as $slug => $linhas) {
            $melhor = null;

            foreach ($linhas as $plataformaId => $dados) {
                if ($dados['titulos'] < 5 || !isset($ativas[$plataformaId])) {
                    continue;
                }

                $media = round($dados['soma'] / $dados['titulos'], 1);
                $total = max($dados['titulos'], (int) ($totalPorPlataforma[$plataformaId] ?? $dados['titulos']));
                $especializacao = $dados['titulos'] / $total;

                // Especialistas vencem a própria trilha: o acervo total pesa em
                // log, mas a fatia da plataforma naquele nicho pesa mais.
                $notaTrilha = $media * log(1 + $dados['titulos']) * (0.2 + 0.8 * $especializacao);

                if ($melhor === null || $notaTrilha > $melhor['nota']) {
                    $melhor = [
                        'trilha'          => (string) $slug,
                        'plataforma_id'   => (int) $plataformaId,
                        'plataforma'      => (string) $ativas[$plataformaId]['nome'],
                        'plataforma_slug' => (string) $ativas[$plataformaId]['slug'],
                        'titulos'         => (int) $dados['titulos'],
                        'afinidade_media' => $media,
                        'especializacao'  => round(100 * $especializacao, 0),
                        'nota'            => round($notaTrilha, 1),
                    ];
                }
            }

            if ($melhor !== null) {
                $configTrilha = (array) ($this->config['trilhas'][$slug] ?? []);

                $exemplos = [];

                foreach ($candidatas as $obra) {
                    if ((string) ($obra['trilha'] ?? '') !== (string) $slug) {
                        continue;
                    }

                    if (!in_array((int) $melhor['plataforma_id'], $this->idsDaObra($obra), true)) {
                        continue;
                    }

                    $exemplos[] = $obra;
                }

                usort($exemplos, static function (array $a, array $b): int {
                    return ((float) ($b['afinidade'] ?? 0)) <=> ((float) ($a['afinidade'] ?? 0))
                        ?: ((float) ($b['popularidade'] ?? 0)) <=> ((float) ($a['popularidade'] ?? 0));
                });

                $melhor['nome'] = (string) ($configTrilha['nome'] ?? $slug);
                $melhor['icone'] = (string) ($configTrilha['icone'] ?? 'film');
                $melhor['cor'] = (string) ($configTrilha['cor'] ?? '#2f4858');
                $melhor['logo_path'] = $ativas[(int) $melhor['plataforma_id']]['logo_path'] ?? null;
                $melhor['melhores'] = array_slice($exemplos, 0, 2);

                $porTrilha[(string) $slug] = $melhor;
            }
        }

        uasort($porTrilha, static fn (array $a, array $b): int => $b['nota'] <=> $a['nota']);

        return [
            'geral'      => $geral,
            'por_trilha' => $porTrilha,
            'onde'       => $this->ondeAssistir($obras, $ativas),
            'combinacao' => $this->combinacao($candidatas, $ativas),
        ];
    }

    /**
     * Qual combinação de 1 ou 2 serviços cobre mais títulos do perfil.
     *
     * É a pergunta que o usuário faz de verdade ("o que eu assino?"), e não
     * "qual plataforma tem a maior nota". Usa os 50 melhores títulos do
     * perfil, que é o conjunto que importa para a decisão.
     *
     * @param list<array<string, mixed>> $candidatas
     * @param array<int, array<string, mixed>> $ativas
     * @return array<string, mixed>
     */
    private function combinacao(array $candidatas, array $ativas, int $alvo = 50): array
    {
        if ($candidatas === [] || $ativas === []) {
            return [];
        }

        usort($candidatas, static function (array $a, array $b): int {
            return ((float) ($b['afinidade'] ?? 0)) <=> ((float) ($a['afinidade'] ?? 0));
        });

        $topo = array_slice($candidatas, 0, $alvo);
        $total = count($topo);
        $conjuntos = [];

        foreach ($topo as $indice => $obra) {
            foreach ($this->idsDaObra($obra) as $plataformaId) {
                if (isset($ativas[$plataformaId])) {
                    $conjuntos[$plataformaId][$indice] = true;
                }
            }
        }

        if ($conjuntos === []) {
            return [];
        }

        $uma = null;

        foreach ($conjuntos as $plataformaId => $conjunto) {
            if ($uma === null || count($conjunto) > count($uma['conjunto'])) {
                $uma = ['id' => $plataformaId, 'conjunto' => $conjunto];
            }
        }

        $duas = null;

        foreach ($conjuntos as $plataformaId => $conjunto) {
            if ($plataformaId === $uma['id']) {
                continue;
            }

            $uniao = $uma['conjunto'] + $conjunto;

            if ($duas === null || count($uniao) > count($duas['conjunto'])) {
                $duas = ['ids' => [$uma['id'], $plataformaId], 'conjunto' => $uniao];
            }
        }

        $resumo = function (array $ids, array $conjunto) use ($ativas, $total): array {
            $preco = 0.0;
            $nomes = [];

            foreach ($ids as $id) {
                $plataforma = $ativas[$id] ?? null;

                if ($plataforma === null) {
                    continue;
                }

                $nomes[] = (string) $plataforma['nome'];

                if ($plataforma['preco_mensal'] !== null && is_numeric($plataforma['preco_mensal'])) {
                    $preco += (float) $plataforma['preco_mensal'];
                }
            }

            return [
                'plataformas' => $nomes,
                'cobertos'    => count($conjunto),
                'total'       => $total,
                'pct'         => $total > 0 ? (int) round(100 * count($conjunto) / $total) : 0,
                'preco'       => $preco > 0 ? round($preco, 2) : null,
            ];
        };

        return [
            'uma'  => $resumo([$uma['id']], $uma['conjunto']),
            'duas' => $duas !== null ? $resumo($duas['ids'], $duas['conjunto']) : null,
        ];
    }

    /**
     * IDs de plataforma de uma obra.
     *
     * `catalogo_obras` expõe a lista completa em `plataformas` (para os cards) e
     * a lista enxuta em `plataformas_ids` (para o cálculo). Aceita as duas para
     * não quebrar chamadas antigas.
     *
     * @param array<string, mixed> $obra
     * @return list<int>
     */
    private function idsDaObra(array $obra): array
    {
        $bruto = (array) ($obra['plataformas_ids'] ?? $obra['plataformas'] ?? []);
        $ids = [];

        foreach ($bruto as $valor) {
            $id = is_array($valor) ? (int) ($valor['id'] ?? 0) : (int) $valor;

            if ($id > 0) {
                $ids[$id] = true;
            }
        }

        return array_keys($ids);
    }

    /**
     * Mapa obra_id => plataformas ativas, usado nos cards e no quick view.
     *
     * @param list<array<string, mixed>> $obras
     * @param array<int, array<string, mixed>> $ativas
     * @return array<int, list<array<string, mixed>>>
     */
    public function ondeAssistir(array $obras, array $ativas): array
    {
        $mapa = [];

        foreach ($obras as $obra) {
            $ids = array_values(array_intersect(
                $this->idsDaObra($obra),
                array_keys($ativas)
            ));

            if ($ids === []) {
                continue;
            }

            $lista = [];

            foreach ($ids as $id) {
                $lista[] = [
                    'id'     => $id,
                    'nome'   => (string) $ativas[$id]['nome'],
                    'slug'   => (string) $ativas[$id]['slug'],
                    'logo'   => $ativas[$id]['logo_path'] ?? null,
                    'preco'  => $ativas[$id]['preco_mensal'] !== null ? (float) $ativas[$id]['preco_mensal'] : null,
                ];
            }

            usort($lista, static fn (array $a, array $b): int => strcmp($a['nome'], $b['nome']));
            $mapa[(int) $obra['id']] = $lista;
        }

        return $mapa;
    }
}
