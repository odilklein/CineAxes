<?php

declare(strict_types=1);

use Models\Pergunta;

require_once __DIR__ . '/_assert.php';

teste('modos têm 24, 36 e 48 perguntas', function (): void {
    igual(24, Pergunta::MODOS['rapido']);
    igual(36, Pergunta::MODOS['completo']);
    igual(48, Pergunta::MODOS['extremo']);
});

teste('cota do modo essencial soma 24 e é par em todos os eixos', function (): void {
    $cotas = Pergunta::cotas(24, array_fill(0, 6, 8));

    igual(24, array_sum($cotas));
    igual([4, 4, 4, 4, 4, 4], $cotas);

    foreach ($cotas as $cota) {
        igual(0, $cota % 2, 'cota deveria ser par');
    }
});

teste('cota do modo completo soma 36 e é par em todos os eixos', function (): void {
    $cotas = Pergunta::cotas(36, array_fill(0, 6, 8));

    igual(36, array_sum($cotas));
    igual([6, 6, 6, 6, 6, 6], $cotas);
});

teste('cota do modo profundo usa as 8 de cada eixo', function (): void {
    igual([8, 8, 8, 8, 8, 8], Pergunta::cotas(48, array_fill(0, 6, 8)));
});

teste('cota nunca passa do que o eixo tem', function (): void {
    $cotas = Pergunta::cotas(48, [4, 4, 4, 4, 4, 4]);

    igual([4, 4, 4, 4, 4, 4], $cotas);
    igual(24, array_sum($cotas));
});

teste('cota de eixo vazio fica em zero e o resto é redistribuído', function (): void {
    $cotas = Pergunta::cotas(24, [8, 0, 8, 8, 8, 8]);

    igual(0, $cotas[1]);
    igual(24, array_sum($cotas), 'o modo não deveria encolher');

    foreach ($cotas as $cota) {
        igual(0, $cota % 2, 'cota deveria continuar par');
    }
});

teste('cota cresce junto com o modo (subconjunto por eixo)', function (): void {
    $rapido = Pergunta::cotas(24, array_fill(0, 6, 8));
    $completo = Pergunta::cotas(36, array_fill(0, 6, 8));
    $profundo = Pergunta::cotas(48, array_fill(0, 6, 8));

    foreach ($rapido as $i => $cota) {
        afirmar($cota <= $completo[$i], 'essencial deveria caber no completo');
        afirmar($completo[$i] <= $profundo[$i], 'completo deveria caber no profundo');
    }
});
