<?php

declare(strict_types=1);

use Services\MatcherService;

require_once __DIR__ . '/_assert.php';

$matcher = new MatcherService(12.0, 100);

$vetor = [50.0, 50.0, 90.0, 50.0, 20.0, 50.0, 50.0, 50.0];

$entidades = [
    [
        'id' => 1, 'tipo' => 'personagem', 'nome' => 'Perto',
        'vetor' => [50.0, 50.0, 78.0, 50.0, 22.0, 50.0, 50.0, 50.0],
    ],
    [
        'id' => 2, 'tipo' => 'personagem', 'nome' => 'Longe',
        'vetor' => [10.0, 90.0, 10.0, 90.0, 90.0, 10.0, 90.0, 10.0],
    ],
    [
        'id' => 3, 'tipo' => 'diretor', 'nome' => 'Meio',
        'vetor' => [50.0, 50.0, 50.0, 50.0, 50.0, 50.0, 50.0, 50.0],
    ],
];

teste('vetores idênticos têm compatibilidade 100', function () use ($matcher): void {
    $a = [30.0, 40.0, 50.0, 60.0, 70.0, 80.0, 90.0, 10.0];

    proximo(0.0, $matcher->distancia($a, $a));
    proximo(100.0, $matcher->compatibilidade($a, $a));
    proximo(100.0, $matcher->afinidade($a, $a));
});

teste('vetores opostos perdem a maior parte da compatibilidade', function () use ($matcher): void {
    $a = array_fill(0, 8, 0.0);
    $b = array_fill(0, 8, 100.0);

    proximo(282.84, $matcher->distancia($a, $b), 0.01);
    afirmar($matcher->compatibilidade($a, $b) < 25.0, 'opostos deveriam ficar abaixo de 25%');
});

teste('diferença uniforme de 10 pontos fica perto de 88%', function () use ($matcher): void {
    $a = array_fill(0, 8, 50.0);
    $b = array_fill(0, 8, 60.0);

    proximo(88.5, $matcher->compatibilidade($a, $b), 0.5);
});

teste('um único eixo fora da curva pesa mais que a média linear', function () use ($matcher): void {
    $a = array_fill(0, 8, 50.0);
    $b = [90.0, 50.0, 50.0, 50.0, 50.0, 50.0, 50.0, 50.0];

    $nota = $matcher->compatibilidade($a, $b);

    afirmar($nota > 80.0 && $nota < 90.0, 'esperado entre 80 e 90, obtido ' . $nota);
});

teste('ranqueamento ordena pela compatibilidade', function () use ($matcher, $vetor, $entidades): void {
    $ranqueadas = $matcher->ranquear($vetor, $entidades);

    igual('Perto', $ranqueadas[0]['nome']);
    igual('Meio', $ranqueadas[1]['nome']);
    igual('Longe', $ranqueadas[2]['nome']);
});

teste('topPorTipo filtra e limita', function () use ($matcher, $vetor, $entidades): void {
    $ranqueadas = $matcher->ranquear($vetor, $entidades);

    igual(2, count($matcher->topPorTipo($ranqueadas, 'personagem', 3)));
    igual(1, count($matcher->topPorTipo($ranqueadas, 'diretor', 1)));
    igual(0, count($matcher->topPorTipo($ranqueadas, 'estudio', 3)));
});

teste('extremos encontram o eixo mais distante de 50', function () use ($matcher, $vetor): void {
    $extremos = $matcher->extremos($vetor, 2);

    igual(2, $extremos[0]['indice'], 'eixo dominante');
    proximo(90.0, $extremos[0]['pct']);
    igual(4, $extremos[1]['indice'], 'segundo eixo');
});

teste('extremos desempatam pelo maior índice', function () use ($matcher): void {
    $extremos = $matcher->extremos([50.0, 50.0, 100.0, 50.0, 0.0, 50.0, 50.0, 50.0], 2);

    igual(4, $extremos[0]['indice']);
    igual(2, $extremos[1]['indice']);
});

teste('resolverArquetipo escolhe o vetor mais próximo', function () use ($matcher): void {
    $arquetipos = [
        [
            'id' => 10, 'eixo_dominante_1' => 3, 'polo_1' => 'D',
            'eixo_dominante_2' => null, 'polo_2' => null,
            'vetor' => [50.0, 50.0, 90.0, 50.0, 30.0, 50.0, 50.0, 50.0],
        ],
        [
            'id' => 11, 'eixo_dominante_1' => 1, 'polo_1' => 'E',
            'eixo_dominante_2' => null, 'polo_2' => null,
            'vetor' => [50.0, 50.0, 50.0, 50.0, 50.0, 50.0, 50.0, 50.0],
        ],
    ];

    $resolucao = $matcher->resolverArquetipo(
        [50.0, 50.0, 100.0, 50.0, 30.0, 50.0, 50.0, 50.0],
        $arquetipos,
        range(1, 8)
    );

    igual(false, $resolucao['neutro']);
    igual(3, $resolucao['eixo_1']);
    igual('D', $resolucao['polo_1']);
    igual(10, $resolucao['arquetipo']['id']);
    afirmar($resolucao['compatibilidade'] > 90.0, 'compatibilidade do arquétipo');
});

teste('perfil neutro não recebe arquétipo', function () use ($matcher): void {
    $resolucao = $matcher->resolverArquetipo(array_fill(0, 8, 50.0), [], range(1, 8));

    igual(true, $resolucao['neutro']);
    igual(null, $resolucao['arquetipo']);
});

teste('raridade calcula percentual de perfis similares', function () use ($matcher): void {
    $vetor = array_fill(0, 8, 50.0);
    $base = [
        array_fill(0, 8, 50.0),
        array_fill(0, 8, 55.0),
        array_fill(0, 8, 45.0),
        array_fill(0, 8, 100.0),
    ];

    $raridade = $matcher->raridade($vetor, $base);

    proximo(75.0, $raridade['percentual']);
    igual(3, $raridade['similares']);
    igual(4, $raridade['total']);
    igual(false, $raridade['confiavel'], 'base abaixo do mínimo');
    igual(1, $raridade['um_em']);
});

teste('raridade com base vazia não divide por zero', function () use ($matcher): void {
    $raridade = $matcher->raridade(array_fill(0, 8, 50.0), []);

    proximo(0.0, $raridade['percentual']);
    igual(null, $raridade['um_em']);
});

teste('sinergia de perfis iguais é 100% e alma gêmea', function () use ($matcher): void {
    $a = [10.0, 20.0, 30.0, 40.0, 50.0, 60.0, 70.0, 80.0];
    $comparacao = $matcher->comparar($a, $a);

    proximo(100.0, $comparacao['compatibilidade']);
    igual('alma_gemea', $comparacao['veredito']);
    igual($a, $comparacao['media']);
});

teste('sinergia identifica convergência e divergência', function () use ($matcher): void {
    $a = array_fill(0, 8, 50.0);
    $b = [90.0, 50.0, 50.0, 50.0, 50.0, 50.0, 50.0, 50.0];
    $comparacao = $matcher->comparar($a, $b);

    afirmar($comparacao['compatibilidade'] > 80.0, 'um eixo fora da curva');
    afirmar(in_array(0, $comparacao['divergencia'], true), 'eixo 0 deve divergir');
    afirmar(!in_array(0, $comparacao['convergencia'], true), 'eixo 0 não deve convergir');
    proximo(70.0, $comparacao['media'][0]);
});

teste('perfis opostos recebem veredito de briga', function () use ($matcher): void {
    $a = array_fill(0, 8, 20.0);
    $b = array_fill(0, 8, 80.0);
    $comparacao = $matcher->comparar($a, $b);

    igual('briga', $comparacao['veredito']);

    foreach ($comparacao['media'] as $valor) {
        proximo(50.0, $valor);
    }
});
