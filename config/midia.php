<?php

declare(strict_types=1);

/**
 * Configuração da seleção automática de cenas (clipes oficiais da TMDB).
 *
 * As palavras-chave são comparadas com o nome do clipe em inglês, por isso
 * ficam nesse idioma. A chave é o `texto_key` da pergunta, que é estável mesmo
 * se o id numérico mudar.
 */
return [
    'score_minimo'    => 8.0,
    'duracao_padrao'  => 12, // segundos exibidos no loop

    'pesos' => [
        'oficial'        => 5.0,
        'palavra_chave'  => 8.0,
        'max_palavras'   => 24.0,
        'titulo'         => 3.0,
        'idioma_pt'      => 2.0,
        'idioma_en'      => 1.0,
        'generico'       => -5.0,
    ],

    // Nomes que indicam montagem/extra, não uma cena isolada.
    'palavras_ruins' => [
        'full movie',
        'full film',
        'extended preview',
        'minute preview',
        'movie preview',
        'preview',
        'behind the scenes',
        'featurette',
        'trailer',
        'teaser',
        'blooper',
        'interview',
        'reaction',
        'review',
        'making of',
        'video essay',
        'commentary',
    ],

    // Nomes genéricos recebem penalidade para não vencerem cenas nomeadas.
    'palavras_genericas' => [
        'clip',
        'official clip',
        'movie clip',
        'exclusive clip',
        'scene',
    ],

    'palavras_chave' => [
        'pergunta.estetica.01'   => ['airport', 'goodbye', 'letter of transit', 'rick', 'ilsa', 'classic'],
        'pergunta.tom.01'        => ['police', 'station', 'grief', 'ex wife', 'confrontation', 'fire', 'street'],
        'pergunta.universo.01'   => ['destiny', 'paradise', 'gangster', 'turf', 'tender trio', 'man down', 'lesson learned', 'getting out', 'getaway'],
        'pergunta.ritmo.01'      => ['memories', 'opening scene', 'desert', 'mirror', 'peep booth', 'talk', 'travis', 'jane'],
        'pergunta.moralidade.01' => ['don vito', 'michael', 'shot', 'offer', 'family', 'restaurant'],
        'pergunta.estrutura.01'  => ['run', 'bench', 'chocolates', 'jenny', 'vietnam', 'july fourth'],
        'pergunta.atmosfera.01'  => ['jury', 'room', 'ballot', 'knife', 'vote', 'henry fonda'],
        'pergunta.epoca.01'      => ['projection', 'cinema', 'child', 'kiss', 'village', 'square'],
        'pergunta.estetica.02'   => ['joy', 'hologram', 'desert', 'las vegas', 'orange', 'dream'],
        'pergunta.tom.02'        => ['we must go to her', 'concierge', 'lobby', 'hotel', 'zero', 'gustave', 'escape'],
        'pergunta.universo.02'   => ['ring', 'shire', 'mountain', 'moria', 'fellowship', 'battle'],
        'pergunta.ritmo.02'      => ['chase', 'furiosa', 'storm', 'convoy', 'escape', 'war rig'],
        'pergunta.moralidade.02' => ['idol', 'boulder', 'truck', 'chase', 'whip', 'hat'],
        'pergunta.estrutura.02'  => ['bonnie situation', 'needle', 'watch', 'time is a factor', 'time'],
        'pergunta.atmosfera.02'  => ['desert', 'army', 'camel', 'march', 'battle', 'aqaba'],
        'pergunta.epoca.02'      => ['red pill', 'blue pill', 'desert of the real', 'rabbit hole', 'neo', 'morpheus'],
        'pergunta.estetica.03'   => ['ambush', 'car', 'escape', 'baby', 'soldiers', 'refugee'],
        'pergunta.tom.03'        => ['corridor', 'rain', 'stairs', 'neighbor', 'restaurant', 'smoke'],
        'pergunta.universo.03'   => ['beach', 'family', 'house', 'street', 'cleo', 'kids', 'hospital'],
        'pergunta.ritmo.03'      => ['room', 'zone', 'walk', 'desert', 'stalker', 'wish'],
        'pergunta.moralidade.03' => ['coin', 'gas station', 'motel', 'chase', 'briefcase', 'chigurh'],
        'pergunta.estrutura.03'  => ['bike', 'moon', 'phone home', 'garage', 'friends', 'fbi'],
        'pergunta.atmosfera.03'  => ['before sunrise', 'train', 'diner', 'walk', 'talk', 'vienna', 'record store'],
        'pergunta.epoca.03'      => ['duel', 'army', 'ball', 'letter', 'gambling', 'court'],
        'pergunta.estetica.04'   => ['memory', 'train', 'beach', 'goodbye', 'dream', 'montauk', 'erased'],
        'pergunta.tom.04'        => ['what she s having', 'ill have what she s having', 'diner', 'orgasm', 'new year'],
        'pergunta.universo.04'   => ['cantina', 'lightsaber', 'death star', 'trench', 'force', 'vader'],
        'pergunta.ritmo.04'      => ['face to face', 'diner', 'crew'],
        'pergunta.moralidade.04' => ['battle', 'crown', 'ring', 'siege', 'gondor', 'eowyn'],
        'pergunta.estrutura.04'  => ['final scene', 'spinning top', 'limbo', 'subconscious', 'kick'],
        'pergunta.atmosfera.04'  => ['docking', 'wave', 'black hole', 'murph', 'launch', 'tesseract'],
        'pergunta.epoca.04'      => ['what do you love', 'samantha', 'computer', 'city', 'letter', 'phone', 'future', 'neon', 'apartment'],
    ],
];
