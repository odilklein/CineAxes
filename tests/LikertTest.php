<?php

declare(strict_types=1);

use Services\LikertCalculatorService;

require_once __DIR__ . '/_assert.php';

$servico = new LikertCalculatorService();

/** Perguntas sintéticas: 4 por eixo, 2 para cada polo, pesos 2 e 1. */
$perguntas = [];

for ($eixo = 1; $eixo <= 8; $eixo++) {
    $perguntas[$eixo * 10 + 1] = ['eixo_id' => $eixo, 'direcao' => -1, 'peso' => 2];
    $perguntas[$eixo * 10 + 2] = ['eixo_id' => $eixo, 'direcao' => 1, 'peso' => 2];
    $perguntas[$eixo * 10 + 3] = ['eixo_id' => $eixo, 'direcao' => -1, 'peso' => 1];
    $perguntas[$eixo * 10 + 4] = ['eixo_id' => $eixo, 'direcao' => 1, 'peso' => 1];
}

$eixos = range(1, 8);

teste('respostas neutras resultam em 50 em todos os eixos', function () use ($servico, $perguntas, $eixos): void {
    $respostas = array_fill_keys(array_keys($perguntas), 0);
    $resultado = $servico->calcular($respostas, $perguntas, $eixos);

    foreach ($resultado as $dados) {
        proximo(50.0, $dados['pct']);
    }
});

teste('questionário balanceado: -2 em tudo também é 50', function () use ($servico, $perguntas, $eixos): void {
    $respostas = array_fill_keys(array_keys($perguntas), -2);
    $resultado = $servico->calcular($respostas, $perguntas, $eixos);

    foreach ($resultado as $dados) {
        proximo(50.0, $dados['pct']);
    }
});

teste('concordar com o polo esquerdo leva a 0 no eixo', function () use ($servico, $perguntas, $eixos): void {
    $respostas = [11 => 2, 12 => -2];
    $resultado = $servico->calcular($respostas, $perguntas, $eixos);

    proximo(0.0, $resultado[1]['pct'], 0.01, 'eixo 1');
    proximo(50.0, $resultado[2]['pct'], 0.01, 'eixo 2 sem respostas');
});

teste('concordar com o polo direito leva a 100 no eixo', function () use ($servico, $perguntas, $eixos): void {
    $respostas = [11 => -2, 12 => 2];
    $resultado = $servico->calcular($respostas, $perguntas, $eixos);

    proximo(100.0, $resultado[1]['pct'], 0.01, 'eixo 1');
});

teste('normaliza pela soma dos pesos quando faltam respostas', function () use ($servico, $perguntas, $eixos): void {
    // Apenas a pergunta de peso 2 e direção -1: bruto = -4, denominador = 2 * 2.
    $resultado = $servico->calcular([11 => 2], $perguntas, $eixos);

    proximo(0.0, $resultado[1]['pct'], 0.01);
    igual(2, $resultado[1]['peso_total']);
    igual(1, $resultado[1]['respondidas']);
});

teste('resposta intermediária produz percentual proporcional', function () use ($servico, $perguntas, $eixos): void {
    // bruto = 1 * 2 = 2; denominador = 2 * 2 = 4 → 50 + 25 = 75.
    $resultado = $servico->calcular([12 => 1], $perguntas, $eixos);

    proximo(75.0, $resultado[1]['pct'], 0.01);
});

teste('validação rejeita valor fora da escala', function () use ($servico, $perguntas): void {
    $respostas = array_fill_keys(array_keys($perguntas), 0);
    $respostas[11] = 5;

    igual(1, count($servico->validarRespostas($respostas, $perguntas)));
});

teste('validação rejeita pergunta faltante', function () use ($servico, $perguntas): void {
    $respostas = array_fill_keys(array_keys($perguntas), 0);
    unset($respostas[12]);

    igual(1, count($servico->validarRespostas($respostas, $perguntas)));
});

teste('validação aceita um pacote completo e válido', function () use ($servico, $perguntas): void {
    $respostas = array_fill_keys(array_keys($perguntas), 1);

    igual([], $servico->validarRespostas($respostas, $perguntas));
});

teste('filtro remove ids desconhecidos e limita valores', function () use ($servico, $perguntas): void {
    $limpas = $servico->filtrarRespostas([11 => 9, 12 => -9, 999 => 2], $perguntas);

    igual([11 => 2, 12 => -2], $limpas);
});

teste('intensidade respeita as faixas 8 e 20', function () use ($servico): void {
    igual('equilibrado', $servico->intensidade(50.0));
    igual('equilibrado', $servico->intensidade(57.9));
    igual('tendencia', $servico->intensidade(58.0));
    igual('tendencia', $servico->intensidade(69.9));
    igual('purista', $servico->intensidade(70.0));
    igual('purista', $servico->intensidade(10.0));
});

teste('polo trata a margem neutra de 2 pontos', function () use ($servico): void {
    igual('N', $servico->polo(50.0));
    igual('N', $servico->polo(52.0));
    igual('D', $servico->polo(52.1));
    igual('E', $servico->polo(47.9));
});
