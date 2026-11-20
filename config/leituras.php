<?php

declare(strict_types=1);

/**
 * Leituras do perfil (modelo v2, 6 eixos).
 *
 * Cada eixo tem uma leitura para cada polo. A leitura é a camada editorial que
 * traduz o vetor em consequência: o que a pessoa procura, o que a afasta e dois
 * filmes para reconhecer isso na prática.
 *
 * Os exemplos `combina` são fallback. O PerfilController substitui por filmes
 * do próprio perfil quando o catálogo está disponível.
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
                'manchete' => 'Você quer que o filme funcione antes de surpreender.',
                'corpo' => 'Histórias claras, elenco carismático e uma boa noite de cinema valem mais que um exercício de linguagem. Você não confunde dificuldade com profundidade.',
                'combina' => [
                    ['titulo' => 'Top Gun: Maverick', 'ano' => 2022],
                    ['titulo' => 'Jurassic Park', 'ano' => 1993],
                ],
                'cansa' => 'filme que confunde lentidão com importância.',
                'curto' => 'Cinema popular',
            ],
            'd' => [
                'manchete' => 'Você procura a assinatura do diretor.',
                'corpo' => 'O plano que ninguém mais faria, o filme que pede um pouco de esforço, a voz que se reconhece. O prazer está em descobrir uma linguagem, não em seguir a fórmula.',
                'combina' => [
                    ['titulo' => 'Cidade dos Sonhos', 'ano' => 2001],
                    ['titulo' => 'Amor à Flor da Pele', 'ano' => 2000],
                ],
                'cansa' => 'blockbuster que poderia ter sido dirigido por qualquer um.',
                'curto' => 'Cinema de autor',
            ],
        ],

        'tom' => [
            'e' => [
                'manchete' => 'Você vai ao cinema para sair mais leve.',
                'corpo' => 'Humor, música e finais que resolvem são a sua forma de estar no mundo. Você não foge do drama, mas não quer que ele seja a regra.',
                'combina' => [
                    ['titulo' => 'La La Land', 'ano' => 2016],
                    ['titulo' => 'Tudo em Todo Lugar ao Mesmo Tempo', 'ano' => 2022],
                ],
                'cansa' => 'filme que confunde peso com profundidade.',
                'curto' => 'Leveza',
            ],
            'd' => [
                'manchete' => 'Você quer que o filme fique com você depois do fim.',
                'corpo' => 'O seu cinema termina em silêncio, com uma pergunta aberta. Você não confunde tristeza fácil com verdade: o que fica é o que doeu de verdade.',
                'combina' => [
                    ['titulo' => 'Melancolia', 'ano' => 2011],
                    ['titulo' => 'Manchester à Beira-Mar', 'ano' => 2016],
                ],
                'cansa' => 'comédia que pede risada antes de construir qualquer coisa.',
                'curto' => 'Peso',
            ],
        ],

        'mundo' => [
            'e' => [
                'manchete' => 'Você se interessa pelo que poderia acontecer na esquina.',
                'corpo' => 'Personagens verossímeis, ruas reconhecíveis e conflitos sem solução mágica. O mundo, para você, já é grande o suficiente.',
                'combina' => [
                    ['titulo' => 'Cidade de Deus', 'ano' => 2002],
                    ['titulo' => 'Paterson', 'ano' => 2016],
                ],
                'cansa' => 'fantasia que usa o mundo inventado para fugir do presente.',
                'curto' => 'Real',
            ],
            'd' => [
                'manchete' => 'Você quer regras novas.',
                'corpo' => 'Mundos inventados, mitologias e criaturas são menos fuga do que uma forma de olhar o real de outro ângulo. Você aceita o pacto da ficção.',
                'combina' => [
                    ['titulo' => 'A Viagem de Chihiro', 'ano' => 2001],
                    ['titulo' => 'Duna', 'ano' => 2021],
                ],
                'cansa' => 'filme que inventa um mundo e não faz nada com ele.',
                'curto' => 'Imaginário',
            ],
        ],

        'ritmo' => [
            'e' => [
                'manchete' => 'Você confia no tempo longo.',
                'corpo' => 'Um plano parado pode dizer mais que dez cortes, e você prefere reparar no detalhe a ser empurrado para o próximo estímulo.',
                'combina' => [
                    ['titulo' => 'Paterson', 'ano' => 2016],
                    ['titulo' => 'A Árvore da Vida', 'ano' => 2011],
                ],
                'cansa' => 'edição que não deixa a cena existir.',
                'curto' => 'Tempo longo',
            ],
            'd' => [
                'manchete' => 'Você quer o corpo dentro do filme.',
                'corpo' => 'Perseguição, corte seco e som no limite são a gramática que te mantém presente na cadeira. Você não quer contemplar: quer atravessar.',
                'combina' => [
                    ['titulo' => 'Mad Max: Estrada da Fúria', 'ano' => 2015],
                    ['titulo' => 'Whiplash', 'ano' => 2014],
                ],
                'cansa' => 'filme que confunde silêncio com tédio.',
                'curto' => 'Adrenalina',
            ],
        ],

        'escala' => [
            'e' => [
                'manchete' => 'Você prefere o rosto ao horizonte.',
                'corpo' => 'Quartos, segredos domésticos e dois personagens falando baixo dizem mais sobre o mundo do que qualquer exército em cena.',
                'combina' => [
                    ['titulo' => 'História de um Casamento', 'ano' => 2019],
                    ['titulo' => 'Antes do Amanhecer', 'ano' => 1995],
                ],
                'cansa' => 'espetáculo que esquece as pessoas.',
                'curto' => 'Close',
            ],
            'd' => [
                'manchete' => 'Você quer escala.',
                'corpo' => 'Deserto, planeta, multidão, trilha orquestral — o cinema é o único lugar onde o tamanho das coisas pode ser sentido no corpo.',
                'combina' => [
                    ['titulo' => 'Lawrence da Arábia', 'ano' => 1962],
                    ['titulo' => 'O Senhor dos Anéis: A Sociedade do Anel', 'ano' => 2001],
                ],
                'cansa' => 'filme pequeno que se acha grande.',
                'curto' => 'Épico',
            ],
        ],

        'amplitude' => [
            'e' => [
                'manchete' => 'Você prefere o que já conhece.',
                'corpo' => 'O gênero que domina, o ator de sempre, o país de sempre. Familiaridade não é preguiça: é o prazer de reconhecer o terreno.',
                'combina' => [
                    ['titulo' => 'Top Gun: Maverick', 'ano' => 2022],
                    ['titulo' => 'O Auto da Compadecida', 'ano' => 2000],
                ],
                'cansa' => 'filme que exige pesquisa antes de começar.',
                'curto' => 'Familiar',
            ],
            'd' => [
                'manchete' => 'Você busca o que ainda não viu.',
                'corpo' => 'Outro país, outra década, outro gênero. O risco faz parte do prazer, e o familiar é só o ponto de partida.',
                'combina' => [
                    ['titulo' => 'Parasita', 'ano' => 2019],
                    ['titulo' => 'Relatos Selvagens', 'ano' => 2014],
                ],
                'cansa' => 'lista que só repete os mesmos dez títulos.',
                'curto' => 'Omnívoro',
            ],
        ],
    ],
];
