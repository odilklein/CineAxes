# Recomendação, trilhas e streaming

Este documento explica como o PersonaCut transforma o vetor de 8 eixos em
recomendações de títulos, cards de curadoria e ranking de plataformas. O motor é
determinístico: nenhuma dessas notas depende de IA.

## 1. Do perfil à obra

O perfil é um vetor de 8 valores (0–100). Cada obra do catálogo também recebe um
vetor, calculado na ingestão por `Services\RecomendacaoService::construir()`.

As fontes e os pesos:

| Fonte | Peso | Exemplo |
| --- | --- | --- |
| Gênero (TMDB) | 1,00 | Terror puxa Emoção para o melancólico e Tamanho para o íntimo |
| País de origem | 0,75 | Índia puxa Emoção e Tamanho para cima; França puxa Ritmo para baixo |
| Idioma original | 0,50 | `ko` reforça o lado melancólico; `hi` reforça o euforico |
| Década | 0,35 | Antes de 1960 puxa Memória e Ritmo contemplativo |

Os desvios somam-se em torno de 50 e são limitados a 0–100. O vetor resultante é
legível: uma obra coreana de terror fica melancólica, íntima e contemporânea.

### Confiança

`confianca = min(100, 100 × sinais / 6)`, em que `sinais` conta gêneros distintos
(até 5), país, idioma e década. A confiança **não** altera o vetor guardado; ela
encolhe a nota de afinidade em direção a 50:

```
fator = 0,60 + 0,40 × confianca / 100
afinidade = 50 + (compatibilidade - 50) × fator
```

O piso de 60% evita que uma obra sem metadados pareça compatível com todo mundo.
Abaixo de `confianca_minima` (25) a obra não entra no ranking de plataformas.

### Afinidade × alinhamento

A afinidade usa `MatcherService::compatibilidade()`, a mesma régua dos espelhos.
Ela é generosa com obras perto do centro: quase todo título moderado fica em
torno de 75%.

Para diferenciar **lado do mapa**, o serviço também calcula o cosseno dos vetores
centrados (`alinhamento`, de -1 a 1). Um perfil contemplativo tem alinhamento
positivo com Ozu e negativo com um blockbuster frenético. As trilhas são
ordenadas por esse valor; os títulos exibidos continuam ordenados por afinidade.

## 2. Trilhas

A definição é editorial (`config/recomendacao.php`): nome, descrição, ícone, cor,
tipo (filme/série), países, gêneros permitidos e gêneros excluídos.

Regras:

- um título entra na **primeira** trilha compatível, na ordem do arquivo;
- só entram obras com pelo menos uma plataforma ativa (o card precisa ter ação);
- a trilha precisa de `min_titulos_trilha` (3) títulos com afinidade ≥ 62;
- o card mostra até `max_titulos_trilha` (6), ordenados por afinidade;
- o encaixe do card é a média do alinhamento dos 3 melhores títulos,
  normalizada para 0–100 (`50 + 50 × alinhamento`);
- cards com alinhamento abaixo de `alinhamento_minimo` (-0,05) são descartados;
- no máximo `max_cards` (6), ordenados por encaixe.

Exemplo: "Brasil: cinema e séries" só aparece se houver pelo menos três títulos
brasileiros disponíveis no Brasil e alinhados com o perfil.

## 3. Ranking de plataformas

Entram apenas plataformas ativas e ofertas de **assinatura inclusa**
(`flatrate`) na região `BR`.

Para cada plataforma:

| Componente | Peso | Definição |
| --- | --- | --- |
| Relevância | 0,40 | Soma de `forca^1,5` dos 25 melhores títulos; `forca = (afinidade-50)/50` |
| Acervo | 0,25 | `100 × log(1+n) / log(401)`, limitado a 100 |
| Exclusivos | 0,15 | `100 × exclusivos / (exclusivos + 15)` |
| Variedade | 0,10 | Trilhas com pelo menos 2 títulos, normalizadas por 5 |
| Preço | 0,10 | Normalizado entre a plataforma mais barata e a mais cara |

`nota = 0,40·relevância + 0,25·acervo + 0,15·exclusivos + 0,10·variedade + 0,10·preço`

Decisões:

- **log no acervo**: sem isso, a plataforma com mais títulos sempre ganharia,
  independentemente do gosto;
- **exclusivos dentro do conjunto candidato**: um título que está na Netflix e na
  Prime não conta como exclusivo de nenhuma das duas;
- **piso de 25 títulos**: plataformas pequenas não entram no pódio (mas ainda
  podem ser "melhor por trilha" com 5 títulos);
- **preço é editorial**: não vem da TMDB, é editável no painel, com data de
  conferência. Sem preço, a plataforma recebe a mediana (50).

O resultado inclui também "melhor por trilha". A plataforma vencedora é a que
combina acervo, afinidade e **especialização** no nicho:

```
nota_trilha = afinidade_média × log(1 + títulos) × (0,2 + 0,8 × títulos / acervo_da_plataforma)
```

Assim a MUBI pode ganhar "cinema francês" com 40 títulos (50% do acervo dela),
mesmo que a Netflix tenha mais títulos franceses no total. O mínimo é 5 títulos
na trilha.

## 4. Elenco

`Services\ElencoService` agrega os créditos das 80 melhores obras:

```
score(ator) = Σ forca(obra) × max(0,25; 1 - 0,12 × posição_no_crédito)
```

Direção não entra na lista de atores. O card mostra em quais títulos do gosto a
pessoa aparece e permite abrir a biografia via TMDB (cache de 24 h).

## 5. Ingestão e atualização

```
discover (trilhas) ─┐
                    ├→ detalhe + watch/providers → vetor → MySQL
cobertura (nichos) ─┘                              ↓
                                        bin/catalogo_export.php
                                                   ↓
                                  sql/010_catalogo_seed.sql
                                                   ↓
                                   bin/deploy_pacote.php
```

- `bin/catalogo_sync.php` é retomável e idempotente. O detalhe de cada título
  traz gêneros, países, duração, temporadas e disponibilidade em uma chamada.
- `--creditos` busca elenco e direção (top 10) dos títulos mais populares.
- `--recalcular` refaz somente os vetores quando as regras editoriais mudam.
- O sync revalida o que passou de **90 dias**; os termos da TMDB limitam o cache a
  **6 meses**. A disponibilidade é do **JustWatch** e exige atribuição.
- O site publicado lê apenas o MySQL: nenhuma chamada de rede para montar
  catálogo, cards ou ranking.

### Como ampliar a base

1. Edite `limite` da trilha em `config/recomendacao.php` (o valor é aplicado por
   tipo; trilhas `ambos` buscam filmes e séries).
2. Rode `bin/catalogo_sync.php` (ou `--force` para revalidar).
3. Rode `bin/catalogo_sync.php --creditos --limite=1500` para o elenco.
4. `bin/catalogo_status.php` mostra a cobertura por trilha, país e plataforma.
5. Gere o snapshot e o pacote de deploy.

## 6. Limitações conhecidas

- O vetor de obra é heurístico: gênero e país não capturam tudo o que um filme
  faz. A confiança e o alinhamento reduzem o ruído, mas não substituem crítica.
- A TMDB erra em coproduções (`origin_country`); as trilhas usam regras próprias
  em vez de um `WHERE pais = 'X'` cru.
- "Dorama" é definido por tipo + país + gêneros, não por uma lista fechada.
- Preços e catálogo das plataformas mudam; a data de conferência é exibida.
- Não há histórico de consumo: o perfil é declarado, não observado. Um passo
  futuro é o feedback "gostei/não gostei" nos cards, que ajustaria o vetor.
