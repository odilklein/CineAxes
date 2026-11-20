<?php

declare(strict_types=1);

use Services\LeituraService;

require_once __DIR__ . '/_assert.php';

/** @return list<array<string, mixed>> */
function linhasDeTeste(array $pcts, array $polos = []): array
{
    $eixos = [
        ['slug' => 'estetica', 'nome' => 'Estética'],
        ['slug' => 'tom', 'nome' => 'Tom'],
        ['slug' => 'mundo', 'nome' => 'Mundo'],
        ['slug' => 'ritmo', 'nome' => 'Ritmo'],
        ['slug' => 'escala', 'nome' => 'Escala'],
        ['slug' => 'amplitude', 'nome' => 'Amplitude'],
    ];

    $linhas = [];

    foreach ($eixos as $indice => $eixo) {
        $pctDir = (float) ($pcts[$indice] ?? 50);
        $polo = $polos[$indice] ?? ($pctDir >= 50 ? 'D' : 'E');
        $distancia = abs($pctDir - 50);

        $linhas[] = [
            'eixo' => $eixo,
            'pct_dir' => $pctDir,
            'pct_esq' => 100 - $pctDir,
            'polo' => $polo,
            'intensidade' => $distancia >= 20 ? 'purista' : ($distancia >= 8 ? 'tendencia' : 'equilibrado'),
            'cor' => '#8A6A45',
            'cor_esq' => '#8A6A45',
            'cor_dir' => '#5E7F6A',
            'rotulo_esq' => 'Esquerda',
            'rotulo_dir' => 'Direita',
        ];
    }

    return $linhas;
}

$config = require dirname(__DIR__) . '/config/leituras.php';
$service = new LeituraService($config);

teste('leitura escolhe os quatro eixos mais distantes do neutro', function () use ($service): void {
    $linhas = linhasDeTeste([50, 60, 55, 48, 70, 90]);
    $leituras = $service->selecionar($linhas);

    igual(4, count($leituras));
    igual('amplitude', $leituras[0]['slug']);
    igual('escala', $leituras[1]['slug']);
    igual('tom', $leituras[2]['slug']);
    igual('mundo', $leituras[3]['slug']);
});

teste('leitura usa o polo real do eixo', function () use ($service): void {
    $linhas = linhasDeTeste([50, 50, 50, 50, 50, 67]);
    $leitura = $service->selecionar($linhas)[0];

    igual('Amplitude', $leitura['eixo']);
    igual('Direita', $leitura['polo']);
    igual('Você busca o que ainda não viu.', $leitura['manchete']);
    proximo(67.0, (float) $leitura['pct']);
});

teste('perfil de trânsito recebe a leitura neutra', function () use ($service): void {
    $linhas = linhasDeTeste([50, 51, 49, 50, 52, 48]);
    $leituras = $service->selecionar($linhas);

    igual(1, count($leituras));
    igual('neutro', $leituras[0]['slug']);
    igual('Entre os lados', $leituras[0]['curto']);
});

teste('força acompanha a distância do centro', function () use ($service): void {
    $leituras = $service->selecionar(linhasDeTeste([50, 50, 50, 50, 50, 90]));
    igual('traço forte', $leituras[0]['forca']);

    $leituras = $service->selecionar(linhasDeTeste([50, 50, 50, 50, 50, 62]));
    igual('traço claro', $leituras[0]['forca']);
});

teste('espelho é escolhido no mesmo lado do eixo', function () use ($service): void {
    $personagens = [
        ['nome' => 'Do lado direito', 'obra_origem' => 'Filme A', 'ano' => 2001, 'vetor' => [50, 50, 50, 50, 50, 88]],
        ['nome' => 'Do lado esquerdo', 'obra_origem' => 'Filme B', 'ano' => 1999, 'vetor' => [50, 50, 50, 50, 50, 12]],
        ['nome' => 'No meio', 'obra_origem' => 'Filme C', 'ano' => 2010, 'vetor' => array_fill(0, 6, 50.0)],
    ];

    $leitura = $service->selecionar(linhasDeTeste([50, 50, 50, 50, 50, 80]), $personagens)[0];

    igual('Do lado direito', $leitura['espelho']['nome']);
});

teste('config cobre os seis eixos com conteúdo completo', function () use ($config): void {
    igual(6, count($config['eixos']));

    foreach ($config['eixos'] as $slug => $polos) {
        foreach (['e', 'd'] as $polo) {
            $leitura = $polos[$polo] ?? null;
            afirmar(is_array($leitura), "{$slug}.{$polo} ausente");
            afirmar(trim((string) ($leitura['manchete'] ?? '')) !== '', "{$slug}.{$polo} sem manchete");
            afirmar(trim((string) ($leitura['corpo'] ?? '')) !== '', "{$slug}.{$polo} sem corpo");
            afirmar(trim((string) ($leitura['cansa'] ?? '')) !== '', "{$slug}.{$polo} sem cansa");
            afirmar(trim((string) ($leitura['curto'] ?? '')) !== '', "{$slug}.{$polo} sem curto");
            afirmar(count((array) ($leitura['combina'] ?? [])) >= 2, "{$slug}.{$polo} com menos de dois filmes");
        }
    }
});
