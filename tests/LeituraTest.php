<?php

declare(strict_types=1);

use Services\LeituraService;

require_once __DIR__ . '/_assert.php';

/** @return list<array<string, mixed>> */
function linhasDeTeste(array $pcts, array $polos = []): array
{
    $eixos = [
        ['slug' => 'estetica', 'nome' => 'Imagem'],
        ['slug' => 'tom', 'nome' => 'Emoção'],
        ['slug' => 'universo', 'nome' => 'Mundo'],
        ['slug' => 'ritmo', 'nome' => 'Ritmo'],
        ['slug' => 'moralidade', 'nome' => 'Ética'],
        ['slug' => 'estrutura', 'nome' => 'História'],
        ['slug' => 'atmosfera', 'nome' => 'Tamanho'],
        ['slug' => 'epoca', 'nome' => 'Época'],
    ];

    $linhas = [];

    foreach ($eixos as $indice => $eixo) {
        $pctDir = (float) ($pcts[$indice] ?? 50);
        $polo = $polos[$indice] ?? ($pctDir >= 50 ? 'D' : 'E');

        $linhas[] = [
            'eixo' => $eixo,
            'pct_dir' => $pctDir,
            'pct_esq' => 100 - $pctDir,
            'polo' => $polo,
            'intensidade' => abs($pctDir - 50) >= 20 ? 'purista' : (abs($pctDir - 50) >= 8 ? 'tendencia' : 'equilibrado'),
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
    $linhas = linhasDeTeste([50, 52, 55, 48, 70, 90, 60, 50]);
    $leituras = $service->selecionar($linhas);

    igual(4, count($leituras));
    igual('estrutura', $leituras[0]['slug']);
    igual('moralidade', $leituras[1]['slug']);
    igual('atmosfera', $leituras[2]['slug']);
    igual('universo', $leituras[3]['slug']);
});

teste('leitura usa o polo real do eixo', function () use ($service): void {
    $linhas = linhasDeTeste([50, 50, 50, 50, 50, 67, 50, 50]);
    $leitura = $service->selecionar($linhas)[0];

    igual('História', $leitura['eixo']);
    igual('Direita', $leitura['polo']);
    igual('Você não quer que a história entregue tudo pronto.', $leitura['manchete']);
    proximo(67.0, (float) $leitura['pct']);
});

teste('perfil de trânsito recebe a leitura neutra', function () use ($service): void {
    $linhas = linhasDeTeste([50, 51, 49, 50, 52, 48, 51, 49]);
    $leituras = $service->selecionar($linhas);

    igual(1, count($leituras));
    igual('neutro', $leituras[0]['slug']);
    igual('Entre os lados', $leituras[0]['curto']);
});

teste('força acompanha a distância do centro', function () use ($service): void {
    $leituras = $service->selecionar(linhasDeTeste([50, 50, 50, 50, 50, 90, 50, 50]));
    igual('traço forte', $leituras[0]['forca']);

    $leituras = $service->selecionar(linhasDeTeste([50, 50, 50, 50, 50, 62, 50, 50]));
    igual('traço claro', $leituras[0]['forca']);
});

teste('espelho é escolhido no mesmo lado do eixo', function () use ($service): void {
    $personagens = [
        ['nome' => 'Do lado direito', 'obra_origem' => 'Filme A', 'ano' => 2001, 'vetor' => [50, 50, 50, 50, 50, 88, 50, 50]],
        ['nome' => 'Do lado esquerdo', 'obra_origem' => 'Filme B', 'ano' => 1999, 'vetor' => [50, 50, 50, 50, 50, 12, 50, 50]],
        ['nome' => 'No meio', 'obra_origem' => 'Filme C', 'ano' => 2010, 'vetor' => array_fill(0, 8, 50.0)],
    ];

    $leitura = $service->selecionar(linhasDeTeste([50, 50, 50, 50, 50, 80, 50, 50]), $personagens)[0];

    igual('Do lado direito', $leitura['espelho']['nome']);
});

teste('config cobre os oito eixos com conteúdo completo', function () use ($config): void {
    igual(8, count($config['eixos']));

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
