<?php

declare(strict_types=1);

namespace Controllers;

use Models\Arquetipo;
use Models\CatalogoCredito;
use Models\CatalogoObra;
use Models\CatalogoPessoa;
use Models\Decada;
use Models\Eixo;
use Models\EntidadeMatch;
use Models\FilmeCard;
use Models\Obra;
use Models\Pergunta;
use Models\Plataforma;
use Models\Retrato;
use Core\Vetor;
use Models\Resultado;
use PDO;
use Services\CalibracaoService;
use Services\ElencoService;
use Services\LikertCalculatorService;
use Services\LeituraService;
use Services\MatcherService;
use Services\PlataformaRankingService;
use Services\RecomendacaoService;
use Services\NarrativaService;
use Services\TrilhaService;

/**
 * Monta o "view model" do dashboard de perfil e da sinergia.
 * As páginas ficam só com apresentação.
 */
final class PerfilController
{
    /** @var array<string, mixed> */
    private readonly array $leiturasConfig;

    /** @var array<string, mixed> */
    private readonly array $recomendacaoConfig;

    private readonly RecomendacaoService $recomendacao;

    private readonly CalibracaoService $calibracao;

    public function __construct(
        private readonly PDO $pdo,
        private readonly MatcherService $matcher,
        private readonly LikertCalculatorService $likert,
        private readonly array $config
    ) {
        $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
        $this->leiturasConfig = require $base . '/config/leituras.php';
        $this->recomendacaoConfig = require $base . '/config/recomendacao.php';
        $this->recomendacao = new RecomendacaoService($this->recomendacaoConfig, $matcher);
        $this->calibracao = new CalibracaoService($matcher);
    }

    /**
     * @param bool $comCatalogo false na landing, no retrato e na sinergia:
     *                          essas telas não usam ranking nem cards.
     * @return array<string, mixed>|null
     */
    public function montar(string $codigo, bool $comCatalogo = true, string $mix = 'misturado'): ?array
    {
        $codigo = strtoupper(trim($codigo));

        if (!preg_match('/^[A-Z0-9]{8}$/', $codigo)) {
            return null;
        }

        $resultado = Resultado::porCodigo($this->pdo, $codigo);

        if ($resultado === null) {
            return null;
        }

        $eixos = Eixo::todos($this->pdo);
        $vetor = $resultado['vetor'];
        $eixoIds = array_map(static fn (array $e): int => (int) $e['id'], $eixos);

        $arquetipos = Arquetipo::todos($this->pdo);
        $resolucao = $this->matcher->resolverArquetipo($vetor, $arquetipos, $eixoIds);

        // O cálculo atual tem prioridade. O rótulo salvo poderia contradizer a
        // distribuição exibida quando o limiar de neutro ou o questionário
        // mudam; ele fica como fallback para perfis que hoje são neutros.
        $arquetipo = !$resolucao['neutro'] && $resolucao['arquetipo'] !== null
            ? $resolucao['arquetipo']
            : ($resultado['arquetipo_id'] !== null
                ? Arquetipo::porId($this->pdo, (int) $resultado['arquetipo_id'])
                : null);

        // Catálogo carregado uma vez quando a tela usa filmes/recomendações.
        $obrasCatalogo = $comCatalogo ? CatalogoObra::todos($this->pdo, true) : [];
        $mapaObras = [];

        foreach ($obrasCatalogo as $obra) {
            $mapaObras[(int) $obra['tmdb_id']] = $obra;
        }

        $entidades = $this->comFilmografia(EntidadeMatch::todas($this->pdo), $mapaObras);

        // Diretores e escolas são comparados dentro do próprio grupo. Personagens
        // saíram do matching: não temos preferências cinematográficas medidas deles.
        $diretores = $this->calibracao->anotar($this->matcher->ranquear(
            $vetor,
            array_values(array_filter(
                $entidades,
                static fn (array $entidade): bool => ($entidade['tipo'] ?? '') === 'diretor'
            ))
        ));
        $estudios = $this->calibracao->anotar($this->matcher->ranquear(
            $vetor,
            array_values(array_filter(
                $entidades,
                static fn (array $entidade): bool => ($entidade['tipo'] ?? '') === 'estudio'
            ))
        ));

        // Respostas agrupadas por eixo, carregadas uma vez por perfil.
        $porEixo = $this->respostasPorEixo($resultado);
        $estabilidade = $this->estabilidade($porEixo, $resultado, $eixos, $diretores);

        if ($estabilidade !== null) {
            foreach ($diretores as &$item) {
                $dados = $estabilidade['itens'][(string) $item['id']] ?? null;

                if ($dados !== null) {
                    $item['posicao_mediana'] = (int) $dados['mediana'];
                    $item['posicao_ic'] = $dados['ic95'];
                }
            }
            unset($item);
        }

        $confianca = [
            'rotulo' => $this->calibracao->rotuloConfianca($estabilidade['top1_fracao'] ?? null),
            'fracao' => $estabilidade['top1_fracao'] ?? null,
            'iteracoes' => $estabilidade['iteracoes'] ?? 0,
            'por_eixo' => $estabilidade['por_eixo'] ?? null,
            'top1' => $estabilidade['top1'] ?? null,
        ];

        $diretor = $diretores[0] ?? null;
        $estudio = $estudios[0] ?? null;

        $idsAncoras = array_values(array_filter([
            $diretor['id'] ?? null,
            $estudio['id'] ?? null,
        ]));

        $obrasAncoras = $idsAncoras !== [] ? Obra::porEntidades($this->pdo, $idsAncoras) : [];

        foreach ([&$diretor, &$estudio] as &$ancora) {
            if ($ancora !== null) {
                $ancora['obras'] = $obrasAncoras[(int) $ancora['id']] ?? [];
                $ancora['polos'] = entidade_polos($ancora, $eixos);
                $ancora['motivos'] = $this->explicar($vetor, $ancora, $eixos);
            }
        }
        unset($ancora);

        // Personagens não entram no matching; são usados apenas como metadados
        // dos filmes que combinam com o perfil.
        $personagens = [];
        $atores = [];
        $estante = [];
        $filmesTop = [];

        $linhas = $this->linhasEixos($eixos, $vetor);
        $consistencia = $this->consistencia($porEixo, $linhas);

        $limiteRaridade = (int) ($this->config['raridade']['max_amostra'] ?? 5000);
        $vetoresBase = Resultado::vetores($this->pdo, $limiteRaridade, $codigo);
        $raridade = $this->matcher->raridade($vetor, $vetoresBase);
        $raridade['reais'] = Resultado::totalReais($this->pdo);

        // Base sintética é prior, não população: o selo só é confiável quando
        // houver gente real suficiente, não apenas linhas simuladas.
        $raridade['confiavel'] = $raridade['confiavel']
            && $raridade['reais'] >= (int) ($this->config['raridade']['min_base'] ?? 100);

        $catalogo = $comCatalogo
            ? $this->montarCatalogo($vetor, $obrasCatalogo, $diretor, $estudio, $mix, $entidades)
            : [
                'ranking' => [], 'cards' => [], 'onde' => [], 'elenco' => [],
                'decada_obras' => [], 'total' => 0, 'por_trilha' => [], 'combinacao' => [],
                'filmes_top' => [], 'estante' => [], 'descobertas' => [],
            ];

        $estante = $catalogo['estante'] ?? [];
        $filmesTop = $catalogo['filmes_top'] ?? [];

        $insights = $this->insights(
            $linhas,
            $vetor,
            $vetoresBase,
            $arquetipos,
            $arquetipo,
            $estante
        );

        if ($insights['decada'] !== null && ($catalogo['decada_obras'] ?? []) !== []) {
            $obrasDecada = $catalogo['decada_obras'];
            $vistos = [];

            foreach ($obrasDecada as $obraDecada) {
                $vistos[mb_strtolower(trim((string) $obraDecada['titulo']))] = true;
            }

            // Completa com a estante do espelho se o catálogo tiver menos de 6.
            foreach ($insights['decada']['obras'] as $obraEstante) {
                if (count($obrasDecada) >= 6) {
                    break;
                }

                $chave = mb_strtolower(trim((string) ($obraEstante['titulo'] ?? '')));

                if ($chave === '' || isset($vistos[$chave])) {
                    continue;
                }

                $obrasDecada[] = $obraEstante;
                $vistos[$chave] = true;
            }

            $insights['decada']['obras'] = array_slice($obrasDecada, 0, 8);
            $insights['decada']['fonte'] = 'catalogo';
        }

        $afinidadeMedia = 0.0;

        $leituras = (new LeituraService($this->leiturasConfig))->selecionar($linhas, []);
        $leituras = $this->exemplosLeituras($leituras, $filmesTop, $eixos);

        return [
            'resultado'       => $resultado,
            'vetor'           => $vetor,
            'eixos'           => $eixos,
            'linhas'          => $linhas,
            'leituras'        => $leituras,
            'arquetipo'       => $arquetipo,
            'resolucao'       => $resolucao,
            'arquetipo_distribuicao' => $resolucao['distribuicao'] ?? [],
            'neutro'          => $arquetipo === null,
            'personagens'     => $personagens,
            'atores'          => $atores,
            'diretor'         => $diretor,
            'estudio'         => $estudio,
            'estante'         => $estante,
            'retrato'         => $this->retrato($linhas, $resolucao),
            'afinidade_media' => $afinidadeMedia,
            'raridade'        => $raridade,
            'confianca'       => $confianca,
            'consistencia'    => $consistencia,
            'base_total'      => Resultado::total($this->pdo),
            'filmes_top'      => $filmesTop,
            'descobertas'     => $catalogo['descobertas'] ?? [],
            'mix'             => $mix,
            'filmes_total'    => count($filmesTop),
            'retrato_salvo'   => Retrato::porCodigo($this->pdo, $codigo, NarrativaService::PROMPT_VERSAO),
            'insights'        => $insights,
            'plataformas'     => $catalogo['ranking'],
            'plataformas_trilha' => $catalogo['por_trilha'],
            'combinacao'      => $catalogo['combinacao'] ?? [],
            'trilhas'         => $catalogo['cards'],
            'elenco'          => $catalogo['elenco'] ?? [],
            'trilhas_nomes'   => array_map(
                static fn (array $trilha): string => (string) ($trilha['nome'] ?? ''),
                (array) ($this->recomendacaoConfig['trilhas'] ?? [])
            ),
            'catalogo_total'  => $catalogo['total'],
            'onde_assistir'   => $catalogo['onde'],
        ];
    }

    /**
     * Comparação entre dois perfis (Modo Sinergia).
     *
     * @return array<string, mixed>|null
     */
    public function comparar(string $codigoA, string $codigoB): ?array
    {
        $a = $this->montar($codigoA, false);
        $b = $this->montar($codigoB, false);

        if ($a === null || $b === null) {
            return null;
        }

        $comparacao = $this->matcher->comparar($a['vetor'], $b['vetor']);

        // Referência honesta: como dois perfis ao acaso se comparam? Sem isso,
        // "78% de compatibilidade" parece muito, mas o par aleatório já fica
        // perto de 60.
        $vetores = Resultado::vetores($this->pdo, 500, $codigoA);
        $amostra = [];
        $quantidade = count($vetores);

        for ($i = 0; $i < 200 && $quantidade > 1; $i++) {
            $amostra[] = $this->matcher->compatibilidade(
                $vetores[random_int(0, $quantidade - 1)],
                $vetores[random_int(0, $quantidade - 1)]
            );
        }

        sort($amostra);
        $comparacao['baseline'] = $amostra !== [] ? round($amostra[intdiv(count($amostra), 2)], 1) : null;
        $comparacao['percentil'] = $amostra !== []
            ? (int) round(100 * count(array_filter($amostra, static fn (float $n): bool => $n < $comparacao['compatibilidade'])) / count($amostra))
            : null;

        $entidades = EntidadeMatch::todas($this->pdo);
        $diretoresCombinados = $this->calibracao->anotar($this->matcher->ranquear(
            $comparacao['media'],
            array_values(array_filter(
                $entidades,
                static fn (array $item): bool => ($item['tipo'] ?? '') === 'diretor'
            ))
        ));
        $estudiosCombinados = $this->calibracao->anotar($this->matcher->ranquear(
            $comparacao['media'],
            array_values(array_filter(
                $entidades,
                static fn (array $item): bool => ($item['tipo'] ?? '') === 'estudio'
            ))
        ));

        return [
            'a'          => $a,
            'b'          => $b,
            'comparacao' => $comparacao,
            'combinado'  => [
                'vetor'   => $comparacao['media'],
                'diretor' => $diretoresCombinados[0] ?? null,
                'estudio' => $estudiosCombinados[0] ?? null,
            ],
        ];
    }

    /**
     * Ranking de plataformas, cards de trilha e disponibilidade das obras que
     * já aparecem no perfil.
     *
     * O catálogo é carregado uma vez (4 consultas) e pontuado em memória. Só
     * entram títulos com pelo menos uma plataforma ativa: um card sem ação
     * ("onde assistir") vira ruído.
     *
     * @param list<float> $vetor
     * @param list<array<string, mixed>> $estante
     * @param list<array<string, mixed>> $personagens
     * @param array<string, mixed>|null $diretor
     * @param array<string, mixed>|null $estudio
     * @return array{ranking: list<array<string, mixed>>, por_trilha: array<string, array<string, mixed>>, cards: list<array<string, mixed>>, onde: array<int, list<array<string, mixed>>>, total: int}
     */
    /**
     * Substitui o vetor editorial de diretores/escolas pelo centroide da filmografia.
     *
     * @param list<array<string, mixed>> $entidades
     * @param array<int, array<string, mixed>> $mapaObras tmdb_id => obra do catálogo
     * @return list<array<string, mixed>>
     */
    private function comFilmografia(array $entidades, array $mapaObras): array
    {
        if ($mapaObras === []) {
            return $entidades;
        }

        $ids = array_map(static fn (array $entidade): int => (int) $entidade['id'], $entidades);
        $marcadores = implode(',', array_fill(0, count($ids), '?'));

        try {
            $stmt = $this->pdo->prepare(
                'SELECT entidade_id, tmdb_id
                   FROM entidades_obras
                  WHERE tmdb_id IS NOT NULL
                    AND entidade_id IN (' . $marcadores . ')'
            );
            $stmt->execute($ids);
            $porEntidade = [];

            foreach ($stmt->fetchAll() as $linha) {
                $porEntidade[(int) $linha['entidade_id']][] = (int) $linha['tmdb_id'];
            }
        } catch (\Throwable $erro) {
            return $entidades;
        }

        foreach ($entidades as &$entidade) {
            if (($entidade['tipo'] ?? '') === 'personagem') {
                continue;
            }

            $idsObras = $porEntidade[(int) $entidade['id']] ?? [];
            $soma = array_fill(0, Vetor::EIXOS, 0.0);
            $pesoTotal = 0.0;
            $filmografia = [];

            foreach ($idsObras as $tmdb) {
                $obra = $mapaObras[$tmdb] ?? null;

                if ($obra === null) {
                    continue;
                }

                $peso = max(0.25, ((int) ($obra['confianca'] ?? 50)) / 100);

                foreach ($obra['vetor'] as $indice => $valor) {
                    $soma[$indice] += (float) $valor * $peso;
                }

                $pesoTotal += $peso;
                $filmografia[] = [
                    'titulo' => (string) $obra['titulo'],
                    'ano' => $obra['ano'],
                    'tmdb' => (int) $obra['tmdb_id'],
                    'poster' => $obra['poster_path'] ?? null,
                ];
            }

            if ($pesoTotal > 0) {
                $entidade['vetor6'] = array_map(
                    static fn (float $valor): float => round($valor / $pesoTotal, 2),
                    $soma
                );
                $entidade['filmografia'] = $filmografia;
            }
        }
        unset($entidade);

        return $entidades;
    }

    /**
     * Troca os exemplos fixos das leituras por filmes do próprio perfil.
     *
     * @param list<array<string, mixed>> $leituras
     * @param list<array<string, mixed>> $filmes
     * @param list<array<string, mixed>> $eixos
     * @return list<array<string, mixed>>
     */
    private function exemplosLeituras(array $leituras, array $filmes, array $eixos): array
    {
        if ($leituras === [] || $filmes === []) {
            return $leituras;
        }

        $indicePorSlug = [];

        foreach ($eixos as $indice => $eixo) {
            $indicePorSlug[(string) ($eixo['slug'] ?? '')] = $indice;
        }

        $usados = [];

        foreach ($leituras as &$leitura) {
            $indice = $indicePorSlug[(string) ($leitura['slug'] ?? '')] ?? null;

            if ($indice === null) {
                continue;
            }

            $lado = ($leitura['lado'] ?? 'esq') === 'dir' ? 'dir' : 'esq';
            $exemplos = [];

            foreach ($filmes as $filme) {
                $chave = mb_strtolower(trim((string) ($filme['titulo'] ?? '')));

                if ($chave === '' || isset($usados[$chave])) {
                    continue;
                }

                $valor = (float) ($filme['vetor'][$indice] ?? 50);

                if ($lado === 'dir' && $valor < 55) {
                    continue;
                }

                if ($lado === 'esq' && $valor > 45) {
                    continue;
                }

                $exemplos[] = [
                    'titulo' => (string) $filme['titulo'],
                    'ano' => $filme['ano'],
                    'tmdb' => (int) ($filme['tmdb_id'] ?? 0),
                    'poster' => tmdb_img($filme['poster_path'] ?? null, 'w342'),
                ];

                $usados[$chave] = true;

                if (count($exemplos) >= 2) {
                    break;
                }
            }

            if ($exemplos !== []) {
                $leitura['combina'] = $exemplos;
            }
        }
        unset($leitura);

        return $leituras;
    }

    /**
     * Ranking do catálogo com mistura de descoberta, diversidade e 10 filmes.
     *
     * @param list<float> $vetor
     * @param list<array<string, mixed>> $obras catálogo já carregado
     * @param array<string, mixed>|null $diretor
     * @param array<string, mixed>|null $estudio
     * @param list<array<string, mixed>> $entidades personagens usados como metadados
     * @return array<string, mixed>
     */
    private function montarCatalogo(
        array $vetor,
        array $obras,
        ?array $diretor,
        ?array $estudio,
        string $mix,
        array $entidades
    ): array {
        try {
            $plataformas = Plataforma::todas($this->pdo, true);
        } catch (\Throwable $erro) {
            error_log('[PersonaCut][catalogo] ' . $erro->getMessage());

            return $this->catalogoVazio();
        }

        if ($obras === [] || $plataformas === []) {
            return $this->catalogoVazio(count($obras));
        }

        $qualidade = (array) ($this->config['descoberta'] ?? []);
        $notaMinima = (float) ($qualidade['nota_minima'] ?? 6.5);
        $votosMinimos = (int) ($qualidade['votos_minimos'] ?? 500);
        $notaDescoberta = (float) ($qualidade['nota_descoberta'] ?? 7.0);
        $votosDescoberta = (int) ($qualidade['votos_descoberta'] ?? 1000);

        $obras = array_values(array_filter(
            $obras,
            static fn (array $obra): bool => ($obra['nota'] ?? 0) >= $notaMinima
                && ($obra['votos'] ?? 0) >= $votosMinimos
        ));

        if ($obras === []) {
            return $this->catalogoVazio();
        }

        $ranqueadas = $this->calibracao->anotar($this->recomendacao->rankear($vetor, $obras));

        foreach ($ranqueadas as &$obra) {
            $obra['trilha'] = $this->recomendacao->trilhaDe($obra);
        }
        unset($obra);

        $corte = $this->recomendacao->corteAdaptativo($ranqueadas);
        $mistura = $this->selecionarMix($ranqueadas, $mix, 160);

        // A seção "10 filmes" é de filmes; séries continuam nas trilhas e no
        // streaming. Se o catálogo não tiver filmes suficientes, aceita séries.
        $filmesMistura = array_values(array_filter(
            $mistura,
            static fn (array $obra): bool => (string) ($obra['tipo'] ?? 'filme') === 'filme'
        ));
        $filmesTop = $this->selecionarDiverso($filmesMistura !== [] ? $filmesMistura : $mistura, 10);

        $ranking = (new PlataformaRankingService($this->recomendacaoConfig))
            ->ranquear($ranqueadas, $plataformas, $corte);
        $cards = (new TrilhaService($this->recomendacaoConfig))
            ->montar($mistura, $ranking['onde'], $corte);

        // Elenco: créditos dos títulos mais próximos, ponderados pela força.
        $topElenco = array_slice($ranqueadas, 0, 250);
        $creditos = CatalogoCredito::porObras($this->pdo, array_column($topElenco, 'id'));
        $pessoaIds = [];

        foreach ($creditos as $listaCreditos) {
            foreach ($listaCreditos as $credito) {
                $pessoaIds[] = (int) $credito['pessoa_id'];
            }
        }

        $elenco = (new ElencoService())->relevantes(
            $topElenco,
            $creditos,
            CatalogoPessoa::porIds($this->pdo, $pessoaIds),
            8
        );

        // Personagens entram apenas como rostos dos filmes que combinam.
        $personagensPorTmdb = [];
        $personagensPorTitulo = [];

        foreach ($entidades as $entidade) {
            if (($entidade['tipo'] ?? '') !== 'personagem') {
                continue;
            }

            $tmdb = (int) ($entidade['tmdb_id'] ?? 0);

            if ($tmdb > 0 && !isset($personagensPorTmdb[$tmdb])) {
                $personagensPorTmdb[$tmdb] = $entidade;
            }

            $chave = mb_strtolower(trim((string) ($entidade['obra_origem'] ?? '')))
                . '|' . (int) ($entidade['ano'] ?? 0);

            if ($chave !== '|0' && !isset($personagensPorTitulo[$chave])) {
                $personagensPorTitulo[$chave] = $entidade;
            }
        }

        foreach ($filmesTop as &$filme) {
            $chave = mb_strtolower(trim((string) $filme['titulo'])) . '|' . (int) ($filme['ano'] ?? 0);
            $personagem = $personagensPorTmdb[(int) $filme['tmdb_id']]
                ?? ($personagensPorTitulo[$chave] ?? null);

            $filme['personagem'] = $personagem['nome'] ?? null;
            $filme['ator_nome'] = $personagem['ator_nome'] ?? null;
            $filme['ator_foto_url'] = $personagem['ator_foto_url'] ?? null;
            $filme['onde'] = $ranking['onde'][(int) $filme['id']] ?? [];
        }
        unset($filme);

        $estante = $this->paraEstante(array_slice($filmesTop, 0, 8));
        $decadaObras = $this->obrasDaDecada($mistura, $estante);

        // Descoberta é o que ainda não apareceu no top 10 do perfil.
        $excluirDescobertas = [];

        foreach ($filmesTop as $filmeTop) {
            $excluirDescobertas[(int) ($filmeTop['tmdb_id'] ?? 0)] = true;
            $chaveTitulo = mb_strtolower(trim((string) ($filmeTop['titulo'] ?? '')));

            if ($chaveTitulo !== '') {
                $excluirDescobertas[$chaveTitulo] = true;
            }
        }

        $descobertas = $this->descobertas($mistura, 3, $excluirDescobertas);

        return [
            'ranking'     => $ranking['geral'],
            'por_trilha'  => $ranking['por_trilha'],
            'cards'       => $cards,
            'onde'        => $ranking['onde'],
            'combinacao'  => $ranking['combinacao'] ?? [],
            'elenco'      => $elenco,
            'filmes_top'  => $filmesTop,
            'estante'     => $estante,
            'descobertas' => $descobertas,
            'decada_obras' => $decadaObras,
            'total'       => count($obras),
        ];
    }

    /** @return array<string, mixed> */
    private function catalogoVazio(int $total = 0): array
    {
        return [
            'ranking' => [], 'por_trilha' => [], 'cards' => [], 'onde' => [],
            'combinacao' => [], 'elenco' => [], 'filmes_top' => [], 'estante' => [],
            'descobertas' => [], 'decada_obras' => [], 'total' => $total,
        ];
    }

    /**
     * Mistura 60/40 (ou 80/20) entre cânone popular e descoberta.
     *
     * @param list<array<string, mixed>> $ranqueadas
     * @return list<array<string, mixed>>
     */
    private function selecionarMix(array $ranqueadas, string $mix, int $limite): array
    {
        $total = count($ranqueadas);

        if ($total === 0) {
            return [];
        }

        $qualidade = (array) ($this->config['descoberta'] ?? []);
        $notaDescoberta = (float) ($qualidade['nota_descoberta'] ?? 7.0);
        $votosDescoberta = (int) ($qualidade['votos_descoberta'] ?? 1000);

        $populares = array_map(static fn (array $obra): float => (float) ($obra['popularidade'] ?? 0), $ranqueadas);
        sort($populares);
        $p60 = $populares[(int) floor($total * 0.60)];
        $p80 = $populares[(int) floor($total * 0.80)];

        $ancoras = [];
        $descobertas = [];

        foreach ($ranqueadas as $obra) {
            $popularidade = (float) ($obra['popularidade'] ?? 0);
            $generosObra = array_map(
                static fn (array $genero): int => (int) ($genero['id'] ?? 0),
                (array) ($obra['generos'] ?? [])
            );
            $infantil = array_intersect($generosObra, [10762, 10764, 10767]) !== [];

            $obra['ancora'] = $popularidade >= $p80;
            $obra['descoberta'] = !$infantil
                && $popularidade < $p60
                && ($obra['nota'] ?? 0) >= $notaDescoberta
                && ($obra['votos'] ?? 0) >= $votosDescoberta;

            if ($obra['ancora']) {
                $ancoras[] = $obra;
            } elseif ($obra['descoberta']) {
                $descobertas[] = $obra;
            }
        }

        $fracoes = [
            'classicos'  => [0.80, 0.20],
            'descobertas' => [0.20, 0.80],
            'misturado'  => [0.40, 0.60],
        ];
        [$fracaoAncora, $fracaoDescoberta] = $fracoes[$mix] ?? $fracoes['misturado'];

        $alvo = (int) min($limite, count($ancoras) + count($descobertas));
        $quantidadeAncora = (int) round($alvo * $fracaoAncora);
        $quantidadeDescoberta = $alvo - $quantidadeAncora;

        $selecionadas = array_merge(
            array_slice($ancoras, 0, $quantidadeAncora),
            array_slice($descobertas, 0, $quantidadeDescoberta)
        );

        // Se uma das pilhas for curta, completa com a outra.
        $vistos = array_column($selecionadas, 'id');

        foreach (array_merge($ancoras, $descobertas) as $obra) {
            if (count($selecionadas) >= $alvo) {
                break;
            }

            if (!in_array((int) $obra['id'], $vistos, true)) {
                $selecionadas[] = $obra;
                $vistos[] = (int) $obra['id'];
            }
        }

        usort($selecionadas, static fn (array $a, array $b): int => $b['afinidade'] <=> $a['afinidade']);

        return $selecionadas;
    }

    /**
     * Evita uma lista com cinco variações do mesmo filme.
     *
     * @param list<array<string, mixed>> $pool
     * @return list<array<string, mixed>>
     */
    private function selecionarDiverso(array $pool, int $limite): array
    {
        $selecionados = [];
        $paises = [];
        $decadas = [];
        $generos = [];
        $titulos = [];

        foreach ($pool as $obra) {
            $titulo = mb_strtolower(trim((string) ($obra['titulo'] ?? '')));

            if ($titulo === '' || isset($titulos[$titulo])) {
                continue;
            }

            $pais = (string) (($obra['paises'][0]['codigo'] ?? '') ?: '—');
            $ano = (int) ($obra['ano'] ?? 0);
            $decada = $ano > 0 ? (int) (floor($ano / 10) * 10) : 0;
            $genero = (int) ($obra['generos'][0]['id'] ?? 0);

            if (($paises[$pais] ?? 0) >= 2 || ($decadas[$decada] ?? 0) >= 2 || ($generos[$genero] ?? 0) >= 2) {
                continue;
            }

            $selecionados[] = $obra;
            $titulos[$titulo] = true;
            $paises[$pais] = ($paises[$pais] ?? 0) + 1;
            $decadas[$decada] = ($decadas[$decada] ?? 0) + 1;
            $generos[$genero] = ($generos[$genero] ?? 0) + 1;

            if (count($selecionados) >= $limite) {
                break;
            }
        }

        // Se as cotas de diversidade forem apertadas demais, completa.
        if (count($selecionados) < $limite) {
            foreach ($pool as $obra) {
                $titulo = mb_strtolower(trim((string) ($obra['titulo'] ?? '')));

                if ($titulo === '' || isset($titulos[$titulo])) {
                    continue;
                }

                $selecionados[] = $obra;
                $titulos[$titulo] = true;

                if (count($selecionados) >= $limite) {
                    break;
                }
            }
        }

        return array_slice($selecionados, 0, $limite);
    }

    /**
     * @param list<array<string, mixed>> $obras
     * @return list<array<string, mixed>>
     */
    private function paraEstante(array $obras): array
    {
        $estante = [];

        foreach ($obras as $obra) {
            $estante[] = [
                'titulo'     => (string) $obra['titulo'],
                'ano'        => $obra['ano'],
                'poster'     => tmdb_img($obra['poster_path'] ?? null, 'w342'),
                'origem'     => 'Catálogo',
                'tipo'       => (string) ($obra['tipo'] ?? 'filme'),
                'personagem' => $obra['personagem'] ?? null,
                'tmdb'       => (int) ($obra['tmdb_id'] ?? 0),
                'onde'       => $obra['onde'] ?? [],
            ];
        }

        return $estante;
    }

    /**
     * @param list<array<string, mixed>> $mistura
     * @param list<array<string, mixed>> $estante
     * @return list<array<string, mixed>>
     */
    private function obrasDaDecada(array $mistura, array $estante): array
    {
        $anoDecada = $this->anoDecadaDaEstante($estante);

        if ($anoDecada === null) {
            return [];
        }

        $obras = [];

        foreach ($mistura as $obra) {
            $ano = (int) ($obra['ano'] ?? 0);

            if ($ano < $anoDecada || $ano >= $anoDecada + 10) {
                continue;
            }

            $poster = tmdb_img($obra['poster_path'] ?? null, 'w342');
            $trilha = (string) ($obra['trilha'] ?? '');

            $obras[] = [
                'titulo'    => (string) $obra['titulo'],
                'ano'       => $ano,
                'tmdb'      => (int) $obra['tmdb_id'],
                'poster'    => $poster !== '' ? $poster : null,
                'origem'    => (string) ($this->recomendacaoConfig['trilhas'][$trilha]['nome'] ?? 'Catálogo'),
                'afinidade' => (float) $obra['afinidade'],
                'top_pct'   => (int) ($obra['top_pct'] ?? 0),
            ];

            if (count($obras) >= 8) {
                break;
            }
        }

        return $obras;
    }

    /**
     * Três sugestões fora do óbvio, com um motivo legível.
     *
     * @param list<array<string, mixed>> $mistura
     * @param array<int|string, bool> $excluir tmdb_id ou título já mostrado no top 10
     * @return list<array<string, mixed>>
     */
    private function descobertas(array $mistura, int $limite, array $excluir = []): array
    {
        $candidatas = array_values(array_filter(
            $mistura,
            static function (array $obra) use ($excluir): bool {
                if (empty($obra['descoberta'])) {
                    return false;
                }

                $tmdb = (int) ($obra['tmdb_id'] ?? 0);
                $titulo = mb_strtolower(trim((string) ($obra['titulo'] ?? '')));

                return !isset($excluir[$tmdb])
                    && ($titulo === '' || !isset($excluir[$titulo]));
            }
        ));
        $fortes = array_values(array_filter(
            $candidatas,
            static fn (array $obra): bool => ($obra['nota'] ?? 0) >= 7.0 && ($obra['votos'] ?? 0) >= 1000
        ));
        $saida = [];

        foreach ($fortes !== [] ? $fortes : $candidatas as $obra) {

            $paises = array_map(
                static fn (array $pais): string => strtoupper((string) ($pais['codigo'] ?? '')),
                (array) ($obra['paises'] ?? [])
            );
            $motivos = [];

            if ($paises !== [] && !in_array('US', $paises, true) && !in_array('BR', $paises, true)) {
                $motivos[] = 'fora do circuito EUA/Brasil';
            }

            $ano = (int) ($obra['ano'] ?? 0);

            if ($ano > 0 && $ano < 1980) {
                $motivos[] = 'dos anos ' . $ano;
            }

            if ($motivos === []) {
                $motivos[] = 'pouco visto no seu perfil';
            }

            $poster = tmdb_img($obra['poster_path'] ?? null, 'w342');
            $saida[] = [
                'titulo' => (string) $obra['titulo'],
                'tipo' => (string) ($obra['tipo'] ?? 'filme'),
                'ano' => $obra['ano'],
                'tmdb' => (int) $obra['tmdb_id'],
                'poster' => $poster !== '' ? $poster : null,
                'poster_path' => $obra['poster_path'] ?? null,
                'porque' => implode(' · ', $motivos),
                'top_pct' => (int) ($obra['top_pct'] ?? 0),
            ];

            if (count($saida) >= $limite) {
                break;
            }
        }

        return $saida;
    }

    /**
     * Agrupa as respostas salvas por eixo, já com direção e peso.
     *
     * @param array<string, mixed> $resultado
     * @return array<int, list<array{resposta: int, direcao: int, peso: int}>>
     */
    private function respostasPorEixo(array $resultado): array
    {
        $json = $resultado['respostas_json'] ?? null;

        if (!is_string($json) || trim($json) === '') {
            return [];
        }

        $respostas = json_decode($json, true);

        if (!is_array($respostas) || $respostas === []) {
            return [];
        }

        $mapa = Pergunta::mapaParaCalculo(Pergunta::todas($this->pdo));
        $porEixo = [];

        foreach ($respostas as $idPergunta => $valor) {
            $meta = $mapa[(int) $idPergunta] ?? null;

            if ($meta === null || !is_numeric($valor)) {
                continue;
            }

            $porEixo[$meta['eixo_id']][] = [
                'resposta' => (int) $valor,
                'direcao' => (int) $meta['direcao'],
                'peso' => (int) $meta['peso'],
            ];
        }

        return $porEixo;
    }

    /**
     * Reamostra as respostas salvas e mede a estabilidade do ranking.
     *
     * @param array<int, list<array{resposta: int, direcao: int, peso: int}>> $porEixo
     * @param array<string, mixed> $resultado
     * @param list<array<string, mixed>> $eixos
     * @param list<array<string, mixed>> $ranqueadas
     * @return array<string, mixed>|null
     */
    private function estabilidade(
        array $porEixo,
        array $resultado,
        array $eixos,
        array $ranqueadas
    ): ?array {
        if ($porEixo === []) {
            return null;
        }

        $itens = array_map(
            static fn (array $item): array => [
                'id' => $item['id'],
                'vetor' => $item['vetor6'] ?? $item['vetor'] ?? [],
            ],
            $ranqueadas
        );

        $itens = array_values(array_filter(
            $itens,
            static fn (array $item): bool => Vetor::valido($item['vetor'])
        ));

        if ($itens === []) {
            return null;
        }

        $seed = (int) sprintf(
            '%u',
            crc32((string) ($resultado['codigo_unico'] ?? '') . '|' . (string) ($resultado['questionario_versao'] ?? ''))
        ) & 0x7FFFFFFF;

        return $this->calibracao->estabilidade(
            $porEixo,
            array_map(static fn (array $eixo): int => (int) $eixo['id'], $eixos),
            $itens,
            120,
            $seed !== 0 ? $seed : 1
        );
    }

    /**
     * Dois eixos que explicam o match, com os valores do usuário e da entidade.
     *
     * @param list<float> $vetor
     * @param array<string, mixed> $item
     * @param list<array<string, mixed>> $eixos
     * @return list<array{eixo: string, voce: float, ele: float, delta: float}>
     */
    private function explicar(array $vetor, array $item, array $eixos): array
    {
        $motivos = $this->recomendacao->motivos($vetor, $item);
        $saida = [];

        foreach ($motivos as $motivo) {
            $indice = (int) $motivo['indice'] - 1;
            $eixo = $eixos[$indice] ?? null;

            if ($eixo === null) {
                continue;
            }

            $saida[] = [
                'eixo' => eixo_nome($eixo),
                'voce' => round((float) ($vetor[$indice] ?? 50)),
                'ele' => round((float) (($item['vetor6'] ?? $item['vetor'] ?? [])[$indice] ?? 50)),
                'delta' => (float) $motivo['delta'],
            ];
        }

        return $saida;
    }

    /**
     * Detecta resposta intensa que se anula.
     *
     * @param array<int, list<array{resposta: int, direcao: int, peso: int}>> $porEixo
     * @param list<array<string, mixed>> $linhas
     * @return array{rotulo: string, media_abs: float, distorcao: float}
     */
    private function consistencia(array $porEixo, array $linhas): array
    {
        $valores = [];

        foreach ($porEixo as $itens) {
            foreach ($itens as $item) {
                $valores[] = abs((int) $item['resposta']);
            }
        }

        $mediaAbs = $valores !== [] ? array_sum($valores) / count($valores) : 0.0;
        $distorcao = 0.0;

        foreach ($linhas as $linha) {
            $distorcao = max($distorcao, abs((float) $linha['pct_dir'] - 50.0));
        }

        $indeterminado = $mediaAbs >= 1.5 && $distorcao < MatcherService::NEUTRO;

        return [
            'rotulo' => $indeterminado ? 'indeterminado' : 'consistente',
            'media_abs' => round($mediaAbs, 2),
            'distorcao' => round($distorcao, 1),
        ];
    }

    /**
     * Duas frases geradas a partir dos eixos mais extremos.
     *
     * @param list<array<string, mixed>> $linhas
     * @param array<string, mixed> $resolucao
     * @return list<array<string, mixed>>
     */
    private function retrato(array $linhas, array $resolucao): array
    {
        $itens = [];

        foreach (array_slice($resolucao['extremos'] ?? [], 0, 2) as $extremo) {
            $linha = $linhas[$extremo['indice']] ?? null;

            if ($linha === null) {
                continue;
            }

            $polo = $linha['polo'] === 'D' ? 'd' : 'e';

            $itens[] = [
                'eixo'  => eixo_nome($linha['eixo']),
                'polo'  => $linha['polo'] === 'D' ? $linha['rotulo_dir'] : $linha['rotulo_esq'],
                'pct'   => $linha['polo'] === 'D' ? $linha['pct_dir'] : $linha['pct_esq'],
                'cor'   => $linha['cor'],
                'texto' => t('retrato.' . $linha['eixo']['slug'] . '.' . $polo),
            ];
        }

        return $itens;
    }

    /**
     * Década mediana do repertório do espelho (1900–2020).
     *
     * @param list<array<string, mixed>> $estante
     */
    private function anoDecadaDaEstante(array $estante): ?int
    {
        $anos = [];

        foreach ($estante as $obra) {
            if (isset($obra['ano']) && (int) $obra['ano'] >= 1900) {
                $anos[] = (int) $obra['ano'];
            }
        }

        if ($anos === []) {
            return null;
        }

        sort($anos);
        $mediana = $anos[intdiv(count($anos), 2)];

        return max(1900, min(2020, (int) (floor($mediana / 10) * 10)));
    }

    /**
     * Insights que vão além do espelho: distinção, típico, oposto, top 10,
     * arquétipo secundário e a década do repertório.
     *
     * @param list<array<string, mixed>> $linhas
     * @param list<float> $vetor
     * @param list<list<float>> $vetoresBase
     * @param list<array<string, mixed>> $ranqueadas
     * @param list<array<string, mixed>> $arquetipos
     * @param array<string, mixed>|null $arquetipo
     * @param list<array<string, mixed>> $estante
     * @return array<string, mixed>
     */
    private function insights(
        array $linhas,
        array $vetor,
        array $vetoresBase,
        array $arquetipos,
        ?array $arquetipo,
        array $estante
    ): array {
        $total = count($vetoresBase);
        $somas = array_fill(0, Vetor::EIXOS, 0.0);

        foreach ($vetoresBase as $v) {
            for ($i = 0; $i < Vetor::EIXOS; $i++) {
                $somas[$i] += (float) ($v[$i] ?? 50);
            }
        }

        $medias = array_map(
            static fn (float $soma): float => $total > 0 ? round($soma / $total, 2) : 50.0,
            $somas
        );

        $distincao = null;
        $tipico = null;
        $maiorDesvio = -1.0;
        $menorDesvio = PHP_FLOAT_MAX;

        foreach ($linhas as $indice => $linha) {
            $pct = (float) $linha['pct_dir'];
            $media = $medias[$indice] ?? 50.0;
            $desvio = abs($pct - $media);
            $abaixo = 0;

            foreach ($vetoresBase as $v) {
                if ((float) ($v[$indice] ?? 50) <= $pct) {
                    $abaixo++;
                }
            }

            $percentil = $total > 0 ? round(100 * $abaixo / $total, 0) : 50.0;

            // Para o polo esquerdo, "mais para X" significa estar à esquerda
            // da base: inverte o percentil.
            if ($linha['polo'] === 'E') {
                $percentil = 100.0 - $percentil;
            }

            $item = [
                'eixo' => eixo_nome($linha['eixo']),
                'polo' => $linha['polo'] === 'D' ? $linha['rotulo_dir'] : $linha['rotulo_esq'],
                'pct' => $pct,
                'media' => $media,
                'percentil' => $percentil,
                'cor' => $linha['cor'],
            ];

            if ($desvio > $maiorDesvio) {
                $maiorDesvio = $desvio;
                $distincao = $item;
            }

            if ($desvio < $menorDesvio) {
                $menorDesvio = $desvio;
                $tipico = $item;
            }
        }

        $eixosPorId = [];

        foreach ($linhas as $linha) {
            $eixosPorId[(int) ($linha['eixo']['id'] ?? 0)] = $linha;
        }

        $secundario = null;
        $melhorNota = -1.0;

        foreach ($arquetipos as $candidato) {
            if ($arquetipo !== null && (int) $candidato['id'] === (int) $arquetipo['id']) {
                continue;
            }

            if (!Vetor::valido($candidato['vetor'] ?? [])) {
                continue;
            }

            $nota = $this->matcher->compatibilidade($vetor, $candidato['vetor']);

            if ($nota <= $melhorNota) {
                continue;
            }

            $melhorNota = $nota;
            $polos = [];

            foreach ([
                [(int) ($candidato['eixo_dominante_1'] ?? 0), (string) ($candidato['polo_1'] ?? '')],
                [(int) ($candidato['eixo_dominante_2'] ?? 0), (string) ($candidato['polo_2'] ?? '')],
            ] as [$idEixo, $polo]) {
                $linha = $eixosPorId[$idEixo] ?? null;

                if ($linha === null || $idEixo <= 0) {
                    continue;
                }

                $direita = $polo === 'D';
                $pct = $direita ? (float) $candidato['vetor'][$idEixo - 1] : 100 - (float) $candidato['vetor'][$idEixo - 1];

                $polos[] = [
                    'eixo' => eixo_nome($linha['eixo']),
                    'polo' => $direita ? $linha['rotulo_dir'] : $linha['rotulo_esq'],
                    'pct' => round($pct, 0),
                    'cor' => $direita ? (string) $linha['eixo']['cor_dir'] : (string) $linha['eixo']['cor_esq'],
                ];
            }

            $secundario = [
                'titulo' => $candidato['titulo'],
                'compatibilidade' => $nota,
                'descricao' => $candidato['descricao'],
                'ritual' => $candidato['ritual_cinema'],
                'polos' => $polos,
            ];
        }

        $decada = null;
        $anoDecada = $this->anoDecadaDaEstante($estante);
        $anos = [];

        foreach ($estante as $obra) {
            if (isset($obra['ano']) && (int) $obra['ano'] >= 1900) {
                $anos[] = (int) $obra['ano'];
            }
        }

        if ($anoDecada !== null) {
            $guia = Decada::porAno($anoDecada);

            // Obras do próprio espelho que caem nessa década.
            $obrasDecada = [];

            foreach ($estante as $obra) {
                $anoObra = isset($obra['ano']) ? (int) $obra['ano'] : 0;

                if ($anoObra >= $anoDecada && $anoObra < $anoDecada + 10) {
                    $obrasDecada[] = $obra;
                }
            }

            $decada = [
                'ano'         => $anoDecada,
                'label'       => 'Anos ' . $anoDecada,
                'epiteto'     => (string) ($guia['epiteto'] ?? ''),
                'descricao'   => (string) ($guia['descricao'] ?? 'Um repertório que atravessa décadas.'),
                'curiosidade' => (string) ($guia['curiosidade'] ?? ''),
                'marcos'      => FilmeCard::enriquecer((array) ($guia['marcos'] ?? [])),
                'obras'       => FilmeCard::enriquecer(array_slice($obrasDecada, 0, 4)),
                'amostra'     => count($anos),
            ];
        }

        return [
            'distincao' => $distincao,
            'tipico' => $tipico,
            'secundario' => $secundario,
            'decada' => $decada,
            'base_total' => $total,
        ];
    }

    /**
     * Linhas prontas para as barras duplas e para o radar.
     *
     * @param list<array<string, mixed>> $eixos
     * @param list<float> $vetor
     * @return list<array<string, mixed>>
     */
    private function linhasEixos(array $eixos, array $vetor): array
    {
        $linhas = [];

        foreach (array_values($eixos) as $indice => $eixo) {
            $pctDir = round((float) ($vetor[$indice] ?? 50), 2);
            $pctEsq = round(100 - $pctDir, 2);
            $polo = $this->likert->polo($pctDir);

            $linhas[] = [
                'eixo'        => $eixo,
                'pct_dir'     => $pctDir,
                'pct_esq'     => $pctEsq,
                'polo'        => $polo,
                'intensidade' => $this->likert->intensidade($pctDir),
                'cor'         => $polo === 'E' ? $eixo['cor_esq'] : $eixo['cor_dir'],
                'cor_esq'     => $eixo['cor_esq'],
                'cor_dir'     => $eixo['cor_dir'],
                'rotulo_esq'  => eixo_polo($eixo, 'esq'),
                'rotulo_dir'  => eixo_polo($eixo, 'dir'),
            ];
        }

        return $linhas;
    }
}
