<?php

declare(strict_types=1);

use Services\SceneService;

require_once __DIR__ . '/_assert.php';

$configMidia = require dirname(__DIR__) . '/config/midia.php';

$payload = static fn (array $itens): array => ['results' => $itens];

teste('normaliza apenas vídeos do YouTube com chave', function () use ($payload): void {
    $candidatos = SceneService::candidatos($payload([
        ['site' => 'YouTube', 'key' => 'abc123def45', 'name' => 'A', 'type' => 'Clip'],
        ['site' => 'Vimeo', 'key' => 'xyz', 'name' => 'B', 'type' => 'Clip'],
        ['site' => 'YouTube', 'key' => '', 'name' => 'C', 'type' => 'Clip'],
    ]));

    igual(1, count($candidatos));
    igual('abc123def45', $candidatos[0]['key']);
});

teste('descarta trailer, featurette e preview', function () use ($payload, $configMidia): void {
    $candidatos = SceneService::candidatos($payload([
        ['site' => 'YouTube', 'key' => 'a', 'name' => 'Official Trailer', 'type' => 'Trailer', 'official' => true],
        ['site' => 'YouTube', 'key' => 'b', 'name' => 'Behind the Scenes', 'type' => 'Featurette', 'official' => true],
        ['site' => 'YouTube', 'key' => 'c', 'name' => 'Full Movie Preview', 'type' => 'Clip', 'official' => true],
        ['site' => 'YouTube', 'key' => 'd', 'name' => 'The Final Scene', 'type' => 'Clip', 'official' => true],
    ]));

    $perfil = [
        'titulo' => 'A Origem', 'ano' => 2010, 'legenda' => 'A Origem (2010)',
        'tmdb_id' => 27205,
        'palavras_chave' => $configMidia['palavras_chave']['pergunta.estrutura.04'],
    ];

    $avaliados = SceneService::avaliarCandidatos($candidatos, $perfil, $configMidia);

    igual(1, count($avaliados));
    igual('d', $avaliados[0]['key']);
});

teste('prefere a cena nomeada do eixo à cena genérica', function () use ($payload, $configMidia): void {
    $candidatos = SceneService::candidatos($payload([
        ['site' => 'YouTube', 'key' => 'bank', 'name' => 'Bank Robbery Scene', 'type' => 'Clip', 'official' => true, 'iso_639_1' => 'en'],
        ['site' => 'YouTube', 'key' => 'face', 'name' => 'Face To Face', 'type' => 'Clip', 'official' => true, 'iso_639_1' => 'en'],
    ]));

    $perfil = [
        'titulo' => 'Fogo Contra Fogo', 'ano' => 1995, 'legenda' => 'Fogo Contra Fogo (1995)',
        'tmdb_id' => 949,
        'palavras_chave' => $configMidia['palavras_chave']['pergunta.ritmo.04'],
    ];

    $escolha = SceneService::escolher($candidatos, $perfil, $configMidia);

    afirmar($escolha !== null, 'deveria escolher um clipe');
    igual('face', $escolha['key']);
});

teste('A Origem escolhe a cena final, não o sonho', function () use ($payload, $configMidia): void {
    $candidatos = SceneService::candidatos($payload([
        ['site' => 'YouTube', 'key' => 'dream', 'name' => 'The Dream Sequence', 'type' => 'Clip', 'official' => true, 'iso_639_1' => 'en'],
        ['site' => 'YouTube', 'key' => 'final', 'name' => 'The Final Scene', 'type' => 'Clip', 'official' => true, 'iso_639_1' => 'en'],
    ]));

    $perfil = [
        'titulo' => 'A Origem', 'ano' => 2010, 'legenda' => 'A Origem (2010)',
        'tmdb_id' => 27205,
        'palavras_chave' => $configMidia['palavras_chave']['pergunta.estrutura.04'],
    ];

    $escolha = SceneService::escolher($candidatos, $perfil, $configMidia);

    afirmar($escolha !== null, 'deveria escolher um clipe');
    igual('final', $escolha['key']);
});

teste('sem clipe acima do limiar mantém a imagem', function () use ($payload, $configMidia): void {
    $candidatos = SceneService::candidatos($payload([
        ['site' => 'YouTube', 'key' => 'generic', 'name' => 'Clip', 'type' => 'Clip', 'official' => true],
    ]));

    $perfil = [
        'titulo' => 'Filme', 'ano' => 2000, 'legenda' => 'Filme (2000)', 'tmdb_id' => 1,
        'palavras_chave' => ['cena específica'],
    ];

    igual(null, SceneService::escolher($candidatos, $perfil, $configMidia));
});

teste('perfil lê filme, ano, tmdb_id e palavras-chave', function () use ($configMidia): void {
    $perfil = SceneService::perfil([
        'texto_key' => 'pergunta.ritmo.04',
        'tmdb_id' => 949,
        'midia_meta' => '{"legenda":"Fogo Contra Fogo (1995)","ano":1995,"tmdb_id":949}',
    ], $configMidia);

    igual('Fogo Contra Fogo', $perfil['titulo']);
    igual(1995, $perfil['ano']);
    igual(949, $perfil['tmdb_id']);
    afirmar(in_array('face to face', $perfil['palavras_chave'], true));
});

teste('preserva o backdrop original como poster do vídeo', function () use ($configMidia): void {
    $perfil = SceneService::perfil([
        'texto_key' => 'pergunta.ritmo.04',
        'tmdb_id' => 949,
        'midia_tipo' => 'imagem',
        'midia_url_ou_id' => '/poster-original.jpg',
        'midia_meta' => '{"legenda":"Fogo Contra Fogo (1995)","ano":1995}',
    ], $configMidia);

    igual('/poster-original.jpg', $perfil['poster']);

    $meta = SceneService::meta([
        'key' => 'abc', 'nome' => 'Face To Face', 'tipo' => 'Clip',
        'idioma' => 'en', 'score' => 14.0,
    ], $perfil, $configMidia);

    igual('/poster-original.jpg', $meta['poster']);
});

teste('meta do clipe grava recorte e crédito do YouTube', function () use ($configMidia): void {
    $meta = SceneService::meta([
        'key' => 'abc', 'nome' => 'Bank Robbery Scene', 'tipo' => 'Clip',
        'idioma' => 'en', 'score' => 14.0, 'duracao' => null,
    ], [
        'titulo' => 'Fogo Contra Fogo', 'ano' => 1995, 'legenda' => 'Fogo Contra Fogo (1995)',
        'tmdb_id' => 949,
    ], $configMidia);

    igual(0, $meta['start']);
    igual(12, $meta['end']);
    igual('YouTube', $meta['credito']);
    igual('Bank Robbery Scene', $meta['video_nome']);
    igual(949, $meta['tmdb_id']);
});

teste('trecho usa o padrão e evita intros longas', function (): void {
    igual(['start' => 0, 'end' => 12], SceneService::trecho());
    igual(['start' => 0, 'end' => 10], SceneService::trecho(10));
    igual(['start' => 9, 'end' => 21], SceneService::trecho(90));
    igual(['start' => 0, 'end' => 12], SceneService::trecho(20));
});
