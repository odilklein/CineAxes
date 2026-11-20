<?php

declare(strict_types=1);

namespace Controllers;

use Models\Arquetipo;
use Models\Eixo;
use Models\EntidadeMatch;
use Models\Pergunta;
use Models\Resultado;
use PDO;
use RuntimeException;
use Services\LikertCalculatorService;
use Services\MatcherService;

/**
 * Orquestra o envio do questionário: valida, calcula, casa com entidades,
 * persiste e devolve o código público.
 */
final class QuizController
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly LikertCalculatorService $likert,
        private readonly MatcherService $matcher
    ) {
    }

    /**
     * @param array<int, mixed> $respostas pergunta_id => -2..+2
     * @return array{codigo: string, vetor: list<float>, arquetipo: array<string, mixed>|null, neutro: bool}
     *
     * @throws \InvalidArgumentException quando o payload é inválido
     */
    public function processar(string $modo, array $respostas, ?string $ipHash = null): array
    {
        $modo = isset(Pergunta::MODOS[$modo]) ? $modo : 'completo';
        $perguntas = Pergunta::paraModo($this->pdo, $modo);

        if ($perguntas === []) {
            throw new RuntimeException('Nenhuma pergunta cadastrada para este modo.');
        }

        // A validação e o cálculo trabalham com mapa pergunta_id => metadados.
        $mapaPerguntas = Pergunta::mapaParaCalculo($perguntas);

        $erros = $this->likert->validarRespostas($respostas, $mapaPerguntas);

        if ($erros !== []) {
            throw new \InvalidArgumentException($erros[0]);
        }

        $limpas = $this->likert->filtrarRespostas($respostas, $mapaPerguntas);

        $eixos = Eixo::todos($this->pdo);
        $eixoIds = array_map(static fn (array $e): int => (int) $e['id'], $eixos);

        $porEixo = $this->likert->calcular(
            $limpas,
            $mapaPerguntas,
            $eixoIds
        );

        $percentuais = array_map(
            static fn (array $dados): float => (float) $dados['pct'],
            $porEixo
        );

        $vetor = Eixo::vetorOrdenado($eixos, $percentuais);

        $resolucao = $this->matcher->resolverArquetipo(
            $vetor,
            Arquetipo::todos($this->pdo),
            $eixoIds
        );

        $codigo = $this->gerarCodigoUnico();

        Resultado::criar($this->pdo, [
            'codigo'        => $codigo,
            'idioma'        => 'pt-BR',
            'modo'          => $modo,
            'respostas_json' => json_encode($limpas, JSON_UNESCAPED_UNICODE),
            'questionario_versao' => Pergunta::VERSAO,
            'vetor'         => $vetor,
            'arquetipo_id'  => $resolucao['arquetipo']['id'] ?? null,
            'origem'        => 'real',
            'ip_hash'       => $ipHash,
        ]);

        return [
            'codigo'    => $codigo,
            'vetor'     => $vetor,
            'arquetipo' => $resolucao['arquetipo'],
            'neutro'    => (bool) $resolucao['neutro'],
        ];
    }

    private function gerarCodigoUnico(): string
    {
        for ($tentativa = 0; $tentativa < 12; $tentativa++) {
            $codigo = Resultado::gerarCodigo();

            if (!Resultado::codigoExiste($this->pdo, $codigo)) {
                return $codigo;
            }
        }

        throw new RuntimeException('Não foi possível gerar um código único.');
    }
}
