<?php

declare(strict_types=1);

/**
 * Testes dos serviços de recomendação, ranking de plataformas e trilhas.
 * Rodam sem banco: usam vetores e linhas sintéticas.
 */

use Services\MatcherService;
use Services\PlataformaRankingService;
use Services\RecomendacaoService;
use Services\TrilhaService;

require_once __DIR__ . '/_assert.php';

$recConfig = require dirname(__DIR__) . '/config/recomendacao.php';
$matcher = new MatcherService();
$rec = new RecomendacaoService($recConfig, $matcher);

teste('vetor de obra vazio fica no neutro e sem confiança', function () use ($rec): void {
    $resultado = $rec->construir(['generos' => [], 'paises' => [], 'idioma' => null, 'ano' => null]);

    igual([50.0, 50.0, 50.0, 50.0, 50.0, 50.0, 50.0, 50.0], $resultado['vetor']);
    igual(0, $resultado['confianca']);
});

teste('terror puxa para o polo melancólico e íntimo', function () use ($rec): void {
    $resultado = $rec->construir([
        'generos' => [27],
        'paises'  => ['US'],
        'idioma'  => 'en',
        'ano'     => 2020,
    ]);

    afirmar($resultado['vetor'][1] < 40, 'emoção deveria ficar abaixo de 40');
    afirmar($resultado['vetor'][6] < 50, 'tamanho deveria pender para íntimo');
    afirmar($resultado['confianca'] > 0, 'esperava confiança positiva');
});

teste('anime japonês puxa para o fantástico e para o passado', function () use ($rec): void {
    $resultado = $rec->construir([
        'generos' => [16, 14],
        'paises'  => ['JP'],
        'idioma'  => 'ja',
        'ano'     => 2001,
    ]);

    afirmar($resultado['vetor'][2] > 65, 'mundo deveria ficar fantástico');
    afirmar($resultado['vetor'][3] < 50, 'ritmo deveria pender para contemplativo');
});

teste('afinidade encolhe quando a confiança é baixa', function () use ($rec): void {
    $usuario = [50.0, 15.0, 50.0, 50.0, 50.0, 50.0];
    $obra = [50.0, 15.0, 50.0, 50.0, 50.0, 50.0];

    $comConfianca = $rec->afinidade($usuario, ['vetor' => $obra, 'confianca' => 100]);
    $semConfianca = $rec->afinidade($usuario, ['vetor' => $obra, 'confianca' => 0]);

    afirmar($comConfianca > $semConfianca, 'confiança 100 deveria render nota maior');
    afirmar($semConfianca >= 50, 'o piso da nota é 50');
});

teste('motivos só cita eixos em que perfil e obra apontam para o mesmo lado', function () use ($rec): void {
    $usuario = [80.0, 20.0, 50.0, 50.0, 50.0, 50.0];
    $obra = [85.0, 15.0, 50.0, 50.0, 50.0, 50.0];

    $motivos = $rec->motivos($usuario, ['vetor' => $obra]);
    $indices = array_column($motivos, 'indice');

    igual([1, 2], $indices);
});

teste('trilha respeita tipo, país e gênero', function () use ($rec): void {
    $dorama = ['tipo' => 'serie', 'paises' => ['KR'], 'generos' => [18, 10749]];
    $filmeCoreano = ['tipo' => 'filme', 'paises' => ['KR'], 'generos' => [18]];
    $anime = ['tipo' => 'serie', 'paises' => ['JP'], 'generos' => [16, 14]];
    $reality = ['tipo' => 'serie', 'paises' => ['KR'], 'generos' => [10764]];

    igual('coreia-doramas', $rec->trilhaDe($dorama));
    igual('coreia-cinema', $rec->trilhaDe($filmeCoreano));
    igual('japao-anime', $rec->trilhaDe($anime));
    afirmar($rec->trilhaDe($reality) !== 'coreia-doramas', 'reality não deveria entrar em doramas');
});

teste('plataforma pequena não entra no ranking', function () use ($recConfig): void {
    $config = $recConfig;
    $config['limiares']['min_titulos_plataforma'] = 5;
    $servico = new PlataformaRankingService($config);

    $obras = [];

    for ($i = 1; $i <= 3; $i++) {
        $obras[] = [
            'id' => $i,
            'afinidade' => 90.0,
            'forca' => 0.8,
            'plataformas' => [9],
            'trilha' => 'coreia-cinema',
            'popularidade' => 10,
        ];
    }

    $ranking = $servico->ranquear($obras, [[
        'id' => 9,
        'slug' => 'mubi',
        'nome' => 'MUBI',
        'logo_path' => null,
        'preco_mensal' => 27.9,
        'ativa' => 1,
        'prioridade' => 1,
    ]]);

    igual(0, count($ranking['geral']));
});

teste('volume bruto não vence sozinho a relevância', function () use ($recConfig): void {
    $config = $recConfig;
    $config['limiares']['min_titulos_plataforma'] = 5;
    $servico = new PlataformaRankingService($config);

    $obras = [];

    // Plataforma 1: 40 títulos mornos.
    for ($i = 1; $i <= 40; $i++) {
        $obras[] = [
            'id' => $i,
            'afinidade' => 63.0,
            'forca' => 0.26,
            'plataformas' => [1],
            'trilha' => 'eua-reino-unido',
            'popularidade' => 10,
        ];
    }

    // Plataforma 2: 25 títulos muito alinhados e exclusivos.
    for ($i = 100; $i < 125; $i++) {
        $obras[] = [
            'id' => $i,
            'afinidade' => 88.0,
            'forca' => 0.76,
            'plataformas' => [2],
            'trilha' => 'coreia-cinema',
            'popularidade' => 10,
        ];
    }

    $ranking = $servico->ranquear($obras, [
        [
            'id' => 1, 'slug' => 'volume', 'nome' => 'Volume', 'logo_path' => null,
            'preco_mensal' => 20.0, 'ativa' => 1, 'prioridade' => 1,
        ],
        [
            'id' => 2, 'slug' => 'foco', 'nome' => 'Foco', 'logo_path' => null,
            'preco_mensal' => 20.0, 'ativa' => 1, 'prioridade' => 2,
        ],
    ]);

    igual(2, count($ranking['geral']));
    igual('foco', $ranking['geral'][0]['slug']);
});

teste('card de trilha só existe com o mínimo de títulos disponíveis', function () use ($recConfig): void {
    $config = $recConfig;
    $config['limiares']['min_titulos_trilha'] = 3;
    $config['limiares']['max_titulos_trilha'] = 3;
    $servico = new TrilhaService($config);

    $obras = [
        ['id' => 1, 'afinidade' => 80.0, 'forca' => 0.6, 'trilha' => 'coreia-doramas', 'popularidade' => 30],
        ['id' => 2, 'afinidade' => 75.0, 'forca' => 0.5, 'trilha' => 'coreia-doramas', 'popularidade' => 20],
        ['id' => 3, 'afinidade' => 70.0, 'forca' => 0.4, 'trilha' => 'coreia-doramas', 'popularidade' => 10],
    ];
    $onde = [1 => [['nome' => 'Netflix']], 2 => [['nome' => 'Netflix']], 3 => [['nome' => 'Netflix']]];

    $cards = $servico->montar($obras, $onde);

    igual(1, count($cards));
    igual('coreia-doramas', $cards[0]['slug']);
    igual(3, count($cards[0]['titulos']));
    igual('Netflix', $cards[0]['titulos'][0]['onde'][0]['nome']);
});

teste('trilha sem disponibilidade não vira card', function () use ($recConfig): void {
    $config = $recConfig;
    $servico = new TrilhaService($config);

    $obras = [
        ['id' => 1, 'afinidade' => 90.0, 'forca' => 0.8, 'trilha' => 'india', 'popularidade' => 30],
        ['id' => 2, 'afinidade' => 85.0, 'forca' => 0.7, 'trilha' => 'india', 'popularidade' => 20],
        ['id' => 3, 'afinidade' => 80.0, 'forca' => 0.6, 'trilha' => 'india', 'popularidade' => 10],
    ];

    $cards = $servico->montar($obras, [1 => [['nome' => 'X']]]);

    igual(0, count($cards));
});

teste('elenco pondera força do título e posição no crédito', function (): void {
    $servico = new \Services\ElencoService();

    $obras = [
        ['id' => 1, 'titulo' => 'Obra A', 'ano' => 2000, 'forca' => 0.9, 'afinidade' => 90.0],
        ['id' => 2, 'titulo' => 'Obra B', 'ano' => 2001, 'forca' => 0.8, 'afinidade' => 85.0],
        ['id' => 3, 'titulo' => 'Obra C', 'ano' => 2002, 'forca' => 0.2, 'afinidade' => 60.0],
    ];

    $creditos = [
        1 => [
            ['pessoa_id' => 10, 'funcao' => 'elenco', 'personagem' => 'Protagonista', 'ordem' => 0],
            ['pessoa_id' => 11, 'funcao' => 'elenco', 'personagem' => 'Coadjuvante', 'ordem' => 5],
        ],
        2 => [
            ['pessoa_id' => 10, 'funcao' => 'elenco', 'personagem' => 'Outro papel', 'ordem' => 1],
            ['pessoa_id' => 12, 'funcao' => 'direcao', 'personagem' => null, 'ordem' => 0],
        ],
        3 => [
            ['pessoa_id' => 13, 'funcao' => 'elenco', 'personagem' => 'Papel pequeno', 'ordem' => 0],
        ],
    ];

    $pessoas = [
        10 => ['id' => 10, 'tmdb_id' => 100, 'nome' => 'Atriz Frequente', 'foto_path' => null],
        11 => ['id' => 11, 'tmdb_id' => 101, 'nome' => 'Ator Coadjuvante', 'foto_path' => null],
        12 => ['id' => 12, 'tmdb_id' => 102, 'nome' => 'Diretor', 'foto_path' => null],
        13 => ['id' => 13, 'tmdb_id' => 103, 'nome' => 'Ator de Uma Obra', 'foto_path' => null],
    ];

    $lista = $servico->relevantes($obras, $creditos, $pessoas, 4);

    afirmar($lista !== [], 'esperava pelo menos um ator');
    igual('Atriz Frequente', $lista[0]['nome']);
    igual(2, $lista[0]['titulos']);
    afirmar(in_array('Diretor', array_column($lista, 'nome'), true) === false, 'direção não entra no elenco');
});

teste('elenco ignora obra sem força', function (): void {
    $servico = new \Services\ElencoService();
    $obras = [['id' => 1, 'titulo' => 'X', 'forca' => 0.0, 'afinidade' => 50.0]];

    igual([], $servico->relevantes($obras, [1 => [
        ['pessoa_id' => 1, 'funcao' => 'elenco', 'ordem' => 0],
    ]], [1 => ['nome' => 'Ninguém']]));
});

teste('elenco reserva vagas para nomes reconhecíveis', function (): void {
    $servico = new \Services\ElencoService();

    $obras = [];
    $creditos = [];
    $pessoas = [];

    // Quatro atores medianos com score alto; dois famosos com score menor.
    for ($i = 1; $i <= 4; $i++) {
        $obras[] = ['id' => $i, 'titulo' => 'Obra ' . $i, 'forca' => 1.0, 'afinidade' => 90.0, 'idioma_original' => 'pt'];
        $creditos[$i] = [['pessoa_id' => $i, 'funcao' => 'elenco', 'ordem' => 0]];
        $pessoas[$i] = ['id' => $i, 'tmdb_id' => $i, 'nome' => 'Mediano ' . $i, 'foto_path' => null, 'popularidade' => 2.0];
    }

    foreach ([10, 11] as $indice => $id) {
        $obraId = 5 + $indice;
        $obras[] = ['id' => $obraId, 'titulo' => 'Obra ' . $obraId, 'forca' => 0.55, 'afinidade' => 88.0, 'idioma_original' => 'en'];
        $creditos[$obraId] = [['pessoa_id' => $id, 'funcao' => 'elenco', 'ordem' => 0]];
        $pessoas[$id] = ['id' => $id, 'tmdb_id' => $id, 'nome' => 'Famoso ' . $indice, 'foto_path' => null, 'popularidade' => 60.0];
    }

    $lista = $servico->relevantes($obras, $creditos, $pessoas, 4);
    $famosos = array_filter($lista, static fn (array $item): bool => (float) ($item['popularidade'] ?? 0) >= 8.0);

    igual(4, count($lista));
    afirmar(count($famosos) >= 2, 'esperava pelo menos duas vagas para nomes reconhecíveis');
});

teste('elenco não deixa um idioma ocupar a lista toda', function (): void {
    $servico = new \Services\ElencoService();

    $obras = [];
    $creditos = [];
    $pessoas = [];

    for ($i = 1; $i <= 6; $i++) {
        $obras[] = ['id' => $i, 'titulo' => 'Anime ' . $i, 'forca' => 1.0, 'afinidade' => 95.0, 'idioma_original' => 'ja'];
        $creditos[$i] = [['pessoa_id' => $i, 'funcao' => 'elenco', 'ordem' => 0]];
        $pessoas[$i] = ['id' => $i, 'tmdb_id' => $i, 'nome' => 'Voz ' . $i, 'foto_path' => null, 'popularidade' => 3.0];
    }

    for ($i = 7; $i <= 11; $i++) {
        $obras[] = ['id' => $i, 'titulo' => 'Filme ' . $i, 'forca' => 0.5, 'afinidade' => 88.0, 'idioma_original' => 'en'];
        $creditos[$i] = [['pessoa_id' => $i, 'funcao' => 'elenco', 'ordem' => 0]];
        $pessoas[$i] = ['id' => $i, 'tmdb_id' => $i, 'nome' => 'Ator ' . $i, 'foto_path' => null, 'popularidade' => 4.0];
    }

    $lista = $servico->relevantes($obras, $creditos, $pessoas, 5);
    $japoneses = array_filter($lista, static fn (array $item): bool => ($item['idiomas'][0] ?? '') === 'ja');

    igual(5, count($lista));
    afirmar(count($japoneses) <= 3, 'o mesmo idioma deveria ficar limitado a três nomes');
});

teste('alinhamento separa o lado do mapa melhor que a afinidade', function () use ($rec): void {
    $contemplativo = [50.0, 30.0, 50.0, 20.0, 50.0, 50.0];
    $obraContemplativa = [50.0, 35.0, 50.0, 25.0, 50.0, 50.0];
    $obraFrenetica = [50.0, 70.0, 50.0, 85.0, 50.0, 50.0];

    afirmar($rec->alinhamento($contemplativo, $obraContemplativa) > 0.9, 'mesmo lado deveria ter alinhamento alto');
    afirmar($rec->alinhamento($contemplativo, $obraFrenetica) < 0.0, 'lado oposto deveria ter alinhamento negativo');
});

teste('trilha ordena por alinhamento e descarta o lado oposto', function () use ($recConfig): void {
    $config = $recConfig;
    $config['limiares']['min_titulos_trilha'] = 1;
    $config['limiares']['max_cards'] = 5;
    $config['limiares']['afinidade'] = 50.0;

    $servico = new TrilhaService($config);
    $obras = [
        ['id' => 1, 'afinidade' => 80.0, 'forca' => 0.6, 'alinhamento' => 0.8, 'trilha' => 'coreia-doramas', 'popularidade' => 10],
        ['id' => 2, 'afinidade' => 80.0, 'forca' => 0.6, 'alinhamento' => 0.6, 'trilha' => 'india', 'popularidade' => 10],
        ['id' => 3, 'afinidade' => 80.0, 'forca' => 0.6, 'alinhamento' => -0.5, 'trilha' => 'anime', 'popularidade' => 10],
    ];
    $onde = [1 => [['nome' => 'X']], 2 => [['nome' => 'Y']], 3 => [['nome' => 'Z']]];

    $cards = $servico->montar($obras, $onde);

    igual(2, count($cards));
    igual('coreia-doramas', $cards[0]['slug']);
    igual('india', $cards[1]['slug']);
});

teste('melhor por trilha premia a plataforma especialista', function () use ($recConfig): void {
    $config = $recConfig;
    $config['limiares']['min_titulos_plataforma'] = 5;
    $servico = new PlataformaRankingService($config);

    $obras = [];

    // Plataforma 1: 10 títulos, todos de dorama (100% do acervo).
    for ($i = 1; $i <= 10; $i++) {
        $obras[] = [
            'id' => $i, 'afinidade' => 80.0, 'forca' => 0.6,
            'plataformas_ids' => [1], 'trilha' => 'coreia-doramas', 'popularidade' => 10,
        ];
    }

    // Plataforma 2: 100 títulos, só 10 de dorama (10% do acervo).
    for ($i = 101; $i <= 200; $i++) {
        $obras[] = [
            'id' => $i, 'afinidade' => 80.0, 'forca' => 0.6,
            'plataformas_ids' => [2],
            'trilha' => $i <= 110 ? 'coreia-doramas' : 'eua-reino-unido',
            'popularidade' => 10,
        ];
    }

    $ranking = $servico->ranquear($obras, [
        ['id' => 1, 'slug' => 'especialista', 'nome' => 'Especialista', 'logo_path' => null, 'preco_mensal' => 20.0, 'ativa' => 1, 'prioridade' => 1],
        ['id' => 2, 'slug' => 'geral', 'nome' => 'Geral', 'logo_path' => null, 'preco_mensal' => 20.0, 'ativa' => 1, 'prioridade' => 2],
    ]);

    afirmar(isset($ranking['por_trilha']['coreia-doramas']), 'esperava um vencedor para doramas');
    igual('especialista', $ranking['por_trilha']['coreia-doramas']['plataforma_slug']);
    igual(100, (int) $ranking['por_trilha']['coreia-doramas']['especializacao']);
});
