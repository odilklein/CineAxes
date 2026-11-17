<?php

declare(strict_types=1);

use Models\Pergunta;

require_once __DIR__ . '/_assert.php';

teste('modos têm 36, 60 e 240 perguntas', function (): void {
    igual(36, Pergunta::MODOS['rapido']);
    igual(60, Pergunta::MODOS['completo']);
    igual(240, Pergunta::MODOS['extremo']);
});

teste('cota do modo rápido soma 36 e é par em todos os eixos', function (): void {
    $cotas = Pergunta::cotas(36, array_fill(0, 8, 30));

    igual(36, array_sum($cotas));
    igual([6, 6, 4, 4, 4, 4, 4, 4], $cotas);

    foreach ($cotas as $cota) {
        igual(0, $cota % 2, 'cota deveria ser par');
    }
});

teste('cota do modo completo soma 60 e é par em todos os eixos', function (): void {
    $cotas = Pergunta::cotas(60, array_fill(0, 8, 30));

    igual(60, array_sum($cotas));
    igual([8, 8, 8, 8, 8, 8, 6, 6], $cotas);
    igual(0, array_sum($cotas) % 2 * 1);
});

teste('cota do modo extremo usa todas as 30 de cada eixo', function (): void {
    igual([30, 30, 30, 30, 30, 30, 30, 30], Pergunta::cotas(240, array_fill(0, 8, 30)));
});

teste('cota nunca passa do que o eixo tem', function (): void {
    $cotas = Pergunta::cotas(240, [4, 4, 4, 4, 4, 4, 4, 4]);

    igual([4, 4, 4, 4, 4, 4, 4, 4], $cotas);
    igual(32, array_sum($cotas));
});

teste('cota de eixo vazio fica em zero e o resto é redistribuído', function (): void {
    $cotas = Pergunta::cotas(36, [30, 0, 30, 30, 30, 30, 30, 30]);

    igual(0, $cotas[1]);
    igual(36, array_sum($cotas), 'o modo não deveria encolher');

    foreach ($cotas as $cota) {
        igual(0, $cota % 2, 'cota deveria continuar par');
    }
});

teste('cota cresce junto com o modo (subconjunto por eixo)', function (): void {
    $rapido = Pergunta::cotas(36, array_fill(0, 8, 30));
    $completo = Pergunta::cotas(60, array_fill(0, 8, 30));
    $extremo = Pergunta::cotas(240, array_fill(0, 8, 30));

    foreach ($rapido as $i => $cota) {
        afirmar($cota <= $completo[$i], 'rápido deveria caber no completo');
        afirmar($completo[$i] <= $extremo[$i], 'completo deveria caber no extremo');
    }
});
