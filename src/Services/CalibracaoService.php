<?php

declare(strict_types=1);

namespace Services;

/**
 * Transforma o score bruto de compatibilidade em posição relativa.
 *
 * O problema do score absoluto é que ele é comprimido: um perfil neutro já
 * fica em ~82 de "afinidade" com o personagem mais próximo. Posição, percentil,
 * margem e intervalo de confiança dizem a mesma coisa sem vender precisão que
 * o questionário não tem.
 *
 * O serviço é puro: recebe vetores e respostas, devolve arrays. O bootstrap
 * reamostra as respostas de cada eixo (o mesmo questionário respondido por
 * alguém parecido) e mede o quanto o ranking se mexe.
 */
final class CalibracaoService
{
    public function __construct(private readonly MatcherService $matcher)
    {
    }

    /**
     * Anota posição, percentil, margem e z-score em uma lista já ordenada
     * (a saída de `MatcherService::ranquear()` ou `RecomendacaoService::rankear()`).
     *
     * Empates usam a posição de competição: dois itens com a mesma nota
     * aparecem ambos como "3º de 36", e não como 3º e 4º por sorte de ordem.
     *
     * @param list<array<string, mixed>> $ranqueadas
     * @return list<array<string, mixed>>
     */
    public function anotar(array $ranqueadas): array
    {
        $total = count($ranqueadas);

        if ($total === 0) {
            return [];
        }

        $notas = [];

        foreach ($ranqueadas as $item) {
            $notas[] = (float) ($item['compatibilidade'] ?? $item['afinidade'] ?? 0.0);
        }

        $media = array_sum($notas) / $total;
        $variancia = 0.0;

        foreach ($notas as $nota) {
            $variancia += ($nota - $media) ** 2;
        }

        $desvio = $total > 1 ? sqrt($variancia / $total) : 0.0;
        $posicao = 1;

        // Para cada posição, o próximo valor estritamente menor. Calculado da
        // direita para a esquerda: evita um laço interno que ficaria O(n²)
        // quando muitas obras empatam.
        $proximoDiferente = [];
        $valorProximo = null;

        for ($indice = $total - 1; $indice >= 0; $indice--) {
            if ($indice < $total - 1 && abs($notas[$indice] - $notas[$indice + 1]) > 0.0001) {
                $valorProximo = $notas[$indice + 1];
            }

            $proximoDiferente[$indice] = $valorProximo;
        }

        foreach ($ranqueadas as $indice => &$item) {
            $nota = $notas[$indice];
            $anterior = $indice > 0 ? $notas[$indice - 1] : null;

            if ($anterior === null || abs($nota - $anterior) > 0.0001) {
                $posicao = $indice + 1;
            }

            $margem = $proximoDiferente[$indice] !== null
                ? round($nota - $proximoDiferente[$indice], 1)
                : null;

            $item['posicao'] = $posicao;
            $item['de'] = $total;
            $item['top_pct'] = (int) max(1, min(100, (int) round(100 * $posicao / $total)));
            $item['percentil'] = (int) max(0, min(100, (int) round(100 * ($total - $posicao) / max(1, $total - 1))));
            $item['margem'] = $margem;
            $item['z'] = $desvio > 0 ? round(($nota - $media) / $desvio, 2) : 0.0;
        }
        unset($item);

        return $ranqueadas;
    }

    /**
     * Bootstrap de itens: reamostra as respostas de cada eixo e mede a
     * estabilidade do ranking.
     *
     * @param array<int, list<array{resposta: int, direcao: int, peso: int}>> $porEixo
     *        respostas agrupadas por id de eixo
     * @param list<int> $eixoIds ids na ordem do vetor
     * @param list<array{id: int|string, vetor: list<float>}> $itens
     * @return array{
     *     itens: array<string, array{mediana: int, ic95: array{0: int, 1: int}}>,
     *     top1: int|string|null,
     *     top1_fracao: float,
     *     iteracoes: int,
     *     por_eixo: float
     * }|null
     */
    public function estabilidade(
        array $porEixo,
        array $eixoIds,
        array $itens,
        int $iteracoes = 120,
        int $seed = 0
    ): ?array {
        if ($porEixo === [] || $eixoIds === [] || $itens === []) {
            return null;
        }

        $iteracoes = max(20, min(500, $iteracoes));
        // Semente fixa por perfil: o mesmo código mostra sempre o mesmo IC.
        // `random_int` é criptográfico e não pode ser semeado; usamos um LCG
        // local para manter a página reproduzível.
        $estado = $seed !== 0 ? $seed : 0x2545F491;
        $posicoes = [];
        $primeiros = [];

        for ($iteracao = 0; $iteracao < $iteracoes; $iteracao++) {
            $vetor = $this->vetorReamostrado($porEixo, $eixoIds, $estado);
            $notas = [];

            foreach ($itens as $item) {
                $notas[(string) $item['id']] = $this->matcher->compatibilidade($vetor, $item['vetor']);
            }

            arsort($notas);
            $posicao = 0;

            foreach ($notas as $id => $nota) {
                $posicao++;
                $posicoes[$id][] = $posicao;

                if ($posicao === 1) {
                    $primeiros[$id] = ($primeiros[$id] ?? 0) + 1;
                }
            }
        }

        if ($posicoes === []) {
            return null;
        }

        arsort($primeiros);
        $resumo = [];

        foreach ($posicoes as $id => $lista) {
            sort($lista);
            $quantidade = count($lista);
            $resumo[$id] = [
                'mediana' => (int) $lista[intdiv($quantidade, 2)],
                'ic95' => [
                    (int) $lista[(int) floor($quantidade * 0.025)],
                    (int) $lista[max(0, (int) ceil($quantidade * 0.975) - 1)],
                ],
            ];
        }

        $top1 = array_key_first($primeiros);
        $totalItens = 0;

        foreach ($porEixo as $itens) {
            $totalItens += count($itens);
        }

        return [
            'itens' => $resumo,
            'top1' => $top1,
            'top1_fracao' => round(((float) ($primeiros[$top1] ?? 0)) / $iteracoes, 3),
            'iteracoes' => $iteracoes,
            'por_eixo' => round($totalItens / max(1, count($porEixo)), 1),
        ];
    }

    /** Rótulo qualitativo da confiança a partir da fração estável do topo. */
    public function rotuloConfianca(?float $fracao): string
    {
        if ($fracao === null) {
            return 'nao_estimada';
        }

        return match (true) {
            $fracao >= 0.75 => 'alta',
            $fracao >= 0.45 => 'media',
            default         => 'baixa',
        };
    }

    /**
     * Reconstrói o vetor a partir de uma reamostragem com reposição das
     * respostas de cada eixo. A fórmula é a mesma do LikertCalculatorService.
     *
     * @param array<int, list<array{resposta: int, direcao: int, peso: int}>> $porEixo
     * @param list<int> $eixoIds
     * @param int $estado estado do LCG, alterado a cada sorteio
     * @return list<float>
     */
    private function vetorReamostrado(array $porEixo, array $eixoIds, int &$estado): array
    {
        $vetor = [];

        foreach ($eixoIds as $eixoId) {
            $itens = $porEixo[$eixoId] ?? [];
            $quantidade = count($itens);

            if ($quantidade === 0) {
                $vetor[] = 50.0;
                continue;
            }

            $soma = 0.0;
            $peso = 0;

            for ($i = 0; $i < $quantidade; $i++) {
                $item = $itens[$this->sorteio($quantidade, $estado)];
                $soma += (int) $item['resposta'] * (int) $item['direcao'] * (int) $item['peso'];
                $peso += (int) $item['peso'];
            }

            $percentual = $peso > 0 ? 50.0 + 50.0 * $soma / (2 * $peso) : 50.0;
            $vetor[] = round(max(0.0, min(100.0, $percentual)), 2);
        }

        return $vetor;
    }

    /**
     * Xorshift32 determinístico para o bootstrap reproduzível.
     *
     * Não usamos `% limite`: os bits baixos de um LCG têm período curto e
     * enviesam a reamostragem (o top-1 "estabilizava" em 47% em vez de 9%).
     * O mapeamento por multiplicação alta distribui melhor.
     */
    private function sorteio(int $limite, int &$estado): int
    {
        $estado ^= ($estado << 13) & 0xFFFFFFFF;
        $estado ^= $estado >> 17;
        $estado ^= ($estado << 5) & 0xFFFFFFFF;
        $estado &= 0xFFFFFFFF;

        if ($limite <= 0) {
            return 0;
        }

        return intdiv($estado * $limite, 0x100000000);
    }
}
