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
use Models\Plataforma;
use Models\Retrato;
use Core\Vetor;
use Models\Resultado;
use PDO;
use Services\ElencoService;
use Services\LikertCalculatorService;
use Services\LeituraService;
use Services\MatcherService;
use Services\PlataformaRankingService;
use Services\RecomendacaoService;
use Services\RetratoService;
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
    }

    /**
     * @param bool $comCatalogo false na landing, no retrato e na sinergia:
     *                          essas telas não usam ranking nem cards.
     * @return array<string, mixed>|null
     */
    public function montar(string $codigo, bool $comCatalogo = true): ?array
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

        // O arquétipo salvo tem prioridade — garante estabilidade do link.
        $arquetipo = $resultado['arquetipo_id'] !== null
            ? Arquetipo::porId($this->pdo, (int) $resultado['arquetipo_id'])
            : $resolucao['arquetipo'];

        if ($arquetipo === null) {
            $arquetipo = $resolucao['arquetipo'];
        }

        $entidades = EntidadeMatch::todas($this->pdo);
        $ranqueadas = $this->matcher->ranquear($vetor, $entidades);

        // Seis personagens, evitando repetir a mesma obra (dois do Star Wars,
        // dois do Exterminador...) para o espelho ficar mais variado.
        $personagens = [];
        $obrasVistas = [];

        foreach ($this->matcher->topPorTipo($ranqueadas, 'personagem', 40) as $candidato) {
            $chaveObra = mb_strtolower(trim((string) $candidato['obra_origem']));

            if ($chaveObra !== '' && isset($obrasVistas[$chaveObra])) {
                continue;
            }

            $obrasVistas[$chaveObra] = true;
            $personagens[] = $candidato;

            if (count($personagens) >= 6) {
                break;
            }
        }
        $diretor = $this->matcher->topPorTipo($ranqueadas, 'diretor', 1)[0] ?? null;
        $estudio = $this->matcher->topPorTipo($ranqueadas, 'estudio', 1)[0] ?? null;

        // Obras essenciais das entidades exibidas (a "estante" do perfil).
        $ids = array_merge(
            array_map(static fn (array $e): int => (int) $e['id'], $personagens),
            $diretor !== null ? [(int) $diretor['id']] : [],
            $estudio !== null ? [(int) $estudio['id']] : []
        );

        $obras = Obra::porEntidades($this->pdo, $ids);

        foreach ($personagens as &$personagem) {
            $personagem['obras'] = $obras[(int) $personagem['id']] ?? [];
            $personagem['polos'] = entidade_polos($personagem, $eixos);
        }
        unset($personagem);

        foreach ([&$diretor, &$estudio] as &$ancora) {
            if ($ancora !== null) {
                $ancora['obras'] = $obras[(int) $ancora['id']] ?? [];
                $ancora['polos'] = entidade_polos($ancora, $eixos);
            }
        }
        unset($ancora);

        // Atores dos espelhos: a mesma pessoa interpreta um personagem, mas o
        // perfil também quer saber quem é o intérprete.
        $atores = [];
        $atoresVistos = [];

        foreach ($personagens as $personagem) {
            $nomeAtor = trim((string) ($personagem['ator_nome'] ?? ''));
            $chaveAtor = mb_strtolower($nomeAtor);

            if ($nomeAtor === '' || isset($atoresVistos[$chaveAtor])) {
                continue;
            }

            $atoresVistos[$chaveAtor] = true;

            $atores[] = [
                'nome'        => $nomeAtor,
                'foto'        => $personagem['ator_foto_url'] ?? null,
                'nascimento'  => $personagem['nascimento'] ?? null,
                'pais'        => $personagem['pais'] ?? null,
                'tmdb'        => $personagem['ator_tmdb_id'] ?? null,
                'personagem'  => (string) $personagem['nome'],
                'obra'        => (string) $personagem['obra_origem'],
                'ano'         => $personagem['ano'] ?? null,
                'filme_tmdb'  => $personagem['tmdb_id'] ?? null,
                'afinidade'   => $personagem['afinidade'] ?? null,
            ];
        }

        $linhas = $this->linhasEixos($eixos, $vetor);

        $limiteRaridade = (int) ($this->config['raridade']['max_amostra'] ?? 5000);
        $vetoresBase = Resultado::vetores($this->pdo, $limiteRaridade);
        $raridade = $this->matcher->raridade($vetor, $vetoresBase);

        $estante = $this->montarEstante($personagens, $diretor, $estudio);

        $catalogo = $comCatalogo
            ? $this->montarCatalogo($vetor, $estante, $personagens, $diretor, $estudio)
            : ['ranking' => [], 'cards' => [], 'onde' => [], 'elenco' => [], 'decada_obras' => [], 'total' => 0, 'por_trilha' => []];

        $insights = $this->insights(
            $linhas,
            $vetor,
            $vetoresBase,
            $ranqueadas,
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

        $afinidadeMedia = $personagens !== []
            ? round(array_sum(array_column($personagens, 'afinidade')) / count($personagens), 0)
            : 0.0;

        $leituras = (new LeituraService($this->leiturasConfig))->selecionar($linhas, $personagens);

        return [
            'resultado'       => $resultado,
            'vetor'           => $vetor,
            'eixos'           => $eixos,
            'linhas'          => $linhas,
            'leituras'        => $leituras,
            'arquetipo'       => $arquetipo,
            'resolucao'       => $resolucao,
            'neutro'          => $arquetipo === null,
            'personagens'     => $personagens,
            'atores'          => $atores,
            'diretor'         => $diretor,
            'estudio'         => $estudio,
            'estante'         => $estante,
            'retrato'         => $this->retrato($linhas, $resolucao),
            'afinidade_media' => $afinidadeMedia,
            'raridade'        => $raridade,
            'base_total'      => Resultado::total($this->pdo),
            'retrato_salvo'   => Retrato::porCodigo($this->pdo, $codigo, RetratoService::PROMPT_VERSAO),
            'insights'        => $insights,
            'plataformas'     => $catalogo['ranking'],
            'plataformas_trilha' => $catalogo['por_trilha'],
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

        $entidades = EntidadeMatch::todas($this->pdo);
        $ranqueadas = $this->matcher->ranquear($comparacao['media'], $entidades);

        return [
            'a'          => $a,
            'b'          => $b,
            'comparacao' => $comparacao,
            'combinado'  => [
                'vetor'      => $comparacao['media'],
                'personagem' => $this->matcher->topPorTipo($ranqueadas, 'personagem', 1)[0] ?? null,
                'estudio'    => $this->matcher->topPorTipo($ranqueadas, 'estudio', 1)[0] ?? null,
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
    private function montarCatalogo(
        array $vetor,
        array &$estante,
        array &$personagens,
        ?array &$diretor,
        ?array &$estudio
    ): array {
        // Deploy seguro: se as tabelas do catálogo ainda não foram importadas,
        // o perfil continua funcionando sem as seções novas.
        try {
            $this->enriquecerOnde($estante, $personagens, $diretor, $estudio);

            $obras = CatalogoObra::todos($this->pdo, true);
            $plataformas = Plataforma::todas($this->pdo, true);
        } catch (\Throwable $erro) {
            error_log('[PersonaCut][catalogo] ' . $erro->getMessage());

            return [
                'ranking'      => [],
                'por_trilha'   => [],
                'cards'        => [],
                'onde'         => [],
                'elenco'       => [],
                'decada_obras' => [],
                'total'        => 0,
            ];
        }

        if ($obras === [] || $plataformas === []) {
            return [
                'ranking'    => [],
                'por_trilha' => [],
                'cards'      => [],
                'onde'       => [],
                'elenco'     => [],
                'total'      => count($obras),
            ];
        }

        $ranqueadas = $this->recomendacao->rankear($vetor, $obras);

        foreach ($ranqueadas as &$obra) {
            $obra['trilha'] = $this->recomendacao->trilhaDe($obra);
        }
        unset($obra);

        $ranking = (new PlataformaRankingService($this->recomendacaoConfig))
            ->ranquear($ranqueadas, $plataformas);
        $cards = (new TrilhaService($this->recomendacaoConfig))
            ->montar($ranqueadas, $ranking['onde']);

        // 250 obras: garante elenco mesmo quando parte dos melhores títulos
        // ainda não tem créditos ingeridos. O serviço pondera pela afinidade.
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

        // Obras do catálogo na década do repertório. A estante do espelho
        // costuma ter só uma ou duas; o catálogo garante uma prateleira cheia.
        $decadaObras = [];
        $anoDecada = $this->anoDecadaDaEstante($estante);

        if ($anoDecada !== null) {
            foreach ($ranqueadas as $obra) {
                if (empty($obra['plataformas'])) {
                    continue;
                }

                $anoObra = (int) ($obra['ano'] ?? 0);

                if ($anoObra < $anoDecada || $anoObra >= $anoDecada + 10) {
                    continue;
                }

                $poster = tmdb_img($obra['poster_path'] ?? null, 'w342');
                $trilhaSlug = (string) ($obra['trilha'] ?? '');

                $decadaObras[] = [
                    'titulo'     => (string) $obra['titulo'],
                    'ano'        => $anoObra,
                    'tmdb'       => (int) $obra['tmdb_id'],
                    'poster'     => $poster !== '' ? $poster : null,
                    'origem'     => (string) ($this->recomendacaoConfig['trilhas'][$trilhaSlug]['nome'] ?? 'Catálogo'),
                    'afinidade'  => (float) $obra['afinidade'],
                ];

                if (count($decadaObras) >= 8) {
                    break;
                }
            }
        }

        return [
            'ranking'     => $ranking['geral'],
            'por_trilha'  => $ranking['por_trilha'],
            'cards'       => $cards,
            'onde'        => $ranking['onde'],
            'elenco'      => $elenco,
            'decada_obras' => $decadaObras,
            'total'       => count($obras),
        ];
    }

    /**
     * Acrescenta `onde` (plataformas ativas) às obras já exibidas no perfil,
     * por tmdb_id. Não carrega o catálogo inteiro: consulta só os ids usados.
     *
     * @param list<array<string, mixed>> $estante
     * @param list<array<string, mixed>> $personagens
     * @param array<string, mixed>|null $diretor
     * @param array<string, mixed>|null $estudio
     */
    private function enriquecerOnde(
        array &$estante,
        array &$personagens,
        ?array &$diretor,
        ?array &$estudio
    ): void {
        $ids = [];

        foreach ($estante as $obra) {
            if (!empty($obra['tmdb'])) {
                $ids[] = (int) $obra['tmdb'];
            }
        }

        foreach ($personagens as $personagem) {
            if (!empty($personagem['tmdb_id'])) {
                $ids[] = (int) $personagem['tmdb_id'];
            }

            foreach ((array) ($personagem['obras'] ?? []) as $obra) {
                if (!empty($obra['tmdb_id'])) {
                    $ids[] = (int) $obra['tmdb_id'];
                }
            }
        }

        foreach ([$diretor, $estudio] as $ancora) {
            if ($ancora === null) {
                continue;
            }

            foreach ((array) ($ancora['obras'] ?? []) as $obra) {
                if (!empty($obra['tmdb_id'])) {
                    $ids[] = (int) $obra['tmdb_id'];
                }
            }
        }

        $mapa = CatalogoObra::plataformasPorTmdb($this->pdo, $ids);

        $aplicar = static function (array &$item, ?int $tmdbId) use ($mapa): void {
            if ($tmdbId !== null && isset($mapa[$tmdbId])) {
                $item['onde'] = $mapa[$tmdbId];
            }
        };

        foreach ($estante as &$obra) {
            $aplicar($obra, isset($obra['tmdb']) ? (int) $obra['tmdb'] : null);
        }
        unset($obra);

        foreach ($personagens as &$personagem) {
            $aplicar($personagem, isset($personagem['tmdb_id']) ? (int) $personagem['tmdb_id'] : null);

            foreach ($personagem['obras'] as &$obra) {
                $aplicar($obra, isset($obra['tmdb_id']) ? (int) $obra['tmdb_id'] : null);
            }
            unset($obra);
        }
        unset($personagem);

        foreach ([&$diretor, &$estudio] as &$ancora) {
            if ($ancora === null) {
                continue;
            }

            foreach ($ancora['obras'] as &$obra) {
                $aplicar($obra, isset($obra['tmdb_id']) ? (int) $obra['tmdb_id'] : null);
            }
            unset($obra);
        }
        unset($ancora);
    }

    /**
     * Obras que formam a estante do perfil: a obra de cada personagem espelho
     * + as obras essenciais do diretor e do estúdio.
     *
     * @param list<array<string, mixed>> $personagens
     * @param array<string, mixed>|null $diretor
     * @param array<string, mixed>|null $estudio
     * @return list<array<string, mixed>>
     */
    private function montarEstante(array $personagens, ?array $diretor, ?array $estudio): array
    {
        $estante = [];
        $vistos = [];

        $adicionar = static function (array $item) use (&$estante, &$vistos): void {
            $chave = mb_strtolower(trim((string) $item['titulo']));

            if ($chave === '' || isset($vistos[$chave])) {
                return;
            }

            $vistos[$chave] = true;
            $estante[] = $item;
        };

        foreach ($personagens as $personagem) {
            $adicionar([
                'titulo'     => (string) $personagem['obra_origem'],
                'ano'        => $personagem['ano'],
                'poster'     => $personagem['imagem_url'],
                'origem'     => (string) $personagem['nome'],
                'tipo'       => 'personagem',
                'personagem' => (string) $personagem['nome'],
                'tmdb'       => $personagem['tmdb_id'],
            ]);
        }

        foreach ([$diretor, $estudio] as $ancora) {
            if ($ancora === null) {
                continue;
            }

            foreach ($ancora['obras'] ?? [] as $obra) {
                $adicionar([
                    'titulo'     => (string) $obra['titulo'],
                    'ano'        => $obra['ano'],
                    'poster'     => $obra['poster_url'],
                    'origem'     => (string) $ancora['nome'],
                    'tipo'       => (string) $ancora['tipo'],
                    'personagem' => $obra['personagem'] ? (string) $obra['personagem'] : null,
                    'tmdb'       => $obra['tmdb_id'],
                ]);
            }
        }

        return array_slice($estante, 0, 8);
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
        array $ranqueadas,
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

        $oposto = null;
        $ultimo = $ranqueadas !== [] ? $ranqueadas[count($ranqueadas) - 1] : null;

        if ($ultimo !== null) {
            $oposto = [
                'nome' => $ultimo['nome'],
                'tipo' => $ultimo['tipo'],
                'obra' => $ultimo['obra_origem'],
                'imagem' => $ultimo['imagem_url'],
                'afinidade' => $ultimo['afinidade'],
            ];
        }

        $top10 = [];
        $topAncoras = [];

        foreach ($ranqueadas as $item) {
            if ($item['tipo'] === 'personagem') {
                if (count($top10) >= 10) {
                    continue;
                }

                $top10[] = [
                    'nome' => $item['nome'],
                    'tipo' => $item['tipo'],
                    'obra' => $item['obra_origem'],
                    'imagem' => $item['ator_foto_url'] ?: $item['imagem_url'],
                    'afinidade' => $item['afinidade'],
                ];

                continue;
            }

            if (count($topAncoras) < 6) {
                $topAncoras[] = [
                    'nome' => $item['nome'],
                    'tipo' => $item['tipo'],
                    'obra' => $item['obra_origem'],
                    'imagem' => $item['imagem_url'],
                    'afinidade' => $item['afinidade'],
                    'bio' => $item['bio'],
                ];
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
            'oposto' => $oposto,
            'top10' => $top10,
            'top_ancoras' => $topAncoras,
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
