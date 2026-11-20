<?php

declare(strict_types=1);

use Core\Radar;

require_once __DIR__ . '/_assert.php';

$rotulos = ['Estética', 'Tom', 'Mundo', 'Ritmo', 'Escala', 'Amplitude'];
$valores = [20, 80, 50, 40, 90, 10];

teste('radar gera um svg com seis eixos', function () use ($rotulos, $valores): void {
    $svg = Radar::render($rotulos, [
        ['nome' => 'Seu perfil', 'valores' => $valores, 'cor' => '#53201c'],
    ]);

    afirmar(str_contains($svg, '<svg'), 'svg ausente');
    igual(6, substr_count($svg, 'class="pc-radar-label"'), 'rótulos');
    igual(6, substr_count($svg, 'class="pc-radar-ponto"'), 'pontos');
    afirmar(substr_count($svg, 'pc-radar-raio') === 6, 'raios');
    afirmar(str_contains($svg, 'pc-radar-anel--neutro'), 'anel neutro');
});

teste('radar escapa rótulos e aceita duas séries', function (): void {
    $svg = Radar::render(['A', '<script>alert(1)</script>', 'C'], [
        ['nome' => 'A', 'valores' => [10, 20, 30], 'cor' => '#111111'],
        ['nome' => 'B', 'valores' => [90, 80, 70], 'cor' => '#222222'],
    ]);

    afirmar(!str_contains($svg, '<script>'), 'rótulo não escapado');
    igual(2, substr_count($svg, 'class="pc-radar-serie"'), 'séries');
});

teste('radar limita valores fora da escala', function (): void {
    $svg = Radar::render(['A', 'B', 'C'], [
        ['nome' => 'A', 'valores' => [-50, 150, 50], 'cor' => '#111111'],
    ]);

    afirmar(str_contains($svg, 'class="pc-radar-ponto"'), 'pontos ausentes');
});
