<?php

declare(strict_types=1);

namespace Services;

use Core\Vetor;

/**
 * Compara o vetor de 8 eixos do usuário com entidades, arquétipos e outros
 * usuários.
 *
 * A afinidade segue o modelo do 12 Axes: quatro componentes — similaridade por
 * eixo (com penalidade de lado oposto), direção (cosseno aumentado), magnitude
 * e outlier — em vez de uma distância linear. A ordenação usa a mesma nota.
 * A raridade continua usando a diferença média absoluta, porque mede "perfis
 * parecidos" e não "compatibilidade".
 */
final class MatcherService
{
    private const AXIS_WEIGHT = 0.42;
    private const DIRECTION_WEIGHT = 0.33;
    private const MAGNITUDE_WEIGHT = 0.18;
    private const OUTLIER_WEIGHT = 0.07;

    private const OPPOSITE_SIDE_MAX_PENALTY = 0.45;
    private const OPPOSITE_SIDE_SPREAD = 25.0;
    private const OUTLIER_FULL_SPREAD = 100.0;
    private const OUTLIER_EXPONENT = 2.5;
    private const DIRECTION_AUGMENT_RADIUS = 8.0;

    /**
     * O arquétipo é escolhido pelo vetor completo, mas o eixo dominante do
     * usuário ganha um bônus: sem isso, um tipo cujo eixo principal está neutro
     * pode vencer só porque os eixos secundários coincidem.
     */
    private const DOMINANCE_BONUS = 8.0;

    /**
     * Distância do centro a partir da qual o perfil deixa de ser "neutro".
     * É o mesmo limiar de `LikertCalculatorService::intensidade()` (8.0), para
     * que "Equilibrado" na barra e "Espectador Neutro" no arquétipo não se
     * contradigam.
     */
    public const NEUTRO = 8.0;

    /**
     * Temperatura do softmax que converte compatibilidade em distribuição de
     * probabilidade entre os arquétipos. 6.0 mantém o vencedor à frente sem
     * transformar 2 pontos de diferença em certeza.
     */
    private const SOFTMAX_TEMPERATURA = 6.0;

    public function __construct(
        private readonly float $limiarRaridade = 12.0,
        private readonly int $minBaseRaridade = 100
    ) {
    }

    /**
     * @param list<float> $a
     * @param list<float> $b
     */
    public function distancia(array $a, array $b): float
    {
        $soma = 0.0;

        for ($i = 0; $i < Vetor::EIXOS; $i++) {
            $delta = ((float) ($a[$i] ?? 50)) - ((float) ($b[$i] ?? 50));
            $soma += $delta * $delta;
        }

        return sqrt($soma);
    }

    /**
     * Compatibilidade composta (0–100), no espírito do ProfileMatchScorer do
     * 12 Axes: 42% similaridade por eixo + 33% direção + 18% magnitude + 7% outlier.
     *
     * @param list<float> $a
     * @param list<float> $b
     */
    public function compatibilidade(array $a, array $b): float
    {
        $total = Vetor::EIXOS;
        $similaridadeEixos = 0.0;
        $produtoEscalar = 0.0;
        $normaA = 0.0;
        $normaB = 0.0;
        $intensidadeA = 0.0;
        $intensidadeB = 0.0;
        $maiorDiferenca = 0.0;

        for ($i = 0; $i < $total; $i++) {
            $valorA = (float) ($a[$i] ?? 50);
            $valorB = (float) ($b[$i] ?? 50);
            $centradoA = $valorA - 50.0;
            $centradoB = $valorB - 50.0;
            $diferenca = abs($valorA - $valorB);

            // Similaridade quadrática: diferenças pequenas quase não pesam;
            // a partir de 50 pontos o eixo zera.
            $similaridade = max(0.0, 1.0 - ($diferenca / 50.0) ** 2);

            // Lados opostos do centro não são "quase iguais": aplica até 45%.
            if ($centradoA * $centradoB < 0.0) {
                $penalidade = self::OPPOSITE_SIDE_MAX_PENALTY
                    * tanh(abs($centradoA) / self::OPPOSITE_SIDE_SPREAD)
                    * tanh(abs($centradoB) / self::OPPOSITE_SIDE_SPREAD);
                $similaridade *= 1.0 - $penalidade;
            }

            $similaridadeEixos += $similaridade;
            $produtoEscalar += $centradoA * $centradoB;
            $normaA += $centradoA ** 2;
            $normaB += $centradoB ** 2;
            $intensidadeA += abs($centradoA);
            $intensidadeB += abs($centradoB);
            $maiorDiferenca = max($maiorDiferenca, $diferenca);
        }

        $similaridadeEixos = 100.0 * $similaridadeEixos / $total;

        // Cosseno aumentado: perto do centro, a direção é ruído; o termo
        // constante segura a nota e converge ao cosseno puro nos extremos.
        $aumento = $total * self::DIRECTION_AUGMENT_RADIUS ** 2;
        $cosseno = ($produtoEscalar + $aumento)
            / sqrt(($normaA + $aumento) * ($normaB + $aumento));
        $direcao = 50.0 + 50.0 * $cosseno;

        $magnitude = 100.0 - 2.0 * abs(($intensidadeA / $total) - ($intensidadeB / $total));
        $outlier = 100.0 * max(
            0.0,
            1.0 - ($maiorDiferenca / self::OUTLIER_FULL_SPREAD) ** self::OUTLIER_EXPONENT
        );

        $nota = self::AXIS_WEIGHT * $similaridadeEixos
            + self::DIRECTION_WEIGHT * $direcao
            + self::MAGNITUDE_WEIGHT * $magnitude
            + self::OUTLIER_WEIGHT * $outlier;

        return round(max(0.0, min(100.0, $nota)), 1);
    }

    /**
     * Nome histórico usado pelo dashboard.
     *
     * @param list<float> $a
     * @param list<float> $b
     */
    public function afinidade(array $a, array $b): float
    {
        return $this->compatibilidade($a, $b);
    }

    /**
     * @param list<float> $vetor
     * @param list<array<string, mixed>> $entidades cada uma com chave `vetor`
     * @return list<array<string, mixed>>
     */
    public function ranquear(array $vetor, array $entidades): array
    {
        $ranqueadas = [];

        foreach ($entidades as $entidade) {
            $vetorEntidade = is_array($entidade['vetor6'] ?? null)
                ? $entidade['vetor6']
                : (is_array($entidade['vetor'] ?? null) && count($entidade['vetor']) === Vetor::EIXOS
                    ? $entidade['vetor']
                    : Vetor::daLinha($entidade));

            $entidade['distancia'] = round($this->distancia($vetor, $vetorEntidade), 2);
            $entidade['afinidade'] = $this->compatibilidade($vetor, $vetorEntidade);
            $entidade['compatibilidade'] = $entidade['afinidade'];

            $ranqueadas[] = $entidade;
        }

        usort($ranqueadas, static function (array $a, array $b): int {
            return $b['compatibilidade'] <=> $a['compatibilidade']
                ?: $a['distancia'] <=> $b['distancia']
                ?: strcmp((string) $a['nome'], (string) $b['nome']);
        });

        return $ranqueadas;
    }

    /**
     * @param list<array<string, mixed>> $ranqueadas
     * @return list<array<string, mixed>>
     */
    public function topPorTipo(array $ranqueadas, string $tipo, int $limite = 3): array
    {
        $filtradas = array_values(array_filter(
            $ranqueadas,
            static fn (array $e): bool => ($e['tipo'] ?? '') === $tipo
        ));

        return array_slice($filtradas, 0, max(0, $limite));
    }

    /**
     * Os N eixos mais distantes de 50.
     *
     * @param list<float> $vetor
     * @return list<array{indice: int, pct: float, distancia: float}>
     */
    public function extremos(array $vetor, int $quantidade = 2): array
    {
        $lista = [];

        foreach ($vetor as $indice => $percentual) {
            $percentual = (float) $percentual;
            $lista[] = [
                'indice'    => (int) $indice,
                'pct'       => $percentual,
                'distancia' => round(abs($percentual - 50.0), 2),
            ];
        }

        usort($lista, static function (array $a, array $b): int {
            return $b['distancia'] <=> $a['distancia']
                ?: $a['indice'] <=> $b['indice'];
        });

        return array_slice($lista, 0, max(0, $quantidade));
    }

    /**
     * Escolhe o arquétipo mais próximo do vetor COMPLETO, como o 12 Axes faz
     * com as ideologias. Os dois eixos extremos continuam sendo devolvidos para
     * os chips do dashboard, mas não decidem mais sozinhos o arquétipo.
     *
     * @param list<float> $vetor
     * @param list<array<string, mixed>> $arquetipos
     * @param list<int> $eixosIds
     *
     * @return array{
     *     arquetipo: array<string, mixed>|null,
     *     neutro: bool,
     *     compatibilidade: float,
     *     probabilidade: float,
     *     distribuicao: list<array{id: int, titulo: string, compatibilidade: float, probabilidade: float}>,
     *     extremos: list<array{indice: int, pct: float, distancia: float}>,
     *     eixo_1: int|null, polo_1: string|null,
     *     eixo_2: int|null, polo_2: string|null
     * }
     */
    public function resolverArquetipo(array $vetor, array $arquetipos, array $eixosIds): array
    {
        $extremos = $this->extremos($vetor, 2);

        $eixo1 = null;
        $polo1 = null;
        $eixo2 = null;
        $polo2 = null;

        if ($extremos !== []) {
            $eixo1 = (int) ($eixosIds[$extremos[0]['indice']] ?? ($extremos[0]['indice'] + 1));
            $polo1 = $extremos[0]['pct'] >= 50 ? 'D' : 'E';
        }

        if (isset($extremos[1])) {
            $eixo2 = (int) ($eixosIds[$extremos[1]['indice']] ?? ($extremos[1]['indice'] + 1));
            $polo2 = $extremos[1]['pct'] >= 50 ? 'D' : 'E';
        }

        if ($extremos === [] || $extremos[0]['distancia'] < self::NEUTRO) {
            return [
                'arquetipo' => null,
                'neutro' => true,
                'compatibilidade' => 0.0,
                'probabilidade' => 0.0,
                'distribuicao' => [],
                'extremos' => $extremos,
                'eixo_1' => $eixo1,
                'polo_1' => $polo1,
                'eixo_2' => $eixo2,
                'polo_2' => $polo2,
            ];
        }

        $candidatos = [];

        foreach ($arquetipos as $arquetipo) {
            $vetorArquetipo = $arquetipo['vetor'] ?? [];

            if (!is_array($vetorArquetipo) || !Vetor::valido($vetorArquetipo)) {
                continue;
            }

            $nota = $this->compatibilidade($vetor, $vetorArquetipo);

            if ((int) $arquetipo['eixo_dominante_1'] === $eixo1
                && (string) $arquetipo['polo_1'] === $polo1
            ) {
                $nota += self::DOMINANCE_BONUS;
            }

            // O bônus é aplicado depois do clamp do matcher: sem este segundo
            // clamp a compatibilidade podia passar de 100.
            $candidatos[] = [
                'arquetipo' => $arquetipo,
                'nota' => round(min(100.0, max(0.0, $nota)), 2),
            ];
        }

        // Compatibilidade com bancos antigos (sem vetor nos arquétipos).
        if ($candidatos === []) {
            $antigos = array_values(array_filter(
                $arquetipos,
                static fn (array $a): bool => (int) $a['eixo_dominante_1'] === $eixo1
                    && (string) $a['polo_1'] === $polo1
            ));

            foreach ($antigos as $arquetipo) {
                $candidatos[] = ['arquetipo' => $arquetipo, 'nota' => 0.0];
            }
        }

        if ($candidatos === []) {
            return [
                'arquetipo' => null,
                'neutro' => true,
                'compatibilidade' => 0.0,
                'probabilidade' => 0.0,
                'distribuicao' => [],
                'extremos' => $extremos,
                'eixo_1' => $eixo1,
                'polo_1' => $polo1,
                'eixo_2' => $eixo2,
                'polo_2' => $polo2,
            ];
        }

        usort($candidatos, static function (array $a, array $b): int {
            return $b['nota'] <=> $a['nota']
                ?: (int) $a['arquetipo']['id'] <=> (int) $b['arquetipo']['id'];
        });

        $vencedor = $candidatos[0];
        $distribuicao = $this->distribuicao($candidatos);

        return [
            'arquetipo' => $vencedor['arquetipo'],
            'neutro' => false,
            'compatibilidade' => $vencedor['nota'],
            'probabilidade' => $distribuicao[0]['probabilidade'] ?? 0.0,
            'distribuicao' => $distribuicao,
            'extremos' => $extremos,
            'eixo_1' => $eixo1,
            'polo_1' => $polo1,
            'eixo_2' => $eixo2,
            'polo_2' => $polo2,
        ];
    }

    /**
     * Distribuição de probabilidade entre os arquétipos (softmax).
     *
     * O rótulo continua sendo o mais próximo, mas a probabilidade diz o quanto
     * o segundo colocado está perto: um perfil com 0,34 e 0,31 é um perfil de
     * trânsito, não uma identidade fechada.
     *
     * @param list<array{arquetipo: array<string, mixed>, nota: float}> $candidatos
     * @return list<array{id: int, titulo: string, compatibilidade: float, probabilidade: float}>
     */
    private function distribuicao(array $candidatos): array
    {
        if ($candidatos === []) {
            return [];
        }

        $max = 0.0;

        foreach ($candidatos as $candidato) {
            $max = max($max, (float) $candidato['nota']);
        }

        $pesos = [];
        $soma = 0.0;

        foreach ($candidatos as $candidato) {
            $peso = exp((((float) $candidato['nota']) - $max) / self::SOFTMAX_TEMPERATURA);
            $pesos[] = $peso;
            $soma += $peso;
        }

        $saida = [];

        foreach ($candidatos as $indice => $candidato) {
            $saida[] = [
                'id' => (int) ($candidato['arquetipo']['id'] ?? 0),
                'titulo' => (string) ($candidato['arquetipo']['titulo'] ?? ''),
                'compatibilidade' => round((float) $candidato['nota'], 1),
                'probabilidade' => $soma > 0 ? round($pesos[$indice] / $soma, 3) : 0.0,
            ];

            if (count($saida) >= 3) {
                break;
            }
        }

        return $saida;
    }

    /**
     * @param list<float> $vetor
     * @param list<list<float>> $vetores
     * @return array{percentual: float, similares: int, total: int, confiavel: bool, limiar: float, um_em: int|null}
     */
    public function raridade(array $vetor, array $vetores): array
    {
        $total = count($vetores);
        $similares = 0;

        foreach ($vetores as $outro) {
            if ($this->distanciaMedia($vetor, $outro) <= $this->limiarRaridade) {
                $similares++;
            }
        }

        $percentual = $total > 0 ? round(($similares / $total) * 100, 1) : 0.0;

        return [
            'percentual' => $percentual,
            'similares'  => $similares,
            'total'      => $total,
            'confiavel'  => $total >= $this->minBaseRaridade,
            'limiar'     => $this->limiarRaridade,
            'um_em'      => $similares > 0 ? (int) max(1, round($total / $similares)) : null,
        ];
    }

    /**
     * Modo Sinergia: compatibilidade entre dois perfis, ponto médio e os eixos
     * de maior convergência/divergência.
     *
     * @param list<float> $a
     * @param list<float> $b
     * @return array{
     *     compatibilidade: float,
     *     media: list<float>,
     *     convergencia: list<int>,
     *     divergencia: list<int>,
     *     diferencas: array<int, float>,
     *     veredito: string
     * }
     */
    public function comparar(array $a, array $b): array
    {
        $diferencas = [];

        for ($i = 0; $i < Vetor::EIXOS; $i++) {
            $diferencas[$i] = round(abs(((float) ($a[$i] ?? 50)) - ((float) ($b[$i] ?? 50))), 2);
        }

        $ordenadas = $diferencas;
        asort($ordenadas);
        $convergencia = array_slice(array_keys($ordenadas), 0, 2);

        $invertidas = $diferencas;
        arsort($invertidas);
        $divergencia = array_slice(array_keys($invertidas), 0, 2);

        $compatibilidade = $this->compatibilidade($a, $b);

        return [
            'compatibilidade' => $compatibilidade,
            'media'           => Vetor::media($a, $b),
            'convergencia'    => array_map('intval', $convergencia),
            'divergencia'     => array_map('intval', $divergencia),
            'diferencas'      => $diferencas,
            'veredito'        => $this->veredito($compatibilidade),
        ];
    }

    private function veredito(float $compatibilidade): string
    {
        $nota = (int) round($compatibilidade);

        return match (true) {
            $nota >= 95 => 'alma_gemea',
            $nota >= 85 => 'boa_companhia',
            $nota >= 65 => 'negociavel',
            $nota >= 45 => 'atrito',
            default     => 'briga',
        };
    }

    /** @param list<float> $a @param list<float> $b */
    private function distanciaMedia(array $a, array $b): float
    {
        $soma = 0.0;

        for ($i = 0; $i < Vetor::EIXOS; $i++) {
            $soma += abs(((float) ($a[$i] ?? 50)) - ((float) ($b[$i] ?? 50)));
        }

        return $soma / Vetor::EIXOS;
    }
}
