<?php

declare(strict_types=1);

/** Landing page do PersonaCut. */

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use Models\Eixo;

// Os eixos vêm do banco; se o banco ainda não estiver pronto, a página usa
// uma lista estática para não quebrar a apresentação.
$eixos = [];
$eixosDoBanco = true;

try {
    $eixos = Eixo::todos(Core\Database::pdo());
} catch (Throwable $erro) {
    $eixosDoBanco = false;
    $slugs = ['estetica', 'tom', 'universo', 'ritmo', 'moralidade', 'estrutura', 'atmosfera', 'epoca'];
    $icones = ['frame', 'moon', 'building', 'feather', 'scale', 'list', 'home', 'film'];
    $cores = [['#A9762F', '#6C63B5'], ['#4F7399', '#D9A441'], ['#4F7F5F', '#9B5BA6'], ['#4E8493', '#C2553A'], ['#6E6A9E', '#B98A2E'], ['#4E8080', '#A84F6B'], ['#9C6F82', '#3F7FA8'], ['#9A7B4F', '#479184']];

    foreach ($slugs as $i => $slug) {
        $eixos[] = [
            'id' => $i + 1,
            'slug' => $slug,
            'ordem' => $i + 1,
            'nome' => t('eixo.' . $slug . '.nome'),
            'descricao' => t('eixo.' . $slug . '.desc'),
            'polo_esq_key' => 'eixo.' . $slug . '.esq',
            'polo_esq_nome' => t('eixo.' . $slug . '.esq'),
            'polo_dir_key' => 'eixo.' . $slug . '.dir',
            'polo_dir_nome' => t('eixo.' . $slug . '.dir'),
            'cor_esq' => $cores[$i][0],
            'cor_dir' => $cores[$i][1],
            'icone_esq' => $icones[$i],
            'icone_dir' => $icones[$i],
        ];
    }
}

$paginaTitulo = t('meta.title');
$paginaDescricao = t('meta.description');

/*
 * Card de exemplo da landing: usa um perfil real da base sintética, montado pelo
 * mesmo PerfilController do resultado. Assim o exemplo nunca "envelhece" e mostra
 * o arquétipo, as marcas e os espelhos de verdade. Sem banco, cai num exemplo fixo.
 */
$exemplo = [
    'codigo' => '',
    'titulo' => 'O Espectador Neutro',
    'descricao' => 'Um perfil equilibrado, que transita entre os dois polos de cada eixo.',
    'afinidade' => 0.0,
    'barras' => [],
    'marcas' => [],
    'espelhos' => [],
    'linhas' => [],
    // Dados do card 9:16 (mesmo parcial usado no resultado do questionário).
    'personagens' => [],
    'leituras' => [],
    'estante' => [],
    'diretor' => null,
    'estudio' => null,
];

try {
    $pdoExemplo = Core\Database::pdo();
    // Perfil curado do seed; em bases antigas, cai no primeiro resultado real.
    $codigoExemplo = $pdoExemplo->query(
        "SELECT codigo_unico
           FROM resultados_usuarios
          WHERE codigo_unico = 'EXEMPLO1' OR origem = 'real'
          ORDER BY (codigo_unico = 'EXEMPLO1') DESC, id
          LIMIT 1"
    )->fetchColumn();

    if (is_string($codigoExemplo) && $codigoExemplo !== '') {
        $perfilExemplo = (new Controllers\PerfilController(
            $pdoExemplo,
            new Services\MatcherService(
                (float) ($config['raridade']['limiar'] ?? 12.0),
                (int) ($config['raridade']['min_base'] ?? 100)
            ),
            new Services\LikertCalculatorService(),
            $config
        ))->montar($codigoExemplo);

        if ($perfilExemplo !== null) {
            $arquetipoExemplo = $perfilExemplo['arquetipo'];
            $linhasExemplo = $perfilExemplo['linhas'];

            // Os três eixos que mais afastam este perfil do neutro.
            usort($linhasExemplo, static function (array $a, array $b): int {
                return abs((float) $b['pct_dir'] - 50) <=> abs((float) $a['pct_dir'] - 50);
            });

            $destacadas = array_slice($linhasExemplo, 0, 3);

            $exemplo = [
                'codigo' => (string) $codigoExemplo,
                'titulo' => (string) ($arquetipoExemplo['titulo'] ?? 'O Espectador Neutro'),
                'descricao' => (string) ($arquetipoExemplo['descricao'] ?? ''),
                'afinidade' => (float) ($perfilExemplo['afinidade_media'] ?? 0),
                // Repassa os dados brutos para o card 9:16 de compartilhamento.
                'personagens' => $perfilExemplo['personagens'] ?? [],
                'leituras' => $perfilExemplo['leituras'] ?? [],
                'estante' => $perfilExemplo['estante'] ?? [],
                'diretor' => $perfilExemplo['diretor'] ?? null,
                'estudio' => $perfilExemplo['estudio'] ?? null,
                'barras' => array_map(
                    static fn (array $linha): array => [
                        'rotulo' => eixo_nome($linha['eixo']),
                        'polo'   => $linha['polo'] === 'D' ? $linha['rotulo_dir'] : $linha['rotulo_esq'],
                        'valor'  => round($linha['pct_dir'], 0),
                        'pct'    => (float) max($linha['pct_esq'], $linha['pct_dir']),
                        'cor'    => $linha['cor'],
                    ],
                    $destacadas
                ),
                'marcas' => array_map(
                    static fn (array $leitura): array => [
                        'curto' => (string) $leitura['curto'],
                        'cor'   => (string) $leitura['cor'],
                    ],
                    $perfilExemplo['leituras']
                ),
                'espelhos' => array_map(
                    static fn (array $p): array => [
                        'nome'      => nome_curto((string) $p['nome']),
                        'obra'      => (string) $p['obra_origem'],
                        'foto'      => (string) ($p['ator_foto_url'] ?: $p['imagem_url']),
                        'afinidade' => (float) $p['afinidade'],
                    ],
                    array_slice($perfilExemplo['personagens'], 0, 3)
                ),
                'linhas' => array_map(
                    static fn (array $linha): array => [
                        'nome'  => eixo_nome($linha['eixo']),
                        'esq'   => $linha['rotulo_esq'],
                        'dir'   => $linha['rotulo_dir'],
                        'pct_e' => (float) $linha['pct_esq'],
                        'pct_d' => (float) $linha['pct_dir'],
                        'cor_e' => (string) $linha['cor_esq'],
                        'cor_d' => (string) $linha['cor_dir'],
                        'nivel' => $linha['intensidade'],
                    ],
                    $destacadas
                ),
            ];
        }
    }
} catch (Throwable $erro) {
    // Landing continua com o exemplo fixo.
}

require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>

<div class="pc-marquee" aria-hidden="true">
    <div class="pc-marquee__track">
        <?php for ($volta = 0; $volta < 2; $volta++): ?>
            <span>PersonaCut</span><i>✦</i>
            <span>8 eixos</span><i>✦</i>
            <span>Um perfil</span><i>✦</i>
            <span>Cinema analógico</span><i>✦</i>
            <span>Sem cadastro</span><i>✦</i>
            <span>Resultado imediato</span><i>✦</i>
        <?php endfor; ?>
    </div>
</div>

<section class="pc-hero">
    <div class="pc-wrap pc-hero__grid">
        <div class="reveal">
            <span class="pc-eyebrow"><?= e(t('home.hero.eyebrow')) ?></span>
            <h1 class="pc-hero__title"><?= t('home.hero.title') ?></h1>
            <p class="pc-hero__sub"><?= e(t('home.hero.subtitle')) ?></p>

            <div class="pc-hero__cta">
                <a class="pc-btn" href="<?= url('quiz.php') ?>">
                    <?= e(t('home.hero.cta_primary')) ?>
                    <?= icon('arrow-right', 18) ?>
                </a>
                <a class="pc-btn pc-btn--ghost" href="#eixos">
                    <?= e(t('home.hero.cta_secondary')) ?>
                </a>
            </div>

            <div class="pc-hero__meta">
                <?php foreach (tl('home.hero.meta') as $item): ?>
                    <span><?= e($item) ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="reveal pc-hero__story">
            <?php
            /*
             * Card 9:16 idêntico ao gerado no questionário: reaproveita o mesmo
             * parcial do resultado, alimentado pelo perfil de exemplo da landing.
             * O escopo isolado evita que as variáveis do parcial vazem para a página.
             */
            (static function (array $exemplo): void {
                $codigo = $exemplo['codigo'];
                $tituloPerfil = $exemplo['titulo'];
                $perfil = [
                    'afinidade_media' => $exemplo['afinidade'],
                    'leituras' => $exemplo['leituras'],
                    'personagens' => $exemplo['personagens'],
                    'estante' => $exemplo['estante'],
                    'diretor' => $exemplo['diretor'],
                    'estudio' => $exemplo['estudio'],
                ];
                require __DIR__ . '/partials/resultado/story-card.php';
            })($exemplo);
            ?>
        </div>
    </div>
</section>

<section class="pc-section">
    <div class="pc-wrap">
        <div class="pc-section__head reveal">
            <div>
                <span class="pc-eyebrow"><?= e(t('home.discover.eyebrow')) ?></span>
                <h2 class="pc-title"><?= e(t('home.discover.title')) ?></h2>
            </div>
            <p class="pc-lead"><?= e(t('home.discover.subtitle')) ?></p>
        </div>

        <div class="pc-features">
            <?php foreach (tl('home.discover.cards') as $card): ?>
                <article class="pc-feature reveal">
                    <span class="pc-feature__icon"><?= icon((string) $card['icon'], 21) ?></span>
                    <div>
                        <h3><?= e($card['title']) ?></h3>
                        <p><?= e($card['text']) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="pc-section pc-example">
    <div class="pc-wrap">
        <div class="pc-example__grid">
            <div class="reveal">
                <span class="pc-eyebrow"><?= e(t('home.example.eyebrow')) ?></span>
                <h2 class="pc-title"><?= e(t('home.example.title')) ?></h2>
                <p class="pc-lead pc-mt-2"><?= e(t('home.example.note')) ?></p>
                <a class="pc-link-arrow pc-mt-3" href="<?= url('quiz.php') ?>">
                    <?= e(t('home.example.cta')) ?>
                    <?= icon('arrow-right', 16) ?>
                </a>
            </div>

            <div class="reveal">
                <div class="pc-example__panel">
                    <div class="pc-example__panel-head">
                        <div>
                            <span class="pc-example__badge"><?= icon('star', 12) ?> <?= e(t('resultado.affinity')) ?></span>
                            <h3 class="pc-example__name"><?= e($exemplo['titulo']) ?></h3>
                        </div>
                        <?php if ($exemplo['afinidade'] > 0): ?>
                            <span class="pc-chip pc-chip--red"><?= pct($exemplo['afinidade']) ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($exemplo['linhas'] === []): ?>
                        <p class="pc-text-muted" style="font-size:13px">
                            Rode o seed para ver um perfil de exemplo montado com dados reais.
                        </p>
                    <?php endif; ?>

                    <?php foreach ($exemplo['linhas'] as $linha): ?>
                        <div class="pc-axis-line">
                            <div class="pc-axis-line__top">
                                <span class="pc-axis-line__name"><?= e($linha['nome']) ?></span>
                                <span class="pc-axis-line__intensity" data-level="<?= e($linha['nivel']) ?>"><?= e(t('resultado.intensity.' . $linha['nivel'])) ?></span>
                            </div>
                            <div class="pc-dual">
                                <span class="pc-dual__pole"><b><?= pct($linha['pct_e']) ?></b> <?= e($linha['esq']) ?></span>
                                <span class="pc-dual__track">
                                    <span class="pc-dual__esq" style="width: <?= num_css($linha['pct_e']) ?>%; background: <?= e($linha['cor_e']) ?>"></span>
                                    <span class="pc-dual__dir" style="width: <?= num_css($linha['pct_d']) ?>%; background: <?= e($linha['cor_d']) ?>"></span>
                                </span>
                                <span class="pc-dual__pole pc-dual__pole--dir"><?= e($linha['dir']) ?> <b><?= pct($linha['pct_d']) ?></b></span>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($exemplo['codigo'] !== ''): ?>
                        <a class="pc-link-arrow" style="margin-top:18px" href="<?= url('resultado.php', ['codigo' => $exemplo['codigo']]) ?>">
                            <?= e(t('home.example.open')) ?> <?= icon('arrow-right', 15) ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="pc-section pc-dark" id="como-funciona">
    <div class="pc-wrap">
        <div class="reveal" style="max-width:640px">
            <span class="pc-eyebrow"><?= e(t('home.how.eyebrow')) ?></span>
            <h2 class="pc-title"><?= e(t('home.how.title')) ?></h2>
            <p class="pc-lead pc-mt-2"><?= e(t('home.how.subtitle')) ?></p>
        </div>

        <div class="pc-steps reveal">
            <?php foreach (tl('home.how.steps') as $i => $passo): ?>
                <article class="pc-step">
                    <span class="pc-step__num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                    <h3><?= e($passo['title']) ?></h3>
                    <p><?= e($passo['text']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="pc-section" id="eixos">
    <div class="pc-wrap">
        <div class="pc-section__head reveal">
            <div>
                <span class="pc-eyebrow"><?= e(t('home.axes.eyebrow')) ?></span>
                <h2 class="pc-title"><?= e(t('home.axes.title')) ?></h2>
            </div>
            <p class="pc-lead"><?= e(t('home.axes.subtitle')) ?></p>
        </div>

        <div class="pc-axes">
            <?php foreach ($eixos as $i => $eixo): ?>
                <article class="pc-axis-row reveal">
                    <span class="pc-axis-row__num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                    <span class="pc-axis-row__icon"><?= icon((string) $eixo['icone_esq'], 20) ?></span>
                    <div>
                        <h3><?= e(eixo_nome($eixo)) ?></h3>
                        <div class="pc-axis-row__poles">
                            <i class="pc-pole-dot" style="background: <?= e($eixo['cor_esq']) ?>"></i>
                            <span><?= e(eixo_polo($eixo, 'esq')) ?></span>
                            <span aria-hidden="true">↔</span>
                            <i class="pc-pole-dot" style="background: <?= e($eixo['cor_dir']) ?>"></i>
                            <span><?= e(eixo_polo($eixo, 'dir')) ?></span>
                        </div>
                        <p><?= e(eixo_descricao($eixo)) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if (!$eixosDoBanco): ?>
            <p class="pc-hint pc-text-center pc-mt-3">Banco de dados indisponível: exibindo a definição estática dos eixos.</p>
        <?php endif; ?>
    </div>
</section>

<section class="pc-section" id="formatos" style="background: var(--pc-bg-2); border-block: 1px solid var(--pc-line);">
    <div class="pc-wrap">
        <div class="pc-section__head reveal">
            <div>
                <span class="pc-eyebrow"><?= e(t('home.formats.eyebrow')) ?></span>
                <h2 class="pc-title"><?= e(t('home.formats.title')) ?></h2>
            </div>
            <p class="pc-lead"><?= e(t('home.formats.subtitle')) ?></p>
        </div>

        <div class="pc-formats">
            <article class="pc-format reveal">
                <span class="pc-format__label"><?= e(t('home.formats.quick.label')) ?></span>
                <div class="pc-format__count">
                    <b><?= e(t('home.formats.quick.count')) ?></b>
                    <span><?= e(t('home.formats.quick.unit')) ?></span>
                </div>
                <p><?= e(t('home.formats.quick.text')) ?></p>
                <hr class="pc-format__rule">
                <div class="pc-format__meta">
                    <?= icon('clock', 15) ?>
                    <?= e(t('home.formats.quick.time')) ?>
                </div>
                <a class="pc-btn pc-btn--ghost" href="<?= url('quiz.php', ['modo' => 'rapido']) ?>">
                    <?= e(t('home.formats.quick.cta')) ?>
                    <?= icon('arrow-right', 17) ?>
                </a>
            </article>

            <article class="pc-format pc-format--featured reveal">
                <span class="pc-format__recommended"><?= e(t('home.formats.recommended')) ?></span>
                <span class="pc-format__label"><?= e(t('home.formats.full.label')) ?></span>
                <div class="pc-format__count">
                    <b><?= e(t('home.formats.full.count')) ?></b>
                    <span><?= e(t('home.formats.full.unit')) ?></span>
                </div>
                <p><?= e(t('home.formats.full.text')) ?></p>
                <hr class="pc-format__rule">
                <div class="pc-format__meta">
                    <?= icon('clock', 15) ?>
                    <?= e(t('home.formats.full.time')) ?>
                </div>
                <a class="pc-btn pc-btn--light" href="<?= url('quiz.php', ['modo' => 'completo']) ?>">
                    <?= e(t('home.formats.full.cta')) ?>
                    <?= icon('arrow-right', 17) ?>
                </a>
            </article>

            <article class="pc-format reveal">
                <span class="pc-format__label"><?= e(t('home.formats.extreme.label')) ?></span>
                <div class="pc-format__count">
                    <b><?= e(t('home.formats.extreme.count')) ?></b>
                    <span><?= e(t('home.formats.extreme.unit')) ?></span>
                </div>
                <p><?= e(t('home.formats.extreme.text')) ?></p>
                <hr class="pc-format__rule">
                <div class="pc-format__meta">
                    <?= icon('clock', 15) ?>
                    <?= e(t('home.formats.extreme.time')) ?>
                </div>
                <a class="pc-btn pc-btn--ghost" href="<?= url('quiz.php', ['modo' => 'extremo']) ?>">
                    <?= e(t('home.formats.extreme.cta')) ?>
                    <?= icon('arrow-right', 17) ?>
                </a>
            </article>
        </div>

        <p class="pc-formats__note"><?= e(t('home.formats.note')) ?></p>

        <form class="pc-open-code pc-mt-4" action="<?= url('resultado.php') ?>" method="get" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:center">
            <label class="pc-label" for="codigo-existente" style="margin:0"><?= e(t('home.open_code.label')) ?></label>
            <input class="pc-input" id="codigo-existente" name="codigo" type="text" maxlength="8" placeholder="<?= e(t('home.open_code.placeholder')) ?>" required data-code-input style="width:170px">
            <button class="pc-btn pc-btn--ghost pc-btn--sm" type="submit"><?= e(t('home.open_code.cta')) ?></button>
        </form>
    </div>
</section>

<section class="pc-section" id="comparar">
    <div class="pc-wrap">
        <div class="pc-compare reveal">
            <div>
                <span class="pc-eyebrow"><?= e(t('home.compare.eyebrow')) ?></span>
                <h2 class="pc-title"><?= e(t('home.compare.title')) ?></h2>
                <p class="pc-lead pc-mt-2"><?= e(t('home.compare.subtitle')) ?></p>
            </div>

            <div>
                <form class="pc-compare__form" action="<?= url('sinergia.php') ?>" method="get">
                    <div class="pc-field">
                        <label class="pc-label" for="codigo-a"><?= e(t('home.compare.label_a')) ?></label>
                        <input class="pc-input" id="codigo-a" name="a" type="text" maxlength="8" required data-code-input placeholder="ABCD2345">
                    </div>
                    <div class="pc-field">
                        <label class="pc-label" for="codigo-b"><?= e(t('home.compare.label_b')) ?></label>
                        <input class="pc-input" id="codigo-b" name="b" type="text" maxlength="8" required data-code-input placeholder="WXYZ6789">
                    </div>
                    <button class="pc-btn" type="submit">
                        <?= e(t('home.compare.cta')) ?>
                        <?= icon('users', 17) ?>
                    </button>
                </form>
                <p class="pc-hint"><?= e(t('home.compare.hint')) ?></p>
            </div>
        </div>
    </div>
</section>

<section class="pc-section">
    <div class="pc-wrap pc-faq">
        <div class="reveal">
            <span class="pc-eyebrow"><?= e(t('home.faq.eyebrow')) ?></span>
            <h2 class="pc-title"><?= e(t('home.faq.title')) ?></h2>
            <p class="pc-lead pc-mt-2"><?= e(t('home.axes.subtitle')) ?></p>
        </div>

        <div class="pc-faq__list reveal">
            <?php foreach (tl('home.faq.items') as $i => $item): ?>
                <details<?= $i === 0 ? ' open' : '' ?>>
                    <summary>
                        <?= e($item['q']) ?>
                        <?= icon('chevron', 18) ?>
                    </summary>
                    <p><?= e($item['a']) ?></p>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
