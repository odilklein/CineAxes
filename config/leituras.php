<?php

declare(strict_types=1);

/**
 * Leituras do perfil.
 *
 * Cada eixo bipolar tem uma leitura para cada polo. A leitura é a camada
 * editorial que traduz o vetor em consequência: o que a pessoa procura, o que
 * costuma afastá-la e dois filmes para reconhecer isso na prática.
 *
 * Regras de escrita:
 * - manchete: uma frase, no máximo ~70 caracteres, sem percentual e sem jargão.
 * - corpo: duas frases; a segunda diz o que a leitura NÃO significa.
 * - combina: dois filmes com ano, títulos reconhecíveis.
 * - cansa: o que afasta essa pessoa, em uma frase.
 * - curto: rótulo de 1 a 3 palavras para o card e o índice das marcas.
 *
 * Os eixos continuam sendo o motor determinístico. Este arquivo é conteúdo,
 * não cálculo: pode ser editado sem mexer no vetor nem nos resultados.
 */
return [
    'forcas' => [
        ['minimo' => 20.0, 'rotulo' => 'traço forte'],
        ['minimo' => 8.0, 'rotulo' => 'traço claro'],
        ['minimo' => 0.0, 'rotulo' => 'tendência'],
    ],

    'neutro' => [
        'manchete' => 'Você não escolhe lado — e esse é o seu jeito.',
        'corpo' => 'Seu perfil transita entre os polos: um dia pede silêncio, outro pede adrenalina; um filme clássico, outro que quebra tudo. O que te move é a obra específica, não a bandeira.',
        'combina' => [
            ['titulo' => 'Tudo em Todo Lugar ao Mesmo Tempo', 'ano' => 2022],
            ['titulo' => 'Cidade dos Sonhos', 'ano' => 2001],
        ],
        'cansa' => 'fórmula que exige escolher um time antes de o filme começar.',
        'curto' => 'Entre os lados',
    ],

    'eixos' => [
        'estetica' => [
            'e' => [
                'manchete' => 'Você quer que a imagem sirva à história.',
                'corpo' => 'Enquadramento, luz e corte desaparecem a serviço do que está sendo contado. Você confia na transparência — e desconfia quando o estilo pede mais atenção que a cena.',
                'combina' => [
                    ['titulo' => 'Casablanca', 'ano' => 1942],
                    ['titulo' => 'O Poderoso Chefão', 'ano' => 1972],
                ],
                'cansa' => 'filme que esconde a história atrás de um exercício de estilo.',
                'curto' => 'Imagem clássica',
            ],
            'd' => [
                'manchete' => 'Você quer ver o cinema pensando em voz alta.',
                'corpo' => 'Textura, montagem e enquadramento são o argumento, não o enfeite. Quando o filme inventa uma linguagem, você entra no jogo em vez de pedir que ele se explique.',
                'combina' => [
                    ['titulo' => 'Cidade dos Sonhos', 'ano' => 2001],
                    ['titulo' => 'Sinédoque, Nova York', 'ano' => 2008],
                ],
                'cansa' => 'filme bonito que não arrisca um único plano.',
                'curto' => 'Imagem livre',
            ],
        ],

        'tom' => [
            'e' => [
                'manchete' => 'Você quer que o filme fique com você depois do fim.',
                'corpo' => 'O seu cinema termina em silêncio, com uma pergunta aberta. Você não confunde peso com tristeza fácil: o que fica é o que doeu de verdade.',
                'combina' => [
                    ['titulo' => 'Melancolia', 'ano' => 2011],
                    ['titulo' => 'Manchester à Beira-Mar', 'ano' => 2016],
                ],
                'cansa' => 'comédia que pede risada antes de construir qualquer coisa.',
                'curto' => 'Emoção que pesa',
            ],
            'd' => [
                'manchete' => 'Você vai ao cinema para sair mais leve.',
                'corpo' => 'Humor, música e energia compartilhada são a sua forma de estar no mundo. Você quer o filme que contagia a sala, não o que pede silêncio e reverência.',
                'combina' => [
                    ['titulo' => 'La La Land', 'ano' => 2016],
                    ['titulo' => 'Tudo em Todo Lugar ao Mesmo Tempo', 'ano' => 2022],
                ],
                'cansa' => 'filme que pede reverência e silêncio antes de conquistar a sala.',
                'curto' => 'Emoção em festa',
            ],
        ],

        'universo' => [
            'e' => [
                'manchete' => 'Você desconfia de mundos inventados.',
                'corpo' => 'A esquina já tem drama suficiente. Personagens verossímeis e conflitos sem solução mágica te interessam mais do que mitologias — o real, bem olhado, já é invenção.',
                'combina' => [
                    ['titulo' => 'Cidade de Deus', 'ano' => 2002],
                    ['titulo' => 'História de um Casamento', 'ano' => 2019],
                ],
                'cansa' => 'épico que resolve tudo com uma profecia.',
                'curto' => 'Mundo real',
            ],
            'd' => [
                'manchete' => 'Você quer regras novas.',
                'corpo' => 'Mundos inventados não são fuga: são outra forma de olhar o real. Você aceita a mitologia, a criatura e o planeta distante como linguagem, não como desculpa.',
                'combina' => [
                    ['titulo' => 'O Senhor dos Anéis: A Sociedade do Anel', 'ano' => 2001],
                    ['titulo' => 'Akira', 'ano' => 1988],
                ],
                'cansa' => 'filme que usa o fantástico só para enfeitar o cartaz.',
                'curto' => 'Mundos inventados',
            ],
        ],

        'ritmo' => [
            'e' => [
                'manchete' => 'Você confia no tempo longo.',
                'corpo' => 'Um plano parado pode dizer mais que dez cortes. Você prefere reparar no detalhe, no silêncio e no que só aparece quando o filme respira.',
                'combina' => [
                    ['titulo' => 'Paterson', 'ano' => 2016],
                    ['titulo' => 'A Árvore da Vida', 'ano' => 2011],
                ],
                'cansa' => 'edição que não deixa nenhuma cena terminar.',
                'curto' => 'Tempo que respira',
            ],
            'd' => [
                'manchete' => 'Você quer o corpo dentro do filme.',
                'corpo' => 'Corte, som e movimento te mantêm na cadeira. A lentidão só funciona quando tem intenção — não quando é só falta de ideia.',
                'combina' => [
                    ['titulo' => 'Whiplash', 'ano' => 2014],
                    ['titulo' => 'Mad Max: Estrada da Fúria', 'ano' => 2015],
                ],
                'cansa' => 'drama contemplativo que confunde silêncio com profundidade.',
                'curto' => 'Corpo acelerado',
            ],
        ],

        'moralidade' => [
            'e' => [
                'manchete' => 'Você gosta de sair sem saber de que lado ficar.',
                'corpo' => 'Zona cinzenta, culpa e escolhas erradas te parecem mais honestas que um herói sem sombra. O melhor filme te deixa desconfortável — e é aí que a história começa.',
                'combina' => [
                    ['titulo' => 'Pulp Fiction', 'ano' => 1994],
                    ['titulo' => 'Onde os Fracos Não Têm Vez', 'ano' => 2007],
                ],
                'cansa' => 'fábula que explica quem é bom e quem é mau antes dos créditos.',
                'curto' => 'Zona cinzenta',
            ],
            'd' => [
                'manchete' => 'Você ainda acredita que o cinema pode escolher um lado.',
                'corpo' => 'Heróis com motivo e vilões com peso te dão o prazer da fábula. Não é ingenuidade: é querer que a história tenha uma bússola e que o final signifique alguma coisa.',
                'combina' => [
                    ['titulo' => 'Star Wars: Uma Nova Esperança', 'ano' => 1977],
                    ['titulo' => 'O Senhor dos Anéis: A Sociedade do Anel', 'ano' => 2001],
                ],
                'cansa' => 'filme que confunde ambiguidade com falta de rumo.',
                'curto' => 'Lados claros',
            ],
        ],

        'estrutura' => [
            'e' => [
                'manchete' => 'Você quer saber para onde a história vai.',
                'corpo' => 'Causa e efeito, arco fechado e um final que responde: isso te dá prazer, não tédio. Você confia na narrativa que constrói o caminho sem esconder o mapa.',
                'combina' => [
                    ['titulo' => 'Forrest Gump', 'ano' => 1994],
                    ['titulo' => 'A Viagem de Chihiro', 'ano' => 2001],
                ],
                'cansa' => 'quebra-cabeça que parece exercício de estilo.',
                'curto' => 'História linear',
            ],
            'd' => [
                'manchete' => 'Você não quer que a história entregue tudo pronto.',
                'corpo' => 'Filmes que embaralham o tempo te convidam a montar o sentido junto — o prazer está na montagem, não no final. Você se inclina para a frente em vez de desistir.',
                'combina' => [
                    ['titulo' => 'Pulp Fiction', 'ano' => 1994],
                    ['titulo' => 'Amnésia', 'ano' => 2000],
                ],
                'cansa' => 'trama linear que explica cada passo antes de acontecer.',
                'curto' => 'História em pedaços',
            ],
        ],

        'atmosfera' => [
            'e' => [
                'manchete' => 'O mundo inteiro cabe num quarto.',
                'corpo' => 'Closes, silêncios e poucos personagens te dizem mais que um exército. O épico só funciona quando tem intimidade dentro — e você prefere o rosto ao horizonte.',
                'combina' => [
                    ['titulo' => 'Antes do Amanhecer', 'ano' => 1995],
                    ['titulo' => 'História de um Casamento', 'ano' => 2019],
                ],
                'cansa' => 'batalha sem rosto e sem consequência.',
                'curto' => 'Mundo num quarto',
            ],
            'd' => [
                'manchete' => 'Você quer sentir o tamanho das coisas no corpo.',
                'corpo' => 'Deserto, planeta, multidão e trilha orquestral: o cinema é o lugar onde a escala se sente. Você aceita o exagero quando ele tem ideia por trás.',
                'combina' => [
                    ['titulo' => 'Lawrence da Arábia', 'ano' => 1962],
                    ['titulo' => 'Duna', 'ano' => 2021],
                ],
                'cansa' => 'filme pequeno que se comporta como se fosse grande.',
                'curto' => 'Escala épica',
            ],
        ],

        'epoca' => [
            'e' => [
                'manchete' => 'Você sente o tempo no material.',
                'corpo' => 'Grão, figurino, arquitetura e memória: o passado no cinema não é decoração, é uma forma de sentir o presente. Você gosta quando o filme tem cheiro de época.',
                'combina' => [
                    ['titulo' => 'Cinema Paradiso', 'ano' => 1988],
                    ['titulo' => 'Meia-Noite em Paris', 'ano' => 2011],
                ],
                'cansa' => 'futuro genérico que não pensa em nada.',
                'curto' => 'Cheiro de passado',
            ],
            'd' => [
                'manchete' => 'O amanhã te interessa mais que a saudade.',
                'corpo' => 'Neon, código e cidades impossíveis são o seu presente, não o seu futuro distante. Você quer o cinema que imagina o que vem depois — e que usa o amanhã para falar do agora.',
                'combina' => [
                    ['titulo' => 'Blade Runner 2049', 'ano' => 2017],
                    ['titulo' => 'Akira', 'ano' => 1988],
                ],
                'cansa' => 'nostalgia de época sem ideia nova.',
                'curto' => 'Amanhã',
            ],
        ],
    ],
];
