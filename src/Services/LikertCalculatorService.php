<?php

declare(strict_types=1);

namespace Services;

/**
 * Converte respostas Likert (-2..+2) em percentuais de 0 a 100 por eixo.
 *
 * Fórmula (ver docs/EIXOS.md):
 *
 *   pct = 50 + 50 * Σ(resposta * direcao * peso) / (2 * Σ(peso respondido))
 *
 * O serviço é puro: não acessa banco, sessão nem tradução. Isso permite
 * testar a matemática sem infraestrutura (tests/LikertTest.php).
 */
final class LikertCalculatorService
{
    public const MIN = -2;
    public const MAX = 2;
    public const NEUTRO = 50.0;

    /**
     * @param array<int, int> $respostas   pergunta_id => -2..+2
     * @param array<int, array{eixo_id: int, direcao: int, peso: int}> $perguntas
     * @param list<int> $eixos             ids de eixo a inicializar (opcional)
     *
     * @return array<int, array{pct: float, bruto: float, peso_total: int, respondidas: int}>
     */
    public function calcular(array $respostas, array $perguntas, array $eixos = []): array
    {
        $ids = $eixos !== []
            ? array_map('intval', $eixos)
            : array_values(array_unique(array_map(
                static fn (array $p): int => (int) $p['eixo_id'],
                array_values($perguntas)
            )));

        $acumulado = [];

        foreach ($ids as $idEixo) {
            $acumulado[$idEixo] = ['bruto' => 0.0, 'peso_total' => 0, 'respondidas' => 0];
        }

        foreach ($perguntas as $idPergunta => $pergunta) {
            if (!array_key_exists($idPergunta, $respostas)) {
                continue;
            }

            $resposta = (int) $respostas[$idPergunta];

            if ($resposta < self::MIN || $resposta > self::MAX) {
                continue;
            }

            $idEixo = (int) $pergunta['eixo_id'];
            $peso   = max(1, (int) $pergunta['peso']);
            $direcao = ((int) $pergunta['direcao']) >= 0 ? 1 : -1;

            $acumulado[$idEixo] ??= ['bruto' => 0.0, 'peso_total' => 0, 'respondidas' => 0];
            $acumulado[$idEixo]['bruto'] += $resposta * $direcao * $peso;
            $acumulado[$idEixo]['peso_total'] += $peso;
            $acumulado[$idEixo]['respondidas']++;
        }

        $saida = [];

        foreach ($acumulado as $idEixo => $dados) {
            $percentual = $dados['peso_total'] > 0
                ? self::NEUTRO + 50.0 * ($dados['bruto'] / (2 * $dados['peso_total']))
                : self::NEUTRO;

            $saida[$idEixo] = [
                'pct'        => round(max(0.0, min(100.0, $percentual)), 2),
                'bruto'      => round($dados['bruto'], 2),
                'peso_total' => $dados['peso_total'],
                'respondidas'=> $dados['respondidas'],
            ];
        }

        return $saida;
    }

    /**
     * Valida o pacote de respostas antes de calcular.
     *
     * @param array<int, mixed> $respostas
     * @param array<int, array<string, mixed>> $perguntas
     * @return list<string> mensagens de erro (vazio = válido)
     */
    public function validarRespostas(array $respostas, array $perguntas): array
    {
        $erros = [];

        foreach ($perguntas as $idPergunta => $pergunta) {
            if (!array_key_exists($idPergunta, $respostas)) {
                $erros[] = sprintf('A pergunta %d não foi respondida.', $idPergunta);
                continue;
            }

            $valor = $respostas[$idPergunta];

            if (is_bool($valor) || !is_numeric($valor) || (int) $valor != $valor) {
                $erros[] = sprintf('Resposta inválida para a pergunta %d.', $idPergunta);
                continue;
            }

            if ((int) $valor < self::MIN || (int) $valor > self::MAX) {
                $erros[] = sprintf('A resposta da pergunta %d está fora da escala.', $idPergunta);
            }
        }

        return $erros;
    }

    /**
     * Remove respostas de perguntas que não fazem parte do questionário atual
     * (proteção contra payload adulterado).
     *
     * @param array<int, mixed> $respostas
     * @param array<int, array<string, mixed>> $perguntas
     * @return array<int, int>
     */
    public function filtrarRespostas(array $respostas, array $perguntas): array
    {
        $limpas = [];

        foreach ($perguntas as $idPergunta => $pergunta) {
            if (!array_key_exists($idPergunta, $respostas)) {
                continue;
            }

            $valor = $respostas[$idPergunta];

            if (is_numeric($valor) && (int) $valor == $valor) {
                $limpas[(int) $idPergunta] = max(self::MIN, min(self::MAX, (int) $valor));
            }
        }

        return $limpas;
    }

    /** Distância de 50: Equilibrado (<8), Tendência (<20) ou Purista. */
    public function intensidade(float $percentual): string
    {
        $distancia = abs($percentual - self::NEUTRO);

        if ($distancia < 8.0) {
            return 'equilibrado';
        }

        return $distancia < 20.0 ? 'tendencia' : 'purista';
    }

    /** Polo dominante do eixo: E, D ou N (neutro). */
    public function polo(float $percentual, float $margem = 2.0): string
    {
        if (abs($percentual - self::NEUTRO) <= $margem) {
            return 'N';
        }

        return $percentual > self::NEUTRO ? 'D' : 'E';
    }
}
