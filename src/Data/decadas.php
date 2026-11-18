<?php

declare(strict_types=1);

/**
 * Retrato editorial de cada década.
 *
 * Alimenta o insight "A década do seu repertório": um epíteto, uma descrição
 * curta, uma curiosidade concreta e três filmes-marco da época. Texto curado,
 * estático: não depende do banco nem da TMDB.
 */

return [
    1900 => [
        'epiteto' => 'O cinema aprende a contar',
        'descricao' => 'O espetáculo de feira vira narrativa: truque, perseguição e o primeiro suspense.',
        'curiosidade' => 'Os filmes duravam de 8 a 12 minutos e eram vendidos por metro de película. "O Grande Roubo do Trem" (1903) popularizou a montagem em paralelo, mostrando dois lugares ao mesmo tempo.',
        'marcos' => [
            ['titulo' => 'Viagem à Lua', 'ano' => 1902],
            ['titulo' => 'O Grande Roubo do Trem', 'ano' => 1903],
            ['titulo' => 'O Gabinete do Dr. Caligari', 'ano' => 1920],
        ],
    ],
    1910 => [
        'epiteto' => 'Hollywood nasce',
        'descricao' => 'O longa-metragem se firma, nasce o star system e o cinema vira indústria.',
        'curiosidade' => 'Chaplin assinou seu primeiro contrato milionário em 1916 sem dizer uma palavra em cena. No mesmo ano, "Intolerância" cruzava quatro épocas diferentes — algo que o público só voltaria a aceitar décadas depois.',
        'marcos' => [
            ['titulo' => 'O Nascimento de uma Nação', 'ano' => 1915],
            ['titulo' => 'Intolerância', 'ano' => 1916],
            ['titulo' => 'O Garoto', 'ano' => 1921],
        ],
    ],
    1920 => [
        'epiteto' => 'A vanguarda explode',
        'descricao' => 'Expressionismo, montagem soviética e surrealismo transformam a imagem em argumento.',
        'curiosidade' => 'Ainda sem som gravado, o cinema confiava em música ao vivo e em intertítulos desenhados à mão. "Um Cão Andaluz" (1929) foi feito para provocar: Buñuel e Dalí escreveram o roteiro a partir de sonhos.',
        'marcos' => [
            ['titulo' => 'Nosferatu', 'ano' => 1922],
            ['titulo' => 'Encouraçado Potemkin', 'ano' => 1925],
            ['titulo' => 'Metrópolis', 'ano' => 1927],
        ],
    ],
    1930 => [
        'epiteto' => 'O som muda tudo',
        'descricao' => 'Diálogo, música e o musical: a indústria aprende a ouvir o público.',
        'curiosidade' => 'Nos primeiros anos do som, as câmeras ficavam presas em cabines à prova de ruído — o que engessou o enquadramento. O Technicolor em três tiras chegou em 1932 e custava o dobro de um filme em preto e branco.',
        'marcos' => [
            ['titulo' => 'King Kong', 'ano' => 1933],
            ['titulo' => 'Aconteceu Naquela Noite', 'ano' => 1934],
            ['titulo' => 'Tempos Modernos', 'ano' => 1936],
        ],
    ],
    1940 => [
        'epiteto' => 'Noir e neorrealismo',
        'descricao' => 'Sombras urbanas de um lado, ruas destruídas do outro: o cinema descobre a dúvida.',
        'curiosidade' => 'O termo "film noir" só foi cunhado por críticos franceses em 1946, depois que os filmes americanos chegaram a Paris. Já o neorrealismo italiano nasceu com película escassa e cenários reais: era mais necessidade que escola.',
        'marcos' => [
            ['titulo' => 'O Falcão Maltês', 'ano' => 1941],
            ['titulo' => 'Casablanca', 'ano' => 1942],
            ['titulo' => 'Roma, Cidade Aberta', 'ano' => 1945],
        ],
    ],
    1950 => [
        'epiteto' => 'A tela se alarga',
        'descricao' => 'Widescreen, cor e o crítico de cinema virando diretor.',
        'curiosidade' => 'A TV virou concorrência e o cinema respondeu com telas gigantes: CinemaScope, VistaVision e Cinerama. Na França, os jovens críticos dos Cahiers du Cinéma passaram da escrita para a câmera e inventaram a "política dos autores".',
        'marcos' => [
            ['titulo' => 'Cantando na Chuva', 'ano' => 1952],
            ['titulo' => 'Os Sete Samurais', 'ano' => 1954],
            ['titulo' => 'Janela Indiscreta', 'ano' => 1954],
        ],
    ],
    1960 => [
        'epiteto' => 'A modernidade',
        'descricao' => 'Nouvelle vague, cinema novo e a câmera que assume estar filmando.',
        'curiosidade' => 'A nouvelle vague nasceu com câmeras leves e película sensível, gravando na rua sem permissão. No Brasil, Glauber Rocha levou essa urgência ao sertão e transformou a precariedade em estilo.',
        'marcos' => [
            ['titulo' => 'Acossado', 'ano' => 1960],
            ['titulo' => 'Deus e o Diabo na Terra do Sol', 'ano' => 1964],
            ['titulo' => '2001: Uma Odisseia no Espaço', 'ano' => 1968],
        ],
    ],
    1970 => [
        'epiteto' => 'O New Hollywood',
        'descricao' => 'Estúdios entregues aos autores, paranoia, violência urbana e o primeiro blockbuster.',
        'curiosidade' => 'Depois do sucesso de "O Poderoso Chefão", os estúdios apostaram em diretores jovens com liberdade quase total. A festa durou até "Star Wars" (1977) mostrar que a franquia rendia mais que o autor.',
        'marcos' => [
            ['titulo' => 'O Poderoso Chefão', 'ano' => 1972],
            ['titulo' => 'Taxi Driver', 'ano' => 1976],
            ['titulo' => 'Star Wars: Uma Nova Esperança', 'ano' => 1977],
        ],
    ],
    1980 => [
        'epiteto' => 'Blockbuster e videocassete',
        'descricao' => 'O VHS leva o cinema para casa e a trilha sintetizada vira assinatura.',
        'curiosidade' => 'O videocassete transformou o espectador em colecionador: pela primeira vez dava para pausar, rever e alugar. "Blade Runner" fracassou nos cinemas em 1982 e virou clássico justamente no aluguel e na TV.',
        'marcos' => [
            ['titulo' => 'O Iluminado', 'ano' => 1980],
            ['titulo' => 'Blade Runner, o Caçador de Androides', 'ano' => 1982],
            ['titulo' => 'E.T. — O Extraterrestre', 'ano' => 1982],
        ],
    ],
    1990 => [
        'epiteto' => 'O indie e a ironia',
        'descricao' => 'Sundance, Dogma 95 e o digital batendo na porta do cinema independente.',
        'curiosidade' => 'O digital ainda era experimental, mas já mudava o set: "O Projeto Blair Witch" (1999) virou fenômeno com câmera na mão e marketing na internet. No mesmo ano, "Matrix" juntava filosofia pop e efeitos que se tornariam padrão.',
        'marcos' => [
            ['titulo' => 'Pulp Fiction: Tempo de Violência', 'ano' => 1994],
            ['titulo' => 'Clube da Luta', 'ano' => 1999],
            ['titulo' => 'Matrix', 'ano' => 1999],
        ],
    ],
    2000 => [
        'epiteto' => 'A expansão digital',
        'descricao' => 'DVD, épico de fantasia e o cinema de festival no centro do debate.',
        'curiosidade' => 'A câmera digital barateou o set e multiplicou a produção independente. Ao mesmo tempo, a trilogia "O Senhor dos Anéis" provou que uma mitologia longa podia sustentar três anos de lançamentos e um fandom global.',
        'marcos' => [
            ['titulo' => 'Cidade dos Sonhos', 'ano' => 2001],
            ['titulo' => 'O Senhor dos Anéis: A Sociedade do Anel', 'ano' => 2001],
            ['titulo' => 'Cidade de Deus', 'ano' => 2002],
        ],
    ],
    2010 => [
        'epiteto' => 'Streaming e prestígio',
        'descricao' => 'A série vira cinema de autor e o algoritmo começa a recomendar o próximo filme.',
        'curiosidade' => 'Os serviços de streaming mudaram a janela de exibição: estrear direto em casa deixou de ser vergonha. Em 2019, "Parasita" se tornou o primeiro filme de língua não inglesa a ganhar o Oscar de melhor filme.',
        'marcos' => [
            ['titulo' => 'Mad Max: Estrada da Fúria', 'ano' => 2015],
            ['titulo' => 'Moonlight: Sob a Luz do Luar', 'ano' => 2016],
            ['titulo' => 'Parasita', 'ano' => 2019],
        ],
    ],
    2020 => [
        'epiteto' => 'Multiverso e tela vertical',
        'descricao' => 'Franquias em loop, pandemia e o cinema disputando atenção com o vídeo curto.',
        'curiosidade' => 'Durante a pandemia, festivais viraram plataformas e lançamentos foram direto para o streaming. "Tudo em Todo Lugar ao Mesmo Tempo" (2022) virou fenômeno justamente por misturar blockbuster, videoclipe e drama familiar em uma só montagem.',
        'marcos' => [
            ['titulo' => 'Duna', 'ano' => 2021],
            ['titulo' => 'Tudo em Todo Lugar ao Mesmo Tempo', 'ano' => 2022],
            ['titulo' => 'Ainda Estou Aqui', 'ano' => 2024],
        ],
    ],
];
