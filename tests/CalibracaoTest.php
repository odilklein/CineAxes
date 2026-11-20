<?php

declare(strict_types=1);

use Services\CalibracaoService;
use Services\MatcherService;

require_once __DIR__ . '/_assert.php';

$matcher = new MatcherService(12.0, 100);
$calibracao = new CalibracaoService($matcher);

teste('anotar usa posição de competição em empates', function () use ($calibracao): void {
    $ranqueadas = [
        ['id' => 1, 'nome' => 'A', 'compatibilidade' => 90.0],
        ['id' => 2, 'nome' => 'B', 'compatibilidade' => 90.0],
        ['id' => 3, 'nome' => 'C', 'compatibilidade' => 80.0],
    ];

    $anotadas = $calibracao->anotar($ranqueadas);

    igual(1, $anotadas[0]['posicao']);
    igual(1, $anotadas[1]['posicao']);
    igual(3, $anotadas[2]['posicao']);
    igual(3, $anotadas[0]['de']);
    proximo(10.0, $anotadas[0]['margem'], 0.01);
    proximo(10.0, $anotadas[1]['margem'], 0.01);
    igual(null, $anotadas[2]['margem']);
});

teste('anotar calcula percentil, top_pct e z', function () use ($calibracao): void {
    $anotadas = $calibracao->anotar([
        ['id' => 1, 'compatibilidade' => 90.0],
        ['id' => 2, 'compatibilidade' => 70.0],
        ['id' => 3, 'compatibilidade' => 50.0],
    ]);

    igual(100, $anotadas[0]['percentil']);
    igual(0, $anotadas[2]['percentil']);
    igual(33, $anotadas[0]['top_pct']);
    igual(100, $anotadas[2]['top_pct']);
    afirmar($anotadas[0]['z'] > 0.0, 'o primeiro deve ter z positivo');
    afirmar($anotadas[2]['z'] < 0.0, 'o último deve ter z negativo');
});

teste('anotar lida com lista vazia', function () use ($calibracao): void {
    igual([], $calibracao->anotar([]));
});

teste('bootstrap devolve posições dentro do intervalo', function () use ($calibracao): void {
    $porEixo = [];

    foreach (range(1, 6) as $eixo) {
        $porEixo[$eixo] = [
            ['resposta' => 2, 'direcao' => 1, 'peso' => 1],
            ['resposta' => -2, 'direcao' => 1, 'peso' => 1],
        ];
    }

    $itens = [
        ['id' => 1, 'vetor' => array_fill(0, 6, 50.0)],
        ['id' => 2, 'vetor' => array_fill(0, 6, 90.0)],
        ['id' => 3, 'vetor' => array_fill(0, 6, 10.0)],
    ];

    $resultado = $calibracao->estabilidade($porEixo, range(1, 6), $itens, 40);

    afirmar($resultado !== null, 'bootstrap deveria devolver estrutura');

    foreach ($itens as $item) {
        $chave = (string) $item['id'];
        afirmar(isset($resultado['itens'][$chave]), 'item ' . $chave . ' ausente');
        afirmar($resultado['itens'][$chave]['mediana'] >= 1, 'mediana abaixo de 1');
        afirmar($resultado['itens'][$chave]['mediana'] <= 3, 'mediana acima do total');
        afirmar($resultado['itens'][$chave]['ic95'][0] <= $resultado['itens'][$chave]['ic95'][1], 'IC invertido');
    }

    afirmar($resultado['top1_fracao'] >= 0.0 && $resultado['top1_fracao'] <= 1.0, 'fração inválida');
    igual(40, $resultado['iteracoes']);
    proximo(2.0, $resultado['por_eixo'], 0.01);
});

teste('bootstrap sem respostas não inventa confiança', function () use ($calibracao): void {
    igual(null, $calibracao->estabilidade([], range(1, 6), [['id' => 1, 'vetor' => array_fill(0, 6, 50.0)]]));
});

teste('rótulo de confiança traduz a fração estável', function () use ($calibracao): void {
    igual('nao_estimada', $calibracao->rotuloConfianca(null));
    igual('alta', $calibracao->rotuloConfianca(0.80));
    igual('media', $calibracao->rotuloConfianca(0.50));
    igual('baixa', $calibracao->rotuloConfianca(0.20));
});

teste('arquétipo nunca passa de 100 com o bônus de dominância', function () use ($matcher): void {
    $arquetipos = [[
        'id' => 7,
        'eixo_dominante_1' => 3,
        'polo_1' => 'D',
        'vetor' => [50.0, 50.0, 100.0, 50.0, 30.0, 50.0],
    ]];

    $resolucao = $matcher->resolverArquetipo(
        [50.0, 50.0, 100.0, 50.0, 30.0, 50.0],
        $arquetipos,
        range(1, 6)
    );

    afirmar($resolucao['compatibilidade'] <= 100.0, 'compatibilidade acima de 100');
    igual(7, $resolucao['arquetipo']['id']);
});

teste('distribuição do arquétipo tem no máximo três entradas e soma até 1', function () use ($matcher): void {
    $arquetipos = [];

    foreach ([50.0, 60.0, 70.0, 80.0] as $indice => $valor) {
        $arquetipos[] = [
            'id' => $indice + 1,
            'eixo_dominante_1' => 3,
            'polo_1' => 'D',
            'vetor' => [50.0, 50.0, $valor, 50.0, 50.0, 50.0],
        ];
    }

    $resolucao = $matcher->resolverArquetipo(
        [50.0, 50.0, 80.0, 50.0, 50.0, 50.0],
        $arquetipos,
        range(1, 6)
    );

    afirmar(count($resolucao['distribuicao']) <= 3, 'mais de três entradas');
    afirmar($resolucao['distribuicao'] !== [], 'distribuição vazia');
    $soma = array_sum(array_column($resolucao['distribuicao'], 'probabilidade'));
    afirmar($soma <= 1.001, 'probabilidade acima de 1');
    afirmar($resolucao['probabilidade'] > 0.0, 'probabilidade do vencedor ausente');
});

teste('limiar de neutro é o mesmo da escala de intensidade', function () use ($matcher): void {
    $neutro = $matcher->resolverArquetipo(
        array_fill(0, 6, 54.0),
        [[
            'id' => 1,
            'eixo_dominante_1' => 1,
            'polo_1' => 'D',
            'vetor' => array_fill(0, 6, 54.0),
        ]],
        range(1, 6)
    );

    igual(true, $neutro['neutro'], 'distância 4 ainda é equilibrado');

    $definido = $matcher->resolverArquetipo(
        array_fill(0, 6, 62.0),
        [[
            'id' => 1,
            'eixo_dominante_1' => 1,
            'polo_1' => 'D',
            'vetor' => array_fill(0, 6, 62.0),
        ]],
        range(1, 6)
    );

    igual(false, $definido['neutro']);
});
