<?php

declare(strict_types=1);

use Services\MidiaService;

require_once __DIR__ . '/_assert.php';

teste('imagem da TMDB vira URL absoluta com crédito', function (): void {
    $midia = MidiaService::resolver(
        'imagem',
        '/8K9qHeM6G6QjQN0C5XKFGvK5lzM.jpg',
        '{"legenda":"Matrix (1999)","ano":1999,"fonte":"tmdb"}'
    );

    afirmar($midia !== null, 'mídia deveria ser resolvida');
    igual('https://image.tmdb.org/t/p/w780/8K9qHeM6G6QjQN0C5XKFGvK5lzM.jpg', $midia['src']);
    igual('TMDB', $midia['credito']);
    igual(1999, $midia['ano']);
    igual('Matrix (1999)', $midia['legenda']);
});

teste('imagem com URL absoluta é preservada', function (): void {
    $midia = MidiaService::resolver('imagem', 'https://exemplo.test/cena.jpg', null, 'w1280');

    afirmar($midia !== null, 'mídia deveria ser resolvida');
    igual('https://exemplo.test/cena.jpg', $midia['src']);
    igual(null, $midia['credito']);
});

teste('GIF aceita poster opcional da TMDB', function (): void {
    $midia = MidiaService::resolver(
        'gif',
        'https://media.giphy.com/media/abc/giphy.gif',
        ['legenda' => 'Cena', 'poster' => '/poster.jpg']
    );

    afirmar($midia !== null, 'GIF deveria ser resolvido');
    igual('https://media.giphy.com/media/abc/giphy.gif', $midia['src']);
    igual('https://image.tmdb.org/t/p/w780/poster.jpg', $midia['poster']);
});

teste('vídeo do YouTube monta embed mudo com início e fim', function (): void {
    $midia = MidiaService::resolver(
        'video_yt',
        'https://www.youtube.com/watch?v=8K9qHeM6G6Q',
        ['legenda' => 'A Origem (2010)', 'start' => 142, 'end' => 149]
    );

    afirmar($midia !== null, 'vídeo deveria ser resolvido');
    igual('8K9qHeM6G6Q', $midia['video_id']);
    afirmar(str_contains($midia['embed'], 'mute=1'), 'embed deveria iniciar mudo');
    afirmar(str_contains($midia['embed'], 'start=142'), 'embed deveria ter start');
    afirmar(str_contains($midia['embed'], 'end=149'), 'embed deveria ter end');
    igual('https://i.ytimg.com/vi/8K9qHeM6G6Q/hqdefault.jpg', $midia['poster']);
});

teste('ID curto e URLs youtu.be/shorts são reconhecidos', function (): void {
    igual('dQw4w9WgXcQ', MidiaService::youtubeId('dQw4w9WgXcQ'));
    igual('dQw4w9WgXcQ', MidiaService::youtubeId('https://youtu.be/dQw4w9WgXcQ?t=10'));
    igual('dQw4w9WgXcQ', MidiaService::youtubeId('https://www.youtube.com/shorts/dQw4w9WgXcQ'));
    igual(null, MidiaService::youtubeId('não é um vídeo'));
});

teste('tipo desconhecido ou valor vazio não renderiza mídia', function (): void {
    igual(null, MidiaService::resolver('audio', '/x.mp3'));
    igual(null, MidiaService::resolver('imagem', '   '));
    igual(null, MidiaService::resolver(null, '/x.jpg'));
});

teste('meta do formulário preserva legenda, ano e fonte', function (): void {
    $meta = MidiaService::metaParaSalvar(
        ['legenda' => 'Pulp Fiction (1994)', 'ano' => 1994, 'start' => 0, 'end' => 0],
        '/suaEOtk1N1sgg2MTM7oZd2cfVp3.jpg'
    );

    igual('Pulp Fiction (1994)', $meta['legenda']);
    igual(1994, $meta['ano']);
    igual('tmdb', $meta['fonte']);
    afirmar(!isset($meta['start']), 'start zero não deveria ser salvo');
});
