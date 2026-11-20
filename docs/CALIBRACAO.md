> **Nota de versão (v2).** A auditoria abaixo foi feita no modelo de **8 eixos**.
> O v2 usa **6 eixos** (Estética, Tom, Mundo, Ritmo, Escala, Amplitude), 24/36/48
> perguntas e recomendações com camada de descoberta. Os métodos — posição,
> bootstrap, confiança, corte adaptativo — continuam os mesmos; os números de
> perfis e de entidades mudaram.

# Calibração: o que o PersonaCut mede, o que estima e o que é curadoria

Este documento registra a auditoria feita em 19/11/2026 e as mudanças da versão
1.7. Ele existe para responder a uma pergunta simples: **"eu estou perto do
Gandalf" é uma medição?**

Resposta curta: **não no sentido científico.** É geometria sobre um vetor de 8
números atribuído à mão ao personagem. Isso pode ser útil como metáfora e como
organização de repertório, mas não é uma probabilidade nem uma porcentagem de
personalidade. A partir desta versão, a interface fala em **posição relativa**,
**margem** e **confiança**, não em "82% de afinidade".

## 1. As três camadas

| Camada | Exemplo | Natureza |
| --- | --- | --- |
| **Medida** | respostas do quiz, fórmula Likert | aritmética correta, com erro de amostragem |
| **Editorial** | vetor do Gandalf `(30,45,100,45,90,30,90,25)` | julgamento humano, sem procedência registrada |
| **Derivada** | ranking, posição, trilhas, plataformas | geometria sobre as duas camadas acima |

O código não contém "alucinação de IA": as fórmulas são determinísticas e
documentadas. O risco está em apresentar a camada editorial com a mesma
autoridade da camada medida.

## 2. Achados da auditoria

Com o banco de 19/11/2026 (4.040 obras, 52 entidades, 11 perfis reais):

- **Score comprimido**: um perfil neutro já tem 87% do catálogo acima de 62 de
  afinidade e 50% acima de 75. "75%" não distingue nada.
- **Melhor personagem sempre alto**: em 20 mil perfis aleatórios, o melhor
  personagem ficou em mediana 82,5. Quase todo mundo recebe "82%+".
- **Empate técnico**: a margem entre 1º e 2º lugar tem mediana 2,3 pontos (p25
  = 1,0). O primeiro lugar costuma ser indistinguível do segundo.
- **Gandalf**: aparece em 1º em 2,78% dos perfis aleatórios — exatamente o
  acaso (1/36). No top-10, em 24,45%, *abaixo* do acaso (27,8%). Nos 11 perfis
  reais, ele ficou entre 16º e 36º em 10 deles.
- **Instabilidade**: reamostrando as respostas, o 1º personagem se manteve em
  apenas 12% a 58% das simulações. O IC 95% da posição do Gandalf chegou a
  [2, 36].
- **Perfil extremo quebrava**: o perfil `HRC4K4Q7` tinha zero títulos acima de
  75 e **zero plataformas**. O limiar fixo de 62 não fazia sentido para ele.
- **Base sintética degenerada**: `RAND(n*1000+k)` no MySQL gerou linhas
  alternadas `A,B,A,B,...`, não uma nuvem. O "1 em N" era calculado contra duas
  retas.
- **Respondedor extremo virava neutro**: quem respondia "concordo totalmente"
  em tudo tinha as respostas anuladas e recebia vetor 50 em todos os eixos,
  tratado como "neutro" com confiança total.
- **Réguas misturadas**: 5 dos 11 perfis reais guardam 16 respostas (2 por eixo)
  e 6 guardam 36. Nada registrava qual questionário foi usado.
- **Coordenadas sem lastro**: o vetor editorial médio difere **17,9 pontos por
  eixo** do centroide das obras do próprio catálogo (`bin/calibrar_entidades.php`).

## 3. O que mudou

### Posição em vez de porcentagem

`Services\CalibracaoService::anotar()` transforma o score bruto em:

- `posicao` — posição de competição (empates compartilham a posição);
- `de` — tamanho do grupo (36 personagens, 10 diretores, 6 escolas);
- `top_pct` — "top X%" do grupo;
- `percentil` — 100 no topo, 0 na base;
- `margem` — distância para o próximo colocado;
- `z` — desvio-padrão em relação ao grupo.

A interface mostra `3º de 36 · top 8%`, e a margem aparece no top-10.

### Confiança por bootstrap

`CalibracaoService::estabilidade()` reamostra as respostas de cada eixo 120
vezes (com reposição), recalcula o vetor e o ranking, e devolve a mediana e o
IC 95% da posição de cada entidade. A fração de vezes em que o 1º lugar se
mantém vira um rótulo: `alta` (≥ 75%), `media` (≥ 45%) ou `baixa`.

Perfis antigos sem `respostas_json` ficam como `nao_estimada` — o sistema não
inventa confiança. A semente do bootstrap é derivada do código e da versão do
questionário, então o mesmo perfil mostra sempre o mesmo intervalo (a página é
reproduzível).

### Resposta intensa que se anula

Quem responde "concordo totalmente" em tudo tem as afirmações dos dois polos
anuladas e cai em 50. O sistema detecta esse padrão (intensidade média ≥ 1,5 e
nenhum eixo além do limiar de neutro) e rotula o perfil como **indeterminado**,
não "neutro". É a diferença entre "não tenho preferência" e "não consegui
expressá-la neste questionário".

### Neutro consistente e arquétipo suave

- O limiar de "neutro" passou de 3,0 para **8,0**, o mesmo da escala de
  intensidade ("Equilibrado").
- O bônus de dominância é limitado a 100 (antes podia chegar a 103,7).
- `resolverArquetipo()` devolve uma **distribuição softmax** entre os três
  primeiros. Um perfil 58% / 9% / 9% é uma identidade; um perfil 23% / 14% /
  14% é um perfil de trânsito.

### Corte adaptativo no catálogo

O limiar fixo de 62 foi trocado por `RecomendacaoService::corteAdaptativo()`:
a afinidade do título na posição "top 20%" do perfil, com piso de 40 títulos.
Assim todo perfil — inclusive o mais extremo — tem um conjunto de candidatos do
próprio tamanho, e o ranking de plataformas deixa de zerar.

### Base sintética válida

`bin/base_sintetica.php` gera 600 perfis com cada eixo sorteado de forma
independente (distribuição triangular em torno de 50). A base antiga foi
removida pela migração 011. A base continua sendo um *prior*, não uma medição:
serve até haver perfis reais suficientes.

### Raridade

- O próprio perfil é excluído da base (`Resultado::vetores(..., $codigo)`).
- O resultado informa `reais` e `confiavel`.
- A raridade não é exibida como selo enquanto a base real não justificar.

### Versão do questionário

`Pergunta::VERSAO` é gravada em `resultados_usuarios.questionario_versao`.
Vetores de réguas diferentes ficam identificáveis. Perfis antigos ficam com
`NULL` (régua desconhecida).

## 4. Como ler a interface agora

| Antes | Depois |
| --- | --- |
| "Batman · 86,5% de afinidade" | "Batman · 1º de 36 · top 3%" |
| "10 personagens mais próximos. De 412 perfis comparados." | "36 personagens comparados. A posição é relativa ao grupo." |
| "Índice de raridade: 1 em 3" | (oculto até a base real crescer) |
| "Sua plataforma: Netflix · nota 78,7" | "Netflix + MUBI cobrem 78% dos seus 50 melhores títulos" |

A explicação do match usa os dois eixos em que perfil e entidade apontam para o
mesmo lado: `Mundo: você 88 · ele 100`. O `title` do chip mostra a frase
completa.

## 5. Ferramentas

```bash
# regerar a base sintética (após a migração 011)
docker compose exec app php bin/base_sintetica.php --recriar --total=600

# psicometria: alfa de Cronbach, correlação item-total, itens a reescrever
docker compose exec app php bin/analise_itens.php --minimo=5

# comparar vetor editorial com o centroide das obras do catálogo
docker compose exec app php bin/calibrar_entidades.php

# aplicar a mistura (50% catálogo / 50% editorial)
docker compose exec app php bin/calibrar_entidades.php --aplicar --peso-catalogo=0.5
```

`bin/calibrar_entidades.php` é deliberadamente conservador: sem `--aplicar` ele
só relata. No modelo v2, diretores e escolas têm o vetor calculado do centroide
da filmografia em tempo de execução; `--aplicar` está desativado para não gravar
6 coordenadas nas colunas legadas de 8. O ideal é validar com julgamentos
pareados antes de promover uma entidade para `vetor_origem = 'catalogo'`.

## 6. O que ainda falta (e exige dados, não código)

1. **Calibração empírica das coordenadas**: painel de 10–20 pessoas julgando
   pares de filmes ("qual é mais contemplativo?") e estimação por
   Bradley–Terry/Thurstone. Depois, regressão dos deltas de gênero/país.
2. **IRT (graded response)** para estimar habilidade com erro-padrão e teste
   adaptativo: a mesma precisão com ~15 perguntas em vez de 36.
3. **Estilo de resposta**: modelar concordância global para separar "concordo
   com tudo" de "sou neutro".
4. **Teste-reteste** com 20 pessoas em duas semanas para medir confiabilidade.
5. **Loop de cliques**: aprender os pesos do score composto com dados reais, em
   vez dos 0,42/0,33/0,18/0,07 herdados do 12 Axes.

Enquanto esses passos não acontecem, a interface deve continuar falando em
posição e confiança — nunca em probabilidade.
