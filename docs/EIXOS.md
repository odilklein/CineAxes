# Os 8 eixos do PersonaCut

Este documento é a régua editorial do produto: define o que cada eixo mede, por que
ele é uma dimensão reconhecível da crítica de cinema e como as respostas viram
números. **Toda pontuação de 0 a 100 é relativa ao polo direito do eixo**:
`0` = afinidade máxima com o polo esquerdo, `50` = neutro, `100` = polo direito.

## Base crítica

O modelo não tenta medir "personalidade" no sentido psicológico. Ele organiza
**preferências de forma, narrativa, tempo e experiência** — categorias que a crítica
de cinema usa há décadas:

| Eixo | O que mede | Diálogo crítico |
| --- | --- | --- |
| **Forma** | linguagem visual: transparência clássica ↔ ruptura formal | Bordwell, *Narration in the Fiction Film* (modo clássico x arte); Bazin, *What Is Cinema?*; Sontag, *Against Interpretation* (a forma como prazer) |
| **Narrativa** | arquitetura da história: causa-e-efeito linear ↔ fragmentação | Bordwell, *The Classical Hollywood Cinema* (fabula/syuzhet); a narração de arte que "expõe o dispositivo" |
| **Realismo** | invenção do mundo: ruas reconhecíveis ↔ cosmologia inventada | Bazin e Kracauer (realismo, plano longo, imagem indexical); a tradição oposta de Méliès e da fantasia |
| **Tempo** | experiência da duração: contemplação ↔ aceleração | Deleuze, *Cinema 2: The Time-Image*; Tarkovsky, *Sculpting in Time*; o debate do slow cinema (Çağlayan) |
| **Tom** | registro emocional: melancolia ↔ euforia | a distinção clássica entre tragédia e comédia; o "erotics of art" de Sontag, que devolve o prazer ao primeiro plano |
| **Moral** | posição ética: ambiguidade ↔ clareza maniqueísta | V. F. Perkins, *Film as Film* (crítica moral); a tradição do noir e do anti-herói |
| **Escala** | medida do drama: close e intimidade ↔ espetáculo e épica | Balázs, *Visible Man* (o close-up); a oposição entre rosto e paisagem |
| **Temporalidade** | orientação no tempo: memória e passado ↔ futuro e especulação | Deleuze (tempo direto); Boym, *The Future of Nostalgia* (nostalgia como modo, não gênero) |

Isso não é uma grade fechada: é uma hipótese editorial explícita, testável com o
catálogo. Um crítico pode discordar de um polo; o importante é que cada eixo tenha
uma definição estável e não se sobreponha aos outros.

### Por que não "Época"?

"Época" descrevia cenário (figurino, década), não uma escolha formal. Trocamos por
**Temporalidade**, que pergunta como o filme se posiciona no tempo — memória,
preservação, luto contra futurabilidade, especulação, tecnologia. É uma dimensão de
**modo**, não de ambientação, e conversa com a tradição do time-image.

## Os eixos

| # | Eixo | Polo esquerdo (E) | Polo direito (D) | Gêneros TMDB (E) | Gêneros TMDB (D) |
| --- | --- | --- | --- | --- | --- |
| 1 | Forma | Clássica | Experimental | Drama (18), História (36), Romance (10749) | Fantasia (14), Animação (16), Mistério (9648) |
| 2 | Tom | Melancólico | Eufórico | Drama (18), Romance (10749), Guerra (10752) | Comédia (35), Aventura (12), Música (10402) |
| 3 | Realismo | Realista | Fantástico | Documentário (99), Crime (80), História (36) | Ficção científica (878), Fantasia (14), Animação (16) |
| 4 | Tempo | Contemplativo | Frenético | Drama (18), Faroeste (37), Documentário (99) | Ação (28), Suspense (53), Aventura (12) |
| 5 | Moral | Ambígua | Maniqueísta | Crime (80), Suspense (53), Mistério (9648) | Família (10751), Aventura (12), Fantasia (14) |
| 6 | Narrativa | Linear | Fragmentada | Drama (18), Faroeste (37), História (36) | Mistério (9648), Ficção científica (878), Suspense (53) |
| 7 | Escala | Íntima | Épica | Drama (18), Romance (10749), Música (10402) | Aventura (12), Guerra (10752), Ficção científica (878) |
| 8 | Temporalidade | Memória | Futuro | História (36), Faroeste (37), Romance (10749) | Ficção científica (878), Ação (28), Fantasia (14) |

### Definições operacionais

1. **Forma** — o prazer da imagem: enquadramento, luz, decupagem e continuidade
   invisível contra textura, montagem livre, auto-reflexão e ruptura.
2. **Tom** — o que fica depois: peso, silêncio e catarse triste contra leveza, riso
   e saída em estado de festa.
3. **Realismo** — o grau de invenção do mundo: ruas, corpos e conflitos plausíveis
   contra regras próprias, mitologias e mundos impossíveis.
4. **Tempo** — a experiência da duração: planos que respiram, *temps morts* e espera
   contra corte rápido, perseguição e adrenalina.
5. **Moral** — como o filme julga: zona cinzenta em que ninguém é inocente contra
   bem e mal demarcados.
6. **Narrativa** — como a história é montada: causalidade linear e arco fechado
   contra quebra de tempo, camadas e realidades paralelas.
7. **Escala** — a medida do drama: quartos, closes e poucos personagens contra
   exércitos, planetas e tela grande.
8. **Temporalidade** — de onde o filme fala: memória, grão e passado contra neon,
   código e imaginação do amanhã.

## Régua de intensidade

| Distância de 50 | Rótulo | Leitura |
| --- | --- | --- |
| 0 a 7,99 | **Equilibrado** | o eixo quase não diferencia o perfil |
| 8 a 19,99 | **Tendência** | inclinação clara, mas sem radicalismo |
| 20 ou mais | **Purista** | o eixo é uma assinatura do perfil |

## Distribuição das perguntas

- **240 perguntas** no total: **30 por eixo**, 15 para cada polo.
- As **32 primeiras** (4 por eixo) são as curadas, com cena da TMDB; as duas
  primeiras de cada eixo têm peso 2 e formam o núcleo (`modo_rapido = 1`).
- As outras **208** (26 por eixo) foram curadas em cima de uma geração assistida e
  não têm mídia.
- **Direção**: `+1` quando concordar empurra para o polo direito; `-1` quando
  empurra para o esquerdo.
- A `ordem` é round-robin entre os eixos: a cada rodada, uma pergunta de cada eixo.

### Modos e cotas

| Modo | Perguntas | Cota por eixo | Tempo |
| --- | --- | --- | --- |
| `rapido` | 36 | 6, 6, 4, 4, 4, 4, 4, 4 | ~5 min |
| `completo` | 60 | 8, 8, 8, 8, 8, 8, 6, 6 | ~9 min |
| `extremo` | 240 | 30 em todos | ~35 min |

A cota é distribuída em passos de 2 (`Pergunta::cotas`), para que cada eixo receba
o mesmo número de afirmações de cada polo, e cresce junto com o modo. Assim o modo
menor é sempre um subconjunto do maior, com as mesmas perguntas por eixo. A ordem
de exibição intercala os eixos (1ª de cada, 2ª de cada, ...), então qualquer
prefixo do teste já é equilibrado.

### Fórmula do eixo

```
pct_dir = 50 + 50 · Σ(resposta · direção · peso) / (2 · Σpeso)
```

`resposta` vai de `-2` a `+2` (5 pontos, igualmente espaçados). O resultado é
limitado a `[0, 100]` e arredondado para uma casa decimal na exibição.

Essa fórmula é **algebricamente equivalente** à média ponderada de "esquerdice"
usada pelo 12 Axes: mapeando `a = (resposta + 2) / 4`, a afinidade com o polo
esquerdo é `100 · Σ(peso · leftScore) / Σpeso`, com `leftScore = (2 − resposta · direção) / 4`.

## Arquétipos: match do vetor completo

O arquétipo **não** é mais decidido só pelos dois eixos mais extremos. Cada um dos
16 arquétipos tem um **vetor de referência de 8 eixos** (`arquetipos.eixo_1..8`) e o
matcher escolhe o mais próximo do vetor do usuário — o mesmo princípio que o 12
Axes usa para achar a ideologia mais próxima. Os dois eixos extremos continuam
aparecendo como chips no dashboard, mas são informação, não a chave do título.

- **Bônus de dominância**: o arquétipo cujo `eixo_dominante_1`/`polo_1` coincide
  com o eixo mais extremo do usuário ganha 8 pontos. Sem isso, um tipo com o eixo
  principal neutro poderia vencer só porque os eixos secundários coincidiram.
- Empate: vence o arquétipo de menor `id` (determinístico).
- Perfil com todos os eixos a menos de 3 pontos do centro: **O Espectador Neutro**.
- O segundo eixo (`eixo_dominante_2`/`polo_2`) é um modificador de texto, não uma
  chave de busca.

## Afinidade: score composto

A nota exibida (e a ordenação) usa quatro componentes, com os pesos do 12 Axes:

| Componente | Peso | Conta |
| --- | --- | --- |
| Similaridade por eixo | 42% | `1 − (diferença/50)²`, com penalidade de até 45% quando os dois estão em lados opostos do centro |
| Direção | 33% | cosseno dos vetores centrados, "aumentado" com raio 8 (`50 + 50·cos`) |
| Magnitude | 18% | `100 − 2·|intensidadeA − intensidadeB|` |
| Outlier | 7% | `1 − (maiorDiferença/100)^2,5` |

A **raridade** continua separada: ela mede quantos perfis da base têm diferença
média ≤ 12 por eixo (uma métrica de comunidade, não de compatibilidade).

## Entidades e estante

O seed traz **36 personagens, 10 diretores e 6 escolas/estúdios** (52 entidades) e
**32 obras essenciais** em `entidades_obras`.

- Cada personagem tem uma obra de definição (`obra_origem` + `ano`) e um intérprete
  (`ator_nome`). O pôster vem do filme; o retrato, do ator.
- A **estante do perfil** combina a obra dos 6 personagens espelho com as obras
  essenciais do diretor e do estúdio, sem repetir filmes.
- Diretores e escolas ganham retrato/logo, país, nascimento e fichas.
- Imagens e metadados são preenchidos por `bin/tmdb_sync.php` e versionados em
  `sql/003_enriquecimento.sql`. Sem a chave, o front usa monogramas na cor do eixo.

O resultado não recomenda "o que está em cartaz": ele descreve o repertório que
forma o espelho do usuário.

## Leitura recomendada

- David Bordwell, *Narration in the Fiction Film* (1985).
- André Bazin, *What Is Cinema?* (1967).
- Gilles Deleuze, *Cinema 2: The Time-Image* (1985).
- Andrei Tarkovsky, *Sculpting in Time* (1986).
- Susan Sontag, *Against Interpretation* (1966).
- V. F. Perkins, *Film as Film* (1972).
- Béla Balázs, *Visible Man* (1924).
- Svetlana Boym, *The Future of Nostalgia* (2001).
- Orhan Emre Çağlayan, *Screening Boredom: The History and Aesthetics of Slow Cinema* (2014).
