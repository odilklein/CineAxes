<?php

declare(strict_types=1);

/**
 * Recomendação por perfil — regras editoriais do catálogo.
 *
 * O motor determinístico continua sendo o vetor de 8 eixos. Este arquivo
 * traduz atributos objetivos de uma obra (gênero, país, idioma, década) em um
 * vetor de gosto, e define quais plataformas e trilhas entram no resultado.
 *
 * Regras de escrita:
 * - `eixos` usa a MESMA ordem de `eixos.ordem` no banco:
 *   1 Imagem, 2 Emoção, 3 Mundo, 4 Ritmo, 5 Ética, 6 História, 7 Tamanho, 8 Época.
 * - Os desvios são intenção editorial, não medida: somam-se e depois são
 *   encolhidos pela confiança em RecomendacaoService.
 * - Um delta positivo empurra para o polo direito (Experimental, Eufórica,
 *   Fantástico, Frenético, Clara, Fragmentada, Épica, Futuro).
 * - Editar aqui muda o catálogo no próximo `bin/catalogo_sync.php --recalcular`.
 */
return [
    'versao' => 'rec-v1',

    // Região usada na disponibilidade (TMDB /watch/providers).
    'regiao' => 'BR',

    // Peso de cada fonte de sinal ao montar o vetor da obra.
    'pesos_fonte' => [
        'genero'  => 1.0,
        'pais'    => 0.75,
        'idioma'  => 0.5,
        'decada'  => 0.35,
    ],

    // Confiança = sinais distintos / este número (limitado a 100).
    'sinais_confianca' => 6,

    // Abaixo disso a obra não entra no ranking de plataformas.
    'confianca_minima' => 25,

    'limiares' => [
        'afinidade'          => 62.0, // obra "combina com você"
        'afinidade_forte'    => 75.0, // obra que puxa o topo
        'min_titulos_plataforma' => 25,
        'top_relevancia'     => 25,   // melhores títulos contados por plataforma
        'min_titulos_trilha' => 3,
        'max_titulos_trilha' => 6,
        'max_cards'          => 6,
        // Alinhamento mínimo (cosseno dos vetores centrados) para um card
        // existir. Evita recomendar uma trilha que contraria o perfil.
        'alinhamento_minimo' => -0.05,
    ],

    // Nota final de cada plataforma (soma 1.0).
    'pesos_plataforma' => [
        'relevancia'    => 0.40,
        'cobertura'     => 0.25,
        'exclusividade' => 0.15,
        'diversidade'   => 0.10,
        'preco'         => 0.10,
    ],

    /* ---------------------------------------------------------------------
     * Gêneros da TMDB (filmes e séries compartilham os IDs).
     * ------------------------------------------------------------------- */
    'generos' => [
        28   => ['slug' => 'acao', 'nome' => 'Ação', 'eixos' => [4 => 32, 7 => 18, 3 => 8, 2 => 8, 5 => 6]],
        12   => ['slug' => 'aventura', 'nome' => 'Aventura', 'eixos' => [4 => 18, 3 => 18, 7 => 22, 2 => 12, 8 => 8]],
        16   => ['slug' => 'animacao', 'nome' => 'Animação', 'eixos' => [3 => 26, 1 => 14, 2 => 10, 7 => 6]],
        35   => ['slug' => 'comedia', 'nome' => 'Comédia', 'eixos' => [2 => 30, 4 => 10, 5 => 8, 7 => -10, 1 => -6]],
        80   => ['slug' => 'crime', 'nome' => 'Crime', 'eixos' => [5 => -28, 1 => 4, 4 => 6, 3 => -8, 7 => -8]],
        99   => ['slug' => 'documentario', 'nome' => 'Documentário', 'eixos' => [3 => -32, 4 => -22, 1 => -10, 7 => -14, 5 => 4]],
        18   => ['slug' => 'drama', 'nome' => 'Drama', 'eixos' => [2 => -20, 4 => -18, 7 => -10, 5 => -6, 1 => -4]],
        10751 => ['slug' => 'familia', 'nome' => 'Família', 'eixos' => [2 => 22, 5 => 22, 7 => 8, 3 => 6]],
        14   => ['slug' => 'fantasia', 'nome' => 'Fantasia', 'eixos' => [3 => 36, 7 => 22, 8 => 10, 1 => 8, 2 => 6]],
        36   => ['slug' => 'historia', 'nome' => 'História', 'eixos' => [8 => -32, 7 => 16, 4 => -12, 1 => -8]],
        27   => ['slug' => 'terror', 'nome' => 'Terror', 'eixos' => [2 => -28, 7 => -16, 5 => -14, 3 => 10, 4 => 10, 1 => 10]],
        10402 => ['slug' => 'musica', 'nome' => 'Música', 'eixos' => [2 => 26, 4 => 12, 7 => 10, 1 => 8]],
        9648 => ['slug' => 'misterio', 'nome' => 'Mistério', 'eixos' => [6 => 24, 5 => -12, 1 => 10, 4 => 6]],
        10749 => ['slug' => 'romance', 'nome' => 'Romance', 'eixos' => [2 => 20, 4 => -14, 7 => -14, 5 => 6, 8 => -6]],
        878  => ['slug' => 'ficcao-cientifica', 'nome' => 'Ficção científica', 'eixos' => [3 => 34, 8 => 34, 1 => 14, 7 => 18, 6 => 8]],
        10770 => ['slug' => 'filme-tv', 'nome' => 'Filme de TV', 'eixos' => []],
        53   => ['slug' => 'suspense', 'nome' => 'Suspense', 'eixos' => [4 => 24, 5 => -16, 6 => 12, 7 => 8, 2 => -10]],
        10752 => ['slug' => 'guerra', 'nome' => 'Guerra', 'eixos' => [2 => -24, 7 => 26, 5 => 10, 4 => 6, 8 => -12]],
        37   => ['slug' => 'faroeste', 'nome' => 'Faroeste', 'eixos' => [8 => -30, 7 => 20, 4 => -10, 1 => -10, 5 => 4]],

        // Gêneros exclusivos de séries
        10759 => ['slug' => 'acao-aventura-tv', 'nome' => 'Ação e aventura', 'eixos' => [4 => 26, 3 => 14, 7 => 18, 2 => 10]],
        10762 => ['slug' => 'infantil', 'nome' => 'Infantil', 'eixos' => [2 => 20, 5 => 18, 3 => 8]],
        10763 => ['slug' => 'noticias', 'nome' => 'Notícias', 'eixos' => [3 => -34, 4 => -6]],
        10764 => ['slug' => 'reality', 'nome' => 'Reality', 'eixos' => [3 => -24, 6 => -12, 4 => 12]],
        10765 => ['slug' => 'ficcao-fantasia-tv', 'nome' => 'Ficção e fantasia', 'eixos' => [3 => 34, 8 => 22, 7 => 16, 1 => 8]],
        10766 => ['slug' => 'novela', 'nome' => 'Novela', 'eixos' => [2 => -12, 4 => -10, 6 => -8, 7 => -8]],
        10767 => ['slug' => 'talk-show', 'nome' => 'Talk show', 'eixos' => [3 => -30]],
        10768 => ['slug' => 'guerra-politica', 'nome' => 'Guerra e política', 'eixos' => [5 => -10, 7 => 18, 8 => -14, 4 => 4]],
    ],

    /* ---------------------------------------------------------------------
     * Países de origem. Prior suave: gênero continua sendo o sinal principal.
     * ------------------------------------------------------------------- */
    'paises' => [
        'US' => ['nome' => 'Estados Unidos', 'eixos' => [7 => 8, 4 => 8]],
        'GB' => ['nome' => 'Reino Unido', 'eixos' => [1 => -6, 2 => -6]],
        'KR' => ['nome' => 'Coreia do Sul', 'eixos' => [2 => -14, 4 => -6, 7 => -10, 5 => -6, 6 => -4]],
        'JP' => ['nome' => 'Japão', 'eixos' => [1 => 10, 4 => -12, 3 => 10, 7 => -10, 8 => -4]],
        'IN' => ['nome' => 'Índia', 'eixos' => [2 => 24, 4 => 10, 7 => 14, 5 => 12, 6 => -10]],
        'CN' => ['nome' => 'China', 'eixos' => [1 => 14, 4 => 6, 7 => 12, 3 => 6]],
        'HK' => ['nome' => 'Hong Kong', 'eixos' => [1 => 12, 4 => 12, 7 => 8]],
        'TW' => ['nome' => 'Taiwan', 'eixos' => [1 => 10, 2 => -6, 4 => -6]],
        'FR' => ['nome' => 'França', 'eixos' => [1 => 8, 4 => -16, 2 => -10, 7 => -6]],
        'IT' => ['nome' => 'Itália', 'eixos' => [1 => 10, 2 => 6, 4 => -8]],
        'ES' => ['nome' => 'Espanha', 'eixos' => [2 => 10, 4 => -4, 5 => -6]],
        'PT' => ['nome' => 'Portugal', 'eixos' => [4 => -14, 2 => -8, 7 => -8]],
        'BR' => ['nome' => 'Brasil', 'eixos' => [3 => -14, 4 => 4, 7 => -6, 5 => -4]],
        'MX' => ['nome' => 'México', 'eixos' => [2 => 10, 3 => -8]],
        'AR' => ['nome' => 'Argentina', 'eixos' => [2 => 8, 3 => -6, 1 => 4]],
        'CL' => ['nome' => 'Chile', 'eixos' => [3 => -8, 4 => -4]],
        'CO' => ['nome' => 'Colômbia', 'eixos' => [2 => 8, 3 => -10]],
        'PE' => ['nome' => 'Peru', 'eixos' => [3 => -10, 4 => -6]],
        'NG' => ['nome' => 'Nigéria', 'eixos' => [2 => 12, 4 => 12, 5 => 6]],
        'TR' => ['nome' => 'Turquia', 'eixos' => [2 => -8, 4 => 6, 7 => 12]],
        'IR' => ['nome' => 'Irã', 'eixos' => [4 => -20, 2 => -16, 1 => 10, 3 => -10]],
        'TH' => ['nome' => 'Tailândia', 'eixos' => [4 => -8, 2 => 4, 3 => -6]],
        'ID' => ['nome' => 'Indonésia', 'eixos' => [2 => 6, 4 => 6]],
        'PH' => ['nome' => 'Filipinas', 'eixos' => [2 => 10, 4 => 4]],
        'VN' => ['nome' => 'Vietnã', 'eixos' => [4 => -6, 2 => 2]],
        'SE' => ['nome' => 'Suécia', 'eixos' => [4 => -16, 2 => -18, 1 => 6, 5 => -8]],
        'DK' => ['nome' => 'Dinamarca', 'eixos' => [4 => -16, 2 => -16, 1 => 6]],
        'NO' => ['nome' => 'Noruega', 'eixos' => [4 => -14, 2 => -14, 1 => 4]],
        'FI' => ['nome' => 'Finlândia', 'eixos' => [4 => -18, 2 => -18]],
        'IS' => ['nome' => 'Islândia', 'eixos' => [4 => -16, 2 => -14, 1 => 6]],
        'DE' => ['nome' => 'Alemanha', 'eixos' => [1 => 6, 2 => -10, 5 => -6]],
    ],

    /* ---------------------------------------------------------------------
     * Idiomas (ISO 639-1). O prior é aplicado com peso menor que o país.
     * ------------------------------------------------------------------- */
    'idiomas' => [
        'ko' => [2 => -10, 4 => -4],
        'ja' => [3 => 6, 4 => -6],
        'hi' => [2 => 18, 4 => 8, 7 => 8],
        'ta' => [2 => 16, 4 => 8, 7 => 8],
        'te' => [2 => 16, 4 => 8, 7 => 8],
        'ml' => [2 => 14, 4 => 4, 7 => 6],
        'fr' => [4 => -8, 2 => -6],
        'es' => [2 => 8],
        'pt' => [4 => -8],
        'tr' => [2 => -6, 4 => 4],
        'fa' => [4 => -16, 2 => -12],
        'th' => [4 => -6],
        'zh' => [1 => 10, 4 => 4],
        'cn' => [1 => 10, 4 => 4],
        'he' => [5 => -6],
    ],

    // Década de lançamento (faixas inclusivas no início, exclusivas no fim).
    'decadas' => [
        ['ate' => 1959, 'eixos' => [1 => -10, 4 => -10, 8 => -14, 5 => 6, 7 => 6]],
        ['ate' => 1979, 'eixos' => [1 => -6, 8 => -10, 4 => -6]],
        ['ate' => 1999, 'eixos' => [8 => -4, 4 => 4, 7 => 4]],
        ['ate' => 2014, 'eixos' => [8 => 2, 1 => 4, 4 => 2]],
        ['ate' => 2100, 'eixos' => [8 => 6, 1 => 4, 4 => 4]],
    ],

    /* ---------------------------------------------------------------------
     * Trilhas de curadoria: alimentam o ETL e viram cards no perfil.
     * `card => false` mantém a trilha no catálogo (cobertura de plataforma)
     * sem exibir um card "EUA e Reino Unido" na seção além-Hollywood.
     * ------------------------------------------------------------------- */
    'trilhas' => [
        'eua-reino-unido' => [
            'nome' => 'EUA e Reino Unido',
            'descricao' => 'Clássicos, indies e blockbusters anglófonos.',
            'icone' => 'film',
            'cor' => '#2f4858',
            'tipo' => 'ambos',
            'paises' => ['US', 'GB'],
            'limite' => 320,
            'card' => false,
        ],
        'coreia-doramas' => [
            'nome' => 'Doramas para você',
            'descricao' => 'Séries coreanas de drama, romance e suspense — o melodrama em formato longo.',
            'icone' => 'heart',
            'cor' => '#b04a5a',
            'tipo' => 'serie',
            'paises' => ['KR'],
            'sem_generos' => [10764, 10767, 10763, 10762],
            'limite' => 240,
        ],
        'coreia-cinema' => [
            'nome' => 'Cinema coreano',
            'descricao' => 'Vingança, classe e tensão contida: o cinema que redefiniu o suspense contemporâneo.',
            'icone' => 'bolt',
            'cor' => '#7a4a63',
            'tipo' => 'filme',
            'paises' => ['KR'],
            'limite' => 180,
        ],
        'japao-anime' => [
            'nome' => 'Anime',
            'descricao' => 'Animação japonesa para todas as idades — do slice of life ao shonen de ação.',
            'icone' => 'sparkles',
            'cor' => '#4f6480',
            'tipo' => 'ambos',
            'paises' => ['JP'],
            'generos' => [16],
            'limite' => 200,
        ],
        'japao-live-action' => [
            'nome' => 'Cinema e séries do Japão',
            'descricao' => 'Live-action do Japão: drama familiar, policial e fantasia urbana.',
            'icone' => 'moon',
            'cor' => '#445c7a',
            'tipo' => 'ambos',
            'paises' => ['JP'],
            'sem_generos' => [16],
            'limite' => 100,
        ],
        'india' => [
            'nome' => 'Filmes e séries da Índia',
            'descricao' => 'Bollywood e os cinemas tamil, telugu e malaiala: música, cor e emoção em escala.',
            'icone' => 'sun',
            'cor' => '#c08a2e',
            'tipo' => 'ambos',
            'paises' => ['IN'],
            'limite' => 240,
        ],
        'china-hong-kong-taiwan' => [
            'nome' => 'China e Hong Kong',
            'descricao' => 'Artes marciais, autoral taiwanês e o cinema de autor de Wong Kar-wai.',
            'icone' => 'mountain',
            'cor' => '#5c7048',
            'tipo' => 'ambos',
            'paises' => ['CN', 'HK', 'TW'],
            'limite' => 140,
        ],
        'brasil' => [
            'nome' => 'Cinema e séries do Brasil',
            'descricao' => 'Do Cinema Novo às séries contemporâneas: o país filmado por quem vive nele.',
            'icone' => 'home',
            'cor' => '#3f6b6b',
            'tipo' => 'ambos',
            'paises' => ['BR'],
            'limite' => 150,
        ],
        'franca' => [
            'nome' => 'Cinema e séries da França',
            'descricao' => 'Nouvelle Vague, drama social e comédia de costumes.',
            'icone' => 'feather',
            'cor' => '#8c5a2b',
            'tipo' => 'ambos',
            'paises' => ['FR'],
            'limite' => 110,
        ],
        'italia-espanha-portugal' => [
            'nome' => 'Sul da Europa',
            'descricao' => 'Itália, Espanha e Portugal: neorrealismo, Almodóvar e o cinema português de autor.',
            'icone' => 'compass',
            'cor' => '#a8792c',
            'tipo' => 'ambos',
            'paises' => ['IT', 'ES', 'PT'],
            'limite' => 80,
        ],
        'america-latina' => [
            'nome' => 'América Latina',
            'descricao' => 'México, Argentina, Chile, Colômbia e Peru — vizinhos que o cinema americano esconde.',
            'icone' => 'globe',
            'cor' => '#b0553c',
            'tipo' => 'ambos',
            'paises' => ['MX', 'AR', 'CL', 'CO', 'PE'],
            'limite' => 110,
        ],
        'africa-asia-ocidental' => [
            'nome' => 'África e Ásia Ocidental',
            'descricao' => 'Nollywood, cinema turco, iraniano e o sudeste asiático que quase nunca chega aos streamings.',
            'icone' => 'users',
            'cor' => '#6b5b7b',
            'tipo' => 'ambos',
            'paises' => ['NG', 'TR', 'IR', 'TH', 'ID', 'PH', 'VN'],
            'limite' => 130,
        ],
    ],

    /* ---------------------------------------------------------------------
     * Cobertura dirigida: plataformas de nicho têm acervo pequeno e podem não
     * aparecer num recorte só por popularidade. O ETL busca um extra por
     * plataforma (com `with_watch_providers`) para garantir inventário.
     * Chave = tmdb_provider_id; valor = quantos títulos buscar.
     * ------------------------------------------------------------------- */
    'cobertura_plataformas' => [
        11   => 120, // MUBI
        283  => 100, // Crunchyroll
        47   => 150, // Looke
        307  => 120, // Globoplay
        531  => 100, // Paramount+
        350  => 80,  // Apple TV+
    ],
];
