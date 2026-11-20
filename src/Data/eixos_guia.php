<?php

declare(strict_types=1);

/**
 * Guia editorial dos 6 eixos (modelo v2).
 *
 * Alimenta o modal "Entender este eixo" durante o teste. As referências são
 * paráfrases do argumento de cada autor, não citações literais.
 */

return [
    'estetica' => [
        'resumo' => 'A pergunta por trás deste eixo é simples: o que você quer que a imagem faça — desaparecer a serviço da história ou se apresentar como linguagem?',
        'paragrafos' => [
            'No polo clássico, o cinema herdado de Hollywood esconde o próprio trabalho: decupagem invisível, continuidade de olhar, luz que só orienta a atenção. Você não vê o enquadramento, vê a cena. A imagem é um veículo.',
            'No polo experimental, a imagem se exibe: montagem que quebra o raccord, textura, câmera que se assume, cor saturada, um plano que dura além do funcional. O prazer está no gesto formal, não apenas no que ele conta.',
            'Nenhum dos lados é mais "cinema". Casablanca e Cidade dos Sonhos são igualmente rigorosos; o eixo só diz onde você se senta com mais conforto.',
        ],
        'leitura' => [
            'esq' => 'Perto de 0, você pede que a forma seja transparente: história em primeiro plano, técnica a serviço dela.',
            'dir' => 'Perto de 100, você pede que a forma se mostre: textura, ruptura e consciência do dispositivo fazem parte do prazer.',
        ],
        'cenas' => [
            [
                'obra' => 'Casablanca',
                'ano' => 1942,
                'tipo' => 'filme',
                'cena' => 'A despedida no aeroporto: campo e contracampo limpos, luz de estúdio, nenhum plano chamando atenção para si.',
                'porque' => 'É o padrão clássico levado ao ponto de invisibilidade: você chora a cena, não o enquadramento.',
                'polo' => 'esq',
            ],
            [
                'obra' => 'Cidade dos Sonhos',
                'ano' => 2001,
                'tipo' => 'filme',
                'cena' => 'A sequência do Club Silencio, com Rebekah Del Rio cantando "Llorando" e o som continuando depois que ela desmaia.',
                'porque' => 'A imagem assume que é imagem: som, playback e encenação viram o assunto da própria cena.',
                'polo' => 'dir',
            ],
            [
                'obra' => 'Jeanne Dielman, 23, quai du Commerce, 1080 Bruxelles',
                'ano' => 1975,
                'tipo' => 'filme',
                'cena' => 'Planos fixos e longos de tarefas domésticas, sem decupagem que "explique" o que está acontecendo.',
                'porque' => 'O enquadramento é a própria tese do filme: o tempo e o espaço da personagem não são ilustrados, são impostos.',
                'polo' => 'dir',
            ],
            [
                'obra' => 'Fleabag',
                'ano' => 2016,
                'tipo' => 'série',
                'cena' => 'A protagonista olha para a câmera e comenta a própria cena no meio da ação.',
                'porque' => 'O dispositivo exposto em cada episódio: a série negocia o tempo todo com o espectador sobre estar sendo vista.',
                'polo' => 'dir',
            ],
        ],
        'citacoes' => [
            [
                'autor' => 'David Bordwell',
                'obra' => 'Narration in the Fiction Film',
                'ano' => 1985,
                'ideia' => 'Bordwell descreve o "modo clássico de narração" como um sistema em que a técnica se apaga para deixar a fábula em primeiro plano: corte invisível, motivação clara, olhar guiado.',
            ],
            [
                'autor' => 'André Bazin',
                'obra' => 'What Is Cinema?',
                'ano' => 1967,
                'ideia' => 'Bazin desconfia da montagem que decide por você: a profundidade de campo e o plano longo devolveriam ao espectador a liberdade de escolher onde olhar.',
            ],
            [
                'autor' => 'Susan Sontag',
                'obra' => 'Against Interpretation',
                'ano' => 1966,
                'ideia' => 'Sontag pede uma "erótica da arte" no lugar de uma hermenêutica: responder à forma em vez de traduzi-la em sentido.',
            ],
        ],
    ],

    'tom' => [
        'resumo' => 'Este eixo mede o clima que fica depois dos créditos: se você procura peso, silêncio e catarse triste ou leveza, riso e saída em estado de festa.',
        'paragrafos' => [
            'Melancólico não é sinônimo de triste. É o filme que aceita a perda como matéria, trabalha o silêncio, deixa a emoção decantar em vez de resolvê-la.',
            'Eufórico é o registro da comédia, da música e da aventura: o corpo sai da sala mais leve e o riso faz parte do contrato com o espectador.',
            'Quase todo grande filme mistura os dois — Manchester à Beira-Mar tem humor, O Grande Hotel Budapeste tem luto. O eixo mede a temperatura que predomina.',
        ],
        'leitura' => [
            'esq' => 'Perto de 0, o filme que te marca é o que dói: você aceita sair da sala com um nó na garganta.',
            'dir' => 'Perto de 100, o filme que te marca é o que anima: você quer rir, cantar e sair com energia.',
        ],
        'cenas' => [
            [
                'obra' => 'Manchester à Beira-Mar',
                'ano' => 2016,
                'tipo' => 'filme',
                'cena' => 'A cena na delegacia, quando Lee entende o que aconteceu: plano contido, silêncio e a interpretação de Casey Affleck no limite.',
                'porque' => 'A tristeza não é explicada por diálogo; é sustentada pelo tempo e pelo não-dito.',
                'polo' => 'esq',
            ],
            [
                'obra' => 'O Grande Hotel Budapeste',
                'ano' => 2014,
                'tipo' => 'filme',
                'cena' => 'A fuga de trenó e a perseguição com as caixas de Mendl\'s no hotel.',
                'porque' => 'Comédia pastelão e leveza visual mesmo quando a história é sobre um mundo que está acabando.',
                'polo' => 'dir',
            ],
            [
                'obra' => 'BoJack Horseman',
                'ano' => 2014,
                'tipo' => 'série',
                'cena' => 'O episódio "Fish Out of Water", quase sem diálogo, passado no fundo do mar.',
                'porque' => 'Uma série de humor que constrói um episódio inteiro em torno da solidão e da dificuldade de se comunicar.',
                'polo' => 'esq',
            ],
            [
                'obra' => 'Ted Lasso',
                'ano' => 2020,
                'tipo' => 'série',
                'cena' => 'Episódios sobre luto e fracasso que terminam em abraço, piada e otimismo explícito.',
                'porque' => 'O eufórico como programa narrativo: a série decide, sempre, puxar o espectador para cima.',
                'polo' => 'dir',
            ],
        ],
        'citacoes' => [
            [
                'autor' => 'Aristóteles',
                'obra' => 'Poética',
                'ano' => -335,
                'ideia' => 'A catarse: a tragédia organiza o terror e a piedade até produzir alívio. A tristeza deixa de ser acidente e vira forma.',
            ],
            [
                'autor' => 'Henri Bergson',
                'obra' => 'O Riso',
                'ano' => 1900,
                'ideia' => 'Bergson trata o riso como gesto social: rimos do mecânico que se instala no vivo — o corpo que tropeça, o hábito que denuncia a pessoa.',
            ],
            [
                'autor' => 'Susan Sontag',
                'obra' => 'Against Interpretation',
                'ano' => 1966,
                'ideia' => 'Ao devolver o prazer ao primeiro plano, Sontag lembra que a arte também serve para sentir, não só para ser decifrada.',
            ],
        ],
    ],

    'mundo' => [
        'resumo' => 'Este eixo mede o grau de invenção do mundo: ruas e corpos plausíveis contra regras próprias, mitologias e cosmologias impossíveis.',
        'paragrafos' => [
            'Realista: o filme pede que você reconheça o mundo — geografia existente, conflitos sociais, causalidade próxima do cotidiano. A verossimilhança é o contrato.',
            'Fantástico: o filme constrói regras próprias (magia, tecnologia, física alternativa) e pede que você as aprenda. O prazer é a coerência interna do impossível.',
            'O eixo não mede escapismo. Cidade de Deus é tão estilizado quanto O Senhor dos Anéis; a diferença é se o mundo retratado poderia existir.',
        ],
        'leitura' => [
            'esq' => 'Perto de 0, você se entrega quando reconhece o mundo: a esquina, o sotaque, a conta que não fecha.',
            'dir' => 'Perto de 100, você se entrega quando precisa aprender um mundo: mapas, línguas, regras que só existem ali.',
        ],
        'cenas' => [
            [
                'obra' => 'Cidade de Deus',
                'ano' => 2002,
                'tipo' => 'filme',
                'cena' => 'A apresentação do conjunto e dos personagens por blocos de tempo, com geografia e violência reconhecíveis.',
                'porque' => 'O mundo não é inventado: é observado, mesmo quando a câmera é estilizada.',
                'polo' => 'esq',
            ],
            [
                'obra' => 'O Senhor dos Anéis: A Sociedade do Anel',
                'ano' => 2001,
                'tipo' => 'filme',
                'cena' => 'O prólogo e o Conselho de Elrond, que ensinam línguas, povos, eras e genealogias.',
                'porque' => 'O filme começa alfabetizando você para um mundo com história própria.',
                'polo' => 'dir',
            ],
            [
                'obra' => 'Matrix',
                'ano' => 1999,
                'tipo' => 'filme',
                'cena' => 'A pílula vermelha: o filme troca as regras do mundo diante de você.',
                'porque' => 'A virada só funciona porque o novo mundo tem regras estáveis o suficiente para sustentar três filmes.',
                'polo' => 'dir',
            ],
            [
                'obra' => 'The Wire',
                'ano' => 2002,
                'tipo' => 'série',
                'cena' => 'Baltimore como personagem: portos, escolas, redações, burocracias e sotaques reais.',
                'porque' => 'A série troca trama por sistema: o mundo é o argumento.',
                'polo' => 'esq',
            ],
        ],
        'citacoes' => [
            [
                'autor' => 'André Bazin',
                'obra' => 'What Is Cinema?',
                'ano' => 1967,
                'ideia' => 'Para Bazin, o cinema tem uma dívida com o real: o plano longo e a profundidade de campo preservam a ambiguidade do mundo em vez de interpretá-la.',
            ],
            [
                'autor' => 'Siegfried Kracauer',
                'obra' => 'Theory of Film',
                'ano' => 1960,
                'ideia' => 'Kracauer vê na vocação documental do cinema — ruas, rostos comuns, acaso — sua chance de redimir a realidade física.',
            ],
            [
                'autor' => 'Georges Méliès',
                'obra' => 'tradição do cinema de trucagem',
                'ano' => 1902,
                'ideia' => 'O lado oposto: o estúdio como laboratório, em que a imagem inventa o que a câmera nunca encontrou.',
            ],
        ],
    ],

    'ritmo' => [
        'resumo' => 'Este eixo mede a experiência da duração: planos que respiram e temps morts contra corte rápido, perseguição e adrenalina.',
        'paragrafos' => [
            'Contemplativo: o filme confia no tempo. O plano dura além do necessário, o fora de campo trabalha, a espera é parte do sentido.',
            'Frenético: o filme confia no impacto. Corte curto, câmera em movimento, som que empurra — cada segundo tem de render.',
            'Não se trata de ser lento ou rápido por si. Existe lentidão vazia e aceleração precisa; o eixo mede onde você se sente em casa.',
        ],
        'leitura' => [
            'esq' => 'Perto de 0, você gosta quando o filme respira: silêncios, planos longos e cenas que não têm pressa.',
            'dir' => 'Perto de 100, você gosta quando o filme empurra: corte, movimento e som mantêm o corpo acelerado.',
        ],
        'cenas' => [
            [
                'obra' => 'Paris, Texas',
                'ano' => 1984,
                'tipo' => 'filme',
                'cena' => 'O monólogo na cabine de peep show, em um plano longo quase sem corte.',
                'porque' => 'A duração é a própria emoção: encurtar a cena destruiria o filme.',
                'polo' => 'esq',
            ],
            [
                'obra' => 'Mad Max: Estrada da Fúria',
                'ano' => 2015,
                'tipo' => 'filme',
                'cena' => 'A perseguição contínua de quase quinze minutos, com enquadramento central e corte a cada poucos segundos.',
                'porque' => 'Ritmo como arquitetura: a ação é coreografada como música, sem tempo morto.',
                'polo' => 'dir',
            ],
            [
                'obra' => 'Better Call Saul',
                'ano' => 2015,
                'tipo' => 'série',
                'cena' => 'Episódios como "Chicanery", que constroem tensão em salas e corredores com planos que duram.',
                'porque' => 'Uma série de TV que recusa a aceleração padrão do formato e deixa o desconforto durar.',
                'polo' => 'esq',
            ],
            [
                'obra' => 'The Bear',
                'ano' => 2022,
                'tipo' => 'série',
                'cena' => 'O episódio "Review", filmado em um único take de cerca de vinte minutos.',
                'porque' => 'Ansiedade como forma: o plano não corta porque o personagem também não consegue sair dali.',
                'polo' => 'dir',
            ],
        ],
        'citacoes' => [
            [
                'autor' => 'Gilles Deleuze',
                'obra' => 'Cinema 2: A Imagem-Tempo',
                'ano' => 1985,
                'ideia' => 'Quando a ação cede lugar à duração, o cinema passa a mostrar o tempo diretamente — situações ópticas e sonoras puras.',
            ],
            [
                'autor' => 'Andrei Tarkovsky',
                'obra' => 'Esculpir o Tempo',
                'ano' => 1986,
                'ideia' => 'O plano é a unidade de tempo: a montagem não cria o ritmo, apenas organiza o tempo já inscrito na imagem.',
            ],
            [
                'autor' => 'Orhan Emre Çağlayan',
                'obra' => 'Screening Boredom',
                'ano' => 2014,
                'ideia' => 'No slow cinema, o tédio é produtivo: ele devolve ao espectador a experiência do tempo em vez de substituí-la por informação.',
            ],
        ],
    ],

    'escala' => [
        'resumo' => 'Este eixo mede a escala do drama: quartos, closes e poucos personagens contra exércitos, planetas e tela grande.',
        'paragrafos' => [
            'Íntima: o drama cabe num quarto. O close-up é o plano decisivo e o rosto é a paisagem.',
            'Épica: o drama é maior que os personagens — guerra, migração, multidão, horizonte. O plano geral é o plano decisivo.',
            'Escala não é orçamento. 12 Homens e uma Sentença é pequeno no espaço e imenso na tensão; um blockbuster pode parecer minúsculo.',
        ],
        'leitura' => [
            'esq' => 'Perto de 0, você prefere o drama de quarto: poucos personagens, closes e silêncios.',
            'dir' => 'Perto de 100, você prefere o drama de horizonte: multidões, paisagens e proporções gigantes.',
        ],
        'cenas' => [
            [
                'obra' => '12 Homens e uma Sentença',
                'ano' => 1957,
                'tipo' => 'filme',
                'cena' => 'Uma sala, doze homens, e a câmera que desce gradualmente dos planos gerais aos closes.',
                'porque' => 'O espaço não cresce, mas a pressão sim: a intimidade é o próprio conflito.',
                'polo' => 'esq',
            ],
            [
                'obra' => 'Lawrence da Arábia',
                'ano' => 1962,
                'tipo' => 'filme',
                'cena' => 'O deserto em 70 mm, com a figura humana reduzida a um ponto na paisagem.',
                'porque' => 'A escala é o tema: o indivíduo diante do deserto e da história.',
                'polo' => 'dir',
            ],
            [
                'obra' => 'Succession',
                'ano' => 2018,
                'tipo' => 'série',
                'cena' => 'Disputas de poder resolvidas em salas, elevadores e closês de rosto.',
                'porque' => 'Uma série sobre impérios de mídia que escolhe contar tudo no tamanho de uma família.',
                'polo' => 'esq',
            ],
            [
                'obra' => 'Game of Thrones',
                'ano' => 2011,
                'tipo' => 'série',
                'cena' => 'Batalhas com centenas de figurantes e dragões definindo o destino de continentes.',
                'porque' => 'A épica como contrato: o drama só se resolve quando a escala do mundo entra em cena.',
                'polo' => 'dir',
            ],
        ],
        'citacoes' => [
            [
                'autor' => 'Béla Balázs',
                'obra' => 'O Homem Visível',
                'ano' => 1924,
                'ideia' => 'O close-up revela o rosto como território — a "paisagem" da fisionomia, que nenhuma outra arte mostra do mesmo jeito.',
            ],
            [
                'autor' => 'André Bazin',
                'obra' => 'A Evolução da Linguagem Cinematográfica',
                'ano' => 1950,
                'ideia' => 'O plano geral e a profundidade de campo colocam o homem dentro do espaço e devolvem ao espectador a escolha do que olhar.',
            ],
            [
                'autor' => 'Susan Sontag',
                'obra' => 'The Imagination of Disaster',
                'ano' => 1965,
                'ideia' => 'A ficção científica de catástrofe trabalha com a escala do desastre como forma de falar do presente.',
            ],
        ],
    ],

    'amplitude' => [
        'resumo' => 'O quanto você sai do familiar: outro país, outra década, outro gênero. Não é sobre ser cult; é sobre o prazer (ou o incômodo) de não reconhecer o terreno.',
        'paragrafos' => [
            'No polo circunscrito, o prazer está no reconhecimento: o gênero que você domina, o ator de sempre, o país de sempre. Familiaridade não é preguiça; é uma forma de conforto que permite prestar atenção no detalhe.',
            'No polo omnívoro, o prazer está no deslocamento: um filme iraniano dos anos 1990, um thriller coreano, um experimental que ninguém recomendou. O risco faz parte da experiência, e o familiar vira só o ponto de partida.',
            'A pesquisa de gosto chama isso de "onivorismo": em vez de uma hierarquia fixa entre alto e baixo, o que distingue o repertório é a amplitude. O eixo mede o quanto você aceita sair do seu circuito.',
        ],
        'leitura' => [
            'esq' => 'Perto de 0, você prefere o que já conhece: mesmo gênero, mesmo país, mesma época. O prazer está em reconhecer.',
            'dir' => 'Perto de 100, você busca o que ainda não viu: outro país, outra década, outro gênero. O prazer está em se deslocar.',
        ],
        'cenas' => [
            [
                'obra' => 'Parasita',
                'ano' => 2019,
                'tipo' => 'filme',
                'cena' => 'A casa modernista de vidro e o porão escondido: dois mundos no mesmo endereço.',
                'porque' => 'Um filme coreano que virou fenômeno global sem abrir mão do próprio idioma e do próprio olhar de classe.',
                'polo' => 'dir',
            ],
            [
                'obra' => 'Casablanca',
                'ano' => 1942,
                'tipo' => 'filme',
                'cena' => 'O aeroporto, a neblina e a despedida que todo mundo já viu antes de ver o filme.',
                'porque' => 'O clássico que funciona como língua franca: você reconhece a cena mesmo sem ter assistido.',
                'polo' => 'esq',
            ],
            [
                'obra' => 'Relatos Selvagens',
                'ano' => 2014,
                'tipo' => 'filme',
                'cena' => 'O primeiro episódio, no avião, com todos os passageiros descobrindo o que têm em comum.',
                'porque' => 'Um filme argentino de episódios que combina humor, violência e crítica social sem pedir licença ao circuito americano.',
                'polo' => 'dir',
            ],
        ],
        'citacoes' => [
            [
                'autor' => 'Richard Peterson',
                'obra' => 'Changing Highbrow Taste: From Snob to Omnivore',
                'ano' => 1996,
                'ideia' => 'Peterson mostra que o gosto de prestígio deixou de ser a exclusão do popular: o "omnívoro" combina alto e baixo, antigo e novo, local e estrangeiro.',
            ],
            [
                'autor' => 'Pierre Bourdieu',
                'obra' => 'Distinction',
                'ano' => 1984,
                'ideia' => 'Bourdieu lê o gosto como marcador social: o que se consome e o que se recusa diz tanto sobre posição quanto sobre prazer. O eixo de amplitude mede essa abertura, sem transformá-la em hierarquia.',
            ],
        ],
    ],
];
