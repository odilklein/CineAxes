<?php

declare(strict_types=1);

namespace Controllers;

use Models\Arquetipo;
use Models\Eixo;
use Models\EntidadeMatch;
use Models\Obra;
use Models\Retrato;
use Core\Vetor;
use Models\Resultado;
use PDO;
use Services\LikertCalculatorService;
use Services\MatcherService;
use Services\RetratoService;

/**
 * Monta o "view model" do dashboard de perfil e da sinergia.
 * As páginas ficam só com apresentação.
 */
final class PerfilController
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly MatcherService $matcher,
        private readonly LikertCalculatorService $likert,
        private readonly array $config
    ) {
    }

    /** @return array<string, mixed>|null */
    public function montar(string $codigo): ?array
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
        }
        unset($personagem);

        foreach ([&$diretor, &$estudio] as &$ancora) {
            if ($ancora !== null) {
                $ancora['obras'] = $obras[(int) $ancora['id']] ?? [];
            }
        }
        unset($ancora);

        $linhas = $this->linhasEixos($eixos, $vetor);

        $limiteRaridade = (int) ($this->config['raridade']['max_amostra'] ?? 5000);
        $vetoresBase = Resultado::vetores($this->pdo, $limiteRaridade);
        $raridade = $this->matcher->raridade($vetor, $vetoresBase);

        $estante = $this->montarEstante($personagens, $diretor, $estudio);

        $insights = $this->insights(
            $linhas,
            $vetor,
            $vetoresBase,
            $ranqueadas,
            $arquetipos,
            $arquetipo,
            $estante
        );

        $afinidadeMedia = $personagens !== []
            ? round(array_sum(array_column($personagens, 'afinidade')) / count($personagens), 0)
            : 0.0;

        return [
            'resultado'       => $resultado,
            'vetor'           => $vetor,
            'eixos'           => $eixos,
            'linhas'          => $linhas,
            'arquetipo'       => $arquetipo,
            'resolucao'       => $resolucao,
            'neutro'          => $arquetipo === null,
            'personagens'     => $personagens,
            'diretor'         => $diretor,
            'estudio'         => $estudio,
            'estante'         => $estante,
            'retrato'         => $this->retrato($linhas, $resolucao),
            'afinidade_media' => $afinidadeMedia,
            'raridade'        => $raridade,
            'base_total'      => Resultado::total($this->pdo),
            'retrato_salvo'   => Retrato::porCodigo($this->pdo, $codigo, RetratoService::PROMPT_VERSAO),
            'insights'        => $insights,
        ];
    }

    /**
     * Comparação entre dois perfis (Modo Sinergia).
     *
     * @return array<string, mixed>|null
     */
    public function comparar(string $codigoA, string $codigoB): ?array
    {
        $a = $this->montar($codigoA);
        $b = $this->montar($codigoB);

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

        foreach (array_slice($ranqueadas, 0, 10) as $item) {
            $top10[] = [
                'nome' => $item['nome'],
                'tipo' => $item['tipo'],
                'obra' => $item['obra_origem'],
                'imagem' => $item['imagem_url'],
                'afinidade' => $item['afinidade'],
            ];
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

            if ($nota > $melhorNota) {
                $melhorNota = $nota;
                $secundario = [
                    'titulo' => $candidato['titulo'],
                    'compatibilidade' => $nota,
                    'descricao' => $candidato['descricao'],
                ];
            }
        }

        $anos = [];

        foreach ($estante as $obra) {
            if (isset($obra['ano']) && (int) $obra['ano'] >= 1900) {
                $anos[] = (int) $obra['ano'];
            }
        }

        $decada = null;

        if ($anos !== []) {
            sort($anos);
            $mediana = $anos[intdiv(count($anos), 2)];
            $anoDecada = max(1900, min(2020, (int) (floor($mediana / 10) * 10)));
            $descricoes = [
                1900 => 'A invenção da linguagem clássica, o estúdio e o melodrama.',
                1910 => 'A consolidação do longa-metragem e das estrelas.',
                1920 => 'O expressionismo, o burlesco e a montagem soviética.',
                1930 => 'O som, a comédia sofisticada e o star system.',
                1940 => 'O noir, o melodrama de guerra e o estúdio em plena forma.',
                1950 => 'O classicismo maduro, o widescreen e o melodrama.',
                1960 => 'A modernidade, a nouvelle vague e o cinema de autor.',
                1970 => 'O New Hollywood, a paranoia e a violência urbana.',
                1980 => 'O blockbuster, o VHS e a trilha sintetizada.',
                1990 => 'O indie, o digital nascente e a ironia.',
                2000 => 'A expansão do digital, o épico e o cinema de festival.',
                2010 => 'O streaming, o autor de prestígio e a série como cinema.',
                2020 => 'O multiverso, o neon e a tela vertical.',
            ];

            $decada = [
                'ano' => $anoDecada,
                'label' => 'Anos ' . $anoDecada,
                'descricao' => $descricoes[$anoDecada] ?? 'Um repertório que atravessa décadas.',
                'amostra' => count($anos),
            ];
        }

        return [
            'distincao' => $distincao,
            'tipico' => $tipico,
            'oposto' => $oposto,
            'top10' => $top10,
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
