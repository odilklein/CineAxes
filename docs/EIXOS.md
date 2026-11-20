# Os 6 eixos do PersonaCut (modelo v2)

Este documento é a régua editorial do produto. **Toda pontuação de 0 a 100 é
relativa ao polo direito do eixo**: `0` = polo esquerdo, `50` = neutro,
`100` = polo direito.

O modelo v2 troca os 8 eixos do protótipo por **6 eixos** mais amplos e menos
sobrepostos. A decisão vem da literatura de preferências de entretenimento
(Rentfrow et al., 2010; escala MOVIE, Monteiro, 2022) e da sociologia do gosto
(Peterson, 1992; Bourdieu, 1984): em vez de medir oito estilos formais, o quiz
mede **onde o filme circula, que afeto ele produz, que mundo ele inventa, em que
ritmo, em que escala e o quanto ele sai do familiar**.

## Os eixos

| # | Eixo | Polo esquerdo (E) | Polo direito (D) | O que mede |
| --- | --- | --- | --- | --- |
| 1 | **Estética** | Popular | Autoral | circuito: blockbuster/comercial ↔ arte/autoral/estrangeiro/clássico |
| 2 | **Tom** | Claro | Sombrio | valência afetiva: leveza, riso e conforto ↔ peso, desconforto e ambiguidade |
| 3 | **Mundo** | Real | Imaginário | ontologia: documentário/realismo social ↔ fantasia/sci-fi/mitologia |
| 4 | **Ritmo** | Calmo | Intenso | duração: plano que respira ↔ corte rápido, ação, adrenalina |
| 5 | **Escala** | Íntimo | Épico | medida do drama: quarto/close ↔ exércitos, planetas, multidão |
| 6 | **Amplitude** | Circunscrito | Omnívoro | repertório: familiar/local/recente ↔ antigo/estrangeiro/experimental |

### Por que 6 e não 8

Os oito eixos do protótipo (Imagem, Emoção, Mundo, Ritmo, Ética, História,
Tamanho, Época) se sobrepunham: "Imagem clássica ↔ experimental" e "História
linear ↔ fragmentada" medem, em boa parte, a mesma complexidade formal; "Emoção"
e "Ética" se cruzavam na valência; "Época" confundia ambientação com nostalgia.
Com 6 eixos, cada dimensão tem mais itens no mesmo total de perguntas — e a
medida fica mais confiável.

A dimensão **Amplitude** não existia no protótipo e é o eixo mais preditivo de
repertório: quanto a pessoa aceita sair do seu país, da sua época e do seu gênero
confortável.

### O que saiu da interface

- **Personagens não são mais comparados ao seu gosto.** Um personagem não tem
  preferências cinematográficas medidas; o vetor editorial dele era uma
  inferência de personalidade, não de gosto. Personagens aparecem apenas como
  rostos dos filmes que combinam com você.
- **"Quem filmaria a sua vida" saiu.** Diretores e escolas aparecem como
  "cujas obras mais combinam com você", com o vetor calculado do centroide da
  filmografia — não uma metáfora biográfica.

## Fórmula do eixo

```
pct_dir = 50 + 50 · Σ(resposta · direção · peso) / (2 · Σpeso)
```

`resposta` vai de `-2` a `+2` (5 pontos). O resultado é limitado a `[0, 100]`.

Os modos têm cotas pares por eixo, então cada polo recebe o mesmo número de
afirmações: 2/2 no Essencial, 3/3 no Completo e 4/4 no Profundo.

## Formatos

| Modo | Perguntas | Por eixo | Tempo |
| --- | --- | --- | --- |
| `rapido` (Essencial) | 24 | 4 (2 por polo) | ~3 min |
| `completo` (Completo) | 36 | 6 (3 por polo) | ~5 min |
| `extremo` (Profundo) | 48 | 8 (4 por polo) | ~7 min |

Com 4 itens por eixo, o erro-padrão ainda é alto (~15–20 pontos). Por isso a
interface mostra **faixas** e condiciona as leituras à confiança; não exibe
"62,5%" como se fosse uma medida de laboratório.

## Arquétipos: 12, com distribuição

Cada arquétipo tem um **vetor de referência de 6 eixos** (`arquetipos.eixo_1..6`)
e um eixo dominante/polo. O matcher escolhe o mais próximo do vetor completo e
devolve uma **distribuição softmax** entre os três primeiros:

- perfil com 58% / 9% / 9% → identidade razoavelmente definida;
- perfil com 23% / 14% / 14% → perfil de trânsito.

Perfil com o eixo mais extremo a menos de **8 pontos** do centro vira **Espectador
Neutro**. É o mesmo limiar de "Equilibrado" na régua de intensidade.

## Posição, não porcentagem

O score composto é comprimido: quase todo perfil fica perto de 80 com o diretor
mais próximo. Por isso a interface mostra:

- **posição no grupo** (10 diretores, 6 escolas), com margem para o próximo;
- **top % do perfil** nos filmes (posição no catálogo ranqueado);
- **IC 95% por bootstrap** das respostas e um rótulo de confiança;
- para filmes, **motivos por gênero/país/época**, não por "afinidade 82%".

Ver [CALIBRACAO.md](CALIBRACAO.md) para a auditoria e os métodos.

## Recomendação e descoberta

- Piso de qualidade: nota ≥ 6,5 e ≥ 500 votos (configurável em `config/app.php`).
- Mistura 60/40 entre descoberta e cânone, com seletor **Clássicos · Misturado ·
  Descobertas**.
- Diversidade: no máximo 2 títulos por país, década e gênero numa lista de 10.
- `Amplitude` alta libera mais país/época/experimento; baixa mantém o familiar.
- "Já vi" fica no `localStorage` e remove o título das listas no navegador.

## Base crítica

- Peter Rentfrow et al., *Listening, Watching, and Reading: The Structure and
  Correlates of Entertainment Preferences* (2010) — os cinco fatores Communal,
  Aesthetic, Dark, Thrilling e Cerebral.
- Rafael Monteiro, *O modelo MOVIE* (2022) — Melodrama, Comic, Violent,
  Imaginative e Educational em pt-BR.
- Richard Peterson, *Changing Highbrow Taste: From Snob to Omnivore* (1996).
- Pierre Bourdieu, *Distinction* (1984).
- David Bordwell, *Narration in the Fiction Film* (1985).
- André Bazin, *What Is Cinema?* (1967).
- Gilles Deleuze, *Cinema 2: The Time-Image* (1985).
- Susan Sontag, *Against Interpretation* (1966).

O modelo não é um instrumento psicológico clínico. É uma hipótese editorial
explícita, agora mais próxima da literatura empírica de preferências.
